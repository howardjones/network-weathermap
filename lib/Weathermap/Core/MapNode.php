<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License


namespace Weathermap\Core;


class MapNode extends MapDataItem
{
    public $drawable;
    public $x;
    public $y;
    public $original_x;
    public $original_y;
    public $relative_resolved;
    public $width;
    public $height;
    public $label;
    public $processedLabel;
    public $labelangle;
    public $selected = 0;
    public $position;

    public $pos_named;
    public $named_offsets;
    public $relative_name;

    public $iconfile;
    public $iconscalew;
    public $iconscaleh;
    public $labeloffset;
    public $labeloffsetx;
    public $labeloffsety;

    /** @var  WMColour $labelbgcolour */
    public $labelbgcolour;
    /** @var  WMColour $labeloutlinecolour */
    public $labeloutlinecolour;
    /** @var  WMColour $labelfontcolour */
    public $labelfontcolour;
    /** @var  WMColour $labelfontshadowcolour */
    public $labelfontshadowcolour;

    public $labelfont;

    public $useiconscale;
    public $iconscaletype;
    public $iconscalevar;
    public $image;
    public $centre_x; // TODO these were for ORIGIN
    public $centre_y; // TODO these were for ORIGIN
    public $relative_to;
    public $polar;
    public $boundingboxes = array();
    /** @var  WMColour $aiconfillcolour */
    public $aiconfillcolour;
    /** @var  WMColour $aiconoutlinecolour */
    public $aiconoutlinecolour;

    /**
     * WeatherMapNode constructor.
     *
     * @param string $name
     * @param string $template
     * @param Map $owner
     */
    public function __construct($name, $template, $owner)
    {
        parent::__construct();

        $this->name = $name;
        $this->owner = $owner;
        $this->template = $template;

        $this->width = 0;
        $this->height = 0;
        $this->centre_x = 0;
        $this->centre_y = 0;
        $this->polar = false;
        $this->pos_named = false;
        $this->image = null;
        $this->drawable = false;

        $this->inherit_fieldlist = array
        (
            'boundingboxes' => array(),
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
            'relative_resolved' => false,
            'x' => null,
            'y' => null,
            'inscalekey' => '', 'outscalekey' => '',
            #'incolour'=>-1,'outcolour'=>-1,
            'original_x' => 0,
            'original_y' => 0,
            'labelangle' => 0,
            'iconfile' => '',
            'iconscalew' => 0,
            'iconscaleh' => 0,
            'targets' => array(),
            'named_offsets' => array(),
            'infourl' => array(IN => '', OUT => ''),
            'maxValuesConfigured' => array(IN => "100", OUT => "100"),
            'maxValues' => array(IN => null, OUT => null),
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

            'labeloffset' => '',
            'labeloffsetx' => 0,
            'labeloffsety' => 0,
            'zorder' => 600,
        );

        $this->reset($owner);
    }

    public function my_type()
    {
        return "NODE";
    }

