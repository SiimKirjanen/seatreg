<div class="modal fade copy-registration-modal" tabindex="-1" role="dialog" aria-hidden="true" data-registration-id="<?php echo $registrationCode; ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php esc_html_e('Copy registration', 'seatreg'); ?></h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">
                    <label for="copy-registration-<?php echo $registrationCode; ?>">
                        <?php esc_html_e('Enter new registration name','seatreg'); ?>
                    </label>
                    <input type="text" name="new-registration-name" id="copy-registration-<?php echo $registrationCode; ?>" style="margin-left: 12px" maxlength="<?php echo SEATREG_REGISTRATION_NAME_MAX_LENGTH; ?>">
                    <input type='hidden' name='action' value='seatreg_copy_registration' />
                    <input type='hidden' name='registration_code' value='<?php echo $registrationCode; ?>' />
                    <?php echo seatrag_generate_nonce_field('seatreg-admin-nonce'); ?>
                    <br><br>
                    <p>
                        <?php esc_html_e('Only registration map and settings will be copied', 'seatreg'); ?>
                    </p>
                    <?php
                        submit_button(esc_html__('Copy registration', 'seatreg'), 'primary', 'submit', false);
                    ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
            </div>
        </div>
    </div>
</div>