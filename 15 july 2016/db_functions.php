<?php

include('config.php');
//include('send_noti_iphone.php');

// is_member 0: not a member, 1: a member, 2: pending, 3: group lock

$db_connection = '';
date_default_timezone_set('UTC');

error_reporting(E_ERROR);

function open_connection(){ // open connection to database

	global $db_hostname, $db_username, $db_password, $db_name, $db_connection;
	$db_connection = new mysqli($db_hostname, $db_username, $db_password, $db_name);
}

function close_connection(){ // close connection
	global $db_connection;
	$db_connection->close();
}

function db_sign_up( ){
	global $db_connection;
	$return = [];

	$json_input = file_get_contents('php://input');
	$data = json_decode($json_input, true);

	open_connection();
	$email = $db_connection->escape_string($data['email']);
	$password = $db_connection->escape_string($data['password']);
	$password = md5($password);
	$college = $db_connection->escape_string($data['college']);
	$screen_name = $db_connection->escape_string( $data['screen_name'] );

	$sql = "INSERT INTO `users` (`email`, `password`, `college`, `username`)
	VALUES ('$email', '$password', '$college', '$screen_name')";

	$status = $db_connection->query($sql);
	

	if($status){
		$return['status'] = true;
		$return['user_id'] = $db_connection->insert_id;
	}
	else{
		$return['status'] = false;
		$return['error'] = $db_connection->error;
		$return['errno'] = $db_connection->errno;
	}

	
	close_connection();

	return $return;

}

function db_login(){
	global $db_connection;
	$return = [];

	$json_input = file_get_contents('php://input');
	$data = json_decode($json_input, true);

	open_connection();
	$email = $db_connection->escape_string($data['email']);
	$password = $db_connection->escape_string($data['password']);
	$password = md5($password);
	$token_id = $data['token_id'];
	

	$query = "Select id from `users` where `email` = '$email' and `password` = '$password' ";
	$result = $db_connection->query($query);



	if( $result->num_rows ){
		$row = $result->fetch_assoc();
		
		$return['status'] = true;
		$return['id'] = $row['id'];

		$query = "update users set token_id = '$token_id', online_status = true where id = ".$row['id'];
		$status = $db_connection->query($query);



	}
	else{
		$return['status'] = false;
	}

	return $return;
}

function db_forget_password(){
	global $db_connection;
	$return = [];

	$json_input = file_get_contents('php://input');
	$data = json_decode($json_input, true);

	open_connection();
	$email = $db_connection->escape_string($data['email']);
	
	$query = "Select id from `users` where `email` = '$email'";
	$row = $db_connection->query($query);

	if( $row->num_rows ){
		$return['status'] = true;

		$length = 8;

		$password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);

		$msg = "Your new password is : $password";

		mail($email, "new password", $msg);

		$password = md5($password);

		$query = "Update `users` set password = '$password' where `email` = '$email' ";
		$db_connection->query($query);

	}
	else{
		$return['status'] = false;
	}

	return $return;

}

function db_create_screen_name(){
	global $db_connection;

	$return = [];

	$json_input = file_get_contents('php://input');
	$data = json_decode($json_input, true);

	open_connection();

	$screen_name = $db_connection->escape_string($data['screen_name']);
	$user_id = $db_connection->escape_string($data['user_id']);

	$query = "Update users set username = '$screen_name' where id = $user_id ";
	$status = $db_connection->query($query);

	if($status){
		$return['status'] = true;
	}
	else{
		$return['status'] = false;
		$return['error'] = $db_connection->error;
	}

	close_connection();

	return $return;

}


function db_create_chat_name(){
	global $db_connection;

	$return = [];

	$json_input = file_get_contents('php://input');
	$data = json_decode($json_input, true);

	open_connection();

	$chat_name = $db_connection->escape_string($data['chat_name']);
	$user_id = $db_connection->escape_string($data['user_id']);
	$chat_type = $data['chat_type'];

	//$query = "Update users set chat_name = '$chat_name' where id = $user_id ";
	$members_id = [];
	$members_id[] = $user_id;
	$members_id_json = json_encode($members_id);
	$query = "Insert into chat_names ( `chat_name`, `user_id`, `members_id`, `chat_type`  ) VALUES ( '$chat_name', $user_id, '$members_id_json', '$chat_type' )";
	$status = $db_connection->query($query);
	$group_id = $db_connection->insert_id;

	if($status){
		$return['status'] = true;

		

		$query = "Select username from users where id = $user_id";
		$result = $db_connection->query($query);
		$row = $result->fetch_assoc();
		$username = $row['username'];

		$return['username'] = $username;
		$return['chat_name'] = $chat_name;

		$return['user_id'] = $user_id;
		$return['chat_name_id'] = $group_id;
		$return['is_member'] = 1;

		
	
	}
	else{
		$return['status'] = false;
		$return['errno'] = $db_connection->errno;
		$return['error'] = $db_connection->error;
	}

	close_connection();

	return $return;

}

