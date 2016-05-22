<?php

class WMNodeLabel
{
    private $boundingBox;
    private $node;
    private $map;

    private $textPosition;
    private $labelPosition;
    private $labelAngle;
    private $labelString;
    private $labelFont;
    private $labelRectangle;

    private $x;
    private $y;
    private $name;

    private $labelFillColour;
    private $labelOutlineColour;
    private $labelShadowColour;
    private $labelTextColour;
    private $selectedColour;

    private $stringHeight;
    private $stringWidth;

    public function __construct($node)
    {
        $this->node = $node;
        $this->map = $node->owner;

        $position = $node->getPosition();

        $this->boundingBox = new WMBoundingBox("Label for $node->name");
        $this->boundingBox->addWMPoint($position);
        $this->textPosition = $position;
        $this->labelPosition = $position;
        $this->labelAngle = $node->labelangle;

        $this->x = $position->x;
        $this->y = $position->y;
        $this->name = $node->name;
    }

    public function getBoundingBox()
    {
        return $this->boundingBox->getBoundingRectangle();
    }

    /**
     * Calculate the bounding box of the label, centred around 0,0 so it can be used to position the
     * label relative to the icon (if there is one) by the parent Node drawing function. Only the label
     * needs to know how text is laid out - the node should just see boxes (via getBoundingBox)
     *
     * @param $labelString
     * @param $labelFont
     */
    public function calculateGeometry($labelString, $labelFont)
    {
        $this->labelFont = $labelFont;
        $this->labelString = $labelString;

        list($stringWidth, $stringHeight) = $this->map->myimagestringsize($labelFont, $labelString);

        $stringHalfHeight = $stringHeight / 2;
        $stringHalfWidth = $stringWidth / 2;

        $this->textPosition = new WMPoint(0, 0);

        wm_debug("Node->Label->pre_render: centred: $this->textPosition\n");

        $this->stringHeight = $stringHeight;
        $this->stringWidth = $stringHalfWidth;
        $this->calculateOutlineGeometry();

        if ($this->labelAngle == 90) {
            $this->textPosition = new WMPoint($stringHalfHeight, $stringHalfWidth);
//                $this->translate($stringHalfHeight, $stringHalfWidth);
        }

        if ($this->labelAngle == 270) {
            $this->textPosition = new WMPoint(-$stringHalfHeight, -$stringHalfWidth);
//                $this->translate(-$stringHalfHeight, -$stringHalfWidth);
        }

        if ($this->labelAngle == 0) {
            $this->textPosition = new WMPoint(-$stringHalfWidth, -$stringHalfHeight);
//                $this->translate(-$stringHalfWidth, -$stringHalfHeight);
        }

        if ($this->labelAngle == 180) {
            $this->textPosition = new WMPoint($stringHalfWidth, $stringHalfHeight);
//                $this->translate($stringHalfWidth, $stringHalfHeight);
        }
        wm_debug("Node->Label->pre_render: final: $this->textPosition\n");

        wm_debug("Node->Label->pre_render: " . $this->name . " Label Metrics are: $stringWidth x $stringHeight\n");

        $this->boundingBox->addRectangle($this->labelRectangle);

    }

    public function translate($deltaX, $deltaY)
    {
        $this->textPosition->translate($deltaX, $deltaY);
        wm_debug("Node->Label: translated by $deltaX, $deltaY: $this->textPosition\n");
        $this->calculateOutlineGeometry();
    }

    public function preRender(
        $labelFillColour,
        $labelOutlineColour,
        $labelShadowColour,
        $labelTextColour,
        $selectedColour
    ) {
        $this->labelFillColour = $labelFillColour;
        $this->labelOutlineColour = $labelOutlineColour;
        $this->labelShadowColour = $labelShadowColour;
        $this->labelTextColour = $labelTextColour;
        $this->selectedColour = $selectedColour;
    }

