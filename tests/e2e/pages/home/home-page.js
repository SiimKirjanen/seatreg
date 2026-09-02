const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { openSeatRegScreen } = require('../../utils/navigation');
const { clickUntil, expectModalShown } = require('../../utils/interactions');
const { RegistrationPage } = require('../registration/registration-page');

/**
 * Page object for the SeatReg Home screen (admin.php?page=seatreg-welcome).
 *
 * Ids here are not unique - `#submit` is the create form's button and every
 * copy-registration modal's - so selectors are scoped rather than id based. Every
 * registration renders its own copy of the More, Copy and Shortcode modals, all
 * keyed by data-registration-id.
 *
 * The layout builder is a popup on this screen, covered from
 * tests/e2e/pages/layout-builder. Only its Layout button belongs here.
 */
class HomePage {
	constructor(page) {
		this.page = page;
	}

	/* Page shell */

	get heading() {
		return this.page.getByRole('heading', { name: 'Create and manage online registrations' });
	}

	get registrationsHeader() {
		return this.page.locator('h4.your-registrations-header');
	}

	/* Create registration form */

	get createForm() {
		return this.page.locator('#create-registration-form');
	}

	get nameInput() {
		return this.page.locator('#new-registration-name');
	}

	get createButton() {
		return this.createForm.locator('input[type="submit"]');
	}

	get errorToast() {
		return this.page.locator('.alertify-log-error');
	}

	/* Registration cards */

	registrationCard(name) {
		return this.page
			.locator('[data-item="registration"]')
			.filter({ has: this.page.getByRole('link', { name, exact: true }) });
	}

	registrationCardByCode(code) {
		return this.page
			.locator('[data-item="registration"]')
			.filter({ has: this.page.locator(`[data-action="view-more-modal"][data-registration-id="${code}"]`) });
	}

	registrationNameLink(name) {
		return this.registrationCard(name).locator('.registration-name-link');
	}

	registrationLink(code) {
		return this.registrationCardByCode(code).getByRole('link', { name: 'Registration', exact: true });
	}

	statusBadge(code) {
		return this.registrationCardByCode(code).locator('.seatreg-registration-card__badge');
	}

	/** @param {'pending'|'approved'|'deleted'} type */
	bookingCount(code, type) {
		return this.registrationCardByCode(code)
			.locator(`.seatreg-registration-card__stat--${type} .seatreg-registration-card__stat-value`);
	}

	calendarIcon(code) {
		return this.registrationCardByCode(code).locator('.seatreg-registration-card__calendar-icon');
	}

	/** The date the counts beside it belong to. Calendar registrations only. */
	countedDate(code) {
		return this.registrationCardByCode(code).locator('.seatreg-registration-card__footer-date');
	}

	/** Stands in for the counted date when today takes no bookings. */
	footerNotice(code) {
		return this.registrationCardByCode(code).locator('.seatreg-registration-card__footer-notice');
	}

	layoutButton(code) {
		return this.page.locator(`.seatreg-map-popup-btn[data-map-code="${code}"]`);
	}

	overviewLink(code) {
		return this.registrationCardByCode(code).getByRole('link', { name: 'Overview', exact: true });
	}

	settingsLink(code) {
		return this.registrationCardByCode(code).getByRole('link', { name: 'Settings', exact: true });
	}

	bookingsLink(code) {
		return this.registrationCardByCode(code).getByRole('link', { name: 'Bookings', exact: true });
	}

	moreLink(code) {
		return this.page.locator(`[data-action="view-more-modal"][data-registration-id="${code}"]`);
	}

	/* Modals */

	moreModal(code) {
		return this.page.locator(`.more-items-modal[data-registration-id="${code}"]`);
	}

	copyModal(code) {
		return this.page.locator(`.copy-registration-modal[data-registration-id="${code}"]`);
	}

	shortcodeModal(code) {
		return this.page.locator(`.shortcode-modal[data-registration-id="${code}"]`);
	}

	get logsModal() {
		return this.page.locator('#registration-activity-modal');
	}

