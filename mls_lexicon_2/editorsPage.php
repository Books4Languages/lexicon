<div id="allWordsTable">
    <?php
    for ($i = -1; $i < fetchFullLangWordDetails(-1, 'countArray'); $i++) { //Get the number of iterations
        if ($i !== -1) {    // Echo words

            echo '<div id="wordEditBox' . fetchFullLangWordDetails($i, 'all', 'none') . '" style="padding:16px; border-top: 1px solid rgba(168,151,145,0.3); display: inline-block; width: 90%">';
            echo '<div style="display: inline-flex; width: 90%">';
            
            echo '<div style="width: 30%; padding: 5px 5px 5px 5px; border-right: 1px solid rgba(168,151,145,0.3);"><h3>' . fetchFullLangWordDetails($i, 'prim', ' word') . '</h3><br/>' . fetchFullLangWordDetails($i, 'prim', ' phrase') . '</div>';
            echo '<div style="width: 30%; padding: 5px 5px 5px 5px; border-right: 1px solid rgba(168,151,145,0.3);"><h3>' . fetchFullLangWordDetails($i, 'sec', ' word') . '</h3><br/>' . fetchFullLangWordDetails($i, 'sec', ' phrase') . '</div>';
            echo '<div style="width: 30%; padding: 5px 5px 5px 5px; border-right: 1px solid rgba(168,151,145,0.3);"><h3>' . fetchFullLangWordDetails($i, 'add', ' word') . '</h3><br/>' . fetchFullLangWordDetails($i, 'add', ' phrase') . '</div>';

            ?>
    <form id="wordEditForm<?php echo fetchFullLangWordDetails($i, 'all', 'none') ?>" data-url="<?php echo admin_url('admin-ajax.php') ?>">
            <input type="hidden" name="wordIdToEdit" value="<?php echo fetchFullLangWordDetails($i, 'all', 'none') ?>"/>
            <input type="hidden" name="action" value="editor_changing"/>
    </form>
            <input type="button" class="button-primary" style="align-self: end;" value="Edit" id="editButton<?php echo fetchFullLangWordDetails($i, 'all', 'none') ?>" onclick="editCurrentWord(this.id)"/>
            <?php
            echo '</div>';
            echo '</div>';

            echo '<br/><br/>';
        } else if ($i === -1) { // Echo languages (Titles on top -- English ... ... )
            echo '<div id="langTitlesBox" style="padding:16px; border-top: 1px solid rgba(168,151,145,0.3); display: inline-block; width: 90%">';
            echo '<div style="display: inline-flex; width: 90%">';

            echo '<div style="width: 30%; text-align: center; padding: 5px 5px 5px 5px; border-right: 1px solid rgba(168,151,145,0.3);"><h2>' . fetchFullLangWordDetails($i, 'prim') . '</h2></div>';
            echo '<div style="width: 30%; text-align: center; padding: 5px 5px 5px 5px; border-right: 1px solid rgba(168,151,145,0.3);"><h2>' . fetchFullLangWordDetails($i, 'sec') . '</h2></div>';
            echo '<div style="width: 30%; text-align: center; padding: 5px 5px 5px 5px; border-right: 1px solid rgba(168,151,145,0.3);"><h2>' . fetchFullLangWordDetails($i, 'add') . '</h2></div><br/>';

            echo '</div>';
            echo '</div>';
        }
    }
    ?>

</div>

<?php