function db_find_chats(){
	global $db_connection;

	$return = [];

	$json_input = file_get_contents('php://input');
	$data = json_decode($json_input, true);

	$search_chat = $data['search_chat'];
	$user_id = $data['user_id'];

	$chat_type = isset($data['chat_type']) ? $data['chat_type'] : 'public'; 

	open_connection();

	$page = 1;

	$offset = 0;
	$limit = 20;

	if( isset($_GET['page']) ){
		$page = $_GET['page'];

		$offset = ($page - 1 ) * $limit;
	}

	

	$query = " Select users.id user_id, chat_names.id chat_name_id, chat_name, username, lock_status from chat_names  
		Left join users on chat_names.user_id = users.id where chat_name LIKE '%$search_chat%' and chat_type = '$chat_type' limit $limit offset $offset 
	";

	$result = $db_connection->query($query);
	$chats = [];

	if( $result->num_rows > 0 ){
		$return['status'] = 1;
		$return['page'] = $page;

		while ( $row = $result->fetch_assoc()) {
			$query = "Select id c_id from chat_names where members_id like '%$user_id%' and id = ".$row['chat_name_id'];

			$result2 = $db_connection->query($query);
			$r1 = $result2->fetch_assoc()['c_id'];

			$row['is_member'] = 0;
			if($r1){
				$row['is_member'] = 1;
			}

			$group_id = $row['chat_name_id'];

			$query = "Select request_status from group_requests where chat_group_id = $group_id and request_by_id = $user_id";

			$result3 = $db_connection->query($query);
			$r3 = $result3->fetch_assoc();

			$row['is_member'] = 1;
			if($row['lock_status']){
				$row['is_member'] = 0;
				if($r1){
					$row['is_member'] = 1;
				}
			}

			if($r3){

				$request_status = $r3['request_status'];
				if( $request_status == 0 ){
					$row['is_member'] = 2;
				}

			}

			

			$query = "Select count(id) user_count  from group_requests where chat_group_id = $group_id and request_status = 0";

			$result4 = $db_connection->query($query);
			$r4 = $result4->fetch_assoc();
			$row['user_count'] = $r4['user_count'];


			$chats[] = $row;
		}

		$return['chats'] = $chats;

	}
	else{
		$return['status'] = 0;
	}

	close_connection();

	return $return;
} 


function db_find_all_chats(){
	global $db_connection;

	$return = [];

	$input = file_get_contents('php://input');
	$user_id = $_GET['user_id'];

	$chat_type = isset($_GET['chat_type']) ? $_GET['chat_type'] : 'public' ;



	open_connection();

	$page = 1;

	$offset = 0;
	$limit = 40;

	if( isset($_GET['page']) ){
		$page = $_GET['page'];

		$offset = ($page - 1 ) * $limit;
	}

	$query = " Select users.id user_id, chat_names.id chat_name_id, chat_name, username, lock_status from chat_names 
		Left join users on chat_names.user_id = users.id where chat_type = '$chat_type'
		order by rand()
		limit $limit offset $offset
	";

	$result = $db_connection->query($query);
	$chats = [];

	if( $result->num_rows > 0 ){
		$return['status'] = 1;
		$return['page'] = $page;

		while ( $row = $result->fetch_assoc()) {
			$query = "Select id c_id from chat_names where members_id like '%$user_id%' and id = ".$row['chat_name_id'];

			$result2 = $db_connection->query($query);
			$r1 = $result2->fetch_assoc()['c_id'];

			$row['is_member'] = 0;
			if($r1){
				$row['is_member'] = 1;
			}

			$group_id = $row['chat_name_id'];

			$query = "Select request_status from group_requests where chat_group_id = $group_id and request_by_id = $user_id";

			$result3 = $db_connection->query($query);
			$r3 = $result3->fetch_assoc();

			$row['is_member'] = 1;
			if($row['lock_status']){
				$row['is_member'] = 0;
				if($r1){
					$row['is_member'] = 1;
				}
			}


			if($r3){

				$request_status = $r3['request_status'];
				if( $request_status == 0 ){
					$row['is_member'] = 2;
				}

			}

			


			$query = "Select count(id) user_count  from group_requests where chat_group_id = $group_id and request_status = 0";

			$result4 = $db_connection->query($query);
			$r4 = $result4->fetch_assoc();
			$row['user_count'] = $r4['user_count'];



			$chats[] = $row;

			//$query = "select count(*) from group_requests where chat_group_id = ".$row['chat_name_id']
		}

		$return['chats'] = $chats;

	}
	else{
		$return['status'] = 0;
	}

	close_connection();

	return $return;
}

function db_fetch_my_profile(){
	global $db_connection;

	$return = [];

	$json_input = file_get_contents('php://input');
	$data = json_decode($json_input, true);

	$user_id = $data['user_id'];

	open_connection();

	$query = " Select username, email, college, image, user_status, bg_image from users where id = $user_id ";

	$row = $db_connection->query($query);
	$user = [];
	$base_url = base_url();

	if( $row->num_rows == 1 ){
		$return['status'] = 1;
		$user = $row->fetch_assoc();
		$user['username'] = $user['username'] ? $user['username'] : '';
		//$user['image'] = $user['image'] ? $base_url.'/yardin/'.$user['image'] : '';
		$user['image'] = $user['image'] ? $user['image'] : '';
		$user['user_status'] = $user['user_status'] ? $user['user_status'] : '';
		$user['bg_image'] = $user['bg_image'] ? $user['bg_image'] : '';

		$return['user'] = $user;
	}
	else{
		$return['status'] = 0;
	}

	close_connection();

	return $return;

}

