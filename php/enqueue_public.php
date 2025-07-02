<?php
require_once(SEATREG_PLUGIN_FOLDER_DIR . 'registration/php/reg_functions.php');
require_once(SEATREG_PLUGIN_FOLDER_DIR . 'php/seatreg_strings.php');

//remove queued styles from registration view page
add_action('wp_print_styles', 'seatreg_remove_all_styles', 100);
function seatreg_remove_all_styles() {
	if( seatreg_is_registration_view_page() ) {
		global $wp_styles;
		$allowedToLoad = array('seatreg-registration-style', 'google-open-sans', 'pg-calendar-style', 'alertify-core', 'alertify-default');
    	$wp_styles->queue = $allowedToLoad;
	}
	if( seatreg_is_booking_check_page() ) {
		global $wp_styles;
		$allowedToLoad = array('alertify-core', 'alertify-default');
    	$wp_styles->queue = $allowedToLoad;
	}
	if( seatreg_is_booking_confirm_page() ) {
		global $wp_styles;
		$allowedToLoad = array();
    	$wp_styles->queue = $allowedToLoad;
	}
}

//only allow spesific scripts to load on registration view page
add_action('wp_print_scripts', 'seatreg_remove_all_scripts', 100);
function seatreg_remove_all_scripts() {
	if( seatreg_is_registration_view_page() ) {
		global $wp_scripts;
		$allowedToLoad = array('jquery', 'seatreg-registration', 'date-format', 'jquery-powertip', 'iscroll-zoom', 'modernizr', 'pg-calendar', 'alertify');
		$wp_scripts->queue = $allowedToLoad;
	}
	if( seatreg_is_booking_check_page() ) {
		global $wp_scripts;
		$allowedToLoad = array('jquery', 'alertify', 'seatreg-booking-check');
		$wp_scripts->queue = $allowedToLoad;
	}
	if( seatreg_is_booking_confirm_page() ) {
		global $wp_scripts;
		$allowedToLoad = array();
		$wp_scripts->queue = $allowedToLoad;
	}
}

