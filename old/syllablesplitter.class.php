<?php

/***************************************************************
 * *************************************************************
 * *************************************************************
 * *************************************************************
 * *************************************************************
  *  WARNING THIS CHASS WAS REDESIGNED AND REDONE. It's HERE FOR
 *   HISTORICAL PURPOSES ONLY. THIS CLASS IS NOT fully (or is?) i don't remember
 *   FUNCTIONAL.
 * 
 *  (may be good as an example for other scarping applications)
  * *************************************************************
  * *************************************************************
  * *************************************************************
  * *************************************************************
  * *************************************************************
  * *************************************************************
 * * * * * * * 
 * 
 */
 require_once  "../../simple_html_dom.php";
 
 
 define("SYLLABLESPLITTER_INI_HOST","host", false);
 define("SYLLABLESPLITTER_INI_USER","login", false);
 define("SYLLABLESPLITTER_INI_PASSWORD","password", false);
 define("SYLLABLESPLITTER_INI_DATABASE","db_name", false);
 
 
 define("SYLLABLESPLITTER_PARAM_PDO", 1, false);
 define("SYLLABLESPLITTER_PARAM_MYSQLCREDENTIALS",2 , false); 

 require_once 'syllable_table_definitions.class.php'; // defines 'SyllableSplitterConstants
 require_once 'http_helper.class.php';
 require_once 'syllableexceptions.class.php';
 require_once 'sessionid.class.php';
 
 require_once 'syllable_splitter.interface.php';

/**
 * This class is splitting words into syllables. 
 * At the moment it does it through direct connection to the web service. 
 * With every instantiation of the class there's a "connect" call made, so 
 * in case multiple words are meant to be parsed - may it be done with 
 * the same instance of the class.
 * 
 * TODO:
 * the class should cache syllables in mysql. Otherwise there's no point.
 * in orde to do that ther can be one more method (or second constructor)
 * setMysqlParams($PARAMS); with the host/database/user/password parameters
 * @author Ernesto Guevara
 *
 */

class SyllableSplitter implements ISyllableSplitter
{
	
	/** var SessionID */
	private $sessionID;

	/**
	 *  @var    $httpHelper HttpHelper */																			
	private $httpHelper  = null; // this is helper object which allows us to make post requests
	
	const baseUrl = 'http://tip.dis.ulpgc.es/Silabas_Web/default.aspx?lang=en';
	
