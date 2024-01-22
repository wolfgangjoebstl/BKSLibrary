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
 *	hier für die Homematic und FHT Thermostate den Stellwert und den Sollwert
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

IPSUtils_Include ('IPSModuleHeatControl_All.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl');	

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
		}
	$installedModules = $moduleManager->GetInstalledModules();

	echo "Folgende Module werden von RemoteAccess bearbeitet:\n";
	if (isset ($installedModules["Guthabensteuerung"])) 	{ echo "  Modul Guthabensteuerung ist installiert.\n"; } else { echo "Modul Guthabensteuerung ist NICHT installiert.\n"; }
	if (isset ($installedModules["Amis"])) 					{ echo "  Modul Amis ist installiert.\n"; } else { echo "Modul Amis ist NICHT installiert.\n"; }
	if (isset ($installedModules["CustomComponent"])) 		{ echo "  Modul CustomComponent ist installiert.\n"; } else { echo "Modul CustomComponent ist NICHT installiert.Bitte installieren, fuer Funktion erforderlich\n"; }
	if (isset ($installedModules["DetectMovement"])) 		{ echo "  Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; }
	echo "\n";


/******************************************************

				INIT

*************************************************************/

	// max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(500); 
	$startexec=microtime(true);

	echo "Update Konfiguration und register Events fuer HeatControl:\n\n";

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

    IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	
	/****************************************************************************************************************
	 *
	 *                                      Heat Control Actuators
	 *
	 ****************************************************************************************************************/

    $componentHandling=new ComponentHandling();
    $commentField="zuletzt Konfiguriert von RemoteAccess EvaluateHeatControl um ".date("h:i am d.m.Y ").".";

	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Heat Control Actuator Handler wird ausgeführt.\n";

    if ( (getfromDataBase()) && false)
        {
        echo "\n\n==ACTUATOR based on MySQL ===============================================================================\n";
        $componentHandling->installComponentFull("MySQL",["TYPECHAN" => "TYPE_ACTUATOR"],"","","",true);                   // true ist Debug
        }
   elseif ( (function_exists('deviceList')) && true)
        {
        echo "Aktuatoren von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_ACTUATOR","REGISTER" => "VALVE_STATE"],'IPSComponentHeatControl_Homematic','IPSModuleHeatControl_All',$commentField, true);				/* true ist Debug,  */
        }
	elseif (function_exists('HomematicList'))
		{
		echo "\n";
		echo "Homematic Heat Control Actuator werden auf Basis HomematicList registriert.\n";
		$componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),"TYPE_ACTUATOR",'IPSComponentHeatControl_Homematic','IPSModuleHeatControl_All',$commentField);

		echo "\n";
		echo "HomematicIP Heat Control Actuator werden registriert.\n";
		$componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),"TYPE_ACTUATOR",'IPSComponentHeatControl_HomematicIP','IPSModuleHeatControl_All',$commentField);		
		//installComponentFull(HomematicList(),"VALVE_STATE",'IPSComponentHeatControl_Homematic','IPSModuleHeatControl_All,1,2,3');					
		} 			


		
	if (function_exists('FHTList'))
		{
		//installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All');
		echo "\n";
		echo "FHT80b Heat Control Actuator werden registriert.\n";		
		$componentHandling->installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All',$commentField);
		}

	echo "***********************************************************************************************\n";

	/****************************************************************************************************************
	 *
	 *                                      Heat Set Temperature (Thermostate an der Wand)
	 *
	 ****************************************************************************************************************/

	
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Heat Control Set Temperature Handler wird ausgeführt.\n";
	
	if ( (getfromDataBase()) && false)
        {
        echo "\n\n==THERMOSTAT based on MySQL ===============================================================================\n";
        $componentHandling->installComponentFull("MySQL",["TYPECHAN" => "TYPE_THERMOSTAT"],"","","",true);                   // true ist Debug, nur SQL kennt die richtigen Components/Modules, die dann 
        }
   elseif ( (function_exists('deviceList')) && false)                // haendisch ein/ausschalten, wir vertrauen der Sache noch nicht
        {
        echo "Thermostat von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_THERMOSTAT"],'','',$commentField, true);				/* true ist Debug,  */
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_ACTUATOR","REGISTER" => "SET_TEMPERATURE"],'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All',$commentField, true);				/* true ist Debug,  */        
        }
	elseif (function_exists('HomematicList'))
		{
		echo "\n";
		echo "Homematic Heat Set Werte aus den Thermostaten werden registriert.\n";
		$componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),"TYPE_THERMOSTAT",'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All',$commentField);

		echo "\n";
		echo "HomematicIP Heat Set Werte aus den Thermostaten werden registriert.\n";
		$componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),"TYPE_THERMOSTAT",'IPSComponentHeatSet_HomematicIP','IPSModuleHeatSet_All',$commentField);		
		//installComponentFull(HomematicList(),array("SET_TEMPERATURE","WINDOW_OPEN_REPORTING"),'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All');
		//installComponentFull(HomematicList(),"TYPE_THERMOSTAT",'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All');
		} 	


		
	if (function_exists('FHTList'))
		{
		echo "\n";
		echo "FHT80b Heat Set Werte aus den Thermostaten werden registriert.\n";		
		//installComponentFull(FHTList(),"TargetTempVar",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All');
		$componentHandling->installComponentFull(FHTList(),"TYPE_THERMOSTAT",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All',$commentField);
		}

	echo "***********************************************************************************************\n";

	/****************************************************************************************************************
	 *
	 *                                      Heat Set Thermostat Control Mode (manuell, Auto etc.)
	 *
	 ****************************************************************************************************************/

	
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Heat Control Mode Handler wird ausgeführt.\n";
		
	if (function_exists('HomematicList'))
		{
		echo "\n";
		echo "Homematic Control Mode Werte aus den Thermostaten werden registriert.\n";
        $componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),["CONTROL_MODE","WINDOW_OPEN_REPORTING"],'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All',$commentField);

		echo "\n";
		echo "HomematicIP Control Mode Werte aus den Thermostaten werden registriert.\n";
	    $componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),["CONTROL_MODE","!VALVE_STATE"],'IPSComponentHeatSet_HomematicIP','IPSModuleHeatSet_All',$commentField);
	    $componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),["SET_POINT_MODE","!VALVE_STATE"],'IPSComponentHeatSet_HomematicIP','IPSModuleHeatSet_All',$commentField);
		}
		 

	if ( (function_exists('FHTList')) && (sizeof(FHTList())>0) )
		{
    	echo "\n";
	    echo "FHT80b Heat Set Werte aus den Thermostaten werden registriert.\n";		
        $componentHandling->installComponentFull(FHTList(),"TargetModeVar",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All',$commentField);        
        //installComponentFull(FHTList(),"TargetTempVar",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All');
		//$componentHandling->installComponentFull(FHTList(),"TYPE_THERMOSTAT",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All',$commentField);
		}


?>