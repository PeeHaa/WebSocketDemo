<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Room # | Chat | WebSocketDemo</title>
    <link href='//fonts.googleapis.com/css?family=Lato' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="/style/style.css">
  </head>
  <body class="loading" data-room-id="<?php echo $_GET['id']; ?>">
    <div id="body">
      <div id="user-list">
        <ul>
        </ul>
      </div>
      <div id="conversation">
        <div class="scrollable">
          <div class="scrollbar">
            <div class="handle"></div>
          </div>
        </div>
      </div>
      <div id="fix"></div>
      <div class="loader">
        <img src="/style/ajax-loader.gif" alt="loading...">
      </div>
    </div>
    <footer>
      <form action="#" method="post">
        <div class="submit">
          <input type="submit" name="submit" value="Send">
        </div>
        <div class="message">
          <textarea name="message"></textarea>
        </div>
      </form>
    </footer>
    <script src="/js/WebSocketClient.js"></script>
    <script src="/js/Page.js"></script>
    <script src="/js/DomHandler.js"></script>
    <script src="/js/room.js"></script>
  </body>
</html>