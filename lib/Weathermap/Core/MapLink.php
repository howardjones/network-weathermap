<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

namespace Weathermap\Core;

/**
 * A single link on a map. Handles drawing, creation and generating config for getConfig()
 *
 * @package Weathermap\Core
 */
class MapLink extends MapDataItem
{
    const FMT_BITS_IN = '{link:this:bandwidth_in:%2k}';
    const FMT_BITS_OUT = '{link:this:bandwidth_out:%2k}';
    const FMT_UNFORM_IN = '{link:this:bandwidth_in}';
    const FMT_UNFORM_OUT = '{link:this:bandwidth_out}';
    const FMT_PERC_IN = '{link:this:inpercent:%.2f}%';
    const FMT_PERC_OUT = '{link:this:outpercent:%.2f}%';

    /** @var MapLinkEndpoint[] $endpoints */
    public $endpoints = array();

    public $commentOffsets = array();
    public $bwlabelOffsets = array();

    public $width;
    public $arrowStyle;
    public $linkStyle;
    public $labelStyle;
    public $labelBoxStyle;
//    public $selected; //TODO - can a link even BE selected?
    public $viaList = array();
    public $viaStyle;
    public $commentStyle;
    public $splitPosition;

    public $bwfont;
    public $commentfont;

    /** @var Colour $outlinecolour */
    public $outlinecolour;
    /** @var  Colour $bwoutlinecolour */
    public $bwoutlinecolour;
    /** @var  Colour $bwboxcolour */
    public $bwboxcolour;
    /** @var  Colour $commentfontcolour */
    public $commentfontcolour;
    /** @var  Colour $bwfontcolour */
    public $bwfontcolour;

    public $bwlabelformats = array();
    public $comments = array();

    /** @var  LinkGeometry $geometry */
    public $geometry;  // contains all the spine-related data (WMLinkGeometry)

