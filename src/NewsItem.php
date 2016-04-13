<?php

// WARNING -- DO NOT SAVE THIS FILE USING cPanel
// OR THE SPECIAL CHARACTERS WILL BE CORRUPTED!

require_once ('functions.php');

class NewsItem {
	public $title;
	public $dateString;
	public $submitter;
	public $body;
	
	public $anchorName;
	public $newsIssue;
	
	public $hasFood = false;
	public $maybeFood = false;
	
	private $asterisksNSuch = '';
	
	private $sourceHTML;
	
	function __construct($sourceHTML, $anchorName, $newsIssue) {
		$this->sourceHTML = $sourceHTML;
		$this->anchorName = $anchorName;
		$this->newsIssue = $newsIssue;
		
		// First, handle the cases where the subject line wraps across two lines.
		$result = preg_match("#Date: (.+?)\n<br />\nFrom: (.+?)\n<br />\nSubject: (.*?)\n<br />\n<p>(.+?)\n<br />\n(Date: .+?\n<br />\n)?(From: (.+?)\n<br />\n)?Sender: (.+?)\n<br />\\nPrecedence: bulk\n<br />\n(Reply-To: .+?\n<br />\n)?<p>(.+)#s", $this->sourceHTML, $matches);
		
		if ($newsIssue->isStudentNews && $newsIssue->issueNumber == 3168) {
			// September 1, 2015
			// ESN #3168 (201509/0000) was an interesting one
			$result = preg_match("#Date: (.+?)\n<br />\nFrom: (.+?)\n<br />\nSubject: (.*?)\n<br />\n<p>From: (.+?)\n<br />\n(To: .+?\n<br />\n)?(Date: .+?\n<br />\n)?Subject: (.+?)\n<br />\n(Date: .+?\n<br />\n)?<p>(.+)#s", $this->sourceHTML, $matches);
		}
		
		if ($result) {
			// subject line wrapped across two lines
			$this->title = $matches[3] . $matches[4];
			$this->submitter = $matches[2];
			
			$incorrectFromAddresses = array(
				'student-news&#64;lists.calvin.edu',
				'student-news-owner&#64;lists.calvin.edu', // hypothetical
				'student-news-temp-owner&#64;lists.calvin.edu',
				'calvin-news&#64;lists.calvin.edu', // hypothetical
				'calvin-news-owner&#64;lists.calvin.edu', // hypothetical
				'calvin-news-temp-owner&#64;lists.calvin.edu' // hypothetical
				// ? - 'stunews-admin@calvin.edu', // example: student-news/201509/0000
			);
			
			if (in_array($this->submitter, $incorrectFromAddresses)) {
				$this->submitter = $matches[8];
			}
			if (in_array($this->submitter, $incorrectFromAddresses) && isset($matches[7])) {
				$this->submitter = $matches[7];
			}
			$this->body = $matches[10];
			
			if ($newsIssue->isStudentNews && $newsIssue->issueNumber == 3168) {
				// September 1, 2015
				$this->body = $matches[9];
				$this->submitter = $matches[4];
				$this->title = $matches[7];
			}
			
			// Examples in Student News:
			//   201310/0014.html#2739.2 - has an extra From header and no extra Date header
			//   201310/0014.html#2739.6
			//   201310/0014.html#2739.21
			//   201312/0010.html#2781.3 - has no Reply-To header
			//   201401/0009.html#2795.21 - has two From headers; first is student-news-temp-owner@lists.calvin.edu
		} else {
			// single line subject line or unrecognized error
			preg_match("#Date: (.+?)\n<br />\nFrom: (.+?)\n<br />\nSubject: (.+?)\n<br />\n<p>(.+)#s", $this->sourceHTML, $matches);
			
			$this->title = $matches[3];
			$this->submitter = $matches[2];
			$this->body = $matches[4];
		}
		
		$this->title = NewsItem::fixTitle(NewsItem::fixSpecialCharacters($this->title));
		$this->body = NewsItem::fixSpecialCharacters($this->body);
		
		// TODO Determine whether it is better to fix special characters in the news item body
		//      *before* or *after* unwrapping the text.
		
		if ($this->newsIssue->isStudentNews) {
			$foods = '(breakfast|lunch|dinner|supper|food|pizza|refreshments|appetizers|snacks|bagels)';
			if (strpos(strtolower($this->body), 'refreshments') !== false
					|| preg_match('#' . $foods . '[^.!?;]+(provided|available|included|served|offered)#s', strtolower($this->body))
					|| preg_match('#(free|there will be)[^.!?;]+' . $foods . '#s', strtolower($this->body))) {
				$this->hasFood = true;
			} else if ($this->newsIssue->isStudentNews && preg_match('#(breakfast|lunch|dinner|supper|pizza|refreshment|appetizer|snack|bagel|donut|doughnut|cookie)#s', strtolower($this->body))) {
				$this->maybeFood = true;
			}
		}
	}
	
