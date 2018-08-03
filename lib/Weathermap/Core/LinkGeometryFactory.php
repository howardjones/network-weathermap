<?php
namespace Weathermap\Core;

/**
 * Given the link style, return an appropriate LinkGeometry object to draw it
 *
 * @package Weathermap\Core
 */
class LinkGeometryFactory
{
    /**
     * @param $style
     * @return AngledLinkGeometry|CurvedLinkGeometry
     * @throws WeathermapInternalFail
     *
     * Create a geometry-generator for a link.
     *
     */
    public static function create($style)
    {
        if ($style=='angled') {
            return new AngledLinkGeometry();
        }
        if ($style=='curved') {
            return new CurvedLinkGeometry();
        }

        throw new WeathermapInternalFail('UnexpectedViaStyle');
    }
}
