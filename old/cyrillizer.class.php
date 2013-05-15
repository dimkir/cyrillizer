<?php 

require_once 'syllablesplitter.class.php';

/**
 * WARNING: THIS IS OLD CLASS. Which is tied to UNFINISHED syllablesplitter_v1.class!!!!
 * Class implements cyrillize(String $word); method, which cyrillizes SPANISH (!) word.
 * This class is custom made for the spanish orphography.
 * 
 * Uses mysql database to store 
 * 1) word 2 syllables map
 * 2) syllable - to cyrillics map
 * if there's no mysql database can do the trivial cyrillization (just character by character conversion)
 
 * 
 * 
 * WHAT'S THE USAGE OF THE OBJECT?:
 * $mysqlParams = array( "user" => "root", "password"=> "rootpass", "host"=> "localhost", "database"=>"mySyllabDB");
 * $cyr = new Cyrillizer($mysqlParams);
 * $cyr_res = $cyr->cyrillize("comer");
 * if ( $cyr_res == "комэр" ) 
 * 
 * 
 * @author Ernesto Guevara
 *
 */
class Cyrillizer
{
	// DATA
	private $mysqlParams;	// connection parameters for the mysql database
	
        /**
         *
         * @var PDO
         */
        private $pdo;
	
        
        /**
         * @var SyllableSplitter2
         */
	private $syllabilizer; // object which splits words into syllables
	
	private $wordCache = null; // 
	
	/**
	 * 
	 * @param stirngHash 	$mysqlParams (or explicitly NULL in case we won'd use db)
         *                      // the explicit way of not passing mysqlParams is done 
         *                      // so that the caller never forgets about the fact that 
         *                      // functionality of the class is tied with the database
                    		with parameters for msyql connection: 
	 * 			user, password, host, database
         *                      Can be FALSE - then using built in methods for cyrillizing, 
         *                      the one's which are not syllable super correct.
	 
         * @param ISyllableSplitter $syllabilizer        in case we want to use syllabilizer from the caller
         *                                              we can use that.
         *                                               
         * 
         * @throws SomeDBException in case error connecting to db. 
         *                          This is not fatal error, as in case there's no db
         *                          the trivial way of converting sylalbles will be used 
         *                          (like letter by letter)
	 */
	function __construct($mysqlParams , $syllabilizer = null)
	{
		// init syllabilizer if not set
                if ( $syllabilizer )
                {
                      $this->syllabilizer = $syllabilizer;
                }
                else
                {
                    // we keep the $this->syllabilizer at it's default value (null) 
                    // as the syllabilizer will be initialized when the first word will need 
                    // to be split.
                }
                        


                /**
                 * IMPORTANT: The mysql connection code (and exception throwing should be the  
                 * last part of the constructor. Because the object should be functional even
                 * if db connection fails and the caller decides that it will use the cyrillizer 
                 * without the DB connectivity.
                 */
                if ( ( $mysqlParams == false) || ($mysqlParams == null) )
                {
                        // there's no parameter, so the caller doesn't want to connect to db
			// TODO: perform PDO mysql connection
                    
		}
                else
                {
                        
                        // connect to the DB with the parameters
                    try
                    {
                        $this->pdo = new PDO($mysqlParams);
                    }
                    catch (PDOException $pex)
                    {
                        throw new SplitterDatabaseErrorException("Error connecting to db with the credentials");
                        //
                    }
                        
                }
                            
	}
	
	/**
	 * Cyrillizes SPANISH WORD (this implementation is hooked up with the spanish letters).
	 * @param string  $word non trempty word (word with valueable characters)
         * @param ??    $russianLettersToUse This is the list of russian letters which we want to 
         *              show up in the final cyrillized word. 
         *              Default value is NULL which means that ALL the russian letters are allowed
         *              to appear in the resulting word.
         * 	 * @return String 	cyrillized word
         * 
         * @throws SomeInvalidParameterException
         *          in case error splitting syllables for example
	 * 
	 */
	public function cyrillize($word, $russianLettersToUse = null)
	{ 
            
            
                // split word into syllables
                        if ( $this->syllabilizer == null)
                        {
                            $this->syllabilizer = new SyllableSplitter2();
                        }

                        try
                        {
                            $syllable_stringar = $this->syllabilizer->getSyllables($word);
                        }
                        catch (SyllableSplitterException $sex)
                        {
                            throw new SomeNewException("Some error:" . $ex->getMessage());
                        }
                
                // load syllable transform table from DB (if available and if wasn't loaded already)
                        // if were're here, let's load syllable conversion table
                        try
                        {
                                if ( $this->syllable_convertor == null)
                                {

                                    $this->syllable_convertor = new SyllableConvertorToCyrillics($this->pdo);
                                    // can throw exception - 
                                }
                                else
                                {
                                    // no convertor
                                }
                        }
                        catch (Exception $ex)
                        {
                            
                        }
                // convert syllables into russian
                // return cyrillized word
                        return implode('' , $cyrillized_syllable_stringar);
	}
}


?>