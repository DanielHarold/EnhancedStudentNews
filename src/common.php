<?php

if ((isset($testNews) && $testNews && !isset($_GET['notest'])) || isset($_GET['test'])) {
	define ('TEST_MODE', true);
}

?>