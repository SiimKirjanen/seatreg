const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');

/**
 * Page object for the public registration view
 * (?seatreg=registration&c=<code>&page_id=seatreg).
 *
 * It has no spec of its own yet. The screen exists here so the layout builder
 * and settings specs can assert that what they saved survived, through named
 * locators instead of raw selectors. Its own tests get added to this folder
 * when the screen is covered.
 *
 * Two things about the screen shape the code here:
 *
 * 1. The page paints itself from the saved layout after load, so everything is
 *    asserted with a navigation length timeout rather than assumed present.
 * 2. Below 720px the room navigation collapses behind a "Change room" button.
 *    The suite runs at Desktop Chrome width, where the room links are on screen
 *    (registration/css/registration.scss), so nothing here opens that button.
 */
class RegistrationPage {
	constructor(page) {
		this.page = page;
	}

	/* What a visitor is shown instead of the registration. A closed registration
	   and a password protected one both replace the whole page, so neither of
	   these can be on screen at the same time as the rooms below. */

	get closedNotice() {
		return this.page.locator('#center-wrap h2');
	}

	/** Only rendered when a close reason was set in the settings. */
	get closeReason() {
		return this.page.locator('#center-wrap p');
	}

	get passwordForm() {
		return this.page.locator('#pwd-form');
	}

	get passwordInput() {
		return this.page.locator('#reg-pwd');
	}

	/**
	 * Shown over the registration when it takes bookings from logged in
	 * WordPress users only, and never rendered at all for someone who has a
	 * session.
	 */
	get loginNotice() {
		return this.page.locator('#login-notify');
	}

	/**
	 * Shown when the registration is outside the dates or the hours it takes
	 * bookings in. One element serves both: they are branches of the same
	 * condition, and the dates win over the hours.
	 */
	get timeNotice() {
		return this.page.locator('#time-notify');
	}

	/* Rooms */

	get roomNavLinks() {
		return this.page.locator('#room-nav-items .room-nav-link');
	}

	get roomDescription() {
		return this.page.locator('.top-info-bar [data-info="room"]');
	}

	/* Seats */

	get seats() {
		return this.page.locator('#boxes .box');
	}

	seat(number) {
		return this.page.locator(`#boxes .box[data-seat-nr="${number}"]`);
	}

	get textBoxes() {
		return this.page.locator('#boxes .box.text-box');
	}

	get roomBackgroundImage() {
		return this.page.locator('#boxes img.room-image');
	}

	/**
	 * Whether the browser really fetched the background image. The src alone
	 * only says where the page looked, not that anything was served from there.
	 */
	async backgroundImageLoaded() {
		return this.roomBackgroundImage.evaluate((img) => img.complete && img.naturalWidth > 0);
	}

	/* Seat dialog. Clicking a seat opens it, and what it offers is how the seat's
	   settings in the layout reach a visitor: a seat that can be booked gets an
	   add to booking button, one that cannot gets a notice instead. */

	get seatDialog() {
		return this.page.locator('#confirm-dialog-mob');
	}

	get addToBookingButton() {
		return this.seatDialog.locator('.add-to-cart');
	}

	get seatNotice() {
		return this.seatDialog.locator('.seat-taken-notify');
	}

	/** Shown only for a seat the layout gave hover text to. */
	get seatHoverText() {
		return this.seatDialog.locator('#confirm-dialog-mob-hover');
	}

	get seatPasswordInput() {
		return this.seatDialog.locator('#seat-password');
	}

	get seatDialogCloseButton() {
		return this.page.locator('#dialog-close-btn');
	}

	/* Legends */

	legend(name) {
		return this.page.locator('#legends .legend-div').filter({ hasText: name });
	}

	seatsWithLegend(name) {
		return this.page.locator(`#boxes .box[data-legend="${name}"]`);
	}

	/* Actions */

	/**
	 * The room names in the order the page shows them, which is the room order
	 * set in the builder and not the order the rooms were created in.
	 */
	async roomNames() {
		await expect(this.roomNavLinks.first()).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		return this.roomNavLinks.allInnerTexts();
	}

	/**
	 * Answer the password the registration is asking for. The form posts back to
	 * the registration itself, so a wrong password lands on the same form again.
	 */
	async submitPassword(password) {
		await this.passwordInput.fill(password);
		await this.passwordForm.locator('input[type="submit"]').click();
		await this.page.waitForLoadState('domcontentloaded');
	}

	/** Click a seat to see what the registration offers for it. */
	async openSeat(number) {
		await this.seat(number).click();
		await expect(this.seatDialog).toBeVisible();
	}

	async closeSeatDialog() {
		await this.seatDialogCloseButton.click();
		await expect(this.seatDialog).toBeHidden();
	}

	/**
	 * The whole page's HTML, for asserting that something never reached a
	 * visitor. The layout arrives as JSON in the markup, so a value that leaked
	 * would show up here even when no element renders it.
	 */
	async html() {
		return this.page.content();
	}
}

module.exports = { RegistrationPage };
