<?php 
global $pagetitle;
$pagetitle = "Список компьютеров";
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
    if(isset($_REQUEST['softfilter'])) {
        echo '<h1 class="content">Список компьютеров с установленным ПО - '.htmlspecialchars($_REQUEST['softfilter'], ENT_QUOTES).'</h1>';
        $where .= ' AND ((LOCATE(:softfilter, sFullList) > 0) or (LOCATE(:softfilter, sOSSN) > 0) or (LOCATE(:softfilter, sMSOfficeSN) > 0))';
        $params['softfilter'] = $_REQUEST['softfilter'];
        $urlfilters['softfilter'] = $_REQUEST['softfilter'];
    } else {
        echo "<h1 class='content'>Список компьютеров</h1>";
    }
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
    
    if($st->execute($params) and ($st->rowCount() > 0)){
        //echo $query;
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $num_rows = $row['cnt'];
        $st = $idb->prepare('SELECT 
                    invmain.plSity, plCompany, plFilial, cTitle, nHost, hProcessor, hMainboardModel, hRAM, cOtd, plCab, inMainBox
                  FROM
                    invmain
                  WHERE ' . $where . '
                  ORDER BY
                    invmain.plSity, invmain.plCompany, invmain.plFilial, invmain.cOtd, invmain.plCab 
                  LIMIT ' . $limit);
        ?>
          <br>
          <form action="complist.php" method=GET>
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
                echo "<tr><td colspan = 8><h1>".$sity."<img align='right' src='./webdata/building.png'</h1></td></tr>";
                foreach ($plCompanys as $plCompany => $plFilials) {
                    $company = trim($plCompany) == '' ? '___?___' : $plCompany; 
                    echo "<tr><td colspan = 8><br /><b><img align='left' src='./webdata/bullet_black.png'>".$company."</b></td></tr>";
                    foreach ($plFilials as $plFilial => $cOtds) {
                        $filial = trim($plFilial) == '' ? '___?___' : $plFilial; 
                        echo "<tr><td colspan = 8><br /><b><img align='left' src='./webdata/bullet_green.png'>".$filial."</b></td></tr>";
                        echo "<tr>
                                <th>Отдел</th><th>№</th><th>Процессор / материнская плата / оперативная память</th><th>Пользователь</th><th width=50>Кабинет</th><th>Инвентарный номер</th><th>DNS имя</th><th>&nbsp</th>
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
                                
                                echo $l.".</td><td><a class='menu' href=rowdetailshow.php?nHost=".$row['nHost'].">".$row['hProcessor']." / ".$row['hMainboardModel']." / ".$row['hRAM']."Mb RAM</a></td><td>".$row['cTitle']."</td><td>".$row['plCab']."</td>
                                          <td>".$row['inMainBox']."</td><td>".$row['nHost']."</td><td><nobr>
                                              ".$editlnk."<a href='./rowdetailshow.php?nHost=".$row['nHost']."'><img border=0 src='webdata/info.png' title='Подробная информация'></a>
                                              <a href='./vncout.php?nHost=".$row['nHost']."'><img border=0 src='webdata/uvnc.png' title='Управление'></a>
                                          </nobr></td></tr>";
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