const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { SEATREG_PAGES, openSeatRegScreen, expectOnSeatRegPage } = require('../../utils/navigation');
const { clickUntil } = require('../../utils/interactions');
const { visitorContext } = require('../../utils/auth');
const { uploadThroughMediaModal } = require('../../utils/media');
const { HomePage } = require('../home/home-page');
const { LayoutBuilderPage } = require('../layout-builder/layout-builder-page');
const { RegistrationPage } = require('../registration/registration-page');

/** The caption the plugin gives the media modal's button (seatreg_admin.js). */
const PAGE_LOGO_CONFIRM_LABEL = 'Use as logo';

/** The room the settings specs put their seats in. Nothing asserts on it. */
const ROOM_NAME = 'Settings room';

/**
 * The settings the specs touch, and the section tab each one lives in.
 *
 * A field is looked up by name rather than by selector so a spec never has to
 * know which section a setting sits in, and so the table stays the one place
 * that has to grow when a later spec covers another tab.
 */
const FIELDS = {
	registrationName: { tab: 'general', selector: '#registration-name', kind: 'text' },
	registrationStatus: { tab: 'general', selector: '#registration-status', kind: 'checkbox' },
	closeReason: { tab: 'general', selector: '#registration-close-reason', kind: 'text' },
	registrationPassword: { tab: 'general', selector: '#registration-password', kind: 'text' },
	requireWpLogin: { tab: 'general', selector: '#require-wp-login', kind: 'checkbox' },
	maxSeats: { tab: 'general', selector: '#registration-max-seats', kind: 'text' },

	usePending: { tab: 'booking-flow', selector: '#use-pending', kind: 'checkbox' },

	usingCalendar: { tab: 'scheduling', selector: '#using-calendar', kind: 'checkbox' },
	startTime: { tab: 'scheduling', selector: '#registration-start-time', kind: 'time' },
	endTime: { tab: 'scheduling', selector: '#registration-end-time', kind: 'time' },

	infoText: { tab: 'booking-flow', selector: '#registration-info-text', kind: 'text' },
	showInfoButton: { tab: 'booking-flow', selector: '#show-info-button', kind: 'checkbox' },
	pdfLogoPosition: { tab: 'booking-flow', selector: '#booking-pdf-logo-position', kind: 'select' },
	onePersonCheckout: { tab: 'booking-flow', selector: '#one-person-checkout', kind: 'checkbox' },
	automaticBookingConfirmDialog: {
		tab: 'booking-flow',
		selector: '#automatic-booking-confirm-dialog',
		kind: 'checkbox',
	},
	requireName: { tab: 'booking-flow', selector: '#require-name', kind: 'checkbox' },
	gmailRequired: { tab: 'booking-flow', selector: '#gmail-required', kind: 'checkbox' },
	zoomOnTop: { tab: 'booking-flow', selector: '#zoom-on-top', kind: 'checkbox' },
	seatSelectionBtnText: {
		tab: 'booking-flow',
		selector: '#seat-selection-btn-text',
		kind: 'text',
	},
	customFooterText: {
		tab: 'booking-flow',
		selector: '#customFooterTextEditor',
		kind: 'richText',
	},

	emailFrom: { tab: 'emails', selector: '#email-from', kind: 'text' },
	approvedEmailTemplate: { tab: 'emails', selector: '#approved-booking-email-template', kind: 'text' },
	emailConfirm: { tab: 'emails', selector: '#email-confirm', kind: 'checkbox' },
	bookerPendingNotification: {
		tab: 'emails',
		selector: '#booker-pending-booking-notification',
		kind: 'checkbox',
	},
	approvedBookingEmail: { tab: 'emails', selector: '#approved-booking-email', kind: 'checkbox' },
	adminBookingNotification: { tab: 'emails', selector: '#booking-notification', kind: 'checkbox' },

	currencyCode: { tab: 'payments', selector: '#paypal-currency-code', kind: 'text' },
	stripeEnabled: { tab: 'payments', selector: '#stripe', kind: 'checkbox' },
	stripeApiKey: { tab: 'payments', selector: '#stripe-api-key', kind: 'text' },

	customizePageColors: { tab: 'pages', selector: '#customize-page-colors', kind: 'checkbox' },
	pageBackgroundColor: { tab: 'pages', selector: '#page-background-color', kind: 'text' },
	pageHeadingColor: { tab: 'pages', selector: '#page-heading-color', kind: 'text' },
	pageTextColor: { tab: 'pages', selector: '#page-text-color', kind: 'text' },
	bookingNotFoundText: { tab: 'pages', selector: '#bookingNotFoundTextEditor', kind: 'richText' },
	bookingStatusStyles: {
		tab: 'pages',
		selector: 'textarea[name="booking-status-custom-styles"]',
		kind: 'text',
	},
	bookingConfirmStyles: {
		tab: 'pages',
		selector: 'textarea[name="booking-confirm-custom-styles"]',
		kind: 'text',
	},

	customStyles: { tab: 'advanced', selector: '#custom-styles', kind: 'text' },
	publicApi: { tab: 'advanced', selector: '#public-api', kind: 'checkbox' },
};

