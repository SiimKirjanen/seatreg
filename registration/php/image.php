<?php
	require_once('../../php/util/session_captcha.php');
	session_start();
	$img = imagecreatetruecolor(60, 30);  //pildi suurus
	
	$white = imagecolorallocate($img, 255, 255, 255);
	$black = imagecolorallocate($img, 0, 0, 0);
	$gray = imagecolorallocate($img, 150, 150, 150);
	$red = imagecolorallocate($img, 255, 0, 0);
	$pink = imagecolorallocate($img, 200, 0, 150);

	for($i=0;$i<=rand(3,5);$i++){
		imageline($img, rand(5,70), rand(5,20), rand(5,120), rand(5,20)+5, $gray); //$img , us alustab x, kus algab y,
	}
	
	imagefill($img, 0, 0, $white);
	$string = seatreg_change_captcha(3);

	imagettftext($img, 20, 0, 10, 20, $black, __DIR__ . "/calibri.ttf", $string); //resource image, float size, float angle, int x, int y, 
	header("Content-type: image/png");
	imagepng($img);
	imagedestroy($img);
?>