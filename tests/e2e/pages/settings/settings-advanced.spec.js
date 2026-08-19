const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { validateToken } = require('../../utils/public-api');

/* One of every kind the builder offers. The select's options are what it puts
   in front of a booker, so they are named rather than counted. */
const TEXT_FIELD = { label: 'Phone', type: 'text' };
const CHECKBOX_FIELD = { label: 'Newsletter', type: 'checkbox' };
const SELECT_FIELD = { label: 'Meal', type: 'select', options: ['Fish', 'Meat'] };
const EXTRA_OPTION = 'Vegetarian';

const PLEASE_ENTER_NAME = 'Please enter name';
const ILLEGAL_CHARACTERS = 'Illegal characters detected';
const NAME_ALREADY_USED = 'Name already used';
const PLEASE_ADD_AN_OPTION = 'Please add at least one option';
const AT_LEAST_ONE_OPTION = 'You must have at least one option.';

/* A name with characters the builder does not allow: it takes letters, digits,
   a plus and spaces, and nothing else. */
const ILLEGAL_LABEL = 'E-mail?';

/* A rule that can be measured off a seat once the registration draws one.
   Nothing here may use > or quotes: the plugin escapes the styles twice on the
   way out, so neither survives being typed in. */
const CUSTOM_STYLE = '.box[data-seat]{border-radius:50%}';
const STYLED_SEAT_RADIUS = '50%';

/* The advanced tab holds three unrelated things: a builder for the extra
   questions a booking asks, a stylesheet for the registration, and the public
   API. Each is checked where it ends up - the booking form, the seat map, and
   the API itself.

   The whole tab has no validation on the server beyond what the builder already
   turns down on the page, so there is no refused save to cover. */

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

		/* The text field is the only kind that can be made optional. */
		await settings.customField(TEXT_FIELD.label).locator('.optional-input').check();

		await settings.moveCustomFieldDown(TEXT_FIELD.label);

		const listed = await settings.customFieldLabels();
		expect(listed).toEqual([CHECKBOX_FIELD.label, TEXT_FIELD.label, SELECT_FIELD.label]);

		await settings.save();

		const registration = await settings.openRegistration(code);

		await registration.bookSeats(1);

		/* Each field arrives as the kind of control it was made as. */
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

		/* And in the order the list ended up in, which is what moving one did. */
		expect(await registration.customFieldLabels()).toEqual(listed);
	});

	test('refuses a custom field it cannot use', async () => {
		await settings.addCustomFieldExpectingError({ label: '', type: 'text' }, PLEASE_ENTER_NAME);

		await settings.addCustomFieldExpectingError(
			{ label: ILLEGAL_LABEL, type: 'text' },
			ILLEGAL_CHARACTERS
		);

		await settings.addCustomField(TEXT_FIELD);

		await settings.addCustomFieldExpectingError(TEXT_FIELD, NAME_ALREADY_USED);

		/* A select with nothing to select from. */
		await settings.addCustomFieldExpectingError(
			{ label: SELECT_FIELD.label, type: 'select' },
			PLEASE_ADD_AN_OPTION
		);
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

	test('hides an API token until it is asked for', async () => {
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
});
