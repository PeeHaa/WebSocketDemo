function Room(connection, page) {
    this.connection = connection;
    this.page       = page;

    this.getId = function() {
        return document.getElementsByTagName('body')[0].getAttribute('data-room-id');
    };

    this.load = function(users, messages) {
        for (var i = 0, l = users.length; i < l; i++) {
            this.addUser(users[i].id, users[i].username, users[i].avatarHash, users[i].status);
        }

        for (var i = 0, l = messages.length; i < l; i++) {
            this.addMessage(messages[i]);
        }
    };

    this.addUser = function(id, username, avatarHash, status) {
        var userList        = document.querySelector('#user-list ul'),
            userElement     = document.createElement('li'),
            usernameElement = document.createElement('p'),
            statusElement   = document.createElement('img');

        userElement.setAttribute('data-id', id);
        userElement.setAttribute('data-status', 'idle');

        usernameElement.appendChild(document.createTextNode(username));

        statusElement.setAttribute('src', '/style/bullet_black.png');
        statusElement.setAttribute('alt', 'Idle');
        statusElement.setAttribute('title', 'Idle');
        statusElement.className = 'status';

        userElement.appendChild(this.createAvatar(username, avatarHash));
        userElement.appendChild(usernameElement);
        userElement.appendChild(statusElement);

        userList.appendChild(userElement);

        this.updateStatus(id, status);
    };

    this.removeUser = function(id) {
        var userElement = document.querySelector('#user-list li[data-id="' + id + '"]');

        userElement.parentNode.removeChild(userElement);
    };

    this.addMessage = function(message) {
        switch (message.type) {
            case 'post':
                this.addPost(message);
                break;

            case 'notification':
                this.addNotification(message);
                break;
        }

        updateConversationView();
    };

    this.createAvatar = function(username, avatarHash, size) {
        if (typeof size === 'undefined') {
            size = 15;
        }

        var image = document.createElement('img');

        image.className = 'avatar';
        image.setAttribute('src', '//www.gravatar.com/avatar/' + avatarHash + '?s=' + size + '&d=identicon');
        image.setAttribute('alt', username);
        image.setAttribute('title', username);

        return image;
    };

    this.updateStatus = function(userId, status) {
        var userContainer = document.querySelector('#user-list li[data-id="' + userId + '"]'),
            statusElement = userContainer.querySelector('.status');

        if (userContainer.getAttribute('data-status') == status) {
            return;
        }

        userContainer.setAttribute('data-status', status);

        switch (status) {
            case 'online':
                statusElement.setAttribute('src', '/style/bullet_green.png');
                statusElement.setAttribute('alt', 'Online');
                statusElement.setAttribute('title', 'Online');
                break;

            case 'typing':
                statusElement.setAttribute('src', '/style/bullet_orange.png');
                statusElement.setAttribute('alt', 'Typing');
                statusElement.setAttribute('title', 'Typing');
                break;

            case 'idle':
                statusElement.setAttribute('src', '/style/bullet_black.png');
                statusElement.setAttribute('alt', 'Idle');
                statusElement.setAttribute('title', 'Idle');
                break;
        }
    };

    this.addPost = function(message) {
        var conversation = document.querySelector('#conversation .scrollable'),
            article      = document.createElement('article'),
            postElement  = document.createElement('div');

        postElement.className = 'message';
        postElement.appendChild(document.createTextNode(message.text));

        article.appendChild(this.createPostUserInfo(message.user.username, message.user.avatarHash));
        article.appendChild(postElement);

        conversation.appendChild(article);

        this.updateStatus(message.user.id, 'online');
    };

    this.createPostUserInfo = function(username, avatarHash) {
        var container       = document.createElement('div'),
            avatar          = this.createAvatar(username, avatarHash),
            usernameElement = document.createElement('p');

        usernameElement.appendChild(document.createTextNode(username));

        container.className = 'user-info';

        container.appendChild(avatar);
        container.appendChild(usernameElement);

        return container;
    };

    this.addNotification = function(message) {
        var conversation = document.querySelector('#conversation .scrollable'),
            article      = document.createElement('article'),
            postElement  = document.createElement('div');

        postElement.className = 'notification';
        postElement.appendChild(document.createTextNode(message));

        article.appendChild(postElement);

        conversation.appendChild(article);
    };
}

function updateConversationView(e) {
    var conversation = document.getElementById('conversation'),
        scrollable   = document.querySelector('#conversation .scrollable');

    var height = $().getViewport().height - 110;
    conversation.style.height = height + 'px';

    conversation.scrollTop = scrollable.offsetHeight;

    updateScrollbar();
};

function updateScrollbar() {
    var conversation = document.getElementById('conversation'),
        scrollable   = document.querySelector('#conversation .scrollable'),
        scrollbar    = document.querySelector('.scrollbar'),
        handle       = document.querySelector('.scrollbar .handle');

    var maxHeight = (conversation.offsetHeight - 10);

    if (conversation.offsetHeight >= scrollable.offsetHeight) {
        handle.style.height = maxHeight + 'px';

        return;
    }

    handle.style.height = maxHeight * (conversation.offsetHeight / scrollable.offsetHeight) + 'px';

    handle.style.top = ((scrollbar.clientHeight - 4) - handle.clientHeight) + 'px';
}

