<?php
    function seatreg_shortcode( $atts ){
        $registrations = seatreg_get_registrations();
        $a = shortcode_atts( array(
            'code' =>  (is_array($registrations) && count($registrations)) ? $registrations[0]->registration_code : null,
        ), $atts );
        $site_url = get_site_url();
        
        return "<iframe style='width:100%;height:700px' src='". $site_url ."/?seatreg=registration&c=". $a['code'] ."'></iframe>";
    }
    add_shortcode( 'seatreg', 'seatreg_shortcode' );