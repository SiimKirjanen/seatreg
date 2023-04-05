<div class="modal fade add-modal" id="add-booking-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
	  	<h4 class="modal-title"><?php esc_html_e('Add booking', 'seatreg'); ?></h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php esc_html_e('Close', 'seatreg'); ?></span></button>
      </div>
      <div class="modal-body">

		<?php if($calendarDate): ?>
			<div class="add-modal-calendar-date">
				<i class="fa fa-calendar" aria-hidden="true"></i>
				<?php echo sprintf(esc_html__('Selected date is %s', 'seatreg'), $calendarDate); ?>
			</div>
		<?php endif; ?>

		<form id="add-booking-modal-form">
			<div class="modal-body-items">
				<div class="modal-body-item">
					<div class="add-modal-input-wrap">
						<label>
							<h5>
								<?php $usingSeats ? esc_html_e('Seat ID', 'seatreg') : esc_html_e('Place ID', 'seatreg'); ?>
								<i class="fa fa-question-circle seatreg-ui-tooltip" aria-hidden="true" title="<?php $usingSeats ? esc_html_e('ID can be seen in map-editor when hovering seats', 'seatreg') : esc_html_e('ID can be seen in map-editor when hovering places', 'seatreg'); ?>"></i>
							</h5>
							<input type="text" name="seat-id[]" autocomplete="off"/>
							<div class="input-error"></div>
						</label>
					</div>
					<div class="add-modal-input-wrap">
						<label>
							<h5>
								<?php esc_html_e('Room', 'seatreg'); ?>
							</h5>
							<input type="text" name="room[]" autocomplete="off"/>
							<div class="input-error"></div>
						</label>
					</div>
					<div class="add-modal-input-wrap">
						<label>
							<h5>
								<?php esc_html_e('First name', 'seatreg'); ?>
							</h5>
							<input type="text" name="first-name[]" autocomplete="off"/>
							<div class="input-error"></div>
						</label>
					</div>
					<div class="add-modal-input-wrap">
						<label>
							<h5>
								<?php esc_html_e('Last name', 'seatreg'); ?>
							</h5>
							<input type="text" name="last-name[]" autocomplete="off"/>
							<div class="input-error"></div>
						</label>
					</div>
					<div class="add-modal-input-wrap">
						<label>
							<h5>
								<?php esc_html_e('Email', 'seatreg'); ?>
							</h5>
							<input type="text" name="email[]" autocomplete="off"/>
							<div class="input-error"></div>
						</label>
					</div>
					<div class="modal-body-custom"></div>
				</div>
			</div>
			<input type="hidden" name="custom-fields" />
			<div class="bottom-action">
				<div class="seat-operations">
					<div class="seat-operation" id="add-modal-add-seat">
						<?php $usingSeats ? esc_html_e('Add seat', 'seatreg') : esc_html_e('Add place', 'seatreg'); ?>
						<i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>
					</div>
					<div class="seat-operation" id="add-modal-remove-seat">
						<?php $usingSeats ? esc_html_e('Remove seat', 'seatreg') : esc_html_e('Remove place', 'seatreg'); ?>
						<i class="fa fa-minus-circle fa-lg" aria-hidden="true"></i>
					</div>
				</div>
				<div class="bottom-action-item">
					<div>
						<?php esc_html_e('Booking status', 'seatreg'); ?>
					</div>
					<label>
						<?php esc_html_e('Pending'); ?>
						<input type="radio" name="booking-status" value="1" checked>
					</label>
					<label>
						<?php esc_html_e('Approved'); ?>
						<input type="radio" name="booking-status" value="2">
					</label>
				</div>
			</div>
			<input type="hidden" name="calendar-date" value="<?php echo esc_html($calendarDate); ?>" />
			<input type="hidden" name="registration-code" id="add-booking-registration-id" />
			<input type="hidden" name="action" value="seatreg_add_booking_with_manager" />
	     </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'seatreg'); ?></button>
        <button type="button" class="btn btn-primary" id="add-booking-btn"><?php esc_html_e('Add booking', 'seatreg'); ?></button>
      </div>
    </div>
  </div>
</div>