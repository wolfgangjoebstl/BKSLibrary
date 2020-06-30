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
	 
	/**
	 *
	 * Script zur Erstellung von WebCamera Anzeigen
     *
     * noch kein eigenständiges Modul. Verwendet aber bereits eigenes Webfront für die Darstellung
     * Verwendung von Activity für regelmaessige Timeraufrufe und Library für gemeinsame Routine und eine Class
     *
     * aus der OperationCenter Configuration werden die Cameras für die Live Überwachung genommen. Das sind alle lokalen Kameras mit lokalen IP Adressen. Exxterne gehen auch aber dee Bandbreite ist recht gross.
     * in der IPSCam können mehrere Kameras sein, auch welche die nur über extern erreichbar sind
	 *
	 *
	 * @file          WebCamera_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 *
	 **/

	$debug=false;
    $startexec=microtime(true);     /* Laufzeitmessung */

    /*******************************
    *
    * Initialisierung, Modul Handling Vorbereitung
    *
    ********************************/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

    IPSUtils_Include ("WebCamera_Configuration.inc.php","IPSLibrary::config::modules::WebCamera");
	IPSUtils_Include ("WebCamera_Library.inc.php","IPSLibrary::app::modules::WebCamera");

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	
    IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
    IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

    $sysOps = new sysOps();
	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
    echo "Allocated Memory : ".$sysOps->getNiceFileSize(memory_get_usage(true),false).".\n";

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('WebCamera',$repository);
		}
 	$installedModules = $moduleManager->GetInstalledModules();
	//print_r($installedModules);

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
    $scriptIdActivity   = IPS_GetScriptIDByName('WebCamera_Activity', $CategoryIdApp);
    echo "Script ID für regelmaessige Timeraufrufe gefunden:  $scriptIdActivity (".IPS_GetName($scriptIdActivity).")\n";

	echo "IP Symcon Daten:\n";
	echo "  Kernelversion : ".IPS_GetKernelVersion()."\n";
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "  Modulversion  : ".$ergebnis."\n";
    $ergebnisVersion=$moduleManager->VersionHandler()->GetVersion('WebCamera');
	echo "  WebCamera Version : ".$ergebnisVersion."\n";                                    /* wird auch für das Logging der Installation verwendet */
    echo "\n";

    $ipsOps = new ipsOps();
    $dosOps = new dosOps();
    
    $subnet="10.255.255.255";
	$OperationCenter=new OperationCenter($subnet);
	$LogFileHandler=new LogFileHandler($subnet);    // handles Logfiles und Cam Capture Files

    $webCamera = new webCamera();       // eigene class starten

	/******************************************************
	 *
	 *				Logging der Installation 
     *
     ********************************************************/

	$Heute=time();
	$HeuteString=date("dmY",$Heute);
	echo "Heute  Datum ".$HeuteString." für das Logging der OperationCenter Installation.\n";
	
	if (isset ($installedModules["OperationCenter"])) 
		{
		$log_Install=new Logging("C:\Scripts\Install\Install".$HeuteString.".csv");								// mehrere Installs pro Tag werden zusammengefasst
		$log_Install->LogMessage("Install Module OperationCenter. Aktuelle Version ist $ergebnisVersion.");
		}    


	/******************************************************
	 *
	 *				INIT, Timer
	 *
	 * Timer immer so konfigurieren dass sie sich nicht in die Quere kommen. 
     * Derzeit ein Timer der alle 5 Minuten ein Bild von einer Webcam abholt. Umso mehr WebCams umso langsamer geht es.
     * In IPSCam ware es einzelne Timer pro Camera, der Startpunkt war zufällig, wenn die Auslesezeit neu definiert wurde
	 *
	 *************************************************************/

	echo "\nTimer für das Auslesen des Standbildes aller Cameras programmieren :\n";
	
	$timerOps = new timerOps();
    $tim1ID = $timerOps->setTimerPerMinute("GetCamPictureTimer", $scriptIdActivity, 5);           /* Name Activity Script Minuten */

	/******************************************************
     *
	 *			INIT, DATA SystemInfo
     *
	 *************************************************************/

	$categoryId_CamPictures	= CreateCategory('CamPictures',   $CategoryIdData, 230);
	$camIndexID   			= CreateVariableByName($categoryId_CamPictures, "Hostname", 1, "", "", 1000); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */

    IPS_SetHidden($categoryId_CamPictures, true); 		// in der normalen OperationCenter Kategorie Darstellung die Kategorie verstecken, ist jetzt eh im Webfront

    SetValue($camIndexID,0);

    /********************************************************************
     *
     * auch IPSCam Installation mit betrachten
     *
     *****************************************************************/

    if (isset($installedModules["IPSCam"]) )
        {
        IPSUtils_Include ("IPSCam.inc.php","IPSLibrary::app::modules::IPSCam");

        $repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
        $moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);

    	$camManager = new IPSCam_Manager();
		echo "\n";
		echo "IPSCam installiert, Ausgabe Konfiguration, nur zur information, es gilt die Config im OperationCenter_Configuration File :\n";
		$CamConfig             = IPSCam_GetConfiguration();
        //print_r($CamConfig);
        foreach ($CamConfig as $cam) echo "    ".$cam["Name"]."\n";
        echo "\n";
        }

    /********************************************************************
     *
     * auch OperationCenter Installation mit betrachten, Config Dateien kommen von dort
     *
     *****************************************************************/

    if (isset($installedModules["OperationCenter"]) )
        {
    	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\OperationCenter\OperationCenter_Configuration.inc.php");
	    IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");            

        $moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
        $CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');

        $CategoryIdDataOverview=IPS_GetObjectIDByName("Cams",$CategoryIdDataOC);
        echo "IPS Path of OperationCenter Data Category : ".$CategoryIdDataOverview."  ".$ipsOps->path($CategoryIdDataOverview)."\n";

        $subnet="10.255.255.255";
        $OperationCenter=new OperationCenter($subnet);
        $OperationCenterConfig = $OperationCenter->getConfiguration();
        }
    else $OperationCenterConfig = array();                  // leeres Array wenn OperationCenter nicht konfiguriert ist

    echo "====================================================================================\n";
    echo "Ausgabe der OperationCenter spezifischen Cam Konfigurationsdaten:\n";
	$count=0;    
    if (isset ($OperationCenterConfig['CAM']))
        {
        echo "   Vorher ein bisschen Move von CamFiles Bilder vom FTP machen. nur so zum Ausprobieren:\n";
        foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)             /* das sind die Capture Dateien, die häufen sich natürlich wenn mehr Bewegung ist */
            {
            if (isset ($cam_config['FTPFOLDER']))         
                {
                echo "   ==> Bearbeite Purge Webkamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
                $cam_config['CAMNAME']=$cam_name;
                if (isset($cam_config["MOVECAMFILES"])) if ($cam_config["MOVECAMFILES"]) $count+=$LogFileHandler->MoveCamFiles($cam_config,false);        // true ist mit debug
                //if (isset($cam_config["PURGECAMFILES"])) if ($cam_config["PURGECAMFILES"]) $OperationCenter->PurgeFiles(14,$cam_config['FTPFOLDER'],true);
                }
            }        

        $CamTablePictureID=IPS_GetObjectIdByName("CamTablePicture",$CategoryIdDataOverview);
        $CamMobilePictureID=IPS_GetObjectIdByName("CamMobilePicture",$CategoryIdDataOverview);
        echo "   Kategorie Cams in OperationCenter Data : $CategoryIdDataOverview und darin ein Objekt CamTablePicture mit OID $CamTablePictureID / $CamMobilePictureID für die Captured Bilder.\n";

        //$OperationCenter->CopyCamSnapshots(); // bereits in die Timer Routine übernommen
        
        /* Überblick der Webcams angeführt nach den einzelnen IPCams in deren OperationCenter Konfiguration 
         * Darstellung erfolgt unabhängig von den Einstellungen in der Konfig des IPSCam Moduls
         */
		$resultStream=array(); $idx=0;          // Link zu den Cameras

        echo "\n";
        echo "   CamCapture Ausgabe für folgende Kameras konfiguriert:\n";
        foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
            {
            echo "   ---------------------------------\n";
            echo "   Bearbeite WebCamera $cam_name:\n";
            if ( (isset($cam_config["FTP"])) && (strtoupper($cam_config["FTP"])=="ENABLED") ) 
                {
                echo "     Kamera FTP Server: ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";  
                $verzeichnis = $cam_config['FTPFOLDER'];  
                if (is_dir($verzeichnis)) 
                    {
                    //$dir=$dosOps->readdirToArray($verzeichnis); print_r($dir);
                    $stat=$dosOps->readdirToStat($verzeichnis);
                    //print_r($stat);
                    $lastupdate=$dosOps->latestChange($verzeichnis);
                    echo "     Verzeichnis $verzeichnis verfügbar. ".$stat["dirs"]." Verzeichnisse und ".$stat["files"]." Dateien. Latest File is from ".date("j.m.Y H:i:s",$lastupdate).".\n";
                    }
                else 
                    {
                    echo "    Fehler, Verzeichnis $verzeichnis NICHT verfügbar, jetzt erstellen.\n";
                    $rootDir='C:\\ftp\\';
                    if (is_dir($rootDir)) echo "      Verzeichnis $rootDir verfügbar. Das Unterverzeichnis erstellen.\n";
                    $dosOps->mkdirtree($verzeichnis,true);          // mit Debug um Fehler rauszufinden
                    }
                }
            else echo 'Fehler, FTP Funktion in der Konfiguration disabled. Füge "FTP" => "Enabled" ein.'."\n";

            $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdDataOC);
            if ($cam_categoryId==false)
                {
                $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                IPS_SetParent($cam_categoryId,$CategoryIdDataOC);
                }
            echo "      Kategorie pro Kamera vorhanden: $cam_categoryId.  ".$ipsOps->path($cam_categoryId)."\n";

            if (isset($cam_config['FTPFOLDER']) )
                {
                $WebCam_LetzteBewegungID = IPS_GetObjectIdByName("Cam_letzteBewegung", $cam_categoryId); 
                $WebCam_PhotoCountID = IPS_GetObjectIdByName("Cam_PhotoCount", $cam_categoryId);
                $WebCam_MotionID = IPS_GetObjectIdByName("Cam_Motion", $cam_categoryId); 

                if ( (isset($cam_config["STREAM"])) && (strtoupper($cam_config["STREAM"])=="ENABLED") ) 
                    {
                    $cam_streamId=@IPS_GetObjectIDByName("CamStream_".$cam_name,$cam_categoryId);                  
                    if ($cam_streamId===false)
                        {  
                        echo "Eigenes Media Objekt mit CamStream_$cam_name in dieser Kategorie erstellen mit entsprechendem Streaming Link.\n";
                        $cam_streamId=IPS_CreateMedia(3);
                        IPS_SetName($cam_streamId, "CamStream_".$cam_name);     // Media Stream Objekt benennen
                        IPS_SetParent($cam_streamId, $cam_categoryId);
                        }
                    echo "rtsp Stream link für $cam_streamId zusammenbauen. Sollte ähnlich lauten wie RTSP Stream 1: rtsp://user:password@192.168.x.x:/11\n";
                    if ( (isset($cam_config["FORMAT"])) && (isset($cam_config["USERNAME"])) && (isset($cam_config["PASSWORD"])) && (isset($cam_config["IPADRESSE"])) && (isset($cam_config["STREAMPORT"])))
                        {
                        /* a few checks, wether it is a IP Address */
                        $ok=true;
                        $ipadresse=explode(".",$cam_config["IPADRESSE"]);
                        if (count($ipadresse)==4) 
                            {
                            foreach ($ipadresse as $ip)
                                {
                                $ipNum=(integer)$ip;
                                if ( ($ipNum !== false) && ($ipNum < 256) ) ; else $ok=false;
                                }
                            }
                        else $ok=false;
                        if ($ok) 
                            {
                            echo "IPADRESSE ".$cam_config["IPADRESSE"]." hat ".count($ipadresse)." numerische Eintraege.";
                            $streamLink="";
                            if (strtoupper($cam_config["FORMAT"])=="RTSP")
                                {
                                $streamLink .= 'rtsp://'.$cam_config["USERNAME"].':'.$cam_config["PASSWORD"].'@'.$cam_config["IPADRESSE"].':554'.$cam_config["STREAMPORT"];
                                echo "    Streaming Media will be set to $streamLink.\n";
                                IPS_SetMediaFile($cam_streamId,$streamLink,true);

                                $resultStream[$idx]["Stream"]["OID"]=$cam_streamId;
                                $resultStream[$idx]["Stream"]["Name"]=$cam_name; 
                                $resultStream[$idx]["Data"]["Cam_letzteBewegung"]=$WebCam_LetzteBewegungID;
                                $resultStream[$idx]["Data"]["Cam_PhotoCount"]=$WebCam_PhotoCountID;
                                $resultStream[$idx]["Data"]["Cam_Motion"]=$WebCam_MotionID;
                                $idx++;
                                }
                            elseif (strtoupper($cam_config["FORMAT"])=="HTTP")
                                {
                                echo 'http MJPEG Stream link für $cam_streamId zusammenbauen. Sollte ähnlich lauten wie : http://10.0.1.121/videostream.cgi?user=admin&pwd=cloudg06&resolution=8'."\n";
                                $streamLink .= 'http://'.$cam_config["IPADRESSE"].'/videostream.cgi?user='.$cam_config["USERNAME"].'&pwd='.$cam_config["PASSWORD"].'&resolution='.$cam_config["STREAMPORT"];
                                echo "    Streaming Media will be set to $streamLink.\n";
                                IPS_SetMediaFile($cam_streamId,$streamLink,true);

                                $resultStream[$idx]["Stream"]["OID"]=$cam_streamId;
                                $resultStream[$idx]["Stream"]["Name"]=$cam_name; 
                                $resultStream[$idx]["Data"]["Cam_letzteBewegung"]=$WebCam_LetzteBewegungID;
                                $resultStream[$idx]["Data"]["Cam_PhotoCount"]=$WebCam_PhotoCountID;
                                $resultStream[$idx]["Data"]["Cam_Motion"]=$WebCam_MotionID;
                                $idx++;
                                }
                            else echo 'Fehler, keine gültiges FORMAT angelegt. Verwende http oder rstp.\n'; 
                            }
                        else echo 'Fehler, IPADRESSE hat kein gültiges Format.\n'; 
                        }
                    else echo 'Fehler, keine Stream Parameter angelegt. Füge "FORMAT","USERNAME","PASSWORD","IPADRESSE","STREAMPORT" ein.\n'; 
                    }
                else echo 'Fehler, kein Stream definiert. Füge "STREAM" => "enabled" ein.\n';
                }                           /* nur anlegen wenn FTPFOLDER definiert ist */
            else echo 'Fehler, FTPFOLDER in der Konfiguration nicht definiert. Füge "FTPFOLDER" => FTP_Cam_Verzeichnis ein.'."\n";

            //echo "      Konfiguration ".$cam_name."\n"; print_r($cam_config);
            echo "\n";

            }
        //print_r($resultStream);

    /* Webfront Konfiguration aufbauen.
     * Im array resultstream sind die Links die in jedem Webfront Administrator,User,Mobile gesetzt werden
     *
     */

    $configWFront=$ipsOps->configWebfront($moduleManager);
    //print_r($configWFront);

    if (isset($configWFront["Administrator"]))
        {
        $configWF = $configWFront["Administrator"];
        installWebfrontMon($configWF,$resultStream,true);             
        $configWF["TabItem"]="CamPicture";                          // different path item from WebCamera, local override for regularily loaded Pictures
        $configWF["TabIcon"]="Window";                              // different Icon
        installWebfrontPics($configWF,$CamTablePictureID);        
        }
        
    if (isset($configWFront["User"]))
        {
        $configWF = $configWFront["User"];            
        installWebfrontMon($configWF,$resultStream,true); 
        
        $configWF["TabItem"]="CamPicture";                          // different path item from WebCamera, local override for regularily loaded Pictures
        $configWF["TabIcon"]="Window";                              // different Icon
        installWebfrontPics($configWF,$CamTablePictureID);            
        }

    if (isset($configWFront["Mobile"]))
        {
        $configWF=$configWFront["Mobile"];
        if ( (isset($configWF["Path"])) && (isset($configWF["PathOrder"])) && (isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false)) )
            {
            $categoryId_WebFront    = CreateCategoryPath($configWF["Path"],$configWF["PathOrder"],$configWF["PathIcon"]);        // Path=Visualization.Mobile.WebCamera    , 15, Image    
            $mobileId               = CreateCategoryPath($configWF["Path"].'.'.$configWF["Name"],$configWF["Order"],$configWF["Icon"]);        // Path=Visualization.Mobile.WebCamera    , 25, Image    
            EmptyCategory($mobileId);

            /* RTSP Streams werden noch nicht am Iphone angezeigt */

            if (sizeof($resultStream)>0) CreateLink($resultStream[0]["Stream"]["Name"], $resultStream[0]["Stream"]["OID"], $mobileId, 10);
            if (sizeof($resultStream)>1) CreateLink($resultStream[1]["Stream"]["Name"], $resultStream[1]["Stream"]["OID"], $mobileId, 20);
            if (sizeof($resultStream)>2) CreateLink($resultStream[2]["Stream"]["Name"], $resultStream[2]["Stream"]["OID"], $mobileId, 30);
            if (sizeof($resultStream)>3) CreateLink($resultStream[3]["Stream"]["Name"], $resultStream[3]["Stream"]["OID"], $mobileId, 40);

            $categoryId_WebFront    = CreateCategoryPath($configWF["Path"],$configWF["PathOrder"],$configWF["PathIcon"]);        // Path=Visualization.Mobile.WebCamera    , 15, Image    
            $mobileId               = CreateCategoryPath($configWF["Path"].'.KameraPics',$configWF["Order"],$configWF["Icon"]);        // Path=Visualization.Mobile.WebCamera    , 25, Image    
            EmptyCategory($mobileId);
            CreateLinkByDestination("Pictures", $CamMobilePictureID, $mobileId,  10,"");	
            }
        }
	}









