<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregLinksService {
    public static function getRegistrationURL() {
        return get_site_url() . '/seatreg';
    }

    /**
     *
     * The registration address in the language the visitor is reading, so a registration
     * embedded in a page opens in that page's language. How a language is written into an
     * address is the translation plugin's own business, so it is left to write it.
     *
     * @return string
     *
     */
    public static function getRegistrationURLInCurrentLanguage() {
        $url = self::getRegistrationURL();

        if( function_exists('PLL') && isset(PLL()->links_model) && isset(PLL()->curlang) ) {
            return PLL()->links_model->add_language_to_link($url, PLL()->curlang);
        }

        if( defined('ICL_LANGUAGE_CODE') ) {
            return apply_filters('wpml_permalink', $url, ICL_LANGUAGE_CODE);
        }

        return $url;
    }
}