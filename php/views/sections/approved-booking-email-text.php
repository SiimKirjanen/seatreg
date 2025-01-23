<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit(); 
	}	
?>

<div class="approved-booking-email-text">
    <strong><?php esc_html_e('Custom text for approved email template', 'seatreg'); ?></strong>
    <p><?php esc_html_e('This can be used to add booking specific text to approved booking receipt email. Note that some settings can interfare with this feature as booking can be approved automatically before text can be added.', 'seatreg'); ?></p>
    <?php if($seatregData->use_pending === '0'): ?>
        <div class="approved-booking-email-text__warning"><?php esc_html_e('Note that pending bookings are turned off!', 'seatreg'); ?></div>
    <?php endif; ?>
    <?php if($seatregData->payment_completed_set_booking_confirmed === '1' || $seatregData->payment_completed_set_booking_confirmed_stripe === '1'): ?>
        <div class="approved-booking-email-text__warning"><?php esc_html_e('Note that one of the payment options is set up so that when payment is completed the booking is approved automatically!', 'seatreg'); ?></div>
    <?php endif; ?>     
    <div class="approved-booking-email-text__input-wrapper">
        <textarea rows="3" cols="40" data-taget="custom-text-approved-email"><?php echo sanitize_textarea_field($booking->custom_text_for_approved_email); ?></textarea>
        <button class="btn btn-outline-secondary btn-sm" data-action="save-approved-email-template-text"><?php esc_html_e('Save', 'seatreg'); ?></button>
    </div>
</div>