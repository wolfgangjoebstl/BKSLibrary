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

/* overview of cam Status   
 *
 * es gibt IPS Cam als externes Modul, hier werden pro Kamera Einstellungen im Webfront vorgesehen
 *
 * im OperationCenter gibt es die Livestreamdarstellung und eine Capturebilder Darstellung
 *
 *
 */


Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");

IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('WebCamera',$repository);
        }

    $installedModules = $moduleManager->GetInstalledModules();

    $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
    $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
    $CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');

    $repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
	$moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);

	echo "IP Symcon Daten:\n";
	echo "  Kernelversion : ".IPS_GetKernelVersion()."\n";
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "  Modulversion  : ".$ergebnis."\n";

	/*******************************
     *
     * Init, wichtige Variablen
     *
     ********************************/

    $ipsOps = new ipsOps();
    $dosOps = new dosOps();

    $CategoryIdDataOverview=IPS_GetObjectIDByName("Cams",$CategoryIdDataOC);
    echo "IPS Path of OperationCenter Data Category : ".$CategoryIdDataOverview."  ".$ipsOps->path($CategoryIdDataOverview)."\n";

    //echo "(".memory_get_usage()." Byte).\n";	

    $subnet="10.255.255.255";
    $OperationCenter=new OperationCenter($subnet);
    $OperationCenterConfig = $OperationCenter->getConfiguration();

	/*******************************
     *
     * Webfront, Action Routines
     *
     ********************************/

    if ($_IPS['SENDER']=="WebFront")
        {
        /* vom Webfront aus gestartet */

        SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
        
        }
    else
        {

        /*******************************************
        *
        * Media Objects Vorbereitung, Evaluierung
        *
        **********************************************************/

        $mediaFound=$ipsOps->getMediaListbyType(3);     // get Streams out of MediaList

        echo "\n";
        echo "Anzahl Eintraege Streaming Media ".count($mediaFound)."\n";
        $module=array();
        foreach ($mediaFound as $media)
            {
            $objectR=$media;
            echo " $media ";
            echo "   ".IPS_GetName($objectR);
            $moduleName="";
            while ($objectR=IPS_GetParent($objectR))
                {
                if (IPS_GetName($objectR)=="modules") $moduleName=$last;
                $last=IPS_GetName($objectR);
                echo ".".$last;
                }
            echo ".".IPS_GetName($objectR);
            echo "         $moduleName    ".IPS_GetMedia($media)["MediaFile"];
            echo "\n";
            $module[$moduleName][$media]=IPS_GetName($media);
            }
        echo "\n";
        echo "Auflistung, Strukturierung anhand Parent Verzeichnis = Module:\n";
        print_r($module);

        /*******************************
         *
         * Kamerakonfiguration auswerten
         *
         ********************************/

        echo "====================================================================================\n";
        echo "Ausgabe der OperationCenter spezifischen Cam Konfigurationsdaten:\n";
        if (isset ($OperationCenterConfig['CAM']))
            {

            //$OperationCenter->CopyCamSnapshots(); // bereits in die Timer Routine übernommen
            
            /* Überblick der Webcams angeführt nach den einzelnen IPCams in deren OperationCenter Konfiguration 
             * Darstellung erfolgt unabhängig von den Einstellungen in der Konfig des IPSCam Moduls
             * es werden die OperationCenter Data Objekte verwendet. Keine Objekte im data von Webcamera anlegen
             */
            $resultStream=array(); $idx=0;          // Link zu den Cameras

            echo "CamCapture Ausgabe FTP übertragene Bilder/Videos für folgende Kameras konfiguriert:\n";
            echo "\n";
            foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
                {
                echo "   ---------------------------------\n";
                echo "   Bearbeite WebCamera $cam_name:\n";
                if ( (isset($cam_config["FTP"])) && (strtoupper($cam_config["FTP"])=="ENABLED") ) 
                    {
                    echo "     Kamera, FTP Server Verzeichnis: ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";  
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
                        echo "    Verzeichnis $verzeichnis NICHT verfügbar, sollte erstellt werden.\n";
                        $rootDir='C:\\ftp\\';
                        if (is_dir($rootDir)) echo "    Verzeichnis $rootDir verfügbar.\n";
                        //$dosOps->mkdirtree($verzeichnis,true);          // mit Debug um Fehler rauszufinden
                        }
                    }
                else echo "       FTP Folder disabled.\n";

                $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdDataOC);
                if ($cam_categoryId==false)
                    {
                    echo "              !!! Fehler, eigene Kategorie pro Kamera muss vorhanden sein.\n";
                    //$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                    //IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                    //IPS_SetParent($cam_categoryId,$CategoryIdData);
                    }
                echo "        Kategorie pro Kamera : $cam_categoryId   -> Pfad: ".$ipsOps->path($cam_categoryId)."\n";
                $WebCam_LetzteBewegungID = IPS_GetObjectIdByName("Cam_letzteBewegung", $cam_categoryId); 
				$WebCam_PhotoCountID = IPS_GetObjectIdByName("Cam_PhotoCount", $cam_categoryId);
				$WebCam_MotionID = IPS_GetObjectIdByName("Cam_Motion", $cam_categoryId); 
                echo "            Status Bewegung             : ".(GetValue($WebCam_MotionID)?"Ja":"Nein")."\n";
                //echo "            Letzte erkannte Bewegung    : ".date("D d.m.Y H:i:s",GetValue($WebCam_LetzteBewegungID))."\n";
                echo "            Letzte erkannte Bewegung    : ".GetValue($WebCam_LetzteBewegungID)."\n";
                echo "            Anzahl erfasste Bilder      : ".GetValue($WebCam_PhotoCountID)."\n";
                if ( (isset($cam_config["STREAM"])) && (strtoupper($cam_config["STREAM"])=="ENABLED") ) 
                    {
                    $cam_streamId=@IPS_GetObjectIDByName("CamStream_".$cam_name,$cam_categoryId);                  
                    if ($cam_streamId===false)
                        {  
                        echo "              !!! Fehler, eigenes Media Objekt mit CamStream_$cam_name in dieser Kategorie ist zu erstellen mit entsprechendem Streaming Link.\n";
                        //$cam_streamId=IPS_CreateMedia(3);
                        //IPS_SetName($cam_streamId, "CamStream_".$cam_name);     // Media Stream Objekt benennen
                        //IPS_SetParent($cam_streamId, $cam_categoryId);
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
                                    echo "    Streaming Media shall be set to $streamLink.\n";
                                    //IPS_SetMediaFile($cam_streamId,$streamLink,true);

                                    $resultStream[$idx]["Stream"]["OID"]=$cam_streamId;
                                    $resultStream[$idx]["Stream"]["Name"]=$cam_name;                                
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
                
            echo "\n";
            echo "Das sind die installierten WebCams.\n";
            print_R($resultStream);
        
            }           // im Modul OperationCenter sind Cameras konfiguriert

        $WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
        $structur = $ipsOps->readWebfrontConfig($WFC10_ConfigId,false);         // Debug false
        echo "Webfront, komplette Struktur ausgeben für Webfront ".IPS_GetName($WFC10_ConfigId).":\n";
        print_r($structur);

        echo "Webfront $WFC10_ConfigId ".IPS_GetName($WFC10_ConfigId)."\n";
        foreach ($structur as $root => $entries) 
            {
            echo "  Ausgabe für Name Wurzel : $root\n";
            foreach ($entries as $TabPane => $entry)
                {
                echo "    $TabPane    \n";
                }
            }

        /* die drei betroffenen Module */

        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        $moduleManager = new IPSModuleManager('WebCamera',$repository);
        $moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
        $repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
        $moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);

        $result=array();
        $result["WebCamera"]=$ipsOps->configWebfront($moduleManager);
        $result["OperationCenter"]=$ipsOps->configWebfront($moduleManagerOC);
        $result["IPSCam"]=$ipsOps->configWebfront($moduleManagerCam);        
        print_r($result);
        }


?>