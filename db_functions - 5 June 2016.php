<?php

include('config.php');

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

	$sql = "INSERT INTO `users` (`email`, `password`, `college`)
	VALUES ('$email', '$password', '$college')";

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
	

	$query = "Select id from `users` where `email` = '$email' and `password` = '$password' ";
	$row = $db_connection->query($query);



	if( $row->num_rows ){

		$return['status'] = true;
		$return['id'] = $row->fetch_assoc()['id'];

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

	//$query = "Update users set chat_name = '$chat_name' where id = $user_id ";
	$members_id = [];
	$members_id[] = $user_id;
	$members_id_json = json_encode($members_id);
	$query = "Insert into chat_names ( `chat_name`, `user_id`, `members_id`  ) VALUES ( '$chat_name', $user_id, '$members_id_json' )";
	$status = $db_connection->query($query);

	if($status){
		$return['status'] = true;
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

	open_connection();

	$page = 1;

	$offset = 0;
	$limit = 20;

	if( isset($_GET['page']) ){
		$page = $_GET['page'];

		$offset = ($page - 1 ) * $limit;
	}

	

	$query = " Select users.id user_id, chat_names.id chat_name_id, chat_name, username from chat_names  
		Left join users on chat_names.user_id = users.id where chat_name LIKE '%$search_chat%' limit $limit offset $offset 
	";

	$result = $db_connection->query($query);
	$chats = [];

	if( $result->num_rows > 0 ){
		$return['status'] = 1;
		$return['page'] = $page;

		while ( $row = $result->fetch_assoc()) {
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

	open_connection();

	$page = 1;

	$offset = 0;
	$limit = 20;

	if( isset($_GET['page']) ){
		$page = $_GET['page'];

		$offset = ($page - 1 ) * $limit;
	}

	$query = " Select users.id user_id, chat_names.id chat_name_id, chat_name, username from chat_names 
		Left join users on chat_names.user_id = users.id
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
		if(!empty($_FILES))
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
		

		/*$json['res'] = $id ;
		$json['message'] = 'send successfully';
		*/
		$return['status'] = true;
		$return['res'] = $id;
		$return['message'] = 'send successfully';

		//echo "{\"response\":".json_encode($json)."}";
		
	
	// else
	// {
	// 	$return['res'] = 'Please provide the parameter :send_by, send_to,text,image';
	// 	//echo "{\"response\":".json_encode($json)."}";
	// }

		
	}
	else
	{
		$return['status'] = 0;
		$return['res'] = 'Please provide the parameter :send_by, send_to,text,image';
		//echo "{\"response\":".json_encode($json)."}";
	}	

	close_connection();


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
		

		$date1 = date("Y/m/d H:i");
		//$date =  date("d/m/Y h:i A", strtotime('+5 hours 30 minutes', strtotime($date1))) ;
        $date = date("d/m/Y h:i A");
		$posted_by = $postData['posted_by'] = $posted_by;
		$group_id = $postData['group_id'] = $group_id;
		if(!empty($_FILES))
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
		else
		{
			$message = $postData['message'] = addslashes($message);
			$type = $postData['type'] = 'text';
		}
		$posted_date = $postData['posted_date'] = $date;		
		
		$query = "Insert Into `group_chat` (`group_id`, `posted_by`, `message`, `posted_date`, `type`) values ($group_id, $posted_by, '$message', '$posted_date', '$type')";
		$status =  $db_connection->query($query);	
		
		
	
		if($status){
			$return['res'] = $id;
			$return['status'] = true;
			$return['message'] = 'Message Send Successfully';
		}
		else{
			$return['status'] = false;
			$return['message'] = 'Oops! error occur while sending group message';
			//$return['message'] = $query;
			
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
				$json['message'] = 'Not List Found';
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
			$json['message_id'] = '-1' ;
			$json['message'] = 'Not List Found';
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

	$query = "Select users.id request_by_id, users.username, users.image from group_requests left join users on request_by_id = users.id where group_user_id = $user_id and chat_group_id = $group_id";

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

	if( $respond_status = 'accept' ){
		$query = "Select members_id from chat_names where id = $group_id";
		$result = $db_connection->query($query);
		$row = $result->fetch_assoc();
		$members_id_json = $row['members_id'];
		$explode = explode(',', $members_id_json);
		$explode[] = $request_by_id;
		$members_id_json = implode(',', $explode);

		$query = "update chat_names set members_id = '$members_id_json' where id $group_id";
		$status = $db_connection->query($query);

		if($status){
			$return['status'] = true;
		}
		else{
			$return['status'] = false;
		}

	}
	else if( $respond_status = 'reject' ){
		$return['status'] = true;
	}

	


	close_connection();
	return $return;
}


?>