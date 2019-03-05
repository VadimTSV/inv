<?php
global $pagetitle;
$pagetitle = "Статистика";
include "htmlstart.php";
include "sw.php";
global $idb;
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 
SubmitWork();
OutHeaders();
?>
<h1 class='content'>Статистика работы системы.</h1>
<div class ='content-text'>
<a href="stats-comp.php">Компьютеры</a> | <b>Заявки</b> | <a href="stats-worker.php">Выполненые заявки</a>
<?php
include_once './piechart.php';
include_once './report_date_filter.php';
global $z_filters;
$st = $idb->prepare('SELECT  COUNT(id) AS cnt, 
                        SUM(active) AS active, 
                        SUM(NOT moderated) AS need_moder, 
                        SUM(NOT active AND NOT (enddate IS NULL) AND NOT (answerrate IS NULL)) AS done,
                        SUM(NOT active AND enddate IS NULL) AS wait_user,
                        SUM(NOT active AND answerrate IS NULL AND NOT (enddate IS NULL)) AS aborted
                    FROM zayavki WHERE ' . $z_filters['SQLWhere'] . ' AND ' . $gfilters_sql);
//print_r($st);
if($st->execute($gfilters)){
    if(($st->rowCount() > 0) and  ($row = $st->fetch(PDO::FETCH_ASSOC)) and ($row['cnt'] > 0)){
        if($row['need_moder']) $a[json_encode('Ожидают модерации')] = $row['need_moder'];
        if($row['active']) $a[json_encode('В стадии выполнения')] = $row['active'];
        if($row['done']) $a[json_encode('Выполнено')] = $row['done'];
        if($row['aborted']) $a[json_encode('Отказано в обслуживании')] = $row['aborted'];
        ?>   
            <center>
            <h3>Общая статистика по заявкам</h3> 
            <table style="border: none;">             
             <tr style="border: none;">
                <td style="border: none;">Заявок всего:</td>
                <td style="border: none;"><?php echo $row['cnt']?></td>
             </tr>
             <tr style="border: none;">
                <td style="border: none;">Ожидают модерации:</td>
                <td style="border: none;"><?php echo $row['need_moder']?></td>
             </tr>
             <tr style="border: none;">
                <td style="border: none;">В стадии выполнения:</td>
                <td style="border: none;"><?php echo $row['active']?></td>
             </tr>
             <tr style="border: none;">
                <td style="border: none;">Выполнено:</td>
                <td style="border: none;"><?php echo $row['done']?></td>
             </tr>             
             <tr style="border: none;">
                <td style="border: none;">Выполнение подтверждено пользователями:</td>
                <td style="border: none;"><?php echo $row['done'] - $row['wait_user']?></td>
             </tr>             
             <tr style="border: none;">
                <td style="border: none;">Ожидают подтверждения выполнения пользователем:</td>
                <td style="border: none;"><?php echo $row['wait_user']?></td>
             </tr>
             <tr style="border: none;">
                <td style="border: none;">Отказано в обслуживании:</td>
                <td style="border: none;"><?php echo $row['aborted']?></td>
             </tr>             
             </table>
            </center>     
        <?php
        MakePieChart('zgraph', 'Заявки', '', $a);
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