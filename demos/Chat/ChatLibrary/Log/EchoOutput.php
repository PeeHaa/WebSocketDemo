<?php
/**
 * Logs events by echoing them to the cli
 *
 * PHP version 5.4
 *
 * @category   ChatLibrary
 * @package    Core
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace ChatLibrary\Log;

use WebSocketServer\Log\Loggable;

/**
 * Logs events by echoing them to the cli
 *
 * @category   ChatLibrary
 * @package    Core
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
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