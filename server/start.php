<?php

/**
 * Simple example of a chat server
 */

use \WebSocketServer\Core\ServerFactory,
    \WebSocketServer\Core\Server,
    \WebSocketServer\Event\Event,
    \WebSocketServer\Socket\Client,
    \WebSocketServer\Socket\Frame,
    \WebSocketServer\Log\Loggable;

// setup environment
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('date.timezone', 'Europe/Amsterdam');
set_time_limit(0);
ob_implicit_flush();

// load the lib
require __DIR__ . '/../src/WebSocketServer/bootstrap.php';

class ChatApplication
{
    /**
     * @var \WebSocketServer\Core\Server $server The websocket server
     */
    private $server;

    /**
     * Construct the application
     *
     * @param \WebSocketServer\Core\Server $server The websocket server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Start the application
     *
     * @param string $address The listen socket address
     */
    public function start($address)
    {
        $this->server->on('clientconnect', array($this, 'onClientConnect'));

        $this->server->start($address);
    }

    /**
     * Callback when a client connects
     *
     * @param \WebSocketServer\Event\Event   $event  The event
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onClientConnect(Event $event, Client $client)
    {
        $client->on('message',    array($this, 'onMessage')   );
        $client->on('disconnect', array($this, 'onDisconnect'));
        $client->on('error',      array($this, 'onError')     );

        $client->getServer()->sendToAllButClient('User #' . $client->getId() . ' entered the room', $client);
    }

    /**
     * Callback when a client sends a message
     *
     * @param \WebSocketServer\Event\Event   $event  The event
     * @param \WebSocketServer\Socket\Client $client The client
     * @param \WebSocketServer\Socket\Frame  $frame  The message
     */
    public function onMessage(Event $event, Client $client, Frame $frame)
    {
        if ($frame->getData() == '!!stop') {
            $client->getServer()->broadcast('#' . $client->getId() . ' stopped the server');
            $client->getServer()->stop();
        } else {
            $client->getServer()->broadcast('#' . $client->getId() . ': ' . $frame->getData());
        }
    }

    /**
     * Callback when a client disconnects
     *
     * @param \WebSocketServer\Event\Event   $event  The event
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onDisconnect(Event $event, Client $client)
    {
        $client->getServer()->sendToAllButClient('User #' . $client->getId() . ' left the room', $client);
    }

    /**
     * Callback when a client suffers an error
     *
     * @param \WebSocketServer\Event\Event   $event   The event
     * @param \WebSocketServer\Socket\Client $client  The client
     * @param string                         $message The error description
     */
    public function onError(Event $event, Client $client, $message)
    {
        $client->getServer()->sendToAllButClient('User #' . $client->getId() . ' fell over', $client);
    }
}

class EchoOutput implements Loggable
{
    /**
     * @var int Logging level
     */
    private $level = self::LEVEL_INFO;

    /**
     * @var array Logging level to string description map
     */
    private $levelStrs = [
        self::LEVEL_ERROR => 'INFO',
        self::LEVEL_WARN  => 'WARN',
        self::LEVEL_INFO  => 'INFO',
        self::LEVEL_DEBUG => 'DEBUG',
    ];

    /**
     * Write a message to the log
     *
     * @param string $message The message
     */
    public function write($message, $level)
    {
        if ($level <= $this->level) {
            $levelStr = $this->levelStrs[$level];
            echo '[' . $levelStr . '] [' . (new \DateTime())->format('d-m-Y H:i:s') . '] '. $message . "\n";
        }
    }

    /**
     * Set the logging level
     *
     * @param int $level New logging level
     */
    public function setLevel($level)
    {
        $this->level = (int) $level;
    }

    /**
     * Get the current logging level
     *
     * @return int Current logging level
     */
    public function getLevel()
    {
        return $this->level;
    }
}

/**
 * Start the server
 */
$server = (new ServerFactory)->create(new EchoOutput);

$application = new ChatApplication($server);
$application->start('tcp://0.0.0.0:1337');