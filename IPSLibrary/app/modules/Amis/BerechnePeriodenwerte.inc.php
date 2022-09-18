<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur Berechnung der Periodenwerte von Energieregistern
     * es werden Werte von AMIS Zählern als auch von Homematic Energiemessungen verwendet.
     * welche gibt die AMIS Config vor.
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
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

$display=false;       /* alle Eintraege auf der Console ausgeben */
//$display=true;

$Amis = new Amis();

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

$MeterConfig = $Amis->getMeterConfig();

$Tag=1;
$Monat=1;
$Jahr=2011;
//$variableID=30163;

$ipsOps = new ipsOps();

foreach ($MeterConfig as $meter)
	{
	echo"-------------------------------------------------------------\n";
    $ID = CreateVariableByName($CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
    echo "Create Variableset for : ".$meter["NAME"]." vom Typ ".$meter["TYPE"]." in $ID (".$ipsOps->path($ID).")\n";   
    $variableID = $Amis->getWirkenergieID($meter); 
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
        $starttime=$endtime-60*60*24*1;
        echo "Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
        echo "Variable: ".IPS_GetName($variableID)."     ($variableID)\n";

        //$ergebnis=summestartende2($starttime, $endtime, true,false,$archiveHandlerID,$variableID,$display);
        //echo "Ergebnis (alt) Wert letzter Tag : ".$ergebnis."kWh \n";
        $ergebnis=$Amis->summestartende($starttime, $endtime, true,false, $variableID,$display);
        echo "Ergebnis Wert     letzter Tag : ".number_format($ergebnis,3,",",".")."kWh \n";
        SetValue($letzterTagID,$ergebnis);
        SetValue($letzterTagEurID,$ergebnis*GetValue($KostenID));

        $starttime=$endtime-60*60*24*7;
        //$ergebnis=summestartende2($starttime, $endtime, true, false, $archiveHandlerID, $variableID, $display);
        //echo "Ergebnis (alt) Wert letzte 7 Tage : ".$ergebnis."kWh \n";
        $ergebnis=$Amis->summestartende($starttime, $endtime, true, false, $variableID, true);
        echo "Ergebnis Wert letzte   7 Tage : ".number_format($ergebnis,3,",",".")."kWh \n";
        SetValue($letzte7TageID,$ergebnis);
        SetValue($letzte7TageEurID,$ergebnis*GetValue($KostenID));

        $starttime=$endtime-60*60*24*30;
        //$ergebnis=summestartende2($starttime, $endtime, true, false,$archiveHandlerID,$variableID,$display);
        //echo "Ergebnis (alt) Wert letzte 30 Tage : ".$ergebnis."kWh \n";
        $ergebnis=$Amis->summestartende($starttime, $endtime, true, false, $variableID,$display);
        echo "Ergebnis Wert letzte  30 Tage : ".number_format($ergebnis,3,",",".")."kWh \n";
        SetValue($letzte30TageID,$ergebnis);
        SetValue($letzte30TageEurID,$ergebnis*GetValue($KostenID));

        $starttime=$endtime-60*60*24*360;
        //$ergebnis=summestartende2($starttime, $endtime, true, false,$archiveHandlerID,$variableID,$display);
        //echo "Ergebnis letzte 360 Tage von $starttime zu $endtime berechnen:\n";
        $ergebnis=$Amis->summestartende($starttime, $endtime, true, false, $variableID,$display);
        echo "Ergebnis Wert letzte 360 Tage : ".number_format($ergebnis,3,",",".")."kWh \n";
        SetValue($letzte360TageID,$ergebnis);
        SetValue($letzte360TageEurID,$ergebnis*GetValue($KostenID));
        }
    else echo "VariableID wurde nicht gefunden\n";
   	}

