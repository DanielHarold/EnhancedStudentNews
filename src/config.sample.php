<?php

// Copy this file to "config.php" and fill in values for these settings.

// Where to get the news from
define ('BASE_SOURCE_URL', 'http://www.example.com/archive/');

// Relative paths -- used by "previous issue" and "next issue" links
define ('CALVIN_NEWS_BASE_VIEW_PATH', 'view.php?issue=calvin-news/');
define ('STUDENT_NEWS_BASE_VIEW_PATH', 'view.php?issue=student-news/');

// Email addresses used on the web site for those who wish to subscribe
// to Enhanced Calvin News or Enhanced Student News
define ('CALVIN_NEWS_SUBSCRIBE_EMAIL', '<span style="color: #333355">ecn</span>[a<!-- not putting the actual symbol here to discourage automated spammers -->t]<!-- somewhere near --><span style="color: #333355">example.com</span>');
define ('STUDENT_NEWS_SUBSCRIBE_EMAIL', '<span style="color: #333355">esn</span>[a<!-- not putting the actual symbol here to discourage automated spammers -->t]<!-- somewhere near --><span style="color: #333355">example.com</span>');

// Name of a person for people to contact with questions
define ('CONTACT_NAME', 'John Doe');

// Email address to send issues from
define ('FROM_EMAIL', 'enhanced-student-news');

// Email address to send debugging messages to
define ('DEBUG_EMAIL', 'test@example.com');

// Recipients for Enhanced Calvin News emails
$calvin_news_recipients = array(
	// TARGET EMAIL ADDRESSES GO HERE
//	'john@example.com',
//	'jane@example.com',

	DEBUG_EMAIL,
	DEBUG_EMAIL,	// for testing redundancy
	// If the script times out, DEBUG_EMAIL will be affected, since this address is last.
);

// Recipients for Enhanced Student News emails
$student_news_recipients = array(
	// TARGET EMAIL ADDRESSES GO HERE
//	'john@example.com',
//	'jane@example.com',

	DEBUG_EMAIL,
	DEBUG_EMAIL,	// for testing redundancy
	// If the script times out, DEBUG_EMAIL will be affected, since this address is last.
);
