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
                <option value="">Create new room</option>
              </select>
            </td>
          </tr>
          <tr>
            <th>Create room</th>
            <th><input type="text" name="new_room"></th>
          </tr>
          <tr>
            <th>&nbsp;</th>
            <th><input type="submit" name="submit" value="Join / create room"></th>
          </tr>
        </table>
      </form>
      <div class="loader">
        <img src="/style/ajax-loader.gif" alt="loading...">
      </div>
    </div>
    <script src="/js/WebSocketClient.js"></script>
    <script src="/js/CookieManager.js"></script>
    <script src="/js/connect.js"></script>
  </body>
</html>