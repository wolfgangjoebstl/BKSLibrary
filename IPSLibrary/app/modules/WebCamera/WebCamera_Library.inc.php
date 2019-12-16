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

/* WebCamera Library   
 *
 * es gibt IPS Cam als externes Modul, hier werden pro Kamera Einstellungen im Webfront vorgesehen
 * im OperationCenter gibt es die Livestreamdarstellung und eine Capturebilder Darstellung
 * WebCamera ist dazu ein eigenständiges Modul das aktuell noch Funktionen aus den beiden anderen Programmen übernimmt
 *
 */


class WebCamera
    {

    var $CategoryIdData,  $CategoryIdApp;               // zur Orientierung, wo ist was
    var $CategoryIdDataOC;

    var $camConfiguration;                              // die Konfiguration

    var $ipsOps, $dosOps;                               // praktische Module

    function __construct()
        {
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        if (!isset($moduleManager))
            {
            IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
            $moduleManager = new IPSModuleManager('WebCamera',$repository);
            }
        $installedModules = $moduleManager->GetInstalledModules();

        $this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
        $this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

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
            Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\OperationCenter\OperationCenter_Configuration.inc.php");
            IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
            
            $moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
            $this->CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');

            $CategoryIdDataOverview=IPS_GetObjectIDByName("Cams",$this->CategoryIdDataOC);

            $subnet="10.255.255.255";
            $OperationCenter=new OperationCenter($subnet);
            $OperationCenterConfig = $OperationCenter->getConfiguration();

            /* Ausgabe der OperationCenter spezifischen Cam Konfigurationsdaten */
            if (isset ($OperationCenterConfig['CAM']))
                {
                $this->camConfiguration=$OperationCenterConfig['CAM'];
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

    /* aus der Operation Center Configuration die Component Information raussuchen
     */

    public function getStillPicsConfiguration()
        {
        $i=0; $j=0; $configCamPicture=array();
        foreach($this->camConfiguration as $camera => $entry) 
            {
            if (isset($entry["COMPONENT"])) 
                {
                echo "    $i : ".str_pad($camera,30)." ".$entry["COMPONENT"]."\n";
                $configCamPicture[$j]=$entry;
                $configCamPicture[$j]["NAME"]=$camera;
                $j++;
                }
            else echo "    $i : ".str_pad($camera,30)." ".$entry["IPADRESSE"]."\n";
            $i++;
            }
        return($configCamPicture);
        }

    public function getCategoryIdData()
        {
        return ($this->CategoryIdData);
        }

    public function zielVerzeichnis()
        {
        /* Zielverzeichnis für Anzeige ermitteln */
        $picVerzeichnis="user/OperationCenter/AllPics/";
        $picVerzeichnisFull=IPS_GetKernelDir()."webfront/".$picVerzeichnis;
        $picVerzeichnisFull = str_replace('\\','/',$picVerzeichnisFull);            
        return ($picVerzeichnisFull);
        }

    function DownloadImageFromCam($cameraIdx, $componentParams, $directoryName, $size, $fileName, $debug=false) 
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
            echo "   DownloadImageFromCam, $cameraIdx $directoryName$fileName  \n";
            IPSLogger_Inf(__file__, "WebCamera Copy ".$this->GetLoggingTextFromURL($urlPicture)." --> $localFile");

            $curl_handle=curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $urlPicture);
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($curl_handle, CURLOPT_TIMEOUT, 30);  
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl_handle, CURLOPT_FAILONERROR, true);
            $fileContent = curl_exec($curl_handle);
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
                if ($debug) echo "      Kamera ".$componentParams["NAME"]." :  write to ".$picVerzeichnisFull." File Datum vom ".date ("F d Y H:i:s.", $filemtime)."\n";	
                SetValue($PictureTitleID,$componentParams["NAME"]."   ".date ("F d Y H:i:s.", $filemtime));
                SetValue($PictureTimeID,$filemtime);
                }
            return $localFile;
            }
        else echo "Error, semaphore IPSCam_".$cameraIdx." busy.\n";
        return false;
        }

		private function GetLoggingTextFromURL($url) {
			return str_replace(parse_url($url, PHP_URL_USER).":".parse_url($url, PHP_URL_PASS)."@", "<<user:pwd>>",$url);
		}



    }

?>