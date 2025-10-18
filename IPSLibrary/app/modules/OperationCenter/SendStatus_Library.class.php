<?php

/*
 * Sendstatus function, now as part of a class
 *
 *
 */

class SendStatus
    {
    //Objekteigenschaften
    protected $knownModules,$installedModules;

    function __construct()
        {
        // Repository
        $repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';

        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);

        $versionHandler = $moduleManager->VersionHandler();
        $this->knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
        $this->installedModules = $moduleManager->VersionHandler()->GetInstalledModules();            
        }

        
    /****************************************************************************************************
    * immer wenn eine Statusmeldung per email angefragt wird 
    *
    * Ausgabe des Status für aktuelle und historische Werte
    *
    ****************************************************************************************/

    function send_status($aktuell, $startexec=0, $debug=false)
        {
        if ($startexec==0) { $startexec=microtime(true); }
        $sommerzeit=false;
        $einleitung="Erstellt am ".date("D d.m.Y H:i")." fuer die ";

        /* alte Programaufrufe sind ohne Parameter, daher für den letzten Tag */

        if ($aktuell)
        {
        $einleitung.="Ausgabe der aktuellen Werte vom Gerät : ".IPS_GetName(0)." .\n";
        if ($debug) echo ">>Ausgabe der aktuellen Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
        }
        else
        {
        $einleitung.="Ausgabe der historischen Werte - Vortag vom Gerät : ".IPS_GetName(0).".\n";
        if ($debug) echo ">>Ausgabe der historischen Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
        }
        if (date("I")=="1")
            {
            $einleitung.="Wir haben jetzt Sommerzeit, daher andere Reihenfolge der Ausgabe.\n";
            $sommerzeit=true;
            }
        $einleitung.="\n";
        
        $inst_modules = "\n".$this->statusModules();
        echo ">>Auswertung der Module die upgedatet werden müssen. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";

        if (isset($this->installedModules["Amis"])==true)
            {
            $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Amis');
            $updatePeriodenwerteID=IPS_GetScriptIDByName('BerechnePeriodenwerte',$parentid);
            if ($debug) echo "Script zum Update der Periodenwerte:".$updatePeriodenwerteID." aufrufen. ".exectime($startexec)." Sek \n";
            IPS_RunScript($updatePeriodenwerteID);
            echo ">>AMIS Update Periodenwerte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
            }

        /* Alle werte aus denen eine Ausgabe folgt initialisieren */

        $cost=""; $internet=""; $statusverlauf=""; $ergebnis_tabelle=""; $alleStromWerte=""; $ergebnisTemperatur=""; $ergebnisRegen=""; $aktheizleistung=""; $ergebnis_tagesenergie=""; $alleTempWerte=""; $alleHumidityWerte="";
        $ergebnisStrom=""; $ergebnisStatus=""; $ergebnisBewegung=""; $ergebnisGarten=""; $IPStatus=""; $ergebnisSteuerung=""; $energieverbrauch="";

        $ergebnisOperationCenter="";
        $ergebnisErrorIPSLogger="";
        $ServerRemoteAccess="";
        $SystemInfo="";

        $dosOps = new dosOps();    
        $systemDir     = $dosOps->getWorkDirectory(); 

        /******************************************************************************************
        *
        * Allgemeiner Teil, unabhängig von Hardware oder Server
        *
        * zuerst aktuell dann historisch
        *		
        ******************************************************************************************/

        if ($aktuell) /* aktuelle Werte */
            {
            echo "------------------jetzt aktuelle Werte verarbeiten :\n";
            $alleTempWerte="";
            $alleHumidityWerte="";
            $alleMotionWerte="";
            $alleHelligkeitsWerte="";
            $alleStromWerte="";
            $alleHeizungsWerte="";
            $guthaben="";
            
            /******************************************************************************************
            
            Allgemeiner Teil, Auswertung für aktuelle Werte
            
            ******************************************************************************************/
            if ( (isset($this->installedModules["RemoteReadWrite"])==true) || (isset($this->installedModules["EvaluateHardware"])==true) )
                {
                if (isset($this->installedModules["EvaluateHardware"])==true) 
                    {
                    IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
                    }
                //else IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

                $Homematic = HomematicList();
                $FS20= FS20List();

                $alleTempWerte.="\n\nAktuelle Temperaturwerte direkt aus den HW-Registern:\n\n";
                $alleTempWerte.=ReadTemperaturWerte();
                if ($debug) echo "$alleTempWerte \n";

                $alleHumidityWerte.="\n\nAktuelle Feuchtigkeitswerte direkt aus den HW-Registern:\n\n";
            
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Feuchtigkeitswerte ausgeben */
                    if (isset($Key["COID"]["HUMIDITY"])==true)
                        {
                        $oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
                        $alleHumidityWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                        }
                    }
                if ($debug) echo "$alleHumidityWerte \n";

                $alleHelligkeitsWerte.="\n\nAktuelle Helligkeitswerte direkt aus den HW-Registern:\n\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Bewegungsmelder ausgeben */
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder, aber die Helligkeitswerte, um herauszufinden ob bei einem der Melder die Batterie leer ist */
                        if ( isset($Key["COID"]["BRIGHTNESS"]["OID"]) ) {$oid=(integer)$Key["COID"]["BRIGHTNESS"]["OID"]; }
                        else { $oid=(integer)$Key["COID"]["ILLUMINATION"]["OID"]; }
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $alleHelligkeitsWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        else
                            {
                            $alleHelligkeitsWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        }
                    }
                if ($debug) echo "$alleHelligkeitsWerte \n";

                $alleMotionWerte.="\n\nAktuelle Bewegungswerte direkt aus den HW-Registern:\n\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Bewegungsmelder ausgeben */
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder */

                        $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        else
                            {
                            $alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        }
                    }

                /**
                * Bewegungswerte von den FS20 Registern, eigentlich schon ausgemustert
                *
                *******************************************************************************/

                //if (isset($installedModules["RemoteAccess"])==true)
                    {
                    //IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
                    IPSUtils_Include ("IPSComponentLogger_Configuration.inc.php","IPSLibrary::config::core::IPSComponent");				
                    $TypeFS20=RemoteAccess_TypeFS20();
                    foreach ($FS20 as $Key)
                        {
                        /* FS20 alle Bewegungsmelder ausgeben */
                        if ( (isset($Key["COID"]["MOTION"])==true) )
                            {
                            /* alle Bewegungsmelder */

                            $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                            $variabletyp=IPS_GetVariable($oid);
                            if ($variabletyp["VariableProfile"]!="")
                                {
                                $alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                                }
                            else
                                {
                                $alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                                }
                            }
                        /* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei in Remote Access verknüpfen */
                        if ((isset($Key["COID"]["StatusVariable"])==true))
                            {
                            foreach ($TypeFS20 as $Type)
                                {
                                if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
                                    {
                                    $oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
                                    $variabletyp=IPS_GetVariable($oid);
                                    IPS_SetName($oid,"MOTION");
                                    if ($variabletyp["VariableProfile"]!="")
                                        {
                                        $alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                                        }
                                    else
                                        {
                                        $alleMotionWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                                        }
                                    }
                                }
                            }
                        }
                    }
                if ($debug) echo "$alleMotionWerte \n";

                $alleStromWerte.="\n\nAktuelle Energiewerte direkt aus den HW-Registern:\n\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Energiesensoren ausgeben */
                    if ( (isset($Key["COID"]["VOLTAGE"])==true) )
                        {
                        /* alle Energiesensoren */

                        $oid=(integer)$Key["COID"]["ENERGY_COUNTER"]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $alleStromWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        else
                            {
                            $alleStromWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        }
                    }
                //if ($debug) echo "$alleStromWerte \n";                //wird weiter unten noch erweitert

                $alleHeizungsWerte.=ReadThermostatWerte();
                $alleHeizungsWerte.=ReadAktuatorWerte();
                if ($debug) echo "$alleHeizungsWerte \n";
                            
                $ergebnisRegen.="\n\nAktuelle Regenmengen direkt aus den HW-Registern:\n\n";
                $regenmelder=0;
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Energiesensoren ausgeben */
                    if ( (isset($Key["COID"]["RAIN_COUNTER"])==true) )
                        {
                        /* alle Regenwerte */
                        $regenmelder++;
                        $oid=(integer)$Key["COID"]["RAIN_COUNTER"]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $ergebnisRegen.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        else
                            {
                            $ergebnisRegen.=str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        }
                    }
                if ($regenmelder==0) $ergebnisRegen="";	/* Ausgabe rückgängig machen, es gibt keine Regenmelder. */	
                    
                if (isset($this->installedModules["Gartensteuerung"])==true)
                    {
                    echo "Die Regenwerte der letzten 20 Tage ausgeben.\n";
                    $ergebnisRegen.="\nIn den letzten 20 Tagen hat es zu folgenden Zeitpunkten geregnet:\n";
                    /* wenn die Gartensteuerung installiert ist, gibt es einen Regensensor der die aktuellen Regenmengen der letzten 10 Tage erfassen kann */
                    IPSUtils_Include ('Gartensteuerung_Library.class.ips.php', 'IPSLibrary::app::modules::Gartensteuerung');
                    $gartensteuerung = new GartensteuerungStatistics();
                    $rainResults=$gartensteuerung->listRainEvents(20);
                    foreach ($rainResults as $regeneintrag)
                        {
                        $ergebnisRegen.="  Regenbeginn ".date("d.m H:i",$regeneintrag["Beginn"]).
                            "  Regenende ".date("d.m H:i",$regeneintrag["Ende"]).
                            " mit insgesamt ".number_format($regeneintrag["Regen"], 1, ",", "").
                            " mm Regen. Max pro Stunde ca. ".number_format($regeneintrag["Max"], 1, ",", "")."mm/Std.\n";
                        }				
                    }
                if ($debug) 
                    {
                    if ($regenmelder==0) echo "Es wurde kein Regensensor installiert.\n";
                    else echo "$ergebnisRegen \n";
                    }
                    
                echo ">>RemoteReadWrite. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
                }

            /******************************************************************************************/

            if (isset($this->installedModules["Amis"])==true)
                {
                echo "--> AMIS Stromverbrauchswerte erfassen:\n";
                $alleStromWerte.="\n\nAktuelle Stromverbrauchswerte direkt aus den gelesenen und dafür konfigurierten Registern:\n\n";

                $amisdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
                IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
                IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis'); 
                $Amis = new Amis();           
                $MeterConfig = $Amis->getMeterConfig();

                foreach ($MeterConfig as $meter)
                    {
                    if ($meter["TYPE"]=="Amis")
                    {
                    $alleStromWerte.="\nAMIS Zähler im ".$meter["NAME"].":\n\n";
                        $amismeterID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
                        $AmisID = CreateVariableByName($amismeterID, "AMIS", 3);
                        $AmisVarID = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
                        $AMIS_Werte=IPS_GetChildrenIDs($AmisVarID);
                        for($i = 0; $i < sizeof($AMIS_Werte);$i++)
                            {
                            //$alleStromWerte.=str_pad(IPS_GetName($AMIS_Werte[$i]),30)." = ".GetValue($AMIS_Werte[$i])." \n";
                            if (IPS_GetVariable($AMIS_Werte[$i])["VariableCustomProfile"]!="")
                            {
                                $alleStromWerte.=str_pad(IPS_GetName($AMIS_Werte[$i]),30)." = ".str_pad(GetValueFormatted($AMIS_Werte[$i]),30)."   (".date("d.m H:i",IPS_GetVariable($AMIS_Werte[$i])["VariableChanged"]).")\n";
                                }
                            else
                            {
                                $alleStromWerte.=str_pad(IPS_GetName($AMIS_Werte[$i]),30)." = ".str_pad(GetValue($AMIS_Werte[$i]),30)."   (".date("d.m H:i",IPS_GetVariable($AMIS_Werte[$i])["VariableChanged"]).")\n";
                                }
                            }
                        }
                    if ($meter["TYPE"]=="Homematic")
                    {
                    $alleStromWerte.="\nHomematic Zähler im ".$meter["NAME"].":\n\n";
                        $HM_meterID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
                        $HM_Wirkenergie_meterID = CreateVariableByName($HM_meterID, "Wirkenergie", 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
                        if (IPS_GetVariable($HM_Wirkenergie_meterID)["VariableCustomProfile"]!="")
                        {
                            $alleStromWerte.=str_pad(IPS_GetName($HM_Wirkenergie_meterID),30)." = ".str_pad(GetValueFormatted($HM_Wirkenergie_meterID),30)."   (".date("d.m H:i",IPS_GetVariable($HM_Wirkenergie_meterID)["VariableChanged"]).")\n";
                            }
                        else
                        {
                            $alleStromWerte.=str_pad(IPS_GetName($HM_Wirkenergie_meterID),30)." = ".str_pad(GetValue($HM_Wirkenergie_meterID),30)."   (".date("d.m H:i",IPS_GetVariable($HM_Wirkenergie_meterID)["VariableChanged"]).")\n";
                            }
                        $HM_Wirkleistung_meterID = CreateVariableByName($HM_meterID, "Wirkleistung", 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
                        if (IPS_GetVariable($HM_Wirkleistung_meterID)["VariableCustomProfile"]!="")
                        {
                            $alleStromWerte.=str_pad(IPS_GetName($HM_Wirkleistung_meterID),30)." = ".str_pad(GetValueFormatted($HM_Wirkleistung_meterID),30)."   (".date("d.m H:i",IPS_GetVariable($HM_Wirkleistung_meterID)["VariableChanged"]).")\n";
                            }
                        else
                        {
                            $alleStromWerte.=str_pad(IPS_GetName($HM_Wirkleistung_meterID),30)." = ".str_pad(GetValue($HM_Wirkleistung_meterID),30)."   (".date("d.m H:i",IPS_GetVariable($HM_Wirkleistung_meterID)["VariableChanged"]).")\n";
                            }

                        } /* endeif */
                    } /* ende foreach */
                if ($debug) echo "$alleStromWerte \n";
                echo ">>AMIS. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
                } /* endeif */

            /******************************************************************************************/
            echo "===============================================================\n";

            if (isset($this->installedModules["OperationCenter"])==true)
                {
                $ergebnisOperationCenter.="\nAusgabe der Erkenntnisse des Operation Centers, Logfile: \n\n";

                IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
                IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
                
                $CatIdData  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.OperationCenter');
                $categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CatIdData, 20);
                $input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
                $log_OperationCenter=new Logging($systemDir."Log_OperationCenter.csv",$input);
                if ($debug) echo "log Operation center vorbereitet. \n";

                $subnet="10.255.255.255";
                $OperationCenter=new OperationCenter($subnet);
                echo "DeviceManagement initialisiern:\n";
                $DeviceManager = new DeviceManagement_Homematic($debug);
                
                $pingOperation       = new PingOperation();

                $ergebnisOperationCenter.="Lokale IP Adresse im Netzwerk : \n";
                echo "Lokale IP Adresse im Netzwerk suchen.\n";
                $result=$OperationCenter->ownIPaddress($debug);
                foreach ($result as $ip => $data)
                    {
                    $ergebnisOperationCenter.="  Port \"".$data["Name"]."\" hat IP Adresse ".$ip." und das Gateway ".$data["Gateway"].".\n";
                    }
                //if ($debug) echo "$ergebnisOperationCenter \n";
                
                $result=$OperationCenter->whatismyIPaddress1()[0];
                if ($result["IP"]== true)
                    {
                    $ergebnisOperationCenter.= "Externe IP Adresse : \n";
                    $ergebnisOperationCenter.= "  Server liefert : ".$result["IP"]."\n\n";
                    }
                if ($debug) echo "$ergebnisOperationCenter \n";

                $ergebnisOperationCenter.="Systeminformationen : \n\n";
                $ergebnisOperationCenter.=$OperationCenter->readSystemInfo()."\n";
                    
                $ergebnisOperationCenter.="Angeschlossene bekannte Endgeräte im lokalen Netzwerk : \n\n";
                $ergebnisOperationCenter.=$OperationCenter->find_HostNames();
                if ($debug) echo "$ergebnisOperationCenter \n";

                $OperationCenterConfig = OperationCenter_Configuration();

                echo "Aktuelles Datenvolumen für die verwendeten Router \n";
                $ergebnisOperationCenter.="\nAktuelles Datenvolumen für die verwendeten Router : \n";
                foreach ($OperationCenterConfig['ROUTER'] as $router)
                    {
                    if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
                        {

                        }
                    else
                        {                    
                        $ergebnisOperationCenter.="  Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER'];
                        $router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CatIdData);
                        if ($router_categoryId !== false)		// wenn in Install noch nicht angelegt, auch hier im Timer ignorieren
                            {
                            $ergebnisOperationCenter.="\n";
                            if ($debug) echo "****************************************************************************************************\n";
                            switch (strtoupper($router["TYP"]))
                                {                    
                                case 'B2368':
                                case 'MR3420':      
                                    $ergebnisOperationCenter.= "    Werte von Heute     : ".$OperationCenter->get_router_history($router,0,1)." Mbyte. \n";
                                    $ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_router_history($router,1,1)." Mbyte. \n";
                                    $ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,7),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,30),0)."/".
                                                        round($OperationCenter->get_router_history($router,30,30),0)." \n";
                                    break;
                                case 'RT1900AC':								
                                case 'RT2600AC':								
                                    $ergebnisOperationCenter.="\n";
                                    $ergebnisOperationCenter.= "    Werte von heute     : ".$OperationCenter->get_routerdata_RT1900($router,true)." Mbyte \n";
                                    $ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_routerdata_RT1900($router,false)." Mbyte \n";
                                    $ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,7),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,30),0)."/".
                                                        round($OperationCenter->get_router_history($router,30,30),0)." \n";
                                    break;
                                case 'MBRN3000':
                                    $ergebnisOperationCenter.="\n";
                                    $ergebnisOperationCenter.= "    Werte von heute     : ".$OperationCenter->get_routerdata_MBRN3000($router,true)." Mbyte \n";
                                    $ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_routerdata_MBRN3000($router,false)." Mbyte \n";
                                    $ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,7),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,30),0)."/".
                                                        round($OperationCenter->get_router_history($router,30,30),0)." \n";
                                    break;
                                default:
                                    $ergebnisOperationCenter.="    Keine Werte. Router nicht unterstützt.\n";
                                break;
                                }	// ende switch
                        }		// ende roter category available
                        }	// ende if status true
                    }		// ende foreach
                $ergebnisOperationCenter.="\n";
                
                $ergebnisOperationCenter.=$pingOperation->writeSysPingActivity();         // Angaben über die Verfügbarkeit der Internetfähigen Geräte
                
                $ergebnisOperationCenter.="\n\nErreichbarkeit der Hardware Register/Instanzen, zuletzt erreicht am .... :\n\n"; 
                $ergebnisOperationCenter.=$DeviceManager->HardwareStatus(true);
                if ($debug) echo "$ergebnisOperationCenter \n";
                
                $ergebnisErrorIPSLogger.="\nAus dem Error Log der letzten Tage :\n\n";
                $ergebnisErrorIPSLogger.=$OperationCenter->getIPSLoggerErrors();

                /******************************************************************************************/

                $alleHM_Errors=$DeviceManager->HomematicFehlermeldungen();
                
                echo ">>OperationCenter. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
                }

            /******************************************************************************************/

            $ServerRemoteAccess .="LocalAccess Variablen dieses Servers:\n\n";
                
            /* Remote Access Crawler für Ausgabe aktuelle Werte */

            $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $jetzt=time();
            $endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
            $starttime=$endtime-60*60*24*1; /* ein Tag */

            $ServerRemoteAccess.="RemoteAccess Variablen aller hier gespeicherten Server:\n\n";

            $visID=@IPS_GetObjectIDByName ( "Visualization", 0 );
            $ServerRemoteAccess .=  "Visualization ID : ";
            if ($visID==false)
                {
                $ServerRemoteAccess .=  "keine\n";
                }
            else
                {
                $ServerRemoteAccess .=  $visID."\n";
                $visWebRID=@IPS_GetObjectIDByName ( "Webfront-Retro", $visID );
                $ServerRemoteAccess .=  "  Webfront Retro     ID : ";
                if ($visWebRID==false) {$ServerRemoteAccess .=  "keine\n";} else {$ServerRemoteAccess .=  $visWebRID."\n";}

                $visMobileID=@IPS_GetObjectIDByName ( "Mobile", $visID );
                $ServerRemoteAccess .=  "  Mobile             ID : ";
                if ($visMobileID==false) {$ServerRemoteAccess .=  "keine\n";} else {$ServerRemoteAccess .=  $visMobileID."\n";}

                $visWebID=@IPS_GetObjectIDByName ( "WebFront", $visID );
                $ServerRemoteAccess .=  "  WebFront           ID : ";
                if ($visWebID==false)
                    {
                    $ServerRemoteAccess .=  "keine\n";
                    }
                else
                    {
                    $ServerRemoteAccess .=  $visWebID."\n";
                    $visUserID=@IPS_GetObjectIDByName ( "User", $visWebID );
                    $ServerRemoteAccess .=  "    Webfront User          ID : ";
                    if ($visUserID==false) {$ServerRemoteAccess .=  "keine\n";} else {$ServerRemoteAccess .=  $visUserID."\n";}

                    $visAdminID=@IPS_GetObjectIDByName ( "Administrator", $visWebID );
                    $ServerRemoteAccess .=  "    Webfront Administrator ID : ";
                    if ($visAdminID==false)
                        {
                        $ServerRemoteAccess .=  "keine\n";
                        }
                    else
                        {
                        $ServerRemoteAccess .=  $visAdminID."\n";

                        $visRemAccID=@IPS_GetObjectIDByName ( "RemoteAccess", $visAdminID );
                        $ServerRemoteAccess .=  "      RemoteAccess ID : ";
                        if ($visRemAccID==false)
                            {
                            $ServerRemoteAccess .=  "keine\n";
                            }
                        else
                            {
                            $ServerRemoteAccess .=  $visRemAccID."\n";
                            $server=IPS_GetChildrenIDs($visRemAccID);
                            foreach ($server as $serverID)
                            {
                            $ServerRemoteAccess .=  "        Server    ID : ".$serverID." Name : ".IPS_GetName($serverID)."\n";
                                $categories=IPS_GetChildrenIDs($serverID);
                                foreach ($categories as $categoriesID)
                                {
                                $ServerRemoteAccess .=  "          Category  ID : ".$categoriesID." Name : ".IPS_GetName($categoriesID)."\n";
                                    $objects=IPS_GetChildrenIDs($categoriesID);
                                    $objectsbyName=array();
                                    foreach ($objects as $key => $objectID)
                                    {
                                    $objectsbyName[IPS_GetName($objectID)]=$objectID;
                                        }
                                    ksort($objectsbyName);
                                    //print_r($objectsbyName);
                                    foreach ($objectsbyName as $objectID)
                                        {
                                        $werte = @AC_GetLoggedValues($archiveHandlerID, $objectID, $starttime, $endtime, 0);
                                        if ($werte===false)
                                            {
                                            $log="kein Log !!";
                                            }
                                        else
                                        {
                                            $log=sizeof($werte)." logged in 24h";
                                            }
                                        if ( (IPS_GetVariable($objectID)["VariableProfile"]!="") or (IPS_GetVariable($objectID)["VariableCustomProfile"]!="") )
                                            {
                                            echo "Variablenprofil von $objectID (".IPS_GetName($objectID).") erkannt: Standard ".IPS_GetVariable($objectID)["VariableProfile"]." Custom ".IPS_GetVariable($objectID)["VariableCustomProfile"]."\n";
                                            $ServerRemoteAccess .=  "            ".str_pad(IPS_GetName($objectID),30)." = ".str_pad(GetValueFormatted($objectID),30)."   (".date("d.m H:i",IPS_GetVariable($objectID)["VariableChanged"]).") "
                                                .$log."\n";
                                            }
                                        else
                                            {
                                            $ServerRemoteAccess .=  "            ".str_pad(IPS_GetName($objectID),30)." = ".str_pad(GetValue($objectID),30)."   (".date("d.m H:i",IPS_GetVariable($objectID)["VariableChanged"]).") "
                                                .$log."\n";
                                            }
                                        //print_r(IPS_GetVariable($objectID));
                                        } /* object */
                                    } /* Category */
                                } /* Server */
                            } /* RemoteAccess */
                        }  /* Administrator */
                    }   /* Webfront */
                } /* Visualization */

            //echo $ServerRemoteAccess;

            /*****************************************************************************************
            *
            * Guthaben Verwaltung von Simkarten
            *
            *******************************************************************************/

            if (isset($this->installedModules["Guthabensteuerung"])==true)
                {
                IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

                $dataID      = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
                $guthabenid  = @IPS_GetObjectIDByName("Guthaben", $dataID);

                $GuthabenConfig = get_GuthabenConfiguration();
                //print_r($GuthabenConfig);
                $guthaben="\nGuthabenstatus:\n";
                foreach ($GuthabenConfig as $TelNummer)
                    {
                    if (strtoupper($TelNummer["STATUS"])=="ACTIVE")
                        {
                        $phone1ID      = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"],$guthabenid);
                        $phone_Summ_ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_Summary",$phone1ID);
                        if ($phone_Summ_ID) $guthaben .= "\n    ".GetValue($phone_Summ_ID);
                        }
                    }
                $guthaben .= "\n\n";			
                $guthaben.="Ausgabe Status der aktiven SIM Karten :\n\n";
                $guthaben.="    Nummer       Name                             letztes File von             letzte Aenderung Guthaben    letzte Aufladung\n";		
                foreach ($GuthabenConfig as $TelNummer)
                    {
                    //print_r($TelNummer);
                    $phone1ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"], $guthabenid);
                    $dateID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_Date", $phone1ID);
                    $ldateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_loadDate", $phone1ID);
                    $udateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_unchangedDate", $phone1ID);
                    $userID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_User", $phone1ID);
                    if (strtoupper($TelNummer["STATUS"])=="ACTIVE") 
                        {
                        if ($phone1ID) $guthaben.="    ".$TelNummer["NUMMER"]."  ".str_pad(GetValue($userID),30)."  ".str_pad(GetValue($dateID),30)." ".str_pad(GetValue($udateID),30)." ".GetValue($ldateID)."\n";
                        else echo "Nicht alle Guthaben Variablen gesetzt : $phone1ID $dateID $ldateID $udateID $userID $phone_Summ_ID \n";
                        }
                    //echo "Telnummer ".$TelNummer["NUMMER"]." ".$udateID."\n";
                    }
                $guthaben.="\n";    
                }
            else
                {
                $guthaben="";
                }
            echo $guthaben;
            
            /*****************************************************************************************
            *
            * SystemInfo des jeweiligen PCs ausgeben
            *
            *******************************************************************************/

            $SystemInfo.="System Informationen dieses Servers:\n\n";

            exec('systeminfo',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden */

            $PrintLines="";
            foreach($catch as $line)
                {
                if (strlen($line)>2)
                    {
                    $PrintLines.=$line."\n";
                    }  /* ende strlen */
                }
            $SystemInfo.=$PrintLines."\n\n";
            
            
            if ($sommerzeit)
            {
                $ergebnis=$einleitung.$ergebnisTemperatur.$ergebnisRegen.$ergebnisOperationCenter.$aktheizleistung.$alleHeizungsWerte.$ergebnis_tagesenergie.$alleTempWerte.
                $alleHumidityWerte.$alleHelligkeitsWerte.$alleMotionWerte.$alleStromWerte.$alleHM_Errors.$ServerRemoteAccess.$guthaben.$SystemInfo.$ergebnisErrorIPSLogger;
                }
            else
            {
                $ergebnis=$einleitung.$aktheizleistung.$ergebnis_tagesenergie.$ergebnisTemperatur.$alleTempWerte.$alleHumidityWerte.$alleHelligkeitsWerte.$alleHeizungsWerte.
                $ergebnisOperationCenter.$alleMotionWerte.$alleStromWerte.$alleHM_Errors.$ServerRemoteAccess.$guthaben.$SystemInfo.$ergebnisErrorIPSLogger;
            }
            echo ">>Ende aktuelle Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
            }
        else   /* historische Werte */
            {
            echo "------------------jetzt historische Werte verarbeiten :\n";
            $alleHeizungsWerte="";

            /******************************************************************************************

            Allgemeiner Teil, Auswertung für historische Werte

            ******************************************************************************************/


            /**************Stromverbrauch, Auslesen der Variablen von AMIS *******************************************************************/

            $ergebnistab_energie="";
            $ergebnistab_energie.=$this->amisHistorischeWerte();
            echo ">>AMIS historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
                
            /************** Guthaben auslesen ****************************************************************************/
            
            if (isset($this->installedModules["Guthabensteuerung"])==true)
                {
                IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

                $dataID      = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
                $guthabenid  = @IPS_GetObjectIDByName("Guthaben", $dataID);

                $GuthabenConfig = get_GuthabenConfiguration();
                //print_r($GuthabenConfig);
                $guthaben="Guthabenstatus:\n";
                foreach ($GuthabenConfig as $TelNummer)
                    {
                    if (strtoupper($TelNummer["STATUS"])=="ACTIVE")
                        {
                        $phone1ID      = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"],$guthabenid);
                        $phone_Summ_ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_Summary",$phone1ID);
                        if ($phone_Summ_ID) $guthaben .= "\n".GetValue($phone_Summ_ID);
                        elseif ($phone1ID) echo "send_status historische Werte : Phone_".$TelNummer["NUMMER"]."_Summary in $phone1ID nicht gefunden.\n";
                        else echo "send_status historische Werte : Phone_".$TelNummer["NUMMER"]." in $guthabenid nicht gefunden.\n";
                        }
                    }
                $guthaben .= "\n\n";			
                echo ">>Guthaben historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
                }
            else
                {
                $guthaben="";
                }

            /************** Werte der Custom Components ****************************************************************************/

            $alleComponentsWerte="";
            if (isset($this->installedModules["CustomComponent"])==true)
                {
                $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
                IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
                $moduleManagerCC = new IPSModuleManager('CustomComponent',$repository);
                $CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
                $Category=IPS_GetChildrenIDs($CategoryIdData);
                //$search=array('HeatControl','Auswertung');          // Aktuatoren in CustomComponents Daten suchen
                //$search=array('HeatSet','Auswertung');
                $search=array('*','Auswertung');
                $result=array();
                $power=array();
                foreach ($Category as $CategoryId)
                    {
                    //echo "  Category    ID : ".$CategoryId." Name : ".IPS_GetName($CategoryId)."\n";
                    $Params = explode("-",IPS_GetName($CategoryId)); 
                    $SubCategory=IPS_GetChildrenIDs($CategoryId);
                    foreach ($SubCategory as $SubCategoryId)
                        {
                        if ( (sizeof($Params)>1) && ( (isset($search) == false) || ( ( ($search[0]==$Params[0]) || ($search[0]=="*") ) && ( ($search[1]==$Params[1]) || ($search[1]=="*") ) ) )	)
                            {
                            //echo "       ".IPS_GetName($SubCategoryId)."   ".$Params[0]."   ".$Params[1]."\n";
                            $result[]=$SubCategoryId;
                            $Values=IPS_GetChildrenIDs($SubCategoryId);
                            foreach ($Values as $valueID)                
                                {
                                $Types = explode("_",IPS_GetName($valueID));
                                switch ($Types[1])
                                    {
                                    case "Changetime":
                                        echo "         * ".IPS_GetName($valueID)."   ".date("d.m.y H:i:s",GetValue($valueID))."\n";
                                        break;
                                    case "Power":
                                        $power[]=$valueID;
                                    default:
                                        echo "         * ".IPS_GetName($valueID)."   ".GetValue($valueID)."\n";
                                        break;
                                    }    
                                }
                            }
                        //$webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["NAME"]=IPS_GetName($SubCategoryId);
                        //$webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["ORDER"]=IPS_GetObject($SubCategoryId)["ObjectPosition"];
                        }
                    }
                $archiveOps = new archiveOps();                
                $alleComponentsWerte .= "\nErfasste Werte in CustomComponents:\n";
                $alleComponentsWerte .= $archiveOps->getComponentValues($result,false);             // keine logs
                }

            /************** Detect Movement Motion Detect ****************************************************************************/

            $alleMotionWerte="";
            print_r($this->installedModules);
            if ( (isset($this->installedModules["DetectMovement"])==true) && ( (isset($this->installedModules["RemoteReadWrite"])==true) || (isset($this->installedModules["EvaluateHardware"])==true) ) )
                {
                echo "=====================Detect Movement Motion Detect \n";
                IPSUtils_Include ('IPSComponentSensor_Motion.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
                if (isset($this->installedModules["EvaluateHardware"])==true) 
                    {
                    IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
                    }
                //elseif (isset($installedModules["RemoteReadWrite"])==true) IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

                $Homematic = HomematicList();
                $FS20= FS20List();
                $log=new Motion_LoggingStatistics(true);                  // construct ohne Variable wird nicht mehr akzeptiert, class macht default Werte dazu, true für Debug
            
                $cuscompid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.core.IPSComponent');
            
                $alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
                echo "===========================Alle Homematic Bewegungsmelder ausgeben.\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Bewegungsmelder ausgeben */
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder */
                        $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                        $log->Set_LogValue($oid);
                        $alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
                        }
                    }
                echo "===========================Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein.\n";
                if (isset($this->installedModules["RemoteAccess"])==true) IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
                $TypeFS20=RemoteAccess_TypeFS20();
                foreach ($FS20 as $Key)
                    {
                    /* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder */
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
                echo ">>DetectMovement historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
                }
            /******************************************************************************************/

            if (isset($this->installedModules["Gartensteuerung"])==true)
                {
                $gartensteuerung = new Gartensteuerung();
                $ergebnisGarten="\n\nVerlauf der Gartenbewaesserung:\n\n";
                $ergebnisGarten=$ergebnisGarten.$gartensteuerung->listEvents()."\n";
                echo ">>Gartensteuerung historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
                }

            /******************************************************************************************/

            if (isset($this->installedModules["OperationCenter"])==true)
                {
                $ergebnisOperationCenter="\nAusgabe der Erkenntnisse des Operation Centers, Logfile: \n\n";

                IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
                IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

                $CatIdData  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.OperationCenter');
                $categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CatIdData, 20);
                $input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
                $log_OperationCenter=new Logging($systemDir."Log_OperationCenter.csv",$input);

                $subnet="10.255.255.255";
                $OperationCenter=new OperationCenter($subnet);
                $ergebnisOperationCenter.=$log_OperationCenter->PrintNachrichten();

                $OperationCenterConfig = OperationCenter_Configuration();
                $ergebnisOperationCenter.="\nHistorisches Datenvolumen für die verwendeten Router : \n";
                $historie="";
                foreach ($OperationCenterConfig['ROUTER'] as $router)
                    {
                    if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
                        {

                        }
                    else
                        {                    
                        $ergebnisOperationCenter.="  Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER'];
                        $router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CatIdData);
                        if ($router_categoryId !== false)		// wenn in Install noch nicht angelegt, auch hier im Timer ignorieren
                            {
                            $ergebnisOperationCenter.="\n";
                            echo "****************************************************************************************************\n";
                            switch (strtoupper($router["TYP"]))
                                {                    
                                case 'B2368':
                                case 'MR3420':      
                                    $ergebnisOperationCenter.= "    Werte von Heute     : ".$OperationCenter->get_router_history($router,0,1)." Mbyte. \n";
                                    $ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_router_history($router,1,1)." Mbyte. \n";
                                    $ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,7),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,30),0)."/".
                                                        round($OperationCenter->get_router_history($router,30,30),0)." \n";
                                    break;
                                case 'RT1900AC':								
                                case 'RT2600AC':								
                                    $ergebnisOperationCenter.="\n";
                                    $ergebnisOperationCenter.= "    Werte von heute     : ".$OperationCenter->get_routerdata_RT1900($router,true)." Mbyte \n";
                                    $ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_routerdata_RT1900($router,false)." Mbyte \n";
                                    $ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,7),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,30),0)."/".
                                                        round($OperationCenter->get_router_history($router,30,30),0)." \n";
                                    break;
                                case 'MBRN3000':
                                    $ergebnisOperationCenter.="\n";
                                    $ergebnisOperationCenter.= "    Werte von heute     : ".$OperationCenter->get_routerdata_MBRN3000($router,true)." Mbyte \n";
                                    $ergebnisOperationCenter.= "    Werte von Gestern   : ".$OperationCenter->get_routerdata_MBRN3000($router,false)." Mbyte \n";
                                    $ergebnisOperationCenter.= "    Historie 1/7/30/30  : ".round($OperationCenter->get_router_history($router,0,1),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,7),0)."/".
                                                        round($OperationCenter->get_router_history($router,0,30),0)."/".
                                                        round($OperationCenter->get_router_history($router,30,30),0)." \n";
                                    break;
                                default:
                                break;
                                }	// ende switch
                        }		// ende roter category available
                        }	// ende if status true
                    }		// ende foreach
                $ergebnisOperationCenter.="\n";
                echo ">>OperationCenter historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
                }
            
            /******************************************************************************************/

            if ($sommerzeit)
                {
                    $ergebnis=$einleitung.$ergebnisRegen.$guthaben.$cost.$internet.$statusverlauf.$ergebnisStrom.
                        $ergebnisStatus.$ergebnisBewegung.$ergebnisGarten.$ergebnisSteuerung.$IPStatus.$energieverbrauch.$ergebnis_tabelle.
                            $ergebnistab_energie.$ergebnis_tagesenergie.$ergebnisOperationCenter.$alleComponentsWerte.$alleMotionWerte.$alleHeizungsWerte.$inst_modules;
                    }
                else
                {
                    $ergebnis=$einleitung.$ergebnistab_energie.$energieverbrauch.$ergebnis_tabelle.$ergebnis_tagesenergie.$alleHeizungsWerte.
                    $ergebnisRegen.$guthaben.$cost.$internet.$statusverlauf.$ergebnisStrom.
                        $ergebnisStatus.$ergebnisBewegung.$ergebnisSteuerung.$ergebnisGarten.$ergebnisOperationCenter.$IPStatus.$alleComponentsWerte.$alleMotionWerte.$inst_modules;
                    }
                }
            echo ">>ENDE. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
            return $ergebnis;
            }

	function repository()
        {
        // Repository
        $repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';

        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);

        $versionHandler = $moduleManager->VersionHandler();
        $versionHandler->BuildKnownModules();
        }

	function statusModules()
        {
        $repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);            

        $inst_modules = "Verfügbare Module und die installierte Version :\n\n";
        $inst_modules.= "Modulname                  Version    Version      Beschreibung\n";
        $inst_modules.= "                          verfügbar installiert                   \n";
        
        $upd_modules = "\n";
        $upd_modules .= "Module die upgedated werden müssen und die installierte Version :\n\n";
        $upd_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";

        foreach ($this->knownModules as $module=>$data)
            {
            $infos   = $moduleManager->GetModuleInfos($module);
            $inst_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10);
            if (array_key_exists($module, $this->installedModules))
                {
                $inst_modules .= " ".str_pad($infos['CurrentVersion'],10)."   ";
                if ($infos['CurrentVersion']!=$infos['Version'])
                    {
                    $inst_modules .= "**";
                    $upd_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10)." ".str_pad($infos['CurrentVersion'],10)."   ".$infos['Description']."\n";
                    }
                }
            else
                {
                $inst_modules .= "  none        ";
            }
            $inst_modules .=  $infos['Description']."\n";
            }
        $upd_modules .= "\n";
        return ($inst_modules.$upd_modules);
        }

    /*
     *************Stromverbrauch, Auslesen der Variablen von AMIS ******************************************************************
     */
    function amisHistorischeWerte($debug=false)
        {
        $ergebnistab_energie="";
        if (isset($this->installedModules["Amis"])==true)
            {
            /* nur machen wenn AMIS installiert */
            IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');		
            IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
            $Amis = new Amis();           
            $MeterConfig = $Amis->getMeterConfig();
            if ($debug) print_r($MeterConfig);
            $ergebnistab_energie="";
            
            $amis=new Amis();
            $Meter=$amis->writeEnergyRegistertoArray($MeterConfig,$debug);		/* alle Energieregister in ein Array schreiben, Parameter : Config, debug */
            $ergebnistab_energie.=$amis->writeEnergyRegisterTabletoString($Meter,false);	/* output with no html encoding */	
            $ergebnistab_energie.="\n\n";					
            $ergebnistab_energie.=$amis->writeEnergyRegisterValuestoString($Meter,false);	/* output with no html encoding */	
            $ergebnistab_energie.="\n\n";					
            $ergebnistab_energie.=$amis->writeEnergyPeriodesTabletoString($Meter,false,true);	/* output with no html encoding, values in kwh */
            $ergebnistab_energie.="\n\n";
            $ergebnistab_energie.=$amis->writeEnergyPeriodesTabletoString($Meter,false,false);	/* output with no html encoding, values in EUR */
            $ergebnistab_energie.="\n\n";
            }
        return $ergebnistab_energie;            
        }
	}

?>