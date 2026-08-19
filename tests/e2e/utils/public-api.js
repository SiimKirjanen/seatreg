/**
 * The plugin's public REST API (seatreg/v1).
 *
 * The one place that knows the API's shape, the way navigation.js is for the
 * admin's URLs. It is the only place the public API setting shows what it does,
 * so a test that covers that setting has to leave the browser and ask.
 */

/* Every request names the version of the API it is asking for: the plugin
   compares it against its own and turns down anything newer than itself. Kept
   at the lowest thing that can be asked for, so the suite does not have to move
   whenever the plugin's version does. */
const SEATREG_API_VERSION = '1.0.0';

/**
 * The address of an endpoint.
 *
 * Asked for as a query parameter instead of under /wp-json/, because that path
 * only exists on a site with pretty permalinks and the suite does not get to
 * decide what the site's are.
 */
function endpointUrl(endpoint, params = {}) {
	const query = new URLSearchParams({
		rest_route: `/seatreg/v1/${endpoint}`,
		seatreg_api: SEATREG_API_VERSION,
		...params,
	});

	return `/?${query}`;
}

/**
 * Ask the API which registration a token belongs to.
 *
 * @param {import('@playwright/test').APIRequestContext} request The `request`
 *                                                               fixture
 * @param {string} token An API token created in the settings
 * @return {Promise<import('@playwright/test').APIResponse>}
 */
function validateToken(request, token) {
	return request.get(endpointUrl('validate-token', { api_token: token }));
}

module.exports = { SEATREG_API_VERSION, endpointUrl, validateToken };
