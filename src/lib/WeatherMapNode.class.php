<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License


class WeatherMapNode extends WeatherMapDataItem
{
    public $x;
    public $y;
    public $position; // to replace the above eventually
    public $original_x;
    public $original_y;
    public $relative_resolved;
    public $width;
    public $height;
    public $label; // the configured label text
    public $processedLabel; // the label after processing (what is actually drawn)
    public $labelfont;
    public $labelangle;

    // SCALE colours

    public $notestext = array();

    public $selected = 0;
    public $iconfile;
    public $iconscalew;
    public $iconscaleh;

    public $labeloffset;
    public $labeloffsetx;
    public $labeloffsety;

    public $labelbgcolour;
    public $labeloutlinecolour;
    public $labelfontcolour;
    public $labelfontshadowcolour;
    public $aiconfillcolour;
    public $aiconoutlinecolour;

    public $cachefile;
    public $useiconscale;
    public $iconscaletype;
    public $iconscalevar;

    public $image;
    public $centre_x;
    public $centre_y;
    public $relative_to;
    public $polar;
    public $boundingboxes = array();
    public $subObjects = array();
    public $named_offsets = array();

    public $drawable = false;
    public $resolvedColours = array();

    public $runtime = array();

    function __construct($name, $template, $owner)
    {
        parent::__construct();

        $this->name = $name;
        $this->owner = $owner;
        $this->template = $template;

        $this->inherit_fieldlist = array
        (
            'boundingboxes' => array(),
            'named_offsets' => array(),
            'my_default' => null,
            'label' => '',
            'proclabel' => '',
            'usescale' => 'DEFAULT',
            'scaletype' => 'percent',
            'iconscaletype' => 'percent',
            'useiconscale' => 'none',
            'scalevar' => 'in',
            'template' => ':: DEFAULT ::',
            'iconscalevar' => 'in',
            'labelfont' => 3,
            'relative_to' => '',
            'relative_name' => '',
            'relative_resolved' => false,
            'x' => null,
            'y' => null,
            'inscalekey' => '',
            'outscalekey' => '',
            'original_x' => 0,
            'original_y' => 0,
            'inpercent' => 0,
            'outpercent' => 0,
            'labelangle' => 0,
            'iconfile' => '',
            'iconscalew' => 0,
            'iconscaleh' => 0,
            'targets' => array(),
            'infourl' => array(IN => '', OUT => ''),
            'notestext' => array(IN => '', OUT => ''),
            'notes' => array(),
            'hints' => array(),
            'overliburl' => array(IN => array(), OUT => array()),
            'overlibwidth' => 0,
            'overlibheight' => 0,
            'overlibcaption' => array(IN => '', OUT => ''),
            'labeloutlinecolour' => new WMColour(0, 0, 0),
            'labelbgcolour' => new WMColour(255, 255, 255),
            'labelfontcolour' => new WMColour(0, 0, 0),
            'labelfontshadowcolour' => new WMColour('none'),
            'aiconoutlinecolour' => new WMColour(0, 0, 0),
            'aiconfillcolour' => new WMColour('copy'), // copy from the node label
            'resolvedColours' => array(
                'labeloutline' => null,
                'labelbg' => null,
                'labelfont' => null,
                'labelfontshadow' => null,
                'aiconfill' => null,
                'aiconoutline' => null
            ),
            'labeloffset' => '',
            'labeloffsetx' => 0,
            'labeloffsety' => 0,
            'zorder' => 600,
            'max_bandwidth_in' => 100,
            'max_bandwidth_out' => 100,
            'max_bandwidth_in_cfg' => '100',
            'max_bandwidth_out_cfg' => '100'
        );

        $this->width = 0;
        $this->height = 0;
        $this->centre_x = 0;
        $this->centre_y = 0;
        $this->polar = false;
        $this->pos_named = false;
        $this->image = null;
        $this->drawable = false;

        $this->reset($owner);
    }

    function isTemplate()
    {
        return is_null($this->x);
    }

    function my_type()
    {
        return "NODE";
    }

    public function getPosition()
    {
        return new WMPoint($this->x, $this->y);
    }

