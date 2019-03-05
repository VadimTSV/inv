<?php
include_once ("admin/mysqlconnect.php");

function SubmitWork() {
    global $curuser;
    global $idb;
    if (IsSet($_POST['1-action'])) { // Обработка Submit
        $i = 1;
        $s = $i . '-action';
        while (IsSet($_POST[$s])) {
            $arr = explode("-", $_POST[$s]);
            $action = $arr[0];
            $zid = $arr[1];
            if ($action == 'accept') {                
                $query = "SELECT zayavki.id, zayavki.owner, zayavki.zayavka, zayavki.Active FROM zayavki WHERE (zayavki.id='$zid')";
                $sql_owner = $idb->query($query);
                if ($str = $sql_owner->fetch(PDO::FETCH_ASSOC))
                    if ($str['Active']) {
                        $oowner = $str['owner'];
                        $ii = 'worker-' . $i;

                        //echo $_POST[$ii];

                        global $idb;
                        $query = "SELECT * FROM usr WHERE login='" . $_POST[$ii] . "'";
                        $mysqlusr = $idb->query($query);
                        if ($row = $mysqlusr->fetch(PDO::FETCH_ASSOC)) {
                            $user = $row['dname'];
                            $title = $row['title'];
                        }

                        $mmessage = 'Модератор ' . $curuser['title'] . ' ' . $curuser['dname'] . ' передал вашу заявку на исполнение.\r\nОтвественный за исполение заявки: ' . $title . ' ' . $user . '\r\nЗаявка:\r\n' . StrCut($str['zayavka'], 50);
                        $mquery = "INSERT INTO messages (date, brodcast, message, recipient, zid, mtype, priority,expire) VALUES (current_timestamp,'0','$mmessage','$oowner', '$zid', 'zacept', 'hi', DATE_ADD(CURRENT_TIMESTAMP,INTERVAL 30 DAY))";
                        $sql_query = $idb->query($mquery);
                        $zquery = 'UPDATE zayavki SET zayavki.moderator="' . $curuser['dname'] . '",  zayavki.moderated=1, zayavki.worker="' . $_POST[$ii] . '" WHERE (zayavki.id=' . $zid . ')';
                        $sql_query = $idb->query($zquery);
                    }
            }

            if ($action == 'drop') {

                $sql_owner = $idb->query("SELECT zayavki.id, zayavki.owner, zayavki.zayavka, zayavki.Active FROM zayavki WHERE (zayavki.id='$zid')");
                if ($str = $sql_owner->fetch(PDO::FETCH_ASSOC))
                    if ($str['Active']) {
                        $oowner = $str['owner'];
                        $ii = 'DropReason-' . $i;
                        $mmessage = 'Модератор ' . $curuser['title'] . ' ' . $curuser['dname'] . ' отказал в обслуживании заявки - "' . StrCut($str['zayavka'], 50) . '"\r\nПричина:\r\n' . $_POST[$ii];
                        $mquery = "INSERT INTO messages (date, brodcast, message, recipient, zid, mtype, priority,expire) VALUES (current_timestamp,'0','$mmessage','$oowner', '$zid', 'zdrop', 'highest',DATE_ADD(CURRENT_TIMESTAMP,INTERVAL 30 DAY))";
                        $sql_query = $idb->query($mquery);
                        $zquery = "UPDATE zayavki SET zayavki.moderator='" . $curuser['dname'] . "', zayavki.Active=0, zayavki.moderated=1, zayavki.enddate=(current_timestamp), zayavki.answer='" . $mmessage . "'  WHERE (zayavki.id=" . $zid . ")";
                        $sql_query = $idb->query($zquery);
                    }
            }

            if ($action == 'done') {
                global $idb;
                $query = "SELECT zayavki.id, zayavki.owner, zayavki.zayavka, zayavki.Active FROM zayavki WHERE (zayavki.id='$zid')";
                $sql_owner = $idb->query($query);
                if ($str = $sql_owner->fetch(PDO::FETCH_ASSOC))
                    if ($str['Active']) {
                        $oowner = $str['owner'];
                        $ii = 'DropReason-' . $i;

                        $mmessage = $curuser['title'] . ' ' . $curuser['dname'] . ' указал что ваша заявка исполнена.\r\nЗаявка:\r\n' . StrCut($str['zayavka'], 50) . '\r\nКоментарий исполнителя:\r\n' . StrCut($_POST[$ii], 50);
                        $mquery = "INSERT INTO messages (date, brodcast, message, recipient, zid, mtype, priority,expire) VALUES (current_timestamp,'0','$mmessage','$oowner', '$zid', 'zdone', 'highest',DATE_ADD(CURRENT_TIMESTAMP,INTERVAL 30 DAY))";
                        $sql_query = $idb->query($mquery);
                        $zquery = 'UPDATE zayavki SET zayavki.answer="' . $_POST[$ii] . '", zayavki.Active=0 WHERE (zayavki.id=' . $zid . ')';
                        $sql_query = $idb->query($zquery);
                    }
            }

            $i++;
            $s = $i . '-action';
        }
    }
}