add_action( 'wp_enqueue_scripts', 'seatreg_public_scripts_and_styles' );
function seatreg_public_scripts_and_styles() {
	if ( seatreg_is_registration_view_page() && !empty($_GET['c']) ) {
		$manifestFileContents = file_get_contents(SEATREG_PLUGIN_FOLDER_DIR . 'rev-manifest.json');
		$manifest = json_decode($manifestFileContents, true);

		wp_enqueue_style('google-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,700', array(), '1.0.0', 'all');
		wp_enqueue_style('seatreg-registration-style', SEATREG_PLUGIN_FOLDER_URL . 'registration/css/' . $manifest['registration.min.css'] , array(), '1.6.0', 'all');
		wp_enqueue_style('pg-calendar-style', SEATREG_PLUGIN_FOLDER_URL . 'js/pg-calendar/dist/css/pignose.calendar.css', array(), '1.4.31', 'all');
		wp_enqueue_style('alertify-core', SEATREG_PLUGIN_FOLDER_URL . 'css/alertify.core.css', array(), '1.0.0', 'all');
		wp_enqueue_style('alertify-default', SEATREG_PLUGIN_FOLDER_URL . 'css/alertify.default.css', array(), '1.0.0', 'all');

		wp_enqueue_script("jquery");
		wp_enqueue_script('modernizr', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/modernizr.custom.89593.min.js' , array(), '2.8.3', false);
		wp_enqueue_script('date-format', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/date.format.js' , array(), '1.0.0', true);
		wp_enqueue_script('iscroll-zoom', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/iscroll-zoom.js' , array(), '5.1.3', true);
		wp_enqueue_script('jquery-powertip', SEATREG_PLUGIN_FOLDER_URL . 'js/jquery.powertip.js' , array(), '1.2.0', true);
		wp_enqueue_script('pg-calendar', SEATREG_PLUGIN_FOLDER_URL . 'js/pg-calendar/dist/js/pignose.calendar.full.min.js' , array('jquery'), '1.4.31', false);
		wp_enqueue_script('seatreg-utils', SEATREG_PLUGIN_FOLDER_URL . 'js/utils.js' , array(), '1.0.0', true);
		wp_enqueue_script('seatreg-registration', SEATREG_PLUGIN_FOLDER_URL . 'registration/js/registration.js' , array('jquery', 'date-format', 'iscroll-zoom', 'jquery-powertip', 'seatreg-utils'), '1.28.0', true);
		wp_enqueue_script('alertify', SEATREG_PLUGIN_FOLDER_URL . 'js/alertify.js', array('jquery'), '1.0.0', true);

		$data = seatreg_get_options_reg($_GET['c']);
		$filterCalendarDate = SeatregCalendarService::getBookingFilteringDateForRegistrationView($data->using_calendar, assignIfNotEmpty($_GET['calendar-date'], null));
		$seatsInfo = json_encode( SeatregLayoutService::getBookingsInfoForLayout($data->registration_layout, $data->registration_code, $filterCalendarDate) );
		$registrationTime = seatreg_registration_time_status( $data->registration_start_timestamp,  $data->registration_end_timestamp );
		$selectedShowRegistrationData = $data->show_bookings_data_in_registration ? explode(',', $data->show_bookings_data_in_registration) : [];
		$registrations = json_encode(SeatregBookingRepository::getBookingsForRegistrationPage($_GET['c'], $selectedShowRegistrationData, $filterCalendarDate));
		$siteLanguage = getSiteLanguage();
		$registrationTimeRestrictions = json_encode( SeatregTimeRepository::getTimeInfoForRegistrationView($data->registration_start_time, $data->registration_end_time) );
		$isLoggedIn = SeatregAuthService::isLoggedIn();

		$inlineScript = 'function showErrorView(title) {';
			$inlineScript .= "jQuery('body').addClass('error-view').html('";
				$inlineScript .= '<div>An error occured</div><img src="' . SEATREG_PLUGIN_FOLDER_URL . 'img/monkey.png" alt="monkey" /><div></div>';
			$inlineScript .= "');";
		$inlineScript .= '}';
		
		$inlineScript .= 'try {';
			$inlineScript .= 'var seatregPluginFolder = "' . SEATREG_PLUGIN_FOLDER_URL . '";';
			$inlineScript .= "var seatregTranslations = jQuery.parseJSON('" .  wp_json_encode( seatreg_generate_registration_strings() ) . "');";
			$inlineScript .= 'var seatLimit = ' . esc_js($data->seats_at_once) . ';';
			$inlineScript .= 'var gmail = ' . esc_js($data->gmail_required) . ';';
			$inlineScript .= 'var NotifyBookerPendingBooking = ' . esc_js($data->notify_booker_pending_booking) . ';';
			$inlineScript .= 'var dataReg = jQuery.parseJSON(' . wp_json_encode(SeatregLayoutService::hideSensitiveData($data->registration_layout)) . ');';
			$inlineScript .= 'var roomsInfo = jQuery.parseJSON(' . wp_json_encode($seatsInfo) . ');';
			$inlineScript .= 'var custF = jQuery.parseJSON(' . wp_json_encode($data->custom_fields) . ');';
			$inlineScript .= 'var regTime = "' . esc_js($registrationTime) . '";';
			$inlineScript .= 'var registrations = jQuery.parseJSON(' . wp_json_encode($registrations) . ');';
			$inlineScript .= 'var ajaxUrl = "'. admin_url('admin-ajax.php') . '";';
			$inlineScript .= 'var emailConfirmRequired = "'. esc_js($data->booking_email_confirm) . '";';
			$inlineScript .= 'var payPalEnabled = "'. esc_js($data->paypal_payments) . '";';
			$inlineScript .= 'var stripeEnabled = "'. esc_js($data->stripe_payments) . '";';
			$inlineScript .= 'var customPaymentEnabled = "'. esc_js($data->custom_payment || SeatregPaymentRepository::hasCustomPayments($data)) . '";';
			$inlineScript .= 'var payPalCurrencyCode = "'. esc_js($data->paypal_currency_code) . '";';
			$inlineScript .= 'var receiptEnabled = "'. esc_js( $data->send_approved_booking_email) . '";';
			$inlineScript .= 'var usingSeats = "'. esc_js( $data->using_seats ) . '";';
			$inlineScript .= 'var usingCalendar = "'. esc_js( $data->using_calendar ) . '";';
			$inlineScript .= 'var calendarDates = "'. esc_js( $data->calendar_dates ) . '";';
			$inlineScript .= 'var activeCalendarDate = "'. esc_js($filterCalendarDate) . '";';
			$inlineScript .= 'var siteLanguage = "'. esc_js($siteLanguage) . '";';
			$inlineScript .= 'var controlledScroll = "'. esc_js($data->controlled_scroll) . '";';
			$inlineScript .= 'var customFooterText = "'. esc_js($data->custom_footer_text) . '";';
			$inlineScript .= 'var registrationTimeRestrictions = jQuery.parseJSON(' . wp_json_encode($registrationTimeRestrictions) . ');';
			$inlineScript .= 'var bookingRedirectToStatusPage = "'. esc_js($data->booking_redirect_status_page) . '";';
			$inlineScript .= 'var requireWPLogin = "'. esc_js($data->require_wp_login) . '";';
			$inlineScript .= 'var isLoggedIn = "'. esc_js($isLoggedIn) . '";';
			$inlineScript .= 'var onePersonCheckout = "'. esc_js($data->one_person_checkout) . '";';
			$inlineScript .= 'var requireName = "' . esc_js($data->require_name ? '1' : '0') . '";';
			$inlineScript .= 'var automaticBookingConfirmDialog = "' . esc_js($data->automatic_booking_confirm_dialog ? '1' : '0') . '";';
			$inlineScript .= '} catch(err) {';
				$inlineScript .= "showErrorView('Data initialization failed');";
				$inlineScript .= "console.log(err);";
			$inlineScript .= 
		$inlineScript .= '}';

		if( $data->custom_styles ) {
			add_action('wp_head', function() use ($data) {
				seatreg_add_custom_styles($data->custom_styles);
			}, 100);
		}

		wp_add_inline_script('seatreg-registration', $inlineScript, 'before');
		wp_localize_script('seatreg-registration', 'WP_Seatreg', array(
			'SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH' => SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH,
			'plugin_dir_url' => plugin_dir_url( dirname( __FILE__ ) ),
			'uploads_url' => SEATREG_TEMP_FOLDER_URL,
		));
	}

	if( seatreg_is_booking_check_page() && !empty($_GET['registration']) && !empty($_GET['id']) ) {
		$bookingData = SeatregBookingRepository::getDataRelatedToBooking( $_GET['id'] );

		wp_enqueue_style('alertify-core', plugins_url('css/alertify.core.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_style('alertify-default', plugins_url('css/alertify.default.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_script("jquery");
		wp_enqueue_script('alertify', plugins_url('js/alertify.js', dirname(__FILE__) ), array('jquery'), '1.0.0', true);
		wp_enqueue_script('seatreg-booking-check', SEATREG_PLUGIN_FOLDER_URL . 'js/seatreg_booking_check.js' , array('jquery'), '1.3.0', true);
		wp_localize_script('seatreg-booking-check', 'WP_Seatreg', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'successMessage' => __('Receipt sent', 'seatreg'),
			'errorMessage' => __('Something went wrong!', 'seatreg'),
			'updatingPageContent' => __('Refreshing page. Please wait.', 'seatreg')
		));

		if( $bookingData->booking_status_page_custom_styles ) {
			add_action('wp_head', function() use ($bookingData) {
				seatreg_add_custom_styles($bookingData->booking_status_page_custom_styles);
			}, 100);
		}
	}

	if( seatreg_is_booking_confirm_page() && !empty( $_GET['confirmation-code'] ) ) {
		$options = SeatregOptionsRepository::getOptionsByConfirmationCode( sanitize_text_field($_GET['confirmation-code']) );

		if( $options->booking_confirm_page_custom_styles ) {
			add_action('wp_head', function() use ($options) {
				seatreg_add_custom_styles($options->booking_confirm_page_custom_styles);
			}, 100);
		}
	}
}

function seatreg_add_custom_styles($customStyles) {
	$customStyles = str_replace( '&gt;', '>', esc_html($customStyles) );
	echo "<style type=\"text/css\">\n" . esc_html($customStyles) . "\n</style>\n";
}
