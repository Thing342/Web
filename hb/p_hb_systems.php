<?php
/**
 * Created by PhpStorm.
 * User: wes
 * Date: 6/2/17
 * Time: 4:41 PM
 */
//We have no filters at all, so display list of systems as a landing page.
echo <<<HTML
    <script type="text/javascript">
    $(document).ready(function () {
            $('#systemsTable').DataTable({
                pageLength: 50,
                order: [[3, "asc"], [0, "asc"], [1, "asc"]],
                columnDefs: [
                    {targets: [0, 1, 3, 4], orderable: true},
                    {targets: '_all', orderable: false}
                ],
                stateSave: true
            });
        });
    </script>
    <style type="text/css">
    #systems_container {
        width: 50%;
        margin-left: auto;
        margin-right: auto;
    }
    
    #systemsTable > tbody > tr > td.sysname {
        font-size: 11px;
        font-weight: 800;
    }
</style>
    <div id="systems_container">
    <table class="display compact hover" id="systemsTable">
        <thead>
            <tr><th colspan="5">List of Systems</th></tr>
            <tr><th class="sortable">Country</th><th class="sortable">System</th><th class="sortable">Code</th><th class="sortable">Status</th><th class="sortable">Level</th></tr>
        </thead>
        <tbody>
HTML;

$sql_command = "SELECT * FROM systems LEFT JOIN countries ON countryCode = countries.code";
$res = tmdb_query($sql_command);
while ($row = $res->fetch_assoc()) {
    $linkJS = "window.open('/hb/index.php?sys={$row['systemName']}&u={$tmuser}')";
    echo "<tr class='status-" . $row['level'] . "' onClick=\"$linkJS\">";
    if (strlen($row['name']) > 15) {
        echo "<td>{$row['code']}</td>";
    } else {
        echo "<td>{$row['name']}</td>";
    }

    echo "<td class='sysname'>{$row['fullName']}</td><td>{$row['systemName']}</td><td>{$row['level']}</td><td>Tier {$row['tier']}</td></tr>\n";
}

echo "</tbody></table></div>";
?>