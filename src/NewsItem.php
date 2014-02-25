<?php

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
		$result = preg_match("#Date: (.+?)\n<br />\nFrom: (.+?)\n<br />\nSubject: (.*?)\n<br />\n<p>(.+?)\n<br />\n(Date: .+?\n<br />\n)?(From: (.+?)\n<br />\n)?Sender: (.+)\n<br />\\nPrecedence: bulk\n<br />\n(Reply-To: .+?\n<br />\n)?<p>(.+)#s", $this->sourceHTML, $matches);
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
			);
			
			if (in_array($this->submitter, $incorrectFromAddresses)) {
				$this->submitter = $matches[8];
			}
			if (in_array($this->submitter, $incorrectFromAddresses) && isset($matches[7])) {
				$this->submitter = $matches[7];
			}
			$this->body = $matches[10];
			
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
		
		$this->title = $this->fixSpecialCharacters($this->title);
		$this->body = $this->fixSpecialCharacters($this->body);
		
		// TODO Determine whether it is better to fix special characters in the news item body
		//      *before* or *after* unwrapping the text.
		
		if ($this->newsIssue->isStudentNews) {
			$foods = '(breakfast|lunch|dinner|supper|food|pizza|refreshments)';
			if (strpos(strtolower($this->body), 'refreshments') !== false
					|| preg_match('#' . $foods . '[^.!?;]+(provided|available|included|served)#s', strtolower($this->body))
					|| preg_match('#(free|there will be)[^.!?;A-Za-z]+' . $foods . '#s', strtolower($this->body))) {
				//$this->hasFood = true;
			} else if ($this->newsIssue->isStudentNews && preg_match('#(breakfast|lunch|dinner|supper|pizza|refreshment|donut|doughnut|cookie)#s', strtolower($this->body))) {
				$this->maybeFood = true;
			}
		}
	}
	
	function getTOCEntry() {
		// for some reason, Gmail puts a 15px margin-left on list items, so this will apply it in other places
		$out = '<li style="margin-left: 15px;">';
		
		$out .= '<a href="#' . $this->anchorName . '">' . $this->title . '</a>';
		
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
		$out .= '		<title>' . $this->title . '</title>' . "\n";
		$out .= '		<description>';
		$out .= str_replace("\n", "\n\t\t\t", htmlentities($this->autoSuperscriptOrdinals($this->unwrapNewsItemText(linkEmailAddresses($this->body)))));
		$out .= '</description>' . "\n";
		$out .= '		<dc:creator>' . $this->submitter . '</dc:creator>' . "\n";
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
	
	private function fixSpecialCharacters($text) {
		$fixedText = $text;
		
		// some hard coded frustration reduction; some of these are guesses, however.
		// note: at least one of the following strings contains an invisible character.
		// many of these are manual conversions from ANSI to UTF-8.
		// double and triple conversions are included for the apostrophe.
		// I have not figured out what the © conversions are yet.
		/*$fixedText = str_replace(
			array('©ø','©÷','©ö','¡©','³','²','¹','â€œ','â€™','â€','Ã¢â‚¬â„¢','â€“','â€”','Â·','Â½','â€¦','ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€Â¢','Ã¡','Ãº','Ã“','Ã­',),
			array('“', '”', '’', '–', '‘','’','’','“',  '’',  '”', '’',       '–',  '—',  '·', '½', '…',  '’',                 'á', 'ú', 'Ó', 'í', ),
			$text);*/
		
		$ansi = array('â‚¬', 'â€š', 'Æ’', 'â€', 'â€¦', 'â€ ', 'â€¡', 'Ë†', 'â€°', 'Å ', 'â€¹', 'Å’', 'Å½', 'â€˜', 'â€™', 'â€œ', 'â€', 'â€¢', 'â€“', 'â€”', 'Ëœ', 'â„¢', 'Å¡', 'â€º', 'Å“', 'Å¾', 'Å¸', 'Â ', 'Â¡', 'Â¢', 'Â£', 'Â¤', 'Â¥', 'Â¦', 'Â§', 'Â¨', 'Â©', 'Âª', 'Â«', 'Â¬', 'Â®', 'Â¯', 'Â°', 'Â±', 'Â²', 'Â³', 'Â´', 'Âµ', 'Â¶', 'Â·', 'Â¸', 'Â¹', 'Âº', 'Â»', 'Â¼', 'Â½', 'Â¾', 'Â¿', 'Ã€', 'Ã', 'Ã‚', 'Ãƒ', 'Ã„', 'Ã…', 'Ã†', 'Ã‡', 'Ãˆ', 'Ã‰', 'ÃŠ', 'Ã‹', 'ÃŒ', 'Ã', 'Ã', 'Ã', 'Ã', 'Ã‘', 'Ã’', 'Ã“', 'Ã”', 'Ã•', 'Ã–', 'Ã—', 'Ã˜', 'Ã™', 'Ãš', 'Ã›', 'Ãœ', 'Ã', 'Ã', 'ÃŸ', 'Ã ', 'Ã¡', 'Ã¢', 'Ã£', 'Ã¤', 'Ã¥', 'Ã¦', 'Ã§', 'Ã¨', 'Ã©', 'Ãª', 'Ã«', 'Ã¬', 'Ã­', 'Ã®', 'Ã¯');
		$utf8 = array('€', '‚', 'ƒ', '„', '…', '†', '‡', 'ˆ', '‰', 'Š', '‹', 'Œ', '', '‘', '’', '“', '”', '•', '–', '—', '˜', '™', 'š', '›', 'œ', '', 'Ÿ', ' ', '¡', '¢', '£', '¤', '¥', '¦', '§', '¨', '©', 'ª', '«', '¬', '®', '¯', '°', '±', '²', '³', '´', 'µ', '¶', '·', '¸', '¹', 'º', '»', '¼', '½', '¾', '¿', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ğ', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', '×', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'İ', 'Ş', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï');
		
		for ($i = 0; $i < 3; $i++) {
			$fixedText = str_replace($ansi, $utf8, $fixedText);
		}
		//$fixedText = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $text);
		
		$othersToReplace =   array('©ø','©÷','©ö','¡©','³','²','¹');
		$otherReplacements = array('“', '”', '’', '–', '‘','’','’');
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