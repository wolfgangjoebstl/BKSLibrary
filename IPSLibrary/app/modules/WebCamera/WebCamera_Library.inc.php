<?php

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

/* WebCamera Library   
 *
 * es gibt IPS Cam als externes Modul, hier werden pro Kamera Einstellungen im Webfront vorgesehen
 * im OperationCenter gibt es die Livestreamdarstellung und eine Capturebilder Darstellung
 * WebCamera ist dazu ein eigenständiges Modul das aktuell noch Funktionen aus den beiden anderen Programmen übernimmt
 *
 * __construct
 * getConfiguration
 * getStillPicsConfiguration
 * getComponentIntern
 * getComponentExtern
 * IPAdrQuickCheck
 * getCategoryIdData
 * zielVerzeichnis
 * DownloadImageFromCams
 * DownloadImageFromCam
 * GetLoggingTextFromURL
 *
 */


class WebCamera
    {

    var $CategoryIdData,  $CategoryIdApp;               // zur Orientierung, wo ist was
    var $CategoryIdDataOC;

    var $categoryId_CamPictures, $camIndexID;           // Variablen für die StillPics downloads

    var $camConfiguration;                              // die Konfiguration

    var $ipsOps, $dosOps;                               // praktische Module

    function __construct()
        {
        echo "Webcamera Create:\n";            
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        if (!isset($moduleManager))
            {
            IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
            $moduleManager = new IPSModuleManager('WebCamera',$repository);
            }
        $installedModules = $moduleManager->GetInstalledModules();

        $this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
        $this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    	$this->categoryId_CamPictures	= CreateCategory('CamPictures',   $this->CategoryIdData, 230);
	    $this->camIndexID   			= CreateVariableByName($this->categoryId_CamPictures, "Hostname", 1, "", "", 1000); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */

        /*******************************
        *
        * Init, wichtige Variablen
        *
        ********************************/

        $this->ipsOps = new ipsOps();
        $this->dosOps = new dosOps();

        $this->camConfiguration = array();
        if (isset($installedModules["OperationCenter"]) )
            {
            //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\OperationCenter\OperationCenter_Configuration.inc.php");
            IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");            
            IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
            
            $moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
            $this->CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');

            $categoryIdCams=IPS_GetObjectIDByName("Cams",$this->CategoryIdDataOC);          // im OperationCenter/data

            $subnet="10.255.255.255";
            $OperationCenter=new OperationCenter($subnet);
            $OperationCenterConfig = $OperationCenter->getConfiguration();

            /* Ausgabe der OperationCenter spezifischen Cam Konfigurationsdaten */
            if (isset ($OperationCenterConfig['CAM']))
                {
                $this->camConfiguration=$OperationCenterConfig['CAM'];
                }
            $camConfig = $this->getStillPicsConfiguration(false);                // true Debug Ausgabe, um alle konfigurierten Kameras zu sehen
            foreach ($camConfig as $index=>$data) 
                {
                $PictureTitleID=CreateVariable("CamPictureTitle".$index,3, $categoryIdCams,100,"",null,null,"");        // string
                $PictureTimeID =CreateVariable("CamPictureTime".$index,1, $categoryIdCams,101,"",null,null,"");         // integer, time
                }
            }
        }

    /* aktuell gilt die Operation Center Configuration als die treibende Konfiguration. 
     * Es sollen so gut wie möglich alle Configurationen hier zusammengefasst werden. 
     */

    public function getConfiguration()
        {
        return ($this->camConfiguration);
        }

    /* aus der Operation Center Configuration die Component Information zusammenstellen
     * und die Anordnung erfolgt nicht mehr nach Namen sondern nach Indexnummern. Wichtig wenn nicht alle Kameras in einem Durchlauf gemacht werden können
     * Wenn keine IPSCam oder so installiert, definiert, dann ist das Array leer
     * Eintraege ohne "COMPONET" nicht mehr igorieren
     *
     */

    public function getStillPicsConfiguration($debug=false)
        {
        $i=0; $configCamPicture=array();
        foreach($this->camConfiguration as $camera => $entry) 
            {
            if (isset($entry["COMPONENT"])) 
                {
                $alt=false;                    // Berechnung von alternativem Zugang über lokale IP Adresse
                if ($debug) echo "    $i : ".str_pad($camera,30)." ".$entry["COMPONENT"]." (vorher)\n";
                $componentDef=explode(",",$entry["COMPONENT"]);
                //print_r($componentDef);        // 0 component 1 domainname:port 2 user 3 password  , 1 bis 3 aus den anderen Angaben vervollstaendigen 
                if (isset($entry["DOMAINADRESSE"])) 
                    {
                    $componentDef[1]=$entry["DOMAINADRESSE"];
                    if (isset($entry["IPADRESSE"])) $alt=true;                         // alternative interne IP Adresse definieren
                    }
                elseif (isset($entry["IPADRESSE"])) $componentDef[1]=$entry["IPADRESSE"];
                else 
                    {
                    if ($debug) echo "                                    keine detaillierten Angaben zur Netzwerk Adresse, Component nicht überschreiben, es bleibt bei ".$componentDef[1]."\n";
                    }
                if (isset($entry["USERNAME"])) $componentDef[2]=$entry["USERNAME"];
                else
                    {
                    if ($debug)  echo "                                    keine detaillierten Angaben zum Usernamen, Component nicht überschreiben, es bleibt bei ".$componentDef[2]."\n";
                    }
                if (isset($entry["PASSWORD"])) $componentDef[3]=$entry["PASSWORD"];
                else 
                    {
                    if ($debug)  echo "                                    keine detaillierten Angaben zum Passwort, Component nicht überschreiben, es bleibt bei ".$componentDef[3]."\n";
                    }
                $entry["COMPONENT"]=implode(",",$componentDef);
                if ($alt)
                    {
                    $componentDefAlt=$componentDef;
                    $componentDefAlt[1]=$entry["IPADRESSE"];
                    $entry["COMPONENTALT"]=implode(",",$componentDefAlt);
                    if ($debug) echo "        ".str_pad("",30)." ".$entry["COMPONENT"]." und alternativ auch ".$entry["COMPONENTALT"]."\n";
                    }
                elseif ($debug) echo "        ".str_pad("",30)." ".$entry["COMPONENT"]."\n";
                }
            elseif ($debug) echo "    $i : ".str_pad($camera,30)." ".$entry["IPADRESSE"]."\n";
            $configCamPicture[$i]=$entry;
            $configCamPicture[$i]["NAME"]=$camera;          // sonst wird es woeder überschrieben
            $i++;
            }
        return($configCamPicture);
        }

    /* etwas schwierig mit den Components:
     * rstp streams werden vom IP Symcom Server umgesetzt, daher immer lokale IP Adresse
     * http mjpeg streams greifen direkt zu, daher externe IP Adresse
     *
     */

    public function getComponentIntern($entry)
        {
        if (isset($entry["COMPONENT"])) 
            {
            $componentDef=explode(",",$entry["COMPONENT"]);
            //print_r($componentDef);        // 0 component 1 domainname:port 2 user 3 password  , 1 bis 3 aus den anderen Angaben vervollstaendigen 
            if (isset($entry["DOMAINADRESSE"])) $componentDef[1]=$entry["DOMAINADRESSE"];
            else echo "Component nicht überschreiben, es bleibt bei ".$componentDef[1]."\n";
            if (isset($entry["USERNAME"])) $componentDef[2]=$entry["USERNAME"];
            else echo "Component nicht überschreiben, es bleibt bei ".$componentDef[2]."\n";
            if (isset($entry["PASSWORD"])) $componentDef[3]=$entry["PASSWORD"];
            else echo "Component nicht überschreiben, es bleibt bei ".$componentDef[3]."\n";
            $entry["COMPONENT"]=implode(",",$componentDef);
            return($entry);
            }
        else return (false); 
        }

    public function getComponentExtern($entry)
        {
        if (isset($entry["COMPONENT"])) 
            {
            if (IPAdrQuickCheck($entry["IPADRESSE"])==false) unset($entry["IPADRESSE"]);                
            $componentDef=explode(",",$entry["COMPONENT"]);
            //print_r($componentDef);        // 0 component 1 domainname:port 2 user 3 password  , 1 bis 3 aus den anderen Angaben vervollstaendigen 
            if (isset($entry["IPADRESSE"])) $componentDef[1]=$entry["IPADRESSE"];
            else echo "Component nicht überschreiben, es bleibt bei ".$componentDef[1]."\n";
            if (isset($entry["USERNAME"])) $componentDef[2]=$entry["USERNAME"];
            else echo "Component nicht überschreiben, es bleibt bei ".$componentDef[2]."\n";
            if (isset($entry["PASSWORD"])) $componentDef[3]=$entry["PASSWORD"];
            else echo "Component nicht überschreiben, es bleibt bei ".$componentDef[3]."\n";
            $entry["COMPONENT"]=implode(",",$componentDef);
            return($entry);
            }
        else return (false); 
        }

    /* a few checks, whether it is a IP Address */

    public function IPAdrQuickCheck($ip)
        {
        $ok=true;
        $ipadresse=explode(".",$ip);
        if (count($ipadresse)==4) 
            {
            foreach ($ipadresse as $ip)
                {
                $ipNum=(integer)$ip;
                if ( ($ipNum !== false) && ($ipNum < 256) ) ; else $ok=false;
                }
            }
        else $ok=false;
        return ($ok);
        }

    /* Data Category ausgeben */

    public function getCategoryIdData()
        {
        return ($this->CategoryIdData);
        }

    /* Zielverzeichnis für Anzeige ermitteln */

    public function zielVerzeichnis()
        {
        $picVerzeichnis="user/OperationCenter/AllPics/";
        $picVerzeichnisFull=IPS_GetKernelDir()."webfront/".$picVerzeichnis;
        $picVerzeichnisFull = str_replace('\\','/',$picVerzeichnisFull);            
        return ($picVerzeichnisFull);
        }

    /***************************
     *  Download alle StillPics
     *  wird von der TimerRoutine WebCamera_Activity aufgerufen
     *
     *
     *
     *******************/

    function DownloadImageFromCams($camConfig, $zielVerzeichnis)
        {
        $startexec=microtime(true);            
        $maxCount = count($camConfig);    
        echo "StillPics download from $maxCount Cams to $zielVerzeichnis\n";
        $j=GetValue($this->camIndexID);
        for ($i=0; $i<$maxCount; $i++)
            {
            $Cam=$camConfig[$j];
            if (isset($Cam["COMPONENT"]))
                {
                echo $j."  ".$Cam["NAME"]."   ".$Cam["COMPONENT"]."    ".number_format((microtime(true)-$startexec),1)." Sekunden   \n";

                /* 
                $componentDef=explode(",",$Cam["COMPONENT"]);
                print_r($componentDef);        // 0 component 1 domainname:port 2 user 3 password  , 1 bis 3 aus den anderen Angaben vervollstaendigen 

                $component       = IPSComponent::CreateObjectByParams($Cam["COMPONENT"]);
                $size=0; $command=100;
                $urlPicture      = $component->Get_URLPicture($size);
                $urlLiveStream   = $component->Get_URLLiveStream($size);
                $urlCommand      = $component->Get_URL($command); // zum steuern wenn beweglich
                //Get_Width, Get_Height

                Livestream
                Instar       small||medium||large    /videostream.cgi?user=admin&pwd=cloudg06&resolution={8||8||32}
                Instar 720p  small||medium||large    /cgi-bin/hi3510/mjpegstream.cgi?-chn={13||12||11}&-usr=admin&-pwd=cloudg06
                Instar 1080p small||medium||large    /mjpegstream.cgi?-chn={13||12||11}&-usr=admin&-pwd=cloudg06
                                small||medium||large    rtsp://admin:instar@IP-Address:RTSP-Port/{13||12||11}
                Reolink                              rtsp://admin:111111@192.168.0.110:554//h264Preview_01_main
                                        Sub Stream:  rtsp://admin:111111@192.168.0.110:554//h264Preview_01_sub      
                        
                pictures
                Instar                             /snapshot.cgi?user=admin&pwd=cloudg06&next_url=snapshot.jpg
                Instar  720p small||medium||large  /tmpfs/{auto2.jpg||auto.jpg||snap.jpg}?usr=admin&pwd=cloudg06 
                Instar 1080p small||medium||large  /tmpfs/{auto2.jpg||auto.jpg||snap.jpg}?usr=admin&pwd=cloudg06 
                Reolink                            /cgi-bin/api.cgi?cmd=Snap&channel=0&rs=(any combination of numbers and letters)&user=admin&password=cloudg06


                */

                //print_R($Cam);
                $status = $this->DownloadImageFromCam($j, $Cam, $zielVerzeichnis, 2, "Cam".$j.".jpg");         // $Cam ist Camer Configuration
                if ($status === false) 
                    {
                    echo "    Download Image from Camera ".$Cam["NAME"]." mit ".$Cam["COMPONENT"]." nicht erfolgreich.\n";
                    if (isset($Cam["COMPONENTALT"])) 
                        {
                        $Cam["COMPONENT"]=$Cam["COMPONENTALT"];
                        $status = $this->DownloadImageFromCam($j, $Cam, $zielVerzeichnis, 2, "Cam".$j.".jpg", true);     // true Debug
                        if ($status === false) echo "    Download Image (alt. try) from Camera ".$Cam["NAME"]." mit ".$Cam["COMPONENT"]." nicht erfolgreich.\n";
                        }
                    }
                }                   // kein Component verfügbar , der Nächste bitte
            $j++;
            if ($j==$maxCount) $j=0;                // Beim nächsten Mal gehts hier weiter. Bei Timeouts einfach mit der nächsten Kamera weitermachen
            SetValue($this->camIndexID,$j);
            if ((microtime(true)-$startexec) > 25)  return (false);
            }
        return (true);   
        }

    /* StillPics Download von einer Camera mit Index
     *
     * Den richtigen Befehl aus dem Component ableiten und aufrufen. 
     * Wenn es zu lange dauert Abbruch !
     *
     */

    function DownloadImageFromCam($cameraIdx, $componentParams, $directoryName, $size, $fileName, $debug=false) 
        {
        echo "DownloadImageFromCam für Camera $cameraIdx aufgerufen:\n";
        if (isset($componentParams["COMPONENT"]))
            {
            $categoryIdCams     		= IPS_GetObjectIDByName('Cams',    $this->CategoryIdDataOC);
            $PictureTitleID             = IPS_GetObjectIDByName("CamPictureTitle".$cameraIdx, $categoryIdCams);        // string
            $PictureTimeID              = IPS_GetObjectIDByName("CamPictureTime".$cameraIdx, $categoryIdCams);         // integer, time                    
            
            $result = IPS_SemaphoreEnter('IPSCam_'.$cameraIdx, 5000);
            if ($result) 
                {
                //$componentParams = $this->config[$cameraIdx][IPSCAM_PROPERTY_COMPONENT];
                $component       = IPSComponent::CreateObjectByParams($componentParams["COMPONENT"]);
                $urlPicture      = $component->Get_URLPicture($size);
                //$localFile       = IPS_GetKernelDir().'Cams/'.$cameraIdx.'/'.$directoryName.'/'.$fileName.'.jpg';
                $localFile       = $directoryName.$fileName;
                if ($debug) 
                    {
                    echo "Bearbeite Kamera IPSCam_$cameraIdx (".$componentParams["NAME"].") mit folgenden Parametern aus der Konfiguration:\n"; 
                    print_r($componentParams);
                    echo "   DownloadImageFromCam, $cameraIdx with $urlPicture to $directoryName$fileName  \n";
                    }
                IPSLogger_Dbg(__file__, "WebCamera Copy ".$this->GetLoggingTextFromURL($urlPicture)." --> $localFile");         // Debug damit im Info Log nicht zuviele Ausgaben sind

                $curl_handle=curl_init();
                curl_setopt($curl_handle, CURLOPT_URL, $urlPicture);
                curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($curl_handle, CURLOPT_TIMEOUT, 30);  
                curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,true);
                curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl_handle, CURLOPT_FAILONERROR, true);
                $fileContent = curl_exec($curl_handle);                                 // hier lauft die Routine manchmal ins Tiemout
                curl_close($curl_handle);

                if ($fileContent===false) 
                    {
                    IPS_SemaphoreLeave('IPSCam_'.$cameraIdx);
                    //IPSLogger_Dbg (__file__, 'File '.$this->GetLoggingTextFromURL($urlPicture).' could NOT be found on the Server !!!');
                    echo "Error, filecontent false.\n";
                    return false;
                    }
                $result = file_put_contents($localFile, $fileContent);
                IPS_SemaphoreLeave('IPSCam_'.$cameraIdx);

                if ($result===false) 
                    {
                    trigger_error('Error writing File Content to '.$localFile);
                    }
                else
                    {       /* erfolgeich eine Datei erstellt, die begleitenden Informationen updaten */
                    $filemtime=filemtime($localFile);
                    if ($debug) echo "      Kamera ".$componentParams["NAME"]." :  write to  $localFile. File Datum vom ".date ("F d Y H:i:s.", $filemtime)."\n";	
                    SetValue($PictureTitleID,$componentParams["NAME"]."   ".date ("F d Y H:i:s.", $filemtime));
                    SetValue($PictureTimeID,$filemtime);
                    }
                return $localFile;
                }
            else echo "Error, semaphore IPSCam_".$cameraIdx." busy.\n";
            return false;
            }
        else 
            {
            echo "Parameter [\"COMPONENT\"] nicht definiert.\n";
            return false;
            }
        }

    /*   GetLoggingTextFromURL                     */

	private function GetLoggingTextFromURL($url) 
        {
			return str_replace(parse_url($url, PHP_URL_USER).":".parse_url($url, PHP_URL_PASS)."@", "<<user:pwd>>",$url);
		}



    }

?>