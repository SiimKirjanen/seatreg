(function($) {
	var translator = {
		translate: function(translationKey) {
			if(window.seatregTranslations && window.seatregTranslations.hasOwnProperty(translationKey)) {
				return window.seatregTranslations[translationKey];
			}
		}
	};

	function capitalizeFirstLetter(string) {
	    return string.charAt(0).toUpperCase() + string.slice(1);
	}

	$('.time').each(function() {
		var date = new Date(parseInt($(this).text()));
		$(this).text(date.format("d.M.Y"));
	});

	var screenWidth = $(window).width();
	var screenHeight = $(window).height();
	var zKeyDown = false;

	var rtime = new Date(1, 1, 2000, 12,00,00);
	var timeout = false;
	var delta = 200;

	var myScroll = null;
	var legendScroll = null;

	var ua = window.navigator.userAgent;
	var msie = ua.indexOf("MSIE ");

	 Object.size = function(obj) {
		var size = 0, key;
		
	    for (key in obj) {
	        if(obj.hasOwnProperty(key)){
	        	size++;
	        } 
	    }
	    return size;
	};

	var qs = (function(a) {
	    if (a == "") return {};
	    var b = {};
	    for (var i = 0; i < a.length; ++i)
	    {
	        var p=a[i].split('=');
	        if (p.length != 2) continue;
	        b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
	    }
	    return b;
	})(window.location.search.substr(1).split('&'));

	function deepCopyObject(object) {
		return JSON.parse(JSON.stringify(object))
	}

	function getCurrencySymbolFromISO(isoCode) {
		try {
			const currencyFormatter = new Intl.NumberFormat(undefined, {
				style: 'currency',
				currency: isoCode,
				currencyDisplay: 'symbol'
			});
	
			const exampleValue = 0;
			const formattedValue = currencyFormatter.format(exampleValue);
	
			const currencySymbol = formattedValue.replace(/[\d.,\s]/g, '').trim();

			return currencySymbol;
		} catch (error) {
			console.error(error);
			return isoCode;
		}
	}

	$(document).keydown(function(e){
        if(e.which === 90){
        	zKeyDown = true;
        } 
    });

    $(document).keyup(function(e){
        if(e.which === 90){
        	zKeyDown = false;
        } 
    });

	function SeatReg() {
		this.rooms = (window.dataReg !== null) ? deepCopyObject(window.dataReg.roomData) : null;
		this.seatLimit = seatLimit;
		this.currentRoom = 0;
		this.css3 = false;
		this.ie8 = false;
		this.mobileview = true;
		this.bronSeats = null;
		this.openSeats = null;
		this.seatsTotal = null;
		this.takenSeats = null;
		this.locationObj = {};
		this.selectedSeats = [];
		this.customF = [];
		this.gmailNeeded = gmail;
		this.NotifyBookerPendingBooking = NotifyBookerPendingBooking;
		this.status = regTime;
		this.timeRestrictions = registrationTimeRestrictions;
		this.emailConfirmEnabled = window.emailConfirmRequired === '1' ? true: false;
		this.payPalEnabled = window.payPalEnabled === '1' ? true : false;
		this.stripeEnabled = window.stripeEnabled === '1' ? true : false;
		this.customPaymentEnabled = window.customPaymentEnabled === '1' ? true : false;
		this.payPalCurrencyCode = window.payPalCurrencyCode;
		this.enteredSeatPasswords = {};
		this.usingSeats = usingSeats === '1';
		this.usingCalendar = window.usingCalendar === '1' ? true : false;
		this.enabledCalendarDates = window.calendarDates ? window.calendarDates.split(',') : [];
		this.spotName =  this.usingSeats ? translator.translate('seat') : translator.translate('place');
		this.activeCalendarDate = window.activeCalendarDate;
		this.siteLanguage = window.siteLanguage;
		this.controlledScrollEnabled = window.controlledScroll === '1';
		this.customFooterText = customFooterText;
		this.bookingRedirectToStatusPage = window.bookingRedirectToStatusPage === '1';
		this.requireName = requireName === '1';
		this.requireWPLogin = window.requireWPLogin === '1';
		this.isLoggedIn = window.isLoggedIn === '1';
		this.onePersonCheckout = window.onePersonCheckout === '1';
		this.automaticBookingConfirmDialog = window.automaticBookingConfirmDialog === '1';
	}

	function CartItem(id, nr, room, roomUUID, price, multiPriceUUID) {
		this.id = id;
		this.nr = nr;
		this.room = room;
		this.roomUUID = roomUUID;
		this.defFields = ['FirstName','LastName','Email'];
		this.customFields = [];
		this.price = price;
		this.multiPriceUUID = multiPriceUUID;
	};

	SeatReg.prototype.isPaymentEnabled = function() {
		return this.payPalEnabled || this.stripeEnabled || this.customPaymentEnabled;
	};

	SeatReg.prototype.browserInfoDetection = function() {
		//set browser related info.

		if( $('html').hasClass('csstransitions') ) {
			this.css3 = true;
		}else {
			$('#zoom-controller').css({'display': 'none'});
		}

		if(!document.addEventListener ){
	        this.ie8 = true;
	        $('#box-wrap').css('overflow','auto'); 
		}
		
	    if(screenWidth >= 1024) {
	    	this.mobileview = false;
	    }
	};

	SeatReg.prototype.fillLocationObj = function() {
		//where is room located
		var roomsLength = this.rooms.length;

		for(var i = 0; i < roomsLength; i++) {
			this.locationObj[this.rooms[i].room.uuid] = i;
		}
	};

	SeatReg.prototype.fillCustom = function(customs) {
		this.customF = customs;
	};

	SeatReg.prototype.buildRegistration = function() {
		//add roomsInfo to seatReg
		this.bronSeats = roomsInfo.bronSeats;
		this.openSeats = roomsInfo.openSeats;
		this.seatsTotal = roomsInfo.seatsTotal;
		this.takenSeats = roomsInfo.takenSeats;

		for (var property in roomsInfo.roomsInfo) {
			if (roomsInfo.roomsInfo.hasOwnProperty(property)) {
				var roomLoc = this.locationObj[roomsInfo.roomsInfo[property].roomUuid];

				this.rooms[roomLoc].room['roomBronSeats'] = roomsInfo.roomsInfo[property].roomBronSeats;
				this.rooms[roomLoc].room['roomCustomBoxes'] = roomsInfo.roomsInfo[property].roomCustomBoxes;
				this.rooms[roomLoc].room['roomOpenSeats'] = roomsInfo.roomsInfo[property].roomOpenSeats;
				this.rooms[roomLoc].room['roomSeatsTotal'] = roomsInfo.roomsInfo[property].roomSeatsTotal;
				this.rooms[roomLoc].room['roomTakenSeats'] = roomsInfo.roomsInfo[property].roomTakenSeats;	 
			}
		}

		//adding registrations
		var reLength = Object.size(window.registrations);

		for(var i = 0; i < reLength; i++) {
			var customFieldData = window.registrations[i].hasOwnProperty('custom_field_data') ? window.registrations[i]['custom_field_data'] : '[]';
			var bookingFullName = window.registrations[i].hasOwnProperty('reg_name') ? window.registrations[i]['reg_name'] : null;

			seatReg.markBookingToRegistration(window.registrations[i]['seat_id'], window.registrations[i]['room_uuid'], window.registrations[i]['status'], bookingFullName, customFieldData);
		}
		if(custF != null) {
			seatReg.fillCustom(custF);
		}

		//fill extra info
		$('.total-rooms').text(roomsInfo.roomCount);
		$('.total-open').text(roomsInfo.openSeats);
		$('.total-bron').text(roomsInfo.bronSeats);
		$('.total-tak').text(roomsInfo.takenSeats);
		
		if(this.mobileview) {
			seatReg.paintRoomsNav();
			seatReg.paintRoomInfo();
			setMiddleSecSize(seatReg.rooms[seatReg.currentRoom].room.width, seatReg.rooms[seatReg.currentRoom].room.height);
			seatReg.paintRoomLegends();
			seatReg.paintRoom();
		}else {
			seatReg.paintRoomsNav();
			seatReg.paintRoomInfo();
			seatReg.paintRoomLegends();
			setMiddleSecSize(seatReg.rooms[seatReg.currentRoom].room.width, seatReg.rooms[seatReg.currentRoom].room.height);
			seatReg.paintRoom();
		}
	};

	SeatReg.prototype.init = function() {
		this.browserInfoDetection();

		if(this.rooms === null) {
			$('body').append('<div class="under-construction-notify"><span class="icon-construction6 index-icon"></span>'+ translator.translate('_regUnderConstruction') +'</div>');
		}else {
			this.fillLocationObj();

			if( this.usingCalendar && !this.isSelectedCalendarDateAvalidable() ) {
				this.paintRegistrationMessage(translator.translate('closedPleaseChooseNewDate'));
			}else {
				this.buildRegistration();
			}
			
			this.initCalendar();
		} 
	};

	SeatReg.prototype.isSelectedCalendarDateAvalidable = function() {
		if(this.enabledCalendarDates.length) {
			return this.enabledCalendarDates.includes(this.activeCalendarDate);
		}

		return true;
	};

	SeatReg.prototype.initCalendar = function() {
		if( this.usingCalendar ) {
			var seatregScope = this;
			$('#calendar-date').text(this.formatCalendarDateForDisplay(this.activeCalendarDate));

			$('#calendar-date-selection').pignoseCalendar({
				modal: true,
				theme: 'blue',
				format: 'YYYY-MM-DD',
				date: this.activeCalendarDate,
				buttons: true,
				enabledDates: this.enabledCalendarDates,
				lang: this.siteLanguage,
				apply: function(date, context) {
					if(date && date[0] !== null) {
						seatregScope.calendarDateChange( date[0].format('YYYY-MM-DD') );
					}
				}
			});
		}
	};

	SeatReg.prototype.formatCalendarDateForDisplay = function(isoDate) {
		if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) {
			return isoDate;
		}
		var parts = isoDate.split('-'); // avoid timezone shift
		var d = new Date(parts[0], parts[1] - 1, parts[2]);
		var locale = (this.siteLanguage || 'en').replace('_', '-');
		
		try {
			return new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'long', day: 'numeric' }).format(d);
		} catch (e) {
			return isoDate;
		}
	};

	SeatReg.prototype.calendarDateChange = function( selectedCalendarDate ) {
		this.activeCalendarDate = selectedCalendarDate;
		$('#calendar-date').text(this.formatCalendarDateForDisplay(this.activeCalendarDate));
		setCalendarDateUrlParam(this.activeCalendarDate);
		this.hideRegistrationMessage();
		this.fetchBookings();
	};

	SeatReg.prototype.fetchBookings = function() {
		var scope = this;
		$('#modal-bg').css('display','block');
		$('#calendar-date-change-loading').css('display', 'block');

		$.ajax({
			type: 'GET',
			url: window.ajaxUrl,
			data: {
				date: this.activeCalendarDate,
				'registration-code': getRegistrationCode(),
				action: 'seatreg_fetch_bookings_and_info'
			},
			success: function(response) {
				$('#calendar-date-change-loading').css('display', 'none');
				$('#modal-bg').css('display','none');
				window.registrations = response.bookings;
				window.roomsInfo = jQuery.parseJSON(response.roomsBookingInfo);

				scope.rooms = (window.dataReg !== null) ? deepCopyObject(window.dataReg.roomData) : null;
				scope.clearCart();
				scope.buildRegistration();
			},
			error: function() {
				$('#calendar-date-change-loading').css('display', 'none');
				$('#modal-bg').css('display','none');
				showErrorAlertDialog(translator.translate('somethingWentWrong'));
			}
		});
	}

	SeatReg.prototype.getRoomNameFromLayout = function(roomUUID) {
		var roomsLength = this.rooms.length;
		var roomName = null;

		for(var i = 0; i < roomsLength; i++) {
			
			if(this.rooms[i].room.uuid === roomUUID) {
				roomName = this.rooms[i].room.name;
				break;
			}
		}

		return roomName;
	};

	SeatReg.prototype.markBookingToRegistration = function(seatId, roomUuid, status, registrantName, customFieldData) {
		var roomLocation = this.locationObj[roomUuid];
		var boxesLen = this.rooms[roomLocation].boxes.length;

		for(var j = 0; j < boxesLen; j++) {
			if(this.rooms[roomLocation].boxes[j].id == seatId) {
				if(status == 1) {
					this.rooms[roomLocation].boxes[j].status = 'bronRegister';
				}else {
					this.rooms[roomLocation].boxes[j].status = 'takenRegister';
				}

				if(registrantName != null) {	//need to show name
					this.rooms[roomLocation].boxes[j].registrantName = registrantName;
				}
				this.rooms[roomLocation].boxes[j].customFieldData = JSON.parse(customFieldData);

				break;
			}
		}
	};

	SeatReg.prototype.paintRoom = function() {
		//paint room boxes and add listeners
		var documentFragment = document.createDocumentFragment();
		var loc = this.rooms[this.currentRoom].boxes;
		var boxLength = loc.length;
		var scope = this;
		var boxWrap = document.getElementById("box-wrap");
		var roomIsEmptyWrap = document.getElementById('room-is-empty');
		
		boxWrap.classList.remove("dont-display");
		roomIsEmptyWrap.classList.add('dont-display');

		if(boxLength === 0) {
			boxWrap.classList.add("dont-display");
			roomIsEmptyWrap.classList.remove('dont-display');
		}

		for(var i = 0; i < boxLength; i++) {
			var box = document.createElement('div');
			box.className = "box";
			var tooltipContent = '';
			var legend = loc[i].legend;
			var clickable = false;

			box.style.top = loc[i].yPosition + 'px';
			box.style.left = loc[i].xPosition + 'px';
			box.style.backgroundColor = loc[i].color;
			box.style.zIndex = loc[i].zIndex;
			box.style.width = loc[i].width + 'px';
			box.style.height = loc[i].height + 'px';

			if(loc[i].type === 'text-box') {
				box.className = box.className + ' text-box';
				box.style.color = loc[i].fontColor;
				box.style.fontSize = loc[i].inputSize + 'px';
				box.style.pointerEvents = 'none';
				box.innerHTML = loc[i].input;
			}

			if(legend !== 'noLegend') {
				box.setAttribute('data-legend',legend);
				box.setAttribute('data-leg',legend.replace(/\s+/g, '_').toLowerCase());
				box.classList.add( sanitizeClassName( legend ) );
				tooltipContent = '<div class="seatreg-tooltip-row">' + legend + '</div>';
				clickable = true;
			}

			if(loc[i].canRegister == "true") {
				var prefix = loc[i].hasOwnProperty('prefix') ? loc[i].prefix : '';
				box.setAttribute('data-seat', loc[i].id);
				box.setAttribute('data-seat-nr', loc[i].seat);
				box.setAttribute('data-seat-prefix', prefix);
				var number = document.createElement('div');
				
				number.className = "seat-number";
				var newContent = document.createTextNode(prefix + loc[i].seat);
				number.appendChild(newContent);
				box.appendChild(number);
				clickable = true;
			}

			if(loc[i].hoverText !== "nohover") {
				tooltipContent += '<div class="seatreg-tooltip-row">' + loc[i].hoverText.replace(/\^/g,'<br>') + '</div>';
				box.className = box.className +' bubble-text';
				clickable = true;

				var commentIcon = document.createElement('i');
				commentIcon.className = ' fa fa-comment-o comment-icon';
				box.appendChild(commentIcon);
			}

			if(loc[i].status !== "noStatus") {
				if(loc[i].status == "bronRegister") {
					box.setAttribute('data-status','bron');
					var bronSign = document.createElement('div');
					bronSign.className = "bron-sign";
					tooltipContent += '<div class="seatreg-tooltip-row">' + translator.translate('Pending') + '</div>';
					box.appendChild(bronSign);
				}else if(loc[i].status == "takenRegister") {
					box.setAttribute('data-status','tak');
					var takSign = document.createElement('div');
					takSign.className = "taken-sign";
					tooltipContent += '<div class="seatreg-tooltip-row">' + translator.translate('Booked') + '</div>';
					box.appendChild(takSign);
				}
				clickable = true;
			}

			if(loc[i].hasOwnProperty('registrantName')) {
				tooltipContent += '<div class="seatreg-tooltip-row">' + loc[i].registrantName + '</div>';
			}

			if(loc[i].hasOwnProperty('customFieldData')) {
				this.customF.forEach(function(createdCustomField) {
					var userEnteredCustomFieldData = loc[i].customFieldData.find(function(c) {
						return c.label === createdCustomField.label;
					});

					if(userEnteredCustomFieldData) {
						var customFieldValue = userEnteredCustomFieldData.value;

						if(createdCustomField.type === 'check') {
							customFieldValue = customFieldValue === '1' ? translator.translate('yes') : translator.translate('no');
						}	

						tooltipContent += '<div class="seatreg-tooltip-row">' + userEnteredCustomFieldData.label + ': ' + customFieldValue + '</div>';
					}
				});
			}

			if(loc[i].hasOwnProperty('price')) {
				if( Array.isArray(loc[i].price) ) {
					box.setAttribute('data-price', JSON.stringify(loc[i].price));
				}else {
					box.setAttribute('data-price', loc[i].price);
				}
			}

			if(loc[i].hasOwnProperty('lock')) {
				box.setAttribute('data-lock', loc[i].lock);
			}

			if(loc[i].hasOwnProperty('password')) {
				box.setAttribute('data-password', loc[i].password);
			}

			if(tooltipContent) {
				box.setAttribute('data-powertip', tooltipContent);
			}

			if(clickable) {
				box.className = box.className + ' cursor';
			}
					
			if (!this.ie8){
				box.addEventListener('touchstart',function(e) {
					e.preventDefault();
					scope.openSeatDialog(this);
				});
				box.addEventListener('click',function() {
					scope.openSeatDialog(this);
				});
			}else{
				//IE
				box.attachEvent('onclick',function(evt) {
					var evt = evt || window.event;
					var target = evt.target || evt.srcElement;
					scope.openSeatDialog(target);
				});
			}
			documentFragment.appendChild(box);
		}

		//check if seat is in cart
		var arrLen = this.selectedSeats.length;
		var roomName = this.rooms[this.currentRoom].room.name;

		for(var i = 0; i < arrLen; i++) {
			if(this.selectedSeats[i].room == roomName) {
				var selectedSeatId = this.selectedSeats[i].id;
				var selectedElement = documentFragment.querySelector('.box[data-seat="' + selectedSeatId + '"]');

				if(selectedElement) {
					selectedElement.setAttribute('data-selectedbox','true');
					selectedElement.classList.add('selected-box'); 
				}
			}
		}
		$('#boxes').html(documentFragment);

		if(this.rooms[this.currentRoom].room.backgroundImage !== null && this.rooms[this.currentRoom].room.backgroundImage.indexOf('.') !== -1) {  //dose room have a background image?
			$('#boxes').append('<img class="room-image" src="' + WP_Seatreg.uploads_url + '/room_images/' + qs['c'] + '/' + this.rooms[this.currentRoom].room.backgroundImage + '" />');
		}

		$('#boxes .box[data-powertip]').powerTip({
			followMouse: true,
			fadeInTime: 0,
			fadeOutTime:0,
			intentPollInterval: 10,
		});
	};

