<?php

require_once ('db_functions.php');

$action = '';

if( isset($_GET['action']) ){
	$action = $_GET['action'];
}


switch ($action) {
	case 'signup':
		signup();
		break;

	case 'login':
		login();
		break;

	case 'logout':
		if(isset($_GET['user_id']))
			logout();
		else
			not_found();
		break;
		

	case 'forget_password':
		forget_password();
		break;

	case 'create_screen_name':
		create_screen_name();
		break;

	case 'create_chat_name':
		create_chat_name();
		break;

	case 'find_chats':
		find_chats();		
		break;

	case 'find_all_chats':
		if(isset($_GET['user_id']))
			find_all_chats();
		else
			not_found();
		break;

	case 'fetch_my_profile':
		fetch_my_profile();
		break;

	case 'update_my_profile':
		update_my_profile();
		break;		
	
	case 'send_message':
		send_message();
		break;

	case 'send_group_message':
		send_group_message();
		break;

	case 'fetch_single_chat':
		fetch_single_chat();
		break;

	case 'fetch_group_chat':
		fetch_group_chat();
		break;

	case 'delete_group':
		delete_group();
		break;

	case 'invite_friends':
		invite_friends();
		break;

	case 'group_chat_request':
		group_chat_request();
		break;

	case 'group_chat_request_list':
		group_chat_request_list();
		break;

	case 'respond_to_group_request':
		respond_to_group_request();
		break;

	case 'chat_comments':
		chat_comments();
		break;

	case 'fetch_chat_comments':
		if(isset($_GET['group_chat_id']))
			fetch_chat_comments();
		else
			not_found();
		break;

	case 'fetch_recent_chat':
		if(isset($_GET['user_id']))
			fetch_recent_chat();
		else
			not_found();
		
		break;

	case 'leave_group':
		leave_group();
		break;

	case 'get_all_members':
		if(isset($_GET['group_id']))
			get_all_members();
		else
			not_found();
		
		break;

	case 'remove_direct_chat':
		if( isset($_GET['remove_by']) and isset($_GET['remove_to']) ){

			remove_direct_chat();
		}
		else{
			not_found();
		}
		break;

	case 'get_group_memberList_by_name':
		if( isset($_GET['group_id']) and isset($_GET['username']) ){
			get_group_memberList_by_name();
		}
		else{
			not_found();
		}		
		break;

	case 'lock_unlock_group':
		if( isset($_GET['group_id']) and isset($_GET['lock_status']) ){
			lock_unlock_group();
		}
		else{
			not_found();
		}		
		break;

	case 'find_all_private_chats':
		if(isset($_GET['user_id']))
			find_all_private_chats();
		else
			not_found();
		break;

	case 'add_remove_friend_to_private_chat':
		add_remove_friend_to_private_chat();
		break;

	case 'get_user_list':
		if(isset($_GET['private_chat_id']))
			get_user_list();
		else
			not_found();
		
		break;

	case 'get_private_chat_members':
		if(isset($_GET['private_chat_id']))
			get_private_chat_members($_GET['private_chat_id']);
		else
			not_found();
		
		break;		

	case 'getMetaDataTesting':
		getMetaDataTesting();
		break;

	
	default:
		not_found();
		
}

function getMetaDataTesting(){
	$bind = [];
	$return = db_getMetaDataTesting();
	$bind = $return;

	make_response($bind);
}

function get_private_chat_members($private_chat_id){
	$bind = [];
	$return = db_get_private_chat_members($private_chat_id);

	if($return['status']){
		$bind['status'] = 1;
		$bind['members'] = $return['members'];
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'No members Found';
	}

	make_response($bind);
}

function get_user_list(){
	$bind = [];
	$return = db_get_user_list();

	if($return['status']){

		$bind['status'] = 1;
		$bind['response_type'] = 'get private chat user list';
		$bind['users'] = $return['users'];

	}
	else{
		$bind['status'] = 0;
		$bind['response_type'] = 'get private chat user list';
		$bind['message'] = 'No Users Found';
	}

	make_response($bind);
}

function lock_unlock_group(){
	$bind = [];
	$return = db_lock_unlock_group();

	if($return['status']){
		$bind['status'] = 1;
		
	}
	else{
		$bind['status'] = 0;
	}
	$bind['message'] = $return['message'];
	$bind['response_type'] = 'lock_unlock_group';
	make_response($bind);
}

