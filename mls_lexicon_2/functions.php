<?php

global $wpdb;

//* Add filter to check filetype and extension
add_filter('wp_check_filetype_and_ext', 'lexicon_check_filetype_and_ext', 10, 4);

// If the current user can upload_csv and the file extension is csv, override arguments - edit - "$pathinfo" changed to "pathinfo"
function lexicon_check_filetype_and_ext($args, $file, $filename, $mimes) {
    if (current_user_can('upload_csv') && 'csv' === pathinfo($filename)['extension']) {
        $args = array(
            'ext' => 'csv',
            'type' => 'text/csv',
            'proper_filename' => $filename,
        );
    }
    return $args;
}
/*
 * Method for checking word coexistance
 * 
 */
function lexicon_word_coexist() {
    global $wpdb;
    $allWords = $wpdb->get_results('SELECT code_id FROM ' . _LEXICON_WORD_DETAILS . '');

    $allActiveLangs = $wpdb->get_results('SELECT * FROM ' . _LEXICON_LANGUAGES . ' where Status = "active"');

    $sqlsTemp = "";
    foreach ($allWords as $word) {

        $wordId = $word->code_id;
        $wordPrevCoexist = $wpdb->get_results('SELECT word_coexist FROM ' . _LEXICON_WORD_CODE . ' where id=' . $wordId . '');
        //var_dump($wordPrevCoexist);
        $wordPrevCoexistValue = $wordPrevCoexist[0]->word_coexist;

        $independentLangValues = explode('--', $wordPrevCoexistValue);
        $newCounter = count($independentLangValues);
        $specialWord = array();
        foreach ($independentLangValues as $lang) {
            if (--$newCounter <= 0) {
                break;
            }
            $independentValues = explode(',', $lang);
            if ($independentValues[3] === "true") {
                $specialWord[] = $independentValues[0];
            } else if ($independentValues[3] === "false") {
                continue;
            }
        }
        //var_dump($specialWord);
        $finalValuesToInput = "";
        foreach ($allActiveLangs as $activeLang) {

            $activeLangWordId = $activeLang->id;
            $activeLangWordCol = $activeLangWordId . '_word';
            $activeLangPhraseCol = $activeLangWordId . '_phrase';

            $activeLangCols = [$activeLangWordCol, $activeLangPhraseCol];

            $newValues = "";
            foreach ($activeLangCols as $column) {

                $query = $wpdb->get_results('SELECT ' . $column . ' FROM ' . _LEXICON_WORD_DETAILS . ' where code_id=' . $wordId . '');
                //var_dump($query);
                $singleColValue = $query[0]->$column;

                if ($singleColValue !== '') {
                    $newIndependentValues = lexicon_assign_nums(); //------>>>>CALL FUNCTION THAT CREATES THE NUMBERS FOR WORDS
                } else if ($singleColValue === '') {
                    $newIndependentValues = lexicon_assign_nums("untranslated"); //------>>>>CALL FUNCTION THAT CREATES THE NUMBERS FOR WORDS
                }
                $newValues .= $newIndependentValues;
            }
            if (is_null($specialWord)) {
                $finalValues = $activeLangWordId . "," . $newValues . "false";
            } else {
                if (in_array($activeLangWordId, $specialWord)) {
                    $finalValues = $activeLangWordId . "," . $newValues . "true";
                } else {
                    $finalValues = $activeLangWordId . "," . $newValues . "false";
                }
            }
            $finalValuesToInput .= $finalValues . "--";
        }
        $sqlsTemp .= 'UPDATE ' . _LEXICON_WORD_CODE . ' SET word_coexist = "' . $finalValuesToInput . '" WHERE id = ' . $wordId . ';';
    }
    $sqls = explode(';', $sqlsTemp);
    $countIter = count($sqls);
    $error = false;
    $wpdb->query('START TRANSACTION');
    foreach ($sqls as $sqlQuery) {
        if (--$countIter <= 0) {
            break;
        }
        if (!$wpdb->query($sqlQuery)) {
            $error = true;
            break;
        }
        if ($error) {
            $wpdb->query('ROLLBACK');
        } else {
            $wpdb->query('COMMIT');
        }
    }
}
/*
 * Method for cassigning numbers to words, wether they are fuzzy, untranslated,
 * or whatever we want them to be
 * 
 * @params: $type -- the type of the word or phrase
 * 
 */
function lexicon_assign_nums($type = "all") {

    //Calling this function with the type parameter, translates the type to a number according to our prefer
    
    switch ($type) {
        case "untranslated": //If the ward is unttraslated 0 is returned
            return "0,";
        case "all": //all, is used for a normall situation
            return "1,";
        case "fuzzy": //If the word is fuzzy(has may meanings) we return 2
            return "2,"; 
    } //We can add as many cases as we want. Those numbers appear in word_coexist column in DB
}
/*
 * Method for loading various content
 * 
 */
function lexicon_load($dir, $type, $cols_to_add) {

    $directory = opendir($dir);
    while ($archive = readdir($directory)) {
        if ($archive != '.' && $archive != '..') {
            switch ($type) {
                case 'lang':
                    $x = lexicon_load_lang($dir, $archive, $cols_to_add);
                    break;
                case 'course':
                    $x = lexicon_load_course($dir, $archive);
                    break;
                case 'catLoad':
                    $x = lexicon_load_word_categories($dir, $archive, $cols_to_add);
                    break;
                default:
            }
        }
    }
    closedir($directory);
}
/*
 * Method for transforming single character numbers to double character numbers
 * 
 */
function lexiconSingleBit2Two($param) {
    if (strlen($param) == 2) {
        return $param;
    } else if (strlen($param) == 1) {
        return '0' . $param;
    }
}
/*
 * Method for transforming single character numbers to triple character numbers
 * 
 */
function lexiconSingleBit2Three($param) {
    if (strlen($param) == 3) {
        return $param;
    } else if (strlen($param) == 2) {
        return '0' . $param;
    } else if (strlen($param) == 1) {
        return '00' . $param;
    }
}
/*
 * Method for loading the courses on the database (development)
 * 
 */
