<?php if(!defined('ABSPATH')) exit; ?>

<?php
	$seatregCode = $seatregRegistration->registration_code;
	$seatregRegistrationLink = SeatregLinksService::getRegistrationURL() . '?seatreg=registration&c=' . esc_html($seatregCode) . '&page_id=' . SEATREG_PAGE_ID;
	$seatregBookingsLink = admin_url( 'admin.php?page=seatreg-management&tab=' . $seatregCode );
	$seatregRooms = SeatregLayoutService::getRoomDataFromLayout($seatregRegistration->registration_layout);
	$seatregStatus = SeatregRegistrationStatusService::getStatus($seatregRegistration);
	$seatregStartDate = $seatregRegistration->registration_start_timestamp ? SeatregTimeService::getDateStringFromUnix($seatregRegistration->registration_start_timestamp, 'M j Y') : null;
	$seatregEndDate = $seatregRegistration->registration_end_timestamp ? SeatregTimeService::getDateStringFromUnix($seatregRegistration->registration_end_timestamp, 'M j Y') : null;
	$seatregTimeInfo = SeatregTimeRepository::getTimeInfoForRegistrationView(
		$seatregRegistration->registration_start_time,
		$seatregRegistration->registration_end_time
	);
	//The booking manager names its tab panels after the registration, so the same name lands on the same tab
	$seatregManagerTab = sha1( str_replace(' ', '_', $seatregRegistration->registration_name) );
	$seatregUsingCalendar = $seatregRegistration->using_calendar === '1';
	$seatregOpenToday = SeatregCalendarService::isDateAvailable($seatregRegistration->calendar_dates, $seatregTodayDate);
?>

