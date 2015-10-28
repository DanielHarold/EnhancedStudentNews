<?php

require_once ('config.php');

require_once ('NewsItem.php');
require_once ('functions.php');

class NewsIssue {
	public $publicationTitle;
	public $issueTitle;
	public $issueNumber;
	public $issueDateString;
	
	public $monthPathString;
	
	public $newsItems = array();
	public $newsItemsCount = 0;
	
	public $isCalvinNews = false;
	public $isStudentNews = false;
	
	public $sourceURL;
	
	private $sourceHTML;
	
	private $previousIssuePath = '';
	private $nextIssuePath = '';
	
	public static function getLatestIssue($calvinNews = false) {
		$issuePath = getLastIssuePath($calvinNews);
		
		if ($issuePath) {
			$url = getIssueSourceURL($issuePath, $calvinNews);
			$newsIssue = new NewsIssue();
			$newsIssue->loadFromURL($url);
		} else {
			$newsIssue = null;
		}
		
		return $newsIssue;
	}
	
	public function loadFromURL($url) {
		// setup CURL
		$this->sourceURL = $url;
		$srcurl = $url;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// download the page
		ob_start(); 
		curl_exec($ch); 
		curl_close($ch); 
		$sourceHTML = ob_get_contents();
		ob_end_clean();
		
		$this->loadFromHTML($sourceHTML);
	}
	
	public function loadFromHTML($sourceHTML) {
		$this->sourceHTML = $sourceHTML;
		
		// Load issue title
		$result = preg_match('#<\\!-- subject="(.*)" -->#', $this->sourceHTML, $matches);
		if ($result < 1) {
			debugEmail('Error: Unable to parse news', $this->sourceHTML);
			die ('Error: Unable to parse news');
		}
		$subject = $matches[1];
		$this->issueTitle = 'Enhanced ' . preg_replace('/V[0-9]+ /', '', $subject);
		
		// Load issue month path
		$result = preg_match('#<\\!-- isoreceived="([0-9]{6})#', $this->sourceHTML, $matches);
		$this->monthPathString = $matches[1];
		
		// Load email body
		$result = preg_match('/<\\/address>\\n<p>\\n(.+?) +([SMTWF].*)<p>End of ' . preg_quote($subject, '/') . '/s', $this->sourceHTML, $matches);
		if ($result < 1) {
			debugEmail('Error: Unable to parse news', $this->sourceHTML);
			die ('Error: Unable to parse news');
		}
		$this->loadPublicationTitle($matches[1]); // sets the publicationTitle, isCalvinNews, and isStudentNews variables
		$parts = explode("<p>----------------------------------------------------------------------\n<br />\n<p>", $matches[2]);
		
		// Load header information
		$header = substr($parts[0], 0, strpos($parts[0], 'In this'));
		preg_match('/(.+?) +Volume.+Number ([0-9]+)/', $header, $matches);
		$this->issueNumber = intval($matches[2]);
		$this->issueDateString = preg_replace('/([0-9]) ([0-9])/', '$1, $2', $matches[1]); // add a comma between the day and the year
		
		// Load individual news items
		$items = explode("<p>------------------------------\n<br />\n<p>", $parts[1]);
		$i = 0;
		foreach ($items as $item) {
			$i++;
			$this->newsItems[$i] = new NewsItem($item, $this->issueNumber . '.' . $i, $this);
		}
		$this->newsItemsCount = $i;
	}
	