/********************************************************************/







    /*******************************************************************
     *
     * eigenes generisches WebCamera Webfront aufbauen, Default Icon Arztkoffer
     * es funktioniert noch nicht so dass die Funktion in AllgemeineDateien gespeichert und verwendet wird
     *
     * Tab mit 5 Fenster links gross und 4fach im Quadrat
     *           $resultStream[0]["Stream"]["OID"]   für Fenster Links oben
     *           $resultStream[1]["Stream"]["Link"]  für Fenster Rechts oben
     *           $resultStream[2]["Stream"]["Link"]  für Fenster Links unten
     *           //$resultStream[3]["Stream"]["Link"]  für Fenster Rechts unten
     *           $resultStream[4]["Stream"]["Link"]  für grosses Fenster links
     *
     *
     **********************************/

    function installWebfrontMon($configWF,$resultStream, $emptyWebfrontRoot=false, $StartIndex=0)
        {
        //if  ( !((isset($configWF["Enabled"])) && ($configWF["Enabled"]==false)) )  
        if ( (isset($configWF["Path"])) && (isset($configWF["TabPaneItem"])) && (isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false)) )
             {
            $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.WebCamera
            
            //$tabItem = $configWF["TabPaneItem"].$configWF["TabItem"];																				
            //echo "installWebfront: WF10 Path for WebCamera Monitor         : ".$configWF["Path"]." with this Webfront item name : $tabItem\n";
            echo "installWebfront Path : ".$configWF["Path"]." with this Webfront Tabpane Item Name : ".$configWF["TabPaneItem"]."\n";
            echo "----------------------------------------------------------------------------------------------------------------------------------\n";

            if ($emptyWebfrontRoot)         // für OperationCenter zB nicht loeschen, es gibt noch andere sub-Webfronts
                {
                echo "Kategorie $categoryId_WebFront (".IPS_GetName($categoryId_WebFront).") Inhalt loeschen und verstecken. Es dürfen keine Unterkategorien enthalten sein, sonst nicht erfolgreich.\n";
                $status=@EmptyCategory($categoryId_WebFront);
                if ($status) echo "   -> erfolgreich.\n";
                IPS_SetHidden($categoryId_WebFront, true); 		// in der normalen Viz Darstellung Kategorie verstecken
                }

            echo "Create Sub-Category ".$configWF["TabItem"]." in ".IPS_GetName($categoryId_WebFront)." and empty it.\n";
            $categoryId_WebFrontMonitor  = CreateCategory($configWF["TabItem"],  $categoryId_WebFront, 10);        // gleich wie das Tabitem beschriften, erleichtert die Wiedererkennung
            IPS_SetHidden($categoryId_WebFrontMonitor, true);                                                      // nicht im OperationCenter anzeigen, eigener Tab
			$status=@EmptyCategory($categoryId_WebFrontMonitor);				        // ausleeren und neu aufbauen, die Geschichte ist gelöscht !
            if ($status) echo "   -> erfolgreich.\n";

            /* Kategorien neu anlegen, aktuell Bezeichnung individuell */
            $categoryIdOverview  = CreateCategory('Overview',  $categoryId_WebFrontMonitor, 0);             // links davon, um die Cam Bilder in die richtige Größe zu bringen, für Summaries
            $categoryIdLeftUp  = CreateCategory('LeftUp',  $categoryId_WebFrontMonitor, 10);
            $categoryIdRightUp = CreateCategory('RightUp', $categoryId_WebFrontMonitor, 20);						
            $categoryIdLeftDn  = CreateCategory('LeftDn',  $categoryId_WebFrontMonitor, 30);
            $categoryIdRightDn = CreateCategory('RightDn', $categoryId_WebFrontMonitor, 40);						

            /*                                                    
             *    Monitor, TabpaneItem (Tab Arztkoffer im Admin Root)
             *        Splitpane, TabItem, vertical 20%    (Tab Monitorstecker unter Arztkofer)
             *             Category TabItem_Ovw
             *             Splitpane TabItem_Show, vertical 50%
             *                  Splitpane TabItem_Left, horizontal 50%
             *                      Category Up
             *                      Category Down
             *                  Splitpane TabItem_Right, horizontal 50%
             *                      Category Up
             *                      Category Down
             *
             */

            DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]);		// Einzel Tab loeschen
            CreateWFCItemTabPane   ($configWF["ConfigId"], $configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // WebCamera Tabpane
            CreateWFCItemSplitPane ($configWF["ConfigId"], $configWF["TabItem"], $configWF["TabPaneItem"], ($configWF["TabOrder"]+200), "Monitor", $configWF["TabIcon"], 1 /*Vertical*/, 20 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');  // Monitor Splitpane

            CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"]."_Ovw", $configWF["TabItem"],  10, "","",$categoryIdOverview /*BaseId*/, 'false' /*BarBottomVisible*/ );       // muss angeben werden, sonst schreibt das Splitpane auf die falsche Seite
            CreateWFCItemSplitPane ($configWF["ConfigId"], $configWF["TabItem"]."_Show", $configWF["TabItem"], ($configWF["TabOrder"]+200), "Show", "", 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            CreateWFCItemSplitPane ($configWF["ConfigId"], $configWF["TabItem"]."_Left", $configWF["TabItem"]."_Show", 10, "Left", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            CreateWFCItemSplitPane ($configWF["ConfigId"], $configWF["TabItem"]."_Right", $configWF["TabItem"]."_Show", 20, "Right", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
        
            CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Up_Left', $configWF["TabItem"]."_Left", 10, '', '', $categoryIdLeftUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Up_Right', $configWF["TabItem"]."_Right", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Dn_Left', $configWF["TabItem"]."_Left", 20, '', '', $categoryIdLeftDn   /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Dn_Right', $configWF["TabItem"]."_Right", 20, '', '', $categoryIdRightDn   /*BaseId*/, 'false' /*BarBottomVisible*/);  

            $resultStream[0]["Stream"]["Link"]=$categoryIdLeftUp;
            $resultStream[1]["Stream"]["Link"]=$categoryIdRightUp;
            $resultStream[2]["Stream"]["Link"]=$categoryIdLeftDn;
            $resultStream[3]["Stream"]["Link"]=$categoryIdRightDn;
            $count=sizeof($resultStream);
            print_r($resultStream);

            for ($i=0;$i<$count;$i++) 
                {
                if (isset($resultStream[$i]["Stream"]["Name"]))
                    {
                    /* echo die Stream OIDs auf Stream Link und damit in die einzelnen vier Fenster und die LinkVariablen in Data alle gemeinsam in den Overview, Stream Name als Überschrift */
                    CreateLink($resultStream[$i]["Stream"]["Name"], $resultStream[$i]["Stream"]["OID"],  $resultStream[$i]["Stream"]["Link"], 10+$i*10);
                    //$camID=CreateCategory($resultStream[$i]["Stream"]["Name"],$categoryIdOverview,$i*10);             // sonst entstehen parallele Tabs
                    if (isset($resultStream[$i]["Stream"]["Name"]))
                        { 
                        $camID=CreateVariable($resultStream[$i]["Stream"]["Name"],0, $categoryIdOverview,$i*10,"",null,null,"");
                        foreach ($resultStream[$i]["Data"] as $name=>$link) CreateLink($name, $link,  $camID, 10);
                        }
                    }
                }
            }
        }

    function installWebfrontPics($configWF,$CamTablePictureID)
        {
        $full=false;            // nur zusätzlich installieren

        $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.WebCamera

        if ($full) 
            {
            echo "installWebfront Path : ".$configWF["Path"]." with this Webfront Tabpane Item Name : ".$configWF["TabPaneItem"]."\n";
            echo "----------------------------------------------------------------------------------------------------------------------------------\n";            $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.WebCamera
            if ( exists_WFCItem($configWF["ConfigId"], $configWF["TabPaneItem"])==false )           /* nur wenn uebergeordnetes Webfront nicht da ist, die entsprechende Kategorie loeschen */
                {
                CreateWFCItemTabPane   ($configWF["ConfigId"], $configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // WebCamera Tabpane
                EmptyCategory($categoryId_WebFront);
                IPS_SetHidden($categoryId_WebFront, true); 		// in der normalen Viz Darstellung Kategorie verstecken
                }
            }
        
        $categoryId_WebFrontMonitor  = CreateCategory($configWF["TabItem"],  $categoryId_WebFront, 50);        // gleich wie das Tabitem beschriften, erleichtert die Wiedererkennung

        if ( exists_WFCItem($configWF["ConfigId"], $configWF["TabItem"]) )
            {
            echo "Einzel Tab loeschen und neu anlegen: DeleteWFCItems(".$configWF["ConfigId"].", ".$configWF["TabItem"].")\n";		
		    DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]);		// Einzel Tab loeschen und neu anlegen
            EmptyCategory($categoryId_WebFrontMonitor);
    	    IPS_SetHidden($categoryId_WebFrontMonitor, true); 		// in der normalen Viz Darstellung Kategorie verstecken
            }
		
        /* im TabPane entweder eine Kategorie oder ein SplitPane und Kategorien anlegen */
        CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"], $configWF["TabPaneItem"],   ($configWF["TabOrder"]+300), $configWF["TabItem"], $configWF["TabIcon"], $categoryId_WebFrontMonitor /*BaseId*/, 'false' /*BarBottomVisible*/ );

        // definition CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="") {
        CreateLinkByDestination("Pictures", $CamTablePictureID, $categoryId_WebFrontMonitor,  10,"");								
        }


?>