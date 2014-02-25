<?php

require_once ('config.php');

require_once ('common.php');

function getLastIssuePath($calvinNews = false) {
	return getLastIssuePathAsOfMonth(@date('Ym'), $calvinNews);
}

function getLastIssuePathAsOfMonth($monthString, $calvinNews, $prevMonthsToCheck = 3) {
	$newsFolder = ($calvinNews) ? 'calvin-news' : 'student-news';
	
	// If we are passed a "0" month, subtract another (100-12) to get to December of the previous year.
	if ($monthString % 100 == 0) {
		$monthString -= (100 - 12);
	}
	
	$url = BASE_SOURCE_URL . $newsFolder . '/' . $monthString . '/';
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	ob_start(); 
	curl_exec($ch); 
	curl_close($ch); 
	$output = ob_get_contents(); 
	ob_end_clean();
	
	$result = preg_match('#<a href="(\d+).html" accesskey="j" name="first" id="first">#', $output, $matches);
	if ($result > 0) {
		return $monthString . '/' . $matches[1];
	} else {
		// Check the previous month
		if ($prevMonthsToCheck > 0) {
			// To get the previous month, subtract 1 from the current month.
			$prevMonthString = $monthString - 1;
			return getLastIssuePathAsOfMonth($prevMonthString, $calvinNews, $prevMonthsToCheck - 1);
		} else {
			return false;
		}
	}
}

function getIssueSourceURL($issuePath, $calvinNews) {
	$newsFolder = ($calvinNews) ? 'calvin-news' : 'student-news';
	return BASE_SOURCE_URL . $newsFolder . '/' . $issuePath . '.html';
}

function linkEmailAddresses($in) {
	// first remove existing email links (as in for Calvin News 201401/0001.html#5188.7 and 201401/0015.html#5202.13)
	$out = preg_replace('/<a href="mailto:.+?">(.+?)<\\/a>/', '$1', $in);
	
	// then add email links
	$out = preg_replace('/[A-Za-z0-9._%+-]+(@|&#64;)[A-Za-z0-9.-]+\\.[A-Za-z]{2,4}/', '<a href="mailto:$0">$0</a>', $out);
	
	return $out;
}

function debugEmail($subject, $body = '') {
	mail(DEBUG_EMAIL, $subject, $body, "From: ESN Debug <" . FROM_EMAIL . ">\r\n");
	//echo '<strong><br />Debug email not sent<br /></strong>';
}

function writeFile($fileName, $content) {
	// based on an example from http://php.net/manual/en/function.fwrite.php
	
	if (!$handle = fopen($fileName, 'w+')) {
		echo "Cannot create file $fileName";
		return;
	}

	// Write $somecontent to our opened file.
	if (fwrite($handle, $content) === FALSE) {
		echo "Cannot write to file $fileName";
		return;
	}

	echo "Success, wrote file $fileName";

	fclose($handle);
}

?>