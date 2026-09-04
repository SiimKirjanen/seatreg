<?php if(!defined('ABSPATH')) exit; ?>

<?php
	$seatregCode = $seatregRegistration->registration_code;
	$seatregName = wp_unslash($seatregRegistration->registration_name);
	$seatregUsingSeats = $seatregRegistration->using_seats === '1';
	$seatregUsingCalendar = $seatregRegistration->using_calendar === '1';
	$seatregStatus = SeatregRegistrationStatusService::getStatus($seatregRegistration);
	$seatregStartDate = $seatregRegistration->registration_start_timestamp
		? SeatregTimeService::getDateStringFromUnix($seatregRegistration->registration_start_timestamp, 'M j Y H:i')
		: null;
	$seatregEndDate = $seatregRegistration->registration_end_timestamp
		? SeatregTimeService::getDateStringFromUnix($seatregRegistration->registration_end_timestamp, 'M j Y H:i')
		: null;
	$seatregRegistrationLink = SeatregLinksService::getRegistrationURL() . '?seatreg=registration&c=' . $seatregCode . '&page_id=' . SEATREG_PAGE_ID;
	$seatregBookingsLink = admin_url( 'admin.php?page=seatreg-management&tab=' . $seatregCode );

	if( $seatregUsingCalendar && $seatregCalendarDate ) {
		$seatregBookingsLink = add_query_arg( 'calendar-date', $seatregCalendarDate, $seatregBookingsLink );
	}

	//The booking manager names its tab panels after the registration, so the same name lands on the same tab
	$seatregManagerTab = sha1( str_replace(' ', '_', $seatregRegistration->registration_name) );
	$seatregRooms = isset($seatregStats['roomsInfo']) ? $seatregStats['roomsInfo'] : array();
	$seatregSeatsLabel = $seatregUsingSeats ? __('Seats', 'seatreg') : __('Places', 'seatreg');
	$seatregChartTypes = array(
		'doughnut' => __('Doughnut chart', 'seatreg'),
		'pie' => __('Pie chart', 'seatreg'),
		'column' => __('Column chart', 'seatreg'),
		'bar' => __('Bar chart', 'seatreg')
	);
	$seatregScopes = array();

	if( $seatregRooms ) {
		$seatregScopes[] = array(
			'id' => 'overview',
			'label' => __('Overall', 'seatreg'),
			'heading' => __('All rooms', 'seatreg'),
			'total' => $seatregStats['seatsTotal'],
			'open' => $seatregStats['openSeats'],
			'confirmed' => $seatregStats['takenSeats'],
			'pending' => $seatregStats['bronSeats']
		);

		foreach($seatregRooms as $seatregRoom) {
			$seatregScopes[] = array(
				'id' => $seatregRoom['roomUuid'],
				'label' => $seatregRoom['roomName'],
				'heading' => $seatregRoom['roomName'],
				'total' => $seatregRoom['roomSeatsTotal'],
				'open' => $seatregRoom['roomOpenNoStatusSeats'],
				'confirmed' => $seatregRoom['roomTakenSeats'],
				'pending' => $seatregRoom['roomBronSeats']
			);
		}
	}
?>

