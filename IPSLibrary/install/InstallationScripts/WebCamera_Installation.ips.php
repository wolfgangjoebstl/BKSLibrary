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
     * 
     *
     * Verwendung von Activity für regelmaessige Timeraufrufe und Library für gemeinsame Routine und eine Class
     *
     * aus der OperationCenter Configuration werden die Cameras für die Live Überwachung genommen. Das sind alle lokalen Kameras mit lokalen IP Adressen.
     * Externe gehen auch aber die Bandbreite ist recht gross.
     * in der IPSCam können mehrere Kameras sein, auch welche die nur über extern erreichbar sind
	 *
	 *
	 * @file          WebCamera_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 *
	 **/

    // max. Scriptlaufzeit definieren
    ini_set('max_execution_time', 500);

	$debug=false;
    $startexec=microtime(true);     /* Laufzeitmessung */

    /*******************************
    *
    * Initialisierung, Modul Handling Vorbereitung
    *
    ********************************/

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("WebCamera_Configuration.inc.php","IPSLibrary::config::modules::WebCamera");
	IPSUtils_Include ("WebCamera_Library.inc.php","IPSLibrary::app::modules::WebCamera");

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

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
    $sysOps = new sysOps();

    $systemDir     = $dosOps->getWorkDirectory(); 
    echo "systemDir : $systemDir \n";           // systemDir : C:/Scripts/ 
    echo "Operating System : ".$dosOps->getOperatingSystem()."\n";

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
        IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
        IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");            

        $subnet="10.255.255.255";
        $OperationCenter=new OperationCenter($subnet);

        $moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
        $CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');
        $CategoryIdDataOverview=CreateCategory("Cams",$CategoryIdDataOC,20);    
        $webCamera = new webCamera();       // eigene class starten
        echo "\n";

        $LogFileHandler=new LogFileHandler($subnet);    // handles Logfiles und Cam Capture Files
		$log_Install=new Logging($systemDir."Install/Install".$HeuteString.".csv");								// mehrere Installs pro Tag werden zusammengefasst
		$log_Install->LogMessage("Install Module OperationCenter. Aktuelle Version ist $ergebnisVersion.");
   


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
            echo "IPSCam installiert, Ausgabe Konfiguration, nur zur Information, es gilt die Config im OperationCenter_Configuration File :\n";
            $CamConfig             = IPSCam_GetConfiguration();
            //print_r($CamConfig);
            foreach ($CamConfig as $cam) echo "    ".str_pad($cam["Name"],30).$cam["Component"]."\n";
            echo "\n";
            }

        /********************************************************************
        *
        * auch OperationCenter Installation mit betrachten, Config Dateien kommen von dort
        *
        *****************************************************************/

        $CategoryIdDataOverview=IPS_GetObjectIDByName("Cams",$CategoryIdDataOC);
        echo "IPS Path of OperationCenter Data Category : ".$CategoryIdDataOverview."  ".$ipsOps->path($CategoryIdDataOverview)."\n";

        $OperationCenterConfig = $OperationCenter->getConfiguration();

        echo "====================================================================================\n";
        echo "Ausgabe der OperationCenter spezifischen Cam Konfigurationsdaten:\n";
        $count=0;    
        $camConfig = $webCamera->getStillPicsConfiguration(true);               // true, Debug. Die Konfiguration so umdrehen das es statt dem Kameranamen einen einfachen Index gibt

        echo "====================================================================================\n";
        echo "Vorher ein bisschen Move von CamFiles Bilder vom FTP machen. nur so zum Ausprobieren:\n";
        foreach ($camConfig as $index => $cam_config)             /* das sind die Capture Dateien, die häufen sich natürlich wenn mehr Bewegung ist */
            {
            $cam_name=$cam_config['NAME'];    
            if (isset ($cam_config['FTPFOLDER']))         
                {
                if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                    {                        
                    echo "\n ==> Bearbeite LogFileHandler->MoveCamFiles für die Webkamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."       -> ".number_format((microtime(true)-$startexec),1)." Sekunden vergangen.\n";
                    $cam_config['CAMNAME']=$cam_name;
                    if (isset($cam_config["MOVECAMFILES"])) if ($cam_config["MOVECAMFILES"]) $count+=$LogFileHandler->MoveCamFiles($cam_config,false);        // true ist mit debug
                    $OperationCenter->PurgeCamFiles($cam_config,true);      // true for debug
                    }
                }
            }        

        $CamTablePictureID=CreateVariable("CamTablePicture",3, $CategoryIdDataOverview,0,"~HTMLBox",null,null,"");
        $CamMobilePictureID=CreateVariable("CamMobilePicture",3, $CategoryIdDataOverview,0,"~HTMLBox",null,null,"");

        echo "Kategorie Cams in OperationCenter Data : $CategoryIdDataOverview übernehmen und darin ein Objekt CamTablePicture mit OID $CamTablePictureID und OID $CamMobilePictureID für die Captured Bilder.\n";

        /**************************************************
        *
        * Überblick der Webcams angeführt nach den einzelnen IPCams in deren OperationCenter Konfiguration 
        * Darstellung erfolgt unabhängig von den Einstellungen in der Konfig des IPSCam Moduls
        *
        ******************************************************************************************************/

        $resultStream=array(); $idx=0;          // Link zu den Cameras
        $debug=false;

        echo "\n=============================================================\n";
        echo "Webcam Kamera Ausgabe für die folgenden Kameras konfigurieren:\n";
        foreach ($camConfig as $index => $cam_config)             /* das sind die Capture Dateien, die häufen sich natürlich wenn mehr Bewegung ist */
            {
            $cam_name=$cam_config['NAME'];    

            echo "   ---------------------------------\n";
            echo "   Bearbeite WebCamera $cam_name:       -> ".number_format((microtime(true)-$startexec),1)." Sekunden vergangen\n";
            if ($debug) print_r($cam_config);        
            if ( (isset($cam_config["FTP"])) && (strtoupper($cam_config["FTP"])=="ENABLED") ) 
                {
                if ($debug) echo "    Kamera FTP Server speichert für Kamera ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";  
                $verzeichnis = $cam_config['FTPFOLDER'];  
                if (is_dir($verzeichnis)) 
                    {
                    //$dir=$dosOps->readdirToArray($verzeichnis); print_r($dir);
                    $stat=$dosOps->readdirToStat($verzeichnis, true);
                    //print_r($stat);
                    //$lastupdate=$dosOps->latestChange($verzeichnis, true);
                    echo "       Verzeichnis $verzeichnis verfügbar. ".$stat["dirs"]." Verzeichnisse und ".$stat["files"]." Dateien (rekursiv ausgelesen). Latest File is from ".date("j.m.Y H:i:s",$stat["latestdate"]).".\n";
                    }
                else 
                    {
                    echo "       Fehler, Verzeichnis $verzeichnis NICHT verfügbar, jetzt erstellen.\n";
                    $rootDir='C:\\ftp\\';
                    if (is_dir($rootDir)) echo "      Verzeichnis $rootDir verfügbar. Das Unterverzeichnis erstellen.\n";
                    $dosOps->mkdirtree($verzeichnis,true);          // mit Debug um Fehler rauszufinden
                    }
                }
            else 
                {
                /* bei externen Kameras ist der FTP Folder nicht vorhanden */    
                //echo 'Fehler, FTP Funktion in der Konfiguration disabled. Füge "FTP" => "Enabled" ein.'."\n";
                }

            $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdDataOC);
            if ($cam_categoryId==false)
                {
                $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                IPS_SetParent($cam_categoryId,$CategoryIdDataOC);
                }
            if ($debug) echo "    Kategorie pro Kamera vorhanden: $cam_categoryId.  ".$ipsOps->path($cam_categoryId)."\n";

            if (isset($cam_config['FTPFOLDER']) )
                {
                $WebCam_LetzteBewegungID = IPS_GetObjectIdByName("Cam_letzteBewegung", $cam_categoryId); 
                $WebCam_PhotoCountID = IPS_GetObjectIdByName("Cam_PhotoCount", $cam_categoryId);
                $WebCam_MotionID = IPS_GetObjectIdByName("Cam_Motion", $cam_categoryId); 
                }

            /************************************************************************************************************** 
            *
            * Streaming aufbauen. Derzeit entweder http (MJpeg) oder rstp (h.264)
            *
            *
            *******************************************************************************************************/

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
                if ( (isset($cam_config["FORMAT"])) && (isset($cam_config["USERNAME"])) && (isset($cam_config["PASSWORD"]))
                                                                                        && (isset($cam_config["IPADRESSE"])) && (isset($cam_config["STREAMPORT"])))
                    {
                    if (isset($cam_config["TYPE"])) $type = strtoupper($cam_config["TYPE"]);
                    else $type="";
                    $know=false;
                    if ($debug) echo "    rtsp/http Stream link für $cam_streamId zusammenbauen. Camera Type is \"$type\":\n";
                    switch ($type)
                        {
                        /* http only */
                        case "INSTAR-3011":
                        case "INSTAR-6014":
                            if (isset($cam_config["COMPONENT"]))
                                {
                                if ($debug) echo "    $index  ".$cam_config["NAME"]."   ".$cam_config["COMPONENT"]."    ".number_format((microtime(true)-$startexec),1)." Sekunden   \n";
                                $component       = IPSComponent::CreateObjectByParams($cam_config["COMPONENT"]);
                                $size=0; $command=100;
                                //$urlPicture      = $component->Get_URLPicture($size);
                                $urlLiveStream   = $component->Get_URLLiveStream($size);
                                if ((isset($cam_config["DOMAINADRESSE"])))
                                    {
                                    $urlLiveStream .= 'http://'.$cam_config["DOMAINADRESSE"].'/videostream.cgi?user='.$cam_config["USERNAME"].'&pwd='.$cam_config["PASSWORD"].'&resolution='.$cam_config["STREAMPORT"];
                                    }
                                else $urlLiveStream   = $component->Get_URLLiveStream($size);
                                }
                            break;
                        /* rstp only */
                        case "REOLINK":
                            if (isset($cam_config["COMPONENT"]))
                                {
                                if ($debug) echo "    $index  ".$cam_config["NAME"]."   ".$cam_config["COMPONENT"]."    ".number_format((microtime(true)-$startexec),1)." Sekunden   \n";
                                $component       = IPSComponent::CreateObjectByParams($cam_config["COMPONENT"]);
                                $size=0; $command=100;
                                //$urlPicture      = $component->Get_URLPicture($size);
                                $urlLiveStream   = $component->Get_URLLiveStream($size);
                                }
                            break;
                        /* http or rstp, kann Component nicht verwenden */
                        case "INSTAR-8015":
                        case "INSTAR-8003":
                        case "INSTAR-5905":                    
                        
                            $know=true;
                        default:
                            echo $index."  ".$cam_config["NAME"]."   ".$cam_config["COMPONENT"]."    ".number_format((microtime(true)-$startexec),1)." Sekunden   \n";
                            if (!($know)) echo "     ===> do not know Type  \"$type\"\n";
                            $urlLiveStream="";
                            if (isset($cam_config["FORMAT"])) $streamFormat=strtoupper($cam_config["FORMAT"]);
                            else $streamFormat="";
                            if ($debug) echo "     Streamformat is \"$streamFormat\",\n";
                            if ($streamFormat=="RTSP")
                                {
                                $urlLiveStream .= 'rtsp://'.$cam_config["USERNAME"].':'.$cam_config["PASSWORD"].'@'.$cam_config["IPADRESSE"].':554'.$cam_config["STREAMPORT"];
                                }
                            elseif ($streamFormat=="HTTP")
                                {
                                //echo 'http MJPEG Stream link für $cam_streamId zusammenbauen. Sollte ähnlich lauten wie : http://10.0.1.121/videostream.cgi?user=admin&pwd=cloudg06&resolution=8'."\n";
                                if ((isset($cam_config["DOMAINADRESSE"])))
                                    {
                                    $urlLiveStream .= 'http://'.$cam_config["DOMAINADRESSE"].'/videostream.cgi?user='.$cam_config["USERNAME"].'&pwd='.$cam_config["PASSWORD"].'&resolution='.$cam_config["STREAMPORT"];
                                    }
                                else $urlLiveStream .= 'http://'.$cam_config["IPADRESSE"].'/videostream.cgi?user='.$cam_config["USERNAME"].'&pwd='.$cam_config["PASSWORD"].'&resolution='.$cam_config["STREAMPORT"];
                                }
                            else echo "Fehler, keine gültiges FORMAT mit $streamFormat angelegt. Verwende http oder rstp.\n"; 
                            break;
                        }           // ende switch

                    echo "       Streaming Media will be set to $urlLiveStream.\n";
                    IPS_SetMediaFile($cam_streamId,$urlLiveStream,true);

                    $resultStream[$idx]["Stream"]["OID"]=$cam_streamId;
                    $resultStream[$idx]["Stream"]["Name"]=$cam_name; 
                    $resultStream[$idx]["Data"]["Cam_letzteBewegung"]=$WebCam_LetzteBewegungID;
                    $resultStream[$idx]["Data"]["Cam_PhotoCount"]=$WebCam_PhotoCountID;
                    $resultStream[$idx]["Data"]["Cam_Motion"]=$WebCam_MotionID;
                    $idx++;
                    }
                else echo "    Kein Stream Parameter angelegt. Füge \"FORMAT\",\"USERNAME\",\"PASSWORD\",\"IPADRESSE\",\"STREAMPORT\" ein.\n"; 
                }
            else echo "     Kein Stream definiert. Füge \"STREAM\" => \"enabled\" ein.\n";
            }

        //echo "      Konfiguration ".$cam_name."\n"; print_r($cam_config);
        echo "\n";

        //print_r($resultStream);

        /************************************************************************************************************** 
        *
        * Stillpics und Capture Window aufbauen, so als Kür, gleiche Routinen wie im Timer.
        *
        *
        *******************************************************************************************************/

        echo "\n==========================================================\n";
        //echo "------StillPics View aufbauen, so wie auch im Timer\n";
        $zielVerzeichnis = $webCamera->zielVerzeichnis();
        $webCamera->DownloadImageFromCams($camConfig, $zielVerzeichnis);            // von allen Cameras ein Bild herunterladen, wenn zwei Kameras nicht erreichbar sind abbrechen
                
        echo "-------showCamSnapshots  \n";
        $OperationCenter->showCamSnapshots($camConfig,true);	
        
        /* die wichtigsten Capture Files auf einen Bildschirm je lokaler Kamera bringen */
        echo "-------showCamCaptureFiles  \n";
        $OperationCenter->showCamCaptureFiles($OperationCenterConfig['CAM'],true);

        /************************************************************************************************************** 
        *
        * Webfront Konfiguration aufbauen.
        *
        * Im array resultstream sind die Links die in jedem Webfront Administrator,User,Mobile gesetzt werden
        * es werden nur die Live monitore rtsp und http mjpeg 
        *
        *******************************************************************************************************/

        echo "============================================\n";
        echo "Webfront aufbauen für Monitor und StillPics.\n";
        
        //print_r($resultStream);           // für die Live Streams
        //echo $CamTablePictureID;          // für die regelmaessig upgedateten Pictures
        $CamTableCaptureID=$OperationCenter->getPictureFieldIDs($OperationCenterConfig['CAM'],true);
        print_R($CamTableCaptureID);

        $configWFront=$ipsOps->configWebfront($moduleManager);
        print_r($configWFront);

        if (isset($configWFront["Administrator"]))
            {
            $configWF = $configWFront["Administrator"];
            installWebfrontMon2($configWF,$resultStream,true);              // TabItem und TabIcon entsprechend der Konfiguration
            $configWF["TabItem"]="CamPicture";                          // different path item from WebCamera, local override for regularily loaded Pictures
            $configWF["TabIcon"]="Window";                              // different Icon
            installWebfrontPics($configWF,$CamTablePictureID);  
            $configWF["TabItem"]="CamCapture";                          // different path item from WebCamera, local override for captured selected Pictures
            $configWF["TabIcon"]="Window";                              // different Icon
            installWebfrontCaptures($configWF,$CamTableCaptureID,true);                
            }
            
        if (isset($configWFront["User"]))
            {
            $configWF = $configWFront["User"];            
            installWebfrontMon2($configWF,$resultStream,true); 
            
            $configWF["TabItem"]="CamPicture";                          // different path item from WebCamera, local override for regularily loaded Pictures
            $configWF["TabIcon"]="Window";                              // different Icon
            installWebfrontPics($configWF,$CamTablePictureID);            
            $configWF["TabItem"]="CamCapture";                          // different path item from WebCamera, local override for captured selected Pictures
            $configWF["TabIcon"]="Window";                              // different Icon
            installWebfrontCaptures($configWF,$CamTableCaptureID,true);                
            }

        if (isset($configWFront["Mobile"]))
            {
            $configWF=$configWFront["Mobile"];
            if ( (isset($configWF["Path"])) && (isset($configWF["PathOrder"])) && (isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false)) )
                {
                $categoryId_WebFront    = CreateCategoryPath($configWF["Path"],$configWF["PathOrder"],$configWF["PathIcon"]);        // Path=Visualization.Mobile.WebCamera    , 15, Image    
                $mobileId               = CreateCategoryPath($configWF["Path"].'.'.$configWF["Name"],$configWF["Order"],$configWF["Icon"]);        // Path=Visualization.Mobile.WebCamera    , 25, Image    
                $ipsOps->emptyCategory($mobileId);

                /* RTSP Streams werden noch nicht am Iphone angezeigt */

                if (sizeof($resultStream)>0) CreateLink($resultStream[0]["Stream"]["Name"], $resultStream[0]["Stream"]["OID"], $mobileId, 10);
                if (sizeof($resultStream)>1) CreateLink($resultStream[1]["Stream"]["Name"], $resultStream[1]["Stream"]["OID"], $mobileId, 20);
                if (sizeof($resultStream)>2) CreateLink($resultStream[2]["Stream"]["Name"], $resultStream[2]["Stream"]["OID"], $mobileId, 30);
                if (sizeof($resultStream)>3) CreateLink($resultStream[3]["Stream"]["Name"], $resultStream[3]["Stream"]["OID"], $mobileId, 40);

                $categoryId_WebFront    = CreateCategoryPath($configWF["Path"],$configWF["PathOrder"],$configWF["PathIcon"]);        // Path=Visualization.Mobile.WebCamera    , 15, Image    
                $mobileId               = CreateCategoryPath($configWF["Path"].'.KameraPics',$configWF["Order"],$configWF["Icon"]);        // Path=Visualization.Mobile.WebCamera    , 25, Image    
                $ipsOps->emptyCategory($mobileId);
                CreateLinkByDestination("Pictures", $CamMobilePictureID, $mobileId,  10,"");	
                }
            }
        }








