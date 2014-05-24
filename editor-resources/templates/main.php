<?php include "header.php"; ?>

<body id="mainview">
<div id="wrap">
    
  <div class="navbar navbar-default navbar-fixed-top" id="topmenu">    
	<div class="navbar-header">
	    <div class="container">
	    <a class="brand" href="#">WMEditor</a>
	    <ul class="nav">
	      <li><a href="#" id="tb_newfile">Change<br /> File</a></li>
	      <li ><a href="#" id="tb_addnode">Add<br /> Node</a></li>
	      <li ><a href="#" id="tb_addlink">Add<br /> Link</a></li>
	      <li ><a href="#" id="tb_poslegend">Position<br /> Legend</a></li>
	      <li ><a href="#" id="tb_postime">Position<br /> Timestamp</a></li>
	      <li ><a href="#" id="tb_mapprops">Map<br /> Properties</a></li>
	      <li ><a href="#" id="tb_mapstyle">Map<br /> Style</a></li>
	      <!-- <li ><a href="#" id="tb_colours">Manage Colors</a></li>
	      <li ><a href="#" id="tb_manageimages">Manage Images</a></li> -->
	      <li ><a href="#" id="tb_prefs">Editor<br /> Settings</a></li>
	      <li class="divider-vertical"></li>
	      <li><a id="tb_coords">Position<br /> ---, ---</a></li>
	      <!-- <li class="tb_help"><span id="tb_help">or click a Node or Link to edit it's properties</span></li> -->
	    </ul>
	    </div>
	</div>
  </div>
  
  <div class="container" id="wmmaincontainer">
    <form action="" method="post" name="frmMain" >
	   <div align="center" id="mainarea">
	   	<input type="hidden" name="plug" value="<?php echo ($fromplug==true ? 1 : 0) ?>" />
	    <input style="display:none" type="image"  width="<?php echo $map_width?>" height="<?php echo $map_height?>" 
	       src="<?php echo $imageurl; ?>" id="xycapture" /><img src=
	       "<?php echo $imageurl; ?>" width="<?php echo $map_width?>" height="<?php echo $map_height?>"
           id="existingdata" alt="Weathermap" usemap="#weathermap_imap" />
	   
	       <div class="debug control-group">
        	   <input type="text" class="input-mini" name="mapname" value="<?php echo $mapname ?>" />
        	   <input type="text" class="input-mini" id="action" name="action" value="<?php echo $newaction ?>" />
        	   <input type="text" class="input-mini" name="param" id="param" value="" />
               <input type="text" class="input-mini" name="param2" id="param2" value="<?php echo $param2 ?>" />
        	   <input type="text" class="input-mini" id="debug" value="" name="debug" />    	  
        	   <a class="btn btn-success" href="?<?php echo ($fromplug==true ? 'plug=1&amp;' : '');
               ?>action=nothing&amp;mapname=<?php echo  htmlspecialchars($mapname) ?>">Do Nothing</a>
        	   <a class="btn btn-info" target="configwindow" href="?<?php echo ($fromplug==true ? 'plug=1&amp;':'');
               ?>action=show_config&amp;mapname=<?php echo  urlencode($mapname) ?>">See config</a>
    	   </div>  
	   
	       <?php echo $imagemap ?>
	   </div>
	   </form>	 
  </div>
  
  
</div>
</body></html>
