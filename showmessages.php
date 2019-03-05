<?php 
global $pagetitle;
$pagetitle = "Мои сообщения"; 
include "htmlstart.php";
global $curuser;
if(isset($_POST['message']) and ($_POST['message'] != "")){
  global $idb;
$query = "INSERT INTO usermsges (message, recipient, sender) 
            VALUES ('".$_POST['message']."', '".$_GET['sender']."', '".$curuser['login']."')";
  $res = $idb->query($query);
  header("location:showmessages.php?sender=".$_GET['sender']);
}

global $idb;
$query = "UPDATE usermsges SET isNew=0
          WHERE recipient='".$curuser['login']."' AND usermsges.sender='".$_GET['sender']."'";
$res = $idb->query($query);

OutHeaders();

if(IsSet($_GET['sender'])){

global $idb;
$query = "SELECT * FROM usr WHERE login='".$_GET['sender']."'";
$res = $idb->query($query);
$row = $res->fetch(PDO::FETCH_ASSOC);
$sender = $row['dname'];

?>
<h1 class='content'>Переписка с <?php echo $sender;?></h1>
<div class ='content-text'>
<?php
  global $curuser;
  
  $users = array();
  global $idb;
  $query = "SELECT * FROM usr";
  $res = $idb->query($query);
  while($row = $res->fetch(PDO::FETCH_ASSOC)){
    $users[$row['login']] = $row['dname'];  
  } 
  
  global $idb;
  $query = "SELECT
              *
            FROM
              usermsges
            WHERE 
              (recipient='".$curuser['login']."' AND usermsges.sender='".$_GET['sender']."') OR
              (recipient='".$_GET['sender']."' AND usermsges.sender='".$curuser['login']."') 
            ORDER BY date DESC LIMIT 15";
  $res = $idb->query($query);
  $lines = array();
  while($row = $res->fetch(PDO::FETCH_ASSOC)){
    $line = "";
    $d = strtotime($row['date']);
    $line .= date("d.m.Y - H:m", $d)." > ".$users[$row['sender']];
    $line .=  "<p class='quote'>".nl2br(htmlspecialchars($row['message']))."</p>";
    $lines[] = $line;
  }
  $lines = array_reverse($lines);
  
  foreach ($lines as $line) {
  	echo $line;
  }
?>

<a href="showmessages.php?sender=<?php echo $_GET['sender']; ?>">Обновить</a>

<form action="showmessages.php?sender=<?php echo $_GET['sender']; ?>" method="post">
  <div class='leftmargin20'>
      <i>Ответ</i><br />
      <textarea name='message' cols='90' rows='5'></textarea><br><br>
      <input type='Submit' value='Отправить сообщение' />
  </div> 
</div>
<?php
}
  
include "htmlend.php";
