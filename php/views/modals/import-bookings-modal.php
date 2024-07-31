<div class="modal fade" id="import-bookings-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
	  	<h4 class="modal-title"><?php esc_html_e('Import bookings', 'seatreg'); ?></h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
      </div>
      <div class="modal-body">
		    <form>
            <h6><?php esc_html_e('Upload bookings CSV file', 'seatreg'); ?></h6>
            <p><?php esc_html_e('Bookings CSV file generation can be found in the booking manager', 'seatreg'); ?><p>
            <div class="form-group">   
                <input type="file" class="form-control-file" name="csv-file" accept=".csv"/>
            </div>
            <input type="hidden" name="seatreg-code" value="<?php echo esc_attr($seatregCode); ?>"/>
        </form>
        <div class="import-booking-modal-loading" style="display:none">
          <img src="<?php echo SEATREG_PLUGIN_FOLDER_URL; ?>img/ajax_loader.gif" alt="Loading..."><?php esc_html_e('Loading...', 'seatreg'); ?>
        </div>
        <div class="import-booking-modal-error"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
      </div>
    </div>
  </div>
</div>