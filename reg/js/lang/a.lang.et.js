/*
|--------------------------------------------------------------------------
| Estonian language for a.js
|--------------------------------------------------------------------------
|myLanguage.getLang('translate')
*/

function myLanguage() {
	this.language = 'et';


	//errors
	this.wrongCaptcha = 'Vale kood!';
	this.dateChangeError = 'Tekkis viga kuupäevade vahetamisega';
	this.infoTextError = 'Tekkis viga teksti muutmisel';
	this.paymentTextUpError = 'Tekkis viga maksejuhendi uuendamisega';
	this.notCorrectNumb = 'Vigane number!';
	this.regPassUpError = 'Parooli vahetus nurjus. Palun proovi uuesti'; 
	this.errorUpdate = 'Uuendamisel tekkis viga. Palun proovi uuesti';

	//info
	this.datesChanged = 'Kuupäevad muudetud';
	this.infoTextChanged = 'Tekst muudetud';
	this.paymentTextUpdated = 'Maksejuhend uuendatud';
	this.maxSeatsChanged = 'Salvestatud';
	this.gmailOpChange = 'Salvestatud';
	this.customFieldsUpdated = 'Isekoostatud küsimused uuendatud';
	this.copyToSite = 'Kopeeri järgnev kood enda veebilehele';
	this.noChanges = 'Pole midagi uuendada';
	this.regNameUpdates = 'Registratsiooni nimi uuendatud';

	//actions
	this.pleaseEnterName = 'Palun sisesta nimi';
	this.enterCode = 'Sisesta kood:';
	this.actionNotChosen = 'Tegevus valimata!';
	this.openPDF = 'Ava PDF';
	this.downloadTextFile = 'Lae alla tekstifail';
	this.downloadxlxs = 'Lae alla xlsx fail';

	//questions
	this.areSureToDel = 'Oled kindel, et soovid kustutada?';

	//words
	this.ok = 'Ok';
	this.cancel = 'Tühista';
	this.del = 'Kustuta';
	this.updated = 'Uuendatud';
	this.pdfOptions = 'PDF sätted';
	this.yourTimezone_ = 'Sinu ajatsoon: ';
	this.showPending = 'Kuva mittekinnitatuid kohti';
	this.showConfirmed = 'Kuva kinnitatuid kohti';
	this.textOptions = 'Tekstifaili seaded';
	this.xlsxOptions = 'XLSX sätted';
	this.learnMore = 'Loe veel';

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