	/* @var $pdo PDO  */
	private $pdo = null;			// handle for mysql pdo
	

	
	/**
	 * Creates syllable splitter object. to use later with method 
	 * this.getSyllables(String $word) : String[] resulting_syllables
	 * if necessay - connects to database (or just saves the reference to PDO)
	 * 
	 * @param unknown_type $FIRST_PARAM
						    if FIRST_PARAM is MysqlCredentials, need to contain the following fields:
						    login, password, host, db_name
						    
						    
						    if FIRST_PARAM is PDO OBJECT (Should be initialized with UTF8)
						    
						    if FIRST_PARAM is false (THEN(!) the syllable splitter will work without
						    caching
	
	 * @param 	unknown_type $FIRST_PARAM_TYPE
	 * @throws	SyllableException 		in case error connecting to DB
	 */
	function __construct($PARAM_TYPE , $PARAM )
	{
		/**
		 * @var $b PDOStatement
		 */
		if ( $PARAM_TYPE == SYLLABLESPLITTER_PARAM_MYSQLCREDENTIALS)
		{
				try
				{
					$host = $PARAM[SYLLABLESPLITTER_INI_HOST];
					$user = $PARAM[SYLLABLESPLITTER_INI_USER];
					$password = $PARAM[SYLLABLESPLITTER_INI_PASSWORD];
					$db 	= $PARAM[SYLLABLESPLITTER_INI_DATABASE];
					$connstr = "host=$host;dbname=$db";
					//echo "Connstr:[$connstr]";
					
					/* @var $a PDO */
					$a = new PDO("mysql:$connstr", $user, $password);
					$a->exec("SET NAMES UTF8");
					$a->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$this->pdo = $a;
					
				}
				catch (PDOException $e)
				{
					throw new SyllableException("Exception, error connecting to PDO:" . $e->getMessage());
				}
		}
		elseif ( $PARAM_TYPE == SYLLABLESPLITTER_PARAM_PDO)
		{
				// just save reference to pdo
				$this->pdo = $PARAM;
		}
		else 
		{
			throw new SyllableException("Error, parameter type is 
								spescified invalid :[" . $PARAM_TYPE . "]");
		}
		

		
		
	}
	
	
	/**
	 * Initializes HttpHelper object IF IT IS NOT INITIALIZED YET.
	 */
	private function _initHttpHelper()
	{
		if ( $this->httpHelper == null)
		{
			$this->httpHelper = new HttpHelper($this); // current object ClassName will be used as a LoggingTAG
		}
	}
	
	/**
	 * Starts session with the remote server (initializes session and keeps the session_ids so that 
	 * all further transactions with server can be performed through the session.
	 * 
	 * This method should be called BEFORE any other method of the SyllableSplitter is called 
	 * (TODO: is this true statement? or may it not be called?)
	 * Actually this is not completely true, because the truth is that getSyllables can reinitiate the session if it is not set.
	 * We need to know that the start_service_session() can fail during the runtime of the script, for example because of the connection error,
	 * and thus it may be necessary to retry. Or let's say that there's a script which sleeps and then tries to process words over and over,
	 * then in case during one "wake period" the session failed -that's no problem - because 
	 * 
	 * @throws SyllableFailureToStartSesssion exception
	 */
	private function start_service_session()
	{
		// init httpHelper object so that we can make post requests		
		
			$this->_initHttpHelper();
		
		
		// send request and get reply (document) from which we will extract SESSION_IDs
				$rez = $this->postRequestWrapper(self::baseUrl, false);
				if ( $rez == null)
				{
					throw new SyllableFailureToStartSesssion("SyllableSplitter::start_service_session(): cannot connect to the server");
				}
				if ($rez['status'] != 'ok' )
				{
					throw new SyllableFailureToStartSesssion("Error, the status of POST is not 'ok'");
				}
				$content = $rez['content'];
		
		
		// extract SESSION_IDs
				try
				{
						$SessionID = new SessionID();
						$SessionID->parseHTML($content);
						$this->sessionID = $SessionID;
				}
				catch (SessionIDException $e)
				{		
						throw new SyllableFailureToStartSesssion("Error parsing html: ".$e->getMessage()
																	.PHP_EOL
																	.$e->getTraceAsString()
																	
								);
				}
		
		
	}
	
	/**
	 * Gets syllables from cache if they're avaialble there.
	 * @return	FALSE - on MISS
	 * 			String[] resultingSyllables
	 * 			or {@link SyllableException} in case there's  no PDO object.
	 * 
	 * 
	 * @throws 	PDOException		in the current implementation simply bubbles PDOException
	 * 								technically can do that in case there's an error connecting to DB		
	 * 
	 * @param String	 $word
	 * @param PDO	 $pdo
	 */
	private function _getSyllablesFromCache($word)
	{
		if ( ( $this->pdo == false ) || ($this->pdo == null ) )
		{
			throw new SyllableException("Errro, pdo parameter is null or false. Cannot fetch syllables from cache");
		}
		
		/** var $a PDO 
		 * 
		 */
		$a = $this->pdo;
		
// 	 	In the current implementation just bubbles the PDOException and do not catch or morph it into another one.
// 		try
// 		{
				//  put quotes around the word
					$word  = $a->quote($word);
					
				// prepare short version of database tables and it's fields
						// table names
							$tbWord = SyllableSplitterConstants::TB_WORDS;
							$tbSyll = SyllableSplitterConstants::TB_SYLLABLES;
							
						// fields of WORD table
							$fwWord = SyllableSplitterConstants::F_WORDS_WORD;
							$fwID	= SyllableSplitterConstants::F_WORDS_ID;
							
						// fields of SYLLABLES table
							$fsSyl	= SyllableSplitterConstants::F_SYL_SYLLABLE;
							$fsOrd	= SyllableSplitterConstants::F_SYL_ORDER;
							$fsWID	= SyllableSplitterConstants::F_SYL_WID;
							$fsComm	= SyllableSplitterConstants::F_SYL_COMMENT;
				
				
				
				$SELECT = "SELECT a.$fwWord , b.$fsSyl , b.$fsOrd , b.$fsWID ";
				$FROM = " FROM `$tbWord` AS a ";
				$JOIN = "JOIN `$tbSyll` AS b ";
				$ON = " ON a.$fwID = b.$fsWID ";
				$WHERE = " WHERE a.$fwWord LIKE $word ";
				$ORDER = " ORDER BY b.$fsOrd ASC";
				
				/**
				 SELECT a.word, b.syllable, b.order, b.wid
					FROM `words`  AS a 
					JOIN `syllables` AS b 
					ON a.id = b.wid
					WHERE a.word LIKE 'andar'
					ORDER BY b.order ASC
				 */

				$query = "$SELECT $FROM $JOIN $ON $WHERE $ORDER";
				echo "Prepared query <h2>[$query]</h2>";
				$stmt = $a->query($query);
				$syllables  = array();
				while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) )
				{
					var_dump($row); echo "<br>\n";
					$syllables[] = $row[$fsSyl];
				}
				$stmt = null;
				if ( count($syllables) > 0 )
				{
					return $syllables;
				}
				return false;
				