	public function getFormattedHTML($emailFormat = false) {
		$out = '';
		$verse = '';

		$out .= "<html><head><title>" . $this->issueTitle . "</title><style>body {font-family: Calibri, Arial, sans-serif; background-color: #f1f1ff;} a:link, a:visited.noMarkVisited {color:#444466;} a:visited{color:#666699;} a:hover, a:hover.noMarkVisited {color:#8e8ee4; text-decoration: none;}</style></head>\n";

		$out .= '<body style="margin-left: 0px; margin-right: 0px;">' . "\n";

		if ($emailFormat) {
			// email format
			if (defined('TEST_MODE')) {
				$out .= '<div style="margin:16px 16px 0px;color:#777777;"><small>';
				$out .= 'This is a test version of Enhanced ' . $this->publicationTitle . '. ';
				$out .= 'Please feel free to give feedback. ';
				$out .= 'If you would like to switch to a stable version which is less likely to have problems, please reply to this message or contact ' . CONTACT_NAME . '. ';
				$out .= '<br /><br /></small></div>';
			} else {
				if ($this->isCalvinNews) {
					/*  'Thank you for your interest in Enhanced ' . $this->publicationTitle . '!
						This is an unofficial format; rely on it at your own risk.
						Please give positive or negative feedback to me, ' . CONTACT_NAME . ', by replying to this message.
						Let me know if you would like to opt out as well. Thanks again!' */
					$out .= '<div style="margin:16px 16px 0px;color:#777777;"><small>';
					$out .= 'Enhanced ' . $this->publicationTitle . ' is an unofficial format; rely on this at your own risk. ';
					$out .= 'If you would like to give feedback or opt out, please reply to this message.';
					$out .= '<br /><br /></small></div>';
				} else {
					$out .= '<div style="margin:16px 16px 0px;color:#777777;"><small>';
					$out .= 'Enhanced ' . $this->publicationTitle . ' is an unofficial format; rely on this at your own risk. ';
					$out .= 'If you would like to give feedback or opt out, please reply to this message.';
					$out .= '<br /><br /></small></div>';
				}
			}
		} else {
			// web format
			// invitation to subscribe or email feedback
			if ($this->isCalvinNews) {
				$out .= '<div align="center" style="text=align:center;margin:16px 16px 16px;color:#777777;">If you would like to receive Enhanced ' . $this->publicationTitle . ' regularly by email, please email <big>' . CALVIN_NEWS_SUBSCRIBE_EMAIL . '</big>.</div>';
			} else {
				$out .= '<div align="center" style="text=align:center;margin:16px 16px 16px;color:#777777;">If you would like to receive Enhanced ' . $this->publicationTitle . ' regularly by email, please email <big>' . STUDENT_NEWS_SUBSCRIBE_EMAIL . '</big>.</div>';
			}
		}

		// special headers such as special announcements and Daylight Savings Time reminders
		$out .= $this->getSpecialHeaders();
		
		// invisible box to hold max-width of 800px
		$out .= '<div style="max-width: 800px; margin-left: auto; margin-right: auto;">' . "\n";
		
		// "top" anchor
		$out .= '<a id="top" name="top"></a>' . "\n";
		
		// navigation for web format
		$webNavLinks = '';
		if (!$emailFormat) {
			if ($this->getPreviousIssuePath()) {
				$webNavLinks .= '<span style="float:left;text-align:left;">';
				$webNavLinks .= '<a href="' . $this->getPreviousIssuePath() . '">&larr; Previous Issue</a>';
				$webNavLinks .= '</span>';
			}
			if ($this->getNextIssuePath()) {
				$webNavLinks .= '<span style="float:right;text-align:right;">';
				$webNavLinks .= '<a href="' . $this->getLatestIssuePath() . '" style="padding-right: 12px; border-right: solid 1px #777799;">Latest Issue</a>';
				$webNavLinks .= '<a href="' . $this->getNextIssuePath() . '" style="padding-left: 12px;">Next Issue &rarr;</a>';
				$webNavLinks .= '</span>';
			}
			$webNavLinks .= '<div>&nbsp;</div>';
		}
		$out .= $webNavLinks;
		
		// visible box to hold title and contents
		$out .= '<div style="border: solid 1px #777799; border-radius: 12px; margin: 8px 0px; background-color: #e8e8ff;">';
		// title area within visible box
		$out .= '<div style="padding: 8px;">' . "\n";
		
		$out .= '<h1 style="margin:0px;"><span style="color:#cc3333;text-transform:uppercase;font-size:80%;border:solid 2px #cc3333;background-color:#ffffff;padding:0px 4px;position:relative;top:-2px;">Enhanced</span> ' . str_replace(' ', '&nbsp;', $this->publicationTitle) . '</h1>';

		$out .= '<div><strong>Issue #' . $this->issueNumber . ' &middot; ' . $this->issueDateString . "</strong></div>\n";
		$out .= '</div>' . "\n"; // close title area
		
		// weather widget
		if (!$emailFormat) {
			/* $out .= '<div style="float: right;">
				<script type="text/javascript" src="http://voap.weather.com/weather/oap/49546?template=GENXH&par=3000000007&unit=0&key=twciweatherwidget"></script></div>'; */
		}
		
		// Output the table of contents if there is more than one news item.
		$newsItemCount = count($this->newsItems);
		if ($newsItemCount > 1) {
			$tocOutput = '<div style="padding: 8px; background-color: #ffffff; border-radius: 0px 0px 12px 12px">' . "\n";
			$hasAnyFood = false;
			
			// daily Bible verse for email version
			if ($emailFormat) {
				$verse = $this->getDailyBibleVerse();
				if ($verse) {
					$tocOutput .= '<div style="color: #007700; text-align: center; margin: 8px; padding: 8px; border: solid 1px #007700;">' . $verse . '</div>' . "\n";
				}
			}
			
			//$tocOutput .= '<ul style="padding-left: 24px;">' . "\n";
			//$tocOutput .= '<ul>' . "\n";
			$tocOutput .= '<ul style="padding-left: 5px; margin-top: 8px;">' . "\n";
			foreach ($this->newsItems as $newsItem) {
				$tocOutput .= "\t" . $newsItem->getTOCEntry();
				if (!$hasAnyFood && $newsItem->hasFood) {
					$hasAnyFood = true;
				}
			}
			$tocOutput .= '</ul>' . "\n";
			
			if ($hasAnyFood) {
				$tocOutput .= '<div><small style="color:#555555">News items marked with an asterisk (*) appear to mention food. ';
				$tocOutput .= 'Read the news items carefully to see whether food is actually provided at an event, as these markings are not always correct, and not all events with food are marked.</small></div>' . "\n";
			}
			
			$tocOutput .= '</div>' . "\n";
			$out .= $tocOutput;
		}
		
		// close title and contents box
		$out .= '</div>' . "\n";
		
		// the news items themselves
		foreach ($this->newsItems as $newsItem) {
			$out .= $newsItem->getFormattedHTML($this->newsItemsCount > 1);
		}
		
		// navigation for web format (again)
		$out .= $webNavLinks;
		
		$out .= '</div>' . "\n"; // close invisible bounding box
		$out .= $this->getFooter($emailFormat, $verse != '');
		$out .= '</div></body></html>';
		
		return $out;
	}
	