    public function draw($imageRef, $centre_x, $centre_y)
    {
        $txt_x = $this->textPosition->x;
        $txt_y = $this->textPosition->y;

        wm_debug("$this->labelRectangle + $centre_x + $centre_y\n");

        // XXX - this had better be temporary!
        $label_x1 = $this->labelRectangle->topLeft->x + $centre_x;
        $label_y1 = $this->labelRectangle->topLeft->y + $centre_y;
        $label_x2 = $this->labelRectangle->bottomRight->x + $centre_x;
        $label_y2 = $this->labelRectangle->bottomRight->y + $centre_y;

        wm_debug("DRAW FINAL TXT $txt_x,$txt_y\n");
        wm_debug("DRAW FINAL RECT $label_x1,$label_y1-$label_x2,$label_y2\n");

        // if there's an icon, then you can choose to have no background

        if (!$this->node->resolvedColours['labelfill']->isNone()) {
            imagefilledrectangle($imageRef, $label_x1, $label_y1, $label_x2, $label_y2,
                $this->node->resolvedColours['labelfill']->gdAllocate($imageRef));
        }

        if ($this->node->selected) {
            imagerectangle($imageRef, $label_x1, $label_y1, $label_x2, $label_y2, $this->selectedColour);
            // would be nice if it was thicker, too...
            imagerectangle($imageRef, $label_x1 + 1, $label_y1 + 1, $label_x2 - 1, $label_y2 - 1,
                $this->selectedColour);
        } else {
            // $label_outline_colour = $this->labeloutlinecolour;
            if ($this->labelOutlineColour->isRealColour()) {
                imagerectangle($imageRef, $label_x1, $label_y1, $label_x2, $label_y2,
                    $this->labelOutlineColour->gdallocate($imageRef));
            }
        }

        if ($this->labelShadowColour->isRealColour()) {
            $this->node->owner->myimagestring(
                $imageRef,
                $this->labelFont,
                $txt_x + 1 + $centre_x,
                $txt_y + 1 + $centre_y,
                $this->labelString,
                $this->labelShadowColour->gdallocate($imageRef),
                $this->labelAngle
            );
        }

        $txcol = $this->labelTextColour;

        if ($txcol->isContrast()) {
            if ($this->labelFillColour->isRealColour()) {
                $txcol = $this->labelFillColour->getContrastingColour();
            } else {
                wm_warn("You can't make a contrast with 'none' - guessing black. [WMWARN43]\n");
                $txcol = new WMColour(0, 0, 0);
            }
        }

        $this->map->myimagestring(
            $imageRef,
            $this->labelFont,
            $txt_x + $centre_x,
            $txt_y + $centre_y,
            $this->labelString,
            $txcol->gdAllocate($imageRef),
            $this->labelAngle
        );
    }

    /**
     * Calculate the surrounding rectangle for a given size of text.
     *
     * @param $stringHeight
     * @param $stringWidth
     *
     * @return array
     */
    private function calculateOutlineGeometry()
    {
        $stringHeight = $this->stringHeight;
        $stringWidth = $this->stringWidth;

        $padding = 4.0;
        $padFactor = 1.0;

        if ($this->labelAngle == 90 || $this->labelAngle == 270) {
            $boxWidth = ($stringHeight * $padFactor) + $padding;
            $boxHeight = ($stringWidth * $padFactor) + $padding;
        } else {
            $boxWidth = ($stringWidth * $padFactor) + $padding;
            $boxHeight = ($stringHeight * $padFactor) + $padding;
        }

        $halfWidth = $boxWidth / 2;
        $halfHeight = $boxHeight / 2;

        wm_debug("box is $boxWidth x $boxHeight\n");
        wm_debug("position is $this->textPosition\n");

        $this->labelRectangle = new WMRectangle($this->textPosition->x - $halfWidth,
            $this->textPosition->y - $halfHeight,
            $this->textPosition->x + $halfWidth,
            $this->textPosition->y + $halfHeight);

        wm_debug("Node->Label->pre_render: Rect is $this->labelRectangle\n");

        return array($boxWidth, $boxHeight);
    }
}
