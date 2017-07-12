<!DOCTYPE HTML>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <title>Mozilla Phonebook</title>
    <?php echo link_stylesheet("css/font-awesome.min.css"); ?>
    <?php echo link_stylesheet("css/style.css"); ?>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <?php echo link_javascript("js/jquery-min.js"); ?>
    <link title="Mozilla Phonebook" rel="search" type="application/opensearchdescription+xml" href="opensearch.xml">
  </head>

<body data-page="<?php echo page ?>">

<div id="header">
  <form action="search.php" method="get" id="phonebook-search">
    <h1><a href=".">Phonebook</a></h1>
    <div id="search-region">
      <input type="hidden" name="format" value="html">
      <div id="text-wrapper">
        <input type="text" name="query" class="with-clear-button" id="text" size="18" autofocus="true">
        <div id="clear-button" title="Clear"></div>
      </div><button type="submit" id="search">Search</button>
    </div>
    <div id="throbber"></div>
    <ul id="menu">
      <li><a class="card persist" href=".">Cards</a></li>
      <li><a class="wall persist" href="faces.php">Faces</a></li>
      <li><a class="tree persist" href="tree.php">Org Chart</a></li>
      <li class="edit"><a class="edit" href="edit.php" id="edit-entry">Edit My Entry</a></li>
    </ul>
  </form>
</div>

<div class="no-results-template">
  <img src="img/ohnoes.jpg">
  <h2>OH NOES! No ones were foundz.</h2>
</div>

<div class="error-result-template">
  <img src="img/searcherror.png">
  <h2>ERROR! <a class="reload-page">Reload the page</a> to resubmit your search.</h2>
</div>