SeatReg.prototype.paintRoomInfo = function() {
	//room-nav-info
	$('#current-room-name').text(this.rooms[this.currentRoom].room.name);
	var infoLoc = this.rooms[this.currentRoom].room;
	var documentFragment = $(document.createDocumentFragment());
	var text = this.usingSeats ? translator.translate('openSeatsInRoom_') : translator.translate('openPlacesInRoom_');

	documentFragment.append(
		'<div class="info-item open-seats">' + 
		'<span>' + 
		text +
		'</span>' + 
		infoLoc.roomOpenSeats + 
		'</div>', 
		'<div class="info-item"><span class="bron-legend"></span> <span>'+ translator.translate('pendingSeatInRoom_') +'</span>' + infoLoc.roomBronSeats +'</div>', '<div class="info-item"><span class="tak-legend"></span> <span>'+ translator.translate('confirmedSeatInRoom_') +'</span>' + infoLoc.roomTakenSeats +'</div>');

	$('#room-nav-info-inner').html(documentFragment);
};

SeatReg.prototype.paintRoomLegends = function() {
	//paint legend boxes
	var legendsCount = this.rooms[this.currentRoom].room.legends.length;

	$('#legends').empty();

	if(legendsCount > 0) {
		if(this.mobileview) {
			$('#legend-wrapper').css('display','none');
			$('.mobile-legend').css('display','inline-block');
		}else {
			$('#legend-wrapper').css('display','inline-block');
		}
	}else {
		$('#legend-wrapper, .mobile-legend').css('display','none');
	}

	var documentFragment = $(document.createDocumentFragment());

	for(var i = 0; i < legendsCount; i++) {
		documentFragment.append($('<div class="legend-div" data-target-legend='+ this.rooms[this.currentRoom].room.legends[i].text.replace(/\s+/g, '_').toLowerCase() +'></div>').append('<div class="legend-box" style="background-color:'+ this.rooms[this.currentRoom].room.legends[i].color +'"></div>', '<div class="legend-name">'+ this.rooms[this.currentRoom].room.legends[i].text +'</div>'));
	}

	$('#legends').append(documentFragment);
	$('#legends .legend-div').on('click', function() {
		var clickLegend = $(this).data('target-legend');
		var legendBoxes = $('#boxes .box[data-leg='+ clickLegend +']');

		legendBoxes.css("--animationColor", legendBoxes.css('background-color'));
		legendBoxes.one('webkitAnimationEnd oanimationend msAnimationEnd animationend', function() {
			$(this).removeClass('legend-animation');
		});
		legendBoxes.addClass('legend-animation');
	});

	initLegendsScroll();
};

