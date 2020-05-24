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

/* UpdateMySQL
 *
 *
 */

$startexec=microtime(true);     // Zeitmessung, um lange Routinen zu erkennen

/******************************************************
 *
 *				INIT
 *
 *************************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');    
IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
	}
$installedModules = $moduleManager->GetInstalledModules();

if (isset($installedModules["DetectMovement"]))
    {
    IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
    IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
    $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
    $DetectDeviceListHandler = new DetectDeviceListHandler();               // neuer Handler für die DeviceList, registriert die Devices in EvaluateHarwdare_Configuration
    }

    echo "\n";
    echo "Kernel Version (Revision) ist : ".IPS_GetKernelVersion()." (".IPS_GetKernelRevision().")\n";
    echo "Kernel Datum ist : ".date("D d.m.Y H:i:s",IPS_GetKernelDate())."\n";
    echo "Kernel Startzeit ist : ".date("D d.m.Y H:i:s",IPS_GetKernelStartTime())."\n";
    echo "Kernel Dir seit IPS 5.3. getrennt abgelegt : ".IPS_GetKernelDir()."\n";
    echo "Kernel Install Dir ist auf : ".IPS_GetKernelDirEx()."\n";
    echo "\n";

/* DeviceManger muss immer installiert werden, wird in Timer als auch RunScript und Execute verwendet */

if (isset($installedModules["OperationCenter"])) 
    {
    IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter'); 
    echo "OperationCenter ist installiert.\n";
    $DeviceManager = new DeviceManagement();            // class aus der OperationCenter_Library
    //echo "  Aktuelle Fehlermeldung der der Homematic CCUs ausgeben:\n";      
    echo $DeviceManager->HomematicFehlermeldungen()."\n";
    //echo "  Homematic Serialnummern erfassen:\n";
    $serials=$DeviceManager->addHomematicSerialList_Typ();      // kein Debug
    }

    $ipsOps = new ipsOps();
	$modulhandling = new ModuleHandling();	                	            // in AllgemeineDefinitionen, alles rund um Bibliotheken, Module und Librariestrue bedeutet mit Debug
    $topologyLibrary = new TopologyLibraryManagement();                     // in EvaluateHardware Library, neue Form des Topology Managements
    $evaluateHardware = new EvaluateHardware();



?>