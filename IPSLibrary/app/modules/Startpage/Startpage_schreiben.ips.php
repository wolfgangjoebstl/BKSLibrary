<?

/********************************************* CONFIG *******************************************************/

ini_set('memory_limit', '-1');
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');

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


/**************************************** PROGRAM *********************************************************/


 if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);

	switch ($_IPS['VALUE'])
		{
		case "3":  /* Bildschirmschoner */
			SetValue($StartPageTypeID,1);
			break;


		case "2":  /* Wetterstation */
			SetValue($StartPageTypeID,2);
			break;

		case "1":  /* Full Screen ein */
		case "0":  /* Full Screen aus */

			IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, -1);

			break;
		}
	}

SetValue($variableIdHTML,StartPageWrite(GetValue($StartPageTypeID)));



 if ($_IPS['SENDER']=="Execute")
	{
	echo "Execute aufgerufen:\n";
	echo "\nKonfigurationseinstellungen:\n";
	print_r($configuration);

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

	$noweather=false;
	$todayID = @IPS_GetObjectIDByName("Program",0);
	$todayID = @IPS_GetObjectIDByName("IPSLibrary",$todayID);
	$todayID = @IPS_GetObjectIDByName("data",$todayID);
	$todayID = @IPS_GetObjectIDByName("modules",$todayID);
	$todayID = @IPS_GetObjectIDByName("Weather",$todayID);
	$todayID = @IPS_GetObjectIDByName("IPSWeatherForcastAT",$todayID);
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
		}
		
	/* Wenn Configuration verfügbar und nicht Active dann die rechte Tabelle nicht anzeigen */	
	if ( isset ($configuration["Display"]["Weathertable"]) == true ) { if ( $configuration["Display"]["Weathertable"] != "Active" ) { $noweather=true; } }

	/* html file schreiben, Anfang Style für alle gleich */

	$wert='<style>';
    $wert.='kopf { background-color: red; height:120px;  }';        // define element selectors
    $wert.='strg { height:280px; color:black; background-color: #c1c1c1; font-size: 12em; }';
    $wert.='innen { color:black; background-color: #ffffff; height:100px; font-size: 80px; }';
    $wert.='aussen { color:black; background-color: #c1c1c1; height:100px; font-size: 80px; }';
    $wert.='temperatur { color:black; height:100px; font-size: 28px; align:center; }';
    $wert.='infotext { color:white; height:100px; font-size: 12px; }';
    $wert.='#temp td { background-color:#ffefef; }';                // define ID Selectors
    $wert.='#imgdisp { border-radius: 8px;  max-width: 100%; height: auto;  }';
    $wert.='#startpage { border-collapse: collapse; border: 2px dotted white; width: 100%; }';
    $wert.='.container { width: 100%; }';
    $wert.='.image { opacity: 1; display: block; width: 90%; height: auto; transition: .5s ease; backface-visibility: hidden; padding: 5px }';
    $wert.='.middle { transition: .5s ease; opacity: 0; position: absolute; top: 90%; left: 30%; transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%) }';
    $wert.='.container:hover .image { opacity: 0.8; }';             // define classes
    $wert.='.container:hover .middle { opacity: 1; }';
    $wert.='.text { background-color: #4CAF50; color: white; font-size: 16px; padding: 16px 32px; }';
    $wert.='</style>';

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
		if ($PageType==2)
			{
            echo "NOWEATHER false. PageType 2. NoPicture.\n";            	
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
            $filename = 'user/Startpage/user/pictures/SmallPics/'.$file[$showfile];
            $filegroesse=number_format((filesize(IPS_GetKernelDir()."webfront/".$filename)/1024/1024),2);
            $info=getimagesize(IPS_GetKernelDir()."webfront/".$filename);
            if (file_exists(IPS_GetKernelDir()."webfront/".$filename)) 
                {
                //echo "Filename vorhanden - Groesse ".$filegroesse." MB.\n";
                }
            //echo "NOWEATHER false. PageType 1. Picture. ".$filename."\n\n";            	                
			$wert.='<table id="startpage">';
            $wert.='<tr><th>Bild</th><th>Temperatur und Wetter</th></tr><tr>';
            //$wert.='<td><img id="imgdisp" src="'.$filename.'" alt="'.$filename.'"></td>';
            $wert.='<td><div class="container"><img src="'.$filename.'" alt="'.$filename.'" class="image">';
            $wert.='<div class="middle"><div class="text">'.$filename.'<br>'.$filegroesse.' MB '.$info[3].'</div>';
            $wert.='</div></td>';
            $wert.='<td><table border="0" bgcolor="#f1f1f1"><tr><td> <img src="user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>';
            $wert.='<td><img src="user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur"></td></tr>';
            $wert.='<tr><td><aussen>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
            $wert.='<tr id="temp"><td><table><tr> <td> <temperatur>'.number_format($todayTempMin, 1, ",", "" ).'°C</temperatur></td> </tr>';
            $wert.='<tr> <td><temperatur>'.number_format($todayTempMax, 1, ",", "" ).'°C</temperatur></td> </tr></table>';
			$wert.='</td><td align="center"> <img src="'.$today.'" alt="Heute" > </td></tr><tr id="temp"><td> <table>';
			$wert.='<tr> <td> <temperatur>'.number_format($tomorrowTempMin, 1, ",", "" ).'°C</temperatur></td> </tr>';
  			$wert.='<tr> <td><temperatur>'.number_format($tomorrowTempMax, 1, ",", "" ).'°C</temperatur></td> </tr>';
  			$wert.='</table></td><td align="center"> <img src="'.$tomorrow.'" alt="Heute" > </td></tr><tr id="temp"><td><table>';
  			$wert.='<tr><td> <temperatur>'.number_format($tomorrow1TempMin, 1, ",", "" ).'°C</temperatur></td></tr>';
  			$wert.='<tr><td><temperatur>'.number_format($tomorrow1TempMax, 1, ",", "" ).'°C</temperatur></td></tr></table></td>';
  			$wert.='<td align="center"> <img src="'.$tomorrow1.'" alt="Heute" > </td></tr>';
  			$wert.='<tr id="temp"><td><table><tr> <td style="background-color:#efefef;right:50px;"> <temperatur>'.number_format($tomorrow2TempMin, 1, ",", "" ).'°C</temperatur></td> </tr>';
  			$wert.='<tr><td><temperatur>'.number_format($tomorrow2TempMax, 1, ",", "" ).'°C</temperatur></td></tr>';
  			$wert.='</table><td align="center"> <img src="'.$tomorrow2.'" alt="Heute" > </td></tr></table></td></tr></table>';
            }
        }    
	return $wert;

	}

?>