function db_update_my_profile(){
	global $db_connection;

	$return = [];

	open_connection();


	$user_id = $_POST['user_id'];
	
	$user_status = $_POST['user_status'];

	$file_name1 = '';
	$file_name2 = '';

	if( isset($_FILES['image']) and !empty($_FILES['image']) ){
      $errors= array();
      $file_name1 = $_FILES['image']['name'];
      $file_size =$_FILES['image']['size'];
      $file_tmp =$_FILES['image']['tmp_name'];
      $file_type=$_FILES['image']['type'];
      $file_ext=strtolower(end(explode('.',$_FILES['image']['name'])));
      
      $expensions= array("jpeg","jpg","png");

      $file_name1 = time().'.'.$file_ext;
      
      if(in_array($file_ext,$expensions) == false){
         $errors[]="extension not allowed, please choose a JPEG or PNG file.";
      }
      
      if($file_size > 2097152){
         $errors[]='File size must be less than 2 MB';
      }
      
      if(empty($errors)==true){
        move_uploaded_file($file_tmp,"profile_image/".$file_name1);
        $file_name1 = "profile_image/$file_name1";
        
      }
      else{
      	$return['profile_image_error'] = $errors;
         
      }
   }

   

   if( isset($_FILES['bg_image']) and !empty($_FILES['bg_image'])){
      $errors= array();
      $file_name2 = $_FILES['bg_image']['name'];
      $file_size =$_FILES['bg_image']['size'];
      $file_tmp =$_FILES['bg_image']['tmp_name'];
      $file_type=$_FILES['bg_image']['type'];
      $file_ext=strtolower(end(explode('.',$_FILES['bg_image']['name'])));
      
      $expensions= array("jpeg","jpg","png");

      $file_name2 = time().'.'.$file_ext;
      
      if(in_array($file_ext,$expensions) == false){
         $errors[]="extension not allowed, please choose a JPEG or PNG file.";
      }
      
      if($file_size > 2097152){
         $errors[]='File size must be less than 2 MB';
      }
      
    if(empty($errors)==true){
        move_uploaded_file($file_tmp,"bg_image/".$file_name2);
        $file_name2 = "bg_image/$file_name2";
    }
    else{
    	$return['bg_image_error'] = $errors;
         
    }
   }

   if( $return['profile_image_error'] or $return['bg_image_error'] = $errors  ){
   		$return['status'] = false;
   }
   else{
   		$return['status'] = true;
   }

   
	$updated_time =  date("Y-m-d H:i:s", strtotime('+5 hours 30 minutes', time())) ;

	$query = "Update users set image = '$file_name1', bg_image = '$file_name2', user_status = '$user_status', updated_time = '$updated_time' where id = $user_id ";
	$status = $db_connection->query($query);

	

	if($status){
		$return['status'] = true;
		$return['error'] = '';
	}
	else{
		$return['status'] = false;
		
		$return['error'] = $db_connection->error;
	}

	close_connection();
	return $return;
}


function db_send_message()
{
	global $db_connection;

	$return = [];

	open_connection();

	if(isset($_POST) && !empty($_POST))
		{

		extract($_POST);	

		$postData =array();
		$date1 = date("Y/m/d H:i");
		$date =  date("d/m/Y h:i A", strtotime('+5 hours 30 minutes', strtotime($date1))) ;
		//$posted_date =  date("d/m/Y h:i A", strtotime('+5 hours 30 minutes', strtotime($date1)));
		$posted_date = date("d/m/Y h:i A");
		$posted_date_str = strtotime($posted_date);
		
		$send_by = $postData['send_by'] = $send_by;
		$send_to = $postData['send_to'] = $send_to;
		if(!empty($_FILES['image']))
		{
			$img = 'image';
			$filename = $_FILES[$img]['name'];
			$temp = explode(".", $_FILES[$img]['name']);
			$extension = end($temp);
			$UNEEQUE = uniqid();
			$finalimage = $UNEEQUE.'.'.$extension;
			$new_dir = dirname(__FILE__)."/chat_pic/". $finalimage;
			move_uploaded_file($_FILES[$img]["tmp_name"], $new_dir);
			$text = $postData['text'] = $finalimage;
			$type = $postData['type'] = 'image';
		}
		else if(!empty($_FILES['video'])){
			$video = 'video';
			$filename = $_FILES[$video]['name'];
			$temp = explode(".", $_FILES[$video]['name']);
			$extension = end($temp);
			$UNEEQUE = uniqid();
			$finalvideo = $UNEEQUE.'.'.$extension;
			$new_dir = dirname(__FILE__)."/chat_video/". $finalvideo;
			move_uploaded_file($_FILES[$video]["tmp_name"], $new_dir);
			$message = $postData['message'] = $finalvideo;
			$type = $postData['type'] = 'video';

		}
		else
		{
			$text = $postData['text'] = addslashes($text);
			$type = 'text';
		}
		
		$posted_date = $postData['posted_date'] = date("d/m/Y h:i A");		
		$created_time = date('Y-m-d H:i:s');
		
		$query = "insert into `single_chat` (`send_by`,`send_to`,`text`,`posted_date`,`type`,`created_time`,`updated_time`)
		 values($send_by,$send_to,'$text','$posted_date','$type','$created_time','$created_time')";
		
		$status = $db_connection->query($query);
		$id =  $db_connection->insert_id;	


		$qry = "select * from last_posted_message where (send_to=".$send_to." and send_by=".$send_by.") or  (send_to=".$send_by." and send_by=".$send_to . ')';
		$row = $db_connection->query($qry);
		$chk = $row->fetch_assoc();
		if(empty($chk))
		{
		   $query="insert into last_posted_message (`send_by`,`send_to`,`text`,`posted_date`,`created_time`) values('$send_by','$send_to','$text','$posted_date','$created_time')";

	       $db_connection->query($query);
	    				
		}
		else
		{    
	         $chkid = $chk['id'];

			 $query="update last_posted_message set `send_by` = $send_by, `send_to` = $send_to, text='$text',posted_date='$posted_date' where id = $chkid";
		
		     $db_connection->query($query);
			
		}
		
		$query = "select * from `users` where id = $send_by";
		$result = $db_connection->query($query);
		$senderDeatail = $result->fetch_assoc();
		//$msg  =$senderDeatail['first_name']." send you message";
		$msg = $senderDeatail['username']." sent you message";
		$sender_name = $senderDeatail['username'];
		$sender_id = $send_by;
		
		//$reciverDeatail = $obj->select_single_row('user','uid',$send_to);
		$query = "select * from `users` where id = $send_to";
		$result = $db_connection->query($query);
		$reciverDeatail = $result->fetch_assoc();

		$noti_status = send_noti($reciverDeatail['token_id'],$msg,'single_chat',$sender_id);

		//$return['notification_status'] = $noti_status;



	// 			if($reciverDeatail['device_type']=='A')
	// 			{
	// /*
	// 				echo $msg;
	// */
	// 				//echo$reciverDeatail['token_id'];
	// 				$obj->gcm($reciverDeatail['token_id'],$msg,'single_chat',$sender_name,$sender_id);
	// 			}
	// 			else
	// 			{
	// 				//echo$reciverDeatail['token_id'];
	// 				 send_noti($reciverDeatail['token_id'],$msg,'single_chat',$sender_id);

	// 			}
		

		$json['res'] = 10 ;
		$json['message'] = 'send successfully';
		
		$return['status'] = true;
		$return['res'] = $id;
		$return['message'] = 'Message send successfully';

		echo "{\"response\":".json_encode($json)."}";

		//echo 'hello';
			

		// else
		// {
		// 	$return['res'] = 'Please provide the parameter :send_by, send_to,text,image';
		// 	//echo "{\"response\":".json_encode($json)."}";
		// }

		
	}
	else
	{
		/*$return['status'] = 0;
		$return['res'] = 'Please provide the parameter :send_by, send_to,text,image';*/
		//echo "{\"response\":".json_encode($json)."}";

		$return['res'] = 'Please provide the parameter :send_by, send_to,text,image';
		echo "{\"response\":".json_encode($json)."}";
	}	

	close_connection();

	die;


	return $return;
}

