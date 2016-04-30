<?php

error_reporting (E_ALL);

require_once ('config.php');

require_once ('NewsIssue.php');

if (isset($_REQUEST['issue'])) {
	$newsIssue = new NewsIssue();
	$newsIssue->loadFromURL(BASE_SOURCE_URL . $_REQUEST['issue']);
} else {
	$newsIssue = NewsIssue::getLatestIssue(isset($_REQUEST['calvinnews']));
}

echo $newsIssue->getFormattedHTML(isset($_REQUEST['asifto']));
