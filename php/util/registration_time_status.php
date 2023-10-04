<?php 

function seatreg_registration_time_status($startUnix, $endUnix) {
    $startDate = SeatregTimeService::getLocalDateTimeOutOfUnix($startUnix);
    $endDate = SeatregTimeService::getLocalDateTimeOutOfUnix($endUnix);
    $currentDateTime = SeatregTimeService::getCurrentDateTime();
    $currentDate = $currentDateTime->setTime(0, 0);

    if( $startDate === null && $endDate === null ) {
        return 'run';
    }

    if( $endDate === null && $currentDate > $startDate ) {
        return 'run';
    }

    if( $endDate === null && $currentDate < $startDate ) {
        return 'wait';
    }

    if( $startDate === null && $currentDate <= $endDate ) {
        return 'run';
    }

    if( $startDate === null && $currentDate > $endDate ) {
        return 'end';
    }
 
    if( $startDate <= $currentDate && $endDate >= $currentDate ) {
        return 'run';
    }

    if( $currentDate > $endDate ) {
        return 'end';
    }

    if( $startDate > $currentDate ) {
        return 'wait';
    } 
}