function lexicon_load_course($dir, $course_name) {
    global $wpdb;
    $absolutepath = $dir . '/' . $course_name;
    $level = strstr($course_name, '-', true);
    $course_name = strstr($course_name, '-');
    $course_name = substr($course_name, 1);
    $lang = strstr($course_name, '-', true);
    $course_name = strstr($course_name, '-');
    $course_name = substr($course_name, 1);
    $author = strstr($course_name, '.', true);
    $langs = $wpdb->get_results("SELECT DISTINCT lang FROM " . _LEXICON_WORDS . " WHERE lang <> '" . $lang . "'");

    if (count($langs) > 0) {
        $user_id = 1;

        foreach ($langs as $lang_z) {
            $sqls = array();
            $course_id = set_courses($lang, $lang_z->lang, $level, 'A course by ' . $author . '');
            set_course_teacher($user_id, $course_id);
            //load file
            $data = file($absolutepath);
            $isFirst = true;
            foreach ($data as $line) {
                //Remove last CVC comma & new line
                $line = rtrim($line);
                $line = rtrim($line, ",");
                if ($isFirst) {
                    $isFirst = false;
                    continue;
                }
                $entry_data = explode(';', $line);
                $csvToTable .= $entry_data[0] . ';' . $entry_data[1] . ';';
                $sqls[] = 'INSERT INTO ' . _LEXICON_COURSE_CODES . '(code, context, course_id) values ("' . $entry_data[0] . '" , "' . $entry_data[1] . '" , "' . $course_id . '")';
            }
        }

        $error = false;
        $wpdb->query('START TRANSACTION');
        foreach ($sqls as $sql) {

            if (!$wpdb->query($sql)) {
                $error = true;
                break;
            }
            if ($error) {
                $wpdb->query('ROLLBACK');
            } else {
                $wpdb->query('COMMIT');
            }
        }
    }
    return true;
}
/*
 * Method for loading the languages on the database
 * 
 */
function lexicon_load_lang($dir, $lang_name, $cols_to_add) {
    global $wpdb;
    $databaseName = $wpdb->dbname;
    $absolutepath = $dir . $lang_name;
    $sqlsTemp = "";
    //load file
    $data = file($absolutepath);
    $isFirst = true;
    $tempValStore = lexicon_add_language_inDB($cols_to_add, "yes");

    $cols_to_add_word = $tempValStore[0];
    $cols_to_add_phrase = $tempValStore[1];
    $languageExistCheck = $tempValStore[2];

    foreach ($data as $line) {
        //Remove last CVC comma & new line
        $lineTemp = rtrim($line);
        $lineTempNew = rtrim($lineTemp, ",");
        if ($isFirst) {
            $isFirst = false;
            continue;
        }
        $entry_data = explode(';', $lineTempNew);

        //Data to process
        $file_word_id = $entry_data[0];
        $file_word_code = $entry_data[1];
        $file_word_level = $entry_data[2];
        $file_word_tn = $entry_data[3];
        $file_word_cl = $entry_data[4];
        $file_word_sc = $entry_data[5];
        $file_word_gr = $entry_data[6];
        $file_word_ej = $entry_data[7];
        $file_word_p = $entry_data[8];
        $file_word_unit = $entry_data[9];
        $file_word_theme = $entry_data[10];

        if (stristr($entry_data[11], "\xEF\xBF\xBD")) {
            $file_word_word = substr($entry_data[11], 0, -1);
        } else {
            $file_word_word = $entry_data[11];
        }
        if (stristr($entry_data[12], "\xEF\xBF\xBD")) {
            $file_word_phrase = substr($entry_data[12], 0, -1);
        } else {
            $file_word_phrase = $entry_data[12];
        }

        $file_word_cl = lexiconSingleBit2Two($file_word_cl);
        $file_word_sc = lexiconSingleBit2Two($file_word_sc);
        $file_word_gr = lexiconSingleBit2Two($file_word_gr);
        $file_word_ej = lexiconSingleBit2Two($file_word_ej);
        $file_word_p = lexiconSingleBit2Three($file_word_p);

        //FIRST TIME UPLOADING

        if ($file_word_word != '') {
            $word_digit = '1';
        } else if ($file_word_word == '') {
            $word_digit = '0';
        }

        if ($file_word_phrase != '') {
            $phrase_digit = '1';
        } else if ($file_word_phrase == '') {
            $phrase_digit = '0';
        }

        $file_word_code = $file_word_cl . $file_word_sc . $file_word_gr . $file_word_ej . $file_word_p;

        $result = $wpdb->get_results('SELECT * FROM ' . _LEXICON_WORD_CODE . ' WHERE id IS NOT NULL');

        $checkIdExist = $wpdb->get_results('SELECT * FROM ' . _LEXICON_WORD_CODE . ' WHERE id = ' . $file_word_id . '');

        $checkLangExist = $wpdb->get_results('SELECT * FROM ' . _LEXICON_LANGUAGES . ' WHERE Status = "active"');

        if (count($result) == 0) {
            //>>>QUERY USED FOR THE FIRST IMPORTED FILE<<<
            $file_word_coexist = $word_digit . $phrase_digit;
            $sqlsTemp .= 'INSERT INTO ' . _LEXICON_WORD_CODE . '(id, code, level, t_n, word_coexist) values ("' . $file_word_id . '" , "' . $file_word_code . '"  , "' . $file_word_level . '"  , "' . $file_word_tn . '"  , " ");'
                    . 'INSERT INTO ' . _LEXICON_WORD_DETAILS . '(code_id, c_l, s_c, g_r, e_j, p, unit, theme, ' . $cols_to_add_word . ', ' . $cols_to_add_phrase . ') values ("' . $file_word_id . '" , "' . $file_word_cl . '"  , "' . $file_word_sc . '"  , "' . $file_word_gr . '"  , "' . $file_word_ej . '"  , "' . $file_word_p . '"  , "' . $file_word_unit . '"  , "' . $file_word_theme . '" , "' . $file_word_word . '" , "' . $file_word_phrase . '");';
        } else if (count($result) != 0 && count($checkIdExist) == 1 && !$languageExistCheck) {
            //>>>QUERY USED FOR THE REST OF THE FILES IN CASE THERE ARE NO NEW WORDS IN THE CSV FILE AND THE LANGUAGE DOES NOT ALREADY EXIST<<<
            $sqlsTemp .= 'UPDATE ' . _LEXICON_WORD_DETAILS . ' SET ' . $cols_to_add_word . ' = "' . $file_word_word . '", ' . $cols_to_add_phrase . ' = "' . $file_word_phrase . '" WHERE code_id = ' . $file_word_id . ';';
        } else if (count($result) != 0 && count($checkIdExist) == 1 && $languageExistCheck) {
            //>>>QUERY USED FOR THE REST OF THE FILES IN CASE THERE ARE NO NEW WORDS IN THE CSV FILE AND THE LANGUAGE ALREADY EXIST<<<
            $sqlsTemp .= 'UPDATE ' . _LEXICON_WORD_DETAILS . ' SET ' . $cols_to_add_word . ' = "' . $file_word_word . '", ' . $cols_to_add_phrase . ' = "' . $file_word_phrase . '" WHERE code_id = ' . $file_word_id . ';';
        } else if (count($result) != 0 && count($checkIdExist) == 0) {
            //>>>QUERY USED FOR THE REST OF THE FILES IN CASE THERE ARE NEW WORDS IN THE CSV FILE<<<
            $file_word_coexist .= $word_digit . $phrase_digit;
            $sqlsTemp .= 'INSERT INTO ' . _LEXICON_WORD_CODE . '(id, code, level, t_n, word_coexist) values ("' . $file_word_id . '" , "' . $file_word_code . '"  , "' . $file_word_level . '"  , "' . $file_word_tn . '"  , " ");'
                    . 'INSERT INTO ' . _LEXICON_WORD_DETAILS . '(code_id, c_l, s_c, g_r, e_j, p, unit, theme, ' . $cols_to_add_word . ', ' . $cols_to_add_phrase . ') values ("' . $file_word_id . '" , "' . $file_word_cl . '"  , "' . $file_word_sc . '"  , "' . $file_word_gr . '"  , "' . $file_word_ej . '"  , "' . $file_word_p . '"  , "' . $file_word_unit . '"  , "' . $file_word_theme . '" , "' . $file_word_word . '" , "' . $file_word_phrase . '");';
        }
    }

    $sqls = explode(';', $sqlsTemp);
    $countIter = count($sqls);
    $error = false;
    $wpdb->query('START TRANSACTION');
    foreach ($sqls as $sqlQuery) {
        if (--$countIter <= 0) {
            lexicon_word_coexist();
            break;
        }
        if (!$wpdb->query($sqlQuery)) {
            $error = true;
            break;
        }
        if ($error) {
            $wpdb->query('ROLLBACK');
        } else {
            $wpdb->query('COMMIT');
        }
    }
    unlink(LEXICON_FILE_TO_REMOVE);
}
/*
 * Method for loading the word categories on the database
 * 
 */
