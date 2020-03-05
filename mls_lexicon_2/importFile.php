<?php

// EXECUTE THIS PART OF THE CODE IF THE SUBMIT BUTTON IS PRESSED AND WE ARE UPLOADING A LANGUAGE FILE
if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['submit_form_lang']) && isset($_POST['lex_lang'])) {

    if (!empty($_FILES['lexicon_file_to_upload'])) {    //Check if file is not empty

        if (!function_exists('wp_handle_upload')) {     //Require the wp_handle_upload function
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $new_cols_name = $_POST['lex_lang'];            

        //lexicon_get_word_details_cols();

        $uploadedfile = $_FILES['lexicon_file_to_upload'];

        $upload_overrides = array('test_form' => false);

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        //print_r($movefile);
        $new_filename = basename($movefile['url']);
        
        $lex_tempfile_url = $movefile['url'];
        $lex_tempfile_name_length = strlen($_FILES['lexicon_file_to_upload']['name']);

        if ($movefile && !isset($movefile['error'])) {      //Upload the file in the wp-content/uploads/ folder of wordpress

            $dir = LEXICON_UPLOAD_DIR;
            $lex_temp_file_loc = strstr($lex_tempfile_url, LEXICON_UPLOAD_DIR_NAME);
            $dir = LEXICON_UPLOAD_DIR . $lex_temp_file_loc;
            $new_dir = $dir;
            define('LEXICON_FILE_TO_REMOVE', $new_dir);
            $direc = substr($new_dir, 0, -$lex_tempfile_name_length);

            lexicon_load($direc, 'lang', $new_cols_name);   //Execute lexicon load function to decode the file and put data in the database
            wp_redirect(esc_url_raw(add_query_arg(array('page' => 'lex_impExp'), admin_url('admin.php')))); //When done, redirect
			exit();
        } else {
            // If something else happens alert that something is wrong
            echo '<script type="text/javascript">alert("Your file was not succesfully uploaded");</script>';
            wp_redirect(esc_url_raw(add_query_arg(array('page' => 'lexicon_testing'), admin_url('admin.php'))));
			exit();
        }
    }
} else if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['submit_form_cat']) && isset($_POST['lex_lang'])) {
    
    // EXECUTE THIS PART OF THE CODE IF THE SUBMIT BUTTON IS PRESSED AND WE ARE UPLOADING A CATEGORY FILE
    
    if (!empty($_FILES['lexicon_file_to_upload'])) {

        if (!function_exists('wp_handle_upload')) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $new_cols_name = $_POST['lex_lang'];

        //lexicon_get_word_details_cols();

        $uploadedfile = $_FILES['lexicon_file_to_upload'];

        $upload_overrides = array('test_form' => false);

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        $new_filename = basename($movefile['url']);
        $lex_tempfile_url = $movefile['url'];
        $lex_tempfile_name_length = strlen($_FILES['lexicon_file_to_upload']['name']);

        if ($movefile && !isset($movefile['error'])) {
            $dir = LEXICON_UPLOAD_DIR;
            $lex_temp_file_loc = strstr($lex_tempfile_url, LEXICON_UPLOAD_DIR_NAME);
            $dir = LEXICON_UPLOAD_DIR . $lex_temp_file_loc;
            $new_dir = $dir;
            define('LEXICON_FILE_TO_REMOVE', $new_dir);
            $direc = substr($new_dir, 0, -$lex_tempfile_name_length);

            lexicon_load($direc, 'catLoad', $new_cols_name);    //Execute lexicon load function to decode the file and put data in the database
            wp_redirect(esc_url_raw(add_query_arg(array('page' => 'lex_impExp'), admin_url('admin.php'))));
			exit();
        } else {
            echo '<script type="text/javascript">alert("Your file was not succesfully uploaded");</script>';
            wp_redirect(esc_url_raw(add_query_arg(array('page' => 'lexicon_testing'), admin_url('admin.php'))));
			exit();
        }
    }
} else if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['submit_form_course']) && isset($_POST['lex_lang'])) {
    
    // EXECUTE THIS PART OF THE CODE IF THE SUBMIT BUTTON IS PRESSED AND WE ARE UPLOADING A COURSE FILE
    
    //Not implemented yet
    
} else {
    
    // EXECUTE THIS PART OF THE CODE IN ANY OTHER OCASION
    
    $twoLetterLivingLanguages = $wpdb->get_results("SELECT * FROM " . _LEXICON_LANGUAGES . " WHERE Part1 <> '' AND Language_Type = 'L';");
    // $smallLangList variable is a short list of languages shown on the dropdown box
    $smallLangList = ['es', 'cs', 'da', 'de', 'et', 'el', 'en', 'fr', 'ga', 'hr', 'it', 'lv', 'lt', 'hu', 'mt', 'nl', 'pl', 'pt', 'ro', 'sk', 'sl', 'fi', 'sv'];

    $moreLanguagesOption = '';
    $lessLanguagesOption = '';
    foreach ($twoLetterLivingLanguages as $langTable) {
        $moreLanguagesOption .= "<option value=" . "$langTable->id" . ">" . "$langTable->Ref_Name" . "</option>";
    }

    foreach ($twoLetterLivingLanguages as $langTable) {
        if (in_array($langTable->Part1, $smallLangList)) {
            $lessLanguagesOption .= "<option value=" . "$langTable->id" . ">" . "$langTable->Ref_Name" . "</option>";
        }
    }
    ?>
    <input type="hidden" value="<?php echo $moreLanguagesOption ?>" id="moreOpt"/> <!-- Store language options with all languages, to pass it on javascript -->
    <input type="hidden" value="<?php echo $lessLanguagesOption ?>" id="lessOpt"/> <!-- Store language options with some languages, to pass it on javascript -->
    
    <div id="importOptions">
        <p class="impOpts">
            <label id="impOptsSelLang" class="impOptsSel" onclick="showSelectedImpOpt('language')">Language</label>
            /
            <label id="impOptsSelCourse" class="impOptsUnSel" onclick="showSelectedImpOpt('course')">Course</label>
            /
            <label id="impOptsSelCat" class="impOptsUnSel" onclick="showSelectedImpOpt('categories')">Categories</label></p>
    </div>
    
    <section id="sectionImportLang" class="impOptsShow">
        <label>Language: </label>
        <?php //echo var_dump($lessLanguagesOption); ?>
        <select name="lex_lang" id="theLanguagesOptions">
            <?php
            echo $lessLanguagesOption;
            ?>
        </select>

        <br/>
        <br/>
        <div id="showLanguagesLink">
            <a href="#" onclick="showMoreLanguages(true)">Need more languages?</a> <!-- Link with function to show more or less language options -->
        </div>
        <br/>
        <br/>

        <input type="file" name="lexicon_file_to_upload" id="lexicon_file_to_upload_id" />

        <br/> <br/>

        <input id="submit" class="button-primary" name="submit_form_lang" type="submit" value="Submit">
    </section>

    <?php
}