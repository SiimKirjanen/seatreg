const { test, expect } = require('@playwright/test');
const { SettingsPage, BOOKER } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { uniqueBookerEmail } = require('../../utils/mail');
const { validateToken, bookings } = require('../../utils/public-api');
const { setCustomFieldTranslation } = require('../../utils/fixtures');
const { isoDate } = require('../../utils/dates');

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

/* A rule that can be measured off a seat once the registration draws one.
   Nothing here may use > or quotes: the plugin escapes the styles twice on the
   way out, so neither survives being typed in. */
const CUSTOM_STYLE = '.box[data-seat]{border-radius:50%}';
const STYLED_SEAT_RADIUS = '50%';

/* Three unrelated things - the custom field builder, a stylesheet for the
   registration, and the public API - each checked where it ends up. Nothing here
   is validated on the server beyond what the builder turns down on the page. */

test.describe('Settings advanced', () => {
	let settings;
	let name;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		name = uniqueRegistrationName('Settings advanced');
		code = await settings.openForNewRegistrationWithSeats(name, 1);
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
		await settings.openSection('advanced');

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
		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings advanced unique'),
			2
		);

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

	test('styles the registration with the css it was given', async () => {
		await settings.set('customStyles', CUSTOM_STYLE);
		await settings.save();

		const registration = await settings.openRegistration(code);

		await expect(registration.seat(1)).toHaveCSS('border-radius', STYLED_SEAT_RADIUS);
	});

	test('hides an API token until it is asked for, and removes it once confirmed', async () => {
		const token = await settings.createApiToken();

		const secret = await token.getAttribute('data-token');
		const masked = await token.getAttribute('data-token-hidden');

		/* The mask is the plugin's own doing: the token's length, with all but
		   the first few characters covered over. */
		expect(masked).toHaveLength(secret.length);
		expect(masked).toMatch(/^.{3}●+$/);
		await expect(token.locator('.token')).toHaveText(masked);

		await token.locator('.toggle-token').click();

		await expect(token.locator('.token')).toHaveText(secret);

		await token.locator('.toggle-token').click();

		await expect(token.locator('.token')).toHaveText(masked);

		await settings.removeApiToken(token, { confirm: false });

		await expect(settings.apiTokens).toHaveCount(1);

		await settings.removeApiToken(token);

		await expect(settings.apiTokens).toHaveCount(0);
	});

	test('lets a token read the registration only while the API is on', async ({ request }) => {
		const token = await settings.createApiToken();
		const secret = await token.getAttribute('data-token');

		await settings.set('publicApi', true);
		await settings.save();

		const answered = await validateToken(request, secret);

		expect(answered.ok()).toBe(true);
		expect((await answered.json()).registrationName).toBe(name);

		await settings.set('publicApi', false);
		await settings.save();

		const refused = await validateToken(request, secret);

		expect(refused.status()).toBe(403);
	});

	/* What the API is for: validate-token only says the token is good, this is the
	   one endpoint that hands the bookings over, and it is what both the companion
	   app and the Android app are built on. */
	test('reads the registration bookings with a token', async ({ request }) => {
		const token = await settings.createApiToken();
		const secret = await token.getAttribute('data-token');

		await settings.set('publicApi', true);
		await settings.allowBookings();

		const booking = await settings.makeBooking(code);

		const answered = await bookings(request, secret, isoDate(new Date()));

		expect(answered.ok()).toBe(true);

		const seated = (await answered.json()).bookings.find(
			(row) => row.booking_id === booking.id
		);

		expect(seated).toBeTruthy();
		expect(seated.first_name).toBe(BOOKER.firstName);
		expect(seated.booker_email).toBe(booking.email);

		/* The room the seat was drawn in is put on the row by the endpoint - it is
		   in the layout rather than on the booking. */
		expect(seated.room_name).toBeTruthy();
	});
});
