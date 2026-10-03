const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { uniqueBookerEmail } = require('../../utils/mail');
const {
	ageBooking,
	runPendingBookingExpiration,
	setCustomFieldTranslation,
} = require('../../utils/fixtures');

/* Two seats, because several of these settings only differ once a booking is
   for more than one. A registration takes one seat per booking until it is told
   otherwise, so the tests that book both say so first. */
const SEAT_COUNT = 2;

const INFO_TEXT = 'Doors open half an hour before the show.';
const FOOTER_TEXT = 'By booking you agree to the house rules.';

/* An extra question whose answer the registration is told to make public. */
const PUBLIC_FIELD = { label: 'Company', type: 'text' };

const BOOKER = {
	firstName: 'Riina',
	lastName: 'Tamm',
	email: 'riina.tamm@example.com',
	company: 'Kalev',
};

/* One of every kind the builder offers. The select's options are what it puts
   in front of a booker, so they are named rather than counted. */
const TEXT_FIELD = { label: 'Phone', type: 'text' };
const CHECKBOX_FIELD = { label: 'Newsletter', type: 'checkbox' };
const SELECT_FIELD = { label: 'Meal', type: 'select', options: ['Fish', 'Meat'] };
const EXTRA_OPTION = 'Vegetarian';

/* An answer two bookers can be made to give, to have the second one turned down. */
const TAKEN_PHONE = '5551234';

const PLEASE_ENTER_NAME = 'Please enter name';
const ILLEGAL_CHARACTERS = 'Illegal characters detected';
const NAME_ALREADY_USED = 'Name already used';
const PLEASE_ADD_AN_OPTION = 'Please add at least one option';
const AT_LEAST_ONE_OPTION = 'You must have at least one option.';

/* A name with characters the builder does not allow: it takes letters, digits,
   a plus and spaces, and nothing else. */
const ILLEGAL_LABEL = 'E-mail?';

/* What a translation plugin, standing behind the custom field filters, calls a
   label and a select option in another language. */
const TRANSLATED_LABEL = 'Telefon';
const TRANSLATED_OPTION = 'Kala';

/* The plugin names a custom field string after the text itself, not after the
   registration, so a label two workers share would be one string. Everything the
   translation test names is its own, spelled the way the builder allows. */
let textCounter = 0;

function uniqueCustomFieldText(prefix) {
	textCounter += 1;

	const run = Date.now().toString(36);
	const worker = process.env.TEST_WORKER_INDEX ?? '0';

	return `${prefix} ${run}${worker}${textCounter}`;
}

const PENDING_EXPIRATION_MINUTES = 30;

const EXPIRES_AFTER = `a pending booking is automatically removed after ${PENDING_EXPIRATION_MINUTES} minutes`;
const EXPIRES_WITH_PROCESSING =
	'Expired pending bookings are also removed even if they have one of these payment statuses: Processing';

