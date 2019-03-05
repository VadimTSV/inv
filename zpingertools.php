<?php
include_once 'CreateZipFile.inc.php';
include_once ("admin/mysqlconnect.php");;
include_once 'inv_global.php';
include_once "inituser.php";
global $curuser;
global $idb;

// Отключаем HTML форматирование ошибок
ini_set('xdebug.default_enable', false);
ini_set('html_errors', false);

//exit;
// Взаимодействие сервера invCollector с ZPinger

function download_file($filename) {
    // есл файла нет
    if (!file_exists($filename)) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    // получим размер файла
    $fsize = filesize($filename);
    // дата модификации файла для кеширования
    $ftime = date("D, d M Y H:i:s T", filemtime($filename));
    // смещение от начала файла
    $range = 0;

    // пробуем открыть
    $handle = @fopen($filename, "rb");

    // если не удалось
    if (!$handle) {
        header("HTTP/1.0 403 Forbidden");
        exit;
    }

    // Если запрашивающий агент поддерживает докачку
    if (isset($_SERVER["HTTP_RANGE"])) {
        $range = $_SERVER["HTTP_RANGE"];
        $range = str_replace("bytes=", "", $range);
        $range = str_replace("-", "", $range);
        // смещаемся по файлу на нужное смещение
        if ($range) {
            fseek($handle, $range);
        }
    }

    // если есть смещение
    if ($range) {
        header("HTTP/1.1 206 Partial Content");
    } else {
        header("HTTP/1.1 200 OK");
    }

    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Last-Modified: {$ftime}");
    header("Content-Length: " . ($fsize - $range));
    header("Accept-Ranges: bytes");
    header("Content-Range: bytes {$range}-" . ($fsize - 1) . "/" . $fsize);

    // подправляем под IE что б не умничал
    if (isset($_SERVER['HTTP_USER_AGENT']) and strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
        Header('Content-Type: application/force-download');
    else
        Header('Content-Type: application/octet-stream');

    while (!feof($handle)) {
        $buf = fread($handle, 512);
        print($buf);
    }

    fclose($handle);
    exit;
}

function can_getmap($user, $mapid) {
    global $idb;
    global $curuser;
    $st = $idb->prepare('SELECT
                netmap_maps.GUID, netmap_maps.CanRead
              FROM
                netmap_maps
              WHERE
                (netmap_maps.GUID = :GUID) AND (LOCATE("~All~", netmap_maps.CanRead) OR LOCATE(:user, netmap_maps.CanRead) OR (netmap_maps.Owner=:Owner))');
    $st->bindValue('GUID', $mapid);
    $st->bindValue('user', $user);
    $st->bindValue('Owner', $curuser['login']);
    if (($st->execute()) and ($st->rowCount() > 0)) {
        return true;  // У пользователя имеются права получения схемы
    } else {
        $query = 'SELECT
                netmap_maps.GUID, netmap_maps.CanRead
              FROM
                netmap_maps
              WHERE
                (netmap_maps.GUID = "' . $mapid . '")';
        $res = $idb->query($query);
        return ($res->rowCount() == 0); // Разрешено для новых схем (добавление схемы на сервер)
    }
}

function can_editmap($user, $mapid, $isadmin) {
    global $idb;
    $query = 'SELECT netmap_maps.GUID FROM netmap_maps WHERE (netmap_maps.GUID = "' . $mapid . '") AND (LOCATE("~All~", netmap_maps.CanWrite) OR LOCATE("' . $user . '", netmap_maps.CanWrite) OR (netmap_maps.Owner="' . $user . '"))';
    $res = $idb->query($query);
    if ($res->rowCount() > 0)
        return true;
    else {
        //return false;
        global $idb;
        $query = 'SELECT netmap_maps.GUID FROM netmap_maps WHERE netmap_maps.GUID = "' . $mapid . '"';
        $res = $idb->query($query);
        return ($res->rowCount == 0);// or $isadmin;
    }
}

function UrlEncodedIniToArray($ini) {
    $result = array();
    $lines = explode("\r\n", $ini);
    foreach ($lines as $value) {
        $i = mb_strpos($value, '=', null, 'UTF-8');
        if ($i > 0) {
            $key = mb_substr($value, 0, $i, 'UTF-8');
            $value = mb_substr($value, $i + 1, mb_strlen($value, 'UTF-8'), 'UTF-8');
            // echo $value;
            $result[$key] = urldecode($value);
            //echo $key.' = ' . $result[$key] . "\r\n";
        }
    }
    return $result;
}

function out_map_prop($mapid) { // возвращает данные по параметрам доступа к странице
// Выходной формат ini где ключ имя пользователя, значение строка
    global $idb;
    $query = 'SELECT
                *
              FROM
                netmap_maps
              WHERE
                netmap_maps.GUID = "' . $mapid . '"';
    $res = $idb->query($query);
    if ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo "server_status_ok\r\n";
        echo 'Title=' . urlencode($row['Title']) . "\r\n";
        echo 'Description=' . urlencode($row['Description']) . "\r\n";
        echo 'Res=' . urlencode($row['Res']) . "\r\n";
        echo 'CanRead=' . urlencode($row['CanRead']) . "\r\n";
        echo 'CanWrite=' . urlencode($row['CanWrite']) . "\r\n";
        echo 'FullRights=' . urlencode($row['FullRights']) . "\r\n";
        echo 'Owner=' . urlencode($row['Owner']) . "\r\n";
        echo 'EditTimeStamp=' . urlencode($row['EditTimeStamp']) . "\r\n";
        echo 'Lock=' . urlencode($row['Lock']) . "\r\n";
        echo 'LockConfirmTime=' . urlencode($row['LockConfirmTime']) . "\r\n";
        echo 'GUID=' . urlencode($row['GUID']) . "\r\n";
        echo 'ChangeTime=' . urlencode($row['ChangeTime']) . "\r\n";
        echo 'ForceToAll=' . urlencode($row['ForceToAll']) . "\r\n";
    } else
        echo 'server_status_nodata';
}

function update_map_prop($mapid, $map_prop_ini) { // Устаналиваем
    global $curuser;
    $values = UrlEncodedIniToArray($map_prop_ini);
    if ($values['Owner'] == '')
        $values['Owner'] = $curuser['login'];
    if ($values['EditTimeStamp'] == '')
        $values['EditTimeStamp'] = date('Y-m-d H:i:s');
    global $idb;
    $query = 'INSERT INTO netmap_maps (GUID, Title, Description, Res, Owner, CanRead, CanWrite, FullRights, EditTimeStamp, ForceToAll)
              VALUES ("' . $values['GUID'] . '", "' . $values['Title'] . '", "' . $values['Description'] . '", "' . $values['Res'] . '", "' . $values['Owner'] . '", "' . $values['CanRead'] . '", "' . $values['CanWrite'] . '",
                      "' . $values['FullRights'] . '", "' . $values['EditTimeStamp'] . '", "' . $values['ForceToAll'] . '")
              ON DUPLICATE KEY UPDATE
              Title="' . $values['Title'] . '",
              Description="' . $values['Description'] . '",
              Res="' . $values['Res'] . '",
              Owner="' . $values['Owner'] . '",
              CanRead="' . $values['CanRead'] . '",
              CanWrite="' . $values['CanWrite'] . '",
              FullRights="' . $values['FullRights'] . '",
              EditTimeStamp="' . $values['EditTimeStamp'] . '",
              ForceToAll="' . $values['ForceToAll'] . '",
              ChangeTime="' . date('Y-m-d H:i:s') . '"';
    ;
    //echo $query;
    $idb->query($query);
    echo "server_status_ok\r\n";
}

function get_userlist() {
    global $idb;
    $query = 'SELECT usr.login, usr.dname, usr.title FROM usr';
    $res = $idb->query($query);
    $result = "server_status_ok\r\n";
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $result .= $row['login'] . '=' . urlencode(($row['dname'] . ' (' . $row['title']) . ')') . "\r\n";
    }
    echo trim($result);
}

