const { test, expect } = require('@playwright/test');
const { BookingManagerPage } = require('./booking-manager-page');
const { SettingsPage } = require('../settings/settings-page');
const { WP_ADMIN_USER } = require('../../utils/auth');
const { uniqueRegistrationName, bookingStatusUrlQuery } = require('../../utils/registrations');

const SEAT_COUNT = 4;

const SEATS = [
	{ seat: 1, firstName: 'Zoe', lastName: 'Vaher', email: 'zoe.vaher@example.com' },
	{ seat: 2, firstName: 'Anna', lastName: 'Kask', email: 'anna.kask@example.com' },
];

const BOOKER_EMAIL = 'booker@example.com';

const VISITOR = { firstName: 'Riina', lastName: 'Tamm', email: 'riina.tamm@example.com' };

const DELETION_REASON = 'Deletion reason';

const PERMANENT_DELETE_CONFIRM =
	'This will permanently delete the selected bookings. This cannot be undone. Are you sure?';

/* Everything that moves a booking between the three lists. Covered together
   because none of it can be seen from one list alone. The last test is the only
   one that walks a real visitor booking; the rest add theirs through the manager. */

test.describe('Booking manager bookings', () => {
	let manager;
	let settings;
	let code;

	test.beforeEach(async ({ page }) => {
		manager = new BookingManagerPage(page);
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Booking manager bookings'),
			SEAT_COUNT
		);
	});

	test('approves every seat of the booking that was selected', async () => {
		await settings.allowSeatsPerBooking(SEATS.length);

		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({
			seats: SEATS,
			bookerEmail: BOOKER_EMAIL,
		});

		await manager.selectBookingAction('pending', bookingId, 'approve');

		await expect(
			manager.bookingActionCheckbox('pending', bookingId, 'approve', { seat: 2 })
		).toBeChecked();

		/* The button is a div, switched on with a class rather than an
		   attribute, so this is the whole of it being live. */
		await expect(manager.bulkActionControl('pending')).not.toHaveClass(/is-disabled/);

		await manager.applyBulkAction('pending');

		await expect(manager.bookingRow('pending', bookingId)).toHaveCount(0);

		await manager.openStatusTab('approved');

		await expect(manager.bookingRow('approved', bookingId)).toHaveCount(SEATS.length);
		await expect(manager.statusTab('approved')).toContainText(`(${SEATS.length})`);
	});

	test('unapproves the booking that was selected', async () => {
		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({
			seats: [SEATS[0]],
			status: 'approved',
		});

		await manager.applyBookingAction('approved', bookingId, 'unapprove');

		await expect(manager.bookingRow('approved', bookingId)).toHaveCount(0);

		await manager.openStatusTab('pending');

		await expect(manager.bookingRow('pending', bookingId)).toBeVisible();
		await expect(manager.pendingNotice).toContainText('1');
	});

	/* The row's more-info says what a booking is now; this is the only place that
	   says how it got there, and the only reading of the log a booking keeps. */
	test('names what happened to the booking in its activity', async () => {
		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({ seats: [SEATS[0]] });

		await manager.applyBookingAction('pending', bookingId, 'approve');

		await manager.openStatusTab('approved');
		await manager.openMoreInfo('approved', bookingId);
		await manager.openBookingActivity('approved', bookingId);

		await expect(manager.activityLogs).toContainText(
			new RegExp(
				`Booking approved \\(Booking manager\\) by ${WP_ADMIN_USER.username} \\(id \\d+\\)`
			)
		);
	});

	test('deletes a booking and says who deleted it', async () => {
		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({ seats: [SEATS[0]] });

		await manager.applyBookingAction('pending', bookingId, 'delete');

		await manager.openStatusTab('deleted');
		await manager.openMoreInfo('deleted', bookingId);

		await expect(manager.moreInfoLine('deleted', bookingId, DELETION_REASON)).toContainText(
			new RegExp(
				`Deleted via booking manager by ${WP_ADMIN_USER.username} \\(WP user ID: \\d+\\)`
			)
		);

		await expect(manager.editButton('deleted', bookingId)).toHaveCount(0);
	});

	test('permanently deletes a deleted booking, or keeps it', async () => {
		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({ seats: [SEATS[0]] });

		await manager.applyBookingAction('pending', bookingId, 'delete');
		await manager.openStatusTab('deleted');

		const question = await manager.permanentlyDelete(bookingId, { confirm: false });

		expect(question).toBe(PERMANENT_DELETE_CONFIRM);
		await expect(manager.bookingRow('deleted', bookingId)).toBeVisible();

		await manager.permanentlyDelete(bookingId);

		await manager.openStatusTab('deleted');

		await expect(manager.bookingRow('deleted', bookingId)).toHaveCount(0);
	});

	test('shows a booking a visitor made', async () => {
		await settings.allowBookings();

		const registration = await settings.openRegistration(code);

		await registration.completeBooking(VISITOR);

		await expect(registration.bookingConfirmed).toBeVisible();

		/* The address of the status page is all the booker is told about the
		   booking, so it is also where its id has to be read from. */
		const statusUrl = await registration.bookingStatusUrl();
		const bookingId = await registration.bookingId();

		expect(statusUrl).toContain(bookingStatusUrlQuery(code, bookingId));

		await registration.page.close();

		await manager.openForRegistration(code);
		await manager.openMoreInfo('pending', bookingId);

		await expect(manager.bookingIdBox('pending', bookingId)).toHaveText(bookingId);
		await expect(manager.bookedName('pending', bookingId)).toHaveText(
			`${VISITOR.firstName} ${VISITOR.lastName}`
		);
		await expect(manager.statusPageLink('pending', bookingId)).toHaveAttribute(
			'href',
			statusUrl
		);
	});
});
