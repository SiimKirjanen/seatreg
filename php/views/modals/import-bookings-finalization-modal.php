<div class="modal fade import-bookings-finalization-modal" id="import-bookings-finalization-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
	  	<h4 class="modal-title"><?php esc_html_e('Finalize import', 'seatreg'); ?></h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
      </div>
      <div class="modal-body import-bookings-finalization-modal__body">
        <div class="import-bookings-finalization-modal__info" data-element="modal-info"></div>
		    <div class="import-bookings-finalization-modal__bookings" data-element="modal-bookings-wrap"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
        <button type="button" class="btn btn-primary" data-action="start-booking-import" data-code="<?php echo esc_attr($seatregCode); ?>"><?php esc_html_e('Start import', 'seatreg'); ?></button>
      </div>
    </div>
  </div>
</div>