    /**
     * @param resource $imageRef
     * @param Map $map
     */
    public function preRender(&$map)
    {
        if (!$this->drawable) {
            wm_debug("Skipping undrawable %s", $this);
            return;
        }

        // don't bother drawing if it's a template
        if ($this->isTemplate()) {
            return;
        }

        // apparently, some versions of the gd extension will crash
        // if we continue...
        if ($this->label == '' && $this->iconfile == '') {
            return;
        }

        // start these off with sensible values, so that bbox
        // calculations are easier.

        $boundingBox = new WMBoundingBox();
        $labelBox = new WMRectangle($this->x, $this->y, $this->x, $this->y);
        $iconBox = new WMRectangle($this->x, $this->y, $this->x, $this->y);
        $textPoint = new WMPoint($this->x, $this->y);

        $labelBoxWidth = 0;
        $labelBoxHeight = 0;
        $iconWidth = 0;
        $iconHeight = 0;

        $labelColour = new WMColour('none');

        // if a target is specified, and you haven't forced no background, then the background will
        // come from the SCALE in USESCALE
        if (!empty($this->targets) && $this->usescale != 'none') {
            $percentValue = 0;

            if ($this->scalevar == 'in') {
                $percentValue = $this->percentUsages[IN];
                $labelColour = $this->colours[IN];
            }

            if ($this->scalevar == 'out') {
                $percentValue = $this->percentUsages[OUT];
                $labelColour = $this->colours[OUT];
            }
        } elseif (!$this->labelbgcolour->isNone()) {
            wm_debug("labelbgcolour=%s\n", $this->labelbgcolour);
            $labelColour = $this->labelbgcolour;
        }

        $iconColour = null;
        if (!empty($this->targets) && $this->useiconscale != 'none') {
            wm_debug("Colorising the icon\n");
            $iconColour = $this->calculateIconColour($map);
        }

        // figure out a bounding rectangle for the label
        if ($this->label != '') {
            $paddingConstant = 4.0;
            $paddingFactor = 1.0;

            $this->processedLabel = $map->ProcessString($this->label, $this, true, true);

            // if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
            // hopefully that will preserve enough information to show cool stuff without leaking info
            if ($map->get_hint('screenshot_mode') == 1) {
                $this->processedLabel = Utility::stringAnonymise($this->processedLabel);
            }

            $fontObject = $this->owner->fonts->getFont($this->labelfont);
            list($stringWidth, $stringHeight) = $fontObject->calculateImageStringSize($this->processedLabel);

            if ($this->labelangle == 90 || $this->labelangle == 270) {
                $labelBoxWidth = $stringHeight * $paddingFactor + $paddingConstant;
                $labelBoxHeight = $stringWidth * $paddingFactor + $paddingConstant;
            }
            if ($this->labelangle == 0 || $this->labelangle == 180) {
                $labelBoxWidth = $stringWidth * $paddingFactor + $paddingConstant;
                $labelBoxHeight = $stringHeight * $paddingFactor + $paddingConstant;
            }

            wm_debug("Node->pre_render: " . $this->name . " Label Metrics are: $stringWidth x $stringHeight -> $labelBoxWidth x $labelBoxHeight\n");

            if ($this->labelangle == 90) {
                $textPoint = new WMPoint($stringHeight / 2, $stringWidth / 2);
            }
            if ($this->labelangle == 270) {
                $textPoint = new WMPoint(-$stringHeight / 2, -$stringWidth / 2);
            }
            if ($this->labelangle == 0) {
                $textPoint = new WMPoint(-$stringWidth / 2, $stringHeight / 2);
            }
            if ($this->labelangle == 180) {
                $textPoint = new WMPoint($stringWidth / 2, -$stringHeight / 2);
            }

            $textPoint->translate($this->x, $this->y);

            $labelBox = new WMRectangle(-$labelBoxWidth / 2, -$labelBoxHeight / 2, $labelBoxWidth / 2, $labelBoxHeight / 2);
            $labelBox->translate($this->x, $this->y);

            wm_debug("LABEL at %s\n", $labelBox);

            $this->width = $labelBoxWidth;
            $this->height = $labelBoxHeight;
        }

        // figure out a bounding rectangle for the icon
        if ($this->iconfile != '') {
            $icon_im = null;
            $iconWidth = 0;
            $iconHeight = 0;

            if ($this->iconfile == 'rbox' || $this->iconfile == 'box' || $this->iconfile == 'round' || $this->iconfile == 'inpie' || $this->iconfile == 'outpie' || $this->iconfile == 'gauge' || $this->iconfile == 'nink') {
                $icon_im = $this->drawArtificialIcon($map, $labelColour);
            } else {
                $icon_im = $this->drawRealIcon($map, $iconColour);
            }

            if ($icon_im) {
                $iconWidth = imagesx($icon_im);
                $iconHeight = imagesy($icon_im);

                $iconBox = new WMRectangle(-$iconWidth / 2, -$iconHeight / 2, $iconWidth / 2, $iconHeight / 2);
                $iconBox->translate($this->x, $this->y);

                $this->width = $iconWidth;
                $this->height = $iconHeight;

                $this->boundingboxes[] = $iconBox->asArray();
                $boundingBox->addRectangle($iconBox);
            }
        }

        // do any offset calculations
        $dx = 0;
        $dy = 0;
        if (($this->labeloffset != '') && (($this->iconfile != ''))) {
            $this->labeloffsetx = 0;
            $this->labeloffsety = 0;

            list($dx, $dy) = Utility::calculateOffset(
                $this->labeloffset,
                ($iconWidth + $labelBoxWidth - 1),
                ($iconHeight + $labelBoxHeight)
            );
        }

        $labelBox->translate($this->labeloffsetx + $dx, $this->labeloffsety + $dy);
        $textPoint->translate($this->labeloffsetx + $dx, $this->labeloffsety + $dy);

        // now we have the labelBox in the final position, add it to the bounding box list
        if ($this->label != '') {
            $this->boundingboxes[] = $labelBox->asArray();
            $boundingBox->addRectangle($labelBox);
        }

        // work out the bounding box of the whole thing

        $totalBoundingBox = $boundingBox->getBoundingRectangle();
        $totalBoundingBox->bottomRight->translate(1, 1);

        // create TWO imagemap entries - one for the label and one for the icon
        // (so we can have close-spaced icons better)

        // create an image of that size and draw into it
        $node_im = $this->createTransparentImage($totalBoundingBox->width(), $totalBoundingBox->height());

        $labelBox->translate(-$totalBoundingBox->topLeft->x, -$totalBoundingBox->topLeft->y);
        $iconBox->translate(-$totalBoundingBox->topLeft->x, -$totalBoundingBox->topLeft->y);

        // Draw the icon, if any
        if (isset($icon_im)) {
            imagecopy($node_im, $icon_im, $iconBox->topLeft->x, $iconBox->topLeft->y, 0, 0, imagesx($icon_im), imagesy($icon_im));
            imagedestroy($icon_im);
        }

        // Draw the label, if any
        if ($this->label != '') {
            $textPoint->translate(-$totalBoundingBox->topLeft->x, -$totalBoundingBox->topLeft->y);
            imagealphablending($node_im, true);
            $this->drawLabel($map, $textPoint, $labelColour, $node_im, $labelBox);
        }

        $this->centre_x = $this->x - $totalBoundingBox->topLeft->x;
        $this->centre_y = $this->y - $totalBoundingBox->topLeft->y;

        $this->image = $node_im;

        $this->makeImagemapAreas();
    }

