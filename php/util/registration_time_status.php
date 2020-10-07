<?php 

function seatreg_registration_time_status($startUnix, $endUnix) {
    $unix = round(microtime(true) * 1000);

    if($startUnix == null && $endUnix == null) {
        return 'run';
    }

    if($endUnix == null && $unix > $startUnix) {
        return 'run';
    }

    if($endUnix == null && $unix < $startUnix) {
        return 'wait';
    }

    if($startUnix == null && $unix < $endUnix) {
        return 'run';
    }

    if($startUnix == null && $unix > $endUnix) {
        return 'end';
    }

    if($startUnix < $unix && $endUnix > $unix) {
        return 'run';
    }

    if($unix > $endUnix) {
        return 'end';
    }

    if($startUnix > $unix) {
        return 'wait';
    }
}