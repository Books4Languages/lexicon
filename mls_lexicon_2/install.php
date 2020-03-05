<?php

/* * ***************************************
 * *		Database installation
 * *************************************** */

global $wpdb;
$lexicon_db_version = "1.1";

// Check for existing DB
if (get_option("lexicon_db_version") == "") {
    //No db found

    /*
     * 	DB Tables SQL
     */
    $sqls = Array();
    // lexicon_course
    $sqls[] = "CREATE TABLE `" . _LEXICON_COURSE . "`(
					`id` int unsigned NOT NULL auto_increment PRIMARY KEY,
					`lang_1` varchar(30) NOT NULL DEFAULT '',
					`lang_2` varchar(30) NOT NULL DEFAULT '',		
					`level` varchar(10) NOT NULL DEFAULT '',
					`description` varchar(255)							
					) DEFAULT CHARSET=utf8; ";
    // lexicon_course_student
    $sqls[] = "CREATE TABLE `" . _LEXICON_COURSE_STUDENT . "`(
					`student_id` bigint(20) NOT NULL DEFAULT 0,
					`course_id` int unsigned NOT NULL DEFAULT 0,
					`state` int unsigned NOT NULL DEFAULT 0,
                                        PRIMARY KEY (student_id, course_id),
					CONSTRAINT `COURSE_STUDENT_FK01` FOREIGN KEY (course_id) REFERENCES " . _LEXICON_COURSE . " (id)
                                        ON DELETE CASCADE
                                        ON UPDATE CASCADE	
					) DEFAULT CHARSET=utf8; ";
    // lexicon_course_student_card
    $sqls[] = "CREATE TABLE `" . _LEXICON_COURSE_SUTDENT_CARD . "`(
					`student_id` bigint(20) NOT NULL DEFAULT 0,
					`course_id` int unsigned NOT NULL DEFAULT 0,
					`code` varchar(16) NOT NULL DEFAULT '',
					`prog_level` int unsigned NOT NULL DEFAULT 0,
					CONSTRAINT `COURSE_STUDENT_CARD_FK01` FOREIGN KEY (student_id) REFERENCES " . _LEXICON_COURSE_STUDENT . " (student_id)
                                        ON DELETE CASCADE
                                        ON UPDATE CASCADE,
					CONSTRAINT `COURSE_STUDENT_CARD_FK02` FOREIGN KEY (course_id) REFERENCES " . _LEXICON_COURSE . " (id)
                                        ON DELETE CASCADE
                                        ON UPDATE CASCADE			
					) DEFAULT CHARSET=utf8; ";
    // lexicon_course_author
    $sqls[] = "CREATE TABLE `" . _LEXICON_COURSE_AUTHOR . "`(
					`teacher_id` bigint(20) NOT NULL DEFAULT 0,
					`course_id` int unsigned NOT NULL DEFAULT 0,									
					PRIMARY KEY (teacher_id, course_id)					
					) DEFAULT CHARSET=utf8; ";
    // lexicon_course_codes
    $sqls[] = "CREATE TABLE `" . _LEXICON_COURSE_CODES . "`(
					`course_id` int unsigned NOT NULL DEFAULT 0,
					`code` varchar(16) NOT NULL DEFAULT '',
                                        `context` varchar(120),
					PRIMARY KEY (course_id, code)			
					) DEFAULT CHARSET=utf8; ";
    // lexicon_words
    $sqls[] = "CREATE TABLE `" . _LEXICON_WORDS . "`(
                                `id` int unsigned NOT NULL auto_increment PRIMARY KEY,
                                `code` varchar(16) NOT NULL DEFAULT '',
                                `text` varchar(30) NOT NULL DEFAULT '',
                                `phrase` varchar(120),
          			`context` varchar(120),
  			        `level` varchar(10) NOT NULL DEFAULT '',
  			        `column_6` varchar(10) NOT NULL DEFAULT '',
  			        `column_7` varchar(10) NOT NULL DEFAULT '',
  			        `column_8` varchar(10) NOT NULL DEFAULT '',
  			        `column_9` varchar(10) NOT NULL DEFAULT '',
   			      	`column_10` varchar(10) NOT NULL DEFAULT '',
                                `column_11` varchar(10) NOT NULL DEFAULT '',
  			        `column_12` varchar(10) NOT NULL DEFAULT '',
                                `column_13` varchar(10) NOT NULL DEFAULT '',
                                `column_14` varchar(10) NOT NULL DEFAULT '',
                                `column_15` varchar(10) NOT NULL DEFAULT '',
                                `column_16` varchar(10) NOT NULL DEFAULT '',
                                `lang` varchar(30) NOT NULL DEFAULT ''
					) DEFAULT CHARSET=utf8; ";
    // lexicon_word_code
    $sqls[] = "CREATE TABLE `" . _LEXICON_WORD_CODE . "`(
                                `id` int unsigned NOT NULL PRIMARY KEY,
                                `code` varchar(16) NOT NULL DEFAULT '',
  			        `level` varchar(10) NOT NULL DEFAULT '',
                                `t_n` varchar(16) NOT NULL DEFAULT '',
                                `word_coexist` varchar(800) NOT NULL DEFAULT ''
					) DEFAULT CHARSET=utf8; ";
    // lexicon_word_details
    $sqls[] = "CREATE TABLE `" . _LEXICON_WORD_DETAILS . "`(
                                `code_id` int unsigned NOT NULL,
  			        `c_l` varchar(10),
  			        `s_c` varchar(10),
  			        `g_r` varchar(10),
  			        `e_j` varchar(10),
   			      	`p` varchar(10),
                                `unit` varchar(10),
  			        `theme` varchar(10),
                                CONSTRAINT `WORD_DETAILS_FK03` FOREIGN KEY (code_id) REFERENCES " . _LEXICON_WORD_CODE . " (id)
                                ON DELETE CASCADE
                                ON UPDATE CASCADE
					) DEFAULT CHARSET=utf8; ";
    // lexicon_languages
    $sqls[] = "CREATE TABLE `" . _LEXICON_LANGUAGES . "`(
					`id` varchar(4) NOT NULL DEFAULT '',
					`Part2B` varchar(10) NOT NULL DEFAULT '',
					`Part2T` varchar(10) NOT NULL DEFAULT '',		
					`Part1` varchar(10) NOT NULL DEFAULT '',
					`Scope` varchar(10) NOT NULL DEFAULT '',
					`Language_Type` varchar(10) NOT NULL DEFAULT '',		
					`Ref_Name` varchar(50) NOT NULL DEFAULT '',
                                        `Comment` varchar(100) NOT NULL DEFAULT '',
                                        `Status` varchar(100) NOT NULL DEFAULT 'inactive'
					) DEFAULT CHARSET=utf8; ";
    // lexicon_word_categories
    $sqls[] = "CREATE TABLE `" . _LEXICON_WORD_CATEGORIES . "`(
					`id` int unsigned NOT NULL auto_increment PRIMARY KEY,
					`t_n` varchar(16) NOT NULL DEFAULT '',
					`c_l` varchar(10),
                                        `s_c` varchar(10),
                                        `g_r` varchar(10),
                                        `e_j` varchar(10),
                                        `cat_eng` varchar(30) NOT NULL DEFAULT '',
                                        `cat_esp` varchar(30) NOT NULL DEFAULT ''
					) DEFAULT CHARSET=utf8; ";
    $error = false;
    $wpdb->query('START TRANSACTION');
    foreach ($sqls as $sql) {

        if (!$wpdb->query($sql)) {
            $error = $wpdb->print_error();
            break;
        };
    }

    if (!$error) {
        $wpdb->query('COMMIT');
        lexicon_load_all_lang();  /* Execute function to import all languages in the database.
                                    (The file lexicon_all_langs.csv inside the folder all_languages fills up the table _LEXICON_LANGUAGES) */
        lexicon_load_word_categories(); /*Execute function to import english and spanish word categories in the database.
                                    (The file cod_cat_final.csv inside the folder all_languages fills up the table _LEXICON_LANGUAGES)*/
        add_option("lexicon_db_version", $lexicon_db_version);
    } else {
        $wpdb->query('ROLLBACK');
        echo $error;
    }
} else {
    // DB version outdated

    $sqls = Array();
    //$sqls[] = "SET foreign_key_checks = 0";
    //lexicon_course
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE . "` DROP PRIMARY KEY";
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE . "` 
					MODIFY `id` int unsigned NOT NULL auto_increment,
					MODIFY `lang_1` varchar(30) NOT NULL DEFAULT '',
					MODIFY `lang_2` varchar(30) NOT NULL DEFAULT '',		
					MODIFY `level` varchar(10) NOT NULL DEFAULT '',
					MODIFY `description` varchar(255),
					ADD PRIMARY KEY (id)";
    //lexicon_course_student
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE_STUDENT . "` DROP PRIMARY KEY, DROP FOREIGN KEY `COURSE_STUDENT_FK01`";
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE_STUDENT . "`
					MODIFY `student_id` bigint(20) NOT NULL DEFAULT 0,
					MODIFY `course_id` int unsigned NOT NULL DEFAULT 0,
					MODIFY `state` int unsigned NOT NULL DEFAULT 0,
                                        ADD PRIMARY KEY (student_id, course_id),
					CONSTRAINT `COURSE_STUDENT_FK01` FOREIGN KEY (course_id) REFERENCES " . _LEXICON_COURSE . " (id)
                                        ON DELETE CASCADE
                                        ON UPDATE CASCADE";
    // lexicon_course_student_card
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE_SUTDENT_CARD . "` DROP FOREIGN KEY `COURSE_STUDENT_CARD FK01`, DROP FOREIGN KEY `COURSE_STUDENT_CARD FK02`";
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE_SUTDENT_CARD . "`
					MODIFY `student_id` bigint(20) NOT NULL DEFAULT 0,
					MODIFY `course_id` int unsigned NOT NULL DEFAULT 0,
					MODIFY `code` varchar(16) NOT NULL DEFAULT '',
					MODIFY `prog_level` int unsigned NOT NULL DEFAULT 0,
					CONSTRAINT `COURSE_STUDENT_CARD_FK01` FOREIGN KEY (student_id) REFERENCES " . _LEXICON_COURSE_STUDENT . " (student_id)
                                        ON DELETE CASCADE
                                        ON UPDATE CASCADE,
					CONSTRAINT `COURSE_STUDENT_CARD_FK02` FOREIGN KEY (course_id) REFERENCES " . _LEXICON_COURSE . " (id)
                                        ON DELETE CASCADE
                                        ON UPDATE CASCADE";
    // lexicon_course_author
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE_AUTHOR . "` DROP PRIMARY KEY";
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE_AUTHOR . "`
					MODIFY `teacher_id` bigint(20) NOT NULL DEFAULT 0,
					MODIFY `course_id` int unsigned NOT NULL DEFAULT 0,								
					ADD PRIMARY KEY (teacher_id, course_id)";
    // lexicon_course_codes
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE_CODES . "` DROP PRIMARY KEY";
    $sqls[] = "ALTER TABLE `" . _LEXICON_COURSE_CODES . "`
					MODIFY `course_id` int unsigned NOT NULL DEFAULT 0,
					MODIFY `code` varchar(16) NOT NULL DEFAULT '',
                                        MODIFY `context` varchar(120),
					ADD PRIMARY KEY (course_id, code)";
    // lexicon_words
    $sqls[] = "ALTER TABLE `" . _LEXICON_WORDS . "` DROP PRIMARY KEY";
    $sqls[] = "ALTER TABLE `" . _LEXICON_WORDS . "`
					MODIFY `id` int unsigned NOT NULL auto_increment,
					MODIFY `code` varchar(16) NOT NULL DEFAULT '',
					MODIFY `text` varchar(30) NOT NULL DEFAULT '',
					MODIFY `phrase` varchar(120),
                                        MODIFY `context` varchar(120),
					MODIFY `level` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_6` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_7` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_8` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_9` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_10` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_11` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_12` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_13` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_14` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_15` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `column_16` varchar(10) NOT NULL DEFAULT '',
                                        MODIFY `lang` varchar(30) NOT NULL DEFAULT '',
				  	ADD PRIMARY KEY (id)";
    // lexicon_word_code
    $sqls[] = "ALTER TABLE `" . _LEXICON_WORD_CODE . "` DROP PRIMARY KEY";
    $sqls[] = "ALTER TABLE `" . _LEXICON_WORD_CODE . "`
                                MODIFY `id` int unsigned NOT NULL,
                                MODIFY `code` varchar(16) NOT NULL DEFAULT '',
  			        MODIFY `level` varchar(10) NOT NULL DEFAULT '',
                                MODIFY `t_n` varchar(16) NOT NULL DEFAULT '',
                                MODIFY `word_coexist` varchar(80) NOT NULL DEFAULT '',
				ADD PRIMARY KEY (id)";
    // lexicon_word_details
    $sqls[] = "ALTER TABLE `" . _LEXICON_WORD_DETAILS . "` DROP FOREIGN KEY `WORD_DETAILS FK03`";
    $sqls[] = "ALTER TABLE `" . _LEXICON_WORD_DETAILS . "`
                                MODIFY `code_id` int unsigned NOT NULL,
  			        MODIFY `c_l` varchar(10),
  			        MODIFY `s_c` varchar(10),
  			        MODIFY `g_r` varchar(10),
  			        MODIFY `e_j` varchar(10),
   			      	MODIFY `p` varchar(10),
                                MODIFY `unit` varchar(10),
  			        MODIFY `theme` varchar(10),
                                DROP FOREIGN KEY `WORD_DETAILS FK03`,
                                CONSTRAINT `WORD_DETAILS_FK03` FOREIGN KEY (code_id) REFERENCES " . _LEXICON_WORD_CODE . " (id)
                                ON DELETE CASCADE
                                ON UPDATE CASCADE";
    //lexicon_course
    $sqls[] = "ALTER TABLE `" . _LEXICON_LANGUAGES . "` 
					MODIFY `id` varchar(4) NOT NULL,
					MODIFY `Part2B` varchar(10) NOT NULL DEFAULT '',
					MODIFY `Part2T` varchar(10) NOT NULL DEFAULT '',		
					MODIFY `Part1` varchar(10) NOT NULL DEFAULT '',
					MODIFY `Scope` varchar(10) NOT NULL DEFAULT '',
					MODIFY `Language_Type` varchar(10) NOT NULL DEFAULT '',		
					MODIFY `Ref_Name` varchar(50) NOT NULL DEFAULT '',
                                        MODIFY `Comment` varchar(100) NOT NULL DEFAULT ''";
    //lexicon_word_categories
    $sqls[] = "ALTER TABLE `" . _LEXICON_WORD_CATEGORIES . "` DROP PRIMARY KEY";
    $sqls[] = "ALTER TABLE `" . _LEXICON_WORD_CATEGORIES . "` 
                                        MODIFY `id` int unsigned NOT NULL auto_increment,
					MODIFY `t_n` varchar(16) NOT NULL DEFAULT '',
					MODIFY `c_l` varchar(10),
                                        MODIFY `s_c` varchar(10),
                                        MODIFY `g_r` varchar(10),
                                        MODIFY `e_j` varchar(10),
                                        MODIFY `cat_eng` varchar(30) NOT NULL DEFAULT '',
                                        MODIFY `cat_esp` varchar(30) NOT NULL DEFAULT '',
                                        ADD PRIMARY KEY (id)";
    //$sqls[] = "SET foreign_key_checks = 1";

    $error = false;
    $wpdb->query('START TRANSACTION');
    foreach ($sqls as $sql) {

        if (!$wpdb->query($sql)) {
            $error = $wpdb->print_error();
            break;
        };
    }

    if ($error) {
        $wpdb->query('COMMIT');
        update_option("lexicon_db_version", $lexicon_db_version);
    } else {
        $wpdb->query('ROLLBACK');
        echo $error;
    }
}

if (get_page_by_title('LEXICON') == null) {
    $post = array();
    $post['post_title'] = 'LEXICON';
    $post['post_type'] = 'page';
    $post['post_content'] = '[lexicon]';
    $post['post_status'] = 'publish';
    $post['post_author'] = 1;
    $post['comment_status'] = 'closed';
    $post['ping_satuts'] = 'closed';
    $post['page_template'] = 'front-page.php';
    wp_insert_post($post);
}
/*
 * 	Lexicon Options
 */

if (get_option("lexicon_install") == "") {
    add_option("lexicon_install", '1');
} else {
    update_option("lexicon_install", '1');
}
if (get_option("lexicon_clear_data_deactive") == "") {
    add_option("lexicon_clear_data_deactive", '1');
} else {
    update_option("lexicon_clear_data_deactive", '1');
}
if (get_option("lexicon_cleanup_db") == "") {
    add_option("lexicon_cleanup_db", '1');
} else {
    update_option("lexicon_cleanup_db", '1');
}

$lex_userId = get_current_user_id();
add_user_meta($lex_userId, "primaryLang", "eng"); /* Set a use meta key calles primaryLang with value = eng. 
                                                    We need it to save the plugin's primary language to use in the plugin settings page */
?>
