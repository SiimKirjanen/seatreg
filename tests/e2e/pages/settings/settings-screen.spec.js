const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const MAX_SEATS = '4';
const INFO_TEXT = 'Doors open at 18:00.';
const PDF_LOGO_POSITION = 'bottom-right';

/* Sentences the summary writes for a setting and for its opposite. A fresh
   registration counts in seats rather than places, which is the noun they are
   filled in with. */
const MANUAL_DIALOG = 'After choosing seats, visitors open the selection menu to complete';
const AUTOMATIC_DIALOG = 'The booking form opens automatically as soon as a seat is selected.';
const PER_SEAT_CHECKOUT = 'Booking details are entered for each seat.';
const ONE_PERSON_CHECKOUT = 'Booking details are entered once and applied to every seat.';
const CLOSED = 'Your registration is currently closed, so visitors cannot make a booking.';

/* The screen itself: which registration it is editing, its section tabs, and the
   save that carries all of them. The settings live in the spec of the section
   tab they belong to.

   The whole form is one post that writes every setting at once, so one setting
   of each kind is enough to prove saving works. What each of those settings then
   does is not this file's business. */

test.describe('SeatReg Settings screen', () => {
	let settings;
	let name;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		name = uniqueRegistrationName('Settings');
		code = await settings.openForNewRegistration(name);
	});

	test('shows the settings of the registration picked in the registration tabs', async () => {
		await settings.open(code);

		await expect(settings.registrationTab(code)).toHaveClass(/nav-tab-active/);
		await expect(settings.heading).toContainText(name);
	});

	test('saves a change in every kind of field and keeps it after the reload', async () => {
		const newName = uniqueRegistrationName('Settings renamed');

		await settings.set('registrationName', newName);
		await settings.set('maxSeats', MAX_SEATS);
		await settings.set('infoText', INFO_TEXT);
		await settings.set('showInfoButton', false);
		await settings.set('pdfLogoPosition', PDF_LOGO_POSITION);

		await settings.save();

		await expect(settings.field('registrationName')).toHaveValue(newName);
		await expect(settings.field('maxSeats')).toHaveValue(MAX_SEATS);
		await expect(settings.field('infoText')).toHaveValue(INFO_TEXT);
		await expect(settings.field('showInfoButton')).not.toBeChecked();
		await expect(settings.field('pdfLogoPosition')).toHaveValue(PDF_LOGO_POSITION);

		/* The name is the one setting that also renames the registration
		   everywhere else in the admin. */
		await expect(settings.heading).toContainText(newName);

		await settings.homePage.goto();
		await expect(settings.homePage.registrationNameLink(newName)).toHaveText(newName);
	});

	test('returns to the section that was open when the settings were saved', async () => {
		await settings.openSection('payments');

		await settings.save();

		await expect(settings.activeSectionPanel).toHaveAttribute('data-tab-panel', 'payments');
	});

	/* The summary is the plugin reading its own settings back as sentences, and
	   it is rewritten as they are changed rather than when they are saved, so
	   nothing here is posted. */

	test('writes the booking flow out of the settings it is given', async () => {
		await settings.openBookingFlowSummary();

		const makingABooking = settings.summaryGroup('Making a booking');

		await expect(makingABooking).toContainText(MANUAL_DIALOG);
		await expect(makingABooking).toContainText(PER_SEAT_CHECKOUT);

		await settings.set('automaticBookingConfirmDialog', true);
		await settings.set('onePersonCheckout', true);

		await expect(makingABooking).toContainText(AUTOMATIC_DIALOG);
		await expect(makingABooking).toContainText(ONE_PERSON_CHECKOUT);

		/* With nobody able to book, there is no flow left to describe and the
		   groups give way to the one sentence that says so. */
		await settings.set('registrationStatus', false);

		await expect(settings.bookingFlowSummary).toHaveText(CLOSED);
		await expect(makingABooking).toHaveCount(0);
	});

	test('jumps to the setting a summary line is about', async () => {
		await settings.openBookingFlowSummary();

		/* The screen opens on the first section, and this setting is on another
		   one, so following the line has to change section to get there. */
		await settings.summaryJumpLink('#one-person-checkout').click();

		await expect(settings.activeSectionPanel).toHaveAttribute('data-tab-panel', 'booking-flow');
		await expect(settings.field('onePersonCheckout')).toBeFocused();
	});
});