	get logsModalEntries() {
		return this.logsModal.locator('.activity-modal__logs');
	}

	deleteButton(code) {
		return this.moreModal(code).locator(`[id="delete-${code}"]`);
	}

	/* Actions */

	async goto() {
		await openSeatRegScreen(this.page, 'Home');
	}

	/** @return {Promise<string>} The new registration's code */
	async createRegistration(name) {
		await this.nameInput.fill(name);
		await this.createButton.click();

		const card = this.registrationCard(name);
		await expect(card).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		return card.locator('[data-registration-id]').first().getAttribute('data-registration-id');
	}

	/**
	 * This card is the only place in the admin that links to a registration.
	 *
	 * @return {Promise<RegistrationPage>} The registration in its own tab
	 */
	async openRegistration(code) {
		const popup = this.page.waitForEvent('popup', { timeout: TIMEOUTS.NAVIGATION });

		await this.registrationLink(code).click();

		const registrationTab = await popup;
		await registrationTab.waitForLoadState('domcontentloaded');

		return new RegistrationPage(registrationTab);
	}

	/**
	 * Open a registration at an address carrying the extra parameters a visitor
	 * can arrive with. Only the query string is the test's: the address still
	 * comes off the card, because the path in front of it depends on the site's
	 * permalink settings. Opens in this tab, since there is no link to click.
	 *
	 * @param {Object<string, string>} params Added to the address
	 * @return {Promise<RegistrationPage>}
	 */
	async openRegistrationWith(code, params) {
		await this.goto();

		const url = new URL(await this.registrationLink(code).getAttribute('href'));

		for (const [key, value] of Object.entries(params)) {
			url.searchParams.set(key, value);
		}

		await this.page.goto(url.toString());
		await this.page.waitForLoadState('domcontentloaded');

		return new RegistrationPage(this.page);
	}

	/** @param {string} action e.g. 'view-registration-activity' */
	moreModalItem(code, action) {
		return this.moreModal(code).locator(`[data-action="${action}"]`);
	}

	/** Safe to retry - the handler shows the modal instead of toggling it. */
	async openMoreModal(code) {
		await clickUntil(this.moreLink(code), this.moreModal(code));
		await expectModalShown(this.moreModal(code));
	}

	async closeMoreModal(code) {
		await this.moreModal(code).locator('.modal-footer button[data-dismiss="modal"]').click();
		await expect(this.moreModal(code)).toBeHidden();
	}

	/* Copy and Shortcode are opened from inside the More modal, which stays open
	   behind them. */

	async openCopyModal(code) {
		await this.openMoreModal(code);
		await this.moreModalItem(code, 'open-copy-registration').click();
		await expectModalShown(this.copyModal(code));
	}

	async openShortcodeModal(code) {
		await this.openMoreModal(code);
		await this.moreModalItem(code, 'view-shortcode').click();
		await expectModalShown(this.shortcodeModal(code));
	}

	async openLogsModal(code) {
		await this.openMoreModal(code);
		await this.moreModalItem(code, 'view-registration-activity').click();
		await expectModalShown(this.logsModal);
	}

	async copyRegistration(code, newName) {
		await this.openCopyModal(code);

		const modal = this.copyModal(code);
		await modal.locator(`[id="copy-registration-${code}"]`).fill(newName);
		await modal.locator('input[type="submit"]').click();

		await expect(this.registrationCard(newName)).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/** @return {Promise<string>} The confirm dialog's message */
	async deleteRegistration(code) {
		let dialogMessage = null;

		this.page.once('dialog', async (dialog) => {
			dialogMessage = dialog.message();
			await dialog.accept();
		});

		await this.deleteButton(code).click();
		await expect.poll(() => dialogMessage, { timeout: TIMEOUTS.DEFAULT }).not.toBeNull();
		await expect(this.registrationCardByCode(code)).toHaveCount(0, {
			timeout: TIMEOUTS.NAVIGATION,
		});

		return dialogMessage;
	}
}

module.exports = { HomePage };