function add_remove_friend_to_private_chat(){
	$bind = [];
	$return = db_add_remove_friend_to_private_chat();

	if($return['status']){
		$bind['status'] = 1;
		$bind['response_type'] = 'add_remove_friend_to_private_chat';
		if($return['flag'])
			$bind['message'] = 'Friend added Successfully in private chat';
		else
			$bind['message'] = 'Friend removed Successfully in private chat';

	}
	else{
		$bind['response_type'] = 'add_remove_friend_to_private_chat';
		$bind['status'] = 0;
		if($return['flag'])
			$bind['message'] = 'Oops ! error occur while adding to private chat';
		else
			$bind['message'] = 'Oops ! error occur while removing to private chat';
	}

	make_response($bind);


}

function get_group_memberList_by_name(){
	$bind = [];
	$return = db_get_group_memberList_by_name();
	if($return['status']){
		$bind['status'] = 1;
		$bind['members'] = $return['members'];
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = $return['message'];
	}

	make_response($bind);
}

function remove_direct_chat(){
	$bind = [];
	$return = db_remove_direct_chat();
	if($return){
		$bind['status'] = 1;
		$bind['message'] = 'User Removed Successfully';
		$bind['response_type'] = 'remove_direct_chat';
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'Oops! error occur while removing message';
		$bind['response_type'] = 'remove_direct_chat';
	}
	make_response($bind);
}

function logout(){
	$bind = [];
	$return = db_logout();

	if($return){
		$bind['status'] = 1;
		$bind['message'] = 'You are logout Successfully';
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'Oops! error occur while logout';
	}

	make_response($bind);
}

function get_all_members(){
	$bind = [];
	$return = db_get_all_members();

	if($return['status']){
		$bind['status'] = 1;
		$bind['members'] = $return['members'];
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = $return['message'];
	}

	make_response($bind);
}

function leave_group(){
	$bind = [];
	$return = db_leave_group();
	if($return['status']){
		$bind['status'] = 1;
		$bind['message'] = $return['message'];
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = $return['message'];
	}

	make_response($bind);
}

function fetch_recent_chat(){
	$bind = [];
	$return = db_fetch_recent_chat();

	if($return['status']){
		$bind['status'] = 1;
		$bind['recent_chat'] = $return['recent_chat'];
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = $return['message'];
	}

	make_response($bind);
}

function fetch_chat_comments(){
	$bind = [];
	$return = db_fetch_chat_comments();

	if($return['status']){
		$bind['status'] = 1;
		$bind['comments'] = $return['comments'];
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'No comments Found';
	}

	make_response($bind);
}

function chat_comments(){
	$bind = [];
	$return = db_chat_comments();

	if($return['status']){
		$bind['status'] = 1;
		$bind['message'] = 'Your comment was sent Successfully';
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'Oops! error occur while commenting';
		$bind['error'] = $return['error'];
	}

	make_response($bind);
}

function respond_to_group_request(){
	$bind = [];
	$return = db_respond_to_group_request();

	if($return['status'] ){
		$bind['status'] = 1;
		$bind['response_from'] = 'respond_to_request';
		$bind['message'] = 'Group request updated Successfully';
	}
	else{
		$bind['status'] = 0;
		$bind['response_from'] = 'respond_to_request';
		$bind['message'] = 'Oops! Error occur while responding to group request';
	}

	make_response($bind);
}

function group_chat_request_list(){
	$bind = [];
	$return = db_group_chat_request_list();

	if($return['data']){
		$bind['requests'] = $return['data'];
		$bind['request_from'] = 'group_request_list';
		$bind['status'] = 1;
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'No requests found';
	}

	make_response($bind);

}

function group_chat_request(){
	$bind = [];
	$return = db_group_chat_request();
	if($return['status']){
		$bind['status'] = 1;
		$bind['response_from'] = 'group_request';
		$bind['message'] = 'Group request sent Successfully';
	}
	else{
		$bind['status'] = 0;
		$bind['response_from'] = 'group_request';
		$bind['error'] = $return['error'];
		$bind['message'] = 'Oops! Error Occur while sending group request';
	}
	make_response($bind);
}

function invite_friends(){
	$bind = [];
	db_invite_friends();
}

function delete_group(){
	$bind = [];
	$return = db_delete_group();

	if($return['status']){
		$bind['status'] = 1;
		$bind['message'] = 'Group Deleted Successfully';
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'Oops ! Error Occur while deleting group';
	}

	make_response($bind);
}

function fetch_group_chat(){
	$bind = [];
	$return = db_fetch_group_chat();
}

function fetch_single_chat(){
	$bind = [];
	$return = db_fetch_single_chat();
}

function send_group_message(){
	$bind = [];
	$return = db_send_group_message();

	if( $return['status'] ){
		$bind['status'] = 1;
		$bind['mentioned_username_json'] = $return['mentioned_username_json'];
		$bind['message'] = $return['message'];
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = $return['message'];
	}

	make_response($bind);

}

