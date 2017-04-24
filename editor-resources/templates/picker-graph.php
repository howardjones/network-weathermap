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
        ul#dslist li { cursor: hand; }
    </style>
    <title><?php echo $title ?></title>
</head>
<body class="graph-picker">

<h3><?php echo $title ?></h3>

<form name="mini">
    Host: <select id="host_id" name="host_id">
        <option value="-1">Any</option>
        <option value="0">None</option>
        <?php foreach ($hosts as $host): ?>
            <option value="<?php echo htmlspecialchars($host['id'])?>"><?php echo htmlspecialchars($host['name']) ?></option>
        <?php endforeach ?>
    </select><br />
    <span class="filter" style="display: none;">Filter: <input id="filterstring" name="filterstring" size="20"> (case-sensitive)<br /></span>
    <span class="ds_includegraph" style="display: none;"><input id="overlib" name="overlib" type="checkbox" value="yes"> <label for="overlib">Set both OVERLIBGRAPH and INFOURL.</label><br /></span>
    <span class="aggregate_choice"  style="display: none;"><input id="aggregate" name="aggregate" type="checkbox" value="yes"> <label for="aggregate">Append TARGET to existing one (Aggregate)</label></span>
</form>
<div class="listcontainer">
    <ul id="dslist">
        <?php foreach ($sources as $source): ?>
            <li
                data-graph-id="<?php echo intval($source['local_graph_id'])?>"
                data-width="<?php echo intval($source['width'])?>"
                data-height="<?php echo intval($source['height'])?>"
                data-host-id="<?php echo intval($source['host_id'])?>"><?php echo htmlspecialchars($source['description']) ?></li>
        <?php endforeach ?>
    </ul>
</div>

</body>
</html>