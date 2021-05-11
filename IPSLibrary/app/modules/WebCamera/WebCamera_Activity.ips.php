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

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 500);

$startexec=microtime(true);

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

    IPSUtils_Include ("WebCamera_Configuration.inc.php","IPSLibrary::config::modules::WebCamera");
	IPSUtils_Include ("WebCamera_Library.inc.php","IPSLibrary::app::modules::WebCamera");

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('WebCamera',$repository);
        }
    $installedModules = $moduleManager->GetInstalledModules();

	/*******************************
     *
     * Init, wichtige Variablen
     *
     ********************************/

    $ipsOps = new ipsOps();
    $dosOps = new dosOps();

    $webCamera = new webCamera();   

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet, geänderte Variablen setzen */
    $variableId=$_IPS['VARIABLE'];
    $value=$_IPS['VALUE'];
    $oldvalue=GetValue($variableId);
	SetValue($variableId,$value);
    }

if ($_IPS['SENDER']=="Variable")
	{

	}

/********************************************************************************************
 *
 * Timer Aufrufe
 *
 **********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	//switch ($_IPS['EVENT']) 	{ case $tim1ID:     
	IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Copy Camera Still Picture");

    $camConfig = $webCamera->getStillPicsConfiguration();
    $zielVerzeichnis = $webCamera->zielVerzeichnis();
    
    $webCamera->DownloadImageFromCams($camConfig, $zielVerzeichnis);        // bricht nach 2 nicht erreichbaren Kameras (ca. 40 Sekunden) automatisch ab
    }




if ($_IPS['SENDER']=="Execute") 
	{    
    /* eventuelle Ausgaben über den Status */
    echo "\n";
    echo "Ausgabe der Camera Config aus der OperationCenter CamConfig nach Cam Namen gegliedert:\n";
    //print_r($webCamera->getConfiguration());    
    $camConfig = $webCamera->getStillPicsConfiguration();
    //print_r($camConfig);                                        // plain mit Index
    $zielVerzeichnis = $webCamera->zielVerzeichnis();
    
    $webCamera->DownloadImageFromCams($camConfig, $zielVerzeichnis);


    }


?>