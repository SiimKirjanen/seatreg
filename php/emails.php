<?php

function sendBookingNotificationEmail($registrationName, $bookedSeatsString, $emailAddress) {
    $message = "Hello <br>This is a notification email telling you that $registrationName has a new booking <br><br> $bookedSeatsString <br><br> You can disable booking notification in options if you don't want to receive them.";

    wp_mail($emailAddress, "$registrationName has a new booking", $message, array(
        "Content-type: text/html"
    ));
}