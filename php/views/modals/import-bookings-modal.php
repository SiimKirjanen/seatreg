<div class="modal fade" id="import-bookings-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
	  	<h4 class="modal-title"><?php esc_html_e('Import bookings', 'seatreg'); ?></h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
      </div>

      <div class="modal-body">

        <?php if($seatregData->using_calendar === '1') : ?>

          <div class="alert alert-warning" role="alert">
            <?php esc_html_e('Currently importing with calendar mode is not supported', 'seatreg'); ?>
          </div>

        <?php else : ?>

          <form>
            <h6><?php esc_html_e('Upload bookings CSV file', 'seatreg'); ?></h6>
            <p>
              <?php esc_html_e('You can generate bookings CSV file in the booking manager.', 'seatreg'); ?>
              <?php esc_html_e('Before importing make sure you also copied the correct registration schema.', 'seatreg'); ?>
            <p>
            <div class="form-group">   
                <input type="file" class="form-control-file" name="csv-file" accept=".csv"/>
            </div>
            <input type="hidden" name="seatreg-code" value="<?php echo esc_attr($seatregCode); ?>"/>
          </form>
          <div class="import-booking-modal-loading" style="display:none">
            <img src="<?php echo SEATREG_PLUGIN_FOLDER_URL; ?>img/ajax_loader.gif" alt="Loading..."><h6><?php esc_html_e('Uploading and analyzing CSV', 'seatreg'); ?></h6>
          </div>
          <div class="import-booking-modal-error"></div>

        <?php endif; ?>
		   
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
      </div>
    </div>

  </div>
</div>