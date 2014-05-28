<?php
        include "vars.php";
        $PAGE_TITLE="Upgrading";
        include "common-page-head.php";
?>

            <h2>Upgrading From Previous Versions</h2>

            <p><strong>Make a backup of your working weathermap directory before you do
            this!</strong> Just in case you <em>do</em> need to roll back.</p>

            <p>You should be able to upgrade from any previous version by simply
            unpacking the new one over the top. The files that will need to be changed
            afterwards are the same ones you edited when you first installed:

            <ul>
                <li>If you use the editor, then copy your editor-config.php out of the
                way, copy the new editor-config.php-dist over the top of the in-place
                editor-config.php and then make the same changes you made in your
                original install (cacti path and URI).
                <b>This is especially important when upgrading to 0.92</b></li>

                <li>If you use the command-line tool, you will need to put the path to
                rrdtool back in, around line 30 of the 'weathermap' file.</li>
            </ul></p>

            <p>Any necessary database updates for Cacti users should be taken care of
            automatically.</p>

<?php
        include "common-page-foot.php";