    public function setPosition($point)
    {
        $this->x = $point->x;
        $this->y = $point->y;
        $this->position = $point;
    }

    public function cleanUp()
    {
        parent::cleanUp();

        if (isset($this->image)) {
            imagedestroy($this->image);
        }
        $this->owner = null;
        $this->parent = null;
        $this->descendents = null;
        $this->image = null;
    }


    /**
     * @return WMColour
     */
    protected function calculateFillColour()
    {
        $labelFillColour = new WMColour('none');

        // if a target is specified, and you haven't forced no background, then the background will
        // come from the SCALE in USESCALE
        if (!empty($this->targets) && $this->usescale != 'none') {
            if ($this->scalevar == 'in') {
                $labelFillColour = $this->colours[IN];
            }

            if ($this->scalevar == 'out') {
                $labelFillColour = $this->colours[OUT];

                return $labelFillColour;
            }
            return $labelFillColour;
        } else {
            $labelFillColour = $this->labelbgcolour;

            return $labelFillColour;
        }
    }

    /**
     * @return null
     */
    protected function calculateIconFillColour()
    {
        $iconFillColour = null;
        if (!empty($this->targets) && $this->useiconscale != 'none') {
            wm_debug("Colorising the icon\n");
            $percent = 0;
            $val = 0;

            if ($this->iconscalevar == 'in') {
                $percent = $this->percentUsages[IN];
                $val = $this->absoluteUsages[IN];
            }
            if ($this->iconscalevar == 'out') {
                $percent = $this->percentUsages[OUT];
                $val = $this->absoluteUsages[OUT];
            }

            if ($this->iconscaletype == 'percent') {
                list($iconFillColour, $node_iconscalekey, $icontag) =
                    $this->owner->scales[$this->useiconscale]->ColourFromValue($percent, $this->name);

                return $iconFillColour;
            } else {
                // use the absolute value if we aren't doing percentage scales.
                list($iconFillColour, $node_iconscalekey, $icontag) =
                    $this->owner->scales[$this->useiconscale]->ColourFromValue($val, $this->name, false);

                return $iconFillColour;
            }
        }

        return $iconFillColour;
    }

    private function getDirectionList()
    {
        if ($this->scalevar == 'in') {
            return array(IN);
        }

        return array(OUT);
    }


    function preCalculateColours(&$owner)
    {
        wm_debug("Trace");

        $labelFillColour = $this->calculateFillColour();
        $iconFillColour = $this->calculateIconFillColour();

        $aiconFillColour = $this->aiconfillcolour;
        $aiconInkColour = $this->aiconoutlinecolour;

        // if useiconscale isn't defined, use the static colours defined by AICONFILLCOLOR and AICONOUTLINECOLOR
        // (or copy the colour from the label fill colour)
        if ($this->useiconscale == 'none') {
            if ($aiconFillColour->isCopy() && !$labelFillColour->isNone()) {
                $aiconFillColour = $labelFillColour;
            }
        } else {
            // if useiconscale IS defined, use that to figure out the file colour
            $aiconFillColour = $iconFillColour;
        }

        $this->resolvedColours['labelfill'] = $labelFillColour;
        $this->resolvedColours['iconfill'] = $iconFillColour;
        $this->resolvedColours['aiconfill'] = $aiconFillColour;
        $this->resolvedColours['aiconoutline'] = $aiconInkColour;

        foreach ($this->resolvedColours as $k => $v) {
            wm_debug("%s: %s", $k, $v);
        }
    }

