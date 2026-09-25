const { expect } = require('@playwright/test');

/* Site state a spec needs but no SeatReg screen makes: a post to put a shortcode
   in, a user who is not an administrator. Made through
   tests/e2e/mu-plugins/fixtures.php rather than clicked together in wp-admin,
   which would be the block editor and the user screen for no coverage at all.

   Nothing is cleaned up, so everything made here is named uniquely. */

let counter = 0;

function uniqueSuffix() {
	counter += 1;

	const run = Date.now().toString(36);
	const worker = process.env.TEST_WORKER_INDEX ?? '0';

	return `${run}-${worker}-${counter}`;
}

async function askFixture(page, action, params) {
	const query = new URLSearchParams({ action: `seatreg_e2e_${action}`, ...params });

	const response = await page.request.get(`/wp-admin/admin-ajax.php?${query}`);

	expect(
		response.ok(),
		`The e2e fixtures mu-plugin refused ${action}: ${await response.text()}`
	).toBeTruthy();

	return response.json();
}

/**
 * A published post, so a shortcode in it is rendered for whoever opens the address.
 *
 * @return {Promise<{id: number, url: string}>}
 */
function createPost(page, { title, content }) {
	return askFixture(page, 'create_post', { title, content });
}

/**
 * A user of the role named, with an address and a password of their own.
 *
 * @return {Promise<{username: string, password: string}>} How to sign in as them
 */
async function createUser(page, { role, prefix = 'seatreg-e2e' }) {
	const username = `${prefix}-${uniqueSuffix()}`;
	const password = `pw-${uniqueSuffix()}`;

	await askFixture(page, 'create_user', { username, password, role });

	return { username, password };
}

/**
 * Answer the seatreg_room_nouns filter with these words for this registration, the
 * way a translation plugin would. Only the named registration is affected.
 */
function setRoomNouns(page, { code, singular, plural }) {
	return askFixture(page, 'set_room_nouns', { code, singular, plural });
}

/**
 * The same for the word a registration uses for a seat, through seatreg_seat_nouns.
 */
function setSeatNouns(page, { code, singular, plural }) {
	return askFixture(page, 'set_seat_nouns', { code, singular, plural });
}

/**
 * Answer the custom field filters with this text, the way a translation plugin
 * would. Stored under the label or the option it translates, so a caller naming
 * its own is the only one affected.
 */
function setCustomFieldTranslation(page, { text, translation }) {
	return askFixture(page, 'set_custom_field_translation', { text, translation });
}

/**
 * Move a booking back in time by this many minutes, as if it had been made that
 * long ago.
 */
function ageBooking(page, { bookingId, minutes }) {
	return askFixture(page, 'age_booking', { booking_id: bookingId, minutes });
}

/**
 * Run the pending booking expiry job now rather than when WP-Cron gets to it.
 *
 * @return {Promise<{scheduled: boolean}>} Whether the site has the job on its schedule
 */
function runPendingBookingExpiration(page) {
	return askFixture(page, 'run_pending_booking_expiration', {});
}

module.exports = {
	createPost,
	createUser,
	setRoomNouns,
	setSeatNouns,
	setCustomFieldTranslation,
	ageBooking,
	runPendingBookingExpiration,
};
