<?php

$recipient = 'pontus.freyhult@uadm.uu.se';
$certfile = 'pontskol.pem';

$encryptheaders = array(
			"Content-Type: text/plain; charset=utf-8",
			"Content-Transfer-Encoding: quoted-printable"
			);

$headers = array( 
		  "Subject: =?ISO-8859-1?Q?Intresseanm=E4lan_till_psykologmottagningen?=",
		  "From: =?ISO-8859-1?Q?Mottagningens webbtj=E4nst?= <mottagning@psyk.uu.se>",
		  "To: =?ISO-8859-1?Q?Handl=E4ggare f=E5r mottagningen?= <" . $recipient . ">"
		  );


// Create the message even if we don't have it.

$msg =  "Content-type: text/plain; charset=utf-8\n" .
  "Content-Transfer-Encoding: quoted-printable\n\n" .
  "Detta är en anmälan inkommen via webbsidan.\n\n\n" . 
  "Namn:   " . $_POST['name']. "\n\n" . 
  "Adress: " . $_POST['address'] . "\n" . 
  "        " . $_POST['zip'] . "  " . $_POST['location'] . "\n\n" . 
  "Mobil:  " . $_POST['cellphone'] . "\n\n" . 
  "E-post: " . $_POST['mail'] . "\n\n" . 
  "Datum:  " . $_POST['date'] . "\n\n" .  
  "Orsak:  " . $_POST['reason'] . "\n\n" ;


function sendit($mailmsg)
{
  global $recipient;
  global $headers;
  
  $descriptorspec = array(
			  0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			  1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			  2 => array("pipe", "w") // stderr is a file to write to
			  );
  
  $cwd = '/tmp';
  $env = array();

  
  // Something seems to mess up in PKCS headers, add them instead at the start

  foreach($headers as $current_line)
    $mailmsg = $current_line . "\n" . $mailmsg;

  $process = proc_open(ini_get('sendmail_path') . ' ' . $recipient,
		       $descriptorspec, 
		       $pipes,
		       $cwd,
		       $env);
  
  
  if (is_resource($process)) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // Any error output will be appended to /tmp/error-output.txt


    fwrite($pipes[0],$mailmsg);

    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $return_value = proc_close($process);

    return !$return_value;
 }

 return false;
}


function signit()
{

  // Sign our message

  global $certfile;
  global $msg;

  $recipcert =  file_get_contents($certfile);
     
  if (!$recipcert)
    return false;

  $tmpin = tempnam('pskmtt','/tmp');
  $tmpout = tempnam('pskmtt','/tmp');


  if (!$tmpin || !$tmpout)
    return false;   
 
  if (false === file_put_contents($tmpin,$msg))
    return;

  
  $ok = openssl_pkcs7_encrypt($tmpin,$tmpout,$recipcert,array(),0,OPENSSL_CIPHER_RC2_128);
  if (!$ok)
    return false;
 
  $crypted = file_get_contents($tmpout);

  if (!$crypted)
    return false;

  $crypted = strstr($crypted,'MIME-Version');
  $crypted = str_replace('application/x-pkcs7-mime; smime-type=enveloped-data','application/pkcs7-mime', $crypted);
  
  return $crypted;
}




$template = file_get_contents('template.html');


// Submitted form?

if( !$_POST['skickat'])
  {

    
    $form = file_get_contents('form.inc');
   
    $page = str_replace('%USABLEAREA%',$form,$template);
    print($page);
  }
else
  {
    
    $all = signit();

    if ($all)
      {
	$ret = sendit($all);
	$all = $ret;
      }

    if (!$all)
      {
	
	$page = str_replace('%USABLEAREA%','Det uppstod tyvärr ett fel. Var god ' . 
			    ' försök senare eller ring 018-471 71 28.',$template);
      }
    else
      {
	$page = str_replace('%USABLEAREA%','Tack för din intresseanmälan, den är ' . 
			    'registrerad och kommer att hanteras.', $template);
      }

    print($page);


  }

?>
