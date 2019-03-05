<?php
global $pagetitle;
$pagetitle = "Установленное програмное обеспечение";
include "htmlstart.php";
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 
OutHeaders();
?>
<h1 class='content'>Установленное програмное обеспечение.</h1>
<div class ='content-text'>
<center>
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
  $query = "SELECT * FROM softlist ORDER BY sLicense";  
  $res = $idb->query($query);
  while($row = $res->fetch(PDO::FETCH_ASSOC)){
    $rec['sCat'] = $row['sCat'];
    $rec['sLicense'] = $row['sLicense'];
    $rec['sLicenseFileName'] = $row['sLicenseFileName'];
    $rec['sBlackList'] = $row['sLicenseFileName'];
    $sCatList[$row['dname']] = $rec;
    $CatFull[$row['sCat']] = 0;
    $LicenseFull[$row['sLicense']] = 0; 
  };
}

  Get_sCatList();
  global $curuser;
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
  
  echo "<h2>Полный список програмного обеспечения по категориям.</h2>";
  print "<table border=1 width='95%'>";
  print "<tr>";
  print "<th>№</th>";
  print "<th>Категория програмного обеспечения</th>";
  print "<th>Наименований ПО</th>";
  print "<th>Инсталяций</th>";
  print "</tr>";
  
  ksort($allsoft);
  $i = 0;
  $instcnt = array();
  $fullsoftcnt = 0;
  $fullinstallcount = 0;
  foreach($allsoft as $index=>$value){
    $lowindex = mb_strtolower($index, 'UTF-8');
    $sCat = isset($sCatList[$lowindex]) ? $sCatList[$lowindex]['sCat'] : '';
    if(isset($CatFull[$sCat]))$CatFull[$sCat] += $value;
    else $CatFull[$sCat] = $value;
    if(isset($instcnt[$sCat])) $instcnt[$sCat]++;
    else $instcnt[$sCat] = 1;
    $fullsoftcnt++;
    $fullinstallcount += $value;
  }
  
  
  $i = 1;
  ksort($CatFull);
  foreach($CatFull as $index=>$value){
    if($value > 0){
      echo "<tr><td>".$i.".</td>";
      if($index != "")echo "<td align=left><a href='slist.php?catfilter=".urlencode($index)."'>". htmlspecialchars($index)."</a></td>";
      else echo "<td align=left><a href='slist.php?catfilter='><i>Нераспределенное ПО...</i></a></td>";
      echo "<td align='right'>".$instcnt[$index]."</td>";
      echo "<td align='right'>".$value."</td>";
      echo "</tr>";
      $i++;
    }
  }
  echo "<tr>";
  echo "<td colspan=2 align=left><i><b><a href='slist.php'>Всё ПО:</a></b></i></td>";
  echo "<td align='right'><b>".$fullsoftcnt."</b></td>";
  echo "<td align='right'><b>".$fullinstallcount."</b></td>";
  echo "</tr>";
  print "</table>";
  
  echo "<h2>Операционные системы (Volume License).</h2>";
  print "<table border=1 width='95%'>";
  print "<tr>";
  print "<th>№</th>";
  print "<th>Ключ продукта</th>";
  print "<th>Инсталяций</th>";
  print "<th>Лицензия</th>";
  print "</tr>";
  
  $where .= " AND invmain.sOSLicenseType = 'Volume license'";
  
  global $idb;
  $st = $idb->prepare("SELECT
              invmain.sOSSN, invmain.sOS, invmain.sOSLicenseType, COUNT(invmain.nHost) as Cnt
            FROM
              invmain WHERE ".$where."
            GROUP BY
              invmain.sOSSN
            ORDER BY invmain.sOS");
    if($st->execute($gfilters) and ($st->rowCount() > 0)){
        $i = 1;
        $os = '';
        $sall = 0;
        while($row = $st->fetch(PDO::FETCH_ASSOC)){
          if($os != $row['sOS']) {
            echo '<tr><td colspan=4 align=center><b>'.$row['sOS'].'</b></td></tr>';
            $os = $row['sOS'];
          }
          echo "<tr><td>".$i.".</td>";
          echo "<td align='left'><a href=complist.php?softfilter=".$row['sOSSN'].">".$row['sOSSN']."</td>";
          echo "<td align='right'>".$row['Cnt']."</td>";
          echo "<td align='right'>"."</td>";
          echo "</tr>";
          $i++;
          $sall += $row['Cnt'];
        }
        echo '<tr><td colspan=2 align=left><b>Всего:</b></td><td align=right><b>'.$sall.'</b></td><td></td></tr>';
        print "</table>";


        echo "<h2>Операционные системы (OEM).</h2>";
        print "<table border=1 width='95%'>";
        print "<tr>";
        print "<th>№</th>";
        print "<th>Ключ продукта</th>";
        print "<th>Инсталяций</th>";
        //print "<th>Лицензия подтверждена</th>";
        print "<th>Коментарий к лицензии</th>";
        print "</tr>";
    }

    $where = $gfilters_sql . " AND invmain.sOSLicenseType = 'OEM'";
    $st = $idb->prepare("SELECT
                invmain.sOSSN, invmain.sOS, invmain.sOSLicenseType, COUNT(invmain.nHost) as Cnt, invmain.sOSLegal, invmain.sOSLegalComment
              FROM
                invmain WHERE ".$where."
              GROUP BY
                invmain.sOSSN
              ORDER BY invmain.sOS");

    if($st->execute($gfilters) and ($st->rowCount() > 0)){
        $i = 1;
        $os = '';
        $sall = 0;
        while($row = $st->fetch(PDO::FETCH_ASSOC)){
          if($os != $row['sOS']) {
            echo '<tr><td colspan=4 align=center><b>'.$row['sOS'].'</b></td></tr>';
            $os = $row['sOS'];
          }
          echo "<tr><td>".$i.".</td>";
          echo "<td align='left'><a href=complist.php?softfilter=".$row['sOSSN'].">".$row['sOSSN']."</td>";
          echo "<td align='right'>".$row['Cnt']."</td>";
          //echo "<td align='right'>".$row['sOSLegal']."</td>";
          echo "<td align='right'>".$row['sOSLegalComment']."</td>";
          echo "</tr>";
          $i++;
          $sall += $row['Cnt'];
        }
        echo '<tr><td colspan=2 align=left><b>Всего:</b></td><td align=right><b>'.$sall.'</b></td><td></td></tr>';
        print "</table>";

        echo "<h2>Операционные системы (Retail).</h2>";
        print "<table border=1 width='95%'>";
        print "<tr>";
        print "<th>№</th>";
        print "<th>Ключ продукта</th>";
        print "<th>Инсталяций</th>";
        //print "<th>Лицензия подтверждена</th>";
        print "<th>Коментарий к лицензии</th>";
        print "</tr>";
    }

    $where = $gfilters_sql . " AND invmain.sOSLicenseType = 'Retail'";
    $st = $idb->prepare("SELECT
                invmain.sOSSN, invmain.sOS, invmain.sOSLicenseType, COUNT(invmain.nHost) as Cnt, invmain.sOSLegal, invmain.sOSLegalComment
              FROM
                invmain WHERE ".$where."
              GROUP BY
                invmain.sOSSN
              ORDER BY invmain.sOS");

    if($st->execute($gfilters) and ($st->rowCount() > 0)){
        $i = 1;
        $os = '';
        $sall = 0;
        while($row = $st->fetch(PDO::FETCH_ASSOC)){
          if($os != $row['sOS']) {
            echo '<tr><td colspan=4 align=center><b>'.$row['sOS'].'</b></td></tr>';
            $os = $row['sOS'];
          }
          echo "<tr><td>".$i.".</td>";
          echo "<td align='left'><a href=complist.php?softfilter=".$row['sOSSN'].">".$row['sOSSN']."</td>";
          echo "<td align='right'>".$row['Cnt']."</td>";
          //echo "<td align='right'>".$row['sOSLegal']."</td>";
          echo "<td align='right'>".$row['sOSLegalComment']."</td>";
          echo "</tr>";
          $i++;
          $sall += $row['Cnt'];
        }
        echo '<tr><td colspan=2 align=left><b>Всего:</b></td><td align=right><b>'.$sall.'</b></td><td></td></tr>';
        print "</table>";

        echo "<h2>Microsoft Office.</h2>";
        print "<table border=1 width='95%'>";
        print "<tr>";
        print "<th>№</th>";
        print "<th>Ключ продукта</th>";
        print "<th>Инсталяций</th>";
        //print "<th>Подлинность подтверждена</th>";
        print "<th>Коментарий к лицензии</th>";
        print "</tr>";
    }

    $where = $gfilters_sql . " AND  invmain.sMSOfficeSN IS NOT NULL AND invmain.sMSOfficeSN <> ''";
    $st = $idb->prepare("SELECT
                invmain.sMSOfficeSN, invmain.sMSOffice, COUNT(invmain.nHost) as Cnt, SUM(invmain.sOfficeLegal) as sOfficeLegal, invmain.sOfficeLegalComent
              FROM
                invmain WHERE ".$where."
              GROUP BY
                invmain.sMSOfficeSN
              ORDER BY invmain.sMSOffice");
    if($st->execute($gfilters) and ($st->rowCount() > 0)){
        $i = 1;
        $os = '';
        $sall = 0;
        while($row = $st->fetch(PDO::FETCH_ASSOC)){
          if($os != $row['sMSOffice']) {
            echo '<tr><td colspan=4 align=center><b>'.$row['sMSOffice'].'</b></td></tr>';
            $os = $row['sMSOffice'];
          }
          echo "<tr><td>".$i.".</td>";
          echo "<td align='left'><a href=complist.php?softfilter=".$row['sMSOfficeSN'].">".$row['sMSOfficeSN']."</td>";
          echo "<td align='right'>".$row['Cnt']."</td>";
          //echo "<td align='right'>".$row['sOfficeLegal']."</td>";
          echo "<td align='right'>".$row['sOfficeLegalComent']."</td>";
          echo "</tr>";
          $i++;
          $sall += $row['Cnt'];
        }
        echo '<tr><td colspan=2 align=left><b>Всего:</b></td><td align=right><b>'.$sall.'</b></td><td></td></tr>';
        print "</table>";
    }
  
?>
</center>
</div>

<?php  

include "htmlend.php";