<?php

/*
	============================================
		SeatReg Admin Page enqueue functions
	============================================
*/

function seatreg_load_admin_scripts($hook) {
	$screen = get_current_screen();
	$allowedToLoadStylesAndScripts = array(
		'toplevel_page_seatreg-welcome',
		'seatreg_page_seatreg-overview',
		'seatreg_page_seatreg-builder',
		'seatreg_page_seatreg-options',
		'seatreg_page_seatreg-management'
	);

	if(in_array($screen->id, $allowedToLoadStylesAndScripts)) {
		wp_enqueue_style('jquery-ui-1.9.2-style', plugins_url('css/custom-theme/jquery-ui-1.9.2.custom.min.css', dirname(__FILE__) ), array(), '1.9.2', 'all');
		wp_enqueue_style('bootstrap-styles', plugins_url('css/bootstrap.min.css', dirname(__FILE__) ), array(), '3.1.1', 'all');
		wp_enqueue_style('tipsy_style', plugins_url('css/tipsy.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_style('alertify-core', plugins_url('css/alertify.core.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_style('alertify-default', plugins_url('css/alertify.default.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_style('colpick_style', plugins_url('css/colpick.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_style('seatreg_builder_style', plugins_url('css/seatreg_builder.css', dirname(__FILE__) ), array(), '1.0.1', 'all');
		wp_enqueue_style('seatreg_admin_styles', plugins_url('css/seatreg_admin.css', dirname(__FILE__) ), array(), '1.0.0', 'all');
		wp_enqueue_style('Google_open_sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,600', array(), '1.0.0', 'all');

		wp_enqueue_script('jquery-1.8.3', plugins_url('js/jquery-1.8.3.min.js', dirname(__FILE__) ), array(), '1.8.3', true);
		wp_enqueue_script('jquery-ui-1.9.2', plugins_url('js/jquery-ui-1.9.2.custom.min.js', dirname(__FILE__) ), array('jquery-1.8.3'), '1.9.2', true);
		wp_enqueue_script('selectable_scroll', plugins_url('js/selectableScroll.js', dirname(__FILE__) ), array('jquery-1.8.3','jquery-ui-1.9.2'), '1.0.0', true);
		wp_enqueue_script('bootstrap-3-1-1', plugins_url('js/bootstrap.min.js', dirname(__FILE__) ), array('jquery-1.8.3'), '3.1.1', true);
		wp_enqueue_script("jquery");
		wp_enqueue_script('alertify', plugins_url('js/alertify.js', dirname(__FILE__) ), array('jquery-1.8.3'), '1.0.0', true);
		wp_enqueue_script('easytabs', plugins_url('js/jquery.easytabs.js', dirname(__FILE__) ), array('jquery-1.8.3'), '1.0.0', true);
		wp_enqueue_script('colpick', plugins_url('js/colpick.js', dirname(__FILE__) ), array('jquery-1.8.3'), '1.0.0', true);
		wp_enqueue_script('tipsy', plugins_url('js/jquery.tipsy.js', dirname(__FILE__) ), array('jquery-1.8.3'), '1.0.0', true);
		wp_enqueue_script('date-format', plugins_url('js/date.format.js', dirname(__FILE__) ), array('jquery-1.8.3'), '1.0.0', true);
		wp_enqueue_script('seatreg_admin_chart', plugins_url('js/Chart.min.js', dirname(__FILE__) ), array('jquery-1.8.3'), '1.0.0', true);
		wp_enqueue_script('seatreg_admin', plugins_url('js/seatreg_admin.js', dirname(__FILE__) ), array('jquery-1.8.3','tipsy','seatreg_admin_chart'), '1.0.0', true);
		wp_enqueue_script('jstz', plugins_url('js/jstz-1.0.4.min.js', dirname(__FILE__) ), array(), '1.0.4', true);

		wp_enqueue_script('seatreg_builder_lang', plugins_url('js/lang/builder.lang.eng.js', dirname(__FILE__) ), array(), '1.0.0', true);
		wp_enqueue_script('seatreg_builder_script', plugins_url('js/build.js', dirname(__FILE__) ), array('seatreg_builder_lang','jquery-1.8.3','jquery-ui-1.9.2','selectable_scroll','alertify','colpick','tipsy'), '1.0.0', true);

		wp_localize_script('seatreg_admin', 'WP_Seatreg', array(
			'nonce' => wp_create_nonce('seatreg-admin-nonce'),
			'plugin_dir_url' => plugin_dir_url( dirname( __FILE__ ) ) 
		));
	}
}
add_action('admin_enqueue_scripts', 'seatreg_load_admin_scripts');