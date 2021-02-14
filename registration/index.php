<?php
	if(empty($_GET['c'])){
		exit();
	}

	require_once('./../php/util/registration_time_status.php');
	require_once('php/reg_functions.php');
	require_once('./../php/seatreg_strings.php');

	$data = seatreg_get_options_reg($_GET['c'])[0];
	$showPwdForm = false;

	if($data->registration_password != null ) {
		//view password is set

		if(empty($_POST['reg_pwd'])) {
			//need to ask pwd
			$showPwdForm = true;

		}else if(!empty($_POST['reg_pwd'])) {
			//ok pwd is entered
			if($_POST['reg_pwd'] != $data->registration_password ) {
				$showPwdForm = true;
			}
		}
	}

 	if(!$showPwdForm) {
		$seatsInfo = json_encode( seatreg_stats_for_registration_reg($data->registration_layout, $data->registration_code) );
	
		if($data->show_bookings == '1') {
			$registrations = json_encode(seatreg_get_registration_bookings_reg($_GET['c'], true)); //also names
		}else {
			$registrations = json_encode(seatreg_get_registration_bookings_reg($_GET['c'], false));  //no names
		}

		$registrationTime = seatreg_registration_time_status( $data->registration_start_timestamp,  $data->registration_end_timestamp );
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php echo htmlspecialchars( $data->registration_name ); ?></title>
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<link rel="shortcut icon" type="image/png" href="favicon.png"/>
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="css/registration.min.css">

	<?php if($data->registration_open == 1) : ?>	
		<script src="js/modernizr.custom.89593.min.js"></script>
	<?php else : ?>
		
		<style>
			html, body {
				height: 100%;
			}
			#center-wrap {
				text-align: center;
				height: 100%;
			}
			#center-wrap:before {
				 content: '';
				 display: inline-block;
				 height: 100%;
				 vertical-align: middle;
			}
			#center-wrap .center-header {
				display: inline-block;
	  			vertical-align: middle;
			}
		</style>
	<?php endif; ?>
