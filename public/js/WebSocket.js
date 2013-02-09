function WebSocket(debug) {
    this.debug = false;
    if (typeof debug === 'boolean') {
        this.debug = debug;
    }

    this.connected = false;
    this.socket = {};

    this.connect = function(url) {
        console.log('trying to connect');
        try {
            var socket = new WebSocket(url);
                console.log(socket.readyState);
            this.socket.onopen = function() {
console.log('');
                this.log('Socket status: ' + this.socket.readyState + '(open)');

                //socket.send('This string is much much longer. Perhaps I need to add some lipsum text for the final test.');
            }.bind(this);

            this.socket.onmessage = function(message) {
                this.log('Socket received: ' + message.data);
            }.bind(this);

            this.socket.onclose = function() {
                this.log('Socket status: ' + this.socket.readyState + ' (closed)');
            }.bind(this);
        } catch(exception) {
            console.log(exception);
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
}