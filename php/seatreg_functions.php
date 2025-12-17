<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

global $wpdb;
global $seatreg_db_table_names;

$seatreg_db_table_names = new stdClass();
$seatreg_db_table_names->table_seatreg = $wpdb->prefix . "seatreg";
$seatreg_db_table_names->table_seatreg_options = $wpdb->prefix . "seatreg_options";
$seatreg_db_table_names->table_seatreg_bookings = $wpdb->prefix . "seatreg_bookings";
$seatreg_db_table_names->table_seatreg_payments = $wpdb->prefix . "seatreg_payments";
$seatreg_db_table_names->table_seatreg_payments_log = $wpdb->prefix . "seatreg_payments_log";
$seatreg_db_table_names->table_seatreg_activity_log = $wpdb->prefix . "seatreg_activity_log";
$seatreg_db_table_names->table_seatreg_api_tokens = $wpdb->prefix . "seatreg_api_tokens";

/*
   Useful functions
   Generating HTML stuff
   Registration logic and math
   Database stuff
   Admin form submit stuff
   Ajax stuff
   Paypal
 */

/*
==================================================================================================================================================================================================================
Useful functions
==================================================================================================================================================================================================================
*/

//for bookings pdf, xlsx adn text files. Do view those files you need to be logged in and have permissions
function seatreg_is_user_logged_in_and_has_permissions() {
	if( !is_user_logged_in() ) {
		esc_html_e('Please log in to view this area', 'seatreg');

		exit();
	}

	if( !current_user_can(SEATREG_MANAGE_BOOKINGS_CAPABILITY) ) {
		esc_html_e('No permissions', 'seatreg');

		exit();
	}
}

//generating nonce fields without html id attribute
function seatrag_generate_nonce_field($action) {
	?>
		<input type="hidden" name="<?php echo esc_attr($action); ?>" value="<?php echo esc_attr(wp_create_nonce( $action )); ?>" />
		<?php echo wp_referer_field( false ); ?>
	<?php
}

//nonce check
function seatreg_nonce_check() {
	if ( ! wp_verify_nonce( $_POST['seatreg-admin-nonce'], 'seatreg-admin-nonce' ) ) {
	    wp_die('Nonce validation failed!');
	}

	if( !current_user_can(SEATREG_MANAGE_EVENTS_CAPABILITY) ) {
		 wp_die('You are not allowed to do this');
	}
}

//capability check
function seatreg_check_user_capabilities($requiredCapability) {
	if( !current_user_can($requiredCapability) ) {	
		wp_die('You are not allowed to do this');	
	}
}

function seatreg_is_registration_view_page() {
	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'registration' ) {
		return true;
	}
	return false;
}

function seatreg_is_companion_app_page() {
	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'companion' ) {
		return true;
	}
	return false;
}

function seatreg_is_booking_check_page() {
	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'booking-status' ) {
		return true;
	}
	return false;
}

function seatreg_is_booking_confirm_page() {
	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'booking-confirm' ) {
		return true;
	}
	return false;
}

function seatreg_validate_bookings_file_input() {
	if(empty($_GET['code'])) {
		wp_die('Missing code');
	}
}

function assignIfNotEmpty(&$item, $default){
    return (!empty($item)) ? $item : $default;
}

function getSiteLanguage() {
	return explode('-', get_bloginfo("language"))[0];
}

function showFirstLetters($inputString, $lettersToShow) {
	$firstFourLetters = substr($inputString, 0, $lettersToShow);
    $remainingLetters = substr($inputString, $lettersToShow);
    $convertedString = $firstFourLetters . str_repeat('●', strlen($remainingLetters));

    return $convertedString;
}

/*
==================================================================================================================================================================================================================
Generating HTML stuff
==================================================================================================================================================================================================================
*/

function seatreg_generate_overview_section($targetRoom) {
	global $wpdb;
	global $seatreg_db_table_names;

	$active_tab = null;

	if( SeatregDataValidation::tabsDataExists() ) {
	    $active_tab = sanitize_text_field($_GET[ 'tab' ]);
		$validation = SeatregDataValidation::validateTabData($active_tab);

		if( !$validation->valid ) {
			wp_die( esc_html($validation->errorMessage) );
		}
	} 
	$registration = seatreg_get_options( $active_tab )[0];
	$filterBookingsByDate = SeatregCalendarService::getBookingFilteringDate($registration->using_calendar);

	seatreg_generate_overview_section_html($targetRoom, $active_tab, $filterBookingsByDate);
}

