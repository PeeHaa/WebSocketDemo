<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

ini_set('date.timezone', 'Europe/Amsterdam');
?>

<html>
  <head>
    <title>WebSucket POC</title>
  </head>
  <body>
    <script>
    (function() {
      try{
          var socket = new WebSocket('ws://websucket.pieterhordijk.com:1337/Websocket.php');
          console.log('Socket status: '+socket.readyState);
          socket.onopen = function(){
             console.log('Socket status: '+socket.readyState+' (open)');
             socket.send('This is sent to the server...');
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
    }());
    </script>
  </body>
</html>