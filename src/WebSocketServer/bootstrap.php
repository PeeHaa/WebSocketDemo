<?php
/**
 * This bootstraps the websocket server
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer;

use WebSocketServer\Core\Autoloader;

require_once __DIR__ . '/Core/Autoloader.php';

$autoloader = new AutoLoader(__NAMESPACE__, dirname(__DIR__));

$autoloader->register();