function lexicon_load_word_categories($dir = "empty", $lang_name = "empty", $cols_to_add = "none") {
    global $wpdb;
    $databaseName = $wpdb->dbname;

    $result = $wpdb->get_results('SELECT * FROM ' . _LEXICON_WORD_CATEGORIES . ' WHERE id IS NOT NULL');

    if (count($result) == 0) {
        $absolutepath = str_replace("\\", "/", LEXICON_DIR) . '/lexicon_all_languages/cod-cat-final.csv';
        $finishingTouch = false;
    } else if (count($result) > 0) {
        $finishingTouch = true;
        $absolutepath = $dir . $lang_name;
        $tempValStore = lexicon_add_wordCats_inDB($cols_to_add, "yes");
        $cols_to_add_cat = $tempValStore[0];
        $languageExistCheck = $tempValStore[1];
    }

    $sqlsTemp = "";
    //load file
    $data = file($absolutepath);
    $isFirst = true;
    $isSecond = true;
    foreach ($data as $line) {
        //Remove last CVC comma & new line
        $lineTemp = rtrim($line);
        $lineTempNew = rtrim($lineTemp, ",");
        if ($isFirst) {
            $isFirst = false;
            continue;
        }
        if ($isSecond) {
            $isSecond = false;
            continue;
        }
        $entry_data = explode(';', $lineTempNew);

        if ($entry_data[0] == "1") {
            $entry_data[0] = "N.G.";
        } else if ($entry_data[0] == "2") {
            $entry_data[0] = "N.E.";
        }

        $entry_data[1] = lexiconSingleBit2Two($entry_data[1]);
        $entry_data[2] = lexiconSingleBit2Two($entry_data[2]);
        $entry_data[3] = lexiconSingleBit2Two($entry_data[3]);
        $entry_data[4] = lexiconSingleBit2Two($entry_data[4]);
        $entry_data[5] = sanitize_text_field($entry_data[5]);
        $entry_data[5] = ucfirst(mb_strtolower($entry_data[5]));

        //$result = $wpdb->get_results('SELECT * FROM ' . _LEXICON_WORD_CATEGORIES . ' WHERE id IS NOT NULL');

        $checkIdExist = $wpdb->get_results('SELECT * FROM ' . _LEXICON_WORD_CATEGORIES . ' WHERE t_n = "' . $entry_data[0] . '" AND c_l = "' . $entry_data[1] . '" AND s_c = "' . $entry_data[2] . '" AND g_r = "' . $entry_data[3] . '" AND e_j = "' . $entry_data[4] . '";');
        if ($finishingTouch == FALSE) {
            //>>>QUERY USED FOR THE FIRST IMPORTED FILE<<<
            $entry_data[6] = sanitize_text_field($entry_data[6]);
            $entry_data[6] = ucfirst(mb_strtolower($entry_data[6]));
            $sqlsTemp .= 'INSERT INTO ' . _LEXICON_WORD_CATEGORIES . ' (t_n, c_l, s_c, g_r, e_j, cat_eng, cat_esp) values ("' . $entry_data[0] . '" , "' . $entry_data[1] . '" , "' . $entry_data[2] . '" , "' . $entry_data[3] . '", "' . $entry_data[4] . '" , "' . $entry_data[6] . '" , "' . $entry_data[5] . '");';
        } else if ($finishingTouch == TRUE && count($checkIdExist) == 1) {
            //>>>QUERY USED FOR THE REST OF THE FILES IN CASE THERE ARE NO NEW WORDS IN THE CSV FILE
            $sqlsTemp .= 'UPDATE ' . _LEXICON_WORD_CATEGORIES . ' SET ' . $cols_to_add_cat . ' = "' . $entry_data[5] . '" WHERE t_n = "' . $entry_data[0] . '" AND c_l = ' . $entry_data[1] . ' AND s_c = ' . $entry_data[2] . ' AND g_r = ' . $entry_data[3] . ' AND e_j = ' . $entry_data[4] . ';';
        } else if ($finishingTouch == TRUE && count($checkIdExist) == 0) {
            //>>>QUERY USED FOR THE REST OF THE FILES IN CASE THERE ARE NEW WORDS IN THE CSV FILE<<<
            $sqlsTemp .= 'INSERT INTO ' . _LEXICON_WORD_CATEGORIES . ' (t_n, c_l, s_c, g_r, e_j, ' . $cols_to_add_cat . ') values ("' . $entry_data[0] . '" , "' . $entry_data[1] . '" , "' . $entry_data[2] . '" , "' . $entry_data[3] . '", "' . $entry_data[4] . '" , "' . $entry_data[5] . '");';
        }
    }

    $sqls = explode(';', $sqlsTemp);
    $countIter = count($sqls);
    $error = false;

    $wpdb->query('START TRANSACTION');
    foreach ($sqls as $sqlQuery) {
        if (--$countIter <= 0) {
            break;
        }
        if (!$wpdb->query($sqlQuery)) {
            $error = true;
            break;
        }
        if ($error) {
            $wpdb->query('ROLLBACK');
        } else {
            $wpdb->query('COMMIT');
        }
    }


    if ($finishingTouch == TRUE) {
        unlink(LEXICON_FILE_TO_REMOVE);
    }
}
/*
 * Method for adding languages names on the database
 * 
 */
