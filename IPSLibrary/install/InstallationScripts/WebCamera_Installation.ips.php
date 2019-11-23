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
	 *
	 * @file          WebCamera_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 *
	 **/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\Configuration.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\WebLinks\WebLinks_Configuration.inc.php");

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\OperationCenter\OperationCenter_Configuration.inc.php");
	IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

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

	echo "IP Symcon Daten:\n";
	echo "  Kernelversion : ".IPS_GetKernelVersion()."\n";
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "  Modulversion  : ".$ergebnis."\n";
    echo "\n";

    /********************************************************************
     *
     * auch OperationCenter Installation mit betrachten 
     *
     *****************************************************************/

    $moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
    $CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');

    /* auch IPSCam Installation mit betrachten */

    $repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
	$moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);

    $ipsOps = new ipsOps();
    $dosOps = new dosOps();

    $CategoryIdDataOverview=IPS_GetObjectIDByName("Cams",$CategoryIdDataOC);
    echo "IPS Path of OperationCenter Data Category : ".$CategoryIdDataOverview."  ".$ipsOps->path($CategoryIdDataOverview)."\n";

	/*******************************
     *
     * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
     *
     ********************************/

	//echo "\n";
	//$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	//echo "Default WFC10_ConfigId fuer OperationCenter, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
    //echo "(".memory_get_usage()." Byte).\n";	

    $subnet="10.255.255.255";
    $OperationCenter=new OperationCenter($subnet);
    $OperationCenterConfig = $OperationCenter->getConfiguration();

    echo "====================================================================================\n";
    echo "Ausgabe der OperationCenter spezifischen Cam Konfigurationsdaten:\n";
    if (isset ($OperationCenterConfig['CAM']))
        {
        $CategoryIdDataOverview=IPS_GetObjectIdByName("Cams",$CategoryIdDataOC);
        $CamTablePictureID=IPS_GetObjectIdByName("CamTablePicture",$CategoryIdDataOverview);
        $CamMobilePictureID=IPS_GetObjectIdByName("CamMobilePicture",$CategoryIdDataOverview);
        echo "    Kategorie Cams in OperationCenter Data : $CategoryIdDataOverview und darin ein Objekt CamTablePicture mit OID $CamTablePictureID / $CamMobilePictureID für die Captured Bilder.\n";

        //$OperationCenter->CopyCamSnapshots(); // bereits in die Timer Routine übernommen
        
        /* Überblick der Webcams angeführt nach den einzelnen IPCams in deren OperationCenter Konfiguration 
         * Darstellung erfolgt unabhängig von den Einstellungen in der Konfig des IPSCam Moduls
         */
		$resultStream=array(); $idx=0;          // Link zu den Cameras

        echo "    CamCapture Ausgabe für folgende Kameras konfiguriert:\n";
        echo "\n";
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
                    echo "    Verzeichnis $verzeichnis NICHT verfügbar, jetzt erstellen.\n";
                    $rootDir='C:\\ftp\\';
                    if (is_dir($rootDir)) echo "    Verzeichnis $rootDir verfügbar.\n";
                    $dosOps->mkdirtree($verzeichnis,true);          // mit Debug um Fehler rauszufinden
                    }
                }
            else echo '       FTP Folder in der Konfiguration disabled. Füge "FTP" => "Enabled" ein.'."\n";

            $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdDataOC);
            if ($cam_categoryId==false)
                {
                $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                IPS_SetParent($cam_categoryId,$CategoryIdDataOC);
                }
            echo "Kategorie pro Kamera : $cam_categoryId.  ".$ipsOps->path($cam_categoryId)."\n";
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
                    $ipadresse=explode(".",$cam_config["IPADRESSE"]);
                    if (count($ipadresse)==4) 
                        {
                        $ok=true;
                        foreach ($ipadresse as $ip)
                            {
                            $ipNum=(integer)$ip;
                            if ( ($ipNum !== false) && ($ipNum < 256) ) ; else $ok=false;
                            }
                        if ($ok) echo "IPADRESSE ".$cam_config["IPADRESSE"]." hat ".count($ipadresse)." numerische Eintraege.";
                            {
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
                            }
                        }
                    }
                
                }

            echo "      Konfiguration ".$cam_name."\n";
            print_r($cam_config);
            echo "\n";

            }
        print_R($resultStream);

    $configWFront=$ipsOps->configWebfront($moduleManager);
    print_r($configWFront);

    if (isset($configWFront["Administrator"]))
        {
        $configWF = $configWFront["Administrator"];
        installWebfrontMon($configWF,$resultStream);             
        $configWF["TabItem"]="CamPicture";                          // different path item from WebCamera, local override for regularily loaded Pictures
        $configWF["TabIcon"]="Window";                              // different Icon
        installWebfrontPics($configWF,$CamTablePictureID);        
        }
        
    if (isset($configWFront["User"]))
        {
        $configWF = $configWFront["User"];            
        installWebfrontMon($configWF,$resultStream); 
        
        $configWF["TabItem"]="CamPicture";                          // different path item from WebCamera, local override for regularily loaded Pictures
        $configWF["TabIcon"]="Window";                              // different Icon
        installWebfrontPics($configWF,$CamTablePictureID);            
        }

    if (isset($configWFront["Mobile"]))
        {
        $configWF=$configWFront["Mobile"];
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









/********************************************************************/


    function installWebfrontMon($configWF,$resultStream)
        {
        $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.WebCamera
        
        //$tabItem = $configWF["TabPaneItem"].$configWF["TabItem"];																				
		//echo "installWebfront: WF10 Path for WebCamera Monitor         : ".$configWF["Path"]." with this Webfront item name : $tabItem\n";
        echo "installWebfront Path : ".$configWF["Path"]." with this Webfront Tabpane Item Name : ".$configWF["TabPaneItem"]."\n";
        echo "----------------------------------------------------------------------------------------------------------------------------------\n";

        echo "Kategorie $categoryId_WebFront (".IPS_GetName($categoryId_WebFront).") Inhalt loeschen und verstecken. Es dürfen keine Unterkategorien enthalten sein, sonst nicht erfolgreich.\n";
        $status=@EmptyCategory($categoryId_WebFront);
        if ($status) echo "   -> erfolgreich.\n";
    	IPS_SetHidden($categoryId_WebFront, true); 		// in der normalen Viz Darstellung Kategorie verstecken
        
        $categoryId_WebFrontMonitor  = CreateCategory($configWF["TabItem"],  $categoryId_WebFront, 10);        // gleich wie das Tabitem beschriften, erleichtert die Wiedererkennung

        $categoryIdOverview  = CreateCategory('Overview',  $categoryId_WebFrontMonitor, 0);             // links davon, um die Cam Bilder in die richtige Größe zu bringen, für Summaries
        $categoryIdLeftUp  = CreateCategory('LeftUp',  $categoryId_WebFrontMonitor, 10);
        $categoryIdRightUp = CreateCategory('RightUp', $categoryId_WebFrontMonitor, 20);						
        $categoryIdLeftDn  = CreateCategory('LeftDn',  $categoryId_WebFrontMonitor, 30);
        $categoryIdRightDn = CreateCategory('RightDn', $categoryId_WebFrontMonitor, 40);						

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
        for ($i=0;$i<$count;$i++) 
            {
            if (isset($resultStream[$i]["Stream"]["Name"]))
                {
                CreateLink($resultStream[$i]["Stream"]["Name"], $resultStream[$i]["Stream"]["OID"],  $resultStream[$i]["Stream"]["Link"], 10+$i*10);
                //$camID=CreateCategory($resultStream[$i]["Stream"]["Name"],$categoryIdOverview,$i*10);             // sonst entstehen parallele Tabs 
                $camID=CreateVariable($resultStream[$i]["Stream"]["Name"],0, $categoryIdOverview,$i*10,"",null,null,"");
                foreach ($resultStream[$i]["Data"] as $name=>$link) CreateLink($name, $link,  $camID, 10);
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