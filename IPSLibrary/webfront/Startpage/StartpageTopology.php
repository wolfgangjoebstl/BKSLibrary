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
		
		<link rel="stylesheet" type="text/css" href="/user/Startpage/StartpageTopology.css" />

		<script type="text/javascript" src="jquery.min.js"></script>
		<script type="text/javascript" src="StartpageTopology.js" ></script>
		<script type="text/javascript" charset="ISO-8859-1" >
			function trigger_button(action, module, info) {
				var id         = $(this).attr("id");

				$.ajax({type: "POST",
						url: "/user/Startpage/StartpageTopology_Receiver.php",
						data: "id="+id+"&action="+action+"&module="+module+"&info="+info});
			}

			function trigger_button2(action, module, info) {
				var id                    = $(this).attr("id");
				var WFC10Enabled          = $("#WFC10Enabled").is(':checked');
				var WFC10TabPaneExclusive = $("#WFC10TabPaneExclusive").is(':checked');
				var WFC10Path             = $("#WFC10Path").val();
				var WFC10ID               = $("#WFC10ID").val();
				var WFC10TabPaneParent    = $("#WFC10TabPaneParent").val();
				var WFC10TabPaneItem      = $("#WFC10TabPaneItem").val();
				var WFC10TabPaneIcon      = $("#WFC10TabPaneIcon").val();
				var WFC10TabPaneName      = $("#WFC10TabPaneName").val();
				var WFC10TabPaneOrder     = $("#WFC10TabPaneOrder").val();
				var WFC10TabItem          = $("#WFC10TabItem").val();
				var WFC10TabIcon          = $("#WFC10TabIcon").val();
				var WFC10TabName          = $("#WFC10TabName").val();
				var WFC10TabOrder         = $("#WFC10TabOrder").val();
	
				var MobileEnabled         = $("#MobileEnabled").is(':checked');
				var MobilePath            = $("#MobilePath").val();
				var MobilePathIcon        = $("#MobilePathIcon").val();
				var MobilePathOrder       = $("#MobilePathOrder").val();
				var MobileName            = $("#MobileName").val();
				var MobileIcon            = $("#MobileIcon").val();
				var MobileOrder           = $("#MobileOrder").val();

				$.ajax({type: "POST",
						url: "/user/Startpage/StartpageTopology_Receiver.php",
						contentType:"application/x-www-form-urlencoded; charset=ISO-8859-1",
						data: "id="+encodeURIComponent(id)
						       +"&action="+encodeURIComponent(action)
						       +"&module="+encodeURIComponent(module)
						       +"&info="+encodeURIComponent(info)+
						       +"&WFC10Enabled="+encodeURIComponent(WFC10Enabled)
						       +"&WFC10TabPaneExclusive="+encodeURIComponent(WFC10TabPaneExclusive)
						       +"&WFC10Path="+encodeURIComponent(WFC10Path)
						       +"&WFC10ID="+encodeURIComponent(WFC10ID)
						       +"&WFC10TabPaneParent="+encodeURIComponent(WFC10TabPaneParent)
						       +"&WFC10TabPaneItem="+encodeURIComponent(WFC10TabPaneItem)
						       +"&WFC10TabPaneIcon="+encodeURIComponent(WFC10TabPaneIcon)
						       +"&WFC10TabPaneName="+encodeURIComponent(WFC10TabPaneName)
						       +"&WFC10TabPaneOrder="+encodeURIComponent(WFC10TabPaneOrder)
						       +"&WFC10TabItem="+encodeURIComponent(WFC10TabItem)
						       +"&WFC10TabIcon="+encodeURIComponent(WFC10TabIcon)
						       +"&WFC10TabName="+encodeURIComponent(WFC10TabName)
						       +"&WFC10TabOrder="+encodeURIComponent(WFC10TabOrder)
						       +"&MobileEnabled="+encodeURIComponent(MobileEnabled)
						       +"&MobilePath="+encodeURIComponent(MobilePath)
						       +"&MobilePathIcon="+encodeURIComponent(MobilePathIcon)
						       +"&MobilePathOrder="+encodeURIComponent(MobilePathOrder)
						       +"&MobileName="+encodeURIComponent(MobileName)
						       +"&MobileIcon="+encodeURIComponent(MobileIcon)
						       +"&MobileOrder="+encodeURIComponent(MobileOrder)
						});
						
			}


		</script>

	</head>
	<body >
		<!--
		<a href="#" onClick=trigger_button('View1','','')>View1</a> |
		<a href="#" onClick=trigger_button('View2','','')>View2</a> |
		<a href="#" onClick=trigger_button('View3','','')>View3</a> |
		<a href="#" onClick=trigger_button('View4','','')>View4</a> |
		<a href="#" onClick=trigger_button('View5','','')>View5</a> -->
		<?php
			IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
			IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
			IPSUtils_Include ("Startpage_Include.inc.php", "IPSLibrary::app::modules::Startpage");
			IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

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
				
			$style =  "";
			$style .= '.container-startpage { box-sizing: border-box; display:grid; grid-template-columns: 9fr 1fr 1fr 2fr; 
												grid-template-rows:  repeat('.$lines.',auto);';
			$style .= '                         grid-template-areas:';
			$style .= '                             "picture cmd  cmd  cmd "'."\n"; 
			$style .= '                             "picture icon icon ."'; 
			for ($i=0;($i<$tempLine);$i++) 
				  $style .= '                       "picture span'.$i.' span'.$i.' ."';

			switch ($addLine)
				{
				case -1:
					$style .= '                     "picture add0 add0 add0"'; 
					break;
				case 0:
					break;
				case ($addLine<4):
					for ($i=0;($i<$addLine);$i++) 
						$style .= '                 "picture add'.$i.' add'.$i.' ."'; 
					break;
				default:
					echo "Anzahl addLines $addLine kenn ich nicht.\n";
					break;
				}
			$style .= '                             "picture . . ." 
													"picture . . ." 
													"picture . . ." 
													"picture . . ."';
			for ($i=0;($i<$itemLine);$i++)                                        
				$style .= '                         "bottom bottom bottom bottom"';
			$style .= '                             "info info info info";}'."\n";										
                                          
			$style .= '.container-bottomline { display:inline-grid; grid-template-columns: repeat('.$maxItems.',auto); 
                                        grid-template-rows: auto;}';
		    $style .= '.container-picture { display:inline-grid; grid-template-columns: repeat('.$maxPicCols.',auto); 
                                        grid-template-rows: repeat('.$maxPicLines.',auto);}'."\n";
										
			//$style .= '.container-picture1 { display:inline-grid; grid-template-columns: auto; grid-template-rows: auto;"}'."\n";
			$style .= '.container-picture1 { display:flex;}'."\n";
			$style .= '.container-picture2 { display:inline-grid; grid-template-columns: 1fr 1fr 1fr; 
                                        grid-template-rows: auto auto;
                                        grid-template-areas:  "area1 area1 area2"
                                                              "area1 area1 area2";}'."\n";
			$wert = "";
			$wert.= $startpage->writeStartpageStyle($style);			
			
			$wert.= '<div id="sp" class="container-startpage">';            // display type Table
			$wert.=     '<div id="sp-pic" style="grid-area:picture">';                 // display type Cell
			$wert.='	    <div id="sp-pic-grid" class="container-picture" style="width:100%;">';
			$wert.=             '<div id="sp-pic-grid-left" style="grid-area:area1">';                 // display type Cell picture
			$wert .=        		$startpage->showPictureWidgetResponsive(9);					// amount of pictures, you can switch
			$wert.=                 '</div>';
			$wert.=             '<div id="sp-pic-grid-right" style="grid-area:area2; display:none">';                 // display type Cell picture
			$wert .=                $startpage->inclOrfWeather();
			$wert.=                 '</div>';			
			$wert.=         	'</div>';
			$wert.=         '</div>';
			$wert.=     '<div id="sp-cmd" class="container-cmd" style="grid-area:cmd; ">';                 // display type Cell cmd
			$wert .=             $startpage->commandLineResponsive();
			$wert.=         '</div>';			
			$wert .=    $startpage->showTemperatureTableIcons();
			$wert .=    $startpage->showTemperatureTableValues();
			$wert .=    $startpage->additionalTableLinesResponsive();
			$wert .=    $startpage->showWeatherTableResponsive();
			$wert.='	<div id="sp-bot" style="grid-area:bottom; background-color:#7f8f9f">';
			//$wert.='	    <div id="sp-bot-grid" class="container-bottomline" style="background-color:#4f3f3f; width:100%;">';
			$wert .=             $startpage->bottomTableLinesResponsive();
			//$wert.=             '</div>';    
			$wert.=         '</div>'; 
			$wert.='	<div id="sp-inf" style="grid-area:info; background-color:#7f6f5f">';    
			$wert .=             $startpage->infoTableLinesResponsive(["ID"=>$identifier]);  			
			$wert.=         '</div>';  			
			$wert.='    </div>';          
			echo $wert;
			//echo $startpage->StartPageWrite(1);
			
			/*$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
			$action  = GetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_ACTION, $baseId));
			$module  = GetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_MODULE, $baseId));
			$info    = GetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_INFO,   $baseId));
			switch($action) {
				case 'View1':
					echo 'Hallo, hier ist View1 mit Temperaturwerten -check<br> ';
					$Werte=IPS_GetChildrenIDs(22334);
					foreach ($Werte as $Wert) echo "   ".$Wert."  ".IPS_GetName($Wert)."   ".GetValue($Wert)."<br>";
					SetValue(35191,"View1 gedrueckt");
					break;
				case 'View2':
					echo "Hallo, hier ist View2 mit Bewegungswerten <br>";
					$Werte=IPS_GetChildrenIDs(22823);
					foreach ($Werte as $Wert) echo "   ".$Wert."  ".IPS_GetName($Wert)."   ".(GetValue($Wert)?"Ein":"Aus")."<br>";	
					SetValue(35191,"View2 gedrueckt");
					break;
				case 'View3':
					echo "Hallo, hier ist View3 mit den installierten Modulen <br>";					
					IPSUtils_Include ("IPSModuleManager.class.php", "IPSLibrary::install::IPSModuleManager");
					$moduleManager = new IPSModuleManager();
					$modules         = $moduleManager->GetInstalledModules();
					foreach ($modules as $name => $module) echo "   ".$name."  ".$module."<br>";					
					break;
				case 'View4':
					echo "Hallo, hier ist View4 mit der aktuellen Uhrzeit:<br>";
					echo 'Es ist heute '.date("D.m.Y H:i:s").' Refresh ?';  					
					break;
				case 'View5':
					echo "Hallo, hier ist View5";				
					break;
				default:
					trigger_error('Unknown Action '.$action);
			}  */
		?>
	</body>
</html>


