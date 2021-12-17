<div class="modal fade more-items-modal" tabindex="-1" role="dialog" aria-hidden="true" data-registration-id="<?php echo $registrationCode; ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php esc_html_e('More actions', 'seatreg'); ?></h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
            </div>
            <div class="modal-body">
                <div class="reg-more-items">
                    <div class="reg-more-items__item" data-action="view-registration-activity" data-registration-id="<?php echo $registrationCode; ?>">
                        <?php esc_html_e('Logs', 'seatreg'); ?>
                    </div>
                    <div class="reg-more-items__item" data-action="view-shortcode">
                        <?php esc_html_e('Shortcode', 'seatreg'); ?>
                    </div>
                    <div class="reg-more-items__item" data-action="open-copy-registration" data-registration-id="<?php echo $registrationCode; ?>">
                        <?php esc_html_e('Copy', 'seatreg'); ?>
                    </div>
                    <form action="<?php echo get_admin_url(); ?>admin-post.php" method="post" class="seatreg-delete-registration-form reg-more-items__item--delete-form" onsubmit="return confirm('Do you really want to delete?');">
                        <input type="hidden" name="registration-code" value="<?php echo esc_attr($registrationCode); ?>" />
                        <input type='hidden' name='action' value='seatreg_delete_registration' />
                        <?php echo seatrag_generate_nonce_field('seatreg-admin-nonce'); ?>
                        <div>
                            <?php
                                submit_button(esc_html__('Delete', 'seatreg'), 'delete-registration-btn', 'delete-registration', false, array( 'id' => "delete-$registrationCode" ));
                            ?>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
            </div>
        </div>
    </div>
</div>