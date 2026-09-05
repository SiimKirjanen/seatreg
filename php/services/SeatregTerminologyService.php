<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregTerminologyService {

    /**
     *
     * Return the noun a registration uses for a room in all the forms the UI needs.
     * Admins can rename it per registration, so nothing may hardcode the word.
     *
     * @param object|null $options registration options row, or any row joined with it
     *
     * @return object
     *
     */
    public static function getRoomNouns($options = null) {
        $singular = isset($options->room_noun_singular) ? trim((string) $options->room_noun_singular) : '';
        $plural = isset($options->room_noun_plural) ? trim((string) $options->room_noun_plural) : '';

        if( $singular === '' ) {
            $singular = __('room', 'seatreg');
        }

        if( $plural === '' ) {
            $plural = __('rooms', 'seatreg');
        }

        $nouns = new stdClass();
        $nouns->singular = $singular;
        $nouns->plural = $plural;
        $nouns->singularUpper = self::ucfirst($singular);
        $nouns->pluralUpper = self::ucfirst($plural);

        return $nouns;
    }

    /**
     *
     * Uppercase the first character. PHP has no mb_ucfirst and the byte based ucfirst()
     * mangles a noun that starts with a multibyte letter.
     *
     * @param string $text
     *
     * @return string
     *
     */
    public static function ucfirst($text) {
        if( $text === '' ) {
            return '';
        }

        if( function_exists('mb_strtoupper') && function_exists('mb_substr') ) {
            return mb_strtoupper( mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8' ) . mb_substr($text, 1, null, 'UTF-8');
        }

        return ucfirst($text);
    }
}
