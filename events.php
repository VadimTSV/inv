<?php
include_once ("admin/mysqlconnect.php");;

header('Content-type: application/xml');
$data="<?xml version=\"1.0\" encoding=\"utf-8\" ?> ";
$data.= "<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">";
$data.= "<channel>";
$data.= "<title>InvCollector</title>";
$data.= "<link>".$_SERVER['PHP_SELF']."</link>";
$data.= "<description>Инвентаризация</description>";
$data.= "<copyright>zldo</copyright>";
$data.= "<language>ru</language>";
$data.= "<managingEditor></managingEditor>";
$data.= "<webMaster></webMaster>";

global $idb;
$query = "SELECT
  *
FROM
  eventlog
ORDER BY
  eventlog.id DESC LIMIT 100";

$result = $idb->query($query);

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $date = $row['EventDate'];
    $title = $row['EventType'];
    $text = nl2br(htmlspecialchars($row['EventText']));
    $author = $row['EvetOwner'];
    $dir = dirname($_SERVER['PHP_SELF']);
    if($dir != '/') $dir .= '/';
    $data.="<item>";
    $data.="<title>".$title."</title>";
    $data.="<link>http://".$_SERVER['SERVER_NAME'].$dir.'eventview.php?eventID='.$row['id']."</link>";
    $data.="<description><![CDATA[".$text."]]></description>";
    $data.="<dc:creator>$author</dc:creator>";
    $data.="<dc:date>".$date."</dc:date>\n</item>";
}

$data = $data."</channel></rss>";

echo $data;
exit;
