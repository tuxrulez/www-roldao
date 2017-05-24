#!/usr/bin/php -q
<?php
include "config_entrada.php";

$volume = 0;

while ($volume < $volumeMusical) {
	$volume = $volume + 1;
	
	$comando = "amixer set Line ".$volume."%";
	exec($comando);

	usleep(1000);
}
?>