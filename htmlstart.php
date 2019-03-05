<?php
include_once 'admin/mysqlconnect.php';
include_once "inituser.php";

global $pagetitle; // Заголовок страницы
global $idb; // База данных
global $curuser; // Текущий пользователь
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 
global $userfilters; // фильтры пользователя
global $userfilters_sql; // фильтры пользователя
global $filter_fields_order; // Список полей доступных для глобальной фильтрации в порядке иерархии


if(!isset($_SESSION['user_id'])) {
    header("location:login.php?target=http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
    exit();
}

function StrCut($srt, $maxlen){
  if(strlen($srt) > 255) return mb_substr($srt,0, 255, 'UTF-8')."...";
  else return $srt;
}

function LeftRight($records,$r_start,$URL,$inpage) {
    $str="";

    if ($records<=$inpage) return;
    if ($r_start!=0) {
        $str.="<a href=".$URL."0>&lt;&lt</a> ";
        $str.="<a href=$URL".($r_start-1).">&lt;</a> ";
        }
    else $str.="&lt;&lt &lt; ";

    if ($r_start==0) {$sstart=$r_start-0;$send=$r_start+10;}
    if ($r_start==1) {$sstart=$r_start-1;$send=$r_start+9;}
    if ($r_start==2) {$sstart=$r_start-2;$send=$r_start+8;}
    if ($r_start==3) {$sstart=$r_start-3;$send=$r_start+7;}
    if ($r_start==4) {$sstart=$r_start-4;$send=$r_start+6;}
    if ($r_start>=5) {$sstart=$r_start-5;$send=$r_start+5;}

    if ($send*$inpage>$records) $send=$records/$inpage;
    if ($sstart<0) $sstart=0;

    if ($records%$inpage==0) $add=0; else $add=1;

    for ($i=$sstart;$i<$send;$i++) {
        if ($i==$r_start) $str.=" <B>".($i+1)."/".(intval($records/$inpage)+$add)."</B> | ";
        else $str.="<a href=$URL".($i)."><U><B>".($i+1)."</B></U></a> |  ";
        }

    if ($r_start+(1-$add)<intval($records/$inpage)) {
        $str.=" <a href=$URL".($r_start+1).">&gt;</a>";
        $str.=" <a href=$URL".(intval($records/$inpage)-(1-$add)).">&gt;&gt;</a>";
        }
    else $str.=" &gt; &gt;&gt";
    return($str);
    }

function OutFilterBar($field){
  global $curuser;
  global $idb;
  global $gfilters; // Глобальные фильтры
  global $filter_fields_order;
  global $userfilters; // фильтры пользователя
  global $userfilters_sql; // фильтры пользователя
  $topfilter_sql = '(true';
  $topfilter = array();
  $i = array_search($field, $filter_fields_order) - 1;
  for ($index = $i; $index >= 0; $index--) {
      if(isset($gfilters[$filter_fields_order[$index]])){
        $topfilter_sql .= ' AND ('. $filter_fields_order[$index] . ' = :' . $filter_fields_order[$index] . ')';
        $topfilter[$filter_fields_order[$index]] = $gfilters[$filter_fields_order[$index]]; 
      }
  }
  $topfilter_sql .= ')';
  $st = $idb->prepare('SELECT DISTINCT
              IF(ISNULL('.$field.'), "", '.$field.') AS '.$field.'
            FROM
              invmain WHERE '.$userfilters_sql.' AND '.$topfilter_sql.' ORDER BY '.$field);

  if($st->execute(array_merge($userfilters, $topfilter))){
    while($row = $st->fetch(PDO::FETCH_ASSOC)){
      if($row[$field] === "") $disp = '"___?___"';
      else $disp = trim($row[$field]);
      if(IsSet($gfilters[$field]) and ($row[$field] == $gfilters[$field])) print "<a class='menu'><strong>".$disp."</a></strong><br>" ;
      else print "<a class='menu' href='".$_SERVER['PHP_SELF']."?filter_".$field."=".$row[$field]."'>".$disp."</a><br>";
    }
  }
}

global $newmessages;

global $idb;
$query = "SELECT SUM(usermsges.isNew) AS newm FROM usermsges
          WHERE recipient='".$curuser['login']."'";
$res = $idb->query($query);
if($row = $res->fetch(PDO::FETCH_ASSOC)) $newmessages = $row['newm'];


function OutHeaders() {

global $pagetitle; // Заголовок страницы
global $idb; // База данных
global $curuser; // Текущий пользователь
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 
global $newmessages;

if($curuser['ismoderator']){  
  $st = $idb->prepare("SELECT COUNT(id) as cnt FROM zayavki WHERE  (Active=1) AND (moderated=0) AND".$gfilters_sql);
  if($st->execute($gfilters) and ($st->rowCount() > 0)) {
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $z_active = $row['cnt'];
  } else {
      $z_active = 0;
  }
}
if($curuser['login'] != ""){
  $st = $idb->prepare("SELECT COUNT(id) as cnt FROM zayavki WHERE (worker = :worker) AND (Active=1) AND ".$gfilters_sql);
  if($st->execute(array_merge($gfilters, array('worker' => $curuser['login']))) and ($st->rowCount() > 0)) {
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $uz_active = $row['cnt'];
  } else {
      $uz_active = 0;
  }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN">
<html>
  <head>
  <LINK href="style.css" rel="stylesheet" type="text/css">
  <LINK href="datepicker.css" rel="stylesheet" type="text/css">
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="generator" content="PSPad editor, www.pspad.com">
  <title></title>
  </head>
  <body>


<table class='clear' width=100%>
 <tr class='clear'>
   <td class='clear'>
     <?php print $curuser['title'].', '.$curuser['dname']; ?> | <a href=login.php?logout=1>Выход</a>
     <h1 class="toptitle"><?php

       if(IsSet($gfilters['plSity'])){
         if($gfilters['plSity'] == "") echo '"___?___"';
         else echo $gfilters['plSity'];
       } else echo "Все записи в базе данных";

     ?></h1>
     <h2 class="toptitle"><?php echo isset($gfilters['plCompany']) ? $gfilters['plCompany'] : '';?></h2>
     <h2 class="toptitle"><?php echo isset($gfilters['plFilial']) ? $gfilters['plFilial'] : '';?></h2>
   </td><td class='clear' align="right"><a href="http://citsk.ru/forum"><img border=0 src="./webdata/weblogo.png" alt="InvCollector" height="60" width="125" align="middle"></a></td>
 </tr>
</table>

<h1>
<?php
echo $pagetitle;
?>
</h1>


<div id="left">
<a href="index.php"><h1 class="menu" > Информационная база</h1></a>
<p class='menuframe'>
  <a href="actions.php" class="menu"><img border=0 src="webdata/event.png" align="middle"> События</a>
</p>
<p class='menuframe'>
  <a href="complist.php" class="menu"><img border=0 src="webdata/comp.png" align="middle"> Компьютеры</a>
</p>
<p class='menuframe'>
  <a href="phones.php" class="menu"><img border=0 src="webdata/phones.png" align="middle"> Справочник</a>
</p>
<p class='menuframe'>
  <a href="smain.php" class="menu"><img border=0 src="webdata/install.png" align="middle"> ПО</a>
</p>
<p class='menuframe'>
  <a href="stats-comp.php" class="menu"><img border=0 src="webdata/stats.png" align="middle"> Статистика</a>
</p>
<?php if($curuser['isadmin']){
  echo "<p class='menuframe'><a href='config-main.php' class='menu'><img border=0 src='webdata/gear.png' align='middle'> Настройки</a></p>";    
} ?>
<br>
<h1 class="menu">Заявки на техническое обслуживание</h1>
<?php if($curuser['ismoderator']) ?>
<p class='menuframe'><a href='zlist.php' class='menu'>
  <img border=0 src='webdata/moder.png' align='middle'> Модерация
  <?php
    if($z_active > 0) echo " <strong>(".$z_active.")</strong>";
  ?>
</a></p>
<?php;?>
<p class='menuframe'><a href="myworks.php" class="menu">
  <img border=0 src="webdata/q.png" align="middle"> Для исполнения
  <?php
    if($uz_active > 0) echo " <strong>(".$uz_active.")</strong>";
  ?>
</a></p>
<?php if($curuser['isadmin']) echo "<p class='menuframe'><a href='zactive.php' class='menu'><img border=0 src='webdata/lamp.png' align='middle'> В стадии выполнения</a></p>";?>
<p class='menuframe'><a href="zdone.php" class="menu"><img border=0 src="webdata/done.png" align="middle">  Выполненые</a></p>
<br>

<h1 class="menu">Сообщения</h1>
<?php if($curuser['ismoderator']) ?>
<p class='menuframe'><a href="mymessages.php" class="menu">
  <img border=0 src="webdata/msg.png" align="middle"> Мои сообщения
  <?php
    if($newmessages > 0) echo " <strong>(".$newmessages.")</strong>";
  ?>
</a></p>
<p class='menuframe'><a href="newmessage.php" class="menu"><img border=0 src="webdata/nmsg.png" align="middle">  Рассылка</a></p>
<p class='menuframe'><a href='amessages.php' class='menu'><img border=0 src='webdata/newspaper_go.png' align='middle'> Активные рассылки</a></p>
<br>  
    
<h1 class="menu">Фильтр</h1>
<p class="menuframe">
<br><strong>Город</strong><br>
<?php
  if(isset($gfilters['plSity'])){
      echo "<a class='menu' href='" . $_SERVER['PHP_SELF']. "?null_filter_plSity=1'>Все</a><br />";
  } else {
      echo "<strong><a class='menu' href='" . $_SERVER['PHP_SELF']. "?null_filter_plSity=1'>Все</a><br /></strong>";
  }
  OutFilterBar("plSity");
  if(isset($gfilters['plSity'])){
    ?>
    <br><strong>Организация</strong><br>
    <?php
      if(isset($gfilters['plCompany'])){
          echo "<a class='menu' href='" . $_SERVER['PHP_SELF']. "?null_filter_plCompany=1'>Все</a><br />";
      } else {
          echo "<strong><a class='menu' href='" . $_SERVER['PHP_SELF']. "?null_filter_plCompany=1'>Все</a><br /></strong>";
      }
      OutFilterBar("plCompany");
      if(isset($gfilters['plCompany'])){
      ?>

      <br><strong>Подразделение</strong><br>
      <?php
        if(isset($gfilters['plFilial'])){
          echo "<a class='menu' href='" . $_SERVER['PHP_SELF']. "?null_filter_plFilial=1'>Все</a><br />";
        } else {
            echo "<strong><a class='menu' href='" . $_SERVER['PHP_SELF']. "?null_filter_plFilial=1'>Все</a><br /></strong>";
        }
        OutFilterBar("plFilial");
        if(isset($gfilters['plFilial'])){
        ?>
        <br><strong>Отдел</strong><br>
        <?php
          if(isset($gfilters['cOtd'])){
            echo "<a class='menu' href='" . $_SERVER['PHP_SELF']. "?null_filter_cOtd=1'>Все</a><br />";
          } else {
              echo "<strong><a class='menu' href='" . $_SERVER['PHP_SELF']. "?null_filter_cOtd=1'>Все</a><br /></strong>";
          }
          OutFilterBar("cOtd");
        }
      }
    }
    ?>

</p>
<br>
</div>

<div id="content">


<?php
}
