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
    /** @var WeatherMapDataItem $currentObject */
    private $currentObject = null;
    private $currentType = "GLOBAL";
    private $currentSource = "";
    /** @var WeatherMap $mapObject */
    private $mapObject = null;
    private $objectLineCount = 0;

    // new version of config_keywords
    // array of contexts, contains an array of keywords, contains a (short) list of regexps as now
    // this way, we don't scan the whole table, and we call preg_match a WHOLE lot less
    // there will be more lines in the array, but we'll be checking less of them

    private $configKeywords = array(
        'GLOBAL' => array(

            'INCLUDE' => array(
                array('/^\s*INCLUDE\s+(.*)\s*$/i', 'handleINCLUDE'),
            ),

            'SCALE' => array(
                array('/^\s*SCALE\s+([A-Za-z][A-Za-z0-9_]*\s+)?(\-?\d+\.?\d*[munKMGT]?)\s+(\-?\d+\.?\d*[munKMGT]?)\s+(?:(\d+)\s+(\d+)\s+(\d+)(?:\s+(\d+)\s+(\d+)\s+(\d+))?|(none))\s*(.*)$/i', 'handleSCALE'),
            ),
            'KEYSTYLE' => array(
                array('/^\s*KEYSTYLE\s+([A-Za-z][A-Za-z0-9_]+\s+)?(classic|horizontal|vertical|inverted|tags)\s?(\d+)?\s*$/i', 'handleKEYSTYLE'),
            ),
            'KEYPOS' => array(
                array('/^\s*KEYPOS\s+([A-Za-z][A-Za-z0-9_]*\s+)?(-?\d+)\s+(-?\d+)(.*)/i', 'handleKEYPOS'),
            ),
            'NODE' => array(
                array('/^\s*NODE\s+(\S+)\s*$/i', 'handleNODE'),
            ),
            'LINK' => array(
                array('/^\s*LINK\s+(\S+)\s*$/i', 'handleLINK'),
            ),

            'FONTDEFINE' => array(
                array('/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s+(\d+)\s+(-?\d+)\s*$/i', 'handleFONTDEFINE'),
                array('/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s+(\d+)\s*$/i', 'handleFONTDEFINE'),
                array('/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s*$/i', 'handleFONTDEFINE'),
            ),
            'KEYOUTLINECOLOR' => array(
                array(

                    '/^KEYOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleGLOBALCOLOR'
                ),
                array(

                    '/^KEYOUTLINECOLOR\s+(none)$/i',
                    'handleGLOBALCOLOR'
                ),
            ),
            'KEYTEXTCOLOR' => array(
                array(

                    '/^KEYTEXTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleGLOBALCOLOR'
                ),
            ),
            'TITLECOLOR' => array(
                array(

                    '/^TITLECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleGLOBALCOLOR'
                ),
            ),
            'TIMECOLOR' => array(
                array(

                    '/^TIMECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleGLOBALCOLOR'
                ),
            ),
            'KEYBGCOLOR' => array(
                array(

                    '/^KEYBGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleGLOBALCOLOR'
                ),
                array(

                    '/^KEYBGCOLOR\s+(none)$/i',
                    'handleGLOBALCOLOR'
                ),
            ),
            'BGCOLOR' => array(
                array(

                    '/^BGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleGLOBALCOLOR'
                ),
            ),
            'SET' => array(
                array(

                    'SET',
                    'handleSET'
                ),
            ),
            'HTMLSTYLESHEET' => array(
                array(

                    '/^HTMLSTYLESHEET\s+(.*)\s*$/i',
                    array('htmlstylesheet' => 1)
                ),
            ),
            'HTMLOUTPUTFILE' => array(
                array(

                    '/^HTMLOUTPUTFILE\s+(.*)\s*$/i',
                    array('htmloutputfile' => 1)
                ),
            ),
            'BACKGROUND' => array(
                array(

                    '/^BACKGROUND\s+(.*)\s*$/i',
                    array('background' => 1)
                ),
            ),
            'IMAGEOUTPUTFILE' => array(
                array(

                    '/^IMAGEOUTPUTFILE\s+(.*)\s*$/i',
                    array('imageoutputfile' => 1)
                ),
            ),
            'DATAOUTPUTFILE' => array(
                array(

                    '/^DATAOUTPUTFILE\s+(.*)\s*$/i',
                    array('dataoutputfile' => 1)
                ),
            ),
            'IMAGEURI' => array(
                array(

                    '/^IMAGEURI\s+(.*)\s*$/i',
                    array('imageuri' => 1)
                ),
            ),
            'TITLE' => array(
                array(

                    '/^TITLE\s+(.*)\s*$/i',
                    array('title' => 1)
                ),
            ),
            'HTMLSTYLE' => array(
                array(

                    '/^HTMLSTYLE\s+(static|overlib)\s*$/i',
                    array('htmlstyle' => 1)
                ),
            ),
            'KILO' => array(
                array(

                    '/^KILO\s+(\d+)\s*$/i',
                    array('kilo' => 1)
                ),
            ),
            'KEYFONT' => array(
                array(

                    '/^KEYFONT\s+(\d+)\s*$/i',
                    array('keyfont' => 1)
                ),
            ),
            'TITLEFONT' => array(
                array(

                    '/^TITLEFONT\s+(\d+)\s*$/i',
                    array('titlefont' => 1)
                ),
            ),
            'TIMEFONT' => array(
                array(

                    '/^TIMEFONT\s+(\d+)\s*$/i',
                    array('timefont' => 1)
                ),
            ),
            'WIDTH' => array(
                array(

                    '/^WIDTH\s+(\d+)\s*$/i',
                    array('width' => 1)
                ),
            ),
            'HEIGHT' => array(
                array(

                    '/^HEIGHT\s+(\d+)\s*$/i',
                    array('height' => 1)
                ),
            ),
            'TITLEPOS' => array(
                array(

                    '/^TITLEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                    array(
                        'titlex' => 1,
                        'titley' => 2
                    )
                ),
                array(

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

                    '/^TIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                    array(
                        'timex' => 1,
                        'timey' => 2
                    )
                ),
                array(

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

                    '/^MINTIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                    array(
                        'mintimex' => 1,
                        'mintimey' => 2
                    )
                ),
                array(

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

                    '/^MAXTIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',
                    array(
                        'maxtimex' => 1,
                        'maxtimey' => 2
                    )
                ),
                array(

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
                array('/^\s*INCLUDE\s+(.*)\s*$/i', 'handleINCLUDE'),
            ),

            'TEMPLATE' => array(
                array('/^\s*TEMPLATE\s+(\S+)\s*$/i', 'handleTEMPLATE')
            ),

            'NODE' => array(
                array('/^\s*NODE\s+(\S+)\s*$/i', 'handleNODE'),
            ),
            'LINK' => array(
                array('/^\s*LINK\s+(\S+)\s*$/i', 'handleLINK'),
            ),

            'TARGET' => array(
                array(

                    'TARGET',
                    'handleTARGET'
                ),
            ),
            'SET' => array(
                array(

                    'SET',
                    'handleSET'
                ),
            ),
            'AICONOUTLINECOLOR' => array(
                array(

                    '/^AICONOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^AICONOUTLINECOLOR\s+(none)$/i',
                    'handleCOLOR'
                ),
            ),
            'AICONFILLCOLOR' => array(
                array(

                    '/^AICONFILLCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^AICONFILLCOLOR\s+(copy)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^AICONFILLCOLOR\s+(none)$/i',
                    'handleCOLOR'
                ),
            ),
            'LABELOUTLINECOLOR' => array(
                array(

                    '/^LABELOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^LABELOUTLINECOLOR\s+(none)$/i',
                    'handleCOLOR'
                ),
            ),
            'LABELBGCOLOR' => array(
                array(

                    '/^LABELBGCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^LABELBGCOLOR\s+(none)$/i',
                    'handleCOLOR'
                ),
            ),
            'LABELFONTCOLOR' => array(
                array(

                    '/^LABELFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^LABELFONTCOLOR\s+(contrast)$/i',
                    'handleCOLOR'
                ),
            ),
            'LABELFONTSHADOWCOLOR' => array(
                array(

                    '/^LABELFONTSHADOWCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
            ),
            'NOTES' => array(
                array(

                    '/^NOTES\s+(.*)\s*$/i',
                    array(
                        'notestext[IN]' => 1,
                        'notestext[OUT]' => 1
                    )
                ),
            ),
            'MAXVALUE' => array(
                array(

                    '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'maxValuesConfigured[IN]' => 2,
                        'maxValuesConfigured[OUT]' => 3,
                    )
                ),
                array(

                    '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'maxValuesConfigured[IN]' => 2,
                        'maxValuesConfigured[OUT]' => 2,
                    )
                ),
            ),
