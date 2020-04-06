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
ini_set('max_execution_time', 500);
$startexec=microtime(true);

	echo "RemoteAccess: Update Konfiguration und register Events\n";

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	//IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

    IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt

    $componentHandling=new ComponentHandling();
	$commentField="zuletzt Konfiguriert von EvaluateHomematic um ".date("h:i am d.m.Y ").".";

	/****************************************************************************************************************
	 *
	 *                                      Temperature
	 *
	 ****************************************************************************************************************/
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Temperatur Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
    if (function_exists('deviceList'))
        {
        echo "Temperatur Sensoren von verschiedenen Geräten werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "TEMPERATURE"],'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField,true);				/* Temperatursensoren und Homematic Thermostat */
        //print_r($result);
        }

    if (false)
        {
        echo "Homematic Temperatur Sensoren werden registriert.\n";
        if (function_exists('HomematicList'))
            {
            $componentHandling->installComponentFull(HomematicList(),"TEMPERATURE",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);				/* Temperatursensoren und Homematic Thermostat */
            $componentHandling->installComponentFull(HomematicList(),"ACTUAL_TEMPERATURE",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);		/* HomematicIP Thermostat */
            } 
        echo "\n";
        echo "FHT Heizungssteuerung Geräte werden registriert.\n";
        if (function_exists('FHTList'))
            {
            $componentHandling->installComponentFull(FHTList(),"TemeratureVar",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);
            }
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
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "HUMIDITY"],'IPSComponentSensor_Feuchtigkeit','IPSModuleSensor_Feuchtigkeit,',$commentField,true);				/* Temperatursensoren und Homematic Thermostat */
        //print_r($result);
        }
   

    if (false)
        {
        echo "Homematic Humidity Sensoren werden registriert.\n";
        if (function_exists('HomematicList'))
            {
            $componentHandling->installComponentFull(HomematicList(),"HUMIDITY",'IPSComponentSensor_Feuchtigkeit','IPSModuleSensor_Feuchtigkeit,',$commentField);
            } 
        }


    /*  alte Berechnung, jetzt überall den selben Algortithmus verwenden

	$remote=new RemoteAccess();    
	$Homematic = HomematicList();
	$remote->RPC_CreateVariableField($Homematic, "TEMPERATURE", "Temperatur", $startexec);  // rpc, remote OID of category, OID Liste, OID Typ daraus, zuzuordnendes Profil, RPC ArchiveHandler 
	$remote->RPC_CreateVariableField($Homematic, "HUMIDITY", "Humidity", $startexec);  // rpc, remote OID of category, OID Liste, OID Typ daraus, zuzuordnendes Profil, RPC ArchiveHandler 
    */


    echo "Aktuelle Laufzeit ".exectime($startexec)." Sekunden.\n"; 


?>