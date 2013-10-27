<?php
###################################################################
# tracker is developped with GPL Licence 2.0
#
# GPL License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
#
# Developped by : Cyril Feraudet
#
###################################################################
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
#    For information : cyril@feraudet.com
####################################################################
/**
  * Database creation script
  * SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
  * CREATE DATABASE `GS503` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
  * USE `GS503`;
  * 
  * CREATE TABLE IF NOT EXISTS `gps` (
  *   `id` int(11) NOT NULL AUTO_INCREMENT,
  *   `date` datetime NOT NULL,
  *   `imei` varchar(50) NOT NULL,
  *   `latitude` float NOT NULL,
  *   `longitude` float NOT NULL,
  *   `speed` int(11) NOT NULL,
  *   `direction` int(11) NOT NULL,
  *   `type_identity_code` varchar(4) NOT NULL,
  *   `language` int(11) NOT NULL,
  *   `east_west_timezone` varchar(1) NOT NULL,
  *   `timezone` varchar(4) NOT NULL,
  *   `north_south_latitude` varchar(1) NOT NULL,
  *   `east_west_latitude` varchar(1) NOT NULL,
  *   `gps_location_fixed` int(11) NOT NULL,
  *   `realtime_gps` int(11) NOT NULL,
  *   PRIMARY KEY (`id`)
  * ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
  * 
  * CREATE TABLE IF NOT EXISTS `extended_lsb` (
  *   `id` int(11) NOT NULL AUTO_INCREMENT,
  *   `date` datetime NOT NULL,
  *   `imei` varchar(50) NOT NULL,
  *   `mcc` int(11) NOT NULL,
  *   `mnc` int(11) NOT NULL,
  *   `lac` int(11) NOT NULL,
  *   `mci` int(11) NOT NULL,
  *   `rssi` int(11) NOT NULL,
  *   PRIMARY KEY (`id`)
  * ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
  * 
  * CREATE TABLE IF NOT EXISTS `lsb` (
  *   `id` int(11) NOT NULL AUTO_INCREMENT,
  *   `date` datetime NOT NULL,
  *   `imei` varchar(50) NOT NULL,
  *   `mcc` int(11) NOT NULL,
  *   `mnc` int(11) NOT NULL,
  *   `lac` int(11) NOT NULL,
  *   `ci` int(11) NOT NULL,
  *   `rssi` int(11) NOT NULL,
  *   PRIMARY KEY (`id`)
  * ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
  * 
  * CREATE TABLE IF NOT EXISTS `status` (
  *   `id` int(11) NOT NULL AUTO_INCREMENT,
  *   `date` datetime NOT NULL,
  *   `imei` varchar(50) NOT NULL,
  *   `voltage` int(11) NOT NULL,
  *   `gsm_signal` int(11) NOT NULL,
  *   PRIMARY KEY (`id`)
  * ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
  */
$ip = "192.210.139.43";
$port = 1025;
$mysql_host = 'localhost';
$mysql_user = 'tracker';
$mysql_passwd = '';
$mysql_database = 'GS503';

$__server_listening = true;

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);

$GLOBALS['foreground'] = true;
if(!isset($argv[1]) || $argv[1] != '-f') {
	$GLOBALS['foreground'] = false;
	become_daemon();
}
if ($GLOBALS['foreground'] == true) { echo "Foreground\n"; }

