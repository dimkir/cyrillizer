<?php
header('Content-type: text/html; charset=UTF-8');
include_once "../include.inc.php";
mb_internal_encoding("UTF-8");
include_once "utilsx.inc.php";
include_once "classes/syllable_splitter2utf.class.php";





$param = getParamOrDefault("w", "comer");


$sc  = new StringCaret($param);
                
                // test output
                echo "<h3>$param</h3>";

                for($i= 0; $i < strlen($param) ; $i++)
                {
                    echo "[".$param[$i]."]  ";
                }

                echo "<br>\n";
                $mlen = mb_strlen($param);
                for( $i = 0 ; $i < $mlen; $i++)
                {
                    echo "[". mb_substr($param, $i, 1)."]";
                }

                echo "<p>";
                $needle = "ру";
                $res = mb_strstr($param, $needle);
                if ( $res === false)
                {
                    echo "cannot find substring [$needle] in [$param]";
                }
                else
                {
                    echo "found substring [$needle] in [$param]";
                }

                
// string caret iteration
                echo "<p>Now iteration from string caret: ";
                for (   ; !$sc->isOutsideWord(); $sc->movePosition())
                {
                  echo "[". $sc->curChar()  ."]"   ;
                }
                
?>


<form method="GET">
    <input type="text" name="w" value="<? echo $param; ?>">
    
    <input type="submit" value="Check word">
    
    
</form>