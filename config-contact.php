<?php 
global $pagetitle;
$pagetitle = "Контактные данные службы поддержки"; 
include "htmlstart.php";
global $curuser;
global $idb;
$message = '';

if(isset($_POST['contacts_title']) and isset($curuser) and $curuser['isadmin']){
    $params = array('contacts_title', 'contacts_info_short', 'contacts_info_full');
    $st = $idb->prepare('INSERT INTO client_config (param, value, data) VALUES (:param, :value, "") '
                      . 'ON DUPLICATE KEY UPDATE value = :value');
    $fail = false;
    foreach ($params as $value) {
        $st->bindValue('param', $value);
        $st->bindValue('value', $_POST[$value]);
         if(!$st->execute()){
             $fail = true;
         }
    }
    if(!$fail){
        $message = '<b>Данные сохранены</b><br><br>';
    } else {
        $message = '<b>Ошибка базы данных:</b><br>' . htmlspecialchars(print_r($st->errorInfo(), true)) . '<br><br>';
    }
}

OutHeaders();
if($curuser['isadmin']){
    
$st = $idb->prepare('SELECT * FROM client_config WHERE param LIKE "contacts_%"');
if($st->execute()){
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $cur_config[$row['param']] = $row['value'];
    }
} 

if(!isset($cur_config['contacts_title'])) {
    $cur_config['contacts_title'] = '';
}

if(!isset($cur_config['contacts_info_short'])) {
    $cur_config['contacts_info_short'] = '';
}

if(!isset($cur_config['contacts_info_full'])) {
    $cur_config['contacts_info_full'] = '';
}
    
?>
<h1 class='content'><a href="config-main.php" style="color: white"><u>Настройки</u></a> \ Контактная информация службы поддержки</h1>
<div class ='content-text'>
<?php echo $message; ?>
<center>
<form action="config-contact.php" method="post">
<table class="form">
    <tr  class="form">
        <th colspan=2>Контактные данные отображаемые в клиентском модуле</th>
    </tr>
    <tr  class="form">
        <td class="form"><br>Строка заголовка</td>
	<td class="form"><br><input type="text" size="50" name="contacts_title" value="<?php echo $cur_config['contacts_title']; ?>" /></td>
    </tr>    
    <tr  class="form">
        <td class="form"><br>Информационная строка</td>
	<td class="form"><br><input type="text" size="50" name="contacts_info_short" value="<?php echo $cur_config['contacts_info_short']; ?>" /></td>
    </tr>    
    <tr  class="form">
        <td class="form"><br>Подробные сведенья</td>
	<td class="form"><br><textarea name="contacts_info_full" cols="50" rows="10"><?php echo $cur_config['contacts_info_full']; ?></textarea><br></td>    
    </tr>  
    <tr class="form">
        <th colspan=2 align="right" class="form"><input type="Submit" value="Сохранить"></td>  
    </tr>
</table>
</form>
    <img src="webdata/contacthint.png">
</center>
</div>
<?php
} else {
    echo '<h2>У вас недостаточно прав для доступа к этому разделу</h2>';
}
  
include "htmlend.php";



