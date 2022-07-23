(function($) {
	var leftButtonDown = false;		// when left mouse button is down

    $(document).mousedown(function(e){
        // Left mouse button was pressed, set flag
        if(e.which === 1){
        	leftButtonDown = true;
        } 
    });

    $(document).mouseup(function(e){
        // Left mouse button was released, clear flag
        if(e.which === 1){
        	leftButtonDown = false;
        } 
    });

    Object.size = function(obj) {
	    var size = 0, key;
	    for (key in obj) {
	        if(obj.hasOwnProperty(key)){
	        	size++;
	        } 
	    }
	    return size;
	};

	var translator = {
		translate: function(translationKey) {
			if(WP_Seatreg.translations && WP_Seatreg.translations.hasOwnProperty(translationKey)) {
				return WP_Seatreg.translations[translationKey];
			}
		}
	};

	/*
		*------Skeleton class and methods---------
	*/

	//Skeleton grid info. each room has one.
	function Skeleton() {
		this.hasSkeleton = true;
		this.width = 40;	 
		this.height = 40;
		this.countX = 22;
		this.countY = 20;
		this.marginX = 10;
		this.marginY = 10;
		this.buildGrid = 0;
		this.totalWidth = null;
		this.totalHeight = null;

		this.calculateTotals();
	}

	Skeleton.prototype.changeSkeleton = function(sizeX, sizeY, countX, countY, marginX, marginY, grid) {
		this.width = sizeX;	 
		this.height = sizeY;
		this.countX = countX;
		this.countY = countY;
		this.marginX = marginX;
		this.marginY = marginY;
		this.buildGrid = grid;

		this.calculateTotals();
	};

	Skeleton.prototype.calculateTotals = function() {
		this.totalWidth = (this.width + this.marginX) * this.countX;
		this.totalHeight = (this.height + this.marginY) * this.countY;
	};

	/*
		*------Box class and methods---------
	*/

	//box class 12 construct
	function Box(legend, xPos, yPos, xSize, ySize, id, color, hoverText, canIRegister, seat, status, zIndex, price, type) {
		this.legend = legend;
		this.xPosition = xPos;
		this.yPosition = yPos;
		this.width = xSize;
		this.height = ySize;
		this.color = color;
		this.fontColor = '#212529';
		this.hoverText = hoverText;
		this.id = id;
		this.canRegister = canIRegister;
		this.seat = seat;
		this.status = status;
		this.zIndex = zIndex;
		this.price = price;
		this.type = type;
		this.input = '';
		this.inputSize = 16;
		this.lock = false;
		this.password = '';
		this.prefix = '';
	}

	//Change box values. position and size
	Box.prototype.changeValues = function(xPos,yPos,xSize,ySize) {
		this.xPosition = xPos;
		this.yPosition = yPos;
		this.width = xSize;
		this.height = ySize;
		reg.needToSave = true;
	};

	//change size
	Box.prototype.changeSize = function(xSize, ySize) {
		this.width = xSize;
		this.height = ySize;
	};

	//Change width
	Box.prototype.changeWidth = function(newWidth) {
		this.width = newWidth;
	}

	//change position
	Box.prototype.changePosition = function(xPos, yPos) {
		this.xPosition = xPos;
		this.yPosition = yPos;
		reg.needToSave = true;
	};
	Box.prototype.changeZIndex = function(newIndex) {
		this.zIndex = newIndex;
	};

	Box.prototype.changeSeatNr = function(newSeatNr) {
		this.seat = newSeatNr;
	};

	//change color
	Box.prototype.changeColor = function(color) {
		this.color = color;
		this.legend = "noLegend";
		reg.needToSave = true;
	};

	Box.prototype.changeFontColor = function(newFontColor) {
		this.fontColor = newFontColor;
	}

	Box.prototype.changeRegisterStatus = function(newStatus) {
		this.canRegister = newStatus;
		if(newStatus == true) {
			this.color = '#61B329';
			this.legend = "RegSpot";
		}else {
			this.color = '#ccc';
			this.legend = "custom";
		}
	}

	Box.prototype.changePrice = function(price) {
		this.price = price;
	}

	Box.prototype.changeInput = function(newInput) {
		this.input = newInput;
	}

	Box.prototype.changeLock = function(newLock) {
		this.lock = newLock;
	}

	Box.prototype.changePassword = function(newPassword) {
		this.password = newPassword;
	}

	Box.prototype.changeTextBoxInputValues = function($input) {
		var inputFontSize = parseInt($input.css('font-size'), 10);

		this.changeInput($input.val());
		this.setInputFontSize(inputFontSize);
	}

	Box.prototype.calculateInputBoxDimentions = function() {
		$testingBox = $('<span id="font-size-width-test" style="font-size:'+ this.inputSize +'px">'+ this.input +'</span>');
		$('.seatreg-builder-popup').append($testingBox);
		var testElementWidth = $('#font-size-width-test').width();
		var testElementHeight = $('#font-size-width-test').height();
		$('#font-size-width-test').remove();

		return {
			width: testElementWidth + 16,
			height: testElementHeight + 16
		};
	}

	Box.prototype.setInputFontSize = function(newFontSize) {
		this.inputSize = newFontSize;
	}

	Box.prototype.hideTextBoxFontSizeControls = function() {
		$('.build-area .text-box[data-id="'+ this.id +'"]').find('.text-size-control').css('display', 'none');
	}

	Box.prototype.showTextBoxFontSizeControls = function() {
		$('.build-area .text-box[data-id="'+ this.id +'"]').find('.text-size-control').css('display', 'block');
	}

	Box.prototype.renderSeatNr = function() {
		$('.build-area .drag-box[data-id="'+ this.id +'"] .seat-number').text(this.prefix + this.seat);
	};

	Box.prototype.changePrefix = function(newPrefix) {
		this.prefix = newPrefix;

		this.renderSeatNr();
	}

	/*

		*-------Legend class and methods----------
	*/
	function Legend(text, color) {
		this.text = text;
		this.color = color;
	}

	/*

		*-------Room class and methods----------
	*/

	//Room class. Registration can have many rooms.Room hase user made boxes, skeleton info and ...
	function Room(id, title, uuid) {
		this.title = title;		//room title.
		this.uuid = uuid;
		this.initialName = "";
		this.roomId = id;		//for finding in assosiative array
		this.boxes = [];		//stores user made boxes
		this.skeleton = new Skeleton();	//stores skeleton grid info
		this.boxCounter = 0;	//how many boxes
		this.roomText = "";
		this.legends = [];
		this.roomSeatCounter = 0;
		this.roomWidth = 0;
		this.roomHeight = 0;
		this.backgroundImage = null;
		this.backgroundImageWidth = null;
		this.backgroundImageHeight = null;
	}

	Room.prototype.returnRoomData = function() {
		var roomData = {
			skeleton: {
				width: this.skeleton.width,
				height: this.skeleton.height,
				countX: this.skeleton.countX,
				countY: this.skeleton.countY,
				marginX: this.skeleton.marginX,
				marginY: this.skeleton.marginY,
				buildGrid: this.skeleton.buildGrid
			}
		};	
		var roomLegendArray = [];
		var roomLegendsLength = this.legends.length;

		for(var c = 0; c < roomLegendsLength; c++) {
			roomLegendArray.push({
				text: this.legends[c].text,
				color: this.legends[c].color  
			});
		}

		roomData['room'] = {
			id: this.roomId,
			uuid: this.uuid,
			name: this.title,
			text: this.roomText,
			legends: roomLegendArray,
			width: this.roomWidth + 10,
			height: this.roomHeight + 10,
			seatCounter: this.roomSeatCounter,
			backgroundImage: this.backgroundImage
		}

		roomData['boxes'] = [];

		var arrLength = this.boxes.length;
		for(var i = 0; i < arrLength; i++) {
			var canReg = this.boxes[i].canRegister;
			if(canReg) {
				canReg = "true";
			}else{
				canReg = "false";
			}
			roomData['boxes'].push({
				legend: this.boxes[i].legend,
				xPosition: Math.round(this.boxes[i].xPosition),
				yPosition: Math.round(this.boxes[i].yPosition),
				width: Math.round(this.boxes[i].width),
				height: Math.round(this.boxes[i].height),
				color: this.boxes[i].color,
				fontColor: this.boxes[i].fontColor,
				hoverText: this.boxes[i].hoverText.replace(/<br>/g,'^'),
				id: this.boxes[i].id,
				canRegister: canReg,
				seat: this.boxes[i].seat,
				status: 'noStatus',
				zIndex: this.boxes[i].zIndex,
				price: this.boxes[i].price,
				type: this.boxes[i].type,
				input: this.boxes[i].input,
				inputSize: this.boxes[i].inputSize,
				lock: this.boxes[i].lock,
				password: this.boxes[i].password,
				prefix: this.boxes[i].prefix,
			});
		}

		return roomData;
	};

	//find if room hase a box. return box location in Room.boxes array. if no box then returns false
	Room.prototype.findBox = function(id) {
		var arrLength = this.boxes.length;
		for(var i = 0; i < arrLength; i++) {
			if(this.boxes[i].id == id) {
				return i;
			}
		}

		return false;
	};

	Room.prototype.findAndReturnBox = function(id) {
		var arrLength = this.boxes.length;
		for(var i = 0; i < arrLength; i++) {
			if(this.boxes[i].id == id) {
				return this.boxes[i];
			}
		}

		return false;
	}

	//adds box to room
	Room.prototype.addBox = function(title,posX,posY,sizeX,sizeY,id,color,hoverText,canIRegister,status,zIndex, type) {
		if(canIRegister) {
			this.roomSeatCounter++;
		}

		this.boxes.push(new Box(title,posX,posY,sizeX,sizeY,id,color,hoverText,canIRegister,this.roomSeatCounter,status,zIndex, 0, type));
		reg.needToSave = true;
		this.boxCounter++;
		reg.regBoxCounter++;
		
		$('.room-box-counter').text(this.boxes.length);
	};

	//add box to room from server data
	Room.prototype.addBoxS = function(title,posX,posY,sizeX,sizeY,id,color,hoverText,canIRegister,status,boxZIndex, price, type, input, fontColor, inputSize, lock, password, prefix) {
		if(canIRegister) {
			this.roomSeatCounter++;
		}
		var box = new Box(title,posX,posY,sizeX,sizeY,id,color,hoverText,canIRegister,this.roomSeatCounter,status,boxZIndex, price, type);
		box.input = input;
		box.fontColor = fontColor;
		box.inputSize = inputSize;
		box.password = password;
		box.lock = lock;
		box.prefix = prefix ? prefix : '';
		this.boxes.push(box);
		this.boxCounter++;
	};

	//find last bron or taken seat and return it seat number
	Room.prototype.lastBronOrTaken = function() {
		var arrLength = this.boxes.length;
		var lastNr = 0;

		for(var i = 0; i < arrLength; i++) {
			if(this.boxes[i].status == 'bronRegister' || this.boxes[i].status == 'takenRegister') {
				if(this.boxes[i].seat > lastNr) {
					lastNr = this.boxes[i].seat;
				}
			}
		}

		return lastNr;
	};

	Room.prototype.removeLegendFromRoom = function(legend) {
		var arrLength = this.legends.length;

		for(var i = 0; i < arrLength; i++) {
			if(this.legends[i].text == legend) {
				this.legends.splice(i, 1);
				reg.needToSave = true;

				break;
			}
		}

		reg.createLegendBox();
	};

	//deletes box
	Room.prototype.deleteBox = function(id) {
		var location = this.findBox(id);
		var legendCheck = null;

		if(location !== false) {
			if(this.boxes[location].legend != "noLegend") {
				legendCheck = this.boxes[location].legend;
			}
			if(this.boxes[location].canRegister != true || this.boxes[location].status === 'noStatus') {
				this.boxes.splice(location, 1);
				reg.needToSave = true;
				$('.drag-box[data-id="' + id +'"]').remove();
				$('.room-box-counter').text(this.boxes.length);

				if(legendCheck != null) {
					if(reg.canRemoveLegendRoom(legendCheck)) {
						this.removeLegendFromRoom(legendCheck);
					}
				}
			} else {
				$('.build-area-wrapper .active-box').removeClass('active-box');

				if($('html').hasClass('cssanimations')) {
					if(!reg.animationRunning) {
						reg.animationRunning = true;
						$('#build-section-message-wrap').addClass('animated bounceIn').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function() {
							$(this).removeClass('animated bounceIn');
							reg.animationRunning = false;
						});
					}
				}else {
					if(!reg.animationRunning) {
						reg.animationRunning = true;
						$('#build-section-message-wrap').css('display','none').show('bounce',{distance: 10, times: 4}, 1200, function() {
							reg.animationRunning = false;
						});
					}
					
				}
			}
		}
	};

	//do i have bron or taken spots in this room?
	Room.prototype.bronOrRegCheck = function() {
		var arrLength = this.boxes.length;

		for(var i = 0; i < arrLength; i++) {
			if(this.boxes[i].status == "takenRegister" || this.boxes[i].status == "bronRegister") {
				
				return true;
			}
		}

		return false;
	};

	Room.prototype.correctRoomBoxesIndex = function() {
		var boxes = $('.build-area-wrapper .drag-box');
		var arrLength = this.boxes.length;

		for(var i = 0; i < arrLength; i++) {
			var targetIndex = parseInt(boxes.filter('[data-id="'+ this.boxes[i].id +'"]').css('z-index'));
			this.boxes[i].changeZIndex(targetIndex);
		}
	};

	Room.prototype.removeLegendFromRoomBoxes = function(legendText) {
		var roomBoxLength = this.boxes.length;

		for(var i = 0; i < roomBoxLength; i++) {
			if(this.boxes[i].legend === legendText) {
				this.boxes[i].legend = "noLegend";
			}
		}
	};

	/*
		*------Registration class and methods---------
	*/

	function Registration() {
		this.rooms = {};		//rooms obj. stores room objects
		this.roomLocator = 1;	//helps to find room in rooms array.
		this.currentRoom = 1;	//what room is selected
		this.roomLabel = 1;
		this.activeBoxArray = [];	//user select box or boxes
		this.action = 1; //mouse action: 1 = regular, action 2 = creator, action 3 = delete, action 4 = lasso
		this.needMultiDrag = false;
		this.canOpenColor = true;
		this.existingHover = []; //if bubble text is already present. store here
		this.bigTitle = "Suur pealkiri";
		this.allLegends = []; //echo room has its legends array, but here are all legends but together
		this.regBoxCounter = 1;
		this.regLang = 'est';
		this.animationRunning = false; //registration box delete limitation animation(room has bron or taken)
		this.animationRunning2 = false; //cant add no more boxes limitation animation
		this.animationRunning3 = false; //pro to free accound. need to make changes animation
		this.animationRunning4 = false; //registration add room limitation animation
		this.needToChangeStructure = false;
		this.needToSave = false;  //if user makes changes this will be true. when saved this will be false
		this.roomNameChange = {};  //if room name got changed. store old and new here
		this.settings = {};
	}

	Registration.prototype.clearRegistrationData = function() {
		this.rooms = {};		//rooms obj. stores room objects
		this.roomLocator = 1;	//helps to find room in rooms array.
		this.currentRoom = 1;	//what room is selected
		this.roomLabel = 1;
		this.activeBoxArray = [];	//user select box or boxes
		this.action = 1; //mouse action: 1 = regular, action 2 = creator, action 3 = delete, action 4 = lasso
		this.needMultiDrag = false;
		this.canOpenColor = true;
		this.existingHover = []; //if bubble text is already present. store here
		this.bigTitle = "Suur pealkiri";
		this.allLegends = []; //echo room has its legends array, but here are all legends but together
		this.regBoxCounter = 1;
		this.regLang = 'est';
		this.animationRunning = false; //registration box delete limitation animation(room has bron or taken)
		this.animationRunning2 = false; //cant add no more boxes limitation animation
		this.animationRunning3 = false; //pro to free accound. need to make changes animation
		this.animationRunning4 = false; //registration add room limitation animation
		this.needToChangeStructure = false;
		this.needToSave = false;  //if user makes changes this will be true. when saved this will be false
		this.roomNameChange = {};  //if room name got changed. store old and new here

		$('#room-selection-wrapper').empty();
	};

	Registration.prototype.setRoomImage = function(imgLog, size) {
		var dim = size.split(",");
		this.rooms[this.currentRoom].backgroundImage = imgLog;
		this.rooms[this.currentRoom].backgroundImageWidth = parseInt(dim[0]);
		this.rooms[this.currentRoom].backgroundImageHeight = parseInt(dim[1]);
		$('.room-image').remove();

		var bgImg = $('<img class="room-image" src="'+ window.WP_Seatreg.plugin_dir_url +'uploads/room_images/' + seatreg.selectedRegistration  + '/' + imgLog + '" />');
		$('.build-area').append(bgImg);
	};

	Registration.prototype.removeCurrentRoomImage = function() {
		this.rooms[this.currentRoom].backgroundImage = null;
		this.rooms[this.currentRoom].backgroundImageWidth = null;
		this.rooms[this.currentRoom].backgroundImageHeight = null;
		$('.room-image').remove();
	};

	Registration.prototype.removeImgAllRooms = function(img) {
		for (var property in this.rooms) {
			if (this.rooms.hasOwnProperty(property)) {
				if(this.rooms[property].backgroundImage == img) {
					this.rooms[property].backgroundImage = null;
				}
			}
		}
	};

	Registration.prototype.generatePrevUploadedImgMarkup = function() {
		if(window.seatreg.uploadedImages && window.seatreg.uploadedImages.length > 0) {
			window.seatreg.uploadedImages.forEach(function(uploaded) {
				var $imgWrap = $("<div class='uploaded-image-box'></div");
				$imgWrap.append("<img src='" + window.WP_Seatreg.plugin_dir_url + "uploads/room_images/" + window.seatreg.selectedRegistration + "/" + uploaded.file + "' class='uploaded-image' />");
				$imgWrap.append("<span class='add-img-room' data-img='"+ uploaded.file +"' data-size='" + uploaded.size[0] + "," + uploaded.size[1] + "'><span class='glyphicon glyphicon-ok' aria-hidden='true'></span>"+ translator.translate('addToRoomBackground') +"</span>");
				$imgWrap.append("<span class='up-img-rem' data-img='"+ uploaded.file +"'><span class='glyphicon glyphicon-remove' aria-hidden='true'></span> "+ translator.translate('remove') +"</span>");

				$('#uploaded-images').append($imgWrap);
			});
		}
	};

	Registration.prototype.changeHoverText = function(newHover) {
		if(this.activeBoxArray.length > 0) {
			var arrLength = this.activeBoxArray.length;

			for(var i = 0; i < arrLength; i++) {
				var index = this.rooms[this.currentRoom].findBox(this.activeBoxArray[i]);

				if(index !== false) {
					var box = $('.build-area .drag-box[data-id="' + this.activeBoxArray[i] + '"]');
					var canRegistraBox = this.rooms[this.currentRoom].boxes[index].canRegister; 

					if(newHover != '') {
						this.rooms[this.currentRoom].boxes[index].hoverText = newHover;
						var newHoverToShow = newHover;

						if(canRegistraBox) {
							newHoverToShow = 'ID: ' + this.rooms[this.currentRoom].boxes[index].id + '<br>' + newHoverToShow;
						}

						if(box.data('powertip')) {
							box.attr('data-powertip', newHoverToShow).data('powertip', newHoverToShow).addClass('box-hover').removeClass('no-bubble');

						}else {
							box.attr('data-powertip', newHoverToShow).addClass('box-hover').removeClass('no-bubble').powerTip({
								fadeInTime: 0,
								fadeOutTime:0,
								intentPollInterval: 10,
								placement: 's',
								manual: true
							});
						}
						
					}else {
						this.rooms[this.currentRoom].boxes[index].hoverText = "nohover";
						box.removeClass('box-hover');
					}
				}else {
					alert(translator.translate('hoverError'));
				}
			}

			if(newHover == '') {
				alertify.success(translator.translate('hoverDeleteSuccess'));
			}else {
				alertify.success(translator.translate('hoverTextAdded'));
				this.needToSave = true;
			}
		}
	};

	//add new legend to registration
	Registration.prototype.addLegendBox = function(text, color) {
		var arrLength = this.allLegends.length;

		for(var i = 0; i < arrLength; i++) {
			if(this.allLegends[i].text == text) {
				$('#new-legend-text').focus();
				alertify.set({ 
					labels: {
				    	ok     : translator.translate('ok'),
				    	cancel: translator.translate('cancel')
					},
					buttonFocus: "ok"  
				});

				alertify.alert(translator.translate('legendNameTaken'));

				return false;
			}
			if(this.allLegends[i].color == color) {
				alertify.set({ 
					labels: {
				    	ok     : translator.translate('ok'),
				    	cancel: translator.translate('cancel')
					},
					buttonFocus: "ok"  
				});

				alertify.alert(translator.translate('legendColorTaken'));
				return false;
			}
		}

		this.allLegends.unshift(new Legend(text,color));
		this.needToSave = true;
		this.updateLegendSelect();
		return true;		
	};

	//adds existing legend to box/boxes
	Registration.prototype.changeLegend = function(legend) {
		var oldLegend = [];
		var arrLength = this.activeBoxArray.length;
		var addedLegendToRoom = false;
		var color = null;

		//get legend color
		for(var j = 0; j < this.allLegends.length; j++){
			if(this.allLegends[j].text == legend){
				color = this.allLegends[j].color;
				
				break;
			}
		}

		for(var i = 0; i < arrLength; i++) {
			var index = this.rooms[this.currentRoom].findBox(this.activeBoxArray[i]);

			if(index !== false) {
				if(oldLegend.length == 0) {
					oldLegend.push(this.rooms[this.currentRoom].boxes[index].legend);
				}
				
				this.rooms[this.currentRoom].boxes[index].legend = legend;
				this.rooms[this.currentRoom].boxes[index].color = color;
				this.needToSave = true;
				
				$('.build-area .drag-box[data-id="' + this.rooms[this.currentRoom].boxes[index].id + '"]').css('background-color',color);

				if(!addedLegendToRoom) {	//add legend to room
					this.addedLegendToRoom(legend,color);
					addedLegendToRoom = true;
				}

			} else {
				alert(translator.translate('legendChangeError'));
			}

		}

		alertify.success(translator.translate('legendAddedTo') + ' ' + arrLength + ' ' + translator.translate('boxes'));
		this.afterColorChange(oldLegend);
		this.createLegendBox();

	};

	//info for legend and color dialogs. 
	Registration.prototype.activeBoxesInfo = function() {
		var arrLength = this.activeBoxArray.length;

		var activeBoxesInfoString = '';
		if(arrLength === 1) {
			activeBoxesInfoString = translator.translate('youHaveSelected') + ' ' + arrLength + ' ' + translator.translate('box');
		}else if (arrLength > 0) {
			activeBoxesInfoString = translator.translate('youHaveSelected') + ' ' + arrLength + ' ' + translator.translate('boxes');
		}else {
			activeBoxesInfoString = translator.translate('noBoxesSelected');
		}

		return activeBoxesInfoString;
	};

	Registration.prototype.addedLegendToRoom = function(legend,color) {
		var arrLength = this.rooms[this.currentRoom].legends.length;
		var alreadyExists = false;

		for(var i = 0; i < arrLength; i++) {
			if(this.rooms[this.currentRoom].legends[i].text == legend) {
				alreadyExists = true;
				break;
			}
		}

		if(!alreadyExists) {
			this.rooms[this.currentRoom].legends.push(new Legend(legend,color));
		}
	};

	//remove legend from registration (allLegends array and in each room)
	Registration.prototype.removeLegend = function(legendText) {
		var arrLength = this.allLegends.length;

		for(var i = 0; i < arrLength; i++) {
			if(this.allLegends[i].text == legendText) {
				this.allLegends.splice(i, 1);
				break;
			}
		}

		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		    	var alength = this.rooms[property].legends.length;

		    	for(var i = 0; i < alength; i++) {

		    		if(this.rooms[property].legends[i].text == legendText){
		    			this.rooms[property].legends.splice(i,1);
		    			break;
		    		}
		    	}
		    }
		}
		this.needToSave = true;
		this.removeLegendFromBoxes(legendText);
		this.updateLegendSelect();
		this.createLegendBox();
	};

	//remove legend from all boxes
	Registration.prototype.removeLegendFromBoxes = function(legendText) {
		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
				this.rooms[property].removeLegendFromRoomBoxes(legendText);
		    }
		}
	};

	//will draw legend boxes
	Registration.prototype.createLegendBox = function() {
		$('.legends').empty();

		for(var j = 0; j < 2; j++) {
			var colorBox = $('<div>').addClass('legend-box');
			var textSpan = $('<span>').addClass('legend-text'); 

			switch(j) { //case 0: registreerimise koht. case 1: broneeritud koht . case 2: taken place
				case 0:
					colorBox.css({
						'background-color':'yellow',
					}).addClass('legend-box-circle');
					textSpan.text(translator.translate('pendingSeat'));
					break;

				case 1:
					colorBox.css('background-color','red').addClass('legend-box-circle');
					textSpan.text(translator.translate('confirmedSeat'));
					break;
			}
			$('.legends').append(colorBox,textSpan);
		}

		var arrLength = this.rooms[this.currentRoom].legends.length;

		for(var i = 0; i < arrLength; i++) {			
			var text = this.rooms[this.currentRoom].legends[i].text;
			var lcolor = this.rooms[this.currentRoom].legends[i].color;
			var colorBox = $('<div>').addClass('legend-box').css({
				backgroundColor: lcolor
			});
			var textSpan = $('<span>').addClass('legend-text').text(text);
			$('.legends').append(colorBox,textSpan);
		}
	};

	//do i have box in acrive room with legend
	Registration.prototype.canRemoveLegendRoom = function(legend) {
		var arrLength = this.rooms[this.currentRoom].boxes.length;

		for(var i = 0; i < arrLength; i++) {
			if(this.rooms[this.currentRoom].boxes[i].legend == legend) {
				return false;
			}
		}

		return true;
	};

	//change legend select element. add options
	Registration.prototype.updateLegendSelect = function() {
		var arrLength = this.allLegends.length;
		$('.legend-select').empty();
		$('.legend-select-room').empty();

		for(var i = 0; i < arrLength; i++) {
			$('.legend-select').append($('<option>').text(this.allLegends[i].text));
		}

		var arrLength2 = this.rooms[this.currentRoom].legends.length;

		for(var i = 0; i < arrLength2; i++) {
			$('.legend-select-room').append($('<option>').text(this.rooms[this.currentRoom].legends[i].text));
		}
	};

	//check if active boxes have hover text already. if so then add text to existingHover array
	Registration.prototype.checkBubbles = function() {
		this.existingHover.length = 0;
		var arrLength = this.activeBoxArray.length;

		if(arrLength == 0) {
			alertify.set({ 
				labels: {
			    	ok     : translator.translate('ok'),
				},
				buttonFocus: "ok"  
			});
			var hoverGuide = '<div><div class="guide-block">'+ translator.translate('toSelectOneBox_') +'<div class="guide-item guide-item-mouse"></div></div><br><div class="guide-block"> '+ translator.translate('toSelectMultiBox_') +' <div class="guide-item guide-item-lasso"></div></div>';
			alertify.alert(translator.translate('selectBoxesToAddHover') + hoverGuide);

			return;
		}
		
		for(var i = 0; i < arrLength; i++) {
			var index = this.rooms[this.currentRoom].findBox(this.activeBoxArray[i]);

			if(index !== false) {
				if(this.rooms[this.currentRoom].boxes[index].hoverText != "nohover") {
					this.existingHover.push(this.rooms[this.currentRoom].boxes[index].hoverText);
				}
			}else {
				alert(translator.translate('hoverError'));
			}
		}		
	};

	//adds new room object to registration. new Room object to assosiative array. 1: firstRoom, 2: secondRoom
	Registration.prototype.addRoom = function(ignoreLimit,boxIndexSave,buildSkeleton, uuid){
		var regScope = this;
	
		if(boxIndexSave) {
			this.rooms[this.currentRoom].correctRoomBoxesIndex();
		}

		if(ignoreLimit == false) {
			this.needToSave = true;
		}
		
		this.rooms[this.roomLocator] = new Room(
			this.roomLocator,
			this.roomLocator + ' ' + translator.translate('room'),
			uuid
		);
		$('#active-room').removeAttr('id');
		$('#build-head-stats-3 .room-counter').text(Object.size(this.rooms));

		//set mouse action back to 1 or 6 for touch device

		if($('html').hasClass('touch')) {
			//add mouse-option
			$('#mouse-option-active').removeAttr('id');
			$('.mouse-action-boxes .action6').attr('id','mouse-option-active');
			$('.build-area-wrapper').removeAttr('data-cursor');
			reg.action = 6;
		}else {
			$('#mouse-option-active').removeAttr('id');
			$('.mouse-action-boxes .action1').attr('id','mouse-option-active');
			$('.build-area-wrapper').removeAttr('data-cursor');
			reg.action = 1;
		}

		this.roomLabel = $('#room-selection-wrapper .room-selection').length + 1;
		$('<div>').addClass('room-selection').attr({
			'id': 'active-room',
			'data-room-location': regScope.roomLocator
		}).text(regScope.rooms[regScope.roomLocator].title).on('click', function() {
			var loadingImg = $('<img>', {
				"src": window.WP_Seatreg.plugin_dir_url + "img/loading.png",
				"id": "loading-img"
			});

			var imgWrap = $('<div>', {
				"id": "build-area-loading-wrap"
			}).append(loadingImg, "<span class='loading-text'>"+ translator.translate('loading') +"</span>");

			var changeScope = $(this);

			$('#build-section').append(imgWrap);
			
			setTimeout(function(){
				regScope.changeRoom(changeScope.attr('data-room-location'), changeScope, false, true);
			}, 300);
		}).appendTo('#room-selection-wrapper');

		this.roomLabel++;
		this.currentRoom = this.roomLocator;
		this.roomLocator++;
		clearBuildArea();

		if(buildSkeleton) {
			this.buildSkeleton();
			this.createLegendBox();
		}
		
		this.canOpenColor = true;
		$('.palette-call').removeAttr('id');
		$('.room-title-name').text(this.rooms[this.currentRoom].title);
			
		
	};

	Registration.prototype.deleteCurrentRoom = function() {
		var size = Object.size(this.rooms);

		if(size == 1) {
			alert(translator.translate('oneRoomNeeded'));
		}else if(size > 1) {
			delete this.rooms[this.currentRoom];
			this.needToSave = true;
			this.activeBoxArray.length = 0;
			
			$('#build-head-stats-3 .room-counter').text(Object.size(this.rooms));
			$('#room-selection-wrapper .room-selection[data-room-location="' + this.currentRoom + '"]').remove();

			var newRoomElem = $('#room-selection-wrapper .room-selection').first();
			this.changeRoom(newRoomElem.attr('data-room-location'), newRoomElem, false, false);
		}
	};

	//check if room name exists in registration. return true if found. false if not
	//don't include current room 
	Registration.prototype.roomNameExists = function(roomName) {
		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
				if(
					typeof this.rooms[property].title !== 'undefined' && 
					this.rooms[property].title.toLowerCase() == roomName.toLowerCase() && 
					this.rooms[property].roomId !== this.currentRoom
				) {
					return true;
		        }  
		    }
		}

		return false;
	};

	//find room width and height of all rooms
	Registration.prototype.roomWidthAndHeight = function() {
		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		    	var arrLen = this.rooms[property].boxes.length;
		    	var roomWidth = 0;
				var roomHeight = 0;

				for(var i = 0; i < arrLen; i++) {
					if(this.rooms[property].boxes[i].width + this.rooms[property].boxes[i].xPosition > roomWidth) {
						roomWidth = this.rooms[property].boxes[i].width + this.rooms[property].boxes[i].xPosition;
					}

					if(this.rooms[property].boxes[i].height + this.rooms[property].boxes[i].yPosition > roomHeight) {
						roomHeight = this.rooms[property].boxes[i].height + this.rooms[property].boxes[i].yPosition;
					}
				}

				this.rooms[property].roomWidth = roomWidth;
				this.rooms[property].roomHeight = roomHeight;

				if(this.rooms[property].backgroundImage !== null) {
					if(this.rooms[property].roomWidth < this.rooms[property].backgroundImageWidth) {
						this.rooms[property].roomWidth = this.rooms[property].backgroundImageWidth;
					}
					if(this.rooms[property].roomHeight < this.rooms[property].backgroundImageHeight) {
						this.rooms[property].roomHeight = this.rooms[property].backgroundImageHeight;
					}
				}
		    }
		}
	};

	Registration.prototype.legendNameExists = function(legendName) {
		var legendArrayLength = this.allLegends.length;

		for(var i = 0; i < legendArrayLength; i++) {
			if(this.allLegends[i].text == legendName) {
				return true;
			}
		}

		return false;
	};

	Registration.prototype.legendColorExists = function(legendColor) {
		var legendArrayLength = this.allLegends.length;

		for(var i = 0; i < legendArrayLength; i++) {
			if(this.allLegends[i].color == legendColor) {
				return true;
			}
		}

		return false;
	};

	Registration.prototype.initToolTip = function() {
		$('.build-area-wrapper .box-hover').powerTip({
			fadeInTime: 0,
			fadeOutTime: 0,
			intentPollInterval: 10,
			placement: 's',
			manual: true
		});
	};

	Registration.prototype.changeRoom = function(id, element, isInit, lIndexCheck) {		
		if(lIndexCheck) {
			this.rooms[this.currentRoom].correctRoomBoxesIndex();
		}

		if(id != this.currentRoom || isInit == true) {
			$('#build-area-loading-wrap').remove();
			this.activeBoxArray.length = 0;
			this.showClickControls();
			this.canOpenColor = true;
			$('.palette-call').removeAttr('id');
			this.removeSelectableScroll();
			$('#active-room').removeAttr('id');
			element.attr('id','active-room');
			$('#mouse-option-active').removeAttr('id');

			//set mouse action back to 1 or 6 for touch device

			if($('html').hasClass('touch')) {
				//add mouse-option
				$('.mouse-action-boxes .action6').attr('id','mouse-option-active');
				$('.build-area-wrapper').removeAttr('data-cursor');
				this.action = 6;

			}else {
				$('.mouse-action-boxes .action1').attr('id','mouse-option-active');
				$('.build-area-wrapper').removeAttr('data-cursor');
				this.action = 1;
				$('.build-area-wrapper').attr('data-cursor','1');
			}
			
			clearBuildArea();  //removes boxes
			reg.currentRoom = id;
			this.buildSkeleton();  //builds skeleton grid
			this.buildBoxes();	//builds boxes if it finds some....
			this.createLegendBox();
			this.initToolTip();
		
			$('.room-title-name').text(this.rooms[this.currentRoom].title);
			$('.room-box-counter').text(this.rooms[this.currentRoom].boxes.length);
			$('#build-section-message-wrap').css('display','none');
			
		}else {
			$('#build-area-loading-wrap').remove();
			alertify.set({ 
				labels: {
			    	ok     : translator.translate('ok'),
			    	cancel: translator.translate('cancel')
				},
				buttonFocus: "ok"  
			});
			alertify.alert(translator.translate('alreadyInRoom'));
		}
	};

	//for changing room skeleton values
	Registration.prototype.updateSkeleton = function(sizeX,sizeY,countX,countY,marginX,marginY,grid) {
		this.rooms[this.currentRoom].skeleton.changeSkeleton(sizeX, sizeY, countX, countY, marginX, marginY, grid);
	};

	//changes box values (position and size). finds if box is in room. then calls box changeValues method.
	Registration.prototype.changeBox = function(id,xPos,yPos,xSize,ySize) {
		var locationIndex = this.rooms[this.currentRoom].findBox(id);

		if(locationIndex !== false) {
			this.rooms[this.currentRoom].boxes[locationIndex].changeValues(xPos,yPos,xSize,ySize);
		}else {
		}
	};

	//changes box width,height on box
	Registration.prototype.changeBoxSize = function(id,xSize,ySize) {
		var locationIndex = this.rooms[this.currentRoom].findBox(id);

		if(locationIndex !== false) {
			this.rooms[this.currentRoom].boxes[locationIndex].changeSize(xSize,ySize);
		}else {
		}
	};

	//changes box position on box
	Registration.prototype.changeBoxPosition = function(id,xPos,yPos) {
		var locationIndex = this.rooms[this.currentRoom].findBox(id);

		if(locationIndex !== false) {
			this.rooms[this.currentRoom].boxes[locationIndex].changePosition(xPos,yPos);
		}else {
	
		}
	};

	//Change background color of a box. In case of text-box change font color
	Registration.prototype.changeBoxColor = function(colorRGBA) {
		if(this.action == 1) {
			var box = this.rooms[this.currentRoom].findAndReturnBox(this.activeBoxArray[0]);

			if(box !== false) {
				if(box.type === 'text-box') {
					$('.build-area').find("[data-id='" + this.activeBoxArray[0] +"'] .text-box-input").css('color', colorRGBA);
					box.changeFontColor(colorRGBA);
				}else {
					var oldLegend = [box.legend];

					$('.build-area').find("[data-id='" + this.activeBoxArray[0] +"']").css('background-color', colorRGBA);
					box.changeColor(colorRGBA);
					this.afterColorChange(oldLegend);
				}
			}
		}else if(this.action == 4) {
			var activeBoxesLength = this.activeBoxArray.length;
			var oldLegends = [];  //store box legends before new color

			for(var i = 0; i < activeBoxesLength; i++) {
				var box = this.rooms[this.currentRoom].findAndReturnBox(this.activeBoxArray[i]);

				if(box !== false) {
					if(box.type === 'text-box') {
						$('.build-area').find("[data-id='" + this.activeBoxArray[i] +"'] .text-box-input").css('color', colorRGBA);
						box.changeFontColor(colorRGBA);
					}else {
						oldLegends.push(box.legend);
						box.changeColor(colorRGBA);
						$('.build-area').find("[data-id='" + this.activeBoxArray[i] + "']").css('background-color', colorRGBA);
					}
				}
			}
			this.afterColorChange(oldLegends);
		}
	};

	//should i remove legend/legends from active room when color got changed on box. triggered buy color palette. change box color and cahnge legend
	Registration.prototype.afterColorChange = function(legendArray) {
		var arrLength = legendArray.length;

		for(var i = 0; i < arrLength; i++) {
			if(this.canRemoveLegendRoom(legendArray[i])) {
				this.rooms[this.currentRoom].removeLegendFromRoom(legendArray[i]);
			}
		}	
	};

	Registration.prototype.changeBoxRegisterStatus = function() {
		var arrLength = this.activeBoxArray.length;

		if(arrLength == 0) {
			alertify.alert("Vali registreerimis koht/kohad mida muuta tavalisteks kastideks");
			return;
		}
		if(!this.rooms[this.currentRoom].bronOrRegCheck()) {
			for(var i = 0; i < arrLength; i++) {
				var index = this.rooms[this.currentRoom].findBox(this.activeBoxArray[i]);

				if(index !== false) {
					var location = this.rooms[this.currentRoom].boxes[index];

					if(location.legend == 'RegSpot') {
						location.changeRegisterStatus(false);
						this.rooms[this.currentRoom].roomSeatCounter--;
						location.seat = 0;
						location.legend = 'custom';
						$('.build-area .drag-box[data-id="' + this.activeBoxArray[i] + '"]').removeClass('can-register').addClass('no-register').removeAttr('data-seatnr').css('background-color', '#ccc').find('.seat-number').text('');
					}
				}
			}
		}else {
			alertify.alert("Kui ruumis on broneeritud/kinnitatud kohti siis registreerimiskohti tavakastiks muuta ei saa.");
		}
	};

	//cahnges legend name to different name
	Registration.prototype.changeLegendTo = function(oldLegend, newLegend) {
		//first allLegends array
		var arrlength = this.allLegends.length;

		for(var i = 0; i < arrlength; i++) {
			if(this.allLegends[i].text == oldLegend) {
				this.allLegends[i].text = newLegend;
				break;
			}
		}

		//all rooms and boxes
		var roomsLength = Object.size(this.rooms);

		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		    	var roomLegendsLength = this.rooms[property].legends.length;

		    	//change legend in room
		    	for(var i = 0; i < roomLegendsLength; i++) {
		    		if(this.rooms[property].legends[i].text == oldLegend) {
		    			this.rooms[property].legends[i].text = newLegend;
		    			break;
		    		}
		    	}
		    	//and change legend in box
		    	var roomBoxLength = this.rooms[property].boxes.length;

		    	for(var j = 0; j < roomBoxLength; j++) {
					if(this.rooms[property].boxes[j].legend == oldLegend) {
						this.rooms[property].boxes[j].legend = newLegend;
					}
				}
		    }
		}

		this.needToSave = true;
		this.createLegendBox();
		alertify.success(translator.translate('legendNameChanged'));
	};

	Registration.prototype.changeLegendColorTo = function(legendName, newColor) {
		//first allLegends array
		var arrlength = this.allLegends.length;

		for(var i = 0; i < arrlength; i++) {
			if(this.allLegends[i].text == legendName) {
				this.allLegends[i].color = newColor;
				break;
			}
		}

		//all rooms and boxes
		var roomsLength = Object.size(this.rooms);

		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
				var roomLegendsLength = this.rooms[property].legends.length;
				
		    	//change legend in room
		    	for(var i = 0; i < roomLegendsLength; i++) {
		    		if(this.rooms[property].legends[i].text == legendName) {
		    			this.rooms[property].legends[i].color = newColor;
		    			break;
		    		}
				}
				
		    	//and change legend in box
		    	var roomBoxLength = this.rooms[property].boxes.length;

		    	for(var j = 0; j < roomBoxLength; j++) {
					if(this.rooms[property].boxes[j].legend == legendName) {
						this.rooms[property].boxes[j].color = newColor;
					}
				}
		    }
		}
		this.needToSave = true;
		this.createLegendBox();
	};

	Registration.prototype.reColorLegendBoxes = function(legendName,newColor) {
		var curRoomBoxLength = this.rooms[this.currentRoom].boxes.length;

		for(var i = 0; i < curRoomBoxLength; i++) {
			if(this.rooms[this.currentRoom].boxes[i].legend == legendName) {
				$('.build-area-wrapper .' + this.rooms[this.currentRoom].boxes[i].id).css('background-color',newColor);
			}
		}
		alertify.success(translator.translate('legendColorChanged'));
	};

	//deletes box or boxes which are active. in activeboxes array. for mouse 1 and 4.
	Registration.prototype.deleteBoxes = function() {
		if(this.action == 1) {	//normal mouse delete
			if(this.activeBoxArray.length == 0) {	//no boxes selected
				var delGuide = '<div><div class="guide-block">'+ translator.translate('toSelectOneBox_') +' <div class="guide-item guide-item-mouse"></div></div><br><div class="guide-block">'+ translator.translate('toSelectMultiBox_') +'<div class="guide-item guide-item-lasso"></div></div>';
				
				alertify.set({ 
					labels: {
				    	ok     : translator.translate('ok'),
				    	cancel: translator.translate('cancel')
					},
					buttonFocus: "ok"  
				});

				alertify.alert('<span class="bold-text">'+ translator.translate('selectBoxesToDelete') +'</span>' + delGuide);

				return;
			}

			if(this.activeBoxArray.length > 1){	//too many boxes slected. should not hapen
				alert('This should not hapen. mouseaction 1 and many active');
				return;
			}

			this.rooms[this.currentRoom].deleteBox(this.activeBoxArray[0]);
			this.activeBoxArray.length = 0;	//empty activeBoxarray

		}else if(this.action == 4) {	//lasso delete
			if(this.activeBoxArray.length == 0){
				alertify.set({ 
					labels: {
				    	ok     : translator.translate('ok'),
				    	cancel: translator.translate('cancel')
					},
					buttonFocus: "ok"  
				});
				alertify.alert(translator.translate('selectBoxesToDelete'));

				return;
			}

			var arrLength = this.activeBoxArray.length;

			for(var i = 0; i < arrLength; i++) {
				this.rooms[this.currentRoom].deleteBox(this.activeBoxArray[i]);	//delete element from reg
			}

			this.activeBoxArray.length = 0;	//empty activeBoxarray

		}else if(this.action == 3) {	//speed delete
			this.rooms[this.currentRoom].deleteBox(this.activeBoxArray[0]);
		}
	};

	//adding skeleton boxes to DOM and adding listeners. Add background iamge if needed
	Registration.prototype.buildSkeleton = function() {
		$('.room-image').remove();  //remove room image 
		var regScope = this;	//registration scope

		//creat fragment
		var fragment = document.createDocumentFragment();
		var roomSkeleton = this.rooms[this.currentRoom].skeleton;
		var xPosition = roomSkeleton.marginX;
		var yPosition = roomSkeleton.marginY;
		
		for(var i = 0; i < roomSkeleton.countY; i++) {
			for(var j = 0; j < roomSkeleton.countX; j++) {
				var el = document.createElement("div");
				el.style.cssText = 'width:'+roomSkeleton.width+'px;height:'+roomSkeleton.height+ 'px;position:absolute;top:'+ yPosition + 'px;left:'+xPosition+'px';
				el.setAttribute('class','skeleton-box');
				fragment.appendChild(el);
				
				xPosition += roomSkeleton.width + roomSkeleton.marginX;
			}

			xPosition = roomSkeleton.marginX;
			yPosition += roomSkeleton.height + roomSkeleton.marginY;
		}

		//add fragment to build area. .skeleton-box are in fragment
		$('.build-area').append(fragment);
		if(this.rooms[this.currentRoom].backgroundImage !== null) {
			var bgImg = $('<img class="room-image" src="' + window.WP_Seatreg.plugin_dir_url + 'uploads/room_images/'+ seatreg.selectedRegistration + '/' + this.rooms[this.currentRoom].backgroundImage + '" />');
			
			$('.build-area').append(bgImg);
		}


		$('.build-area .skeleton-box').on('mouseenter', function(){
			if(regScope.action == 2 && leftButtonDown == true) {
				var skelStyle = $(this).attr('style');
				var dataCounter = 'b' +  regScope.regBoxCounter;
				regScope.rooms[regScope.currentRoom].addBox("noLegend",parseInt($(this).css('left')), parseInt($(this).css('top')), parseInt($(this).css('width')), parseInt($(this).css('height')), dataCounter, '#61B329', 'nohover',true, "noStatus", 1, 'registration-box');  //add box to room
				regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, true);
			}else if(regScope.action == 5 && leftButtonDown == true) {
				var skelStyle = $(this).attr('style');
				var dataCounter = 'b' +  regScope.regBoxCounter;
				regScope.rooms[regScope.currentRoom].addBox("noLegend",parseInt($(this).css('left')), parseInt($(this).css('top')), parseInt($(this).css('width')), parseInt($(this).css('height')), dataCounter, '#cccccc', 'nohover',false, "noStatus", 1, 'custom-box');  //add box to room
				regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, false);
			}
		}).on('mousedown', function() {  //down klik on element
			if(regScope.action == 2) {
				var skelStyle = $(this).attr('style');
				var dataCounter = 'b' +  regScope.regBoxCounter;	
				regScope.rooms[regScope.currentRoom].addBox("noLegend",parseInt($(this).css('left')), parseInt($(this).css('top')), parseInt($(this).css('width')), parseInt($(this).css('height')), dataCounter, '#61B329', 'nohover',true,'noStatus',1, 'registration-box');  //add box to room
				regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, true);
			}else if(regScope.action == 5) {
				var skelStyle = $(this).attr('style');
				var dataCounter = 'b' +  regScope.regBoxCounter;
				regScope.rooms[regScope.currentRoom].addBox("noLegend",parseInt($(this).css('left')), parseInt($(this).css('top')), parseInt($(this).css('width')), parseInt($(this).css('height')), dataCounter, '#cccccc', 'nohover',false,'noStatus',1, 'custom-box');  //add box to room
				regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, false);
			}
		});

		$('.build-area-wrapper').off().on('click', function(e) {
			e.stopPropagation();
			var $this = $(this);
			var target = $(e.target);

			if(target.hasClass('drag-box') || target.hasClass('text-box-input') ) {
				return;
			}

			var relX = e.pageX + $this.scrollLeft() - $this.offset().left;
			var relY = e.pageY + $this.scrollTop() - $this.offset().top;
			var outSideOfSkeletonBoxZone = relX > roomSkeleton.totalWidth || relY > roomSkeleton.totalHeight; //detect click outside of the 'skeletons' zone

			if( (regScope.action == 2 || regScope.action == 5) && outSideOfSkeletonBoxZone ) {
				var dataCounter = 'b' +  regScope.regBoxCounter;
				var skelStyle = 'width: ' + roomSkeleton.width + 'px; height: ' + roomSkeleton.height + 'px; position: absolute; top: ' + (relY - roomSkeleton.height/2) + 'px; left: ' + (relX - roomSkeleton.width/2) + 'px;';

				if(regScope.action == 2) { 
					regScope.rooms[regScope.currentRoom].addBox("noLegend", relX - roomSkeleton.width/2, relY - roomSkeleton.height/2, roomSkeleton.width, roomSkeleton.height, dataCounter, '#61B329', 'nohover',true,'noStatus',1, 'registration-box');  //add box to room
					regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, true);
				}else if(regScope.action == 5) {
					regScope.rooms[regScope.currentRoom].addBox("noLegend", relX - roomSkeleton.width/2, relY - roomSkeleton.height/2, roomSkeleton.width, roomSkeleton.height, dataCounter, '#cccccc', 'nohover',false,'noStatus',1, 'custom-box');  //add box to room
					regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, false);
				}
			}else if(regScope.action == 9) {
				var dataCounter = 'b' +  regScope.regBoxCounter;
				var skelStyle = 'width: ' + 16 + 'px; height: ' + roomSkeleton.height + 'px; position: absolute; top: ' + (relY - roomSkeleton.height/2) + 'px; left: ' + (relX - roomSkeleton.width/2) + 'px;';

				regScope.buildTextBox((relX - roomSkeleton.width/2), (relY - roomSkeleton.height/2), dataCounter);
			}
		});
	};

	Registration.prototype.buildTextBox = function(positionX, positionY, dataCounter) {
		var initialBoxWidth = 16;
		var initialBoxHeight = 32;
		var styles = 'width: ' + initialBoxWidth + 'px; height: ' + initialBoxHeight + 'px; position: absolute; top: ' + positionY + 'px; left: ' + positionX + 'px;';
		var registrationScope = this;
		var boxClasses = "drag-box text-box " + dataCounter;
		this.rooms[this.currentRoom].addBox("noLegend", positionX, positionY, initialBoxWidth, initialBoxHeight, dataCounter, 'none', 'nohover',false,'noStatus', 1, 'text-box'); 
		var box = registrationScope.rooms[registrationScope.currentRoom].findAndReturnBox(dataCounter);

		var textBox = $('<div class="'+ boxClasses +'"><input class="text-box-input" /><i class="fa fa-plus text-size-control" data-action="increase"></i><i class="fa fa-minus text-size-control" data-action="degrease"></i></div>').attr({
			style: styles,
			'data-id': dataCounter, 
		}).on('keyup', function() {
			var $input = $(this).find('input');

			box.changeTextBoxInputValues($input);
			var dimentions = box.calculateInputBoxDimentions();
			box.changeSize(dimentions.width, dimentions.height);
			$(this).css({
				'width': dimentions.width,
				'height': dimentions.height
			});
		}).on('focusout', function() {
			var inputTextWidth = $(this).find('input').val().length;

			if(inputTextWidth < 1) {
				$('.build-area .text-box[data-id="'+ dataCounter +'"]').remove();
				registrationScope.rooms[registrationScope.currentRoom].deleteBox(dataCounter);
			}
		}).on('focusin', function() {
			box.showTextBoxFontSizeControls();
		}).on('mouseenter', function() {
			if(registrationScope.action === 3 && leftButtonDown === true) {
				registrationScope.activeBoxArray.length = 0;	//make sure activebox in empty.
				registrationScope.activeBoxArray.push($(this).attr('data-id'));
				registrationScope.deleteBoxes();
			}
		});

		textBox.appendTo('.build-area');
		$('.build-area .text-box .text-size-control').off().on('click', function(e) {
			e.stopPropagation();

			var boxId = $(this).closest('.drag-box').data('id');
			var $box = $('.build-area .text-box[data-id="'+ boxId +'"]');
			var action = $(this).data('action');
			var fontSize = parseInt($box.find('.text-box-input').css('font-size'), 10);
			var $input = $box.find('.text-box-input');

			if(action === 'increase') {
				fontSize += 1;
			}else {
				fontSize -= 1;
			}

			var regBox = registrationScope.rooms[registrationScope.currentRoom].findAndReturnBox(boxId);
			regBox.changeTextBoxInputValues($input);
			var dimentions = regBox.calculateInputBoxDimentions();
			
			regBox.changeSize(dimentions.width, dimentions.height);

			$box.find('.text-box-input').css('font-size', fontSize);
			$box.css({
				'width': dimentions.width,
				'height': dimentions.height,
			});
		});

		$('.build-area .text-box[data-id="'+ dataCounter +'"] .text-box-input').focus();
	};

	//creates a box out of skeleton box
	Registration.prototype.buildBoxOutOfSkeleton = function(skelStyle, dataCounter, regScope) {
		var nr = this.rooms[this.currentRoom].roomSeatCounter;
		var disableDrag = true;

		if(regScope.action == 1) {	//only actino 1 allows draging,resizing box
			disableDrag = false;
		}

		var box = $('<div>').addClass('drag-box can-register active-box ' + dataCounter).attr({
			style: skelStyle, 
			'data-id':dataCounter,
			'data-seatnr': nr
		}).on('click', function() { //chen you klik box			
			if(regScope.action == 1) {	//is mouse action 1?
				regScope.activeBoxArray.length = 0;  //make sure activebox in empty.
				regScope.activeBoxArray.push($(this).attr('data-id'));	//this box in now active
				
				$('.active-box').removeClass('active-box');	//remove all previous active
				$(this).addClass('active-box');	//set this box active

				regScope.showClickControls();
			}else if(regScope.action == 3) {
				regScope.activeBoxArray.length = 0;	//make sure activebox in empty.
				regScope.activeBoxArray.push($(this).attr('data-id'));
				regScope.deleteBoxes();
			}		
		}).on('mouseenter', function(e) {
			if(regScope.action === 3 && leftButtonDown === true) {
				regScope.activeBoxArray.length = 0;	//make sure activebox in empty.
				regScope.activeBoxArray.push($(this).attr('data-id'));
				regScope.deleteBoxes();
			}
			if($(this).data('powertip') && leftButtonDown === false) {
				$.powerTip.show($(this))
			}
		}).on('mouseleave', function() {
			if($(this).data('powertip')) {
				$.powerTip.hide();
			}
		}).each(function() {
			if(reg.action == 2) {
				$(this).removeClass('active-box');
			}
			if(reg.action == 5) {
				$(this).removeClass('can-register active-box').removeAttr('data-seatnr').css('background-color','#cccccc');
			}

			if(reg.action == 1 || reg.action == 2) {
				$(this).append($('<div>').addClass('seat-number').text(nr));
			}
		});

		box.appendTo('.build-area');  //fainally add box to build-area
	}

	//this method is used for building already existing rooms. like when you change room. build old room
	Registration.prototype.buildBoxes = function() {
		var regScope = this;

		if($('html').hasClass('touch')) {
			regScope.action = 6;
		}else {
			regScope.action = 1;
		}

		var boxCount = this.rooms[this.currentRoom].boxes.length;

		for(var i = 0; i < boxCount; i++) {
			var boxId = this.rooms[this.currentRoom].boxes[i].id;
			var boxClasses = 'drag-box ' + boxId;
			var canRegisterBox = regScope.rooms[regScope.currentRoom].boxes[i].canRegister;
			
			if(this.rooms[this.currentRoom].boxes[i].type === 'text-box') {
				boxClasses += ' text-box';
			}

			$('<div>').addClass(boxClasses).attr({
				'data-id': boxId
			}).css({
				width: this.rooms[this.currentRoom].boxes[i].width + 'px',
				height: this.rooms[this.currentRoom].boxes[i].height + 'px',
				position: 'absolute',
				left: this.rooms[this.currentRoom].boxes[i].xPosition,
				top: this.rooms[this.currentRoom].boxes[i].yPosition,
				'background-color': this.rooms[this.currentRoom].boxes[i].color,
				'z-index': this.rooms[this.currentRoom].boxes[i].zIndex
			}).on('click', function() { 	
				if(regScope.action == 1) {	//is mouse action 1?
					regScope.activeBoxArray.length = 0;  //make sure activebox in empty.
					regScope.activeBoxArray.push($(this).attr('data-id'));	//this box in now active
					$('.active-box').removeClass('active-box');	//remove all previous active
					$(this).addClass('active-box');	//set this box active

					regScope.showClickControls();
				}
				if(regScope.action == 3) {
					regScope.activeBoxArray.length = 0;	//make sure activebox in empty.
					regScope.rooms[regScope.currentRoom].deleteBox($(this).attr('data-id'));
				}	
			}).on('mouseenter', function() {
				if(regScope.action == 3 && leftButtonDown === true) {
					regScope.rooms[regScope.currentRoom].deleteBox($(this).attr('data-id'));
				}
				if($(this).data('powertip') && leftButtonDown === false) {
					$.powerTip.show($(this))
				}
			}).on('mouseleave', function() {
				if($(this).data('powertip')) {
					$.powerTip.hide();
				}
			}).appendTo('.build-area').each(function(){
				if(regScope.rooms[regScope.currentRoom].boxes[i].canRegister === true) {
					$(this).addClass('can-register').attr('data-seatnr', regScope.rooms[regScope.currentRoom].boxes[i].seat);

					$(this).append($('<div>').addClass('seat-number').text(regScope.rooms[regScope.currentRoom].boxes[i].prefix + regScope.rooms[regScope.currentRoom].boxes[i].seat));

					if(regScope.rooms[regScope.currentRoom].boxes[i].status == 'bronRegister') {
						$(this).append($('<div>').addClass('bron-sign'));
					}else if(regScope.rooms[regScope.currentRoom].boxes[i].status == 'takenRegister') {
						$(this).addClass('bron-register').append($('<div>').addClass('taken-sign'));
					}
				}else {
					var $this = $(this);
					var boxId = $this.data('id');
					

					$this.addClass('no-register');

					if(regScope.rooms[regScope.currentRoom].boxes[i].type === 'text-box') {
						var boxInput = regScope.rooms[regScope.currentRoom].boxes[i].input;
						var boxInputFontSize = regScope.rooms[regScope.currentRoom].boxes[i].inputSize;

						$this.append('<input class="text-box-input" value="' + boxInput + '" />');
						$this.append('<i class="fa fa-plus text-size-control" data-action="increase" style="display:none"></i>');
						$this.append('<i class="fa fa-minus text-size-control" data-action="degrease" style="display:none"></i>');
						$this.find('.text-box-input').css({
							'color': regScope.rooms[regScope.currentRoom].boxes[i].fontColor,
							'font-size':  boxInputFontSize + 'px'
						});
						$this.on('keyup', function() {
							var $input = $this.find('input');
							var box = regScope.rooms[regScope.currentRoom].findAndReturnBox(boxId);

							box.changeTextBoxInputValues($input);
							var dimentions = box.calculateInputBoxDimentions();
							
							box.changeWidth(dimentions.width);
							$this.css('width', dimentions.width);
						}).on('focusout', function() {
							var inputTextWidth = $this.find('input').val().length;
				
							if(inputTextWidth < 1) {
								$('.build-area .text-box[data-id="'+ boxId +'"]').remove();
								regScope.rooms[regScope.currentRoom].deleteBox(boxId);
							}
						});
					}
				}
				var hoverText = regScope.rooms[regScope.currentRoom].boxes[i].hoverText;
				
				if(hoverText != 'nohover') {
					if(canRegisterBox) {
						$(this).attr('data-powertip', 'ID: ' + regScope.rooms[regScope.currentRoom].boxes[i].id + '<br>' + hoverText).addClass('box-hover');
					}else {
						$(this).attr('data-powertip', hoverText).addClass('box-hover');
					}
				}else {
					if(canRegisterBox) {
						$(this).attr('data-powertip', 'ID: ' + regScope.rooms[regScope.currentRoom].boxes[i].id).addClass('box-hover no-bubble');
					}
				}
			});	
		}
		regScope.initToolTip();
		regScope.addDraggableListeners();
		regScope.addResisableListeners();
	};

	Registration.prototype.addDraggableListeners = function() {
		if(this.hasDraggableListeners()) {
			return true;
		}
		disableDrag = false;
		var regScope = this;
		var boxCollection = $('.build-area .drag-box'); 
		var multiDragBoxes = null;

		boxCollection.draggable({
			containment: ".build-area",  
			scroll: true, 
			scrollSensitivity: 50,
			scrollSpeed: 30, 
			stack: ".drag-box",
			disabled: disableDrag,
			start: function(){
				if(!regScope.needMultiDrag) {
					regScope.activeBoxArray.length = 0;  //make sure activebox in empty.
					regScope.activeBoxArray.push($(this).attr('data-id'));	//this box in now active
					$('.active-box').removeClass('active-box');	//remove all previous active
					$(this).addClass('active-box');	//set this box active
				}else {
					multiDragBoxes = $('.build-area .active-box');
				}	
			},
			drag: function(event, ui) {
				if(regScope.needMultiDrag) {
					var currentLoc = $(this).position();
					var prevLoc = $(this).data('prevLoc');
					if (!prevLoc) {
						prevLoc = ui.originalPosition;
					}

					var offsetLeft = currentLoc.left-prevLoc.left;
					var offsetTop = currentLoc.top-prevLoc.top;
					var outOfContainer = false;

					multiDragBoxes.each(function(){
						$this =$(this);
						var p = $this.position();
						var l = p.left;
						var t = p.top;
						
						if(l + offsetLeft <= 0 || t + offsetTop <= 0) {					        
							outOfContainer = true;
						}
					});

					if(!outOfContainer) {
						multiDragBoxes.each(function(){
							$this =$(this);
							var p = $this.position();
							var l = p.left;
							var t = p.top;
							$this.css('left', l+offsetLeft);
							$this.css('top', t+offsetTop);
						});
						$(this).data('prevLoc', currentLoc);
					}
				}
			},
			stop: function(event, ui) {
				if(!regScope.needMultiDrag) {
					var location = regScope.rooms[regScope.currentRoom].findBox($(this).attr('data-id'));
					if(location !== false) {
						regScope.rooms[regScope.currentRoom].boxes[location].changePosition(ui.position.left, ui.position.top);
					}
				}else {
					multiDragBoxes.each(function() {
						$this = $(this);
						var location = regScope.rooms[regScope.currentRoom].findBox($this.attr('data-id'));
						if(location !== false) {
							regScope.rooms[regScope.currentRoom].boxes[location].changePosition(parseInt($this.css('left')), parseInt($this.css('top')));
						}
					});
				}
			}
		});
	};

	Registration.prototype.hasDraggableListeners = function() {
		if($('.build-area-wrapper .drag-box').hasClass("ui-draggable")) {
			return true;
		}

		return false;
	};

	Registration.prototype.removeDraggableListeners = function() {
		if(this.hasDraggableListeners()) {
			$('.build-area-wrapper .drag-box').draggable("destroy");
		}
	};

	Registration.prototype.disableDraggableListeners = function() {
		if(this.hasDraggableListeners()) {
			$('.build-area-wrapper .drag-box').draggable("option","disabled",false)
		}
	};

	Registration.prototype.addResisableListeners = function(disableDrag) {
		var regScope = this;
		var boxCollection = $('.build-area .drag-box:not(.ui-resizable):not(.text-box)'); 
		var autoHide = true;

		if($('html').hasClass('touch')) {
			autoHide = false;
		}

		boxCollection.resizable({
			autoHide: autoHide,
			handles: "n, e, s, w, ne, se, sw, nw",
			disabled: disableDrag,
			alsoResize: false,

			start: function(event, ui) {
				if(regScope.activeBoxArray.length == 0) {
					$('.build-area .drag-box:not(.text-box)').resizable("option","alsoResize", false); 
				}
				if(regScope.activeBoxArray.indexOf($(this).data('id')) == -1) {
					regScope.activeBoxArray.push($(this).data('id'));
					$(this).addClass('active-box');
				}
				
			},
			stop: function(event, ui) {
				var aLen = regScope.activeBoxArray.length;

				if(aLen == 1) {
					var box = regScope.rooms[regScope.currentRoom].findAndReturnBox(regScope.activeBoxArray[0]);

					if(box !== false) {
						box.changeValues(ui.position.left, ui.position.top, ui.size.width, ui.size.height);
					}
				}else {
					for(var i = 0; i < aLen; i++) {
						var box = regScope.rooms[regScope.currentRoom].findAndReturnBox(regScope.activeBoxArray[i]);
						var location = regScope.rooms[regScope.currentRoom].findBox(regScope.activeBoxArray[i]);

						if(location !== false) {
							var resizedBox = $('.drag-box[data-id='+ regScope.activeBoxArray[i] +']');

							box.changeValues(parseInt(resizedBox.css('left')), parseInt(resizedBox.css('top')), parseInt(resizedBox.css('width')), parseInt(resizedBox.css('height')));
						}
					} 
				}
			} 
		});
	};

	Registration.prototype.hasResizableListeners = function () {
		var dragBoxes = $('.build-area-wrapper .drag-box');

		if(dragBoxes.hasClass("ui-resizable")) {
			return true;
		}
		return false;
	};

	Registration.prototype.removeResisableListeners = function() {
		if(this.hasResizableListeners()) {
			$('.build-area-wrapper .drag-box:not(.text-box)').resizable("destroy");
		}
	};

	Registration.prototype.disableResisableListeners = function() {
		if(this.hasResizableListeners()) {
			$('.build-area-wrapper .drag-box').resizable("option","disabled",false);
		}
	};

	Registration.prototype.addselectableScroll = function() {
		var regScope = this;

		if($('.build-area-wrapper').hasClass('ui-selectable')) {
			return true;
		}
		
		$('.build-area-wrapper').selectableScroll({
			filter: ".drag-box",
			stop: function( event, ui ) {
				$('.build-area .ui-selected').addClass('active-box').removeClass('ui-selected');

				var bIndex = regScope.biggestzIndex();
				var selectedBoxesData = [];

				$('.build-area .active-box').each(function() {
					selectedBoxesData.push({
						id: $(this).attr('data-id'),
						left: parseInt($(this).css('left')),
						top: parseInt($(this).css('top')),
					});
					$(this).css({'zIndex':bIndex});
				});

				selectedBoxesData.sort(function(a, b) {
					if( a.left < b.left && a.top < b.top ) {
						return -1;
					}
					if( a.left > b.left && a.top > b.top ) {
						return 1;
					}

					return 0;
				});
				
				var selectedBoxesIds = selectedBoxesData.map(function(box) {
					return box.id;
				});
				regScope.activeBoxArray = selectedBoxesIds;

				if($('.build-area .active-box').length > 1) {
					$('.build-area .drag-box:not(.text-box)').resizable( "option", "alsoResize", ".active-box:not(.text-box)" );
					regScope.needMultiDrag = true;
				}else {
					$('.build_area .drag-box:not(.text-box)').resizable( "option", "alsoResize", false );
					regScope.needMultiDrag = false;
				}

				regScope.showClickControls();
			},
			start: function( event, ui ) {
				regScope.activeBoxArray.length = 0;
				$('.build-area .drag-box').removeClass('active-box');
			},
			scrollSnapX: 30,
			scrollSnapY: 30,
			scrollAmount: 6,
			scrollIntervalTime: 100	
		});
	};

	Registration.prototype.removeSelectableScroll = function() {
		if($('.build-area-wrapper').hasClass('ui-selectable')) {
			$( ".build-area-wrapper" ).selectableScroll( "destroy" ).removeClass('ui-selectable');
		}
	};

	Registration.prototype.hideTextBoxFontControls = function() {
		$('.build-area .text-box').find('.text-size-control').css('display', 'none');
	}

	Registration.prototype.showTextBoxFontControls = function() {
		$('.build-area .text-box').find('.text-size-control').css('display', 'block');
	}

	Registration.prototype.mouseActionChange = function(driggerElement) {
		var action = parseInt(driggerElement.attr('data-action'));
		var regScope = this;

		//actino is already selected
		if(action == this.action) {

			return;
		}
		//change action is reg obejct
		this.action = action;

		$('#mouse-option-active').removeAttr('id');
		driggerElement.attr('id','mouse-option-active');

		//remove active boxes
		this.activeBoxArray.length = 0;
		this.showClickControls();

		$('.build-area .active-box').removeClass('active-box');

		if(this.action == 1) { 			//normal mouse action
			regScope.needMultiDrag = false;
			$('.build-area-wrapper').attr('data-cursor','1');
			regScope.hideTextBoxFontControls();
			regScope.removeSelectableScroll();		
			regScope.addDraggableListeners();
			regScope.addResisableListeners();
			
		}else if(this.action == 2) { 	//speed creator tool selected
			$('.build-area-wrapper').attr('data-cursor','2');
			regScope.hideTextBoxFontControls();
			regScope.removeSelectableScroll();	
			regScope.removeDraggableListeners();
			regScope.removeResisableListeners();

		}else if(this.action == 3) { //speed delete tool selected
			$('.build-area-wrapper').attr('data-cursor','4');
			regScope.hideTextBoxFontControls();
			regScope.removeSelectableScroll();	
			regScope.removeDraggableListeners();
			regScope.removeResisableListeners();
			
		}else if(this.action == 4) {	//lasso tool selected
			$('.build-area-wrapper').attr('data-cursor','5');
			regScope.hideTextBoxFontControls();
			regScope.addDraggableListeners();
			regScope.addResisableListeners();
			regScope.addselectableScroll();
		}else if(this.action == 5) {  //normal box creation tool
			$('.build-area-wrapper').attr('data-cursor','3');
			regScope.hideTextBoxFontControls();
			regScope.removeSelectableScroll();	
			regScope.removeDraggableListeners();
			regScope.removeResisableListeners();
		}else if(this.action == 6) {  //in touch devices, move around tool
			regScope.hideTextBoxFontControls();
			regScope.addselectableScroll();
			regScope.removeDraggableListeners();
			regScope.removeResisableListeners();
		}else if(this.action == 9) {
			$('.build-area-wrapper').attr('data-cursor','9');
			regScope.showTextBoxFontControls();
			regScope.removeResisableListeners();
			regScope.removeSelectableScroll();	
			regScope.removeDraggableListeners();
		}
	};

	Registration.prototype.sendValidation = function() {
		//check if all rooms have title
		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		       if(this.rooms[property].title == "") {

		       		alertify.set({ 
						labels: {
					    	ok     : translator.translate('ok'),
					    	cancel: translator.translate('cancel')
						},
						buttonFocus: "ok"  
					});
		       		alertify.alert(translator.translate('allRoomsNeedName'));

		       		return false;
		       }
		    }
		}

		return true;
	};

    //collect data for sending to server
	Registration.prototype.collectData = function() {
		var data = {
			global: {
				legends: [],
				roomLocator: this.roomLocator,
				boxCounter: this.regBoxCounter
			},
			roomData: []
		};
		var allLegendsLength = this.allLegends.length;

		for( var j = 0; j < allLegendsLength; j++) {
			data.global.legends.push({
				text: this.allLegends[j].text,
				color: this.allLegends[j].color
			});
		}

		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		        data.roomData.push(this.rooms[property].returnRoomData());
		    }
		}

		return data;
	};

	//overrite existing registration data on server
	Registration.prototype.updateData = function() {
		var dataToSend = JSON.stringify(this.collectData());
		var scope = this;
		var token = $('#sec_token').val();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: (Object.size(scope.roomNameChange) > 0) ?
				{
					updatedata:dataToSend,
					bigtitle:scope.bigTitle,
					roomlocator:scope.roomLocator,
					changeR: JSON.stringify(scope.roomNameChange),
					token: token,
					action: 'seatreg_update_layout',
					security: WP_Seatreg.nonce,
					registration_code: window.seatreg.selectedRegistration
				} :
				{
					updatedata:dataToSend,
					bigtitle:scope.bigTitle,
					roomlocator:scope.roomLocator,
					token: token,
					action: 'seatreg_update_layout',
					security: WP_Seatreg.nonce,
					registration_code: window.seatreg.selectedRegistration
				},
			success: function(data) {
				$('#update-data').find('.save-text').text(translator.translate('save'));
				if(data._response.type == 'ok') {
					scope.needToSave = false;

					//set initialName to title
					for (var property in scope.rooms) {
						if (scope.rooms.hasOwnProperty(property)) {
							scope.rooms[property].initialName = scope.rooms[property].title;
						}
					}

					scope.roomNameChange = {};
					alertify.success(translator.translate('saved'));

					$('#server-response').empty();
				}else {
					$('#server-response').text(data._response.text);
				}
			}
		});
	};

	Registration.prototype.syncBoxStatuses = function(registratedSpots) {
		var arrLen = registratedSpots.length;

		for(var i = 0; i < arrLen; i++) {
			 this.updateBoxStatus(registratedSpots[i].seat_id, registratedSpots[i].status);
		}
	};

	Registration.prototype.updateBoxStatus = function(id, status) {
		var breakCheck = false;

		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		    	var arrLength = this.rooms[property].boxes.length;

		    	for(var i = 0; i < arrLength; i++) {
		    		if(this.rooms[property].boxes[i].id == id) {
		    			if(status == 1) {
		    				this.rooms[property].boxes[i].status = 'bronRegister';
		    			}else if(status == 2) {
		    				this.rooms[property].boxes[i].status = 'takenRegister';
						}
						
		    			breakCheck = true;
		    			break;
		    		}
		    	}
		    	if(breakCheck == true) {
		    		break;
		    	}
		    }
		}
	};

	//SyncData from server
	Registration.prototype.syncData = function(responseObj) {		
		if($.isEmptyObject(responseObj)){
			this.addRoom(false,false,true, generateUUID());
			$('#build-area-loading-wrap').remove();
			$('#room-name-dialog').modal("toggle");
			this.setBuilderHeight();
		}else {
			this.settings =  window.seatreg.settings;
			var roomData = responseObj.roomData;
			this.regBoxCounter = responseObj.global.boxCounter;
			var globalLegendsLength = responseObj.global.legends.length;
	
			for(var r = 0; r < globalLegendsLength; r++) {
				this.syncAllLegends(responseObj.global.legends[r].text, responseObj.global.legends[r].color);
			}

			for (var property in roomData) {
			    if (roomData.hasOwnProperty(property)) {
			    	this.addRoom(true,false,false, roomData[property]['room'].uuid);
			    	this.rooms[this.currentRoom].title = roomData[property]['room'].name;
			    	this.rooms[this.currentRoom].initialName = roomData[property]['room'].name;

					var roomBackgroundImage = roomData[property]['room'].backgroundImage;
			    	if(typeof roomBackgroundImage !== 'undefined' && roomBackgroundImage !== null) {
			    		this.rooms[this.currentRoom].backgroundImage = roomBackgroundImage;
			    	}
			    		
			    	//update skeleton
			    	var skeleton = roomData[property]['skeleton'];
			    	this.rooms[this.currentRoom].skeleton.changeSkeleton(
						skeleton.width, 
						skeleton.height, 
						skeleton.countX, 
						skeleton.countY, 
						skeleton.marginX, 
						skeleton.marginY, 
						skeleton.buildGrid
					);
					var roomLegends = roomData[property]['room'].legends;
			    	var roomLegendsLength = roomData[property]['room'].legends.length;

			    	for(var k = 0; k < roomLegendsLength; k++) {
			    		this.rooms[this.currentRoom].legends.push(new Legend(roomLegends[k].text, roomLegends[k].color));
			    	}

			    	$('#room-selection-wrapper .room-selection[data-room-location="'+ reg.currentRoom +'"]').text(reg.rooms[reg.currentRoom].title);
			    	var arr = roomData[property]['boxes'];
			    	var arrLength = arr.length;

			    	for(var i = 0; i < arrLength; i++) {  //adding boxes
			    		var canReg = arr[i].canRegister;

			    		if(canReg == 'true') {
			    			canReg = true;
			    		}else if(canReg == 'false'){
			    			canReg = false;
			    		}
			    		this.rooms[this.currentRoom].addBoxS(
							arr[i].legend,
							arr[i].xPosition,
							arr[i].yPosition, 
							arr[i].width, 
							arr[i].height, 
							arr[i].id, 
							arr[i].color, 
							arr[i].hoverText.replace(/\^/g,'<br>'), 
							canReg, 
							arr[i].status, 
							arr[i].zIndex,
							arr[i].price,
							arr[i].type,
							arr[i].input,
							arr[i].fontColor,
							arr[i].inputSize,
							arr[i].lock,
							arr[i].password,
							arr[i].prefix
						);
			    	}
			    }
			}
			
			if(window.seatreg.bookings.length > 0) {
				this.syncBoxStatuses(window.seatreg.bookings);
			}
			
			var roomElem = $('#room-selection-wrapper .room-selection').first();
			this.changeRoom(roomElem.attr('data-room-location'), roomElem, true, false);
			this.generatePrevUploadedImgMarkup();
			this.setBuilderHeight();
			$('#build-area-loading-wrap').remove();
		}
	};

	//check if legend not exist add new legend
	Registration.prototype.syncAllLegends = function(text,color) {
		var foundLegend = false;
		var arrLength = this.allLegends.length;

		for(var i = 0; i < arrLength; i++) {
			if(this.allLegends[i].text == text) {
				foundLegend = true;
				break;
			}
		}

		if(!foundLegend) {
			this.allLegends.push(new Legend(text,color));
		}
	};

	//can i move all selected boxes. Stop box from getting out of view (left and top).
	Registration.prototype.boxMoveCheck = function(dest, destAmount) {
		var activeBoxesLength = this.activeBoxArray.length;
		var curRoom = this.rooms[this.currentRoom];
		var minDest = 999999999; //min amount boxes can move
		var canMove = true;

		for(var i = 0; i < activeBoxesLength; i++) {
			var b = curRoom.findBox(this.activeBoxArray[i]);

			if(b !== false) {
				if(dest == 'up') {
					if((this.rooms[this.currentRoom].boxes[b].yPosition - destAmount) < 0) {
						canMove = false;

						if(this.rooms[this.currentRoom].boxes[b].yPosition < minDest) {
							minDest = this.rooms[this.currentRoom].boxes[b].yPosition;
						}
					}
				}else if(dest == "left") {
					if((this.rooms[this.currentRoom].boxes[b].xPosition - destAmount) < 0) {
						canMove = false;

						if(this.rooms[this.currentRoom].boxes[b].xPosition < minDest) {
							minDest = this.rooms[this.currentRoom].boxes[b].xPosition;
						}
					}
				}
			}
		}

		if(canMove) {
			return {'status': true};
		}else {
			return {'status': false, 'nr': this.rooms[this.currentRoom].boxes[b].seat, 'minDest': minDest};
		}
	};

	Registration.prototype.moveActiveBoxes = function(dest, destAmount) {
		var activeBoxesLength = this.activeBoxArray.length;
		var curRoom = this.rooms[this.currentRoom];

		//move location in memory
		for(var i = 0; i < activeBoxesLength; i++) {
			var b = curRoom.findBox(this.activeBoxArray[i]);

			if(b !== false) {
				if(dest == 'up') {
					this.rooms[this.currentRoom].boxes[b].yPosition -= destAmount;
					$('.build-area-wrapper .' + this.rooms[this.currentRoom].boxes[b].id).css({'top':'-=' + destAmount + 'px'});
				}else if(dest == 'down') {
					this.rooms[this.currentRoom].boxes[b].yPosition += destAmount;
					$('.build-area-wrapper .' + this.rooms[this.currentRoom].boxes[b].id).css({'top':'+=' + destAmount + 'px'});
				}else if(dest == "left") {
					this.rooms[this.currentRoom].boxes[b].xPosition -= destAmount;
					$('.build-area-wrapper .' + this.rooms[this.currentRoom].boxes[b].id).css({'left':'-=' + destAmount + 'px'});
				}else if(dest == 'right') {
					this.rooms[this.currentRoom].boxes[b].xPosition += destAmount;
					$('.build-area-wrapper .' + this.rooms[this.currentRoom].boxes[b].id).css({'left':'+=' + destAmount + 'px'});
				}
			}
		}
	};

	//change selected box location
	Registration.prototype.prepareMoveActiveBoxes = function(dest, destAmount) {
		if(dest == 'up' || dest == 'left') {
			var desisionObj = this.boxMoveCheck(dest, destAmount);

			if(desisionObj.status == true) {
				this.moveActiveBoxes(dest, destAmount);

			}else if(desisionObj.status == false) {
				this.moveActiveBoxes(dest, desisionObj.minDest);
			}
		}else {
			this.moveActiveBoxes(dest, destAmount);
		}
	};

	Registration.prototype.showClickControls = function() {
		if(this.activeBoxArray.length > 0) {
			$('#build-section-click-controls').css({'display': 'inline-block'});
		}else {
			$('#build-section-click-controls').css({'display': 'none'});
		}
	};

	//find biggest zIndex from .drag-box
	Registration.prototype.biggestzIndex = function() {
		var biggestIndex = 0;

		$('.build-area-wrapper .drag-box').each(function() {
			var targetIndex = parseInt($(this).css('z-index'));

			if(targetIndex > biggestIndex) {
				biggestIndex = targetIndex;
			}

		});

		return biggestIndex + 1;	
	};

	Registration.prototype.setBuilderHeight = function() {
		var screenHeight = $(window).height();
		var buildHeadHeight = $('.build-head').outerHeight(true);
		var buildSectionHeight = $('#build-section').outerHeight(true);
		var buildControlsHeight = $('.build-controls').outerHeight(true);
		var extraHeight = 200;

		$('.build-area-wrapper').height(screenHeight - buildHeadHeight - buildSectionHeight - buildControlsHeight - extraHeight);
	};

	/*
		*------Create Registrstion object
	*/

	var reg = new Registration();
	window.seatreg.builder = reg;

	if($('html').hasClass('touch')) {
		//add mouse-option
		$('.mouse-action-boxes').prepend($('<div class="mouse-option action6" data-action="6"></div>'));
		reg.action = 6;
	}
	
	function clearBuildArea() {
		$('.build-area').empty();
	}

	function generateUUID() {
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
			return v.toString(16);
		}) + new Date().valueOf();
	}

	/*

		*----------Init jquery ui and other ----------
	*/
	
	$(".modal").draggable({
	    handle: ".modal-header"
	});
	
	//----Dialogs----

	$('#hover-dialog').on('show.bs.modal', function() {
		$('#box-hover-text').val('');
		$('#hover-dialog').find('.hover-dialog-info').html(reg.activeBoxesInfo());
	});

	$('#color-dialog').on('show.bs.modal', function() {
		$('#color-dialog .color-dialog-info').html('<h5>' + reg.activeBoxesInfo() + '</h5>');
	});

	$('#room-name-dialog').on('show.bs.modal', function() {
		$(this).find('.room-name-error').empty();	
		$('#room-name-dialog-input').val(reg.rooms[reg.currentRoom].title);
		$('.room-name-char-rem').text('');	
	});

	$('#skeleton-dialog').on('show.bs.modal', function() {
		$('#size-x').val(reg.rooms[reg.currentRoom].skeleton.width);
		$('#size-y').val(reg.rooms[reg.currentRoom].skeleton.height);

		$('#count-x').val(reg.rooms[reg.currentRoom].skeleton.countX);
		$('#count-y').val(reg.rooms[reg.currentRoom].skeleton.countY);

		$('#margin-x').val(reg.rooms[reg.currentRoom].skeleton.marginX);
		$('#margin-y').val(reg.rooms[reg.currentRoom].skeleton.marginY);
	});

	$('#seat-numbering-dialog').on('show.bs.modal', function() {
		var currentRoom = reg.rooms[reg.currentRoom];
		var selectedBoxes = reg.activeBoxArray.map(function(selectedBoxId) {
			return currentRoom.findAndReturnBox(selectedBoxId);
		});
		var hasStatusSeat = selectedBoxes.some(function(box) {
			return box.status !== "noStatus"
		});
		var selectedSeats = selectedBoxes.filter(function(box) {
			return box.canRegister === true && box.status === "noStatus";
		});

		$('#seat-numbering-dialog .alert').addClass('d-none');
		$('#seat-numbering-wrap').removeClass('d-none');

		if(!selectedSeats.length) {
			$('#seat-nr-change-no-selection').removeClass('d-none');
			$('#seat-numbering-wrap').addClass('d-none');
		}else {
			if(hasStatusSeat) {
				$('#seat-nr-change-warning').removeClass('d-none');
			}

			$('#set-seat-prefix').off('click').on('click', function() {
				selectedSeats.forEach(function(seat) {
					var box = currentRoom.findAndReturnBox(seat.id);

					box.changePrefix( $('#seat-prefix').val() );
				});
			})
			$('#reorder-seats').off('click').on('click', function() {
				var startReorderFrom = $('#seat-reorder').val();

				selectedSeats.forEach(function(seat) {
					var box = currentRoom.findAndReturnBox(seat.id);

					box.changeSeatNr(startReorderFrom);
					box.renderSeatNr();
					startReorderFrom++;
				});
			});
		}
	});

	$('#lock-seat-dialog').on('show.bs.modal', function() {
		var $lockWrap = $('#selected-seats-for-locking');
		var currentRoom = reg.rooms[reg.currentRoom];
		var selectedBoxes = reg.activeBoxArray.map(function(selectedBoxId) {
			var boxLocation = currentRoom.findBox(selectedBoxId);
			
			return currentRoom.boxes[boxLocation];
		});
		var hasSeatSelected = selectedBoxes.some(function(box) {
			return box.canRegister === true;
		});

		$lockWrap.empty();

		if(!hasSeatSelected) {
			$('.set-password-wrap').addClass('d-none');
			$('#set-seat-locks').addClass('d-none');
			$lockWrap.append(
				'<div class="alert alert-primary">'+ translator.translate('noSeatsSelected') +'</div>'
			);
		}else if(selectedBoxes.length == 1) {
			$('.set-password-wrap').addClass('d-none');
			$('#set-seat-locks').removeClass('d-none');
		}else {
			$('.set-password-wrap').removeClass('d-none');
			$('#set-seat-locks').removeClass('d-none');
		}

		selectedBoxes.forEach(function(box) {
			if(box.canRegister) {
				var boxLocation = currentRoom.findBox(box.id);

				$lockWrap.append(
					'<div class="lock-item" data-box-location="' + boxLocation + '">' + 
						'<div class="lock-item-seat">'  + box.seat  + '</div>' +
						'<label>' + translator.translate('lockSeat') +
							'<input type="checkbox" ' + (box.lock ? "checked" : "") + ' />' + 
						'</label>' +
						'<label>' + translator.translate('setPassword') + 
							'<input type="text" value="' + box.password + '" />' + 
						'</label>' + 
					'</div>'
				);
			}
		});

	});

	$('#price-dialog').on('show.bs.modal', function() {
		var $pricingWrap = $('#selected-seats-for-pricing');
		var currentRoom = reg.rooms[reg.currentRoom];
		var selectedBoxes = reg.activeBoxArray.map(function(selectedBoxId) {
			var boxLocation = currentRoom.findBox(selectedBoxId);
			
			return currentRoom.boxes[boxLocation];
		});
		var hasSeatSelected = selectedBoxes.some(function(box) {
			return box.canRegister === true;
		});

		$pricingWrap.empty();
		$('#set-prices').removeClass('d-none');

		if(reg.settings.paypal_payments === '1') {
			$('#enable-paypal-alert').css('display', 'none');
		}
		if(!hasSeatSelected) {
			$('.set-price-wrap').addClass('d-none');
			$('#set-prices').addClass('d-none');
			$pricingWrap.append(
				'<div class="alert alert-primary">'+ translator.translate('noSeatsSelected') +'</div>'
			);
		}else if(selectedBoxes.length == 1) {
			$('.set-price-wrap').addClass('d-none');
		}else {
			$('.set-price-wrap').removeClass('d-none');
		}
		
		selectedBoxes.forEach(function(box) {
			if(box.canRegister) {
				var boxLocation = currentRoom.findBox(box.id);

				$pricingWrap.append(
					'<div class="price-item" data-box-location="' + boxLocation + '">' + 
						'<div class="price-item-seat">'  + box.seat  + '</div>' +
						'<input type="number" min="0" oninput="this.value = Math.abs(this.value)" value="' + box.price + '" />' + 
					'</div>'
				);
			}
		});
	});

	$("#fill-price-for-all-selected").on('click', function() {
		var priceForAllSelected = $('#price-for-all-selected').val();

		$("#selected-seats-for-pricing .price-item").each(function() {
			$(this).find('input').val(priceForAllSelected);
		});
	});

	$("#fill-password-for-all-selected").on('click', function() {
		var passwordForAllSelected = $('#password-for-all-selected').val();

		$("#selected-seats-for-locking .lock-item").each(function() {
			$(this).find('input[type=text]').val(passwordForAllSelected);
		});
	});


	$('#set-prices').on('click', function() {
		var currentRoom = reg.rooms[reg.currentRoom];

		$('#selected-seats-for-pricing .price-item').each(function() {
			var $this = $(this);
			var boxLocation = $this.data('box-location');
			var price = parseInt($this.find('input').val());
			var box = currentRoom.boxes[boxLocation];

			box.changePrice(price);
			reg.needToSave = true;
		});
		$('#price-dialog').modal('hide');
		if(reg.activeBoxArray.length) {
			alertify.success(translator.translate('pricesAdded'));
		}
	});

	$('#set-seat-locks').on('click', function() {
		var currentRoom = reg.rooms[reg.currentRoom];

		$('#selected-seats-for-locking .lock-item').each(function() {
			var $this = $(this);
			var boxLocation = $this.data('box-location');
			var password = $this.find('input[type=text]').val();
			var locked = $this.find('input[type=checkbox]').is(":checked");
			var box = currentRoom.boxes[boxLocation];

			box.changePassword(password);
			box.changeLock(locked);
			reg.needToSave = true;
		});

		if(reg.activeBoxArray.length) {
			alertify.success(translator.translate('changesApplied'));
		}
	});

    $('#legend-dialog').dialog({
    	autoOpen: false,
    	width: 500,
    	//modal: true,
    	position: {my:"center", at:"center", of: window},
    	buttons: [ { text: "Close", class: 'btn btn-default', tabIndex: -1, click: function() { $( this ).dialog( "close" ); } } ],
    	closeOnEscape: true,
    	show: {
			effect: "drop",
			direction: "up"
		},
		hide: {
			effect: "drop",
			direction: "down"
		},
		open: function(event, ui) {
			$('#toggle-lcreator').removeClass('change-btn-to-red change-btn-to-green red-toggle').addClass('green-toggle');
			$('#legend-creator').removeAttr('style');

			var showInfo = cahngeLegendDialogMessage();

			if(showInfo) {
				$('.legend-dialog-info').css('display','block');
			}else {
				$('.legend-dialog-info').css('display','none');
			}

			cleanUpLegendDialog();

			if(reg.allLegends.length > 0) {
				$('.legend-dialog-commands').css('display','block');
			}else {
				$('.legend-dialog-commands').css('display','none');
			}

			$('.legend-dialog-upper').css('display','block');
		},
		close: function(event, ui) {
			$('#toggle-lcreator').text('Create new legend');
			$('.legend-creator').css('display','none');
			$('.legend-dialog-upper').css('display','block');
			$('#legend-change-wrap').css('display','none');
			$('.toggle-lcreator-wrap').css('display','block');

		}
    });

    //color picket for main dialog
	
	var seatColorPicker = new Picker({
		parent: document.querySelector('#picker'),
		popup: false,
		alpha: true,
		editor: true,
		editorFormat: 'rgb',
		color: '#0072CE',
		onDone: function (color) {
			reg.changeBoxColor(color.rgbaString);
			$('#color-dialog').modal('toggle');
			alertify.success(translator.translate('colorApplied'));	
		},
	});
	
    //color picker for legends
	var legendColorPicker = new Picker({
		parent: document.querySelector('#picker2'),
		popup: false,
		alpha: true,
		editor: true,
		editorFormat: 'rgb',
		color: '#61B329',
		onChange: function (color) {
			$('#dummy-legend .legend-box').css("background-color", color.rgbaString);
			$('#hiddenColor').val(color.rgbaString);
		},
	});

	/*
		*--------button click listeners--------
	*/

	$('#legend-creator .step-btn').on('click', function() {
		var currentSlide = $(this).attr('data-slide');
		var nextSlide = $(this).attr('data-slide-open');

		if(nextSlide == 1) { //step 1
			$('#legend-creator .legend-dialog-slide').animate({
				height: "140px"
			},1000, "easeOutCubic");

			$('#legend-creator').animate({
				marginLeft: "+=500px"	
			},1000,"easeOutCubic");

		}else if(nextSlide == 2) { //step 2
			if(currentSlide == 1) {  //step 1 open				
				if($('#new-legend-text').val() !== "") {
					if(!reg.legendNameExists($('#new-legend-text').val())) {
						$('#legend-creator .legend-dialog-slide').animate({
							height: "300px"
						},1000, "easeOutCubic");
						$('#legend-creator').animate({
							marginLeft: "-=500px"
						},1000,"easeOutCubic");
					}else {
						$('#new-legend-text-rem').html('<span style="color:red">'+ translator.translate('legendNameTaken') +'</span>');
					}
				}else {
					$('#new-legend-text-rem').html('<span style="color:red">' + translator.translate('lagendNameMissing') + '</span>');
				}
			}else if(currentSlide == 3) { //step 3 open
				$('#legend-creator .legend-dialog-slide').animate({
						height: "300px"
				},1000, "easeOutCubic");

				$('#legend-creator').animate({
					marginLeft: "+=500px"
				},1000,"easeOutCubic");
			}
		}else if(nextSlide == 3) { //step 3
			$('#legend-creator .legend-dialog-slide').animate({
						height: "160px"
					},1000, "easeOutCubic");

			$('#legend-creator').animate({
					marginLeft: "-=500px"
					
			},1000,"easeOutCubic");
		}
	});

	function cleanUpLegendDialog() {
		$('#dummy-legend .dialog-legend-text').text('');
		$('#new-legend-text').val('');
		$('#new-legend-text-rem').text('');
		$('#new-legend-text').removeClass('input-focus');
	}

	function cahngeLegendDialogMessage() {
		$('.legend-dialog-info').empty();
		var showNotify = false;
		var dialogInfoText = reg.activeBoxesInfo();

		if(reg.allLegends.length == 0) {
			showNotify = true;
			$('.legend-dialog-info').append('<li class="legend-dialog-info-box"><span class="glyphicon glyphicon-exclamation-sign"></span>'+ translator.translate('noLegendsCreated') +'</li>');
			$('.legend-dialog-commands').slideUp();
		}

		if(dialogInfoText != '') {
			$('.legend-dialog-div:first').css('display','block');
			$('#apply-legend').text('Add legend to ' + reg.activeBoxArray.length + ' boxes');
		}else {
			showNotify = true;
			$('#legend-dialog .legend-dialog-info').prepend('<li class="legend-dialog-info-box"><span class="glyphicon glyphicon-exclamation-sign"></span>'+ translator.translate('_noSelectBoxToAddLegend') +'</li>');
			$('.legend-dialog-div:first').css('display','none');
		}

		//return true when need to show notify
		if(showNotify) {
			return true;
		}else {
			return false;
		}
	}

	$('#toggle-lcreator').on('click', function() {
		var toggleBtn = $(this);

		if($('#legend-creator').is(':visible')) {
			//legend creator is open.now close it
			//button animation
			if($('html').hasClass('cssanimations')) {
				toggleBtn.addClass('change-btn-to-green green-toggle').removeClass('change-btn-to-red red-toggle');
				toggleBtn.text('Create new legen');
			}else {
				toggleBtn.addClass('green-toggle').removeClass('red-toggle').text(translator.translate('createLegend'));
			}

			//legend creator slide up
			$('#legend-creator').slideUp(400, function() {
				cleanUpLegendDialog(); //clean up
				$('.legend-dialog-slide').removeAttr('style');

				var showNotify = cahngeLegendDialogMessage();

				if(showNotify) {
					$('.legend-dialog-info').css('display','block');
				}else {
					$('.legend-dialog-info').css('display','none');
				}
				
				if(reg.allLegends.length > 0) {
					$('.legend-dialog-commands').css('display','block');
				}else {
					$('.legend-dialog-commands').css('display','none');
				}

				$('.legend-dialog-upper').slideDown();
		
			});

		}else {
			//legend creator is closed. must open it
			$('#legend-creator').removeAttr('style');

			if($('.legend-dialog-commands').is(':visible')) {
				//dialog-commands are visible

				$('.legend-dialog-upper').slideUp(400, function() {
					if($('html').hasClass('cssanimations')) {
						toggleBtn.addClass('change-btn-to-red red-toggle').removeClass('change-btn-to-green green-toggle');
						toggleBtn.text(translator.translate('cancelLegendCreation'));
					}else {
						toggleBtn.removeClass('green-toggle').addClass('red-toggle').text(translator.translate('cancelLegendCreation'));					
					}

					$('#legend-creator').slideDown(400);
				});

			}else {
				$('#new-legend-text').val('');

				if($('html').hasClass('cssanimations')) {
					toggleBtn.addClass('change-btn-to-red red-toggle').removeClass('change-btn-to-green green-toggle');
					toggleBtn.text(translator.translate('cancelLegendCreation'));
				}else {
					toggleBtn.addClass('red-toggle').removeClass('green-toggle').text(translator.translate('cancelLegendCreation'));	
				}

				$('.legend-dialog-upper').slideUp(400, function() {
					$('#legend-creator').slideDown();
				});
			}
		}
	});
	
	$('#create-new-legend').on('click', function() {
		if($('#new-legend-text').val() != '') {
			var added = reg.addLegendBox($('#new-legend-text').val(), $('#hiddenColor').val());

			if(added) {
				$('.legend-dialog-info-legend').remove();
				var showInfo = cahngeLegendDialogMessage(); 

				if(showInfo) {
					$('.legend-dialog-info').css('display','block');
				}else {
					$('.legend-dialog-info').css('display','none');
				}

				$('#legend-creator').slideUp(400, function() {
					cleanUpLegendDialog();

					if($('html').hasClass('cssanimations')) {
						$('#toggle-lcreator').addClass('change-btn-to-green green-toggle').removeClass('change-btn-to-red red-toggle');
						$('#toggle-lcreator').text('Create new legen');
					}else {
						$('#toggle-lcreator').addClass('green-toggle').removeClass('red-toggle').text(translator.translate('createLegend'));
					}

					$('.legend-dialog-commands').css('display','block');
					$('.legend-dialog-upper').slideDown();
				});
			}
			
		}else {
			$('#new-legend-text').addClass('input-focus').focus();
			$('#new-legend-text-rem').text(translator.translate('missingName'));
		}
	});

	$('#apply-legend').on('click', function() {
		var selectedLegend = $('#use-select :selected').text();

		if(selectedLegend == '') {
			alertify.alert(translator.translate('chooseLegend'));
		}else {
			reg.changeLegend(selectedLegend);
		}
	});

	$('#delete-legend').on('click', function() {
		var selectedLegend = $('#delete-select :selected').text();

		if(selectedLegend != '') {
			reg.removeLegend(selectedLegend);
			var showInfo = cahngeLegendDialogMessage();

			if(showInfo) {
				$('.legend-dialog-info').css('display','block');
			}else {
				$('.legend-dialog-info').css('display','none');
			}
		}
	});

	$('#change-legend').on('click', function() {
		if($('#legend-change-select :selected').text() != '') {
			openLegendChangeSection($('#legend-change-select :selected').text());
		}
	});

	$('#close-legend-change').on('click', function() {
		closeLegendChangeSection();
	});

	//open legend change section in dialog
	function openLegendChangeSection(legendName) {
		var legend = legendName;
		var color = null;

		var arrLength = reg.allLegends.length;
		for(var i = 0; i < arrLength; i++) {
			if(reg.allLegends[i].text == legendName) {
				color = reg.allLegends[i].color;
				break;
			}
		}

		$('#legend-change-wrap-inner').removeAttr('style');
		$('#legend-change-wrap .legend-box-2').css('background-color', color);
		$('#legend-change-wrap .dialog-legend-text-2').text(legend);
		$('.toggle-lcreator-wrap').css('display','none');
	
		$('.legend-dialog-upper').slideUp(400, function() {
			$('#legend-change-wrap-inner').css('margin-left','-500px');
			$('#legend-change-wrap').slideDown(400, function() {
			});
		});
	}

	function colorToHex(color) {
	    if (color.substr(0, 1) === '#') {
	        return color;
	    }

	    var digits = /(.*?)rgb\((\d+), (\d+), (\d+)\)/.exec(color);
	    var red = parseInt(digits[2]);
	    var green = parseInt(digits[3]);
	    var blue = parseInt(digits[4]);
		var rgb = blue | (green << 8) | (red << 16);
		
	    return digits[1] + '#' + rgb.toString(16);
	}

	function closeLegendChangeSection() {
		$('#legend-change-wrap').slideUp(400, function() {
			$('.toggle-lcreator-wrap').css('display','block');
			$('.legend-dialog-upper').slideDown(400);
		})
	}

	var changeLegendsColorPicker = null;
	$('#legend-change-wrap-inner .change-btn').on('click', function() {
		var currentSlide = $(this).attr('data-slide');
		var targetSlide = $(this).attr('data-slide-open');
		

		if(targetSlide == 1) {
			$('#new-legend-name-info').empty();
			$('#new-legend-name, #old-legend-name').val($('#legend-change-wrap-inner .dialog-legend-text-2').text());
			$('#legend-change-wrap-inner').animate({
				marginLeft: "+=500px",
				height: "150px"
			},1000,"easeOutCubic");
		}else if(targetSlide == 3) {
			$('#new-legend-color-info').empty();
			var currentColor = $('#legend-change-wrap-inner .legend-box-2').css('background-color');

			if ( changeLegendsColorPicker ) {
				changeLegendsColorPicker.setColor(currentColor);		    
				//$('#legend-change-color-pic').colpickSetColor(currentColor.replace('#',''),true);
			}else {
				changeLegendsColorPicker = new Picker({
					parent: document.querySelector('#legend-change-color-pic'),
					popup: false,
					alpha: true,
					editor: false,
					editorFormat: 'rgb',
					color: currentColor,
					onChange: function (color) {
						$('#change-chosen-color').val(color.rgbaString);	
					},
				});
			}
			$('#legend-change-wrap-inner').animate({
				marginLeft: "-=500px",
				height: "300px"
			},1000,"easeOutCubic");

		}else if(targetSlide == 2) {
			if(currentSlide == 1) {
				$('#legend-change-wrap-inner').animate({
					marginLeft: "-=500px",
					height: "150px"
				},1000,"easeOutCubic");
			}else {
				$('#legend-change-wrap-inner').animate({
					marginLeft: "+=500px",
					height: "150px"
				},1000,"easeOutCubic");
			}	
		}
	});

	$('#apply-new-legend-name').on('click', function() {
		var newLegend = $('#new-legend-name').val();
		var oldLegend = $('#old-legend-name').val();

		if(newLegend != '') {
			if(!reg.legendNameExists(newLegend)) {
				reg.changeLegendTo(oldLegend, newLegend);
				$('.dialog-legend-text-2').text(newLegend);
				$('#legend-change-wrap-inner').animate({
					marginLeft: "-=500px",
				},1000,"easeOutCubic");
			}else {
				$('#new-legend-name-info').html('<span style="color:red">'+ translator.translate('legendNameTaken') +'</span>');
			}
		}else {
			$('#new-legend-name-info').html('<span style="color:red">'+ translator.translate('enterLegendName') +'</span>');
			$('#new-legend-name').focus();
		}
	});

	$('#apply-new-legend-color').on('click', function() {
		var chosenColor = $('#change-chosen-color').val();
		var legendName = $('#legend-change-wrap-inner .dialog-legend-text-2').text();

		if(!reg.legendColorExists(chosenColor)) {
			reg.changeLegendColorTo(legendName,chosenColor);
			reg.reColorLegendBoxes(legendName,chosenColor);

			$('.legend-box-2').css('background-color',chosenColor);
			$('#legend-change-wrap-inner').animate({
					marginLeft: "+=500px",
					height: "130px"
			},1000,"easeOutCubic");
		}else {
			$('#new-legend-color-info').html('<span style="color:red">'+ translator.translate('legendColorTaken') +'</span>');
		}
	});

	$('#delete-legend-from-room').on('click', function() {
		var selectedLegend = $('#legend-delete-select-room :selected').text();

		if(selectedLegend != '') {
			reg.rooms[reg.currentRoom].removeLegendFromRoom(selectedLegend);
			reg.rooms[reg.currentRoom].removeLegendFromRoomBoxes(selectedLegend);
			reg.updateLegendSelect();
		}
	});

	$('#new-legend-text').keyup(function() {
		$('#dummy-legend .dialog-legend-text').text($(this).val());
	});

	//adds new room
	$('#new-room-create').on('click', function() {
            reg.addRoom(false,true,true, generateUUID());
            $('#room-name-dialog').modal("toggle"); 	
	});

	$('#current-room-delete').on('click', function() {
		//do i have bron or reg seats?

		if(!reg.rooms[reg.currentRoom].bronOrRegCheck()) {
			alertify.set({ 
				labels: {
			    	ok     : translator.translate('ok'),
			    	cancel: translator.translate('cancel')
				},
				buttonFocus: "cancel"  
			});

			alertify.confirm(translator.translate('deleteRoom_') + reg.rooms[reg.currentRoom].title + " ?", function (e) {
			    if (e) {
			        reg.deleteCurrentRoom();
			    }
			});

		}else {
			alertify.set({ 
				labels: {
			    	ok     : translator.translate('ok'),
			    	cancel: translator.translate('cancel')
				},
				buttonFocus: "ok"  
			});

			alertify.alert(translator.translate('cantDelRoom_') +'<span class="bold-text">' + reg.rooms[reg.currentRoom].title + '</span>' + translator.translate('_cantDelRoomBecause'));
		}
	});

	$('#update-data').on('click', function() {
		$(this).prop('disabled', true);
		$('#update-data').find('.save-text').text(translator.translate('saving'));
		
		if(reg.sendValidation()) {
			reg.rooms[reg.currentRoom].correctRoomBoxesIndex();
			reg.roomWidthAndHeight();
			reg.updateData();
		}

		$(this).blur().prop('disabled', false);
	});

	$('#box-hover-submit').on('click', function() {
		reg.checkBubbles();

		if(reg.activeBoxArray.length > 0) {
			var hoverValue = $('#box-hover-text').val().replace(/\n|\r/g, '<br>'); //.replace(/\n|\r/g, '<br>'); //&lt;br/&gt; 

			reg.changeHoverText(hoverValue);
			$("#hover-dialog").modal('toggle');
		}
	});

	$('.register-status').on('click', function(){
		reg.changeBoxRegisterStatus();
	});

	$('.bubble-text').on('click', function() {
		reg.checkBubbles(); //checks for existing boxes with hover bubble 

		if(reg.activeBoxArray.length > 0) {
			$("#hover-dialog").modal('toggle');
		}	
	});

	//legend inoc click
	$('.legend-option').on('click', function() {
			if(reg.canOpenColor == true) {
				reg.updateLegendSelect();
				$('#legend-creator').css('display','none');
				$("#legend-dialog").dialog("open");
			}
	});

	//palette icon click
	$('.palette-call').on('click', function(){
		if(reg.activeBoxArray.length > 0) {
			$("#color-dialog").modal("toggle");
		}else {
			var palleteGuide = '<div><div class="guide-block">'+ translator.translate('toSelectOneBox_') +'<div class="guide-item guide-item-mouse"></div></div><br><div class="guide-block">'+ translator.translate('toSelectMultiBox_') +'<div class="guide-item guide-item-lasso"></div></div>';
			alertify.set({ 
				labels: {
			    	ok     : translator.translate('ok'),
				},
				buttonFocus: "ok"  
			});
			alertify.alert(translator.translate('selectBoxesToAddColor') + palleteGuide);
		}
	});

	//selects room
	$('#print-active-boxes').on('click', function() {
	});

	$('.mouse-action-boxes .mouse-option').on('click', function(){   //mouse action menu
		reg.mouseActionChange($(this));
	});

	//will be called when room number is clikked

	//building skeleton
	$('.build-skeleton').on('click', function(){
		$('.skeleton-box').remove();	//removes all skeleton boxes from build area

		var sizeX = parseInt($('#size-x').val());
		var sizeY = parseInt($('#size-y').val());
		var countX = parseInt($('#count-x').val());
		var countY = parseInt($('#count-y').val());
		var marginX = parseInt($('#margin-x').val());
		var marginY = parseInt($('#margin-y').val());
		var grid = 0;

		reg.rooms[reg.currentRoom].skeleton.changeSkeleton(sizeX, sizeY, countX, countY, marginX, marginY, grid);
		
		//build skeleton
		reg.buildSkeleton();
		reg.needToSave = true;
		alertify.success(translator.translate('buildingGridUpdated'));
	});

	$('.delete-skeleton').on('click', function(){
		$('.skeleton-box').remove();
	});

	//detete single or many boxes
	$('.delete-box').on('click', function(){
		reg.deleteBoxes();
		reg.showClickControls();
	});

	$('.change-room-name').on('click', function() {
		$('#room-name-dialog').modal("toggle");
	});

	$('#room-dialog-ok').on('click', function() {
		if($('#room-name-dialog-input').val() == '') {
			$('#room-name-dialog-input').focus();
			$('.room-name-error').text(translator.translate('roomNameMissing')).css('display','block');
		}else {
			if(!reg.roomNameExists($('#room-name-dialog-input').val())) {
				var oldRoomName = reg.rooms[reg.currentRoom].title;
				reg.rooms[reg.currentRoom].title = $('#room-name-dialog-input').val();
				$('.room-title-name').text(reg.rooms[reg.currentRoom].title);
				$('#room-selection-wrapper .room-selection[data-room-location="'+ reg.currentRoom +'"]').text(reg.rooms[reg.currentRoom].title);

				if(oldRoomName != "") {
					var newRoom = reg.rooms[reg.currentRoom].title;
					var initName = reg.rooms[reg.currentRoom].initialName;

					reg.roomNameChange[initName] = newRoom;

					alertify.success(translator.translate('roomNameChanged'));
				}else {
					if(reg.rooms[reg.currentRoom].initialName == "") {
						reg.rooms[reg.currentRoom].initialName = reg.rooms[reg.currentRoom].title;
					}
					alertify.success(translator.translate('roomNameSet'));
				}
				
				$('#room-name-dialog').modal('toggle');
				reg.needToSave = true;
				
			}else {
				$('.room-name-error').text(translator.translate('roomNameExists')).css('display','block');
			}
		}
	});
	   
    $('#room-name-dialog-input').keyup(function(e) {		
    	if (e.which == 13) {
			$(this).blur();
			$('#room-dialog-ok').click();
    	}
    });

    $('.save-check').on('click', function(event) {
    	var location = $(this).attr('href');

    	if(reg.needToSave) {
    		event.preventDefault();

    		alertify.set({ 
				labels: {
			    	ok     : translator.translate('yes'),
			    	cancel: translator.translate('no')
				},
				buttonFocus: "cancel"  
			});

    		alertify.confirm(translator.translate('unsavedChanges'),function(e) {
    			if (e) {
    				window.open(location,"_self");
				} 
    		});
    	}
    });

    $('.click-control-right .fa').on('click', function() {
    	var destination = $(this).data('destination');
    	var destinationAm = Math.abs($('#click-control-move-nr').val());

    	reg.prepareMoveActiveBoxes(destination, destinationAm);
	});

	$('.progress').css({'display': 'none'});

	var imageSubmitOptions = {
		data: {
			security: WP_Seatreg.nonce
		}, 
		beforeSubmit:  function() {
			$('.progress').show();
		},
		uploadProgress: function(event, position, total, percentComplete) {
			$('.progress-bar').width(percentComplete + '%');
			$('.progress .sr-only').text(percentComplete + '%');
		},
		success:  function() {
			$('.progress').hide();
		},
		complete: function(response) {
			 $('#reset-btn').click();
			 var respObjekt = $.parseJSON(response.responseText);

			 if(respObjekt.type == 'ok') {
				 $('#img-upload-resp').html('<div class="alert alert-success" role="alert">' + respObjekt.text + '</div>');  

				 var imgRem = $(' <span class="up-img-rem" data-img="'+ respObjekt.data +'"></span>').append('<span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Remove');
				 var addImg = $(' <span class="add-img-room" data-img="'+ respObjekt.data +'" data-size="'+ respObjekt.extraData +'"></span>').append('<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Add to room');
				 var upImgBox = $('<div class="uploaded-image-box"></div>').append('<img src="' + window.WP_Seatreg.plugin_dir_url + 'uploads/room_images/' + seatreg.selectedRegistration + '/' + respObjekt.data + '" class="uploaded-image" /> ', addImg, imgRem);

				 $('#uploaded-images').append(upImgBox);
			 }else if(respObjekt.type == 'error'){
				 $('#img-upload-resp').html('<div class="alert alert-danger" role="alert">' + respObjekt.text + '</div>');
			 }
		}
	}; 

	$('#room-image-submit').ajaxForm(imageSubmitOptions); 
	$('#uploaded-images').on('click', '.up-img-rem', function() {
		var imgName = $(this).data('img');
		var thisLoc = $(this);

		$.ajax({
			type:'POST',
			url: ajaxurl,
			data: {
				imgName:imgName,
				code: seatreg.selectedRegistration,
				security: WP_Seatreg.nonce,
				action: 'seatreg_remove_img',
			},
			success: function(data) {
				var response = $.parseJSON(data);

				if(response.type == 'ok') {
					reg.removeImgAllRooms(imgName);
					thisLoc.closest('.uploaded-image-box').remove();

					if(reg.rooms[reg.currentRoom].backgroundImage === imgName) {
						reg.removeCurrentRoomImage();
						$('#activ-room-img-wrap').empty().text(translator.translate('noBgImageInRoom'));
					}
					
				}else if(response.type == 'error') {
					console.log(response.text);
				}
			}
		});
	});

	$('#uploaded-images').on('click', '.add-img-room', function() {	
		reg.setRoomImage($(this).data('img'), $(this).data('size'));

		var curImgWrap = $('<div class="cur-img-wrap"></div>');
		var bgImg = $('<img class="uploaded-image" src="' + window.WP_Seatreg.plugin_dir_url + 'uploads/room_images/' + seatreg.selectedRegistration + '/' + reg.rooms[reg.currentRoom].backgroundImage + '" />');
		var remImg = $('<span id="rem-room-img"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> '+ translator.translate('removeFromRoom') +'</span>');

		curImgWrap.append(bgImg, remImg);

		$('#activ-room-img-wrap').empty().append(curImgWrap);
	});

	$('#background-image-modal').on('show.bs.modal', function() {
		$('#activ-room-img-wrap').empty();

		if(reg.rooms[reg.currentRoom].backgroundImage !== null) {
			var curImgWrap = $('<div class="cur-img-wrap"></div>');
			var bgImg = $('<img class="uploaded-image" src="'+ window.WP_Seatreg.plugin_dir_url +'uploads/room_images/' + seatreg.selectedRegistration + '/' + reg.rooms[reg.currentRoom].backgroundImage + '" />');
			var remImg = $('<span id="rem-room-img"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> '+ translator.translate('removeFromRoom') +'</span>');

			curImgWrap.append(bgImg, remImg);
			$('#activ-room-img-wrap').append(curImgWrap);
		}else {
			$('#activ-room-img-wrap').html(translator.translate('noBgImageInRoom'));
		}
	});

	$('#activ-room-img-wrap').on('click', '#rem-room-img', function() {
		reg.removeCurrentRoomImage();
		$('.room-image').remove();
		$(this).closest('.cur-img-wrap').remove();
		$('#activ-room-img-wrap').html(translator.translate('noBgImageInRoom'));
	});
	
	$('#file-sub').on('click', function(e) {
		var picName = $('#img-upload').val().split(/(\\|\/)/g).pop();
		var re = /^[0-9a-zA-Z\-._]{1,90}$/;
		$('#urlCode').val(seatreg.selectedRegistration);

		if(picName == '') {
			e.preventDefault();
			$('#img-upload-resp').html('<div class="alert alert-danger" role="alert">'+ translator.translate('choosePictureToUpload') +'</div>');
		}else {
			if(!re.test(picName)) {
				e.preventDefault();
				$('#img-upload-resp').html('<div class="alert alert-danger" role="alert">'+ translator.translate('imageNameIllegalChar') +'</div>');
			}
		}
	});
})(jQuery);