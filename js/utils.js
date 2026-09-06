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
 * without a reload so its nouns arrive with the layout, and so does the registration view, which
 * has no localized object; every other screen reloads and gets them from that.
 * @returns {Object} with singular, plural, singularUpper and pluralUpper
 */
function seatregRoomNouns() {
	if (window.seatreg && window.seatreg.roomNouns) {
		return window.seatreg.roomNouns;
	}

	return window.WP_Seatreg && WP_Seatreg.room_nouns;
}

/**
 * The word a registration uses for a seat, in the same four forms and reaching the screen the
 * same two ways.
 * @returns {Object} with singular, plural, singularUpper and pluralUpper
 */
function seatregSeatNouns() {
	if (window.seatreg && window.seatreg.seatNouns) {
		return window.seatreg.seatNouns;
	}

	return window.WP_Seatreg && WP_Seatreg.seat_nouns;
}

/**
 * Repaints the builder chrome, which is rendered once for no registration in particular.
 * A node names the noun it wants in data-seatreg-noun-kind and data-seatreg-noun and, when the
 * noun sits inside a sentence, that sentence in data-seatreg-noun-template. A sentence needing
 * more than one form lists them comma separated, in the order the sentence takes them.
 * data-seatreg-noun-attr writes to that attribute instead of the node's text.
 */
function seatregApplyNouns() {
	var nodes = document.querySelectorAll('[data-seatreg-noun]');
	var nouns = {
		room: seatregRoomNouns(),
		seat: seatregSeatNouns()
	};

	for (var i = 0; i < nodes.length; i++) {
		var kind = nouns[nodes[i].getAttribute('data-seatreg-noun-kind')];

		if (!kind) {
			continue;
		}

		var forms = nodes[i].getAttribute('data-seatreg-noun').split(',');
		var template = nodes[i].getAttribute('data-seatreg-noun-template');
		var attribute = nodes[i].getAttribute('data-seatreg-noun-attr');
		var nounArgs = [];

		for (var j = 0; j < forms.length; j++) {
			nounArgs.push(kind[forms[j]]);
		}

		var text = template ? seatregFormat(template, nounArgs) : nounArgs[0];

		if (attribute) {
			nodes[i].setAttribute(attribute, text);
		} else {
			nodes[i].textContent = text;
		}
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