	public function getRSS() {
		$out = '';
		$out .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$out .= '<rss xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0"><channel>' . "\n";
		$out .= '	<title>Enhanced ' . $this->publicationTitle . '</title>' . "\n";
		$out .= '	<link />' . "\n";
		$out .= '	<description />' . "\n";
		$out .= '	<language>en-US</language>' . "\n";
		
		// Output news items
		foreach ($this->newsItems as $newsItem) {
			$out .= $newsItem->getRSS();
		}
		
		$out .= '</channel></rss>';
		
		return $out;
	}
	
	private function loadPublicationTitle($publicationTitle) {
		switch ($publicationTitle) {
			case 'Calvin News':
				$this->isCalvinNews = true;
				$this->isStudentNews = false;
			break;
			
			case 'Student News':
				$this->isStudentNews = true;
				$this->isCalvinNews = false;
			break;
			
			default:
				debugEmail('Error: Unrecognized publication: not Student News or Calvin News', $this->sourceHTML);
				die('Unrecognized publication: not Student News or Calvin News');
		}
		
		$this->publicationTitle = $publicationTitle;
	}
	
	public function getPreviousIssuePath() {
		if ($this->previousIssuePath === '') {
			$result = preg_match('#<dfn>Previous message</dfn>: <a href="([0-9]+)\\.html"#', $this->sourceHTML, $matches);
			if ($result > 0) {
				$this->previousIssuePath = $this->monthPathString . '/' . $matches[1];
			} else {
				$this->previousIssuePath = getLastIssuePathAsOfMonth($this->monthPathString - 1, $this->isCalvinNews);
			}
		}
		
		if ($this->previousIssuePath === false) {
			return false;
		}
		
		return $this->getBaseViewPath() . $this->previousIssuePath;
	}
	