    public function isTemplate()
    {
        return is_null($this->x);
    }

    // make a mini-image, containing this node and nothing else
    // figure out where the real NODE centre is, relative to the top-left corner.

    private function makeImagemapAreas()
    {
        $index = 0;
        foreach ($this->boundingboxes as $bbox) {
            $areaName = "NODE:N" . $this->id . ":" . $index;
            $newArea = new HTMLImagemapAreaRectangle($areaName, "", array($bbox));
            wm_debug("Adding imagemap area [" . join(",", $bbox) . "] => $newArea \n");
            $this->imap_areas[] = $newArea;
            $index++;
        }
    }

    /**
     * precalculate the colours to be used, and the bounding boxes for labels and icons (if they exist)
     *
     * This is the only stuff that needs to be done if we're doing an editing pass. No actual drawing is necessary.
     *
     * @param Map $owner
     *
     */
    public function preCalculate(&$owner)
    {
        wm_debug("------------------------------------------------\n");
        wm_debug("Calculating node geometry for %s\n", $this);

        $this->drawable = false;

        // don't bother drawing if it's a template
        if ($this->isTemplate()) {
            wm_debug("%s is a pure template. Skipping.\n", $this);
            return;
        }

        // apparently, some versions of the gd extension will crash if we continue...
        if ($this->label == '' && $this->iconfile == '') {
            wm_debug("%s has no label OR icon. Skipping.\n", $this);
            return;
        }

        $this->drawable = true;
    }

    // draw the node, using the pre_render() output
    public function Draw($imageRef)
    {
        if (!$this->drawable) {
            wm_debug("Skipping undrawable %s\n", $this);
            return;
        }

        // take the offset we figured out earlier, and just blit
        // the image on. Who says "blit" anymore?

        // it's possible that there is no image, so better check.
        if (isset($this->image)) {
            imagealphablending($imageRef, true);
            imagecopy($imageRef, $this->image, $this->x - $this->centre_x, $this->y - $this->centre_y, 0, 0, imagesx($this->image), imagesy($this->image));
        }
    }

