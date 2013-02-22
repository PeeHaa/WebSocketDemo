<?php

/**
 * Simple example of a chat server
 */
use WebSocketServer\Event\Handler,
    WebSocketServer\Log\EchoOutput,
    WebSocketServer\Socket\ClientFactory,
    WebSocketServer\Http\RequestFactory,
    WebSocketServer\Http\ResponseFactory,
    WebSocketServer\Cache\Queue,
    WebSocketServer\Socket\FrameFactory,
    WebSocketServer\Core\Server,
    WebSocketServer\Socket\Client,
    WebSocketServer\Socket\Frame,
    ChatLibrary\Chat\RoomCollection,
    ChatLibrary\Chat\Room,
    ChatLibrary\Chat\UserFactory;

// setup environment
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('date.timezone', 'Europe/Amsterdam');
set_time_limit(0);
ob_implicit_flush();
session_start();

// load the websocket lib
require __DIR__ . '/../src/WebSocketServer/bootstrap.php';

// load the chat lib
require __DIR__ . '/../demos/Chat/ChatLibrary/bootstrap.php';

class EventHandler implements Handler
{
    private $roomCollection;

    private $userFactory;

    public function __construct(RoomCollection $roomCollection, UserFactory $userFactory)
    {
        $this->roomCollection = $roomCollection;
        $this->userFactory    = $userFactory;
    }

    /**
     * Callback when a client connects
     *
     * @param \WebSocketServer\Core\Server   $server The websocket server
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onConnect(Server $server, Client $client)
    {
        //$server->sendToAllButClient('User #' . $client->getId() . ' entered the room', $client);
    }

    /**
     * Callback when a client sends a message
     *
     * @param \WebSocketServer\Core\Server   $server  The websocket server
     * @param \WebSocketServer\Socket\Client $client  The client
     * @param \WebSocketServer\Socket\Frame  $frame The message
     */
    public function onMessage(Server $server, Client $client, Frame $frame)
    {
        $data = json_decode($frame->getData());

        switch ($data->event) {
            case 'listRooms':
                $client->sendText(json_encode([
                    'event' => $data->event,
                    'rooms' => $this->getRooms(),
                ]));
                break;

            case 'connect':
                $client->sendText(json_encode([
                    'event' => $data->event,
                    'result' => $this->connect($data->roomId, $data->username),
                    'roomId' => $data->roomId,
                ]));
                break;

            case 'loadRoom':
                $client->sendText(json_encode([
                    'event'    => $data->event,
                    'users'    => $this->getUsersOfRoom($data->roomId),
                    'messages' => [],
                ]));

                $room = $this->roomCollection->getById($data->roomId);
                $user = $room->getUser(session_id());

                $server->sendToAllButClient(json_encode([
                    'event'      => 'userConnected',
                    'id'         => session_id(),
                    'username'   => $user->getUsername(),
                    'avatarHash' => md5($user->getUsername()),
                    'message'    => $user->getUsername() . ' entered the room.',
                ]), $client);
                break;

            case 'userStartedTyping':
                $server->broadcast(json_encode([
                    'event' => $data->event,
                    'id'    => session_id(),
                ]));
                break;

            case 'userStoppedTyping':
                $server->broadcast(json_encode([
                    'event' => $data->event,
                    'id'    => session_id(),
                ]));
                break;

            case 'sendMessage':
                $room = $this->roomCollection->getById($data->roomId);
                $user = $room->getUser(session_id());

                $server->broadcast(json_encode([
                    'event' => $data->event,
                    'message' => [
                        'type' => 'post',
                        'text' => $data->message,
                        'user' => [
                            'id' => session_id(),
                            'username' => $user->getUsername(),
                            'avatarHash' => md5($user->getUsername()),
                        ],
                    ],
                ]));
                break;
        }
    }

    /**
     * Callback when a client disconnects
     *
     * @param \WebSocketServer\Core\Server   $server The websocket server
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onDisconnect(Server $server, Client $client)
    {
        $server->sendToAllButClient(json_encode([
            'event' => 'userDisconnected',
            'id'    => session_id(),
            'message' => 'Someone left the room.',
        ]), $client);
    }

    /**
     * Callback when a client suffers an error
     *
     * @param \WebSocketServer\Core\Server   $server  The websocket server
     * @param \WebSocketServer\Socket\Client $client  The client
     * @param string                         $message The error description
     */
    public function onError(Server $server, Client $client, $message)
    {
        $server->sendToAllButClient('User #' . $client->getId() . ' fell over', $client);
    }

    private function getRooms()
    {
        $rooms = [];
        foreach ($this->roomCollection->getAll() as $id => $room) {
            $rooms[$id] = $room->getName();
        }

        return $rooms;
    }

    private function connect($roomId, $username = null)
    {
        if (!$this->roomCollection->exists($roomId)) {
            return 'failed';
        }

        $user = $this->userFactory->build(session_id(), $username);

        $room = $this->roomCollection->getById($roomId);
        $room->addUser($user);

        return 'success';
    }

    private function getUsersOfRoom($roomId)
    {
        $room = $this->roomCollection->getById($roomId);

        $users = [];
        foreach ($room->getUsers() as $userId => $user) {
            $users[] = [
                'id' => $userId,
                'username' => $user->getUsername(),
                'avatarHash' => md5($user->getUsername()),
                'status' => 'idle',
            ];
        }

        return $users;
    }
}

/**
 * Setup the chat
 */
$roomCollection = new RoomCollection();
$room           = new Room(1, 'Sandbox');
$userFactory    = new UserFactory();
$roomCollection->add($room);

/**
 * Start the server
 */
$eventHandler    = new EventHandler($roomCollection, $userFactory);
$logger          = new EchoOutput();
$requestFactory  = new RequestFactory();
$responseFactory = new ResponseFactory();
$frameFactory    = new FrameFactory();
$clientFactory   = new ClientFactory($eventHandler, $logger, $requestFactory, $responseFactory, $frameFactory);
$socketServer    = new Server($eventHandler, $logger, $clientFactory);

$socketServer->start('127.0.0.1', 1337);