SeatReg.prototype.paintRoomsNav = function() {
	var roomsLength = this.rooms.length;
	var documentFragment = $(document.createDocumentFragment());
	var scope = this;

	for(var i = 0; i < roomsLength; i++) {
		var roomName = this.rooms[i].room.name;
		var navItem = $('<div>', {
			'class': 'room-nav-link',
			'data-open': this.rooms[i].room.uuid
		}).html(roomName).on('click', function() {
			scope.roomChange($(this).attr('data-open'));
		});

		if(seatReg.currentRoom == i) {
			navItem.addClass('active-nav-link');
		}

		navItem.appendTo(documentFragment);
	}
	$('#room-nav-items').html(documentFragment);
};

SeatReg.prototype.paintRegistrationMessage = function(text) {
	var $messageWrap = $('#registration-message');

	$messageWrap.find('p').text(text);
	$messageWrap.removeClass('dont-display');
};
SeatReg.prototype.hideRegistrationMessage = function() {
	$('#registration-message').addClass('dont-display');
}

SeatReg.prototype.roomChange = function(roomUUID) {
	$('#room-nav').removeClass('modal');
	$('#modal-bg').css('display','none');
	
	this.currentRoom = this.locationObj[roomUUID];

	if(myScroll != null) {
		myScroll.destroy();
		myScroll = null;
	}
	if(legendScroll != null) {
		legendScroll.destroy();
		legendScroll= null;
	}

	$('#room-nav-items .active-nav-link').removeClass('active-nav-link');
	$('#room-nav-items').find('.room-nav-link[data-open=' + roomUUID +']').addClass('active-nav-link');

	$('#boxes').empty();	//clear boxes
	$('#legends').empty();	//clear legends

	if(this.mobileview <= 1024) {
		this.paintRoomLegends();
		this.paintRoomInfo();
		setMiddleSecSize(this.rooms[this.currentRoom].room.width, this.rooms[this.currentRoom].room.height);
	}else {
		this.paintRoomLegends();
		this.paintRoomInfo();
		setMiddleSecSize(this.rooms[this.currentRoom].room.width, this.rooms[this.currentRoom].room.height);
	}
	
	this.paintRoom();
};

SeatReg.prototype.clearCart = function() {
	this.selectedSeats = [];

	$('#boxes .box').removeAttr('data-selectedbox');
	$('#booking-total-price').empty().attr('data-booking-price', 0);
	$('#seat-cart-items').empty();
	$('.seats-in-cart').text(0);

};

