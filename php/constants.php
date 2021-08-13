<?php
define('SEATREG_PLUGIN_FOLDER_DIR', plugin_dir_path( dirname( __FILE__ ) ));
define('SEATREG_PLUGIN_FOLDER_URL', plugin_dir_url( dirname( __FILE__ ) ));

// DB
define('SEATREG_DB_VERSION', '1.2');

// Validation
define('SEATREG_MANAGER_ALLOWED_ORDER', array('id', 'date', 'name', 'room', 'nr'));
define('SEATREG_REGISTRATION_NAME_MAX_LENGTH', 255);
define('SEATREG_REGISTRATION_SEARCH_MAX_LENGTH', 60);
define('SEATREG_CUSTOM_FIELD_TYPES', array('text', 'check', 'sel'));
define('SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH', 50);

// PayPal
define('SEATREG_PAYPAL_FORM_ACTION', "https://www.paypal.com/cgi-bin/webscr");
define('SEATREG_PAYPAL_FORM_ACTION_SANDBOX', "https://www.sandbox.paypal.com/cgi-bin/webscr");