function get_values_of($field) {
    global $idb;
    $query = "SELECT
              invmain." . $field . "
            FROM
              invmain
            WHERE
              invmain." . $field . " <> ''
            GROUP BY
              invmain." . $field . "
            ORDER BY
               invmain." . $field;

    $res = $idb->query($query);
    $result = $field . '=';
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $result .= urlencode($row[$field]) . '%0D%0A';
    }
    unset($res);
    return $result;
}

function get_struct($curuser) {
    echo "server_status_ok\r\n";
    echo get_values_of('plSity') . "\r\n";
    echo get_values_of('plCompany') . "\r\n";
    echo get_values_of('plFilial') . "\r\n";
    echo get_values_of('plAdress') . "\r\n";
    echo get_values_of('cOtd') . "\r\n";

    global $idb;
    $query = "SELECT DISTINCT
                SUBSTRING_INDEX(invmain.nIP, '.', 3) AS subnet
              FROM
                invmain
              WHERE
                (LOCATE('0.0.0', invmain.nIP) = 0) AND NOT (invmain.nIP IS NULL)
              ORDER BY
                subnet";
    $res = $idb->query($query);
    $result = 'subnet' . '=';
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $result .= urlencode($row[subnet]) . '%0D%0A';
    }
    echo "server_status_ok\r\n";
    echo $result . "\r\n";
    unset($res);
    $userdata = 'login=' . urlencode($curuser['login']) . "\r\n";
    $userdata = 'role=' . urlencode($curuser['role']) . "\r\n";
    $userdata = 'places=' . urlencode($curuser['places']) . "\r\n";
    $userdata = 'dname=' . urlencode($curuser['dname']) . "\r\n";
    $userdata = 'title=' . urlencode($curuser['title']) . "\r\n";
    $userdata = 'filter_plSity=' . urlencode($curuser['filter_plSity']) . "\r\n";
    $userdata = 'filter_plCompany=' . urlencode($curuser['filter_plCompany']) . "\r\n";
    $userdata = 'filter_plFilial=' . urlencode($curuser['filter_plFilial']) . "\r\n";
    $result = 'userdata=' . urlencode($userdata);
    echo $result . "\r\n";
}