function lexicon_add_language_inDB($cols_to_add, $sendBack = "no") {

    global $wpdb;

    $cols_to_add_word = $cols_to_add . '_word';
    $cols_to_add_phrase = $cols_to_add . '_phrase';

    $databaseName = $wpdb->dbname;
    $checkColExist = $wpdb->get_results("SELECT * FROM " . _LEXICON_LANGUAGES . " WHERE id='" . $cols_to_add . "' AND Status='active'");

    if (!$checkColExist) {
        $add_cols_query = 'ALTER TABLE ' . _LEXICON_WORD_DETAILS . ' ADD ' . $cols_to_add_word . ' varchar(50) NOT NULL DEFAULT "";'
                . 'ALTER TABLE ' . _LEXICON_WORD_DETAILS . ' ADD ' . $cols_to_add_phrase . ' varchar(120) NOT NULL DEFAULT "";'
                . "UPDATE " . _LEXICON_LANGUAGES . " SET Status='active' WHERE id='" . $cols_to_add . "';";
        $sqls = explode(';', $add_cols_query);
        $countIter = count($sqls);
        $error = false;
        $wpdb->query('START TRANSACTION');
        foreach ($sqls as $sqlQuery) {
            if (--$countIter <= 0) {
                break;
            }
            if (!$wpdb->query($sqlQuery)) {
                $error = true;
                break;
            }
            if ($error) {
                $wpdb->query('ROLLBACK');
            } else {
                $wpdb->query('COMMIT');
                lexicon_word_coexist(); // Whenever we add a language in the database, we update the coexist column
            }
        }
    }

    if ($sendBack === "yes") {
        return array($cols_to_add_word, $cols_to_add_phrase, $checkColExist);
    }
}
/*
 * Method for importing language names on columns on the database
 * 
 */
function lexicon_add_wordCats_inDB($cols_to_add, $sendBack = "no") {
    global $wpdb;

    $cols_to_add_cats = 'cat_' . $cols_to_add;

    echo '<br/>';
    echo '<br/>';
    echo '<br/>';

    $databaseName = $wpdb->dbname;
    $checkColExist = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$databaseName' AND TABLE_NAME='" . _LEXICON_WORD_CATEGORIES . "' and column_name='$cols_to_add_cats';");

    if (!$checkColExist) {
        $add_cols_query = 'ALTER TABLE ' . _LEXICON_WORD_CATEGORIES . ' ADD ' . $cols_to_add_cats . ' varchar(30) NOT NULL DEFAULT "";';

        $sqls = explode(';', $add_cols_query);
        $countIter = count($sqls);
        $error = false;
        $wpdb->query('START TRANSACTION');
        foreach ($sqls as $sqlQuery) {
            if (--$countIter <= 0) {
                break;
            }
            if (!$wpdb->query($sqlQuery)) {
                $error = true;
                break;
            }
            if ($error) {
                $wpdb->query('ROLLBACK');
            } else {
                $wpdb->query('COMMIT');
            }
        }
    }

    if ($sendBack === "yes") {
        return array($cols_to_add_cats, $checkColExist);
    }
}
/*
 * Method for loading all languages from a specified file
 * 
 */
