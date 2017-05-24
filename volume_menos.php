#!/usr/bin/php -q
<?php
include "config_entrada.php";
$volume = $volumeMusical;

while ($volume > 0) {
	$volume = $volume - 1;
	$cmd = "echo 'set_property volume ".$volume."' > /tmp/stream_in";
	exec($cmd);

	usleep(1000);
}

?>
