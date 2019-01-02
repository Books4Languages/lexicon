<?php

global $wpdb;

//* Add filter to check filetype and extension
add_filter('wp_check_filetype_and_ext', 'testingv1_check_filetype_and_ext', 10, 4);

// If the current user can upload_csv and the file extension is csv, override arguments - edit - "$pathinfo" changed to "pathinfo"
function testingv1_check_filetype_and_ext($args, $file, $filename, $mimes) {
    if (current_user_can('upload_csv') && 'csv' === pathinfo($filename)['extension']) {
        $args = array(
            'ext' => 'csv',
            'type' => 'text/csv',
            'proper_filename' => $filename,
        );
    }
    return $args;
}

function lexicon_word_coexist() {
    global $wpdb;
    $allWords = $wpdb->get_results('SELECT * FROM ' . _LEXICON_WORD_DETAILS . '');

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
                    $newIndependentValues = testingv1_assign_nums(); //------>>>>DAME PREPI NA KALITE TO FUNCTION PU DIMIURGA TON PINAKA ME TO LANG - NUM RELATION
                } else if ($singleColValue === '') {
                    $newIndependentValues = testingv1_assign_nums("untranslated"); //------>>>>DAME PREPI NA KALITE TO FUNCTION PU DIMIURGA TON PINAKA ME TO LANG - NUM RELATION
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
            $sqlsTemp .= 'UPDATE ' . _LEXICON_WORD_CODE . ' SET word_coexist = "' . $finalValuesToInput . '" WHERE id = ' . $wordId . ';';
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
}

function testingv1_assign_nums($type = "all") {

    switch ($type) {
        case "untranslated":
            return "0,";
        case "all":
            return "1,";
        case "fuzzy":
            return "2,";
    }
}

function lexicon_load($dir, $type, $cols_to_add) {
    //echo '<script type="text/javascript">alert("In lexicon load function")</script>';

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
    echo "Done";
}

function lexiconSingleBit2Two($param) {
    if (strlen($param) == 2) {
        return $param;
    } else if (strlen($param) == 1) {
        return '0' . $param;
    }
}

function lexiconSingleBit2Three($param) {
    if (strlen($param) == 3) {
        return $param;
    } else if (strlen($param) == 2) {
        return '0' . $param;
    } else if (strlen($param) == 1) {
        return '00' . $param;
    }
}

function lexicon_load_lang($dir, $lang_name, $cols_to_add) {
    global $wpdb;
    $databaseName = $wpdb->dbname;
    $absolutepath = $dir . $lang_name;
    //echo $absolutepath;
    $sqlsTemp = "";
    //echo '<script type="text/javascript">alert("IN LOAD LANG FUNCTION");</script>';
    //$sqls = array();
    //load file
    $data = file($absolutepath);
    $isFirst = true;
    //$cols_to_add_word = $cols_to_add . '_word';
    //$cols_to_add_phrase = $cols_to_add . '_phrase';
    //$languageExistCheck = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$databaseName' AND TABLE_NAME='" . _LEXICON_WORD_DETAILS . "' and (column_name='$cols_to_add_word' or column_name='$cols_to_add_phrase');");
    $tempValStore = lexicon_add_language_inDB($cols_to_add, "yes");

    $cols_to_add_word = $tempValStore[0];
    $cols_to_add_phrase = $tempValStore[1];
    $languageExistCheck = $tempValStore[2];

    //echo print_r($languageExistCheck);
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
            echo $file_word_word;
        } else {
            $file_word_word = $entry_data[11];
        }
        if (stristr($entry_data[12], "\xEF\xBF\xBD")) {
            $file_word_phrase = substr($entry_data[12], 0, -1);
            echo $file_word_phrase;
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

        //echo var_dump($checkLangExist);

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

    //echo $sqlsTemp;
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
    echo '<script type="text/javascript">alert("Your file was succesfully uploaded");</script>';
    unlink(LEXICON_FILE_TO_REMOVE);
}

