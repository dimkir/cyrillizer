<?php

include_once "config_constants.interface.php";

/**
 Это текст который написан на русском и должен быть в УТФ8 y tambíén Éñ éspañol áü?¡¿

 * This class provides methods to convert spanish syllables to cyrillics.
 * To convert it uses matchpairs of ES2RU syllables (which the class is retrieving 
 * from the open db). 
 * Eg:
 *  $syllables_ES2RU = 
 * array(   'es' => 'эс',
 *          'gi' => 'хи',
 *          'que' => 'ке'
 *      )
 * In case DB connection is not available it performs 
 * letter-by-letter syllable conversion.
 * 
 * The caller can also define letters to be used when converting, so only syllables with 
 * these letters will be converted.
 * 
 * This is more like a helper class to be used by the Cyrillizer class

 * @author Ernesto Guevara
 */
class SyllableConvertorToCyrillics
                    implements IConfigConstants
{

    /**
     * define data for accessing sylalble pairs 
     */
    // hash for converting syllables
    private $syllables_ES2RU = array();
    
    
    // NOT USED
    // this is used when converting just single characters
    private $characters_ES2RU = array();
    
    // the two below work in a fancy way
    private $spanishLetterArray; // stringar of spanish letters
    private $russianLetterArray; // stringar of russian letters
    
    /**
     * @param PDO
     */
    public function __construct($pdo) {
                                 // can throw exception (but in our case PDOException, 
                                 // so if we want to be fully abstract from the data source
                                 // we need to catch it and throw more generic exception
        $this->syllables_ES2RU = SyllableDBHelper::queryCyryllizerDBForMatchSyllablePairs($pdo);
        //$this->syllables_ES2RU = $this->_querySyllableTableForMatchPairs($pdo);
        
        //  print_r($this->syllables_ES2RU);              
                                // here if we want we can use different way of loading
                                // pairs. In reality we can even if we want to become abstract from 
                                // use some abstract clas 'SyllableMatchPairLoader',
                                // but that's for later versions 
        $this->characters_ES2RU = SyllableDBHelper::queryCyryllizerDBForLetterToLetterMatches($pdo);
        $i = 0;
        foreach ($this->characters_ES2RU AS $es=>$ru)
        {
            $this->spanishLetterArray[$i] = $es;
            $this->russianLetterArray[$i++] = $ru;
        }

    }
        
                
    
    
    private function _querySyllableTableForMatchPairs($pdo)
    {
        $retMatchingPairs = array(); 
        
        $syllable_table = SyllableConvertorToCyrillics::DHC_TABLE_NAME;
        
        $sql = "SELECT * FROM `$syllable_table`";
        
        echo "query: [$sql]<br>\n";
         
         foreach ( $pdo->query($sql) AS $row)
         {
//             echo "<pre>";
//             print_r ($row);
         
             $syl_es = $row[SyllableConvertorToCyrillics::DHC_FL_SYLES2RU_es_syllable];
             $syl_ru = $row[SyllableConvertorToCyrillics::DHC_FL_SYLES2RU_ru_syllable];
             $retMatchingPairs[$syl_es] = $syl_ru;
         }
         
         print_r($retMatchingPairs);
        
         //return $retMatchingPairs;
         return array('es'=>'ses');
    }   
    
    /**
     * Converts syllable from ES to RU via matching sylalble with hash values.
     * If can't match returns FALSE
     * @param type $src_syllable 
     * @return String - syllable in RU
     *         or
     *         FALSE if can't find in the hash
     */
    private function _convertViaMatchingSyllable($src_syllable, $russianCharactersToUse)
    {
        // echo __FUNCTION__  . " count of syllables array is : " . count($this->syllables_ES2RU) ."<br>\n";
        if ( isset($this->syllables_ES2RU[$src_syllable]))
        {
            
            return $this->syllables_ES2RU[$src_syllable];
        }
        return false;
    }
    
    
    private function _convertSyllableByLetter($src_syllable, $russianCharactersToUse)
    {
   
        
//          
//        // TODO: to remove invariant from here (as the arrays are parsed every time 
//        $spanishLetters = 'asdfghjklqwertyuiopñzxcvbnm';
//        $russianLetters = 'асдфгхйклквэртиуиопнзксвбнм';
//        $spanishLetterArray = array();
//        $russianLetterArray = array();
//        $len = strlen($spanishLetters);
//
//        
//        
//        for ($i= 0; $i < $len ; $i++)
//        {
//            $russianLetterArray[$i] = $russianLetters[$i];
//            $spanishLetterArray[$i] = $spanishLetters[$i];
//        }
        
        
        $dest_syllable = str_replace($this->spanishLetterArray, $this->russianLetterArray, $src_syllable);
       //  echo "src sylalble[$src_syllable]:[$dest_syllable]<br>\n";
        
        return $dest_syllable;
  
    }
    
    /**
     * 
     * @param stringar $syllable_stringar 
     * @param String    russian characters which are allowed to be used when converting
     * @return stringar  of converted syllables
     */
    public function convertSyllableArray($syllable_stringar, $russianCharactersToUse)
    {
        $retStringar = array();
        foreach ($syllable_stringar AS $src_syllable)
        {
            $dest_matched_syllable = $this->_convertViaMatchingSyllable($src_syllable, $russianCharactersToUse);
            if ( $dest_matched_syllable === false )
            {
                //echo "couldnt find matching pair in db for [$src_syllable]<br>\n";
                // couldn't convert via matching exact syllable, so 
                // we will convert it via macthing letter by letter. 
                
                // so far we do not implement this function, as I want to see 
                // what syllables are know and which are not
                $retStringar[] = $this->_convertSyllableByLetter($src_syllable, $russianCharactersToUse);
            }
            else
            {
                $retStringar[] = $dest_matched_syllable;
            }
        }
        
        return $retStringar;
        
        
    }
 
    
}// class


