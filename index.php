<?php
global $pagetitle;
$pagetitle = "Главная страница";
include "htmlstart.php";
global $idb; // База данных
global $curuser; // Текущий пользователь
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 

OutHeaders();
echo "<h1 class='content'>Добро пожаловать на главную страницу системы автоматизированной инвентаризации компьютеров InvCollector</h1>";
?>
<p class="content">
    <strong>Статистика информационной базы.</strong><br>
<ul>
    <?php
    global $idb;
    $query = "SELECT
    COUNT(DISTINCT nHost)
      FROM
    invmain";
    $res = $idb->query($query);
    $row = $res->fetch(PDO::FETCH_NUM);
    echo "<li>Компьютеров всего - " . $row[0] . "</li>";    
    
    global $idb;
    $query = "SELECT
    COUNT(DISTINCT plSity)
      FROM
    invmain";
    $res = $idb->query($query);
    $row = $res->fetch(PDO::FETCH_NUM);
    echo "<li>Городов - " . $row[0] . "</li>";

    global $idb;
    $query = "SELECT
    COUNT(DISTINCT plCompany)
      FROM
    invmain";
    $res = $idb->query($query);
    $row = $res->fetch(PDO::FETCH_NUM);
    echo "<li>Организаций - " . $row[0] . "</li>";

    global $idb;
    $query = "SELECT
    COUNT(DISTINCT plFilial)
      FROM
    invmain";
    $res = $idb->query($query);
    $row = $res->fetch(PDO::FETCH_NUM);
    echo "<li>Подразделений - " . $row[0] . "</li>";
    
    $query = "SELECT
    COUNT(*)
      FROM
    clientstatus";
    $res = $idb->query($query);
    $row = $res->fetch(PDO::FETCH_NUM);
    echo "<li>Клиентских модулей сейчас в сети - " . $row[0] . "</li>";
    
    ?>
</ul>
</p>
<p class="content">
    <strong>Заявки на техническое обслуживание.</strong><br>
<ul>
    <?php
    global $idb;
    $query = "SELECT
    COUNT(DISTINCT id)
      FROM
    zayavki";
    $res = $idb->query($query);
    $row = $res->fetch(PDO::FETCH_NUM);
    echo "<li>Заявок всего - " . $row[0] . "</li>";

    global $idb;
    $query = "SELECT
    COUNT(DISTINCT id)
      FROM
    zayavki WHERE active=1";
    $res = $idb->query($query);
    $row = $res->fetch(PDO::FETCH_NUM);
    echo "<li>Активных заявок  - " . $row[0] . "</li>";
    ?>
</ul>
</p>
<p class="content">
    <strong>Просмотр информационной базы.</strong><br>
<ul>
    <li><a href="actions.php" class="menu">Журнал событий</a></li>
    <li><a href="zlist.php" class="menu">Заявки на техническое обслуживание</a></li>
    <li><a href="complist.php" class="menu">Список компьютеров</a></li>
    <li><a href="smain.php" class="menu">Установленное программное обеспечение</a></li>
</ul>
</p>
<h1 class="content">Последние события</h1>
<?php
    $haselement = 0;
    global $idb;
    $query = "SELECT DISTINCT
              EventType
            FROM
              eventlog";
    $res = $idb->query($query);
    if (!($res === false)) {
        while ($EventTypes = $res->fetch(PDO::FETCH_ASSOC)) {
            global $curuser;
            $where = $gfilters_sql . " and EvetOwner = nHost and EventType = '" . $EventTypes['EventType'] . "'";            
            $st = $idb->prepare("SELECT
                    EventDate, EventText, EvetOwner, id
                  FROM
                    eventlog, invmain WHERE " . $where . " ORDER BY EventDate DESC LIMIT 3");
            if($st->execute($gfilters) and ($st->rowCount() > 0)){
                echo "<p class='content'><strong>" . $EventTypes['EventType'] . "</strong><ul>";
                while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                    if (strlen($row['EventText']) > 255)
                        $s = mb_substr($row['EventText'], 0, 255, 'UTF-8') . "...";
                    else
                        $s = $row['EventText'];
                    echo "<li>" . $row['EventDate'] . "  <a href='rowdetailshow.php?nHost=" . $row['EvetOwner'] . "'>" . $row['EvetOwner'] . "</a><br><a href='eventview.php?eventID=" . $row['id'] . "'>" . nl2br(htmlentities($s)) . "</a></li>";
                    $haselement = 1;
                }
                echo "</ul></p>";
            }
        }
    }

    if ($haselement == 0)
        echo "<p class='content'><strong>Нет элементов для отображения</strong></p>";

include "htmlend.php";