SeatReg.prototype.addSeatToCart = function() {
	//adding selected seat to seat cart
	var seatId = document.getElementById('selected-seat').value;
	var seatNr = document.getElementById('selected-seat-nr').value;
	var roomName = document.getElementById('selected-seat-room').value;
	var roomUUID = document.getElementById('selected-room-uuid').value;
	var price = parseInt(document.getElementById('selected-seat-price').value);
	var multiPriceUUID = document.getElementById('selected-multi-price-uuid').value;
	var scope = this;
	this.selectedSeats.push(new CartItem(seatId, seatNr, roomName, roomUUID, price, multiPriceUUID));
	
	$('.seats-in-cart').text(this.selectedSeats.length);
	var boxColor = $('#boxes .box[data-seat="' + seatId + '"]').css('background-color');
	$('#boxes .box[data-seat="' + seatId + '"]').attr('data-selectedBox','true').css("--animationColor", boxColor).addClass('selected-box');

	//add to seat cart popup
	var cartItem = $('<div class="cart-item" data-cart-id="' + seatId + '" data-room-uuid="'+ roomUUID +'"></div>');
	var seatNumberDiv = $('<div class="cart-item-nr">' + seatNr + '</div>');
	var roomNameDiv = $('<div class="cart-item-room">' + roomName + '</div>');
	var delItem = $('<div class="remove-cart-item"><i class="fa fa-times-circle"></i><span style="padding-left:4px">'+ translator.translate('remove') +'</span></div>').on('click', function() {
		var item = $(this).closest('.cart-item');
		var removeId = item.attr('data-cart-id');
		var priceToRemove = 0;
		var arrLen = scope.selectedSeats.length;

		for(var i = 0; i < arrLen; i++) {
			if(scope.selectedSeats[i].id == removeId) {
				priceToRemove = scope.selectedSeats[i].price;
				scope.selectedSeats.splice(i, 1);

				break;
			}
		}
		item.remove();
		$('#boxes .box[data-seat="'+ removeId +'"]').removeAttr('data-selectedbox').removeClass('selected-box');

		if(scope.selectedSeats.length == 0) {
			$('#seat-cart-info').html('<h3>'+ translator.translate('selectionIsEmpty') +'</h3><p>' + translator.translate('youCanAdd_') + scope.spotName + translator.translate('_toCartClickTab') + '</p>');
			$('#checkout').css('display','none');
			$('#seat-cart-rows').css('display','none');
			$('#booking-total-price').empty().attr('data-booking-price', 0);
		}else {
			var selected = scope.selectedSeats.length;
			var infoText;

			if( selected > 1 ) {
				infoText = selected + (this.usingSeats ? translator.translate('_seatsSelected') : translator.translate('_placesSelected') );
			}else {
				infoText = selected + (this.usingSeats ? translator.translate('_seatSelected') : translator.translate('_placeSelected') );
			}
			$('#seat-cart-info').text(infoText);
			var totalPrice = scope.selectedSeats.reduce(function(accumulator, currentValue) {
				return currentValue.price + accumulator;
			}, 0);
			$('#booking-total-price').text( translator.translate('bookingTotalCostIs_') + getCurrencySymbolFromISO(scope.payPalCurrencyCode) + totalPrice);
			$('#booking-total-price').attr('data-booking-price', totalPrice);
		}
		$('.seats-in-cart').text(scope.selectedSeats.length);
	});

	cartItem.append(seatNumberDiv, roomNameDiv, delItem);
	$('#seat-cart-items').append(cartItem);
	
	var totalPrice = scope.selectedSeats.reduce(function(accumulator, currentValue) {
		return currentValue.price + accumulator;
	}, 0);

	$('#booking-total-price').text( translator.translate('bookingTotalCostIs_') + getCurrencySymbolFromISO(scope.payPalCurrencyCode) + totalPrice);
	$('#booking-total-price').attr('data-booking-price', totalPrice);

	this.closeSeatDialog();
};

SeatReg.prototype.hasFailedTimeRestrictions = function() {
	return !this.timeRestrictions.registrationStartCheck || !this.timeRestrictions.registrationEndCheck; 
}

SeatReg.prototype.openSeatCart = function() {
	var selected = this.selectedSeats.length;
	var cartHeaderText = this.usingSeats ? translator.translate('selectionIsEmpty') : translator.translate('selectionIsEmptyPlace');
	var cartEmptyText = this.usingSeats ? translator.translate('selectingGuide') : translator.translate('selectingGuidePlace');

	if(selected == 0) {	
		if(this.requireWPLogin && !this.isLoggedIn) {
			$('#seat-cart-info').html('<h3>'+ translator.translate('wpLoginRequired') +'</h3>');
		}else if( this.status == 'run' && !this.hasFailedTimeRestrictions() ) {
			$('#seat-cart-info').html('<h3>'+ cartHeaderText +'</h3><p>' + cartEmptyText + '</p>');
			$('#checkout').css('display','none');
			$('#seat-cart-rows').css('display','none');
		}else {
			$('#seat-cart-info').html('<h3>'+ translator.translate('regClosedAtMoment') +'</h3>');
		}

	}else {
		$('#seat-cart-rows').css('display','block');
		var infoText;

		if(selected > 1) {
			infoText = selected + ( this.usingSeats ? translator.translate('_seatsSelected') : translator.translate('_placesSelected') );
		}else {
			infoText = selected + translator.translate('_seatSelected');
			infoText = selected + ( this.usingSeats ? translator.translate('_seatSelected') : translator.translate('_placeSelected') );
		}
		$('#seat-cart-info').text(infoText);
		$('#checkout').css('display','inline-block');
	}

	$('#seat-cart-popup .cart-popup-inner').addClass('zoomIn');
	$('#seat-cart-popup').css('display','block');
	$('#modal-bg').css('display','block');
};

SeatReg.prototype.closeSeatCart = function() {
	$('#seat-cart-popup .cart-popup-inner').removeClass('zoomIn');
	$('#seat-cart-popup').css('display','none');
	$('#modal-bg').css('display','none');
};

SeatReg.prototype.openCheckOut = function() {
	var arrLen = this.selectedSeats.length;

	if(arrLen == 0) {
		return;
	}

	$('#seat-cart-popup').css('display','none');
	this.generateCheckout(arrLen);
	$('#checkout-area').css('display','block');
	$('#modal-bg').css('display','block');
};

SeatReg.prototype.openInfo = function() {
	$('#modal-bg').css('display','block');
	$('#extra-info').css('display','block');
};

SeatReg.prototype.closeCheckOut = function() {
	$('#checkout-area').css('display','none');
	$('#modal-bg').css('display','none');
	$('#request-error').text('').css('display', 'none');	
};

SeatReg.prototype.generateCheckout = function(arrLen) {
	$('#checkout-input-area').empty();
	var documentFragment = $(document.createDocumentFragment());
	var arrLen3 = this.customF.length;
	var hasMultipleSeatsSelected = arrLen > 1;
    var checkItemCounter = 1;
	if( hasMultipleSeatsSelected && !this.onePersonCheckout ) {
		$('#checkout-area .checkout-settings').removeClass('display-none');
	}

	for(var i = 0; i < arrLen; i++) {
		var checkItem = $('<div class="check-item"></div>');
		var checkItemHeader = $('<div class="check-item-head">'+ this.spotName +' No. <span>' + this.selectedSeats[i].nr + '</span><br><span>' + this.selectedSeats[i].room + '</span></div>');
		var documentFragment2 = $(document.createDocumentFragment());
		var arrLen2 = this.selectedSeats[i].defFields.length;
		var isLastCheckItem = i === arrLen - 1 || this.onePersonCheckout;

		if( this.onePersonCheckout ) {
			checkItemHeader = null;

			$('#checkoput-area-inner .checkout-settings__copy input').attr('checked', true);
		}

		if (checkItemCounter > 1 && this.onePersonCheckout) {
			checkItem.css('display', 'none');
		}

		if( isLastCheckItem ) {
			checkItem.addClass('check-item--last');
		}

		if (!this.requireName)
		{
			var firstName = '<input type="hidden" name="FirstName[]" data-field="FirstName">';
			var lastName = '<input type="hidden" name="LastName[]" data-field="LastName">';
			var email = this.generateField(this.selectedSeats[i].defFields[2], isLastCheckItem);
			documentFragment2.append(firstName, lastName, email);
		}
		else
		{
			for(var j = 0; j < arrLen2; j++) {
				var field = this.generateField(this.selectedSeats[i].defFields[j], isLastCheckItem);
				documentFragment2.append(field);
			}
		}

		for(var j = 0; j < arrLen3; j++) {
			var field = this.generateCustomField(this.customF[j]);
			documentFragment2.append(field);
		}

		var seatId = $('<input type="hidden" class="item-id" name="item-id[]" value="' + this.selectedSeats[i].id + '" />');
		var seatNr = $('<input type="hidden" class="item-nr" name="item-nr[]" value="' + this.selectedSeats[i].nr + '" />');
		var roomUUID = $('<input type="hidden" name="room-uuid[]" value="' + this.selectedSeats[i].roomUUID + '" />');
		var multiPriceUUID = $('<input type="hidden" name="multi-price-uuid[]" value="' + this.selectedSeats[i].multiPriceUUID + '" />');
		var selectedCalendarDate = null;

		if( this.usingCalendar ) {
			selectedCalendarDate = $('<input type="hidden" name="selected-calendar-date" value="' + this.activeCalendarDate + '" />');
		}

		checkItem.append(checkItemHeader, documentFragment2, seatId, seatNr, roomUUID, multiPriceUUID, selectedCalendarDate);
		documentFragment.append(checkItem);
		checkItemCounter++;
	}

	if(arrLen > 1 && !this.onePersonCheckout) {
		if(this.gmailNeeded == 1) {
			var primaryMail = $('<div style="text-align:center;margin-top:16px"><label class="field-label">'+ translator.translate('confWillBeSentTogmail') +'</br> <input type="text" id="prim-mail" class="field-input" data-field="Email"><span class="field-error"></span></label></div>');
		}else {
			console.log('Gmail not needed!');
			var primaryMail = $('<div style="text-align:center;margin-top:16px"><label class="field-label">'+ translator.translate('confWillBeSentTo') +'</br> <input type="text" id="prim-mail" class="field-input" data-field="Email"><span class="field-error"></span></label></div>');
		}
		documentFragment.append(primaryMail);
	}
	
    //Custom footer text field
	var field = this.generateCustomFooterFextField(this.customFooterText);
	documentFragment.append(field);

	$('#checkout-input-area').append(documentFragment);

	if(arrLen == 1 && this.gmailNeeded == 1) {
		$('#checkout-input-area .field-input[data-field="Email"]').prev().text('Email (Gmail required)');
	}
};

