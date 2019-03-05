<!--[if IE]>
<script type="text/javascript" src="/static/lib/FlashCanvas/bin/flashcanvas.js"></script>
<![endif]-->
<script type="text/javascript" src="flotr/flotr2.min.js"></script>
<?php

function MakePieChart($field, $title, $subtitle, $data){
?>
<div id="<?php echo $field; ?>" style=" width: 800px; height: 600px; margin: 8px auto;"></div>
    
 <script type="text/javascript">
 (function basic_pie(container) {

  var
    graph;
    graph = Flotr.draw(container, [
    <?php
    foreach ($data as $key => $value) {
        echo '{ data : [[0, '.$value.']], label : '.$key.' },';
    }
    ?>    
    ], {
    title: <?php echo json_encode($title);?>,
    subtitle: <?php echo json_encode($subtitle);?>,  
    HtmlText : false,
    resolution: 1,
    fontSize: 8,
    fontColor: '#000000', 
    grid : {
      verticalLines : false,
      horizontalLines : false
    },
    xaxis : { showLabels : false },
    yaxis : { showLabels : false },
    pie : {
      show : true, 
      explode : 6
    },
    mouse : { track : true },
    legend : {
      position : 'se',
      backgroundColor : '#D2E8FF'
    }
  });
})(document.getElementById("<?php echo $field; ?>"));
</script>
<?php    
}
