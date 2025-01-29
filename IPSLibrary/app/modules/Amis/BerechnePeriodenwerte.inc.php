<?php

    /*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur Berechnung der Periodenwerte von Energieregistern
     * es werden Werte von AMIS Zählern als auch von Homematic Energiemessungen verwendet.
     * mittlerweile auch einfache Register und Werte aus einem Smart Meter Webportal
     * welche gibt die AMIS Config vor.
     *
     * diese Routine wird nur einmal am Tag um 1:45 aufgerufen, für einen 15min aktuellen 24 Stundenwert muss ein anderes Script verwendet werden
     * berechnet Tages, Wochen, Monats und Jahreswert, gibt die Datenqualität an
     * dafür verwendet summstartende, debug Tiefe kann man am Anfang setzen
     *
     *
     * Homematic und Co haben Energiezählregister mit aufsteigenden Werten, Speicherung im Archiv als Zähler. Das hat Einfluss auf die Aggregation bei der Visualisierung
	 *
     * Smart Meter Webportalwerte haben keine Energieregister als Zähler sondern Einzelverbrauchs- oder Leistungsmittelwerte
     * die Werte am Webportal stehen erst ab Mittag für den Vortag zur Verfügung. Aus Leistungsmittelwerten sind zusätzlich Tageswerte zu ermitteln
     *
     * Zusätzlich gibt es die Möglichkeit csv Dateien einzulesen, abhängig vom Format aus unterschiedlichen Source Verzeichnissen
     * Diese 15min Werte kann man in einen Counter umwandeln, erfordert aber einen Ausgangswert, sicherstellen das zumindest der älteste Zählerwert eingetragen ist
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
     */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
    IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');

    /************************************************************

                    INIT

    *************************************************************/

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
    $installedModules = $moduleManager->GetInstalledModules();

    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(100);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme

    $display=false;         // true alle Eintraege auf der Console ausgeben 
    $execute=true;         // false keine on execute Berechnungen

    $Amis = new Amis();         // true globales Debug

    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

    $MeterConfig = $Amis->getMeterConfig();

    $debug=false; // kein SummestartEnde Debug, Wert kann sein: false, true/1,2,3 ...
    $Tag=1;
    $Monat=1;
    $Jahr=2011;
    //$variableID=30163;

    $ipsOps = new ipsOps();
    $debug2=$debug;         //debug diese Funktion
    foreach ($MeterConfig as $meter)
        {
        echo"-------------------------------------------------------------\n";
        $ID = CreateVariableByName($CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
        echo "Create Variableset for : ".$meter["NAME"]." vom Typ ".$meter["TYPE"]." in Kategorie $ID (".$ipsOps->path($ID).")\n";   
        $variableID = $Amis->getWirkenergieID($meter);                      // sucht das register das Wirkenergie heisst
        if ($variableID)
            {
            $PeriodenwerteID = CreateVariableByName($ID, "Periodenwerte", 3);
            $KostenID = CreateVariableByName($ID, "Kosten kWh", 2);
            if ( isset($meter["costKWh"]) )
                {
                SetValue($KostenID,$meter["costKWh"]);
                }

            $letzterTagID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzterTag", 2,'kWh',null,100);                         // null keinen identifier schreiben, geht auch ""
            $letzte7TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte7Tage", 2,'kWh',null,110);
            $letzte30TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte30Tage", 2,'kWh',null,120);
            $letzte360TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte360Tage", 2,'kWh',null,130);

            $letzterTagEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzterTag", 2,'Euro',null,200);
            $letzte7TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte7Tage", 2,'Euro',null,210);
            $letzte30TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte30Tage", 2,'Euro',null,220);
            $letzte360TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte360Tage", 2,'Euro',null,230);         

            $vorwert=0;
            $zaehler=0;
            $jetzt=time();

            $endtime=mktime(0,0,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));     // mktime(hour,minute,second,month,day,year)

            /* es werden Wirkenergie Werte berücksichtigt. Typ DailyLPRead hat nur Leistungswerte im 15 Min Intervall
            */

            //$ergebnis=summestartende2($starttime, $endtime, true,false,$archiveHandlerID,$variableID,$display);
            //echo "Ergebnis (alt) Wert letzter Tag : ".$ergebnis."kWh \n";
            switch (strtoupper($meter["TYPE"]))
                {
                case "DAILYLPREAD":
                    echo "-----aggregate 15min Power Intervall data to Energy\n";
                    $config = ["StartTime" => strtotime("-120days"),"Update" => true];            // die letzten 120 Tage nachbearbeiten    
                    $Amis->aggregate15minPower2Energy($meter,$config,true);           // true update values false no debug
                    echo "||----\n";
                case "DAILYREAD":
                    $endtime=$endtime-60*60*24*1;               // Werte erst mit einem Tag Verzögerung erhalten, werden während eines Tages aus dem Smart Meter ausgelesen
                    break;
                default:
                    break;
                }
            echo "summestartende aufrufen für einen Tag, 7 Tage, 30 Tage und 360 Tage. Startdatum ist ".date("d.m.Y H:i:s",$endtime)."\n";
            $starttime=$endtime-60*60*24*1;
            echo "Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
            echo "Variable: ".IPS_GetName($variableID)."     ($variableID)\n";
            $ergebnis=$Amis->summestartende($starttime, $endtime, true,1, $variableID,$debug2);            // statt true eigentlich 0,1,2
            $result=$Amis->getFunctionResult();
            echo "Ergebnis Wert     letzter Tag : ".str_pad(number_format($ergebnis,3,",",".")."kWh",45)."  ".$result["Count"]." Werte vor Aggregation auf Tageswerte berücksichtigt, ".$result["DailyValues"]." Tageswerte erzeugt. \n";
            SetValue($letzterTagID,$ergebnis);
            SetValue($letzterTagEurID,$ergebnis*GetValue($KostenID));

            $starttime=$endtime-60*60*24*7;
            //$ergebnis=summestartende2($starttime, $endtime, true, false, $archiveHandlerID, $variableID, $display);
            //echo "Ergebnis (alt) Wert letzte 7 Tage : ".$ergebnis."kWh \n";
            $ergebnis=$Amis->summestartende($starttime, $endtime, true, 7, $variableID, $debug2);
            $result=$Amis->getFunctionResult();
            echo "Ergebnis Wert letzte   7 Tage : ".str_pad((number_format($ergebnis,3,",",".")." kWh"),20)."  ".str_pad(nf($ergebnis/7,"kWh")." pro Tag",25).$result["Count"]." Werte vor Aggregation auf Tageswerte berücksichtigt, ".$result["DailyValues"]." Tageswerte erzeugt.\n";
            SetValue($letzte7TageID,$ergebnis);
            SetValue($letzte7TageEurID,$ergebnis*GetValue($KostenID));

            $starttime=$endtime-60*60*24*30;
            //$ergebnis=summestartende2($starttime, $endtime, true, false,$archiveHandlerID,$variableID,$display);
            //echo "Ergebnis (alt) Wert letzte 30 Tage : ".$ergebnis."kWh \n";
            $ergebnis=$Amis->summestartende($starttime, $endtime, true, 30, $variableID,$debug2);
            $result=$Amis->getFunctionResult();
            echo "Ergebnis Wert letzte  30 Tage : ".str_pad((number_format($ergebnis,3,",",".")." kWh"),20)."  ".str_pad(nf($ergebnis/30,"kWh")." pro Tag",25).$result["Count"]." Werte vor Aggregation auf Tageswerte berücksichtigt, ".$result["DailyValues"]." Tageswerte erzeugt.\n";
            SetValue($letzte30TageID,$ergebnis);
            SetValue($letzte30TageEurID,$ergebnis*GetValue($KostenID));

            $starttime=$endtime-60*60*24*360;
            //$ergebnis=summestartende2($starttime, $endtime, true, false,$archiveHandlerID,$variableID,$display);
            //echo "Ergebnis letzte 360 Tage von $starttime zu $endtime berechnen:\n";
            $ergebnis=$Amis->summestartende($starttime, $endtime, true, 360, $variableID,$debug2);
            $result=$Amis->getFunctionResult();
            echo "Ergebnis Wert letzte 360 Tage : ".str_pad((number_format($ergebnis,3,",",".")."kWh"),20)."  ".str_pad(nf($ergebnis/360,"kWh")." pro Tag",25).$result["Count"]." Werte vor Aggregation auf Tageswerte berücksichtigt, ".$result["DailyValues"]." Tageswerte erzeugt.\n";
            SetValue($letzte360TageID,$ergebnis);
            SetValue($letzte360TageEurID,$ergebnis*GetValue($KostenID));
            }
        else echo "VariableID wurde nicht gefunden\n";
        }

    if ( ($_IPS['SENDER'] == "Execute") && $execute)
        {
        echo "\n\n\n";
        echo "-------------------------------------------------------------------------------------------\n";
        echo "        EXECUTE\n";
        echo "-------------------------------------------------------------------------------------------\n";

        echo "\nIPS aktuelle Kernelversion : ".IPS_GetKernelVersion();
        $ergebnis=$moduleManager->VersionHandler()->GetVersion('Amis');       /*   <--- change here */
        echo "\nAmis Modul Version : ".$ergebnis."\n"; 

        echo "\n";
        $count=sizeof($MeterConfig);
        echo "Plausi-Check von Logged Variablen. Im AMIS Configfile sind $count Geräte angelegt. Der Reihe nach durchgehen :\n";

        $endtime=mktime(0,0,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
        //$endtime=mktime(0,0,0,3 /* Monat */, 1/* Tag */, date("Y", $jetzt));
        $endtime=time();

        //$starttime=$endtime-60*60*24*7;
        $starttime=$endtime-60*60*24;
        //$starttime=mktime(0,0,0,2 /* Monat */, 1/* Tag */, date("Y", $jetzt));

        foreach ($MeterConfig as $meter)
            {
            echo"-------------------------------------------------------------\n";
            $ID = IPS_GetObjectIdByName($meter["NAME"],$CategoryIdData);   /* 0 Boolean 1 Integer 2 Float 3 String */
            echo "Get Variableset for : ".$meter["NAME"]." vom Typ ".$meter["TYPE"]." in $ID (".$ipsOps->path($ID).")\n";   
            $variableID = $Amis->getWirkenergieID($meter); 
            $Amis->getArchiveData($variableID, $starttime, $endtime);                   // schneller Check ob Werte für Wirkenergie in den letzten 24 Stunden da sind, endtime ist jetzt und starttime minus duration

            $variableID = $Amis->getZaehlervariablenID($meter, "Strom L1");             // Pseudocheck für Strom L1 only, ist sowieso im normalfall false
            if ($variableID !== false)
                {
                echo "WirkenergieID $variableID (".$ipsOps->path($variableID).")\n";  	
                $Amis->getArchiveData($variableID, $starttime, $endtime, "A");                  // nur Ausgabe als echo, wenn überhaupt
                }
  
            switch (strtoupper($meter["TYPE"]))
                {
                case "DAILYLPREAD":
                case "DAILYREAD":
                    $meter["TYPE"]="DAILYLPREAD";                           // fake dailyread, es gibt zwar nur Tageswerte, change meter Type to Update from 15min Values
                    if (isset($meter["LeistungID"])===false) 
                        {
                        $inputID=$Amis->getWirkleistungID($meter,true);                //true for Debug
                        echo "Warning, setMeterConfig, OID Identifier must be provided for TYPE DAILYREAD of ".$meter["NAME"].". Found one by searching: $inputID\n";
                        } 
                    else $inputID=$meter["LeistungID"];
                    ini_set('memory_limit', '128M');                        // können grosse Dateien werden, default sind 32MB
                    echo "Update/Verify Daily values for : ".$meter["NAME"]." \n"; 
                    echo "-----aggregate 15min Power Intervall data to align with Energy and Energy Counter Register:\n";
                    $config = [ "StartTime"         => strtotime("-400days"),
                                "Update"            => true,
                                "InputId"           => $inputID,
                                "OutputID"          => $Amis->getWirkenergieID($meter),
                                "OutputCounterID"   => IPS_GetVariableIDByName("Wirkenergie", $ID),
                                ];            // die letzten 120 Tage nachbearbeiten    
                    $Amis->aggregate15minPower2Energy($meter,$config,true);           // true or config to update values false no debug
                    echo "||----\n";
                    break;
                default:
                    break;
                }                
            }       // ende foreach

        // für spezielle Archive ausprobieren
        //$Amis->getArchiveData(17449, $starttime, $endtime, "A");  
        //$Amis->getArchiveData(46020, $starttime, $endtime, "A"); 
        //$Amis->getArchiveData(33623, $starttime, $endtime, "A"); 

        if (isset($installedModules["OperationCenter"]))
            {
            IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
            IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
            IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

            $scriptId       = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
            $tim7ID         = @IPS_GetEventIDByName("FileStatus", $scriptId);
            $status         = IPS_GetEvent($tim7ID);
            //print_r($status);
            echo "========================================================================================================\n";
            echo "Berechnung Historie und Aktuelle Energie das letzte Mal gestartet am/um ".date("d.m.Y H:i:s",$status["LastRun"])."\n";        

            $subnet="10.255.255.255";
            $OperationCenter=new OperationCenter($subnet);

            $amis=new Amis();
            $MeterConfig = $amis->getMeterConfig();
            $dataOID=$amis->getAMISDataOids();
            $tableID = CreateVariableByName($dataOID, "Historie-Energie", 3);
            $regID = CreateVariableByName($dataOID, "Aktuelle-Energie", 3);
            $MeterValues=$amis->writeEnergyRegistertoArray($MeterConfig);                             // erstellen der Werte für die Anzeige in der Tabelle, true für Debug,oder 2,3,
            //print_R($MeterValues);
            echo "writeEnergyRegisterTabletoString:\n";
            SetValue($tableID,$amis->writeEnergyRegisterTabletoString($MeterValues));
            echo "writeEnergyRegisterValuestoString:\n";
            SetValue($regID,$amis->writeEnergyRegisterValuestoString($MeterValues));		
            echo "----------------------------\n";
            echo GetValue($tableID);
            echo GetValue($regID);

            }


        }           // Ende Execute


	   
?>