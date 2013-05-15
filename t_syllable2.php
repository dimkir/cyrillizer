<?php
mb_internal_encoding("UTF-8");
set_time_limit(100);

header('Content-type: text/html; charset=UTF-8');
include_once "../include.inc.php"; // what is this include? this one sets all the pathes
include 'html_top.inc.php';

/**
 * Test module for the SyllableSplitter2 class
 *  
 */
include_once 'utilsx.inc.php';
include_once 'classes/syllable_splitter2utf.class.php';
include_once 'actions/test_action_book_syllabize.php';

switch (getParamOrDefault('a', 'book'))
{
    case 'singleword':
            // we don't do anything special, just regular thing
            action_singleword($_GET);
            break;
    case 'book':
            action_book_syllabize($_GET);
            break;
}



function action_singleword($PARAM)
{

    $word = getParamOrDefault('w','comer', $PARAM); // get prams uses
$splitter = new SyllableSplitter2();
$split_word = $splitter->getSyllablesAsString($word);
echo $split_word;
?>

<html>
    <body>
        <form method="get">
            <input type="text" name="w" value="<?php echo $word; ?>">
            <input type="submit" value="Split word into syllables"/>
        </form>
        <a href="?a=book">Book</a>
    </body>
</html>

<?php
}






?>