function lexicon_load_all_lang() {
    global $wpdb;
    $dir = str_replace("\\", "/", LEXICON_DIR) . '/lexicon_all_languages/lexicon_all_lang.csv';
    $sqlsTemp = "";
    //load file
    $data = file($dir);
    $isFirst = true;
    foreach ($data as $line) {
        //Remove last CVC comma & new line
        $lineTemp = rtrim($line);
        $lineTempNew = rtrim($lineTemp, ",");
        if ($isFirst) {
            $isFirst = false;
            continue;
        }
        $entry_data = explode(';', $lineTempNew);
        $sqlsTemp .= 'INSERT INTO ' . _LEXICON_LANGUAGES . '(id, Part2B, Part2T, Part1, Scope, Language_Type, Ref_Name, Comment) values ("' . $entry_data[0] . '" , "' . $entry_data[1] . '" , "' . $entry_data[2] . '" , "' . $entry_data[3] . '", "' . $entry_data[4] . '" , "' . $entry_data[5] . '" , "' . $entry_data[6] . '" , "' . $entry_data[7] . '");';
    }
    $sqls = explode(';', $sqlsTemp);
    $countIter = count($sqls);
    $error = false;
    $wpdb->query('START TRANSACTION');
    foreach ($sqls as $sqlQuery) {
        if (--$countIter <= 0) {
            break;
        }
        if (!$wpdb->query($sqlQuery)) {
            $error = true;
            break;
        }
        if ($error) {
            $wpdb->query('ROLLBACK');
        } else {
            $wpdb->query('COMMIT');
        }
    }
}

if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}
/*
 * Method for submiting editors changes
 * 
 */
function submit_editor_change() {

    global $wpdb;

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'wordChanged') === 0) {
            $wordChangedId[] = substr(substr($key, 12), 3);
            $wordChangedLang[] = substr(substr($key, 12), 0, 3);
            $wordChangedValue[] = sanitize_textarea_field($value);
        } else if (strpos($key, 'phraseChanged') === 0) {
            $phraseChangedId[] = substr(substr($key, 14), 3);
            $phraseChangedLang[] = substr(substr($key, 14), 0, 3);
            $phraseChangedValue[] = sanitize_textarea_field($value);
        }
    }

    $updateQuery = "UPDATE " . _LEXICON_WORD_DETAILS . " SET ";

    if (count($wordChangedId) === count($wordChangedLang) && count($wordChangedLang) === count($wordChangedValue) && count($phraseChangedId) === count($phraseChangedLang) && count($phraseChangedLang) === count($phraseChangedValue)) {
        for ($i = 0; $i < count($wordChangedId); $i++) {
            if (($i + 1) === count($wordChangedId)) {
                $updateQuery .= $wordChangedLang[$i] . "_word = '" . $wordChangedValue[$i] . "', " . $phraseChangedLang[$i] . "_phrase = '" . $phraseChangedValue[$i] . "'";
            } else {
                $updateQuery .= $wordChangedLang[$i] . "_word = '" . $wordChangedValue[$i] . "', " . $phraseChangedLang[$i] . "_phrase = '" . $phraseChangedValue[$i] . "', ";
            }
        }
    }

    $updateQuery .= " WHERE code_id = " . $wordChangedId[0] . ";";

    $sqls = explode(';', $updateQuery);
    $countIter = count($sqls);
    $error = false;
    $wpdb->query('START TRANSACTION');

    foreach ($sqls as $sqlQuery) {
        if (--$countIter <= 0) {
            break;
        }
        if (!$wpdb->query($sqlQuery)) {
            $error = true;
            break;
        }
        if ($error) {
            $wpdb->query('ROLLBACK');
        } else {
            $wpdb->query('COMMIT');
        }
    }
    if($error === false) {
        lexicon_word_coexist();
    }
    //echo "Yes I got your request";
    wp_send_json($error);
    wp_die();
}

add_action('wp_ajax_submit_editor_change', 'submit_editor_change');
add_action('wp_ajax_nopriv_submit_editor_change', 'submit_editor_change');
/*
 * Method for acquiring the word categories
 * 
 */
function fetchCategories() {
    global $wpdb;

    $wordClassificationQuery = "SELECT * FROM " . _LEXICON_WORD_CATEGORIES . " WHERE s_c='00' AND g_r='00' AND e_j='00'";
    $wordSubClassificationQuery = "SELECT * FROM " . _LEXICON_WORD_CATEGORIES . " WHERE s_c<>'00' AND g_r='00' AND e_j='00'";
    $wordGroupQuery = "SELECT * FROM " . _LEXICON_WORD_CATEGORIES . " WHERE s_c<>'00' AND g_r<>'00' AND e_j='00'";
    $wordExampleQuery = "SELECT * FROM " . _LEXICON_WORD_CATEGORIES . " WHERE s_c<>'00' AND g_r<>'00' AND e_j<>'00'";

    $wordClassificationResult = $wpdb->get_results($wordClassificationQuery, 'ARRAY_A');
    $wordSubClassificationResult = $wpdb->get_results($wordSubClassificationQuery, 'ARRAY_A');
    $wordGroupResult = $wpdb->get_results($wordGroupQuery, 'ARRAY_A');
    $wordExampleResult = $wpdb->get_results($wordExampleQuery, 'ARRAY_A');

    $response = array(
        'classification' => $wordClassificationResult,
        'subClassification' => $wordSubClassificationResult,
        'group' => $wordGroupResult,
        'example' => $wordExampleResult,
    );

    return $response;
}
/*
 * Method for acquiring the word's category group
 * 
 */
function fetchCategoryGroup($classParam = " ", $subclassParam = " ", $groupParam = " ", $exampleParam = " ") {

    global $wpdb;
    $error = FALSE;

    if ($classParam !== " ") {
        $classParam = "c_l='" . $classParam . "'";
    } else {
        $error = TRUE;
    }
    if ($subclassParam !== " ") {
        $subclassParam = "AND s_c='" . $subclassParam . "'";
    } else if ($subclassParam === " ") {
        $subclassParam = " ";
    } else {
        $error = TRUE;
    }
    if ($groupParam !== " ") {
        $groupParam = "AND g_r='" . $groupParam . "'";
    } else if ($groupParam === " ") {
        $groupParam = " ";
    } else {
        $error = TRUE;
    }
    if ($exampleParam !== " ") {
        $exampleParam = "AND e_j='" . $exampleParam . "'";
    } else if ($exampleParam === " ") {
        $exampleParam = " ";
    } else {
        $error = TRUE;
    }

    $wordCategoryQuery = "SELECT * FROM " . _LEXICON_WORD_CATEGORIES . " WHERE" . $classParam . $subclassParam . $groupParam . $exampleParam;

    if (!$error) {
        $wordCategoryResult = $wpdb->get_results($wordCategoryQuery, 'ARRAY_A');

        $response = array(
            'wordCategorySet' => $wordCategoryResult,
        );

        return $response;
    } else if ($error) {
        $response = 'error';

        return $response;
    }
}

