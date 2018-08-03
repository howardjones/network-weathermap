<?php include "header.php"; ?>

<body id="mainview">
<script type="text/javascript">
    <?php echo $map_json; ?>
    <?php echo $images_json; ?>
    var fromplug = <?php echo $fromplug ?>;
</script>
<div id="wrap">
    <div class="navbar navbar-default navbar-fixed-top" id="topmenu">
        <div class="navbar-header">
            <a class="brand" href="#">WMEditor</a>
        </div>
        <div class="navbar-header">
            <ul class="nav navbar-nav">
                <li><a href="#" id="tb_newfile"><?php if ($fromplug): ?>
                    Return to<br />Cacti
                <?php else: ?>
                    Change<br/> File
                <?php endif ?>
                </a></li>
                <li><a href="#" id="tb_addnode">Add<br/> Node</a></li>
                <li><a href="#" id="tb_addlink">Add<br/> Link</a></li>
                <li><a href="#" id="tb_poslegend">Position<br/> Legend</a></li>
                <li><a href="#" id="tb_postime">Position<br/> Timestamp</a></li>
                <li><a href="#" id="tb_mapprops">Map<br/> Properties</a></li>
                <li><a href="#" id="tb_mapstyle">Map<br/> Style</a></li>
                <!-- <li ><a href="#" id="tb_colours">Manage Colors</a></li>
                <li ><a href="#" id="tb_manageimages">Manage Images</a></li> -->
                <li><a href="#" id="tb_prefs">Editor<br/> Settings</a></li>
                <li class="divider-vertical"></li>
                <li><a id="tb_coords">Position<br/><span id="coords_x">---</span>, <span id="coords_y">---</span></a></li>
                <!-- <li class="tb_help"><span id="tb_help">or click a Node or Link to edit it's properties</span></li> -->
            </ul>
        </div>
    </div>
    <p></p>

    <div class="container" id="wmmaincontainer">
        <form action="" method="post" name="frmMain" class="form-inline">
            <div align="center" id="mainarea">
                <input type="hidden" name="plug" value="<?php echo($fromplug == true ? 1 : 0) ?>"/>
                <div id="imagecontainer">
                <input style="display:none" type="image" width="<?php echo $map_width ?>"
                       src="<?php echo $imageurl; ?>"
                       height="<?php echo $map_height ?>"
                       src="" id="xycapture"/><img src="<?php echo $imageurl; ?>"
                                                   width="<?php echo $map_width ?>"
                                                   height="<?php echo $map_height ?>"
                                                   id="existingdata" alt="Weathermap"
                                                   usemap="#weathermap_imap"/>
                </div>
                <div class="debug well well-sm">
                    Editing: <?php echo $mapname; ?>
                    <a class="btn btn-success btn-xs" href="?<?php echo($fromplug == true ? 'plug=1&amp;' : '');
                    ?>action=retidy&amp;mapname=<?php echo htmlspecialchars($mapname) ?>">Retidy</a>

                    <a class="btn btn-success btn-xs" href="?<?php echo($fromplug == true ? 'plug=1&amp;' : '');
                    ?>action=untidy_all&amp;mapname=<?php echo htmlspecialchars($mapname) ?>">Untidy</a>

                    <a class="btn btn-success btn-xs" href="?<?php echo($fromplug == true ? 'plug=1&amp;' : '');
                    ?>action=tidy_all&amp;mapname=<?php echo htmlspecialchars($mapname) ?>">Tidy All</a>

                    <input type="hidden" class="form-control input-sm" name="mapname" value="<?php echo $mapname ?>"/>
                    <input type="ahidden" class="form-control input-sm" id="action" name="action"
                           value="<?php echo $newaction ?>"/>
                    <input type="ahidden" class="form-control input-sm" name="param" id="param" value=""/>
                    <input type="ahidden" class="form-control input-sm" name="param2" id="param2"
                           value="<?php echo $param2 ?>"/>
                    <input type="hidden" class="form-control input-sm" id="debug" value="" name="debug"/>

                    <a class="btn btn-success btn-warning btn-xs"
                       href="?<?php echo($fromplug == true ? 'plug=1&amp;' : '');
                       ?>action=nothing&amp;mapname=<?php echo htmlspecialchars($mapname) ?>">Do Nothing</a>

                    <a class="btn btn-info  btn-xs" target="configwindow"
                       href="?<?php echo($fromplug == true ? 'plug=1&amp;' : '');
                       ?>action=show_config&amp;mapname=<?php echo urlencode($mapname) ?>">See config</a>
                    <?php echo $log ?>
                </div>
                <?php echo $imagemap ?>
                <canvas id="canvas_node_drag" width="100" height="100"></canvas>
            </div>
        </form>
    </div>