    // take the pre-rendered node and write it to a file so that
    // the editor can get at it.
    public function WriteConfig()
    {
        if ($this->config_override != '') {
            return $this->config_override . "\n";
        }

        $output = '';

        // This allows the editor to wholesale-replace a single node's configuration
        // at write-time - it should include the leading NODE xyz line (to allow for renaming)
        $template_item = $this->owner->nodes[$this->template];

        wm_debug("Writing config for NODE $this->name against $this->template\n");

        $basic_params = array(
            # array('template','TEMPLATE',self::CONFIG_TYPE_LITERAL),
            array('label', 'LABEL', self::CONFIG_TYPE_LITERAL),
            array('zorder', 'ZORDER', self::CONFIG_TYPE_LITERAL),
            array('labeloffset', 'LABELOFFSET', self::CONFIG_TYPE_LITERAL),
            array('labelfont', 'LABELFONT', self::CONFIG_TYPE_LITERAL),
            array('labelangle', 'LABELANGLE', self::CONFIG_TYPE_LITERAL),
            array('overlibwidth', 'OVERLIBWIDTH', self::CONFIG_TYPE_LITERAL),
            array('overlibheight', 'OVERLIBHEIGHT', self::CONFIG_TYPE_LITERAL),

            array('aiconoutlinecolour', 'AICONOUTLINECOLOR', self::CONFIG_TYPE_COLOR),
            array('aiconfillcolour', 'AICONFILLCOLOR', self::CONFIG_TYPE_COLOR),
            array('labeloutlinecolour', 'LABELOUTLINECOLOR', self::CONFIG_TYPE_COLOR),
            array('labelfontshadowcolour', 'LABELFONTSHADOWCOLOR', self::CONFIG_TYPE_COLOR),
            array('labelbgcolour', 'LABELBGCOLOR', self::CONFIG_TYPE_COLOR),
            array('labelfontcolour', 'LABELFONTCOLOR', self::CONFIG_TYPE_COLOR)
        );

        # TEMPLATE must come first. DEFAULT
        if ($this->template != 'DEFAULT' && $this->template != ':: DEFAULT ::') {
            $output .= "\tTEMPLATE " . $this->template . "\n";
        }

        $output .= $this->getSimpleConfig($basic_params, $template_item);

        // IN/OUT are the same, so we can use the simpler form here
        if ($this->infourl[IN] != $template_item->infourl[IN]) {
            $output .= "\tINFOURL " . $this->infourl[IN] . "\n";
        }

        if ($this->overlibcaption[IN] != $template_item->overlibcaption[IN]) {
            $output .= "\tOVERLIBCAPTION " . $this->overlibcaption[IN] . "\n";
        }

        // IN/OUT are the same, so we can use the simpler form here
        if ($this->notestext[IN] != $template_item->notestext[IN]) {
            $output .= "\tNOTES " . $this->notestext[IN] . "\n";
        }

        if ($this->overliburl[IN] != $template_item->overliburl[IN]) {
            $output .= "\tOVERLIBGRAPH " . join(" ", $this->overliburl[IN]) . "\n";
        }

        $val = $this->iconscalew . " " . $this->iconscaleh . " " . $this->iconfile;

        $comparison = $template_item->iconscalew . " " . $template_item->iconscaleh . " " . $template_item->iconfile;

        if ($val != $comparison) {
            $output .= "\tICON ";
            if ($this->iconscalew > 0) {
                $output .= $this->iconscalew . " " . $this->iconscaleh . " ";
            }
            $output .= ($this->iconfile == '' ? 'none' : $this->iconfile) . "\n";
        }

        if ($this->targets != $template_item->targets) {
            $output .= "\tTARGET";

            foreach ($this->targets as $target) {
                $output .= " " . $target->asConfig();
            }

            $output .= "\n";
        }

        $val = $this->usescale . " " . $this->scalevar . " " . $this->scaletype;
        $comparison = $template_item->usescale . " " . $template_item->scalevar . " " . $template_item->scaletype;

        if (($val != $comparison)) {
            $output .= "\tUSESCALE " . $val . "\n";
        }

        $val = $this->useiconscale . " " . $this->iconscalevar;
        $comparison = $template_item->useiconscale . " " . $template_item->iconscalevar;

        if ($val != $comparison) {
            $output .= "\tUSEICONSCALE " . $val . "\n";
        }

        $val = $this->labeloffsetx . " " . $this->labeloffsety;
        $comparison = $template_item->labeloffsetx . " " . $template_item->labeloffsety;

        if ($comparison != $val) {
            $output .= "\tLABELOFFSET " . $val . "\n";
        }

        $val = $this->x . " " . $this->y;
        $comparison = $template_item->x . " " . $template_item->y;

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

        $output .= $this->getMaxValueConfig($template_item, "MAXVALUE");

        $output .= $this->getHintConfig($template_item);

        foreach ($this->named_offsets as $off_name => $off_pos) {
            // if the offset exists with different values, or
            // doesn't exist at all in the template, we need to write
            // some config for it
            if ((array_key_exists($off_name, $template_item->named_offsets))) {
                $offsetX = $template_item->named_offsets[$off_name][0];
                $offsetY = $template_item->named_offsets[$off_name][1];

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

        return $output;
    }

    protected function asJSCore()
    {
        // TODO - this should use json_encode()
        $output = "";

        $output .= "x:" . (is_null($this->x) ? "'null'" : $this->x) . ", ";
        $output .= "y:" . (is_null($this->y) ? "'null'" : $this->y) . ", ";
        $output .= "\"id\":" . $this->id . ", ";
        $output .= "ox:" . $this->original_x . ", ";
        $output .= "oy:" . $this->original_y . ", ";
        $output .= "relative_to:" . Utility::jsEscape($this->relative_to) . ", ";
        $output .= "label:" . Utility::jsEscape($this->label) . ", ";
        $output .= "name:" . Utility::jsEscape($this->name) . ", ";
        $output .= "infourl:" . Utility::jsEscape($this->infourl[IN]) . ", ";
        $output .= "overlibcaption:" . Utility::jsEscape($this->overlibcaption[IN]) . ", ";
        $output .= "overliburl:" . Utility::jsEscape(join(" ", $this->overliburl[IN])) . ", ";
        $output .= "overlibwidth:" . $this->overlibheight . ", ";
        $output .= "overlibheight:" . $this->overlibwidth . ", ";
        if (sizeof($this->boundingboxes) > 0) {
            $output .= sprintf("bbox:[%d,%d, %d,%d], ", $this->boundingboxes[0][0], $this->boundingboxes[0][1], $this->boundingboxes[0][2], $this->boundingboxes[0][3]);
        } else {
            $output .= "bbox: [], ";
        }

        if (preg_match("/^(none|nink|inpie|outpie|box|rbox|gauge|round)$/", $this->iconfile)) {
            $output .= "iconfile:" . Utility::jsEscape("::" . $this->iconfile);
        } else {
            $output .= "iconfile:" . Utility::jsEscape($this->iconfile);
        }

        return $output;
    }

    public function asJS($type = "Node", $prefix = "N")
    {
        return parent::asJS($type, $prefix);
    }

    public function isRelativePositionResolved()
    {
        return $this->relative_resolved;
    }

    public function isRelativePositioned()
    {
        if ($this->relative_to != "") {
            return true;
        }

        return false;
    }

    public function getRelativeAnchor()
    {
        return $this->relative_to;
    }

    /**
     * @param MapNode $anchorNode
     * @return bool
     */
    public function resolveRelativePosition($anchorNode)
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
        $this->descendents = null;
        $this->image = null;
    }

    public function getValue($name)
    {
        wm_debug("Fetching %s\n", $name);
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new WeathermapInternalFail("NoSuchProperty");
    }

    private function getDirectionList()
    {
        if ($this->scalevar == 'in') {
            return array(IN);
        }

        return array(OUT);
    }

    public function getTemplateObject()
    {
        return $this->owner->getNode($this->template);
    }

    /**
     * @param Map $map
     * @param WMColour $labelColour
     * @return resource
     */
    private function drawArtificialIcon(&$map, $labelColour)
    {
        wm_debug("Artificial Icon type " . $this->iconfile . " for $this->name\n");
        // this is an artificial icon - we don't load a file for it

        $icon_im = $this->createTransparentImage($this->iconscalew, $this->iconscaleh);

        list($finalFillColour, $finalInkColour) = $this->calculateAICONColours($labelColour, $map);

        wm_debug("ink is: $finalInkColour\n");
        wm_debug("fill is: $finalFillColour\n");

        switch ($this->iconfile) {
            case "box":
                $this->drawArtificialIconBox($icon_im, $finalFillColour, $finalInkColour);
                break;
            case "rbox":
                $this->drawArtificialIconRoundedBox($icon_im, $finalFillColour, $finalInkColour);
                break;
            case "round":
                $this->drawArtificialIconRound($icon_im, $finalFillColour, $finalInkColour);
                break;
            case "nink":
                $this->drawArtificialIconNINK($icon_im, $finalInkColour, $map);
                break;
            case "inpie":
                $this->drawArtificialIconPie($icon_im, $finalFillColour, $finalInkColour, IN);
                break;
            case "outpie":
                $this->drawArtificialIconPie($icon_im, $finalFillColour, $finalInkColour, OUT);
                break;
            case "gauge":
                wm_warn('gauge AICON not implemented yet [WMWARN99]');
                break;
        }

        return $icon_im;
    }

    /**
     * @param $map
     * @param $colicon
     * @return resource
     */
    private function drawRealIcon(&$map, $colicon)
    {
        $this->iconfile = $map->ProcessString($this->iconfile, $this);

        wm_debug("Actual image-based icon from " . $this->iconfile . " for $this->name\n");

        $icon_im = null;

        if (is_readable($this->iconfile)) {
            // draw the supplied icon, instead of the labelled box
            if (isset($colicon)) {
                $colour_method = "imagecolorize";
                if (function_exists("imagefilter") && $map->get_hint("use_imagefilter") == 1) {
                    $colour_method = "imagefilter";
                }

                $icon_im = $this->owner->imagecache->imagecreatescaledcolourizedfromfile(
                    $this->iconfile,
                    $this->iconscalew,
                    $this->iconscaleh,
                    $colicon,
                    $colour_method
                );
            } else {
                $icon_im = $this->owner->imagecache->imagecreatescaledfromfile(
                    $this->iconfile,
                    $this->iconscalew,
                    $this->iconscaleh
                );
            }

            if (!$icon_im) {
                wm_warn("Couldn't open ICON: '" . $this->iconfile . "' - is it a PNG, JPEG or GIF? [WMWARN37]\n");
            }
        } else {
            if ($this->iconfile != 'none') {
                wm_warn("ICON '" . $this->iconfile . "' does not exist, or is not readable. Check path and permissions. [WMARN38]\n");
            }
        }
        return $icon_im;
    }

    /**
     * @param Map $map
     * @param WMPoint $textPoint
     * @param WMColour $col
     * @param resource $node_im
     * @param WMRectangle $labelBox
     */
    private function drawLabel(&$map, $textPoint, $col, $node_im, $labelBox)
    {
        wm_debug("Label colour is $col\n");

        // if there's an icon, then you can choose to have no background
        if (!$this->labelbgcolour->isNone()) {
            imagefilledrectangle(
                $node_im,
                $labelBox->topLeft->x,
                $labelBox->topLeft->y,
                $labelBox->bottomRight->x,
                $labelBox->bottomRight->y,
                $col->gdallocate($node_im)
            );
        }

        if ($this->selected) {
            imagerectangle(
                $node_im,
                $labelBox->topLeft->x,
                $labelBox->topLeft->y,
                $labelBox->bottomRight->x,
                $labelBox->bottomRight->y,
                $map->selected
            );
            // would be nice if it was thicker, too...
            imagerectangle(
                $node_im,
                $labelBox->topLeft->x - 1,
                $labelBox->topLeft->y - 1,
                $labelBox->bottomRight->x + 1,
                $labelBox->bottomRight->y + 1,
                $map->selected
            );
        } else {
            $outlineColour = $this->labeloutlinecolour;
            if ($outlineColour->isRealColour()) {
                imagerectangle(
                    $node_im,
                    $labelBox->topLeft->x,
                    $labelBox->topLeft->y,
                    $labelBox->bottomRight->x,
                    $labelBox->bottomRight->y,
                    $outlineColour->gdAllocate($node_im)
                );
            }
        }

        $fontObject = $this->owner->fonts->getFont($this->labelfont);

        $shadowColour = $this->labelfontshadowcolour;
        if ($shadowColour->isRealColour()) {
            $fontObject->drawImageString($node_im, $textPoint->x + 1, $textPoint->y + 1, $this->processedLabel, $shadowColour->gdAllocate($node_im), $this->labelangle);
        }

        $textColour = $this->labelfontcolour;

        if ($textColour->isContrast()) {
            if ($col->isRealColour()) {
                $textColour = $col->getContrastingColour();
            } else {
                wm_warn("You can't make a contrast with 'none'. Guessing black. [WMWARN43]\n");
                $textColour = new WMColour(0, 0, 0);
            }
        }
        $fontObject->drawImageString($node_im, $textPoint->x, $textPoint->y, $this->processedLabel, $textColour->gdAllocate($node_im), $this->labelangle);
    }

    /**
     * @param $map
     * @return WMColour
     */
    private function calculateIconColour(&$map)
    {
        $percentValue = 0;
        $absoluteValue = 0;

        if ($this->iconscalevar == 'in' || $this->iconscalevar == 'out') {
            $channel = constant(strtoupper($this->iconscalevar));
            $percentValue = $this->percentUsages[$channel];
            $absoluteValue = $this->absoluteUsages[$channel];
        }

        if ($this->iconscaletype == 'percent') {
            list($iconColour, $junk, $junk) =
                $map->scales[$this->useiconscale]->colourFromValue($percentValue, $this->name);
        } else {
            // use the absolute value if we aren't doing percentage scales.
            list($iconColour, $junk, $junk) =
                $map->scales[$this->useiconscale]->colourFromValue($absoluteValue, $this->name, false);
        }
        return $iconColour;
    }

    /**
     * @param WMColour $finalFillColour
     * @param resource $icon_im
     * @param WMColour $finalInkColour
     */
    private function drawArtificialIconBox($icon_im, $finalFillColour, $finalInkColour)
    {
        if (!$finalFillColour->isNone()) {
            imagefilledrectangle($icon_im, 0, 0, $this->iconscalew - 1, $this->iconscaleh - 1, $finalFillColour->gdallocate($icon_im));
        }

        if (!$finalInkColour->isNone()) {
            imagerectangle($icon_im, 0, 0, $this->iconscalew - 1, $this->iconscaleh - 1, $finalInkColour->gdallocate($icon_im));
        }
    }

    /**
     * @param WMColour $finalFillColour
     * @param resource $icon_im
     * @param WMColour $finalInkColour
     */
    private function drawArtificialIconRoundedBox($icon_im, $finalFillColour, $finalInkColour)
    {
        if (!$finalFillColour->isNone()) {
            imagefilledroundedrectangle($icon_im, 0, 0, $this->iconscalew - 1, $this->iconscaleh - 1, 4, $finalFillColour->gdallocate($icon_im));
        }

        if (!$finalInkColour->isNone()) {
            imageroundedrectangle($icon_im, 0, 0, $this->iconscalew - 1, $this->iconscaleh - 1, 4, $finalInkColour->gdallocate($icon_im));
        }
    }

    /**
     * @param WMColour $finalFillColour
     * @param resource $icon_im
     * @param WMColour $finalInkColour
     */
    private function drawArtificialIconRound($icon_im, $finalFillColour, $finalInkColour)
    {
        $xRadius = $this->iconscalew / 2 - 1;
        $yRadius = $this->iconscaleh / 2 - 1;

        if (!$finalFillColour->isNone()) {
            imagefilledellipse($icon_im, $xRadius, $yRadius, $xRadius * 2, $yRadius * 2, $finalFillColour->gdallocate($icon_im));
        }

        if (!$finalInkColour->isNone()) {
            imageellipse($icon_im, $xRadius, $yRadius, $xRadius * 2, $yRadius * 2, $finalInkColour->gdallocate($icon_im));
        }
    }

    /**
     * @param $map
     * @param resource $icon_im
     * @param WMColour $finalInkColour
     */
    private function drawArtificialIconNINK($icon_im, $finalInkColour, &$map)
    {
        $xRadius = $this->iconscalew / 2 - 1;
        $yRadius = $this->iconscaleh / 2 - 1;
        $size = $this->iconscalew;
        $quarter = $size / 4;

        $col1 = $this->colours[OUT];
        $col2 = $this->colours[IN];

        assert('!is_null($col1)');
        assert('!is_null($col2)');

        imagefilledarc($icon_im, $xRadius - 1, $yRadius, $size, $size, 270, 90, $col1->gdallocate($icon_im), IMG_ARC_PIE);
        imagefilledarc($icon_im, $xRadius + 1, $yRadius, $size, $size, 90, 270, $col2->gdallocate($icon_im), IMG_ARC_PIE);

        imagefilledarc($icon_im, $xRadius - 1, $yRadius + $quarter, $quarter * 2, $quarter * 2, 0, 360, $col1->gdallocate($icon_im), IMG_ARC_PIE);
        imagefilledarc($icon_im, $xRadius + 1, $yRadius - $quarter, $quarter * 2, $quarter * 2, 0, 360, $col2->gdallocate($icon_im), IMG_ARC_PIE);

        if (!$finalInkColour->isNone()) {
            // XXX - need a font definition from somewhere for NINK text
            $font = 1;

            $instr = $map->ProcessString("{node:this:bandwidth_in:%.1k}", $this);
            $outstr = $map->ProcessString("{node:this:bandwidth_out:%.1k}", $this);

            $fontObject = $this->owner->fonts->getFont($font);
            list($textWidth, $textHeight) = $fontObject->calculateImageStringSize($instr);
            $fontObject->drawImageString($icon_im, $xRadius - $textWidth / 2, $yRadius - $quarter + ($textHeight / 2), $instr, $finalInkColour->gdallocate($icon_im));

            list($textWidth, $textHeight) = $fontObject->calculateImageStringSize($outstr);
            $fontObject->drawImageString($icon_im, $xRadius - $textWidth / 2, $yRadius + $quarter + ($textHeight / 2), $outstr, $finalInkColour->gdallocate($icon_im));

            imageellipse($icon_im, $xRadius, $yRadius, $xRadius * 2, $yRadius * 2, $finalInkColour->gdallocate($icon_im));
        }
    }

    /**
     * @param $which
     * @param WMColour $finalFillColour
     * @param resource $icon_im
     * @param WMColour $finalInkColour
     */
    private function drawArtificialIconPie($icon_im, $finalFillColour, $finalInkColour, $which)
    {
        $percentValue = $this->percentUsages[$which];

        $segmentAngle = clip(($percentValue / 100) * 360, 1, 360);

        $xRadius = $this->iconscalew / 2 - 1;
        $yRadius = $this->iconscaleh / 2 - 1;

        if (!$finalFillColour->isNone()) {
            imagefilledellipse($icon_im, $xRadius, $yRadius, $xRadius * 2, $yRadius * 2, $finalFillColour->gdallocate($icon_im));
        }

        if (!$finalInkColour->isNone()) {
            imagefilledarc($icon_im, $xRadius, $yRadius, $xRadius * 2, $yRadius * 2, 0, $segmentAngle, $finalInkColour->gdallocate($icon_im), IMG_ARC_PIE);
        }

        if (!$finalFillColour->isNone()) {
            imageellipse($icon_im, $xRadius, $yRadius, $xRadius * 2, $yRadius * 2, $finalFillColour->gdallocate($icon_im));
        }
    }

    public function asConfigData()
    {
        $config = parent::asConfigData();

        $config['label'] = $this->label;
        $config['icon'] = array($this->iconfile, $this->iconscalew, $this->iconscaleh);
        $config['labeloffset'] = $this->labeloffset;

        return $config;
    }

    /**
     * @param $labelColour
     * @return array
     */
    private function calculateAICONColours($labelColour, &$map)
    {
        $finalFillColour = new WMColour('none');
        $finalInkColour = new WMColour('none');

        $configuredAIFillColour = $this->aiconfillcolour;
        $configuredAIOutlineColour = $this->aiconoutlinecolour;

        // if useiconscale isn't set, then use the static colour defined, or copy the colour from the label
        if ($this->useiconscale == "none") {
            if ($configuredAIFillColour->isCopy() && !$labelColour->isNone()) {
                $finalFillColour = $labelColour;
            } else {
                if ($configuredAIFillColour->isRealColour()) {
                    $finalFillColour = $configuredAIFillColour;
                }
            }
        } else {
            // if useiconscale IS defined, use that to figure out the fill colour
            $finalFillColour = $this->calculateIconColour($map);
        }

        # Same kind of thing for the outline colour
        if (!$configuredAIOutlineColour->isNone()) {
            if ($configuredAIOutlineColour->isCopy() && !$labelColour->isNone()) {
                $finalInkColour = $labelColour;
            } else {
                if ($configuredAIOutlineColour->isRealColour()) {
                    $finalInkColour = $configuredAIOutlineColour;
                }
            }
        }
        return array($finalFillColour, $finalInkColour);
    }

    /**
     * @return resource
     */
    private function createTransparentImage($width, $height)
    {
        $icon_im = imagecreatetruecolor($width, $height);
        imagesavealpha($icon_im, true);

        $nothing = imagecolorallocatealpha($icon_im, 128, 0, 0, 127);
        imagefill($icon_im, 0, 0, $nothing);

        return $icon_im;
    }

}

// vim:ts=4:sw=4:
