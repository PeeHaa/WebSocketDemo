<?php

/**
 * Simple example of a chat server
 */
use WebSocketServer\Core\ServerFactory,
    ChatLibrary\User\Manager as UserManager,
    ChatLibrary\User\Factory as UserFactory,
    ChatLibrary\Room\Manager as RoomManager,
    ChatLibrary\Room\Entity as RoomEntity,
    ChatLibrary\Room\Factory as RoomFactory,
    ChatLibrary\Core\Application as ChatApplication,
    ChatLibrary\Log\EchoOutput;

// setup environment
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('date.timezone', 'Europe/Amsterdam');
set_time_limit(0);
ob_implicit_flush();

// load the lib
require __DIR__ . '/../src/WebSocketServer/bootstrap.php';

// load the chat lib
require __DIR__ . '/../demos/Chat/ChatLibrary/bootstrap.php';

// setup the chat application
$room = new RoomEntity(1, 'Sandbox');
$roomFactory = new RoomFactory();
$roomManager = new RoomManager($roomFactory);

$roomManager->add($room);

/**
 * Start the server
 */
$server = (new ServerFactory)->create(new EchoOutput);
$userManager = new UserManager(new UserFactory);

$application = new ChatApplication($server, $userManager, $roomManager);

$application->start('0.0.0.0:1337');
//$application->start('tls://0.0.0.0:1337', 'localhost.cert');