<?php
global $pagetitle;
$pagetitle = "Настройки программы";
include "htmlstart.php";
OutHeaders();
?>
<h1 class='content'>Настройки программы и клиентских модулей.</h1>
<div class ='content-text'>
<ul>
<li><a href="userlist.php">Пользователи системы</a></li>
<li><a href="config-contact.php">Контактная информация службы поддержки</a></li>
<li><a href="config-places.php">Ограничения ввода данных для клиентских модулей</a></li>
<li><a href="config-client.php">Поведение клиентских модулей</a></li>
</ul>
<?php

include "htmlend.php";