$isFirstFetchFullLangWordDetails = 'true';
/*
 * Method for acquiring all word details
 * 
 */
function fetchFullLangWordDetails($counter, $langOffset = "", $wordOrPhrase = "") {

    global $wpdb;

    if ($GLOBALS['isFirstFetchFullLangWordDetails'] === 'true') {

        $GLOBALS['isFirstFetchFullLangWordDetails'] = 'false';

        $lex_userId = get_current_user_id();
        $lex_userPrimaryLang = get_user_meta($lex_userId, "primaryLang", true);
        $lex_userSecondaryLang = get_user_meta($lex_userId, "secondaryLang", true);
        $lex_userAdditionalLang = get_user_meta($lex_userId, "additionalLang", true);

        if (empty($lex_userAdditionalLang)) {
            $GLOBALS['additionalCheck'] = false;
        } else if (!empty($lex_userAdditionalLang)) {
            $GLOBALS['additionalCheck'] = true;
        }

		if( $GLOBALS['additionalCheck'] === true){
			$langsQueryPart1 = "SELECT Ref_Name FROM " . _LEXICON_LANGUAGES . " WHERE id='" . $lex_userPrimaryLang . "'";
			$langsQueryPart2 = "SELECT Ref_Name FROM " . _LEXICON_LANGUAGES . " WHERE id='" . $lex_userSecondaryLang . "'";
			$langsQueryPart3 = "SELECT Ref_Name FROM " . _LEXICON_LANGUAGES . " WHERE id='" . $lex_userAdditionalLang . "'";
			$fullNameLangsPart1 = $wpdb->get_results($langsQueryPart1, 'ARRAY_A');
			$fullNameLangsPart2 = $wpdb->get_results($langsQueryPart2, 'ARRAY_A');
			$fullNameLangsPart3 = $wpdb->get_results($langsQueryPart3, 'ARRAY_A');

			$GLOBALS['fullNameLangs'] = array_column(array_merge_recursive($fullNameLangsPart1, $fullNameLangsPart2, $fullNameLangsPart3), 'Ref_Name');
		} else if($GLOBALS['additionalCheck'] === false){
			$langsQueryPart1 = "SELECT Ref_Name FROM " . _LEXICON_LANGUAGES . " WHERE id='" . $lex_userPrimaryLang . "'";
			$langsQueryPart2 = "SELECT Ref_Name FROM " . _LEXICON_LANGUAGES . " WHERE id='" . $lex_userSecondaryLang . "'";
			
			$fullNameLangsPart1 = $wpdb->get_results($langsQueryPart1, 'ARRAY_A');
			$fullNameLangsPart2 = $wpdb->get_results($langsQueryPart2, 'ARRAY_A');


			$GLOBALS['fullNameLangs'] = array_column(array_merge_recursive($fullNameLangsPart1, $fullNameLangsPart2), 'Ref_Name');
		}
        $mainQueryAdd = '';
        $activeLangsQuery = "SELECT id FROM " . _LEXICON_LANGUAGES . " WHERE Status='active'";
        $res = $wpdb->get_results($activeLangsQuery, 'ARRAY_A');
        foreach ($res as $value) {
            if ($value['id'] != $lex_userPrimaryLang && $value['id'] != $lex_userSecondaryLang && $value['id'] != $lex_userAdditionalLang) {
                $mainQueryAdd .= "ALTER TABLE temp_tb DROP " . $value['id'] . "_word;"
                        . "ALTER TABLE temp_tb DROP " . $value['id'] . "_phrase;";
            }
        }

        $query = "CREATE TEMPORARY TABLE temp_tb SELECT *
          FROM " . _LEXICON_WORD_DETAILS . "
          INNER JOIN " . _LEXICON_WORD_CODE . " ON " . _LEXICON_WORD_DETAILS . ".code_id=" . _LEXICON_WORD_CODE . ".id;";

        $query .= "ALTER TABLE temp_tb DROP id;"
                . "ALTER TABLE temp_tb DROP code;"
                . "ALTER TABLE temp_tb DROP word_coexist;";

        $query .= $mainQueryAdd;

        $sqls = explode(';', $query);
        $countIter = count($sqls);
        $error = false;
        $wpdb->query('START TRANSACTION');
        foreach ($sqls as $sqlQuery) {
            if (--$countIter <= 0) {
                break;
            }
            if (!$wpdb->query($sqlQuery)) {
                $error = true;
                break;
            }
            if ($error) {
                $wpdb->query('ROLLBACK');
            } else {
                $wpdb->query('COMMIT');
            }
        }

        $query = "SELECT * FROM temp_tb";

        $GLOBALS['result'] = $wpdb->get_results($query, 'ARRAY_A');

        $GLOBALS['countArray'] = count($GLOBALS['result']);

        if ($GLOBALS['additionalCheck'] === true) {
            $GLOBALS['updatedKeys'] = ["code_id" => "Word Code",
                "c_l" => "Classification",
                "s_c" => "SubClassification",
                "g_r" => "Group",
                "e_j" => "Example",
                "p" => "Word digit",
                "unit" => "Unit",
                "theme" => "Theme",
                $lex_userPrimaryLang . "_word" => $GLOBALS['fullNameLangs'][0] . " word",
                $lex_userPrimaryLang . "_phrase" => $GLOBALS['fullNameLangs'][0] . " phrase",
                $lex_userSecondaryLang . "_word" => $GLOBALS['fullNameLangs'][1] . " word",
                $lex_userSecondaryLang . "_phrase" => $GLOBALS['fullNameLangs'][1] . " phrase",
                $lex_userAdditionalLang . "_word" => $GLOBALS['fullNameLangs'][2] . " word",
                $lex_userAdditionalLang . "_phrase" => $GLOBALS['fullNameLangs'][2] . " phrase",
                "level" => "Level",
                "t_n" => "General/Specific",
            ];
        } else if ($GLOBALS['additionalCheck'] === false) {
            $GLOBALS['updatedKeys'] = ["code_id" => "Word Code",
                "c_l" => "Classification",
                "s_c" => "SubClassification",
                "g_r" => "Group",
                "e_j" => "Example",
                "p" => "Word digit",
                "unit" => "Unit",
                "theme" => "Theme",
                $lex_userPrimaryLang . "_word" => $GLOBALS['fullNameLangs'][0] . " word",
                $lex_userPrimaryLang . "_phrase" => $GLOBALS['fullNameLangs'][0] . " phrase",
                $lex_userSecondaryLang . "_word" => $GLOBALS['fullNameLangs'][1] . " word",
                $lex_userSecondaryLang . "_phrase" => $GLOBALS['fullNameLangs'][1] . " phrase",
                "level" => "Level",
                "t_n" => "General/Specific",
            ];
        }

        return sendBackFullLangWordDetails($counter, $langOffset, $wordOrPhrase);
    } else if ($GLOBALS['isFirstFetchFullLangWordDetails'] === 'false') {
        return sendBackFullLangWordDetails($counter, $langOffset, $wordOrPhrase);
    }
}
/*
 * Method for sending back all word details
 * 
 */