SeatReg.prototype.generateField = function(fieldName, isLastCheckItem) {
	var fieldText;
	switch(fieldName) {
		case 'FirstName':
			fieldText = translator.translate('firstName');
			break;
		case 'LastName':
			fieldText = translator.translate('lastName');
			break;
		case 'Email':
			fieldText = translator.translate('eMail');
			break;
	}

	var label = $('<label class="field-label"><span class="l-text">' + fieldText + '</span></label>');
	var fieldInput = $('<div style="position:relative"><input type="text" name="'+ fieldName +'[]" class="field-input" data-field="' + fieldName+ '" maxlength="100"></div>');

	if( !isLastCheckItem ) {
		fieldInput.append('<i class="fa fa-arrow-circle-right check-item__copy" aria-hidden="true"></i>');
	}
	
	var errorText = $('<span class="field-error">error</span>');
	label.append(fieldInput, errorText);
	return label;
};

SeatReg.prototype.generateCustomFooterFextField = function (customFooterText) {
	var decodedcustomFooterText = $("<div/>").html(customFooterText).text();
	var div = $('<div class="field-label custom-footer-text">'+ decodedcustomFooterText + '</div>');
	div.find('script').remove();
	return div;
};

SeatReg.prototype.generateCustomField = function(custom) {
	var label = $('<label class="field-label custom-input" data-label="' + custom.label + '"><span class="l-text">' + custom.label +  '</span></label>');

	if(custom.type == 'text') {
		var optional = custom.optional === true ? true : false;
		var fieldInput = $('<input type="text" name="'+ custom.label +'[]" class="field-input" data-field="' + custom.label + '" data-type="' +  custom.type +'" data-optional="' +  optional +'" maxlength="'+ WP_Seatreg.SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH +'">');
	}else if(custom.type == 'check') {
		var fieldInput = $('<input type="checkbox" name="'+ custom.label +'[]" class="field-input" data-field="' + custom.label + '" data-type="' +  custom.type + '" value="'+ custom.label +'">');
	}else if(custom.type == 'sel') {
		var fieldInput = $('<select name="'+ custom.label +'[]" class="field-input" data-type="' + custom.type + '" data-field="' + custom.label + '"></select>');
		var arrLen = custom.options.length;

		for(var i = 0; i < arrLen; i++) {
			fieldInput.append('<option value="'+ custom.options[i] +'">' + custom.options[i] + '</option>');
		}
	}

	var errorText = $('<span class="field-error">error</span>');
	label.append($('<div>').append(fieldInput),errorText);
	return label;
};

SeatReg.prototype.openModel = function() {
	$('#modal-bg').css('display','block');
	$('#room-nav').addClass('modal');
};

SeatReg.prototype.closeModal = function() {
	$('#modal-bg').css('display','none');
	$('#room-nav').removeClass('modal');
};

SeatReg.prototype.openSeatDialog = function(clickBox) {
	var openDialog = this.paintSeatDialog(clickBox);

	if(openDialog) {
		$('#modal-bg').css('display','block');
		$('#confirm-dialog-mob').css('display','block');
		$('#confirm-dialog-mob-inner').removeClass('zoomOut').addClass('zoomIn');
	}
};

SeatReg.prototype.closeSeatDialog = function() {
	$('#confirm-dialog-mob').css('display','none');
	$('#modal-bg').css('display','none');
};

SeatReg.prototype.paintSeatDialog = function(clickBox) {
	$('#confirm-dialog-mob-hover, #confirm-dialog-mob-legend').empty().css('display','none');
	$('#confirm-dialog-mob-text, #confirm-dialog-bottom').empty();

	var hover = null;
	var legend = null;
	var nr = clickBox.getAttribute('data-seat-nr');
	var seatPrefix = clickBox.getAttribute('data-seat-prefix');
	var seatId = clickBox.getAttribute('data-seat'); 
	var isLocked = clickBox.getAttribute('data-lock') === "true";
	var passwordNeeded = clickBox.getAttribute('data-password') === "true";
	var type = 'box';
	var currentRoom = this.rooms[this.currentRoom].room;
	var room = this.rooms[this.currentRoom].room.name;
	var showDialog = false;
	var isSelected = false;
	var price = 0;

	if(clickBox.hasAttribute('data-powertip')) {
		$('#confirm-dialog-mob-hover').css('display','block');
		hover = clickBox.getAttribute('data-powertip');
		showDialog = true;
	}
	if(clickBox.hasAttribute('data-legend')) {
		$('#confirm-dialog-mob-legend').css('display','block');
		legend = clickBox.getAttribute('data-legend');
		showDialog = true;
	}
	
	if(clickBox.hasAttribute('data-seat')) {
		$('#selected-seat').val(clickBox.getAttribute('data-seat'));
		$('#selected-seat-room').val(currentRoom.name);
		$('#selected-room-uuid').val(currentRoom.uuid);
		type = 'rbox';
	
		$('#selected-seat-nr').val(seatPrefix + nr);
		showDialog = true;
	}

	if(clickBox.hasAttribute('data-status')) {
		type = clickBox.getAttribute('data-status');
		showDialog = true;
	}

	if(clickBox.hasAttribute("data-selectedBox")) {
		isSelected = true;
	}

	if(clickBox.hasAttribute("data-price")) {
		price = JSON.parse(clickBox.getAttribute('data-price'));
	}
	
	if(hover != null) {
		$('#confirm-dialog-mob-hover').html(hover);
	}

	if( !Array.isArray(price) ) {
		//Not multi price
		$('#confirm-dialog-bottom').append('<div class="seatreg-btn green-btn add-to-cart" data-price="' + price + '">' + translator.translate('addToBooking') + '</div>');
	}else if( this.isPaymentEnabled() ) {
		//Multi price
		$('#confirm-dialog-bottom').append('<div class="multi-price-title">Price selction</div><div class="multi-price-wrap"></div>');

		price.forEach(function(price) {
			$('#confirm-dialog-bottom .multi-price-wrap').append('<div><strong>'+ getCurrencySymbolFromISO(this.payPalCurrencyCode) + price.price + '</strong> <span class="mullti-price-description">' + price.description  + '</span></div><div class="seatreg-btn green-btn add-to-cart" data-price="' + price.price + '" data-price-uuid="' + price.uuid + '">' + translator.translate('addToBooking') + '</div>');
		});
	}else {
		//Fallback
		$('#confirm-dialog-bottom').append('<div class="seatreg-btn green-btn add-to-cart" data-price="0">' + translator.translate('addToBooking') + '</div>');
	}

	if(type != 'box') {
		if(!isSelected) {
			if(isLocked) {
				var text = this.usingSeats ? translator.translate('seatIsLocked') : translator.translate('placeIsLocked');

				$('#confirm-dialog-bottom').empty();
				$('#confirm-dialog-mob-text').html('<div class="seat-taken-notify">' + text + '</div>');
			}else if(passwordNeeded && !this.enteredSeatPasswords.hasOwnProperty(seatId)) {
				$('#confirm-dialog-bottom').empty();
				$('#confirm-dialog-mob-text').html('<div class="seat-taken-notify">'+ translator.translate('pleaseEnterPassword') + '</div>' + 
					'<div class="box-password-wrap"><input type="text" id="seat-password" /> ' +
					'<div class="seatreg-btn green-btn" id="password-check">Ok</div></div>' +
					'<div id="password-error" class="d-none" style="color:red">'+ translator.translate('passwordNotCorrect') +'</div>' + 
					'<div id="password-check-loader" class="d-none">'+ '<img alt="Loading..." src="'+ WP_Seatreg.plugin_dir_url + 'img/ajax_loader_small.gif' +'" />' +'</div>');
			}else if(type == 'rbox' && this.selectedSeats.length < this.seatLimit ) {

				if( this.requireWPLogin && !this.isLoggedIn ) {
					var text = translator.translate('wpLoginRequired');
	
					$('#confirm-dialog-bottom').empty();
					$('#confirm-dialog-mob-text').html('<div class="seat-taken-notify">' + text + '</div>');
				}else if( this.status == 'run' && !this.hasFailedTimeRestrictions() ) {
					var maxPlacesText = this.usingSeats ? translator.translate('maxSeatsToAdd') : translator.translate('maxPlacesToAdd');
					
					$('#confirm-dialog-mob-text').html('<div class="add-seat-text"><h5>'+ translator.translate('add_') + ' ' + this.spotName + ' ' + seatPrefix + nr + translator.translate('_fromRoom_') + ' ' + room + translator.translate('_toSelection') +'</h5><p>'+ maxPlacesText + ' ' + this.seatLimit +'</p>' + '</div>');

					if(this.isPaymentEnabled() && this.payPalCurrencyCode && price > 0) {
						var placeCostText = this.usingSeats ? translator.translate('seatCosts_') : translator.translate('placeCosts_');

						$('#confirm-dialog-mob-text .add-seat-text').append('<p>' + placeCostText + '<strong>' +  getCurrencySymbolFromISO(this.payPalCurrencyCode) + price + '</strong></p>');
					}
				}else {
					$('#confirm-dialog-mob-text').html('<div class="add-seat-text"><h5>' + this.spotName + ' ' + nr + translator.translate('_fromRoom_')  + room + '</h5></div>');
				}

			}else if(type == 'tak') {
				$('#confirm-dialog-bottom').empty();
				$('#confirm-dialog-mob-text').html('<div class="seat-taken-notify"><h5>'+ translator.translate('this_') + this.spotName + translator.translate('_isOccupied') + '</h5></div>');
			}else if(type == 'bron') {
				$('#confirm-dialog-bottom').empty();
				$('#confirm-dialog-mob-text').html('<div class="seat-bron-notify"><h5>' + translator.translate('this_') +  ' ' + this.spotName + translator.translate('_isPendingState') +'</h5>'+ translator.translate('regOwnerNotConfirmed') +'</div>');
			}else if(type == 'rbox' && this.selectedSeats.length >= this.seatLimit ) {
				$('#confirm-dialog-bottom').empty();
				$('#confirm-dialog-mob-text').html('<div class="seat-taken-notify">'+ translator.translate('selectionIsFull') +'</div>');
			}
		}else {
			$('#confirm-dialog-bottom').empty();
			$('#confirm-dialog-mob-text').html('<div class="add-seat-text"><h5>' + capitalizeFirstLetter(this.spotName)  + ' ' + nr + translator.translate('_isAlreadySelected') +'</h5></div>');
		}	
	}
	if(showDialog) {
		return true;
	}else {
		return false;
	}
};