<div class="seatreg-registration-card" data-item="registration">
	<div class="seatreg-registration-card__head">
		<h5 class="seatreg-registration-card__title">
			<a class="registration-name-link" href="<?php echo esc_url($seatregRegistrationLink); ?>" target="_blank"><?php echo esc_html( wp_unslash($seatregRegistration->registration_name) ); ?></a>
		</h5>
		<div class="seatreg-registration-card__status">
			<?php if( $seatregUsingCalendar ) : ?>
				<i class="fa fa-calendar seatreg-registration-card__calendar-icon" title="<?php esc_attr_e('Calendar mode', 'seatreg'); ?>" aria-hidden="true"></i>
				<span class="screen-reader-text"><?php esc_html_e('Calendar mode', 'seatreg'); ?></span>
			<?php endif; ?>

			<span class="badge seatreg-registration-card__badge seatreg-registration-card__badge--<?php echo esc_attr($seatregStatus); ?>">
				<?php echo esc_html( SeatregRegistrationStatusService::getStatusLabel($seatregStatus) ); ?>
			</span>
		</div>
	</div>

	<div class="seatreg-registration-card__meta">
		<?php if( $seatregStartDate && $seatregEndDate ) : ?>
			<div class="seatreg-registration-card__dates">
				<?php
					/* translators: 1: registration start date, 2: registration end date */
					printf( esc_html__('%1$s - %2$s', 'seatreg'), esc_html($seatregStartDate), esc_html($seatregEndDate) );
				?>
			</div>
		<?php elseif( $seatregStartDate ) : ?>
			<div class="seatreg-registration-card__dates">
				<?php
					/* translators: %s is replaced with the registration start date */
					printf( esc_html__('From %s', 'seatreg'), esc_html($seatregStartDate) );
				?>
			</div>
		<?php elseif( $seatregEndDate ) : ?>
			<div class="seatreg-registration-card__dates">
				<?php
					/* translators: %s is replaced with the registration end date */
					printf( esc_html__('Until %s', 'seatreg'), esc_html($seatregEndDate) );
				?>
			</div>
		<?php endif; ?>

		<?php if( $seatregStatus === SeatregRegistrationStatusService::STATUS_CLOSED && $seatregRegistration->registration_close_reason ) : ?>
			<div class="seatreg-registration-card__reason">
				<?php echo esc_html($seatregRegistration->registration_close_reason); ?>
			</div>
		<?php elseif( $seatregStatus === SeatregRegistrationStatusService::STATUS_OPEN && $seatregTimeInfo->registrationOpenClosingText ) : ?>
			<div class="seatreg-registration-card__daily-window">
				<?php echo esc_html($seatregTimeInfo->registrationOpenClosingText); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="seatreg-registration-card__actions">
		<a href="<?php echo esc_url($seatregRegistrationLink); ?>" target="_blank"><?php esc_html_e('Registration', 'seatreg'); ?></a>

		<button type="button" class="btn btn-link seatreg-map-popup-btn" data-registration-name="<?php echo esc_attr($seatregRegistration->registration_name); ?>" data-map-code="<?php echo esc_attr($seatregCode); ?>"><?php esc_html_e('Layout', 'seatreg'); ?></button>

		<a href="<?php echo esc_url(admin_url( 'admin.php?page=seatreg-overview&tab=' . $seatregCode )); ?>"><?php esc_html_e('Overview', 'seatreg'); ?></a>

		<a href="<?php echo esc_url(admin_url( 'admin.php?page=seatreg-options&tab=' . $seatregCode )); ?>"><?php esc_html_e('Settings', 'seatreg'); ?></a>

		<a href="<?php echo esc_url($seatregBookingsLink); ?>"><?php esc_html_e('Bookings', 'seatreg'); ?></a>

		<a href="#" data-action="view-more-modal" data-registration-id="<?php echo esc_attr($seatregCode); ?>"><?php esc_html_e('More', 'seatreg'); ?></a>
	</div>

	<div class="seatreg-registration-card__footer">
		<?php if( $seatregUsingCalendar && !$seatregOpenToday ) : ?>
			<div class="seatreg-registration-card__footer-notice">
				<?php esc_html_e('Bookings are not taken today', 'seatreg'); ?>
			</div>
		<?php elseif( $seatregUsingCalendar ) : ?>
			<div class="seatreg-registration-card__footer-date">
				<i class="fa fa-calendar" aria-hidden="true"></i>
				<?php echo esc_html($seatregTodayLabel); ?>
			</div>
		<?php endif; ?>

		<div class="seatreg-registration-card__stats">
			<a class="seatreg-registration-card__stat seatreg-registration-card__stat--pending<?php echo $seatregBookingCounts['pending'] > 0 ? ' seatreg-registration-card__stat--highlight' : ''; ?>" href="<?php echo esc_url($seatregBookingsLink . '#' . $seatregManagerTab . 'bron'); ?>">
				<span class="seatreg-registration-card__stat-value"><?php echo esc_html($seatregBookingCounts['pending']); ?></span>
				<span class="seatreg-registration-card__stat-label"><?php esc_html_e('Pending', 'seatreg'); ?></span>
			</a>

			<a class="seatreg-registration-card__stat seatreg-registration-card__stat--approved" href="<?php echo esc_url($seatregBookingsLink . '#' . $seatregManagerTab . 'taken'); ?>">
				<span class="seatreg-registration-card__stat-value"><?php echo esc_html($seatregBookingCounts['approved']); ?></span>
				<span class="seatreg-registration-card__stat-label"><?php esc_html_e('Approved', 'seatreg'); ?></span>
			</a>

			<a class="seatreg-registration-card__stat seatreg-registration-card__stat--deleted" href="<?php echo esc_url($seatregBookingsLink . '#' . $seatregManagerTab . 'deleted'); ?>">
				<span class="seatreg-registration-card__stat-value"><?php echo esc_html($seatregBookingCounts['deleted']); ?></span>
				<span class="seatreg-registration-card__stat-label"><?php esc_html_e('Deleted', 'seatreg'); ?></span>
			</a>
		</div>
	</div>

	<?php
		seatreg_more_items_modal( $seatregCode );
		seatreg_copy_registration_modal( $seatregCode );
		seatreg_shortcode_modal( $seatregCode, $seatregRooms, SeatregTerminologyService::getRoomNouns($seatregRegistration) );
	?>
</div>
