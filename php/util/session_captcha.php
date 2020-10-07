<?php

function seatreg_change_captcha($length) {		
    $chars = "abcdefghijklmnprstuvwzyx23456789";
    $str = "";
    $i = 0;
    
    while($i < $length){
        $num = rand() % 33;
        $temp = substr($chars, $num, 1);
        $str = $str.$temp;
        $i++;
    }
    
    $_SESSION['captcha'] = $str;
}	