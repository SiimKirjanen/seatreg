<div class="modal fade bookings-file-modal" id="bookings-file-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php esc_html_e('Filtering', 'seatreg'); ?></h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
            </div>
            <div class="modal-body">
                <form id="bookings-file-form">
                    <div class="form-fields">
                        <div class="mb-1">
                            <label><?php esc_html_e('Name', 'seatreg'); ?><input name="name"/></label>
                        </div>

                        <div class="mb-1">
                            <label><?php esc_html_e('Email', 'seatreg'); ?><input name="email" /></label>
                        </div>

                        <div class="mb-1">
                            <label><?php esc_html_e('Show pending bookins', 'seatreg'); ?><input type='checkbox' name='s1' checked></label>
                        </div>

                        <div class="mb-1">
                            <label><?php esc_html_e('Show approved bookings', 'seatreg'); ?><input type='checkbox' name='s2' checked></label>
                        </div>
                    </div>
                    <hr>
                    <div class="custom-filtering">
                        <span><?php esc_html_e('Add custom field filter', 'seatreg'); ?></span><i class="fa fa-plus-square-o" aria-hidden="true"></i>
                    </div>
                </form>
                <div class="custom-filtering-selection">
                    <?php count($customFields) > 0 ? SeatregCustomFieldService::generateCustomFieldsMarkup($customFields, true) : esc_html_e('No custom fields created!', 'seatreg'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
                <button type="button" class="btn btn-primary" id="generate-bookings-file" data-link=<?php echo get_site_url() . '?seatreg=pdf&code=' . esc_attr($registrationCode); ?>><?php esc_html_e('Generate file', 'seatreg'); ?></button>
            </div>
        </div>
    </div>
</div>