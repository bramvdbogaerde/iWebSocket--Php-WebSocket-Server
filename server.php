<?php
	require_once("server.class.php");
	class MyExample extends Server{
		function myCallBackFunction($input){
			/* Input give you an array with the message and the user who sends it:
			$input['msg'] - The message
			$input['user'] - The user
			*/
			$msg = $input['msg'];
			self::sendToAll($msg);
			/* 
			You can send data back to the client by sending it to a specific user
			Or by sending it to all
			self::sendToUser(THE_USER,THE_MESSAGE) - Send to a specific user
			self::sendToAll(THE_MESSAGE) - Send to all connected clients
			More documentation will be on github soon
			*/
		}
	}
	// To initialize the server the following things must be in your script
	$server = new Server("192.168.0.50","9091",false,"myCallBackFunction","MyExample");
	// new Server(HOST,PORT,DEBUGGING,FUNCTION,CLASS)