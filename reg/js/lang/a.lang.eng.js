/*
|--------------------------------------------------------------------------
| English language for a.js
|--------------------------------------------------------------------------
|myLanguage.getLang('translate')
*/

function myLanguage() {
	this.language = 'eng';


	//errors
	this.wrongCaptcha = 'Wrong CAPTCHA code!';
	this.dateChangeError = 'Error changing dates';
	this.infoTextError = 'Error changing info text';
	this.paymentTextUpError = 'Error changing payment text';
	this.notCorrectNumb = 'Not correct number!';
	this.regPassUpError = 'Error updating registration password. Please try again.';
	this.errorUpdate = 'Error while updating. Please try again.';

	//info
	this.datesChanged = 'Dates changed';
	this.infoTextChanged = 'Info text changed';
	this.paymentTextUpdated = 'Payment text changed';
	this.maxSeatsChanged = 'Max seats changed';
	this.gmailOpChange = 'Gmail option changed';
	this.customFieldsUpdated = 'Custom fields updated';
	this.copyToSite = 'Copy following code to your site';
	this.noChanges = 'No changes detected';
	this.regNameUpdates = 'Registration name updated';

	//actions
	this.pleaseEnterName = 'Please enter name';
	this.enterCode = 'Enter code:';
	this.actionNotChosen = 'Action not chosen!';
	this.openPDF = 'Open PDF';
	this.downloadTextFile = 'Download text file';
	this.downloadxlxs = 'Download xlsx file';

	//questions
	this.areSureToDel = 'Are you sure you want to delete?';


	//words 
	this.ok = 'Ok';
	this.cancel = 'Cancel';
	this.del = 'Delete';
	this.updated = 'Updated';
	this.pdfOptions = 'PDF options';
	this.yourTimezone_ = 'Your timezone: ';
	this.showPending = 'Show pending';
	this.showConfirmed = 'Show confirmed';
	this.textOptions = 'Text options';
	this.xlsxOptions = 'XLSX options';
	this.learnMore = 'Learn more';



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