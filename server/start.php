<?php

/**
 * Simple example of a chat server
 */

use \WebSocketServer\Core\ServerFactory,
    \WebSocketServer\Core\Server,
    \WebSocketServer\Event\Event,
    \WebSocketServer\Http\Request,
    \WebSocketServer\Http\Response,
    \WebSocketServer\Socket\Client,
    \WebSocketServer\Socket\Frame,
    \WebSocketServer\Socket\Message,
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
     * @var \UserManager The user manager
     */
    private $userManager;

    /**
     * Construct the application
     *
     * @param \WebSocketServer\Core\Server $server The websocket server
     */
    public function __construct(Server $server, UserManager $userManager)
    {
        $this->server = $server;
        $this->userManager = $userManager;
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
        
        if ($user->numClients() < 2) {
            $client->getServer()->sendToAllButClient($userId . ' entered the room', $client);
        }

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
            if ($message->getData() == '!!stop') {
                $client->getServer()->broadcast($client->getAppData('user')->getId() . ' stopped the server');
                $client->getServer()->stop();
            } else {
                $client->getServer()->broadcast($client->getAppData('user')->getId() . ': ' . $message->getData());
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
        $user = $client->getAppData('user');
        $user->removeClient($client);

        if ($user->numClients() < 1) {
            $this->userManager->removeUser($user);
            $client->getServer()->sendToAllButClient($user->getId() . ' has left the room', $client);
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
        $client->getServer()->sendToAllButClient($client->getAppData('user')->getId() . ' fell over', $client);
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

class UserManager
{
    private $users = [];

    private $userFactory;

    public function __construct(UserFactory $userFactory)
    {
        $this->userFactory = $userFactory;
    }

    public function getUserById($id)
    {
        if (!isset($this->users[$id])) {
            $this->users[$id] = $this->userFactory->create($id);
        }

        return $this->users[$id];
    }

    public function removeUser(User $user)
    {
        unset($this->users[$user->getId()]);
    }
}

class User
{
    private $clients = [];   

    private $id;
    
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function addClient(Client $client)
    {
        $this->clients[$client->getId()] = $client;
    }

    public function removeClient(Client $client)
    {
        unset($this->clients[$client->getId()]);
    }

    public function numClients()
    {
        return count($this->clients);
    }
}

class UserFactory
{
    public function create($id)
    {
        return new User($id);
    }
}

/**
 * Start the server
 */
$server = (new ServerFactory)->create(new EchoOutput);
$userManager = new UserManager(new UserFactory);

$application = new ChatApplication($server, $userManager);

//$application->start('0.0.0.0:1337');
$application->start('tls://0.0.0.0:1337', 'localhost.cert');
