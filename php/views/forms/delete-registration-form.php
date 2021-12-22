<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post" class="seatreg-delete-registration-form" onsubmit="return confirm('Do you really want to delete?');">
    <input type="hidden" name="registration-code" value="<?php echo esc_attr($registrationCode); ?>" />
    <input type='hidden' name='action' value='seatreg_delete_registration' />
    <?php echo seatrag_generate_nonce_field('seatreg-admin-nonce'); ?>
    <?php
        submit_button(esc_html__('Delete', 'seatreg'), 'delete-registration-btn', 'delete-registration', false, array( 'id' => "delete-$registrationCode" ));
    ?>
</form>