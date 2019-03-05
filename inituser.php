<?php
include_once 'admin/mysqlconnect.php';
session_start();

global $idb; // База данных
global $curuser; // Текущий пользователь
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 
global $userfilters; // фильтры пользователя
$userfilters = array();
global $userfilters_sql; // фильтры пользователя
global $filter_fields_order; // Список полей доступных для глобальной фильтрации в порядке иерархии
$filter_fields_order = array('plSity', 'plCompany', 'plFilial', 'cOtd'); 

// Авторизация
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['login']) and isset($_COOKIE['password'])) {
        $st = $idb->prepare("SELECT * , 
                                    (LOCATE('~ADMIN~', usr.right) > 0) as isadmin, 
                                    (LOCATE('~MODERATOR~', usr.right) > 0) as ismoderator
                                FROM usr
				WHERE login = :login AND password = :password LIMIT 1");
        $login = $_COOKIE['login'];
        $password = $_COOKIE['password']; 
        $st->bindValue('login', $login);
        $st->bindValue('password', $password);
	if ($st->execute() and ($st->rowCount() == 1)) {
            $curuser = $st->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $curuser['id'];
            $time = 86400*10;
            setcookie('login', $login, time()+$time, "/");
            setcookie('password', $password, time()+$time, "/");
	}
    } 
} else {
    $st = $idb->prepare('SELECT * ,
              (LOCATE("~ADMIN~", usr.right) > 0) as isadmin,
              (LOCATE("~MODERATOR~", usr.right) > 0) as ismoderator
            FROM usr
            WHERE
              id=:id LIMIT 1');
    $st->bindValue('id', $_SESSION['user_id']);
    if($st->execute() and ($st->rowCount() == 1)){
        $curuser = $st->fetch(PDO::FETCH_ASSOC);
    }    
}

if(isset($_SESSION['user_id'])) {     
    // Обновление глобальных фильтров
    if(isset($_SESSION['gfilters'])) {
        $gfilters = $_SESSION['gfilters'];
    } else {
        $gfilters = array();
    }

    foreach ($filter_fields_order as $field) {         
        if(isset($_GET['filter_'.$field])) { // Фильтр изменен
            $gfilters[$field] = $_GET['filter_'.$field];
            $i = array_search($field, $filter_fields_order) + 1; // Сброс нижележащих фильтров              
            for ($index = $i; $index < count($filter_fields_order); $index++) {
                unset($gfilters[$filter_fields_order[$index]]);
            }
        }
        if(isset($_GET['null_filter_'.$field])){ // Сброс фильтра с текущего уровня 
            $i = array_search($field, $filter_fields_order); // Сброс фильтров с текущего уровня             
            for ($index = $i; $index < count($filter_fields_order); $index++) {
                unset($gfilters[$filter_fields_order[$index]]);
            }
        }       
        if(isset($curuser['filter_'.$field]) and !($curuser['filter_'.$field] === '')) { // Переопределение фильтров если они жестко заданы для пользователя
            $gfilters[$field] = $curuser['filter_'.$field];
        }
    }    
    $userfilters_sql = '(true ';
    foreach ($filter_fields_order as $field) {             
        if(isset($curuser['filter_'.$field]) and !($curuser['filter_'.$field] === '')) { // Переопределение фильтров если они жестко заданы для пользователя
            $userfilters['user_'.$field] = $curuser['filter_'.$field];
            $userfilters_sql .= ' AND (' . $field.' = :' . 'user_'.$field . ')';

        }
    }    
    $userfilters_sql .= ')';

    $_SESSION['gfilters'] = $gfilters;
    $gfilters_sql = '(true ';
    foreach ($gfilters as $key => $value) {
        $gfilters_sql .= ' AND (' . $key .' = :' . $key . ') ';
    }
    $gfilters_sql .= ')';
} else {
    unset($curuser);
}