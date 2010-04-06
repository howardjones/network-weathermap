<?php
/** PHP Weathermap 0.97a
 * Copyright Howard Jones, 2005-2010 howie@thingy.com
 * http://www.network-weathermap.com/
 * Released under the GNU Public License
 *
 */

require_once 'HTML_ImageMap.class.php';

require_once 'WeatherMap.functions.php';
require_once 'WeatherMapNode.class.php';
require_once 'WeatherMapLink.class.php';

$WEATHERMAP_VERSION = '0.98dev';
$weathermap_debugging = false;
$weathermap_map = '';
$weathermap_warncount = 0;
$weathermap_debug_suppress = array (
    'processstring',
    'mysprintf'
);

$weathemap_lazycounter = 0;

// Turn on ALL error reporting for now.
# error_reporting (E_ALL|E_STRICT);

// parameterise the in/out stuff a bit
define('IN', 0);
define('OUT', 1);
define('WMCHANNELS', 2);

define('CONFIG_TYPE_LITERAL', 0);
define('CONFIG_TYPE_COLOR', 1);

// some strings that are used in more than one place
define('FMT_BITS_IN', '{link:this:bandwidth_in:%2k}');
define('FMT_BITS_OUT', '{link:this:bandwidth_out:%2k}');
define('FMT_UNFORM_IN', '{link:this:bandwidth_in}');
define('FMT_UNFORM_OUT', '{link:this:bandwidth_out}');
define('FMT_PERC_IN', '{link:this:inpercent:%.2f}%');
define('FMT_PERC_OUT', '{link:this:outpercent:%.2f}%');

// the fields within a spine triple
define('X', 0);
define('Y', 1);
define('DISTANCE', 2);


// most of the config keywords just copy stuff into object properties.
// these are all dealt with from this one array. The special-cases
// follow on from that

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
        'KEYOUTLINECOLOR' => array (array (
            'GLOBAL',
            '/^KEYOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
        ),),
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
        'KEYBGCOLOR' => array (array (
            'GLOBAL',
            '/^KEYBGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
            'ReadConfig_Handle_GLOBALCOLOR'
        ),),
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


// template class for data sources. All data sources extend this class.
// I really wish PHP4 would just die overnight
class WeatherMapDataSource
{
    // Initialize - called after config has been read (so SETs are processed)
    // but just before ReadData. Used to allow plugins to verify their dependencies
    // (if any) and bow out gracefully. Return FALSE to signal that the plugin is not
    // in a fit state to run at the moment.
    function Init(&$map)
    {
        return true;
    }

// called with the TARGET string. Returns TRUE or FALSE, depending on whether it wants to handle this TARGET
// called by map->ReadData()
    function Recognise($targetstring)
    {
        return false;
    }

// the actual ReadData
//   returns an array of two values (in,out). -1,-1 if it couldn't get valid data
//   configline is passed in, to allow for better error messages
//   itemtype and itemname may be used as part of the target (e.g. for TSV source line)
// function ReadData($targetstring, $configline, $itemtype, $itemname, $map) { return (array(-1,-1)); }
    function ReadData($targetstring, &$map, &$item)
    {
        return (array (
            -1,
            -1
        ));
    }

// pre-register a target + context, to allow a plugin to batch up queries to a slow database, or snmp for example
    function Register($targetstring, &$map, &$item) { }

// called before ReadData, to allow plugins to DO the prefetch of targets known from Register
    function Prefetch() { }
}

// template classes for the pre- and post-processor plugins
class WeatherMapPreProcessor
{
    function run(&$map)
    {
        return false;
    }
}

class WeatherMapPostProcessor
{
    function run(&$map)
    {
        return false;
    }
}

/**
 * Collect together everything scale-related
 */
class WeatherMapScale
{
    var $keystyle;

    function WeatherMapScale()
    {
        $keystyle = 'classic';
    }

    function PopulateDefaults()
    {

    }

    function DrawLegend()
    {
        switch($this->keystyle)
        {
            case 'classic':
                $this->DrawLegendClassic($image, $scalename, false);
                break;
            case 'horizontal':
                $this->DrawLegendHorizontal($image, $scalename, $this->keysize[$scalename]);
                break;
            case 'vertical':
                $this->DrawLegendVertical($image, $scalename,
                        $this->keysize[$scalename]);
                break;
            case 'inverted':
                $this->DrawLegendVertical($image, $scalename,
                    $this->keysize[$scalename], true);
                break;
            case 'tags':
                $this->DrawLegendClassic($image, $scalename, true);
                break;
        }
    }

    function AddSpan($lowvalue, $highvalue, $colour, $tag='')
    {

    }

    function WriteConfig()
    {
        
    }

    function ColourFromValue()
    {
        
    }

    function DrawLegendClassic()
    {

    }

    function DrawLegendVertical()
    {

    }

    function DrawLegendHorizontal()
    {

    }
}

// Links, Nodes and the Map object inherit from this class ultimately.
// Just to make some common code common.
class WeatherMapBase
{
    var $notes = array ();
    var $hints = array ();
    var $inherit_fieldlist;
    var $imap_areas = array ();

    function add_note($name, $value)
    {
        debug("Adding note %s='%s' to %s\n", $name, $value, $this->name);
        $this->notes[$name] = $value;
    }

    function get_note($name)
    {
        if (true === isset($this->notes[$name])) {

            return ($this->notes[$name]);
        } else {

            return (null);
        }
    }

    function add_hint($name, $value)
    {
        debug("Adding hint %s='%s' to %s\n", $name, $value, $this->name);
        $this->hints[$name] = $value;
    }

    function get_hint($name)
    {
        if (true === isset($this->hints[$name])) {

            return ($this->hints[$name]);
        } else {

            return (null);
        }
    }
}

class WeatherMapConfigItem
{
    var $defined_in;
    var $name;
    var $value;
    var $type;
}

// The 'things on the map' class. More common code (mainly variables, actually)
class WeatherMapItem extends WeatherMapBase
{
    var $owner;

    var $configline;
    var $infourl;
    var $overliburl;
    var $overlibwidth, $overlibheight;
    var $overlibcaption;
    var $my_default;
    var $defined_in;
    var $config_override; # used by the editor to allow text-editing

    function my_type()
    {
        return 'ITEM';
    }
}

class WeatherMap extends WeatherMapBase
{
    var $nodes = array (); // an array of WeatherMapNodes
    var $links = array (); // an array of WeatherMapLinks
    var $texts = array (); // an array containing all the extraneous text bits
    var $used_images =
        array (); // an array of image filenames referred to (used by editor)
    var $seen_zlayers = array (
        0 => array (),
        1000 => array ()
    ); // 0 is the background, 1000 is the legends, title, etc

    var $config;
    var $next_id;
    var $min_ds_time;
    var $max_ds_time;
    var $background;
    var $htmlstyle;
    var $imap;
    var $colours;
    var $configfile;
    var $imagefile, $imageuri;
    var $rrdtool;
    var $title, $titlefont;
    var $kilo;
    var $sizedebug, $widthmod, $debugging;
    var $linkfont, $nodefont, $keyfont, $timefont;

    var $timex, $timey;
    var $width, $height;
    var $keyx, $keyy, $keyimage;
    var $titlex, $titley;
    var $keytext, $stamptext, $datestamp;
    var $min_data_time, $max_data_time;
    var $htmloutputfile, $imageoutputfile;
    var $htmlstylesheet;
    var $defaultlink, $defaultnode;
    var $need_size_precalc;
    var $keystyle, $keysize;
    var $rrdtool_check;
    var $inherit_fieldlist;
    var $mintimex, $maxtimex;
    var $mintimey, $maxtimey;
    var $minstamptext, $maxstamptext;
    var $context;
    var $cachefolder, $mapcache, $cachefile_version;
    var $name;
    var $black, $white, $grey, $selected;

    var $datasourceclasses;
    var $preprocessclasses;
    var $postprocessclasses;
    var $activedatasourceclasses;
    var $thumb_width, $thumb_height;
    var $has_includes;
    var $has_overlibs;
    var $node_template_tree;
    var $link_template_tree;

    var $plugins = array ();
    var $included_files = array ();
    var $usage_stats = array ();
	var $coverage = array();
	
    function WeatherMap()
    {
        $this->inherit_fieldlist = array (
            'width' => 800,
            'height' => 600,
            'kilo' => 1000,
            'numscales' => array ('DEFAULT' => 0),
            'datasourceclasses' => array (),
            'preprocessclasses' => array (),
            'postprocessclasses' => array (),
            'included_files' => array (),
            'context' => '',
            'dumpconfig' => false,
            'rrdtool_check' => '',
            'background' => '',
            'imageoutputfile' => '',
            'imageuri' => '',
            'htmloutputfile' => '',
            'htmlstylesheet' => '',
            'labelstyle' => 'percent', // redundant?
            'htmlstyle' => 'static',
            'keystyle' => array ('DEFAULT' => 'classic'),
            'title' => 'Network Weathermap',
            'keytext' => array ('DEFAULT' => 'Traffic Load'),
            'keyx' => array ('DEFAULT' => -1),
            'keyy' => array ('DEFAULT' => -1),
            'keyimage' => array (),
            'keysize' => array ('DEFAULT' => 400),
            'stamptext' => 'Created: %b %d %Y %H:%M:%S',
            'keyfont' => 4,
            'titlefont' => 2,
            'timefont' => 2,
            'timex' => 0,
            'timey' => 0,
            'mintimex' => -10000,
            'mintimey' => -10000,
            'maxtimex' => -10000,
            'maxtimey' => -10000,
            'minstamptext' => 'Oldest Data: %b %d %Y %H:%M:%S',
            'maxstamptext' => 'Newest Data: %b %d %Y %H:%M:%S',
            'thumb_width' => 0,
            'thumb_height' => 0,
            'titlex' => -1,
            'titley' => -1,
            'cachefolder' => 'cached',
            'mapcache' => '',
            'sizedebug' => false,
            'debugging' => false,
            'widthmod' => false,
            'has_includes' => false,
            'has_overlibs' => false,
            'name' => 'MAP'
        );

        $this->Reset();
    }

    function my_type()
    {
        return 'MAP';
    }

    function Reset()
    {
        $this->next_id = 100;

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            $this->$fld = $this->inherit_fieldlist[$fld];
        }

        $this->min_ds_time = null;
        $this->max_ds_time = null;

        $this->need_size_precalc = false;

        $this->nodes = array (); // an array of WeatherMapNodes
        $this->links = array (); // an array of WeatherMapLinks

        // these are the default defaults
        // by putting them into a normal object, we can use the
        // same code for writing out LINK DEFAULT as any other link.
        debug("Creating ':: DEFAULT ::' DEFAULT LINK\n");
        // these two are used for default settings
        $deflink = new WeatherMapLink;
        $deflink->name = ':: DEFAULT ::';
        $deflink->template = ':: DEFAULT ::';
        $deflink->Reset($this);

        $this->links[':: DEFAULT ::'] = &$deflink;

        debug("Creating ':: DEFAULT ::' DEFAULT NODE\n");
        $defnode = new WeatherMapNode;
        $defnode->name = ':: DEFAULT ::';
        $defnode->template = ':: DEFAULT ::';
        $defnode->Reset($this);

        $this->nodes[':: DEFAULT ::'] = &$defnode;

        $this->node_template_tree = array ();
        $this->link_template_tree = array ();

        $this->node_template_tree['DEFAULT'] = array ();
        $this->link_template_tree['DEFAULT'] = array ();


// ************************************
// now create the DEFAULT link and node, based on those.
// these can be modified by the user, but their template (and therefore comparison in WriteConfig) is ':: DEFAULT ::'
        debug("Creating actual DEFAULT NODE from :: DEFAULT ::\n");
        $defnode2 = new WeatherMapNode;
        $defnode2->name = 'DEFAULT';
        $defnode2->template = ':: DEFAULT ::';
        $defnode2->Reset($this);

        $this->nodes['DEFAULT'] = &$defnode2;

        debug("Creating actual DEFAULT LINK from :: DEFAULT ::\n");
        $deflink2 = new WeatherMapLink;
        $deflink2->name = 'DEFAULT';
        $deflink2->template = ':: DEFAULT ::';
        $deflink2->Reset($this);

        $this->links['DEFAULT'] = &$deflink2;

        assert('is_object($this->nodes[":: DEFAULT ::"])');
        assert('is_object($this->links[":: DEFAULT ::"])');
        assert('is_object($this->nodes["DEFAULT"])');
        assert('is_object($this->links["DEFAULT"])');

        $this->imap = new HTML_ImageMap('weathermap');
        $this->colours = array ();

        debug("Adding default map colour set.\n");
        $defaults = array (
            'KEYTEXT' => array (
                'bottom' => -2,
                'top' => -1,
                'red1' => 0,
                'green1' => 0,
                'blue1' => 0,
                'special' => 1
            ),
            'KEYOUTLINE' => array (
                'bottom' => -2,
                'top' => -1,
                'red1' => 0,
                'green1' => 0,
                'blue1' => 0,
                'special' => 1
            ),
            'KEYBG' => array (
                'bottom' => -2,
                'top' => -1,
                'red1' => 255,
                'green1' => 255,
                'blue1' => 255,
                'special' => 1
            ),
            'BG' => array (
                'bottom' => -2,
                'top' => -1,
                'red1' => 255,
                'green1' => 255,
                'blue1' => 255,
                'special' => 1
            ),
            'TITLE' => array (
                'bottom' => -2,
                'top' => -1,
                'red1' => 0,
                'green1' => 0,
                'blue1' => 0,
                'special' => 1
            ),
            'TIME' => array (
                'bottom' => -2,
                'top' => -1,
                'red1' => 0,
                'green1' => 0,
                'blue1' => 0,
                'special' => 1
            )
        );

        foreach ($defaults as $key => $def) {
            $this->colours['DEFAULT'][$key] = $def;
        }

        $this->configfile = '';
        $this->imagefile = '';
        $this->imageuri = '';

        $this->fonts = array ();

        // Adding these makes the editor's job a little easier, mainly
        for ($i = 1; $i <= 5; $i++) {
            $this->fonts[$i]->type = 'GD builtin';
            $this->fonts[$i]->file = '';
            $this->fonts[$i]->size = 0;
        }

        $this->LoadPlugins('data', 'lib' . DIRECTORY_SEPARATOR . 'datasources');
        $this->LoadPlugins('pre', 'lib' . DIRECTORY_SEPARATOR . 'pre');
        $this->LoadPlugins('post', 'lib' . DIRECTORY_SEPARATOR . 'post');

