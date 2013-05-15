<?php

include_once 'syllable_splitter.interface.php';
include_once 'loggingwizard.class.php';
/**
 * This class provides methods to split spanish word into syllables and 
 * determining tonic syllable.
 * 
 * This is PHP-port of original class made in C# (and C++) by the professor 
 * of University of Grand Canarias (cit) . And available at his website (cit).
 * 
 * 
 * This PHP-port is made by Dimitry Kireyenkov
 * @email dimitry@languagekings.com
 * @author Ernesto Guevara
 */
class SyllableSplitter2 implements ISyllableSplitter {

    /**
     * This is main workhorse method which does all the job
     * @param String $word  (will be trimmed on sides). 
     *                      What are the requirements for the valid word? valid characters
     *                      and no spaces in the middle? 
     *                      One word with only valid characters (including 'supported french tildas'),
     *                      spaces/tabs/newlines on the sides are allowed (as trim() will trim them.
     *                      
     * @return Hash of syllable stringar and index of the tonic syllable
     */
    public function getSylalblesAndTonic($word) 
    {
        $trimmed_word = trim($word);
        
        $log = new LoggingWizard("d:/tmp/logroot/", "SYLLABLE_SPLIT");
        $log->put($word);
        if ( $trimmed_word == 'Forzados')
        {
            //throw new SyllableSplitterException("error forzados is chosen");
            $a = 100;
        }
        
        $sCaret = new StringCaret($trimmed_word);
        
        $syllable_start_indices = array();
        // here we will be finding indices of the syllables
        while ( !$sCaret->isOutsideWord() )
        {
            // record syllable beginning
               $syllable_start_indices[] = $sCaret->getCurPosIndex();
            // jump over syllable
                    $this->_shiftCaretOverAtaque($sCaret);
                    $this->_shiftCaretOverNucleo($sCaret);
                    $this->_shiftCaretOverCoda($sCaret);
        }

        //print_r($syllable_start_indices);
        $syllableStringar = $this->_wordToArray($trimmed_word, $syllable_start_indices);
        $retHash = array( self::SYLSPLIT_TONICSYLLABLENUM => 0,
                          self::SYLSPLIT_SYLLABLES =>  $syllableStringar
                    );
        return $retHash;
        
    }
    
    
    /**
     * Generates stringar with syllables from the word(string) and intar of sylable start indices
     * @param type $trimmed_word
     * @param type $syllable_start_indices_array 
     * @return Stringar of syllables
     */
    private function  _wordToArray($trimmed_word, $syllable_start_indices_array)
    {
        // TODO: needs implementation
       $returnSyllableStringar = array();
        
       $i = 0;
       for ($i = 0 ; $i < count($syllable_start_indices_array) ; $i++)
       {
           // we only do anyting when we encounter the end of the syllable 
           // (which is in our case: the beginning of the next syllable)
           if ( $i == 0 ) continue;
           $prevIndex = $syllable_start_indices_array[$i-1];
           $curIndex = $syllable_start_indices_array[$i];
           
           $lenOfSyllable = $curIndex - $prevIndex;
           $syllable = substr($trimmed_word, $prevIndex, $lenOfSyllable );
           $returnSyllableStringar[] = $syllable;
       }

       // by now the last syllable is not saved
       $prevIndex = $syllable_start_indices_array[$i-1]; // $i points at outside, with $i-1 we get pointer to the last element of the indices_array
       $curIndex = strlen($trimmed_word); // now pointing at the first index outside word
       $syllable = substr($trimmed_word, $prevIndex,  $curIndex - $prevIndex );

       $returnSyllableStringar[] = $syllable;
       return $returnSyllableStringar;
    }
    
    
    /**
     * Moves caret over consonants, stops at vowel or at any consonant which is in the stoplist
     * @param StringCaret $sCaret
     * @param String $consonant_stoplist_string  'y' or 'ykl' 
     */
    private function _moveCaretOverConsonantsExcept($sCaret, $consonant_stoplist_string)
    {
         while (   !$sCaret->isOutsideWord() &&  $sCaret->curChar()->isConsonantExceptBut($consonant_stoplist_string) ) 
         {
             $sCaret->movePosition(1);
         }
    }
    
