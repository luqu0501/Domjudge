<?php
/**
 * View team details
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');

$id = getRequestID();
$current_cid = null;
if ( isset($_GET['cid']) && is_numeric($_GET['cid']) ) {
	$cid = $_GET['cid'];
	$cdata = $cdatas[$cid];
	$current_cid = $cid;
}
$title = ucfirst((empty($_GET['cmd']) ? '' : specialchars($_GET['cmd']) . ' ') .
                 'team' . ($id ? ' t'.specialchars(@$id) : ''));

if ( isset($_GET['cmd'] ) ) {
	$cmd = $_GET['cmd'];
} else {
	$extra = '';
	if ( $current_cid !== null ) {
		$extra = '&cid=' . urlencode($current_cid);
	}
	$refresh = '15;url='.$pagename.'?id='.urlencode($id).$extra.
		(isset($_GET['restrict'])?'&restrict='.urlencode($_GET['restrict']):'');
}

$jqtokeninput = true;

require(LIBWWWDIR . '/header.php');
echo "<div class='container' style='margin-bottom: 100px;'>";

require(LIBWWWDIR . '/scoreboard.php');

if ( !empty($cmd) ):

	requireAdmin();
	echo "<div class='row'>";
	echo "<div class='col s8 offset-s2'>";
	
    echo "<h2 class=\"title-scoreboard flow-text\">$title</h2>\n\n";
    
	

	// echo "<h2>$title</h2>\n\n";

	echo addForm('edit.php');

	echo "<table class='white z-depth-1'>\n";

	if ( $cmd == 'edit' ) {
		$row = $DB->q('MAYBETUPLE SELECT * FROM team WHERE teamid = %i', $id);
		if ( !$row ) error("Missing or invalid team id");

		echo "<tr><td class='tb-add'>ID:</td><td class='pd-add'>" .
			addHidden('keydata[0][teamid]', $row['teamid']) .
			"t" . specialchars($row['teamid']) . "</td></tr>\n";
	}

?>
<tr><td class='tb-add'><label for="data_0__name_">Team name:</label></td>
<td class="pd-add"><?php echo addInput('data[0][name]', @$row['name'], 35, 255, 'required')?></td></tr>
<tr><td class='tb-add'><label for="data_0__categoryid_">Category:</label></td>
<td class="pd-add"><?php
$cmap = $DB->q("KEYVALUETABLE SELECT categoryid,name FROM team_category ORDER BY categoryid");
echo '<div style="width:50%">' .addSelect('data[0][categoryid]', $cmap, @$row['categoryid'], true) .'</div>';
?>
</td></tr>
<tr><td class='tb-add'><label for="data_0__members_">Members:</label></td>
<td class="pd-add"><?php echo addTextArea('data[0][members]', @$row['members'], 40, 3)?></td></tr>
<tr><td class='tb-add'><label for="data_0__affilid_">Affiliation:</label></td>
<td class="pd-add"><?php
$amap = $DB->q("KEYVALUETABLE SELECT affilid,name FROM team_affiliation ORDER BY name");
$amap[''] = 'none';
echo '<div style="width:50%">' .addSelect('data[0][affilid]', $amap, @$row['affilid'], true).'</div>';
?>
</td></tr>
<tr><td class='tb-add'><label for="data_0__penalty_">Penalty time:</label></td>
<td class="pd-add"><?php echo addInput('data[0][penalty]', (isset($row['penalty'])?$row['penalty']:0), 10, 15, 'required')?></td></tr>
<tr><td class='tb-add'><label for="data_0__room_">Location:</label></td>
<td class="pd-add"><?php echo addInput('data[0][room]', @$row['room'], 10, 15)?></td></tr>
<tr><td class='tb-add'><label for="data_0__comments_">Comments:</label></td>
<td class="pd-add"><?php echo addTextArea('data[0][comments]', @$row['comments'])?></td></tr>

<?php
$num_contests = $DB->q("VALUE SELECT COUNT(*) FROM contest c WHERE c.public = 0");
if ( $num_contests > 0 ) {
	$prepopulate = $DB->q("TABLE SELECT c.cid AS id, c.name, c.shortname,
	                       CONCAT(c.name, ' (', c.shortname, ' - c', c.cid, ')') AS search
	                       FROM contest c INNER JOIN contestteam USING (cid)
	                       WHERE teamid = %i", $id);
?>

<!-- contest selection -->
<tr>
	<td class='tb-add'>Private contests:</td>
	<td class="pd-add">
		<?php echo addInput('data[0][mapping][0][items]', '', 50); ?>
		<script type="text/javascript">
			$(function() {
				$('#data_0__mapping__0__items_').tokenInput('ajax_contests.php?public=0', {
					propertyToSearch: 'search',
					hintText: 'Type to search for contest ID, name, or short name',
					noResultsText: 'No private contests found',
					preventDuplicates: true,
					excludeCurrent: true,
					prePopulate: <?php echo json_encode($prepopulate); ?>
				});
			});
		</script>
	</td>
</tr>
<?php
}
?>

<tr><td class='tb-add'>Enabled:</td>
<td class="pd-add"><?php echo addRadioButton('data[0][enabled]', (!isset($row['']) || $row['enabled']), 1)?> <label for="data_0__enabled_1">yes</label>
<?php echo addRadioButton('data[0][enabled]', (isset($row['enabled']) && !$row['enabled']), 0)?> <label for="data_0__enabled_0">no</label></td></tr>
</table>

<?php
echo addHidden('data[0][mapping][0][fk][0]', 'teamid') .
     addHidden('data[0][mapping][0][fk][1]', 'cid') .
     addHidden('data[0][mapping][0][table]', 'contestteam');
echo '<div style="margin-top: 30px;">';
// echo '<div class="col s3 offset-s3">';     
echo addHidden('cmd', $cmd) .
     addHidden('table','team') .
     addHidden('referrer', @$_GET['referrer'] . ( $cmd == 'edit'?(strstr(@$_GET['referrer'],'?') === FALSE?'?edited=1':'&edited=1'):'')) .
     addSubmit('Save') .
     addSubmit('Cancel', 'cancel', null, true, 'formnovalidate');
     // echo "</div>";
     echo "</div>";
     addEndForm();
echo "</div>";
echo "</div>";
echo "</div>";
require(LIBWWWDIR . '/footer.php');
exit;

endif;

/* optional restriction of submissions list to specific problem, language, etc. */
$restrictions = array();
if ( isset($_GET['restrict']) ) {
	list($key, $value) = explode(":",$_GET['restrict'],2);
	$restrictions[$key] = $value;
}

