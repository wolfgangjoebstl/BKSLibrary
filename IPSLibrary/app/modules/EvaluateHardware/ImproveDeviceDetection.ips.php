<?

/* verbessert und überprüft die aktuelle Systemkonfiguration
 *
 */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

    IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
    IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
    IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');           // sonst werden die Event Listen überschrieben

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    $startexec=microtime(true);

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
        }
    $installedModules = $moduleManager->GetInstalledModules();
    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	$modulhandling = new ModuleHandling();	

    echo "\nAlle installierten Discovery Instances mit zugehörigem Modul und Library:\n";
    $discovery = $modulhandling->getDiscovery();
    echo "\n";

    if ( (isset($installedModules["DetectMovement"]) )             )
        {    
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

        $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
        $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();        

        echo "Bewegungsregister Auflistung der Events:\n";
        $DetectMovementHandler = new DetectMovementHandler();
    	$events=$DetectMovementHandler->ListEvents();
        $eventMoveConfig=$DetectMovementHandler->Get_EventConfigurationAuto();    

        //$detectMoveConfig=$DetectMovementHandler->ListConfigurations();  print_r($detectMoveConfig);
        
        $mirrorsMoveFound=array();
        echo "      OID    Pfad                                                                     Config aus EvaluateHardware                                             MoveConfig aus DetectMovement            \n";
        foreach ($events as $oid => $typ)
            {
            $moid=$DetectMovementHandler->getMirrorRegister($oid);
            if (IPS_GetObject($oid) === false) echo "Register nicht bekannt.\n";
            elseif (isset($eventDeviceConfig[$oid])===false) echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75)." --> DeviceConfig nicht bekannt.\n";
            elseif (isset($eventMoveConfig[$oid])===false) echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75).str_pad(json_encode($eventDeviceConfig[$oid]),70)." --> MoveConfig nicht bekannt.\n";
            elseif ($moid === false) echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75).str_pad(json_encode($eventDeviceConfig[$oid]),70)."  ".str_pad(json_encode($eventMoveConfig[$oid]),60)."  --> Spiegelregister nicht bekannt.\n";
            else
                {
                $mirrorsMoveFound[$moid] = IPS_GetName($moid);                
                echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75).str_pad(json_encode($eventDeviceConfig[$oid]),70)."  ".str_pad(json_encode($eventMoveConfig[$oid]),60)."\n";
                }
            }
        print_R($mirrorsMoveFound);
        echo "Temperaturregister Auflistung der Events:\n";
        $DetectTemperatureHandler = new DetectTemperatureHandler();
    	$events=$DetectTemperatureHandler->ListEvents();
        $eventTempConfig=$DetectTemperatureHandler->Get_EventConfigurationAuto();    

        $mirrorsTempFound=array();
        echo "      OID    Pfad                                                                              Config aus EvaluateHardware                                             TemperatureConfig aus DetectMovement            \n";
        foreach ($events as $oid => $typ)
            {
            $moid=$DetectTemperatureHandler->getMirrorRegister($oid);
            $mirrorsTempFound[$moid] = IPS_GetName($moid);                
            echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75).str_pad(json_encode($eventDeviceConfig[$oid]),70)."  ".str_pad(json_encode($eventTempConfig[$oid]),60)."\n";
            }

        echo "\n";
        }
    else 
        {
        $mirrorsTempFound=array();   
        }

    if ( (isset($installedModules["CustomComponent"]) )                   ) 
        {    

        $moduleManagerCC = new IPSModuleManager('CustomComponent',$repository);

        IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
        IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
        IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

        $CategoryIdDataCC     = $moduleManagerCC->GetModuleCategoryID('data');
        $CategoryIdAppCC      = $moduleManagerCC->GetModuleCategoryID('app');

        echo "\n";
        echo "Modul CustomComponents Category OIDs for data : ".$CategoryIdDataCC."  (".IPS_GetName($CategoryIdDataCC).") for App : ".$CategoryIdAppCC."\n";
        echo "Ausgabe aller Bewegung Spiegelregister:\n";
        $name="Bewegung-Auswertung";
        $MoveAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdDataCC);
        checkMirrorRegisters($MoveAuswertungID,$mirrorsMoveFound);


        echo "Ausgabe aller Temperatur Spiegelregister:\n";

        /* Get Category to store the Temperature-Spiegelregister */	
        $name="Temperatur-Auswertung";
        $TempAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdDataCC);
        checkMirrorRegisters($TempAuswertungID,$mirrorsTempFound);

        }

    if ( (isset($installedModules["DetectMovement"]) )             )
        {    

        echo "\n";
        echo "=======================================================================\n";
        echo "Summenregister suchen und evaluieren :\n";
        echo "\n";
        echo "Bewegungsregister hereinholen:\n";								

        $events=$DetectMovementHandler->ListEvents();
        echo "Die Configurationen der Bewegungsregisterregister auf Konsistenz prüfen:\n";
        $events=$DetectTemperatureHandler->ListEvents();
        foreach ($events as $oid => $typ)
            {
            $moid=$DetectMovementHandler->getMirrorRegister($oid);
            if ( (isset($detectDeviceConfig[$oid]["Config"]["Mirror"])) && ($detectDeviceConfig[$oid]["Config"]["Mirror"] != "") ) 
                {
                if ($detectDeviceConfig[$oid]["Config"]["Mirror"] != IPS_GetName($moid)) 
                    {
                    $mirror1=$detectDeviceConfig[$oid]["Config"]["Mirror"];
                    echo "     ---> Mirror register in detectDeviceConfig cannot be overwritten. Clear manually to $mirror1!\n";
                    //print_r($detectDeviceConfig[$oid]);
                    }
                }
            //echo "\ndetectTemperatureConfig:\n"; print_r($detectTemperatureConfig[$oid]);
            if ( (isset($detectTemperatureConfig[$oid]["Config"]["Mirror"])) && ($detectTemperatureConfig[$oid]["Config"]["Mirror"] != "") ) 
                {
                if ($detectTemperatureConfig[$oid]["Config"]["Mirror"] != IPS_GetName($moid)) 
                    {
                    $mirror2=$detectTemperatureConfig[$oid]["Config"]["Mirror"];
                    echo "     ---> Mirror register in detectMovementConfig cannot be overwritten. Clear manually to $mirror2 !\n";
                    //print_R($detectTemperatureConfig[$oid]);
                    }
                }            
            }        
        //print_r($events);
        /*
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$DetectMovementHandler->getMirrorRegister($oid);
            $DetectDeviceHandler->RegisterEvent($moid,'Topology','','Movement');		
            }
        */
        $groups=$DetectMovementHandler->ListGroups("Motion");       // Type angeben damit mehrere Gruppen aufgelöst werden können
        //print_r($groups)
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectMovementHandler->InitGroup($group);
            echo "     ".$soid."  ".str_pad(IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid))),70)."  ".(integer)GetValue($soid)."\n";
            //$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Movement');		
            }	
        echo "Die Configurationen der Temperaturregister auf Konsistenz prüfen:\n";
        $events=$DetectTemperatureHandler->ListEvents();
        foreach ($events as $oid => $typ)
            {
            $moid=$DetectTemperatureHandler->getMirrorRegister($oid);
            if ( (isset($detectDeviceConfig[$oid]["Config"]["Mirror"])) && ($detectDeviceConfig[$oid]["Config"]["Mirror"] != "") ) 
                {
                if ($detectDeviceConfig[$oid]["Config"]["Mirror"] != IPS_GetName($moid)) 
                    {
                    $mirror1=$detectDeviceConfig[$oid]["Config"]["Mirror"];
                    echo "     ---> Mirror register in detectDeviceConfig cannot be overwritten. Clear manually to $mirror1!\n";
                    //print_r($detectDeviceConfig[$oid]);
                    }
                }
            //echo "\ndetectTemperatureConfig:\n"; print_r($detectTemperatureConfig[$oid]);
            if ( (isset($detectTemperatureConfig[$oid]["Config"]["Mirror"])) && ($detectTemperatureConfig[$oid]["Config"]["Mirror"] != "") ) 
                {
                if ($detectTemperatureConfig[$oid]["Config"]["Mirror"] != IPS_GetName($moid)) 
                    {
                    $mirror2=$detectTemperatureConfig[$oid]["Config"]["Mirror"];
                    echo "     ---> Mirror register in detectTemperatureConfig cannot be overwritten. Clear manually to $mirror2 !\n";
                    //print_R($detectTemperatureConfig[$oid]);
                    }
                }            
            }        
        echo "Alle Temperatur Gruppen durchgehen und wenn erforderlich neu registrieren :\n";
        $groups=$DetectTemperatureHandler->ListGroups("Temperatur");        /* Type angeben damit mehrere Gruppen aufgelöst werden können */
        //print_r($groups);
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectTemperatureHandler->InitGroup($group);
            echo "     ".$soid."  ".str_pad(IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid))),70)."  ".GetValue($soid)." °C\n";
            //$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Temperature');		
            }
        }	



        echo "Aktuelle Laufzeit ".(time()-$startexec)." Sekunden.\n";





        function checkMirrorRegisters($TempAuswertungID,$mirrorsFound)
            {
            $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $i=0;
            $childrens=IPS_getChildrenIDs($TempAuswertungID);
            $mirrors = array();
            foreach ($childrens as $oid)
                {    
                $mirrors[IPS_GetName($oid)]=$oid;
                }
            ksort($mirrors);
            //print_r($mirrors);
            foreach ($mirrors as $oid)
                {
                $werte = @AC_GetLoggedValues($archiveHandlerID,$oid, time()-60*24*60*60, time(),1000);
                if ($werte === false) echo "   ".str_pad($i,4).str_pad($oid,6).str_pad("(".IPS_GetName($oid).")",35)."  : no archive\n";
                else 
                    {
                    echo "   ".str_pad($i,4).str_pad($oid,6).str_pad("(".IPS_GetName($oid).")",35)."  : ".str_pad(count($werte),4)."  ";
                    if (count($werte)>0) 
                        {
                        //print_r($werte[0]);
                        echo " last change ".date("d.m.Y H:i:s",$werte[0]["TimeStamp"]);
                        }
                    else echo "                                ";
                    if (isset($mirrorsFound[$oid])) echo "   -> Mirror in Config";
                    echo "\n";
                    }
                $i++;
                }
            }











	
?>