SeatReg.prototype.addEnteredSeatPassword = function(seatId, password) {
	this.enteredSeatPasswords[seatId] = password;
};

/*Turning on lights*/
var seatReg = new SeatReg();
seatReg.init();

$(window).resize(function() {
		rtime = new Date();
	    if (timeout === false && seatReg.rooms) {
	        timeout = true;
	        setTimeout(resizeend, delta);
	    }
});

function resizeend() {
    if (new Date() - rtime < delta) {
        setTimeout(resizeend, delta);
    } else {
        timeout = false;
        screenWidth = $(window).width();
  		screenHeight = $(window).height();

  		if(screenWidth > 1024) {
			seatReg.mobileview = false;
			  
  			if($('#room-nav').hasClass('modal')) {
  				$('#room-nav').removeClass('modal');
  				$('#modal-bg').css('display','none');
  			}
  		}else {
  			seatReg.mobileview = true;		
  		}
  		setMiddleSecSize(seatReg.rooms[seatReg.currentRoom].room.width, seatReg.rooms[seatReg.currentRoom].room.height);
  		if(legendScroll != null) {
			legendScroll.destroy();
			legendScroll= null;
		}
		initLegendsScroll();
    }               
}

function setMiddleSecSize(roomSizeWidth, roomSizeHeight) {
	var navHeight = $('#room-nav-wrap').outerHeight(true);
	var infoHeight = $('.top-info-bar').outerHeight(true) || 0;
	var poweredByHeight = $('#powered-by').outerHeight(true);
	var cartWidth = $('#controls-wrapper').outerWidth(true);
	var legendWidth = 0;
	var spaceForMiddleWidth = screenWidth - 20; //how much room for seat map
	var spaceForMiddleHeight = screenHeight - 30 - 70 - navHeight - $('#bottom-wrapper').outerHeight(true) - $('#zoom-controller').outerHeight(true);  // - header height, -legend height, navbar height, -spacing  --default mobile
	var needHorizScroll = false;
	var needVerticScroll = false;

	$('#middle-section').css('margin-left','');

	if(screenWidth >= 1024) {
		//ok i have bigger screen. set legends area left and seatcart right
		
		if($('#legend-wrapper').is(':visible')) {
			legendWidth = $('#legend-wrapper').outerWidth(true);
			spaceForMiddleWidth = spaceForMiddleWidth - Math.max(legendWidth, cartWidth) * 2 - 20;
		}else {
			spaceForMiddleWidth = spaceForMiddleWidth - cartWidth * 2;
		}

		/* console.log('Screen height: ', screenHeight);
		console.log('navHeight height: ', navHeight);
		console.log('infoHeight height: ', infoHeight); */

		spaceForMiddleHeight = screenHeight - 30 - navHeight - infoHeight - 30 - poweredByHeight;  //- header height, - navbar height, -footer if needed

		if(seatReg.rooms[seatReg.currentRoom].room.legends.length > 0) {
			$('#legend-wrapper').css('display','inline-block');
		}

	}else {
		//mobile screen
		$('#box-wrap').css('width', spaceForMiddleWidth- 20);
	}

	$('#boxes').removeAttr('style');
	//width of middle
	if(roomSizeWidth > spaceForMiddleWidth) {
		//roomsize is too wide
		needHorizScroll = true;
		$('#box-wrap').css('width', spaceForMiddleWidth - 40);
		$('#boxes').css('width',roomSizeWidth + 15);

	}else {
		$('#box-wrap, #boxes').css('width', roomSizeWidth);
	}

	//height of middle

	if(roomSizeHeight > spaceForMiddleHeight) {
		needVerticScroll = true;
		$('#box-wrap').css('height', spaceForMiddleHeight);
		$('#boxes').css('height',roomSizeHeight + 15);

	}else {
		$('#box-wrap, #boxes').css('height', roomSizeHeight);
	}

	//legends height

	if(screenWidth < 1024) {
		$('#legend-wrapper').css('display','none');
	}

	$('#box-wrap').attr('data-sec-size', $('#box-wrap').css('width'));

	//init iScroll
	initScroll(needHorizScroll, needVerticScroll);  //for seat map
}

function initLegendsScroll() {
	if(screenWidth < 1024) {
		if(seatReg.ie8 == false) {
		}
	}else {
		$('#legend-wrapper').css('max-height',"");
	}
}

function initScroll(needHorizScroll, needVerticScroll) {

	$('#box-wrap').off('mousewheel DOMMouseScroll').on('mousewheel DOMMouseScroll', function(e) {
		//if ctrl scroll enabled prevent scroll without ctrl down
		if( seatReg.controlledScrollEnabled && !zKeyDown ) {
			e.stopPropagation();

			if( !showingAlertNotification() ) {
				displayNotificationMessage(translator.translate('controlledZoom'));
			}
		}
	});	

	//destroy previous scroll
	if(myScroll != null) {
		myScroll.destroy();
		myScroll = null;
	}

	//do i need to zoom out?
	var needToZoom = false;

	if(seatReg.rooms[seatReg.currentRoom].room.width > $('#middle-section').width() || seatReg.rooms[seatReg.currentRoom].room.height > $('#middle-section').height()) {
		needToZoom = true;
	}

	if(myScroll == null && seatReg.ie8 == false) {
			myScroll = new IScroll('#box-wrap', {
				keyBindings: true,
				scrollbars: true,
				scrollX: true,
				scrollY: true,
				bounce: false,
				tap: true,
				click: true,
				interactiveScrollbars: true,
				freeScroll: true,
				zoom: true,
				zoomMax: 30,
				zoomMin: 0.1,
				mouseWheelSpeed: 20,
			});

			if(needToZoom) {
				var fitF = fitFactor();
				myScroll.zoom(fitF);
			}

		$('#boxes').css({'cursor':"all-scroll"});
	}
}

