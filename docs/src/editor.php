<?php
        include "vars.php";
        $PAGE_TITLE="The Map Editor";
        $PATH_EXTRA="../";
        include "common-page-head.php";
?>
            <h2 id = "editor">The Map Editor</h2>

            <p class = "notebox"><b>Note:</b> This section is basically unaltered from
            the v0.7 manual. The editor hasn't really changed since then, except to fix
            some bugs, and work with the new config directives
            - that is to say, it won't edit them, but nor will it remove/damage them.
            It's still true to say that the editor is the easiest way to initially lay
            out your map, and pick your TARGETs if you use Cacti, but after that, some
            hand-editing will help with cosmetics.
            </p>

            <p>New in version 0.7, there is a partially-complete interactive editor
            included. In this release, it allows for visual layout of nodes and links,
            modification of most parameters, and some integration with Cacti for picking
            data-sources.
            <strong>It is not integrated into Cacti's management pages, OR access
            control.</strong></p>

            <p>To use the editor, you need to make a few extra considerations beyond
            those needed by the command-line weathermap software, as detailed in the
            <a href = "main.html#install_standaloneeditor">Installation Guide</a>.
            </p>

            <p>
            Once you have all that taken care of, put a copy of one of your
            configuration files into the configs directory you just created, and then go
            to: <tt>http://www.your.web.server/cacti/php-weathermap/editor.php</tt> (or
            whereever you put it). You should get a menu to either create a map, or open
            the ones you just put in that directory.
            </p>

            <p>
            Hopefully, the actual editor is fairly self-explanatory.

            <ul>
                <li>You can click on any existing node or link to get it's properties
                and change them. </li><li>You can delete a node or link from the
                properties box. For nodes, you can move them from the properties box
                too. </li><li>You can move the key or timestamp by clicking on them,
                also. </li><li>If you set up the Cacti-related options in the
                editor-config.php file, then you should also see an additional option to
                pick a data source directly from Cacti, in the Link Properties
                box.</li><li> To create a new node, choose the 'Add Node' button at the
                top and then click on the map where you want the node to be.</li><li> To
                create a new link, choose the 'Add Link' button then click on the two
                nodes to link together, in turn. There are separate buttons at the top
                for changing various global parameters. </li>

                <li>There is no save
                - Every change is written back to the configuration file immediately.
                Make a backup copy if you feel the need to.</li>
            </ul>
            </p>

            <p>
            There are a number of things <i>not</i> editable in the editor currently:

            <ul>
                <li>background images and icons
                - it's intended that the editor will allow you to upload image files to
                the server, which will then appear in a list where appropriate.</li>

                <li>colours
                - there's no way to pick colours currently, either for the map elements
                or for the scale.</li>

                <li>defaults
                - the editor lags behind the current command-line software here. It
                generates 'old-style' global options for some features where the correct
                thing to do is make changes to the DEFAULT link and node definitions.
                Similarly, there is no per-node or per-link adjustment of things like
                font, label style or label offset.</li>

                <li>NODE TARGETs</li>

                <li>Multiple SCALEs</li>

                <li>Curves</li>

                <li>Link offsets or Label offsets</li>
            </ul>

            Luckily, the editor shouldn't damage any of these things that you put into
            the configuration file by hand, so it's safe to do some work in the editor
            where it's quicker or more intuitive, and then fine-tune the file by hand.
            </p>

<?php
        include "common-page-foot.php";