/** The kinds of custom field the builder offers, by the caption it lists them under. */
const CUSTOM_FIELD_TYPES = {
	text: 'Text',
	checkbox: 'Checkbox',
	select: 'Select',
};

/**
 * Page object for the SeatReg Settings screen
 * (admin.php?page=seatreg-options&tab=<code>).
 *
 * Five things about the screen shape the code here:
 *
 * 1. It is one form split into section tab panels, and a hidden panel keeps its
 *    fields in the DOM. They can be read there, but not filled, so set() opens
 *    the section a setting belongs to before touching it.
 * 2. Reaching the screen through the menu opens the oldest registration, not
 *    the one a test just created (seatreg_get_options() falls back to the first
 *    one by create timestamp). openForNewRegistration() goes through the Home
 *    card's Settings link instead, which carries the registration code.
 * 3. Saving is a plain form post that redirects back to this same URL, with no
 *    success notice of any kind, so save() waits for the reloaded screen to
 *    arrive rather than for something to appear on the old one.
 * 4. The screen has no link to the registration itself. Checking what a setting
 *    did to a visitor's view goes back to Home, which has one.
 * 5. A few settings are gated by another one: the page colours are disabled
 *    until page colours are being customised. set() does not know about that,
 *    so a spec has to set the gate first, the way a user would.
 */
class SettingsPage {
	constructor(page) {
		this.page = page;
		this.homePage = new HomePage(page);
	}

	/* Screen shell */

	/** Names the registration being edited, so it says which one is open. */
	get heading() {
		return this.page.locator('h4.settings-heading');
	}

	get form() {
		return this.page.locator('#seatreg-settings-form');
	}

	get saveButton() {
		return this.page.locator('#seatreg-settings-submit');
	}

	/**
	 * alertify appends its toasts and hides them on a timer, so the newest one
	 * is the one that belongs to the click that just happened.
	 */
	get errorToast() {
		return this.page.locator('.alertify-log-error').last();
	}

	/** The registration picker above the form, one tab per registration. */
	registrationTab(code) {
		return this.page.locator(`.nav-tab-wrapper a.nav-tab[href*="tab=${code}"]`);
	}

	/* Booking flow summary. The plugin's own reading of the settings, written out
	   as sentences above the tab bar, so it belongs to no section and is on
	   screen whichever one is open. */

	get bookingFlowSummary() {
		return this.page.locator('#booking-flow-summary');
	}

	get bookingFlowSummaryToggle() {
		return this.form.locator('summary.booking-flow-details__summary');
	}

	/**
	 * One of the summary's headings and the sentences under it.
	 *
	 * @param {string} title e.g. 'Making a booking'
	 */
	summaryGroup(title) {
		return this.bookingFlowSummary
			.locator('.flow-group')
			.filter({ has: this.page.getByText(title, { exact: true }) });
	}