        debug("WeatherMap class Reset() complete\n");
    }

    function myimagestring($image, $fontnumber, $x, $y, $string, $colour, $angle = 0)
    {
// if it's supposed to be a special font, and it hasn't been defined, then fall through
        if ($fontnumber > 5 && (false === isset($this->fonts[$fontnumber]))) {
            warn(sprintf(
                "Using a non-existent special font (%d) - falling back to internal GD fonts [WMWARN03]\n",
                $fontnumber));

            if ($angle !== 0) {
                warn("Angled text doesn't work with non-FreeType fonts [WMWARN02]\n");
            }

            $fontnumber = 5;
        }

        if (($fontnumber > 0) && ($fontnumber < 6)) {
            imagestring($image, $fontnumber, $x, $y - imagefontheight($fontnumber),
                $string, $colour);

            if ($angle !== 0) {
                warn("Angled text doesn't work with non-FreeType fonts [WMWARN02]\n");
            }
        } else {
            // look up what font is defined for this slot number
            if ($this->fonts[$fontnumber]->type === 'truetype') {
                imagettftext($image, $this->fonts[$fontnumber]->size, $angle, $x, $y,
                    $colour, $this->fonts[$fontnumber]->file, $string);
            }

            if ($this->fonts[$fontnumber]->type === 'gd') {
                imagestring($image, $this->fonts[$fontnumber]->gdnumber, $x,
                    $y - imagefontheight($this->fonts[$fontnumber]->gdnumber), $string,
                    $colour);

                if ($angle !== 0) {
                    warn("Angled text doesn't work with non-FreeType fonts [WMWARN04]\n");
                }
            }
        }
    }

    function myimagestringsize($fontnumber, $string)
    {
        $linecount = 1;

        $lines = explode("\n", $string);
        $linecount = count($lines);
        $maxlinelength = 0;

        foreach ($lines as $line) {
            $l = strlen($line);

            if ($l > $maxlinelength) {
                $maxlinelength = $l;
            }
        }

        if (($fontnumber > 0) && ($fontnumber < 6)) {
            return array (
                imagefontwidth($fontnumber) * $maxlinelength,
                $linecount * imagefontheight($fontnumber)
            );
        } else {
            // look up what font is defined for this slot number
            if (false === isset($this->fonts[$fontnumber])) {
                warn(sprintf(
                    "Using a non-existent special font (%d) - falling back to internal GD fonts [WMWARN36]\n",
                    $fontnumber));
                $fontnumber = 5;
                return array (
                    imagefontwidth($fontnumber) * $maxlinelength,
                    $linecount * imagefontheight($fontnumber)
                );
            } else {
                if ($this->fonts[$fontnumber]->type === 'truetype') {
                    $ysize = 0;
                    $xsize = 0;

                    foreach ($lines as $line) {
                        $bounds = imagettfbbox($this->fonts[$fontnumber]->size, 0,
                            $this->fonts[$fontnumber]->file, $line);
                        $cx = $bounds[4] - $bounds[0];
                        $cy = $bounds[1] - $bounds[5];

                        if ($cx > $xsize) {
                            $xsize = $cx;
                        }
                        $ysize += ($cy * 1.2);
                    }

                    return (array (
                        $xsize,
                        $ysize
                    ));
                }

                if ($this->fonts[$fontnumber]->type === 'gd') {
                    return array (
                        imagefontwidth($this->fonts[$fontnumber]->gdnumber)
                            * $maxlinelength,
                        $linecount * imagefontheight($this->fonts[$fontnumber]->gdnumber)
                    );
                }
            }
        }
    }

    function ProcessString($input, &$context, $include_notes = true, $multiline = false)
    {
        //        assert('is_scalar($input)');

        if ($input === '') {
            return '';
        }

        if (1 == 0 && $this->context == 'cacti') {
            $fd = fopen("/var/www/docs/cacti/plugins/weathermap/processstring.log", "a+");
            fwrite($fd, $input . "\n");
            fclose($fd);
        }

        if ($multiline === true) {
            $i = $input;
            $input = str_replace("\\n", "\n", $i);
        }

        // don't bother with all this regexp rubbish if there's nothing to match
        if (false === strpos($input, "{")) {
            return $input;
        }

        $context_description = strtolower($context->my_type());

        // next, shortcut all the regexps for very common tokens
        if ($context_description === 'node') {
            $input = str_replace("{node:this:graph_id}", $context->get_hint("graph_id"),
                $input);
            $input = str_replace("{node:this:name}", $context->name, $input);
        }

        if ($context_description === 'link') {
            $input = str_replace("{link:this:graph_id}", $context->get_hint("graph_id"),
                $input);
        }

        // don't bother with all this regexp rubbish if there's nothing to match
        if (false === strpos($input, "{")) {
            return $input;
        }

        if ($context_description !== 'map') {
            $context_description .= ':' . $context->name;
        }


        //        debug("Trace: ProcessString($input, $context_description)\n");

        $output = $input;

        while (preg_match('/(\{(?:node|map|link)[^}]+\})/', $input, $matches)) {
            $value = '[UNKNOWN]';
            $format = '';
            $key = $matches[1];
            //          debug("ProcessString: working on %s\n", $key);

            if (preg_match('/\{(node|map|link):([^}]+)\}/', $key, $matches)) {
                $type = $matches[1];
                $args = $matches[2];

                if ($type === 'map') {
                    $the_item = $this;

                    if (preg_match('/map:([^:]+):*([^:]*)/', $args, $matches)) {
                        $args = $matches[1];
                        $format = $matches[2];
                    }
                }

                if (($type === 'link') || ($type === 'node')) {
                    if (preg_match('/([^:]+):([^:]+):*([^:]*)/', $args, $matches)) {
                        $itemname = $matches[1];
                        $args = $matches[2];
                        $format = $matches[3];

                        $the_item = null;

                        if (($itemname === 'this')
                            && ($type === strtolower($context->my_type()))) {
                            $the_item = $context;
                        } elseif (strtolower($context->my_type()) === 'link'
                            && $type === 'node'
                            && ($itemname === '_linkstart_' || $itemname === '_linkend_'))
                            {
                            // this refers to the two nodes at either end of this link
                            if ($itemname === '_linkstart_') {
                                $the_item = $context->a;
                            }

                            if ($itemname === '_linkend_') {
                                $the_item = $context->b;
                            }
                        } elseif (($itemname === 'parent')
                            && ($type == strtolower($context->my_type()))
                                && ($type == 'node') && ($context->relative_to != '')) {
                            $the_item = $this->nodes[$context->relative_to];
                        } else {
                            if (($type === 'link')
                                && (true === isset($this->links[$itemname]))) {
                                $the_item = $this->links[$itemname];
                            }

                            if (($type === 'node')
                                && (true === isset($this->nodes[$itemname]))) {
                                $the_item = $this->nodes[$itemname];
                            }
                        }
                    }
                }

                if (true === is_null($the_item)) {
                    warn(
                        "ProcessString: $key refers to unknown item (context is $context_description) [WMWARN05]\n");
                } else {
//                debug("ProcessString: Found appropriate item: %s %s\n",
//                        get_class($the_item), $the_item->name);

// SET and notes have precedent over internal properties
// this is my laziness - it saves me having a list of reserved words
// which are currently used for internal props. You can just 'overwrite' any of them.
                    if (true === isset($the_item->hints[$args])) {
                        $value = $the_item->hints[$args];
                    //              debug("ProcessString: used hint\n");
                    }
// for some things, we don't want to allow notes to be considered.
// mainly - TARGET (which can define command-lines), shouldn't be
// able to get data from uncontrolled sources (i.e. data sources rather than SET in config files).
                    elseif ($include_notes && isset($the_item->notes[$args])) {
                        $value = $the_item->notes[$args];
                    //                  debug("ProcessString: used note\n");
                    } elseif (isset($the_item->$args)) {
                        $value = $the_item->$args;
                    //                debug("ProcessString: used internal property\n");
                    }
                }
            }

            // format, and sanitise the value string here, before returning it

            if ($value === null) {
                $value = 'NULL';
            }
            debug("ProcessString: replacing %s with %s\n", $key, $value);

            if ($format !== '') {

                $value = mysprintf($format, $value);
            }

            $input = str_replace($key, '', $input);
            $output = str_replace($key, $value, $output);
        }

        return ($output);
    }

    function RandomData()
    {
        foreach ($this->links as $link) {
            $this->links[$link->name]->bandwidth_in = rand(0, $link->max_bandwidth_in);
            $this->links[$link->name]->bandwidth_out = rand(0, $link->max_bandwidth_out);
        }
    }

    function LoadPlugins($type = 'data', $dir = 'lib/datasources')
    {
        debug("Beginning to load %s plugins from %s\n", $type, $dir);

        if (false === file_exists($dir)) {
            $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . $dir;
            debug("Relative path didn't exist. Trying %s\n", $dir);
        }

        $dh = @opendir($dir);

        if (false === $dh) { // try to find it with the script, if the relative path fails
            $srcdir = substr($_SERVER['argv'][0], 0,
                strrpos($_SERVER['argv'][0], DIRECTORY_SEPARATOR));
            $dh = opendir($srcdir . DIRECTORY_SEPARATOR . $dir);

            if (false !== $dh) {
                $dir = $srcdir . DIRECTORY_SEPARATOR . $dir;
            }
        }

        if (false !== $dh) {
            while ($file = readdir($dh)) {
                $realfile = $dir . DIRECTORY_SEPARATOR . $file;

                if (is_file($realfile) && preg_match('/\.php$/', $realfile)) {
                    debug("Loading $type Plugin class from $file\n");

                    include_once $realfile;
                    $class = preg_replace('/\.php$/', '', $file);

                    if ($type === 'data') {
                        $this->datasourceclasses[$class] = $class;
                        $this->activedatasourceclasses[$class] = 1;
                    }

                    if ($type === 'pre') {
                        $this->preprocessclasses[$class] = $class;
                    }

                    if ($type === 'post') {
                        $this->postprocessclasses[$class] = $class;
                    }

                    debug("Loaded %s Plugin class %s from %s\n", $type, $class, $file);
                    $this->plugins[$type][$class] = new $class;

                    if (false === isset($this->plugins[$type][$class])) {
                        debug("** Failed to create an object for plugin %s/%s\n", $type,
                            $class);
                    } else {
                        debug("Instantiated %s.\n", $class);
                    }
                } else {
                    debug("Skipping %s\n", $file);
                }
            }
        } else {
            warn(
                "Couldn't open %s Plugin directory (%s). Things will probably go wrong. [WMWARN06]\n",
                $type, $dir);
        }
    }

    function DatasourceInit()
    {
        debug("Running Init() for Data Source Plugins...\n");

        foreach ($this->datasourceclasses as $ds_class) {
            // make an instance of the class
            $dsplugins[$ds_class] = new $ds_class;
            debug("Running %s->Init()\n", $ds_class);

            assert('isset($this->plugins["data"][$ds_class])');

            $ret = $this->plugins['data'][$ds_class]->Init($this);

            if (false === $ret) {
                debug("Removing %s from Data Source list, since Init() failed\n",
                    $ds_class);
                $this->activedatasourceclasses[$ds_class] = 0;
            }
        }
        debug("Finished Initialising Plugins...\n");
    }

    function ProcessTargets()
    {
        debug("Preprocessing targets\n");

        $allitems = array (
            &$this->links,
            &$this->nodes
        );

        reset($allitems);

        debug("Preprocessing targets\n");

        while (list($kk, ) = each($allitems)) {
            unset($objects);
            $objects = &$allitems[$kk];

            reset($objects);

            while (list($k, ) = each($objects)) {
                unset($myobj);
                $myobj = &$objects[$k];

                $type = $myobj->my_type();
                $name = $myobj->name;

                if (($type == 'LINK' && isset($myobj->a))
                    || ($type == 'NODE' && !is_null($myobj->x))) {
                    if (count($myobj->targets) > 0) {
                        $tindex = 0;

                        foreach ($myobj->targets as $target) {
                            debug("ProcessTargets: New Target: $target[4]\n");
                            // processstring won't use notes (only hints) for this string

                            $targetstring =
                                $this->ProcessString($target[4], $myobj, false, false);

                            if ($target[4] != $targetstring)
                                debug("Targetstring is now $targetstring\n");

// if the targetstring starts with a -, then we're taking this value OFF the aggregate
                            $multiply = 1;

                            if (preg_match("/^-(.*)/", $targetstring, $matches)) {
                                $targetstring = $matches[1];
                                $multiply = -1 * $multiply;
                            }

// if the remaining targetstring starts with a number and a *-, then this is a scale factor
                            if (preg_match("/^(\d+\.?\d*)\*(.*)/", $targetstring,
                                $matches)) {
                                $targetstring = $matches[2];
                                $multiply = $multiply * floatval($matches[1]);
                            }

                            $matched = false;
                            $matched_by = '';

                            foreach ($this->datasourceclasses as $ds_class) {
                                if (!$matched) {

                                    $recognised =
                                        $this->plugins['data'][$ds_class]->Recognise(
                                            $targetstring);

                                    if ($recognised) {
                                        $matched = true;
                                        $matched_by = $ds_class;

                                        if ($this->activedatasourceclasses[$ds_class]) {
                                            $this->plugins['data'][$ds_class]->Register(
                                                $targetstring, $this, $myobj);

                                            if ($type == 'NODE') {
                                                $this->nodes[$name]->targets[$tindex][1] =
                                                    $multiply;
                                                $this->nodes[$name]->targets[$tindex][0] =
                                                    $targetstring;
                                                $this->nodes[$name]->targets[$tindex][5] =
                                                    $matched_by;
                                            }

                                            if ($type == 'LINK') {
                                                $this->links[$name]->targets[$tindex][1] =
                                                    $multiply;
                                                $this->links[$name]->targets[$tindex][0] =
                                                    $targetstring;
                                                $this->links[$name]->targets[$tindex][5] =
                                                    $matched_by;
                                            }
                                        } else {
                                            warn(
                                                "ProcessTargets: $type $name, target: $targetstring on config line $target[3] of $target[2] was recognised as a valid TARGET by a plugin that is unable to run ($ds_class) [WMWARN07]\n");
                                        }
                                    }
                                }
                            }

                            if (!$matched) {
                                warn(
                                    "ProcessTargets: $type $name, target: $target[4] on config line $target[3] of $target[2] was not recognised as a valid TARGET [WMWARN08]\n");
                            }

                            $tindex++;
                        }
                    }
                }
            }
        }
    }

    function ReadData()
    {
        $this->DatasourceInit();

        debug("======================================\n");
        debug("ReadData: Updating link data for all links and nodes\n");

        // we skip readdata completely in sizedebug mode
        if ($this->sizedebug === false) {
            $this->ProcessTargets();

            debug("======================================\n");
            debug("Starting prefetch\n");

            foreach ($this->datasourceclasses as $ds_class) {
                $this->plugins['data'][$ds_class]->Prefetch();
            }

            debug("======================================\n");
            debug("Starting main collection loop\n");

            $allitems = array (
                &$this->links,
                &$this->nodes
            );

            reset($allitems);

            while (list($kk, ) = each($allitems)) {
                unset($objects);
                $objects = &$allitems[$kk];

                reset($objects);

                while (list($k, ) = each($objects)) {
                    unset($myobj);
                    $myobj = &$objects[$k];

                    $type = $myobj->my_type();

                    $total_in = 0;
                    $total_out = 0;
                    $name = $myobj->name;
                    debug("\n");
                    debug("ReadData for %s %s: \n", $type, $name);

                    if (($type == 'LINK' && isset($myobj->a))
                        || ($type == 'NODE' && !is_null($myobj->x))) {
                        if (count($myobj->targets) > 0) {
                            $tindex = 0;

                            foreach ($myobj->targets as $target) {
                                debug("ReadData: New Target: %s\n", $target[4]);

                                $targetstring = $target[0];
                                $multiply = $target[1];

                                $in = 0;
                                $out = 0;
                                $datatime = 0;

                                if ($target[4] !== '') {
// processstring won't use notes (only hints) for this string

                                    $targetstring =
                                        $this->ProcessString($target[0], $myobj, false,
                                            false);

                                    if ($target[0] !== $targetstring) {
                                        debug("Targetstring is now %s\n", $targetstring);
                                    }

                                    if ($multiply !== 1) {
                                        debug("Will multiply result by %f\n", $multiply);
                                    }

                                    if ($target[0] !== '') {
                                        $matched_by = $target[5];
                                        list($in, $out, $datatime) =
                                            $this->plugins['data'][$target[5]]->ReadData(
                                                $targetstring, $this, $myobj);
                                    }

                                    if (($in === null) && ($out === null)) {
                                        $in = 0;
                                        $out = 0;
                                        warn(
                                            "ReadData: $type $name, target: $targetstring on config line $target[3] of $target[2] had no valid data, according to $matched_by\n");
                                    } else {
                                        if ($in === null) {
                                            $in = 0;
                                        }

                                        if ($out === null) {
                                            $out = 0;
                                        }
                                    }

                                    if ($multiply != 1) {
                                        debug("Pre-multiply: $in $out\n");

                                        $in = $multiply * $in;
                                        $out = $multiply * $out;

                                        debug("Post-multiply: $in $out\n");
                                    }

                                    $total_in = $total_in + $in;
                                    $total_out = $total_out + $out;
                                    debug("Aggregate so far: $total_in $total_out\n");

# keep a track of the range of dates for data sources (mainly for MRTG/textfile based DS)
                                    if ($datatime > 0) {
                                        if ($this->max_data_time == null
                                            || $datatime > $this->max_data_time) {
                                            $this->max_data_time = $datatime;
                                        }

                                        if ($this->min_data_time == null
                                            || $datatime < $this->min_data_time) {
                                            $this->min_data_time = $datatime;
                                        }

                                        debug("DataTime MINMAX: " . $this->min_data_time
                                            . " -> " . $this->max_data_time . "\n");
                                    }
                                }
                                $tindex++;
                            }

                            debug(
                                "ReadData complete for $type $name: $total_in $total_out\n");
                        } else {
                            debug("ReadData: No targets for $type $name\n");
                        }
                    } else {
                        debug(
                            "ReadData: Skipping $type $name that looks like a template\n.");
                    }

                    $myobj->bandwidth_in = $total_in;
                    $myobj->bandwidth_out = $total_out;

                    if ($type == 'LINK' && $myobj->duplex == 'half') {
// in a half duplex link, in and out share a common bandwidth pool, so percentages need to include both
                        debug("Calculating percentage using half-duplex\n");
                        $myobj->outpercent = (($total_in + $total_out)
                            / ($myobj->max_bandwidth_out)) * 100;
                        $myobj->inpercent = (($total_out + $total_in)
                            / ($myobj->max_bandwidth_in)) * 100;

                        if ($myobj->max_bandwidth_out != $myobj->max_bandwidth_in) {
                            warn(
                                "ReadData: $type $name: You're using asymmetric bandwidth AND half-duplex in the same link. That makes no sense. [WMWARN44]\n");
                        }
                    } else {
                        $myobj->outpercent = (($total_out) / ($myobj->max_bandwidth_out))
                            * 100;
                        $myobj->inpercent = (($total_in) / ($myobj->max_bandwidth_in))
                            * 100;
                    }

# print $myobj->name."=>".$myobj->inpercent."%/".$myobj->outpercent."\n";

                    $warn_in = true;
                    $warn_out = true;

                    if ($type == 'NODE' && $myobj->scalevar == 'in') {
                        $warn_out = false;
                    }

                    if ($type == 'NODE' && $myobj->scalevar == 'out') {
                        $warn_in = false;
                    }

                    if ($myobj->scaletype == 'percent') {
                        list($incol, $inscalekey, $inscaletag) =
                            $this->NewColourFromPercent($myobj->inpercent,
                                $myobj->usescale, $myobj->name, true, $warn_in);
                        list($outcol, $outscalekey, $outscaletag) =
                            $this->NewColourFromPercent($myobj->outpercent,
                                $myobj->usescale, $myobj->name, true, $warn_out);
                    } else {
                        // use absolute values, if that's what is requested
                        list($incol, $inscalekey, $inscaletag) =
                            $this->NewColourFromPercent($myobj->bandwidth_in,
                                $myobj->usescale, $myobj->name, false, $warn_in);
                        list($outcol, $outscalekey, $outscaletag) =
                            $this->NewColourFromPercent($myobj->bandwidth_out,
                                $myobj->usescale, $myobj->name, false, $warn_out);
                    }

                    $myobj->add_note("inscalekey", $inscalekey);
                    $myobj->add_note("outscalekey", $outscalekey);

                    $myobj->add_note("inscaletag", $inscaletag);
                    $myobj->add_note("outscaletag", $outscaletag);

                    $myobj->add_note("inscalecolor", $incol->as_html());
                    $myobj->add_note("outscalecolor", $outcol->as_html());

                    $myobj->colours[IN] = $incol;
                    $myobj->colours[OUT] = $outcol;

                    debug("ReadData: Setting $total_in,$total_out\n");
                    unset($myobj);
                }
            }
            debug("ReadData Completed.\n");
            debug("------------------------------\n");
        } else {
            debug("ReadData skipped due to sizedebug\n");
        }
    }

    // nodename is a vestigal parameter, from the days when nodes were just big labels
    function DrawLabelRotated($im, $x, $y, $angle, $text, $font, $padding, $linkname,
        $textcolour, $bgcolour, $outlinecolour, &$map, $direction)
    {
        list($strwidth, $strheight) = $this->myimagestringsize($font, $text);

        if (abs($angle) > 90) {
            $angle -= 180;
        }

        if ($angle < -180) {
            $angle += 360;
        }

        $rangle = -deg2rad($angle);

        $extra = 3;

        $x1 = $x - ($strwidth / 2) - $padding - $extra;
        $x2 = $x + ($strwidth / 2) + $padding + $extra;
        $y1 = $y - ($strheight / 2) - $padding - $extra;
        $y2 = $y + ($strheight / 2) + $padding + $extra;

        // a box. the last point is the start point for the text.
        $points = array (
            $x1,
            $y1,
            $x1,
            $y2,
            $x2,
            $y2,
            $x2,
            $y1,
            $x - $strwidth / 2,
            $y + $strheight / 2 + 1
        );

        $npoints = count($points) / 2;

        RotateAboutPoint($points, $x, $y, $rangle);

        if ($bgcolour != array (
            -1,
            -1,
            -1
        )) {
            $bgcol = myimagecolorallocate($im, $bgcolour[0], $bgcolour[1], $bgcolour[2]);
            # imagefilledrectangle($im, $x1, $y1, $x2, $y2, $bgcol);
            imagefilledpolygon($im, $points, 4, $bgcol);
        }

        if ($outlinecolour != array (
            -1,
            -1,
            -1
        )) {
            $outlinecol = myimagecolorallocate($im, $outlinecolour[0], $outlinecolour[1],
                $outlinecolour[2]);
            imagepolygon($im, $points, 4, $outlinecol);
        }

        $textcol =
            myimagecolorallocate($im, $textcolour[0], $textcolour[1], $textcolour[2]);
        $this->myimagestring($im, $font, $points[8], $points[9], $text, $textcol, $angle);

        $areaname = "LINK:L" . $map->links[$linkname]->id . ':' . ($direction + 2);

// the rectangle is about half the size in the HTML, and easier to optimise/detect in the browser
        if ($angle == 0) {
            $map->imap->addArea("Rectangle", $areaname, '', array (
                $x1,
                $y1,
                $x2,
                $y2
            ));

            debug("Adding Rectangle imagemap for $areaname\n");
        } else {
            $map->imap->addArea("Polygon", $areaname, '', $points);

            debug("Adding Poly imagemap for $areaname\n");
        }
        $this->links[$linkname]->imap_areas[] = $areaname;
    }

    function ColourFromPercent($image, $percent, $scalename = 'DEFAULT', $name = '')
    {
        $bt = debug_backtrace();
        $function = (isset($bt[1]['function']) ? $bt[1]['function'] : '');
        print "$function calls ColourFromPercent\n";

        exit();
    }

    function NewColourFromPercent($value, $scalename = 'DEFAULT', $name = '',
        $is_percent = true, $scale_warning = true)
    {
        $col = new Colour(0, 0, 0);
        $tag = '';
        $matchsize = null;

        $nowarn_clipping = intval($this->get_hint("nowarn_clipping"));
        $nowarn_scalemisses = (!$scale_warning)
            || intval($this->get_hint("nowarn_scalemisses"));

        if (isset($this->colours[$scalename])) {
            $colours = $this->colours[$scalename];

            if ($is_percent && $value > 100) {
                if ($nowarn_clipping == 0) {
                    warn(
                        "NewColourFromPercent: Clipped $value% to 100% for item $name [WMWARN33]\n");
                }
                $value = 100;
            }

            if ($is_percent && $value < 0) {
                if ($nowarn_clipping == 0) {
                    warn(
                        "NewColourFromPercent: Clipped $value% to 0% for item $name [WMWARN34]\n");
                }
                $value = 0;
            }

            foreach ($colours as $key => $colour) {
                if ((!isset($colour['special']) || $colour['special']
                    == 0) and ($value >= $colour['bottom']) and ($value
                    <= $colour['top'])) {
                    $range = $colour['top'] - $colour['bottom'];

                    if (isset($colour['red2'])) {
                        if ($colour["bottom"] == $colour["top"]) {
                            $ratio = 0;
                        } else {
                            $ratio = ($value - $colour["bottom"])
                                / ($colour["top"] - $colour["bottom"]);
                        }

                        $r = $colour["red1"]
                            + ($colour["red2"] - $colour["red1"]) * $ratio;
                        $g = $colour["green1"]
                            + ($colour["green2"] - $colour["green1"]) * $ratio;
                        $b = $colour["blue1"]
                            + ($colour["blue2"] - $colour["blue1"]) * $ratio;
                    } else {
                        $r = $colour["red1"];
                        $g = $colour["green1"];
                        $b = $colour["blue1"];
                    }

// change in behaviour - with multiple matching ranges for a value, the smallest range wins
                    if (is_null($matchsize) || ($range < $matchsize)) {
                        $col = new Colour($r, $g, $b);
                        $matchsize = $range;
                    }

                    if (isset($colour['tag'])) {
                        $tag = $colour['tag'];
                    }

                    debug("NCFPC $name $scalename $value '$tag' $key $r $g $b\n");

                    return (array (
                        $col,
                        $key,
                        $tag
                    ));
                }
            }
        } else {
            if ($scalename != 'none') {
                warn(
                    "ColourFromPercent: Attempted to use non-existent scale: $scalename for item $name [WMWARN09]\n");
            } else {
                return array (
                    new Colour(255, 255, 255),
                    '',
                    ''
                );
            }
        }

// shouldn't really get down to here if there's a complete SCALE

// you'll only get grey for a COMPLETELY quiet link if there's no 0 in the SCALE lines
        if ($value == 0) {
            return array (
                new Colour(192, 192, 192),
                '',
                ''
            );
        }

        if ($nowarn_scalemisses == 0) {
            warn(
                "NewColourFromPercent: Scale $scalename doesn't include a line for $value"
                . ($is_percent ? "%" : "") . " while drawing item $name [WMWARN29]\n");
        }
        // and you'll only get white for a link with no colour assigned
        return array (
            new Colour(255, 255, 255),
            '',
            ''
        );
    }

    function coloursort($a, $b)
    {
        if ($a['bottom'] == $b['bottom']) {
            if ($a['top'] < $b['top']) {
                return -1;
            }
            ;

            if ($a['top'] > $b['top']) {
                return 1;
            }
            ;
            return 0;
        }

        if ($a['bottom'] < $b['bottom']) {
            return -1;
        }

        return 1;
    }

    function FindScaleExtent($scalename = "DEFAULT")
    {
        $max = -999999999999999999999;
        $min = -$max;

        if (isset($this->colours[$scalename])) {
            $colours = $this->colours[$scalename];

            foreach ($colours as $key => $colour) {
                if (!$colour['special']) {
                    $min = min($colour['bottom'], $min);
                    $max = max($colour['top'], $max);
                }
            }
        } else {
            warn("FindScaleExtent: non-existent SCALE $scalename [WMWARN43]\n");
        }
        return array (
            $min,
            $max
        );
    }

    function DrawLegend_Horizontal($im, $scalename = "DEFAULT", $width = 400)
    {
        $title = $this->keytext[$scalename];

        $colours = $this->colours[$scalename];
        $nscales = $this->numscales[$scalename];

        debug("Drawing $nscales colours into SCALE\n");

        $font = $this->keyfont;

        $x = 0;
        $y = 0;

        $scalefactor = $width / 100;

        list($tilewidth, $tileheight) = $this->myimagestringsize($font, "100%");
        $box_left = $x;

        $scale_left = $box_left + 4 + $scalefactor / 2;
        $box_right = $scale_left + $width + $tilewidth + 4 + $scalefactor / 2;
        $scale_right = $scale_left + $width;

        $box_top = $y;

        $scale_top = $box_top + $tileheight + 6;
        $scale_bottom = $scale_top + $tileheight * 1.5;
        $box_bottom = $scale_bottom + $tileheight * 2 + 6;

        $scale_im = imagecreatetruecolor($box_right + 1, $box_bottom + 1);
        $scale_ref = 'gdref_legend_' . $scalename;
        $this->AllocateScaleColours($scale_im, $scale_ref);

        imagefilledrectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
            $this->colours['DEFAULT']['KEYBG'][$scale_ref]);
     
		imagerectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
            $this->colours['DEFAULT']['KEYOUTLINE'][$scale_ref]);

        $this->myimagestring($scale_im, $font, $scale_left,
            $scale_bottom + $tileheight * 2 + 2, $title,
            $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

        for ($p = 0; $p <= 100; $p++) {
            $dx = $p * $scalefactor;

            if (($p % 25) == 0) {
                imageline($scale_im, $scale_left + $dx, $scale_top - $tileheight,
                    $scale_left + $dx, $scale_bottom + $tileheight,
                    $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
                $labelstring = sprintf("%d%%", $p);
                $this->myimagestring($scale_im, $font, $scale_left + $dx + 2,
                    $scale_top - 2, $labelstring,
                    $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
            }

            list($col, $junk) = $this->NewColourFromPercent($p, $scalename);

            if ($col->is_real()) {
                $cc = $col->gdallocate($scale_im);
                imagefilledrectangle($scale_im, $scale_left + $dx - $scalefactor / 2,
                    $scale_top, $scale_left + $dx + $scalefactor / 2, $scale_bottom, $cc);
            }
        }

        imagecopy($im, $scale_im, $this->keyx[$scalename], $this->keyy[$scalename], 0, 0,
            imagesx($scale_im), imagesy($scale_im));
        $this->keyimage[$scalename] = $scale_im;

        $rx = $this->keyx[$scalename];
        $ry = $this->keyy[$scalename];

        $areaname = "LEGEND:" . $scalename;
        $this->imap->addArea("Rectangle", $areaname, '', array (
            $rx + $box_left,
            $ry + $box_top,
            $rx + $box_right,
            $ry + $box_bottom
        ));

        $this->imap_areas[] = $areaname;
    }

    function DrawLegend_Vertical($im, $scalename = "DEFAULT", $height = 400,
        $inverted = false)
    {
        $title = $this->keytext[$scalename];

        $colours = $this->colours[$scalename];
        $nscales = $this->numscales[$scalename];

        debug("Drawing $nscales colours into SCALE\n");

        $font = $this->keyfont;

        $x = $this->keyx[$scalename];
        $y = $this->keyy[$scalename];

        $scalefactor = $height / 100;

        list($tilewidth, $tileheight) = $this->myimagestringsize($font, "100%");

        $box_left = 0;
        $box_top = 0;

        $scale_left = $box_left + $scalefactor * 2 + 4;
        $scale_right = $scale_left + $tileheight * 2;
        $box_right = $scale_right + $tilewidth + $scalefactor * 2 + 4;

        list($titlewidth, $titleheight) = $this->myimagestringsize($font, $title);

        if (($box_left + $titlewidth + $scalefactor * 3) > $box_right) {
            $box_right = $box_left + $scalefactor * 4 + $titlewidth;
        }

        $scale_top = $box_top + 4 + $scalefactor + $tileheight * 2;
        $scale_bottom = $scale_top + $height;
        $box_bottom = $scale_bottom + $scalefactor + $tileheight / 2 + 4;

        $scale_im = imagecreatetruecolor($box_right + 1, $box_bottom + 1);
        $scale_ref = 'gdref_legend_' . $scalename;
        $this->AllocateScaleColours($scale_im, $scale_ref);

        imagefilledrectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
            $this->colours['DEFAULT']['KEYBG']['gdref1']);
        imagerectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
            $this->colours['DEFAULT']['KEYOUTLINE']['gdref1']);

        $this->myimagestring($scale_im, $font, $scale_left - $scalefactor,
            $scale_top - $tileheight, $title,
            $this->colours['DEFAULT']['KEYTEXT']['gdref1']);

        $updown = 1;

        if ($inverted) {
            $updown = -1;
        }

        for ($p = 0; $p <= 100; $p++) {
            if ($inverted) {
                $dy = (100 - $p) * $scalefactor;
            } else {
                $dy = $p * $scalefactor;
            }

            if (($p % 25) == 0) {
                imageline($scale_im, $scale_left - $scalefactor, $scale_top + $dy,
                    $scale_right + $scalefactor, $scale_top + $dy,
                    $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
                $labelstring = sprintf("%d%%", $p);
                $this->myimagestring($scale_im, $font, $scale_right + $scalefactor * 2,
                    $scale_top + $dy + $tileheight / 2, $labelstring,
                    $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
            }

            list($col, $junk) = $this->NewColourFromPercent($p, $scalename);

            if ($col->is_real()) {
                $cc = $col->gdallocate($scale_im);
                imagefilledrectangle($scale_im, $scale_left,
                    $scale_top + $dy - $scalefactor / 2, $scale_right,
                    $scale_top + $dy + $scalefactor / 2, $cc);
            }
        }

        imagecopy($im, $scale_im, $this->keyx[$scalename], $this->keyy[$scalename], 0, 0,
            imagesx($scale_im), imagesy($scale_im));
        $this->keyimage[$scalename] = $scale_im;

        $rx = $this->keyx[$scalename];
        $ry = $this->keyy[$scalename];
        $areaname = "LEGEND:" . $scalename;

        $this->imap->addArea("Rectangle", $areaname, '', array (
            $rx + $box_left,
            $ry + $box_top,
            $rx + $box_right,
            $ry + $box_bottom
        ));

        $this->imap_areas[] = $areaname;
    }

    function DrawLegend_Classic($im, $scalename = 'DEFAULT', $use_tags = false)
    {
        $title = $this->keytext[$scalename];

        $colours = $this->colours[$scalename];
        usort($colours, array (
            'Weathermap',
            'coloursort'
        ));

        $nscales = $this->numscales[$scalename];

        debug("Drawing $nscales colours into SCALE\n");

        $hide_zero = intval($this->get_hint("key_hidezero_" . $scalename));
        $hide_percent = intval($this->get_hint("key_hidepercent_" . $scalename));

        // did we actually hide anything?
        $hid_zero = false;

        if (($hide_zero == 1) && isset($colours['0_0'])) {
            $nscales--;
            $hid_zero = true;
        }

        $font = $this->keyfont;

        $x = $this->keyx[$scalename];
        $y = $this->keyy[$scalename];

        list($tilewidth, $tileheight) = $this->myimagestringsize($font, 'MMMM');
        $tileheight = $tileheight * 1.1;
        $tilespacing = $tileheight + 2;

        if (($this->keyx[$scalename] >= 0) && ($this->keyy[$scalename] >= 0)) {

            list($minwidth, $junk) = $this->myimagestringsize($font, 'MMMM 100%-100%');
            list($minminwidth, $junk) = $this->myimagestringsize($font, 'MMMM ');
            list($boxwidth, $junk) = $this->myimagestringsize($font, $title);

            if ($use_tags) {
                $max_tag = 0;

                foreach ($colours as $colour) {
                    if (isset($colour['tag'])) {
                        list($w, $junk) = $this->myimagestringsize($font, $colour['tag']);

                        if ($w > $max_tag) {
                            $max_tag = $w;
                        }
                    }
                }

                // now we can tweak the widths, appropriately to allow for the tag strings
                if (($max_tag + $minminwidth) > $minwidth) {
                    $minwidth = $minminwidth + $max_tag;
                }
            }

            $minwidth += 10;
            $boxwidth += 10;

            if ($boxwidth < $minwidth) {
                $boxwidth = $minwidth;
            }

            $boxheight = $tilespacing * ($nscales + 1) + 10;

            $boxx = $x;
            $boxy = $y;
            $boxx = 0;
            $boxy = 0;

            // allow for X11-style negative positioning
            if ($boxx < 0) {
                $boxx += $this->width;
            }

            if ($boxy < 0) {
                $boxy += $this->height;
            }

            $scale_im = imagecreatetruecolor($boxwidth + 1, $boxheight + 1);
            $scale_ref = 'gdref_legend_' . $scalename;
            $this->AllocateScaleColours($scale_im, $scale_ref);

            imagefilledrectangle($scale_im, $boxx, $boxy, $boxx + $boxwidth,
                $boxy + $boxheight, $this->colours['DEFAULT']['KEYBG'][$scale_ref]);
            imagerectangle($scale_im, $boxx, $boxy, $boxx + $boxwidth, $boxy + $boxheight,
                $this->colours['DEFAULT']['KEYOUTLINE'][$scale_ref]);
            $this->myimagestring($scale_im, $font, $boxx + 4, $boxy + 4 + $tileheight,
                $title, $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

            $i = 1;

            foreach ($colours as $colour) {
                if (!isset($colour['special']) || $colour['special'] == 0) {
                    // pick a value in the middle...
                    $value = ($colour['bottom'] + $colour['top']) / 2;
                    debug(sprintf("%f-%f (%f)  %d %d %d\n", $colour['bottom'],
                        $colour['top'], $value, $colour['red1'], $colour['green1'],
                        $colour['blue1']));

                    if (($hide_zero == 0) || $colour['key'] != '0_0') {
                        $y = $boxy + $tilespacing * $i + 8;
                        $x = $boxx + 6;

                        $fudgefactor = 0;

                        if ($hid_zero && $colour['bottom'] == 0) {
// calculate a small offset that can be added, which will hide the zero-value in a
// gradient, but not make the scale incorrect. A quarter of a pixel should do it.
                            $fudgefactor = ($colour['top'] - $colour['bottom'])
                                / ($tilewidth * 4);
                        }

// if it's a gradient, red2 is defined, and we need to sweep the values
                        if (isset($colour['red2'])) {
                            for ($n = 0; $n <= $tilewidth; $n++) {
                                $value = $fudgefactor + $colour['bottom']
                                    + ($n / $tilewidth)
                                    * ($colour['top'] - $colour['bottom']);
                                list($ccol, $junk) =
                                    $this->NewColourFromPercent($value, $scalename, '',
                                        false);
                                $col = $ccol->gdallocate($scale_im);
                                imagefilledrectangle($scale_im, $x + $n, $y, $x + $n,
                                    $y + $tileheight, $col);
                            }
                        } else {
                            // pick a value in the middle...

                            list($ccol, $junk) =
                                $this->NewColourFromPercent($value, $scalename, '',
                                    false);
                            $col = $ccol->gdallocate($scale_im);
                            imagefilledrectangle($scale_im, $x, $y, $x + $tilewidth,
                                $y + $tileheight, $col);
                        }

                        if ($use_tags) {
                            $labelstring = "";

                            if (isset($colour['tag'])) {
                                $labelstring = $colour['tag'];
                            }
                        } else {
                            $labelstring =
                                sprintf("%s-%s", $colour['bottom'], $colour['top']);

                            if ($hide_percent == 0) {
                                $labelstring .= "%";
                            }
                        }

                        $this->myimagestring($scale_im, $font, $x + 4 + $tilewidth,
                            $y + $tileheight, $labelstring,
                            $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
                        $i++;
                    }
                    imagecopy($im, $scale_im, $this->keyx[$scalename],
                        $this->keyy[$scalename], 0, 0, imagesx($scale_im),
                        imagesy($scale_im));
                    $this->keyimage[$scalename] = $scale_im;
                }
            }

            $areaname = "LEGEND:" . $scalename;

            $this->imap->addArea("Rectangle", $areaname, '', array (
                $this->keyx[$scalename],
                $this->keyy[$scalename],
                $this->keyx[$scalename] + $boxwidth,
                $this->keyy[$scalename] + $boxheight
            ));

            $this->imap_areas[] = $areaname;
        }
    }

    function DrawTimestamp($im, $font, $colour, $which = "")
    {
        // add a timestamp to the corner, so we can tell if it's all being updated

        switch ($which) {
            case "MIN":
                $stamp = strftime($this->minstamptext, $this->min_data_time);
                $pos_x = $this->mintimex;
                $pos_y = $this->mintimey;
                break;

            case "MAX":
                $stamp = strftime($this->maxstamptext, $this->max_data_time);
                $pos_x = $this->maxtimex;
                $pos_y = $this->maxtimey;
                break;

                default:
                $stamp = $this->datestamp;
                $pos_x = $this->timex;
                $pos_y = $this->timey;
                break;
        }

        list($boxwidth, $boxheight) = $this->myimagestringsize($font, $stamp);

        $x = $this->width - $boxwidth;
        $y = $boxheight;

        if (($pos_x != 0) && ($pos_y != 0)) {
            $x = $pos_x;
            $y = $pos_y;
        }

        $this->myimagestring($im, $font, $x, $y, $stamp, $colour);
        $areaname = $which . "TIMESTAMP";

        $this->imap->addArea("Rectangle", $areaname, '', array (
            $x,
            $y,
            $x + $boxwidth,
            $y - $boxheight
        ));

        $this->imap_areas[] = $areaname;
    }

    function DrawTitle($im, $font, $colour)
    {
        $string = $this->ProcessString($this->title, $this);

        if ($this->get_hint('screenshot_mode') == 1)
            $string = screenshotify($string);

        list($boxwidth, $boxheight) = $this->myimagestringsize($font, $string);

        $x = 10;
        $y = $this->titley - $boxheight;

        if (($this->titlex >= 0) && ($this->titley >= 0)) {
            $x = $this->titlex;
            $y = $this->titley;
        }

        $this->myimagestring($im, $font, $x, $y, $string, $colour);

        $this->imap->addArea('Rectangle', 'TITLE', '', array (
            $x,
            $y,
            $x + $boxwidth,
            $y - $boxheight
        ));

        $this->imap_areas[] = 'TITLE';
    }

    function ReadConfig_Handle_VIA($fullcommand, $args, $matches, &$curobj, $filename,
        $linecount)
    {
        if (preg_match("/^\s*VIA\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $fullcommand,
            $matches)) {
            $curobj->vialist[] = array (
                $matches[1],
                $matches[2]
            );

            return true;
        }

        if (preg_match("/^\s*VIA\s+(\S+)\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $fullcommand,
            $matches)) {
            $curobj->vialist[] = array (
                $matches[2],
                $matches[3],
                $matches[1]
            );

            return true;
        }
        return false;
    }

    function ReadConfig_Handle_NODES($fullcommand, $args, $matches, &$curobj, $filename,
        $linecount)
    {
        if (preg_match("/^NODES\s+(\S+)\s+(\S+)\s*$/i", $fullcommand, $matches)) {

            $valid_nodes = 2;

            foreach (array (
                1,
                2
            ) as $i) {
                $endoffset[$i] = 'C';
                $nodenames[$i] = $matches[$i];

                // percentage of compass - must be first
                if (preg_match("/:(NE|SE|NW|SW|N|S|E|W|C)(\d+)$/i", $matches[$i],
                    $submatches)) {
                    $endoffset[$i] = $submatches[1] . $submatches[2];
                    $nodenames[$i] =
                        preg_replace("/:(NE|SE|NW|SW|N|S|E|W|C)\d+$/i", '', $matches[$i]);
                    $this->need_size_precalc = true;
                }

                if (preg_match("/:(NE|SE|NW|SW|N|S|E|W|C)$/i", $matches[$i], $submatches))
                    {
                    $endoffset[$i] = $submatches[1];
                    $nodenames[$i] =
                        preg_replace("/:(NE|SE|NW|SW|N|S|E|W|C)$/i", '', $matches[$i]);
                    $this->need_size_precalc = true;
                }

                if (preg_match("/:(-?\d+r\d+)$/i", $matches[$i], $submatches)) {
                    $endoffset[$i] = $submatches[1];
                    $nodenames[$i] = preg_replace("/:(-?\d+r\d+)$/i", '', $matches[$i]);
                    $this->need_size_precalc = true;
                }

                if (preg_match("/:([-+]?\d+):([-+]?\d+)$/i", $matches[$i], $submatches)) {
                    $xoff = $submatches[1];
                    $yoff = $submatches[2];
                    $endoffset[$i] = $xoff . ":" . $yoff;
                    $nodenames[$i] = preg_replace("/:$xoff:$yoff$/i", '', $matches[$i]);
                    $this->need_size_precalc = true;
                }

                if (!array_key_exists($nodenames[$i], $this->nodes)) {
                    warn("Unknown node '" . $nodenames[$i]
                        . "' on line $linecount of config\n");
                    $valid_nodes--;
                }
            }

            // TODO - really, this should kill the whole link, and reset for the next one
            // XXX this error case will not work in the handler function
            if ($valid_nodes == 2) {
                $curobj->a = $this->nodes[$nodenames[1]];
                $curobj->b = $this->nodes[$nodenames[2]];
                $curobj->a_offset = $endoffset[1];
                $curobj->b_offset = $endoffset[2];
            } else {
                // this'll stop the current link being added
                $last_seen = "broken";
            }

            return true;
        }
        return false;
    }

    function ReadConfig_Handle_SET($fullcommand, $args, $matches, &$curobj, $filename,
        $linecount)
    {
        if (preg_match("/^SET\s+(\S+)\s+(.*)\s*$/i", $fullcommand, $matches)) {
            $curobj->add_hint($matches[1], trim($matches[2]));
            return true;
        }

        // allow setting a variable to ""
        if (preg_match("/^SET\s+(\S+)\s*$/i", $fullcommand, $matches)) {
            $curobj->add_hint($matches[1], '');
            return true;
        }

        return false;
    }

    function ReadConfig_Handle_GLOBALCOLOR($fullcommand, $args, $matches, &$curobj,
        $filename, $linecount)
    {
        $key = str_replace("COLOR", "", strtoupper($args[0]));

        $this->colours['DEFAULT'][$key]['red1'] = $args[1];
        $this->colours['DEFAULT'][$key]['green1'] = $args[2];
        $this->colours['DEFAULT'][$key]['blue1'] = $args[3];
        $this->colours['DEFAULT'][$key]['bottom'] = -2;
        $this->colours['DEFAULT'][$key]['top'] = -1;
        $this->colours['DEFAULT'][$key]['special'] = 1;

        return true;
    }

    function ReadConfig_Handle_NODE_USESCALE($fullcommand, $args, $matches, &$curobj, $filename,   $linecount)
    {
        $svar = '';
        $stype = 'percent';

        if (isset($matches[3])) {
            $svar = trim($matches[3]);
        }

        if (isset($matches[6])) {
            $stype = strtolower(trim($matches[6]));
        }

        // opens the door for other scaley things...
        switch ($args[0]) {
            case 'ICON':
                $varname = 'iconscalevar';
                $uvarname = 'useiconscale';
                $tvarname = 'iconscaletype';

                break;

            default:
                $varname = 'scalevar';
                $uvarname = 'usescale';
                $tvarname = 'scaletype';
                break;
        }

        if ($svar != '') {
            $curobj->$varname = $svar;
        }
        $curobj->$tvarname = $stype;
        $curobj->$uvarname = $matches[2];

        return true;
    }


    function ReadConfig_Handle_FONTDEFINE($fullcommand, $args, $matches, &$curobj, $filename,
        $linecount)
    {

            if(isset($args[3])) {
				debug("New TrueType font in slot %d\n",$args[1]);
					if (function_exists("imagettfbbox")) {
// test if this font is valid, before adding it to the font table...
                            $bounds =
                                @imagettfbbox($args[3], 0, $args[2], "Ignore me");

                            if (isset($bounds[0])) {
                                $this->fonts[$args[1]]->type = "truetype";
                                $this->fonts[$args[1]]->file = $args[2];
                                $this->fonts[$args[1]]->size = $args[3];
                            } else {
                                warn("Failed to load ttf font " . $args[2]
                                    . " - at config line $linecount\n [WMWARN30]");
                            }
                        } else {
                            warn(
                                "imagettfbbox() is not a defined function. You don't seem to have FreeType compiled into your gd module. [WMWARN31]\n");
                        }

                        return true;
            }
            else
       		 {
				debug("New GD font in slot %d\n",$args[1]);
				$newfont = imageloadfont($args[2]);

                        if ($newfont) {
                            $this->fonts[$args[1]]->type = "gd";
                            $this->fonts[$args[1]]->file = $args[2];
                            $this->fonts[$args[1]]->gdnumber = $newfont;
                        } else {
                            warn("Failed to load GD font: " . $args[2]
                                . " ($newfont) at config line $linecount [WMWARN32]\n");
                        }
                        return true;
            }

        

        return false;
    }

    function ReadConfig_Handle_OVERLIB($fullcommand, $args, $matches, &$curobj, $filename,
        $linecount)
    {
            $this->has_overlibs = true;

        $urls = preg_split('/\s+/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);

        if ($args[0] == 'INOVERLIBGRAPH') {
            $index = IN;
        }

        if ($args[0] == 'OUTOVERLIBGRAPH') {
            $index = OUT;
        }

        if ($args[0] == 'OVERLIBGRAPH') {
            $curobj->overliburl[IN] = $urls;
            $curobj->overliburl[OUT] = $urls;
        } else {
            $curobj->overliburl[$index] = $urls;
        }

        return true;
    }

    function ReadConfig_Handle_COLOR($fullcommand, $args, $matches, &$curobj, $filename,
        $linecount)
    {
        $key = $args[0];
        $field = str_replace("color", "colour", strtolower($args[0]));
        $val = strtolower($args[1]);

        if (isset($args[2])) // this is a regular colour setting thing
        {
            $curobj->$field = array (
                $args[1],
                $args[2],
                $args[3]
            );

            return true;
        }

        if ($val == 'none') {
            $curobj->$field = array (
                -1,
                -1,
                -1
            );

            return true;
        }

        if ($val == 'contrast') {
            $curobj->$field = array (
                -3,
                -3,
                -3
            );

            return true;
        }

        if ($val == 'copy') {
            $curobj->$field = array (
                -2,
                -2,
                -2
            );

            return true;
        }

        return false;
    }

    function ReadConfig_Handle_TARGET($fullcommand, $args, $matches, &$curobj, $filename,
        $linecount)
    {
// wipe any existing targets, otherwise things in the DEFAULT accumulate with the new ones
        $curobj->targets = array ();
        array_shift($args); // take off the actual TARGET keyword

        foreach ($args as $arg) {
// we store the original TARGET string, and line number, along with the breakdown, to make nicer error messages later
// array of 7 things:
// - only 0,1,2,3,4 are used at the moment (more used to be before DS plugins)
// 0 => final target string (filled in by ReadData)
// 1 => multiplier (filled in by ReadData)
// 2 => config filename where this line appears
// 3 => linenumber in that file
// 4 => the original target string
// 5 => the plugin to use to pull data
            $newtarget = array (
                '',
                '',
                $filename,
                $linecount,
                $arg,
                "",
                ""
            );

            if ($curobj) {
                debug("  TARGET: $arg\n");
                $curobj->targets[] = $newtarget;
            }
        }

        return true;
    }

    function ReadConfig($input, $is_include = false)
    {
        global $WM_config_keywords2;

        $curnode = null;
        $curlink = null;
        $matches = 0;
        $nodesseen = 0;
        $linksseen = 0;
        $scalesseen = 0;
        $last_seen = 'GLOBAL';
        $filename = '';
        $objectlinecount = 0;


        // check if $input is more than one line. if it is, it's a text of a config file
        // if it isn't, it's the filename

        $lines = array ();

        if ((strchr($input, "\n") != false) || (strchr($input, "\r") != false)) {
            debug("ReadConfig Detected that this is a config fragment.\n");
            // strip out any Windows line-endings that have gotten in here
            $input = str_replace("\r", "", $input);
            $lines = explode("/n", $input);
            $filename = "{text insert}";
        } else {
            debug("ReadConfig Detected that this is a config filename.\n");
            $filename = $input;

            if ($is_include) {
                debug("ReadConfig Detected that this is an INCLUDED config filename.\n");

                if ($is_include && in_array($filename, $this->included_files)) {
                    warn("Attempt to include '$filename' twice! Skipping it.\n");
                    return (false);
                } else {
                    $this->included_files[] = $filename;
                    $this->has_includes = true;
                }
            }

            $fd = fopen($filename, "r");

            if ($fd) {
                while (!feof($fd)) {
                    $buffer = fgets($fd, 4096);
                    // strip out any Windows line-endings that have gotten in here
                    $buffer = str_replace("\r", "", $buffer);
                    $lines[] = $buffer;
                }
                fclose($fd);
            }
        }

        $linecount = 0;
        $objectlinecount = 0;

        foreach ($lines as $buffer) {
            $linematched = 0;
            $linecount++;

            $buffer = trim($buffer);

            if ($buffer == '' || substr($buffer, 0, 1) == '#') {
            // this is a comment line, or a blank line
            }
            else {

// for any other config elements that are shared between nodes and links, they can use this
                unset($curobj);
                $curobj = null;

                if ($last_seen == "LINK") {
                    $curobj = &$curlink;
                }

                if ($last_seen == "NODE") {
                    $curobj = &$curnode;
                }

                if ($last_seen == "GLOBAL") {
                    $curobj = &$this;
                }

                $objectlinecount++;

                if (preg_match("/^(LINK|NODE)\s+(\S+)\s*$/i", $buffer, $matches)) {
                    $objectlinecount = 0;

                    if (1 == 1) {
                        $this->ReadConfig_Commit($curobj);
                    } else {
// first, save the previous item, before starting work on the new one
                        if ($last_seen == "NODE") {
                            $this->nodes[$curnode->name] = $curnode;

                            if ($curnode->template == 'DEFAULT')
                                $this->node_template_tree["DEFAULT"][] = $curnode->name;

                            debug("Saving Node: " . $curnode->name . "\n");
                        }

                        if ($last_seen == "LINK") {
                            if (isset($curlink->a) && isset($curlink->b)) {
                                $this->links[$curlink->name] = $curlink;
                                debug("Saving Link: " . $curlink->name . "\n");
                            } else {
                                $this->links[$curlink->name] = $curlink;
                                debug("Saving Template-Only Link: " . $curlink->name
                                    . "\n");
                            }

                            if ($curlink->template == 'DEFAULT')
                                $this->link_template_tree["DEFAULT"][] = $curlink->name;
                        }
                    }

                    if ($matches[1] == 'LINK') {
                        if ($matches[2] == 'DEFAULT') {
                            if ($linksseen > 0) {
                                warn(
                                    "LINK DEFAULT is not the first LINK. Defaults will not apply to earlier LINKs. [WMWARN26]\n");
                            }
                            unset($curlink);
                            debug("Loaded LINK DEFAULT\n");
                            $curlink = $this->links['DEFAULT'];
                        } else {
                            unset($curlink);

                            if (isset($this->links[$matches[2]])) {
                                warn("Duplicate link name " . $matches[2]
                                    . " at line $linecount - only the last one defined is used. [WMWARN25]\n");
                            }

                            debug("New LINK " . $matches[2] . "\n");
                            $curlink = new WeatherMapLink;
                            $curlink->name = $matches[2];
                            $curlink->Reset($this);

                            $linksseen++;
                        }

                        $last_seen = "LINK";
                        $curlink->configline = $linecount;
                        $linematched++;
                        $curobj = &$curlink;
                    }

                    if ($matches[1] == 'NODE') {
                        if ($matches[2] == 'DEFAULT') {
                            if ($nodesseen > 0) {
                                warn(
                                    "NODE DEFAULT is not the first NODE. Defaults will not apply to earlier NODEs. [WMWARN27]\n");
                            }

                            unset($curnode);
                            debug("Loaded NODE DEFAULT\n");
                            $curnode = $this->nodes['DEFAULT'];
                        } else {
                            unset($curnode);

                            if (isset($this->nodes[$matches[2]])) {
                                warn("Duplicate node name " . $matches[2]
                                    . " at line $linecount - only the last one defined is used. [WMWARN24]\n");
                            }

                            $curnode = new WeatherMapNode;
                            $curnode->name = $matches[2];
                            $curnode->Reset($this);

                            $nodesseen++;
                        }

                        $curnode->configline = $linecount;
                        $last_seen = 'NODE';
                        $linematched++;
                        $curobj = &$curnode;
                    }

                    # record where we first heard about this object
                    $curobj->defined_in = $filename;
                }

                if ($linematched == 0) {
                    // alternative for use later where quoted strings are more useful
                    $args = ParseString($buffer);
                }

// From here, the aim of the game is to get out of this loop as
// early as possible, without running more preg_match calls than
// necessary. In 0.97, this per-line loop accounted for 50% of
// the running time!


// this next loop replaces a whole pile of duplicated ifs with something with consistent handling

                assert('is_object($curobj)');

                if ($linematched == 0 && true === isset($args[0])) {
// check if there is even an entry in this context for the current keyword
                    if (true === isset($WM_config_keywords2[$last_seen][$args[0]])) {
// if there is, then the entry is an array of arrays - iterate them to validate the config
                        foreach ($WM_config_keywords2[$last_seen][$args[0]] as $keyword) {

                            unset($matches);

                            if ((substr($keyword[1], 0, 1) != '/')
                                || (1 === preg_match($keyword[1], $buffer, $matches))) {

								$key = sprintf("%s:%s:%s", $last_seen, $args[0] ,$keyword[1]);
								$this->coverage[$key]++;
								
                                // if we came here without a regexp, then the \1 etc
                                // refer to arg numbers, not match numbers

                                if (false === isset($matches)) {
                                    $matches = $args;
                                }

                                if (is_array($keyword[2])) {

                                    foreach ($keyword[2] as $key => $val) {
                                        // so we can poke in numbers too, if the value starts with #
                                        // then take the # off, and treat the rest as a number literal
                                        if (substr($val, 0, 1) === '#') {
                                            $val = substr($val, 1);
                                        } elseif (is_numeric($val)) {
                                            // if it's a number, then it's a match number,
                                            // otherwise it's a literal to be put into a variable
                                            $val = $matches[$val];
                                        }

                                        // if there are [] in the string, it's an index into an array
                                        // and the index will be one of the constants: IN or OUT
                                        if (1 === preg_match('/^(.*)\[([^\]]+)\]$/', $key,
                                            $m)) {
                                            $index = constant($m[2]);
                                            $key = $m[1];
                                            $curobj->{$key}[$index] = $val;
                                        } else {
                                            // otherwise, it's just the name of a property on the
                                            // appropriate object.
                                            $curobj->$key = $val;
                                        }
                                    }
                                    $linematched++;
                                } else {

                                    // the third arg wasn't an array, it was a function name.
                                    // call that function to handle this keyword
                                    if (call_user_func(array (
                                        $this,
                                        $keyword[2]
                                    ), $buffer, $args, $matches, $curobj, $filename,
                                        $linecount)) {
                                        $linematched++;
                                    }
                                }
                            }

                            // jump out of this loop if there's been a match
                            if ($linematched > 0) {
                                break;
                            }
                        }
                    }
                }

                // most config should be recognised by now.
                // remaining items here require special knowledge of
                // the parsing loop (INCLUDE) or do something else funky

                if (1==0 && $linematched == 0) {
                    print "READCONFIG: $last_seen/" . $args[0]
                        . " unhandled - |$buffer|\n";
                }

                // the next blocks are for commands that only apply to one
                // type of object, but need some more processing/validation
                // than config_keywords[] could have done.
                // Putting more common things at the top of these blocks
                // should also help, if possible.

                // LINK-specific stuff that couldn't be done with just a regexp
                if ($last_seen == 'LINK' && $linematched == 0) {
                    if (($linematched == 0) && preg_match(
                        "/^\s*BWLABEL\s+(bits|percent|unformatted|none)\s*$/i",
                        $buffer, $matches)) {
                        $format_in = '';
                        $format_out = '';
                        $style = strtolower($matches[1]);

                        if ($style == 'percent') {
                            $format_in = FMT_PERC_IN;
                            $format_out = FMT_PERC_OUT;
                        }

                        if ($style == 'bits') {
                            $format_in = FMT_BITS_IN;
                            $format_out = FMT_BITS_OUT;
                        }

                        if ($style == 'unformatted') {
                            $format_in = FMT_UNFORM_IN;
                            $format_out = FMT_UNFORM_OUT;
                        }

                        $curobj->labelstyle = $style;
                        $curobj->bwlabelformats[IN] = $format_in;
                        $curobj->bwlabelformats[OUT] = $format_out;
                        $linematched++;
                    }

                    if (($linematched == 0)
                        && preg_match("/^\s*ARROWSTYLE\s+(\d+)\s+(\d+)\s*$/i", $buffer,
                            $matches)) {
                        $curobj->arrowstyle = $matches[1] . ' ' . $matches[2];
                        $linematched++;
                    }
                }
                
                // GLOBAL-specific stuff that couldn't be done with just a regexp
                if ($last_seen == 'GLOBAL' && $linematched == 0) {
                    if (($linematched == 0)
                        && preg_match(
                            "/^\s*KEYPOS\s+([A-Za-z][A-Za-z0-9_]*\s+)?(-?\d+)\s+(-?\d+)(.*)/i",
                            $buffer, $matches)) {
                        $whichkey = trim($matches[1]);

                        if ($whichkey == '') {
                            $whichkey = 'DEFAULT';
                        }

                        $this->keyx[$whichkey] = $matches[2];
                        $this->keyy[$whichkey] = $matches[3];
                        $extra = trim($matches[4]);

                        if ($extra != '') {
                            $this->keytext[$whichkey] = $extra;
                        }

                        // it's possible to have keypos before the scale is defined.
                        // this is to make it at least mostly consistent internally
                        if (!isset($this->keytext[$whichkey])) {
                            $this->keytext[$whichkey] = "DEFAULT TITLE";
                        }

                        if (!isset($this->keystyle[$whichkey])) {
                            $this->keystyle[$whichkey] = "classic";
                        }

                        $linematched++;
                    }


                    if (($linematched == 0)
                        && preg_match(
                            "/^\s*KEYSTYLE\s+([A-Za-z][A-Za-z0-9_]+\s+)?(classic|horizontal|vertical|inverted|tags)\s?(\d+)?\s*$/i",
                            $buffer, $matches)) {
                        $whichkey = trim($matches[1]);

                        if ($whichkey == '')
                            $whichkey = 'DEFAULT';
                        $this->keystyle[$whichkey] = strtolower($matches[2]);

                        if (isset($matches[3]) && $matches[3] != '') {
                            $this->keysize[$whichkey] = $matches[3];
                        } else {
                            $this->keysize[$whichkey] = $this->keysize['DEFAULT'];
                        }

                        $linematched++;
                    }


                    // one REGEXP to rule them all:
                    if (($linematched == 0)
                        && preg_match(
                            "/^\s*SCALE\s+([A-Za-z][A-Za-z0-9_]*\s+)?(\-?\d+\.?\d*[munMGT]?)\s+(\-?\d+\.?\d*[munMGT]?)\s+(?:(\d+)\s+(\d+)\s+(\d+)(?:\s+(\d+)\s+(\d+)\s+(\d+))?|(none))\s*(.*)$/i",
                            $buffer, $matches)) {
                        // The default scale name is DEFAULT
                        if ($matches[1] == '')
                            $matches[1] = 'DEFAULT';
                        else
                            $matches[1] = trim($matches[1]);

                        $key = $matches[2] . '_' . $matches[3];

                        $this->colours[$matches[1]][$key]['key'] = $key;

                        $tag = $matches[11];

                        $this->colours[$matches[1]][$key]['tag'] = $tag;

                        $this->colours[$matches[1]][$key]['bottom'] =
                            unformat_number($matches[2], $this->kilo);
                        $this->colours[$matches[1]][$key]['top'] =
                            unformat_number($matches[3], $this->kilo);
                        $this->colours[$matches[1]][$key]['special'] = 0;

                        if (isset($matches[10]) && $matches[10] == 'none') {
                            $this->colours[$matches[1]][$key]['red1'] = -1;
                            $this->colours[$matches[1]][$key]['green1'] = -1;
                            $this->colours[$matches[1]][$key]['blue1'] = -1;
                        } else {
                            $this->colours[$matches[1]][$key]['red1'] =
                                (int)($matches[4]);
                            $this->colours[$matches[1]][$key]['green1'] =
                                (int)($matches[5]);
                            $this->colours[$matches[1]][$key]['blue1'] =
                                (int)($matches[6]);
                        }

                        // this is the second colour, if there is one
                        if (isset($matches[7]) && $matches[7] != '') {
                            $this->colours[$matches[1]][$key]['red2'] =
                                (int)($matches[7]);
                            $this->colours[$matches[1]][$key]['green2'] =
                                (int)($matches[8]);
                            $this->colours[$matches[1]][$key]['blue2'] =
                                (int)($matches[9]);
                        }

                        if (!isset($this->numscales[$matches[1]])) {
                            $this->numscales[$matches[1]] = 1;
                        } else {
                            $this->numscales[$matches[1]]++;
                        }

// we count if we've seen any default scale, otherwise, we have to add
// one at the end.
                        if ($matches[1] == 'DEFAULT') {
                            $scalesseen++;
                        }

                        $linematched++;
                    }

                    if (($linematched == 0)
                        && preg_match("/^\s*INCLUDE\s+(.*)\s*$/i", $buffer, $matches)) {
                        if (file_exists($matches[1])) {
                            debug("Including '{$matches[1]}'\n");
                            $this->ReadConfig($matches[1], true);
                            $last_seen = "GLOBAL";
                        } else {
                            warn("INCLUDE File '{$matches[1]}' not found!\n");
                        }
                        $linematched++;
                    }
                }


                // *********************************************************

                if (($linematched == 0) && ($last_seen == 'NODE' || $last_seen == 'LINK')
                    && preg_match("/^\s*TEMPLATE\s+(\S+)\s*$/i", $buffer, $matches)) {
                    $tname = $matches[1];

                    if (($last_seen == 'NODE' && isset($this->nodes[$tname]))
                        || ($last_seen == 'LINK' && isset($this->links[$tname]))) {
                        $curobj->template = $matches[1];
                        debug("Resetting to template $last_seen " . $curobj->template
                            . "\n");
                        $curobj->Reset($this);

                        if ($objectlinecount > 1)
                            warn(
                                "line $linecount: TEMPLATE is not first line of object. Some data may be lost. [WMWARN39]\n");
// build up a list of templates - this will be useful later for the tree view

                        if ($last_seen == 'NODE')
                            $this->node_template_tree[$tname][] = $curobj->name;

                        if ($last_seen == 'LINK')
                            $this->link_template_tree[$tname][] = $curobj->name;
                    } else {
                        warn(
                            "line $linecount: $last_seen TEMPLATE '$tname' doesn't exist! (if it does exist, check it's defined first) [WMWARN40]\n");
                    }
                    $linematched++;
                }


                // *********************************************************

                if (($linematched == 0) && ($buffer != '')) {
                    warn("Unrecognised config on line $linecount: $buffer\n");
                }

                if ($linematched > 1) {
                    warn(
                        "Same line ($linecount) interpreted twice. This is a program error. Please report to Howie with your config!\nThe line was: $buffer");
                }
            } // if blankline
        }     // while

        $this->ReadConfig_Commit($curobj);

        debug("ReadConfig has finished reading the config ($linecount lines)\n");
        debug("------------------------------------------\n");

        // load some default colouring, otherwise it all goes wrong
        if ($scalesseen == 0) {
            debug("Adding default SCALE colour set (no SCALE lines seen).\n");
            $defaults = array (
                '0_0' => array (
                    'bottom' => 0,
                    'top' => 0,
                    'red1' => 192,
                    'green1' => 192,
                    'blue1' => 192,
                    'special' => 0
                ),
                '0_1' => array (
                    'bottom' => 0,
                    'top' => 1,
                    'red1' => 255,
                    'green1' => 255,
                    'blue1' => 255,
                    'special' => 0
                ),
                '1_10' => array (
                    'bottom' => 1,
                    'top' => 10,
                    'red1' => 140,
                    'green1' => 0,
                    'blue1' => 255,
                    'special' => 0
                ),
                '10_25' => array (
                    'bottom' => 10,
                    'top' => 25,
                    'red1' => 32,
                    'green1' => 32,
                    'blue1' => 255,
                    'special' => 0
                ),
                '25_40' => array (
                    'bottom' => 25,
                    'top' => 40,
                    'red1' => 0,
                    'green1' => 192,
                    'blue1' => 255,
                    'special' => 0
                ),
                '40_55' => array (
                    'bottom' => 40,
                    'top' => 55,
                    'red1' => 0,
                    'green1' => 240,
                    'blue1' => 0,
                    'special' => 0
                ),
                '55_70' => array (
                    'bottom' => 55,
                    'top' => 70,
                    'red1' => 240,
                    'green1' => 240,
                    'blue1' => 0,
                    'special' => 0
                ),
                '70_85' => array (
                    'bottom' => 70,
                    'top' => 85,
                    'red1' => 255,
                    'green1' => 192,
                    'blue1' => 0,
                    'special' => 0
                ),
                '85_100' => array (
                    'bottom' => 85,
                    'top' => 100,
                    'red1' => 255,
                    'green1' => 0,
                    'blue1' => 0,
                    'special' => 0
                )
            );

            foreach ($defaults as $key => $def) {
                $this->colours['DEFAULT'][$key] = $def;
                $this->colours['DEFAULT'][$key]['key'] = $key;
                $scalesseen++;
            }
            // we have a 0-0 line now, so we need to hide that.
            $this->add_hint("key_hidezero_DEFAULT", 1);
        } else {
            debug("Already have $scalesseen scales, no defaults added.\n");
        }

        $this->numscales['DEFAULT'] = $scalesseen;
        $this->configfile = "$filename";

        if ($this->has_overlibs && $this->htmlstyle == 'static') {
            warn(
                "OVERLIBGRAPH is used, but HTMLSTYLE is static. This is probably wrong. [WMWARN41]\n");
        }

        debug("Building cache of z-layers and finalising bandwidth.\n");

        $allitems = array ();

        foreach ($this->nodes as $node) {
            $allitems[] = $node;
        }

        foreach ($this->links as $link) {
            $allitems[] = $link;
        }

        foreach ($allitems as $ky => $vl) {
            $item = &$allitems[$ky];
            $z = $item->zorder;

            if (!isset($this->seen_zlayers[$z]) || !is_array($this->seen_zlayers[$z])) {
                $this->seen_zlayers[$z] = array ();
            }
            array_push($this->seen_zlayers[$z], $item);

            // while we're looping through, let's set the real bandwidths
            if ($item->my_type() === 'LINK') {
                $this->links[$item->name]->max_bandwidth_in =
                    unformat_number($item->max_bandwidth_in_cfg, $this->kilo);
                $this->links[$item->name]->max_bandwidth_out =
                    unformat_number($item->max_bandwidth_out_cfg, $this->kilo);
            } elseif ($item->my_type() === 'NODE') {
                $this->nodes[$item->name]->max_bandwidth_in =
                    unformat_number($item->max_bandwidth_in_cfg, $this->kilo);
                $this->nodes[$item->name]->max_bandwidth_out =
                    unformat_number($item->max_bandwidth_out_cfg, $this->kilo);
            } else {
                warn("Internal bug - found an item of type: " . $item->my_type() . "\n");
            }

            debug(sprintf("   Setting bandwidth on " . $item->my_type()
                . " $item->name (%s -> %d bps, %s -> %d bps, KILO = %d)\n",
                $item->max_bandwidth_in_cfg, $item->max_bandwidth_in,
                $item->max_bandwidth_out_cfg, $item->max_bandwidth_out, $this->kilo));
        }

        debug("Found " . sizeof($this->seen_zlayers)
            . " z-layers including builtins (0,100).\n");

        // calculate any relative positions here - that way, nothing else
        // really needs to know about them

        debug("Resolving relative positions for NODEs...\n");
        // safety net for cyclic dependencies
        $i = 100;

        do {
            $skipped = 0;
            $set = 0;

            foreach ($this->nodes as $node) {
                if (($node->relative_to != '') && (!$node->relative_resolved)) {
                    debug("Resolving relative position for NODE " . $node->name . " to "
                        . $node->relative_to . "\n");

                    if (array_key_exists($node->relative_to, $this->nodes)) {

// check if we are relative to another node which is in turn relative to something
// we need to resolve that one before we can resolve this one!
                        if (($this->nodes[$node->relative_to]->relative_to != '')
                            && (!$this->nodes[$node->relative_to]->relative_resolved)) {
                            debug(
                                "Skipping unresolved relative_to. Let's hope it's not a circular one\n");
                            $skipped++;
                        } else {
                            $rx = $this->nodes[$node->relative_to]->x;
                            $ry = $this->nodes[$node->relative_to]->y;

                            if ($node->polar) {
                                // treat this one as a POLAR relative coordinate.
                                // - draw rings around a node!
                                $angle = $node->x;
                                $distance = $node->y;
                                $newpos_x = $rx + $distance * sin(deg2rad($angle));
                                $newpos_y = $ry - $distance * cos(deg2rad($angle));
                                debug("->$newpos_x,$newpos_y\n");
                                $this->nodes[$node->name]->x = $newpos_x;
                                $this->nodes[$node->name]->y = $newpos_y;
                                $this->nodes[$node->name]->relative_resolved = true;
                                $set++;
                            } else {

                                // save the relative coords, so that WriteConfig can work
                                // resolve the relative stuff

                                $newpos_x = $rx + $this->nodes[$node->name]->x;
                                $newpos_y = $ry + $this->nodes[$node->name]->y;
                                debug("->$newpos_x,$newpos_y\n");
                                $this->nodes[$node->name]->x = $newpos_x;
                                $this->nodes[$node->name]->y = $newpos_y;
                                $this->nodes[$node->name]->relative_resolved = true;
                                $set++;
                            }
                        }
                    } else {
                        warn("NODE " . $node->name
                            . " has a relative position to an unknown node! [WMWARN10]\n");
                    }
                }
            }
            debug(
                "Relative Positions Cycle $i - set $set and Skipped $skipped for unresolved dependencies\n");
            $i--;
        } while (($set > 0) && ($i != 0));

        if ($skipped > 0) {
            warn(
                "There are Circular dependencies in relative POSITION lines for $skipped nodes. [WMWARN11]\n");
        }

        debug("-----------------------------------\n");

        debug("Running Pre-Processing Plugins...\n");

        foreach ($this->preprocessclasses as $pre_class) {
            debug("Running $pre_class" . "->run()\n");
            $this->plugins['pre'][$pre_class]->run($this);
        }
        debug("Finished Pre-Processing Plugins...\n");

        return (true);
    }

    function ReadConfig_Commit(&$curobj)
    {
        if (is_null($curobj)) {
            return;
        }

        $last_seen = $curobj->my_type();

        // first, save the previous item, before starting work on the new one
        if ($last_seen == "NODE") {
            $this->nodes[$curobj->name] = $curobj;
            debug("Saving Node: " . $curobj->name . "\n");

            if ($curobj->template == 'DEFAULT') {
                $this->node_template_tree["DEFAULT"][] = $curobj->name;
            }
        }

        if ($last_seen == "LINK") {
            if (isset($curobj->a) && isset($curobj->b)) {
                $this->links[$curobj->name] = $curobj;
                debug("Saving Link: " . $curobj->name . "\n");
            } else {
                $this->links[$curobj->name] = $curobj;
                debug("Saving Template-Only Link: " . $curobj->name . "\n");
            }

            if ($curobj->template == 'DEFAULT') {
                $this->link_template_tree["DEFAULT"][] = $curobj->name;
            }
        }
    }

    function WriteConfig($filename)
    {
        global $WEATHERMAP_VERSION;

        $fd = fopen($filename, 'w');
        $output = '';

        if ($fd) {
            $output
                .= "# Automatically generated by php-weathermap v$WEATHERMAP_VERSION\n\n";

            if (count($this->fonts) > 0) {
                foreach ($this->fonts as $fontnumber => $font) {
                    if ($font->type == 'truetype')
                        $output .= sprintf("FONTDEFINE %d %s %d\n", $fontnumber,
                            $font->file, $font->size);

                    if ($font->type == 'gd')
                        $output .= sprintf("FONTDEFINE %d %s\n", $fontnumber,
                            $font->file);
                }

                $output .= "\n";
            }

            $basic_params = array (
                array (
                    'background',
                    'BACKGROUND',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'width',
                    'WIDTH',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'height',
                    'HEIGHT',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'htmlstyle',
                    'HTMLSTYLE',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'kilo',
                    'KILO',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'keyfont',
                    'KEYFONT',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'timefont',
                    'TIMEFONT',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'titlefont',
                    'TITLEFONT',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'title',
                    'TITLE',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'htmloutputfile',
                    'HTMLOUTPUTFILE',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'htmlstylesheet',
                    'HTMLSTYLESHEET',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'imageuri',
                    'IMAGEURI',
                    CONFIG_TYPE_LITERAL
                ),
                array (
                    'imageoutputfile',
                    'IMAGEOUTPUTFILE',
                    CONFIG_TYPE_LITERAL
                )
            );

            foreach ($basic_params as $param) {
                $field = $param[0];
                $keyword = $param[1];

                if ($this->$field != $this->inherit_fieldlist[$field]) {
                    if ($param[2] == CONFIG_TYPE_COLOR) {
                        $output .= "$keyword " . render_colour($this->$field) . "\n";
                    }

                    if ($param[2] == CONFIG_TYPE_LITERAL) {
                        $output .= "$keyword " . $this->$field . "\n";
                    }
                }
            }

            if (($this->timex != $this->inherit_fieldlist['timex'])
                || ($this->timey != $this->inherit_fieldlist['timey'])
                    || ($this->stamptext != $this->inherit_fieldlist['stamptext']))
                $output .= "TIMEPOS " . $this->timex . " " . $this->timey . " "
                    . $this->stamptext . "\n";

            if (($this->mintimex != $this->inherit_fieldlist['mintimex'])
                || ($this->mintimey != $this->inherit_fieldlist['mintimey'])
                    || ($this->minstamptext != $this->inherit_fieldlist['minstamptext']))
                $output .= "MINTIMEPOS " . $this->mintimex . " " . $this->mintimey . " "
                    . $this->minstamptext . "\n";

            if (($this->maxtimex != $this->inherit_fieldlist['maxtimex'])
                || ($this->maxtimey != $this->inherit_fieldlist['maxtimey'])
                    || ($this->maxstamptext != $this->inherit_fieldlist['maxstamptext']))
                $output .= "MAXTIMEPOS " . $this->maxtimex . " " . $this->maxtimey . " "
                    . $this->maxstamptext . "\n";

            if (($this->titlex != $this->inherit_fieldlist['titlex'])
                || ($this->titley != $this->inherit_fieldlist['titley']))
                $output .= "TITLEPOS " . $this->titlex . " " . $this->titley . "\n";

            $output .= "\n";

            foreach ($this->colours as $scalename => $colours) {
// not all keys will have keypos but if they do, then all three vars should be defined
                if ((isset($this->keyx[$scalename])) && (isset($this->keyy[$scalename]))
                    && (isset($this->keytext[$scalename])) && (($this->keytext[$scalename]
                    != $this->inherit_fieldlist['keytext'])
                    || ($this->keyx[$scalename] != $this->inherit_fieldlist['keyx'])
                        || ($this->keyy[$scalename]
                            != $this->inherit_fieldlist['keyy']))) {
// sometimes a scale exists but without defaults. A proper scale object would sort this out...
                    if ($this->keyx[$scalename] == '') {
                        $this->keyx[$scalename] = -1;
                    }

                    if ($this->keyy[$scalename] == '') {
                        $this->keyy[$scalename] = -1;
                    }

                    $output .= "KEYPOS " . $scalename . " " . $this->keyx[$scalename]
                        . " " . $this->keyy[$scalename] . " " . $this->keytext[$scalename]
                        . "\n";
                }

                if ((isset($this->keystyle[$scalename])) && ($this->keystyle[$scalename]
                    != $this->inherit_fieldlist['keystyle']['DEFAULT'])) {
                    $extra = '';

                    if ((isset($this->keysize[$scalename])) && ($this->keysize[$scalename]
                        != $this->inherit_fieldlist['keysize']['DEFAULT'])) {
                        $extra = " " . $this->keysize[$scalename];
                    }
                    $output .= "KEYSTYLE  " . $scalename . " "
                        . $this->keystyle[$scalename] . $extra . "\n";
                }
                $locale = localeconv();
                $decimal_point = $locale['decimal_point'];

                foreach ($colours as $k => $colour) {
                    if (!isset($colour['special']) || !$colour['special']) {
                        $top = rtrim(rtrim(sprintf("%f", $colour['top']), "0"),
                            $decimal_point);
                        $bottom = rtrim(rtrim(sprintf("%f", $colour['bottom']), "0"),
                            $decimal_point);

                        if ($bottom > 1000) {
                            $bottom = nice_bandwidth($colour['bottom'], $this->kilo);
                        }

                        if ($top > 1000) {
                            $top = nice_bandwidth($colour['top'], $this->kilo);
                        }

                        $tag = (isset($colour['tag']) ? $colour['tag'] : '');

                        if (($colour['red1'] == -1) && ($colour['green1'] == -1)
                            && ($colour['blue1'] == -1)) {
                            $output .= sprintf("SCALE %s %-4s %-4s   none   %s\n",
                                $scalename, $bottom, $top, $tag);
                        } elseif (!isset($colour['red2'])) {
                            $output .= sprintf("SCALE %s %-4s %-4s %3d %3d %3d  %s\n",
                                $scalename, $bottom, $top, $colour['red1'],
                                $colour['green1'], $colour['blue1'], $tag);
                        } else {
                            $output .= sprintf(
                                "SCALE %s %-4s %-4s %3d %3d %3d   %3d %3d %3d    %s\n",
                                $scalename, $bottom, $top, $colour['red1'],
                                $colour['green1'], $colour['blue1'], $colour['red2'],
                                $colour['green2'], $colour['blue2'], $tag);
                        }
                    } else {
                        $output .= sprintf("%sCOLOR %d %d %d\n", $k, $colour['red1'],
                            $colour['green1'], $colour['blue1']);
                    }
                }
                $output .= "\n";
            }

            foreach ($this->hints as $hintname => $hint) {
                $output .= "SET $hintname $hint\n";
            }

            // this doesn't really work right, but let's try anyway
            if ($this->has_includes) {
                $output .= "\n# Included files\n";

                foreach ($this->included_files as $ifile) {
                    $output .= "INCLUDE $ifile\n";
                }
            }

            $output .= "\n# End of global section\n\n";

            fwrite($fd, $output);

            foreach (array (
                "template",
                "normal"
            ) as $which) {
                if ($which == "template") {
                    fwrite($fd, "\n# TEMPLATE-only NODEs:\n");
                }

                if ($which == "normal") {
                    fwrite($fd, "\n# regular NODEs:\n");
                }

                foreach ($this->nodes as $node) {
                    if (!preg_match("/^::\s/", $node->name)) {
                        if ($node->defined_in == $this->configfile) {
                            if ($which == "template" && $node->x === null) {
                                debug("TEMPLATE\n");
                                fwrite($fd, $node->WriteConfig());
                            }

                            if ($which == "normal" && $node->x !== null) {
                                fwrite($fd, $node->WriteConfig());
                            }
                        }
                    }
                }

                if ($which == "template") {
                    fwrite($fd, "\n# TEMPLATE-only LINKs:\n");
                }

                if ($which == "normal") {
                    fwrite($fd, "\n# regular LINKs:\n");
                }

                foreach ($this->links as $link) {
                    if (!preg_match("/^::\s/", $link->name)) {
                        if ($link->defined_in == $this->configfile) {
                            if ($which == "template" && $link->a === null) {
                                fwrite($fd, $link->WriteConfig());
                            }

                            if ($which == "normal" && $link->a !== null)
                                fwrite($fd, $link->WriteConfig());
                        }
                    }
                }
            }

            fwrite($fd, "\n\n# That's All Folks!\n");

            fclose($fd);
        } else {
            warn("Couldn't open config file $filename for writing");
            return (false);
        }

        return (true);
    }

    // pre-allocate colour slots for the colours used by the arrows
    // this way, it's the pretty icons that suffer if there aren't enough colours, and
    // not the actual useful data
    // we skip any gradient scales
    function AllocateScaleColours($im, $refname = 'gdref1')
    {
        # $colours=$this->colours['DEFAULT'];
        foreach ($this->colours as $scalename => $colours) {
            foreach ($colours as $key => $colour) {
                if ((!isset($this->colours[$scalename][$key]['red2']))
                    && (!isset($this->colours[$scalename][$key][$refname]))) {
                    $r = $colour['red1'];
                    $g = $colour['green1'];
                    $b = $colour['blue1'];
                    debug("AllocateScaleColours: $scalename/$refname $key ($r,$g,$b)\n");
                    $this->colours[$scalename][$key][$refname] =
                        myimagecolorallocate($im, $r, $g, $b);
                }
            }
        }
    }

    function DrawMap($filename = '', $thumbnailfile = '', $thumbnailmax = 250,
        $withnodes = true, $use_via_overlay = false, $use_rel_overlay = false)
    {
        debug("Trace: DrawMap()\n");
        // metadump("# start", true);
        $bgimage = null;

        if ($this->configfile != "") {
            $this->cachefile_version = crc32(file_get_contents($this->configfile));
        } else {
            $this->cachefile_version = crc32("........");
        }

        debug("Running Post-Processing Plugins...\n");

        foreach ($this->postprocessclasses as $post_class) {
            debug("Running $post_class" . "->run()\n");
            $this->plugins['post'][$post_class]->run($this);
        }
        debug("Finished Post-Processing Plugins...\n");

        debug("=====================================\n");
        debug("Start of Map Drawing\n");

        $this->datestamp = strftime($this->stamptext, time());

        // do the basic prep work
        if ($this->background != '') {
            if (is_readable($this->background)) {
                $bgimage = imagecreatefromfile($this->background);

                if (!$bgimage) {
                    warn(
                        "Failed to open background image.  One possible reason: Is your BACKGROUND really a PNG?\n");
                } else {
                    $this->width = imagesx($bgimage);
                    $this->height = imagesy($bgimage);
                }
            } else {
                warn(
                    "Your background image file could not be read. Check the filename, and permissions, for "
                    . $this->background . "\n");
            }
        }

        $image = imagecreatetruecolor($this->width, $this->height);

        if (!$image) {
            warn("Couldn't create output image in memory (" . $this->width . "x"
                . $this->height . ").");
        } else {
            ImageAlphaBlending($image, true);

            // by here, we should have a valid image handle

            // save this away, now
            $this->image = $image;

            $this->white = myimagecolorallocate($image, 255, 255, 255);
            $this->black = myimagecolorallocate($image, 0, 0, 0);
            $this->grey = myimagecolorallocate($image, 192, 192, 192);
            $this->selected =
                myimagecolorallocate($image, 255, 0, 0); // for selections in the editor

            $this->AllocateScaleColours($image);

// fill with background colour anyway, in case the background image failed to load
            imagefilledrectangle($image, 0, 0, $this->width, $this->height,
                $this->colours['DEFAULT']['BG']['gdref1']);

            if ($bgimage) {
                imagecopy($image, $bgimage, 0, 0, 0, 0, $this->width, $this->height);
                imagedestroy($bgimage);
            }

// Now it's time to draw a map

// do the node rendering stuff first, regardless of where they are actually drawn.
// this is so we can get the size of the nodes, which links will need if they use offsets
            foreach ($this->nodes as $node) {
                // don't try and draw template nodes
                debug("Pre-rendering " . $node->name . " to get bounding boxes.\n");

                // don't bother drawing if there's no position - it's a template
                if ( (!is_null($node->x) && (!is_null($node->y)))) {
                    $this->nodes[$node->name]->pre_render($image, $this);
                }
            }

            $all_layers = array_keys($this->seen_zlayers);
            sort($all_layers);

            foreach ($all_layers as $z) {
                $z_items = $this->seen_zlayers[$z];
                debug("Drawing layer " . $z . "\n");

                // all the map 'furniture' is fixed at z=1000
                if ($z === 1000) {
                    foreach ($this->colours as $scalename => $colours) {
                        debug("Drawing KEY for " . $scalename . " if necessary.\n");

                        if ((isset($this->numscales[$scalename]))
                            && (isset($this->keyx[$scalename]))
                                && ($this->keyx[$scalename] >= 0)
                                && ($this->keyy[$scalename] >= 0)) {
                            if ($this->keystyle[$scalename] == 'classic')
                                $this->DrawLegend_Classic($image, $scalename, false);

                            if ($this->keystyle[$scalename] == 'horizontal')
                                $this->DrawLegend_Horizontal($image, $scalename,
                                    $this->keysize[$scalename]);

                            if ($this->keystyle[$scalename] == 'vertical')
                                $this->DrawLegend_Vertical($image, $scalename,
                                    $this->keysize[$scalename]);

                            if ($this->keystyle[$scalename] == 'inverted')
                                $this->DrawLegend_Vertical($image, $scalename,
                                    $this->keysize[$scalename], true);

                            if ($this->keystyle[$scalename] == 'tags')
                                $this->DrawLegend_Classic($image, $scalename, true);
                        }
                    }

                    $this->DrawTimestamp($image, $this->timefont,
                        $this->colours['DEFAULT']['TIME']['gdref1']);

                    if (!is_null($this->min_data_time)) {
                        $this->DrawTimestamp($image, $this->timefont,
                            $this->colours['DEFAULT']['TIME']['gdref1'], "MIN");
                        $this->DrawTimestamp($image, $this->timefont,
                            $this->colours['DEFAULT']['TIME']['gdref1'], "MAX");
                    }
                    $this->DrawTitle($image, $this->titlefont,
                        $this->colours['DEFAULT']['TITLE']['gdref1']);
                }

                if (is_array($z_items)) {
                    foreach ($z_items as $it) {
                        if (strtolower(get_class($it)) == 'weathermaplink') {
// only draw LINKs if they have NODES defined (not templates)
// (also, check if the link still exists - if this is in the editor, it may have been deleted by now)
                            if (isset($this->links[$it->name]) && isset($it->a)
                                && isset($it->b)) {
                                debug("Drawing LINK " . $it->name . "\n");
                                $this->links[$it->name]->Draw($image, $this);
                            }
                        }

                        if (strtolower(get_class($it)) == 'weathermapnode') {
                            if ($withnodes) {
                                // don't try and draw template nodes
                                if (isset($this->nodes[$it->name]) && !is_null($it->x)) {
                                    debug("Drawing NODE " . $it->name . "\n");
                                    $this->nodes[$it->name]->NewDraw($image, $this);
                                    $ii = 0;

                                    foreach ($this->nodes[$it->
                                        name]->boundingboxes as $bbox) {
                                        $areaname = "NODE:N" . $it->id . ":" . $ii;
                                        $this->imap->addArea("Rectangle", $areaname, '',
                                            $bbox);
                                        $this->nodes[$it->name]->imap_areas[] = $areaname;

                                        debug("Adding imagemap area\n");
                                        $ii++;
                                    }
                                    debug('Added ' . $ii . " bounding boxes too\n");
                                }
                            }
                        }
                    }
                }
            }

            $overlay = myimagecolorallocate($image, 200, 0, 0);

            // for the editor, we can optionally overlay some other stuff
            if ($this->context == 'editor') {
                if ($use_rel_overlay) {
                    #		$overlay = myimagecolorallocate($image, 200, 0, 0);

                    // first, we can show relatively positioned NODEs
                    foreach ($this->nodes as $node) {
                        if ($node->relative_to != '') {
                            $rel_x = $this->nodes[$node->relative_to]->x;
                            $rel_y = $this->nodes[$node->relative_to]->y;
                            imagearc($image, $node->x, $node->y, 15, 15, 0, 360,
                                $overlay);
                            imagearc($image, $node->x, $node->y, 16, 16, 0, 360,
                                $overlay);

                            imageline($image, $node->x, $node->y, $rel_x, $rel_y,
                                $overlay);
                        }
                    }
                }

                if ($use_via_overlay) {
                    // then overlay VIAs, so they can be seen
                    foreach ($this->links as $link) {
                        foreach ($link->vialist as $via) {
                            if (isset($via[2])) {
                                $x = $this->nodes[$via[2]]->x + $via[0];
                                $y = $this->nodes[$via[2]]->y + $via[1];
                            } else {
                                $x = $via[0];
                                $y = $via[1];
                            }
                            imagearc($image, $x, $y, 10, 10, 0, 360, $overlay);
                            imagearc($image, $x, $y, 12, 12, 0, 360, $overlay);
                        }
                    }
                }
            }

            // Ready to output the results...

            if ($filename == 'null') {
// do nothing at all - we just wanted the HTML AREAs for the editor or HTML output
            }
            else {
                if ($filename == '') {
                    imagepng($image);
                } else {
                    $result = false;
                    $functions = true;

                    if (function_exists('imagejpeg') && preg_match("/\.jpg/i", $filename))
                        {
                        debug("Writing JPEG file to $filename\n");
                        $result = imagejpeg($image, $filename);
                    } elseif (function_exists('imagegif')
                        && preg_match("/\.gif/i", $filename)) {
                        debug("Writing GIF file to $filename\n");
                        $result = imagegif($image, $filename);
                    } elseif (function_exists('imagepng')
                        && preg_match("/\.png/i", $filename)) {
                        debug("Writing PNG file to $filename\n");
                        $result = imagepng($image, $filename);
                    } else {
                        warn(
                            "Failed to write map image. No function existed for the image format you requested. [WMWARN12]\n");
                        $functions = false;
                    }

                    if (($result == false) && ($functions == true)) {
                        if (file_exists($filename)) {
                            warn(
                                "Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN13]");
                        } else {
                            warn(
                                "Failed to create image file $filename - permissions of output directory are wrong? [WMWARN14]");
                        }
                    }
                }
            }

            if ($this->context == 'editor2') {
                $cachefile = $this->cachefolder . DIRECTORY_SEPARATOR
                    . dechex(crc32($this->configfile)) . "_bg."
                    . $this->cachefile_version . ".png";
                imagepng($image, $cachefile);
                $cacheuri =
                    $this->cachefolder . '/' . dechex(crc32($this->configfile)) . "_bg."
                    . $this->cachefile_version . ".png";
                $this->mapcache = $cacheuri;
            }

            if (function_exists('imagecopyresampled')) {
                // if one is specified, and we can, write a thumbnail too
                if ($thumbnailfile != '') {
                    $result = false;

                    if ($this->width > $this->height) {
                        $factor = ($thumbnailmax / $this->width);
                    } else {
                        $factor = ($thumbnailmax / $this->height);
                    }

                    $this->thumb_width = $this->width * $factor;
                    $this->thumb_height = $this->height * $factor;

                    $imagethumb =
                        imagecreatetruecolor($this->thumb_width, $this->thumb_height);
                    imagecopyresampled($imagethumb, $image, 0, 0, 0, 0,
                        $this->thumb_width, $this->thumb_height, $this->width,
                        $this->height);
                    $result = imagepng($imagethumb, $thumbnailfile);
                    imagedestroy($imagethumb);

                    if (($result == false)) {
                        if (file_exists($filename)) {
                            warn(
                                "Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN15]");
                        } else {
                            warn(
                                "Failed to create image file $filename - permissions of output directory are wrong? [WMWARN16]");
                        }
                    }
                }
            } else {
                warn(
                    "Skipping thumbnail creation, since we don't have the necessary function. [WMWARN17]");
            }
            imagedestroy($image);
        }
    }

    /**
     * So that memory is deallocated correctly, go through and remove all the
     * references to other objects. The PHP GC doesn't deal with circular
     * references, so this stops the memory usage from ballooning up.
     */
    function CleanUp()
    {
        $all_layers = array_keys($this->seen_zlayers);

        foreach ($all_layers as $z) {
            $this->seen_zlayers[$z] = null;
        }

        foreach ($this->links as $link) {
            $link->owner = null;
            $link->a = null;
            $link->b = null;

            unset($link);
        }

        foreach ($this->nodes as $node) {
            // destroy all the images we created, to prevent memory leaks

            if (isset($node->image)) {
                imagedestroy($node->image);
            }
            $node->owner = null;
            unset($node);
        }
    }

    function PreloadMapHTML()
    {
        debug("Trace: PreloadMapHTML()\n");


        // find the middle of the map
        $center_x = $this->width / 2;
        $center_y = $this->height / 2;

        // loop through everything. Figure out along the way if it's a node or a link
        $allitems = array (
            &$this->nodes,
            &$this->links
        );

        reset($allitems);

        while (list($kk, ) = each($allitems)) {
            unset($objects);
            $objects = &$allitems[$kk];

            reset($objects);

            while (list($k, ) = each($objects)) {
                unset($myobj);
                $myobj = &$objects[$k];

                $type = $myobj->my_type();
                $prefix = substr($type, 0, 1);

                $dirs = array ();

                if ($type == 'LINK') {
                    $dirs = array (
                        IN => array (
                            0,
                            2
                        ),
                        OUT => array (
                            1,
                            3
                        )
                    );
                }

                if ($type == 'NODE') {
                    $dirs = array (IN => array (
                        0,
                        1,
                        2,
                        3
                    ));
                }

                // check to see if any of the relevant things have a value
                $change = '';

                foreach ($dirs as $d => $parts) {
                    $change .= join('', $myobj->overliburl[$d]);
                    $change .= $myobj->notestext[$d];
                }

                if ($this->htmlstyle == "overlib") {

                    // skip all this if it's a template node
                    if ($type == 'LINK' && !isset($myobj->a->name)) {
                        $change = '';
                    }

                    if ($type == 'NODE' && !isset($myobj->x)) {
                        $change = '';
                    }

                    if ($change != '') {
                        //print "Something to be done.\n";
                        if ($type == 'NODE') {
                            $mid_x = $myobj->x;
                            $mid_y = $myobj->y;
                        }

                        if ($type == 'LINK') {
                            $a_x = $this->nodes[$myobj->a->name]->x;
                            $a_y = $this->nodes[$myobj->a->name]->y;

                            $b_x = $this->nodes[$myobj->b->name]->x;
                            $b_y = $this->nodes[$myobj->b->name]->y;

                            $mid_x = ($a_x + $b_x) / 2;
                            $mid_y = ($a_y + $b_y) / 2;
                        }
                        $left = "";
                        $above = "";
                        $img_extra = "";

                        if ($myobj->overlibwidth != 0) {
                            $left = "WIDTH," . $myobj->overlibwidth . ",";
                            $img_extra .= " WIDTH=$myobj->overlibwidth";

                            if ($mid_x > $center_x) {
                                $left .= "LEFT,";
                            }
                        }

                        if ($myobj->overlibheight != 0) {
                            $above = "HEIGHT," . $myobj->overlibheight . ",";
                            $img_extra .= " HEIGHT=$myobj->overlibheight";

                            if ($mid_y > $center_y) {
                                $above .= "ABOVE,";
                            }
                        }

                        foreach ($dirs as $dir => $parts) {
                            $caption = ($myobj->overlibcaption[$dir]
                                != '' ? $myobj->overlibcaption[$dir] : $myobj->name);
                            $caption = $this->ProcessString($caption, $myobj);

                            $overlibhtml = "onmouseover=\"return overlib('";

                            $n = 0;

                            if (sizeof($myobj->overliburl[$dir]) > 0) {
                                foreach ($myobj->overliburl[$dir] as $url) {
                                    if ($n > 0) {
                                        $overlibhtml .= '&lt;br /&gt;';
                                    }
                                    $overlibhtml .= "&lt;img $img_extra src="
                                        . $this->ProcessString($url, $myobj) . "&gt;";
                                    $n++;
                                }
                            }

                            # print "Added $n for $dir\n";
                            if (trim($myobj->notestext[$dir]) != '') {
                                # put in a linebreak if there was an image AND notes
                                if ($n > 0) {
                                    $overlibhtml .= '&lt;br /&gt;';
                                }
                                $note =
                                    $this->ProcessString($myobj->notestext[$dir], $myobj);
                                $note = htmlspecialchars($note, ENT_NOQUOTES);
                                $note = str_replace("'", "\\&apos;", $note);
                                $note = str_replace('"', "&quot;", $note);
                                $overlibhtml .= $note;
                            }
                            $overlibhtml .= "',DELAY,250,${left}${above}CAPTION,'"
                                . $caption . "');\"  onmouseout=\"return nd();\"";

                            foreach ($parts as $part) {
                                $areaname =
                                    $type . ":" . $prefix . $myobj->id . ":" . $part;

                                $this->imap->setProp("extrahtml", $overlibhtml,
                                    $areaname);
                            }
                        }
                    } // if change
                }     // overlib?

                // now look at inforurls
                foreach ($dirs as $dir => $parts) {
                    foreach ($parts as $part) {

                        $areaname = $type . ":" . $prefix . $myobj->id . ":" . $part;

                        if (($this->htmlstyle != 'editor')
                            && ($myobj->infourl[$dir] != '')) {
                            $this->imap->setProp("href",
                                $this->ProcessString($myobj->infourl[$dir], $myobj),
                                $areaname);
                        }
                    }
                }
            }
        }
    }

    function asJS()
    {
        $js = '';

        $js .= "var Links = new Array();\n";
        $js .= "var LinkIDs = new Array();\n";

        foreach ($this->links as $link) {
            $js .= $link->asJS();
        }

        $js .= "var Nodes = new Array();\n";
        $js .= "var NodeIDs = new Array();\n";

        foreach ($this->nodes as $node) {
            $js .= $node->asJS();
        }

        return $js;
    }

    function asJSON()
    {
        $json = '';

        $json .= "{ \n";

        $json .= "\"map\": {  \n";

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            $json .= js_escape($fld) . ": ";
            $json .= js_escape($this->$fld);
            $json .= ",\n";
        }
        $json = rtrim($json, ", \n");
        $json .= "\n},\n";

        $json .= "\"nodes\": {\n";
        $json .= $this->defaultnode->asJSON();

        foreach ($this->nodes as $node) {
            $json .= $node->asJSON();
        }
        $json = rtrim($json, ", \n");
        $json .= "\n},\n";

        $json .= "\"links\": {\n";
        $json .= $this->defaultlink->asJSON();

        foreach ($this->links as $link) {
            $json .= $link->asJSON();
        }
        $json = rtrim($json, ", \n");
        $json .= "\n},\n";

        $json .= "'imap': [\n";
        $json .= $this->imap->subJSON('NODE:');
        // should check if there WERE nodes...
        $json .= ",\n";
        $json .= $this->imap->subJSON('LINK:');
        $json .= "\n]\n";
        $json .= "\n";

        $json .= ", 'valid': 1}\n";

        return ($json);
    }

// This method MUST run *after* DrawMap. It relies on DrawMap to call the map-drawing bits
// which will populate the ImageMap with regions.
//
// imagemapname is a parameter, so we can stack up several maps in the Cacti plugin with their own imagemaps
    function MakeHTML($imagemapname = "weathermap_imap")
    {
        debug("Trace: MakeHTML()\n");
        // PreloadMapHTML fills in the ImageMap info, ready for the HTML to be created.
        $this->PreloadMapHTML();

        $html = '';

        $html
            .= '<div class="weathermapimage" style="margin-left: auto; margin-right: auto; width: '
            . $this->width . 'px;" >';

        if ($this->imageuri !== '') {
            $html .= sprintf(
                '<img id="wmapimage" src="%s" width="%d" height="%d" border="0" usemap="#%s"',
                $this->imageuri, $this->width, $this->height, $imagemapname);

            $html .= '/>';
        } else {
            $html .= sprintf(
                '<img id="wmapimage" src="%s" width="%d" height="%d" border="0" usemap="#%s"',
                $this->imagefile, $this->width, $this->height, $imagemapname);

            $html .= '/>';
        }
        $html .= '</div>';

        $html .= $this->SortedImagemap($imagemapname);

        return ($html);
    }

    function SortedImagemap($imagemapname)
    {
        $html = "\n" . '<map name="' . $imagemapname . '" id="' . $imagemapname . '">';

        $all_layers = array_keys($this->seen_zlayers);
        rsort($all_layers);

        debug("Starting to dump imagemap in reverse Z-order...\n");

        // this is not precisely efficient, but it'll get us going
        // XXX - get Imagemap to store Z order, or map items to store the imagemap
        foreach ($all_layers as $z) {
            debug("Writing HTML for layer %d\n", $z);
            $z_items = $this->seen_zlayers[$z];

            if (is_array($z_items)) {
                debug("   Found things for layer %d\n", $z);

                // at z=1000, the legends and timestamps live
                if ($z === 1000) {
                    debug("     Builtins fit here.\n");

                    if (1 == 0) {
                        $html .= $this->imap->subHTML('LEGEND:', true, ($this->context
                            != 'editor'));
                        $html .= $this->imap->subHTML('TIMESTAMP', true, ($this->context
                            != 'editor'));
                    }

                    foreach ($this->imap_areas as $areaname) {
// skip the linkless areas if we are in the editor - they're redundant
                        $html .= $this->imap->exactHTML($areaname, true, ($this->context
                            != 'editor'));
                    }
                }

                foreach ($z_items as $it) {
                    if ($it->name != 'DEFAULT' && $it->name != ':: DEFAULT ::') {
                        $name = '';

                        foreach ($it->imap_areas as $areaname) {
// skip the linkless areas if we are in the editor - they're redundant
                            $html .= $this->imap->exactHTML(
                                $areaname, true, ($this->context != 'editor'));
                        }

                        if (1 == 0) {
                            if (strtolower(get_class($it)) === 'weathermaplink') {
                                $name = 'LINK:L';
                            }

                            if (strtolower(get_class($it)) === 'weathermapnode') {
                                $name = 'NODE:N';
                            }
                            $name .= $it->id . ":";
                            debug("      Writing %s from imagemap\n", $name);

// skip the linkless areas if we are in the editor - they're redundant
                            $html .= $this->imap->subHTML($name, true, ($this->context
                                != 'editor'));
                        }
                    }
                }
            }
        }

        $html .= "</map>\n";

        return ($html);
    }

    // update any editor cache files.
    // if the config file is newer than the cache files, or $agelimit seconds have passed,
    // then write new stuff, otherwise just return.
    // ALWAYS deletes files in the cache folder older than $agelimit, also!
    function CacheUpdate($agelimit = 600)
    {
        global $weathermap_lazycounter;

        $cachefolder = $this->cachefolder;
        $configchanged = filemtime($this->configfile);
// make a unique, but safe, prefix for all cachefiles related to this map config
// we use CRC32 because it makes for a shorter filename, and collisions aren't the end of the world.
        $cacheprefix = dechex(crc32($this->configfile));

        debug("Comparing files in %s starting with %s, with date of %s\n", $cachefolder,
            $cacheprefix, $configchanged);

        $dh = opendir($cachefolder);

        if ($dh === false) {
            debug("Couldn't read cache folder.\n");
            return;
        }

        while ($file = readdir($dh)) {
            $realfile = $cachefolder . DIRECTORY_SEPARATOR . $file;

            if (is_file($realfile) && (preg_match('/^' . $cacheprefix . '/', $file))) {
                debug("Cache: checking %s\n", $realfile);

                if ((filemtime($realfile) < $configchanged)
                    || ((time() - filemtime($realfile)) > $agelimit)) {
                    debug("Cache: deleting %s\n", $realfile);
                    unlink($realfile);
                }
            }
        }
        closedir($dh);

        foreach ($this->nodes as $node) {
            if (isset($node->image)) {
                $nodefile = $cacheprefix . '_' . dechex(crc32($node->name)) . '.png';
                $this->nodes[$node->name]->cachefile = $nodefile;
                imagepng($node->image, $cachefolder . DIRECTORY_SEPARATOR . $nodefile);
            }
        }

        foreach ($this->keyimage as $key => $image) {
            $scalefile = $cacheprefix . '_scale_' . dechex(crc32($key)) . '.png';
            $this->keycache[$key] = $scalefile;
            imagepng($image, $cachefolder . DIRECTORY_SEPARATOR . $scalefile);
        }

        $json = '';
        $fd = fopen($cachefolder . DIRECTORY_SEPARATOR . $cacheprefix . '_map.json', 'w');

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            $json .= js_escape($fld) . ': ';
            $json .= js_escape($this->$fld);
            $json .= ",\n";
        }
        $json = rtrim($json, ", \n");
        fputs($fd, $json);
        fclose($fd);

        $json = '';
        $fd =
            fopen($cachefolder . DIRECTORY_SEPARATOR . $cacheprefix . '_tree.json', 'w');
        $id = 10; // first ID for user-supplied thing

        $json .= "{ id: 1, text: 'SCALEs'\n, children: [\n";

        foreach ($this->colours as $scalename => $colours) {
            $json .= "{ id: " . $id++ . ", text:" . js_escape($scalename)
                . ", leaf: true }, \n";
        }
        $json = rtrim($json, ", \n");
        $json .= "]},\n";

        $json .= "{ id: 2, text: 'FONTs',\n children: [\n";

        foreach ($this->fonts as $fontnumber => $font) {
            if ($font->type === 'truetype') {
                $json .= sprintf("{ id: %d, text: %s, leaf: true}, \n", $id++,
                    js_escape('Font ' . $fontnumber . ' (TT)'));
            }

            if ($font->type === 'gd') {
                $json .= sprintf("{ id: %d, text: %s, leaf: true}, \n", $id++,
                    js_escape('Font ' . $fontnumber . ' (GD)'));
            }
        }
        $json = rtrim($json, ", \n");
        $json .= "]},\n";

        $json .= "{ id: 3, text: 'NODEs',\n children: [\n";
        $json .= "{ id: " . $id++ . ", text: 'DEFAULT', children: [\n";

        $weathemap_lazycounter = $id;
        // pass the list of subordinate nodes to the recursive tree function
        $json .= $this->MakeTemplateTree($this->node_template_tree);
        $id = $weathermap_lazycounter;

        $json = rtrim($json, ", \n");
        $json .= "]} ]},\n";

        $json .= "{ id: 4, text: 'LINKs',\n children: [\n";
        $json .= "{ id: " . $id++ . ", text: 'DEFAULT', children: [\n";
        $weathemap_lazycounter = $id;
        $json .= $this->MakeTemplateTree($this->link_template_tree);
        $id = $weathermap_lazycounter;
        $json = rtrim($json, ", \n");
        $json .= "]} ]}\n";

        fputs($fd, '[' . $json . ']');
        fclose($fd);

        $fd =
            fopen($cachefolder . DIRECTORY_SEPARATOR . $cacheprefix . '_nodes.json', 'w');
        $json = '';

        foreach ($this->nodes as $node) {
            $json .= $node->asJSON(true);
        }
        $json = rtrim($json, ", \n");
        fputs($fd, $json);
        fclose($fd);

        $fd =
            fopen($cachefolder . DIRECTORY_SEPARATOR . $cacheprefix . '_nodes_lite.json',
                'w');
        $json = '';

        foreach ($this->nodes as $node) {
            $json .= $node->asJSON(false);
        }
        $json = rtrim($json, ", \n");
        fputs($fd, $json);
        fclose($fd);

        $fd =
            fopen($cachefolder . DIRECTORY_SEPARATOR . $cacheprefix . '_links.json', "w");
        $json = '';

        foreach ($this->links as $link) {
            $json .= $link->asJSON(true);
        }
        $json = rtrim($json, ", \n");
        fputs($fd, $json);
        fclose($fd);

        $fd =
            fopen($cachefolder . DIRECTORY_SEPARATOR . $cacheprefix . '_links_lite.json',
                'w');
        $json = '';

        foreach ($this->links as $link) {
            $json .= $link->asJSON(false);
        }
        $json = rtrim($json, ", \n");
        fputs($fd, $json);
        fclose($fd);

        $fd = fopen($cachefolder . DIRECTORY_SEPARATOR . $cacheprefix . '_imaphtml.json',
            'w');
        $json = $this->imap->subHTML('LINK:');
        fputs($fd, $json);
        fclose($fd);

        $fd =
            fopen($cachefolder . DIRECTORY_SEPARATOR . $cacheprefix . '_imap.json', 'w');
        $json = '';
        $nodejson = trim($this->imap->subJSON('NODE:'));

        if ($nodejson !== '') {
            $json .= $nodejson;
            // should check if there WERE nodes...
            $json .= ",\n";
        }
        $json .= $this->imap->subJSON('LINK:');
        fputs($fd, $json);
        fclose($fd);
    }

    function MakeTemplateTree(&$tree_list, $startpoint = 'DEFAULT')
    {
        global $weathermap_lazycounter;

        $output = '';

        foreach ($tree_list[$startpoint] as $subnode) {
            $output .= '{ id: ' . $weathermap_lazycounter++ . ', text: '
                . js_escape($subnode);

            if (isset($tree_list[$subnode])) {
                $output .= ", children: [ \n";
                $output .= $this->MakeTemplateTree($tree_list, $subnode);
                $output = rtrim($output, ", \n");
                $output .= "] \n";
            } else {
                $output .= ', leaf: true ';
            }
            $output .= "}, \n";
        }

        return ($output);
    }

    function DumpStats($filename = '')
    {
        $report = "Feature Statistics:\n\n";

        foreach ($this->usage_stats as $key => $val) {
            $report .= sprintf("%70s => %d\n", $key, $val);
        }

        if ($filename === '') {
            print $report;
        }
    }
	
	function SeedCoverage()
	{
		global $WM_config_keywords2;
		
		foreach ( array_keys($WM_config_keywords2) as $context) {
			foreach ( array_keys($WM_config_keywords2[$context]) as $keyword) {
				foreach ( $WM_config_keywords2[$context][$keyword] as $patternarray) {
					$key = sprintf("%s:%s:%s",$context, $keyword ,$patternarray[1]);
					$this->coverage[$key] = 0;
				}
			}
		}
	}
	
	function LoadCoverage($file)
	{
		$i=0;
		$fd = fopen($file,"r");
		while(! feof($fd)) {
			$line = fgets($fd,1024);
			$line = trim($line);
			list($val,$key) = explode("\t",$line);
			$this->coverage[$key] = $val;
			if($val > 0) { $i++; }
		}
		fclose($fd);
#		print "Loaded $i non-zero coverage stats.\n";
	}
	
	function SaveCoverage($file)
	{
		$i=0;
		$fd = fopen($file,"w+");
		foreach ($this->coverage as $key=>$val) {
			fputs($fd, "$val\t$key\n");
			if($val > 0) { $i++; }
		}
		fclose($fd);	
#		print "Saved $i non-zero coverage stats.\n";
	}


}
;
// vim:ts=4:sw=4:
?>