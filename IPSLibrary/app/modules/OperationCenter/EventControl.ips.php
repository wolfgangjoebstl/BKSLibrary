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
 *      ok diesen Workaround nur mehr umsetzen wenn er für eine Instanz aktiviert wurde
 *      ok Logging weiterhin für alle Instanzen, die hier eingetragen sind
 *      ok Open Semaphore blocking, to avoid event stacking 
 *
 *  Reboot checking for ccus is done in ccu_syncstatus
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

    $categoryId_EventControl    = @IPS_GetObjectIDByName('EventControl',        $CategoryIdData);
    $categoryId_Nachrichten     = @IPS_GetObjectIDByName('Nachrichtenverlauf',  $CategoryIdData);   
    $categoryId_RebootCtr  	    = @IPS_GetObjectIDByName('RebootCounter', 	    $CategoryIdData);
    $input = @IPS_GetObjectIDByName("Nachricht_Input",$categoryId_Nachrichten);                             // Logging

    $configSetup=$OperationConfig->setSetup();
    $configuration=$OperationConfig->setConfiguration()["CCU"];

    if ($categoryId_Nachrichten && $input)
        {
        $log_Watchdog=new Logging($configSetup["LogDirectory"]."Log_Watchdog.csv",$input);    
        $ccuConfig=$OperationConfig->getCCUConfig($configuration);

        if ($_IPS['SENDER'] == "Execute")
            {
            $subnet="10.255.255.255";
            $OperationCenter=new OperationCenter($subnet);                
            echo "\n";
            echo "EventControl,StartSymcon: Eigenen Logspeicher für Watchdog und OperationCenter vorbereiten.\n";
            echo "   Define Logging Channel        : ".$configSetup["LogDirectory"]."Log_Watchdog.csv \n";
            echo "   Define Logging Input Register : ".$input."\n";
            //echo "Configuration CCU, index OID:\n"; print_R($ccuConfig); 
            echo "Define CCU Reboot Counter Category       : ".$categoryId_RebootCtr."  \n";
            $childs=IPS_GetChildrenIDs($categoryId_RebootCtr);
            echo "Define CCU CloseOpen Workaround Category : ".$categoryId_EventControl."  \n";
            foreach ($ccuConfig as $instance=>$entry)
                {
                $instanceName=IPS_GetName($entry["OID"]);
                if (isset($ccuConfig[$instance]["AUTOCLOSEOPEN"])) 
                    {
                    // works per instancename
                    $CloseOpenID = @IPS_GetObjectIDByName($instanceName."_SocketStatus", $categoryId_EventControl);   
                    $resetCounterID       = @IPS_GetObjectIDByName($instanceName."_ResetCounter", $categoryId_EventControl);  
                    $timeOfLastResetID    = @IPS_GetObjectIDByName($instanceName."_TimeOfLastReset", $categoryId_EventControl);
                    $ResetActiveID        = @IPS_GetObjectIDByName( $instanceName."_ResetActive", $categoryId_EventControl);                    
                    echo str_pad($instanceName,35);
                    if ($CloseOpenID) echo " SocketStatus $CloseOpenID : ".GetValue($CloseOpenID);
                    else echo " unset";
                    if ($ccuConfig[$instance]["AUTOCLOSEOPEN"]) echo ", ACO : active";
                    else echo ", ACO : inactive";
                    if ($resetCounterID) echo ", ResetCounter $resetCounterID : ".str_pad(GetValue($resetCounterID),4);
                    if ($timeOfLastResetID) echo ", Last event $timeOfLastResetID : ".str_pad(nf(time()-GetValue($timeOfLastResetID),"s"),10)," ".date("H:i:s",GetValue($timeOfLastResetID));
                    if ($ResetActiveID) echo ", ResetActive ".(GetValue($ResetActiveID)?"Yes":"No ");

                    //IPS_SemaphoreLeave("EventControl".$instance);                   // Leave of Semaphores
                    }               
                foreach ($childs as $oid)                               // other way round, see whats there and whether you can use it
                    {
                    $name=IPS_GetName($oid); 
                    $pos1=strpos($name,$instanceName."_");
                    if ($pos1 !== false) 
                        {
                        echo ", RebootCounter $oid : ".GetValue($oid);
                        }
                    //else echo "      found $oid $name ".GetValue($oid)."\n";
                    } 
                echo "\n";

                $daysback=1; $debug=false;
                $CloseOpenID=$OperationCenter->setDebugArchiveVar($CloseOpenID,$daysback,$debug);        //true for Debug
                $resetCounterID=$OperationCenter->setDebugArchiveVar($resetCounterID,$daysback,$debug);        //true for Debug
                }
            }
        else
            {
            $instance = $_IPS['INSTANCE'];	        //InstanceID for state change
            $instanceName = IPS_GetName($instance);
            $status = $_IPS['STATUS'];              //	State of the instance. A list of possible values is found here: IPS_GetInstance
            $statustext = $_IPS['STATUSTEXT'];
            // Logging konfigurieren und festlegen 
            $ResetActiveID        = @IPS_GetObjectIDByName( $instanceName."_ResetActive", $categoryId_EventControl);             
            if (isset($ccuConfig[$instance]["AUTOCLOSEOPEN"]) && $ccuConfig[$instance]["AUTOCLOSEOPEN"])       // nur weiter wenn es eine Konfiguration gibt, und die Funktion aktiviert wurde
                {
                // works per instancename
                $CloseOpenID = @IPS_GetObjectIDByName($instanceName."_SocketStatus", $categoryId_EventControl); 
                if ($CloseOpenID) SetValue($CloseOpenID,$status);

                $timeOfLastResetID    = @IPS_GetObjectIDByName($instanceName."_TimeOfLastReset", $categoryId_EventControl);                    // Category, Name, 0 Boolean 1 Integer 2 Float 3 String 
                $resetCounterID       = @IPS_GetObjectIDByName($instanceName."_ResetCounter", $categoryId_EventControl); 


                /* if there is a CCU close open action right after an error we might recover the CCU
                 * if the recovery process fails it stays failed. so after 100 retries in 1 hour we stopp the activity
                 */
                if ($timeOfLastResetID && ((time()-GetValue($timeOfLastResetID))>60*60) )                   // nach einer Stunde ohne Reset den Resetcounter zurücksetzen
                    {
                    if ($resetCounterID) SetValue($resetCounterID,0);    
                    } 
                if ($status>=200) 
                    {
                    if ($resetCounterID && (GetValue($resetCounterID)<500))
                        {
                        /* Semaphore Check is essential, script is called also when Reset process is executed due to changed status 
                         * every change of status is recorded
                         * we see several resets until one is successful, this is repeated every timeout section
                         */
                        if (IPS_SemaphoreEnter("EventControl".$instance, 1000))          // Verwende bei EvenTcontrol damit sich nicht viele Events überholen könen, vielleicht auch bei SyncState, damit ein SyncState nicht gleich den nächsten triggert
                            {
                            SetValue($ResetActiveID,true);
                            IPS_Sleep(3000); // 3 Sekunden warten

                            IPS_SetProperty($instance, "Open", false);
                            IPS_ApplyChanges($instance);
                            
                            IPS_Sleep(5000); // 5 Sekunden warten
                            
                            // Socket wieder aktivieren
                            IPS_SetProperty($instance, "Open", true);
                            IPS_ApplyChanges($instance);
                            $log_Watchdog->LogMessage("$instanceName ($instance) has been reseted , info from EventControl");           // geht in die Datei
                            $log_Watchdog->LogNachrichten("$instanceName ($instance) has been reseted , info from EventControl");
                            // ...Run critical Commands
                            //Release semaphore again!
                            SetValue($resetCounterID,GetValue($resetCounterID)+1);                                // increase counter, stays at 100
                            SetValue($ResetActiveID,false);
                            IPS_SemaphoreLeave("EventControl".$instance);
                            }
                        else
                            {
                            // ...No execution possible. Another script uses the "CriticalPoint" 
                            // for more than 1 second, so our wait time is exceeded.
                            $log_Watchdog->LogMessage("$instanceName ($instance) is in reset process, no action, info from EventControl");
                            $log_Watchdog->LogNachrichten("$instanceName ($instance) is in reset process, no action, info from EventControl");
                            } 
                        }
                    else            // either reset message or error message
                        {
                        $log_Watchdog->LogMessage("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");
                        $log_Watchdog->LogNachrichten("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");                         
                        }
                    if ($timeOfLastResetID) SetValue($timeOfLastResetID,time());                                // actual time of reset is increased even if the counter stopps at 100, after 5 hours a powerOn reset is done                      
                    }
                elseif (GetValue($ResetActiveID)===false)
                    {
                    $log_Watchdog->LogMessage("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");
                    $log_Watchdog->LogNachrichten("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");
                    }                     
                }
            else                // standard logging if AutoCloseOpen is deactivated 
                {
                $log_Watchdog->LogMessage("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");
                $log_Watchdog->LogNachrichten("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");
                }
            }
        }
    else echo "No Logging initialised, run OperationCenter_Installation script.\n";


?>