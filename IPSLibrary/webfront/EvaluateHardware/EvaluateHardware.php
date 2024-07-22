<?php 
	/**
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 *
	 */

	/* EvaluateHardware.php
	 *
	 *
	 */

	$cookie_name = "identifier-symcon-evaluatehardware";
	$cookie_value = random_string();
	if(!isset($_COOKIE[$cookie_name])) {
		setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // 86400 = 1 day => 30days
		}
		
	function random_string() {
	   if(function_exists('random_bytes')) {
		  $bytes = random_bytes(16);
		  $str = bin2hex($bytes); 
	   } else if(function_exists('openssl_random_pseudo_bytes')) {
		  $bytes = openssl_random_pseudo_bytes(16);
		  $str = bin2hex($bytes); 
	   } else if(function_exists('mcrypt_create_iv')) {
		  $bytes = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
		  $str = bin2hex($bytes); 
	   } else {
		  //Bitte euer_geheim_string durch einen zufälligen String mit >12 Zeichen austauschen
		  $str = md5(uniqid('identifier-symcon-evaluatehardware-120166', true));
	   }   
	   return $str;
	}
	/**
	
	
	 */

	/** @}*/
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
		<meta http-equiv="Pragma" content="no-cache">
		<meta http-equiv="Expires" content="0">
		<meta name="viewport" content="width=device-width, initial-scale=1" />							<!-- Formatierung  -->
		
		<link rel="stylesheet" type="text/css" href="/user/EvaluateHardware/EvaluateHardware.css" />

		<script type="text/javascript" src="jquery-3.7.1.min.js"></script>
		<script type="text/javascript" src="/user/EvaluateHardware/EvaluateHardware.js" ></script>
		<script type="text/javascript" charset="ISO-8859-1" >
		</script>

	</head>
	<body >
		<?php

    Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
	IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
	
	$ipsOps    = new ipsOps();
    $ipsTables = new ipsTables();               // fertige Routinen für eine Tabelle in der HMLBox verwenden
	$showEvaluateHardware = new showEvaluateHardware();
	
	$identifier=false;
	if(!isset($_COOKIE[$cookie_name])) {
	  echo "Cookie named '" . $cookie_name . "' is not set!";
	} else {
		//echo "Cookie '" . $cookie_name . "' is set, Value is: " . $_COOKIE[$cookie_name];
		$identifier = $_COOKIE[$cookie_name];
		}
	/*
	$inputData=array();
	foreach (IPSDetectDeviceHandler_GetEventConfiguration() as $key => $entry)
		{
		$inputData[$key]=$entry;
		if (IPS_VariableExists($key)) $inputData[$key]["Value"]=GetValueIfFormatted($key);
		}
    $config["text"]    = false;						// kein echo
    $config["insert"]["Header"]    = true;
    $config["insert"]["Index"]    = true;
    $config["html"]    = 'html';    
    $config["display"] = [
                    "Index"                     => ["header"=>"OID","format"=>"OID"],
                    "Name"                      => "ObjectName",
                    "Value"                     => "Wert",					
                    "0"                         => "Modul",
                    "1"                         => "Place",
                    "2"                         => "Type",
                    "3"                         => "Device",
                    "4"                         => "newName",
                ];
    $config["format"]["class-id"]="topy";           // make it short
    $config["format"]["header-id"]="hrow";          // make it short				
    $text = $ipsTables->showTable($inputData, false ,$config, false);                // true Debug

    echo $text;	*/
		echo $showEvaluateHardware->showTableHtml();
		
		?>
	</body>
</html>


