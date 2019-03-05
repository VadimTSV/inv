<?php
include_once ("admin/mysqlconnect.php");
include_once ("inv_global.php");
global $idb;


session_start();

function GetConfig($field = 'value'){
 /*
  * Запрос конфигурации клиентского модуля
  */
    global $idb;
    $st = $idb->prepare('SELECT * FROM client_config');
    if($st->execute()){
        echo "server_status_ok\r\n";
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            if($row[$field] != ''){
                echo $row['param'] . '=' . urlencode($row[$field]) ."\r\n";            
            }
        }
    } else {
        echo "server_status_db_error\r\n";
        print_r($st->errorInfo());
    }
}

function GetValues($hostname) {

    if (IsSet($hostname)) {
        global $idb;
        $st = $idb->prepare("SELECT invmain.* FROM invmain WHERE invmain.nHost = :nHost");        
        $st->bindValue('nHost', $_REQUEST['nHost']);
        if($st->execute()){
            if($st->rowCount() > 0) { // Информацио о компьютере присутствует в базе данных
                $row = $st->fetch(PDO::FETCH_ASSOC);            
                echo "server_status_ok\r\n";
                foreach ($row as $key => $value) {
                    echo $key . '=' .urlencode($value) . "\r\n";
                }            
            } else {
                echo "server_status_ok\r\n" . "host not found";
            }
        } else {
            echo "server_status_db_error\r\n";
            print_r($st);
        }
    } else {
        echo "server_status_param_error\r\n";
    }
}

