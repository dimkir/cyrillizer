<?php

/**
 * PART OF SyllableSplitter v1 class
 * This exception will be used in case some error happens to this class
 * @author Ernesto Guevara
 *
 */
class SyllableException extends Exception
{

}


/**
 * As there will be some situations when NON-critical exception is happening (and
 * the calling class has to be notified of some state (like if the calling class wants to retry
 * the web-request. This exception comes to use.
 * Basically it is to be used in non-fatal situations so that the caller class knows that it
 * may need to make a 2nd attempt to do something
 *
 * @author Ernesto Guevara
 *
 */
class SyllableNonCritical extends SyllableException
{
}


class SyllableFailureToStartSesssion extends SyllableNonCritical
{

}

class SyllableFailureQueryOverNetwork extends SyllableNonCritical
{

}


class SyllableFailureSavingToCache extends SyllableNonCritical
{
	
}

?>