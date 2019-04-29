<?

/********************************************* CONFIG *******************************************************/

ini_set('memory_limit', '-1');
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
IPSUtils_Include ('Startpage.inc.php', 'IPSLibrary::app::modules::Startpage');

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
//IPS_SetScriptTimer($_IPS['SELF'], 8*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */

$configuration=startpage_configuration();
$temperatur=temperatur();
$innentemperatur=innentemperatur();
$bilderverzeichnis=$configuration["Directories"]["Pictures"];
$picturedir=IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\pictures\\";

$StartPageTypeID = CreateVariableByName($parentid, "Startpagetype", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */

$variableIdHTML  = CreateVariable("Uebersicht", 3 /*String*/,  $parentid, 40, '~HTMLBox', null,null,"");

$vid = @IPS_GetVariableIDByName("SwitchScreen",$parentid);

/******************************************* INIT ******************************************************/

/* 
 * Das Archiv für die Fotos ist das bilderverzeichnis, picturedir wird für die Darstellung aus dem Webfront verwendet
 * da eine relative Adressierung auf den Server adressiert und eine absolute Adressierung auf den Client geht.
 *
 */

$file=array();
$handle=opendir ($picturedir);
//echo "Verzeichnisinhalt:<br>";
$i=0;
while ( false !== ($datei = readdir ($handle)) )
	{
	if ($datei != "." && $datei != ".." && $datei != "Thumbs.db") 
		{
        if (is_dir($picturedir.$datei)==true ) 
            {
            //echo "Verzeichnis ".$picturedir.$datei." gefunden.\n";
            }
        else
            {            
		    $i++;
 		    $file[$i]=$datei;
            }
		}
	}
closedir($handle);
$maxcount=count($file);
$showfile=rand(1,$maxcount-1);
//echo $maxcount."  ".$showfile."\n";;
//print_r($file);

if ( is_dir($picturedir."SmallPics") ==  false ) mkdir($picturedir."SmallPics");
$datei=$file[$showfile];

// Get new dimensions
list($width, $height) = getimagesize($picturedir.$datei);
//echo "Resample Picture (".$width." x ".$height.") from ".$picturedir.$datei." to ".$picturedir."SmallPics/".$datei.".\n";

$new_width=1920;
$percent=$new_width/$width;
$new_height = $height * $percent;
if ($new_height > 1080) 
    { 
    //echo "Status zu hoch : ".$new_width."  ".$new_height."   \n";
    $new_height=1080;
    $percent=$new_height/$height;
    $new_width = $width * $percent;
    }
//echo "New Size : (".$new_width." x ".$new_height.").\n";

// Resample
$image_p = imagecreatetruecolor($new_width, $new_height);
$image = imagecreatefromjpeg($picturedir.$datei);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

// Output
imagejpeg($image_p, $picturedir."SmallPics/".$datei, 60);


/**************************************** PROGRAM WEBFRONT *********************************************************/


 if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
    $variableID=$_IPS['VARIABLE'];
    switch ($variableID)          // Value formatted : Explorer Fullscreen Station Picture Topologoe Off
        {
        case ($vid):
        	switch ($_IPS['VALUE'])
		        {
        		case "5":	/* Monitor off/on, Off */
		        	controlMonitor("off",$configuration);
        			break;
                case "4":   /* Topologie, new one with picture drawing of geographical position*/
        			SetValue($StartPageTypeID,3);
					break;
    	        case "3":  	/* Bildschirmschoner, Picture */
        			SetValue($StartPageTypeID,1);
		        	break;
        		case "2":  	/* Wetterstation, Station */
		        	SetValue($StartPageTypeID,2);
        			break;
        		case "1":  	/* Full Screen ein, Fullscreen */
		        case "0":  	/* Full Screen aus, Explorer */
			        controlMonitor("FullScreen",$configuration);
        			//IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, -1);
		        	break;
		        }
            SetValue($variableID,$_IPS['VALUE']);                
            break;

        default:
           	SetValue($variableID,$_IPS['VALUE']);
            break;
        }    

	}

/* wenn OpenWeather installiert ist dieses für die Startpage passend aggregieren, die Werte werden automatisch abgeholt */

aggregateOpenWeather();		

/* mit der Funktion StartPageWrite wird die html Information für die Startpage aufgebaut */

