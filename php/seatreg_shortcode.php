<?php
    function seatreg_shortcode( $atts ){
        if ( !isset($atts['code']) || !isset($atts['height']) ) {
            return "Invalid shortcode attributes";
        }

        if (!SeatregDataValidation::validateRegistrationCode( $atts['code'] )) {
            return "Invalid registration code";
        }

        if (!SeatregDataValidation::validateNumberic( $atts['height'] )) {
            return "Invalid height value";
        }
        
        $seatregRegistrationUrl = esc_url(SeatregLinksService::getRegistrationURL() . "?seatreg=registration&c=". $atts['code']);
        $height = esc_attr($atts['height']);
        $pageId = esc_attr(SEATREG_PAGE_ID);
        
        return "<iframe style='width:100%;height:". $height . 'px' ."' src='". $seatregRegistrationUrl . '&page_id=' . $pageId ."'></iframe>";
    }
    add_shortcode( 'seatreg', 'seatreg_shortcode' );