$row = $DB->q('MAYBETUPLE SELECT t.*, a.country, c.name AS catname,
                                 a.shortname AS affshortname, a.name AS affname
               FROM team t
               LEFT JOIN team_category c USING (categoryid)
               LEFT JOIN team_affiliation a ON (t.affilid = a.affilid)
               WHERE teamid = %i', $id);

if ( !$row ) error("Invalid team identifier");

if ( isset($_GET['edited']) ) {

	echo addForm('refresh_cache.php') .
	     msgbox (
		     "Warning: Refresh scoreboard cache",
		     "If the membership of a team in a contest was changed, it may be necessary to recalculate any cached scoreboards.<br /><br />" .
		     addSubmit('recalculate caches now', 'refresh')
	     ) .
	     addEndForm();

}

$users = $DB->q('TABLE SELECT userid,username FROM user WHERE teamid = %i', $id);

$affillogo   = "../images/affiliations/" . urlencode($row['affilid']) . ".png";
$countryflag = "../images/countries/"    . urlencode($row['country']) . ".png";
$teamimage   = "../images/teams/"        . urlencode($row['teamid'])  . ".jpg";

echo "<h1 class=\"title-scoreboard flow-text\">Team ".specialchars($row['name'])."</h1>\n\n";

if ( $row['enabled'] != 1 ) {
	echo "<p><em>Team is disabled</em></p>\n\n";
}

?>

<div class="col1"><table class="white z-depth-1">
<tr><td>ID:        </td><td>t<?php echo specialchars($row['teamid'])?></td></tr>
<tr><td>Name:      </td><td><?php echo specialchars($row['name'])?></td></tr>
<tr><td>Host:</td><td><?php echo
	(@$row['hostname'] ? printhost($row['hostname'], TRUE):'') ?></td></tr>
<?php if (!empty($row['penalty'])): ?>
<tr><td>Penalty time:</td><td><?php echo specialchars($row['penalty'])?></td></tr>
<?php endif; ?>
<?php if (!empty($row['room'])): ?>
<tr><td>Location:</td><td><?php echo specialchars($row['room'])?></td></tr>
<?php endif; ?>
<tr><td>User:</td><td><?php
if ( count($users) ) {
	foreach($users as $user) {
		echo "<a href=\"user.php?id=" . urlencode($user['userid']) . "\">" . specialchars($user['username']) . "</a> ";
	}
} else {
	echo "<a href=\"user.php?cmd=add&amp;forteam=" . urlencode($row['teamid']) . "\"><small>(add)</small></a>";
}
?></td></tr>
<?php
$private_contests = $DB->q("TABLE SELECT contest.* FROM contest
                            INNER JOIN contestteam USING (cid)
                            WHERE public = 0 AND teamid = %i", $id);
if ( !empty($private_contests)) {
	foreach ( $private_contests as $i => $contest ) {
		echo "<tr><td>\n";
		if ( $i == 0 ) {
			echo 'Private contests:';
		}
		echo "</td><td>\n";
		if ( IS_JURY ) {
			echo '<a href="contest.php?id=' . $contest['cid'] . '">';
		}
		echo 'c' . $contest['cid'] . ' - ' . $contest['shortname'];
		if ( IS_JURY ) {
			echo '</a>';
		}
		echo "</td></tr>\n";
	}
}
?>
</table></div>

<div class="col2"><table style="margin-left: 30px;" class="white z-depth-1">
<?php

echo '<tr><td>Category:</td><td><a href="team_category.php?id=' .
	urlencode($row['categoryid']) . '">' .
	specialchars($row['catname']) . "</a></td></tr>\n";

if ( !empty($row['affilid']) ) {
	echo '<tr><td>Affiliation:</td><td>';
	if ( is_readable($affillogo) ) {
		echo '<img src="' . $affillogo . '" alt="' .
			specialchars($row['affshortname']) . '" /> ';
	}
	echo '<a href="team_affiliation.php?id=' . urlencode($row['affilid']) . '">' .
		specialchars($row['affname']) . "</a></td></tr>\n";
}
if ( !empty($row['country']) ) {
	echo '<tr><td>Country:</td><td>';
	if ( is_readable($countryflag) ) {
		echo '<img src="' . $countryflag . '" alt="' .
			specialchars($row['country']) . '" /> ';
	}
	echo specialchars($row['country']) . "</td></tr>\n";
}
if ( !empty($row['members']) ) {
	echo '<tr><td>Members:   </td><td>' .
		nl2br(specialchars($row['members'])) . "</td></tr>\n";
}
if ( !empty($row['comments']) ) {
	echo '<tr><td>Comments:</td><td>' .
		nl2br(specialchars($row['comments'])) . "</td></tr>\n";
}
echo "</table></div>\n";

if ( IS_ADMIN ) {
	echo "<p style='padding-top: 16px;' class=\"nomorecol\">" .
		editLink('team', $id). "\n" .
		delLink('team','teamid',$id) .
		"</p>\n\n";
}

echo rejudgeForm('team', $id) . "\n\n";

if ( $cid ) {
	echo "<h3 class=\"title-scoreboard flow-text\">Score</h3>\n\n";

	putTeamRow($cdata, array($id));
}

echo '<h3 class="title-scoreboard flow-text">Submissions';
if ( isset($key) ) {
	$keystr = "";
	switch ( $key ) {
	case 'probid':    $keystr = "problem";   break;
	case 'langid':    $keystr = "language";  break;
	case 'judgehost': $keystr = "judgehost"; break;
	default:          error("Restriction on $key not allowed.");
	}
	echo ' for ' . specialchars($keystr) . ': ' . specialchars($value);
}
echo "</h3>\n\n";

$restrictions['teamid'] = $id;
putSubmissions($cdatas, $restrictions);
echo "</div>";
echo "</div>";
echo "</div>";
require(LIBWWWDIR . '/footer.php');