	/**
	 * The link a summary sentence carries to the setting behind it.
	 *
	 * @param {string} target The setting's selector, as the link names it
	 */
	summaryJumpLink(target) {
		return this.bookingFlowSummary.locator(`.flow-jump[data-target="${target}"]`);
	}

	/* Sections */

	sectionTab(tab) {
		return this.form.locator(`.settings-tab[data-tab="${tab}"]`);
	}

	sectionPanel(tab) {
		return this.form.locator(`.settings-tab-panel[data-tab-panel="${tab}"]`);
	}

	/** The section on screen. Only the active panel is displayed. */
	get activeSectionPanel() {
		return this.form.locator('.settings-tab-panel--active');
	}

	/* Settings */

	/**
	 * A setting's control, for asserting on. Reading works from any section,
	 * including one that is not on screen.
	 *
	 * @param {string} name A key of FIELDS
	 */
	field(name) {
		return this.page.locator(this.#fieldConfig(name).selector);
	}

	/* Scheduling. The two registration dates are each a pair: a display input
	   showing dd.mm.yyyy, which has no name and is never posted, and a hidden
	   field holding the unix milliseconds that are. Only the datepicker writes
	   the hidden one, so both are named here and both get asserted. */

	/**
	 * @param {string} which 'start' or 'end'
	 */
	registrationDateInput(which) {
		return this.page.locator(`#registration-${which}-timestamp`);
	}

	registrationDateValue(which) {
		return this.page.locator(`#${which}-timestamp`);
	}

	/** The group the calendar toggle shows and hides. */
	get calendarDatesGroup() {
		return this.form.locator('.form-group').filter({ has: this.page.locator('#calendar-dates') });
	}

	/** Always on the page, unlike the registration dates' popup picker. */
	get calendarDatesPicker() {
		return this.page.locator('#calendar-dates-picker');
	}

	get calendarDates() {
		return this.page.locator('#calendar-dates');
	}

	/**
	 * The chip a picked day is listed as, found by its own date rather than by
	 * position, so the assertion says which day it is about.
	 *
	 * @param {string} date A yyyy-mm-dd date, as the picker stores it
	 */
	calendarDateChip(date) {
		return this.page
			.locator('#calendar-dates-list .calendar-date-chip')
			.filter({ has: this.page.locator(`[data-date="${date}"]`) });
	}

	/* Pages. The logo is an attachment id in a hidden field, with a preview and a
	   Remove button that the media modal and the field between them decide the
	   state of. */

	get pageLogo() {
		return this.page.locator('#page-logo');
	}

	get pageLogoPreview() {
		return this.page.locator('#page-logo-preview');
	}

	get pageLogoSelectButton() {
		return this.page.locator('#page-logo-select');
	}

	get pageLogoRemoveButton() {
		return this.page.locator('#page-logo-remove');
	}

	/* Advanced. Custom fields are not a setting but a builder: they are drawn one
	   at a time into a list, and only the save button's own click turns that list
	   into the value that gets posted. */

	get customFieldList() {
		return this.form.locator('.existing-custom-fields');
	}

	/**
	 * A custom field that has been added to the list.
	 *
	 * @param {string} label The name it was created under
	 */
	customField(label) {
		return this.customFieldList.locator(`.custom-container[data-label="${label}"]`);
	}

	/** The names of the fields in the order they are listed, which is the order
	    they reach a booker in. */
	customFieldLabels() {
		return this.customFieldList.locator('.custom-container .l-text').allInnerTexts();
	}

	/* The box a new field is built in, before it joins the list. */

	get newCustomFieldLabel() {
		return this.form.locator('.cust-field-create .cust-input-label');
	}

	get newCustomFieldType() {
		return this.form.locator('.cust-field-create .custom-field-select');
	}

	get newCustomFieldOptionName() {
		return this.form.locator('.cust-field-create .option-name');
	}

	/**
	 * The options a select field is being given. They only apply to the field
	 * being built, and are cleared once it joins the list.
	 */
	get newCustomFieldOptions() {
		return this.form.locator('.cust-field-create .existing-options .option-value');
	}

	/**
	 * The dialog for changing a select field's options.
	 *
	 * Only fields that have been saved get one: the pencil that opens it is
	 * rendered by the server, and the one the builder makes has neither the
	 * pencil nor the select id the pencil points at. The dialog is put on the
	 * body rather than in the form, so it is not looked for inside it.
	 */
	get editOptionsDialog() {
		return this.page.locator('dialog');
	}

	get editOptionsError() {
		return this.editOptionsDialog.locator('#error-message');
	}

	get editOptionInputs() {
		return this.editOptionsDialog.locator('#options-list input');
	}

	/** The options the dialog is offering, in the order it lists them. */
	editOptionValues() {
		return this.editOptionInputs.evaluateAll((inputs) => inputs.map((input) => input.value));
	}

	/* API tokens. Created and deleted the moment the button is pressed, not when
	   the settings are saved. */

	get apiTokens() {
		return this.page.locator('#public-api-tokens .token-box');
	}

	get createApiTokenButton() {
		return this.page.locator('#create-api-token');
	}

	/* Actions */

	/**
	 * Open the screen through the plugin's menu. This lands on whichever
	 * registration the plugin picks, so open() or openForNewRegistration() is
	 * what a test that cares about a particular one should use.
	 */
	async goto() {
		await openSeatRegScreen(this.page, 'Settings');
	}

	/**
	 * Open the settings of a registration by picking it from the registration
	 * tabs, the way a user switches between them.
	 */
	async open(code) {
		await this.goto();
		await this.registrationTab(code).click();
		await expectOnSeatRegPage(this.page, SEATREG_PAGES.SETTINGS, { tab: code });
	}

	/**
	 * Create a registration on the Home screen and open its settings.
	 *
	 * @param {string} name Registration name, must be unique for the run
	 * @return {Promise<string>} The registration's code
	 */
	async openForNewRegistration(name) {
		await this.homePage.goto();

		const code = await this.homePage.createRegistration(name);

		await this.homePage.settingsLink(code).click();
		await expectOnSeatRegPage(this.page, SEATREG_PAGES.SETTINGS, { tab: code });
		await expect(this.form).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		return code;
	}

	/**
	 * Create a registration with something to book, and open its settings.
	 *
	 * Most of what the booking flow settings decide is only visible on a
	 * registration a visitor can actually get as far as the booking form on, so
	 * those tests need a map with seats on it before they touch a setting.
	 *
	 * @param {string} name      Registration name, must be unique for the run
	 * @param {number} seatCount How many seats to put in its one room
	 * @return {Promise<string>} The registration's code
	 */
	async openForNewRegistrationWithSeats(name, seatCount) {
		const builder = new LayoutBuilderPage(this.page);

		const code = await builder.openForNewRegistration(name);

		await builder.nameFirstRoom(ROOM_NAME);
		await builder.placeSeats(seatCount);
		await builder.save();

		await this.open(code);

		return code;
	}

	/**
	 * Put the registration in a state where a booking can actually be made.
	 *
	 * Two things stand in the way of one by default, and both are email.
	 *
	 * The plugin sends its booking emails during the submit itself, and turns a
	 * send it could not complete into a booking that failed. The site the tests
	 * run on has no way to send mail at all, so every email whose result the
	 * booking path looks at has to be off. The one it does not look at is turned
	 * off too, only so the request is not left waiting on it.
	 *
	 * Email verification is the other one: with it on the booking is written at
	 * a status that is filtered out of everything - the map does not show it and
	 * the seat stays free - so no test could see that it worked.
	 *
	 * @param {boolean} options.approved Whether bookings skip the pending state.
	 *                                   The only way to get a seat to show as
	 *                                   taken rather than pending.
	 */
	async allowBookings({ approved = false } = {}) {
		await this.set('emailConfirm', false);
		await this.set('adminBookingNotification', false);

		if (approved) {
			await this.set('usePending', false);
			await this.set('approvedBookingEmail', false);
		} else {
			await this.set('bookerPendingNotification', false);
		}

		await this.save();
	}

	/**
	 * Unfold the booking flow summary so its sentences can be read.
	 *
	 * It starts collapsed, and until it is opened there is no text in it to
	 * assert on. Retried because the summary is wired up by the same footer
	 * script the section tabs are.
	 */
	async openBookingFlowSummary() {
		await clickUntil(this.bookingFlowSummaryToggle, this.bookingFlowSummary);
	}

	/**
	 * Show a section of the form.
	 *
	 * The tabs are wired up by a footer script, so a click landing right after
	 * the screen loaded can be ignored. clickUntil() retries only while the
	 * panel is still hidden, which also makes asking for the section already on
	 * screen a no-op.
	 *
	 * @param {string} tab One of the data-tab values, e.g. 'payments'
	 */
	async openSection(tab) {
		await clickUntil(this.sectionTab(tab), this.sectionPanel(tab));
	}

	/**
	 * Give a setting a value, opening its section first.
	 *
	 * @param {string} name A key of FIELDS
	 * @param {string|boolean} value Text for a text or number field, the option
	 *                               value for a select, checked for a checkbox
	 */
	async set(name, value) {
		const config = this.#fieldConfig(name);

		await this.openSection(config.tab);

		const field = this.page.locator(config.selector);

		if (config.kind === 'checkbox') {
			await field.setChecked(value);

			return;
		}

		if (config.kind === 'select') {
			await field.selectOption(value);

			return;
		}

		if (config.kind === 'time') {
			/* The clock widget leaves the input in place, so it can be filled,
			   but it opens a popup over the form on focus. Blurring puts that
			   away again before anything else is clicked.

			   Closing the popup is also when the widget decides what the field
			   is worth, and a value it never saw typed that reads as a round
			   hour past midnight (00:00, 01:00) is thrown away as if nothing had
			   been entered. Pick times outside that shape; the check below says
			   so plainly rather than leaving a later assertion to fail. */
			await field.fill(value);
			await field.blur();

			await expect(field).toHaveValue(value);

			return;
		}

		if (config.kind === 'richText') {
			/* A TinyMCE field. In its Visual mode the textarea is behind the
			   editor's iframe and holds nothing; its Text mode hands the
			   textarea back, and is also what makes it the thing that gets
			   posted, so the whole value goes through the tab first.

			   The editor is only set up once its section has been shown, and it
			   opens in Visual mode, so a tab clicked before that is undone
			   again. Retrying until the textarea is on screen waits that out. */
			await clickUntil(this.page.locator(`${config.selector}-html`), field);

			await field.fill(value);

			return;
		}

		await field.fill(value);
	}

	/**
	 * Pick one of the registration's dates from its datepicker.
	 *
	 * Typing into the field on screen would not do: it carries no name, and the
	 * value that gets posted is the timestamp the picker writes into the hidden
	 * field next to it when a day is chosen.
	 *
	 * @param {string} which 'start' or 'end'
	 * @param {Date} date
	 */
	async pickRegistrationDate(which, date) {
		await this.openSection('scheduling');
		await this.registrationDateInput(which).click();

		/* jQuery UI keeps one popup for every datepicker on the page. */
		const picker = this.page.locator('#ui-datepicker-div');
		await expect(picker).toBeVisible();

		await this.#pickDay(picker, date);
		await expect(picker).toBeHidden();
	}

	/**
	 * Pick the days the registration is open on. This picker is part of the
	 * form rather than a popup, and stays put while several days are chosen.
	 *
	 * @param {Date[]} dates
	 */
	async pickCalendarDates(dates) {
		await this.openSection('scheduling');

		for (const date of dates) {
			await this.#pickDay(this.calendarDatesPicker, date);
		}
	}

	/**
	 * Click a day in a jQuery UI datepicker, stepping to its month first.
	 *
	 * Only one month is on screen at a time, and the cells carry the month and
	 * year they belong to, so the target cell showing up is what says the right
	 * month has been reached.
	 */
	async #pickDay(picker, date) {
		const cell = picker
			.locator(`td[data-month="${date.getMonth()}"][data-year="${date.getFullYear()}"]`)
			.getByText(String(date.getDate()), { exact: true });

		const shownMonth = picker.locator('.ui-datepicker-calendar td[data-month]').first();

		for (let step = 0; step < 24 && (await cell.count()) === 0; step += 1) {
			const shown = new Date(
				Number(await shownMonth.getAttribute('data-year')),
				Number(await shownMonth.getAttribute('data-month')),
				1
			);
			const target = new Date(date.getFullYear(), date.getMonth(), 1);

			await picker.locator(target > shown ? '.ui-datepicker-next' : '.ui-datepicker-prev').click();
		}

		await cell.click();
	}

