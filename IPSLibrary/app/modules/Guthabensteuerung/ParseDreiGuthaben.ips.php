<?php
	/*
     * This file is part of the IPSLibrary.
     *
     * The IPSLibrary is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published
     * by the Free Software Foundation, either version 3 of the License, orF
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
	 
	/**@defgroup ParseDreiGuthaben
	 * @{
	 *
     * Zwei Betriebsarten, iMacro und Selenium zur Auslesung von Webseiten:
     *
     * iMacro legt nette Files an als Ergebnis des Downloads der aktuell angesteuerten Homepage
     * Selenium liest einzelne Datenobjekte aus, speichert diese in einer IP Symcon Variablen zur weiteren Auswertung
     * Diese Programm kann man für zeitversetzte Aufrufe aus dem Webfront ebenfalls verwenden
     *
     * Bei Selenium wird diese Routine noch eingesetzt aber nicht mehr benötigt, der Aufruf der Funktionen zur Auflösung der Ergebnisse kann sofort erfolgen -> noch nicht implementiert !!!!
     * wird von Guthabensteuerung Timer früh am Morgen als Script nach der Abfrage der Drei Konten aufgerufen
     * 
     * Ergebnis für alle Hosts in der Konfiguration ermitteln, wenn nicht schon geschehen
     *
     * Input from CSV Files. It is also possible to provide data from csv Files
     *
     *
     *
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

    ini_set('memory_limit', '128M');                        // können grosse Dateien werden

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
    IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/******************************************************
 *
 *				INIT, find needed Modules
 *
 *************************************************************/

    $startexec=microtime(true);   
    $execute=true;                     // Execute extra code when called manually
    $debug=false;                       // false, nicht soviele Ausgaben, Übersichtlich halten

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
		}

	$installedModules   = $moduleManager->GetInstalledModules();
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	echo "Category Data ID           : ".$CategoryIdData."\n";
	echo "Category App ID            : ".$CategoryIdApp."\n";

    if (isset($installedModules["Amis"]))
        {
        IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
        IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');  
        $moduleManagerAmis = new IPSModuleManager('Amis',$repository);     /*   <--- change here */     
        $CategoryIdDataAmis     = $moduleManagerAmis->GetModuleCategoryID('data');
        }

/***************************************************************************** 
 *
 * Config einlesen
 *
 *********************************************************************************************/

    $guthabenHandler = new GuthabenHandler();                  //default keine Ausgabe, speichern
	$GuthabenConfig         = $guthabenHandler->getContractsConfiguration();            // get_GuthabenConfiguration();
	$GuthabenAllgConfig     = $guthabenHandler->getGuthabenConfiguration();                              //get_GuthabenAllgemeinConfig();

    $phoneID=$guthabenHandler->getPhoneNumberConfiguration();
    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {
        case "IMACRO":
            break;
        case "SELENIUM":        
            IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
            $seleniumOperations = new SeleniumOperations();        
            $guthabenHandler->extendPhoneNumberConfiguration($phoneID,$seleniumOperations->getCategory("DREI"));            // phoneID is return parameter, erweitert um  [LastUpdated] und [OID]   
            break;
        }

    $maxcount=count($phoneID);

/*********************************************************************************************
 * 
 * Logging aktivieren
 *
 *********************************************************************************************/
    $dosOps = new dosOps();
    $systemDir     = $dosOps->getWorkDirectory(); 
    echo "systemDir : $systemDir \n";           // systemDir : C:/Scripts/ 
    echo "Operating System : ".$dosOps->getOperatingSystem()."\n";

    $ipsOps = new ipsOps();    
    $archiveOps = new archiveOps();                              
    $archiveID = $archiveOps->getArchiveID();   


    $NachrichtenID      = $ipsOps->searchIDbyName("Nachricht",$CategoryIdData);
    $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);
    /* logging in einem File und in einem String am Webfront */
    $log_Guthabensteuerung=new Logging($systemDir."Guthabensteuerung/Log_Guthaben.csv",$NachrichtenInputID);
    $log_Guthabensteuerung->LogNachrichten("ParseDreiGuthaben called.");  

