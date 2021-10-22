<?


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
	 
	/**@defgroup zParseDreiGuthaben
	 * @{
	 *
     * iMacro legt nette Files an als Ergebnis des Downloads der aktuell angesteuerten Homepage
     * Selenium lest einzelne Datenobjekte aus, speichert diese in einer IP Symcon Variablen zur weiteren Auswertung
     * Diese Programm kann man für zeitversetzte Aufrufe aus dem Webfront ebenfalls verwenden
     *
     *
     *
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");					// Library verwendet Configuration, danach includen
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/******************************************************

				INIT

*************************************************************/

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

    $phoneID                = $guthabenHandler->getPhoneNumberConfiguration();
    $maxcount=count($phoneID);
	
    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

/*********************************************************************************************
 * 
 * Logging aktivieren
 *
 *********************************************************************************************/

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_Guthaben.csv",$input);

/*********************************************************************************************
 * 
 * MODE Selection
 *
 *********************************************************************************************/

	print_r($GuthabenAllgConfig);
    if (strtoupper($GuthabenAllgConfig["OperatingMode"])=="IMACRO") 
        {
        echo "Verzeichnis für Macros     : ".$GuthabenAllgConfig["MacroDirectory"]."\n";
        echo "Verzeichnis für Ergebnisse : ".$GuthabenAllgConfig["DownloadDirectory"]."\n\n";
        /* "C:/Users/Wolfgang/Documents/iMacros/Downloads/ */


        if ($_IPS['SENDER']=="Execute")
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
    *                        RUN
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

                    Execute

    *************************************************************/

        if ($_IPS['SENDER']=="Execute")
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
                //print_r($TelNummer);
                $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

                $phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
                $dateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Date", 3);
                $ldateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_loadDate", 3);
                $udateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_unchangedDate", 3);
                $userID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
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
                $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
                $phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
                $phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Volume", 2);
                $phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
                $phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_VolumeCumm", 2);
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
                    $phone_Cost_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Cost", 2);
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
                    $phone_Load_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Load", 2);
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
        }
    else
        {
        $ergebnis="";
        echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
        $guthabenHandler->updateConfiguration(true,true,true);                     // $ausgeben,$ergebnisse,$speichern          gespeichert wird aber eh immer
            IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");        
            //print_R($phoneID);
            $seleniumOperations = new SeleniumOperations(); 
            $guthabenHandler->extendPhoneNumberConfiguration($phoneID,$seleniumOperations->getCategory("DREI"));            // phoneID is return parameter, erweitert um  [LastUpdated] und [OID]
            //print_R($phoneID);
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
                        echo "$result\n";
                        $lines = explode("\n",$result);
                        $ergebnis1=$guthabenHandler->parsetxtfile($entry["Nummer"],$lines,false,"array");                    
                        }
                    else echo "\n";
                    }
                else echo "\n";
                }

        print_r($GuthabenConfig);
        foreach ($GuthabenConfig as $TelNummer)
            {
            //print_r($TelNummer);
            /* Neue Schreibweise ist Status. STATUS kann bei iMacro noch vorkommen */
            if ( (isset($TelNummer["STATUS"])) && (strtoupper($TelNummer["STATUS"]) == "ACTIVE" ) ) 
                { 
                $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
                echo "parsetxtfile : ".$TelNummer["NUMMER"]."\n";
                $phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
                $ergebnis1=$guthabenHandler->parsetxtfile($TelNummer["NUMMER"]);
                //SetValue($phone1ID,$ergebnis1);
                $ergebnis.=$ergebnis1."\n";
                }
            elseif ( (isset($TelNummer["Status"])) && (strtoupper($TelNummer["Status"]) == "ACTIVE" ) )         // Ergebis is strtoupper
                {
                $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
                echo "parsetxtfile : ".$TelNummer["Nummer"]."\n";
                $phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["Nummer"], 3);
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

        if ($_IPS['SENDER']=="Execute")
            {
            echo "========================================================\n";
            echo "Execute, Script ParseDreiGuthaben wird ausgeführt:\n\n";
            echo "Operating Mode              : ".(strtoupper($GuthabenAllgConfig["OperatingMode"]))."\n";            
            echo "  Ausgabe Ergebnis parsetxtfile :\n";
            echo "  -------------------------------\n";
            echo $ergebnis;
            echo "  Ausgabe Status der aktiven SIM Karten :\n";
            echo "  ---------------------------------------\n";
            if (false)
                {
            $ergebnis1="";
            foreach ($GuthabenConfig as $TelNummer)
                {
                //print_r($TelNummer);
                $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

                $phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
                $dateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Date", 3);
                $ldateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_loadDate", 3);
                $udateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_unchangedDate", 3);
                $userID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
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
                $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
                $phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
                $phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Volume", 2);
                $phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
                $phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_VolumeCumm", 2);
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
                    $phone_Cost_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Cost", 2);
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
                    $phone_Load_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Load", 2);
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
            }
        
        }


?>