/*
  function lexicon_load_auto_categories() {
  global $wpdb;
  $dir = str_replace("\\", "/", LEXICON_DIR) . '/lexicon_all_languages/cod-cat-final.csv';
  $sqlsTemp = "";
  //$sqls = array();
  //load file
  $data = file($dir);
  //session_start();
  //$_SESSION["giannakis"] = $data;       -->TESTING PURPOSE
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
  //$_SESSION["giannakis"] = $entry_data;

  if ($entry_data[0] == "1") {
  $entry_data[0] = "N.G.";
  } else if ($entry_data[0] == "2") {
  $entry_data[0] = "N.E.";
  }

  $entry_data[1] = lexiconSingleBit2Two($entry_data[1]);
  $entry_data[2] = lexiconSingleBit2Two($entry_data[2]);
  $entry_data[3] = lexiconSingleBit2Two($entry_data[3]);
  $entry_data[4] = lexiconSingleBit2Two($entry_data[4]);

  $sqlsTemp .= 'INSERT INTO ' . _LEXICON_WORD_CATEGORIES . '(t_n, c_l, s_c, g_r, e_j, cat_eng, cat_esp) values ("' . $entry_data[0] . '" , "' . $entry_data[1] . '" , "' . $entry_data[2] . '" , "' . $entry_data[3] . '", "' . $entry_data[4] . '" , "' . ucfirst(mb_strtolower($entry_data[6])) . '" , "' . ucfirst(mb_strtolower($entry_data[5])) . '");';
  }
  //$_SESSION["giannakis"] = $sqlsTemp;   -->TESTING PURPOSE
  $sqls = explode(';', $sqlsTemp);
  $countIter = count($sqls);
  $error = false;
  $wpdb->query('START TRANSACTION');
  //$_SESSION["giannakis"] = $countIter;
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
 */

function lexicon_load_word_categories($dir = "empty", $lang_name = "empty", $cols_to_add = "none") {
    global $wpdb;
    $databaseName = $wpdb->dbname;

    $result = $wpdb->get_results('SELECT * FROM ' . _LEXICON_WORD_CATEGORIES . ' WHERE id IS NOT NULL');
    //write_log(count($result));
    //session_start();
    //$_SESSION["otinane"] = count($result);

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

    //echo $absolutepath;
    $sqlsTemp = "";
    //$sqls = array();
    //load file
    $data = file($absolutepath);
    $isFirst = true;
    $isSecond = true;
    //$cols_to_add_cat = 'cat_' . $cols_to_add;
    //$languageExistCheck = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$databaseName' AND TABLE_NAME='" . _LEXICON_WORD_DETAILS . "' and (column_name='$cols_to_add_cat');");
    //echo print_r($languageExistCheck);
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
        //write_log(print_r($checkIdExist, true));
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
    
    /*write_log(print_r($sqlsTemp, true));
    */
    /*if ($finishingTouch == FALSE) {
        write_log("false");
    }else if ($finishingTouch == TRUE){
        write_log("TRUE");
    }*/
    //echo $sqlsTemp;
    $sqls = explode(';', $sqlsTemp);
    $countIter = count($sqls);
    $error = false;
    //write_log($countIter);
    //write_log($sqlsTemp);
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
            //write_log($error);
            $wpdb->query('ROLLBACK');
        } else {
            //write_log($sqlQuery);
            $wpdb->query('COMMIT');
        }
    }
    

    if ($finishingTouch == TRUE) {
        echo '<script type="text/javascript">alert("Your file was succesfully uploaded");</script>';
        unlink(LEXICON_FILE_TO_REMOVE);
    }
}

