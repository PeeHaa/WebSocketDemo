<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('date.timezone', 'Europe/Amsterdam');
set_time_limit(0);
ob_implicit_flush();

class WebSocket
{
    const SIGNING_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    const OP_CONT  = 0x00;
    const OP_TEXT  = 0x01;
    const OP_BIN   = 0x02;
    const OP_CLOSE = 0x08;
    const OP_PING  = 0x09;
    const OP_PONG  = 0x0A;

    const ATTR_FIN    = 0x01;
    const ATTR_MASKED = 0x02;

    private $clientFactory;

    private $requestFactory;

    private $responseFactory;

    private $masterSocket;

    private $sockets = [];

    private $clients = [];

    public function __construct(ClientFactory $clientFactory, RequestFactory $requestFactory, ResponseFactory $responseFactory)
    {
        $this->clientFactory   = $clientFactory;
        $this->requestFactory  = $requestFactory;
        $this->responseFactory = $responseFactory;
    }

    public function start($address, $port)
    {
        $this->setMasterSocket($address, $port);

        while(true) {
            $changedSockets = $this->sockets;

            $write  = null;
            $except = null;
            $tv_sec = null;

            socket_select($changedSockets, $write, $except, $tv_sec);
            foreach ($changedSockets as $changedSocket) {
                if ($changedSocket == $this->master) {
                    $client = socket_accept($this->master);

                    if ($client < 0) {
                        $this->log('socket_accept() failed');
                        continue;
                    } else {
                        $this->addClient($client);
                    }
                } else {
                    $bytes = @socket_recv($changedSocket, $buffer, 2048, 0);
                    if ($bytes == 0) {
                        $this->disconnectClient($changedSocket);
                    } else {
                        $client = $this->getClientBySocket($changedSocket);

                        if($client->didHandshake() === false) {
                            $this->dohandshake($client, $buffer);
                        } else{
                            $this->process($client, $buffer);
                        }
                    }
                }
            }
        }
    }

    private function setMasterSocket($address, $port)
    {
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->masterSocket === false) {
            throw new \Exception('Failed to create the master socket.');
        }

