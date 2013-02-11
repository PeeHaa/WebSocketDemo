function WebSocketClient(debug) {
    this.debug = false;
    if (typeof debug === 'boolean') {
        this.debug = debug;
    }

    this.connected = false;
    this.socket = {};

    this.conversationElement = document.getElementById('conversation');

    this.connect = function(url) {
        try {
            this.socket = new WebSocket(url);

            this.log('Socket change: ' + this.socket.readyState);

            this.socket.onopen = function() {
                this.log('Socket status: ' + this.socket.readyState + '(open)');
            }.bind(this);

            this.socket.onmessage = function(message) {
                this.log('Socket received: ' + message.data);

                this.addMessageToConversation(message.data);
            }.bind(this);

            this.socket.onclose = function() {
                this.log('Socket status: ' + this.socket.readyState + ' (closed)');
            }.bind(this);

            this.connected = true;
        } catch(exception) {
            this.log(exception);
            return false;
        }

        return true;
    };

    this.log = function(message) {
        if (this.debug !== true) {
            return;
        }

        console.log(message);
    };

    this.isConnected = function() {
        return this.connected;
    };

    this.send = function(message) {
        if (this.isConnected() !== true) {
            return;
        }

        this.socket.send(message);
    };

    this.addMessageToConversation = function(message) {
        var chatArticle = document.createElement('article'),
            chatMessage = document.createTextNode(message);

        chatArticle.appendChild(chatMessage);
        this.conversationElement.appendChild(chatArticle);
    };
}