    function preCalculateGeometry(&$owner)
    {
        wm_debug("Trace");

        // First, figure out the icon
        if ($this->iconfile != '') {
            wm_debug("Has icon - creating subcomponent");
            $iconImageRef = null;
            $icon_w = 0;
            $icon_h = 0;

            if (WMNodeArtificialIcon::isAICONName($this->iconfile)) {
                wm_debug("Artificial");

                $iconObj = WMNodeArtificialIcon::createAICON($this->iconfile, $this, $aiconInkColour, $aiconFillColour,
                    $iconFillColour,
                    $labelFillColour);
            } else {
                wm_debug("Legit Image");
                $iconObj = new WMNodeImageIcon($this, $this->owner->ProcessString($this->iconfile, $this),
                    $this->iconscalew, $this->iconscaleh);
            }

            wm_debug($iconObj);

            $iconObj->calculateGeometry();
            //XXX - this isn't correct
            //$iconObj->preRender();

            $iconImageRef = $iconObj->getImageRef();

            if ($iconImageRef) {

                $icon_bb = $iconObj->getBoundingBox();

                // $this->boundingboxes[] = array($icon_x1, $icon_y1, $icon_x2, $icon_y2);
            }

            $this->subObjects [] = $iconObj;

        }

        // figure out a bounding rectangle for the label
        if ($this->label != '') {
            wm_debug("Has label - creating subcomponent");
            $labelObj = new WMNodeLabel($this);

            $this->processedLabel = $owner->processString($this->label, $this, true, true);

            // if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
            // hopefully that will preserve enough information to show cool stuff without leaking info
            if ($owner->get_hint('screenshot_mode') == 1) {
                $this->processedLabel = WMUtility::stringAnonymise($this->processedLabel);
            }

            $labelObj->calculateGeometry($this->processedLabel, $this->labelfont);

            $this->subObjects [] = $labelObj;

//            $labelObj->preRender($labelFillColour, $this->labeloutlinecolour, $this->labelfontshadowcolour,
//                $this->labelfontcolour, $this->owner->selected);
        }
    }

    /***
     * precalculate the colours to be used, and the bounding boxes for labels and icons (if they exist)
     *
     * This is the only stuff that needs to be done if we're doing an editing pass. No actual drawing is necessary.
     *
     * TODO: write this.
     */
    function preCalculate(&$owner)
    {
        wm_debug("------------------------------------------------");
        wm_debug("Calculating node geometry for %s", $this);

        // don't bother drawing if it's a template
        if ($this->isTemplate()) {
            wm_debug("%s is a pure template. Skipping.", $this);
            return;
        }

        // apparently, some versions of the gd extension will crash if we continue...
        if ($this->label == '' && $this->iconfile == '') {
            wm_debug("%s has no label OR icon. Skipping.", $this);
            return;
        }

        $this->drawable = true;

        $this->preCalculateColours($owner);
        $this->preCalculateGeometry($owner);

        wm_debug("------------------------------------------------");
    }

    function colourizeImage($imageRef, $tintColour)
    {

        list ($red, $green, $blue) = $tintColour->getComponents();

        // TODO - bug? Shouldn't this be per-map and not per-node?
        // Also, if this was a parameter to this function, it wouldn't need to be a method at all
        if (function_exists("imagefilter") && $this->get_hint("use_imagefilter") == 1) {
            imagefilter($imageRef, IMG_FILTER_COLORIZE, $red, $green, $blue);
        } else {
            imagecolorize($imageRef, $red, $green, $blue);
        }
    }