	function getTOCEntry() {
		// for some reason, Gmail puts a 15px margin-left on list items, so this margin-left will apply it in other clients
		$out = '<li style="margin-left: 15px; margin-bottom: 0.2em;">';
		
		$out .= '<a href="#' . $this->anchorName . '" style="text-decoration: none">' . $this->title . '</a>';
		
		if ($this->newsIssue->isStudentNews && $this->hasFood) {
			//$out .= ' <span style="background-color: #ffff66; font-weight: bold; font-size: small; border: solid 1px black; padding: 0px 2px;">FOOD</span>';
			$out .= $this->asterisksNSuch = ' *';
		} else if ($this->newsIssue->isStudentNews && $this->maybeFood && isset($_GET['debugFood'])) {
			$out .= $this->asterisksNSuch = ' <span style="color:#666666">*?</span>';
		}
		
		if (!isset($_GET['debugFood'])) {
			$this->asterisksNSuch = '';
		}
		
		$out .= '</li>' . "\n";
		
		return $out;
	}
	
	function getFormattedHTML($includeTopLink = true) {
		//return $this->getHTML_dark($includeTopLink);
		$out = '';
		
		$out .= '<a id="' . $this->anchorName . '" name="' . $this->anchorName . '"></a>' . "\n";
		
		// news item box: border with no padding
		$out .= '<div style="border: solid 1px #777799; border-radius: 12px; margin: 8px 0px; background-color: #e8e8ff;">' . "\n";
		// header area within news item box
		$out .= '<div style="padding: 8px;">' . "\n";
		
		$out .= '<big><strong style="color: #333355">' . $this->title . $this->asterisksNSuch . "</strong></big><br />\n";
		$out .= '<small style="color: #333333">by ' . preg_replace('#(.+) &lt;(<a href=".+?">).+#', '<strong>$1</strong> ($2email</a>)', linkEmailAddresses($this->submitter)) . '</small>';
		
		$out .= '</div>' . "\n";
		
		if ($includeTopLink) {
			$out .= '<div style="background-color: #ffffff; padding: 16px 8px;">' . "\n";
		} else {
			$out .= '<div style="background-color: #ffffff; padding: 16px 8px; border-radius: 0px 0px 12px 12px;">' . "\n";
		}
		
		$html_body = $this->autoSuperscriptOrdinals($this->unwrapNewsItemText(linkEmailAddresses($this->body)));
		
		$out .= $html_body . "\n" . '</div>';
		
		if ($includeTopLink) {
			$out .= '<div style="padding: 4px 8px; font-size: 90%;">';
			$out .= '<a href="#top" class="noMarkVisited">^ Top</a>';
			//$out .= ' - <a href="#">Email this to myself</a>';
			$out .= '</div>' . "\n";
		}
		
		$out .= '</div>' . "\n";
		
		return $out;
		// return '<span style="background-color:yellow;border:solid 1px black;">' . 'Test' . '</span>';
	}
	
	function getRSS() {
		$out = '';
		$out .= '	<item>' . "\n";
		$out .= '		<title>' . htmlentities($this->title) . '</title>' . "\n";
		$out .= '		<description>';
		$out .= substr(str_replace("\n", "\n\t\t\t", htmlentities($this->autoSuperscriptOrdinals($this->unwrapNewsItemText(linkEmailAddresses($this->body))))), 0, -1);
		$out .= '</description>' . "\n";
		$out .= '		<dc:creator>' . htmlentities($this->submitter) . '</dc:creator>' . "\n";
		$out .= '	</item>' . "\n";
		return $out;
	}
	
