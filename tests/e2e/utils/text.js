/** For matching a value that carries regular expression punctuation of its own. */
function escapeForRegExp(value) {
	return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

module.exports = { escapeForRegExp };
