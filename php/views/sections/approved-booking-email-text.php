<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit(); 
	}	
?>

<div class="approved-booking-email-text">
    <strong><?php _e('Custom text for approved email template', 'seatreg'); ?></strong>
    <p><?php _e('This text can be used to add custom info to approved booking receipt email.', 'seatreg'); ?></p>
    <div class="approved-booking-email-text__input-wrapper">
        <textarea data-taget="custom-text-approved-email"><?php echo sanitize_textarea_field($booking->custom_text_for_approved_email); ?></textarea>
        <button class="btn btn-outline-secondary btn-sm" data-action="save-approved-email-template-text"><?php _e('Save', 'seatreg'); ?></button>
    </div>
</div>