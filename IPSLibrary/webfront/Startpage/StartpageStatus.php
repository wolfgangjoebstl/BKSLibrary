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
	 * test Startpage Output als Station
	 */

	$cookie_name = "identifier-symcon-startpage";
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
		  $str = md5(uniqid('identifier-symcon-startpage-120166', true));
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
		
		<link rel="stylesheet" type="text/css" href="/user/Startpage/StartpageStatus.css" />

		<script type="text/javascript" src="jquery.min.js"></script>
		<script type="text/javascript" src="StartpageStatus.js" ></script>

	</head>
	<body >
	<?php
		IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
		IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
		IPSUtils_Include ("Startpage_Include.inc.php", "IPSLibrary::app::modules::Startpage");
		IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');
		IPSUtils_Include ('Startpage_Update.ips.php', 'IPSLibrary::app::modules::Startpage');
	
		$identifier=false;
		if(!isset($_COOKIE[$cookie_name])) {
		  echo "Cookie named '" . $cookie_name . "' is not set!";
		} else {
			//echo "Cookie '" . $cookie_name . "' is set, Value is: " . $_COOKIE[$cookie_name];
			$identifier = $_COOKIE[$cookie_name];
			}
		
		$startpage = new StartpageWidgets("responsive");
		$debug=false;
		
		$files=$startpage->readPicturedir();
		$maxcount=count($files);
		if ($maxcount>0) $showfile=rand(1,$maxcount-1);
		   
		$maxPicCols=2; $maxPicLines=2;   
		$maxItems=4;   
		$tempLine = $startpage->showTemperatureTableValues(0);
		$addLine=$startpage->additionalTableLinesResponsive(0);
		$items=$startpage->bottomTableLinesResponsive(0);                                       // nur zählen, sonst html5 code ausgeben
		$itemLine=ceil($items/$maxItems);
		$lines=$tempLine+$addLine+7+$itemLine;
												
		$wert = "";
		$wert .= $startpage->getStartpageStyleStatusSize()->writeStartpageStyleStatus()->writeStartpageStyle();			// style einfügen Size an status style an allg style			
		$wert .= '<div id="sp-status" class="container-status">';
        $wert .= $startpage->showDisplayStationResponsive(1);
        $wert .= '</div>';
		echo $wert;	
		?>
	</body>
</html>


