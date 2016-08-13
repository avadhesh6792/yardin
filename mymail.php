
<?php
//ini_set('display_errors',1);

$to      = 'avatesting@yopmail.com';
$subject = 'the subject';
$message = 'hello';
/*$headers = 'From: webmaster@example.com' . "\r\n" .
    'Reply-To: webmaster@example.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
*/

$status = mail($to, $subject, $message);

echo $status ? 'sent' : 'not sent';

//echo phpinfo();

?>
