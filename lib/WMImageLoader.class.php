<?php

class WMImageLoader
{
    var $cache = array();
    // for now, disable caching. The imageduplicate() function doesn't work in all cases.
    var $cacheEnabled = true;

    // we don't want to be caching huge images (they are probably the background, and won't be re-used)
    function isCacheable($width, $height)
    {
        if (! $this->cacheEnabled) {
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
     * @param WMColour $colour
     * @param string $colourMethod
     * @return null|resource
     */
    function imagecreatescaledcolourizedfromfile($filename, $scaleWidth, $scaleHeight, $colour, $colourMethod)
    {

        wm_debug("Getting a (maybe cached) scaled coloured image for $filename at $scaleWidth x $scaleHeight with $colour\n");

        $key = sprintf("%s:%d:%d:%s:%s", $filename, $scaleWidth, $scaleHeight,
            $colour->asString(), $colourMethod);
        wm_debug("$key\n");

        if (array_key_exists($key, $this->cache)) {
            wm_debug("Cache hit for $key\n");
            $iconImageRef = $this->cache[$key];
            wm_debug("From cache: $iconImageRef\n");
            $finalImageRef = $this->imageduplicate($iconImageRef);
        } else {
            wm_debug("Cache miss - processing\n");
            $iconImageRef = $this->imagecreatefromfile($filename);
            //    imagealphablending($icon_im, true);

            $this->colourizeImage($colour, $colourMethod, $iconImageRef);

            $iconImageRef = $this->scaleImage($scaleWidth, $scaleHeight, $iconImageRef);

            $finalImageRef = $this->updateCache($scaleWidth, $scaleHeight, $key, $iconImageRef);
        }

        wm_debug("Returning $finalImageRef\n");
        return $finalImageRef;
    }

    /**
     * @param string $filename
     * @param int $scaleWidth
     * @param int $scaleHeight
     * @return null|resource
     */
    function imagecreatescaledfromfile($filename, $scaleWidth, $scaleHeight)
    {
        list($width, $height, $type, $attr) = getimagesize($filename);

        wm_debug("Getting a (maybe cached) image for $filename at $scaleWidth x $scaleHeight\n");

        // do the non-scaling version if no scaling is required
        if ($scaleWidth == 0 && $scaleHeight == 0) {
            wm_debug("No scaling, punt to regular\n");
            return $this->imagecreatefromfile($filename);
        }

        if ($width == $scaleWidth && $height == $scaleHeight) {
            wm_debug("No scaling, punt to regular\n");
            return $this->imagecreatefromfile($filename);
        }
        $key = sprintf("%s:%d:%d", $filename, $scaleWidth, $scaleHeight);

        if (array_key_exists($key, $this->cache)) {
            wm_debug("Cache hit for $key\n");
            $iconImageRef = $this->cache[$key];
            wm_debug("From cache: $iconImageRef\n");
            $finalImageRef = $this->imageduplicate($iconImageRef);
        } else {
            wm_debug("Cache miss - processing\n");
            $iconImageRef = $this->imagecreatefromfile($filename);

            $iconImageRef = $this->scaleImage($scaleWidth, $scaleHeight, $iconImageRef);

            $finalImageRef = $this->updateCache($scaleWidth, $scaleHeight, $key, $iconImageRef);
        }

        wm_debug("Returning $finalImageRef\n");
        return $finalImageRef;
    }

    function imageduplicate($sourceImageRef)
    {
        $source_width = imagesx($sourceImageRef);
        $source_height = imagesy($sourceImageRef);

        if (imageistruecolor($sourceImageRef)) {
            wm_debug("Duplicating $source_width x $source_height TC image\n");
            $newImageRef = imagecreatetruecolor($source_width, $source_height);
            imagealphablending($newImageRef, false);
            imagesavealpha($newImageRef, true);
        } else {
            wm_debug("Duplicating $source_width x $source_height palette image\n");
            $newImageRef = imagecreate($source_width, $source_height);
            $trans = imagecolortransparent($sourceImageRef);
            if ($trans >= 0) {
                wm_debug("Duplicating transparency in indexed image\n");
                $rgb = imagecolorsforindex($sourceImageRef, $trans);
                $trans_index = imagecolorallocatealpha($newImageRef, $rgb['red'], $rgb['green'], $rgb['blue'],
                    $rgb['alpha']);
                imagefill($newImageRef, 0, 0, $trans_index);
                imagecolortransparent($newImageRef, $trans_index);
            }
        }

        imagecopy($newImageRef, $sourceImageRef, 0, 0, 0, 0, $source_width, $source_height);

        return $newImageRef;
    }

    /**
     * @param $filename
     * @return null|resource
     */
    function imagecreatefromfile($filename)
    {
        $result_image = NULL;
        $new_image = NULL;
        if (is_readable($filename)) {
            list($width, $height, $type, $attr) = getimagesize($filename);
            $key = $filename;

            if (array_key_exists($key, $this->cache)) {
                wm_debug("Cache hit! for $key\n");
                $cache_image = $this->cache[$key];
                wm_debug("From cache: $cache_image\n");
                $new_image = $this->imageduplicate($cache_image);
                wm_debug("$new_image\n");
            } else {
                wm_debug("Cache miss - processing\n");

                switch ($type) {
                    case IMAGETYPE_GIF:
                        if (imagetypes() & IMG_GIF) {
                            wm_debug("Load gif\n");
                            $new_image = imagecreatefromgif($filename);
                        } else {
                            wm_warn("Image file $filename is GIF, but GIF is not supported by your GD library. [WMIMG01]\n");
                        }
                        break;

                    case IMAGETYPE_JPEG:
                        if (imagetypes() & IMG_JPEG) {
                            wm_debug("Load jpg\n");
                            $new_image = imagecreatefromjpeg($filename);
                        } else {
                            wm_warn("Image file $filename is JPEG, but JPEG is not supported by your GD library. [WMIMG02]\n");
                        }
                        break;

                    case IMAGETYPE_PNG:
                        if (imagetypes() & IMG_PNG) {
                            wm_debug("Load png\n");
                            $new_image = imagecreatefrompng($filename);
                        } else {
                            wm_warn("Image file $filename is PNG, but PNG is not supported by your GD library. [WMIMG03]\n");
                        }
                        break;

                    default:
                        wm_warn("Image file $filename wasn't recognised (type=$type). Check format is supported by your GD library. [WMIMG04]\n");
                        break;
                }
            }
            if (!is_null($new_image) && $this->isCacheable($width, $height)) {
                wm_debug("Caching $key $new_image\n");
                $this->cache[$key] = $new_image;
                $result_image = $this->imageduplicate($new_image);
            } else {
                $result_image = $new_image;
            }
        } else {
            wm_warn("Image file $filename is unreadable. Check permissions. [WMIMG05]\n");
        }
        wm_debug("Returning $result_image\n");
        return $result_image;
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

        wm_debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");

        if ($scaleWidth > 0 && $scaleHeight > 0) {

            wm_debug("SCALING ICON here\n");
            if ($iconWidth > $iconHeight) {
                $scaleFactor = $iconWidth / $scaleWidth;
            } else {
                $scaleFactor = $iconHeight / $scaleHeight;
            }
            if ($scaleFactor != 1.0) {
                $new_width = $iconWidth / $scaleFactor;
                $new_height = $iconHeight / $scaleFactor;

                $scaledImageRef = imagecreatetruecolor($new_width, $new_height);

                imagesavealpha($scaledImageRef, true);
                imagealphablending($scaledImageRef, false);
                imagecopyresampled($scaledImageRef, $iconImageRef, 0, 0, 0, 0, $new_width, $new_height, $iconWidth,
                    $iconHeight);
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
            wm_debug("Caching [$key]=$iconImageRef\n");
            $this->cache[$key] = $iconImageRef;
            $finalImageRef = $this->imageduplicate($iconImageRef);
            return $finalImageRef;
        } else {
            $finalImageRef = $iconImageRef;
            return $finalImageRef;
        }
    }

    /**
     * @param WMColour $colour
     * @param string $colourMethod
     * @param resource $iconImageRef
     */
    private function colourizeImage($colour, $colourMethod, $iconImageRef)
    {
        wm_debug("$colourMethod\n");
        if ($colourMethod == 'imagefilter') {
            wm_debug("Colorizing (imagefilter)...\n");
            list ($red, $green, $blue) = $colour->getComponents();
            imagefilter($iconImageRef, IMG_FILTER_COLORIZE, $red, $green, $blue);
        }

        if ($colourMethod == 'imagecolorize') {
            wm_debug("Colorizing (imagecolorize)...\n");
            list ($red, $green, $blue) = $colour->getComponents();
            imagecolorize($iconImageRef, $red, $green, $blue);
        }
    }
}
