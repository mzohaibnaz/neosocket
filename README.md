# NeoSocket - (PHP)

NeoSocket is a very simple and lite library that can help you to manage your socket logics.
if you are using NeoSocket you should use [NeoSocket  - JS Client](#x) for complete solution package.

## Installation

  - Using composer
 
```sh
$ composer install mzohaib/socketphp
```
## How to use
  - [Initialization](#initializing-neosocket)
  - [Setup socket](#setup-your-socket)
  -  - [Change host & port](#binding-socket-to-hostport)
  - [Setup events](#setup-events)
  - - [Default event-types](#default-event-types)
  - - [New Connection](#new-connection)
  - - [On Disconnect](#on-user-disconnected)
  - [Sending data](#data-sending)
  -  - [Send to other event](#send-data-to-other-event)
  -  - [Send to client](#send-data-to-specific-client)
  - [Get all clients](#get-all-clients-with-attributes)
  - - [Add attributes](#set-client-custom-attributes)
  - - [Get client by attribute](#get-client-by-attribute)
  - [TIP* : how to do code chain](#how-to-do-code-chain)
  - [Dismiss client](#dismiss-client)


## Initializing NeoSocket
Initializing `NeoSocket` using `SocketManager` class
```php
// include NeoSocket vendor
require_once  "vendor/autoload.php";
// Using neoSocket namespace
use NeoSocket\SocketManager;
// Initializing neoSocket with SocketManager
$ns = new  SocketManager();
```

## Setup your socket
`setup` method will bind your socket on host localhost with port 6940
`setup` take 1 `parameter` as callback function
```php
$ns->setup(function($socket){
	// log on console socket is running
	print("\n\n Socket is Running \n\n");
});
```
##### Binding Socket to host/port
bind socket on different host and port using `create` method
`create` take 2 `parameter` as (host, port) 
```php
$ns->create("localhost", 1414)->setup(function($socket){
	// log on console socket is running
	print("\n\n Socket is Running  on localhost with port 1414 \n\n");
});
```
## Setup events
After socket is setup now setup all your events inside it.
For setup events use `on` method.
`on` method take 2 `parameter` (event_name, callback_function)
```php
$ns->setup(function($socket){
	// event setup callback take 2 parameter
	// first socket reference. second contain data from the client for that event
	function callback_fnc($socket, $data){
		// do something awesome here
	};
	
	$socket->on("test", callback_fnc);
});
```
###### setup event with anonymous function
```php
$socket->on("test", function($socket, $data){
	// do something awesome here
});
```
#### Default Event Types

`NeoSocket` library using 2 event types as default types to notify develop for new connection and disconnection of user. 
- `connection` for new connection
- `disconnected` for disconnection of user

##### New Connection

`connection` event-type take 2 parameters in callback

- socket reference
- uid of new connection `auto-generated`

###### Example for new connection 
```php
	// default event whenever there is new connection
	$socket->on("connection", function($socket, $uid){
	// log on console about new user with unique id
	// $uid is a unique id for each user in socket
	print("\n new user is here with id: {$uid}");
	// tell other users that new user is here with event `newuser`
	// that will send data to javascript client of neoSocket with event `newuser`
	// for chat room example tell all other users that,
	// there is new user
	$socket->event("newUser")->send("new user with id! : ".$uid);
});
```

##### On user disconnected

`disconnected` event-type take 2 parameters in callback

- socket reference
- uid of disconnected user

###### Example for disconnected
```php
$socket->on("disconnected", function($socket, $uid){
	echo  "\n user disconnected : {$uid} \n";
	// for chat room example tell other user that user is disconnected
	$socket->event("ondisconnect")->send("\n disconnected user with id! : {$uid} \n");
});
```


## Data Sending
`send` method is used to send data on events.
`send` method take 1 `parameter` as data ( `string / array` )
```php
$socket->on("test", function($socket, $data){
	// send data to `test` event
	$socket->send("hello test");
	// send data as array to `test` event
	$socket->send(["hello","world","test"]);
});
```

##### # send data to other event
`event` method used to select `event-type` before sending data on it.
`event` method take 1 parameter as event type

- ###### Example Code

```php
	$socket->event("neo")->send("hello neo");
```
- ###### Full code
```php
$socket->on("test", function($socket, $data){
	// send data to `test` event
	$socket->send("hello test");
	// send data to event type `neo`
	$socket->event("neo")->send("hello neo");
});
```
##### # send data to specific client

`client` method used to select `client` before sending data on it.
`client` method take 1 parameter as client `uid`

- ###### Example Code

```php
	$socket->client("testclient")->send("hello test user");
```
- ###### Full code
```php
$socket->on("test", function($socket, $data){
	// send data to `test` event
	$socket->send("hello test event");
	// send data to only `testclient`
	$socket->client("testclient")->send("hello test user");
});
```


### Get all clients with attributes
`getClients` is used to get list of all active clients in socket with their attributes

```php
$clients = $socket->getClients();
```

#### Set client custom attributes
`addAttr` will help you to add attributes to your client object for additional information storage.
`addAttr` take 2 parameters as `key` and `value` of an attribute.


> Note:  before adding attribute select client by [client](#send-data-to-specific-client) or [clientByAttr](#get-client-by-attribute) method.
- ##### Example code
```php
// for example you want to set first & last name for client
$socket->client("uid")->addAttr("first","test")->addAttr("last","user");
```
#### Get client by attribute
`clientByAttr` is used to select client like [client](#send-data-to-specific-client) 		`method` but  by its attribute value.
`clientByAttr` takes 3 parameters as mention below

-  `key` in which you want to search
-  `value` value of that key
- `reference`variable to store searched client . `optional parameter`

> Note: if searched result is multiple code will select the very first matched client.
##### # Example Code
```php
$socket->on("test", function($socket, $data){
	$found_client = false;
	// store selected client in found_client varible
	// and send message to selected client
	$socket->clientByAttr("username","test",$found_client)->send("hello test user");
});
```
### Reset Instance References
`reset` method is used to reset selected event/client for that current socket reference within on method

- when you call event/client method to select that selective statement remain until you call reset method. this can help you to perform chain actions. like example below

- ##### Code Example
```php
$socket->on("test", function($socket, $data){
	// this line send data to event-type `test`
	$socket->send("send data to test event");
	// this line send data to event-type `neo`
	$socket->event("neo")->send("send data to neo event");
	// because neo is still selected,
	// this line will send data to neo event-type
	$socket->send("send data to neo event");
	// reset references
	$socket->reset();
	// because all selective statements are reset,
	// now send will send data to `test` event-type,
	// because you are calling send method in `test`event
	$socket->send("hello test event-type");
});
```
 #### How to do code chain 
```php
$socket->on("test", function($socket, $data){
	// simple example of code chain :)
	$socket->send("send data to test event")
	->event("neo")->send("send data to neo event");
	->send("send data to neo event because `neo` event is selected");
	->client("testuser")
	->send("sending data to `testuser` with event-type `neo`")
	->event("greating")
	->send("sendinig data to `testuser but now with event-type `greating`")
	->reset()
	->send("send data to event-type `test` because references are reset!");
});
```
### Dismiss Client

`dismiss` method used to disconnect `client` from socket.
`dismiss` method take 1 parameter as client `uid`

- ###### Example Code
```php
	// dismiss client from socket with uid
	$socket->dismiss("testclient");
	// select client then dismiss it
	$socket->client($uid)->dismiss();
	// select client by attribute and dismiss it
	$socket->clientByAttr("username", "test")->dismiss();
```
# License
[MIT](https://choosealicense.com/licenses/mit/)