function get_hosts($filtrs) {
    $filter_fiels = UrlEncodedIniToArray($filtrs);
    $filter = ' WHERE false';
    foreach ($filter_fiels as $key => $value) {
        if (!mb_strpos($key, ';', 'UTF-8')) {
            $filter_strs = explode("/r/n", $value);
            foreach ($filter_strs as $str) {
                $filter .= ' OR (' . $key . '="' . $str . '")';
            }
        }
    }
    global $idb;
    $query = 'SELECT invmain.nHost FROM invmain' . $filter;
    $res = $idb->query($query);
    echo "server_status_ok\r\n";
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo $row['nHost'];
    }
}

function user_filter() {
    global $curuser;
    global $idb;
    $result = '';
    $keys = array();
    $st = $idb->prepare('SELECT * FROM invmain LIMIT 1');
    if($st->execute()){
        for ($i=0; $i < $st->columnCount(); $i++) {
            $filtername = "filter_" . $st->getColumnMeta($i)['name'];
            if (isset($curuser[$filtername]) and ($curuser[$filtername] != "")) {
                $result .= ' AND (' . $st->getColumnMeta($i)['name'] . '="' . $curuser[$filtername] . '")' . "\r\n";
            }
        }
    }
    return $result;
}

function get_moder_str($curuser) {
    global $idb;
    $query = 'SELECT zayavki.* FROM zayavki WHERE Active="1" ' . user_filter($curuser) . ' AND (moderated=0)';
    $res = $idb->query($query);
    $result = '';
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        global $InvDBName;
        $list_f = mysql_list_fields($InvDBName, "invmain");
        $n = mysql_num_fields($list_f);
        $z = '';
        for ($i = 0; $i < $n; $i++) {
            $name_f = mysql_field_name($list_f, $i);
            $z = $name_f . '=' . urlencode($row[$name_f]) . "\r\n";
        }
        $result .= $row['id'] . '=' . urldecode($z) . "\r\n";
    }
}

function get_works($user, $lastid) {
    global $idb;
    $query = 'SELECT zayavki.* FROM zayavki WHERE worker ="' . $user . '" AND Active="1" AND (id >= "' . $lastid . '"';
    $res = $idb->query($query);
    echo "server_status_ok\r\n";
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        global $InvDBName;
        $list_f = mysql_list_fields($InvDBName, "invmain");
        $n = mysql_num_fields($list_f);
        $z = '';
        for ($i = 0; $i < $n; $i++) {
            $name_f = mysql_field_name($list_f, $i);
            $z = $name_f . '=' . urlencode($row[$name_f]) . "\r\n";
        }
        echo $row['id'] . '=' . urldecode($z);
    }
}

