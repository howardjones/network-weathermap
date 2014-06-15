<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

/** Wrapper API around WeatherMap to provide the relevant operations to manipulate
 *  the map contents that an editor will need, without it needing to see inside the map object.
 *  (a second class, WeatherMapEditorUI, is concerned with the actual presentation of the supplied editor)
 */

class WeatherMapEditor {
    
    var $map;
    var $mapfile;
    
    function WeatherMapEditor()
    {
        $this->map = null;
    }
    
    function isLoaded() {
        return !is_null($this->map);
    }
    
    function newConfig() 
    {
        $this->map = new WeatherMap();
        $this->map->context = "editor";
        $this->mapfile = "untitled";
    }
    
    function loadConfig($filename)
    {
        $this->map = new WeatherMap();
        $this->map->context = 'editor';
        $this->map->ReadConfig($filename);
        $this->mapfile = $filename;
    }

    /**
     * Save the map config file.
     *
     * Optionally, save to a different file from the one loaded.
     *
     * @param string $filename
     */
    function saveConfig($filename="")
    {
        if ($filename != "") {
            $this->mapfile = $filename;
        }
        $this->map->writeConfig($this->mapfile);
    }

    /**
     * Return the config that would have been saved. Mainly for tests.
     *
     */
    function getConfig()
    {
        return $this->map->getConfig();
    }
    
    function addNode($x, $y, $nodename = "", $template = "DEFAULT")
    {    
        if (! $this->isLoaded() ) {
            die("Map must be loaded before editing API called.");
        }

        $success = false;
        $log = "";
        $newnodename = null;

        // Generate a node name for ourselves if one wasn't supplied
        if ($nodename == "") {
            $newnodename = sprintf("node%05d",time()%10000);
            while (array_key_exists($newnodename,$this->map->nodes)) {
                $newnodename .= "a";
            }
        } else {
            $newnodename = $nodename;
        }

        // Check again - if they are specifying a name, it's possible for it to exist
        if (!array_key_exists($newnodename, $this->map->nodes)) {
            $node = new WeatherMapNode;
            $node->name = $newnodename;
            $node->template = $template;

            $node->reset($this->map);

            $node->x = $x;
            $node->y = $y;
            $node->defined_in = $this->map->configfile;

            // needs to know if zlayer exists.
            if ( !array_key_exists($node->zorder, $this->map->seen_zlayers)) {
                $this->map->seen_zlayers[$node->zorder] = array();
            }
            array_push($this->map->seen_zlayers[$node->zorder], $node);

            // only insert a label if there's no LABEL in the DEFAULT node.
            // otherwise, respect the template.
            if ($this->map->nodes['DEFAULT']->label == $this->map->nodes[':: DEFAULT ::']->label) {
                $node->label = "Node";
            }

            $this->map->nodes[$node->name] = $node;
            $log = "added a node called $newnodename at $x,$y to $this->mapfile";
            $success = true;
        } else {
            $log = "Requested node name already exists";
            $success = false;
        }

        return array($newnodename, $success, $log);
    }
    
    /**
     * moveNode - move a node, taking into account any relative nodes, and any links that
     * join to it, dealing with VIAs in an attractive way.
     * 
     * @param string $node_name
     * @param number $x
     * @param number $y
     *
     * @return array (number of affected nodes, number of affected links, list of affected nodes, list of affected links)
     */
    function moveNode($node_name, $x, $y)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        $n_links = 0;
        $n_nodes = 0;
        $affected_nodes = array();
        $affected_links = array();

