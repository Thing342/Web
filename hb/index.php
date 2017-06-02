<?php require $_SERVER['DOCUMENT_ROOT']."/lib/tmphpuser.php" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
 ***
 * Highway Browser Main Page. If a root is supplied, a map will show that root's path along with its waypoints.
 * Otherwise, it will show a list of routes that the user can select from, with filters by region and system availible.
 * URL Params:
 *  r - root of route to view waypoints for. When set, the page will display a map with the route params. (required for displaying map)
 *  u - user to display highlighting for on map (optional)
 *  rg - region to filter for on the highway browser list (optional)
 *  sys - system to filter for on the highway browser list (optional)
 *  ([r [u]] [rg | sys])
 ***
 -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" href="/css/travelMapping.css" />
    <link rel="stylesheet" type="text/css" href="/fonts/roadgeek.css" />
    <link rel="shortcut icon" type="image/png" href="/favicon.png">
    <style type="text/css">
        #headerbox {
            position: absolute;
            top: 0px;
            bottom: 50px;
            width: 100%;
            overflow: hidden;
            text-align: center;
            font-size: 30px;
            font-family: "Times New Roman", serif;
            font-style: bold;
        }

        #routebox {
            position: fixed;
            left: 0px;
            top: 110px;
            bottom: 0px;
            width: 100%;
            overflow: auto;
        }

        #pointbox {
            position: fixed;
            left: 0px;
            top: 70px;
            right: 400px;
            bottom: 0px;
            width: 400px;
            overflow: auto;
        }

        #controlbox {
            position: fixed;
            top: 65px;
            bottom: 100px;
            height: 100%;
            left: 400px;
            right: 0px;
            overflow: auto;
            padding: 5px;
            font-size: 20px;
        }

        #map {
            position: absolute;
            top: 100px;
            bottom: 0px;
            left: 400px;
            right: 0px;
            overflow: hidden;
        }

        #map * {
            cursor: crosshair;
        }

        #waypoints:hover {
            cursor: pointer;
        }

        #pointbox span {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 5px;
        }

        #pointbox table {
            width: 75%;
            margin-bottom: 15px;
        }

        #routeInfo td {
            text-align: right;
        }


    </style>
    <?php require $_SERVER['DOCUMENT_ROOT']."/lib/tmphpfuncs.php" ?>
    <?php
    // check for region and/or system parameters
    $regions = tm_qs_multi_or_comma_to_array("rg");
    if (count($regions) > 0) {
        $region = $regions[0];
        $regionName = tm_region_code_to_name($region);
    }
    else {
        $region = "";
        $regionName = "No Region Specified";
    }

    $systems = tm_qs_multi_or_comma_to_array("sys");
    if (count($systems) > 0) {
        $system = $systems[0];
        $systemName = tm_system_code_to_name($system);
    }
    else {
        $system = "";
        $systemName = "No System Specified";
    }

    // if a specific route is specified, that's what we'll view
    if (array_key_exists("r", $_GET)) {
        $routeparam = $_GET['r'];
    } else {
        $routeparam = "";
    }

    ?>
    <script
        src="http://maps.googleapis.com/maps/api/js?key=<?php echo $gmaps_api_key ?>&sensor=false"
        type="text/javascript"></script>
    <!-- jQuery -->
    <script src="http://code.jquery.com/jquery-1.11.0.min.js" type="text/javascript"></script>
    <!-- TableSorter -->
    <script src="/lib/jquery.tablesorter.min.js" type="text/javascript"></script>

    <script src="../lib/tmjsfuncs.js" type="text/javascript"></script>
    <script type="application/javascript">
        $(document).ready(function () {
                $("#routes").tablesorter({
                    sortList: [[0, 0]],
                    headers: {0: {sorter: false}, 4: {sorter: false}, 5: {sorter: false}}
                });
            }
        );
    </script>
    <title><?php
        if ($routeparam != "") {
            $sql_command = "SELECT * FROM routes WHERE root = '" . $_GET['r'] . "'";
            $res = tmdb_query($sql_command);
            $routeInfo = $res->fetch_array();
            $res->free();
            echo $routeInfo['region'] . " " . $routeInfo['route'];
            if (strlen($routeInfo['banner']) > 0) {
                echo " " . $routeInfo['banner'];
            }
            if (strlen($routeInfo['city']) > 0) {
                echo " (" . $routeInfo['city'] . ")";
            }
            echo " - ";
        }
        ?>Travel Mapping Highway Browser (Draft)</title>
</head>
<?php
$nobigheader = 1;

if ($routeparam == "") {
    echo "<body>\n";
    require  $_SERVER['DOCUMENT_ROOT']."/lib/tmheader.php";
    echo "<h1>Travel Mapping Highway Browser (Draft)</h1>";
    echo "<form id=\"selectHighways\" name=\"HighwaySearch\" action=\"/hb/index.php?u={$tmuser}\">";
    echo "<label for=\"sys\">Filter routes by...  System: </label>";
    tm_system_select(FALSE);
    echo "<label for=\"rg\"> Region: </label>";
    tm_region_select(FALSE);
    // should be taken care of by the cookie:
    //echo "<input type=\"hidden\" name=\"u\" value=\"{$tmuser}\" />";
    echo "<input type=\"submit\" value=\"Apply Filter\" /></form>";

}
else {
    echo "<body onload=\"loadmap(); waypointsFromSQL(); updateMap();\">\n";
    require  $_SERVER['DOCUMENT_ROOT']."/lib/tmheader.php";

}
?>
<script type="text/javascript">
    function initFloatingHeaders($table) {
        var $col = $table.find('tr.float');
        var $th = $col.find('th');
        var tag = "<tr style='height: 22px'></tr>";
        $(tag).insertAfter($col);
        $th.each(function (index) {
            var $row = $table.find('tr td:nth-child(' + (index + 1) + ')');
            if ($row.outerWidth() > $(this).width()) {
                $(this).width($row.width());
            } else {
                $row.width($(this).width());
            }
            var pos =  $row.position().left - $table.offset().left - 2;
            //console.log($table.offset().left);
            $(this).css({left: pos})
        });
    }

    $(document).ready(function () {
            <?php
                if ($routeparam != "") {

                } elseif (($region != "") or ($system != "")) {
                    echo <<<JS
                    routes = $("#routes");
                    routes.tablesorter({
                        sortList: [[0, 0], [6, 0]],
                        headers: {0: {sorter: false}}
                    });
                    initFloatingHeaders(routes);
JS;
                } else {
                    echo <<<JS
                    systems = $('#systemsTable');
                    systems.tablesorter({
                        sortList: [[0, 0], [4, 0], [3, 0]],
                        headers: {0: {sorter: false}}
                    });
JS;
                }
            ?>
        }
    );
</script>

<?php
if ($routeparam != "") {
    require 'p_hb_map.php';
} elseif (($region != "") or ($system != "")) {  // we have no r=, so we will show a list of all
    require 'p_hb_routelist.php';
} else {
    require 'p_hb_systems.php';
}
$tmdb->close();
?>
</body>
<script type="application/javascript" src="../api/waypoints.js.php?<?php echo "r=$routeparam&u=$tmuser";?>"></script>
</html>
