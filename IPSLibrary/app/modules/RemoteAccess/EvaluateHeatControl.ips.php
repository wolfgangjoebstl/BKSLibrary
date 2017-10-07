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
	ini_set('max_execution_time', 500);
	$startexec=microtime(true);

	echo "Update Konfiguration und register Events fuer HeatControl:\n\n";

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	
	if (function_exists('HomematicList'))
		{
		$parameter=installAccess(HomematicList(),"VALVE_STATE","HeatControl","~Intensity.100");		
		print_r($parameter);   
		installComponent(HomematicList(),"VALVE_STATE",'IPSComponentHeatControl_Homematic','IPSModuleHeatControl',$parameter);		   
		}	
		
	if (function_exists('FHTList'))
		{
		/* Alternative, alle Module auf einmal gemeinsam installieren */
		installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl,1,2,3');
		}		
		

?>