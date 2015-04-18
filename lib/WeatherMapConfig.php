<?php

/*
 * Refactoring of all the ReadConfig code out of the giant Weathermap class
 *
 * A ConfigReader holds state for parsing through a config file. Includes are done by
 * creating a new ConfigReader and passing it the state of the current one. Shared state
 * reduces the amount of junk passed to HANDLE_* functions.
 *
 */

class WeatherMapConfigReader
{

    private $lineCount = 0;
    private $currentObject = null;
    private $currentType = "GLOBAL";
    private $currentSource = "";
    private $mapObject = null;
    private $objectLineCount = 0;

    // new version of config_keywords
    // array of contexts, contains an array of keywords, contains a (short) list of regexps as now
    // this way, we don't scan the whole table, and we call preg_match a WHOLE lot less
    // there will be more lines in the array, but we'll be checking less of them

    private $configKeywords = array(
        'GLOBAL' => array(
            'INCLUDE' => array(
                array('GLOBAL', "/^\s*INCLUDE\s+(.*)\s*$/i", 'handleINCLUDE'),
            ),

            'SCALE' => array(
                array('GLOBAL', "/^\s*SCALE\s+([A-Za-z][A-Za-z0-9_]*\s+)?(\-?\d+\.?\d*[munKMGT]?)\s+(\-?\d+\.?\d*[munKMGT]?)\s+(?:(\d+)\s+(\d+)\s+(\d+)(?:\s+(\d+)\s+(\d+)\s+(\d+))?|(none))\s*(.*)$/i", 'handleSCALE'),
            ),
            'KEYSTYLE' => array(
                array('GLOBAL', '/^\s*KEYSTYLE\s+([A-Za-z][A-Za-z0-9_]+\s+)?(classic|horizontal|vertical|inverted|tags)\s?(\d+)?\s*$/i', 'handleKEYSTYLE'),
            ),
            'KEYPOS' => array(
                array('GLOBAL', '/^\s*KEYPOS\s+([A-Za-z][A-Za-z0-9_]*\s+)?(-?\d+)\s+(-?\d+)(.*)/i', 'handleKEYPOS'),
            ),
            'NODE' => array(
                array('GLOBAL', '/^\s*NODE\s+(\S+)\s*$/i', 'handleNODE'),
            ),
            'LINK' => array(
                array('GLOBAL', '/^\s*LINK\s+(\S+)\s*$/i', 'handleLINK'),
            ),
            'FONTDEFINE' => array(
                array('GLOBAL', '/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s+(\d+)\s*$/i', 'handleFONTDEFINE'),
                array('GLOBAL', '/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s*$/i', 'handleFONTDEFINE'),
            ),
            'KEYOUTLINECOLOR' => array(
                array(
                    'GLOBAL',
                    '/^KEYOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleGLOBALCOLOR'
                ),
                array(
                    'GLOBAL',
                    '/^KEYOUTLINECOLOR\s+(none)$/',
                    'handleGLOBALCOLOR'
                ),
            ),
            'KEYTEXTCOLOR' => array(array(
                'GLOBAL',
                '/^KEYTEXTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'handleGLOBALCOLOR'
            ),),
            'TITLECOLOR' => array(array(
                'GLOBAL',
                '/^TITLECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'handleGLOBALCOLOR'
            ),),
            'TIMECOLOR' => array(array(
                'GLOBAL',
                '/^TIMECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'handleGLOBALCOLOR'
            ),),
            'KEYBGCOLOR' => array(
                array(
                    'GLOBAL',
                    '/^KEYBGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleGLOBALCOLOR'
                ),
                array(
                    'GLOBAL',
                    '/^KEYBGCOLOR\s+(none)$/',
                    'handleGLOBALCOLOR'
                ),
            ),
            'BGCOLOR' => array(array(
                'GLOBAL',
                '/^BGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'handleGLOBALCOLOR'
            ),),
            'SET' => array(array(
                'GLOBAL',
                'SET',
                'handleSET'
            ),),
            'HTMLSTYLESHEET' => array(array(
                'GLOBAL',
                '/^HTMLSTYLESHEET\s+(.*)\s*$/i',
                array('htmlstylesheet' => 1)
            ),),
            'HTMLJSINCLUDE' => array(array(
                'GLOBAL',
                '/^HTMLJSINCLUDE\s+(.*)\s*$/i',
                array('jsincludes+' => 1)
            ),),
            'HTMLOUTPUTFILE' => array(array(
                'GLOBAL',
                '/^HTMLOUTPUTFILE\s+(.*)\s*$/i',
                array('htmloutputfile' => 1)
            ),),
            'BACKGROUND' => array(array(
                'GLOBAL',
                '/^BACKGROUND\s+(.*)\s*$/i',
                array('background' => 1)
            ),),
            'IMAGEOUTPUTFILE' => array(array(
                'GLOBAL',
                '/^IMAGEOUTPUTFILE\s+(.*)\s*$/i',
                array('imageoutputfile' => 1)
            ),),
            'DATAOUTPUTFILE' => array(array(
                'GLOBAL',
                '/^DATAOUTPUTFILE\s+(.*)\s*$/i',
                array('dataoutputfile' => 1)
            ),),
            'IMAGEURI' => array(array(
                'GLOBAL',
                '/^IMAGEURI\s+(.*)\s*$/i',
                array('imageuri' => 1)
            ),),
            'TITLE' => array(array(
                'GLOBAL',
                '/^TITLE\s+(.*)\s*$/i',
                array('title' => 1)
            ),),
            'HTMLSTYLE' => array(array(
                'GLOBAL',
                '/^HTMLSTYLE\s+(static|overlib)\s*$/i',
                array('htmlstyle' => 1)
            ),),
            'KILO' => array(array(
                'GLOBAL',
                '/^KILO\s+(\d+)\s*$/i',
                array('kilo' => 1)
            ),),
            'KEYFONT' => array(array(
                'GLOBAL',
                '/^KEYFONT\s+(\d+)\s*$/i',
                array('keyfont' => 1)
            ),),
            'TITLEFONT' => array(array(
                'GLOBAL',
                '/^TITLEFONT\s+(\d+)\s*$/i',
                array('titlefont' => 1)
            ),),
            'TIMEFONT' => array(array(
                'GLOBAL',
                '/^TIMEFONT\s+(\d+)\s*$/i',
                array('timefont' => 1)
            ),),
            'WIDTH' => array(array(
                'GLOBAL',
                '/^WIDTH\s+(\d+)\s*$/i',
                array('width' => 1)
            ),),
            'HEIGHT' => array(array(
                '(GLOBAL)',
                '/^HEIGHT\s+(\d+)\s*$/i',
                array('height' => 1)
            ),),
            'TITLEPOS' => array(
                array(
                    'GLOBAL',
                    '/^TITLEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                    array(
                        'titlex' => 1,
                        'titley' => 2
                    )
                ),
                array(
                    'GLOBAL',
                    '/^TITLEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',
                    array(
                        'titlex' => 1,
                        'titley' => 2,
                        'title' => 3
                    )
                ),
            ),
            'TIMEPOS' => array(
                array(
                    'GLOBAL',
                    '/^TIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                    array(
                        'timex' => 1,
                        'timey' => 2
                    )
                ),
                array(
                    'GLOBAL',
                    '/^TIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',
                    array(
                        'timex' => 1,
                        'timey' => 2,
                        'stamptext' => 3
                    )
                ),
            ),
            'MINTIMEPOS' => array(
                array(
                    'GLOBAL',
                    '/^MINTIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                    array(
                        'mintimex' => 1,
                        'mintimey' => 2
                    )
                ),
                array(
                    'GLOBAL',
                    '/^MINTIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',
                    array(
                        'mintimex' => 1,
                        'mintimey' => 2,
                        'minstamptext' => 3
                    )
                ),
            ),
            'MAXTIMEPOS' => array(
                array(
                    'GLOBAL',
                    '/^MAXTIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                    array(
                        'maxtimex' => 1,
                        'maxtimey' => 2
                    )
                ),
                array(
                    'GLOBAL',
                    '/^MAXTIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',
                    array(
                        'maxtimex' => 1,
                        'maxtimey' => 2,
                        'maxstamptext' => 3
                    )
                ),
            ),
        ), // end of global
        'NODE' => array(
            'INCLUDE' => array(
                array('NODE', "/^\s*INCLUDE\s+(.*)\s*$/i", 'handleINCLUDE'),
            ),

            'TEMPLATE' => array(
                array('NODE', "/^\s*TEMPLATE\s+(\S+)\s*$/i", 'handleTEMPLATE')
            ),
            'NODE' => array(
                array('NODE', '/^\s*NODE\s+(\S+)\s*$/i', 'handleNODE'),
            ),
            'LINK' => array(
                array('NODE', '/^\s*LINK\s+(\S+)\s*$/i', 'handleLINK'),
            ),
            'TARGET' => array(array(
                'NODE',
                'TARGET',
                'handleTARGET'
            ),),
            'SET' => array(array(
                'NODE',
                'SET',
                'handleSET'
            ),),
            'AICONOUTLINECOLOR' => array(
                array(
                    'NODE',
                    '/^AICONOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'NODE',
                    '/^AICONOUTLINECOLOR\s+(none)$/',
                    'handleCOLOR'
                ),
            ),
            'AICONFILLCOLOR' => array(
                array(
                    'NODE',
                    '/^AICONFILLCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'NODE',
                    '/^AICONFILLCOLOR\s+(copy|none)$/',
                    'handleCOLOR'
                ),
            ),
            'LABELOUTLINECOLOR' => array(
                array(
                    'NODE',
                    '/^LABELOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'NODE',
                    '/^LABELOUTLINECOLOR\s+(none)$/',
                    'handleCOLOR'
                ),
            ),
            'LABELBGCOLOR' => array(
                array(
                    'NODE',
                    '/^LABELBGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'NODE',
                    '/^LABELBGCOLOR\s+(none)$/',
                    'handleCOLOR'
                ),
            ),
            'LABELFONTCOLOR' => array(
                array(
                    'NODE',
                    '/^LABELFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'NODE',
                    '/^LABELFONTCOLOR\s+(contrast)$/',
                    'handleCOLOR'
                ),
            ),
            'LABELFONTSHADOWCOLOR' => array(array(
                'NODE',
                '/^LABELFONTSHADOWCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'handleCOLOR'
            ),),
            'NOTES' => array(array(
                'NODE',
                '/^NOTES\s+(.*)\s*$/i',
                array(
                    'notestext[IN]' => 1,
                    'notestext[OUT]' => 1
                )
            ),),
            'MAXVALUE' => array(
                array(
                    'NODE',
                    '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'max_bandwidth_in_cfg' => 2,
                        'max_bandwidth_out_cfg' => 3
                    )
                ),
                array(
                    'NODE',
                    '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'max_bandwidth_in_cfg' => 2,
                        'max_bandwidth_out_cfg' => 2
                    )
                ),
            ),
            'ORIGIN' => array(
                array('NODE',
                    '/^ORIGIN\s+(C|NE|SE|NW|SW|N|S|E|W)/i',
                    array("position_origin" => 1)
                )
            ),
            'POSITION' => array(
                array(
                    'NODE',
                    '/^POSITION\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i',
                    array(
                        'x' => 1,
                        'y' => 2
                    )
                ),
                array(
                    'NODE',
                    '/^POSITION\s+(\S+)\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i',
                    array(
                        'x' => 2,
                        'y' => 3,
                        'original_x' => 2,
                        'original_y' => 3,
                        'relative_to' => 1,
                        'relative_resolved' => false
                    )
                ),
                array(
                    'NODE',
                    '/^POSITION\s+(\S+)\s+([-+]?\d+)r(\d+)\s*$/i',
                    array(
                        'x' => 2,
                        'y' => 3,
                        'original_x' => 2,
                        'original_y' => 3,
                        'relative_to' => 1,
                        'polar' => true,
                        'relative_resolved' => false
                    )
                ),
                array( # named offset
                    'NODE',
                    '/^POSITION\s+([A-Za-z][A-Za-z0-9\-_]*):([A-Za-z][A-Za-z0-9_]*)$/i',
                    array(
                        'relative_to' => 1,
                        'relative_name' => 2,
                        'pos_named' => true,
                        'polar' => false,
                        'relative_resolved' => false
                    )
                ),
            ),
            'DEFINEOFFSET' => array(
                array(
                    'NODE',
                    '/^DEFINEOFFSET\s+([A-Za-z][A-Za-z0-9_]*)\s+([-+]?\d+)\s+([-+]?\d+)/i',
                    "handleDEFINEOFFSET"
                ),
            ),
            'INFOURL' => array(array(
                'NODE',
                '/^INFOURL\s+(.*)\s*$/i',
                array(
                    'infourl[IN]' => 1,
                    'infourl[OUT]' => 1
                )
            ),),
            'OVERLIBCAPTION' => array(array(
                'NODE',
                '/^OVERLIBCAPTION\s+(.*)\s*$/i',
                array(
                    'overlibcaption[IN]' => 1,
                    'overlibcaption[OUT]' => 1
                )
            ),),
            'ZORDER' => array(array(
                'NODE',
                '/^ZORDER\s+([-+]?\d+)\s*$/i',
                array('zorder' => 1)
            ),),
            'OVERLIBHEIGHT' => array(array(
                'NODE',
                '/^OVERLIBHEIGHT\s+(\d+)\s*$/i',
                array('overlibheight' => 1)
            ),),
            'OVERLIBWIDTH' => array(array(
                'NODE',
                '/^OVERLIBWIDTH\s+(\d+)\s*$/i',
                array('overlibwidth' => 1)
            ),),
            'LABELFONT' => array(array(
                'NODE',
                '/^LABELFONT\s+(\d+)\s*$/i',
                array('labelfont' => 1)
            ),),
            'LABELANGLE' => array(array(
                'NODE',
                '/^LABELANGLE\s+(0|90|180|270)\s*$/i',
                array('labelangle' => 1)
            ),),
            'ICON' => array(
                array(
                    'NODE',
                    '/^ICON\s+(\S+)\s*$/i',
                    array(
                        'iconfile' => 1,
                        'iconscalew' => '#0',
                        'iconscaleh' => '#0'
                    )
                ),
                array(
                    'NODE',
                    '/^ICON\s+(\S+)\s*$/i',
                    array('iconfile' => 1)
                ),
                array(
                    'NODE',
                    '/^ICON\s+(\d+)\s+(\d+)\s+(inpie|outpie|box|rbox|round|gauge|nink)\s*$/i',
                    array(
                        'iconfile' => 3,
                        'iconscalew' => 1,
                        'iconscaleh' => 2
                    )
                ),
                array(
                    'NODE',
                    '/^ICON\s+(\d+)\s+(\d+)\s+(\S+)\s*$/i',
                    array(
                        'iconfile' => 3,
                        'iconscalew' => 1,
                        'iconscaleh' => 2
                    )
                ),
            ),
            'LABEL' => array(
                array(
                    'NODE',
                    '/^LABEL\s*$/i',
                    array('label' => '')
                ), # special case for blank labels
                array(
                    'NODE',
                    '/^LABEL\s+(.*)\s*$/i',
                    array('label' => 1)
                ),
            ),
            'LABELOFFSET' => array(
                array(
                    'NODE',
                    '/^LABELOFFSET\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i',
                    array(
                        'labeloffsetx' => 1,
                        'labeloffsety' => 2
                    )
                ),
                array(
                    'NODE',
                    '/^LABELOFFSET\s+(C|NE|SE|NW|SW|N|S|E|W)\s*$/i',
                    array('labeloffset' => 1)
                ),
                array(
                    'NODE',
                    '/^LABELOFFSET\s+((C|NE|SE|NW|SW|N|S|E|W)\d+)\s*$/i',
                    array('labeloffset' => 1)
                ),
                array(
                    'NODE',
                    '/^LABELOFFSET\s+(-?\d+r\d+)\s*$/i',
                    array('labeloffset' => 1)
                ),
            ),
            'USESCALE' => array(
                array('NODE', '/^(USESCALE)\s+([A-Za-z][A-Za-z0-9_]*)(\s+(in|out))?(\s+(absolute|percent))?\s*$/i', "handleNODE_USESCALE"),
            ),
            'USEICONSCALE' => array(
                array('NODE', '/^(USEICONSCALE)\s+([A-Za-z][A-Za-z0-9_]*)(\s+(in|out))?(\s+(absolute|percent))?\s*$/i', "handleNODE_USESCALE"),
            ),
            'OVERLIBGRAPH' => array(
                array('NODE', '/^OVERLIBGRAPH\s+(.+)$/i', "handleOVERLIB")
            ),

        ), // end of node
        'LINK' => array(
            'INCLUDE' => array(
                array('LINK', "/^\s*INCLUDE\s+(.*)\s*$/i", 'handleINCLUDE'),
            ),

            'TEMPLATE' => array(
                array('LINK', "/^\s*TEMPLATE\s+(\S+)\s*$/i", 'handleTEMPLATE')
            ),
            'NODE' => array(
                array('LINK', '/^\s*NODE\s+(\S+)\s*$/i', 'handleNODE'),
            ),
            'LINK' => array(
                array('LINK', '/^\s*LINK\s+(\S+)\s*$/i', 'handleLINK'),
            ),
            'TARGET' => array(array(
                'LINK',
                'TARGET',
                'handleTARGET'
            ),),
            'SET' => array(array(
                'LINK',
                'SET',
                'handleSET'
            ),),
            'NODES' => array(array(
                'LINK',
                'NODES',
                'handleNODES'
            ),),
            'VIA' => array(array(
                'LINK',
                'VIA',
                'handleVIA'
            ),),
            'COMMENTFONTCOLOR' => array(
                array(
                    'LINK',
                    '/^COMMENTFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'LINK',
                    '/^COMMENTFONTCOLOR\s+(contrast)$/',
                    'handleCOLOR'
                ),
            ),
            'OUTLINECOLOR' => array(
                array(
                    'LINK',
                    '/^OUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'LINK',
                    '/^OUTLINECOLOR\s+(none)$/',
                    'handleCOLOR'
                ),
            ),
            'BWOUTLINECOLOR' => array(
                array(
                    'LINK',
                    '/^BWOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'LINK',
                    '/^BWOUTLINECOLOR\s+(none)$/',
                    'handleCOLOR'
                ),
            ),
            'BWBOXCOLOR' => array(
                array(
                    'LINK',
                    '/^BWBOXCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                    'handleCOLOR'
                ),
                array(
                    'LINK',
                    '/^BWBOXCOLOR\s+(none)$/',
                    'handleCOLOR'
                ),
            ),
            'BWFONTCOLOR' => array(array(
                'LINK',
                '/^BWFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/',
                'handleCOLOR'
            ),),
            'NOTES' => array(array(
                'LINK',
                '/^NOTES\s+(.*)\s*$/i',
                array(
                    'notestext[IN]' => 1,
                    'notestext[OUT]' => 1
                )
            ),),
            'MAXVALUE' => array(
                array(
                    'LINK',
                    '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'max_bandwidth_in_cfg' => 2,
                        'max_bandwidth_out_cfg' => 3
                    )
                ),
                array(
                    'LINK',
                    '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'max_bandwidth_in_cfg' => 2,
                        'max_bandwidth_out_cfg' => 2
                    )
                ),
            ),
            'WIDTH' => array(
                array(
                    'LINK',
                    '/^WIDTH\s+(\d+)\s*$/i',
                    array('width' => 1)
                ),
                array(
                    'LINK',
                    '/^WIDTH\s+(\d+\.\d+)\s*$/i',
                    array('width' => 1)
                ),
            ),
            'SPLITPOS' => array(array(
                'LINK',
                '/^SPLITPOS\s+(\d+)\s*$/i',
                array('splitpos' => 1)
            ),),
            'BWLABEL' => array(
                array(
                    'LINK',
                    '/^BWLABEL\s+bits\s*$/i',
                    array(
                        'labelstyle' => 'bits',
                        'bwlabelformats[IN]' => FMT_BITS_IN,
                        'bwlabelformats[OUT]' => FMT_BITS_OUT,
                    )
                ),
                array(
                    'LINK',
                    '/^BWLABEL\s+percent\s*$/i',
                    array(
                        'labelstyle' => 'percent',
                        'bwlabelformats[IN]' => FMT_PERC_IN,
                        'bwlabelformats[OUT]' => FMT_PERC_OUT,
                    )
                ),
                array(
                    'LINK',
                    '/^BWLABEL\s+unformatted\s*$/i',
                    array(
                        'labelstyle' => 'unformatted',
                        'bwlabelformats[IN]' => FMT_UNFORM_IN,
                        'bwlabelformats[OUT]' => FMT_UNFORM_OUT,
                    )
                ),
                array(
                    'LINK',
                    '/^BWLABEL\s+none\s*$/i',
                    array(
                        'labelstyle' => 'none',
                        'bwlabelformats[IN]' => '',
                        'bwlabelformats[OUT]' => '',
                    )
                ),
            ),
            'BWLABELPOS' => array(array(
                'LINK',
                '/^BWLABELPOS\s+(\d+)\s(\d+)\s*$/i',
                array(
                    'labeloffset_in' => 1,
                    'labeloffset_out' => 2
                )
            ),),
            'COMMENTPOS' => array(array(
                'LINK',
                '/^COMMENTPOS\s+(\d+)\s(\d+)\s*$/i',
                array(
                    'commentoffset_in' => 1,
                    'commentoffset_out' => 2
                )
            ),),
            'DUPLEX' => array(array(
                'LINK',
                '/^DUPLEX\s+(full|half)\s*$/i',
                array('duplex' => 1)
            ),),
            'BWSTYLE' => array(array(
                'LINK',
                '/^BWSTYLE\s+(classic|angled)\s*$/i',
                array('labelboxstyle' => 1)
            ),),
            'LINKSTYLE' => array(array(
                'LINK',
                '/^LINKSTYLE\s+(twoway|oneway)\s*$/i',
                array('linkstyle' => 1)
            ),),
            'COMMENTSTYLE' => array(array(
                'LINK',
                '/^COMMENTSTYLE\s+(edge|center)\s*$/i',
                array('commentstyle' => 1)
            ),),
            'ARROWSTYLE' => array(array(
                'LINK',
                '/^ARROWSTYLE\s+(classic|compact)\s*$/i',
                array('arrowstyle' => 1)
            ),
                array('LINK', '/^\s*ARROWSTYLE\s+(\d+)\s+(\d+)\s*$/i', 'handleARROWSTYLE'),

                ),
            'VIASTYLE' => array(array(
                'LINK',
                '/^VIASTYLE\s+(curved|angled)\s*$/i',
                array('viastyle' => 1)
            ),),
            'INCOMMENT' => array(array(
                'LINK',
                '/^INCOMMENT\s+(.*)\s*$/i',
                array('comments[IN]' => 1)
            ),),
            'OUTCOMMENT' => array(array(
                'LINK',
                '/^OUTCOMMENT\s+(.*)\s*$/i',
                array('comments[OUT]' => 1)
            ),),

            'OVERLIBGRAPH' => array(
                array('LINK', '/^OVERLIBGRAPH\s+(.+)$/i', "handleOVERLIB")
            ),
            'INOVERLIBGRAPH' => array(
                array('LINK', '/^INOVERLIBGRAPH\s+(.+)$/i', "handleOVERLIB")
            ),
            'OUTOVERLIBGRAPH' => array(
                array('LINK', '/^OUTOVERLIBGRAPH\s+(.+)$/i', "handleOVERLIB")
            ),

            'USESCALE' => array(
                array(
                    'LINK',
                    '/^USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s*$/i',
                    array('usescale' => 1)
                ),
                array(
                    'LINK',
                    '/^USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s+(absolute|percent)\s*$/i',
                    array(
                        'usescale' => 1,
                        'scaletype' => 2
                    )
                ),
            ),
            'BWFONT' => array(array(
                'LINK',
                '/^BWFONT\s+(\d+)\s*$/i',
                array('bwfont' => 1)
            ),),
            'COMMENTFONT' => array(array(
                'LINK',
                '/^COMMENTFONT\s+(\d+)\s*$/i',
                array('commentfont' => 1)
            ),),
            'BANDWIDTH' => array(
                array(
                    'LINK',
                    '/^(BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'max_bandwidth_in_cfg' => 2,
                        'max_bandwidth_out_cfg' => 3
                    )
                ),
                array(
                    'LINK',
                    '/^(BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'max_bandwidth_in_cfg' => 2,
                        'max_bandwidth_out_cfg' => 2
                    )
                ),
            ),
            'OUTBWFORMAT' => array(array(
                'LINK',
                '/^OUTBWFORMAT\s+(.*)\s*$/i',
                array(
                    'bwlabelformats[OUT]' => 1,
                    'labelstyle' => '--'
                )
            ),),
            'INBWFORMAT' => array(array(
                'LINK',
                '/^INBWFORMAT\s+(.*)\s*$/i',
                array(
                    'bwlabelformats[IN]' => 1,
                    'labelstyle' => '--'
                )
            ),),
            'INNOTES' => array(array(
                'LINK',
                '/^INNOTES\s+(.*)\s*$/i',
                array('notestext[IN]' => 1)
            ),),
            'OUTNOTES' => array(array(
                'LINK',
                '/^OUTNOTES\s+(.*)\s*$/i',
                array('notestext[OUT]' => 1)
            ),),
            'INFOURL' => array(array(
                'LINK',
                '/^INFOURL\s+(.*)\s*$/i',
                array(
                    'infourl[IN]' => 1,
                    'infourl[OUT]' => 1
                )
            ),),
            'ININFOURL' => array(array(
                'LINK',
                '/^ININFOURL\s+(.*)\s*$/i',
                array('infourl[IN]' => 1)
            ),),
            'OUTINFOURL' => array(array(
                'LINK',
                '/^OUTINFOURL\s+(.*)\s*$/i',
                array('infourl[OUT]' => 1)
            ),),
            'OVERLIBCAPTION' => array(array(
                'LINK',
                '/^OVERLIBCAPTION\s+(.*)\s*$/i',
                array(
                    'overlibcaption[IN]' => 1,
                    'overlibcaption[OUT]' => 1
                )
            ),),
            'INOVERLIBCAPTION' => array(array(
                'LINK',
                '/^INOVERLIBCAPTION\s+(.*)\s*$/i',
                array('overlibcaption[IN]' => 1)
            ),),
            'OUTOVERLIBCAPTION' => array(array(
                'LINK',
                '/^OUTOVERLIBCAPTION\s+(.*)\s*$/i',
                array('overlibcaption[OUT]' => 1)
            ),),
            'ZORDER' => array(array(
                'LINK',
                '/^ZORDER\s+([-+]?\d+)\s*$/i',
                array('zorder' => 1)
            ),),
            'OVERLIBWIDTH' => array(array(
                'LINK',
                '/^OVERLIBWIDTH\s+(\d+)\s*$/i',
                array('overlibwidth' => 1)
            ),),
            'OVERLIBHEIGHT' => array(array(
                'LINK',
                '/^OVERLIBHEIGHT\s+(\d+)\s*$/i',
                array('overlibheight' => 1)
            ),),
        ) // end of link
    );

    public function Init(&$map, $type="GLOBAL", $object=null)
    {
        $this->mapObject = $map;
        $this->currentType = $type;
        if ($type == "GLOBAL") {
            $this->currentObject = $map;
        } else {
            $this->currentObject = $object;
        }
    }


    // parseString is based on code from:
    // http://www.webscriptexpert.com/Php/Space-Separated%20Tag%20Parser/
    private function parseString($input)
    {
        $output = array (); // Array of Output
        $cPhraseQuote = null; // Record of the quote that opened the current phrase
        $sPhrase = null; // Temp storage for the current phrase we are building

        // Define some constants
        $sTokens = " \t"; // Space, Tab
        $sQuotes = "'\""; // Single and Double Quotes

        // Start the State Machine
        do {
            // Get the next token, which may be the first
            $sToken = isset($sToken) ? strtok($sTokens) : strtok($input, $sTokens);

            // Are there more tokens?
            if ($sToken === false) {
                // Ensure that the last phrase is marked as ended
                $cPhraseQuote = null;
            } else {
                // Are we within a phrase or not?
                if ($cPhraseQuote !== null) {
                    // Will the current token end the phrase?
                    if (substr($sToken, - 1, 1) === $cPhraseQuote) {
                        // Trim the last character and add to the current phrase, with a single leading space if necessary
                        if (strlen($sToken) > 1) {
                            $sPhrase .= ((strlen($sPhrase) > 0) ? ' ' : null) . substr($sToken, 0, - 1);
                        }
                        $cPhraseQuote = null;
                    } else {
                        // If not, add the token to the phrase, with a single leading space if necessary
                        $sPhrase .= ((strlen($sPhrase) > 0) ? ' ' : null) . $sToken;
                    }
                } else {
                    // Will the current token start a phrase?
                    if (strpos($sQuotes, $sToken [0]) !== false) {
                        // Will the current token end the phrase?
                        if ((strlen($sToken) > 1) && ($sToken [0] === substr($sToken, - 1, 1))) {
                            // The current token begins AND ends the phrase, trim the quotes
                            $sPhrase = substr($sToken, 1, - 1);
                        } else {
                            // Remove the leading quote
                            $sPhrase = substr($sToken, 1);
                            $cPhraseQuote = $sToken[0];
                        }
                    } else {
                        $sPhrase = $sToken;
                    }
                }
            }

            // If, at this point, we are not within a phrase, the prepared phrase is complete and can be added to the array
            if (($cPhraseQuote === null) && ($sPhrase != null)) {
                $output [] = $sPhrase;
                $sPhrase = null;
            }
        } while ($sToken !== false); // Stop when we receive false from strtok()

        return $output;
    }


    private function handleINCLUDE($fullcommand, $args, $matches)
    {
        $filename = $matches[1];

        if (file_exists($filename)) {
            wm_debug("Including '{$filename}'\n");

            if (in_array($filename, $this->mapObject->included_files)) {
                wm_warn("Attempt to include '$filename' twice! Skipping it.\n");
                return (false);
            } else {
                $this->mapObject->included_files[] = $filename;
                $this->mapObject->has_includes = true;
            }

            $reader = new WeatherMapConfigReader();
            $reader->Init($this->mapObject);
            $reader->readConfigFile($matches[1]);

            $this->currentType = "GLOBAL";
            $this->currentObject = $this->mapObject;

            return true;
        } else {
            wm_warn("INCLUDE File '{$matches[1]}' not found!\n");
            return false;
        }
    }

    private function handleTEMPLATE($fullcommand, $args, $matches)
    {
        $templateName = $matches[1];

        if (($this->currentType == 'NODE' && isset($this->mapObject->nodes[$templateName]))
            || ($this->currentType == 'LINK' && isset($this->mapObject->links[$templateName]))
        ) {
            $this->currentObject->setTemplate($matches[1], $this->mapObject);

            if ($this->objectLineCount > 1) {
                wm_warn("line $this->lineCount: TEMPLATE is not first line of object. Some data may be lost. [WMWARN39]\n");
            }
        } else {
            wm_warn("line $this->lineCount: $last_seen TEMPLATE '$templateName' doesn't exist! (if it does exist, check it's defined first) [WMWARN40]\n");
        }

        return true;
    }

    // TODO: refactor this - it doesn't need to be one big handler anymore (multiple regexps for different styles?)
    private function handleSCALE($fullcommand, $args, $matches)
    {
        // The default scale name is DEFAULT
        if ($matches[1] == '') {
            $matches[1] = 'DEFAULT';
        } else {
            $matches[1] = trim($matches[1]);
        }

        if (isset($this->mapObject->scales[$matches[1]])) {
            $newscale = $this->mapObject->scales[$matches[1]];
            // wm_debug("Found.");
        } else {
            $this->mapObject->scales[$matches[1]] = new WeatherMapScale($matches[1], $this->mapObject);
            $newscale = $this->mapObject->scales[$matches[1]];
            //  wm_debug("Created.");
        }

        $key = $matches[2] . '_' . $matches[3];
        $tag = $matches[11];

        $colour1 = null;
        $colour2 = null;

        $bottom = wmInterpretNumberWithMetricPrefix($matches[2], $this->mapObject->kilo);
        $top = wmInterpretNumberWithMetricPrefix($matches[3], $this->mapObject->kilo);

        $this->mapObject->colours[$matches[1]][$key]['key'] = $key;
        $this->mapObject->colours[$matches[1]][$key]['tag'] = $tag;

        $this->mapObject->colours[$matches[1]][$key]['bottom'] = wmInterpretNumberWithMetricPrefix($matches[2], $this->mapObject->kilo);
        $this->mapObject->colours[$matches[1]][$key]['top'] = wmInterpretNumberWithMetricPrefix($matches[3], $this->mapObject->kilo);

        $this->mapObject->colours[$matches[1]][$key]['special'] = 0;

        if (isset($matches[10]) && $matches[10] == 'none') {
            $this->mapObject->colours[$matches[1]][$key]['red1'] = -1;
            $this->mapObject->colours[$matches[1]][$key]['green1'] = -1;
            $this->mapObject->colours[$matches[1]][$key]['blue1'] = -1;
            $this->mapObject->colours[$matches[1]][$key]['c1'] = new WMColour('none');

            $colour1 = new WMColour("none");

        } else {
            $this->mapObject->colours[$matches[1]][$key]['red1'] = (int)($matches[4]);
            $this->mapObject->colours[$matches[1]][$key]['green1'] = (int)($matches[5]);
            $this->mapObject->colours[$matches[1]][$key]['blue1'] = (int)($matches[6]);
            $this->mapObject->colours[$matches[1]][$key]['c1'] = new WMColour((int)$matches[4], (int)$matches[5], (int)$matches[6]);

            $colour1 = new WMColour((int)($matches[4]), (int)($matches[5]), (int)($matches[6]));
            $colour2 = $colour1;
        }

        // this is the second colour, if there is one
        if (isset($matches[7]) && $matches[7] != '') {
            $this->mapObject->colours[$matches[1]][$key]['red2'] = (int)($matches[7]);
            $this->mapObject->colours[$matches[1]][$key]['green2'] = (int)($matches[8]);
            $this->mapObject->colours[$matches[1]][$key]['blue2'] = (int)($matches[9]);
            $this->mapObject->colours[$matches[1]][$key]['c2'] = new WMColour((int)$matches[7], (int)$matches[8], (int)$matches[9]);

            $colour2 = new WMColour((int)($matches[7]), (int)($matches[8]), (int)($matches[9]));
        }

        $newscale->AddSpan($bottom, $top, $colour1, $colour2, $tag);

        if (!isset($this->mapObject->numscales[$matches[1]])) {
            $this->mapObject->numscales[$matches[1]] = 1;
        } else {
            $this->mapObject->numscales[$matches[1]]++;
        }

        // we count if we've seen any default scale, otherwise, we have to add
        // one at the end.
        if ($matches[1] == 'DEFAULT') {
            $this->mapObject->scalesseen++;
        }

        return true;
    }

    private function handleKEYSTYLE($fullcommand, $args, $matches)
    {
        $whichKey = trim($matches[1]);

        if ($whichKey == '') {
            $whichKey = 'DEFAULT';
        }
        $this->mapObject->keystyle[$whichKey] = strtolower($matches[2]);

        if (isset($matches[3]) && $matches[3] != '') {
            $this->mapObject->keysize[$whichKey] = $matches[3];
        } else {
            $this->mapObject->keysize[$whichKey] = $this->mapObject->keysize['DEFAULT'];
        }

        return true;
    }

    private function handleKEYPOS($fullcommand, $args, $matches)
    {
        $whichKey = trim($matches[1]);

        if ($whichKey == '') {
            $whichKey = 'DEFAULT';
        }

        $this->mapObject->keyx[$whichKey] = $matches[2];
        $this->mapObject->keyy[$whichKey] = $matches[3];
        $extra = trim($matches[4]);

        if ($extra != '') {
            $this->mapObject->keytext[$whichKey] = $extra;
        }

        // it's possible to have keypos before the scale is defined.
        // this is to make it at least mostly consistent internally
        if (!isset($this->mapObject->keytext[$whichKey])) {
            $this->mapObject->keytext[$whichKey] = "DEFAULT TITLE";
        }

        if (!isset($this->mapObject->keystyle[$whichKey])) {
            $this->mapObject->keystyle[$whichKey] = "classic";
        }

        return true;
    }

    private function handleARROWSTYLE($fullcommand, $args, $matches)
    {

        $this->currentObject->arrowstyle = $matches[1] . ' ' . $matches[2];
        return true;
    }

    private function handleNODE($fullcommand, $args, $matches) {

        $this->commitItem();
        unset($this->currentObject);

        if ($args[1] == 'DEFAULT') {
            $this->currentObject = $this->mapObject->nodes['DEFAULT'];
            wm_debug("Loaded default NODE\n");

            if(sizeof($this->mapObject->nodes) > 2) {
                wm_warn("NODE DEFAULT is not the first NODE. Defaults will not apply to earlier NODEs. [WMWARN27]\n");
            }

        } else {
            if (isset($this->mapObject->nodes[$matches[1]])) {
                wm_warn("Duplicate node name " . $matches[1] . " at line $this->lineCount - only the last one defined is used. [WMWARN24]\n");
            }

            $this->currentObject = new WeatherMapNode();
            $this->currentObject->name = $matches[1];
            $this->currentObject->reset($this->mapObject);
            wm_debug("Created new NODE\n");
        }
        $this->objectLineCount = 0;
        $this->currentType = "NODE";
        $this->currentObject->configline = $this->lineCount;
        $this->currentObject->defined_in = $this->currentSource;

        return true;

    }

    private function handleLINK($fullcommand, $args, $matches) {
        $this->commitItem();
        unset($this->currentObject);

        if ($args[1] == 'DEFAULT') {
            $this->currentObject = $this->mapObject->links['DEFAULT'];
            wm_debug("Loaded default LINK\n");

            if(sizeof($this->mapObject->nodes) > 2) {
                wm_warn("LINK DEFAULT is not the first LINK. Defaults will not apply to earlier LINKs. [WMWARN26]\n");
            }

        } else {
            if (isset($this->mapObject->links[$matches[1]])) {
                wm_warn("Duplicate link name " . $matches[1] . " at line $this->lineCount - only the last one defined is used. [WMWARN25]\n");
            }
            $this->currentObject = new WeatherMapLink();
            $this->currentObject->name = $matches[1];
            $this->currentObject->reset($this->mapObject);
            wm_debug("Created new LINK\n");
        }
        $this->currentType = "LINK";
        $this->objectLineCount = 0;
        $this->currentObject->configline = $this->lineCount;
        $this->currentObject->defined_in = $this->currentSource;

        return true;
    }

    // *************************************
    // New ReadConfig special-case handlers

    private function handleDEFINEOFFSET($fullcommand, $args, $matches)
    {
        wm_debug("Defining a named offset: ". $matches[1]."\n");
        $this->currentObject->named_offsets[$matches[1]] = array(intval($matches[2]), intval($matches[3]));

        return true;
    }

    private function handleVIA($fullcommand, $args, $matches)
    {
        if (preg_match("/^\s*VIA\s+(\S+)\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $fullcommand, $matches)) {
            $this->currentObject->vialist[] = array($matches[2], $matches[3], $matches[1]);

            return true;
        }

        if (preg_match("/^\s*VIA\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $fullcommand, $matches)) {
            $this->currentObject->vialist[] = array($matches[1], $matches[2]);

            return true;
        }
        return false;
    }

    // TODO refactor this?
    private function handleNODES($fullcommand, $args, $matches)
    {
        $offset_dx = array();
        $offset_dy = array();
        $nodenames = array();
        $endoffset = array();

        if (preg_match("/^NODES\s+(\S+)\s+(\S+)\s*$/i", $fullcommand, $matches)) {

            $valid_nodes = 2;

            foreach (array(1, 2) as $i) {
                $endoffset[$i] = 'C';
                $nodenames[$i] = $matches[$i];
                $offset_dx[$i] = 0;
                $offset_dy[$i] = 0;

                // percentage of compass - must be first
                if (preg_match("/:(NE|SE|NW|SW|N|S|E|W|C)(\d+)$/i", $matches[$i], $submatches)) {
                    $endoffset[$i] = $submatches[1] . $submatches[2];
                    $nodenames[$i] = preg_replace("/:(NE|SE|NW|SW|N|S|E|W|C)\d+$/i", '', $matches[$i]);
                    $this->mapObject->need_size_precalc = true;
                }

                if (preg_match("/:(NE|SE|NW|SW|N|S|E|W|C)$/i", $matches[$i], $submatches)) {
                    $endoffset[$i] = $submatches[1];
                    $nodenames[$i] = preg_replace("/:(NE|SE|NW|SW|N|S|E|W|C)$/i", '', $matches[$i]);
                    $this->mapObject->need_size_precalc = true;
                }

                if (preg_match("/:(-?\d+r\d+)$/i", $matches[$i], $submatches)) {
                    $endoffset[$i] = $submatches[1];
                    $nodenames[$i] = preg_replace("/:(-?\d+r\d+)$/i", '', $matches[$i]);
                    $this->mapObject->need_size_precalc = true;
                }

                if (preg_match("/:([-+]?\d+):([-+]?\d+)$/i", $matches[$i], $submatches)) {
                    $xoff = $submatches[1];
                    $yoff = $submatches[2];
                    $endoffset[$i] = $xoff . ":" . $yoff;
                    $nodenames[$i] = preg_replace("/:$xoff:$yoff$/i", '', $matches[$i]);
                    $this->mapObject->need_size_precalc = true;
                }

                if (preg_match("/^([^:]+):([A-Za-z][A-Za-z0-9\-_]*)$/i", $matches[$i], $submatches)) {
                    $other_node = $submatches[1];
                    if (array_key_exists($submatches[2], $this->mapObject->nodes[$other_node]->named_offsets)) {
                        $named_offset = $submatches[2];
                        $nodenames[$i] = preg_replace("/:$named_offset$/i", '', $matches[$i]);

                        $endoffset[$i] = $named_offset;
                        $offset_dx[$i] = $this->mapObject->nodes[$other_node]->named_offsets[$named_offset][0];
                        $offset_dy[$i] = $this->mapObject->nodes[$other_node]->named_offsets[$named_offset][1];
                    }
                }

                if (!array_key_exists($nodenames[$i], $this->mapObject->nodes)) {
                    wm_warn("Unknown node '" . $nodenames[$i] . "' on line $this->lineCount of config\n");
                    $valid_nodes--;
                }
            }

            // TODO - really, this should kill the whole link, and reset for the next one
            // XXX this error case will not work in the handler function
            if ($valid_nodes == 2) {
                $this->currentObject->a = $this->mapObject->nodes[$nodenames[1]];
                $this->currentObject->b = $this->mapObject->nodes[$nodenames[2]];
                $this->currentObject->a_offset = $endoffset[1];
                $this->currentObject->b_offset = $endoffset[2];

                // lash-up to avoid having to pass loads of context to calc_offset
                // - named offsets require access to the internals of the node, when they are
                //   resolved. Luckily we can resolve them here, and skip that.
                if ($offset_dx[1] != 0 || $offset_dy[1] != 0) {
                    $this->currentObject->a_offset_dx = $offset_dx[1];
                    $this->currentObject->a_offset_dy = $offset_dy[1];
                    $this->currentObject->a_offset_resolved = true;
                }

                if ($offset_dx[2] != 0 || $offset_dy[2] != 0) {
                    $this->currentObject->b_offset_dx = $offset_dx[2];
                    $this->currentObject->b_offset_dy = $offset_dy[2];
                    $this->currentObject->b_offset_resolved = true;
                }
            } else {
                // this'll stop the current link being added
                $last_seen = "broken";
            }

            return true;
        }
        return false;
    }

    private function handleSET($fullcommand, $args, $matches)
    {
        global $weathermap_error_suppress;

        if (preg_match("/^SET\s+(\S+)\s+(.*)\s*$/i", $fullcommand, $matches)) {
            $this->currentObject->add_hint($matches[1], trim($matches[2]));

            if ($this->currentObject->my_type() == "MAP" && substr($matches[1], 0, 7) == 'nowarn_') {
                $weathermap_error_suppress[] = strtoupper(substr($matches[1], 7));
            }

            return true;
        }

        // allow setting a variable to ""
        if (preg_match("/^SET\s+(\S+)\s*$/i", $fullcommand, $matches)) {
            $this->currentObject->add_hint($matches[1], '');

            if ($this->currentObject->my_type() == "MAP" && substr($matches[1], 0, 7) == 'nowarn_') {
                $weathermap_error_suppress[] = strtoupper(substr($matches[1], 7));
            }

            return true;
        }

        return false;
    }

    private function handleGLOBALCOLOR($fullcommand, $args, $matches)
    {
        $key = str_replace("COLOR", "", strtoupper($args[0]));
        $val = strtolower($args[1]);

        $red = 0;
        $green = 0;
        $blue = 0;

        // this is a regular colour setting thing

        if (isset($args[2])) {
            $wmc = new WMColour($args[1], $args[2], $args[3]);
        } else {
            $wmc = new WMColour($val);
        }
        $this->mapObject->colourtable[$key] = $wmc;

        // below here is the old way

        // this is a regular colour setting thing
        if (isset($args[2])) {
            $red = $args[1];
            $green = $args[2];
            $blue = $args[3];
        }

        if ($args[1] == 'none') {
            $red = -1;
            $green = -1;
            $blue = -1;
        }

        $this->mapObject->colours['DEFAULT'][$key]['red1'] = $red;
        $this->mapObject->colours['DEFAULT'][$key]['green1'] = $green;
        $this->mapObject->colours['DEFAULT'][$key]['blue1'] = $blue;
        $this->mapObject->colours['DEFAULT'][$key]['bottom'] = -2;
        $this->mapObject->colours['DEFAULT'][$key]['top'] = -1;
        $this->mapObject->colours['DEFAULT'][$key]['special'] = 1;

        return true;
    }

    private function handleNODE_USESCALE($fullcommand, $args, $matches)
    {
        $svar = '';
        $stype = 'percent';

        // in or out?
        if (isset($matches[3])) {
            $svar = trim($matches[3]);
        }

        // percent or absolute?
        if (isset($matches[6])) {
            $stype = strtolower(trim($matches[6]));
        }

        // opens the door for other scaley things...
        switch (strtoupper($args[0])) {
            case 'USEICONSCALE':
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
            $this->currentObject->$varname = $svar;
        }
        $this->currentObject->$tvarname = $stype;
        $this->currentObject->$uvarname = $matches[2];

        return true;
    }


    private function handleFONTDEFINE($fullcommand, $args, $matches)
    {
        if (isset($args[3])) {
            wm_debug("New TrueType font in slot %d\n", $args[1]);

            $newFontObject = new WMFont();
            $fontOK = $newFontObject->initTTF($args[2], $args[3]);

            if (! $fontOK) {
                wm_warn("Failed to load ttf font " . $args[2] . " - at config line $this->lineCount\n [WMWARN30]");
            }

        } else {
            wm_debug("New GD font in slot %d\n", $args[1]);

            $newFontObject = new WMFont();
            $fontOK = $newFontObject->initGD($args[2]);

            if (!$fontOK) {
                wm_warn("Failed to load GD font: " . $args[2] . " ($newfont) at config line $this->lineCount [WMWARN32]\n");
                $newFontObject = null;
            }
        }

        if (! is_null($newFontObject)) {
            // $this->mapObject->fonts[$args[1]] = $newFontObject;
            $this->mapObject->fonts->addFont($args[1], $newFontObject);
            return true;
        }

        return false;
    }

    private function handleOVERLIB($fullcommand, $args, $matches)
    {
        $this->mapObject->has_overlibs = true;

        $urls = preg_split('/\s+/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);

        if ($args[0] == 'INOVERLIBGRAPH') {
            $index = IN;
        }

        if ($args[0] == 'OUTOVERLIBGRAPH') {
            $index = OUT;
        }

        if ($args[0] == 'OVERLIBGRAPH') {
            $this->currentObject->overliburl[IN] = $urls;
            $this->currentObject->overliburl[OUT] = $urls;
        } else {
            $this->currentObject->overliburl[$index] = $urls;
        }

        return true;
    }

    private function handleCOLOR($fullcommand, $args, $matches)
    {
        $key = $args[0];
        $field = str_replace("color", "colour", strtolower($args[0]));
        $val = strtolower($args[1]);

        // this is a regular colour setting thing
        if (isset($args[2])) {
            $wmc = new WMColour($args[1], $args[2], $args[3]);
        } else {
            $wmc = new WMColour($val);
        }

        $this->currentObject->$field = $wmc;

        return true;

        // this is a regular colour setting thing
        if (isset($args[2])) {
            $this->currentObject->$field = array($args[1], $args[2], $args[3]);

            return true;
        }

        if ($val == 'none') {
            $this->currentObject->$field = array(-1, -1, -1);

            return true;
        }

        if ($val == 'contrast') {
            $this->currentObject->$field = array(-3, -3, -3);

            return true;
        }

        if ($val == 'copy') {
            $this->currentObject->$field = array(-2, -2, -2);

            return true;
        }

        return false;
    }

    private function handleTARGET($fullcommand, $args, $matches)
    {
        // wipe any existing targets, otherwise things in the DEFAULT accumulate with the new ones
        $this->currentObject->targets = array();
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
            $newTarget = array(
                '',
                '',
                $this->currentSource,
                $this->lineCount,
                $arg,
                "",
                ""
            );

            if ($this->currentObject) {
                wm_debug("  TARGET: $arg\n");
                $this->currentObject->targets[] = $newTarget;
            }
        }

        return true;
    }

    private function commitItem()
    {
        if (is_null($this->currentObject)) {
            return;
        }

        if (get_class($this->currentObject) == 'stdClass') {
            wm_warn("INTERNAL - avoided a stdClass");
            return;
        }

        wm_debug("-> Committing a $this->currentType\n");

        if($this->currentType == 'NODE') {
            $this->mapObject->nodes[$this->currentObject->name] = $this->currentObject;
        }

        if($this->currentType == 'LINK') {
            $this->mapObject->links[$this->currentObject->name] = $this->currentObject;
        }
    }

    function readConfigLines($inputLines)
    {
        $matches = null;

        wm_debug("in readConfig\n");

        foreach ($inputLines as $buffer) {

            wm_debug("Processing: $buffer\n");
            $lineMatched = false;
            $this->lineCount++;

            $buffer = trim($buffer);

            if ($buffer == '' || substr($buffer, 0, 1) == '#') {
                // this is a comment line, or a blank line
                $lineMatched = true;
            } else {
                $this->objectLineCount++;
                // break out the line into words (quoted strings are one word)
                $args = $this::parseString($buffer);
                wm_debug("  First: $args[0] in $this->currentType\n");


                // From here, the aim of the game is to get out of this loop as
                // early as possible, without running more preg_match calls than
                // necessary. In 0.97, this per-line loop accounted for 50% of
                // the running time!

                // this next loop replaces a whole pile of duplicated ifs with something with consistent handling


                if ( !$lineMatched && true === isset($args[0])) {
                    // check if there is even an entry in this context for the current keyword
                    if (true === isset($this->configKeywords[$this->currentType][$args[0]])) {
                        // if there is, then the entry is an array of arrays - iterate them to validate the config
                        wm_debug("    Possible!\n");
                        foreach ($this->configKeywords[$this->currentType][$args[0]] as $keyword) {
                            unset($matches);
                            wm_debug("      Trying $keyword[1]\n");
                            if ((substr($keyword[1], 0, 1) != '/') || (1 === preg_match($keyword[1], $buffer, $matches))) {

                                wm_debug("Might be $args[0]\n");

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
                                        if (1 === preg_match('/^(.*)\[([^\]]+)\]$/', $key, $m)) {
                                            $index = constant($m[2]);
                                            $key = $m[1];
                                            $this->currentObject->{$key}[$index] = $val;
                                            $this->currentObject->setConfig($key . "." . $index, $val);
                                        } elseif (substr($key, -1, 1) == "+") {
                                            // if the key ends in a plus, it's an array we should append to
                                            $key = substr($key, 0, -1);
                                            array_push($this->currentObject->$key, $val);
                                            array_push($this->currentObject->config[$key], $val);
                                            $this->currentObject->addConfig($key, $val);

                                        } else {
                                            // otherwise, it's just the name of a property on the
                                            // appropriate object.
                                            wm_debug("      DONE! ($key, $val)\n");
                                            $this->currentObject->$key = $val;
                                            $this->currentObject->setConfig($key, $val);
                                        }
                                    }
                                    $lineMatched = true;
                                } else {
                                    // the third arg wasn't an array, it was a function name.
                                    // call that function to handle this keyword
                                    if (call_user_func(array($this, $keyword[2]), $buffer, $args, $matches)) {
                                        $lineMatched = true;
                                    }
                                }
                            }

                            // jump out of this loop if there's been a match
                            if ($lineMatched) {
                                break;
                            }
                        }
                    }
                }
            }

            if ((!$lineMatched) && ($buffer != '')) {
                wm_warn("Unrecognised config on line $this->lineCount: $buffer\n");
            }
        }

        // Commit the last item
        $this->commitItem();

        wm_debug("ReadConfig has finished reading the config ($this->lineCount lines)\n");
        wm_debug("------------------------------------------\n");

        return $this->lineCount;
    }

    function readConfigFile($filename)
    {
        $fileHandle = fopen($filename, "r");

        if ($fileHandle) {
            while (!feof($fileHandle)) {
                $buffer = fgets($fileHandle, 16384);
                // strip out any Windows line-endings that have gotten in here
                $buffer = str_replace("\r", "", $buffer);
                $lines[] = $buffer;
            }
            fclose($fileHandle);

            $this->currentSource = $filename;
            $result = $this->readConfigLines($lines);


            return $result;
        } else {
            return false;
        }

    }

}