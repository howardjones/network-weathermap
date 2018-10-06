<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License


namespace Weathermap\Core;

/**
 * A single node on a map. Handles drawing, creation and generating config for getConfig()
 *
 * @package Weathermap\Core
 */
class MapNode extends MapDataItem
{
    public $drawable;
    public $x;
    public $y;
    public $originalX;
    public $originalY;
    public $relativePositionResolved;
    public $width;
    public $height;
    public $label;
    public $processedLabel;
    public $labelangle;
    public $selected = 0;
    public $position;

    public $positionedByNamedOffset;
    public $namedOffsets;
    public $positionRelativeToNamedOffset;

    public $iconfile;
    public $iconscalew;
    public $iconscaleh;
    public $labeloffset;
    public $labeloffsetx;
    public $labeloffsety;

    /** @var  Colour $labelbgcolour */
    public $labelbgcolour;
    /** @var  Colour $labeloutlinecolour */
    public $labeloutlinecolour;
    /** @var  Colour $labelfontcolour */
    public $labelfontcolour;
    /** @var  Colour $labelfontshadowcolour */
    public $labelfontshadowcolour;

    public $labelfont;

    public $useiconscale;
    public $iconscaletype;
    public $iconscalevar;
    public $imageRef;
    public $centreX; // TODO these were for ORIGIN
    public $centreY; // TODO these were for ORIGIN
    public $positionRelativeTo;
    public $polar;
    public $boundingboxes = array();
    /** @var  Colour $aiconfillcolour */
    public $aiconfillcolour;
    /** @var  Colour $aiconoutlinecolour */
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
        $this->centreX = 0;
        $this->centreY = 0;
        $this->polar = false;
        $this->positionedByNamedOffset = false;
        $this->imageRef = null;
        $this->drawable = false;

        $this->inheritedFieldList = array
        (
            'boundingboxes' => array(),
            'my_default' => null,
            'label' => '',
            'processedLabel' => '',
            'usescale' => 'DEFAULT',
            'scaletype' => 'percent',
            'iconscaletype' => 'percent',
            'useiconscale' => 'none',
            'scalevar' => 'in',
            'template' => ':: DEFAULT ::',
            'iconscalevar' => 'in',
            'labelfont' => 3,
            'positionRelativeTo' => '',
            'relativePositionResolved' => false,
            'x' => null,
            'y' => null,
            'scaleKeys' => array(IN => '', OUT => ''),
            'scaleTags' => array(IN => '', OUT => ''),
//            'inscalekey' => '',
//            'outscalekey' => '',
            #'incolour'=>-1,'outcolour'=>-1,
            'originalX' => 0,
            'originalY' => 0,
            'labelangle' => 0,
            'iconfile' => '',
            'iconscalew' => 0,
            'iconscaleh' => 0,
            'targets' => array(),
            'namedOffsets' => array(),
            'infourl' => array(IN => '', OUT => ''),
            'maxValuesConfigured' => array(IN => '100', OUT => '100'),
            'maxValues' => array(IN => null, OUT => null),
            'notestext' => array(IN => '', OUT => ''),
            'notes' => array(),
            'hints' => array(),
            'overliburl' => array(IN => array(), OUT => array()),
            'overlibwidth' => 0,
            'overlibheight' => 0,
            'overlibcaption' => array(IN => '', OUT => ''),

            'labeloutlinecolour' => new Colour(0, 0, 0),
            'labelbgcolour' => new Colour(255, 255, 255),
            'labelfontcolour' => new Colour(0, 0, 0),
            'labelfontshadowcolour' => new Colour('none'),
            'aiconoutlinecolour' => new Colour(0, 0, 0),
            'aiconfillcolour' => new Colour('copy'), // copy from the node label

            'labeloffset' => '',
            'labeloffsetx' => 0,
            'labeloffsety' => 0,
            'zorder' => 600,
        );

