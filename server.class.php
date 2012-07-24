<?php
/* Server Class 
  Class created by Bram Vandenbogaerde(github:bramvdbogaerde) 
  Everybody is free to fork it and change anything only if you put somewhere on your project
  page that the project originaly came from github.com/bramvdbogaerde
  General use can be found in example.server.php
  More documentation will come later(soon as possible) on the github project page
   */

require_once("frames.class.php");
class Server extends Frames{
	protected $socket;
	static protected $connections;
	protected $callbackfunction;
	protected $maxDisconnectTime = 100;
	protected $callbackclass;
	protected $numberofconnections = 30;
	protected $currentnumber = 0;
	function __construct($server,$port,$debuging,$callback,$class){
         set_time_limit(0);
         $this->callbackfunction = $callback;
         $this->callbackclass = $class;
         self::$connections = array();
	     $this->createServer($server,$port,$debuging);
	}
	public function createServer($host,$port,$debuging){
		$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($socket,$host,$port);
		socket_listen($socket);
        /* This is very important to have multiple connections at know socket at the same time: 
           */ socket_set_nonblock($socket); /*
           But it blocks socket_accept to accept the connections 
           I'm currently searching for a solution for that */
		if(!$socket){
			echo "Server isn't started \n";
			echo "$errstr($errno)";
		}
		else{
			echo "==============================\n";
			echo "Server started successfully \n";
			echo "Domain: ".$host;
			echo "\nPort: ".$port;
			echo "\nMax clients: ".$this->numberofconnections;
			echo "\nTimeout time: ".$this->maxDisconnectTime;
			echo "\nCurrent number of clients: ".$this->currentnumber;
			echo "\nListening for connections";
			echo "\n==============================";
			do{
				    if (($connection = @socket_accept($socket)) === false) {
      				  echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
      				  break;
    				}
    				else{
						echo "\nConnected client";
    				}

    				do{
    				   if (false === ($msg = @socket_read($connection, 2048))) {
           					echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($connection)) . "\n";
           					break 2;
       					}
       					else{

       						if(!in_array($connection,self::$connections)){
								echo "\n New client is trying to connect";
								echo "\n Doing handshake";
								$result = $this->doHandShake($msg);
								socket_write($connection,$result,strlen($result));
								array_push(self::$connections,$connection);
							}
							else{
								$this->proccessMessages($msg,$connection);
							}
       					}	
       				}while(true);
			}while(true);
		}
	}
	
	public function onData(){
		if(stream_select(self::$connections,$w = null,$ex = null,$this->maxDisconnectTime) > 0){
			foreach(self::$connections as $user){
					$msg = socket_read($user,1020);
					$this->proccessMessages($msg,$user);
				}
		}
	}
	public function close(){
		$this->currentnumber = $this->currentnumber-1;
		echo "\n Connection closed";
	}
	public function doHandShake($req){
		$r = $h = $o = $key1 = $key2 = $data = $key = null;
        if(preg_match("/GET (.*) HTTP/"   , $req, $match))              { $resource=$match[1];    }
        if(preg_match("/Host: (.*)\r\n/"  , $req, $match))              { $host=$match[1];    }
        if(preg_match("/Origin: (.*)\r\n/", $req, $match))              { $origin=$match[1];    }
        if(preg_match("/Sec-WebSocket-Key2: (.*)\r\n/", $req, $match))  { $key2=$match[1]; }
        if(preg_match("/Sec-WebSocket-Key1: (.*)\r\n/", $req, $match))  { $key1=$match[1]; }
        if(preg_match("/\r\n(.*?)\$/", $req, $match))                   { $data=$match[1]; }
        if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$req,$match)){$key = $match[1]; }
	     $hash_data = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		 $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
               "Upgrade: WebSocket\r\n" .
               "Connection: Upgrade\r\n" .
               "Sec-WebSocket-Origin: " . $origin . "\r\n" .
               "Sec-WebSocket-Accept: ".$hash_data. "\r\n".
               "Sec-WebSocket-Location: ws://" . $host . $resource . "\r\n" .
               "\r\n"; 
    	  return $upgrade;
	}
	function proccessMessages($msg,$user){
		$msg = $this->decode($msg);
		$msg = $msg['payload'];
		$output['msg'] = $msg;
		$output['user'] = $user;
		call_user_func($this->callbackclass."::".$this->callbackfunction,$output);
	}
	function setDisconnectTime($time = 100){
		$this->maxDisconnectTime = $time;
	}
	function sendToUser($user,$msg){
		$msg = self::hybi10Encode($msg);
		socket_write($user,$msg,strlen($msg));
	}
	function sendToAll($msg){
		foreach(self::$connections as $user){
			self::sendToUser($user,$msg);
		}
	}
	function setMaxNumber($number){
		$this->$numberofconnections = $number;
	}
}