    // make a mini-image, containing this node and nothing else
    // figure out where the real NODE centre is, relative to the top-left corner.
    function preRender(&$map)
    {
        wm_debug("------------------------------------------------");
        wm_debug($this);

        if (!$this->drawable) {
            wm_debug("Skipping undrawable %s", $this);
            return;
        }

        $iconObj = null;
        $labelObj = null;

        // work out the bounding box of the whole thing
        $totalBoundingBox = new WMBoundingBox("TotalBB for $this->name");


        // figure out a bounding rectangle for the label
        if ($this->label != '') {
            wm_debug("Has label - creating subcomponent");
            $labelObj = new WMNodeLabel($this);

            $this->processedLabel = $map->processString($this->label, $this, true, true);

            // if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
            // hopefully that will preserve enough information to show cool stuff without leaking info
            if ($map->get_hint('screenshot_mode') == 1) {
                $this->processedLabel = WMUtility::stringAnonymise($this->processedLabel);
            }

            $labelObj->calculateGeometry($this->processedLabel, $this->labelfont);

            $labelObj->preRender($this->resolvedColours['labelfill'], $this->labeloutlinecolour,
                $this->labelfontshadowcolour,
                $this->labelfontcolour, $this->owner->selected);
        }

        // figure out a bounding rectangle for the icon
        if ($this->iconfile != '') {
            wm_debug("Has icon - creating subcomponent");
            $iconImageRef = null;
            $icon_w = 0;
            $icon_h = 0;

            if (WMNodeArtificialIcon::isAICONName($this->iconfile)) {
                wm_debug("Artificial");

                # $iconObj = new WMNodeArtificialIcon($this, $aiconInkColour, $aiconFillColour, $iconFillColour,                     $labelFillColour);
                $iconObj = WMNodeArtificialIcon::createAICON($this->iconfile, $this, $aiconInkColour, $aiconFillColour,
                    $iconFillColour,
                    $labelFillColour);
            } else {
                wm_debug("Legit");
                $iconObj = new WMNodeImageIcon($this, $this->owner->ProcessString($this->iconfile, $this),
                    $this->iconscalew, $this->iconscaleh);
            }

            wm_debug($iconObj);

            $iconObj->calculateGeometry();
            //XXX - this isn't correct
            //$iconObj->preRender();

            $iconImageRef = $iconObj->getImageRef();

            if ($iconImageRef) {

                $icon_bb = $iconObj->getBoundingBox();

                // $this->boundingboxes[] = array($icon_x1, $icon_y1, $icon_x2, $icon_y2);
            }
        }

        // do any offset calculations
        $deltaX = 0;
        $deltaY = 0;
        if (($this->labeloffset != '') && (($this->iconfile != ''))) {
            $this->labeloffsetx = 0;
            $this->labeloffsety = 0;

            if ($iconObj) {
                $icon_bb = $iconObj->getBoundingBox();
            }
            if ($labelObj) {
                $label_bb = $labelObj->getBoundingBox();

                list($deltaX, $deltaY) = WMUtility::calculateOffset(
                    $this->labeloffset,
                    ($icon_bb->width() + $label_bb->width() - 1),
                    ($icon_bb->height() + $label_bb->height())
                );
            }
        }


        if ($iconObj) {
            $brect = $iconObj->getBoundingBox();
            $totalBoundingBox->addRectangle($brect);
            $this->boundingboxes[] = $brect;
        }

        if ($labelObj) {
            $labelObj->translate($deltaX, $deltaY);
            $labelObj->translate($this->labeloffsetx, $this->labeloffsety);
            $brect = $labelObj->getBoundingBox();
            $totalBoundingBox->addRectangle($brect);
            $this->boundingboxes[] = $brect;
        }

        $bbox = $totalBoundingBox->getBoundingRectangle();

        wm_debug("Total bbox is $bbox\n");

        // create TWO imagemap entries - one for the label and one for the icon
        // (so we can have close-spaced icons better)

//            $temp_width = $bbox_x2 - $bbox_x1;
//            $temp_height = $bbox_y2 - $bbox_y1;

        if ($bbox->width() + $bbox->height() == 0) {
            wm_debug("0-size bounding box. Nothing to draw for %s", $this);
            return;
        }

        // create an image of that size and draw into it
        $node_im = imagecreatetruecolor($bbox->width(), $bbox->height());
        if ($node_im === false) {
            throw new WeathermapInternalFail("Failed to create node image");
        }
        // ImageAlphaBlending($node_im, false);
        imagesavealpha($node_im, true);

        $nothing = imagecolorallocatealpha($node_im, 128, 0, 0, 127);
        $nothing = imagecolorallocatealpha($node_im, 128, 0, 0, 0);
        imagefill($node_im, 0, 0, $nothing);

//            $label_x1 -= $bbox_x1;
//            $label_x2 -= $bbox_x1;
//            $label_y1 -= $bbox_y1;
//            $label_y2 -= $bbox_y1;
//
//            $icon_x1 -= $bbox_x1;
//            $icon_y1 -= $bbox_y1;

        // used in draw() to position the whole node image around the centre point
        $this->centre_x = $this->x - $this->boundingboxes[0]->width() / 2;
        $this->centre_y = $this->y - $this->boundingboxes[0]->height() / 2;

        // Draw the icon, if any
        if (isset($iconImageRef)) {
            $iconObj->draw($node_im, $this->x, $this->y);
        }

        // Draw the label, if any
        if ($this->label != '') {
            //          $labelObj->translate(-$bbox_x1 + $deltaX + $this->labeloffsetx, -$bbox_y1 + $deltaY + $this->labeloffsety);
            $labelObj->draw($node_im, $this->centre_x + $deltaX, $this->centre_y + $deltaY);
        }

        $this->image = $node_im;
    }

