<?php
include_once 'admin/mysqlconnect.php';
include_once "inituser.php";
global $idb;
global $curuser;

// Отправка пользовательского сообщения
if(isset($_POST['message']) and ($_POST['message'] != "")){    
    if(isset($curuser)){
        $st = $idb->prepare("INSERT INTO usermsges (message, recipient, sender) VALUES (:message, :recipient, :sender)");
        $st->bindValue('message', $_POST['message']);
        $st->bindValue('recipient', $_POST['recipient']);
        $st->bindValue('sender', $curuser['login']);
        if($st->execute()){
            header("location:mymessages.php?msgdone=1");
        } else {
            print_r($st->errorInfo());
            exit;
        }
    }
}

global $pagetitle;
$pagetitle = "Мои сообщения"; 
include "htmlstart.php";
OutHeaders();
?>
<h1 class='content'>Сообщения.</h1>
<div class ='content-text'>
<?php
    global $idb; 
    $msgperpage = 20;
    $mysqlusr =  $idb->query("SELECT * FROM usr ORDER BY dName");
    $userslist = "";
    $firstuser = "";
    $users = array();
    
    while($row = $mysqlusr->fetch(PDO::FETCH_ASSOC)){
        if($curuser['login'] != $row['login']) {
            if($firstuser == "") $firstuser = $row['login'];  
            $userslist .= "<option value='".$row['login']."'>".$row['dname']."</option>";
            $users[$row['login']] = $row['dname'];
        }
    }
        
    if(IsSet($_REQUEST['page'])) {
        $page = (int)$_REQUEST['page'];
        $limit = ($page * $msgperpage). ", " . $msgperpage;
    } else {
        $page = 0;
        $limit = $msgperpage;   
    }
    $st = $idb->prepare('SELECT COUNT(id) AS cnt FROM 
                            (SELECT id FROM usermsges 
                             WHERE (recipient = :recipient) 
                             GROUP BY usermsges.sender) 
                            AS tbl1');
    $st->bindValue('recipient', $curuser['login']);
    if($st->execute() and ($st->rowCount() > 0)){
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $num_rows = $row['cnt'];
    } else {
        $num_rows = 0;
    }
    $url = $_SERVER['PHP_SELF']."?page=";    
    $query = "SELECT
                  usermsges.recipient, usermsges.sender, COUNT(usermsges.id) AS cnt, SUM(usermsges.isNew) AS newm, MAX(usermsges.`date`) AS lastmsg
                FROM
                  usermsges
                WHERE 
                  recipient='".$curuser['login']."'
            GROUP BY
              usermsges.sender
            ORDER BY isNew DESC, lastmsg DESC, sender LIMIT " . $limit;
  $res = $idb->query($query);
  echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,$msgperpage)."</p></center>";
  print "<table border=1 width=100%>";
  print "<tr>";
  print "<th>№</th>";
  print "<th>От</th>";
  print "<th>Новых сообщений</th>";
  print "<th>Сообщений всего</th>";
  print "<th>Последнее сообщение</th>";
  print "</tr>";
  $i = 1;
  while($row = $res->fetch(PDO::FETCH_ASSOC)){
    if($row['newm'] > 0) { $b = '<b>'; $bc = '</b>';}
    else { $b = ''; $bc = '';}
    $d = strtotime($row['lastmsg']);
    print "<tr>";
    print "<td>".$b.$i.$bc."</td>";
    $sender = isset($row['sender']) ? $row['sender'] : '';
    $sender_usr = isset($users[$sender]) ? $users[$sender] : '';
    print "<td><a href='showmessages.php?sender=".$sender."'>".$b.$sender_usr.$bc."</a></td>";
    print "<td>".$b.$row['newm'].$bc."</td>";
    print "<td>".$b.$row['cnt'].$bc."</td>";
    print "<td>".$b.date("H:m - d.m.Y", $d).$bc."</td>";
    print "</tr>";
    $i++;  
  }
  print "</table>";
  echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,$msgperpage)."</p></center>";
  
?>
  <?php if(isset($_GET['msgdone']) and $_GET['msgdone']) echo "<b>Ваше сообщение отправлено.</b><br /><br /><br />";?>  
  <h3>Написать сообщение</h3>  
  <form action="mymessages.php" method="post">
  <div class='leftmargin20'>      
      <i>Кому</i><br />      
      <select name = 'recipient' id='recipient' size =  multiple value='<?php echo $firstuser; ?>'><?php echo $userslist; ?>
      </select>       
      <br><br>
      <i>Сообщение</i><br />
      <textarea name='message' cols='90' rows='5'></textarea><br><br>
      <input type='Submit' value='Отправить сообщение' />
  </div> 
  </form>  

</div>
  
<?php

include "htmlend.php";