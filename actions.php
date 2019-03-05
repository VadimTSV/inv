<?php 
global $pagetitle;
$pagetitle = "Журнал событий";
include "htmlstart.php";
OutHeaders();
$nHost = isset($_REQUEST['filter_nHost']) ? $_REQUEST['filter_nHost'] : '';
if($nHost != '')echo "<h1 class='content'>Журнал событий: ".$nHost."</h1>";
else echo "<h1 class='content'>Журнал событий</h1>"; 
?>
<ul>
<?php
  $haselement = 0;
global $idb;
$query = "SELECT DISTINCT
              EventType
            FROM
              eventlog ORDER BY EventType";
  $res = $idb->query($query);
  $filter_nHost = '';
  while($EventTypes = $res->fetch(PDO::FETCH_ASSOC)){
    if($EventTypes['EventType'] != ""){
        $filter_nHost = IsSet($_REQUEST['filter_nHost'])?"&filter_nHost=".$_REQUEST['filter_nHost']:"";
        echo "<li><a class='menu' href='actions.php?EventType=".urlencode($EventTypes['EventType']).$filter_nHost."'>";
        //echo $_REQUEST['EventType'];
        if(isSet($_REQUEST['EventType']) and ($_REQUEST['EventType'] == $EventTypes['EventType'])) echo "<strong>".$EventTypes['EventType']."</strong>"; 
        else echo "".$EventTypes['EventType']."";  
        echo "</a></li>";
      }
    }

?>
</ul>
<?php
  if(!IsSet($_REQUEST['page'])) $page = 0;
  else $page = (int)$_REQUEST['page'];      
  if(IsSet($_REQUEST['EventType'])) echo "<h1 class='content'>Coбытия: ".$_REQUEST['EventType']."</h1>";
  else echo "<h1 class='content'>Coбытия</h1>";
  global $curuser;
  global $gfilters; // Глобальные фильтры
  global $gfilters_sql; // Глобальные фильтры в SQL представлении 
  global $idb;
  $where = $gfilters_sql;
  $params = $gfilters;
  if(IsSet($_REQUEST['EventType'])) {
      $where .= ' AND (EventType = :EventType) ';
      $params['EventType'] = $_REQUEST['EventType']; 
  }
  if(IsSet($_REQUEST['filter_nHost'])) {
      $where .= ' AND (nHost = :nHost) ';
      $params['nHost'] = $_REQUEST['filter_nHost']; 
  }
  if(IsSet($_REQUEST['page'])) {
      $limit = (((int)$_REQUEST['page']) * 20). ", 20";
  } else {
      $limit = 20;   
  }
  $st = $idb->prepare('SELECT Count(id) as cnt
            FROM
              eventlog, invmain WHERE (EvetOwner = nHost) AND '.$where);
  if($st->execute($params) and ($st->rowCount() > 0)) {   
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $num_rows = $row['cnt'];
    $st = $idb->prepare("SELECT 
                  EventDate, EventText, EvetOwner, id
                FROM
                  eventlog, invmain WHERE (EvetOwner = nHost) AND ".$where." ORDER BY EventDate DESC LIMIT ".$limit);

    if($st->execute($params) and ($st->rowCount() > 0)){
        if(!IsSet($_REQUEST['EventType'])) $url = $_SERVER['PHP_SELF']."?".$filter_nHost."&page=";
        else $url = $_SERVER['PHP_SELF']."?EventType=".urlencode($_REQUEST['EventType']).$filter_nHost."&page=";
        echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,20)."</p></center>";

        echo "<div class='content-text'><table border=1 width=100%><tr><th>Дата</th><th>Источник</th><th>Событие</th></tr>";

        while($row = $st->fetch(PDO::FETCH_ASSOC)){
          if(strlen($row['EventText']) > 255) $s = mb_substr($row['EventText'],0, 255, 'UTF-8')."..."; 
          else $s = $row['EventText'];
          $s = nl2br(htmlspecialchars($s, ENT_QUOTES, 'UTF-8'));
          echo "<tr><td>".date("d.m.Y - H:i", strtotime($row['EventDate']))."</td><td><a href='rowdetailshow.php?nHost=".$row['EvetOwner'].$filter_nHost."'>".$row['EvetOwner']."</a></td><td><a href='eventview.php?eventID=".$row['id']."'>".$s."</a></td></tr>";
          $haselement = 1;
        }
        echo "</table></div></p>";
        echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,20)."</p></center>";
      }    
  } else {
      echo '<h3>Нет событий</h3>';
  }
  
  //echo "mysql_num_rows(intres) = ".$num_rows; 
  
  

  
include "htmlend.php";
