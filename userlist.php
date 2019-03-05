<?php
global $pagetitle;
$pagetitle = "Список пользователей"; 
include "htmlstart.php";
OutHeaders();


global $curuser;

if($curuser['isadmin']) {
    
if(isset($_GET['deleted_login'])) {
    global $idb;
    $st = $idb->prepare('DELETE FROM usr WHERE login = :login');
    $st->execute(array(':login' => $_GET['deleted_login']));
}

function GenerateSalt($n=3)
{
	$key = '';
	$pattern = '1234567890abcdefghijklmnopqrstuvwxyz.,*_-=+';
	$counter = strlen($pattern)-1;
	for($i=0; $i<$n; $i++)
	{
		$key .= $pattern{rand(0,$counter)};
	}
	return $key;
}

function GetFieldValueList($fieldname){
  global $idb;
  $query = "SELECT DISTINCT ".$fieldname." FROM invmain ORDER BY ".$fieldname;  
  $res =  $idb->query($query);
  $temp_res = "";
    
  $temp_res .= "<option value=''></option>";
  while($row = $res->fetch(PDO::FETCH_ASSOC)){
    if($row[$fieldname] != "") $temp_res .= "<option value='".$row[$fieldname]."'>".$row[$fieldname]."</option>";
  } 
  
  return $temp_res; 
}

?>
<h1 class='content'><a href="config-main.php" style="color: white"><u>Настройки</u></a> \ Список пользователей</h1>
<br />
&nbsp<a href="register.php">Добавить нового пользователя</a> 
<br />
<?php

if(IsSet($_POST['login']) or  IsSet($_GET['login'])){

  if(IsSet($_POST['isedit'])){ // Редактирование данных
    $s  = IsSet($_POST['isadmin'])?'~ADMIN~':'';
    $s .= IsSet($_POST['ismoder'])?'~MODERATOR~':'';
    $password = (isset($_POST['dochangepwd'])) ? $_POST['password'] : '';
    $salt = GenerateSalt();
		$hashed_password = ($password != "")?md5(md5($password) . $salt):'';
		$password = (isset($_POST['dochangepwd']))? ", usr.password='". $hashed_password."', salt='".$salt."'" : ''; 
	  global $idb;
          $query = "UPDATE usr
	      SET  dname='".$_POST['dname']."', title='".$_REQUEST['title']."', usr.right='".$s."'".$password.",
	      filter_plSity = '".$_REQUEST['fSity']."', filter_plCompany = '".$_REQUEST['fCompany']."', filter_plFilial = '".$_REQUEST['fFilial']."'
        WHERE login='".$_POST['login']."'";
	  $sql = $idb->query($query);
    }
    
        if(isset($_GET['login'])){
            $login = $_GET['login'];
        } else {
            $login = $_POST['login'];
        }

  

	global $idb;
        $query = "Select *, 
              LOCATE('~ADMIN~', usr.right) as isadmin, 
              LOCATE('~MODERATOR~', usr.right) as ismoderator 
              from usr where login='".$login."'";
	$sql = $idb->query($query);
  
  if($sql->rowCount() > 0){
    $row = $sql->fetch(PDO::FETCH_ASSOC); 
    //print_r($row);

?>

 <div class='content-text'>
  <center>
 	<form action="userlist.php" method="post">
		<table class="form">
		  <tr  class="form">
				<th colspan=2>Свойства учетной записи - <?php echo $row['login']; ?> </th>
			</tr>
		  <tr  class="form">
				<td class="form"><br>Имя</td>
				<td class="form"><br><input type="text" size="50" name="dname" value="<?php echo $row['dname']; ?>" /></td>
			</tr>
			<tr  class="form">
      	<td class="form">Должность:</td>
				<td class="form"><input type="text" size="50" name="title" value="<?php echo $row['title']; ?>" /></td>
			</tr>
      <tr  class="form">
			  <td class="form"><input type="checkbox" name = "dochangepwd" value="1" />Сменить пароль на</td>
        <td class="form"><input type="password" size="50" name="password" /></td>			
				<input type="hidden" name="login" value="<?php echo $login; ?>" />
				<input type="hidden" name="isedit" value="1" />
			</tr>
			<tr  class="form">
			  <td class="form" colspan=2><input type="checkbox" name = "isadmin" value="1" <?php echo ($row['isadmin'] > 0)?'checked':''; ?> />Администратор</td>
			</tr>
			<tr  class="form">
			  <td class="form" colspan=2><input type="checkbox" name = "ismoder" value="1" <?php echo ($row['ismoderator'] > 0)?'checked':''; ?> />Модератор</td>
			</tr>
			
			<tr  class="form">
			  <td class="form">Фильтр города</td>
			  <td class="form">
			    <select name = 'fSity' size =  multiple value='<?php echo $row['filter_plSity']; ?>' STYLE="width: 350px">
			      <option value='<?php echo $row['filter_plSity']; ?>'><?php echo $row['filter_plSity']; ?></option>
            <?php echo GetFieldValueList('plSity'); ?> 
          </select>
        </td>
			</tr>
			
			<tr  class="form">
			  <td class="form">Фильтр организации</td>
			  <td class="form">
			    <select name = 'fCompany' size =  multiple value='<?php echo $row['filter_plCompany']; ?>' STYLE="width: 350px">
			      <option value='<?php echo $row['filter_plCompany']; ?>'><?php echo $row['filter_plCompany']; ?></option>
            <?php echo GetFieldValueList('plCompany'); ?> 
          </select>
        </td>
			</tr>
			
			<tr  class="form">
			  <td class="form">Фильтр филиала</td>
			  <td class="form">
			    <select name = 'fFilial' size =  multiple value='<?php echo $row['filter_plFilial']; ?>' STYLE="width: 350px">
			      <option value='<?php echo $row['filter_plFilial']; ?>'><?php echo $row['filter_plFilial']; ?></option>
            <?php echo GetFieldValueList('plFilial'); ?> 
          </select>
        </td>
			</tr>
			
			<tr  class="form">
				<th colspan="2"  class="form" align=right><input type="submit" value="Сохранить" /></th>
			</tr>
		</table>	
	</form>
  </center>
  </div>

  <?php
}

}
  if(!IsSet($_REQUEST['page'])) $page = 0;
  else $page = $_REQUEST['page'];     
  if(IsSet($_REQUEST['page'])) $limit = (($_REQUEST['page']) * 25). ", 25";
  else $limit = 25;
  
  global $idb;
  $query = "SELECT Count(login) as cnt
            FROM
              usr ";
  $intres = $idb->query($query);
  $row = $intres->fetch(PDO::FETCH_ASSOC);
  $num_rows = $row['cnt'];
  global $idb;
  $query = 'SELECT
              login, 
              role, 
              places, 
              dname, 
              title, 
              LOCATE("~ADMIN~", usr.right) as isadmin, 
              LOCATE("~MODERATOR~", usr.right) as ismoderator
            FROM
              usr
            ORDER BY dname LIMIT '.$limit;  
  $intres = $idb->query($query);
  if($num_rows > 0){
    $url = $_SERVER['PHP_SELF']."?page=";
    echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,25)."</p></center>";
    ?>
      <div class='content-text'>
      <table border=1 width=100%>
        <tr>
          <th>Имя</th>
          <th>Должность</th>
          <th>Логин</th>
          <th>Администратор</th>
          <th>Модератор</th>
          <th>Управление</th>
        </tr>
    <?php
    while($row = $intres->fetch(PDO::FETCH_ASSOC)){
      echo "<tr>";
        echo "<td><a href='userlist.php?login=".$row['login']."'>".$row['dname']."</a></td>";
        echo "<td><a href='userlist.php?login=".$row['login']."'>".$row['title']."</a></td>";
        echo "<td><a href='userlist.php?login=".$row['login']."'>".$row['login']."</a></td>";
        $s = $row['isadmin'] > 0?'Да':'Нет';
        echo "<td>".$s."</td>";
        $s = $row['ismoderator'] > 0?'Да':'Нет';
        echo "<td>".$s."</td>";
        echo '<td align="center"><a href="userlist.php?deleted_login=' . $row['login'] . '" onclick="return confirm(\'Вы действительно хотите удалить пользователя '.$row['dname'].'?\') ? true : false;"><img src="./webdata/delete.png"></a></td>';
      echo "</tr>";        
    }
    ?>
      </table>
      </div>
    <?php
    echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,25)."</p></center>";
  }
  
}
else {
   ?><h1 class='content'>Вы не имеете прав доступа к этой странице</h1><?php
}
  
include "htmlend.php";