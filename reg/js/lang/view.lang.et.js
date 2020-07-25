/*
|--------------------------------------------------------------------------
| Estonian language for view.js
|--------------------------------------------------------------------------
|myLanguage.getLang('translate')
*/

function myLanguage() {
	this.language = 'et';

	//errors
	this.illegalCharactersDetec = 'Keelatud tähemärk leitud';
	this.emailNotCorrect = 'E-mail address vigane';
	this.wrongCaptcha = 'Kood vale';
	this.somethingWentWrong = 'Midagi läks valesti. Palun proovi uuesti';

	//info
	this.selectionIsEmpty = 'Pole midagi valitud';
	this.youCanAdd_ = 'Broneerimiseks vali endale ';
	this._toCartClickTab = '';
	this.regClosedAtMoment = 'Registratsioon on hetkel suletud';
	this.confWillBeSentTo = 'Kinnituskiri saadetakse:';
	this.confWillBeSentTogmail = 'Kinnituskiri saadetakse(Gmail):';
	this.gmailReq = 'E-mail (Gmail nõutud)';
	this._fromRoom_ = ' ruumist ';
	this._toSelection = ' valikusse?';
	this._isOccupied = ' on juba võetud';
	this._isPendingState = ' on kinnitamata';
	this.regOwnerNotConfirmed = '(registratsiooni omanik pole veel kinnitanud)';
	this.selectionIsFull = 'Rohkem ei saa korraga valida';
	this._isAlreadyInCart = ' on juba valikus';
	this._regUnderConstruction = ' Registratsioon on loomisel...';
	this.emptyField = 'Tühi lahter';

	//actions
	this.remove = 'Eemalda';
	this.add_ = 'Lisa ';

	//words
	this.openSeatsInRoom_ = 'Avatud kohti ruumis: ';
	this.pendingSeatInRoom_ = 'Kinnitamata kohti ruumis: ';
	this.confirmedSeatInRoom_ = 'Kinnitatud kohti ruumis: ';
	this.seat = 'koht';
	this.firstName = 'Eesnimi';
	this.lastName = 'Perekonnanimi';
	this.eMail = 'E-mail';
	this.this_ = 'See ';
	this._selected = ' valitud';


	//configuration
	this.bgImgDir = '../uploads/room_images/';

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