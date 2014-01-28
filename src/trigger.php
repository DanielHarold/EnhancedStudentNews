<?php

error_reporting (E_ALL);

require_once ('config.php');

require_once ('NewsIssue.php');
require_once ('functions.php');

echo '------' . "\n";
echo 'Triggered at ' . date(DATE_RSS) . "\n";

// TODO put test mode back into being a constant everywhere

if (!defined('NEWS_TRIGGER'))
	die ('Unauthorized news trigger');

//if (!$testNews)
//	die ('Test mode expected');

include_once ('common.php');

$targets = array( 
	// TARGET EMAIL ADDRESSES GO HERE
//	'john@example.com',
//	'jane@example.com',

	DEBUG_EMAIL,
	DEBUG_EMAIL,	// for testing redundancy
	// If the script times out, DEBUG_EMAIL will be affected, since this address is last.
);

// TODO automatically add @students.calvin.edu for Student News when no domain is specified
//      and add @calvin.edu for Calvin News

$newsIssue = NewsIssue::getLatestIssue($calvinNews);
if (!$newsIssue) {
	die ('Unable to find latest news issue.');
}

$formattedNews = $newsIssue->getFormattedHTML(true);

// TODO If any errors occurred, abort.

echo 'Sending ' . $newsIssue->issueTitle . '...' . "\n";

foreach ($targets as $target) {
	$success = mail($target, $newsIssue->issueTitle, $formattedNews, "From: Enhanced Student News <" . FROM_EMAIL . ">\r\nContent-Type: text/html\r\nPrecedence: bulk");
	echo $target . ': ' . $success . "\n";
}

echo '--Completed-- at ' . date(DATE_RSS) . "\n";

echo 'Writing RSS files: ';
writeFile('rss/esn-latest-issue.rss', $newsIssue->getRSS());
echo '; ';
writeFile('rss/esn-' . $newsIssue->issueNumber . '.rss', $newsIssue->getRSS());
echo "\n\n";

?>