    /**
     * @param StringCaret $sCaret
     * @return type  
     */
    private function _shiftCaretOverAtaque($sCaret)
    {
        $this->_moveCaretOverConsonantsExcept($sCaret, 'y'); 
        //TODO: what if it is BEHIND THE LAST CHAR. what if the word was invalid and had combination of consonants only at the and comerqqq?
        
        if ($sCaret->isOutsideWord())
        {
            return; // that's it, we finished word. probably was errorneous last syllable ending with consonants only like 'comer[qq]' or 'qqq'
        }
        
        if ( $sCaret->isLastChar() )
        {
            // if there's only one character (and in our case vocal o 'y') left, 
            // it will become part of núcleo. We don't have to do any more filters,
            // it can be ma[qu] or ma[gu] or ma[gü]. Though these last syllables are
            // theoretical, this is done so that this class behaviour is identical to the
            // original source written by señor Las Palmas
            return;
        }
        
        if ( $sCaret->isFirstChar() )
        {
            return; // we're pointing at first vowel => we're pointing at nucleose
        }
        
        // now can point at 'y' or vocal :
        // |yiendo
        // c|omer
        // g|uell
        // q|ueso
        
        // if it is pointing at pen[g|ui]no
        // then we have to skip the vocal 'u' because it will be part of ataque
        if ( $sCaret->prevChar()->pointsAt('gü') )
        {
            $sCaret->movePosition(1); // moves pointer to 'i' so that 
        }
        // if it is pointing at 
        // [q|ue]so
        // then we have to skip u, because u will be part of ataque as well
        else if ( $sCaret->prevChar()->pointsAt('qu'))
        {
            $sCaret->movePosition(1); // moves pointer to 'e', because it will be nucleo
        }
        else 
        {
            // these tuples will be used later
            $tuples = array('gue', 'gui', 'gué', 'guí');
            if ( $sCaret->prevChar()->pointsAtOneOf($tuples)) // 'gu[eéií]'
            {
                $sCaret->movePosition(1); // skip the 'u' and position straight at [eeii]
            }
        }
    }

