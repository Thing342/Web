<?php
/**
 * Created by PhpStorm.
 * User: wes
 * Date: 6/2/17
 * Time: 4:46 PM
 */
require $_SERVER['DOCUMENT_ROOT'] . "/shields/shieldgen.php";
echo "<div id=\"pointbox\">\n";
echo "<span class='bigshield'>" . generate($_GET['r'], true) . "</span>";
echo "<span><a href='/user/mapview.php?rte={$routeInfo['route']}'>View Associated Routes</a></span>";

echo "<span>";
$sql_command = "SELECT region, route, banner, city FROM routes WHERE root = '" . $routeparam . "';";
$res = tmdb_query($sql_command);
$row = $res->fetch_assoc();
echo $row['region'] . " " . $row['route'];
if (strlen($row['banner']) > 0) {
    echo " " . $row['banner'];
}
if (strlen($row['city']) > 0) {
    echo " (" . $row['city'] . ")";
}
$res->free();
echo "</span>";

echo "<table id='routeInfo' class=\"gratable\"><thead><tr><th colspan='2'>Route Stats</th></tr></thead><tbody>";
$sql_command = <<<SQL
      SELECT
          COUNT(*) as numDrivers,
          SUM(cr.clinched) as numClinched,
          @numUsers := (
            SELECT COUNT(DISTINCT traveler) FROM clinchedOverallMileageByRegion
          ) as numUsers,
          ROUND(COUNT(*) / @numUsers * 100, 2) as drivenPct,
          ROUND(SUM(cr.clinched) / @numUsers * 100, 2) as clinchedPct,
          ROUND(SUM(cr.clinched) / COUNT(*) * 100, 2) as drivenClinchedPct,
          GROUP_CONCAT(cr.traveler SEPARATOR ', ') as drivers,
          GROUP_CONCAT(IF(cr.clinched = 1, cr.traveler, null) separator ', ') as clinchers,
          ROUND(AVG(cr.mileage), 2) as avgMileage,
          ROUND(r.mileage, 2) as totalMileage,
          ROUND(avg(cr.mileage) / r.mileage * 100, 2) as mileagePct
        FROM clinchedRoutes as cr
          JOIN routes as r ON cr.route = r.root
        WHERE cr.route = '$routeparam'
SQL;
$row = tmdb_query($sql_command)->fetch_assoc();
$totalLength = tm_convert_distance($row['totalMileage']) . " " . $tmunits;
$averageTraveled = tm_convert_distance($row['avgMileage']) . " " . $tmunits;
echo <<<HTML
    <tr><td class="important">Total Length</td><td>{$totalLength}</td></tr>
    <tr><td>LIST Name</td><td>{$routeInfo['region']} {$routeInfo['route']}{$routeInfo['banner']}{$routeInfo['abbrev']}</td></tr> 
    <tr title="{$row['drivers']}"><td>Total Drivers</td><td>{$row['numDrivers']} ({$row['drivenPct']} %)</td>
    <tr class="link" title="{$row['clinchers']}"><td rowspan="2">Total Clinched</td><td>{$row['numClinched']} ({$row['clinchedPct']} %)</td>
    <tr class="link" title="{$row['clinchers']}"><td>{$row['drivenClinchedPct']} % of drivers</td>
    s<tr><td>Average Traveled</td><td>{$averageTraveled} ({$row['mileagePct']} %)</td></tr>
    </tbody></table>
HTML;
echo "<table id='waypoints' class=\"gratable\"><thead><tr><th colspan=\"3\">Waypoints</th></tr><tr><th>Coordinates</th><th>Name</th><th title='Percent of people who have driven this route who have driven though this point.'>%</th></tr></thead><tbody>\n";
$sql_command = <<<SQL
        SELECT pointName, latitude, longitude, driverPercent
        FROM waypoints
        LEFT JOIN (
            SELECT
              waypoints.pointId,
              @num_drivers := (
                SELECT COUNT(DISTINCT traveler) FROM clinchedRoutes WHERE clinchedRoutes.route = '$routeparam'
              ) as numDrivers,
              count(*),
              ROUND(count(*) / @num_drivers * 100, 2) as driverPercent
            FROM segments
            LEFT JOIN clinched ON segments.segmentId = clinched.segmentId
            LEFT JOIN waypoints ON segments.waypoint1 = waypoints.pointId
            WHERE segments.root = '$routeparam'
            GROUP BY segments.segmentId
        ) as pointStats on pointStats.pointId = waypoints.pointId
        WHERE root = '$routeparam';
SQL;
$res = tmdb_query($sql_command);
$waypointnum = 0;
while ($row = $res->fetch_assoc()) {
    # only visible points should be in this table
    if (!startsWith($row['pointName'], "+")) {
        $colorFactor = $row['driverPercent'] / 100;
        $colors = [255, 255 - round($colorFactor * 128), 255 - round($colorFactor * 128)];
        echo "<tr onClick='javascript:LabelClick(" . $waypointnum . ",\"" . $row['pointName'] . "\"," . $row['latitude'] . "," . $row['longitude'] . ",0);'><td>(" . $row['latitude'] . "," . $row['longitude'] . ")</td><td class='link'>" . $row['pointName'] . "</td><td style='background-color: rgb({$colors[0]},{$colors[1]},{$colors[2]})'>{$row['driverPercent']}</td></tr>\n";
    }
    $waypointnum = $waypointnum + 1;
}
$res->free();
echo <<<ENDA
</table>
</div>
  <div id="controlbox">
      <span id="controlboxroute">
ENDA;
if ($routeparam != "") {
    echo "<table><tbody><tr><td>";
    echo "<input id=\"showMarkers\" type=\"checkbox\" name=\"Show Markers\" onclick=\"showMarkersClicked()\" checked=\"false\" />&nbsp;Show Markers&nbsp;";
    echo "</td><td>";
    echo "<form id=\"userForm\" action=\"/hb/index.php\">";
    echo "User: ";
    tm_user_select();
    echo "<label>Units: </label>\n";
    tm_units_select();
    echo "</td><td>";
    echo "<input type=\"hidden\" name=\"r\" value=\"" . $routeparam . "\" />";
    echo "<input type=\"submit\" value=\"Apply\" />";
    echo "</td></tr></tbody></table>\n";
}
echo <<<ENDB
  </span>
</div>
<div id="map">
</div>
ENDB;
?>