<?php

class WMImageLoader
{
    var $cache = array();

    function load_image($filename)
    {

    }

    // we don't want to be caching huge images (they are probably the background, and won't be re-used)
    function cacheable($width, $height)
    {
        // for now, disable this. The imageduplicate() function doesn't work in all cases.
        return false;

        if ($width * $height > 65536) {
            return false;
        }
        return true;
    }

    /**
     * @param string $filename
     * @param int $scalew
     * @param int $scaleh
     * @param WMColour $colour
     * @param string $colour_method
     * @return null|resource
     */
    function imagecreatescaledcolourizedfromfile($filename, $scalew, $scaleh, $colour, $colour_method)
    {

        wm_debug("Getting a (maybe cached) scaled coloured image for $filename at $scalew x $scaleh with $colour\n");

        $key = sprintf("%s:%d:%d:%s:%s", $filename, $scalew, $scaleh,
            $colour->asString(), $colour_method);
        wm_debug("$key\n");

        if (array_key_exists($key, $this->cache)) {
            wm_debug("Cache hit for $key\n");
            $icon_im = $this->cache[$key];
            wm_debug("From cache: $icon_im\n");
            $real_im = $this->imageduplicate($icon_im);
        } else {
            wm_debug("Cache miss - processing\n");
            $icon_im = $this->imagecreatefromfile($filename);
            //    imagealphablending($icon_im, true);

            $icon_w = imagesx($icon_im);
            $icon_h = imagesy($icon_im);

            wm_debug("$colour_method\n");
            if ($colour_method == 'imagefilter') {
                wm_debug("Colorizing (imagefilter)...\n");
                list ($red, $green, $blue) = $colour->getComponents();
                imagefilter($icon_im, IMG_FILTER_COLORIZE, $red, $green, $blue);
            }

            if ($colour_method == 'imagecolorize') {
                wm_debug("Colorizing (imagecolorize)...\n");
                list ($red, $green, $blue) = $colour->getComponents();
                imagecolorize($icon_im, $red, $green, $blue);
            }

            if ($scalew > 0 && $scaleh > 0) {

                wm_debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");

                wm_debug("SCALING ICON here\n");
                if ($icon_w > $icon_h) {
                    $scalefactor = $icon_w / $scalew;
                } else {
                    $scalefactor = $icon_h / $scaleh;
                }
                if ($scalefactor != 1.0) {
                    $new_width = $icon_w / $scalefactor;
                    $new_height = $icon_h / $scalefactor;

                    $scaled = imagecreatetruecolor($new_width, $new_height);
                    imagealphablending($scaled, false);
                    imagecopyresampled($scaled, $icon_im, 0, 0, 0, 0, $new_width, $new_height, $icon_w,
                        $icon_h);
                    imagedestroy($icon_im);
                    $icon_im = $scaled;
                }
            }
            if ($this->cacheable($scalew, $scaleh)) {
                wm_debug("Caching $key $icon_im\n");
                $this->cache[$key] = $icon_im;
                $real_im = $this->imageduplicate($icon_im);
            } else {
                $real_im = $icon_im;
            }
        }

        wm_debug("Returning $real_im\n");
        return $real_im;
    }

    function imagecreatescaledfromfile($filename, $scalew, $scaleh)
    {
        list($width, $height, $type, $attr) = getimagesize($filename);

        wm_debug("Getting a (maybe cached) image for $filename at $scalew x $scaleh\n");

        // do the non-scaling version if no scaling is required
        if ($scalew == 0 && $scaleh == 0) {
            wm_debug("No scaling, punt to regular\n");
            return $this->imagecreatefromfile($filename);
        }

        if ($width == $scalew && $height == $scaleh) {
            wm_debug("No scaling, punt to regular\n");
            return $this->imagecreatefromfile($filename);
        }
        $key = sprintf("%s:%d:%d", $filename, $scalew, $scaleh);

        if (array_key_exists($key, $this->cache)) {
            wm_debug("Cache hit for $key\n");
            $icon_im = $this->cache[$key];
            wm_debug("From cache: $icon_im\n");
            $real_im = $this->imageduplicate($icon_im);
        } else {
            wm_debug("Cache miss - processing\n");
            $icon_im = $this->imagecreatefromfile($filename);

            $icon_w = imagesx($icon_im);
            $icon_h = imagesy($icon_im);

            wm_debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");

            wm_debug("SCALING ICON here\n");
            if ($icon_w > $icon_h) {
                $scalefactor = $icon_w / $scalew;
            } else {
                $scalefactor = $icon_h / $scaleh;
            }
            if ($scalefactor != 1.0) {
                $new_width = $icon_w / $scalefactor;
                $new_height = $icon_h / $scalefactor;

                $scaled = imagecreatetruecolor($new_width, $new_height);
                imagesavealpha($scaled, true);
                imagealphablending($scaled, false);
                imagecopyresampled($scaled, $icon_im, 0, 0, 0, 0, $new_width, $new_height, $icon_w,
                    $icon_h);
                imagedestroy($icon_im);
                $icon_im = $scaled;
            }
            if ($this->cacheable($scalew, $scaleh)) {
                wm_debug("Caching $key $icon_im\n");
                $this->cache[$key] = $icon_im;
                $real_im = $this->imageduplicate($icon_im);
            } else {
                $real_im = $icon_im;
            }
        }

        wm_debug("Returning $real_im\n");
        return $real_im;
    }

    function imageduplicate($source_im)
    {
        $source_width = imagesx($source_im);
        $source_height = imagesy($source_im);

        if (imageistruecolor($source_im)) {
            wm_debug("Duplicating $source_width x $source_height TC image\n");
            $new_im = imagecreatetruecolor($source_width, $source_height);
            imagealphablending($new_im, false);
            imagesavealpha($new_im, true);
        } else {
            wm_debug("Duplicating $source_width x $source_height palette image\n");
            $new_im = imagecreate($source_width, $source_height);
            $trans = imagecolortransparent($source_im);
            if ($trans >= 0) {
                wm_debug("Duplicating transparency in indexed image\n");
                $rgb = imagecolorsforindex($source_im, $trans);
                $trans_index = imagecolorallocatealpha($new_im, $rgb['red'], $rgb['green'], $rgb['blue'],
                    $rgb['alpha']);
                imagefill($new_im, 0, 0, $trans_index);
            }
        }

        imagecopy($new_im, $source_im, 0, 0, 0, 0, $source_width, $source_height);

        return $new_im;
    }

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
            if (!is_null($new_image) && $this->cacheable($width, $height)) {
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

}
