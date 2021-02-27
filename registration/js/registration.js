$(function() {
	var ajaxUrl = '../../../../wp-admin/admin-ajax.php';

	var translator = {
		translate: function(translationKey) {
			if(seatregTranslations && seatregTranslations.hasOwnProperty(translationKey)) {
				return seatregTranslations[translationKey];
			}
		}
	};

	function capitalizeFirstLetter(string) {
	    return string.charAt(0).toUpperCase() + string.slice(1);
	}

	$('.time').each(function() {
		var date = new Date(parseInt($(this).text()));
		$(this).text(date.format("d.M.Y H:i"));
	});


	var screenWidth = $(window).width();
	var screenHeight = $(window).height();

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


	function SeatReg() {
		this.rooms = dataReg.roomData;
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
		this.status = regTime;
		this.spotName = translator.translate('seat');
		this.demo = false;
	}

	function CartItem(id,nr,room, roomUUID) {
		this.id = id;
		this.nr = nr;
		this.room = room;
		this.roomUUID = roomUUID;
		this.defFields = ['FirstName','LastName','Email'];
		this.customFields = [];
	};

	SeatReg.prototype.browserInfo = function() {
		//set browser info. css3 support and is ie8

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

	SeatReg.prototype.init = function() {
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
		var reLength = Object.size(registrations);

		for(var i = 0; i < reLength; i++) {
			if(registrations[i].hasOwnProperty('reg_name')) {
				seatReg.addRegistration(registrations[i]['seat_id'], registrations[i]['room_uuid'], registrations[i]['status'], registrations[i]['reg_name']);
			}else {
				seatReg.addRegistration(registrations[i]['seat_id'], registrations[i]['room_uuid'], registrations[i]['status'], null);
			}
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

	SeatReg.prototype.addRegistration = function(seat_id, room_uuid, status, reg_name) {
		var roomLocation = this.locationObj[room_uuid];
		var boxesLen = this.rooms[roomLocation].boxes.length;

		for(var j = 0; j < boxesLen; j++) {
			if(this.rooms[roomLocation].boxes[j].id == seat_id) {
				if(status == 1) {
					this.rooms[roomLocation].boxes[j].status = 'bronRegister';
				}else {
					this.rooms[roomLocation].boxes[j].status = 'takenRegister';
				}

				if(reg_name != null) {	//need to show name
					if(this.rooms[roomLocation].boxes[j].hoverText === "nohover") { //no bubble text
						this.rooms[roomLocation].boxes[j].hoverText = reg_name;
					}else {  //need to add name at the beginning of bubble text
						this.rooms[roomLocation].boxes[j].hoverText = reg_name + '^^' + this.rooms[roomLocation].boxes[j].hoverText;
					}
				}

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

			var clickable = false;

			box.style.top = loc[i].yPosition + 'px';
			box.style.left = loc[i].xPosition + 'px';
			box.style.backgroundColor = loc[i].color;
			box.style.zIndex = loc[i].zIndex;
			box.style.width = loc[i].width + 'px';
			box.style.height = loc[i].height + 'px';

			if(loc[i].legend !== 'noLegend') {
				box.setAttribute('data-legend',loc[i].legend);
				box.setAttribute('data-leg',loc[i].legend.replace(/\s+/g, '_').toLowerCase());
				clickable = true;
			}

			if(loc[i].canRegister == "true") {
				box.setAttribute('data-seat',loc[i].id);
				var number = document.createElement('div');
				number.className = "seat-number";
				var newContent = document.createTextNode(loc[i].seat);
				number.appendChild(newContent);
				box.appendChild(number);
				clickable = true;
			}

			if(loc[i].hoverText !== "nohover") {
				box.setAttribute('data-powertip',loc[i].hoverText.replace(/\^/g,'<br>'));
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
					box.appendChild(bronSign);
				}else if(loc[i].status == "takenRegister") {
					box.setAttribute('data-status','tak');
					var takSign = document.createElement('div');
					takSign.className = "taken-sign";
					box.appendChild(takSign);
				}
				clickable = true;
			}

			if(clickable) {
				box.className = box.className + ' cursor';
			}
					
			if (!this.ie8){
				box.addEventListener('tap',function() {
					scope.openSeatDialog(this);
					
				},false);
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
				//add selected seat mark				
				documentFragment.querySelector('.box[data-seat="' + this.selectedSeats[i].id + '"]').setAttribute('data-selectedbox','true');
			}
		}

		document.getElementById("boxes").appendChild(documentFragment);	

		if(this.rooms[this.currentRoom].room.backgroundImage !== null && this.rooms[this.currentRoom].room.backgroundImage.indexOf('.') !== -1) {  //dose room have a background image?
			$('#boxes').append('<img class="room-image" src="../uploads/room_images/' + qs['c'] + '/' + this.rooms[this.currentRoom].room.backgroundImage + '" />');
		}

		$('#boxes .bubble-text').powerTip({
			followMouse: true,
			fadeInTime: 0,
			fadeOutTime:0,
			intentPollInterval: 10
		});
	};

SeatReg.prototype.paintRoomInfo = function() {
	//room-nav-info
	$('#current-room-name').text(this.rooms[this.currentRoom].room.name);
	var infoLoc = this.rooms[this.currentRoom].room;
	var documentFragment = $(document.createDocumentFragment());

	documentFragment.append(
		'<div class="info-item open-seats">' + 
		'<span>' + 
		translator.translate('openSeatsInRoom_') +
		'</span>' + 
		infoLoc.roomOpenSeats + 
		'</div>', 
		'<div class="info-item"><span class="bron-legend"></span> <span>'+ translator.translate('pendingSeatInRoom_') +'</span>' + infoLoc.roomBronSeats +'</div>', '<div class="info-item"><span class="tak-legend"></span> <span>'+ translator.translate('confirmedSeatInRoom_') +'</span>' + infoLoc.roomTakenSeats +'</div>');

	$('#room-nav-info-inner').html(documentFragment);
};

SeatReg.prototype.paintRoomLegends = function() {
	//paint legend boxes
	$('#legends').empty();
	var arrLen = this.rooms[this.currentRoom].room.legends.length;

	if(arrLen > 0) {
		if(this.mobileview) {
			$('#legend-wrapper').css('display','none');
		}else {
			$('#legend-wrapper').css('display','inline-block');
		}
	}else {
		$('#legend-wrapper, .mobile-legend').css('display','none');
	}

	var documentFragment = $(document.createDocumentFragment());

	for(var i = 0; i < arrLen; i++) {
		documentFragment.append($('<div class="legend-div" data-target-legend='+ this.rooms[this.currentRoom].room.legends[i].text.replace(/\s+/g, '_').toLowerCase() +'></div>').append('<div class="legend-box" style="background-color:'+ this.rooms[this.currentRoom].room.legends[i].color +'"></div>', '<div class="legend-name">'+ this.rooms[this.currentRoom].room.legends[i].text +'</div>'));
	}

	$('#legends').append(documentFragment);
	$('#legends .legend-div').on('click', function() {
		var clickLegend = $(this).data('target-legend');
		var legendBoxes = $('#boxes .box[data-leg='+ clickLegend +']');
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
	$('#room-nav-items').append(documentFragment);
};

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

SeatReg.prototype.addSeatToCart = function() {
	//adding selected seat to seat cart
	var seatId = document.getElementById('selected-seat').value;
	var seatNr = document.getElementById('selected-seat-nr').value;
	var roomName = document.getElementById('selected-seat-room').value;
	var roomUUID = document.getElementById('selected-room-uuid').value;
	var scope = this;
	this.selectedSeats.push(new CartItem(seatId, seatNr, roomName, roomUUID));
	
	$('.seats-in-cart').text(this.selectedSeats.length);
	$('#boxes .box[data-seat="' + seatId + '"]').attr('data-selectedBox','true');

	//add to seat cart popup
	var cartItem = $('<div class="cart-item" data-cart-id="' + seatId + '" data-room-uuid="'+ roomUUID +'"></div>');
	var seatNumberDiv = $('<div class="cart-item-nr">' + seatNr + '</div>');
	var roomNameDiv = $('<div class="cart-item-room">' + roomName + '</div>');
	var delItem = $('<div class="remove-cart-item"><i class="fa fa-times-circle"></i><span style="padding-left:4px">'+ translator.translate('remove') +'</span></div>').on('click', function() {
		var item = $(this).closest('.cart-item');
		var removeId = item.attr('data-cart-id');

		var arrLen = scope.selectedSeats.length;
		for(var i = 0; i < arrLen; i++) {

			if(scope.selectedSeats[i].id = removeId) {
				scope.selectedSeats.splice(i, 1);
				break;
			}
		}
		item.remove();
		$('#boxes .box[data-seat="'+ removeId +'"]').removeAttr('data-selectedbox');

		if(scope.selectedSeats.length == 0) {
			$('#seat-cart-info').html('<h3>'+ translator.translate('selectionIsEmpty') +'</h3><p>' + translator.translate('youCanAdd_') + scope.spotName + translator.translate('_toCartClickTab') + '</p>');
			$('#checkout').css('display','none');
			$('#seat-cart-rows').css('display','none');
		}else {
			var selected = scope.selectedSeats.length;
			var infoText;
			if(selected > 1) {
				infoText = selected + translator.translate('_seatsSelected');
			}else {
				infoText = selected + translator.translate('_seatSelected');
			}
			$('#seat-cart-info').text(infoText);
		
		}
		$('.seats-in-cart').text(scope.selectedSeats.length);

	});

	cartItem.append(seatNumberDiv, roomNameDiv, delItem);
	$('#seat-cart-items').append(cartItem);

	this.closeSeatDialog();
};

SeatReg.prototype.openSeatCart = function() {
	var selected = this.selectedSeats.length;

	if(selected == 0) {	
		if(this.status == 'run') {
			$('#seat-cart-info').html('<h3>'+ translator.translate('selectionIsEmpty') +'</h3><p>' + translator.translate('youCanAdd_') + this.spotName + translator.translate('_toCartClickTab') + '</p>');
			$('#checkout').css('display','none');
			$('#seat-cart-rows').css('display','none');
		}else {
			$('#seat-cart-info').html('<h3>'+ translator.translate('regClosedAtMoment') +'</h3>');
		}

	}else {
		$('#seat-cart-rows').css('display','block');
		var infoText;
		if(selected > 1) {
			infoText = selected + translator.translate('_seatsSelected');
		}else {
			infoText = selected + translator.translate('_seatSelected');
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

SeatReg.prototype.closeCheckOut = function(hideModalBg) {
	$('#checkout-area').css('display','none');
	$('#modal-bg').css('display','none');	
};

SeatReg.prototype.generateCheckout = function(arrLen) {
	$('#checkout-input-area').empty();
	var documentFragment = $(document.createDocumentFragment());
	var arrLen3 = this.customF.length;

	for(var i = 0; i < arrLen; i++) {
		var checkItem = $('<div class="check-item"></div>');
		var checkItemHeader = $('<div class="check-item-head">'+ this.spotName +' nr <span>' + this.selectedSeats[i].nr + '</span><br><span>' + this.selectedSeats[i].room + '</span></div>');
		var documentFragment2 = $(document.createDocumentFragment());
		var arrLen2 = this.selectedSeats[i].defFields.length;
		
		for(var j = 0; j < arrLen2; j++) {
			var field = this.generateField(this.selectedSeats[i].defFields[j]);
			documentFragment2.append(field);
		}

		for(var j = 0; j < arrLen3; j++) {
			var field = this.generateCustomField(this.customF[j]);
			documentFragment2.append(field);
		}

		var seatId = $('<input type="hidden" class="item-id" name="item-id[]" value="' + this.selectedSeats[i].id + '" />');
		var seatNr = $('<input type="hidden" class="item-nr" name="item-nr[]" value="' + this.selectedSeats[i].nr + '" />');
		var roomUUID = $('<input type="hidden" name="room-uuid[]" value="' + this.selectedSeats[i].roomUUID + '" />');

		checkItem.append(checkItemHeader, documentFragment2, seatId, seatNr, roomUUID);
		documentFragment.append(checkItem);
	}

	if(arrLen > 1) {
		if(this.gmailNeeded == 1) {
			var primaryMail = $('<div style="text-align:center"><label class="field-label">'+ translator.translate('confWillBeSentTogmail') +'</br> <input type="text" id="prim-mail" class="field-input" data-field="Email"><span class="field-error"></span></label></div>');
		}else {
			var primaryMail = $('<div style="text-align:center"><label class="field-label">'+ translator.translate('confWillBeSentTo') +'</br> <input type="text" id="prim-mail" class="field-input" data-field="Email"><span class="field-error"></span></label></div>');
		}
		documentFragment.append(primaryMail);
	}

	$('#checkout-input-area').append(documentFragment);

	if(arrLen == 1 && this.gmailNeeded == 1) {
		$('#checkout-input-area .field-input[data-field="Email"]').prev().text('Email (Gmail required)');
	}
};

SeatReg.prototype.generateField = function(fieldName) {
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
	var fieldInput = $('<input type="text" name="'+ fieldName +'[]" class="field-input" data-field="' + fieldName+ '" maxlength="100">');
	
	var errorText = $('<span class="field-error">error</span>');
	label.append(fieldInput, errorText);
	return label;
};

SeatReg.prototype.generateCustomField = function(custom) {
	var label = $('<label class="field-label custom-input"><span class="l-text">' + custom.label +  '</span></label>');

	if(custom.type == 'text') {
		var fieldInput = $('<input type="text" name="'+ custom.label +'[]" class="field-input" data-field="' + custom.label + '" data-type="' +  custom.type +'" maxlength="50">');
	}else if(custom.type == 'check') {
		var fieldInput = $('<input type="checkbox" name="'+ custom.label +'[]" class="field-input" data-field="' + custom.label + '" data-type="' +  custom.type + '" value="'+ custom.label +'">');
	}else if(custom.type == 'sel') {
		var fieldInput = $('<select name="'+ custom.label +'[]" class="field-input" data-type="' + custom.type + '"></select>');
		var arrLen = custom.options.length;

		for(var i = 0; i < arrLen; i++) {
			fieldInput.append('<option value="'+ custom.options[i] +'">' + custom.options[i] + '</option>');
		}
	}

	var errorText = $('<span class="field-error">error</span>');
	label.append(fieldInput,errorText);
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
		$('#confirm-dialog-mob-inner').removeClass('zoomOut').addClass('zoomIn');  //bounceInRight
	}else {
		//console.log('dont open');
	}
};

SeatReg.prototype.closeSeatDialog = function() {
	$('#confirm-dialog-mob').css('display','none');
	$('#modal-bg').css('display','none');
};

SeatReg.prototype.paintSeatDialog = function(clickBox) {
	$('#confirm-dialog-mob-hover, #confirm-dialog-mob-legend').empty().css('display','none');
	$('#confirm-dialog-mob-text').empty();
	$('#confirm-dialog-mob-ok').css('display','none');

	var hover = null;
	var legend = null;
	var nr = null;
	var type = 'box';
	var currentRoom = this.rooms[this.currentRoom].room;
	var room = this.rooms[this.currentRoom].room.name;
	var showDialog = false;
	var isSelected = false;

	var jClickBox = $(clickBox);

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
		nr = clickBox.getElementsByTagName('div')[0].firstChild.nodeValue;
		$('#selected-seat-nr').val(nr);
		showDialog = true;
	}

	if(clickBox.hasAttribute('data-status')) {
		type = clickBox.getAttribute('data-status');
		showDialog = true;
	}

	if(clickBox.hasAttribute("data-selectedBox")) {
		isSelected = true;
	}
	
	if(hover != null) {
		$('#confirm-dialog-mob-hover').html('<span class="dialog-hover"></span> ' + '<span class="dialog-hover-text">' + hover + '</span>');
	}
	if(legend != null) {
		$('#confirm-dialog-mob-legend').html('Legend: <div class="dialog-legend-box" style="background-color:' + jClickBox.css('background-color') + '"></div><span class="dialog-legend-text">' + legend + '</span>');
	}
	if(type != 'box') {
		if(!isSelected) {
			if(type == 'rbox' && this.selectedSeats.length < this.seatLimit ) {

				if(this.status == 'run') {
					$('#confirm-dialog-mob-text').html('<div class="add-seat-text"><h5>'+ translator.translate('add_') + ' ' + this.spotName + ' ' + nr + translator.translate('_fromRoom_') + ' ' + room + translator.translate('_toSelection') +'</h5>' + '</div>');
					$('#confirm-dialog-mob-ok').css('display','inline-block');
				}else {
					$('#confirm-dialog-mob-text').html('<div class="add-seat-text"><h5>' + this.spotName + ' ' + nr + translator.translate('_fromRoom_')  + room + '</h5></div>');
				}

			}else if(type == 'tak') {
				$('#confirm-dialog-mob-ok').css('display','none');
				$('#confirm-dialog-mob-text').html('<div class="seat-taken-notify"><h5>'+ translator.translate('this_') + this.spotName + translator.translate('_isOccupied') + '</h5></div>');
			}else if(type == 'bron') {
				$('#confirm-dialog-mob-ok').css('display','none');
				$('#confirm-dialog-mob-text').html('<div class="seat-bron-notify"><h5>' + translator.translate('this_') +  ' ' + this.spotName + translator.translate('_isPendingState') +'</h5>'+ translator.translate('regOwnerNotConfirmed') +'</div>');
			}else if(type == 'rbox' && this.selectedSeats.length >= this.seatLimit ) {
				$('#confirm-dialog-mob-ok').css('display','none');
				$('#confirm-dialog-mob-text').html('<div class="seat-taken-notify">'+ translator.translate('selectionIsFull') +'</div>');
			}
		}else {
			$('#confirm-dialog-mob-text').html('<div class="add-seat-text"><h5>' + capitalizeFirstLetter(this.spotName)  + ' ' + nr + translator.translate('_isAlreadySelected') +'</h5></div>');
		}	
	}
	if(showDialog) {
		return true;
	}else {
		return false;
	}
};

var seatReg = new SeatReg();
seatReg.browserInfo();

if (typeof seatregdemo !== 'undefined') {
    seatReg.demo = true;
}

if($.isEmptyObject(dataReg)) {
	$('body').append('<div class="under-construction-notify"><span class="icon-construction6 index-icon"></span>'+ translator.translate('_regUnderConstruction') +'</div>');

	return false;
}else {
	seatReg.fillLocationObj();
}

$(window).resize(function() {
		rtime = new Date();
	    if (timeout === false) {
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
	var navHeight = $('#room-nav-wrap').outerHeight();
	var cartWidth = $('#seat-cart').outerWidth(true);
	var spaceForMiddleWidth = screenWidth - 20; //how much room for seat map
	var spaceForMiddleHeight = screenHeight - 30 - 30 - navHeight - 20;  // - header height, -legend height, navbar height, -spacing  --default mobile
	var needHorizScroll = false;
	var needVerticScroll = false;

	$('#middle-section').css('margin-left','');

	if(screenWidth >= 1024) {
		//ok i have bigger screen. set legends area left and seatcart right
		var legendWidth = 0;

		if($('#legend-wrapper').is(':visible')) {
			legendWidth = $('#legend-wrapper').outerWidth(true);

			if(legendWidth > cartWidth) {
				$('#seat-cart').css('margin-left', (legendWidth - cartWidth) + 10);
				cartWidth = $('#seat-cart').outerWidth(true);
			}else {
				$('#legend-wrapper').css('margin-right', (cartWidth - legendWidth) + 5);
				legendWidth = $('#legend-wrapper').outerWidth(true);
			}
			spaceForMiddleWidth = spaceForMiddleWidth - legendWidth - cartWidth - 20;
		}else {
			spaceForMiddleWidth = spaceForMiddleWidth - cartWidth * 2;
		}
		
		spaceForMiddleHeight = screenHeight - 30 - navHeight - 30;  //- header height, - navbar height, -footer if needed

		
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
		$('#box-wrap').css('width', spaceForMiddleWidth - 20);
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
	//destroy previous scroll
	if(myScroll != null) {
		myScroll.destroy();
		myScroll = null;
	}

	//do i need to zoom out?
	var needToZoom = false;

	if(seatReg.rooms[seatReg.currentRoom].room[4] > $('#middle-section').width() || seatReg.rooms[seatReg.currentRoom].room[5] > $('#middle-section').height()) {
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

function zoomStart() {
	var w = fitFactor();

	boxWrapSize(w);
}

function boxWrapSize(fitF) {
	var w = seatReg.rooms[seatReg.currentRoom].room[4] * fitF;

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

	    var w = seatReg.rooms[seatReg.currentRoom].room[4] / ($('#middle-section').width() - 20);
	    var h = seatReg.rooms[seatReg.currentRoom].room[5] / ($('#middle-section').height() - 20);
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
	var customField = /^[\s\p{L}]{1,50}$/u;
	var value = inputField.val();

	if(value == '') {
		inputField.next().text(translator.translate('emptyField')).css('display','inline');
		return false;
	}

	switch(inputField.attr('data-field')) {
		case 'FirstName':
			inputField.next().text('').css('display','inline');
	
			break;
		case 'LastName':
			inputField.next().css('display','none');
		
			break;
		case 'Email':
			var useThis = emailReg;
			if(seatReg.gmailNeeded == 1) {
				useThis = gmailReg;
			}

			if(useThis.test(value)) {
				inputField.next().css('display','none');
			}else {
				inputField.next().text(translator.translate('emailNotCorrect')).css('display','inline');
	
				return false;
			}

			break;
		default:
			//custom field validation
			if(customField.test(value)) {				
				inputField.next().css('display','none');	
			}else {	
				inputField.next().text(translator.translate('illegalCharactersDetec')).css('display','inline');	

				return false;	
			}
	}
	return true;
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
				customFieldPack.push(new CustomData($(this).find('.l-text').text(),$(this).find('.field-input').val()));
			}else if(type == 'check') {
				customFieldPack.push(new CustomData($(this).find('.l-text').text(),$(this).find('.field-input').is(":checked")));
			}else if(type == 'sel') {
				customFieldPack.push(new CustomData($(this).find('.l-text').text(),$(this).find('.field-input').find(":selected").val()));
			}	
		});

		sendPack.push(customFieldPack);
	});

	return sendPack;
	
}

function sendData(customFieldBack, regURL) {
	$('#checkout-confirm-btn').css('display','none');
	$('#checkoput-area-inner .ajax-load').css('display','inline-block');

	var mailToSend = null;

	if(seatReg.selectedSeats.length > 1) {
		mailToSend = $('#prim-mail').val();
	}else {
		mailToSend = $('#checkout-input-area .check-item').first().find('.field-input[data-field="Email"]').val();
	}

	customFieldBack = JSON.stringify(customFieldBack);
		
	$.ajax({
		type: 'POST',
		url: ajaxUrl,
		data: $('#checkoput-area-inner').serialize() + '&custom=' + customFieldBack +'&action=' + 'seatreg_booking_submit' + '&c=' + regURL + '&em=' + mailToSend + '&pw=' + $('#sub-pwd').val(),

		success: function(data) {
			$('#checkoput-area-inner .ajax-load').css('display','none');

			var is_JSON = true;
			
			try {
				var resp = $.parseJSON(data);
			} catch(err) {
				is_JSON = false;
			}
			if(is_JSON) {
				if(resp.type == 'ok' && resp.text == 'mail') {	
					$('#email-send').text(mailToSend);
					needMailCheckInfo();
				}else if(resp.type == 'ok' && resp.text == 'bookings-confirmed-status-2') {
					bookingsConfirmedInfo(resp.data, 2);
				}else if(resp.type == 'ok' && resp.text == 'bookings-confirmed-status-1') {
					bookingsConfirmedInfo(resp.data, 1);
				}else if(resp.type == 'error' && resp.text == 'Wrong captcha') {
					$('#captcha-img').replaceWith(resp.data);
					$('#checkout-confirm-btn').css('display','inline-block');
					$('#captcha-text').text(translator.translate('wrongCaptcha'));
				}else if(resp.type == 'error') {
					$('#checkout-area').css('display','none');
					$('#captcha-ref').click();
					$('#error-text').html(resp.text);
					$('#error').css('display','block');
					$('#checkout-confirm-btn').css('display','inline-block');
				}else {
					$('#error-text').text(translator.translate('somethingWentWrong'));
					$('#error').css('display','block');
					$('#checkout-confirm-btn').css('display','inline-block');
				}
			}else {
				$('#checkout-area').css('display','none');
				$('#error-inner').prepend(translator.translate('somethingWentWrong'));
				$('#error').css('display','block');
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

function needMailCheckInfo() {
	$('#checkout-area').css('display','none');
	$('#email-conf').css('display','block');
}

function bookingsConfirmedInfo(data, status) {
	$('.booking-check-url').text(data).attr('href', data);
	$('#checkout-area').css('display','none');
	$('#bookings-confirmed').css('display','block');

	if(status === 1) {
		$('.booking-confirmed-header').text(translator.translate('bookingsConfirmedPending'));
	}else if (status === 2) {
		$('.booking-confirmed-header').text(translator.translate('bookingsConfirmed'));
	}
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

$('#confirm-dialog-mob-ok').on('click', function() {
	seatReg.addSeatToCart();
});

$('#confirm-dialog-mob-cancel').on('click', function() {
	seatReg.closeSeatDialog();
});

$('#room-nav-close').on('click', function() {
	seatReg.closeModal();
});

$('#checkout').on('click', function() {
	seatReg.openCheckOut();
});

$('#checkout-input-area').on('keyup','.field-input', function() {
	validateInput($(this));
});

$('.refresh-btn').on('click', function() {
	window.location.reload();
});

$('#checkoput-area-inner').submit(function(e) {
	e.preventDefault();

	$('#request-error').text('');

	var valid = true;

	$('#checkout-input-area .field-input').each(function() {
		if(!validateInput($(this))) {
			valid = false;
		}
	});

	if($('#captcha-val').val() == '') {
		$('#captcha-val').focus();
		$('#request-error').text('Enter captcha').css('display','block');
		return;
	}

	if(valid) {
		if(!seatReg.demo) {
			sendData(collectData(), qs['c']);
		}else {
			alert('Demo');
		}
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

if(seatReg.rooms != null) {
	seatReg.init();
}

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
});