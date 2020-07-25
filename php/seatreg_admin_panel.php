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
		'seatreg-welcome',   //slug,  admin.php?page=seatreg-overview
		'seatreg_create_welcome',  //callback
		plugins_url('img/setting_icon.png', dirname(__FILE__) ),     //custom icon. 
		110  //position
	);

	add_submenu_page(
		'seatreg-welcome',
		'SeatReg Settings',
		'Main',
		'manage_options',
		'seatreg-welcome',
		'seatreg_create_welcome'
	);

	//Generate SeatReg Admin Page Sub Pages
	add_submenu_page(
		'seatreg-welcome',   //slug kuhu sisse submenu tuleb
		'Seatreg Overview',  //page title
		'Overview',  //menu title
		'manage_options',  //capability
		'seatreg-overview',   //slug
		'seatreg_create_overview'  //callback
	);

	add_submenu_page(
		'seatreg-welcome',   //slug kuhu sisse submenu tuleb
		'Map Builder',  //page title
		'Map Builder',  //menu title
		'manage_options',  //capability
		'seatreg-builder',   //slug
		'seatreg_create_builder'
	);

	add_submenu_page(
		'seatreg-welcome',   //slug kuhu sisse submenu tuleb
		'SeatReg settings',  //page title
		'Settings',  //menu title
		'manage_options',  //capability
		'seatreg-options',   //slug
		'seatreg_create_options'
	);

	add_submenu_page(
		'seatreg-welcome',   //slug kuhu sisse submenu tuleb
		'Bookings',  //page title
		'Bookings',  //menu title
		'manage_options',  //capability
		'seatreg-management',   //slug
		'seatreg_create_management'
	);

	//Activate custom settings
	add_action('admin_init', 'seatreg_custom_settings');
}


function seatreg_custom_settings() {
	//sätete registreerimine WordPressi
	register_setting(
		'seatreg_settings_group',
		'info_text'
	);
	register_setting(
		'seatreg_settings_group',
		'seats_per_order'
	);
	register_setting(
		'seatreg_settings_group',
		'registration_open'
	);

	//add settings section for options page
	add_settings_section(
		'seatreg-options-section',   //String for use in the 'id' attribute of tags.
		'General options',    //Title of the section.
		'seatreg_options_section_callback',	//Function that fills the section with the desired content. The function should echo its output.
		'seatreg-options'		//The menu page on which to display this section.
	);
	add_settings_field(
		'info-text',   //The ID (or the name) of the field
		'Info text',   //The text used to label the field
		'seatreg_info_text_callback',  //The callback function used to render the field
		'seatreg-options',   //The section to which we're adding the setting
		'seatreg-options-section'   //section to add
	);
}

function seatreg_info_text_callback() {
	echo '<textarea name="info_text" rows="4" cols="50">'. esc_attr(get_option('info_text')) .'</textarea>';
	//echo '<input type="text" name="first_name" placeholder="First Name" value="'. esc_attr(get_option('first_name')) .'" />';
}

function seatreg_options_section_callback() {
	echo '';
}


function seatreg_create_welcome() {
	?>
	<div class="seatreg-wp-admin seatreg_page_seatreg-builder">
		<div class="jumbotron">
		  <h2 class="main-heading">Create and manage seat registrations</h2>
		  <p class="jumbotron-text">Design your own seat map and manage seat bookings</p>
	    </div>

	    <h2>Thank you for downloading SeatReg</h2>
	    <p>
	    	Lorem ipsum dolor sit amet, consectetur adipisicing elit. In aperiam ea quos impedit accusamus necessitatibus blanditiis dignissimos, mollitia ad vitae minima, dolorem facere nostrum architecto a, praesentium, sunt corporis tempore. Corrupti obcaecati veniam harum debitis assumenda nihil sit, excepturi vitae aliquam at illo. Dignissimos fugiat, consectetur. Sint rem laboriosam ullam molestiae voluptates modi eligendi deleniti debitis provident esse officia culpa ab maxime eum optio commodi ad, ipsum atque necessitatibus nihil aperiam! Debitis, enim ullam quas suscipit odio at incidunt assumenda consequatur repudiandae neque provident eum numquam voluptatum est cum consectetur deserunt libero nulla, repellendus, perferendis. Explicabo perferendis temporibus delectus id, odit dolore molestias architecto, numquam debitis consectetur porro distinctio, eveniet officiis dicta maiores dolores laborum quisquam deserunt nobis. Eum dolorem nihil temporibus, dicta harum voluptatem a nobis perspiciatis, ab voluptate reiciendis deserunt, officiis. Inventore ratione voluptate perferendis eius consequatur, mollitia labore. Autem tempora nam voluptates eveniet iusto laborum aliquid aperiam, quibusdam, voluptatem quasi blanditiis nihil? Minima cum consectetur, atque doloribus culpa reiciendis excepturi? Eius perspiciatis itaque tempore quaerat illum sapiente officiis id doloribus, officia vel ut assumenda earum laborum, fuga quam obcaecati. Nam dolores ipsa at tempore architecto natus, accusamus eligendi error velit, repudiandae nemo. Dignissimos, rem, error. Vero laboriosam qui, fugit pariatur ipsa cum incidunt delectus ea excepturi soluta dolores adipisci, beatae consequuntur reprehenderit odio provident illo rerum sunt voluptas. Voluptate et molestiae voluptatum, obcaecati assumenda deserunt hic architecto! Est dolorem cum explicabo in a. Magni, quaerat voluptates! Ullam repudiandae in doloremque pariatur tenetur dicta doloribus reprehenderit minus a quod error adipisci aperiam accusantium ratione, illo perferendis? Maxime consectetur libero ab iusto rem dolorem suscipit. Accusamus sequi facilis praesentium ducimus, asperiores reprehenderit nesciunt labore architecto hic consequatur alias aliquid adipisci repellendus voluptas, earum non rem placeat nulla id est repudiandae ex aliquam aperiam! Veritatis vel fugiat perspiciatis ipsum ut?
	    </p>
	   <?php 
	   		seatreg_create_registration_from(); 
	   		seatreg_generate_my_registrations_section();
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
		<h1><span class="glyphicon glyphicon-cog"></span> Welcome to settings page.</h1>
		<p>Here you can change your registration settings.</p>
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
		<h1>SeatReg overview</h1>
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
			<h3>Bookings</h3>
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

function seatreg_create_builder() {
	?>
		<div class="seatreg-wp-admin">
			<h2>Builder</h2>
				<?php $registrations = seatreg_get_registrations(); ?>
				<?php 
					if( count($registrations) == 0 ) {
						seatreg_no_registration_created_info();
					}
				?>
				<?php foreach( $registrations as $registration ): ?> 				  	
							<?php
								echo '<h3>', $registration->registration_name, '</h3>';
								echo '<button type="button" class="btn btn-primary seatreg-map-popup-btn" data-registration-name="'. $registration->registration_name .'"  data-map-code="'. $registration->registration_code .'">Edit map</button>';
							?>
				<?php endforeach; ?>
			<div class="seatreg-builder-popup">
				<i class="fa fa-times-circle builder-popup-close"></i>
				<div class="seatreg-builder-popup-content">
					<?php require( plugin_dir_path( __FILE__ ) . 'builder_content.php'  ); ?>
				</div>
			</div>
		</div>
	<?php
}