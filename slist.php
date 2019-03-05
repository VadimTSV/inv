<?php 
global $pagetitle;
$pagetitle = "Установленное програмное обеспечение";
include "htmlstart.php";
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 

if(isset($_POST['savemodec'])){
  if(isset($_POST['catenabled']) or isset($_POST['licenseenabled'])) {
    if($_POST['savemodec'] != 1) $ncat = $_POST['newcatname'];
    else $ncat = $_POST['catname'];
    if($_POST['savemodel'] != 1) $nlic = $_POST['newlicense'];
    else $nlic = $_POST['license'];
    $slist = $_POST['selsoft'];    
    global $idb;
    $fiels = array('dname' => ':dname');
    $values = array('dname' => '');
    if(isset($_POST['catenabled'])) {
        $fiels['sCat'] = ':sCat';
        $values['sCat'] = $ncat;
    }
    if(isset($_POST['licenseenabled'])) {
        $fiels['sLicense'] = ':sLicense';
        $values['sLicense'] = $nlic;
    }
    foreach ($fiels as $key => $value) {
        $set[] = $key .' = ' . $value;
    }
    $st = $idb->prepare('INSERT INTO softlist ('.implode(',', array_keys($fiels)).') VALUES ('.implode(',', $fiels).') ON DUPLICATE KEY UPDATE ' . implode(',', $set));           
    foreach($slist as $dname){  
        $values['dname'] = mb_strtolower(urldecode($dname), 'UTF-8');
        $st->execute($values);
    }
  }
}

OutHeaders();
?>
<h1 class='content'>Установленное програмное обеспечение.</h1>
<div class ='content-text'>

<?php

global $allsoft;
$allsoft = array();
  
function add_str_to_allsoft($str){
  if(trim($str) != ""){
    global $allsoft;  
    if(isset($allsoft[$str])) $allsoft[$str]++;
    else $allsoft[$str] = 1;
  }
}

global $sCatList;
global $CatFull;
global $LicenseFull;

