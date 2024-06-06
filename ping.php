<?php
function encode_varint($value) {
	$varint = "";
	for($i = 0; $i < 5; $i++) {
		$byte = $value & 0x7F;
		$value >>= 7;
		if($value > 0) $byte |= 0x80;
		$varint .= chr($byte);
		if($value == 0) break;
	}
	return $varint;
}

function decode_varint($value) {
	$pos = 0;
	$result = 0;
	$shift = 0;
	while($pos < strlen($value)) {
		$byte = ord($value[$pos++]);
		$result |= ($byte & 0x7F) << $shift;
		if(($byte & 0x80) == 0) return $result;
		$shift += 7;
		if($shift >= 32) return false;
	}
	return false;
}

function ping($address, $port, $data) {
	$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("无法创建套接字");
	socket_set_option($server, SOL_SOCKET, SO_RCVTIMEO, ["sec" => 2, "usec" => 0]);
	socket_set_option($server, SOL_SOCKET, SO_SNDTIMEO, ["sec" => 2, "usec" => 0]);
	socket_connect($server, $address, $port) or die("无法连接服务器");
	// 也不知道怎么回事反应特慢，我也不知道我加这个怎么就修好了，可能是凭感觉
	socket_set_nonblock($server);

	socket_write($server, $data);

	$response = "";
	// 我也不知道我这是干什么
	$time = time();
	while(time() <= $time + 2) {
		$response2 = socket_read($server, 8192, PHP_BINARY_READ);
		if(!empty($response2)) $response .= $response2;
	}
	return $response;
}

if(isset($_GET["address"])) {
	$address = $_GET["address"];
} else if(isset($_POST["address"])) {
	$address = $_POST["address"];
}
if(isset($_GET["port"])) {
	$port = $_GET["port"];
} else if(isset($_POST["port"])) {
	$port = $_POST["port"];
}
if(isset($_GET["ver"])) {
	$ver = $_GET["ver"];
} else if(isset($_POST["ver"])) {
	$ver = $_POST["ver"];
}
if(isset($_GET["type"])) {
	$type = $_GET["type"];
} else if(isset($_POST["type"])) {
	$type = $_POST["type"];
}

if($ver == "java") {
	$handshake = encode_varint(0x00) . encode_varint(340) . encode_varint(strlen($address)) . $address . pack("n", $port) . encode_varint(1);
	$handshake = encode_varint(strlen($handshake)) . $handshake;

	$requeststatus = encode_varint(0x00);
	$requeststatus = encode_varint(strlen($requeststatus)) . $requeststatus;

	$response = ping($address, $port, $handshake . $requeststatus);

	if(empty($response)) {
		$data = base64_decode("/gH6");
		sleep(2);
		$response = ping($address, $port, $data);
		if($type == "raw") die($response);
		$response = hex2bin(preg_replace("/([A-Fa-f0-9]{2})00/", "$1", bin2hex($response)));
		$response = substr($response, strpos($response, base64_decode("pzEA")) + 3);
		if($type == "decode") die($response);
		$pvn = substr($response, 0, strpos($response, "\x00"));
		$response = substr($response, strpos($response, "\x00") + 1);
		$ver = substr($response, 0, strpos($response, "\x00"));
		$response = substr($response, strpos($response, "\x00") + 1);
		$motd = substr($response, 0, strpos($response, "\x00"));
		$response = substr($response, strpos($response, "\x00") + 1);
		$online = substr($response, 0, strpos($response, "\x00"));
		$max = substr($response, strpos($response, "\x00") + 1);
		if($type == "text") die("服务端版本: {$ver}\n协议版本: {$pvn}\nMOTD: {$motd}\n最大玩家数量: {$max}\n在线玩家数量: {$online}");
		exit;
	}

	if($type == "raw") die($response);

	$datapacketlength = decode_varint(substr($response, 0, 5));
	$datapacketlengthlength = strlen(encode_varint($datapacketlength));

	$response = substr($response, 0 + $datapacketlengthlength + 1 + 1);
	$response = substr($response, strpos($response, '{"'));

	if($type == "decode") die($response);
	if($type == "text") {
		$array = json_decode($response, true);
		die("服务端版本: {$array["version"]["name"]}\n协议版本: {$array["version"]["protocol"]}\nMOTD: {$array["description"]}\n最大玩家数量: {$array["players"]["max"]}\n在线玩家数量: {$array["players"]["online"]}");
	}
} else if($ver == "bedrock") {
	$data = pack("cQ", 0x01, time()) . base64_decode("AP//AP7+/v79/f39EjRWeA==") . pack("Q", 2);
	$server = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP) or die("无法创建套接字");
	socket_set_option($server, SOL_SOCKET, SO_RCVTIMEO, ["sec" => 2, "usec" => 0]);
	socket_set_option($server, SOL_SOCKET, SO_SNDTIMEO, ["sec" => 2, "usec" => 0]);
	socket_sendto($server, $data, strlen($data), 0, $address, $port) or die("无法发送数据");
	socket_recvfrom($server, $data, 1024, 0, $address, $port) or die("无法读取数据");
	socket_close($server);

	if($type == "raw") die($data);

	$data = explode(";", substr($data, strpos($data, "MCPE;") + 5));

	if($type == "decode") die(json_encode($data));
	if($type == "text") die("服务端版本: {$data[2]}\n协议版本: {$data[1]}\nMOTD: {$data[0]}\n最大玩家数量: {$data[4]}\n在线玩家数量: {$data[3]}");
}
