<div class="modal fade shortcode-modal" tabindex="-1" role="dialog" aria-hidden="true" data-registration-id="<?php echo esc_attr($registrationCode); ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php esc_html_e('Shortcode', 'seatreg'); ?></h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
            </div>
            <div class="modal-body">
                <code class="shortcode-example">[seatreg code=<?php echo esc_html($registrationCode); ?> height=600]</code>
                <p class="shortcode-instructions"><?php esc_html_e("You can also set height for smaller screen sizes.", "seatreg"); ?></p>
                <code class="shortcode-example">[seatreg code=<?php echo esc_html($registrationCode); ?> height=600 mobile_height=500]</code>
                <p class="shortcode-instructions"><?php esc_html_e("You can customize the screen width below which the mobile height is applied (default: 720px).", "seatreg"); ?></p>
                <code class="shortcode-example">[seatreg code=<?php echo esc_html($registrationCode); ?> height=600 mobile_height=500 mobile_max_width=600]</code>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
            </div>
        </div>
    </div>
</div>