if ($GLOBALS['foreground'] == false) {
	/* nobody/nogroup, change to your host's uid/gid of the non-priv user */
	change_identity(65534, 65534);

	/* handle signals */
	pcntl_signal(SIGTERM, 'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGCHLD, 'sig_handler');
}

$crctab16 = array(0x0000, 0x1189, 0x2312, 0x329b, 0x4624, 0x57ad, 0x6536, 0x74bf, 
	0x8c48, 0x9dc1, 0xaf5a, 0xbed3, 0xca6c, 0xdbe5, 0xe97e, 0xf8f7, 
	0x1081, 0x0108, 0x3393, 0x221a, 0x56a5, 0x472c, 0x75b7, 0x643e, 
	0x9cc9, 0x8d40, 0xbfdb, 0xae52, 0xdaed, 0xcb64, 0xf9ff, 0xe876, 
	0x2102, 0x308b, 0x0210, 0x1399, 0x6726, 0x76af, 0x4434, 0x55bd, 
	0xad4a, 0xbcc3, 0x8e58, 0x9fd1, 0xeb6e, 0xfae7, 0xc87c, 0xd9f5, 
	0x3183, 0x200a, 0x1291, 0x0318, 0x77a7, 0x662e, 0x54b5, 0x453c, 
	0xbdcb, 0xac42, 0x9ed9, 0x8f50, 0xfbef, 0xea66, 0xd8fd, 0xc974, 
	0x4204, 0x538d, 0x6116, 0x709f, 0x0420, 0x15a9, 0x2732, 0x36bb, 
	0xce4c, 0xdfc5, 0xed5e, 0xfcd7, 0x8868, 0x99e1, 0xab7a, 0xbaf3, 
	0x5285, 0x430c, 0x7197, 0x601e, 0x14a1, 0x0528, 0x37b3, 0x263a, 
	0xdecd, 0xcf44, 0xfddf, 0xec56, 0x98e9, 0x8960, 0xbbfb, 0xaa72, 
	0x6306, 0x728f, 0x4014, 0x519d, 0x2522, 0x34ab, 0x0630, 0x17b9, 
	0xef4e, 0xfec7, 0xcc5c, 0xddd5, 0xa96a, 0xb8e3, 0x8a78, 0x9bf1, 
	0x7387, 0x620e, 0x5095, 0x411c, 0x35a3, 0x242a, 0x16b1, 0x0738, 
	0xffcf, 0xee46, 0xdcdd, 0xcd54, 0xb9eb, 0xa862, 0x9af9, 0x8b70, 
	0x8408, 0x9581, 0xa71a, 0xb693, 0xc22c, 0xd3a5, 0xe13e, 0xf0b7, 
	0x0840, 0x19c9, 0x2b52, 0x3adb, 0x4e64, 0x5fed, 0x6d76, 0x7cff, 
	0x9489, 0x8500, 0xb79b, 0xa612, 0xd2ad, 0xc324, 0xf1bf, 0xe036, 
	0x18c1, 0x0948, 0x3bd3, 0x2a5a, 0x5ee5, 0x4f6c, 0x7df7, 0x6c7e, 
	0xa50a, 0xb483, 0x8618, 0x9791, 0xe32e, 0xf2a7, 0xc03c, 0xd1b5, 
	0x2942, 0x38cb, 0x0a50, 0x1bd9, 0x6f66, 0x7eef, 0x4c74, 0x5dfd, 
	0xb58b, 0xa402, 0x9699, 0x8710, 0xf3af, 0xe226, 0xd0bd, 0xc134, 
	0x39c3, 0x284a, 0x1ad1, 0x0b58, 0x7fe7, 0x6e6e, 0x5cf5, 0x4d7c, 
	0xc60c, 0xd785, 0xe51e, 0xf497, 0x8028, 0x91a1, 0xa33a, 0xb2b3, 
	0x4a44, 0x5bcd, 0x6956, 0x78df, 0x0c60, 0x1de9, 0x2f72, 0x3efb, 
	0xd68d, 0xc704, 0xf59f, 0xe416, 0x90a9, 0x8120, 0xb3bb, 0xa232, 
	0x5ac5, 0x4b4c, 0x79d7, 0x685e, 0x1ce1, 0x0d68, 0x3ff3, 0x2e7a, 
	0xe70e, 0xf687, 0xc41c, 0xd595, 0xa12a, 0xb0a3, 0x8238, 0x93b1, 
	0x6b46, 0x7acf, 0x4854, 0x59dd, 0x2d62, 0x3ceb, 0x0e70, 0x1ff9, 
	0xf78f, 0xe606, 0xd49d, 0xc514, 0xb1ab, 0xa022, 0x92b9, 0x8330, 
	0x7bc7, 0x6a4e, 0x58d5, 0x495c, 0x3de3, 0x2c6a, 0x1ef1, 0x0f78
);

/* change this to your own host / port */
server_loop($ip, $port);

$gpsdata = array();
$txinfosn = 1;

/**
  * Become a daemon by forking and closing the parent
  */
function become_daemon()
{
    $pid = pcntl_fork();
   
    if ($pid == -1)
    {
        /* fork failed */
        echo "fork failure!\n";
        exit();
    }elseif ($pid)
    {
        /* close the parent */
        exit();
    }else
    {
        /* child becomes our daemon */
        posix_setsid();
        chdir('/');
        umask(0);
        return posix_getpid();

    }
} 

/**
  * Change the identity to a non-priv user
  */
function change_identity( $uid, $gid )
{
    if( !posix_setgid( $gid ) )
    {
        print "Unable to setgid to " . $gid . "!\n";
        exit;
    }

    if( !posix_setuid( $uid ) )
    {
        print "Unable to setuid to " . $uid . "!\n";
        exit;
    }
}

/**
  * Creates a server socket and listens for incoming client connections
  * @param string $address The address to listen on
  * @param int $port The port to listen on
  */
function server_loop($address, $port)
{
    GLOBAL $__server_listening;

    if(!$sock = socket_create(AF_INET, SOCK_STREAM, 0))
    {
        echo "failed to create socket: ".socket_strerror($sock)."\n";
        exit();
    }

    if(!$ret = socket_bind($sock, $address, $port))
    {
        echo "failed to bind socket: ".socket_strerror($ret)."\n";
        exit();
    }

    if(!$ret = socket_listen( $sock, 0 ))
    {
        echo "failed to listen to socket: ".socket_strerror($ret)."\n";
        exit();
    }

    socket_set_nonblock($sock);
   
    if ($GLOBALS['foreground'] == true) { echo "waiting for clients to connect\n"; }

    while ($__server_listening)
    {
        $connection = @socket_accept($sock);
        if ($connection === false)
        {
            usleep(100);
        }elseif ($connection > 0)
        {
            handle_client($sock, $connection);
        }else
        {
            echo "error: ".socket_strerror($connection);
            die;
        }
    }
}

/**
  * Signal handler
  */
function sig_handler($sig)
{
    switch($sig)
    {
        case SIGTERM:
        case SIGINT:
            //exit();
        break;

        case SIGCHLD:
            pcntl_waitpid(-1, $status);
        break;
    }
}

/**
  * Handle a new client connection
  */
function handle_client($ssock, $csock)
{
    GLOBAL $__server_listening;

    if ($GLOBALS['foreground'] == false) {
	$pid = pcntl_fork();
    }

    if ($GLOBALS['foreground'] == false && $pid == -1)
    {
        /* fork failed */
        echo "fork failure!\n";
        die;
    }elseif ($GLOBALS['foreground'] == true || $pid == 0)
    {
        /* child process */
        $__server_listening = false;
        socket_close($ssock);
        interact($csock);
        socket_close($csock);
    }else
    {
        socket_close($csock);
    }
}

function interact($socket)
{
	global $gpsdata;
	while (true) {
		socket_recv($socket, $rec, 3, 0);
		var_dump($rec);
		
		
	}
}

function handle_login($datas, $socket) {
	global $gpsdata;
	$imei = "";
	for ($i = 0; $i < 8; $i++) {
		$imei .= bin2hex($datas[$i]);
	}
	$gpsdata['imei'] = (int)$imei;
	$gpsdata['type_identity_code'] = bin2hex($datas[8]).bin2hex($datas[9]);

	$extbit = bin2string(bin2hex($datas[10])).bin2string(bin2hex($datas[11]));
	//echo "extbit: $extbit (0x".bin2hex($datas[10])." 0x".bin2hex($datas[11]).")\n";
	$gpsdata['language'] = hexdec(strbin2hex($extbit[14].$extbit[15]));
	$gpsdata['east_west_timezone'] = $extbit[12] == 0 ? 'E' : 'W';
	$gpsdata['timezone'] = hexdec(bin2hex($datas[10]).($datas[11] & 0xF0));
	reply($socket, '01');
}

function handle_gps($datas, $socket) {
	global $gpsdata;
	// TODO: Really handle GPS ...
	reply($socket, '11');
}

function handle_extended_lsb($datas, $socket) {
	global $gpsdata;
	// TODO: Really handle LSB ...
	$gpsdata['datetime'] = binhex2($datas[0]).binhex2($datas[1]).binhex2($datas[2]).binhex2($datas[3]).binhex2($datas[4]).binhex2($datas[5]);
	$gpsdata['mcc'] = hexdec(bin2hex($datas[6].$datas[7]));
	$gpsdata['mnc'] = hexdec(bin2hex($datas[8]));
	$gpsdata['lac'] = hexdec(bin2hex($datas[9].$datas[10]));
	$gpsdata['mci'] = hexdec(bin2hex($datas[11].$datas[12].$datas[13].$datas[14]));
	$gpsdata['rssi'] = hexdec(bin2hex($datas[15]));
	$mysqli = new mysqli($GLOBALS["mysql_host"], $GLOBALS["mysql_user"], $GLOBALS["mysql_passwd"], $GLOBALS["mysql_database"]);
	$stmt = $mysqli->prepare("INSERT INTO  extended_lsb (`date`, `imei`, `mcc`, `mnc`, `lac`, `mci`, `rssi`) VALUES (?,  ?,  ?,  ?,  ?,  ?,  ?)");
	$stmt->bind_param("sssssss", date("ymdHis"), $gpsdata['imei'], $gpsdata['mcc'], $gpsdata['mnc'], $gpsdata['lac'], $gpsdata['mci'], $gpsdata['rssi']);
	$stmt->execute();
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	if ($GLOBALS['foreground'] == true) { print_r($gpsdata); }
	reply($socket, '11');
}

function handle_status($datas, $socket) {
	global $gpsdata;
	// TODO: Really handle status ...
	$di = $datas[0];
	$gpsdata['voltage'] = hexdec(bin2hex($datas[1]));
	$gpsdata['gsm_signal'] = hexdec(bin2hex($datas[1]));
	if ($GLOBALS['foreground'] == true) { print_r($gpsdata); }
	$mysqli = new mysqli($GLOBALS["mysql_host"], $GLOBALS["mysql_user"], $GLOBALS["mysql_passwd"], $GLOBALS["mysql_database"]);
	$stmt = $mysqli->prepare("INSERT INTO  status (`date`, `imei`, `voltage`, `gsm_signal`) VALUES (?,  ?,  ?,  ?)");
	$stmt->bind_param("ssss", date("ymdHis"), $gpsdata['imei'], $gpsdata['voltage'], $gpsdata['gsm_signal']);
	$stmt->execute();
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	reply($socket, '13');
}

function handle_lsb($datas, $socket) {
	global $gpsdata;
	// TODO: Really handle LSB ...
	$gpsdata['datetime'] = binhex2($datas[0]).binhex2($datas[1]).binhex2($datas[2]).binhex2($datas[3]).binhex2($datas[4]).binhex2($datas[5]);
	$gpsdata['mcc'] = hexdec(bin2hex($datas[6].$datas[7]));
	$gpsdata['mnc'] = hexdec(bin2hex($datas[8]));
	$gpsdata['lac'] = hexdec(bin2hex($datas[9].$datas[10]));
	$gpsdata['ci'] = hexdec(bin2hex($datas[11].$datas[12].$datas[13].$datas[14]));
	if ($GLOBALS['foreground'] == true) { print_r($gpsdata); }
	$mysqli = new mysqli($GLOBALS["mysql_host"], $GLOBALS["mysql_user"], $GLOBALS["mysql_passwd"], $GLOBALS["mysql_database"]);
	$stmt = $mysqli->prepare("INSERT INTO  lsb (`date`, `imei`, `mcc`, `mnc`, `lac`, `ci`) VALUES (?,  ?,  ?,  ?,  ?,  ?)");
	$stmt->bind_param("ssssss", date("ymdHis"), $gpsdata['imei'], $gpsdata['mcc'], $gpsdata['mnc'], $gpsdata['lac'], $gpsdata['ci']);
	$stmt->execute();
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	reply($socket, '18');
}

function handle_gps_extended_lsb($datas, $socket) {
	global $gpsdata;
	$gpsdata['datetime'] = binhex2($datas[0]).binhex2($datas[1]).binhex2($datas[2]).binhex2($datas[3]).binhex2($datas[4]).binhex2($datas[5]);
	$data_length = $datas[6];
	$gpsdata['latitude'] = hexdec(bin2hex($datas[7].$datas[8].$datas[9].$datas[10])) / 30000 / 60;
	$gpsdata['longitude'] = hexdec(bin2hex($datas[11].$datas[12].$datas[13].$datas[14])) / 30000 / 60;
	$gpsdata['speed'] = hexdec(bin2hex($datas[15]));
	$l = $datas[16];
	$r = $datas[17];
	$gpsdata['direction'] = hexdec(bin2hex(($l & 0x03).$r));
	$gpsdata['north_south_latitude'] = ($l & 0x04 == 0x04) ? 'N' : 'S';
	$gpsdata['east_west_latitude'] = ($l & 0x08 == 0x08) ? 'W' : 'E';
	$gpsdata['gps_location_fixed'] = ($l & 0x10 == 0x10) ? 1 : 0;
	$gpsdata['realtime_gps'] = ($l & 0x20 == 0x20) ? 0 : 1;
	if ($GLOBALS['foreground'] == true) { print_r($gpsdata); }
	$mysqli = new mysqli($GLOBALS["mysql_host"], $GLOBALS["mysql_user"], $GLOBALS["mysql_passwd"], $GLOBALS["mysql_database"]);
	$stmt = $mysqli->prepare("INSERT INTO  gps (`date`, `imei` ,`latitude` ,`longitude` ,`speed` ,`direction` ,`type_identity_code` ,`language` ,`east_west_timezone` ,`timezone` ,`north_south_latitude` ,`east_west_latitude` ,`gps_location_fixed` ,`realtime_gps`) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )");
	$stmt->bind_param("ssssssssssssss", $gpsdata['datetime'], $gpsdata['imei'], $gpsdata['latitude'], $gpsdata['longitude'], $gpsdata['speed'], $gpsdata['direction'], $gpsdata['type_identity_code'], $gpsdata['language'], $gpsdata['east_west_timezone'], $gpsdata['timezone'], $gpsdata['north_south_latitude'], $gpsdata['east_west_latitude'], $gpsdata['gps_location_fixed'], $gpsdata['realtime_gps']);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	reply($socket, '1e');
}

function handle_command_response($datas, $socket) {
	global $gpsdata;
	// TODO: Really handle command response ...
	//echo "$datas\n";
	reply($socket, '80');
}

function reply($socket, $datas) {
	global $txinfosn;
	$datas .= str_replace(' ', '0', sprintf('%4d',dechex($txinfosn)));
	$len = strlen($datas) - 1;
	$len = str_replace(' ', '0', sprintf('%2d',$len));
	$reply = '7878'.$len.$datas.crc_itu($len.$datas, true).'0d0a';
	$reply = pack('H*', $reply);
	socket_write($socket, $reply, strlen($reply));
	$txinfosn++;
}

function binhex2($datas) {
	return str_replace(' ', '0',sprintf('%2d',hexdec(bin2hex($datas))));
}

// Grabed on http://www.php.net/manual/en/function.bin2hex.php
function bin2string($bin) { 
    $res = ""; 
    for($p=7; $p >= 0; $p--) { 
      $res .= ($bin & (1 << $p)) ? "1" : "0"; 
    } 
    return $res; 
} 

// Grabed on http://www.php.net/manual/en/function.bin2hex.php
function strbin2hex($bin, $pad=false, $upper=false){
  $last = strlen($bin)-1;
  $x = 0;
  for($i=0; $i<=$last; $i++){ $x += $bin[$last-$i] * pow(2,$i); }
  $x = dechex($x);
  if($pad){ while(strlen($x) < intval(strlen($bin))/4){ $x = "0$x"; } }
  if($upper){ $x = strtoupper($x); }
  return $x;
}

function crc_itu($datas, $alreadyhex = false)
{
	global $crctab16;
	if ($alreadyhex) {
		$datas_hex = pack('H*',$datas);
	} else {
      		$datas_hex = pack('H*',bin2hex($datas));
	}
        $len = strlen($datas_hex);
        $fsc = 0xFFFF;
        $i = 0;
        while($len > 0) {
            $len--;
            $fsc = ($fsc >> 8) ^ $crctab16[($fsc ^ ord($datas_hex[$i++])) & 0xFF];
        }
        return substr(dechex(~$fsc),-4);
}

function showrec($rec) {
	echo "$rec (".strlen($rec).")\n";
	for($i = 0; $i < strlen($rec); $i++) {
	        echo "$i 0x".bin2hex($rec[$i])." (".hexdec(bin2hex($rec[$i])).")\n";
	}

}


?>