//            'ORIGIN' => array(
//                array(
//                    
//                    '/^ORIGIN\s+(C|NE|SE|NW|SW|N|S|E|W)/i',
//                    array('position_origin' => 1)
//                )
//            ),
            'POSITION' => array(
                array(

                    '/^POSITION\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i',
                    array(
                        'x' => 1,
                        'y' => 2
                    )
                ),
                array(

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
            'INFOURL' => array(
                array(

                    '/^INFOURL\s+(.*)\s*$/i',
                    array(
                        'infourl[IN]' => 1,
                        'infourl[OUT]' => 1
                    )
                ),
            ),
            'OVERLIBCAPTION' => array(
                array(

                    '/^OVERLIBCAPTION\s+(.*)\s*$/i',
                    array(
                        'overlibcaption[IN]' => 1,
                        'overlibcaption[OUT]' => 1
                    )
                ),
            ),
            'ZORDER' => array(
                array(

                    '/^ZORDER\s+([-+]?\d+)\s*$/i',
                    array('zorder' => 1)
                ),
            ),
            'OVERLIBHEIGHT' => array(
                array(

                    '/^OVERLIBHEIGHT\s+(\d+)\s*$/i',
                    array('overlibheight' => 1)
                ),
            ),
            'OVERLIBWIDTH' => array(
                array(

                    '/^OVERLIBWIDTH\s+(\d+)\s*$/i',
                    array('overlibwidth' => 1)
                ),
            ),
            'LABELFONT' => array(
                array(

                    '/^LABELFONT\s+(\d+)\s*$/i',
                    array('labelfont' => 1)
                ),
            ),
            'LABELANGLE' => array(
                array(

                    '/^LABELANGLE\s+(0|90|180|270)\s*$/i',
                    array('labelangle' => 1)
                ),
            ),
            'ICON' => array(
                array(

                    '/^ICON\s+(\S+)\s*$/i',
                    array(
                        'iconfile' => 1,
                        'iconscalew' => '#0',
                        'iconscaleh' => '#0'
                    )
                ),
                array(

                    '/^ICON\s+(\S+)\s*$/i',
                    array('iconfile' => 1)
                ),
                array(

                    '/^ICON\s+(\d+)\s+(\d+)\s+(inpie|outpie|box|rbox|round|gauge|nink)\s*$/i',
                    array(
                        'iconfile' => 3,
                        'iconscalew' => 1,
                        'iconscaleh' => 2
                    )
                ),
                array(

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

                    '/^LABEL\s*$/i',
                    array('label' => '')
                ), # special case for blank labels
                array(

                    '/^LABEL\s+(.*)\s*$/i',
                    array('label' => 1)
                ),
            ),
            'DEFINEOFFSET' => array(
                array(

                    '/^DEFINEOFFSET\s+([A-Za-z][A-Za-z0-9_]*)\s+([-+]?\d+)\s+([-+]?\d+)/i',
                    "handleDEFINEOFFSET"
                ),
            ),
            'LABELOFFSET' => array(
                array(

                    '/^LABELOFFSET\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i',
                    array(
                        'labeloffsetx' => 1,
                        'labeloffsety' => 2
                    )
                ),
                array(

                    '/^LABELOFFSET\s+(C|NE|SE|NW|SW|N|S|E|W)\s*$/i',
                    array('labeloffset' => 1)
                ),
                array(

                    '/^LABELOFFSET\s+((C|NE|SE|NW|SW|N|S|E|W)\d+)\s*$/i',
                    array('labeloffset' => 1)
                ),
                array(

                    '/^LABELOFFSET\s+(-?\d+r\d+)\s*$/i',
                    array('labeloffset' => 1)
                ),
            ),
            'USESCALE' => array(
                array(

                    '/^(USESCALE)\s+([A-Za-z][A-Za-z0-9_]*)(\s+(in|out))?(\s+(absolute|percent))?\s*$/i',
                    'handleNODE_USESCALE'
                ),
            ),
            'USEICONSCALE' => array(
                array(

                    '/^(USEICONSCALE)\s+([A-Za-z][A-Za-z0-9_]*)(\s+(in|out))?(\s+(absolute|percent))?\s*$/i',
                    'handleNODE_USESCALE'
                ),
            ),
            'OVERLIBGRAPH' => array(
                array('/^OVERLIBGRAPH\s+(.+)$/i', 'handleOVERLIB')
            ),

        ), // end of node
        'LINK' => array(

            'INCLUDE' => array(
                array('/^\s*INCLUDE\s+(.*)\s*$/i', 'handleINCLUDE'),
            ),

            'TEMPLATE' => array(
                array('/^\s*TEMPLATE\s+(\S+)\s*$/i', 'handleTEMPLATE')
            ),
            'NODE' => array(
                array('/^\s*NODE\s+(\S+)\s*$/i', 'handleNODE'),
            ),
            'LINK' => array(
                array('/^\s*LINK\s+(\S+)\s*$/i', 'handleLINK'),
            ),

            'TARGET' => array(
                array(

                    'TARGET',
                    'handleTARGET'
                ),
            ),
            'SET' => array(
                array(

                    'SET',
                    'handleSET'
                ),
            ),
            'NODES' => array(
                array(

                    'NODES',
                    'handleNODES'
                ),
            ),
            'VIA' => array(
                array(

                    'VIA',
                    'handleVIA'
                ),
            ),
            'COMMENTFONTCOLOR' => array(
                array(

                    '/^COMMENTFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^COMMENTFONTCOLOR\s+(contrast)$/i',
                    'handleCOLOR'
                ),
            ),
            'OUTLINECOLOR' => array(
                array(

                    '/^OUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^OUTLINECOLOR\s+(none)$/',
                    'handleCOLOR'
                ),
            ),
            'BWOUTLINECOLOR' => array(
                array(

                    '/^BWOUTLINECOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^BWOUTLINECOLOR\s+(none)$/i',
                    'handleCOLOR'
                ),
            ),
            'BWBOXCOLOR' => array(
                array(

                    '/^BWBOXCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
                array(

                    '/^BWBOXCOLOR\s+(none)$/i',
                    'handleCOLOR'
                ),
            ),
            'BWFONTCOLOR' => array(
                array(

                    '/^BWFONTCOLOR\s+(\d+)\s+(\d+)\s+(\d+)$/i',
                    'handleCOLOR'
                ),
            ),
            'NOTES' => array(
                array(

                    '/^NOTES\s+(.*)\s*$/i',
                    array(
                        'notestext[IN]' => 1,
                        'notestext[OUT]' => 1
                    )
                ),
            ),
            'MAXVALUE' => array(
                array(

                    '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'maxValuesConfigured[IN]' => 2,
                        'maxValuesConfigured[OUT]' => 3,
                    )
                ),
                array(

                    '/^(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'maxValuesConfigured[IN]' => 2,
                        'maxValuesConfigured[OUT]' => 2,
                    )
                ),
            ),
            'WIDTH' => array(
                array(

                    '/^WIDTH\s+(\d+)\s*$/i',
                    array('width' => 1)
                ),
                array(

                    '/^WIDTH\s+(\d+\.\d+)\s*$/i',
                    array('width' => 1)
                ),
            ),
            'SPLITPOS' => array(
                array(

                    '/^SPLITPOS\s+(\d+)\s*$/i',
                    array('splitpos' => 1)
                ),
            ),
            'BWLABEL' => array(
                array(

                    '/^BWLABEL\s+bits\s*$/i',
                    array(
                        'labelstyle' => 'bits',
                        'bwlabelformats[IN]' => WeatherMapLink::FMT_BITS_IN,
                        'bwlabelformats[OUT]' => WeatherMapLink::FMT_BITS_OUT,
                    )
                ),
                array(

                    '/^BWLABEL\s+percent\s*$/i',
                    array(
                        'labelstyle' => 'percent',
                        'bwlabelformats[IN]' => WeatherMapLink::FMT_PERC_IN,
                        'bwlabelformats[OUT]' => WeatherMapLink::FMT_PERC_OUT,
                    )
                ),
                array(

                    '/^BWLABEL\s+unformatted\s*$/i',
                    array(
                        'labelstyle' => 'unformatted',
                        'bwlabelformats[IN]' => WeatherMapLink::FMT_UNFORM_IN,
                        'bwlabelformats[OUT]' => WeatherMapLink::FMT_UNFORM_OUT,
                    )
                ),
                array(

                    '/^BWLABEL\s+none\s*$/i',
                    array(
                        'labelstyle' => 'none',
                        'bwlabelformats[IN]' => '',
                        'bwlabelformats[OUT]' => '',
                    )
                ),
            ),
            'BWLABELPOS' => array(
                array(

                    '/^BWLABELPOS\s+(\d+)\s(\d+)\s*$/i',
                    array(
                        'labeloffset_in' => 1,
                        'labeloffset_out' => 2
                    )
                ),
            ),
            'COMMENTPOS' => array(
                array(

                    '/^COMMENTPOS\s+(\d+)\s(\d+)\s*$/i',
                    array(
                        'commentoffset_in' => 1,
                        'commentoffset_out' => 2
                    )
                ),
            ),
            'DUPLEX' => array(
                array(

                    '/^DUPLEX\s+(full|half)\s*$/i',
                    array('duplex' => 1)
                ),
            ),
            'BWSTYLE' => array(
                array(

                    '/^BWSTYLE\s+(classic|angled)\s*$/i',
                    array('labelboxstyle' => 1)
                ),
            ),
            'LINKSTYLE' => array(
                array(

                    '/^LINKSTYLE\s+(twoway|oneway)\s*$/i',
                    array('linkstyle' => 1)
                ),
            ),
            'COMMENTSTYLE' => array(
                array(

                    '/^COMMENTSTYLE\s+(edge|center)\s*$/i',
                    array('commentstyle' => 1)
                ),
            ),
            'ARROWSTYLE' => array(
                array(

                    '/^ARROWSTYLE\s+(classic|compact)\s*$/i',
                    array('arrowstyle' => 1)
                ),
                array('/^\s*ARROWSTYLE\s+(\d+)\s+(\d+)\s*$/i', 'handleARROWSTYLE'),
            ),
            'VIASTYLE' => array(
                array(

                    '/^VIASTYLE\s+(curved|angled)\s*$/i',
                    array('viastyle' => 1)
                ),
            ),
            'INCOMMENT' => array(
                array(

                    '/^INCOMMENT\s+(.*)\s*$/i',
                    array('comments[IN]' => 1)
                ),
            ),
            'OUTCOMMENT' => array(
                array(

                    '/^OUTCOMMENT\s+(.*)\s*$/i',
                    array('comments[OUT]' => 1)
                ),
            ),

            'OVERLIBGRAPH' => array(
                array('/^OVERLIBGRAPH\s+(.+)$/i', 'handleOVERLIB')
            ),
            'INOVERLIBGRAPH' => array(
                array('/^INOVERLIBGRAPH\s+(.+)$/i', 'handleOVERLIB')
            ),
            'OUTOVERLIBGRAPH' => array(
                array('/^OUTOVERLIBGRAPH\s+(.+)$/i', 'handleOVERLIB')
            ),

            'USESCALE' => array(
                array(

                    '/^USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s*$/i',
                    array('usescale' => 1)
                ),
                array(

                    '/^USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s+(absolute|percent)\s*$/i',
                    array(
                        'usescale' => 1,
                        'scaletype' => 2
                    )
                ),
            ),
            'BWFONT' => array(
                array(

                    '/^BWFONT\s+(\d+)\s*$/i',
                    array('bwfont' => 1)
                ),
            ),
            'COMMENTFONT' => array(
                array(

                    '/^COMMENTFONT\s+(\d+)\s*$/i',
                    array('commentfont' => 1)
                ),
            ),
            'BANDWIDTH' => array(
                array(

                    '/^(BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'maxValuesConfigured[IN]' => 2,
                        'maxValuesConfigured[OUT]' => 3,
                    )
                ),
                array(

                    '/^(BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s*$/i',
                    array(
                        'maxValuesConfigured[IN]' => 2,
                        'maxValuesConfigured[OUT]' => 2,
                    )
                ),
            ),
            'OUTBWFORMAT' => array(
                array(

                    '/^OUTBWFORMAT\s+(.*)\s*$/i',
                    array(
                        'bwlabelformats[OUT]' => 1,
                        'labelstyle' => '--'
                    )
                ),
            ),
            'INBWFORMAT' => array(
                array(

                    '/^INBWFORMAT\s+(.*)\s*$/i',
                    array(
                        'bwlabelformats[IN]' => 1,
                        'labelstyle' => '--'
                    )
                ),
            ),
            'INNOTES' => array(
                array(

                    '/^INNOTES\s+(.*)\s*$/i',
                    array('notestext[IN]' => 1)
                ),
            ),
            'OUTNOTES' => array(
                array(

                    '/^OUTNOTES\s+(.*)\s*$/i',
                    array('notestext[OUT]' => 1)
                ),
            ),
            'INFOURL' => array(
                array(

                    '/^INFOURL\s+(.*)\s*$/i',
                    array(
                        'infourl[IN]' => 1,
                        'infourl[OUT]' => 1
                    )
                ),
            ),
            'ININFOURL' => array(
                array(

                    '/^ININFOURL\s+(.*)\s*$/i',
                    array('infourl[IN]' => 1)
                ),
            ),
            'OUTINFOURL' => array(
                array(

                    '/^OUTINFOURL\s+(.*)\s*$/i',
                    array('infourl[OUT]' => 1)
                ),
            ),
            'OVERLIBCAPTION' => array(
                array(

                    '/^OVERLIBCAPTION\s+(.*)\s*$/i',
                    array(
                        'overlibcaption[IN]' => 1,
                        'overlibcaption[OUT]' => 1
                    )
                ),
            ),
            'INOVERLIBCAPTION' => array(
                array(

                    '/^INOVERLIBCAPTION\s+(.*)\s*$/i',
                    array('overlibcaption[IN]' => 1)
                ),
            ),
            'OUTOVERLIBCAPTION' => array(
                array(

                    '/^OUTOVERLIBCAPTION\s+(.*)\s*$/i',
                    array('overlibcaption[OUT]' => 1)
                ),
            ),
            'ZORDER' => array(
                array(

                    '/^ZORDER\s+([-+]?\d+)\s*$/i',
                    array('zorder' => 1)
                ),
            ),
            'OVERLIBWIDTH' => array(
                array(

                    '/^OVERLIBWIDTH\s+(\d+)\s*$/i',
                    array('overlibwidth' => 1)
                ),
            ),
            'OVERLIBHEIGHT' => array(
                array(

                    '/^OVERLIBHEIGHT\s+(\d+)\s*$/i',
                    array('overlibheight' => 1)
                ),
            ),
        ) // end of link
    );

    public
    function __construct(&$map, $type = "GLOBAL", $object = null)
    {
        $this->mapObject = $map;
        $this->currentType = $type;
        if ($type == "GLOBAL") {
            $this->currentObject = $map;
        } else {
            $this->currentObject = $object;
        }
    }

    public
    function __toString()
    {
        return "ConfigReader for '" . $this->currentSource . "''";
    }


