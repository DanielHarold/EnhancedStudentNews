<?php

if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR']) {
	die ('Forbidden');
} else {
	define ('NEWS_TRIGGER', true);
	
	$calvinNews = false;
	$testNews = false;
	
	include ('trigger.php');
}
