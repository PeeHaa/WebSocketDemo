WebSocketDemo
=

Simple WebSocket demo in PHP.

Getting started
-

A very simple chat application is included. To use the demo please follow the steps below:

1. Download the sauce
2. Point the WebSocket client to the correct address in the file `/public/js/main.js` on line 4
3. Let the WebSocket server listen to the correct address and port in the file `/server/start.php` on line 77
4. Setup your webserver to server `/public/index.php`
5. Start the WebSocket server at `/server/start.php`
6. Navigate to the demo in your browser, e.g.: http://localhost:1337/start.php

Implementation
-

RFC 6455

License
-

[MIT][mit]

[mit]:http://opensource.org/licenses/MIT

Status of the project
-

This is just the result of some quick gluing code together.