/*********************************************************************************************
 * 
 * Auswertung basierend auf MODE Selection, vollstaendig unabhängig programmiert
 *
 *********************************************************************************************/

	//print_r($GuthabenAllgConfig);
    $dataID      = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
    $guthabenid  = @IPS_GetObjectIDByName("Guthaben", $dataID);

    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {  
        case "IMACRO":
            echo "Guthabensteuerung OperatingMode ist IMACRO:\n";
            echo "Verzeichnis für Macros     : ".$GuthabenAllgConfig["MacroDirectory"]."\n";
            echo "Verzeichnis für Ergebnisse : ".$GuthabenAllgConfig["DownloadDirectory"]."\n\n";
            /* "C:/Users/Wolfgang/Documents/iMacros/Downloads/ */
            $ausgeben=false; $ergebnisse=false; $speichern=false;

            /* RUN iMACRO, Parse textfiles, die von iMacro generiert wurden     */
            $ergebnis="";
            foreach ($GuthabenConfig as $TelNummer)
                {
                //print_r($TelNummer);
                if ( ( (isset($TelNummer["STATUS"])) && (strtoupper($TelNummer["STATUS"]) == "ACTIVE" ) ) || ( (isset($TelNummer["Status"])) && (strtoupper($TelNummer["Status"]) == "ACTIVE" ) ) )
                    { 
                    $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
                    echo "parsetxtfile : ".$TelNummer["NUMMER"]."\n";
                    $phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
                    $ergebnis1=$guthabenHandler->parsetxtfile($TelNummer["NUMMER"]);
                    SetValue($phone1ID,$ergebnis1);
                    $ergebnis.=$ergebnis1."\n";
                    }
                else
                    {
                    }	
                }
            break;
        case "SELENIUM":
            echo "Guthabensteuerung OperatingMode ist SELENIUM:\n";
            $ergebnis="";
            $guthabenHandler->updateConfiguration(true,true,true);                     // $ausgeben,$ergebnisse,$speichern          gespeichert wird aber eh immer
            //print_R($phoneID);
            $config = $guthabenHandler->getSeleniumHostsConfig()["Hosts"];

            foreach ($config as $host => $entry)
                {
                echo "======================================================================";
                switch (strtoupper($host))
                    {
                    case "DREI":
                        echo "DREI ============\n";
                        foreach ($phoneID as $entry)
                            {
                            echo "--------------------------------------------------\n   ".$entry["Nummer"]."    : ";
                            if (isset($entry["OID"])) 
                                {
                                echo "register (".$entry["OID"].") available";
                                if (isset($entry["LastUpdated"])) 
                                    {
                                    echo ", last update was ".date("d.m.Y H:i:s",$entry["LastUpdated"]);                    
                                    echo "\n";
                                    $log_Guthabensteuerung->LogNachrichten("Parse Drei Guthaben ".$entry["Nummer"]." from ".date("d.m.Y H:i:s",$entry["LastUpdated"]).".");  
                                    $result=GetValue($entry["OID"]);
                                    //echo "$result\n";
                                    $lines = explode("\n",$result);
                                    $ergebnis1=$guthabenHandler->parsetxtfile($entry["Nummer"],$lines,false,"array",true);          // true für Debug                    
                                    }
                                else echo "\n";
                                }
                            else echo "\n";
                            }
                        //print_R($ergebnis);
                        echo "Berechnung Guthaben ist abgeschlossen.\n";
                        break;
                    case "EASY":
                        echo "EASY ============\n";
                        $seleniumEasycharts = new SeleniumEasycharts();
                        echo "Konfiguration von EASY gesucht:\n";
                        $depotRegister = $seleniumEasycharts->getDebotBooksfromConfig($guthabenHandler->getSeleniumTabsConfig("EASY"));                 //  Auswertung
                        foreach ($depotRegister as $depot)
                            {
                            if ($debug) echo "Depot ausgewählt: $depot  \n";
                            echo "--------------------------------\n";
                            echo "Selenium Operations, readResult from EASY on $depot: ";
                            $result=$seleniumOperations->readResult("EASY",$depot,$debug);                  // true Debug   
                            echo "Letztes Update ".date("D d.m.Y H:i:s",$result["LastChanged"])."\n";       
                            $log_Guthabensteuerung->LogNachrichten("Parse Eayschart Depot $depot Ergebnis from ".date("d.m.Y H:i:s",$result["LastChanged"]).".");  
                            $lines = explode("\n",$result["Value"]);    
                            $data=$seleniumEasycharts->parseResult($lines,false);             // einlesen, true debug
                            $shares=$seleniumEasycharts->evaluateResult($data,$debug);
                            $depotName=str_replace(" ","",$depot);                      // Blanks weg
                            if ($depotName != $depot)               
                                {
                                if ($debug) echo "--------\n";
                                $value=$seleniumEasycharts->evaluateValue($shares, $debug);         // Summe ausrechnen, nicht mehr relevant, da Depotzusammenstellung in actualDepot, aber es wird Musterdepot3 als eigene Kurve geführt
                                $seleniumEasycharts->writeResult($shares,"Depot".$depotName,$value);                         // die ermittelten Werte abspeichern, shares Array etwas erweitern                                
                                }
                            else
                                {
                                $seleniumEasycharts->writeResult($shares,"Depot".$depotName);                         // die ermittelten Werte abspeichern, shares Array etwas erweitern
                                }
                            $seleniumEasycharts->updateResultConfigurationSplit($shares);                           // es kann sein dass es Splits für Aktien gibt
                            $seleniumEasycharts->writeResultConfiguration($shares, $depotName);                                    
                            }
                        echo "--------------------------------\n";                            
                        // Split in allen Depots eintragen, immer machen, es könnte sich ja etwas geändert haben
                        $allDepotRegisters=$seleniumEasycharts->showDepotConfigurations(false,false);           // true für Debug
                        foreach ($allDepotRegisters as $result)       // es können auch mehrere sein
                            {
                            if (isset($result["Depot"]))
                                {
                                $shares = $seleniumEasycharts->getResultConfiguration($result["Depot"]["Name"]);
                                $seleniumEasycharts->updateResultConfigurationSplit($shares);                           // es kann sein dass es Splits für Aktien gibt
                                $seleniumEasycharts->writeResultConfiguration($shares,$result["Depot"]["Name"]);
                                }
                            }
                        echo "Aktuell vergangene Zeit : ".exectime($startexec)." Sekunden.\n";          
                        break;
                    case "LOGWIEN":
                        echo "LOGWIEN ============\n";        
                        $targetID = $seleniumOperations->defineTargetID($host,$entry["CONFIG"]);            
                        echo "parse LogWien Ergebnis in Result and archive in $targetID.\n";
                        $result=$seleniumOperations->readResult("LogWien","Result",true);                  // true Debug
                        //print_R($result);
                        echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";
                        $log_Guthabensteuerung->LogNachrichten("Parse Log.Wien Ergebnis from ".date("d.m.Y H:i:s",$result["LastChanged"]).".");  
                        echo "--------\n";
                        //$checkArchive=$archiveOps->getComponentValues($oid,20,false);                 // true mit Debug
                        $seleniumLogWien = new SeleniumLogWien();
                        $ergebnis = $seleniumLogWien->writeEnergyValue($result["Value"],$entry["CONFIG"]["ResultTarget"]);
                        //echo $ergebnis["Value"]." (".$ergebnis["OID"].")\n";
                        $config=array();
                        $config["manAggregate"]=false;
                        $config["maxDistance"]=false;
                        $archiveOps->getValues($ergebnis["OID"],$config,1);                                 // nicht nur ein get in das interne Array, es folgt auch eine Analyse der Werte
                        $config["ShowTable"]=array();                           // input ist result.oid
                        $config["ShowTable"]["align"]="daily";
                        $config["ShowTable"]["adjust"]=["EnergyProfile"=>"+2 days"];
                        echo "\n";
                        $result=$archiveOps->showValues(false,$config);             // das interne Result nehmen, config berücksichtigen                     
                        // do some manual cleanup
                        //AC_DeleteVariableData ($archiveID, $ergebnis["OID"],strtotime("13.05.2018 00:00"),strtotime("14.05.2018 00:00"));          // $start>$end    
                        break;
                    case "YAHOOFIN":
                        echo "YAHOOFIN ============\n";                    
                        echo "parse YahooFin Ergebnis in Result.\n";
                        $result=$seleniumOperations->readResult("YAHOOFIN");                  // true Debug   , lest RESULT als Egebnis Variable, wenn zweite Variable ausgefüllt ist es das entsprechende register
                        echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";       
                        $log_Guthabensteuerung->LogNachrichten("Parse YahooFin Ergebnis from ".date("d.m.Y H:i:s",$result["LastChanged"]).".");  
                        $yahoofin = new SeleniumYahooFin();
                        $ergebnis = $yahoofin->parseResult($result);                        // eigentlich nur json_decode auf ein array
                        $yahoofin->writeResult($ergebnis,"TargetValue",true);
                        break;
                    case "EVN":
                        echo "EVN ============\n";                    
                        echo "parse EVN Ergebnis in Result.\n"; 
                        $targetID = $seleniumOperations->defineTargetID($host,$entry["CONFIG"]);            
                        echo "parse LogWien Ergebnis in Result and archive in $targetID.\n";
                        /*$LeistungID = false;     
                        if (isset($entry["CONFIG"]["ResultTarget"]))
                            {
                            if (isset($entry["CONFIG"]["ResultTarget"]["OID"])) $LeistungID = $entry["CONFIG"]["ResultTarget"]["OID"];
                            }
                        if ($LeistungID === false) 
                            {
                            if (isset($installedModules["Amis"])===false) echo "unknown Module.\n";
                            else    
                                {
                                // ********************* noch nicht richtig implmentiert, sucht nicht sondern definiert Test-BKS01 
                                $ID = CreateVariableByName($CategoryIdDataAmis, "Test-BKS01", 3);           // Name neue Variablen
                                SetValue($ID,"nur testweise den EVN Smart Meter auslesen und speichern");
                                $LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   
                                }
                            }
                        if ($LeistungID)
                            {
                            echo "Archivierte Werte erfassen, bearbeiten und speichern in $LeistungID (".$ipsOps->path($LeistungID).") :\n";
                            if (AC_GetLoggingStatus($archiveID, $LeistungID)==false) 
                                {
                                echo "Werte wird noch nicht im Archive gelogged. Jetzt als Logging konfigurieren. \n";
                                AC_SetLoggingStatus($archiveID,$LeistungID,true);           // eine geloggte Variable machen
                                } */
                        if ($targetID)
                            {                                
                            //echo "Variable mit Archive ist hier : $LeistungID \n";
                            $result=$seleniumOperations->readResult("EVN","Result",true); 
                            echo "Letztes Update  von Selenium  am ".date("d.m.Y H:i:s",$result["LastChanged"])." erfolgt.\n";       
                            $log_Guthabensteuerung->LogNachrichten("Parse EVN Ergebnis from ".date("d.m.Y H:i:s",$result["LastChanged"]).".");  
                            $evn = new SeleniumEVN();
                            $werte = $evn->parseResult($result); 
                            $knownTimeStamps = $evn->getKnownData($targetID);
                            $input = $evn->filterNewData($werte,$knownTimeStamps);
                            echo "Add Logged Values: ".count($input["Add"])."\n";
                            $status=AC_AddLoggedValues($archiveID,$targetID,$input["Add"]);
                            echo "Erfolgreich : $status \n";
                            AC_ReAggregateVariable($archiveID,$targetID);                    
                            }
                        break;
                    case "ORF":
                        echo "ORF ============\n";                    
                        echo "parse ORF Ergebnis in Result.\n"; 
                        print_R($installedModules);
                        if (isset($installedModules["Startpage"]))
                            {
                            IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
                            IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
                            IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');                                
                            $moduleManagerSP    = new IPSModuleManager('Startpage',$repository);
                            $CategoryIdDataSP   = $moduleManagerSP->GetModuleCategoryID('data');
                            $categoryId_OrfWeather = IPS_GetObjectIDByName('OrfWeather',$CategoryIdDataSP); 
                            $variableIdOrfText   = IPS_GetObjectIDByName("OrfWeatherReportHTML",$categoryId_OrfWeather);        
                            $result=$seleniumOperations->readResult("ORF","Result");    
                            //print_r($result);
                            $text="<div>".$result["Value"]."<br>ORF Update from ".date("d.m.Y H:i",$result["LastChanged"])."</div>";
                            $text = str_replace("\n","<br>",$text);
                            SetValue($variableIdOrfText,$text); 
                            }
                        break;                        
                    default:
                        echo (strtoupper($host))."============\n";

                        break;    
                        
                    }           // ende switch
                }           // ende foreach
            break;
        default:
            break;        
        }


