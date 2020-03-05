// Global variables

var headAttributes;
var original_content;
var nonEditId;
var prevIdPrefix = 'row-';
var prevId;
var isFirst = true;
var parseFullString = [];
var editBoxId;
var editBoxIsFirst = true;
var editBoxOriginalContent;
var dskfvmsdl = 0;
var secondaryLanguageDropInitIndex;
var additionalLanguageDropInitIndex;
if(document.getElementById("secondaryLanguageDrop")) {
	secondaryLanguageDropInitIndex = document.getElementById("secondaryLanguageDrop").selectedIndex;
}
if(document.getElementById("additionalLanguageDrop")) {
	additionalLanguageDropInitIndex = document.getElementById("additionalLanguageDrop").selectedIndex;
}
var addLangInitValue = "";
var secLangInitValue = "";
var additionalLangAllowed;
var additionalLangInitValueCheck = true;
var additionalLangInitValue;
var additionalLangCurrValue;
var secondaryLangInitValueCheck = true;
var secondaryLangInitValue;
var secondaryLangCurrValue;
var languagesToChange;
var settingsLangOrigin;
var settingsLangMod;
/*
 * Method for quick editing the table row content on Database Managment page (on development)
 * 
 *  @param ids, word_code  - the id of the currently editing row and the word code
 * 
 */
function quickEditContent(ids, word_code) {
    let adminAjaxLoad = "<?php echo admin_url('admin-ajax.php'); ?>";

    let content = '<td><form id="theForm" action="#" method="post" data-url="' + adminAjaxLoad + '">'
            + 'The data.......................................................'
            + '<input class="button-secondary" type="button" id="' + ids + '" value="Cancel" onclick="cancelMods(this.id)" />'
            + '   <input class="button-primary" type="submit" id="' + ids + '" value="Update" onclick="applyMods(this.id)" name="updateTheWord"/>'
            + '</form></td>';

    return content;
}
/*
 * Method for editing the table row content
 * 
 *  @param ids  - the id of the currently editing row
 * 
 */
function editWordRow(ids) {

    nonEditId = "row-" + ids;

    if (ids != prevId) {
        if (isFirst) {

            isFirst = false;
            original_content = document.getElementById(nonEditId).innerHTML;
            var word_code = document.getElementById("row-" + ids + "-contains-code").innerHTML;
            prevId = ids;
            document.getElementById(nonEditId).innerHTML =
                    quickEditContent(ids, word_code);
        } else if (isFirst == false) {

            var prevIdFull = prevIdPrefix + prevId;
            document.getElementById(prevIdFull).innerHTML = original_content;
            original_content = document.getElementById(nonEditId).innerHTML;

            var word_code = document.getElementById("row-" + ids + "-contains-code").innerHTML;
            prevId = ids;
            document.getElementById(nonEditId).innerHTML =
                    quickEditContent(ids, word_code);
        }
    } else if (ids == prevId) {
        original_content = document.getElementById(nonEditId).innerHTML;
        var word_code = document.getElementById("row-" + ids + "-contains-code").innerHTML;
        prevId = ids;
        document.getElementById(nonEditId).innerHTML =
                quickEditContent(ids, word_code);
    }
}
/*
 * Method for canceling content modifications
 * 
 *  @param theId  - the id of the currently editing row
 * 
 */
function cancelMods(theId) {

    if (theId.includes("cancelEditWord")) {
        var plainId = "wordEditBox" + theId.split("cancelEditWord").pop();
        document.getElementById(plainId).innerHTML = editBoxOriginalContent;
    } else if (theId === "cancelLanguageUpdate") {
        settingsLangMod = document.getElementById("showOptions").innerHTML;
        
        if (settingsLangMod !== settingsLangOrigin) {
            document.getElementById("showOptions").innerHTML = settingsLangOrigin;
        }
		document.getElementById("secondaryLanguageDrop").selectedIndex = secondaryLanguageDropInitIndex;
		document.getElementById("additionalLanguageDrop").selectedIndex = additionalLanguageDropInitIndex;
		document.getElementById("languageChangeMessages").style.display = "none";
		additionalLangCurrValue = null;
        secondaryLangInitValue = secLangInitValue;
        secondaryLangCurrValue = null;
        additionalLangInitValue = addLangInitValue;
    } else {
        document.getElementById(nonEditId).innerHTML = original_content;
    }
}
/*
 * Method for adding new words (on development)
 * 
 */