function get_moder($curuser) {
    echo "server_status_ok\r\n";
    echo get_moder_str($curuser);
}

function get_rt_data($curuser) {

    // Получение списка схем
    //echo "ghjdthrf ".  $curuser['login'] ;
    global $idb;
    $query = 'SELECT
                *
              FROM
                netmap_maps
              WHERE
                (LOCATE("~All~", netmap_maps.CanRead) OR LOCATE("' . $curuser['login'] . '", netmap_maps.CanRead) OR (netmap_maps.Owner="'.$curuser['login'].'"))';
    $res = $idb->query($query);

    $result = '';
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $mapdata = 'EditTimeStamp=' . urlencode($row['EditTimeStamp']) . "\r\n";
        $mapdata .= 'ChangeTime=' . urlencode($row['ChangeTime']) . "\r\n";
        $mapdata .= 'ForceToAll=' . urlencode($row['ForceToAll']) . "\r\n";
        $result .= $row['GUID'] . '=' . urlencode($mapdata) . "\r\n";
    }
    echo "server_status_ok\r\n";
    echo 'maplist=' . urlencode($result) . "\r\n";

    global $idb;
    $query = 'SELECT zayavki.* FROM zayavki WHERE (Active=1) ' . user_filter() . ' AND (moderated=0)';
    $res = $idb->query($query);
    $IDs = '';
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $IDs .= $row['id'] . "\r\n";
    }
    echo 'moderlist=' . urlencode($IDs) . "\r\n";

    global $idb;
    $query = 'SELECT zayavki.* FROM zayavki WHERE (Active=1) ' . user_filter() . ' AND (worker="' . $curuser['login'] . '")';
    $res = $idb->query($query);
    $IDs = '';
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $IDs .= $row['id'] . "\r\n";
    }
    echo 'worklist=' . urlencode($IDs) . "\r\n";
}

function lock_map($curuser, $mapid) {
    if (can_editmap($curuser['login'], $mapid, $curuser['isadmin'])) {
        global $idb;
        $query = 'UPDATE netmap_maps
            SET netmap_maps.Lock=""
            WHERE DATE_ADD(LockConfirmTime, INTERVAL 2 MINUTE) < NOW()';
        $idb->query($query);

        global $idb;
        $query = 'SELECT * FROM netmap_maps
            WHERE GUID="' . $mapid . '"';
        $res = $idb->query($query);
        if ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            switch ($row['Lock']) {
                case $curuser['login']:
                    echo "server_status_ok\r\n";
                    out_map_prop($mapid);
                    break;

                case '':
                    global $idb;
                    $query = 'UPDATE netmap_maps
                          SET netmap_maps.LockConfirmTime=NOW(), netmap_maps.Lock="' . $curuser['login'] . '"
                          WHERE GUID="' . $mapid . '"';
                    $idb->query($query);
                    echo "server_status_ok\r\n";
                    out_map_prop($mapid);
                    break;

                default:
                    global $idb;
                    $query = 'SELECT * FROM usr
                          WHERE login="' . $row['Lock'] . '"';
                    $res2 = $idb->query($query);
                    if ($usr = $res2->fetch(PDO::FETCH_ASSOC)) {
                        echo "server_status_ok\r\n";
                        echo 'В настоящее время схему редактирует ' . $usr['title'] . ' ' . $usr['dname'];
                    }
                    break;
            }
        } else {
            echo "server_status_ok\r\n";
            out_map_prop($mapid);
        }
    } else
        echo 'У вас недостаточно прав для редактирования данной схемы';
}

function update_lock($curuser, $locked) {
    global $idb;
    $query = 'UPDATE netmap_maps
            SET netmap_maps.Lock=""
            WHERE DATE_ADD(LockConfirmTime, INTERVAL 2 MINUTE) < NOW()';
    //echo $query;
    $idb->query($query);

    global $idb;
    $query = 'UPDATE netmap_maps
            SET LockConfirmTime=NOW()
            WHERE netmap_maps.Lock="' . $curuser['login'] . '" AND (LOCATE(GUID, "' . $locked . '") > 0) ';
    $idb->query($query);
}