if ($_IPS['SENDER'] == "Execute")
	{
	echo "-------------------------------------------------------------------------------------------\n";
	echo "        EXECUTE\n";
	echo "-------------------------------------------------------------------------------------------\n";

	echo "\nIPS aktuelle Kernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Amis');       /*   <--- change here */
	echo "\nAmis Modul Version : ".$ergebnis."\n"; 

    echo "\n";
    $count=sizeof($MeterConfig);
	echo "Plausi-Check von Logged Variablen. Im AMIS Configfile sind $count Geräte angelegt.\n";

	$endtime=mktime(0,0,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
	//$endtime=mktime(0,0,0,3 /* Monat */, 1/* Tag */, date("Y", $jetzt));
	$endtime=time();

	$starttime=$endtime-60*60*24*7;
	$starttime=$endtime-60*60*24;
	//$starttime=mktime(0,0,0,2 /* Monat */, 1/* Tag */, date("Y", $jetzt));

	


	foreach ($MeterConfig as $meter)
		{
		echo"-------------------------------------------------------------\n";
		$ID = IPS_GetObjectIdByName($meter["NAME"],$CategoryIdData);   /* 0 Boolean 1 Integer 2 Float 3 String */
        echo "Get Variableset for : ".$meter["NAME"]." vom Typ ".$meter["TYPE"]." in $ID (".$ipsOps->path($ID).")\n";   
        $variableID = $Amis->getWirkenergieID($meter); 
		$Amis->getArchiveData($variableID, $starttime, $endtime);

        $variableID = $Amis->getZaehlervariablenID($meter, "Strom L1"); 
        if ($variableID !== false)
            {
            echo "WirkenergieID $variableID (".$ipsOps->path($variableID).")\n";  	
            $Amis->getArchiveData($variableID, $starttime, $endtime, "A");  
            }

        if (false)
            {
            $display=true;
            $delete=false;          // damit werden geloggte Werte gelöscht

            $initial=true;
            $ergebnis=0;
            $vorigertag="";
            $disp_vorigertag="";
            $neuwert=0;


            $vorwert=0;
            $zaehler=0;
            //$variableID=44113;

            echo "ArchiveHandler: ".$archiveHandlerID." Variable: $variableID (".$ipsOps->path($variableID).")\n";
            echo "Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
            
            $increment=1;
            //echo "Increment :".$increment."\n";
            $gepldauer=($endtime-$starttime)/24/60/60;
            do {
                /* es könnten mehr als 10.000 Werte sein
                    Abfrage generisch lassen
                */

                $werte = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime, $endtime, 0);
                /* Dieser Teil erstellt eine Ausgabe im Skriptfenster mit den abgefragten Werten
                    Nicht mer als 10.000 Werte ...
                */
                //print_r($werte);
                $anzahl=count($werte);
                echo "   Variable: ".IPS_GetName($variableID)." mit ".$anzahl." Werte. \n";

                if (($anzahl == 0) & ($zaehler == 0)) 
                    {
                    echo " Keine Werte archiviert. \n";
                    break;
                    }   // hartes Ende der Schleife wenn keine Werte vorhanden

                if ($initial)
                    {
                    /* allererster Durchlauf */
                    $ersterwert=$werte['0']['Value'];
                    $ersterzeit=$werte['0']['TimeStamp'];
                    }

                if ($anzahl<10000)
                    {
                    /* letzter Durchlauf */
                    $letzterwert=$werte[sprintf('%d',$anzahl-1)]['Value'];
                    $letzterzeit=$werte[sprintf('%d',$anzahl-1)]['TimeStamp'];
                    //echo "   Erster Wert : ".$werte[sprintf('%d',$anzahl-1)]['Value']." vom ".date("D d.m.Y H:i:s",$werte[sprintf('%d',$anzahl-1)]['TimeStamp']).
                    //     " Letzter Wert: ".$werte['0']['Value']." vom ".date("D d.m.Y H:i:s",$werte['0']['TimeStamp'])." \n";
                    }

                $initial=true;

                foreach($werte as $wert)
                    {
                    $zeit=$wert['TimeStamp'];
                    $tag=date("d.m.Y", $zeit);
                    $aktwert=(float)$wert['Value'];

                    if ($initial)
                        {
                        //print_r($wert);
                        $initial=false;
                        $vorwert=$aktwert;
                        echo "   Initial Startzeitpunkt:".date("d.m.Y H:i:s", $wert['TimeStamp'])."\n";
                        }
                    $vorwertCalc=$vorwert;                    
                    if (($aktwert>$vorwert) or ($aktwert==0) or ($aktwert<0))
                        {
                        if ($delete==true)
                            {
                            AC_DeleteVariableData($archiveHandlerID, $variableID, $zeit, $zeit);
                            }
                        echo "****".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." ergibt in Summe         : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
                        }
                    else
                        {
                        $vorwert=$aktwert;
                        }
                    if ($tag!=$vorigertag)
                        { /* neuer Tag */
                        $altwert=$neuwert;
                        $neuwert=$aktwert;
                        switch ($increment)
                            {
                            case 1:
                                    $ergebnis=$aktwert;
                            break;
                            case 2:
                                if ($altwert<$neuwert)
                                    {
                                        $ergebnis+=($neuwert-$altwert);
                                        }
                                    else
                                    {
                                        //$ergebnis+=($altwert-$neuwert);
                                        //$ergebnis=$aktwert;
                                        }
                                    break;
                                case 0:
                                    $ergebnis+=$aktwert;
                            break;
                            default:
                            }
                    $vorigertag=$tag;
                    }

                    if ($display==true)
                        {
                        /* jeden Eintrag ausgeben */
                        //print_r($wert);
                        if ($vorwertCalc != $aktwert)
                            {
                            echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "")."   ".number_format(($vorwertCalc-$aktwert)*4, 3, ".", "")." ergibt in Summe (Tageswert) : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
                            }
                        else echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "")."         ergibt in Summe (Tageswert) : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;

                        }
                    $zaehler+=1;
                    }
                    
                    //$endtime=$zeit;
                } while (count($werte)==10000);
            }    // ende if false
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
        $MeterValues=$amis->writeEnergyRegistertoArray($MeterConfig,true);                             // erstellen der Werte für die Anzeige in der Tabelle, true für Debug
        print_R($MeterValues);
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