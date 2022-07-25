<?


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
     * Bei Selenium wird diese Routine noch eingesetzt aber nicht mehr benötigt, der Aufruf der Funktionen zur Auflösung der Ergebnisse kann sofort erfolgen
     * wird von Guthabensteuerung Timer früh am Morgen als Script nach der Abfrage der Drei Konten aufgerufen
     * 
     * Ergebnis für alle Hosts in der Konfiguration ermitteln, wenn nicht schon geschehen
     *
     *
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");					// Library verwendet Configuration, danach includen
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/******************************************************
 *
 *				INIT
 *
 *************************************************************/

    $startexec=microtime(true);   
    $execute=false;                     // Execute extra code when called manually

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

    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

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


            if ($execute && ($_IPS['SENDER']=="Execute"))
                {
                /* Logging Einstellungen zum Debuggen */
                
                //$ausgeben=true; $ergebnisse=true; $speichern=true;				// Debug
                //$ausgeben=false; $ergebnisse=false; $speichern=false;				// Operation
                $ausgeben=true; $ergebnisse=true; $speichern=false;
                }
            else
                {	
                $ausgeben=false; $ergebnisse=false; $speichern=false;
                }

            /******************************************************
            *
            *                        RUN iMACRO
            *
            * Parse textfiles, die von iMacro generiert wurden
            *				
            *
            *************************************************************/


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

            /******************************************************
            *
            *              Execute
            *
            *************************************************************/

            if ($execute && ($_IPS['SENDER']=="Execute"))
                {
                echo "========================================================\n";
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
                    if (AC_GetLoggingStatus($archiveHandlerID, $phone_VolumeCumm_ID)==false)
                        {
                        echo "Werte wird noch nicht gelogged.\n";
                        }
                    else
                        {
                        $werteLogVolC = AC_GetLoggedValues($archiveHandlerID, $phone_VolumeCumm_ID, $starttime2, $endtime,0);
                        $werteLogVol = AC_GetLoggedValues($archiveHandlerID, $phone_Volume_ID, $starttime2, $endtime,0);
                        //$werteAggVol = AC_GetAggregatedValues($archiveHandlerID, $phone_Volume_ID, 1, $starttime2, $endtime,0); /* tägliche Aggregation */
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
                        $werteLogCost = AC_GetLoggedValues($archiveHandlerID, $phone_Cost_ID, $starttime2, $endtime,0);
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
                        $werteLogLoad = AC_GetLoggedValues($archiveHandlerID, $phone_Load_ID, $starttime2, $endtime,0);
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

                        if ($execute && ($_IPS['SENDER']=="Execute"))
                            {
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
                                    if (AC_GetLoggingStatus($archiveHandlerID, $phone_VolumeCumm_ID)==false)
                                        {
                                        echo "Werte wird noch nicht gelogged.\n";
                                        }
                                    else
                                        {
                                        $werteLogVolC = AC_GetLoggedValues($archiveHandlerID, $phone_VolumeCumm_ID, $starttime2, $endtime,0);
                                        $werteLogVol = AC_GetLoggedValues($archiveHandlerID, $phone_Volume_ID, $starttime2, $endtime,0);
                                        //$werteAggVol = AC_GetAggregatedValues($archiveHandlerID, $phone_Volume_ID, 1, $starttime2, $endtime,0); /* tägliche Aggregation */
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
                                        $werteLogCost = AC_GetLoggedValues($archiveHandlerID, $phone_Cost_ID, $starttime2, $endtime,0);
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
                                        $werteLogLoad = AC_GetLoggedValues($archiveHandlerID, $phone_Load_ID, $starttime2, $endtime,0);
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
                            }           // ende if execute
                        echo "Berechnung Guthaben ist abgeschlossen.\n";
                        break;
                    case "EASY":
                        echo "EASY ============\n";
                        $seleniumEasycharts = new SeleniumEasycharts();
                        echo "Konfiguration von EASY gesucht:\n";
                        $depotRegister = $seleniumEasycharts->getDebotBooksfromConfig($guthabenHandler->getSeleniumTabsConfig("EASY"));                 //  Auswertung
                        foreach ($depotRegister as $depot)
                            {
                            echo "Depot ausgewählt: $depot  \n";
                            echo "--------------------------------\n";
                            echo "Selenium Operations, readResult from EASY on $depot:\n";
                            $result=$seleniumOperations->readResult("EASY",$depot,true);                  // true Debug   
                            echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";       
                            $lines = explode("\n",$result["Value"]);    
                            $data=$seleniumEasycharts->parseResult($lines,false);             // einlesen, true debug
                            $shares=$seleniumEasycharts->evaluateResult($data);
                            $depotName=str_replace(" ","",$depot);                      // Blanks weg
                            if ($depotName != $depot)               
                                {
                                echo "--------\n";
                                $value=$seleniumEasycharts->evaluateValue($shares);         // Summe ausrechnen
                                $seleniumEasycharts->writeResult($shares,"Depot".$depotName,$value);                         // die ermittelten Werte abspeichern, shares Array etwas erweitern                                
                                }
                            else
                                {
                                $seleniumEasycharts->writeResult($shares,"Depot".$depotName);                         // die ermittelten Werte abspeichern, shares Array etwas erweitern
                                }
                            $seleniumEasycharts->updateResultConfigurationSplit($shares);                           // es kann sein dass es Splits für Aktien gibt
                            $seleniumEasycharts->writeResultConfiguration($shares, $depotName);                                    
                            }
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
                        echo "parse LogWien Ergebnis in Result.\n";
                        $result=$seleniumOperations->readResult("LogWien","Result",true);                  // true Debug
                        //print_R($result);
                        echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";
                        echo "--------\n";
                        //$checkArchive=$archiveOps->getComponentValues($oid,20,false);                 // true mit Debug
                        $seleniumLogWien = new SeleniumLogWien();
                        $seleniumLogWien->writeEnergyValue($result["Value"],"EnergyCounter");
                        break;
                    case "YAHOOFIN":
                        echo "YAHOOFIN ============\n";                    
                        echo "parse YahooFin Ergebnis in Result.\n";
                        $result=$seleniumOperations->readResult("YAHOOFIN");                  // true Debug   , lest RESULT als Egebnis Variable, wenn zweite Variable ausgefüllt ist es das entsprechende register
                        echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";       
                        $yahoofin = new SeleniumYahooFin();
                        $ergebnis = $yahoofin->parseResult($result);                        // eigentlich nur json_decode auf ein array
                        $yahoofin->writeResult($ergebnis,"TargetValue",true);
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


?>