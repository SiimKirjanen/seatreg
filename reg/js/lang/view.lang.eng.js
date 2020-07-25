/*
|--------------------------------------------------------------------------
| English language for view.js
|--------------------------------------------------------------------------
|myLanguage.getLang('translate')
*/

function myLanguage() {
	this.language = 'eng';

	//errors
	this.illegalCharactersDetec = 'Illegal characters detected';
	this.emailNotCorrect = 'Email address is not correct';
	this.wrongCaptcha = 'Wrong code!';
	this.somethingWentWrong = 'Something went wrong. Please try again';

	//info
	this.selectionIsEmpty = 'Selection is empty';
	this.youCanAdd_ = 'You can add ';
	this._toCartClickTab = ' to selection by clicking/tabbing them';
	this.regClosedAtMoment = 'Registration is closed at the moment';
	this.confWillBeSentTo = 'Confirmation will be sent to:';
	this.confWillBeSentTogmail = 'Confirmation will be sent to (Gmail):'
	this.gmailReq = 'Email (Gmail required)';
	this._fromRoom_ = ' from room ';
	this._toSelection = ' to selection?';
	this._isOccupied = ' is occupied';
	this._isPendingState = ' is in pending state';
	this.regOwnerNotConfirmed = '(registration owner has not confirmed it yet)';
	this.selectionIsFull = 'Selection is full';
	this._isAlreadyInCart = ' is already in cart!';
	this._regUnderConstruction = ' Registration under construction';
	this.emptyField = 'Empty field';

	//actions
	this.remove = 'Remove';
	this.add_ = 'Add ';


	//words
	this.openSeatsInRoom_ = 'Open seats in room: ';
	this.pendingSeatInRoom_ = 'Pending seat in room: ';
	this.confirmedSeatInRoom_ = 'Confirmed seat in room: ';
	this.seat = 'seat';
	this.firstName = 'Firstname';
	this.lastName = 'Lastname';
	this.eMail = 'Email';
	this.this_ = 'This ';
	this._selected = ' selected';
	
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