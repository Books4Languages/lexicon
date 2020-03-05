<?php
global $wpdb;
?>

<br />

<?php
if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['formSubmit'])) {  //If we click on the submit button
    if (isset($_POST['language'])) {    //Check if languages are checked
        $lexicon_lang = $_POST['language'];
    } else {
        $lexicon_lang = "";
    }
    if (empty($lexicon_lang)) { //If empty echo message
        echo("You didn't select any language.");
    } else { //Else for each language selected, add it in the DB
        $N = count($lexicon_lang);

        //echo("You selected $N language(s): ");
        //var_dump($lexicon_lang);
        foreach ($lexicon_lang as $langToAdd) {
            lexicon_add_language_inDB($langToAdd, "no");
        }
        echo "All $N languages created successfully!";
        wp_redirect(esc_url_raw(add_query_arg(array('page' => 'lexicon_testing'), admin_url('admin.php')))); // Redirect int he end
    }
} else {    // Page shown when not clicked on the submit button
    $allLanguages = $wpdb->get_results("SELECT * FROM " . _LEXICON_LANGUAGES . " WHERE Part1 <> '' AND Language_Type = 'L' AND Status = 'inactive';");
    $counter = 0;
    //  Get all languages that are not active
    ?>
    <table>
        <?php
        foreach ($allLanguages as $language) { // For each not active language, echo a check box and there name
            $languageId = $language->id;
            $languageRefName = $language->Ref_Name;
            $langRefNameBrackCheck = strpos($languageRefName, "(");
            if (!$langRefNameBrackCheck) {
                $langRefName = $languageRefName;
            } else {
                $langRefName = substr($languageRefName, 0, strpos($languageRefName, "(") - 1);
            }
            if ($counter <= 5) {
                ?>
                <td><input type="checkbox" name="language[]" value="<?php echo $languageId ?>" /></td>
                <td><span style=";width: 120px;"><?php echo $langRefName ?></span></td>


                <?php
                $counter++;
            } else {
                $counter = 0;
                ?>
                <tr></tr>
                <tr></tr>
                <?php
            }
        }
        ?>
    </table>
    <br/>
    <br/>
    <div style="display: flex">
        <input class="button-primary" type="submit" onclick="applyLoader()" name="formSubmit" value="Submit" />
        <span style="width: 10px"></span>
        <div id="updateLoader" class="loader"></div>
    </div>

    <?php
}
