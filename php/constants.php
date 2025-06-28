<?php
define('SEATREG_PLUGIN_FOLDER_DIR', plugin_dir_path( dirname( __FILE__ ) ));
define('SEATREG_PLUGIN_FOLDER_URL', plugin_dir_url( dirname( __FILE__ ) ));
define('SEATREG_HOME_PAGE', admin_url('/admin.php?page=seatreg-welcome'));
define('SEATREG_SETTINGS_PAGE', admin_url('/admin.php?page=seatreg-options'));
define('SEATREG_PAGE_ID', 'seatreg');

// DB
define('SEATREG_DB_VERSION', '1.46');

// Validation
define('SEATREG_MANAGER_ALLOWED_ORDER', array('id', 'date', 'name', 'room', 'nr', 'payment-status'));
define('SEATREG_REGISTRATION_NAME_MAX_LENGTH', 255);
define('SEATREG_REGISTRATION_SEARCH_MAX_LENGTH', 60);
define('SEATREG_CUSTOM_FIELD_TYPES', array('text', 'check', 'sel'));
define('SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH', 100);
define('SEATREG_DEFAULT_INPUT_MAX_LENGHT', 100);
define('SEATREG_CUSTOM_PAYMENT_DESCRIPTION', '/^[\p{L}\p{N}+\s.:\/]+$/u');

// Payments
define('SEATREG_PAYMENT_PROCESSING', 'processing');
define('SEATREG_PAYMENT_COMPLETED', 'completed');
define('SEATREG_PAYMENT_REVERSED', 'reversed');
define('SEATREG_PAYMENT_REFUNDED', 'refunded');
define('SEATREG_PAYMENT_ERROR', 'error');
define('SEATREG_PAYMENT_NONE', 'none');
define('SEATREG_PAYMENT_DEPOSIT_PAYED', 'deposit_payed');
define('SEATREG_PAYMENT_LOG_ERROR', 'error');
define('SEATREG_PAYMENT_LOG_OK', 'ok');
define('SEATREG_PAYMENT_LOG_INFO', 'info');
define('SEATREG_PAYMENT_VALIDATION_FAILED', 'validation_failure');
define('SEATREG_PAYMENT_CALLBACK_URL', get_site_url()); //For live use get_site_url(). For local testing use ngrok URL

// PayPal
define('SEATREG_PAYPAL_FORM_ACTION', "https://www.paypal.com/cgi-bin/webscr");
define('SEATREG_PAYPAL_FORM_ACTION_SANDBOX', "https://www.sandbox.paypal.com/cgi-bin/webscr");
define('SEATREG_PAYPAL_IPN', "https://ipnpb.paypal.com/cgi-bin/webscr");
define('SEATREG_PAYPAL_IPN_SANDBOX', "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr"); //https://ipnpb.sandbox.paypal.com/cgi-bin/webscr
define('SEATREG_PAYPAL_NOTIFY_URL', SEATREG_PAYMENT_CALLBACK_URL  . '?seatreg=paypal-ipn');
define('SEATREG_PAYPAL_RETURN_URL', SEATREG_PAYMENT_CALLBACK_URL  . '?seatreg=payment-return');
define('SEATREG_PAYPAL_CANCEL_URL', SEATREG_PAYMENT_CALLBACK_URL  . '?seatreg=booking-status');

