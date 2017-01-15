<?php
require $_SERVER['DOCUMENT_ROOT'] . "/lib/tmphpfuncs.php";
require $_SERVER['DOCUMENT_ROOT'] . "/shields/shieldgen.php";

if (array_key_exists('num', $_GET)) $num = $_GET['num'];
else $num = 25;

if (array_key_exists('sys', $_GET)) $sysClause = "AND systemName = '{$_GET['sys']}'";
else $sysClause = "";

if (array_key_exists('rg', $_GET)) $rtClause = " AND region = '{$_GET['rg']}'";
else $rtClause = "";

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" href="/css/travelMapping.css"/>
    <!-- jQuery -->
    <script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
    <!-- TableSorter -->
    <script src="/lib/jquery.tablesorter.min.js"></script>
    <style type="text/css">
        #layout {
            width: 80%;
            margin: auto;
        }
        #layout td {
            vertical-align: top;
        }

        #layout td table td {
            vertical-align: middle;
            align-items: center;
        }
    </style>
</head>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . "/lib/tmheader.php"; ?>


<h1>Overall Stats</h1>
<a name="top"></a>
<a href="stat.php">Back to Stats Page</a>
<table id="layout"><tbody>
<tr><td colspan="3"><p>
<?php
$driven = number_format(tmdb_query("SELECT ROUND(sum(activePreviewMileage)) as driven FROM clinchedOverallMileageByRegion")->fetch_assoc()['driven']);
$mapped = number_format(tmdb_query("SELECT ROUND(sum(activePreviewMileage)) as mapped FROM overallMileageByRegion")->fetch_assoc()['mapped']);
$rts = number_format(tmdb_query("SELECT count(*) as routes from routes")->fetch_assoc()['routes']);

echo <<<HTML
Together, users on this site have mapped a total of <b>$driven</b> miles.<br>
Contributors have plotted <b>$mapped</b> miles of <b>$rts</b> routes.
HTML

?>
</p></td></tr>
<tr>
<td><table class="gratable tablesorter" id="connectedClinchedTable">
    <thead>
    <tr>
        <th colspan="4"><?php echo $num ?> Most Clinched Routes</th>
    </tr>
    <tr>
        <th class="sortable">#</th>
        <th class="sortable">Route</th>
        <th class="sortable">Drivers</th>
        <th class="sortable">%</th>
    </tr>

    </thead>
    <tbody>
    <?php

    $sql_command = <<<SQL
        SELECT
          clinchedConnectedRoutes.route,
          routes.route as name,
          routes.banner,
          routes.groupName as city,
          count(*) as amt,
          ROUND(100 * count(*) / 147,2) as pct
        FROM clinchedConnectedRoutes
          JOIN connectedRoutes AS routes ON routes.firstRoot = clinchedConnectedRoutes.route
        WHERE clinched=1 $sysClause
        GROUP BY route
        ORDER BY amt DESC
        LIMIT $num;
SQL;
    
    $res = tmdb_query($sql_command);
    $rank = 1;
    while ($row = $res->fetch_assoc())
    {
        $routeName = ""; //$row['name'];
        if (strlen($row['banner']) > 0) {
            $routeName .= " {$row['banner']} ";
        }
        if (strlen($row['city']) > 0) {
            $routeName .= " ({$row['city']}) ";
        }

        $shield = generate($row['route']);

        echo <<<HTML
        <tr onclick="window.document.location='user/mapview.php?first={$row['route']}'">
            <td>$rank</td>
            <td style="align-items: center"><span class='shield'>$shield<br>$routeName</td>
            <td>{$row['amt']}</td><td>{$row['pct']}%</td>
        </tr> 
HTML;
        $rank+=1;
    }
?>
    </tbody>
</table></td>
<td><table class="gratable tablesorter" id="sectionClinchedTable">
    <thead>
    <tr>
        <th colspan="4"><?php echo $num ?> Most Clinched Route Sections</th>
    </tr>
    <tr>
        <th class="sortable">#</th>
        <th class="sortable">Section</th>
        <th class="sortable">Drivers</th>
        <th class="sortable">%</th>
    </tr>

    </thead>
    <tbody>
    <?php

    $sql_command = <<<SQL
        SELECT
          clinchedRoutes.route,
          routes.route as name,
          regions.name as rg,
          routes.banner,
          routes.city as city,
          count(*) as amt,
          ROUND(100 * count(*) / 147,2) as pct
        FROM clinchedRoutes
          JOIN routes AS routes ON clinchedRoutes.route = routes.root
          JOIN regions ON routes.region = regions.code
        WHERE clinched=1 $sysClause $rtClause
        GROUP BY route
        ORDER BY amt DESC
        LIMIT $num;
SQL;

    $res = tmdb_query($sql_command);
    $rank = 1;
    while ($row = $res->fetch_assoc())
    {
        $routeName = $row['rg'];
        if (strlen($row['banner']) > 0) {
            $routeName .= " {$row['banner']} ";
        }
        if (strlen($row['city']) > 0) {
            $routeName .= " ({$row['city']}) ";
        }

        $shield = generate($row['route']);

        echo <<<HTML
        <tr onclick="window.document.location='hb?r={$row['route']}'">
            <td>$rank</td>
            <td style="align-items: center"><span class='shield'>$shield<br>$routeName</td>
            <td>{$row['amt']}</td><td>{$row['pct']}%</td>
        </tr> 
HTML;
        $rank+=1;
    }
    ?>
    </tbody>
</table></td>
<td><table class="gratable tablesorter" id="sectionDrivenTable">
            <thead>
            <tr>
                <th colspan="4"><?php echo $num ?> Most Driven Route Sections</th>
            </tr>
            <tr>
                <th class="sortable">#</th>
                <th class="sortable">Section</th>
                <th class="sortable">Drivers</th>
                <th class="sortable">%</th>
            </tr>

            </thead>
            <tbody>
            <?php

            $sql_command = <<<SQL
            SELECT
              clinchedRoutes.route,
              routes.route as name,
              regions.name as rg,
              routes.banner,
              routes.city as city,
              count(*) as amt,
              ROUND(100 * count(*) / 147,2) as pct
            FROM clinchedRoutes
              JOIN routes AS routes ON clinchedRoutes.route = routes.root
              JOIN regions ON routes.region = regions.code
            WHERE 1=1 $sysClause $rtClause
            GROUP BY route
            ORDER BY amt DESC
            LIMIT $num;
SQL;

            $res = tmdb_query($sql_command);
            $rank = 1;
            while ($row = $res->fetch_assoc())
            {
                $routeName = $row['rg'];
                if (strlen($row['banner']) > 0) {
                    $routeName .= " {$row['banner']} ";
                }
                if (strlen($row['city']) > 0) {
                    $routeName .= " ({$row['city']}) ";
                }

                $shield = generate($row['route']);

                echo <<<HTML
        <tr onclick="window.document.location='hb?r={$row['route']}'">
            <td>$rank</td>
            <td style="align-items: center"><span class='shield'>$shield<br>$routeName</td>
            <td>{$row['amt']}</td><td>{$row['pct']}%</td>
        </tr> 
HTML;
                $rank+=1;
            }
            ?>
            </tbody>
        </table></td>
</tr><tr><td colspan="3"><a href="#top">Back to Top</a></td> </tr>
</tbody></table>
<?php require $_SERVER['DOCUMENT_ROOT'] . "/lib/tmfooter.php"; ?>
</body>
</html>
