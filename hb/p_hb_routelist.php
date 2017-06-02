<?php
/**
 * Created by PhpStorm.
 * User: wes
 * Date: 6/2/17
 * Time: 4:44 PM
 */
$sql_command = "SELECT * FROM routes LEFT JOIN systems ON systems.systemName = routes.systemName";
//check for query string parameter for system and region filters
if ($system != "") {
    $sql_command .= " WHERE routes.systemName = '" . $system . "'";
    if ($region != "") {
        $sql_command .= "AND routes.region = '" . $region . "'";
    }
} else if ($region != "") {
    $sql_command .= " WHERE routes.region = '" . $region . "'";
}

$sql_command .= ";";
echo "<div id=\"routebox\">\n";
echo "<table class=\"gratable tablesorter ws_data_table\" id=\"routes\"><thead><tr><th colspan=\"7\">Select Route to Display (click a header to sort by that column)</th></tr><tr class='float'><th class=\"sortable\">Tier</th><th class=\"sortable\">System</th><th class=\"sortable\">Region</th><th class=\"sortable\">Route&nbsp;Name</th><th>.list Name</th><th class=\"sortable\">Level</th><th>Root</th></tr></thead><tbody>\n";
$res = tmdb_query($sql_command);
while ($row = $res->fetch_assoc()) {
    echo "<tr class=\"notclickable status-" . $row['level'] . "\"><td>{$row['tier']}</td><td>" . $row['systemName'] . "</td><td>" . $row['region'] . "</td><td>" . $row['route'] . $row['banner'];
    if (strcmp($row['city'], "") != 0) {
        echo " (" . $row['city'] . ")";
    }
    echo "</td><td>" . $row['region'] . " " . $row['route'] . $row['banner'] . $row['abbrev'] . "</td><td>" . $row['level'] . "</td><td><a href=\"/hb/index.php?u={$tmuser}&r=" . $row['root'] . "\">" . $row['root'] . "</a></td></tr>\n";
}
$res->free();
echo "</table></div>\n";
?>