	public function getNextIssuePath() {
		if ($this->nextIssuePath === '') {
			$result = preg_match('#<dfn>Next message</dfn>: <a href="([0-9]+)\\.html"#', $this->sourceHTML, $matches);
			if ($result > 0) {
				$this->nextIssuePath = $this->monthPathString . '/' . $matches[1];
			} else {
				if (@date('Ym') > $this->monthPathString) {
					
					// TODO Fix: This method assumes that the next month has a student news issue.
					// This fails, for example, in issue #2282 (May 31, 2011)
					// because there were no issues in June 2011 or even July 2011.
					
					$nextMonthPathString = $this->monthPathString + 1;
					if ($nextMonthPathString % 100 == 13) {
						// If we end up on month "13", add another (100-12) to get to January of the next year.
						$nextMonthPathString += (100 - 12);
					}
					$this->nextIssuePath = $nextMonthPathString . '/0000';
				} else {
					$this->nextIssuePath = false;
					return false;
				}
			}
		}
		
		return $this->getBaseViewPath() . $this->nextIssuePath;
	}
	
	public function getLatestIssuePath() {
		return $this->getBaseViewPath() . 'latest';
	}
	
	public function getBaseViewPath() {
		if ($this->isCalvinNews) {
			$baseViewPath = '/calvin-news/';
		} else {
			$baseViewPath = '/calvin-student-news/';
		}
		
		if (defined('TEST_MODE')) {
			$baseViewPath .= 'TEST/view/';
		} else {
			$baseViewPath .= 'view/';
		}
		
		return $baseViewPath;
	}
	
	private function getSpecialHeaders() {
		$out = '';
		$today = date('Y-m-d');
		
		// ReGathering and adjusted schedule 2014
		if (false && '2014-02-05' == $today) {
			$specialbg = ($this->isCalvinNews) ? '#ffffff' : '#ffffaa';
			// oh wait... Calvin News isn't using this new code yet anyway
			$out .= '<div style="text-align:center;border: solid 2px #000000;font-weight:bold;margin:16px 16px 0px;padding: 16px;background-color:'.$specialbg.';font-size:large;">
				Today\'s class schedule is adjusted for ReGathering Convocation in the CFAC auditorium at 9:50am. See the <a href="http://www.calvin.edu/academic/services/calendar/modifiedschedule.html">modified class schedule</a>.</div><br />';
		}
		
		// Daylight Savings Time beginning in 2014
		if ('2014-03-07' == $today) {
			$out .= '<div style="text-align:center;border: solid 2px #000000;font-weight:bold;margin:16px 16px 0px;padding: 16px;background-color:#ffffaa;font-size:large;">
				Note: Daylight Savings Time is beginning this weekend. Remember to turn clocks <em>forward</em> 1 hour on Saturday night!</div><br />';
		}
		
		// TODO Announce Friday schedule on Thursday of last week of classes in spring 2014!
		
		// Daylight Savings Time ending in 2014
		if ('2014-10-31' == $today) {
			$out .= '<div style="text-align:center;border: solid 2px #000000;font-weight:bold;margin:16px 16px 0px;padding: 16px;background-color:#ffffaa; font-size: large;">
				Note: Daylight Savings Time is ending this weekend. Remember to turn clocks <em>back</em> 1 hour on Saturday night!</div><br />';
		}
		
		// Christmas buffet in dining halls in 2013
		if (false && '2013-12-05' == $today) {
			$out .= '<div style="text-align:center;border: solid 2px #990000;font-weight:bold;margin:0px 16px 0px;padding: 16px;background-color:#99ff99;color:#990000;">Don\'t miss the <big>Christmas Buffet</big> at Commons and Knollcrest dining halls tonight!</div><br />';

		}

		// Christmas 2013 thank-you and congratulations
		if (false && '2013-12-23' == $today && $this->isCalvinNews) {
			$out .= '<div style="background-color:#ffffff;border-style: double; border-color: #008800;padding: 12px;margin:16px 16px 0px;color:#008800;font-family:Cambria, Times New Roman, serif;"><big><strong>Merry Christmas!</strong>
				Thank you for all the <span style="color:#004400">hard</span> work you have been doing for Calvin College in this season!
				May God bless you and continue to shower you with His unending grace during this Christmas season and in 2014!</big>
				<div style="text-align:right;"><small><br />from ' . CONTACT_NAME . ' (student) at Enhanced Calvin News</small></div></div><br />';
		}
		if (false && '2013-12-23' == $today && !$this->isCalvinNews) {
			$out .= '<div style="background-color:#ffffff;border-style: double; border-color: #008800;padding: 12px;margin:16px 16px 0px;color:#008800;font-family:Cambria, serif;"><big><strong>Merry Christmas!</strong>
				Congratulations on finishing the Fall 2013 semester!
				May God bless you and continue to shower you with His unending grace during this Christmas season and in 2014!</big>
				</div></div><br />';
		}
		
		return $out;
	}
	
