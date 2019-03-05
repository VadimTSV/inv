<?php
global $pagetitle;
$pagetitle = "Регистрация пользователя"; 
include "htmlstart.php";
OutHeaders();

global $curuser;

if($curuser['isadmin']) {

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

if (empty($_POST))
{
	?>
  
	<h1 class='content'>Введите данные для регистрации пользователя</h1>
  <br />
  &nbsp<a href='userlist.php'>Перейти к списку пользователей</a> 
  <br />
  <div class='content-text'>
  <center>
 	<form action="register.php" method="post">
		<table class="form" bgcolor=white>
		  <tr >
				<th colspan=2>Свойства учетной записи</th>
			</tr class="form">
		  <tr class="form">
				<td class="form"><br>Имя</td>
				<td class="form"><br><input type="text" size="50" name="dname" /></td>
			</tr>
			<tr class="form">
      	<td class="form">Должность:</td>
				<td class="form"><input type="text" size="50" name="title" /></td>
			</tr>
			<tr class="form">
				<td class="form">Логин:</td>
        <td class="form"><input type="text" size="50" name="login" /></td>
      </tr>
      <tr class="form">
        <td class="form">Пароль:</td>
        <td class="form"><input type="password" size="50" name="password" /></td>
      </tr class="form">			
			<tr class="form">
			  <td class="form" colspan=2><input type="checkbox" name = "isadmin" value="1" />Администратор</td>
			</tr>
			<tr class="form">
			  <td class="form" colspan=2><input type="checkbox" name = "ismoder" value="1" />Модератор</td>
			</tr>
			<tr class="form">
				<td class="form" align=right colspan=2><input type="submit" value="Добавить пользователя" /></td>
			</tr>
		</table>	
	</form>
  </center>
  </div>


	<?php
}
else
{

	$login = (isset($_POST['login'])) ? $_POST['login'] : '';
	$password = (isset($_POST['password'])) ? $_POST['password'] : '';

	$error = false;
	$errort = '';

	if (strlen($login) < 2)
	{
		$error = true;
		$errort .= 'Длина логина должна быть не менее 2х символов.<br />';
	}
	if (strlen($password) < 4)
	{
		$error = true;
		$errort .= 'Длина пароля должна быть не менее 4 символов.<br />';
	}
	if (strlen($_POST['dname']) == 0)
	{
		$error = true;
		$errort .= 'Не заполнено поле Отображаемое имя.<br />';
	}

	global $idb;
        $query = "SELECT *
				FROM usr
				WHERE login=:login
				LIMIT 1";
        $sql = $idb->prepare($query);
	if (($sql->execute(array('login' => $login))) and ($sql->rowCount() > 0))
	{
		$error = true;
		$errort .= 'Пользователь с таким логином уже существует в базе данных, введите другой.<br />';
	}

	if (!$error)
	{
		// генерируем соль и пароль

		$salt = GenerateSalt();

		//print $salt;

		$hashed_password = md5(md5($password) . $salt);
		
		$s  = IsSet($_REQUEST['isadmin'])?'~ADMIN~':'';
    $s .= IsSet($_REQUEST['ismoder'])?'~MODERATOR~':'';

		global $idb;
                $query = "INSERT
					INTO usr
					SET
					  salt='{$salt}',
						login='{$login}',
						usr.password='{$hashed_password}',
						dname='". $_POST['dname'] ."',
						title='". $_POST['title'] ."',
						usr.right='{$s}'";
		
		//echo $query; 
		
		$sql = $idb->query($query);


		print '<h1 class="content">Пользователь успешно зарегистрирован.</h1><p class="content">';
		print "<br />";
    print "&nbsp<a href='userlist.php'>Перейти к списку пользователей</a>"; 
    print "<br />";
    print "<br />";
    print "&nbsp<a href='register.php'>Вернуться к добавлению пользователей</a>"; 
    print "<br />";
    print "<br /></p>";
	}
	else
	{
		print '<h1 class="content">Возникли следующие ошибки</h1><p class="content">'.$errort."</p>";
		print "<br />";
    print "&nbsp<a href='userlist.php'>Перейти к списку пользователей</a>"; 
    print "<br />";
    print "<br />";
    print "&nbsp<a href='register.php'>Вернуться к добавлению пользователей</a>"; 
    print "<br />";
    print "<br />";
	}
}
}
else {
  
  ?><h1 class='content'>Вы не имеете прав доступа к этой странице</h1><?php

}

