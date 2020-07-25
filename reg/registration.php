<?php

	if(empty($_GET['c'])){
		exit();
	}

	require_once('php/reg_functions.php');

	

	$data = seatreg_get_options_reg($_GET['c'])[0];

	$showPwdForm = false;
	$isPremium = true;

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


		
		 // 'running', 'not_started', 'over'   null is when times have not set


		$registrationTime = seatreg_registration_time_status_reg( $data->registration_start_timestamp,  $data->registration_end_timestamp );

		
		//$registrationTime will be:   'run', 'wait','end',
		//echo $registrationTime;
	}
	


	
	//print_r($seatsInfo);
	//$data2 = getTakenSpots($_GET['v']);
	
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php echo htmlspecialchars( $data->registration_name ); ?></title>
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<link rel="shortcut icon" type="image/png" href="favicon.png"/>
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="css/view.all.min.css">

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

	<!--[if lt IE 9]>
	  <script src="js/html5shiv.js"></script>
	  <script src="js/respond.js"></script>
	<![endif]-->

	

</head>
<body>
<?php include('noscript.html'); ?>	
	<input type="hidden" id="spot-name" value="<?php //echo htmlspecialchars($data['spot_name']); ?>">

	<?php if(!$showPwdForm) : ?>


		<?php if($data->registration_open == 0) : ?>

	    	<div id="center-wrap">
					<h2 class="center-header">Registration <?php echo htmlspecialchars($data->registration_name); ?> is closed at the moment</h2>
	    	</div>

		<?php else : ?>


		<header id="main-header">

			<?php echo htmlspecialchars($data->registration_name);?>
			<?php
				/*
				echo '<span class="header-time">';
				if($data['registration_start_date'] != null) {
					echo '<div class="flag1"></div><span class="time" title="Registration starts">', htmlspecialchars($data['registration_start_date']), '</span>';
				}
				if($data['registration_end_date'] != null) {
					echo '<div class="flag2"></div><span class="time" title="Registration ends">', htmlspecialchars($data['registration_end_date']), '</span>';
				}
				echo '</span>';
				*/
			 ?>
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
				<div id="room-nav-btn">Change room</div>
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

			</div>

			<div id="legend-wrapper" class="border-box">
				
				<div id="legends"></div>

			</div>

			<div id="seat-cart" class="border-box no-select">

				<div class="seat-cart-left">
					<div class="cart-icon">
						<div class="cart-icon-box-1"></div>
						<div class="cart-icon-box-2"></div>
						<div class="cart-icon-box-3"></div>
					</div>
					<div id="cart-text"><span class="seats-in-cart">0</span> <?php //echo htmlspecialchars($data['spot_name']); ?> in selection

						<div class="max-seats">Max 

							<?php 

								if($data->seats_at_once > 1) {
									echo htmlspecialchars( $data->seats_at_once ),'<br>'; 
								}else {
									echo htmlspecialchars( $data->seats_at_once ),'<br>'; 
								}
							
							?>

						</div>

					</div>
				</div>

				<div class="seat-cart-right">
					<div id="cart-checkout-btn" class="border-box">Open</div>
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
				<h3>Registration info</h3>
				<?php

					if($data->info != null) {
						echo '<div>',htmlspecialchars($data->info),'</div><br>';
					}

					if($data->registration_start_timestamp != null) {
						echo '<div><div class="flag1"></div>Registration start: <span class="time">', htmlspecialchars($data->registration_start_timestamp), '</span></div>';
					}
					if($data->registration_end_timestamp != null) {
						echo '<div><div class="flag2"></div>Registration end: <span class="time">', htmlspecialchars($data->registration_end_timestamp), '</span></div>';
					}

				 ?>

					


				 <div>Total rooms: <span class="total-rooms"></span></div>
				 <div>Total open seats <?php //htmlspecialchars($data['spot_name']); ?>: <span class="total-open"></span></div>
				 <div>Total pending seats <?php //htmlspecialchars($data['spot_name']); ?>: <span class="total-bron"></span></div>
				 <div>Total confirmed seats <?php //htmlspecialchars($data['spot_name']); ?>: <span class="total-tak"></span></div>

			</div>

		</div>

		<div id="modal-bg"></div>


		<div id="legend-popup-dialog" class="dialog-box">
			
			<div id="legend-popup-dialog-inner" class="dialog-box-inner border-box">
				
				<div class="close-btn">
					<div class="close-btn-bg"></div>
					<i class="fa fa-times-circle"></i>
				</div>
				
				<h2>Legends</h2>
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
						<div id="confirm-dialog-mob-ok" class="seatreg-btn green-btn">Add to selection</div>
						<div id="confirm-dialog-mob-cancel" class="seatreg-btn red-btn">Close</div>
					</div>

				<?php endif; ?>

				<input type="hidden" id="selected-seat">
				<input type="hidden" id="selected-seat-room">
				<input type="hidden" id="selected-seat-nr">
			</div>
		</div>

		<div id="seat-cart-popup" class="dialog-box">	
			<div class="cart-popup-inner dialog-box-inner border-box">

				<div id="cart-popup-close" class="close-btn">
					<div class="close-btn-bg"></div>
					<i class="fa fa-times-circle"></i>
				</div>


				<div id="seat-cart-info"><?php //htmlspecialchars($data->spot_name); ?> Cart</div>
				<?php if($registrationTime == 'run') : ?>
					<div id="seat-cart-rows">
						<div class="row-nr">NR</div>
						<div class="row-room">Room</div>
					</div>
					
					<div id="seat-cart-items"></div>
					
					<div id="checkout" class="seatreg-btn green-btn">Checkout</div>
				<?php endif; ?>
			</div>
		</div>

		<div id="checkout-area" class="dialog-box">

			<form id="checkoput-area-inner" class="dialog-box-inner border-box">

				<div id="checkout-close" class="close-btn">
					<div class="close-btn-bg"></div>
					<i class="fa fa-times-circle"></i>
				</div>
				<div class="checkout-header">Checkout</div>
				<div id="checkout-input-area"></div>
				<div id="captchaWrap">
					
					<label for="captcha-val" style="vertical-align:middle">
						<span id="captcha-text">Enter code:</span>
					</label>
					<img src="php/image.php" id="captcha-img" alt="captcha image"/>
					<div id="captcha-ref" class="refresh1">
						<i class="fa fa-refresh"></i>
					</div><br>
					
					<input type="text" id="captcha-val" name="capv" />
				
				</div>

				<button type="submit" id="checkout-confirm-btn" class="seatreg-btn green-btn">OK</button>

				<img src="css/ajax_loader.gif" alt="Loading" class="ajax-load">
				<div id="request-error"></div>

			</form>

		</div>

		<input type="hidden" name="pw" id="sub-pwd" value="<?php if(!empty($_POST['reg_pwd'])) {echo $_POST['reg_pwd'];} ?>" />


		<div id="bottom-wrapper">
			<div class="mobile-cart">
				<div class="cart-icon">
					<div class="cart-icon-box-1"></div>
					<div class="cart-icon-box-2"></div>
					<div class="cart-icon-box-3"></div>
				</div>
				<div class="cart-icon-text">
					<span class="seats-in-cart">0</span> in selection 
					<span class="max-seats">Max 

							<?php 

								if($data->seats_at_once > 1) {
									echo htmlspecialchars($data->seats_at_once),'<br>'; 
								}else {
									echo htmlspecialchars($data->seats_at_once),'<br>'; 
								}
							
							?>

					</span>
				</div>
			</div>
			<div class="mobile-legend">Show legends</div>
		</div>

		<div id="email-conf" class="dialog-box">
			<div id="email-conf-inner" class="dialog-box-inner border-box animated zoomIn">
				<h2>Confirm email sent to <span id="email-send"></span></h2>
				<p>You need to confirm your <?php //htmlspecialchars($data->spot_name); ?> selection by following confirm email instructions. Make sure you check your junk folders.</p>
				<button class="refresh-btn">OK</button>
			</div>
		</div>

		<div id="error" class="dialog-box">
			<div id="error-inner" class="dialog-box-inner border-box animated zoomIn">
				<h2>Error</h2>
				<p id="error-text"></p>
				<button class="refresh-btn">OK</button>
			</div>
		</div>

		

		<?php if(!$isPremium) : ?>
		<!--
			<div class="view-footer">
				www.seatreg.com
			</div>
		-->
		<?php endif; ?>

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

									echo '<h3>Not open yet</h3>';
									echo '<h4>Registration starts: <span class="time">', htmlspecialchars($data->registration_start_timestamp), '</span></h4>';
								}else if($registrationTime == 'end') {
									echo '<h3>Closed</h3>';
									echo '<h4>Registration ended: <span class="time">',htmlspecialchars($data->registration_end_timestamp), '</span></h4>';
								}
							?>

						</div>
					</div>

			<?php endif; ?>



		
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>

		<script>

			var is_JSON = true;

			try {
				
				var seatLimit = <?php echo $data->seats_at_once;?>;
				var gmail = <?php echo $data->gmail_required;?>;
				var dataReg = $.parseJSON('<?php echo $data->registration_layout;?>');
				var roomsInfo = $.parseJSON('<?php echo $seatsInfo;?>');
				var custF = $.parseJSON('<?php  if($isPremium) {echo $data->custom_fields;} ?>')
				var regTime = <?php echo "'$registrationTime';";?>
				var registrations = $.parseJSON(<?php echo "'$registrations'";?>);
				

			} catch(err) {

				is_JSON = false;
			}

			console.log(registrations);

		</script>


		<script src="js/lang/view.lang.eng.js"></script>
		<script src="js/date.format.js"></script>
		<script src="js/iscroll-zoom-5-1-3.js"></script>
		<script src="js/jquery.powertip.js"></script>
		<script src="js/view.js"></script>

<!--
		<script src="js/view.all.min.js"></script>
-->		

		<?php endif; //end of is registration open ?>  


	<?php else : ?>

			

		<form method="post" id="pwd-form">
			<h2 >Password protected</h2>
			<label for="reg-pwd">Please enter password</label>
			<input type="password" name="reg_pwd" id="reg-pwd" /><br><br>
			<input type="submit" value="OK" />

		</form>
					



	<?php endif; ?>
	
</body>
</html>