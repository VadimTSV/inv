<?php 
global $pagetitle;
$pagetitle = "Журнал событий";
include 'htmlstart.php';
OutHeaders();
?>
<h1 class='content'>Информация о событии №: <strong><?php echo $_REQUEST['eventID'];  ?></strong></h1>
<div class='content-text'>

<?php    
  if(IsSet($_REQUEST['eventID'])){
    global $idb;
    $query = "SELECT * FROM eventlog WHERE ID=".$_REQUEST['eventID'];
    $res = $idb->query($query);
    while($row = $res->fetch(PDO::FETCH_ASSOC)){
      echo "Инициатор события: <a href='rowdetailshow.php?nHost=".$row['EvetOwner']."'>".$row['EvetOwner']."</a><br /><br />";
      echo "Дата поступления информации о событии: ".date("d.m.Y - H:i", strtotime($row['EventDate']))."<br /><br />";
      echo "<center><table width=100 border=1>";
      echo "<tr><th>Текст события.</th></tr>";
      echo "<tr><td><pre>".$row['EventText']."</pre></td></tr>";      
    }
  }
?>
  </table> 
  </center>
</div>
<?php
include "htmlend.php";


