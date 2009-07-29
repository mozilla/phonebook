<?php require_once("templates/header.php"); ?>

<div style="margin: auto; width: 40em;">
<form method="get" action="search.php">
	<b>Search phone book for:</b> <input id="search" type="text" name="search" />
	<input type="submit" value="Search" />
	<br />
</form>
</div>
<br />
<div style="margin: auto; width: 40em; text-align: center;"><a href="index.php">Home</a> | <a href="search.php?search=+">List All</a> | <a href="https://intranet.mozilla.org/OfficeLocations">Office Locations</a> | <a href="edit.php">Edit Phonebook Entry</a></div>
<br />


<?php 
	if ($entries['count'] == 0 ) {
		print "<h2>0 entries matched your search.</h2>";
	}
?>

<?php for( $i=0; $i < $entries['count']; $i++ ) {  ?>
    <div class="vcard">
        <table>
            <tr>
                <td rowspan="13" width="170" style="text-align:center"><a href="pic.php?mail=<?php echo $entries[$i]['mail'][0] ?>" class="image" title=""><img src="pic.php?mail=<?php echo $entries[$i]['mail'][0] ?>&amp;type=thumb" alt=""   /></a></td>
                <tr ><td style="width:3em;">Name</td><td style="width:19em"><span class="fn"><?php echo htmlspecialchars($entries[$i]['cn'][0]) ?></span> <span style="font-size: x-small"><a href="vcard.php?mail=<?php echo $entries[$i]['mail'][0] ?>">(vCard)</a></span></td></tr>

                <?php if ( !empty($entries[$i]['title']) ) { ?>
                <tr><td>Title</td><td><?php echo htmlspecialchars($entries[$i]['title'][0]) ?></td></tr>
                <?php } ?>
                <?php if ( !empty($entries[$i]['manager']) ) { ?>
                <tr><td>Manager</td><td><?php echo get_manager($ldapconn, $entries[$i]['manager'][0]) ?></td></tr> 
                <?php } ?>
                <?php if ( !empty($entries[$i]['employeetype']) ) { ?>
                <tr><td>Type</td><td><?php echo print_status ($entries[$i]['employeetype'][0]) ?></td></tr>
                <?php } ?>
                <?php if ( !empty($entries[$i]['mail']) ) { ?>
                <tr><td>Email</td><td>
                    <a href="mailto:<?php echo $entries[$i]['mail'][0] ?>" class='external text' title="mailto:<?php echo $entries[$i]['mail'][0] ?>" rel="nofollow"><?php echo htmlspecialchars($entries[$i]['mail'][0]) ?></a>

                    <?php 
                    if ( !empty($entries[$i]['emailalias']) ) {
                        for ($j=0;$j<$entries[$i]['emailalias']['count']; $j++) { 
                            echo emaillinks($entries[$i]['emailalias'][$j]) . '<br />';
                        }
                    }
                    ?>

                </td></tr>
                <?php } ?>
                <?php if ( !empty($entries[$i]['physicaldeliveryofficename']) ) { ?>
                <tr><td>City/Country</td><td><?php echo htmlspecialchars(implode('/',split(':::', $entries[$i]['physicaldeliveryofficename'][0]))); ?></td></tr>
                <?php } ?>
                <?php if ( !empty($entries[$i]['telephonenumber']) ) { ?>
                <tr><td>Ext.</td><td><?php echo htmlspecialchars($entries[$i]['telephonenumber'][0]) ?></td></tr>
                <?php } ?>
                <?php if ( !empty($entries[$i]['mobile']) ) { ?>
                <tr><td>Phone</td><td><div class="tel"></div>

                <?php 
                for ($j=0;$j<$entries[$i]['mobile']['count']; $j++) { 
                    echo htmlspecialchars($entries[$i]['mobile'][$j]) . '<br />';
                }
                ?>

                </td></tr>
            <?php } ?>
            <?php if ( !empty($entries[$i]['im']) ) { ?>
            <tr><td>IM</td><td><div class="tel"></div>

                <?php 
                for ($j=0;$j<$entries[$i]['im']['count']; $j++) { 
                    echo wikilinks($entries[$i]['im'][$j]) . '<br />';
                }
                ?>

            </td></tr>
            <?php } ?>

            <?php if ( !empty($entries[$i]['description']) ) { ?>
            <tr><td>I work on:</td><td><?php echo wikilinks($entries[$i]['description'][0]) ?></td></tr>
            <?php } ?>
            <?php if ( !empty($entries[$i]['status']) ) { ?>
            <tr><td>Status</td><td><?php echo htmlspecialchars($entries[$i]['status'][0]) ?></td></tr>
            <?php } ?>
            <?php if ( !empty($entries[$i]['other']) ) { ?>
            <tr><td>Other</td><td><?php echo wikilinks($entries[$i]['other'][0]) ?></td></tr>
            <?php } ?>

            <?php if ( phonebookadmin($ldapconn, $_SERVER['PHP_AUTH_USER']) == 1 ) { ?>
            <tr><td colspan='2'><a href="edit.php?edit_mail=<?php echo $entries[$i]['mail'][0]; ?>">Edit this entry</a></td></tr>
            <?php } ?>

        </table>
    </div>


<?php  } ?>

<script type="text/javascript">
   document.getElementById('search').focus(); 
</script>

<?php require_once("templates/footer.php"); ?>
