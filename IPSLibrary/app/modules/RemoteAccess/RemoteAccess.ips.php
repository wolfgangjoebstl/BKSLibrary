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


/* Program baut auf einem oder mehreren remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 * es wird die Struktur am remote Server aufgebaut
 *
 * zusaetzlich wird ein Evaluate_ROID.inc erstellt, damit Variablen auf den Remote Servern schneller adressierbar sind
 *    mit function GuthabensteuerungList(), function AmisStromverbrauchList() und function ROID_List()
 *
 * function ROID_List() beinhaltet die Liste der VIS Server
 *     und die Kategorien der dort angelegten Tabs
 */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
    IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(500); 
    $startexec=microtime(true);

/********************************************************************************
 *
 *    EVALUATION
 *
 * welche Module sind installiert
 *
 ************************************************************************************/	

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
		}

	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n\n";

 /******************************************************
  *
  *  			INSTALLATION
  *
  *************************************************************/
  
  	echo "Folgende Module werden von RemoteAccess bearbeitet:\n";
	if (isset ($installedModules["Guthabensteuerung"])) { 			echo "  Modul Guthabensteuerung ist installiert.\n"; }  else { echo "   Modul Guthabensteuerung ist NICHT installiert.\n"; }
	//if (isset ($installedModules["Gartensteuerung"])) { 	        echo "  Modul Gartensteuerung ist installiert.\n"; }    else { echo "Modul Gartensteuerung ist NICHT installiert.\n";}
	if (isset ($installedModules["Amis"])) { 				        echo "  Modul Amis ist installiert.\n"; }               else { echo "   Modul Amis ist NICHT installiert.\n"; }
	if (isset ($installedModules["OperationCenter"])) { 			echo "  Modul OperationCenter ist installiert.\n"; }   else { echo "   Modul OperationCenter ist NICHT installiert.\n"; }

	if (isset ($installedModules["DetectMovement"]))
		{
		IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
		IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        echo "  Modul DetectMovement ist installiert.\n"; 
        } 
    else 
        { 
        echo "   Modul DetectMovement ist NICHT installiert.\n"; 
		}
    echo "\n";
    
	/************************************************************************************************
	 *
	 * Create Include file
	 *
	 ************************************************************************************************/

	$remote=new RemoteAccess();
	if (isset ($installedModules["Guthabensteuerung"])) 
		{ 
		$remote->add_Guthabensteuerung();           // true debug 
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
	$status=$remote->server_ping();		/* speichert die aktuelle Erreichbarkeit jedes einzelnen Servers in der Liste */
	echo "Ende Remote Server ping : ".(microtime(true)-$startexec)." Sekunden \n";
	$remote->add_RemoteServer($status,true);	    /* mit new wurde ein include File angelegt, in dieses wird die Liste der erreichbaren Remote Logging Server eingetragen, ROID_List() */
	echo "Ende Remote Server installieren : ".(microtime(true)-$startexec)." Sekunden \n";
	
	$remote->write_includeFile();			/* und am Ende das include File geschrieben */
	echo "Ende Evaluierung : ".(microtime(true)-$startexec)." Sekunden \n";

	//$remote->rpc_showProfiles();
	$remote->rpc_createProfiles($status);

	$remote->write_classresult($status);
	
	echo "Ende Profilerstellung : ".(microtime(true)-$startexec)." Sekunden \n";

/******************************************************************/




?>