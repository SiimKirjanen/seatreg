/*
|--------------------------------------------------------------------------
| English language ofr builder.js
|--------------------------------------------------------------------------
|
sofar: 919
*/

function myLanguage() {
	this.language = 'et';


	//success
	this.hoverDeleteSuccess = 'Hõljumiskiri kustutatud';
	this.hoverTextAdded = 'Hõljumiskiri lisatud';
	this.legendNameChanged = 'Legendi nimi vahetatud';
	this.legendColorChanged = 'Legendi värv vahetatud';
	this.buildingGridUpdated = 'Ehitusvõre uuendatud';
	this.roomNameChanged = 'Ruumi nimi vahetatud';
	this.roomNameSet = 'Ruumi nimi paigaldatud';
	//errors
	this.hoverError = 'Viga hõljumisteksti tegemisel';
	this.legendChangeError = 'Viga legendi muutmisel';

	//alerts
	this.legendNameTaken = 'Legendi nimi on võetud';
	this.lagendNameMissing = 'Legendi nimi puudu!';
	this.legendColorTaken = 'Värv juba kasutusel. Vali muu';
	this.legendAddedTo_ = 'Legend lisatud ';
	this.noPermToAddRoom = 'Pole õigusi uue ruumi lisamiseks';
	this.noPermToDel = 'Pole õigusi kustutamiseks';
	this.oneRoomNeeded = 'Vähemalt üks ruum peab olema';
	this.alreadyInRoom = 'Oled juba valitud ruumis';
	this.allRoomsNeedName = 'Igal ruumil peab olema nimi';
	this.illegalCharactersDetec = 'Keelatud tähemärk leitud';
	this.missingName = 'Nimi puudu';
	this.cantDelRoom_ = 'Sa ei saa kustutada ruumi ';
	this._cantDelRoomBecause = ' kuna see sisaldab broneeritud kohti. Kasuta haldust nende eemaldamiseks.';
	this.roomNameMissing = 'Ruumi nimi puudu';
	this.roomNameExists = 'Ruumi nimi juba kasutuses. Palun vali midagi muud';

	//info
	this.liYouHaveSelectedSpan_ = '<li>Sa oled selekteerinud <span> ';
	this._boxesSpanLi = ' kohta/objekti</span></li>';
	this.toSelectOneBox_ = 'Ühe kasti valimiseks kasuta ';
	this.toSelectMultiBox_ = 'Ühe või mitme kasti valimiseks kasuta ';
	this.selectBoxesToAddHover = 'Selekteeri kohad/objektid millel hõljumisteksti lisada';
	this.loading = 'Laen...';
	this.selectBoxesToDelete = 'Selekteeri koht/objekt mida soovid eemaldada';
	this.onlyPremMembUpImg = 'Ainult premium kasutajad saavad lisada tagataustapilti';
	this.fixNeededToSave = 'Salvestamine keelatud';
	this.roomLimitExceeded = 'Ruumide limiit täis';
	this.freeAccountRoomLimit = 'Tasuta kontoga on võimalik luua kuni 3 ruumi';
	this.boxLimitExceeded = 'Kastide limiit täis';
	this.freeAccountBoxLimit = 'Tasuta kontoga on võimalik luua 100 kastiga igasse ruumi';
	this.colorApplied = 'Värvus kohaldatud';
	this.noLegendsCreated = 'Ühtegi legendi pole veel loodud';
	this._noSelectBoxToAddLegend = ' Sa pole selekteerinud ühtegi kohta/objekti, millele legend lisada';
	this._charRemaining = ' tähemärki jäänud';

	//questions
	this.deleteRoom_ = 'Oled kindel, et soovid ruumi eemaldada? ';
	this.unsavedChanges = 'Salvestamata uuendused. Oled kindel, et soovid lahkuda?';

	//actions
	this.createLegend = 'Loo uus legend';
	this.cancelLegendCreation = 'Katkesta legendi loomine';
	this.chooseLegend = 'Vali legend';
	this.enterLegendName = 'Sisesta legendi nimi';

	//words
	this.ok = 'Ok';
	this.cancel = 'Tühista';
	this._boxes = ' kasti</li>';
	this.pendingSeat = 'Kinnitamata koht';
	this.confirmedSeat = 'Kinnitatud koht';
	this.save = 'Salvesta';
	this.saving = 'Salvestan...';
	this.saved = 'Salvestatud';

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