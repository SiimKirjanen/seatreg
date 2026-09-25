const { test, expect } = require('@playwright/test');
const { LayoutBuilderPage } = require('../layout-builder/layout-builder-page');
const { HomePage } = require('../home/home-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

/* Two rooms with different numbers of seats, so switching between them changes
   something a test can count. Only the first has a description, which is what
   says the line is emptied for a room that has none. */
const STALLS = { name: 'Stalls', seats: 2, description: 'Ground floor, step free' };
const BALCONY = { name: 'Balcony', seats: 3 };

/* What a visitor does with the map itself: moving between rooms and reading it.
   What the map is drawn from belongs to the builder's specs. */

test.describe('Registration map', () => {
	let homePage;
	let registration;
	let code;

	test.beforeEach(async ({ page }) => {
		homePage = new HomePage(page);

		const builder = new LayoutBuilderPage(page);

		code = await builder.openForNewRegistration(uniqueRegistrationName('Registration map'));

		await builder.nameFirstRoom(STALLS.name);
		await builder.openRoomDescriptionDialog();
		await builder.submitRoomDescription(STALLS.description);
		await builder.placeSeats(STALLS.seats);

		await builder.addRoom(BALCONY.name);
		await builder.placeSeats(BALCONY.seats);

		await builder.save();

		registration = await builder.openRegistration();
	});

	test('switches to another room and repaints everything it shows', async () => {
		await expect(registration.activeRoomLink).toHaveText(STALLS.name);
		await expect(registration.seats).toHaveCount(STALLS.seats);
		await expect(registration.roomDescription).toHaveText(STALLS.description);
		await expect(registration.roomCounts.first()).toContainText(String(STALLS.seats));

		await registration.openRoom(BALCONY.name);

		await expect(registration.activeRoomLink).toHaveText(BALCONY.name);
		await expect(registration.seats).toHaveCount(BALCONY.seats);
		await expect(registration.roomCounts.first()).toContainText(String(BALCONY.seats));

		/* The other room was never described, so the line has to be cleared
		   rather than left saying what the last room said. */
		await expect(registration.roomDescription).toBeEmpty();
	});

	test('opens the room named in the address', async ({ page }) => {
		const arrived = await homePage.openRegistrationWith(code, { room: BALCONY.name });

		await expect(arrived.activeRoomLink).toHaveText(BALCONY.name);
		await expect(arrived.seats).toHaveCount(BALCONY.seats);

		/* The address is only ever read. Moving around the registration leaves
		   it as the visitor arrived with it. */
		const arrivedAt = page.url();

		await arrived.openRoom(STALLS.name);

		expect(page.url()).toBe(arrivedAt);
	});

	test('tells a visitor what the registration holds', async () => {
		await registration.header.click();

		await expect(registration.infoDialog).toBeVisible();

		await expect(registration.totalRooms).toHaveText('2');
		await expect(registration.totalOpenSeats).toHaveText(String(STALLS.seats + BALCONY.seats));
		await expect(registration.totalPendingBookings).toHaveText('0');
		await expect(registration.totalApprovedBookings).toHaveText('0');
	});
});
