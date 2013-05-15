<?php

include_once('simple_html_dom.php');
//require 'cyrillizer.class.php';
include 'syllable_convertor_to_cyrillics.class.php';
//include "header.inc.php";
include_once "config_constants.interface.php";
include_once "connect_to_db.class.php";
include_once "syllable_splitter2.class.php";


/**
 * This is helper wrapper class to ease access to the article properties. And
 * probalby his MAIN functionality is that it provides the 
 * echoln() stdout() and transliterate() methods (the latter is for trasferring spanish to cyrillics)
 */
class MenubeArticle
{
	private $title;
	private $subtitles;
	
	
        /**
         * This is reference to the cyrillizer object, which can cyrillize spanish words
         * @var type 
         */
	private $cyrillize;
	
        
        /**
         *
         * @param String          $title
         * @param Array(String)   $subtitle_array
         * @param Cyrillizer      $cyrillize 
         */
	function __construct($title, $subtitle_array, $cyrillize = null)
	{
// 		echo "constructor is executing";
// 		var_dump($subtitle_array);
		$this->title = $title;
		$this->subtitles = $subtitle_array;
                $this->cyrillize = $cyrillize;
// 		var_dump($this->subtitles);
	}
	
	function getTitle()
	{
		return $this->title;
	}
	
	function getSubTitles()
	{
		return $this->subtitles;
	}
	
	function stdout()
	{
		$this->echoln("title:<b>[" . $this->title ."]</b>");
		$this->echoln("subtitles:");
		foreach ($this->subtitles  as $key=>  $st)
		{
			$this->echoln("<li>" . $st);
		}
		$this->echoln("end subtitles.");
		
	}
	
	function echoln($s)
	{
		if ( $this->cyrillize )
		{
			echo $this->transliterate(null, $s)."<br>\n";	
		}
		else 
		{
			echo $s."<br>\n";
		}
	}
	
        /**
         * NOT USED.
         * This function is here for didactical reasons. (It is example of how to use in UTF-8 russian characters)
         */
	static function transliterate($textcyr = null, $textlat = null) {
		$cyr = array(
				'Ð¶',  'Ñ‡',  'Ñ‰',   'Ñˆ',  'ÑŽ',  'Ð°', 'Ð±', 'Ð²', 'Ð³', 'Ð´', 'e', 'Ð·', 'Ð¸', 'Ð¹', 'Ðº', 'Ð»', 'Ð¼', 'Ð½', 'Ð¾', 'Ð¿', 'Ñ€', 'Ñ�', 'Ñ‚', 'Ñƒ', 'Ñ„', 'Ñ…', 'Ñ†', 'ÑŠ', 'ÑŒ', 'Ñ�',
				'Ð–',  'Ð§',  'Ð©',   'Ð¨',  'Ð®',  'Ð�', 'Ð‘', 'Ð’', 'Ð“', 'Ð”', 'Ð•', 'Ð—', 'Ð˜', 'Ð™', 'Ðš', 'Ð›', 'Ðœ', 'Ð�', 'Ðž', 'ÐŸ', 'Ð ', 'Ð¡', 'Ð¢', 'Ð£', 'Ð¤', 'Ð¥', 'Ð¦', 'Ðª', 'Ð¬', 'Ð¯');
		$lat = array(
				'zh', 'ch', 'sht', 'sh', 'yu', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', 'x', 'q',
				'Zh', 'Ch', 'Sht', 'Sh', 'Yu', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'c', 'Y', 'X', 'Q');
		if($textcyr) return str_replace($cyr, $lat, $textcyr);
		else if($textlat) return str_replace($lat, $cyr, $textlat);
		else return null;
	}	
	
}







/**
 * Allows to get scrape article data from the MENUBE website. (via getArticleHeader() method)
 */
