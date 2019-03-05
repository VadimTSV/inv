<?php
global $pagetitle;
$pagetitle = "Активные сообщения"; 
include "htmlstart.php";
OutHeaders();


global $curuser;

if($curuser['isadmin']) {
    
if(isset($_GET['disabled_msg'])) {
    global $idb;
    $st = $idb->prepare('UPDATE messages SET expire = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY) WHERE id=:id');
    $st->bindValue('id', $_GET['disabled_msg']);
    $st->execute();
}
}

?>
<h1 class='content'>Список активных сообщений для клиентских модулей</h1>

<?php
  if(!IsSet($_REQUEST['page'])) $page = 0;
  else $page = $_REQUEST['page'];     
  if(IsSet($_REQUEST['page'])) $limit = (($_REQUEST['page']) * 20). ", 20";
  else $limit = 20;
  
  global $idb;
  $st = $idb->prepare("SELECT Count(id) as cnt
            FROM
              messages 
            WHERE 
              (DATE_ADD(expire,INTERVAL 1 DAY) > CURRENT_TIMESTAMP) AND (confirmdate IS NULL AND (brodcast=1))
            ");
  if($st->execute() and ($st->rowCount() > 0)){
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $num_rows = $row['cnt'];
    $st = $idb->prepare('SELECT
              *
            FROM
              messages
            WHERE
               (DATE_ADD(expire,INTERVAL 1 DAY) > CURRENT_TIMESTAMP) AND (confirmdate IS NULL AND (brodcast=1))
            ORDER BY date DESC LIMIT '.$limit);  
    if($st->execute() and ($st->rowCount() > 0)){
        $url = $_SERVER['PHP_SELF']."?page=";
        echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,20)."</p></center>";
        ?>
          <div class='content-text'>
          <table border=1 width=100%>
            <tr>
              <th>ID</th>  
              <th>Отправлено</th>        
              <th>Сообщение</th>
              <th>Истекает</th>
              <th>Управление</th>
            </tr>
        <?php
        while($row = $st->fetch(PDO::FETCH_ASSOC)){
          echo "<tr>";
            echo "<td>".$row['id']."</td>";
            echo "<td>".date("d.m.Y - H:m", strtotime($row['date']))."</td>";        
            echo "<td>".nl2br(htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'))."</td>";        
            echo "<td>".date("d.m.Y", strtotime($row['expire']))."</td>";
            if($curuser['isadmin']) {
                echo '<td align="center"><a href="amessages.php?disabled_msg=' . $row['id'] . '" onclick="return confirm(\'Вы действительно хотите завершить рассылку сообщения '.$row['id'].'?\') ? true : false;"><img src="./webdata/delete.png"></a></td>';
            } else {
                echo '<td></td>';
            }    
          echo "</tr>";        
        }
        ?>
          </table>
          </div>
        <?php
        echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,25)."</p></center>";
    } else {
      echo '<h3>Нет активных сообщений<h3>';  
  }} else {  
        echo '<h3>Нет активных сообщений<h3>';  
    }
    
  
include "htmlend.php";