    /**
     * WeatherMapLink constructor.
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
        $this->endpoints = array(
            new MapLinkEndpoint(),
            new MapLinkEndpoint()
        );

        $this->inheritedFieldList = array(
            'my_default' => null,
            'width' => 7,
            'commentfont' => 1,
            'bwfont' => 2,
            'template' => ':: DEFAULT ::',
            'splitPosition' => 50,
            'commentStyle' => 'edge',
            'arrowStyle' => 'classic',
            'viaStyle' => 'curved',
            'usescale' => 'DEFAULT',
            'scaletype' => 'percent',
            'targets' => array(),
            'duplex' => 'full',
            'infourl' => array('', ''),
            'notes' => array(),
            'hints' => array(),
            'scaleKeys' => array(IN => '', OUT => ''),
            'scaleTags' => array(IN => '', OUT => ''),
            'comments' => array('', ''),
            'commentOffsets' => array(IN => 95, OUT => 5),
            'bwlabelOffsets' => array(IN => 75, OUT => 25),
            'bwlabelformats' => array(self::FMT_PERC_IN, self::FMT_PERC_OUT),
            'overliburl' => array(array(), array()),
            'notestext' => array(IN => '', OUT => ''),
            'maxValuesConfigured' => array(IN => '100M', OUT => '100M'),
            'maxValues' => array(IN => null, OUT => null),
            'labelStyle' => 'percent',
            'labelBoxStyle' => 'classic',
            'linkStyle' => 'twoway',
            'overlibwidth' => 0,
            'overlibheight' => 0,
            'outlinecolour' => new Colour(0, 0, 0),
            'bwoutlinecolour' => new Colour(0, 0, 0),
            'bwfontcolour' => new Colour(0, 0, 0),
            'bwboxcolour' => new Colour(255, 255, 255),
            'commentfontcolour' => new Colour(192, 192, 192),
//            'inscalekey' => '',
//            'outscalekey' => '',
            'zorder' => 300,
            'overlibcaption' => array('', '')
        );

        $this->reset($owner);
    }

    public function myType()
    {
        return 'LINK';
    }

    public function getTemplateObject()
    {
        return $this->owner->getLink($this->template);
    }

    public function isTemplate()
    {
        return !isset($this->endpoints[0]->node);
    }

    private function getDirectionList()
    {
        if ($this->linkStyle == 'oneway') {
            return array(OUT);
        }

        return array(IN, OUT);
    }

    private function drawComments($gdImage)
    {
        MapUtility::debug('Link ' . $this->name . ": Drawing comments.\n");

        $directions = $this->getDirectionList();

        $commentColours = array();
        $gdCommentColours = array();

        $widthList = $this->geometry->getWidths();

        $fontObject = $this->owner->fonts->getFont($this->commentfont);

        foreach ($directions as $direction) {
            MapUtility::debug('Link ' . $this->name . ": Drawing comments for direction $direction\n");

            $widthList[$direction] *= 1.1;

            $commentColours[$direction] = $this->commentfontcolour;

            if ($this->commentfontcolour->isContrast()) {
                $commentColours[$direction] = $this->colours[$direction]->getContrastingColour();
            }

            $gdCommentColours[$direction] = $commentColours[$direction]->gdAllocate($gdImage);


            $comment = $this->calculateCommentText($direction);

            if ($comment == '') {
                MapUtility::debug('Link ' . $this->name . " no text for direction $direction\n");
                continue;
            }

            list($angle, $edge) = $this->calculateCommentPosition($fontObject, $comment, $direction, $widthList);

            MapUtility::debug('Link ' . $this->name . " writing $comment at $edge and angle $angle for direction $direction\n");

            // FINALLY, draw the text!
            $fontObject->drawImageString($gdImage, $edge->x, $edge->y, $comment, $gdCommentColours[$direction], $angle);
        }
    }

    /***
     * @param Map $map
     * @throws WeathermapInternalFail
     */
    public function preCalculate(&$map)
    {
        MapUtility::debug('Link ' . $this->name . ": Calculating geometry.\n");

        // don't bother doing anything if it's a template
        if ($this->isTemplate()) {
            return;
        }

        $points = array();

        $this->endpoints[0]->resolve('A');
        $this->endpoints[1]->resolve('B');

        $points [] = $this->endpoints[0]->point;

        MapUtility::debug('POINTS SO FAR:' . join(' ', $points) . "\n");

        foreach ($this->viaList as $via) {
            MapUtility::debug("VIALIST...\n");
            // if the via has a third element, the first two are relative to that node
            if (isset($via[2])) {
                $relativeTo = $map->getNode($via[2]);
                MapUtility::debug("Relative to $relativeTo\n");
                $point = new Point($relativeTo->x + $via[0], $relativeTo->y + $via[1]);
            } else {
                $point = new Point($via[0], $via[1]);
            }
            MapUtility::debug("Adding $point\n");
            $points [] = $point;
        }
        MapUtility::debug('POINTS SO FAR:' . join(' ', $points) . "\n");

        $points [] = $this->endpoints[1]->point;

        MapUtility::debug('POINTS SO FAR:' . join(' ', $points) . "\n");

        if ($points[0]->closeEnough($points[1]) && count($this->viaList) == 0) {
            MapUtility::warn('Zero-length link ' . $this->name . ' skipped. [WMWARN45]');
            $this->geometry = null;
            return;
        }

        $widths = array($this->width, $this->width);

        // for bulging animations, modulate the width with the percentage value
        if (($map->widthmod) || ($map->getHint('link_bulge') == 1)) {
            // a few 0.1s and +1s to fix div-by-zero, and invisible links

            $widths[IN] = (($widths[IN] * $this->percentUsages[IN] * 1.5 + 0.1) / 100) + 1;
            $widths[OUT] = (($widths[OUT] * $this->percentUsages[OUT] * 1.5 + 0.1) / 100) + 1;
        }

        $style = $this->viaStyle;

        // don't bother with any curve stuff if there aren't any Vias defined, even if the style is 'curved'
        if (count($this->viaList) == 0) {
            MapUtility::debug("Forcing to angled (no vias)\n");
            $style = 'angled';
        }

        $this->geometry = LinkGeometryFactory::create($style);
        $this->geometry->init(
            $this,
            $points,
            $widths,
            ($this->linkStyle == 'oneway' ? 1 : 2),
            $this->splitPosition,
            $this->arrowStyle
        );
    }

