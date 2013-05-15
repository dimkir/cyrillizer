<?php

/*
 * Implements function which syllabizes the spanish book excerpt
 */

include_once 'phpBooks.class.php';
include_once 'classes/connect_to_db.class.php';
include_once 'classes/syllable_convertor_to_cyrillics.class.php';
include_once 'config_constants.interface.php';
//include_once 'syllable_splitter2.class.php';

class DBConfig implements IConfigConstants
{
}

/**
* Multibyte safe version of trim()
* Always strips whitespace characters (those equal to \s)
*
* @author Peter Johnson
* @email phpnet@rcpt.at
* @param $string The string to trim
* @param $chars Optional list of chars to remove from the string ( as per trim() )
* @param $chars_array Optional array of preg_quote'd chars to be removed
* @return string
*/
function mb_trim( $string, $chars = "", $chars_array = array() )
{
    for( $x=0; $x<iconv_strlen( $chars ); $x++ ) $chars_array[] = preg_quote( iconv_substr( $chars, $x, 1 ) );
    $encoded_char_list = implode( "|", array_merge( array( "\s","\t","\n","\r", "\0", "\x0B" ), $chars_array ) );

    $string = mb_ereg_replace( "^($encoded_char_list)*", "", $string );
    $string = mb_ereg_replace( "($encoded_char_list)*$", "", $string );
    return $string;
}

function mb_trim_while_changes($string, $chars )
{
    $afterChangeStr = $string;
    //$beforeChangeStr = $string;
    do
    {
        $beforeChangeStr = $afterChangeStr;
        $afterChangeStr = mb_trim($beforeChangeStr, $chars);
    }
    while ( $beforeChangeStr != $afterChangeStr);
    return $afterChangeStr;
}

function _contains_only_allowed_chars($slong, $allowed)
{
    $mlen = mb_strlen($slong);
    for ($i = 0; $i < $mlen; $i++ )
    {
        $wch = mb_substr($slong, $i, 1);
        if ( ! Character::_isOneOfTheCharsFromList($allowed, $wch))
        {
            return false;
        }
    }
    return true;
}

function _generate_verified_colorized_syllable_string($cyrillized_syl_stringar, $syllable_stringar, $allowed_letters)
{
    $rez = '';
    $i = -1;
    foreach ($cyrillized_syl_stringar AS $slog)
    {
        $i++;
       // if ( $rez != '') $rez .= "-";

        if ( _contains_only_allowed_chars($slog, $allowed_letters) )
        {
             $rez .= '<font color="red">' . $slog . '</font>';
        }
        else
        {
            $rez .= '<font color="blue">' . $syllable_stringar[$i] . '</font>';
        }
    }
    
    return $rez;
}




