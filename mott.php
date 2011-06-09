<?php

$fileh = fopen('template.html', 'r');
$template = fread($fileh, filesize('template.html'));
fclose($fileh);



if( !$_POST['skickat'])
  {

    $fileh = fopen('form.inc', 'r');
    $form = fread($fileh, filesize('form.inc'));
    fclose($fileh);
    
    $form = str_replace('%CURRENTURL%','http://'. $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"],$form);
    $page = str_replace('%USABLEAREA%',$form,$template);
    print($page);
  }
else
  {
    $page = str_replace('%USABLEAREA%','Test',$template);
    print($page);
  }

?>
