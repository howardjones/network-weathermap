<html>
<head>
    <script type="text/javascript" src="vendor/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="vendor/jquery-fastlivefilter/jquery.fastLiveFilter.js"></script>
    <script type="text/javascript" src="editor-resources/cacti-pick.js"></script>

    <script type="text/javascript">

        var base_url = <?php echo $base_url ?>;
        var rra_path = <?php echo $rra_path ?>;
        var aggregate = <?php echo $aggregate ?>;
        var overlib = <?php echo $overlib ?>;
        var selected_host = <?php echo $selected_host ?>;

        $(document).ready(function() {
            $("select#host_id").val(<?php echo $selected_host ?>);
            console.log("Selecting host");
        });
    </script>

    <style type="text/css">
        body { font-family: sans-serif; font-size: 10pt; }
        ul { list-style: none;  margin: 0; padding: 0; }
        ul { border: 1px solid black; }
        ul li.row0 { background: #ddd;}
        ul li.row1 { background: #ccc;}
        ul li { border-bottom: 1px solid #aaa; border-top: 1px solid #eee; padding: 2px;}
        ul li a { text-decoration: none; color: black; }
    </style>
    <title><?php echo $title ?></title>
</head>
<body class="data-picker">

<h3><?php echo $title ?></h3>

<?php if (sizeof($recents) > 0) {
    print "recent";
    foreach ($recents as $id => $name) {
        print "<a href=cacti-pick.php?host_id=" . $id . "&action=link_step1&overlib=1&aggregate=0>[" . $name . "]</a><br>";
    }
}
?>

<form name="mini">
    Host: <select id="host_id" name="host_id">
        <option value="-1">Any</option>
        <option value="0">None</option>
        <?php foreach ($hosts as $host): ?>
            <option value="<?php echo htmlspecialchars($host['id'])?>"><?php echo htmlspecialchars($host['name']) ?></option>
        <?php endforeach ?>
    </select><br />
    <span class="filter" style="display: none;">Filter: <input id="filterstring" name="filterstring" size="20"> (case-sensitive)<br /></span>
    <span class="ds_includegraph" style="display: none;"><input id="overlib" name="overlib" type="checkbox" value="yes"> <label for="overlib">Set both OVERLIBGRAPH and INFOURL (as well as TARGET).</label><br /></span>
    <span class="aggregate_choice"  style="display: none;"><input id="aggregate" name="aggregate" type="checkbox" value="yes"> <label for="aggregate">Append TARGET to existing one (Aggregate)</label></span>
    <span class="ds_dsstats" style="display: none;"><input id="dsstats" name="dsstats" type="checkbox" value="no"> <label for="dsstats">Generate 'dsstats' target instead of rrdtool.</label><br /></span>
</form>
<div class="listcontainer">
    <ul id="dslist">
        <?php foreach ($sources as $source): ?>
            <li
                data-source-id="<?php echo intval($source['local_data_id'])?>"
                data-path="<?php echo htmlspecialchars($source['data_source_path'])?>"
                data-host-id="<?php echo intval($source['host_id'])?>"><?php echo htmlspecialchars($source['description']) ?></li>
        <?php endforeach ?>
    </ul>
</div>

</body>
</html>