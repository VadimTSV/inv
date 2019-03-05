<?php
if(isset ($_GET['nHost'])){
    header("Content-type: text/vnc");
    header("Content-Disposition: attachment;Filename=".$_GET['nHost'].".vnc");
    echo "[connection]\r\n";
    echo "host=".$_GET['nHost']."\r\n";
    echo "port=5900";
}
