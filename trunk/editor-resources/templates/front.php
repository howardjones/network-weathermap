<?php include "header.php"; ?>
<body>
<div class="container">

<div id="nojs" class="alert"><b>WARNING</b> - 
    Sorry, it's partly laziness on my part, but you really need JavaScript enabled and DOM support in your browser to use this editor. 
    It's a visual tool, so accessibility is already an issue, if it is, and from a security viewpoint, you\'re already running my 
    code on your <i>server</i> so either you trust it all having read it, or you're already screwed.<P>
    If it's a major issue for you, please feel free to complain. 
    It's mainly laziness as I said, and there could be a fallback (not so smooth) mode 
    for non-javascript browsers if it was seen to be worthwhile (I would take a bit of convincing, 
    because I don't see a benefit, personally).
</div>

<div id="withjs">
    <div id="dlgStart" class="modal" >
        <div class="dlgTitlebar modal-header"><h3>
            Welcome to the Weathermap <?php echo $WEATHERMAP_VERSION ?> editor</h3>
        </div>
        <div class="modal-body">
    
            <form method="GET" class="form-inline">
                <label><strong>Create A New Map</strong>, named</label>
                <div class="input-append"><input type="text" name="mapname" size="20"><span class="add-on">.conf</span></div>
                <input name="action" type="hidden" value="newmap">
                <input name="plug" type="hidden" value="<?php echo $fromplug ?>">        
                <input type="submit" class="btn btn-success" value="Create">
            </form>
        
            <hr />
        
            <form method="GET"  class="form-inline">
                <input name="action" type="hidden" value="newmapcopy">
                <input name="plug" type="hidden" value="'.$fromplug.'">
                <div class="control-group"><label>or <strong>Create A New Map as a copy</strong> of an existing map, named:</label>
                <div class="input-append"><input type="text" name="mapname" size="20"><span class="add-on">.conf</span></div></div>
                <div class="control-group"><label>based on</label><select name="sourcemap">
                <?php foreach ($titles as $file=>$title): ?>                
                    <option value="<?php echo $file?>"><?php echo $file?></option>
                <?php endforeach ?>
                </select>
                <input type="submit" class="btn btn-success" value="Create Copy"></div>
            </form>
        
            <hr />
        
            or <strong>Open An Existing Map</strong>:
            <div id="existinglist">
            <ul class="filelist">
           <?php foreach ($titles as $file=>$title) : ?>                               
                <li>
                <?php echo $note ?>
                <a href="?mapname=<?php echo $file ?>&action=nothing&plug=<?php echo $fromplug ?>"><?php echo $file ?></a>
                 - <span class="comment"><?php echo $title ?></span>
                 </li>
            <?php endforeach ?>            
            
            </ul>
    	    </div>
        </div>    
    
        <div class="modal-footer">
            <small>PHP Weathermap <?php echo $WEATHERMAP_VERSION ?>
            Copyright &copy; 2005-2013 Howard Jones - howie@thingy.com
            <br />The current version should always be <a href="http://www.network-weathermap.com/">available here</a>,
            along with other related software. PHP Weathermap is licensed under the GNU Public License, version 2. See 
            COPYING for details. This distribution also includes other open source software listed in the README file.</small>
        </div>            
    </div>
        
</div>

</div>
</body></html>