<?php
require_once('init.php');
get_ldap_connection();
define('page', 'cards');
require_once('templates/header.php');
?>
<div id="results"></div>

<?php require_once('templates/footer.php'); ?>