function action_book_syllabize($PARAM)
{
    $doCyrillize = getParamOrDefault("cyr", false);
    $allowed_letters = getParamOrDefault("l","абвгдеёжзийклмнопрстуфхцчшщъыьэюя");
    // open a book
    $p_books = new phpBooks();
    //print_r ( $p_books->getBookNames() );
    // get words array
//    $randomWords = $p_books->getBookWords("d:\wamp\www\spanish\books\/el_juego_de_ender_cap7_salamandra.txt ");
//    $randomWords = $p_books->getBookWords("d:\wamp\www\spanish\books\");
   // $randomWords = $p_books->getBookWords("d:\wamp\www\spanish\books\ojosdeperroazul.txt");
   // $randomWords = $p_books->getBookWords("d:\wamp\www\spanish\books\elpais_utf8_no_BOM.txt");
    $randomWords = $p_books->getBookWords("elpais_utf8_no_BOM.txt");
  // $randomWords = $p_books->getBookWords("d:\wamp\www\spanish\books\salamandra_utf8_nobom.txt");
//    $randomWords = $p_books->getRandomBookWords();

    
    $pdo = ConnectToDB::connectToDB_UsingIniFileData(new DBConfig()); // via $this, passes DB CONSTANTS
    $cyr = new SyllableConvertorToCyrillics($pdo);    
    
    
    $syl = new SyllableSplitter2();
    $count = 0;
    
    $ecol = new ErrorCollection();
    $all_syllables = array();
    
    foreach ($randomWords AS $w)
    {
        $count++;
        if ( $count == 2573) 
        {
                // break;
            $a = 200;
        }
        if ( substr($w,1,4) == "Creo")
        {
            $a = 100;
        }
        try
        {

                $quotes = array(
                    "\xC2\xAB"     => '"', // « (U+00AB) in UTF-8
                    "\xC2\xBB"     => '"', // » (U+00BB) in UTF-8
                    "\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
                    "\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
                    "\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
                    "\xE2\x80\x9B" => "'", // ‛ (U+201B) in UTF-8
                    "\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
                    "\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
                    "\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
                    "\xE2\x80\x9F" => '"', // ‟ (U+201F) in UTF-8
                    "\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
                    "\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
                );
                $w2 = strtr($w, $quotes);   
            //$w2 = str_replace('”' “','', $w2); // can't remove those
            $w2 = str_replace('"', '' , $w2);
            $w2 = str_replace("'",'', $w2);
            $w2 = mb_trim_while_changes($w2, ';, .?¿¡´"=)(/&%$#!-”');
            //$w2 = mb_trim_both($w2, ';, \.?¿¡´"=)(\/\&%$#!-”' );
            if ( $w2 != '')
            {
                //echo " [$w2]";
                //$w1 = $syl->getSyllablesAsString( $w2 );
                $syllable_stringar = $syl->getSyllables($w2);
                $w1 = implode('-', $syllable_stringar);
                if ( $doCyrillize )
                {
                    $cyrillized_syl_stringar = $cyr->convertSyllableArray($syllable_stringar, null);
                    $verified_syl_stringar = _generate_verified_colorized_syllable_string($cyrillized_syl_stringar, $syllable_stringar, $allowed_letters);
                    print_r($verified_syl_stringar);
                    //$w1 = implode('-', $cyrillized_syl_stringar);
                    $w1 = $verified_syl_stringar;
                }
                echo ' ' . $w1;
                
                $syl_stringar = $syl->getSyllables($w2);
                foreach ($syl_stringar AS $slab)
                {
                    $slab = mb_strtolower($slab);
                    if ( ! isset( $all_syllables[$slab]) )
                    {
                        $all_syllables[$slab] = 1;
                    }
                    else
                    {
                        $all_syllables[$slab]++;
                    }
                }
            }
        }
        catch (SyllableSplitterException $sex)
        {
            echo  " <span style='color: Red;'>";
//            if ( $count == 1) 
//        
//            {
//                echo 'BOM Con';
//                
//            }
//            else
//            {
                echo $w2 . " ($w)[$count]";
                $ecol->add($sex);
//            }
            
            echo "</span>";
            
            //$sex->
        }
    }
    echo '<pre>';
    echo "Found " . count($all_syllables) . " syllables.\n\n";
    echo '<form method="GET">';
    echo '<input type="hidden" name="a" value=""><br>'.PHP_EOL;
            
    arsort($all_syllables);
    
    foreach ($all_syllables AS $syl=>$qty)
    {
       _print_syl_record($syl, $qty); 
    }
    
    //print_r($all_syllables);
    echo '</form>';
    echo '</pre>';
    //print_r ($randomWords);
    $ecol->renderToStdOut();
    
    // split word by word
    // display splitted words
    
}

function _print_syl_record($syl, $qty)
{
    static $v = 100;
    $v++;
    $idz = 'idz_' . $v;
    $idb = 'but_' . $v;
    
    echo $syl . " .. " . $qty . "<br>";
    echo "Spanish: <input type='text' name='aa' value='$syl'> ";
    echo "Russian: <input id='$idz' type='text' name='ru' value=''>";
    echo "<input id='$idb' type='button' name='but' value='Save $idz' onClick=\"javascript:test1('$syl', document.getElementById('$idz'), document.getElementById('$idb')  );\">";
    echo "<br>\n\n";
    
}

class ErrorCollection
{
    
    private $msg = array();
    
    /**
     * 
     * @param Exception $ex 
     */
    function add($ex)
    {
        $this->msg[] = "<b>".$ex->getMessage() ."</b><br><pre>" . $ex->getTraceAsString() ."</pre>";
    }
    
    function renderToStdOut()
    {
        echo "<br>\n";
        echo "<br>\n";
        echo "<br>\n";
        foreach ($this->msg AS $m)
        {
            echo $m ."<br>\n";
            echo "<br>\n";
            echo "<br>\n";
        }
    }
    
}
?>