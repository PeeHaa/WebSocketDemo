(function() {
    var webSocketClient, cookieManager, url;

    webSocketClient = new WebSocketClient(true);
    cookieManager = new CookieManager();

    url = 'ws://localhost:1337/start.php?userid=' + cookieManager.getCookie('userid');
    //url = 'wss://localhost:1337/start.php?userid=' + cookieManager.getCookie('userid');

    webSocketClient.connect(url);

    document.getElementById('send-message').addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        webSocketClient.send(document.getElementById('message').value);

        document.getElementById('message').value = '';
    });
}());