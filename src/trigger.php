<?php

error_reporting (E_ALL);

require_once ('config.php');

require_once ('NewsIssue.php');
require_once ('functions.php');

echo '------' . "\n";
echo 'Triggered at ' . date(DATE_RSS) . "\n";

//die ("\nAborted: ESN prod (2014) is disabled as of 10-28-2015\nin favor of the 2013 edition.\n\n");

$issue_manual_override = FALSE;
//$issue_manual_override = 'student-news/201508/0017';

// TODO put test mode back into being a constant everywhere

if (!defined('NEWS_TRIGGER'))
	die ('Unauthorized news trigger');

//if (!$testNews)
//	die ('Test mode expected');

include_once ('common.php');

if ($calvinNews) {
	$targets = $calvin_news_recipients;
} else {
	$targets = $student_news_recipients;
}

// TODO automatically add @students.calvin.edu for Student News when no domain is specified
//      and add @calvin.edu for Calvin News

if ( ! $issue_manual_override ) {
	$newsIssue = NewsIssue::getLatestIssue($calvinNews);
	if (!$newsIssue) {
		die ('Unable to find latest news issue.');
	}
} else {
	echo "Manual override for issue " . $issue_manual_override . "\n";
	$newsIssue = new NewsIssue();
        $newsIssue->loadFromURL(BASE_SOURCE_URL . $issue_manual_override );
}

$formattedNews = $newsIssue->getFormattedHTML(true);

// TODO If any errors occurred, abort.


// Check if RSS file for this issue exists yet.
// If so, abort to avoid sending duplicate issues.
// (This code was added on 6-6-2014.
//  The first time a duplicate issue was sent was on 5-5-2014.)
if (file_exists('rss/esn-' . $newsIssue->issueNumber . '.rss')) {
	die ('RSS file for this issue already exists. Aborting.' . "\n");
}


// Write RSS files
echo 'Writing RSS files: ';
writeFile('rss/esn-latest-issue.rss', $newsIssue->getRSS());
echo '; ';
writeFile('rss/esn-' . $newsIssue->issueNumber . '.rss', $newsIssue->getRSS());

// Write HTML file for web site
echo "\n" . 'Writing HTML file: ';
writeFile('latest-issue.html', $newsIssue->getFormattedHTML(false));
echo "\n\n";



echo 'Sending ' . $newsIssue->issueTitle . '...' . "\n";

foreach ($targets as $target) {
	$success = mail($target, $newsIssue->issueTitle, $formattedNews, "From: Enhanced Student News <" . FROM_EMAIL . ">\r\nMIME-Version: 1.0\r\nContent-Type: text/html\r\nPrecedence: bulk");
	echo $target . ': ' . $success . "\n";
	@ob_flush();
	flush();
	
	// wait 3.6 seconds between messages (beginning 4-14-2014; before it was 0.5 seconds)
	usleep(3.6 * 1000000);
	// (We would actually need to wait 3.6 seconds for a rate of 1000 emails per hour.)
}

echo '--Completed-- at ' . date(DATE_RSS) . "\n";