        if (isset($this->map->nodes[$node_name])) {
            // This is a complicated bit. Find out if this node is involved in any
            // links that have VIAs. If it is, we want to rotate those VIA points
            // about the *other* node in the link
            foreach ($this->map->links as $link) {
                if ( (count($link->vialist)>0)  && (($link->a->name == $node_name) || ($link->b->name == $node_name)) ) {

                    $affected_links[] = $link->name;

                    // get the other node from us
                    if ($link->a->name == $node_name) {
                        $pivot = $link->b;
                    }

                    if ($link->b->name == $node_name) {
                        $pivot = $link->a;
                    }

                    // this is a wierd special case, but it is possible, with link offsets
                    if ( ($link->a->name == $node_name) && ($link->b->name == $node_name) ) {
                        $dx = $link->a->x - $x;
                        $dy = $link->a->y - $y;

                        for ($i=0; $i<count($link->vialist); $i++) {
                            $link->vialist[$i][0] = $link->vialist[$i][0]-$dx;
                            $link->vialist[$i][1] = $link->vialist[$i][1]-$dy;
                        }
                    } else {
                        $pivx = $pivot->x;
                        $pivy = $pivot->y;

                        $dx_old = $pivx - $this->map->nodes[$node_name]->x;
                        $dy_old = $pivy - $this->map->nodes[$node_name]->y;
                        $dx_new = $pivx - $x;
                        $dy_new = $pivy - $y;
                        $l_old = sqrt($dx_old*$dx_old + $dy_old*$dy_old);
                        $l_new = sqrt($dx_new*$dx_new + $dy_new*$dy_new);

                        $angle_old = rad2deg(atan2(-$dy_old,$dx_old));
                        $angle_new = rad2deg(atan2(-$dy_new,$dx_new));

                        # $log .= "$pivx,$pivy\n$dx_old $dy_old $l_old => $angle_old\n";
                        # $log .= "$dx_new $dy_new $l_new => $angle_new\n";

                        // the geometry stuff uses a different point format, helpfully
                        $points = array();
                        foreach ($link->vialist as $via) {
                            $points[] = $via[0];
                            $points[] = $via[1];
                        }

                        $scalefactor = $l_new/$l_old;

                        // rotate so that link is along the axis
                        rotateAboutPoint($points,$pivx, $pivy, deg2rad($angle_old));
                        // do the scaling in here
                        for ($i=0; $i<(count($points)/2); $i++) {
                            $basex = ($points[$i*2] - $pivx) * $scalefactor + $pivx;
                            $points[$i*2] = $basex;
                        }
                        // rotate back so that link is along the new direction
                        rotateAboutPoint($points,$pivx, $pivy, deg2rad(-$angle_new));

                        // now put the modified points back into the vialist again
                        $v = 0;
                        $i = 0;
                        foreach ($points as $p) {
                            // skip a point if it positioned relative to a node. Those shouldn't be rotated (well, IMHO)
                            if (!isset($link->vialist[$v][2])) {
                                $link->vialist[$v][$i]=$p;
                            }
                            $i++;
                            if ($i==2) {
                                $i=0;
                                $v++;
                            }
                        }
                   }
               }
            }

            $this->map->nodes[$node_name]->x = $x;
            $this->map->nodes[$node_name]->y = $y;
        }  

        $n_links = count($affected_links);
        $n_nodes = count($affected_nodes);
        