function db_send_group_message(){

	global $db_connection;
	$return = [];
	open_connection();


	if(isset($_POST) && !empty($_POST))
	{
		
		extract($_POST);	
		
		
		$postData =array();

		$mentioned_username_json = $arry_mention;
		//echo $mentioned_username_json;die;
		

		$date1 = date("Y/m/d H:i");
		//$date =  date("d/m/Y h:i A", strtotime('+5 hours 30 minutes', strtotime($date1))) ;
        $date = date("d/m/Y h:i A");
		$posted_by = $postData['posted_by'] = $posted_by;
		$group_id = $postData['group_id'] = $group_id;
		if(!empty($_FILES['image']))
		{
			$img = 'image';
			$filename = $_FILES[$img]['name'];
			$temp = explode(".", $_FILES[$img]['name']);
			$extension = end($temp);
			$UNEEQUE = uniqid();
			$finalimage = $UNEEQUE.'.'.$extension;
			$new_dir = dirname(__FILE__)."/chat_pic/". $finalimage;
			move_uploaded_file($_FILES[$img]["tmp_name"], $new_dir);
			$message = $postData['message'] = $finalimage;
			$type = $postData['type'] = 'image';
		}
		else if(!empty($_FILES['video'])){
			$video = 'video';
			$filename = $_FILES[$video]['name'];
			$temp = explode(".", $_FILES[$video]['name']);
			$extension = end($temp);
			$UNEEQUE = uniqid();
			$finalvideo = $UNEEQUE.'.'.$extension;
			$new_dir = dirname(__FILE__)."/chat_video/". $finalvideo;
			move_uploaded_file($_FILES[$video]["tmp_name"], $new_dir);
			$message = $postData['message'] = $finalvideo;
			$type = $postData['type'] = 'video';

		}
		else
		{
			$message = $postData['message'] = addslashes($message);
			$type = $postData['type'] = 'text';
		}
		$posted_date = $postData['posted_date'] = $date;		
		
		$query = "Insert Into `group_chat` (`group_id`, `posted_by`, `message`, `posted_date`, `type`) values ($group_id, $posted_by, '$message', '$posted_date', '$type')";
		$status =  $db_connection->query($query);



		//send_noti('dfdf', $message, 'fdf', 47);


		//send_noti('b1613c57ae38cd31df18899075052b3438fc511843dd39ad6036d12c66855042','group_msg','group_chat',$posted_by);

		$query = "select members_id from chat_names where id = $group_id";
		$result = $db_connection->query($query);

		$row = $result->fetch_assoc();

		$members_id_arr = json_decode($row['members_id'], true);

		if( isset($chattype) and $chattype == 'private' ){

			$mentioned_username_arr = json_decode($mentioned_username_json, true);
			if( count($mentioned_username_arr) ){
				foreach ($mentioned_username_arr as  $member) {
					$query = "select token_id from `users` where username like '$member'";
					$result = $db_connection->query($query);
					$reciverDeatail = $result->fetch_assoc();

					

					if($reciverDeatail['token_id']){
						send_noti($reciverDeatail['token_id'], $message, 'groupmsg', $posted_by);

						//$message = $senderDeatail['username'].' whisper you in '.$groupDeatail['chat_name']. ' group';
						send_noti($reciverDeatail['token_id'], $message, 'mention_noti', $posted_by);
						/*$noti_status = send_noti($reciverDeatail['token_id'],$message,'group_chat',$posted_by);
						$return['notification_status'] = $noti_status;*/
					}
				}
			}


		}
		else{
			foreach ($members_id_arr as $member_id) {
				$query = "select * from `users` where id = $member_id";
				$result = $db_connection->query($query);
				$reciverDeatail = $result->fetch_assoc();

				if($reciverDeatail['token_id']){
					send_noti($reciverDeatail['token_id'], $message, 'groupmsg', $posted_by);
					/*$noti_status = send_noti($reciverDeatail['token_id'],$message,'group_chat',$posted_by);
					$return['notification_status'] = $noti_status;*/
				}

			
			}
		}

		

		$mentioned_username_arr = json_decode($mentioned_username_json, true);
		if( count($mentioned_username_arr) and ! isset($chattype)){
			foreach ($mentioned_username_arr as  $member) {
				$query = "select token_id from `users` where username like '$member'";
				$result = $db_connection->query($query);
				$reciverDeatail = $result->fetch_assoc();

				$query = "select username from `users` where id = $posted_by";
				$result = $db_connection->query($query);
				$senderDeatail = $result->fetch_assoc();

				$query = "select chat_name from `chat_names` where id = $group_id";
				$result = $db_connection->query($query);
				$groupDeatail = $result->fetch_assoc();


				$message = $senderDeatail['username'].' mentioned you in '.$groupDeatail['chat_name']. ' group';
				if(isset($chattype) and $chattype == 'private'){
					$message = $senderDeatail['username'].' whisper you in '.$groupDeatail['chat_name']. ' group';
				}

				if($reciverDeatail['token_id']){
					send_noti($reciverDeatail['token_id'], $message, 'mention_noti', $posted_by);
					/*$noti_status = send_noti($reciverDeatail['token_id'],$message,'group_chat',$posted_by);
					$return['notification_status'] = $noti_status;*/
				}
			}
		}
			
		
		
	
		if($status){
			$return['res'] = $id;
			$return['status'] = true;
			$return['mentioned_username_json'] = $mentioned_username_json;
			$return['message'] = 'Message Send Successfully';
		}
		else{
			$return['status'] = false;
			$return['message'] = 'Oops! error occur while sending group message';
			//$return['message'] = $query;
			
		}

		// check to see if user exist in group member list or not
		$query = "Select members_id from chat_names where id = $group_id";
		$result = $db_connection->query($query);
		$row = $result->fetch_assoc();
		$members_id_json = $row['members_id'];
		$json_decode = json_decode($members_id_json, true);

		if(! in_array($posted_by, $json_decode)){ // insert user in group members list
			$json_decode[] = $posted_by;
			$members_id_json = json_encode($json_decode);

			$query = "Update chat_names set members_id = '$members_id_json' where id = $group_id";
			$status = $db_connection->query($query);
		}
		
	}
	else
	{
		$return['status'] = false;
		$return['message'] = 'Please provide the parameter :posted_by, group_id,message,image';
		
	}

	close_connection();
	return $return;
}

