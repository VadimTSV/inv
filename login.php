<?php
session_start();

include_once ("admin/mysqlconnect.php");

if (isset($_GET['logout']))
{
	if (isset($_SESSION['user_id']))
		unset($_SESSION['user_id']);

	setcookie('login', '', 0, "/");
	setcookie('password', '', 0, "/");
	header('Location: index.php');
	exit;
}

if (isset($_SESSION['user_id']))
{
  if(IsSet($_GET['target']))header('Location: '.$_GET['target']);
	else header('Location: index.php');
	exit;

}

if (!empty($_POST))
{
	$login = (isset($_POST['login'])) ? $_POST['login'] : '';

	global $idb;
        $sql = $idb->prepare('SELECT `salt`
				FROM usr
				WHERE `login`=:login
				LIMIT 1');	
        $sql->bindValue('login', $login); 
	if ($sql->execute() and ($sql->rowCount() > 0))
	{
		$row = $sql->fetch(PDO::FETCH_ASSOC);
		$salt = $row['salt'];
		$password = md5(md5($_POST['password']) . $salt);
		global $idb;
                $query = "SELECT `id`
					FROM usr
					WHERE `login`='{$login}' AND `password`='{$password}'
					LIMIT 1";		
		if ($sql = $idb->query($query) and ($sql->rowCount() > 0))
		{
			$row = $sql->fetch(PDO::FETCH_ASSOC);
			$_SESSION['user_id'] = $row['id'];
			$time = 86400*10; // ставим куку на 24 часа
			if (isset($_POST['remember']))
			{
				setcookie('login', $login, time()+$time, "/");
				setcookie('password', $password, time()+$time, "/");
			}
			if(IsSet($_GET['target']))header('Location: '.$_GET['target']);
	    else header('Location: index.php');
			exit;
		}
		else
		{
			die('Такой логин с паролем не найдены в базе данных. — <a href="login.php">Авторизоваться</a>');
		}
	}
	else
	{
		die('пользователь с таким логином не найден. — <a href="login.php">Авторизоваться</a>');
	}
}

?>
<!DOCTYPE XHTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//RU">
<html>
  <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <title>Авторизация</title>
  <style type="text/css" media="screen"><!--
  
body {
  background-color: rgb(236,236,236);
  font-size: 12px;
  font-family: Verdana, Arial, Helvetica, SunSans-Regular, Sans-Serif;
  color:#564b47;  
  padding:0px;
  margin:0px;
}
#content { 	
  position:absolute;
  height:200px; 
  width:400px;
  margin:-100px 0px 0px -200px;
  top: 50%; 
  left: 50%;
  text-align: left;
  padding: 0px;
  background-color: rgb(255,255,255);
  border: 1px solid rgb(0,0,0);
  overflow: auto;
}
p{
margin: 0px; 
padding: 10px; 
}
h1 {
font-size: 10px;
text-transform:uppercase;
text-align: left;
color: white;
background-color: rgb(0,0,0);
padding:5px 15px;
margin:0px
}

a { 
color: #ff66cc;
font-size: 11px;
background-color:transparent;
text-decoration: none; 
}

#px300 {
 width: 200px; /* Ширина поля 300 пикселов */
}
 
 /* ]]> */	
--></style>
  </head>
   
  <body>
  <div id="content"> 
  <center>
<?php

print '
<h1>InvCollector: авторизация</h1>  
<br /><br />
<form action="login.php?target='.$_GET['target'].'" method="post">
	<table>
		<tr>
			<td>Логин:</td>
			<td><input type="text" name="login" id="px300" /></td>
		</tr>
		<tr>
			<td>Пароль:</td>
			<td><input type="password" name="password" id="px300"  /></td>
		</tr>
		<tr>
			<td colspan=2 align=right><input type="submit" value="Войти" /></td>
		</tr>
	</table>
	<br />
	<input type="checkbox" name="remember" /> Запомнить меня на этом компьютере
</form>
';

?>
   </center> 
   </div>
  </body>
</html>