	/**
	 * Give the registration a page logo.
	 *
	 * The form has no file input of its own: the button opens the WordPress
	 * media modal, and only choosing something in there writes the attachment id
	 * into the field that posts. An image is uploaded rather than picked from
	 * whatever the library already holds, so the test knows which one it got.
	 *
	 * @return {Promise<string>} The attachment's title
	 */
	async selectPageLogo() {
		await this.openSection('pages');
		await this.pageLogoSelectButton.click();

		const title = await uploadThroughMediaModal(this.page, PAGE_LOGO_CONFIRM_LABEL);

		await expect(this.pageLogo).not.toHaveValue('');

		return title;
	}

	/**
	 * Fill in the box a new custom field is built in, without applying it.
	 *
	 * @param {Object} field
	 * @param {string} field.label   The name to ask for it under
	 * @param {string} field.type    A key of CUSTOM_FIELD_TYPES
	 * @param {string[]} field.options The choices a select field offers
	 */
	async #fillNewCustomField({ label, type, options = [] }) {
		await this.openSection('advanced');

		await this.newCustomFieldLabel.fill(label);
		await this.newCustomFieldType.selectOption({ label: CUSTOM_FIELD_TYPES[type] });

		for (const option of options) {
			await this.newCustomFieldOptionName.fill(option);
			await this.form.locator('.cust-field-create .add-select-option').click();
		}

