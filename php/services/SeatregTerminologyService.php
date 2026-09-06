<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregTerminologyService {
    const SINGULAR = 'singular';
    const PLURAL = 'plural';
    //Also what the translation plugin's string names start with
    const ROOM = 'Room';
    const SEAT = 'Seat';

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
        return self::resolveNouns(
            $options,
            'room_noun_singular',
            'room_noun_plural',
            __('room', 'seatreg'),
            __('rooms', 'seatreg'),
            self::ROOM,
            SEATREG_FILTER_ROOM_NOUNS
        );
    }

    /**
     *
     * Return the noun a registration uses for a seat in all the forms the UI needs.
     * Without a renamed noun the using_seats setting picks between seat and the more generic place.
     *
     * @param object|null $options registration options row, or any row joined with it
     *
     * @return object
     *
     */
    public static function getSeatNouns($options = null) {
        //Absent column means a row that did not select it, and the setting defaults to seats
        $usingSeats = !isset($options->using_seats) || (string) $options->using_seats !== '0';

        return self::resolveNouns(
            $options,
            'seat_noun_singular',
            'seat_noun_plural',
            $usingSeats ? __('seat', 'seatreg') : __('place', 'seatreg'),
            $usingSeats ? __('seats', 'seatreg') : __('places', 'seatreg'),
            self::SEAT,
            SEATREG_FILTER_SEAT_NOUNS
        );
    }

    /**
     *
     * Names the noun after the registration code, never the registration name, so renaming a
     * registration cannot orphan its translation.
     *
     * @param string $kind self::ROOM or self::SEAT
     *
     */
    public static function nounStringName($kind, $registrationCode, $form) {
        return $kind . ' noun ' . ($form === self::PLURAL ? 'plural' : 'singular') . ' (' . $registrationCode . ')';
    }

    /**
     *
     * @param object|null $options the row the nouns are read from
     * @param string $singularColumn column holding the renamed singular
     * @param string $pluralColumn column holding the renamed plural
     * @param string $defaultSingular word to use when the registration did not rename it
     * @param string $defaultPlural
     * @param string $kind self::ROOM or self::SEAT
     * @param string $filter filter run over the resolved nouns
     *
     * @return object
     *
     */
    private static function resolveNouns($options, $singularColumn, $pluralColumn, $defaultSingular, $defaultPlural, $kind, $filter) {
        $singular = isset($options->$singularColumn) ? trim((string) $options->$singularColumn) : '';
        $plural = isset($options->$pluralColumn) ? trim((string) $options->$pluralColumn) : '';
        $registrationCode = isset($options->registration_code) && is_string($options->registration_code)
            ? $options->registration_code
            : null;

        //The defaults are translated from the language files already. Sending them through the
        //string translation as well would let a translator there shadow that.
        if( $singular === '' ) {
            $singular = $defaultSingular;
        }else {
            $singular = self::translated($singular, $registrationCode, self::nounStringName($kind, $registrationCode, self::SINGULAR));
        }

        if( $plural === '' ) {
            $plural = $defaultPlural;
        }else {
            $plural = self::translated($plural, $registrationCode, self::nounStringName($kind, $registrationCode, self::PLURAL));
        }

        $nouns = new stdClass();
        $nouns->singular = $singular;
        $nouns->plural = $plural;

        /**
         * Filters the word a registration uses for a room or a seat, after any string translation.
         *
         * @param object $nouns singular and plural. The capitalized forms are derived from what is
         *                      returned, so only these two need setting.
         * @param string|null $registrationCode null where the nouns are the defaults
         * @param object|null $options the row the nouns were resolved from
         */
        $filtered = apply_filters( $filter, $nouns, $registrationCode, $options );

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
     * The screens ask for the noun over and over, a booking PDF once per booking, so the lookup
     * is answered from memory after the first time. The string name carries both which noun it is
     * and which registration, so a room and a seat renamed to the same word cannot share an entry.
     *
     */
    private static function translated($value, $registrationCode, $stringName) {
        $key = $stringName . '|' . $value . '|' . determine_locale();

        if( !isset(self::$translatedNouns[$key]) ) {
            self::$translatedNouns[$key] = self::translatedNoun($value, $registrationCode, $stringName);
        }

        return self::$translatedNouns[$key];
    }

    private static function translatedNoun($value, $registrationCode, $stringName) {
        if( $registrationCode === null ) {
            return $value;
        }

        $translated = trim( (string) SeatregStringTranslationService::translate($value, $stringName) );

        //A translator is not always an administrator, so a translation has to obey the rule the admin's own word obeys
        if( $translated === '' || !SeatregDataValidation::validateNoun($translated)->valid ) {
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
