<?php
namespace Weathermap\Core;

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