        return array($n_nodes, $n_links, $affected_nodes, $affected_links);
    }
        
    function updateNode($nodename)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        die("unimplemented");
    }
    
    function deleteNode($nodename)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        $log = "";
        $n_links = 0;
        $n_nodes = 0;
        $affected_nodes = array();
        $affected_links = array();

        if (isset($this->map->nodes[$nodename])) {

            $affected_nodes[] = $nodename;

            $log = "delete node ".$nodename;

            foreach ($this->map->links as $link) {
                if ( isset($link->a) ) {
                    if ( ($nodename == $link->a->name) || ($nodename == $link->b->name) ) {
                        $affected_links[] = $link->name;
                        unset($this->map->links[$link->name]);
                    }
                }
            }

            unset($this->map->nodes[$nodename]);
            $n_nodes++;
        }
        // TODO - look for relative positioned nodes, and un-relative them

        $n_nodes = count($affected_nodes);
        $n_links = count($affected_links);
        return array($n_nodes, $n_links, $affected_nodes, $affected_links ,$log);
    }

    function cloneNode($sourcename, $targetname="", $or_fail = false)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        if (isset($this->map->nodes[$sourcename])) {
            $log = "cloned node ".$sourcename;

            // Try to use the requested name, if possible, and if specified
            $newnodename = ($targetname != "" ? $targetname : $sourcename);

            if ($targetname != "" && $or_fail && isset($this->map->nodes[$newnodename]) ) {
                return array(false, null, "Requested name already exists");
            }

            if ( isset($this->map->nodes[$newnodename]) ) {
                $newnodename = $sourcename;
                do {
                    $newnodename = $newnodename."_copy";
                } while (isset($this->map->nodes[$newnodename]));
            }

            $log .= " into $newnodename";

            $node = new WeatherMapNode;
            $node->reset($this->map);
            $node->copyFrom($this->map->nodes[$sourcename]);

            # CopyFrom skips this one, because it's also the function used by template inheritance
            # - but for Clone, we DO want to copy the template too
            $node->template = $this->map->nodes[$sourcename]->template;

            $node->name = $newnodename;
            $node->x += 30;
            $node->y += 30;
            $node->defined_in = $this->map->configfile;

            $this->map->nodes[$newnodename] = $node;
            array_push($this->map->seen_zlayers[$node->zorder], $node);

            return array(true, $newnodename, $log);
        }

        return array(false, null,"Request source does not exist");
    }
        
    function addLink($node1, $node2, $linkname = "",$template = "DEFAULT")
    {    
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        $success = false;
        $log = "";
        $newlinkname = null;

        if ($node1 != $node2 && isset($this->map->nodes[$node1]) && isset($this->map->nodes[$node2]) ) {
            $newlink = new WeatherMapLink;
            $newlink->reset($this->map);

            $newlink->a = $this->map->nodes[$node1];
            $newlink->b = $this->map->nodes[$node2];

            // make sure the link name is unique. We can have multiple links between
            // the same nodes, these days
            $newlinkname = "$node1-$node2";
            while (array_key_exists($newlinkname,$this->map->links)) {
                $newlinkname .= "a";
            }
            $newlink->name = $newlinkname;
            $newlink->defined_in = $this->map->configfile;
            $this->map->links[$newlinkname] = $newlink;

            // needs to know if zlayer exists.
            if ( !array_key_exists($newlink->zorder, $this->map->seen_zlayers)) {
                $this->map->seen_zlayers[$newlink->zorder] = array();
            }
            array_push($this->map->seen_zlayers[$newlink->zorder], $newlink);
            $success = true;
            $log = "created link $newlinkname between $node1 and $node2";
        }

        return array($newlinkname, $success, $log);

    }
    
    function updateLink()
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        die("unimplemented");
    }

    function deleteLink($linkname)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        if (isset($this->map->links[$linkname])) {
            unset($this->map->links[$linkname]);
            return true;
        }
        return false;
    }

    /**
     * cloneLink - create a copy of an existing link
     * Not as useful as cloneNode, but still sometimes handy.
     * 
     * @param string $sourcename
     * @param string $targetname
     */
    function cloneLink($sourcename, $targetname="")
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }
        
        die("unimplemented");
    }
    
    /**
     * setLinkVia - simple-minded add/replacement of a single VIA for a link.
     * Should be replaced by addLinkVia with intelligent handling of multiple VIAs
     * 
     * @param string $linkname
     * @param number $x
     * @param number $y
     * @return boolean - successful or not
     */
    function setLinkVia($linkname, $x,$y)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        if (isset($this->map->links[$linkname])) {
            $this->map->links[$linkname]->vialist = array(array(0 =>$x, 1=>$y));

            return true;
        }
        return false;
    }
    
    function tidyLink($linkname) 
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMapImage('null');
        $this->_tidy_link($linkname);
    }

    function retidyAllLinks($linkname)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }
        
        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->DrawMap('null');
        $this->_retidy_links(true);
    }
    
    function retidyLinks($linkname)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }
        
        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->DrawMap('null');                
        $this->_retidy_links();
    }
    
    /**
     * _retidy_links - find all links that have previously been tidied (_tidied hint) and tidy them again
     * UNLESS $ignore_tidied is set, then do every single link (for editor testing)
     *  
     * @param boolean $ignore_tidied
     */
    function _retidyLinks($ignore_tidied=false)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }
    
        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->DrawMap('null');
        
        $routes = array();
        $done = array();
        
        foreach ($this->map->links as $link) {
            $route = $link->a->name . " " . $link->b->name;
            if (strcmp( $link->a->name, $link->b->name) > 0) {
                $route = $link->b->name . " " . $link->a->name;
            }
            $routes[$route][] = $link->name;
        }
        
        foreach ($this->map->links as $link) {
            $route = $link->a->name . " " . $link->b->name;
            if (strcmp( $link->a->name, $link->b->name) > 0) {
                $route = $link->b->name . " " . $link->a->name;
            }
        
            if ( ($ignore_tidied || $link->get_hint("_tidied")==1) && $done[$route]==0) {
        
                if (sizeof($routes[$route]) == 1) {
                    $this->_tidy_link($link->name);
                    $done[$route] = 1;
                } else {
                    # handle multi-links specially...
                    $this->_tidy_links($routes[$route]);
                    // mark it so we don't do it again when the other links come by
                    $done[$route] = 1;
                }
            }
        }
    }
    
    /**
     * _tidy_link - change link offsets so that link is horizonal or vertical, if possible.
     *  if not possible, change offsets to the closest facing compass points
     *  
     * @param string $target - the link name to tidy
     * @param int $linknumber - if this is part of a group, which number in the group
     * @param int $linktotal - if this is part of a group, how many total in the group
     * @param bool $ignore_tidied - whether to take notice of the "_tidied" hint
     */
    function _tidy_link($target, $linknumber = 1, $linktotal = 1, $ignore_tidied = false)
    {
        // print "\n-----------------------------------\nTidying $target...\n";
        if (isset($this->map->links[$target]) and isset($this->map->links[$target]->a) ) {
        
            $node_a = $this->map->links[$target]->a;
            $node_b = $this->map->links[$target]->b;
             
            $new_a_offset = "0:0";
            $new_b_offset = "0:0";
        
            // Update TODO: if the nodes are already directly left/right or up/down, then use compass-points, not pixel offsets
            // (e.g. N90) so if the label changes, they won't need to be re-tidied
        
            // First bounding box in the node's boundingbox array is the icon, if there is one, or the label if not.
            $bb_a = $node_a->boundingboxes[0];
            $bb_b = $node_b->boundingboxes[0];
        
            // figure out if they share any x or y coordinates
            $x_overlap = $this->range_overlaps($bb_a[0], $bb_a[2], $bb_b[0], $bb_b[2]);
            $y_overlap = $this->range_overlaps($bb_a[1], $bb_a[3], $bb_b[1], $bb_b[3]);
        
            $a_x_offset = 0; $a_y_offset = 0;
            $b_x_offset = 0; $b_y_offset = 0;
        
            // if they are side by side, and there's some common y coords, make link horizontal
            if ( !$x_overlap && $y_overlap ) {
                // print "SIDE BY SIDE\n";
        
                // snap the X coord to the appropriate edge of the node
                if ($bb_a[2] < $bb_b[0]) {
                    $a_x_offset = $bb_a[2] - $node_a->x;
                    $b_x_offset = $bb_b[0] - $node_b->x;
                }
                if ($bb_b[2] < $bb_a[0]) {
                    $a_x_offset = $bb_a[0] - $node_a->x;
                    $b_x_offset = $bb_b[2] - $node_b->x;
                }
        
                // this should be true whichever way around they are
                list($min_overlap,$max_overlap) = $this->common_range($bb_a[1],$bb_a[3],$bb_b[1],$bb_b[3]);
                $overlap = $max_overlap - $min_overlap;
                $n = $overlap/($linktotal+1);
        
                $a_y_offset = $min_overlap + ($linknumber*$n) - $node_a->y;
                $b_y_offset = $min_overlap + ($linknumber*$n) - $node_b->y;
                 
                $new_a_offset = sprintf("%d:%d", $a_x_offset,$a_y_offset);
                $new_b_offset = sprintf("%d:%d", $b_x_offset,$b_y_offset);
            }
        
            // if they are above and below, and there's some common x coords, make link vertical
            if ( !$y_overlap && $x_overlap ) {
                // print "ABOVE/BELOW\n";
        
                // snap the Y coord to the appropriate edge of the node
                if ($bb_a[3] < $bb_b[1]) {
                    $a_y_offset = $bb_a[3] - $node_a->y;
                    $b_y_offset = $bb_b[1] - $node_b->y;
                }
                if ($bb_b[3] < $bb_a[1]) {
                    $a_y_offset = $bb_a[1] - $node_a->y;
                    $b_y_offset = $bb_b[3] - $node_b->y;
                }
        
                list($min_overlap,$max_overlap) = $this->common_range($bb_a[0],$bb_a[2],$bb_b[0],$bb_b[2]);
                $overlap = $max_overlap - $min_overlap;
                $n = $overlap/($linktotal+1);
        
                // move the X coord to the centre of the overlapping area
                $a_x_offset = $min_overlap + ($linknumber*$n) - $node_a->x;
                $b_x_offset = $min_overlap + ($linknumber*$n) - $node_b->x;
        
                $new_a_offset = sprintf("%d:%d", $a_x_offset,$a_y_offset);
                $new_b_offset = sprintf("%d:%d", $b_x_offset,$b_y_offset);
        
        
            }
        
            // if no common coordinates, figure out the best diagonal...
            if ( !$y_overlap && !$x_overlap ) {
        
                $pt_a = new WMPoint($node_a->x, $node_a->y);
                $pt_b = new WMPoint($node_b->x, $node_b->y);
                 
        
                $line = new WMLineSegment($pt_a, $pt_b);
        
                $tangent = $line->vector;
                $tangent->normalise();
        
                $normal = $tangent->getNormal();
        
                $pt_a->addVector( $normal, 15 * ($linknumber-1) );
                $pt_b->addVector( $normal, 15 * ($linknumber-1) );
        
                $a_x_offset = $pt_a->x - $node_a->x;
                $a_y_offset = $pt_a->y - $node_a->y;
        
                $b_x_offset = $pt_b->x - $node_b->x;
                $b_y_offset = $pt_b->y - $node_b->y;
        
                $new_a_offset = sprintf("%d:%d", $a_x_offset,$a_y_offset);
                $new_b_offset = sprintf("%d:%d", $b_x_offset,$b_y_offset);
        
        
            }
        
            // if no common coordinates, figure out the best diagonal...
            // currently - brute force search the compass points for the shortest distance
            // potentially - intersect link line with rectangles to get exact crossing point
            if ( 1==0 && !$y_overlap && !$x_overlap ) {
                // print "DIAGONAL\n";
        
                $corners = array("NE","E","SE","S","SW","W","NW","N");
        
                // start with what we have now
                $best_distance = distance( $node_a->x, $node_a->y, $node_b->x, $node_b->y );
                $best_offset_a = "C";
                $best_offset_b = "C";
        
                foreach ($corners as $corner1) {
                    list ($ax,$ay) = wmCalculateOffset($corner1, $bb_a[2] - $bb_a[0], $bb_a[3] - $bb_a[1]);
        
                    $axx = $node_a->x + $ax;
                    $ayy = $node_a->y + $ay;
        
                    foreach ($corners as $corner2) {
                        list($bx,$by) = wmCalculateOffset($corner2, $bb_b[2] - $bb_b[0], $bb_b[3] - $bb_b[1]);
        
                        $bxx = $node_b->x + $bx;
                        $byy = $node_b->y + $by;
        
                        $d = distance($axx,$ayy, $bxx, $byy);
                        if ($d < $best_distance) {
                            // print "from $corner1 ($axx, $ayy) to $corner2 ($bxx, $byy): ";
                            // print "NEW BEST $d\n";
                            $best_distance = $d;
                            $best_offset_a = $corner1;
                            $best_offset_b = $corner2;
                        }
                    }
                }
                // Step back a bit from the edge, to hide the corners of the link
                $new_a_offset = $best_offset_a."85";
                $new_b_offset = $best_offset_b."85";
            }
        
            // unwritten/implied - if both overlap, you're doing something wierd and you're on your own
        
            // finally, update the offsets
            $this->map->links[$target]->a_offset = $new_a_offset;
            $this->map->links[$target]->b_offset = $new_b_offset;
            // and also add a note that this link was tidied, and is eligible for automatic tidying
            $this->map->links[$target]->add_hint("_tidied",1);
        }
    }
    
    /**
     * _tidy_links - for a group of links between the same two nodes, distribute them
     * nicely.
     * 
     * @param string[] $links - the link names to treat as a group
     * @param bool $ignore_tidied - whether to take notice of the "_tidied" hint
     * 
     */
    function _tidy_links($links,  $ignore_tidied=false) 
    {
        // not very efficient, but it saves looking for special cases (a->b & b->a together)
        $ntargets = count($targets);
        
        $i = 1;
        foreach ($targets as $target) {
            $this->_tidy_link($target, $i, $ntargets, $ignore_tidied);
            $i++;
        }
    }

    /**
     * untidyLinks - remove all link offsets from the map. Used mainly for testing.
     * 
     */
    function untidyLinks()
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }
        
        foreach ($this->map->links as $link) {
            $link->a_offset = "C";
            $link->b_offset = "C";
        }    
    }
    
    function placeLegend($x, $y, $scalename = "DEFAULT")
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }
        
        $this->map->keyx[$scalename] = $x;
        $this->map->keyy[$scalename] = $y;
    }
    
    function placeTitle($x, $y) 
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }
        
        $this->map->timex = $x;
        $this->map->timey = $y;
    }
    
    function placeTimestamp($x, $y)
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }
        
        $this->map->timex = $x;
        $this->map->timey = $y;
    }
    
    function asJS()
    {
        if (! $this->isLoaded()) {
            die("Map must be loaded before editing API called.");
        }

    }
    
    
    /**
     * range_overlaps - check if two ranges have anything in common. Used for tidy()
     * 
     * @param unknown $a_min
     * @param unknown $a_max
     * @param unknown $b_min
     * @param unknown $b_max
     * @return boolean
     */
    function range_overlaps($a_min, $a_max, $b_min, $b_max)
    {
        if ($a_min > $b_max) {
            return false;
        }
        if ($b_min > $a_max) {
            return false;
        }
    
        return true;
    }
    
    /**
     * common_range - find the range of numbers where two ranges overlap. Used for tidy()
     * 
     * @param number $a_min
     * @param number $a_max
     * @param number $b_min
     * @param number $b_max
     * @return number list($min,$max)
     */
    function common_range ($a_min, $a_max, $b_min, $b_max)
    {
        $min_overlap = max($a_min, $b_min);
        $max_overlap = min($a_max, $b_max);
    
        return array($min_overlap, $max_overlap);
    }
    
    /* distance - find the distance between two points
     *
    */
    function distance ($ax, $ay, $bx, $by)
    {
        $dx = $bx - $ax;
        $dy = $by - $ay;
        return sqrt( $dx*$dx + $dy*$dy );
    }
}