function Get_sCatList(){
  global $sCatList;
  global $CatFull;
  global $LicenseFull;
  global $idb;
  $query = "SELECT softlist.dname, softlist.sLicense, softlist.sLicenseFileName, softlist.sBlackList, softlist.sCat, sLicenseID, ((softlist.sLicenseID IS NOT NULL) or (softlist.sLicenseID > 0)) AS havelic FROM softlist";  
  $res = $idb->query($query);
  while($row = $res->fetch(PDO::FETCH_ASSOC)){
    $rec['sCat'] = $row['sCat'];
    $rec['sLicense'] = $row['sLicense'];
    $rec['sLicenseFileName'] = $row['sLicenseFileName'];
    $rec['sBlackList'] = $row['sLicenseFileName'];
    $rec['havelic'] = $row['havelic'];
    $rec['sLicenseID'] = $row['sLicenseID'];
    $sCatList[mb_strtolower($row['dname'], 'UTF-8')] = $rec;
    $CatFull[$row['sCat']] = 0;
    $LicenseFull[ $row['sLicense']] = 0; 
  };
}

  Get_sCatList();
  $where = $gfilters_sql;
  global $idb;
  $st = $idb->prepare("SELECT 
              invmain.sFullList
            FROM
              invmain
            WHERE ".$where);
  
  if($st->execute($gfilters) and ($st->rowCount() > 0)){
    while($row = $st->fetch(PDO::FETCH_ASSOC)){
      $csoft = explode("\r\n", $row['sFullList']);
      foreach($csoft as $soft) add_str_to_allsoft($soft);
    }
  }
  
  $softfiltred = array();
  $actlic = array();
  //if(isset($_GET['catfilter']) or isset($_GET['licfilter']) and !isset($_GET['nofilter'])){
    $tmp = array();     
    foreach($allsoft as $index=>$value){
      $lowindex = mb_strtolower($index, 'UTF-8');
      $sCat = isset($sCatList[$lowindex]) ? $sCatList[$lowindex] : array('sCat' => '', 'sLicense' => '');
      if((!isset($_GET['catfilter']) or ($sCat['sCat'] == $_GET['catfilter']))){
         if($sCat['sLicense'] != "")$actlic[$sCat['sLicense']] = 0; 
         if((!isset($_GET['licfilter']) or ($sCat['sLicense'] == $_GET['licfilter']))) {
           $tmp[$index] = $value;
         }
      } 
    }
    $allsoft = $tmp;
  //}
  if(!IsSet($_REQUEST['page'])) $page = 0;
  else $page = (int)$_REQUEST['page'];     
  
  if(count($actlic) > 0) {
    if(isset($_GET['catfilter']) and ($_GET['catfilter'] != ""))
      if(isset($_GET['licfilter']) and ($_GET['licfilter'] != "")) $licpanel = "<a href='slist.php?catfilter=".urlencode($_GET['catfilter'])."'>Все</a>";
      else $licpanel = "<b>Все</b>";
    else if(isset($_GET['licfilter']) and ($_GET['licfilter'] != "")) $licpanel = "<a href='slist.php'>Все</a>";
         else $licpanel = "<b>Все</b>";
    foreach($actlic as $index=>$value) 
      if(isset($_GET['catfilter']))
        if(!isset($_GET['licfilter']) or $_GET['licfilter'] != $index) $licpanel .= " | <a href='slist.php?catfilter=".urlencode($_GET['catfilter'])."&licfilter=".urlencode($index)."'>".htmlspecialchars($index)."</a>";
        else $licpanel .= " | <b>".htmlspecialchars($index)."</b>"; 
      else if($_GET['licfilter'] != $index) $licpanel .= " | <a href='slist.php?licfilter=".urlencode($index)."'>".htmlspecialchars($index)."</a>";
           else $licpanel .= " | <b>".htmlspecialchars($index)."</b>";    
  }
  
   $paramstr = "";
   if(isset($_GET['catfilter'])) $paramstr .= "catfilter=".urlencode($_GET['catfilter']); 
   if(isset($_GET['licfilter']))
     if($paramstr != "") $paramstr .= "&licfilter=".urlencode($_GET['licfilter']);
     else $paramstr .= "licfilter=".urlencode($_GET['licfilter']);   
  
  if($paramstr != "") $url = $_SERVER['PHP_SELF']."?".$paramstr."&page=";
  else $url = $_SERVER['PHP_SELF']."?page=";
  echo "<a href='smain.php'>Вернуться к списку ПО</a><br /><br />";
  if(isset($licpanel) and ($licpanel != "")) echo "<b>Тип лицензии: </b><br />".$licpanel;
  echo "<center><p class='content'>".LeftRight(count($allsoft), $page, $url ,20)."</p></center>";
  echo "<center>";
  echo "<form method='POST' action='".$url.$page."'>";
  print "<table border=1 width=100%>";
  print "<tr>";
  print "<th>№</th>";
  print "<th></th>";
  print "<th>Наименование програмного обеспечения</th>";
  print "<th>Категория ПО</th>";
  print "<th>Тип лицензии</th>";
  print "<th>Лицензия</th>";
  print "<th>Количество инсталяций</th>";
  print "</tr>";
  
  ksort($allsoft);
  $i = 0;
  $softfiltred = array();
  foreach($allsoft as $index=>$value){
    if(($i >= ($page * 20)) and ($i < ($page * 20 + 20))) {
      $lowindex = mb_strtolower($index, 'UTF-8');
      $sCat = isset($sCatList[$lowindex]) ? $sCatList[$lowindex] : array('sCat' => '', 'sLicense' => '', 'havelic' => '', 'sLicenseID' => '');
      ?>
      <tr><td><?php echo ($i + 1);?></td>
      <td><input type=checkbox name='selsoft[]' value='<?php echo htmlspecialchars($index, ENT_QUOTES);?>'></td>
      <td align="left"><?php echo '<a href="complist.php?softfilter='.urlencode($index).'">'.htmlspecialchars($index);?></a></td>
      <td><?php echo $sCat['sCat'];?></td>
      <td><?php echo $sCat['sLicense'];?></td>
      <td>
      <?php
        if($sCat['havelic']) echo '<a href="editlic.php?sLicenseID='.$sCat['sLicenseID'].'"><img src="webdata/atach.png" alt="Изменить" title="Изменить"></a>'; 
      ?>
      </td> 
      <td><?php echo $value;?></td>
      </tr>
      <?php
    }
    $i++;
  }
  print "</table>";
  echo "</center>";
  ?>
     
    <div align="right">
    <table class='noframe'>
    <tr class='form'>
      <td class='form' colspan="2"><input type="checkbox" name='catenabled' value=1 checked><b>Переместить выбранное ПО:</b></td>
      <td class='form' colspan="2"><input type="checkbox" name='licenseenabled' value=1 checked><b>Изменить тип лицензии выбранного ПО:</b></td>
    </tr>
    <tr class='form'>
      <td class='form'><nobr> <input type='radio' name='savemodec' value='1' checked />В существующую категорию</nobr></td>
      <td class='form'>
        <select name='catname'>
        <?php
          $catname = isset($_POST['catname']) ? $_POST['catname'] : '';
          foreach($CatFull as $index=>$value) {
            if($catname == $index) $s = 'selsected';
            else $s = '';
            echo "<option value='".htmlspecialchars($index, ENT_QUOTES)."' ".$s.">".htmlspecialchars($index)."</option>";
          }
        ?>   
        </select>
      </td>
      <td class='form'><nobr> <input type='radio' name='savemodel' value='1' checked />Существующий тип</nobr></td>
      <td class='form'>
        <select name='license'>
        <?php
          $license = isset($_POST['license']) ? $_POST['license'] : '';  
          foreach($LicenseFull as $index=>$value) {
            if($license == $index) $s = 'selsected';
            else $s = '';
            echo "<option value='".htmlspecialchars($index, ENT_QUOTES)."' ".$s.">".htmlspecialchars($index)."</option>";
          }
        ?>   
        </select>
      </td>
    </tr>
    <tr class='form'>
      <td class='form'><nobr><input type='radio' name='savemodec' value='0' />В новую категорию<nobr></td>
      <td class='form'><input name='newcatname' />&nbsp&nbsp&nbsp</td>
      <td class='form'><nobr><input type='radio' name='savemodel' value='0' />Новый тип<nobr></td>
      <td class='form'><input name='newlicense' />&nbsp&nbsp&nbsp</td>
    </tr>
      <tr class='form'><td class='form'></td><td class='form'></td><td class='form'></td><td class='form'><div align="right"><input type=submit value='Применить'></div></td></tr>
    </table>
    </div>
  <?php
  print "</form>";
  echo "<center><p class='content'>".LeftRight(count($allsoft), $page, $url ,20)."</p></center>";
  if(isset($licpanel) and ($licpanel != "")) echo "<b>Тип лицензии: </b><br />".$licpanel;
  echo "<br /><br /><a href='smain.php'>Вернуться к списку ПО</a>";
  
?>
</div>

<?php
  
include "htmlend.php";