function unlock_map($curuser, $locked) {
    global $idb;
    $query = 'UPDATE netmap_maps
            SET netmap_maps.Lock=""
            WHERE netmap_maps.Lock="' . $curuser['login'] . '" AND (LOCATE(GUID, "' . $locked . '") > 0) ';
    $idb->query($query);
}

function get_validpics($pics) {
    global $idb;
$query = 'SELECT netmap_maps.Res FROM netmap_maps';
    $res = $idb->query($query);
    echo "server_status_ok\r\n";
    $buf = array();
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $res_lines = explode("\r\n", $row['Res']);
        foreach ($res_lines as $value) {
            $buf[$value] = 1;
        }
    }

    foreach ($pics as $value) {
        if (!isset($buf[$value]))
            echo $value . "\r\n";
    }
}

function delete_map($curuser, $mapid) {
    
}

function filter_get_text($curuser, $filter, $fields, $copyname = NULL) {
    if (!isset($filter) or ($filter == ''))
        $filter = 'TRUE';
    if (!isset($fields) or ($fields == ''))
        $fields = 'nHost';
    global $idb;
    $query = "SELECT
                nHost,"
              . $fields . "
              FROM
                invmain
              WHERE
                TRUE AND " . $filter . ' ' . user_filter(); 
    $res = $idb->query($query);
    if($res === false){
        $result = "server_status_ok\r\n";
        $result .= "Ошибка в пользовательском фильтре для выборки данных.";
        return $result;
    }
    $result = "server_status_ok\r\n";
    $fa = explode(',', $fields);
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $param = '';
        foreach ($fa as $value) {
            if(isset($copyname) AND ($value == 'nHost') AND isset($copyname[$row['nHost']])) {
                $row['nHost'] = $copyname[$row['nHost']];
            }
            $param .= $value . '=' . urlencode($row[$value]) . "\r\n";
        }
        if(isset($copyname[$row['nHost']])) {
            $row['nHost'] = $copyname[$row['nHost']];
        }
        $result .= $row['nHost'] . '=' . urlencode($param) . "\r\n";
    }
    return $result;
}

function filter_get($curuser, $filter, $fields) {
    echo filter_get_text($curuser, $filter, $fields);
}

function filter_get_zip($curuser, $filter, $fields) {
    $CreateZip = new CreateZipFile();
    //echo $fields;
    $CreateZip->addFile(filter_get_text($curuser, $filter, $fields), 'recuest.txt');
    //$CreateZip->forceDownload('recuest');            
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false);
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=request;" );
    header("Content-Transfer-Encoding: binary");
    echo $CreateZip->getZippedfile();
    //header("Content-Length: ".filesize($archiveName));
}

function table_description_l($t,$c){
     $sql = "SELECT column_comment FROM information_schema.columns
      WHERE table_name = '$t' AND column_name LIKE '$c'";
     global $idb;
     $query = $idb->query($sql);
     $v = mysql_fetch_row($query);
     if($v){
         return $v[0];
         }
     return 'Table description not found';
}

function field_list() {
    global $idb;
    echo "server_status_ok\r\n";
    $st = $idb->prepare('SHOW FULL COLUMNS FROM invmain');
    if($st->execute()){
        while ($row = $st->fetch(PDO::FETCH_ASSOC)){
            echo $row['Field'] . '=' . $row['Comment'] . "\r\n";            
        }
    }
}

function clearrequestvalues($keys){
    foreach ($keys as $value) {
        unset ($_REQUEST[$value]);
    }
}

function dbupdate($newvalues){
    global $curuser;
    if(isset($curuser)){
        //exit;
        $hosts = UrlEncodedIniToArray($newvalues);
        global $InvDBName;
        $list_f = mysql_list_fields($InvDBName, "invmain");
        $n = mysql_num_fields($list_f);
        $keys = array();
        for ($i = 0; $i < $n; $i++) {
            $keys[] = mysql_field_name($list_f, $i);
        }
        echo "server_status_ok\r\n";
        foreach ($hosts as $host => $values) {
            clearrequestvalues($keys);
            $hostdata = UrlEncodedIniToArray($values);
            foreach ($hostdata as $key => $value) {
                $_REQUEST[$key] = $value;
            }
            $_REQUEST['nHost'] = $host;
            //echo "Проверка связи";
            if(!AddInvDataToDB()) echo $host . "\r\n";
            //if (AddInvDataToDB()) echo "server_status_ok\r\n";
            //else $
        }
    } else echo "server_status_autherror\r\n";
}

