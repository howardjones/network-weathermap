<?php
namespace Weathermap\Core;

/**
 * A handler for loading images required by the map (e.g. backgrounds and icons). It also
 * is responsible for any colorizing and resizing necessary. It maintains a cache, to try and speed up
 * maps with many resized icons, in particular.
 *
 * @package Weathermap\Core
 */
class ImageLoader
{
    private $cache = array();
    // for now, disable caching. The imageduplicate() function doesn't work in all cases.
    private $cacheEnabled = true;

    // we don't want to be caching huge images (they are probably the background, and won't be re-used)
    private function isCacheable($width, $height)
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        if ($width * $height > 65536) {
            return false;
        }

        return true;
    }

    /**
     * @param string $filename
     * @param int $scaleWidth
     * @param int $scaleHeight
     * @param Colour $colour
     * @param string $colourMethod
     * @return null|resource
     */
    public function imagecreatescaledcolourizedfromfile($filename, $scaleWidth, $scaleHeight, $colour, $colourMethod)
    {
        MapUtility::debug("Getting a (maybe cached) scaled coloured image for $filename at $scaleWidth x $scaleHeight with $colour\n");

        $key = sprintf('%s:%d:%d:%s:%s', $filename, $scaleWidth, $scaleHeight, $colour->asString(), $colourMethod);
        MapUtility::debug("$key\n");

        if (array_key_exists($key, $this->cache)) {
            MapUtility::debug("Cache hit for $key\n");
            $iconImageRef = $this->cache[$key];
            MapUtility::debug("From cache: $iconImageRef\n");
            $finalImageRef = $this->imageduplicate($iconImageRef);
        } else {
            MapUtility::debug("Cache miss - processing\n");
            $iconImageRef = $this->imagecreatefromfile($filename);
            //    imagealphablending($icon_im, true);

            $this->colourizeImage($colour, $colourMethod, $iconImageRef);

            $iconImageRef = $this->scaleImage($scaleWidth, $scaleHeight, $iconImageRef);

            $finalImageRef = $this->updateCache($scaleWidth, $scaleHeight, $key, $iconImageRef);
        }

        MapUtility::debug("Returning $finalImageRef\n");
        return $finalImageRef;
    }

    /**
     * @param string $filename
     * @param int $scaleWidth
     * @param int $scaleHeight
     * @return null|resource
     */
    public function imagecreatescaledfromfile($filename, $scaleWidth, $scaleHeight)
    {
        list($width, $height) = getimagesize($filename);

        MapUtility::debug("Getting a (maybe cached) image for $filename at $scaleWidth x $scaleHeight\n");

        // do the non-scaling version if no scaling is required
        if ($scaleWidth == 0 && $scaleHeight == 0) {
            MapUtility::debug("No scaling, punt to regular\n");
            return $this->imagecreatefromfile($filename);
        }

        if ($width == $scaleWidth && $height == $scaleHeight) {
            MapUtility::debug("No scaling, punt to regular\n");
            return $this->imagecreatefromfile($filename);
        }
        $key = sprintf('%s:%d:%d', $filename, $scaleWidth, $scaleHeight);

        if (array_key_exists($key, $this->cache)) {
            MapUtility::debug("Cache hit for $key\n");
            $iconImageRef = $this->cache[$key];
            MapUtility::debug("From cache: $iconImageRef\n");
            $finalImageRef = $this->imageduplicate($iconImageRef);
        } else {
            MapUtility::debug("Cache miss - processing\n");
            $iconImageRef = $this->imagecreatefromfile($filename);

            $iconImageRef = $this->scaleImage($scaleWidth, $scaleHeight, $iconImageRef);

            $finalImageRef = $this->updateCache($scaleWidth, $scaleHeight, $key, $iconImageRef);
        }

        MapUtility::debug("Returning $finalImageRef\n");
        return $finalImageRef;
    }

    public function imageduplicate($sourceImageRef)
    {
        $sourceWidth = imagesx($sourceImageRef);
        $sourceHeight = imagesy($sourceImageRef);

        if (imageistruecolor($sourceImageRef)) {
            MapUtility::debug("Duplicating $sourceWidth x $sourceHeight TC image\n");
            $newImageRef = imagecreatetruecolor($sourceWidth, $sourceHeight);
            imagealphablending($newImageRef, false);
            imagesavealpha($newImageRef, true);
        } else {
            MapUtility::debug("Duplicating $sourceWidth x $sourceHeight palette image\n");
            $newImageRef = imagecreate($sourceWidth, $sourceHeight);
            $trans = imagecolortransparent($sourceImageRef);
            if ($trans >= 0) {
                MapUtility::debug("Duplicating transparency in indexed image\n");
                $rgb = imagecolorsforindex($sourceImageRef, $trans);
                $transparentIndex = imagecolorallocatealpha($newImageRef, $rgb['red'], $rgb['green'], $rgb['blue'], $rgb['alpha']);
                imagefill($newImageRef, 0, 0, $transparentIndex);
                imagecolortransparent($newImageRef, $transparentIndex);
            }
        }

        imagecopy($newImageRef, $sourceImageRef, 0, 0, 0, 0, $sourceWidth, $sourceHeight);

        return $newImageRef;
    }

    /**
     * @param $filename
     * @return null|resource
     */
    public function imagecreatefromfile($filename)
    {
        $resultImage = null;
        $newImage = null;

        if (is_readable($filename)) {
            list($width, $height, $type) = getimagesize($filename);
            $key = $filename;

            if (array_key_exists($key, $this->cache)) {
                MapUtility::debug("Cache hit! for $key\n");
                $cacheImage = $this->cache[$key];
                MapUtility::debug("From cache: $cacheImage\n");
                $newImage = $this->imageduplicate($cacheImage);
                MapUtility::debug("$newImage\n");
            } else {
                MapUtility::debug("Cache miss - processing\n");

                switch ($type) {
                    case IMAGETYPE_GIF:
                        if (imagetypes() & IMG_GIF) {
                            MapUtility::debug("Load gif\n");
                            $newImage = imagecreatefromgif($filename);
                        } else {
                            MapUtility::warn("Image file $filename is GIF, but GIF is not supported by your GD library. [WMIMG01]\n");
                        }
                        break;

                    case IMAGETYPE_JPEG:
                        if (imagetypes() & IMG_JPEG) {
                            MapUtility::debug("Load jpg\n");
                            $newImage = imagecreatefromjpeg($filename);
                        } else {
                            MapUtility::warn("Image file $filename is JPEG, but JPEG is not supported by your GD library. [WMIMG02]\n");
                        }
                        break;

                    case IMAGETYPE_PNG:
                        if (imagetypes() & IMG_PNG) {
                            MapUtility::debug("Load png\n");
                            $newImage = imagecreatefrompng($filename);
                        } else {
                            MapUtility::warn("Image file $filename is PNG, but PNG is not supported by your GD library. [WMIMG03]\n");
                        }
                        break;

                    default:
                        MapUtility::warn("Image file $filename wasn't recognised (type=$type). Check format is supported by your GD library. [WMIMG04]\n");
                        break;
                }
            }
            if (!is_null($newImage) && $this->isCacheable($width, $height)) {
                MapUtility::debug("Caching $key $newImage\n");
                $this->cache[$key] = $newImage;
                $resultImage = $this->imageduplicate($newImage);
            } else {
                $resultImage = $newImage;
            }
        } else {
            MapUtility::warn("Image file $filename is unreadable. Check permissions. [WMIMG05]\n");
        }

        MapUtility::debug("Returning $resultImage\n");
        return $resultImage;
    }

    /**
     * @param int $scaleWidth
     * @param int $scaleHeight
     * @param resource $iconImageRef
     * @return resource
     */
    private function scaleImage($scaleWidth, $scaleHeight, $iconImageRef)
    {
        $iconWidth = imagesx($iconImageRef);
        $iconHeight = imagesy($iconImageRef);

        MapUtility::debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");

        if ($scaleWidth > 0 && $scaleHeight > 0) {
            MapUtility::debug("SCALING ICON here\n");
            if ($iconWidth > $iconHeight) {
                $scaleFactor = $iconWidth / $scaleWidth;
            } else {
                $scaleFactor = $iconHeight / $scaleHeight;
            }
            if ($scaleFactor != 1.0) {
                $newWidth = $iconWidth / $scaleFactor;
                $newHeight = $iconHeight / $scaleFactor;

                $scaledImageRef = imagecreatetruecolor($newWidth, $newHeight);

                imagesavealpha($scaledImageRef, true);
                imagealphablending($scaledImageRef, false);
                imagecopyresampled($scaledImageRef, $iconImageRef, 0, 0, 0, 0, $newWidth, $newHeight, $iconWidth, $iconHeight);
                imagedestroy($iconImageRef);
                $iconImageRef = $scaledImageRef;
            }
        }
        return $iconImageRef;
    }

    /**
     * @param int $scaleWidth
     * @param int $scaleHeight
     * @param string $key
     * @param resource $iconImageRef
     * @return resource
     */
    private function updateCache($scaleWidth, $scaleHeight, $key, $iconImageRef)
    {
        if ($this->isCacheable($scaleWidth, $scaleHeight)) {
            MapUtility::debug("Caching [$key]=$iconImageRef\n");
            $this->cache[$key] = $iconImageRef;
            $finalImageRef = $this->imageduplicate($iconImageRef);
            return $finalImageRef;
        } else {
            $finalImageRef = $iconImageRef;
            return $finalImageRef;
        }
    }

    /**
     * @param Colour $colour
     * @param string $colourMethod
     * @param resource $iconImageRef
     */
    private function colourizeImage($colour, $colourMethod, $iconImageRef)
    {
        MapUtility::debug("$colourMethod\n");
        if ($colourMethod == 'imagefilter') {
            MapUtility::debug("Colorizing (imagefilter)...\n");
            list ($red, $green, $blue) = $colour->getComponents();
            imagefilter($iconImageRef, IMG_FILTER_COLORIZE, $red, $green, $blue);
        }

        if ($colourMethod == 'imagecolorize') {
            MapUtility::debug("Colorizing (imagecolorize)...\n");
            list ($red, $green, $blue) = $colour->getComponents();
            ImageUtility::imageColorize($iconImageRef, $red, $green, $blue);
        }
    }
}
