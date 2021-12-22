<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post" id="create-registration-form">
    <h4 class="new-reg-title">
        <?php esc_html_e('Create new registration','seatreg'); ?>
    </h4>
    <label for="new-registration-name">
        <?php esc_html_e('Enter registration name','seatreg'); ?>
    </label>
    <input type="text" name="new-registration-name" id="new-registration-name" style="margin-left: 12px" maxlength="<?php echo SEATREG_REGISTRATION_NAME_MAX_LENGTH; ?>">
    <input type='hidden' name='action' value='seatreg_create_submit' />
    <?php echo seatrag_generate_nonce_field('seatreg-admin-nonce'); ?>
    <?php
        submit_button(esc_html__('Create new registration', 'seatreg'));
    ?>
</form>