<?php

require_once 'utilsx.inc.php';
require_once 'myconstants.inc.php';

/**
 * Universal interface for retreveing and storing SessionIDs. 
 * Supports access to 3 keys 
 * @author Ernesto Guevara
 *
 */
interface ISessionID
{
	/**
	 * Returns key with specific index. In case key is not set (all keys, even empty should be set to '') throws exception.
	 * @throws SessionIDException 	in case there was key parse error or in case was requested key index which is out of bounds. 
	 * 						
	 * @param Int 	$index 		index of the key (session_id).  CALLER SHOULD BE SURE THAT CONTRACT allows the key index. or exception will be thrown.		
	 * @return 	String session_ID_key 
	 */
	function getKey($index);	
	function getKey0();
	function getKey1();
	function getKey2();
	
	/**
	 * @return Boolean 	FALSE in case there's active session established
	 * 					TRUE in case THERE'S NONE active sessions
	 */
	function noActiveSession();
	
	/**
	 * Parses html and extracts session_id (and other keys). Always SETS all possible keys (even if they're empty to '');
	 * 
	 * @throws SessionIDException 	in case error parsing
	 * @param String $html_doc_string		HTML document from which to extract the SESSIONIDs.
	 */
	function parseHTML($html_doc_string);
}

class SessionIDException extends  Exception
{
	
}

/**
 * Parse status constants
 * @author Ernesto Guevara
 *
 */
class SessionIDConstants
{
	const SUCCESS = 1;
	const FAILURE = 2;
	const UNINITIALIZED = 3;
	
	/**
	 * Verifies if the $canddate is valid value for the constant
	 * @param  SessionIDConstants(values)
	 * @return Booelan	result. TRUE if valid
	 */
	public static function isValid($candidate)
	{
		
		switch ($candidate){
			case self::FAILURE:
			case self::SUCCESS:
			case self::UNINITIALIZED:
				return true;
			default:
				return false;
					
		}
	}

}


abstract class SessionIDBase implements  ISessionID
{

	
	private  $lastParseStatus = SessionIDConstants::UNINITIALIZED;
	private  $key_array = null;

	
	function noActiveSession()
	{
		if ( $this->lastParseStatus == SessionIDConstants::SUCCESS)
		{
			return false; // there IS active session
		}
		
		return true; // in other situations (on failure or if is UNINITIALIZED there's NO active session
	}
	
	/**
	 * 
	 * @param SessionIDConstants::(CONSTANTS which are there) $status
	 */
	protected function setLastParseStatus($status)
	{
		if ( ! SessionIDConstants::isValid($status))
		{
			throw new SessionIDException("Error the value for the status is invalid [$status]");
		}
		$this->lastParseStatus = $status;
		
		
	}
	
	
	function getKey0()
	{
		return $this->getKey(0);
	}
	
	function getKey1()
	{
		return $this->getKey(1);
	}
	
	
	function getKey2()
	{
		return $this->getKey(2);
	}
	
	
	/**
	 * Returns key ("" in case key was empty) or throws exception (in case parsing failed)
	 * When requesting certain key, the caller should be sure that it is valid to request key with that index.
	 * If the key was not set during parsing - then exception will be raised. 
	 * 
	 * Checks if the keyarray (is not null) and 
	 * @param int $index			index of the parameter
	 * @throws	SessionIDException	in case there's error with parsing, or in case there's error with keys missing. 
	 */
	function getKey( $index)
	{
		// verify last parse status 
				if ( $this->lastParseStatus == SessionIDConstants::FAILURE)
				{
					throw new SessionIDException("Error getting SessionID key index [$index].".
													" THE RESULT OF LAST PARSING IS 'FAILURE'.");	
				}
				
				if ( $this->lastParseStatus == SessionIDConstants::UNINITIALIZED)
				{
					throw new SessionIDException("Error getting SessionID key index [$index].".
							" THE RESULT OF LAST PARSING IS 'UNINITIALIZED'.");
				}	
				
				if ( $this->lastParseStatus != SessionIDConstants::SUCCESS)
				{
					throw new SessionIDException("Error getting SessionID key index [$index].".
							" Logic error, the last parse status is not SUCCESS, nor FAILURE, nor UNITIALIZED.");
				}		
		

		// verify if array is valid 
				if ( $this->key_array === null)
					throw new SessionIDException("Error getting SessionID key index [$index].".
													" THE ARRAY OF KEYS IS NULL.");	

		// verify if specific element is set and return it (we assume that even if the element wasn't found in document, it should be set
		//			in arrray as empty '' element
				if ( isset($this->key_array[$index]))
				{
					return $this->key_array[$index];
					
				}
				else
				{
					throw new SessionIDException("Error getting SessionID key index [$index].".
							" ELEMENT IS NOT SET.");
				}
		
	}
	
	
	protected function setKey($index, $key_string)
	{
		$this->key_array[$index] = $key_string;
	}
	
	
	// because this method is already present in the ISessionID, we cannot redeclare it here.
	//abstract public function parseHTML($html_doc_string);
	
}


class SessionID extends SessionIDBase
{
	const MINIMUM_SESSIONIDS_FOUND = 2;
	
	
	/**
	 * (non-PHPdoc)
	 * @see ISessionID::parseHTML()
	 */
	public function parseHTML($html_doc_string)
	{
		
		// extract keys with static method
				$keys = self::_extractSessionIDs($html_doc_string);
				if ( $keys == false) 
				{
					$status = SessionIDConstants::FAILURE; 
					$this->setLastParseStatus($status);
							
					throw new SessionIDException("Error parsing, array is empty");
				}	
			
				$arLen = count($keys);
				if (   $arLen != self::MINIMUM_SESSIONIDS_FOUND )
				{
					$status = SessionIDConstants::FAILURE;
					$this->setLastParseStatus($status);
					
					throw new SessionIDException("Error parsing. Array len is $arLen and should be ". self::MINIMUM_SESSIONIDS_FOUND. ").");
				}
		// set keys and succes of status

				foreach ($keys as $k=>$v)
				{
					$this->setKey($k, $v);					
				}
					
				$status = SessionIDConstants::SUCCESS;
				$this->setLastParseStatus($status);
	}
	
	
	/**
	 * Extracts from the HTML document returned by the server the two session IDs.
	 * @param String  $content HTML contents of the document
	 * @returns	FALSE on failure
	 * 			String[2] on success (with 2 valid KEYS. (First "__VIEWSTATE" and second "__EVENTVALIDATION")
	 */
	private static function _extractSessionIDs($content)
	{
		
		try
		{ 
				$a_token = '__VIEWSTATE" value="';
				$z_token = '" />';
			
				$key1 = find_between($content, $a_token, $z_token);
			
		
			
				$a2_token = '__EVENTVALIDATION" value="';
				$z2_token = '" />';
				$key2 = find_between($content, $a2_token, $z2_token);
			
			
				return array($key1, $key2);
		}
		catch (FindBetweenException $e)
		{
				return false;
		}
	
	}	


}


?>