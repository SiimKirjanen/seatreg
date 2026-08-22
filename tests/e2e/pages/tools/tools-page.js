const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { openSeatRegScreen } = require('../../utils/navigation');

/**
 * Page object for the SeatReg Tools screen (admin.php?page=seatreg-tools).
 *
 * The screen holds no registration, so it is the one SeatReg screen with no tab
 * bar and nothing to set up before it can be opened.
 *
 * Both tools report through alertify rather than into the page: there is no
 * result element anywhere on the screen, so a toast is the whole of what the
 * screen says back. The QR code is the exception, and it is not reported at all -
 * the image is written to disk while the screen renders, so it is either served
 * or it is broken.
 */
class ToolsPage {
	constructor(page) {
		this.page = page;
	}

	/* Screen shell */

	get heading() {
		return this.page.locator('.seatreg-wp-admin.wrap h1');
	}

	/* Email testing */

	get emailForm() {
		return this.page.locator('#email-tester-form');
	}

	get emailInput() {
		return this.page.locator('#test-email-address');
	}

	/** An input rather than a button, so its caption is a value. */
	get sendButton() {
		return this.page.locator('#seatreg-send-test-email');
	}

	/* alertify hides its toasts on a timer, so the newest is the one just raised. */

	get errorToast() {
		return this.page.locator('.alertify-log-error').last();
	}

	get successToast() {
		return this.page.locator('.alertify-log-success').last();
	}

	/* QR code testing. Written into the uploads folder as the screen renders, and
	   shown from there, so nothing here asks for it. */

	get qrCode() {
		return this.page.locator('img[src*="seatreg-qr-code-test"]');
	}

	/** Only rendered in place of the image, when the site has no gd extension. */
	get qrCodeNotice() {
		return this.page.locator('.seatreg-wp-admin.wrap .alert');
	}

	/* Actions */

	async goto() {
		await openSeatRegScreen(this.page, 'Tools');
		await expect(this.emailForm).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/**
	 * Ask for a test mail and leave the answer to the caller: the address is
	 * checked in the browser before anything is sent, so a malformed one is
	 * refused without the screen ever asking the server.
	 */
	async sendTestEmail(address) {
		await this.emailInput.fill(address);
		await this.sendButton.click();
	}

	/** The src alone only says where the screen looked, not that anything was served. */
	async qrCodeLoaded() {
		return this.qrCode.evaluate((img) => img.complete && img.naturalWidth > 0);
	}
}

module.exports = { ToolsPage };
