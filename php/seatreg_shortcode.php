<?php
    function seatreg_shortcode( $atts ){
        $registrations = SeatregRegistrationRepository::getRegistrations();
        $shortcodeAtts = shortcode_atts( array(
            'code' =>  (is_array($registrations) && count($registrations)) ? $registrations[0]->registration_code : null,
            'height' => '700',
        ), $atts );
        $site_url = get_site_url();
        
        return "<iframe style='width:100%;height:". (int)$shortcodeAtts['height'] . 'px' ."' src='". $site_url ."/?seatreg=registration&c=". $shortcodeAtts['code'] ."'></iframe>";
    }
    add_shortcode( 'seatreg', 'seatreg_shortcode' );