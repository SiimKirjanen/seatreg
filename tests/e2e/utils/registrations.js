/* Registrations are global to the WordPress install and tests do not clean up
   after themselves, so every test works with its own uniquely named registration
   and never asserts on the size or emptiness of the list. */

let counter = 0;

/**
 * A name that cannot collide with another test, another worker, or a previous
 * run - hence the three parts of the suffix.
 *
 * @return {string} e.g. `E2E mgk3x2ab-0-1`
 */
function uniqueRegistrationName(prefix = 'E2E') {
	counter += 1;

	const run = Date.now().toString(36);
	const worker = process.env.TEST_WORKER_INDEX ?? '0';

	return `${prefix} ${run}-${worker}-${counter}`;
}

/* The host and path in front of these depend on the site's permalink settings,
   so tests assert on the query string only. */

function registrationPublicUrlQuery(code) {
	return `?seatreg=registration&c=${code}&page_id=seatreg`;
}

function bookingStatusUrlQuery(code, bookingId) {
	return `?seatreg=booking-status&registration=${code}&id=${bookingId}`;
}

module.exports = { uniqueRegistrationName, registrationPublicUrlQuery, bookingStatusUrlQuery };
