<?php

function DDate($s){
    $d = explode('.', $s);
    if(count($d) == 3) {
        return $d[2] . "-" . $d[1] . "-" . $d[0];
    } else {
        return date('Y-m-d');
    }
}

global $z_filters;

if(isset($_POST['data-start'])){ // Обновление значений
    $z_filters['data-start'] = $_POST['data-start'];
    $z_filters['data-end'] = $_POST['data-end'];        
    $z_filters['SQLWhere'] = '(date BETWEEN "' . DDate($z_filters['data-start']) . '" AND "' . DDate($z_filters['data-end']) . ' 23:59:59")';
    $_SESSION['z_filters'] = $z_filters;
} elseif(isset($_SESSION['z_filters'])){ // Возвращение значений из сессии
    $z_filters = $_SESSION['z_filters'];
} else { // Значения по умолчанию
    $z_filters['data-start'] = date('d.m.Y', time() - (3600*24*30));
    $z_filters['data-end'] = date('d.m.Y');        
    $z_filters['SQLWhere'] = '(date BETWEEN "' . DDate($z_filters['data-start']) . '" AND "' . DDate($z_filters['data-end']) . ' 23:59:59")';
    $_SESSION['z_filters'] = $z_filters;
}

?>
   <p>  
   <script src="datepicker.js" type="text/javascript" charset="UTF-8" language="javascript"></script>   
   <form id="searchform" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">             
         <table style="border: none;">             
         <tr style="border: none;">
            <td style="border: none;">Начальная дата</td>
            <td style="border: none;">Конечная дата</td>
         </tr>
         <tr style="border: none;">                                          
            <td style="border: none;">
                <input style="width: 170px;" name="data-start" value="<?php echo $z_filters['data-start']; ?>">
                <input type="button" style="background: url('calendar.png') no-repeat; width: 18px; border: 0px;" onclick="displayDatePicker('data-start', false, 'dmy', '.');">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            </td>             
            <td style="border: none;">
                <input style="width: 170px;" name="data-end" value="<?php echo $z_filters['data-end']; ?>">
                <input type="button" style="background: url('calendar.png') no-repeat; width: 18px; border: 0px;" onclick="displayDatePicker('data-end', false, 'dmy', '.');">                    
            </td>
            <td align='right' colspan="2" style="border: none;"><input type='Submit' value='Применить' /></td>
         </tr>                
         </table>
    </form>
   </p>
   <p>