function displayNotificationMessage(message) {
	alertify.log(message);
}

function showingAlertNotification() {
	return $('.alertify-log-show').length > 0;
}

function zoomStart() {
	var w = fitFactor();

	boxWrapSize(w);
}

function boxWrapSize(fitF) {
	var w = seatReg.rooms[seatReg.currentRoom].room.width * fitF;

	if(w < parseInt($('#box-wrap').data('sec-size'))) {

		$('#box-wrap').css({
			'width': w
		});
	}else {
		$('#box-wrap').css({
			'width': $('#box-wrap').data('sec-size')
		});
	}
}

function fitFactor(){
	    //compute witch dimension is larger width vs height

	    var w = seatReg.rooms[seatReg.currentRoom].room.width / ($('#middle-section').width() - 20);
	    var h = seatReg.rooms[seatReg.currentRoom].room.height / ($('#middle-section').height() - 20);
	    //h = content.H / wrap.H;//zoom factor for height
	    //w = content.W/ wrap.W;//zoom factor for width
	    //get max between zoom factores, remove percents
	    var renderedFactor = Math.max(w, h);
		//return scale factor
		
	    return  1/renderedFactor;
}

function validateInput(inputField) {
	var emailReg = /^\S+@\S+$/;
	var gmailReg = /^[a-z0-9](\.?[a-z0-9]){2,}@g(oogle)?mail\.com$/;
	var customFieldRegExp = new RegExp("^[\\p{L}1234567890\\s:\/.,-:;?]{1," + WP_Seatreg.SEATREG_CUSTOM_TEXT_FIELD_MAX_LENGTH + "}$", "u");
	var inputReg = new RegExp("^[\\p{L}1234567890\\s-]{1,100}$", "u");
	var isOptional = inputField.attr('data-optional') === 'true';
	var value = inputField.val();

	if(value == '' && !isOptional) {
		inputField.parent().siblings('.field-error').text(translator.translate('emptyField')).css('display','block');

		return false;
	}

	switch(inputField.attr('data-field')) {
		case 'FirstName':
			inputField.parent().siblings('.field-error').text('').css('display','inline');

			if(!inputReg.test(value)) {
				inputField.parent().siblings('.field-error').text(translator.translate('illegalCharactersDetec')).css('display','block');	

				return false;
			}
	
			break;
		case 'LastName':
			inputField.parent().siblings('.field-error').css('display','none');

			if(!inputReg.test(value)) {
				inputField.parent().siblings('.field-error').text(translator.translate('illegalCharactersDetec')).css('display','block');	

				return false;
			}
		
			break;
		case 'Email':
			var useThis = emailReg;
			if(seatReg.gmailNeeded == 1) {
				useThis = gmailReg;
			}

			if(useThis.test(value)) {
				inputField.parent().siblings('.field-error').css('display','none');
			}else {
				inputField.parent().siblings('.field-error').text(translator.translate('emailNotCorrect')).css('display','block');
	
				return false;
			}

			break;
		default:
			//custom field validation
			var customFieldType = inputField.attr('data-type');

			if(customFieldType === 'text' || customFieldType === 'sel') {
				if (isOptional) {
					return true;	
				}
				if( customFieldRegExp.test(value)) {				
					inputField.parent().siblings('.field-error').css('display','none');	
				}else {	
					inputField.parent().siblings('.field-error').text(translator.translate('illegalCharactersDetec')).css('display','block');	
	
					return false;	
				}
			}
			
	}
	return true;
}

function duplicateBookingFirstEntryValue(fieldName) {
	var firstInput = $('#checkout-input-area .check-item').first().find('.field-input[data-field="' + fieldName + '"]');
    var value, isChecked;

    if (firstInput.is(':checkbox')) {
        isChecked = firstInput.is(':checked');
    } else {
        value = firstInput.val();
    }

	$('#checkout-input-area .check-item').each(function() {
		var targetInput = $(this).find('.field-input[data-field="' + fieldName + '"]');

		if (targetInput.is(':checkbox')) {
            targetInput.prop('checked', isChecked);
        } else {
            targetInput.val(value);
        }
	});
}

function CustomData(label, value) {
	this.label = label;
	this.value = value;
}

function collectData() {
	var sendPack = [];
	
	$('#checkout-input-area .check-item').each(function() {
		var customFieldPack = [];

		$(this).find('.custom-input').each(function() {
			var type = $(this).find('.field-input').attr('data-type');

			if(type == 'text') {
				customFieldPack.push(new CustomData($(this).attr('data-label'), $(this).find('.field-input').val()));
			}else if(type == 'check') {
				customFieldPack.push(new CustomData($(this).attr('data-label'), $(this).find('.field-input').is(":checked") ? '1' : '0') );
			}else if(type == 'sel') {
				customFieldPack.push(new CustomData($(this).attr('data-label'),$(this).find('.field-input').find(":selected").val()));
			}	
		});

		sendPack.push(customFieldPack);
	});

	return sendPack;
	
}