function sendBackFullLangWordDetails($counter, $langOffset = "", $wordOrPhrase = "") {
    if ($langOffset !== "" || $wordOrPhrase !== "") {
        if ($counter !== -1) {
            $tempTable = $GLOBALS['result'][$counter];
            $tempTableNew = array_combine(array_merge($tempTable, $GLOBALS['updatedKeys']), $tempTable);
            if ($GLOBALS['additionalCheck'] === true) {
                switch ($langOffset) {
                    case 'prim':
                        return $tempTableNew[$GLOBALS['fullNameLangs'][0] . $wordOrPhrase];
                    case 'sec':
                        return $tempTableNew[$GLOBALS['fullNameLangs'][1] . $wordOrPhrase];
                    case 'add':
                        return $tempTableNew[$GLOBALS['fullNameLangs'][2] . $wordOrPhrase];
                    case 'all':
                        return $tempTableNew['Word Code'];
                }
            } else if ($GLOBALS['additionalCheck'] === false) {
                switch ($langOffset) {
                    case 'prim':
                        return $tempTableNew[$GLOBALS['fullNameLangs'][0] . $wordOrPhrase];
                    case 'sec':
                        return $tempTableNew[$GLOBALS['fullNameLangs'][1] . $wordOrPhrase];
                    case 'all':
                        return $tempTableNew['Word Code'];
                }
            }
        } else if ($counter === -1) {
            if ($GLOBALS['additionalCheck'] === true) {
                switch ($langOffset) {
                    case 'prim':
                        return $GLOBALS['fullNameLangs'][0];
                    case 'sec':
                        return $GLOBALS['fullNameLangs'][1];
                    case 'add':
                        return $GLOBALS['fullNameLangs'][2];
                    case 'countArray':
                        return $GLOBALS['countArray'];
                    case 'langCheck':
                        if ($GLOBALS['additionalCheck']) {
                            return '3';
                        } else if (!$GLOBALS['additionalCheck']) {
                            return '2';
                        }
                }
            } else if ($GLOBALS['additionalCheck'] === false) {
                switch ($langOffset) {
                    case 'prim':
                        return $GLOBALS['fullNameLangs'][0];
                    case 'sec':
                        return $GLOBALS['fullNameLangs'][1];
                    case 'countArray':
                        return $GLOBALS['countArray'];
                    case 'langCheck':
                        if ($GLOBALS['additionalCheck']) {
                            return '3';
                        } else if (!$GLOBALS['additionalCheck']) {
                            return '2';
                        }
                }
            }
        }
    } else if ($langOffset === "" && $wordOrPhrase === "") {
        $tempTable = $GLOBALS['result'][$counter];
        $tempTableNew = array_combine(array_merge($tempTable, $GLOBALS['updatedKeys']), $tempTable);
        //write_log(var_dump($tempTableNew));

        return array(
            'wordDetails' => $tempTableNew,
        );
    }
}
/*
 * Method for getting the full name of a language from the 3 first characters
 * 
 * @params $variable - the 3 first letters of the language
 */
function shortLangToFull($variable) {

    global $wpdb;

    $allLanguages = $wpdb->get_results("SELECT * FROM " . _LEXICON_LANGUAGES . " where Status = 'active'");

    foreach ($allLanguages as $item) {
        switch ($variable) {
            case "$item->id": return "$item->Ref_Name";
            case "$item->Part1": return "$item->Ref_Name";
        }
    }
}
/*
 * Method for acquiring some word details. Used in Editors Page
 * 
 */