    function isRelativePositionResolved()
    {
        return $this->relative_resolved;
    }

    function isRelativePositioned()
    {
        if ($this->relative_to != "") {
            return true;
        }

        return false;
    }

    function getRelativeAnchor()
    {
        return $this->relative_to;
    }

    function resolveRelativePosition($anchorNode)
    {
        $anchorPosition = $anchorNode->getPosition();

        if ($this->polar) {
            // treat this one as a POLAR relative coordinate.
            // - draw rings around a node!
            $angle = $this->x;
            $distance = $this->y;

            $now = $anchorPosition->copy();
            $now->translatePolar($angle, $distance);
            wm_debug("POLAR $this -> $now\n");
            $this->setPosition($now);
            $this->relative_resolved = true;

            return true;
        }

        if ($this->pos_named) {
            $off_name = $this->relative_name;
            if (isset($anchorNode->named_offsets[$off_name])) {
                $now = $anchorPosition->copy();
                $now->translate(
                    $anchorNode->named_offsets[$off_name][0],
                    $anchorNode->named_offsets[$off_name][1]
                );
                wm_debug("NAMED OFFSET $this -> $now\n");
                $this->setPosition($now);
                $this->relative_resolved = true;

                return true;
            }
            wm_debug("Fell through named offset.\n");

            return false;
        }

        // resolve the relative stuff
        $now = $this->getPosition();
        $now->translate($anchorPosition->x, $anchorPosition->y);

        wm_debug("OFFSET $this -> $now\n");
        $this->setPosition($now);
        $this->relative_resolved = true;

        return true;
    }

    // draw the node, using the pre_render() output
    function draw($im, &$map)
    {
        // take the offset we figured out earlier, and just blit the image on. Who says "blit" anymore?

        // it's possible that there is no image, so better check.
        if (!is_null($this->image)) {
            imagealphablending($im, true);
            imagecopy($im, $this->image, $this->x - $this->centre_x, $this->y - $this->centre_y, 0, 0,
                imagesx($this->image), imagesy($this->image));
        }

        // XXX - Hiding this here so Weathermap::drawMapImage doesn't need to know about it
        $this->makeImageMapAreas();
    }

    private function makeImageMapAreas()
    {
        $index = 0;
        foreach ($this->boundingboxes as $bbox) {
            $areaName = "NODE:N" . $this->id . ":" . $index;
            $newArea = new HTML_ImageMap_Area_Rectangle($areaName, "", array($bbox->asArray()));
            wm_debug("Adding imagemap area $bbox\n");
            $this->imageMapAreas[] = $newArea;
            $this->imap_areas[] = $areaName; // XXX - what is this used for?
            $index++;
        }
    }

    function getTemplateObject()
    {
        return $this->owner->getNode($this->template);
    }