function scrollContent(newRelativePosition) {
    var scrollbar = document.querySelector('.scrollbar'),
        handle    = document.querySelector('.scrollbar .handle');

    // failing to get the correct top position of the handle
    // you win for now javascript well played
    // I'll just hack whatever I need in and wait for people seeing this pointing out my stupidity

    // update: now that I think about it. it is probably either padding or border which is screwing with me
    // i don't want to test it though, because I'm afraid that is not the case. at least now I have the feeling
    // I won from the JS. Take that JS! Muahhahahaha
    var topPosition = 0;
    if (handle.style.top !== '') {
        topPosition = parseInt(handle.style.top, 10);
    }

    handle.style.top = (topPosition + newRelativePosition) + 'px';
}

function scrollOutOfBounds(previousY, newY) {
    var scrollbar = document.querySelector('.scrollbar'),
        handle    = document.querySelector('.scrollbar .handle');

    var topPosition = 0;
    if (handle.style.top !== '') {
        topPosition = parseInt(handle.style.top, 10);
    }

    if (previousY > newY) {
        if (topPosition < 3) {
            return true;
        }
    }

    if (previousY < newY) {
        if ((handle.offsetHeight + topPosition) > (scrollbar.offsetHeight - 4)) {
            return true;
        }
    }

    return false;
}

(function() {
    var page = new Page();
    var room = new Room(webSocketClient, page);

    updateConversationView();

    var webSocketClient = new WebSocketClient({
        onopen: function(data) {
            page.removeLoader();

            webSocketClient.send(JSON.stringify({
                event: 'loadRoom',
                roomId: room.getId()
            }));

            page.setTitle(page.getTitle().replace('#', room.getId()));
        },
        onmessage: function(data) {
            var data = JSON.parse(data);

            switch(data.event) {
                case 'loadRoom':
                    room.load(data.users, data.messages);
                    break;

                case 'userConnected':
                    room.addUser(data.id, data.username, data.avatarHash, 'online');
                    room.addNotification(data.message);
                    break;

                case 'userDisconnected':
                    room.removeUser(data.id);
                    room.addNotification(data.message);
                    break;

                // typing /idle
                case 'userChangedStatus':
                    room.updateStatus(data.id, data.status);
                    break;

                case 'userStoppedTyping':
                    room.updateStatus(data.id, 'online');
                    break;

                case 'sendMessage':
                    room.addMessage(data.message);
                    break;
            }
        },
        onerror: function(data) {
            alert('Error connecting to the server');
        }
    });

    var shiftPressed = false;
    $(document.querySelector('footer form textarea')).on('keydown', function(e) {
        if (e.keyCode === 16) {
            shiftPressed = true;
        }
    });

    $(document.querySelector('footer form textarea')).on('keyup', function(e) {
        if (e.keyCode === 16) {
            shiftPressed = false;
        }

        if (e.target.value) {
            webSocketClient.send(JSON.stringify({
                event: 'userStartedTyping'
            }));
        } else {
            webSocketClient.send(JSON.stringify({
                event: 'userStoppedTyping'
            }));
        }
    });

    $(document.querySelector('footer form textarea')).on('paste', function(e) {
        if (e.target.value) {
            webSocketClient.send(JSON.stringify({
                event: 'userStartedTyping'
            }));
        } else {
            webSocketClient.send(JSON.stringify({
                event: 'userStoppedTyping'
            }));
        }
    });

    $(document.querySelector('footer form textarea')).on('input', function(e) {
        if (e.target.value) {
            webSocketClient.send(JSON.stringify({
                event: 'userStartedTyping'
            }));
        } else {
            webSocketClient.send(JSON.stringify({
                event: 'userStoppedTyping'
            }));
        }
    });

    $(document.querySelector('footer form textarea')).on('keypress', function(e) {
        if (e.keyCode === 13 && !shiftPressed) {
            e.preventDefault();
            e.stopPropagation();

            document.querySelector('footer form input[type="submit"]').click();

            return;
        }
    });

    $(document.querySelector('footer form')).on('submit', function(e) {
        var value = document.querySelector('footer form textarea').value;

        e.stopPropagation();
        e.preventDefault();

        if (value == '') {
            return;
        }

        webSocketClient.send(JSON.stringify({
            event: 'sendMessage',
            roomId: room.getId(),
            message: value
        }));

        document.querySelector('footer form textarea').value = '';
    });

    $(window).on('resize', updateConversationView);

    var scrolling = false;
    var y = 0;

    $(document.querySelector('.scrollbar .handle')).on('mousedown', function(e) {
        scrolling = true;
        y = e.pageY;

        e.preventDefault();
    });

    $(document).on('mousemove', function(e) {
        if (scrolling === false || scrollOutOfBounds(y, e.pageY)) {
            return;
        }

        scrollContent(e.pageY - y);

        y = e.pageY;
    });

    $(document).on('mouseup', function(e) {
        if (scrolling === false || scrollOutOfBounds(y, e.pageY)) {
            return;
        }

        scrolling = false;

        scrollContent(e.pageY - y);
    });

    webSocketClient.connect('ws://www.chat.localhost:1337/chat-server.php');
}());