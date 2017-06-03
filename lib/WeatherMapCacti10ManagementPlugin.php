<?php

require_once "WeatherMapUIBase.class.php";
require_once 'WeathermapManager.class.php';
require_once 'WeatherMapCactiManagementPlugin.php';


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
        $this->cacti_header();
        $this->maplist_warnings();
        $this->maplist();
        ?>
        <script type='text/javascript'>
            $(function () {
                $('#settings').click(function (event) {
                    document.location = urlPath + 'settings.php?tab=maps';
                });

                $('#edit').click(function (event) {
                    event.preventDefault();
                    loadPageNoHeader('weathermap-cacti10-plugin-mgmt.php?action=groupadmin&header=false');
                });

                $('.remover').click(function () {
                    href = $(this).attr('href');
                    loadPageNoHeader(href);
                });
            });
        </script>
        <?php

        $this->cacti_footer();
    }

    public function cacti_header()
    {
        top_header();
    }

    public function cacti_footer()
    {
        bottom_footer();
    }

    private function cacti_row_start($i)
    {
        form_alternate_row();
    }
}