<?php
/**
 * Factory that makes server objects and associated factory dependencies
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Core
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Core;

use \WebSocketServer\Event\Emitter as EventEmitter,
    \WebSocketServer\Event\EventFactory,
    \WebSocketServer\Socket\ClientFactory,
    \WebSocketServer\Socket\HandshakeFactory,
    \WebSocketServer\Socket\FrameFactory,
    \WebSocketServer\Http\RequestFactory,
    \WebSocketServer\Http\ResponseFactory,
    \WebSocketServer\Log\Loggable;

/**
 * Factory that makes server objects and associated factory dependencies
 *
 * @category   WebSocketServer
 * @package    Core
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class ServerFactory
{
    /**
     * Create a server and dependencies
     *
     * @var Loggable $logger Optional event logger
     */
    public function create(Loggable $logger = null)
    {
        $eventFactory = new EventFactory;
        return new Server(
            $eventFactory,
            new ClientFactory(
                $eventFactory,
                new HandshakeFactory(
                    new RequestFactory,
                    new ResponseFactory
                ),
                new FrameFactory,
                $logger
            ),
            $logger
        );
    }
}