<?php
namespace NeoSocket;

class SocketManager {
	private $socket= null;
	private $is_client = false;
	public $host = "localhost";
	public $port = 6940;

	function __construct() {
		// Set time limit to indefinite execution
		set_time_limit(0);
	}
	
	/*
	function connect($host, $port){
		$this->socket = new CreateSocket($host, $port);
		$this->socket->create(true);
		$this->is_client = true;
		return $this;
	}
	*/

	function create($host, $port){
		$this->host = $host;
		$this->port = $port;
		$this->socket = new CreateSocket($host, $port);
		$this->socket->create();
		return $this;
	}

	function setup($callback){
		if(!$this->socket instanceof CreateSocket){
			$this->socket = new CreateSocket($this->host, $this->port);
			$this->socket->create();	
		}
		if(is_callable($callback)){
			$callback($this->socket);
		}
		return $this;
	}

	function run(){
		return $this->socket->run($this->is_client);
	}

}
?>