/********************************************************************/







    /*******************************************************************
     *
     * eigenes generisches WebCamera Webfront aufbauen
     *
     *      CreateWFCItemTabPane      $configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // WebCamera Tabpane
     *        CreateWFCItemSplitPane  $configWF["TabItem"],     $configWF["TabPaneItem"],    ($configWF["TabOrder"]+200), "Monitor", $configWF["TabIcon"], 1 Vertical, 20 Width, 0 Target=Pane1, 0  UsePixel, 'true');  // Monitor Splitpane
     *
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

    function installWebfrontMon2($configWF,$resultStream, $emptyWebfrontRoot=false, $StartIndex=0)
        {
        $ipsOps=new ipsOps();
            
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
                $ipsOps->emptyCategory($categoryId_WebFront);
                IPS_SetHidden($categoryId_WebFront, true); 		// in der normalen Viz Darstellung Kategorie verstecken
                }

            echo "Create Sub-Category ".$configWF["TabItem"]." in ".IPS_GetName($categoryId_WebFront)." and empty it.\n";
            $categoryId_WebFrontMonitor  = CreateCategory($configWF["TabItem"],  $categoryId_WebFront, 10);        // gleich wie das Tabitem beschriften, erleichtert die Wiedererkennung
            IPS_SetHidden($categoryId_WebFrontMonitor, true);                                                      // nicht im OperationCenter anzeigen, eigener Tab
			$ipsOps->emptyCategory($categoryId_WebFrontMonitor);				        // ausleeren und neu aufbauen, die Geschichte ist gelöscht !

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

    /*******************************************************************
     *
     * selber Aufbau in jeder der Routinen, Input configWF wird abgeändert
     *     
     *      CreateWFCItemTabPane        $configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // WebCamera Tabpane
     *           CreateWFCItemCategory  $configWF["TabItem"],     $configWF["TabPaneItem"],   ($configWF["TabOrder"]+300), $configWF["TabItem"], $configWF["TabIcon"], $categoryId_WebFrontMonitor BaseId, 'false' BarBottomVisible );
     *
     **********************************/

    function installWebfrontPics($configWF,$CamTablePictureID)
        {
        $full=false;            // nur zusätzlich installieren, nicht notwendig macht WebfrontMon
        $ipsOps=new ipsOps();
        $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.WebCamera

        if ($full) 
            {
            echo "installWebfront Path : ".$configWF["Path"]." with this Webfront Tabpane Item Name : ".$configWF["TabPaneItem"]."\n";
            echo "----------------------------------------------------------------------------------------------------------------------------------\n";            $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.WebCamera
            if ( exists_WFCItem($configWF["ConfigId"], $configWF["TabPaneItem"]) )           /* nur wenn uebergeordnetes Webfront nicht da ist, die entsprechende Kategorie loeschen */
                {
                CreateWFCItemTabPane   ($configWF["ConfigId"], $configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // WebCamera Tabpane
                $ipsOps->emptyCategory($categoryId_WebFront);
                IPS_SetHidden($categoryId_WebFront, true); 		// in der normalen Viz Darstellung Kategorie verstecken
                }
            }
        
        $categoryId_WebFrontMonitor  = CreateCategory($configWF["TabItem"],  $categoryId_WebFront, 50);        // gleich wie das Tabitem beschriften, erleichtert die Wiedererkennung

        if ( ( exists_WFCItem($configWF["ConfigId"], $configWF["TabItem"]) ) && $full)
            {
            echo "Einzel Tab loeschen und neu anlegen: DeleteWFCItems(".$configWF["ConfigId"].", ".$configWF["TabItem"].")\n";		
		    DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]);		// Einzel Tab loeschen und neu anlegen
            $ipsOps->emptyCategory($categoryId_WebFrontMonitor);
    	    IPS_SetHidden($categoryId_WebFrontMonitor, true); 		// in der normalen Viz Darstellung Kategorie verstecken
            }
		
        /* im TabPane entweder eine Kategorie oder ein SplitPane mit Unterkategorien anlegen */
        CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"], $configWF["TabPaneItem"],   ($configWF["TabOrder"]+300), $configWF["TabItem"], $configWF["TabIcon"], $categoryId_WebFrontMonitor /*BaseId*/, 'false' /*BarBottomVisible*/ );

        // definition CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="") {
        CreateLinkByDestination("Pictures", $CamTablePictureID, $categoryId_WebFrontMonitor,  10,"");								
        }


    /************************
     *
     * Anlegen des Capture Overviews von allen Kameras, ähnlich wie im OperationCenter
     * Ziel ist es die Anzeige aus den IPSCam Webfroint Tab in das WebCamera Webfron hinüberzubringen
     *
     * einzelne Tabs pro Kamera mit den interessantesten Bildern der letzten Stunden oder Tage
     * die Daten werden aus den FTP Verzeichnissen gesammelt.
     *
     ************************/
							
    function installWebfrontCaptures($configWF,$CamTableCaptureID, $debug=false)
        {
        $full=false;
        $ipsOps=new ipsOps();
        if ( (isset($configWF["Path"])) && (isset($configWF["TabPaneItem"])) && (isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false)) && (isset($CamTableCaptureID["Base"])) )
            {
            /* im TabPane entweder eine Kategorie oder ein SplitPane mit Unterkategorien anlegen */
            $categoryId_WebFrontMonitor = $CamTableCaptureID["Base"];
            CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"], $configWF["TabPaneItem"],   ($configWF["TabOrder"]+600), $configWF["TabItem"], $configWF["TabIcon"], $categoryId_WebFrontMonitor /*BaseId*/, 'false' /*BarBottomVisible*/ );

        if (false)
            {    
			echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: \"".$WFC10Cam_Path."_Capture\"\n";			
			$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path."_Capture");
			EmptyCategory($categoryId_WebFrontAdministrator);
			IPS_SetHidden($categoryId_WebFrontAdministrator, true); 		// in der normalen Viz Darstellung Kategorie verstecken

			//CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
			CreateWFCItemTabPane  ($WFC10_ConfigId, "CamCapture", $WFC10Cam_TabPaneItem, ($WFC10Cam_TabOrder+1000), 'CamCapture', $WFC10Cam_TabIcon);
			if (isset ($OperationCenterConfig['CAM']))
				{
				$i=0;
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
					$i++; $found=false;
                    if (isset ($cam_config['FTPFOLDER']))         
                        {
                        if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                            {
                            echo "  Webfront Tabname für ".$cam_name." \n";
                            $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                            if ($cam_categoryId==false)
                                {
                                $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                                IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                                IPS_SetParent($cam_categoryId,$CategoryIdData);
                                }
                            $categoryIdCapture  = CreateCategory("Cam_".$cam_name,  $categoryId_WebFrontAdministrator, 10*$i);
                            CreateWFCItemCategory  ($WFC10_ConfigId, "Cam_".$cam_name,  "CamCapture",    (10*$i),  "Cam_".$cam_name,     $WFC10Cam_TabIcon, $categoryIdCapture /*BaseId*/, 'false' /*BarBottomVisible*/);
                            echo "     CreateWFCItemCategory  ($WFC10_ConfigId, Cam_$cam_name,  CamCapture,    ".(10*$i).",  Cam_$cam_name,     $WFC10Cam_TabIcon, $categoryIdCapture, false);\n";
                            $pictureFieldID = CreateVariable("pictureField",   3 /*String*/,  $categoryIdCapture, 50 , '~HTMLBox',null,null,"");
                            $box='<iframe frameborder="0" width="100%">     </iframe>';
                            SetValue($pictureFieldID,$box);
                            $found=true;
                            }
                        }
                    if (!$found)
                        {
                        echo "  Webfront Tabname für ".$cam_name." wird nicht mehr benötigt, loeschen.\n";
                        $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                        if ($cam_categoryId !== false)
                            {
                            DeleteWFCItems($WFC10_ConfigId, "Cam_".$cam_name);    
                            }
                        }
					}
				}
            }  // false

            }
        }



?>