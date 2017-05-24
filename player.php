#!/usr/bin/php -q
<?php
/**
 * player.php: Alterado para o ROLDAO
*/
include "config.inc.php";
include "config_entrada.php";

$mode=0777;

//verifica se vai tocar streaming
if($streaming == true)
{
    $in_stream="/tmp/stream_in";

    $out_stream="/tmp/stream_out";

    if(!file_exists($in_stream)) 
    {
      umask(0);
      posix_mkfifo($in_stream,$mode);
    }

    if(!file_exists($out_stream)) exec("touch $out_stream");

    exec("mplayer -slave -nolirc -softvol -idle -quiet -noautosub -volume ".$volumeMusical." -cache 100 -input file=$in_stream 2>&- 1> $out_stream &"); //abre mplayer q toca stream

    comando("loadfile $urlStream",$in_stream);

    //executa script q monitora se o streaming está tocando
    $arquivo = __DIR__.DIRECTORY_SEPARATOR."monitora_streaming.sh";
    exec("$arquivo $urlStream > /dev/null &");

}
else
{
    $comando = "amixer set Line ".$volumeMusical."%";
    exec($comando);
}


include "playlist-comerciais.php";

exec("truncate -s0 /var/www/comerciais.txt");
foreach ($comerciais as $key => $comercial) {
    # code...
    $c = $comercial->inicio." - ".join(',', $comercial->arquivo)."\r\n";
    exec("echo '$c' >> /var/www/comerciais.txt");
}
//$c = json_encode($comerciais);
//exec("echo '$c' > /var/www/comerciais.txt");


mysql_connect("localhost", "root", ""); // host, usuario e senha
mysql_select_db("radio");                  // DB

$hora = localtime();
$tempo = $hora[0] + $hora[1] * 60 + $hora[2] * 3600;

$arquivos = mysql_query("SELECT hora_inicio FROM playlists
					   WHERE hora_inicio < '$tempo'");   // iniciar
$proximo = mysql_num_rows($arquivos)+1;	// Quantos arquivos + 1?

$arquivos = mysql_query("SELECT hora_inicio FROM playlists");   // todos
mysql_data_seek($arquivos, $proximo);	// Vai para o proximo arquivo
$arquivo = mysql_fetch_object($arquivos);   // Seleciona um arquivo

// Gera o log, carrega a playlist
exec("/var/www/crialog.php");

$in_comerciais="/tmp/comerciais_in";

$out_comerciais="/tmp/comerciais_out";

$playlist_mute = false;


if(!file_exists($in_comerciais)) 
{
  umask(0);
  posix_mkfifo($in_comerciais,$mode);
}

if(!file_exists($out_comerciais)) exec("touch $out_comerciais");

exec("mplayer -slave -nolirc -softvol -idle -quiet -noautosub -volume ".$volumeComercial." -input file=$in_comerciais 2>&- 1> $out_comerciais &"); //abre mplayer q toca comerciais

//executa scripts q gera log
exec("/var/www/inicia_log.sh > /dev/null &");


// Seleciona eventos validos para o dia e para o horario
$eventos_sql = mysql_query("SELECT arquivo,hora_inicio,tempo,hora_inicio as inicio FROM
	arquivos WHERE rede='$rede' AND loja='$loja' AND
	tipo='comercial' AND genero='Eventos'
	AND CURDATE() >= data_inicio AND CURDATE() <= data_fim AND
	FIND_IN_SET(DAYOFWEEK(CURDATE()),dia_semana) ORDER BY hora_inicio,arquivo");


while ($evento = mysql_fetch_object($eventos_sql)) 
{
    $segundos = $evento->inicio % 60;
    $minutos = floor($evento->inicio / 60) % 60;
    $hora = floor($evento->inicio / 3600);

    $c = new StdClass();
    $c->arquivo[] = "/usr/local/radio/generos/comercial/Eventos/".$evento->arquivo;
    $c->tempo = $evento->tempo;
    $c->inicio = $hora.':'.$minutos.':'.$segundos;
    $comerciais[$evento->inicio] = $c;

    //verifica se tem algum comercial no mesmo time do evento e remove se tiver
    $chaves = array_keys($comerciais);
    sort($chaves);
    foreach ($chaves as $key => $chave) {
        if($chave == $evento->inicio){
            //print_r($chaves[$key]);
            //remove depois do evento
            for ($i=$key+1; $i < count($chaves); $i++) { 
                $ic = $chaves[$i];
                $depois = $evento->inicio + $c->tempo + 5;
                if($ic <= $depois){
                   //echo $ic."\r\n";
                   unset($comerciais[$ic]);
                }
            }
            //remove antes do evento
            for ($i=$key-1; $i > 0; $i--) { 
                
                $ic = $chaves[$i];
                $c = $comerciais[$ic];
                $intervalo = $ic + $c->tempo + 5;
                if($intervalo >= $evento->inicio){
                    //echo $chaves[$i]."\r\n";
                    unset($comerciais[$ic]);
                }
            }
        }
    }
}



//echo segundos2hora($tempo)."\r\n";

while (true) 
{
	$hora = localtime();
	$tempo = $hora[0] + $hora[1] * 60 + $hora[2] * 3600;


    //print_r($comerciais);
    echo segundos2hora($tempo)." | ".$tempo."\r\n";
    if(isset($comerciais[$tempo]))
    {
        print_r($comerciais[$tempo]);
        echo "tocando comercial\r\n";
        if($streaming == true)
        {
            exec("/var/www/volume_menos.php > /dev/null &");//diminui volume
        }
        else
        {
            exec("/var/www/volume_menos_input.php > /dev/null &");//diminui volume da entrada
        }
        sleep(2);
        $comercial = $comerciais[$tempo];
        $comercial_pl = "";
        foreach ($comercial->arquivo as $value) {
            $comercial_pl.= $value."\n";
        }

        exec("echo '$comercial_pl' > /var/www/comerciais.m3u"); // pl com o evento

        comando("loadlist /var/www/comerciais.m3u",$in_comerciais);// toca evento
        sleep($comercial->tempo);
        echo "fim do comercial\r\n";
        
        if($streaming == true)
        {
            exec("/var/www/volume_mais.php > /dev/null &");//aumenta volume
        }
        else
        {
            exec("/var/www/volume_mais_input.php > /dev/null &");//aumenta volume da entrada
        }
    }
    else{
        sleep(1);//ciclo de um segundo
    }
    
}

function segundos2hora($segundos)
{
    return floor($segundos/3600).":".floor(($segundos%3600)/60).":".$segundos%60;
}

function comando($_cmd, $arquivo)
{
	$cmd = "echo '".$_cmd."' > $arquivo";
	exec($cmd);
    //loadlist /var/www/mplayer.m3u
    ///tmp/comerciais_in
}


// Libera variaveis e desconecta do banco de dados
mysql_free_result($eventos);
mysql_close();

?>