	private function getFooter($emailFormat, $citeVerseSource) {
		$out = '';
		
		$out .= "\n" . '<div style="margin: 0px 12px;"><small style="color:#777777"><br />Although the <strong>' . $this->newsItemsCount . '</strong>';
		$out .= ' news item(s) here are taken directly from Calvin\'s official ' . $this->publicationTitle;
		$out .= ' mailing, this &quot;Enhanced ' . $this->publicationTitle . '&quot; format is not endorsed by Calvin College in any way. ';
		if ($emailFormat) {
			$out .= 'To opt out, please reply to this message or contact ' . CONTACT_NAME . '.';
		} else {
			if ($this->sourceURL) {
				$out .= 'The content on this page was taken from <a href="' . $this->sourceURL . '">' . $this->sourceURL . '</a>.';
			}
		}
		if ($citeVerseSource) {
			$out .= ' <br /><br /> The Bible verse at the top is provided by OurManna.com.';
		}
		$out .= "</small></div>\n";
		
		return $out;
	}
	
	private function getDailyBibleVerse() {
		$verse = '';
		
		// TODO Save the daily verse on the server for future reference.
		
		// setup CURL and download a Bible verse of the day
		// for documentation and other formats, see http://www.ourmanna.com/verses/api/
		$url = 'http://www.ourmanna.com/verses/api/get/';
		if (isset($_REQUEST['randomverse'])) {
			$url .= '?order=random';
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		ob_start(); 
		curl_exec($ch); 
		curl_close($ch); 
		$verse = ob_get_contents();
		ob_end_clean();
		
		//$verse = 'Here is a trustworthy saying that deserves full acceptance: Christ Jesus came into the world to save sinners—of whom I am the worst. - 1 Timothy 1:15 (NIV)';
		
		// check for what seems like the proper format -- hopefully this will filter out errors
		if (preg_match('#^.+\\-.+:.+\\(.+\\)$#', $verse) >= 1) {
			// fix special characters
			$verse = NewsItem::fixSpecialCharacters($verse);
			
			// place the verse into a div, and the reference into another div
			$verse = '<div>' . preg_replace('#(^.+)( - .+$)#', '$1</div><div>$2', $verse) . '</div>';
		} else {
			$verse = '';
		}
		
		// Date-based verse chooser:
		//switch (date('Y-m-d')) {
		//	case '2014-02-10':
		//		//$verse = 'Genesis 1:1 - &quot;In the beginning, God created the heavens and the earth.&quot;';
		//	break;
		//}
		
		//
		
		return $verse;
	}
	
}

?>
