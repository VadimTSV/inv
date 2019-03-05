<?php 
global $pagetitle;
$pagetitle = "Телефонный справочник";
include "htmlstart.php";
global $gfilters; // Глобальные фильтры
global $gfilters_sql; // Глобальные фильтры в SQL представлении 
OutHeaders();

function SerchExpr($fieldname, $sex){
  return '('.$fieldname.' IS NOT NULL AND LOCATE('.$sex.', '.$fieldname.') > 0)';
}

function SerchWhereinvmain($sex){
  //$sex = mb_strtoupper($asex);
  return "(".SerchExpr('plSity',$sex)." OR "
            .SerchExpr('plCompany',$sex)." OR "
            .SerchExpr('plFilial',$sex)." OR "
            .SerchExpr('plAdress',$sex)." OR "
            .SerchExpr('plBuilding',$sex)." OR "
            .SerchExpr('plCab',$sex)." OR "
            .SerchExpr('cOtd',$sex)." OR "
            .SerchExpr('cTitle',$sex)." OR "
            .SerchExpr('nUserLogin',$sex)." OR "
            .SerchExpr('nHost',$sex)." OR "
            .SerchExpr('inMonitor',$sex)." OR "
            .SerchExpr('inMainBox',$sex)." OR "
            .SerchExpr('sFullList',$sex)." OR "
            .SerchExpr('inPrinter',$sex).
            ")";  
}

    $params = $gfilters;
    $where = $gfilters_sql;
    $urlfilters = array();   
    if(isset($_REQUEST['searchexp'])) {        
        $where .= ' AND ' . SerchWhereinvmain(':searchexp');
        $params['searchexp'] = $_REQUEST['searchexp'];
        $urlfilters['searchexp'] = $_REQUEST['searchexp'];
    }
    if(!IsSet($_REQUEST['page'])) $page = 0;
    else $page = $_REQUEST['page'];     
    if(IsSet($_REQUEST['page'])) $limit = (((int)$_REQUEST['page']) * 40). ", 40";
    else $limit = 40;    
    global $idb;
    $st = $idb->prepare('SELECT 
              Count(nHost) as cnt 
            FROM
              invmain
            WHERE ' . $where);
    echo "<h1 class='content'>Телефонный справочник</h1>";
    if($st->execute($params) and ($st->rowCount() > 0)){
        //echo $query;
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $num_rows = $row['cnt'];
        $st = $idb->prepare('SELECT 
                    invmain.plSity, plCompany, plFilial, cTitle, nHost, cTelephoneCell, cTelephone, cTelephoneLocal, cOtd, plCab, cSkype, cEmail
                  FROM
                    invmain
                  WHERE ' . $where . '
                  ORDER BY
                    invmain.plSity, invmain.plCompany, invmain.plFilial, invmain.cOtd, invmain.plCab 
                  LIMIT ' . $limit);
        ?>
          <br>
          <form action="phones.php" method=GET>
            <div align="right">
             <nobr> Поиск 
               <input name="searchexp" value=<?php echo isset($_REQUEST['searchexp']) ? $_REQUEST['searchexp'] : '';?>>
               <input type="submit" value="Найти">&nbsp&nbsp
             </nobr>
            </div>
          </form>

        <?php
          if($st->execute($params) and ($st->rowCount() > 0)){   
            if(count($urlfilters) > 0) {
               $url = '?'; 
               foreach($urlfilters as $index=>$value) $url .=$index.'='.urlencode($value).'&';
               $url .= 'page=';  
            } else $url = '?page=';

            echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,40)."</p></center>";
            echo "<div class='content-text'><table border=1 width=100%>";
            
            $l = $page * 40;            
            $itemstree = array(); // Дерево элементов
            while($row = $st->fetch(PDO::FETCH_ASSOC)){
                $itemstree[$row['plSity']][$row['plCompany']][$row['plFilial']][$row['cOtd']][] = $row;
            }     
            foreach ($itemstree as $plSity => $plCompanys) {
                $sity = $plSity == '' ? '___?___' : $plSity; 
                echo "<tr><td colspan = 9><h1>".$sity."<img align='right' src='./webdata/building.png'</h1></td></tr>";
                foreach ($plCompanys as $plCompany => $plFilials) {
                    $company = trim($plCompany) == '' ? '___?___' : $plCompany; 
                    echo "<tr><td colspan = 9><br /><b><img align='left' src='./webdata/bullet_black.png'>".$company."</b></td></tr>";
                    foreach ($plFilials as $plFilial => $cOtds) {
                        $filial = trim($plFilial) == '' ? '___?___' : $plFilial; 
                        echo "<tr><td colspan = 9><br /><b><img align='left' src='./webdata/bullet_green.png'>".$filial."</b></td></tr>";
                        echo "<tr>
                                 <th>Отдел</th><th>Должность</th><th>Городской</th><th>Сотовый</th><th>Внутренний</th><th width=50>Кабинет</th><th>Skype</th><th>e-mail</th><th>&nbsp</th>
                              </tr>";
                        foreach ($cOtds as $hosts) {
                            $split = count($hosts);                            
                            foreach ($hosts as $row) {
                                $l++;
                                $editlnk = $curuser['isadmin'] ? "<a href='./deldbentry.php?nHost=".$row['nHost']."&backurl=".$_SERVER['REQUEST_URI']."'><img border=0 src='webdata/delete.png' title='Удалить запись'></a><a href='./edituserdata.php?nHost=".$row['nHost']."&backurl=".$_SERVER['REQUEST_URI']."'><img border=0 src='webdata/edit.png' title='Редактировать данные'></a>":"";
                                if(isset($split)){ 
                                    echo '<tr><td bgcolor=#E8E8E8 width=150 rowspan='.$split.'>';
                                    echo "<center><b><i>".$row['cOtd']."</i></b></center></td><td>";
                                    unset($split);
                                } else {
                                    echo "<tr><td>";
                                }
                                
                                echo $row['cTitle']."</td><td><a href='sip:".$row['cTelephone']."'></td><td>".$row['cTelephoneCell']."</td>
                                        <td><a href='sip:".$row['cTelephoneLocal']."'> ".$row['cTelephoneLocal']."</a></td><td>".$row['plCab']."</td><td>".$row['cSkype']."</td><td><a href='mailto:".$row['cEmail']."'>".$row['cEmail']."</a></td><td><nobr>
                                            ".$editlnk."<a href='./rowdetailshow.php?nHost=".$row['nHost']."'><img border=0 src='webdata/info.png' title='Подробная информация'></a>
                                        </nobr></td></tr>";;
                            }
                        }
                    }
                }
            }            
            echo "</table></div></p>";
            echo "<center><p class='content'>".LeftRight($num_rows, $page, $url ,40)."</p></center>";

          }
 
    } else {
        echo '<h3>Ничего не найдено</h3>';
    }

      
include "htmlend.php";