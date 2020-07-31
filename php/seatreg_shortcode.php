<?php

function seatreg_shortcode() {
	return 'from SeatReg registration shortcode';
}

function seatreg_register_shortcode() {
	add_shortcode( 'seatreg', 'seatreg_shortcode' );
}