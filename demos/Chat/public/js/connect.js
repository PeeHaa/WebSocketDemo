function Authentication() {
    this.authenticate = function(data) {
        if (data.result == 'success') {
            window.location = '/room.php?id=' + data.roomId;
        } else {
            this.invalidate();
        }
    };

    this.invalidate = function(errors) {
        var loginForm = document.querySelector('form tbody');

        var errorRows = loginForm.querySelectorAll('.error');
        for (var i = 0, l = errorRows.length; i < l; i++) {
            errorRows[i].parentNode.removeChild(errorRows[i]);
        }

        var errorRow  = document.createElement('tr'),
            errorCell = document.createElement('td'),
            errorText = document.createTextNode('Invalid room chosen.');

        errorRow.className = 'error';
        errorCell.setAttribute('colspan', '2');

        errorCell.appendChild(errorText);
        errorRow.appendChild(errorCell);
        loginForm.appendChild(errorRow);
    };
}

function Rooms() {
    this.load = function(rooms) {
        var roomsSelect = document.querySelector('select[name="room"]');

        for (var roomId in rooms) {
            if (!rooms.hasOwnProperty(roomId)) {
                continue;
            }

            var option = document.createElement('option'),
                optionText = document.createTextNode(rooms[roomId]);

            option.value = roomId;
            option.appendChild(optionText);

            roomsSelect.appendChild(option);
        }

        document.querySelector('.loader').parentNode.removeChild(document.querySelector('.loader'));
        document.querySelector('body').className = '';
    };
}

(function() {
    var authentication = new Authentication();
    var rooms = new Rooms();

    var webSocketClient = new WebSocketClient({
        onopen: function(data) {
            webSocketClient.send(JSON.stringify({event: 'listRooms'}));
        },
        onmessage: function(data) {
            var parsedData = JSON.parse(data);

            switch(parsedData.event) {
                case 'connect':
                    authentication.authenticate(parsedData);
                    break;

                case 'listRooms':
                    rooms.load(parsedData.rooms);
                    break;
            }
        },
        onerror: function(data) {
            alert('Error connecting to the server');
        }
    });

    var form = document.querySelector('form');

    form.addEventListener('submit', function(e) {
        var username = document.querySelector('input[name="username"]');
        var room = document.querySelector('select[name="room"]');

        webSocketClient.send(JSON.stringify({
            event: 'connect',
            username: username.value ? username.value : null,
            roomId: room.value
        }));

        e.preventDefault();
        e.stopPropagation();
    });

    webSocketClient.connect('ws://www.chat.localhost:1337/chat-server.php');
}());