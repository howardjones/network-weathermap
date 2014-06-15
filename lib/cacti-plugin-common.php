<?php

function wmGenerateFooterLinks()
{
    global $colors;
    global $WEATHERMAP_VERSION;

    print '<br />';
    html_start_box(
        "<center><a target=\"_blank\" class=\"linkOverDark\" href=\"docs/\">Local Documentation</a> -- "
        . "<a target=\"_blank\" class=\"linkOverDark\" href=\"http://www.network-weathermap.com/\">Weathermap"
        . " Website</a> -- <a target=\"_target\" class=\"linkOverDark\" "
        . "href=\"weathermap-cacti-plugin-editor.php?plug=1\">Weathermap Editor</a> -- "
        . "This is version $WEATHERMAP_VERSION</center>",
        "78%",
        $colors["header"],
        "2",
        "center",
        ""
    );
    html_end_box();
}
