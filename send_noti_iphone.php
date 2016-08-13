<?php
 
send_noti('dfdf', 'my msg', 'fdf', 47);

function send_noti($recivertok_id,$message,$notmessage='',$msgsender_id='')
{  
    // Put your device token here (without spaces):
    //$deviceToken = $recivertok_id;
    //$deviceToken = 'b1613c57ae38cd31df18899075052b3438fc511843dd39ad6036d12c66855042';
    $deviceToken = 'f94a876fb50ebf7fce079c9f88d2efe72127839029ce93bedbc360c10ea77d1c';
    //$deviceToken = '94982e93d6dc3019fecb05f9fc2e6863b148a568d4fbedd111bd0911c8c8705e';
                    
    // Put your private key's passphrase here:
    $passphrase = '';
    // Put your alert message here:
    //$message = $message;
    $message = 'hello testing';
    ////////////////////////////////////////////////////////////////////////////////

    $ctx = stream_context_create();
    //stream_context_set_option($ctx, 'ssl', 'local_cert', 'cabmaps.pem');
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'YardingT.pem');
    //stream_context_set_option($ctx, 'ssl', 'local_cert', 'yardingt.pem');
     // stream_context_set_option($ctx, 'ssl', 'local_cert', 'CertificatesFunYou.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
	
    // Open a connection to the APNS server
/*   
   $fp = stream_socket_client(
								'ssl://gateway.sandbox.push.apple.com:2195', $err,
    	$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
*/
    
    


    $fp = stream_socket_client(
                               //'ssl://gateway.push.apple.com:2195', $err,
                                'ssl://gateway.sandbox.push.apple.com:2195', $err,
                               $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);


    if (!$fp)
	     exit("Failed to connect: $err $errstr" . PHP_EOL);

    //echo 'Connected to APNS' . PHP_EOL;
    // Create the payload body
     $body['aps'] = array(
    'alert' => $message,
	'sound' => 'default',
	'status' => 'YES', 
	'ischatNotiKey' => $notmessage, 
    'msgsender_id'=>$msgsender_id
	);

    // Encode the payload as JSON
    $payload = json_encode($body);

    // Build the binary notification
	$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

	// Send it to the server
	$result = fwrite($fp, $msg, strlen($msg));

	echo "<pre>";
	print_r($result);



	if (!$result)
		echo 'Message not delivered' . PHP_EOL;
	else
		echo 'Message successfully delivered' . PHP_EOL;






	// Close the connection to the server
	fclose($fp);
 
}		 
// send_noti('cac2749a4f9ebff25e96ec12ed31cdb4a6520e3f7d5294a001f4417774b194e9','testing notification funyou',$notmessage='',$msgsender_id='');

/*
602bf95477c1f019ad9a91250831b3fc06fb05adec7b2bb4b81e925f9551a129 ---iphone
* cac2749a4f9ebff25e96ec12ed31cdb4a6520e3f7d5294a001f4417774b194e9 --ipad
* 
*/
?>
