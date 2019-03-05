<?php
global $pagetitle;
$pagetitle = "Выполненые заявки";
include "htmlstart.php";
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 
OutHeaders();
?>
<h1 class='content'>Выполненые заявки на техническое обслуживание.</h1>
<div class ='content-text'>

<?php
  
  $users = array();
  global $idb;
$query = "SELECT * FROM usr";
  $res = $idb->query($query);
  while($row = $res->fetch(PDO::FETCH_ASSOC)){
    $users[$row['login']] = $row['dname'];  
  }
  
  if(!IsSet($_REQUEST['page'])) $page = 0;
  else $page = $_REQUEST['page'];     
  
  $df=0;
  if(IsSet($_GET['donefilter'])) {
    $df = $_GET['donefilter']; 
    switch($_GET['donefilter']) {
      case 0: { $donefilter = ''; // Все заявки
                break;
              }
      case 1: { $donefilter = ' AND NOT (enddate IS NULL) AND NOT (answerrate IS NULL)'; // Подтвержденные заявки
                break;
              }
      case 2: { $donefilter = ' AND enddate IS NULL'; // Неподтверждённые заявки
                break;
              }
      case 3: { $donefilter = ' AND answerrate IS NULL AND NOT (enddate IS NULL)'; // Отказано в обслуживании
                break;
              }
    }
  } else $donefilter = '';
    
  global $curuser;
  $where = $gfilters_sql . " and active=0 ".$donefilter;  
  if(IsSet($_REQUEST['page'])) $limit = (($_REQUEST['page']) * 10). ", 10";
  else $limit = 10;
  global $idb;
  $st = $idb->prepare("SELECT COUNT(id) as cnt
            FROM
              zayavki WHERE ".$where);
  if($st->execute($gfilters) and ($st->rowCount() > 0)){
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $num_rows = $row['cnt'];
  } else {
     $num_rows = 0; 
  }
  $st = $idb->prepare("SELECT *             
            FROM
              zayavki WHERE ".$where." ORDER BY date DESC LIMIT ".$limit);
   
  if($st->execute($gfilters) and ($st->rowCount() > 0)){
    $url = $_SERVER['PHP_SELF']."?donefilter=".$df."&page=";
    echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,10)."</p></center>";
    if($df == 0) print "<b>Все</b> | ";
    else print "<a href=zdone.php?donefilter=0>Все</a> | ";
    
    if($df == 1) print "<b>Подтвержденные</b> | ";
    else print "<a href=zdone.php?donefilter=1>Подтвержденные</a> | ";
    
    if($df == 2) print "<b>Неподтверждённые</b> | ";
    else print "<a href=zdone.php?donefilter=2>Неподтверждённые</a> | ";
    
    if($df == 3) print "<b>Отказано в обслуживании</b>";
    else print "<a href=zdone.php?donefilter=3>Отказано в обслуживании</a>";
    print "<table border=1 width=100%>";
    print "<tr>";
    print "<th>№</th>";
    print "<th>Дата подачи / <br /> Дата выполнения</th>";
    print "<th>Заявитель</th>";
    print "<th>Заявка</th>";
    print "<th>Исполнитель / <br />Выполнено</th>";
    print "<th>Коментарий пользователя</th>";
    print "<th>Оценка</th>";
    print "</tr>";
    $otdel = "";
    $i = $page * 10;      
    $k = 0;
    while($row = $st->fetch(PDO::FETCH_ASSOC)){
      if($otdel != $row['profile']){
        print "<tr align=center>";
        print "<td colspan=7><br/><b><i>".$row['profile']."</i></b>&nbsp</td>"; 
        print "</tr>";
        $otdel = $row['profile'];
      }
      $k++;
      print "<tr bgcolor=#FAFAFA>";
      print "<td><nobr>".++$i.".</nobr></td>";
      print "<td>".date("d.m.Y - H:m", strtotime($row['date']))."<hr size=1 color=#bcbcbc noshade /><b>".$row['plFilial']."</b><br />".$row['plCompany']."<br />".$row['plAdress']."</td>";
      print "<td>Кабинет - ".$row['plCab']."<br />Отдел: ".$row['cOtd']."<br />Должность: ".$row['cTitle']."<br /><b>".$row['zFIO']."</b><hr size=1 color=#bcbcbc noshade />Отправлено с <a href='rowdetailshow.php?nHost=".$row['Owner']."'>".$row['Owner']."</a>
          </br>
          <a href='./rowdetailshow.php?nHost=".$row['Owner']."'><img border=0 src='webdata/info.png' title='Подробная информация'></a>
          <a href='./vncout.php?nHost=".$row['Owner']."'><img border=0 src='webdata/uvnc.png' title='Управление'></a></td>";
      print "<td>".nl2br(htmlspecialchars($row['zayavka']))."&nbsp</td>";
      $worker = isset($users[$row['worker']]) ? $users[$row['worker']] : '';
      print "<td>".$worker." / <br />".$row['answer']."&nbsp</td>";
      print "<td>".$row['usercomment']."&nbsp</td>";
      print "<td>".$row['answerrate']."&nbsp</td>";
      print "</tr>";
      $haselement = 1;
    }
    print "</table>";
    if($df == 0) print "<b>Все</b> | ";
    
    else print "<a href=zdone.php?donefilter=0>Все</a> | ";
    
    if($df == 1) print "<b>Подтвержденные</b> | ";
    else print "<a href=zdone.php?donefilter=1>Подтвержденные</a> | ";
    
    if($df == 2) print "<b>Неподтверждённые</b> | ";
    else print "<a href=zdone.php?donefilter=2>Неподтверждённые</a> | ";
    
    if($df == 3) print "<b>Отказано в обслуживании</b>";
    else print "<a href=zdone.php?donefilter=3>Отказано в обслуживании</a>";
  
    echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,10)."</p></center>";
  } else {
      echo '<h3>Нет выполненых заявок</h3>';
  }  
?>
</div>

<?php

include "htmlend.php";