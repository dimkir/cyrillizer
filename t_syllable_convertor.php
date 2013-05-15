<?php

/**
 * This module is testing class: SyllableConvertorToCyrrilics
 *  
 */

header('Content-type: text/html; charset=UTF-8');
include_once "../include.inc.php";
//echo '<pre>';
include_once "config_constants.interface.php";
include_once "syllable_convertor_to_cyrillics.class.php";
include_once "syllable_splitter2.class.php";
include "utilsx.inc.php";


//TestSyllableConvertor::main();



class TestSyllableConvertor
                            implements IConfigConstants
{
        
    
        public static function main($src_syllable_stringar)
        {
            $o = new TestSyllableConvertor();
            $o->_main($src_syllable_stringar);
        }

        
        
        /**
         * @return PDO  
         */
        private function _connectToDB()
        {
                    $PARAM = parse_ini_file(self::DHC_CONFIG_FILE);

                    $host = $PARAM[self::DHC_INI_HOST];
                    $user = $PARAM[self::DHC_INI_USER];
                    $password = $PARAM[self::DHC_INI_PASSWORD];
                    $db 	= $PARAM[self::DHC_INI_DATABASE];
                    
                    // TODO: HOW TO MAKE IT WORK PROPERLY WITH UTF-8????
                    $connstr = "host=$host;dbname=$db";
                    //echo "Connstr:[$connstr]";

                    /* @var $a PDO */
                    $pdo = new PDO("mysql:$connstr", $user, $password);
                    $pdo->exec("SET NAMES UTF8");  // as I remember it wasn't enough. That was just useless operation?
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $pdo;
        }
        
        private function _main($src_syllable_stringar)
        {

            try
            {
                    // connect to DB ( PDO)
                    $pdo  = $this->_connectToDB();
                    // init the sylalble convertor class
                    $sc = new SyllableConvertorToCyrillics($pdo);
                    // try some sylalbles

                    
                    $allowed_chars = "абвгдеёжзийклмнопрстуфхцчшщъыьэюя";
                    $dest_syllable_stringar = $sc->convertSyllableArray($src_syllable_stringar, $allowed_chars);
                    //                              // the second parameter is empty
                                                    // so we will use all the russian characters to perform conversion

                    $src_word = implode('', $src_syllable_stringar);
                    $dest_word = implode('', $dest_syllable_stringar);
                    echo "<h1>Source word [$src_word] and cyrillized form is [$dest_word]</h1> <BR>\n";
            }
            catch (PDOException $pex)
            {
                echo "Error, cannot perform cyllabization as we can't connect to DB via PDO [".
                                    $pex->getMessage() . "]<br>\n";
            }
            
            
        }
}


$word = getParamOrDefault("w","comerandarnapoleonar");
$sSplitter = new SyllableSplitter2();
$src_syllable_stringar = $sSplitter->getSyllables($word);
//$src_syllable_stringar = array('co', 'mer', 'an', 'dar', 'na', 'po', 'le', 'on');
TestSyllableConvertor::main($src_syllable_stringar);
?>

<form> 
    <input type="text" name="w" value="<?php echo $word; ?>" size="60">
    <input type="submit" value="Cyrillize spanish word">
</form>