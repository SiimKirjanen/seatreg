<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 *
 * Offers the text an admin typed to whichever translation plugin the site runs. Language files
 * cannot reach it, as it is written after the plugin ships.
 *
 * The strings themselves belong to whatever feature owns them: this only names them to the
 * translation plugin and hands the answer back, so what a valid value looks like stays with the
 * feature that knows.
 *
 */
class SeatregStringTranslationService {

    /**
     *
     * Is a translation plugin we know how to talk to installed?
     *
     * @return bool
     *
     */
    public static function isAvailable() {
        return function_exists('pll_register_string') || has_action('wpml_register_single_string');
    }

    /**
     *
     * Offer every translatable string the plugin holds. Polylang keeps its registry only for the
     * length of a request, so its string screen lists what was registered while it rendered.
     * Running on every admin request is what that needs, and it also picks up text saved before
     * this existed without asking the admin to open each registration again.
     *
     */
    public static function registerStrings() {
        if( !self::isAvailable() ) {
            return;
        }

        foreach( SeatregOptionsRepository::getCustomRoomNouns() as $registration ) {
            self::register(
                SeatregTerminologyService::roomNounStringName($registration->registration_code, SeatregTerminologyService::SINGULAR),
                $registration->room_noun_singular
            );
            self::register(
                SeatregTerminologyService::roomNounStringName($registration->registration_code, SeatregTerminologyService::PLURAL),
                $registration->room_noun_plural
            );
        }
    }

    /**
     *
     * The translation of a string in the current language, or the original when there is none.
     * The caller decides whether what comes back is usable.
     *
     * @param string $value the text the admin entered
     * @param string $name what the string is called in the translation plugin
     *
     * @return string
     *
     */
    public static function translate($value, $name) {
        if( function_exists('pll__') ) {
            return (string) pll__($value);
        }

        if( has_filter('wpml_translate_single_string') ) {
            return (string) apply_filters(
                'wpml_translate_single_string',
                $value,
                SEATREG_TRANSLATION_STRING_GROUP,
                $name
            );
        }

        return $value;
    }

    private static function register($name, $value) {
        $value = $value === null ? '' : trim($value);

        if( $value === '' ) {
            return;
        }

        if( function_exists('pll_register_string') ) {
            pll_register_string($name, $value, SEATREG_TRANSLATION_STRING_GROUP, false);
        }

        if( has_action('wpml_register_single_string') ) {
            do_action('wpml_register_single_string', SEATREG_TRANSLATION_STRING_GROUP, $name, $value);
        }
    }
}