        if (socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1) === false) {
            throw new \Exception('Failed to set the master socket options.');
        }

        if (socket_bind($this->master, $address, $port) === false) {
            throw new \Exception('Failed to bind the master socket.');
        }

        if (socket_listen($this->master) === false) {
            throw new \Exception('Failed to listen.');
        }

        $this->addSocket($this->master);

        $this->broadcast('Server Started : ' . (new DateTime())->format('Y-m-d H:i:s'));
        $this->broadcast('Listening on   : ' . $address . ':' . $port);
        $this->broadcast('Master socket  : ' . $this->master . "\n");
    }

    private function addSocket($socket)
    {
        $this->sockets[] = $socket;
    }

    private function addClient($socket)
    {
        $this->clients[] = $this->clientFactory->create(uniqid(), $socket);
        $this->addSocket($socket);

        $this->log($socket . ' connected.');
    }

    private function disconnectClient($socket)
    {
        $found = null;
        $n = count($this->clients);
        for ($i = 0; $i < $n; $i++) {
            if ($this->clients[$i]->getSocket() == $socket) {
                $found = $i;
                break;
            }
        }

        if (!is_null($found)) {
            array_splice($this->clients, $found, 1);
        }

        $index = array_search($socket, $this->sockets);

        socket_close($socket);

        $this->log($socket . ' disconnected.');

        if($index >= 0) {
            array_splice($this->sockets, $index, 1);
        }
    }

    private function doHandshake($client, $buffer)
    {
        $this->log('Requesting handshake...');
        $this->log($buffer);

        $request  = $this->requestFactory->create($buffer);
        $response = $this->responseFactory->create();

        $this->log('Handshaking...');

        $response->addHeader('HTTP/1.1 101 WebSocket Protocol Handshake');
        $response->addHeader('Upgrade', 'WebSocket');
        $response->addHeader('Connection', 'Upgrade');
        $response->addHeader('Sec-WebSocket-Origin', $request->getOrigin());
        $response->addHeader('Sec-WebSocket-Location', 'ws://' . $request->getHost() . $request->getResource());
        $response->addHeader('Sec-WebSocket-Accept', $this->getSignature($request->getKey()));

        $responseString = $response->buildResponse() . chr(0);

        socket_write($client->getSocket(), $responseString, strlen($responseString));

        $client->setHandshake(true);

        $this->log($response->buildResponse());
        $this->log('Done handshaking...');

        return true;
    }

    private function getSignature($key)
    {
        return base64_encode(sha1($key . self::SIGNING_KEY, true));
    }

    private function process($client, $message)
    {
        $this->sendToClient($client->getSocket(), 'RECEIVED MESSAGE:: ' . $this->decodeMessage($message));
    }

    private function decodeMessage($message)
    {
        $payloadLength = '';
        $mask = '';
        $unmaskedPayload = '';
        $decodedData = array();

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($message[0]));
        $secondByteBinary = sprintf('%08b', ord($message[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($message[1]) & 127;

        switch ($opcode) {
            case 1:
                $decodedData['type'] = 'text';
                break;

            case 8:
                $decodedData['type'] = 'close';
                break;

            case 9:
                $decodedData['type'] = 'ping';
                break;

            case 10:
                $decodedData['type'] = 'pong';
                break;

            default:
                // Close connection on unknown opcode:
                //$this->close(1003);
                break;
        }

        if ($payloadLength === 126) {
            $mask = substr($message, 4, 4);
            $payloadOffset = 8;
        } elseif ($payloadLength === 127) {
            $mask = substr($message, 10, 4);
            $payloadOffset = 14;
        } else {
            $mask = substr($message, 2, 4);
            $payloadOffset = 6;
        }

        $dataLength = strlen($message);

        if ($isMasked === true) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                $unmaskedPayload .= $message[$i] ^ $mask[$j % 4];
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($message, $payloadOffset);
        }

        return $decodedData['payload'];
    }

    private function encodeMessage($message, $type = self::OP_TEXT, $flags = 3)
    {
        $frameHeaders = [];

        $finBit = $flags & self::ATTR_FIN ? 0x80 : 0x00;
        $frameHeaders[] = chr($finBit | $type);

        $payloadLength = $flags & self::ATTR_MASKED ? 0x80 : 0x00;
        $messageLength = strlen($message);

        if ($messageLength > 0xFFFF) {
            $frameHeaders[] = chr($payloadLength | 0x7F);
            $frameHeaders[] = "\x00\x00\x00\x00".pack('N', $messageLength); // This limits you to 2.1GB - does that matter?
        } elseif ($messageLength > 0x7D) {
            $frameHeaders[] = chr($payloadLength | 0x7E);
            $frameHeaders[] = pack('n', $messageLength);
        } else {
            $frameHeaders[] = chr($payloadLength | $messageLength);
        }

        if ($flags & self::ATTR_MASKED) {
            $mask = pack('C*', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            $frameHeaders[] = $mask;

            $message ^= str_pad('', $messageLength, $mask);
        }

        return implode('', $frameHeaders).$message;
    }

    private function encodeMessagex($message, $type = 'text', $masked = true)
    {
        $frameHeaders = [];

        switch ($type) {
            case 'text':
                $frameHeaders[0] = 129;
                break;

            case 'close':
                $frameHeaders[0] = 136;
                break;

            case 'ping':
                $frameHeaders[0] = 137;
                break;

            case 'pong':
                $frameHeaders[0] = 138;
                break;
        }

        $messageLength = strlen($message);

        if ($messageLength > 65535) {
            $messageLengthBin = str_split(sprintf('%064b', $messageLength), 8);
            $frameHeaders[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHeaders[$i + 2] = bindec($messageLengthBin[$i]);
            }

            if ($frameHeaders[2] > 127) {
                // can this happen? Frame too big! Better drink my own piss
                return false;
            }
        } elseif ($messageLength > 125) {
            $messageLengthBin = str_split(sprintf('%016b', $messageLength), 8);
            $frameHeaders[1] = ($masked === true) ? 254 : 126;
            $frameHeaders[2] = bindec($messageLengthBin[0]);
            $frameHeaders[3] = bindec($messageLengthBin[1]);
        } else {
            $frameHeaders[1] = ($masked === true) ? $messageLength + 128 : $messageLength;
        }

        foreach (array_keys($frameHeaders) as $i) {
            $frameHeaders[$i] = chr($frameHeaders[$i]);
        }
        if ($masked === true) {
            $mask = array();
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHeaders = array_merge($frameHeaders, $mask);
        }
        $frame = implode('', $frameHeaders);

        $framePayload = array();
        for ($i = 0; $i < $messageLength; $i++) {
            $frame .= ($masked === true) ? $message[$i] ^ $mask[$i % 4] : $message[$i];
        }

        return $frame;
    }

    private function getClientBySocket($socket)
    {
        foreach($this->clients as $client) {
            if ($client->doesSocketMatch($socket) === true) {
                return $client;
            }
        }
    }

    private function broadcast($message)
    {
        echo $message . "\n";
    }

    private function sendToClient($clientSocket, $message)
    {
        $this->broadcast('> ' . $message);

        $encodeMessage = $this->encodeMessage('response', self::OP_TEXT, self::ATTR_FIN);

        socket_write($clientSocket, $encodeMessage, strlen($encodeMessage));

        $this->log('Number of bytes sent to client: ' . strlen($encodeMessage));
        $this->log('Hexdump of data sent to client: ' . bin2hex($encodeMessage));
    }

    private function log($message)
    {
        echo '[LOG] ' . $message . "\n";
    }
}

class ClientFactory
{
    public function create($id, $socket)
    {
        return new Client($id, $socket);
    }
}

class Client
{
    private $id;

    private $socket;

    private $handshake = false;

    public function __construct($id, $socket)
    {
        $this->id = $id;
        $this->socket = $socket;
    }

    public function doesSocketMatch($socket)
    {
        return $this->socket == $socket;
    }

    public function setHandshake($handshake)
    {
        $this->handshake = $handshake;
    }

    public function didHandshake()
    {
        return $this->handshake === true;
    }

    public function getSocket()
    {
        return $this->socket;
    }
}

class RequestFactory
{
    public function create($requestData)
    {
        return new Request($requestData);
    }
}

class Request
{
    private $requestData;

    public function __construct($requestData)
    {
        $this->requestData = $requestData;
    }

    public function getResource()
    {
        if (preg_match('/GET (.*) HTTP/', $this->requestData, $match)) {
            return $match[1];
        }
    }

    public function getHost()
    {
        if (preg_match("/Host: (.*)\r\n/", $this->requestData, $match)) {
            return $match[1];
        }
    }

    public function getOrigin()
    {
        if (preg_match("/Origin: (.*)\r\n/", $this->requestData, $match)) {
            return $match[1];
        }
    }

    public function getKey()
    {
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $this->requestData, $match)) {
            return $match[1];
        }
    }

    public function getLastBytes($numberOfBytes)
    {
        $lastBytes = substr($this->requestData, -$numberOfBytes);

        if ($lastBytes) {
            return $lastBytes;
        }
    }
}

class ResponseFactory
{
    public function create()
    {
        return new Response();
    }
}

class Response
{
    private $headers = [];

    private $body;

    public function addHeader($key, $value = null)
    {
        $this->headers[$key] = $value;
    }

    public function setBody($content)
    {
        $this->body = $content;
    }

    public function buildResponse()
    {
        return $this->buildHeaders() . "\r\n";
    }

    private function buildHeaders()
    {
        $headers = '';
        foreach ($this->headers as $key => $value) {
            if ($value !== null) {
                $key .= ': ';
            }

            $headers .= $key . $value . "\r\n";
        }

        return $headers;
    }
}

$clientFactory   = new ClientFactory();
$requestFactory  = new RequestFactory();
$responseFactory = new ResponseFactory();
$webSocket       = new WebSocket($clientFactory, $requestFactory, $responseFactory);

$webSocket->start('127.0.0.1', 1337);