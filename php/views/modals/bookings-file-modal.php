<div class="modal fade bookings-file-modal" id="bookings-file-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php esc_html_e('Filtering', 'seatreg'); ?></h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
            </div>
            <div class="modal-body">
                <form id="bookings-file-form">
                    <div>
                        <label>Name<input name="name"/></label>
                    </div>

                    <div>
                        <label>Email<input name="email" /></label>
                    </div>

                    <?php SeatregCustomFieldService::generateCustomFieldsMarkup($customFields); ?>

                    <div>
                        <label><?php esc_html_e('Show pending bookins', 'seatreg'); ?><input type='checkbox' name='show-pending' checked /></label>
                    </div>

                    <div>
                        <label><?php esc_html_e('Show approved bookings', 'seatreg'); ?><input type='checkbox' name='show-confirmed' checked /></label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
                <button type="button" class="btn btn-primary" id="generate-bookings-file" data-link=<?php echo get_site_url() . '?seatreg=pdf&code=' . esc_attr($registrationCode); ?>><?php esc_html_e('Generate file', 'seatreg'); ?></button>
            </div>
        </div>
    </div>
</div>