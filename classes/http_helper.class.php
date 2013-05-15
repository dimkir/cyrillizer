<?php
require_once "loggingwizard.class.php";

/**
 * Class providing HTTP operation methods:
 * POST request 
 * 
 * Also should provide methods for logging 
 * (define in constructor first parameter as true and 2nd parameter - the class which is using it).
 * 
 * @author Ernesto Guevara
 *
 */
class HttpHelper
{
	
	// flag which decides if we do loggin or not
	private $doLogging = false;

	/** @var LoggingWizard */
	private $logWiz = null; 
	
	/**
	 * 
	 * @param String/Object		 [ $loggingTag ]
	 * 							if it is omitted - no logging happens
	 * 							if String  - then String is used as the Tag
	 * 							if Object  - then class name is extracted from object 
	 * 												and classname is used as the tag.
	 * @throws  ?Can throw error, in case loggin is not critical...
	 * 
	 */
	public function __construct($loggingTag = false)
	{
		if ( $loggingTag != false )
		{
			if ( is_object($loggingTag))
			{
				// this is object reference, need to elicit class name
				$tag = get_class($loggingTag);
			}
			else
			{
				// just a variable
				$tag = $loggingTag;
			}
			
			$this->doLogging = true;
			$this->logWiz = new LoggingWizard(MC_LOGS_GLOBAL_DIR_, $tag);		
			
		}
	}
	
	
	/**
	 * Sends POST request to server
	 * @param unknown_type $url
	 * @param unknown_type $data
	 * @param unknown_type $referer
	 */
	public function postRequest($url, $data, $referer='') {
	
		// Convert the data array into URL Parameters like a=b&foo=bar etc.
		if ( $data != false)
		{
			$data = http_build_query($data);
			//echo "<pre>".$data."</pre>";
			//$data = '__EVENTTARGET=&__EVENTARGUMENT=&__VIEWSTATE=%2FwEPDwUJODQ2ODc4MDgxZGRm6E6L0lQ%2BUtzBG33Iy2XI84l%2FkN6yVWFPT13X7umhvg%3D%3D&__EVENTVALIDATION=%2FwEWAwKmpeDZDgLs0bLrBgKM54rGBvxiNiDyc79WxUtotnMUBJFB1CNabSkngjILUCjLqFDh&TextBox1=estaci%C3%B3n&Button1=Split';
			//echo "<pre>".$data."</pre>";
		}
		else
		{
			//echo "<pre>no data supplied</pre>";
				
		}
	
		// TODO: looks like this function is removing ?query part
		// parse the given URL
		print "<h2>Parsing URL:[" . $url . "]</h2>";
		$url = parse_url($url);
		
		
		var_dump($url);
		
	
		if ($url['scheme'] != 'http') {
			die('Error: Only HTTP request are supported !');
		}
	
		// extract host and path:
		$host = $url['host'];
		//$host = "localhost:8888";
		
		$path = $url['path'];
		
		if ( isset($url['query'])  )
		{
			 $path .= "?".$url['query'];
		}
	
		// open a socket connection on port 80 - timeout: 30 sec
		$fp = fsockopen($host, 80, $errno, $errstr, 30);
	
	
		if ($fp){
				
				
	
			// send the request headers:
			$this->logged_fputs($fp, "POST $path HTTP/1.1\r\n");
			$this->logged_fputs($fp, "Host: $host\r\n");
	
			if ($referer != '')
				$this->logged_fputs($fp, "Referer: $referer\r\n");
	
			$this->logged_fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			$this->logged_fputs($fp, "Content-length: ". strlen($data) ."\r\n");
			$this->logged_fputs($fp, "Connection: close\r\n\r\n");
			if ( $data != false )
			{
				$this->logged_fputs($fp, $data);
			}
	
			$result = '';
			while(!feof($fp)) {
				// receive the results of the request
				$result .= fgets($fp, 128);
			}
		}
		else {
			return array(
					'status' => 'err',
					'error' => "$errstr ($errno)"
			);
		}
	
		// close the socket connection:
		fclose($fp);
		
	
		$this->logWiz->putContents($result, "post_response");
	
		// split the result header from the content
		$result = explode("\r\n\r\n", $result, 2);
	
		$header = isset($result[0]) ? $result[0] : '';
		$content = isset($result[1]) ? $result[1] : '';
	
		$this->logWiz->putContents($content, "post_response.htm");
	
		// return as structured array:
		return array(
				'status' => 'ok',
				'header' => $header,
				'content' => $content
		);
	}
	
	
	/**
	 * Writes data to filePointer and to the log
	 * 
	 * This is temporary function
	 * @param filePointer $fp
	 * @param String $data
	 */
	private function logged_fputs($fp, $data)
	{
		fputs($fp, $data);
		if ( $this->doLogging)
		{
			$this->logWiz->put($data);
		}
	}	
	
	
}



?>