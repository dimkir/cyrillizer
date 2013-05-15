<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Ernesto Guevara
 */
interface IConfigConstants {
    //put your code here
    const DHC_INI_HOST = "host";
    const DHC_INI_USER = "login";
    const DHC_INI_PASSWORD  = "password";
    const DHC_INI_DATABASE = "db_name";
    
    const DHC_CONFIG_FILE = "../../config.ini"; // path to the file
    
    
    /*
     * THIS IS CONSICED TABLE SPEC (OR AT LEAST TABLE DEPENDENCY)
     */
    const DHC_TABLE_NAME = "cyrillizer_cyrillized_syllables";
    const DHC_T_letterTable = "cyrillizer_map_letters_es2ru";
    //const DHC_T_letterTable = "cyrillizer_map_letters_es2ge";
    
    
    const DHC_FL_SYLES2RU_es_syllable = "syllable_es";
    const DHC_FL_SYLES2RU_ru_syllable = "syllable_cyr";
    
    const DHC_FL_letters_esru_RU = "ru";
    const DHC_FL_letters_esru_ES = "es";
    
    
        
}

?>