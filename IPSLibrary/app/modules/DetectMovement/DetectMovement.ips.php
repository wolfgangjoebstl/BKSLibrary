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

    /****************
     * Routine um Überblick zu verschaffen, arbeitet nur wenn von der Console aufgerufen
     * Ausgabe der line prints wann welche bewegung erfolgt ist
     *
     */
        
    $startexec=microtime(true);

    //Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
    IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

/******************************************************

                INIT

*************************************************************/

    $startexec=microtime(true);

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('DetectMovement',$repository);
        }

    $installedModules = $moduleManager->GetInstalledModules();

    if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; return; }
    if (isset ($installedModules["EvaluateHardware"])) { echo "Modul EvaluateHardware ist installiert.\n"; } else { echo "Modul EvaluateHardware ist NICHT installiert.\n"; return;}
    //if (isset ($installedModules["RemoteReadWrite"])) { echo "Modul RemoteReadWrite ist installiert.\n"; } else { echo "Modul RemoteReadWrite ist NICHT installiert.\n"; return;}
    if (isset ($installedModules["RemoteAccess"])) { echo "Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; return;}

/*

jetzt wird für jeden Bewegungsmelder ein Event registriert. Das führt beim Message handler dazu das die class function handle event aufgerufen woird

Selbe Routine in RemoteAccess, allerdings wird dann auch auf einem Remote Server zusaetzlich geloggt


*/

IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");

    $componentHandling=new ComponentHandling();
    $commentField="zuletzt Konfiguriert von RemoteAccess EvaluateMotion um ".date("h:i am d.m.Y ").".";

/****************************************************************************************************************/
/*                                                                                                              */
/*                                      Install                                                                 */
/*                                                                                                              */
/****************************************************************************************************************/




/****************************************************************************************************************/
/*                                                                                                              */
/*                                      Execute                                                                 */
/*                                                                                                              */
/****************************************************************************************************************/

	if ($_IPS['SENDER']=="Execute")
		{
        $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
        $DetectDeviceListHandler = new DetectDeviceListHandler();               // neuer Handler für die DeviceList, registriert die Devices in EvaluateHarwdare_Configuration            

        echo "\n";
        echo "DetectMovement Temperaturregister aus der Configuration hereinholen, Spiegelregister auch registrieren:\n";								
        $DetectTemperatureHandler = new DetectTemperatureHandler();
        $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();
        $eventTempConfig=$DetectTemperatureHandler->Get_EventConfigurationAuto();    	
        $groups=$DetectTemperatureHandler->ListGroups("Temperatur");        /* Type angeben damit mehrere Gruppen aufgelöst werden können */
        $events=$DetectTemperatureHandler->ListEvents();
        echo "----------------Liste der DetectTemperature Events durchgehen:\n";    
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ";
            $moid=$DetectTemperatureHandler->getMirrorRegister($oid);
            if ($moid !== false) 
                {
                $mirror = IPS_GetName($moid);    
                $werte = @AC_GetLoggedValues($archiveHandlerID,$moid, time()-60*24*60*60, time(),1000);
                if ($werte === false) echo "Kein Logging für Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).")\n";
                if ( (isset($eventDeviceConfig[$oid])) && (isset($eventTempConfig[$oid])) )
                    {
                    if (IPS_ObjectExists($oid))
                        {    
                        echo str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75).
                                json_encode($eventDeviceConfig[$oid])."  ".json_encode($eventTempConfig[$oid])." Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).") Archive Groesse : ".count($werte)."\n";
                        /* check and get mirror register,. It is taken from config file. If config file is empty it is calculated from parent or other inputs and stored afterwards 
                            * Config function DetectDevice follows detecttemperaturehandler
                            */            
                        //$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','Mirror->'.$mirror);     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben , Mirror Register als Teil der Konfig*/
                        //$result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Temperature',false, true);	        // par 3 config overwrite
                        //if ($result) echo "   *** register Event $moid\n";
                        //$result=$DetectDeviceHandler->RegisterEvent($oid,'Topology','','Temperature,Mirror->'.$mirror,false, true);	        	/* par 3 config overwrite, Mirror Register als Zusatzinformation, nicht relevant */
                        //if ($result) echo "   *** register Event $oid\n";
                        }
                    else echo "   -> ****Fehler, $oid nicht mehr vorhanden aber in config eingetragen.\n";
                    }
                else 
                    {
                    echo str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75)." ---->   not in config.";
                    if (isset($eventDeviceConfig[$oid])===false) echo "DetectDeviceHandler->Get_EventConfigurationAuto() ist false  ";
                    if (isset($eventTempConfig[$oid])===false) echo "DetectTemperatureHandler->Get_EventConfigurationAuto() ist false  ";
                    echo "\n";
                    //$result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Temperature');                     // zumindest einmal in den DeviceHandler übernehmen
                    //if ($result) echo "   *** register Event $moid\n";
                    }
                }
            else echo "  -> ****Fehler, Mirror Register für ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75)." nicht gefunden.\n";
            }
        print_r($groups);
        echo "----------------Liste der DetectTemperature Gruppen durchgehen:\n";
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectTemperatureHandler->InitGroup($group);
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            //$result=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Temperature');		
            //if ($result) echo "   *** register Event $soid\n";
            }            
        echo "\n";
        echo "DetectMovement Feuchtigkeitsregister hereinholen:\n";								
        $DetectHumidityHandler = new DetectHumidityHandler();
        $groups=$DetectHumidityHandler->ListGroups("Humidity");
        //print_r($groups);
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectHumidityHandler->InitGroup($group);
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            }
        $events=$DetectHumidityHandler->ListEvents();
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            }
        echo "DetectMovement Climate Register hereinholen:\n";
        //$variable = 43240;
        $variable=false;
        $DetectClimateHandler = new DetectClimateHandler();
        //echo "Category for Mirror Registers is here : ".$DetectClimateHandler->getDetectDataID()."\n";
        //$result = $DetectClimateHandler->ListConfigurations(); print_R($result);

        $groups=$DetectClimateHandler->ListGroups("Climate",$variable);           // ohne Angabe von Climate werden die durch Beistrich getrennten Gruppen nicht aufgelöst
        //print_r($groups);
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectClimateHandler->InitGroup($group,false);            // true für Debug
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            }        


        $variable = 43240;
        echo "DetectMovement Sensor Register für $variable hereinholen:\n";
        //$variable=false;
        $DetectClimateHandler = new DetectClimateHandler();
        //echo "Category for Mirror Registers is here : ".$DetectClimateHandler->getDetectDataID()."\n";
        //$result = $DetectClimateHandler->ListConfigurations(); print_R($result);

        $groups=$DetectClimateHandler->ListGroups("Sensor",$variable);           // ohne Angabe von Sensor,Climate etc. werden die durch Beistrich getrennten Gruppen nicht aufgelöst, wenn die variable genannt wird istd er Type mehr oder wenuger egal
        print_r($groups);
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectClimateHandler->InitGroup($group,false);            // true für Debug
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            }        

        if (false)
            {    
            echo "Darstellung der Konfiguration in DetectMovement_VConfiguration:\n";
            echo "Bewegungsregister Auflistung der Events:\n";
            $DetectMovementHandler = new DetectMovementHandler();
            $DetectMovementHandler->Print_EventConfigurationAuto(true);         // true extended display
            echo "\n";

			echo "Ausgabe aller Event IDs mit zugeordneter Gruppe deren erster Parameter Motion ist:\n";
			print_r($DetectMovementHandler->ListEvents("Motion"));
			echo "Ausgabe aller Event IDs mit zugeordneter Gruppe deren erster Parameter Contact ist:\n";
			print_r($DetectMovementHandler->ListEvents("Contact"));
            }
        if (false)
            {
            // Funktion von RemoteAccess übernommen, finde alle bewegungsmelder
            if ( (function_exists('deviceList')) )
                {
                echo "Bewegungsmelder von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
                $result1 = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "MOTION"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungsensoren */
                $result2 = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "DIRECTION"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungssensoren aller Art */
                print_r($result1);

                }
            else          // reine Homematic Auswertung macht wenig Sinn
                {
                $Homematic = HomematicList();
                //print_r($Homematic);
                $FS20= FS20List();
                $cuscompid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.core.IPSComponent');

                $alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
                echo "\n";
                echo "Execute von Detect Movement, zusaetzliche Auswertungen.\n\n";
                $log=new Motion_Logging(false);                            // variablename und value sind null
                echo "===========================Alle Homematic Bewegungsmelder ausgeben.\n";
                foreach ($Homematic as $Name => $Key)
                    {
                    /* Alle Homematic Bewegungsmelder ausgeben */
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder */
                        echo "*******\nBearbeite Bewegungsmelder ".$Name."\n";
                        $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                        $log->Set_LogValue($oid);
                        $alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
                        }
                    if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
                        {
                        /* alle Kontakte */
                        echo "*******\nBearbeite Kontakt ".$Name."\n";
                        $oid=(integer)$Key["COID"]["STATE"]["OID"];
                        $log->Set_LogValue($oid);
                        $alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
                        }
                    }
                echo "===========================Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein.\n";
                IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
                $TypeFS20=RemoteAccess_TypeFS20();
                foreach ($FS20 as $Key)
                    {
                    /* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder */
                        echo "*******\nBearbeite FS20 Bewegungsmelder ".$Name."\n";
                        $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                        $log->Set_LogValue($oid);
                        $alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
                        }
                    /* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei verknüpfen */
                    if ((isset($Key["COID"]["StatusVariable"])==true))
                        {
                        foreach ($TypeFS20 as $Type)
                            {
                            if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
                                {
                                echo "*******\nBearbeite FS20 Bewegungsmelder ".$Name."\n";						
                                $oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
                                $variabletyp=IPS_GetVariable($oid);
                                IPS_SetName($oid,"MOTION");
                                $log->Set_LogValue($oid);
                                $alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
                                }
                            }
                        }
                    }

                    $alleMotionWerte.="********* Gesamtdarstellung\n".$log->writeEvents(true,true)."\n\n";
                    echo "\n\n======================================================================================\n";
                    echo $alleMotionWerte;
                    echo "\n\n======================================================================================\n";
                                
                    /* Detect Movement Auswertungen analysieren */
                    
                    /* Routine in Log_Motion uebernehmen */
                    IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                    IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                    $DetectMovementHandler = new DetectMovementHandler();
                    echo "Ausgabe aller Event IDs mit zugeordneter Gruppe deren erster Parameter Motion ist:\n";
                    print_r($DetectMovementHandler->ListEvents("Motion"));
                    echo "Ausgabe aller Event IDs mit zugeordneter Gruppe deren erster Parameter Contact ist:\n";
                    print_r($DetectMovementHandler->ListEvents("Contact"));

                    echo "\n==================================================================\n";
                    $groups=$DetectMovementHandler->ListGroups();
                    foreach($groups as $group=>$name)
                        {
                        echo "*****\nDetect Movement Gruppe ".$group." behandeln.\n";
                        $config=$DetectMovementHandler->ListEvents($group);
                        $status=false;
                        foreach ($config as $oid=>$params)
                            {
                            $status=$status || GetValue($oid);
                            echo "   OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
                            }
                        echo "Gruppe ".$group." hat neuen Status : ".(integer)$status."\n";
                        }

                    echo "****\nDetect Movement Konfiguration durchgehen:\n";
                    $config=IPSDetectMovementHandler_GetEventConfiguration();
                    //print_r($config);
                    foreach ($config as $oid=>$params)
                        {
                        echo "  OID: ".$oid." Name: ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),50)." Type :".str_pad($params[0],15)."Status: ".(integer)GetValue($oid)." Gruppe ".$params[1]."\n";
                        }

                    $groups=$DetectMovementHandler->ListGroups('Motion');		// wenn Parameter angegeben ist gibt es auch ein Explode der mit Komma getrennten Gruppennamen
                    //print_r($groups);
                    $gesamt=array();
                    foreach ($groups as $group=>$status)
                        {
                        $gesamt["Gesamtauswertung_".$group]["NAME"]="Gesamtauswertung_".$group;
                        $gesamt["Gesamtauswertung_".$group]["OID"]=@IPS_GetObjectIDByName("Gesamtauswertung_".$group,$DetectMovementHandler->getCustomComponentsDataGroup());
                        $gesamt["Gesamtauswertung_".$group]["MOID"]=@IPS_GetObjectIDByName("Gesamtauswertung_".$group,$DetectMovementHandler->getDetectMovementDataGroup());
                        }

                    $LogConfiguration=get_IPSComponentLoggerConfig();
                    $delayTime=$LogConfiguration["LogConfigs"]["DelayMotion"]/60;
                    echo "Delay zum Glätten sind ".($delayTime)." Minuten.\n\n";
                        
                    echo "****\nZusammenfassung:\n";	
                    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];			
                    foreach ($gesamt as $entry)
                        {
                        echo "\n    ".$entry["NAME"]."   ".$entry["OID"]." (".IPS_GetName($entry["OID"])."/".IPS_GetName(IPS_GetParent($entry["OID"])).")   ".$entry["MOID"]." (".IPS_GetName($entry["MOID"])."/".IPS_GetName(IPS_GetParent($entry["MOID"])).")\n";
                        $endtime=time();
                        $starttime=$endtime-60*60*24*10;
                        echo "       Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime)." für : ".$entry["OID"]." Aktuell : ".(GetValue($entry["OID"])?"Ein":"Aus")."\n";
                        $werte = AC_GetLoggedValues($archiveHandlerID, $entry["OID"], $starttime, $endtime, 0);
                        $zeile=0; $zeilemax=6;
                        foreach($werte as $wert)
                            {
                            echo "           ".date("D d.m H:i", $wert['TimeStamp'])."   ".($wert['Value']?"Ein":"Aus")."    ".$wert['Duration']."\n";
                            $zeile++;
                            if ($zeile>($zeilemax*2)) break;
                            }
                        echo "       Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime)." für : ".$entry["MOID"]." Aktuell : ".(GetValue($entry["OID"])?"Ein":"Aus")."   Geglättet mit ".$delayTime." Minuten.\n";
                        $werte = AC_GetLoggedValues($archiveHandlerID, $entry["MOID"], $starttime, $endtime, 0);
                        $zeile=0;
                        foreach($werte as $wert)
                            {
                            echo "           ".date("D d.m H:i", $wert['TimeStamp'])."   ".($wert['Value']?"Ein":"Aus")."    ".$wert['Duration']."\n";
                            $zeile++;
                            if ($zeile>$zeilemax) break;
                            }
                            
                        }	

                    echo "Was ist mit den Gesamtauswertungen_ im CustomComponents verzeichnis.\n";



                    echo "\n";
                    echo "Execute von Detect Movement, zusaetzliche Auswertungen fuer Temperatur.\n\n";
                    echo "===========================Alle Homematic Temperaturmelder ausgeben.\n";
                    
                    $alleTempWerte="\n\nHistorische Temperaturwerte aus den Logs der CustomComponents:\n\n";
                    
                    foreach ($Homematic as $Key)
                        {
                        /* alle Feuchtigkeits oder Temperaturwerte ausgeben */
                        if (isset($Key["COID"]["TEMPERATURE"])==true)
                            {
                            $oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
                            echo "$oid\n";
                            $log=new Temperature_Logging($oid);
                            $alleTempWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
                            }
                        }
                    $alleTempWerte.="********* Gesamtdarstellung\n".$log->writeEvents(true,true)."\n\n";
                    echo $alleTempWerte;
                }
            }
			
			
	}  // Ende if execute

?>