function setCalendarDateUrlParam(calendarDate) {
	var queryParams = new URLSearchParams(window.location.search);

	queryParams.set('calendar-date', calendarDate);
	window.history.replaceState(null, null, '?' + queryParams.toString());
}

function seatregFormatCalendarDateForDisplay(isoDate, siteLang) {
	if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) {
		return isoDate;
	}
	var parts = isoDate.split('-'); // avoid timezone shift
	var d = new Date(parts[0], parts[1] - 1, parts[2]);
	var locale = (siteLang || 'en').replace('_', '-');
	
	try {
		return new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'long', day: 'numeric' }).format(d);
	} catch (e) {
		return isoDate;
	}
}

function seatregGenerateUUIDv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// Lightweight printf-style replace supporting %s, %d and positional %1$d, %2$s tokens.
function seatregFormat(template, args) {
	if (!args || !args.length) {
		return template;
	}

	return template
		.replace(/%(\d+)\$[ds]/g, function(match, position) {
			return args[parseInt(position, 10) - 1];
		})
		.replace(/%[ds]/g, (function() {
			var index = 0;
			return function() {
				return args[index++];
			};
		})());
}

/**
 * The word a registration uses for a room, in all four forms. The builder swaps registrations
 * without a reload so its nouns arrive with the layout; every other screen reloads and gets
 * them from the localized object.
 * @returns {Object} with singular, plural, singularUpper and pluralUpper
 */
function seatregRoomNouns() {
	if (window.seatreg && window.seatreg.roomNouns) {
		return window.seatreg.roomNouns;
	}

	return WP_Seatreg.room_nouns;
}

/**
 * Repaints the builder chrome, which is rendered once for no registration in particular.
 * A node names the form it wants in data-seatreg-noun and, when the noun sits inside a
 * sentence, that sentence in data-seatreg-noun-template.
 * @param {Object} nouns - as returned by seatregRoomNouns()
 */
function seatregApplyRoomNouns(nouns) {
	var nodes = document.querySelectorAll('[data-seatreg-noun]');

	for (var i = 0; i < nodes.length; i++) {
		var noun = nouns[nodes[i].getAttribute('data-seatreg-noun')];
		var template = nodes[i].getAttribute('data-seatreg-noun-template');

		nodes[i].textContent = template ? seatregFormat(template, [noun]) : noun;
	}
}

/**
 * Creates a translator object for handling WP_Seatreg translations
 * @param {Object} translationsSource - The translations object (usually WP_Seatreg.translations)
 * @returns {Object} Translator with translate method
 */
function createSeatregTranslator(translationsSource) {
    return {
        translate: function(translationKey) {
            if (translationsSource && translationsSource.hasOwnProperty(translationKey)) {
                return translationsSource[translationKey];
            }
            if (console && console.warn) {
                console.warn('Translation key not found:', translationKey);
            }
            return '';
        }
    };
}