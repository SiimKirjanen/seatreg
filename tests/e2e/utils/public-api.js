/* The plugin turns down any API version newer than its own, so this stays at the
   lowest thing that can be asked for. */
const SEATREG_API_VERSION = '1.0.0';

/* Asked for as a query parameter rather than under /wp-json/, which only exists
   on a site with pretty permalinks. */
function endpointUrl(endpoint, params = {}) {
	const query = new URLSearchParams({
		rest_route: `/seatreg/v1/${endpoint}`,
		seatreg_api: SEATREG_API_VERSION,
		...params,
	});

	return `/?${query}`;
}

function validateToken(request, token) {
	return request.get(endpointUrl('validate-token', { api_token: token }));
}

/**
 * The bookings the companion and the Android app read.
 *
 * A calendar date is asked for whether or not the registration runs on one; it
 * is only used by those that do, and left out the endpoint answers 400.
 *
 * @param {string} calendarDate A yyyy-mm-dd date
 */
function bookings(request, token, calendarDate) {
	return request.get(
		endpointUrl('bookings', { api_token: token, calendar_date: calendarDate })
	);
}

module.exports = { SEATREG_API_VERSION, endpointUrl, validateToken, bookings };
