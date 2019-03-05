<?php

include_once 'admin/mysqlconnect.php';

function AddToEventLog($EventType, $EventText, $EventOwner, $IsSystemEvent, $EventLevel) {
    
    global $curuser;    
    if (isset($curuser)) {
        $table = 'dbeditlog';
        $EventOwner = ($curuser['dname'] != '') ? $curuser['dname'] : '';
    } else {
        $table = 'eventlog';
        $EventOwner = $EventOwner;
    }
    global $idb;
    $st = $idb->prepare('INSERT INTO '.$table.' (EventType, EventText, EvetOwner, IsSysEvent, EventLevel, EventDate)
                            VALUES (:EventType, :EventText, :EvetOwner, :IsSysEvent, :EventLevel, CURRENT_TIMESTAMP)'); 
    $st->bindValue('EventType', $EventType);
    $st->bindValue('EventText', trim($EventText));
    $st->bindValue('EvetOwner', $EventOwner);
    $st->bindValue('IsSysEvent', $IsSystemEvent);
    $st->bindValue('EventLevel', $EventLevel);
    $st->execute();
}

function GetNewLnArray($newlines, $oldlines) {
// Возвращает строки из $newlines которых нет в $oldlines  
    $result = array();
    $arr1 = array_flip(explode("\r\n", $oldlines));
    $arr2 = explode("\r\n", $newlines);    
    foreach ($arr2 as $value) {
        if(!isset($arr1[$value])){
            $result[] = $value;
        }
    }
    return $result;
}

function GetNewLn($newlines, $oldlines) {
// Возвращает строки из $newlines которых нет в $oldlines  
    return implode("\r\n", GetNewLnArray($newlines, $oldlines));
}

function LastChangesDateTime($dbrow) {
    foreach ($dbrow as $key => $value) {
        if(isset($_REQUEST[$key]) and !in_array($key, array('LastUpdate', 'LastDataChanges', 'ignorecnt')) and ((trim($_REQUEST[$key])) != trim($dbrow[$key]))) {
             return date("Y-m-d H:i:s");
        }
    }    
    if (isset($dbrow['LastDataChanges']))
        return $dbrow['LastDataChanges'];
    else
        return date("Y-m-d H:i:s");
}

function ErrorLog($errortext) {
    global $idb;
    $st = $idb->prepare('"INSERT INTO errorlog (errortext) VALUES (:errortext)');
    $st->bindValue('errortext', $errortext);    
    $st->execute();
}

function CheckValue($field, $row, $msg_change, $msg_new) {
    if (isset($_REQUEST[$field]) and (mb_strtoupper($_REQUEST[$field], 'utf8') != mb_strtoupper($row[$field], 'utf8'))){
        // Переданное значение отличается от имеющегося
        if($row[$field] == ''){
            // Новое значение
            $result = str_replace('%old%', $row[$field], $msg_new);
            $result = str_replace('%new%', $_REQUEST[$field], $result);
            return $result . "\r\n";
        } else {
            $result = str_replace('%old%', $row[$field], $msg_change);
            $result = str_replace('%new%', $_REQUEST[$field], $result);
            return $result . "\r\n";            
        }
    }
}

function AddInvDataToDB() {
    $trimfields = array('plSity' => true, // Текстовые поля для которых нужно применять trim()
                        'plCompany' => true,
                        'plFilial' => true,
                        'plAdress' => true,
                        'plBuilding' => true,
                        'plCab' => true,
                        'cOtd' => true,
                        'cTitle' => true,
                        'cTelephone' => true,
                        'cTelephoneLocal' => true,
                        'cTelephoneCell' => true,
                        'cEmail' => true,
                        'cJabber' => true,
                        'cICQ' => true,
                        'cMailRu' => true,
                        'hSystemName' => true,
                        'hSystemVendor' => true,
                        'hMainboardModel' => true,
                        'hMainboardSerialNumber' => true,
                        'hMainboardManufacturer' => true,
                        'hBiosVendor' => true,
                        'hBiosVersion' => true,
                        'hBiosDate' => true,
                        'hProcessor' => true,
                        'hProcessorSoket' => true,
                        'hRAMtype' => true,
                        'hHDD' => true,
                        'hVGA' => true,
                        'hMultimedia' => true,
                        'hKeyboard' => true,
                        'hMouse' => true,
                        'hNetwork' => true,
                        'hPilot' => true,
                        'hCDDVD' => true,
                        'pMonitor' => true,
                        'pPrinter' => true,
                        'pScaner' => true,
                        'pUPSmodel' => true,
                        'inMonitor' => true,
                        'inMainBox' => true,
                        'inPrinter' => true,
                        'inScaner' => true,
                        'inUPS' => true,
                        'inMOL' => true,
                        'inGno' => true,
                        'inGFirma' => true,
                        'sOS' => true,
                        'sOSLegalComment' => true,
                        'sOSSP' => true,
                        'sOSInsDate' => true,
                        'sOSSN' => true,
                        'sOSLicenseType' => true,
                        'sMSOffice' => true,
                        'sOfficeLegalComent' => true,
                        'sMSOfficeSN' => true,
                        'sScreenResolution' => true,
                        'sWindir' => true,
                        'sMyDock' => true,
                        'sSoftOthelLegalComment' => true,
                        'nHost' => true,
                        'nDomain' => true,
                        'nIP' => true,
                        'nMAC' => true,
                        'nUserLogin' => true,
                        'nIPConfigAll' => true,
                        'LastDataChanges' => true,
                        'dop_oborudovanie1' => true,
                        'dop_oborudovanie' => true,
                        'nomer_shemy' => true);
    
    $sysfields = array('nHost' => true, // Поля обновляемые системой
                       'LastDataChanges' => true,
                       'LastUpdate' => true,
                       'ignorecnt' => true,
                       'lassUserDataUpdateTime' => true        
    );
    if (IsSet($_REQUEST['nHost'])) {
        global $idb;
        $st = $idb->prepare("SELECT invmain.* FROM invmain WHERE invmain.nHost = :nHost LIMIT 1");        
        if ($st->execute(array('nHost' => $_REQUEST['nHost'])) and ($st->rowCount() > 0)) {// Информацио о компьютере уже присутствует в базе данных
            $row = $st->fetch(PDO::FETCH_ASSOC);

            $updateset = '';
            $params = array();
            foreach ($row as $key => $value) {
                if(isset($_REQUEST[$key]) and !isset($sysfields[$key])){
                    if(isset($trimfields[$key])) {
                        $updateset .= $key .' = TRIM(:'.$key.'), ';
                    } else {
                        $updateset .= $key .' = :'.$key.', ';
                    }
                    $params[$key] = $_REQUEST[$key];
                }
            }
            $params['nHost'] = $_REQUEST['nHost'];
          
            global $idb;
            $st = $idb->prepare('UPDATE invmain SET ' .
                    $updateset .
                    'LastUpdate = CURRENT_TIMESTAMP,
                     LastDataChanges = "' . LastChangesDateTime($row) . '"
                WHERE nHost=:nHost');
            if ($st->execute($params) === false) {
                ErrorLog('AddInvDataToDB - ошибка обновления данных о ' . $_REQUEST['nHost'] . ' (' . $_SERVER['REMOTE_ADDR'] . ")\n\r'" . $query . "'\n\r'" . mysql_error());
                $idb->query($query);
                return false;
            }
            //Изменение расположения рабочего места
            $Changes  = CheckValue('plSity',     $row, 'Город изменён с %old% на %new%.',        'Получены новые данные - город: %new%');
            $Changes .= CheckValue('plCompany',  $row, 'Организация изменена с %old% на %new%.', 'Получены новые данные - организация: %new%');
            $Changes .= CheckValue('plFilial',   $row, 'Филиал изменен с %old% на %new%.',       'Получены новые данные - филиал: %new%');
            $Changes .= CheckValue('plAdress',   $row, 'Адрес изменён с %old% на %new%.',        'Получены новые данные - адрес: %new%');
            $Changes .= CheckValue('plBuilding', $row, 'Корпус изменён с %old% на %new%.',       'Получены новые данные - корпус: %new%');
            $Changes .= CheckValue('plCab',      $row, 'Кабинет изменён с %old% на %new%.',      'Получены новые данные - кабинет: %new%');            
            if ($Changes != "")  AddToEventLog("Изменение расположения рабочего места", $Changes, $_REQUEST['nHost'], 1, 1);
            
            //Изменение контактной информации
            $Changes  = CheckValue('cOtd',              $row, 'Отдел изменён с %old% на %new%.',                  'Получены новые данные - отдел: %new%');
            $Changes .= CheckValue('cTitle',            $row, 'Должность изменена с %old% на %new%.',             'Получены новые данные - должность: %new%');
            $Changes .= CheckValue('cTelephone',        $row, 'Основной телефон изменен с %old% на %new%.',       'Получены новые данные - основной телефон: %new%');
            $Changes .= CheckValue('cTelephoneLocal',   $row, 'Внутренний телефон изменен с %old% на %new%.',     'Получены новые данные - организация: %new%');
            $Changes .= CheckValue('cEmail',            $row, 'Электронная почта изменена с %old% на %new%.',     'Получены новые данные - электронная почта: %new%');
            $Changes .= CheckValue('cJabber',           $row, 'Учетная запись Jabber изменена с %old% на %new%.', 'Получены новые данные - учетная запись Jabber: %new%');
            $Changes .= CheckValue('cSkype',            $row, 'Учетная запись Skype изменена с %old% на %new%.',  'Получены новые данные - учетная запись Skype: %new%');
            if ($Changes != "") AddToEventLog("Изменение контактной информации", $Changes, $_REQUEST['nHost'], 1, 1);
            
            //Изменение конфигурации оборудования
            $Changes  = CheckValue('hMainboardManufacturer', $row, 'Производитель материнской платы изменён с %old% на %new%.',  'Получены новые данные - производитель материнской: %new%');
            $Changes .= CheckValue('hMainboardModel',        $row, 'Модель материнской платы изменена с %old% на %new%.',        'Получены новые данные - модель материнской: %new%');
            $Changes .= CheckValue('hMainboardSerialNumber', $row, 'Серийный номер материнской платы изменён с %old% на %new%.', 'Получены новые данные - серийный номер материнской платы: %new%');
            $Changes .= CheckValue('hBiosVersion',           $row, 'Версия BIOS изменена с %old% на %new%.',                     'Получены новые данные - версия BIOS: %new%');
            $Changes .= CheckValue('hProcessor',             $row, 'Процессор изменён с %old% на %new%.',                        'Получены новые данные - процессор: %new%');
            if(isset($_REQUEST['hRAM']) and  ($_REQUEST['hRAM'] != '') and ($_REQUEST['hRAM'] !=0)) {
                $Changes .= CheckValue('hRAM',                   $row, 'Объем оперативной памяти изменён с %old% на %new%.',         'Получены новые данные - объем оперативной памяти: %new%');
            }    
            $Changes .= CheckValue('pMonitor',               $row, 'Монитор изменён с %old% на %new%.',                          'Получены новые данные - монитор: %new%');
            if ($Changes != "") AddToEventLog("Изменение конфигурации оборудования", $Changes, $_REQUEST['nHost'], 1, 1);
            
            //Изменение настроек програмного обеспечения
            $Changes  = CheckValue('sOS',            $row, 'Операционная ситема изменена с %old% на %new%.',                    'Получены новые данные - операционная система: %new%');
            $Changes .= CheckValue('sOSSP',          $row, 'Пакет обновления операционной системы изменён с %old% на %new%.',   'Получены новые данные - пакет обновления ОС: %new%');
            $Changes .= CheckValue('sOSInsDate',     $row, 'Дата установки операционной системы изменёна с %old% на %new%.',    'Получены новые данные - дата установки ОС: %new%');         
            $Changes .= CheckValue('sOSSN',          $row, 'Серийный номер операционной системы изменён с %old% на %new%.',     'Получены новые данные - Серийный номер операционной системы: %new%');
            $Changes .= CheckValue('sOSLicenseType', $row, 'Тип лицензии ОС изменён с %old% на %new%.',                         'Получены новые данные - Тип лицензии ОС: %new%');
            $Changes .= CheckValue('sMSOffice',      $row, 'Версия установленного MS Office изменена с %old% на %new%.',        'Получены новые данные - Версия установленного MS Office: %new%');
            $Changes .= CheckValue('sMSOfficeSN',    $row, 'Серийный номер MS Office изменён с %old% на %new%.',                'Получены новые данные - Серийный номер MS Office: %new%');
            $Changes .= CheckValue('nDomain',        $row, 'Домен к которому принадлежит узел изменён с %old% на %new%.',       'Получены новые данные - домен: %new%');
            $Changes .= CheckValue('nIP',            $row, 'IP адрес изменён с %old% на %new%.',                                'Получены новые данные - IP адрес: %new%');
            $Changes .= CheckValue('nMAC',           $row, 'MAC адрес основного сетевого интерфейса изменён с %old% на %new%.', 'Получены новые данные - MAC адрес: %new%');
            if ($Changes != "") AddToEventLog("Изменение настроек програмного обеспечения", $Changes, $_REQUEST['nHost'], 1, 1);
            
            // Поля с множественными значениями
            // Установка и удаление оборудования 
            $Changes = '';
            if (isset($_REQUEST['hHDD']) and ($_REQUEST['hHDD'] != $row['hHDD'])) {
                $s = GetNewLn($row['hHDD'], $_REQUEST['hHDD']);
                if ($s != '')
                    $Changes .= "Удалены жесткие диски:\r\n" . $s . "\r\n";
                $s = GetNewLn($_REQUEST['hHDD'], $row['hHDD']);
                if ($s != '')
                    $Changes .= "Установлены жесткие диски:\r\n" . $s . "\r\n";
            }
            if (isset($_REQUEST['hMultimedia']) and ($_REQUEST['hMultimedia'] != $row['hMultimedia'])) {
                $s = GetNewLn($row['hMultimedia'], $_REQUEST['hMultimedia']);
                if ($s != '')
                    $Changes .= "Удалены платы мультимедиа:\r\n" . $s . "\r\n";
                $s = GetNewLn($_REQUEST['hMultimedia'], $row['hMultimedia']);
                if ($s != '')
                    $Changes .= "Установлены платы мультимедиа:\r\n" . $s . "\r\n";
            }
            if (isset($_REQUEST['hRAMtype']) and ($_REQUEST['hRAMtype'] != $row['hRAMtype'])) {
                $s = GetNewLn($row['hRAMtype'], $_REQUEST['hRAMtype']);
                if ($s != '')
                    $Changes .= "Удалены модули памяти:\r\n" . $s . "\r\n";
                $s = GetNewLn($_REQUEST['hRAMtype'], $row['hRAMtype']);
                if ($s != '')
                    $Changes .= "Установлены модули памятии:\r\n" . $s . "\r\n";
            }
            if (isset($_REQUEST['hNetwork']) and ($_REQUEST['hNetwork'] != $row['hNetwork'])) {
                $s = GetNewLn($row['hNetwork'], $_REQUEST['hNetwork']);
                if ($s != '')
                    $Changes .= "Удалены сетевые адаптеры:\r\n" . $s . "\r\n";
                $s = GetNewLn($_REQUEST['hNetwork'], $row['hNetwork']);
                if ($s != '')
                    $Changes .= "Установлены сетевые адаптеры:\r\n" . $s . "\r\n";
            }

            if (isset($_REQUEST['hVGA']) and ($_REQUEST['hVGA'] != $row['hVGA'])) {
                $s = GetNewLn($row['hVGA'], $_REQUEST['hVGA']);
                if ($s != '')
                    $Changes .= "Удалены видео адаптеры:\r\n" . $s . "\r\n";
                $s = GetNewLn($_REQUEST['hNetwork'], $row['hNetwork']);
                if ($s != '')
                    $Changes .= "Установлены видео адаптеры:\r\n" . $s . "\r\n";
            }
            if ($Changes != "") AddToEventLog("Установка и удаление оборудования", $Changes, $_REQUEST['nHost'], 1, 1);          
            
            //Установка и удаление программ
            $Changes = '';
            if (isset($_REQUEST['sFullList']) and ($_REQUEST['sFullList'] != $row['sFullList'])) {
                $s_deleted = GetNewLnArray($row['sFullList'], $_REQUEST['sFullList']);                
                $s_installed = GetNewLnArray($_REQUEST['sFullList'], $row['sFullList']); // Установленное ПО
                $_deleted = array_flip($s_deleted);
                $_installed = array_flip($s_installed);
                $updated = array();
                //Определение обновления ПО
                $re = '/(\d+\.)?(\d+\.)?(\*|\d+)/';
                foreach ($s_deleted as $key => $deleted) {
                    $deleted_wo_ver = preg_replace($re, '$deleted', $deleted);
                    foreach ($s_installed as $key => $installed) {
                        if($deleted_wo_ver == preg_replace($re, '$deleted', $installed)) { // Обновление ПО
                            $updated[$deleted] = $installed;
                            unset($_deleted[$deleted]);
                            unset($_installed[$installed]);
                        }
                    }
                }
                if (count($_deleted) > 0)
                    $Changes .= "Удалено програмное обеспечение:\r\n" . implode("\r\n", array_flip($_deleted)) . "\r\n";
                if (count($_installed) > 0)
                    $Changes .= "Установлено програмное обеспечение:\r\n" . implode("\r\n", array_flip($_installed)) . "\r\n";
                if ($Changes != "") AddToEventLog("Установка и удаление программ", $Changes, $_REQUEST['nHost'], 1, 1);
                
                $Changes = '';
                if (count($updated) > 0) {
                    $Changes .= 'Обновлено програмное обеспечение:' . "\r\n";
                    foreach ($updated as $key => $value) {
                        $Changes .= $key . ' до версии ' . $value . "\r\n";
                    }
                    AddToEventLog("Обновление программного обеспечения", $Changes, $_REQUEST['nHost'], 1, 1);
                }
                
            }
            //mysql_free_result($res);
        } else {
            $keys = array();
            for ($i=0; $i < $st->columnCount(); $i++) {                
                $key = $st->getColumnMeta($i)['name'];
                unset($sysfields['nHost']);
                if(isset($_REQUEST[$key]) and !isset($sysfields[$key])){
                    $keys[$key] = ':'.$key;                    
                }
            };            
            foreach ($keys as $key => $value) {
                if(isset($trimfields[$key])) {                    
                    $keys[$key] = 'TRIM('.$value.')';
                } else {
                    $keys[$key] = $value;
                }
            }
            $st = $idb->prepare('INSERT INTO invmain ('.implode(',', array_keys($keys)).', LastDataChanges, LastUpdate) VALUES ('.implode(',', $keys).', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
            foreach ($keys as $key => $value) {
                $st->bindValue($key, $_REQUEST[$key]);
            }

            if ($st->execute() === false) {                
                ErrorLog('AddInvDataToDB - ошибка добавления данных о ' . $_REQUEST['nHost'] . ' (' . $_SERVER['REMOTE_ADDR'] . ")\n\r'" . $st->query . "'\n\r'" . mysql_error());                
                return false;
            } else
                AddToEventLog("Добавление нового компьютера в базу данных",
                        "Данные о рабочем месте (хост - " . $_REQUEST['nHost'] . ") успешно добавлены в базу данных.",
                        $_REQUEST['nHost'], 1, 1);
        }


        // echo 'asdasd' . $_REQUEST['nHost'];
        return true;
    } else
        return false;
}

function OutUrlEncodedINI($hostname) {

    if (IsSet($hostname)) {
        global $idb;
        $query = "SELECT invmain.* FROM invmain WHERE invmain.nHost = '" . $hostname . "'";        
        if (($res = $idb->query($query)) and ($res->rowCount() > 0)) { // Информацио о компьютере присутствует в базе данных
            $row = $res->fetch(PDO::FETCH_ASSOC);
            global $InvDBName;
            global $idb;
            $q = $idb->prepare("DESCRIBE ".$InvDBName);
            $q->execute();
            $list_f = $q->fetchAll(PDO::FETCH_COLUMN);            
            $n = count($list_f);
            echo "server_status_ok\r\n";
            for ($i = 0; $i < $n; $i++) {
                $name_f = mysql_field_name($list_f, $i);
                echo $name_f . '=' . urlencode($row[$name_f]) . "\r\n";
            }
            //echo "server_status_ok";
        } else
            echo "server_status_hostnotfound";
    } else
        echo "server_status_hostnotfound";
}

