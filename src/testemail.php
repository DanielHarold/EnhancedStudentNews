<?php

header ('Content-Type: text/plain');

error_reporting (E_ALL);

require_once ('config.php');

require_once ('NewsIssue.php');

echo '------' . "\n";
echo 'Triggered at ' . date(DATE_RSS) . "\n";

include_once ('common.php');

$target = DEBUG_EMAIL;

$newsIssue = NewsIssue::getLatestIssue(isset($_GET['calvinNews']));
if (!$newsIssue) {
	die ('Unable to find latest news issue.');
}

$formattedNews = $newsIssue->getFormattedHTML(true);

echo 'Sending ' . $newsIssue->issueTitle . '...' . "\n";

$success = mail($target, $newsIssue->issueTitle, $formattedNews, "From: Enhanced Student News <" . FROM_EMAIL . ">\r\nContent-Type: text/html\r\nPrecedence: bulk");
echo $target . ': ' . $success . "\n";

echo 'Completed.' . "\n\n";

?>