<div class="seatreg-overview" id="seatreg-overview">
	<?php if( $seatregScopes ) : ?>
		<svg class="seatreg-overview__sprite" aria-hidden="true" focusable="false">
			<symbol id="seatreg-chart-icon-doughnut" viewBox="0 0 16 16">
				<circle cx="8" cy="8" r="5.5" fill="none" stroke="currentColor" stroke-width="4"/>
			</symbol>
			<symbol id="seatreg-chart-icon-pie" viewBox="0 0 16 16">
				<path d="M13.6 8.4A5.6 5.6 0 1 1 7.6 2.8v5.6z" fill="currentColor"/>
				<path d="M9.1 1.6a5.6 5.6 0 0 1 5.6 5.6H9.1z" fill="currentColor"/>
			</symbol>
			<symbol id="seatreg-chart-icon-column" viewBox="0 0 16 16">
				<path d="M2 7h3v7H2zM6.5 3h3v11h-3zM11 9h3v5h-3z" fill="currentColor"/>
			</symbol>
			<symbol id="seatreg-chart-icon-bar" viewBox="0 0 16 16">
				<path d="M2 2.5h7v3H2zM2 6.5h12v3H2zM2 10.5h5v3H2z" fill="currentColor"/>
			</symbol>
		</svg>
	<?php endif; ?>

	<div class="seatreg-overview__head">
		<div class="seatreg-overview__identity">
			<h2 class="seatreg-overview__title"><?php echo esc_html($seatregName); ?></h2>

			<?php if( $seatregUsingCalendar ) : ?>
				<i class="fa fa-calendar seatreg-overview__calendar-icon" title="<?php esc_attr_e('Calendar mode', 'seatreg'); ?>" aria-hidden="true"></i>
				<span class="screen-reader-text"><?php esc_html_e('Calendar mode', 'seatreg'); ?></span>
			<?php endif; ?>

			<span class="badge seatreg-registration-card__badge seatreg-registration-card__badge--<?php echo esc_attr($seatregStatus); ?>">
				<?php echo esc_html( SeatregRegistrationStatusService::getStatusLabel($seatregStatus) ); ?>
			</span>
		</div>

		<div class="seatreg-overview__dates">
			<?php if( $seatregStartDate && $seatregEndDate ) : ?>
				<?php
					/* translators: 1: registration start date, 2: registration end date */
					printf( esc_html__('%1$s - %2$s', 'seatreg'), esc_html($seatregStartDate), esc_html($seatregEndDate) );
				?>
			<?php elseif( $seatregStartDate ) : ?>
				<?php
					/* translators: %s is replaced with the registration start date */
					printf( esc_html__('From %s', 'seatreg'), esc_html($seatregStartDate) );
				?>
			<?php elseif( $seatregEndDate ) : ?>
				<?php
					/* translators: %s is replaced with the registration end date */
					printf( esc_html__('Until %s', 'seatreg'), esc_html($seatregEndDate) );
				?>
			<?php else : ?>
				<?php esc_html_e('No start or end date set', 'seatreg'); ?>
			<?php endif; ?>
		</div>

		<div class="seatreg-overview__links">
			<a href="<?php echo esc_url($seatregRegistrationLink); ?>" target="_blank"><?php esc_html_e('Registration', 'seatreg'); ?></a>
			<a href="<?php echo esc_url(admin_url( 'admin.php?page=seatreg-options&tab=' . $seatregCode )); ?>"><?php esc_html_e('Settings', 'seatreg'); ?></a>
			<a href="<?php echo esc_url($seatregBookingsLink); ?>"><?php esc_html_e('Bookings', 'seatreg'); ?></a>
		</div>
	</div>

	<?php if( $seatregUsingCalendar ) : ?>
		<div class="seatreg-overview__toolbar">
			<div class="seatreg-overview__toolbar-field">
				<label for="overview-calendar-date"><?php esc_html_e('Date', 'seatreg'); ?></label>
				<input type="text" id="overview-calendar-date" value="<?php echo esc_attr($seatregCalendarDate); ?>" autocomplete="off" />
				<input type="hidden" id="overview-calendar-date-value" value="<?php echo esc_attr($seatregCalendarDate); ?>" />
			</div>
		</div>
	<?php endif; ?>

	<?php if( !$seatregScopes ) : ?>
		<div class="seatreg-overview__empty">
			<?php esc_html_e('This registration has no layout yet, so there is nothing to count.', 'seatreg'); ?>
			<a href="<?php echo esc_url(admin_url('admin.php?page=seatreg-welcome')); ?>"><?php esc_html_e('Build one from the Home screen', 'seatreg'); ?></a>
		</div>
	<?php else : ?>
		<div class="seatreg-overview__body">
			<div class="seatreg-overview__rooms" role="tablist" aria-orientation="vertical" aria-label="<?php esc_attr_e('Rooms', 'seatreg'); ?>">
				<?php foreach($seatregScopes as $seatregIndex => $seatregScope) : ?>
					<?php
						$seatregIsActive = $seatregIndex === 0;
						$seatregTotal = $seatregScope['total'];
						$seatregBooked = $seatregScope['confirmed'] + $seatregScope['pending'];
					?>
					<button type="button"
						class="seatreg-overview__room"
						role="tab"
						id="seatreg-overview-tab-<?php echo esc_attr($seatregScope['id']); ?>"
						aria-controls="seatreg-overview-panel-<?php echo esc_attr($seatregScope['id']); ?>"
						aria-selected="<?php echo $seatregIsActive ? 'true' : 'false'; ?>"
						tabindex="<?php echo $seatregIsActive ? '0' : '-1'; ?>">
						<span class="seatreg-overview__room-head">
							<span class="seatreg-overview__room-name"><?php echo esc_html($seatregScope['label']); ?></span>
							<span class="seatreg-overview__room-count">
								<?php
									/* translators: 1: number of booked seats, 2: total number of seats */
									printf( esc_html__('%1$s / %2$s', 'seatreg'), esc_html($seatregBooked), esc_html($seatregTotal) );
								?>
							</span>
						</span>

						<span class="seatreg-overview__bar" aria-hidden="true">
							<?php foreach(array('confirmed', 'pending', 'open') as $seatregPartName) : ?>
								<span class="seatreg-overview__bar-segment seatreg-overview__bar-segment--<?php echo esc_attr($seatregPartName); ?>"
									style="width: <?php echo esc_attr( $seatregTotal > 0 ? round(($seatregScope[$seatregPartName] / $seatregTotal) * 100, 2) : 0 ); ?>%"></span>
							<?php endforeach; ?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="seatreg-overview__panels">
				<?php foreach($seatregScopes as $seatregIndex => $seatregScope) : ?>
					<?php
						$seatregTotal = $seatregScope['total'];
						$seatregParts = array(
							'confirmed' => array( 'label' => __('Confirmed', 'seatreg'), 'value' => $seatregScope['confirmed'] ),
							'pending' => array( 'label' => __('Pending', 'seatreg'), 'value' => $seatregScope['pending'] ),
							'open' => array( 'label' => __('Open', 'seatreg'), 'value' => $seatregScope['open'] )
						);
					?>
					<div class="seatreg-overview__panel"
						role="tabpanel"
						id="seatreg-overview-panel-<?php echo esc_attr($seatregScope['id']); ?>"
						aria-labelledby="seatreg-overview-tab-<?php echo esc_attr($seatregScope['id']); ?>"
						tabindex="0"
						<?php echo $seatregIndex === 0 ? '' : 'hidden'; ?>>

						<h3 class="seatreg-overview__panel-title"><?php echo esc_html($seatregScope['heading']); ?></h3>

						<div class="seatreg-overview__stats">
							<div class="seatreg-overview__stat" data-stat="seats">
								<span class="seatreg-overview__stat-value"><?php echo esc_html($seatregTotal); ?></span>
								<span class="seatreg-overview__stat-label"><?php echo esc_html($seatregSeatsLabel); ?></span>
							</div>

							<div class="seatreg-overview__stat" data-stat="open">
								<span class="seatreg-overview__stat-value"><?php echo esc_html($seatregScope['open']); ?></span>
								<span class="seatreg-overview__stat-label"><?php esc_html_e('Open', 'seatreg'); ?></span>
							</div>

							<a class="seatreg-overview__stat seatreg-overview__stat--link" data-stat="confirmed" href="<?php echo esc_url($seatregBookingsLink . '#' . $seatregManagerTab . 'taken'); ?>">
								<span class="seatreg-overview__stat-value"><?php echo esc_html($seatregScope['confirmed']); ?></span>
								<span class="seatreg-overview__stat-label"><?php esc_html_e('Confirmed', 'seatreg'); ?></span>
							</a>

							<a class="seatreg-overview__stat seatreg-overview__stat--link<?php echo $seatregScope['pending'] > 0 ? ' seatreg-overview__stat--highlight' : ''; ?>" data-stat="pending" href="<?php echo esc_url($seatregBookingsLink . '#' . $seatregManagerTab . 'bron'); ?>">
								<span class="seatreg-overview__stat-value"><?php echo esc_html($seatregScope['pending']); ?></span>
								<span class="seatreg-overview__stat-label"><?php esc_html_e('Pending', 'seatreg'); ?></span>
							</a>
						</div>

						<?php if( $seatregTotal > 0 ) : ?>
							<div class="seatreg-overview__chart">
								<div class="seatreg-overview__chart-types" role="group" aria-label="<?php esc_attr_e('Chart type', 'seatreg'); ?>">
									<?php foreach($seatregChartTypes as $seatregChartType => $seatregChartLabel) : ?>
										<button type="button"
											class="seatreg-overview__chart-type"
											data-chart-type="<?php echo esc_attr($seatregChartType); ?>"
											aria-pressed="<?php echo $seatregChartType === 'doughnut' ? 'true' : 'false'; ?>"
											title="<?php echo esc_attr($seatregChartLabel); ?>">
											<svg class="seatreg-overview__chart-icon" aria-hidden="true" focusable="false"><use href="#seatreg-chart-icon-<?php echo esc_attr($seatregChartType); ?>"></use></svg>
											<span class="screen-reader-text"><?php echo esc_html($seatregChartLabel); ?></span>
										</button>
									<?php endforeach; ?>
								</div>

								<div class="seatreg-overview__chart-body">
									<div class="seatreg-overview__canvas-wrap">
										<canvas class="seatreg-overview__canvas"
											data-confirmed="<?php echo esc_attr($seatregScope['confirmed']); ?>"
											data-pending="<?php echo esc_attr($seatregScope['pending']); ?>"
											data-open="<?php echo esc_attr($seatregScope['open']); ?>"
											role="img"
											aria-label="<?php echo esc_attr( sprintf(
												/* translators: 1: number of confirmed seats, 2: number of pending seats, 3: number of open seats */
												__('%1$s confirmed, %2$s pending, %3$s open', 'seatreg'),
												$seatregScope['confirmed'],
												$seatregScope['pending'],
												$seatregScope['open']
											) ); ?>"></canvas>
									</div>

									<div class="seatreg-overview__legend">
										<?php foreach($seatregParts as $seatregPartName => $seatregPart) : ?>
											<div class="seatreg-overview__legend-row seatreg-overview__legend-row--<?php echo esc_attr($seatregPartName); ?>">
												<span class="seatreg-overview__legend-swatch seatreg-overview__legend-swatch--<?php echo esc_attr($seatregPartName); ?>" aria-hidden="true"></span>
												<span class="seatreg-overview__legend-label"><?php echo esc_html($seatregPart['label']); ?></span>
												<span class="seatreg-overview__legend-percent"><?php echo esc_html( round(($seatregPart['value'] / $seatregTotal) * 100) ); ?>%</span>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						<?php else : ?>
							<div class="seatreg-overview__empty">
								<?php echo esc_html( $seatregUsingSeats
									? __('No seats in this room yet.', 'seatreg')
									: __('No places in this room yet.', 'seatreg') ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
