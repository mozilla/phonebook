<?php
require_once('init.php');
get_ldap_connection();
require_once('templates/header.php');
?>
<div id="results"></div>

<?php echo link_javascript("js/view-cards.js"); ?>
<?php require_once('templates/footer.php'); ?>
