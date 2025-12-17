<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/*
	==========================
		SeatReg Admin Page
	==========================
*/

function seatreg_add_plugin_menu() {
	//Generate SeatReg Admin page
	add_menu_page(
		'SeatReg',  //header title
		'SeatReg',  //menu title
		SEATREG_MANAGE_EVENTS_CAPABILITY,  //capability,
		'seatreg-welcome',   //slug, 
		'seatreg_create_welcome',  //callback
		plugins_url('img/setting_icon.png', dirname(__FILE__) ),     //custom icon. 
		110  //position
	);

	//Generate SeatReg Admin Page Sub Pages
	add_submenu_page(
		'seatreg-welcome',
		sprintf(esc_html__('%s Home', 'seatreg'), 'SeatReg'),
		esc_html__('Home', 'seatreg'),
		SEATREG_MANAGE_EVENTS_CAPABILITY,
		'seatreg-welcome',
		'seatreg_create_welcome'
	);
	add_submenu_page(
		'seatreg-welcome',   //slug 
		/* translators: %s: Name of the registration */
		sprintf(esc_html__('%s Overview', 'seatreg'), 'SeatReg'),  //page title
		esc_html__('Overview', 'seatreg'),  //menu title
		SEATREG_MANAGE_EVENTS_CAPABILITY,  //capability
		'seatreg-overview',   //slug
		'seatreg_create_overview'  //callback
	);
	add_submenu_page(
		'seatreg-welcome',   //slug 
		sprintf(esc_html__('%s Settings', 'seatreg'), 'SeatReg'),  //page title
		esc_html__('Settings', 'seatreg'),  //menu title
		SEATREG_MANAGE_EVENTS_CAPABILITY,  //capability
		'seatreg-options',   //slug
		'seatreg_create_options'
	);
	add_submenu_page(
		'seatreg-welcome',   //slug kuhu sisse submenu tuleb
		sprintf(esc_html__('%s Bookings', 'seatreg'), 'SeatReg'),  //page title
		esc_html__('Bookings', 'seatreg'),  //menu title
		SEATREG_MANAGE_BOOKINGS_CAPABILITY,  //capability
		'seatreg-management',   //slug
		'seatreg_create_management'
	);
	add_submenu_page(
		'seatreg-welcome',   //slug kuhu sisse submenu tuleb
		esc_html__('SeatReg tools', 'seatreg'),  //page title
		esc_html__('Tools', 'seatreg'),  //menu title
		SEATREG_MANAGE_EVENTS_CAPABILITY,  //capability
		'seatreg-tools',   //slug
		'seatreg_create_tools'
	);
	add_submenu_page(
		'seatreg-welcome',   //slug kuhu sisse submenu tuleb
		esc_html__('Companion', 'seatreg'),  //page title
		esc_html__('Companion', 'seatreg'),  //menu title
		SEATREG_MANAGE_EVENTS_CAPABILITY,  //capability
		'seatreg-companion-app',   //slug
		'seatreg_companion_app'
	);
}

