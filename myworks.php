<?php
global $pagetitle;
$pagetitle = "Мои заявки";
include "htmlstart.php";
include "sw.php";
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 

SubmitWork();
OutHeaders();

?>

<script type="text/javascript">

function OnRadioClick(RadioID, Action) {
   document.getElementById('worker-'+RadioID).disabled = !(Action == 'accept');
   document.getElementById('DropReason-'+RadioID).disabled = !((Action == 'drop') || (Action == 'done')) ;
}

</script>


<h1 class='content'>Заявки на техническое обслуживание</h1>
<div class ='content-text'>
<?php


if(!IsSet($_REQUEST['page'])) $page = 0;
  else $page = $_REQUEST['page'];     
  
  if(IsSet($_REQUEST['page'])) $limit = (($_REQUEST['page']) * 10). ", 10";
  else $limit = 10;
  global $idb;
  $st = $idb->prepare("SELECT 
              COUNT(id) as cnt
            FROM
              zayavki WHERE ".$gfilters_sql." AND worker ='".$curuser['login']."' AND Active='1' and moderated=1");
  if($st->execute($gfilters) and ($st->rowCount() > 0)){
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $num_rows = $row['cnt'];
  } else {
    $num_rows = 0;  
  }
  $st = $idb->prepare("SELECT *             
            FROM
              zayavki WHERE ".$gfilters_sql." AND worker ='".$curuser['login']."' AND Active='1' ORDER BY profile, date DESC LIMIT ".$limit);
  
  if($st->execute($gfilters) and ($st->rowCount() > 0)){
    
    global $idb;
    $query = "SELECT * FROM usr ORDER BY dName";
    
    $mysqlusr =  $idb->query($query);
    $users="";
    $firstuser="";
    
    while($row = $mysqlusr->fetch(PDO::FETCH_ASSOC)){
      if($firstuser == "") $firstuser = $row['login'];  
      $users .= "<option value='".$row['login']."'>".$row['dname']."</option>";
    }
    
    $url = $_SERVER['PHP_SELF']."?page=";
    echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,10)."</p></center>";
    print "<form action='myworks.php' method='POST'>";
    print "<div align='right'><input type='Submit' value='Применить изменения' /></div>";
    print "<table border=1 width=100%>";
    print "<tr>";
    print "<th>№</th>";
    print "<th>Дата подачи</th>";
    print "<th>Заявитель / Модератор</th>";
    print "<th>Заявка</th>";
    print "<th>Операции</th>";
    print "</tr>";
    $otdel = "";
    $i = $page * 10;      
    $k = 0;
    while($row = $st->fetch(PDO::FETCH_ASSOC)){
      if($otdel != $row['profile']){
        print "<tr align=center>";
        print "<td colspan=5><br/><b><i>".$row['profile']."</i></b>&nbsp</td>"; 
        print "</tr>";
        $otdel = $row['profile'];
      }
      $k++;
      print "<tr bgcolor=#FAFAFA>";
      print "<td><nobr>".++$i.".</nobr></td>";
      //print "<td><a class='menu' href=rowdetailshow.php?nHost=".$row['nHost'].">".$row['hProcessor']." / ".$row['hMainboardModel']." / ".$row['hRAM']."Mb RAM</a></td>";
      print "<td>".date("d.m.Y - H:m", strtotime($row['date']))."<hr size=1 color=#bcbcbc noshade /><b>".$row['plFilial']."</b><br />".$row['plCompany']."<br />".$row['plAdress']."</td>";
      print "<td>Кабинет - ".$row['plCab']."<br />Отдел: ".$row['cOtd']."<br />Должность: ".$row['cTitle']."<br /><b>".$row['zFIO']."</b><hr size=1 color=#bcbcbc noshade />Отправлено с <a href='rowdetailshow.php?nHost=".$row['Owner']."'>".$row['Owner']."</a>
          </br>
          <a href='./rowdetailshow.php?nHost=".$row['Owner']."'><img border=0 src='webdata/info.png' title='Подробная информация'></a>
          <a href='./vncout.php?nHost=".$row['Owner']."'><img border=0 src='webdata/uvnc.png' title='Управление'></a></td>";
      print "<td>".nl2br(htmlspecialchars($row['zayavka']))."&nbsp</td>";
      ?><td>
                <nobr><input onclick="OnRadioClick('<?php echo $k; ?>', 'ignore')" type=radio name='<?php echo $k; ?>-action' value='ignore-<?php echo $row['id']; ?>' checked />Не менять статус заявки</nobr><br />
                <?php if($curuser['ismoderator']) {?> 
                <nobr><input onclick="OnRadioClick('<?php echo $k; ?>', 'accept')" type=radio name='<?php echo $k; ?>-action' value='accept-<?php echo $row['id']; ?>' /> Передать на исполнение</nobr><br />
                <div class='leftmargin20'>
                  <i>Исполнитель</i><br />
                  <select disabled name = 'worker-<?php echo $k; ?>' id='worker-<?php echo $k; ?>' size =  multiple value='<?php echo $firstuser; ?>'><?php echo $users; ?>
                  </select>
                </div>             
                <?php }?>
                <nobr><input onclick="OnRadioClick('<?php echo $k; ?>', 'drop')" type=radio name='<?php echo $k; ?>-action' value='drop-<?php echo $row['id']; ?>' />Отказать в обслуживании &nbsp <input onclick="OnRadioClick('<?php echo $k; ?>', 'done')" type=radio name='<?php echo $k; ?>-action' value='done-<?php echo $row['id']; ?>' />Заявка выполнена</nobr><br>
                <div class='leftmargin20'>
                  <i>Коментарий для отказа или отчет о выполненых работах.</i><br />
                  <textarea disabled name='DropReason-<?php echo $k; ?>' id='DropReason-<?php echo $k; ?>' cols='40' rows='2'></textarea>
                </div>
             </td><?php
      print "</tr>";
      $haselement = 1;
    }
    print "</table>";
    print "<div align='right'><input type='Submit' value='Применить изменения' /></div>";
    print "</form>";
    echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,10)."</p></center>";
  } else {
      echo '<h3>Нет заявок для исполнения.</h3>';
  }

?>
</div>

<script language="javascript1.1">
<!--
  for (i = 1; i < <?php echo $k;?>; i++){ 
    OnRadioClick(i, '');  
  }
//-->
</script>

<?php
?>
  
<?php

include "htmlend.php";