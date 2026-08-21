const path = require('path');
const { test, expect } = require('@playwright/test');
const { LayoutBuilderPage } = require('./layout-builder-page');
const { HomePage } = require('../home/home-page');
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

const HOVER_TEXT_LINES = ['Extra legroom', 'Next to the exit'];

const SEAT_PRICE = 25;

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

	test('places seats that reach the registration with the same numbers', async () => {
		await builder.placeSeats(SEAT_COUNT);

		for (let number = 1; number <= SEAT_COUNT; number++) {
			await expect(builder.seat(number)).toBeVisible();
		}

		await builder.save();

		const registration = await builder.openRegistration();
		await expect(registration.seats).toHaveCount(SEAT_COUNT);

		for (let number = 1; number <= SEAT_COUNT; number++) {
			await expect(registration.seat(number)).toHaveText(String(number));
		}
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

	test('prices the seats that were selected', async ({ page }) => {
		await builder.placeSeats(2);
		await builder.lassoSelectSeats(1, 2);

		await builder.setSeatPrices(SEAT_PRICE);
		await builder.save();

		/* Nothing on the map says what a seat costs, so the dialog the prices were
		   given to is also where they are read back. */
		await new HomePage(page).goto();
		await builder.open(code);

		await builder.lassoSelectSeats(1, 2);
		await builder.openPriceDialog();

		await expect(builder.priceInputs(1)).toHaveValue(String(SEAT_PRICE));
		await expect(builder.priceInputs(2)).toHaveValue(String(SEAT_PRICE));
	});

	test('locks and password protects seats without leaking the password', async () => {
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

		/* The registration is told a seat wants a password, never which one:
		   the layout goes through SeatregLayoutService::hideSensitiveData()
		   first, which flattens it to a boolean. */
		expect(await registration.html()).not.toContain(SEAT_PASSWORD);
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
