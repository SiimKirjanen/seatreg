<div class="modal fade seat-id-modal" id="seat-id-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
	  	<h4 class="modal-title"><?php esc_html_e('Seat ID lookup', 'seatreg'); ?></h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
      </div>
      <div class="modal-body">
			<?php foreach($roomsData as $roomData): ?>
				<?php
					 $openSeatCounter = 0;
				?>
				<h5><?php echo esc_html($roomData->room->name); ?></h5>

				<div class="seat-id-grid">
					<div class="grid-title"><?php esc_html_e('No.', 'seatreg'); ?></div>
					<div class="grid-title"><?php esc_html_e('ID', 'seatreg'); ?></div>
					<div class="grid-title"><?php esc_html_e('Action', 'seatreg'); ?></div>

					<?php foreach($roomData->boxes as $box): ?>
						<?php 
							$seatNumber = $box->prefix . $box->seat;
						?>
						<?php if($box->canRegister === 'true' && !in_array($box->id, $bookingIds)): ?>
							<?php 
								$openSeatCounter++;
							?>
							<div>
								<?php echo esc_html($seatNumber); ?>
							</div>
							<div>
								<?php echo esc_html($box->id); ?>
							</div>

							<button class="btn btn-outline-secondary btn-sm" data-action="select-id" data-seat-id="<?php echo esc_attr($box->id); ?>">
								<?php echo esc_html('Select ID', 'seatreg'); ?>
							</button>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<?php if($openSeatCounter === 0): ?>
					<div class="alert alert-info"><?php echo sprintf(esc_html('No open seats in %s', 'seatreg'), esc_html($roomData->room->name)); ?></div>
				<?php endif; ?>
			<?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
      </div>
    </div>
  </div>
</div>