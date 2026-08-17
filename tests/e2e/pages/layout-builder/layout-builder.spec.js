const { test, expect } = require('@playwright/test');
const { LayoutBuilderPage } = require('./layout-builder-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

test.describe('Layout builder', () => {
	let builder;
	let name;

	test.beforeEach(async ({ page }) => {
		builder = new LayoutBuilderPage(page);

		name = uniqueRegistrationName('Layout builder');
		await builder.openForNewRegistration(name);
	});

	test('opens with the registration loaded and names its first room', async () => {
		await expect(builder.registrationName).toHaveText(name);
		await expect(builder.roomNameDialog.locator('.modal-title')).toHaveText('Room name');

		await builder.nameFirstRoom('Main hall');

		await expect(builder.roomName).toHaveText('Main hall');
	});

	test('warns about unsaved changes and closes only when discarded', async () => {
		await builder.dismissRoomNameDialog();

		await builder.closeButton.click();
		await expect(builder.unsavedChangesMessage).toHaveText(
			'Unsaved changes. You sure you want to leave?'
		);
		await builder.keepEditingButton.click();
		await expect(builder.registrationName).toHaveText(name);

		await builder.close();
	});
});
