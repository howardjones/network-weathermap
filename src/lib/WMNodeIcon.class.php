<?php


class WMNodeIcon
{
    protected $type;
    protected $boundingBox;
    protected $node;

    protected $widthScale;
    protected $heightScale;

    protected $iconImageRef;

    protected $iconFileName;

    public function __construct($node)
    {
        $this->type = "";
        $this->node = $node;
        $this->boundingBox = new WMBoundingBox("Icon for $node->name");
        $this->boundingBox->addPoint(0, 0);

        $this->iconFileName = $node->iconfile;
        $this->widthScale = $node->iconscalew;
        $this->heightScale = $node->iconscaleh;
        $this->iconImageRef = null;
    }

    public function __toString()
    {
        return sprintf("%s %s [%dx%d]", get_class($this), $this->iconFileName, $this->widthScale, $this->heightScale);
    }

    public function draw($targetImageRef)
    {
        $boundingBox = $this->getBoundingBox();

        $icon_x = -($boundingBox->width() / 2);
        $icon_y = -($boundingBox->height() / 2);

        wm_debug("Drawing into icon at $icon_x, $icon_y\n");

        imagecopy($targetImageRef, $this->iconImageRef, $icon_x, $icon_y, 0, 0,
            imagesx($this->iconImageRef),
            imagesy($this->iconImageRef));
        imagedestroy($this->iconImageRef);
    }

    public function getBoundingBox()
    {
        return $this->boundingBox;
    }

    public function calculateGeometry()
    {
        $iconImageRef = $this->iconImageRef;

        if ($iconImageRef) {
            $iconHalfWidth = imagesx($iconImageRef) / 2;
            $iconHalfHeight = imagesy($iconImageRef) / 2;
            $iconRect = new WMRectangle(-$iconHalfWidth, -$iconHalfHeight, $iconHalfWidth, $iconHalfHeight);
        } else {
            $iconRect = new WMRectangle(0, 0, 0, 0);
        }

        $this->boundingBox = $iconRect;
    }

    public function getImageRef()
    {
        return $this->iconImageRef;
    }
}

class WMNodeImageIcon extends WMNodeIcon
{
    public function __construct($node)
    {
        parent::__construct($node);
    }

    public function preRender($iconFile, $scaleWidth = null, $scaleHeight = null)
    {

        if (is_readable($iconFile)) {
            // TODO - this should be in Draw(), not here.
            // imagealphablending($im, true);

            // draw the supplied icon, instead of the labelled box
            $this->iconImageRef = imagecreatefromfile($iconFile);

            if (true === isset($this->iconFillColour)) {
                $this->colourizeImage($this->iconImageRef, $this->iconFillColour);
            }

            if ($this->iconImageRef) {
                $iconWidth = imagesx($this->iconImageRef);
                $iconHeight = imagesy($this->iconImageRef);

                if (($scaleWidth * $scaleHeight) > 0) {
                    wm_debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");

                    imagealphablending($this->iconImageRef, true);

                    // figure out which dimension to use when scaling the icon, so that it is all still visible
                    wm_debug("SCALING ICON here\n");
                    if ($iconWidth > $iconHeight) {
                        $scaleFactor = $iconWidth / $scaleWidth;
                    } else {
                        $scaleFactor = $iconHeight / $scaleHeight;
                    }
                    $newWidth = $iconWidth / $scaleFactor;
                    $newHeight = $iconHeight / $scaleFactor;

                    // Scale, and replace the original image with the scaled one
                    $scaledImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagealphablending($scaledImage, false);
                    imagecopyresampled($scaledImage, $this->iconImageRef, 0, 0, 0, 0, $newWidth, $newHeight, $iconWidth,
                        $iconHeight);
                    imagedestroy($this->iconImageRef);
                    $this->iconImageRef = $scaledImage;
                }
            } else {
                throw new WeathermapRuntimeWarning("Couldn't open ICON: '" . $iconFile . "' - is it a PNG, JPEG or GIF? [WMWARN37]");
                //    wm_warn("Couldn't open ICON: '" . $realiconfile . "' - is it a PNG, JPEG or GIF? [WMWARN37]\n");
            }
        } else {
            if ($iconFile != 'none') {
                throw new WeathermapRuntimeWarning("ICON '" . $iconFile . "' does not exist, or is not readable. Check path and permissions. [WMARN38]");
                // wm_warn("ICON '" . $realiconfile . "' does not exist, or is not readable. Check path and permissions. [WMARN38]\n");
            }
        }
    }
}

