<?php require $_SERVER['DOCUMENT_ROOT']."/lib/tmphpuser.php" ?>
<?php require $_SERVER['DOCUMENT_ROOT']."/lib/tmphpfuncs.php" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
 ***
 * Map viewer page. Displays the routes selected using the url params on a map, as well as on a table to the side.
 * URL Params:
 *  u - user to display highlighting for on map (required)
 *  rg - region to show routes for on the map (optional)
 *  sys - system to show routes for on the map (optional)
 *  rte - route name to show on the map. Supports pattern matching, with _ matching a single character, and % matching 0 or multiple characters.
 * (u, [rg|sys][rte])
 ***
 -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" href="/css/travelMapping.css" />
    <link rel="shortcut icon" type="image/png" href="/favicon.png">
    <style type="text/css">
        #controlbox {
            position: absolute;
            top: 30px;
            height: 20px;
            right: 20px;
            overflow: auto;
            padding: 5px;
            font-size: 20px;
	    width: 25%;
        }

        #routesTable {
            background-color: white;
        }

        #routesTable .routeName {
            width: 149px;
        }

        #routesTable .systemName {
            min-width: 54px;
        }

        #routesTable .clinched {
            min-width: 66px;
        }

        #routesTable .overall {
            min-width: 57px;
        }

        #routesTable .percent {
            min-width: 57px;
        }

        #map {
            position: absolute;
            top: 25px;
            bottom: 0px;
            width: 100%;
            overflow: hidden;
        }

        #map * {
            cursor: crosshair;
        }

	#selected {
            position: absolute;
            right: 10px;
            top: 60px;
            bottom: 20px;
            overflow-y: auto;
            max-width: 420px;
	    opacity: .95;  /* also forces stacking order */
        }

        #showHideMenu {
            position: absolute;
            right: 10px;
	    opacity: .75;  /* also forces stacking order */
        }
    </style>
    <script
        src="http://maps.googleapis.com/maps/api/js?key=<?php echo $gmaps_api_key ?>&sensor=false"
        type="text/javascript"></script>

    <!--Datatables-->
    <link rel="stylesheet" type="text/css"
          href="https://cdn.datatables.net/v/dt/jq-2.2.4/dt-1.10.15/fc-3.2.2/fh-3.1.2/kt-2.2.1/r-2.1.1/rg-1.0.0/sc-1.4.2/se-1.2.2/datatables.min.css"/>
    <script type="text/javascript"
            src="https://cdn.datatables.net/v/dt/jq-2.2.4/dt-1.10.15/fc-3.2.2/fh-3.1.2/kt-2.2.1/r-2.1.1/rg-1.0.0/sc-1.4.2/se-1.2.2/datatables.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/css/datatables.css"/>

    <script src="../lib/tmjsfuncs.js" type="text/javascript"></script>
    <title>Travel Mapping: Draft Map Overlay Viewer</title>
</head>

<body onload="loadmap(); waypointsFromSQL(); updateMap(); toggleTable();">
<script type="application/javascript">

    function toggleTable() {
        var menu = document.getElementById("showHideMenu");
        var index = menu.selectedIndex;
        var value = menu.options[index].value;
        routes = $('#routes');
        options = $('#options');
        selected = $('#selected');
        // show only table (or no table) based on value
        if (value == "routetable") {
            routes.show();
            options.hide();
        }
        else if (value == "options") {
            routes.hide();
            options.show();
        }
        else {
            routes.hide();
            options.hide();
        }
    }

    $(document).ready(function () {
        $('#routesTable').DataTable({
            paging: false,
            columnDefs: [
                {targets: '_all', orderable: true}
            ]
            });
        }
    );
</script>
<?php $nobigheader = 1; ?>
<?php require  $_SERVER['DOCUMENT_ROOT']."/lib/tmheader.php"; ?>

