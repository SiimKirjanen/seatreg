<?php
	//===========
	/* Page that generates and displays booking PDF */
	//===========

if ( ! defined( 'ABSPATH' ) ) {
	exit(); 
}

if( empty($_GET['id']) || empty($_GET['id']) ) {
	exit('Missing data'); 
}

echo 'hey';