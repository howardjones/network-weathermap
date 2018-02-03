<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>

    <link rel="stylesheet" type="text/css" media="screen" href="editor-resources/oldeditor.css"/>
    <script type="text/javascript" src="vendor/jquery/dist/jquery.min.js"></script>
    <script src="editor-resources/editor.js" type="text/javascript"></script>
    <script src="editor-resources/editor16.js" type="text/javascript"></script>
    <script type="text/javascript">
        <?php echo $map_json; ?>
        <?php echo $images_json; ?>
        <?php echo $fonts_json; ?>
        <?php echo $editor_settings; ?>

        var global_settings = <?php echo $global; ?>;
        var global_settings2 = <?php echo $global2; ?>;
        var editor_url = '<?php echo $editor_name; ?>';
        var fromplug = <?php echo $fromplug ?>;
        var host_url = '<?php echo $host_url ?>';
        var host_type = '<?php echo $host_type ?>';
    </script>
    <style type="text/css">
        <?php
        // if the host is cacti, we can unhide the cacti links in the Link Properties box
        if (!$fromplug || ($fromplug && $host_type!='cacti')) {
            echo "    .cactilink { display: none; }\n";
            echo "    .cactinode { display: none; }\n";
        }
        ?>
    </style>
    <title>PHP Weathermap Editor <?php echo $WEATHERMAP_VERSION; ?></title>
</head>

<body id="mainview">
<div id="toolbar">
    <ul>
        <li class="tb_active" id="tb_newfile">Change<br/>File</li>
        <li class="tb_active" id="tb_addnode">Add<br/>Node</li>
        <li class="tb_active" id="tb_addlink">Add<br/>Link</li>
        <li class="tb_active" id="tb_poslegend">Position<br/>Legend</li>
        <li class="tb_active" id="tb_postime">Position<br/>Timestamp</li>
        <li class="tb_active" id="tb_mapprops">Map<br/>Properties</li>
        <li class="tb_active" id="tb_mapstyle">Map<br/>Style</li>
        <li class="tb_active" id="tb_colours">Manage<br/>Colors</li>
        <li class="tb_active" id="tb_manageimages">Manage<br/>Images</li>
        <li class="tb_active" id="tb_prefs">Editor<br/>Settings</li>
        <li class="tb_coords" id="tb_coords">Position<br/>---, ---</li>
        <li class="tb_help"><span id="tb_help">or click a Node or Link to edit it's properties</span></li>
    </ul>