function GetValuesDef(){
 /*
  * Запрос значений по умолчанию для клиентского модуля
  */
    function WtiteValuesOf($field){
        global $idb;
        $st = $idb->prepare("SELECT
                                invmain.".$field."
                              FROM
                                invmain
                              WHERE 
                                invmain.".$field." <> ''
                              GROUP BY
                                invmain.".$field."
                              ORDER BY
                                 invmain.".$field);      
        echo $field.'=';
        if($st->execute()){
            while ($row = $st->fetch(PDO::FETCH_ASSOC)){
                echo urlencode($row[$field]).'%0D%0A';
            }
            unset($res);
            echo "\r\n";
        }
    }

    echo "server_status_ok\r\n";
    WtiteValuesOf('plSity');
    WtiteValuesOf('plCompany');
    WtiteValuesOf('plFilial');
    WtiteValuesOf('plAdress');
    WtiteValuesOf('cOtd');
    WtiteValuesOf('cTitle');

    global $idb;
    $query = "SELECT
                zayavki.profile
              FROM
                zayavki
              GROUP BY
                zayavki.profile
              ORDER BY
                zayavki.profile";

      $res = $idb->query($query);
      echo 'profile=';
      while ($row = $res->fetch(PDO::FETCH_ASSOC)){
        echo urlencode($row["profile"]).'%0D%0A';
      }
      unset($res);
      echo "\r\n";
}

function GetUserMessages(){
/*
 *  Получение сообщений для клиентских модулей
 */    
    // обновление статуса клиентского модуля
    global $idb;
    $st = $idb->prepare('INSERT INTO clientstatus (nHost, LastActive) VALUES (:nHost, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE LastActive = CURRENT_TIMESTAMP'); 
    $st->bindValue('nHost', $_REQUEST['nHost']);
    $st->execute();
    global $idb;
    $LastCheck = $idb->query('SELECT CURRENT_TIMESTAMP')->fetchColumn(); 
    $st = $idb->prepare('SELECT * FROM messages 
                                WHERE (date >= :LastCheck) AND (confirmdate IS NULL) AND (DATE_ADD(expire,INTERVAL 1 DAY) > CURRENT_TIMESTAMP) 
                                AND
                                (
                                  recipient = :nHost OR 
                                  (      
                                    brodcast=1 AND (SELECT count(*) FROM  messages_confirm WHERE messageID = id AND messages_confirm.nHost = :nHost) = 0
                                  )
                                ) ORDER BY date');
    $st->bindValue('LastCheck', isset($_REQUEST['LastCheck']) ? $_REQUEST['LastCheck'] : '0000-00-00');
    $st->bindValue('nHost', $_REQUEST['nHost']);
    if($st->execute() and ($st->rowCount() > 0)) { // Имеются новые сообщения
        echo "server_status_ok\r\n"; // 
        echo $LastCheck . "\r\n"; // Возвращаем время последней проверки для оптимизации последующих запросов
        while($message = $st->fetch(PDO::FETCH_ASSOC)){
            if($message['brodcast']) { // Для объявлений
                if(!isset($host)){ // Получаем параметры хоста для возможности фильтрации
                    $hostst = $idb->prepare('SELECT * FROM invmain WHERE nHost = :nHost');
                    $hostst->bindValue('nHost', $_REQUEST['nHost']);
                    if($hostst->execute() and ($hostst->rowCount() > 0)) {
                        $host = $hostst->fetch(PDO::FETCH_ASSOC);
                    } else {
                        break;
                    }
                }
                // Проверка фильтров для объявлений
                if(     (!isset($message['plSity']) or ($message['plSity'] == '')    or ($host['plSity'] == $host['plSity']))
                    and (!isset($message['plCompany']) or ($message['plCompany'] == '') or ($host['plCompany'] == $host['plCompany']))
                    and (!isset($message['plFilial']) or ($message['plFilial'] == '')  or ($host[''] == $host['plFilial']))
                    and (!isset($message['cOtd']) or ($message['cOtd'] == '') or ($host['cOtd'] == $host['cOtd'])) ){
                    
                    echo urlencode($message['id']).'%0D%0A'.
                        urlencode($message['date']).'%0D%0A'.
                        urlencode($message['priority']).'%0D%0A'.
                        urlencode($message['zid']).'%0D%0A'.
                        urlencode($message['mtype']).'%0D%0A'.
                        urlencode($message['brodcast']).'%0D%0A'.
                        urlencode($message['message'])."\r\n";
                }
                
            } else {
                echo urlencode($message['id']).'%0D%0A'.
                     urlencode($message['date']).'%0D%0A'.
                     urlencode($message['priority']).'%0D%0A'.
                     urlencode($message['zid']).'%0D%0A'.
                     urlencode($message['mtype']).'%0D%0A'.
                     urlencode($message['brodcast']).'%0D%0A'.
                     urlencode($message['message'])."\r\n";
            }            
        }        
    } else {
        echo "server_status_ok\r\n"; // 
        echo $LastCheck . "\r\n"; // Возвращаем время последней проверки для оптимизации последующих запросов
    }  
}

function ConfirmMessage(){
    if(isset($_REQUEST['mID'])){
        global $idb;
        if(isset($_REQUEST['broadcast']) and $_REQUEST['broadcast']){
            $st = $idb->prepare('INSERT INTO messages_confirm (nHost, messageID) VALUES (:nHost, :id)');
            $st->bindValue('nHost', $_REQUEST['nHost']);
            $st->bindValue('id', $_REQUEST['mID']);
        } else {
            $st = $idb->prepare('UPDATE messages SET confirmdate = CURRENT_TIMESTAMP WHERE id = :id');
            $st->bindValue('id', $_REQUEST['mID']);           
        }
        if($st->execute()){
            if(isset($_REQUEST['zID'])) { // Подтверждение выполнения заявки
                $zst = $idb->prepare('UPDATE zayavki SET '
                        . 'enddate = CURRENT_TIMESTAMP, '
                        . 'usercomment=:usercomment, '
                        . 'answerrate=:answerrate '
                        . 'WHERE id=:zID');
                $zst->bindValue('zID', $_REQUEST['zID']);
                $zst->bindValue('usercomment', $_REQUEST['usercomment']);
                $zst->bindValue('answerrate', $_REQUEST['answerrate']);
                if($zst->execute()){
                    echo "server_status_ok\r\n";
                } else {
                    echo "server_status_dbfail\r\n";
                }                
            } else {
                echo "server_status_ok\r\n";
            }            
        } else {
            echo "server_status_dbfail\r\n";
        }
    } else {
        echo "server_status_fail\r\n";
        echo "Недопустимый набор параметров.";
    }
}

function AddZ(){
    global $idb;
    $st = $idb->prepare("INSERT INTO zayavki 
            ( zFIO,
              zayavka,
              `date`,
              Owner,
              plSity,
              plCompany,
              plFilial,
              plAdress,
              plCab,
              plBuilding,
              cOtd,
              cTitle,
              cTelephone,
              cTelephoneLocal,
              profile             
            )
              VALUES
            ( :zFIO,
              :zayavka,
              CURRENT_TIMESTAMP,
              :Owner,
              :plSity,
              :plCompany,
              :plFilial,
              :plAdress,
              :plCab,
              :plBuilding,
              :cOtd,
              :cTitle,
              :cTelephone,
              :cTelephoneLocal,
              :profile
            )");
            
    $st->bindValue('zFIO', isset($_REQUEST['zFIO'])? $_REQUEST['zFIO'] : '');
    $st->bindValue('zayavka', isset($_REQUEST['zayavka'])? $_REQUEST['zayavka'] : '');
    $st->bindValue('Owner', isset($_REQUEST['nHost'])? $_REQUEST['nHost'] : '');
    $st->bindValue('plSity', isset($_REQUEST['plSity'])? $_REQUEST['plSity'] : '');
    $st->bindValue('plCompany', isset($_REQUEST['plCompany'])? $_REQUEST['plCompany'] : '');
    $st->bindValue('plFilial', isset($_REQUEST['plFilial'])? $_REQUEST['plFilial'] : '');
    $st->bindValue('plAdress', isset($_REQUEST['plAdress'])? $_REQUEST['plAdress'] : '');
    $st->bindValue('plCab', isset($_REQUEST['plCab'])? $_REQUEST['plCab'] : '');
    $st->bindValue('plBuilding', isset($_REQUEST['plBuilding'])? $_REQUEST['plBuilding'] : '');
    $st->bindValue('cOtd', isset($_REQUEST['cOtd'])? $_REQUEST['cOtd'] : '');
    $st->bindValue('cTitle', isset($_REQUEST['cTitle'])? $_REQUEST['cTitle'] : '');
    $st->bindValue('cTelephone', isset($_REQUEST['cTelephone'])? $_REQUEST['cTelephone'] : '');
    $st->bindValue('cTelephoneLocal', isset($_REQUEST['cTelephoneLocal'])? $_REQUEST['cTelephoneLocal'] : '');
    $st->bindValue('profile', isset($_REQUEST['profile'])? $_REQUEST['profile'] : '');    
    if($st->execute()) {
        echo "server_status_ok\r\n";
        echo $idb->lastInsertId();
    } else {
       echo "server_status_dbfail\r\n";             
    } 
}

if(isset($_REQUEST['nHost']) and isset($_REQUEST['reason'])){
    switch ($_REQUEST['reason']) {
        case 'get_config':
            GetConfig();
            break;
        
        case 'get_config_data':
            GetConfig('data');
            break;
        
        case 'get_values':
            GetValues($_REQUEST['nHost']);
            break;
        
        case 'get_valuesdef':
            GetValuesDef();
            break;        
        case 'add_data':
            AddInvDataToDB();
            break;        
        
        case 'get_messages':
            GetUserMessages();
            break;
        
        case 'confirm_message':
            ConfirmMessage();
            break;
        
        case 'addz':
            AddZ();
            break;

        default:
            echo "server_status_not_supported\r\n";
            break;
    }
} else {
    echo "WRONG REQUEST!";
}

