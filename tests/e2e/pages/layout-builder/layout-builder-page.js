const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { clickUntil, expectModalShown } = require('../../utils/interactions');
const { HomePage } = require('../home/home-page');

/**
 * Page object for the layout builder.
 *
 * The builder is not an admin screen of its own. It is a popup rendered on the
 * Home screen and opened by a registration's Layout button, so getting into it
 * goes through HomePage.
 */
class LayoutBuilderPage {
	constructor(page) {
		this.page = page;
		this.homePage = new HomePage(page);
	}

	/* Builder shell */

	get popup() {
		return this.page.locator('.seatreg-builder-popup');
	}

	get registrationName() {
		return this.page.locator('.reg-title-name');
	}

	get closeButton() {
		return this.page.locator('.builder-popup-close');
	}

	get roomName() {
		return this.page.locator('.room-title-name');
	}

	/* A registration without a layout gets an empty first room, and the builder
	   opens the room name dialog on top of itself to have it named. */

	get roomNameDialog() {
		return this.page.locator('#room-name-dialog');
	}

	get roomNameInput() {
		return this.page.locator('#room-name-dialog-input');
	}

	get roomNameOkButton() {
		return this.page.locator('#room-dialog-ok');
	}

	/* The "unsaved changes" prompt is an alertify confirm, not a native dialog,
	   so it is asserted through the DOM. */

	get unsavedChangesDialog() {
		return this.page.locator('#alertify.alertify-confirm');
	}

	get unsavedChangesMessage() {
		return this.unsavedChangesDialog.locator('.alertify-message');
	}

	get discardChangesButton() {
		return this.page.locator('#alertify-ok');
	}

	get keepEditingButton() {
		return this.page.locator('#alertify-cancel');
	}

	/* Actions */

	/**
	 * Create a registration on the Home screen and open its layout builder.
	 *
	 * @param {string} name Registration name, must be unique for the run
	 * @return {Promise<string>} The registration's code
	 */
	async openForNewRegistration(name) {
		await this.homePage.goto();

		const code = await this.homePage.createRegistration(name);
		await this.open(code);

		return code;
	}

	/**
	 * The layout is fetched over AJAX before the popup is shown. The longer
	 * reaction window covers that request, so a slow response does not look like
	 * an ignored click. Safe to retry - the handler disables the button while its
	 * request is in flight.
	 */
	async open(code) {
		await clickUntil(this.homePage.layoutButton(code), this.popup, {
			reaction: TIMEOUTS.DEFAULT,
		});
	}

	async nameFirstRoom(roomName) {
		await this.waitForRoomNameDialog();
		await this.roomNameInput.fill(roomName);
		await this.roomNameOkButton.click();
		await expect(this.roomNameDialog).toBeHidden();
	}

	async dismissRoomNameDialog() {
		await this.waitForRoomNameDialog();
		await this.roomNameDialog.locator('.modal-footer button[data-dismiss="modal"]').click();
		await expect(this.roomNameDialog).toBeHidden();
	}

	/** The builder opens this itself, right after the layout finishes loading. */
	async waitForRoomNameDialog() {
		await expect(this.roomNameDialog).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
		await expectModalShown(this.roomNameDialog);
	}

	/**
	 * Close the builder, discarding the changes. It marks itself as having
	 * unsaved changes as soon as it loads a registration, so closing always asks
	 * for confirmation first.
	 */
	async close() {
		await this.closeButton.click();
		await expect(this.unsavedChangesDialog).toBeVisible();
		await this.discardChangesButton.click();
		await expect(this.popup).toBeHidden();
	}
}

module.exports = { LayoutBuilderPage };
