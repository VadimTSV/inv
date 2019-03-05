<?php
global $pagetitle;
$pagetitle = "Статистика";
include "htmlstart.php";
include "sw.php";
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 
SubmitWork();
OutHeaders();
?>
<h1 class='content'>Статистика работы системы.</h1>
<div class ='content-text'>
<a href="stats-comp.php">Компьютеры</a> | <a href="stats-z.php">Заявки</a> | <b>Выполненые заявки</b>
<?php

include_once './piechart.php';

include_once './piechart.php';
include_once './report_date_filter.php';
global $z_filters;
$st = $idb->prepare('SELECT  COUNT(worker) AS cnt, dname                        
                    FROM zayavki, usr WHERE worker=login AND NOT ISNULL(worker) AND ' . $z_filters['SQLWhere'] . ' AND ' . $gfilters_sql .' GROUP BY worker');
//print_r($st);
if($st->execute($gfilters)){
    $a = array();
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $a[json_encode($row['cnt'] . ' ' . $row['dname'])] = $row['cnt'];
    }
    if(count($a) > 0) {
        arsort($a);
        MakePieChart('workers', 'Выполненые заявки по сотрудникам', '', $a);
    } else {
        echo '<h3>За указанный период ничего не найдено</h3>';
    }
} else {
    print_r($st->errorInfo());
}
?>
</div>
<?php
  
include "htmlend.php";