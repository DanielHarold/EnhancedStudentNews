<?php

header ('Content-Type: application/rdf+xml');

error_reporting (E_ALL);

require_once ('NewsIssue.php');

$newsIssue = NewsIssue::getLatestIssue(isset($_GET['calvinnews']));
$rss = $newsIssue->getRSS();
echo $rss;

?>