    public function draw($imageRef)
    {
        MapUtility::debug('Link ' . $this->name . ": Drawing.\n");
        // If there is geometry to draw, draw it
        if (!is_null($this->geometry)) {
            MapUtility::debug(get_class($this->geometry) . "\n");

            $this->geometry->setOutlineColour($this->outlinecolour);
            $this->geometry->setFillColours(array($this->colours[IN], $this->colours[OUT]));

            $this->geometry->draw($imageRef);

            if (!$this->commentfontcolour->isNone()) {
                $this->drawComments($imageRef);
            }

            $this->drawBandwidthLabels($imageRef);
        } else {
            MapUtility::debug("Skipping link with no geometry attached\n");
        }

        $this->makeImagemapAreas();
    }

    private function makeImagemapAreas()
    {
        if (!isset($this->geometry)) {
            return;
        }

        foreach ($this->getDirectionList() as $direction) {
            $areaName = 'LINK:L' . $this->id . ":$direction";

            $polyPoints = $this->geometry->getDrawnPolygon($direction);

            $newArea = new HTMLImagemapAreaPolygon(array($polyPoints), $areaName, '');
            $newArea->info['direction'] = $direction;
            MapUtility::debug("Adding Poly imagemap for %s\n", $areaName);

            $this->imagemapAreas[] = $newArea;
        }
    }

    private function drawBandwidthLabels($gdImage)
    {
        MapUtility::debug('Link ' . $this->name . ": Drawing bwlabels.\n");

        $directions = $this->getDirectionList();

        foreach ($directions as $direction) {
            list ($position, $index, $angle, $distance) = $this->geometry->findPointAndAngleAtPercentageDistance($this->bwlabelOffsets[$direction]);

            $bandwidth = $this->absoluteUsages[$direction];

            if ($this->owner->sizedebug) {
                $bandwidth = $this->maxValues[$direction];
                $this->absoluteUsages[$direction] = $this->maxValues[$direction];
            }

            $bwlabelText = $this->owner->processString($this->bwlabelformats[$direction], $this);

            if ($bwlabelText != '') {
                MapUtility::debug('Bandwidth for label is ' . StringUtility::valueOrNull($bandwidth) . " (label is '$bwlabelText')\n");
                $padding = intval($this->getHint('bwlabel_padding'));

                // if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
                // hopefully that will preserve enough information to show cool stuff without leaking info
                if ($this->owner->getHint('screenshot_mode') == 1) {
                    $bwlabelText = StringUtility::stringAnonymise($bwlabelText);
                }

                if ($this->labelBoxStyle != 'angled') {
                    $angle = 0;
                }

                $this->drawLabelRotated(
                    $gdImage,
                    $position,
                    $angle,
                    $bwlabelText,
                    $padding,
                    $direction
                );
            }
        }
    }

    private function normaliseAngle($angle)
    {
        $out = $angle;

        if (abs($out) > 90) {
            $out -= 180;
        }
        if ($out < -180) {
            $out += 360;
        }

        return $out;
    }

