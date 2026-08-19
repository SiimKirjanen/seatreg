const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

/* Two seats, because several of these settings only differ once a booking is
   for more than one. A registration takes one seat per booking until it is told
   otherwise, so the tests that book both say so first. */
const SEAT_COUNT = 2;

const DEFAULT_SELECTION_BUTTON = 'Open';
const SELECTION_BUTTON = 'Choose your seats';

const INFO_TEXT = 'Doors open half an hour before the show.';
const FOOTER_TEXT = 'By booking you agree to the house rules.';

/* Everything on this tab shapes the seat map, the cart or the booking form, so
   every test walks a visitor to the part of the registration its setting
   decides. Nothing here is validated, on the page or on the server, so there is
   no refusing to save to cover.

   The settings that only show on a booking that has been made - pending
   bookings and their expiry, the redirect to the status page, the booking data
   shown on taken seats, and everything under Booking PDF - are left out until
   the suite can make one. */

test.describe('Settings booking flow', () => {
	let settings;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings booking flow'),
			SEAT_COUNT
		);
	});

	test('dresses the seat map from the settings', async () => {
		const asBuilt = await settings.openRegistration(code);

		/* A registration nobody has touched: the info button is on, and the
		   button that opens the selection falls back to a caption of its own. */
		await expect(asBuilt.infoButton).toBeVisible();
		await expect(asBuilt.selectionButton).toHaveText(DEFAULT_SELECTION_BUTTON);

		await asBuilt.page.close();

		await settings.open(code);
		await settings.set('showInfoButton', false);
		await settings.set('seatSelectionBtnText', SELECTION_BUTTON);
		await settings.save();

		const registration = await settings.openRegistration(code);

		await expect(registration.infoButton).toHaveCount(0);
		await expect(registration.selectionButton).toHaveText(SELECTION_BUTTON);
	});

	test('shows the registration info text where a visitor can find it', async () => {
		await settings.set('infoText', INFO_TEXT);
		await settings.save();

		const registration = await settings.openRegistration(code);

		await expect(registration.registrationInfo).toHaveText(INFO_TEXT);

		/* The same text again in the dialog the info button opens, which is
		   where a visitor reads it once the map is in the way. */
		await registration.openInfoDialog();

		await expect(registration.infoDialog).toContainText(INFO_TEXT);
	});

	test('puts the zoom controls where the settings say', async () => {
		const below = await settings.openRegistration(code);

		/* The controller is rendered either above the map or below it with the
		   cart. Both are the same element, so which of the two arrangements was
		   drawn is the whole setting. */
		await expect(below.zoomController).toBeVisible();
		await expect(below.zoomControllerBelowMap).toHaveCount(1);

		await below.page.close();

		await settings.open(code);
		await settings.set('zoomOnTop', true);
		await settings.save();

		const above = await settings.openRegistration(code);

		await expect(above.zoomController).toBeVisible();
		await expect(above.zoomControllerBelowMap).toHaveCount(0);
	});

	test('opens the booking dialog as soon as a seat is chosen', async () => {
		const byHand = await settings.openRegistration(code);

		await byHand.addSeatToBooking(1);

		/* The seat did go in, so the cart staying shut is the setting's doing
		   and not a click that never landed. */
		await expect(byHand.seatsInCart).toHaveText('1');
		await expect(byHand.cartPopup).toBeHidden();

		await byHand.page.close();

		await settings.open(code);
		await settings.set('automaticBookingConfirmDialog', true);
		await settings.save();

		const automatic = await settings.openRegistration(code);

		await automatic.addSeatToBooking(1);

		await expect(automatic.cartPopup).toBeVisible();
	});

	test('enters the booking details once for every seat', async () => {
		await settings.set('maxSeats', String(SEAT_COUNT));
		await settings.save();

		const perSeat = await settings.openRegistration(code);

		await perSeat.bookSeats(SEAT_COUNT);

		/* Asked once per seat, with the offer to copy the first entry to the
		   rest. */
		await expect(perSeat.checkoutItems).toHaveCount(SEAT_COUNT);
		await expect(perSeat.checkoutSyncSettings).toBeVisible();

		await perSeat.page.close();

		await settings.open(code);
		await settings.set('onePersonCheckout', true);
		await settings.save();

		const once = await settings.openRegistration(code);

		await once.bookSeats(SEAT_COUNT);

		/* The other seat's entry is still posted, it is just not asked for. */
		await expect(once.checkoutItems).toHaveCount(SEAT_COUNT);
		await expect(once.checkoutItems.nth(1)).toBeHidden();
		await expect(once.checkoutSyncSettings).toBeHidden();
	});

	test('shapes the booking form from the settings', async () => {
		await settings.set('maxSeats', String(SEAT_COUNT));
		await settings.set('requireName', false);
		await settings.set('gmailRequired', true);
		await settings.set('customFooterText', FOOTER_TEXT);
		await settings.save();

		const registration = await settings.openRegistration(code);

		await registration.bookSeats(SEAT_COUNT);

		/* The name is still posted, so it is hidden rather than dropped. */
		await expect(registration.checkoutField('FirstName').first()).toHaveAttribute(
			'type',
			'hidden'
		);
		await expect(registration.checkoutField('LastName').first()).toHaveAttribute(
			'type',
			'hidden'
		);
		await expect(registration.checkoutField('Email').first()).toBeVisible();

		/* One address for a booking of several seats, and the label says which
		   kind of address it has to be. */
		await expect(registration.primaryEmailLabel).toContainText('Gmail');

		await expect(registration.customFooterText).toHaveText(FOOTER_TEXT);
	});
});