class WMNodeArtificialIcon extends WMNodeIcon
{
    private static $types = array(
        'rbox' => "WMNodeRoundedBoxIcon",
        'round' => "WMNodeRoundIcon",
        'box' => "WMNodeBoxIcon",
        'inpie' => "WMNodePieIcon",
        'outpie' => "WMNodePieIcon",
        #'gauge' => "",
        'nink' => "WMNodeNINKIcon"
    );
    protected $aiconFillColour;
    protected $aiconInkColour;
    // protected $aiconOutlineColour; // ???
    protected $iconFillColour;
    protected $labelFillColour;

    public function __construct($node, $aiconInkColour, $aiconFillColour, $iconFillColour, $labelFillColour)
    {
        parent::__construct($node);

        $this->iconFileName = $node->iconfile;
        $this->widthScale = $node->iconscalew;
        $this->heightScale = $node->iconscaleh;
        $this->name = $node->name;

        if (!self::isAICONName($this->iconFileName)) {
            throw new WeathermapRuntimeWarning("AICON with invalid type");
        }

        // $this->aiconOutlineColour = $aiconOutlineColour;
        $this->aiconFillColour = $aiconFillColour;
        $this->iconFillColour = $iconFillColour;
        $this->aiconInkColour = $aiconInkColour;
        $this->labelFillColour = $labelFillColour;
    }

    public static function isAICONName($name)
    {
        return array_key_exists($name, self::$types);
    }

    public static function createAICON(
        $name,
        $node,
        $aiconInkColour,
        $aiconFillColour,
        $iconFillColour,
        $labelFillColour
    ) {
        $class = self::$types{$name};

        $iconObj = new $class($node, $aiconInkColour, $aiconFillColour, $iconFillColour,
            $labelFillColour);

        return $iconObj;
    }

    public function getFinalDimensions()
    {
        return new WMRectangle(0, 0, $this->widthScale, $this->heightScale);
    }

    public function calculateGeometry()
    {
        parent::calculateGeometry();
    }

    public function preRender()
    {
        wm_debug("Artificial Icon type " . $this->iconFileName . " for $this->name\n");
        // this is an artificial icon - we don't load a file for it

        $this->createEmptyImage();

        $fill = $this->aiconFillColour;
        $ink = $this->aiconInkColour;

//        // if useiconscale isn't set, then use the static colour defined, or copy the colour from the label
//        if ($this->useiconscale == "none") {
//            if ($aiconFillColour->isCopy() && !$labelFillColour->isNone()) {
//                $fill = $labelFillColour;
//            } else {
//                if ($aiconFillColour->isRealColour()) {
//                    $fill = $aiconFillColour;
//                }
//            }
//        } else {
//            // if useiconscale IS defined, use that to figure out the fill colour
//
//            $fill = $iconFillColour;
//        }

//        // support 'none' and 'copy' for AICON outlines too
//        if (!$this->aiconoutlinecolour->isNone() && $this->aiconoutlinecolor->isCopy()) {
//            $ink = $labelFillColour;
//        } else {
//            if ($aiconOutlineColour->isRealColour()) {
//                $ink = $aiconInkColour;
//            }
//        }

        wm_debug("AICON colours are $ink and $fill\n");

        $this->drawAIcon();

    }

    protected function createEmptyImage()
    {
        $this->iconImageRef = imagecreatetruecolor($this->widthScale, $this->heightScale);
        imageSaveAlpha($this->iconImageRef, true);

        $nothing = imagecolorallocatealpha($this->iconImageRef, 128, 0, 0, 127);
        imagefill($this->iconImageRef, 0, 0, $nothing);
    }

    public function drawAIcon()
    {

    }


}

class WMNodeNINKIcon extends WMNodeArtificialIcon
{
    public function drawAIcon()
    {
        $iconImageRef = $this->iconImageRef;
        $fill = $this->aiconFillColour;
        $ink = $this->aiconInkColour;

        $radiusX = $this->widthScale / 2 - 1;
        $radiusY = $this->heightScale / 2 - 1;
        $size = $this->widthScale;
        $quarter = $size / 4;

        $colour1 = $this->node->colours[OUT]->gdallocate($iconImageRef);
        $colour2 = $this->node->colours[IN]->gdallocate($iconImageRef);

        imagefilledarc($iconImageRef, $radiusX - 1, $radiusY, $size, $size, 270, 90, $colour1, IMG_ARC_PIE);
        imagefilledarc($iconImageRef, $radiusX + 1, $radiusY, $size, $size, 90, 270, $colour2, IMG_ARC_PIE);

        imagefilledarc($iconImageRef, $radiusX - 1, $radiusY + $quarter, $quarter * 2, $quarter * 2, 0, 360, $colour1,
            IMG_ARC_PIE);
        imagefilledarc($iconImageRef, $radiusX + 1, $radiusY - $quarter, $quarter * 2, $quarter * 2, 0, 360, $colour2,
            IMG_ARC_PIE);

        if ($ink !== null && !$ink->isNone()) {
            // XXX - need a font definition from somewhere for NINK text
            $font = 1;
            $inkGD = $ink->gdallocate($iconImageRef);

            $directions = array(
                array("in", -1),
                array("out", +1)
            );

            foreach ($directions as $direction) {
                $name = $direction[0];
                $label = $this->node->owner->ProcessString("{node:this:bandwidth_$name:%.1k}", $this->node);

                list($twid, $thgt) = $this->node->owner->myimagestringsize($font, $label);
                $this->node->owner->myimagestring($iconImageRef, $font, $radiusX - $twid / 2,
                    $radiusY + $direction[1] * $quarter + ($thgt / 2), $label, $inkGD);
            }

            imageellipse($iconImageRef, $radiusX, $radiusY, $radiusX * 2, $radiusY * 2, $inkGD);
        }
    }
}