		if (options.length) {
			await expect(this.newCustomFieldOptions).toHaveCount(options.length);
		}
	}

	/**
	 * Add a custom field to the registration.
	 *
	 * @see #fillNewCustomField for the shape of the field
	 */
	async addCustomField(field) {
		await this.#fillNewCustomField(field);
		await this.form.locator('.cust-field-create .apply-custom-field').click();

		await expect(this.customField(field.label)).toBeVisible();
	}

	/**
	 * Try to add a custom field and expect the builder to turn it down.
	 *
	 * The field never leaves the box it is built in, so the toast is the only
	 * sign anything happened, and the list not growing is the other half of it.
	 *
	 * @param {string} message The error the screen is expected to show
	 */
	async addCustomFieldExpectingError(field, message) {
		const listed = await this.customFieldList.locator('.custom-container').count();

		await this.#fillNewCustomField(field);
		await this.form.locator('.cust-field-create .apply-custom-field').click();

		await expect(this.errorToast).toHaveText(message);
		await expect(this.customFieldList.locator('.custom-container')).toHaveCount(listed);
	}

	/**
	 * Take a custom field off the list, or decide not to.
	 *
	 * It asks first, through the browser's own confirm, which Playwright says no
	 * to unless it is told otherwise - so both answers have to be given here.
	 *
	 * @param {boolean} options.confirm Whether to go through with it
	 */
	async removeCustomField(label, { confirm = true } = {}) {
		this.page.once('dialog', (dialog) => (confirm ? dialog.accept() : dialog.dismiss()));

		await this.customField(label).locator('.remove-cust-item').click();
	}

	/** Move a custom field one place down the list. */
	async moveCustomFieldDown(label) {
		await this.customField(label).locator('.custom-container-move-down').click();
	}

	/**
	 * Open the dialog for changing a saved select field's options.
	 */
	async openEditOptions(label) {
		await this.openSection('advanced');
		await this.customField(label).locator('.edit-options').click();

		await expect(this.editOptionsDialog).toBeVisible();
	}

	/**
	 * Create an API token.
	 *
	 * The token is made over AJAX and is live at once, with nothing to save, so
	 * the new box arriving is what says it exists.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} The new token's box
	 */
	async createApiToken() {
		await this.openSection('advanced');

		const listed = await this.apiTokens.count();

		await this.createApiTokenButton.click();
		await expect(this.apiTokens).toHaveCount(listed + 1);

		return this.apiTokens.last();
	}

	/**
	 * Delete an API token, or decide not to. Asks through the browser's confirm,
	 * the same way removing a custom field does.
	 *
	 * @param {import('@playwright/test').Locator} token A token box
	 */
	async removeApiToken(token, { confirm = true } = {}) {
		this.page.once('dialog', (dialog) => (confirm ? dialog.accept() : dialog.dismiss()));

		await token.locator('.remove-token').click();
	}

	/**
	 * Save the settings.
	 *
	 * The form posts to admin-post.php, which redirects back to this screen
	 * without leaving a notice behind. The reloaded screen is therefore the only
	 * sign the save went through, so that is what is waited for.
	 */
	async save() {
		const reloaded = this.page.waitForResponse(
			(response) =>
				response.request().method() === 'GET' &&
				response.url().includes(`page=${SEATREG_PAGES.SETTINGS}`),
			{ timeout: TIMEOUTS.NAVIGATION }
		);

		await this.saveButton.click();
		await reloaded;

		await this.page.waitForLoadState('domcontentloaded');
		await expect(this.form).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/**
	 * Try to save and expect the form's own validation to stop it.
	 *
	 * The check runs on the submit button's click and calls preventDefault(), so
	 * a blocked save never leaves the page. That is what makes the toast proof
	 * of anything: a post that went through would reload the screen and take the
	 * toast with it. The url is checked too, because a post the server refuses
	 * ends on a wp_die page instead.
	 *
	 * @param {string} message The error the screen is expected to show
	 */
	async saveExpectingError(message) {
		const urlBeforeSubmit = this.page.url();

		await this.saveButton.click();

		await expect(this.errorToast).toHaveText(message);
		expect(this.page.url()).toBe(urlBeforeSubmit);
	}

	/**
	 * Reload the screen, dropping anything that was not saved.
	 */
	async reload() {
		await this.page.reload();
		await expect(this.form).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/**
	 * Open the registration a visitor sees, to check what a setting did to it.
	 * The Settings screen has no link to it, so this goes through Home.
	 *
	 * @return {Promise<RegistrationPage>} The registration in its own tab
	 */
	async openRegistration(code) {
		await this.homePage.goto();

		return this.homePage.openRegistration(code);
	}

	/**
	 * Open the registration as someone who is not logged in, in a browser of
	 * their own.
	 *
	 * The tests' own browser is always the admin, so a setting that only does
	 * something to a visitor without a session can only be seen working from a
	 * context that never had one. The address comes off the Home card because
	 * the path in front of a registration's query string depends on the site's
	 * permalink settings.
	 *
	 * The returned page's context stays open until the caller closes it.
	 *
	 * @param {import('@playwright/test').Browser} browser The `browser` fixture
	 * @return {Promise<RegistrationPage>} The registration in a visitor's browser
	 */
	async openRegistrationAsVisitor(browser, code) {
		await this.homePage.goto();

		const registrationUrl = await this.homePage.registrationLink(code).getAttribute('href');

		const context = await visitorContext(browser);
		const visitorPage = await context.newPage();

		await visitorPage.goto(registrationUrl);
		await visitorPage.waitForLoadState('domcontentloaded');

		return new RegistrationPage(visitorPage);
	}

	#fieldConfig(name) {
		const config = FIELDS[name];

		if (!config) {
			throw new Error(`Unknown SeatReg setting: ${name}`);
		}

		return config;
	}
}

module.exports = { SettingsPage, FIELDS };
