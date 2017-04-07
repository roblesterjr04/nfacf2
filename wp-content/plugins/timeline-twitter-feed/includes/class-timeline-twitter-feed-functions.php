<?php

class Timeline_Twitter_Feed_Functions {

    /** 
     * Better alternative for explode().
     * 
     * Takes care of removing empty values and skips values
     * which are too short. 
     * 
     * @param  string $string    The string to split.
     * @param  string $separator The string to split by.
     * @param  int    $length    The min length of values. 
     * @return array             An array of found values.
     */
    static function str_split( $string, $separator = ',', $length = 1 ) {
        if ( is_array( $string ) ) {
            return $string;
        }

        $string = trim( $string, $separator );
        $parts  = explode( $separator, $string );
        $out    = array();

        foreach( $parts as $part ) {
            $part = trim( $part );
            if ( self::str_length( $part ) > 0 && self::str_length( $part ) >= $length ) {
                $out[] = $part;
            }
        }

        return $out;
    }

    /** 
     * An UTF-8 safe version of strlen()
     * 
     * @param  string $str
     * @return string  
     */
    static function str_length( $str ) {
        return mb_strlen( $str, 'UTF-8' );
    }

}