    /**
     * @param $imageRef
     * @param Point $centre
     * @param float $degreesAngle
     * @param string $text
     * @param float $padding
     * @param int $direction
     * @throws \Exception
     */
    private function drawLabelRotated($imageRef, $centre, $degreesAngle, $text, $padding, $direction)
    {
        $fontObject = $this->owner->fonts->getFont($this->bwfont);
        list($strWidth, $strHeight) = $fontObject->calculateImageStringSize($text);

        $degreesAngle = $this->normaliseAngle($degreesAngle);
        $radianAngle = -deg2rad($degreesAngle);

        $extra = 3;

        $minX = $centre->x - ($strWidth / 2) - $padding - $extra;
        $minY = $centre->y - ($strHeight / 2) - $padding - $extra;

        $maxX = $centre->x + ($strWidth / 2) + $padding + $extra;
        $maxY = $centre->y + ($strHeight / 2) + $padding + $extra;


        // a box. the last point is the start point for the text.
        $points = array(
            $minX, $minY,
            $minX, $maxY,
            $maxX, $maxY,
            $maxX, $minY,
            $centre->x - $strWidth / 2, $centre->y + $strHeight / 2 + 1
        );

        if ($radianAngle != 0) {
            MathUtility::rotateAboutPoint($points, $centre->x, $centre->y, $radianAngle);
        }

        $textY = array_pop($points);
        $textX = array_pop($points);

        if ($this->bwboxcolour->isRealColour()) {
            imagefilledpolygon($imageRef, $points, 4, $this->bwboxcolour->gdAllocate($imageRef));
        }

        if ($this->bwoutlinecolour->isRealColour()) {
            imagepolygon($imageRef, $points, 4, $this->bwoutlinecolour->gdAllocate($imageRef));
        }

        $fontObject->drawImageString(
            $imageRef,
            $textX,
            $textY,
            $text,
            $this->bwfontcolour->gdallocate($imageRef),
            $degreesAngle
        );

        // ------

        $areaName = 'LINK:L' . $this->id . ':' . ($direction + 2);

        // the rectangle is about half the size in the HTML, and easier to optimise/detect in the browser
        if (($degreesAngle % 90) == 0) {
            // We optimise for 0, 90, 180, 270 degrees - find the rectangle from the rotated points
            $rectanglePoints = array();
            $rectanglePoints[] = min($points[0], $points[2]);
            $rectanglePoints[] = min($points[1], $points[3]);
            $rectanglePoints[] = max($points[0], $points[2]);
            $rectanglePoints[] = max($points[1], $points[3]);
            $newArea = new HTMLImagemapAreaRectangle(array($rectanglePoints), $areaName, '');
            MapUtility::debug("Adding Rectangle imagemap for $areaName\n");
            $newArea->info['direction'] = $direction;
        } else {
            $newArea = new HTMLImagemapAreaPolygon(array($points), $areaName, '');
            $newArea->info['direction'] = $direction;
            MapUtility::debug("Adding Poly imagemap for $areaName\n");
        }
        // Make a note that we added this area
        $this->imagemapAreas[] = $newArea;
        $this->owner->imap->addArea($newArea);
    }