class MenubeScrapper 
                    implements IConfigConstants // just to have constants to be able to connect to DB
{

	private $cyr = false;            // reference for the cyrrilizer object
	
	private $syllabSplitter = null;  // this is temporary reference to the syllabSplitter object.
									 // we will not be refering to it directly in our final build.
									 // this is just to try out and fill out our database. 
	private $html_dom;
	
	
	/**
	 * Initializes scrapper. 
	 * The scrapper is using mysql database, so it needs parameters to mysql.
         * @param $mysqlParams  HASH with DB login data
	 * (at the moment to store data for cyrillization)
	 * mysql 		hash-array of: 
	 * 				user
	 * 				password
	 * 				host
	 * 				database
	 * to connect to mysql.
	 */
	function __construct($mysqlParams)
	{
                $pdo = ConnectToDB::connectToDB_UsingIniFileData($this); // via $this, passes DB CONSTANTS
		$this->cyr = new SyllableConvertorToCyrillics($pdo);
		
		//FIXME: this is temporary putting in effect the syllabSplitter
		//$this->syllabSplitter = new SyllableSplitter(SYLLABLESPLITTER_PARAM_MYSQLCREDENTIALS, $mysqlParams);
                $this->syllabSplitter = new SyllableSplitter2();
	}
	
	
	/**
	 * Loads the DOM model into the class and keeps it there so that we can refer to it later
	 * Scans the webpage for specific links and returns them as article header.
         *
         * @param  String $url (URL of the MENUBE article to load)   
         * @return MenubeArticle 
	*/
	function getArticleHeader($url)
	{
			// $url = 'http://blog.minube.com/las-10-estaciones-de-tren-mas-espectaculares/';
			
			$html_dom = file_get_html($url);
			
			
	
			$this->html_dom = $html_dom;
			
                        $title = $this->_findTitle($html_dom);
                        $subtitles = $this->_findSubTitles($html_dom);
		    
                        return  new MenubeArticle($title, $subtitles, $this->cyr);
	}	


			private function _findSubTitles($html_dom)
			{
				// find subtitles
				$subtitles_to_return = array();
				
				$subtitles = $html_dom->find('div.entry h3');
				foreach ( $subtitles as $subt)
				{
					//echo "<li>".$subt->innertext."<br>\n";
					$subtitles_to_return[] = $subt->innertext;
				}
			
				return $subtitles_to_return;
			}
				
			private function _findTitle($html_dom)
			{
			
						// find the title
				$title_element_array = $html_dom->find('h1.title');
				return $title_element_array[0]->innertext;
// 				if ( ( $title_element_array== false ) || ( count($title_element_array)  < 1 ) )
// 				{
// 					echo "returned null or empty array!";
			
// 				}
// 				else
// 				{
// 				    echo "Array length is: " . count($title_element_array)."<br>\n";
// 				    foreach ($title_element_array as $a)
// 				    {
// 				     	echo $a->innertext."<br>\n";
// 				    }
			
// 				}
			}
				
			private function println($s)
			{
			  echo $s."<br>\n";
			}
			
			function saveToFile($fname)
			{
				$this->html_dom->save($fname);
			}
			
			/**
			 * Prints the whole document html to the screen
			 */
			function output()
			{
                                $html_dom->set_callback(array(&$this, 'mycallback'));
				echo $this->html_dom;
                                $html_dom->remove_callback();
			}
			
			
			/**
			 * Callback used to process inner text in some way
			 * @param unknown_type $element
			 */
			public function mycallback($element)
			{
				if ( $element->first_child() == false)
				{
					$tag = $element->tag;
                                        //$this->__saveToFile($tag);
					$contents = $element->innertext;
					//$contents = MenubeArticle::transliterate(null,$element->innertext);
                                        $contents = $this->_cyrillizeContents($contents);
					if ($contents == null) $contents = "empty";
					$element->innertext = $contents;
				}
			}
                        
                        
                        /**
                         * Takes any string contents and cyrillizes it as if it were spanish text
                         * @param String $contents 
                         */
                        private function _cyrillizeContents($contents)
                        {
                            $cyrillizedWordsStringar = array();
                            //$words = explode("\n\r\t ;,.!", $contents);
                            // $words = explode("\n\r\t ;,.!", $contents);
                            
                            //foreach ($words AS $w)
                            for ( $tok = strtok($contents, "\n\r\t ;,.!"); $tok !== false; 
                                                                    $tok = strtok("\n\r\t ;,.!"))
                            {
                                try
                                {
                                    $syllable_stringar = $this->syllabSplitter->getSyllables($tok);

                                    $cyrillized_syllables_stringar = $this->cyr->convertSyllableArray($syllable_stringar);
                                    $cyrillizedWordsStringar[] = implode('', $cyrillized_syllables_stringar); 
                                }
                                catch (SyllableSplitterException $sex)
                                {
                                    $cyrillizedWordsStringar[] = "errorSplittingSyllable";
                                }
                            }
                                
                            return implode(" ", $cyrillizedWordsStringar);
                            
                        }
                        
                        
                        

                        /**
                         * Logs contents of the tag (html element (OOP object))
                         * into tmp directory (d:/tmp/dom subdirectory)
                         * @param Element $tag 
                         */
                        private function _saveToFile($tag)
                                {
					$tmpfname = tempnam("d:/tmp/dom", $tag."_");
					$f = fopen($tmpfname, "w");
			
					fwrite($f, $element->tag."\n");
					fwrite($f, $element->innertext);
					fclose($f);
                                }
			
			
} // class MenubeScrapper

?>
