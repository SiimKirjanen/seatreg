<?php
    function seatreg_shortcode( $atts ){

        $atts = shortcode_atts( array(
            'code'          => '',
            'height'        => '',
            'mobile_height' => '',
            'mobile_max_width'    => '720', // default mobile breakpoint
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
        
        $seatregRegistrationUrl = esc_url(SeatregLinksService::getRegistrationURL() . "?seatreg=registration&c=". $atts['code']);
        $height = (int) esc_attr($atts['height']);
        $mobileHeight = (int) esc_attr($atts['mobile_height']);
        $breakpoint = (int) esc_attr($atts['mobile_max_width']);
        $pageId = esc_attr(SEATREG_PAGE_ID);
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

        return "<iframe id='{$iframeId}' src='{$seatregRegistrationUrl}&page_id={$pageId}'></iframe>";
    }
    add_shortcode( 'seatreg', 'seatreg_shortcode' );