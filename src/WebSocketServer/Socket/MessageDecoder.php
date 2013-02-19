<?php
/**
 * Decodes raw data from the network into Frame and Message objects
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Socket;

use \WebSocketServer\Event\EventEmitter,
    \WebSocketServer\Event\EventEmitters;

/**
 * Decodes raw data from the network into Frame and Message objects
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class MessageDecoder implements EventEmitter
{
    use EventEmitters;

    /**
     * @var \WebSocketServer\Socket\FrameFactory Frame factory object
     */
    private $frameFactory;

    /**
     * @var \WebSocketServer\Socket\MessageFactory Message factory object
     */
    private $messageFactory;

    /**
     * @var \WebSocketServer\Socket\Frame[] Current pending frames
     */
    private $pendingFrames = [];

    /**
     * @var array Header of currently pending frame
     */
    private $pendingFrameHeader;

    /**
     * Build the message decoder object
     *
     * @param \WebSocketServer\Socket\FrameFactory   $frameFactory   Frame factory object
     * @param \WebSocketServer\Socket\MessageFactory $messageFactory Message factory object
     */
    public function __construct(FrameFactory $frameFactory, MessageFactory $messageFactory)
    {
        $this->frameFactory = $frameFactory;
        $this->messageFactory = $messageFactory;
    }

    /**
     * Parses data from a data buffer into an array representing a frame header
     *
     * @param \WebSocketServer\Socket\Buffer $buffer The data buffer
     *
     * @return bool True is a complete frame header was successfully parsed from the buffer
     *
     * @throws \RangeException When the frame is invalid
     */
    private function parseFrameHeader(Buffer $buffer)
    {
        if ($buffer->length() < 2) {
            return false;
        } 

        $firstByte  = ord($buffer->read(1, 0, Buffer::READ_PEEK));
        $fin = (bool) ($firstByte & 0b10000000);
        $rsv = ($firstByte & 0b01110000) >> 4;
        $opcode = $firstByte & 0b00001111;

        $secondByte = ord($buffer->read(1, 1, Buffer::READ_PEEK));
        $masked = (bool) ($secondByte & 0b10000000);
        $lengthHeader = $secondByte & 0b01111111;

        $bytesProcessed = 2;

        if ($lengthHeader === 0x7F) {
            if ($buffer->length() < 10) {
                return false;
            }

            $lengthLong32Pair = unpack('NN', $buffer->read(8, 2, Buffer::READ_PEEK));
            $bytesProcessed += 8;

            if (PHP_INT_MAX === 0x7fffffff) {
                // Size of packets limited to 2.1GB on 32-bit platforms
                // TODO: fix this (although arguably a non-problem, this is a huge security flaw)
                if ($lengthLong32Pair[0] !== 0 || $lengthLong32Pair[1] < 0) {
                    $buffer->read(10);
                    throw new \RangeException('A frame was received that stated its payload length to be larger than 0x7fffffff bytes, this platform does not support values that large');
                }
                
                $length = $lengthLong32Pair[1];
            } else {
                $length = ($lengthLong32Pair[0] << 32) | $lengthLong32Pair[1];
                
                if ($length < 0) {
                    throw new \RangeException('Invalid frame: Most significant bit of 64-bit length field set');
                }
            }
        } else if ($lengthHeader === 0x7E) {
            if ($buffer->length() < 4) {
                return false;
            }

            $length = current(unpack('n', $buffer->read(2, 2, Buffer::READ_PEEK)));
            $bytesProcessed += 2;
        } else {
            $length = $lengthHeader;
        }

        if ($masked) {
            if ($buffer->length() < $bytesProcessed + 4) {
                return false;
            }

            $maskingKey = $buffer->read(4, $bytesProcessed, Buffer::READ_PEEK);
            $bytesProcessed += 4;
        } else {
            $maskingKey = null;
        }

        $buffer->read($bytesProcessed);

        $this->pendingFrameHeader = [
            'fin'        => $fin,
            'rsv'        => $rsv,
            'opcode'     => $opcode,
            'masked'     => $masked,
            'maskingKey' => $maskingKey,
            'length'     => $length,
        ];

        return true;
    }

    /**
     * Unmask a data string using a key
     *
     * @param string $data The data string
     * @param string $key  The key
     *
     * @return string The unmasked data
     */
    private function unmaskData($data, $key)
    {
        return $data ^ str_pad($key, strlen($data), $key, STR_PAD_RIGHT);
    }

    /**
     * Construct a frame from the current pending frame header and a data buffer
     *
     * @param \WebSocketServer\Socket\Buffer $buffer The data buffer
     *
     * @return \WebSocketServer\Socket\Frame The constructed frame
     */
    private function makeFrame(Buffer $buffer)
    {
        $data = $buffer->read($this->pendingFrameHeader['length']);

        if ($this->pendingFrameHeader['masked']) {
            $data = $this->unmaskData($data, $this->pendingFrameHeader['maskingKey']);
        }

        $frame = $this->frameFactory->create(
            $this->pendingFrameHeader['fin'],
            $this->pendingFrameHeader['rsv'],
            $this->pendingFrameHeader['opcode'],
            $data
        );
        unset($this->pendingFrameHeader);

        return $frame;
    }

    /**
     * Construct a message from an array of frames
     *
     * @param \WebSocketServer\Socket\Frame[] $frame The array of frames
     *
     * @return \WebSocketServer\Socket\Message The constructed message
     */
    private function makeMessage(array $frames)
    {
        return $this->messageFactory->create($frames);
    }

    /**
     * Process the data buffer
     *
     * @param \WebSocketServer\Socket\Buffer $buffer Data buffer
     *
     * @return bool True if the buffer may have more data to process
     */
    public function processData(Buffer $buffer)
    {
        try {
            if (!isset($this->pendingFrameHeader) && !$this->parseFrameHeader($buffer)) {
                return false;
            }
        } catch (\RangeException $e) {
            $this->trigger('error', $e->getMessage());
            return false;
        }

        if ($buffer->length() < $this->pendingFrameHeader['length']) {
            return false;
        }

        $frame = $this->makeFrame($buffer);
        $this->trigger('frame', $frame);

        $opcode = $frame->getOpcode();
        if ($opcode && $this->pendingFrames && $frame->getOpcode() < 0x08) {
            $this->trigger('error', 'The client started a new data frame between data frame fragments');
            return false;
        }

        if ($opcode >= 0x08) {
            if (!$frame->isFin()) {
                $this->trigger('error', 'Invalid frame: Control frames cannot be fragmented');
                return false;
            }

            $message = $this->makeMessage([$frame]);
        } else {
            $this->pendingFrames[] = $frame;

            if ($frame->isFin()) {
                $message = $this->makeMessage($this->pendingFrames);
                $this->pendingFrames = [];
            }
        }

        if (isset($message)) {
            $this->trigger('message', $message);
        }

        return true;
    }
}