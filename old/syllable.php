<?php 

require_once 'myconstants.inc.php';
require_once 'tweet_to_dimitry.inc.php';
require "syllablesplitter.class.php";

define("SYLLABLE_CONFIG_FILE","config.ini",false);
define("SYLLABLE_PARAM_WORD", "word", false);

/**
 * THIS SCRIPT IS JUST FRONT-END for testing SyllableSplitter.class
 * it just displays the user text input to input the word and returns the word split in syllables.
 */
main(); // EP


/**
 *  Main Entry Point. So that PHP script looks more structured
 */
function main()
{
			$d = 1;
			
			if ( !isset($_GET[SYLLABLE_PARAM_WORD]))
			{
				output_prompt("Error, please input word (parameter = ".SYLLABLE_PARAM_WORD.")", true);
				return; // main()
			}
			
			
			$word = $_GET[SYLLABLE_PARAM_WORD];
			
			
			$MYSQL_CREDENTIALS = parse_ini_file(SYLLABLE_CONFIG_FILE);
			var_dump($MYSQL_CREDENTIALS);
			
			try 
			{
					$syl = new SyllableSplitter(SYLLABLESPLITTER_PARAM_MYSQLCREDENTIALS, $MYSQL_CREDENTIALS);

					$syllables = $syl->getSyllables($word);
					
					if ( $syllables == null)
					{
						// error getting syllables
						output_prompt("Error, cannot split. Returned NULL", true);
					}
					else
					{
						output_syllables($syllables);
						
						output_prompt("Success");
						_tweet_result(		$_GET[SYLLABLE_PARAM_WORD], 
											$syl->syllableArrayToDashedString($syllables), 
											"TwitterConstants"
								);
						
					}

			}
			catch ( SyllableException $e)
			{
					output_prompt($e->getMessage(), true);
			}
			return; // main()
}


/**
 * Just tweets the result to twitter. 
 * Gets Autehntication parameters from interface passed as $twitterCredentials
 * @param unknown_type $word
 * @param unknown_type $syllable_array
 * @param TwitterConstants  $twitterCredentials NOT USED NOWl6
 * 
 */
function _tweet_result($word, $dashed_syllables, $twitterCredentials)
{
	$msg = "Just looked up word [$word] with result [$dashed_syllables]";
	tweet_to_dimitry($msg);
}


function output_syllables($syllables_array)
{
	// later will be JSON printing.
	var_dump($syllables_array);
}

function output_prompt($msg = false, $error = false)
{
	
	if ( $msg )
	{

		if ( $error )
		{
			echo "<font color=red>$msg</font>";
		}
		else{
			echo "<font color=green>$msg</font>";
		}
	}
   print <<<HEREDOC
	<form method=GET>
	Please input word:
	<input type=text size=60 name=word>
	
	
	</form>
	
HEREDOC;
   
}

?>