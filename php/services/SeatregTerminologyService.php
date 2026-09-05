<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregTerminologyService {
    const SINGULAR = 'singular';
    const PLURAL = 'plural';

    private static $translatedNouns = array();

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
        $registrationCode = isset($options->registration_code) && is_string($options->registration_code)
            ? $options->registration_code
            : null;

        //The defaults are translated from the language files already. Sending them through the
        //string translation as well would let a translator there shadow that.
        if( $singular === '' ) {
            $singular = __('room', 'seatreg');
        }else {
            $singular = self::translated($singular, $registrationCode, self::SINGULAR);
        }

        if( $plural === '' ) {
            $plural = __('rooms', 'seatreg');
        }else {
            $plural = self::translated($plural, $registrationCode, self::PLURAL);
        }

        $nouns = new stdClass();
        $nouns->singular = $singular;
        $nouns->plural = $plural;

        /**
         * Filters the word a registration uses for a room, after any string translation.
         *
         * @param object $nouns singular and plural. The capitalized forms are derived from what is
         *                      returned, so only these two need setting.
         * @param string|null $registrationCode null where the nouns are the defaults
         * @param object|null $options the row the nouns were resolved from
         */
        $filtered = apply_filters( SEATREG_FILTER_ROOM_NOUNS, $nouns, $registrationCode, $options );

        $result = new stdClass();
        $result->singular = self::nounOr($filtered, 'singular', $singular);
        $result->plural = self::nounOr($filtered, 'plural', $plural);
        //Derived last, as a translated or filtered word capitalizes by its own rules
        $result->singularUpper = self::ucfirst($result->singular);
        $result->pluralUpper = self::ucfirst($result->plural);

        return $result;
    }

    /**
     *
     * Names the noun after the registration code, never the registration name, so renaming a
     * registration cannot orphan its translation.
     *
     */
    public static function roomNounStringName($registrationCode, $form) {
        return $form === self::PLURAL
            ? 'Room noun plural (' . $registrationCode . ')'
            : 'Room noun singular (' . $registrationCode . ')';
    }

    /**
     *
     * The screens ask for the noun over and over, a booking PDF once per booking, so the lookup
     * is answered from memory after the first time.
     *
     */
    private static function translated($value, $registrationCode, $form) {
        $key = $form . '|' . $registrationCode . '|' . $value . '|' . determine_locale();

        if( !isset(self::$translatedNouns[$key]) ) {
            self::$translatedNouns[$key] = self::translatedNoun($value, $registrationCode, $form);
        }

        return self::$translatedNouns[$key];
    }

    private static function translatedNoun($value, $registrationCode, $form) {
        if( $registrationCode === null ) {
            return $value;
        }

        $translated = trim( (string) SeatregStringTranslationService::translate($value, self::roomNounStringName($registrationCode, $form)) );

        //A translator is not always an administrator, so a translation has to obey the rule the admin's own word obeys
        if( $translated === '' || !SeatregDataValidation::validateRoomNoun($translated)->valid ) {
            return $value;
        }

        return $translated;
    }

    private static function nounOr($filtered, $form, $fallback) {
        if( !is_object($filtered) || !isset($filtered->$form) || !is_string($filtered->$form) ) {
            return $fallback;
        }

        $noun = trim($filtered->$form);

        return $noun === '' ? $fallback : $noun;
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