/**
 * Just here to incapsulate db helper methods into separate class.
 * (In this file it's ok, as there's only one operation with the database,
 * but in more db-intense class, there may be a lot of db opertions and 
 * I don't want to clutter the body of the caller class with db-operation methods. 
 */
class SyllableDBHelper
{
    /**
     * Queries db for ES2RU matching syllable pairs and returns as hash
     * @return Matchpair hash
     * array(   'es'  => 'эс',
     *          'gi'  => 'хи',
     *          'que' => 'ке'
     *      )
     * @param PDO $pdo 
     * @throws PDOException in case error with querying db
     */
    public static function queryCyryllizerDBForMatchSyllablePairs($pdo)
    {
        $retMatchingPairs = array(); 
        
        $syllable_table = SyllableConvertorToCyrillics::DHC_TABLE_NAME;
        
        $sql = "SELECT * FROM `$syllable_table`";
        
       // echo "query: [$sql]<br>\n";
         
         foreach ( $pdo->query($sql) AS $row)
         {
//             echo "<pre>";
//             print_r ($row);
         
             $syl_es = $row[SyllableConvertorToCyrillics::DHC_FL_SYLES2RU_es_syllable];
             $syl_ru = $row[SyllableConvertorToCyrillics::DHC_FL_SYLES2RU_ru_syllable];
             $retMatchingPairs[$syl_es] = $syl_ru;
         }
         
         //print_r($retMatchingPairs);
        
         return $retMatchingPairs;
         //return array('es'=>'ses');
    }
    

    /**
     * Returns hash of spanish letter to russian letter. (in UTF8).
     * $ret = array(
     *  'ñ' => 'н',
     *  'p' => 'п',
     *  'b' => 'б',
     * );
     * @param type $pdo 
     */

    public static function queryCyryllizerDBForLetterToLetterMatches($pdo)
    {
        $retLetterPairs = array(); 
        
        $letter_pair_table = SyllableConvertorToCyrillics::DHC_T_letterTable;
        
        $sql = "SELECT * FROM `$letter_pair_table`";
        
       // echo "query: [$sql]<br>\n";
         
         foreach ( $pdo->query($sql) AS $row)
         {
//             echo "<pre>";
//             print_r ($row);
         
             $let_es = $row[SyllableConvertorToCyrillics::DHC_FL_letters_esru_ES];
             $let_ru = $row[SyllableConvertorToCyrillics::DHC_FL_letters_esru_RU];
             $retLetterPairs[$let_es] = $let_ru;
         }
         
         //print_r($retMatchingPairs);
        
         return $retLetterPairs;
    }
    
}

?>