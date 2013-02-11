<?php
/**
 * Decodes a socket frame
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Socket
 * @subpackage Frame
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Socket\Frame;

use WebSocketServer\Cache\Queue;

/**
 * Decodes a socket frame
 *
 * @category   WebSocketServer
 * @package    Socket
 * @subpackage Frame
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Decoder
{
    const OP_CONT  = 0x00;
    const OP_TEXT  = 0x01;
    const OP_BIN   = 0x02;
    const OP_CLOSE = 0x08;
    const OP_PING  = 0x09;
    const OP_PONG  = 0x0A;

    const ATTR_FIN    = 0x01;
    const ATTR_MASKED = 0x02;

    /**
     * @var \WebSocketServer\Cache\Queue Cache of messages
     */
    private $cache;

    /**
     * Build the decoder object
     *
     * @param int $cacheSize The number of frame to cache
     */
    public function __construct(Queue $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Decode the message as frame
     *
     * @param string $message The message to decode
     */
    public function getDecodedMessage($message)
    {
        if ($this->cache->keyExists($message)) {
            return $this->cache->getItem($message);
        }

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
            case self::OP_TEXT:
                $decodedData['type'] = 'text';
                break;

            case self::OP_CLOSE:
                $decodedData['type'] = 'close';
                break;

            case self::OP_PING:
                $decodedData['type'] = 'ping';
                break;

            case self::OP_PONG:
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

        $this->cache->add($message, $decodedData['payload']);

        return $decodedData['payload'];
    }
}