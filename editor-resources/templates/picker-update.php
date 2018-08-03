<html>
<head>
    <script type="text/javascript">
        function update_source_step2(graphid) {
            var graph_url, info_url;

            var base_url = <?php echo $base_url ?>;

            if (typeof window.opener == "object") {

                graph_url = base_url + 'graph_image.php?rra_id=0&graph_nolegend=true&graph_height=100&graph_width=300&local_graph_id=' + graphid;
                info_url = base_url + 'graph.php?rra_id=all&local_graph_id=' + graphid;

                opener.document.forms["frmMain"].link_infourl.value = info_url;
                opener.document.forms["frmMain"].link_hover.value = graph_url;
            }
            self.close();
        }

        window.onload = update_source_step2(<?php echo $graphId ?>);

    </script>
</head>
<body>
This window should disappear in a moment.
</body>
</html>