function addWord() {
    document.getElementById("lexicon-table-content").style.display = "none";
    document.getElementById("lexicon-add-word").style.display = "block";

}
/*
 * Method for exiting the Add Word section
 * 
 */
function backToLexicon() {
    document.getElementById("lexicon-table-content").style.display = "block";
    document.getElementById("lexicon-add-word").style.display = "none";

}
/*
 * Method for exporting data to a csv file
 * 
 *  @param iterNum  - number of the columns in the file to export
 * 
 */
function export_data_to_CSV(iterNum) {

    var fullString = document.getElementById("data_to_export").innerHTML; // Get the content that we will fill the csv file

    for (var i = 0; i <= iterNum - 1; i++) {    // Store the content in parseFullString array
        var partString = fullString.split("<br/>", 1).toString();
        var partStringLength = partString.length;
        var fullStringTemp = fullString.slice(partStringLength);
        fullString = fullStringTemp;
        parseFullString[i] = partString;
    }
    var finalText = parseFullString.toString();     // Convert array to string

    var searchNum = finalText.search("<br>");       // Search for <br> in the string

    while (searchNum != -1) {                       // While there are <br> in the text replace them with new line
        var final = finalText.replace("<br>", "\n");
        finalText = final;
        searchNum = finalText.search("<br>");
    }

    var filename = "mydata.csv";                // File name
    var csvFile = finalText.slice(0, -iterNum + 1);
    var blob = new Blob([csvFile], {type: 'text/csv;charset=utf-8;'});
    if (navigator.msSaveBlob) { // IE 10+
        navigator.msSaveBlob(blob, filename);
    } else {
        var link = document.createElement("a");
        if (link.download !== undefined) { // feature detection
            // Browsers that support HTML5 download attribute
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}
/*
 * Method for showing more/less languages options on dropdowns
 * 
 *  @param response  - defines which options are shown
 * 
 */
function showMoreLanguages(response) {
    var theLessLangData = document.getElementById("lessOpt").value;
    var theMoreLangData = document.getElementById("moreOpt").value;

    if (response === true) {
        document.getElementById("theLanguagesOptions").innerHTML = theMoreLangData;
        document.getElementById("showLanguagesLink").innerHTML = '<a href="#" onclick="showMoreLanguages(false)">Need less languages?</a>';
    } else if (response === false) {
        document.getElementById("theLanguagesOptions").innerHTML = theLessLangData;
        document.getElementById("showLanguagesLink").innerHTML = '<a href="#" onclick="showMoreLanguages(true)">Need more languages?</a>';
    }

}
/*
 * Method for defining what is importing
 * 
 *  @param selectedImpOpt  -  option that is selected
 *  Description: Hides/Shows different sections for better user interaction
 */
function showSelectedImpOpt(selectedImpOpt) {
    if (selectedImpOpt == "language") {

        jQuery("#impOptsSelLang").removeClass("impOptsUnSel");
        jQuery("#impOptsSelCourse").removeClass("impOptsSel");
        jQuery("#impOptsSelCat").removeClass("impOptsSel");
        jQuery("#impOptsSelLang").addClass("impOptsSel");
        jQuery("#impOptsSelCourse").addClass("impOptsUnSel");
        jQuery("#impOptsSelCat").addClass("impOptsUnSel");

        document.getElementById("submit").name = "submit_form_lang";
    } else if (selectedImpOpt == "course") {

        jQuery("#impOptsSelLang").removeClass("impOptsSel");
        jQuery("#impOptsSelCourse").removeClass("impOptsUnSel");
        jQuery("#impOptsSelCat").removeClass("impOptsSel");
        jQuery("#impOptsSelLang").addClass("impOptsUnSel");
        jQuery("#impOptsSelCourse").addClass("impOptsSel");
        jQuery("#impOptsSelCat").addClass("impOptsUnSel");

        document.getElementById("submit").name = "submit_form_course";
    } else if (selectedImpOpt == "categories") {

        jQuery("#impOptsSelLang").removeClass("impOptsSel");
        jQuery("#impOptsSelCourse").removeClass("impOptsSel");
        jQuery("#impOptsSelCat").removeClass("impOptsUnSel");
        jQuery("#impOptsSelLang").addClass("impOptsUnSel");
        jQuery("#impOptsSelCourse").addClass("impOptsUnSel");
        jQuery("#impOptsSelCat").addClass("impOptsSel");

        document.getElementById("submit").name = "submit_form_cat";
    }
}
/*
 * Method for defining dropdown before and after dropdown choices
 * 
 */
jQuery("#secondaryLanguageDrop").focus(function () {
    // Store the current value on focus and on change
    secondaryLangInitValue = this.value;
    secondaryLangInitValueCheck = false;
}).change(function () {
    // Do something with the previous value after the change
    secondaryLangCurrValue = this.value;
});
/*
 * Method for defining before and after dropdown choices
 * 
 */
jQuery("#additionalLanguageDrop").focus(function () {
    // Store the current value on focus and on change
    additionalLangInitValue = this.value;
    additionalLangInitValueCheck = false;
}).change(function () {
    // Do something with the previous value after the change
    additionalLangCurrValue = this.value;
    if (additionalLangInitValue !== additionalLangCurrValue) {
    }

});
/*
 * Method for setting Secondary and additional Languages in Settings Page
 * 
 */
function settingsPageChangeLang() {
    document.getElementById("languageChangeMessages").style.display = "none";
    var standardTextPt1 = "Are you sure you want to change your ";
    var standardTextPt2 = "";
    var standardTextPt3 = '<br/><br/>'
            + '<div style="display: flex;">'
            + '<input id="cancelLanguageUpdate" type="button" class="button-secondary" value="Cancel" onclick="cancelMods(this.id)"/>'
            + '<span style="width: 5px"></span>'
            + '<input type="submit" onclick="applyLoader()" value="Accept" name="submitLang" class="button-primary" />'
            + '<span style="width: 10px"></span>'
            + '<div id="updateLoader" class="loader"></div>'
            + '</div>';
    //Add text according to which options are changed
    if (((secLangInitValue !== secondaryLangCurrValue) || !additionalLangAllowed) && (typeof secondaryLangCurrValue !== 'undefined' && secondaryLangCurrValue !== null)) {
        if (secLangInitValue !== secondaryLangCurrValue && (typeof secondaryLangCurrValue !== 'undefined' && secondaryLangCurrValue !== null)) {
            standardTextPt2 += standardTextPt1 + "Secondary Language from " + secLangInitValue.substring(3) + " to " + secondaryLangCurrValue.substring(3);
            if ((addLangInitValue !== additionalLangCurrValue) && additionalLangAllowed && typeof additionalLangCurrValue !== 'undefined') {
                standardTextPt2 += " and your Additional Language from " + addLangInitValue.substring(3) + " to " + additionalLangCurrValue.substring(3) + "?";
            } else if (!additionalLangAllowed) {
                standardTextPt2 += " and proceed with no Additional Language?";
            } else {
                standardTextPt2 += "?";
            }
        } else if (!additionalLangAllowed) {
            standardTextPt2 = "Are you sure you want to proceed with no Additional Language?";
        }
        document.getElementById("languageChangeMessages").innerHTML = standardTextPt2 + standardTextPt3;
        document.getElementById("languageChangeMessages").style.display = "block";
    } else if ((addLangInitValue !== additionalLangCurrValue) && (typeof additionalLangCurrValue !== 'undefined' && additionalLangCurrValue !== null)) {
		if (typeof addLangInitValue === 'undefined') {
			addLangInitValue = "nonNone";
		}
        standardTextPt2 += standardTextPt1 + " Additional Language from " + addLangInitValue.substring(3) + " to " + additionalLangCurrValue.substring(3);
        standardTextPt2 += "?";
        document.getElementById("languageChangeMessages").innerHTML = standardTextPt2 + standardTextPt3;
        document.getElementById("languageChangeMessages").style.display = "block";
    } else {
		
	}
}
/*
 * Method for applying loader, a small circle showing that something is loading
 * 
 */
function applyLoader() {
    document.getElementById("updateLoader").style.border = "6px solid #f3f3f3";
    document.getElementById("updateLoader").style.borderRadius = "50%";
    document.getElementById("updateLoader").style.borderTop = "6px solid #3498db";
    document.getElementById("updateLoader").style.width = "20px";
    document.getElementById("updateLoader").style.height = "20px";
    document.getElementById("updateLoader").style.WebkitAnimation = "spin 2s linear infinite";
    document.getElementById("updateLoader").style.animation = "spin 2s linear infinite";
	document.getElementById("updateLoader").innerHTML = "";

}
/*
 * Method for showing that somethis went wrong
 * 
 */
function applyErrorMark() {
	jQuery('#updateLoader').removeAttr('style');
	document.getElementById("updateLoader").innerHTML = "X"
	document.getElementById("updateLoader").style.WebkitAnimation = "none";
    document.getElementById("updateLoader").style.color = "rgb(223, 0, 0)";
    document.getElementById("updateLoader").style.borderRadius = "3px";
    document.getElementById("updateLoader").style.backgroundColor = "#0000";
    document.getElementById("updateLoader").style.width = "20px";
    document.getElementById("updateLoader").style.height = "20px";
    document.getElementById("updateLoader").style.textAlign = "center";
    document.getElementById("updateLoader").style.fontSize = "2em";
	document.getElementById("updateLoader").style.fontFamily = "'Helvetica', 'Arial', sans-serif";
	document.getElementById("updateLoader").style.fontWeight = "bold";

}
/*
 * Method for showing that everything was ok
 * 
 */
function applyCheckMark() {
	
	jQuery('#updateLoader').removeAttr('style');
	document.getElementById("updateLoader").innerHTML = "";
    document.getElementById("updateLoader").style.transform = "rotate(45deg)";
    document.getElementById("updateLoader").style.backgroundColor = "#0000";
    document.getElementById("updateLoader").style.width = "9px";
    document.getElementById("updateLoader").style.height = "20px";
    document.getElementById("updateLoader").style.borderBottom = "7px solid #78b13f";
    document.getElementById("updateLoader").style.borderRight = "7px solid #78b13f";

}
/*
 * Method for editing the word with id passed on the parameter
 * 
 *  @param theId  -  the word id
 * 
 */
function editCurrentWord(theId) {

    var plainId = theId.split("editButton").pop();

    if (plainId != editBoxId) {
        if (editBoxIsFirst) {
            editBoxIsFirst = false;
            editCurrentWordReplaceContent(theId, plainId);
        } else if (editBoxIsFirst == false) {
            let prevEditBoxId = "wordEditBox" + editBoxId;
            document.getElementById(prevEditBoxId).innerHTML = editBoxOriginalContent;
            editCurrentWordReplaceContent(theId, plainId);
        }
    } else if (plainId == editBoxId) {
        editCurrentWordReplaceContent(theId, plainId);
    }

}

//  AJAX METHODS START

/*
 * Method for replacing content on that line in order to modify details
 * 
 *  @param theEditButtonId, theEditBoxId  -  button id for defining what needs to be changed, 
 *  and edit box id where is going to change
 * 
 */
function editCurrentWordReplaceContent(theEditButtonId, theEditBoxId) {

    let divEditBoxId = "wordEditBox" + theEditBoxId;
    editBoxOriginalContent = document.getElementById(divEditBoxId).innerHTML;
    editBoxId = theEditBoxId;

    document.getElementById(theEditButtonId).style.display = "none";

    var wordEditFormId = "wordEditForm" + theEditBoxId;
    var lexicon_EditorChangeBox = document.getElementById(wordEditFormId);

    let url = lexicon_EditorChangeBox.dataset.url;
    let params = new URLSearchParams(new FormData(lexicon_EditorChangeBox));
    var addDetails = "";
    var addDetailsWords = "";
    var addDetailsPhrases = "";
    //SHOW SOMETHING THAT YOU TELL THE USER THAT YOU ARE SUBMITTING THE FORM

    fetch(url, {
        method: "POST",
        body: params,
    }).then(response => response.json())
            .catch(error => {
                //SHOW ERROR MESSAGE, RESET EVERYTHING
            })
            .then(response => {
                //RESET EVERYTHING
                //DEAL WITH THE RESPONSE
				
				languagesToChange = response['Updating Languages'];
				var langPointer = 0;
                var countLangs = 0;
                for (var x in response['wordDetails']) {
                    if (response['wordDetails'].hasOwnProperty(x)) {
                        if (x.search("word") !== -1) {
                            countLangs++;
                        }
                    }
                }
				
				var countLangsToChange = languagesToChange.length;
				
				addCategories = "";
				if(response['wordDetails']['Classification'] !== "") {
					addCategories += response['wordDetails']['Classification'] + " / </br/>";
				} else {
					addCategories += "";
				}
				
				if(response['wordDetails']['SubClassification'] !== "") {
					addCategories += response['wordDetails']['SubClassification'] + " / </br/>";
				} else {
					addCategories += "";
				}
				
				if(response['wordDetails']['Group'] !== "") {
					addCategories += response['wordDetails']['Group'] + " / </br/>";
				} else {
					addCategories += "";
				}
				
				if(response['wordDetails']['Example'] !== "") {
					addCategories += response['wordDetails']['Example'] + " / </br/>";
				} else {
					addCategories += "";
				}
				
                addDetails = '<div style="width: 95%; padding-right: 15%; display: inline-flex;">';
				addDetails += '<div style="font-weight: bold; width: 25%; padding-top: 2%; overflow: hidden; ' 
						   + 'text-overflow: ellipsis; white-space: nowrap;"><label>Word Categories:</label></br> ' 
						   + '</br><div style="font-weight: bold; margin-right: 2% padding-top: 2%; white-space: pre-line;"> ' + addCategories + '</div></div>';

                for (var x in response['wordDetails']) {
                    if (response['wordDetails'].hasOwnProperty(x)) {
                        if (x.search("word") !== -1 && countLangs > countLangsToChange) {
                            addDetails += '<div class="lang_wordPhrase_container">'
                                    + '<label><h4>' + x + ':</h4></label>'
                                    + '<div class="wordPhrase_container">'
                                    + response['wordDetails'][x]
                                    + '</div><br/>';
                        } else if (x.search("word") !== -1 && countLangs === countLangsToChange) {
                            addDetails += '<div class="lang_wordPhrase_container">'
                                    + '<label><h4>' + x + ':</h4></label>'
                                    + '<textarea class="wordPhrase_container"  name="wordChanged_' + languagesToChange[langPointer] + theEditBoxId + '">'
                                    + response['wordDetails'][x]
                                    + '</textarea><br/>';
                        }
                        if (x.search("phrase") !== -1 && countLangs > countLangsToChange) {
                            addDetails += '<label><h4>' + x + ':</h4></label>'
                                    + '<div class="wordPhrase_container">'
                                    + response['wordDetails'][x]
                                    + '</div>'
                                    + '</div>';
                            countLangs--;
                        } else if (x.search("phrase") !== -1 && countLangs === countLangsToChange ) {
                            addDetails += '<label><h4>' + x + ':</h4></label>'
                                    + '<textarea class="wordPhrase_container" name="phraseChanged_' + languagesToChange[langPointer] + theEditBoxId + '">'
                                    + response['wordDetails'][x]
                                    + '</textarea>'
                                    + '</div>';
							countLangsToChange--;
							countLangs--;
							langPointer++;
                        }
                    }
                }

                addDetails += '</div>'; //WE ARE INPUTING THE INFO IN THIS VARIABLE

                var element = document.getElementById(wordEditFormId);
                element.parentNode.removeChild(element);

                let addForm = '<form id="lexicon_EditorChange" action="#" method="post" data-url="' + ajaxURLforJsUse + '">';
                let addHiddenButton = '<input type="hidden" id="parseWordDetails' + theEditBoxId + '" value="' + theEditBoxId + '"/>';

                let addHiddenInputAction = '<input type="hidden" name="action" value="submit_editor_change"/>';
                let addButton = '<br/><br/><div style="display: inline-flex">' +
						'<div style="display: flex"><input id="cancelEditWord' + theEditBoxId + '" type="button" class="button-secondary" value="Cancel" onclick="cancelMods(this.id)"/>' +
						'<span style="width: 10px;"></span><input type="submit" class="button-primary" onclick="applyLoader()" value="Update" id="' + theEditBoxId + '"/>' +
						'<span style="width: 10px"></span>' +
						'<div id="updateLoader" class="loader"></div>' +
					'</div>';
                let closeForm = '</form>';

                document.getElementById(divEditBoxId).innerHTML = addForm + addHiddenButton + addDetails + addHiddenInputAction + addButton + closeForm;

                return;
            });

    //REMOVING THE INSIDE FORM


}
/*
 * Method for catching the submit action and enforcing ajax
 * 
 * 
 */
jQuery(document).on("submit", "#lexicon_EditorChange", function (e) {

    e.preventDefault();
    console.log("The form is ready to submit"); //----->TESTING PURPOSES

    var tempParseWordDetails = "parseWordDetails" + editBoxId;
    var lexicon_EditorChange = document.getElementById(tempParseWordDetails).value;

    var lexicon_EditorChangeForm = document.getElementById("lexicon_EditorChange");
    var lexicon_theNameOfEditBox = document.getElementById("theNameOfEditBox");

    let data = {
        currentBoxId: lexicon_EditorChange,
        theNameofBox: lexicon_theNameOfEditBox
    }

    let url = lexicon_EditorChangeForm.dataset.url;
    let params = new URLSearchParams(new FormData(lexicon_EditorChangeForm));

    //SHOW SOMETHING THAT YOU TELL THE USER THAT YOU ARE SUBMITTING THE FORM

    fetch(url, {
        method: "POST",
        body: params,
    }).then(response => response.json())
            .catch(error => {
                applyErrorMark();
            })
            .then(response => {
				if(response === false){
					applyCheckMark();
				} else {
					applyErrorMark();
				}
            })

});


//  AJAX METHODS FINISH

//CHECKBOX IN SETTINGS PAGE ON CHANGE EVENT
if (document.getElementById("additionalLanguageDrop")) {
	var additionalLanguageDropId = document.getElementById("additionalLanguageDrop");
	var additionalLangHiddenInitValue = additionalLanguageDropId.options[additionalLanguageDropId.selectedIndex].value;
}

jQuery(document).on("change", '#allowAdditionalLang', function () {
    if (this.checked !== true) {
        jQuery('#additionalLanguageDrop > option').removeAttr("selected");
        jQuery('#additionalLanguageDrop option').filter(function() {
			return !this.value || jQuery.trim(this.value).length == 0 || jQuery.trim(this.text).length == 0;
		}).remove();
        
        jQuery('#additionalLanguageDrop').append(jQuery('<option selected="">', {
            value: 'spare'
        }));
        
        jQuery("#additionalLangPlaceholder").hide();
        jQuery("#additionalLangSelect").hide();
        additionalLangAllowed = false;
        secondaryLangCurrValue = jQuery('#secondaryLanguageDrop').val();

    } else {

		jQuery('#additionalLanguageDrop option').filter(function() {
			return !this.value || jQuery.trim(this.value).length == 0 || jQuery.trim(this.text).length == 0;
		}).remove();
		
		if (addLangInitValue === "nonNone") {
			
			jQuery("#additionalLanguageDrop")[0].selectedIndex = 0;
			
		} else {
			
			document.getElementById("additionalLanguageDrop").value = addLangInitValue;
			
		}
		
        jQuery("#additionalLangPlaceholder").show();
        jQuery("#additionalLangSelect").show();
		additionalLangCurrValue = jQuery('#additionalLanguageDrop :selected').val();
        secondaryLangInitValue = secLangInitValue;
        secondaryLangCurrValue = jQuery('#secondaryLanguageDrop').val();
        additionalLangAllowed = true;
    }
});

jQuery(document).ready(function () {
	
	secLangInitValue = jQuery("#secondaryLanguageDrop").val();
	
    if (jQuery('#additionalLangPlaceholder').is(":hidden")) {

        jQuery('#additionalLanguageDrop > option').removeAttr("selected");
        var hasOption = jQuery('#additionalLanguageDrop option').filter(function() {
			return !this.value || jQuery.trim(this.value).length == 0 || jQuery.trim(this.text).length == 0;
		}).remove();
        
        jQuery('#additionalLanguageDrop').append(jQuery('<option selected="">', {
            value: 'spare'
        }));
        additionalLangAllowed = false;
		addLangInitValue = "nonNone";
    } else {
        additionalLangAllowed = true;
		addLangInitValue = jQuery("#additionalLanguageDrop").val();
    }
	
	if(document.getElementById("showOptions")){
		settingsLangOrigin = document.getElementById("showOptions").innerHTML;
	}
}
);