function lexicon_add_language_inDB($cols_to_add, $sendBack = "no") {

    global $wpdb;

    $cols_to_add_word = $cols_to_add . '_word';
    $cols_to_add_phrase = $cols_to_add . '_phrase';

    echo '<br/>';
    //echo $LEXICON_LANGUAGES_FOR_GET_COLUMNS;
    echo '<br/>';
    //echo $LEXICON_LANGUAGES_FOR_COLUMN_DEFAULT;
    echo '<br/>';

    $databaseName = $wpdb->dbname;
    $checkColExist = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$databaseName' AND TABLE_NAME='" . _LEXICON_WORD_DETAILS . "' and column_name='$cols_to_add_word';");

    //echo print_r($result);

    if (!$checkColExist) {
        $add_cols_query = 'ALTER TABLE ' . _LEXICON_WORD_DETAILS . ' ADD ' . $cols_to_add_word . ' varchar(30) NOT NULL DEFAULT "";'
                . 'ALTER TABLE ' . _LEXICON_WORD_DETAILS . ' ADD ' . $cols_to_add_phrase . ' varchar(120) NOT NULL DEFAULT "";'
                . "UPDATE " . _LEXICON_LANGUAGES . " SET Status='active' WHERE id='" . $cols_to_add . "';";
        //echo $add_cols_query;
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
        return array($cols_to_add_word, $cols_to_add_phrase, $checkColExist);
    }
}

function lexicon_add_wordCats_inDB($cols_to_add, $sendBack = "no") { //NEEDS FULL MODIFICATION
    global $wpdb;

    $cols_to_add_cats = 'cat_' . $cols_to_add;

    echo '<br/>';
    //echo $LEXICON_LANGUAGES_FOR_GET_COLUMNS;
    echo '<br/>';
    //echo $LEXICON_LANGUAGES_FOR_COLUMN_DEFAULT;
    echo '<br/>';

    $databaseName = $wpdb->dbname;
    $checkColExist = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$databaseName' AND TABLE_NAME='" . _LEXICON_WORD_CATEGORIES . "' and column_name='$cols_to_add_cats';");

    //echo print_r($result);

    if (!$checkColExist) {
        $add_cols_query = 'ALTER TABLE ' . _LEXICON_WORD_CATEGORIES . ' ADD ' . $cols_to_add_cats . ' varchar(30) NOT NULL DEFAULT "";';

        //echo $add_cols_query;
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

function lexicon_load_all_lang() {
    global $wpdb;
    $dir = str_replace("\\", "/", LEXICON_DIR) . '/lexicon_all_languages/lexicon_all_lang.csv';
    $sqlsTemp = "";
    //$sqls = array();
    //load file
    $data = file($dir);
    //session_start();
    //$_SESSION["giannakis"] = $data;       -->TESTING PURPOSE
    $isFirst = true;
    //$countEntries = 0;
    foreach ($data as $line) {
        //Remove last CVC comma & new line
        $lineTemp = rtrim($line);
        $lineTempNew = rtrim($lineTemp, ",");
        if ($isFirst) {
            $isFirst = false;
            continue;
        }
        $entry_data = explode(';', $lineTempNew);
        //$_SESSION["giannakis"] = $entry_data;
        $sqlsTemp .= 'INSERT INTO ' . _LEXICON_LANGUAGES . '(id, Part2B, Part2T, Part1, Scope, Language_Type, Ref_Name, Comment) values ("' . $entry_data[0] . '" , "' . $entry_data[1] . '" , "' . $entry_data[2] . '" , "' . $entry_data[3] . '", "' . $entry_data[4] . '" , "' . $entry_data[5] . '" , "' . $entry_data[6] . '" , "' . $entry_data[7] . '");';
        //$countEntries++;
    }
    //$_SESSION["giannakis"] = $sqlsTemp;   -->TESTING PURPOSE
    $sqls = explode(';', $sqlsTemp);
    $countIter = count($sqls);
    $error = false;
    $wpdb->query('START TRANSACTION');
    //$_SESSION["giannakis"] = $countIter;
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
	function write_log ( $log )  {
		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}
}