// parseString is based on code from:
// http://www.webscriptexpert.com/Php/Space-Separated%20Tag%20Parser/
    public
    function parseString($input)
    {
        $output = array(); // Array of Output
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
                    if (substr($sToken, -1, 1) === $cPhraseQuote) {
                        // Trim the last character and add to the current phrase, with a single leading space if necessary
                        if (strlen($sToken) > 1) {
                            $sPhrase .= ((strlen($sPhrase) > 0) ? ' ' : null) . substr($sToken, 0, -1);
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
                        if ((strlen($sToken) > 1) && ($sToken [0] === substr($sToken, -1, 1))) {
                            // The current token begins AND ends the phrase, trim the quotes
                            $sPhrase = substr($sToken, 1, -1);
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

    private
    function commitItem()
    {
        if (is_null($this->currentObject)) {
            return;
        }

        if (get_class($this->currentObject) == 'stdClass') {
            wm_warn("INTERNAL - avoided a stdClass");
            return;
        }

        wm_debug("-> Committing a $this->currentType named " . $this->currentObject->name . "\n");

        if ($this->currentType == 'NODE') {
            $this->mapObject->nodes[$this->currentObject->name] = $this->currentObject;
        }

        if ($this->currentType == 'LINK') {
            $this->mapObject->links[$this->currentObject->name] = $this->currentObject;
        }
    }

    function readConfigFile($filename)
    {
        $lines = array();

        $fileHandle = fopen($filename, "r");

        if (!$fileHandle) {
            return false;
        }

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
    }

    function readConfigLines($inputLines)
    {
        wm_debug("in readConfigLines\n");

        foreach ($inputLines as $buffer) {
            wm_debug("Processing: $buffer\n");
            $this->lineCount++;

            $buffer = trim($buffer);

            if ($buffer == '' || substr($buffer, 0, 1) == '#') {
                // this is a comment line, or a blank line, just skip to the next line
                continue;
            }

            $this->objectLineCount++;
            // break out the line into words (quoted strings are one word)
            $args = $this::parseString($buffer);
            wm_debug("  First: $args[0] in $this->currentType\n");

            // From here, the aim of the game is to get out of this loop as
            // early as possible, without running more preg_match calls than
            // necessary. In 0.97, this per-line loop accounted for 50% of
            // the running time!

            // this next loop replaces a whole pile of duplicated ifs with something with consistent handling
            $lineMatched = $this->readConfigLine($args, $buffer);

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

    /**
     * @param $args
     * @param $buffer
     * @return bool
     */
    private function readConfigLine($args, $buffer)
    {
        $matches = null;

        if (true === isset($args[0])) {
            // check if there is even an entry in this context for the current keyword
            if (true === isset($this->configKeywords[$this->currentType][$args[0]])) {
                // if there is, then the entry is an array of arrays - iterate them to validate the config
                wm_debug("    Possible!\n");
                foreach ($this->configKeywords[$this->currentType][$args[0]] as $keyword) {
                    unset($matches);
                    wm_debug("      Trying $keyword[0]\n");
                    if ((substr($keyword[0], 0, 1) != '/') || (1 === preg_match($keyword[0], $buffer, $matches))) {
                        #   wm_debug("Might be $args[0]\n");

                        // if we came here without a regexp, then the \1 etc
                        // refer to arg numbers, not match numbers

                        $params = isset($matches) ? $matches : $args;

                        // The second array item is either an array of config variables to populate,
                        // or a function to call that will handle decoding this stuff
                        if (is_array($keyword[1])) {
                            $this->readConfigSimpleAssignment($keyword, $params);
                            return true;
                        } else {
                            // the second arg wasn't an array, it was a function name.
                            // call that function to handle this keyword
                            if (call_user_func(array($this, $keyword[1]), $buffer, $args, $params)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param string[] $keyword The entry from configKeywords
     * @param string[] $matches The list of parameters or regexp matches
     */
    private function readConfigSimpleAssignment($keyword, $matches)
    {
        foreach ($keyword[1] as $key => $val) {
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
                $this->currentObject->setConfigValue($key . "." . $index, $val);
            } elseif (substr($key, -1, 1) == "+") {
                // if the key ends in a plus, it's an array we should append to
                $key = substr($key, 0, -1);
                array_push($this->currentObject->$key, $val);
                // array_push($this->currentObject->config[$key], $val);
                $this->currentObject->addConfigValue($key, $val);
            } else {
                // otherwise, it's just the name of a property on the
                // appropriate object.
                # wm_debug("      DONE! ($key, $val)\n");
                $this->currentObject->$key = $val;
                $this->currentObject->setConfigValue($key, $val);
            }
        }
    }

    function handleVIA($fullcommand, $args, $matches)
    {
        if (preg_match('/^\s*VIA\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i', $fullcommand,
            $matches)) {
            $this->currentObject->vialist[] = array(
                $matches[1],
                $matches[2]
            );
            return true;
        }
        if (preg_match('/^\s*VIA\s+(\S+)\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i', $fullcommand,
            $matches)) {
            $this->currentObject->vialist[] = array(
                $matches[2],
                $matches[3],
                $matches[1]
            );
            return true;
        }
        return false;
    }

    private function interpretNodeSpec($input)
    {
        $endoffset = 'C';
        $nodename = $input;
        $offset_dx = 0;
        $offset_dy = 0;
        $need_size_precalc = false;

        // percentage of compass - must be first
        if (preg_match('/:(NE|SE|NW|SW|N|S|E|W|C)(\d+)$/i', $input, $submatches)) {
            wm_debug("Matching partial compass offset\n");
            $endoffset = $submatches[1] . $submatches[2];
            $nodename = preg_replace('/:(NE|SE|NW|SW|N|S|E|W|C)\d+$/i', '', $input);
            $need_size_precalc = true;
        } elseif (preg_match("/:(NE|SE|NW|SW|N|S|E|W|C)$/i", $input, $submatches)) {
            wm_debug("Matching 100% compass offset\n");
            $endoffset = $submatches[1];
            $nodename = preg_replace('/:(NE|SE|NW|SW|N|S|E|W|C)$/i', '', $input);
            $need_size_precalc = true;
        } elseif (preg_match('/:(-?\d+r\d+)$/i', $input, $submatches)) {
            wm_debug("Matching radial offset\n");
            $endoffset = $submatches[1];
            $nodename = preg_replace('/:(-?\d+r\d+)$/i', '', $input);
            $need_size_precalc = true;
        } elseif (preg_match('/:([-+]?\d+):([-+]?\d+)$/i', $input, $submatches)) {
            wm_debug("Matching regular x,y link offset\n");
            $xoff = $submatches[1];
            $yoff = $submatches[2];
            $endoffset = $xoff . ":" . $yoff;
            $nodename = preg_replace("/:$xoff:$yoff$/i", '', $input);
            $need_size_precalc = true;
        } elseif (preg_match('/^([^:]+):([A-Za-z][A-Za-z0-9\-_]*)$/i', $input, $submatches)) {
            wm_debug("Matching node namedoffset %s on node %s\n", $submatches[2], $submatches[1]);
            $otherNode = $this->mapObject->getNode($submatches[1]);
            if (array_key_exists($submatches[2], $otherNode->named_offsets)) {
                $named_offset = $submatches[2];
                $nodename = preg_replace("/:$named_offset$/i", '', $input);

                $endoffset = $named_offset;
                $offset_dx = $otherNode->named_offsets[$named_offset][0];
                $offset_dy = $otherNode->named_offsets[$named_offset][1];
            }
        }

        return array($offset_dx, $offset_dy, $nodename, $endoffset, $need_size_precalc);
    }

    function handleNODES($fullcommand, $args, $matches)
    {
        $offset_dx = array();
        $offset_dy = array();
        $nodeNames = array();
        $endOffsets = array();

        if (preg_match('/^NODES\s+(\S+)\s+(\S+)\s*$/i', $fullcommand, $matches)) {
            $valid_nodes = 2;
            foreach (array(1, 2) as $i) {

                list($offset_dx[$i], $offset_dy[$i], $nodeNames[$i], $endOffsets[$i], $need_size_precalc) = $this->interpretNodeSpec($matches[$i]);

                if (!array_key_exists($nodeNames[$i], $this->mapObject->nodes)) {
                    wm_warn("Unknown node '" . $nodeNames[$i]
                        . "' on line $this->lineCount of config\n");
                    $valid_nodes--;
                }
            }
            // TODO - really, this should kill the whole link, and reset for the next one
            // XXX this error case will not work in the handler function
            if ($valid_nodes == 2) {
//                $this->currentObject->a = $this->mapObject->nodes[$nodeNames[1]];
//                $this->currentObject->b = $this->mapObject->nodes[$nodeNames[2]];

                $this->currentObject->setEndNodes($this->mapObject->getNode($nodeNames[1]), $this->mapObject->getNode($nodeNames[2]));

                $this->currentObject->a_offset = $endOffsets[1];
                $this->currentObject->b_offset = $endOffsets[2];

                // lash-up to avoid having to pass loads of context to calc_offset
                // - named offsets require access to the internals of the node, when they are
                //   resolved. Luckily we can resolve them here, and skip that.

                foreach (array(1 => "a", 2 => "b") as $index => $name) {
                    if ($offset_dx[$index] != 0 || $offset_dy[$index] != 0) {
                        wm_debug("Applying offset for $name end %s,%s\n", $offset_dx[$index], $offset_dy[$index]);

                        // TODO - these should be arrays, not named properties
                        $n1 = $name . "_offset_dx";
                        $n2 = $name . "_offset_dy";
                        $n3 = $name . "_offset_resolved";

                        $this->currentObject->$n1 = $offset_dx[$index];
                        $this->currentObject->$n2 = $offset_dy[$index];
                        $this->currentObject->$n3 = true;
                    }
                }

            } else {
                // this'll stop the current link being added
                $last_seen = "broken";
            }
            return true;
        }
        return false;
    }

    function handleSET($fullcommand, $args, $matches)
    {
        global $weathermap_error_suppress;

        if (preg_match('/^SET\s+(\S+)\s+(.*)\s*$/i', $fullcommand, $matches)) {
            $this->currentObject->add_hint($matches[1], trim($matches[2]));

            if ($this->currentObject->my_type() == "map" && substr($matches[1], 0, 7) == 'nowarn_') {
                $weathermap_error_suppress[$matches[1]] = 1;
            }
            return true;
        }
        // allow setting a variable to ""
        if (preg_match('/^SET\s+(\S+)\s*$/i', $fullcommand, $matches)) {
            $this->currentObject->add_hint($matches[1], '');
            if ($this->currentObject->my_type() == "map" && substr($matches[1], 0, 7) == 'nowarn_') {
                $weathermap_error_suppress[$matches[1]] = 1;
            }
            return true;
        }
        return false;
    }

    function handleGLOBALCOLOR($fullcommand, $args, $matches)
    {
        $key = str_replace("COLOR", "", strtoupper($args[0]));
        $val = strtolower($args[1]);

        // this is a regular colour setting thing
        if (isset($args[2])) {
            $wmc = new WMColour($args[1], $args[2], $args[3]);
        } else {
            // it's a special colour
            $wmc = new WMColour($val);
        }
        $this->mapObject->colourtable[$key] = $wmc;

        return true;
    }

    function handleNODE_USESCALE($fullcommand, $args, $matches)
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
            $this->currentObject->setConfigValue($varname, $svar);
        }
        $this->currentObject->$tvarname = $stype;
        $this->currentObject->setConfigValue($tvarname, $stype);
        $this->currentObject->$uvarname = $matches[2];
        $this->currentObject->setConfigValue($uvarname, $matches[2]);

        return true;
    }

    function handleFONTDEFINE($fullcommand, $args, $matches)
    {
        if (isset($args[3])) {
            wm_debug("New TrueType font in slot %d\n", $args[1]);

            $newFontObject = $this->mapObject->fonts->makeFontObject("truetype", $args[2], $args[3]);

            if (isset($args[4])) {
                $newFontObject->v_offset = $args[4];
            }

            # $newFontObject = new WMFont();
            # $fontOK = $newFontObject->initTTF($args[2], $args[3]);

            if (!$newFontObject->isLoaded()) {
                wm_warn("Failed to load ttf font " . $args[2] . " - at config line $this->lineCount\n [WMWARN30]");
            }

        } else {
            wm_debug("New GD font in slot %d\n", $args[1]);

            $newFontObject = $this->mapObject->fonts->makeFontObject("gd", $args[2]);
            # $newFontObject = new WMFont();
            # $fontOK = $newFontObject->initGD($args[2]);

            // XXX - why do we do this with GD fonts but not truetype?
            if (!$newFontObject->isLoaded()) {
                wm_warn("Failed to load GD font: " . $args[2] . " ($args[1]) at config line $this->lineCount [WMWARN32]\n");
                $newFontObject = null;
            }
        }

        if (!is_null($newFontObject)) {
            $this->mapObject->fonts->addFont($args[1], $newFontObject);
            return true;
        }

        return false;
    }

    function handleOVERLIB($fullcommand, $args, $matches)
    {

        $this->has_overlibs = true;
        $urls = preg_split('/\s+/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
        $keyword = strtoupper($args[0]);

        if ($keyword == 'INOVERLIBGRAPH') {
            $index = IN;
        }
        if ($keyword == 'OUTOVERLIBGRAPH') {
            $index = OUT;
        }
        if ($keyword == 'OVERLIBGRAPH') {
            $this->currentObject->overliburl[IN] = $urls;
            $this->currentObject->overliburl[OUT] = $urls;
        } else {
            $this->currentObject->overliburl[$index] = $urls;
        }
        return true;
    }

    function handleCOLOR($fullcommand, $args, $matches)
    {
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
    }

    function handleTARGET($fullcommand, $args, $matches)
    {
        // wipe any existing targets, otherwise things in the DEFAULT accumulate with the new ones
        $this->currentObject->targets = array();
        array_shift($args); // take off the actual TARGET keyword

        // Now loop through all the rest
        foreach ($args as $arg) {
            $newTarget = new WMTarget($arg, $this->currentSource, $this->lineCount);

            if ($this->currentObject) {
                wm_debug("  TARGET: $arg\n");
                $this->currentObject->targets[] = $newTarget;
            }
        }

        return true;
    }

    private function handleNODE($fullcommand, $args, $matches)
    {

        $this->commitItem();
        unset($this->currentObject);

        if ($args[1] == 'DEFAULT') {
            $this->currentObject = $this->mapObject->nodes['DEFAULT'];
            wm_debug("Loaded default NODE\n");

            if (sizeof($this->mapObject->nodes) > 2) {
                wm_warn("NODE DEFAULT is not the first NODE. Defaults will not apply to earlier NODEs. [WMWARN27]\n");
            }

        } else {
            if (isset($this->mapObject->nodes[$matches[1]])) {
                wm_warn("Duplicate node name " . $matches[1] . " at line $this->lineCount - only the last one defined is used. [WMWARN24]\n");
            }

            $this->currentObject = new WeatherMapNode($matches[1], "DEFAULT", $this->mapObject);
            wm_debug("Created new NODE\n");
        }
        $this->objectLineCount = 0;
        $this->currentType = "NODE";
        $this->currentObject->configline = $this->lineCount;
        $this->currentObject->defined_in = $this->currentSource;

        return true;

    }

    private function handleLINK($fullcommand, $args, $matches)
    {
        $this->commitItem();
        unset($this->currentObject);

        if ($args[1] == 'DEFAULT') {
            $this->currentObject = $this->mapObject->links['DEFAULT'];
            wm_debug("Loaded default LINK\n");

            if (sizeof($this->mapObject->links) > 2) {
                wm_warn("$this LINK DEFAULT is not the first LINK. Defaults will not apply to earlier LINKs. [WMWARN26]\n");
            }

        } else {
            if (isset($this->mapObject->links[$matches[1]])) {
                wm_warn("Duplicate link name " . $matches[1] . " at line $this->lineCount - only the last one defined is used. [WMWARN25]\n");
            }
            $this->currentObject = new WeatherMapLink($matches[1], "DEFAULT", $this->mapObject);
            wm_debug("Created new LINK\n");
        }
        $this->currentType = "LINK";
        $this->objectLineCount = 0;
        $this->currentObject->configline = $this->lineCount;
        $this->currentObject->defined_in = $this->currentSource;

        return true;
    }

    private function handleARROWSTYLE($fullcommand, $args, $matches)
    {
        $this->currentObject->arrowstyle = $matches[1] . ' ' . $matches[2];
        $this->currentObject->setConfigValue("arrowstyle", $matches[1] . ' ' . $matches[2]);
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

        if (!isset($this->mapObject->scales[$matches[1]])) {
            $this->mapObject->scales[$matches[1]] = new WeatherMapScale($matches[1], $this->mapObject);
        }
        $newscale = $this->mapObject->scales[$matches[1]];

        $key = $matches[2] . '_' . $matches[3];
        $tag = $matches[11];

        $colour1 = null;
        $colour2 = null;

        $bottom = WMUtility::interpretNumberWithMetricSuffix($matches[2], $this->mapObject->kilo);
        $top = WMUtility::interpretNumberWithMetricSuffix($matches[3], $this->mapObject->kilo);

        if (isset($matches[10]) && $matches[10] == 'none') {
            $colour1 = new WMColour("none");
        } else {
            $colour1 = new WMColour((int)($matches[4]), (int)($matches[5]), (int)($matches[6]));
            $colour2 = $colour1;
        }

        // this is the second colour, if there is one
        if (isset($matches[7]) && $matches[7] != '') {
            $colour2 = new WMColour((int)($matches[7]), (int)($matches[8]), (int)($matches[9]));
        }

        $newscale->AddSpan($bottom, $top, $colour1, $colour2, $tag);

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

    private function handleDEFINEOFFSET($fullcommand, $args, $matches)
    {
        wm_debug("Defining a named offset: " . $matches[1] . "\n");
        $this->currentObject->named_offsets[$matches[1]] = array(intval($matches[2]), intval($matches[3]));

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
            return true;
        }

        wm_warn("line $this->lineCount: $this->currentType TEMPLATE '$templateName' doesn't exist! (if it does exist, check it's defined first) [WMWARN40]\n");

        return false;
    }

    private function handleINCLUDE($fullcommand, $args, $matches)
    {
        $filename = $matches[1];

        if (file_exists($filename)) {
            wm_debug("Including '{$filename}'\n");

            if (in_array($filename, $this->mapObject->included_files)) {
                wm_warn("Attempt to include '$filename' twice! Skipping it.\n");
                return (false);
            }

            $this->mapObject->included_files[] = $filename;
            $this->mapObject->has_includes = true;

            $reader = new WeatherMapConfigReader($this->mapObject);
            $reader->readConfigFile($matches[1]);

            $this->currentType = "GLOBAL";
            $this->currentObject = $this->mapObject;

            return true;
        }

        wm_warn("INCLUDE File '{$matches[1]}' not found!\n");
        return false;
    }

    /**
     * Generate a basic Markdown-formatted summary of all known config keywords,
     * and what they do (at least, what variables they affect, or which function
     * handles them)
     *
     * @return int total entries in the keyword list
     */
    public function dumpKeywords()
    {
        $count = 0;
        print "# Complete configuration keyword list\n\n";
        foreach ($this->configKeywords as $scope => $keywords) {
            print "\n\n# $scope\n";
            ksort($keywords);

            foreach ($keywords as $keyword => $matches) {
                print "\n## $keyword\n";
                foreach ($matches as $match) {

                    $nicer = str_replace("\\", "\\\\", $match[0]);

                    print "\n### $nicer\n";
                    if (is_array($match[1])) {
                        foreach ($match[1] as $key => $val) {
                            $escval = $val;
                            if (substr($val, 0, 1) == "#") {
                                $escval = "\"" . substr($val, 1.) . "\"";
                            }

                            print "\n* $escval &#x21d2; `$scope->$key`\n";
                        }
                    } else {
                        print "\n* &#x2192; `$match[1]()`\n";
                    }
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Go through the configKeywords array, making sure that all the functions and members
     * referenced by name in strings actually exist! Used only by phpunit.
     */
    public function selfValidate()
    {
        $classes = array(
            "GLOBAL" => "WeatherMap",
            "LINK" => "WeatherMapLink",
            "NODE" => "WeatherMapNode"
        );

        $result = true;

        foreach ($this->configKeywords as $scope => $keywords) {
            foreach ($keywords as $keyword => $matches) {
                foreach ($matches as $match) {

                    # $match[0] is a regexp or string match
                    // TODO - can we validate a regexp?
                    # $match[1] is either an array of properties to set, or a function to handle it
                    if (is_array($match[1])) {
                        # TODO: if it's a list of variables, check they exist on the relevant object (from scope)
                        foreach ($match[1] as $key => $val) {
                            if (1 === preg_match('/^(.*)\[([^\]]+)\]$/', $key, $m)) {
                                $key = $m[1];
                            }

                            if (!property_exists($classes[$scope], $key)) {
                                wm_warn("$scope:$keyword tries to set nonexistent property $key");
                                $result = false;
                            }
                        }
                    } else {
                        # TODO: if it's a handleXXXX function, check that exists
                        if (!method_exists($this, $match[1])) {
                            wm_warn("$scope:$keyword has a missing handler ($match[1])");
                            $result = false;
                        }
                    }
                }
            }
        }

        return $result;
    }

}