				// 		}
// 		catch (	PDOException $e)
// 		{		
// 			throw new PDO
// 		}
				
		
	}
	
	
	/**
	 * Returns word split into syllables as a dash-seprated-string
	 * @param unknown_type $word
	 * @return Ambigous <Ambigous, string, unknown>
	 */
	function getSyllablesAsDashedString($word)
	{
		$syllable_array = $this->getSyllables($word);
		return $this->syllableArrayToDashedString($syllable_array);
	}
	
	
	/**
	 * Converts array of syllables into dash-separated-string
	 * @param unknown_type $syllable_array
	 * @return Ambigous <string, unknown>
	 */
	function syllableArrayToDashedString($syllable_array)
	{
			$rez = '';
			foreach ($syllable_array as $s){
				if  ( $rez != '')
				{
					$rez .= "-";
				}
				$rez .= $s;
			}
		
			return $rez;
	}
	
	/**
	 * Splits word into syllables and returns syllable array of strings
	 * 
	 * LOGIC WARNING: it may be possible that certain request for session may fail and then the metohd would be called again
	 * 				  to retry. (This is why start_session is called in several places throughout the class
	 * @param 		String $word
	 * @return		array of string of syllables
	 * 				?? do we return null? or false
	 * 
	 * @throws		SyllableException 		in case some fatal error occurs (like dbError or smth)
	 * @throws		SyllableFailureQueryOverNetwork : SyllableNonCritical
	 * @throws		SyllableFailureToStartSesssion	: SyllableNonCritical
	 */
	function getSyllables($word)
	{
		
		
		// if cache available and word is in cache - fetch it from there.
		if ( $this->pdo != null )
		{
			$syllables_array = $this->_getSyllablesFromCache($word, $this->pdo); 
								 // returns false when MISS occurs, or exception in case of error
							     // when this method is called $this->pdo MUST BE INITIALIZED (or exception
								 // will be thrown
															 
			if ($syllables_array != false)
			{
				return $syllables_array;
			}
		}
		
		
		
		// TODO: check if the sessionID has invalid value as well: 
		//  || ($this->sessionID->noSession() )
		if ( ( $this->sessionID == null) || $this->sessionID->noActiveSession() )
		{
				$this->start_service_session();
		}
		
		
		// @throws SyllableQueryFailureOverNetwork
		$syllables_array = $this->_postWord($word);
		var_dump($syllables_array);
		
		if ( ( $syllables_array != false )  && ( $this->pdo != null) )
		{
			try 
			{
				$this->_saveWordToCache($word, $syllables_array, $this->pdo); // throws *FailureSavingToCache
			}
			catch (SyllableFailureSavingToCache $e)
			{
				echo "Error saving to cache[" . $e->getMessage() ."]";
			}
			catch (SyllableNonCritical $e)
			{
				echo "No rows affected. Probably word already exists";
			}
		}
		
		return $syllables_array;
	}
	
	
	
	/**
	 * 
	 * @param String 		 $word
	 * @param String[] 		 $syllables_array		According to contract: NON EMPTY array of syllables String[]
	 * @param PDO 	 $pdo					According to contract should be initialized PDO,
	 * 											otherwise will throw fatal exception
	 * @throws								SyllableFailureSavingToCache
	 */
	private function _saveWordToCache($word, $syllables_array, $pdo)
	{
		if ( ( $pdo == FALSE) || ($pdo == null) )
		{
			throw new SyllableException("Error, the PDO is not initialized");
		}
		
		if ( !is_array($syllables_array)) 
		{
			throw new SyllableException("Error, 2nd argument should be array. Now not array");
		}
		
		if ( count($syllables_array) < 1 )
		{
			throw new SyllableException("Error, 2nd argument should be NON EMPTY array. Now it IS EMPTY array");
		}
		
		
		// now do actual saving
		
		try
		{
			$table = SyllableSplitterConstants::TB_WORDS;
			$f_word = SyllableSplitterConstants::F_WORDS_WORD;
			
			$pdo->beginTransaction();
			
			$q = "INSERT IGNORE INTO `$table` (`$f_word`) VALUES ('$word')";
			echo "<h3>$q</h3>";
			$num_affected_rows = $pdo->exec($q);
			if ( $num_affected_rows < 1 )
			{
				echo "Error, number of affected rows isn't 1 ($num_affected_rows)";
				throw new SyllableNonCritical("Error updating cache for word [$word]");
			}
			$id = $pdo->lastInsertId();
			
			
			$table_syllables = SyllableSplitterConstants::TB_SYLLABLES;
			$f_wid = SyllableSplitterConstants::F_SYL_WID;
			$f_syllable = SyllableSplitterConstants::F_SYL_SYLLABLE;
			$f_order = SyllableSplitterConstants::F_SYL_ORDER;
			$f_comment = SyllableSplitterConstants::F_SYL_COMMENT;
			
			$i = 0;
			foreach ($syllables_array as $syllable)
			{
				$i++;
				$q2 = "INSERT INTO `$table_syllables` (`$f_wid`,`$f_syllable`,`$f_order`,`$f_comment`) ".
											  "VALUES ($id, '$syllable', $i, '$word')";
				echo "<h3>$q2</h3>";
				$num_rows = $pdo->exec($q2);
			}
			
			
			$pdo->commit();
			
			
		}
		catch (PDOException $e)
		{
			throw new SyllableFailureSavingToCache(__FUNCTION__."PDOException:: Error saving to cache: ".$e->getMessage().".");
		}
		
		
	}
	
	/**
	 * 
	 * @param 		String			 $word
	 * @return 		syllables array of strings
	 */
	private function _postWord($word)
	{
		
		$PP = array(
						"__EVENTTARGET" => 		"",
						"__EVENTARGUMENT" => 	"",
						"__VIEWSTATE" 	 => 	$this->sessionID->getKey0(),
						"__EVENTVALIDATION" => 	$this->sessionID->getKey1(),
						"TextBox1" 		   => 	$word,
						"Button1" 			=> 	"Split"
					);
		
		
		// FIXME: there's no more local method postRequest - need to remove
		$rez = $this->postRequestWrapper(self::baseUrl, $PP);
		$html = str_get_html($rez['content']);
		$elements = $html->find('div[id=div1]',0);

		$text = $elements->innertext;
		//echo "Inner text:<pre>". $text . "</pre>";
		
		$text = $this->_strip_tags_and_sup($text);
		//echo "Text us: [$text]";
		
		$ar = explode('-',$text);
		//var_dump($ar);
		return $ar;

		
	}
	
	/**
	 * removes tail of the string which starts with <sup> and also removes all the spaces.
	 * @param unknown_type $str
	 * @return unknown
	 */
	private function _strip_tags_and_sup($str)
	{
		$e_pos = strpos($str, "<sup>");
		if ( $e_pos === false ) $e_pos = strlen($str);
		$str = substr($str, 0, $e_pos);
		$str = strip_tags($str);
		$str = str_replace(" ","",$str);
		return $str;
	}
	
	
	/**
	 * Assumes that $this->httpHelper is not null
	 * @param unknown_type $baseUrl
	 * @param unknown_type $data
	 * @param unknown_type $referer
	 */
	private function postRequestWrapper($baseUrl, $data, $referer = '')
	{
		$helper = $this->httpHelper;

		return $helper->postRequest($baseUrl, $data, $referer); 
	}


}


?>