    /**
     * 
     * @param StringCaret $sCaret 
     */
    private function _shiftCaretOverNucleo($sCaret)
    {

        if ( $sCaret->isOutsideWord()  )
        {
            return; // doesn't have nucleo. Could have happened fi the syllable was errorneous and only consisted of the consonants. (and was last syllable of the word)
        }
                
        // we skip over 'y'. This special treatment of 'y' character is inherited from the original source in C#.
        if ( $sCaret->curChar()->equals('y' ))
        {
            $sCaret->movePosition(1);

            if ( $sCaret->isOutsideWord())
            {
                return; // for example in case we were parsing preposition 'y'
            }            
        }
        
        // NUCLEO can be :
        // 1) mono vowel (íiée)
        // 2) diphtong (
        // 3) tripthong (
        // Trick is: if the vowel is potential diphtong, then it becomes candidate for a tripthong. 
//        
//        // if it can't be a diphtong, then it can't be a potential triphtong either. So we move over to first pos of coda and exit
//        if (!$wPointer->isPossibleDiphtongFromCurPos() ) 
//        {
//            // then this is the end of the nucleo. 
//            $wPointer->movePosition(1);
//            return;
//        }
//        
//        if ( $wPointer->isPossibleTriphtongFromCurPos() )
//        {
//            // hey it is triptong
//            $wPointer->movePosition(3); // only one will move to 2nd position in the tripthong
//        }
//        else
//        {
//            // it is Diphtong
//            // advance two:
//            $wPointer->movePosition(2);
//            // hiatus
//        }
        
        // aqui puede ser que el CurChar es tambien consonante (in case that was an incorrect word or smth like: cy|mente)
        // pues asi no avanzamos
        if ( ! $sCaret->curChar()->isVocal() )
        {
            return;  // parece que la palabra es incorrecta, pues no tenemos vocal, pues nucleo se termino
        }

        
        
        if ( $sCaret->curChar()->isVocalCerradaTildada() )
        {
            $sCaret->movePosition(1); // current char is accented vocal 
                                        //  theoretical patron for diptongo would be ´CC , that 
                                        //  doesn't fit into any possible 
                                        //  DIPTONGO patrones AC,CA,CC, ÁC, CÁ, C´C
                                        
            return; // end of nucleo
        }
        
        
        
        // la primera vocal, siempre va a estar,(pues va a estar la primera vocal del nucleo.
        //  pues lo que importa es entender si vamos a tener diptongo (AC, CA, CC) o triptongo (CÁC) accentuada no obligatamente tildada
        
        // we skip the first vowel
        // TODO: remember that it may have been tildada , that's important (in the analysis of the second character)
        $sCaret->movePosition(1);
        
        if ( $sCaret->isOutsideWord() )
        {
            
            return; // ya es el fin de la palabra como en pa[pa|]
        }
        
        
        
        // ************************
        // *** YA ESTAMOS EN LA SEGUNDA LETRA DESPUES DE LA PRIMERA VOCAL DE NUCLEO 
        // ahora estamos en la segunda letra.  puede ser la segunda letra del N-tongo
        //  o el consonante
                $hache = false;
                if (  $sCaret->curChar()->equals('h') )
                {
                    // 'h' intercalada en el nucleo, no condiciona diptongos o hiatos
                    $hache = true;
                    $sCaret->movePosition();
                }


                if ( $sCaret->curChar()->isConsonant() )
                {
                    // TODO: something should happen here with 'h' (rolled back or smth like that).
                    // !! no looks like everything is ok, like with the word KAHLO: [kah]|lo
                    return; // if we encounter consonant then it is fin of the nucleo as well. 
                            // and 'h' between first vowel of nucleo and first consonalt of coda, will
                            // belong to the nucleo. (liek in [kah]|lo 
                            //It is candidate for coda or ataque of the next syllable
                }
                
                if ( $sCaret->curChar()->isVocalCerradaTildada())
                {
                    // puede resultar en diptongo solo si sigue patron de C`C
                    // 
                    if ( $sCaret->prevChar()->isVocalCerrada() )  // hey: this actually can inculde any cerrada, including tildada
                        //TODO: maybe need to throw here invlalid letter combination exception, in case there's two tildadas characters
                        // PUEDE ser `C`C 
                        // y que pasa si hay 'h' adentro?
                    {
                        // C`C
                        $sCaret->movePosition(1); // saltamos a la letra despues de diptongo
                        return;
                    }
                    else
                    {
                        // si no es asi, y es patron A`C, este letra no va a ser parte de nucleo.
                        // tenemos que seguir a coda 
                        if ( $hache )
                        {
                            $sCaret->movePosition(-1); // si no es diptongo, 'h' tampoco va estar parte del nucleo
                        }
                        return; // este va a venir a fin:  
                    }
                }
                
                // aqui puede ser AC CA AA  CC pero NO PUEDE ser %`C  (C`C or A`C )
                
                // ********************************************************
                // pues aqui sabemos que es vocal CERRADA, pero NO TILDADA
                
                // puede tambien ser letra 'üÜ'
                
                // puede ser que estamos en la formula de 
                // de verdad aqui puede ser solo AC, CC, pero la logica tambien permite `CC (sino que C1==C2) úi  o íu
                
                // sort out triplets (split them into 1:2 in syllable
                    if ( $sCaret->hasNextChar() )
                    {
                        if ( !$sCaret->nextChar()->isConsonant() )
                        {
                            // if it is triplet, then we need to split triplet into 1:2 parts
                            if ( $hache )
                            {
                                $sCaret->movePosition(-1); // if there was hache, we move it back
                            }
                            return;
                        }
                    }
                
                
                if ( $sCaret->prevChar()->getCh() != $sCaret->curChar()->getCh() )
                {
                    $sCaret->movePosition(1); // if they're not same, that's a diptongo. CC o `CC
                }
                else
                {
                    // los dos son iguales, pues no es diptongo. Tenemos que marcar que es fin del nucleo
                    if ( $hache)
                    {
                        $sCaret->movePosition(-1);
                    }
                    return;
                }

                
                
              // si la segunda vocal es abierta no vale con el diptongo model de 'AC,CA,CC' con otra vocal abierta es model 'AA'
                if ( $sCaret->curChar()->isVocalAbierta()  )
                {
                    
                    if ( $sCaret->prevChar()->isVocalAbierta() )
                    {       // se rompe el diptongo
                            // esto no es la segunda vocal del nucleo/ENtongo, esto es el principio de la proxima sillaba, or por lo menos el fin del nucleo
                            // acordamos que si hemos tenido 'h', tenemos que volver lo
                            if ( $hache )
                            {
                                $sCaret->movePosition(-1); // pues 'ma[|hou]' en lugar de 'mah[|ou]
                            }
                            return; 
                    }
                    else
                    {
                        // estamos en patron del: C|A? puede ser si la proxima letra es 
                        if ( !$sCaret->curChar()->hasNextChar() )
                        {
                            $sCaret->movePosition(1); // make it point outside of word as we will leave 'CA' as a diptongo at the end of word
                            return; 
                        }
                        // check what's the story with the triptong?
                        // we're pointing to second vowel of the nucleo in patron C|A, that' definitely a dipthong, so we advance position. and 
                        if ( $sCaret->nextChar()->isVocalCerradaSinTilde() )
                        {
                            // triptongo
                            $sCaret->movePosition(2); // if we skip one, we will be at last letter of triptongo, now we're outside nucleo
                            return;
                        }
                        else
                        {
                            // diptongo
                            $sCaret->movePosition(1); // make point outside of nucleo
                            return;
                        }
                    }
                }                
                
                return;
    }
    
