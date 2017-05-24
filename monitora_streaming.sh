#!/bin/bash

#VERSAO004


mplayer_out=/tmp/stream_out
mplayer_in=/tmp/stream_in

path=""
if [ "$1" != "" ]; then
	path="$1"
	echo $path
else
	exit -1
fi

# verifica se o arquivo existe
while [ ! -f "$mplayer_out" ]
do
	sleep 1
done

# verifica se mplayer esta tocando
while [ $? ]; do
	echo 'get_property path' > "$mplayer_in"
	temp=`tail -1 $mplayer_out`
	hora=`date +%d/%m/%Y-%H:%M:%S`
	#echo '' > "$mplayer_out"
	echo $temp
	echo $hora
	if [ "$temp" == "ANS_ERROR=PROPERTY_UNAVAILABLE" ] || [ "$temp" == "ANS_path=(null)" ]; then
		echo "ERRO - `date`" >> /usr/local/src/erro-player.txt
		echo "loadfile $path" > "$mplayer_in"
		sleep 3
		echo "play"
	fi
	sleep 3
done