SetValue($variableIdHTML,StartPageWrite(GetValue($StartPageTypeID)));


/**************************************** PROGRAM EXECUTE *********************************************************/

 if ($_IPS['SENDER']=="Execute")
	{
	echo "Execute aufgerufen:\n";
	echo "\nKonfigurationseinstellungen:\n";
	print_r($configuration);

	$pname="StartpageControl";
    echo "Variable SwitchScreen mit Profil \"$pname\" hat OID: $vid \n";
	if (IPS_VariableProfileExists($pname) == true)  //Var-Profil erstellen     
		{
        $profile=IPS_GetVariableProfile($pname)["Associations"];
        foreach ($profile as $index => $profil) echo "  ".$index."  ".$profil["Value"]."  ".$profil["Name"]."\n";
        //print_r($profile);
        }
	echo "Switch on Monitor, look for :".$configuration["Directories"]["Scripts"].'nircmd.exe'."\n"; 
	IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, -1);	
	
	$noweather=true;
	if ( isset ($configuration["Display"]["Weathertable"]) == true ) { if ( $configuration["Display"]["Weathertable"] != "Active" ) { $noweather=false; } }
	if ($noweather == false) { echo "Keine Anzeige der rechten Wettertabelle in der Startpage.\n"; }
	
	echo "Bildanzeige, es gibt insgesamt ".$maxcount." Bilder auf dem angegebenen Laufwerk.\n";
	echo StartPageWrite(1);
	}

/**************************************** FUNCTIONS *********************************************************/