/* Everything on this tab shapes the seat map, the cart or the booking form, so
   every test walks a visitor to the part its setting decides. The custom field
   builder is the exception: what it turns down or removes is checked on the
   builder itself.

   Left out: Booking PDF, the info button, the selection button's text and where
   the zoom controls sit. */

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

	test('shows the registration info text where a visitor can find it', async () => {
		await settings.set('infoText', INFO_TEXT);
		await settings.save();

		const registration = await settings.openRegistration(code);

		await expect(registration.registrationInfo).toHaveText(INFO_TEXT);

		await registration.openInfoDialog();

		await expect(registration.infoDialog).toContainText(INFO_TEXT);
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
		await settings.allowSeatsPerBooking(SEAT_COUNT);

		const perSeat = await settings.openRegistration(code);

		await perSeat.bookSeats(SEAT_COUNT);

		await expect(perSeat.checkoutItems).toHaveCount(SEAT_COUNT);
		await expect(perSeat.checkoutSyncSettings).toBeVisible();

		await perSeat.page.close();

		await settings.open(code);
		await settings.set('onePersonCheckout', true);
		await settings.save();

		const once = await settings.openRegistration(code);

		await once.bookSeats(SEAT_COUNT);

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

	test('builds custom fields that reach the booking form in the order they are listed', async () => {
		await settings.addCustomField(TEXT_FIELD);
		await settings.addCustomField(CHECKBOX_FIELD);
		await settings.addCustomField(SELECT_FIELD);

		await settings.customField(TEXT_FIELD.label).locator('.optional-input').check();

		await settings.moveCustomFieldDown(TEXT_FIELD.label);

		const listed = await settings.customFieldLabels();
		expect(listed).toEqual([CHECKBOX_FIELD.label, TEXT_FIELD.label, SELECT_FIELD.label]);

		await settings.save();

		const registration = await settings.openRegistration(code);

		await registration.bookSeats(1);

		await expect(registration.checkoutField(TEXT_FIELD.label)).toHaveAttribute(
			'data-optional',
			'true'
		);
		await expect(registration.checkoutField(CHECKBOX_FIELD.label)).toHaveAttribute(
			'type',
			'checkbox'
		);
		await expect(registration.checkoutField(SELECT_FIELD.label).locator('option')).toHaveText(
			SELECT_FIELD.options
		);

		expect(await registration.customFieldLabels()).toEqual(listed);
	});

	/* A label is what the booking is stored under and what every lookup matches on,
	   so a translation may only reach the booker's eyes. The booking going through
	   is what proves it did: the server turns down an answer to a field it cannot
	   find by the label it was given. */
	test('shows a booker the translated custom field while keeping the words it was given', async ({
		page,
	}) => {
		const textLabel = uniqueCustomFieldText('Phone');
		const selectLabel = uniqueCustomFieldText('Meal');
		const firstOption = uniqueCustomFieldText('Fish');
		const secondOption = uniqueCustomFieldText('Meat');

		await settings.addCustomField({ label: textLabel, type: 'text' });
		await settings.addCustomField({
			label: selectLabel,
			type: 'select',
			options: [firstOption, secondOption],
		});

		await settings.allowBookings();

		await setCustomFieldTranslation(page, { text: textLabel, translation: TRANSLATED_LABEL });
		await setCustomFieldTranslation(page, { text: firstOption, translation: TRANSLATED_OPTION });

		const registration = await settings.openRegistration(code);

		await registration.bookSeats(1);

		expect(await registration.customFieldLabels()).toEqual([TRANSLATED_LABEL, selectLabel]);
		await expect(registration.checkoutField(selectLabel).locator('option')).toHaveText([
			TRANSLATED_OPTION,
			secondOption,
		]);

		/* The field is still reached by the label the admin typed, and the option
		   still carries it as its value. */
		await expect(registration.customField(textLabel)).toBeVisible();
		await expect(registration.checkoutField(selectLabel).locator('option').first()).toHaveValue(
			firstOption
		);

		await registration.fillBooking({
			...BOOKER,
			email: uniqueBookerEmail(),
			customFields: { [textLabel]: TAKEN_PHONE },
		});
		await registration.submitBooking();

		await expect(registration.bookingConfirmed).toBeVisible();

		await settings.open(code);
		await settings.openSection('booking-flow');

		expect(await settings.customFieldLabels()).toEqual([textLabel, selectLabel]);
	});

	test('refuses a custom field it cannot use', async () => {
		await settings.addCustomFieldExpectingError({ label: '', type: 'text' }, PLEASE_ENTER_NAME);

		await settings.addCustomFieldExpectingError(
			{ label: ILLEGAL_LABEL, type: 'text' },
			ILLEGAL_CHARACTERS
		);

		await settings.addCustomField(TEXT_FIELD);

		await settings.addCustomFieldExpectingError(TEXT_FIELD, NAME_ALREADY_USED);

		await settings.addCustomFieldExpectingError(
			{ label: SELECT_FIELD.label, type: 'select' },
			PLEASE_ADD_AN_OPTION
		);
	});

	/* The unique flag is the one thing a custom field does that the builder cannot
	   show: it is only ever felt on the registration, by the second booker to give
	   an answer somebody else already gave. */
	test('turns away a booker whose unique field value is already taken', async () => {
		await settings.addCustomField(TEXT_FIELD);
		await settings.customField(TEXT_FIELD.label).locator('.unique-input').check();
		await settings.save();

		await settings.allowBookings();

		await settings.makeBooking(code, { customFields: { [TEXT_FIELD.label]: TAKEN_PHONE } });

		/* A seat of its own and an address of its own, so the only thing this
		   booking has in common with the first is the answer being claimed. */
		const registration = await settings.openRegistration(code);

		await registration.completeBooking({
			seats: [2],
			...BOOKER,
			email: uniqueBookerEmail(),
			customFields: { [TEXT_FIELD.label]: TAKEN_PHONE },
		});

		await expect(registration.bookingRefusal).toContainText(
			`${TEXT_FIELD.label} field value is already used`
		);
		await expect(registration.bookingConfirmed).toBeHidden();
	});

	test('removes a custom field after confirming and keeps it when cancelled', async () => {
		await settings.addCustomField(TEXT_FIELD);

		await settings.removeCustomField(TEXT_FIELD.label, { confirm: false });

		await expect(settings.customField(TEXT_FIELD.label)).toBeVisible();

		await settings.removeCustomField(TEXT_FIELD.label);

		await expect(settings.customField(TEXT_FIELD.label)).toHaveCount(0);
	});

	test('edits the options of a select field that was already saved', async () => {
		await settings.addCustomField(SELECT_FIELD);

		/* The dialog only comes with a saved field: the one the builder makes has
		   nothing to open it with. */
		await settings.save();
		await settings.openEditOptions(SELECT_FIELD.label);

		expect(await settings.editOptionValues()).toEqual(SELECT_FIELD.options);

		/* A select has to offer something, so the last option cannot be taken
		   away. */
		await settings.editOptionsDialog.locator('.remove-option').first().click();
		await settings.editOptionsDialog.locator('.remove-option').first().click();

		await expect(settings.editOptionsError).toHaveText(AT_LEAST_ONE_OPTION);
		await expect(settings.editOptionInputs).toHaveCount(1);

		await settings.editOptionsDialog.locator('#new-option').fill(EXTRA_OPTION);
		await settings.editOptionsDialog.locator('#add-option').click();
		await settings.editOptionsDialog.locator('#save-options').click();

		await expect(settings.editOptionsDialog).toHaveCount(0);

		await settings.save();

		const registration = await settings.openRegistration(code);

		await registration.bookSeats(1);

		await expect(registration.checkoutField(SELECT_FIELD.label).locator('option')).toHaveText([
			SELECT_FIELD.options[1],
			EXTRA_OPTION,
		]);
	});

	/* The one setting on this tab that needs a booking before it says anything.
	   What it makes public is not written onto the map but into the taken seat's
	   tooltip, one row per thing it was told to give away. */
	test('shows the booker details the settings make public on a taken seat', async () => {
		/* A field only gets a row in the Show booking data list once it has been
		   saved: the server draws that list from the fields the registration
		   already has, not from the ones the builder is holding. */
		await settings.addCustomField(PUBLIC_FIELD);
		await settings.save();

		await settings.showBookingData(['name', PUBLIC_FIELD.label]);
		await settings.allowBookings();

		const registration = await settings.openRegistration(code);

		await registration.completeBooking({
			...BOOKER,
			customFields: { [PUBLIC_FIELD.label]: BOOKER.company },
		});

		await expect(registration.bookingConfirmed).toBeVisible();

		/* Nothing repaints the map, so the seat only gives it away on the next
		   visit. */
		await registration.page.reload();

		const tooltip = await registration.seatTooltip(1);

		expect(tooltip).toContain(`${BOOKER.firstName} ${BOOKER.lastName}`);
		expect(tooltip).toContain(BOOKER.company);

		expect(tooltip).not.toContain(BOOKER.email);
	});

	/* The dialog naming the new booking is the whole of what a booker normally
	   gets; with this on they are taken to the booking instead and never see it. */
	test('sends the booker to their status page when told to', async () => {
		await settings.set('redirectToStatusPage', true);
		await settings.allowBookings();

		const registration = await settings.openRegistration(code);

		await registration.completeBooking({ ...BOOKER });

		await expect(registration.page).toHaveURL(/seatreg=booking-status/);

		/* The dialog is on the page either way, so it is its never being shown
		   that says the booker was sent on instead. */
		await expect(registration.bookingConfirmed).toBeHidden();

		await registration.page.close();
	});

	/* The screen writes out what the expiry will do, worked out from all three of
	   its controls. */
	test('says when a pending booking will be given up on', async () => {
		await settings.set('usePending', true);
		await settings.set('pendingExpiration', String(PENDING_EXPIRATION_MINUTES));

		await settings.openBookingFlowSummary();

		const afterSubmitting = settings.summaryGroup('After submitting');

		await expect(afterSubmitting).toContainText(EXPIRES_AFTER);
		await expect(afterSubmitting).not.toContainText(EXPIRES_WITH_PROCESSING);

		await settings.pendingExpirationProcessing.check();

		await expect(afterSubmitting).toContainText(EXPIRES_WITH_PROCESSING);

		await settings.save();

		await expect(settings.field('pendingExpiration')).toHaveValue(
			String(PENDING_EXPIRATION_MINUTES)
		);
		await expect(settings.pendingExpirationProcessing).toBeChecked();
	});

	/* WP-Cron only runs the expiry job when the site is visited, so one booking is
	   made to look old enough and the job is run the way the schedule would. */
	test('gives up on a pending booking once it has expired', async ({ page }) => {
		await settings.set('pendingExpiration', String(PENDING_EXPIRATION_MINUTES));
		await settings.allowBookings();

		const expired = await settings.makeBooking(code, { seats: [1] });
		await settings.makeBooking(code, { seats: [2] });

		await ageBooking(page, { bookingId: expired.id, minutes: PENDING_EXPIRATION_MINUTES + 1 });

		expect((await runPendingBookingExpiration(page)).scheduled).toBe(true);

		const registration = await settings.openRegistration(code);

		/* Left its age, the other booking says the job went by the expiry. */
		await expect(registration.seat(2)).toHaveAttribute('data-status', 'bron');

		await registration.openSeat(1);

		await expect(registration.addToBookingButton).toBeVisible();
	});
});
