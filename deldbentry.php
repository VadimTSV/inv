<?php
global $pagetitle;
$pagetitle = "Удаление записи из базы данных";
include "htmlstart.php";

if(isset($_POST['nHost'])){
  global $curuser;
  if($curuser['isadmin']) {
     global $idb;
     $st = $idb->prepare('DELETE FROM invmain WHERE nHost = :nHost');//.quote_smart($_POST['nHost']);
     $st->bindValue('nHost', $_POST['nHost']);
     $st->execute();
  }
}

if(isset($_POST['backurl']))  header("location:".$_POST['backurl']);

OutHeaders();

echo "<h1 class='content'>Удаление записи из базы данных</h1>";
?>
<div class='content-text'>
Вы действительно хотите удалить из базы данных запись <a href="rowdetailshow.php?nHost=<?echo $_GET['nHost'];?>"><?php echo $_GET['nHost']; ?></a>?
<table class='form'>
<tr>
<td>
<form action="deldbentry.php" method="post">
    <input type="hidden" name="nHost" value="<?php echo $_GET['nHost']; ?>" />
		<input type="hidden" name="backurl" value="<?php echo $_GET['backurl']; ?>" />
		<input type="submit" value="Да, удалить" />
</form>
</td>
<td>
<form action="deldbentry.php" method="post">
		<input type="submit" value="Отменить" />
		<input type="hidden" name="backurl" value="<?php echo $_GET['backurl']; ?>" />
</form>
</td>
</tr>
</table>
</div>

<?php  
include "htmlend.php";