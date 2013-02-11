<?php
/**
 * Encodes a socket frame
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Socket
 * @subpackage Frame
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @author     DaveRandom <https://github.com/DaveRandom>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Socket\Frame;

use WebSocketServer\Cache\Queue;

/**
 * Encodes a socket frame
 *
 * @category   WebSocketServer
 * @package    Socket
 * @subpackage Frame
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Encoder
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
     * Build the encoder object
     *
     * @param int $cacheSize The number of frame to cache
     */
    public function __construct(Queue $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Encode the message as frame
     *
     * @param string $message The message to frame
     * @param int    $type    The type of the frame
     * @param int    $flags
     */
    public function getEncodedMessage($message, $type = self::OP_TEXT, $flags = self::ATTR_FIN)
    {
        if ($this->cache->keyExists($message)) {
            return $this->cache->getItem($message);
        }

        $originalMessage = $message;

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

        $this->cache->add($originalMessage, implode('', $frameHeaders) . $message);

        return implode('', $frameHeaders).$message;
    }
}