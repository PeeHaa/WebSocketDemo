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

        $(errorRow).addClass('error');
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

    var webSocketClient, cookieManager, url;
    cookieManager = new CookieManager();

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

                case 'error':
                    for (var i = 0, l = parsedData.errors.length; i < l; i++) {
                        switch(parsedData.errors[i].name) {
                            case 'username':
                                $(document.querySelector('input[name="username"]')).addClass('error');
                                break;

                            case 'room':
                                $(document.querySelector('select[name="room"]')).addClass('error');
                                break;
                        }
                    }
                    break;
            }
        },
        onerror: function(data) {
            alert('Error connecting to the server');
        }
    });

    var form = document.querySelector('form');

    $(form.querySelector('select')).on('change', function(e) {
        $(form.querySelector('.new')).removeClass('active');

        var roomValue = form.querySelector('select').options[form.querySelector('select').selectedIndex].value;
        if (!roomValue) {
            $(form.querySelector('.new')).addClass('active');
        }
    });

    $(form).on('submit', function(e) {
        var username = document.querySelector('input[name="username"]');
        var room = document.querySelector('select[name="room"]');

        e.preventDefault();
        e.stopPropagation();

        $(username).removeClass('error');
        $(room).removeClass('error');

        var roomValue = form.querySelector('select').options[form.querySelector('select').selectedIndex].value;

        if (!roomValue && !form.querySelector('input[name="new"]').value) {
            return;
        }

        webSocketClient.send(JSON.stringify({
            event: 'connect',
            username: username.value ? username.value : null,
            roomId: roomValue,
            roomName: form.querySelector('input[name="new"]').value
        }));
    });

    url = 'wss://localhost:1337/start.php?userid=' + cookieManager.getCookie('userid');
    url = 'ws://' + location.host + ':1337/chat-server.php?userid=' + cookieManager.getCookie('userid');

    webSocketClient.connect(url);
}());