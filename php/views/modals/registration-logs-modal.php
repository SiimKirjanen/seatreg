<div class="modal fade activity-modal" id="registration-activity-modal" tabindex="-1" role="dialog" aria-hidden="true" data-registration-id="">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Registration logs', 'seatreg'); ?></h4>
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
        </div>
        <div class="modal-body">
            <div class="activity-modal__loading"></div>
            <div class="activity-modal__logs"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
        </div>
        </div>
    </div>
</div>