const path = require('path');
const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { clickUntil, expectModalShown, expectModalHidden } = require('../../utils/interactions');
const { HomePage } = require('../home/home-page');
const { RegistrationPage } = require('../registration/registration-page');

/**
 * Page object for the layout builder, which is not a screen of its own but a
 * popup on Home opened by a registration's Layout button.
 *
 * Its dialogs are opened with .modal('toggle'), so a second click closes the one
 * that just opened and nothing here clicks a trigger twice. alertify renders
 * every confirm into the same node, so the delete room and unsaved changes
 * prompts share their selectors and are told apart by their message.
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

	/* Room strip */

	get addRoomButton() {
		return this.page.locator('#new-room-create');
	}

	get deleteRoomButton() {
		return this.page.locator('#current-room-delete');
	}

	get changeRoomNameLink() {
		return this.page.locator('.change-room-name');
	}

	get changeRoomDescriptionLink() {
		return this.page.locator('.change-room-description');
	}

	get roomSelections() {
		return this.page.locator('#room-selection-wrapper .room-selection');
	}

	roomSelection(name) {
		return this.roomSelections.filter({
			has: this.page.locator('.room-title', { hasText: name }),
		});
	}

	/** The builder moves this id, not a class. */
	get activeRoomSelection() {
		return this.page.locator('#room-selection-wrapper #active-room');
	}

	/**
	 * The builder only renders the arrows a room can use, so the first room has no
	 * left arrow and the last has no right one.
	 *
	 * @param {string} direction 'left' or 'right'
	 */
	reorderArrow(name, direction) {
		return this.roomSelection(name).locator(`.room-reorder[data-direction="${direction}"]`);
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

	get roomNameError() {
		return this.roomNameDialog.locator('.room-name-error');
	}

	/* Room description dialog */

	get roomDescriptionDialog() {
		return this.page.locator('#room-description-dialog');
	}

	get roomDescriptionInput() {
		return this.page.locator('#room-description-input');
	}

	get roomDescriptionSaveButton() {
		return this.page.locator('#room-description-save');
	}

	get roomDescriptionError() {
		return this.roomDescriptionDialog.locator('.room-description-error');
	}

	/* Build area */

	get selectTool() {
		return this.page.locator('.mouse-action-boxes .action1');
	}

	get lassoTool() {
		return this.page.locator('.mouse-action-boxes .action4');
	}

	get seatTool() {
		return this.page.locator('.mouse-action-boxes .action2');
	}

	get textTool() {
		return this.page.locator('.mouse-action-boxes .action9');
	}

	get gridBoxes() {
		return this.page.locator('.build-area .skeleton-box');
	}

	get seats() {
		return this.page.locator('.build-area .drag-box');
	}

	/**
	 * By the number it was created with. Renumbering only rewrites the caption, so
	 * this stays the seat's identity; seatNumbers() says what they are called now.
	 */
	seat(number) {
		return this.page.locator(`.build-area .drag-box[data-seatnr="${number}"]`);
	}

	get seatNumbers() {
		return this.seats.locator('.seat-number');
	}

	get selectedSeats() {
		return this.page.locator('.build-area .drag-box.active-box');
	}

	get textBoxes() {
		return this.page.locator('.build-area .text-box');
	}

	/* Seat numbering dialog. It reads the selection once, when it opens, and
	   rebinds its buttons to exactly those seats. */

	get numberingButton() {
		return this.page.locator('.numbering-option');
	}

	get numberingDialog() {
		return this.page.locator('#seat-numbering-dialog');
	}

	get numberingControls() {
		return this.page.locator('#seat-numbering-wrap');
	}

	get noSeatsSelectedAlert() {
		return this.page.locator('#seat-nr-change-no-selection');
	}

	get seatPrefixInput() {
		return this.page.locator('#seat-prefix');
	}

	get setSeatPrefixButton() {
		return this.page.locator('#set-seat-prefix');
	}

	get seatReorderInput() {
		return this.page.locator('#seat-reorder');
	}

	get reorderSeatsButton() {
		return this.page.locator('#reorder-seats');
	}

	/* Colour dialog. Its picker is vanilla-picker, whose editor field is the only
	   part worth driving - the hue and saturation strips are drag targets where
	   the colour you get depends on where in a gradient the cursor lands. */

	get colorButton() {
		return this.page.locator('.palette-call');
	}

	get colorDialog() {
		return this.page.locator('#color-dialog');
	}

	get colorEditorInput() {
		return this.colorDialog.locator('.picker_editor input');
	}

	get colorOkButton() {
		return this.colorDialog.locator('.picker_done button');
	}

	/* Background image dialog. Uploading an image and putting it on a room are two
	   steps: an upload is kept for the whole registration and can be added to, and
	   taken off, any of its rooms. */

	get backgroundImageButton() {
		return this.page.locator('.background-image');
	}

	get backgroundImageDialog() {
		return this.page.locator('#background-image-modal');
	}

	get imageFileInput() {
		return this.page.locator('#img-upload');
	}

	get uploadImageButton() {
		return this.page.locator('#file-sub');
	}

	/** By the file name the plugin stored it under. */
	uploadedImage(fileName) {
		return this.page
			.locator('#uploaded-images .uploaded-image-box')
			.filter({ has: this.page.locator(`.add-img-room[data-img="${fileName}"]`) });
	}

	get currentRoomImage() {
		return this.page.locator('#activ-room-img-wrap .cur-img-wrap');
	}

	get roomBackgroundImage() {
		return this.page.locator('.build-area img.room-image');
	}

	/* Scoped to the side bar: the builder also puts a bubble-text class on the
	   boxes that have hover text. */

	get hoverTextButton() {
		return this.page.locator('.build-area-side .bubble-text');
	}

	get hoverTextDialog() {
		return this.page.locator('#hover-dialog');
	}

	get hoverTextInput() {
		return this.page.locator('#box-hover-text');
	}

	get hoverTextSubmitButton() {
		return this.page.locator('#box-hover-submit');
	}

	/* Seat lock dialog. Like the numbering one it reads the selection when it
	   opens, and renders a row per selected seat. */

	get lockButton() {
		return this.page.locator('.lock-option');
	}

	get lockDialog() {
		return this.page.locator('#lock-seat-dialog');
	}

	/** Exact, so seat 1 does not also match seat 11. */
	lockRow(seatNumber) {
		return this.page
			.locator('#selected-seats-for-locking .lock-item')
			.filter({ has: this.page.getByText(String(seatNumber), { exact: true }) });
	}

	get applyLocksButton() {
		return this.page.locator('#set-seat-locks');
	}

	/* Legend dialog. A jQuery UI dialog, not a Bootstrap modal, and its creator is
	   a three step slider animated with jQuery. */

	get legendButton() {
		return this.page.locator('.legend-option');
	}

	get legendDialog() {
		return this.page.locator('#legend-dialog');
	}

	get legendCreatorToggle() {
		return this.page.locator('#toggle-lcreator');
	}

	get legendNameInput() {
		return this.page.locator('#new-legend-text');
	}

	/**
	 * Both ends of a step carry the same data-slide-open, so the step it leaves is
	 * part of the selector.
	 *
	 * @param {number} from Step the button sits on
	 * @param {number} to   Step it opens
	 */
	legendCreatorStep(from, to) {
		return this.page.locator(
			`#legend-creator .step-btn[data-slide="${from}"][data-slide-open="${to}"]`
		);
	}

	get createLegendButton() {
		return this.page.locator('#create-new-legend');
	}

	get legendSelect() {
		return this.page.locator('#use-select');
	}

	get applyLegendButton() {
		return this.page.locator('#apply-legend');
	}

	/* Controls */

	get saveButton() {
		return this.page.locator('#update-data');
	}

	get saveButtonText() {
		return this.saveButton.locator('.save-text');
	}

	get viewRegistrationLink() {
		return this.page.locator('#registration-link');
	}

	/* alertify reuses one node for both of the builder's confirm prompts, so these
	   are named after the widget and not after either prompt. */

	get confirmDialog() {
		return this.page.locator('#alertify.alertify-confirm');
	}

	get confirmMessage() {
		return this.confirmDialog.locator('.alertify-message');
	}

	get confirmOkButton() {
		return this.page.locator('#alertify-ok');
	}

	get confirmCancelButton() {
		return this.page.locator('#alertify-cancel');
	}

	/* Actions */

	/** @return {Promise<string>} The registration's code */
	async openForNewRegistration(name) {
		await this.homePage.goto();

		const code = await this.homePage.createRegistration(name);
		await this.open(code);

		return code;
	}

	/**
	 * The button is on the registration's Home card, so the caller has to be on
	 * Home already. The layout is fetched over AJAX before the popup is shown, and
	 * the longer reaction window covers that so a slow response does not look like
	 * an ignored click. Safe to retry: the handler disables the button in flight.
	 */
	async open(code) {
		await clickUntil(this.homePage.layoutButton(code), this.popup, {
			reaction: TIMEOUTS.DEFAULT,
		});
	}

	async nameFirstRoom(roomName) {
		await this.waitForRoomNameDialog();
		await this.submitRoomName(roomName);

		await expectModalHidden(this.roomNameDialog);
		await expect(this.roomName).toHaveText(roomName);
	}

	async dismissRoomNameDialog() {
		await this.waitForRoomNameDialog();
		await this.roomNameDialog.locator('.modal-footer button[data-dismiss="modal"]').click();
		await expectModalHidden(this.roomNameDialog);
	}

	/** The builder opens this itself, right after the layout finishes loading. */
	async waitForRoomNameDialog() {
		await expect(this.roomNameDialog).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
		await expectModalShown(this.roomNameDialog);
	}

	/** The builder names it "<n> room" and opens the name dialog straight away. */
	async addRoom(roomName) {
		await this.addRoomButton.click();
		await this.waitForRoomNameDialog();
		await this.submitRoomName(roomName);

		await expectModalHidden(this.roomNameDialog);
		await expect(this.activeRoomSelection).toHaveText(roomName);
		await expect(this.roomName).toHaveText(roomName);
	}

	/** Kept apart from renameCurrentRoom() so a spec can submit a name it rejects. */
	async openRoomNameDialog() {
		await this.changeRoomNameLink.click();
		await this.waitForRoomNameDialog();
	}

	async submitRoomName(roomName) {
		await this.roomNameInput.fill(roomName);
		await this.roomNameOkButton.click();
	}

	async renameCurrentRoom(roomName) {
		await this.openRoomNameDialog();
		await this.submitRoomName(roomName);

		await expectModalHidden(this.roomNameDialog);
		await expect(this.roomName).toHaveText(roomName);
	}

	async openRoomDescriptionDialog() {
		await this.changeRoomDescriptionLink.click();
		await expect(this.roomDescriptionDialog).toBeVisible();
		await expectModalShown(this.roomDescriptionDialog);
	}

	async submitRoomDescription(description) {
		await this.roomDescriptionInput.fill(description);
		await this.roomDescriptionSaveButton.click();
	}

	/**
	 * @param {boolean} options.confirm Whether to go through with the delete
	 * @return {Promise<string>} The confirm prompt's message
	 */
	async deleteCurrentRoom({ confirm }) {
		await this.deleteRoomButton.click();
		await expect(this.confirmDialog).toBeVisible();

		const message = await this.confirmMessage.innerText();

		await (confirm ? this.confirmOkButton : this.confirmCancelButton).click();
		await expect(this.confirmDialog).toBeHidden();

		return message;
	}

	/** Deferred behind a 300ms timeout and a loading overlay, so the title is the signal. */
	async selectRoom(roomName) {
		await this.roomSelection(roomName).locator('.room-title').click();
		await expect(this.roomName).toHaveText(roomName, { timeout: TIMEOUTS.NAVIGATION });
	}

	/** In the order the strip shows them. */
	async roomNames() {
		return this.roomSelections.locator('.room-title').allInnerTexts();
	}

	/**
	 * The builder creates a seat on mousedown over a grid box and leaves the new
	 * seat sitting on top of it, so each grid box is only ever used once.
	 */
	async placeSeats(count) {
		await this.seatTool.click();

		const placed = await this.seats.count();

		for (let i = 0; i < count; i++) {
			await this.gridBoxes.nth(i).click();
		}

		await expect(this.seats).toHaveCount(placed + count);
	}

	/**
	 * The builder puts the text into the layout on keyup, so it has to be typed
	 * key by key - a filled value never reaches the model. Blurring is part of
	 * placing it: a text box that loses focus while still empty deletes itself.
	 *
	 * @param {number} options.at Grid box to place it over
	 */
	async addText(text, { at = 0 } = {}) {
		await this.textTool.click();
		await this.gridBoxes.nth(at).click();

		const input = this.textBoxes.last().locator('.text-box-input');
		await expect(input).toBeFocused();

		await input.pressSequentially(text);
		await input.blur();
	}

	async selectSeat(number) {
		await this.selectTool.click();
		await this.seat(number).click();
		await expect(this.seat(number)).toHaveClass(/active-box/);
	}

	/**
	 * The only way to select more than one seat: the select tool empties the
	 * selection before adding the seat the click landed on. The drag starts just
	 * off the first seat's corner because the lasso has to begin on bare grid, and
	 * ends past the last seat's opposite corner so both are enclosed.
	 */
	async lassoSelectSeats(first, last) {
		await this.lassoTool.click();

		const start = await this.seat(first).boundingBox();
		const end = await this.seat(last).boundingBox();
		const margin = 5;

		await this.page.mouse.move(start.x - margin, start.y - margin);
		await this.page.mouse.down();
		await this.page.mouse.move(end.x + end.width + margin, end.y + end.height + margin, {
			steps: 10,
		});
		await this.page.mouse.up();

		await expect(this.selectedSeats).toHaveCount(last - first + 1);
	}

	async openSeatNumberingDialog() {
		await this.numberingButton.click();
		await expectModalShown(this.numberingDialog);
	}

	async closeSeatNumberingDialog() {
		await this.numberingDialog.locator('.modal-footer button[data-dismiss="modal"]').click();
		await expectModalHidden(this.numberingDialog);
	}

	/** The dialog binds to the seats selected when it opened, so select first. */
	async setSeatPrefix(prefix) {
		await this.openSeatNumberingDialog();
		await this.seatPrefixInput.fill(prefix);
		await this.setSeatPrefixButton.click();
		await this.closeSeatNumberingDialog();
	}

	/** Same precondition as setSeatPrefix(). */
	async reorderSeatsFrom(start) {
		await this.openSeatNumberingDialog();
		await this.seatReorderInput.fill(String(start));
		await this.reorderSeatsButton.click();
		await this.closeSeatNumberingDialog();
	}

	async openBackgroundImageDialog() {
		await this.backgroundImageButton.click();
		await expectModalShown(this.backgroundImageDialog);
	}

	async closeBackgroundImageDialog() {
		await this.backgroundImageDialog
			.locator('.modal-footer button[data-dismiss="modal"]')
			.click();
		await expectModalHidden(this.backgroundImageDialog);
	}

	/**
	 * The upload is a real multipart post, so its entry turning up in the dialog
	 * is what says it finished. The file keeps its name - the plugin stores it
	 * under that and rejects anything outside [0-9a-zA-Z-._].
	 *
	 * @return {Promise<string>} The name the image was stored under
	 */
	async setRoomBackgroundImage(filePath) {
		const fileName = path.basename(filePath);

		await this.openBackgroundImageDialog();

		await this.imageFileInput.setInputFiles(filePath);
		await this.uploadImageButton.click();

		const uploaded = this.uploadedImage(fileName);
		await expect(uploaded).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		await uploaded.locator('.add-img-room').click();
		await expect(this.currentRoomImage).toBeVisible();

		await this.closeBackgroundImageDialog();

		return fileName;
	}

	/** The upload itself stays, which is what tells this apart from removing it. */
	async removeRoomBackgroundImage() {
		await this.openBackgroundImageDialog();

		await this.currentRoomImage.locator('#rem-room-img').click();
		await expect(this.currentRoomImage).toHaveCount(0);

		await this.closeBackgroundImageDialog();
	}

	/**
	 * Both the button that opens the dialog and the one that submits it check the
	 * selection first, so nothing happens until a seat is selected. Line breaks
	 * survive as ^ in the saved layout and become breaks again on the registration.
	 */
	async setHoverText(text) {
		await this.hoverTextButton.click();
		await expectModalShown(this.hoverTextDialog);

		await this.hoverTextInput.fill(text);
		await this.hoverTextSubmitButton.click();

		await expectModalHidden(this.hoverTextDialog);
	}

	/**
	 * The colour is typed into the picker's editor field, which commits it on
	 * input, and Ok hands it to the builder. Under the select tool only the first
	 * selected seat is coloured.
	 */
	async setSeatColor(color) {
		await this.colorButton.click();
		await expectModalShown(this.colorDialog);

		await this.colorEditorInput.fill(color);
		await this.colorOkButton.click();

		await expectModalHidden(this.colorDialog);
	}

	/**
	 * Locks and passwords are set per seat on the same rows and applied by the
	 * same button, so they are done in one trip. The seats have to be selected
	 * first, and only selected seats get a row.
	 *
	 * @param {number[]} options.lock     Seat numbers to lock
	 * @param {Object}   options.password Password keyed by seat number
	 */
	async applySeatLocks({ lock = [], password = {} } = {}) {
		await this.lockButton.click();
		await expectModalShown(this.lockDialog);

		for (const seatNumber of lock) {
			await this.lockRow(seatNumber).locator('input[type="checkbox"]').check();
		}

		for (const [seatNumber, value] of Object.entries(password)) {
			await this.lockRow(seatNumber).locator('input[type="text"]').fill(value);
		}

		await this.applyLocksButton.click();

		await this.lockDialog.locator('.modal-footer button[data-dismiss="modal"]').click();
		await expectModalHidden(this.lockDialog);
	}

	/**
	 * The dialog reads the selection when it opens, so a seat has to be selected
	 * first.
	 *
	 * @param {string} legendName Must be unique for the registration
	 */
	async createAndApplyLegend(legendName) {
		await this.legendButton.click();
		await expect(this.legendDialog).toBeVisible();

		await this.legendCreatorToggle.click();
		await this.legendNameInput.fill(legendName);
		await this.legendCreatorStep(1, 2).click();
		await this.legendCreatorStep(2, 3).click();
		await this.createLegendButton.click();

		await expect(this.legendSelect).toContainText(legendName);
		await this.legendSelect.selectOption(legendName);
		await this.applyLegendButton.click();

		// jQuery UI renders the dialog's own Close button outside #legend-dialog.
		await this.page.keyboard.press('Escape');
		await expect(this.legendDialog).toBeHidden();
	}

	/**
	 * The request finishing is not enough: the builder clears its unsaved changes
	 * flag in the success callback, and that flag is what decides whether View
	 * registration opens. The callback also puts the button caption back, so the
	 * caption returning is the signal that it ran.
	 */
	async save() {
		const idleCaption = await this.saveButtonText.innerText();
		const saved = this.page.waitForResponse(
			(response) =>
				response.url().includes('admin-ajax.php') &&
				(response.request().postData() ?? '').includes('seatreg_update_layout'),
			{ timeout: TIMEOUTS.NAVIGATION }
		);

		await this.saveButton.click();
		await saved;

		await expect(this.saveButtonText).toHaveText(idleCaption);
	}

	/**
	 * Must follow save(): with unsaved changes the link cancels its own navigation
	 * and asks about them instead, so no tab would ever arrive.
	 *
	 * @return {Promise<RegistrationPage>} The registration in its own tab
	 */
	async openRegistration() {
		const popup = this.page.waitForEvent('popup', { timeout: TIMEOUTS.NAVIGATION });

		await this.viewRegistrationLink.click();

		const registrationTab = await popup;
		await registrationTab.waitForLoadState('domcontentloaded');

		return new RegistrationPage(registrationTab);
	}

	/**
	 * The builder marks itself as having unsaved changes as soon as it loads a
	 * registration, so closing always asks for confirmation first.
	 */
	async close() {
		await this.closeButton.click();
		await expect(this.confirmDialog).toBeVisible();
		await this.confirmOkButton.click();
		await expect(this.popup).toBeHidden();
	}
}

module.exports = { LayoutBuilderPage };