    public function getConfig()
    {
        if ($this->configOverride != '') {
            return $this->configOverride . "\n";
        }

        $output = '';

        $templateSource = $this->owner->links[$this->template];

        MapUtility::debug("Writing config for LINK $this->name against $this->template\n");

        $simpleParameters = array(
            array('width', 'WIDTH', self::CONFIG_TYPE_LITERAL),
            array('zorder', 'ZORDER', self::CONFIG_TYPE_LITERAL),
            array('overlibwidth', 'OVERLIBWIDTH', self::CONFIG_TYPE_LITERAL),
            array('overlibheight', 'OVERLIBHEIGHT', self::CONFIG_TYPE_LITERAL),
            array('arrowStyle', 'ARROWSTYLE', self::CONFIG_TYPE_LITERAL),
            array('viaStyle', 'VIASTYLE', self::CONFIG_TYPE_LITERAL),
            array('linkStyle', 'LINKSTYLE', self::CONFIG_TYPE_LITERAL),
            array('splitPosition', 'SPLITPOS', self::CONFIG_TYPE_LITERAL),
            array('duplex', 'DUPLEX', self::CONFIG_TYPE_LITERAL),
            array('commentStyle', 'COMMENTSTYLE', self::CONFIG_TYPE_LITERAL),
            array('labelBoxStyle', 'BWSTYLE', self::CONFIG_TYPE_LITERAL),
            //      array('usescale','USESCALE',self::CONFIG_TYPE_LITERAL),

            array('bwfont', 'BWFONT', self::CONFIG_TYPE_LITERAL),
            array('commentfont', 'COMMENTFONT', self::CONFIG_TYPE_LITERAL),

            array('bwoutlinecolour', 'BWOUTLINECOLOR', self::CONFIG_TYPE_COLOR),
            array('bwboxcolour', 'BWBOXCOLOR', self::CONFIG_TYPE_COLOR),
            array('outlinecolour', 'OUTLINECOLOR', self::CONFIG_TYPE_COLOR),
            array('commentfontcolour', 'COMMENTFONTCOLOR', self::CONFIG_TYPE_COLOR),
            array('bwfontcolour', 'BWFONTCOLOR', self::CONFIG_TYPE_COLOR)
        );

        # TEMPLATE must come first. DEFAULT
        if ($this->template != 'DEFAULT' && $this->template != ':: DEFAULT ::') {
            $output .= "\tTEMPLATE " . $this->template . "\n";
        }

        $output .= $this->getSimpleConfig($simpleParameters, $templateSource);

        $val = $this->usescale . ' ' . $this->scaletype;
        $comparison = $templateSource->usescale . ' ' . $templateSource->scaletype;

        if (($val != $comparison)) {
            $output .= "\tUSESCALE " . $val . "\n";
        }

        if ($this->infourl[IN] == $this->infourl[OUT]) {
            $dirs = array(IN => ''); // only use the IN value, since they're both the same, but don't prefix the output keyword
        } else {
            $dirs = array(IN => 'IN', OUT => 'OUT');// the full monty two-keyword version
        }

        foreach ($dirs as $dir => $tdir) {
            if ($this->infourl[$dir] != $templateSource->infourl[$dir]) {
                $output .= "\t" . $tdir . 'INFOURL ' . $this->infourl[$dir] . "\n";
            }
        }

        if ($this->overlibcaption[IN] == $this->overlibcaption[OUT]) {
            $dirs = array(IN => ''); // only use the IN value, since they're both the same, but don't prefix the output keyword
        } else {
            $dirs = array(IN => 'IN', OUT => 'OUT');// the full monty two-keyword version
        }

        foreach ($dirs as $dir => $tdir) {
            if ($this->overlibcaption[$dir] != $templateSource->overlibcaption[$dir]) {
                $output .= "\t" . $tdir . 'OVERLIBCAPTION ' . $this->overlibcaption[$dir] . "\n";
            }
        }

        if ($this->notestext[IN] == $this->notestext[OUT]) {
            $dirs = array(IN => ''); // only use the IN value, since they're both the same, but don't prefix the output keyword
        } else {
            $dirs = array(IN => 'IN', OUT => 'OUT');// the full monty two-keyword version
        }

        foreach ($dirs as $dir => $tdir) {
            if ($this->notestext[$dir] != $templateSource->notestext[$dir]) {
                $output .= "\t" . $tdir . 'NOTES ' . $this->notestext[$dir] . "\n";
            }
        }

        if ($this->overliburl[IN] == $this->overliburl[OUT]) {
            $dirs = array(IN => ''); // only use the IN value, since they're both the same, but don't prefix the output keyword
        } else {
            $dirs = array(IN => 'IN', OUT => 'OUT');// the full monty two-keyword version
        }

        foreach ($dirs as $dir => $tdir) {
            if ($this->overliburl[$dir] != $templateSource->overliburl[$dir]) {
                $output .= "\t" . $tdir . 'OVERLIBGRAPH ' . join(' ', $this->overliburl[$dir]) . "\n";
            }
        }

        // if formats have been set, but they're just the longform of the built-in styles, set them back to the built-in styles
        if ($this->labelStyle == '--' && $this->bwlabelformats[IN] == self::FMT_PERC_IN && $this->bwlabelformats[OUT] == self::FMT_PERC_OUT) {
            $this->labelStyle = 'percent';
        }
        if ($this->labelStyle == '--' && $this->bwlabelformats[IN] == self::FMT_BITS_IN && $this->bwlabelformats[OUT] == self::FMT_BITS_OUT) {
            $this->labelStyle = 'bits';
        }
        if ($this->labelStyle == '--' && $this->bwlabelformats[IN] == self::FMT_UNFORM_IN && $this->bwlabelformats[OUT] == self::FMT_UNFORM_OUT) {
            $this->labelStyle = 'unformatted';
        }

        // if specific formats have been set, then the style will be '--'
        // if it isn't then use the named style
        if (($this->labelStyle != $templateSource->labelStyle) && ($this->labelStyle != '--')) {
            $output .= "\tBWLABEL " . $this->labelStyle . "\n";
        }

        // if either IN or OUT field changes, then both must be written because a regular BWLABEL can't do it
        // XXX this looks wrong
        $comparison = $templateSource->bwlabelformats[IN];
        $comparison2 = $templateSource->bwlabelformats[OUT];

        if (($this->labelStyle == '--') && (($this->bwlabelformats[IN] != $comparison) || ($this->bwlabelformats[OUT] != '--'))) {
            $output .= "\tINBWFORMAT " . $this->bwlabelformats[IN] . "\n";
            $output .= "\tOUTBWFORMAT " . $this->bwlabelformats[OUT] . "\n";
        }

        $comparison = $templateSource->bwlabelOffsets[IN];
        $comparison2 = $templateSource->bwlabelOffsets[OUT];

        if (($this->bwlabelOffsets[IN] != $comparison) || ($this->bwlabelOffsets[OUT] != $comparison2)) {
            $output .= "\tBWLABELPOS " . $this->bwlabelOffsets[IN] . ' ' . $this->bwlabelOffsets[OUT] . "\n";
        }

        $comparison = $templateSource->commentOffsets[IN] . ':' . $templateSource->commentOffsets[OUT];
        $mine = $this->commentOffsets[IN] . ':' . $this->commentOffsets[OUT];
        if ($mine != $comparison) {
            $output .= "\tCOMMENTPOS " . $this->commentOffsets[IN] . ' ' . $this->commentOffsets[OUT] . "\n";
        }

        $comparison = $templateSource->targets;

        if ($this->targets != $comparison) {
            $output .= "\tTARGET";

            foreach ($this->targets as $target) {
                $output .= ' ' . $target->asConfig();
            }
            $output .= "\n";
        }

        foreach (array('IN', 'OUT') as $tdir) {
            $dir = constant($tdir);

            $comparison = $templateSource->comments[$dir];
            if ($this->comments[$dir] != $comparison) {
                $output .= "\t" . $tdir . 'COMMENT ' . $this->comments[$dir] . "\n";
            }
        }

        if (isset($this->endpoints[0]->node) && isset($this->endpoints[1]->node)) {
            $output .= sprintf("\tNODES %s %s\n", $this->endpoints[0], $this->endpoints[1]);
        }

        if (count($this->viaList) > 0) {
            foreach ($this->viaList as $via) {
                if (isset($via[2])) {
                    $output .= sprintf("\tVIA %s %d %d\n", $via[2], $via[0], $via[1]);
                } else {
                    $output .= sprintf("\tVIA %d %d\n", $via[0], $via[1]);
                }
            }
        }

        $output .= $this->getMaxValueConfig($templateSource, 'BANDWIDTH');
        $output .= $this->getHintConfig($templateSource);

        if ($output != '') {
            $output = 'LINK ' . $this->name . "\n" . $output . "\n";
        }

        return $output;
    }

