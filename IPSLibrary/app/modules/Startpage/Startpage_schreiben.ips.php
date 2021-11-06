<?

	/*
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

    /*******************************************************
    *
    * schreiben des Startpage html Strings in der htmlbox der Startpage
    *
    * es gibt verschiedene darstellungsarten
    *
    *
    **************************************/

    $debug=false;

    /********************************************* CONFIG *******************************************************/

    ini_set('memory_limit', '-1');          // memory unbeschränkt um die Bildbearbeitung zu ermöglichen

    //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
    IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
    IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Startpage',$repository);
		}
 	$installedModules = $moduleManager->GetInstalledModules();
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');

    //$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
    //IPS_SetScriptTimer($_IPS['SELF'], 8*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */

    if (isset($installedModules["DetectMovement"]))
        {
        /* Detect Movement kann auch Temperaturen agreggieren */
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        }


    $startpage = new StartpageHandler();
    $configuration=$startpage->getStartpageConfiguration();

    $bilderverzeichnis=$configuration["Directories"]["Pictures"];

    $StartPageTypeID = IPS_getObjectIdByName("Startpagetype", $startpage->CategoryIdData);   /* 0 Boolean 1 Integer 2 Float 3 String */
    if ($debug) 
        {
        echo "StartpageTypeID : ".$StartPageTypeID." (".IPS_GetName($StartPageTypeID)."/".IPS_GetName(IPS_GetParent($StartPageTypeID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($StartPageTypeID)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($StartPageTypeID)))).") ".GetValue($StartPageTypeID)."\n";
        echo "Kategorienvergleich: ".$startpage->CategoryIdData."   $CategoryIdData  \n";
        }
    $variableIdHTML  = CreateVariable("Uebersicht",    3 /*String*/, $CategoryIdData, 40, '~HTMLBox', null,null,"");
    $AstroLinkID     = CreateVariable("htmlAstroTable",3           , $CategoryIdData,100, "~HTMLBox", null,null,"");

    $switchScreenID    = IPS_GetVariableIDByName("SwitchScreen",$CategoryIdData);
    $switchSubScreenID = IPS_GetVariableIDByName("SwitchSubScreen",$CategoryIdData);  

    $showfile=false;            // dann wird auch wenn nicht übergeben es automatisch generiert

    /**************************************** Tastendruecke aus dem Webfront abarbeiten *********************************************************/


 if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
    $variableID=$_IPS['VARIABLE'];
    switch ($variableID)          // Value formatted : Explorer Fullscreen Station Picture Topologoe Off
        {
        case ($switchScreenID):
        	switch ($_IPS['VALUE'])
		        {
        		case "7":	/* Monitor off/on, Off */
		        	controlMonitor("off",$configuration);
        			break;
                case "6":   /* Hierarchy, new one with picture drawing of geographical position*/
        			SetValue($StartPageTypeID,4);
					break;
                case "5":   /* Topologie, new one with picture drawing of geographical position*/
        			SetValue($StartPageTypeID,3);
					break;
    	        case "4":  	/* Bildschirmschoner, Media */
        			SetValue($StartPageTypeID,5);
		        	break;
    	        case "3":  	/* Bildschirmschoner, Picture */
        			SetValue($StartPageTypeID,1);
		        	break;
        		case "2":  	/* Wetterstation, Station */
		        	SetValue($StartPageTypeID,2);
                    SetValue($switchSubScreenID,GetValue($switchSubScreenID)+1);
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

/******************************************* Bilder initialisieren und bearbeiten ******************************************************/

if (GetValue($StartPageTypeID)==1)      // nur die Fotos von gross auf klein konvertieren und aussuchen wenn die darstellung auch auf Pictures ist
    {

    /* 
    * Das Archiv für die Fotos ist das bilderverzeichnis, picturedir wird für die Darstellung aus dem Webfront verwendet
    * da eine relative Adressierung auf den Server adressiert und eine absolute Adressierung auf den Client geht.
    *
    */

    $file=$startpage->readPicturedir();
    $maxcount=count($file);
    if ($maxcount>0)
        {
        $showfile=rand(1,$maxcount-1);
        if ($debug) echo "StartpageTypeID ist 1. Parameter : $maxcount   $showfile \n";;
        //print_r($file);

        if ( is_dir($startpage->picturedir."SmallPics") ==  false ) mkdir($startpage->picturedir."SmallPics");
        $datei=$file[$showfile];

        // Get new dimensions
        list($width, $height) = getimagesize($startpage->picturedir.$datei);
        if ($debug) echo "Resample Picture (".$width." x ".$height.") from ".$startpage->picturedir.$datei." to ".$startpage->picturedir."SmallPics/".$datei.".\n";

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
        if ($debug) echo "New Size : (".$new_width." x ".$new_height.").\n";

        // Resample
        $image_p = imagecreatetruecolor($new_width, $new_height);
        $image = imagecreatefromjpeg($startpage->picturedir.$datei);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Output
        imagejpeg($image_p, $startpage->picturedir."SmallPics/".$datei, 60);
        }
    }
        
    /**************************************** und jetzt sich auch noch um das Wetter kuemmern *********************************************************/

    /* wenn OpenWeather installiert ist dieses für die Startpage passend aggregieren, die Werte werden automatisch abgeholt */

    if ($debug) echo "aggregate Openweather.\n";
    $startpage->aggregateOpenWeather();						// die Highcharts Darstellung huebsch machen, zusaetzlich die Zusammenfassung für die Wetter-Tabelle auf der Startpage machen
    if ($debug) echo "write Summary Openweather.\n";
    $startpage->writeOpenweatherSummarytoFile();			// es gibt eine lange html Zusammenfassung, die man am besten in einen iFrame mit scroll Funktion packt		

    /**************************************** und jetzt die Startpage darstellen *********************************************************/

    /* mit der Funktion StartPageWrite wird die html Information für die Startpage aufgebaut */

    if ($debug) echo "Aufruf StartpageWrite in Startpage Class Library.\n";
    SetValue($variableIdHTML,$startpage->StartPageWrite(GetValue($StartPageTypeID),$showfile,$debug));


    /**************************************** PROGRAM EXECUTE *********************************************************/

 if ($_IPS['SENDER']=="Execute")
	{
    echo "\n================================================================\n"; 
	echo "Execute aufgerufen:\n";
	//echo "\nKonfigurationseinstellungen:\n"; print_r($configuration);

	$pname="StartpageControl";
    echo "Variable SwitchScreen mit Profil \"$pname\" hat OID: $switchScreenID \n";
	if (IPS_VariableProfileExists($pname) == true)  //Var-Profil erstellen     
		{
        $profile=IPS_GetVariableProfile($pname)["Associations"];
        foreach ($profile as $index => $profil) echo "  ".$index."  ".$profil["Value"]."  ".$profil["Name"]."\n";
        //print_r($profile);
        }
	echo "Switch on Monitor, look for :".$configuration["Directories"]["Scripts"].'nircmd.exe'."\n"; 
	IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, -1);	
	
	$Config=$startpage->configWeather();
	$noweather=!$Config["Active"]; 
    print_r($Config);

    $file=$startpage->readPicturedir();
    $maxcount=count($file);
	echo "Bildanzeige, es gibt insgesamt ".$maxcount." Bilder auf dem angegebenen Laufwerk.\n";
    echo "Startpage wird mit folgenden Parametern aufgerufen : Modus:".GetValue($StartPageTypeID)." ShowFile:".($showfile?"true":"false").".\n";
    echo "Darstellung Startpage, Darstellung der links zu Bildern ist nicht möglich.\n";
	echo $startpage->StartPageWrite(2);
	}



?>