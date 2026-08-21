const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { BookingStatusPage } = require('../booking-status/booking-status-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const SEAT_COUNT = 2;

const PAYMENT = { title: 'Bank transfer', description: 'Pay to the account on the invoice' };

const PAYMENT_INSTRUCTIONS = 'Payment is due a week before the event.';

const CUSTOM_PAYMENT_TITLE_MISSING = 'Please enter custom payment title';
const CUSTOM_PAYMENT_DESCRIPTION_MISSING = 'Please enter custom payment description';

const COUPON = { code: 'EARLYBIRD', discount: '10' };

const COUPON_CODE_MISSING = 'Please enter coupon code';
const COUPON_CODE_ILLEGAL = 'Illegal characters detected in coupon code';
const COUPON_DISCOUNT_MISSING = 'Please enter coupon discount';
const COUPON_DISCOUNT_ILLEGAL = 'Illegal characters detected in discount value';

const ILLEGAL_COUPON_CODE = 'EARLY BIRD!';
const OUT_OF_RANGE_DISCOUNT = '500';

/* The other half of the Payments tab: not the providers a registration is
   configured with, but what a booker is offered. A custom payment is enough to
   make the plugin count payments as turned on, which is what puts the coupon box
   in the cart - so none of this needs an account anywhere.

   The payment buttons themselves are only drawn for a booking that costs
   something, and are covered from booking-status.spec.js. The instruction text
   above them has no such condition, so that is what is followed to the booker
   here. */

test.describe('Settings payment options', () => {
	let settings;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings payment options'),
			SEAT_COUNT
		);
	});

	test('builds a custom payment and tells the booker how to pay', async ({ page }) => {
		const bookingStatus = new BookingStatusPage(page);

		await settings.addCustomPayment(PAYMENT);
		await settings.set('paymentInstructions', PAYMENT_INSTRUCTIONS);
		await settings.save();

		await expect(settings.customPayment(PAYMENT.title)).toBeVisible();

		/* The status page is the only place the payment settings show, and its
		   address is only ever given to the booker, so there has to be a booking
		   to have an id to look one up by. */
		await settings.open(code);
		await settings.allowBookings();

		const booking = await settings.makeBooking(code);

		await bookingStatus.goto(code, booking.id);

		await expect(bookingStatus.content).toContainText(PAYMENT_INSTRUCTIONS);

		/* Taken off the saved list, which is the only list it can be taken off:
		   the Remove button on a payment that has only just been added carries no
		   action for the plugin to match on. */
		await settings.open(code);
		await settings.removeCustomPayment(PAYMENT.title);
		await settings.save();

		await expect(settings.customPayment(PAYMENT.title)).toHaveCount(0);
	});

	test('refuses a custom payment it cannot use', async () => {
		await settings.addCustomPaymentExpectingError(
			{ title: '', description: PAYMENT.description },
			CUSTOM_PAYMENT_TITLE_MISSING
		);

		await settings.addCustomPaymentExpectingError(
			{ title: PAYMENT.title, description: '' },
			CUSTOM_PAYMENT_DESCRIPTION_MISSING
		);
	});

	test('applies a coupon to a booking', async () => {
		/* A coupon is only offered where there is something to pay, and a custom
		   payment is the cheapest way to make that true. */
		await settings.addCustomPayment(PAYMENT);
		await settings.set('enableCoupons', true);
		await settings.addCoupon(COUPON);
		await settings.save();

		await expect(settings.couponDiscount(COUPON.code)).toHaveText(COUPON.discount);

		const registration = await settings.openRegistration(code);

		await registration.addSeatToBooking(1);
		await registration.openCart();

		await expect(registration.couponBox).toBeVisible();

		await registration.applyCoupon(COUPON.code);

		/* The registration takes the code, and the box for entering one gives way
		   to the box that says one is on the booking.

		   Neither what that box says nor what the coupon takes off is asserted.
		   The message is built by replacing '%s' twice in a string that uses
		   '%1$s' and '%2$s' (registration.js:1223), so it reaches the booker with
		   its placeholders still in it and holds neither the code nor the amount;
		   and the discount itself is validated as a percentage by the builder but
		   written out as money here, so there is no settled answer to assert. */
		await expect(registration.couponApplied).toBeVisible();
		await expect(registration.couponBox).toBeHidden();
	});

	test('refuses a coupon it cannot use', async () => {
		await settings.set('enableCoupons', true);

		await settings.addCouponExpectingError(
			{ code: '', discount: COUPON.discount },
			COUPON_CODE_MISSING
		);

		/* The builder's own "code is too long" branch is left alone: the field
		   will not hold more than the twenty characters it allows, so nothing a
		   user can type reaches it. */
		await settings.addCouponExpectingError(
			{ code: ILLEGAL_COUPON_CODE, discount: COUPON.discount },
			COUPON_CODE_ILLEGAL
		);

		await settings.addCouponExpectingError({ code: COUPON.code, discount: '' }, COUPON_DISCOUNT_MISSING);

		await settings.addCouponExpectingError(
			{ code: COUPON.code, discount: OUT_OF_RANGE_DISCOUNT },
			COUPON_DISCOUNT_ILLEGAL
		);
	});
});