function fetchLangWordDetails($id, $prim, $sec, $add = "") {
    global $wpdb;
    if ($add !== "") {
        $columnSet = $prim . "_word, " . $prim . "_phrase, " . $sec . "_word, " . $sec . "_phrase, " . $add . "_word, " . $add . "_phrase";

        $updatedKeys = [
            "code_id" => "Code Id",
            $prim . "_word" => shortLangToFull($prim) . " word",
            $prim . "_phrase" => shortLangToFull($prim) . " phrase",
            $sec . "_word" => shortLangToFull($sec) . " word",
            $sec . "_phrase" => shortLangToFull($sec) . " phrase",
            $add . "_word" => shortLangToFull($add) . " word",
            $add . "_phrase" => shortLangToFull($add) . " phrase",
            "c_l" => "Classification",
            "s_c" => "SubClassification",
            "g_r" => "Group",
            "e_j" => "Example",
            "t_n" => "General/Specific"
        ];
    } else {
        $columnSet = $prim . "_word, " . $prim . "_phrase, " . $sec . "_word, " . $sec . "_phrase";

        $updatedKeys = [
            "code_id" => "Code Id",
            $prim . "_word" => shortLangToFull($prim) . " word",
            $prim . "_phrase" => shortLangToFull($prim) . " phrase",
            $sec . "_word" => shortLangToFull($sec) . " word",
            $sec . "_phrase" => shortLangToFull($sec) . " phrase",
            "c_l" => "Classification",
            "s_c" => "SubClassification",
            "g_r" => "Group",
            "e_j" => "Example",
            "t_n" => "General/Specific"
        ];
    }
    $query = "SELECT " . _LEXICON_WORD_DETAILS . ".code_id, " . _LEXICON_WORD_DETAILS . ".c_l, "
            . _LEXICON_WORD_DETAILS . ".s_c, " . _LEXICON_WORD_DETAILS . ".g_r, " . _LEXICON_WORD_DETAILS . ".e_j, "
            . _LEXICON_WORD_CODE . ".t_n, " . $columnSet . " FROM " . _LEXICON_WORD_DETAILS . ", " . _LEXICON_WORD_CODE . " WHERE " . _LEXICON_WORD_DETAILS . ".code_id='" . $id . "'";

    $query = "SELECT tblA.code_id, tblA.c_l, tblA.s_c, tblA.g_r, tblA.e_j, "
            . "tblB.t_n, " . $columnSet
            . " FROM "
            . _LEXICON_WORD_DETAILS . " as tblA, "
            . _LEXICON_WORD_CODE . " as tblB"
            . " WHERE "
            . "tblA.code_id='" . $id . "'";

    $results = $wpdb->get_results($query, 'ARRAY_A');
    foreach ($results as $result) {

        $response = array_combine(array_merge($result, $updatedKeys), $result);
    }

    $catQuery = "SELECT cat_eng FROM " . _LEXICON_WORD_CATEGORIES . " WHERE (t_n='" . $response["General/Specific"] . "') AND"
            . " ((c_l='" . $response["Classification"] . "' AND s_c='00' AND g_r='00' AND e_j='00') OR"
            . " (c_l='" . $response["Classification"] . "' AND s_c='" . $response["SubClassification"] . "' AND g_r='00' AND e_j='00') OR"
            . " (c_l='" . $response["Classification"] . "' AND s_c='" . $response["SubClassification"] . "' AND g_r='" . $response["Group"] . "' AND e_j='00') OR"
            . " (c_l='" . $response["Classification"] . "' AND s_c='" . $response["SubClassification"] . "' AND g_r='" . $response["Group"] . "' AND e_j='" . $response["Example"] . "')) ";

    $resultsCatQuery = array();

    $resultsPartB = $wpdb->get_results($catQuery, 'ARRAY_A');
    if ($resultsPartB) {
        foreach ($resultsPartB as $resultB) {
            $resultsCatQuery[] = $resultB["cat_eng"];
        }
    }

    for ($i = 0; $i < count($resultsCatQuery); $i++) {
        switch ($i) {
            case 0:
                if (array_key_exists(0, $resultsCatQuery)) {
                    $response["Classification"] = $resultsCatQuery[0];
                } else {
                    $response["Classification"] = "";
                }
            case 1:
                if (array_key_exists(1, $resultsCatQuery)) {
                    $response["SubClassification"] = $resultsCatQuery[1];
                } else {
                    $response["SubClassification"] = "";
                }
            case 2:
                if (array_key_exists(2, $resultsCatQuery)) {
                    $response["Group"] = $resultsCatQuery[2];
                } else {
                    $response["Group"] = "";
                }
            case 3:
                if (array_key_exists(3, $resultsCatQuery)) {
                    $response["Example"] = $resultsCatQuery[3];
                } else {
                    $response["Example"] = "";
                }
        }
    }

    return array(
        'wordDetails' => $response,
    );
}
/*
 * Method for determining which word is the editor currently changing
 * 
 */
function editor_changing() {

    $tempHoldId = $_POST['wordIdToEdit'];

    //LANGUAGES CHECK

    $lex_userId = get_current_user_id();
    $lex_userMetaSet = get_user_meta($lex_userId, "secondaryLang", true);
    $lex_userMetaSetAdd = get_user_meta($lex_userId, "additionalLang", true);

    if (current_user_can('editor') || current_user_can('administrator')) {
        $userPriv = "Admin/Editor";
    } else {
        $userPriv = "Other";
    }

    if ($userPriv === "Admin/Editor" && $lex_userMetaSetAdd === "") {
        $updatingLangs = array("eng", $lex_userMetaSet);
    } else if ($userPriv === "Admin/Editor" && $lex_userMetaSetAdd !== "") {
        $updatingLangs = array("eng", $lex_userMetaSet, $lex_userMetaSetAdd);
    } else if ($userPriv === "Other" && $lex_userMetaSetAdd === "") {
        $updatingLangs = array($lex_userMetaSet);
    } else if ($userPriv === "Other" && $lex_userMetaSetAdd !== "") {
        $updatingLangs = array($lex_userMetaSetAdd);
    }
    $tempArray = array(
        "Privilage" => $userPriv,
        "Updating Languages" => $updatingLangs
    );


    if ($lex_userMetaSetAdd !== "") {
        $responsePart1 = fetchLangWordDetails($tempHoldId, "eng", $lex_userMetaSet, $lex_userMetaSetAdd);
    } else {
        $responsePart1 = fetchLangWordDetails($tempHoldId, "eng", $lex_userMetaSet);
    }

    $response = $responsePart1;

    $res = array_merge($response, $tempArray);
    wp_send_json($res);

    wp_die();
}

add_action('wp_ajax_editor_changing', 'editor_changing');
add_action('wp_ajax_nopriv_editor_changing', 'editor_changing');

//allow redirection, even if my theme starts to send output to the browser
add_action('init', 'do_output_buffer');

function do_output_buffer() {
    ob_start();
}