        $this->reset($owner);
    }

    public function myType()
    {
        return 'NODE';
    }

    public function hasArtificialIcon()
    {
        if ($this->iconfile == 'rbox' || $this->iconfile == 'box' || $this->iconfile == 'round' || $this->iconfile == 'inpie' || $this->iconfile == 'outpie' || $this->iconfile == 'gauge' || $this->iconfile == 'nink') {
            return true;
        }

        return false;
    }

    /**
     * @param resource $imageRef
     * @param Map $map
     */
    public function preRender(&$map)
    {
        if (!$this->drawable) {
            MapUtility::debug('Skipping undrawable %s', $this);
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

        $boundingBox = new BoundingBox();
        $labelBox = new Rectangle($this->x, $this->y, $this->x, $this->y);
        $iconBox = new Rectangle($this->x, $this->y, $this->x, $this->y);
        $textPoint = new Point($this->x, $this->y);

        $labelBoxWidth = 0;
        $labelBoxHeight = 0;
        $iconWidth = 0;
        $iconHeight = 0;

        $labelColour = new Colour('none');

        // if a target is specified, and you haven't forced no background, then the background will
        // come from the SCALE in USESCALE
        if (!empty($this->targets) && $this->usescale != 'none') {
//            $percentValue = 0;

            if ($this->scalevar == 'in') {
//                $percentValue = $this->percentUsages[IN];
                $labelColour = $this->colours[IN];
            }

            if ($this->scalevar == 'out') {
//                $percentValue = $this->percentUsages[OUT];
                $labelColour = $this->colours[OUT];
            }
        } elseif (!$this->labelbgcolour->isNone()) {
            MapUtility::debug("labelbgcolour=%s\n", $this->labelbgcolour);
            $labelColour = $this->labelbgcolour;
        }

        $iconColour = null;
        if (!empty($this->targets) && $this->useiconscale != 'none') {
            MapUtility::debug("Colorising the icon\n");
            $iconColour = $this->calculateIconColour($map);
        }

        // figure out a bounding rectangle for the label
        if ($this->label != '') {
            $paddingConstant = 4.0;
            $paddingFactor = 1.0;

            $this->processedLabel = $map->processString($this->label, $this, true, true);

            // if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
            // hopefully that will preserve enough information to show cool stuff without leaking info
            if ($map->getHint('screenshot_mode') == 1) {
                $this->processedLabel = StringUtility::stringAnonymise($this->processedLabel);
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

            MapUtility::debug('Node->pre_render: ' . $this->name . " Label Metrics are: $stringWidth x $stringHeight -> $labelBoxWidth x $labelBoxHeight\n");

            if ($this->labelangle == 90) {
                $textPoint = new Point($stringHeight / 2, $stringWidth / 2);
            }
            if ($this->labelangle == 270) {
                $textPoint = new Point(-$stringHeight / 2, -$stringWidth / 2);
            }
            if ($this->labelangle == 0) {
                $textPoint = new Point(-$stringWidth / 2, $stringHeight / 2);
            }
            if ($this->labelangle == 180) {
                $textPoint = new Point($stringWidth / 2, -$stringHeight / 2);
            }

            $textPoint->translate($this->x, $this->y);

            $labelBox = new Rectangle(
                -$labelBoxWidth / 2,
                -$labelBoxHeight / 2,
                $labelBoxWidth / 2,
                $labelBoxHeight / 2
            );
            $labelBox->translate($this->x, $this->y);

            MapUtility::debug("LABEL at %s\n", $labelBox);

            $this->width = $labelBoxWidth;
            $this->height = $labelBoxHeight;
        }

        // figure out a bounding rectangle for the icon
        if ($this->iconfile != '') {
            $iconImageRef = null;
            $iconWidth = 0;
            $iconHeight = 0;

            if ($this->hasArtificialIcon()) {
                $iconImageRef = $this->drawArtificialIcon($map, $labelColour);
            } else {
                $iconImageRef = $this->drawRealIcon($map, $iconColour);
            }

            if ($iconImageRef) {
                $iconWidth = imagesx($iconImageRef);
                $iconHeight = imagesy($iconImageRef);

                $iconBox = new Rectangle(-$iconWidth / 2, -$iconHeight / 2, $iconWidth / 2, $iconHeight / 2);
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

            list($dx, $dy) = MapUtility::calculateOffset(
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
        $nodeImageRef = ImageUtility::createTransparentImage($totalBoundingBox->width(), $totalBoundingBox->height());

        $labelBox->translate(-$totalBoundingBox->topLeft->x, -$totalBoundingBox->topLeft->y);
        $iconBox->translate(-$totalBoundingBox->topLeft->x, -$totalBoundingBox->topLeft->y);

        // Draw the icon, if any
        if (isset($iconImageRef)) {
            imagecopy(
                $nodeImageRef,
                $iconImageRef,
                $iconBox->topLeft->x,
                $iconBox->topLeft->y,
                0,
                0,
                imagesx($iconImageRef),
                imagesy($iconImageRef)
            );
            imagedestroy($iconImageRef);
        }

        // Draw the label, if any
        if ($this->label != '') {
            $textPoint->translate(-$totalBoundingBox->topLeft->x, -$totalBoundingBox->topLeft->y);
            imagealphablending($nodeImageRef, true);
            $this->drawLabel($map, $textPoint, $labelColour, $nodeImageRef, $labelBox);
        }

        $this->centreX = $this->x - $totalBoundingBox->topLeft->x;
        $this->centreY = $this->y - $totalBoundingBox->topLeft->y;

        $this->imageRef = $nodeImageRef;

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
            $areaName = 'NODE:N' . $this->id . ':' . $index;
            $newArea = new HTMLImagemapAreaRectangle(array($bbox), $areaName, '');
            // it doesn't really matter which, but it needs to have SOME direction
            $newArea->info['direction'] = IN;
            MapUtility::debug('Adding imagemap area [' . join(',', $bbox) . "] => $newArea \n");
            $this->imagemapAreas[] = $newArea;
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
        MapUtility::debug("------------------------------------------------\n");
        MapUtility::debug("Calculating node geometry for %s\n", $this);

        $this->drawable = false;

        // don't bother drawing if it's a template
        if ($this->isTemplate()) {
            MapUtility::debug("%s is a pure template. Skipping.\n", $this);
            return;
        }

        // apparently, some versions of the gd extension will crash if we continue...
        if ($this->label == '' && $this->iconfile == '') {
            MapUtility::debug("%s has no label OR icon. Skipping.\n", $this);
            return;
        }

        $this->drawable = true;
    }

    // draw the node, using the pre_render() output
    public function draw($imageRef)
    {
        if (!$this->drawable) {
            MapUtility::debug("Skipping undrawable %s\n", $this);
            return;
        }

        // take the offset we figured out earlier, and just blit
        // the image on. Who says "blit" anymore?

        // it's possible that there is no image, so better check.
        if (isset($this->imageRef)) {
            imagealphablending($imageRef, true);
            imagecopy(
                $imageRef,
                $this->imageRef,
                $this->x - $this->centreX,
                $this->y - $this->centreY,
                0,
                0,
                imagesx($this->imageRef),
                imagesy($this->imageRef)
            );
        }
    }

    // take the pre-rendered node and write it to a file so that
    // the editor can get at it.
    public function getConfig()
    {
        if ($this->configOverride != '') {
            return $this->configOverride . "\n";
        }

        $output = '';

        // This allows the editor to wholesale-replace a single node's configuration
        // at write-time - it should include the leading NODE xyz line (to allow for renaming)
        $templateSource = $this->owner->nodes[$this->template];

        MapUtility::debug("Writing config for NODE $this->name against $this->template\n");

        $simpleParameters = array(
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

        $output .= $this->getSimpleConfig($simpleParameters, $templateSource);

        // IN/OUT are the same, so we can use the simpler form here
        if ($this->infourl[IN] != $templateSource->infourl[IN]) {
            $output .= "\tINFOURL " . $this->infourl[IN] . "\n";
        }

        if ($this->overlibcaption[IN] != $templateSource->overlibcaption[IN]) {
            $output .= "\tOVERLIBCAPTION " . $this->overlibcaption[IN] . "\n";
        }

        // IN/OUT are the same, so we can use the simpler form here
        if ($this->notestext[IN] != $templateSource->notestext[IN]) {
            $output .= "\tNOTES " . $this->notestext[IN] . "\n";
        }

        if ($this->overliburl[IN] != $templateSource->overliburl[IN]) {
            $output .= "\tOVERLIBGRAPH " . join(' ', $this->overliburl[IN]) . "\n";
        }

        $val = $this->iconscalew . ' ' . $this->iconscaleh . ' ' . $this->iconfile;

        $comparison = $templateSource->iconscalew . ' ' . $templateSource->iconscaleh . ' ' . $templateSource->iconfile;

        if ($val != $comparison) {
            $output .= "\tICON ";
            if ($this->iconscalew > 0) {
                $output .= $this->iconscalew . ' ' . $this->iconscaleh . ' ';
            }
            $output .= ($this->iconfile == '' ? 'none' : $this->iconfile) . "\n";
        }

        if ($this->targets != $templateSource->targets) {
            $output .= "\tTARGET";

            foreach ($this->targets as $target) {
                $output .= ' ' . $target->asConfig();
            }

            $output .= "\n";
        }

        $val = $this->usescale . ' ' . $this->scalevar . ' ' . $this->scaletype;
        $comparison = $templateSource->usescale . ' ' . $templateSource->scalevar . ' ' . $templateSource->scaletype;

        if (($val != $comparison)) {
            $output .= "\tUSESCALE " . $val . "\n";
        }

        $val = $this->useiconscale . ' ' . $this->iconscalevar;
        $comparison = $templateSource->useiconscale . ' ' . $templateSource->iconscalevar;

        if ($val != $comparison) {
            $output .= "\tUSEICONSCALE " . $val . "\n";
        }

        $val = $this->labeloffsetx . ' ' . $this->labeloffsety;
        $comparison = $templateSource->labeloffsetx . ' ' . $templateSource->labeloffsety;

        if ($comparison != $val) {
            $output .= "\tLABELOFFSET " . $val . "\n";
        }

        $val = $this->x . ' ' . $this->y;
        $comparison = $templateSource->x . ' ' . $templateSource->y;

        if ($val != $comparison) {
            if ($this->positionRelativeTo == '') {
                $output .= "\tPOSITION " . $val . "\n";
            } else {
                if ($this->polar) {
                    $output .= "\tPOSITION " . $this->positionRelativeTo . ' ' . $this->originalX . 'r' . $this->originalY . "\n";
                } elseif ($this->positionedByNamedOffset) {
                    $output .= "\tPOSITION " . $this->positionRelativeTo . ':' . $this->positionRelativeToNamedOffset . "\n";
                } else {
                    $output .= "\tPOSITION " . $this->positionRelativeTo . ' ' . $this->originalX . ' ' . $this->originalY . "\n";
                }
            }
        }

        $output .= $this->getMaxValueConfig($templateSource, 'MAXVALUE');

        $output .= $this->getHintConfig($templateSource);

        foreach ($this->namedOffsets as $offsetName => $offsetPosition) {
            // if the offset exists with different values, or
            // doesn't exist at all in the template, we need to write
            // some config for it
            if ((array_key_exists($offsetName, $templateSource->namedOffsets))) {
                $offsetX = $templateSource->namedOffsets[$offsetName][0];
                $offsetY = $templateSource->namedOffsets[$offsetName][1];

                if ($offsetX != $offsetPosition[0] || $offsetY != $offsetPosition[1]) {
                    $output .= sprintf(
                        "\tDEFINEOFFSET %s %d %d\n",
                        $offsetName,
                        $offsetPosition[0],
                        $offsetPosition[1]
                    );
                }
            } else {
                $output .= sprintf("\tDEFINEOFFSET %s %d %d\n", $offsetName, $offsetPosition[0], $offsetPosition[1]);
            }
        }

        if ($output != '') {
            $output = 'NODE ' . $this->name . "\n$output\n";
        }

        return $output;
    }

    public function editorData()
    {
        $newOutput = array(
            "id" => "N" . $this->id,
            "name" => $this->name,
            "x" => $this->x,
            "y" => $this->y,
            "ox" => $this->originalX,
            "oy" => $this->originalY,
            "relative_to" => $this->positionRelativeTo,
            "label" => $this->label,
            "infourl" => $this->infourl[IN],
            "overliburl" => $this->overliburl[IN],
            "overlibcaption" => $this->overlibcaption[IN],
            "overlibwidth" => $this->overlibwidth,
            "overlibheight" => $this->overlibheight,
            "iconfile" => $this->iconfile
        );

        if ($this->hasArtificialIcon() || $this->iconfile == 'none') {
            $newOutput['iconfile'] = '::' . $this->iconfile;
        }

        return $newOutput;
    }


    public function isRelativePositionResolved()
    {
        return $this->relativePositionResolved;
    }

    public function isRelativePositioned()
    {
        if ($this->positionRelativeTo != '') {
            return true;
        }

        return false;
    }

    public function getRelativeAnchor()
    {
        return $this->positionRelativeTo;
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
            MapUtility::debug("POLAR $this -> $now\n");
            $this->setPosition($now);
            $this->relativePositionResolved = true;

            return true;
        }

        if ($this->positionedByNamedOffset) {
            $offsetName = $this->positionRelativeToNamedOffset;
            if (isset($anchorNode->namedOffsets[$offsetName])) {
                $now = $anchorPosition->copy();
                $now->translate(
                    $anchorNode->namedOffsets[$offsetName][0],
                    $anchorNode->namedOffsets[$offsetName][1]
                );
                MapUtility::debug("NAMED OFFSET $this -> $now\n");
                $this->setPosition($now);
                $this->relativePositionResolved = true;

                return true;
            }
            MapUtility::debug("Fell through named offset.\n");

            return false;
        }

        // resolve the relative stuff
        $now = $this->getPosition();
        $now->translate($anchorPosition->x, $anchorPosition->y);

        MapUtility::debug("OFFSET $this -> $now\n");
        $this->setPosition($now);
        $this->relativePositionResolved = true;

        return true;
    }

    /**
     * @return Point
     */
    public function getPosition()
    {
        return new Point($this->x, $this->y);
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

        if (isset($this->imageRef)) {
            imagedestroy($this->imageRef);
        }
        $this->owner = null;
        $this->descendents = null;
        $this->imageRef = null;
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
     * @param Colour $labelColour
     * @return resource
     */
    private function drawArtificialIcon(&$map, $labelColour)
    {
        MapUtility::debug('Artificial Icon type ' . $this->iconfile . " for $this->name\n");
        // this is an artificial icon - we don't load a file for it

        $iconImageRef = ImageUtility::createTransparentImage($this->iconscalew, $this->iconscaleh);

        list($finalFillColour, $finalInkColour) = $this->calculateAICONColours($labelColour, $map);

        MapUtility::debug("ink is: $finalInkColour\n");
        MapUtility::debug("fill is: $finalFillColour\n");

        switch ($this->iconfile) {
            case 'box':
                $this->drawArtificialIconBox($iconImageRef, $finalFillColour, $finalInkColour);
                break;
            case 'rbox':
                $this->drawArtificialIconRoundedBox($iconImageRef, $finalFillColour, $finalInkColour);
                break;
            case 'round':
                $this->drawArtificialIconRound($iconImageRef, $finalFillColour, $finalInkColour);
                break;
            case 'nink':
                $this->drawArtificialIconNINK($iconImageRef, $finalInkColour, $map);
                break;
            case 'inpie':
                $this->drawArtificialIconPie($iconImageRef, $finalFillColour, $finalInkColour, IN);
                break;
            case 'outpie':
                $this->drawArtificialIconPie($iconImageRef, $finalFillColour, $finalInkColour, OUT);
                break;
            case 'gauge':
                MapUtility::warn('gauge AICON not implemented yet [WMWARN99]');
                break;
        }

        return $iconImageRef;
    }

    /**
     * @param Map $map
     * @param Colour $iconColour
     * @return resource
     */
    private function drawRealIcon(&$map, $iconColour)
    {
        $this->iconfile = $map->processString($this->iconfile, $this);

        MapUtility::debug('Actual image-based icon from ' . $this->iconfile . " for $this->name\n");

        $iconImageRef = null;

        if (is_readable($this->iconfile)) {
            // draw the supplied icon, instead of the labelled box
            if (isset($iconColour)) {
                $colourisationMethod = 'imagecolorize';
                if (function_exists('imagefilter') && $map->getHint('use_imagefilter') == 1) {
                    $colourisationMethod = 'imagefilter';
                }

                $iconImageRef = $this->owner->imagecache->imagecreatescaledcolourizedfromfile(
                    $this->iconfile,
                    $this->iconscalew,
                    $this->iconscaleh,
                    $iconColour,
                    $colourisationMethod
                );
            } else {
                $iconImageRef = $this->owner->imagecache->imagecreatescaledfromfile(
                    $this->iconfile,
                    $this->iconscalew,
                    $this->iconscaleh
                );
            }

            if (!$iconImageRef) {
                MapUtility::warn("Couldn't open ICON: '" . $this->iconfile . "' - is it a PNG, JPEG or GIF? [WMWARN37]\n");
            }
        } else {
            if ($this->iconfile != 'none') {
                MapUtility::warn("ICON '" . $this->iconfile . "' does not exist, or is not readable. Check path and permissions. [WMARN38]\n");
            }
        }
        return $iconImageRef;
    }

    /**
     * @param Map $map
     * @param Point $textPoint
     * @param Colour $backgroundColour
     * @param resource $nodeImageRef
     * @param Rectangle $labelBox
     */
    private function drawLabel(&$map, $textPoint, $backgroundColour, $nodeImageRef, $labelBox)
    {
        MapUtility::debug("Label colour is $backgroundColour\n");

        // if there's an icon, then you can choose to have no background
        if (!$this->labelbgcolour->isNone()) {
            imagefilledrectangle(
                $nodeImageRef,
                $labelBox->topLeft->x,
                $labelBox->topLeft->y,
                $labelBox->bottomRight->x,
                $labelBox->bottomRight->y,
                $backgroundColour->gdallocate($nodeImageRef)
            );
        }

        if ($this->selected) {
            imagerectangle(
                $nodeImageRef,
                $labelBox->topLeft->x,
                $labelBox->topLeft->y,
                $labelBox->bottomRight->x,
                $labelBox->bottomRight->y,
                $map->selected
            );
            // would be nice if it was thicker, too...
            imagerectangle(
                $nodeImageRef,
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
                    $nodeImageRef,
                    $labelBox->topLeft->x,
                    $labelBox->topLeft->y,
                    $labelBox->bottomRight->x,
                    $labelBox->bottomRight->y,
                    $outlineColour->gdAllocate($nodeImageRef)
                );
            }
        }

        $fontObject = $this->owner->fonts->getFont($this->labelfont);

        $shadowColour = $this->labelfontshadowcolour;
        if ($shadowColour->isRealColour()) {
            $fontObject->drawImageString(
                $nodeImageRef,
                $textPoint->x + 1,
                $textPoint->y + 1,
                $this->processedLabel,
                $shadowColour->gdAllocate($nodeImageRef),
                $this->labelangle
            );
        }

        $textColour = $this->labelfontcolour;

        if ($textColour->isContrast()) {
            if ($backgroundColour->isRealColour()) {
                $textColour = $backgroundColour->getContrastingColour();
            } else {
                MapUtility::warn("You can't make a contrast with 'none'. Guessing black. [WMWARN43]\n");
                $textColour = new Colour(0, 0, 0);
            }
        }
        $fontObject->drawImageString(
            $nodeImageRef,
            $textPoint->x,
            $textPoint->y,
            $this->processedLabel,
            $textColour->gdAllocate($nodeImageRef),
            $this->labelangle
        );
    }

    /**
     * @param Map $map
     * @return Colour
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
            list($iconColour) =
                $map->scales[$this->useiconscale]->colourFromValue($percentValue, $this->name);
        } else {
            // use the absolute value if we aren't doing percentage scales.
            list($iconColour) =
                $map->scales[$this->useiconscale]->colourFromValue($absoluteValue, $this->name, false);
        }
        return $iconColour;
    }

    /**
     * @param Colour $finalFillColour
     * @param resource $iconImageRef
     * @param Colour $finalInkColour
     */
    private function drawArtificialIconBox($iconImageRef, $finalFillColour, $finalInkColour)
    {
        if (!$finalFillColour->isNone()) {
            imagefilledrectangle(
                $iconImageRef,
                0,
                0,
                $this->iconscalew - 1,
                $this->iconscaleh - 1,
                $finalFillColour->gdallocate($iconImageRef)
            );
        }

        if (!$finalInkColour->isNone()) {
            imagerectangle(
                $iconImageRef,
                0,
                0,
                $this->iconscalew - 1,
                $this->iconscaleh - 1,
                $finalInkColour->gdallocate($iconImageRef)
            );
        }
    }

    /**
     * @param Colour $finalFillColour
     * @param resource $iconImageRef
     * @param Colour $finalInkColour
     */
    private function drawArtificialIconRoundedBox($iconImageRef, $finalFillColour, $finalInkColour)
    {
        if (!$finalFillColour->isNone()) {
            ImageUtility::imageFilledRoundedRectangle(
                $iconImageRef,
                0,
                0,
                $this->iconscalew - 1,
                $this->iconscaleh - 1,
                4,
                $finalFillColour->gdallocate($iconImageRef)
            );
        }

        if (!$finalInkColour->isNone()) {
            ImageUtility::imageRoundedRectangle(
                $iconImageRef,
                0,
                0,
                $this->iconscalew - 1,
                $this->iconscaleh - 1,
                4,
                $finalInkColour->gdallocate($iconImageRef)
            );
        }
    }

    /**
     * @param Colour $finalFillColour
     * @param resource $iconImageRef
     * @param Colour $finalInkColour
     */
    private function drawArtificialIconRound($iconImageRef, $finalFillColour, $finalInkColour)
    {
        $xRadius = $this->iconscalew / 2 - 1;
        $yRadius = $this->iconscaleh / 2 - 1;

        if (!$finalFillColour->isNone()) {
            imagefilledellipse(
                $iconImageRef,
                $xRadius,
                $yRadius,
                $xRadius * 2,
                $yRadius * 2,
                $finalFillColour->gdallocate($iconImageRef)
            );
        }

        if (!$finalInkColour->isNone()) {
            imageellipse(
                $iconImageRef,
                $xRadius,
                $yRadius,
                $xRadius * 2,
                $yRadius * 2,
                $finalInkColour->gdallocate($iconImageRef)
            );
        }
    }

    /**
     * @param Map $map
     * @param resource $iconImageRef
     * @param Colour $finalInkColour
     */
    private function drawArtificialIconNINK($iconImageRef, $finalInkColour, &$map)
    {
        $xRadius = $this->iconscalew / 2 - 1;
        $yRadius = $this->iconscaleh / 2 - 1;
        $size = $this->iconscalew;
        $quarter = $size / 4;

        $col1 = $this->colours[OUT];
        $col2 = $this->colours[IN];

        assert(!is_null($col1));
        assert(!is_null($col2));

        imagefilledarc(
            $iconImageRef,
            $xRadius - 1,
            $yRadius,
            $size,
            $size,
            270,
            90,
            $col1->gdallocate($iconImageRef),
            IMG_ARC_PIE
        );
        imagefilledarc(
            $iconImageRef,
            $xRadius + 1,
            $yRadius,
            $size,
            $size,
            90,
            270,
            $col2->gdallocate($iconImageRef),
            IMG_ARC_PIE
        );

        imagefilledarc(
            $iconImageRef,
            $xRadius - 1,
            $yRadius + $quarter,
            $quarter * 2,
            $quarter * 2,
            0,
            360,
            $col1->gdallocate($iconImageRef),
            IMG_ARC_PIE
        );
        imagefilledarc(
            $iconImageRef,
            $xRadius + 1,
            $yRadius - $quarter,
            $quarter * 2,
            $quarter * 2,
            0,
            360,
            $col2->gdallocate($iconImageRef),
            IMG_ARC_PIE
        );

        if (!$finalInkColour->isNone()) {
            // XXX - need a font definition from somewhere for NINK text
            $font = 1;

            $instr = $map->processString('{node:this:bandwidth_in:%.1k}', $this);
            $outstr = $map->processString('{node:this:bandwidth_out:%.1k}', $this);

            $fontObject = $this->owner->fonts->getFont($font);
            list($textWidth, $textHeight) = $fontObject->calculateImageStringSize($instr);
            $fontObject->drawImageString(
                $iconImageRef,
                $xRadius - $textWidth / 2,
                $yRadius - $quarter + ($textHeight / 2),
                $instr,
                $finalInkColour->gdallocate($iconImageRef)
            );

            list($textWidth, $textHeight) = $fontObject->calculateImageStringSize($outstr);
            $fontObject->drawImageString(
                $iconImageRef,
                $xRadius - $textWidth / 2,
                $yRadius + $quarter + ($textHeight / 2),
                $outstr,
                $finalInkColour->gdallocate($iconImageRef)
            );

            imageellipse(
                $iconImageRef,
                $xRadius,
                $yRadius,
                $xRadius * 2,
                $yRadius * 2,
                $finalInkColour->gdallocate($iconImageRef)
            );
        }
    }

    /**
     * @param number $channel
     * @param Colour $finalFillColour
     * @param resource $iconImageRef
     * @param Colour $finalInkColour
     */
    private function drawArtificialIconPie($iconImageRef, $finalFillColour, $finalInkColour, $channel)
    {
        $percentValue = $this->percentUsages[$channel];

        $segmentAngle = MathUtility::clip(($percentValue / 100) * 360, 1, 360);

        $xRadius = $this->iconscalew / 2 - 1;
        $yRadius = $this->iconscaleh / 2 - 1;

        if (!$finalFillColour->isNone()) {
            imagefilledellipse(
                $iconImageRef,
                $xRadius,
                $yRadius,
                $xRadius * 2,
                $yRadius * 2,
                $finalFillColour->gdallocate($iconImageRef)
            );
        }

        if (!$finalInkColour->isNone()) {
            imagefilledarc(
                $iconImageRef,
                $xRadius,
                $yRadius,
                $xRadius * 2,
                $yRadius * 2,
                0,
                $segmentAngle,
                $finalInkColour->gdallocate($iconImageRef),
                IMG_ARC_PIE
            );
        }

        if (!$finalFillColour->isNone()) {
            imageellipse(
                $iconImageRef,
                $xRadius,
                $yRadius,
                $xRadius * 2,
                $yRadius * 2,
                $finalFillColour->gdallocate($iconImageRef)
            );
        }
    }

    public function asConfigData()
    {
        $config = parent::asConfigData();

        $config['label'] = $this->label;
        $config['icon'] = array($this->iconfile, $this->iconscalew, $this->iconscaleh);
        $config['labeloffset'] = $this->labeloffset;
        $config['id'] = "N" . $this->id;
        $config['zorder'] = $this->zorder;
        $config['imagemap'] = array();
        foreach ($this->getImageMapAreas() as $area) {
            $config['imagemap'] [] = $area->asJSONData();
        }

        return $config;
    }

    /**
     * @param Colour $labelColour
     * @param Map map
     * @return array
     */
    private function calculateAICONColours($labelColour, &$map)
    {
        $finalFillColour = new Colour('none');
        $finalInkColour = new Colour('none');

        $configuredAIFillColour = $this->aiconfillcolour;
        $configuredAIOutlineColour = $this->aiconoutlinecolour;

        // if useiconscale isn't set, then use the static colour defined, or copy the colour from the label
        if ($this->useiconscale == 'none') {
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

    public function selfValidate()
    {
        $failed = false;
        $class = get_class();

        foreach (array_keys($this->inheritedFieldList) as $fld) {
            if (!property_exists($class, $fld)) {
                $failed = true;
                MapUtility::warn("$fld is in $class inherit list, but not in object");
            }
        }
        return $failed;
    }

    /**
     * processString used to just reach into objects to get properties. A lot of the
     * property names were accidentally made permanent because of this. Now we're moving
     * towards multi-channel everywhere, all the old in/out names are a problem, so now
     * all properties are fetched by this function, which translates the old names to the
     * actual property that holds that data now. This will also be the 'access-control' for
     * which properties are exposed.
     *
     * @param $name
     * @return mixed|null
     * @throws WeathermapRuntimeWarning
     */
    public function getProperty($name)
    {
        MapUtility::debug("Fetching %s\n", $name);

        $translations = array(
            "inscalekey" => $this->scaleKeys[IN],
            "outscalekey" => $this->scaleKeys[OUT],
            "inscaletag" => $this->scaleTags[IN],
            "outscaletag" => $this->scaleTags[OUT],
            "bandwidth_in" => $this->absoluteUsages[IN],
            "inpercent" => $this->percentUsages[IN],
            "bandwidth_out" => $this->absoluteUsages[OUT],
            "outpercent" => $this->percentUsages[OUT],
            "max_bandwidth_in" => $this->maxValues[IN],
            'max_bandwidth_in_cfg' => $this->maxValuesConfigured[IN],
            "max_bandwidth_out" => $this->maxValues[OUT],
            'max_bandwidth_out_cfg' => $this->maxValuesConfigured[OUT],
            'name' => $this->name
        );

        if (array_key_exists($name, $translations)) {
            return $translations[$name];
        }

        throw new WeathermapRuntimeWarning("NoSuchProperty");
    }

    public function __toString()
    {
        return sprintf("[NODE %s]", $this->name);
    }

    public function getOverlibCentre()
    {
        return array($this->x, $this->y);
    }
}

// vim:ts=4:sw=4:
