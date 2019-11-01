<?php
namespace NeoSocket;

class CreateSocket extends SocketDataAdapter{
    private $clients = [];
    private $clientsAttrs = [];
    private $host;
    private $port;
    private $socket;
    private $event = '';
    private $preEvent = '';
    private $eventCallbacks = [];
    private $is_client = false;
    private $selectedClient= false;
    private $selectedClientUID = false;

    private $newConnectionEventTag = "connection";
    private $disconnectEventTag = "disconnected";

    function __construct($host, $port) {
        $this->host = $host;
        $this->port = $port;
    }

    function create($is_client=false){
        //Create TCP/IP sream socket
        if(false == $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)){
            return false;
        }
        
        // check if socket is for client connection
        if($is_client){
            socket_connect($this->socket, $this->host, $this->port) or $this->onSocketFailure("Failed to connect to {$this->host}:{$this->port}", $this->socket);
            $this->is_client = true;
            return $this;
        }

        // create socket for server
        //reuseable port
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //bind socket to specified host
        socket_bind($this->socket, $this->host, $this->port);

        //listen to port
        socket_listen($this->socket);

        $this->clients[] = $this->socket;
        return $this;
    }    

    function run(){
        while (true) {
            if(!$this->is_client){
                //manage multipal connections
                $changed = $this->clients;
                //returns the socket resources in $changed array
                socket_select($changed, $null, $null, 0, 10);
                //check for new socket
                if (in_array($this->socket, $changed)) {
                    $uid = uniqid();
                    $socket_new = socket_accept($this->socket); //accpet new socket
                    $this->clients[$uid] = $socket_new; //add socket to client array
                    // add client to attr array
                    $this->clientsAttrs[$uid] = ["uid"=>$uid];
                    
                    $header = socket_read($socket_new, 1024); //read data sent by the socket
                    $this->perform_handshaking($header, $socket_new, $this->host, $this->port); //perform websocket handshake
                    socket_getpeername($socket_new, $ip); //get ip address of connected socke
                    
                    $this->onCallback($this->newConnectionEventTag, $uid);
                    
                    //make room for new socket
                    $found_socket = array_search($this->socket, $changed);
                    unset($changed[$found_socket]);
                }

                //loop through all connected sockets
                foreach ($changed as $changed_socket) {
                    //check for any incomming data
                    while(@socket_recv($changed_socket, $buf, 1024, 0) >= 1)
                    {
                        $received_text = $this->unmask($buf); //unmask data
                        $recv_data = json_decode($received_text); //json decode 
                        if($recv_data != null){
                            $type = $recv_data->NSEType;
                            unset($recv_data->NSEType);
                            $data = (isset($recv_data->NSEMsg)) ? $recv_data->NSEMsg : "";
                            $this->onCallback($type, $data);
                        }
                        break 2; //exist this loop
                    }
                    
                    $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
                    if ($buf === false) { // check disconnected client
                        // remove client for $clients array
                        $found_socket = array_search($changed_socket, $this->clients);
                        socket_getpeername($changed_socket, $ip);
                        socket_close($changed_socket);
                        unset($this->clients[$found_socket]);
                        unset($this->clientsAttrs[$found_socket]);
                        $this->onCallback($this->disconnectEventTag, $found_socket);
                    }
                }
            }
        }
    }

    function onCallback($eventType, $data=null){
        if(!isset($this->eventCallbacks[$eventType]))
            return;

        $this->selectedClient = false;
        $this->selectedClientUID = false;
        $this->event = $eventType;
        $this->preEvent = $eventType;

        $result = json_decode($data, 1);
        if(json_last_error() != JSON_ERROR_NONE){
            $result = $data;
        }
        call_user_func($this->eventCallbacks[$eventType], $this, $result);
    }

    function on($event, $eventCallback){
        $this->eventCallbacks[$event] = $eventCallback;
        return $this;
    }

    function event($event){
        $this->event = $event;
        return $this;
    }

    function dismiss($uid=false){
        if(!$uid){
            if(!$this->selectedClientUID)
                return false;
                
            $uid = $this->selectedClientUID;
        }
        if(!isset($this->clients[$uid]))
            return false;
        
        socket_close($this->clients[$uid]);
        unset($this->clients[$uid]);
        unset($this->clientsAttrs[$uid]);
        $this->selectedClient = false;
        $this->selectedClientUID = false;
    }

    function reset(){
        $this->selectedClientUID = false;
        $this->selectedClient = false;
        $this->event = $this->preEvent;
        return $this;
    }

    function getClients(){
        return array_values($this->clientsAttrs);
    }
    
    function client($uid){
        if(!isset($this->clients[$uid]))
            return;
        
        $this->selectedClientUID = $uid;
        $this->selectedClient = $this->clients[$uid];
        return $this;
    }

    function addAttr($key, $value, &$found=false){
        if(!($this->selectedClientUID && isset($this->clientsAttrs[$this->selectedClientUID])))
            return;

        $this->clientsAttrs[$this->selectedClientUID][$key] = $value;
        return $this;
    }

    function clientByAttr($key, $value){
        if(count($this->clientsAttrs) == 0)
            return;
        
        $filter = array_filter($this->clientsAttrs, function($c) use ($key, $value){
            return (isset($c[$key]) && $c[$key] == $value);
        });
        if(count($filter) > 0){
            $val = array_values($filter);      
            $found = $val[0];
            $this->client($val[0]["uid"]);
            return $this;
        }
    }

    function send($message){
        
        if(!$this->selectedClient){
            if(!$this->is_client){
                if(count($this->clients) <= 0)
                    return false;
                $clients = $this->clients;
            }else{
                $clients = $this->socket;
            }
        }else{
            $clients = $this->selectedClient;
        }

        $this->transferData($clients, $this->event, $message);
        return $this;
        
    }
}
?>