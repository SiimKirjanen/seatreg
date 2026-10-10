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
    //The gettext context of a sentence variant, after any context the plain sentence already has
    const VARIANT_CONTEXT_REGEX = '/^(?:(.*); )?(?:seat|room) word is (?:masculine|feminine|neuter)$/';

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
            'room_noun_gender',
            __('room', 'seatreg'),
            __('rooms', 'seatreg'),
            /* translators: If the words around "room" change with its grammatical gender in your language, write masculine, feminine or neuter here in English, for the word you translated "room" to. Otherwise leave it as none. */
            _x('none', 'grammatical gender of the word room', 'seatreg'),
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
            'seat_noun_gender',
            $usingSeats ? __('seat', 'seatreg') : __('place', 'seatreg'),
            $usingSeats ? __('seats', 'seatreg') : __('places', 'seatreg'),
            $usingSeats
                /* translators: If the words around "seat" change with its grammatical gender in your language, write masculine, feminine or neuter here in English, for the word you translated "seat" to. Otherwise leave it as none. */
                ? _x('none', 'grammatical gender of the word seat', 'seatreg')
                /* translators: If the words around "place" change with its grammatical gender in your language, write masculine, feminine or neuter here in English, for the word you translated "place" to. Otherwise leave it as none. */
                : _x('none', 'grammatical gender of the word place', 'seatreg'),
            self::SEAT,
            SEATREG_FILTER_SEAT_NOUNS
        );
    }

    /**
     *
     * Pick the translation of a sentence whose words agree with the noun's grammatical gender.
     * The variants are the same English sentence under a gendered context, so a language without
     * grammatical gender translates only the plain one.
     *
     * @param object $nouns as returned by getRoomNouns() or getSeatNouns()
     *
     * @return string
     *
     */
    public static function agree($nouns, $none, $masculine, $feminine, $neuter) {
        return self::pick($nouns, self::variants($none, $masculine, $feminine, $neuter));
    }

    /**
     *
     * @param object $nouns as returned by getRoomNouns() or getSeatNouns()
     * @param array $variants as returned by variants()
     *
     * @return string
     *
     */
    public static function pick($nouns, $variants) {
        return $nouns->gender !== '' && isset($variants[$nouns->gender]) ? $variants[$nouns->gender] : $variants['none'];
    }

    /**
     *
     * The translations of a sentence for each gender, for a screen that only learns the noun later.
     * seatregAgree() picks from them.
     *
     * @return array
     *
     */
    public static function variants($none, $masculine, $feminine, $neuter) {
        return array(
            'none' => $none,
            'masculine' => $masculine,
            'feminine' => $feminine,
            'neuter' => $neuter
        );
    }

    /**
     *
     * A language that translated the plain sentence but not yet its gendered variant would show
     * that variant in English, so it gets the plain translation instead.
     *
     * Hooked to gettext_with_context.
     *
     */
    public static function fallBackToPlainTranslation($translation, $text, $context, $domain) {
        if( $domain !== 'seatreg' || $translation !== $text || preg_match(self::VARIANT_CONTEXT_REGEX, $context, $matches) !== 1 ) {
            return $translation;
        }

        if( isset($matches[1]) && $matches[1] !== '' ) {
            return translate_with_gettext_context($text, $matches[1], 'seatreg');
        }

        return translate($text, 'seatreg');
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
     * @param string $genderColumn column holding the grammatical gender of the renamed word
     * @param string $defaultSingular word to use when the registration did not rename it
     * @param string $defaultPlural
     * @param string $defaultGender grammatical gender of the default word, as its translator gave it
     * @param string $kind self::ROOM or self::SEAT
     * @param string $filter filter run over the resolved nouns
     *
     * @return object
     *
     */
    private static function resolveNouns($options, $singularColumn, $pluralColumn, $genderColumn, $defaultSingular, $defaultPlural, $defaultGender, $kind, $filter) {
        $singular = isset($options->$singularColumn) ? trim((string) $options->$singularColumn) : '';
        $plural = isset($options->$pluralColumn) ? trim((string) $options->$pluralColumn) : '';
        $registrationCode = isset($options->registration_code) && is_string($options->registration_code)
            ? $options->registration_code
            : null;

        //The defaults are translated from the language files already. Sending them through the
        //string translation as well would let a translator there shadow that.
        if( $singular === '' ) {
            $singular = $defaultSingular;
            $gender = self::genderOr($defaultGender, '');
        }else {
            $renamed = $singular;
            $singular = self::translated($singular, $registrationCode, self::nounStringName($kind, $registrationCode, self::SINGULAR));
            //The admin gave the gender of their own word, which says nothing about a translation of it
            $gender = $singular === $renamed && isset($options->$genderColumn) ? self::genderOr($options->$genderColumn, '') : '';
        }

        if( $plural === '' ) {
            $plural = $defaultPlural;
        }else {
            $plural = self::translated($plural, $registrationCode, self::nounStringName($kind, $registrationCode, self::PLURAL));
        }

        $nouns = new stdClass();
        $nouns->singular = $singular;
        $nouns->plural = $plural;
        $nouns->gender = $gender;

        /**
         * Filters the word a registration uses for a room or a seat, after any string translation.
         *
         * @param object $nouns singular, plural and gender: masculine, feminine, neuter, or an empty
         *                      string for a word that does not change the words around it. The
         *                      capitalized forms are derived from what is returned, so only these
         *                      need setting.
         * @param string|null $registrationCode null where the nouns are the defaults
         * @param object|null $options the row the nouns were resolved from
         */
        $filtered = apply_filters( $filter, $nouns, $registrationCode, $options );

        $result = new stdClass();
        $result->singular = self::nounOr($filtered, 'singular', $singular);
        $result->plural = self::nounOr($filtered, 'plural', $plural);
        $result->gender = is_object($filtered) && isset($filtered->gender) ? self::genderOr($filtered->gender, $gender) : $gender;
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

    //A translator writes the default word's gender by hand, so anything but a known one means none
    private static function genderOr($gender, $fallback) {
        if( !is_string($gender) ) {
            return $fallback;
        }

        $gender = strtolower(trim($gender));

        return $gender === '' || in_array($gender, SEATREG_NOUN_GENDERS, true) ? $gender : $fallback;
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