function db_fetch_single_chat(){
	global $db_connection;

	open_connection();

	
	if(isset($_GET['user_id']) && isset($_GET['person_id']))
	{
		
		extract($_GET);		
		$query = "select * from `single_chat` 
			
		where (send_to=".$person_id." and send_by=".$user_id.") or  (send_to=".$user_id." and send_by=".$person_id.") order by id ASC ";

		//$result = $obj->run_result_query($qry);

		$result = $db_connection->query($query);
	
			
			if($result->num_rows > 0)
			{
				$final = array();
				$base_url = base_url();
				while($res = $result->fetch_assoc())
				{
					// echo $temp['posted_date'] = date('H:i:s d-m-y',strtotime($res['posted_date']));
					$temp['message_id'] = $res['id'];
					$temp['send_by'] = $res['send_by'];
					$temp['send_to'] = $res['send_to'];

					
					$temp['chat_image_base_url'] = $base_url. '/yardin/chat_pic/';

					$temp['text'] = $res['text'];
					$temp['type'] = $res['type'];

					//$temp['posted_date'] = date('Y-d-m h:i A',strtotime($res['posted_date']));
					$temp['posted_date'] = date('Y-m-d h:i A',strtotime($res['posted_date']));
					$temp['posted_date_str'] = date('Y-d-m h:i A',strtotime($res['posted_date']));
					
					
					// $temp =$res;
					//$person_id = ($res['send_by']==$user_id)?$res['send_to']:$res['send_by'];
					$qry1 = "select username fname,image profile_pic from users where id =".$res['send_by'];
					//$send_by_detail = $obj->run_row_query($qry1);
					$send_by_detail = $db_connection->query($qry1);
					$send_by_detail = $send_by_detail->fetch_assoc();

					$temp['send_by_name'] = $send_by_detail['fname'] ? $send_by_detail['fname'] : '';
					$temp['send_by_image'] = $send_by_detail['profile_pic'] ? $send_by_detail['profile_pic'] : '';
					$qry2 = "select username fname,image profile_pic from users where id =".$res['send_to'];
					//$send_by_detail = $obj->run_row_query($qry1);
					$send_by_detail = $db_connection->query($qry2);
					$send_by_detail = $send_by_detail->fetch_assoc();
					$temp['send_to_name'] = $send_to_detail['fname'] ? $send_to_detail['fname'] : '';
					$temp['send_to_image'] = $send_to_detail['profile_pic'] ? $send_to_detail['profile_pic'] : '';
					
					$final[]  = $temp;


				}
				$json = $final;
	//echo "<pre>"; print_r($json); echo "</pre>";
				header('Content-Type: application/json');
				echo "{\"response\":".json_encode($json)."}";
	
			}
			else
			{
				$json['message_id'] = '-1' ;
				$json['message'] = 'No List Found';
				header('Content-Type: application/json');
				echo "{\"response\":[".json_encode($json)."]}";
			}
			
			 
		
	}
	else
	{
		$json['res'] = 'Please provide the parameter :user_id,person_id';
		header('Content-Type: application/json');
		echo "{\"response\":".json_encode($json)."}";
	}


	close_connection();

	die;
}

