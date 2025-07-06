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

	/**@defgroup OperationCenter_Installation
	 *
	 * Script zur Unterstützung der Betriebsführung, installiert das OperationCenter
	 *
	 *
	 * @file          OperationCenter_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/


	/* OperationCenter_Installation
	 *
	 * Script immer ähnlich aufgebaut.
	 *
	 * Init	includes, installed Modules, allgemeine classes
     * Logging
	 * Hardware Evaluierung, nur auf die aktuellsten Hardware Objekte aufbauen
	 * Webfront Config einlesen, OperationCenter hat jetzt auch einen eigenen Tab
	 * Init Timer
     * Variablen Profile festlegen
	 * INIT, iMacro oder SNMP Router auslesen
	 * INIT, Nachrichtenspeicher
	 * INIT, TraceRouteSpeicher
	 * INIT, SystemInfo
	 * Webfront Vorbereitung
	 * Webfront zusammenräumen
	 * INIT, Webcams 
	 * Init SysPing
     * Homematic RSSI auslesen
     * Init DetectMovement vs Event Darstellung, Teil von SystemTP
     * init Alexa, Autosteuerung, Hue, Netatm etc. Darstellung, wo eigentlich
	 *
	 * WebFront Installation
	 * verwendet noch nicht easyWebfront
	 *
	 */


	/******************************************************
	 *
	 * INIT, Init
	 *
	 * Setup, define basic includes and variables, general for all modules
	 * besides the include files
	 *
	 *************************************************************/

	$debug=false;
    $startexec=microtime(true);     /* Laufzeitmessung */



    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
	IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ("DeviceManagement_Library.class.php","IPSLibrary::app::modules::OperationCenter");    

    IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ("Homematic_Library.class.php","IPSLibrary::app::modules::OperationCenter");
	IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    $moduleManager    = new ModuleManagerIPS7('OperationCenter',$repository);

    if ($_IPS['SENDER']=="Execute") 
        {
        echo "Script Execute, Darstellung automatisch mit Debug aktiviert. \n";
        $debug=false;
        }
    else $debug=false;

	$dosOps = new dosOps();
    $ipsOps = new ipsOps();
    $webOps = new webOps();
    $wfcHandling = new WfcHandling();		// für die Interoperabilität mit den alten WFC Routinen nocheinmal mit der Instanz als Parameter aufrufen

 	$installedModules = $moduleManager->GetInstalledModules();
    $systemDir     = $dosOps->getWorkDirectory(); 
    $opSystem      = $dosOps->getOperatingSystem();                 // zum Unterscheiden Linux und Windows

    if ($debug)
        {
        $moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
        $moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
        $moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');
        echo "IP Symcon Daten:\n";
        $ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
        echo "  Modulversion : ".$ergebnis."\n";
        $ergebnis=$moduleManager->VersionHandler()->GetModuleState();
        echo "  Modulstatus  : ".$ergebnis."\n";
        $ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
        echo "  IPSModulManager Version : ".$ergebnis."\n";
        $ergebnisVersion=$moduleManager->VersionHandler()->GetVersion('OperationCenter');
        echo "  OperationCenter Version : ".$ergebnisVersion."\n";
        echo "\n";
        echo "Kernel Version (Revision) ist : ".IPS_GetKernelVersion()." (".IPS_GetKernelRevision().")\n";
        echo "Kernel Datum ist                           : ".date("D d.m.Y H:i:s",IPS_GetKernelDate())."\n";
        echo "Kernel Startzeit ist                       : ".date("D d.m.Y H:i:s",IPS_GetKernelStartTime())."\n";
        echo "Kernel Dir seit IPS 5.3. getrennt abgelegt : ".IPS_GetKernelDir()."\n";
        echo "Kernel Install Dir ist auf                 : ".IPS_GetKernelDirEx()."\n";
        echo "Kernel Log Dir ist auf                     : ".IPS_GetLogDir()."\n";
        echo "\n";
        
        $inst_modules="\nInstallierte Module:\n";
        foreach ($installedModules as $name=>$modules)
            {
            $inst_modules.="   ".str_pad($name,30)." ".$modules."\n";
            }
        echo $inst_modules;
        
        echo "Operating System : $opSystem\n";
        echo "Working Directory from IPSComponentLogger_Configuration : $systemDir\n";          // ersetzt hart kodiertes C:/Scripts, hat ein / am Ende
        }

    // max. Scriptlaufzeit definieren, sonst stoppt vorher wegen langsamer Kamerainstallation
    $dosOps->setMaxScriptTime(400); 

	/******************************************************
	 *
	 *				Logging der Installation 
     *
     ********************************************************/

	$Heute=time();
	//$HeuteString=date("jnY_Hi",$Heute);
	//$HeuteString=date("jnY",$Heute);			// j Tag ohne führende Nullen, n Monat ohne führende Nullen
	$HeuteString=date("dmY",$Heute);
	echo "Heute  Datum ".$HeuteString." für das Logging der OperationCenter Installation.\n";
	
	if (isset ($installedModules["OperationCenter"])) 
		{
		$log_Install=new Logging($systemDir."/Install/Install".$HeuteString.".csv");								// mehrere Installs pro Tag werden zusammengefasst
        $ergebnisVersion=$moduleManager->VersionHandler()->GetVersion('OperationCenter');
		$log_Install->LogMessage("Install Module OperationCenter. Aktuelle Version ist $ergebnisVersion.");
		}
		
        
	$subnet="10.255.255.255";
	$OperationCenter=new OperationCenter($subnet);
	//$OperationCenterConfig = $OperationCenter->oc_Configuration;			// alter zugriff direkt auf die Config Variable
	$OperationCenterConfig = $OperationCenter->getConfiguration();
	$OperationCenterSetup = $OperationCenter->getSetup();

    if ($opSystem != "UNIX")
        {
        // Batch Datei schreiben für Windows Betriebssystem
        $verzeichnisSystem=$OperationCenterSetup["SystemDirectory"];
        $filenameSystem = $verzeichnisSystem."read_Systeminfo.bat";    
        $handle2=fopen($filenameSystem,"w");
        fwrite($handle2,'cd '.$verzeichnisSystem."\r\n");
        fwrite($handle2,'echo %username% >>username.txt'."\r\n");
        fwrite($handle2,'wmic process list >>processlist.txt'."\r\n");                          // sehr aufwendige Darstellung der aktiven Prozesse
        fwrite($handle2,'tasklist >>tasklist.txt'."\r\n");
        fwrite($handle2,'jps >>jps.txt'."\r\n");  
        //fwrite($handle2,'wmic Path win32_process Where "CommandLine Like \'%selenium%\'" >>wmic.txt');
        fwrite($handle2,'wmic Path win32_process >>wmic.txt'."\r\n");
        //fwrite($handle2,"pause\r\n");
        fwrite($handle2,'systeminfo >>system.txt'."\r\n");
        fclose($handle2);
        }
    else            // Unix Mode
        {
        // list of users and active user
        // list of active processes, tasks, and java apps
        // complete systeminfo
        // sys ping alternative  
        // alternative for running scripts, $sysOps->ExecuteUserCommand  
        }

	$modulhandling = new ModuleHandling();
	
    /*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Hardware Evaluierung starten, auf Fertigstellung warten
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	if (isset ($installedModules["EvaluateHardware"])) 
		{     
        IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
        IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');    
    	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");

        $moduleManagerEH = new IPSModuleManager('EvaluateHardware',$repository);
        $CategoryIdAppEH      = $moduleManagerEH->GetModuleCategoryID('app');	
        $scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdAppEH);
        echo "\n";
        echo "Die EvaluateHardware Scripts sind in App auf               ".$CategoryIdAppEH."\n";
        echo "Evaluate Hardware hat die ScriptID ".$scriptIdEvaluateHardware." und wird jetzt gestartet. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
        IPS_RunScriptWait($scriptIdEvaluateHardware);
        echo "Script Evaluate Hardware gestartet wurde mittlerweile abgearbeitet. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";	
        }

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$scriptIdOperationCenter  = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);
	$scriptIdDiagnoseCenter   = IPS_GetScriptIDByName('DiagnoseCenter', $CategoryIdApp);
    $scriptIdFastPollShort     = IPS_GetScriptIDByName('FastPollShortExecution', $CategoryIdApp);

	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	/******************************************************
	 *
	 * Webfront Config einlesen
	 *
	 * nicht so wichtig, Subtabs bei anderen Programmen verwenden
	 *
	 *************************************************************/    

    $configWFront     = $ipsOps->configWebfront($moduleManager);          // nue Art der Webfront ini Konfiguration einlesen, das Ini File auslesen und als Array zur verfügung stellen, es wird nur der modulManager benötigt 
    $WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();           // Webfront Configuration Administrator ConfigID, User etc.

	$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

	/******************************************************
	 *
	 *				INIT, Timer
	 *
	 * Timer so konfigurieren dass sie sich nicht in die Quere kommen. Es gibt
	 * mittlerweile 11 Timer die der Reihe nach ab ca. 1 Uhr aufgerufen werden. 
	 *
	 *************************************************************/

	echo "\nTimer programmieren :\n";
	
	$timer = new TimerHandling();
	//print_r($timer->listScriptsUsed());
	
	$tim4ID = @IPS_GetEventIDByName("SysPingTimer", $scriptIdOperationCenter);
	if ($tim4ID==false)
		{
		$tim4ID = IPS_CreateEvent(1);
		IPS_SetParent($tim4ID, $scriptIdOperationCenter);
		IPS_SetName($tim4ID, "SysPingTimer");
        /* das Event wird alle 5 Minuten aufgerufen, der Standard Sysping, wenn nicht als FAST gekennzeichnet, läuft allerdings alle 60 Minuten */
		IPS_SetEventCyclic($tim4ID,0,1,0,0,2,5);      /* alle 5 Minuten , Tägliche Ausführung, keine Auswertung, Datumstage, Datumstageintervall, Zeittyp-2-alle x Minute, Zeitintervall */
		IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);
		IPS_SetEventActive($tim4ID,true);
		echo "   Timer Event SysPingTimer neu angelegt. Timer 5 Minuten ist aktiviert.\n";
		}
	else
		{
		echo "   Timer Event SysPingTimer bereits angelegt. Timer 5 Minuten ist aktiviert.\n";
  		IPS_SetEventActive($tim4ID,true);
        /* das Event wird alle 5 Minuten aufgerufen, der Standard Sysping, wenn nicht als FAST gekennzeichnet, läuft allerdings alle 60 Minuten */
		IPS_SetEventCyclic($tim4ID,0,1,0,0,2,5);      /* alle 5 Minuten , Tägliche Ausführung, keine Auswertung, Datumstage, Datumstageintervall, Zeittyp-2-alle x Minute, Zeitintervall */
		IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);
  		}
  		
	$tim5ID = @IPS_GetEventIDByName("CyclicUpdate", $scriptIdOperationCenter);
	if ($tim5ID==false)
		{
		$tim5ID = IPS_CreateEvent(1);
		IPS_SetParent($tim5ID, $scriptIdOperationCenter);
		IPS_SetName($tim5ID, "CyclicUpdate");
		IPS_SetEventCyclic($tim5ID,4,1,0,12,0,0);    /* jeden 12. des Monats , Monatliche Ausführung, alle 1 Monate, Datumstage, Datumstageintervall,  */
		echo "   Timer Event CyclicUpdate neu angelegt. Timer jeden 12. des Monates ist aktiviert.\n";
		}
	else
		{
		echo "   Timer Event CyclicUpdate bereits angelegt. Timer jeden 12. des Monates ist aktiviert.\n";
  		IPS_SetEventActive($tim5ID,true);
  		}
		
	$tim1ID=$timer->CreateTimerOC("RouterAufruftimer",00,20);				/* Eventuell Router regelmaessig auslesen */	
	$tim10ID=$timer->CreateTimerOC("Maintenance",01,20);						/* Starte Maintanenance Funktionen */	
	$tim6ID=$timer->CreateTimerOC("CopyScriptsTimer",02,20);	
	$tim8ID=$timer->CreateTimerOC("SystemInfo",02,30);
	$tim9ID=$timer->CreateTimerOC("Homematic",02,40);	
	$tim14ID=$timer->CreateTimerOC("UpdateStatus",03,40);
	$tim7ID=$timer->CreateTimerOC("FileStatus",03,50);
	$tim13ID=$timer->CreateTimerOC("CleanUpEndofDay",22,40);	
		
	$tim11ID=$timer->CreateTimerSync("MoveLogFiles",150);						/* Maintanenance Funktion: Move Log Files, Backup Funktion */	
	$tim2ID=$timer->CreateTimerSync("MoveCamFiles",150);
	$tim3ID=$timer->CreateTimerSync("RouterExectimer",150);

    // Timer12, change to new script
	//$tim12ID=$timer->CreateTimerSync("HighSpeedUpdate",10);					                    /* alle 10 Sekunden Werte updaten, zB die Werte einer SNMP Auslesung über IPS SNMP */
    //$tim12ID = @IPS_GetEventIDByName("HighSpeedUpdate",$scriptIdOperationCenter);					/* alle 10 Sekunden Werte updaten, zB die Werte einer SNMP Auslesung über IPS SNMP */

    $timerOps = new timerOps();
    $tim12ID=$timerOps->CreateTimerSync("HighSpeedUpdate",10, $scriptIdFastPollShort);
    IPS_SetEventActive($tim12ID,false);

	/*******************************
	 *
	 * Variablen Profile Vorbereitung, Allgemeine Profile, gibt es gleich auch bei Autosteuerung
	 *
	 ********************************/

    echo "\n";
    echo "Profile werden vorbereitet:\n";
    /* für Backup Funktionen */

	$pname="AusEinAuto";
	if (IPS_VariableProfileExists($pname) == false)
		{
			//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
		//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." erstellt;\n";
		}

    $pname="RepairRestartFullIncrementCleanup";
	if (IPS_VariableProfileExists($pname) == false)
		{       //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
        }
    else 
        {        
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 5, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Repair", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, 1, "Restart", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "Full", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 3, "Increment", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 4, "Cleanup", "", 0x20c0f0); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 5, "Stopp", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." überarbeitet;\n";
		}

    $pname="KeepOverwriteAuto";
	if (IPS_VariableProfileExists($pname) == false)
		{       //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
        }
    else 
        {        
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Keep", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 1, "Overwrite", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x20c0f0); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." überarbeitet;\n";
		}

    $pname="MaxCopySlider";                                 // Integer für maxCopy, als % Slider
	if (IPS_VariableProfileExists($pname) == false)
		{       //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
        }
    IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
    IPS_SetVariableProfileValues($pname, 0, 1000, 10); //PName, Minimal, Maximal, Schrittweite
    IPS_SetVariableProfileText($pname, "", "%");    
    echo "Profil ".$pname." überarbeitet;\n";



	/******************************************************

				INIT, iMacro basierende und SNMP unterstützende Router vorbereiten

	*************************************************************/

	/* SNMP iFTable verwenden folgendes Profil um die Interface Tabellen zu sortieren. */
		$pname="SortifTable";
		if (IPS_VariableProfileExists($pname) == false)
			{
			//Var-Profil erstellen
			IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
			echo "Profil ".$pname." erstellt;\n";
			}
			
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 10, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Event#", "", 	0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, 1, "ID", "", 	0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "Name", "", 		0x4e3127); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 3, "Pfad", "", 		0x4e7127); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 4, "Objektname", "", 		0x1ef1f7); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 5, "Module", "", 		0x1ef177); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 6, "Funktion", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 7, "Konfiguration", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 8, "Homematic", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 9, "DetectMovement", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 10, "Autosteuerung", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color			
		echo "Profil ".$pname." upgedated.\n";


    /* welche SNMP Module sind verfügbar, gibt es das IPSSNMP Modul von Babenschneider, dann können kurze Request-Response Timings implementiert werden */
    echo "\n";
    echo "Evaluierung der SNMP Module, welche werden verwendet:\n";
    if (false)          // nur zum lokalen Debuggung
        {
        echo "   Ausgabe der geladenen Bibliotheken:\n"; 
        $modulhandling->printrLibraries();
        echo "   Ausgabe der Symcon Module aus der Babenschneider Bibliothek:\n"; 
        $modulhandling->printModules("Babenschneider Symcon Modules");
        echo "   Diese SNMP Instanzen sind bereits instaliert:\n";
        $modulhandling->printInstances("IPSSNMP");
        echo "   mit folgenden Funktionen:\n";
        $modulhandling->getFunctions("IPSSNMP");
        }
    $modules=$modulhandling->getModules("Babenschneider Symcon Modules");
    if (count($modules)>0) echo "   IPSSNMP Module können installiert werden. Klären ob benötigt.\n";
    $instances=$modulhandling->getInstances("IPSSNMP");
    if (count($instances)==0) echo "   IPSSNMP Module müssen erst installiert werden. Klären ob benötigt.\n";
    $instanceTable=array();
    foreach ($instances as $instance) $instanceTable[IPS_GetName($instance)]=$instance;
    //print_R($instanceTable);

    $snmpCount=0; $snmp=array();
   	foreach ($OperationCenterConfig['ROUTER'] as $router)
		{
    	echo "Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
        if ( (isset($router['READMODE'])) && (strtoupper($router['READMODE'])=="SNMP") ) 
            {
            echo "   ->SNMP Abfrage zahlt sich aus.\n";
            $snmpCount++;
            $snmp[]=$router;
            }
		//print_r($router);
        }
    if ( (count($modules)>0) && ($snmpCount>0) )
        {
        $categoryId_Snmp = CreateCategoryPath('Hardware.SNMP');	            
        foreach ($snmp as $router)
            {
        	echo "Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." bekommt eine eigene SNMP Instanz.\n";
            if ( (isset($instanceTable[$router['NAME']."_SNMP"])) === false)
                {
                $instanceId = IPS_CreateInstance("{2F4FB7B0-AF13-46F1-9DEA-1DEBE0C3E324}");
                IPS_SetParent($instanceId, $categoryId_Snmp);
                IPS_SetName($instanceId, $router['NAME']."_SNMP");
                }
            else 
                {
                $instanceId = $instanceTable[$router['NAME']."_SNMP"];
                }
            $config = IPS_GetConfiguration($instanceId);
            echo "Instance ".$router['NAME']."_SNMP bereits bekannt: $instanceId mit Konfiguration $config\n";
            $configArray=json_decode($config,true); // als Array ausgeben
            $update=false;
            if ($configArray["SNMPIPAddress"] != $router["IPADRESSE"]) { $update=true; $configArray["SNMPIPAddress"] = $router["IPADRESSE"]; };
            if ($configArray["SNMPTimeout"] != 5) { $update=true; $configArray["SNMPTimeout"] = 5; };
            if ($configArray["SNMPInterval"] != 3600) { $update=true; $configArray["SNMPInterval"] = 3600; };
            if ( (isset($router["COMMUNITY"])) && ($configArray["SNMPCommunity"] != $router["COMMUNITY"]) ) { $update=true; $configArray["SNMPCommunity"] = $router["COMMUNITY"]; };
            if ( (isset($router["VERSION"])) && ($configArray["SNMPVersion"] != $router["VERSION"]) ) { $update=true; $configArray["SNMPVersion"] = $router["VERSION"]; };
            if ($update)
                {
                $config = json_encode($configArray);           
                IPS_SetConfiguration($instanceId,$config);
                IPS_ApplyChanges($instanceId);
                }
            }
        }

	echo "\nRouter Erstellung der iMacro Programmierung, Vorbereitung Tab für SNMP basierte Geräte :\n";
	$routerSnmpLinks=array();								// Sammlung der SNMP Variablen im OperationCenterWebfront unter Router
	foreach ($OperationCenterConfig['ROUTER'] as $router)
		{
        if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
            {
        	$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
		    if ($router_categoryId==true) 	IPS_SetHidden($router_categoryId,true);       // deaktivierte Kategorien verstecken wenn bereits angelegt
            }
        else
            {
    		echo "  Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
        	$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
		    if ($router_categoryId==false) 	$router_categoryId = CreateCategory("Router_".$router['NAME'],$CategoryIdData,10);       // Kategorie anlegen
			IPS_SetHidden($router_categoryId,false);		// und anzeigen
	    	//print_r($router);

			$host          = $router["IPADRESSE"];
			if (isset($router["COMMUNITY"])) $community     = $router["COMMUNITY"]; 
			else $community     = "public";				 
            $binary        = $systemDir."ssnmpq/ssnmpq.exe";    // Pfad zur ssnmpq.exe
								
            switch (strtoupper($router["TYP"]))
                {
			    case 'MR3420':
                    echo "      iMacro Command-File für Router Typ MR3420 wird hergestellt.\n";
                    if (is_dir($OperationCenterSetup["MacroDirectory"]))
                        {
                        $handle2=fopen($OperationCenterSetup["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");
                        fwrite($handle2,'VERSION BUILD=8961227 RECORDER=FX'."\n");
                        fwrite($handle2,'TAB T=1'."\n");
                        fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
                        fwrite($handle2,'SET !ENCRYPTION NO'."\n");
                        fwrite($handle2,'ONLOGIN USER='.$router['USER'].' PASSWORD='.$router['PASSWORD']."\n");
                        fwrite($handle2,'URL GOTO=http://'.$router['IPADRESSE']."\n");
                        fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:System<SP>Tools'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:-<SP>Statistics'."\n");
                        fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=SELECT FORM=NAME:sysStatic ATTR=NAME:Num_per_page CONTENT=%100'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:NextPage'."\n");
                        fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:Refresh'."\n");
                        //fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");  /* Textfile speichert nicht die komplette Struktur */
                        fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
                        fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:Status'."\n");
                        fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."_Statistics\n");
                        fwrite($handle2,'TAB CLOSE'."\n");
                        fclose($handle2);
                        }
                    else echo "Error, Directory ".$OperationCenterSetup["MacroDirectory"]." not available.\n";                                 
                    break;
		        case 'B2368':
                	echo "      SNMP Abfrage für Router Typ ".$router['TYP']." wird hergestellt.\n";				
					$OperationCenter->read_routerdata_B2368($router_categoryId, $host, $community, $binary, $debug);
					$routerFastPoll_categoryId = CreateCategory("SnmpFastPoll",$router_categoryId,1000);       	// Kategorie anlegen
					if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") )			//  
						{	
						$OperationCenter->read_routerdata_B2368($routerFastPoll_categoryId, $host, $community, $binary, $debug);	
						}			
                    echo "      iMacro Command-File für Router Typ ".$router['TYP']." wird hergestellt.\n";
                    if (is_dir($OperationCenterSetup["MacroDirectory"]))
                        {
                        $handle2=fopen($OperationCenterSetup["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");
                        fwrite($handle2,'VERSION BUILD=8970419 RECORDER=FX'."\n");
                        fwrite($handle2,'TAB T=1'."\n");
                        //fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
                        fwrite($handle2,'SET !ENCRYPTION NO'."\n");
                        //fwrite($handle2,'ONLOGIN USER=admin PASSWORD=cloudg06'."\n");
                        fwrite($handle2,'URL GOTO=http://'.$router['IPADRESSE']."\n");
                        //fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
                        fwrite($handle2,'REFRESH'."\n");
                        //fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:System<SP>Tools'."\n");
                        //fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:-<SP>Statistics'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=ID:login ATTR=ID:username CONTENT='.$router['USER']."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=ID:login ATTR=ID:userpassword CONTENT='.$router['PASSWORD']."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=INPUT:BUTTON FORM=ID:login ATTR=*'."\n");
                        fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=SELECT FORM=NAME:sysStatic ATTR=NAME:Num_per_page CONTENT=%100'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:NextPage'."\n");
                        fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
                        fwrite($handle2,'TAG POS=2 TYPE=A ATTR=TXT:'."\n");
                        //fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:Refresh'."\n");
                        //fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");  /* Textfile speichert nicht die komplette Struktur */
                        //fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
                        fwrite($handle2,'SAVEAS TYPE=CPL FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
                        //fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
                        fwrite($handle2,'FRAME F=0'."\n");
                        //fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:Status'."\n");
                        //fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."_Statistics\n");
                        fwrite($handle2,'TAG POS=1 TYPE=LI ATTR=TITLE:Logout&&CLASS:logout-icon<SP>logoutBtn&&TXT:'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=BUTTON ATTR=TXT:OK'."\n");
                        fwrite($handle2,'TAB CLOSE'."\n");
                        fclose($handle2); 
                        }
                    else echo "Error, Directory ".$OperationCenterSetup["MacroDirectory"]." not available.\n";                                 
                    break;          
		        case 'RT1900AC':
					$OperationCenter->read_routerdata_RT1900AC($router_categoryId, $host, $community, $binary, $debug);
					$routerFastPoll_categoryId = CreateCategory("SnmpFastPoll",$router_categoryId,1000);       	// Kategorie anlegen
					if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") )			//  
						{	
						$OperationCenter->read_routerdata_RT1900AC($routerFastPoll_categoryId, $host, $community, $binary, $debug);	
						}						
					break;					
                case 'RT2600AC':                
					$OperationCenter->read_routerdata_RT2600AC($router_categoryId, $host, $community, $binary, $debug);
					$routerFastPoll_categoryId = CreateCategory("SnmpFastPoll",$router_categoryId,1000);       	// Kategorie anlegen
					if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") )			//  
						{	
						$OperationCenter->read_routerdata_RT2600AC($routerFastPoll_categoryId, $host, $community, $binary, $debug);	
						}						
					break;					
                case 'RT6600AX':                
					$OperationCenter->read_routerdata_RT2600AC($router_categoryId, $host, $community, $binary, $debug);
					$routerFastPoll_categoryId = CreateCategory("SnmpFastPoll",$router_categoryId,1000);       	// Kategorie anlegen
					if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") )			//  
						{	
						$OperationCenter->read_routerdata_RT2600AC($routerFastPoll_categoryId, $host, $community, $binary, $debug);	
						}						
					break;					
                //SetValue($ScriptCounterID,1);
                //IPS_SetEventActive($tim3ID,true);
                }
			if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") ) 
				{
				/* eine SNMP basierte hochauflösende Auswertung erstellen, es können viele Daten in kurzer Auflösung erstellt werden, eine eigene Kategorie unter Router erstellen 
				 */
                echo "      SNMP Highspeed Abfrage für Router Typ ".$router['TYP']." wird hergestellt.\n";
				$fastPollId=@IPS_GetObjectIDByName("SnmpFastPoll",$router_categoryId);				// FastPoll Kategorie anlegen
				$routerSnmpLinks[$router_categoryId]["ID"] = $routerFastPoll_categoryId;					// für Webfront Darstellung, die Links sammeln
				$routerSnmpLinks[$router_categoryId]["NAME"] = $router['NAME']."_".$router['TYP']."_".$router['MANUFACTURER'];
				$results=IPS_GetChildrenIDs($fastPollId);
				foreach ($results as $result)
					{
					$nameWithTags=IPS_GetName($result);
					$nametags=explode("_",$nameWithTags);
					if (sizeof($nametags)>0)
						{
						if ( (isset($nametags[2])) && ($nametags[2]=="speed") )
							{
							//print_r($nametags);
							IPS_SetHidden($result,false);
							}
						else IPS_SetHidden($result,true);	// alle anderen variablen mit _ werden als Hilfsvariablen betrachtet und versteckt 	
						}
					}
				$SchalterFastPoll_ID    = CreateVariableByName($fastPollId, "SNMP Fast Poll",   0, "~Switch",     "", 100,$scriptIdOperationCenter);		
				$ifTable_ID             = CreateVariableByName($fastPollId, "ifTable",          3, "~HTMLBox",    "", 150);		                        
				$SchalterSortSnmp_ID    = CreateVariableByName($fastPollId, "Tabelle sortieren",1, "SortifTable", "", 110,$scriptIdOperationCenter);		
				IPS_SetHidden($SchalterFastPoll_ID,false);	
				IPS_SetHidden($ifTable_ID,false);	
				IPS_SetHidden($SchalterSortSnmp_ID,false);
				}		// ende fastpoll
			}			// ende status active
		}				// ende foreach

	/******************************************************

				INIT, Nachrichtenspeicher

	*************************************************************/


	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$NachrichtinputID          = CreateVariableByName($categoryId_Nachrichten,"Nachricht_Input",3);
	$log_OperationCenter=new Logging($systemDir."/Log_OperationCenter.csv",$NachrichtinputID);
    $MessageTableID            = @IPS_GetObjectIDByName("MessageTable", $NachrichtinputID);
    IPS_SetHidden($categoryId_Nachrichten,true);            // in der normalen Kategorie Darstellung ausblenden

	if ($_IPS['SENDER']=="Execute")
		{
		echo "\nNachrichtenspeicher ausgedruckt:\n";
		echo 	$log_OperationCenter->PrintNachrichten();
		}


	/******************************************************

				INIT, TraceRouteSpeicher

	*************************************************************/

	$categoryId_Route    = CreateCategory('TraceRouteVerlauf',   $CategoryIdData, 20);
	for ($i=1; $i<=20;$i++)
	   {
		$input = CreateVariableByName($categoryId_Route,"RoutePoint".$i,3, "", "", $i*5  );  /* ParentID Name Type Profile Identifier Position */
		}

	/******************************************************

				INIT, SystemInfo

	*************************************************************/

	$categoryId_SystemInfo	= CreateCategory('SystemInfo',   $CategoryIdData, 230);
	$HostnameID   			= CreateVariableByName($categoryId_SystemInfo, "Hostname", 3, "", "", 10);                      /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
	$SystemNameID			= CreateVariableByName($categoryId_SystemInfo, "Betriebssystemname", 3, "", "", 20);            /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */		
	$SystemVersionID		= CreateVariableByName($categoryId_SystemInfo, "Betriebssystemversion", 3, "", "", 30);         /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
	$SystemCodenameID		= CreateVariableByName($categoryId_SystemInfo, "SystemCodename", 3, "", "", 35);	
	$HotfixID				= CreateVariableByName($categoryId_SystemInfo, "Hotfix", 3, "", "", 40);                        /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
	$ExternalIP				= CreateVariableByName($categoryId_SystemInfo, "ExternalIP", 3, "", "", 100);                   /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
	$UptimeID				= CreateVariableByName($categoryId_SystemInfo, "IPS_UpTime", 3, "", "", 200);                   /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
	$VersionID				= CreateVariableByName($categoryId_SystemInfo, "IPS_Version", 3, "", "", 210); 	
	$javaVersionID   		= CreateVariableByName($categoryId_SystemInfo, "Java_Version", 3, "", "", 210); 
	$tailScaleInfoID   		= CreateVariableByName($categoryId_SystemInfo, "TailScaleInfo", 3, "", "", 220); 
    $MemoryID				= CreateVariableByName($categoryId_SystemInfo, "Memory", 3, "", "", 50); 	
    $UserID 				= CreateVariableByName($categoryId_SystemInfo, "User", 3, "", "", 50); 	                        // json Tabelle für alle User
    $AdministratorID     	= CreateVariableByName($categoryId_SystemInfo, "AdministratorID", 3, "", "", 50); 	            // eine Zahl, aber lassen wir sie als String
	
	/* zusaetzlich Table mit IP Adressen auslesen und in einem html Table darstellen */
    $ipTableHtml      		= CreateVariableByName($categoryId_SystemInfo, "TabelleGeraeteImNetzwerk", 3, '~HTMLBox',"", 500); // ipTable am Schluss anlegen
    $sumTableHtmlID      	= CreateVariableByName($categoryId_SystemInfo, "SystemInfoOverview",       3, '~HTMLBox',"", 900); // obige Informationen als kleine Tabelle erstellen

    IPS_SetHidden($categoryId_SystemInfo, true); 		// in der normalen OperationCenter Kategorie Darstellung die Kategorie verstecken, ist jetzt eh im Webfront


	/******************************************************

				INIT, Backup Funktionen

	*************************************************************/

    if (strtoupper($OperationCenterSetup["BACKUP"]["Status"])=="ENABLED")
        {
        echo "Init und Vorbereitung Backup Funktionen:\n";
        $categoryId_BackupFunction	= CreateCategory('Backup',   $CategoryIdData, 500);
        /* Hilfe zur Verwendung von CreateVariable      ($name,$type,$parentid, $position,$profile,$Action,$default,$icon ); verwendet ID zur Wiedererkennung von Variablen nach einer Namensänderung 
                        function CreateVariableByName($parentid, $name, $type, $profile="", $ident="", $position=0, $action=0)*/
        $StatusSchalterBackupID		       = CreateVariableByName($categoryId_BackupFunction, "Backup-Funktion"    ,1, "AusEinAuto",                        "", 100, $scriptIdOperationCenter);
        $StatusSchalterActionBackupID	   = CreateVariableByName($categoryId_BackupFunction, "Backup-Actions"     ,1, "RepairRestartFullIncrementCleanup", "", 110, $scriptIdOperationCenter);
        $StatusSchalterOverwriteBackupID   = CreateVariableByName($categoryId_BackupFunction, "Backup-Overwrite"   ,1, "KeepOverwriteAuto",                 "", 120, $scriptIdOperationCenter);
        $StatusSliderMaxcopyID             = CreateVariableByName($categoryId_BackupFunction, "Maxcopy per Session",1, "MaxCopySlider",                     "", 130, $scriptIdOperationCenter);

        $StatusBackupId				= CreateVariableByName($categoryId_BackupFunction, "Status"          ,3,  "",         "",   20); 		/* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
        $ConfigurationBackupId		= CreateVariableByName($categoryId_BackupFunction, "Configuration"   ,3,  "",         "", 2000); 		/* speichert die konfiguration im json Format */	
        $TokenBackupId		        = CreateVariableByName($categoryId_BackupFunction, "Token"           ,3,  "",         "", 2010); 		        /* verwendet einen Token um sicherzustellen das die Routine nur einmal ausgeführt wird */	
        $ErrorBackupId		        = CreateVariableByName($categoryId_BackupFunction, "LastErrorMessage",3,  "",         "", 2020); 		        /* verwendet einen Token um sicherzustellen das die Routine nur einmal ausgeführt wird */	
        $ExecTimeBackupId		    = CreateVariableByName($categoryId_BackupFunction, "ExecTime"        ,3,  "",         "", 2050); 		        /* maximale Durchlaufzeit um festzustellen ob Backup noch schneller gemacht werden kann */	
        $TableStatusBackupId        = CreateVariableByName($categoryId_BackupFunction, "StatusTable"     ,3,  "~HTMLBox", "", 5000);      /* man kann in einer tabelle alles mögliche darstellen */

        IPS_SetHidden($ConfigurationBackupId,true);
        IPS_SetHidden($TokenBackupId,true);

        $subnet='10.255.255.255';
        $BackupCenter=new BackupIpsymcon($subnet);

        $params=$BackupCenter->getConfigurationStatus("array");
        //print_R($params);
        if (isset($params["BackupDirectoriesandFiles"]) == false) $params["BackupDirectoriesandFiles"]=array("db","media","modules","scripts","webfront","settings.json");
        if (isset($params["BackupSourceDir"]) == false) $params["BackupSourceDir"]=$dosOps->correctDirName($BackupCenter->getSourceDrive());
        if (isset($params["cleanup"])===false) $params["cleanup"]="finished";
        if (isset($params["maxcopy"])===false) $params["maxcopy"]=500;
        if (isset($params["maxtime"])===false) $params["maxtime"]=100;
        $params["status"]="finished";
        $BackupCenter->setConfigurationStatus($params,"array");

        /* Default Parameter after Install */
        $BackupCenter->configBackup(["update" => "overwrite"]);    
        }
    else echo "Backup Funktionen nicht aktiviert.\n";

	/*******************************
     *
     * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
     *
     ********************************/

	echo "\n";
	
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."  (".$instanz.")\n";
		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r($result);
		}
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	if (IPS_ObjectExists($WFC10_ConfigId)) echo "Default WFC10_ConfigId fuer OperationCenter, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	if ( ((IPS_ObjectExists($WFC10_ConfigId)) !== true) || (IPS_GetName($WFC10_ConfigId) != "Administrator") )
		{
		$WFC10_ConfigId=$WebfrontConfigID["Administrator"];
		$WFC10User_ConfigId=$WebfrontConfigID["User"];
		echo "Default WFC10_ConfigId fuer OperationCenter auf ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.") geändert.\n\n";
		}
	echo "\n";

	/******************************************************
	 *
	 *  Webfront zusammenräumen
	 *
	 *******************************************************/
	
    if (isset($installedModules["IPSLight"])==true)
	    {  /* das IPSLight Webfront ausblenden, es bleibt nur die Glühlampe stehen */
    	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
	    $pos=strpos($WFC10_Path,"OperationCenter");
    	$ipslight_Path=substr($WFC10_Path,0,$pos)."IPSLight";
	    $categoryId_WebFront = CreateCategoryPath($ipslight_Path);
    	IPS_SetPosition($categoryId_WebFront,998);
	    IPS_SetHidden($categoryId_WebFront,true);
	    echo "   Administrator Webfront IPSLight auf : ".$ipslight_Path." mit OID : ".$categoryId_WebFront."\n";
	    }

    if (isset($installedModules["IPSPowerControl"])==true)
	    {  /* das IPSPower<Control Webfront ausblenden, es bleibt nur die Glühlampe stehen */
	    $WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
	    $pos=strpos($WFC10_Path,"OperationCenter");
	    $ipslight_Path=substr($WFC10_Path,0,$pos)."IPSPowerControl";
	    $categoryId_WebFront = CreateCategoryPath($ipslight_Path);
	    IPS_SetPosition($categoryId_WebFront,997);
	    IPS_SetHidden($categoryId_WebFront,true);
	    echo "   Administrator Webfront IPSPowerControl auf : ".$ipslight_Path." mit OID : ".$categoryId_WebFront."\n";
	    }

	/******************************************************
	 *
	 *			INIT, Webcams 
	 *				FTP Folder auslesen und auswerten
	 *				Auch die Datenstruktur für den CamOverview und den Snapshot Overview hier erstellen
	 *				Webfront siehe weiter unten
	 *
	 *************************************************************/

	/* Zusammenfassung:
	
	Es gibt IPSCam das sehr vielseitig programmiert wurde:
	------------------------------------------------------
	Visulaisiert Livestreams, steuert die PTZ Kameras an und stellt den Livestream gemeinsam mit Steuerungstasten in einem iFrame dar
	Erstellt automatisch Snapshots, die in einem Verzeichnis abgelegt werden
	und sogar zu einem kleinen Video arrangiert werden können - hab ich immer noch nicht herausgefunden wie
	Darstellung erfolgt
	   Unter Visualization/Webfront/Administrator nicht angelegt, liegt direkt auf data
	   Im Webfront Konfigurator auf CamTPACam/CamTPA/roottp, Splitpane mit zwei Categories verlinken auf NavigationPanel und CameraPanel
	
	Es gibt dazu Erweiterungen, Versuche im OperationCenter, die aktiviert werden wenn IPSCam installiert ist:
	----------------------------------------------------------------------------------------------------------
	Darstellung des Capture auf dem FTP Laufwerk
	   Unter Visualization/Webfront/Administrator auf
	   Im Webfront Konfigurator auf CamCapture
	Gesammelte Livestreams auf einer Seite
	   Unter Visualization auf
	   Im Webfront Konfigurator auf CamTPAOvw bzw. CamTPAOvw0 ...   
	Gesammelte Bilder auf einer Seite
	   Unter Visualization auf
	   Im Webfront Konfigurator auf CamPicture
	
	*/

	if ( (isset ($installedModules["WebCamera"])) || (isset ($installedModules["IPSCam"])) )
		{
		echo "\n"; 
		echo "=====================================================================================\n"; 
		echo "Modul WebCamera/IPSCam installiert. Im Verzeichnis Data die Variablen für Übersichtsdarstellungen Pics und Movies anlegen:\n"; 
		$CategoryIdDataOverview=CreateCategory("Cams",$CategoryIdData,2000,"");
		echo $CategoryIdDataOverview."  ".IPS_GetName($CategoryIdDataOverview)."/".IPS_GetName(IPS_GetParent($CategoryIdDataOverview))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($CategoryIdDataOverview)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($CategoryIdDataOverview))))."\n";
		$CamTablePictureID  = CreateVariableByName($CategoryIdDataOverview,"CamTablePicture", 3, "~HTMLBox");
		$CamMobilePictureID = CreateVariableByName($CategoryIdDataOverview,"CamMobilePicture",3, "~HTMLBox");
		$CamTableMovieID    = CreateVariableByName($CategoryIdDataOverview,"CamTableMovie",   3, "~HTMLBox");
        }

	if (isset ($installedModules["IPSCam"]))
		{
		echo "\n"; 
		echo "=====================================================================================\n"; 
		echo "Modul IPSCam installiert. Im Verzeichnis Data die Variablen für Übersichtsdarstellungen Pics und Movies anlegen:\n"; 
		
		/* es werden für alle in IPSCam registrierten Webcams auch Zusammenfassungsdarstellungen angelegt.
		   OperationCenter kopiert alle 150 Sekunden die verfügbaren Cam Snapshot in ein eigenes für die Darstellung im
		   Webfront geeignetes Verzeichnis */ 


		$repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
		$moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);
		$ergebnisCam=$moduleManagerCam->VersionHandler()->GetVersion('IPSCam');
		echo "  IPSCam Module Version : ".$ergebnisCam."\n";
		$WFC10Cam_Enabled	= $moduleManagerCam->GetConfigValueDef('Enabled', 'WFC10',false);				

		/* Das ist das html Objekt das in den Wefront Frame eingebunden wird:
		
		<iframe frameborder="0" width="100%" height="542px"  src="../user/IPSCam/IPSCam_Camera.php"</iframe> 
		
		im verlinkten webfront wird zwischen mobile und normalem webfront unterschieden. Die Nummer der Kamera wird als erster Parameter mitgegben.
		Die Routine generiert mit echo den html code.
		
		$camManager->GetHTMLWebFront(cameraIdx, Size, ShowPreDefPosButtons, ShowCommandButtons, ShowNavigationButtons)

		*/
		$html="";
		SetValue($CamTablePictureID,$html);

		echo "\nFtp Folder für Webcams zusammenräumen.\n";

		IPSUtils_Include ("IPSCam_Constants.inc.php",         "IPSLibrary::app::modules::IPSCam");
		IPSUtils_Include ("IPSCam_Configuration.inc.php",     "IPSLibrary::config::modules::IPSCam");

		if (isset ($OperationCenterConfig['CAM']))
			{
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
                if (isset($cam_config['FTPFOLDER']))            			/* möglicherweise sind keine FTP Folders zum zusammenräumen definiert */
                    {
                    echo "Bearbeite Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
                    $verzeichnis = $cam_config['FTPFOLDER'];
                    $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                    if ($cam_categoryId==false)
                    {
                        $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                        IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                        IPS_SetParent($cam_categoryId,$CategoryIdData);
                        }
                    $WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3); /* 0 Boolean 1 Integer 2 Float 3 String */
                    $WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
                    AC_SetLoggingStatus($archiveHandlerID,$WebCam_PhotoCountID,true);
                    AC_SetAggregationType($archiveHandlerID,$WebCam_PhotoCountID,1);      /* 0 normaler Wert 1 Zähler */
                    IPS_ApplyChanges($archiveHandlerID);

                    $WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
                    AC_SetLoggingStatus($archiveHandlerID,$WebCam_MotionID,true);
                    AC_SetAggregationType($archiveHandlerID,$WebCam_MotionID,0);      /* normaler Wwert */
                    IPS_ApplyChanges($archiveHandlerID);

                    // Test, ob ein Verzeichnis angegeben wurde
                    if ( is_dir ( $verzeichnis ))
                        {
                        // öffnen des Verzeichnisses
                        if ( $handle = opendir($verzeichnis) )
                            {
                            $count=0; $list="";
                            /* einlesen des Verzeichnisses        	*/
                            while (($file = readdir($handle)) !== false)
                                {
                                if (is_dir($verzeichnis.$file)==false)
                                    {
                                    $count++;
                                    $list .= $file."\n";
                                    }
                                }
                            echo "   Im Cam FTP Verzeichnis ".$verzeichnis." gibt es ".$count." neue Dateien.\n";
                            echo "   Letzter Eintrag von ".GetValue($WebCam_LetzteBewegungID)."\n";
                            //echo $list."\n";
                            }                   /* if handle */
                        }                       /* ende ifisdir */
                    }                           /* ende ftpfolder definiert */
				}                               /* ende foreach */
			}
		}

	/******************************************************

				INIT SysPing Variablen und auf Archivierung setzen

	*************************************************************/

	echo "===========================================\n";
	echo "Sysping Variablen anlegen.\n";

	$categoryId_SysPing           = CreateCategoryByName($CategoryIdData,'SysPing',    200);
    $categoryId_SysPingControl    = CreateCategoryByName($categoryId_SysPing, 'SysPingControl', 200);
    $categoryId_Sockets           = CreateCategoryByName($categoryId_SysPing, "SocketStatus",300);
    echo "Socket Status on OID $categoryId_Sockets, SysPingControl on $categoryId_SysPingControl.\n";

   /* Standardvariablen für den Betrieb von Socketstatus in TabPane Radisostatus setzen
    */  
    $variableSocketCCUHtmlId             = CreateVariableByName($categoryId_Sockets,"StatusCCUConnected",3, '~HTMLBox', "", 6000, null );        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0));      // als String, leichter lesbar
    $variableSocketCCUDutyCycleHtmlId    = CreateVariableByName($categoryId_Sockets,"StatusCCUDutyCycle",3, '~HTMLBox', "", 6010, null );        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0));      // als String, leichter lesbar

    /* Standardvariablen für den Betrieb von Sysping setzen 
     * Exectime
     * Counter für fast and slow exec mode  (5/60 Minuten)
     */
	$SysPingStatusID = CreateVariableByName($categoryId_SysPingControl, "SysPingExectime", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
	IPS_SetVariableCustomProfile($SysPingStatusID,"~UnixTimestamp");
    IPS_SetHidden($SysPingStatusID, true); 		// in der normalen Viz Darstellung Kategorie verstecken
	$SysPingCountID = CreateVariableByName($categoryId_SysPingControl, "SysPingCount", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
    IPS_SetHidden($SysPingCountID, true); 		// in der normalen Viz Darstellung Kategorie verstecken
    setValue($SysPingCountID,0);

	$SysPingTableID = CreateVariableByName($categoryId_SysPingControl, "SysPingTable",   3 /*String*/, '~HTMLBox', "", 6000, null );        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
    IPS_SetHidden($SysPingTableID, true); 		// in der normalen Viz Darstellung Kategorie verstecken
	$SysPingActivityTableID = CreateVariableByName($categoryId_SysPingControl, "SysPingActivityTable",   3 /*String*/, '~HTMLBox', "", 6010, null );        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
    IPS_SetHidden($SysPingActivityTableID, true); 		// in der normalen Viz Darstellung Kategorie verstecken


    /* für Diagnose Funktionen */

	$pname="SortifTableNameStateSince";
    $nameID=["Name","State","Since"];
    $webOps->createActionProfileByName($pname,$nameID,0);  // erst das Profil, dann die Variable, 0 ohne Selektor
	$SysPingSortTableID = CreateVariableByName($categoryId_SysPingControl, "SortPingTable",   1 /*Integer*/, $pname, "", 9000, $scriptIdOperationCenter );        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)

    $pname="UpdateSysTables";                                         // keine Standardfunktion, da Inhalte Variable
    $nameID=["Update"];
    $webOps->createActionProfileByName($pname,$nameID,0);  // erst das Profil, dann die Variable, 0 ohne Selektor
    $SysPingUpdateID          = CreateVariableByName($categoryId_SysPingControl,"Update", 1,$pname,"",1000,$scriptIdOperationCenter);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)


	if (isset ($installedModules["IPSCam"]))
		{
		foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
			{
			$StatusID = CreateVariableByName($categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	if (isset ($installedModules["LedAnsteuerung"]))
		{
		//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
        IPSUtils_Include ("LedAnsteuerung_Configuration.inc.php","IPSLibrary::config::modules::LedAnsteuerung");
		$device_config=LedAnsteuerung_Config();
		foreach ($device_config as $name => $config)
			{
			$StatusID = CreateVariableByName($categoryId_SysPing, "LED_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	if (isset ($installedModules["DENONsteuerung"]))
		{
		//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
        IPSUtils_Include ("DENONsteuerung_Configuration.inc.php","IPSLibrary::config::modules::DENONsteuerung");
		$device_config=Denon_Configuration();
		foreach ($device_config as $name => $config)
			{
			$StatusID = CreateVariableByName($categoryId_SysPing, "Denon_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	foreach ($OperationCenterConfig['ROUTER'] as $cam_name => $cam_config)
		{
		$StatusID = CreateVariableByName($categoryId_SysPing, "Router_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
		AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
		}

	foreach ($OperationCenterConfig['INTERNET'] as $name => $config)
		{
		$StatusID = CreateVariableByName($categoryId_SysPing, "Internet_".$name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
		AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
		}

	if (isset ($installedModules["IPSWeatherForcastAT"]))
		{
		$StatusID = CreateVariableByName($categoryId_SysPing, "Server_Wunderground", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
		AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
		}

	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "    Remote Access Server Status Information anlegen.\n";
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetConfigurationNew();	// das wären nur die Server mit STATUS active und LOGGING enabled
		$remServer    = RemoteAccess_GetServerConfig();
		foreach ($remServer as $Name => $Server)
			{
			if (strtoupper($Server["STATUS"])=="ACTIVE") 
				{
				echo "       Server Name : ".$Name."\n";
				$StatusID = CreateVariableByName($categoryId_SysPing, "Server_".$Name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
				AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
				}
			}
		}
	IPS_ApplyChanges($archiveHandlerID);

	/******************************************************

				INIT Homematic RSSI Read

	 * @author        Andreas Brauneis, mit Modifikationen Wolfgang JÖBSTL
	 * @version
	 *  Version 2.50.1, 02.07.2012<br/>
	 *
	*************************************************************/

	echo "=====================================================================\n";
	echo "Homematic RSSI Variablen für stromversorgte Homematic Devices anlegen.\n";

	$CategoryIdHardware = CreateCategoryPath('Hardware.Homematic');
	$CategoryIdRSSIHardware = CreateCategoryPath('Hardware.HomematicRSSI');
	
	$CategoryIdHomematicErreichbarkeit = CreateCategoryPath('Program.IPSLibrary.data.modules.OperationCenter.HomematicRSSI');
	$HomematicErreichbarkeit = CreateVariableByName($CategoryIdHomematicErreichbarkeit, "ErreichbarkeitHomematic", 3 /*String*/,  '~HTMLBox',       "",  50);
	$UpdateErreichbarkeit =    CreateVariableByName($CategoryIdHomematicErreichbarkeit, "UpdateErreichbarkeit",    1 /*Integer*/, '~UnixTimestamp', "", 500);
    
    $ExecuteRefreshID =        CreateVariableByName($CategoryIdHomematicErreichbarkeit, "UpdateDurchfuehren",      0 /*Boolean*/,  '~Switch',       "", 400, $scriptIdOperationCenter);

    $categoryId_DeviceManagement    = IPS_GetObjectIDByName('DeviceManagement',$CategoryIdData);
    $HMI_ReportStatusID       = IPS_GetObjectIDByName("HMI_ReportStatus",$categoryId_DeviceManagement);
    echo "Found HMI Creator Status $HMI_ReportStatusID in DeviceManagement.\n ";


	$CategoryIdHomematicGeraeteliste = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicDeviceList');
	$HomematicGeraeteliste =   CreateVariableByName($CategoryIdHomematicGeraeteliste,   "HomematicGeraeteListe",   3 /*String*/,   '~HTMLBox',      "",  50);

	$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
	$HomematicInventory =      CreateVariableByName($CategoryIdHomematicInventory,      "HomematicInventory",      3 /*String*/,   '~HTMLBox',      "",  60);

    echo "Ergebnisse von EvaluateHardware untersuchen.\n";
    /* EvaluateHardware erstellt taeglich um 1:10 eine neue Homematic Liste. 
     * Die function Homematic ist in EvaluateHardware_include in der Config gespeichert
     * nur die Geräte die gemeinsam mit Type abgespeichert wurden werden für die RSSI Ermittlung in Betracht gezogen
     *
     */
    if (function_exists("HomematicList"))
        {     
        $homematic=HomematicList();
        $seriennumernliste=array();
        echo "Auf Basis der HomaticList eine Seriennummer basierte Liste machen:\n";
        foreach ($homematic as $instance => $entry)
            {
            $adresse=explode(":",$entry["Adresse"])[0];
            if ( isset($seriennumernliste[$adresse])!=true )
                {
                $seriennumernliste[$adresse]["Adresse"]=$adresse;
                $seriennumernliste[$adresse]["Name"]=$entry["Name"];			
                if (isset($entry["Type"])==true) $seriennumernliste[$adresse]["Type"]=$entry["Type"];	
                else $seriennumernliste[$adresse]["Type"]="             ";		
                if (isset($entry["Device"])==true) $seriennumernliste[$adresse]["Device"]=$entry["Device"];
                else $seriennumernliste[$adresse]["Device"]="              ";
                $seriennumernliste[$adresse]["Channel"]=explode(":",$entry["Adresse"])[1];
                if ($entry["Protocol"]=="Funk") $seriennumernliste[$adresse]["Protocol"]=HM_PROTOCOL_BIDCOSRF;
                elseif ($entry["Protocol"]=="Funk") $seriennumernliste[$adresse]["Protocol"]=HM_PROTOCOL_BIDCOSWI;
                else $seriennumernliste[$adresse]["Protocol"]=$entry["Protocol"]; 
                echo "  ".str_pad($adresse,20).str_pad($entry["Name"],50).str_pad($seriennumernliste[$adresse]["Type"],20).str_pad($seriennumernliste[$adresse]["Device"],30).str_pad($seriennumernliste[$adresse]["Channel"],20).str_pad($entry["Protocol"],20)."\n";
                }		
            }
        echo "Es gibt ".sizeof($seriennumernliste)." Seriennummern also Homematic Geräte in der Liste. Die ohne Type Parameter oder HM_TYPE_BUTTON rausnehmen:\n";	
        foreach ($seriennumernliste as $zeile)
            {
            if (trim($zeile["Type"])=="") 
                {
                //echo "---> kein RSSI Monitoring : ";
                unset($seriennumernliste[$zeile["Adresse"]]);
                //echo "   ".$zeile["Adresse"]."  ".$zeile["Name"]."  ".$zeile["Type"]."  ".$zeile["Device"]."  \n";            
                }
            elseif (trim($zeile["Type"])=="HM_TYPE_BUTTON")  
                {
                unset($seriennumernliste[$zeile["Adresse"]]);
                }
            else 
                {
                //echo "   ".str_pad($zeile["Adresse"],18).str_pad($zeile["Name"],50).str_pad($zeile["Type"],20).str_pad($zeile["Device"],20)."  \n";
                }            

            }
        echo "Davon sind noch ".sizeof($seriennumernliste)." Geraete entweder Switch oder Dimmer und damit ohne Batteriebetrieb.\n";
        $homematicConfiguration=array();
        foreach ($seriennumernliste as $zeile)
            {
            //echo "   ".$zeile["Adresse"]."  ".$zeile["Name"]."  \n";
            echo "   ".str_pad($zeile["Adresse"],18).str_pad($zeile["Name"],50).str_pad($zeile["Type"],20).str_pad($zeile["Device"],20).str_pad($zeile["Channel"],20).str_pad($zeile["Protocol"],20)." \n";
            $name=explode(":",$zeile["Name"])[0];
            $homematicConfiguration[$name][]=$zeile["Adresse"];
            $homematicConfiguration[$name][]=$zeile["Channel"];
            $homematicConfiguration[$name][]=$zeile["Protocol"];
            $homematicConfiguration[$name][]=$zeile["Type"];				
            }
        /*ein neues Array $homematicConfiguration nach Name (Zeichen vor dem Doppelpunkt) angelegt */
        $i=0;
        foreach ($homematicConfiguration as $component=>$componentData) 
            {
            $propertyAddress  = $componentData[0];      // Adresse
            $propertyChannel  = $componentData[1];      // Channel  
            $propertyProtocol = $componentData[2];      // Protocol
            $propertyType     = $componentData[3];      // Type
            $propertyName     = $component;
            
            /* für jedes gerät aus der Liste alle Homematic module durchsuchen */
            $install=true; 
            foreach (IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}") as $HomematicModuleId ) 
                {
                //$HMAddress = HM_GetAddress($HomematicModuleId);
                $HMAddress = IPS_GetProperty($HomematicModuleId,'Address');
                if ($HMAddress=="$propertyAddress:$propertyChannel") 
                    {
                    /* ähnliche Nachricht kommt von CreateHomematicInstance */
                    //echo "Found existing HomaticModule '$propertyName' Address=$propertyAddress, Channel=$propertyChannel, Protocol=$propertyProtocol\n";
                    $install=false;
                    }
                }
            if ($install==true)		/* kein Device gefunden, daher installieren */
                {
                echo "*******Fehler, HomaticModule '$propertyName' muss komplett neu installiert werden.\n";
                $moduleManager->LogHandler()->Log("Create NEW HomaticModule '$propertyName' Address=$propertyAddress, Channel=$propertyChannel, Protocol=$propertyProtocol");
                $DeviceId = CreateHomematicInstance($moduleManager,
                                                $propertyAddress,
                                                $propertyChannel,
                                                $propertyName,
                                                $CategoryIdHardware,
                                                $propertyProtocol);
                }
            echo str_pad($i++,7);
            $SystemId = CreateHomematicInstance($moduleManager,
                                                $propertyAddress,
                                                0,
                                                $propertyName.':RSSI',                      //kein # mehr, : ist die Trennung
                                                $CategoryIdRSSIHardware,
                                                $propertyProtocol);

            if (IPS_GetName($SystemId)!=($propertyName.':RSSI')) 
                {
                echo "RSSI Instanz heisst jetzt $SystemId  (".IPS_GetName($SystemId)."). Name wurde geändert.\n";                
                IPS_SetName($SystemId,$propertyName.':RSSI');
                }
            if ($propertyType==HM_TYPE_SMOKEDETECTOR) 
                {
                $variableId = IPS_GetVariableIDByName('STATE', $DeviceId);
                CreateEvent ($propertyName, $variableId, $scriptIdSmokeDetector);
                } 
            }
        }
    else echo "HomematicList wurde noch nicht angelegt.\n";
	echo "------------------------------------\n";

		
	/********************************************************
	 *
	 *		INIT Detect Movement Event Darstellung und Auswertung
	 *
	 * Auswertung mit DetectMovement/Testmovement, alle anderen mit Autosteuerung/Webfront_Control
	 *
	 ***************************************************/

	/* Autosteuerung und Detectmovement verwenden folgendes Profil um die Event tabellen zu sortieren. */
		$pname="SortTableEvents";
		if (IPS_VariableProfileExists($pname) == false)
			{
			//Var-Profil erstellen
			IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
			IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
			IPS_SetVariableProfileValues($pname, 0, 10, 0); //PName, Minimal, Maximal, Schrittweite
			IPS_SetVariableProfileAssociation($pname, 0, "Event#", "", 	0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
			IPS_SetVariableProfileAssociation($pname, 1, "ID", "", 	0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 2, "Name", "", 		0x4e3127); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 3, "Pfad", "", 		0x4e7127); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 4, "Objektname", "", 		0x1ef1f7); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 5, "Module", "", 		0x1ef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 6, "Funktion", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 7, "Konfiguration", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 8, "Homematic", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 9, "DetectMovement", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 10, "Autosteuerung", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color			
			echo "Profil ".$pname." erstellt;\n";
			}

		$pname="SortTableEventsAlexa";
		if (IPS_VariableProfileExists($pname) == false)
			{
			//Var-Profil erstellen
			IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
			IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
            echo "Profil ".$pname." erstellt;\n";
            }
        /* Synchronization bei Profilen gibt es so nicht, SynchronizeProfiles erstellt neue nach einem Ebenbild */
        $profileConfig=IPS_GetVariableProfile ($pname);
        if ($profileConfig["MaxValue"] != 3) 
            {
            IPS_SetVariableProfileValues($pname, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
            IPS_SetVariableProfileAssociation($pname, 0, "ID", "", 	0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
            IPS_SetVariableProfileAssociation($pname, 1, "Name", "", 		0x4e3127); //P-Name, Value, Assotiation, Icon, Color
            IPS_SetVariableProfileAssociation($pname, 2, "Typ", "", 		0x1ef1f7); //P-Name, Value, Assotiation, Icon, Color
            IPS_SetVariableProfileAssociation($pname, 3, "Pfad", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
            echo "Profil ".$pname." upgedatet;\n";
            }

	if (isset ($installedModules["DetectMovement"]))
		{
	    echo "===========================================\n";
	    echo "DetectMovement Variablen für Webfront anlegen.\n";		
		$moduleManagerDM = new IPSModuleManager('DetectMovement',$repository);
		$CategoryIdDataDM     = $moduleManagerDM->GetModuleCategoryID('data');
		$CategoryIdAppDM      = $moduleManagerDM->GetModuleCategoryID('app');
		$scriptIdTestMovement = IPS_GetObjectIDByIdent('TestMovement', $CategoryIdAppDM);	

		$categoryId_DetectMovement    = CreateCategory('DetectMovement',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_DetectMovement, true); 		// in der normalen Viz Darstellung Kategorie verstecken
		$TableEventsDM_ID  = CreateVariableByName($categoryId_DetectMovement, "TableEvents",       3,"~HTMLBox",        "");
		$SchalterSortDM_ID = CreateVariableByName($categoryId_DetectMovement, "Tabelle sortieren", 1,"SortTableEvents", "", 0, $scriptIdTestMovement);		
		}

	/********************************************************
	 *
	 *		INIT Device Management
     *
     * Geraete Darstellung und Auswertung
	 *
	 ***************************************************/

    echo "===========================================\n";
    echo "Device Management Variablen für Webfront anlegen.\n";		

    $categoryId_DeviceManagement    = CreateCategory('DeviceManagement',   $CategoryIdData, 150);
    IPS_SetHidden($categoryId_DeviceManagement, true); 		// in der normalen Viz Darstellung Kategorie verstecken 

    /* Diese Werte wurden schon lange nicht mehr upgedatet, verwendet von AUtosteuerung ? */       
    $TableEventsDevMan_ID  = CreateVariableByName($categoryId_DeviceManagement, "TableEvents",       3, "~HTMLBox");
    $SchalterSortDevMan_ID = CreateVariableByName($categoryId_DeviceManagement, "Tabelle sortieren", 1, "SortTableEvents","",0,$scriptIdOperationCenter);		

    /* HMI_CreateReport hängt manchmal wenn CCU abstürzt. Diesen Zustand erkennen sonst funktioniert OperationCenter und vieles mehr nicht mehr. */
    $HMIStatus_DevMan_ID   = CreateVariableByName($categoryId_DeviceManagement, "HMI_ReportStatus",  3, "", "", 100);

	/********************************************************
	 *
	 *		INIT Autosteuerung Event Darstellung und Auswertung
	 *
	 * Auswertung mit Autosteuerung/WebfrontControl
	 *
	 ***************************************************/

	if (isset ($installedModules["Autosteuerung"]))
		{
	    echo "===========================================\n";
	    echo "Autosteuerung Variablen für Webfront anlegen.\n";		
        $moduleManagerAS = new IPSModuleManager('Autosteuerung',$repository);
		$CategoryIdDataAS     = $moduleManagerAS->GetModuleCategoryID('data');
		$CategoryIdAppAS      = $moduleManagerAS->GetModuleCategoryID('app');
		$scriptIdAutosteuerung  = IPS_GetObjectIDByIdent('WebfrontControl', $CategoryIdAppAS);		

		$categoryId_Autosteuerung    = CreateCategory('Autosteuerung',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_Autosteuerung, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsAS_ID  = CreateVariableByName($categoryId_Autosteuerung, "TableEvents",       3, "~HTMLBox"       );
		$SchalterSortAS_ID = CreateVariableByName($categoryId_Autosteuerung, "Tabelle sortieren", 1, "SortTableEvents", "", $scriptIdAutosteuerung);

	    echo "===========================================\n";
	    echo "Alexa Variablen für Webfront anlegen.\n";		

		$categoryId_AutosteuerungAlexa    = CreateCategory('Alexa',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_AutosteuerungAlexa, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsAlexa_ID  = CreateVariableByName($categoryId_AutosteuerungAlexa, "TableEvents",       3, "~HTMLBox");
		$SchalterSortAlexa_ID = CreateVariableByName($categoryId_AutosteuerungAlexa, "Tabelle sortieren", 1, "SortTableEventsAlexa", "", $categoryId_AutosteuerungAlexa,0,$scriptIdAutosteuerung);

	    echo "===========================================\n";
	    echo "Zusammenfassung Taster anzeigen.\n";		

		$categoryId_AutosteuerungButton    = CreateCategory('ButtonTasks',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_AutosteuerungButton, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsButton_ID = CreateVariableByName($categoryId_AutosteuerungButton, "TableEvents", 3, "~HTMLBox");

	    echo "===========================================\n";
	    echo "Zusammenfassung Timer anzeigen.\n";		

		$categoryId_AutosteuerungSimulation    = CreateCategory('TimerSimulation',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_AutosteuerungSimulation, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsButton_ID = CreateVariableByName($categoryId_AutosteuerungSimulation, "TableEvents",3, "~HTMLBox");
		}
		
	/********************************************************
	 *
	 *		INIT HUE Module Geraete Darstellung und Bedienung vom HUE Modul 
	 *
	 ***************************************************/
	
    echo "\n";	
    echo "Hue Bridge Instanzen suchen:\n";
	$HUE=$modulhandling->getInstances('HUEBridge');	
	$countHue = sizeof($HUE);
	echo "Es gibt insgesamt ".$countHue." SymCon Hue Instanzen.\n";
	if ($countHue>0)
		{
		$configHue=IPS_GetConfiguration($modulhandling->getInstances("HUEBridge")[0]);
		echo "   ".$configHue."\n";
		$categoryId_Hue = CreateCategoryPath('Hardware.HUE');		
		}
				
	/********************************************************
	 *
	 *		INIT HM Inventory Homematic Geraete Darstellung 
	 *
	 ***************************************************/
    
	if (isset ($installedModules["EvaluateHardware"])) 
		{ 
        echo "HM Inventory Homematic Geraete Darstellung:\n";
        /* Config des Homematic inventory creators für die Formattierung der Homematic tabellen 
            0 - HM address (default)
            1 - HM device type
            2 - HM channel type
            3 - IPS device name
            4 - HM device name	                                            */

        $pname="SortTableHomematic";
        if (IPS_VariableProfileExists($pname) == false)
            {
            //Var-Profil erstellen
            IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
            IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
            echo "Profil ".$pname." erstellt;\n";
            }
        IPS_SetVariableProfileValues($pname, 0, 7, 0); //PName, Minimal, Maximal, Schrittweite
        IPS_SetVariableProfileAssociation($pname, 0, "Adresse", "", 	0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
        IPS_SetVariableProfileAssociation($pname, 1, "DeviceType", "", 	0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
        IPS_SetVariableProfileAssociation($pname, 2, "ChannelType", "", 		0x4e3127); //P-Name, Value, Assotiation, Icon, Color
        IPS_SetVariableProfileAssociation($pname, 3, "Pfad", "", 		0x4e7127); //P-Name, Value, Assotiation, Icon, Color
        IPS_SetVariableProfileAssociation($pname, 4, "IPSDeviceName", "", 		0x1ef1f7); //P-Name, Value, Assotiation, Icon, Color
        IPS_SetVariableProfileAssociation($pname, 5, "DeviceName", "", 		0x1ef177); //P-Name, Value, Assotiation, Icon, Color
        IPS_SetVariableProfileAssociation($pname, 6, " ", "", 		0x1ef177); //P-Name, Value, Assotiation, Icon, Color		
        IPS_SetVariableProfileAssociation($pname, 7, "Update", "", 		0x1ef177); //P-Name, Value, Assotiation, Icon, Color		

        echo "Anzahl Homematic Sockets ermitteln, dann schauen ob es gleich viel Inventories gibt:\n";
        $modulhandling = new ModuleHandling();              // neu initialisiseren, filter entfernen
        $discovery = $modulhandling->getDiscovery();
        $modulhandling->addNonDiscovery($discovery);    // und zusätzliche noch nicht als Discovery bekannten Module hinzufügen
        $topologyLibrary = new TopologyLibraryManagement(true);                     // in EvaluateHardware Library, neue Form des Topology Managements, true für Debug
        print_R($discovery);
        echo "Auswertung der SocketList (I/O Instanzen).\n";
        $socket=array();
        $socket = $topologyLibrary->get_SocketList($discovery);
        print_r($socket);
        $countSocket=0;
        foreach ($socket as $modul => $module) 
            {
            switch ($modul)
                {
                case "Homematic":
                    foreach ($module as $name => $entry) 
                        {
                        echo "Homematic Socket $name .\n";
                        $countSocket++;
                        }
                    break;
                default:
                    echo "$modul Socket.\n";
                    break;
                }
            }

        $order=1000;	
        $HMIs=$modulhandling->getInstances('HM Inventory Report Creator');		
        $countHMI = sizeof($HMIs);
        echo "Es gibt insgesamt ".$countHMI." SymCon Homematic Inventory Instanzen. Entspricht üblicherweise der Anzahl der CCUs : $countSocket.\n";
        if ($countSocket != $countHMI) echo "Fehler, check amount of Homematic Inventory Creators.\n";
        if ($countHMI>0)
            {		
            /* Webfront Darstellung erfolgt im User Verzeichnis, dieses erstellen */
            $Verzeichnis="user/OperationCenter/Homematics/";
            if ($ipsOps->ipsVersion7check()) 
                {
                $Verzeichnis=IPS_GetKernelDir().$Verzeichnis;
                echo "IPS Version 7 oder später, wir übersiedeln in dieses Verzeichnis : $Verzeichnis \n";
                }
            else $Verzeichnis=IPS_GetKernelDir()."webfront/".$Verzeichnis;
            $Verzeichnis = str_replace('\\','/',$Verzeichnis);
            if ( is_dir ( $Verzeichnis ) == false ) $dosOps->mkdirtree($Verzeichnis);
            
            $CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
            $ipsOps->emptyCategory($CategoryIdHomematicInventory,["deleteCategories" => true,]);		
            
            foreach ($HMIs as $HMI)
                {
                $configHMI=IPS_GetConfiguration($HMI);
                echo "\n-----------------------------------\n";
                echo "Konfiguration für HMI Report Creator : ".$HMI."\n";
                echo $configHMI."\n";
                $configStruct=json_decode($configHMI,true);
                //print_r($configStruct);
                $aktVerzeichnis=IPS_GetProperty($HMI,"OutputFile");
                $neuVerzeichnis=$Verzeichnis.$HMI.'/HM_inventory.html';
                if ( is_dir ( $Verzeichnis.$HMI.'/' ) == false ) 
                    {
                    echo "Verzeichnis $neuVerzeichnis existiert noch nicht. Daher erstellen:\n";
                    $dosOps->mkdirtree($Verzeichnis.$HMI.'/');
                    }
                echo "Ausgabe Speicher Verzeichnis :".$aktVerzeichnis."\n";
                if ( $aktVerzeichnis != $neuVerzeichnis)
                    {
                    if ($ipsOps->ipsVersion7check()) echo "Verzeichnis für Webfront auf /user verschieben. In das Verzeichnis ".$neuVerzeichnis."\n";
                    else echo "Verzeichnis für Webfront auf webfront/user verschieben. In das Verzeichnis ".$neuVerzeichnis."\n";
                    IPS_SetProperty($HMI,"OutputFile",$neuVerzeichnis);
                    IPS_ApplyChanges($HMI);
                    }
                $CategoryIdHomematicCCU=CreateCategory("HomematicInventory_".$HMI,$CategoryIdHomematicInventory,$order+5);
                // function CreateVariableByName($id, $name, $type, $profile="", $ident="", $position=0, $action=0)
                $HomematicInventory = CreateVariableByName($CategoryIdHomematicCCU,IPS_GetName($HMI),3,"~HTMLBox","",$order+5);		// String
                $SortInventory = CreateVariableByName($CategoryIdHomematicCCU,"Sortieren",1,"SortTableHomematic","",$order,$scriptIdOperationCenter);		// String
                $html='<iframe frameborder="0" width="100%" height="4000px"  src="../user/OperationCenter/Homematics/'.$HMI.'/HM_inventory.html"</iframe>';
                HMI_CreateReport($HMI);	
                SetValue($HomematicInventory,$html);	

                if ($configStruct["SaveDeviceListInVariable"]) echo "   SaveDeviceListInVariable   \n";
                if ($configStruct["ShowHMConfiguratorDeviceNames"]) echo "   ShowHMConfiguratorDeviceNames   \n";
                if ($configStruct["ShowLongIPSDeviceNames"]) echo "   ShowLongIPSDeviceNames   \n";
                if ($configStruct["ShowMaintenanceEntries"]) echo "   ShowMaintenanceEntries   \n";
                if ($configStruct["ShowNotUsedChannels"]) echo "   ShowNotUsedChannels   \n";
                if ($configStruct["ShowVirtualKeyEntries"]) echo "   ShowVirtualKeyEntries   \n";

                $aktInterval=IPS_GetProperty($HMI,"UpdateInterval");
                if ( ($aktInterval < 60*60*48) && ($aktInterval > 60*60*12) ) echo "Aktualisierung im richtigen Interval.\n";

                $order +=10;
                }
            }
        }
        
    /* easySetupWebfront braucht im einfachsten Fall folgende Struktur
     * Tabpane 
     *   Subtabpane Auswertung
     *
     *   Subtabpane Nachrichten
     *      VariableID
     *          NAME
     *          ORDER
     *
     * Im Fall von Monitor hab ich vier Splittabs, drei horizontale und das letzte als vertikales
     *
     *
     */

    $paneName="Homematic";
    $webfront_links=array();
    $hmi=1; $order=100;

    $subCategories = IPS_GetChildrenIDs($CategoryIdHomematicInventory);
    foreach ($subCategories as $categoryID) 
        {
        echo $categoryID." ".IPS_GetName($categoryID)."\n";
        $variables = IPS_GetChildrenIDs($categoryID);
        foreach ($variables as $variableID) 
            {
            echo "    ".$variableID." ".IPS_GetName($variableID)."\n";
            $webfront_links[$paneName][IPS_GetName($categoryID)][$variableID]["NAME"]=IPS_GetName($variableID);
            if (IPS_GetName($variableID)=="Sortieren") $webfront_links[$paneName][IPS_GetName($categoryID)][$variableID]["ORDER"]=10;
            else $webfront_links[$paneName][IPS_GetName($categoryID)][$variableID]["ORDER"]=$order;
            $order += 10;
            }
        $webfront_links[$paneName][IPS_GetName($categoryID)]["CONFIG"] = array("type" => "link", "name" => "HMI".$hmi, "icon"=>"Notebook",);               // um sicherzustellen dass nicht irrtümlich noch eine Unterkatgeorie erkannt wird
        $hmi++;
        }
    $webfront_links[$paneName]["CONFIG"] = array("type" => "pane");
    
    print_r($webfront_links);


    /* Webfront Darstellung für Homematic Inventory in Administrator
     *
     */

	if ($WFC10_Enabled)
		{
        echo "\n";
        $configWF = $configWFront["Administrator"];
        $configWF["Path"].=".Homematic";
        $tabPaneParent=$configWF["TabPaneItem"];
        $configWF["TabPaneItem"]="Homematic";
        $configWF["TabPaneParent"]=$tabPaneParent;
        echo "Homematic Module im Administrator Webfront $tabPaneParent mit Namen ".$configWF["TabPaneItem"]." abspeichern.\n";
        echo "Visualization Kategorie : ".$configWF["Path"]."\n";

        $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern
        $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            // true für Debug
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);
        }
																																
	/* ----------------------------------------------------------------------------------------------------------------------------
	 * WebFront Installation
	 *
     * Es gibt einen eigenen Tab für die wichtigste Darstellung des Status
     *
	 * ungefilterte Standard-Darstellung vom Ordner Visualization/OperationCenter:
	 * im Ordner OperationCenter gibt es einen Link auf das ganze Data des OperationCenters und einzelne spezielle Links zu Sonderthemen
	 * es wird bereits begonnen, einzelne Untergruppen zu bilden
	 *
	 *	OperationCenter
	 *	Alexa
	 * 	Autosteuerung
	 *	Tasterdarstellung
	 *	Timersimulation
	 *	HUE
	 *	DetectMovement
	 * 	Nachrichtenverlauf
	 *	SystemInfo
	 *	Tracerouteverlauf
	 *
	 *	Untergruppen
	 *		Hardware
	 *
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    echo "\n===================================================================================\n";
    echo "Webfront Installation für den Systatus Monitor (Doctorbag):\n";
	$resultStream=array();
    if ($sumTableHtmlID !== false)              // stream 2 ist rechts oben
        {
        $resultStream[2]["Stream"]["Name"]="SysInfo";
        $resultStream[2]["Stream"]["OID"]=$sumTableHtmlID;
        $resultStream[2]["Data"]["Update"]=$SysPingUpdateID;

        }
    if ($MessageTableID !== false)              // stream 1 Mitte
        {
        $resultStream[1]["Stream"]["Name"]="Nachrichten";
        $resultStream[1]["Stream"]["OID"]=$MessageTableID;
        }
    if ($SysPingActivityTableID !== false)      // stream 0 ist rechts unten
        {
        $resultStream[0]["Stream"]["Name"]="SyspingActivityTabelle";
        $resultStream[0]["Stream"]["OID"]=$SysPingActivityTableID;
        }
    if ($SysPingTableID !== false)              // stream 4 ist links
        {
        $resultStream[4]["Stream"]["Name"]="SysPingTable";
        $resultStream[4]["Stream"]["OID"]=$SysPingTableID;
        $resultStream[4]["Data"]["Sort"]=$SysPingSortTableID;
        }

    echo "\n===================================================================================\n";
    echo "Webfront Installation für den SocketConnect Status in RadioStation, Tab Doctorbag:\n";
	$resultStreamRadio=array();
    if ($variableSocketCCUHtmlId !== false) 
        {
        $resultStreamRadio[0]["Stream"]["Name"]="CCUSocketConnect";
        $resultStreamRadio[0]["Stream"]["OID"]=$variableSocketCCUHtmlId;                       // CCU Socket Status
        $resultStreamRadio[0]["Data"]["Available"]=$HomematicErreichbarkeit;                // RSSI Level - Erreichbarkeit
        $resultStreamRadio[0]["Data"]["DutyCycle"]=$variableSocketCCUDutyCycleHtmlId;
        $resultStreamRadio[0]["Data"]["LastUpdateTime"]=$UpdateErreichbarkeit;
        $resultStreamRadio[0]["Data"]["Refresh"]=$ExecuteRefreshID;
        $resultStreamRadio[0]["Data"]["Homematic Inventory Report"]=$HMI_ReportStatusID;
        }


    $paneName="RemoteAccess"; $paneIcon = "People"; $paneOrder = 9000; 

    $configWFra = $configWFront["Administrator"];
    $configWFra["Path"].=".".$paneName;           // Category and WFC PanName Split or Category is same
    $tabPaneParent=$configWFra["TabPaneItem"];
    $configWFra["TabPaneItem"]=$paneName;
    $configWFra["TabPaneParent"]=$tabPaneParent;
    $configWFra["TabPaneName"]=$paneName;
    $configWFra["TabPaneIcon"]=$paneIcon;
    $configWFra["TabPaneOrder"]=$paneOrder;
    echo "$paneName Submodule im Administrator Webfront $tabPaneParent mit Namen ".$configWFra["TabPaneItem"]." abspeichern.\n";

    $categoryIdSubModul    = CreateCategory($paneName,      $CategoryIdData, 6000);             
    $htmlBoxID=CreateVariable("htmlBigBox",3, $categoryIdSubModul,150,"~HTMLBox",null, null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')

    $resultAccess=array();
    $resultAccess[0]["Stream"]["Name"]='ServerData';
    $resultAccess[0]["Stream"]["OID"]=$htmlBoxID;


    if (isset($configWFront["Administrator"]))
        {
        echo "Administrator:\n";
        $configWF = $configWFront["Administrator"];
        print_r($configWF); print_r($resultStream);
        installWebfrontMon($configWF,$resultStream); 
        
        $configWF["TabItem"]="RadioStatus";
        installWebfrontRadio($configWF,$resultStreamRadio);
        
        installWebfrontRemoteAccess($configWFra,$resultAccess);
		}

    if (isset($configWFront["User"]))
        {
        $configWF = $configWFront["User"];
        installWebfrontMon($configWF,$resultStream); 
		}

    if (isset($configWFront["Mobile"]))
        {
        $configWF=$configWFront["Mobile"];
        $categoryId_WebFront    = CreateCategoryPath($configWF["Path"],$configWF["PathOrder"],$configWF["PathIcon"]);        // Path=Visualization.Mobile.WebCamera    , 15, Image    
        $mobileId               = CreateCategoryPath($configWF["Path"].'.'.$configWF["Name"],$configWF["Order"],$configWF["Icon"]);        // Path=Visualization.Mobile.WebCamera    , 25, Image    
		EmptyCategory($mobileId);

        /* Mobile Links alle auf einen Haufen */
        if ($resultStream !== false) 
            {
            $count=sizeof($resultStream); 
            $j=0;       // es können indexe fehlen, trotzdem durchzählen
            for ($i=0;$i<$count;$i++) 
                {
                if (isset($resultStream[$j]["Stream"]["Name"]))
                    {
                    CreateLink($resultStream[$j]["Stream"]["Name"], $resultStream[$j]["Stream"]["OID"],  $mobileId , 10+$j*10);
                    $j++;
                    }
                }
            }	
        } 

    /**********************************************************************************
     * Path ist vom Operationcenter, das ist jetzt das DoctorBag
     * OperationCenter verlinkt auch direkt auf das Data vom Modul
     * Alexa mit der Auflistung aller befehle kommt von Autosteuerung, ist mittlerweile direkt dort angeordnet
     * Autosteuerung gibt einen Überblick aller Befehle - ab ins DoctorBag
     *
     *
     *
     */

	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		CreateLinkByDestination('OperationCenter', $CategoryIdData,    $categoryId_WebFront,  10);
		if (isset ($installedModules["Autosteuerung"]))
            {
            //CreateLinkByDestination('Alexa', $categoryId_AutosteuerungAlexa,    $categoryId_WebFront,  80);		
            CreateLinkByDestination('Autosteuerung', $categoryId_Autosteuerung,    $categoryId_WebFront,  80);		
            CreateLinkByDestination('TasterDarstellung', $categoryId_AutosteuerungButton,    $categoryId_WebFront,  80);		
            CreateLinkByDestination('TimerSimulation', $categoryId_AutosteuerungSimulation,    $categoryId_WebFront,  80);		
            }
		if ($countHue>0)	CreateLinkByDestination('HUE', $categoryId_Hue,    $categoryId_WebFront,  120);			
		if (isset ($installedModules["DetectMovement"]))	CreateLinkByDestination('DetectMovement', $categoryId_DetectMovement,    $categoryId_WebFront,  90);		
		
        //CreateLinkByDestination('Nachrichtenverlauf', $categoryId_Nachrichten,    $categoryId_WebFront,  200);
		//CreateLinkByDestination('SystemInfo', $categoryId_SystemInfo,    $categoryId_WebFront,  800);

        if (strtoupper($OperationCenterSetup["BACKUP"]["Status"])=="ENABLED")
            {
		    CreateLinkByDestination('Backup', $categoryId_BackupFunction,    $categoryId_WebFront,  850);
            }
		CreateLinkByDestination('TraceRouteVerlauf', $categoryId_Route,    $categoryId_WebFront,  900);

		/* für Hardware */
		$categoryId_Hardware = CreateCategory("Hardware",  $categoryId_WebFront, 40);
		CreateLinkByDestination('HomematicErreichbarkeit', $CategoryIdHomematicErreichbarkeit,    $categoryId_Hardware,  100);		// Link auf eine Kategorie, daher neues Tab
		CreateLinkByDestination('HomematicGeraeteliste', $CategoryIdHomematicGeraeteliste,    $categoryId_Hardware,  110);
		CreateLinkByDestination('HomematicInventory', $CategoryIdHomematicInventory,    $categoryId_Hardware,  120);
	
		/* für Router */
		if (sizeof($routerSnmpLinks)>0)
			{
			echo "   Eigene Kategorie Router erstellen und folgende Links darin anzeigen:\n";
			print_r($routerSnmpLinks);
			$categoryId_Router = CreateCategory("Router",  $categoryId_WebFront, 20);
			foreach ($routerSnmpLinks as $routerSnmpLink)
				{
				CreateLinkByDestination($routerSnmpLink["NAME"], $routerSnmpLink["ID"],    $categoryId_Router,  100);		// Link auf eine Kategorie, daher neues Tab
				}
			}

		/* Zusammenräumen, alte Ordnung eliminieren */
		$linkId=@IPS_GetLinkIDByName('HomematicErreichbarkeit', $categoryId_WebFront);
		if ($linkId) IPS_DeleteLink($linkId); 
		$linkId=@IPS_GetLinkIDByName('HomematicGeraeteliste', $categoryId_WebFront);
		if ($linkId) IPS_DeleteLink($linkId); 
		}

	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren: ".$WFC10User_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);

		}

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: ".$Mobile_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);

		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: ".$Retro_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($Retro_Path);

		}

	/***********************************************************************
	 *
	 * fuer IPSCam verschiedene Overview Darstellungen machen
	 * 
	 * Snapshot Darstellung der in der IPSCam erstellten Snapshot Dateien (current0 bis x), Zeitraster einstellbar
	 * Live Overview für jeweils 4 Cameras machen, Overview1 usw. sollte sich den lokalen Stream holen, anstelle extern, 
	 *		abhängig von der verfügbaren Bandbreite
	 * Capture Darstellung, aus dem FTP Verzeichnissen entsprechende Bilder auswählen
	 *
	 * es gibt zwei Kamera Config Files, für das IPSCam Modul und innerhalb des OperationCenter Config Teil für die CAMs
	 *		IPSCam sind alle Kameras, lokal und remote
	 *		OC Cams sind nur die lokalen Kameras, die teilweise, muessen nicht alle sein auf einem FTP Verzeichis Alarm-Capture Bilder ablegen 
	 *
	 ******************************************************************/


	if ( isset ($installedModules["IPSCam"] ) ) 
		{
		echo "\n";
		echo "=====================================================================================\n"; 
		echo "Modul IPSCam installiert. Die Überblickdarstellung im WebCam Frontend wenn gewünscht anlegen:\n"; 

		if ($WFC10Cam_Enabled)
			{
			echo "IPSCam Überblickdarstellung für Administrator im WebCam Frontend anlegen:\n";
			
			// ----------------------------------------------------------------------------------------------------------------------------
			// Program Installation
			// ----------------------------------------------------------------------------------------------------------------------------
			
			$CategoryIdCamData  		= $moduleManagerCam->GetModuleCategoryID('data');
			$CategoryIdCamApp   		= $moduleManagerCam->GetModuleCategoryID('app');
			$categoryIdCams     		= CreateCategory('Cams',    $CategoryIdCamData, 20);
			$scriptIdActionScript   = IPS_GetScriptIDByName('IPSCam_ActionScript', $CategoryIdCamApp);			
			
			$WFC10Cam_Path        	 = $moduleManagerCam->GetConfigValue('Path', 'WFC10');
			$WFC10Cam_TabPaneItem    = $moduleManagerCam->GetConfigValue('TabPaneItem', 'WFC10');
			$WFC10Cam_TabPaneParent  = $moduleManagerCam->GetConfigValue('TabPaneParent', 'WFC10');
			$WFC10Cam_TabPaneName    = $moduleManagerCam->GetConfigValue('TabPaneName', 'WFC10');
			$WFC10Cam_TabPaneIcon    = $moduleManagerCam->GetConfigValue('TabPaneIcon', 'WFC10');
			$WFC10Cam_TabPaneOrder   = $moduleManagerCam->GetConfigValueInt('TabPaneOrder', 'WFC10');
			$WFC10Cam_TabItem        = $moduleManagerCam->GetConfigValue('TabItem', 'WFC10');
			$WFC10Cam_TabName        = $moduleManagerCam->GetConfigValue('TabName', 'WFC10');
			$WFC10Cam_TabIcon        = $moduleManagerCam->GetConfigValue('TabIcon', 'WFC10');
			$WFC10Cam_TabOrder       = $moduleManagerCam->GetConfigValueInt('TabOrder', 'WFC10');
			echo "WF10 Administrator\n";
			echo "  Path          : ".$WFC10Cam_Path."\n";
			echo "  TabPaneItem   : ".$WFC10Cam_TabPaneItem."\n";
			echo "  TabPaneParent : ".$WFC10Cam_TabPaneParent."\n";
			echo "  TabPaneName   : ".$WFC10Cam_TabPaneName."\n";
			echo "  TabPaneIcon   : ".$WFC10Cam_TabPaneIcon."\n";
			echo "  TabPaneOrder  : ".$WFC10Cam_TabPaneOrder."\n";
			echo "  TabItem       : ".$WFC10Cam_TabItem."\n";
			echo "  TabName       : ".$WFC10Cam_TabName."\n";
			echo "  TabIcon       : ".$WFC10Cam_TabIcon."\n";
			echo "  TabOrder      : ".$WFC10Cam_TabOrder."\n";

			// ===================================================================================================
			// Add Camera Devices
			// ===================================================================================================
			
			IPSUtils_Include ("IPSCam_Constants.inc.php",      "IPSLibrary::app::modules::IPSCam");
			IPSUtils_Include ("IPSCam_Configuration.inc.php",  "IPSLibrary::config::modules::IPSCam");
			

			if (false)		// kompletten iFrame einbinden und suchen
				{
				/* der iFrame für die Movie Darstellung wird von IPSCam übernommen, damit wird ein eigenen Cam.php File aufgerufen */
				$camConfig = IPSCam_GetConfiguration();
				$result=array();
				foreach ($camConfig as $idx=>$data) 
					{
					$categoryIdCamX      = CreateCategory($idx, $categoryIdCams, $idx);
					$variableIdCamHtmlX  = IPS_GetObjectIDByIdent(IPSCAM_VAR_CAMHTML, $categoryIdCamX);
					$variableIdCamStreamX  = IPS_GetObjectIDByIdent(IPSCAM_VAR_CAMSTREAM, $categoryIdCamX);
					echo "\nKamera ".$idx." (".$data["Name"].") auf Kategorie : ".$categoryIdCamX." (".IPS_GetName($categoryIdCamX).") mit HTML Objekt auf : ".$variableIdCamHtmlX."\n";
					//print_r($data);
					$result[$idx]["OID"]=$variableIdCamHtmlX;
					$result[$idx]["Stream"]=$variableIdCamStreamX;
					$result[$idx]["Name"]=$data["Name"];
					$cam_name="Cam_".$data["Name"];
					$cam_categoryId=@IPS_GetObjectIDByName($cam_name,$CategoryIdData);
					if ($cam_categoryId==false) echo "   Name ungleich zu OperationCenter.\n";
					}
				$anzahl=sizeof($result);
				echo "Es werden im Snapshot Overview insgesamt ".$anzahl." Live Cameras (lokal und remote) angezeigt.\n";

	    		//echo "\n"; print_r($result);
				echo "\n";
	
				foreach ($result as $cam)
					{
					$media=IPS_GetMedia($cam["Stream"]);
					echo "    ".$cam["Name"]."     ".$cam["OID"]."   ";
					echo "(".IPS_GetName($cam["OID"])."/".IPS_GetName(IPS_GetParent($cam["OID"]))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($cam["OID"])))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($cam["OID"])))).")";
					echo "    ".$media["MediaFile"]."  ";
					echo "\n";
					}
				echo "\n      \"".htmlspecialchars(GetValue($cam["OID"]))."\"\n";
				//$media=IPS_GetMedia($cam["Stream"]);
				//print_r($media);
				}
			
			if (function_exists('Cam_GetConfiguration'))
				{
				$camConfig = Cam_GetConfiguration();
				}
			else $camConfig=array();
				
			$IntExt="EXT";

			$result=array();
			foreach ($camConfig as $idx=>$data) 
				{
				$ext=(string)$idx;
				$categoryIdCamX      = @IPS_GetObjectIDByName($idx, $categoryIdCams);
				/* der iframe für die Darstellung des Cam Livestreams heisst für jede Kamera gleich =>  CamHtml*/
				$variableIdCamStreamX  = @IPS_GetObjectIDByIdent(IPSCAM_VAR_CAMSTREAM, $categoryIdCamX);
				if ( ($variableIdCamStreamX !== false) && ($categoryIdCamX !== false) )
					{
					$categoryIdNewCamX=CreateCategory($ext,$CategoryIdDataOverview,$idx+200);
					EmptyCategory($categoryIdNewCamX);			// die Config eines Mediastreams wird nachdem er einmal angelegt wurde nicht mehr geändert, daher loeschen
					echo "\nKamera ".$idx." (".$data["CAM_PROPERTY_NAME"].") auf Kategorie : ".$categoryIdCamX." (".IPS_GetName($categoryIdCamX).") mit Stream Objekt auf : ".$variableIdCamStreamX."\n";
					print_r($data);
					$index="CAM_PROPERTY_COMPONENT_".$IntExt;
					if ( isset($data[$index]) )
						{
						$componentParams     = $data[$index];
						$component           = IPSComponent::CreateObjectByParams($componentParams);
						$urlStream           = $component->Get_URLLiveStream();
						$variableIdNewCamStreamX= CreateMediaStream (IPSCAM_VAR_CAMSTREAM, $categoryIdNewCamX, $urlStream,'Image', 40); 

						$result[$idx]["OID"]=$variableIdNewCamStreamX;
						$result[$idx]["Name"]=$data["CAM_PROPERTY_NAME"];
						}
					}
				}
			$anzahl=sizeof($result);
			echo "Es werden im Snapshot Overview der Stream von insgesamt ".$anzahl." Live Cameras (lokal und remote) angezeigt.\n";

			print_r($result);


            $ipsOps = new ipsOps();
            $configWF=$ipsOps->configWebfront($moduleManagerCam);
            //$ipsOps->writeConfigWebfrontAll($configWF);
            //print_R($configWF);

            installWebfrontCam($configWF["Administrator"],$OperationCenterConfig,$CategoryIdData);

			/************************
			 *
			 * Anlegen des Capture Overviews von allen Kameras
			 * einzelne Tabs pro Kamera mit den interessantesten Bildern der letzten Stunden oder Tage
			 * die Daten werden aus den FTP Verzeichnissen gesammelt.
			 *
			 ***********************
							
			echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: \"".$WFC10Cam_Path."_Capture\"\n";			
			$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path."_Capture");
			EmptyCategory($categoryId_WebFrontAdministrator);
			IPS_SetHidden($categoryId_WebFrontAdministrator, true); 		// in der normalen Viz Darstellung Kategorie verstecken

			//CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
			CreateWFCItemTabPane  ($WFC10_ConfigId, "CamCapture", $WFC10Cam_TabPaneItem, ($WFC10Cam_TabOrder+1000), 'CamCapture', $WFC10Cam_TabIcon);
			if (isset ($OperationCenterConfig['CAM']))
				{
				$i=0;
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
					$i++; $found=false;
                    if (isset ($cam_config['FTPFOLDER']))         
                        {
                        if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                            {
                            echo "  Webfront Tabname für ".$cam_name." \n";
                            $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                            if ($cam_categoryId==false)
                                {
                                $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                                IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                                IPS_SetParent($cam_categoryId,$CategoryIdData);
                                }
                            $categoryIdCapture  = CreateCategory("Cam_".$cam_name,  $categoryId_WebFrontAdministrator, 10*$i);
                            CreateWFCItemCategory  ($WFC10_ConfigId, "Cam_".$cam_name,  "CamCapture",    (10*$i),  "Cam_".$cam_name,     $WFC10Cam_TabIcon, $categoryIdCapture , 'false' );
                            echo "     CreateWFCItemCategory  ($WFC10_ConfigId, Cam_$cam_name,  CamCapture,    ".(10*$i).",  Cam_$cam_name,     $WFC10Cam_TabIcon, $categoryIdCapture, false);\n";
                            $pictureFieldID = CreateVariableByName($categoryIdCapture, "pictureField",   3 , '~HTMLBox', "", 50);
                            $box='<iframe frameborder="0" width="100%">     </iframe>';
                            SetValue($pictureFieldID,$box);
                            $found=true;
                            }
                        }
                    if (!$found)
                        {
                        echo "  Webfront Tabname für ".$cam_name." wird nicht mehr benötigt, loeschen.\n";
                        $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                        if ($cam_categoryId !== false)
                            {
                            DeleteWFCItems($WFC10_ConfigId, "Cam_".$cam_name);    
                            }
                        }
					}
				}  */
				
			/************************
			 *
			 * Anlegen des Picture Overviews von allen Kameras
			 * ein Tab für alle Kameras, es wird nicht der Livestream 
			 * sondern Bilder die regelmäßig per Button aktualisiert werden müssen in einer gemeinsamen html Tabelle angezeigt
			 *
			 ************************
													
			echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: \"".$WFC10Cam_Path.".Pictures\"\n";
			$categoryId_WebFrontPictures         = CreateCategoryPath($WFC10Cam_Path."Pictures");
			EmptyCategory($categoryId_WebFrontPictures);				// ausleeren und neu aufbauen, die Geschichte ist gelöscht !
			IPS_SetHidden($categoryId_WebFrontPictures, true); 		// in der normalen Viz Darstellung Kategorie verstecken
				
			// TabPaneItem anlegen und wenn vorhanden vorher loeschen 
			$tabItem = $WFC10Cam_TabPaneItem.$WFC10Cam_TabItem."Pics";
			if ( exists_WFCItem($WFC10_ConfigId, $WFC10Cam_TabPaneItem."Pics") )
		 		{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  löscht TabItem : ".$WFC10Cam_TabPaneItem.".Pics\n";
				DeleteWFCItems($WFC10_ConfigId, $WFC10Cam_TabPaneItem."Pics");
				}
			else
				{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  TabItem : ".$WFC10Cam_TabPaneItem.".Pics nicht mehr vorhanden.\n";
				}	
			echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$WFC10Cam_TabPaneItem." in ".$WFC10Cam_TabPaneParent."\n";
			//CreateWFCItemTabPane   ($WFC10_ConfigId,"CamPictures" ,$WFC10Cam_TabPaneItem, $WFC10Cam_TabPaneOrder, $WFC10Cam_TabPaneName, $WFC10Cam_TabPaneIcon);
			CreateWFCItemCategory  ($WFC10_ConfigId, "CamPictures" ,$WFC10Cam_TabPaneItem,   10, 'CamPictures', $WFC10Cam_TabPaneIcon, $categoryId_WebFrontPictures   , 'false' );
			// im TabPane entweder eine Kategorie oder ein SplitPane und Kategorien anlegen 
			//CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem,   "CamPictures",   10, '', '', $categoryId_WebFrontPictures   , 'false' );

			// definition CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="") {
			CreateLinkByDestination("Pictures", $CamTablePictureID, $categoryId_WebFrontPictures,  10,"");			*/					

			/************************
			 *
			 * Anlegen des Livestream Overviews von allen Kameras
			 * ein Tab für jeweils vier Kameras, es wird nur der Livestream angezeigt - alternativ ist es auch noch möglich die IPSCam iframes mit der Camerasteuerung anzuzeigen
			 * unbenötigte Tabs sollten auch wieder gelöscht werden.
			 *
			 ************************/
				
			/* zuerst die Kategorien in Visualization aufbauen */
			$tabs=(integer)($anzahl/4);
			if ($tabs>0)
				{
				/* mehr als 4 Kameras, zusaetzliche Tabs eröffnen */
				echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur für Livestream in mehreren Tabs installieren in: ".$WFC10Cam_Path." \n";					
				$tabItem = $WFC10Cam_TabPaneItem.'Ovw';																				
				DeleteWFCItems($WFC10_ConfigId, $tabItem);			// Einzel Tab loeschen
				for ($i=0;$i<=$tabs;$i++)
					{
					if ($i==0) $ext="";
					else $ext=(string)$i;
					echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur für Livestream jetzt installieren in: ".$WFC10Cam_Path.$ext." \n";					
					$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path.$ext);
					EmptyCategory($categoryId_WebFrontAdministrator);
        			IPS_SetHidden($categoryId_WebFrontAdministrator, true); 		// in der normalen Viz Darstellung Kategorie verstecken
                    
					$categoryIdLeftUp  = CreateCategory('LeftUp',  $categoryId_WebFrontAdministrator, 10);
					$categoryIdRightUp = CreateCategory('RightUp', $categoryId_WebFrontAdministrator, 20);						
					$categoryIdLeftDn  = CreateCategory('LeftDn',  $categoryId_WebFrontAdministrator, 30);
					$categoryIdRightDn = CreateCategory('RightDn', $categoryId_WebFrontAdministrator, 40);						

					$tabItem = $WFC10Cam_TabPaneItem.'Ovw'.$ext;																				
					CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10Cam_TabPaneItem, ($WFC10Cam_TabOrder+100), "Overview".$ext, $WFC10Cam_TabIcon, 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
					CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Left", $tabItem, 10, "Left", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
					CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Right", $tabItem, 20, "Right", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			
					CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Left', $tabItem."_Left", 10, '', '', $categoryIdLeftUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
					CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Right', $tabItem."_Right", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
					CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Left', $tabItem."_Left", 20, '', '', $categoryIdLeftDn   /*BaseId*/, 'false' /*BarBottomVisible*/);
					CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Right', $tabItem."_Right", 20, '', '', $categoryIdRightDn   /*BaseId*/, 'false' /*BarBottomVisible*/);

					if (sizeof($result)>($i*4)) CreateLink($result[($i*4)+0]["Name"], $result[($i*4)+0]["OID"], $categoryIdLeftUp, 10);
					if (sizeof($result)>(($i*4)+1)) CreateLink($result[($i*4)+1]["Name"], $result[($i*4)+1]["OID"], $categoryIdRightUp, 10);
					if (sizeof($result)>(($i*4)+2)) CreateLink($result[($i*4)+2]["Name"], $result[($i*4)+2]["OID"], $categoryIdLeftDn, 10);
					if (sizeof($result)>(($i*4)+3)) CreateLink($result[($i*4)+3]["Name"], $result[($i*4)+3]["OID"], $categoryIdRightDn, 10);
					}
				}
			else		/* nur ein Tab anlegen, Anzahl dargestellter Kameras kleiner gleich 4 */
				{
				echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur für Livestream in einem Tab installieren in: ".$WFC10Cam_Path." \n";
				for ($i=0;$i<3;$i++)	// sicherheitshalber Tab 0,1,2 loeschen wenn vorhanden
					{
					$ext=(string)$i;
					$tabItem = $WFC10Cam_TabPaneItem.'Ovw'.$ext;																				
					if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
		 				{
						DeleteWFCItems($WFC10_ConfigId, $tabItem);
						}
					}
				$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path);
				EmptyCategory($categoryId_WebFrontAdministrator);
				$categoryIdLeftUp  = CreateCategory('LeftUp',  $categoryId_WebFrontAdministrator, 10);
				$categoryIdRightUp = CreateCategory('RightUp', $categoryId_WebFrontAdministrator, 20);						
				$categoryIdLeftDn  = CreateCategory('LeftDn',  $categoryId_WebFrontAdministrator, 30);
				$categoryIdRightDn = CreateCategory('RightDn', $categoryId_WebFrontAdministrator, 40);						

				$tabItem = $WFC10Cam_TabPaneItem.'Ovw';																				
				CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10Cam_TabPaneItem, ($WFC10Cam_TabOrder+100), "Overview", $WFC10Cam_TabIcon, 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
				CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Left", $tabItem, 10, "Left", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
				CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Right", $tabItem, 20, "Right", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			
				CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Left', $tabItem."_Left", 10, '', '', $categoryIdLeftUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
				CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Right', $tabItem."_Right", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
				CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Left', $tabItem."_Left", 20, '', '', $categoryIdLeftDn   /*BaseId*/, 'false' /*BarBottomVisible*/);
				CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Right', $tabItem."_Right", 20, '', '', $categoryIdRightDn   /*BaseId*/, 'false' /*BarBottomVisible*/);

				if (sizeof($result)>0) CreateLink($result[0]["Name"], $result[0]["OID"], $categoryIdLeftUp, 10);
				if (sizeof($result)>1) CreateLink($result[1]["Name"], $result[1]["OID"], $categoryIdRightUp, 10);
				if (sizeof($result)>2) CreateLink($result[2]["Name"], $result[2]["OID"], $categoryIdLeftDn, 10);
				if (sizeof($result)>3) CreateLink($result[3]["Name"], $result[3]["OID"], $categoryIdRightDn, 10);
				}			
			}			// ende WFCCam enabled */
			
		}

	if (isset ($installedModules["OperationCenter"])) 
		{
		$log_Install->LogMessage("Install Module OperationCenter abgeschlossen.");
        echo "Install Module OperationCenter abgeschlossen.\n";
		}

	// ----------------------------------------------------------------------------------------------------------------------------
	// Local Functions
	// ----------------------------------------------------------------------------------------------------------------------------



    /*
     * configWF braucht eine gewisse Mindestanzahlan Parametern sonst passiert nichts
     * verwendet wfcHandling
     *
     */
    function installWebfrontRemoteAccess($configWF,$resultStream=false)
        {
        if ( (isset($configWF["Path"])) && (isset($configWF["TabPaneItem"])) && (isset($configWF["TabItem"])) && (isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false)) )
            {
            $wfcHandling =  new WfcHandling();                              // ohne Parameter wird die Konfiguration der Webfronts editiert, sonst werden die Standard Befehle der IPS Library verwendet
            $wfcHandling->read_WebfrontConfig($configWF["ConfigId"]);         // register Webfront Confígurator ID  

            $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.OperationCenter
            
            echo "installWebfrontRemoteAccess, Path ".$configWF["Path"]." with this Webfront Tabpane Item Name : ".$configWF["TabPaneItem"]."\n";
            echo "----------------------------------------------------------------------------------------------------------------------------------\n";

        	$wfcHandling->CreateWFCItemCategory ($configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"], $categoryId_WebFront /*ID of Category*/); 
            
            if ($resultStream !== false)        
                {
                $resultStream[0]["Stream"]["Link"]=$categoryId_WebFront;
                $count=sizeof($resultStream);           // spaetestens jetzt sind es immer 1 Eintrage#
                echo "Jetzt diese Kategorien zuordnen: ".json_encode($resultStream)."\n";
                //print_r($resultStream);               // Name, OID, Link
                for ($i=0;$i<$count;$i++) 
                    {
                    if (isset($resultStream[$i]["Stream"]["Name"]))
                        {
                        CreateLink($resultStream[$i]["Stream"]["Name"], $resultStream[$i]["Stream"]["OID"],  $resultStream[$i]["Stream"]["Link"], 10+$i*10);        // Name, OID und Parent für Link
                        if (isset($resultStream[$i]["Data"]))
                            { 
                            foreach ($resultStream[$i]["Data"] as $name=>$link) CreateLink($name, $link,  $resultStream[$i]["Stream"]["Link"], 1000+$i*10);
                            }
                        }
                    }
                }	

            $wfcHandling->write_WebfrontConfig($configWF["ConfigId"]);
            }
        }




    /*******************************************************************
     *
     * eigenes OperationCenter Webfront aufbauen, Default Icon Arztkoffer
     * es funktioniert noch nicht so dass die Funktion in AllgemeineDateien gespeichert und verwendet wird
     * legt 5 Kategorien in der Sub-Kategorie an: SystemNachrichten.SysPingErreichbarkeit,SystemInfo,RightUp,RightDn
     * benötigt werden  
     *      $configWF["Path"]           Kategorie OperationCenter 
     *      $configWF["TabItem"]        Subkategorie SystemStatus
     *      $configWF["ConfigId"]       Webfront Config ID
     *      $configWF["TabPaneItem"]    OperationCenterTPA
     *
     * Tab mit 5 Fenster links gross und 4fach im Quadrat
     *           $resultStream[0]["Stream"]["OID"]   für Fenster Links oben
     *           $resultStream[1]["Stream"]["Link"]  für Fenster Rechts oben
     *           $resultStream[2]["Stream"]["Link"]  für Fenster Links unten
     *           //$resultStream[3]["Stream"]["Link"]  für Fenster Rechts unten
     *           $resultStream[4]["Stream"]["Link"]  für grosses Fenster links
     *
     *
     **********************************/

    function installWebfrontMon($configWF,$resultStream, $emptyWebfrontRoot=false)
        {
        if ( (isset($configWF["Path"])) && (isset($configWF["TabPaneItem"])) && (isset($configWF["TabItem"])) && (isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false)) )
            {
            $wfcHandling =  new WfcHandling();                              // ohne Parameter wird die Konfiguration der Webfronts editiert, sonst werden die Standard Befehle der IPS Library verwendet
            $wfcHandling->read_WebfrontConfig($configWF["ConfigId"]);         // register Webfront Confígurator ID  


            $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.OperationCenter
            
            echo "installWebfrontMon,Path ".$configWF["Path"]." with this Webfront Tabpane Item Name : ".$configWF["TabPaneItem"]."\n";
            echo "----------------------------------------------------------------------------------------------------------------------------------\n";

            if ($emptyWebfrontRoot)         // für OperationCenter zB nicht loeschen, es gibt noch andere sub-Webfronts
                {
                echo "Kategorie $categoryId_WebFront (".IPS_GetName($categoryId_WebFront).") Inhalt loeschen und verstecken. Es dürfen keine Unterkategorien enthalten sein, sonst nicht erfolgreich.\n";
                $status=@EmptyCategory($categoryId_WebFront);
                if ($status) echo "   -> erfolgreich.\n";
                IPS_SetHidden($categoryId_WebFront, true); 		// in der normalen Viz Darstellung Kategorie verstecken
                }
            else echo "Kategorie $categoryId_WebFront (".IPS_GetName($categoryId_WebFront).") Inhalt wird nicht gelöscht.\n";
            echo "Create Sub-Category ".$configWF["TabItem"]." in ".IPS_GetName($categoryId_WebFront)." and empty it.\n";
            $categoryId_WebFrontMonitor  = CreateCategory($configWF["TabItem"],  $categoryId_WebFront, 10);        // gleich wie das Tabitem beschriften, erleichtert die Wiedererkennung
            IPS_SetHidden($categoryId_WebFrontMonitor, true);                                                      // nicht im OperationCenter anzeigen, eigener Tab
			$status=@EmptyCategory($categoryId_WebFrontMonitor);				        // ausleeren und neu aufbauen, die Geschichte ist gelöscht !
            if ($status) echo "   -> erfolgreich.\n";

            $wfcHandling->DeleteWFCItems("SystemStatus");           // delete all webfront items starting with SystemStatus

            /* Kategorien neu anlegen, aktuell Bezeichnung individuell */
            $categoryIdLeftDn    = CreateCategory('SystemNachrichten',      $categoryId_WebFrontMonitor, 0);             
            $categoryIdLeft      = CreateCategory('SysPingErreichbarkeit',  $categoryId_WebFrontMonitor, 0);             
            $categoryIdLeftUp    = CreateCategory('SystemInfo',      $categoryId_WebFrontMonitor, 0);             
            $categoryIdRightUp    = CreateCategory('RightUp',      $categoryId_WebFrontMonitor, 0);             
            $categoryIdRightDn    = CreateCategory('RightDn',      $categoryId_WebFrontMonitor, 0);             

            /*                                                    
             *    TabpaneItem (Tab Arztkoffer im Admin Root, OperationCenterTPA)
             *        Splitpane, TabItem, vertical 30%    (Tab Monitorstecker unter Arztkofer)
             *             Category TabItem_Ovw
             *             Splitpane TabItem_Show, vertical 50%
             *                  Splitpane TabItem_Left, horizontal 50%
             *                      Category Up
             *                      Category Down
             *                  Splitpane TabItem_Right, horizontal 50%
             *                      Category Up
             *                      Category Down
             *
             */
            print_r($configWF);
            echo "Create Webfront TabPane ".$configWF["TabPaneItem"]." , ".$configWF["TabPaneParent"]." , ".$configWF["TabPaneOrder"]." , ".$configWF["TabPaneName"]." , ".$configWF["TabPaneIcon"]."\n";
            /*
            DeleteWFCItems($configWF["ConfigId"], $configWF["TabPaneItem"]);		// Einzel Tab loeschen
            DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]);
            DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]."_Ovw");     //left
            DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]."_Show");
            DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]."_Left");        // middle
            DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]."Up_Left");        // right
            DeleteWFCItems($configWF["ConfigId"], $configWF["TabItem"]."Dn_Left");        // right
            */

            //CreateWFCItemTabPane   ($configWF["ConfigId"], $configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // OperationCenter Tabpane
            $wfcHandling->CreateWFCItemTabPane($configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // OperationCenter Tabpane
            echo "Create Webfront SplitPane ".$configWF["TabItem"]." , ".$configWF["TabPaneItem"]." , ". ($configWF["TabOrder"]+200)." , Monitor ,".$configWF["TabIcon"]."\n";
            //CreateWFCItemSplitPane ($configWF["ConfigId"], $configWF["TabItem"], $configWF["TabPaneItem"], ($configWF["TabOrder"]+200), "Monitor", $configWF["TabIcon"], 1 /*Vertical*/, 30 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');  // Monitor Splitpane
            $wfcHandling->CreateWFCItemSplitPane ($configWF["TabItem"], $configWF["TabPaneItem"], ($configWF["TabOrder"]+200), "Monitor", $configWF["TabIcon"], 1 /*Vertical*/, 30 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');  // Monitor Splitpane
            
            //CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"]."_Ovw", $configWF["TabItem"],  10, "","",$categoryIdLeft /*BaseId*/, 'false' /*BarBottomVisible*/ );       // muss angeben werden, sonst schreibt das Splitpane auf die falsche Seite
            //CreateWFCItemSplitPane ($configWF["ConfigId"], $configWF["TabItem"]."_Show", $configWF["TabItem"], ($configWF["TabOrder"]+200), "Show", "", 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            //CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Up_Right', $configWF["TabItem"]."_Show", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
            //CreateWFCItemSplitPane ($configWF["ConfigId"], $configWF["TabItem"]."_Left", $configWF["TabItem"]."_Show", 10, "Left", "", 0 /*Horizontal*/, 35 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            //CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Up_Left', $configWF["TabItem"]."_Left", 10, '', '', $categoryIdLeftUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
            //CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Dn_Left', $configWF["TabItem"]."_Left", 20, '', '', $categoryIdLeftDn   /*BaseId*/, 'false' /*BarBottomVisible*/);            
            $wfcHandling->CreateWFCItemCategory  ($configWF["TabItem"]."_Ovw", $configWF["TabItem"],  10, "","",$categoryIdLeft /*BaseId*/, 'false' /*BarBottomVisible*/ );       // muss angeben werden, sonst schreibt das Splitpane auf die falsche Seite

            $wfcHandling->CreateWFCItemSplitPane ($configWF["TabItem"]."_Show", $configWF["TabItem"], ($configWF["TabOrder"]+200), "Show", "", 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            $wfcHandling->CreateWFCItemCategory  ($configWF["TabItem"].'Up_Right', $configWF["TabItem"]."_Show", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);

            $wfcHandling->CreateWFCItemSplitPane ($configWF["TabItem"]."_Left", $configWF["TabItem"]."_Show", 10, "Left", "", 0 /*Horizontal*/, 65 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            $wfcHandling->CreateWFCItemCategory  ($configWF["TabItem"].'Up_Left', $configWF["TabItem"]."_Left", 10, '', '', $categoryIdLeftUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
            $wfcHandling->CreateWFCItemCategory  ($configWF["TabItem"].'Dn_Left', $configWF["TabItem"]."_Left", 20, '', '', $categoryIdLeftDn   /*BaseId*/, 'false' /*BarBottomVisible*/);            

            //CreateWFCItemSplitPane ($configWF["ConfigId"], $configWF["TabItem"]."_Right", $configWF["TabItem"]."_Show", 20, "Right", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            //CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Up_Right', $configWF["TabItem"]."_Right", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
            //CreateWFCItemCategory  ($configWF["ConfigId"], $configWF["TabItem"].'Dn_Right', $configWF["TabItem"]."_Right", 20, '', '', $categoryIdRightDn   /*BaseId*/, 'false' /*BarBottomVisible*/);  
        


            if ($resultStream !== false)        
                {
                $resultStream[0]["Stream"]["Link"]=$categoryIdLeftUp;
                $resultStream[1]["Stream"]["Link"]=$categoryIdRightUp;
                $resultStream[2]["Stream"]["Link"]=$categoryIdLeftDn;
                $resultStream[3]["Stream"]["Link"]=$categoryIdRightDn;        // wird nicht mehr verwendet, trotzdem drinnen lassen sonst ist die Struktur zerstört
                $resultStream[4]["Stream"]["Link"]=$categoryIdLeft;             // das ist das klassische sysping
                $count=sizeof($resultStream);           // spaetestens jetzt sind es immer 5 Eintraege
                echo "Jetzt diese Kategorien zuordnen: ".json_encode($resultStream)."\n";
                //print_r($resultStream);               // Name, OID, Link
                for ($i=0;$i<$count;$i++) 
                    {
                    if (isset($resultStream[$i]["Stream"]["Name"]))
                        {
                        CreateLink($resultStream[$i]["Stream"]["Name"], $resultStream[$i]["Stream"]["OID"],  $resultStream[$i]["Stream"]["Link"], 10+$i*10);        // Name, OID und Parent für Link
                        if (isset($resultStream[$i]["Data"]))
                            { 
                            foreach ($resultStream[$i]["Data"] as $name=>$link) CreateLink($name, $link,  $resultStream[$i]["Stream"]["Link"], 1000+$i*10);
                            }
                        }
                    }
                }	
            
            $wfcHandling->write_WebfrontConfig($configWF["ConfigId"]);
            } // Config vollstaendig			
        }    

    /* zusätzliches Webfront TabItem für Radio im Status DoctorBag
     */
    function installWebfrontRadio($configWF,$resultStream, $emptyWebfrontRoot=false)
        {
        if ( (isset($configWF["Path"])) && (isset($configWF["TabPaneItem"])) && (isset($configWF["TabItem"])) && (isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false)) )
            {
            echo "Install Webfront Radio :\n";
            $wfcHandling =  new WfcHandling();                              // ohne Parameter wird die Konfiguration der Webfronts editiert, sonst werden die Standard Befehle der IPS Library verwendet
            $wfcHandling->read_WebfrontConfig($configWF["ConfigId"]);         // register Webfront Confígurator ID  
            $categoryId_WebFront         = CreateCategoryPath($configWF["Path"]);        // Path=Visualization.WebFront.User/Administrator/Mobile.WebCamera
            //echo "Webfront Category Path :".$configWF["Path"]."\n";
            echo "installWebfrontMon,Path ".$configWF["Path"]." with this Webfront Tabpane Item Name : ".$configWF["TabPaneItem"]."\n";
            echo "----------------------------------------------------------------------------------------------------------------------------------\n";

            if ($emptyWebfrontRoot)         // für OperationCenter zB nicht loeschen, es gibt noch andere sub-Webfronts
                {
                echo "Kategorie $categoryId_WebFront (".IPS_GetName($categoryId_WebFront).") Inhalt loeschen und verstecken. Es dürfen keine Unterkategorien enthalten sein, sonst nicht erfolgreich.\n";
                $status=@EmptyCategory($categoryId_WebFront);
                if ($status) echo "   -> erfolgreich.\n";
                IPS_SetHidden($categoryId_WebFront, true); 		// in der normalen Viz Darstellung Kategorie verstecken
                }
            else echo "Kategorie $categoryId_WebFront (".IPS_GetName($categoryId_WebFront).") Inhalt wird nicht gelöscht.\n";
            echo "Create Sub-Category ".$configWF["TabItem"]." in ".IPS_GetName($categoryId_WebFront)." and empty it.\n";

            $categoryId_WebFrontMonitor  = CreateCategory($configWF["TabItem"],  $categoryId_WebFront, 10);        // gleich wie das Tabitem beschriften, erleichtert die Wiedererkennung
            IPS_SetHidden($categoryId_WebFrontMonitor, true);                                                      // nicht im OperationCenter anzeigen, eigener Tab
			$status=@EmptyCategory($categoryId_WebFrontMonitor);				        // ausleeren und neu aufbauen, die Geschichte ist gelöscht !
            if ($status) echo "   -> erfolgreich.\n";

            /* Kategorien neu anlegen, aktuell Bezeichnung individuell */
            $categoryIdRight     = CreateCategory('Right',      $categoryId_WebFrontMonitor, 0);             
            $categoryIdLeft      = CreateCategory('Left',  $categoryId_WebFrontMonitor, 0);             

            //CreateWFCItemTabPane   ($configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // OperationCenter Tabpane
            echo "Create Webfront TabPane ".$configWF["TabPaneItem"]." , ".$configWF["TabPaneParent"]." , ".$configWF["TabPaneOrder"]." , ".$configWF["TabPaneName"]." , ".$configWF["TabPaneIcon"]." already done.\n";
            //$wfcHandling->CreateWFCItemTabPane($configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);        // OperationCenter Tabpane
            echo "Create Webfront SplitPane ".$configWF["TabItem"]." , ".$configWF["TabPaneItem"]." , ". ($configWF["TabOrder"]+500)." , RadioStatus ,  Intensity \n";
            $wfcHandling->CreateWFCItemSplitPane ($configWF["TabItem"], $configWF["TabPaneItem"], ($configWF["TabOrder"]+500), "RadioStatus", "Intensity", 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');  // Monitor Splitpane
            // vorangegangenes SplitPane sollte abgeschlossen werden, sonst finden die beiden Category Befehle das Tab Item nicht  
            $wfcHandling->CreateWFCItemCategory  ($configWF["TabItem"]."_Left", $configWF["TabItem"],  10, "","",$categoryIdLeft /*BaseId*/, 'false' /*BarBottomVisible*/ );       // muss angeben werden, sonst schreibt das Splitpane auf die falsche Seite
            $wfcHandling->CreateWFCItemCategory  ($configWF["TabItem"]."_Right", $configWF["TabItem"],  10, "","",$categoryIdRight /*BaseId*/, 'false' /*BarBottomVisible*/ );       // muss angeben werden, sonst schreibt das Splitpane auf die falsche Seite

            if ($resultStream !== false)        
                {
                $resultStream[0]["Stream"]["Link"]=$categoryIdRight;

                $count=sizeof($resultStream);           // spaetestens jetzt sind es immer 5 Eintraege
                echo "Jetzt diese Kategorien zuordnen: ".json_encode($resultStream)."\n";
                //print_r($resultStream);               // Name, OID, Link
                for ($i=0;$i<$count;$i++) 
                    {
                    if (isset($resultStream[$i]["Stream"]["Name"]))
                        {
                        CreateLink($resultStream[$i]["Stream"]["Name"], $resultStream[$i]["Stream"]["OID"],  $resultStream[$i]["Stream"]["Link"], 10+$i*10);        // Name, OID und Parent für Link
                        if (isset($resultStream[$i]["Data"]))
                            { 
                            foreach ($resultStream[$i]["Data"] as $name=>$link) CreateLink($name, $link,  $resultStream[$i]["Stream"]["Link"], 1000+$i*10);
                            }
                        }
                    }
                }

            $wfcHandling->write_WebfrontConfig($configWF["ConfigId"]);
            }
        }

    /* zusätzliches Webfront TabItem für IPSCam
     */
    function installWebfrontCam($configWF,$OperationCenterConfig,$CategoryIdData)
        {
        if ( (isset($configWF["Path"])) && (isset($configWF["TabPaneItem"])) && (isset($configWF["TabItem"])) && ( (isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false))) )
            {            
			/************************
			 *
			 * Anlegen des Capture Overviews von allen Kameras
			 * einzelne Tabs pro Kamera mit den interessantesten Bildern der letzten Stunden oder Tage
			 * die Daten werden aus den FTP Verzeichnissen gesammelt.
             *    Path      Path_Capture
			 *
			 ************************/
							
			echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: \"".$configWF["Path"]."_Capture\"\n";			
			$categoryId_WebFrontAdministrator         = CreateCategoryPath($configWF["Path"]."_Capture");
			EmptyCategory($categoryId_WebFrontAdministrator);
			IPS_SetHidden($categoryId_WebFrontAdministrator, true); 		// in der normalen Viz Darstellung Kategorie verstecken
            //DeleteWFCItems($configWF["ConfigId"], "CamCapture");    

			//CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
			CreateWFCItemTabPane  ($configWF["ConfigId"], "CamCapture", $configWF["TabPaneItem"], ($configWF["TabOrder"]+1000), 'CamCapture', $configWF["TabIcon"]);
			if (isset ($OperationCenterConfig['CAM']))
				{
				$i=0;
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
					$i++; $found=false;
                    if (isset ($cam_config['FTPFOLDER']))         
                        {
                        if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                            {
                            echo "  Webfront Tabname für ".$cam_name." \n";
                            $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                            if ($cam_categoryId==false)
                                {
                                $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                                IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                                IPS_SetParent($cam_categoryId,$CategoryIdData);
                                }
                            $categoryIdCapture  = CreateCategory("Cam_".$cam_name,  $categoryId_WebFrontAdministrator, 10*$i);
                            CreateWFCItemCategory  ($configWF["ConfigId"], "Cam_".$cam_name,  "CamCapture",    (10*$i),  "Cam_".$cam_name,     $configWF["TabIcon"], $categoryIdCapture /*BaseId*/, 'false' /*BarBottomVisible*/);
                            echo "     CreateWFCItemCategory  (".$configWF["ConfigId"].", Cam_$cam_name,  CamCapture,    ".(10*$i).",  Cam_$cam_name,  ".$configWF["TabIcon"].", $categoryIdCapture, false);\n";
                            $pictureFieldID = CreateVariableByName($categoryIdCapture, "pictureField",   3 /*String*/, '~HTMLBox', "", 50);
                            $box='<iframe frameborder="0" width="100%">     </iframe>';
                            SetValue($pictureFieldID,$box);
                            $found=true;
                            }
                        }
                    if (!$found)
                        {
                        echo "  Webfront Tabname für ".$cam_name." wird nicht mehr benötigt, loeschen.\n";
                        $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                        if ($cam_categoryId !== false)
                            {
                            DeleteWFCItems($configWF["ConfigId"], "Cam_".$cam_name);    
                            }
                        }
					}
				}


			/************************
			 *
			 * Anlegen des Picture Overviews von allen Kameras
			 * ein Tab für alle Kameras, es wird nicht der Livestream 
			 * sondern Bilder die regelmäßig per Button aktualisiert werden müssen in einer gemeinsamen html Tabelle angezeigt
			 *
			 ************************/

            echo "\n"; 
            echo "=====================================================================================\n"; 
            echo "Modul WebCamera/IPSCam installiert. Im Verzeichnis Data die Variablen für Übersichtsdarstellungen Pics und Movies anlegen:\n"; 
            $CategoryIdDataOverview=CreateCategory("Cams",$CategoryIdData,2000,"");
            echo $CategoryIdDataOverview."  ".IPS_GetName($CategoryIdDataOverview)."/".IPS_GetName(IPS_GetParent($CategoryIdDataOverview))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($CategoryIdDataOverview)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($CategoryIdDataOverview))))."\n";
            $CamTablePictureID  = CreateVariableByName($CategoryIdDataOverview,"CamTablePicture", 3, "~HTMLBox");
            $CamMobilePictureID = CreateVariableByName($CategoryIdDataOverview,"CamMobilePicture",3, "~HTMLBox");
            $CamTableMovieID    = CreateVariableByName($CategoryIdDataOverview,"CamTableMovie",   3, "~HTMLBox");

			echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: \"".$configWF["Path"]."Pictures\"\n";
			$categoryId_WebFrontPictures         = CreateCategoryPath($configWF["Path"]."Pictures");
			EmptyCategory($categoryId_WebFrontPictures);				// ausleeren und neu aufbauen, die Geschichte ist gelöscht !
			IPS_SetHidden($categoryId_WebFrontPictures, true); 		// in der normalen Viz Darstellung Kategorie verstecken
				
			/* TabPaneItem anlegen und wenn vorhanden vorher loeschen */
			$tabItem = $configWF["TabPaneItem"].$configWF["TabItem"]."Pics";
			if ( exists_WFCItem($configWF["ConfigId"], $configWF["TabPaneItem"]."Pics") )
		 		{
				echo "Webfront ".$configWF["ConfigId"]." (".IPS_GetName($configWF["ConfigId"]).")  löscht TabItem : ".$configWF["TabPaneItem"].".Pics\n";
				DeleteWFCItems($configWF["ConfigId"], $configWF["TabPaneItem"]."Pics");
				}
			else
				{
				echo "Webfront ".$configWF["ConfigId"]." (".IPS_GetName($configWF["ConfigId"]).")  TabItem : ".$configWF["TabPaneItem"].".Pics nicht mehr vorhanden.\n";
				}	
			echo "Webfront ".$configWF["ConfigId"]." erzeugt TabItem :".$configWF["TabPaneItem"]." in ".$configWF["TabPaneParent"]."\n";
			//CreateWFCItemTabPane   ($WFC10_ConfigId,"CamPictures" ,$WFC10Cam_TabPaneItem, $WFC10Cam_TabPaneOrder, $WFC10Cam_TabPaneName, $WFC10Cam_TabPaneIcon);
			CreateWFCItemCategory  ($configWF["ConfigId"], "CamPictures" ,$configWF["TabPaneItem"],   10, 'CamPictures', $configWF["TabPaneIcon"], $categoryId_WebFrontPictures   /*BaseId*/, 'false' /*BarBottomVisible*/);
			/* im TabPane entweder eine Kategorie oder ein SplitPane und Kategorien anlegen */
			//CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem,   "CamPictures",   10, '', '', $categoryId_WebFrontPictures   /*BaseId*/, 'false' /*BarBottomVisible*/);

			// definition CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="") {
			CreateLinkByDestination("Pictures", $CamTablePictureID, $categoryId_WebFrontPictures,  10,"");	

            }
        else echo "configWF not fully declared as input :".(isset($configWF["Path"]))."+".(isset($configWF["TabPaneItem"]))."+".(isset($configWF["TabItem"]))."+".((isset($configWF["Enabled"])) && (!($configWF["Enabled"]==false)))."\n";
        }


    /* Create HomaticModule Instance with Name Address, Channel, Protocol
     * Teil des Aufrufs bei dem Homematic RSSI Variablen für stromversorgte Homematic Devices angelegt werden
     * Routine prüft alle Homematic Instanzen ob die gefoderte Adresse bereits vergeben ist
     */

	function CreateHomematicInstance($moduleManager, $Address, $Channel, $Name, $ParentId, $Protocol='BidCos-RF') 
        {
		foreach (IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}") as $HomematicModuleId ) 
            {
			//$HMAddress = HM_GetAddress($HomematicModuleId);
            $HMAddress = IPS_GetProperty($HomematicModuleId,'Address');            
			if ($HMAddress=="$Address:$Channel") 
                {
				$moduleManager->LogHandler()->Log("Found existing HomaticModule '$Name' Address=$Address, Channel=$Channel, Protocol=$Protocol");
				return $HomematicModuleId;
			    }
		    }
        echo "CreateHomematicInstance: Create HomaticModule Instance with '$Name' Address=$Address, Channel=$Channel, Protocol=$Protocol";
		$moduleManager->LogHandler()->Log("Create HomaticModule '$Name' Address=$Address, Channel=$Channel, Protocol=$Protocol");
		$HomematicModuleId = IPS_CreateInstance("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
		IPS_SetParent($HomematicModuleId, $ParentId);
        echo ",OID=$HomematicModuleId ,Parent OID=$ParentId";
		IPS_SetName($HomematicModuleId, $Name);
		HM_SetAddress($HomematicModuleId, $Address.':'.$Channel);
		if ($Protocol == 'BidCos-RF') 
			{
			$Protocol = 0; echo "  -> Funk";
			}
		elseif ($Protocol == 'BidCos-WI')           // es gibt auch IP basierte Datenübertragung
			{
			$Protocol = 1; echo "  -> Draht";
			}
        elseif ($Protocol == 'IP')           // es gibt auch IP basierte Datenübertragung
			{
            $Protocol = 2; echo "  -> IP";    
            }
        else 
            {
            $Protocol = 3; echo "  -> unknown";
            }
        echo "\n";
		HM_SetProtocol($HomematicModuleId, $Protocol);
		HM_SetEmulateStatus($HomematicModuleId, true);
		// Apply Changes
		IPS_ApplyChanges($HomematicModuleId);

		return $HomematicModuleId;
	}	

	/*
	 * Bei jedem Bild als html Verzeichnis und alternativem Bildtitel darstellen
	 *
	 */

	function imgsrcstring($imgVerzeichnis,$filename,$title)
		{
		return ($imgVerzeichnis."\\".$filename.'" title="'.$title.'" alt="'.$filename);
		}
	
	
	
?>