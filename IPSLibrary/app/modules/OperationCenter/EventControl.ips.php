<?php

/***********************************************************************
 *
 * Event Control
 *
 * Das Phänomen, dass der HomeMatic-Socket in IP-Symcon nach einem fehlgeschlagenen „Pong“ (KeepAlive-Timeout) einfriert und sich oft nur durch einen kompletten Symcon-Neustart wiederbeleben lässt, 
 * ist ein bekanntes (und extrem nerviges) Problem.
 *
 * Hier ist die Erklärung, warum das passiert, und wie du es lösen kannst, ohne jedes Mal Symcon neu zu starten.
 *
 * Die Ursache: Warum blockiert der Socket?
 * Das Problem liegt meist an einer asynchronen Blockade der Ports oder Event-Server (RPC-Schnittstellen) zwischen IP-Symcon und der CCU (bzw. RaspberryMatic).
 *
 * Der "Zombie"-Port (Address already in use): Wenn die CCU kurzzeitig nicht erreichbar ist (z. B. durch hohe Last, Duty Cycle, Firmware-Schnittstellen-Crash oder ein Netzwerk-Schluckauf), 
 * verliert Symcon den Ping/Pong-Takt. Beim Versuch, den Socket im Hintergrund automatisch neu zu öffnen, blockiert sich Symcon oft selbst, weil das Betriebssystem den 
 * alten lokalen Port (über den die CCU die Events an Symcon meldet) noch als „belegt“ deklariert.
 *
 * Absturz der CCU-Schnittstellenprozesse: Manchmal läuft die CCU zwar noch, aber die einzelnen Dämonen (rfd für Funk, HMIPServer für HmIP) haben sich aufgehängt. Symcon wartet dann vergeblich auf Antwort.
 *
 * Firewall- / Routing-Probleme: Wenn Pakete verzögert ankommen, läuft Symcon in den Timeout.
 *
 * Lösungen ohne Symcon-Neustart
 * Ein kompletter Symcon-Neustart ist die "Vorschlaghammer-Methode". Es gibt elegantere Wege, den Socket gezielt zu resetten.
 *
 *
 *
 ***********************************************************/

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    
    IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
    IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ("DeviceManagement_Library.class.php","IPSLibrary::app::modules::OperationCenter");  

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $dosOps = new dosOps();
    $sysOps = new sysOps();

    $OperationConfig=new OperationCenterConfig();

    $configSetup=$OperationConfig->setSetup();
    $categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
    $input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
    $log_Watchdog=new Logging($configSetup["LogDirectory"]."Log_Watchdog.csv",$input);    

    if ($_IPS['SENDER'] == "Execute")
        {
        echo "\nEventControl,StartSymcon: Eigenen Logspeicher für Watchdog und OperationCenter vorbereiten.\n";
        echo "   Define Logging Channel        : ".$configSetup["LogDirectory"]."Log_Watchdog.csv \n";
        echo "   Define Logging Input Register : ".$input."\n";
        //print_R($configSetup);    
        }
    else
        {
        /* Logging konfigurieren und festlegen */

        $instance = $_IPS['INSTANCE'];	        //InstanceID for state change
        $instanceName = IPS_GetName($instance);
        $status = $_IPS['STATUS'];              //	State of the instance. A list of possible values is found here: IPS_GetInstance
        $statustext = $_IPS['STATUSTEXT'];
        $log_Watchdog->LogMessage("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");
        $log_Watchdog->LogNachrichten("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");

        if ($status>=200)
            {
            IPS_SetProperty($instance, "Open", false);
            IPS_ApplyChanges($instance);
            
            IPS_Sleep(3000); // 3 Sekunden warten
            
            // Socket wieder aktivieren
            IPS_SetProperty($instance, "Open", true);
            IPS_ApplyChanges($instance);
            $log_Watchdog->LogMessage("$instanceName ($instance) has been reseted , info from EventControl");
            }
        }
	


?>