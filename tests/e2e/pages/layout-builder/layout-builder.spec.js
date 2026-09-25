const path = require('path');
const { test, expect } = require('@playwright/test');
const { LayoutBuilderPage } = require('./layout-builder-page');
const { HomePage } = require('../home/home-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const BACKGROUND_IMAGE = path.join(__dirname, '../../../../img/chairs_med.jpg');

test.describe('Layout builder', () => {
	let builder;
	let name;

	test.beforeEach(async ({ page }) => {
		builder = new LayoutBuilderPage(page);

		name = uniqueRegistrationName('Layout builder');
		await builder.openForNewRegistration(name);
	});

	test('warns about unsaved changes and closes only when discarded', async () => {
		await builder.dismissRoomNameDialog();

		await builder.closeButton.click();
		await expect(builder.confirmMessage).toHaveText(
			'Unsaved changes. You sure you want to leave?'
		);
		await builder.confirmCancelButton.click();
		await expect(builder.registrationName).toHaveText(name);

		await builder.close();
	});

	/* The file comes from the server, so there is nothing to export until the
	   layout has been saved once. */
	test('exports the saved layout once there is one', async () => {
		await expect(builder.exportButton).toBeHidden();

		await builder.nameFirstRoom('Exported hall');
		await builder.placeSeats(2);
		await builder.save();

		await expect(builder.exportButton).toBeVisible();

		const { contents } = await builder.exportLayout();

		expect(contents.seatregLayoutExport).toBe(1);
		expect(contents.layout.roomData[0].room.name).toBe('Exported hall');
		expect(contents.layout.roomData[0].boxes).toHaveLength(2);
	});

	test('says the export will not hold the changes that are still unsaved', async () => {
		await builder.nameFirstRoom('Exported hall');
		await builder.placeSeats(1);
		await builder.save();

		await builder.placeSeats(1);

		await builder.exportButton.click();
		await expect(builder.confirmMessage).toContainText('unsaved changes will not be in it');
		await builder.confirmCancelButton.click();

		/* Going ahead gives the layout as it was saved, one seat short of the screen. */
		const { contents } = await builder.exportLayout({ discardingUnsavedChanges: true });

		expect(contents.layout.roomData[0].boxes).toHaveLength(1);
		await expect(builder.seats).toHaveCount(2);
	});
});

test.describe('Layout builder start choices', () => {
	let builder;
	let homePage;

	test.beforeEach(async ({ page }) => {
		builder = new LayoutBuilderPage(page);
		homePage = new HomePage(page);
		await homePage.goto();
	});

	/**
	 * A registration with a saved one-room layout, left closed. Downloads its
	 * layout file on the way out when a test needs one to import.
	 */
	async function registrationWithLayout(roomName, { withImage = false, exporting = false } = {}) {
		const code = await homePage.createRegistration(uniqueRegistrationName('Layout source'));
		let exported = null;

		await builder.open(code);
		await builder.nameFirstRoom(roomName);
		await builder.placeSeats(2);

		if (withImage) {
			await builder.setRoomBackgroundImage(BACKGROUND_IMAGE);
		}

		await builder.save();

		if (exporting) {
			exported = await builder.exportLayout();
		}

		await builder.close();

		return { code, exported };
	}

	test('asks how to start only until a layout is saved', async () => {
		const code = await homePage.createRegistration(uniqueRegistrationName('Layout start'));

		await builder.openRaw(code);
		await expect(builder.startDialog).toBeVisible();

		await builder.startFromScratch();
		await builder.nameFirstRoom('Main hall');
		await builder.save();
		await builder.close();

		await builder.openRaw(code);

		await expect(builder.startDialog).toBeHidden();
		await expect(builder.roomName).toHaveText('Main hall');

		await builder.close();
	});

	test('imports a layout from a file', async () => {
		const { exported } = await registrationWithLayout('Imported hall', { exporting: true });
		const path = exported.path;

		const code = await homePage.createRegistration(uniqueRegistrationName('Layout import'));
		await builder.openRaw(code);
		await builder.importLayoutFile(path);

		await expect(builder.roomName).toHaveText('Imported hall');
		await expect(builder.seats).toHaveCount(2);

		/* Nothing is written until Save, so closing has to warn about it. */
		await builder.closeButton.click();
		await expect(builder.confirmMessage).toHaveText(
			'Unsaved changes. You sure you want to leave?'
		);
		await builder.confirmCancelButton.click();

		await builder.save();
		await builder.close();

		await builder.openRaw(code);
		await expect(builder.roomName).toHaveText('Imported hall');

		await builder.close();
	});

	/* Unlike the file, a registration on this site brings its background images
	   along - they are copied into the folder of the registration being built. */
	test('copies a layout from another registration, background images and all', async () => {
		const { code: sourceCode } = await registrationWithLayout('Copied hall', { withImage: true });

		const code = await homePage.createRegistration(uniqueRegistrationName('Layout copy'));
		await builder.openRaw(code);
		await builder.copyLayoutFrom(sourceCode);

		await expect(builder.roomName).toHaveText('Copied hall');
		await expect(builder.seats).toHaveCount(2);
		await expect(builder.roomBackgroundImage).toHaveAttribute(
			'src',
			new RegExp(`room_images/${code}/`)
		);

		await builder.save();

		const registration = await builder.openRegistration();
		await expect.poll(() => registration.backgroundImageLoaded()).toBe(true);
	});

	test('refuses a file that is not a SeatReg layout', async () => {
		const code = await homePage.createRegistration(uniqueRegistrationName('Layout bad file'));

		await builder.openRaw(code);
		await builder.importLayoutFile({
			name: 'not-a-layout.json',
			mimeType: 'application/json',
			buffer: Buffer.from('{"hello":1}'),
		});

		await expect(builder.startDialogMessage).toHaveText('That is not a SeatReg layout file.');
		await expect(builder.startDialog).toBeVisible();
	});
});
