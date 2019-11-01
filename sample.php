<?php
// ======================================================================
// Run your socket server in command prompt with command `php sample.php`
//=======================================================================

// include neoSocket vendor
require_once "vendor/autoload.php";

// Using neoSocket namespace
use NeoSocket\SocketManager;

// Initializing neoSocket with SocketManager
$neoSocket = new SocketManager();

// create socket on localhost with port 6940 and setup callbacks
// if you want to change host/port use `create` method before `setup` method
// create method accepts 2 parameters (host, port)
$neoSocket->setup(function($socket){
    
    // log on console socket is running
    print("\n\n Socket is Running \n\n");
    // default event whenever there is new connection
    $socket->on("connection", function($socket, $uid){
        // log on console about new user with unique id
        // $uid is a unique id for each user
        print("\n new user is here with id: {$uid}");

        // tell other users that new user is here with event `newuser`
        // that will send data to javascript client of neoSocket with event `newuser` 
        $socket->event("newUser")->send("new user with id! : ".$uid);
    });
    
    // listen to data when javascript client send data on `message` event
    $socket->on("message", function($socket, $message){
        // process your incoming data
        $message = "hello world : ". $message;
        
        // send that message to other users. neoSocket is smart enough to know that you are on `message` event
        // but if you want to send data to other event just use event() function before send call
        
        // here is example
        //================

        // sending data to `message` event
        $socket->send($message);

        // sending data to `test` event
        $socket->event("test")->send($message);
        
    });

    // sending data to specific user in socket connected using uid of that socket
    // listing to javascript client on event `sendto`
    $socket->on("sendto", function($socket, $data){
        // data from javascript client
        $uid = $data["uid"];
        $msg = $data["msg"];

        // select specific user with client() function it will take uid of that socket as arrgument
        // send data to that user with send()
        // you can use clientByAttr() to select client with attitube. see in documentation how to add attritubes
        $socket->client($uid)->send($msg);
    });

    // let's dismiss/kick user from socket
    $socket->on("dismissUser", function($socket, $uid){
        // dismiss() is used to delete user from socket it will take $uid of that user as arrgument
        $socket->dismiss($uid);
        // or select a client and then dismiss
        $socket->client($uid)->send("your connected is dimissed by server!")->dismiss();
    });
    
    // that event will be called whenever user is disconnected callback function will give $uid of disconnected user!
    $socket->on("disconnected", function($socket, $uid){
        // log on console
        echo "\n user disconnected : {$uid} \n";
        // tell javascript client that this user is disconnected on event `ondisconnect`
        $socket->event("ondisconnect")->send("\n disconnected user with id! : {$uid} \n");
    });
    
    // after all setup configuration call run() to execute socket!
})->run();
?>