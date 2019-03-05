<?php 
global $pagetitle;
$pagetitle = "Учетная карточка компьютера";
include 'htmlstart.php';
OutHeaders();

function txthtml($txt) {
  return nl2br(htmlentities($txt, ENT_QUOTES, 'UTF-8'));
}

function ImplodeContacts($contact_array = array()){
    $result = array();
    foreach ($contact_array as $key => $value) {
        if(trim($value) != '') {
            if(!is_int($key)) {
                $result[] = $key . $value; 
            } else {
                $result[] = $value;
            }
        }
    }
    return txthtml(implode(', ', $result));
}

function OutComputerInfo($nHost){
// Вывод данных о компьютере в виде талицы
if(IsSet($nHost)){
  global $idb;
  $query = "SELECT invmain.* FROM invmain WHERE invmain.nHost = '".$nHost."'";
  $res = $idb->query($query);
  if(!($res === false)){// Информацио о компьютере уже присутствует в базе данных
    $row = $res->fetch(PDO::FETCH_ASSOC);
    echo "<center> <b>г.".txthtml($row['plSity'])."</b><br>";
    echo "<b>".txthtml($row['plCompany'])."</b><br>";
    echo "(".txthtml($row['plFilial']).")<br>";
    echo "Учетная карточка персонального компьютера: <u>".$row['nHost']."</u></center><br>";
    //echo "Место нахождения: ".txthtml($row['plAdress']).", ";
    //if($row['plBuilding'] != "") echo txthtml($row['plBuilding']).", ";
    //echo "кабинет - ". txthtml($row['plCab']).".<br>";  
    //echo "Пользователь: ";
    //if($row['cOtd'] != "") echo txthtml($row['cOtd']).", ";
    //echo txthtml($row['cTitle']).".<br>";
    
    echo "<b>Контактная информация:</b><br>";
    echo "<table>";
    echo "<tr class='form'><td class='form'>Место нахождения: </td><td class='form'>".ImplodeContacts(array($row['plAdress'], $row['plBuilding'], "кабинет - " => $row['plCab']))."</td></tr>";   
    echo "<tr class='form'><td class='form'>Пользователь: </td><td class='form'>".ImplodeContacts(array($row['cOtd'], $row['cTitle']))."</td></tr>";
    echo "<tr class='form'><td class='form'>Учетная запись AD: </td><td class='form'>".txthtml($row['nUserLogin'])."</td></tr>";
    echo "<tr class='form'><td class='form'>IP адрес: </td><td class='form'>".txthtml($row['nIP'])."</td></tr>";
    echo "<tr class='form'><td class='form'>Городской телефон: </td><td class='form'>".txthtml($row['cTelephone'])."</td></tr>";
    echo "<tr class='form'><td class='form'>Внутренний телефон: </td><td class='form'>".txthtml($row['cTelephoneLocal'])."</td></tr>";
    echo "<tr class='form'><td class='form'>Сотовый телефон: </td><td class='form'>".txthtml($row['cTelephoneCell'])."</td></tr>";
    echo "<tr class='form'><td class='form'>Электронная почта: </td><td class='form'>".txthtml($row['cEmail'])."</td></tr>";
    echo "<tr class='form'><td class='form'>Учетная запись Jabber: </td><td class='form'>".txthtml($row['cJabber'])."</td></tr>";
    echo "<tr class='form'><td class='form'>Учетная запись Skype: </td><td class='form'>".txthtml($row['cSkype'])."</td></tr>";    
    echo "</table></br>";
    
    echo "
    <b>Аппаратная конфигурация ПК и переферийные устройства:</b><br>
    <table class='form' border=1 width=100%>
      <tr>
        <th><b>Комплектующие</b></th><th><b>Модель / характеристика</b></th><th><b>Инвентарный номер</b></th> 
      </tr>";    
    echo "<tr><td>Материнская плата</td><td>".txthtml($row['hMainboardModel'])."&nbsp</td><td>&nbsp</td></tr>";
    echo "<tr><td>Процессор</td><td>".txthtml($row['hProcessor'])."&nbsp</td><td>&nbsp</td></tr>";
    echo "<tr><td>Оперативная память</td><td>".txthtml($row['hRAMtype'])."&nbsp</td><td>&nbsp</td></tr>";
    echo "<tr><td>Жесткие диски</td><td>".txthtml($row['hHDD'])."&nbsp</td><td>&nbsp</td></tr>";
    echo "<tr><td>Видио адаптер</td><td>".txthtml($row['hVGA'])."&nbsp</td><td>&nbsp</td></tr>";
    echo "<tr><td>Сетевы адаптеры</td><td>".txthtml($row['hNetwork'])."&nbsp</td><td>&nbsp</td></tr>";
    echo "<tr><td>Монитор</td><td>".txthtml($row['pMonitor'])."&nbsp</td><td>".txthtml($row['inMonitor'])."&nbsp</td></tr>";
    if($row['pPrinter'] != "") echo "<tr><td>Принтер</td><td>".txthtml($row['pPrinter'])."&nbsp</td><td>".txthtml($row['inPrinter'])."&nbsp</td></tr>";
    if($row['pScaner'] != "") echo "<tr><td>Сканер</td><td>".txthtml($row['pScaner'])."&nbsp</td><td>".txthtml($row['inScaner'])."&nbsp</td></tr>";
    if($row['pUPS'] != "0") echo "<tr><td>Источник беcперебойного питания</td><td>".txthtml($row['pUPSmodel'])."&nbsp</td><td>".txthtml($row['inUPS'])."&nbsp</td></tr>";
    if($row['hPilot'] != "") echo "<tr><td>Сетевой фильтр</td><td>".txthtml($row['hPilot'])."&nbsp</td><td><&nbsp/td></tr>";
    echo "</table>";
    
    echo "
    <br><b>Установленное програмное обеспечение:</b><br>
    <table class='OutComputerInfo' border=1 width=100%>
      <tr>
        <th><b>Наименование ПО</b></th><th><b>Серийный номер</b></th> 
      </tr>
    ";
    echo "<tr><td>".$row['sOS']." ".$row['sOSSP']."</td><td>".$row['sOSSN']."</td></tr>";
    if($row['sMSOffice'] != "") echo "<tr><td>".$row['sMSOffice']."</td><td>".$row['sMSOfficeSN']."</td></tr>";
    echo "</table>";
    
    echo "<br><b>История записи базы данных:</b><br>";    
    echo "<table>";
    echo "<tr class='form'><td class='form'>Последние обращение клиентского модуля: </td><td class='form'>".date("d.m.Y - H:i", strtotime($row['LastUpdate']))."</td></tr>";
    echo "<tr class='form'><td class='form'>Последнее изменение данных: </td><td class='form'>".date("d.m.Y - H:i", strtotime($row['LastDataChanges']))."</td></tr>";
    echo "<tr class='form'><td class='form'>Количество игнорирований мастера сбора сведений о пользователе: </td><td class='form'>".txthtml($row['ignorecnt'])."</td></tr>";
    echo "</table>";
    
    
  } // if(mysql_num_rows($res))
} //if(IsSet($nHost) 
}