	private function unwrapNewsItemText($sourceText) {
		$unwrappedText = '';
		
		$lines = explode("<br />\n", $sourceText);
		$prevLine = '';
		foreach ($lines as $line) {
			if ((74 - strlen(html_entity_decode($prevLine)) >= strpos(html_entity_decode($line), ' '))
					|| (substr($line, 0, 3) == '<p>')) {
				$unwrappedText .= "<br />\n";
			}
			if ($line == "<p>------------------------------\n") {
				// This is left at the end of the last news item.
				break;
			}
			
			$unwrappedText .= $line;
			$prevLine = $line;
		}
		
		// replace <p> tags (which are left unclosed) with double line breaks
		$unwrappedText = str_replace('<p>', '<br />', $unwrappedText);
		
		// TODO 
		
		// remove the leading and trailing line breaks
		$unwrappedText = preg_replace('#^\\s*<br />(.*)<br />\\s*$#s', '$1', $unwrappedText);
		
		return $unwrappedText;
	}
	
	public static function fixTitle($title) {
		// This function changes titles like
		//   =?Windows-1252?Q?CHAPEL:::Tue::Pray:_=93Self-Control=94_Student_Worship_T?=
		// to be like
		//   CHAPEL:::Tue::Pray: �Self-Control� Student Worship T
		if (preg_match('#=\\?[^?]+\\?Q\\?(.+)\\?=#', $title, $matches)) {
			$newTitle = str_replace('_', ' ', $matches[1]);
			$newTitle = str_replace('=93', '�', $newTitle);
			$newTitle = str_replace(array('=94', '=92'), '�', $newTitle);
		} else {
			$newTitle = $title;
		}
		
		return $newTitle;
	}
	
	public static function fixSpecialCharacters($text) {
		$fixedText = $text;
		
		// some hard coded frustration reduction; some of these are guesses, however.
		// note: at least one of the following strings contains an invisible character.
		// many of these are manual conversions from ANSI to UTF-8.
		// double and triple conversions are included for the apostrophe.
		// I have not figured out what the � conversions are yet.
		/*$fixedText = str_replace(
			array('��','��','��','��','�','�','�','“','’','”','â€™','–','—','·','½','…','Ã¢â‚¬â„¢','á','ú','Ó','í',),
			array('�', '�', '�', '�', '�','�','�','�',  '�',  '�', '�',       '�',  '�',  '�', '�', '�',  '�',                 '�', '�', '�', '�', ),
			$text);*/
		
		$ansi = array('€', '‚', 'ƒ', '„', '…', '†', '‡', 'ˆ', '‰', 'Š', '‹', 'Œ', 'Ž', '‘', '’', '“', '”', '•', '–', '—', '˜', '™', 'š', '›', 'œ', 'ž', 'Ÿ', ' ', '¡', '¢', '£', '¤', '¥', '¦', '§', '¨', '©', 'ª', '«', '¬', '®', '¯', '°', '±', '²', '³', '´', 'µ', '¶', '·', '¸', '¹', 'º', '»', '¼', '½', '¾', '¿', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', '×', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Þ', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', '÷', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'þ', 'ÿ');
		$utf8 = array('�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�');
		
		for ($i = 0; $i < 3; $i++) {
			$fixedText = str_replace($ansi, $utf8, $fixedText);
		}
		//$fixedText = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $text);
		
		$othersToReplace =   array('��','��','��','��','�','�','�');
		$otherReplacements = array('�', '�', '�', '�', '�','�','�');
		$fixedText = str_replace($othersToReplace, $otherReplacements, $fixedText);
		
		
		return $fixedText;
	}
	
	private function autoSuperscriptOrdinals($text) {
		$newText = $text;
		$newText = preg_replace('/1st([^A-Za-z0-9])/', '1<sup>st</sup>$1', $newText);
		$newText = preg_replace('/2nd([^A-Za-z0-9])/', '2<sup>nd</sup>$1', $newText);
		$newText = preg_replace('/3rd([^A-Za-z0-9])/', '3<sup>rd</sup>$1', $newText);
		$newText = preg_replace('/([04-9]|1[1-3])th([^A-Za-z0-9])/', '$1<sup>th</sup>$2', $newText);
		
		// TODO: Figure out why this doesn't work:
		/* $patterns = array(
			'/1st([^A-Za-z0-9])/',
			'/2nd([^A-Za-z0-9])/',
			'/3rd([^A-Za-z0-9])/',
			'/([4-9])th([^A-Za-z0-9])/'
		);
		$replacements = array(
			'1<sup>st</sup>$1',
		    '2<sup>nd</sup>$1',
		    '3<sup>rd</sup>$1',
			'$1<sup>th</sup>$2'
		);
		return preg_replace($patterns, $replacements, $text); */
		
		return $newText;
	}
	
}

?>