// Stripe
define('SEATREG_STRIPE_WEBHOOK_DESCRIPTION', 'WordPress SeatReg plugin webhook');
define('SEATREG_STRIPE_WEBHOOK_CALLBACK_URL', SEATREG_PAYMENT_CALLBACK_URL . '?seatreg=stripe-webhook-callback');
define('SEATREG_STRIPE_WEBHOOK_SUCCESS_URL', SEATREG_PAYMENT_CALLBACK_URL . '?seatreg=payment-return');
define('SEATREG_STRIPE_WEBHOOK_CANCEL_URL', SEATREG_PAYMENT_CALLBACK_URL . '?seatreg=booking-status');
define('SEATREG_STRIPE_API_VERSION', '2020-08-27');
define('SEATREG_STRIPE_ZERO_DECIMAL_CURRENCIES', array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'));

// Status
define('SEATREG_BOOKING_DEFAULT', 0);
define('SEATREG_BOOKING_PENDING', 1);
define('SEATREG_BOOKING_APPROVED', 2);

// Directory
$up_dir = wp_upload_dir();
define('SEATREG_TEMP_FOLDER_DIR', $up_dir['basedir'].'/seatreg');
define('SEATREG_TEMP_FOLDER_URL', $up_dir['baseurl'].'/seatreg');

// Email template keywords
define('SEATREG_TEMPLATE_STATUS_LINK', '[status-link]');
define('SEATREG_TEMPLATE_EMAIL_VERIFICATION_LINK', '[verification-link]');
define('SEATREG_TEMPLATE_BOOKING_TABLE', '[booking-table]');
define('SEATREG_TEMPLATE_PAYMENT_TABLE', '[payment-table]');
define('SEATREG_TEMPLATE_BOOKING_ID', '[booking-id]');
define('SEATREG_TEMPLATE_BOOKING_APPROVED_EMAIL_CUSTOM_TEXT', '[custom-approved-email-text]');

// Time related
define('CALENDAR_DATE_FORMAT', 'Y-m-d');
define('CALENDAR_DATE_PICKER_REGEX', '/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/');

// SeatReg actions
define('SEATREG_ACTION_BOOKING_SUBMITTED', 'seatreg_action_booking_submitted');
define('SEATREG_ACTION_BOOKING_MANUALLY_ADDED', 'seatreg_action_booking_manually_added');
define('SEATREG_ACTION_BOOKING_PENDING', 'seatreg_action_booking_pending');
define('SEATREG_ACTION_BOOKING_PENDING_VIA_MANAGER', 'seatreg_action_booking_pending_via_manager');
define('SEATREG_ACTION_BOOKING_APPROVED', 'seatreg_action_booking_approved');
define('SEATREG_ACTION_BOOKING_APPROVED_VIA_MANAGER', 'seatreg_action_booking_approved_via_manager');
define('SEATREG_ACTION_BOOKING_REMOVED', 'seatreg_action_booking_removed');

// API
define('SEATREG_API_OK_MESSAGE', 'ok');

//Images
define('SEATREG_BACKGROUND_IMAGE_MAX_SIZE', 2120000);

// Capabilities
define('SEATREG_TRIGGER_SIDE_EFFECT', '1');
define('SEATREG_MANAGE_BOOKINGS_CAPABILITY', 'seatreg_manage_bookings');
define('SEATREG_MANAGE_EVENTS_CAPABILITY', 'seatreg_manage_events');

//.htaccess
define('SEATREG_MARKER', 'SeatReg WordPress plugin');

// CSV
define('SEATREG_CSV_COL_FIRST_NAME', 0);
define('SEATREG_CSV_COL_LAST_NAME', 1);
define('SEATREG_CSV_COL_EMAIL', 2);
define('SEATREG_CSV_COL_SEAT_ID', 3);
define('SEATREG_CSV_COL_SEAT_NR', 4);
define('SEATREG_CSV_COL_ROOM_UUID', 5);
define('SEATREG_CSV_COL_BOOKING_DATE', 6);
define('SEATREG_CSV_COL_BOOKING_CONFIRM_DATE', 7);
define('SEATREG_CSV_COL_CUSTOM_FIELD_DATA', 8);
define('SEATREG_CSV_COL_STATUS', 9);
define('SEATREG_CSV_COL_BOOKING_ID', 10);
define('SEATREG_CSV_COL_BOOKER_EMAIL', 11);
define('SEATREG_CSV_COL_MULTI_PRICE_SELECTION', 12);
define('SEATREG_CSV_COL_LOGGED_IN_USER_ID', 13);

// Currency
define('SEATREG_VALID_CURRENCY_CODES', array(
    'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
    'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BOV',
    'BRL', 'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHE', 'CHF',
    'CHW', 'CLF', 'CLP', 'CNY', 'COP', 'COU', 'CRC', 'CUC', 'CUP', 'CVE',
    'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'EUR', 'FJD',
    'FKP', 'FOK', 'GBP', 'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ',
    'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR',
    'IQD', 'IRR', 'ISK', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KID',
    'KMF', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL',
    'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRU', 'MUR',
    'MVR', 'MWK', 'MXN', 'MXV', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK',
    'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG',
    'QAR', 'RON', 'RSD', 'CNY', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SDG',
    'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'SSP', 'STN', 'SYP', 'SZL',
    'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TVD', 'TWD', 'TZS',
    'UAH', 'UGX', 'USD', 'UYI', 'UYU', 'UYW', 'UZS', 'VES', 'VND', 'VUV',
    'WST', 'XAF', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW', 'ZWL'
));