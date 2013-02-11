(function() {
    var webSocketClient = new WebSocketClient(true);

    webSocketClient.connect('ws://www.chat.localhost:1337/start.php');

    document.getElementById('send-message').addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        webSocketClient.send(document.getElementById('message').value);

        document.getElementById('message').value = '';
    });
}());