</div>
<form action="<?php echo $editor_name ?>" method="post" name="frmMain">
    <div align="center" id="mainarea">
        <input type="hidden" name="plug" value="<?php echo($fromplug == true ? 1 : 0) ?>"/>
        <input style="display:none" type="image"
               src="<?php echo $imageurl; ?>" id="xycapture"/><img src=
                                                                   "<?php echo $imageurl; ?>" id="existingdata"
                                                                   alt="Weathermap" usemap="#weathermap_imap"
        />
        <div class="debug"><p><strong>Debug:</strong>
                <a href="?<?php echo($fromplug == true ? 'plug=1&amp;' : ''); ?>action=tidy_all&amp;mapname=<?php echo htmlspecialchars($mapname) ?>">Tidy
                    ALL</a>
                <a href="?<?php echo($fromplug == true ? 'plug=1&amp;' : ''); ?>action=retidy&amp;mapname=<?php echo htmlspecialchars($mapname) ?>">Re-tidy</a>
                <a href="?<?php echo($fromplug == true ? 'plug=1&amp;' : ''); ?>action=untidy&amp;mapname=<?php echo htmlspecialchars($mapname) ?>">Un-tidy</a>
                <a href="?<?php echo($fromplug == true ? 'plug=1&amp;' : ''); ?>action=nothing&amp;mapname=<?php echo htmlspecialchars($mapname) ?>">Do
                    Nothing</a>
                <span><label for="mapname">mapfile</label><input type="text" name="mapname"
                                                                 value="<?php echo htmlspecialchars($mapname); ?>"/></span>
                <span><label for="action">action</label><input type="text" id="action" name="action"
                                                               value="<?php echo htmlspecialchars($newaction); ?>"/></span>
                <span><label for="param">param</label><input type="text" name="param" id="param" value=""/></span>
                <span><label for="param2">param2</label><input type="text" name="param2" id="param2"
                                                               value="<?php echo htmlspecialchars($param2); ?>"/></span>
                <span><label for="debug">debug</label><input id="debug" value="" name="debug"/></span>
                <a target="configwindow"
                   href="?<?php echo($fromplug == true ? 'plug=1&amp;' : ''); ?>action=show_config&amp;mapname=<?php echo urlencode($mapname) ?>">See
                    config</a></p>
            <pre><?php echo htmlspecialchars($log) ?></pre>
            <pre><?php echo htmlspecialchars($internal) ?></pre>
        </div>
        <?php echo $imagemap ?>
    </div><!-- Node Properties -->

    <div id="dlgNodeProperties" class="dlgProperties">
        <div class="dlgTitlebar">
            Node Properties
            <input size="6" name="node_name" type="hidden"/>
            <ul>
                <li><a id="tb_node_submit" class="wm_submit" title="Submit any changes made">Submit</a></li>
                <li><a id="tb_node_cancel" class="wm_cancel" title="Cancel any changes">Cancel</a></li>
            </ul>
        </div>

        <div class="dlgBody">
            <table>
                <tr>
                    <th>Position</th>
                    <td><input id="node_x" name="node_x" size=4 type="text"/>,<input id="node_y" name="node_y" size=4
                                                                                     type="text"/>
                        <span id="node_locktext">
                                <br/>Lock to:
                                <select name="node_lock_to" id="node_lock_to">
                                    <option value="">-- NONE --</option>
                                    <?php echo $nodeselection ?>
                                </select>
                            </span>

                    </td>
                </tr>
                <tr>
                    <th>Internal Name</th>
                    <td><input id="node_new_name" name="node_new_name" type="text"/></td>
                </tr>
                <tr>
                    <th>Label</th>
                    <td><input id="node_label" name="node_label" type="text"/></td>
                </tr>
                <tr>
                    <th>Info URL</th>
                    <td><input id="node_infourl" name="node_infourl" type="text"/></td>
                </tr>
                <tr>
                    <th>'Hover' Graph URL</th>
                    <td><input id="node_hover" name="node_hover" type="text"/>
                        <span class="cactinode"><a id="node_cactipick">[Pick from Cacti]</a></span></td>
                </tr>
                <tr>
                    <th>Icon Filename</th>
                    <td><select id="node_iconfilename" name="node_iconfilename" class="imlist">
                            <option value="--NONE--">--NO ICON--</option>
                            <option value="--AICON--">--ARTIFICIAL ICON--</option>
                        </select></td>
                </tr>
                <tr>
                    <th></th>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <th></th>
                    <td><a id="node_move" class="dlgTitlebar">Move</a><a class="dlgTitlebar" id="node_delete">Delete</a><a
                                class="dlgTitlebar" id="node_clone">Clone</a><a class="dlgTitlebar"
                                                                                id="node_edit">Edit</a></td>
                </tr>
            </table>
        </div>

        <div class="dlgHelp" id="node_help">
            Helpful text will appear here, depending on the current
            item selected. It should wrap onto several lines, if it's
            necessary for it to do that.
        </div>
    </div><!-- Node Properties -->


    <!-- Link Properties -->

    <div id="dlgLinkProperties" class="dlgProperties">
        <div class="dlgTitlebar">
            Link Properties

            <ul>
                <li><a title="Submit any changes made" class="wm_submit" id="tb_link_submit">Submit</a></li>
                <li><a title="Cancel any changes" class="wm_cancel" id="tb_link_cancel">Cancel</a></li>
            </ul>
        </div>

        <div class="dlgBody">
            <div class="comment">
                Link from '<span id="link_nodename1">%NODE1%</span>' to '<span id="link_nodename2">%NODE2%</span>'
            </div>

            <input size="6" name="link_name" type="hidden"/>

            <table width="100%">
                <tr>
                    <th>Maximum Bandwidth<br/>
                        Into '<span id="link_nodename1a">%NODE1%</span>'
                    </th>
                    <td><input size="8" id="link_bandwidth_in" name="link_bandwidth_in" type=
                        "text"/> bits/sec
                    </td>
                </tr>
                <tr>
                    <th>Maximum Bandwidth<br/>
                        Out of '<span id="link_nodename1b">%NODE1%</span>'
                    </th>
                    <td><input type="checkbox" id="link_bandwidth_out_cb" name=
                        "link_bandwidth_out_cb" value="symmetric"/>Same As
                        'In' or <input id="link_bandwidth_out" name="link_bandwidth_out"
                                       size="8" type="text"/> bits/sec
                    </td>
                </tr>
                <tr>
                    <th>Data Source</th>
                    <td><input id="link_target" name="link_target" type="text"/> <span class="cactilink"><a
                                    id="link_cactipick">[Pick from Cacti]</a></span></td>
                </tr>
                <tr>
                    <th>Link Width</th>
                    <td><input id="link_width" name="link_width" size="3" type="text"/>
                        pixels
                    </td>
                </tr>
                <tr>
                    <th>Info URL</th>
                    <td><input id="link_infourl" size="30" name="link_infourl" type="text"/></td>
                </tr>
                <tr>
                    <th>'Hover' Graph URL</th>
                    <td><input id="link_hover" size="30" name="link_hover" type="text"/></td>
                </tr>


                <tr>
                    <th>IN Comment</th>
                    <td><input id="link_commentin" size="25" name="link_commentin" type="text"/>
                        <select id="link_commentposin" name="link_commentposin">
                            <option value=95>95%</option>
                            <option value=90>90%</option>
                            <option value=80>80%</option>
                            <option value=70>70%</option>
                            <option value=60>60%</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>OUT Comment</th>
                    <td><input id="link_commentout" size="25" name="link_commentout" type="text"/>
                        <select id="link_commentposout" name="link_commentposout">
                            <option value=5>5%</option>
                            <option value=10>10%</option>
                            <option value=20>20%</option>
                            <option value=30>30%</option>
                            <option value=40>40%</option>
                            <option value=50>50%</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th></th>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <th></th>
                    <td><a class="dlgTitlebar" id="link_delete">Delete
                            Link</a><a class="dlgTitlebar" id="link_edit">Edit</a><a
                                class="dlgTitlebar" id="link_tidy">Tidy</a><a
                                class="dlgTitlebar" id="link_via">Via</a><a
                                class="dlgTitlebar" id="link_straight">Straight</a>
                    </td>
                </tr>
            </table>
        </div>

        <div class="dlgHelp" id="link_help">
            Helpful text will appear here, depending on the current
            item selected. It should wrap onto several lines, if it's
            necessary for it to do that.
        </div>
    </div><!-- Link Properties -->

    <!-- Map Properties -->

    <div id="dlgMapProperties" class="dlgProperties">
        <div class="dlgTitlebar">
            Map Properties

            <ul>
                <li><a title="Submit any changes made" class="wm_submit" id="tb_map_submit">Submit</a></li>
                <li><a title="Cancel any changes" class="wm_cancel" id="tb_map_cancel">Cancel</a></li>
            </ul>
        </div>

        <div class="dlgBody">
            <table>
                <tr>
                    <th>Map Title</th>
                    <td><input id="map_title" name="map_title" size="25" type="text"
                        /></td>
                </tr>
                <tr>
                    <th>Legend Text</th>
                    <td><input id="map_legend" name="map_legend" size="25" type="text"
                        /></td>
                </tr>
                <tr>
                    <th>Timestamp Text</th>
                    <td><input id="map_stamp" name="map_stamp" size="25" type="text"
                        /></td>
                </tr>

                <tr>
                    <th>Default Link Width</th>
                    <td><input id="map_linkdefaultwidth" name="map_linkdefaultwidth" size="6" type="text"
                        /> pixels
                    </td>
                </tr>

                <tr>
                    <th>Default Link Bandwidth</th>
                    <td><input id="map_linkdefaultbwin" name="map_linkdefaultbwin" size="6" type="text"
                        />
                        bit/sec in, <input id="map_linkdefaultbwout" name="map_linkdefaultbwout" size="6" type="text"
                        />
                        bit/sec out
                    </td>
                </tr>


                <tr>
                    <th>Map Size</th>
                    <td><input id="map_width" name="map_width" size="5" type=
                        "text"/> x <input id="map_height" name="map_height" size="5"
                                          type=
                                          "text"
                        />
                        pixels
                    </td>
                </tr>
                <tr>
                    <th>Output Image Filename</th>
                    <td><input id="map_pngfile" name="map_pngfile" type="text"
                        /></td>
                </tr>
                <tr>
                    <th>Output HTML Filename</th>
                    <td><input id="map_htmlfile" name="map_htmlfile" type="text"
                        /></td>
                </tr>
                <tr>
                    <th>Background Image Filename</th>
                    <td><select id="map_bgfile" name="map_bgfile" class="imlist"></select></td>
                </tr>

            </table>
        </div>

        <div class="dlgHelp" id="map_help">
            Helpful text will appear here, depending on the current
            item selected. It should wrap onto several lines, if it's
            necessary for it to do that.
        </div>
    </div><!-- Map Properties -->

    <!-- Map Style -->
    <div id="dlgMapStyle" class="dlgProperties">
        <div class="dlgTitlebar">
            Map Style

            <ul>
                <li><a title="Submit any changes made" id="tb_mapstyle_submit" class="wm_submit">Submit</a></li>
                <li><a title="Cancel any changes" class="wm_cancel" id="tb_mapstyle_cancel">Cancel</a></li>
            </ul>
        </div>

        <div class="dlgBody">
            <table>
                <tr>
                    <th>Link Labels</th>
                    <td><select id="mapstyle_linklabels" name="mapstyle_linklabels">
                            <option
                                    value="bits">Bits/sec
                            </option>
                            <option
                                    value="percent">Percentage
                            </option>
                            <option
                                    value="none">None
                            </option>
                        </select></td>
                </tr>
                <tr>
                    <th>HTML Style</th>
                    <td><select name="mapstyle_htmlstyle">
                            <option value="overlib">
                                Overlib (DHTML)
                            </option>
                            <option value="static">Static
                                HTML
                            </option>
                        </select></td>
                </tr>
                <tr>
                    <th>Arrow Style</th>
                    <td><select name="mapstyle_arrowstyle">
                            <option
                                    value="classic">Classic
                            </option>
                            <option
                                    value="compact">Compact
                            </option>
                        </select></td>
                </tr>
                <tr>
                    <th>Node Font</th>
                    <td>
                        <select name="mapstyle_nodefont" id="mapstyle_nodefont" class="fontlist"></select>
                    </td>
                </tr>
                <tr>
                    <th>Link Label Font</th>
                    <td>
                        <select id="mapstyle_linkfont" name="mapstyle_linkfont" class="fontlist"></select></td>
                </tr>
                <tr>
                    <th>Legend Font</th>
                    <td>
                        <select id="mapstyle_legendfont" name="mapstyle_legendfont" class="fontlist"></select></td>
                </tr>
                <tr>
                    <th>Font Samples:</th>
                    <td>
                        <div class="fontsamples"><img alt="Sample of defined fonts"
                                                      src="?action=font_samples&mapname=<?php echo $mapname ?>"/></div>
                        <br/>(Drawn using your PHP install)
                    </td>
                </tr>
            </table>
        </div>

        <div class="dlgHelp" id="mapstyle_help">
            Helpful text will appear here, depending on the current
            item selected. It should wrap onto several lines, if it's
            necessary for it to do that.
        </div>
    </div><!-- Map Style -->


    <!-- Colours -->

    <div id="dlgColours" class="dlgProperties">
        <div class="dlgTitlebar">
            Manage Colors

            <ul>
                <li><a title="Submit any changes made" id="tb_colours_submit" class="wm_submit">Submit</a></li>
                <li><a title="Cancel any changes" class="wm_cancel" id="tb_colours_cancel">Cancel</a></li>
            </ul>
        </div>

        <div class="dlgBody">
            Nothing in here works yet. The aim is to have a nice color picker somehow.
            <table>
                <tr>
                    <th>Background Color</th>
                    <td></td>
                </tr>

                <tr>
                    <th>Link Outline Color</th>
                    <td></td>
                </tr>
                <tr>
                    <th>Scale Colors</th>
                    <td>Some pleasant way to design the bandwidth color scale goes in here???</td>
                </tr>

            </table>
        </div>

        <div class="dlgHelp" id="colours_help">
            Helpful text will appear here, depending on the current
            item selected. It should wrap onto several lines, if it's
            necessary for it to do that.
        </div>
    </div><!-- Colours -->


    <!-- Images -->

    <div id="dlgImages" class="dlgProperties">
        <div class="dlgTitlebar">
            Manage Images

            <ul>
                <li><a title="Submit any changes made" id="tb_images_submit" class="wm_submit">Submit</a></li>
                <li><a title="Cancel any changes" class="wm_cancel" id="tb_images_cancel">Cancel</a></li>
            </ul>
        </div>

        <div class="dlgBody">
            <p>Nothing in here works yet. </p>
            The aim is to have some nice way to upload images which can be used as icons or backgrounds.
            These images are what would appear in the dropdown boxes that don't currently do anything in the Node and
            Map Properties dialogs. This may end up being a seperate page rather than a dialog box...
        </div>

        <div class="dlgHelp" id="images_help">
            Helpful text will appear here, depending on the current
            item selected. It should wrap onto several lines, if it's
            necessary for it to do that.
        </div>
    </div><!-- Images -->

    <div id="dlgTextEdit" class="dlgProperties">
        <div class="dlgTitlebar">
            Edit Map Object
            <ul>
                <li><a title="Submit any changes made" id="tb_textedit_submit" class="wm_submit">Submit</a></li>
                <li><a title="Cancel any changes" class="wm_cancel" id="tb_textedit_cancel">Cancel</a></li>
            </ul>
        </div>

        <div class="dlgBody">
            <p>You can edit the map items directly here.</p>
            <textarea wrap="no" id="item_configtext" name="item_configtext" cols=40 rows=15></textarea>
        </div>

        <div class="dlgHelp" id="images_help">
            Helpful text will appear here, depending on the current
            item selected. It should wrap onto several lines, if it's
            necessary for it to do that.
        </div>
    </div><!-- TextEdit -->


    <div id="dlgEditorSettings" class="dlgProperties">
        <div class="dlgTitlebar">
            Editor Settings
            <ul>
                <li><a title="Submit any changes made" id="tb_editorsettings_submit" class="wm_submit">Submit</a></li>
                <li><a title="Cancel any changes" class="wm_cancel" id="tb_editorsettings_cancel">Cancel</a></li>
            </ul>
        </div>

        <div class="dlgBody">
            <table>
                <tr>
                    <th>Show VIAs overlay</th>
                    <td><select id="editorsettings_showvias" name="editorsettings_showvias">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Show Relative Positions overlay</th>
                    <td><select id="editorsettings_showrelative" name="editorsettings_showrelative">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Snap To Grid</th>
                    <td><select id="editorsettings_gridsnap" name="editorsettings_gridsnap">
                            <option value="NO">No</option>
                            <option value="5">5 pixels</option>
                            <option value="10">10 pixels</option>
                            <option value="15">15 pixels</option>
                            <option value="20">20 pixels</option>
                            <option value="50">50 pixels</option>
                            <option value="100">100 pixels</option>
                        </select>
                    </td>
                </tr>
            </table>

        </div>

        <div class="dlgHelp" id="images_help">
            Helpful text will appear here, depending on the current
            item selected. It should wrap onto several lines, if it's
            necessary for it to do that.
        </div>
    </div><!-- TextEdit -->


</form>
</body>
</html>
