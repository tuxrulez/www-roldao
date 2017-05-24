#!/usr/bin/php -q
<?php
include "config_entrada.php";

$volume = 0;

while ($volume < $volumeMusical) {
	$volume = $volume + 1;
	
	$cmd = "echo 'set_property volume ".$volume."' > /tmp/stream_in";
	exec($cmd);

	usleep(1000);
}
?>