function dbdelete($itemstodel){
    global $curuser;
    if(isset($curuser)){
        $hosts = explode(',', $itemstodel);
        $where = '';
        foreach ($hosts as $host) {
            $where .= '(nHost="' . $host .'") OR';
        }
        if($where != ''){
            global $idb;
            $query = 'DELETE FROM invmain WHERE ' . $where . ' false ' . user_filter();
            $idb->query($query);
            echo "server_status_ok\r\n";
            foreach ($hosts as $host) {
                echo $host;
            }
        }
    } else echo "У вас недостаточно прав для удаления записей из базы данных";
}

function dbadditem($source, $newitem){
    global $curuser;
    global $idb;
    $query = 'SELECT nHost FROM invmain WHERE nHost="' . $newitem .'"';
    $res = $idb->query($query);
    if(mysql_num_rows($res) > 0){
        echo 'DNS имя ' . $newitem . ' уже используется';
    }
    else {
        global $idb;
        $query = 'SELECT * FROM invmain WHERE nHost="' . $source . '"';
        $query = $idb->query($query);
        if($row = $query->fetch(PDO::FETCH_ASSOC)){
            $values = '';
            $field = '';
            $row['nHost'] = $newitem;
            $row['LastDataChanges'] = date ("Y-m-d H:i:s");
            foreach ($row as $key => $value) {
                if(!is_int($key)){
                    $values .= quote_smart($value) . ',';
                    $field .= $key . ',';
                }
            }
            $values = mb_substr($values, 0, mb_strlen($values)-1);
            $field = mb_substr($field, 0, mb_strlen($field)-1);
            global $idb;
            $query = 'INSERT INTO invmain (' . $field . ') VALUES (' . $values . ')';
            //echo $query;
            $res = $idb->query($query);
            //AddToEventLog("Добавление", $PlaceChanges, $curuser['dName'], 1, 1);
            echo "server_status_ok\r\n";
            echo $newitem . '=' . $source;
        } else echo 'Исходная запись - ' . $source . ' не найдена в базе данных';
    }
}

function DoLogin() {
    if(isset($_POST['login']) and isset($_POST['password'])) {
	$login = $_POST['login'];
	global $idb;
        $st = $idb->prepare('SELECT salt FROM usr WHERE login = :login LIMIT 1');	
        $st->bindValue('login', $login); 
	if ($st->execute() and ($st->rowCount() > 0)) {
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $salt = $row['salt'];
            $password = md5(md5($_POST['password']) . $salt);		
            $st = $idb->prepare('SELECT id FROM usr WHERE login = :login AND password = :password	LIMIT 1');		
            $st->bindValue('login', $login);
            $st->bindValue('password', $password);
            if($st->execute() and ($st->rowCount() > 0)) {
                $row = $st->fetch(PDO::FETCH_ASSOC);
                $_SESSION['user_id'] = $row['id'];
                $time = 86400 * 10; // ставим куку на 24 часа
                setcookie('login', $login, time() + $time, "/");
                setcookie('password', $password, time() + $time, "/");
                return true; // "server_status_ok";
            }
	}
    }
    return false; // "server_status_auth_login_wrong";
}



$action = $_GET['action'];

// Определяет тип запроса ZPinger