    public function editorData()
    {
        $newOutput = array(
            "id" => "L" . $this->id,
            "name" => $this->name,
            "a" => null,
            "b" => null,
            "width" => $this->width,
            "target" => array(),
            "via" => array(),
            "bw_in" => $this->maxValuesConfigured[IN],
            "bw_out" => $this->maxValuesConfigured[OUT],
            "infourl" => $this->infourl[IN],
            "overliburl" => $this->overliburl[IN],
            "overlibcaption" => $this->overlibcaption[IN],
            "overlibwidth" => $this->overlibwidth,
            "overlibheight" => $this->overlibheight,
            "commentin" => $this->comments[IN],
            "commentposin" => $this->commentOffsets[IN],
            "commentout" => $this->comments[OUT],
            "commentposout" => $this->commentOffsets[OUT],
        );

        if (isset($this->endpoints[0]->node)) {
            $newOutput['a'] = $this->endpoints[0]->node->name;
        }
        if (isset($this->endpoints[1]->node)) {
            $newOutput['b'] = $this->endpoints[1]->node->name;
        }

        $tgt = '';

        $i = 0;
        foreach ($this->targets as $target) {
            if ($i > 0) {
                $tgt .= ' ';
            }
            $tgt .= $target->asConfig();
            $i++;
        }
        $newOutput["target"] = $tgt;

        $newOutput['via'] = $this->viaList;

        return $newOutput;
    }

    public function cleanUp()
    {
        parent::cleanUp();

        $this->owner = null;
        $this->endpoints[0]->node = null;
        $this->endpoints[1]->node = null;
        $this->descendents = null;
    }

    public function getValue($name)
    {
        MapUtility::debug("Fetching %s\n", $name);
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new WeathermapInternalFail('NoSuchProperty');
    }