//generate overview section html.
function seatreg_generate_overview_section_html($targetRoom, $active_tab, $filterBookingsByDate) {
	global $wpdb;
	global $seatreg_db_table_names;

	$registration = seatreg_get_options( $active_tab );

	if( count($registration) == 0 ) {
		seatreg_no_registration_created_info();
		 
	 	return;
	 }

	$registration = $registration[0];

	$bookings = SeatregBookingRepository::getConfirmedAndApprovedBookingsByRegistrationCode( $registration->registration_code );
	$pendingBookingsRoomInfo = [];
	$confirmedBookingsRoomInfo = [];

	if($registration->using_calendar === '1') {
		$pendingBookingsRoomInfo = $wpdb->get_results("SELECT room_uuid, COUNT(id) AS total FROM $seatreg_db_table_names->table_seatreg_bookings WHERE registration_code = '$registration->registration_code' AND calendar_date = '$filterBookingsByDate' AND status = 1 GROUP BY room_uuid");
		$confirmedBookingsRoomInfo = $wpdb->get_results("SELECT room_uuid, COUNT(id) AS total FROM $seatreg_db_table_names->table_seatreg_bookings WHERE registration_code = '$registration->registration_code' AND calendar_date = '$filterBookingsByDate' AND status = 2 GROUP BY room_uuid");
	}else {
		$pendingBookingsRoomInfo = $wpdb->get_results("SELECT room_uuid, COUNT(id) AS total FROM $seatreg_db_table_names->table_seatreg_bookings WHERE registration_code = '$registration->registration_code' AND calendar_date IS NULL AND status = 1 GROUP BY room_uuid");
		$confirmedBookingsRoomInfo = $wpdb->get_results("SELECT room_uuid, COUNT(id) AS total FROM $seatreg_db_table_names->table_seatreg_bookings WHERE registration_code = '$registration->registration_code' AND calendar_date IS NULL AND status = 2 GROUP BY room_uuid");
	}
	
	$regStats = seatreg_get_room_seat_info($registration->registration_layout, $pendingBookingsRoomInfo, $confirmedBookingsRoomInfo);
	$project_name = $registration->registration_name;
	$start_date = $registration->registration_start_timestamp;
	$end_date = $registration->registration_end_timestamp;
	$regUrl =  get_site_url();
	$roomLoactionInStats = -1;
	$rName = str_replace(" ", "-", $registration->registration_name); 

	?>

	  		<?php echo '<div class="reg-overview" id="existing-regs">';?>
	  			<input type="hidden" id="seatreg-reg-code" value="<?php esc_attr_e($registration->registration_code); ?>"/>

				  <?php if($registration->using_calendar === '1') : ?>
					<div class="overview-calendar-wrap">
					<label for="overview-calendar-date"><?php esc_html_e('Date', 'seatreg'); ?> <i class="fa fa-calendar" aria-hidden="true"></i></label>
						<input type="text" id="overview-calendar-date" class="" value="<?php echo esc_attr($filterBookingsByDate); ?>" autocomplete="off" />

						<input type='hidden' value='<?php echo esc_html($filterBookingsByDate); ?>' id='overview-calendar-date-value' />
					</div>
				<?php endif; ?>

				<?php echo '<div class="reg-overview-top">';?>
	  				<?php 
	  					if($targetRoom == 'overview') {
							echo '<div class="reg-overview-top-header">';
								esc_html_e($project_name); 
							echo '</div>'; 
	  					}else {
							echo '<div class="reg-overview-top-header">';
								esc_html_e($targetRoom); 
							echo '</div>';
	  					}
	  				?>

					<?php
						if($targetRoom == 'overview') {
							echo "<div class='reg-overview-top-bron-notify'>";
								echo $registration->using_seats === '1' ? sprintf(esc_html__('%s pending seats', 'seatreg'), $regStats['bronSeats']) : sprintf(esc_html__('%s pending places', 'seatreg'), $regStats['bronSeats']), '!';
							echo '</div>';
						}else {
							for($i = 0; $i < $regStats['roomCount']; $i++) {
								if($regStats['roomsInfo'][$i]['roomName'] == $targetRoom) {
									echo '<div class="reg-overview-top-bron-notify">';
										if( $registration->using_seats === '1' ) {
											echo esc_html($regStats['roomsInfo'][$i]['roomBronSeats']),' ', esc_html__('pending seats', 'seatreg'), '!';
										}else {
											echo esc_html($regStats['roomsInfo'][$i]['roomBronSeats']),' ', esc_html__('pending places', 'seatreg'), '!';
										}	
									echo '</div>'; 
									
									$roomLoactionInStats = $i;

									break;
								}
							}
						}
					?>

					<?php 
						$start = esc_html__('Start date not set', 'seatreg');
						$end = esc_html__('End date not set', 'seatreg');

						if(!empty($start_date)) {
							$start = $start_date;
						}

						if(!empty($end_date)) {
							$end = $end_date;
						}
						
						echo "<div class='reg-overview-top-date'><span class='time-block'><i class='fa fa-clock-o' style='color:rgb(4, 145, 4); margin-right:3px'></i><span class='time-stamp'>$start</span></span>  <span class='time-block'><i class='fa fa-clock-o' style='color:rgb(250, 38, 38); margin-right:3px'></i><span class='time-stamp'>$end</span></span></div>"; 
					?>
	  				
				<?php echo '</div>';?>

				<?php echo '<div class="reg-overview-middle-wrap">'; ?>			
				<?php echo '<div class="reg-overview-aside">';?>

					<ul class="room-list">
						<li class="room-list-item first-item" <?php if($targetRoom == 'overview') { echo 'data-active="true"';} ?> data-stats-target="overview"><?php esc_html_e('Overall', 'seatreg'); ?> </li>

						<?php
							for($i = 0; $i < $regStats['roomCount']; $i++) {
								if($regStats['roomsInfo'][$i]['roomName'] != $targetRoom) {
									echo '<li class="room-list-item" data-stats-target="', esc_attr($regStats['roomsInfo'][$i]['roomName']),'">', esc_html($regStats['roomsInfo'][$i]['roomName']),'</li>';
								}else {
									echo '<li class="room-list-item" data-active="true" data-stats-target="', esc_attr($regStats['roomsInfo'][$i]['roomName']),'">', esc_html($regStats['roomsInfo'][$i]['roomName']),'</li>';
								}
							}	
						?>
						
					</ul>

				<?php echo '</div>';?>

				<?php echo '<div class="reg-overview-middle">';?>
					<div class="overview-middle-box">
						<div class="overview-middle-box-h">
							<?php $registration->using_seats === '1' ? esc_html_e('Seats', 'seatreg') : esc_html_e('Places', 'seatreg'); ?>
						</div>
						<div class="overview-middle-box-stat">
							<?php 
								if($targetRoom == 'overview') {
									echo esc_html($regStats['seatsTotal']);
								}else if($roomLoactionInStats >= 0) {
									echo esc_html($regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal']);
								}
							?>
						</div>	
					</div>

					<div class="overview-middle-box">
						<div class="overview-middle-box-h">
							<?php esc_html_e('Open', 'seatreg'); ?>
						</div>
						<div class="overview-middle-box-stat">
							<?php 
								if($targetRoom == 'overview') {
									echo esc_html($regStats['openSeats']); 

								}else if($roomLoactionInStats >= 0) {
									echo esc_html($regStats['roomsInfo'][$roomLoactionInStats]['roomOpenSeats']);
								}
							?>
						</div>	
					</div>

					<div class="overview-middle-box">
						<div class="overview-middle-box-h">
							<?php esc_html_e('Confirmed', 'seatreg'); ?>
						</div>
						<div class="overview-middle-box-stat">
							<?php 
								if($targetRoom == 'overview') {
									echo esc_html($regStats['takenSeats']); 
								}else if($roomLoactionInStats >= 0) {
									echo esc_html($regStats['roomsInfo'][$roomLoactionInStats]['roomTakenSeats']);
								}
							?>
						</div>	
					</div>

					<div class="overview-middle-box">
						<div class="overview-middle-box-h">
							<?php esc_html_e('Pending', 'seatreg'); ?>
						</div>
						<div class="overview-middle-box-stat">
							<?php 
								if($targetRoom == 'overview') {
									echo esc_html($regStats['bronSeats']); 
								}else if($roomLoactionInStats >= 0) {
									echo esc_html($regStats['roomsInfo'][$roomLoactionInStats]['roomBronSeats']);
								}
							?>
						</div>	
					</div>	

				<?php echo '</div>';?>

				<?php echo '<div class="reg-overview-donuts">';?>

					<canvas class="stats-doughnut" height="100" width="100"></canvas>

					<div class="stats-doughnut-legend">
						<?php if($regStats['seatsTotal']): ?>

							<div class="legend-block"><span class="doughnut-legend" style="background-color:#61B329"></span><span style="padding-right: 12px"><?php esc_html_e('Open', 'seatreg'); ?> </span>
								<span class="legend-block-percent" style="color:#61B329">
									<?php 
										if($targetRoom == 'overview') {
											echo esc_html( round(($regStats['openSeats'] / $regStats['seatsTotal']  ) * 100) ), '%'; 
										}else if($roomLoactionInStats >= 0) {

											if($regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal'] > 0) {
												echo esc_html( round(($regStats['roomsInfo'][$roomLoactionInStats]['roomOpenSeats'] / $regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal']  ) * 100, 2)), '%'; 
											}else {
												echo '0%';
											}
										}
									?>
								</span>
							</div>
							<div class="legend-block"><span class="doughnut-legend" style="background-color:red"></span><span style="padding-right: 12px"><?php esc_html_e('Confirmed', 'seatreg'); ?> </span>
								<span class="legend-block-percent" style="color:red">
									<?php 
										if($targetRoom == 'overview') {
											echo esc_html(round(($regStats['takenSeats'] / $regStats['seatsTotal']  ) * 100)), '%'; 
										}else if($roomLoactionInStats >= 0) {

											if($regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal'] > 0) {
												echo esc_html(round(($regStats['roomsInfo'][$roomLoactionInStats]['roomTakenSeats'] / $regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal']  ) * 100, 2)), '%'; 
											}else {
												echo '0%';
											}
										}
									?>
								</span>
							</div>
							<div class="legend-block"><span class="doughnut-legend" style="background-color:yellow"></span><span style="padding-right: 12px"><?php esc_html_e('Pending', 'seatreg'); ?> </span>
								<span class="legend-block-percent" style="color:#26a6d1">
									<?php 
										if($targetRoom == 'overview') {
											echo esc_html(round(($regStats['bronSeats'] / $regStats['seatsTotal']  ) * 100)), '%'; 
										}else if($roomLoactionInStats >= 0) {

											if($regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal'] > 0) {
												echo esc_html(round(($regStats['roomsInfo'][$roomLoactionInStats]['roomBronSeats'] / $regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal'] ) * 100, 2)), '%'; 
											}else {
												echo '0%';
											}											
										}		
									?>
								</span>
							</div>
						<?php endif; ?>
					</div>
					<?php if($targetRoom == 'overview'): ?>
						<input type="hidden" class="seats-total-don" value="<?php echo esc_attr($regStats['seatsTotal']); ?>"/>
						<input type="hidden" class="seats-bron-don" value="<?php echo esc_attr($regStats['bronSeats']); ?>"/>
						<input type="hidden" class="seats-taken-don" value="<?php echo esc_attr($regStats['takenSeats']); ?>"/>
						<input type="hidden" class="seats-open-don" value="<?php echo esc_attr($regStats['openSeats']); ?>"/>
					<?php else: ?>
						<input type="hidden" class="seats-total-don" value="<?php echo esc_attr($regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal']); ?>"/>
						<input type="hidden" class="seats-bron-don" value="<?php echo esc_attr($regStats['roomsInfo'][$roomLoactionInStats]['roomBronSeats']); ?>"/>
						<input type="hidden" class="seats-taken-don" value="<?php echo esc_attr($regStats['roomsInfo'][$roomLoactionInStats]['roomTakenSeats']); ?>"/>
						<input type="hidden" class="seats-open-don" value="<?php echo esc_attr($regStats['roomsInfo'][$roomLoactionInStats]['roomOpenSeats']); ?>"/>
					<?php endif; ?>

				<?php echo '</div>';?>
				<?php echo '</div>'; ?>	
			<?php echo '</div>';?>
	  <?php		
}

//generate my registration section. In this section you can see your registration names with links to overview, booking manager and map builder.
function seatreg_generate_my_registrations_section() {
	$registrations = SeatregRegistrationRepository::getRegistrations();

	if( count($registrations) ) {
		echo '<h4 class="your-registrations-header">';
			esc_html_e('Created registrations', 'seatreg');
		echo '</h4>';
	}
	echo '<div class="seatreg-registrations">';

	foreach($registrations as $key=>$registration) {
		$registrationLink = SeatregLinksService::getRegistrationURL() . '?seatreg=registration&c=' . esc_html($registration->registration_code) . '&page_id=' . SEATREG_PAGE_ID;
		
		?>
			<div class="mb-4" data-item="registration" style="margin-right: 52px">
				<h5><a class="registration-name-link" href="<?php echo esc_url($registrationLink); ?>" target="_blank"><?php echo esc_html( wp_unslash($registration->registration_name) ); ?></a></h5>

				<a href="<?php echo esc_url($registrationLink); ?>" target="_blank"><?php esc_html_e('Registration', 'seatreg'); ?></a>

				<br>

				<button type="button" class="btn btn-link seatreg-map-popup-btn" data-registration-name="<?php echo esc_attr($registration->registration_name); ?>" data-map-code="<?php echo esc_attr($registration->registration_code); ?>"><?php esc_html_e('Layout', 'seatreg'); ?></button>

				<br>

				<a href="<?php echo esc_url(admin_url( 'admin.php?page=seatreg-overview&tab='.$registration->registration_code )); ?>"><?php esc_html_e('Overview', 'seatreg'); ?></a>

				<br>

				<a href="<?php echo esc_url(admin_url( 'admin.php?page=seatreg-options&tab='.$registration->registration_code )); ?>"><?php esc_html_e('Settings', 'seatreg'); ?></a>

				<br>

				<a href="<?php echo esc_url(admin_url( 'admin.php?page=seatreg-management&tab='.$registration->registration_code )); ?>"><?php esc_html_e('Bookings', 'seatreg'); ?></a>

				<br>

				<a href="#" data-action="view-more-modal" data-registration-id="<?php echo esc_attr($registration->registration_code); ?>"><?php esc_html_e('More', 'seatreg'); ?></a>

				<br>

				<?php
					seatreg_more_items_modal( $registration->registration_code );
					seatreg_copy_registration_modal( $registration->registration_code );
					seatreg_shortcode_modal( $registration->registration_code );
				?>
			</div>
		<?php
	}
	echo '</div>';
}

function seatreg_no_registration_created_info() {
	esc_html_e('No registrations created!', 'seatreg');
}

//generate settings form for registration settings
function seatreg_generate_settings_form() {
	 $active_tab = null;

	if( SeatregDataValidation::tabsDataExists() ) {
	    $active_tab = sanitize_text_field($_GET[ 'tab' ]);
		$validation = SeatregDataValidation::validateTabData($active_tab);

		if( !$validation->valid ) {
			wp_die(esc_html($validation->errorMessage));
		}
	}

	 $options = seatreg_get_options($active_tab);
	 $active_tab = $options[0]->registration_code;

	 if( count($options) == 0 ) {
		 seatreg_no_registration_created_info();
		 
	 	return;
	 }

	 $custFields = json_decode( isset($options[0]->custom_fields) ? $options[0]->custom_fields : "[]");
	 $custLen = count(is_array($custFields) ? $custFields : []);
	 $customPayments = json_decode( $options[0]->custom_payments ? $options[0]->custom_payments : "[]");
	 $coupons = json_decode( isset($options[0]->coupons) ? $options[0]->coupons : "[]");
	 $previouslySelectedBookingDataToShow = $options[0]->show_bookings_data_in_registration ? explode(',', $options[0]->show_bookings_data_in_registration) : [];
	 $adminEmail = get_option( 'admin_email' );
	 $publicApiTokens = SeatregApiTokenRepository::getRegistrationApiTokens($options[0]->registration_code);

	?>
		<h4 class="settings-heading">
			<?php echo sprintf( esc_html__('%s settings', 'seatreg'), esc_html(wp_unslash($options[0]->registration_name))); ?> 
		</h4>
		<form action="<?php echo esc_url(get_admin_url() . 'admin-post.php'); ?>" method="post" id="seatreg-settings-form" class="seatreg-settings-form" style="max-width:600px">

			<div class="form-group">
				<label for="registration-name"><?php esc_html_e('Registration name', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Change registration name', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="registration-name" name="registration-name" maxlength="<?php echo esc_attr(SEATREG_REGISTRATION_NAME_MAX_LENGTH); ?>" placeholder="<?php esc_html_e('Enter registration name', 'seatreg'); ?>" autocomplete="off" value="<?php echo esc_attr($options[0]->registration_name); ?>">
			</div>
			
			<div class="form-group">
				<label for="zoom-on-top"><?php esc_html_e('Zoom controls on top', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Show the zoom controls before the seat map, or after it', 'seatreg'); ?></label>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="zoom-on-top" name="zoom-on-top" value="0" <?php echo $options[0]->zoom_on_top == '1' ? 'checked':'' ?>>
						<?php esc_html_e('Zoom controls on top', 'seatreg'); ?>
					</label>
				</div>
			</div>
			
			<div class="form-group">
				<label for="show-info-button"><?php esc_html_e('Show info button', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Set if the info button is shown on the booking page', 'seatreg'); ?></label>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="show-info-button" name="show-info-button" value="0" <?php echo $options[0]->show_info_button == '1' ? 'checked':'' ?>>
						<?php esc_html_e('Show info button', 'seatreg'); ?>
					</label>
				</div>
			</div>

			<div class="form-group">
				<label for="registration-status"><?php esc_html_e('Registration status', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Close and open registration', 'seatreg'); ?>.</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="registration-status" name="registration-status" value="1" <?php echo $options[0]->registration_open == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Open', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="registration-close-reason"><?php esc_html_e('Close reason', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('You can leave text explaining why registration is closed. Will be displayed on registration view.', 'seatreg'); ?>
				</p>
				<textarea class="form-control" id="registration-close-reason" name="registration-close-reason" placeholder="<?php esc_html_e('Enter reason', 'seatreg'); ?>"><?php echo esc_attr($options[0]->registration_close_reason); ?></textarea>
			</div>

			<div class="form-group">
				<label for="require-name"><?php esc_html_e('Require full name for booking (first & last)', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Controls whether first and last name are required when making a booking. When enabled, customers must provide their full name to complete the registration process.', 'seatreg'); ?></label>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="require-name" name="require-name" value="0" <?php echo $options[0]->require_name == '1' ? 'checked':'' ?>>
						<?php esc_html_e('Require name', 'seatreg'); ?>
					</label>
				</div>
			</div>
			
			<div class="form-group">
				<label for="require-wp-login"><?php esc_html_e('Require WordPress login', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Only logged in WordPress users can make a booking', 'seatreg'); ?>.</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="require-wp-login" name="require-wp-login" value="0" <?php echo $options[0]->require_wp_login == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Require login', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="wp-user-booking-limit">
					<?php esc_html_e('Booking limit per WordPress user', 'seatreg'); ?>
				</label>
				<p class="help-block">
					<?php esc_html_e('Limit how many times can one WordPress user make a booking. Leave empty for no limit', 'seatreg'); ?>.
				</p>
				<input type="number" class="form-control" id="wp-user-booking-limit" name="wp-user-booking-limit" value="<?php echo esc_html($options[0]->wp_user_booking_limit); ?>">
			</div>

			<div class="form-group">
				<label for="wp-user-bookings-seat-limit">
					<?php esc_html_e('Total number of seats WordPress user can book', 'seatreg'); ?>
				</label>
				<p class="help-block">
					<?php esc_html_e('Set limit on how many seats can one WordPress user book across all their bookings. Leave empty for no limit', 'seatreg'); ?>.
				</p>
				<input type="number" class="form-control" id="wp-user-bookings-seat-limit" name="wp-user-bookings-seat-limit" value="<?php echo esc_html($options[0]->wp_user_bookings_seat_limit); ?>">
			</div>

			<div class="form-group">
				<label for="registration-max-seats">
					<?php esc_html_e('Max seats per booking', 'seatreg'); ?>
				</label>
				<p class="help-block">
					<?php esc_html_e('Set how many seats can be added to the booking', 'seatreg'); ?>.
				</p>
				<input type="number" class="form-control" id="registration-max-seats" name="registration-max-seats" value="<?php echo esc_html($options[0]->seats_at_once); ?>">
			</div>

			<div class="form-group">
				<label for="using-seats"><?php esc_html_e('Registration is using seats', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Uncheck if your registration is not dealing with seats. More generic place will be used', 'seatreg'); ?>.</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="using-seats" name="using-seats" value="1" <?php echo $options[0]->using_seats == '1' ? 'checked':''; ?> >
			      		<?php esc_html_e('Using seats', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="using-calendar"><?php esc_html_e('Registration calendar', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Please check if you want to enable calendar functionality, as it allows registration to reset on each day.', 'seatreg'); ?>.</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="using-calendar" name="using-calendar" value="1" <?php echo $options[0]->using_calendar == '1' ? 'checked="checked"':''; ?> >
			      		<?php esc_html_e('Turn on calendar', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group ml-4 mt-n3" <?php if( $options[0]->using_calendar === '0' ) { echo ' style="display:none;"'; } ?>>
				<label for="calendar-dates"><?php esc_html_e('Calendar dates', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Choose the dates when registration is open. Leave empty for no date restrictions', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="calendar-dates" name="calendar-dates" placeholder="<?php esc_html_e('Click here to add dates', 'seatreg'); ?>" autocomplete="off" value="<?php echo esc_attr($options[0]->calendar_dates ? $options[0]->calendar_dates : ""); ?>">
			</div>
		
			<div class="form-group">
				<label for="registration-start-timestamp"><i class="fa fa-calendar" style="color:rgb(4, 145, 4); margin-right:6px"></i><?php esc_html_e('Registration start date', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Set registration start date (dd.mm.yyyy)', 'seatreg'); ?>.
					<?php esc_html_e('Make sure that you have a correct timezone in WordPress settings (settings->general)', 'seatreg'); ?>.
				</p>
				<input type="text" id="registration-start-timestamp" class="form-control option-datepicker" placeholder="(dd.mm.yyyy)" autocomplete="off" />
				<input type='hidden' value='<?php echo esc_attr($options[0]->registration_start_timestamp); ?>' id='start-timestamp' class="datepicker-altfield" name="start-timestamp" />
			</div>

			<div class="form-group">
				<label for="registration-start-time"><i class="fa fa-clock-o" style="color:rgb(4, 145, 4); margin-right:6px"></i><?php esc_html_e('Registration start time', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Set registration start time (24 hours time format)', 'seatreg'); ?>.
					<?php esc_html_e('Make sure that you have a correct timezone in WordPress settings (settings->general)', 'seatreg'); ?>.
				</p>
				<input id="registration-start-time" name="registration-start-time" class="time" type="text" value="<?php echo esc_attr($options[0]->registration_start_time); ?>" />
			</div>

			<div class="form-group">
				<label for="registration-end-timestamp"><i class="fa fa-calendar" style="color:rgb(250, 38, 38); margin-right:6px"></i><?php esc_html_e('Registration end date', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Set registration end date (dd.mm.yyyy)', 'seatreg'); ?>.
					<?php esc_html_e('Make sure that you have a correct timezone in WordPress settings (settings->general)', 'seatreg'); ?>.
				</p>
				<input type="text" id="registration-end-timestamp" class="form-control option-datepicker" placeholder="(dd.mm.yyyy)" autocomplete="off" />
				<input type='hidden' value='<?php echo esc_attr($options[0]->registration_end_timestamp); ?>' id="end-timestamp" class="datepicker-altfield" name="end-timestamp" />
			</div>

			<div class="form-group">
				<label for="registration-end-time"><i class="fa fa-clock-o" style="color:rgb(250, 38, 38); margin-right:6px"></i><?php esc_html_e('Registration end time', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Set registration end time (24 hours time format)', 'seatreg'); ?>.
					<?php esc_html_e('Make sure that you have a correct timezone in WordPress settings (settings->general)', 'seatreg'); ?>.
				</p>
				<input id="registration-end-time" name="registration-end-time" class="time" type="text" value="<?php echo esc_attr($options[0]->registration_end_time); ?>"  />
			</div>

			<div class="form-group">
				<label><?php esc_html_e('Show booking data', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Show booking data in registration view. You can select custom fields here once they are created', 'seatreg'); ?>.</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" name="show-booking-data-registration[]" value="name" <?php echo in_array('name', $previouslySelectedBookingDataToShow) ? 'checked' : '' ?> /> 
			      		<?php esc_html_e('Show full name', 'seatreg'); ?>
			    	</label>
			  	</div>
	 			<?php if( is_array($custFields) ): ?>
					<?php foreach( $custFields as $customField ): ?>
						<div class="checkbox">
							<label>
								<input type="checkbox" name="show-booking-data-registration[]" value="<?php esc_html_e($customField->label); ?>" <?php echo in_array($customField->label, $previouslySelectedBookingDataToShow) ? 'checked' : '' ?> /> 
								<?php esc_html_e($customField->label); ?>
							</label>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="form-group">
				<label for="controlled-scroll"><?php esc_html_e('Controlled scroll', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('By turning on controlled scroll users need to hold Z key while zooming in/out with mouse scrollwheel. Helpful if shortcode conflicts with overall page scroll.', 'seatreg'); ?></p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="controlled-scroll" name="controlled-scroll" value="1" <?php echo $options[0]->controlled_scroll == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Enable controlled scroll', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="registration-info-text"><?php esc_html_e('Registration info text', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Set registration info text. Will be displayed in registration page', 'seatreg'); ?>.</p>
				<textarea class="form-control" id="registration-info-text" name="registration-info-text" placeholder="<?php esc_html_e('Enter info text here', 'seatreg'); ?>"><?php echo esc_html($options[0]->info); ?></textarea>
			</div>

			<div class="form-group">
				<label><?php esc_html_e('Custom footer area for booking confirm dialog', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Will be displayed at the last step of booking process in registration page.', 'seatreg'); ?></p>
				<?php
				/* Add placeholder text? placeholder="<?php esc_html_e('Enter custom footer text here', 'seatreg'); ?>" */
				$customFooterTextEditorSettings = array(
				    'wpautop' => true, // enable auto paragraph
				    'textarea_name' => 'custom-footer-text',
				    'textarea_rows' => 4,
				    'media_buttons' => false,
				);
				wp_editor($options[0]->custom_footer_text, 'customFooterTextEditor', $customFooterTextEditorSettings)
				?>
			</div>

			<div class="form-group">
			<label for="one-person-checkout"><?php esc_html_e('One person checkout', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e("By default, during booking checkout, information must be entered separately for each seat. The 'One Person Checkout' option simplifies this by requiring details for only one seat, and if multiple seats are selected, the same data will be copied to all seats behind the scenes.", 'seatreg'); ?></p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="one-person-checkout" name="one-person-checkout" value="0" <?php echo $options[0]->one_person_checkout === '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Enable one person checkout', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
			<label for="automatic-booking-confirm-dialog"><?php esc_html_e('Automatic booking confirm dialog', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e("By default, users need to manually open the seat selection menu to complete their booking. This option automatically displays the booking confirmation dialog immediately after seat is selected.", 'seatreg'); ?></p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="automatic-booking-confirm-dialog" name="automatic-booking-confirm-dialog" value="0" <?php echo $options[0]->automatic_booking_confirm_dialog  === '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Open booking confirm dialog automatically', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="bookings-email-limit"><?php esc_html_e('Booking email limit', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Specify how many bookings can be made with the same email. Leave empty for no limit', 'seatreg'); ?>.
				</p>
				<input type="number" class="form-control" id="bookings-email-limit" name="bookings-email-limit" value="<?php echo esc_html($options[0]->booking_email_limit); ?>" placeholder="<?php esc_html_e('No limit for email', 'seatreg'); ?>">
			</div>

			<div class="form-group">
				<label for="gmail-required"><?php esc_html_e('Gmail required', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Gmail address is required when making a booking', 'seatreg'); ?>.</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="gmail-required" name="gmail-required" value="1" <?php echo $options[0]->gmail_required == '1' ? 'checked':'' ?> > 
			      		<?php esc_html_e('Allow only gmail address', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="booking-redirect-status-page"><?php esc_html_e('Booking redirect to status page', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Redirect automatically to booking status page after booking was made. This wont be applied when booking email verification is turned on', 'seatreg'); ?>.</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="booking-redirect-status-page" name="booking-redirect-status-page" value="0" <?php echo $options[0]->booking_redirect_status_page == '1' ? 'checked':'' ?> > 
			      		<?php esc_html_e('Redirect to status page', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="registration-password"><?php esc_html_e('Password', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('You can set a password. Only people who know it can view your registration and make a booking. Leave it empty for no password', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="registration-password" name="registration-password" autocomplete="off" placeholder="<?php echo esc_html('Enter password here', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->registration_password); ?>">
			</div>

			<div class="form-group">
				<label for="use-pending"><?php esc_html_e('Email from address', 'seatreg'); ?></label>
				<p class="help-block">
					<?php echo sprintf(esc_html__('You can specify email FROM address that will be used when sending out emails. By default site admin (%s) email will be used', 'seatreg'), esc_html(get_option( 'admin_email' ))); ?>.
				</p>
				<input type="text" class="form-control" id="email-from" name="email-from" placeholder="<?php echo esc_html('Using default admin email', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->email_from_address); ?>">
			</div>

			<div class="form-group">
				<label for="use-pending"><?php esc_html_e('Booking email verification', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Bookings must be verified by email', 'seatreg'); ?>.
				</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="email-confirm" name="email-confirm" value="1" <?php echo $options[0]->booking_email_confirm == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Email verification', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="verification-email-subject"><?php esc_html_e('Booking email verification subject', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('You can customize the verification email subject. Leave empty for default subject', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="verification-email-subject" name="verification-email-subject" placeholder="<?php echo esc_html('Using default email subject', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->verification_email_subject ); ?>">
			</div>

			<div class="form-group">
				<label for="email-verification-template"><?php esc_html_e('Booking email verification template', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('You can customize the verification email.', 'seatreg'); ?>
					<?php esc_html_e('Supported keywords are', 'seatreg'); ?>: <br>
					<code>[verification-link]</code> <?php esc_html_e('(required) will be converted to email verification link', 'seatreg'); ?>
				</p>
				<textarea rows="4" class="form-control" id="email-verification-template" name="email-verification-template" placeholder="<?php esc_html_e('Using system default message', 'seatreg'); ?>"><?php echo esc_html($options[0]->email_verification_template); ?></textarea>
			</div>

			<div class="form-group">
				<label for="use-pending"><?php esc_html_e('Use pending bookings', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('By default all bookings will first be in pending state so admin can approve them (with booking manager). If you want bookings automatically to be in approved state then uncheck below.', 'seatreg'); ?>
				</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="use-pending" name="use-pending" value="1" <?php echo $options[0]->use_pending == '1' ? 'checked':'' ?> > 
			      		<?php esc_html_e('Use pending', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="booker-pending-booking-notification"><?php esc_html_e('Booker pending booking notification', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Send out email to booker when booking is pending.', 'seatreg'); ?>
				</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="booker-pending-booking-notification" name="booker-pending-booking-notification" value="1" <?php echo $options[0]->notify_booker_pending_booking == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Send pending notification to booker', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="pending-booking-email-subject"><?php esc_html_e('Pending booking email subject', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('You can customize the pending booking email subject. Leave empty for default subject', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="pending-booking-email-subject" name="pending-booking-email-subject" placeholder="<?php esc_html_e('Using default email subject', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->pending_booking_email_subject ); ?>">
			</div>

			<div class="form-group">
				<label for="pendin-booking-email-template"><?php esc_html_e('Pending booking email template', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('When booking gets pending status then email will be sent out. You can customize the email.', 'seatreg'); ?>
					<?php esc_html_e('Supported keywords are', 'seatreg'); ?>: <br>
					<code>[status-link]</code> <?php esc_html_e('(required) will be converted to booking status link', 'seatreg'); ?>
				</p>
				<textarea rows="4" class="form-control" id="pendin-booking-email-template" name="pendin-booking-email-template" placeholder="<?php esc_html_e('Using system default message', 'seatreg'); ?>"><?php echo esc_html($options[0]->pending_booking_email_template); ?></textarea>
			</div>

			<div class="form-group">
				<label for="pending-expiration"><?php esc_html_e('Pending booking expiration', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('You can enable pending booking expiration after a certain period of time (in minutes). If the booking has some payment related activity, then booking will not be removed. Leave empty for no expiration time.', 'seatreg'); ?>
				</p>
				<input type="number" class="form-control" id="pending-expiration" name="pending-expiration" autocomplete="off" placeholder="<?php echo esc_html('Expiration time not set', 'seatreg'); ?>" value="<?php echo ($options[0]->pending_expiration) ? esc_html($options[0]->pending_expiration) : ''; ?>" />
			</div>

			<div class="form-group">
				<label for="booking-notification"><?php esc_html_e('Booking notification', 'seatreg'); ?></label>
				<p class="help-block">
					<?php
						esc_html_e('Get notified when you got a new booking.', 'seatreg' );
					?>
				</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="booking-notification" name="booking-notification" value="1" <?php echo $options[0]->notify_new_bookings == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Send notifications', 'seatreg'); ?>
			    	</label>
			  	</div>
				<p class="help-block">
					<?php
						printf(
							/* translators: %s: email address */
							esc_html__( 'By default notification will be sent to %s. You can configure it below.', 'seatreg' ),
							esc_html($adminEmail)
						);
					?>
				</p>
				<input type="text" class="form-control" id="notification-email" name="notification-email" autocomplete="off" placeholder="<?php echo esc_html('Use default email', 'seatreg'); ?>" value="<?php echo ($options[0]->notification_email) ? esc_html($options[0]->notification_email) : ''; ?>" />
			</div>

			<div class="form-group">
				<label for="approved-booking-email"><?php esc_html_e('Approved booking receipt email', 'seatreg'); ?></label>
				<p class="help-block">
					<?php
						esc_html_e('Send out email to booker when booking is approved. This email will contain info about the booking.', 'seatreg');
					?>
				</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="approved-booking-email" name="approved-booking-email" value="1" <?php echo $options[0]->send_approved_booking_email == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Send approved booking email', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="approved-booking-email-subject"><?php esc_html_e('Approved booking email subject', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('You can customize the approved booking email subject. Leave empty for default subject', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="approved-booking-email-subject" name="approved-booking-email-subject" placeholder="<?php esc_html_e('Using default email subject', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->approved_booking_email_subject); ?>">
			</div>

			<div class="form-group">
				<label for="approved-booking-email-template"><?php esc_html_e('Approved booking receipt email template', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Customize how approved booking email looks like.', 'seatreg'); ?>
					<?php esc_html_e('Supported keywords are', 'seatreg'); ?>: <br>
					<code>[status-link]</code> <?php esc_html_e('(required) will be converted to booking status link', 'seatreg'); ?> <br>
					<code>[booking-id]</code> <?php esc_html_e('(optional) will be converted to booking id', 'seatreg'); ?> <br>
					<code>[booking-table]</code> <?php esc_html_e('(optional) will be converted to booking table', 'seatreg'); ?> <br>
					<code>[payment-table]</code> <?php esc_html_e('(optional) will be converted to payment table', 'seatreg'); ?> <br>
					<code><?php echo esc_html(SEATREG_TEMPLATE_BOOKING_APPROVED_EMAIL_CUSTOM_TEXT); ?></code> <?php esc_html_e('(optional) will be converted to text added to booking in booking-manager. Useful if you want to provide custom text specific to the booking.', 'seatreg'); ?> <br>
				</p>
				<textarea rows="6" class="form-control" id="approved-booking-email-template" name="approved-booking-email-template" placeholder="<?php esc_html_e('Using system default message', 'seatreg'); ?>"><?php echo esc_textarea($options[0]->approved_booking_email_template); ?></textarea>
			</div>

			<div class="form-group">
				<label><?php esc_html_e('Booking PDF', 'seatreg'); ?></label>
				<p class="help-block">
					<?php
						esc_html_e('Configures booking details PDF in booking status page.', 'seatreg');
					?>
				</p>
				
				<?php if( extension_loaded('gd') ): ?>
					<?php
						$selectedBookingQRCodeInput = $options[0]->booking_qr_code_input;
					?>
					<select class="form-control" name="booking-qr-code-input">
						<option value="booking-id" <?php echo $selectedBookingQRCodeInput === 'booking-id' ? 'selected' : ''; ?>><?php esc_html_e('Booking ID'); ?></option>
						<option value="booking-url" <?php echo $selectedBookingQRCodeInput === 'booking-url' ? 'selected' : ''; ?>><?php esc_html_e('URl to booking check page'); ?></option>
					</select>
					<br/>
					<div class="checkbox">
						<label>
							<input type="checkbox" id="show-pending-booking-pdf" name="show-pending-booking-pdf" value="0" <?php echo $options[0]->show_pending_booking_pdf == '1' ? 'checked':'' ?> >
							<?php esc_html_e('Show booking PDF if booking status is pending', 'seatreg'); ?>
						</label>
					</div>
					<div class="checkbox">
						<label>
							<input type="checkbox" id="show-approved-booking-pdf" name="show-approved-booking-pdf" value="0" <?php echo $options[0]->show_approved_booking_pdf == '1' ? 'checked':'' ?> >
							<?php esc_html_e('Show booking PDF if booking status is approved', 'seatreg'); ?>
						</label>
					</div>
				<?php else: ?>
					<div class="alert alert-primary" role="alert">
						<?php esc_html_e('PHP gd extension is required to generate QR codes.', 'seatreg'); ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="form-group">
				<label for="approved-booking-email-qr-code"><?php esc_html_e('Approved booking receipt email QR code', 'seatreg'); ?></label>
				<p class="help-block">
					<?php
						esc_html_e('You can set so that approved booking emails will have QR code in the email. You can select what should the QR code include.', 'seatreg');
					?>
				</p>
				<?php if(extension_loaded('gd')): ?>
					<?php
						$selectedBookingQrCode = $options[0]->send_approved_booking_email_qr_code;
					?>

					<select class="form-control" name="approved-booking-email-qr-code">
						<option value="booking-id" <?php echo $selectedBookingQrCode === 'booking-id' ? 'selected' : ''; ?>><?php esc_html_e('Booking ID'); ?></option>
						<option value="booking-url" <?php echo $selectedBookingQrCode === 'booking-url' ? 'selected' : ''; ?>><?php esc_html_e('URl to booking check page'); ?></option>
						<option value="" <?php echo $selectedBookingQrCode === null ? 'selected' : ''; ?>><?php esc_html_e('Don\'t display QR code'); ?></option>
					</select>

				<?php else: ?>
					<div class="alert alert-primary" role="alert">
						<?php esc_html_e('PHP gd extension is required to generate QR codes.', 'seatreg'); ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="form-group">
				<label for="payment-instructions"><?php esc_html_e('Payment instruction', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('You can leave informative text that instructs how to pay for a booking. It will be displayed in booking status page', 'seatreg'); ?>.</p>
				<textarea class="form-control" id="payment-instructions" name="payment-instructions" placeholder="<?php esc_html_e('Enter payment instructions here', 'seatreg')?>"><?php echo esc_html($options[0]->payment_text); ?></textarea>
			</div>

			<div class="form-group">
				<label for="paypal-currency-code"><?php esc_html_e('Currency', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Pease enter payment currency code (ISO 4217)', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="paypal-currency-code" name="paypal-currency-code" autocomplete="off" maxlength="3" oninput="this.value = this.value.toUpperCase()" placeholder="<?php echo esc_html('Currency code', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->paypal_currency_code); ?>"> 
			</div>

			<div class="form-group">
				<label for="paypal"><?php esc_html_e('PayPal payments', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Allow and configure PayPal payments. Enables you to ask money for bookings. To enable this feature you need to create a Buy Now button in Paypal (https://www.paypal.com/buttons/). Button price and currency fields will be overriden by the plugin (just add something as they are required).', 'seatreg'); ?>
				</p>
				<?php if(extension_loaded('curl')): ?>
					<div class="checkbox">
						<label>
							<input type="checkbox" id="paypal" name="paypal-payments" value="0" <?php echo $options[0]->paypal_payments == '1' ? 'checked':'' ?> >
							<?php esc_html_e('Turn on Paypal payments', 'seatreg'); ?>
						</label>
					</div>
					<div class="payment-configuration">
						<label for="paypal-business-email"><?php esc_html_e('PayPal business email', 'seatreg'); ?></label>
						<p class="help-block">
							<?php esc_html_e('Pease enter your PayPal business email', 'seatreg'); ?>.
						</p>
						<input type="text" class="form-control" id="paypal-business-email" name="paypal-business-email" autocomplete="off" placeholder="<?php echo esc_html('PayPal business email', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->paypal_business_email); ?>"> 
						<br>

						<label for="paypal-button-id"><?php esc_html_e('PayPal button id', 'seatreg'); ?></label>
						<p class="help-block">
							<?php esc_html_e('Pease enter PayPal button id', 'seatreg'); ?>.
						</p>
						<input type="text" class="form-control" id="paypal-button-id" name="paypal-button-id" autocomplete="off" placeholder="<?php echo esc_html('PayPal button id', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->paypal_button_id); ?>"> 
						<br>

						<label for="payment-mark-confirmed"><?php esc_html_e('Set paid booking approved', 'seatreg'); ?></label>
						<p class="help-block">
							<?php esc_html_e('Set booking approved automatically when payment has been completed', 'seatreg'); ?>.
						</p>
						<div class="checkbox">
							<label>
								<input type="checkbox" id="payment-mark-confirmed" name="payment-mark-confirmed" value="0" <?php echo $options[0]->payment_completed_set_booking_confirmed == '1' ? 'checked': ''; ?> >
								<?php esc_html_e('Set approved', 'seatreg'); ?>
							</label>
						</div>
						<br>

						<label for="paypal-sandbox-mode"><?php esc_html_e('PayPal sandbox mode', 'seatreg'); ?></label>
						<p class="help-block">
							<?php esc_html_e('Turn on sandbox mode. Lets you test payments with your sandbox account. Don\'t forget to change business email and button id.', 'seatreg'); ?>
						</p>
						<div class="checkbox">
							<label>
								<input type="checkbox" id="paypal-sandbox-mode" name="paypal-sandbox-mode" value="0" <?php echo $options[0]->paypal_sandbox_mode == '1' ? 'checked':'' ?> >
								<?php esc_html_e('PayPal sandbox', 'seatreg'); ?>
							</label>
						</div>
					</div>
				<?php else: ?>
					<div class="alert alert-primary" role="alert">
						<?php esc_html_e('Curl extension is required for Paypal to work', 'seatreg'); ?>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="form-group">
				<label for="stripe"><?php esc_html_e('Stripe payments', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Allow and configure Stripe payments. Enables you to ask money for bookings. ', 'seatreg'); ?>
				</p>
				<?php if(extension_loaded('curl')): ?>
					<div class="checkbox">
						<label>
							<input type="checkbox" id="stripe" name="stripe-payments" value="0" <?php echo $options[0]->stripe_payments == '1' ? 'checked':'' ?> >
							<?php esc_html_e('Turn on Stripe payments', 'seatreg'); ?>
						</label>
					</div>
					<div class="payment-configuration">
						<label for="stripe_api_key"><?php esc_html_e('Stripe API secret key', 'seatreg'); ?></label>
						<p class="help-block">
							<?php esc_html_e('Please enter your Stripe API secret key', 'seatreg'); ?>.
						</p>
						<input type="text" class="form-control" id="stripe-api-key" name="stripe-api-key" autocomplete="off" placeholder="<?php echo esc_html('Stripe API key', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->stripe_api_key); ?>"> 
						<br>
						<label for="payment-mark-confirmed-stripe"><?php esc_html_e('Set paid booking approved', 'seatreg'); ?></label>
						<p class="help-block">
							<?php esc_html_e('Set booking approved automatically when payment has been completed', 'seatreg'); ?>.
						</p>
						<div class="checkbox">
							<label>
								<input type="checkbox" id="payment-mark-confirmed-stripe" name="payment-mark-confirmed-stripe" value="0" <?php echo $options[0]->payment_completed_set_booking_confirmed_stripe == '1' ? 'checked': ''; ?> >
								<?php esc_html_e('Set approved', 'seatreg'); ?>
							</label>
						</div>
					</div>
				<?php else: ?>
					<div class="alert alert-primary" role="alert">
						<?php esc_html_e('Curl extension is required for Stripe to work', 'seatreg'); ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="form-group">
				<label for="custom-payment"><?php esc_html_e('Custom payment (legacy)', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('This payment method is suitable for manual payments. Allows you to control the payment flow.', 'seatreg'); ?>
				</p>

				<div class="checkbox">
					<label>
						<input type="checkbox" id="custom-payment" name="custom-payment" value="0" <?php echo $options[0]->custom_payment == '1' ? 'checked':'' ?> >
						<?php esc_html_e('Turn on custom payments', 'seatreg'); ?>
					</label>
				</div>
	
				<div class="payment-configuration">
					<label for="custom-payment-title"><?php esc_html_e('Custom payment title', 'seatreg'); ?></label>
					<p class="help-block">
						<?php esc_html_e('Please enter custom payment title', 'seatreg'); ?>
					</p>
					<input type="text" class="form-control" id="custom-payment-title" name="custom-payment-title" autocomplete="off" placeholder="<?php echo esc_html('Title', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->custom_payment_title); ?>"> 
					<br>
					<label for="custom-payment-description"><?php esc_html_e('Custom payment instructions', 'seatreg'); ?></label>
					<p class="help-block"><?php esc_html_e('Please enter custom payment instructions. Will be shown when customer chooses this payment method.', 'seatreg'); ?></p>
					<textarea class="form-control" id="custom-payment-description" name="custom-payment-description" placeholder="<?php esc_html_e('Enter payment instructions', 'seatreg')?>"><?php echo esc_html($options[0]->custom_payment_description); ?></textarea>
				</div>
			</div>

			<div class="form-group">
				<label><?php esc_html_e('Custom payments', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('These payment methods are suitable for manual payments. Allows you to control the payment flow.', 'seatreg'); ?>
				</p>
				<div id="custom-payments">
					<div class="existing-custom-payments">
						<?php foreach($customPayments as $customPayment): ?>
							<div class="custom-payment" data-payment-id="<?php echo esc_attr($customPayment->paymentId); ?>">
								<?php 
									$hasCustomPaymentIcon = property_exists($customPayment, 'paymentIcon') && $customPayment->paymentIcon !== null;
									$IconUploadStlyes = !$hasCustomPaymentIcon ? 'style="display: flex;"' : 'style="display: none;"';
									$paymentIconLocationURl = SeatregUploadsRepository::getCustomPaymentIconLocationURL($active_tab);
								?>
								<p><?php esc_html_e('Title', 'seatreg'); ?></p>
								<input value="<?php echo esc_attr($customPayment->title); ?>" data-id="custom-payment-title" />

								<p><?php esc_html_e('Description', 'seatreg'); ?></p>
								<textarea data-id="custom-payment-description"><?php echo esc_textarea($customPayment->description); ?></textarea>
								<p><?php esc_html_e('Payment icon', 'seatreg'); ?></p>

								<div>
									<div class="current-custom-payment-icon">
										<?php if( $hasCustomPaymentIcon ): ?>
											<image class="current-custom-payment-icon__img" src="<?php echo esc_attr($paymentIconLocationURl . '/' . $customPayment->paymentIcon); ?>" data-name="<?php echo esc_attr($customPayment->paymentIcon); ?>" />
											<i class="fa fa-times-circle current-custom-payment-icon__delete"></i>
											<img class="current-custom-payment-icon__loading" src="<?php echo esc_url(SEATREG_PLUGIN_FOLDER_URL . 'img/ajax_loader_small.gif'); ?>" alt="Loading...">
										<?php endif; ?>
									</div>
									<div class="custom-payment-icon-upload" <?php echo esc_html($IconUploadStlyes); ?> >
										<div class="custom-payment-icon-upload__loading">
											<img src="<?php echo esc_url(SEATREG_PLUGIN_FOLDER_URL . 'img/ajax_loader_small.gif'); ?>" alt="Loading...">
										</div>
										<input type="file" name="custom-payment-icon" data-action="custom-payment-icon-upload" data-code="<?php echo esc_attr($active_tab); ?>" />
										<p class="custom-payment-icon-upload__error"></p>
									</div>
								</div>
							
								<div class="custom-payment__controls">
									<button class="btn btn-danger btn-sm" data-action="remove-custom-payment"><?php esc_html_e('Remove', 'seatreg'); ?></button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<div id="new-custom-payment">
						<div style="margin-bottom: 6px"><?php esc_html_e('Create new custom payment', 'seatreg'); ?></div>
						<p class="help-block">
							<?php esc_html_e('Please enter custom payment title', 'seatreg'); ?>
						</p>
						<input type="text" class="form-control" data-id="new-custom-payment-title" autocomplete="off" placeholder="<?php echo esc_html('Title', 'seatreg'); ?>">
						<br>
						<p class="help-block"><?php esc_html_e('Please enter custom payment instructions. Will be shown when customer chooses this payment method.', 'seatreg'); ?></p>
						<textarea class="form-control" data-id="new-custom-payment-description" placeholder="<?php esc_html_e('Enter payment instructions', 'seatreg')?>"></textarea>
						<br> 
						<button class="btn btn-default btn-sm" id="create-custom-payment" type="button"><?php esc_html_e('Add custom payment', 'seatreg'); ?></button>
					</div>
					<input type="hidden" name="custom-payments" />
				</div>
			</div>

			<div class="form-group">
				<div class="user-custom-field-options border-box option-box" style="border-bottom:none">
					<label><?php esc_html_e('Custom fields', 'seatreg'); ?></label>
					<p class="help-block">
						<?php esc_html_e('Custom fields allow you to ask extra information in bookings.', 'seatreg'); ?>
					</p>
					<input type="hidden" name="custom-fields" id="custom-fields" value=""/>

					<div class="existing-custom-fields">
						<?php if( $custLen > 0 ) : ?>
							
							<div style="margin-bottom: 6px"><?php esc_html_e('Existing custom fields', 'seatreg'); ?></div>
							<p>
								<?php esc_html_e('Custom fields you have already created', 'seatreg'); ?>
							</p>
							<?php
								for($i = 0; $i < $custLen; $i++) {
									if($custFields[$i]->type == 'sel') {
										$optLen = count($custFields[$i]->options);
										echo '<div class="custom-container" data-type="sel" data-label="'. esc_html($custFields[$i]->label) .'">';
											echo '<i class="custom-container-move custom-container-move-up">▲</i> <i class="custom-container-move custom-container-move-down">▼</i>';
											echo '<label><span class="l-text">', esc_html($custFields[$i]->label), '</span>';
												echo '<select id="custom-select-'. $i .'">';

													for($j = 0; $j < $optLen; $j++) {
														echo '<option><span class="option-value">', esc_html($custFields[$i]->options[$j]) ,'</span></option>';
													}

												echo '</select>';
											echo '</label>';
											echo '<div class="custom-container-controls">';
												echo ' <i class="fa fa-pencil edit-options mr-1 btn btn-primary" data-select-id="custom-select-'. $i .'"></i>';
												echo ' <i class="fa fa-times-circle remove-cust-item"></i>';
											echo '</div>';

										echo '</div>';

									}else if($custFields[$i]->type == 'text'){
										?>
											<div class="custom-container" data-type="text" data-label="<?php echo esc_attr($custFields[$i]->label); ?>">
												<i class="custom-container-move custom-container-move-up">▲</i> <i class="custom-container-move custom-container-move-down">▼</i>
												<label><span class="l-text"><?php echo esc_html($custFields[$i]->label); ?> </span>
													<input type="text" />
												</label>
												<div class="custom-container-controls">
													<span class="seatreg-ui-tooltip" title="<?php esc_attr_e('Prevents booking when same input value already provided', 'seatreg');?>"><?php esc_html_e('Unique', 'seatreg');?></span> <input type="checkbox" class="unique-input" <?php echo $custFields[$i]->unique ? 'checked' : ''; ?> />
													<span class="seatreg-ui-tooltip" title="<?php esc_attr_e('Make field optional', 'seatreg');?>"><?php esc_html_e('Optional', 'seatreg');?></span> 
													<input type="checkbox" class="optional-input" <?php echo isset($custFields[$i]->optional) && $custFields[$i]->optional ? 'checked' : ''; ?> />
													<i class="fa fa-times-circle remove-cust-item"></i>
												</div>

											</div>
										<?php

									}else if($custFields[$i]->type == 'check') {
										echo '<div class="custom-container" data-type="check" data-label="'. esc_attr($custFields[$i]->label) .'">';
											echo '<i class="custom-container-move custom-container-move-up">▲</i> <i class="custom-container-move custom-container-move-down">▼</i>';
											echo '<label><span class="l-text">', esc_html($custFields[$i]->label), '</span> <input type="checkbox" /></label>';
											echo '<div class="custom-container-controls">';
												echo '<i class="fa fa-times-circle remove-cust-item"></i>';
											echo '</div>';
										echo '</div>';
									}
								}
							?>
							
						<?php endif; ?>
					</div>

					<div class="cust-field-create">
						<div style="margin-bottom: 6px"><?php esc_html_e('New custom field', 'seatreg'); ?></div>
						<p>
							<?php esc_html_e('Create a new custom field', 'seatreg'); ?>
						</p>
						<div style="margin-left: 24px">
							<label><?php esc_html_e('Name', 'seatreg'); ?>:
								<input type="text" class="cust-input-label" maxlenght="30"/>
							</label>

							<label><?php esc_html_e('Type', 'seatreg'); ?>:
								<select class="custom-field-select">
									<option data-type="field"><?php esc_html_e('Text', 'seatreg'); ?></option>
									<option data-type="checkbox"><?php esc_html_e('Checkbox', 'seatreg'); ?></option>
									<option data-type="select"><?php esc_html_e('Select', 'seatreg'); ?></option> 
								</select>
							</label>

							<div class="select-radio-create">
								<ul class="existing-options"></ul>

								<label><?php esc_html_e('Option name', 'seatreg'); ?>
									<input type="text" class="option-name">
								</label>

								<button class="btn btn-default btn-sm add-select-option"><?php esc_html_e('Add option', 'seatreg'); ?></button>
								<div class="select-error"></div>
							</div>
							<button class="btn btn-default btn-sm apply-custom-field" type="button"><?php esc_html_e('Add custom field', 'seatreg'); ?></button>
						</div>
					</div>
				</div>	
			</div>

			<div class="form-group">
				<label><?php esc_html_e('Enable coupons', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Allow users to enter a coupon code during booking checkout.', 'seatreg'); ?></p>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="enable-coupons" name="enable_coupons" value="0" <?php echo $options[0]->enable_coupons == '1' ? 'checked':'' ?> >
						<?php esc_html_e('Turn on coupons', 'seatreg'); ?>
					</label>
				</div>
			</div>

			<div class="form-group" id="coupon-management">
				<label><?php esc_html_e('Coupons', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Create coupon codes to offer discounts. These will be applied to the total booking price.', 'seatreg'); ?></p>

				<div class="existing-coupons">
					<div style="margin-bottom: 10px"><?php esc_html_e('Existing coupons', 'seatreg'); ?></div>
					<?php if( count($coupons) == 0 ): ?>
						<p class="help-block"><?php esc_html_e('No coupons created', 'seatreg'); ?></p>
					<?php endif; ?>

					<?php foreach($coupons as $coupon): ?>
						<div class="coupon-box" data-uuid="<?php echo esc_attr($coupon->couponUUID); ?>">
							<div class="coupon-box__label"><?php esc_html_e('Coupon code', 'seatreg'); ?>:</div>
							<div class="coupon-box__value"><span data-target="coupon-code"><?php echo esc_html($coupon->couponCode); ?></span></div>
							<div class="coupon-box__label"><?php esc_html_e('Discount', 'seatreg'); ?>:</div>
							<div class="coupon-box__value">-<span data-target="discount-value"><?php echo esc_html($coupon->discountValue); ?></span></div>
							<div class="coupon-box__actions">
								<button class="btn btn-danger btn-sm" type="button" data-action="delete-coupon"><?php esc_html_e('Delete', 'seatreg'); ?></button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				
				<div class="coupon-create">
					<div style="margin-bottom: 6px"><?php esc_html_e('New coupon', 'seatreg'); ?></div>
					<p><?php esc_html_e('Create a new coupon', 'seatreg'); ?></p>
					<div class="new-coupon">
						<label class="new-coupon__label"><?php esc_html_e('Coupon code', 'seatreg'); ?>:</label>
						<input type="text" class="form-control" id="new-coupon-code" maxlength="20">
						<label class="new-coupon__label"><?php esc_html_e('Discount', 'seatreg'); ?>:</label>
						<input type="number" class="form-control" id="new-coupon-discount" placeholder="1-100" min="1" oninput="this.value = Math.abs(this.value) || null">
						<button class="btn btn-default btn-sm" type="button" data-action="add-coupon"><?php esc_html_e('Add', 'seatreg'); ?></button>
					</div>
				</div>
				<input type="hidden" name="coupons" value='<?php echo esc_attr(json_encode($coupons)); ?>' />
			</div>

			<div class="form-group">
				<label for="custom-styles"><?php esc_html_e('Custom styles', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Enter custom CSS rules for registration page', 'seatreg'); ?>.</p>
				<p class="help-block">
					<?php esc_html_e('For example:', 'seatreg'); ?><br />
					<?php esc_html_e('Change pending box color', 'seatreg'); ?>
					<code>.box[data-status=bron]{background-color:#6495ED !important;}</code><br />
					<?php esc_html_e('Change approved box color', 'seatreg'); ?>
					<code>.box[data-status=tak]{background-color:#1cdff2 !important;}</code><br />
					<?php esc_html_e('For round seats', 'seatreg'); ?>				
					<code>.box[data-seat] {border-radius:50%}</code>
	
				</p>

				<textarea class="form-control mb-2" id="custom-styles" name="custom-styles" placeholder="<?php esc_html_e('Enter CSS rules', 'seatreg')?>"><?php echo esc_html($options[0]->custom_styles); ?></textarea>

				<p class="help-block"><?php esc_html_e('Enter custom CSS rules for booking status page', 'seatreg'); ?>.</p>
				<textarea class="form-control mb-2" name="booking-status-custom-styles" placeholder="<?php esc_html_e('Enter CSS rules', 'seatreg')?>"><?php echo esc_html($options[0]->booking_status_page_custom_styles); ?></textarea>

				<p class="help-block"><?php esc_html_e('Enter custom CSS rules for booking confirm page', 'seatreg'); ?>.</p>
				<textarea class="form-control" name="booking-confirm-custom-styles" placeholder="<?php esc_html_e('Enter CSS rules', 'seatreg')?>"><?php echo esc_html($options[0]->booking_confirm_page_custom_styles); ?></textarea>
			</div>

			<div class="form-group">
				<label for="seat-selection-btn-text"><?php esc_html_e('Seat selection button text', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('By default the button that opens seat selection has text "open". You can change it if needed.', 'seatreg'); ?>
				</p>
				<input type="text" class="form-control" id="seat-selection-btn-text" name="seat-selection-btn-text" autocomplete="off" placeholder="<?php echo esc_html('Enter button text', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->seat_selection_btn_text); ?>"> 
			</div>

			<div class="form-group">
				<label for="public-api"><?php esc_html_e('SeatReg public API', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Enables external devices to read SeatReg data', 'seatreg'); ?>.<br />
					<?php echo sprintf( esc_html__( 'Use %s when making a connection using Android application.', 'seatreg' ), '<strong>' . esc_url(site_url()) . '</strong>'); ?>
				</p>
				<div style="margin-bottom:25px;">
					<a href="https://play.google.com/store/apps/details?id=com.seatreg" target="_blank">
						<i class="fa fa-android" aria-hidden="true" style="color: #A4C639"></i>
						<?php esc_html_e('SeatReg Android application'); ?>
					<a/>
				</div>

				<div class="checkbox">
					<label>
						<input type="checkbox" id="public-api" name="public-api" value="0" <?php echo $options[0]->public_api_enabled == '1' ? 'checked':'' ?> >
						<?php esc_html_e('Turn on public API', 'seatreg'); ?>
					</label>
				</div>

				<div id="public-api-tokens">				
					<?php foreach($publicApiTokens as $publicApiToken): ?>
						<div class="token-box" data-token="<?php echo esc_attr($publicApiToken->api_token); ?>" data-token-hidden="<?php echo esc_attr(showFirstLetters($publicApiToken->api_token, 2)); ?>">
							<div class="token">
								<?php echo esc_html(showFirstLetters($publicApiToken->api_token, 2)); ?>
							</div>
							<button class="btn btn-default btn-sm toggle-token" type="button">Show token</button>
							<div class="token-actions">
								<i class="fa fa-times-circle remove-token"></i>
							</diV>
						</div>
					<?php endforeach; ?>
				</div>
				<div style="margin-left: 24px; margin-bottom: 12px;">
					<button class="btn btn-default btn-sm" id="create-api-token"><?php esc_html_e('Create API token', 'seatreg'); ?></button>
				</div>
			</div>

			<input type='hidden' name='action' value='seatreg-form-submit' />
			<input type="hidden" name="registration_code" value="<?php echo esc_attr($options[0]->registration_code); ?>"/>

			<?php
				wp_nonce_field( 'seatreg-options-submit', 'seatreg-options-nonce' );
				submit_button( esc_html__('Save changes', 'seatreg'), 'primary', 'seatreg-settings-submit', false );
			?>

		</from>

	<?php
}

function seatreg_create_registration_from() {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/forms/create-registration-form.php' );
}

function seatreg_create_delete_registration_from($registrationCode) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/forms/delete-registration-form.php' );
}

function seatreg_generate_booking_manager() {
	$active_tab = null;
	$order = 'date';
	$searchTerm = '';
	
	if( SeatregDataValidation::tabsDataExists() ) {
	    $active_tab = sanitize_text_field($_GET[ 'tab' ]);
		$validation = SeatregDataValidation::validateTabData($active_tab );

		if( !$validation->valid ) {
			wp_die( esc_html($validation->errorMessage) );
		}
	}

	if( SeatregDataValidation::orderDataExists() ) {
		$order = sanitize_text_field($_GET[ 'o' ]);
		$validation = SeatregDataValidation::validateOrderData($order);

		if( !$validation->valid ) {
			wp_die( esc_html($validation->errorMessage) );
		}
	}

	if( SeatregDataValidation::searchDataExists() ) {
		$searchTerm = sanitize_text_field($_GET[ 's' ]);
		$validation = SeatregDataValidation::validateSearchData($searchTerm);

		if( !$validation->valid ) {
			wp_die( esc_html($validation->errorMessage) );
		}
	}

	$seatregData = seatreg_get_options($active_tab)[0];
	$calendarDate = assignIfNotEmpty( $_GET['calendar-date'], null );

	if( !$calendarDate ) {
		//When calendar mode enabled but not GET['calendar-date'], set initial date for today
		$calendarDate = SeatregCalendarService::getBookingFilteringDate($seatregData->using_calendar);
	}

	seatreg_generate_booking_manager_html($active_tab, $order, $searchTerm, $calendarDate);
}

//generate bookings list for manager
function seatreg_generate_booking_manager_html($active_tab, $order, $searchTerm, $calendarDate) {
	$seatregData = seatreg_get_options($active_tab);

	if( count($seatregData) == 0 ) {
		seatreg_no_registration_created_info();

		return;
	}

    $seatregData = $seatregData[0];
	$code = $seatregData->registration_code;
	$custom_fields = json_decode( isset($seatregData->custom_fields) ? $seatregData->custom_fields : '[]', true);
	$roomsData = json_decode($seatregData->registration_layout ?? '{}')->roomData ?? null;
	$cus_length = count(is_array($custom_fields) ? $custom_fields : []);
	$regId = $seatregData->id;
	$project_name_original = $seatregData->registration_name;
	$bookings1 = seatreg_get_specific_bookings($code, $order, $searchTerm, '1', $calendarDate);
	$bookings2 = seatreg_get_specific_bookings($code, $order, $searchTerm, '2', $calendarDate);
	$row_count = count($bookings1);
	$row_count2 = count($bookings2);
	$project_name = str_replace(' ', '_', $project_name_original);
	$usingSeats = $seatregData->using_seats === '1';
	$requireName = $seatregData->require_name;
	$currencyCode = $seatregData->paypal_currency_code;
	$zipExtensionLoaded = extension_loaded('zip');

	?>
		<div class='management-header'>
			<?php if($calendarDate) : ?>
				<div class="booking-manager-calendar-wrap">
					<label for="booking-manager-calendar-date"><?php esc_html_e('Date', 'seatreg'); ?> <i class="fa fa-calendar" aria-hidden="true"></i></label>
					<input id="booking-manager-calendar-date" value="<?php echo esc_html($calendarDate); ?>" />
					<input type="hidden" id="booking-manager-calendar-date-value" value="<?php echo esc_html($calendarDate); ?>" />
				</div>
			<?php endif; ?>
			
			<div class='registration-name'>
				<?php echo esc_html( wp_unslash($project_name_original) ); ?>
			</div>
			<?php if($row_count > 0): ?>
				<div class="pending-bookings-count">
					<?php echo sprintf(esc_html__('%s pending bookings', 'seatreg'), esc_html($row_count)); ?>
				</div>
			<?php endif; ?>
			<div class="management-extra-actions">
				<div class="add-booking" data-custom-fields='<?php echo json_encode($custom_fields); ?>' data-registration-code="<?php echo esc_attr($code); ?>">
					<span><?php esc_html_e('Add booking', 'seatreg'); ?></span>
					<i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>
				</div>
				<div class="import-bookings">
					<span><?php esc_html_e('Import bookings', 'seatreg'); ?></span>
					<i class="fa fa-toggle-down fa-lg" aria-hidden="true"></i>
				</div>
			</div>
		</div>

		<div class="input-group manager-search-wrap">
			<input type="hidden" id="seatreg-reg-code" value="<?php echo esc_attr($seatregData->registration_code); ?>" />
			<input type="text" class="form-control manager-search" placeholder="<?php esc_html_e('Search booking', 'seatreg'); ?>" maxlength="<?php echo esc_attr(SEATREG_REGISTRATION_SEARCH_MAX_LENGTH); ?>" value="<?php echo esc_attr($searchTerm); ?>"/>
			<div class="input-group-btn">
				<button class="btn btn-default search-button" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
			</div>
		</div>

		<a href="<?php echo esc_url(get_site_url() . '?seatreg=pdf&code=') . esc_attr($code); ?>" target="_blank" class="file-type-link pdf-link" data-file-type="pdf"><i class="fa fa-file-pdf-o" style="color:#D81313"></i> PDF</a>
    	<a href="<?php echo esc_url(get_site_url() . '?seatreg=xlsx&code=') . esc_attr($code); ?>" target="_blank" class="file-type-link xlsx-link" data-file-type="xlsx" data-zip-is-enabled="<?php echo $zipExtensionLoaded ? 'true': 'false'; ?>"><i class="fa fa-file-excel-o" style="color:#6FAA19"></i> XLSX</a>
    	<a href="<?php echo esc_url(get_site_url() . '?seatreg=text&code=') . esc_attr($code); ?>" class="file-type-link text-link" data-file-type="text"><i class="fa fa-file-text-o" style="color:#000"></i> Text</a>
		<a href="<?php echo esc_url(get_site_url() . '?seatreg=csv&code=') . esc_attr($code); ?>" target="_blank" class="file-type-link csv-link" data-file-type="csv"><i class="fa fa-file-text-o" style="color:#008000"></i> CSV</a>

		<div class="bg-color">
			<div class="tab-container">
				<ul class="etabs">
					<li class="tab"><a href="<?php echo '#' . sha1($project_name) . 'bron'; ?>"><?php esc_html_e('Pending', 'seatreg'); ?></a></li>
					<li class="tab"><a href="<?php echo '#' . sha1($project_name) . 'taken'; ?>"><?php esc_html_e('Approved','seatreg'); ?></a></li>
				</ul>
				<div class="panel-container differentBgColor">
					<div class="registration-manager-labels">
						<div class="seat-nr-box manager-box manager-box-link" data-order="nr">
							<?php $usingSeats ? esc_html_e('Seat', 'seatreg') : esc_html_e('Place', 'seatreg'); ?>
						</div>
						<div class="seat-room-box manager-box manager-box-link" data-order="room"><?php esc_html_e('Room', 'seatreg'); ?></div>
						<div class="seat-name-box manager-box manager-box-link" data-order="name"><?php esc_html_e('Name', 'seatreg'); ?></div>
						<div class="seat-name-box manager-box manager-box-link" data-order="date"><?php esc_html_e('Date', 'seatreg'); ?></div>
						<div class="seat-date-box manager-box manager-box-link" data-order="id"><?php esc_html_e('Booking id', 'seatreg'); ?></div>
						<div class="booking-status-box manager-box manager-box-link" data-order="payment-status"><?php esc_html_e('Payment status', 'seatreg'); ?></div>	
					</div>

				<?php
					echo '<div id="', sha1($project_name), 'bron" class="tab_container">';
		
					if($row_count == 0) {
						echo '<div class="notify-text">';
							$usingSeats ? esc_html_e('No pending bookings', 'seatreg') : esc_html_e('No pending places', 'seatreg');
						echo '</div>';
					}			
		
					foreach ($bookings1 as $row) {
						$custom_field_data = json_decode($row->custom_field_data, true);
						$booking = $row->booking_id;
						$registrationId = $row->id;
						$time = strtotime($row->booking_date);
						$myFormatForView = date("m-d-y", $row->booking_date);
						$bookingStatusUrl = seatreg_get_registration_status_url($code, $row->booking_id);
						$bookingDateString = SeatregTimeService::getDateStringFromUnix( $row->booking_date );
						$seatPrice = SeatregLayoutService::getSeatPriceFromLayout($row, $roomsData);
						$appliedCoponsString = SeatregCouponService::getAppliedCouponString(json_decode($row->applied_coupon), $currencyCode);

						echo '<div class="reg-seat-item" data-booking-id="'. esc_attr($booking) .'" data-email="'. esc_attr($row->email) .'" data-booker-email="'. esc_attr($row->booker_email) .'">';
							echo '<div class="seat-nr-box manager-box">', esc_html($row->seat_nr), '</div>';
							echo '<div class="seat-room-box manager-box" title="', esc_html($row->room_name),'">', esc_html($row->room_name),'</div>';
							echo '<div class="seat-name-box manager-box" title="' . esc_html($row->first_name) . ' '. esc_html($row->last_name).'"><input type="hidden" class="f-name" value="'.esc_html($row->first_name).'"/><input type="hidden" class="l-name" value="'. esc_html($row->last_name) .'" /><span class="full-name">', esc_html($row->first_name), ' ', esc_html($row->last_name), '</span></div>';
							echo '<div class="seat-date-box manager-box" title="', esc_attr($bookingDateString),'">',esc_html($myFormatForView),'</div>';
							echo "<div class='booking-id-box manager-box' title='", esc_html($row->booking_id), "'>",esc_html($row->booking_id),"</div>";
							echo "<div class='manager-box payment-status-box'>";
								echo esc_html($row->payment_status ? $row->payment_status : esc_html__('None', 'seatreg'));
							echo "</div>";
							echo '<button class="btn btn-outline-secondary btn-sm show-more-info">', esc_html__('More info','seatreg'), '</button>';
							echo "<span class='edit-btn' data-code='", esc_attr($code),"' data-booking='", esc_attr($booking),"' data-id='", esc_attr($registrationId),"'><i class='fa fa-pencil-square-o' aria-hidden='true'></i>", esc_html__('Edit','seatreg'), "</span>";
							echo '<div class="action-select">';
								echo "<label class='action-label'>", esc_html__('Remove','seatreg'), "<input type='checkbox' value='", esc_attr($row->booking_id),"' class='bron-action' data-action='del'/></label>";
								echo "<label class='action-label'>", esc_html__('Approve','seatreg'), "<input type='checkbox' value='", esc_attr($row->booking_id),"' class='bron-action'data-action='confirm'/></label>";
							echo '</div>';
		
							echo '<div class="more-info">';
								echo esc_html__('Status page','seatreg'), ': ', '<a href="', esc_url($bookingStatusUrl) ,'" target="_blank">', esc_url($bookingStatusUrl) ,'</a>';

								echo '<div>', esc_html__('Booking date','seatreg'), ': <span class="time-string">', esc_html($bookingDateString), '</span></div>';
								if($row->calendar_date) {
									echo '<div>', esc_html__('Calendar date', 'seatreg'), ': ', esc_html($row->calendar_date), '</div>';
								}
								echo '<div>', esc_html__('Email', 'seatreg'), ': ', esc_html($row->email), '</div>';

								if( $seatPrice ) {
									?>
										<div> 
											<?php
												echo esc_html__('Price', 'seatreg'), ': ',  esc_html($seatPrice->price), ' ', esc_html($seatregData->paypal_currency_code);

												if($seatPrice->description) {
													echo ' (', esc_html($seatPrice->description) , ')';
												}
											?>
											
										</div>
									<?php
								}
								echo '<div>', esc_html__('WP user ID', 'seatreg'), ': ', $row->logged_in_user_id ? esc_html($row->logged_in_user_id) : esc_html__('None', 'seatreg'), '</div>';
								echo '<div>', esc_html__('Used coupon', 'seatreg'), ': ', esc_html($appliedCoponsString), '</div>';

								for($i = 0; $i < $cus_length; $i++) {
									echo seatreg_customfield_with_value($custom_fields[$i], $custom_field_data);
								}
								echo seatreg_custom_approved_email_text($row, $seatregData);
								echo seatreg_view_booking_activity_btn($row);
								echo seatreg_generate_payment_section($row, $seatregData);
							echo '</div>';
							echo '<input type="hidden" class="booking-identification" value='. esc_attr($row->booking_id) .' />';
							echo '<input type="hidden" class="seat-id" value='. esc_attr($row->seat_id) .' />';
						echo '</div>'; 
					}
				
					if($row_count > 0) {
						echo "<div class='action-control' data-code='", esc_attr($code), "'>", esc_html__('OK','seatreg'), "</div>";
					}
					
					echo '</div>';
		
					echo '<div id="', sha1($project_name),'taken" class="tab_container active">';
		
					if($row_count2 == 0) {
						echo '<div class="notify-text">', esc_html__('No approved bookings', 'seatreg'), '</div>';
					}
		
					foreach ($bookings2 as $row) {
						$custom_field_data = json_decode($row->custom_field_data, true);
						$booking = $row->booking_id;
						$registrationId = $row->id;
						$time = strtotime($row->booking_date);
						$myFormatForView = date("m-d-y", $row->booking_date);
						$bookingStatusUrl = seatreg_get_registration_status_url($code, $row->booking_id);
						$bookingDateString = SeatregTimeService::getDateStringFromUnix( $row->booking_date );
						$seatPrice = SeatregLayoutService::getSeatPriceFromLayout($row, $roomsData);
						$appliedCoponsString = SeatregCouponService::getAppliedCouponString(json_decode($row->applied_coupon), $currencyCode);
		
						echo '<div class="reg-seat-item" data-booking-id="'. esc_attr($booking) .'" data-email="'. esc_attr($row->email) .'" data-booker-email="'. esc_attr($row->booker_email) .'">';
							echo '<div class="seat-nr-box manager-box">',esc_html( $row->seat_nr), '</div>';
							echo '<div class="seat-room-box manager-box" title="',esc_attr($row->room_name),'">', esc_html($row->room_name),'</div>';
							echo '<div class="seat-name-box manager-box" title="'.esc_attr($row->first_name). ' '. esc_html($row->last_name).'"><input type="hidden" class="f-name" value="'.esc_html($row->first_name).'"/><input type="hidden" class="l-name" value="'. esc_html($row->last_name) .'" /><span class="full-name">', esc_html($row->first_name), ' ', esc_html($row->last_name), '</span></div>';
							echo '<div class="seat-date-box manager-box" title="', esc_attr($bookingDateString),'">', esc_html($myFormatForView), '</div>';
							echo "<div class='booking-id-box manager-box' title='",esc_attr($row->booking_id), "'>",esc_html($row->booking_id),"</div>";
							echo "<div class='manager-box payment-status-box'>";
								echo esc_html($row->payment_status ? $row->payment_status : __('None', 'seatreg'));
							echo "</div>";
							echo '<button class="btn btn-outline-secondary btn-sm show-more-info">', esc_html__('More info','seatreg'), '</button>';
							echo "<span class='edit-btn' data-code='", esc_attr($code),"' data-booking='", esc_attr($booking),"' data-id='", esc_attr($registrationId),"'><i class='fa fa-pencil-square-o' aria-hidden='true'></i>", esc_html__('Edit','seatreg'), "</span>";
							echo '<div class="action-select">';
								echo "<label>", esc_html__('Remove', 'seatreg'), "<input type='checkbox' value='", esc_attr($row->booking_id),"' class='bron-action' data-action='del'/></label>";
								echo "<label>", esc_html__('Unapprove', 'seatreg'), "<input type='checkbox' value='", esc_attr($row->booking_id),"' class='bron-action' data-action='unapprove'/></label>";
							echo '</div>';
		
							echo '<div class="more-info">';
								echo esc_html__('Status page','seatreg'), ': ', '<a href="', esc_url($bookingStatusUrl) ,'" target="_blank">', esc_url($bookingStatusUrl), '</a>';
								echo '<div>', esc_html__('Booking date','seatreg'), ': <span class="time-string">', esc_html($bookingDateString), '</span></div>';
								if($row->calendar_date) {
									echo '<div>', esc_html__('Calendar date', 'seatreg'), ': ', esc_html($row->calendar_date), '</div>';
								}
								echo '<div>', esc_html__('Approval date', 'seatreg'), ': <span class="time-string">', esc_html(SeatregTimeService::getDateStringFromUnix( $row->booking_confirm_date )), '</span></div>';
								echo '<div>Email: ', esc_html( $row->email ), '</div>';

								if( $seatPrice ) {
									?>
										<div> 
											<?php
												echo esc_html__('Price', 'seatreg'), ': ',  esc_html($seatPrice->price), ' ' ,esc_html($seatregData->paypal_currency_code);

												if($seatPrice->description) {
													echo ' (', esc_html($seatPrice->description) , ')';
												}
											?>
											
										</div>
									<?php
								}
								echo '<div>', esc_html__('WP user ID', 'seatreg'), ': ', $row->logged_in_user_id ? esc_html($row->logged_in_user_id) : esc_html__('None', 'seatreg'), '</div>';
								echo '<div>', esc_html__('Used coupon', 'seatreg'), ': ', esc_html($appliedCoponsString), '</div>';
								
								for($i = 0; $i < $cus_length; $i++) {
									echo seatreg_customfield_with_value($custom_fields[$i], $custom_field_data);
								}
								echo seatreg_view_booking_activity_btn($row);
								echo seatreg_generate_payment_section($row, $seatregData);
							echo '</div>';
							echo '<input type="hidden" class="booking-identification" value='. esc_attr($row->booking_id) .' />';
							echo '<input type="hidden" class="seat-id" value='. esc_attr($row->seat_id) .' />';
						echo '</div>'; 
					}
		
					if($row_count2 > 0) {
						echo "<div class='action-control' data-code='", esc_attr($code), "'>", esc_html__('OK','seatreg'), "</div>";
					}
		
					echo '</div>';
				?>
				</div>
			</div>
		</div>
	<?php
	
	seatreg_booking_edit_modal($usingSeats, $calendarDate, $requireName);
	seatreg_add_booking_modal($usingSeats, $calendarDate, $requireName, $roomsData);
	seatreg_booking_activity_modal();
	seatreg_bookings_file_modal($custom_fields, $code, $calendarDate);
	seatreg_seat_id_modal($roomsData, $bookings1, $bookings2);
	seatreg_import_bookings_modal($code, $seatregData);
	seatreg_import_bookings_finalization_modal($code);
}

function seatreg_view_booking_activity_btn($booking) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/buttons/view-booking-activity-btn.php' );
	echo '<br>';
}

function seatreg_custom_approved_email_text($booking, $seatregData) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/sections/approved-booking-email-text.php' );
}

function seatreg_customfield_with_value($custom_field, $submitted_custom_data) {
	$cust_len = count(is_array($submitted_custom_data) ? $submitted_custom_data : []);
	$foundIt = false;

	echo '<div class="custom-field" data-type="'. esc_attr($custom_field['type']) .'" ><span class="custom-field-label">', esc_html($custom_field['label']), '</span>: ';

	for($j = 0; $j < $cust_len; $j++) {
		if( $submitted_custom_data[$j]['label'] === $custom_field['label'] ) {
			
			if( $custom_field['type'] === 'check' ) {
				if($submitted_custom_data[$j]['value'] === '1') {
					echo '<i class="fa fa-check custom-field-value" data-type="check" data-checked="true" aria-hidden="true"></i></div>';
				}else if($submitted_custom_data[$j]['value'] === '0') {
					echo '<i class="fa fa-times custom-field-value" data-type="check" data-checked="false" aria-hidden="true"></i></div>';
				}
			}
			if( $custom_field['type'] === 'text' ) {
				echo '<span class="custom-field-value" data-type="text">', esc_html($submitted_custom_data[$j]['value']), '</span></div>';
			}
			if( $custom_field['type'] === 'sel' ) {
				?>
					<span class="custom-field-value" data-type="sel" data-options='<?php echo json_encode($custom_field['options']); ?>'>
						<?php echo esc_html($submitted_custom_data[$j]['value']) ?>
					</span></div>
				<?php
			}
	
			$foundIt = true;
			break;
		}
	}

	if(!$foundIt) {
		?>
			<span class="custom-field-value" data-options='<?php echo json_encode($custom_field['options']); ?>'><?php echo esc_html__('Not set', 'seatreg'); ?></span></div>
		<?php
	}
}

function seatreg_generate_payment_logs($paymentLogs, $bookingId) {
	?>
	<div class="payment-log-wrap">
	<?php
		foreach ($paymentLogs as $paymentLog) {
			$logClassName = '';
			
			if($paymentLog->log_status === SEATREG_PAYMENT_LOG_ERROR || $paymentLog->log_status === SEATREG_PAYMENT_VALIDATION_FAILED) {
				$logClassName = 'error-log';
			}else if($paymentLog->log_status === SEATREG_PAYMENT_LOG_INFO) {
				$logClassName = 'info-log';
			}

			?>
				<div class="<?php echo esc_attr($logClassName); ?>">
					<?php esc_html_e($paymentLog->log_status); ?>
				</div>
				<div class="<?php echo esc_attr($logClassName); ?>">
					<?php esc_html_e($paymentLog->log_date); ?>
				</div>
				<div class="<?php echo esc_attr($logClassName); ?>">
					<?php esc_html_e($paymentLog->log_message); ?>
				</div>
			<?php
		}
		?>
		</div>
		<div class="add-payment-log-wrap">
			<select class="payment-log-type">
				<option value="<?php echo esc_attr(SEATREG_PAYMENT_LOG_OK); ?>"><?php esc_html_e('Ok', 'seatreg'); ?></option>
				<option value="<?php echo esc_attr(SEATREG_PAYMENT_LOG_ERROR); ?>"><?php esc_html_e('Error', 'seatreg'); ?></option>
				<option value="<?php echo esc_attr(SEATREG_PAYMENT_LOG_INFO); ?>"><?php esc_html_e('Info', 'seatreg'); ?></option>
			</select>
			<input type="text" class="payment-log-message" />
			<button class="btn btn-outline-secondary btn-sm" data-action="add-payment-log" data-booking-id="<?php echo esc_attr($bookingId); ?>"><?php esc_html_e('Add payment log', 'seatreg'); ?></button>
		</div>
		<?php
}

function seatreg_generate_payment_section($booking, $optionsData) {
	$hasPaymentEnabled = SeatregPaymentRepository::hasPaymentEnabled( $optionsData );
	$paymentLogs = SeatregPaymentLogRepository::getPaymentLogsByBookingId( $booking->booking_id );
	$bookingPaymentStatus = SeatregPaymentRepository::getBookingPaymentSatatus($booking);

	?>
		<div style="margin-bottom: 6px;">
			<strong>
				<?php esc_html_e('Payment status: ', 'seatreg'); ?>
			</strong>
			<span data-place="payment-status">
				<?php echo esc_html($bookingPaymentStatus->text); ?>
			</span>
		</div>
	<?php

	if( $bookingPaymentStatus->status === SEATREG_PAYMENT_COMPLETED || $bookingPaymentStatus->status === SEATREG_PAYMENT_REVERSED || $bookingPaymentStatus->status === SEATREG_PAYMENT_REFUNDED ) {
		?>
			<div>
				<?php echo sprintf(esc_html__('Payment of %s', 'seatreg'), esc_html("$booking->payment_total_price  $booking->payment_currency")); ?>
			</div>

			<div>
				<?php echo sprintf(esc_html__('Payment txn is %s', 'seatreg'), esc_html($booking->payment_txn_id)); ?> 
			</div>

			<div class="mb-2">
				<?php echo sprintf(esc_html__('Payment date is %s', 'seatreg'), esc_html($booking->payment_update_date)); ?>
			</div>
		<?php
	}

	?>
		<form>
			<p>
				<?php esc_html_e('You can change the payment status manually if needed. PayPal and Stripe payments will do it automatically.', 'seatreg'); ?>
			</p>
			<div style="display:flex;gap:12px;" class="mb-4">
				<select class="form-control" style="width: 250px" name="payment-status">
					<option value="<?php echo esc_attr(SEATREG_PAYMENT_COMPLETED); ?>" <?php echo $bookingPaymentStatus->status === SEATREG_PAYMENT_COMPLETED ? 'selected' : '';?>><?php esc_html_e('Completed', 'seatreg'); ?></option>
					<option value="<?php echo esc_attr(SEATREG_PAYMENT_PROCESSING); ?>" <?php echo $bookingPaymentStatus->status === SEATREG_PAYMENT_PROCESSING ? 'selected' : '';?>><?php esc_html_e('Processing', 'seatreg'); ?></option>
					<option value="<?php echo esc_attr(SEATREG_PAYMENT_REFUNDED); ?>" <?php echo $bookingPaymentStatus->status === SEATREG_PAYMENT_REFUNDED ? 'selected' : '';?>><?php esc_html_e('Refunded', 'seatreg'); ?></option>
					<option value="<?php echo esc_attr(SEATREG_PAYMENT_REVERSED); ?>" <?php echo $bookingPaymentStatus->status === SEATREG_PAYMENT_REVERSED ? 'selected' : '';?>><?php esc_html_e('Reversed', 'seatreg'); ?></option>
					<option value="<?php echo esc_attr(SEATREG_PAYMENT_ERROR); ?>" <?php echo $bookingPaymentStatus->status === SEATREG_PAYMENT_ERROR ? 'selected' : '';?>><?php esc_html_e('Error', 'seatreg'); ?></option>
					<option value="<?php echo esc_attr(SEATREG_PAYMENT_VALIDATION_FAILED); ?>" <?php echo $bookingPaymentStatus->status === SEATREG_PAYMENT_VALIDATION_FAILED ? 'selected' : '';?>><?php esc_html_e('Validation failure', 'seatreg'); ?></option>
					<option value="<?php echo esc_attr(SEATREG_PAYMENT_DEPOSIT_PAYED); ?>" <?php echo $bookingPaymentStatus->status === SEATREG_PAYMENT_DEPOSIT_PAYED ? 'selected' : '';?>><?php esc_html_e('Deposit Payed', 'seatreg'); ?></option>
					<option value="<?php echo esc_attr(SEATREG_PAYMENT_NONE); ?>" <?php echo $bookingPaymentStatus->status === SEATREG_PAYMENT_NONE ? 'selected' : '';?>><?php esc_html_e('None', 'seatreg'); ?></option>
				</select>
				<button class="btn btn-default btn-sm" data-action="change-payment-status" data-booking-id><?php esc_html_e('Change status', 'seatreg'); ?></button>
			</div>
		</form>
	<?php
	
	if( $hasPaymentEnabled || count($paymentLogs) > 0 ) {
		echo '<div style="margin-bottom: 6px;"><strong>', esc_html__('Payment logs', 'seatreg') ,'</strong></div>';
		echo seatreg_generate_payment_logs( $paymentLogs, $booking->booking_id );
	}
}

function seatreg_add_booking_modal($usingSeats, $calendarDate, $requireName, $roomsData) {
	if(!$roomsData) {
		return;
	}

	$roomNames = array_map(function($roomData) {
		return $roomData->room->name;
	}, $roomsData);
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/add-booking-modal.php' );
}

function seatreg_booking_edit_modal($usingSeats, $calendarDate, $requireName) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/booking-edit-modal.php' );
}

function seatreg_registration_logs_modal() {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/registration-logs-modal.php' );
}

function seatreg_more_items_modal($registrationCode) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/more-items-modal.php' );
}

function seatreg_copy_registration_modal($registrationCode) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/copy-registration-modal.php' );
}

function seatreg_shortcode_modal($registrationCode) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/shortcode-modal.php' );
}

function seatreg_booking_activity_modal() {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/booking-activity-modal.php' );
}

function seatreg_bookings_file_modal($customFields, $registrationCode, $calendarDate) {
	if( !is_array($customFields) ) {
		$customFields = [];
	}
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/bookings-file-modal.php' );
}

function seatreg_seat_id_modal($roomsData, $pendingBookings, $approvedBookings) {
	$combinedBookings = array_merge($pendingBookings, $approvedBookings);
	$bookingIds = array_map(function($booking) {
		return $booking->seat_id;
	}, $combinedBookings);

	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/seat-id-modal.php' );
}

function seatreg_import_bookings_modal($seatregCode, $seatregData) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/import-bookings-modal.php' );
}

function seatreg_import_bookings_finalization_modal($seatregCode) {
	require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/modals/import-bookings-finalization-modal.php' );
}

//generate tabs
function seatreg_generate_tabs($targetPage) {
	$active_tab = null;
	$registrations = SeatregRegistrationRepository::getRegistrations();

	if( SeatregDataValidation::tabsDataExists() ) {
	    $active_tab = sanitize_text_field( $_GET['tab'] );
		$validation = SeatregDataValidation::validateTabData( $active_tab );

		if( !$validation->valid ) {
			wp_die( esc_html($validation->errorMessage) );
		}
	}else {
		if( count($registrations) !== 0 ) {
			$active_tab = $registrations[0]->registration_code;
		}
	} 

	?>

	<h2 class="nav-tab-wrapper"> 
		<?php foreach($registrations as $key => $value): ?>
			<a href="?page=<?php echo esc_html($targetPage); ?>&tab=<?php echo esc_html($value->registration_code); ?>" class="nav-tab <?php echo $active_tab == $value->registration_code ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( wp_unslash($value->registration_name) ); ?>
			</a>
		<?php endforeach; ?>
	</h2>
	
	<?php
}

//echo out booking info and status
function seatreg_echo_booking($registrationCode, $bookingId) {
	$registration = SeatregRegistrationRepository::getRegistrationWithOptionsByCode($registrationCode);

	if($registration) {
		$bookings = SeatregBookingRepository::getBookingsByRegistrationCodeAndBookingId($registrationCode, $bookingId);
		$roomData = json_decode($registration->registration_layout)->roomData;
		$options = SeatregOptionsRepository::getOptionsByRegistrationCode($registrationCode);
		$couponsEnabled = SeatregCouponRepository::areCouponsEnabled($registrationCode);
		$appliedCoupon = SeatregCouponRepository::getBookingAppliedCoupon($bookingId);

		$registrationCustomFields = json_decode( isset($registration->custom_fields) ? $registration->custom_fields : '[]');
	
		foreach ($bookings as $booking) {
			$booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($roomData, $booking->room_uuid);
		}

		if(count($bookings)) {
			$bookingStatus = $bookings[0]->status;
			echo '<h2>', esc_html( wp_unslash($registration->registration_name) ), '</h2>';
			
			if($options && $options->pending_expiration && $bookingStatus === '1') {
				$hasPaymentEntry = SeatregBookingService::checkIfBookingHasPaymentEntry($bookings[0]->booking_id);
				$bookingDateTimestampInMinutes = ceil($bookings[0]->booking_date / 60);
				$bookingWillBeDeletedTimestamp = $bookingDateTimestampInMinutes + (int)$options->pending_expiration;
				$bookingTimeToLive = floor($bookingWillBeDeletedTimestamp - (time() / 60));

				if($bookingTimeToLive > 0 && !$hasPaymentEntry) {
					echo '<h3 style="color:red">', sprintf(esc_html__('This pending booking will be deleted in about %s minutes if not approved', 'seatreg'), esc_html($bookingTimeToLive)), '</h3>';
				}
			}
			echo '<div style="margin-bottom: 6px"><strong>', esc_html__('Booking id', 'seatreg'), '</strong>: ' , esc_html($bookingId),'</div>';
			echo '<div><strong>', esc_html__('Booking status', 'seatreg'), '</strong>: ' , esc_html(SeatregBookingService::getBookingStatusText($bookingStatus)),'</div>';

			echo '<div style="margin: 16px 0px 20px">';
			echo SeatregBookingService::generateBookingTable($registrationCustomFields, $bookings, $registration);
			echo '</div>';

			if ( $couponsEnabled && $appliedCoupon ) {
				echo '<div style="margin-bottom: 20px;">', esc_html__('Applied coupon', 'seatreg') ,': <strong>' . esc_html($appliedCoupon->couponCode) . '</strong> (-' . esc_html($appliedCoupon->discountValue) . esc_html($registration->paypal_currency_code) . ' ' . esc_html__('discount', 'seatreg') . ')</div>';
			}

			if( SeatregBookingService::getBookingTotalCost($bookingId, $registration->registration_layout) > 0 ) {
				echo SeatregBookingService::generatePaymentTable($bookingId, $couponsEnabled, $appliedCoupon);
				echo '<br>';
			}
		}else {
			esc_html_e('Booking not found.', 'seatreg');
			die();
		}
	}else {
		esc_html_e('Registration does not exist', 'seatreg');
		die();
	}
}

/*
======================================================================================================================================================
Registration logic and math
======================================================================================================================================================
*/

//return registration code
function seatreg_generate_registration_code() {
	return substr(md5( microtime() ), 0, 10);
}

//return room info. How many bron and taken seats are in a rooms
function seatreg_get_room_seat_info($struct, $bronRegistrations, $takenRegistrations) {
	$bronLength = count($bronRegistrations);
	$takenLength = count($takenRegistrations);
	$regStructure = ($struct !== null) ? json_decode($struct)->roomData : null;
	$roomCount = count(is_array($regStructure) ? $regStructure : []);
	$howManyRegSeats = 0;
	$howManyOpenSeats = 0;
	$howManyBronSeats= 0;
	$howManyTakenSeats = 0;
	$howManyCustomBoxes = 0;
	$statsArray = array();
	$roomsInfo = array();

	for($i = 0; $i < $roomCount; $i++) {
		$roomBoxes = $regStructure[$i]->boxes;

		//find how many bron seats in this room
		$roomBoxCount = count($roomBoxes);
		$roomRegSeats = 0;  //how many reg seats
		$roomOpenSeats = 0; //how many open reg seats
		$roomTakenSeats = 0; //how many taken seats
		$roomBronSeats = 0;	//bron seats
		$roomCustomBoxes = 0;

		for($k = 0; $k < $bronLength; $k++) {  
			if( $regStructure[$i]->room->uuid == $bronRegistrations[$k]->room_uuid ) { //find how many bron seats in this room
				$roomBronSeats = $bronRegistrations[$k]->total;
				$howManyBronSeats += $bronRegistrations[$k]->total;

				break;
			}
		}

		for($k = 0; $k < $takenLength; $k++) {
			if($regStructure[$i]->room->uuid == $takenRegistrations[$k]->room_uuid) { //find how many taken seats in this room
				$roomTakenSeats = $takenRegistrations[$k]->total;
				$howManyTakenSeats += $takenRegistrations[$k]->total;

				break;
			}
		}
		
		for($j = 0; $j < $roomBoxCount; $j++) {
			if($roomBoxes[$j]->canRegister == 'true') {
				if($roomBoxes[$j]->status == 'noStatus') {
					$howManyOpenSeats++;
					$roomOpenSeats++;
				}
				
				$howManyRegSeats++;
				$roomRegSeats++;
			}else {
				$howManyCustomBoxes++;
				$roomCustomBoxes++;
			}
		}

		$roomsInfo[] = array(
			'roomName' => $regStructure[$i]->room->name,
			'roomSeatsTotal' => $roomRegSeats,
			'roomOpenSeats' => $roomRegSeats - $roomTakenSeats - $roomBronSeats,
			'roomTakenSeats' => $roomTakenSeats,
			'roomBronSeats' => $roomBronSeats,
			'roomCustomBoxes' => $roomCustomBoxes
		);
	}

	$statsArray['seatsTotal'] = $howManyRegSeats;
	$statsArray['openSeats'] = $howManyOpenSeats - $howManyBronSeats - $howManyTakenSeats;
	$statsArray['bronSeats'] = $howManyBronSeats;
	$statsArray['takenSeats'] = $howManyTakenSeats;
	$statsArray['roomCount'] = $roomCount;
	$statsArray['roomsInfo'] = $roomsInfo;

	return $statsArray;
}

function seatreg_validate_del_conf_booking($code, $bookingActions, $calendarDate) {
	$registration = seatreg_get_registration_data($code)[0];
	$structure = json_decode($registration->registration_layout)->roomData;
	$bookingActionLength = count($bookingActions);
	$seat_id;
	$allCorrect = true;

	$resp = array();

	//step 1. check if room exists and contains seat with nr
	foreach ($bookingActions as $key => $value) {
		$step1Desision = seatreg_check_room_and_seat($structure, $value->room_name, $value->seat_nr);

		if( $step1Desision['status'] != 'ok') {
			$allCorrect = false;
			$resp['status'] = $step1Desision['status'];
			$resp['text'] = $step1Desision['text'];

			break;
		}
	}

	
	if(!$allCorrect) {
		return $resp;
	}

	//step2. check whether seat is already pending or confirmed
	$bookings = SeatregBookingRepository::getConfirmedAndApprovedBookingsByRegistrationCode( $code, $calendarDate );
	foreach($bookings as $booking) {
		$booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($structure, $booking->room_uuid);
	}

	$notBooked = true;

	foreach ($bookings as $booking) {
		foreach ($bookingActions as $bookingAction) {
			if($booking->seat_nr == $bookingAction->seat_nr && $booking->room_name == $bookingAction->room_name && $booking->status === "2" && $bookingAction->action != 'del' && $bookingAction->action != 'unapprove') {
				$notBooked = false;
				$resp['text'] = esc_html__('Seat ', 'seatreg') . esc_html($bookingAction->seat_nr) . esc_html__(' from room ', 'seatreg') . esc_html($bookingAction->room_name) . esc_html__(' is already booked', 'seatreg');

				break 2;
			}
		}
	}

	if($notBooked) {
		$resp['status'] = 'ok';

		return $resp;
	}else {
		$resp['status'] = 'seat-booked';

		return $resp;
	}
}

function seatreg_valdiate_add_booking_with_manager($code, $data, $calendarDate) {
	$registration = seatreg_get_options($code)[0];
	$structure = json_decode($registration->registration_layout)->roomData;
	$allCorrect = true;
	$resp = array();
    $resp['status'] = 'ok';
	$layoutValidation = SeatregLayoutService::validateRoomAndSeatId($structure, $data->roomName, $data->seatId );

	if( !$layoutValidation->valid ) {
		$allCorrect = false;
		$resp['status'] = $layoutValidation->searchStatus;
		$resp['text'] = $layoutValidation->errorText;

		return $resp;
	}else {
		$seat = SeatregLayoutService::getBoxFromLayout($structure, $data->seatId);
		$prefix = property_exists($seat, 'prefix') ? $seat->prefix : '';

		$resp['seatId'] = $data->seatId;
		$resp['seatNr'] = $prefix . $seat->seat;
		$resp['roomUUID'] = SeatregLayoutService::getRoomUUID($structure, $data->roomName);
	}

	$bookings = SeatregBookingRepository::getConfirmedAndApprovedBookingsByRegistrationCode($code, $calendarDate);
	$notBooked = true;

	foreach ($bookings as $booking) {
		$booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($structure, $booking->room_uuid);

		if($booking->seat_id === $data->seatId && $booking->room_name === $data->roomName && ($booking->status === "2" || $booking->status === "1") ) {
			$notBooked = false;
			$resp['status'] = 'seat-booked';
			$resp['text'] = esc_html__('Seat ID ', 'seatreg') . esc_html($data->seatId) . esc_html__(' from room ', 'seatreg') . esc_html($booking->room_name) . esc_html__(' is already booked', 'seatreg');

			break;
		}
	}

	$data->room_uuid = $resp['roomUUID'];
	$data->seat_id = $resp['seatId'];
	$data->multi_price_selection = $data->multiPriceSelection;
	if( $data->multiPriceSelection && SeatregLayoutService::checkIfMultiPriceUUIDExists($data, $structure) === false ) {
		$resp['status'] = 'seat-price-not-found';
		$resp['text'] = esc_html__('Selected price was not found', 'seatreg');
	}

	return $resp;
}

//for booking edit
function seatreg_validate_edit_booking($code, $data) {
	$registration = seatreg_get_options($code)[0];
	$structure = json_decode($registration->registration_layout)->roomData;
	$allCorrect = true;
    $resp = array();
    $resp['status'] = 'ok';
	$layoutValidation = SeatregLayoutService::validateRoomAndSeatId($structure, $data->roomName, $data->seatId );
	$customFieldValidation = SeatregDataValidation::validateCustomFieldManagerSubmit($data->editCustomField, $registration->custom_fields, $registration->registration_code);

	if( $registration->using_calendar ) {
		if( !property_exists($data, 'calendarDate') ) {
			$allCorrect = false;
			$resp['status'] = 'date not provided';

			return $resp;
		}

		if( !preg_match(CALENDAR_DATE_PICKER_REGEX, $data->calendarDate )  ) {
			$allCorrect = false;
			$resp['status'] = 'date not correct';

			return $resp;
		}
	}

	if( !$layoutValidation->valid ) {
			$allCorrect = false;
			$resp['status'] = $layoutValidation->searchStatus;
			$resp['text'] = $layoutValidation->errorText;

			return $resp;
	}else if( !$customFieldValidation->valid ) {
		$allCorrect = false;
		$resp['status'] = 'custom field validation failed';
		$resp['text'] = $customFieldValidation->errorMessage;

	}else {
		$seat = SeatregLayoutService::getBoxFromLayout($structure, $data->seatId);
		$prefix = property_exists($seat, 'prefix') ? $seat->prefix : '';
		$resp['newSeatId'] = $data->seatId;
		$resp['newSeatNr'] = $prefix . $seat->seat;
		$resp['roomUUID'] = SeatregLayoutService::getRoomUUID($structure, $data->roomName);
	}
	$bookings = SeatregBookingRepository::getConfirmedAndApprovedBookingsByRegistrationCode( $code, $data->calendarDate );
	$notBooked = true;

	foreach ($bookings as $booking) {
		if($booking->booking_id == $data->bookingId) {

			continue;
		}
		$booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($structure, $booking->room_uuid);

		if($booking->seat_id === $data->seatId && $booking->room_name === $data->roomName && ($booking->status === "2" || $booking->status === "1") ) {
			$notBooked = false;
			$resp['status'] = 'seat-booked';
			$resp['text'] = esc_html__('Seat ', 'seatreg') . esc_html($data->roomName) . esc_html__(' from room ', 'seatreg') . esc_html($booking->room_name) . esc_html__(' is already booked', 'seatreg');

			break;
		}
	}

	if ( !SeatregDataValidation::validateEmailAddress($data->bookerEmail) ) {
		$allCorrect = false;
		$resp['status'] = 'edit-email-not-valid';
		$resp['text'] = esc_html__('Provided email address is not valid', 'seatreg');
	}

	if ( $data->email && !SeatregDataValidation::validateEmailAddress($data->email) ) {
		$allCorrect = false;
		$resp['status'] = 'edit-email-not-valid';
		$resp['text'] = esc_html__('Provided email address is not valid', 'seatreg');
	}
	
	return $resp;
}

//check if booking room and seat are present in registration layout
function seatreg_check_room_and_seat($registrationLayout, $bookingRoomName, $bookingSeatNr) {
	$layoutLength = count($registrationLayout);
	$allCorrect = false;
	$status = array();
	$searchStatus = '';
	$errorText = '';

	for($i = 0; $i < $layoutLength; $i++) {
		$searchStatus = 'room-searching';
		$errorText = esc_html__('Room ','seatreg') . esc_html($bookingRoomName) . esc_html__(' dose not exist!', 'seatreg');

		if($registrationLayout[$i]->room->name == $bookingRoomName) {
			$searchStatus = 'seat-nr-searching';
			$errorText = esc_html__('Seat ','seatreg') . esc_html($bookingSeatNr) . esc_html__(' dose not exist in ', 'seatreg') . esc_html($bookingRoomName);
			$boxLen = count($registrationLayout[$i]->boxes);
			
			for($k = 0; $k < $boxLen; $k++) {
				$prefix = property_exists($registrationLayout[$i]->boxes[$k], 'prefix') ? $registrationLayout[$i]->boxes[$k]->prefix : '';

				if($registrationLayout[$i]->boxes[$k]->canRegister == 'true' && $prefix . $registrationLayout[$i]->boxes[$k]->seat == $bookingSeatNr) {
					$searchStatus = 'ok';
					$allCorrect = true;
					$seat_id = $registrationLayout[$i]->boxes[$k]->id;
					$status['newSeatId'] = $seat_id;
					$status['oldSeatNr'] = $bookingSeatNr;
					$status['roomUUID'] = $registrationLayout[$i]->room->uuid;

					break;
				}
			}
			break;
		}
	}

	if(!$allCorrect) {
		$status['status'] = $searchStatus;
		$status['text'] = $errorText;

		return $status;
	}else {
		$status['status'] = $searchStatus;

		return $status;
	}
}

function seatreg_get_registration_status_url($registrationCode, $bookingId) {
	return get_site_url() . '?seatreg=booking-status&registration=' . $registrationCode . '&id=' . $bookingId;
}

/*
======================================================================================================================================================
Database stuff
======================================================================================================================================================
*/

//plugin init
function seatreg_set_up_db() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $seatreg_db_table_names;
	global $wpdb;
	$seatreg_db_current_version = get_option( "seatreg_db_current_version" );

    $charset_collate = $wpdb->get_charset_collate() . ' ENGINE = innoDB';

	if ( SEATREG_DB_VERSION != $seatreg_db_current_version ) {
		$sql = "CREATE TABLE $seatreg_db_table_names->table_seatreg (
			id int(11) NOT NULL AUTO_INCREMENT,
			registration_code varchar(40) NOT NULL,
			registration_name varchar(255) NOT NULL,
			registration_create_timestamp int(11) DEFAULT NULL,
			registration_layout mediumtext,
			is_deleted tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY registration_code (registration_code)
		  ) $charset_collate;";
	  
		  dbDelta( $sql );
	  
		$sql2 = "CREATE TABLE $seatreg_db_table_names->table_seatreg_options (
			id int(11) NOT NULL AUTO_INCREMENT,
			registration_code varchar(40) NOT NULL,
			registration_start_timestamp varchar(13) DEFAULT NULL,
			registration_end_timestamp varchar(13) DEFAULT NULL,
			custom_fields text,
			seats_at_once int(11) NOT NULL DEFAULT 1,
			gmail_required tinyint(1) DEFAULT 0,
			registration_open tinyint(1) NOT NULL DEFAULT 1,
			use_pending tinyint(1) NOT NULL DEFAULT 1,
			registration_password varchar(255) DEFAULT NULL,
			notify_new_bookings tinyint(1) NOT NULL DEFAULT 1,
			notify_booker_pending_booking tinyint(1) NOT NULL DEFAULT 1,
			send_approved_booking_email tinyint(1) NOT NULL DEFAULT 1,
			send_approved_booking_email_qr_code varchar(255) DEFAULT NULL,
			show_bookings tinyint(1) NOT NULL DEFAULT 0,
			show_bookings_data_in_registration varchar(255) DEFAULT NULL,
			payment_text text,
			info text,
			registration_close_reason text,
			booking_email_confirm tinyint(1) NOT NULL DEFAULT 1,
			paypal_payments tinyint(1) NOT NULL DEFAULT 0,
			paypal_business_email varchar(255) DEFAULT NULL,
			paypal_button_id varchar(255) DEFAULT NULL,
			paypal_currency_code varchar(3) DEFAULT NULL,
			paypal_sandbox_mode tinyint(1) NOT NULL DEFAULT 0,
			payment_completed_set_booking_confirmed tinyint(1) NOT NULL DEFAULT 0,
			pending_expiration int(11) DEFAULT NULL,
			verification_email_subject varchar(255) DEFAULT NULL,
			email_verification_template text,
			pending_booking_email_subject varchar(255) DEFAULT NULL,
			pending_booking_email_template text,
			approved_booking_email_subject varchar(255) DEFAULT NULL,
			approved_booking_email_template text,
			stripe_payments tinyint(1) NOT NULL DEFAULT 0,
			stripe_api_key varchar(255) DEFAULT NULL,
			payment_completed_set_booking_confirmed_stripe tinyint(1) NOT NULL DEFAULT 0,
			stripe_webhook_secret varchar(255) DEFAULT NULL,
			using_seats tinyint(1) NOT NULL DEFAULT 1,
			email_from_address varchar(255) DEFAULT NULL,
			booking_email_limit int(11) DEFAULT NULL,
			using_calendar tinyint(1) NOT NULL DEFAULT 0,
			calendar_dates text,
			controlled_scroll tinyint(0) NOT NULL DEFAULT 0,
			custom_payment tinyint(0) NOT NULL DEFAULT 0,
			custom_payment_title varchar(255) DEFAULT NULL,
			custom_payment_description text,
			custom_styles text,
			public_api_enabled tinyint(0) NOT NULL DEFAULT 0,
			custom_footer_text text,
			seat_selection_btn_text varchar(255) DEFAULT NULL,
			custom_payments text,
			booking_status_page_custom_styles text,
			booking_confirm_page_custom_styles text,
			registration_start_time text,
			registration_end_time text,
			show_pending_booking_pdf tinyint(1) NOT NULL DEFAULT 0,
			show_approved_booking_pdf tinyint(1) NOT NULL DEFAULT 1,
			booking_qr_code_input varchar(255) DEFAULT 'booking-id',
			notification_email varchar(255) DEFAULT NULL,
			booking_redirect_status_page tinyint(1) NOT NULL DEFAULT 0,
			require_wp_login tinyint(0) NOT NULL DEFAULT 0,
			require_name tinyint(0) NOT NULL DEFAULT 1,
			show_info_button tinyint(0) NOT NULL DEFAULT 1,
			zoom_on_top tinyint(0) NOT NULL DEFAULT 0,
			wp_user_booking_limit INT DEFAULT NULL,
			wp_user_bookings_seat_limit INT DEFAULT NULL,
			one_person_checkout tinyint(0) NOT NULL DEFAULT 0,
			automatic_booking_confirm_dialog tinyint(0) NOT NULL DEFAULT 0,
			enable_coupons tinyint(0) NOT NULL DEFAULT 0,
			coupons text,
			PRIMARY KEY  (id)
		) $charset_collate;";
	  
		  dbDelta( $sql2 );
	  
		$sql3 = "CREATE TABLE $seatreg_db_table_names->table_seatreg_bookings (
			id int(11) NOT NULL AUTO_INCREMENT,
			registration_code varchar(40) NOT NULL,
			first_name varchar(255) NOT NULL,
			last_name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			seat_id varchar(255) NOT NULL,
			seat_nr varchar(255) NOT NULL,
			room_uuid varchar(255) NOT NULL,
			booking_date int(11) DEFAULT NULL,
			booking_confirm_date int(11) DEFAULT NULL,
			custom_field_data text,
			status int(2) NOT NULL DEFAULT 0,
			booking_id varchar(40) NOT NULL,
			conf_code char(40) NOT NULL,
			booker_email varchar(255) NOT NULL,
			seat_passwords text,
			multi_price_selection varchar(255) DEFAULT NULL,
			calendar_date DATE DEFAULT NULL,
			logged_in_user_id int DEFAULT NULL,
			custom_text_for_approved_email text,
			applied_coupon text,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql3 );

		$sql4 = "CREATE TABLE $seatreg_db_table_names->table_seatreg_payments (
			id int(11) NOT NULL AUTO_INCREMENT,
			booking_id varchar(40) NOT NULL,
			payment_start_date TIMESTAMP DEFAULT NOW(),
			payment_update_date TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT NOW(),
			payment_status varchar(255) NOT NULL,
			payment_currency varchar(3) DEFAULT NULL,
			payment_total_price int(11) DEFAULT NULL,
			payment_txn_id varchar(255), 
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql4 );

		$sql5 = "CREATE TABLE $seatreg_db_table_names->table_seatreg_payments_log (
			id int(11) NOT NULL AUTO_INCREMENT,
			booking_id varchar(40) NOT NULL,
			log_date TIMESTAMP DEFAULT NOW(),
			log_message text,
			log_status enum('ok', 'error', 'info', 'validation_failure') NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql5 );

		$sql6 = "CREATE TABLE $seatreg_db_table_names->table_seatreg_activity_log (
			id int(11) NOT NULL AUTO_INCREMENT,
			log_type enum('booking', 'map', 'settings') NOT NULL,
			relation_id varchar(40) NOT NULL,
			log_date TIMESTAMP DEFAULT NOW(),
			log_message text,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql6 );

		$sql7 = "CREATE TABLE $seatreg_db_table_names->table_seatreg_api_tokens (
			id int(11) NOT NULL AUTO_INCREMENT,
			registration_code varchar(40) NOT NULL,
			api_token varchar(255) NOT NULL,
			create_date TIMESTAMP DEFAULT NOW(),
			PRIMARY KEY  (id),
			UNIQUE KEY api_token (api_token)
		) $charset_collate;";

		dbDelta( $sql7 );

		update_option( "seatreg_db_current_version", SEATREG_DB_VERSION );
	}
}

//return specific registration and its data if registration code provided.
function seatreg_get_registration_data($code) {
	global $wpdb;
	global $seatreg_db_table_names;

	$registration = $wpdb->get_results( $wpdb->prepare(
		"SELECT a.*, b.paypal_payments, b.stripe_payments, b.custom_payment, b.using_seats, b.using_calendar, b.calendar_dates, b.custom_payments
		FROM $seatreg_db_table_names->table_seatreg AS a
		INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
		ON a.registration_code = b.registration_code
		WHERE a.registration_code = %s",
		$code
	) );
	
	return $registration;
}

// return data related to booking
function seatreg_get_data_related_to_booking($bookingId) {
	global $wpdb;
	global $seatreg_db_table_names;

	$data = $wpdb->get_row( $wpdb->prepare(
		"SELECT a.*, b.*
		FROM $seatreg_db_table_names->table_seatreg AS a
		INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
		ON a.registration_code = b.registration_code
		WHERE a.registration_code = (SELECT registration_code FROM $seatreg_db_table_names->table_seatreg_bookings WHERE booking_id = %s LIMIT 1)",
		$bookingId
	) );

	if($data) {
		$payment = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_payments
			 WHERE booking_id = %s",
			$bookingId
		) );

		if($payment) {
			$data->payment_status = $payment->payment_status;
		}else {
			$data->payment_status = null;
		}
	}

	return $data;
}

//return uploaded images
function seatreg_get_registration_uploaded_images($code) {
	$uploadedImages = array();
	$filePath = SEATREG_TEMP_FOLDER_DIR . '/room_images/' . $code . '/'; 

	if(file_exists($filePath)) {
		$dir = opendir($filePath);
		while ($file = readdir($dir)) { 
		   if (preg_match("/.png/",$file) || preg_match("/.jpg/",$file) || preg_match("/.gif/",$file) || preg_match("/.jpeg/",$file)) { 
				$img = new stdClass();
				$img->file = $file;
				$img->size = getimagesize($filePath . $file);
		   		$uploadedImages[] = $img;
		   }
		}
	}
	return $uploadedImages;
}

function seatreg_order_bookings_by_room_name($a, $b) {
	return strcmp($a->room_name, $b->room_name);
}

//return bookins
function seatreg_get_specific_bookings( $code, $order, $searchTerm, $bookingStatus, $calendarDate ) {
	global $wpdb;
	global $seatreg_db_table_names;

	switch($order) {
		case 'date':
			$order = 'booking_date, seat_nr';
			break;
		case 'nr':
			$order = 'seat_nr';
			break;
		case 'name':
			$order = 'first_name';
			break;
		case 'room':
			$order = 'room_uuid, seat_nr';
			break;
		case 'id':
			$order = 'booking_id, seat_nr';
			break;
		case 'payment-status':
			$order = 'payment_status';
			break;
	}

	if( $calendarDate ) {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, b.payment_status, b.payment_currency, b.payment_total_price, b.payment_update_date, b.payment_txn_id, c.paypal_payments
			FROM $seatreg_db_table_names->table_seatreg_bookings AS a
			LEFT JOIN $seatreg_db_table_names->table_seatreg_payments AS b
			ON a.booking_id = b.booking_id
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS c
			ON a.registration_code = c.registration_code
			WHERE a.registration_code = %s
			AND a.status = $bookingStatus
			AND a.calendar_date = %s
			ORDER BY $order",
			$code,
			$calendarDate
		));
	}else {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, b.payment_status, b.payment_currency, b.payment_total_price, b.payment_update_date, b.payment_txn_id, c.paypal_payments
			FROM $seatreg_db_table_names->table_seatreg_bookings AS a
			LEFT JOIN $seatreg_db_table_names->table_seatreg_payments AS b
			ON a.booking_id = b.booking_id
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS c
			ON a.registration_code = c.registration_code
			WHERE a.registration_code = %s
			AND a.status = $bookingStatus
			AND a.calendar_date IS NULL
			ORDER BY $order",
			$code
		));
	}
	
	$registration = SeatregRegistrationRepository::getRegistrationByCode($code);

	if($registration->registration_layout !== null) {
		$roomData = json_decode($registration->registration_layout)->roomData;

		foreach ($bookings as $booking) {
			$booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($roomData, $booking->room_uuid);
		}
	}
	
	if($order === 'room_uuid, seat_nr') {
		usort($bookings, "seatreg_order_bookings_by_room_name");
	}

	if($searchTerm !== '') {
		$bookings = array_filter($bookings, function($booking) use($searchTerm) {
			if( stripos($booking->booking_id, $searchTerm) !== false ) {
				return true;
			}
			if( stripos($booking->room_name, $searchTerm) !== false ) {
				return true;
			}
			if( stripos($booking->seat_nr, $searchTerm) !== false ) {
				return true;
			}
			if( stripos($booking->first_name, $searchTerm) !== false ) {
				return true;
			}
			if( stripos($booking->last_name, $searchTerm) !== false ) {
				return true;
			}
			if( stripos($booking->email, $searchTerm) !== false ) {
				return true;
			}

			return false;
		});
	}

	return $bookings;
}

//return specific registration options
function seatreg_get_options($code) {
	global $wpdb;
	global $seatreg_db_table_names;

	if($code != null) {
		$options = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, b.* 
			FROM $seatreg_db_table_names->table_seatreg AS a
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
			ON a.registration_code = b.registration_code
			WHERE a.registration_code = %s
			AND a.is_deleted = false",
			$code
		) );
	}else {
		$options = $wpdb->get_results( 
			"SELECT a.*, b.* 
			FROM $seatreg_db_table_names->table_seatreg AS a
			INNER JOIN $seatreg_db_table_names->table_seatreg_options AS b
			ON a.registration_code = b.registration_code
			WHERE a.is_deleted = false
			ORDER BY a.registration_create_timestamp
			LIMIT 1"
		);
	}

	return $options;
}

function seatreg_create_new_registration($newRegistrationName) {
	global $seatreg_db_table_names;
	global $wpdb;

    $registrationCode = seatreg_generate_registration_code();
    $status = $wpdb->insert(
    	$seatreg_db_table_names->table_seatreg,
    	array(
    		'registration_name' => $newRegistrationName,
    		'registration_code' => $registrationCode,
			'registration_create_timestamp' => time()
    	),
    	'%s'
    );

    if($status === 1) {
    	$status = $wpdb->insert(
    		$seatreg_db_table_names->table_seatreg_options,
    		array(
    			'registration_code' => $registrationCode
    		),
    		'%s'
    	);
		seatreg_add_activity_log('map', $registrationCode, 'Registration created');
    }

    if($status === 1) {
    	return true;
    }else {
    	return false;
    }
}

//confirm, delete booking
function seatreg_confirm_or_delete_booking($action, $regCode, $calendarDate) {
	global $seatreg_db_table_names;
	global $wpdb;

	if($action->action == 'conf') {

		$wpdb->update( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array( 
				'status' => SEATREG_BOOKING_APPROVED,
				'booking_confirm_date' => time()
			), 
			array(
				'booking_id' => $action->booking_id, 
				'calendar_date' => $calendarDate
			), 
			'%s',
			'%s'
		);
		seatreg_add_activity_log('booking', $action->booking_id, 'Booking approved (Booking manager)');
		SeatregActionsService::triggerBookingApprovedActionViaManager($action->booking_id);

	}else if($action->action == 'del') {

		$wpdb->delete( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array(
				'booking_id' => $action->booking_id, 
				'seat_id' => $action->seat_id,
				'calendar_date' => $calendarDate
			), 
			'%s'
		);
		seatreg_add_activity_log('booking', $action->booking_id, sprintf('Seat %s from room %s deleted from booking (Booking manager)', $action->seat_nr, $action->room_name));
		SeatregActionsService::triggerBookingRemovedAction($action->booking_id);

	}else if($action->action == 'unapprove') {

		$wpdb->update( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array( 
				'status' => SEATREG_BOOKING_PENDING,
				'booking_confirm_date' => null
			), 
			array(
				'booking_id' => $action->booking_id,
				'calendar_date' => $calendarDate
			), 
			'%s',
			'%s'
		);
		seatreg_add_activity_log('booking', $action->booking_id, 'Booking unapproved (Booking manager)');
		SeatregActionsService::triggerBookingPendingActionViaManager($action->booking_id);
	}
}

//edit booking
function seatreg_edit_booking($custom_fields, $seat_nr, $room_uuid, $f_name, $l_name, $booking_id, $seat_id, $id, $calendarDate, $bookerEmail, $email) {
	global $seatreg_db_table_names;
	global $wpdb;

	$updateFields = array(
        'first_name' => $f_name,
        'last_name' => $l_name,
        'seat_nr' => $seat_nr,
        'room_uuid' => $room_uuid,
        'custom_field_data' => $custom_fields,
        'seat_id' => $seat_id,
        'calendar_date' => $calendarDate
    );

    if ($email) {
        $updateFields['email'] = $email;
    }

	$status = $wpdb->update( 
		$seatreg_db_table_names->table_seatreg_bookings,
		$updateFields, 
		array(
			'booking_id' => $booking_id,
			'id' => $id,
		),
		'%s'
	);

	$status2 = $wpdb->update( 
		$seatreg_db_table_names->table_seatreg_bookings,
		array(
			'booker_email' => $bookerEmail,
		), 
		array(
			'booking_id' => $booking_id,
		),
		'%s'
	);

	if ($status === false || $status2 === false) {
        return false;
    }
	
	return true;
}

function seatreg_add_booking($firstName, $lastName, $email, $customFields, $seatNr, $seatId, $roomUuid, $registrationCode, $bookingStatus, $bookingId, $confCode, $calendarDate = null, $multiPriceSelection = null) {
	global $wpdb;
	global $seatreg_db_table_names;
	$currentTimeStamp = time();

	$inserted = $wpdb->insert( 
		$seatreg_db_table_names->table_seatreg_bookings, 
		array(
			'registration_code' => $registrationCode, 
			'first_name' => $firstName, 
			'last_name' => $lastName,
			'email' => $email,
			'seat_id' => $seatId,
			'seat_nr' => $seatNr,
			'room_uuid' => $roomUuid,
			'conf_code' => $confCode, 
			'custom_field_data' => json_encode($customFields, JSON_UNESCAPED_UNICODE),
			'booking_id' => $bookingId,
			'status' => $bookingStatus,
			'booking_date' => $currentTimeStamp,
			'booking_confirm_date' => $bookingStatus === '2' ? $currentTimeStamp : null,
			'booker_email' => $email,
			'conf_code' => $confCode, 
			'status' => $bookingStatus,
			'calendar_date' => $calendarDate,
			'multi_price_selection' => $multiPriceSelection ?? null,
		), 
		'%s'	
	);

	if($inserted) {
		return true;
	}

	return false;
}


//for generating pdf, xlsx and text
function seatreg_get_data_for_booking_file($code, $whatToShow, $calendarDate) {
	global $seatreg_db_table_names;
	global $wpdb;

	if( $whatToShow == 'all' ) {

		if( $calendarDate ) {
			$bookings = $wpdb->get_results( $wpdb->prepare(
				"SELECT a.*, b.payment_status, b.payment_currency, b.payment_total_price, b.payment_txn_id
				FROM $seatreg_db_table_names->table_seatreg_bookings AS a 
				LEFT JOIN $seatreg_db_table_names->table_seatreg_payments AS b
				ON a.booking_id = b.booking_id
				WHERE a.registration_code = %s
				AND a.status IN (1,2)
				AND a.calendar_date = %s
				ORDER BY room_uuid, seat_nr",
				$code,
				$calendarDate
			) );
		}else {
			$bookings = $wpdb->get_results( $wpdb->prepare(
				"SELECT a.*, b.payment_status, b.payment_currency, b.payment_total_price, b.payment_txn_id
				FROM $seatreg_db_table_names->table_seatreg_bookings AS a 
				LEFT JOIN $seatreg_db_table_names->table_seatreg_payments AS b
				ON a.booking_id = b.booking_id
				WHERE a.registration_code = %s
				AND a.status IN (1,2)
				AND a.calendar_date IS NULL
				ORDER BY room_uuid, seat_nr",
				$code
			) );
		}

	}else if( $whatToShow == 'pending' ) {

		if( $calendarDate ) {
			$bookings = $wpdb->get_results( $wpdb->prepare(
				"SELECT a.*, b.payment_status, b.payment_currency, b.payment_total_price, b.payment_txn_id
				FROM $seatreg_db_table_names->table_seatreg_bookings AS a 
				LEFT JOIN $seatreg_db_table_names->table_seatreg_payments AS b
				ON a.booking_id = b.booking_id
				WHERE a.registration_code = %s
				AND a.status = 1
				AND a.calendar_date = %s
				ORDER BY room_uuid, seat_nr",
				$code,
				$calendarDate
			) );
		}else {
			$bookings = $wpdb->get_results( $wpdb->prepare(
				"SELECT a.*, b.payment_status, b.payment_currency, b.payment_total_price, b.payment_txn_id
				FROM $seatreg_db_table_names->table_seatreg_bookings AS a 
				LEFT JOIN $seatreg_db_table_names->table_seatreg_payments AS b
				ON a.booking_id = b.booking_id
				WHERE a.registration_code = %s
				AND a.status = 1
				AND a.calendar_date IS NULL
				ORDER BY room_uuid, seat_nr",
				$code
			) );
		}

	}else {
		if( $calendarDate ) {
			$bookings = $wpdb->get_results( $wpdb->prepare(
				"SELECT a.*, b.payment_status, b.payment_currency, b.payment_total_price, b.payment_txn_id
				FROM $seatreg_db_table_names->table_seatreg_bookings AS a
				LEFT JOIN $seatreg_db_table_names->table_seatreg_payments AS b
				ON a.booking_id = b.booking_id
				WHERE a.registration_code = %s
				AND a.status = 2
				AND a.calendar_date = %s
				ORDER BY room_uuid, seat_nr",
				$code,
				$calendarDate
			) );
		}else {
			$bookings = $wpdb->get_results( $wpdb->prepare(
				"SELECT a.*, b.payment_status, b.payment_currency, b.payment_total_price, b.payment_txn_id
				FROM $seatreg_db_table_names->table_seatreg_bookings AS a
				LEFT JOIN $seatreg_db_table_names->table_seatreg_payments AS b
				ON a.booking_id = b.booking_id
				WHERE a.registration_code = %s
				AND a.status = 2
				AND a.calendar_date IS NULL
				ORDER BY room_uuid, seat_nr",
				$code
			) );
		}
	}

	$registration = SeatregRegistrationRepository::getRegistrationByCode( $code );

	if($registration->registration_layout !== null) {
		$roomData = json_decode($registration->registration_layout)->roomData;

		foreach($bookings as $booking) {
			$booking->room_name = SeatregRegistrationService::getRoomNameFromLayout($roomData, $booking->room_uuid);
		}
	}
	
	return $bookings;
}

function seatreg_add_activity_log($type, $relation_id, $message, $includeCurrentUser = true) {
	global $seatreg_db_table_names;
	global $wpdb;

	if($includeCurrentUser) {
		$current_user = wp_get_current_user();
		$current_user_displayname = $current_user->data->display_name;
		$current_user_id = $current_user->data->ID;
		$message .= " by $current_user_displayname (id $current_user_id)";
	}

	$wpdb->insert(
		$seatreg_db_table_names->table_seatreg_activity_log,
		array(
			'log_type' => $type,
			'relation_id' => $relation_id,
			'log_message' => $message
		),
		'%s'
	);
}

/*
======================================================================================================================================================
Admin form submit stuff
======================================================================================================================================================
*/

//handle new registration create
add_action('admin_post_seatreg_create_submit', 'seatreg_create_submit_handler'); 
function seatreg_create_submit_handler() {
	seatreg_nonce_check();
	$registrationName = $_POST['new-registration-name']; 

	if( empty($registrationName) ) {
		wp_die('Registration name not provided');
	}

	if( $registrationName === '' ) {
		wp_die('Please provide registration name');
	}

	$nameValidation = SeatregDataValidation::validateRegistrationName($registrationName);

	if( !$nameValidation->valid ) {
		wp_die( esc_html($nameValidation->errorMessage) );
	}

	if( seatreg_create_new_registration($registrationName) ) {
		wp_redirect( SEATREG_HOME_PAGE );

		die();
	}else {
		wp_die( esc_html_e('Something went wrong while creating a new registration', 'seatreg') );
	}
}

add_action('admin_post_seatreg_copy_registration', 'seatreg_copy_registration_handler'); 
function seatreg_copy_registration_handler() {
	seatreg_nonce_check();

	if( empty($_POST['new-registration-name']) || $_POST['new-registration-name'] === '' ) {
		wp_die('Please provide registration name');
	}

	if( empty($_POST['registration_code']) ) {
		wp_die('Code is missing');
	}

	$registrationName = sanitize_text_field($_POST['new-registration-name']); 
	$nameValidation = SeatregDataValidation::validateRegistrationName($registrationName);

	if( !$nameValidation->valid ) {
		wp_die( esc_html($nameValidation->errorMessage) );
	}

	if( SeatregRegistrationService::copyRegistration( $_POST['registration_code'], $_POST['new-registration-name'] ) ) {
		wp_redirect( SEATREG_HOME_PAGE );

		die();
	}else {
		wp_die( esc_html_e('Something went wrong while coping registration', 'seatreg') );
	}
}

//handle registration delete
add_action('admin_post_seatreg_delete_registration', 'seatreg_delete_registration_handler'); 
function seatreg_delete_registration_handler() {
	global $wpdb;
	global $seatreg_db_table_names;
	seatreg_nonce_check();

	$status = $wpdb->update(
		"$seatreg_db_table_names->table_seatreg",
		array(
			'is_deleted' => 1,
		),
		array(
			'registration_code' => sanitize_text_field($_POST['registration-code'])
		),
		'%s'
	);

	if( $status ) {
		seatreg_add_activity_log('map', sanitize_text_field($_POST['registration-code']), 'Registration deleted');
		wp_redirect( SEATREG_HOME_PAGE );
		die();
	}else {
		wp_die( esc_html_e('Something went wrong while deleting a registration', 'seatreg') );
	}
}

function seatreg_update() {
	global $wpdb;
	global $seatreg_db_table_names;

	if( !SeatregDataValidation::registrationCodeDataExists($_POST) ) {
		wp_die('Missing registration code');
	}

	if( !SeatregDataValidation::registrationNameDataExists($_POST) ) {
		wp_die('Missing registration name');
	}

	if( !SeatregDataValidation::validateEmailVerificationTemplate() ) {
		wp_die('Email Verification template not valid');
	}

	if( !SeatregDataValidation::validatePendingBookingEmailTemplate() ) {
		wp_die('Pending booking email template not valid');
	}

	if( !SeatregDataValidation::validateApprovedBookingEmailTemplate() ) {
		wp_die('Approved booking email template not valid');
	}

	if( !empty($_POST['email-from']) && !SeatregDataValidation::validateEmailAddress($_POST['email-from']) ) {
		wp_die('From email not valid');
	}

	if( !empty($_POST['paypal-currency-code']) && !SeatregDataValidation::validateCurrencyCode($_POST['paypal-currency-code']) ) {
		wp_die('Currency code not valid');
	}

	if( !SeatregDataValidation::calendarDatesValdiation($_POST) ) {
		wp_die('Calendar dates are not valid');
	}

	if( isset($_POST['paypal-payments']) && ($_POST['paypal-business-email'] === "" || $_POST['paypal-button-id'] === "" || $_POST['paypal-currency-code'] === "" ) ) {
		wp_die('Missing PayPal configuration');
	}

	if( isset($_POST['stripe-payments']) && ($_POST['stripe-api-key'] === "" || $_POST['paypal-currency-code'] === "") ) {
		wp_die('Missing Stripe configuration');
	}

	$registrationName = sanitize_text_field($_POST['registration-name']);
	$registrationNameValidation = SeatregDataValidation::validateRegistrationName($registrationName);
	$selectedShowBookingData = null;

	if( !$registrationNameValidation->valid ) {
		wp_die( esc_html($registrationNameValidation->errorMessage) );
	}

	$customFileds = stripslashes_deep( $_POST['custom-fields'] );
	$customFiledsValidation = SeatregDataValidation::validateCustomFieldCreation($customFileds);

	if( !$customFiledsValidation->valid ) {
		wp_die( esc_html($customFiledsValidation->errorMessage) );
	}

	$customPayments = stripslashes_deep( $_POST['custom-payments'] );
	$customPaymentsValidation = SeatregDataValidation::validateCustomPaymentCreation($customPayments);

	if( !$customPaymentsValidation->valid ) {
		wp_die( esc_html($customPaymentsValidation->errorMessage) );
	}

	$coupons = stripslashes_deep( $_POST['coupons'] );
	$couponsValidation = SeatregDataValidation::validateCouponCreation($coupons);

	if( !$couponsValidation->valid ) {
		wp_die( esc_html($couponsValidation->errorMessage) );
	}

	if(!isset($_POST['gmail-required'])) {
		$_POST['gmail-required'] = 0;
	}else {
		$_POST['gmail-required'] = 1;
	}
	
	if(!isset($_POST['registration-status'])) {
		$_POST['registration-status'] = 0;
	}

	if(!isset($_POST['using-seats'])) {
		$_POST['using-seats'] = 0;
	}

	if(!isset($_POST['using-calendar'])) {
		$_POST['using-calendar'] = 0;
	}

	if(!isset($_POST['use-pending'])) {
		$_POST['use-pending'] = 0;
	}else {
		$_POST['use-pending'] = 1;
	}

	if(!isset($_POST['show-registration-bookings'])) {
		$_POST['show-registration-bookings'] = 0;  
	}else {
		$_POST['show-registration-bookings'] = 1;
	}

	if(!isset($_POST['email-confirm'])) {
		$_POST['email-confirm'] = 0;  
	}else {
		$_POST['email-confirm'] = 1;
	}

	if(!isset($_POST['booking-notification'])) {
		$_POST['booking-notification'] = 0;
	}else {
		$_POST['booking-notification'] = 1;
	}

	if(!isset($_POST['booker-pending-booking-notification'])) {
		$_POST['booker-pending-booking-notification'] = 0;
	}else {
		$_POST['booker-pending-booking-notification'] = 1;
	}

	if(!isset($_POST['paypal-payments'])) {
		$_POST['paypal-payments'] = 0;  
	}else {
		$_POST['paypal-payments'] = 1;
	}

	if(!isset($_POST['paypal-sandbox-mode'])) {
		$_POST['paypal-sandbox-mode'] = 0;  
	}else {
		$_POST['paypal-sandbox-mode'] = 1;
	}

	if(!isset($_POST['payment-mark-confirmed'])) {
		$_POST['payment-mark-confirmed'] = 0;  
	}else {
		$_POST['payment-mark-confirmed'] = 1;
	}

	if(!isset($_POST['approved-booking-email'])) {
		$_POST['approved-booking-email'] = 0;  
	}else {
		$_POST['approved-booking-email'] = 1;
	}

	if(!isset($_POST['stripe-payments'])) {
		$_POST['stripe-payments'] = 0;  
	}else {
		$_POST['stripe-payments'] = 1;
	}

	if(!isset($_POST['payment-mark-confirmed-stripe'])) {
		$_POST['payment-mark-confirmed-stripe'] = 0;  
	}else {
		$_POST['payment-mark-confirmed-stripe'] = 1;
	}
	
	if(!empty($_POST['show-booking-data-registration'])) {
		$selectedShowBookingData = is_array($_POST['show-booking-data-registration']) ? implode(',', $_POST['show-booking-data-registration']) : null;
	}

	if(empty($_POST['pending-expiration'])) {
		$_POST['pending-expiration'] = null;
	}

	if(empty($_POST['bookings-email-limit'])) {
		$_POST['bookings-email-limit'] = null;
	}

	if(!isset($_POST['controlled-scroll'])) {
		$_POST['controlled-scroll'] = 0;
	}else {
		$_POST['controlled-scroll'] = 1;
	}

	if(!isset($_POST['custom-payment'])) {
		$_POST['custom-payment'] = 0;
	}else {
		$_POST['custom-payment'] = 1;
	}

	if( !empty($_POST['booking-status-custom-styles']) ) {
		$_POST['booking-status-custom-styles'] = wp_kses($_POST['booking-status-custom-styles'], array( '\'', '\"' ));
	}else {
		$_POST['booking-status-custom-styles'] = null;
	}

	if( !empty($_POST['booking_confirm_page_custom_styles']) ) {
		$_POST['booking_confirm_page_custom_styles'] = wp_kses($_POST['booking_confirm_page_custom_styles'], array( '\'', '\"' ));
	}else {
		$_POST['booking_confirm_page_custom_styles'] = null;
	}
	

	if( !empty($_POST['custom-styles']) ) {
		$_POST['custom-styles'] = wp_kses($_POST['custom-styles'], array( '\'', '\"' ));
	}else {
		$_POST['custom-styles'] = null;
	}

	if( !isset($_POST['public-api']) ) {
		$_POST['public-api'] = 0;
	}else {
		$_POST['public-api'] = 1;
	}

	if( !empty($_POST['custom-footer-text']) ) {
	    $_POST['custom-footer-text'] = wp_kses_post(wpautop($_POST['custom-footer-text']));
	}else {
		$_POST['custom-footer-text'] = null;
	}

	if( !isset($_POST['show-pending-booking-pdf']) ) {
		$_POST['show-pending-booking-pdf'] = 0;
	}else {
		$_POST['show-pending-booking-pdf'] = 1;
	}

	if( !isset($_POST['show-approved-booking-pdf']) ) {
		$_POST['show-approved-booking-pdf'] = 0;
	}else {
		$_POST['show-approved-booking-pdf'] = 1;
	}

	if( !isset($_POST['booking-redirect-status-page']) ) {
		$_POST['booking-redirect-status-page'] = 0;
	}else {
		$_POST['booking-redirect-status-page'] = 1;
	}

	if( !isset($_POST['show-info-button']) ) {
		$_POST['show-info-button'] = 0;
	}else {
		$_POST['show-info-button'] = 1;
	}

	
	if( !isset($_POST['zoom-on-top']) ) {
		$_POST['zoom-on-top'] = 0;
	}else {
		$_POST['zoom-on-top'] = 1;
	}
	
	if( !isset($_POST['require-wp-login']) ) {
		$_POST['require-wp-login'] = 0;
	}else {
		$_POST['require-wp-login'] = 1;
	}

	if( !isset($_POST['require-name']) ) {
		$_POST['require-name'] = 0;
	}else {
		$_POST['require-name'] = 1;
	}

	if( !isset($_POST['one-person-checkout']) ) {
		$_POST['one-person-checkout'] = 0;
	}else {
		$_POST['one-person-checkout'] = 1;
	}

	if( !isset($_POST['automatic-booking-confirm-dialog']) ) {
		$_POST['automatic-booking-confirm-dialog'] = 0;
	}else {
		$_POST['automatic-booking-confirm-dialog'] = 1;
	}

	if( !isset($_POST['enable_coupons']) ) {
		$_POST['enable_coupons'] = 0;
	}else {
		$_POST['enable_coupons'] = 1;
	}

	$oldOptions = SeatregOptionsRepository::getOptionsByRegistrationCode(sanitize_text_field($_POST['registration_code']));
	$dbUpdated = true;

	try {
        $wpdb->query('START TRANSACTION');

		$status1 = $wpdb->update(
			"$seatreg_db_table_names->table_seatreg_options",
			array(
				'registration_start_timestamp' => $_POST['start-timestamp'] == '' ? null : sanitize_text_field($_POST['start-timestamp']),
				'registration_end_timestamp' => $_POST['end-timestamp'] == '' ? null : sanitize_text_field($_POST['end-timestamp']),
				'seats_at_once' => sanitize_text_field($_POST['registration-max-seats']),
				'gmail_required' => sanitize_text_field($_POST['gmail-required']),
				'registration_open' => sanitize_text_field($_POST['registration-status']),
				'use_pending' => sanitize_text_field($_POST['use-pending']),
				'registration_password' => $_POST['registration-password'] == '' ? null : sanitize_text_field($_POST['registration-password']),
				'notify_new_bookings' => $_POST['booking-notification'] ? sanitize_text_field($_POST['booking-notification']) : null,
				'notify_booker_pending_booking' => $_POST['booker-pending-booking-notification'] ? sanitize_text_field($_POST['booker-pending-booking-notification']) : null,
				'show_bookings_data_in_registration' => $selectedShowBookingData,
				'payment_text' => $_POST['payment-instructions'] == '' ? null : $_POST['payment-instructions'],
				'info' => sanitize_text_field($_POST['registration-info-text']),
				'registration_close_reason' => sanitize_text_field($_POST['registration-close-reason']),
				'custom_fields' => $customFileds,
				'booking_email_confirm' => sanitize_text_field($_POST['email-confirm']),
				'paypal_payments' => $_POST['paypal-payments'],
				'paypal_business_email' => sanitize_text_field($_POST['paypal-business-email']),
				'paypal_button_id' => sanitize_text_field($_POST['paypal-button-id']),
				'paypal_currency_code' => sanitize_text_field(strtoupper($_POST['paypal-currency-code'])),
				'paypal_sandbox_mode' => $_POST['paypal-sandbox-mode'],
				'payment_completed_set_booking_confirmed' => $_POST['payment-mark-confirmed'],
				'send_approved_booking_email' => $_POST['approved-booking-email'],
				'send_approved_booking_email_qr_code' => ( !isset($_POST['approved-booking-email-qr-code']) || $_POST['approved-booking-email-qr-code'] === '') ? null : sanitize_text_field($_POST['approved-booking-email-qr-code']),
				'pending_expiration' => $_POST['pending-expiration'],
				'verification_email_subject' => $_POST['verification-email-subject'] === '' ? null : $_POST['verification-email-subject'],
				'email_verification_template' => $_POST['email-verification-template'] === '' ? null : SeatregSanitizationService::sanitizeEmailTemplate($_POST['email-verification-template']),
				'pending_booking_email_subject' => $_POST['pending-booking-email-subject'] === '' ? null : $_POST['pending-booking-email-subject'],
				'pending_booking_email_template' => $_POST['pendin-booking-email-template'] === '' ? null : SeatregSanitizationService::sanitizeEmailTemplate($_POST['pendin-booking-email-template']),
				'approved_booking_email_subject' => $_POST['approved-booking-email-subject'] === '' ? null : $_POST['approved-booking-email-subject'],
				'approved_booking_email_template' => $_POST['approved-booking-email-template'] === '' ? null : SeatregSanitizationService::sanitizeEmailTemplate($_POST['approved-booking-email-template']),
				'stripe_payments' => $_POST['stripe-payments'],
				'stripe_api_key' => $_POST['stripe-api-key'],
				'payment_completed_set_booking_confirmed_stripe' => $_POST['payment-mark-confirmed-stripe'],
				'using_seats' => $_POST['using-seats'],
				'email_from_address' => !empty($_POST['email-from']) ? $_POST['email-from'] : null,
				'booking_email_limit' => $_POST['bookings-email-limit'],
				'using_calendar' => $_POST['using-calendar'],
				'calendar_dates' => !empty($_POST['calendar-dates']) ? $_POST['calendar-dates'] : $oldOptions->calendar_dates,
				'controlled_scroll' => $_POST['controlled-scroll'],
				'custom_payment' => $_POST['custom-payment'],
				'custom_payment_title' => $_POST['custom-payment-title'],
				'custom_payment_description' => $_POST['custom-payment-description'],
				'custom_styles' => $_POST['custom-styles'],
				'public_api_enabled' => $_POST['public-api'],
				'custom_footer_text' => $_POST['custom-footer-text'],
				'seat_selection_btn_text' => !empty($_POST['seat-selection-btn-text']) ? $_POST['seat-selection-btn-text'] : null,
				'custom_payments' => $customPayments,
				'booking_status_page_custom_styles' => $_POST['booking-status-custom-styles'],
				'booking_confirm_page_custom_styles' => $_POST['booking-confirm-custom-styles'],
				'registration_start_time' => $_POST['registration-start-time'] === '' ? null : $_POST['registration-start-time'],
				'registration_end_time' => $_POST['registration-end-time'] === '' ? null : $_POST['registration-end-time'],
				'show_pending_booking_pdf' => $_POST['show-pending-booking-pdf'],
				'show_approved_booking_pdf' => $_POST['show-approved-booking-pdf'],
				'booking_qr_code_input' => $_POST['booking-qr-code-input'],
				'notification_email' => $_POST['notification-email'],
				'booking_redirect_status_page' => $_POST['booking-redirect-status-page'],
				'require_name' => $_POST['require-name'],
				'show_info_button' => $_POST['show-info-button'],
				'zoom_on_top' => $_POST['zoom-on-top'],
				'require_wp_login' => $_POST['require-wp-login'],
				'wp_user_booking_limit' => (int)$_POST['wp-user-booking-limit'] > 0 ? (int)$_POST['wp-user-booking-limit'] : null,
				'wp_user_bookings_seat_limit' => (int)$_POST['wp-user-bookings-seat-limit'] > 0 ? (int)$_POST['wp-user-bookings-seat-limit'] : null,
				'one_person_checkout' => $_POST['one-person-checkout'],
				'automatic_booking_confirm_dialog' => $_POST['automatic-booking-confirm-dialog'],
				'enable_coupons' => $_POST['enable_coupons'],
				'coupons' => $coupons,
			 ),
			array(
				'registration_code' => sanitize_text_field($_POST['registration_code'])
			),
			'%s',
			'%s'
		);

		if ($status1 === false) {
            throw new Exception('Settings update failed: ' . $wpdb->last_error);
        }

		$status2 = $wpdb->update(
			"$seatreg_db_table_names->table_seatreg",
			array(
				'registration_name' => $registrationName,
			),
			array(
				'registration_code' => sanitize_text_field($_POST['registration_code'])
			),
			'%s',
			'%s'
		);

		if ($status2 === false) {
            throw new Exception('Name update update failed: ' . $wpdb->last_error);
        }

		$wpdb->query('COMMIT');

	} catch (Exception $e) {
		$wpdb->query('ROLLBACK');
		error_log($e->getMessage());
		$dbUpdated = false;
	}

	if( $dbUpdated === false ) {
		return false;
	}
	
	$turningOnStripePaymentsDetected = $oldOptions->stripe_payments === '0' && $_POST['stripe-payments'] === 1;
	$stripeAPiKeyChangeDetected = $oldOptions->stripe_payments === '1' && $_POST['stripe-payments'] === 1 && $oldOptions->stripe_api_key !== $_POST['stripe-api-key'];

	if( $turningOnStripePaymentsDetected || $stripeAPiKeyChangeDetected ) {
		if( !StripeWebhooksService::isStripeWebhookCreatedForCurrentSite($_POST['stripe-api-key']) ) {
			//Create a new Stripe webhook
			$webhook = StripeWebhooksService::createStripeWebhook($_POST['stripe-api-key']);
			SeatregOptionsService::updateStripeWebhookSecret($webhook->secret, sanitize_text_field($_POST['registration_code']));
		}else {
			//Webhook already created for this site. Set stripe_webhook secret from existing working webhook
			SeatregOptionsService::updateStripeWebhookSecret(
				SeatregOptionsRepository::getActiveStripeWebhookSecret($_POST['stripe-api-key']),
				sanitize_text_field($_POST['registration_code'])
			);
		}
	}else if( $oldOptions->stripe_payments === '1' &&  $_POST['stripe-payments'] === 0) {
		//Turning off Stripe payment
		SeatregOptionsService::updateStripeWebhookSecret(null, sanitize_text_field($_POST['registration_code']));
		StripeWebhooksService::removeNotUsedStripeAPiWebhook($_POST['stripe-api-key']);
	}
}

//handle settings form submit
add_action('admin_post_seatreg-form-submit', 'seatreg_form_submit_handle'); 
function seatreg_form_submit_handle() {
	seatreg_check_user_capabilities(SEATREG_MANAGE_EVENTS_CAPABILITY);
	check_admin_referer('seatreg-options-submit', 'seatreg-options-nonce');

	if( seatreg_update() === false) {
		wp_die('Error updating settings');
	}else {
		seatreg_add_activity_log('settings', sanitize_text_field($_POST['registration_code']), 'Settings changed');
		wp_redirect( SEATREG_SETTINGS_PAGE . '&tab=' . $_POST['registration_code'] );

		die();
	}
}

//handle companion app toggle
add_action('admin_post_seatreg_toggle_companion_app', 'toggle_companion_app_handle');
function toggle_companion_app_handle() {
	seatreg_check_user_capabilities(SEATREG_MANAGE_EVENTS_CAPABILITY);
	check_admin_referer('seatreg-admin-nonce', 'seatreg-admin-nonce');

	$enabled = isset($_POST['seatreg_companion_app_enabled']);
	SeatregCompanionAppService::setEnabled($enabled);

	wp_safe_redirect( wp_get_referer() );
    exit;
}

/*
====================================================================================================================================================================================
Ajax stuff
====================================================================================================================================================================================
*/

function seatreg_ajax_security_check($requiredCapability) {
	seatreg_check_user_capabilities($requiredCapability);
	seatreg_check_ajax_nonce();
}

function seatreg_check_ajax_nonce() {	
	if( !check_ajax_referer('seatreg-admin-nonce', 'security') ) {	
		return wp_send_json_error( 'Nonce error' );	
		wp_die();	
	}	
}

add_action('wp_ajax_get_seatreg_layout_and_bookings', 'seatreg_get_registration_layout_and_bookings');
function seatreg_get_registration_layout_and_bookings() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);

	$registration = seatreg_get_registration_data( sanitize_text_field( $_POST['code']) );
	$bookings = SeatregBookingRepository::getAllConfirmedAndApprovedBookingsByRegistrationCode( sanitize_text_field($_POST['code']) );
	$uploadedImages = seatreg_get_registration_uploaded_images( sanitize_text_field($_POST['code']) );
	$dataToSend = new stdClass();
	$dataToSend->registration = $registration;
	$dataToSend->bookings = $bookings;
	$dataToSend->uploadedImages = $uploadedImages;
	$response = new SeatregJsonResponse();
	$response->setData( $dataToSend);
	wp_send_json( $response );
}

add_action('wp_ajax_seatreg_update_layout', 'seatreg_update_layout');
function seatreg_update_layout() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);
	$response = new SeatregJsonResponse();

	if( !SeatregDataValidation::updateLayoutDataExists() ) {
		$response->setError('Layout data missing');
		wp_send_json( $response );

		die();
	}
	$updateData = stripslashes_deep($_POST['updatedata']);
	$layoutValidation = SeatregDataValidation::layoutDataIsCorrect($updateData);

	if( !$layoutValidation->valid ) {
		$response->setError($layoutValidation->errorMessage);
		wp_send_json( $response );
		
		die();
	}
	
	$status = SeatregRegistrationService::updateRegistrationLayout($updateData, $_POST['registration_code']);

	if($status) {
		seatreg_add_activity_log('map',sanitize_text_field($_POST['registration_code']), 'Registration layout updated');
	}
	
	$response->setData( $status );
	wp_send_json( $response );
}

function seatreg_random_string($length){
	$chars = "abcdefghijklmnoprstuvwzyx023456789";
	$str = "";
	$i = 0;
	
	while($i <= $length){
		$num = rand() % 33;
		$temp = substr($chars, $num, 1);
		$str = $str.$temp;
		$i++;
	}
	return $str;
}

add_action( 'wp_ajax_seatreg_seat_password_check', 'seatreg_seat_password_check_callback' );
add_action( 'wp_ajax_nopriv_seatreg_seat_password_check', 'seatreg_seat_password_check_callback' );
function seatreg_seat_password_check_callback() {
	if( empty($_POST['registration-code']) || empty($_POST['seat-id']) || empty($_POST['password']) ) {
		wp_send_json_error("Missing data");
	}

	$layout = SeatregRegistrationRepository::getRegistrationLayout( $_POST['registration-code'] );
	$hasPassword = SeatregLayoutService::checkIfSeatHasPassword($layout, $_POST['seat-id']);

	if($hasPassword) {
		$seatPassword = SeatregLayoutService::getSeatPassword($layout, $_POST['seat-id']);

		if($seatPassword === $_POST['password']) {
			wp_send_json_success("Password correct");
		}else {
			wp_send_json_error("Password missmatch");
		}
	}
	wp_send_json_success("No password set");
}

add_action( 'wp_ajax_seatreg_check_coupon', 'seatreg_check_coupon_callback' );
add_action( 'wp_ajax_nopriv_seatreg_check_coupon', 'seatreg_check_coupon_callback' );
function seatreg_check_coupon_callback() {
	if ( empty($_POST['registration-code']) || empty($_POST['coupon']) ) {
		wp_send_json_error("Missing data");
	}

	if ( !SeatregCouponRepository::areCouponsEnabled($_POST['registration-code']) ) {
		wp_send_json_error("Coupons not enabled");
	}

	$coupon = SeatregCouponRepository::findCoupon($_POST['registration-code'], $_POST['coupon']);

	if ($coupon) {
		wp_send_json_success([
			'message' => 'Coupon is valid',
			'discount' => $coupon->discountValue,
			'couponCode' => $coupon->couponCode,
		]);
	} else {
		wp_send_json_error("Invalid coupon");
	}
}

add_action( 'wp_ajax_seatreg_fetch_bookings_and_info', 'seatreg_fetch_bookings_and_info_callback' );
add_action( 'wp_ajax_nopriv_seatreg_fetch_bookings_and_info', 'seatreg_fetch_bookings_and_info_callback' );
function seatreg_fetch_bookings_and_info_callback() {
	$data = SeatregRegistrationRepository::getRegistrationWithOptionsByCode( $_GET['registration-code'] );
	$selectedShowRegistrationData = $data->show_bookings_data_in_registration ? explode(',', $data->show_bookings_data_in_registration) : [];
	$bookings = SeatregBookingRepository::getBookingsForRegistrationPage( $_GET['registration-code'], $selectedShowRegistrationData, $_GET['date']);
	$roomsBookingInfo = json_encode( SeatregLayoutService::getBookingsInfoForLayout($data->registration_layout, $data->registration_code, $_GET['date']) );
	$responseData = (object)[
		'bookings' => $bookings,
		'roomsBookingInfo' => $roomsBookingInfo
	];

	wp_send_json( $responseData );
}

add_action( 'wp_ajax_seatreg_booking_submit', 'seatreg_booking_submit_callback' );
add_action( 'wp_ajax_nopriv_seatreg_booking_submit', 'seatreg_booking_submit_callback' );
function seatreg_booking_submit_callback() {
	$resp = new SeatregJsonResponse();

	if ( ! wp_verify_nonce( $_POST['seatreg-booking-submit'], 'seatreg-booking-submit' ) ) {
		$resp->setError('Nonce validation failed');
		$resp->echoData();
				
		die();
	}
	
	$data = seatreg_get_options($_POST['c'])[0];

	if ($data->require_name)
	{
		if( empty($_POST['FirstName']) ||
			empty($_POST['LastName'])) {
				$resp->setError('Missing data');
				$resp->echoData();
				
				die();
			}
	}

	if( empty($_POST['Email']) ||
		empty($_POST['item-id']) ||
		empty($_POST['item-nr']) ||
		empty($_POST['room-uuid']) ||
		empty($_POST['em']) ||
		empty($_POST['c']) ||
		empty($_POST['passwords']) ||
		!isset($_POST['pw']) ||
		!isset($_POST['multi-price-uuid']) ||
		empty($_POST['custom'])) {
			$resp->setError('Missing data');
			$resp->echoData();
			
			die();
	}

	$newBooking = new SeatregSubmitBookings( $_POST['c'], $resp );

	if( $newBooking->validateAndPopulateBookingData(
			$_POST['FirstName'], 
			$_POST['LastName'], 
			$_POST['Email'], 
			$_POST['item-id'], 
			$_POST['item-nr'], 
			$_POST['em'], 
			$_POST['c'], 
			$_POST['pw'], 
			$_POST['custom'],
			$_POST['room-uuid'],
			$_POST['passwords'],
			$_POST['multi-price-uuid'],
			$_POST['coupon']) 
	){
		$newBooking->validateBooking();
	}	
	
	$resp->echoData();

	die();
}

add_action( 'wp_ajax_seatreg_resend_receipt', 'seatreg_resend_receipt_callback' );
add_action( 'wp_ajax_nopriv_seatreg_resend_receipt', 'seatreg_resend_receipt_callback' );
function seatreg_resend_receipt_callback() {
	$resp = new SeatregJsonResponse();

	if( empty($_POST['bookingId']) || empty($_POST['registrationCode'])) {
			$resp->setError('Missing data');
			$resp->echoData();
			
			die();
	}
	$bookingId = sanitize_text_field($_POST['bookingId']);
	$bookings = SeatregBookingRepository::getBookingsById($bookingId);
	$bookingData = SeatregBookingRepository::getDataRelatedToBooking($bookingId);

	if($bookings && $bookings[0]->status === '2') {
		seatreg_send_approved_booking_email( $bookingId, sanitize_text_field($_POST['registrationCode']), $bookingData->approved_booking_email_template );
	}else {
		$resp->setError('Not allowed');
	}

	$resp->echoData();

	die();
}


add_action( 'wp_ajax_seatreg_get_room_stats', 'seatreg_get_room_stats_callback' );
function seatreg_get_room_stats_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	seatreg_generate_overview_section_html($_POST['data']['target'], sanitize_text_field($_POST['code']), $_POST['data']['calendarDate']);

	die();
}

add_action('wp_ajax_seatreg_delete_api_token', 'seatreg_delete_api_token');
function seatreg_delete_api_token() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);

	if( empty( $_POST[ 'code' ] ) || empty( $_POST['data'][ 'api-token' ] ) ) {
		wp_die('Missing data');
	}

	if( SeatregPublicApiService::deleteApiToken( $_POST['data'][ 'api-token' ] )) {
		wp_send_json_success();
	}else {
		wp_send_json_error();
	}
}

add_action('wp_ajax_seatreg_custom_payment_icon_upload', 'seatreg_custom_payment_icon_upload');
function seatreg_custom_payment_icon_upload() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);
	$resp = new SeatregJsonResponse();

	if(empty($_FILES["file"]) || empty($_POST['code'])) {
		$resp->setError('Missing data');
		$resp->echoData();

		die();
	}
	$code = sanitize_text_field($_POST['code']);

	try {
		$imageUploadService = new SeatregImageUploadService('/custom_payment_icons/' . $code . '/');
		$status = $imageUploadService->uploadImage($_FILES["file"]);

		$resp->setText($status->text);
		$resp->setData($status->basename);
		$resp->setExtraData($status->imageDimentsions);
		$resp->echoData();

		die();
	} catch(Exception $e) {
		$resp->setError($e->getMessage());
		$resp->echoData();

		die();
	}
}

add_action('wp_ajax_seatreg_create_api_token', 'seatreg_create_api_token');
function seatreg_create_api_token() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);

	if( empty( $_POST[ 'code' ] ) ) {
		wp_die('Missing data');
	}

	$token = SeatregRandomGenerator::generateApiToken();
	$hiddenToken = showFirstLetters($token, 3);

	if( SeatregPublicApiService::insertApiToken( $_POST[ 'code' ], $token) ) {
		wp_send_json_success(
			(object) [
				'token' => $token,
				'hiddenToken' => $hiddenToken
			]
		);
	}else {
		wp_send_json_error();
	}
}


add_action( 'wp_ajax_seatreg_get_booking_manager', 'seatreg_get_booking_manager_callback' );
function seatreg_get_booking_manager_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);
	if( empty( $_POST[ 'code' ] ) || empty( $_POST['data']['orderby'] ) || !isSet( $_POST['data']['searchTerm'] ) ) {
		wp_die('Missing data');
	}

	$order = sanitize_text_field($_POST['data']['orderby']);
	$code = sanitize_text_field($_POST['code']);
	$search = sanitize_text_field($_POST['data']['searchTerm']); 
	$calendarDate = assignIfNotEmpty($_GET['calendar-date'], null);

	if( strlen($code) > SEATREG_REGISTRATION_NAME_MAX_LENGTH ) {
		wp_die('Too long code');
	}

	if( strlen($search) > SEATREG_REGISTRATION_SEARCH_MAX_LENGTH ) {
		wp_die('Too long search');
	}

	if( !in_array($order, SEATREG_MANAGER_ALLOWED_ORDER) ) {
		wp_die('Search not allowed');
	}

	seatreg_generate_booking_manager_html(
		$code,
		$order,
		$search,
		$calendarDate
	);

	die();
}

add_action( 'wp_ajax_seatreg_confirm_del_bookings', 'seatreg_confirm_del_bookings_callback' );
function seatreg_confirm_del_bookings_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	$actionData = json_decode( stripslashes_deep($_POST['data']['actionData']) );
	$calendarDate = assignIfNotEmpty( $_POST['data']['calendarDate'], null );
	$code = sanitize_text_field( $_POST['code'] );
	$statusArray = seatreg_validate_del_conf_booking( $code, $actionData, $calendarDate );

	if ( $statusArray['status'] != 'ok' ) {
		$errorText = '';

		switch( $statusArray['status'] ) {
			case 'room-searching':
				$errorText = $statusArray['text'];

				break;
			case 'seat-id-searching';
				$errorText = $statusArray['text'];

				break;
			case 'seat-booked';
				$errorText = $statusArray['text'];

				break;
		}

		echo '<div class="alert alert-danger" role="alert">', esc_html($errorText) ,'</div>';
		
	}else {
		$approvalBookingEmailProcessed = [];

		foreach ($actionData as $key => $value) {
			seatreg_confirm_or_delete_booking( $value, $code, $calendarDate);

			if($value->action == 'conf' && !in_array($value->booking_id, $approvalBookingEmailProcessed)) {
				$bookingData = SeatregBookingRepository::getDataRelatedToBooking($value->booking_id);

				if($bookingData->send_approved_booking_email === '1') {
					$mailSent = seatreg_send_approved_booking_email( $value->booking_id, $code, $bookingData->approved_booking_email_template );
					if($mailSent) {
						$approvalBookingEmailProcessed[] = $value->booking_id;
					}
				}
			}
		}
	}

	$order = 'date';
	$searchTerm = '';
	
	if( !empty( $_POST['data']['orderby'] ) ) {
		$order = sanitize_text_field($_POST['data']['orderby']);
	}

	if( !empty( $_POST['data']['searchTerm'] ) ) {
		$searchTerm = sanitize_text_field($_POST['data']['searchTerm']);
	}
	seatreg_generate_booking_manager_html( sanitize_text_field($_POST['code']) , $order, $searchTerm, $calendarDate );

	die();
}

add_action( 'wp_ajax_seatreg_search_bookings', 'seatreg_search_bookings_callback' );
function seatreg_search_bookings_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);
	$order = 'date';
	$searchTerm = '';
	$calendarDate = assignIfNotEmpty($_POST['data']['calendarDate'], null);

	if( !empty( $_POST['data']['orderby'] ) ) {
		$order = sanitize_text_field($_POST['data']['orderby']);
	}

	if( !empty( $_POST['data']['searchTerm'] ) ) {
		$searchTerm = sanitize_text_field($_POST['data']['searchTerm']);
	}
	seatreg_generate_booking_manager_html( sanitize_text_field($_POST['code']) , $order, $searchTerm, $calendarDate );

	die();
}

add_action( 'wp_ajax_seatreg_add_booking_with_manager', 'seatreg_add_booking_with_manager_callback' );
function seatreg_add_booking_with_manager_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	if( empty( $_POST['first-name'] ) || 
		empty( $_POST['last-name'] ) || 
		empty( $_POST['email'] ) || 
		empty( $_POST['seat-id'] ) ||
		empty( $_POST['room'] ) ||
		empty( $_POST['registration-code'] ) ||
		empty( $_POST['booking-status'] ) ||
		empty( $_POST['seat-multi-price'] ) ||
		empty( $_POST['custom-fields'] ) ) {
			wp_send_json_error( array('message'=> 'Missing parameters') );
	}

	$registrationCode = sanitize_text_field( $_POST['registration-code'] );
	$calendarDate = !empty( $_POST['calendar-date'] ) ? $_POST['calendar-date'] : null;
	$bookingsToAdd = [];
	$options = SeatregOptionsRepository::getOptionsByRegistrationCode($registrationCode);
	$customFieldsInput = stripslashes_deep( $_POST['custom-fields'] );
	$customFieldValidation = SeatregDataValidation::validateBookingCustomFields($customFieldsInput, $options->seats_at_once, json_decode($options->custom_fields), $options->registration_code);
	$bookingStatus = sanitize_text_field($_POST['booking-status']);

	if( !$customFieldValidation->valid ) {
		wp_send_json_error( array('message' => $customFieldValidation->errorMessage, 'status' => 'custom field validation failed') );
	}
	$customFields = json_decode($customFieldsInput);

	foreach ( $_POST['first-name'] as $key => $value ) {
		$bookingToAdd = new stdClass();
		$bookingToAdd->firstName = sanitize_text_field($_POST['first-name'][$key]);
		$bookingToAdd->lastName = sanitize_text_field($_POST['last-name'][$key]);
		$bookingToAdd->seatId = sanitize_text_field($_POST['seat-id'][$key]);
		$bookingToAdd->roomName = sanitize_text_field($_POST['room'][$key]);
		$bookingToAdd->customfield = $customFields[$key];
		$bookingToAdd->email = sanitize_text_field($_POST['email'][$key]);
		$bookingToAdd->status = $bookingStatus;
		$bookingToAdd->multiPriceSelection = $_POST['seat-multi-price'][$key] ?? null;

		$bookingsToAdd[] = $bookingToAdd;
	}

	foreach( $bookingsToAdd as $key => $bookingToAdd ) {
		$statusArray = seatreg_valdiate_add_booking_with_manager( $registrationCode, $bookingToAdd, $calendarDate );

		if ( $statusArray['status'] !== 'ok' ) {
			wp_send_json_error( array('message' => $statusArray['text'], 'status' => $statusArray['status'], 'index' => $key) );
		}

		$bookingsToAdd[$key]->seatId = $bookingToAdd->seatId;
		$bookingsToAdd[$key]->roomUUID = $statusArray['roomUUID'];
		$bookingsToAdd[$key]->seatNr = $statusArray['seatNr'];
	}

	// Are separate seats?
	$seatIds = [];
	foreach( $bookingsToAdd as $key => $bookingToAdd ) {
		if(!in_array($bookingToAdd->seatId, $seatIds)) {
			array_push($seatIds, $bookingToAdd->seatId);
		}else {
			wp_send_json_error( array('status' => 'duplicate-seat') );
		}
	}

	$bookingId = sha1(mt_rand(10000,99999).time().$bookingsToAdd[0]->email);
	$confCode = sha1(mt_rand(10000,99999).time().$bookingsToAdd[0]->email);
	$addingStatus = [];

	foreach( $bookingsToAdd as $booking ) {
		$addingStatus[] = seatreg_add_booking( 
			$booking->firstName,
			$booking->lastName,
			$booking->email,
			$booking->customfield, 
			$booking->seatNr, 
			$booking->seatId,
			$booking->roomUUID,
			$registrationCode,
			$booking->status,
			$bookingId,
			$confCode,
			$calendarDate,
			$booking->multiPriceSelection,
		);
	}
	$successStatusCount = count(array_filter($addingStatus, function($status) {
		return $status === true;
	}));
	$failStatusCount = count(array_filter($addingStatus, function($status) {
		return $status === false;
	}));
	$addingStatusCount = count($addingStatus);
	$bookingData = SeatregBookingRepository::getDataRelatedToBooking($bookingId);
	SeatregActionsService::triggerBookingManuallyAddedAction($bookingId);
	
	if( $successStatusCount === $addingStatusCount ) {
		$selectedStatus = $bookingStatus === '1' ? 'pending' : 'approved';
		seatreg_add_activity_log( 'booking', $bookingId, 'Booking with '. $addingStatusCount . ' ' .  $selectedStatus .' seats added with booking manager', true );
		if($bookingStatus === "2") {
			seatreg_send_approved_booking_email($bookingId, $registrationCode, $bookingData->approved_booking_email_template);
		}
		wp_send_json_success( array('status' => 'created') );
	}else if( $successStatusCount !== $addingStatusCount ) {
		seatreg_add_activity_log( 'booking', $bookingId, 'There was a problem adding booking. '. $successStatusCount .' seat/seats was booked but '. $failStatusCount .' seat/seats failed', true );
		wp_send_json_success( array('status' => 'created') );
	}else if ( $successStatusCount === 0 ){
		wp_send_json_error( array('status' => 'create failed') );
	}
}

add_action( 'wp_ajax_seatreg_edit_booking', 'seatreg_edit_booking_callback' );
function seatreg_edit_booking_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	$bookingEdit = new stdClass();
	$bookingEdit->firstName = sanitize_text_field($_POST['fname']);
	$bookingEdit->lastName = sanitize_text_field($_POST['lname']);
	$bookingEdit->roomName = sanitize_text_field($_POST['room']);
	$bookingEdit->seatId = sanitize_text_field($_POST['seatid']);
	$bookingEdit->bookingId = sanitize_text_field($_POST['bookingid']);
	$bookingEdit->editCustomField = stripslashes_deep($_POST['customfield']);
	$bookingEdit->id = sanitize_text_field($_POST['id']);
	$bookingEdit->calendarDate = !empty($_POST['calendarDate']) ? sanitize_text_field($_POST['calendarDate']): null;
	$bookingEdit->bookerEmail = $_POST['bookerEmail'];
	$bookingEdit->email = !empty($_POST['email']) ? $_POST['email'] : null;

	$statusArray = seatreg_validate_edit_booking( sanitize_text_field($_POST['code']), $bookingEdit );

	if ( $statusArray['status'] != 'ok' ) {
		wp_send_json( array('status' => $statusArray['status'], 'text'=> $statusArray['text'] ) );

		die();
	}

	if( seatreg_edit_booking( 
			$bookingEdit->editCustomField, 
			$statusArray['newSeatNr'], 
			$statusArray['roomUUID'], 
			$bookingEdit->firstName,
			$bookingEdit->lastName,
			$bookingEdit->bookingId, 
			$statusArray['newSeatId'],
			$bookingEdit->id,
			$bookingEdit->calendarDate,
			$bookingEdit->bookerEmail,
			$bookingEdit->email
		) !== false) {
		seatreg_add_activity_log('booking', $bookingEdit->bookingId, 'Booking edited (Booking manager)');
		wp_send_json( array(
			'status' => 'updated',
			'newSeatNr' => $statusArray['newSeatNr']
		) );

		die();
	}else {
		wp_send_json( array('status'=>'update failed') );

		die();
	}
}

add_action( 'wp_ajax_seatreg_save_booking_approved_email_custom_text', 'seatreg_save_booking_approved_email_custom_text' );
function seatreg_save_booking_approved_email_custom_text() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);
	$resp = new SeatregJsonResponse();

	if( empty($_POST['code']) || empty($_POST['data']['emailTemplateText']) ) {
		wp_send_json_error();

		die();
	}

	$bookingService = new SeatregBookingService();
	$code = sanitize_text_field($_POST['code']);
	$customEmailTemplateText = sanitize_text_field($_POST['data']['emailTemplateText']);
	$rowsUpdated = $bookingService->updateBookingCustomTextForApprovedEmail($code, $customEmailTemplateText);
	
	if( $rowsUpdated !== false ) {
		wp_send_json_success();
	}else {
		wp_send_json_error();
	}
}

add_action( 'wp_ajax_seatreg_upload_image', 'seatreg_upload_image_callback' );
function seatreg_upload_image_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);

	$resp = new SeatregJsonResponse();

	if(empty($_FILES["fileToUpload"]) || empty($_POST['code'])) {
		$resp->setError('No picture selected');
		$resp->echoData();

		die();
	}

	$code = sanitize_text_field($_POST['code']);

	try {
		$imageUploadService = new SeatregImageUploadService('/room_images/' . $code . '/');
		$status = $imageUploadService->uploadImage($_FILES["fileToUpload"]);

		$resp->setText($status->text);
		$resp->setData($status->basename);
		$resp->setExtraData($status->imageDimentsions);
		$resp->echoData();

		die();
	} catch(Exception $e) {
		$resp->setError($e->getMessage());
		$resp->echoData();

		die();
	}
}

add_action( 'wp_ajax_seatreg_remove_img', 'seatreg_remove_img_callback' );
function seatreg_remove_img_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);

	$resp = new SeatregJsonResponse();

	if(!empty($_POST['imgName']) && !empty($_POST['code'])) {
		//check if file exists
		$imgPath = SEATREG_TEMP_FOLDER_DIR . '/room_images/' . sanitize_text_field($_POST['code']) . '/' . sanitize_text_field($_POST['imgName']);
		
		if(file_exists($imgPath)) {
			unlink($imgPath);
			$resp->setText('Image deleted');
		}else {
			$resp->setError('Image was not found!');
		}
		$resp->echoData();
		
		die();
	}
}

add_action( 'wp_ajax_seatreg_remove_custom_payment_img', 'seatreg_remove_custom_payment_img_callback' );
function seatreg_remove_custom_payment_img_callback() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);

	if( empty($_POST['code']) || empty($_POST['data']) ) {
		wp_send_json_error();
	}

	try {
		SeatregImageDeleteService::deleteCustomPaymentImage( $_POST['code'], $_POST['data'] );

		wp_send_json_success();

	}catch(Exception $e) {
		wp_send_json_error();
	}
}

add_action( 'wp_ajax_seatreg_send_test_email', 'seatreg_send_test_email');
function seatreg_send_test_email() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);

	if(empty($_POST['email'])) {
		exit('Missing data');
	}
	
	$email = sanitize_email($_POST['email']);
	$response = new SeatregJsonResponse();
	$adminEmail = get_option( 'admin_email' );

	$mailSent = wp_mail($email, esc_html__('Seatreg test email', 'seatreg'), esc_html__('This is a test email', 'seatreg'), array(
		"Content-type: text/html",
		"FROM: $adminEmail"
	));

	if(!$mailSent) {
		$response->setError('Email sending error');
	}

	wp_send_json( $response );
}

add_action( 'wp_ajax_seatreg_get_booking_logs', 'seatreg_get_booking_logs');
function seatreg_get_booking_logs() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	if(empty($_GET['bookingId'])) {
		exit('Missing data');
	}

	$activityLogs = SeatregActivityLogRepository::getBookingActivityLogsByBookingId( $_GET['bookingId'] );
	$response = new SeatregJsonResponse();
	$response->setData($activityLogs);

	wp_send_json( $response );
}

add_action( 'wp_ajax_seatreg_get_registration_logs', 'seatreg_get_registration_logs');
function seatreg_get_registration_logs() {
	seatreg_ajax_security_check(SEATREG_MANAGE_EVENTS_CAPABILITY);

	if(empty($_GET['registrationId'])) {
		exit('Missing data');
	}

	$activityLogs = SeatregActivityLogRepository::getRegistrationAcitivityLogs( $_GET['registrationId'] );
	$response = new SeatregJsonResponse();
	$response->setData($activityLogs);

	wp_send_json( $response );
}

add_action( 'wp_ajax_seatreg_booking_payment_status_change', 'seatreg_booking_payment_status_change');
function seatreg_booking_payment_status_change() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	if( empty($_POST['data']) || empty($_POST['code']) || empty($_POST['data']['bookingStatus']) ) {
		wp_send_json_error('Missing data', 422);
	}

	$done = SeatregPaymentService::insertOrUpdatePayment( $_POST['code'], $_POST['data']['bookingStatus'] );

	if($done) {
		$current_user = wp_get_current_user();
		$current_user_displayname = $current_user->data->display_name;
		$current_user_id = $current_user->data->ID;
		SeatregPaymentLogService::log($_POST['code'], 'Booking status changed to ' . $_POST['data']['bookingStatus'] . ' by ' . $current_user_displayname . ' (id ' . $current_user_id .')');
		wp_send_json('ok');
	}else {
		wp_send_json_error();
	}
}

add_action( 'wp_ajax_seatreg_create_payment_log', 'seatreg_create_payment_log');
function seatreg_create_payment_log() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	if( empty($_POST['logStatus']) || empty($_POST['bookingId']) || empty($_POST['logMessage']) ) {
		exit('Missing data');
	}

	$logInserted = SeatregPaymentLogService::log( $_POST['bookingId'], $_POST['logMessage'], $_POST['logStatus'] );

	if( $logInserted ) {
		wp_send_json('ok');
	}else {
		wp_send_json_error();
	}
}

add_action('wp_ajax_seatreg_inspect_booking_csv', 'seatreg_inspect_booking_csv');
function seatreg_inspect_booking_csv() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	if( empty($_POST['seatreg-code']) || empty($_FILES['csv-file']) ) {
		wp_send_json_error('Missing data', 400);
	}
	
	$csvService = new SeatregCSVService($_POST['seatreg-code']);
	$validation = $csvService->validateCSV($_FILES['csv-file']);

	if( !$validation->isValid ) {
		wp_send_json_error($validation->message, 400);
	}

	$validationData = $csvService->validateData($_FILES['csv-file']);

	wp_send_json(array(
		'data' => $validationData,
	));
}

add_action('wp_ajax_seatreg_import_bookings', 'seatreg_import_bookings');
function seatreg_import_bookings() {
	seatreg_ajax_security_check(SEATREG_MANAGE_BOOKINGS_CAPABILITY);

	if( empty($_POST['code']) || empty($_POST['bookingsImport']) ) {
		wp_send_json_error('Missing data', 400);
	}

	$importService = new SeatregImportService($_POST['code']);
	$import = $importService->importBookings($_POST['bookingsImport']);

	wp_send_json(
		array(
			'success' => $import->success,
			'failedImports' => $import->failedImports,
			'successImports' => $import->successfulImports,
		)
	);
}
/*
====================================================================================================================================================================================
Capabilities
====================================================================================================================================================================================
*/

function seatreg_capabilities_add() {
	$role = get_role('administrator');
	if ( !$role->has_cap(SEATREG_MANAGE_EVENTS_CAPABILITY) ) {
		$role->add_cap(SEATREG_MANAGE_EVENTS_CAPABILITY);
	}
	if ( !$role->has_cap(SEATREG_MANAGE_BOOKINGS_CAPABILITY) ) {
		$role->add_cap(SEATREG_MANAGE_BOOKINGS_CAPABILITY);
	}
}
function seatreg_capabilities_remove() {
	$role = get_role('administrator');
	if ( $role->has_cap(SEATREG_MANAGE_EVENTS_CAPABILITY) ) {
		$role->remove_cap(SEATREG_MANAGE_EVENTS_CAPABILITY);
	}
	if ( $role->has_cap(SEATREG_MANAGE_BOOKINGS_CAPABILITY) ) {
		$role->remove_cap(SEATREG_MANAGE_BOOKINGS_CAPABILITY);
	}

}

/*
====================================================================================================================================================================================
.htaccess
====================================================================================================================================================================================
*/

function seatreg_add_rewrite_rule() {
	$content = array(
		'<IfModule mod_rewrite.c>',
		'RewriteEngine On',
		'RewriteRule ^seatreg/?$ index.php [L,QSA]',
		'</IfModule>',
	);
	insert_with_markers(ABSPATH . '.htaccess', SEATREG_MARKER, $content);
}
