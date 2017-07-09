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

	/**@defgroup RemoteAccess
	 *
	 * Script zur Weiterleitung von Daten an einen Visualisierungsserver in BKS
	 *
	 *
	 * @file          RemoteAccess_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	// max. Scriptlaufzeit definieren
	ini_set('max_execution_time', 500);
	$startexec=microtime(true);

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		//echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('RemoteAccess');
	echo "\nRemoteAccess Version : ".$ergebnis;

	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n\n";

	echo "Folgende Module werden von RemoteAccess bearbeitet:\n";
	if (isset ($installedModules["Guthabensteuerung"])) { 			echo "  Modul Guthabensteuerung ist installiert.\n"; } else { echo "Modul Guthabensteuerung ist NICHT installiert.\n"; }
	//if (isset ($installedModules["Gartensteuerung"])) { 	echo "  Modul Gartensteuerung ist installiert.\n"; } else { echo "Modul Gartensteuerung ist NICHT installiert.\n";}
	if (isset ($installedModules["Amis"])) { 				echo "  Modul Amis ist installiert.\n"; } else { echo "Modul Amis ist NICHT installiert.\n"; }
	echo "\n";

	/******************************************************

				INIT, Timer

	*************************************************************/

	/* Timer so konfigurieren dass sie sich nicht in die Quere kommen */

	echo "Timer programmieren für :\n";
	$tim1ID=CreateTimerRA("EvaluateHomematic",20);
	$tim2ID=CreateTimerRA("EvaluateMotion",25);
	$tim3ID=CreateTimerRA("EvaluateAndere",30);
	$tim4ID=CreateTimerRA("EvaluateContact",35);
	$tim5ID=CreateTimerRA("EvaluateStromverbrauch",40);
	$tim6ID=CreateTimerRA("EvaluateSwitch",45);
	$tim7ID=CreateTimerRA("EvaluateButton",50);
	$tim8ID=CreateTimerRA("EvaluateVariables",55);

	/************************************************************************************************
	 *
	 * Create Include file
	 *
	 ************************************************************************************************/

	$remote=new RemoteAccess();
	if (isset ($installedModules["Guthabensteuerung"])) 
		{ 
		$remote->add_Guthabensteuerung(); 
		echo "Ende Guthabensteuerung Variablen zum include file hinzufügen : ".(microtime(true)-$startexec)." Sekunden \n";
		}
	if (isset ($installedModules["Amis"]))	
		{ 
		$remote->add_Amis(); 
		echo "Ende AMIS Variablen zum include file hinzufügen : ".(microtime(true)-$startexec)." Sekunden \n";
		}
	if (isset ($installedModules["OperationCenter"]))	
		{ 		
		$remote->add_SysInfo();
		echo "Ende OperationCenter Variablen zum include file hinzufügen : ".(microtime(true)-$startexec)." Sekunden \n";		
		}		
	$status=$remote->server_ping();
	$remote->add_RemoteServer($status);
	echo "Ende Remote Server installieren : ".(microtime(true)-$startexec)." Sekunden \n";
	$remote->write_includeFile();
	echo "Ende Evaluierung : ".(microtime(true)-$startexec)." Sekunden \n";

	/************************************************************************************************
	 *
	 * Create remote Profiles
	 *
	 ************************************************************************************************/

	$remote->rpc_deleteProfiles($status);
	$remote->rpc_showProfiles($status);
	$remote->rpc_createProfiles($status);

	$remote->write_classresult($status);

	echo "Ende Profilerstellung : ".(microtime(true)-$startexec)." Sekunden \n";
	
	/************************************************************************************************
	 *
	 * Create Web Pages
	 *
	 ************************************************************************************************/

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	if ($WFC10_Enabled==true)
	   {
		$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
		echo "\nWF10 ";
		}

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	if ($WFC10User_Enabled==true)
	   {
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		echo "WF10User ";
		}

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled==true)
	   {
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile ";
		}

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled==true)
	   {
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		}
	
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	

	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren auf ".$WFC10_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		}
		
	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren auf ".$WFC10User_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);

		}

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren auf ".$Mobile_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);

		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren auf ".$Retro_Path.": \n";
		createPortal($Retro_Path);
		}


function CreateTimerRA($name,$minute)
	{
	/* EventHandler Config regelmaessig bearbeiten */
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
	$app_oid=$moduleManager->GetModuleCategoryID()."\n";
	$oid_children=IPS_GetChildrenIDs($app_oid);
	$result=array();
	echo "  Alle Skript Files :\n";
	foreach($oid_children as $oid)
		{
		$result[IPS_GetName($oid)]=$oid;
		//echo "      OID : ".$oid." Name : ".IPS_GetName($oid)."\n";
		}
			
	$timID=@IPS_GetEventIDByName($name, $result[$name]);
	if ($timID==false)
		{
		$timID = IPS_CreateEvent(1);
		IPS_SetParent($timID, $result[$name]);
		IPS_SetName($timID, $name);
		IPS_SetEventCyclic($timID,0,0,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($timID,5,$minute,0);  /* immer um 05:xx */
  		IPS_SetEventActive($timID,true);
	   echo "   Timer Event ".$name." neu angelegt. Timer um 05:20 ist aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event ".$name." bereits angelegt. Timer um 05:".$minute." ist aktiviert.\n";
  		IPS_SetEventActive($timID,true);
  		}
	return($timID);
	}


?>