function seatreg_create_welcome() {
	?>
	<div class="seatreg-wp-admin seatreg_page_seatreg-builder">
		<div class="jumbotron">
		  <h2 class="main-heading"><?php esc_html_e('Create and manage online registrations', 'seatreg'); ?></h2>
		  <p class="jumbotron-text"><?php esc_html_e('Design your own registration layout and manage bookings.', 'seatreg'); ?></p>
	    </div>

		<div class="home-content">
			<div class='container-fluid'>
				<?php 
					echo seatreg_create_registration_from(); 
					echo seatreg_generate_my_registrations_section();
				?>
			</div>
			<div class="donate-wrap">
				<img src="<?php echo esc_url(SEATREG_PLUGIN_FOLDER_URL . 'img/donate.svg'); ?>" alt="Donate a little" width="160" />
				<form action="https://www.paypal.com/donate" method="post" target="_blank">
					<input type="hidden" name="hosted_button_id" value="9QSGHYKHL6NMU" />
					<input type="image" class="donate-img" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
				</form>
				<p style="text-align:center">
					Don't forget to leave a 
					<a href="https://wordpress.org/support/plugin/seatreg/reviews/" target="_blank">
						review
					</a>
				</p>
				<p>
					Thank you if you have already donated and left a review. It means a lot, as it confirms that what I do makes a difference and motivates me to maintain and add new features.
				</p>
				<p style="text-align:center">
					Found a problem or have some ideas how to improve? Don't hesitate to write to
					<a href="https://wordpress.org/support/plugin/seatreg/" target="_blank">
						support forum
					</a>
				</p>
				<p>Source code is located at <a href="https://github.com/SiimKirjanen/seatreg" target="_blank">GitHub</a></p>
				<p style="text-align:center">
					I also created an Android companion application for this plugin.<br>
					<a href="https://play.google.com/store/apps/details?id=com.seatreg" target="_blank">
						<i class="fa fa-android" aria-hidden="true" style="color: #A4C639"></i>
						SeatReg Android application
					</a>
				</p>
				<p>
					Also take a look at my other plugin QuickTasker. It is a task management plugin. <br />
					<img src="<?php echo esc_url(SEATREG_PLUGIN_FOLDER_URL . "img/quicktasker-icon.png"); ?>" />
					<a href="https://wordpress.org/plugins/quicktasker/" target="_blank">QuickTasker</a>
				</p>
			</div>
		</div>

		<?php echo seatreg_registration_logs_modal();  ?>

	   <div class="seatreg-builder-popup">
			<i class="fa fa-times-circle builder-popup-close"></i>
			<div class="seatreg-builder-popup-content">
				<?php require( SEATREG_PLUGIN_FOLDER_DIR . 'php/views/sections/builder_content.php' ); ?>
			</div>
		</div>
	 </div>
	<?php
}

function seatreg_create_options() {
	?>
	<div class="seatreg-wp-admin wrap">
		<h1><i class="fa fa-cogs" aria-hidden="true"></i> <?php esc_html_e('Settings', 'seatreg'); ?></h1>
		<p><?php esc_html_e('Settings for your registrations', 'seatreg'); ?>.</p>
		<?php
			seatreg_generate_tabs('seatreg-options');
		?>
		<div class="seatreg-tabs-content">
			<?php
				seatreg_generate_settings_form();
			?>
		</div>
	</div>
	<?php
}

function seatreg_create_overview() {
	?>
		<div class="seatreg-wp-admin wrap">
			<h1><i class="fa fa-bar-chart" aria-hidden="true"></i> <?php esc_html_e('Overview'); ?></h1>
			<p><?php esc_html_e('Statistics for your registrations', 'seatreg'); ?>.</p>
			<?php
				seatreg_generate_tabs('seatreg-overview');
			?>
			<div class="seatreg-tabs-content">
				<div id="existing-regs-wrap">
					<?php seatreg_generate_overview_section('overview'); ?> 
				</div>
			</div>
		</div>
	<?php
}

function seatreg_create_management() {
	?>
		<div class="seatreg-wp-admin wrap" id="seatreg-booking-manager">
			<h1><i class="fa fa-book" aria-hidden="true"></i> <?php esc_html_e('Booking manager'); ?></h1>
			<p><?php esc_html_e('Manage bookings', 'seatreg'); ?>.</p>
			<?php
				seatreg_generate_tabs('seatreg-management');	
			?>
			<div class="seatreg-tabs-content">
				<?php
					seatreg_generate_booking_manager();
				?>
			</div>
		</div>
	<?php
}

