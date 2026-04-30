<?php

/****************************************************************************************
*
* Autosteuerung, Spezialroutinen für Stromheizung/Heatcontrol
* immer um 00:00:10 aufgerufen
*
*
*
*******************************************************************************************/

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
    IPSUtils_Include ("Autosteuerung_Class.inc.php","IPSLibrary::app::modules::Autosteuerung");


	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
		}
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdHeatControl    = IPS_GetScriptIDByName('Autosteuerung_HeatControl', $CategoryIdApp);

    $timerOps = new timerOps();
    $tim4ID  = @IPS_GetEventIDByName("Weathertimer", $scriptIdHeatControl);

    $debug=false;           // mit Execute wird es true
    $AutoSetSwitches = Autosteuerung_SetSwitches();

    if (isset($AutoSetSwitches["Stromheizung"])===false)
        {
        echo "AutoSetSwitches \"Stromheizung\" nicht aktiviert. Siehe Autosteuerung Config.\n";  
        $debug=true;          
        }
    if (isset($AutoSetSwitches["Measurement"])===false)
        {
        echo "AutoSetSwitches \"Measurement\" nicht aktiviert. Siehe Autosteuerung Config.\n";            
        $debug=true;          
        }
    if ($debug)       print_r($AutoSetSwitches);

        /********************************************************************************************
        *
        * Activity Script für die HeatControl
        *
        *  übernimmt das Setzen von Variablen
        *  verschiebt einmal am Tag den Wochenplan für den Einsatz der Heizung
        *
        **********************************************/

        Switch ($_IPS['SENDER'])
            {
            Case "WebFront":
                //echo "Select";
                /* vom Webfront aus gestartet */
                $variableID=$_IPS['VARIABLE'];
                if (isset($AutoSetSwitches["Stromheizung"]))
                    {  
                    $kalender=new AutosteuerungStromheizung();      // Default nedeutet $logfile="No-Output",$nachrichteninput_Id="Ohne"
                    $oid=$kalender->getAutoFillID();
                    switch ($variableID)
                        {
                        case $oid:
                            //echo "SetAutoFill $oid ".$_IPS['VALUE']."  \n";
                            $kalender->setAutoFill($_IPS['VALUE']);
                            break;
                        default:
                            SetValue($variableID,$_IPS['VALUE']);
                            break;
                        }    
                    }
                break;
            Case "Execute":
                echo "------------------------------------\n";
                echo "Execute, called from Console:\n";
                echo "------------------------------------\n";
                echo "Check Correct Place for log Files:\n";
                $logging = new Logging();	
                $log_ConfigFile = $logging -> get_IPSComponentLoggerConfig();
                print_R($log_ConfigFile);
                echo "Aktivierte Funktionen (SetSwitches):\n";
                $AutoSetSwitches = Autosteuerung_SetSwitches();
                print_r($AutoSetSwitches); 
                $debug=true;
                    if (isset($AutoSetSwitches["Stromheizung"]))
                        {
                        $kalender=new AutosteuerungStromheizung();      // Default nedeutet $logfile="No-Output",$nachrichteninput_Id="Ohne"              
                        $auto = new Autosteuerung();
                        $oid=$kalender->getAutoFillID();
                        if ($debug) echo "Autosteuerung Heatcontrol vom Timer oder Execute aufgerufen:    ".IPS_GetName($oid)." ($oid)\n";
                        if ($oid)
                            {
                            $configuration = $kalender->get_Configuration();
                            if ($debug) print_r($configuration); 
                            /*  $kalender->InitLogMessage(true);            // noch einmal Interesse halber anschauen
                            $kalender->getStatus();
                            
                            $zeile1=$kalender->getZeile1ID();		// OID von Zeile1, aktueller Status
                            echo "Execute vom script aufgerufen (AutoFill:$oid  Zeile1:$zeile1):\n";   */

                            $value=$kalender->getStatusfromProfile(GetValue($oid));                 // value kann auch false sein
                            if ($debug) echo "   getStatusfromProfile(".GetValueIfFormatted($oid).") = ".($value?"true":"false")." \n";
                            //if ($value)           // keine Abfrage, value ist der nächste Wert
                                {
                                $kalender->ShiftforNextDay($value);                                     /* die Werte im Wochenplan durchschieben, neuer Wert ist der Parameter, die Links heissen aber immer noch gleich */
                                $kalender->UpdateLinks($kalender->getWochenplanID());                   /* Update Links für Administrator Webfront */
                                $kalender->UpdateLinks($kalender->getCategoryIdTab());		                            /* Update Links for Mobility Webfront */

                                if ($configuration["HeatControl"]["SwitchName"] != Null)
                                    {
                                    $result = $auto->isitheatday($debug);             // true für Debug
                                    $conf=array();
                                    $conf["TYP"]=$configuration["HeatControl"]["Type"];
                                    $conf["MODULE"]=$configuration["HeatControl"]["Module"];
                                    $conf["NAME"]=$configuration["HeatControl"]["SwitchName"];
                                    $auto->switchByTypeModule($conf,$result,$debug);              // true für Debug
                                    if ($result)
                                        {
                                        $conf["NAME"]=$configuration["HeatControl"]["SwitchName"]."#Temp";
                                        $value = $configuration["HeatControl"]["setTemp"];
                                        $auto->switchByTypeModule($conf,$value,$debug);              // true für Debug
                                        }
                                    //print_r($auto->getFunctions());
                                    }
                                }
                            }
                        }

                break;
            case "TimerEvent":
            	switch ($_IPS['EVENT'])
		            {
                    default:
                    if (isset($AutoSetSwitches["Stromheizung"]))
                        {
                        $kalender=new AutosteuerungStromheizung();      // Default nedeutet $logfile="No-Output",$nachrichteninput_Id="Ohne"              
                        $auto = new Autosteuerung();
                        $oid=$kalender->getAutoFillID();
                        if ($debug) echo "Autosteuerung Heatcontrol vom Timer oder Execute aufgerufen:    ".IPS_GetName($oid)." ($oid)\n";
                        if ($oid)
                            {
                            $configuration = $kalender->get_Configuration();
                            if ($debug) print_r($configuration); 
                            /*  $kalender->InitLogMessage(true);            // noch einmal Interesse halber anschauen
                            $kalender->getStatus();
                            
                            $zeile1=$kalender->getZeile1ID();		// OID von Zeile1, aktueller Status
                            echo "Execute vom script aufgerufen (AutoFill:$oid  Zeile1:$zeile1):\n";   */

                            $value=$kalender->getStatusfromProfile(GetValue($oid));                 // value kann auch false sein
                            if ($debug) echo "   getStatusfromProfile(".GetValueIfFormatted($oid).") = ".($value?"true":"false")." \n";
                            //if ($value)           // keine Abfrage, value ist der nächste Wert
                                {
                                $kalender->ShiftforNextDay($value);                                     /* die Werte im Wochenplan durchschieben, neuer Wert ist der Parameter, die Links heissen aber immer noch gleich */
                                $kalender->UpdateLinks($kalender->getWochenplanID());                   /* Update Links für Administrator Webfront */
                                $kalender->UpdateLinks($kalender->getCategoryIdTab());		                            /* Update Links for Mobility Webfront */

                                if ($configuration["HeatControl"]["SwitchName"] != Null)
                                    {
                                    $result = $auto->isitheatday($debug);             // true für Debug
                                    $conf=array();
                                    $conf["TYP"]=$configuration["HeatControl"]["Type"];
                                    $conf["MODULE"]=$configuration["HeatControl"]["Module"];
                                    $conf["NAME"]=$configuration["HeatControl"]["SwitchName"];
                                    $auto->switchByTypeModule($conf,$result,$debug);              // true für Debug
                                    if ($result)
                                        {
                                        $conf["NAME"]=$configuration["HeatControl"]["SwitchName"]."#Temp";
                                        $value = $configuration["HeatControl"]["setTemp"];
                                        $auto->switchByTypeModule($conf,$value,$debug);              // true für Debug
                                        }
                                    //print_r($auto->getFunctions());
                                    }
                                }
                            }
                        }
                    break;
                    }
                break;	
            default:
                break;
            }																																																																
        

/*********************************************************************************************/


?>