class WMNodeBoxIcon extends WMNodeArtificialIcon
{
    public function drawAIcon()
    {
        $iconImageRef = $this->iconImageRef;
        $fill = $this->aiconFillColour;
        $ink = $this->aiconInkColour;

        if ($fill !== null && !$fill->isNone()) {
            imagefilledrectangle($iconImageRef, 0, 0, $this->widthScale - 1, $this->heightScale - 1,
                $fill->gdAllocate($iconImageRef));
        }

        if ($ink !== null && !$ink->isNone()) {
            imagerectangle($iconImageRef, 0, 0, $this->widthScale - 1, $this->heightScale - 1,
                $ink->gdallocate($iconImageRef));
        }
    }

}

class WMNodeRoundedBoxIcon extends WMNodeArtificialIcon
{
    public function drawAIcon()
    {
        $iconImageRef = $this->iconImageRef;
        $fill = $this->aiconFillColour;
        $ink = $this->aiconInkColour;

        if ($fill !== null && !$fill->isNone()) {
            imagefilledroundedrectangle($iconImageRef, 0, 0, $this->widthScale - 1, $this->heightScale - 1, 4,
                $fill->gdAllocate($iconImageRef));
        }

        if ($ink !== null && !$ink->isNone()) {
            imageroundedrectangle($iconImageRef, 0, 0, $this->widthScale - 1, $this->heightScale - 1, 4,
                $ink->gdallocate($iconImageRef));
        }
    }

}

class WMNodeRoundIcon extends WMNodeArtificialIcon
{

    public function drawAIcon()
    {
        $iconImageRef = $this->iconImageRef;
        $fill = $this->aiconFillColour;
        $ink = $this->aiconInkColour;

        $radiusX = $this->widthScale / 2 - 1;
        $radiusY = $this->heightScale / 2 - 1;

        if ($fill !== null && !$fill->isNone()) {
            imagefilledellipse($iconImageRef, $radiusX, $radiusY, $radiusX * 2, $radiusY * 2,
                $fill->gdAllocate($iconImageRef));
        }

        if ($ink !== null && !$ink->isNone()) {
            imageellipse($iconImageRef, $radiusX, $radiusY, $radiusX * 2, $radiusY * 2,
                $ink->gdallocate($iconImageRef));
        }
    }

}

class WMNodePieIcon extends WMNodeArtificialIcon
{
    public function drawAIcon()
    {
        $iconImageRef = $this->iconImageRef;
        $fill = $this->aiconFillColour;
        $ink = $this->aiconInkColour;

        if ($this->iconFileName == 'inpie') {
            $segment_angle = (($this->node->percentUsages[IN]) / 100) * 360;
        }
        if ($this->iconFileName == 'outpie') {
            $segment_angle = (($this->node->percentUsages[OUT]) / 100) * 360;
        }

        $radiusX = $this->widthScale / 2 - 1;
        $radiusY = $this->heightScale / 2 - 1;

        if ($fill !== null && !$fill->isNone()) {
            imagefilledellipse($iconImageRef, $radiusX, $radiusY, $radiusX * 2, $radiusY * 2,
                $fill->gdAllocate($iconImageRef));
        }

        if ($ink !== null && !$ink->isNone()) {
            imagefilledarc($iconImageRef, $radiusX, $radiusY, $radiusX * 2, $radiusY * 2, 0, $segment_angle,
                $ink->gdallocate($iconImageRef), IMG_ARC_PIE);
        }

        if ($fill !== null && !$fill->isNone()) {
            imageellipse($iconImageRef, $radiusX, $radiusY, $radiusX * 2, $radiusY * 2,
                $fill->gdAllocate($iconImageRef));
        }
    }
}
