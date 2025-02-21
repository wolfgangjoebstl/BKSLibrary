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


/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für Homematic Temperatur und Feuchtigkeits Werte
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $ipsOps = new ipsOps();

    $dosOps->setMaxScriptTime(500); 
    $startexec=microtime(true);

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
    IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
		}

    $installedModules = $moduleManager->GetInstalledModules();
    if (isset($installedModules["DetectMovement"])) 
        {
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        
        IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
        IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

        IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
        IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
        IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");

    	$debug=false;
		$testMovement = new TestMovement($debug);
        }

    $componentHandling=new ComponentHandling();
	$commentField="zuletzt Konfiguriert von EvaluateHomematic um ".date("h:i am d.m.Y ").".";

    $debug=false;

	/****************************************************************************************************************
	 *
	 *                                      Temperature
	 *
	 ****************************************************************************************************************/
	echo "\n";
    echo "RemoteAccess: Update Homematic Konfiguration und register Events\n";
	echo "***********************************************************************************************\n";
	echo "Temperatur Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
    if (function_exists('deviceList'))
        {
        echo "Temperatur Sensoren von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "TEMPERATURE"],'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField, $debug);				/* true ist Debug, Temperatursensoren und Homematic Thermostat */
        //print_r($result);
        }

	/****************************************************************************************************************
	 *
	 *                                      Humidity
	 *
	 ****************************************************************************************************************/
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Humidity Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
    if (function_exists('deviceList'))
        {
        echo "Luftfeuchtigkeit Sensoren von verschiedenen Geräten werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "HUMIDITY"],'IPSComponentSensor_Feuchtigkeit','IPSModuleSensor_Feuchtigkeit,',$commentField,false);				/* true ist Debug, Feuchtigkeitssensoren und Homematic Thermostat */
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_HUMIDITY","REGISTER" => "HUMIDITY"],'IPSComponentSensor_Feuchtigkeit','IPSModuleSensor_Feuchtigkeit,',$commentField,false);				/* true ist Debug, Feuchtigkeitssensoren und Homematic Thermostat */
        //print_r($result);
        }

    // Create Events zum Abschluss, dekonstruierte Routine um eventuell mitzählen zu können
    $messageHandler = new IPSMessageHandler();
    //$messageHandler->CreateEvents();
    $count=0; $countCust=0;
    $configuration = IPSMessageHandler_GetEventConfiguration();
    foreach ($configuration as $variableId=>$params) 
        {
        //echo "CreateEvent $variableId, ".$params[0],"\n";
        $count++;
        $messageHandler->CreateEvent($variableId, $params[0]);
        }
    $configuration = IPSMessageHandler_GetEventConfigurationCust();
    foreach ($configuration as $variableId=>$params) 
        {
        //echo "CreateCustEvent $variableId, ".$params[0],"\n";
        $countCust++;
        $messageHandler->CreateEvent($variableId, $params[0]);
        }
    echo "Summary, CreateEvent $count CreateCustEvent $countCust\n";

    // oder etwas professioneller mit testMovement von DetectMovememt
    if (isset($testMovement)) 
        {
        echo "DetectMovement Module installiert. Class TestMovement für Auswertungen verwenden:\n";
        //echo "--------\n";
        $eventListforDeletion = $testMovement->getEventListforDeletion();
        if (count($eventListforDeletion)>0) 
            {
            echo "Ergebnis TestMovement construct: Es müssen ".count($eventListforDeletion)." Events in der Config Datei \"IPSMessageHandler_GetEventConfiguration\" gelöscht werden, da keine Konfiguration mehr dazu angelegt ist.\n";
            echo "                                 und es müssen auch diese Events hinterlegt beim IPSMessageHandler_Event geloescht werden \"Bei Änderung Event Ungültig\".\n";
            print_R($eventListforDeletion);
            }
        else 
            {
            echo "Events von IPS_MessageHandler mit Konfiguration abgeglichen. TestMovement sagt alles ist in Ordnung.\n";
            echo "\n";
            }
        $filter="IPSMessageHandler_Event";
        $resultEventList = $testMovement->getEventListfromIPS($filter,true);
        //$ipsOps->intelliSort($resultEventList,"OID");                           // Event ID
        $ipsOps->intelliSort($resultEventList,"Name");                           // Device Event ID
        $html=$testMovement->getComponentEventListTable($resultEventList,$filter,true,true);
        echo $html;

        }


    echo "Aktuelle Laufzeit ".exectime($startexec)." Sekunden.\n"; 


?>