function table_description($t,$c){ 
     $sql = "SELECT column_comment FROM information_schema.columns 
      WHERE table_name = '$t' AND column_name LIKE '$c'"; 
     global $idb;
     $query = $idb->query($sql); 
     $v = $query->fetch(PDO::FETCH_NUM); 
     if($v){ 
         return $v[0]; 
         } 
     return 'Table description not found'; 
}

function OutFullComputerInfo($nHost){
  if(IsSet($nHost)){
    global $idb;
    $query = "SELECT invmain.* FROM invmain WHERE invmain.nHost = '".$nHost."'";
    $res = $idb->query($query);
    if(($res = $idb->query($query)) and ($res->rowCount() > 0)){// Информацио о компьютере уже присутствует в базе данных
      $row = $res->fetch(PDO::FETCH_ASSOC);        
      echo "<table class='OutFullComputerInfo' border=1 width=100%>
             <tr><th>Наименование</th><th>Значение</th></tr>";
  
      foreach ($row as $key => $value) {
        echo "<tr>";          
          echo "<td>".table_description("invmain", $key)."</td>";
          echo "<td><div id='txt'>".txthtml($value)."</div></td>";  
        echo "</tr>";
      }
      echo "</table>";
    }
  }
}

function OutCompInfo(){
  global $curuser;
  if(IsSet($_REQUEST['nHost'])){
    if(isSet($_REQUEST['hdetailed'])){
      if($_REQUEST['hdetailed'] != "0") { echo "<h3>".$_REQUEST['nHost']."</h3>";
                                  echo "<a href=rowdetailshow.php?nHost=".$_REQUEST['nHost']."&hdetailed=0>"."Показать подробную информацию"."</a><br />";
                                  echo "<a href='actions.php?filter_nHost=".$_REQUEST['nHost']."'>Посмотреть события связанные с этим узлом</a><br />";
                                  OutComputerInfo($_REQUEST['nHost']);
                                }
      else { echo "<h3>".$_REQUEST['nHost']."</h3>";
             echo "<a href=rowdetailshow.php?nHost=".$_REQUEST['nHost']."&hdetailed=1>"."Показать упрощенную информацию"."</a><br />";
             echo "<a href='actions.php?filter_nHost=".$_REQUEST['nHost']."'>Посмотреть события связанные с этим узлом</a><br />";
             OutFullComputerInfo($_REQUEST['nHost']); 
           }
    } else { echo "<h3>".$_REQUEST['nHost']."</h3>";
             echo "<a href=rowdetailshow.php?nHost=".$_REQUEST['nHost']."&hdetailed=0>"."Показать подробную информацию"."</a><br />";
             echo "<a href='actions.php?filter_nHost=".$_REQUEST['nHost']."'>Посмотреть события связанные с этим узлом</a><br />";
             OutComputerInfo($_REQUEST['nHost']);
           } 
  } else echo "Не задано имя узла"; 
}

?>
<h1 class='content'>Информация о компьютере: <strong><?php echo isset($_REQUEST['nHost']) ? $_REQUEST['nHost'] : 'Нет';  ?></strong></h1>
<div class='content-text'>
<?php
  global $curuser;
  if(isset($_REQUEST['nHost'])) {
    $editlnk = $curuser['isadmin']?"<a href='./deldbentry.php?nHost=".$_REQUEST['nHost']."&backurl=".$_SERVER['REQUEST_URI']."'><img border=0 src='webdata/delete.png' title='Удалить запись'></a><a href='./edituserdata.php?nHost=".$_REQUEST['nHost']."&backurl=".$_SERVER['REQUEST_URI']."'><img border=0 src='webdata/edit.png' title='Редактировать данные'></a>":"";
    echo "<div align=right>".$editlnk."<a href='./vncout.php?nHost=".$_REQUEST['nHost']."'><img border=0 src='webdata/uvnc.png' title='Управление'></a></br></div>";
  }
  OutCompInfo(); 
?>
</div>   
<?php
include "htmlend.php";
?>
