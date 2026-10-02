const path = require('path');
const { test, expect } = require('@playwright/test');
const { LayoutBuilderPage } = require('./layout-builder-page');
const { SettingsPage } = require('../settings/settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { escapeForRegExp } = require('../../utils/text');

const ROOM = 'Main hall';
const SEAT_COUNT = 3;
const LEGEND = 'Wheelchair';
const SEAT_PREFIX = 'A';
const REORDER_FROM = 10;
const SEAT_PASSWORD = 'letmein7f3a';
const LAYOUT_TEXT = 'Stage';

/* What gets typed into the colour picker, and what the browser computes it to.
   The picker hands the plugin an rgba string, which renders as rgb at full
   alpha. */
const SEAT_COLOR = '#e91e63';
const SEAT_COLOR_RGB = 'rgb(233, 30, 99)';

/* One seat for each status the status colors cover, and one left in none of them. */
const STATUS_SEAT_COUNT = 5;
const PENDING_SEAT = 1;
const APPROVED_SEAT = 2;
const LOCKED_SEAT = 3;
const SELECTED_SEAT = 4;
const UNTOUCHED_SEAT = 5;

const SEAT_STATUS_COLORS = {
	pending: { value: '#ff9800', computed: 'rgb(255, 152, 0)' },
	approved: { value: '#4a148c', computed: 'rgb(74, 20, 140)' },
	selected: { value: '#00bcd4', computed: 'rgb(0, 188, 212)' },
	locked: { value: '#607d8b', computed: 'rgb(96, 125, 139)' },
};
const LAYOUT_SEAT_COLOR = 'rgb(97, 179, 41)';
const WHITE = 'rgb(255, 255, 255)';

const HOVER_TEXT_LINES = ['Extra legroom', 'Next to the exit'];

/* One of the plugin's own images, so the suite carries no binary of its own.
   Its name has to stay within [0-9a-zA-Z-._], which the upload form enforces. */
const BACKGROUND_IMAGE = path.join(__dirname, '../../../../img/chairs_med.jpg');

/* What the builder draws into a room. Seat numbers and legends are the values
   the plugin computes for a visitor, so both are followed through the save to
   the registration itself. */

test.describe('Layout builder map', () => {
	let builder;
	let code;

	test.beforeEach(async ({ page }) => {
		builder = new LayoutBuilderPage(page);

		code = await builder.openForNewRegistration(uniqueRegistrationName('Layout map'));
		await builder.nameFirstRoom(ROOM);
	});

	test('prefixes the numbers of the selected seats only', async () => {
		await builder.placeSeats(2);

		await builder.openSeatNumberingDialog();
		await expect(builder.noSeatsSelectedAlert).toBeVisible();
		await expect(builder.numberingControls).toBeHidden();
		await builder.closeSeatNumberingDialog();

		await builder.selectSeat(1);
		await builder.setSeatPrefix(SEAT_PREFIX);

		await expect(builder.seat(1)).toHaveText(`${SEAT_PREFIX}1`);
		await expect(builder.seat(2)).toHaveText('2');

		await builder.save();

		const registration = await builder.openRegistration();
		await expect(registration.seat(1)).toHaveText(`${SEAT_PREFIX}1`);
		await expect(registration.seat(2)).toHaveText('2');
	});

	test('renumbers the selected seats counting up from a chosen number', async () => {
		await builder.placeSeats(SEAT_COUNT);
		await builder.lassoSelectSeats(2, SEAT_COUNT);

		await builder.reorderSeatsFrom(REORDER_FROM);

		await expect(builder.seatNumbers).toHaveText(['1', '10', '11']);

		await builder.save();

		const registration = await builder.openRegistration();
		await expect(registration.seat(1)).toHaveText('1');
		await expect(registration.seat(10)).toHaveText('10');
		await expect(registration.seat(11)).toHaveText('11');
	});

	test('locks and password protects seats', async () => {
		await builder.placeSeats(SEAT_COUNT);
		await builder.lassoSelectSeats(1, 2);

		await builder.applySeatLocks({ lock: [1], password: { 2: SEAT_PASSWORD } });

		await builder.save();

		const registration = await builder.openRegistration();

		await registration.openSeat(1);
		await expect(registration.seatNotice).toBeVisible();
		await expect(registration.addToBookingButton).toHaveCount(0);
		await registration.closeSeatDialog();

		await registration.openSeat(2);
		await expect(registration.seatPasswordInput).toBeVisible();
		await expect(registration.addToBookingButton).toHaveCount(0);
		await registration.closeSeatDialog();

		/* The seat that was left alone still books as usual, which is what makes
		   the two above mean something. */
		await registration.openSeat(3);
		await expect(registration.addToBookingButton).toBeVisible();
		await registration.closeSeatDialog();

		/* Only the locked seat is out of reach, so only it stops counting as open. */
		await expect(registration.roomCounts.first()).toContainText(`: ${SEAT_COUNT - 1}`);
	});

	test('adds text that reaches the registration and drops it when left empty', async () => {
		await builder.addText(LAYOUT_TEXT);
		await expect(builder.textBoxes).toHaveCount(1);

		/* A text box that is clicked into place and then left without any text
		   takes itself back out again. */
		await builder.addText('', { at: 5 });
		await expect(builder.textBoxes).toHaveCount(1);

		await builder.save();

		const registration = await builder.openRegistration();
		await expect(registration.textBoxes).toHaveText([LAYOUT_TEXT]);
	});

	test('colours the selected seat and leaves the others alone', async () => {
		await builder.placeSeats(2);
		await builder.selectSeat(1);

		await builder.setSeatColor(SEAT_COLOR);

		await expect(builder.seat(1)).toHaveCSS('background-color', SEAT_COLOR_RGB);
		await expect(builder.seat(2)).not.toHaveCSS('background-color', SEAT_COLOR_RGB);

		await builder.save();

		const registration = await builder.openRegistration();
		await expect(registration.seat(1)).toHaveCSS('background-color', SEAT_COLOR_RGB);
		await expect(registration.seat(2)).not.toHaveCSS('background-color', SEAT_COLOR_RGB);
	});

	test('paints each seat in the color picked for its status', async ({ page }) => {
		const settings = new SettingsPage(page);

		await builder.placeSeats(STATUS_SEAT_COUNT);
		await builder.lassoSelectSeats(LOCKED_SEAT, LOCKED_SEAT);
		await builder.applySeatLocks({ lock: [LOCKED_SEAT] });
		await builder.save();

		await settings.open(code);
		await settings.allowBookings();
		await settings.makeBooking(code, { seats: [PENDING_SEAT] });

		await settings.open(code);
		await settings.allowBookings({ approved: true });
		await settings.makeBooking(code, { seats: [APPROVED_SEAT] });

		await settings.openLayout(code);
		await builder.setSeatStatusColors(
			Object.fromEntries(Object.entries(SEAT_STATUS_COLORS).map(([status, color]) => [status, color.value]))
		);

		/* The builder knows which seats are booked or locked, so it shows their fill
		   before anything is saved. Selecting only happens on the registration. */
		await expect(builder.seat(PENDING_SEAT)).toHaveCSS('background-color', SEAT_STATUS_COLORS.pending.computed);
		await expect(builder.seat(APPROVED_SEAT)).toHaveCSS('background-color', SEAT_STATUS_COLORS.approved.computed);
		await expect(builder.seat(LOCKED_SEAT)).toHaveCSS('background-color', SEAT_STATUS_COLORS.locked.computed);
		await expect(builder.seat(UNTOUCHED_SEAT)).toHaveCSS('background-color', LAYOUT_SEAT_COLOR);
		await expect(builder.seat(PENDING_SEAT).locator('.bron-sign')).toBeHidden();

		await builder.save();

		const registration = await builder.openRegistration();

		await registration.addSeatToBooking(SELECTED_SEAT);

		const pendingSeat = registration.seat(PENDING_SEAT);
		const approvedSeat = registration.seat(APPROVED_SEAT);

		await expect(pendingSeat).toHaveCSS('background-color', SEAT_STATUS_COLORS.pending.computed);
		await expect(approvedSeat).toHaveCSS('background-color', SEAT_STATUS_COLORS.approved.computed);
		await expect(registration.seat(LOCKED_SEAT)).toHaveCSS(
			'background-color',
			SEAT_STATUS_COLORS.locked.computed
		);
		await expect(registration.seat(SELECTED_SEAT)).toHaveCSS(
			'background-color',
			SEAT_STATUS_COLORS.selected.computed
		);
		await expect(registration.seat(UNTOUCHED_SEAT)).toHaveCSS('background-color', LAYOUT_SEAT_COLOR);

		/* The fill says what the dot used to, so the dot goes. The number is
		   turned light to stay readable on a dark fill. */
		await expect(pendingSeat.locator('.bron-sign')).toBeHidden();
		await expect(approvedSeat.locator('.taken-sign')).toBeHidden();
		await expect(approvedSeat).toHaveCSS('color', WHITE);

		/* What each color means is told beside the room's counts, and a locked
		   seat only gets a line there once it has a color of its own. */
		await expect(registration.roomCounts.locator('.bron-legend')).toHaveCSS(
			'background-color',
			SEAT_STATUS_COLORS.pending.computed
		);
		await expect(registration.roomCounts.locator('.tak-legend')).toHaveCSS(
			'background-color',
			SEAT_STATUS_COLORS.approved.computed
		);

		const lockedCount = registration.roomCounts.filter({
			has: registration.page.locator('.locked-legend'),
		});

		await expect(lockedCount).toContainText(': 1');
		await expect(lockedCount.locator('.locked-legend')).toHaveCSS(
			'background-color',
			SEAT_STATUS_COLORS.locked.computed
		);
	});

	test('adds hover text that the registration shows on the seat', async () => {
		await builder.placeSeats(2);
		await builder.selectSeat(1);

		await builder.setHoverText(HOVER_TEXT_LINES.join('\n'));

		await builder.save();

		const registration = await builder.openRegistration();

		/* Both lines have to come back: a line break is stored as ^ and turned
		   back into a break when the seat is painted. */
		await registration.openSeat(1);
		for (const line of HOVER_TEXT_LINES) {
			await expect(registration.seatHoverText).toContainText(line);
		}
		await registration.closeSeatDialog();

		await registration.openSeat(2);
		await expect(registration.seatHoverText).toBeHidden();
		await registration.closeSeatDialog();
	});

	test('puts a background image on the room and takes it off again', async () => {
		await builder.placeSeats(1);

		const fileName = await builder.setRoomBackgroundImage(BACKGROUND_IMAGE);
		const servedAs = new RegExp(`${escapeForRegExp(fileName)}$`);

		await expect(builder.roomBackgroundImage).toHaveAttribute('src', servedAs);

		await builder.save();

		const registration = await builder.openRegistration();
		await expect(registration.roomBackgroundImage).toHaveAttribute('src', servedAs);
		await expect.poll(() => registration.backgroundImageLoaded()).toBe(true);

		/* Taking the image off the room leaves the upload in place, ready to be
		   put on another room. */
		await builder.removeRoomBackgroundImage();
		await expect(builder.roomBackgroundImage).toHaveCount(0);
		await expect(builder.uploadedImage(fileName)).toHaveCount(1);
	});

	test('applies a legend that reaches the registration', async () => {
		await builder.placeSeats(1);
		await builder.selectSeat(1);
		await builder.createAndApplyLegend(LEGEND);

		await builder.save();

		const registration = await builder.openRegistration();
		await expect(registration.legend(LEGEND)).toBeVisible();
		await expect(registration.seatsWithLegend(LEGEND)).toHaveCount(1);
	});
});
