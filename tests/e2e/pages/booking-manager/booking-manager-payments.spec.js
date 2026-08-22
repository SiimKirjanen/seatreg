const { test, expect } = require('@playwright/test');
const { BookingManagerPage } = require('./booking-manager-page');
const { SettingsPage } = require('../settings/settings-page');
const { WP_ADMIN_USER } = require('../../utils/auth');
const { uniqueRegistrationName } = require('../../utils/registrations');

const SEAT_COUNT = 2;

const SEATS = [{ seat: 1, firstName: 'Zoe', lastName: 'Vaher', email: 'zoe.vaher@example.com' }];

const PAID_STATUS = { value: 'completed', text: 'Completed' };

const MANUAL_LOG = { type: 'error', message: 'Bank transfer never arrived' };

/* What a manager does about a payment the plugin did not take itself - a bank
   transfer, cash at the door - which is the whole of it on a registration paid for
   any way but PayPal or Stripe.

   The block only exists once the registration has a way to be paid, so every test
   here starts from a paid one. Deposit Payed is deliberately never picked: the
   status saves, but the screen has no wording for it and reads it back as None. */

test.describe('Booking manager payments', () => {
	let manager;
	let settings;
	let code;

	test.beforeEach(async ({ page }) => {
		manager = new BookingManagerPage(page);
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Booking manager payments'),
			SEAT_COUNT
		);

		await settings.allowPaidBookings();
	});

	test('changes a booking payment status and records who changed it', async () => {
		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({ seats: SEATS });

		await manager.openMoreInfo('pending', bookingId);

		await expect(manager.paymentStatus('pending', bookingId)).toHaveText('None');

		await manager.changePaymentStatus('pending', bookingId, PAID_STATUS.value);

		await expect(manager.paymentStatus('pending', bookingId)).toHaveText(PAID_STATUS.text);

		/* The screen writes the new status in from what was picked, so only the
		   reload says the server kept it. */
		await manager.reload();
		await manager.openMoreInfo('pending', bookingId);

		await expect(manager.paymentStatus('pending', bookingId)).toHaveText(PAID_STATUS.text);

		/* The change logs itself against the manager who made it, which is the
		   only record of a payment nobody was charged for. */
		await expect(manager.paymentLog('pending', bookingId)).toContainText(
			new RegExp(
				`Booking status changed to ${PAID_STATUS.value} by ${WP_ADMIN_USER.username} \\(id \\d+\\)`
			)
		);
	});

	test('adds a payment log line by hand', async () => {
		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({ seats: SEATS });

		await manager.openMoreInfo('pending', bookingId);
		await manager.addPaymentLog('pending', bookingId, MANUAL_LOG);

		/* Written into the log on the screen without waiting to hear whether it
		   was stored, so only the reload proves anything. */
		await manager.reload();
		await manager.openMoreInfo('pending', bookingId);

		await expect(manager.paymentLog('pending', bookingId)).toContainText(MANUAL_LOG.message);
		await expect(manager.paymentLog('pending', bookingId)).toContainText(MANUAL_LOG.type);
	});
});
