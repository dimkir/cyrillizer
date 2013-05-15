<?php

/*
 *  This is interface for methods which allow to perform spliting spanish words 
 * into syllables. 
 * 
 * Together with the interface there're two classes of exceptions as well 
 * which can be thrown by the methods of the interface.
 *
 * @author Ernesto Guevara
 */
interface ISyllableSplitter {
    /**
     * Takes a spanish word as an input and returns stringar of syllables.
     * Words can (should) have proper diactrics on them and function also
     * supports french style diactrics. 
     * @param String word - any spanish word. In UTF8, with symbols áéóíúÜúúÚúüüÚÜ
     *                                  can be also other (french) direction of diactrics. 
     *               ERRORNEOUS VALUES:
     *                  1) empty string = empty array
     *                  2) one character = array with one character
     *                  3) wrong characters within word (wrong symbols or brackets or smth like that) = ???
     *                  4) spaces within the word = ???
     *               TODO: at the moment I don't know what's the best way of 
     *                     handling errorneous values.
     * @return SUCCESS: array(string)
     *         
     * 
     *          
     */
    function getSyllables($word);
    
    
    
    const SYLSPLIT_TONICSYLLABLENUM = 1;
    const SYLSPLIT_SYLLABLES = 2;
    /**
     * Splits spanish word into syllables and finds tonic syllable (stressed syllable).
     * Not all interfaces will supports 
     * If this function is supported (otherwise throws exception. 
     * 
     * @return hash with two items:
     *              $RETURN[SYLSPLIT_TONICSYLLABLENUM] = [0..sylcount-1]
     *              $RETURN[SYLSPLIT_SYLLABLES] = array('co', 'mer'); 
     * 
     *          ERROR: throws an exception
     * @throws SyllableSplitterOperationNotSupportedException (in case the implementation
     *                              doesn't support tonic syllables)
     *         SyllableSplitterException (in case ther was a general error with splitting)
     */
    function getSylalblesAndTonic($word);
    
    
    /**
     * Service method:
     * Splits words into syllables, but instead of returning syllables as array, 
     * returns them as dash-ed se-quen-ce of cha-rac-ters. 
     * Useful for quick display of the splitting result.
     * @param String word to split
     * @return String (dashed word, or empty string on error). 
     * This method just uses getSyllables() method to do actual splitting, so it
     * bubbles up all the exceptions from that method and inherits error handling.
     * @throws SyllableSplitterException
     */
    function getSyllablesAsString($word);
}


/**
 * This exception is thrown only in case a method of the interface is not implemented 
 * // I think this one is unused actually (as of 2012-aug-07)
 */
class SyllableSplitterOperationNotSupportedException extends SyllableSplitterBaseException
{
}


/**
 * This is our workhorse exception, which is thrown in case 
 * the word splitting encountered an error. (?Don't know yet what kind of error.. maybe wrong character or smth) 
 */
class SyllableSplitterException extends SyllableSplitterBaseException
{
}


/**
 * This is base class for syllable exceptions, basically for lazy exception handling. 
 */
class SyllableSplitterBaseException extends Exception
{
}

?>