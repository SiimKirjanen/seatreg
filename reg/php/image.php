<?php
	session_start();
	$img = imagecreatetruecolor(60, 30);  //pildi suurus
	
	$white = imagecolorallocate($img, 255, 255, 255);
	$black = imagecolorallocate($img, 0, 0, 0);
	$gray = imagecolorallocate($img, 150, 150, 150);
	$red = imagecolorallocate($img, 255, 0, 0);
	$pink = imagecolorallocate($img, 200, 0, 150);


	
	function randomString($length){
		$chars = "abcdefghijklmnprstuvwzyx23456789";
		//srand((double)microtime()* 1000000);
		$str = "";
		$i = 0;
		
		while($i < $length){
			$num = rand() % 33;
			$temp = substr($chars, $num, 1);
			$str = $str.$temp;
			$i++;
		}
		return $str;
	}

	

	for($i=0;$i<=rand(3,5);$i++){
		imageline($img, rand(5,70), rand(5,20), rand(5,120), rand(5,20)+5, $gray); //$img , us alustab x, kus algab y,
	}
	
	imagefill($img, 0, 0, $white);
	
	//$string = randomString(rand(5,7));
	$string = randomString(3);

	$_SESSION['seatreg_captcha'] = $string;
	imagettftext($img, 20, 0, 10, 20, $black, "./CALIBRI.TTF", $string); //resource image, float size, float angle, int x, int y, 
	//imagettftext($img, 20, 0, 12, 22, $gray, "CALIBRI.TTF", $string);
	header("Content-type: image/png");
	imagepng($img);
	imagedestroy($img);
?>