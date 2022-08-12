<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregRegistrationService {
    /**
     *
     * Return generated registration code
     *
    */
    public static function generateRegistrationCode() {
        return substr(md5( microtime() ), 0, 10);
    }

    /**
     *
     * Return seat price from registration layout
     *
    */
    public static function getSeatPriceFromLayout($seatID, $roomUUID, $roomsData) {
        $price = 0;

        foreach($roomsData as $roomData) {
            if($roomData->room->uuid === $roomUUID) {
                foreach($roomData->boxes as $box) {
                    if($box->id === $seatID) {
                        $price = $box->price;

                        break 2;
                    }
                }
            }
        }

	    return $price;
    }

    /**
     *
     * Return room name from layout
     *
    */
    public static function getRoomNameFromLayout($roomsLayout, $bookingRoomUuid) {
        $roomName = null;

        foreach($roomsLayout as $roomLayout) {
            if($roomLayout->room->uuid === $bookingRoomUuid) {
                $roomName = $roomLayout->room->name;
            }
        }

        return $roomName;
    }

    public static function updateRegistrationLayout($registrationLayout, $registrationCode) {
        global $wpdb;
	    global $seatreg_db_table_names;

        return $wpdb->update(
            "$seatreg_db_table_names->table_seatreg",
            array(
                'registration_layout' => $registrationLayout
            ),
            array(
                'registration_code' => $registrationCode
            ),
            array('%s'),
            array('%s')
        );
    }

    public static function insertRegistration($registrationName, $layout, $generatedCode) {
        global $wpdb;
	    global $seatreg_db_table_names;

        $status = $wpdb->insert(
            $seatreg_db_table_names->table_seatreg,
            array(
                'registration_name' => $registrationName,
                'registration_code' => $generatedCode,
                'registration_create_timestamp' => time(),
                'registration_layout' => $layout
            ),
            '%s'
        );

        return $status === 1 ? true : false;
    }

    public static function insertRegistrationOptions($generatedCode, $registrationData) {
        global $wpdb;
	    global $seatreg_db_table_names;

        $status = $wpdb->insert(
    		$seatreg_db_table_names->table_seatreg_options,
    		array(
                'registration_code' => $generatedCode,
                'registration_start_timestamp' => $registrationData->registration_start_timestamp,
                'registration_end_timestamp' => $registrationData->registration_end_timestamp,
                'seats_at_once' => $registrationData->seats_at_once,
                'gmail_required' => $registrationData->gmail_required,
                'registration_open' => $registrationData->registration_open,
                'use_pending' => $registrationData->use_pending,
                'registration_password' => $registrationData->registration_password,
                'notify_new_bookings' => $registrationData->notify_new_bookings,
                'show_bookings' => $registrationData->show_bookings,
                'payment_text' => $registrationData->payment_text,
                'info' => $registrationData->info,
                'registration_close_reason' => $registrationData->registration_close_reason,
                'custom_fields' => $registrationData->custom_fields,
                'booking_email_confirm' => $registrationData->booking_email_confirm,
                'paypal_payments' => $registrationData->paypal_payments,
                'paypal_business_email' => $registrationData->paypal_business_email,
                'paypal_button_id' => $registrationData->paypal_button_id,
                'paypal_currency_code' => $registrationData->paypal_currency_code,
                'paypal_sandbox_mode' => $registrationData->paypal_sandbox_mode,
                'payment_completed_set_booking_confirmed' => $registrationData->payment_completed_set_booking_confirmed,
                'send_approved_booking_email' => $registrationData->send_approved_booking_email,
                'send_approved_booking_email_qr_code' => $registrationData->send_approved_booking_email_qr_code,
                'email_verification_template' => $registrationData->email_verification_template,
                'pending_booking_email_template' => $registrationData->pending_booking_email_template,
                'approved_booking_email_template' => $registrationData->approved_booking_email_template,
                'payment_completed_set_booking_confirmed_stripe' => $registrationData->payment_completed_set_booking_confirmed_stripe,
                'stripe_api_key' => $registrationData->stripe_api_key
            ),
    		'%s'
    	);

        return $status === 1 ? true : false;
    }

    public static function copyRegistration($registrationCode, $newRegistrationName) {
        global $wpdb;
	    global $seatreg_db_table_names;

        $generatedCode = self::generateRegistrationCode();
        $registrationData = SeatregRegistrationRepository::getRegistrationWithOptionsByCode($registrationCode);
        $insertStatus = self::insertRegistration($newRegistrationName, $registrationData->registration_layout, $generatedCode);

        if( $insertStatus ) {
            $insertStatus = self::insertRegistrationOptions($generatedCode, $registrationData);
            if($insertStatus) {
                wp_redirect( $_POST['_wp_http_referer'] );

                die();
            }else {
                wp_die( esc_html_e('Something went wrong while coping a registration settings', 'seatreg') );
            }
        }else {
            wp_die( esc_html_e('Something went wrong while coping a registration', 'seatreg') );
        }
    }
}