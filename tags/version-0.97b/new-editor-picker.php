<html>
    <head>
        <script src = "editor-resources/jquery-latest.min.js" type = "text/javascript">
        </script>

        <script type = "text/javascript">
            jQuery.noConflict();
        </script>

        <style type = "text/css">
            body {
                font-family: sans-serif;
                font-size: 10pt;
            }

            ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            ul {
                border: 1px solid black;
            }

            ul li.row0 {
                background: #ddd;
            }

            ul li.row1 {
                background: #ccc;
            }

            ul li {
                border-bottom: 1px solid #aaa;
                border-top: 1px solid #eee;
                padding: 2px;
            }

            ul li a {
                text-decoration: none;
                color: black;
            }
        </style>
    </head>

    <body>
        <select name = "collection" id = "collection"><option id = "placeholder">Select
        your datasource collection</option>
        </select>

        <div class = "listcontainer">
            <ul id = "dslist"></ul>
        </div>
    </body>
</html>