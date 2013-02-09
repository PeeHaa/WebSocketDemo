<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

ini_set('date.timezone', 'Europe/Amsterdam');
?>

<html>
  <head>
    <title>WebSucket POC</title>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
  </head>
  <body>
    <a href="#" id="random">Send random message</a>
    <a href="#" id="debug">Send debug message</a>
    <script>
    (function() {
      try{
          var socket = new WebSocket('ws://websucket.pieterhordijk.com:1337/Websocket.php');
          console.log('Socket status: '+socket.readyState);
          socket.onopen = function(){
             console.log('Socket status: '+socket.readyState+' (open)');
          }
          socket.onmessage = function(msg){
             console.log('Received shit: '+msg.data);
          }
          socket.onclose = function(){
            console.log('Socket status: '+socket.readyState+' (Closed)');
          }
      } catch(exception){
         console.log('FAIL: '+exception);
      }

      document.getElementById('random').addEventListener('click', function() {
          socket.send('Random message to server');
      });

      document.getElementById('debug').addEventListener('click', function() {
          socket.send('debug');
      });
    }());
    </script>
  </body>
</html>