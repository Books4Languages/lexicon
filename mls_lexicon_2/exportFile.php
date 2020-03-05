<?php

/*
 * This file is used to export all the words from the database, to a csv file
 * 
 */

$csv_export = ''; //Initialized variable that will hold all the content to export
$query = "SELECT *
          FROM " . _LEXICON_WORD_CODE . "
          INNER JOIN " . _LEXICON_WORD_DETAILS . " ON " . _LEXICON_WORD_CODE . ".id=" . _LEXICON_WORD_DETAILS . ".code_id";
$result = $wpdb->get_results($query, 'ARRAY_A');  //Get the results of the above query in an ARRAY_A form
$checkFirst = 0; //Counter for the number of columns we will have, since the number varies to how many languages are activated
?>
<div>
    <?php
    global $wpdb;
    $databaseName = $wpdb->dbname;

    $word_details_cols = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS "
            . "WHERE TABLE_SCHEMA='$databaseName' "
            . "AND (TABLE_NAME='" . _LEXICON_WORD_DETAILS . "' OR TABLE_NAME='" . _LEXICON_WORD_CODE . "') AND (COLUMN_NAME NOT IN ('code_id', 'code'));");
    
    /* The above variable holds the result column names from the tables _LEXICON_WORD_DETAILS and _LEXICON_WORD_CODE, except code_id and code*/
    
    for ($counter = 0; $counter <= (count($result) - 1); $counter++) {  /* With this for, we generate the content of the file we want to print */
        $res = $result[$counter];
        if ($checkFirst <= (count($word_details_cols) - 1)) {
            for ($i = 0; $i <= count($word_details_cols) - 1; $i++) {
                $column_nameA = $word_details_cols[$i]->COLUMN_NAME;
                $csv_export .= "$column_nameA";
                if ($i == count($word_details_cols) - 1) {
                    $csv_export .= ',';
                    $csv_export .= '<br/>';
                } else {
                    $csv_export .= ';';
                }
                $checkFirst++;
            }
        }

        for ($i = 0; $i <= count($word_details_cols) - 1; $i++) {
            $column_nameA = $word_details_cols[$i]->COLUMN_NAME;
            $csv_export_temp = $res["$column_nameA"];
            $csv_export .= "$csv_export_temp";
            if ($i == count($word_details_cols) - 1) {
                $csv_export .= ',';
                $csv_export .= '<br/>';
            } else {
                $csv_export .= ';';
            }
        }
    }

    ?>
    

</div>
<div style="display: none" id="data_to_export"><?php echo $csv_export ?></div>
<a href= "#" class="button-primary" onClick="export_data_to_CSV('<?php echo count($result) ?>');">Export</a> <!-- THE REST ARE HANDLED BY THE JavaScript FUNCTION  -->
<?php