if (IsSet($action) and $action != '') {  // $action должен быть опредеeн
    switch ($action) {
        case 'auth':  // Авторизация
            if (DoLogin()) {
                echo 'server_status_ok';
            } else
                echo 'server_status_auth_login_wrong';
            break;
        ///////////////////////////////схемы////////////////////////////////////
        case 'get_pic':  // Получение изображения с сервера
            if (isset($curuser)) {
                if (isset($_POST['picid']))
                    download_file('zpinger/pic/' . $_POST['picid']);
                else
                    echo 'server_status_error';
            } else
                echo 'server_status_error';
            break;

        case 'get_map': // Получение схемы с сервера
            if (isset($curuser) & isset($_POST['mapid']) & can_getmap($curuser['login'], $_POST['mapid'])) {
                download_file('zpinger/map/' . $_POST['mapid']);
            } else
                echo 'server_status_error';
            break;

        case 'get_map_prop': // Получение характеристик схемы
            if (isset($curuser) & isset($_POST['mapid']) & can_getmap($curuser['login'], $_POST['mapid'])) {
                out_map_prop($_POST['mapid']);
            } else
                echo 'server_status_error';
            break;

        case 'set_map': // загрузка схемы на сервер
            if (isset($curuser) & isset($_FILES['f']) & isset($_POST['mapid']) & isset($_POST['map_prop_ini']) & can_editmap($curuser['login'], $_POST['mapid'], $curuser['isadmin'])) {
                if (move_uploaded_file($_FILES['f']['tmp_name'], 'zpinger/map/' . $_POST['mapid'])) {
                    update_map_prop($_POST['mapid'], $_POST['map_prop_ini']);
                } else
                    echo 'server_status_upload_failed';
            } else
                echo 'server_status_error';
            break;

        case 'set_pic': // загрузка изображения на сервер
            if (isset($curuser) & isset($_POST['picid']) & isset($_FILES['f'])) {
                move_uploaded_file($_FILES['f']['tmp_name'], 'zpinger/pic/' . $_POST['picid']);
                echo "server_status_ok";
            } else
                echo 'server_status_error';
            break;

        case 'get_userlist':
            if (isset($curuser)) {
                get_userlist();
            } else
                echo 'server_status_error';
            break;

        case 'get_struct': // Возвращает данные из базы для подстановки в фильтры
            if (isset($curuser)) {
                get_struct();
            } else
                echo 'server_status_error';
            break;

        case 'get_validpics': // Возвращает список ресурсов которые необходимо загрузить на сервере
            if (isset($curuser)) {
                get_validpics(explode("\r\n", $_POST['pics']));
            } else
                echo 'server_status_error';
            break;

        case 'get_hosts': // Выборка хостов из базы данных
            if (isset($curuser) & isset($_POST['filters'])) {
                get_hosts($_POST['filters']);
            } else
                echo 'server_status_error';
            break;

        case 'get_moder': // Заявки для модерации
            if (isset($curuser) & isset($_POST['lastid'])) {
                get_moder($_POST['lastid']);
            } else
                echo 'server_status_error';
            break;

        case 'get_work': // Заявки для исполнения
            if (isset($curuser) & isset($_POST['lastid'])) {
                get_works($curuser['login'], $_POST['lastid']);
            } else
                echo 'server_status_error';
            break;

        case 'get_messages': // Заявки для модерации
            if (isset($curuser) & isset($_POST['lastid'])) {
                get_works($curuser['login'], $_POST['lastid']);
            } else
                echo 'server_status_error';
            break;

        case 'get_rt_data': // User Real Time Data
            if (isset($curuser)) {
                if(isset($_POST['locked'])) {
                    update_lock($curuser, $_POST['locked']);
                }
                get_rt_data($curuser);
            } else
                echo 'server_status_error';
            break;

        case 'lock_map': // Блокировка редактирования схемы
            if (isset($curuser) & isset($_POST['mapid'])) {
                lock_map($curuser, $_POST['mapid']);
            } else
                echo 'server_status_error';
            break;

        case 'unlock_map': // Снятие блокировки схемы
            if (isset($curuser) & isset($_POST['mapid'])) {
                unlock_map($curuser, $_POST['mapid']);
            } else
                echo 'server_status_error';
            break;

        case 'filter_get': // Проверка фильтра
            if (isset($curuser)) {
                filter_get_zip($curuser, $_POST['filter'], $_POST['fields']);
            } else
                echo 'server_status_error';
            break;

        case 'dbupdate': // Обновление данных в базе
            if (isset($curuser)) {
                dbupdate($_POST['newvalues']);
            } else
                echo 'server_status_error';
            break;


        case 'additemcopy': // Добавление элемента в базу путем копирования существующего
            if (isset($curuser)) {
                dbadditem($_POST['sourceitem'], $_POST['newitem']);
            } else
                echo 'server_status_error';
            break;

        case 'delitems': // Удаление элемента из базы данных 
            if (isset($curuser)) {
                dbdelete($_POST['itemstodel']);
            } else
                echo 'server_status_error';
            break;

        case 'field_list': // Получение списка полей базы данных
            if (isset($curuser)) {
                field_list();
            } else
                echo 'server_status_error';
            break;
    } // switch
}

