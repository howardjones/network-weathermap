<?php

namespace Weathermap\Integrations\Cacti;

//require_once "WeatherMapUIBase.class.php";
//require_once 'WeathermapManager.php';
//require_once 'WeatherMapCactiManagementPlugin.php';


class WeatherMapCacti10ManagementPlugin extends WeatherMapCactiManagementPlugin
{
    public $colours;

    public function __construct($config)
    {
        parent::__construct($config);
        $this->my_url = "weathermap-cacti10-plugin-mgmt.php";
        $this->editor_url = "weathermap-cacti10-plugin-editor.php";
    }

    /**
     * @param $request
     * @param $appObject
     */
    public function handleManagementMainScreen($request, $appObject)
    {
        $this->cactiHeader();
        $this->maplist_warnings();
        $this->maplist();
        $this->footerLinks();
        ?>
        <script type='text/javascript'>
            $(function () {
                $('#settings').click(function () {
                    document.location = urlPath + 'settings.php?tab=maps';
                });

                $('#edit').click(function (event) {
                    event.preventDefault();
                    loadPageNoHeader('weathermap-cacti10-plugin-mgmt.php?action=groupadmin&header=false');
                });

                $('.remover').click(function () {
                    var href = $(this).attr('href');
                    loadPageNoHeader(href);
                });
            });
        </script>
        <?php

        $this->cactiFooter();
    }

    public function cactiHeader()
    {
        top_header();
    }

    public function cactiFooter()
    {
        bottom_footer();
    }

    public function cactiRowStart($i)
    {
        form_alternate_row();
    }
}