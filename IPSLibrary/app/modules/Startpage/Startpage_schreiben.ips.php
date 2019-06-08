<?

/********************************************* CONFIG *******************************************************/

ini_set('memory_limit', '-1');
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
//IPS_SetScriptTimer($_IPS['SELF'], 8*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */

$startpage = new StartpageHandler();
$configuration=$startpage->getStartpageConfiguration();

$bilderverzeichnis=$configuration["Directories"]["Pictures"];

//$StartPageTypeID = CreateVariableByName($parentid, "Startpagetype", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
$StartPageTypeID = IPS_getObjectIdByName("Startpagetype", $startpage->CategoryIdData);   /* 0 Boolean 1 Integer 2 Float 3 String */
//echo "StartpageTypeID : ".$StartPageTypeID." (".IPS_GetName($StartPageTypeID)."/".IPS_GetName(IPS_GetParent($StartPageTypeID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($StartPageTypeID)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($StartPageTypeID)))).")\n";

$variableIdHTML  = CreateVariable("Uebersicht", 3 /*String*/,  $parentid, 40, '~HTMLBox', null,null,"");

$vid = @IPS_GetVariableIDByName("SwitchScreen",$parentid);

/******************************************* INIT ******************************************************/

/* 
 * Das Archiv für die Fotos ist das bilderverzeichnis, picturedir wird für die Darstellung aus dem Webfront verwendet
 * da eine relative Adressierung auf den Server adressiert und eine absolute Adressierung auf den Client geht.
 *
 */

$file=$startpage->readPicturedir();
$maxcount=count($file);
$showfile=rand(1,$maxcount-1);
//echo $maxcount."  ".$showfile."\n";;
//print_r($file);

if ( is_dir($startpage->picturedir."SmallPics") ==  false ) mkdir($startpage->picturedir."SmallPics");
$datei=$file[$showfile];

// Get new dimensions
list($width, $height) = getimagesize($startpage->picturedir.$datei);
//echo "Resample Picture (".$width." x ".$height.") from ".$startpage->picturedir.$datei." to ".$startpage->picturedir."SmallPics/".$datei.".\n";

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
$image = imagecreatefromjpeg($startpage->picturedir.$datei);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

// Output
imagejpeg($image_p, $startpage->picturedir."SmallPics/".$datei, 60);


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

$startpage->aggregateOpenWeather();						// die Highcharts Darstellung huebsch machen, zusaetzlich die Zusammenfassung für die Wetter-Tabelle auf der Startpage machen
$startpage->writeOpenweatherSummarytoFile();			// es gibt eine lange html Zusammenfassung, die man am besten in einen iFrame mit scroll Funktion packt		

/* mit der Funktion StartPageWrite wird die html Information für die Startpage aufgebaut */

SetValue($variableIdHTML,$startpage->StartPageWrite(GetValue($StartPageTypeID),$showfile));


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
	echo $startpage->StartPageWrite(1);
	}



?>