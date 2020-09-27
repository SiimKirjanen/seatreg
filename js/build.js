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

	function Permissions() {
		this.canDeleteBox = true;
		this.canCreateBox = true;
		this.canMoveBox = true;
		this.canResizeBox = true;
		this.canCreateRoom = true;
		this.canDeleteRoom = true;
	}

	//changing permissions
	Permissions.prototype.changePermissions = function(canDelBox,canCreBox,canMovBox,canResBox,canCreRoom,canDelRoom){
		this.canDeleteBox = canDelBox;
		this.canCreateBox = canCreBox;
		this.canMoveBox = canMovBox;
		this.canResizeBox = canResBox;
		this.canCreateRoom = canCreRoom;
		this.canDeleteRoom = canDelRoom;
	};

	/*
		*--------------building help object---------------	
	*/

	var buildHelp = {
		est: {
			defaultText: 'Tere ma üritan sind aidata',
			recycleBinHover: 'Selle abil saad selekteeritud kaste kustutada',
			legendHover: 'Saad teha registratsioonile legende (abistavatele objektidele)',
			customBoxCreateHover: 'Saad muuda registreerimis kohti tavalisteks kastideks',
			bubbleTextHover: 'Saad anda kastile/kastidele hõljumis teksti',
			paletteHover: 'Saad värvida kaste. NB! Värvida ei saa registreerimis kohti (need on rohelised)',
			gridHover: 'Saad muuta abiruudustikku.',
			mouseAction1Hover: 'Selle abil saad erinevaid kaste ringi liigutada, suurust muuta',
			mouseAction2Hover: 'Selel abil saad luua kiiresti registreerimis kohti. Hoia vasakut hiireklahvi all ja liiguta ringi või kliki abiruudustiku peale',
			mouseAction3Hover: 'Kustukummiga saad kustutada tehtud kaste',
			mouseAction4Hover: 'Lasso tööriist. Selle abil saad selekteerida mitu kasti korraga',
			mouseAction5Hover: 'Selle abil saad luua abistavaid objekte (näiteks uks). Hoia vasakut hiireklahvi all ja liiguta ringi või kliki abiruudustiku peale'
		},
		eng: {

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
	function Box(title, xPos, yPos, xSize, ySize, id, color, hoverText, canIRegister, seat, status, zIndex) {
		this.legend = title;
		this.xPosition = xPos;
		this.yPosition = yPos;
		this.width = xSize;
		this.height = ySize;
		this.color = color;
		this.hoverText = hoverText;
		this.id = id;
		this.canRegister = canIRegister;
		this.seat = seat;
		this.status = status;
		this.zIndex = zIndex;
		////console.log('creating box with z-index: ' + zIndex);
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

	//change position
	Box.prototype.changePosition = function(xPos, yPos) {
		this.xPosition = xPos;
		this.yPosition = yPos;
		reg.needToSave = true;
	};
	Box.prototype.changeZIndex = function(newIndex) {
		this.zIndex = newIndex;
	};

	//change color
	Box.prototype.changeColor = function(color) {
		this.color = color;
		this.legend = "noLegend";
		reg.needToSave = true;
	};

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
	function Room(id) {
		this.title = "";		//room title.
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

	//returns object with room info. skeleton and boxes
	Room.prototype.returnRoomData = function() {
		var roomData = {};	//stores skeleton and boxes info
		roomData['l'] = [];
		var arrLength2 = reg.allLegends.length;

		for( var j = 0; j < arrLength2; j++) {
			roomData['l'].push([reg.allLegends[j].text, reg.allLegends[j].color]);
		}

		roomData['g'] = [reg.roomLocator,reg.regBoxCounter];

		var roomLegendArray = [];

		var legendsLength = this.legends.length;

		for(var c = 0; c < legendsLength; c++) {
			roomLegendArray.push([this.legends[c].text, this.legends[c].color]);
		}

		roomData['room'] = [this.roomId, this.title, this.roomText, roomLegendArray, this.roomWidth + 10, this.roomHeight + 10, this.roomSeatCounter, this.backgroundImage];
		roomData['skeleton'] = [this.skeleton.width, this.skeleton.height, this.skeleton.countX, this.skeleton.countY, this.skeleton.marginX, this.skeleton.marginY, this.skeleton.buildGrid];
		roomData['boxes'] = [];

		var arrLength = this.boxes.length;
		for(var i = 0; i < arrLength; i++) {
			var canReg = this.boxes[i].canRegister;
			if(canReg) {
				canReg = "true";
			}else{
				canReg = "false";
			}
			roomData['boxes'].push([this.boxes[i].legend, Math.round(this.boxes[i].xPosition), Math.round(this.boxes[i].yPosition), Math.round(this.boxes[i].width), Math.round(this.boxes[i].height), this.boxes[i].color, this.boxes[i].hoverText.replace(/<br>/g,'^'), this.boxes[i].id, canReg, this.boxes[i].seat,'noStatus', this.boxes[i].zIndex]);
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

	//adds box to room
	Room.prototype.addBox = function(title,posX,posY,sizeX,sizeY,id,color,hoverText,canIRegister,status,zIndex) {
		if(canIRegister) {
			this.roomSeatCounter++;
		}

		this.boxes.push(new Box(title,posX,posY,sizeX,sizeY,id,color,hoverText,canIRegister,this.roomSeatCounter,status,zIndex));
		reg.needToSave = true;
		this.boxCounter++;
		reg.regBoxCounter++;
		
		$('.room-box-counter').text(this.boxes.length);
	};

	//add box to room from server
	Room.prototype.addBoxS = function(title,posX,posY,sizeX,sizeY,id,color,hoverText,canIRegister,status,boxZIndex) {
		if(canIRegister) {
			this.roomSeatCounter++;
		}
		this.boxes.push(new Box(title,posX,posY,sizeX,sizeY,id,color,hoverText,canIRegister,this.roomSeatCounter,status,boxZIndex));
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
			if(this.boxes[location].canRegister != true) {
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
				if(this.bronOrRegCheck()) {
					var lastNr = this.lastBronOrTaken();

					if(this.boxes[location].seat > lastNr) {						
						this.seatNumberReOrder(this.boxes[location].seat); 
						this.roomSeatCounter--;
						this.boxes.splice(location, 1);
						reg.needToSave = true;
						$('.room-box-counter').text(this.boxes.length);
						$('.drag-box[data-id="' + id +'"]').remove();

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
				}else {
					this.seatNumberReOrder(this.boxes[location].seat); 
					this.roomSeatCounter--;
					this.boxes.splice(location, 1);
					reg.needToSave = true;
					$('.room-box-counter').text(this.boxes.length);
					$('.drag-box[data-id="' + id +'"]').remove();

					if(legendCheck != null) {
						if(reg.canRemoveLegendRoom(legendCheck)) {
							this.removeLegendFromRoom(legendCheck);
						}
					}
				}
			}

		} else {  //location check else
		}
	};

	Room.prototype.seatNumberReOrder = function(seatnr) {
			var arrLength = this.boxes.length;

			for(var i = 0; i < arrLength; i++) {
				if(this.boxes[i].seat > seatnr && (this.boxes[i].canRegister == true || this.boxes[i].status == 'bronRegister' || this.boxes[i].status == 'takenRegister')) {
					var newNumber = this.boxes[i].seat - 1;

					$('.build-area .drag-box[data-seatnr="' + this.boxes[i].seat + '"]').attr('data-seatnr', newNumber).find('.seat-number').text(newNumber);
					this.boxes[i].seat -= 1;
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
		this.permissions = new Permissions();
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
	}

	Registration.prototype.clearRegistrationData = function() {
		this.rooms = {};		//rooms obj. stores room objects
		this.roomLocator = 1;	//helps to find room in rooms array.
		this.currentRoom = 1;	//what room is selected
		this.roomLabel = 1;
		this.activeBoxArray = [];	//user select box or boxes
		this.action = 1; //mouse action: 1 = regular, action 2 = creator, action 3 = delete, action 4 = lasso
		this.needMultiDrag = false;
		this.permissions = new Permissions();
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

		var bgImg = $('<img class="room-image" src="../wp-content/plugins/seatreg_wordpress/' + myLanguage.getLang('bgImgDir')  + seatreg.selectedRegistration  + '/' + imgLog + '" />');
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
				$imgWrap.append("<img src='../wp-content/plugins/seatreg_wordpress/uploads/room_images/" + window.seatreg.selectedRegistration + "/" + uploaded.file + "' class='uploaded-image' />");
				$imgWrap.append("<span class='add-img-room' data-img='"+ uploaded.file +"' data-size='" + uploaded.size[0] + "," + uploaded.size[1] + "'><span class='glyphicon glyphicon-ok' aria-hidden='true'></span> Add to room background</span>");
				$imgWrap.append("<span class='up-img-rem' data-img='"+ uploaded.file +"'><span class='glyphicon glyphicon-remove' aria-hidden='true'></span> Remove</span>");

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
					if(newHover != '') {
						this.rooms[this.currentRoom].boxes[index].hoverText = newHover;
						$('.build-area .drag-box[data-id="' + this.activeBoxArray[i] + '"]').attr('original-title',newHover).addClass('box-hover');
					}else {
						this.rooms[this.currentRoom].boxes[index].hoverText = "nohover";
						$('.build-area .drag-box[data-id="' + this.activeBoxArray[i] + '"]').removeClass('box-hover');
					}
				}else {
					alert(myLanguage.getLang('hoverError'));
				}
			}

			if(newHover == '') {
				alertify.success(myLanguage.getLang('hoverDeleteSuccess'));
			}else {
				alertify.success(myLanguage.getLang('hoverTextAdded'));
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
				    	ok     : myLanguage.getLang('ok'),
				    	cancel: myLanguage.getLang('cancel')
					},
					buttonFocus: "ok"  
				});

				alertify.alert(myLanguage.getLang('legendNameTaken'));

				return false;
			}
			if(this.allLegends[i].color == color) {
				alertify.set({ 
					labels: {
				    	ok     : myLanguage.getLang('ok'),
				    	cancel: myLanguage.getLang('cancel')
					},
					buttonFocus: "ok"  
				});

				alertify.alert(myLanguage.getLang('legendColorTaken'));
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
				alert(myLanguage.getLang('legendChangeError'));
			}

		}

		alertify.success(myLanguage.getLang('legendAddedTo_') + arrLength + myLanguage.getLang('_boxes'));
		this.afterColorChange(oldLegend);
		this.createLegendBox();

	};

	//info for legend and color dialogs. 
	Registration.prototype.activeBoxesInfo = function(targetAction) {
		var arrLength = this.activeBoxArray.length;
		var howManyCustom = 0;
		var howManyReg = 0;


		for(var i = 0; i < arrLength; i++) {
			var index = this.rooms[this.currentRoom].findBox(this.activeBoxArray[i]);
			
			if(index !== false) {
				if(this.rooms[this.currentRoom].boxes[index].canRegister == true) {
					howManyReg++;
				}else {
					howManyCustom++;
				}
			}else {
			}
		}

		var activeBoxesInfoString = '';
		if(arrLength > 0) {
			activeBoxesInfoString = myLanguage.getLang('liYouHaveSelectedSpan_') + arrLength + myLanguage.getLang('_boxes');
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

	//removing legend from boxes when delete legend from registration (dialog)
	Registration.prototype.removeLegendFromBoxes = function(legendText) {
		var roomsLength = Object.size(this.rooms);

		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		    	var roomBoxLength = this.rooms[property].boxes.length;

		    	for(var j = 0; j < roomBoxLength; j++) {
					if(this.rooms[property].boxes[j].legend == legendText) {
						this.rooms[property].boxes[j].legend = "noLegend";
					}
				}
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
					textSpan.text(myLanguage.getLang('pendingSeat'));
					break;

				case 1:
					colorBox.css('background-color','red').addClass('legend-box-circle');
					textSpan.text(myLanguage.getLang('confirmedSeat'));
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

	Registration.prototype.changeHelperText = function(textCode) {
		$('#help-area .help-text').empty();		
		$('#help-area .help-text').text(buildHelp[this.regLang][textCode]);		
	};

	//check if active boxes have hover text already. if so then add text to existingHover array
	Registration.prototype.checkBubbles = function() {
		this.existingHover.length = 0;

		if(this.activeBoxArray.length == 0) {
			alertify.set({ 
				labels: {
			    	ok     : myLanguage.getLang('ok'),
			    	cancel: myLanguage.getLang('cancel')
				},
				buttonFocus: "ok"  
			});
			var hoverGuide = '<div><div class="guide-block">'+ myLanguage.getLang('toSelectOneBox_') +'<div class="guide-item guide-item-mouse"></div></div><br><div class="guide-block"> '+ myLanguage.getLang('toSelectMultiBox_') +' <div class="guide-item guide-item-lasso"></div></div>';
			alertify.alert('<span class="bold-text">' + myLanguage.getLang('selectBoxesToAddHover') + hoverGuide);

			return;
		}
		
		var arrLength = this.activeBoxArray.length;

		for(var i = 0; i < arrLength; i++) {
			var index = this.rooms[this.currentRoom].findBox(this.activeBoxArray[i]);

			if(index !== false) {
				if(this.rooms[this.currentRoom].boxes[index].hoverText != "nohover") {
					this.existingHover.push(this.rooms[this.currentRoom].boxes[index].hoverText);
				}
			}else {
				alert(myLanguage.getLang('hoverError'));
			}
		}		
	};

	//adds new room object to registration. new Room object to assosiative array. 1: firstRoom, 2: secondRoom
	Registration.prototype.addRoom = function(ignoreLimit,boxIndexSave,buildSkeleton){
		var regScope = this;

		if(this.permissions.canCreateRoom == true) {
			
			if(boxIndexSave) {
				this.rooms[this.currentRoom].correctRoomBoxesIndex();
			}

			if(ignoreLimit == false) {
				this.needToSave = true;
			}
			
			this.rooms[this.roomLocator] = new Room(this.roomLocator);
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
			}).text(regScope.roomLabel).on('click', function() {
				var loadingImg = $('<img>', {
					"src": window.WP_Seatreg.plugin_dir_url + "css/loading.png",
					"id": "loading-img"
					
				});

				var imgWrap = $('<div>', {
					"id": "build-area-loading-wrap"
				}).append(loadingImg, "<span class='loading-text'>"+ myLanguage.getLang('loading') +"</span>");

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
			$('.room-title-name').text('');
			
		}else {
			alert(myLanguage.getLang('noPermToAddRoom'));
		}
	};

	Registration.prototype.deleteCurrentRoom = function() {
		var size = Object.size(this.rooms);

		if(size == 1) {
			alert(myLanguage.getLang('oneRoomNeeded'));
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
	Registration.prototype.roomNameExists = function(roomName) {
		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		        if(this.rooms[property].title.toLowerCase() == roomName.toLowerCase()) {

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

			if($('.build-area-wrapper').hasClass('ui-selectable')) {
				$( ".build-area-wrapper" ).selectableScroll( "destroy" );
			}

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

			$('.room-title-name').text(this.rooms[this.currentRoom].title);
			$('.room-box-counter').text(this.rooms[this.currentRoom].boxes.length);

			if(reg.rooms[reg.currentRoom].bronOrRegCheck()) {
				if($('html').hasClass('cssanimations')) {
					this.animationRunning = true;
					$('#build-section-message-wrap').css('display','block').addClass('animated bounceIn').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function() {
						$(this).removeClass('animated bounceIn');
						reg.animationRunning = false;
					});
				}else {
					this.animationRunning = true;
					$('#build-section-message-wrap').show('bounce',{distance: 20, times: 4}, 1200, function() {
						reg.animationRunning = false;
					});
				}
			}else {
				$('#build-section-message-wrap').css('display','none');
			}
		}else {
			$('#build-area-loading-wrap').remove();
			alertify.set({ 
				labels: {
			    	ok     : myLanguage.getLang('ok'),
			    	cancel: myLanguage.getLang('cancel')
				},
				buttonFocus: "ok"  
			});
			alertify.alert(myLanguage.getLang('alreadyInRoom'));
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

	//can change box color also
	Registration.prototype.changeBoxColor = function(colorHex) {
		if(this.action == 1) {
			var index = this.rooms[this.currentRoom].findBox(this.activeBoxArray[0]);

			if(index !== false) {
				var oldLegend = [this.rooms[this.currentRoom].boxes[index].legend];

				$('.build-area').find("[data-id='" + this.activeBoxArray[0] +"']").css('background-color','#'+colorHex);
				this.rooms[this.currentRoom].boxes[index].changeColor('#'+colorHex);
				this.afterColorChange(oldLegend);
			}else {
				
			}
					
		}else if(this.action == 4) {
			var arrLength = this.activeBoxArray.length;
			var oldLegends = [];  //store box legends before new color

			for(var i = 0; i < arrLength;i++) {
				var index = this.rooms[this.currentRoom].findBox(this.activeBoxArray[i]);

				if(index !== false) {
					oldLegends.push(this.rooms[this.currentRoom].boxes[index].legend);
					this.rooms[this.currentRoom].boxes[index].changeColor('#'+colorHex);
					$('.build-area').find("[data-id='" + this.activeBoxArray[i] +"']").css('background-color','#'+colorHex);
				}else {
					
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
						this.rooms[this.currentRoom].seatNumberReOrder(location.seat);
						this.rooms[this.currentRoom].roomSeatCounter--;
						location.seat = 0;
						location.legend = 'custom';
						$('.build-area .drag-box[data-id="' + this.activeBoxArray[i] + '"]').removeClass('can-register').addClass('no-register').removeAttr('data-seatnr').css('background-color', '#ccc').find('.seat-number').text('');
					}
				}else {
					//console.log('Major error....changeBoxNoRegister');
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
		alertify.success(myLanguage.getLang('legendNameChanged'));
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
		alertify.success(myLanguage.getLang('legendColorChanged'));
	};

	//deletes box or boxes which are active. in activeboxes array. for mouse 1 and 4.
	Registration.prototype.deleteBoxes = function() {
		if(!this.permissions.canDeleteBox) {
			alert(myLanguage.getLang('noPermToDel'));
			return;
		}

		if(this.action == 1) {	//normal mouse delete
			if(this.activeBoxArray.length == 0) {	//no boxes selected
				var delGuide = '<div><div class="guide-block">'+ myLanguage.getLang('toSelectOneBox_') +' <div class="guide-item guide-item-mouse"></div></div><br><div class="guide-block">'+ myLanguage.getLang('toSelectMultiBox_') +'<div class="guide-item guide-item-lasso"></div></div>';
				
				alertify.set({ 
					labels: {
				    	ok     : myLanguage.getLang('ok'),
				    	cancel: myLanguage.getLang('cancel')
					},
					buttonFocus: "ok"  
				});

				alertify.alert('<span class="bold-text">'+ myLanguage.getLang('selectBoxesToDelete') +'</span>' + delGuide);

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
				    	ok     : myLanguage.getLang('ok'),
				    	cancel: myLanguage.getLang('cancel')
					},
					buttonFocus: "ok"  
				});
				alertify.alert(myLanguage.getLang('selectBoxesToDelete'));

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
			var bgImg = $('<img class="room-image" src="../wp-content/plugins/seatreg_wordpress/'+ myLanguage.getLang('bgImgDir') + seatreg.selectedRegistration + '/' + this.rooms[this.currentRoom].backgroundImage + '" />');
			
			$('.build-area').append(bgImg);
		}

		if(regScope.permissions.canCreateBox) {	//do i have permissions for that?
			$('.build-area .skeleton-box').on('click', function(){	//skeleton-box click				
			}).on('mouseenter', function(){	//when mouse enters element
				
				if(regScope.action == 2 && leftButtonDown == true) {
					var skelStyle = $(this).attr('style');
					var dataCounter = 'b' +  regScope.regBoxCounter;
					regScope.rooms[regScope.currentRoom].addBox("noLegend",parseInt($(this).css('left')), parseInt($(this).css('top')), parseInt($(this).css('width')), parseInt($(this).css('height')), dataCounter, '#61B329', 'nohover',true, "noStatus", 1);  //add box to room
					regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, true);
				}else if(regScope.action == 5 && leftButtonDown == true) {
					var skelStyle = $(this).attr('style');
					var dataCounter = 'b' +  regScope.regBoxCounter;
					regScope.rooms[regScope.currentRoom].addBox("noLegend",parseInt($(this).css('left')), parseInt($(this).css('top')), parseInt($(this).css('width')), parseInt($(this).css('height')), dataCounter, '#cccccc', 'nohover',false, "noStatus", 1);  //add box to room
					regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, false);
				}
			}).on('mousedown', function() {  //down klik on element
				if(regScope.action == 2) {
					var skelStyle = $(this).attr('style');
					var dataCounter = 'b' +  regScope.regBoxCounter;	
					regScope.rooms[regScope.currentRoom].addBox("noLegend",parseInt($(this).css('left')), parseInt($(this).css('top')), parseInt($(this).css('width')), parseInt($(this).css('height')), dataCounter, '#61B329', 'nohover',true,'noStatus',1);  //add box to room
					regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, true);
				}else if(regScope.action == 5) {
					var skelStyle = $(this).attr('style');
					var dataCounter = 'b' +  regScope.regBoxCounter;
					regScope.rooms[regScope.currentRoom].addBox("noLegend",parseInt($(this).css('left')), parseInt($(this).css('top')), parseInt($(this).css('width')), parseInt($(this).css('height')), dataCounter, '#cccccc', 'nohover',false,'noStatus',1);  //add box to room
					regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, false);
				}
			});

			$('.build-area-wrapper').off().on('click', function(e) {
				var $this = $(this);
				var target = $(e.target);

				if(target.hasClass('drag-box') ) {

					return;
				}

				var relX = e.pageX + $this.scrollLeft() - $this.offset().left;  //+ $this.scrollLeft()
				var relY = e.pageY + $this.scrollTop() - $this.offset().top;

				e.stopPropagation();

				if(relX > roomSkeleton.totalWidth || relY > roomSkeleton.totalHeight) {
					var dataCounter = 'b' +  regScope.regBoxCounter;
					var skelStyle = 'width: ' + roomSkeleton.width + 'px; height: ' + roomSkeleton.height + 'px; position: absolute; top: ' + (relY - roomSkeleton.height/2) + 'px; left: ' + (relX - roomSkeleton.width/2) + 'px;';
					
					if(regScope.action == 2) { 
						regScope.rooms[regScope.currentRoom].addBox("noLegend", relX - roomSkeleton.width/2, relY - roomSkeleton.height/2, roomSkeleton.width, roomSkeleton.height, dataCounter, '#61B329', 'nohover',true,'noStatus',1);  //add box to room
						regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, true);
					}else if(regScope.action == 5) {
						regScope.rooms[regScope.currentRoom].addBox("noLegend", relX - roomSkeleton.width/2, relY - roomSkeleton.height/2, roomSkeleton.width, roomSkeleton.height, dataCounter, '#cccccc', 'nohover',false,'noStatus',1);  //add box to room
						regScope.buildBoxOutOfSkeleton(skelStyle, dataCounter, regScope, false);
					}
				}else {
				}
			});

		}else {

		}
	};

	//creates a box out of skeleton box
	Registration.prototype.buildBoxOutOfSkeleton = function(skelStyle, dataCounter, regScope, isRegSpot) {
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
			}
			if(regScope.action == 3 && regScope.permissions.canDeleteBox == true) {
				regScope.activeBoxArray.length = 0;	//make sure activebox in empty.
				regScope.activeBoxArray.push($(this).attr('data-id'));
				regScope.deleteBoxes();
			}			
		}).on('dblclick', function(){
			if(reg.action == 1) {	//dblclick ovly works in action 1				
			}
		}).on('hover', function(e) {
				if(regScope.action == 3 && leftButtonDown == true) {
					if(regScope.permissions.canDeleteBox == true) {
						regScope.activeBoxArray.length = 0;	//make sure activebox in empty.
						regScope.activeBoxArray.push($(this).attr('data-id'));
						regScope.deleteBoxes();
					}else {
						alert(myLanguage.getLang('noPermToDel'));
					}
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

		box.appendTo('.build-area');  //fainally ad box to build-area
		var disableD = true;
		regScope.addDraggableResisableListeners(disableD);
	}

	//this method is used for building already existing rooms. like when you change room. build old ruum
	Registration.prototype.buildBoxes = function() {
		var regScope = this;

		if($('html').hasClass('touch')) {
			regScope.action = 6;

		}else {
			regScope.action = 1;
		}

		var boxCount = this.rooms[this.currentRoom].boxes.length;

		for(var i = 0; i < boxCount; i++) {
			var box = $('<div>').addClass('drag-box ' + this.rooms[this.currentRoom].boxes[i].id).attr({
				'data-id': this.rooms[this.currentRoom].boxes[i].id
			}).css({
				width: this.rooms[this.currentRoom].boxes[i].width + 'px',
				height: this.rooms[this.currentRoom].boxes[i].height + 'px',
				position: 'absolute',
				left: this.rooms[this.currentRoom].boxes[i].xPosition,
				top: this.rooms[this.currentRoom].boxes[i].yPosition,
				'background-color': this.rooms[this.currentRoom].boxes[i].color,
				'z-index': this.rooms[this.currentRoom].boxes[i].zIndex
			}).on('click', function() { //chen you klik box				
				if(regScope.action == 1) {	//is mouse action 1?
					regScope.activeBoxArray.length = 0;  //make sure activebox in empty.
					regScope.activeBoxArray.push($(this).attr('data-id'));	//this box in now active
					$('.active-box').removeClass('active-box');	//remove all previous active
					$(this).addClass('active-box');	//set this box active

					regScope.showClickControls();
				}
				if(regScope.action == 3 && regScope.permissions.canDeleteBox == true) {
					regScope.activeBoxArray.length = 0;	//make sure activebox in empty.
					regScope.rooms[regScope.currentRoom].deleteBox($(this).attr('data-id'));
				}	
			}).on('dblclick', function(){
				if(reg.action == 1) {	//dblclick ovly works in action 1					
				}
			}).on('hover', function(e) {
				if(regScope.action == 3 && leftButtonDown == true) {
					if(regScope.permissions.canDeleteBox == true) {
						regScope.rooms[regScope.currentRoom].deleteBox($(this).attr('data-id'));
					}else {
					
					}	
				}
			}).appendTo('.build-area').each(function(){
				if(regScope.rooms[regScope.currentRoom].boxes[i].canRegister == true) {
					$(this).addClass('can-register').attr('data-seatnr', regScope.rooms[regScope.currentRoom].boxes[i].seat);
					$(this).append($('<div>').addClass('seat-number').text(regScope.rooms[regScope.currentRoom].boxes[i].seat));

					if(regScope.rooms[regScope.currentRoom].boxes[i].status == 'bronRegister') {
						$(this).append($('<div>').addClass('bron-sign'));
					}else if(regScope.rooms[regScope.currentRoom].boxes[i].status == 'takenRegister') {
						$(this).addClass('bron-register').append($('<div>').addClass('taken-sign'));
					}
					if(regScope.rooms[regScope.currentRoom].boxes[i].hoverText != 'nohover') {
						$(this).attr('title', regScope.rooms[regScope.currentRoom].boxes[i].hoverText).addClass('box-hover');
					}
				}else if(regScope.rooms[regScope.currentRoom].boxes[i].canRegister == false) {
					$(this).addClass('no-register');
					if(regScope.rooms[regScope.currentRoom].boxes[i].hoverText != 'nohover') {
						$(this).attr('original-title', regScope.rooms[regScope.currentRoom].boxes[i].hoverText).addClass('box-hover');
					}
				}
			});	//adds to dom
		}

		var disableD = false;
		regScope.addDraggableResisableListeners(disableD);
	};

	Registration.prototype.addDraggableResisableListeners = function(disableDrag) {
		var regScope = this;
		var boxCollection = $('.build-area .drag-box'); 
		var multiDragBoxes = null;

		if(regScope.permissions.canMoveBox) {	//can i drag it around?
			boxCollection.draggable({
				containment: ".build-area",  
				scroll: true, 
				scrollSensitivity: 50,
				scrollSpeed: 30, 
				//snap: true,
				//snapMode: "outer",
				//snapTolerance: 10,
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
						//console.log('Multidrag start');
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
		}

		if(regScope.permissions.canResizeBox) {		//can i resize box?
			var autoHide = true;

			if($('html').hasClass('touch')) {
				autoHide = false;
			}

			boxCollection.resizable({
				autoHide: autoHide,
				handles: "n, e, s, w, ne, se, sw, nw",
				disabled: disableDrag,
				alsoResize: false,
				//containment: ".build-area-wrapper",
				
				start: function(event, ui) {
					
					if(regScope.activeBoxArray.length == 0) {
						$('.build-area .drag-box').resizable("option","alsoResize", false); 
					}
					if(regScope.activeBoxArray.indexOf($(this).data('id')) == -1) {
						regScope.activeBoxArray.push($(this).data('id'));
						$(this).addClass('active-box');
					}
					
					//$('.active-box').removeClass('active-box');	//remove all previous active
					//$(this).addClass('active-box');	//set this box active
				},
				stop: function(event, ui) {
					var aLen = regScope.activeBoxArray.length;

					if(aLen == 1) {
						var location = regScope.rooms[regScope.currentRoom].findBox(regScope.activeBoxArray[0]);
						if(location !== false) {
							regScope.rooms[regScope.currentRoom].boxes[location].changeValues(ui.position.left, ui.position.top, ui.size.width, ui.size.height);
						}
					}else {
						for(var i = 0; i < aLen; i++) {
							var location = regScope.rooms[regScope.currentRoom].findBox(regScope.activeBoxArray[i]);

							if(location !== false) {
								
								var resizedBox = $('.drag-box[data-id='+ regScope.activeBoxArray[i] +']');
								//console.log(parseInt(resizedBox.css('left')), parseInt(resizedBox.css('top')), parseInt(resizedBox.css('width')), parseInt(resizedBox.css('height')));
								regScope.rooms[regScope.currentRoom].boxes[location].changeValues(parseInt(resizedBox.css('left')), parseInt(resizedBox.css('top')), parseInt(resizedBox.css('width')), parseInt(resizedBox.css('height')));
							}
						} 
					}
				} 
			});
		}

		if(regScope.action == 6) {
			//with touch device disable all drag and resize
			boxCollection.draggable("disable").resizable("disable");//disable drag and resize when speed creator tool
		}
	};

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
			//build-area-wrapper
			//check if lasso is initialized and then remove it.
			if($('.build-area-wrapper').hasClass('ui-selectable')) {
				$( ".build-area-wrapper" ).selectableScroll( "destroy" );
			}
			
			//restore draggable and resizable option on drag-box
			if(this.permissions.canMoveBox) {
				$('.build-area-wrapper .drag-box').draggable("option","disabled",false).resizable( "option", "disabled", false );
			}
		}else if(this.action == 2) { 	//speed creator tool selected
			$('.build-area-wrapper').attr('data-cursor','2');
			//check if lasso is initialized and then remove it.
			if($('.build-area-wrapper').hasClass('ui-selectable')) {
				$( ".build-area-wrapper" ).selectableScroll( "destroy" );
			}
			
			if(this.permissions.canMoveBox) {
				//console.log('disabling drag and resize');
				$('.drag-box').draggable("disable").resizable("disable");//disable drag and resize when speed creator tool
			}
			
		}else if(this.action == 3) { //speed delete tool selected
			//check if lasso is initialized and then remove it.
			$('.build-area-wrapper').attr('data-cursor','4');
	
			if($('.build-area-wrapper').hasClass('ui-selectable')) {
				$( ".build-area-wrapper" ).selectableScroll( "destroy" );
			}

			if(this.permissions.canMoveBox) {
				$('.drag-box').draggable("disable").resizable("disable");	//disable drag and resize when speed delete tool
			}
			
		}else if(this.action == 4) {	//lasso tool selected
			$('.build-area-wrapper').attr('data-cursor','5');

			if(this.permissions.canMoveBox) {
				$('.drag-box').draggable("option","disabled",false).resizable("option","disabled",false);	//disable drag and resize when lasso tool .draggable("disable")
			}
			
			//initialize selectable 
			$('.build-area-wrapper').selectableScroll({
		 			selecting: function( event, ui ) {
		 				
		 			},
		 			filter: ".drag-box",
		 			stop: function( event, ui ) {
		 				//alsoResize: ".active-box",
		 				$('.build-area .ui-selected').addClass('active-box').removeClass('ui-selected');

		 				var bIndex = regScope.biggestzIndex();

		 				$('.build-area .active-box').each(function() {
		 					regScope.activeBoxArray.push($(this).attr('data-id'));
		 					$(this).css({'zIndex':bIndex});
		 				});

		 				if($('.build-area .active-box').length > 1) {
		 					$('.build-area .drag-box').resizable( "option", "alsoResize", ".active-box" );
		 					regScope.needMultiDrag = true;
		 				}else {
		 					$('.build_area .drag-box').resizable( "option", "alsoResize", false );
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
		}else if(this.action == 5) {  //normal box creation tool
			$('.build-area-wrapper').attr('data-cursor','3');

			if($('.build-area-wrapper').hasClass('ui-selectable')) {
				$( ".build-area-wrapper" ).selectableScroll( "destroy" );
			}
			if(this.permissions.canMoveBox) {
				$('.drag-box').draggable("disable").resizable("disable");	//disable drag and resize when speed delete tool
			}
		}else if(this.action == 6) {  //in touch devices, move around tool

			if($('.build-area-wrapper').hasClass('ui-selectable')) {
				$( ".build-area-wrapper" ).selectableScroll( "destroy" );
			}

			$('.drag-box').draggable("disable").resizable("disable");
		}
	};

	Registration.prototype.sendValidation = function() {
		//check if all rooms have title
		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		       if(this.rooms[property].title == "") {

		       		alertify.set({ 
						labels: {
					    	ok     : myLanguage.getLang('ok'),
					    	cancel: myLanguage.getLang('cancel')
						},
						buttonFocus: "ok"  
					});
		       		alertify.alert(myLanguage.getLang('allRoomsNeedName'));

		       		return false;
		       }
		    }
		}

		return true;
	};

    //collect data for sending to server
	Registration.prototype.collectData = function() {
		var mySon = [];
		var howManyRooms = Object.size(this.rooms);

		for (var property in this.rooms) {
		    if (this.rooms.hasOwnProperty(property)) {
		        mySon.push(this.rooms[property].returnRoomData());
		    }
		}

		return mySon;
	};

	//get data from server
	Registration.prototype.getData = function() {
		var thisScope = this;

		$.ajax({
			type:'POST',
			url:'php/receiver.php',
			//contentType: "application/json; charset=utf-8",
			data: 'getdata=getData',
			success: function(data) {
				var is_JSON = true;

				try {
					var response = $.parseJSON(data);
				} catch(err) {
					is_JSON = false;
				}
				if(is_JSON) {
					if(response.type == 'ok'){
						thisScope.syncData($.parseJSON(response.data));
					}else if(response.type == 'error') {
						$('#server-response').text(response.text);
					}
				} else {
					alert(data);
				}
			}
		});
	};

	//overrite existing registration data on server
	Registration.prototype.updateData = function() {
		var dataToSend = JSON.stringify(this.collectData());
		var scope = this;
		var regScope = this;
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
				$('#update-data').find('.glyphicon').css({'display':'inline'}).end().find('.fa').css({'display': 'none'}).end().find('.save-text').text(myLanguage.getLang('save'));
				if(data._response.type == 'ok') {
					scope.needToSave = false;

					//set initialName to title
					for (var property in scope.rooms) {
						if (scope.rooms.hasOwnProperty(property)) {
							scope.rooms[property].initialName = scope.rooms[property].title;
						}
					}

					scope.roomNameChange = {};
					alertify.success(myLanguage.getLang('saved'));

					$('#server-response').empty();
				}else {
					$('#server-response').text(data);
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

	//add data from server to registration. adds rooms, legends and boxes...
	Registration.prototype.syncData = function(responseObj) {		
		var isBoxCounterSet = false; 

		if(responseObj == null){
			this.addRoom(false,false,true);
			$('#build-area-loading-wrap').remove();
			$('#room-name-dialog').modal("toggle");
		}else {
			for (var property in responseObj) {
			    if (responseObj.hasOwnProperty(property)) {
			    	if(!isBoxCounterSet){
			    		this.regBoxCounter = responseObj[property]['g'][1];
			    	}
			        
			    	this.addRoom(true,false,false);
			    	this.rooms[this.currentRoom].title = responseObj[property]['room'][1];
			    	this.rooms[this.currentRoom].initialName = responseObj[property]['room'][1];

			    	if(typeof responseObj[property]['room'][7] !== 'undefined' && responseObj[property]['room'][7] !== null) {
			    		this.rooms[this.currentRoom].backgroundImage = responseObj[property]['room'][7];
			    	}
			    		
			    	//update skeleton
			    	var skeleton = responseObj[property]['skeleton'];
			    	this.rooms[this.currentRoom].skeleton.changeSkeleton(skeleton[0], skeleton[1], skeleton[2], skeleton[3], skeleton[4], skeleton[5], skeleton[6]);
			    	var legendsLength = responseObj[property]['room'][3].length;

			    	for(var k = 0; k < legendsLength; k++) {
			    		this.rooms[this.currentRoom].legends.push(new Legend(responseObj[property]['room'][3][k][0], responseObj[property]['room'][3][k][1]));
			    	}

			    	$('#room-selection-wrapper .room-selection[data-room-location="'+ reg.currentRoom +'"]').text(reg.rooms[reg.currentRoom].title);
			    	var arr = responseObj[property]['boxes'];
			    	var arrLength = arr.length;

			    	for(var i = 0; i < arrLength; i++) {  //adding boxes
			    		var canReg = arr[i][8];

			    		if(canReg == 'true') {
			    			canReg = true;
			    		}else if(canReg == 'false'){
			    			canReg = false;
			    		}
			    		this.rooms[this.currentRoom].addBoxS(arr[i][0], arr[i][1], arr[i][2], arr[i][3], arr[i][4], arr[i][7], arr[i][5], arr[i][6].replace(/\^/g,'<br>'), canReg, arr[i][10], arr[i][11]);
			    	}

			    	arrLength = responseObj[property]['l'].length;

			    	for(var r = 0; r < arrLength; r++) { //adding legends
			    		this.syncAllLegends(responseObj[property]['l'][r][0], responseObj[property]['l'][r][1]);
			    	}
			    }
			}
			
			if(window.seatreg.bookings.length > 0) {
				this.syncBoxStatuses(window.seatreg.bookings);
			}
			
			var roomElem = $('#room-selection-wrapper .room-selection').first();
			this.changeRoom(roomElem.attr('data-room-location'), roomElem, true, false);
			this.generatePrevUploadedImgMarkup();
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

		$('.build-area-wrapper .drag-box:not(.active-box)').each(function() {
			var targetIndex = parseInt($(this).css('z-index'));

			if(targetIndex > biggestIndex) {
				biggestIndex = targetIndex;
			}

		});

		return biggestIndex + 1;	
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

	/*

		*----------Init jquery ui and other ----------
	*/
	
	$(".modal").draggable({
	    handle: ".modal-header"
	});
	
	//----Dialogs----

	$('#hover-dialog').on('show.bs.modal', function() {
		$('#box-hover-text').val('');
		$('#hover-dialog').find('.hover-dialog-info').html(reg.activeBoxesInfo('värvida'));
	});

	$('#color-dialog').on('show.bs.modal', function() {
		$('#color-dialog .color-dialog-info').html(reg.activeBoxesInfo('värvida'));
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

    $('#legend-dialog').dialog({
    	autoOpen: false,
    	width: 500,
    	//modal: true,
    	position: {my:"top", at:"bottom", of: $('.room-title')},
    	buttons: [ { text: "Close", click: function() { $( this ).dialog( "close" ); } } ],
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
    $('#picker').colpick({
		flat:true,
		layout:'hex',
		color:'#cccccc',

		onSubmit: function(hsb,hex,rgb,el,bySetColor) {
			reg.changeBoxColor(hex);
			$('#color-dialog').modal('toggle');
			alertify.success(myLanguage.getLang('colorApplied'));	
		}
	});

    //color picker for legends
	$('#picker2').colpick({
		flat:true,
		layout:'hex',
		color:'#61B329',
		submit:false,
		onChange: function(hsb,hex,rgb,el,bySetColor) {
			$('#dummy-legend .legend-box').css("background-color",'#' + hex);
			$('#hiddenColor').val('#' + hex);
		}
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
						$('#new-legend-text-rem').html('<span style="color:red">'+ myLanguage.getLang('legendNameTaken') +'</span>');
					}
				}else {
					$('#new-legend-text-rem').html('<span style="color:red">' + myLanguage.getLang('lagendNameMissing') + '</span>');
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
		var dialogInfoText = reg.activeBoxesInfo('legendi lisada');

		if(reg.allLegends.length == 0) {
			showNotify = true;
			$('.legend-dialog-info').append('<li class="legend-dialog-info-box"><span class="glyphicon glyphicon-exclamation-sign"></span>'+ myLanguage.getLang('noLegendsCreated') +'</li>');
			$('.legend-dialog-commands').slideUp();
		}

		if(dialogInfoText != '') {
			$('.legend-dialog-div:first').css('display','block');
			$('#apply-legend').text('Add legend to ' + reg.activeBoxArray.length + ' boxes');
		}else {
			showNotify = true;
			$('#legend-dialog .legend-dialog-info').prepend('<li class="legend-dialog-info-box"><span class="glyphicon glyphicon-exclamation-sign"></span>'+ myLanguage.getLang('_noSelectBoxToAddLegend') +'</li>');
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
				toggleBtn.addClass('green-toggle').removeClass('red-toggle').text(myLanguage.getLang('createLegend'));
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
						toggleBtn.text(myLanguage.getLang('cancelLegendCreation'));
					}else {
						toggleBtn.removeClass('green-toggle').addClass('red-toggle').text(myLanguage.getLang('cancelLegendCreation'));					
					}

					$('#legend-creator').slideDown(400);
				});

			}else {
				$('#new-legend-text').val('');

				if($('html').hasClass('cssanimations')) {
					toggleBtn.addClass('change-btn-to-red red-toggle').removeClass('change-btn-to-green green-toggle');
					toggleBtn.text(myLanguage.getLang('cancelLegendCreation'));
				}else {
					toggleBtn.addClass('red-toggle').removeClass('green-toggle').text(myLanguage.getLang('cancelLegendCreation'));	
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
						$('#toggle-lcreator').addClass('green-toggle').removeClass('red-toggle').text(myLanguage.getLang('createLegend'));
					}

					$('.legend-dialog-commands').css('display','block');
					$('.legend-dialog-upper').slideDown();
				});
			}
			
		}else {
			$('#new-legend-text').addClass('input-focus').focus();
			$('#new-legend-text-rem').text(myLanguage.getLang('missingName'));
		}
	});

	$('#apply-legend').on('click', function() {
		var selectedLegend = $('#use-select :selected').text();

		if(selectedLegend == '') {
			alertify.alert(myLanguage.getLang('chooseLegend'));
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

	$('#legend-change-wrap-inner .change-btn').on('click', function() {
		var currentSlide = $(this).attr('data-slide');
		var targetSlide = $(this).attr('data-slide-open');

		if(targetSlide == 1) {
			$('#new-legend-name-info').empty();
			$('#new-legend-name, #old-legend-name').val($('#legend-change-wrap-inner .dialog-legend-text-2').text());
			$('#legend-change-wrap-inner').animate({
				marginLeft: "+=500px",
				height: "130px"
			},1000,"easeOutCubic");
		}else if(targetSlide == 3) {
			$('#new-legend-color-info').empty();
			var currentColor = colorToHex($('#legend-change-wrap-inner .legend-box-2').css('background-color'));

			if ( $('#legend-change-color-pic > *').length > 0 ) {				    
				$('#legend-change-color-pic').colpickSetColor(currentColor.replace('#',''),true);
			}else {
				$('#legend-change-color-pic').colpick({
					flat:true,
					layout:'hex',
					submit:false,
					color:currentColor,
					onChange: function(hsb,hex,rgb,el,bySetColor) {
						$('#change-chosen-color').val(hex);	
					}
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
					height: "130px"
				},1000,"easeOutCubic");
			}else {
				$('#legend-change-wrap-inner').animate({
					marginLeft: "+=500px",
					height: "130px"
				},1000,"easeOutCubic");
			}	
		}
	});

	$('#apply-new-legend-name').on('click', function() {
		var reqExprLegendtext = /^[0-9a-zA-ZÜÕÖÄüõöä\s]{1,20}$/;
		var newLegend = $('#new-legend-name').val();
		var oldLegend = $('#old-legend-name').val();

		if(newLegend != '') {
			if(reqExprLegendtext.test(newLegend)) {
				if(!reg.legendNameExists(newLegend)) {
					reg.changeLegendTo(oldLegend, newLegend);
					$('.dialog-legend-text-2').text(newLegend);
					$('#legend-change-wrap-inner').animate({
						marginLeft: "-=500px",
					},1000,"easeOutCubic");
				}else {
					$('#new-legend-name-info').html('<span style="color:red">'+ myLanguage.getLang('legendNameTaken') +'</span>');
				}
			}else {
				$('#new-legend-name-info').html('<span style="color:red">'+ myLanguage.getLang('illegalCharactersDetec') +'</span>');
				$('#new-legend-name').focus();
			}	
		}else {
			$('#new-legend-name-info').html('<span style="color:red">'+ myLanguage.getLang('enterLegendName') +'</span>');
			$('#new-legend-name').focus();
		}
	});

	$('#apply-new-legend-color').on('click', function() {
		var chosenColor = '#' + $('#change-chosen-color').val();
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
			$('#new-legend-color-info').html('<span style="color:red">'+ myLanguage.getLang('legendColorTaken') +'</span>');
		}
	});

	$('#delete-legend-from-room').on('click', function() {
		var selectedLegend = $('#legend-delete-select-room :selected').text();

		if(selectedLegend != '') {
			reg.rooms[reg.currentRoom].removeLegendFromRoom(selectedLegend);
			reg.updateLegendSelect();
		}
	});

	$('#new-legend-text').keyup(function() {
		$('#dummy-legend .dialog-legend-text').text($(this).val());
	});

	//adds new room
	$('#new-room-create').on('click', function() {
            reg.addRoom(false,true,true);
            $('#room-name-dialog').modal("toggle"); 	
	});

	$('#current-room-delete').on('click', function() {
		//do i have bron or reg seats?

		if(!reg.rooms[reg.currentRoom].bronOrRegCheck()) {
			alertify.set({ 
				labels: {
			    	ok     : myLanguage.getLang('ok'),
			    	cancel: myLanguage.getLang('cancel')
				},
				buttonFocus: "cancel"  
			});

			alertify.confirm(myLanguage.getLang('deleteRoom_') + reg.rooms[reg.currentRoom].title + " ?", function (e) {
			    if (e) {
			        reg.deleteCurrentRoom();
			    }
			});

		}else {
			alertify.set({ 
				labels: {
			    	ok     : myLanguage.getLang('ok'),
			    	cancel: myLanguage.getLang('cancel')
				},
				buttonFocus: "ok"  
			});

			alertify.alert(myLanguage.getLang('cantDelRoom_') +'<span class="bold-text">' + reg.rooms[reg.currentRoom].title + '</span>' + myLanguage.getLang('_cantDelRoomBecause'));
		}
	});

	$('#update-data').on('click', function() {
		$(this).prop('disabled', true);
		$('#update-data').find('.glyphicon').css({'display':'none'}).end().find('.fa').css({'display': 'inline'}).end().find('.save-text').text(myLanguage.getLang('saving'));
		
		if(reg.sendValidation()) {
			reg.rooms[reg.currentRoom].correctRoomBoxesIndex();
			reg.roomWidthAndHeight();
			reg.updateData();
		}

		$(this).blur().prop('disabled', false);
	});

	$('#box-hover-submit').on('click', function() {
		reg.checkBubbles();
		reg.changeHelperText();

		if(reg.activeBoxArray.length > 0) {
			var hoverValue = $('#box-hover-text').val().replace(/\n|\r/g, '<br>');; //.replace(/\n|\r/g, '<br>'); //&lt;br/&gt; 

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
			var palleteGuide = '<div><div class="guide-block">'+ myLanguage.getLang('toSelectOneBox_') +'<div class="guide-item guide-item-mouse"></div></div><br><div class="guide-block">'+ myLanguage.getLang('toSelectMultiBox_') +'<div class="guide-item guide-item-lasso"></div></div>';
			alertify.set({ 
				labels: {
			    	ok     : myLanguage.getLang('ok'),
			    	cancel: myLanguage.getLang('cancel')
				},
				buttonFocus: "ok"  
			});
		}
	});

	//selects room
	$('#print-active-boxes').on('click', function() {
	});

	$('.mouse-action-boxes .mouse-option').on('click', function(){   //mouse action menu
		//get button action code
		reg.mouseActionChange($(this));	//changes mouse action
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
		
		//adds skeleton grid info to reg object. 
		//reg.addSkeletons(SkeletonStyles.sizeX, SkeletonStyles.sizeY, SkeletonStyles.countX, SkeletonStyles.countY, SkeletonStyles.marginX, SkeletonStyles.marginY);
		//build skeleton
		reg.buildSkeleton();
		reg.needToSave = true;
		alertify.success(myLanguage.getLang('buildingGridUpdated'));
		//buildSkeleton(50,50,5,5,4,4);
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
			$('.room-name-error').text(myLanguage.getLang('roomNameMissing')).css('display','block');
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

					alertify.success(myLanguage.getLang('roomNameChanged'));
				}else {
					if(reg.rooms[reg.currentRoom].initialName == "") {
						reg.rooms[reg.currentRoom].initialName = reg.rooms[reg.currentRoom].title;
					}
					alertify.success(myLanguage.getLang('roomNameSet'));
				}
				
				$('#room-name-dialog').modal('toggle');
				reg.needToSave = true;
				
			}else {
				$('.room-name-error').text(myLanguage.getLang('roomNameExists')).css('display','block');
			}
		}
	});

	$('.build-area-wrapper .box-hover').tipsy({
		title: 'original-title',
		gravity: 'n',
		live: true,
		html: true
		
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
			    	ok     : myLanguage.getLang('ok'),
			    	cancel: myLanguage.getLang('cancel')
				},
				buttonFocus: "cancel"  
			});

    		alertify.confirm(myLanguage.getLang('unsavedChanges'),function(e) {
    			if (e) {
    				window.open(location,"_self");
				} 
    		});
    	}
    });

    $('.click-control-right .glyphicon').on('click', function() {
    	var destination = $(this).data('destination');
    	var destinationAm = Math.abs($('#click-control-move-nr').val());

    	reg.prepareMoveActiveBoxes(destination, destinationAm);
	});

	$('.progress').css({'display': 'none'});

	var imageSubmitOptions = {    
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

				 var imgRem = $(' <span class="up-img-rem" data-img="'+ respObjekt.data +'"></span>').append('<span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Eemalda');
				 var addImg = $(' <span class="add-img-room" data-img="'+ respObjekt.data +'" data-size="'+ respObjekt.extraData +'"></span>').append('<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Lisa ruumi');
				 var upImgBox = $('<div class="uploaded-image-box"></div>').append('<img src="../wp-content/plugins/seatreg_wordpress/uploads/room_images/' + seatreg.selectedRegistration + '/' + respObjekt.data + '" class="uploaded-image" /> ', addImg, imgRem);

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
					console.log('Image deleted');
					reg.removeImgAllRooms(imgName);
					reg.removeCurrentRoomImage();
					$('#activ-room-img-wrap').empty().text('Ruumis pole hetkel tagatausta');
					thisLoc.closest('.uploaded-image-box').remove();
				}else if(response.type == 'error') {
					console.log(response.text);
				}
			}
		});
	});

	$('#uploaded-images').on('click', '.add-img-room', function() {	
		reg.setRoomImage($(this).data('img'), $(this).data('size'));

		var curImgWrap = $('<div class="cur-img-wrap"></div>');
		var bgImg = $('<img class="uploaded-image" src="../wp-content/plugins/seatreg_wordpress/uploads/room_images/' + seatreg.selectedRegistration + '/' + reg.rooms[reg.currentRoom].backgroundImage + '" />');
		var remImg = $('<span id="rem-room-img"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Eemalda ruumist</span>');

		curImgWrap.append(bgImg, remImg);

		$('#activ-room-img-wrap').empty().append(curImgWrap);
	});

	$('#background-image-modal').on('show.bs.modal', function() {
		$('#activ-room-img-wrap').empty();

		if(reg.rooms[reg.currentRoom].backgroundImage !== null) {
			var curImgWrap = $('<div class="cur-img-wrap"></div>');
			var bgImg = $('<img class="uploaded-image" src="../wp-content/plugins/seatreg_wordpress/uploads/room_images/' + seatreg.selectedRegistration + '/' + reg.rooms[reg.currentRoom].backgroundImage + '" />');
			var remImg = $('<span id="rem-room-img"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Eemalda ruumist</span>');

			curImgWrap.append(bgImg, remImg);
			$('#activ-room-img-wrap').append(curImgWrap);
		}else {
			$('#activ-room-img-wrap').html('Ruumis pole hetkel tagatausta');
		}
	});

	$('#activ-room-img-wrap').on('click', '#rem-room-img', function() {
		reg.removeCurrentRoomImage();
		$('.room-image').remove();
		$(this).closest('.cur-img-wrap').remove();
		$('#activ-room-img-wrap').html('Ruumis pole hetkel tagatausta pilti');
	});
	
	$('#file-sub').on('click', function(e) {
		var picName = $('#img-upload').val().split(/(\\|\/)/g).pop();
		var re = /^[0-9a-zA-ZÜÕÖÄüõöä\-._]{1,90}$/;
		$('#urlCode').val(seatreg.selectedRegistration);

		if(picName == '') {
			e.preventDefault();
			$('#img-upload-resp').html('<div class="alert alert-danger" role="alert">Vali pilt, mida ülesse laadida</div>');
		}else {
			if(!re.test(picName)) {
				e.preventDefault();
				$('#img-upload-resp').html('<div class="alert alert-danger" role="alert">Pildi nimes on keelatud sümbolid! Palun muuda nime</div>');
			}
		}
	});

})(Jquery_1_8_3);