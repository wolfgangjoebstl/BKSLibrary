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
    * es gibt verschiedene Darstellungsarten
    * die Darstellung selbst erfolgt über die Library mit $startpage->StartPageWrite
    *
    * wird alle 8 Minuten vom Timer aufgerufen
    * Routine bearbeitet auch die Tastendrücke am Webfront
    *
    * Bilder im Verzeichnis werden verkleinert um die Darstellung im Webfront zu beschleunigen
    *
    * die Wetterfunktion wird bearbeitet und mit aggregateOpenWeather in eine schöne Form gebracht
    *
    **************************************/

    if ($_IPS['SENDER']=="Execute") $debug=true;
    else $debug=false;

    /********************************************* CONFIG *******************************************************/

    ini_set('memory_limit', '-1');          // memory unbeschränkt um die Bildbearbeitung zu ermöglichen

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
        //echo "Kategorienvergleich: ".$startpage->CategoryIdData."   $CategoryIdData  \n";
        }
    $variableIdHTML  = CreateVariable("Uebersicht",    3 /*String*/, $CategoryIdData, 40, '~HTMLBox', null,null,"");
    $AstroLinkID     = CreateVariable("htmlAstroTable",3           , $CategoryIdData,100, "~HTMLBox", null,null,"");
    $mobileContentID = CreateVariable("htmlMobileTable",3          , $CategoryIdData,0,"~HTMLBox",null,null,"");                    // Bottomline für Mobile Webfront

    $switchScreenID    = IPS_GetVariableIDByName("SwitchScreen",$CategoryIdData);
    $switchSubScreenID = IPS_GetVariableIDByName("SwitchSubScreen",$CategoryIdData);  

    $showfile=false;            // dann wird auch wenn nicht übergeben es automatisch generiert

    /**************************************** Tastendruecke aus dem Webfront abarbeiten *********************************************************/

    $dosOps = new dosOps();
    $Verzeichnis=$dosOps->correctDirName($dosOps->getWorkDirectory());
    $Verzeichnis       = $dosOps->correctDirName($startpage->getPictureDirectory());
    $VerzeichnisBilder = $dosOps->correctDirName($startpage->getPictureDirectory()."SmallPics");

 if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
    $variableID=$_IPS['VARIABLE'];
    switch ($variableID)          // Value formatted : Explorer Fullscreen Station Picture Topologoe Off
        {
        case ($switchScreenID):
            /* andere Zuordnung, Auswahlfeld ist breiter geworden, erfordert
             * Zuordnung zu write Startpage
             *      0       Explorer
             *      1       Full Screen
             *      2       Station             2
             *      3       Media               5
             *      4       Picture             1
             *      5       Topologie           3
             *      6       Hierarchie          4
             *      7       Off
             */
        	switch ($_IPS['VALUE'])
		        {
        		case "7":	/* Monitor off/on, Off */
		        	controlMonitor("off",$configuration);
        			break;
                case "6":   /* Hierarchy, new one with picture drawing of geographical position*/
        			SetValue($StartPageTypeID,4);
                    //echo "Set 6 ID to 4";
					break;
                case "5":   /* Topologie, new one with picture drawing of geographical position*/
        			SetValue($StartPageTypeID,3);
                    //echo "Set 5 ID to 3";
					break;
    	        case "4":  	/* Bildschirmschoner, Picture */
        			SetValue($StartPageTypeID,1);
                    //echo "Set 4 ID to 1";
		        	break;
    	        case "3":  	/* Bildschirmschoner, Media */
        			SetValue($StartPageTypeID,5);
                    //echo "Set 3 ID to 5";                               // Media taste
		        	break;
        		case "2":  	/* Wetterstation, Station */
		        	SetValue($StartPageTypeID,2);
                    SetValue($switchSubScreenID,GetValue($switchSubScreenID)+1);
                    //echo "Set 2 ID to 2";
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

if (GetValue($StartPageTypeID)==1)      // nur die Fotos von gross auf klein konvertieren und aussuchen wenn die Darstellung auch auf Pictures ist
    {

    /* 
    * Das Archiv für die Fotos ist das bilderverzeichnis, picturedir wird für die Darstellung aus dem Webfront verwendet
    * da eine relative Adressierung auf den Server adressiert und eine absolute Adressierung auf den Client geht.
    *
    */

    $files=$startpage->readPicturedir();
    $maxcount=count($files);
    if ($maxcount>0)
        {
        $showfile=rand(1,$maxcount-1);
        if ($debug) echo "StartpageTypeID ist 1. Parameter : Bilder zur Anzeige  $maxcount Datei Index dafür ausgesucht $showfile \n";;
        //print_r($file);

        if ( is_dir($VerzeichnisBilder) ==  false ) mkdir($VerzeichnisBilder);
        $datei=$files[$showfile];

        if (file_exists($VerzeichnisBilder.$datei))
            {
            if ($debug)
                {
                foreach ($files as $index=> $file )
                    {
                    if (file_exists($VerzeichnisBilder.$file)===false) 
                        {
                        $datei=$file;    
                        break;
                        }
                    }
                }
            }
        if (file_exists($VerzeichnisBilder.$datei)===false)
            {
            // Get new dimensions
            list($width, $height) = getimagesize($Verzeichnis.$datei);
            if ($debug) echo "Resample Picture $datei (".$width." x ".$height.") from ".$Verzeichnis.$datei." to ".$VerzeichnisBilder.$datei.".\n";

            $new_width=1920;
            $percent=$new_width/$width;
            $new_height = floor($height * $percent);
            if ($new_height > 1080) 
                { 
                if ($debug) echo "Status zu hoch : ".$new_width."  ".$new_height."   \n";
                $new_height=1080;
                $percent=$new_height/$height;
                $new_width = floor($width * $percent);
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
    }
        
/**************************************** und jetzt sich auch noch um das Wetter kuemmern *********************************************************/

    /* wenn OpenWeather installiert ist dieses für die Startpage passend aggregieren, die Werte werden automatisch abgeholt */

    if ($debug) echo "aggregate Openweather.\n";
    $startpage->aggregateOpenWeather();						// die Zusammenfassung für die Wetter-Tabelle auf der Startpage machen
    $startpage->displayOpenWeather();                       // die Highcharts Darstellung huebsch machen,
    if ($debug) echo "write Summary Openweather.\n";
    $startpage->writeOpenweatherSummarytoFile();			// es gibt eine lange html Zusammenfassung, die man am besten in einen iFrame mit scroll Funktion packt		

    /**************************************** und jetzt die Startpage darstellen *********************************************************/

    /* mit der Funktion StartPageWrite wird die html Information für die Startpage aufgebaut */

    if ($debug) echo "Aufruf StartpageWrite in Startpage Class Library.\n";
    SetValue($variableIdHTML,$startpage->StartPageWrite(GetValue($StartPageTypeID),$showfile,$debug));
    SetValue($mobileContentID,  $startpage->bottomTableLines());                                                        // Mobile Webfron Statuszeile/tabelle

    /**************************************** PROGRAM EXECUTE *********************************************************/

 if ($_IPS['SENDER']=="Execute")
	{
    echo "\n================================================================\n"; 
	echo "Execute aufgerufen:\n";
    echo "Startpage wird mit folgenden Parametern aufgerufen : Modus:".GetValue($StartPageTypeID)." ShowFile:".($showfile?"true":"false").".\n";
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

    /* Verzeichnisse auslesen für besseren Überblick */
    
    $dosOps->writeDirStat($Verzeichnis);
    $dosOps->writeDirStat($VerzeichnisBilder);

    $files = $dosOps->readDirToArray($VerzeichnisBilder);
    $maxcount=count($files);
    $files1 = $dosOps->readDirToArray($Verzeichnis);                    // Input Directory
    $maxcount1=count($files1);
    echo "Bildanzeige, es gibt insgesamt ".$maxcount."/".$maxcount1." Bilder (Ziel/Quelle) auf dem angegebenen Laufwerk.\n";

    $fileName=array(); $fileName1=array();
    foreach ($files as $name)  
        {
        if (is_dir($VerzeichnisBilder.$name)===false) $fileName[$name]  = filesize($VerzeichnisBilder.$name);
        }
    $maxcount=count($fileName);        
    foreach ($files1 as $name) 
        {
        if (is_dir($Verzeichnis.$name)===false) $fileName1[$name] = filesize($Verzeichnis.$name);
        }
    $maxcount1=count($fileName1); 
    echo "Bildanzeige, es gibt insgesamt ".$maxcount."/".$maxcount1." Bilder (Ziel/Quelle) auf dem angegebenen Laufwerk.\n";           
    
    if ($maxcount1>$maxcount)
        {
        foreach ($fileName as $name => $size) unset($fileName1[$name]);
        echo "Files that have not been converted from $Verzeichnis to $VerzeichnisBilder.\n";
        print_R($fileName1);
        }

    if (false)
        {
        echo "Startpage wird mit folgenden Parametern aufgerufen : Modus:".GetValue($StartPageTypeID)." ShowFile:".($showfile?"true":"false").".\n";
        echo "Darstellung Startpage, Darstellung der links zu Bildern ist nicht möglich.\n";
        echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
        echo $startpage->StartPageWrite(2,false,true);                      // true mit Debug
        }

    echo "Erzeugung des Wetter Meteograms:\n";
    $startpage->displayOpenWeather(true);           //true für Debug

    echo "Durchlauf Startpage Write mit Debug ein:\n";
    $startpage->StartPageWrite(GetValue($StartPageTypeID),$showfile,true);      // true für Debug

    echo "Vergleich der Fotos am Sharedrive mit den anderen Fotos:\n";

    $picturedir = $startpage->picturedir;
    //print_R($configuration["Directories"]);
    $smallpicsdir = $picturedir."SmallPics/";
    $filesTable=array();
    echo "Bilderverzeichnis Source $bilderverzeichnis Target $picturedir Small Pics $smallpicsdir\n";
    //$files=$dosOps->readdirToArray($bilderverzeichnis,["recursive"=>false,"detailed"=>true],false,true);
    $files = $dosOps->writeDirToArray($bilderverzeichnis);        // bessere Funktion
    $filesFiltered = $dosOps->findfiles($files,"*.jpg",false);       //Debug
    foreach ($filesFiltered as $index => $filename)
        {
        $filesTable[$filename]["Source"]="ok";
        }
    //print_R($files);
    //$dosOps->writeDirStat($bilderverzeichnis);
    //$files=$dosOps->readdirToArray($picturedir);
    //print_R($files);
    echo "$bilderverzeichnis, insgesamt ".sizeof($files)." Dateien.\n";
    //echo "\n";
    //$files=$dosOps->readdirToArray($picturedir);
    //$dosOps->writeDirStat($picturedir);
    $files = $dosOps->writeDirToArray($picturedir);        // bessere Funktion
    $filesFiltered = $dosOps->findfiles($files,"*.jpg",true);       //Debug
    foreach ($filesFiltered as $index => $filename)
        {
        $filesTable[$filename]["Target"]="ok";
        }
    echo "$picturedir, insgesamt ".sizeof($files)." Dateien.\n";
    //echo "\n";
    //$files=$dosOps->readdirToArray($picturedir."SmallPics/");
    //$dosOps->writeDirStat($picturedir."SmallPics/");
    $files = $dosOps->writeDirToArray($smallpicsdir);
    echo "Small Pics Dir $smallpicsdir, insgesamt ".sizeof($files)." Dateien.\n";
    //print_R($files);
    $filesFiltered = $dosOps->findfiles($files,"*.jpg",true);       //Debug
    //print_R($filesFiltered);
    foreach ($filesFiltered as $index => $filename)
        {
        $filesTable[$filename]["SmallPics"]="ok";
        }    
    echo "Ergebnistabelle der Auswertung:\n";
    //print_R($filesTable);

        $html = "<table>";
        foreach ($filesTable as $filename=>$status)
            {
            $html .= "<tr>";
            $html .= "<td>$filename</td>";
            if (isset($status["Source"])) $html .= "<td>".$status["Source"]."</td>";
            else $html .= "<td></td>";
            if (isset($status["Target"])) 
                {
                $html .= "<td>".$status["Target"]."</td>";
                list($width, $height) = getimagesize($startpage->picturedir.$filename);
                $html .= "<td>".$width."</td><td>".$height."</td>";
                }
            else $html .= "<td></td><td></td><td></td>";
            if (isset($status["SmallPics"])) 
                {
                $html .= "<td>".$status["SmallPics"]."</td>";
                list($width, $height) = getimagesize($smallpicsdir.$filename);
                $html .= "<td>".$width."</td><td>".$height."</td>";
                }
            else $html .= "<td></td><td></td><td></td>";
            $html .= "</tr>";
            }
        $html .= "<table>";
        echo $html;
  
    }



?>