function send_message(){

	$bind = [];
	$return = db_send_message();

	if( $return['status'] ){
		$bind['status'] = 1;
		$bind['res'] = $return['res'];
		$bind['message'] = $return['message'];
		
	}
	else{
		$bind['status'] = 0;
		$bind['res'] = $return['res'];
	}

	make_response($bind);
}



function update_my_profile(){

	$bind = [];
	$return = db_update_my_profile();

	if( $return['status'] ){
		$bind['status'] = 1;
		$bind['message'] = 'Profile was uploaded Successfully';

	}
	else{
		$bind['status'] = 0;
		$bind['profile_image_error'] = $return['profile_image_error'];
		$bind['bg_image_error'] = $return['bg_image_error'];
		$bind['error'] = $return['error'];

	}

	make_response($bind);
}

function fetch_my_profile(){
	$bind = [];
	$return = db_fetch_my_profile();

	if( $return['status'] ){
		$bind['status'] = 1;
		$bind['user'] = $return['user'];

	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'Invalid user id';
	}

	make_response($bind);
}

function find_all_chats(){ // public chats
	$bind = [];
	$return = db_find_all_chats();

	if( $return['status'] ){
		$bind['status'] = 1;
		$bind['response_from'] = 'group_list';
		$bind['page'] = $return['page'];
		$bind['chats'] = $return['chats'];

		if(isset($_GET['chat_type']) and $_GET['chat_type'] = 'private' ){
			$bind['response_from'] = 'private_chat_list';
		}

	}
	else{
		$bind['status'] = 0;
		$bind['response_from'] = 'group_list';
		$bind['message'] = 'No chats found';

		if(isset($_GET['chat_type']) and $_GET['chat_type'] = 'private' ){
			$bind['response_from'] = 'private_chat_list';
		}
	}

	make_response($bind);
}



function find_chats(){
	$bind = [];
	$return = db_find_chats();

	if( $return['status'] ){
		$bind['status'] = 1;
		$bind['response_from'] = 'find_chats';
		$bind['page'] = $return['page'];
		$bind['chats'] = $return['chats'];

	}
	else{
		$bind['status'] = 0;
		$bind['response_from'] = 'find_chats';
		$bind['message'] = 'No chats found';
	}

	make_response($bind);
}


function create_chat_name(){
	$bind = [];
	$return = db_create_chat_name();

	if($return['status']){
		$bind['status'] = 1;
		$bind['message'] = 'Chat name was Created Successfully';

		$bind['username'] = $return['username'];
		$bind['chat_name'] = $return['chat_name'];

		$bind['user_id'] = $return['user_id'];
		$bind['chat_name_id'] = $return['chat_name_id'];
		$bind['is_member'] = $return['is_member'];
	}
	else{
		$bind['status'] = 0;

		if( $return['errno'] == 1062 ){
			$bind['message'] = 'Chat name already exists';
		}
		else if( $return['errno'] == 1452 ){
			$bind['message'] = 'Invalid user';
		}

		
	}

	make_response($bind);


}

function create_screen_name(){
	$bind = [];
	$return = db_create_screen_name();

	if($return['status']){
		$bind['status'] = 1;
		$bind['message'] = 'Screen name was Created Successfully';
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'Screen name already exists';
		$bind['error'] = $return['error'];
	}

	make_response($bind);
}

function signup(){
	$bind = [];
	$return = db_sign_up();

	if($return['status']){
		$bind['status'] = 1;
		$bind['message'] = 'You are registered Successfully';
		$bind['user_id'] = $return['user_id'];
	}
	else{
		$bind['status'] = 0;
		
			$bind['message'] = 'Email id already exists';
		
		
	}

	make_response($bind);
}

function login(){
	$bind = [];
	$return = db_login();

	if($return['status']){
		$bind['status'] = 1;
		$bind['user_id'] = (int) $return['id'];
		$bind['message'] = 'You are login Successfully';
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'Oops ! Invalid Credentials';
	}
	make_response($bind);

}

function forget_password(){
	$bind = [];
	$return = db_forget_password();

	if( $return['status'] ){
		$bind['status'] = 1;
		$bind['message'] = 'Password Sent to your email address';
	}
	else{
		$bind['status'] = 0;
		$bind['message'] = 'Oops ! Invalid email';
	}

	make_response($bind);
}

function make_response($bind){

	$json = json_encode($bind);
	header('Content-Type: application/json');
	echo $json;
	die;
}

function not_found(){
	$bind = [];

	$bind['status'] = 0;
	$bind['message'] = 'The requested action not found';

	$json = json_encode($bind);

	header('HTTP/1.1 400 Not Found');
	header('Content-Type: application/json');
	echo $json;
	die;
}

?>