    /**
     *
     * @param StringCaret $sCaret 
     */
    private function _shiftCaretOverCoda($sCaret)
    {
        if ( $sCaret->isOutsideWord() ) return;
        if ( !$sCaret->curChar()->isConsonant() ) return;
        // POST CONDITION: now curChar is consonant
            if ( $sCaret->isLastChar()) 
            {
                // if this is last consonant of the word, there definitely won't be 
                // another syllable, so this consonant will be the coda
                $sCaret->movePosition(1);
                return;
            }
            // POST CONDITION: curChar is consonant and is NOT LAST character of the word
            // so possibly there may be more syllables made with our consonant
                if ( $sCaret->nextChar()->isVocal() )
                {
                    // like in  hia[|to] if 't' is curChar() and 'o' is the vowel after,
                    // then the 't' will be the part of the next syllable. So we just 
                    // found that 't' is the outside of coda already
                    return;
                }
                // POST CONDITION: curChar() is consonant and nextChar() is consonant and nextChar() is not empty
                    if ( $sCaret->nextChar()->isLastChar() )
                    {
                        // if the word is ending with two consonants, it will be the end, unless there's 'y' which
                        // may take role of 'vowel' in the syllable
                        if ($sCaret->nextChar()->equals('y') )
                        {
                            // it is '%y' where '%' is a consonant, so this is going to be a new syllable
                            return; // pointer points at '%'
                        }
                        $sCaret->movePosition(2); // we move pointer to the EOW, as ending with two consonants is the end of everything
                        return; // this is EOW (end of word)
                    }
                    // POST CONDITION: curChar() is consonant, nextChar() is consonant, the thirdChar() is valid (but we don't know yet if it is consonant or vowel)
                            if ( $sCaret->thirdChar()->isConsonant() )
                            {
                                // POSTCOND: thirdChar() == consonant
                                
                                // tres consonantes al final de la palabra extrajera? 
                                        if ( $sCaret->thirdChar()->isLastChar() )
                                        {

                                            // 'y' functiona como vocal
                                            if ( $sCaret->nextChar()->equals('y') &&  
                                                 $sCaret->curChar()->isOneOfTheChars('slrnc')  
                                                )
                                            {
                                                return;  // vamos a tener silaba de %y*
                                            }

                                            if ( $sCaret->thirdChar()->equals('y') )
                                            {
                                                // 'y' fial funciona como vocal con nextChar()
                                                $sCaret->movePosition(1); // moving to the nextChar(), cual con 'y' final va a hacer sillaba
                                                return;
                                            }

                                            $sCaret->movePosition(3); // para apuntar a EOW
                                            return;
                                        }
                                
                                // 'y' en el centro?
                                        if ( ( $sCaret->nextChar() == 'y')  )
                                        {
                                            if   ( $sCaret->curChar()->isOneOfTheChars('slrnc'))
                                            {
                                                return; 
                                            }
                                            $sCaret->movePosition(1);
                                            return;
                                            
                                        }    
                                            
                                // pt, ct, cn, ps
                                        $tuples = array('pt', 'ct', 'cn', 'ps', 'mn', 'gn', 'ft', 'pn', 'cz', 'tz', 'ts');
                                        if ( $sCaret->nextChar()->pointsAtOneOf($tuples) )
                                        {
                                            $sCaret->movePosition(1);
                                            return;
                                        }
                                        
                                // %%, %%l, ch
                                        if ( $sCaret->thirdChar()->isOneOfTheChars('lry') ||
                                                $sCaret->nextChar()->pointsAt('ch')
                                                )
                                        {
                                                // 'y' funciona como vocal
						// Siguiente sílaba empieza en c2                                            
                                                $sCaret->movePosition(1);
                                                return;
                                        }
                                        
                                        
                                        
                                // came to end of the coda
                                $sCaret->movePosition(2); // jump to the last consonant of the 3
                                                            // c3 inicia la siguiente sílaba
                                return;
                                
                            }
                            else // thirdChar() == vowel
                            {
                                // test rr, ch, ll
                                if ( $sCaret->curChar()->pointsAt('ll') ||
                                     $sCaret->curChar()->pointsAt('ch') ||
                                     $sCaret->curChar()->pointsAt('rr')
                                   )
                                {
                                    return; // parece que estamos en el principio del la nueva silaba
                                }
                                
                                // now we check if we can have 'aristocratic triple' %%$ (consonant, consonant, vocal) but with condition below.
                                // now we need to test for the 'aristocratic'  consonant combination, which is when 2nd consonant is 'h' and the 
                                // first consonant is 'aristicractic'. The consonant is 'aristocratic' when it is not 's' or 'r' (which are peasants :)) )
                                    $firstConsonantIsAristocratic = (  $sCaret->curChar()->notEquals('s') 
                                                                        && 
                                                                       $sCaret->curChar()->notEquals('r')
                                                                      ); 
                                    if ( $firstConsonantIsAristocratic && $sCaret->nextChar()->equals('h') )
                                    {
                                         // 'aristocratic' consonant combination.  they may start syllable.
                                        return;
                                    }

                                 
                                // test c2 == 'y' (nextChar() == 'y' )
                                if ( $sCaret->nextChar()->equals('y') )
                                {
                                    // si hay 'y' por el centro de triple, then we for sure will split it.
                                    if ( $sCaret->curChar()->isOneOfTheChars('slrnc') )
                                    {
                                        // Si la 'y' está precedida por s, l, r, n , c (consonantes alveolares),
                                        // una nueva silaba empieza en la consonante previa, si no, empieza en la 'y'
                                        return;
                                    }
                                    $sCaret->movePosition(1);
                                    return; 
                                }

                                // test ALVEOLARS
                                    // test 'gnpltft + l'
                                    if  ( $sCaret->nextChar()->equals('l') && $sCaret->curChar()->isOneOfTheChars('gkbvpft'))
                                    {
                                        return;  	// gkbvpft + l  : esas tambien empiezan la silaba
                                    }
                                    // test 'gndptlft' + r
                                    if  ($sCaret->nextChar()->equals('r')  &&  $sCaret->curChar()->isOneOfTheChars('gkdtbvpf') )
                                    {
                                        return; 
                                    }
                                        
                                $sCaret->movePosition(1); // just jump over first consonant, to the 2nd consonant
                                                            // as it will be beginning of the next syllable
                                                            // por lo que la c3 (la tercera) es vocal, la c2 es consonante y debe que ser parte de la nueva silaba.
							    // por eso avanzamos el pointer (pos) desde c1 hasta c2 para que marca el principio de la nueva silaba.                                
                                return; 
                            }
        
        
    }
    
    
    /**
     *
     * @param String $word
     * @return Stringar
     */
    public function getSyllables($word) {
        $ret = $this->getSylalblesAndTonic($word);
        return $ret[self::SYLSPLIT_SYLLABLES];
    }
    
    
    /**
     * Splits spanish word into syllables and returns it as a string separated by $separator, eg: bo-li-var
     * @param String $word word to split into syllables
     * @param String $separator : default value '-'
     * @return String
     */
    public function getSyllablesAsString($word, $separator = '-') {
        $syllable_AR = $this->getSyllables($word);
        return implode($separator, $syllable_AR);
    }
    
}




