<?php
    if (!isset($_COOKIE['userid'])) {
        setcookie('userid', uniqid());
    }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Connect | Chat | WebSocketDemo</title>
    <link rel="stylesheet" type="text/css" href="/style/style.css">
  </head>
  <body class="loading">
    <div id="connect">
      <form action="#" method="post">
        <table>
          <tr>
            <th>Username</th>
            <td><input type="text" name="username" placeholder="Optional"></td>
          </tr>
          <tr>
            <th>Join room</th>
            <td>
              <select name="room">
                <option value="">Create a new room</option>
              </select>
            </td>
          </tr>
          <tr class="new active">
            <th>Create room</th>
            <td>
              <input type="text" name="new" placeholder="New room">
            </td>
          </tr>
          <tr>
            <th>&nbsp;</th>
            <th><input type="submit" name="submit" value="Join room"></th>
          </tr>
        </table>
      </form>
      <div class="loader">
        <img src="/style/ajax-loader.gif" alt="loading...">
      </div>
    </div>
    <script src="/js/WebSocketClient.js"></script>
    <script src="/js/DomHandler.js"></script>
    <script src="/js/CookieManager.js"></script>
    <script src="/js/connect.js"></script>
  </body>
</html>