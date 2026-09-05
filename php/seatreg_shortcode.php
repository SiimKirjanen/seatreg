<?php
    if(!defined('ABSPATH')) exit;
    
    function seatreg_shortcode( $atts ){

        $atts = shortcode_atts( array(
            'code'          => '',
            'height'        => '',
            'mobile_height' => '',
            'mobile_max_width'    => '720', // default mobile breakpoint
            'room'          => '', // name of the room the registration opens on
        ), $atts, 'seatreg' );

        if ( empty($atts['code']) || empty($atts['height']) ) {
            return "Missing shortcode attributes";
        }

        if (!SeatregDataValidation::validateRegistrationCode( $atts['code'] )) {
            return "Invalid registration code";
        }

        if (!SeatregDataValidation::validateNumberic( $atts['height'] )) {
            return "Invalid height value";
        }

        if ( !empty($atts['mobile_height']) && !SeatregDataValidation::validateNumberic( $atts['mobile_height'] ) ) {
            return "Invalid mobile height value";
        }

        if ( !SeatregDataValidation::validateNumberic( $atts['mobile_max_width'] ) ) {
            return "Invalid breakpoint value";
        }
        
        $queryArgs = array(
            'seatreg' => 'registration',
            'c' => $atts['code'],
        );

        if ( !empty($atts['room']) ) {
            $registration = SeatregRegistrationRepository::getRegistrationByCode( $atts['code'] );

            if ( !$registration ) {
                return "Registration not found";
            }

            $roomData = SeatregLayoutService::getRoomDataFromLayout( $registration->registration_layout );

            if ( !SeatregLayoutService::findRoomUuidByName( $roomData, $atts['room'] ) ) {
                return "Room not found";
            }

            $queryArgs['room'] = $atts['room'];
        }

        $queryArgs['page_id'] = SEATREG_PAGE_ID;

        //Added as arguments, as the address may already carry the language the page is read in
        $seatregRegistrationUrl = esc_url( add_query_arg( $queryArgs, SeatregLinksService::getRegistrationURLInCurrentLanguage() ) );
        $height = (int) esc_attr($atts['height']);
        $mobileHeight = (int) esc_attr($atts['mobile_height']);
        $breakpoint = (int) esc_attr($atts['mobile_max_width']);
        $iframeId = esc_attr('seatreg-shortcode-' . uniqid());
        $styleHandle = 'seatreg-inline-' . $iframeId;

        $css = "
        #{$iframeId} {
            width: 100%;
            height: {$height}px;
        }
        ";

        if (!empty($mobileHeight)) {
            $css .= "
            @media (max-width: {$breakpoint}px) {
                #{$iframeId} {
                    height: {$mobileHeight}px;
                }
            }
            ";
        }

        wp_register_style($styleHandle, false);
        wp_enqueue_style($styleHandle);
        wp_add_inline_style($styleHandle, $css);

        return "<iframe id='{$iframeId}' src='{$seatregRegistrationUrl}'></iframe>";
    }
    add_shortcode( 'seatreg', 'seatreg_shortcode' );