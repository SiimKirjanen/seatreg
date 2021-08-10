<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

global $wpdb;
global $seatreg_db_table_names;

require_once 'SeatregSubmitBookings.php';
require_once 'SeatregJsonResponse.php';
require_once 'constants.php';
require_once 'SeatregDataValidation.php';

$seatreg_db_table_names = new stdClass();
$seatreg_db_table_names->table_seatreg = $wpdb->prefix . "seatreg";
$seatreg_db_table_names->table_seatreg_options = $wpdb->prefix . "seatreg_options";
$seatreg_db_table_names->table_seatreg_bookings = $wpdb->prefix . "seatreg_bookings";

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

	if( !current_user_can('manage_options') ) {
		esc_html_e('No permissions', 'seatreg');

		exit();
	}
}

//generating nonce fields without html id attribute
function seatrag_generate_nonce_field($action) {
	?>
		<input type="hidden" name="<?php echo $action; ?>" value="<?php echo wp_create_nonce( $action ); ?>" />
		<?php echo wp_referer_field( false ); ?>
	<?php
}

//nonce check
function seatreg_nonce_check() {
	if ( ! wp_verify_nonce( $_POST['seatreg-admin-nonce'], 'seatreg-admin-nonce' ) ) {
	    wp_die('Nonce validation failed!');
	}
	if( !current_user_can('manage_options') ) {
		 wp_die('You are not allowed to do this');
	}
}

//capability check
function seatreg_check_user_capabilities() {
	if( !current_user_can('manage_options') ) {	
		wp_die('You are not allowed to do this');	
	}
}

function seatreg_is_registration_view_page() {
	if( isset($_GET['seatreg']) && $_GET['seatreg'] === 'registration' ) {
		return true;
	}
	return false;
}

function seatreg_validate_bookings_file_input() {
	if(empty($_GET['code'])) {
		wp_die('Missing code');
	}
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
			wp_die($validation->errorMessage);
		}
	} 

	seatreg_generate_overview_section_html($targetRoom, $active_tab);
}