function StartPageWrite($PageType)
	{
	
	global $temperatur, $innentemperatur, $file, $showfile, $configuration, $maxcount;

	/* Wenn Configuration verfügbar und nicht Active dann die rechte Tabelle nicht anzeigen */	
	$Config=configWeather($configuration);
	//print_r($Config);
	$noweather=!$Config["Active"];
	if ($Config["Source"]=="WU")
		{
		$todayID=get_ObjectIDByPath("Program.IPSLibrary.data.modules.Weather.IPSWeatherForcastAT");
		if ($todayID == false)
			{
			//echo "weatherforecast nicht installiert.\n";
			$noweather=true;
			}
		else
			{
			$today = GetValue(@IPS_GetObjectIDByName("TodayIcon",$todayID));
			$todayTempMin = GetValue(@IPS_GetObjectIDByName("TodayTempMin",$todayID));
			$todayTempMax = GetValue(@IPS_GetObjectIDByName("TodayTempMax",$todayID));
			$tomorrow = GetValue(@IPS_GetObjectIDByName("TomorrowIcon",$todayID));
			$tomorrowTempMin = GetValue(@IPS_GetObjectIDByName("TomorrowTempMin",$todayID));
			$tomorrowTempMax = GetValue(@IPS_GetObjectIDByName("TomorrowTempMax",$todayID));
			$tomorrow1 = GetValue(@IPS_GetObjectIDByName("Tomorrow1Icon",$todayID));
			$tomorrow1TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMin",$todayID));
			$tomorrow1TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMax",$todayID));
			$tomorrow2 = GetValue(@IPS_GetObjectIDByName("Tomorrow2Icon",$todayID));
			$tomorrow2TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMin",$todayID));
			$tomorrow2TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMax",$todayID));

			$todayDate="";		/* keine Openweather Darstellung, verwendet als Unterscheidung */
			}
		}
	else			/* nicht Weather Wunderground, daher Openwewather */
		{
		$todayID=get_ObjectIDByPath("Program.IPSLibrary.data.modules.Startpage.OpenWeather");		
		if ($todayID == false)
			{
			//echo "weatherforecast nicht installiert.\n";
			$noweather=true;
			}
		else
			{
			//echo "OpenWeatherData mit Daten von $todayID wird verwendet.\n";
			$todayDate    = GetValue(@IPS_GetObjectIDByName("TodayDay",$todayID));
			$today        = GetValue(@IPS_GetObjectIDByName("TodayIcon",$todayID));
			$todayTempMin = GetValue(@IPS_GetObjectIDByName("TodayTempMin",$todayID));
			$todayTempMax = GetValue(@IPS_GetObjectIDByName("TodayTempMax",$todayID));
			$tomorrowDate = GetValue(@IPS_GetObjectIDByName("TomorrowDay",$todayID));
			$tomorrow     = GetValue(@IPS_GetObjectIDByName("TomorrowIcon",$todayID));
			$tomorrowTempMin = GetValue(@IPS_GetObjectIDByName("TomorrowTempMin",$todayID));
			$tomorrowTempMax = GetValue(@IPS_GetObjectIDByName("TomorrowTempMax",$todayID));
			$tomorrow1Date = GetValue(@IPS_GetObjectIDByName("Tomorrow1Day",$todayID));
			$tomorrow1 = GetValue(@IPS_GetObjectIDByName("Tomorrow1Icon",$todayID));
			$tomorrow1TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMin",$todayID));
			$tomorrow1TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMax",$todayID));
			$tomorrow2Date = GetValue(@IPS_GetObjectIDByName("Tomorrow2Day",$todayID));
			$tomorrow2 = GetValue(@IPS_GetObjectIDByName("Tomorrow2Icon",$todayID));
			$tomorrow2TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMin",$todayID));
			$tomorrow2TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMax",$todayID));
			$tomorrow3Date = GetValue(@IPS_GetObjectIDByName("Tomorrow3Day",$todayID));
			$tomorrow3 = GetValue(@IPS_GetObjectIDByName("Tomorrow3Icon",$todayID));
			$tomorrow3TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow3TempMin",$todayID));
			$tomorrow3TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow3TempMax",$todayID));
			}		
		}
		
	/* html file schreiben, Anfang Style für alle gleich */

	$wert="";
    $wert.= writeStartpageStyle();

	if ( $noweather==true )
		{
        //echo "NOWEATHER true.\n";
		$wert.='<table id="startpage">';
        $wert.='<tr><td>';
   		if ($maxcount >0)
   			{
			$wert.='<img src="user/Startpage/user/pictures/'.$file[$showfile].'" width="67%" height="67%" alt="Heute" align="center">';
			}		
		$wert.='</td></tr></table>';
		}
	else
		{
        if ($PageType==3)           // Topologie
			{
			//echo "Topologiedarstellung fehlt noch.\n";
            }
		elseif ($PageType==2)
			{
			//echo "NOWEATHER false. PageType 2. NoPicture.\n";            	
			$wert.='<table <table border="0" height="220px" bgcolor="#c1c1c1" cellspacing="10"><tr><td>';
			$wert.='<table border="0" bgcolor="#f1f1f1"><tr><td align="center"> <img src="'.$today.'" alt="Heute" > </td></tr>';
			$wert.='<tr><td align="center"> <img src="'.$tomorrow.'" alt="Heute" > </td></tr>';
			$wert.='<tr><td align="center"> <img src="'.$tomorrow1.'" alt="Heute" > </td></tr>';
			$wert.='</table></td><td><img src="user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td><td><strg>'.number_format($temperatur, 1, ",", "" ).'°C</strg></td>';
            $wert.='<td> <table border="0" bgcolor="#ffffff" cellspacing="5" > <tablestyle><tr> <td> <img src="user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur">  </td> </tr>';
            $wert.='<tr> <td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td> </tr></tablestyle> </table> </td></tr>';
            $wert.='</table>';
			}
		else
			{
			/*******************************************
			 *
			 * PageType==1,Diese Art der Darstellung der Startpage wird Bildschirmschoner genannt 
			 * Bild und Wetterstation als zweispaltige Tabelle gestalten
			 *
			 *************************/
			$filename = 'user/Startpage/user/pictures/SmallPics/'.$file[$showfile];
			$filegroesse=number_format((filesize(IPS_GetKernelDir()."webfront/".$filename)/1024/1024),2);
			$info=getimagesize(IPS_GetKernelDir()."webfront/".$filename);
			if (file_exists(IPS_GetKernelDir()."webfront/".$filename)) 
				{
				//echo "Filename vorhanden - Groesse ".$filegroesse." MB.\n";
				}
			//echo "NOWEATHER false. PageType 1. Picture. ".$filename."\n\n";            	                
			$wert.='<table id="startpage">';
			if ($todayDate!="") { $tableSpare='<td bgcolor="#c1c1c1"></td>'; $colspan='colspan="2" '; }
			else { $tableSpare=''; $colspan=""; }
			//$wert.='<tr><th>Bild</th><th>Temperatur und Wetter</th></tr>';  /* Header für Tabelle */
			//$wert.='<td><img id="imgdisp" src="'.$filename.'" alt="'.$filename.'"></td>';
			$wert.='<tr><td><div class="container"><img src="'.$filename.'" alt="'.$filename.'" class="image">';
			$wert.='<div class="middle"><div class="text">'.$filename.'<br>'.$filegroesse.' MB '.$info[3].'</div>';
			$wert.='</div></td>';
			$wert.='<td><table id="nested">';
			$wert.='<tr><td '.$colspan.'bgcolor="#c1c1c1"> <img src="user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>';
			$wert.='<td bgcolor="#ffffff"><img src="user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur"></td></tr>';
			$wert.='<tr><td '.$colspan.' bgcolor="#c1c1c1"><aussen>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
            $wert.= '<tr>'.additionalTableLines($configuration["Display"],$colspan).'</tr>';
//			$wert.='<tr id="temp"><td><temperatur>'.number_format($todayTempMin, 1, ",", "" ).'°C<br>'.number_format($todayTempMax, 1, ",", "" ).'°C</temperatur></td>';
//			$wert.='<td align="center"> <img src="'.$today.'" alt="Heute" > </td></tr>';
//			$wert.='<tr id="temp"><td><temperatur>'.number_format($tomorrowTempMin, 1, ",", "" ).'°C<br>'.number_format($tomorrowTempMax, 1, ",", "" ).'°C</temperatur></td>';
//			$wert.='<td align="center"> <img src="'.$tomorrow.'" alt="Heute" > </td></tr>';
//			$wert.='<tr id="temp"><td> <temperatur>'.number_format($tomorrow1TempMin, 1, ",", "" ).'°C<br>'.number_format($tomorrow1TempMax, 1, ",", "" ).'°C</temperatur></td>';
//			$wert.='<td align="center"> <img src="'.$tomorrow1.'" alt="Heute" > </td></tr>';
//			$wert.='<tr id="temp"><td> <temperatur>'.number_format($tomorrow2TempMin, 1, ",", "" ).'°C<br>'.number_format($tomorrow2TempMax, 1, ",", "" ).'°C</temperatur></td>';
//			$wert.='<td align="center"> <img src="'.$tomorrow2.'" alt="Heute" > </td></tr></table></td></tr>';
			if ($todayDate=="")
				{
				$wert.= tempTableLine($todayTempMin, $todayTempMax, $today);
				$wert.= tempTableLine($tomorrowTempMin, $tomorrowTempMax, $tomorrow);
				$wert.= tempTableLine($tomorrow1TempMin, $tomorrow1TempMax, $tomorrow1);
				$wert.= tempTableLine($tomorrow2TempMin, $tomorrow2TempMax, $tomorrow2);
				}
			else
				{
				$wert.= tempTableLine($todayTempMin, $todayTempMax, $today,$todayDate);
				$wert.= tempTableLine($tomorrowTempMin, $tomorrowTempMax, $tomorrow, $tomorrowDate);
				$wert.= tempTableLine($tomorrow1TempMin, $tomorrow1TempMax, $tomorrow1, $tomorrow1Date);
				$wert.= tempTableLine($tomorrow2TempMin, $tomorrow2TempMax, $tomorrow2, $tomorrow2Date);
				}
			$wert.='</table></td></tr>';
            $wert.=bottomTableLines($configuration["Display"]);
            $wert.='</table>';
            }
        }    
	return $wert;
	}

function tempTableLine($TempMin, $TempMax, $imageSrc, $date="")
	{
	$wert="";
	if ($date=="")
		{
		$wert.='<tr id="temp"><td><temperatur>'.number_format($TempMin, 1, ",", "" ).'°C<br>'.number_format($TempMax, 1, ",", "" ).'°C</temperatur></td>';
		$wert.='<td align="center"> <img src="'.$imageSrc.'" alt="Heute" > </td></tr>';	
		}
	else	
		{
		$wert.='<tr id="temp"><td><datum>'.$date.'</datum></td>';
		$wert.='<td><temperatur>'.number_format($TempMin, 1, ",", "" ).'°C<br>'.number_format($TempMax, 1, ",", "" ).'°C</temperatur></td>';
		$wert.='<td align="center"> <img src="'.$imageSrc.'" alt="Heute" > </td></tr>';			
		}
	return ($wert);
	}


?>