</div>
<script type="text/template" id="tpl-dialog-node-properties">
    <div class="dlgUnderlay">
    </div>
    <div id="dlgNodeProperties" class="dlgProperties">
        <h3>Node Properties <small>Node '<span id="node_nodename1"><%-rc.name %></span>'</small></h3>
        <div class="dlgTitlebar">
            <button id="tb_node_submit" class="wm_submit btn btn-success">Save</button>
            <button id="tb_node_cancel" class="wm_cancel btn btn-danger">Cancel</button>
            <button class="btn btn-default" id="node_move">Move</button>
            <button class="btn btn-default" id="node_clone">Clone</button>
            <button class="btn btn-default" id="node_edit">Edit</button>
            <button class="btn btn-danger" id="node_delete">Delete</button>
        </div>

        <div class="dlgBody">
            Node properties go in here
        </div>
    </div><!-- Node Properties -->
</script>

<script type="text/template" id="tpl-dialog-link-properties">
    <div class="dlgUnderlay">
        </div>
        <div id="dlgLinkProperties" class="dlgProperties">
            <h3>Link Properties <small>Link from '<span id="link_nodename1"><%-rc.data.a %></span>' to '<span id="link_nodename2"><%-rc.data.b %></span>'</small></h3>
            <div class="dlgTitlebar">
                <button id="tb_link_submit" class="wm_submit btn btn-success">Save</button>
                <button id="tb_link_cancel" class="wm_cancel btn btn-danger">Cancel</button>

                <button class="btn btn-default" id="link_edit">Edit</button>
                <button class="btn btn-default" id="link_tidy">Tidy</button>
                <button class="btn btn-default" id="link_via"><%-rc.via_label %></button>
                <% if (rc.show_straighten) { %>
                <button class="btn btn-default" id="link_novia">Straighten</button>
                <% } %>
                <button class="btn btn-danger" id="link_delete">Delete</button>
            </div>

            <div class="dlgBody">

                <input size="6" name="link_name" type="hidden" value="<%- rc.name %>"/>

                <table width="100%" class="table">
                    <tr>
                        <th>Maximum Bandwidth<br/>
                            Into '<span id="link_nodename1a"><%-rc.data.a %></span>'
                        </th>
                        <td><input size="8" id="link_bandwidth_in" name="link_bandwidth_in" value="<%-rc.data.bw_in %>" type=
                            "text"/> bits/sec
                        </td>
                    </tr>
                    <tr>
                        <th>Maximum Bandwidth<br/>
                            Out of '<span id="link_nodename1b"><%-rc.a %></span>'
                        </th>
                        <td><input type="checkbox" id="link_bandwidth_out_cb" name=
                            "link_bandwidth_out_cb" value="symmetric"/>Same As
                            'In' or <input id="link_bandwidth_out" name="link_bandwidth_out"
                                           size="8" value="<%-rc.bw_out %>" type="text"/> bits/sec
                        </td>
                    </tr>
                    <tr>
                        <th>Data Source</th>
                        <td><input id="link_target" size=30 name="link_target" type="text" value="<%- rc.target %>"/> <button class="btn btn-xs cactilink"
                                    id="link_cactipick">Pick</button></td>
                    </tr>
                    <tr>
                        <th>Link Width</th>
                        <td><input id="link_width" name="link_width" size="3" value="<%- rc.width %>" type="text"/>
                            pixels
                        </td>
                    </tr>
                    <tr>
                        <th>Info URL</th>
                        <td><input id="link_infourl" size="30" name="link_infourl" value="<%- rc.infourl %>" type="text"/></td>
                    </tr>
                    <tr>
                        <th>'Hover' Graph URL</th>
                        <td><input id="link_hover" size="30" name="link_hover" value="<%- rc.overliburl %>" type="text"/></td>
                    </tr>


                    <tr>
                        <th>IN Comment</th>
                        <td><input id="link_commentin" size="25" name="link_commentin"  value="<%- rc.commentin %>" type="text"/>
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
                        <td><input id="link_commentout" size="25" name="link_commentout" value="<%- rc.commentout %>" type="text"/>
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
                </table>
            </div>

        </div><!-- Link Properties -->

</script>
<script type="text/template" id="tpl-dialog-map-properties">
    <div class="dlgUnderlay">
    </div>
    <div id="dlgMapProperties" class="dlgProperties">
        <h3>Map Properties <small>Map '<span id="map_mapname1"><%-rc.file %></span>'</small></h3>
        <div class="dlgTitlebar">
            <button id="tb_map_submit" class="wm_submit btn btn-success">Save</button>
            <button id="tb_map_cancel" class="wm_cancel btn btn-danger">Cancel</button>
        </div>

        <div class="dlgBody">
            Map properties go in here.

            <%- rc.title %>
            <%- rc.file %>
            <%- rc.width %> x <%- rc.height %>

        </div>
    </div><!-- Map Properties -->
</script>
</body>
</html>