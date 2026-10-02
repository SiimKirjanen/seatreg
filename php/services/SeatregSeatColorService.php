<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregSeatColorService {

    /**
     * The status colors picked in the layout builder. A status without one is null.
     *
     * @param string|null $layout Registration layout JSON
     * @return array Color keyed by pending, approved, selected and locked
     */
    public static function getColors( $layout ) {
        $seatColors = json_decode( $layout ?? '' )->global->seatColors ?? null;
        $colors     = array();

        foreach ( array( 'pending', 'approved', 'selected', 'locked' ) as $status ) {
            $colors[ $status ] = is_string( $seatColors->$status ?? null ) ? sanitize_hex_color( $seatColors->$status ) : null;
        }

        return $colors;
    }

    /**
     * Registration page CSS for the seat statuses that have a color picked. A status without one keeps
     * the seat color from the layout and its status dot.
     *
     * @param array $colors From getColors()
     * @return string CSS
     */
    public static function getStyles( $colors ) {
        $pending  = $colors['pending'];
        $approved = $colors['approved'];
        $selected = $colors['selected'];
        $locked   = $colors['locked'];
        $css      = '';

        if ( $pending ) {
            $css .= self::fill( '.box[data-status=bron]', $pending ) . '.bron-sign{display:none;}' . self::swatch( '.bron-legend', $pending );
        }

        if ( $approved ) {
            $css .= self::fill( '.box[data-status=tak]', $approved ) . '.taken-sign{display:none;}' . self::swatch( '.tak-legend', $approved );
        }

        if ( $selected ) {
            $css .= self::fill( '.box[data-selectedbox=true]', $selected ) . '.box[data-selectedbox=true]{--animationColor:' . $selected . ' !important;}';
        }

        if ( $locked ) {
            $css .= self::fill( '.box[data-seat][data-lock=true]:not([data-status])', $locked ) . self::swatch( '.locked-legend', $locked );
        }

        return $css;
    }

    /* The layout paints every seat with an inline style, so the fill has to be important to win over it. */
    private static function fill( $selector, $color ) {
        return $selector . '{background-color:' . $color . ' !important;color:' . self::readableTextColor( $color ) . ';}';
    }

    private static function swatch( $selector, $color ) {
        return $selector . '{background-color:' . $color . ';border-radius:0;}';
    }

    private static function readableTextColor( $color ) {
        $hex = ltrim( $color, '#' );

        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $channels = array_map( function( $channel ) {
            $value = hexdec( $channel ) / 255;

            return $value <= 0.03928 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
        }, str_split( $hex, 2 ) );

        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

        return $luminance > 0.179 ? '#000' : '#fff';
    }
}
