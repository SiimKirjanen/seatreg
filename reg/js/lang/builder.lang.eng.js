/*
|--------------------------------------------------------------------------
| English language for builder.js
|--------------------------------------------------------------------------
|myLanguage.getLang('hoverTextAdded')
 myLanguage.getLang('ok')
 myLanguage.getLang('cancel')
 myLanguage.getLang('save')
sofar: 919
*/

function myLanguage() {
	this.language = 'eng';


	//success
	this.hoverDeleteSuccess = 'Hover text deleted';
	this.hoverTextAdded = 'Hover text added';
	this.legendNameChanged = 'Legend name changed';
	this.legendColorChanged = 'Legend color changed';
	this.buildingGridUpdated = 'Building grid updated';
	this.roomNameChanged = 'Room name changed';
	this.roomNameSet = 'Room name set';
	//errors
	this.hoverError = 'Error while creating hover';
	this.legendChangeError = 'Error while changing legend';

	//alerts
	this.legendNameTaken = 'Legend name is taken';
	this.lagendNameMissing = 'Legend name missing!';
	this.legendColorTaken = 'Legend color is taken. Choose another';
	this.legendAddedTo_ = 'Legend added to ';
	this.noPermToAddRoom = 'Dont have permissions to create room';
	this.noPermToDel = 'Dont have permission do delete';
	this.oneRoomNeeded = 'You must have at least on room';
	this.alreadyInRoom = 'Already in this room';
	this.allRoomsNeedName = 'All rooms must have name';
	this.illegalCharactersDetec = 'Illegal characters detected';
	this.missingName = 'Name missing';
	this.cantDelRoom_ = 'You cant delete room ';
	this._cantDelRoomBecause = ' because it contains pending or confirmed seats. You must remove them with manager first.';
	this.roomNameMissing = 'Room name missing';
	this.roomNameExists = 'Room name already exists. You must choose another';

	//info
	this.liYouHaveSelectedSpan_ = '<li>You have selected <span> ';
	this._boxesSpanLi = ' box/boxes</span></li>';
	this.toSelectOneBox_ = 'To select one box use ';
	this.toSelectMultiBox_ = 'To select multiply boxes use ';
	this.selectBoxesToAddHover = 'Select box/boxes to add hover text';
	this.loading = 'Loading...';
	this.selectBoxesToDelete = 'Select box/boxes you want to delete';
	this.onlyPremMembUpImg = 'Only premium members can upload background-image';
	this.fixNeededToSave = 'Fix needed to save!';
	this.roomLimitExceeded = 'Room limit exceeded';
	this.freeAccountRoomLimit = 'Free accounts can have 3 rooms max.';
	this.boxLimitExceeded = 'Box limit exeeded';
	this.freeAccountBoxLimit = 'Free accounts can have max 100 boxes in each room';
	this.colorApplied = 'Color applied';
	this.noLegendsCreated = 'You have not made and legends yet';
	this._noSelectBoxToAddLegend = ' You have not selected any box/boxes to add legends';
	this._charRemaining = ' characters remaining';

	//questions
	this.deleteRoom_ = 'Are you sure you want to delete room ';
	this.unsavedChanges = 'Unsaved changes. You sure you want to leave?';

	//actions
	this.createLegend = 'Create new legend';
	this.cancelLegendCreation = 'Cancel legend creation';
	this.chooseLegend = 'Choose legend';
	this.enterLegendName = 'Enter legend name';


	//words 
	this.ok = 'Ok';
	this.cancel = 'Cancel';
	this._boxes = ' boxes';
	this.pendingSeat = 'Pending seat';
	this.confirmedSeat = 'Confirmed seat';
	this.save = 'Save';
	this.saving = 'Saving...';
	this.saved = 'Saved';

	//configuration

	this.bgImgDir = 'uploads/room_images/';


}
myLanguage.prototype.getLang = function(target) {

	if(target in this) {
		//target found

		return this[target];

	}else {
		//target not found
		return '<span style="color:red">Missing translation</span>';
	}

};


var myLanguage = new myLanguage();