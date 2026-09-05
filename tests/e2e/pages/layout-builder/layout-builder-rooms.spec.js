const { test, expect } = require('@playwright/test');
const { LayoutBuilderPage } = require('./layout-builder-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const FIRST_ROOM = 'Main hall';
const SECOND_ROOM = 'Balcony';

const ROOM_DESCRIPTION = 'Seats 1-3 are next to the stage.';
const INVALID_ROOM_DESCRIPTION = 'Seats <b>1-3</b>';

/* The rooms of a layout, from creating one to the order they end up in on the
   registration. The order and the descriptions only reach a visitor through the
   layout save, so those two are checked on the registration itself rather than
   in the builder. */

test.describe('Layout builder rooms', () => {
	let builder;

	test.beforeEach(async ({ page }) => {
		builder = new LayoutBuilderPage(page);

		await builder.openForNewRegistration(uniqueRegistrationName('Layout rooms'));
		await builder.nameFirstRoom(FIRST_ROOM);
	});

	test('adds a room and makes it the one being edited', async () => {
		await builder.addRoom(SECOND_ROOM);

		await expect.poll(() => builder.roomNames()).toEqual([FIRST_ROOM, SECOND_ROOM]);

		/* The builder only renders the arrows a room can use, so the first room
		   cannot move left and the last cannot move right. */
		await expect(builder.reorderArrow(FIRST_ROOM, 'right')).toBeVisible();
		await expect(builder.reorderArrow(FIRST_ROOM, 'left')).toHaveCount(0);
		await expect(builder.reorderArrow(SECOND_ROOM, 'left')).toBeVisible();
		await expect(builder.reorderArrow(SECOND_ROOM, 'right')).toHaveCount(0);

		await builder.selectRoom(FIRST_ROOM);
		await expect(builder.activeRoomSelection).toHaveText(FIRST_ROOM);
	});

	test('reorders rooms and the registration shows the new order', async () => {
		await builder.addRoom(SECOND_ROOM);

		await builder.reorderArrow(FIRST_ROOM, 'right').click();
		await expect.poll(() => builder.roomNames()).toEqual([SECOND_ROOM, FIRST_ROOM]);

		await builder.save();

		const registration = await builder.openRegistration();
		await expect.poll(() => registration.roomNames()).toEqual([SECOND_ROOM, FIRST_ROOM]);
	});

	test('deletes the current room after confirming and keeps it when cancelled', async ({ page }) => {
		await builder.addRoom(SECOND_ROOM);

		const cancelledMessage = await builder.deleteCurrentRoom({ confirm: false });
		expect(cancelledMessage).toContain(SECOND_ROOM);
		await expect.poll(() => builder.roomNames()).toEqual([FIRST_ROOM, SECOND_ROOM]);

		await builder.deleteCurrentRoom({ confirm: true });
		await expect.poll(() => builder.roomNames()).toEqual([FIRST_ROOM]);

		/* A registration always has to have a room, so the builder confirms the
		   last one and then refuses it with a native alert. */
		let alertMessage = null;

		page.once('dialog', async (dialog) => {
			alertMessage = dialog.message();
			await dialog.accept();
		});

		await builder.deleteCurrentRoom({ confirm: true });
		await expect.poll(() => alertMessage).not.toBeNull();
		await expect.poll(() => builder.roomNames()).toEqual([FIRST_ROOM]);
	});

	test('renames a room and refuses a name another room already uses', async () => {
		await builder.addRoom(SECOND_ROOM);

		await builder.renameCurrentRoom('Upper balcony');
		await expect.poll(() => builder.roomNames()).toEqual([FIRST_ROOM, 'Upper balcony']);

		await builder.openRoomNameDialog();
		await builder.submitRoomName(FIRST_ROOM);

		await expect(builder.roomNameError).not.toBeEmpty();
		await expect(builder.roomNameDialog).toBeVisible();
		await expect(builder.roomName).toHaveText('Upper balcony');
	});

	test('describes a room for the registration and refuses invalid characters', async () => {
		await builder.openRoomDescriptionDialog();

		await builder.submitRoomDescription(INVALID_ROOM_DESCRIPTION);
		await expect(builder.roomDescriptionError).not.toBeEmpty();
		await expect(builder.roomDescriptionDialog).toBeVisible();

		await builder.submitRoomDescription(ROOM_DESCRIPTION);
		await expect(builder.roomDescriptionDialog).toBeHidden();

		await builder.save();

		const registration = await builder.openRegistration();
		await expect(registration.roomDescription).toHaveText(ROOM_DESCRIPTION);
	});
});
