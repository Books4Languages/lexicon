<?php
global $wpdb;
$lex_languages = $wpdb->get_results("SELECT * FROM " . _LEXICON_LANGUAGES . " WHERE Part1 <> '' AND Language_Type = 'L';");

$lex_userId = get_current_user_id();
$lex_userMetaSet = get_user_meta($lex_userId, "secondaryLang", true);
//update_user_meta($lex_userId, "additionalLang", ""); //reset languages
$lex_userMetaSetAdd = get_user_meta($lex_userId, "additionalLang", true);

// Check if the user meta keys are set and there values

if ($lex_userMetaSetAdd === "") {
    $hideIfUnchecked = 'class="hidden"';
    $checkedIfEmpty = '';
} else {
    $hideIfUnchecked = "";
    $checkedIfEmpty = 'checked';
}

// Set some attributes list that are used below. If the additional language is not set, it won't be shown

$additionalLangChecked = "";
?>
<div id="languageChangeMessages" class="update-nag" style="margin: 0px 0px 0px 0px; display: none"></div>
<br/>
<br/>
<?php
if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['submitLang'])) {
    
    // Execute this part of the code when we click on the Save button and then Accept

    if (isset($_POST['secondaryLang'])) {   //If secondary language is set get the value
        $secondaryLangCheckedTemp = $_POST['secondaryLang'];
        $secondaryLangChecked = substr($secondaryLangCheckedTemp, 0, 3);    //Get the code of the language from the value

        if (isset($lex_userMetaSet)) {  //If the key is set then update the value
            update_user_meta($lex_userId, "secondaryLang", $secondaryLangChecked);
            lexicon_add_language_inDB($secondaryLangChecked, "no"); //Execute to chech if the language exists in the DB and if not create its columns
        } else { //Else add the key and its value
            add_user_meta($lex_userId, "secondaryLang", $secondaryLangChecked);
            lexicon_add_language_inDB($secondaryLangChecked, "no"); //Execute to chech if the language exists in the DB and if not create its columns
        }
    } else {
        // If not set, update the global variables values
        if ($lex_userMetaSet) {
            $secondaryLangChecked = get_user_meta($lex_userId, "secondaryLang", true);
        } else {
            $secondaryLangChecked = "";
        }
    }

    if (isset($_POST['additionalLang'])) {  //If additional language is set get the value
        $additionalLangCheckedTemp = $_POST['additionalLang'];

        if ($additionalLangCheckedTemp !== "") { //If additional language is present
            $additionalLangChecked = substr($additionalLangCheckedTemp, 0, 3);  //Get the code of the language from the value
            if (isset($lex_userMetaSetAdd)) {   //If the key is set then update the value
                update_user_meta($lex_userId, "additionalLang", $additionalLangChecked);
                lexicon_add_language_inDB($additionalLangChecked, "no");    //Execute to chech if the language exists in the DB and if not create its columns
            } else {    //Else add the key and its value
                add_user_meta($lex_userId, "additionalLang", $additionalLangChecked);
                lexicon_add_language_inDB($additionalLangChecked, "no");    //Execute to chech if the language exists in the DB and if not create its columns
            }
            $hideIfUnchecked = "";
            $checkedIfEmpty = 'checked';
        } else if ($additionalLangCheckedTemp === "") {
            //If additional language is not present
            if (isset($lex_userMetaSetAdd)) { //Check if key exists
                update_user_meta($lex_userId, "additionalLang", ""); // Update key value
            } else {
                add_user_meta($lex_userId, "additionalLang", "");   // Add key and its value
            }
            update_user_meta($lex_userId, "additionalLang", "");
            $hideIfUnchecked = 'class="hidden"';
            $checkedIfEmpty = '';
        }

    } else {    // If not set, update the global variables values
        if ($lex_userMetaSetAdd !== "") {
            $additionalLangChecked = get_user_meta($lex_userId, "additionalLang", true);
            $hideIfUnchecked = 'class="hidden"';
            $checkedIfEmpty = '';
        } else {
            update_user_meta($lex_userId, "additionalLang", "");
            $additionalLangChecked = "";
            $hideIfUnchecked = "";
            $checkedIfEmpty = 'checked';
        }
    }
    ?>

    <!-- Show table with options -->
    <table id="showOptions">
        <tr>
            <td><b><?php echo _e('Primary Language:', 'mls_lexicon'); ?></b></td>
            <td><?php echo "English"; ?></td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr>
            <td><b><?php echo _e('Secondary Language:', 'mls_lexicon'); ?></b></td>
            <td>
                <select id="secondaryLanguageDrop" name="secondaryLang">
                    <?php
                    foreach ($lex_languages as $lang) { /*Loop in languages, format their names to be just letters and print all the options
                                                            and also check the selected one*/
                        $langId = $lang->id;
                        $langRefNameTemp = $lang->Ref_Name;
                        $langRefNameBrackCheck = strpos($langRefNameTemp, "(");
                        if (!$langRefNameBrackCheck) {
                            $langRefName = $langRefNameTemp;
                        } else {
                            $langRefName = substr($langRefNameTemp, 0, strpos($langRefNameTemp, "(") - 1);
                        }
                        if ($langId === $secondaryLangChecked) {
                            $secondaryLangCheckedValue = " selected";
                        } else {
                            $secondaryLangCheckedValue = "";
                        }
                        echo '<option' . $secondaryLangCheckedValue . ' value="' . $langId . $langRefName . '">' . $langRefName . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr> <!-- Show the additional language part only if it needs to be shown, deciding by the attributes on top of the page -->
            <td id="additionalLangPlaceholder" <?php echo $hideIfUnchecked ?>><b><?php echo _e('Additional Language:', 'mls_lexicon'); ?></b></td>
            <td id="additionalLangSelect" <?php echo $hideIfUnchecked ?>>
                <select id="additionalLanguageDrop" name="additionalLang">
                    <?php
                    foreach ($lex_languages as $lang) { /*Loop in languages, format their names to be just letters and print all the options
                                                            and also check the selected one*/
                        $langId = $lang->id;
                        $langRefNameTemp = $lang->Ref_Name;
                        $langRefNameBrackCheck = strpos($langRefNameTemp, "(");
                        if (!$langRefNameBrackCheck) {
                            $langRefName = $langRefNameTemp;
                        } else {
                            $langRefName = substr($langRefNameTemp, 0, strpos($langRefNameTemp, "(") - 1);
                        }
                        if ($langId === $additionalLangChecked) {
                            $additionalLangCheckedValue = " selected";
                        } else {
                            $additionalLangCheckedValue = "";
                        }
                        echo '<option' . $additionalLangCheckedValue . ' value="' . $langId . $langRefName . '">' . $langRefName . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr></tr>
        <tr>
            <td>
                <div style="display: inline-flex;">
                    <input style="align-self: center;" type="checkbox" <?php echo $checkedIfEmpty ?> id="allowAdditionalLang"/> 
                    <div style="width: 100px;">Allow additional language</div>
                </div>
            </td>
        </tr>
    </table>
    <br/>
    <input type="button" value="Save" onclick="settingsPageChangeLang()" class="button-primary" />

    <?php
} else {
    
    // Execute this part of the code in any other ocasion
    
    ?>

    <table id="showOptions"> <!-- Show the table with all the content -->
        <tr>
            <td><b><?php echo _e('Primary Language:', 'mls_lexicon'); ?></b></td>
            <td><?php echo "English"; ?></td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr>
            <td><b><?php echo _e('Secondary Language:', 'mls_lexicon'); ?></b></td>
            <td>
                <select id="secondaryLanguageDrop" name="secondaryLang">
                    <?php
                    if (!empty($lex_userMetaSet)) {
                        $secondaryLangChecked = get_user_meta($lex_userId, "secondaryLang", true);
                    } else {
                        $secondaryLangChecked = "";
                    }

                    foreach ($lex_languages as $lang) { /*Loop in languages, format their names to be just letters and print all the options
                                                            and also check the selected one*/
                        $langId = $lang->id;
                        $langRefNameTemp = $lang->Ref_Name;
                        $langRefNameBrackCheck = strpos($langRefNameTemp, "(");
                        if (!$langRefNameBrackCheck) {
                            $langRefName = $langRefNameTemp;
                        } else {
                            $langRefName = substr($langRefNameTemp, 0, strpos($langRefNameTemp, "(") - 1);
                        }
                        if ($langId == $secondaryLangChecked) {
                            $secondaryLangCheckedValue = " selected ";
                        } else {
                            $secondaryLangCheckedValue = "";
                        }
                        echo '<option' . $secondaryLangCheckedValue . ' value="' . $langId . $langRefName . '">' . $langRefName . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr> <!-- Show the additional language part only if it needs to be shown, deciding by the attributes on top of the page -->
            <td id="additionalLangPlaceholder" <?php echo $hideIfUnchecked ?>><b><?php echo _e('Additional Language:', 'mls_lexicon'); ?></b></td>
            <td id="additionalLangSelect" <?php echo $hideIfUnchecked ?>>
                <select id="additionalLanguageDrop" name="additionalLang" >

                    <?php
                    if (!empty($lex_userMetaSetAdd)) {
                        $additionalLangChecked = get_user_meta($lex_userId, "additionalLang", true);
                    } else {
                        $additionalLangChecked = "";
                    }

                    foreach ($lex_languages as $lang) { /*Loop in languages, format their names to be just letters and print all the options
                                                            and also check the selected one*/
                        $langId = $lang->id;
                        $langRefNameTemp = $lang->Ref_Name;
                        $langRefNameBrackCheck = strpos($langRefNameTemp, "(");
                        if (!$langRefNameBrackCheck) {
                            $langRefName = $langRefNameTemp;
                        } else {
                            $langRefName = substr($langRefNameTemp, 0, strpos($langRefNameTemp, "(") - 1);
                        }
                        if ($langId == $additionalLangChecked) {
                            $additionalLangCheckedValue = " selected ";
                        } else {
                            $additionalLangCheckedValue = "";
                        }
                        echo '<option' . $additionalLangCheckedValue . ' value="' . $langId . $langRefName . '">' . $langRefName . '</option>';
                    }
                    ?>
                </select>
            </td>
            <td>
            </td>
        </tr>
        <tr></tr>
        <tr>
            <td>
                <div style="display: inline-flex;">
                    <input style="align-self: center;" type="checkbox" <?php echo $checkedIfEmpty ?> id="allowAdditionalLang"/> 
                    <div style="width: 100px;">Allow additional language</div>
                </div>
            </td>
        </tr>
    </table>
    <br/>
    <input type="button" value="Save" onclick="settingsPageChangeLang()" class="button-primary" />
    <?php
}