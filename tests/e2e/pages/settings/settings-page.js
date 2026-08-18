const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { SEATREG_PAGES, openSeatRegScreen, expectOnSeatRegPage } = require('../../utils/navigation');
const { clickUntil } = require('../../utils/interactions');
const { visitorContext } = require('../../utils/auth');
const { HomePage } = require('../home/home-page');
const { RegistrationPage } = require('../registration/registration-page');

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

	usingCalendar: { tab: 'scheduling', selector: '#using-calendar', kind: 'checkbox' },
	startTime: { tab: 'scheduling', selector: '#registration-start-time', kind: 'time' },
	endTime: { tab: 'scheduling', selector: '#registration-end-time', kind: 'time' },

	infoText: { tab: 'booking-flow', selector: '#registration-info-text', kind: 'text' },
	showInfoButton: { tab: 'booking-flow', selector: '#show-info-button', kind: 'checkbox' },
	pdfLogoPosition: { tab: 'booking-flow', selector: '#booking-pdf-logo-position', kind: 'select' },

	emailFrom: { tab: 'emails', selector: '#email-from', kind: 'text' },
	approvedEmailTemplate: { tab: 'emails', selector: '#approved-booking-email-template', kind: 'text' },

	currencyCode: { tab: 'payments', selector: '#paypal-currency-code', kind: 'text' },
	stripeEnabled: { tab: 'payments', selector: '#stripe', kind: 'checkbox' },
	stripeApiKey: { tab: 'payments', selector: '#stripe-api-key', kind: 'text' },
};

/**
 * Page object for the SeatReg Settings screen
 * (admin.php?page=seatreg-options&tab=<code>).
 *
 * Four things about the screen shape the code here:
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