function db_fetch_group_chat(){
	global $db_connection;

	open_connection();
	$base_url = base_url();

	if(isset($_GET['user_id']) && isset($_GET['group_id']))
	{
		extract($_GET);		
	
		$qry = "select c.*,r.id as user_id,r.username fname,r.image profile_pic from group_chat  c,users r WHERE r.id=c.posted_by 
		and c.group_id='".$group_id."'  order by chat_id  ASC"; 
		/* $qry = "select c.*,r.uid as user_id,r.fname,r.profile_pic from group_chat  c,user r WHERE r.uid=c.posted_by 
		and c.group_id='".$group_id."'  order by postedc_date  ASC"; */
		//$result = $obj->run_result_query($qry);

		$result = $db_connection->query($qry);	
		//print_r($result);
		
		if($result->num_rows > 0)
		{
			$base_url = base_url();
			$final = array();
			while($res = $result->fetch_assoc())
			{
				//$temp =	$res;
				if($res['posted_by'] == $user_id)
				{
					$temp['chat_id'] = $res['chat_id'];
					$temp['group_id'] = $res['group_id'];
					$res['posted_date'] = str_replace("/","-",$res['posted_date']);
					//$temp['posted_date'] = date('Y-d-m h:i A' ,strtotime($res['posted_date']));
					$temp['posted_date'] = date('Y-m-d h:i A' ,strtotime($res['posted_date']));
					$temp['uid'] = $res['user_id'];
					$temp['send_by'] = $res['posted_by'];
					//$temp['send_by_image'] = $res['profile_pic'] ? $base_url.'/yardin/'.$res['profile_pic'] : '';
					$temp['send_by_image'] = $res['profile_pic'] ? $res['profile_pic'] : '';
					$temp['send_by_name'] = $res['fname'];
					$temp['text'] = $res['message'];
					$temp['type'] = $res['type'];
					$temp['chat_image_base_url'] = $base_url. '/yardin/chat_pic/';
					$temp['message_status'] = 'MY';
				}
				else
				{
					
					$temp['chat_id'] = $res['chat_id'];
					$temp['group_id'] = $res['group_id'];
					$res['posted_date'] = str_replace("/","-",$res['posted_date']);
					//$temp['posted_date'] = date('Y-d-m h:i A',strtotime($res['posted_date']));
					$temp['posted_date'] = date('Y-m-d h:i A',strtotime($res['posted_date']));
					$temp['uid'] = $res['user_id'];
					$temp['send_by'] = $res['posted_by'];
					$temp['send_by_image'] = $res['profile_pic'] ? $res['profile_pic'] : '';
					$temp['send_by_name'] = $res['fname'];
					$temp['text'] = $res['message'];
					$temp['type'] = $res['type'];
					$temp['chat_image_base_url'] = $base_url. '/yardin/chat_pic/';
					$temp['message_status'] = 'FRIEND';
				}
				$final[] = $temp;
			}
			$json = $final;
			header('Content-Type: application/json');
			echo "{\"response\":".json_encode($json)."}";
		}
		else
		{
			$json['chat_id'] = '-1' ;
			$json['message'] = 'No List Found';
			header('Content-Type: application/json');
			echo "{\"response\":[".json_encode($json)."]}";
		}
	}
	else
	{
		$json['res'] = 'Please provide the parameter :user_id,group_id';
		header('Content-Type: application/json');
		echo "{\"response\":".json_encode($json)."}";
	}


	close_connection();
	die;
}

function base_url(){
  return sprintf(
    "%s://%s",
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['SERVER_NAME']
  );
}

function db_delete_group(){

	global $db_connection;

	$return = [];
	
	$json_input = file_get_contents('php://input');
	$data = json_decode($json_input, true);

	$group_id = $data['group_id'];
	open_connection();

	$query = 'Delete from chat_names where id = '.$group_id;
	$status = $db_connection->query($query);

	$return['status'] = $status;

	close_connection();
	return $return;

}

function db_invite_friends(){
	global $db_connection;

	open_connection();

	$input = file_get_contents('php://input');
	$data = json_decode($input);

	close_connection();
}

function db_group_chat_request(){
	global $db_connection;
	$return = [];

	open_connection();

	$input = file_get_contents('php://input');
	
	$data = json_decode($input, true);


	$group_id = $data['group_id'];
	$request_by_id = $data['request_by_id'];

	$query = "Select user_id from chat_names where id = $group_id";
	$result = $db_connection->query($query);
	$row = $result->fetch_assoc();
	$group_user_id = $row['user_id']; 

	$query = "Insert into group_requests (`chat_group_id`, `request_by_id`, `group_user_id` ) values ($group_id, $request_by_id, $group_user_id)";

	$status = $db_connection->query($query);

	if($status){
		$return['status'] = true;
	}
	else{
		$return['status'] = false;
		$return['error'] = $db_connection->error;
	}


	$query = 'Select username, token_id from users where id = '.$group_user_id;
	$result = $db_connection->query($query);
	$row = $result->fetch_assoc();

	if( $row['token_id'] ){
		$recivertok_id = $row['token_id'];
		$message = $row['username'].' has sent you group request';
		$notmessage = 'join';
		//$msgsender_id = $request_by_id;
		$msgsender_id = $group_id;
		send_noti($recivertok_id, $message, $notmessage, $msgsender_id);
	}


	close_connection();

	return $return;
}

function db_group_chat_request_list(){
	global $db_connection;

	open_connection();

	$input = file_get_contents('php://input');
	$data = json_decode($input, true);

	$user_id = $data['user_id'];
	$group_id = $data['group_id'];

	$query = "Select users.id request_by_id, users.username, users.image from group_requests left join users on request_by_id = users.id where group_user_id = $user_id and chat_group_id = $group_id and request_status = '0'";

	$result = $db_connection->query($query);

	 
	 $data = [];

	 while ($row = $result->fetch_assoc()) {
	 	$data[] = $row;
	 }

	 $return['data'] = $data;

	close_connection();

	return $return;
}

