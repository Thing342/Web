<?php
/**
 * Created by PhpStorm.
 * User: wes
 * Date: 6/2/17
 * Time: 4:44 PM
 */

// init tables
echo <<<HTML
<script type="text/javascript">
$(document).ready(function () {
        $('#routes').DataTable({
            pageLength: 50,
            order: [[3, "asc"]],
            columnDefs: [
                {targets: [0, 3, 4, 5, 6, 7], orderable: true},
                {targets: '_all', orderable: false}
            ],
            stateSave: true
        });
    });
</script>
<style type="text/css">
#routelist {
    width: 70%;
    margin-left: auto;
    margin-right: auto;
}

#routes > tbody > tr > td.routename {
    font-size: 11px;
    font-weight: 800;
}
</style>
HTML;

$sql_command = <<<SQL
SELECT * 
FROM routes 
  LEFT JOIN systems ON systems.systemName = routes.systemName
SQL;
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

function gen_disp_name($route)
{
    $city_field = "";
    if (strcmp($route['city'], "") != 0) {
        $city_field = "({$route['city']}) ";
    }

    return "{$route['route']} {$route['banner']} {$city_field}";
}

echo "<div id=\"routelist\">\n";
echo <<<HTML
<h3>Select a route to display:</h3>
<table class="display compact hover" id="routes">
    <thead>
    <tr>
        <th>Region</th>
        <th>Route&nbsp;Name</th>
        <th>.list Name</th>
        <th>ID</th>
        <th>System</th>
        <th>Level</th>
        <th>Tier</th>
        <th>Length ($tmunits)</th>
    </tr>
    </thead>
    <tbody>\n
HTML;
$res = tmdb_query($sql_command);
while ($row = $res->fetch_assoc()) {
    $jsopen = "window.document.location = '/hb?u={$tmuser}&amp;r={$row['root']}'";
    $disp_name = gen_disp_name($row);
    $disp_length = tm_convert_distance($row['mileage']);
    echo <<<HTML
<tr onclick="$jsopen">
    <td>{$row['region']}</td>
    <td class="routename">{$disp_name}</td>
    <td>{$row['region']} {$row['route']}{$row['banner']}{$row['abbrev']}</td>
    <td>{$row['root']}</td>
    <td class="status-{$row['level']}">{$row['fullName']}</td>
    <td class="status-{$row['level']}">{$row['level']}</td>
    <td class="status-{$row['level']}">Tier {$row['tier']}</td>
    <td>{$disp_length}</td>    
</tr>
HTML;

    #echo "<tr class=\"notclickable status-" . $row['level'] . "\"><td>{$row['tier']}</td><td>" . $row['systemName'] . "</td><td>" . $row['region'] . "</td><td>" . $row['route'] . $row['banner'];
    #if (strcmp($row['city'], "") != 0) {
    #    echo " (" . $row['city'] . ")";
    #}
    #echo "</td><td>" . $row['region'] . " " . $row['route'] . $row['banner'] . $row['abbrev'] . "</td><td>" . $row['level'] . "</td><td><a href=\"/hb/index.php?u={$tmuser}&r=" . $row['root'] . "\">" . $row['root'] . "</a></td></tr>\n";
}
$res->free();
echo "</table></div>\n";
?>