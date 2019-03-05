<?php 
global $pagetitle;
$pagetitle = "Настройка ограничений ввода"; 
include "htmlstart.php";
global $curuser;
global $idb;
$message = '';

if(isset($_POST['part']) and isset($curuser) and $curuser['isadmin']){
    $st = $idb->prepare('INSERT INTO client_config (param, value, data) VALUES (:param, :value, :data) '
                      . 'ON DUPLICATE KEY UPDATE value = :value, data = :data');
    $st->bindValue('param', 'input_conf_'.$_POST['part']);
    $st->bindValue('value', $_POST['param_value']);
    $st->bindValue('data', $_POST['param_data']);
    if($st->execute()){
        $message = '<b>Данные сохранены</b><br><br>';
    } else {
        $message = '<b>Ошибка базы данных:</b><br>' . htmlspecialchars(print_r($st->errorInfo(), true)) . '<br><br>';
    }
}

OutHeaders();

?>
<h1 class='content'><a href="config-main.php" style="color: white"><u>Настройки</u></a> \ Настройка ограничений ввода данных пользователями</h1>
<div class ='content-text'>
<?php
if($curuser['isadmin']){
    global $cur_config;
    $cur_config = array();
    $st = $idb->prepare('SELECT * FROM client_config WHERE param LIKE "input_conf_%"');
    if($st->execute()){
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $cur_config[$row['param']] = $row;
        }
    }

    $pages = array( 'plSity' => 'Города', 
                    'plCompany' => 'Организации', 
                    'plFilial' => 'Филиалы', 
                    'plAdress' => 'Адреса',                    
                    'cOtd' => 'Отделы',
                    'cTitle' => 'Должности',
                    'profile' => 'Категории заявок',                    
             );
    $part = (isset($_REQUEST['part']) and isset($pages[$_REQUEST['part']])) ? $_REQUEST['part'] : 'plSity';
    $links = array();
    foreach ($pages as $key => $value) {
        if($key === $part) {
            $links[] = '<b>'.$value.'</b>';
        } else {
            $links[] = '<a href="config-places.php?part='.$key.'">'.$value.'</a>';
        }
    }
    echo '<h3 class="content">Списки ограничений</h3>';
    echo implode(' | ', $links) . '<br><br>';
    $cheched0 = (!isset($cur_config['input_conf_'.$part]) or ($cur_config['input_conf_'.$part]['value'] == 0)) ? 'checked' : '';
    $cheched1 = (isset($cur_config['input_conf_'.$part]) and ($cur_config['input_conf_'.$part]['value'] == 1)) ? 'checked' : '';
    $cheched2 = (isset($cur_config['input_conf_'.$part]) and ($cur_config['input_conf_'.$part]['value'] == 2)) ? 'checked' : '';
    $list = isset($cur_config['input_conf_'.$part]) ? $cur_config['input_conf_'.$part]['data'] : '';    
    echo '<form action="config-places.php?part='.$part.'" method="post">';
    echo '<input type="hidden" name="part" value="'.$part.'">';
    echo '<nobr><input type=radio name="param_value" '.$cheched0.' value="0" >Разрешить свободный ввод данных пользователем.</nobr><br>';
    echo '<nobr><input type=radio name="param_value" '.$cheched1.' value="1" >Разрешить свободный ввод, но использовать список в качестве доступных вариантов.</nobr><br>';
    echo '<nobr><input type=radio name="param_value" '.$cheched2.' value="2" >Ограничить ввод данных пользователя списком.</nobr><br>';
    echo '<br>Список вариантов:<br>';
    echo '<textarea name="param_data" cols="90" rows="15">'.$list.'</textarea><br>';
    echo '<input type="Submit" value="Сохранить"></form>'.$message;

?>
<br>
<b>Используемые в списках префиксы:</b><br>    
<b>*</b> - определяет что элемент используется по умолчанию.<br>
<b>#NET#</b> - значение будет использоваться по умолчанию для компьютеров в сети NET (например 192.168.1.1 для сети 192.168.1.0), приоритет выше чем у «*». Допустимо использовать несколько сетей в через ";".<br>
<br>
<b>Пример списка:</b>
<p class='squote'>
*Наименование первое<br>
#10.0.0.0#Наименование второе<br>
Наименование третье<br>
#10.0.0.0;10.1.0.0;10.1.1.0#Наименование четвертое<br>
</p>
</div>

<?php
} else {
    echo '<h2>У вас недостаточно прав для доступа к этому разделу</h2>';
}
  
include "htmlend.php";