/**
 * Internal class which is used to iterate over the word.
 * Word should be a string of at least one non-spaced character. The class will automatically trim the word? (really? what about shift in idices?
 */
class StringCaret
                    implements IAbstractCharacterSurroundings
{
    // original word, before standartization
    private $original_word = '';
    
    // This is standartized word: spaces trimmed, lowercase, all vowels with diactrics changed towards correct ones (áéóúíü)
    private $word  = '';
    
    private $pointer = -1;
    private $lastIdx = -1;
    private $wordLen = -1;
    
    
    /**
     * 
     * @param String $word 
     *                 what if it is empty string?
     *                 what if it is string of spaces?
     *                  
     */
    public function __construct($word)
    {
        
        //TODO: check for word validity: non empty, with valuable characters
        $this->word = self::standartize($word);
        
        
        
        
        $this->pointer = 0;
        //var_dump($this->word);
        $this->wordLen = strlen($this->word );
        
        if ( $this->wordLen == 0)
        {
            throw new SyllableSplitterException("Error, empty word [$this->word] supplied to the caret");
        }
            
                    //        for ($i =0 ; $i < $this->wordLen ; $i++ )
                    //        {
                    //            $nu = ord($this->word[$i]);
                    //            echo "[$nu]";
                    //        }
                    //        die("fnished");
        $this->lastIdx = $this->wordLen - 1;
    }
    
    
    /*
     * TODO: What happens if it is behind the last char?
     */
    public function isLastChar()
    {
        return  ($this->pointer == $this->lastIdx);
    }
            
    
    public function getCurPosIndex()
    {
        return $this->pointer;
    }
    
    
    /** 
     * Determines if the pointer is pointing to the EOW (EndOfWord) which is
     * if pointer is outside of word
     */
    public function isEOW()
    {
         return ( $this->pointer > $this->lastIdx );
    }
    
    /**
     * Synonym for the isEOW() 
     */
    public function isOutsideWord()
    {
        return $this->isEOW();
    }
    
    
    /**
     * Moves pointer $stepLengh positions ahead. Default value of step is 1, so 
     * it moves pointer one position ahead.
     * @param Integer $stepLength 
     */
    public function movePosition($stepLength = 1)
    {
        $this->pointer += $stepLength;
    }
    

   
    /**
     * Returns Character object from the position, if position is invalid - throws exception
     * @param type $pos 
     * @return Character
     * @throws SomeException
     * TODO: change to correct exception, all the getxxxChar() methods will inherit this exception
     */
    public function getCharAtPos($pos)
    {
        if  ( !$this->hasCharAtPos($pos))
        {
               throw new SyllableSplitterException("Error cannot find character at position: [$pos] for word [$this->word]");
        }
        return new Character($this, $this->word[$pos], $pos);
    }
    
    /**
     * Returns previous to current character. In case there's no previous character,
     * (eg. current char is the first char of the word, then it throws exception. 
     * So caller needs to be sure that the previous character exists.
     * @return Character
     * @throws SomeException
     * TODO: need to change name of the exception
     */
    public function prevChar()
    {
        // Exception is bubbled
        return $this->getCharAtPos($this->pointer -1);
    }
    
    
    /**
     * Returns current character 
     * @return Character
     * @throws SomeException in case pointing at the EOW
     */
    public function curChar()
    {
        // Execption is bubbled
        return $this->getCharAtPos($this->pointer);
    }
    
    
    
    /** 
     * Returns next character after current. The caller must be sure that that character exists.
     * @return Character
     * @throws SomeException in case there's no next char 
     */
    public function nextChar()
    {
        return $this->getCharAtPos($this->pointer + 1);
    }
    
    
    
    /**
     * Returns character 3 (from 123) if the current is pointing at '1'. 
     * The caller must be sure that the third character exists, otherwise exception will be thrown.
     * 
     * @return Character
     * @throws SomeException
     * TODO:  change exception name to the proper one
     */
    public function thirdChar()
    {
        return $this->getCharAtPos($this->pointer + 2);
    }
    
    
    /**
     * 
     * @param String $word 
     * @return String - word standartized so that we can work with that. (all diactrics corrected, spaces trimmed to the correct diactrics, all lower cased etc)
     */
    public static function standartize($word)
    {
        // TODO: add standartization of other characters - tildas, etc
        $word = trim($word);
        
        $word = strtolower($word);
        
        $len = strlen($word);
        // here I am concerned with how PHP is handling the different encodings.
        // it was said that strlen() is returning numnber of bytes, not of characters.
        // the code below may fail in this case. 
        //echo "length of word [$word] is [$len]<br>".PHP_EOL;
        $allowed_chars = 'aábcdeéfghiíjklmnñoópqrstuúüvwxyz';
        for ( $i = 0 ; $i < $len ; $i++)
        {
            
            $foundPos = strstr($allowed_chars, $word[$i]);
            
            if ( $foundPos === false )
            {
                // found some char which is not allowed
                $ch = $word[$i];
                $ord = ord($ch);
                throw new SyllableSplitterException("Error, in word [$word] 
                            found at position [$i] unsupported character. 
                            [$ch] ord($ord). Only characters [$allowed_chars] are allowed.");
            }
        }
        
        return $word;
        
    }


    public function hasPrevChar() {
        return ( $this->pointer > 0 );
    }

    public function isFirstChar() {
        return  (! $this->hasPrevChar() ) ;
    }

    public function hasNextChar() {
        return $this->hasCharAtPos($this->pointer+1);
    }
    
    public function hasThirdChar() {
        return $this->hasCharAtPos($this->pointer+2);
    }
    
    /**
     * checks if $pos is valid position or not 
     */
    public function hasCharAtPos($pos)
    {
        if ( $pos < 0 ) return false;
        if ( $pos > $this->lastIdx  ) return false;
        return true;
    }
    
} // class StringCaret



/**
 * Provides access to character properties (like isConsonant() isVocal() and also prevChar()  nextChar() ,etc. 
 * !!!WARNING:!!! Only works with 'standartized' characters, ie: lowercase, all tildes correct, 
 *                  ? what about NON-standard characters? what if wrong character gets on the way???
 *                  DON'T KNOW YET!
 */
class Character
                implements IAbstractCharacterSurroundings
{
    /**
     * Parent caret
     * @var StringCaret $baseStringObject
     */
    private $sCaret;
    private $char;
    private $pos; // zero based pos of the character in the parent word
    
    /**
     * 
     * @param StringCaret $parentStringObject //maybe just specific interface of the pointer head. not obligatory all the methods of it. w
     *                                            // we'll narrow it down later
     * @param String $someKindOfRepresentationOfTheCharacterProbablyJustSingleCharacter   // thios is just 1char string (Characters should be STANDARTIZED!!!
     *                                                                                    // so only correct tildes and lowercase is accespted.
     */
    public function __construct($parentStringObject, $someKindOfRepresentationOfTheCharacterProbablyJustSingleCharacter, $posOfLetterInWordZeroBased) {
        $this->char = $someKindOfRepresentationOfTheCharacterProbablyJustSingleCharacter;
        $this->sCaret = $parentStringObject;
        $this->pos = $posOfLetterInWordZeroBased;
    }
    
    
    /**
     * Returns the character itself. Or maybe use 'toString()' ??
     * @return type  
     */
    public function getCh()
    {
        return $this->char;
    }
    
    /**
     * I wonder if this works, when we try to compare the class? I don't think so.. wo there should be implicit toString() call?
     * @return Stirng[1]
     */
    public function __toString() {
        return $this->getCh();
    }
    
    /*
     * Compares if the current char is same as $ch. Returns true/false.
     * Case sensitive!
     * @param String[1] character to compare with
     * @return Boolean
     * 
     */
    public function equals($ch)
    {
        return ( $ch == $this->getCh() );
    }
    
    public function notEquals($ch)
    {
        return (! $this->equals($ch));
    }
    
    
    /**
     * Checks if the current Character is one of the characters within the offered string.
     * @param String $charlist_string  (eg: 'kljmhng'  or 'yu' or 'y' 
     *                      TODO: what about empty charlist string??? 
     */
    public function isOneOfTheChars($charlist_string)
    {
        $findPos = strstr($charlist_string, $this->getCh() );
        // returns strict FALSE in case not found.
        return  ( false !== $findPos);
    }
    
    /**
     * Tests if the current character is a consonant. 
     * 'j' is consonant and 'ene' is included.
     * @return Boolean 
     */
    public function isConsonant()
    {
        // here 'j' is consonant.
        return $this->isOneOfTheChars('bcdfghjklmnñpqrstvwxyz');
    }
    
    public function isConsonantExceptBut($stop_list)
    {
        if ( ! $this->isConsonant() )
        {
            // not consonant at all return false
            return false;
        }
        
        // it is consonant, now we have to check if it is consonant outside of stoplist
        $pos = strstr($stop_list, $this->getCh() );
        $isInStopList =  ( $pos !== false);
        if ( $isInStopList )
        {
            return false; // it is still consonant from the stoplist
        }
        else
        {
            return true; // it is consonant Except But consonants from the stoplist
        }
    }

    /**
     * Just synonym to the isVowel() 
     */
    public function isVocal()
    {
        return $this->isVowel();
    }
    
    /**
     * Tests if the current character is a vowel (including áéóíúü)
     * @return Boolean 
     */
    public function isVowel()
    {
        return $this->isOneOfTheChars('aeoiuáéóíúü');
    }
    
    
    
    /**
     *  Includes only: 'íú'
     */
    public function isVocalCerradaTildada()
    {
        return $this->isOneOfTheChars('íú');
    }
    
    /**
     * Includes: regular, tildadas, ü
     * @return Boolean 
     */
    public function isVocalCerrada()
    {
        return $this->isOneOfTheChars('iuíúü');
    }
    
    /** 
     * Includes regular and tildadas
     * @return Boolean 
     */
    public function isVocalAbierta()
    {
        return $this->isOneOfTheChars('aeoáéó');
    }
    
    /**
     * Includes 'iuü' but I am not sure if ü should be there.
     * @return Boolean
     */
    public function isVocalCerradaSinTilde()
    {
        return $this->isOneOfTheChars('iuü');
    }
    
    
    
    
    
    /**
     *  Returns TRUE if the substring starts at the current character. 
     *  The caller should be sure that there's enough characters in the base string
     *  so that comparison with sub-string can happen. Otherwise exception is thrown (if we go out of bounds)
     *  @returns Boolean 
     *  @throws SomeException
     *  TODO: change exception name
     * 
     */
    public function pointsAt($substring)
    {
        
         $substring = trim($substring);
         if ( $substring == '' ) throw new SyllableSplitterException("Error, substring parameter for the pointsAt() method cannot be empty string");
        
         // i do not do the 'string' comparison here because there's no way to compare with the string (unless we get it from the Caret, but
         // that would break abstraction concept (the fact that caret doesn't know anyting about the contents)
         // Hovewer this is confusing as well. So I just implement this method in Character::pointsAt() (because this is where caller is expecting it to be, and basta)
        
        
        // first check if we can theoretically match the substring.
        // check symbol by symbol
        $slen = strlen($substring);
        $curCharIndex = $this->pos;
        for ($i = 0 ; $i < $slen ; $i++)
        {
            $ch = $substring[$i];
            if  ( $this->sCaret->getCharAtPos($curCharIndex + $i)->equals($ch) )
            {
                // everything is good, we continue matching
            }
            else
            {
                // hey we met the mismatch. That's the end - substring doesn't match
                return false;
            }
        }
        return true;
    }
    
    
    /**
     * Checks if with current character we can start any of the elements of the thing.
     * @param type $stringar 
     */
    public function pointsAtOneOf($stringar)
    {
        foreach ($stringar as $substring)
        {
            if ( $this->pointsAt($substring))
            {
                return true;
            }
        }
        return false;
    }
    
    public function hasNextChar() {
          return $this->sCaret->hasCharAtPos($this->pos + 1);
    }

    public function hasPrevChar() {
         return $this->sCaret->hasCharAtPos($this->pos - 1);
    }

    public function hasThirdChar() {
        return $this->sCaret->hasCharAtPos($this->pos + 2);
    }

    public function isFirstChar() {
        return  (! $this->hasPrevChar() );
    }

    public function isLastChar() {
        return  (!$this->hasNextChar() );
    }

    public function nextChar() {
         return $this->sCaret->getCharAtPos($this->pos + 1);
    }

    public function prevChar() {
        return $this->sCaret->getCharAtPos($this->pos - 1);
    }

    public function thirdChar() {
        return $this->sCaret->getCharAtPos($this->pos + 2);
    }
     
}


interface IAbstractCharacterSurroundings
{
    function isLastChar();
    
    function isFirstChar();
    
    function prevChar();
    function nextChar();
    
    function thirdChar();
    
    function hasThirdChar();
    function hasNextChar();
    function hasPrevChar();
    
}

?>