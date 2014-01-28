<?php

error_reporting (E_ALL);

define ('TEST_MODE', true);

require_once ('config.php');

require_once ('NewsIssue.php');

if (isset($_GET['issue'])) {
	$issue = $_GET['issue'];
} else {
	$issue = 'student-news/201312/0001.html';
}

$newsIssue = new NewsIssue();
$newsIssue->loadFromURL(BASE_SOURCE_URL . $issue);

echo $newsIssue->getFormattedHTML(isset($_GET['asifto']));

?>