//generate overview section html.
function seatreg_generate_overview_section_html($targetRoom, $active_tab) {
	global $wpdb;
	global $seatreg_db_table_names;

	$registration = seatreg_get_options( $active_tab );

	if( count($registration) == 0 ) {
		seatreg_no_registration_created_info();
		 
	 	return;
	 }

	$registration = $registration[0];
	$bookings = seatreg_get_registration_bookings( $registration->registration_code );
	$pendingBookingsRoomInfo = $wpdb->get_results("SELECT room_uuid, COUNT(id) AS total FROM $seatreg_db_table_names->table_seatreg_bookings WHERE registration_code = '$registration->registration_code' AND status = 1 GROUP BY room_uuid");
	$confirmedBookingsRoomInfo = $wpdb->get_results("SELECT room_uuid, COUNT(id) AS total FROM $seatreg_db_table_names->table_seatreg_bookings WHERE registration_code = '$registration->registration_code' AND status = 2 GROUP BY room_uuid");
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
								echo sprintf(esc_html__('%s pending seats', 'seatreg'), $regStats['bronSeats']);
							echo '</div>';
						}else {
							for($i = 0; $i < $regStats['roomCount']; $i++) {
								if($regStats['roomsInfo'][$i]['roomName'] == $targetRoom) {
									echo '<div class="reg-overview-top-bron-notify">';
										echo esc_html($regStats['roomsInfo'][$i]['roomBronSeats']),' ', esc_html__('pending seats', 'seatreg'), '!';
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
							<?php esc_html_e('Seats', 'seatreg'); ?>
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
											echo round(($regStats['openSeats'] / $regStats['seatsTotal']  ) * 100), '%'; 
										}else if($roomLoactionInStats >= 0) {

											if($regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal'] > 0) {
												echo round(($regStats['roomsInfo'][$roomLoactionInStats]['roomOpenSeats'] / $regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal']  ) * 100, 2), '%'; 
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
											echo round(($regStats['takenSeats'] / $regStats['seatsTotal']  ) * 100), '%'; 
										}else if($roomLoactionInStats >= 0) {

											if($regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal'] > 0) {
												echo round(($regStats['roomsInfo'][$roomLoactionInStats]['roomTakenSeats'] / $regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal']  ) * 100, 2), '%'; 
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
											echo round(($regStats['bronSeats'] / $regStats['seatsTotal']  ) * 100), '%'; 
										}else if($roomLoactionInStats >= 0) {

											if($regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal'] > 0) {
												echo round(($regStats['roomsInfo'][$roomLoactionInStats]['roomBronSeats'] / $regStats['roomsInfo'][$roomLoactionInStats]['roomSeatsTotal'] ) * 100, 2), '%'; 
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
	$registrations = seatreg_get_registrations();

	if(count($registrations)) {
		echo '<h4 class="your-registrations-header">';
			esc_html_e('Created registrations', 'seatreg');
		echo '</h4>';
	}
	echo '<div class="seatreg-registrations">';

	foreach($registrations as $key=>$registration) {
		?>
			<div class="mb-4" style="margin-right: 52px">
				<h5><a class="registration-name-link" href="<?php echo get_site_url(); ?>?seatreg=registration&c=<?php echo esc_html($registration->registration_code); ?>" target="_blank"><?php echo esc_html( $registration->registration_name ); ?></a></h5>

				<a href="<?php echo get_site_url(); ?>?seatreg=registration&c=<?php echo esc_html($registration->registration_code); ?>" target="_blank"><?php esc_html_e('Registration', 'seatreg'); ?></a>

				<br>

				<button type="button" class="btn btn-link seatreg-map-popup-btn" data-registration-name="<?php echo esc_attr($registration->registration_name); ?>" data-map-code="<?php echo esc_attr($registration->registration_code); ?>"><?php esc_html_e('Edit map', 'seatreg'); ?></button>

				<br>

				<a href="<?php echo admin_url( 'admin.php?page=seatreg-overview&tab='.$registration->registration_code );  ?>"><?php esc_html_e('Overview', 'seatreg'); ?></a>

				<br>

				<a href="<?php echo admin_url( 'admin.php?page=seatreg-options&tab='.$registration->registration_code ); ?>"><?php esc_html_e('Settings', 'seatreg'); ?></a>

				<br>

				<a href="<?php echo admin_url( 'admin.php?page=seatreg-management&tab='.$registration->registration_code ); ?>"><?php esc_html_e('Bookings', 'seatreg'); ?></a>

				<br>

				<?php
					seatreg_create_delete_registration_from($registration->registration_code);
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
			wp_die($validation->errorMessage);
		}
	}

	 $options = seatreg_get_options($active_tab);

	 if( count($options) == 0 ) {
		 seatreg_no_registration_created_info();
		 
	 	return;
	 }

	 $custFields = json_decode($options[0]->custom_fields);
	 $custLen = count(is_array($custFields) ? $custFields : []);
	 $adminEmail = get_option( 'admin_email' );
	?>
		<h4 class="settings-heading">
			<?php echo sprintf( __('%s settings', 'seatreg'),  $options[0]->registration_name); ?> 
		</h4>
		<form action="<?php echo get_admin_url() . 'admin-post.php'  ?>" method="post" id="seatreg-settings-form" class="seatreg-settings-form" style="max-width:600px">

			<div class="form-group">
				<label for="registration-name"><?php esc_html_e('Registration name', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Change registration name', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="registration-name" name="registration-name" maxlength="<?php echo SEATREG_REGISTRATION_NAME_MAX_LENGTH; ?>" placeholder="Enter registration name" autocomplete="off" value="<?php echo esc_attr($options[0]->registration_name); ?>">
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
				<label for="registration-start-timestamp"><i class="fa fa-clock-o" style="color:rgb(4, 145, 4); margin-right:3px"></i><?php esc_html_e('Registration start date', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Set registration start date (dd.mm.yyyy)', 'seatreg'); ?>.</p>
				<input type="text" id="registration-start-timestamp" class="form-control option-datepicker" placeholder="(dd.mm.yyyy)" autocomplete="off" />
				<input type='hidden' value='<?php echo esc_attr($options[0]->registration_start_timestamp); ?>' id='start-timestamp' class="datepicker-altfield" name="start-timestamp" />
			</div>

			<div class="form-group">
				<label for="registration-end-timestamp"><i class="fa fa-clock-o" style="color:rgb(250, 38, 38); margin-right:3px"></i><?php esc_html_e('Registration end date', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Set registration end date (dd.mm.yyyy)', 'seatreg'); ?>.</p>
				<input type="text" id="registration-end-timestamp" class="form-control option-datepicker" placeholder="(dd.mm.yyyy)" autocomplete="off" />
				<input type='hidden' value='<?php echo esc_attr($options[0]->registration_end_timestamp); ?>' id="end-timestamp" class="datepicker-altfield" name="end-timestamp" />
			</div>

			<div class="form-group">
				<label for="show-registration-bookings"><?php esc_html_e('Show bookings', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Let people see who has made a booking in registration page', 'seatreg'); ?>.</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="show-registration-bookings" name="show-registration-bookings" <?php echo $options[0]->show_bookings == '1' ? 'checked':'' ?> > 
			      		<?php esc_html_e('Show bookings', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="registration-info-text"><?php esc_html_e('Registration info text', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Set registration info text. Will be displayed in registration page', 'seatreg'); ?>.</p>
				<textarea class="form-control" id="registration-info-text" name="registration-info-text" placeholder="<?php esc_html_e('Enter info text here', 'seatreg'); ?>"><?php echo esc_html($options[0]->info); ?></textarea>
			</div>

			<div class="form-group">
				<label for="payment-instructions"><?php esc_html_e('Payment instruction', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('At the moment this plugin dosn\'t offer any payment solutions, but you can leave informative text that instructs how to pay for booking. It will be displayed in booking status page', 'seatreg'); ?>.</p>
				<textarea class="form-control" id="payment-instructions" name="payment-instructions" placeholder="<?php esc_html_e('Enter payment instructions here', 'seatreg')?>"><?php echo esc_html($options[0]->payment_text); ?></textarea>
			</div>

			<div class="form-group">
				<label for="registration-max-seats"><?php esc_html_e('Max seats per booking', 'seatreg'); ?></label>
				<p class="help-block"><?php esc_html_e('Set how many seats can be added to the booking', 'seatreg'); ?>.</p>
				<input type="number" class="form-control" id="registration-max-seats" name="registration-max-seats" value="<?php echo esc_html($options[0]->seats_at_once); ?>">
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
				<label for="registration-password"><?php esc_html_e('Password', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('You can set a password. Only people who know it can view your registration and make a booking. Leave it empty for no password', 'seatreg'); ?>.
				</p>
				<input type="text" class="form-control" id="registration-password" name="registration-password" autocomplete="off" placeholder="<?php echo esc_html('Enter password here', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->registration_password); ?>">
			</div>

			<div class="form-group">
				<label for="use-pending"><?php esc_html_e('Pending status', 'seatreg'); ?></label>
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
				<label for="use-pending"><?php esc_html_e('Booking email confirm', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Bookings must be confirmed with email', 'seatreg'); ?>.
				</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="email-confirm" name="email-confirm" value="1" <?php echo $options[0]->booking_email_confirm == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Email confirm', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="booking-notification"><?php esc_html_e('Booking notification', 'seatreg'); ?></label>
				<p class="help-block">
					<?php
						printf(
							/* translators: %s: email address */
							esc_html__( 'Send a notification to %s when you got a new booking.', 'seatreg' ),
							esc_html($adminEmail)
						);
					?>
				</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="booking-notification" name="booking-notification" value="1" <?php echo $options[0]->notify_new_bookings == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Send notifications', 'seatreg'); ?>
			    	</label>
			  	</div>
			</div>

			<div class="form-group">
				<label for="paypal"><?php esc_html_e('PayPal payments', 'seatreg'); ?></label>
				<p class="help-block">
					<?php esc_html_e('Allow and configure PayPal payments', 'seatreg'); ?>.
				</p>
				<div class="checkbox">
			    	<label>
			      		<input type="checkbox" id="paypal" name="paypal-payments" value="0" <?php echo $options[0]->paypal_payments == '1' ? 'checked':'' ?> >
			      		<?php esc_html_e('Allow payments', 'seatreg'); ?>
			    	</label>
			  	</div>
				<div class="paypal-configuration">
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
					<label for="paypal-currency-code"><?php esc_html_e('PayPal currency', 'seatreg'); ?></label>
					<p class="help-block">
						<?php esc_html_e('Pease enter PayPal currency', 'seatreg'); ?>.
					</p>
					<input type="text" class="form-control" id="paypal-currency-code" name="paypal-currency-code" autocomplete="off" placeholder="<?php echo esc_html('PayPal currency code', 'seatreg'); ?>" value="<?php echo esc_html($options[0]->paypal_currency_code); ?>"> 
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
										echo '<div class="custom-container" data-type="sel" data-label="'. $custFields[$i]->label .'">';
											echo '<label><span class="l-text">', esc_html($custFields[$i]->label), '</span>';
												echo '<select>';

													for($j = 0; $j < $optLen; $j++) {
														echo '<option><span class="option-value">', esc_html($custFields[$i]->options[$j]) ,'</span></option>';
													}

												echo '</select>';
											echo '</label>';
											echo ' <i class="fa fa-times-circle remove-cust-item"></i>';
										echo '</div>';

									}else if($custFields[$i]->type == 'text'){
										echo '<div class="custom-container" data-type="text" data-label="'. $custFields[$i]->label .'">';
											echo '<label><span class="l-text">', esc_html($custFields[$i]->label), '</span>', '<input type="text" /> </label><i class="fa fa-times-circle remove-cust-item"></i>';
										echo '</div>';

									}else if($custFields[$i]->type == 'check') {
										echo '<div class="custom-container" data-type="check" data-label="'. $custFields[$i]->label .'">';
											echo '<label><span class="l-text">', esc_html($custFields[$i]->label), '</span> <input type="checkbox" /></label><i class="fa fa-times-circle remove-cust-item"></i>';
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
	?>
	    <form action="<?php echo get_admin_url(); ?>admin-post.php" method="post" id="create-registration-form">
			<h4 class="new-reg-title">
				<?php esc_html_e('Create new registration','seatreg'); ?>
			</h4>
			<label for="new-registration-name">
				<?php esc_html_e('Enter registration name','seatreg'); ?>
			</label>
	    	<input type="text" name="new-registration-name" id="new-registration-name" style="margin-left: 12px" maxlength="<?php echo SEATREG_REGISTRATION_NAME_MAX_LENGTH; ?>">
			<input type='hidden' name='action' value='seatreg_create_submit' />
			<?php echo seatrag_generate_nonce_field('seatreg-admin-nonce'); ?>
			<?php
				submit_button(esc_html__('Create new registration', 'seatreg'));
			?>
	    </form>
	<?php
}

function seatreg_create_delete_registration_from($registrationCode) {
	?>
	    <form action="<?php echo get_admin_url(); ?>admin-post.php" method="post" class="seatreg-delete-registration-form" onsubmit="return confirm('Do you really want to delete?');">
	    	<input type="hidden" name="registration-code" value="<?php echo esc_attr($registrationCode); ?>" />
			<input type='hidden' name='action' value='seatreg_delete_registration' />
			<?php echo seatrag_generate_nonce_field('seatreg-admin-nonce'); ?>
			<?php
				submit_button(esc_html__('Delete', 'seatreg'), 'delete-registration-btn', 'delete-registration', false, array( 'id' => "delete-$registrationCode" ));
			?>
	    </form>
	<?php
}

function seatreg_generate_booking_manager() {
	$active_tab = null;
	$order = 'date';
	$searchTerm = '';

	if( SeatregDataValidation::tabsDataExists() ) {
	    $active_tab = sanitize_text_field($_GET[ 'tab' ]);
		$validation = SeatregDataValidation::validateTabData($active_tab );

		if( !$validation->valid ) {
			wp_die($validation->errorMessage);
		}
	}

	if( SeatregDataValidation::orderDataExists() ) {
		$order = sanitize_text_field($_GET[ 'o' ]);
		$validation = SeatregDataValidation::validateOrderData($order);

		if( !$validation->valid ) {
			wp_die($validation->errorMessage);
		}
	}

	if( SeatregDataValidation::searchDataExists() ) {
		$searchTerm = sanitize_text_field($_GET[ 's' ]);
		$validation = SeatregDataValidation::validateSearchData($searchTerm);

		if( !$validation->valid ) {
			wp_die($validation->errorMessage);
		}
	}

	seatreg_generate_booking_manager_html($active_tab, $order, $searchTerm);
}

//generate bookings list for manager
function seatreg_generate_booking_manager_html($active_tab, $order, $searchTerm) {
	$seatregData = seatreg_get_options($active_tab);

	if( count($seatregData) == 0 ) {
		seatreg_no_registration_created_info();

		return;
	}

    $seatregData = $seatregData[0];
	$code = $seatregData->registration_code;
	$custom_fields = json_decode($seatregData->custom_fields, true);
	$cus_length = count(is_array($custom_fields) ? $custom_fields : []);
	$regId = $seatregData->id;
	$project_name = $seatregData->registration_name;
	$bookings1 = seatreg_get_specific_bookings($code, $order, $searchTerm, '1');
	$bookings2 = seatreg_get_specific_bookings($code, $order, $searchTerm, '2');
	$row_count = count($bookings1);
	$row_count2 = count($bookings2);

	if($row_count > 0) {
		echo "<div class='bron-count-notify'>", sprintf(esc_html__('%s pending bookings', 'seatreg'), $row_count),"</div>";
	}
	
	$project_name = str_replace(' ', '_', $project_name);

	echo '<input type="hidden" id="seatreg-reg-code" value="', esc_attr($seatregData->registration_code), '"/>';
	echo '<div class="input-group manager-search-wrap">';
				echo '<input type="hidden" id="seatreg-reg-code" value="', esc_attr($seatregData->registration_code), '"/>';
            	echo '<input type="text" class="form-control manager-search" placeholder="'.esc_html__('Search booking', 'seatreg').'" maxlength="', SEATREG_REGISTRATION_SEARCH_MAX_LENGTH ,'" value="', esc_attr($searchTerm), '"/>';
            	echo '<div class="input-group-btn">';
                	echo '<button class="btn btn-default search-button" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>';
            	echo '</div>';
          echo '</div>';
	
    echo '<a href="'. get_site_url() .'?seatreg=pdf&code=', esc_attr($code) , '" target="_blank" class="file-type-link pdf-link" data-file-type="pdf"><i class="fa fa-file-pdf-o" style="color:#D81313"></i> PDF</a> ';
    echo '<a href="' . get_site_url() . '?seatreg=xlsx&code=', esc_attr($code), '" target="_blank" class="file-type-link xlsx-link" data-file-type="xlsx"><i class="fa fa-file-excel-o" style="color:#6FAA19"></i> XLSX</a> ';
    echo '<a href="' . get_site_url() . '?seatreg=text&code=', esc_attr($code), '"class="file-type-link text-link" data-file-type="text"><i class="fa fa-file-text-o" style="color:#000"></i> Text</a> ';

	echo '<div class="bg-color">';
		echo '<div class="tab-container">';
			echo '<ul class="etabs">';
				echo '<li class="tab"><a href="#', esc_html($project_name), 'bron">', esc_html_e('Pending', 'seatreg'), '</a></li>';
				echo '<li class="tab"><a href="#', esc_html($project_name), 'taken">', esc_html_e('Approved','seatreg'),'</a></li>';
			echo '</ul>';
		echo '<div class="panel-container differentBgColor">';
				echo '<div class="registration-manager-labels">
						<div class="seat-nr-box manager-box manager-box-link" data-order="nr">', esc_html__('Seat','seatreg'),'</div>
						<div class="seat-room-box manager-box manager-box-link" data-order="room">', esc_html__('Room','seatreg'),'</div>
						<div class="seat-name-box manager-box manager-box-link" data-order="name">', esc_html__('Name','seatreg'),'</div>
						<div class="seat-name-box manager-box manager-box-link" data-order="date">', esc_html__('Date','seatreg'),'</div>
						<div class="seat-date-box manager-box manager-box-link" data-order="id">', esc_html__('Booking id','seatreg'),'</div>	
					</div>';
				echo '<div id="', esc_html($project_name), 'bron" class="tab_container">';

			if($row_count == 0) {
				echo '<div class="notify-text">', esc_html__('No pending seats', 'seatreg'),'</div>';
			}			

			foreach ($bookings1 as $row) {
				$custom_field_data = json_decode($row->custom_field_data, true);
				$booking = $row->booking_id;
				$registrationId = $row->id;
				$time = strtotime($row->booking_date);
				$myFormatForView = date("m-d-y", $row->booking_date);
				
				echo '<div class="reg-seat-item">';
					echo '<div class="seat-nr-box manager-box">', esc_html($row->seat_nr), '</div>';
					echo '<div class="seat-room-box manager-box" title="',esc_html($row->room_name),'">', esc_html($row->room_name),'</div>';
					echo '<div class="seat-name-box manager-box" title="' . esc_html($row->first_name) . ' '. esc_html($row->last_name).'"><input type="hidden" class="f-name" value="'.esc_html($row->first_name).'"/><input type="hidden" class="l-name" value="'. esc_html($row->last_name) .'" /><span class="full-name">', esc_html($row->first_name), ' ', esc_html($row->last_name), '</span></div>';
					echo '<div class="seat-date-box manager-box" title="', esc_html(date('M j Y h:i e', $row->booking_date)),'">',esc_html($myFormatForView),'</div>';
					echo "<div class='booking-id-box manager-box' title='",esc_html($row->booking_id), "'>",esc_html($row->booking_id),"</div>";
					echo '<button class="btn btn-outline-secondary btn-sm show-more-info">', esc_html__('More info','seatreg'), '</button>';
					echo "<span class='edit-btn' data-code='", esc_attr($code),"' data-booking='", esc_attr($booking),"' data-id='", esc_attr($registrationId),"'><i class='fa fa-pencil-square-o' aria-hidden='true'></i>", esc_html__('Edit','seatreg'), "</span>";
					echo '<div class="action-select">';
						echo "<label class='action-label'>", esc_html__('Remove','seatreg'), "<input type='checkbox' value='", esc_attr($row->booking_id),"' class='bron-action' data-action='del'/></label>";
						echo "<label class='action-label'>", esc_html__('Approve','seatreg'), "<input type='checkbox' value='", esc_attr($row->booking_id),"' class='bron-action'data-action='confirm'/></label>";
					echo '</div>';

					echo '<div class="more-info">';
						echo '<div>', esc_html__('Registration date','seatreg'), ': <span class="time-string">', esc_html(date('M j Y h:i e', $row->booking_date)), '</span></div>';
						echo '<div>', esc_html__('Email', 'seatreg'), ': ', esc_html($row->email), '</div>';

						for($i = 0; $i < $cus_length; $i++) {
							echo seatreg_customfield_with_value($custom_fields[$i], $custom_field_data);
						}
					echo '</div>';
					echo '<input type="hidden" class="booking-identification" value='. esc_attr($row->booking_id) .' />';
				echo '</div>'; 
			}
		
			if($row_count > 0) {
				echo "<div class='action-control' data-code='", esc_attr($code), "'>", esc_html__('OK','seatreg'), "</div>";
			}
			
			echo '</div>';

			echo '<div id="', esc_html($project_name),'taken" class="tab_container active">';

			if($row_count2 == 0) {
				echo '<div class="notify-text">', esc_html__('No approved seats', 'seatreg'), '</div>';
			}

			foreach ($bookings2 as $row) {
				$custom_field_data = json_decode($row->custom_field_data, true);
				$booking = $row->booking_id;
				$registrationId = $row->id;
				$time = strtotime($row->booking_date);
				$myFormatForView = date("m-d-y", $row->booking_date);

				echo '<div class="reg-seat-item">';
					echo '<div class="seat-nr-box manager-box">',esc_html( $row->seat_nr), '</div>';
					echo '<div class="seat-room-box manager-box" title="',esc_attr($row->room_name),'">', esc_html($row->room_name),'</div>';
					echo '<div class="seat-name-box manager-box" title="'.esc_attr($row->first_name). ' '. esc_html($row->last_name).'"><input type="hidden" class="f-name" value="'.esc_html($row->first_name).'"/><input type="hidden" class="l-name" value="'. esc_html($row->last_name) .'" /><span class="full-name">', esc_html($row->first_name), ' ', esc_html($row->last_name), '</span></div>';
					echo '<div class="seat-date-box manager-box" title="', esc_html(date('M j Y h:i e', $row->booking_date)),'">',esc_html($myFormatForView),'</div>';
					echo "<div class='booking-id-box manager-box' title='",esc_attr($row->booking_id), "'>",esc_html($row->booking_id),"</div>";
					echo '<button class="btn btn-outline-secondary btn-sm show-more-info">', esc_html__('More info','seatreg'), '</button>';
					echo "<span class='edit-btn' data-code='", esc_attr($code),"' data-booking='", esc_attr($booking),"' data-id='", esc_attr($registrationId),"'><i class='fa fa-pencil-square-o' aria-hidden='true'></i>", esc_html__('Edit','seatreg'), "</span>";
					echo '<div class="action-select">';
						echo "<label>", esc_html__('Remove', 'seatreg'), "<input type='checkbox' value='", esc_attr($row->booking_id),"' class='bron-action' data-action='del'/></label>";
					echo '</div>';

					echo '<div class="more-info">';
						echo '<div>', esc_html__('Registration date','seatreg'), ': <span class="time-string">', esc_html( date('M j Y h:i e', $row->booking_date) ), '</span></div>';
						echo '<div>', esc_html__('Approval date', 'seatreg'), ': <span class="time-string">', esc_html( date('M j Y h:i e', $row->booking_confirm_date ) ), '</span></div>';
						echo '<div>Email: ', esc_html( $row->email ), '</div>';

						for($i = 0; $i < $cus_length; $i++) {
							echo seatreg_customfield_with_value($custom_fields[$i], $custom_field_data);
						}

					echo '</div>';
					echo '<input type="hidden" class="booking-identification" value='. esc_attr($row->booking_id) .' />';
				echo '</div>'; 
			}

			if($row_count2 > 0) {
				echo "<div class='action-control' data-code='", esc_attr($code), "'>", esc_html__('OK','seatreg'), "</div>";
			}

			echo '</div>';
		echo '</div>';

	echo '</div>';
	echo '</div>'; 
		
	seatreg_booking_edit_modal();
}

function seatreg_customfield_with_value($custom_field, $submitted_custom_data) {
	$cust_len = count(is_array($submitted_custom_data) ? $submitted_custom_data : []);
	$foundIt = false;

	echo '<div class="custom-field" data-type="'. $custom_field['type'] .'" ><span class="custom-field-label">', esc_html($custom_field['label']), '</span>: ';

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

function seatreg_booking_edit_modal() {

?>

<div class="modal fade" id="edit-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
	  	<h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Edit booking', 'seatreg'); ?></h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
      </div>
      <div class="modal-body">
		<form id="booking-edit-form">
	        <label><?php esc_html_e('Seat', 'seatreg'); ?> <input type="text" id="edit-seat" name="seat-nr"/></label> <span id="edit-seat-error"></span><br>
	        <label><?php esc_html_e('Room', 'seatreg'); ?> <input type="text" id="edit-room" name="room"/></label> <span id="edit-room-error"></span><br>
	        
	        <label><?php esc_html_e('First name', 'seatreg'); ?> <input type="text" id="edit-fname" name="first-name"/></label><span id="edit-fname-error"></span><br>
			<label><?php esc_html_e('Last name', 'seatreg'); ?> <input type="text" id="edit-lname" name="last-name"/></label><span id="edit-lname-error"></span><br>
			<input type="hidden" id="modal-code">
			<input type="hidden" id="booking-id">
			<input type="hidden" id="r-id">
	        <div id="modal-body-custom"></div>
	     </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
        <button type="button" class="btn btn-primary" id="edit-update-btn"><?php esc_html_e('Save changes', 'seatreg'); ?></button>
      </div>
    </div>
  </div>
</div>

<?php

}

//generate tabs
function seatreg_generate_tabs($targetPage) {
	$active_tab = null;
	$registrations = seatreg_get_registrations();

	if( SeatregDataValidation::tabsDataExists() ) {
	    $active_tab = sanitize_text_field($_GET[ 'tab' ]);
		$validation = SeatregDataValidation::validateTabData($active_tab );

		if( !$validation->valid ) {
			wp_die($validation->errorMessage);
		}
	}else {
		if(count($registrations) !== 0) {
			$active_tab = $registrations[0]->registration_code;
		}
	} 

	?>

	<h2 class="nav-tab-wrapper"> 
		<?php foreach($registrations as $key=>$value): ?>
			<a href="?page=<?php echo esc_html($targetPage); ?>&tab=<?php echo esc_html($value->registration_code); ?>" class="nav-tab <?php echo $active_tab == $value->registration_code ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html($value->registration_name); ?>
			</a>
		<?php endforeach; ?>
	</h2>
	
	<?php
}

//echo out booking info and status
function seatreg_echo_booking($registrationCode, $bookingId) {
	global $wpdb;
	global $seatreg_db_table_names;

	$registration = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg
		WHERE registration_code = %s",
		$registrationCode
	) );

	if($registration) {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE registration_code = %s
			AND booking_id = %s
			AND status != 0",
			$registrationCode,
			$bookingId
		) );

		$roomData = json_decode($registration->registration_layout)->roomData;

		foreach ($bookings as $booking) {
			$booking->room_name = seatreg_get_room_name_from_layout($roomData, $booking->room_uuid);
		}

		$options = $wpdb->get_row( $wpdb->prepare(
			"SELECT payment_text FROM $seatreg_db_table_names->table_seatreg_options
			WHERE registration_code = %s",
			$registrationCode
		) );

		if(count($bookings) > 0) {
			echo '<h4>', esc_html($registration->registration_name), '</h4>';
			echo '<h4>', esc_html__('Booking id', 'seatreg'), ': ' , esc_html($bookingId),'</h4>';

			foreach($bookings as $booking) {
				echo esc_html__('Name','seatreg'), ': ' , esc_html($booking->first_name), ' ', esc_html($booking->last_name) , '<br>', esc_html__('Seat', 'seatreg'), ': ' , esc_html($booking->seat_nr), '<br>', esc_html__('Room', 'seatreg') , ': ' , esc_html($booking->room_name), '<br>', esc_html__('Status', 'seatreg'), ': ' , ($booking->status === "1") ? esc_html__('Pending', 'seatreg') : esc_html__('Confirmed', 'seatreg'), '<br><br>';
			}

			if($options && $options->payment_text) {
				echo '<h1>', esc_html__('Payment info', 'seatreg'), '</h1>';
				echo '<p>', esc_html($options->payment_text) ,'</p>';
			}
		}else {
			esc_html_e('Booking not found.', 'seatreg');
		}
	}else {
		esc_html_e('Registration does not exist', 'seatreg');
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

//check if room and seat exist in structure and are not already booked
function seatreg_validate_del_conf_booking($code, $bookingActions) {
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
	$bookings = seatreg_get_registration_bookings($code);
	foreach($bookings as $booking) {
		$booking->room_name = seatreg_get_room_name_from_layout($structure, $booking->room_uuid);
	}

	$notBooked = true;

	foreach ($bookings as $booking) {
		foreach ($bookingActions as $bookingAction) {
			if($booking->seat_nr == $bookingAction->seat_nr && $booking->room_name == $bookingAction->room_name && $booking->status === "2" && $bookingAction->action != 'del') {
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

//for booking edit
function seatreg_validate_edit_booking($code, $data) {
	$registration = seatreg_get_options($code)[0];
	$structure = json_decode($registration->registration_layout)->roomData;
	$allCorrect = true;
    $resp = array();
    $resp['status'] = 'ok';
	$status = seatreg_check_room_and_seat($structure, $data->roomName, $data->seatNr );
	$customFieldValidation = SeatregDataValidation::validateCustomFieldEdit($data->editCustomField, $registration->custom_fields);

	if( $status['status'] != 'ok') {
			$allCorrect = false;
			$resp['status'] = $status['status'];
			$resp['text'] = $status['text'];

			return $resp;
	}else if( !$customFieldValidation->valid ) {
		$allCorrect = false;
		$resp['status'] = 'custom field validation failed';
		$resp['text'] = $customFieldValidation->errorMessage;

	}else {
		$resp['newSeatId'] = $status['newSeatId'];
		$resp['oldSeatNr'] = $data->seatNr;
		$resp['roomUUID'] = $status['roomUUID'];
	}
	$bookings = seatreg_get_registration_bookings($code);
	$notBooked = true;

	foreach ($bookings as $booking) {
		if($booking->booking_id == $data->bookingId) {

			continue;
		}
		$booking->room_name = seatreg_get_room_name_from_layout($structure, $booking->room_uuid);

		if($booking->seat_nr === $data->seatNr && $booking->room_name === $data->roomName && ($booking->status === "2" || $booking->status === "1") ) {
			$notBooked = false;
			$resp['status'] = 'seat-booked';
			$resp['text'] = esc_html__('Seat ', 'seatreg') . esc_html($data->roomName) . esc_html__(' from room ', 'seatreg') . esc_html($booking->room_name) . esc_html__(' is already booked', 'seatreg');

			break;
		}
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
				if($registrationLayout[$i]->boxes[$k]->canRegister == 'true' && $registrationLayout[$i]->boxes[$k]->seat == $bookingSeatNr) {
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
			show_bookings tinyint(1) NOT NULL DEFAULT 0,
			payment_text text,
			info text,
			booking_email_confirm tinyint(1) NOT NULL DEFAULT 1,
			paypal_payments tinyint(1) NOT NULL DEFAULT 0,
			paypal_business_email varchar(255) DEFAULT NULL,
			paypal_button_id varchar(255) DEFAULT NULL,
			paypal_currency_code varchar(3) DEFAULT NULL,
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
			seat_nr int(11) NOT NULL,
			room_uuid varchar(255) NOT NULL,
			booking_date int(11) DEFAULT NULL,
			booking_confirm_date int(11) DEFAULT NULL,
			custom_field_data text,
			status int(2) NOT NULL DEFAULT 0,
			booking_id varchar(40) NOT NULL,
			conf_code char(40) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
	  
		dbDelta( $sql3 );
		update_option( "seatreg_db_current_version", SEATREG_DB_VERSION );
	}
}

//return all registrations and their data
function seatreg_get_registrations() {
	global $wpdb;
	global $seatreg_db_table_names;

	$registrations = $wpdb->get_results(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg WHERE is_deleted = 0"
	);

	return $registrations;
}

//return specific registration and its data if registration code provided.
function seatreg_get_registration_data($code) {
	global $wpdb;
	global $seatreg_db_table_names;

	if($code != null) {
		$registration = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg
			WHERE registration_code = %s",
			$code
		) );
	}else {
		$registration = $wpdb->get_results( 
			"SELECT * FROM $seatreg_db_table_names->table_seatreg
			ORDER BY registration_create_timestamp
			LIMIT 1"
		);
	}

	return $registration;
}

//return bookings(status 2 and 3) belonging to specific registration
function seatreg_get_registration_bookings($code) {

	global $wpdb;
	global $seatreg_db_table_names;

	$bookings = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
		WHERE registration_code = %s
		AND (status = '1' OR status = '2')",
		$code
	) );

	return $bookings;
}

//return uploaded images
function seatreg_get_registration_uploaded_images($code) {
	$uploadedImages = array();
	$filePath = SEATREG_PLUGIN_FOLDER_DIR . 'uploads/room_images/' . $code . '/'; 

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
function seatreg_get_specific_bookings( $code, $order, $searchTerm, $bookingStatus ) {
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
	}

	$bookings = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
		WHERE registration_code = %s
		AND status = $bookingStatus
		ORDER BY $order",
		$code
	));
	
	$registration = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg
		WHERE registration_code = %s",
		$code
	) );

	if($registration->registration_layout !== null) {
		$roomData = json_decode($registration->registration_layout)->roomData;

		foreach ($bookings as $booking) {
			$booking->room_name = seatreg_get_room_name_from_layout($roomData, $booking->room_uuid);
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

function seatreg_get_room_name_from_layout($roomsLayout, $bookingRoomUuid) {
	$roomName = null;

	foreach($roomsLayout as $roomLayout) {
		if($roomLayout->room->uuid === $bookingRoomUuid) {
			$roomName = $roomLayout->room->name;
		}
	}

	return $roomName;
}

function seatreg_get_bookings_in_room($registrationId, $roomName) {
	global $wpdb;
	global $seatreg_db_table_names;

	$bookings = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
		WHERE registration_code = %s
		AND room_name = %s,",
		$registrationId,
		$roomName
	) );

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

    $charset_collate = $wpdb->get_charset_collate();
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
    }

    if($status === 1) {
    	return true;
    }else {
    	return false;
    }
}

//confirm, delete booking
function seatreg_confirm_or_delete_booking($action, $regCode) {
	global $seatreg_db_table_names;
	global $wpdb;

	if($action->action == 'conf') {
		$wpdb->update( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array( 
				'status' => 2,
				'booking_confirm_date' => time()
			), 
			array('booking_id' => $action->booking_id), 
			'%s',
			'%s'
		);
	}else if($action->action == 'del') {
		$wpdb->delete( 
			$seatreg_db_table_names->table_seatreg_bookings,
			array('booking_id' => $action->booking_id), 
			'%s'
		);
	}
}

//edit booking
function seatreg_edit_booking($custom_fields, $seat_nr, $room_uuid, $f_name, $l_name, $booking_id, $seat_id, $id) {
	global $seatreg_db_table_names;
	global $wpdb;

	$status = $wpdb->update( 
		$seatreg_db_table_names->table_seatreg_bookings,
		array( 
			'first_name' => $f_name,
			'last_name' => $l_name,
			'seat_nr' => $seat_nr,
			'room_uuid' => $room_uuid,
			'custom_field_data' => $custom_fields,
			'seat_id' => $seat_id
		), 
		array(
			'booking_id' => $booking_id,
			'id' => $id,
		),
		'%s'
	);
	
	return $status;
}


//for generating pdf, xlsx and text
function seatreg_get_data_for_booking_file($code, $whatToShow) {
	global $seatreg_db_table_names;
	global $wpdb;

	if($whatToShow == 'all') {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE registration_code = %s
			AND status IN (1,2)
			ORDER BY room_uuid, seat_nr",
			$code
		) );
	}else if($whatToShow == 'pending') {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE registration_code = %s
			AND status = 1
			ORDER BY room_uuid, seat_nr",
			$code
		) );
	}else {
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $seatreg_db_table_names->table_seatreg_bookings
			WHERE registration_code = %s
			AND status = 2
			ORDER BY room_uuid, seat_nr",
			$code
		) );
	}

	$registration = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $seatreg_db_table_names->table_seatreg
		WHERE registration_code = %s",
		$code
	) );

	if($registration->registration_layout !== null) {
		$roomData = json_decode($registration->registration_layout)->roomData;

		foreach($bookings as $booking) {
			$booking->room_name = seatreg_get_room_name_from_layout($roomData, $booking->room_uuid);
		}
	}
	
	return $bookings;
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

	if( empty($_POST['new-registration-name']) ) {
		wp_die('Registration name not provided');
	}

	if( $_POST['new-registration-name'] === '' ) {
		wp_die('Please provide registration name');
	}

	$registrationName = sanitize_text_field($_POST['new-registration-name']); 
	$nameValidation = SeatregDataValidation::validateRegistrationName($registrationName);

	if( !$nameValidation->valid ) {
		wp_die($nameValidation->errorMessage);
	}

	if( seatreg_create_new_registration($registrationName) ) {
		wp_redirect( $_POST['_wp_http_referer'] );

		die();
	}else {
		wp_die( esc_html_e('Something went wrong while creating a new registration', 'seatreg') );
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
		wp_redirect( $_POST['_wp_http_referer'] );
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

	$registrationName = sanitize_text_field($_POST['registration-name']);
	$registrationNameValidation = SeatregDataValidation::validateRegistrationName($registrationName);

	if( !$registrationNameValidation->valid ) {
		wp_die($registrationNameValidation->errorMessage);
	}

	$customFileds = stripslashes_deep( $_POST['custom-fields'] );
	$customFiledsValidation = SeatregDataValidation::validateCustomFieldCreation($customFileds);

	if( !$customFiledsValidation->valid ) {
		wp_die($customFiledsValidation->errorMessage);
	}

	if(!isset($_POST['gmail-required'])) {
		$_POST['gmail-required'] = 0;
	}else {
		$_POST['gmail-required'] = 1;
	}
	
	if(!isset($_POST['registration-status'])) {
		$_POST['registration-status'] = 0;
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

	if(!isset($_POST['paypal-payments'])) {
		$_POST['paypal-payments'] = 0;  
	}else {
		$_POST['paypal-payments'] = 1;
	}
	
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
			'show_bookings' => sanitize_text_field($_POST['show-registration-bookings']),
			'payment_text' => $_POST['payment-instructions'] == '' ? null : sanitize_text_field($_POST['payment-instructions']),
			'info' => sanitize_text_field($_POST['registration-info-text']),
			'custom_fields' => $customFileds,
			'booking_email_confirm' => sanitize_text_field($_POST['email-confirm']),
			'paypal_payments' => $_POST['paypal-payments'],
			'paypal_business_email' => sanitize_text_field($_POST['paypal-business-email']),
			'paypal_button_id' => sanitize_text_field($_POST['paypal-button-id']),
			'paypal_currency_code' => sanitize_text_field($_POST['paypal-currency-code']),
		),
		array(
			'registration_code' => sanitize_text_field($_POST['registration_code'])
		),
		'%s',
		'%s'
	);

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

	return ($status1 !== false && $status2 !== false);
}

//handle settings form submit
add_action('admin_post_seatreg-form-submit', 'seatreg_form_submit_handle'); 
function seatreg_form_submit_handle() {
	seatreg_check_user_capabilities();
	check_admin_referer('seatreg-options-submit', 'seatreg-options-nonce');

	if( seatreg_update() === false) {
		wp_die('Error updating settings');
	}else {
		wp_redirect($_POST['_wp_http_referer']);

		die();
	}
}

/*
====================================================================================================================================================================================
Ajax stuff
====================================================================================================================================================================================
*/

function seatreg_ajax_security_check() {
	seatreg_check_user_capabilities();
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
	seatreg_ajax_security_check();

	$registration = seatreg_get_registration_data(sanitize_text_field($_POST['code']));
	$bookings = seatreg_get_registration_bookings(sanitize_text_field($_POST['code']));
	$uploadedImages = seatreg_get_registration_uploaded_images(sanitize_text_field($_POST['code']));
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
	seatreg_ajax_security_check();
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
	
	global $wpdb;
	global $seatreg_db_table_names;
	$status = $wpdb->update(
		"$seatreg_db_table_names->table_seatreg",
		array(
			'registration_layout' => $updateData
		),
		array(
			'registration_code' => sanitize_text_field($_POST['registration_code'])
		),
		array('%s'),
		array('%s')
	);
	
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

add_action( 'wp_ajax_seatreg_booking_submit', 'seatreg_booking_submit_callback' );
add_action( 'wp_ajax_nopriv_seatreg_booking_submit', 'seatreg_booking_submit_callback' );
function seatreg_booking_submit_callback() {
	$resp = new SeatregJsonResponse();
	
	session_start();

	if ( ! wp_verify_nonce( $_POST['seatreg-booking-submit'], 'seatreg-booking-submit' ) ) {
		$resp->setError('Nonce validation failed');
		$resp->echoData();
				
		die();
	}
/*
	if($_SESSION['seatreg_captcha'] !== $_POST['capv']) {
		$r = seatreg_random_string(10);
	    $resp->setError('Wrong captcha');
	    $resp->setData('<img src="'. SEATREG_PLUGIN_FOLDER_URL .'registration/php/image.php?dummy='.$r.'" id="captcha-img"/>');
		$resp->echoData();

		die();
	}
*/

	if( empty($_POST['FirstName']) || 
		empty($_POST['LastName']) || 
		empty($_POST['Email']) || 
		empty($_POST['item-id']) ||
		empty($_POST['item-nr']) ||
		empty($_POST['room-uuid']) ||
		empty($_POST['em']) ||
		empty($_POST['c']) ||
		!isset($_POST['pw']) ||
		empty($_POST['custom'])) {
			$resp->setError('Missing data');
			$resp->echoData();
			
			die();
	}

	$newBooking = new SeatregSubmitBookings( $_POST['c'], $resp );

	if( $newBooking->validateBookingData(
			$_POST['FirstName'], 
			$_POST['LastName'], 
			$_POST['Email'], 
			$_POST['item-id'], 
			$_POST['item-nr'], 
			$_POST['em'], 
			$_POST['c'], 
			$_POST['pw'], 
			$_POST['custom'],
			$_POST['room-uuid']) 
	){
		$newBooking->validateBooking();
	}	
	
	$resp->echoData();

	die();
}

add_action( 'wp_ajax_seatreg_get_room_stats', 'seatreg_get_room_stats_callback' );
function seatreg_get_room_stats_callback() {
	seatreg_ajax_security_check();

	seatreg_generate_overview_section_html(sanitize_text_field($_POST['data']), sanitize_text_field($_POST['code']));

	die();
}

add_action( 'wp_ajax_seatreg_new_captcha', 'seatreg_new_captcha_callback' );
add_action( 'wp_ajax_nopriv_seatreg_new_captcha', 'seatreg_new_captcha_callback' );
function seatreg_new_captcha_callback() {
	$r = seatreg_random_string(10);
	echo '<img src="' . SEATREG_PLUGIN_FOLDER_URL . 'registration/php/image.php?dummy='. esc_html($r) .'" id="captcha-img" />';

	die();
}

add_action( 'wp_ajax_seatreg_get_booking_manager', 'seatreg_get_booking_manager_callback' );
function seatreg_get_booking_manager_callback() {
	seatreg_ajax_security_check();
	if( empty( $_POST[ 'code' ] ) || empty( $_POST['data']['orderby'] ) || !isSet( $_POST['data']['searchTerm'] ) ) {
		wp_die('Missing data');
	}

	$order = sanitize_text_field($_POST['data']['orderby']);
	$code = sanitize_text_field($_POST['code']);
	$search = sanitize_text_field($_POST['data']['searchTerm']); 

	if( strlen($code) > SEATREG_REGISTRATION_NAME_MAX_LENGTH ) {
		wp_die('Too long code');
	}

	if( strlen($search) > SEATREG_REGISTRATION_SEARCH_MAX_LENGTH ) {
		wp_die('Too long search');
	}

	if( !in_array($order, SEATREG_MANAGER_ALLOWED_ORDER) ) {
		wp_die('Too long search');
	}

	seatreg_generate_booking_manager_html(
		$code,
		$order,
		$search
	);

	die();
}

add_action( 'wp_ajax_seatreg_confirm_del_bookings', 'seatreg_confirm_del_bookings_callback' );
function seatreg_confirm_del_bookings_callback() {
	seatreg_ajax_security_check();

	$data = json_decode( stripslashes_deep($_POST['data']['actionData']) );
	$statusArray = seatreg_validate_del_conf_booking( sanitize_text_field($_POST['code']), $data );

	if ( $statusArray['status'] != 'ok' ) {
		$errorText = '';

		switch( $statusArray['status'] ) {
			case 'room-searching':
				$errorText = $statusArray['text'];

				break;
			case 'seat-nr-searching';
				$errorText = $statusArray['text'];

				break;
			case 'seat-booked';
				$errorText = $statusArray['text'];

				break;

		}

		echo '<div class="alert alert-danger" role="alert">', $errorText ,'</div>';
		
	}else {
		foreach ($data as $key => $value) {
			seatreg_confirm_or_delete_booking( $value, sanitize_text_field($_POST['code']));
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
	seatreg_generate_booking_manager_html( sanitize_text_field($_POST['code']) , $order, $searchTerm );

	die();
}

add_action( 'wp_ajax_seatreg_search_bookings', 'seatreg_search_bookings_callback' );
function seatreg_search_bookings_callback() {
	seatreg_ajax_security_check();
	$order = 'date';
	$searchTerm = '';

	if( !empty( $_POST['data']['orderby'] ) ) {
		$order = sanitize_text_field($_POST['data']['orderby']);
	}

	if( !empty( $_POST['data']['searchTerm'] ) ) {
		$searchTerm = sanitize_text_field($_POST['data']['searchTerm']);
	}
	seatreg_generate_booking_manager_html( sanitize_text_field($_POST['code']) , $order, $searchTerm );

	die();
}

add_action( 'wp_ajax_seatreg_edit_booking', 'seatreg_edit_booking_callback' );
function seatreg_edit_booking_callback() {
	seatreg_ajax_security_check();

	$bookingEdit = new stdClass();
	$bookingEdit->firstName = sanitize_text_field($_POST['fname']);
	$bookingEdit->lastName = sanitize_text_field($_POST['lname']);
	$bookingEdit->seatNr = sanitize_text_field($_POST['seatnumber']);
	$bookingEdit->roomName = sanitize_text_field($_POST['room']);
	$bookingEdit->seatId = sanitize_text_field($_POST['seatid']);
	$bookingEdit->bookingId = sanitize_text_field($_POST['bookingid']);
	$bookingEdit->editCustomField = stripslashes_deep($_POST['customfield']);
	$bookingEdit->id = sanitize_text_field($_POST['id']);

	$statusArray = seatreg_validate_edit_booking(sanitize_text_field($_POST['code']), $bookingEdit );

	if ( $statusArray['status'] != 'ok' ) {
		wp_send_json( array('status'=>$statusArray['status'], 'text'=> $statusArray['text'] ) );

		die();
	}

	if( seatreg_edit_booking( 
			$bookingEdit->editCustomField, 
			$bookingEdit->seatNr, 
			$statusArray['roomUUID'], 
			$bookingEdit->firstName,
			$bookingEdit->lastName,
			$bookingEdit->bookingId, 
			$statusArray['newSeatId'],
			$bookingEdit->id
		) !== false) {
		wp_send_json( array('status'=>'updated') );

		die();
	}else {
		wp_send_json( array('status'=>'update failed') );

		die();
	}
}

add_action( 'wp_ajax_seatreg_upload_image', 'seatreg_upload_image_callback' );
function seatreg_upload_image_callback() {
	seatreg_ajax_security_check();

	$resp = new SeatregJsonResponse();

	if(empty($_FILES["fileToUpload"]) || empty($_POST['code'])) {
		$resp->setError('No picture selected');
		$resp->echoData();

		die();
	}

	$code = sanitize_text_field($_POST['code']);
	$registration_upload_dir = SEATREG_PLUGIN_FOLDER_DIR . 'uploads/room_images/' . $code . '/';
	$target_file = $registration_upload_dir . basename(sanitize_file_name($_FILES["fileToUpload"]["name"]));
	$target_dimentsions = null;
	$imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);
	$allowedFileTypes = array('jpg', 'png', 'jpeg', 'gif');

	// Check if image file is a actual image or fake image
	$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);

	if($check == false) {
		$resp->setError('File is not an image');
		$resp->echoData();

		die();
	}
	$target_dimentsions = $check[0] . ',' . $check[1];

	// Check if file already exists
	if (file_exists($target_file)) {
		$resp->setError('Sorry, picture already exists');
		$resp->echoData();

		die();

	}

	// Check file size                    
	if ($_FILES["fileToUpload"]["size"] > 2120000 ) {
		$resp->setError('Sorry, your file is too large');
		$resp->echoData();

		die();		
	}

	// Allow certain file formats
	if( !in_array($imageFileType, $allowedFileTypes)  ) {
		$resp->setError('Sorry, only JPG, JPEG, PNG & GIF files are allowed');
		$resp->echoData();

		die();
	}

	//check if folder exists
	if (!file_exists($registration_upload_dir)) {
		mkdir($registration_upload_dir, 0755, true); //create folder
	}
			
	if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
		$resp->setText("The picture ". basename( sanitize_file_name($_FILES["fileToUpload"]["name"]) ). " has been uploaded.");
		$resp->setData(basename( sanitize_file_name($_FILES["fileToUpload"]["name"]) ));
		$resp->setExtraData($target_dimentsions);
		$resp->echoData();

		die();
	} else {
		$resp->setError('Sorry, there was an error uploading your file');
		$resp->echoData();

		die();
	}
}

add_action( 'wp_ajax_seatreg_remove_img', 'seatreg_remove_img_callback' );
function seatreg_remove_img_callback() {
	seatreg_ajax_security_check();

	$resp = new SeatregJsonResponse();

	if(!empty($_POST['imgName']) && !empty($_POST['code'])) {
		//check if file exists
		$imgPath = SEATREG_PLUGIN_FOLDER_DIR . 'uploads/room_images/' . sanitize_text_field($_POST['code']) . '/' . sanitize_text_field($_POST['imgName']);
		
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

/*
==================================================================================================================================================================================================================
Paypal functions
==================================================================================================================================================================================================================
*/

function generatePayPalPayNowForm($formAction, $businessEmail, $buttonId, $amount, $currencyCode, $bookingStatusPage) {
	?>
		<form method="post" action="https://www.sandbox.paypal.com/cgi-bin/webscr">
			<input type="hidden" name="cmd" value="_xclick">
			<input type="hidden" name="business" value="sb-rcb4772629866@business.example.com">
			<input type="hidden" name="item_name" value="booking">
			<input type="hidden" name="notify_url" value="http://XXXXXXX.com/ipn.php" />
			<input type="hidden" name="hosted_button_id" value="7P7TRLKCANBQ8" />
			<input type="hidden" name="amount" value="100">
			<input type="hidden" name="currency_code" value="USD"/>
			<input type="hidden" name="no_shipping" value="1">
			<input type='hidden' name="cancel_return" value='http://seatreg/?seatreg=booking-status&registration=abd01058ed&id=86b4f48f40ab97553ca6bc0d2dff4d82234630d0' />
			<input type="hidden" name="return" value="http://seatreg/?seatreg=booking-status&registration=abd01058ed&id=86b4f48f40ab97553ca6bc0d2dff4d82234630d0">
			<input type="image" src="https://www.sandbox.paypal.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		</form>
	<?php
}