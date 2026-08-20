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

module.exports = { SEATREG_API_VERSION, endpointUrl, validateToken };