function sendData(customFieldBack, registrationCode) {
	$('#checkout-confirm-btn').css('display','none');
	$('#checkoput-area-inner .ajax-load').css('display','inline-block');

	var mailToSend = null;
	var seatPasswords = JSON.stringify(seatReg.enteredSeatPasswords);
	customFieldBack = JSON.stringify(customFieldBack);

	if(seatReg.selectedSeats.length > 1 && seatReg.onePersonCheckout === false) {
		mailToSend = $('#prim-mail').val();
	}else {
		mailToSend = $('#checkout-input-area .check-item').first().find('.field-input[data-field="Email"]').val();
	}

	$.ajax({
		type: 'POST',
		url: ajaxUrl,
		data: $('#checkoput-area-inner').serialize() + '&custom=' + encodeURIComponent(customFieldBack) +'&action=' + 'seatreg_booking_submit' + '&c=' + registrationCode + '&em=' + mailToSend + '&pw=' + $('#sub-pwd').val() + '&passwords=' + encodeURIComponent(seatPasswords),
		success: function(data) {
			var is_JSON = true;
			
			try {
				var resp = $.parseJSON(data);
			} catch(err) {
				is_JSON = false;
			}
			if(is_JSON) {
				if( resp.type == 'ok' && seatReg.bookingRedirectToStatusPage && !seatReg.emailConfirmEnabled) {
					window.location.href = resp.data;
					return;
				}
				$('#checkoput-area-inner .ajax-load').css('display','none');

				if(resp.type == 'ok' && resp.text == 'mail') {	
					$('#email-send').text(mailToSend);
					needMailCheckInfo();
				}else if(resp.type == 'ok' && resp.text == 'bookings-confirmed-status-2') {
					bookingsConfirmedInfo(resp.data, 2);
				}else if(resp.type == 'ok' && resp.text == 'bookings-confirmed-status-1') {
					bookingsConfirmedInfo(resp.data, 1);
				}else if(resp.type == 'error' && resp.text == 'Wrong captcha') {
					$('#captcha-img').replaceWith(resp.data);
					$('#request-error').text(translator.translate('wrongCaptcha')).css('display','block');
					$('#checkout-confirm-btn').css('display','inline-block');
				}else if(resp.type == 'error') {
					$('#checkout-area').css('display','none');
					$('#captcha-ref').click();
					$('#error-text').html(resp.text);
					$('#error').css('display','block');
					$('#checkout-confirm-btn').css('display','inline-block');
				}else if(resp.type == 'validation-error') {
					$('#captcha-ref').click();
					$('#checkout-confirm-btn').css('display','inline-block');
					$('#request-error').text(resp.text).css('display','block');
				} else {
					showErrorAlertDialog(translator.translate('somethingWentWrong'));
					$('#checkout-confirm-btn').css('display','inline-block');
				}
			}else {
				$('#checkoput-area-inner .ajax-load').css('display','none');
				$('#checkout-area').css('display','none');
				showErrorAlertDialog(translator.translate('somethingWentWrong'));
				$('#checkout-confirm-btn').css('display','inline-block');
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			$('#checkout-confirm-btn').css('display','inline-block');
			if (jqXHR.status === 0) {
                alert('Not connect.\n Verify Network.');
            } else if (jqXHR.status == 404) {
                alert('Requested page not found. [404]');
            } else if (jqXHR.status == 500) {
                alert('Internal Server Error [500].');
            } else if (exception === 'timeout') {
                alert('Time out error.');
            } else if (exception === 'abort') {
                alert('Ajax request aborted.');
            } else {
                alert('Uncaught Error.\n' + jqXHR.responseText);
            }
		}
	});
}

function showErrorAlertDialog(errorText) {
	$('#error-inner').prepend(translator.translate(errorText));
	$('#error').css('display','block');
}

function needMailCheckInfo() {
	$('#checkout-area').css('display','none');
	$('#email-conf').css('display','block');
}

function bookingsConfirmedInfo(data, status) {
	$('.booking-check-url').text(data).attr('href', data);
	$('#checkout-area').css('display','none');
	$('#bookings-confirmed').css('display','block');

	if(status === 1) {
		var bookingTotalPrice = parseInt($('#booking-total-price').attr('data-booking-price'));

		$('.booking-confirmed-header').text(translator.translate('bookingsConfirmedPending'));
		if( seatReg.isPaymentEnabled() && bookingTotalPrice > 0) {
			$('#booking-confirmed-text').text(translator.translate('payForBookingLink'));
		}
		if ( !seatReg.NotifyBookerPendingBooking ) { $('#should-receive-update-email-text').css('display','none'); }
	}else if (status === 2) {
		$('#should-receive-update-email-text').css('display', 'none');
		
		if(window.receiptEnabled === '1') {
			$('.booking-confirmed-header').html(translator.translate('bookingsConfirmed') + '<br>' + translator.translate('receiptSent'));
		}else {
			$('.booking-confirmed-header').text(translator.translate('bookingsConfirmed'));
		}
	}
}

function getRegistrationCode() {
	return qs['c'];
}

function sanitizeClassName(inputString) {
	// Replace characters that are not valid in class names with underscores
	return inputString.replace(/[^a-zA-Z0-9_-]/g, '_');
}

$('#room-nav-btn').on('click', function() {	
	seatReg.openModel();
});

$('#close-modal').on('click', function() {
	seatReg.closeModal();
});

$('#seat-cart, .mobile-cart').on('click', function() {
	seatReg.openSeatCart();
});

$('#dialog-close-btn').on('click', function() {
	seatReg.closeSeatDialog();
});

$('#confirm-dialog-bottom').on('click', '.add-to-cart', function() {
	$('#selected-seat-price').val( $(this).data('price') );
	$('#selected-multi-price-uuid').val( $(this).data('price-uuid') || '' );

	seatReg.addSeatToCart();

	if ( seatReg.automaticBookingConfirmDialog ) {
		seatReg.openSeatCart();
	}
});

$('#room-nav-close').on('click', function() {
	seatReg.closeModal();
});

$('#checkout').on('click', function() {
	seatReg.openCheckOut();
});

$('#checkout-area').on('click', '.checkout-settings__copy input[type="checkbox"]', function() {
	if( $(this).is(':checked') ) {
		$('#checkout-input-area .check-item').first().find('.field-input').each(function() {
			var fieldName = $(this).attr('data-field');
			duplicateBookingFirstEntryValue(fieldName);
		});
	}
});

$('#checkout-input-area').on('keyup change input','.field-input', function() {
	var isFirstEntrySyncEnabled = $('#checkout-area .checkout-settings__copy input[type="checkbox"]').is(':checked');

	if( $(this).attr('data-field') === 'Email' ) {
		$(this).val( $(this).val().trim() );
	}

	if( isFirstEntrySyncEnabled ) {
		var fieldName = $(this).attr('data-field');
		duplicateBookingFirstEntryValue(fieldName);
	}

	validateInput($(this));
});


$('#checkout-input-area').on('click', '.check-item__copy', function(e) {
	e.preventDefault();

	var input = $(this).siblings('.field-input');

	$(this).closest('.check-item').next().find('.field-input[data-field="'+ input.data('field') +'"]').val( input.val() );
});

$('.refresh-btn').on('click', function() {
	window.location.reload();
});

$('#confirm-dialog-mob-text').on('click', '#password-check', function() {
	var seatId = $('#selected-seat').val();
	var password = $('#seat-password').val();

	$('#password-error').addClass('d-none');
	$('#password-check-loader').removeClass('d-none');
	
	$.ajax({
		type: 'POST',
		url: ajaxUrl,
		data: {
			action: 'seatreg_seat_password_check',
			password: $('#seat-password').val(),
			'registration-code': qs['c'],
			'seat-id': seatId
		},
		success: function(data) {
			$('#password-check-loader').addClass('d-none');

			if(data.success) {
				seatReg.addEnteredSeatPassword(seatId, password);
				seatReg.openSeatDialog($('.box[data-seat='+ seatId +']')[0]);
			}else {
				$('#password-error').removeClass('d-none');
			}
		}
	});
});

$('#checkoput-area-inner').submit(function(e) {
	e.preventDefault();
	var valid = true;

	$('#request-error').text('');
	$('#checkout-input-area .field-input').each(function() {
		if( !validateInput( $(this) ) ) {
			valid = false;
		}
	});

	if($('#captcha-val').val() == '') {
		$('#captcha-val').focus();
		$('#request-error').text('Enter captcha').css('display','block');

		return;
	}

	if(valid) {
		sendData( collectData(), getRegistrationCode() );
	}
})

$('#captcha-ref').on('click', function() {
	$.ajax({
		type: 'POST',
		url: ajaxUrl,
		data: {
			action: 'seatreg_new_captcha',
			cap:'new'
		},
		success: function(data) {
			$('#captcha-img').replaceWith(data);
		}
	});
});

$('#close-time').on('click', function() {
	$('#time-notify, .modal-bg').css('display','none');
});
$('#login-notify .close-btn').on('click', function() {
	$('#login-notify, .modal-bg').css('display','none');
});

$('.room-nav-extra-info-btn, #main-header').on('click', function() {
	seatReg.openInfo();
});

$('.mobile-legend').on('click', function() {
	$('.legend-popup-legends').html($('#legends').html());
	$('#modal-bg').css('display','block');
	$('#legend-popup-dialog').css('display','block');
});

$('.close-btn').on('click', function() {
	var $activeDialog = $(this).closest('.dialog-box');
	$activeDialog.css('display','none');
	$('#modal-bg').css('display','none');
	$('#request-error').text('').css('display', 'none');
});

$('.zoom-action').on('click', function() {
	if(myScroll != null) {
		if($(this).data('zoom') == 'in') {
			myScroll.zoom(myScroll.scale + 0.4);
		}else {
			myScroll.zoom(myScroll.scale - 0.4);
		}
	}
});

$('.move-action').on('click', function() {
	if(myScroll != null) {
		switch($(this).data('move')) {
			case 'up':
				if(myScroll.y < 0) {
					myScroll.scrollBy(0, +100);
				}
				break;

			case 'left':
				if(myScroll.x < 0) {
					myScroll.scrollBy(100, 0);
				}
				break;

			case 'right':
				myScroll.scrollBy(-100, 0);
				break;

			case 'down':
				var roomHeight = seatReg.rooms[seatReg.currentRoom].room[5];
				myScroll.scrollBy(0, -100);

				break;
		}
	}
});

$('#middle-section').on( 'DOMMouseScroll mousewheel', function ( event ) {
	 event.preventDefault();
	 var mouseX = event.originalEvent.clientX;
	 var mouseY = event.originalEvent.clientY;

	if( event.originalEvent.detail > 0 || event.originalEvent.wheelDelta < 0 ) { //alternative options for wheelData: wheelDeltaX & wheelDeltaY
		scrollDown(mouseX, mouseY);
	} else {
		scrollUp(mouseX, mouseY);
	}
  //prevent page fom scrolling
  return false;
});

function scrollDown(mouseX, mouseY) {
	if(myScroll != null) {
    	if(msie == 0) {
    		myScroll.zoom(myScroll.scale - 0.2, mouseX, mouseY, 600);
    	}else {
    		myScroll.zoom(myScroll.scale - 0.2, mouseX, mouseY, 600);
    	}
    }
}

function scrollUp(mouseX, mouseY) {
	if(myScroll != null) {
    	if(msie == 0) {
     		myScroll.zoom(myScroll.scale + 0.2, mouseX, mouseY, 600);
     	}else {
     		myScroll.zoom(myScroll.scale + 0.2, mouseX, mouseY, 600);
     	}
    }
}
})(jQuery);
