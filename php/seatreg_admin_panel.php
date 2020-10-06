<?php

/*
	==========================
		SeatReg Admin Page
	==========================
*/

function addSeatregPluginMenu() {
	//Generate SeatReg Admin page
	add_menu_page(
		'SeatReg',  //header title
		'SeatReg',  //menu title
		'manage_options',  //capability,
		'seatreg-welcome',   //slug, 
		'seatreg_create_welcome',  //callback
		plugins_url('img/setting_icon.png', dirname(__FILE__) ),     //custom icon. 
		110  //position
	);

	//Generate SeatReg Admin Page Sub Pages
	add_submenu_page(
		'seatreg-welcome',
		sprintf(__('%s Home', 'seatreg'), 'SeatReg'),
		__('Home', 'seatreg'),
		'manage_options',
		'seatreg-welcome',
		'seatreg_create_welcome'
	);
	add_submenu_page(
		'seatreg-welcome',   //slug 
		sprintf(__('%s Overview', 'seatreg'), 'SeatReg'),  //page title
		__('Overview', 'seatreg'),  //menu title
		'manage_options',  //capability
		'seatreg-overview',   //slug
		'seatreg_create_overview'  //callback
	);
	add_submenu_page(
		'seatreg-welcome',   //slug 
		sprintf(__('%s Settings', 'seatreg'), 'SeatReg'),  //page title
		__('Settings', 'seatreg'),  //menu title
		'manage_options',  //capability
		'seatreg-options',   //slug
		'seatreg_create_options'
	);
	add_submenu_page(
		'seatreg-welcome',   //slug kuhu sisse submenu tuleb
		sprintf(__('%s Bookings', 'seatreg'), 'SeatReg'),  //page title
		__('Bookings', 'seatreg'),  //menu title
		'manage_options',  //capability
		'seatreg-management',   //slug
		'seatreg_create_management'
	);
}

function seatreg_create_welcome() {
	?>
	<div class="seatreg-wp-admin seatreg_page_seatreg-builder">
		<div class="jumbotron">
		  <h2 class="main-heading"><?php _e('Create and manage seat registrations', 'seatreg'); ?></h2>
		  <p class="jumbotron-text"><?php _e('Design your own seat map and manage seat bookings', 'seatreg'); ?></p>
	    </div>
	   <?php 
	   		echo "<div class='container-fluid'>";
				seatreg_create_registration_from(); 
				seatreg_generate_my_registrations_section();
			echo "</div>";   
	   	?>
	   <div class="seatreg-builder-popup">
			<i class="fa fa-times-circle builder-popup-close"></i>
			<div class="seatreg-builder-popup-content">
				<?php require( plugin_dir_path( __FILE__ ) . 'builder_content.php'  ); ?>
			</div>
		</div>
	 </div>
	<?php
}

function seatreg_create_options() {
	?>
	<div class="seatreg-wp-admin wrap">
		<h1><span class="glyphicon glyphicon-cog"></span> <?php _e('Welcome to settings page', 'seatreg'); ?>.</h1>
		<p><?php _e('Here you can change your registration settings', 'seatreg'); ?>.</p>
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
		<h1><?php _e('SeatReg overview'); ?></h1>
	<?php
		seatreg_generate_tabs('seatreg-overview');
	?>
	<div class="seatreg-tabs-content">
		<?php seatreg_generate_overview_section('overview'); ?> 
	</div>
	<?php
}

function seatreg_create_management() {
	?>
		<div class="seatreg-wp-admin wrap" id="seatreg-booking-manager">
			<h3><?php _e('Bookings'); ?></h3>
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