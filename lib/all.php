<?php
/*   PHP Weathermap 0.98
     Copyright Howard Jones, 2005-2014 howie@thingy.com
     http://www.network-weathermap.com/
     Released under the GNU Public License

    one file to include all the others...
*/
//
require_once dirname(__FILE__) . '/Weathermap/Core/globals.php';
require_once dirname(__FILE__) . '/Weathermap/Core/constants.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Weathermap\Core\WMDebugFactory;

//require_once dirname(__FILE__) . '/exceptions.php';
//
//# require_once dirname(__FILE__) . '/php-compat.php';
//
//require_once dirname(__FILE__) . '/WMDebugFactory.php';
//require_once dirname(__FILE__) . '/WMStats.php';
//
//require_once dirname(__FILE__) . '/HTMLImagemap.php';
//
//require_once dirname(__FILE__) . '/MapBase.php';
//require_once dirname(__FILE__) . '/plugin-MapBase.php';
//require_once dirname(__FILE__) . '/MapDataItem.php';
//
//require_once dirname(__FILE__) . "/ConfigReader.php";
//
//require_once dirname(__FILE__) . '/WMFont.php';
//require_once dirname(__FILE__) . '/MapTextItem.php';
//require_once dirname(__FILE__) . '/MapScale.php';
//require_once dirname(__FILE__) . '/Map.php';
//
//require_once dirname(__FILE__) . '/WeatherMap.functions.php';
//require_once dirname(__FILE__) . '/Utility.php';
////require_once dirname(__FILE__) . '/WMImageUtility.php';
//require_once dirname(__FILE__) . '/WMImageLoader.class.php';
//
//require_once dirname(__FILE__) . '/image-functions.php';
//require_once dirname(__FILE__) . '/geometry.php';
//require_once dirname(__FILE__) . '/Point.php';
//require_once dirname(__FILE__) . '/WMVector.class.php';
//require_once dirname(__FILE__) . '/Line.php';
//require_once dirname(__FILE__) . '/Rectangle.php';
//require_once dirname(__FILE__) . '/WMBoundingBox.class.php';
//require_once dirname(__FILE__) . '/CatmullRom.class.php';
//
//require_once dirname(__FILE__) . '/Spine.php';
//require_once dirname(__FILE__) . '/LinkGeometry.php';
//require_once dirname(__FILE__) . '/AngledLinkGeometry.php';
//require_once dirname(__FILE__) . '/CurvedLinkGeometry.php';
//require_once dirname(__FILE__) . '/LinkGeometryFactory.php';
//
//require_once dirname(__FILE__) . '/Colour.php';
//require_once dirname(__FILE__) . '/WMTarget.class.php';
//
//# require_once dirname(__FILE__) . '/WMNodeIcon.class.php';
//# require_once dirname(__FILE__) . '/WMNodeLabel.class.php';
//
//require_once dirname(__FILE__) . '/WeatherMapNode.class.php';
//require_once dirname(__FILE__) . '/WeatherMapLink.class.php';
//# require_once dirname(__FILE__) . '/WeatherMapConfig.php';
//
//# require_once dirname(__FILE__) . '/WeatherMapRunner.class.php';

$wm_debug_logger = WMDebugFactory::create();