    /**
     * Set the new ends for a link.
     *
     * @param MapNode $node1
     * @param MapNode $node2
     * @throws WeathermapInternalFail if passed any nulls (should never happen)
     */
    public function setEndNodes($node1, $node2)
    {
        if (null !== $node1 && null === $node2) {
            throw new WeathermapInternalFail('PartiallyRealLink');
        }

        if (null !== $node2 && null === $node1) {
            throw new WeathermapInternalFail('PartiallyRealLink');
        }

        if (null !== $this->endpoints[0]->node) {
            $this->endpoints[0]->node->removeDependency($this);
        }
        if (null !== $this->endpoints[1]->node) {
            $this->endpoints[1]->node->removeDependency($this);
        }
        $this->endpoints[0]->node = $node1;
        $this->endpoints[1]->node = $node2;

        if (null !== $this->endpoints[0]->node) {
            $this->endpoints[0]->node->addDependency($this);
        }
        if (null !== $this->endpoints[1]->node) {
            $this->endpoints[1]->node->addDependency($this);
        }
    }

    public function asConfigData()
    {
        $config = parent::asConfigData();

        $config['id'] = "L" . $this->id;
        if (!$this->isTemplate()) {
            $config['a'] = $this->endpoints[0]->node->name;
            $config['b'] = $this->endpoints[1]->node->name;
        }
        $config['width'] = $this->width;
        $config['zorder'] = $this->zorder;
        $config['imagemap'] = array();
        foreach ($this->getImageMapAreas() as $area) {
            $config['imagemap'] [] = $area->asJSONData();
        }
        return $config;
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

    public function getProperty($name)
    {
        MapUtility::debug("Fetching %s from %s\n", $name, $this);

        $translations = array(
            'inscalekey' => $this->scaleKeys[IN],
            'outscalekey' => $this->scaleKeys[OUT],
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
        return sprintf("[LINK %s]", $this->name);
    }

    /**
     * @param $direction
     * @return mixed|string
     */
    private function calculateCommentText($direction)
    {
        // Time to deal with Link Comments, if any
        $comment = $this->owner->processString($this->comments[$direction], $this);

        if ($this->owner->getHint('screenshot_mode') == 1) {
            $comment = StringUtility::stringAnonymise($comment);
        }
        return $comment;
    }

    /**
     * @param $fontObject
     * @param $comment
     * @param $direction
     * @param $widthList
     * @return array
     */
    private function calculateCommentPosition($fontObject, $comment, $direction, $widthList)
    {
        list($textWidth, $textHeight) = $fontObject->calculateImageStringSize($comment);

        // nudge pushes the comment out along the link arrow a little bit
        // (otherwise there are more problems with text disappearing underneath links)
        $nudgeAlong = intval($this->getHint('comment_nudgealong'));
        $nudgeOut = intval($this->getHint('comment_nudgeout'));

        /** @var Point $position */
        list ($position, $commentIndex, $angle, $distance) = $this->geometry->findPointAndAngleAtPercentageDistance($this->commentOffsets[$direction]);

        $tangent = $this->geometry->findTangentAtIndex($commentIndex);
        $tangent->normalise();

        $centreDistance = $widthList[$direction] + 4 + $nudgeOut;

        if ($this->commentStyle == 'center') {
            $centreDistance = $nudgeOut - ($textHeight / 2);
        }
        // find the normal to our link, so we can get outside the arrow
        $normal = $tangent->getNormal();

        $flipped = false;

        $edge = $position;

        // if the text will be upside-down, rotate it, flip it, and right-justify it
        // not quite as catchy as Missy's version
        if (abs($angle) > 90) {
            $angle -= 180;
            if ($angle < -180) {
                $angle += 360;
            }
            $edge->addVector($tangent, $nudgeAlong);
            $edge->addVector($normal, -$centreDistance);
            $flipped = true;
        } else {
            $edge->addVector($tangent, $nudgeAlong);
            $edge->addVector($normal, $centreDistance);
        }

        $maxLength = $this->geometry->totalDistance();

        if (!$flipped && ($distance + $textWidth) > $maxLength) {
            $edge->addVector($tangent, -$textWidth);
        }

        if ($flipped && ($distance - $textWidth) < 0) {
            $edge->addVector($tangent, $textWidth);
        }
        return array($angle, $edge);
    }

    public function getOverlibCentre()
    {
        $aX = $this->endpoints[0]->node->x;
        $aY = $this->endpoints[0]->node->y;

        $bX = $this->endpoints[1]->node->x;
        $bY = $this->endpoints[0]->node->y;

        $midX = ($aX + $bX) / 2;
        $midY = ($aY + $bY) / 2;

        return array($midX, $midY);
    }
}

// vim:ts=4:sw=4:
