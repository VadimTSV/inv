<?php
global $pagetitle;
$pagetitle = "Новя рассылка"; 
include "htmlstart.php";

function DDate($s){
    $d = explode('.', $s);
    if(count($d) == 3) {
        return $d[2] . "-" . $d[1] . "-" . $d[0];
    } else {
        return date('Y-m-d');
    }
 }

if(isset($_POST['message']) and ($_POST['message'] != "")){
  global $curuser;
  if($curuser['isadmin']) {
     if(isset($_POST['hipr'])) $s = 'highest';
     else $s = 'hi';  
     global $idb;
     $st = $idb->prepare("INSERT INTO messages (brodcast, priority, message, mtype, plSity, plCompany, plFilial, cOtd, expire, sender) 
                         VALUES (1, :priority, :message, :mtype, :plSity, :plCompany, :plFilial, :cOtd, :expire, :sender)");
     $st->bindValue('priority', $s);
     $st->bindValue('message', $_POST['message']." \r\nОтправитель - ".$curuser['title']." (".$curuser['dname'].")");
     $st->bindValue('mtype', 'bcmessage');
     $st->bindValue('plSity', $_POST['plSity']);
     $st->bindValue('plCompany', $_POST['plCompany']);
     $st->bindValue('plFilial', $_POST['plFilial']);
     $st->bindValue('cOtd', $_POST['cOtd']);
     $st->bindValue('expire', DDate($_POST['sdata']));
     $st->bindValue('sender', $curuser['title']." (".$curuser['dname'].")");
     if($st->execute()){
         header("location:newmessage.php?msgdone=1");
     } else {
         print_r($st->errorInfo());
         exit;
     }        
  } 
}

OutHeaders();

?>
<script src="datepicker.js" type="text/javascript" charset="UTF-8" language="javascript"></script>

<h1 class='content'>Новая рассылка.</h1>
<div class ='content-text'>
<?php


function GetFieldValueList($fieldname){
  global $idb;
$query = "SELECT DISTINCT ".$fieldname." FROM invmain ORDER BY ".$fieldname;  
  $res =  $idb->query($query);
  $temp_res = "";
    
  $temp_res .= "<option value=''></option>";
  while($row = $res->fetch(PDO::FETCH_ASSOC)){
    if($row[$fieldname] != "") $temp_res .= "<option value='".$row[$fieldname]."'>".$row[$fieldname]."</option>";
  } 
  
  return $temp_res; 
}
 
?>
<h2>Сообщение для пользователей клиентских модулей программы</h2>
<form action="newmessage.php" method="post">
  <div class='leftmargin20'>
      <?php if(isset($_GET['msgdone']) and $_GET['msgdone']) echo "<b>Рассылка добавлена.</b><br /><br /><br />";?>      
<?php
if($curuser['isadmin']){   
   ?>
     
   <table class="noframe" border=0>
   <tr  class="form">
			  <td class="form">Фильтр города</td>
			  <td class="form">
			    <select <?php echo ($curuser['filter_plSity'] != '') ? 'disabled' : ''; ?> name = 'plSity' id = 'plSity' size =  multiple value='<?php echo $curuser['filter_plSity']; ?>' STYLE="width: 350px">
            <?php echo GetFieldValueList('plSity'); ?> 
          </select>
        </td>
			</tr>
			
			<tr  class="form">
			  <td class="form">Фильтр организации</td>
			  <td class="form">
			    <select  <?php echo ($curuser['filter_plCompany'] != '') ? 'disabled' : ''; ?> name = 'plCompany' id = 'plCompany' size =  multiple value='<?php echo $curuser['filter_plCompany']; ?>' STYLE="width: 350px">
            <?php echo GetFieldValueList('plCompany'); ?> 
          </select>
        </td>
			</tr>
			
			<tr  class="form">
			  <td class="form">Фильтр филиала</td>
			  <td class="form">
			    <select <?php echo ($curuser['filter_plFilial'] != '') ? 'disabled' : ''; ?> name = 'plFilial' id = 'plFilial' size =  multiple value='<?php echo $curuser['filter_plFilial']; ?>' STYLE="width: 350px">
            <?php echo GetFieldValueList('plFilial'); ?> 
          </select>
        </td>
			</tr>
			
			<tr  class="form">
			  <td class="form">Фильтр отдела</td>
			  <td class="form">
                              <select <?php echo ($curuser['filter_plFilial'] != '') ? 'disabled' : ''; ?> name = 'cOtd'  id = 'cOtd' size =  multiple value='<?php echo $curuser['filter_plFilial']; ?>' STYLE="width: 350px">
            <?php echo GetFieldValueList('cOtd'); ?> 
          </select>
        </td>
			</tr>
                        <tr  class="form">
			  <td class="form">Собщение актуально до</td>
			  <td class="form">
			    <input style="width: 350px;" name="sdata" id="sdata" value="<?php echo date('d.m.Y', time() + (3600*24*2)); ?>"> 
                            <input type="button" id="cbutton" style="background: url('calendar.png') no-repeat; width: 30px; border: 0px;" onclick="displayDatePicker('sdata', false, 'dmy', '.');">
                            </select>
                          </td>
			</tr>                 
			
		</table>
                <nobr><input type=checkbox name='hipr' id='hipr' value='1'/>Высокий приоритет сообщения</nobr><br />
		<?php                
}
?> 
      <br>
      <i>Сообщение</i><br />
      <textarea name='message' cols='90' rows='5'></textarea><br><br>
      <input type='Submit' value='Отправить сообщение' />
  </div> 
<?php


?>
</form>
</div>

<?php
  
include "htmlend.php";