function db_respond_to_group_request(){
	global $db_connection;
	$return = [];
	open_connection();

	$input = file_get_contents('php://input');
	$data = json_decode($input, true);

	$group_id = $data['group_id'];
	$request_by_id = $data['request_by_id'];

	$respond_status = $data['respond_status'];

	/*$query = "Select user_id from chat_names where id = $group_id";
	$result = $db_connection->query($query);
	$row = $result->fetch_assoc();
	$group_user_id = $row['user_id'];*/

	if( $respond_status == 'accept' ){
		$query = "Select members_id from chat_names where id = $group_id";
		$result = $db_connection->query($query);
		$row = $result->fetch_assoc();
		$members_id_json = $row['members_id'];
		$explode = json_decode($members_id_json);

		$explode[] = $request_by_id;
		$members_id_json = json_encode($explode);


		$query = "update chat_names set members_id = '$members_id_json' where id = $group_id";
		$status = $db_connection->query($query);

		
		if($status){
			$return['status'] = true;
		}
		else{
			$return['status'] = false;
		}

	}
	else if( $respond_status == 'reject' ){
		$query = "Delete from group_requests where chat_group_id = $group_id and request_by_id = $request_by_id";
		$status = $db_connection->query($query);

		$return['status'] = true;
	}

	$query = "update group_requests set request_status = '1' where chat_group_id = $group_id and request_by_id = $request_by_id";

	$db_connection->query($query);



	$query = 'Select username, token_id from users where id = '.$request_by_id;
	$result = $db_connection->query($query);
	$row = $result->fetch_assoc();


	$query = "Select user_id,username from users join chat_names on users.id = user_id  where chat_names.id = $group_id";
	$result = $db_connection->query($query);
	$row1 = $result->fetch_assoc();
	//$group_user_id = $row1['user_id']; 



	if( $row['token_id'] ){
		$recivertok_id = $row['token_id'];
		if($respond_status == 'accept'){
			$message = $row1['username'].' has accepted your group request';
			$notmessage = 'accept';
		}
		else{
			$message = $row1['username'].' has rejected your group request';
			$notmessage = 'reject';
		}
		
		
		$msgsender_id = $row1['user_id'];
		send_noti($recivertok_id, $message, $notmessage, $msgsender_id);
	}


	close_connection();
	return $return;
}

function db_chat_comments(){
	global $db_connection;
	$return = [];
	open_connection();

	$input = file_get_contents('php://input');
	$data = json_decode($input, true);

	$group_chat_id = $data['group_chat_id'];
	$comment = $db_connection->escape_string($data['comment']);
	$comment_by = $data['comment_by'];

	$current_date = date('Y-m-d H:i:s');

	$query = "Insert into comments (`group_chat_id`, `comment`, `comment_by`, `created_time`) values ($group_chat_id, '$comment', $comment_by, '$current_date') ";

	$status = $db_connection->query($query);

	if($status){
		$return['status'] = true;
	}
	else{
		$return['status'] = false;
		$return['error'] = $db_connection->error;
	}

	close_connection();

	return $return;
}

function db_fetch_chat_comments(){
	global $db_connection;
	open_connection();

	$return = [];
	$data = [];
	$group_chat_id = $_GET['group_chat_id'];

	$query = "SELECT `group_chat_id`, `comments`.`id` as comment_id,`comment`, comments.created_time `comment_time`, `comment_by` comment_by_user_id, username, image  FROM `comments` left JOIN users on `comment_by` = users.id WHERE `group_chat_id` = $group_chat_id";

	$result = $db_connection->query($query);

	if($result->num_rows > 0){
		$return['status'] = true;
		while ( $row = $result->fetch_assoc()) {
			$row['username'] = $row['username'] ? $row['username'] : '';
			$row['image'] = $row['image'] ? $row['image'] : '';
			$row['comment_time'] = date('Y-m-d h:i a', strtotime($row['comment_time']));
			$data[] = $row;

		}
		$return['comments'] = $data;
	}
	else{
		$return['status'] = false;
		$return['message'] = 'No comment Found';
	}

	
	close_connection();
	return $return;
}

function db_fetch_recent_chat(){
	global $db_connection;
	$return = [];
	$user_id = $_GET['user_id'];
	$data = [];
	open_connection();

	$query = "SELECT * FROM `last_posted_message` WHERE (`send_by` = $user_id or `send_to` = $user_id) and ( direct_message_remove_by1 != $user_id and direct_message_remove_by2 != $user_id  ) order by id DESC";


	$result = $db_connection->query($query);

	if($result->num_rows > 0 ){
		$i = 0;
		while($row = $result->fetch_assoc()){
			if( $row['send_by'] == $user_id ){
				$query1 = "SELECT `username`, `image`, online_status FROM `users` WHERE `id` = ".$row['send_to'];
				$result1 = $db_connection->query($query1);
				$row1 = $result1->fetch_assoc();

				$data[$i]['username'] = $row1['username'] ? $row1['username'] : '' ;
				$data[$i]['image'] = $row1['image'] ? $row1['image'] : '' ;
				$data[$i]['user_id'] = $row['send_to']  ;
				$data[$i]['online_status'] = $row1['online_status']  ;


			}
			else{
				$query1 = "SELECT `username`, `image`, online_status FROM `users` WHERE `id` = ".$row['send_by'];
				$result1 = $db_connection->query($query1);
				$row1 = $result1->fetch_assoc();

				$data[$i]['username'] = $row1['username'] ? $row1['username'] : '' ;
				$data[$i]['image'] = $row1['image'] ? $row1['image'] : '' ;
				$data[$i]['user_id'] = $row['send_by']  ;
				$data[$i]['online_status'] = $row1['online_status']  ;
			}
			$data[$i]['message'] = $row['text']  ;
			$i++;

		}
		$return['recent_chat'] = $data;
		$return['status'] = true;

	}
	else{
		$return['status'] = false;
		$return['message'] = 'No chats found';
	}

	close_connection();

	return $return;
}

