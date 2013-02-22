<?php
/**
 * Core of our chat application. This handles all events and delegates them
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
namespace ChatLibrary\Core;

use WebSocketServer\Core\Server,
    WebSocketServer\Event\Event,
    WebSocketServer\Http\Request,
    WebSocketServer\Http\Response,
    WebSocketServer\Socket\Client,
    WebSocketServer\Socket\Frame,
    WebSocketServer\Socket\Message,
    ChatLibrary\User\Entity as UserEntity,
    ChatLibrary\User\Manager as UserManager,
    ChatLibrary\Room\Manager as RoomManager;

/**
 * Core of our chat application. This handles all events and delegates them
 *
 * @category   ChatLibrary
 * @package    Core
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Application
{
    /**
     * @var \WebSocketServer\Core\Server $server The websocket server
     */
    private $server;

    /**
     * @var \ChatLibrary\Chat\User\Manager The user manager
     */
    private $userManager;

    /**
     * @var \ChatLibrary\Chat\Room\Manager The room manager
     */
    private $roomManager;

    /**
     * Construct the application
     *
     * @param \WebSocketServer\Core\Server   $server      The websocket server
     * @param \ChatLibrary\User\Manager $userManager The user manager
     * @param \ChatLibrary\Room\Manager $roomManager The room manager
     */
    public function __construct(Server $server, UserManager $userManager, RoomManager $roomManager)
    {
        $this->server = $server;
        $this->userManager = $userManager;
        $this->roomManager = $roomManager;
    }

    /**
     * Start the application
     *
     * @param string $address The listen socket address
     */
    public function start($address, $localCert = null)
    {
        if (isset($localCert)) {
            $this->server->setSocketContextOption('ssl', 'local_cert', $localCert);
        }

        // $client->on('listening', ...);
        $this->server->on('clientconnect', [$this, 'onClientConnect']);
        // $client->on('clientremove', ...);
        // $client->on('close', ...);

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
        // $client->on('cryptoenabled', ...);
        $client->on('handshake',  [$this, 'onHandshake' ]);
        // $client->on('frame', ...);
        $client->on('message',    [$this, 'onMessage'   ]);
        $client->on('disconnect', [$this, 'onDisconnect']);
        $client->on('error',      [$this, 'onError'     ]);
    }

    /**
     * Callback when a client connects
     *
     * @param \WebSocketServer\Event\Event   $event    The event
     * @param \WebSocketServer\Socket\Client $client   The client
     * @param \WebSocketServer\Http\Request  $request  The handshake request
     * @param \WebSocketServer\Http\Response $response The handshake response
     *
     * @return bool Whether the handshake was accepted
     */
    public function onHandshake(Event $event, Client $client, Request $request, Response $response)
    {
        $userId = $request->getUrlVar('userid');
        $user = $this->userManager->getUserById($userId);
        $user->addClient($client);

        $client->setAppData('user', $user);

        return true;
    }

    /**
     * Callback when a client sends a message
     *
     * @param \WebSocketServer\Event\Event    $event   The event
     * @param \WebSocketServer\Socket\Client  $client  The client
     * @param \WebSocketServer\Socket\Message $message The message
     */
    public function onMessage(Event $event, Client $client, Message $message)
    {
        if ($message->getOpcode() === Message::OP_TEXT) {
            if ($message->getData() == '!!staph') {
                $client->getServer()->broadcast($client->getAppData('user')->getId() . ' stopped the server');
                $client->getServer()->stop();
            } else {
                $data = json_decode($message->getData());
                $user = $client->getAppData('user');
                switch ($data->event) {
                    case 'listRooms':
                        $client->sendText(json_encode([
                            'event' => $data->event,
                            'rooms' => $this->roomManager->getAllNames(),
                        ]));
                        return;

                    case 'connect':
                        $room = $this->roomManager->getById($data->roomId);
                        $user->setUsername($data->username);
                        $room->addUser($user);

                        $client->sendText(json_encode([
                            'event' => $data->event,
                            'roomId' => $data->roomId,
                            'result' => 'success',
                        ]));

                        $client->getServer()->sendToAllButClient(json_encode([
                            'event'      => 'userConnected',
                            'id'         => $user->getHashedId(),
                            'username'   => $user->getUsername(),
                            'avatarHash' => md5($user->getUsername()),
                            'message'    => $user->getUsername() . ' entered the room.',
                        ]), $client);
                        return;

                    case 'loadRoom':
                        $user->setStatus(UserEntity::STATUS_ONLINE);
                        $room = $this->roomManager->getById($data->roomId);
                        $userCollection = $room->getUsers();

                        $users = [];
                        foreach ($userCollection as $user) {
                            $users[] = $user->getInfo();
                        }

                        $client->sendText(json_encode([
                            'event'    => $data->event,
                            'users'    => $users,
                            'messages' => [],
                        ]));
                        return;

                    case 'userStartedTyping':
                        $client->getServer()->broadcast(json_encode([
                            'event'  => 'userChangedStatus',
                            'id'     => $user->getHashedId(),
                            'status' => 'typing',
                        ]));
                        return;

                    case 'userStoppedTyping':
                        $client->getServer()->broadcast(json_encode([
                            'event' => $data->event,
                            'id'    => $user->getHashedId(),
                        ]));
                        return;

                    case 'sendMessage':
                        $room = $this->roomManager->getById($data->roomId);

                        $client->getServer()->broadcast(json_encode([
                            'event' => $data->event,
                            'message' => [
                                'type' => 'post',
                                'text' => $data->message,
                                'user' => [
                                    'id' => $user->getHashedId(),
                                    'username' => $user->getUsername(),
                                    'avatarHash' => md5($user->getUsername()),
                                ],
                            ],
                        ]));
                        return;
                }
            }
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
        if ($user = $client->getAppData('user')) {
            $user->removeClient($client);

            if ($user->numClients() < 1) {
                $this->userManager->removeUser($user);
                $client->getServer()->sendToAllButClient(json_encode([
                    'event'   => 'userDisconnected',
                    'id'      => $user->getHashedId(),
                    'message' => $user->getUsername(),
                ]), $client);
            }
        } else {
            $client->getServer()->sendToAllButClient('#' . $client->getId() . ' has left the room', $client);
        }

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
        if ($user = $client->getAppData('user')) {
            $userId = $user->getId();
        } else {
            $userId = '#' . $client->getId();
        }

        $client->getServer()->sendToAllButClient($userId . ' fell over', $client);
    }
}