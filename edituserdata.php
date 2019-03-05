<?php
include_once 'htmlstart.php';
include_once 'inv_global.php';
global $pagetitle;
$pagetitle = "Редактирование пользовательских данных"; 

global $curuser;
if($curuser['isadmin']){
if(isset($_POST['isedit'])){
    if(AddInvDataToDB()) header("location:".$_POST['backurl']);
}
OutHeaders();

if(!isset($_GET['nHost'])) echo 'Не задана редактируемая запись';
else { 
  
  global $idb;
  $query = "SELECT * FROM invmain WHERE nHost='".$_GET['nHost']."'";
  $res = $idb->query($query);
  if($row = $res->fetch(PDO::FETCH_ASSOC)){
?>
<h1 class='content'>Редактирование данных о хосте - <?php echo $_GET['nHost']; ?></h1>

<div class='content-text'>
  <center>
 	<form action="./edituserdata.php?nHost=<?php echo $_GET['nHost'];?>" method="POST">
		<table bgcolor=white class="form" border=0>
		  <tr  class="form">
				<th colspan=2>Свойства записи - <?php echo $row['nHost']; ?> </th>
			</tr>
			
			<tr  class="form">
				<th colspan=2 class="form" align=right><input type="submit" value="Сохранить" /></th>
			</tr>
			
			<tr class="form">
				<td colspan=2 class="form"><b>Расположение рабочего места</b></td>
			</tr>
			
		  <tr  class="form">
				<td class="form"><br>Город</td>
				<td class="form"><br><input type="text" size="50" name="plSity" value="<?php echo $row['plSity']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Организация</td>
				<td class="form"><input type="text" size="50" name="plCompany" value="<?php echo $row['plCompany']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Адрес</td>
				<td class="form"><input type="text" size="50" name="plAdress" value="<?php echo $row['plAdress']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Кабинет</td>
				<td class="form"><input type="text" size="50" name="plCab" value="<?php echo $row['plCab']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Корпус</td>
				<td class="form"><input type="text" size="50" name="plBuilding" value="<?php echo $row['plBuilding']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Филиал</td>
				<td class="form"><input type="text" size="50" name="plFilial" value="<?php echo $row['plFilial']; ?>" /></td>
			</tr>
			
			<tr class="form">
				<td colspan=2 class="form"><br><b>Контактная информация</b></td>
			</tr>
			
			<tr  class="form">
				<td class="form"><br>Отдел</td>
				<td class="form"><br><input type="text" size="50" name="cOtd" value="<?php echo $row['cOtd']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Должность</td>
				<td class="form"><input type="text" size="50" name="cTitle" value="<?php echo $row['cTitle']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Городской телефон</td>
				<td class="form"><input type="text" size="50" name="cTelephone" value="<?php echo $row['cTelephone']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Внутренний телефон</td>
				<td class="form"><input type="text" size="50" name="cTelephoneLocal" value="<?php echo $row['cTelephoneLocal']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Сотовый телефон</td>
				<td class="form"><input type="text" size="50" name="cTelephoneCell" value="<?php echo $row['cTelephoneCell']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">E-mail</td>
				<td class="form"><input type="text" size="50" name="cEmail" value="<?php echo $row['cEmail']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Jabber</td>
				<td class="form"><input type="text" size="50" name="cJabber" value="<?php echo $row['cJabber']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Skype</td>
				<td class="form"><input type="text" size="50" name="cSkype" value="<?php echo $row['cSkype']; ?>" /></td>
			</tr>
						
			<tr class="form">
				<td colspan=2 class="form"><br><b>Информация о установленном оборудовании</b></td>
			</tr>
			
			<tr  class="form">
				<td class="form"><br>Инвентарный номер системного блока</td>
				<td class="form"><br><input type="text" size="50" name="inMainBox" value="<?php echo $row['inMainBox']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Инвентарный номер монитора</td>
				<td class="form"><input type="text" size="50" name="inMonitor" value="<?php echo $row['inMonitor']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Инвентарный номер принтера</td>
				<td class="form"><input type="text" size="50" name="inPrinter" value="<?php echo $row['inPrinter']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Инвентарный номер сканера</td>
				<td class="form"><input type="text" size="50" name="inScaner" value="<?php echo $row['inScaner']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Инвентарный номер ИБП</td>
				<td class="form"><input type="text" size="50" name="inUPS" value="<?php echo $row['inUPS']; ?>" /></td>
			</tr>
			
			<tr  class="form">
				<td class="form">Модель ИБП</td>
				<td class="form"><input type="text" size="50" name="pUPSmodel" value="<?php echo $row['pUPSmodel']; ?>" /></td>
			</tr>
			
			<tr  class="form">
			  <td class="form" colspan=2><input type="checkbox" name = "pUPS" value="1" <?php echo ($row['pUPS'] > 0)?'checked':''; ?> />Источник безперебойного питания</td>
			</tr>
			<tr  class="form">
			  <td class="form" colspan=2><input type="checkbox" name = "pSpeakers" value="1" <?php echo ($row['pSpeakers'] > 0)?'checked':''; ?> />Акустические колонки</td>
			</tr>
			
			<tr class="form">
				<td colspan=2 class="form"><br><b>Дополнительная информация</b></td>
			</tr>
			
			<tr  class="form">
				<td class="form"><br />Материально-ответственное лицо</td>
				<td class="form"><br /><input type="text" size="50" name="inMOL" value="<?php echo $row['inMOL']; ?>" /></td>
			</tr>
			
			<tr class="form">
				<td class="form">Дата ввода в эксплуатацию</td>
				<td class="form"><input type="text" size="50" name="inUseStart" value="<?php echo $row['inUseStart']; ?>" /></td>
			</tr>
			
			<tr class="form">
				<td class="form">Дата завершения эксплуатации</td>
				<td class="form"><input type="text" size="50" name="inUseEnd" value="<?php echo $row['inUseEnd']; ?>" /></td>
			</tr>
			
			<tr  class="form">
			  <td class="form" colspan=2><input type="checkbox" name = "inNotComplite" value="1" <?php echo ($row['inNotComplite'] > 0)?'checked':''; ?> />Инвентарные номера не нанесены</td>
			</tr>
			
			<tr  class="form">
				<th colspan=2 class="form" align=right><input type="submit" value="Сохранить" /></th>
			</tr>
		</table>	
		<input type="hidden" name="isedit" value="1" />
		<input type="hidden" name="backurl" value="<?php echo $_GET['backurl']; ?>" />
	</form>
  </center>
  </div>

<?php
  }
}
}
  
include "htmlend.php";