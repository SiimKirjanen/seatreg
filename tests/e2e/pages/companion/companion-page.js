const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { SEATREG_PAGES, openSeatRegScreen } = require('../../utils/navigation');

/**
 * Page object for the SeatReg Companion screen
 * (admin.php?page=seatreg-companion-app) and the app it lets through.
 *
 * The switch is a site option rather than a registration setting, so it is the
 * one thing in the suite two workers could take away from each other - which is
 * why its spec is serial and puts back what it found.
 *
 * Saving posts to admin-post.php and redirects back here leaving no notice, so
 * the reloaded screen is the only sign it went through, as on Settings.
 */
class CompanionPage {
	constructor(page) {
		this.page = page;
	}

	/* Screen shell */

	get form() {
		return this.page.locator('.companion-app-form');
	}

	get enabledCheckbox() {
		return this.form.locator('input[name="seatreg_companion_app_enabled"]');
	}

	/** WordPress gives it the default id, so it is only unique inside the form. */
	get saveButton() {
		return this.form.locator('#submit');
	}

	/** The screen writes the address out rather than linking a route it knows. */
	get appLink() {
		return this.page.locator('a[href*="seatreg=companion"]');
	}

	/* The app itself */

	get appRoot() {
		return this.page.locator('#root');
	}

	/* Actions */

	async goto() {
		await openSeatRegScreen(this.page, 'Companion');
		await expect(this.form).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/**
	 * Switch the app on or off and wait for the screen the save redirects back to.
	 * Posted whatever the box already said, so a caller can put the option into a
	 * known state without first reading it.
	 */
	async setEnabled(enabled) {
		await this.enabledCheckbox.setChecked(enabled);

		const reloaded = this.page.waitForResponse(
			(response) =>
				response.request().method() === 'GET' &&
				response.url().includes(`page=${SEATREG_PAGES.COMPANION}`),
			{ timeout: TIMEOUTS.NAVIGATION }
		);

		await this.saveButton.click();
		await reloaded;

		await this.page.waitForLoadState('domcontentloaded');
		await expect(this.enabledCheckbox).toBeChecked({ checked: enabled });
	}

	/**
	 * Open the app at the address the screen advertises. A disabled app answers
	 * with a bare sentence rather than a page, so there is nothing to wait for
	 * beyond the response itself.
	 */
	async openApp() {
		const appUrl = await this.appLink.getAttribute('href');

		await this.page.goto(appUrl);
		await this.page.waitForLoadState('domcontentloaded');
	}
}

module.exports = { CompanionPage };