/******************************************************
*
*              Execute
*
*************************************************************/

    if ($execute && ($_IPS['SENDER']=="Execute"))
        {
        switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
            {  
            case "IMACRO":
                echo "Guthabensteuerung OperatingMode ist IMACRO:\n";
                echo "Verzeichnis für Macros     : ".$GuthabenAllgConfig["MacroDirectory"]."\n";
                echo "Verzeichnis für Ergebnisse : ".$GuthabenAllgConfig["DownloadDirectory"]."\n\n";
                /* "C:/Users/Wolfgang/Documents/iMacros/Downloads/ */
                $ausgeben=true; $ergebnisse=true; $speichern=false;
                echo "======================================IMACRO EXECUTE==================\n";
                echo "Execute, Script ParseDreiGuthaben wird ausgeführt:\n\n";
                echo "  Ausgabe Ergebnis parsetxtfile :\n";
                echo "  -------------------------------\n";
                echo $ergebnis;
                echo "  Ausgabe Status der aktiven SIM Karten :\n";
                echo "  ---------------------------------------\n";
                $ergebnis1="";
                foreach ($GuthabenConfig as $TelNummer)
                    {
                    $phone1ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"], $guthabenid);
                    $dateID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_Date", $phone1ID);
                    $ldateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_loadDate", $phone1ID);
                    $udateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_unchangedDate", $phone1ID);
                    $userID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_User", $phone1ID);
                    if (strtoupper($TelNummer["STATUS"])=="ACTIVE") 
                        {
                        $ergebnis1.="    ".$TelNummer["NUMMER"]."  ".str_pad(GetValue($userID),30)."  ".str_pad(GetValue($dateID),30)." ".str_pad(GetValue($udateID),30)." ".GetValue($ldateID)."\n";
                        }
                    //echo "Telnummer ".$TelNummer["NUMMER"]." ".$udateID."\n";
                    }
                echo "  Nummer                Name                                letztes File von       letzte Aenderung Guthaben    letzte Aufladung\n";
                echo $ergebnis1;
                //print_r($GuthabenConfig);

                echo "\n\nHistorie der Guthaben und verbrauchten Datenvolumen.\n";
                //$variableID=get_raincounterID();
                $endtime=time();
                $starttime=$endtime-60*60*24*2;  /* die letzten zwei Tage */
                $starttime2=$endtime-60*60*24*800;  /* die letzten 100 Tage */

                foreach ($GuthabenConfig as $TelNummer)
                    {
                    $phone1ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"], $guthabenid);
                    $dateID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_Date", $phone1ID);
                    $ldateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_loadDate", $phone1ID);
                    $udateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_unchangedDate", $phone1ID);
                    $userID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_User", $phone1ID);

                    $phone_Volume_ID     = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_Volume", $phone1ID);
                    $phone_User_ID       = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_User", $phone1ID);
                    $phone_VolumeCumm_ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_VolumeCumm", $phone1ID);
                    echo "\n".$TelNummer["NUMMER"]." ".GetValue($phone_User_ID)." : ".GetValue($phone_Volume_ID)."MB und kummuliert ".GetValue($phone_VolumeCumm_ID)."MB \n";
                    if (AC_GetLoggingStatus($archiveID, $phone_VolumeCumm_ID)==false)
                        {
                        echo "Werte wird noch nicht gelogged.\n";
                        }
                    else
                        {
                        $werteLogVolC = AC_GetLoggedValues($archiveID, $phone_VolumeCumm_ID, $starttime2, $endtime,0);
                        $werteLogVol = AC_GetLoggedValues($archiveID, $phone_Volume_ID, $starttime2, $endtime,0);
                        //$werteAggVol = AC_GetAggregatedValues($archiveID, $phone_Volume_ID, 1, $starttime2, $endtime,0); /* tägliche Aggregation */
                        $wertAlt=-1; $letzteZeile="";
                        foreach ($werteLogVol as $wert)
                            {
                            if ($wertAlt!=$wert["Value"])
                                {
                                echo $letzteZeile;
                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                //echo $letzteZeile;
                                $wertAlt=$wert["Value"];
                                }
                            else
                                {
                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                }
                            //echo $letzteZeile;
                            }
                        $phone_Cost_ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_Cost", $phone1ID);
                        $werteLogCost = AC_GetLoggedValues($archiveID, $phone_Cost_ID, $starttime2, $endtime,0);
                        echo "Logged Cost Vaules:\n";
                        $wertAlt=-1; $letzteZeile="";
                        foreach ($werteLogCost as $wert)
                            {
                            if ($wertAlt!=$wert["Value"])
                                {
                                echo $letzteZeile;
                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                //echo $letzteZeile;
                                $wertAlt=$wert["Value"];
                                }
                            else
                                {
                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                }
                            }
                        $phone_Load_ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"]."_Load", $phone1ID);
                        $werteLogLoad = AC_GetLoggedValues($archiveID, $phone_Load_ID, $starttime2, $endtime,0);
                        echo "Logged Load Vaules:\n";
                        $wertAlt=-1; $letzteZeile="";
                        foreach ($werteLogLoad as $wert)
                            {
                            if ($wertAlt!=$wert["Value"])
                                {
                                echo $letzteZeile;
                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                //echo $letzteZeile;
                                $wertAlt=$wert["Value"];
                                }
                            else
                                {
                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                }
                            }
                        }
                    }
                break;
            case "SELENIUM":
                echo "Guthabensteuerung OperatingMode ist SELENIUM:\n";
                $ergebnis="";
                $guthabenHandler->updateConfiguration(true,true,true);                     // $ausgeben,$ergebnisse,$speichern          gespeichert wird aber eh immer
                //print_R($phoneID);
                $config = $guthabenHandler->getSeleniumHostsConfig()["Hosts"];

                foreach ($config as $host => $entry)
                    {
                    echo "=================================================SELENIUM EXECUTE =====================";
                    switch (strtoupper($host))
                        {
                        case "DREI":
                            echo "DREI ============\n";
                            foreach ($phoneID as $entry)
                                {
                                echo "--------------------------------------------------\n   ".$entry["Nummer"]."    : ";
                                if (isset($entry["OID"])) 
                                    {
                                    echo "register (".$entry["OID"].") available";
                                    if (isset($entry["LastUpdated"])) 
                                        {
                                        echo ", last update was ".date("d.m.Y H:i:s",$entry["LastUpdated"]);                    
                                        echo "\n";
                                        $log_Guthabensteuerung->LogNachrichten("Parse Drei Guthaben ".$entry["Nummer"]." from ".date("d.m.Y H:i:s",$entry["LastUpdated"]).".");  
                                        $result=GetValue($entry["OID"]);
                                        //echo "$result\n";
                                        $lines = explode("\n",$result);
                                        $ergebnis1=$guthabenHandler->parsetxtfile($entry["Nummer"],$lines,false,"array",true);          // true für Debug                    
                                        }
                                    else echo "\n";
                                    }
                                else echo "\n";
                                }
                            print_R($ergebnis);

                            //print_r($GuthabenConfig);
                            echo "Alle Aktiven Simkarten neu parsen, Input sind die Dateien:\n";
                            foreach ($GuthabenConfig as $TelNummer)
                                {
                                //print_r($TelNummer);
                                /* Neue Schreibweise ist Status. STATUS kann bei iMacro noch vorkommen */
                                if ( (isset($TelNummer["STATUS"])) && (strtoupper($TelNummer["STATUS"]) == "ACTIVE" ) ) 
                                    { 
                                    echo "parsetxtfile : ".$TelNummer["NUMMER"]."\n";
                                    $phone1ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["NUMMER"], $guthabenid);
                                    $ergebnis1=$guthabenHandler->parsetxtfile($TelNummer["NUMMER"]);                    // Ausgabe Text
                                    //SetValue($phone1ID,$ergebnis1);
                                    $ergebnis.=$ergebnis1."\n";
                                    }
                                elseif ( (isset($TelNummer["Status"])) && (strtoupper($TelNummer["Status"]) == "ACTIVE" ) )         // Ergebis is strtoupper
                                    {
                                    echo "parsetxtfile : ".$TelNummer["Nummer"]."\n";
                                    $phone1ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"], $guthabenid);
                                    $ergebnis1=$guthabenHandler->parsetxtfile($TelNummer["Nummer"]);
                                    //SetValue($phone1ID,$ergebnis1);
                                    $ergebnis.=$ergebnis1."\n";
                                    }
                                else 
                                    {
                                    echo "keine Ahnung was hier vorgeht.\n";	
                                    print_r($TelNummer);
                                    }
                                }

                            echo "========================================================\n";
                            echo "Execute, Script ParseDreiGuthaben wird ausgeführt:\n";
                            echo "Operating Mode              : ".(strtoupper($GuthabenAllgConfig["OperatingMode"]))."\n";            
                            echo "  Ausgabe Ergebnis parsetxtfile :\n";
                            echo "  -------------------------------\n";
                            echo $ergebnis;
                            echo "  Ausgabe Status der aktiven SIM Karten :\n";
                            echo "  ---------------------------------------\n";
                            //if (false)
                                {
                                $ergebnis1="";
                                print_r($GuthabenConfig);
                                foreach ($GuthabenConfig as $TelNummer)
                                    {
                                    //print_r($TelNummer);
                                    $phone1ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"], $guthabenid);
                                    $dateID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_Date", $phone1ID);
                                    $ldateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_loadDate", $phone1ID);
                                    $udateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_unchangedDate", $phone1ID);
                                    $userID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_User", $phone1ID);
                                    echo "Check ".$TelNummer["Nummer"]." IDs: $guthabenid $phone1ID $dateID $ldateID $udateID $userID \n";
                                    if (strtoupper($TelNummer["Status"])=="ACTIVE") 
                                        {
                                        $ergebnis1.="    ".$TelNummer["Nummer"]."  ".str_pad(GetValue($userID),30)."  ".str_pad(GetValue($dateID),30)." ".str_pad(GetValue($udateID),30)." ".GetValue($ldateID)."\n";
                                        }
                                    //echo "Telnummer ".$TelNummer["NUMMER"]." ".$udateID."\n";
                                    }
                                echo "  Nummer                Name                                letztes File von       letzte Aenderung Guthaben    letzte Aufladung\n";
                                echo $ergebnis1;
                                                        $log_Guthabensteuerung->LogNachrichten($ergebnis1);

                                //print_r($GuthabenConfig);

                                echo "\n=================================================================================\n";
                                echo "Historie der Guthaben und verbrauchten Datenvolumen.\n";
                                //$variableID=get_raincounterID();
                                $endtime=time();
                                $starttime=$endtime-60*60*24*2;  /* die letzten zwei Tage */
                                $starttime2=$endtime-60*60*24*800;  /* die letzten 100 Tage */
                                foreach ($GuthabenConfig as $TelNummer)
                                    {
                                    $phone1ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"], $guthabenid);
                                    $dateID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_Date", $phone1ID);
                                    $ldateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_loadDate", $phone1ID);
                                    $udateID  = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_unchangedDate", $phone1ID);
                                    $userID   = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_User", $phone1ID);

                                    $phone_Volume_ID     = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_Volume", $phone1ID);
                                    $phone_User_ID       = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_User", $phone1ID);
                                    $phone_VolumeCumm_ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_VolumeCumm", $phone1ID);

                                    echo "\n".$TelNummer["Nummer"]." ".GetValue($phone_User_ID)." : ".GetValue($phone_Volume_ID)."MB und kummuliert ".GetValue($phone_VolumeCumm_ID)."MB \n";
                                    if (AC_GetLoggingStatus($archiveID, $phone_VolumeCumm_ID)==false)
                                        {
                                        echo "Werte wird noch nicht gelogged.\n";
                                        }
                                    else
                                        {
                                        $werteLogVolC = AC_GetLoggedValues($archiveID, $phone_VolumeCumm_ID, $starttime2, $endtime,0);
                                        $werteLogVol = AC_GetLoggedValues($archiveID, $phone_Volume_ID, $starttime2, $endtime,0);
                                        //$werteAggVol = AC_GetAggregatedValues($archiveID, $phone_Volume_ID, 1, $starttime2, $endtime,0); /* tägliche Aggregation */
                                        $wertAlt=-1; $letzteZeile="";
                                        foreach ($werteLogVol as $wert)
                                            {
                                            if ($wertAlt!=$wert["Value"])
                                                {
                                                echo $letzteZeile;
                                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                                //echo $letzteZeile;
                                                $wertAlt=$wert["Value"];
                                                }
                                            else
                                                {
                                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                                }
                                            //echo $letzteZeile;
                                            }
                                        $phone_Cost_ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_Cost", $phone1ID);
                                        $werteLogCost = AC_GetLoggedValues($archiveID, $phone_Cost_ID, $starttime2, $endtime,0);
                                        echo "Logged Cost Vaules:\n";
                                        $wertAlt=-1; $letzteZeile="";
                                        foreach ($werteLogCost as $wert)
                                            {
                                            if ($wertAlt!=$wert["Value"])
                                                {
                                                echo $letzteZeile;
                                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                                //echo $letzteZeile;
                                                $wertAlt=$wert["Value"];
                                                }
                                            else
                                                {
                                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                                }
                                            }
                                        $phone_Load_ID = @IPS_GetObjectIDByName("Phone_".$TelNummer["Nummer"]."_Load", $phone1ID);
                                        $werteLogLoad = AC_GetLoggedValues($archiveID, $phone_Load_ID, $starttime2, $endtime,0);
                                        echo "Logged Load Vaules:\n";
                                        $wertAlt=-1; $letzteZeile="";
                                        foreach ($werteLogLoad as $wert)
                                            {
                                            if ($wertAlt!=$wert["Value"])
                                                {
                                                echo $letzteZeile;
                                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                                //echo $letzteZeile;
                                                $wertAlt=$wert["Value"];
                                                }
                                            else
                                                {
                                                $letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                                                }
                                            }
                                        }
                                    }

                                }       // ende if false
                            echo "Berechnung Guthaben ist abgeschlossen.\n";
                            break;
                        case "EVN":
                        case "LOGWIEN":
                            echo "=========================$host=============================================\n";
                            echo "Automatic InputCsv for $host, read csv Files from Directory:\n";
                            $archiveID = $archiveOps->getArchiveID();   
                            ini_set('memory_limit', '128M');                        // können grosse Dateien werden
                            echo "Memorysize : ".getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb\n";                        
                            $go=true;
                            // target oid suchen 
                            switch (strtoupper($host))
                                {
                                case "LOGWIEN":
                                    $seleniumModul = new SeleniumLogWien();
                                    break;
                                case "EVN":
                                    $seleniumModul = new SeleniumEvn();       
                                    break;
                                default:
                                    echo "    Warning, dont know Modul $host.\n";
                                    $go=false;
                                    break;
                                }
                            $categoryID = $seleniumOperations->getCategory();                                
                            $targetCategory=$seleniumModul->getResultCategory();
                            echo "  Kategorie für Selenium Modul $host $categoryID : $targetCategory\n";
                            echo "  mit folgenden Ergebnisregistern:\n";
                            $result=IPS_GetChildrenIDs($targetCategory);
                            foreach ($result as $index => $childID) echo "      $index  $childID (".IPS_GetName($childID).") \n";
                            if (isset($entry["CONFIG"]["ResultTarget"]["OID"]))
                                {
                                $oid = $entry["CONFIG"]["ResultTarget"]["OID"];
                                echo "Use same target for Input csv data as Selenium uses : $oid und Name ".IPS_GetName($oid).".\n";
                                }
                            elseif (isset($entry["INPUTCSV"]["Target"]["Name"]))
                                {
                                $targetName = $entry["INPUTCSV"]["Target"]["Name"];
                                echo "Create/get the new register, Modul $modul mit Category $categoryIdResult und Register Name $targetName.\n";
                                /*function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false) */
                                $oid = CreateVariableByName($targetCategory,$targetName,2,'kWh',"",1100);         // kein Identifier, darf in einer Ebene nicht gleich sein
                                $componentHandling->setLogging($oid);                                                   // Archive setzen
                                echo "Use the Register mit OID $oid und Name ".IPS_GetName($oid).".\n";
                                }
                            elseif (isset($entry["INPUTCSV"]["Target"]["OID"]))
                                {
                                $oid=$entry["INPUTCSV"]["Target"]["OID"];
                                echo "Get and use the Register mit OID $oid und Name ".IPS_GetName($oid).". Probably not stored in ResultTarget Area.\n";
                                } 
                            else $go=false;         // nicht weitermachen wenn diser Input Parameter fehlt                        

                            //Input Verzeichnis suchen 
                            if (isset($entry["INPUTCSV"]["InputDir"]))
                                {
                                $inputDir=$entry["INPUTCSV"]["InputDir"];
                                $verzeichnis=$dosOps->getWorkDirectory();
                                $inputDir=$dosOps->correctDirName($verzeichnis.$inputDir);          // richtiges Abschlusszeichen / oder \
                                echo "Look for Input files in Input Directory $inputDir:\n";
                                $dosOps->writeDirStat($inputDir);                    // Ausgabe eines Verzeichnis   
                                $files=$dosOps->readdirToArray($inputDir);                                      
                                }
                            else $go=false;         // nicht weitermachen wenn diser Input Parameter fehlt                    
                            if (isset($entry["INPUTCSV"]["InputFile"]))
                                {
                                $filename=$entry["INPUTCSV"]["InputFile"];
                                echo "Input Filename is $filename.";
                                $filesToRead = $dosOps->findfiles($files,$filename);
                                if ($filesToRead==false) $go=false;
                                echo "\n";
                                }
                            else $go=false;         // nicht weitermachen wenn diser Input Parameter fehlt                    
                            if ($go) 
                                {

                                foreach ($filesToRead as $file)
                                    {
                                    echo "===========================================\n";
                                    echo "Read csv File $file in Directory $inputDir:\n";
                                    $resultAddChg = $archiveOps->addValuesfromCsv($inputDir.$file,$oid,$entry["INPUTCSV"],false);             // false means add to archive, add values to archive from csv, works for logWien and EVN data according to config above
                                    foreach ($resultAddChg as $type => $entries)
                                        {
                                        echo "  $type  : ".count($entries)."\n";            // Chg sind wahrscheinlich Ersatzwerte die nachträglich abgelesen wurden, Wiener netze bezeichnet sie als rechnerisch ermittelt
                                        }
                                    }
                                AC_ReAggregateVariable($archiveID,$oid);                                     
                                }
                            else echo "No reason found to import csv files.\n";

                            break;

                        default:
                            echo (strtoupper($host))."============\n";

                            break;    
                            
                        }           // ende switch
                    }           // ende foreach
                break;
            default:
                break;        
            }
        }

?>