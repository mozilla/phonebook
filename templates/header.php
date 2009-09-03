<!DOCTYPE HTML>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Mozilla Phonebook</title>
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />
    <script type="text/javascript" src="js/prototype.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
  </head>

<body>

<div id="header">
  <form action="search.php" method="get" id="phonebook-search">
    <h1><a href="./">Phonebook</a></h1>
    <div id="search-region">
      <input type="hidden" name="format" value="html" />
      <input type="text" name="query" id="text" size="18" /><button type="submit" id="search">Search</button>
    </div>
    <div id="throbber"></div>
    <ul id="menu">
      <li><a class="card persist" href="./">Cards</a></li>
      <li><a class="wall persist" href="./faces.php">Faces</a></li>
      <li><a class="tree persist" href="./tree.php">Org Chart</a></li>
      <li class="edit"><a class="edit" href="./edit.php" id="edit-entry">Edit My Entry</a></li>
    </ul>
  </form>
</div>

