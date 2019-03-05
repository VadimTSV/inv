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
    <b>Компьютеры</b> | <a href="stats-z.php">Заявки</a> | <a href="stats-worker.php">Выполненые заявки</a>
<?php

function BuildStatAray($field){
  global $idb;
  global $gfilters; // Глобальные фильтры
  global $gfilters_sql; // Глобальные фильтры в SQL представлении
  $comps_total = 0;
  $st = $idb->prepare('SELECT COUNT(*) as cnt, if(ISNULL('.$field.'), "", TRIM('.$field.')) as '.$field.' FROM invmain WHERE ' .  $gfilters_sql . ' GROUP BY if(ISNULL('.$field.'), "", TRIM('.$field.'))');
  $result = array();
  if($st->execute($gfilters)){
      while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
          $comps_total += $row['cnt'];
          if(trim($row[$field]) === "") $row[$field] = '"___?___"';
          $result[json_encode($row['cnt'] . ' ' .trim($row[$field]) . ' ')] = $row['cnt'];
      }
  }  
  arsort($result);
  return $result;
}

include_once './piechart.php';
echo '<center><h3>Общая статистика</h3><table style="border: none;">';

$query = "SELECT
COUNT(DISTINCT nHost)
  FROM
invmain";
$res = $idb->query($query);
$row = $res->fetch(PDO::FETCH_NUM);
echo "<tr style='border: none;'><td style='border: none;'>Компьютеров внесено в базу данных</td><td style='border: none;'>" . $row[0] . "</td></tr>";    

global $idb;
$query = "SELECT
COUNT(DISTINCT plSity)
  FROM
invmain";
$res = $idb->query($query);
$row = $res->fetch(PDO::FETCH_NUM);
echo "<tr style='border: none;'><td style='border: none;'>Городов</td><td style='border: none;'>" . $row[0] . "</td></tr>";

global $idb;
$query = "SELECT
COUNT(DISTINCT plCompany)
  FROM
invmain";
$res = $idb->query($query);
$row = $res->fetch(PDO::FETCH_NUM);
echo "<tr style='border: none;'><td style='border: none;'>Организаций</td><td style='border: none;'>" . $row[0] . "</td></tr>";

global $idb;
$query = "SELECT
COUNT(DISTINCT plFilial)
  FROM
invmain";
$res = $idb->query($query);
$row = $res->fetch(PDO::FETCH_NUM);
echo "<tr style='border: none;'><td style='border: none;'>Подразделений</td><td style='border: none;'>" . $row[0] . "</td></tr>";
echo '</table><center>';

$a = BuildStatAray('plSity');
if(count($a) > 1 ){
    MakePieChart('plSity', 'Распределение компьютеров по городам', '', $a);
}

$a = BuildStatAray('plCompany');
if(count($a) > 1 ){
    MakePieChart('plCompany', 'Распределение компьютеров по организациям', '', $a);
}

$a = BuildStatAray('plFilial');
if(count($a) > 1 ){
    MakePieChart('plFilial', 'Распределение компьютеров по подразделениям', '', $a);
}

if(isset($gfilters['plFilial'])){
  $a = BuildStatAray('cOtd');
  if(count($a) > 1 ){
      MakePieChart('cOtd', 'Распределение компьютеров по отделам', '', $a);
  }    
}
?>
</div>
<?php
  
include "htmlend.php";