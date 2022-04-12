<?php
    function seatreg_shortcode( $atts ){
        $registrations = SeatregRegistrationRepository::getRegistrations();
        $a = shortcode_atts( array(
            'code' =>  (is_array($registrations) && count($registrations)) ? $registrations[0]->registration_code : null,
            'height' => '700px',
        ), $atts );
        $site_url = get_site_url();
        
        return "<iframe style='width:100%;height:". $a['height'] ."' src='". $site_url ."/?seatreg=registration&c=". $a['code'] ."'></iframe>";
    }
    add_shortcode( 'seatreg', 'seatreg_shortcode' );