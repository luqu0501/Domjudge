<?php

/**
 * Scoreboard
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');
$refresh = '30;url=scoreboard.php';
$title = 'Scoreboard';
$printercss = TRUE;

require(LIBWWWDIR . '/scoreboard.php');

// This reads and sets a cookie, so must be called before headers are sent.
$filter = initScorefilter();

require(LIBWWWDIR . '/header.php');
echo "<div class='container' style='margin-bottom: 100px;'>";
// call the general putScoreBoard function from scoreboard.php
putScoreBoard($cdata, NULL, FALSE, $filter);
echo "</div>";
require(LIBWWWDIR . '/footer.php');
