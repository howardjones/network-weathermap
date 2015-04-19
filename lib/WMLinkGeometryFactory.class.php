<?php

class WMLinkGeometryFactory
{
    public function create($style)
    {
        if ($style=='angled') {
            return new WMAngledLinkGeometry();
        }
        if ($style=='curved') {
            return new WMCurvedLinkGeometry();
        }

        throw new WMException("UnexpectedViaStyle");
    }
}
