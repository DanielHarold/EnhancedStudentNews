<?php

require_once ('config.php');

require_once ('NewsItem.php');
require_once ('functions.php');

class NewsIssue {
	public $publicationTitle;
	public $issueTitle;
	public $issueNumber;
	public $issueDateString;
	
	public $newsItems = array();
	public $newsItemsCount = 0;
	
	public $isCalvinNews = false;
	public $isStudentNews = false;
	
	public $sourceURL;
	
	private $sourceHTML;
	
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

		$out .= "<html><head><title>" . $this->issueTitle . "</title><style>body {font-family: Calibri, Arial, sans-serif; background-color: #f0f3ff;} a:link, a:visited.noMarkVisited {color:#444466;} a:visited{color:#666699;} a:hover, a:hover.noMarkVisited {color:#8e8ee4; text-decoration: none;}</style></head>\n";

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
			if ($this->isCalvinNews) {
				$out .= '<div align="center" style="text=align:center;margin:16px 16px 16px;color:#777777;"><small>If you would like to subscribe to Enhanced ' . $this->publicationTitle . ' or give feedback, please email ' . CALVIN_NEWS_SUBSCRIBE_EMAIL . '.</small></div>';
			} else {
				$out .= '<div align="center" style="text=align:center;margin:16px 16px 16px;color:#777777;"><small>If you would like to subscribe to Enhanced ' . $this->publicationTitle . ' or give feedback, please email ' . STUDENT_NEWS_SUBSCRIBE_EMAIL . '.</small></div>';
			}
		}

		// special headers such as special announcements and Daylight Savings Time reminders
		$out .= $this->getSpecialHeaders();

		// "top" anchor
		$out .= '<a id="top" name="top"></a>' . "\n";
		// invisible box to hold max-width of 800px
		$out .= '<div style="max-width: 800px; margin-left: auto; margin-right: auto;">' . "\n";
		
		// visible box to hold title and contents
		$out .= '<div style="border: solid 1px #777799; border-radius: 12px; margin: 8px 0px; background-color: #e8e8ff;">';
		// title area within visible box
		$out .= '<div style="padding: 8px;">' . "\n";

		$out .= '<h1 style="margin:0px;"><span style="color:#cc3333;text-transform:uppercase;font-size:80%;border:solid 2px #cc3333;background-color:#ffffff;padding:0px 4px;position:relative;top:-2px;">Enhanced</span> ' . str_replace(' ', '&nbsp;', $this->publicationTitle) . '</h1>';

		$out .= '<div><strong>Issue #' . $this->issueNumber . ' - ' . $this->issueDateString . "</strong></div>\n";
		$out .= '</div>' . "\n"; // close title area
		
		// weather widget
		if (!$emailFormat) {
			/* $out .= '<div style="float: right;">
				<script type="text/javascript" src="http://voap.weather.com/weather/oap/49546?template=GENXH&par=3000000007&unit=0&key=twciweatherwidget"></script></div>'; */
		}
		
		// Output the table of contents if there is more than one news item.
		$newsItemCount = count($this->newsItems);
		if ($newsItemCount > 1) {
			$tocOutput = '<div style="padding: 8px; background-color: #ffffff; border-radius: 0px 0px 12px 12px">';
			$hasAnyFood = false;
			
			$tocOutput .= '<ul style="padding-left: 24px;">' . "\n";
			foreach ($this->newsItems as $newsItem) {
				$tocOutput .= "\t" . $newsItem->getTOCEntry();
				if (!$hasAnyFood && $newsItem->hasFood) {
					$hasAnyFood = true;
				}
			}
			$tocOutput .= '</ul>' . "\n";
			
			if ($hasAnyFood) {
				$tocOutput .= '<div><small style="color:#555555">News items marked with an asterisk (*) appear to mention food. ';
				$tocOutput .= 'Read the news items carefully to see whether food is actually provided at an event, as not all events with food will be marked, and some events without food will be marked.</small></div>' . "\n";
			}
			
			$tocOutput .= '</div>' . "\n";
			$out .= $tocOutput;
		}
		
		// close title and contents box
		$out .= '</div>' . "\n";
		
		// Output news items
		foreach ($this->newsItems as $newsItem) {
			$out .= $newsItem->getFormattedHTML($this->newsItemsCount > 1);
		}
		
		$out .= '</div>' . "\n"; // close invisible bounding box
		$out .= $this->getFooter($emailFormat);
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
		
	}
	
	public function getNextIssuePath() {
		
	}
	
	private function getSpecialHeaders() {
		$out = '';
		$today = date('Y-m-d');
		
		// Daylight Savings Time beginning in 2014
		if ('2014-03-07' == $today) {
			$out .= '<div style="text-align:center;border: solid 2px #000000;font-weight:bold;margin:16px 16px 0px;padding: 16px;background-color:#ffffaa;font-size:large;">
				Note: Daylight Savings Time is beginning this weekend. Remember to turn clocks <em>forward</em> 1 hour on Saturday night!</div><br />';
		}
		
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
	
	private function getFooter($emailFormat) {
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
		$out .= "</small></div>\n";
		
		return $out;
	}
	
}

?>