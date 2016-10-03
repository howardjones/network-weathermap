<?php

class WMLinkGeometryFactory
{
    /**
     * @param $style
     * @return WMAngledLinkGeometry|WMCurvedLinkGeometry
     * @throws WeathermapInternalFail
     *
     * Create a geometry-generator for a link.
     *
     */
    public static function create($style)
    {
        if ($style=='angled') {
            return new WMAngledLinkGeometry();
        }
        if ($style=='curved') {
            return new WMCurvedLinkGeometry();
        }

        throw new WeathermapInternalFail("UnexpectedViaStyle");
    }
}
