<?php 
global $pagetitle;
$pagetitle = "Настройка клиентского модуля"; 
include "htmlstart.php";
global $curuser;
global $idb;
$message = '';

if(isset($_POST['dosave']) and isset($curuser) and $curuser['isadmin']){
    
    function DoSaveData(&$st, $param, $value, $data){
        $st->bindValue('param', $param);
        $st->bindValue('value', $value);
        $st->bindValue('data', $data);
        return $st->execute();
    }
    
    $st = $idb->prepare('INSERT INTO client_config (param, value, data) VALUES (:param, :value, :data) '
                      . 'ON DUPLICATE KEY UPDATE value = :value, data = :data');
    
    $req_fields = isset($_POST['client_conf_req_fields']) ? implode("\r\n", $_POST['client_conf_req_fields']) : '';
    if(
        DoSaveData($st, 'client_conf_query_startup', isset($_POST['client_conf_query_startup']), '') and
        DoSaveData($st, 'client_conf_query_notify', isset($_POST['client_conf_query_notify']), '') and
        DoSaveData($st, 'client_conf_req_fields', '', $req_fields)
      ){
        $message = '<b>Данные сохранены</b><br><br>';        
    } else {
        $message = '<b>Ошибка базы данных:</b><br>' . htmlspecialchars(print_r($st->errorInfo(), true)) . '<br><br>';       
    }
}

OutHeaders();

?>
<h1 class='content'><a href="config-main.php" style="color: white"><u>Настройки</u></a> \ Настройка клиентского модуля</h1>
<div class ='content-text'>
<?php
if($curuser['isadmin']){
    global $cur_config;
    $cur_config = array();
    $st = $idb->prepare('SELECT * FROM client_config WHERE param LIKE "client_conf_%"');
    if($st->execute()){
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $cur_config[$row['param']] = $row;
        }
    }   
    $client_conf_query_startup = (isset($cur_config['client_conf_query_startup']) and ($cur_config['client_conf_query_startup']['value'] == 1)) ? 'checked' : '';
    $client_conf_query_notify = (isset($cur_config['client_conf_query_notify']) and ($cur_config['client_conf_query_notify']['value'] == 1)) ? 'checked' : '';
    
    $req_fields = array(
        'plSity' => 'Город',          
        'plCompany' => 'Организация',        
        'plFilial' => 'Филиал (подразделение)',         
        'plAdress' => 'Адрес',         
        'plBuilding' => 'Корпус',       
        'plCab' => 'Кабинет',            
        'cOtd' => 'Отдел',             
        'cTitle' => 'Должность',           
        'cTelephone' => 'Основной телефон',       
        'cTelephoneLocal' => 'Внутренний телефон',  
        'cTelephoneCell' => 'Сотовый телефон',   
        'cEmail' => 'e-mail',           
        'cJabber' => 'Jabber',          
        'cSkype' => 'Skype',           
        'inMonitor' => 'Инвентарный номер монитора', 
        'inMainBox' => 'Инвентарный номер системного блока', 
        'inPrinter' => 'Инвентарный номер принтера', 
        'inScaner' => 'Инвентарный номер сканера',  
    );
    
    echo '<h3 class="content">Параметры клиентского модуля</h3>';
    
    echo '<form action="config-client.php" method="post">';    
    echo '<input type="hidden" name="dosave" value="1">';
    echo '<nobr><input type=checkbox name="client_conf_query_startup" '.$client_conf_query_startup.' value="0" >Требовать от пользователя ввода данных при запуске программы.</nobr><br>';
    echo '<nobr><input type=checkbox name="client_conf_query_notify" '.$client_conf_query_notify.' value="1" >Напоминать пользователю о необходимости ввода данных во время работы.</nobr><br><br>';
    echo '<br><b>Обязательные для ввода поля:</b><br>';
    if(isset($cur_config['client_conf_req_fields'])){
        $req_fields_enabled = array_flip(explode("\r\n", $cur_config['client_conf_req_fields']['data']));
    } else {
        $req_fields_enabled = array();
    }      
    foreach ($req_fields as $key => $value) {
        $checked = isset($req_fields_enabled[$key]) ? 'checked' : '';
        echo '<nobr><input type=checkbox name="client_conf_req_fields[]" '.$checked.' value="'.$key.'" >'.$value.'</nobr><br>';
    }
    echo '<br><input type="Submit" value="Сохранить"></form>'.$message;

?>
</p>
</div>

<?php
} else {
    echo '<h2>У вас недостаточно прав для доступа к этому разделу</h2>';
}
  
include "htmlend.php";