<div id="map">
</div>
<div id="selected">
    <div id="options" hidden>
        <form id="optionsForm" action="mapview.php">
            <table id="optionsTable" class="gratable">
                <thead>
                <tr>
                    <th>Select Map Options</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <input id="showMarkers" type="checkbox" name="Show Markers" onclick="showMarkersClicked()"/>&nbsp;Show
                        Markers
                    </td>
                </tr>

                <tr>
                    <td>User:
                        <?php tm_user_select(); ?>
                    </td>
                </tr>

                <tr>
                    <td>Region(s): <br/>
                        <?php tm_region_select(TRUE); ?>
                    </td>
                </tr>

                <tr>
                    <td>System(s): <br/>
                        <?php tm_system_select(TRUE); ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <input type="submit" value="Apply Changes"/>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div hih>
    <div id="routes">
        <table id="routesTable" class="display compact hover">
            <thead>
            <tr>
                <th class="sortable routeName">Route</th>
                <th class="sortable systemName">System</th>
                <th class="sortable clinched">Clinched (<?php tm_echo_units(); ?>)</th>
                <th class="sortable overall">Overall (<?php tm_echo_units(); ?>)</th>
                <th class="sortable percent">%</th>
            </tr>
            </thead>
            <tbody>
            <?php
            // TODO: a toggle to include/exclude devel routes?
            $sql_command = <<<SQL
    SELECT r.region, r.root, r.route, r.systemName, banner, city, sys.tier, 
      round(r.mileage, 2) AS total, 
      round(COALESCE(cr.mileage, 0), 2) as clinched 
    FROM routes AS r 
      LEFT JOIN clinchedRoutes AS cr ON r.root = cr.route AND traveler = '{$_GET['u']}' 
      LEFT JOIN systems as sys on r.systemName = sys.systemName
    WHERE  
SQL;
            if (array_key_exists('rte', $_GET)) {
                $sql_command .= "(r.route like '" . $_GET['rte'] . "' or r.route regexp '" . $_GET['rte'] . "[a-z]')";
                $sql_command = str_replace("*", "%", $sql_command);
                if (array_key_exists('rg', $_GET) or array_key_exists('sys', $_GET)) $sql_command .= ' AND ';
            }
            if (array_key_exists('rg', $_GET) && array_key_exists('sys', $_GET)) {
                $sql_command .= orClauseBuilder('rg', 'region') . " AND " . orClauseBuilder('sys', 'systemName');
            } elseif (array_key_exists('rg', $_GET)) {
                $sql_command .= orClauseBuilder('rg', 'region');
            } elseif (array_key_exists('sys', $_GET)) {
                $sql_command .= orClauseBuilder('sys', 'systemName');
            } elseif (!array_key_exists('rte', $_GET)) {
                //Don't show. Too many routes
                $sql_command .= "r.root IS NULL";
            }
            $sql_command .= "ORDER BY sys.tier, r.route;";
            $res = tmdb_query($sql_command);
            while ($row = $res->fetch_assoc()) {
                $link = "/hb?u=" . $_GET['u'] . "&amp;r=" . $row['root'];
                echo "<tr onclick=\"window.open('" . $link . "')\"><td class='routeName'>";
                //REGION ROUTE BANNER (CITY)
                echo $row['region'] . " " . $row['route'];
                if (strlen($row['banner']) > 0) {
                    echo " " . $row['banner'];
                }
                if (strlen($row['city']) > 0) {
                    echo " (" . $row['city'] . ")";
                }
                $pct = sprintf("%0.2f", ($row['clinched'] / $row['total'] * 100));
                echo <<<HTML
                    </td>
                    <td class='link systemName'>{$row['tier']}. <a href='/user/system.php?u={$_GET['u']}&amp;sys={$row['systemName']}'>{$row['systemName']}</a></td>
                    <td class="clinched">
HTML
                    . tm_convert_distance($row['clinched']) . "</td><td class='overall'>" . tm_convert_distance($row['total']) . "</td><td class='percent'>" . $pct . "%</td></tr>\n";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
<div id="controlbox">
    <select id="showHideMenu" onchange="toggleTable();">
    <option value="maponly">Map Only</option>
    <option value="options">Show Map Options</option>
    <option value="routetable" selected="selected">Show Route Table</option>
    </select>
</div>
</body>
<?php
    $tmdb->close();
?>

<script type="application/javascript" src="../api/waypoints.js.php?<?php echo $_SERVER['QUERY_STRING']?>"></script>
</html>