    function copyFrom(&$source)
    {
        wm_debug("Initialising NODE $this->name from $source->name\n");
        assert('is_object($source)');

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            if ($fld != 'template') {
                $this->$fld = $source->$fld;
            }
        }
    }

    public function getConfig()
    {
        $output = '';

        wm_debug("Writing config for node $this->name\n");
        # $output .= "# ID ".$this->id." - first seen in ".$this->defined_in."\n";

        // This allows the editor to wholesale-replace a single node's configuration
        // at write-time - it should include the leading NODE xyz line (to allow for renaming)
        if ($this->config_override != '') {
            $output = $this->config_override . "\n";

            return ($output);
        }

        // this is our template. Anything we do different should be written
        $default_default = $this->getTemplateObject();

        wm_debug("Writing config for NODE $this->name against $this->template\n");

        $basic_params = array(
            array("fieldName" => 'label', "configKeyword" => 'LABEL', "type" => CONFIG_TYPE_LITERAL),
            array("fieldName" => 'zorder', "configKeyword" => 'ZORDER', "type" => CONFIG_TYPE_LITERAL),
            array("fieldName" => 'labeloffset', "configKeyword" => 'LABELOFFSET', "type" => CONFIG_TYPE_LITERAL),
            array("fieldName" => 'labelfont', "configKeyword" => 'LABELFONT', "type" => CONFIG_TYPE_LITERAL),
            array("fieldName" => 'labelangle', "configKeyword" => 'LABELANGLE', "type" => CONFIG_TYPE_LITERAL),

            array(
                "fieldName" => 'aiconoutlinecolour',
                "configKeyword" => 'AICONOUTLINECOLOR',
                "type" => CONFIG_TYPE_COLOR
            ),
            array("fieldName" => 'aiconfillcolour', "configKeyword" => 'AICONFILLCOLOR', "type" => CONFIG_TYPE_COLOR),
            array(
                "fieldName" => 'labeloutlinecolour',
                "configKeyword" => 'LABELOUTLINECOLOR',
                "type" => CONFIG_TYPE_COLOR
            ),
            array(
                "fieldName" => 'labelfontshadowcolour',
                "configKeyword" => 'LABELFONTSHADOWCOLOR',
                "type" => CONFIG_TYPE_COLOR
            ),
            array("fieldName" => 'labelbgcolour', "configKeyword" => 'LABELBGCOLOR', "type" => CONFIG_TYPE_COLOR),
            array("fieldName" => 'labelfontcolour', "configKeyword" => 'LABELFONTCOLOR', "type" => CONFIG_TYPE_COLOR)
        );

        $output .= $this->getConfigSimple($basic_params, $default_default);

        // IN/OUT are the same, so we can use the simpler form here
        if ($this->infourl[IN] != $default_default->infourl[IN]) {
            $output .= "\tINFOURL " . $this->infourl[IN] . "\n";
        }

        if ($this->overlibcaption[IN] != $default_default->overlibcaption[IN]) {
            $output .= "\tOVERLIBCAPTION " . $this->overlibcaption[IN] . "\n";
        }

        // IN/OUT are the same, so we can use the simpler form here
        if ($this->notestext[IN] != $default_default->notestext[IN]) {
            $output .= "\tNOTES " . $this->notestext[IN] . "\n";
        }

        if ($this->overliburl[IN] != $default_default->overliburl[IN]) {
            $output .= "\tOVERLIBGRAPH " . join(" ", $this->overliburl[IN]) . "\n";
        }

        $val = $this->iconscalew . " " . $this->iconscaleh . " " . $this->iconfile;

        $comparison = $default_default->iconscalew . " " . $default_default->iconscaleh . " " . $default_default->iconfile;

        if ($val != $comparison) {
            $output .= "\tICON ";
            if ($this->iconscalew > 0) {
                $output .= $this->iconscalew . " " . $this->iconscaleh . " ";
            }
            $output .= ($this->iconfile == '' ? 'none' : $this->iconfile) . "\n";
        }

        if ($this->targets != $default_default->targets) {
            $output .= "\tTARGET";

            foreach ($this->targets as $target) {
                $output .= " " . $target->asConfig();
            }
            $output .= "\n";
        }

        $val = $this->usescale . " " . $this->scalevar . " " . $this->scaletype;
        $comparison = $default_default->usescale . " " . $default_default->scalevar . " " . $default_default->scaletype;

        if (($val != $comparison)) {
            $output .= "\tUSESCALE " . $val . "\n";
        }

        $val = $this->useiconscale . " " . $this->iconscalevar;
        $comparison = $default_default->useiconscale . " " . $default_default->iconscalevar;

        if ($val != $comparison) {
            $output .= "\tUSEICONSCALE " . $val . "\n";
        }

        $val = $this->labeloffsetx . " " . $this->labeloffsety;
        $comparison = $default_default->labeloffsetx . " " . $default_default->labeloffsety;

        if ($comparison != $val) {
            $output .= "\tLABELOFFSET " . $val . "\n";
        }

        $val = $this->x . " " . $this->y;
        $comparison = $default_default->x . " " . $default_default->y;

        if ($val != $comparison) {
            if ($this->relative_to == '') {
                $output .= "\tPOSITION " . $val . "\n";
            } else {
                if ($this->polar) {
                    $output .= "\tPOSITION " . $this->relative_to . " " . $this->original_x . "r" . $this->original_y . "\n";
                } elseif ($this->pos_named) {
                    $output .= "\tPOSITION " . $this->relative_to . ":" . $this->relative_name . "\n";
                } else {
                    $output .= "\tPOSITION " . $this->relative_to . " " . $this->original_x . " " . $this->original_y . "\n";
                }
            }
        }

        $output .= $this->getMaxValueConfig($default_default);

        $output .= $this->getConfigHints($default_default);

        foreach ($this->named_offsets as $off_name => $off_pos) {
            // if the offset exists with different values, or
            // doesn't exist at all in the template, we need to write
            // some config for it
            if ((array_key_exists($off_name, $default_default->named_offsets))) {
                $offsetX = $default_default->named_offsets[$off_name][0];
                $offsetY = $default_default->named_offsets[$off_name][1];

                if ($offsetX != $off_pos[0] || $offsetY != $off_pos[1]) {
                    $output .= sprintf("\tDEFINEOFFSET %s %d %d\n", $off_name, $off_pos[0], $off_pos[1]);
                }
            } else {
                $output .= sprintf("\tDEFINEOFFSET %s %d %d\n", $off_name, $off_pos[0], $off_pos[1]);
            }
        }

        if ($output != '') {
            $output = "NODE " . $this->name . "\n$output\n";
        }

        return ($output);
    }

    function asJSCore()
    {
        $output = "";

        $output .= "x:" . (is_null($this->x) ? "'null'" : $this->x) . ", ";
        $output .= "y:" . (is_null($this->y) ? "'null'" : $this->y) . ", ";
        $output .= "\"id\":" . $this->id . ", ";
        $output .= "ox:" . $this->original_x . ", ";
        $output .= "oy:" . $this->original_y . ", ";
        $output .= "relative_to:" . WMUtility::jsEscape($this->relative_to) . ", ";
        $output .= "label:" . WMUtility::jsEscape($this->label) . ", ";
        $output .= "name:" . WMUtility::jsEscape($this->name) . ", ";
        $output .= "infourl:" . WMUtility::jsEscape($this->infourl[IN]) . ", ";
        $output .= "overlibcaption:" . WMUtility::jsEscape($this->overlibcaption[IN]) . ", ";
        $output .= "overliburl:" . WMUtility::jsEscape(join(" ", $this->overliburl[IN])) . ", ";
        $output .= "overlibwidth:" . $this->overlibheight . ", ";
        $output .= "overlibheight:" . $this->overlibwidth . ", ";
        if (sizeof($this->boundingboxes) > 0) {
            $output .= sprintf("bbox:[%d,%d, %d,%d], ", $this->boundingboxes[0][0], $this->boundingboxes[0][1],
                $this->boundingboxes[0][2], $this->boundingboxes[0][3]);
        } else {
            $output .= "bbox: [], ";
        }

        if (preg_match("/^(none|nink|inpie|outpie|box|rbox|gauge|round)$/", $this->iconfile)) {
            $output .= "iconfile:" . WMUtility::jsEscape("::" . $this->iconfile);
        } else {
            $output .= "iconfile:" . WMUtility::jsEscape($this->iconfile);
        }

        return $output;
    }

    function asJS($type = "Node", $prefix = "N")
    {
        return parent::asJS($type, $prefix);
    }

    public function getValue($name)
    {
        wm_debug("Fetching %s from %s\n", $name, $this);
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new WeathermapRuntimeWarning("NoSuchProperty");
    }
}
// vim:ts=4:sw=4:
