<div class="modal fade seat-id-modal" id="seat-id-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
	  	<h4 class="modal-title"><?php esc_html_e('Seat ID lookup', 'seatreg'); ?></h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
      </div>
      <div class="modal-body">
			<?php foreach($roomsData as $roomData): ?>
				<h5><?php echo $roomData->room->name; ?></h5>

				<div class="seat-id-grid">
					<div class="grid-title"><?php esc_html_e('No.', 'seatreg'); ?></div>
					<div class="grid-title"><?php esc_html_e('ID', 'seatreg'); ?></div>
					<?php foreach($roomData->boxes as $box): ?>
						<?php if($box->canRegister === 'true'): ?>
							<div>
								<?php echo $box->prefix, $box->seat; ?>
							</div>
							<div>
								<?php echo $box->id; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
      </div>
    </div>
  </div>
</div>