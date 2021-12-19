<?php
define('SEATREG_PLUGIN_FOLDER_DIR', plugin_dir_path( dirname( __FILE__ ) ));
define('SEATREG_PLUGIN_FOLDER_URL', plugin_dir_url( dirname( __FILE__ ) ));

// DB
define('SEATREG_DB_VERSION', '1.6');

// Validation
define('SEATREG_MANAGER_ALLOWED_ORDER', array('id', 'date', 'name', 'room', 'nr'));
define('SEATREG_REGISTRATION_NAME_MAX_LENGTH', 255);
define('SEATREG_REGISTRATION_SEARCH_MAX_LENGTH', 60);
define('SEATREG_CUSTOM_FIELD_TYPES', array('text', 'check', 'sel'));
define('SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH', 50);
define('SEATREG_DEFAULT_INPUT_MAX_LENGHT', 100);

// PayPal
define('SEATREG_PAYPAL_FORM_ACTION', "https://www.paypal.com/cgi-bin/webscr");
define('SEATREG_PAYPAL_FORM_ACTION_SANDBOX', "https://www.sandbox.paypal.com/cgi-bin/webscr");
define('SEATREG_PAYPAL_IPN', "https://ipnpb.paypal.com/cgi-bin/webscr");
define('SEATREG_PAYPAL_IPN_SANDBOX', "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr"); //https://ipnpb.sandbox.paypal.com/cgi-bin/webscr
define('SEATREG_PAYMENT_PROCESSING', 'processing');
define('SEATREG_PAYMENT_COMPLETED', 'completed');
define('SEATREG_PAYMENT_REVERSED', 'reversed');
define('SEATREG_PAYMENT_REFUNDED', 'refunded');
define('SEATREG_PAYMENT_VALIDATION_FAILED', 'validation_failure');
define('SEATREG_PAYMENT_LOG_ERROR', 'error');
define('SEATREG_PAYMENT_LOG_OK', 'ok');
define('SEATREG_PAYMENT_LOG_INFO', 'info');

// Status
define('SEATREG_BOOKING_PENDING', 1);
define('SEATREG_BOOKING_APPROVED', 2);

//Directory
$up_dir = wp_upload_dir();
define('SEATREG_TEMP_FOLDER_DIR', $up_dir['basedir'].'/seatreg');
define('SEATREG_TEMP_FOLDER_URL', $up_dir['baseurl'].'/seatreg');