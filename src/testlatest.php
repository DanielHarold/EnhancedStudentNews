<?php

error_reporting (E_ALL);

define ('TEST_MODE', true);

require_once ('NewsIssue.php');

$newsIssue = NewsIssue::getLatestIssue(isset($_GET['calvinnews']));
echo $newsIssue->getFormattedHTML();
