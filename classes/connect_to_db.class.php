<?php

/*
 * WARNING!!!!!!!!!!!!
 * WARNING!!!!!!!!!
 * WARNING!!!!!!!!!!!!!!! THERE'S EXPLICIT NAME OF THE INTERFACE WITH THE CONSTANTS
 * This class provides static methods to receive PDO of the specific database
 * to which the caller referes (eg. via INI-file).
 * 
 * Uses interface with constants to determine db credentials
 *
 * @author Ernesto Guevara
 */
include_once "config_constants.interface.php";
class ConnectToDB {
        /**
         * @param IConfigConstants interface - containing all the database connection params
         *          and constants which define table
         * @return PDO  
         */
        public static function connectToDB_UsingIniFileData( $TABLE_CONSTANTS_INTERFACE)
        {
//               $cl =  get_class($TABLE_CONSTANTS_INTERFACE);
//               echo $cl;
//					//$fileName = TABLE_CONSTANTS_INTERFACE::DHC_CONFIG_FILE;
//                        echo $cl::DHC_CONFIG_FILE;                
                                        
            $fileName = IConfigConstants::DHC_CONFIG_FILE;
                    $PARAM = parse_ini_file($fileName);

                    $host = $PARAM[IConfigConstants::DHC_INI_HOST];
                    $user = $PARAM[IConfigConstants::DHC_INI_USER];
                    $password = $PARAM[IConfigConstants::DHC_INI_PASSWORD];
                    $db 	= $PARAM[IConfigConstants::DHC_INI_DATABASE];
                    
                    // TODO: HOW TO MAKE IT WORK PROPERLY WITH UTF-8????
                    $connstr = "host=$host;dbname=$db";
                    //echo "Connstr:[$connstr]";

                    /* @var $a PDO */
                    $pdo = new PDO("mysql:$connstr", $user, $password);
                    $pdo->exec("SET NAMES UTF8");  // as I remember it wasn't enough. That was just useless operation?
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $pdo;
        }
}

?>