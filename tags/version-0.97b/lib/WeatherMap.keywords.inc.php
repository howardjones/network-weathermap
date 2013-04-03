
// new version of config_keywords
// array of contexts, contains an array of keywords, contains a (short) list of regexps as now
// this way, we don't scan the whole table, and we call preg_match a WHOLE lot less
// there will be more lines in the array, but we'll be checking less of them
$WM_config_keywords2 = array (
    'GLOBAL' => array (
        'FONTDEFINE' => array(
            array('GLOBAL',"/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s+(\d+)\s*$/i",'ReadConfig_Handle_FONTDEFINE'),
            array('GLOBAL',"/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s*$/i",'ReadConfig_Handle_FONTDEFINE'),
        ),
        'KEYOUTLINECOLOR' => array (
            array (
            'GLOBAL',
            '/^KEYOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
            ),
            array (
            'GLOBAL',
            '/^KEYOUTLINECOLOR\s+(none)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
            ),
            ),
        'KEYTEXTCOLOR' => array (array (
            'GLOBAL',
            '/^KEYTEXTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
        ),),
        'TITLECOLOR' => array (array (
            'GLOBAL',
            '/^TITLECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
        ),),
        'TIMECOLOR' => array (array (
            'GLOBAL',
            '/^TIMECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
        ),),
        'KEYBGCOLOR' => array (
            array (
            'GLOBAL',
            '/^KEYBGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
        ),
            array (
            'GLOBAL',
            '/^KEYBGCOLOR\s+(none)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
            ),
            ),
        'BGCOLOR' => array (array (
            'GLOBAL',
            '/^BGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
        ),),
        'SET' => array (array (
            'GLOBAL',
            'SET',
            'ReadConfig_Handle_SET'
        ),),
        'HTMLSTYLESHEET' => array (array (
            'GLOBAL',
            '/^HTMLSTYLESHEET\s+(.*)\s*$/i',
            array ('htmlstylesheet' => 1)
        ),),
        'HTMLOUTPUTFILE' => array (array (
            'GLOBAL',
            '/^HTMLOUTPUTFILE\s+(.*)\s*$/i',
            array ('htmloutputfile' => 1)
        ),),
        'BACKGROUND' => array (array (
            'GLOBAL',
            '/^BACKGROUND\s+(.*)\s*$/i',
            array ('background' => 1)
        ),),
        'IMAGEOUTPUTFILE' => array (array (
            'GLOBAL',
            '/^IMAGEOUTPUTFILE\s+(.*)\s*$/i',
            array ('imageoutputfile' => 1)
        ),),
        'DATAOUTPUTFILE' => array (array (
            'GLOBAL',
            '/^DATAOUTPUTFILE\s+(.*)\s*$/i',
            array ('dataoutputfile' => 1)
        ),),
        'IMAGEURI' => array (array (
            'GLOBAL',
            '/^IMAGEURI\s+(.*)\s*$/i',
            array ('imageuri' => 1)
        ),),
        'TITLE' => array (array (
            'GLOBAL',
            '/^TITLE\s+(.*)\s*$/i',
            array ('title' => 1)
        ),),
        'HTMLSTYLE' => array (array (
            'GLOBAL',
            '/^HTMLSTYLE\s+(static|overlib)\s*$/i',
            array ('htmlstyle' => 1)
        ),),
        'KILO' => array (array (
            'GLOBAL',
            '/^KILO\s+(\d+)\s*$/i',
            array ('kilo' => 1)
        ),),
        'KEYFONT' => array (array (
            'GLOBAL',
            '/^KEYFONT\s+(\d+)\s*$/i',
            array ('keyfont' => 1)
        ),),
        'TITLEFONT' => array (array (
            'GLOBAL',
            '/^TITLEFONT\s+(\d+)\s*$/i',
            array ('titlefont' => 1)
        ),),
        'TIMEFONT' => array (array (
            'GLOBAL',
            '/^TIMEFONT\s+(\d+)\s*$/i',
            array ('timefont' => 1)
        ),),
        'WIDTH' => array (array (
            'GLOBAL',
            "/^WIDTH\s+(\d+)\s*$/i",
            array ('width' => 1)
        ),),
        'HEIGHT' => array (array (
            '(GLOBAL)',
            "/^HEIGHT\s+(\d+)\s*$/i",
            array ('height' => 1)
        ),),
        'TITLEPOS' => array (
            array (
                'GLOBAL',
                '/^TITLEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                array (
                    'titlex' => 1,
                    'titley' => 2
                )
            ),
            array (
                'GLOBAL',
                '/^TITLEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',
                array (
                    'titlex' => 1,
                    'titley' => 2,
                    'title' => 3
                )
            ),
        ),
        'TIMEPOS' => array (
            array (
                'GLOBAL',
                '/^TIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                array (
                    'timex' => 1,
                    'timey' => 2
                )
            ),
            array (
                'GLOBAL',
                '/^TIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',
                array (
                    'timex' => 1,
                    'timey' => 2,
                    'stamptext' => 3
                )
            ),
        ),
        'MINTIMEPOS' => array (
            array (
                'GLOBAL',
                '/^MINTIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                array (
                    'mintimex' => 1,
                    'mintimey' => 2
                )
            ),
            array (
                'GLOBAL',
                '/^MINTIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',
                array (
                    'mintimex' => 1,
                    'mintimey' => 2,
                    'minstamptext' => 3
                )
            ),
        ),
        'MAXTIMEPOS' => array (
            array (
                'GLOBAL',
                '/^MAXTIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                array (
                    'maxtimex' => 1,
                    'maxtimey' => 2
                )
            ),
            array (
                'GLOBAL',
                '/^MAXTIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',
                array (
                    'maxtimex' => 1,
                    'maxtimey' => 2,
                    'maxstamptext' => 3
                )
            ),
        ),
    ), // end of global
    'NODE' => array (
        'TARGET' => array (array (
            'NODE',
            'TARGET',
            'ReadConfig_Handle_TARGET'
        ),),
        'SET' => array (array (
            'NODE',
            'SET',
            'ReadConfig_Handle_SET'
        ),),
        'AICONOUTLINECOLOR' => array (
            array (
                'NODE',
                '/^AICONOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'NODE',
                '/^AICONOUTLINECOLOR\s+(none)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'AICONFILLCOLOR' => array (
            array (
                'NODE',
                '/^AICONFILLCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'NODE',
                '/^AICONFILLCOLOR\s+(copy)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'LABELOUTLINECOLOR' => array (
            array (
                'NODE',
                '/^LABELOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'NODE',
                '/^LABELOUTLINECOLOR\s+(none)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'LABELBGCOLOR' => array (
            array (
                'NODE',
                '/^LABELBGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'NODE',
                '/^LABELBGCOLOR\s+(none)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'LABELFONTCOLOR' => array (
            array (
                'NODE',
                '/^LABELFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'NODE',
                '/^LABELFONTCOLOR\s+(contrast)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'LABELFONTSHADOWCOLOR' => array (array (
            'NODE',
            '/^LABELFONTSHADOWCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_COLOR'
        ),),
        'NOTES' => array (array (
            'NODE',
            '/^NOTES\s+(.*)\s*$/i',
            array (
                'notestext[IN]' => 1,
                'notestext[OUT]' => 1
            )
        ),),
        'MAXVALUE' => array (
            array (
                'NODE',
                '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                array (
                    'max_bandwidth_in_cfg' => 2,
                    'max_bandwidth_out_cfg' => 3
                )
            ),
            array (
                'NODE',
                '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                array (
                    'max_bandwidth_in_cfg' => 2,
                    'max_bandwidth_out_cfg' => 2
                )
            ),
        ),
        'ORIGIN' => array(
            array('NODE', 
                "/^ORIGIN\s+(C|NE|SE|NW|SW|N|S|E|W)/i",
                array("position_origin" => 1)                
            )
        ),
        'POSITION' => array (
            array (
                'NODE',
                "/^POSITION\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i",
                array (
                    'x' => 1,
                    'y' => 2
                )
            ),
            array (
                'NODE',
                "/^POSITION\s+(\S+)\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i",
                array (
                    'x' => 2,
                    'y' => 3,
                    'original_x' => 2,
                    'original_y' => 3,
                    'relative_to' => 1,
                    'relative_resolved' => false
                )
            ),
            array (
                'NODE',
                "/^POSITION\s+(\S+)\s+([-+]?\d+)r(\d+)\s*$/i",
                array (
                    'x' => 2,
                    'y' => 3,
                    'original_x' => 2,
                    'original_y' => 3,
                    'relative_to' => 1,
                    'polar' => true,
                    'relative_resolved' => false
                )
            ),
        ),
        'INFOURL' => array (array (
            'NODE',
            '/^INFOURL\s+(.*)\s*$/i',
            array (
                'infourl[IN]' => 1,
                'infourl[OUT]' => 1
            )
        ),),
        'OVERLIBCAPTION' => array (array (
            'NODE',
            '/^OVERLIBCAPTION\s+(.*)\s*$/i',
            array (
                'overlibcaption[IN]' => 1,
                'overlibcaption[OUT]' => 1
            )
        ),),
        'ZORDER' => array (array (
            'NODE',
            "/^ZORDER\s+([-+]?\d+)\s*$/i",
            array ('zorder' => 1)
        ),),
        'OVERLIBHEIGHT' => array (array (
            'NODE',
            "/^OVERLIBHEIGHT\s+(\d+)\s*$/i",
            array ('overlibheight' => 1)
        ),),
        'OVERLIBWIDTH' => array (array (
            'NODE',
            "/^OVERLIBWIDTH\s+(\d+)\s*$/i",
            array ('overlibwidth' => 1)
        ),),
        'LABELFONT' => array (array (
            'NODE',
            '/^LABELFONT\s+(\d+)\s*$/i',
            array ('labelfont' => 1)
        ),),
        'LABELANGLE' => array (array (
            'NODE',
            '/^LABELANGLE\s+(0|90|180|270)\s*$/i',
            array ('labelangle' => 1)
        ),),
        'ICON' => array (
            array (
                'NODE',
                '/^ICON\s+(\S+)\s*$/i',
                array (
                    'iconfile' => 1,
                    'iconscalew' => '#0',
                    'iconscaleh' => '#0'
                )
            ),
            array (
                'NODE',
                '/^ICON\s+(\S+)\s*$/i',
                array ('iconfile' => 1)
            ),
            array (
                'NODE',
                '/^ICON\s+(\d+)\s+(\d+)\s+(inpie|outpie|box|rbox|round|gauge|nink)\s*$/i',
                array (
                    'iconfile' => 3,
                    'iconscalew' => 1,
                    'iconscaleh' => 2
                )
            ),
            array (
                'NODE',
                '/^ICON\s+(\d+)\s+(\d+)\s+(\S+)\s*$/i',
                array (
                    'iconfile' => 3,
                    'iconscalew' => 1,
                    'iconscaleh' => 2
                )
            ),
        ),
        'LABEL' => array (
            array (
                'NODE',
                "/^LABEL\s*$/i",
                array ('label' => '')
            ), # special case for blank labels
            array (
                'NODE',
                "/^LABEL\s+(.*)\s*$/i",
                array ('label' => 1)
            ),
        ),
        'LABELOFFSET' => array (
            array (
                'NODE',
                '/^LABELOFFSET\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i',
                array (
                    'labeloffsetx' => 1,
                    'labeloffsety' => 2
                )
            ),
            array (
                'NODE',
                '/^LABELOFFSET\s+(C|NE|SE|NW|SW|N|S|E|W)\s*$/i',
                array ('labeloffset' => 1)
            ),
            array (
                'NODE',
                '/^LABELOFFSET\s+((C|NE|SE|NW|SW|N|S|E|W)\d+)\s*$/i',
                array ('labeloffset' => 1)
            ),
            array (
                'NODE',
                '/^LABELOFFSET\s+(-?\d+r\d+)\s*$/i',
                array ('labeloffset' => 1)
            ),
        ),
        'USESCALE' => array(
            array('NODE', "/^(USESCALE)\s+([A-Za-z][A-Za-z0-9_]*)(\s+(in|out))?(\s+(absolute|percent))?\s*$/i","ReadConfig_Handle_NODE_USESCALE"),
        ),
        'USEICONSCALE' => array(
            array('NODE', "/^(USEICONSCALE)\s+([A-Za-z][A-Za-z0-9_]*)(\s+(in|out))?(\s+(absolute|percent))?\s*$/i","ReadConfig_Handle_NODE_USESCALE"),
        ),
        'OVERLIBGRAPH' => array(
            array('NODE',"/^OVERLIBGRAPH\s+(.+)$/i","ReadConfig_Handle_OVERLIB")
        ),

    ), // end of node
    'LINK' => array (
        'TARGET' => array (array (
            'LINK',
            'TARGET',
            'ReadConfig_Handle_TARGET'
        ),),
        'SET' => array (array (
            'LINK',
            'SET',
            'ReadConfig_Handle_SET'
        ),),
        'NODES' => array (array (
            'LINK',
            'NODES',
            'ReadConfig_Handle_NODES'
        ),),
        'VIA' => array (array (
            'LINK',
            'VIA',
            'ReadConfig_Handle_VIA'
        ),),
        'COMMENTFONTCOLOR' => array (
            array (
                'LINK',
                '/^COMMENTFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'LINK',
                '/^COMMENTFONTCOLOR\s+(contrast)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'OUTLINECOLOR' => array (
            array (
                'LINK',
                '/^OUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'LINK',
                '/^OUTLINECOLOR\s+(none)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'BWOUTLINECOLOR' => array (
            array (
                'LINK',
                '/^BWOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'LINK',
                '/^BWOUTLINECOLOR\s+(none)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'BWBOXCOLOR' => array (
            array (
                'LINK',
                '/^BWBOXCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'ReadConfig_Handle_COLOR'
            ),
            array (
                'LINK',
                '/^BWBOXCOLOR\s+(none)$/',
                'ReadConfig_Handle_COLOR'
            ),
        ),
        'BWFONTCOLOR' => array (array (
            'LINK',
            '/^BWFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_COLOR'
        ),),
        'NOTES' => array (array (
            'LINK',
            '/^NOTES\s+(.*)\s*$/i',
            array (
                'notestext[IN]' => 1,
                'notestext[OUT]' => 1
            )
        ),),
        'MAXVALUE' => array (
            array (
                'LINK',
                '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                array (
                    'max_bandwidth_in_cfg' => 2,
                    'max_bandwidth_out_cfg' => 3
                )
            ),
            array (
                'LINK',
                '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                array (
                    'max_bandwidth_in_cfg' => 2,
                    'max_bandwidth_out_cfg' => 2
                )
            ),
        ),
        'WIDTH' => array (
            array (
                'LINK',
                "/^WIDTH\s+(\d+)\s*$/i",
                array ('width' => 1)
            ),
            array (
                'LINK',
                "/^WIDTH\s+(\d+\.\d+)\s*$/i",
                array ('width' => 1)
            ),
        ),
        'SPLITPOS' => array (array (
            'LINK',
            '/^SPLITPOS\s+(\d+)\s*$/i',
            array ('splitpos' => 1)
        ),),
        'BWLABELPOS' => array (array (
            'LINK',
            '/^BWLABELPOS\s+(\d+)\s(\d+)\s*$/i',
            array (
                'labeloffset_in' => 1,
                'labeloffset_out' => 2
            )
        ),),
        'COMMENTPOS' => array (array (
            'LINK',
            '/^COMMENTPOS\s+(\d+)\s(\d+)\s*$/i',
            array (
                'commentoffset_in' => 1,
                'commentoffset_out' => 2
            )
        ),),
        'DUPLEX' => array (array (
            'LINK',
            '/^DUPLEX\s+(full|half)\s*$/i',
            array ('duplex' => 1)
        ),),
        'BWSTYLE' => array (array (
            'LINK',
            '/^BWSTYLE\s+(classic|angled)\s*$/i',
            array ('labelboxstyle' => 1)
        ),),
        'LINKSTYLE' => array (array (
            'LINK',
            '/^LINKSTYLE\s+(twoway|oneway)\s*$/i',
            array ('linkstyle' => 1)
        ),),
        'COMMENTSTYLE' => array (array (
            'LINK',
            '/^COMMENTSTYLE\s+(edge|center)\s*$/i',
            array ('commentstyle' => 1)
        ),),
        'ARROWSTYLE' => array (array (
            'LINK',
            '/^ARROWSTYLE\s+(classic|compact)\s*$/i',
            array ('arrowstyle' => 1)
        ),),
        'VIASTYLE' => array (array (
            'LINK',
            '/^VIASTYLE\s+(curved|angled)\s*$/i',
            array ('viastyle' => 1)
        ),),
        'INCOMMENT' => array (array (
            'LINK',
            '/^INCOMMENT\s+(.*)\s*$/i',
            array ('comments[IN]' => 1)
        ),),
        'OUTCOMMENT' => array (array (
            'LINK',
            '/^OUTCOMMENT\s+(.*)\s*$/i',
            array ('comments[OUT]' => 1)
        ),),

        'OVERLIBGRAPH' => array(
            array('LINK',"/^OVERLIBGRAPH\s+(.+)$/i","ReadConfig_Handle_OVERLIB")
        ),
        'INOVERLIBGRAPH' => array(
            array('LINK',"/^INOVERLIBGRAPH\s+(.+)$/i","ReadConfig_Handle_OVERLIB")
        ),
        'OUTOVERLIBGRAPH' => array(
            array('LINK',"/^OUTOVERLIBGRAPH\s+(.+)$/i","ReadConfig_Handle_OVERLIB")
        ),

        'USESCALE' => array (
            array (
                'LINK',
                '/^USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s*$/i',
                array ('usescale' => 1)
            ),
            array (
                'LINK',
                '/^USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s+(absolute|percent)\s*$/i',
                array (
                    'usescale' => 1,
                    'scaletype' => 2
                )
            ),
        ),
        'BWFONT' => array (array (
            'LINK',
            '/^BWFONT\s+(\d+)\s*$/i',
            array ('bwfont' => 1)
        ),),
        'COMMENTFONT' => array (array (
            'LINK',
            '/^COMMENTFONT\s+(\d+)\s*$/i',
            array ('commentfont' => 1)
        ),),
        'BANDWIDTH' => array (
            array (
                'LINK',
                '/^(BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                array (
                    'max_bandwidth_in_cfg' => 2,
                    'max_bandwidth_out_cfg' => 3
                )
            ),
            array (
                'LINK',
                '/^(BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                array (
                    'max_bandwidth_in_cfg' => 2,
                    'max_bandwidth_out_cfg' => 2
                )
            ),
        ),
        'OUTBWFORMAT' => array (array (
            'LINK',
            '/^OUTBWFORMAT\s+(.*)\s*$/i',
            array (
                'bwlabelformats[OUT]' => 1,
                'labelstyle' => '--'
            )
        ),),
        'INBWFORMAT' => array (array (
            'LINK',
            '/^INBWFORMAT\s+(.*)\s*$/i',
            array (
                'bwlabelformats[IN]' => 1,
                'labelstyle' => '--'
            )
        ),),
        'INNOTES' => array (array (
            'LINK',
            '/^INNOTES\s+(.*)\s*$/i',
            array ('notestext[IN]' => 1)
        ),),
        'OUTNOTES' => array (array (
            'LINK',
            '/^OUTNOTES\s+(.*)\s*$/i',
            array ('notestext[OUT]' => 1)
        ),),
        'INFOURL' => array (array (
            'LINK',
            '/^INFOURL\s+(.*)\s*$/i',
            array (
                'infourl[IN]' => 1,
                'infourl[OUT]' => 1
            )
        ),),
        'ININFOURL' => array (array (
            'LINK',
            '/^ININFOURL\s+(.*)\s*$/i',
            array ('infourl[IN]' => 1)
        ),),
        'OUTINFOURL' => array (array (
            'LINK',
            '/^OUTINFOURL\s+(.*)\s*$/i',
            array ('infourl[OUT]' => 1)
        ),),
        'OVERLIBCAPTION' => array (array (
            'LINK',
            '/^OVERLIBCAPTION\s+(.*)\s*$/i',
            array (
                'overlibcaption[IN]' => 1,
                'overlibcaption[OUT]' => 1
            )
        ),),
        'INOVERLIBCAPTION' => array (array (
            'LINK',
            '/^INOVERLIBCAPTION\s+(.*)\s*$/i',
            array ('overlibcaption[IN]' => 1)
        ),),
        'OUTOVERLIBCAPTION' => array (array (
            'LINK',
            '/^OUTOVERLIBCAPTION\s+(.*)\s*$/i',
            array ('overlibcaption[OUT]' => 1)
        ),),
        'ZORDER' => array (array (
            'LINK',
            "/^ZORDER\s+([-+]?\d+)\s*$/i",
            array ('zorder' => 1)
        ),),
        'OVERLIBWIDTH' => array (array (
            'LINK',
            "/^OVERLIBWIDTH\s+(\d+)\s*$/i",
            array ('overlibwidth' => 1)
        ),),
        'OVERLIBHEIGHT' => array (array (
            'LINK',
            "/^OVERLIBHEIGHT\s+(\d+)\s*$/i",
            array ('overlibheight' => 1)
        ),),
    ) // end of link
);