</head>
<body>
<?php include('noscript.html'); ?>	

	<?php if(!$showPwdForm) : ?>
		<?php if($data->registration_open == 0) : ?>
	    	<div id="center-wrap">
				<h2 class="center-header">
					<?php
						printf(
							/* translators: %s: Name of the registration */
							__( 'Registration %s is closed at the moment', 'seatreg' ),
							$data->registration_name
						);
					?>
				</h2>
	    	</div>
		<?php else : ?>

		<?php if($data->registration_layout != null && $data->registration_layout !== '{}'): ?>
			<header id="main-header">
				<?php echo htmlspecialchars($data->registration_name);?>
			</header>
			<div id="room-nav-wrap" class="border-box no-select">
				<div id="room-nav">
					<div id="room-nav-items">
						<div id="room-nav-close" class="close-btn">
							<div class="close-btn-bg"></div>
							<i class="fa fa-times-circle"></i>
						</div>
					</div>
				</div>

				<div id="room-nav-info" class="border-box">
					<div id="room-nav-info-inner"></div>
				</div>

				<div id="room-nav-btn-wrap" class="border-box">
					<div id="current-room-name"></div>
					<div id="room-nav-btn">
						<?php _e('Change room', 'seatreg'); ?>
					</div>
					<div class="room-nav-extra-info-btn">
						<i class="fa fa-info-circle"></i>
					</div>
				</div>
				
			</div>
			<div id="middle" class="no-select">
				<div id="view-wrap">
					<div id="middle-section">
						<div id="box-wrap">
							<div id="boxes">
					
							</div>
						</div>
					</div>
					<div id="room-is-empty" class="dont-display">
						<p class="room-is-empty-text">
							<?php _e('Room is empty', 'seatreg'); ?>
						</p>
					</div>		
				</div>

				<div id="legend-wrapper" class="border-box">
					<div id="legends"></div>
				</div>

				<div id="seat-cart" class="border-box no-select">
					<div class="seat-cart-left">
						<div id="cart-text">
							<div class="seats-in-cart">0</div>
							<div><?php _e('seats selected', 'seatreg'); ?></div> 
							<div class="max-seats">
								(<?php 
									_e('Max', 'seatreg');
									if($data->seats_at_once > 1) {
										echo htmlspecialchars( $data->seats_at_once ); 
									}else {
										echo htmlspecialchars( $data->seats_at_once ); 
									}
								?>)
							</div>
						</div>
					</div>

					<div class="seat-cart-right">
						<div id="cart-checkout-btn" class="border-box">
							<?php 
								_e('Open', 'seatreg');
							?>
						</div>
					</div>
				</div>

				<div id="zoom-controller" class="no-select">
					<i class="fa fa-arrow-circle-up move-action" data-move="up"></i><br>
					<i class="fa fa-arrow-circle-left move-action" data-move="left"></i>
					<i class="fa fa-arrow-circle-right move-action" data-move="right"></i><br>
					<i class="fa fa-arrow-circle-down move-action" data-move="down"></i><br><br>
					<i class="fa fa-plus zoom-action" data-zoom="in"></i><br>
					<i class="fa fa-minus zoom-action" data-zoom="out"></i>
				</div>

				<div class="room-nav-extra-info-btn big-display-btn">
					<i class="fa fa-info-circle"></i>
				</div>
			</div>

			<div id="extra-info" class="dialog-box">
						
				<div id="extra-info-inner" class="border-box dialog-box-inner">
					<div id="info-close-btn" class="close-btn">
						<div class="close-btn-bg"></div>
						<i class="fa fa-times-circle"></i>
					</div>
					<h3>
						<?php 
							_e('Registration info', 'seatreg');
						?>
					</h3>
					<?php
						if($data->info != null) {
							echo '<div>',htmlspecialchars($data->info),'</div><br>';
						}

						if($data->registration_start_timestamp != null) {
							?>
								<div>
									<div class="flag1"></div>
									<?php 
										_e('Registration start', 'seatreg');
									?>
									<span class="time">
										<?php
										    echo htmlspecialchars($data->registration_start_timestamp);
										?>
									</span>
								</div>
							<?php
						}

						if($data->registration_end_timestamp != null) {
							?>
								<div>
									<div class="flag2"></div>
									<?php 
										_e('Registration end', 'seatreg');
									?>
									<span class="time">
										<?php
											echo htmlspecialchars($data->registration_end_timestamp);
										?>
									</span>
								</div>
							<?php
						}
					?>
					<div>
						<?php _e('Total rooms', 'seatreg'); ?>: <span class="total-rooms"></span>
					</div>
					<div>
						<?php _e('Total open seats', 'seatreg'); ?>: <span class="total-open"></span>
					</div>
					<div>
						<?php _e('Total pending seats', 'seatreg'); ?>: <span class="total-bron"></span>
					</div>
					<div>
						<?php _e('Total confirmed seats', 'seatreg'); ?>: <span class="total-tak"></span>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div id="modal-bg"></div>

		<div id="legend-popup-dialog" class="dialog-box">
			<div id="legend-popup-dialog-inner" class="dialog-box-inner border-box">
				<div class="close-btn">
					<div class="close-btn-bg"></div>
					<i class="fa fa-times-circle"></i>
				</div>
				
				<h2>
					<?php
						_e('Legends', 'seatreg');
					?>
				</h2>
				<div class="legend-popup-legends">
				</div>
			</div>
		</div>

		<div id="confirm-dialog-mob" class="dialog-box">
			<div id="confirm-dialog-mob-inner" class="dialog-box-inner border-box">

				<div id="dialog-close-btn" class="close-btn">
					<div class="close-btn-bg"></div>
					<i class="fa fa-times-circle"></i>
				</div>

				<div id="confirm-dialog-mob-legend" class="confirm-dialog-mob-block"></div>
				<div id="confirm-dialog-mob-hover" class="confirm-dialog-mob-block"></div>
				<div id="confirm-dialog-mob-text"></div>

				<?php if($registrationTime == 'run') : ?>

					<div id="confirm-dialog-bottom">
						<div id="confirm-dialog-mob-ok" class="seatreg-btn green-btn">
							<?php 
								_e('Add to booking', 'seatreg');
							?>
						</div>
						<div id="confirm-dialog-mob-cancel" class="seatreg-btn red-btn">
							<?php 
								_e('Close', 'seatreg');
							?>
						</div>
					</div>

				<?php endif; ?>

				<input type="hidden" id="selected-seat">
				<input type="hidden" id="selected-seat-room">
				<input type="hidden" id="selected-seat-nr">
				<input type="hidden" id="selected-room-uuid">
			</div>
		</div>

		<div id="seat-cart-popup" class="dialog-box">	
			<div class="cart-popup-inner dialog-box-inner border-box">

				<div id="cart-popup-close" class="close-btn">
					<div class="close-btn-bg"></div>
					<i class="fa fa-times-circle"></i>
				</div>

				<div id="seat-cart-info">
					<?php 
						_e('Cart', 'seatreg');
					?>
				</div>
				<?php if($registrationTime == 'run') : ?>
					<div id="seat-cart-rows">
						<div class="row-nr">
							<?php
								_e('NR', 'seatreg');
							?>
						</div>
						<div class="row-room">
							<?php 
								_e('Room', 'seatreg');
							?>
						</div>
					</div>
					
					<div id="seat-cart-items"></div>
					
					<div id="checkout" class="seatreg-btn green-btn">
						<?php
							_e('Next', 'seatreg');
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div id="checkout-area" class="dialog-box">
			<form id="checkoput-area-inner" class="dialog-box-inner border-box">
				<div id="checkout-close" class="close-btn">
					<div class="close-btn-bg"></div>
					<i class="fa fa-times-circle"></i>
				</div>
				<div class="checkout-header">
					<?php
						_e('Booking data', 'seatreg');
					?>
				</div>
				<div id="checkout-input-area"></div>
				<div id="captchaWrap">				
					<label for="captcha-val" style="vertical-align:middle">
						<span id="captcha-text">
							<?php
								_e('Enter code', 'seatreg');
							?>:
						</span>
					</label>
					<img src="php/image.php" id="captcha-img" alt="captcha image"/>
					<div id="captcha-ref" class="refresh1">
						<i class="fa fa-refresh"></i>
					</div><br>
					
					<input type="text" id="captcha-val" name="capv" />				
				</div>
				<button type="submit" id="checkout-confirm-btn" class="seatreg-btn green-btn">
					<?php 
						_e('OK', 'seatreg');
					?>
				</button>
				<img src="css/ajax_loader.gif" alt="Loading" class="ajax-load">
				<div id="request-error"></div>
			</form>
		</div>

		<input type="hidden" name="pw" id="sub-pwd" value="<?php if(!empty($_POST['reg_pwd'])) {echo $_POST['reg_pwd'];} ?>" />

		<div id="bottom-wrapper">
			<div class="mobile-cart">
				<div class="cart-icon-text">
					<span class="seats-in-cart">0</span> 
					<?php 
						_e('seats selected', 'seatreg');
					?>
					<span class="max-seats">
						(<?php
							_e('Max', 'seatreg');
						?>
						<?php 
							if($data->seats_at_once > 1) {
								echo htmlspecialchars($data->seats_at_once),')<br>'; 
							}else {
								echo htmlspecialchars($data->seats_at_once),')<br>'; 
							}	
						?>
					</span>
				</div>
			</div>
			<div class="mobile-legend">
				<?php 
					_e('Show legends', 'seatreg');
				?>
			</div>
		</div>

		<div id="email-conf" class="dialog-box">
			<div id="email-conf-inner" class="dialog-box-inner border-box animated zoomIn">
				<h2>
					<?php 
						_e('Confirm email sent to', 'seatreg'); 
					?>
				<span id="email-send"></span></h2>
				<p>
				<?php 
					_e('You need to confirm your booking by following email instructions. Make sure you check your junk folders', 'seatreg');
				?>.
				</p>
				<button class="refresh-btn">
					<?php 
						_e('OK', 'seatreg');
					?>
				</button>
			</div>
		</div>

		<div id="bookings-confirmed" class="dialog-box">
			<div id="bookings-confirmed-inner" class="dialog-box-inner border-box animated zoomIn">
				<h2 class="booking-confirmed-header">
					<?php 
						_e('You Bookings are confirmed', 'seatreg');
					?>		
				</h2>
				<p>
					<?php _e('You can look your bookings status at the following link'); ?><br>
					<a href="" class="booking-check-url" target="_blank"></a>
				</p>
				<p>
					<?php _e('Save the link for future reference', 'seatreg'); ?>
				</p>
				<button class="refresh-btn">
					<?php 
						_e('OK', 'seatreg');
					?>
				</button>
			</div>
		</div>

		<div id="error" class="dialog-box">
			<div id="error-inner" class="dialog-box-inner border-box animated zoomIn">
				<h2>
					<?php 
						_e('Error', 'seatreg');
					?>
				</h2>
				<p id="error-text"></p>
				<button class="refresh-btn">
					<?php 
						_e('OK', 'seatreg');
					?>
				</button>
			</div>
		</div>

		<?php if($registrationTime == 'wait' || $registrationTime == 'end') : ?>
				<div class="modal-bg"></div>

				<div id="time-notify" class="dialog-box" style="display:block">
					<div class="dialog-box-inner border-box">
						<div id="close-time" class="close-btn">
							<div class="close-btn-bg"></div>
							<i class="fa fa-times-circle"></i>
						</div>
				
						<?php
							if($registrationTime == 'wait') {
								echo '<h3>', _e('Not open yet', 'seatreg'), '</h3>';
								echo '<h4>', _e('Registration starts', 'seatreg'), ': <span class="time">', htmlspecialchars($data->registration_start_timestamp), '</span></h4>';
							}else if($registrationTime == 'end') {
								echo '<h3>', _e('Closed', 'seatreg'), '</h3>';
								echo '<h4>', _e('Registration ended', 'seatreg'), ': <span class="time">',htmlspecialchars($data->registration_end_timestamp), '</span></h4>';
							}
						?>
					</div>
				</div>

		<?php endif; ?>

		<script src="js/jquery.3.5.1.min.js"></script>
		<script>
			try {
				var seatregTranslations = $.parseJSON('<?php echo json_encode(seatreg_generate_registration_stringes()); ?>');
				var seatLimit = <?php echo $data->seats_at_once;?>;
				var gmail = <?php echo $data->gmail_required;?>;
				var dataReg = $.parseJSON('<?php echo ($data->registration_layout);?>');
				var roomsInfo = $.parseJSON('<?php echo $seatsInfo;?>');
				var custF = $.parseJSON('<?php echo $data->custom_fields; ?>');
				var regTime = <?php echo "'$registrationTime';";?>
				var registrations = $.parseJSON(<?php echo "'$registrations'";?>);
			} catch(err) {
				alert('Data gathering failed');
			}
		</script>
	
		<script src="js/date.format.js"></script>
		<script src="js/iscroll-zoom-5-1-3.js"></script>
		<script src="js/jquery.powertip.js"></script>
		<script src="js/registration.js"></script>
		<!--
		<script src="js/registration.min.js"></script>
		-->
		<?php endif; //end of is registration open ?>  

	<?php else : ?>
		<form method="post" id="pwd-form">
			<h2>
				<?php 
					_e('Password protected', 'seatreg');
				?>
			</h2>
			<label for="reg-pwd">
				<?php 
					_e('Please enter password', 'seatreg');
				?>
			</label>
			<input type="password" name="reg_pwd" id="reg-pwd" /><br><br>
			<input type="submit" value="<?php _e('OK', 'seatreg'); ?>" />
		</form>			
	<?php endif; ?>
</body>
</html>