function db_leave_group(){
	global $db_connection;

	open_connection();
	$return = [];
	$input = file_get_contents('php://input');
	$data = json_decode($input, true);
	$user_id = $data['user_id'];
	$group_id = $data['group_id'];

	$query = "select members_id from chat_names where id = $group_id";
	$result = $db_connection->query($query);
	$row = $result->fetch_assoc();
	$members_id_json = $row['members_id'];
	$members_id_arr = json_decode($members_id_json, true);

	$key = array_search($user_id,$members_id_arr);
	if($key!==false){
	    unset($members_id_arr[$key]); // remove member from group
	    $return['status'] = true;

	    $members_id_json = json_encode($members_id_arr);

	    $query = "Update chat_names set members_id = '$members_id_json' where id = $group_id";
	    $status = $db_connection->query($query);

	    $query = "Delete from group_requests where chat_group_id = $group_id and request_by_id = $user_id";
	    $status = $db_connection->query($query);

	    $return['message'] = 'Group leave successfully';

	}
	else{
		$return['status'] = false;
		$return['message'] = 'Oops! Error occur while leaving group';
	}



	close_connection();

	return $return;
}

function db_get_all_members(){
	global $db_connection;
	$return = [];
	$members = [];

	open_connection();

	$group_id = $_GET['group_id'];

	$query = "Select members_id from chat_names where id = $group_id";
	$result = $db_connection->query($query);

	$members_id_json = $result->fetch_assoc()['members_id'];
	$members_id = json_decode($members_id_json, true);

	$members_in = implode(',', $members_id);

	$query = "Select users.id user_id, username, image, user_status from users where id in ($members_in)";

	$result = $db_connection->query($query);

	if($result->num_rows > 0){
		$return['status'] = true;
		while ($row = $result->fetch_assoc()) {
			$row['username'] = $row['username'] ? $row['username'] : '';
			$row['image'] = $row['image'] ? $row['image'] : '';
			$row['user_status'] = $row['user_status'] ? $row['user_status'] : '';
			$members[] = $row;
		}

		$return['members'] = $members;
	}
	else{
		$return['status'] = false;
		$return['message'] = 'No members found';
	}

	close_connection();

	return $return;
}

function db_logout(){
	global $db_connection;
	open_connection();

	$return = [];
	$user_id = $_GET['user_id'];

	$query = 'Update users set token_id = "", online_status = false where id = '.$user_id;
	$status = $db_connection->query($query);
	close_connection();

	return $status;
}

function db_remove_direct_chat(){
	global $db_connection;
	open_connection();

	$return = [];
	$remove_by = $_GET['remove_by'];
	$remove_to = $_GET['remove_to'];

	$query = "SELECT id, direct_message_remove_by1, direct_message_remove_by2 FROM `last_posted_message` WHERE (`send_by` = $remove_by and send_to = $remove_to) or (`send_to` = $remove_by and send_by = $remove_to)";
	$result = $db_connection->query($query);
	$row = $result->fetch_assoc();
	$latest_message_id = $row['id'];

	if($row['direct_message_remove_by1']){
		$query = "Update last_posted_message set direct_message_remove_by2 = $remove_by where id = $latest_message_id";
		
	}
	else{
		$query = "Update last_posted_message set direct_message_remove_by1 = $remove_by where id = $latest_message_id";
	}

	$status = $db_connection->query($query);


	close_connection();

	return $status;
}

function db_get_group_memberList_by_name(){
	global $db_connection;
	$return = [];
	$group_id = $_GET['group_id'];
	$username = $_GET['username'];

	open_connection();

	$query = "Select members_id from chat_names where id = $group_id";
	$result = $db_connection->query($query);
	$row = $result->fetch_assoc();
	$members_id_json = $row['members_id'];
	$members_id_arr = json_decode($members_id_json, true);
	$members_in = implode(',', $members_id_arr);



	$query = "Select username from users where id in ($members_in) and username like  '$username%'";
	$result = $db_connection->query($query);

	

	if($result->num_rows > 0){
		$return['status'] = true;
		while ( $row = $result->fetch_assoc() ) {
			$return['members'][] = $row;
		}

	}
	else{
		$return['status'] = false;
		$return['message'] = 'No username found';
	}

	close_connection();
	return $return;
}

function db_lock_unlock_group(){
	global $db_connection;
	$return = [];

	$group_id = $_GET['group_id'];
	$lock_status = (int) $_GET['lock_status'];
	open_connection();

	$query = "Update chat_names set lock_status = $lock_status where id = $group_id";
	$status = $db_connection->query($query);

	if($status){
		$return['status'] = true;
		if($lock_status){
			$return['message'] = 'Group Locked Successfully';
		}
		else{
			$return['message'] = 'Group Unlocked Successfully';
		}
	}
	else{
		$return['status'] = false;
		$return['message'] = 'Oops! Error occur while locking/unlocking group';
	}

	close_connection();

	return $return;

}

 


// send notification


function send_noti($recivertok_id,$message,$notmessage='',$msgsender_id='')
{  
    // Put your device token here (without spaces):
    $deviceToken = $recivertok_id;
    //$deviceToken = 'b1613c57ae38cd31df18899075052b3438fc511843dd39ad6036d12c66855042';

    // Put your private key's passphrase here:
    $passphrase = '';
    // Put your alert message here:
    $message = $message;
    //$message = 'hello testing';
    ////////////////////////////////////////////////////////////////////////////////

    $ctx = stream_context_create();
    //stream_context_set_option($ctx, 'ssl', 'local_cert', 'cabmaps.pem');
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'HYarding.pem');
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

	return 'sent';

	/*
	echo "<pre>";
	print_r($result);



	if (!$result)
		echo 'Message not delivered' . PHP_EOL;
	else
		echo 'Message successfully delivered' . PHP_EOL;




*/

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