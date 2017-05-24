#!/usr/bin/php -q
<?php
include "config_entrada.php";
$volume = $volumeMusical;

while ($volume > 0) {
	$volume = $volume - 1;
	$comando = "amixer set Line ".$volume."%";
	exec($cmd);

	usleep(1000);
}
?>