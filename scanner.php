#!/usr/bin/php
<?php
declare(ticks=1);

// SCANNER FUNCTIONS
function scanner() {
	$output="original/output_".date("YmdHis").".tif";
	mypassthru("scanimage -d dsseries:usb:0x04F9:0x60E0 --format tiff --mode Color --resolution 600 > $output");
	if(!filesize($output)) {
		unlink($output);
		return "";
	}
	return $output;
}

function arreglar($file1) {
	// CONVERTIR A JPG Y RECORTAR AREA NEGRA
	$file2=str_replace("original/","destino/",$file1);
	$file2=str_replace(".tif",".jpg",$file2);
	if(file_exists($file2)) unlink($file2);
	$size=getimagesize($file1);
	$size[0]-=2;
	$crop=$size[0]."x".$size[1];
	mypassthru("convert $file1 -crop $crop -fuzz 10% -trim -quality 100 $file2");
	// ARREGLAR EL PROBLEMA QUE EN OCASIONES HAY MAS DE UN FICHERO
	$file3=str_replace(".jpg","-*.jpg",$file2);
	$temp=glob($file3);
	if(count($temp)>0) {
		$temp=array_flip($temp);
		foreach($temp as $temp2=>$temp3) $temp[$temp2]=filesize($temp2);
		$temp=array_flip($temp);
		ksort($temp,SORT_NUMERIC);
		$file3=array_pop($temp);
		rename($file3,$file2);
		foreach($temp as $temp2) unlink($temp2);
	}
	// CONVERTIR A PDF Y MINIMIZAR
	$file3=str_replace(".jpg","_tmp.pdf",$file2);
	if(file_exists($file3)) unlink($file3);
	mypassthru("convert $file2 $file3");
	$file4=str_replace(".jpg",".pdf",$file2);
	if(file_exists($file4)) unlink($file4);
	mypassthru("gs -sDEVICE=pdfwrite -dPDFSETTINGS=/ebook -dPrinted=false -dNOPAUSE -dBATCH -dSAFER -sOutputFile=$file4 $file3");
	if(file_exists($file3)) unlink($file3);
	// RETORNAR FILE2 Y FILE4
	return array($file2,$file4);
}

function preview($file2) {
	mypassthru("killall geeqie");
	mypassthru("geeqie $file2 1>/dev/null 2>/dev/null &");
}

// SYSTEM FUNCTIONS
function mypassthru($command) {
	echo $command."\n";
	ob_start();
	passthru($command);
	$buffer=trim(ob_get_clean());
	echo $buffer."\n";
	return $buffer;
}

function usleep_protected($usec) {
	$socket=socket_create(AF_UNIX,SOCK_STREAM,0);
	$read=null;
	$write=null;
	$except=array($socket);
	$time1=microtime(true);
	@socket_select($read,$write,$except,intval($usec/1000000),intval($usec%1000000));
	$time2=microtime(true);
	return ($time2-$time1)*1000000;
}

// HANDLER FUNCTIONS
function __shutdown_handler() {
	global $QUIT;
	$QUIT=1;
}

function __signal_handler($signo) {
	global $QUIT;
	$QUIT=1;
}

// REGISTER HANDLERS
pcntl_signal(SIGTERM,"__signal_handler");
pcntl_signal(SIGINT,"__signal_handler");
register_shutdown_function("__shutdown_handler");

// BEGIN
chdir(dirname(__FILE__));

// CHECK DEPENDENCIES
$commands=array("scanimage","convert","gs","killall","geeqie");
foreach($commands as $command) {
	$buffer=mypassthru("which ${command}");
	if(!file_exists($buffer) || !is_executable($buffer)) {
		echo "Unsatisfied dependency ${command}!!!\n";
		die();
	}
}

// CHECK DIRECTORIES
$dirs=array("original","destino");
foreach($dirs as $dir) {
	if(!file_exists($dir) || !is_dir($dir)) {
		echo "Directory ${dir} not found!!!\n";
		die();
	}
}

//~ // LOOP FOR CONVERT ALL ORIGINAL FILES TO DESTINO FILES
//~ foreach(glob("original/*.tif") as $file1) {
	//~ list($file2,$file3)=arreglar($file1);
	//~ preview($file2);
//~ }
//~ die();

// MAIN LOOP, YOU CAN BREAK IT BY CTRL+C
for(;;) {
	$file1=scanner();
	if($file1!="") {
		list($file2,$file3)=arreglar($file1);
		preview($file2);
	} else {
		usleep_protected(1000000);
	}
	if(isset($QUIT)) break;
}
mypassthru("clear");

?>