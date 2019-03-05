<?php
include_once 'admin/mysqlconnect.php';
include_once "inituser.php";

global $idb; // База данных
global $curuser; // Текущий пользователь
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 

if (isset($curuser)) {
    echo "server_status_ok";			
    if($curuser['ismoderator']) {
        $where = $gfilters_sql . " and Active=1 and moderated=0";
        $st = $idb->prepare("SELECT COUNT(id) as cnt
                FROM
                  zayavki WHERE ".$where);
        if($st->execute($gfilters) and ($st->rowCount() > 0)){
            $row = $st->fetch(PDO::FETCH_ASSOC);
            echo "\r\n".$row['cnt']; 
        } else {
            echo "\r\n"."-1";
        }
    }

    if($curuser['login'] != "") {
        $st = $idb->prepare("SELECT 
                  COUNT(id) as cnt
                FROM
                  zayavki WHERE worker ='".$curuser['login']."' AND Active='1' and moderated=1");
        if($st->execute($gfilters) and ($st->rowCount() > 0)){
            $row = $intres->fetch(PDO::FETCH_ASSOC);
            echo "\r\n".$row['cnt'];
        } else { 
            echo "\r\n".'-1';
            echo "\r\n".$curuser['dname']; 
        }    
    } else echo "server_status_autherror";      
}
// ++++выходной формат в случаи удачи авторизации:
// server_status_ok
// количество заявок на модерацию / -1 при отсутствии
// количество заявок для исполнения / -1 при отсутсвии
// отображаемое имя текущего пользователя
// ----при провале server_status_autherror 