function seatreg_create_tools() {
	require_once(SEATREG_PLUGIN_FOLDER_DIR . 'php/libs/phpqrcode/qrlib.php');
	?>
		<div class="seatreg-wp-admin wrap">
			<h1><i class="fa fa-wrench" aria-hidden="true"></i> <?php esc_html_e('Tools'); ?></h1>
			<p><?php esc_html_e('Useful tools', 'seatreg'); ?>.</p>

			<form id="email-tester-form">
				<h4>
					<?php esc_html_e('Email testing','seatreg'); ?>
				</h4>
				<p>
					<?php esc_html_e('Send a test email to verify that email sending works. If it doesn\'t then most likely your WordPress hosting is not configured to send out emails. In that case you can use some WordPress SMTP email plugin like WP Mail SMTP by WPForms','seatreg'); ?>.
				</p>
				<label for="test-email-address">
					<?php esc_html_e('Enter your email address','seatreg'); ?>
				</label>
				<input type="text" id="test-email-address" style="margin-left: 12px">
				<?php
					submit_button(esc_html__('Send test mail', 'seatreg'), 'primary', 'seatreg-send-test-email');
				?>
	    	</form>

			<div>
				<h4>
					<?php esc_html_e('QR Code testing','seatreg'); ?>
				</h4>
				<p>
					<?php esc_html_e('QR codes can be sent with booking receipt email. You should see test QR code below. If not then you should see error message that can help with debugging.'); ?>
				</p>

				<?php if( extension_loaded('gd') ) : ?>
					<?php 
						try {	
							if (!file_exists(SEATREG_TEMP_FOLDER_DIR)) {
								mkdir(SEATREG_TEMP_FOLDER_DIR, 0775, true);
							}
							QRcode::png('https://wordpress.org/plugins/seatreg/', SEATREG_TEMP_FOLDER_DIR.'/seatreg-qr-code-test.png', QR_ECLEVEL_L, 4); 
						} catch(Exception $err) {
							?>
								<div class="alert alert-primary" role="alert">
									<?php esc_html_e('Something went terribly wrong.', 'seatreg'); ?><br />
									<?php $err->getMessage(); ?>
								</div>
							<?php
						}
					?>
					<img src="<?php echo esc_url(SEATREG_TEMP_FOLDER_URL .'/seatreg-qr-code-test.png'); ?>" />
				<?php else : ?>
					<div class="alert alert-primary" role="alert">
						<?php esc_html_e('PHP gd extension is required to generate QR codes.', 'seatreg'); ?>	
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php
}

function seatreg_companion_app() {
	$companionAppUrl = get_site_url() . '/?seatreg=companion';
	$isCompanionEnabled = SeatregCompanionAppRepository::isCompanionAppEnabled();

	?>
		<div class="seatreg-wp-admin wrap">
			<h1><i class="fa fa-mobile" aria-hidden="true"></i> <?php esc_html_e('Companion app'); ?></h1>
			<p><?php esc_html_e('Web version of the mobile companion app.', 'seatreg'); ?></p>
			<p><?php esc_html_e("Alternative way to view bookings on mobile devices."); ?><p>
			<p>
				<?php esc_html_e("Source code is available on GitHub:", "seatreg"); ?>
				<a target="_blank" href="<?php echo esc_url('https://github.com/SiimKirjanen/seatreg-app'); ?>">
					<?php echo esc_html('https://github.com/SiimKirjanen/seatreg-app'); ?>
				</a>
			<p>

			<form action="<?php echo esc_url(get_admin_url() . 'admin-post.php'); ?>" method="post" class="companion-app-form">
				<label>
					<input
						type="checkbox"
						name="seatreg_companion_app_enabled"
						value="1"
						<?php checked( $isCompanionEnabled ); ?>
					/>
					<?php esc_html_e('Enable Companion App', 'seatreg'); ?>
				</label>
				
				<?php
					submit_button(esc_html__('Save', 'seatreg'));
				?>

				<?php echo seatrag_generate_nonce_field('seatreg-admin-nonce'); ?>
				<input type='hidden' name='action' value='seatreg_toggle_companion_app' />
			</form>
			
			<span><?php esc_html_e("The app is located at:", "seatreg"); ?></span>
			<a target="_blank" href="<?php echo esc_url($companionAppUrl); ?>">
				<?php echo esc_html($companionAppUrl); ?>
			</a>
		</div>
	<?php	
}