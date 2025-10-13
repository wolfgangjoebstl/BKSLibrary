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
	 
	 /***********************************************
	  *
	  * verschiedene Routinen und Definitionen die von allen Modulen benötigt werden können
	  * viele davon bereits zusammengefasst in classes oder groups
      *
      * ein paar altmodische Definitionen
      * Power functions (group)
      * send_status
      *
      * praktische Funktionen für alle Programme und Funktionen
      * 
      * nf      	    number_format abhängig von Unit oder default
	  * send_status  Ausgabe des aktuellen Status aktuell oder historisch
      *
	  * erstellt auch einige für alle brauchbaren Klassen:
      * -------------------------------------------------
      *
      * uebersichtlicher als die verschiedenen einzelnen Routinen
	  *
      * profileOps              versammelt Operationen rund um die Bearbeitung von Profile
      * webOps
      * archiveOps              rund um die Archiv Funktion von IP Symcon
      * statistics
      *         meansCalc           extends statistics
      *         eventLogEvaluate    extends statistics
      *         meansRollEvaluate   extends statistics
      *         maxminCalc          extends statistics
      *
      * App_Convert_XmlToArray
      *
      *
      * jsSnippets              Aufbau von javascript basierten html Seiten
      * ipsTables               Darstellung von html basierten Tabellen
      * ipsCharts               Chartdarstellung rund um die Highcharts Funktionen
      *                
      * ipsOps                  Zusammenfassung von Funktionen rund um die Erleichterung der Bedienung von IPS Symcon
      * dosOps
      * sysOps
      * fileOps                 Zusammenfassung von Funktionen rund um das lesen und schreiben von Datenbanken im csv Forma
      * timerOps
      * curlOps                 curl Aufgaben, zusammengefasst
      * geoOps                  alles rund um Plätze auf der Erde und im Weltall
      * errorAusgabe
      * ComponentHandling
	  * WfcHandling             Vereinfachter Webfront Aufbau wenn SplitPanes verwendet werden sollen, vorerst von Modulen AMIS und Sprachsteuerung verwendet
      *                         übernimmt Funktionen zum WFC Handling, wie zB CreatePane, Create Category, SpiltPane etc
      * ModuleHandling              
      *
      *
      *
      *
      *
      *
      * DEPRICATED
      * verschiedene Routinen die bald geloescht werden sollen
      *     getNiceFileSize
      *     getServerMemoryUsage
      *
	  ****************************************************************/


IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
   
/*******************************************
 * altmodische Definitionen 
 *************************************/
    
    /* ObjectID Adresse vom send email server */

    $sendResponse = 30887; //ID einer SMTP Instanz angeben, um Rückmelde-Funktion zu aktivieren

    /* Heizung */

    define("ADR_Heizung_AZ",38731);
    define("ADR_Heizung_WZ",34901);
    define("ADR_Zusatzheizung_WZ",40443);
    define("ADR_Heizung_KZ",30616);
    define("ADR_Zusatzheizung_KZ",36154);

    define("ADR_Router_Stromversorgung",41865);
    define("ADR_WebCam_Stromversorgung",13735);
    define("ADR_GBESwitch_Stromversorgung",23695);


    /* IP Adressen */


    define("ADR_Gateway","10.0.1.200");
    define("ADR_Homematic","10.0.1.3");

    define("ADR_Webcam_innen","10.0.1.2");
    define("ADR_Webcam_innen_Port","2001");

    define("ADR_Webcam_lbg","hupo35.ddns-instar.de");

    define("ADR_Webcam_Keller","10.0.1.122");
    define("ADR_Webcam_Keller_Port","2002");

    define("ADR_Webcam_Garten","10.0.1.123");
    define("ADR_Webcam_Garten_Port","2003");


    /* Wohnungszustand */

    define("STAT_WohnungszustandInaktiv",0);
    define("STAT_WohnungszustandFerien",1);
    define("STAT_WohnungszustandUnterwegs",2);
    define("STAT_WohnungszustandStandby",3);
    define("STAT_WohnungszustandAktiv",4);
    define("STAT_WohnungszustandTop",5);

    /* erkannter Zustand */
    define("STAT_KommtnachHause",18);
    define("STAT_Bewegung9",15);
    define("STAT_Bewegung8",14);
    define("STAT_Bewegung7",13);
    define("STAT_Bewegung6",12);
    define("STAT_Bewegung5",11);
    define("STAT_Bewegung4",10);
    define("STAT_Bewegung3", 9);
    define("STAT_Bewegung2", 8);
    define("STAT_Bewegung",  7);
    define("STAT_WenigBewegung",6);
    define("STAT_KeineBewegung",5);
    define("STAT_Unklar",4);
    define("STAT_Undefiniert",3);
    define("STAT_vonzuHauseweg",2);
    define("STAT_nichtzuHause",1);
    define("STAT_Abwesend",0);



    /**************************************************************************************************************************************/


    /* Webcam hat zwei Ports, derzeit verwenden wir den WLAN Port, da er immer auf lbgtest (direkt am Thomson) funktioniert
        10.0.0.27 (es kann auch immer nur ein Dienst auf eine IP Adresse umgeroutet werden, daher immer WLAN verwenden
        ausser zur Konfiguration */
        
    //define("ADR_WebCamLBG","10.0.0.27");

    /* Cam Positionen : 1-4 : 'Sofa', 'Gang', 'Sessel', 'Terasse'  */

    define("ADR_WebCamLBG","hupo35.ddns-instar.de");
    define("ADR_WebCamBKS","sina73.ddns-instar.com");
    define("ADR_GanzLinks","10.0.0.1");
    define("ADR_DenonWZ","10.0.0.115");
    define("ADR_DenonAZ","10.0.0.26");

    /* IP Adresse iTunes SOAP Modul  */

    define("ADR_SOAPModul","10.0.0.20:8085");
    define("ADR_SoapServer","10.0.0.20:8085");

    //define("ADR_Programs","C:/Program Files/");
    define("ADR_Programs",'C:/Program Files (x86)/');


/*************************************************************************************************************************************
 *
 *      POWER FUNCTIONS
 *
 * useful functions:
 *
 * nf                           number format with extended functionality, shifts automatically the unit , i.e. from seconds to minutes and hours etc.
 *
 * configfileParser             Unit, UNIT Einheit etc  wenn in der Config eines der Synonyme vorhanden ist wird es gemappt (&$inputArray, &$outputArray, [Synonym,2,3,4],$tag,$defaultValue)
 * configfileMultiTarget        Switch whatever it is, adjust the config to fit to such needs
 * doMultiTargetSwitch          Switch whatever it is when config is adjusted as above
 *
 * rpc_CreateVariableProfile    Variable Profile lokal oder remote erzeugen, $rpc ist entweder eine class oder false
 * rpc_SetVariableProfileIcon
 * rpc_SetVariableProfileDigits
 * rpc_SetVariableProfileText
 * rpc_SetVariableProfileValues
 * synchronizeProfiles
 * compareProfiles
 * createProfilesByName         depricated, use 	$profileOps->createKnownProfilesByName("Euro");
 *
 *
 *************************************************************************/


    /* nf       number format with extended functionality, shifts automatically the unit , i.e. from seconds to minutes and hours etc.  
     *
     * known units
     *
     *  s,sec
     *  m,min
     *  kWh
     *  kW
     *  W
     *  Byte, Bytes
     *  DayMonth                date("d.m.
     *  HourMinute, HourMin
     *  °                       degree, temperature
     *  %
     *  USD,EUR,MM
     *  Date, Time, DateTime
     *
     * second parameter formats width with pads alignment left
     */

    function nf($value,$unit="",$pad=0)
        {
        $result=false;
        if (is_integer($unit)) $result = number_format($value, $unit, ",",".");         // unit ist die Anzahl an Nachkommastellen
        else
            {
            switch (strtoupper($unit))
                {
                case "S":
                case "SEC":
                    if ($value <(4*60)) $result = number_format($value, 1, ",",".")." sec";
                    elseif ($value <(4*60*60)) $result = number_format(($value/60), 1, ",",".")." m";
                    elseif ($value <(4*24*60*60)) $result = number_format(($value/60/60), 1, ",",".")." h";
                    elseif ($value <(4*24*60*60)) $result = number_format(($value/24/60/60), 1, ",",".")." d";
                    else $result = number_format(($value/7/24/60/60), 1, ",",".")." w";
                    break;
                case "M":
                case "MIN":
                    if ($value <(4*60)) $result = number_format(($value), 1, ",",".")." m";
                    elseif ($value <(4*24*60)) $result = number_format(($value/60), 1, ",",".")." h";
                    elseif ($value <(4*24*60)) $result = number_format(($value/24/60), 1, ",",".")." d";
                    else $result = number_format(($value/7/24/60), 1, ",",".")." w";
                    break;
                case "KWH":
                    $result = number_format($value, 2, ",",".")." $unit";
                    break;
                case  "KW":
                    $result = number_format($value, 3, ",",".")." $unit";
                    break;
                case  "W":
                    $result = number_format($value, 0, ",",".")." $unit";
                    break;
                case "BYTE":
                case "BYTES":
                    $sysOps = new sysOps();
                    $result = $sysOps->getNiceFileSize($value);         // getNiceFileSize
                    break;
                case "DAYMONTH":
                    if (is_numeric($value)) $result = date("d.m.",$value);
                    else $result = $value;
                    break; 
                case "DATE":
                    if (is_numeric($value)) $result = date("d.m.Y",$value);
                    else $result = $value;
                    break; 
                case "DATETIME":
                    if (is_numeric($value)) $result = date("d.m.Y H:i:s",$value);
                    else $result = $value;
                    break;    
                case "TIME":
                    if (is_numeric($value)) $result = date("H:i:s",$value);
                    else $result = $value;
                    break;                                      
                case "HOURMINUTE":
                case "HOURMIN":
                    if (is_numeric($value)) $result = date("H:i",$value);
                    else $result = $value;
                    break; 
                case "LAT":
                case "LON":         // degree plus kommas
                    if (is_numeric($value)) $result = number_format((float)$value, 4, ",",".")."° $unit";           // wie unten, aber vielleicht kommt noch etwas
                    else $result = $value;
                    break;                                     
                case "°":
                    if (is_numeric($value)) $result = number_format((float)$value, 1, ",",".")."$unit";           // wie unten, aber vielleicht kommt noch etwas
                    else $result = $value;
                    break;
                case "USD":
                case "EUR":
                case "MM":
                    if (is_numeric($value)) $result = number_format($value, 2, ",",".")." $unit";           // wie unten, aber vielleicht kommt noch etwas
                    else $result = $value;
                    break;
                case "%":
                    $result = number_format($value*100, 2, ",",".")."%";           // wie unten, aber vielleicht kommt noch etwas
                    break;
                case "AUTO":
                    if (gettype($value)=="boolean") $result = ($value?"true":"false"); 
                    elseif (gettype($value)=="string") $result = $value;
                    else 
                        {
                        if ($value<10) $round=3;
                        elseif ($value<1000) $round=2;
                        else $round=0;
                        $result = number_format($value, $round, ",",".");
                        }
                    break;
                case "OID":
                    $result = str_pad($value, 5);
                    break;                    
                default:
                    if (gettype($value)=="boolean") $result = ($value?"true":"false"); 
                    elseif (gettype($value)=="string") $result = $value;
                    elseif (gettype($value)=="integer") $result = $value;                    
                    else $result = number_format($value, 2, ",",".")." $unit";           // unit wahrscheinlich empty oder ein Wert den wir nicht kennnen
                    break;
                }
            }
        if ($pad) $result = str_pad($result,$pad, " ", STR_PAD_LEFT);
        return($result);    
        }

    /* function überall verwenden zu Adressierung von Variablen
      */
    function universalOid($oid,$debug=false)
        {
        $result=array();
        $server = explode("::",$oid);
        $size = sizeof($server);
        if ($size==2)
            {
            $remServer	  = RemoteAccessServerTable(2,$debug);
            if (isset($remServer[$server[0]])) $url=$remServer[$server[0]]["Url"];
            else echo "Warning, no url found in RemoteAccess data.\n";
            $rpc = new JSONRPC($url);
            if ($debug) echo "Server $url : ".$rpc->IPS_GetName(0)." Search for Name at Program.IPSLibrary.data.core.IPSComponent.Counter-Auswertung\n";
            $path = explode(".",$server[1]);
            $dirs = sizeof($path);
            if ($dirs>1)        // wir haben einen path
                {
                $newID=0; 
                foreach ($path as $item)    
                    {
                    $actualID=$newID;
                    $newID=$rpc->IPS_GetObjectIDByName($item,$actualID);
                    if ($newID===false) break;
                    if ($debug) echo ".$newID";
                    }
                if ($newID===false) 
                    {
                    echo "Warning, no name found in $path";
                    return (false);
                    }
                $remOID=$newID;
                }
            else                // wir glauben zu wissen wo die Datei erwartet wird   
                {
                $prgID=$rpc->IPS_GetObjectIDByName("Program",0);
                $ipsID=$rpc->IPS_GetObjectIDByName("IPSLibrary",$prgID);
                $dataID=$rpc->IPS_GetObjectIDByName("data",$ipsID);
                $coreID=$rpc->IPS_GetObjectIDByName("core",$dataID);
                $compID=$rpc->IPS_GetObjectIDByName("IPSComponent",$coreID);
                $CounterAuswertungID=$rpc->IPS_GetObjectIDByName("Counter-Auswertung",$compID);
                $remOID=@$rpc->IPS_GetObjectIDByName($server[1],$CounterAuswertungID);
                if ($remOID===false) 
                    {
                    echo "Warning, no name found in Program.IPSLibrary.data.core.IPSComponent.Counter-Auswertung";
                    return (false);
                    }
                }
            $result["Server"]=$rpc;
            $result["OID"]=$remOID;
            return ($result);
            }
        else
            {
            $path = explode(".",$server);
            $dirs = sizeof($path);
            if ($dirs>1)        // wir haben einen path
                {
                $newID=0; 
                foreach ($path as $item)    
                    {
                    $actualID=$newID;
                    $newID=IPS_GetObjectIDByName($item,$actualID);
                    if ($newID===false) break;
                    if ($debug) echo ".$newID";
                    }
                if ($newID===false) 
                    {
                    echo "Warning, no name found in $path";
                    return (false);
                    }
                $oid=$newID;
                }
            else                // wir glauben zu wissen wo die Datei erwartet wird   
                {
                $prgID=IPS_GetObjectIDByName("Program",0);
                $ipsID=IPS_GetObjectIDByName("IPSLibrary",$prgID);
                $dataID=IPS_GetObjectIDByName("data",$ipsID);
                $coreID=IPS_GetObjectIDByName("core",$dataID);
                $compID=IPS_GetObjectIDByName("IPSComponent",$coreID);
                $CounterAuswertungID=IPS_GetObjectIDByName("Counter-Auswertung",$compID);
                $oid=@IPS_GetObjectIDByName($result[0],$CounterAuswertungID);
                if ($oid===false) 
                    {
                    echo "Warning, no name found in $path";
                    return (false);
                    }
                }
            $result["Server"]=false;
            $result["OID"]=$oid;
            return ($result);
            }
        }


    /*
    *
    *   Configfile Parser   Unit, UNIT Einheit etc  wenn in der Config eines der Synonyme vorhanden ist wird es gemappt (&$inputArray, &$outputArray, [Synonym,2,3,4],$tag,$defaultValue)
    */

    function configfileParser(&$inputArray, &$outputArray, $synonymArray,$tag,$defaultValue,$debug=false)
        {
        $found=false;
        if ($debug)  
            {
            echo "Aufruf configfileParser for $tag mit default $defaultValue.\n";
            print_R($inputArray);
            }
        foreach ($synonymArray as $synonym)
            {
            if (isset($inputArray[$synonym])) 
                {
                $outputArray[$tag] = $inputArray[$synonym];
                if ($found === false) $found=true;
                else echo "*****configfileParser, Configuration Fehler, Synonym mehrmals vorhanden. $tag\n";
                }
            }
        if ($found===false)         // keines der Synonyme vorhanden, den json dekodierten Defaultwert übernehmen
            {
            //if ($debug) echo "not found, use default ".$outputArray[$tag]."\n";
            if (is_array($defaultValue))
                {
                $outputArray[$tag] = $defaultValue;
                }
            elseif ($defaultValue===null)                           // wenn nicht === wird null mit false gleichgesetzt
                {
                //$outputArray[$tag] = $defaultValue;               // changed, defaultvalue null does not result into creation of the item per se
                if ($debug) echo "configfileParser: Tag $tag Null detected as default value.\n";    
                }
            else  
                {
                if ($debug) echo "not found, use default \"$defaultValue\" for \"$tag\".\n";
                $input=json_decode($defaultValue,true);
                if ( ($defaultValue != "") && (is_array($input)) ) $outputArray[$tag] = $input;                 // input ist ein json encodiertes array
                elseif ($input !== false) $outputArray[$tag] = $defaultValue;                               // input ist ein Wert
                else echo "json decode failed on \"$defaultValue\" for $tag.\n";
                }
            //if ($outputArray[$tag]===null) $outputArray[$tag]=$defaultValue;                
            } 
        }

    /* DEPRECIATED, change to profileOps class
     * Profil Befehle die gleich sind für Lokal und Remote Server, werden weiter unten und in Remote Access class gebraucht.
     * Ziel ist einheitliche eigene Profile zu schaffen, die immer vorhanden sind
     * $rpc ist entweder eine class oder false
     *
     */

    function rpc_CreateVariableProfile($rpc, $pname, $type, $demo=false)
        {
        if ($demo) echo '    IPS_CreateVariableProfile ($pname, '.$type.');'."\n";
        else
            {
            if ($rpc) $rpc->IPS_CreateVariableProfile ($pname, $type);
            else 
                {
                if (IPS_VariableProfileExists($pname))
                    {
                    $targetTyp=IPS_GetVariableProfile($pname)["ProfileType"];
                    if ($targetTyp != $type)
                        {
                        echo "rpc_CreateVariableProfile,Profile has different type, requested $type, existing $targetTyp.\n";
                        return (false);
                        }
                    }
                else IPS_CreateVariableProfile ($pname, $type);                                             // wird immer erstellt, es könnte sich ja auch der Typ geändert haben
                }
            }        
        }

    /* DEPRECIATED, change to profileOps class */

    function rpc_SetVariableProfileIcon($rpc, $pname, $icon, $demo=false)
        {
        if ($demo) echo '    IPS_SetVariableProfileIcon ($pname, "'.$icon.'");'."\n";
        else
            {
            if ($rpc) $rpc->IPS_SetVariableProfileIcon ($pname, $icon);
            else IPS_SetVariableProfileIcon ($pname, $icon);
            }        
        }

    /* DEPRECIATED, change to profileOps class */

    function rpc_SetVariableProfileDigits($rpc, $pname, $digits, $demo=false)
        {
        if ($demo) echo '    IPS_SetVariableProfileDigits ($pname, '.$digits.");\n";                // ähnliche Formatierung, damit das Kopieren leicheter fällt
        else
            {
            if ($rpc) $rpc->IPS_SetVariableProfileDigits ($pname, $digits);
            else IPS_SetVariableProfileDigits ($pname, $digits);
            }
        }

    /* DEPRECIATED, change to profileOps class */

    function rpc_SetVariableProfileText($rpc, $pname, $prefix, $suffix, $demo=false)
        {
        if ($demo) echo '    IPS_SetVariableProfileText ($pname, "'.$master["Prefix"].'","'.$master["Suffix"]."\");\n";
        else
            {
            if ($rpc) $rpc->IPS_SetVariableProfileText ($pname, $prefix,$suffix);
            else IPS_SetVariableProfileText ($pname, $prefix,$suffix);
            }
        }

    /* DEPRECIATED, change to profileOps class */

    function rpc_SetVariableProfileValues($rpc, $pname, $minValue, $maxValue, $stepSize, $demo=false)
        {
        if ($demo) echo '    IPS_SetVariableProfileValues ($pname, '.$minValue.",".$maxValue.",".$stepSize.");\n";
        else
            {
            if ($rpc) $rpc->IPS_SetVariableProfileValues ($pname, $minValue,$maxValue,$stepSize);
            else IPS_SetVariableProfileValues ($pname, $minValue,$maxValue,$stepSize);
            }
        }

    /* Anlegen und Synchronisieren von Profilen, Aufruf geht auch rekursiv
     * soll auch gleich Remote gehen
     * verwendet in CustomComponent und RemoteAccess und hier in synchronize Profiles
     *
     *  server      Remote Server oder Local/false, damit wird rpc false, sonst der Server Zugriffsname
     *  master      kann auch ein leeres array sien
     *
     */

    function compareProfiles($server, $master,$target,$masterName,$targetName, $demo=false,$debug=false)
        {
        $prefix=true; $minvalue=true;

        if ($demo)
            {
            $target=array();
            $target["ProfileName"]=$targetName;            
            }

        if ( (strtoupper($server) == "LOCAL") || ($server===false) )
            {
            if ($debug) echo "compareProfiles only locally, compare $masterName,$targetName:\n";    
            $rpc=false;
            }
        else
            {
            if ($debug) echo "compareProfiles with Server $server, compare $masterName,$targetName:\n";    
            $rpc = new JSONRPC($server);
            $remote=true;
            }


        // Profile name needs to be set
        
        //print_r($master); print_r($target);
        foreach ($master as $index => $entry)           // kann ach ein leeres array sein, dann wird hier übersprungen und nix gemacht
            {
            if (is_array($master[$index])) 
                {
                switch ($index)
                    {
                    case "Associations":
                        if ( (isset($target[$index])) === false) 
                            {
                            $target[$index]=array();
                            echo "$index ist ein Array. Im Target neu anlegen. ".sizeof($master[$index])." != ".sizeof($target[$index])."\n";
                            }
                        if ( (sizeof($master[$index])) != (sizeof($target[$index])) ) 
                            {
                            if (sizeof($target[$index])==0)
                                {
                                //echo "Associations im Target neu anlegen.\n";
                                //print_r($master[$index]);
                                foreach ($master[$index] as $entry)
                                    {
                                    if ($demo) echo '    IPS_SetVariableProfileAssociation($pname, '.$entry["Value"].', "'.$entry["Name"].'", "'.$entry["Icon"].'", '.$entry["Color"].");\n";
                                    else 
                                        {
                                        if ($rpc) $rpc->IPS_SetVariableProfileAssociation($targetName, $entry["Value"], $entry["Name"], $entry["Icon"], $entry["Color"]);
                                        else IPS_SetVariableProfileAssociation($targetName, $entry["Value"], $entry["Name"], $entry["Icon"], $entry["Color"]);
                                        }
                                    }
                                }
                            else echo "Associations nicht gleich gross\n";
                            }
                        break;
                    default:
                        echo "sub array $index\n";
                        if (isset($target[$index])) compareProfiles($server, $master[$index], $target[$index],$masterName,$targetName,$demo);
                        else echo "Target Index not available\n";
                        break;
                    }
                }
            elseif ( ((isset($target[$index])) === false) || ( (isset($target[$index])) && ($master[$index] != $target[$index]) ))      //entweder gibts den target Index gar nicht oder er ist nicht gleich dem master
                {
                if ( (isset($target["ProfileType"])) === false)
                    {
                    //echo "$index: Profil noch nicht vorhanden. Als ersten Befehl CreateVariableProfil durchführen.\n";
                    rpc_CreateVariableProfile($rpc, $targetName, $master["ProfileType"], $demo);
                    $target["ProfileName"]=$targetName;
                    $target["ProfileType"]=$master["ProfileType"];   
                    }
                switch ($index)
                    {
                    case "ProfileName":
                    case "ProfileType":
                        //echo "Variable bereits mit $targetName und Typ ".$master["ProfileType"]." erstellt.\n";
                        break;
                    case "MinValue":
                    case "MaxValue":
                    case "StepSize":
                        if ($minvalue)
                            {
                            rpc_SetVariableProfileValues($rpc, $targetName, $master["MinValue"], $master["MaxValue"], $master["StepSize"], $demo);
                            $minvalue=false;
                            }
                        break;
                    case "Digits":
                        rpc_SetVariableProfileDigits($rpc, $targetName, $master["Digits"],$demo);
                        break;
                    case "Icon":
                        rpc_SetVariableProfileIcon($rpc, $targetName, $master["Icon"], $demo);
                        break;
                    case "Prefix":
                    case "Suffix":
                        if ($prefix)
                            {
                            rpc_SetVariableProfileText ($rpc, $targetName, $master["Prefix"], $master["Suffix"]);
                            $prefix=false;
                            }
                        break;
                    default:
                        if (isset($target[$index])) echo "    ".str_pad($index,20)."  $master[$index]  $target[$index] \n";
                        else echo "    ".str_pad($index,20)."  $master[$index]  $targetName Index $index unknown \n";
                        break;
                    }
                }
            }
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
	
	// Repository
	$repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';

	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);

	$versionHandler = $moduleManager->VersionHandler();
	$versionHandler->BuildKnownModules();
	$knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
	$inst_modules = "Verfügbare Module und die installierte Version :\n\n";
	$inst_modules.= "Modulname                  Version    Version      Beschreibung\n";
	$inst_modules.= "                          verfügbar installiert                   \n";
	
	$upd_modules = "Module die upgedated werden müssen und die installierte Version :\n\n";
	$upd_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";

	foreach ($knownModules as $module=>$data)
		{
		$infos   = $moduleManager->GetModuleInfos($module);
		$inst_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10);
		if (array_key_exists($module, $installedModules))
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
	$inst_modules .= "\n".$upd_modules;
	echo ">>Auswertung der Module die upgedatet werden müssen. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";

	if (isset($installedModules["Amis"])==true)
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
		if ( (isset($installedModules["RemoteReadWrite"])==true) || (isset($installedModules["EvaluateHardware"])==true) )
			{
			if (isset($installedModules["EvaluateHardware"])==true) 
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
				
			if (isset($installedModules["Gartensteuerung"])==true)
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

		if (isset($installedModules["Amis"])==true)
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

		if (isset($installedModules["OperationCenter"])==true)
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
            if ($debug) echo "$ergebnisOperationCenter \n";
			
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

		if (isset($installedModules["Guthabensteuerung"])==true)
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
		if (isset($installedModules["Amis"])==true)
			{
			/* nur machen wenn AMIS installiert */
			IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');		
			IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
            $Amis = new Amis();           
			$MeterConfig = $Amis->getMeterConfig();
			
			$ergebnistab_energie="";
			
			$amis=new Amis();
			$Meter=$amis->writeEnergyRegistertoArray($MeterConfig,false);		/* alle Energieregister in ein Array schreiben, Parameter : Config, debug */
			$ergebnistab_energie.=$amis->writeEnergyRegisterTabletoString($Meter,false);	/* output with no html encoding */	
			$ergebnistab_energie.="\n\n";					
			$ergebnistab_energie.=$amis->writeEnergyRegisterValuestoString($Meter,false);	/* output with no html encoding */	
			$ergebnistab_energie.="\n\n";					
			$ergebnistab_energie.=$amis->writeEnergyPeriodesTabletoString($Meter,false,true);	/* output with no html encoding, values in kwh */
			$ergebnistab_energie.="\n\n";
			$ergebnistab_energie.=$amis->writeEnergyPeriodesTabletoString($Meter,false,false);	/* output with no html encoding, values in EUR */
			$ergebnistab_energie.="\n\n";

			if (false)
				{
				$zeile = array("Datum" => array("Datum",0,1,2), "Heizung" => array("Heizung",0,1,2), "Datum2" => array("Datum",0,1,2), "Energie" => array("Energie",0,1,2), "EnergieVS" => array("EnergieVS",0,1,2));

				$amisdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
			
				foreach ($MeterConfig as $meter)
					{
					$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
					$meterdataID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
					/* ID von Wirkenergie bestimmen */
					switch ( strtoupper($meter["TYPE"]) )
						{	
						case "AMIS":
							$AmisID = CreateVariableByName($meterdataID, "AMIS", 3);
							//$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
							//$variableID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
							$variableID = IPS_GetObjectIDByName ( 'Wirkenergie' , $AmisID );
							break;
						case "HOMEMATIC":
						case "REGISTER":	
						default:
							$variableID = CreateVariableByName($meterdataID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
							break;
						}	
					/* Energiewerte der ketzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr */
					$jetzt=time();
					$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
					$starttime=$endtime-60*60*24*10;
	
					$werte = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime, $endtime, 0);
					$vorigertag=date("d.m.Y",$jetzt);

					echo "Create Variableset for :".$meter["NAME"]." für Variable ".$variableID."  \n";
					echo "ArchiveHandler: ".$archiveHandlerID." Variable: ".$variableID."\n";
					echo "Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
					//echo "Voriger Tag :".$vorigertag."\n";
					$laufend=1;
					$alterWert=0;
					$ergebnis_tabelle1=substr("                          ",0,12);
					foreach($werte as $wert)
						{
						$zeit=$wert['TimeStamp']-60;
						if (date("d.m.Y", $zeit)!=$vorigertag)
						   {
							$zeile["Datum2"][$laufend] = date("D d.m", $zeit);
							$zeile["Energie"][$laufend] = number_format($wert['Value'], 2, ",", "" ) ." kWh";
							echo "Werte :".$zeile["Datum2"][$laufend]." ".$zeile["Energie"][$laufend]."\n";
							if ($laufend>1) {$zeile["EnergieVS"][$laufend-1] = number_format(($alterWert-$wert['Value']), 2, ",", "" ) ." kWh";}
							$ergebnis_tabelle1.= substr($zeile["Datum2"][$laufend]."            ",0,12);
							$laufend+=1;
							$alterWert=$wert['Value'];
							//echo "Voriger Tag :".date("d.m.Y",$zeit)."\n";
							}
						$vorigertag=date("d.m.Y",$zeit);
						}
					$anzahl2=$laufend-2;
					$ergebnis_datum="";
					$ergebnis_tabelle1="";
					$ergebnis_tabelle2="";
					echo "Es sind ".$laufend." Eintraege vorhanden.\n";
					//print_r($zeile);
					$laufend=0;
					while ($laufend<=$anzahl2)
						{
						$ergebnis_datum.=substr($zeile["Datum2"][$laufend]."            ",0,12);
						$ergebnis_tabelle1.=substr($zeile["Energie"][$laufend]."            ",0,12);
						$ergebnis_tabelle2.=substr(($zeile["EnergieVS"][$laufend])."            ",0,12);
						$laufend+=1;
						//echo $ergebnis_tabelle."\n";
						}
					//$ergebnistab_energie.="Stromverbrauch der letzten Tage von ".$meter["NAME"]." :\n\n".$ergebnis_datum."\n".$ergebnis_tabelle1."\n".$ergebnis_tabelle2."\n\n";
					$ergebnistab_energie.="Stromverbrauch der letzten Tage von ".$meter["NAME"]." :\n\n";
					$ergebnistab_energie.="Energiewert aktuell ".$zeile["Energie"][1]."\n\n";
					$ergebnistab_energie.=$ergebnis_datum."\n".$ergebnis_tabelle2."\n\n";

					/* Kategorie Periodenwerte selbst suchen */
					$PeriodenwerteID = CreateVariableByName($meterdataID, "Periodenwerte", 3);
				
					$ergebnistab_energie.="Stromverbrauchs-Statistik von ".$meter["NAME"]." :\n\n";
					$ergebnistab_energie.="Stromverbrauch (1/7/30/360) : ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzterTag',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte7Tage',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte30Tage',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte360Tage',$PeriodenwerteID)), 2, ",", "" )." kWh \n";
					$ergebnistab_energie.="Stromkosten    (1/7/30/360) : ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzterTag',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte7Tage',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte30Tage',$PeriodenwerteID)), 2, ",", "" );
					$ergebnistab_energie.=        " / ".number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte360Tage',$PeriodenwerteID)), 2, ",", "" )." Euro \n\n\n";
					}
				}
			
		  	echo ">>AMIS historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}
			
		/************** Guthaben auslesen ****************************************************************************/
		
		if (isset($installedModules["Guthabensteuerung"])==true)
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
		if (isset($installedModules["CustomComponent"])==true)
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
		print_r($installedModules);
		if ( (isset($installedModules["DetectMovement"])==true) && ( (isset($installedModules["RemoteReadWrite"])==true) || (isset($installedModules["EvaluateHardware"])==true) ) )
			{
			echo "=====================Detect Movement Motion Detect \n";
			IPSUtils_Include ('IPSComponentSensor_Motion.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
			if (isset($installedModules["EvaluateHardware"])==true) 
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
			if (isset($installedModules["RemoteAccess"])==true) IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
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

		if (isset($installedModules["Gartensteuerung"])==true)
			{
			$gartensteuerung = new Gartensteuerung();
			$ergebnisGarten="\n\nVerlauf der Gartenbewaesserung:\n\n";
			$ergebnisGarten=$ergebnisGarten.$gartensteuerung->listEvents()."\n";
		  	echo ">>Gartensteuerung historische Werte. Abgelaufene Zeit : ".exectime($startexec)." Sek \n";
			}

		/******************************************************************************************/

		if (isset($installedModules["OperationCenter"])==true)
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




   


/**************************************************************************************************************************************
 *
 *	Verschieden brauchbare Funktionen
 *
 *      GetInstanceIDFromHMID
 *      writeLogEvent
 *      writeLogEventClass
 *
 *      GetValueIfFormattedEx
 *      GetValueIfFormatted
 *
 *      CreateVariableByName
 *      getVariableIDByName
 *      CreateCategoryByName
 *      getCategoryIdByName
 *
 *      CreateCategoryPathFromOid
 *
 *      Depricated/Removed: 
 *          CreateVariableByName2
 *          CreateVariable2
 *          CreateVariableByNameFull
 *
 *          summestartende
 *          summestartende2
 *
 *      Get_IdentByName2
 *      UpdateObjectData2
 *
 *      RPC_CreateVariableByName
 *      RPC_CreateCategoryByName
 *      RPC_CreateVariableField
 *      RemoteAccessServerTable
 *      RemoteAccess_GetConfigurationNew
 *
 *      ReadTemperaturWerte
 *      ReadThermostatWerte
 *      ReadAktuatorWerte
 *
 *      startexec
 *      exectime
 *      getVariableId
 *
 *
 * AD_ErrorHandler
 *
 **************************************************************************************************************************************/
	

    /* durchsucht alle Homematic Instanzen
    * nach Adresse:Port
    * wenn adresse:port uebereinstimmt die Instanz ID zurückgeben, sonst 0
    */
    
    function GetInstanceIDFromHMID($sid)
        {
        $ids = IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
        foreach($ids as $id)
            {
            //$address=HM_GetAddress($id);
            $address=IPS_GetProperty($id,'Address');
            //echo "GetInstanceIDFromHMID get $address from $id and compare ports with name\n";
            $a = explode(":", $address);
            $b = explode(":", $sid);
            if($a[0] == $b[0])
                {
                return $id;
                }
            }
        return 0;
        }


    /******************************************************************/

    function writeLogEvent($event)
        {
        /* call with writelogEvent("Beschreibung")  writes to Log_Event.csv File */
        $dosOps = new dosOps();    
        $systemDir     = $dosOps->getWorkDirectory();         
        if (!file_exists($systemDir."Log_Events.csv"))
            {
            $handle=fopen($systemDir."Log_Events.csv", "a");
            fwrite($handle, date("d.m.y H:i:s").";Eventbeschreibung\r\n");
            fclose($handle);
            }

        $handle=fopen($systemDir."Log_Events.csv","a");
        fwrite($handle, date("d.m.y H:i:s").";".$event."\r\n");
        /* unterschiedliche Event Speicherorte */

        fclose($handle);
        }

    /******************************************************************/

    function writeLogEventClass($event,$class)
        {

        /* call with writelogEvent("Beschreibung")  writes to Log_Event.csv File

        */
        $dosOps = new dosOps();    
        $systemDir     = $dosOps->getWorkDirectory();  
        if (!file_exists($systemDir."Log_Events.csv"))
            {
            $handle=fopen($systemDir."Log_Events.csv", "a");
            fwrite($handle, date("d.m.y H:i:s").";Eventbeschreibung\r\n");
            fclose($handle);
            }

        $handle=fopen($systemDir."Log_Events.csv","a");
        $ausgabewert=date("d.m.y H:i:s").";".$event;
        fwrite($handle, $class.$ausgabewert."\r\n");

        /* unterschiedliche Event Speicherorte */
        
        if (IPS_GetName(0)=="LBG70")
            {
            SetValue(24829,$ausgabewert);
            }
        else
            {
            SetValue(44647,$ausgabewert);
            }
        fclose($handle);
        }


    /*****************************************************************
    * Formtiert den Wert Value wie die Formatierung von oid
    *
    ************************************************************************/

    function GetValueIfFormattedEx($oid,$value, $html=false)
        {
        $variabletyp=IPS_GetVariable($oid);
        if ( ($variabletyp["VariableProfile"]!="")  or ($variabletyp["VariableCustomProfile"]!="") )
            {
            if ($variabletyp["VariableProfile"]!="")        $profile = $variabletyp["VariableProfile"];
            if ($variabletyp["VariableCustomProfile"]!="")  $profile = $variabletyp["VariableCustomProfile"];
            $profileConfig = IPS_GetVariableProfile($profile);
            $result=GetValueFormattedEx($oid,$value);
            if ($html && (isset($profileConfig["Associations"])) ) 
                {
                foreach ($profileConfig["Associations"] as $index => $association)
                    {
                    if ($association["Value"]==$value) 
                        {
                        //print_R($association);
                        $color = "000000".dechex($association["Color"]);
                        $color = substr($color,-6);
                        if (hexdec($color) > 1000000) $color="1F2F1F";
                        echo "Farbe Association ist #$color\n";
                        //$result='<p style="background-color:black;color:#'.$color.'";>'.$result.'</p>';
                        $result='<p style="background-color:'.$color.';color:white;">'.$result.'</p>';
                        }

                    }
                
                }
            }
        else
            {
            $result=$value;
            }
        return ($result);  

        }

    /*****************************************************************
    *
    * vereint getValue und GetValueFormatted
    * Nachdem getValueFormatted immer einen Fehler ausgibt wenn die Variable keine Formattierung unterstützt wird halt vorher abgefragt
    *
    ************************************************************************/

    function GetValueIfFormatted($oid)
        {
        $variabletyp=IPS_GetVariable($oid);
        if ( ($variabletyp["VariableProfile"]!="")  or ($variabletyp["VariableCustomProfile"]!="") )
            {
            $result=@GetValueFormatted($oid);
            if ($result===false) { echo "GetValueIfFormatted: Fehler mit Format von $oid.\n"; }
            }
        else
            {
            $result=GetValue($oid);
            }
        return ($result);    
        }

    /*****************************************************************
    *
    * CreateVariableByName, CreateCategoryByName 
    * Variable oder Kategorie wird nur angelegt wenn sie noch nicht vorhanden ist
    *
    */

    function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false, $debug=false)
        {
        //echo "Position steht auf $position.\n";
        //echo "CreateVariableByName: $id $name $type $profile $ident $position $action\n";
        /* type steht für 0 Boolean 1 Integer 2 Float 3 String */
        
        $VariableId = @IPS_GetVariableIDByName($name, $parentID);
        if ($VariableId === false)
            {
            if ($debug) echo "CreateVariableByName : $name Type $type in $parentID:\n";
            $VariableId = @IPS_CreateVariable($type);
            if ($VariableId === false ) throw new Exception("Cannot CreateVariable with Type $type");
            IPS_SetParent($VariableId, $parentID);
            IPS_SetName($VariableId, $name);
            if ( ($profile) && ($profile !== "") ) { IPS_SetVariableCustomProfile($VariableId, $profile); }
            if ( ($ident) && ($ident !=="") ) {IPS_SetIdent ($VariableId , $ident );}
            if ( $action && ($action!=0) ) { IPS_SetVariableCustomAction($VariableId,$action); }        
            if ($default !== false) SetValue($VariableId, $default);
            IPS_SetInfo($VariableId, "this variable was created by script #".$_IPS['SELF']." ");
            }
        else 
            {
            $VariableData = IPS_GetVariable($VariableId);
            $objectInfo   = IPS_GetObject($VariableId); 
            if ($VariableData['VariableType'] <> $type)
                {
                IPSLogger_Err(__file__, "CreateVariableByName, $VariableId ($name) Type ".$VariableData['VariableType']." <> \"$type\". Delete and create new.");
                IPS_DeleteVariable($VariableId); 
                $VariableId=CreateVariableByName($parentID, $name, $type, $profile, $ident, $position, $action);  
                $VariableData = IPS_GetVariable ($VariableId);            
                }
            if ($profile && ($VariableData['VariableCustomProfile'] <> $profile) )
                {
                //Debug ("Set VariableProfile='$Profile' for Variable='$name' ");
                if ($debug) echo "Set VariableProfile='$profile' for Variable='$name' \n";
                $result=@IPS_SetVariableCustomProfile($VariableId, $profile);
                if ($result==false) 
                    {
                    echo "CreateVariableByName, $VariableId ($name) Type ".$VariableData['VariableType']." and new Profile $profile produce error, do not match.\n";
                    IPSLogger_Err(__file__, "CreateVariableByName, $VariableId ($name) Type ".$VariableData['VariableType']." and new Profile $profile produce error, do not match.");
                    }
                }	
            if ($action && ($VariableData['VariableCustomAction'] <> $action) )
                {
                //Debug ("Set VariableCustomAction='$Action' for Variable='$Name' ");
                if ($debug) echo "Set VariableCustomAction='$action' for Variable='$name' \n";
                IPS_SetVariableCustomAction($VariableId, $action);
                }
            if ($ident && ($objectInfo['ObjectIdent'] <> $ident) )
                {
                //Debug ("Set VariableCustomAction='$Action' for Variable='$Name' ");
                if ($debug) echo "Set VariableIdent='$ident' for Variable='$name' \n";
                IPS_SetIdent($VariableId, $ident);
                }
    
            }
        IPS_SetPosition($VariableId, $position);
        return $VariableId;
        }

    /* infache getVariableId Function
     * vertauschte Parameter :-) und ein false ohne Fehlermeldung
     *
     */
    function getVariableIDByName($parentID, $name)
        {
        return(@IPS_GetVariableIDByName($name, $parentID));    
        }

    /* einfache CreateCategory Function
     */
    function CreateCategoryByName($parentID, $name, $position=0)
        {
        $vid = @IPS_GetCategoryIDByName($name, $parentID);
        if($vid === false)
            {
            $vid = IPS_CreateCategory();
            IPS_SetParent($vid, $parentID);
            IPS_SetName($vid, $name);
            IPS_SetInfo($vid, "this category was created by script #".$_IPS['SELF']." ");
            }
        IPS_SetPosition($vid, $position);
        return $vid;
        }

    /* einfache getCategoryId Function
     * vertauschte Parameter :-) und ein false ohne Fehlermeldung
     */
    function getCategoryIdByName($parentID, $name)
        {
        return(@IPS_GetCategoryIDByName($name, $parentID));
        }


	/** Anlegen eines Kategorie Pfades. Kopiert from IPSInstaller CreateCategoryPath
	 *
	 * Eine Liste von Kategorien, die durch einen '.' voneinander separiert sind, können als String übergeben
	 * werden.
	 *
	 *  $Path Kategorie Pfad (zB. 'Program.IPSInstaller'), Achtung geht in die verkehrte richtung, neue Categorie links, 
	 * 
	 *  return integer ID der Kategorie
	 *
	 */
	function CreateCategoryPathFromOid($Path, $ParentId=0, $debug=false) {
		$CategoryListInput = explode('.',$Path);
        $CategoryList=array();
        $count=count($CategoryListInput)-1; $count1=$count;
		foreach ($CategoryListInput as $Idx=>$Category) 
            {
            $CategoryList[$count]=$Category;
            $count--;
            }
        $count++;
        ksort($CategoryList);
        if ($debug) echo "CreateCategoryPathFromOid ";
		foreach ($CategoryList as $Idx=>$Category) {
            if ( ($Idx==0) && ($Category == IPS_GetName($ParentId)) ) 
                { 
                if ($debug) echo "$Idx ".IPS_GetName($ParentId)." : $ParentId  "; 
                }
            else 
                {
                if ($debug) echo "$Idx $Category : ";
                $ParentId = CreateCategoryByName ($ParentId, $Category);
                if ($debug) echo $ParentId."  ";
                }
			}
        if ($debug) echo "\n";
		return $ParentId;
	}

    /******************************************************************/

    /* Additional function to create an identifier out of a variable name, space is the new parameter to decide either to remove 
    * special characters or replace them either by a space or an underscore
    *
    */

    function Get_IdentByName2($name, $space="")
        {
            $ident = str_replace(' ', $space, $name);
            $ident = str_replace(array('ö','ä','ü','Ö','Ä','Ü'), array('oe', 'ae','ue','Oe', 'Ae','Ue' ), $ident);
            $ident = str_replace(array('"','\'','%','&','(',')','=','#','<','>','|','\\'), $space, $ident);
            $ident = str_replace(array(',','.',':',';','!','?'), $space, $ident);
            $ident = str_replace(array('+','-','/','*'), $space, $ident);
            $ident = str_replace(array('ß'), 'ss', $ident);
            return $ident;
        }

    /******************************************************************/

    function UpdateObjectData2($ObjectId, $Position, $Icon="")
        {
            $ObjectData = IPS_GetObject ($ObjectId);
            $ObjectPath = IPS_GetLocation($ObjectId);
            if ($ObjectData['ObjectPosition'] <> $Position and $Position!==false) {
                //Debug ("Set ObjectPosition='$Position' for Object='$ObjectPath' ");
                IPS_SetPosition($ObjectId, $Position);
            }
            if ($ObjectData['ObjectIcon'] <> $Icon and $Icon!==false) {
                //Debug ("Set ObjectIcon='$Icon' for Object='$ObjectPath' ");
                IPS_SetIcon($ObjectId, $Icon);
            }

        }

    /*****************************************************************
     * verwendet in installComponentFull
     * Parameter
     *  rpc         der rpc für den Server
     *  id          OID der Kategorie
     *  name        Name
     *  struktur    array der Server Struktur, childrens der id
     *  type        steht für 0 Boolean 1 Integer 2 Float 3 String
     *
     * möglichst effiziente Routine gesucht, da rpc Zugriffe immer Zeit brauchen für Auf und Abbau des TCP
     * die Erzeugung der Struktur braucht lange, daher soll immer die Struktur mitübergeben werden
     *
     */
    function RPC_CreateVariableByName($rpc, $id, $name, $type, $struktur=array())
        {
        $result="";
        $size=sizeof($struktur);
        if ($size==0)
            {
            $children=$rpc->IPS_GetChildrenIDs($id);
            foreach ($children as $oid)
                {
                $struktur[$oid]=$rpc->IPS_GetName($oid);
                }		
            //echo "RPC_CreateVariableByName, nur wenn Struktur nicht übergeben wird neu ermitteln.\n";
            //echo "Struktur :\n";
            //print_r($struktur);
            }
        foreach ($struktur as $oid => $oname)
            {
            if ($name==$oname) {$result=$name;$vid=$oid;}
            //echo "Variable ".$name." bereits angelegt, keine weiteren Aktivitäten.\n";		
            }
        if ($result=="")
            {
            echo "Variable ".$name." auf Server neu erzeugen.\n";
            $vid = $rpc->IPS_CreateVariable($type);
            $rpc->IPS_SetParent($vid, $id);
            $rpc->IPS_SetName($vid, $name);
            $rpc->IPS_SetInfo($vid, "this variable was created by script RPC_CreateVariableByName, from Server ".IPS_GetName(0).".");
            }
        //echo "Fertig mit ".$vid."\n";
        return $vid;
        }

    /******************************************************************/

    function RPC_CreateCategoryByName($rpc, $id, $name)
        {

        /* erzeugt eine Category am Remote Server */

        $result="";
        $struktur=$rpc->IPS_GetChildrenIDs($id);
        foreach ($struktur as $category)
        {
        $oname=$rpc->IPS_GetName($category);
        //echo str_pad($oname,20)." ".$category."\n";
        if ($name==$oname) {$result=$name;$vid=$category;}
        }
        if ($result=="")
        {
        $vid = $rpc->IPS_CreateCategory();
        $rpc->IPS_SetParent($vid, $id);
        $rpc->IPS_SetName($vid, $name);
        $rpc->IPS_SetInfo($vid, "this category was created by script. ");
        }
        return $vid;
        }

    /******************************************************************/

    function RPC_CreateVariableField($Homematic, $keyword, $profile,$startexec=0)
        {

        IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
        $remServer=ROID_List();
        if ($startexec==0) {$startexec=microtime(true);}
        foreach ($Homematic as $Key)
            {
            /* alle Feuchtigkeits oder Temperaturwerte ausgeben */
            if (isset($Key["COID"][$keyword])==true)
                {
                $oid=(integer)$Key["COID"][$keyword]["OID"];
                $variabletyp=IPS_GetVariable($oid);
                if ($variabletyp["VariableProfile"]!="")
                    {
                    echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
                    }
                else
                    {
                    echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
                    }
                $parameter="";
                foreach ($remServer as $Name => $Server)
                    {
                    $rpc = new JSONRPC($Server["Adresse"]);
                    if ($keyword=="TEMPERATURE")
                        {
                        $result=RPC_CreateVariableByName($rpc, (integer)$Server[$profile], $Key["Name"], 2);
                        }
                    else
                        {
                        $result=RPC_CreateVariableByName($rpc, (integer)$Server[$profile], $Key["Name"], 1);
                        }
                    $rpc->IPS_SetVariableCustomProfile($result,$profile);
                    $rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
                    $rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
                    $rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
                    $parameter.=$Name.":".$result.";";
                    }
                $messageHandler = new IPSMessageHandler();
                $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
                $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
                if ($keyword=="TEMPERATURE")
                    {
                    $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Temperatur,'.$parameter,'IPSModuleSensor_Temperatur,1,2,3');
                    }
                else
                    {
                    $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Feuchtigkeit,'.$parameter,'IPSModuleSensor_Feuchtigkeit,1,2,3');
                    }
                }
            }
        }

    /* RemoteAccessServerTable, benötigt das Module RemoteAccess
    *
    * wandelt die Liste der remoteAccess server in RemoteAccess_GetServerConfig in eine bessere Tabelle um und hängt den aktuellen Status zur Erreichbarkeit in die Tabelle ein
    * der Status wird alle 60 Minuten von operationCenter ermittelt. Wenn Modul nicht geladen wurde wird einfach true angenommen
    * vergleiche Funktion in RemoteAccess Library
    * 
    * wenn Modul OperationCenter vorhanden gibt es auch eine Uptime
    *
    * nur hier wird auch Alexa berücksichtigt
    *
    *****************************************************************************/

    function RemoteAccessServerTable($mode=1,$debug=false)
        {
        $RemoteServer = array();    
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $result=$moduleManager->GetInstalledModules();
        if (isset ($result["RemoteAccess"]))
            {
            IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");	
            $remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
            if (isset ($result["OperationCenter"]))
                {
                if ($debug)  
                    {
                    echo "RemoteAccessServerTable aufgerufen, Modul RemoteAccess und OperationCenter sind installiert.\n"; 
                    echo "    check RemoteAccess_GetServerConfig() in RemoteAccess_Configuration.inc.php.\n";
                    }
                $moduleManager_DM = new IPSModuleManager('OperationCenter');     /*   <--- change here */
                $CategoryIdData   = $moduleManager_DM->GetModuleCategoryID('data');
                $Access_categoryId=@IPS_GetObjectIDByName("AccessServer",$CategoryIdData);
                }
            else $Access_categoryId=false;
            //$remServer=RemoteAccess_GetConfiguration();
            //foreach ($remServer as $Name => $UrlAddress)
            foreach ($remServer as $Name => $Server)
                {
                if (isset($Server["LOGGING"])===false) $Server["LOGGING"]="DISABED";                // wenns nicht definiert ist, default is disabled
                $UrlAddress=$Server["ADRESSE"];                                                         // das ist die RPC Adresse
                if ($debug) echo "   Server Name ".str_pad($Name,20)." : ".str_pad($UrlAddress,100);
                if ( (isset($Server["STATUS"])===true) and (isset($Server["LOGGING"])===true) )                         // beides muss definiert sein
                    {                    
                    if ( ( ($mode==1) && (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") ) ||
                            ($mode==0) ||
                            ( ($mode==2) && (strtoupper($Server["STATUS"])=="ACTIVE") ) )
                        {
                        if (isset($Server["HOOK"]))	$RemoteServer[$Name]["Webhook"]=$Server["HOOK"];			
                        else
                            {
                            $parsed = parse_url($UrlAddress);           //     [scheme] => http ,    [host] => wolfgangjoebstlbks.synology.me,     [port] => 3778,     [user] => wolfgangjoebstl@yahoo.com ,     [pass] => Cloudg0606 ,     [path] => /api/
                            $RemoteServer[$Name]["Webhook"]=$parsed["scheme"].'://'.$parsed["host"];
                            if (isset($parsed["port"])) $RemoteServer[$Name]["Webhook"].=":".$parsed["port"];
                            $RemoteServer[$Name]["Webhook"].="/webhook/";
                            }
                        $RemoteServer[$Name]["Url"]=$UrlAddress;
                        $RemoteServer[$Name]["Name"]=$Name;
                        if ($Access_categoryId)
                            {
                            $IPS_UpTimeID = CreateVariableByName($Access_categoryId, $Name."_IPS_UpTime", 1);
                            if (GetValue($IPS_UpTimeID)==0)
                                {
                                $RemoteServer[$Name]["Status"]=false;
                                if ($debug) echo "not available";
                                }
                            else
                                {
                                $RemoteServer[$Name]["Status"]=true;
                                if ($debug) echo "    available";
                                }
                            }
                        else $RemoteServer[$Name]["Status"]=true;
                        }
                    else { if ($debug) echo "STATUS and LOGGING do not fit to requirement fo Mode $mode : ACTIVE/ENABLED";    }
                    }
                else { if ($debug) echo "no STATUS or LOGGING config entry found"; }
                if ($debug) echo "\n"; 
                if (isset($Server["ALEXA"])===true ) $RemoteServer[$Name]["Alexa"] = $Server["ALEXA"];
                }
            }
        return($RemoteServer);
        }

    /*****************************************************************
    *
    * wandelt die Liste der remoteAccess_GetServerConfig  in das alte Format der tabelle RemoteAccess_GetConfiguration um
    * Neuer Name , damit alte Funktionen keine Fehlermeldung liefern 
    *
    *****************************************************************************/
    
    function RemoteAccess_GetConfigurationNew()
        {
        $RemoteServer=array();
        $remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
        foreach ($remServer as $Name => $Server)
            {
            $UrlAddress=$Server["ADRESSE"];
            if ( (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") )
                {				
                $RemoteServer[$Name]=$UrlAddress;
                }
            }	
        return($RemoteServer);
        }
        
    /******************************************************************/

    function ReadTemperaturWerte()
        {
        
        if (isset($installedModules["EvaluateHardware"])==true) 
            {
            IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            }
        //elseif (isset($installedModules["RemoteReadWrite"])==true) IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
        
        $alleTempWerte="";
        $Homematic = HomematicList();
        foreach ($Homematic as $Key)
            {
            /* alle Homematic Temperaturwerte ausgeben */
            if (isset($Key["COID"]["TEMPERATURE"])==true)
                {
                $oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
                $alleTempWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                }
            }

        $FHT = FHTList();
        foreach ($FHT as $Key)
            {
            /* alle FHT Temperaturwerte ausgeben */
            if (isset($Key["COID"]["TemeratureVar"])==true)
            {
                $oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
                $alleTempWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                }
            }
        return ($alleTempWerte);
        }

    function ReadThermostatWerte()
        {
        $repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

        if (isset($installedModules["EvaluateHardware"])==true) 
            {
            IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            }
        //elseif (isset($installedModules["RemoteReadWrite"])==true) IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
        
        $alleWerte="";

        $Homematic = HomematicList();
        $FS20= FS20List();
        $FHT = FHTList();

        $pad=50;
        $alleWerte.="\n\nAktuelle Heizungswerte direkt aus den HW-Registern:\n\n";
        $varname="SET_TEMPERATURE";
        foreach ($Homematic as $Key)
            {
            /* Alle Homematic Stellwerte ausgeben */
            if ( (isset($Key["COID"][$varname])==true) && !(isset($Key["COID"]["VALVE_STATE"])==true) )
                {
                /* alle Stellwerte der Thermostate */
                //print_r($Key);

                $oid=(integer)$Key["COID"][$varname]["OID"];
                $variabletyp=IPS_GetVariable($oid);
                if ($variabletyp["VariableProfile"]!="")
                    {
                    $alleWerte.=str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                    }
                else
                    {
                    $alleWerte.=str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                    }
                }
            }

        $varname="SET_POINT_TEMPERATURE";
        foreach ($Homematic as $Key)
            {
            /* Alle Homematic Stellwerte ausgeben */
            if ( (isset($Key["COID"][$varname])==true) && !(isset($Key["COID"]["VALVE_STATE"])==true) )
                {
                /* alle Stellwerte der Thermostate */
                //print_r($Key);
                $oid=(integer)$Key["COID"][$varname]["OID"];
                $variabletyp=IPS_GetVariable($oid);
                if ($variabletyp["VariableProfile"]!="")
                    {
                    $alleWerte.=str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                    }
                else
                    {
                    $alleWerte.=str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                    }
                }
            }

        foreach ($FHT as $Key)
            {
            /* alle FHT Temperaturwerte ausgeben */
            if (isset($Key["COID"]["TargetTempVar"])==true)
            {
                $oid=(integer)$Key["COID"]["TargetTempVar"]["OID"];
                $alleWerte.=str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                }
            }

        return ($alleWerte);
        }

    function ReadAktuatorWerte()
        {
        $repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
        if (isset($installedModules["EvaluateHardware"])==true) 
            {
            IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            }
        //elseif (isset($installedModules["RemoteReadWrite"])==true) IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
        $componentHandling=new ComponentHandling();

        $alleWerte="";
        $alleWerte.="\n\nAktuelle Heizungs-Aktuatorenwerte direkt aus den HW-Registern:\n\n";
        
        if (function_exists('HomematicList')) $alleWerte.=$componentHandling->getComponent(HomematicList(),"TYPE_ACTUATOR","String");
        if (function_exists('FHTList')) $alleWerte.=$componentHandling->getComponent(FHTList(),"TYPE_ACTUATOR","String");

        return ($alleWerte);
        }


    /******************************************************************/

    function startexec($mode="ms")
        {
        $time=hrtime(true);
        switch ($mode)
            {
            case "ms":
                $time=$time/1000000;
                break;
            case "us":
                $time=$time/1000;
                break;
            case "s":
                $time=$time/1000000000;
                break;
            default:
                break;
            }
        return ($time);
        }

    /* gibt wenn default die Zeit in Sekunden an basierend auf der Unix Zeit
     * wenn ein Parameter verwendet wird wird anstelle von microtime hrtime verwendet, Zeit vom Systemstart in Nanosekunden
     */

    function exectime($startexec,$mode=false)
        {
        $time=hrtime(true);
        switch ($mode)
            {
            case "ms":
                return (round($time/1000000-$startexec,0));
                break;
            case "us":
                return (round($time/1000-$startexec,0));
                break;
            case "s":
                return (round($time/1000000000-$startexec,3));
                break;
            default:
                return (number_format((microtime(true)-$startexec),2));
                break;
            }        
        }

    /*****************************************************************
     *
     * hilfreiche Funktion wird in Stromheizung verwendet
     * findet einen Variablennamen name an verschiedenen Orten Id, es wird ein GetChildrens auf dieser Kategorie gemacht und der Name abgeglichen
     *      switchCategoryId        kann array of IDs oder eine Id sein
     * wenn false wird die 
     *      groupCategory           geprüft und dann die Programs Kategorie geprüft
     *      categoryIdPrograms
     *
     *
     */

    function getVariableId($name, $switchCategoryId, $groupCategoryId=false, $categoryIdPrograms=false,$debug=false) 
        {
        if (is_array($switchCategoryId))
            {
            if ($debug) echo "getVariableId, check Children for $name in one of these categries ".json_encode($switchCategoryId)."\n";
            foreach ($switchCategoryId as $categoryId)
                {
                $childrenIds = IPS_GetChildrenIDs($categoryId);
                foreach ($childrenIds as $childId) 
                    {
                    if (IPS_GetName($childId)==$name) 
                        {
                        return $childId;
                        }
                    }
                }
            }
        elseif ($switchCategoryId !== false)
            {
            if ($debug) echo "getVariableId, check Children for $name in the category $switchCategoryId :\n    ";
            $childrenIds = IPS_GetChildrenIDs($switchCategoryId);
            foreach ($childrenIds as $childId) 
                {
                //if ($debug) echo IPS_GetName($childId)."  ";
                if (IPS_GetName($childId)==$name) 
                    {
                    return $childId;
                    }
                }
            }
        // Wenn Switch nicht erfolgreich in Gruppe weitersuchen
        if ($groupCategoryId !== false)
            {
            $childrenIds = IPS_GetChildrenIDs($groupCategoryId);
            foreach ($childrenIds as $childId) 
                {
                if (IPS_GetName($childId)==$name) 
                    {
                    return $childId;
                    }
                }
            }
        
        // Wenn Switch und Gruppe nicht erfolgreich in Program weitersuchen
        if ($categoryIdPrograms !== false)
            {
            $childrenIds = IPS_GetChildrenIDs($categoryIdPrograms);
            foreach ($childrenIds as $childId) 
                {
                if (IPS_GetName($childId)==$name) 
                    {
                    return $childId;
                    }
                }
            }
        // immer noch nichts gefunden
        //trigger_error("getVariableId: '$name' could NOT be found in 'Switches' and 'Groups'");
        echo "getVariableId: '$name' could NOT be found in 'Switches' and 'Groups'\n";
        return (false);
        }

/***************************************************************************************************************************
 *
 * versammelt Operationen rund um die Bearbeitung von Profile
 * synchronize profiles between nodes (rpc)
 *
 *  GetVariableProfile                  IPS_GetVariableProfile
 *  VariableProfileExists               IPS_VariableProfileExists
 *  CreateVariableProfile               Fehlermeldung wenn Typ nicht übereinstimmt
 *  SetVariableProfileIcon
 *  SetVariableProfileDigits
 *  SetVariableProfileText
 *  SetVariableProfileValues
 *  GetVariableProfileAssociations      siehe oben, nimmt nur Associations
 *  SetVariableProfileAssociation
 *  UpdateVariableProfileAssociations
 *  DeleteVariableProfileAssociation
 *  createKnownProfilesByName           bekannte neue persönliche Profile
 *  synchronizeProfiles
 *  compareProfiles
 *
 */

class profileOps
    {

    var $rpc;               // rpc für remote server, wenn server nicht local
    protected $debug;       // zusaetzliches Debug

    function __construct($server="local",$debug=false)
        {
        if (strtoupper($server) != "LOCAL") 
            {
            $this->rpc = new JSONRPC($server);
            }
        else $this->rpc=false;
        $this->debug = $debug;
        }

    /* profileOps::GetVariableProfile
     * Profile lokal oder Remote auslesen, das config array auslesen
     */
    function GetVariableProfile($pname)
        {
        if ($this->rpc) $profile=$this->rpc->IPS_GetVariableProfile ($pname);
        else $profile=IPS_GetVariableProfile ($pname);
        return ($profile);
        }

    /* VariableProfileExists
     * Profile lokal oder Remote auslesen, das config array auslesen
     */
    function VariableProfileExists($pname)
        {
        if ($this->rpc) return $this->rpc->IPS_VariableProfileExists($pname);
        else return IPS_VariableProfileExists($pname);
        }

    /* CreateVariableProfile
     * Profil Befehle die gleich sind für Lokal und Remote Server, werden weiter unten und in Remote Access class gebraucht.
     * Ziel ist einheitliche eigene Profile zu schaffen, die immer vorhanden sind
     * $rpc ist entweder eine class oder false
     *
     */
    function CreateVariableProfile($pname, $type)
        {
        if ($this->VariableProfileExists($pname))
            {
            $targetTyp=$this->GetVariableProfile($pname)["ProfileType"];
            if ($targetTyp != $type)
                {
                echo "profileOps::CreateVariableProfile,Profile has different type, requested $type, existing $targetTyp.\n";
                return (false);
                }
            }
        else 
            {
            if ($this->rpc) $this->rpc->IPS_CreateVariableProfile ($pname, $type);
            else IPS_CreateVariableProfile ($pname, $type);                                             // wird immer erstellt, es könnte sich ja auch der Typ geändert haben
            }
        }


    function SetVariableProfileIcon($pname, $icon)
        {
        if ($this->rpc) $this->rpc->IPS_SetVariableProfileIcon ($pname, $icon);
        else IPS_SetVariableProfileIcon ($pname, $icon);
        }

    function SetVariableProfileDigits($pname, $digits)
        {
        if ($this->rpc) $this->rpc->IPS_SetVariableProfileDigits ($pname, $digits);
        else IPS_SetVariableProfileDigits ($pname, $digits);
        }

    function SetVariableProfileText($pname, $prefix, $suffix)
        {
        if ($this->rpc) $this->rpc->IPS_SetVariableProfileText ($pname, $prefix,$suffix);
        else IPS_SetVariableProfileText ($pname, $prefix,$suffix);
        }

    function SetVariableProfileValues($pname, $minValue, $maxValue, $stepSize)
        {
        if ($this->rpc) $this->rpc->IPS_SetVariableProfileValues ($pname, $minValue,$maxValue,$stepSize);
        else IPS_SetVariableProfileValues ($pname, $minValue,$maxValue,$stepSize);
        }

    /* GetVariableProfileAssociations 
     * Profile Associations lokal oder Remote auslesen
     */
    function GetVariableProfileAssociations($pname)
        {
        $profile=$this->GetVariableProfile ($pname);
        return ($profile["Associations"]);
        }

    /* SetVariableProfileAssociation
     * Profile Associations lokal oder Remote schreiben
     */
    function SetVariableProfileAssociation($pname,$pos,$name,$icon,$color)
        {
        if ($this->rpc) $status=$this->rpc->IPS_SetVariableProfileAssociation ($pname, $pos, $name, $icon, $color);
        else $status=IPS_SetVariableProfileAssociation ($pname, $pos,  $name, $icon, $color); 
        return ($status);
        }

    /* profileOps::UpdateVariableProfileAssociations
     * Update Profile Associations lokal oder Remote
     */
    function UpdateVariableProfileAssociations($pname, $profile=array())
        {
        $profileOld=$this->GetVariableProfile ($pname)["Associations"];
        $count = sizeof($profile);
        $countOld = sizeof($profileOld);
        if ( ($countOld > $count) && ($count>0) )  
            {
            if ($this->debug) echo "Delete some Profile ( $countOld > $count )\n";
            foreach ($profileOld as $num => $assoc)
                {
                if ($num>($count-1)) 
                    {
                    if ($this->debug) echo "Delete Profile Association $num mit Wert ".$assoc["Value"].".\n";
                    //print_r($assoc);
                    //$this->DeleteVariableProfileAssociation($pname, $num);                  // ist num die vierte Instanz oder das Profil 6
                    $this->DeleteVariableProfileAssociation($pname, $assoc["Value"]);
                    }
                elseif ($this->debug) echo "Keep   Profile Association $num.\n";
                }
            }
        foreach ($profile as $num => $assoc)
            {
            $this->SetVariableProfileAssociation($pname,$assoc["Value"],$assoc["Name"],$assoc["Icon"],$assoc["Color"]);
            }
        }

    /* profileOps::DeleteVariableProfileAssociation
     * Delete Profile Association Position pos lokal oder Remote
     * set    IPS_SetVariableProfileAssociation (string $ProfileName, variant $Value, string $Name, string $Icon, int $Color) 
     * delete IPS_SetVariableProfileAssociation (string $ProfileName, variant $Value, "", "", -1);
     *
     * link: en/service/documentation/components/visualizations/object-presentation/ text: Object presentation 
     */
    function DeleteVariableProfileAssociation($pname, $pos)
        {
        if ($this->rpc) $status=$this->rpc->IPS_SetVariableProfileAssociation ($pname, $pos, "", "", -1);
        else $status=IPS_SetVariableProfileAssociation ($pname, $pos, "", "", -1); 
        return ($status);
        }

    /* profileOps::createKnownProfilesByName , Aufruf von synchronizeProfiles in zB CustomComponent_Installation verwendet
     * alle Profile manuell erzeugen, geht auch lokal oder remote, entscheidet ob ein Servername mitgegeben wir
     * die Profile haben alle vorgegebene Namen und werden hier zentral ausschliesslich nach dem erkannten Namen erzeugt
     *
     *      Temperatur            °C
     *      TemperaturSet          °C
     *      Humidity
     *      Switch
     *      Contact
     *      Window
     *      PowerLockBefehl
     *      PowerLockStatus
     *      Button
     *      Motion      
     *      Pressure
     *      CO2
     *      mode.HM
     *      Rainfall
     *
     *
     */

	function createKnownProfilesByName($pname,$debug=false)
        {
        if ($debug)
            {
            echo "      profileOps::createKnownProfilesByName, Profil ".$pname." existiert nicht, oder Aufforderung zum update. ";
            if ($this->rpc) echo "Durchführung remote für Server ".$this->rpc."\n";
            else echo "Durchführung lokal.\n";
            }
        switch ($pname)
            {
            case "Temperatur":
                $this->CreateVariableProfile($pname, 2);
                $this->SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
                $this->SetVariableProfileText($pname,'',' °C');
                break;
            case "TemperaturSet":
                $this->CreateVariableProfile($pname, 2);
                $this->SetVariableProfileIcon ($pname, "Temperature");
                $this->SetVariableProfileText($pname,'',' °C');
                $this->SetVariableProfileDigits($pname, 1); // PName, Nachkommastellen
                $this->SetVariableProfileValues ($pname, 6, 30, 0.5 );	// eingeschraenkte Werte von 6 bis 30 mit Abstand 0,5					
                break;
            case "Humidity";
                $this->CreateVariableProfile($pname, 2);
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileText($pname,'',' %');
                break;
            case "Switch";
                $this->CreateVariableProfile($pname, 0);
                $this->SetVariableProfileAssociation($pname, 0, "Aus","",0xff0000);   /*  Rot */
                $this->SetVariableProfileAssociation($pname, 1, "Ein","",0x00ff00);     /* Grün */
                break;
            case "Contact";
                $this->CreateVariableProfile($pname, 0);
                $this->SetVariableProfileIcon ($pname, "Alert");
                $this->SetVariableProfileText ($pname, "","");
                $this->SetVariableProfileValues ($pname, 0,1,0);
                $this->SetVariableProfileDigits ($pname, 0);                    
                $this->SetVariableProfileAssociation($pname, 0, "Geschlossen","" , -1);
                $this->SetVariableProfileAssociation($pname, 1, "Geöffnet", "", 65280);                    
                break;
            case "Window";
                $this->CreateVariableProfile($pname, 1);
                $this->SetVariableProfileIcon ($pname, "Window");
                $this->SetVariableProfileText ($pname, "","");
                $this->SetVariableProfileValues ($pname, 0,2,0);
                $this->SetVariableProfileDigits ($pname, 0);                    
                $this->SetVariableProfileAssociation($pname, 0, "Geschlossen","" , -1);
                $this->SetVariableProfileAssociation($pname, 1, "Gekippt", "", 255);
                $this->SetVariableProfileAssociation($pname, 2, "Geöffnet", "", 65280);                    
                break;
            case "PowerLockBefehl";
                $this->CreateVariableProfile($pname, 1);
                $this->SetVariableProfileIcon ($pname, "PowerLockStatus");
                $this->SetVariableProfileText ($pname, "","");
                $this->SetVariableProfileValues ($pname, 0,2,0);
                $this->SetVariableProfileDigits ($pname, 0);                    
                $this->SetVariableProfileAssociation($pname, 0, "Verriegeln","" , -1);
                $this->SetVariableProfileAssociation($pname, 1, "Entriegeln", "", 255);
                $this->SetVariableProfileAssociation($pname, 2, "Öffnen", "", 65280);                    
                break;
            case "PowerLockStatus";
                $this->CreateVariableProfile($pname, 1);
                $this->SetVariableProfileIcon ($pname, "PowerLockStatus");
                $this->SetVariableProfileText ($pname, "","");
                $this->SetVariableProfileValues ($pname, 0,2,0);
                $this->SetVariableProfileDigits ($pname, 0);                    
                $this->SetVariableProfileAssociation($pname, 0, "Schloss verriegelt","" , -1);
                $this->SetVariableProfileAssociation($pname, 1, "Schloss entriegelt", "", 255);
                $this->SetVariableProfileAssociation($pname, 2, "Tür offen", "", 65280);                    
                break;
            case "Button";
                $this->CreateVariableProfile($pname, 0);
                $this->SetVariableProfileAssociation($pname, 0, "Ja","",0xffffff);
                $this->SetVariableProfileAssociation($pname, 1, "Nein","",0xffffff);
                break;
            case "Motion";
                $this->CreateVariableProfile($pname, 0);
                $this->SetVariableProfileAssociation($pname, 0, "Ruhe","",0xffffff);
                $this->SetVariableProfileAssociation($pname, 1, "Bewegung","",0xffffff);
                break;
            case "Pressure";
                $this->CreateVariableProfile($pname, 2);
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileText($pname,'',' mbar');
                $this->SetVariableProfileIcon($pname,"Gauge");
                break;      
            case "CO2";
                $this->CreateVariableProfile($pname, 1);
                $this->SetVariableProfileText($pname,'',' ppm');
                $this->SetVariableProfileIcon($pname,"Gauge");
                $this->SetVariableProfileValues ($pname, 250, 2000, 0);
                break;                                    
            case "mode.HM";
                $this->CreateVariableProfile($pname, 1);
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 5, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, 0, "Automatisch", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, 1, "Manuell", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 2, "Profil1", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 3, "Profil2", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 4, "Profil3", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 5, "Urlaub", "", 0x5e2187); //P-Name, Value, Assotiation, Icon, Color
                break;		
            case "Rainfall":
                $this->CreateVariableProfile($pname, 2);
                $this->SetVariableProfileIcon ($pname, "Rainfall");
                $this->SetVariableProfileText ($pname, ""," mm");
                //IPS_SetVariableProfileValues ($pname, 0,0,0);
                $this->SetVariableProfileDigits ($tpname, 1);			
                break;
            case "Euro":
        		$this->CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		        $this->SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
		        $this->SetVariableProfileText($pname,'','Euro');
                break;
            case "MByte":
        		$this->CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		        $this->SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
		        $this->SetVariableProfileText($pname,'',' MByte');
                break;
	        case "AusEinAuto":
                $this->CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 2, "Auto", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
                break;
	        case "AusEin":
                $this->CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                break;
	        case "AusEin-Boolean":
                $this->CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, false, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, true, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                break;
	        case "InActive":
                $this->CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, false, "Inactive", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, true, "Active", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                break;                
	        case "NeinJa":
                $this->CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, 0, "Nein", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, 1, "Ja", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                break;
	        case "Null":
                $this->CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 0, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, 0, "Null", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                break;
	        case "SchlafenAufwachenMunter":
                $this->CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, 0, "Schlafen", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, 1, "Aufwachen", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 2, "Munter", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
                break;
	        case "AusEinAutoP1P2P3P4":
                $this->CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 6, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 2, "Auto", "", 0x615c6e); //P-Name, Value, Assotiation, Icon, Color             
                $this->SetVariableProfileAssociation($pname, 3, "Profil 1", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 4, "Profil 2", "", 0x3ec127); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 5, "Profil 3", "", 0x5ea147); //P-Name, Value, Assotiation, Icon, Color
                $this->SetVariableProfileAssociation($pname, 6, "Profil 4", "", 0x7ea167); //P-Name, Value, Assotiation, Icon, Color
                break;
            case "Minuten":
                $this->CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileText($pname,'','Min');
                break;
            case "Zaehlt":
                $this->CreateVariableProfile($pname, 0); 
                $this->SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                $this->SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
                $this->SetVariableProfileAssociation($pname, false, "Idle", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
                $this->SetVariableProfileAssociation($pname, true, "Active", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
                break;
            case "kWh":
                $this->CreateVariableProfile($pname, 2); 
                $this->SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
                $this->SetVariableProfileText($pname,'','kWh');
                break;
            case "Wh":
                $this->CreateVariableProfile($pname, 2); 
                $this->SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
                $this->SetVariableProfileText($pname,'','Wh');
                break;
            case "kW":
                $this->CreateVariableProfile($pname, 2); 
                $this->SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
                $this->SetVariableProfileText($pname,'','kW');
                break;
            default:
                echo "      profileOps::createKnownProfilesByName, Profil ".$pname." existiert nicht, oder Aufforderung zum update. ";
                break;
            }
        }


    /* profileOps::synchronizeProfiles, in zB CustomComponent_Installation verwendet
     * die vollautomatische Function zum synchronisieren von Profilen, lokal oder remote 
     * wenn ein Profil aus Defaultwerten erzeugt werden soll (new) wird createKnownProfilesByName aufgerufen
     * verwendet in CustomComponents
     *
     *      server      ist der Remote Server, kann auch Local/false annehmen
     *      profilname  ist ein Array [pname => masterName]
     *                  pname ist das neue Profil das auf Basis von masterName angelegt werden soll
     *                  masterName kann new, update oder ein bekannter Profilname sein
     *  Beispiel:
     *     
     	$profilname     = array("Temperatur"=>"new",
                                "TemperaturSet"=>"new",
                                "Humidity"=>"new",
                                "Switch"=>"new",
                                "Button"=>"new",
                                "Contact"=>"new",
                                "Motion"=>"new",
                                "Pressure"=>"Netatmo.Pressure",
                                "CO2"=>"Netatmo.CO2",
                                "mode.HM"=>"new");
     *
     */

    function synchronizeProfiles($profilname,$debug=false)
        {
        if ($debug) echo "profileOps::synchronizeProfiles aufgerufen für folgende Profile: ".json_encode($profilname)."\n";
        foreach ($profilname as $pname => $masterName)
            {
            if (( ($this->VariableProfileExists($pname) == false) && ($masterName=="new") ) || ($masterName=="update") )
                {
                if ($debug) 
                    {
                    if ( ($this->VariableProfileExists($pname) == false) && ($masterName=="new") ) echo "   Profil $pname existiert nicht und Aufforderung zum neu anlegen.\n";
                    else echo "   Profil $pname update.\n";
                    }
                $this->createKnownProfilesByName($pname,$debug);               //lokal
                }
            elseif ($masterName == "new") echo "   Profil ".$pname." existiert bereits, nichts weiter machen.\n";          // wenn das Profil existiert kommt man hier vorbei
            elseif (IPS_VariableProfileExists($masterName) == false)            
                {
                if ($this->VariableProfileExists($pname)) 
                    {
                    $target=IPS_GetVariableProfile ($pname);
                    $master=array();
                    $masterName="new";                              // nicht vorhanden braucht auch einen Namen
                    $targetName=$target["ProfileName"];
                    $this->compareProfiles($master, $target,$masterName,$targetName,$debug);      // nur die lokalen Profile anpassem, geht auch Remote
                    }
                else 
                    {
                    if ($debug) echo "Zu übernehmendes Profil $masterName existiert nicht, anstelle vorbereitetes default Profil nehmen.\n";
                    $this->createKnownProfilesByName($pname);
                    }
                }
            else    
                {
                if ($this->VariableProfileExists($pname) == false) 
                    {
                    if ($debug) echo "Profil $pname existiert noch nicht und erhält Aufruf zum Synchronisieren mit einem vorhandenen Profil namens $masterName.\n";
                    $targetName=$pname;
                    $target=array();
                    }
                else
                    {
                    if ($debug) echo "   Profil ".$pname." existiert bereits und erhält Aufruf zum Synchronisieren mit einem vorhandenen Profil namens $masterName.\n";  
                    $target=$this->GetVariableProfile ($pname);                            // pname wird synchronisiert oder upgedatet
                    $targetName=$target["ProfileName"];
                    }

                $master=IPS_GetVariableProfile ($masterName);                       // mastername ist die Quelle zum Synchronisieren
                $masterName=$master["ProfileName"];         // sonst nicht rekursiv möglich
                $this->compareProfiles($master, $target,$masterName,$targetName,$debug);      // nur die lokalen Profile anpassem, geht auch Remote, false kein demo mode 
                }
            } 
        }

    /* Anlegen und Synchronisieren von Profilen, Aufruf geht auch rekursiv
     * soll auch gleich Remote gehen
     * verwendet in CustomComponent und RemoteAccess und hier in synchronize Profiles
     *
     *  server      Remote Server oder Local/false, damit wird rpc false, sonst der Server Zugriffsname
     *  master      kann auch ein leeres array sien
     *
     */

    function compareProfiles($master,$target,$masterName,$targetName, $debug=false)
        {
        if ($debug) echo "compareProfiles aufgerufen.\n";
        $prefix=true; $minvalue=true;
        foreach ($master as $index => $entry)           // kann ach ein leeres array sein, dann wird hier übersprungen und nix gemacht
            {
            if (is_array($master[$index])) 
                {
                switch ($index)
                    {
                    case "Associations":
                        if ( (isset($target[$index])) === false) 
                            {
                            $target[$index]=array();
                            echo "$index ist ein Array. Im Target neu anlegen. ".sizeof($master[$index])." != ".sizeof($target[$index])."\n";
                            }
                        if ( (sizeof($master[$index])) != (sizeof($target[$index])) ) 
                            {
                            if (sizeof($target[$index])==0)
                                {
                                //echo "Associations im Target neu anlegen.\n";
                                //print_r($master[$index]);
                                foreach ($master[$index] as $entry)
                                    {
                                    $this->SetVariableProfileAssociation($targetName, $entry["Value"], $entry["Name"], $entry["Icon"], $entry["Color"]);
                                    }
                                }
                            else echo "Associations nicht gleich gross\n";
                            }
                        break;
                    default:
                        echo "sub array $index\n";
                        if (isset($target[$index])) $this->compareProfiles($master[$index], $target[$index],$masterName,$targetName, $debug);
                        else echo "Target Index not available\n";
                        break;
                    }
                }
            elseif ( ((isset($target[$index])) === false) || ( (isset($target[$index])) && ($master[$index] != $target[$index]) ))      //entweder gibts den target Index gar nicht oder er ist nicht gleich dem master
                {
                if ( (isset($target["ProfileType"])) === false)
                    {
                    //echo "$index: Profil noch nicht vorhanden. Als ersten Befehl CreateVariableProfil durchführen.\n";
                    $this->CreateVariableProfile($targetName, $master["ProfileType"]);
                    $target["ProfileName"]=$targetName;
                    $target["ProfileType"]=$master["ProfileType"];   
                    }
                switch ($index)
                    {
                    case "ProfileName":
                    case "ProfileType":
                        //echo "Variable bereits mit $targetName und Typ ".$master["ProfileType"]." erstellt.\n";
                        break;
                    case "MinValue":
                    case "MaxValue":
                    case "StepSize":
                        if ($minvalue)
                            {
                            $this->SetVariableProfileValues($targetName, $master["MinValue"], $master["MaxValue"], $master["StepSize"]);
                            $minvalue=false;
                            }
                        break;
                    case "Digits":
                        $this->SetVariableProfileDigits($targetName, $master["Digits"]);
                        break;
                    case "Icon":
                        $this->SetVariableProfileIcon($targetName, $master["Icon"]);
                        break;
                    case "Prefix":
                    case "Suffix":
                        if ($prefix)
                            {
                            $this->SetVariableProfileText ($targetName, $master["Prefix"], $master["Suffix"]);
                            $prefix=false;
                            }
                        break;
                    default:
                        if (isset($target[$index])) echo "    ".str_pad($index,20)."  $master[$index]  $target[$index] \n";
                        else echo "    ".str_pad($index,20)."  $master[$index]  $targetName Index $index unknown \n";
                        break;
                    }
                }
            }
        }

    }

/***************************************************************************************************************************
 *
 * versammelt Operationen in einer Klasse die die Darstellung der Webfronts betrifft
 * 
 *  __construct                     gerade einmal zebtral debug aktivieren
 *  createActionProfileByName       erzeugt ein Profil für eine Zeile aus einzelnen Buttons die ein Script initieren, mit und ohne Selector, ohne ist kompakt
 *  getActionProfileByName
 *  createButtonProfileByName       erzeugt ein profil für einen einzelnen Button
 *  createSelectButtons             eine Reihe von Buttons anlegen, die untereinander ein Auswahlfeld ergeben
 *      setButtonColors                 set color of Button
 *      setSelectButtons                save Button Parameters as configuration in class for other functions
 *      setConfigButtons                save other default parameters for Button in class
 *  getSelectButtons
 *  selectButton
 *
 */

class webOps
    {

    var $pnames=array();
    protected $categoryId;

    // Default Values
    var $order=10;                                  // position of Buttons
    var $color       = 0x235643;                        // color of button
    var $colorSelect = 0x897654;                  // color of selected button
    var $modulName   = "";                      // Before Profilname to differ between modules

    // Navigation variables
    protected $variableTypeOffsetID,$variableTimeOffsetID,$variableTimeCount;
    protected $variablePeriodCountID,$variablePeriodYearID,$variablePeriodMonthID,$variablePeriodWeekID,$variablePeriodDayID,$variablePeriodHourID;
    protected $associationsWithIndex=array();


    var $debug=false;

    function __construct($debug=false)
        {
        $this->debug=$debug;
        if ($debug) echo "Aufruf class webOps\n";    
        }

    /* webOps::createActionProfileByName
     * SpezialProfile für Action Aufrufe aus dem Webfront, erzeugt eine Zeile aus einzelnen Buttons die ein Script initieren 
     *  pname ist der Name
     *  nameID ein Array aus einzelnen Einträgen
     *  style ist 1 wenn ein Selector mit der Auswahl von Defaultwerten aufgerufen werden kann
     * die Farbe wird automatisch bestimmt
     *
     * Beispielaufruf:
     *    $pname="DeviceTables";                                        // Profilname
     *    $nameID=["DeviceType","Rooms"];                               // Auswahlfelder
     *    $webOps->createActionProfileByName($pname,$nameID,0);         // erst das Profil, dann die Variable, 0 ohne Selektor
     *    $actionDeviceTableID          = CreateVariableByName($hardwareStatusCat,"ShowTablesBy", 1,$pname,"",1010,$scriptIdImproveDeviceDetection);                 
     *
     */
    function createActionProfileByName($pname,$nameIDs,$style=1,$colorSet=false,$debug=false)
        {
        // Farben für die Buttons annehmen, fehlende oder nicht zueinander passende Werte schätzen
        if (($colorSet===false) || (is_array($colorSet)==false))
            {
            $i=0;
            if ($colorSet===false) $colorSet=1040;
            $color=array();
            foreach ($nameIDs as $index => $name)
                {
                $color[$index] = ($colorSet+200*$i);
                $i++;       // sonst wird letzter Wert überschrieben
                }
            }
        else
            {
            $i=0; $colorStart=1040;
            foreach ($nameIDs as $index => $name)
                {
                if (isset($colorSet[$index])) $color[$index] = $colorSet[$index];
                else $color[$index] = $colorStart+=200*$i;
                $colorStart = $color[$index];
                $i++;       
                }
            }  
        // Profil erstellen      
        $create=false;
        $profileOps = new profileOps();                 //lokal
        $namecount=count($nameIDs);
        if (IPS_VariableProfileExists($pname) == false)
            {
            //Var-Profil existiert noch nicht, neu erstellen
            IPS_CreateVariableProfile($pname, 1);                           // PName, Typ 0 Boolean 1 Integer 2 Float 3 String 
            IPS_SetVariableProfileDigits($pname, 0);                        // PName, Nachkommastellen
            $create=true;
            }
        // Rest der Profilkonfiguration sicherheitshalber immer überarbeiten             
        if ($namecount>1) IPS_SetVariableProfileValues($pname, 0, ($namecount-1), $style);      //PName, Minimal, Maximal, Schrittweite
        $i=0;
        $profile=array();
        foreach ($nameIDs as $index => $name)
            {
            $profile[$i]=array("Value"=>$i,"Name"=>$name,"Icon"=>"","Color"=>$color[$index],);
            $i++;       // sonst wird letzter Wert überschrieben
            }
        $profileOps->UpdateVariableProfileAssociations($pname,$profile); 
        if ($debug)          
            {
            //print_r($profile);
            if ($create) echo "Aktions Profil ".$pname." erstellt.\n";
            else echo "Aktions Profil ".$pname." überarbeitet.\n";		
            }
        }

    /* webOps::
     * die umgekehrte Funktion, Profil analysieren, zuordnung Einzelbuttons aus dem Profil
     * wenn style false einfach das Association array mit Icon und Color ausgeben
     * wenn style true das Assciation array verkürzen auf Value=>Name verkuerzen
     */
    function getActionProfileByName($pname,$style=false)
        {
        $profileOps = new profileOps();                 //lokal
        if (IPS_VariableProfileExists($pname) == false) return (false);
        $associations = $profileOps->GetVariableProfileAssociations($pname);            
        if ($style===false) return($associations);
        else
            {
            $result=array();
            foreach ($associations as $index => $profil) 
                {
                //echo "  ".$index."  ".$profil["Value"]."  ".$profil["Name"]."\n";
                $result[$profil["Value"]]=$profil["Name"];
                }
            return ($result);
            }
        }


	/* webOps::createProfileAssociations
     * Anlegen eines Profils mit Associations, Kopie von CreateProfile_Associations aus dem IPSInstaller, siehe auch createActionProfileByName
	 *
	 * der Befehl legt ein Profile an und erzeugt für die übergebenen Werte Assoziationen
	 *
	 * @param string $Name Name des Profiles
	 * @param string $Associations[] Array mit Wert und Namens Zuordnungen
	 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
	 * @param integer $Color[] Array mit Farbwerten im HTML Farbcode (z.b. 0x0000FF für Blau). Sonderfall: -1 für Transparent
	 * @param boolean $DeleteProfile Profile löschen und neu generieren
	 *
	 */
	function createProfileAssociations ($Name, $Associations, $Icon="", $Color=-1, $DeleteProfile=true) 
        {
		if ($DeleteProfile) {
			@IPS_DeleteVariableProfile($Name);
		}
		@IPS_CreateVariableProfile($Name, 1);
		IPS_SetVariableProfileText($Name, "", "");
		IPS_SetVariableProfileValues($Name, 0, 0, 0);
		IPS_SetVariableProfileDigits($Name, 0);
		IPS_SetVariableProfileIcon($Name, $Icon);
        $count=0;
        $associationWithIndex=array();
		foreach($Associations as $Idx => $IdxName) 
            {
            if (is_numeric($Idx)) $count=$Idx;
			if ($IdxName == "") 
                {
			    // Ignore
			    } 
            elseif (is_array($Color)) 
                {
				IPS_SetVariableProfileAssociation($Name, $count, $IdxName, "", $Color[$Idx]);
		        } 
            else 
                {
				IPS_SetVariableProfileAssociation($Name, $count, $IdxName, "", $Color);
			    }
            $associationWithIndex[$IdxName]=$count;
            $count++;
		    }
        return($associationWithIndex);
	    }


    /* webOps::
     * SpezialProfile für Action Aufrufe aus dem Webfront 
     * verwendet von Guthabensteuerung, für einzelne Buttons zum Draufdrücken
     *      pname ist der Name des Button, der Link auf den Button sollte dann "" sein oder der Button Teil des Webfronts
     *      die Farbe wird optional übergeben
     *
     */

    function createButtonProfileByName($pname,$color=false)
        {
        if ($color===false) $color = $this->color;
        $create=false;
        $button=$pname;
        $pname = $this->modulName.$pname."Profil";
        if (IPS_VariableProfileExists($pname) == false)
            {
            //Var-Profil existiert noch nicht, neu erstellen
            IPS_CreateVariableProfile($pname, 1);                           // PName, Typ 0 Boolean 1 Integer 2 Float 3 String 
            IPS_SetVariableProfileDigits($pname, 0);                        // PName, Nachkommastellen
            $create=true;
            }
        // Rest der Profilkonfiguration sicherheitshalber immer überarbeiten             
        IPS_SetVariableProfileValues($pname, 0, 0, 0);      //PName, Minimal, Maximal, Schrittweite muss 0 sein für einen Button
        IPS_SetVariableProfileAssociation($pname, 0, $button, "", $color); //P-Name, Value, Assotiation, Icon, Color=grau
        if ($this->debug) 
            {
            if ($create) echo "Button $button mit Aktions Profil ".$pname." erstellt.\n";
            else echo "Button $button mit Aktions Profil ".$pname." überarbeitet.\n";	
            }
        return $pname;	
        }

    /* webOps::
     * eine Reihe von Buttons anlegen, die untereinander ein Auswahlfeld ergeben  
     * pnames ist ein array
     * es braucht die categoryId und scriptId, werden auch in die class gespeichert
     *
     */

    function createSelectButtons($pnames,$categoryId, $scriptId, $debug=false)
        {
        if ($this->debug) echo "createSelectButtons(".json_encode($pnames).",$categoryId,$scriptId   \n";
        $this->setSelectButtons($pnames,$categoryId);           // auch in der class speichern
        $result=array();
        if ($debug || $this->debug) 
            {
            echo "createSelectButtons with following Names :\n";
            foreach ($pnames as $pname)
                {
                if (is_array($pname)) return (false);               // nur Name des Profiles erlaubt
                echo $pname." ";
                }
            echo "\n";
            }
        $order=$this->order;
        $id=0;
        foreach ($pnames as $pname)
            {
            $profilName         = $this->createButtonProfileByName($pname);
            $updateButtonID     = CreateVariableByName($categoryId,$pname, 1,$profilName,"",$order,$scriptId);           // button profile is Integer
            $result[$id]["ID"]     = $updateButtonID;           // button profile is Integer
            $result[$id]["NAME"]   = $pname; 
            $id++;
            $order += 10;
            }
        return ($result);
        }

    /* webOps::
     * set color of Button
     */

    function setButtonColors($color = 0x235643, $colorSelect = 0x897654)                  // color of button and selected button
        {
        $this->color       = $color;
        $this->colorSelect = $colorSelect;
        }

    /* webOps::
     * save Button Parameters as configuration in class for other functions
     *
     */
    function setSelectButtons($pnames,$categoryId)
        {
        $this->pnames       = $pnames;
        $this->categoryId   = $categoryId;
        }

    /* webOps::
     * save other default parameters for Button in class
     */
    function setConfigButtons($order=10,$modulName="")
        {
        if ($this->debug) echo "setConfigButtons($order,$modulName)   \n";
        $this->order       = $order;
        $this->modulName   = $modulName;
        }


    /* webOps::
     * get the Button Id
     * needs pnames, categoryId stored in class by setSelectButtons used by createSelectButtons
     */
    function getSelectButtons($debug=false)
        {
        $result=array();
        if ($debug) 
            {
            echo "getSelectButtons with following Names :\n";
            foreach ($this->pnames as $pname)
                {
                echo $pname." ";
                }
            echo "\n";
            echo "Button Data is stored here : ".$this->categoryId."\n";
            }
        $id=0;
        foreach ($this->pnames as $pname)
            {
            $result[$id]["ID"]     = IPS_GetObjectIdByName($pname,$this->categoryId);           // button profile is Integer
            $result[$id]["NAME"]   = $pname; 
            $id++;
            }
        return ($result);
        }

    /* webOps::
     * select the Button Id
     * verändert in der Profildarstellung für die jeweilige Association die Farbe
     */
    function selectButton($select)
        {
        $buttonIds = $this->getSelectButtons();
        foreach ($buttonIds as $index => $buttonId)
            {
            if ($index == $select) IPS_SetVariableProfileAssociation($buttonId["NAME"]."Profil", 0, $buttonId["NAME"], "", $this->colorSelect); 
            else IPS_SetVariableProfileAssociation($buttonId["NAME"]."Profil", 0, $buttonId["NAME"], "", $this->color); 
            }
        }

    /* webOps::createNavigation
     * alle haben die selbe category Variable, aufpassen
     *
     *
     */
    public function createNavigation($categoryId,$scriptIdActionScript=false,$debug=false)
        {
        if ($debug) echo "createNavigation($categoryId,$scriptIdActionScript ..) \n";
        $this->categoryId   = $categoryId;
        $categoryId_Navigation  	= CreateCategory('Navigation', $categoryId, 21);

        $pname='AD_PeriodAndCount';
        $associationsPeriodAndCount  = array(
                                    'HOUR'      => 'Stunde',
                                    'DAY'       => 'Tag',
                                    'WEEK'      => 'Woche',
                                    'MONTH'     => 'Monat',
                                    'YEAR'      => 'Jahr',
                                    'SEPARATOR' => ' ',
                                    'MINUS'     => '-',
                                    'VALUE'     => '1',
                                    'PLUS'      => '+',
                                    );
        if ($scriptIdActionScript !== false)
            {
            $this->associationsWithIndex = $this->createProfileAssociations ($pname,   $associationsPeriodAndCount, "Clock");
            $this->variablePeriodCountID = CreateVariableByName($categoryId,              'PeriodAndCount',    1 , "AD_PeriodAndCount", false, 20, $scriptIdActionScript,1);      // kein Icon zum definieren, Defaultwert nur beim anlegen
            //SetValue($this->variablePeriodCountID,1);           // Set Defaultwert muss extern geschehen, wenn die Variable das erste Mal angelegt
            }
        else 
            {
            $this->variablePeriodCountID = @IPS_GetVariableIDByName('PeriodAndCount',$categoryId);
            /*$associations=IPS_GetVariableProfile($pname)["Associations"];
            //print_R($associations);
            $this->associationsWithIndex = $associationsPeriodAndCount;             // Index ist PLUS, MINUS etc
            $assos=array();
            foreach ($associations as $association)
                {
                $assos[$association["Name"]]=$association["Value"];                        // 2=7
                }
            print_r($assos);
            foreach ($this->associationsWithIndex as $index=> $entry)
                {
                if (isset($assos[$entry])) $this->associationsWithIndex[$index] =  $assos[$entry];    
                }*/
            $this->associationsWithIndex = array(
                                    'HOUR'      => 0,
                                    'DAY'       => 1,
                                    'WEEK'      => 2,
                                    'MONTH'     => 3,
                                    'YEAR'      => 4,
                                    'SEPARATOR' => 5,
                                    'MINUS'     => 6,
                                    'VALUE'     => 7,
                                    'PLUS'      => 8,
                                    );
            }

        // function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
        //$this->variableTypeOffsetID  = CreateVariableByName($categoryId,'TypeAndOffset',    1 , false, false, 10, $scriptIdActionScript,  IPSRP_TYPE_KWH, 'Clock');
        $this->variableTimeOffsetID  = CreateVariableByName($categoryId,"TimeOffset",  1, false,false,   40);
	    $this->variableTimeCountID   = CreateVariableByName($categoryId,"TimeCount" ,  1, false,false,   50);

        $this->variablePeriodYearID  = CreateVariableByName($categoryId_Navigation,   "PeriodYearLast",    1 , false,false,   5000);
        $this->variablePeriodMonthID = CreateVariableByName($categoryId_Navigation,   "PeriodMonthLast",   1 , false,false,   5010);
        $this->variablePeriodWeekID  = CreateVariableByName($categoryId_Navigation,   "PeriodWeekLast",    1 , false,false,   5020);
        $this->variablePeriodDayID   = CreateVariableByName($categoryId_Navigation,   "PeriodDayLast",     1 , false,false,   5030);
        $this->variablePeriodHourID  = CreateVariableByName($categoryId_Navigation,   "PeriodHourLast",    1 , false,false,   5040);


        return($this->variablePeriodCountID);
        }

    /* webOps::doNavigation 
     * eine ungewöhnliche aber sehr kompakte Darstellungsform.
     * categoryId stored in class by setSelectButtons used by createSelectButtons
     * Es gibt zwei Profile die individuell zur Runtime angepasst werden, das bedeutet auch einen Update der Profil Associations, damit der neue Wert angezeigt werden kann
     * Bearbeitet werden dadurch folgende Einstellungen
     *      $variableIdCount  aus der Zeile PeriodAndCount
     *      $variableIdOffset aus der Zeile TypeAndOffset
     * die anderen Werte werden geradlinig transparent abgespeichert
     *
     * anhand des Wertes der beiden Profile kann erkannt werden in welcher Variable man ist 
     *
     */

    public function doNavigation($variableId, $value)
        {
        /* Wert 10 ist Stunde, 11 Tag, 12 Woche */
        $lastValue = GetValue($variableId);
        SetValue($variableId, $value);
        $variableIdCount = $this->variableTimeCountID;
        //print_R($this->associationsWithIndex);
        $restoreOldValue = false;
        $updateLastValue = false;
        Switch($value) {
            case $this->associationsWithIndex["MINUS"]:
                if (GetValue($variableIdCount) > 1) {
                    SetValue($variableIdCount, GetValue($variableIdCount) - 1);
                }
                IPS_SetVariableProfileAssociation('AD_PeriodAndCount', $this->associationsWithIndex["VALUE"], GetValue($variableIdCount), "", -1);
                $updateLastValue = true;
                $restoreOldValue = true;
                break;
            case $this->associationsWithIndex["PLUS"]:
                SetValue($variableIdCount, GetValue($variableIdCount) + 1);
                IPS_SetVariableProfileAssociation('AD_PeriodAndCount', $this->associationsWithIndex["VALUE"], GetValue($variableIdCount), "", -1);
                $restoreOldValue = true;
                $updateLastValue = true;
                echo "plus";
                break;
            /*case "PREV":
                SetValue($variableIdOffset, GetValue($variableIdOffset) - 1);
                IPS_SetVariableProfileAssociation('IPSReport_TypeAndOffset', IPSRP_OFFSET_VALUE, GetValue($variableIdOffset), "", -1);
                $restoreOldValue = true;
                break;
            case "NEXT":
                if (GetValue($variableIdOffset) < 0) {
                    SetValue($variableIdOffset, GetValue($variableIdOffset) + 1);
                }
                IPS_SetVariableProfileAssociation('IPSReport_TypeAndOffset', IPSRP_OFFSET_VALUE, GetValue($variableIdOffset), "", -1);          // es wird nur eine Association umgestellt auf den aktuellen Wert
                $restoreOldValue = true;
                break;*/
            case $this->associationsWithIndex["VALUE"]:
            case $this->associationsWithIndex["SEPARATOR"]:
                SetValue($variableId, $lastValue);
                break;
            case $this->associationsWithIndex["HOUR"]:
                SetValue($variableIdCount, GetValue($this->variablePeriodHourID));
                IPS_SetVariableProfileAssociation('AD_PeriodAndCount', $this->associationsWithIndex["VALUE"], GetValue($variableIdCount), "", -1);
                break;
            case $this->associationsWithIndex["DAY"]:
                SetValue($variableIdCount, GetValue($this->variablePeriodDayID));
                IPS_SetVariableProfileAssociation('AD_PeriodAndCount', $this->associationsWithIndex["VALUE"], GetValue($variableIdCount), "", -1);
                break;
            case $this->associationsWithIndex["WEEK"]:
                SetValue($variableIdCount, GetValue($this->variablePeriodWeekID));
                IPS_SetVariableProfileAssociation('AD_PeriodAndCount', $this->associationsWithIndex["VALUE"], GetValue($variableIdCount), "", -1);
                break;
            case $this->associationsWithIndex["MONTH"]:
                SetValue($variableIdCount, GetValue($this->variablePeriodMonthID));
                IPS_SetVariableProfileAssociation('AD_PeriodAndCount', $this->associationsWithIndex["VALUE"], GetValue($variableIdCount), "", -1);
                break;
            case $this->associationsWithIndex["YEAR"]:
                SetValue($variableIdCount, GetValue($this->variablePeriodYearID));
                IPS_SetVariableProfileAssociation('AD_PeriodAndCount', $this->associationsWithIndex["VALUE"], GetValue($variableIdCount), "", -1);
                break;
            default:
                // other Values
            }
        if ($updateLastValue)
            {
            switch ($lastValue)
                {
                case $this->associationsWithIndex["HOUR"]:
                    SetValue($this->variablePeriodHourID,GetValue($variableIdCount));
                    break;
                case $this->associationsWithIndex["DAY"]:
                    SetValue($this->variablePeriodDayID,GetValue($variableIdCount));
                    break;
                case $this->associationsWithIndex["WEEK"]:
                    SetValue($this->variablePeriodWeekID,GetValue($variableIdCount));
                    break;
                case $this->associationsWithIndex["MONTH"]:
                    SetValue($this->variablePeriodMonthID,GetValue($variableIdCount));
                    break;
                case $this->associationsWithIndex["YEAR"]:
                    SetValue($this->variablePeriodYearID,GetValue($variableIdCount));
                    break;
                default:
                    echo "Dont know";
                    break;
                }    
            }
        if ($restoreOldValue)  
            {
            //echo "restore $lastValue";
            IPS_Sleep(200);
            SetValue($variableId, $lastValue);
            }
        //echo "Neuer Wert ist ".GetValue($variableId)."\n";
        }

    /* webOps::getNavPeriode
     * alle haben die selbe category Variable, aufpassen
     *
     *
     */
    public function getNavPeriode($variableId,$debug=false)
        {
        $periode=false;
        $lastValue = GetValue($variableId);            
            switch ($lastValue)
                {
                case $this->associationsWithIndex["HOUR"]:
                    $periode=GetValue($this->variablePeriodHourID)*60*60;
                    break;
                case $this->associationsWithIndex["DAY"]:
                    $periode=GetValue($this->variablePeriodDayID)*24*60*60;
                    break;
                case $this->associationsWithIndex["WEEK"]:
                    $periode=GetValue($this->variablePeriodWeekID)*7*24*60*60;
                    break;
                case $this->associationsWithIndex["MONTH"]:
                    $periode=GetValue($this->variablePeriodMonthID)*30*24*60*60;
                    break;
                case $this->associationsWithIndex["YEAR"]:
                    $periode=GetValue($this->variablePeriodYearID)*365*24*60*60;
                    break;
                default:
                    echo "Dont know";
                    break;
                }
        return($periode); 
        }

    }

/***************************************************************************************************************************
 *
 * versammelt Archive Operationen in einer Klasse
 * sehr mächtige Funktionen
 *
 * die Klasse kann bei der Erzeugung auf die Bearbeitung einer Variable eingeschränkt werden
 * abhängig davon wird eine Liste aller archivierten Variablen oder die Konfiguration eines Wertes ausgegeben
 * verwendet class statistics
 *  setConfiguration        Abgleich der Konfiguration
 *
 * dann kommen die archive operations, functions für die bearbeitung der Daten in den Archiven
 *
 *  __construct             alle oder ein Archive auswählen, in aggregationConfig speichern
 *  getConfig               aggregationConfig von AC_GetAggregationVariables ausgeben, für alle oder für eine
 *  getArchiveID            archiveID
 *  getSize                 aggregationConfig["RecordCount"]
 *  getStatus               in einer lesbaren Zeile den Status aus der Archive Config einer Variable ausgeben
 *
 *  getComponentValues      für alle oder einzelne Archive anzeigen wieviele Daten in einer Zeitspanne gelogged wurden
 *  quickStore
 *  showValues              ein oder mehrere Archive als echo ausgeben
 *  lookforValues           nach einem historischen Wert mit einem bestimmten Zeitstempel suchen
 *  getArchivedValues       die Daten eines Archivs holen, Zeitspanne oder alle, keine Restriktionen, inklusive manueller Aggregation
 *  manualDailyAggregate    tägliche Aggregation von geloggten Daten, aktuell Energiedaten
 *  manualDailyEvaluate
 *
 *  getValues               holt sich die Daten und analysiert sie, die generelle Funktion
 *  analyseValues           holt sich die Daten und analysiert sie, hier geht man bereits von einer geordneten Struktur aus
 *
 *  alignScaleValues
 *  addInfoValues
 *  configAggregated
 *  configStartEndTime
 *
 *  cleanupStoreValues      Daten entsprechend der Config analysieren und bereinigen
 *  processOnData
 *  calculateSplitOnData
 *  prepareSplit
 *  countperIntervalValues
 *  filterNewData
 *
 *  setConfigForAddValues   Konfiguration vereinheitlichen
 *  addValuesfromCsv
 *
 *
 *  
 *
 ***************************************************************************************************************************************/

class archiveOps
    {

    private $archiveID,$ipsOps;
    private $oid;
    public $result;                 // das Array mit den Werten die verarbeitet werden
    public $warnings;               // noch ein array für andere Beobachtungen

    /* minimum init needed
     * wenn Defaultparameter Übergabe , construct liest die OIDs aller Datenwerte mit Archivfunktion in die aggregationConfig ein  
     */
    function __construct($oid=false)
        {
        $this->archiveID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        $this->ipsOps = new ipsOps();

        $result = AC_GetAggregationVariables($this->archiveID,false);
        $this->aggregationConfig=array();
        $this->oid=false;
        if ($oid===false) $this->aggregationConfig=$result;
        else
            {
            $this->oid=$oid;
            foreach ($result as $entry)
                {
                if ($entry["VariableID"]==$oid) $this->aggregationConfig=$entry;    
                }
            }

        // Wertespeicher initialisieren 
        $this->result=array();
        // Beobachtungen Speicher initialisieren
        $this->warnings=array();
        }

    /* aggregationConfig von AC_GetAggregationVariables ausgeben, für alle oder für eine
     *
     */
    public function getConfig()
        {
        return($this->aggregationConfig);
        }

    /* archiveID zurückgeben
     *
     */
    public function getArchiveID()
        {
        return($this->archiveID);
        }

    /* 
     *
     */
    public function getSize()
        {
        return($this->aggregationConfig["RecordCount"]);
        }

    /* archiveOps, getStatus in einer lesbaren Zeile den Status aus der Archive Config einer oder aller Variablen ausgeben 
     *
     */
    public function getStatus($oid=false)
        {
        if ($oid===false)
            {
            $noExist=0;
            //print_R($this->aggregationConfig);
            if (isset($this->aggregationConfig["VariableID"]))
                {
                $result = "Anzahl: ".$this->aggregationConfig["RecordCount"]." Erster Wert: ".date("d.m.Y H:i:s",$this->aggregationConfig["FirstTime"])." Letzter Wert: ".date("d.m.Y H:i:s",$this->aggregationConfig["LastTime"])."\n";
                }
            else
                {            
                foreach ($this->aggregationConfig as $index => $config)
                    {
                    $oid = $config["VariableID"];
                    if (IPS_VariableExists($oid)) $name=IPS_GetName($oid);
                    else 
                        {
                        $name = "!does not exist";
                        $noExist++;
                        }
                    echo str_pad($index,6)."$oid $name \n";
                    }
                $index++;
                $result = "Archive: Insgesamt $index Einträge, davon haben $noExist Einträge keinen Objektnamen mehr.\n";                
                }
            return ($result);
            }
        else
            {
            foreach ($this->aggregationConfig as $entry)
                {
                if ($entry["VariableID"]==$oid) 
                    {
                    $entry;
                    $result = "Anzahl: ".str_pad($entry["RecordCount"],8," ", STR_PAD_LEFT)." Erster Wert: ".date("d.m.Y H:i:s",$entry["FirstTime"])." Letzter Wert: ".date("d.m.Y H:i:s",$entry["LastTime"]);
                    return ($result);                        
                    }
                }                
            }
        return (false);
        }

    /*************************************************************************************
    * archiveOps::getComponentValues
    * alle OIDs die im Array von Component angeführt sind ausgeben
    * das ist eine besonders hilfreiche Ausgabe, derzeit nur in send_status für die historischen Werte verwendet
    * wir suchen geloggte Werte in einem bestimmten Zeitintervall zur besseren Orientierung
    * component ist ein eindimensionales array mit den OIDs aller gemessenen Werte die über eine ArchiveID verfügen
    * endtime ist entweder die erste Minute des aktuellen Tages, oder die aktuelle Uhrzeit 
    *
    ************************************************************************************************/

    function getComponentValues($componentInput=false,$logs=true,$debug=false)
        {
        /* für component ein array aus Werten oder einen einzelnen Wert zulassen */
        if (is_array($componentInput)==false) 
            {
            $component=array();
            if ($componentInput===false) $component[]=$this->oid;
            else $component[]=$componentInput;
            }
        else $component=$componentInput;
        /* für logs false true oder einen Integer Wert zulassen, integer ist die Anzahl der Log werte die ausgegeben wird */
        if ($logs>1) 
            {
            $maxLogsperInterval=$logs;              // zumindest die geforderte Anzahl an Logwerten anzeigen
            }
        else 
            {
            $logs=10;
            $maxLogsperInterval=1;                  // ein Wert reicht aus, max wäre 10
            }

        $result="";
    	$jetzt=time();
        /* endtime ist entweder die erste Minute des aktuellen Tages, oder die aktuelle Uhrzeit 
	    $endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt)); // letzter Tag 24:00
         */
        $endtime=$jetzt;
        if ($debug) echo "getComponentValues aufgerufen, endtime ist ".date("d.m.Y H:i:s",$endtime)."\n";  
	    $startday=$endtime-60*60*24*1; /* ein Tag */ 
	    $startweek=$endtime-60*60*24*7; /* 7 Tage, Woche */                    
  	    $startmonth=$endtime-60*60*24*30; /* 30 Tage, Monat */                    
  	    $startyear=$endtime-60*60*24*360; /* 360 Tage, Jahr */                    
        foreach ($component as $oid)
            {   /* Vorwerte ermitteln */
            $result .= "  ".str_pad(IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName($oid),60)." (".$oid.")  ";
		    $werte = @AC_GetLoggedValues($this->archiveID, $oid, $startday, $endtime, 0);
            if ($werte !== false)             
                {
                $count=sizeof($werte); $scale="Day";
                if ($count<$maxLogsperInterval)
                    {
    		        $werte = @AC_GetLoggedValues($this->archiveID, $oid, $startweek, $endtime, 0);
                    $count=sizeof($werte); $scale="Week";
                    if ($count<$maxLogsperInterval)
                        {
    	        	    $werte = @AC_GetLoggedValues($this->archiveID, $oid, $startmonth, $endtime, 0);
                        $count=sizeof($werte); $scale="Month";
                        if ($count<$maxLogsperInterval)
                            {
        	        	    $werte = @AC_GetLoggedValues($this->archiveID, $oid, $startyear, $endtime, 0);
                            $count=sizeof($werte); $scale="Year";
                            }
                        }    
                    }
	    	    $result .= $count." logged per ".$scale;
                }
            else $result .= "no logs available";    

            if ($logs && ($werte !== false) )
                {
                $logCount=0;
        	    foreach($werte as $wert)
						{
                        //if (is_numeric($wert['Value'])==false) print_R($wert);
                        if (is_numeric($wert['Value'])) $result .= "\n     ".date("d.m.y H:i:s",$wert['TimeStamp'])."   ".number_format($wert['Value'], 2, ",", "" );
                        else echo "Fehler, dieser Wert ist keine Zahl : ".json_encode($wert)." \n";
                        if (($logCount++)>$logs) break;
						}
                }        
            $result .= "\n";
            }
        return ($result);    
        }

    /* speichern wie wenn die Daten ein Archive wären
     * verwendet             
     *      $oid = $config["OIdtoStore"];          
     *
     */
    function quickStore($data,$config=array(),$debug=false)
        {
        if ($debug) echo "Use archiveOps::quickstore to enter data.\n";
        foreach ($data as $oidIndex => $result)
            {
            if (isset($config["OIdtoStore"])) $oid = $config["OIdtoStore"];             // oid wird auch ausserhalb geändert

            if ($debug)
                {
                if (@IPS_getName($oidIndex)) echo "Datenspeicher $oidIndex (".IPS_getName($oidIndex).")\n";                // oid muss nicht immer einen Namen haben
                else echo "Datenspeicher $oidIndex \n"; 
                }
            foreach ($result as $function => $entries)    
                {
                if ($debug) echo "  $function   ";
                $f=0;
                if ($function==="Values")  $this->result[$oid][$function]=$data[$oidIndex][$function];
                else $this->result[$oid]["Values"]=$data[$oidIndex];       
                }
            }
        //$this->result=$data;                   // ist nur der Pointer   
        }


    /* archiveOps::showValues
     * es werden die Werte in $werte oder die internen Werte (wenn false) genommen
     * sind damit eigentlich zwei Routinen aber in einer gemeinsamen Funktion: wenn werte ein array ist dann erster Teil der Funktion, wenn werte false ist zweiter Teil der Funktion
     * noch keine Gemeinsamkeiten aktiviert.
     *
     * wenn werte false, hier sind die meisten Entwicklungen passiert:
     *  Verwendung von this->result, function ist immer values, sonst Fehlermeldung
     *  interne Werte result haben Struktur oid->values->[Value,TimeStamp] || [Avg,TimeStamp]
     *
     * Ausgabe, echo von historischen Werten, funktioniert für aggregated und geloggte Werte
     *
     *
     * Ausgabe von historischen Werte mit Berücksichtigung von Duration
     * Problem es fehlt der Nullwert bei geloggten Werten mit Zero Unterdrückung. Kein Problem wenn bei einem Kühlschrank der Verbrauch auf 4W zurückgeht. Aber 0 wird nicht geloggt.
     * Eigentlich ganz einfache Lösung ist es Duration mitzuberücksichtigen. Timestamp[n]+Duration[n] != Timestamp[n+1] bedeutet es gibt Nullwerte dazwischen, einfach als zusätzlichen Wert aufnehmen
     *
     * Config Parameter (mit statistics->setConfiguration bereinigt)
     *      ShowTable
     *          output          [realTable,all]  entweder doecho oder ausgabe als arra mit allen Daten oder nur realTable
     *          align           den Zeitstempel auf Tages, Stunden, Minutenwerte runterrechnen um leichter Übereinstimmungen zu finden
     *          adjust
     *          calculate
     *          double          [mean,sum]  beim alignment, was passiert mit den doppelten Werten, default ist aufsummieren
     *
     *
     * es fehlt
     *      bei align gibt es noch nicht die Möglichkeit einen Abstand abzugeben, zB 30 Sekunden
     *
     */
    function showValues($werte=false,$configInput=array(),$debug=false)
        {
        $resultShow=array();           // Ausgabe von bereinigten Werten zur weiteren Bearbeitung
        $valuesAdd=array();
        $statistics = new statistics();   
        $config = $statistics->setConfiguration($configInput);   
        $doecho=true; $returnRealTable=false;
        if (isset($config["ShowTable"]["output"]))
            {
            if ($config["ShowTable"]["output"]=="realTable") { $returnRealTable=true; $doecho=false;  }  
            if ($config["ShowTable"]["output"]=="all")       { $doecho=false; }  
            }
        if (isset($config["ShowTable"]["doecho"]))
            {
            if ($config["ShowTable"]["doecho"]==true) { $doecho=true;  }  
            }
            
        // einfache Routine
        //foreach ($werte as $wert) echo "   ".date ("d.m.Y H:i:s",$wert["TimeStamp"])."   ".$wert["Value"]."\n";     

        // schönere Routine für echo Tabelle mit einem Wert, mit Avg oder Value, kein Defaultwert als Parameter, es wird eine Tabelle übergeben    
        if (is_array($werte))
            {
            if (count($werte)>1000)
                {
                echo "more than 1000 lines to show, break.\n";
                return (false);
                }
            if ($debug) echo "showValues mit einem Array als Input aufgerufen:\n";
            $timeStamp2=false; $timeStamp1=false;
            foreach ($werte as $wert) 
                {
                if (isset($wert["Avg"]))                // aggregierte Werte
                    {
                    //print_R($wert);
                    $timeStamp=$wert["TimeStamp"];
                    $value=$wert["Avg"];
                    echo "   ".date("d.m.Y H:i:s",$timeStamp);
                    if (isset($wert["Value"])) echo "   Val ".str_pad($wert["Value"],20);
                    echo "   Avg ".str_pad($value,20)."  Min ".$wert["Min"]." ".date("d.m.Y H:i:s",$wert["MinTime"])."  Max ".$wert["Max"]." ".date("d.m.Y H:i:s",$wert["MaxTime"])."   \n";   
                    }
                else                                    // geloggte Werte
                    {
                    // Richtung bestimmen
                    $timeStamp=$wert["TimeStamp"];
                    $value=$wert["Value"];
                    if ($timeStamp1 !==false) 
                        {
                        if ($timeStamp<$timeStamp1)  $direction="down";
                        else $direction="up";            
                        $timeStamp1=$timeStamp;
                        echo "   ".date ("d.m.Y H:i:s",$timeStamp1)."   ".str_pad($value,20)."     \n";   
                        // Zwischenwerte 
                        if ( ($timeStamp2 !==false) && ($timeStamp1 != $timeStamp2) ) echo "   ".date("d.m.Y H:i:s",$timeStamp2)."   ".str_pad(0,20)."     (calculated)\n";
                        if ($direction=="up") $timeStamp2=$wert["TimeStamp"]+$wert["Duration"];
                        else $timeStamp2=$wert["TimeStamp"]-$wert["Duration"];
                        }
                    else echo "   ".date ("d.m.Y H:i:s",$timeStamp)."   ".str_pad($value,20)."     \n";
                    }
                }
            if (isset($wert["Value"])) echo "   $timeStamp2        \n";
            }

        $getValues=array();
        $moreValues=false;
        if ($config["AggregatedValue"] != "Avg") 
            {
            if (is_array($config["AggregatedValue"])) $getValues=$config["AggregatedValue"];
            else $getValues[]=$config["AggregatedValue"];
            $moreValues=true;
            }

        //Darstellung des Result Speicher aus dem archive, das sind dann mehrere oids, synchronisiseren des Zeitstempels und eventuell anpassen erforderlich
        $tabelle=array();
        $double=array();           // beim alignen gibt es mehrere Werte zum selben Zeitpunkt, was sollen wir machen        
        if ($werte===false)
            {
            if ($debug) echo "showValues mit den intern gespeicherten Daten als Input aufgerufen:\n";                
            // tabelle schreiben timestamp oid value
            $oids=array();                                                                                                  // Counter wieviele Messwerte
            foreach ($this->result as $oid => $result) 
                { 
                if ($debug)           
                    {
                    if (@IPS_getName($oid)) echo "Datenspeicher $oid (".IPS_getName($oid).")\n";                // oid muss nicht immer einen Namen haben
                    else echo "Datenspeicher $oid \n";
                    }
                if (isset($oids[$oid])) $oids[$oid]++;                      // Counter wieviele Messwerte erhöhen
                else $oids[$oid]=0;                                         // oder anlegen
                foreach ($result as $function => $entries)    
                    {
                    if ($debug) echo "  $function   ";
                    $f=0;
                    if ($function==="Values")                    // nur den Unterpunkt Values bearbeiten, Means, Info etc nicht
                        {
                        //print_r($entries);
                        if (is_countable($entries))
                            {
                            foreach ($entries as $index => $entry) 
                                {
                                if (isset($entry["TimeStamp"])==false) { echo "warning, unexpected format ".json_encode($entry)."\n"; }
                                $timestamp=$entry["TimeStamp"];
                                if  (isset($config["ShowTable"]))
                                    {
                                    if  (isset($config["ShowTable"]["align"]))    // nur bearbeiten wenn Parameter gesetzt, timestamp auf ganze Tage, Stunden, Minuten runden
                                        {
                                        if ($config["ShowTable"]["align"]=="monthly")  $timestamp = strtotime(date("1.m.Y",$timestamp));
                                        if ($config["ShowTable"]["align"]=="daily")  $timestamp = strtotime(date("d.m.Y",$timestamp));
                                        if ($config["ShowTable"]["align"]=="hourly")  $timestamp = strtotime(date("d.m.Y H:00",$timestamp));
                                        if ($config["ShowTable"]["align"]=="minutely")  $timestamp = strtotime(date("d.m.Y H:i",$timestamp));
                                        //  oder einen Zielwert übernehmen wenn Abstand nicht mehr als x ist
                                        }
                                    if  (isset($config["ShowTable"]["adjust"]))    // nur bearbeiten wenn Parameter gesetzt, für eine bestimmte Zahlenreihe den Zeitstempel verschieben
                                        {     
                                        foreach ($config["ShowTable"]["adjust"] as $lookforName => $shiftTime)
                                            {
                                            if (IPS_GetName($oid)==$lookforName)             // Register nach Namen suchen, zB EnergyCounter auf echte Zeit bringen oder 
                                                {
                                                $timestamp = strtotime($shiftTime, strtotime(date("d.m.Y",$timestamp)));   // der Tageszeitstempel dient als Basis und wird mit shiftTime verschoben "+1 month"
                                                }
                                            }
                                        }
                                    if  (isset($config["ShowTable"]["calculate"]["delta"]))    // nur bearbeiten wenn Parameter gesetzt, Deltawerte berechnen
                                        {
                                        echo "Calculate delta with processOnData, not implemented here\n";
                                        }
                                    }
                                if (isset($tabelle[$timestamp][$oid])) 
                                    {
                                    //echo "Doppelter Wert: ".date("d.m.Y H:i",$entry["TimeStamp"])."\n"; 
                                   if (isset($config["ShowTable"]["doubles"]))
                                        { 
                                        if (isset($double[$timestamp][$oid]["Value"]))                                  // das dritte Mal jetzt
                                            {
                                            $double[$timestamp][$oid]["Value"][] = $entry["Value"];
                                            $double[$timestamp][$oid]["Count"]++;
                                            }
                                        else 
                                            {
                                            $double[$timestamp][$oid]["Value"][] = $tabelle[$timestamp][$oid]["Value"];             // neuer Wert
                                            $double[$timestamp][$oid]["Value"][] = $entry["Value"];
                                            $double[$timestamp][$oid]["Count"]=2;
                                            } 
                                        if ($config["ShowTable"]["doubles"]=="add") $entry["Value"] += $tabelle[$timestamp][$oid]["Value"];
                                        elseif ($config["ShowTable"]["doubles"]=="mean")
                                            {
                                            $addx=0;
                                            foreach ($double[$timestamp][$oid]["Value"] as $idx => $addix) $addx += $addix;
                                            $entry["Value"] = $addx/$double[$timestamp][$oid]["Count"];     
                                            }
                                        else $entry["Value"] += $tabelle[$timestamp][$oid]["Value"];
                                        }
                                    }
                                $tabelle[$timestamp][$oid]=$entry;              // Value/Timestamp ist der Originalwert, nicht die alignte Variante
                                $f++;
                                } 
                            }  
                        if ($debug) echo "count $f"; 
                        }
                    elseif ($debug) echo "warning, unexpected category, $function ";
                    if ($debug) echo "\n";
                    }
                }
            //echo "Tabelle hat ".count($tabelle)." Einträge.\n";   
            if (count($tabelle)) ksort($tabelle);                       // eine leere Tabelle muss man nicht sortieren
            ksort($oids);                

            //tabelle Ausgeben, zuerst die Spalten sortieren
            if ($doecho)
                {
                echo "Ausgabe der alignten Tabelle für die obigen Werte:\n";
                echo "   Timestamp                  ";
                foreach ($oids as $oid=>$count) 
                    {
                    echo $oid."         ";
                    }
                echo "\n";
                }

            /* Zeilenweise nach Datumsstempel abarbeiten und darstellen, Zeitstempel sind bereits aligned, fehlende Werte mit *** kennzeichnen
             * tabelle mit timestamp als index und dann oid als index, mögliche oids sind im array oids abgespeichert
             * für jede Zeile feststellen welche oids von den darzustellenden vorhanden sind, immer mit TimeStamp und Value gespeichert
             * erster Wert von oid der fehlt speichert oid in overwrite, mehrere fehlende Werte ist eine Fehlermeldung
             * dann ein Target für den fehlenden Wert suchen
             * 
             */ 
            $correct=0;
            $realtable=array(); $line=0; 
            //$line=count($tabelle)-1;
            foreach ($tabelle as $timeStamp => $entries)
                {
                if ($doecho) echo "   ".date ("d.m.Y H:i:s",$timeStamp)."        ";          
                $overwrite=false; $pullwrite=false;         // für Mechanismus welche Werte fehlen, geändert gehören oder ergänzt werden müssen
                foreach ($oids as $oid=>$count)             // für jede Zeile feststellen welche oids von den darzustellenden vorhanden sind
                    {
                    //echo $oid." ";
                    if (isset($entries[$oid])===false)      // wenn nicht vorhanden *** vergeben, aber TimeStamp nicht setzen, als target für Ersatzwerte markieren
                        {
                        $entries[$oid]=array();
                        $entries[$oid]["Value"]="***";
                        if ($overwrite===false) $overwrite=$oid;                    // overwrite,target geben das target an das überschrieben bzw. neu gesetzt werden muss
                        //else echo "Too many targets for overwrite.\n";
                        $target=$oid;           // für Eintrag in array add
                        }
                    }
                if ($overwrite)             // source für das target suchen
                    {
                    foreach ($oids as $oid=>$count) 
                        {
                        if (isset($entries[$oid]["TimeStamp"]))         // Value hat ja nur Value=*** bekommen, wird nicht gefunden als source for target
                            {
                            //print_R($entries[$oid]);
                            if ($pullwrite===false) 
                                {
                                $pullwrite=$oid;                            // Source gefunden, immer die erste gefundene nehmen
                                //else echo "Too many sources for target to overwrite.\n";
                                if (isset($entries[$oid]["Value"])) $targetValue=$entries[$oid]["Value"];
                                else $targetValue=$entries[$oid]["Avg"];
                                $targetTimeStamp = $entries[$oid]["TimeStamp"];
                                //adjust timestamp if appropriate
                                $adjustSource=false; $adjustTarget=false;
                                if (isset($config["ShowTable"]["adjust"]))         // nur wenn eine Datenreihe verschoben werden soll
                                    {                            
                                    foreach ($config["ShowTable"]["adjust"] as $lookforName => $shiftTime)
                                        {
                                        if (IPS_GetName($pullwrite)==$lookforName) $adjustSource=$shiftTime;         //entweder target oder source werden verschoben
                                        if (IPS_GetName($overwrite)==$lookforName) $adjustTarget=$shiftTime;
                                        }                        
                                    //echo "Abgleichen der Zeitstempel Source: $adjustSource und Target: $adjustTarget ";
                                    if ($adjustSource) $targetTimeStamp = strtotime($adjustSource, strtotime(date("d.m.Y",$entries[$oid]["TimeStamp"])));
                                    else $targetTimeStamp = strtotime(date("d.m.Y",$entries[$oid]["TimeStamp"]));
                                    //if ($adjustTarget) echo "Target cannot be adjusted. Change $shiftTime from + to - or vice versa.\n";
                                    }
                                }
                            $overwrite=false;                                       // sehr gut, gefunden
                            }
                        }
                    }
                if ($overwrite)                     // nicht gut wenn nicht gefunden
                    {
                    echo "Keine Quelle gefunden. Ziel loeschen.\n";
                    $overwrite=false;
                    }
                if ($pullwrite && $target)                      // source für das target gefunden, dort Werte hinzufügen, dazu correct einfach als Index hochzählen
                    {
                    // mit Source oder mit Delta
                    echo "Add Data Source \"$pullwrite\" to Target \"$target\" \n";
                    $valuesAdd[$target][$pullwrite][$correct]["Value"]=$targetValue;            // source nicht mehr relevant, sondern nur wo werden die Wert gespeichert
                    $valuesAdd[$target][$pullwrite][$correct]["TimeStamp"]=$targetTimeStamp;
                    $correct++;
                    }    
                // schlussendlich die Anzeige als tabelle
                ksort($entries);
                foreach ($entries as $oid =>$entry)
                    {
                    if (isset($entry["Value"]))   { $wert=nf($entry["Value"],"");  $wert2=$entry["Value"]; }
                    elseif (isset($entry["Avg"])) { $wert=nf($entry["Avg"],"");  $wert2=$entry["Avg"]; }
                    else { $wert="";  $wert2=""; }
                    if ($doecho) echo str_pad($wert,14);
                    if ( $moreValues && (isset($entry["Avg"])) ) 
                        {
                        foreach ($getValues as $valIndex) 
                            {
                            if ($valIndex=="Avg") $realtable[$line][$oid]=$entry[$valIndex]; 
                            else $realtable[$line][$oid.$valIndex]=$entry[$valIndex];   
                            }
                        }
                    else $realtable[$line][$oid]=$wert2;
                    }
                if ($doecho) echo "\n";
                $realtable[$line]["TimeStamp"]=$timeStamp;
                $line++;
                //$line--;
                }       // Zeile für Zeile ausgeben
            $resultShow["realtable"]=$realtable;                  // ausgabefertige version
            $resultShow["table"]=$tabelle;                  // das sind die bereinigten Werte, wahrscheinlich zu gross
            $resultShow["columns"]=$oids;
            $resultShow["add"]=$valuesAdd;
            $resultShow["double"]=$double;            
            }
        if ($returnRealTable) return ($resultShow["realtable"]);
        return ($resultShow);
        }

    /* nach einem historischen Wert mit einem bestimmten Zeitstempel suchen
     * die Werte am besten von showValues übernehmen, dann sind sie bereits bereinigt
     *
     */
    function lookforValues($werte=false,$lookTimeStamp,$debug=false)
        {
        $tabelle=array();           // Ausgabe der Ergebnisse aus dem Speicher
        if (is_array($werte))
            {
            foreach ($werte as $wert) 
                {
                if (isset($wert["TimeStamp"]))
                    {
                    //print_R($wert);
                    $timeStamp=$wert["TimeStamp"];
                    if ($lookTimeStamp===$timeStamp) return($wert);
                    }
                }
            }
        elseif ($werte===false)         //Darstellung des Result Speicher aus dem archive, das sind dann mehrere oids, synchronisiseren des Zeitstempels und eventuell anpassen erforderlich
            {
            // tabelle schreiben timestamp oid value
            $oids=array();
            if (is_countable($this->result))
                {
                foreach ($this->result as $oid => $result) 
                    {
                    //echo "Datenspeicher $oid ".IPS_getName($oid)."\n";            oid muss nicht immer einen Namen haben
                    echo "Datenspeicher $oid \n";
                    if (isset($oids[$oid])) $oids[$oid]++;
                    else $oids[$oid]=0;
                    foreach ($result as $function => $entries)    
                        {
                        if ($function=="Values")
                            {
                            //print_r($entries);
                            foreach ($entries as $index => $entry) 
                                {
                                $timestamp=$entry["TimeStamp"];
                                if ($lookTimeStamp===$timeStamp) $tabelle[$oid]=$entry;
                                }   
                            }
                        }
                    }
                }
            }
        return ($tabelle);
        }

    /* archiveOps::getArchivedValues
     * aufgerufen von  getValues wenn Parameter Aggregated auf false steht
     * es gibt keine Funktion die Werte holt, also rund um AC_getLogged was machen
     * Zwischen Start und Endtime werden Werte aus dem Archiv getLogged geholt, es gibt keine 10.000er Begrenzung, allerdings wird der Speicher schnell knapp
     * Es gibt eine manuelle Aggregation die sowohl die Summe als auch den Average und Max/Min berechnen kann. 
     * Die Funktion kann auch die Übergabe von einer 10.000 Tranche zur nächsten
     *    if ($config["manAggregate"]) $config["carryOver"] = $this->manualDailyAggregate($werteTotal,$werte,$config,$debug1);      // werteTotal ist ergebnis, werte ist Input, config 
     *    else {$this->manualDailyEvaluate($werteTotal,$werte,$config,$debug1); $werteTotal = array_merge($werteTotal,$werte); }
     *
     * es wird meansCalc verwendet um die Datenmengen vorab zu analysieren, Ergebnis Auswertung wird am Ende präsentiert
     *
     * config auch qualifizieren, aus der Config werden benötigt
     *      StartTime
     *      EndTime
     *      manAggregate    verwendet manualDailyAggregate
     *
     *
     *
     */
    public function getArchivedValues($oid,$configInput=array(),$debug=false)
        {
        //if ($debug) $debug=false;
        // Konfiguration vorbereiten
        $gaps=false;                        // wen nicht verwendet wird, zumindest definiert
        $remOID=false;  	                // anstelle lokaler Archive, die Archive von einem Remote Server abfragen
        $statistics = new statistics();        
        $config = $statistics->setConfiguration($configInput);          // Konfiguration qualifizieren
        // OID vom Archiv ermitteln
        if (is_numeric($oid)) $aggType = AC_GetAggregationType($this->archiveID, $oid);
        else
            {
            if ($debug) echo "getArchivedValues($oid, benötigt Analyse wo der Wert gespeichert ist und welche OID er hat.\n"; // String Variable, wahrscheinlich im Format Server::VariableName
            $result = explode("::",$oid);
            $size = sizeof($result);
            if ($size==2)
                {
                $Configuration=array();
                $remServer	  = RemoteAccessServerTable(2,$debug);
                if (isset($remServer[$result[0]])) $url=$remServer[$result[0]]["Url"];
                else return (false);
                $rpc = new JSONRPC($url);
                if ($debug) echo "Server $url : ".$rpc->IPS_GetName(0)." Search for Name at Program.IPSLibrary.data.core.IPSComponent.Counter-Auswertung\n";
                $prgID=$rpc->IPS_GetObjectIDByName("Program",0);
                $ipsID=$rpc->IPS_GetObjectIDByName("IPSLibrary",$prgID);
                $dataID=$rpc->IPS_GetObjectIDByName("data",$ipsID);
                $coreID=$rpc->IPS_GetObjectIDByName("core",$dataID);
                $compID=$rpc->IPS_GetObjectIDByName("IPSComponent",$coreID);
                $CounterAuswertungID=$rpc->IPS_GetObjectIDByName("Counter-Auswertung",$compID);
                $remOID=@$rpc->IPS_GetObjectIDByName($result[1],$CounterAuswertungID);
                if ($remOID===false) return(false);
                $archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                $aggType = $rpc->AC_GetAggregationType($archiveHandlerID,$remOID);
                }
            else 
                {
                $prgID=IPS_GetObjectIDByName("Program",0);
                $ipsID=IPS_GetObjectIDByName("IPSLibrary",$prgID);
                $dataID=IPS_GetObjectIDByName("data",$ipsID);
                $coreID=IPS_GetObjectIDByName("core",$dataID);
                $compID=IPS_GetObjectIDByName("IPSComponent",$coreID);
                $CounterAuswertungID=IPS_GetObjectIDByName("Counter-Auswertung",$compID);
                $oid=@IPS_GetObjectIDByName($result[0],$CounterAuswertungID);
                if ($oid===false) return(false);
                $aggType = AC_GetAggregationType($this->archiveID, $oid);
                }
            }
        if ($debug) 
            {
            echo "getArchivedValues($oid,".json_encode($configInput)."...)\n";
            echo "manuelle Aggregation wird durchgeführt: ".json_encode($config["manAggregate"])."\n";
            echo "Aggregation Type des Archives ist ".($aggType?"Zähler":"Standard")."\n";
            }

        $werteTotal=array(); 
        //print_R($config);   
        $this->configStartEndTime($config);         // transforms config Start/Endtime to Unixtime

        /* Mittelwertberechnung von analyseValues verwenden und vorbereiten */
        $means=array();                                          // Speicherplatz im Ergebnis zur Verfügung stellen
        $config["Means"]["Full"]   = new meansCalc($means, "Full");       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen
        $config["Means"]["Day"]    = new meansCalc($means, "Day",2000);                           // Ergebnis in means[Day]
        $maxminFull = $config["Means"]["Full"];
        $maxminDay = $config["Means"]["Day"];

        $config["carryOver"]=array();
        $config["counter"]=$aggType;                        // aufpassen Aggregation ist anders
        if ($config["maxDistance"]>0)                       // Eventuell Gapanalyse machen
            {
            if ($debug) echo "Do distance check for more than ".$config["maxDistance"]." hours before cleanup Data and store.\n";
            $gaps=array();    
            $config["Gaps"] = new gapsEval($gaps, "Hours",$config,$debug);       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen
            }
        $starttime = $config["StartTime"]; $endtime = $config["EndTime"];
        $debug1=false;
        // als werte vom Archive einlesen und als werteTotal abspeichern, Umweg notwendig wegen manueller Aggregation
        do  {   // es könnten mehr als 10.000 Werte sein,   Abfrage generisch lassen
            if ($remOID) 
                {
                $werte = @$rpc->AC_GetLoggedValues($archiveHandlerID, $remOID, $starttime, $endtime, 0);
                }
            else $werte = @AC_GetLoggedValues($this->archiveID, $oid, $starttime, $endtime, 0);
            if ( ($werte !== false) && (count($werte)>0) )
                {
                //print_r($werte);
                //if ($debug) echo "AC_GetLoggedValues von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
                $firstTime = $werte[array_key_last($werte)]["TimeStamp"];
                $lastTime  = $werte[array_key_first($werte)]["TimeStamp"];
                if ($debug) echo "   Read batch from ".date("d.m.Y H:i:s",$firstTime)."  to  ".date("d.m.Y H:i:s",$lastTime)." with ".count($werte)." Values.\n";
                $endtime=$firstTime-1;                  // keine doppelten Werte, eine Sekunde abziehen 
                if ($config["manAggregate"]) $config["carryOver"] = $this->manualDailyAggregate($werteTotal,$werte,$config,$debug1);      // werteTotal ist ergebnis, werte ist Input, config 
                else 
                    {
                    $this->manualDailyEvaluate($werteTotal,$werte,$config,$debug1);      // werteTotal ist ergebnis, werte ist Input, config
                    $werteTotal = array_merge($werteTotal,$werte);
                    }
                }
            else 
                {
                if ($config["Warning"]) echo "Warning, retrieving Logging Data for $oid from ".$config["StartTime"]." to ".$config["EndTime"]." results to empty or fail.\n";
                $werte=array();
                }
            } while (count($werte)==10000);

        $maxminFull->calculate();                   // Max Min Werte im Ergebnis Array maxmin abspeichern
        $maxminDay->calculate();                   // Max Min Werte im Ergebnis Array maxmin abspeichern
        if ($debug) 
            {
            echo "Ergebnis getArchivedValues Count ".count($werteTotal)."\n";
            if (count($werteTotal)<20) 
                {
                foreach ($werteTotal as $index => $wert) echo "$index ".date("d.m.Y H:i:s",$wert["TimeStamp"])."   ".$wert["Value"]."\n";
                }
            //print_r($means);
            $maxminFull->print();
            $maxminDay->print();
            if ($gaps) print_r($gaps);                  // werden sonst nicht weiter verwendet
            }
        if ($gaps) $this->warnings=$gaps;
        return($werteTotal);
        }

    /* archiveOps::getArchivedCounters
     * aufgerufen von  getValues wenn Parameter "DataType auf Counter steht
     * noch nicht implmentiert, fork von getArchivedValues
     * geht auch mit manAggregate=daily
     *
     * es gibt keine Funktion die Werte holt, also rund um AC_getLogged was machen
     * Zwischen Start und Endtime werden Werte aus dem Archiv getLogged geholt, es gibt keine 10.000er Begrenzung, allerdings wird der Speicher schnell knapp
     * es gibt eine manuelle Aggregation die sowohl die Summe als auch den Average und Max/Min berechnen kann. Die Funktion kann auch die Übergabe von einer 10.000 Tranche zurnächsten
     *
     * es wird meansCalc verwendet um die Datenmengen vorab zu analysieren, Ergebnis Auswertung wird am Ende präsentiert
     *
     * config auch qualifizieren, aus der Config werden benötigt
     *      StartTime
     *      EndTime
     *      manAggregate    verwendet manualDailyAggregate zum Aggregieren
     *
     *
     * Return Wert sind werteTotal, diese werden aus dem Input Werten erzeugt
     *
     */
    public function getArchivedCounters($oid,$configInput=array(),$debug=false)
        {
        //if ($debug) $debug=false;
        // Konfiguration vorbereiten
        $gaps=false;                        // wen nicht verwendet wird, zumindest definiert
        $remOID=false;  	                // anstelle lokaler Archive, die Archive von einem Remote Server abfragen
        $statistics = new statistics();        
        $config = $statistics->setConfiguration($configInput);          // Konfiguration qualifizieren
        // OID vom Archiv ermitteln
        if (is_numeric($oid)) $aggType = AC_GetAggregationType($this->archiveID, $oid);
        else
            {
            if ($debug) echo "getArchivedValues($oid, benötigt Analyse wo der Wert gespeichert ist und welche OID er hat.\n"; // String Variable, wahrscheinlich im Format Server::VariableName
            $result = explode("::",$oid);
            $size = sizeof($result);
            if ($size==2)
                {
                $Configuration=array();
                $remServer	  = RemoteAccessServerTable(2,$debug);
                if (isset($remServer[$result[0]])) $url=$remServer[$result[0]]["Url"];
                else return (false);
                $rpc = new JSONRPC($url);
                if ($debug) echo "Server $url : ".$rpc->IPS_GetName(0)." Search for Name at Program.IPSLibrary.data.core.IPSComponent.Counter-Auswertung\n";
                $prgID=$rpc->IPS_GetObjectIDByName("Program",0);
                $ipsID=$rpc->IPS_GetObjectIDByName("IPSLibrary",$prgID);
                $dataID=$rpc->IPS_GetObjectIDByName("data",$ipsID);
                $coreID=$rpc->IPS_GetObjectIDByName("core",$dataID);
                $compID=$rpc->IPS_GetObjectIDByName("IPSComponent",$coreID);
                $CounterAuswertungID=$rpc->IPS_GetObjectIDByName("Counter-Auswertung",$compID);
                $remOID=@$rpc->IPS_GetObjectIDByName($result[1],$CounterAuswertungID);
                if ($remOID===false) return(false);
                $archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                $aggType = $rpc->AC_GetAggregationType($archiveHandlerID,$remOID);
                }
            else 
                {
                $prgID=IPS_GetObjectIDByName("Program",0);
                $ipsID=IPS_GetObjectIDByName("IPSLibrary",$prgID);
                $dataID=IPS_GetObjectIDByName("data",$ipsID);
                $coreID=IPS_GetObjectIDByName("core",$dataID);
                $compID=IPS_GetObjectIDByName("IPSComponent",$coreID);
                $CounterAuswertungID=IPS_GetObjectIDByName("Counter-Auswertung",$compID);
                $oid=@IPS_GetObjectIDByName($result[0],$CounterAuswertungID);
                if ($oid===false) return(false);
                $aggType = AC_GetAggregationType($this->archiveID, $oid);
                }
            }
        if ($debug) 
            {
            echo "getArchivedValues($oid,".json_encode($configInput)."...)\n";
            echo "manuelle Aggregation wird durchgeführt: ".json_encode($config["manAggregate"])."\n";
            echo "Aggregation Type des Archives ist ".($aggType?"Zähler":"Standard")."\n";
            }

        $werteTotal=array(); 
        //print_R($config);   
        $this->configStartEndTime($config);         // transforms config Start/Endtime to Unixtime

        /* Mittelwertberechnung von analyseValues verwenden und vorbereiten */
        $means=array();                                          // Speicherplatz im Ergebnis zur Verfügung stellen
        $config["Means"]["Full"]   = new meansCalc($means, "Full");       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen
        $config["Means"]["Day"]    = new meansCalc($means, "Day",2000);                           // Ergebnis in means[Day]
        $maxminFull = $config["Means"]["Full"];
        $maxminDay = $config["Means"]["Day"];

        $config["carryOver"]=array();
        $config["counter"]=$aggType;                        // aufpassen Aggregation ist anders
        if ($config["maxDistance"]>0)                       // Eventuell Gapanalyse machen
            {
            if ($debug) echo "Do distance check for more than ".$config["maxDistance"]." hours before cleanup Data and store.\n";
            $gaps=array();    
            $config["Gaps"] = new gapsEval($gaps, "Hours",$config,$debug);       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen
            }
        $starttime = $config["StartTime"]; $endtime = $config["EndTime"];
        $debug1=false;
        // als werte vom Archive einlesen und als werteTotal abspeichern, Umweg notwendig wegen manueller Aggregation
        do  {   // es könnten mehr als 10.000 Werte sein,   Abfrage generisch lassen, endtime wird upgedated
            if ($remOID) 
                {
                $werte = @$rpc->AC_GetLoggedValues($archiveHandlerID, $remOID, $starttime, $endtime, 0);
                }
            else $werte = @AC_GetLoggedValues($this->archiveID, $oid, $starttime, $endtime, 0);
            if ( ($werte !== false) && (count($werte)>0) )
                {
                //print_r($werte);
                //if ($debug) echo "AC_GetLoggedValues von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
                $firstTime = $werte[array_key_last($werte)]["TimeStamp"];
                $lastTime  = $werte[array_key_first($werte)]["TimeStamp"];
                if ($debug) echo "   Read batch from ".date("d.m.Y H:i:s",$firstTime)."  to  ".date("d.m.Y H:i:s",$lastTime)." with ".count($werte)." Values.\n";
                $endtime=$firstTime-1;                  // keine doppelten Werte, eine Sekunde abziehen 
                if ($config["manAggregate"]) $config["carryOver"] = $this->manualDailyAggregate($werteTotal,$werte,$config,$debug1);      // werteTotal ist ergebnis, werte ist Input, config 
                else 
                    {
                    $this->manualDailyCounterEvaluate($werteTotal,$werte,$config,$debug);      // werteTotal ist ergebnis, werte ist Input, config
                    //$werteTotal = array_merge($werteTotal,$werte);
                    }
                }
            else 
                {
                if ($config["Warning"]) echo "Warning, retrieving Logging Data for $oid from ".$config["StartTime"]." to ".$config["EndTime"]." results to empty or fail.\n";
                $werte=array();
                }
            } while (count($werte)==10000);

        $maxminFull->calculate();                   // Max Min Werte im Ergebnis Array maxmin abspeichern
        $maxminDay->calculate();                   // Max Min Werte im Ergebnis Array maxmin abspeichern
        if ($debug) 
            {
            echo "Ergebnis getArchivedValues Count ".count($werteTotal)."\n";
            if (count($werteTotal)<20) 
                {
                foreach ($werteTotal as $index => $wert) echo "$index ".date("d.m.Y H:i:s",$wert["TimeStamp"])."   ".$wert["Value"]."\n";
                }
            //print_r($means);
            $maxminFull->print();
            $maxminDay->print();
            if ($gaps) print_r($gaps);                  // werden sonst nicht weiter verwendet
            }
        if ($gaps) $this->warnings=$gaps;
        return($werteTotal);
        }

    /* archiveOps::getArchivedAggregates
     * es gibt keine Funktion die Werte holt, also rund um AC_getAggregated was machen
     *
     *  aus der Config werden benötigt
     *      StartTime
     *      EndTime
     *      Aggregate
     *
     * statt TimeStamp Value gibt es mehr Informationen
     *      [Duration] => 57554                             // Dauer bis zum nächsten Wert oder aktueller Uhrzeit
            [TimeStamp] => 1698098400
            [Avg] => 14,463310977517
            [MinTime] => 1698103676
            [Min] => 13,7
            [MaxTime] => 1698147028
            [Max] => 15,2
     *
     */
    public function getArchivedAggregates($oid,$configInput=array(),$debug=false)
        {
        $remOID=false;  	                // anstelle lokaler Archive, die Archive von einem Remote Server abfragen
        $statistics = new statistics();                
        $config = $statistics->setConfiguration($configInput);
        $aggreg=$this->configAggregated($config["Aggregated"]); 
        $this->configStartEndTime($config);         // transforms config Start/Endtime to Unixtime
        $werte   = @AC_GetAggregatedValues($this->archiveID, $oid, $aggreg, $config["StartTime"], $config["EndTime"], 0);             // 0 unlimited  
        //print_r($werte);
        return ($werte);
        }

    /* zum Check die Datenflut als Tageswerte zusammenzählen 
     * zusätzlich zum Daily Aggreagate gibt es auch wöchentliche oder monatliche Auswertungen 
     * das Ergebnis wird im Array result gespeichert, return ist etwas anderes, das Ergebnis
     * result wird wenn nicht leer einfach länger, zeitlich und nach Index wird einfach hinten dran angehängt, index = 0,1,2....n
     *
     * config Einstellungen
     *      manAggregate        daily, weekly, monthly
     *      carryOver           Übertrag, wenn der halbe Tag noch nicht fertig wird die bisherige Summe als Teil der Konfiguration übergeben
     *      counter             Zähler oder Standard, zusätzliche Information ob die Werte steigen oder nur der Vorschub sind
     *      means.Full          pointer auf die class für die Mittelwertsberechnung
     *      means.Day           pointer auf die class
     *
     * unterschiedliche Arten der Berechnung des Aggregates, Increment = [0,1,2]
     *      0 gut geeignet für 15 Minuten Energiewerte, wird nur bei Daily verwendet
     *      1 für nicht so standardisiserte Werte, keine Berücksichtigung der aufgelaufenen Werte zwischen den Einzelwerten
     *      2 für Zählerwerte, Differenz dist dei Aggregation
     *
     */
    public function manualDailyAggregate(&$result,$werte,$config,$debug=false)
        {
        //$debug=true;            // overwrite for failure analysis

        // Configuration config klären
        if (isset(($config["manAggregate"]))===false) $config["manAggregate"]=true;
        if ($debug) echo "manualDailyAggregate aufgerufen mit ".json_encode($config["manAggregate"]).".\n";
        if (is_array($result)===false) return (false);                  // $result wird als array übergeben, damit kann man mehrere Ergebnisse zusammenfassen

        if (is_array($config["manAggregate"]))
            {

            }
        else
            {
            switch (strval($config["manAggregate"]))
                {
                case "1":
                case "daily":
                    $manAggregate=1;
                    $increment=0;                       // unterschiedliche Funktionen bei der Integration der Werte, geeignet für 15 Minutenwerte     
                    break;
                case "2":
                case "weekly";
                    $manAggregate=2;
                    $increment=1;                       
                    break;
                case "3":
                case "monthly";
                    $manAggregate=3;         
                    $increment=1;
                    break;                    
                default:
                    echo "Warning, do not understand \"".strval($config["manAggregate"])."\", expect hourly/daily/weekly/monthly.\n";
                    $manAggregate=1;
                    $increment=0; 
                    break;
                }
            if ($debug) echo "Aggregated is configured with ".$config["manAggregate"]." converted to internal modes :  manAggregate $manAggregate und increment $increment.\n";                
            }

        // mit welchem Index hängen wir die Daten an die bestehenden dran
        if (count($result)==0) $index=0;              // Wir brauchen einen Index zum abspeichern der Werte, wenn leer beginnen wir mit 0
        else
            {
            // ersten und letzten Key rausfinden und den Zeitstempel dazu
            $lastkey     = array_key_last($result);
            $firstkey    = array_key_first($result);
            $pastTime    = $result[$lastkey]["TimeStamp"];
            $futureTime  = $result[$firstkey]["TimeStamp"];
            if ($debug) echo "   Input batch data from ".date("d.m.Y H:i:s",$pastTime)." ($lastkey)  to  ".date("d.m.Y H:i:s",$futureTime)." ($firstkey) with ".count($werte)." Values.\n";
            $index=$lastkey+1;
            }

        // Vorbereiten der Datenbearbeitung
        $initial=true; 
        $display=$debug;                                //$display=$debug;
        $zaehler=0; $gepldauer=0; $showAggCount=0;
        $prevdirection=false;                           // Richtungserkennung

        if (isset($config["carryOver"]["Value"])) $ergebnis=$config["carryOver"]["Value"];
        else $ergebnis=0;
        if (isset($config["carryOver"]["Max"])) { $max=$config["carryOver"]["Max"]; $maxTime=$config["carryOver"]["MaxTime"]; }
        else $max=false;
        if (isset($config["carryOver"]["Min"])) { $min=$config["carryOver"]["Min"]; $maxTime=$config["carryOver"]["MinTime"]; }
        else $min=false;
        if (isset($config["carryOver"]["countPeriode"])) $countPeriode=$config["carryOver"]["countPeriode"];
        else  $countPeriode=0;
 
        if (isset(($config["counter"]))===false) $aggType=0;
        else $aggType=$config["counter"];
        if ($aggType==1) $increment=2; 


        $maxmin=array();                                    // Speicherplatz zur Verfügung stellen
        if (isset($config["Means"]["Full"])) $maxminFull = $config["Means"]["Full"];
        else $maxminFull = new maxminCalc($maxmin,"Full");       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen
        if (isset($config["Means"]["Day"])) $maxminDay = $config["Means"]["Day"];
        else $maxminDay = new maxminCalc($maxmin,"Day");
        $maxminFull->updateConfig($config);             // Fehlermeldung wenn maxminCalc verwendet wird
        $maxminDay->updateConfig($config);
        
        if (isset($config["Gaps"])) $gaps = $config["Gaps"];            // die class gapsEval
        else $gaps=false;

        // werte ist input und result ist output array
        foreach($werte as $wert)            // die Werte analysieren und bearbeiten, Standardfunktionen verwenden
            {
            $maxminFull->addValue($wert);
            $maxminDay->addValue($wert);
            if ($gaps) $gaps->addValue($wert);              // Gaps ermitteln
            
            $zeit=$wert['TimeStamp'];
            $tag=date("Ymd", $zeit);                        // zum aggregieren brauchen wir die Änderungen des Tages/Woche, Monat
            $woche=date("YW", $zeit);
            $monat=date("Ym", $zeit);

            $aktwert=(float)$wert['Value'];

            if ($initial)       // interne Vorbereitung
                {
                //print_r($wert);
                $initial=false;                 // initial nur einmal aufrufen
                $showAgg=false;                 // Anzeige des Ergebnisses nach Ende einer Periode
                if ($debug) 
                    {
                    echo "   Init, Mode Aggregate/Increment $manAggregate/$increment:\n";
                    echo "   Startzeitpunkt:".date("d.m.Y H:i:s", $wert['TimeStamp'])."\n";
                    $dailyStart = strtotime(date("d.m.Y", $wert['TimeStamp']));
                    $dailyDelay=($wert['TimeStamp']-$dailyStart)/60;
                    echo "   Tagesanfang ist allerdings $dailyDelay Minuten früher. berücksichtigen.\n";
                    echo "   Aktuelle Woche ist $woche\n";
                    }
                $vorigertag=$tag;
                $vorigeWoche=$woche;
                $vorigeMonat=$monat;
                $disp_vorigertag=$tag;
                $vorigeZeit=$zeit;
                $direction="unknown";
                $neuwert=$aktwert; 
                $altwert2=$aktwert;             // bei Typ Zähler nur die kleinen Änderungen schreiben    
                $altzeit2=$zeit;    
                $count2=0;  
                // ergebnis und countPeriode wird übernommen  
                }
            else 
                {
                if ($vorigeZeit>$zeit) $direction="past";       // höherer Index ist in der Vergangheit
                else $direction="future";
                if ($prevdirection!=$direction) if ($debug) echo "Richtung erkannt : go to $direction \n";
                $prevdirection=$direction;
                }
            /* es hängt von der Richtung ab index 0 ist jetzt heute und index 1 ist jetzt-15min, wir fahren in die Vergangenheit (past), die zeit Werte werden kleiner
             * alter Tag fangt an, zB mit 31.1.1978 23:45 (index 31), hier ist der Wert von 1.2.1978 00:00 (index 30) bis 00:00 enthalten, das bedeutet:
             * wenn neuer Tag erkannt wurde mit dem alten Wert neu beginnen
             *
             * oder umgekehrt 1.1.1970 ist 0 und 15min später ist index 1, wir fahren aus der Vergangenheit in die Zukunft (future)
             * neuer Tag fangt an, zB mit 1.2.1978 00:00 (index 31), hier ist der Wert von 31.1.1978 23:45 (index 30) bis 00:00 enthalten, das bedeutet 
             * wenn neuer Tag erkannt wird mit 0 beginnen und alten Wert zum alten Tag dazuzählen
             *
             */
            if ( ( ($tag!=$vorigertag) && ($manAggregate==1) ) || ( ($woche!=$vorigeWoche) && ($manAggregate==2) ) || ( ($monat!=$vorigeMonat) && ($manAggregate==3) ) )     // Tages/Wochen/Monatswechsel, beim ersten Mal kommt man hier nicht vorbei
                { 
                $showAgg=true;                      // Ergebnis anzeigen, es gibt einen Übertrag
                //echo "Wechsel Aggregate $manAggregate Mode $increment\n"; 
                //$altwert=$neuwert;
                switch ($increment)         // increment 0 und 1 verwendet für Standard Logging
                    {
                    case 1:                                                     // Standard Logging, Ergebnis wird übernommen
                        $ergebnisTag=$ergebnis;
                        $ergebnis=$aktwert;
                        $result[$index]["TimeStamp"]=$altzeit;
                        $result[$index]["Value"]=$ergebnisTag;      
                        $index++;                                          
                        break;
                    case 2:                 // Zähler Logging
                        $altwert=$neuwert;
                        if ($aktwert<$neuwert)
                            {
                            $ergebnis+=($neuwert-$aktwert);
                            }
                        else
                            {
                            //$ergebnis+=($altwert-$neuwert);
                            //$ergebnis=$aktwert;
                            }
                        $result[$index]["TimeStamp"]=$zeit;
                        $result[$index]["Value"]=($neuwert-$aktwert);
                        $index++;                      
                        $neuwert=$aktwert; 
                        break;
                    case 0:                                         // Standard Logging, Statuswert, daher kompletten Bereich zusammenzählen 
                        if ($direction=="past")
                            {
                            if (true)       // so wie increment 1, geändert damit mit log.Wien zusammenpasst
                                {
                                $ergebnisTag=$ergebnis;                         // geradlinig zählen, alle Werte vom selben Tag zusammenzählen, Zeitstempel ist der Beginn der jeweiligen Einheit
                                $ergebnis=$aktwert;
                                }
                            else            // bisherig
                                {
                                $ergebnisTag=$ergebnis-$altwert;                         // 23:45 vom neuen Tag braucht den 00:00 Wert vom Vortag, wenn wir in die Vergangheit zählen
                                $ergebnis=$altwert+$aktwert;
                                }
                            $result[$index]["TimeStamp"]=$altzeit;
                            $countPeriode--;
                            $count2=$countPeriode;          // eh nur zum anzeigen
                            if ($countPeriode!=0) $result[$index]["Avg"]=$ergebnisTag/$countPeriode;            // Fehler abfangen und lieber keinen Wert
                            else $result[$index]["Avg"]=0;
                            $countPeriode=2; 
                            if ($debug) echo "Tagesende erkannt, Tag $ergebnisTag Weiter $ergebnis \n";
                            }
                        if ($direction=="future")
                            {
                            $ergebnisTag=$ergebnis+$aktwert;                         // 00:00 vom aktuellen tag waren bereits die Werte des Vortages, rauf wäre es umgekehrt
                            $ergebnis=0;
                            $result[$index]["TimeStamp"]=$zeit;
                            $countPeriode++;
                            $count2=$countPeriode;          // eh nur zum anzeigen
                            if ($countPeriode!=0) $result[$index]["Avg"]=$ergebnisTag/$countPeriode;
                            else $result[$index]["Avg"]=0;
                            $countPeriode=0; 
                            }
                        // Werte übernehmen
                        $result[$index]["Value"]=$ergebnisTag;
                        $result[$index]["Min"]=$min; $result[$index]["MinTime"]=$minTime; $min=false;
                        $result[$index]["Max"]=$max; $result[$index]["MaxTime"]=$maxTime; $max=false;
                        $index++;
                        break;
                    default:
                    }
                
                $vorigertag=$tag; 
                $vorigeWoche=$woche;
                $vorigeMonat=$monat;                    
                }
            else                // innerhalb eines Tages/woche/Monat
                {
                $showAgg=false;                    
                /* Übertrag von den vorigen Messwerten sind Ergebnis 
                 * aktwert ist der aktuelle Messwert, bereits oben erfasst, standard format ist float
                 * altzeit ist die aktuelle Zeit des Messwertes
                 *
                 * increment 0 und 2 für intervall daten und increment 1 für aggregierte Werte
                 * neu eingeführt, Bei Statuswert muessen alle Werte agreggiert werden 
                 */
                
                switch ($increment)
                    {
                    case 1:                                         // addiert die werte innerhalb einer Dauer
                        $ergebnis+=$aktwert;
                        $altwert=$aktwert; 
                        $altzeit=$wert['TimeStamp'];                //brauche den Zeitstempel vor der tagesänderung   
                        $count2++;
                        break;                 
                    case 2:                                         // Zähler Logging, es braucht keine Akkumulation
                        $count2++;
                        break;
                    case 0:                                     // Statuswert, daher kompletten Bereich zusammenzählen , für Tageswerte
                        $ergebnis+=$aktwert;
                        if (($max===false) || ($max<$aktwert)) { $max=$aktwert; $maxTime=$wert['TimeStamp']; } 
                        if (($min===false) || ($min>$aktwert)) { $min=$aktwert; $minTime=$wert['TimeStamp']; } 
                        $altwert=$aktwert; 
                        $altzeit=$wert['TimeStamp'];            //brauche den Zeitstempel vor der tagesänderung
                        //$count2++;
                        $countPeriode++; 
                        break;
                    default:
                    }
                }

            $zaehler+=1;                        // jeden Wert zählen

            if ($display==true)         // Anzeige, optional zum besseren Verständnis
                {
                if ($gepldauer++<200)           // nur die ersten hundert Einträge ausgeben und dann nur mehr den Wechsel
                    {
                    if ($debug) 
                        {
                        echo "   ".date("W d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . str_pad(number_format($aktwert, 3, ".", ""),17);
                        if ($aggType==0) echo " ergibt in Summe: " . str_pad(number_format($ergebnis, 3, ".", ""),17) . "   $countPeriode  $count2   " . PHP_EOL;
                        else 
                            {
                            if ($direction=="future") { $diff=($aktwert-$altwert2); $diffzeit=($zeit-$altzeit2); }
                            else {$diff=($altwert2-$aktwert); $diffzeit=($altzeit2-$zeit); }
                            echo "   $diff ($diffzeit)    $count2".PHP_EOL;
                            }
                        }
                    }
                /* Immer wenn Periode abgeschlossen hier anzeigen, es ist bereits der vorige index-1 Wert, da bereits weitergezählt wurde, Timestamp ist der letzte Zeitstempel vor dem Wechsel
                 */
                if ($showAgg && ($showAggCount++<20))
                    {
                    if ($debug && $showAgg) echo "   ".date("W d.m.Y H:i:s", $result[$index-1]['TimeStamp']) . " ergibt in Summe: " . str_pad(number_format($result[$index-1]["Value"], 3, ".", ""),17)."   ".str_pad(number_format($result[$index-1]["Avg"], 3, ".", ""),17)."  $altwert  $neuwert  $count2". PHP_EOL;
                    }
                }
            if ($increment==2) { $altwert2=$aktwert; $altzeit2=$zeit; }          // damit die Differenz in Display noch berechnet werden kann
            }               // Ende foreach werte

        $carryover=array();
        $carryover["Value"] = $ergebnis;
        $carryover["countPeriode"] = $countPeriode;
        return($carryover);
        }

    /* Alternative zu manualDailyAggregate, wenns nix zum Aggregieren gibt dann zumindest die Grundsätzlichen Evaluierungen machen 
     *
     */
    public function manualDailyEvaluate(&$result,$werte,$config,$debug=false)
        {
        //if (isset(($config["carryOver"]))===false) $ergebnis=0;           // wird nicht verwendet
        //else $ergebnis=$config["carryOver"]["Value"];

        $maxmin=array();                                    // Speicherplatz zur Verfügung stellen
        if (isset($config["Means"]["Full"])) $maxminFull = $config["Means"]["Full"];
        else $maxminFull = new maxminCalc($maxmin,"Full");       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen
        if (isset($config["Means"]["Day"])) $maxminDay = $config["Means"]["Day"];
        else $maxminDay = new maxminCalc($maxmin,"Day");
        $maxminFull->updateConfig($config,false);             // Fehlermeldung wenn maxminCalc verwendet wird, über array kommt meansCalc, true extended Debug
        $maxminDay->updateConfig($config);

        if (isset($config["Gaps"])) $gaps = $config["Gaps"];            // die class gapsEval
        else $gaps=false;

        foreach($werte as $wert)            // die Werte analysieren und bearbeiten, Standardfunktionen verwenden
            {
            $maxminFull->addValue($wert);
            $maxminDay->addValue($wert);
            if ($gaps) $gaps->addValue($wert);              // Gaps ermitteln
            }
        $carryover=array();
        //$carryover["Value"] = $ergebnis;
        return($carryover);       
        }


    /* Alternative zu manualDailyAggregate, wenns nix zum Aggregieren gibt dann zumindest die Grundsätzlichen Evaluierungen machen 
     * funktioniert für Counter um Intervalldaten zu generieren und zu evaluieren
     */
    public function manualDailyCounterEvaluate(&$result,$werte,$config,$debug=false)
        {
        //if (isset(($config["carryOver"]))===false) $ergebnis=0;           // wird nicht verwendet
        //else $ergebnis=$config["carryOver"]["Value"];
        if ($debug) echo "manualDailyCounterEvaluate \n";
        $maxmin=array();                                    // Speicherplatz zur Verfügung stellen
        if (isset($config["Means"]["Full"])) $maxminFull = $config["Means"]["Full"];
        else $maxminFull = new maxminCalc($maxmin,"Full");       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen
        if (isset($config["Means"]["Day"])) $maxminDay = $config["Means"]["Day"];
        else $maxminDay = new maxminCalc($maxmin,"Day");
        $maxminFull->updateConfig($config,false);             // Fehlermeldung wenn maxminCalc verwendet wird, über array kommt meansCalc, true extended Debug
        $maxminDay->updateConfig($config);

        if (isset($config["Gaps"])) $gaps = $config["Gaps"];            // die class gapsEval
        else $gaps=false;

        $initial=true;
        // mit welchem Index hängen wir die Daten an die bestehenden dran
        if (count($result)==0) $index=0;              // Wir brauchen einen Index zum abspeichern der Werte, wenn leer beginnen wir mit 0
        else
            {
            // ersten und letzten Key rausfinden und den Zeitstempel dazu
            $lastkey     = array_key_last($result);
            $firstkey    = array_key_first($result);
            $pastTime    = $result[$lastkey]["TimeStamp"];
            $futureTime  = $result[$firstkey]["TimeStamp"];
            if ($debug) echo "   Input batch data from ".date("d.m.Y H:i:s",$pastTime)." ($lastkey)  to  ".date("d.m.Y H:i:s",$futureTime)." ($firstkey) with ".count($werte)." Values.\n";
            $index=$lastkey+1;
            }

        foreach($werte as $wert)            // die Werte analysieren und bearbeiten, Standardfunktionen verwenden
            {
            // Intervall bestimmen
            if ($initial)
                {
                $alterWert=$wert["Value"];    
                $initial=false;
                }
            else 
                {
                $result[$index]["Value"]= $alterWert-$wert["Value"];
                if ( ($alterWert>0) && (($wert["Value"]/$alterWert)>40) ) echo "  manualDailyCounterEvaluate, Warning, Change too Big at ".date("d.m.Y H:i:s",$wert["TimeStamp"])." ".$wert["Value"]."/".$alterWert." .\n";
                $result[$index]["TimeStamp"] = $wert["TimeStamp"];
                $alterWert=$wert["Value"]; 
                $maxminFull->addValue($result[$index]);
                $maxminDay->addValue($result[$index]);
                if ($gaps) $gaps->addValue($result[$index]);              // Gaps ermitteln
                //echo "$index ".date("d.m.Y H:i:s",$result[$index]["TimeStamp"])."   ".$result[$index]["Value"]."\n";
                $index++;
                }
            }
        $carryover=array();
        //$carryover["Value"] = $ergebnis;
        return($carryover);       
        }

    /* archiveOps::getValues
     *
     * Parallelfunktion für analyseValues, diese function ist schon etwas weiter entwickelt
     * Get/AnalyseValues,ArchiveOps, Abgrenzung klar, eine kann auch ohne Zeitangabe Werte verarbeiten
     *
     * kann bereits beide Formate, Logged [Value/TimeStamp/Duration] und aggregated [Avg/TimeStamp/Min/Max] , und kann auch ein Array [Value/TimeStamp] verarbeiten
     * zusätzlich wird ein Remote Server Format mit Server::Dateiname unterstützt.
     *
     * verwendete Klassen:
     *      maxminCalc
     *      eventLogEvaluate
     *      meansRollEvaluate
     *
     * Es können Konfigurationen anstelle des logs Parameter übergeben werden, Werden mit setConfiguration ermittelt und bearbeitet, Einstellmöglichkeiten siehe unten 
     *  
     * Es gibt einen Umweg über Werte, dieses array wird zuerst erstellt und erst wenn die Vorbereitung fertig gestellt sind in this->result[oid][Values][]werte umgeschrieben
     * das führt zu einem höheren Speicherverbrauch. lässt sich aber nicht einfach umstellen
     *
     * Werte mit Archive haben möglicherweise keinen 0 Wert sondern die Dauer gibt an wann der Wert nicht mehr da ist. Es wird 0 angenommen und der Wert ergänzt.
     * 
     * Folgende Funktionen werden durchlaufen
     *      Verarbeitung und Vorbereitung Konfiguration, Einlesen der Werte
     *      Schnelleinschätzung und Analyse der erhaltenen Werte, CleanUp und Vorverarbeitung
     *
     * Debug mit verschiedenen Levels, startet bei 0/false und geht weiter, 1 ist Ergebnis Datenanalyse, 2 ist mit Mehrwert
     *
     * Ausgabe von verschiedenen Zusatzfunktionen der Kurvenanalyse
     * Struktur von ResultShares, Index ist der Aktienindexname
     *      Values
     *      MeansRoll
     *          MeansRoll
     *              Month
     *              Week
     *
     *      Description
     *          Max
     *          Min
     *          Latest
     *          Latest-TimeStamp
     *          Interval
     *              Full
     *              Var
     *              Day
     *              Week
     *              Month
     *          Change
     *          Scale
     *          Result
     *          Means
     *          MeansVar []
     *          Trend
     *          StdDev
     *          StdDevRel
     *          StdDevPos
     *          StdDevNeg
     *          eventLog []
     *          ContNeg
     *          CountPos
     *          Count
     *      Info
     *          OID
     *          ID
     *          Name
     *          Stueck
     *          Kosten     
     *
     * Übergebene Parameter
     *
     *  oid                 ist eine OID,  
     *
     * Über die $logs kann eine Konfiguration mitgegeben werden, die Überprüfung der Konfig gehört zur Statistik Klasse
     *
     *
     *  DataType            Archive , Array
     *  Aggregated          true, hourly, daily, weekly, monthly  nutzt eingebaute Funktion von IP Symcon, liefert Array Min, Max, Avg und Timestamp/Duration dazu
     *  manAggregate        wenn Aggregated false werden die geloggten Werte ausgelesen und dann manuell Aggregiert, siehe getArchivedValues
     *  returnResult        wenn Wert ist DESCRIPTION dann nur den Index Description ausgeben
     *
     *
     *
     */
    function getValues($oid,$logs,$debug=false)
        {
        // Konfiguration vorbereiten
        $statistics = new statistics();
        $config = $statistics->setConfiguration($logs);                     // aus logs die config generieren, durch den generellen check gehen und abspeichern
        
        // Orientierung mit Debug
        if ($debug>1) 
            {
            echo "archiveOps::getValues(";
            if (is_array($oid)) echo "array,";
            elseif (is_numeric($oid)) echo "$oid (".IPS_GetName($oid)."),";
            else echo "$oid mit lookfor Name, config";                                          // braucht Zusatzinformation
            echo json_encode($config)."...  aufgerufen.\n";
            echo "   Memorysize from Start onwards: ".getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb\n";
            //print_R($config);
            }

        // Default Ergebnis festlegen 
        $result=array();

        $configMeans=$statistics->getConfigMeansRoll();
        $meansCustom=$configMeans["count"];          // selber die letzten Werte zusammenzählen, nicht nach Datum vergleichen
        $meansCustomName=$configMeans["name"];

        /****************************** Verarbeitung und Vorbereitung Konfiguration, Einlesen der Werte, quick Check ob Ergebnis vorhanden
         */
        $maxLogsperInterval = $config["maxLogsperInterval"];                // $maxLogsperInterval==1 bedeutet alle Werte bearbeiten

        // Wertebereich festlegen, Vorwerte einlesen, Fehler erkennen und bearbeiten 
        if  ($config["DataType"]=="Array")
            {
            $werte=$oid;
            $oid="Array";           // Ist in Result ein Key, praktischerweise umbenennen
            if (is_array($werte)===false) $werte=false;
            }
        elseif  ($config["DataType"]==="Counter")     //logged Werte auslesen, Zählerstände in Vorschübe ändern
            {
            if ($debug>1) $debug1=true; else $debug1=false;
            //$werte = @AC_GetLoggedValues($this->archiveID, $oid, $config["StartTime"], $config["EndTime"], 0);          // kann nur 10000 Werte einlesen
            $werte = $this->getArchivedCounters($oid,$config,$debug1);                //hat keine Begrenzung, bedeutet aber doppelte Speicherung der Daten im memory, kann auch online Aggregierung
            if ($werte===false) $werte=array();
            if (isset($werte["Description"])) print_R($werte["Description"]);
            }
        elseif  ($config["Aggregated"]===false)     //logged Werte auslesen, eventuell manuell aggregieren
            {
            if ($debug>1) $debug1=true; else $debug1=false;
            //$werte = @AC_GetLoggedValues($this->archiveID, $oid, $config["StartTime"], $config["EndTime"], 0);          // kann nur 10000 Werte einlesen
            $werte = $this->getArchivedValues($oid,$config,$debug1);                //hat keine Begrenzung, bedeutet aber doppelte Speicherung der Daten im memory, kann auch online Aggregierung
            if ($werte===false) $werte=array();
            if (isset($werte["Description"])) print_R($werte["Description"]);
            }
        else                // aggregated Archiv auslesen
            {
            if ($debug>1) $debug1=true; else $debug1=false;
            $aggreg=$this->configAggregated($config["Aggregated"]);         // convert String in Config to integer
            //$werte   = @AC_GetAggregatedValues($this->archiveID, $oid, $aggreg, $config["StartTime"], $config["EndTime"], 0);             // 0 unlimited  
            $werte = $this->getArchivedAggregates($oid,$config,$debug1);                //hat keine Begrenzung, wie getArchivedValues nur mit Aggregates, beinhaltet Avg aber auch Min und Max und Time dazu
            $aggType = AC_GetAggregationType($this->archiveID, $oid);
            if ($debug>1) 
                {
                echo "    Aggregated is configured with ".$config["Aggregated"]." converted to $aggreg. Aggregation Type ist ";
                if ($aggType==0) echo "Standard"; else echo "Counter";
                echo " Range is ".$config["StartTime"]." to ".$config["EndTime"]."\n";
                }
            }

        if ($werte === false)             
            {
            if ($debug) echo "    $oid (".IPS_GetName($oid).") Ergebnis : no logs available, Value has no history\n";  
            $werte=array();
            }
        else            
            {
            if ($debug>2) 
                {
                echo "    getValues extended debug: $debug\n";
                //print_r($werte);
                if (is_array($werte))
                    {
                    if  ($config["DataType"]=="Array") echo "    --> Historie:\n";
                    else echo "    --> Historie des Wertes $oid (".IPS_GetName($oid).") mit aktuellem Wert ".GetValue($oid)." Anzahl  ".count($werte)."  ".($config["Aggregated"]?"Aggregated":"Logged")."\n";
                    $this->showValues($werte);
                    print_r($werte);
                    echo "    <--\n";
                    }
                }
            elseif ($debug>1)  echo "    getValues normal debug\n";
            $count = count($werte);
            $duration=0; $span=0;                   //default Werte für ein leeeres Array
            if ($count==0) { if ($config["Warning"]) echo "     Warnung, ein leeres Array übergeben, keine Eintraege gefunden.\n"; }
            else
                {
                $firstTime = $werte[array_key_last($werte)]["TimeStamp"];
                $lastTime  = $werte[array_key_first($werte)]["TimeStamp"];
                if ($maxLogsperInterval==1) $maxLogsperInterval=count($werte);
                $duration = $lastTime - $firstTime;
                $span = $duration/$count;
                }
            if ($debug>1) 
                {
                echo "   --> Ergebnis Abfrage ";
                if ($config["Aggregated"]===false) echo " logged ";
                else                               echo " aggregated ";
                echo " Archiv (getValues): ";
                //print_R($werte[array_key_first($werte)]);
                //print_R(array_key_last($werte));
                }
            }
        $config["maxLogsperInterval"]=$maxLogsperInterval;              // Eintrag überschreiben
        //if (isset($config["Split"])) $debug=true; else $debug=false;

        /****************************** Schnelleinschätzung und Analyse der erhaltenen Werte, CleanUp und Vorverarbeitung 
         *  Reihenfolge
         *      countperIntervalValues
         *      CleanUp mit Preprocess wie zB calculateSplitOnData
         *      ProcessOnData
         *
         */
        $resultIntervals = $this->countperIntervalValues($werte,$debug);        // true Debug, Aufruf von getValues
        if ($resultIntervals===false) return (false);                            // spätestens hier abbrechen, eigentlich schon vorher wenn wir draufkommen dass es kein Array mit historischen Werten gibt
        //print_r($result);
        if ($resultIntervals["order"] == "newfirst") 
            {
            if ($debug>1) echo "   --> Reihenfolge, neuerster Wert zuerst, hat niedrigsten Index, andersrum sortieren.\n";
            krsort($werte);             // andersrum sortieren
            }
        $this->cleanupStoreValues($werte,$oid,$config,$debug);          // Werte bereinigen und in this->result[$oid][Values] abpeichern, config übernimmt maxLogsperInterval            
        $this->processOnData($oid,$config,$debug);                        // Werte bearbeiten 
        if ($debug>1) echo "Memorysize after calculateSplitOnData: ".getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb\n";

        /******************************************* Vorbereitung Berechnung der beschreibenden Werte wie Trend, Mittelwert Analyse */
        /* maxminCalc macht Max Min und Means für den gesamten Zeitbereich */
        $maxmin=array();         // Speicherplatz zur Verfügung stellen
        $maxminFull   = new maxminCalc($maxmin);       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen

        // Event Log, events ist der lokale Speicher für die Ergebnisse 
        $events=array();
        if (isset($this->result[$oid]["Values"]))
            {
            $config["InputValues"]=$this->result[$oid]["Values"];              // <- etwas strange, hier versteckt alle Werte noch einmal zu übergeben
            //print_r($config);
            $eventLogAll = new eventLogEvaluate($events,"All",$config,$debug);             // events ist der Speicherplatz für die Ergebnisse der Berechnungen
            }

        /* Standardabweichung am Monatsmittelwert */
        $sdevSum=0; $sdevSumPos=0; $sdevSumNeg=0;

        /* rollierender Mittelwert, Defaultwerte bereits Teil der Config : TimeStampPos, CalcDirection */
        $meansRoll = new meansRollEvaluate($this->result[$oid]["Values"],$configMeans,($debug>4));                                     // rollierenden Mittelwert berechnen, benötigt das ganze Archiv, Array, false für keine Config, true für Debug
        /* Wertebereich bearbeiten */
        $lateStart=0; 
        $indexCount=0;                              // nur die Werte zählen für die es bereits einen gültigen Monatswert gibt
        $logCount=0;                                // alle Einträge zählen

        $konto=0; $geldwert=0; $depot=0;
        
        $mittelWertMonatZuletzt=false; $mittelWertWocheZuletzt=false;  
        $trendMonat=false;  $trendWoche=false; $trendTag=false;
        if (isset($this->result[$oid]["Values"]))           // gibts überhaupt Werte
            {
            $debugCount=12; $count = count($this->result[$oid]["Values"]);  $debugcheck=2;                    // die ersten 12 Werte anzeigen
            if ($debug>3) echo "Jetzt alle Werte einzeln durchgehen, einen Trend erkennen, Debug level $debug, zeige die ersten $debugCount Werte, Werte mit aufsteigenden Zeitstempel sortiert:\n";
            // Werte in this->result der Reihe nach durchgehen
            foreach ($this->result[$oid]["Values"] as $index => $entry)     // jetzt die Werte durchgehen, Werte sind mit aufsteigenden Zeitstempel sortiert
                {
                $logCount++;    
                $wertAktuell = $statistics->wert($entry);               // nur umrechnen, statistics ist die übergeordnete class

                $maxminFull->addValue($entry);
                $eventLogAll->addValueAsIndex($index);                  // Vorwert, Änderungs Analyse 
                if ($indexCount >= $lateStart)                          // nur brauchbar wenn in die Vergangenheit der Mittelwert berechnet wird oder es schon zu viele Werte sind
                    {
                    // Trendberechnung verwendet meansRollEvaluate, hat alle Werte als Array bereits übergeben bekommen
                    if ($meansCustom)               // wahrscheinlich eine Zahl
                        {
                        $mittelWertCustom = $meansRoll->meansValues($index, $meansCustom);                    // aus den nächsten oder vorigen Monatswerten einen Mittelwert berechnen, sollte auch für ein Intervall funktionieren
                        if ( (isset($mittelWertCustom["error"])) === false)
                            {
                            $this->result[$oid]["MeansRoll"][$meansCustomName][$index] = $mittelWertCustom;            // beide Werte mit Index abspeichern
                                
                            }
                        }
                    // Monatsmittelwerte auswerten mit meansRollEvaluate und erfassen mit meansValues, verwendet index und nicht Werte
                    $mittelWertMonat = $meansRoll->meansValues($index, "month");                    // aus den nächsten oder vorigen Monatswerten einen Mittelwert berechnen, sollte auch für ein Intervall funktionieren
                    if ( (isset($mittelWertMonat["error"])) === false)
                        {
                        $this->result[$oid]["MeansRoll"]["Month"][$index] = $mittelWertMonat;            // beide Werte mit Index abspeichern
                        $wertMonatMittel =  $statistics->wert($mittelWertMonat);                    // nur den Wert ohne TimeStamp extrahieren
                        /* Standardabweichung vom Monats Mittelwert */
                        $abw=($wertAktuell-$wertMonatMittel);
                        if ($abw>0) $sdevSumPos += ($abw*$abw);
                        else $sdevSumNeg += ($abw*$abw);
                        $sdevSum += ($abw*$abw);
                        $indexCount++;  
                        // Trendberechnung, Vergleich mit den Werten vor 20 Einträgen
                        if (isset($this->result[$oid]["MeansRoll"]["Month"][$index-20]))
                            {
                            /* Trendberechnung */
                            $wertMonatMittel2 =  $statistics->wert($this->result[$oid]["MeansRoll"]["Month"][$index-20]);                    // nur den Wert ohne TimeStamp extrahieren
                            if ($wertMonatMittel2!=0) $trendMonat = ($wertMonatMittel/$wertMonatMittel2-1);
                            $mittelWertMonatZuletzt = $mittelWertMonat;
                            }
                        }
                    elseif ($debug>1) echo "mittelWertMonat Berechnung mit Fehler bei Index $index :  ".$mittelWertMonat["error"]."\n";
                    // Wochenmittelwerte auswerten
                    $mittelWertWoche = $meansRoll->meansValues($index,"week");                    // aus den nächsten oder vorigen Wochenwerten einen Mittelwert berechnen
                    if ( (isset($mittelWertWoche["error"])) === false)                          // keinen Fehler in diesem Berechnungszyklus  erkannt
                        {                
                        $this->result[$oid]["MeansRoll"]["Week"][$index]  = $mittelWertWoche;       // es gibt jeden Tage einen Mittelwert der Woche
                        $wertWocheMittel =  $statistics->wert($mittelWertWoche);                    // nur den Wert ohne TimeStamp extrahieren
                        for ($gobackDays=0;$gobackDays<=5;$gobackDays++)
                            {
                            if (isset($this->result[$oid]["MeansRoll"]["Week"][$index-$gobackDays]))
                                {
                                /* Trendberechnung */
                                $wertWocheMittel2 =  $statistics->wert($this->result[$oid]["MeansRoll"]["Week"][$index-$gobackDays]);                    // nur den Wert ohne TimeStamp extrahieren
                                if ($wertWocheMittel2!=0) $trendWoche = ($wertWocheMittel/$wertWocheMittel2-1);
                                $mittelWertWocheZuletzt = $mittelWertWoche;
                                if ($debug>3) echo "search for Mittelwert before one week ".json_encode($this->result[$oid]["MeansRoll"]["Week"][$index-$gobackDays])."\n";
                                }
                            }
                        }
                    elseif ($debug>1) echo "mittelWert Woche Berechnung mit Fehler bei Index $index :  ".$mittelWertWoche["error"]."\n";
                    //$mittelWertTag = $meansRoll->meansValues($index,  1);                    // aus den nächsten 1 Werten einen Mittelwert berechnen, 1 liefert den aktuellen Wert
                    if ( ($logCount>($count-$debugCount)) && ($debug>$debugcheck) ) echo str_pad($logCount,6).date("d.m.Y H:i:s",$entry["TimeStamp"])."  $wertAktuell";
                    // Trend für aktuellen und letzten Tageswerte berechnen
                    if ( (isset($this->result[$oid]["Values"][($index-1)])) && ($config["DoCheck"]["isNumeric"]) )          // Auswertung mit Zahlen                    
                        {
                        // Wert und Vorwert nehmen, aus zeitlichem Abstand und der Wertdifferenz den trend ausrechnen
                        if ($config["Aggregated"]) 
                            {
                            $value1=$this->result[$oid]["Values"][$index]["Avg"];
                            $value2=$this->result[$oid]["Values"][($index-1)]["Avg"];
                            if ($value2==0) $trendTag=false;
                            else $trendTag=($value1/$value2-1)*100;
                            }
                        else                // Logging
                            {
                            $value1=$this->result[$oid]["Values"][$index]["Value"];                                     // aktueller Wert
                            $timestamp1=date("d.m.Y H:i:s",$this->result[$oid]["Values"][$index]["TimeStamp"]);
                            $value2=$this->result[$oid]["Values"][($index-1)]["Value"];                                 // voriger Wert
                            $timestamp2=date("d.m.Y H:i:s",$this->result[$oid]["Values"][($index-1)]["TimeStamp"]);
                            if ($value2==0) 
                                {
                                //echo "Warning, getValues, Division by 0. $value1/$value2 $timestamp1 vs $timestamp2\n";
                                $trendTag=false;
                                }
                            else $trendTag=($value1/$value2-1);
                            if ( ($logCount>($count-$debugCount)) && ($debug>$debugcheck)) echo "  Change    $value1 ($timestamp1) $value2 ($timestamp2)    ".nf($trendTag,"%");         // value2 ist der vorige Tag
                            }
                        }
                    if ( ($logCount>($count-$debugCount)) && ($debug>$debugcheck) ) echo "\n";

                    
                    /*----------------------------------------------------------------------------------*/
                    if (strtoupper($config["KIfeature"])=="SHARES")
                        {
                        /* KI Feature 
                        * fixe Stückzahlen bewertet die Aktien mit hohem Kurs stärker
                        * fester Kauf und Verkaufspreis erscheint gerecht
                        *
                        */
                        $stueck=100;
                        $budget=200;    // sonst false
                        //$budget=false;
                        $action=  "     ";
                        if ( (isset($mittelWertMonat["error"])) === false)      // es gibt bereits eiunen Monatsmittelwert, andernfalls sind die Datenmengen noch zu klein oder falsch
                            {
                            if ( ($wertAktuell>$wertMonatMittel) && ($wertAktuell>$wertWocheMittel) ) 
                                {
                                $action=  "sell ";
                                if ($budget) $stueck = $budget/$wertAktuell; 
                                if ($depot >= $stueck)
                                    {
                                    $depot -= $stueck;
                                    $konto += $stueck * $wertAktuell;                        
                                    }
                                } 
                            if ( ($wertAktuell<$wertMonatMittel) && ($wertAktuell<$wertWocheMittel) ) 
                                {
                                $action=  "buy  ";
                                if ($budget) $stueck = $budget/$wertAktuell; 
                                $depot += $stueck;
                                $konto -= $stueck * $wertAktuell;                        
                                } 
                            $geldwert = $depot * $wertAktuell;
                            if ($debug>1) 
                                {
                                echo str_pad($index,6).date("d.m.Y H:i:s",$entry["TimeStamp"])." Aktuell $action ".nf($wertAktuell,"€",8)." ".nf($wertWocheMittel,"€",8).nf($wertMonatMittel,"€",8)." bis ".date("d.m.Y H:i:s",$mittelWertMonat["startTime"]);
                                echo "                ".nf($depot,"",8)."    ".nf($geldwert,"€",12)."   $konto   \n";
                                }
                            }
                        }
                    else                // Ausgeabe der einfachen Trend für Woche und Monta, Berechnung erfolgt weiter oben
                        {
                        if ( ($debug>4) )           // debug Level sehr hoch ansetzen
                            {
                            echo str_pad($index,6).date("d.m.Y H:i:s",$entry["TimeStamp"])." Aktuell ".nf($wertAktuell,"€",8);
                            if ( (isset($mittelWertWoche["error"])) === false)                                                              // kein fehler
                                {
                                echo " Mittel Woche ".nf($wertWocheMittel,"€",8)." (".date("d.m.Y H:i:s",$mittelWertWoche["TimeStamp"]).") ";
                                if ($trendWoche) echo "   ".nf($trendWoche,"%")." ";
                                else echo "          ";
                                }
                            else echo "                                             ";
                            if ( (isset($mittelWertMonat["error"])) === false)                                                                  // kein fehler
                                {
                                echo "Monat ".nf($wertMonatMittel,"€",8)." ,berechnet bis ".date("d.m.Y H:i:s",$mittelWertMonat["startTime"]);
                                if ($trendMonat) echo "   ".nf($trendMonat,"%")." ";
                                echo "\n";
                                }
                            else echo "                                           \n";
                            }

                        }
                    }

                if (isset($entry["Max"]))
                    {
                    //echo str_pad($index,4).date("d.m.Y",$entry["TimeStamp"])." ".str_pad($entry["Max"],12)." ".date("H:i:s",$entry["MaxTime"])." ".str_pad($entry["Min"],12)." ".date("H:i:s",$entry["MinTime"])."\n";
                    }
                else 
                    {

                    }
                }            //--endeforeach values, Auswertung beginnen
            
            //echo "--endeforeach values, Auswertung beginnen.\n"
            $maxminFull->calculate();                   // Max Min Werte im Ergebnis Array maxmin abspeichern
            $maxminFull->youngest();
            //$maxminFull->print();
            //print_r($maxmin);
            $this->result[$oid]["Description"]["MaxMin"] = $maxmin["All"];              // All ist die Defaultgruppe für die Ausgabe
            $means=$maxmin["All"]["Means"]["Value"];

            $this->result[$oid]["Description"]["Intervals"]=$resultIntervals;               // von countperIntervalValues
            // Kompatibilität mit analyseValues
            $this->result[$oid]["Description"]["Max"]=$maxmin["All"]["Max"]["Value"]; 
            $this->result[$oid]["Description"]["Min"]=$maxmin["All"]["Min"]["Value"];       
            $this->result[$oid]["Description"]["Means"]=$means;
            $this->result[$oid]["Description"]["Change"]=$trendTag;  
            // Description.Interval  : Full, Var, Day, Week, Month   -> nur Month/Week/Day mit Trend übernehmen
            $this->result[$oid]["Description"]["Interval"]["Full"]["Min"]=$maxmin["All"]["Min"]["Value"];     
            $this->result[$oid]["Description"]["Interval"]["Full"]["Max"]=$maxmin["All"]["Max"]["Value"];     
            $this->result[$oid]["Description"]["Interval"]["Month"]["Trend"]=$trendMonat;                         
            $this->result[$oid]["Description"]["Interval"]["Week"]["Trend"]=$trendWoche;                         
            $this->result[$oid]["Description"]["Interval"]["Day"]["Trend"]=$trendTag;                         

            if (strtoupper($config["KIfeature"])=="SHARES")
                {
                $this->result[$oid]["Analytics"]["stueck"]=$stueck;
                $this->result[$oid]["Analytics"]["geldwert"]=$geldwert;
                $this->result[$oid]["Analytics"]["konto"]=$konto;
                }

            // Description.Trend  : Day, Week, Month    die letzten aktuellen Trends
            //print_R($trendTag);
            if (isset($this->result[$oid]["Description"]["Trend"])) print_R($this->result[$oid]["Description"]["Trend"]);
            else
                {
                $this->result[$oid]["Description"]["Trend"]["Day"]=$trendTag;
                $this->result[$oid]["Description"]["Trend"]["Week"]=$trendWoche;
                $this->result[$oid]["Description"]["Trend"]["Month"]=$trendMonat;
                if ($debug>2) echo "Zusammenfassung der berechneten Trends: Monat ".nf($trendMonat,"%")." Woche ".nf($trendWoche,"%")." Tag ".nf($trendTag,"%").".\n";
                }
                
            $this->result[$oid]["Description"]["MeansPeriode"]["Week"]=$mittelWertWocheZuletzt;
            $this->result[$oid]["Description"]["MeansPeriode"]["Month"]=$mittelWertMonatZuletzt;

            $this->result[$oid]["Description"]["Latest"]=$maxmin["All"]["Youngest"];

            /* Standardabweichung Ergebnis getValues abspeichern, es muss zumindest einen Monatsmittelwert geben, 
             * dann wird der Abstand zum Monatsmittelwert zum jeweiligen zeitpunkt getrennt für die positiven und negativen Abweichungen und gesamt ermittelt 
             * Die Standarabweichung ist der Wert dividiert durch die Anzahl der verwendeten Werte als Wurzel 
             * in Prozent wird der jeweilige Wert in Relation zum gesamten Mittelwert gezogen, das ist ein kennwert für die jeweilige Volatilität des wertes
             * Trendberechnung für aktuell und letzten jewils Tag, Woche und Monat. Sehr ungenau da nur in etwa die Anzahl der Werte nicht aber die Zeit berücksichtigt wird
             */
            if ($indexCount>0)
                {
                $sdev = sqrt($sdevSum/$indexCount);
                $sdevRelPos = sqrt($sdevSumPos/$indexCount)/$means*100;
                $sdevRelNeg = sqrt($sdevSumNeg/$indexCount)/$means*100;
                $this->result[$oid]["Description"]["StdDev"]=$sdev;
                $this->result[$oid]["Description"]["StdDevRel"]=$sdev/$means*100;
                $this->result[$oid]["Description"]["StdDevPos"]=$sdevRelPos;
                $this->result[$oid]["Description"]["StdDevNeg"]=$sdevRelNeg;
                if ($debug) 
                    {
                    echo "Mittelwert : ".nf($means)." Sdev ist ".number_format($sdev,2,",",".")."  und relativ ".number_format($this->result[$oid]["Description"]["StdDevRel"],2,",",".")."% \n";
                    echo "Aktuell ".nf($wertAktuell)." Max ".nf($maxmin["All"]["Max"]["Value"])." Min ".nf($maxmin["All"]["Min"]["Value"])." Trend Monat ".nf($trendMonat,"%")." Woche ".nf($trendWoche,"%")."\n";
                    }
                }
            elseif ($debug) echo "     Warnung, $oid, noch nicht ausreichend gültige Werte für die Mittelwert und Standardabweichungsberechnung verfügbar.\n";
            $this->result[$oid]["Description"]["eventLog"]=$events["All"]["eventLog"];            
            $this->result[$oid]["Description"]["Count"]=$logCount;                              // alle Werte, die für die Berechnung des Mittelwertes herangezogen wurden
            if ($debug && false)
                {
                //print_R($this->result[$oid]["Description"]["eventLog"]);
                foreach ($this->result[$oid]["Description"]["eventLog"] as $time => $event) echo date("d.m.Y H:i:s",$time)." ".$event["Event"]."\n";
                }
            //echo "\n Auswertung beendet.\n";
            }

        //return ($werte);
        //print_R($config);
        if (strtoupper($config["returnResult"])=="DESCRIPTION") 
            {
            if ($debug) echo "Result will be only Description.\n";
            return ($this->result[$oid]["Description"]);
            }
        else return ($this->result[$oid]);
        }

    /* archiveOps::analyseValues
     *
     * Analyse der letzen Werte im Archive. Hier geht man bereits von einer geordneten Struktur aus, es gelten die folgenden Einschränkungen
     *    - es werden für die OID nur Einzelwerte zugelassen, keine aggregierten Werte, TimeStamp ist optional 
     *    - Angabe Parameter oid und logs (Anzahl Werte) verpflichtend
     *    - die Zahl logs muss durch 2 dividierbar sein, sonst wird aufgerundet
     *    - logs + logs/2 muss kleiner 10.000 sein
     *    - Logs kann auch ein array sein ["StartTime"=>$StartTime,"EndTime"=>$EndTime] und eine Zeitspanne angeben
     *
     * Rückgabe ist ein Array mit den bereinigten Werten als Referenz, und den Ergebnissen der Auswertung
     *
     * Folgende Parameter werden analysiert:
     *      Maximalwert
     *      Minimalwert
     *      Mittelwert
     *      erster und zweiter Mittelwert 
     *
     * Mehrstufige Bearbeitung:
     *      Inputparameter bewerten
     *      Vorwerte einlesen und bereinigen, Ergebnis als neues Array in der class $result
     *
     * verwendete Klassen:
     *      meansCalc
     *      eventLogEvaluate
     *
     * Ergebnis
     *      Value
     *      MeansRoll
     *      Description
     *
     */

    function analyseValues($oid,$logs,$debug=false)
        {
        /* für logs false true oder einen Integer Wert zulassen, integer ist die Anzahl der Log werte die ausgegeben wird */
        //if ($debug) echo "--->analyseValues für $oid (".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).") aufgerufen.\n";

        $statistics = new statistics();
        $config = $statistics->setConfiguration($logs);

        $logs      = $config["Logs"];
        $maxLogsperInterval = $config["maxLogsperInterval"];

        if ($debug) 
            {
            if ($config["StartTime"]) echo "archiveOps::analyseValues aufgerufen, für $oid (".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).") aufgerufen, Werte von ".(date("d.m.Y H:i:s",$config["StartTime"]))." bis ".(date("d.m.Y H:i:s",$config["EndTime"]))."\n";
            elseif ($logs==0) echo "archiveOps::analyseValues aufgerufen, für $oid (".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).") aufgerufen, alle vorhandenen Werte werden verarbeitet\n";
            else echo "archiveOps::analyseValues aufgerufen, für $oid (".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).") aufgerufen, $maxLogsperInterval Werte werden verarbeitet\n";
            }

        /* Wertespeicher initialisieren */
        $this->result=array();
        
        /* Vorwerte einlesen, Fehler erkennen und bearbeiten */
        
        $werte = @AC_GetLoggedValues($this->archiveID, $oid, $config["StartTime"], $config["EndTime"], 0);
        if ($werte === false)             
            {
            if ($debug) echo "Ergebnis : no logs available\n";  
            $werte=array();
            }
        else            // $maxLogsperInterval==1 bedeutet alle Werte bearbeiten
            {
            if ($maxLogsperInterval==1) $maxLogsperInterval=count($werte);
            if (($debug) && (is_array($logs) === false) ) echo "      --> Ergebnis Abfrage Archiv (analyseValues) für logged Werte: ".count($werte)." Werte verfügbar. Erster Wert vom ".date("d.m.Y H:i:s",$werte[array_key_last($werte)]["TimeStamp"])."\n";
            //print_R($werte[array_key_first($werte)]);
            //print_R(array_key_last($werte));
            }

        $this->cleanupStoreValues($werte,$oid,$maxLogsperInterval,$debug);          // Werte bereinigen und in result[$oid][Values] abpeichern
        unset($werte);          //free memory
        $this->calculateSplitOnData($oid,$config,$debug);                             // true debug
        $check = $this->countperIntervalValues($this->result[$oid]["Values"],$debug);        // true Debug, Aufruf von analyseValues
        if ($check !== false)
            {
            /* Analyse Ergebnis aus den ausgewählten archivierten Werten */

            /* Mittelwertberechnung für Trendanalysen vorbereiten */
            $means=array();         // Speicherplatz zur Verfügung stellen
            $meansFull   = new meansCalc($means);       	                        // Full, ohne Parameter wird der ganze Datensatz (zwischen Start und Ende) genommen
            $meansVar   = new meansCalc($means, "Var",$logs);                       // Ergebnis in means[Var]
            $meansDay   = new meansCalc($means, "Day",2);                           // Ergebnis in means[Day]
            $meansWeek  = new meansCalc($means, "Week",10);
            $meansMonth = new meansCalc($means, "Month",40);
            //print_R($means);

            /* Event Log, events ist der lokale Speicher für die Ergebnisse */
            $events=array();
            $eventLogAll = new eventLogEvaluate($events,"All",$config,$debug);             // events ist der Speicherplatz für Berechnungen

            /* rollierender Mittelwert , es gibt nur Month und der fix mit 20 Werten */
            $meansRoll = new meansRollEvaluate($this->result[$oid]["Values"]);              // das Array mit den Werten, die Config und Debug       

            /* Wertebereich bearbeiten */
            $logCount=0; $logCount1=0; $logCount2=0;
            $summe=0; $summe1=0; $summe2=0; $sumTime1=0; $sumTime2=0; 
            //$summeDay1=0; $summeDay2=0; $summeWeek1=0; $summeWeek2=0; $summeMonth1=0; $summeMonth2=0;             // zu kompliziert
            $max=0; $min=0; $youngestTime=0; $youngestValue=0; $change=0;
            $scale=0; $oldestTime=0; 

            $previousOne=false; $countPos=0; $countPosMax=0; $countNeg=0; $countNegMax=0; $changeDir="";

            $displayCalcTable=false;
            if ($debug && $displayCalcTable) echo " # Stunde Datum Wert       Mittelwert       Rollierend          Mittel1       Mittel2\n";
            foreach($this->result[$oid]["Values"] as $index => $wert)
                {
                $eventLogAll->addValue($wert);                  /* Vorwert, änderungs Analyse */

                /* Skalierung auf 100 Ausgangswert, das heisst der älteste Wert hat 100 wenn man den Wert mit scale multipliziert */
                if ( ($wert['TimeStamp']<$oldestTime) || ($oldestTime==0) )
                    {
                    $oldestTime=$wert['TimeStamp'];
                    $scale=100/$wert['Value'];   
                    }

                /* Mittelwert Gesamt berechnen, das heisst alle Werte zusammenzählen und Anzahl der Werte festhalten */
                $summe += $wert['Value'];
                $logCount++;

                /* Trendanalyse mit 2 Mittelwerten, variable Länge in Logs, sonst 10=2*5 */
                if ($logCount<=$logs)
                    {
                    if ($logCount>($logs/2)) 
                        {
                        $summe2 += $wert['Value'];
                        $sumTime2 += $wert['TimeStamp'];
                        $logCount2++;
                        }
                    else 
                        {
                        $summe1 += $wert['Value'];
                        $sumTime1 += $wert['TimeStamp'];
                        $logCount1++;
                        }
                    }

                $meansFull->addValue($wert);                        // oder $wert['Value'] aber dann wird kein mittler Timestamp berechnet
                $meansVar->addValue($wert);
                $meansDay->addValue($wert);
                $meansWeek->addValue($wert);
                $meansMonth->addValue($wert);

                /* rollierender Mittelwert , dienen und die nächsten 5 Werte zusammenzählen */
                $sumRol=0; $countRol=0;
                for ($i=0;$i<5;$i++)
                    {
                    //echo " ".($index+$i);
                    if (isset($this->result[$oid]["Values"][$index+$i]["Value"]))
                        {
                        //echo "->".$this->result[$oid]["Values"][$index+$i]["Value"];
                        $sumRol += $this->result[$oid]["Values"][$index+$i]["Value"];
                        $countRol++;
                        }
                    }
                //echo "           $sumRol   $countRol ".($sumRol/$countRol)."\n"; 
                //echo "               $index\n";
                $this->result[$oid]["MeansRoll"]["Var"][$index]["Value"]=$sumRol/$countRol;
                $this->result[$oid]["MeansRoll"]["Var"][$index]["TimeStamp"]=$wert['TimeStamp'];

                $this->result[$oid]["MeansRoll"]["Month"][$index] = $meansRoll->meansValues($index, 20);

                /*                */
                //echo "   Vergleiche ".$wert['TimeStamp'].">$youngestTime $change Wert $index : ".$this->result[$oid]["Values"][$index]['Value']."  ";
                if ($wert['TimeStamp']>$youngestTime)
                    {
                    //echo " do ";
                    $youngestTime=$wert['TimeStamp'];
                    $youngestValue=$wert['Value'];   
                    if (isset($this->result[$oid]["Values"][$index+1]['Value'])) 
                        {
                        //echo "store";
                        $change=(($this->result[$oid]["Values"][$index]['Value']-$this->result[$oid]["Values"][$index+1]['Value'])/$this->result[$oid]["Values"][$index+1]['Value'])*100;
                        }
                    }
                //echo "\n";
                if ( ($wert['Value']>$max) || ($max==0) ) $max = $wert['Value'];
                if ( ($wert['Value']<$min) || ($min==0) ) $min = $wert['Value'];
                if ($debug && $displayCalcTable) 
                    {
                    echo "  ".str_pad($logCount,2)." ".date("H d.m",$wert['TimeStamp'])."  ".number_format($wert['Value'],2,",",".")."   ";
                    //echo str_pad(number_format($summe,2,",","."),18," ", STR_PAD_LEFT)."   ";
                    echo str_pad(number_format($summe/$logCount,2,",","."),18," ", STR_PAD_LEFT);
                    //echo str_pad(number_format($sumRol,2,",","."),18," ", STR_PAD_LEFT)."   $countRol";
                    echo str_pad(number_format($sumRol/$countRol,2,",","."),18," ", STR_PAD_LEFT);
                    echo str_pad(number_format($summe1/$logCount1,2,",","."),18," ", STR_PAD_LEFT);
                    if ($logCount2>0) echo str_pad(number_format($summe2/$logCount2,2,",","."),18," ", STR_PAD_LEFT);
                    else echo str_pad(" ",18);
                    echo "\n";
                    }
                }           // ende alle Werte durchgehen

            //if ($debug) print_r($this->result[$oid]["Description"]["MeansRoll"]); 
            $meansFull->calculate();     
            $meansVar->calculate();
            $meansDay->calculate();
            $meansWeek->calculate();
            $meansMonth->calculate();             

            //echo "Summe Var $logs $summe1 $summe2 \n";
            $this->result[$oid]["Description"]["Max"]=$max;
            $this->result[$oid]["Description"]["Min"]=$min;
            $this->result[$oid]["Description"]["Latest"]["Value"]=$youngestValue;
            $this->result[$oid]["Description"]["Latest"]["TimeStamp"]=$youngestTime;

            $meansMonth->extrapolate($youngestTime);
            //print_R($means["Result"]);     
            $this->result[$oid]["Description"]["Interval"]=$means["Result"];

            $this->result[$oid]["Description"]["Change"]=$change;
            $this->result[$oid]["Description"]["Scale"]=$scale;
            $this->result[$oid]["Description"]["Result"]=$scale*$youngestValue-100;
            if ($debug) echo "Ergebnis Wert seit der ersten Messung ist ".number_format($this->result[$oid]["Description"]["Result"],2,",",".")."% Ergebnis zu Max ist ".number_format($youngestValue/$max*100-100,2,",",".")."%.\n";
            $means=$summe/$logCount;
            $this->result[$oid]["Description"]["Means"]=$means;
            $means1=$summe1/$logCount1;
            $this->result[$oid]["Description"]["MeansVar"][0]["Value"]=$means1;
            $this->result[$oid]["Description"]["MeansVar"][0]["TimeStamp"]=$sumTime1/$logCount1;
            //echo "logCount2 $logCount2 \n";
            if ($debug) echo "Mittelwert ist ".number_format($means,2,",",".")." ".number_format($means1,2,",",".")." ";
            if ($logCount2>0) 
                {
                $means2=$summe2/$logCount2;
                $this->result[$oid]["Description"]["MeansVar"][1]["Value"]=$means2;
                $this->result[$oid]["Description"]["MeansVar"][1]["TimeStamp"]=$sumTime2/$logCount2;            
                $this->result[$oid]["Description"]["Trend"]=($means1/$means2-1)*100;
                if ($debug) echo "Mittelwert ist ".number_format($means2,2,",",".")." Trend ".number_format($this->result[$oid]["Description"]["Trend"],2,",",".")."% ";
                }
            if ($debug) echo "\n";

            /* Standardabweichung, noch einmal alle Werte durchgehen */
            $sdevSum=0; $sdevSumPos=0; $sdevSumNeg=0;
            foreach ($this->result[$oid]["Values"] as $wert)
                {
                $abw=($wert['Value']-$means);
                if ($abw>0) $sdevSumPos += ($abw*$abw);
                else $sdevSumNeg += ($abw*$abw);
                $sdevSum += ($abw*$abw);
                }
            $sdev = sqrt($sdevSum/$logCount);
            $sdevRelPos = sqrt($sdevSumPos/$logCount)/$means*100;
            $sdevRelNeg = sqrt($sdevSumNeg/$logCount)/$means*100;
            
            $this->result[$oid]["Description"]["StdDev"]=$sdev;
            $this->result[$oid]["Description"]["StdDevRel"]=$sdev/$means*100;
            $this->result[$oid]["Description"]["StdDevPos"]=$sdevRelPos;
            $this->result[$oid]["Description"]["StdDevNeg"]=$sdevRelNeg;
            if ($debug) echo "Sdev ist ".number_format($sdev,2,",",".")."  und relativ ".number_format($this->result[$oid]["Description"]["StdDevRel"],2,",",".")."% \n";

            //print_r($events);
            $this->result[$oid]["Description"]["eventLog"]=$events["All"]["eventLog"];

            /* Auswertung wie oft der tägliche Wechsel hintereinander nach plus oder minus schaut */
            $this->result[$oid]["Description"]["CountNeg"]=$events["All"]["countNegMax"];
            $this->result[$oid]["Description"]["CountPos"]=$events["All"]["countPosMax"]; 
            $this->result[$oid]["Description"]["Count"]=$logCount;                      // alle Werte die für die Berechnung des Mittelwertes herangezogen wurden
            return ($this->result[$oid]);    
            }
        else return(false);
        }

    /*
     *
     */

    function alignScaleValues()
        {
        $ergebnis=array();
        foreach ($this->result as $oid=>$werte)
            {
            //print_R($werte);
            echo "$oid    ";
            $ergebnis[$oid]=$werte;
            $erster=array_key_first($werte["Values"]);
            $letzter=array_key_last($werte["Values"]);
            echo "Datum Zeit erster Wert ($letzter): ".date("d.m.y H:i:s",$werte["Values"][$letzter]["TimeStamp"])."\n";
            }
        print_r($ergebnis[$oid]);

        }


    /* archiveOps, zusätzliches Infofeld speichern */

    public function addInfoValues($oid,$share)
        {
        $this->result[$oid]["Info"]=$share;
        }

    /* archiveOps, convert config aggregated to value */

    private function configAggregated($aggregated)
        {
        switch (strval($aggregated))
            {
            case "0":
            case "hourly":
                $aggreg=0;
                break;
            case "1":
            case "daily":
                $aggreg=1;
                break;
            case "2":
            case "weekly";
                $aggreg=2;
                break;
            case "3":
            case "monthly";
                $aggreg=3;
                break;                    
            default:
                echo "Warning, do not understand \"".strval($config["Aggregated"])."\", expect hourly/daily/weekly/monthly.\n";
                $aggreg=1;
                break;
            }
        return ($aggreg);
        }

    /* transform start/endtime to numeric unix time
     * change directly in config array
     */
    private function configStartEndTime(&$config,$debug=false)
        {
        $starttime=$config["StartTime"];
        if (is_numeric($starttime)===false) 
            {
            $starttime=strtotime($starttime);
            if ($debug) echo "Starttime is not 0 or a number. Converted from String : $starttime (".date("d.m.Y H:i:s",$starttime).")\n";
            $config["StartTime"]=$starttime;
            }        
        $endtime=$config["EndTime"];
        if (is_numeric($endtime)===false) 
            {
            $endtime=strtotime($endtime);
            if ($debug) echo "Endtime is not 0 or a number. Converted from String : $endtime (".date("d.m.Y H:i:s",$endtime).")\n";
            $config["EndTime"]=$endtime;
            }  
        return(true);
        }

    /* archiveOps::cleanupStoreValues, Werte bereinigen und in this->result[oid][Values] abpeichern 
     * das bedeutet this->result kann mehrere oids, die Originalwerte und die Auswertung übernehmen
     *
     *  Konfiguration
     *      Interpolate         Zeitstempel interpolieren
     *      Integrate
     *      deleteSourceOnError
     *      Aggregated          [Avg] statt [Value] verwenden, aggregierte Werte
     *      AggregatedValue     anderen Wert festlegen als Avg, default ist Avg
     *
     * Konfiguration in cleanupData:
     *
     * andere Konfiguration berücksichtigt:
     *  Split       löst preprocess aus
     *
     * nur die maxLogsperInterval Anzahl von Werten übernehmen, Wert steht in config
     * die Anzahl der übernommenen Werte wird zurück gemeldet
     *
     * es werden die Werte als Pointer übergeben, es werden alle Werte überprüft
     * Werte mit 0 oder nicht numerische Werte werden in dem als Original mit Pointer übergegebenen Array gelöscht
     * Wenn eine Lücke, jetzt größer 600 Stunden, erkannt wird, werden die Werte davor ignoriert, es werden keine weiteren Werte in result[Vales] abgespeichert
     *
     * Aggregierte Werte werden bearbeitet, wenn config[Aggregated] wird "Avg" genommen, sonst "Value"
     *
     * kann "Split" für Aktienkurse, wenn split in der config dann wird diese als erstes berechnet
     *
     */

    private function cleanupStoreValues(&$werte,&$oid,$configInput,$debug=false)
        {
        $deleteIndex=array(); $deleteReason=array();                                // zusätzlich auch den Grund mitgeben
        $statistics = new statistics();
        $config = $statistics->setConfiguration($configInput);                      // braucht man nicht wurde schon bereinigt
        if (isset($config["cleanupData"])) 
            {
            $configCleanUp=$statistics->configCleanUpData($config["cleanupData"]);
            if ($debug>1) echo "   cleanupStoreValues aufgerufen. Konfiguration cleanupData gesetzt : ".json_encode($configCleanUp)."\n";
            }        
        else
            {
            $configCleanUp=$statistics->configCleanUpData([]);
            if ($debug>1) echo "   cleanupStoreValues aufgerufen. Konfiguration cleanupData nicht gesetzt. Verwende Default Werte : ".json_encode($configCleanUp)."\n";
            }
        if (isset($configCleanUp["Range"])) $config["Range"]=$configCleanUp["Range"];
        else $config["Range"]=false; 
        $config["maxLogsperInterval"]  = $configCleanUp["maxLogsperInterval"];  // default 10000, es geht auch false hier
        $config["deleteSourceOnError"] = $configCleanUp["deleteSourceOnError"];
        if ($debug && (isset($config["Split"]))) echo "      Preprocess, call calculateSplitOnData, Configuration : ".json_encode($config["Split"])."\n";
        $split=$this->prepareSplit($config,$debug);
        $count = @count($werte);
        if ($count && $split)                 // zumindest jeweils ein Eintrag sonst bleibt false
            {
            //print_r($split);
            $i=0; $iMax=10;    
            for ($i=0;$i<$count;$i++)                   // die Werte einem nach dem anderen durchgehen
                {
                $index = $count-$i-1;
                $timestamp=$werte[$index]["TimeStamp"];
                foreach ($split as $date => $factor)
                    {
                    if ($date>$timestamp)  
                        {
                        if (isset($werte[$index]["Value"])) $werte[$index]["Value"] = $werte[$index]["Value"]/$factor;
                        else $werte[$index]["Avg"] = $werte[$index]["Avg"]/$factor;             // die anderen auch, fehlt noch 
                        }
                    }
                if ($debug>1) 
                    {
                    if ($i<$iMax) echo $index." ".date("d.m.Y H:i:s",$timestamp)." ".$werte[$index]["Value"]."\n";
                    //print_r($entry);
                    }
                }
            }        
        // config check, support configless mode when config is maxLogsperInterval
        $maxDistance=600; $doDistanceCheck=false;
        if (is_array($config)) 
            {
            $maxLogsperInterval       = $config["maxLogsperInterval"];
            $suppressZero             = $config["SuppressZero"];
            if (isset($config["manAggregate"])==false) $doDistanceCheck = $config["maxDistance"];
            $doInterpolate            = $config["Interpolate"];
            $doIntegrate              = $config["Integrate"];  
            $deleteSourceonError      = $config["deleteSourceOnError"];
            if (isset($config["OIdtoStore"])) $oid = $config["OIdtoStore"];             // oid wird auch ausserhalb geändert
            if (isset($config["Range"])==false) $config["Range"]=false;
            }
        else 
            {
            $maxLogsperInterval = $config;
            $config=array();
            $config["SuppressZero"]=true;
            $config["Range"]=false;
            $doInterpolate=false;
            $doIntegrate=false;
            $deleteSourceonError=false;
            $config=array();
            }
        if (isset($config["Aggregated"])===false) $config["Aggregated"]=false;
        if ($doDistanceCheck) $maxDistance=$doDistanceCheck;                            // value in hours

        // debug orientation
        if ($debug>1)
            {
            echo "   Es werden $maxLogsperInterval Werte kopiert. Konfig : ".json_encode($config)."\n";
            if ($config["Aggregated"]) echo "   --> Es handelt sich um aggregierte Werte, nicht viel machen. \n";
            //print_r($config);
            }
        
        if ($debug>1) echo"    Search for doubles based on TimeStamp, ignore false values:\n";     // dazu neues array result anlegen mit Index Timestamp
        $check=array();
        unset ($this->result[$oid]);                        //cleanup, alte Werte sollen nicht bestehen bleiben
        $i=0; $d=0; $displayMax=20; $debug2=($debug>1);
        if ($debug2) echo "Werte, insgesamt ".sizeof($werte).", durchgehen.\n";
        foreach ($werte as $indexArchive => $wert)
            {
            if ($config["Aggregated"]) 
                {
                if (isset($config["AggregatedValue"]))
                    {
                    $wertUsed=$wert[$config["AggregatedValue"]];                // Timestamp egal
                    }
                else $wertUsed=$wert["Avg"];
                }
            else 
                {
                if (is_bool($wert["Value"])) 
                    {
                    $wertUsed=($wert["Value"]?1:0);   
                    }
                else $wertUsed=$wert["Value"];
                }
            if ($debug2>1)            // zusätzliches Debug on demand
                {
                if ($i==0) print_R($wert);
                if ($i<$displayMax)  echo str_pad($indexArchive,7)."  ".nf($wertUsed,"kWh")."   ".date("d.m.Y H:i:s",$wert["TimeStamp"])."   \n";
                }
            if ( ((is_numeric($wertUsed)==false) && $config["DoCheck"]["isNumeric"]) || ( ($wertUsed==0) && $config["DoCheck"]["SuppressZero"] ) ) 
                {
                if ( ($d<$displayMax) && $debug2)
                    {
                    if (is_numeric($wertUsed)===false) echo str_pad($indexArchive,7)."  $wertUsed   ".date("d.m.Y H:i:s",$wert["TimeStamp"])."   fehlerhafter Eintrag, kein numerischer Wert.\n";
                    else echo str_pad($indexArchive,7)."  $wertUsed   ".date("d.m.Y H:i:s",$wert["TimeStamp"])."   fehlerhafter Eintrag, Wert is 0 und config[suppressZero] ist gesetzt.\n";
                    } 
                $deleteIndex[$indexArchive]=$wert["TimeStamp"];
                $deleteReason[$indexArchive]["TimeStamp"]=$wert["TimeStamp"];
                $deleteReason[$indexArchive]["ValueChecked"]=$wertUsed;
                $deleteReason[$indexArchive]["Reason"]="Zero or not numeric";
                $d++;    
                }               
            elseif (isset($check[$wert["TimeStamp"]]))
                {
                if ( ($d<$displayMax) && $debug2) echo str_pad($indexArchive,7)."  ".nf($wertUsed,"kWh")."   ".date("d.m.Y H:i:s",$wert["TimeStamp"])."   doppelter Eintrag\n";
                $deleteIndex[$indexArchive]=$wert["TimeStamp"];
                $deleteReason[$indexArchive]["TimeStamp"]=$wert["TimeStamp"];
                $deleteReason[$indexArchive]["ValueChecked"]=$wertUsed;
                $deleteReason[$indexArchive]["Reason"]="Double";
                $d++;
                }
            else
                {
                if ($config["Range"])
                    {
                    if (( (isset($config["Range"]["max"])) && ($wertUsed>$config["Range"]["max"]) ) || ( (isset($config["Range"]["min"])) && ($wertUsed<$config["Range"]["min"]) ))
                        {
                        if ( ($d<$displayMax) && $debug2) echo str_pad($indexArchive,7)."  ".nf($wertUsed,"kWh")."   ".date("d.m.Y H:i:s",$wert["TimeStamp"])."   Eintrag ausserhalb der Grenzen\n";
                        $deleteIndex[$indexArchive]=$wert["TimeStamp"];
                        $deleteReason[$indexArchive]["TimeStamp"]=$wert["TimeStamp"];
                        $deleteReason[$indexArchive]["ValueChecked"]=$wertUsed;
                        $deleteReason[$indexArchive]["Reason"]="Range";                        
                        $d++;
                        }  
                    }
                $check[$wert["TimeStamp"]] = true;    
                $i++;
                }
            //if ( (is_float($wert["Value"])) && ($i<$displayMax)) echo "   Typ Float ok";
            //if ($i<$displayMax) echo "\n";
            }
        if ($doDistanceCheck)           // manueller istance Check, weil gerade praktisch, es reicht aber immer die Auswertung von Duration
            {
            if ($debug) echo "Do Distance check for less than $maxDistance hours.\n";
            ksort($check);
            $first=true;
            foreach ($check as $timeStamp => $status)
                {
                if ($first)                                     //den ersten Wert als referenz nehmen
                    { 
                    $first = false; 
                    $prevTimeStamp=$timeStamp;
                    }
                else
                    {
                    if ((($timeStamp-$prevTimeStamp)/60/60)>$maxDistance) { if ($debug) echo      "Gap from ".date("d.m.Y H:m:i",$prevTimeStamp)." to ".date("d.m.Y H:m:i",$timeStamp)."\n"; }
                    $prevTimeStamp=$timeStamp;
                    }
                }
            }

        if ($debug>1) 
            {
            echo "     --> Zusammenfassung gültig $i und ungültig $d Stück.\n";
            if ($d>0) 
                {
                echo "                Ungültig (marked for deletion) sind ".count($deleteIndex)." Einträge: ";
                //print_R($deleteIndex);
                foreach ($deleteIndex as $index => $value) 
                    {
                    //echo json_encode($value)."  ";
                    echo " $index=>".date("d.m.Y H:i:s",$value)."  ";
                    if (isset($werte[$index]["Value"])) echo $werte[$index]["Value"]." | ";
                    }
                echo "\n";          // alle in einer Zeile
                }
            }

        /* function, go
         * keine tests bei $config["Aggregated"]>0
         * wenn markiert für delete im deleteindex und $deleteSourceonError auch wirklich delete
         * in $this->result[$oid]["Values"] speichern
         */
        $onewarningonly=false;
        $logCount=0; $error=0; $ignore=false;
        $prevTime=false; $prevWert=false; $aktTime=false;
        $offset=0;
        foreach($werte as $index => $wert)              //können aggregierte und geloggte Werte
            {
            if (isset($wert["TimeStamp"])) 
                {
                $aktTime = $this->ipsOps->adjustTimeFormat($wert["TimeStamp"],"Ymd");         // true Debug
                $aktWert = $statistics->wert($statistics->addWert($wert,$offset));            // wert[value] wird um offset erhöht
                }
            if ($config["Aggregated"])                      // keine aufwendigen Überprüfungen, Aggregierte Werte sind per Default in Ordnung
                {
                $this->result[$oid]["Values"][]=$wert;
                $logCount++;
                }
            else                // logging Werte mit TimeStamp und Value, Werte müssen nicht unbedingt numerisch sein, diese Werte dann löschen, wenn konfiguriert auch 0 löschen
                {
                if ( (isset($deleteIndex[$index])) && ($deleteSourceonError) )
                    {
                    //if ($debug) print_R($wert);
                    if ($deleteSourceonError) unset ($werte[$index]);
                    $error++; 
                    }
                else            // gültiger Wert, numerisch, eventuell wenn so konfiguriert auch nicht 0, die anderen ignorieren
                    {
                    //print_R($wert);
                    if (isset($wert['Duration']))               // nur wenn es den Wert gibt überprüfen
                        {
                        $hours = ($wert['Duration']/60/60);
                        if ($hours>$maxDistance) 
                            {
                            if ($onewarningonly===false)
                                {
                                if ($debug) echo "   Warning, Gaps have reached maximum allowed value of $maxDistance hours.\n";
                                $onewarningonly=true;
                                }
                            if ($doDistanceCheck) $ignore=true;         // ein Gap gefunden und ab dann alles ignorieren
                            if ($debug) 
                                {
                                echo "        Fehler, Wert vom ".date("d.m.Y H:i:s",$wert['TimeStamp'])." bis ".date("d.m.Y H:i:s",$werte[($index-1)]['TimeStamp'])." mit ".number_format($hours,2,",",".")." Stunden, Parameter Duration, Abstand zu gross.\n";
                                //echo "                          ".date("d.m.Y H:i:s",$werte[($index-1)]['TimeStamp'])."   ".(($werte[($index)]['TimeStamp']-$werte[($index-1)]['TimeStamp'])/60/60)."\n";
                                }
                            }
                        }
                    if ($ignore===false)            // wenn alle Tests überstanden den Wert auch übernehmen, bildet automatisch prevWert und prevTime
                        {
                        if ($prevWert && $doIntegrate)          // es gibt einen ersten Wert, Integrate, es gibt einen Counter
                            {
                            if ($aktWert<$prevWert) 
                                {
                                $oldoffset = $offset;
                                $offset = $prevWert;           // beim Counter geht es immer wieder mit Null los
                                if ($debug) echo "Fehler, Wert vom ".date("d.m.Y H:i:s",$wert['TimeStamp'])." $aktWert<$prevWert increase offset to $offset .\n";
                                $aktWert = $statistics->wert($statistics->addWert($wert,$offset-$oldoffset));            // wert[value] wird um offset erhöht
                                }
                            if ($debug>2) echo  str_pad(date("d.m.Y H:i:s",$aktTime),12)."    ".str_pad($aktWert,10)."   ".($aktWert-$prevWert)."\n";                                
                            }
                        if ($prevTime && $doInterpolate)          // es gibt einen ersten Wert, jetzt den Abstand ermitteln, Wert ist Avg oder Value
                            {
                            $duration=($aktTime-$prevTime)/60/60;
                            $intervals=round($duration/24,0);
                            $intervalWert=($aktWert-$prevWert)/$intervals;
                            //echo "$duration Stunden. Wert ".date("d.m.Y H:i:s",$prevTime)." $prevWert   ".date("d.m.Y H:i:s",$aktTime)."   $aktWert  $intervals\n";
                            for ($i=1;$i<$intervals;$i++) 
                                {
                                $estValue["Value"]     = ($prevWert+$intervalWert*$i);
                                $estValue["TimeStamp"] = $prevTime+($i*24*60*60);
                                //echo "    ".date("d.m.Y H:i:s",$estValue["TimeStamp"])."    ".$estValue["Value"]."\n";
                                $this->result[$oid]["Values"][]=$estValue;          // hintereinander schreiben
                                }
                            }
                        $prevTime=$aktTime;
                        $prevWert=$aktWert;
                        $this->result[$oid]["Values"][]=$wert;          // hintereinander schreiben
                        $logCount++;
                        }
                    else $error++; 
                    }
                }
            if ( ($maxLogsperInterval) && ($logCount>$maxLogsperInterval) )
                {
                echo "cleanupStoreValues, max logs per Interval reached : $maxLogsperInterval .Break.\n";                    
                break;
                }
            }  
        if ($debug>1) 
            {
            echo "   --> cleanupStoreValues für $maxLogsperInterval Werte, ";
            echo "     $logCount Werte eingelesen.";
            if ($error) echo " $error Fehler.";
            echo "\n";
            }
        $result=array();
        $result["LogCount"]=$logCount;
        $result["delete"] = $deleteIndex;
        if (sizeof($deleteIndex)>0) 
            {
            if ($configCleanUp["provideReason"]) $this->result[$oid]["CleanUp"]["delete"]=$deleteReason;
            else $this->result[$oid]["CleanUp"]["delete"]=$deleteIndex;
            }
        return ($result);
        }


    /* archiveOps::processOnData
     * so wie calculateSplitOnData als Preprocess bevor die Daten gespeichert werden eine allgemeine Post Process Function auf Basis der gespeicherten Daten schaffen
     * Funktion arbeitet bereits auf Basis this->result.$oid.Values, alternative Funktion für on the fly Daten
     * zuerst Anzahl gespeicherter Daten feststellen, Go wenn >1
     *  Funktionen:
     *      Delta
     *
     */
    private function processOnData($oid,$config,$debug=false)
        {
        //$debug=true;
        // check ob genug Daten da sind 
        if ( (isset($this->result[$oid]["Values"])) && (is_countable($this->result[$oid]["Values"])) )
            {
            $count = @count($this->result[$oid]["Values"]);
            //if ($count<40) print_r($this->result[$oid]["Values"]);
            if (isset($config["processOnData"])) $configPoD=$config["processOnData"];
            else $configPoD=array();
            if ( (isset($configPoD["Range"]["max"])) && (isset($configPoD["Range"]["min"])) ) echo "We have active Range Check ".$configPoD["Range"]["max"]."<=>".$configPoD["Range"]["min"]."\n";

            if ($count===false) 
                {
                if ($debug) 
                    {
                    echo "processOnData, Fehler Array:";
                    print_r($this->result[$oid]["Values"]);
                    }
                }
            else        // mit den Daten in this->result arbeiten wenn ein ProcessOnData config Eintrag vorhanden ist
                {
                    if ($debug>1) 
                        {
                        //print_r($config);
                        echo "processOnData aufgerufen. Config ist ".json_encode($configPoD)."\n";
                        }
                    if (isset($configPoD["Delta"]))                    
                        {
                        $oldValue=false; $diff=false;
                        $d=0; $displayMax=10; 
                        foreach ($this->result[$oid]["Values"] as $index => $entry)
                            {
                            if ($oldValue !==false ) $diff=$entry["Value"]-$oldValue;
                            $oldValue=$entry["Value"];
                            $delete=false;
                            if ($diff !==false) 
                                {
                                // Range check, if active
                            if ((isset($configPoD["Range"])) && ($configPoD["Range"]))     // grösser max und kleiner min , damit 0 nicht dabei ist muss ein kleiner positiver Wert angegeben werden
                                    {
                                    if (( (isset($configPoD["Range"]["max"])) && ($diff>$configPoD["Range"]["max"]) ) || ( (isset($configPoD["Range"]["min"])) && ($diff<$configPoD["Range"]["min"]) ))
                                        {
                                        if ( ($d<$displayMax) && $debug2) echo str_pad($index,7)."  $diff (für ".$entry["Value"]."   ".date("d.m.Y H:i:s",$entry["TimeStamp"]).") Eintrag ausserhalb der Grenzen\n";
                                        //$deleteIndex[$index]=$entry["TimeStamp"];
                                        $delete=true;
                                        $d++;
                                        }  
                                    }
                                if ($delete===false) $this->result[$oid]["Values"][$index]["Value"]=$diff;           // wirklich Update der Variable
                                }
                            //echo date("d.m.y H:i:s",$entry["TimeStamp"])."   $oldValue => $diff \n";
                            }
                        }
                }
            }
        }

    /* archiveOps::calculateSplitOnData
        * eine Besonderheit von Aktien, es können Splits und Merges auftreten
        * Funktion arbeitet bereits auf Basis this->result, alternative Funktion für on the fly Daten
        *
        */
    private function calculateSplitOnData($oid,$config,$debug=false)
        {
        //$debug=true;
        // check ob genug Daten da sind 
        $count = @count($this->result[$oid]["Values"]);
        //if ($count<40) print_r($this->result[$oid]["Values"]);
        if ($count===false) 
            {
            if ($debug) 
                {
                echo "calculateSplitOnData, Fehler Array:";
                print_r($this->result[$oid]["Values"]);
                }
            }
        else        // mit den Daten in this->result arbeiten wenn ein Split index config Eintrag vorhanden ist
            {
            //if ($debug && (isset($config["Split"]))) echo "calculateSplitOnData, Configuration : ".json_encode($config["Split"])."\n";
            $split=$this->prepareSplit($config,$debug);
            if ($split)                 // zumindest ein Eintrag sonst bleibt false
                {
                //print_r($split);
                $i=0; $iMax=10;    
                for ($i=0;$i<$count;$i++)                   // die Werte einem nach dem anderen durchgehen
                    {
                    $index = $count-$i-1;
                    $timestamp=$this->result[$oid]["Values"][$index]["TimeStamp"];
                    foreach ($split as $date => $factor)
                        {
                        if ($date>$timestamp) $this->result[$oid]["Values"][$index]["Value"] = $this->result[$oid]["Values"][$index]["Value"]/$factor; 
                        }
                    if ($debug>1) 
                        {
                        if ($i<$iMax) echo $index." ".date("d.m.Y H:i:s",$timestamp)." ".$this->result[$oid]["Values"][$index]["Value"]."\n";
                        //print_r($entry);
                        }
                    }
                }
            else
                {
                if ($debug>1) 
                    {
                    echo "  --> calculateSplitOnData für $count Werte aufgerufen. kein Split in der Konfiguration.\n";
                    //print_R($config);
                    }                    
                }
            }
        }

    /* archiveOps, prepare Splits */

    private function prepareSplit($config,$debug=false)
        {
        //if ($debug && (isset($config["Split"]))) echo "prepareSplit ".json_encode($config["Split"])."\n";
        $split=false;
            if ( (isset($config["Split"])) && (is_array($config["Split"])) && (count($config["Split"])>0) )         // es gibt den Index, das Array und auch Eintraege für Splits 
                {
                $splitFound=false;
                if ($debug>1) 
                    {
                    echo "  --> calculateSplitOnData für $count Werte aufgerufen.\n";
                    print_R($config["Split"]);
                    }
                foreach ($config["Split"] as $date => $entry)                
                    {
                    $datetime=strtotime($date);
                    if ($debug) 
                        {
                        echo "calculateSplitOnData, ".date("d.m.Y H:i:s",$datetime)."   ".json_encode($entry)." ";
                        if ($datetime===false) echo "--> wrong datetime Format of \"$date\".";
                        echo "\n";
                        }
                    if ($datetime !== false)
                        {
                        if (isset($entry["Split"])) { $splitFound=true; $split[$datetime]=$entry["Split"]; }
                        else echo json_encode($config["Split"]);
                        }
                    }
                if ($splitFound)                 // zumindest ein Eintrag sonst bleibt false
                    {
                    ksort($split);                      //in aufsteigender Reihenfolge
                    }
                }
        return ($split);
        }

    /* archiveOps, Anzahl Vorwerte die in einem Intervall vorhanden sind ermitteln 
        * es wird das Array als Pointer übergeben. Auswertung der Periode auf Größenaordnung Tag, Woche, Monat, Jahr
        * werte können auch leer sein, wen zum Beispiel bei der vorigen Überprüfung alle Werte ausgeschieden wurden
        *
        */

    public function countperIntervalValues(&$werte,$debug=false)
        {        
        $result=array();
        $jetzt=time();
        /* endtime ist entweder die erste Minute des aktuellen Tages, oder die aktuelle Uhrzeit 
        $endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt)); // letzter Tag 24:00
        */
        $endtime=$jetzt;
        //if ($debug) echo "countperIntervalValues aufgerufen.\n";  
        $startday=$endtime-60*60*24*1; /* ein Tag */ 
        $startweek=$endtime-60*60*24*7; /* 7 Tage, Woche */                    
        $startmonth=$endtime-60*60*24*30; /* 30 Tage, Monat */                    
        $startyear=$endtime-60*60*24*360; /* 360 Tage, Jahr */
        
        $logCount=0; 
        if ( (is_array($werte)) && (count($werte)>0) )              // ein Array mit mindestens einem EIntrag
            {
            if ($debug>1) echo "countperIntervalValues aufgerufen, es handelt sich um ein Array.\n";
            $erster=array_key_first($werte);
            $letzter=array_key_last($werte);
            $termin1 = $werte[$erster]['TimeStamp'];
            $termin2 = $werte[$letzter]['TimeStamp'];
            if ($termin2>$termin1) { $anfang = $termin1; $ende = $termin2; }
            else                   { $anfang = $termin2; $ende = $termin1; }    
            //print_r($werte[$erster]); print_r($werte[$letzter]);
            $count=sizeof($werte); $scale="indefinite";
            if     ($werte[$letzter]['TimeStamp']>=$startday) $scale="days";
            elseif ($werte[$letzter]['TimeStamp']>=$startweek) $scale="weeks";
            elseif ($werte[$letzter]['TimeStamp']>=$startmonth) $scale="months";
            elseif ($werte[$letzter]['TimeStamp']>=$startyear) $scale="years";
            $duration = abs($werte[$erster]['TimeStamp'] - $werte[$letzter]['TimeStamp']);
            if ($count>1) $span = $duration/($count-1);               // ein Anfangswert muss abgezogen werden
            else $span = $duration;
            $result["count"]=$count;
            $result["span"]=$span;
            //echo "countperIntervalValues, Span is ".nf($span,"s")."\n";
            if ($letzter == ($count-1)) $result["index"]="index";
            else $result["index"]="time";
            /* Archive speichert mit 0 den neuesten Wert, also erster 0, letzter count-1,
            */
            if ($letzter>$erster)
                {
                if ($termin2>$termin1)  $result["order"]="oldfirst";
                else                    $result["order"]="newfirst";
                }
            else  
                {
                if ($termin1>$termin2) $result["order"]="oldfirst";
                else                   $result["order"]="newfirst";
                }
            $result["anfang"] = $anfang; 
            $result["ende"]=$ende;
            if ($debug) 
                {
                //echo "   countperIntervalValues, Ergebnis ist $count Werte logged per $scale. ";
                //echo "Erster  : ".date("H:i:s d.m.Y",$werte[$erster]['TimeStamp'])."  ";
                //echo "Letzter : ".date("H:i:s d.m.Y",$werte[$letzter]['TimeStamp'])."  ";
                echo "$count Werte im Scale $scale verfügbar. Erster Werte am ".date("d.m.Y H:i:s",$anfang)." bis ".date("d.m.Y H:i:s",$ende)." Span durchschnittlich ".nf($span,"s")."\n";
                if ($debug>1) // egal ob aggregated oder logged Values, aber für Debug wichtig
                    {
                    if (isset($werte[$erster]['Value'])) echo "     Info, Timestamp Value mit Key: $erster : ".date("d.m.Y H:i:s",$termin1)." Wert ".$werte[$erster]['Value']." und $letzter : ".date("d.m.Y H:i:s",$termin2)." Wert ".$werte[$letzter]['Value']."\n";
                    else echo "     Info, Timestamp Value mit Key: $erster : ".date("d.m.Y H:i:s",$termin1)." Wert ".$werte[$erster]['Avg']." und $letzter : ".date("d.m.Y H:i:s",$termin2)." Wert ".$werte[$letzter]['Avg']."\n";
                    }
                //echo "\n";
                }
            return($result);
            }
        else return (false);
        }


    /* Werte vergleichen zwischen Array 1 und 2
     * wenn Timestamp eines Wertes in werte bereits in timeStampknown vorhanden ist nicht als neu identifizieren
     * array wert ist Input und wird mit array timeStampknown verglichen
     * array wert kann unterschiedlichen Namen für den timeStamp haben, dritter Parameter
     */

    function filterNewData($werte,$timeStampknown,$timeStamp="TimeStamp",$debug=false)
        {
        $inputAdd=array();
        $inputChg=array();
        $countAdd=0; $countChg=0;
        if (is_array($timeStamp))
            {
            $value=$timeStamp["Value"];
            $timeStamp=$timeStamp["TimeStamp"];
            }
        else $value="Value";
        if ($debug)
            {
            echo "filterNewData, vergleiche ".count($werte)." neue Daten mit ".count($timeStampknown)." vorhandenen Daten.\n";
            echo "Anzahl Einträge : ".count($werte)." First Key ".array_key_first($werte)." => ".date("d.m.Y H:i",array_key_first($werte))." Last Key ".array_key_last($werte)." => ".date("d.m.Y H:i",array_key_last($werte))."\n"; 
            $max=10; $count=0;
            }
        $count=0;
        /* $input=array(); $input["Add"]=array(); $input["Chg"]=array();
        return($input); */

        foreach ($werte as $wert) 
            {
            //if (($debug) && ($count++==0)) print_R($wert);
            if ((isset($wert[$value])) && (isset($wert[$timeStamp])))          // da kommt noch mehr
                {
                //echo ".";
                //$valueFloat=(float)$wert[$value];                 // umrechnen ?      
                $valueFloat=$wert[$value];
                if (isset($timeStampknown[$wert[$timeStamp]])) 
                    {
                    if ($valueFloat != $timeStampknown[$wert[$timeStamp]]) 
                        {
                        if ( ($debug) && ($countChg<10) )
                            {
                            echo "Wert mit Timestamp ".$wert[$timeStamp]." (".date("d.m.Y H:i",$wert[$timeStamp]).") hat einen Eintrag unterschiedlich zum Archive : ";
                            echo $wert[$value]." != ".$timeStampknown[$wert[$timeStamp]]."\n";
                            }
                        $inputChg[$countChg]["TimeStamp"] = $wert[$timeStamp];
                        $inputChg[$countChg]["Value"] = $valueFloat;
                        $countChg++;
                        }
                    }
                else
                    {
                    if ($valueFloat>0)
                        {
                        if ( ($debug) && ($countAdd<10) )
                            {
                            echo "Wert mit Timestamp ".$wert[$timeStamp]." (".date("d.m.Y H:i",$wert[$timeStamp]).") hat einen neuen Eintrag : ";
                            echo $wert[$value]."   ".(float)$wert[$value]."\n";
                            }
                        $inputAdd[$countAdd]["TimeStamp"] = $wert[$timeStamp];
                        $inputAdd[$countAdd]["Value"] = $valueFloat;
                        $countAdd++;
                        }
                    } 
                }
            else 
                {
                //echo "Warning, wrong Format [$value,$timeStamp] :".json_encode($wert); break;                 // das ist eigentlich normal wenn er die nicht findet
                }
            //if ($count++>$max) break;
            }
        $input=array();
        echo "Es wurde $countAdd neue Werte und $countChg geänderte Werte gefunden.\n";
        $input["Add"]=$inputAdd;
        $input["Chg"]=$inputChg;
        return($input);
        }

    /* ohne config funktionieren so komplexe Funktionen wie addValuesfromCsv nicht
     * in einen erwartbaren Ausgangszustand bringen
     *      Index
     *      Key
     *      Format
     *      result
     */
    function setConfigForAddValues($configInput)
        {
        //parse config file, done twice, also in  readFileCsv
        $config=array();
        configfileParser($configInput, $config, ["INDEX","Index","index" ],"Index" ,[]); 
        configfileParser($configInput, $config, ["KEY","Key","key" ],"Key" ,null); 
        configfileParser($configInput, $config, ["FORMAT","Format","format" ],"Format" ,null); 
        configfileParser($configInput, $config, ["RESULT","Result","result" ],"Result" ,"All");
        configfileParser($configInput, $config, ["TARGET","Target","target" ],"Target" ,null);          // Target durchlassen
        return ($config);
        }

    /* addValuesfromCsv
     *
     * add Values to archive oid from csv file
     * used for 15min and daily power values imported manually from Smart Meter Portals
     * Input is config, following format is expected here and at readFileCsv called in here
     *
     * ähnliche Funktion in AMIS class writeSmartMeterCsvInfoToHtml
     *
     * nur wenn debug false ist werden Werte in das Array geschrieben
     * verwendet 
     *      getArchivedValues
     *      readFileCsv  
     */

    function addValuesfromCsv($file,$oid,$configInput,$debug=true)
        {
        $input=false;
        $config=$this->setConfigForAddValues($configInput);           //parse config file, done twice, also in  readFileCsv

        $debug1=false;          // debug readFileCsv, false spart output
        //$debug1=$debug;
        if ($debug) 
            {
            echo "addValuesfromCsv, target für csv Werte ist OID : $oid. Werte aus dem Archiv auslesen. Config is ".json_encode($config)."\n";
            echo "Aggregation Status für diesen Wert : ".(AC_GetAggregationType($this->archiveID, $oid)?"Zähler":"Standard")."\n";            // bedingung ? erfüllt : nicht erfüllt; 
            if ($debug1>1) echo "Memorysize : ".getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb\n";                        
            echo "Archivierte Werte bearbeiten:\n";
            }

        $werte = $this->getArchivedValues($oid,$config,$debug1);            // 2 für debug
        if ($debug) echo "Insgesamt ".count($werte)." Werte aus dem Archive ausgelesen.\n";
        $target=array();
        foreach ($werte as $wert) $target[$wert["TimeStamp"]]=$wert["Value"];
        //$this->showValues($werte,$config);

        $fileOps = new fileOps($file);             // Filenamen gleich mit übergeben, Datei bleibt in der Instanz hinterlegt
        //$index=[];                            // erste Zeile als Index übernehmen
        //$index=["Date","Time","Value"];         // Date und Time werden gemerged
        //if (isset($config$index=["DateTime","Value","Estimate","Dummy"];                                             // Spalten die nicht übernommen werden sollen sind mit Indexwert false
        //$index=["DateTime","Value","Estimate","Dummy"];
        $index=$config["Index"];
        $result=array();                                                        // Ergebnis
        if ($debug) echo "readFileCsv mit Config ".json_encode($config)." und Index ".json_encode($index)." aufgerufen.\n";       // readFileCsv(&$result, $key="", $index=array(), $filter=array(), $debug=false)
        $status = $fileOps->readFileCsv($result,$config,$index,[],$debug1);                  // Archive als Input, status liefert zu wenig Informationen
        if ($debug) echo "Insgesamt ".count($result)." Werte aus der Datei $file ausgelesen.\n";
        if ($debug1>1) echo "Memorysize : ".getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb\n";                        

        if (is_array($config["Key"]))        // key extrahieren
            {
            if (isset($config["Key"]["Merge"])) $key=$config["Key"]["Merge"];
            else echo "Config Parameter \"Merge\" id mising. Check Config \"Key\".\n";
            }
        else $key=$config["Key"]; 
        if (isset($config["Target"]["Column"])) 
            {
            if ($debug) echo "Target found : ".$config["Target"]["Column"]."\n";
            $value=$config["Target"]["Column"];
            }
        else $value="Value";
        $keyconfig=array();;
        $keyconfig["TimeStamp"]=$key;
        $keyconfig["Value"]=$value;

        if (isset($config["Result"]["Column"]))
            {
            $ergebnisCol=$config["Result"]["Column"];
            $ergebnisData=array();    
            $line=0;
            if ($debug) echo "Konfiguration $key ".json_encode($ergebnisCol)."\n";
            foreach ($result as $entry) 
                {
                $time=$entry[$key];
                foreach ($ergebnisCol as $Col) 
                    {
                    if ( (isset($entry[$Col])) && ($entry[$Col]>0) )
                        {
                        if (isset($ergebnisData[$Col]["TimeFirst"])===false) $ergebnisData[$Col]["TimeFirst"]=$time;
                        $ergebnisData[$Col]["TimeLast"]=$time;
                        }
                    }
                if ( ($debug) && ($line++<10) )
                    {
                    echo $line."    ";
                    echo $time;
                    foreach ($ergebnisCol as $Col) echo "    ".$entry[$Col]; 
                    echo "\n";
                    }
                }
            //print_r($ergebnisData);
            if ($debug) foreach ($ergebnisData as $Col => $entry) echo $Col."    ".date("d.m.Y H:i",$entry["TimeFirst"])." bis ".date("d.m.Y H:i",$entry["TimeLast"])." \n";
            }

        $input = $this->filterNewData($result,$target,$keyconfig,$debug);
        
        //$this->showValues($write,$config);

        $countAdd = count($input["Add"]);
        if ($debug) echo "Add Logged Values: ".$countAdd."\n";
        $count=0;
        foreach ($input["Add"] as $entry)          // Anzahl neuer Werte schreiben vorerst limitieren
            {
            if ($count > ($countAdd-3001)) $writeInput[]=$entry;
            $count++;
            //if ($count++>999) break;
            }
        if ( ($debug==false) && ($count) ) 
            {
            //print_R($writeInput);
            //foreach ($writeInput as $row => $entry) echo $row."  ".date("d.m.Y H:i:s",$entry["TimeStamp"])."  ".nf($entry["Value"],"kWh")."\n";
            $status=false;
            $status=AC_AddLoggedValues ($this->archiveID, $oid, $writeInput);
            echo "Erfolgreich : $status . ".count($writeInput)." Werte hinzugefügt.\n";
            echo " Zeitraum von ".date("d.m.Y",$writeInput[array_key_first($writeInput)]["TimeStamp"])." bis ".date("d.m.Y",$writeInput[array_key_last($writeInput)]["TimeStamp"])." \n";
            AC_ReAggregateVariable($this->archiveID, $oid);
            }
        elseif ($debug) echo "Debug Mode oder count $count, no add to archive.\n";
        return ($input);            // as result, also useful in Debug Mode
        }



    }   // ende class archiveOps


/* Statistikfunktionen in einer Parent class zusammen gefasst
 *
 * wird von folgenden child classes verwendet
 *      meansCalc
 *      eventLogEvaluate
 *      meansRollEvaluate
 *      maxminCalc
 *
 * auch wenn von archiveOps nicht verwendet wird zumindes setConfiguration zum Abgleich der Config verwendet
 *
 *
 *  setConfiguration            Konfiguration eindeutig bearbeiten und formattieren
 *  wert                        aus Value,TimeStamp den Wert extrahieren
 *
 *
 *
 */

class statistics
    {

    protected $config;

    /* convert config cleanupData
     */
    public function configCleanUpData($configInput)
        {
        /* Wertebereich festlegen */
        $config = array();

        configfileParser($configInput, $config, ["range","RANGE","Range"],"Range",null);
        configfileParser($configInput, $config, ["maxLogsperInterval"],"maxLogsperInterval",10000);         // wird vor Aufruf cleanup angepasst auf Länge array
        configfileParser($configInput, $config, ["deleteSourceOnError"],"deleteSourceOnError",true);
        configfileParser($configInput, $config, ["provideReason"],"provideReason",false);
        return ($config);
        }

    /* convert config processOnData
     */
    public function configProcessOnData($configInput)
        {
        /* Wertebereich festlegen */
        $config = array();

        configfileParser($configInput, $config, ["range","RANGE","Range"],"Range",null);
        configfileParser($configInput, $config, ["delta","DELTA","Delta"],"Delta",null);         
        return ($config);
        }        

    /* convert config meansRoll
     */
    public function configMeansRoll($configInput)
        {
        /* Wertebereich festlegen */
        $config = array();

        configfileParser($configInput, $config, ["direction","DIRECTION","Direction"],"Direction","Backwards");                // backward, forward oder backintime, forthintime
        configfileParser($configInput, $config, ["timestamppos","TIMESTAMPPOS","TimeStampPos"],"TimeStampPos","Mid");                // backward, forward oder backintime, forthintime
        configfileParser($configInput, $config, ["count","Count","COUNT"],"count",false);         
        configfileParser($configInput, $config, ["name","Name","NAME"],"name","unknown");         
        return ($config);
        }        

    /* config ausgeben, Umsetzung auf erwartete Konfigurationen
     */
    public function getConfigMeansRoll()
        {
        $meansRollConfig=array(); 
        if ($this->config["meansRoll"])
            {
            $meansRollConfig=$this->config["meansRoll"];
            $meansRollConfig["CalcDirection"]=$this->config["meansRoll"]["Direction"];           // für dei Kompatibilität, neuester Wert hat einen Mittelwert
            }
        else
            {
            $meansRollConfig["count"]=false;
            $meansRollConfig["name"]="unknown";
            $meansRollConfig["TimeStampPos"]="Mid";
            $meansRollConfig["CalcDirection"]="Backward";           // neuester Wert hat einen Mittelwert
            }
        return($meansRollConfig);
        }

    /* einheitliche Konfiguration mit Variablen für die Nutzung in den Statistikfunktionen
        *       EventLog            true
        *       DataType            Archive, Aggregated, Logged
        *           Aggregated      true if dataType is Aggregated, werte false,0,1,2,
        *       manAggregate        Aggregate, 
        *                          Format
        *       KIfeature           none, besondere Auswertungen machen
        *       Split               Split, Änderungen der Skalierung zu einem bestimmten zeitpunkt
        *       OIdtoStore          eine ander OID verwenden als die echte OID
        *
        *       processondata       eigene Unterkonfigurationsgruppe
        *       cleanupdata         eigene Unterkonfigurationsgruppe
        * 
        *       returnResult
        *
        *       meansRoll            ein Wert, diese Anzahl werden als vorwerte für den rollierenden Mittelwert verwendet
        *
        *       suppresszero
        *       maxDistance
        *
        *       StartTime 
        *       EndTime
        *       LogChange
        *
        */

    public function setConfiguration($logs,$debug=false)
        {

        /* Wertebereich festlegen */
        $config = array();

        if (is_array($logs)) { $logInput = $logs; $logs=0; }
        else $logInput=array();

        // parse configuration, logInput ist der Input und config der angepasste Output
        configfileParser($logInput, $config, ["EVENTLOG","EventLog","eventLog" ],"EventLog" ,true); 
        configfileParser($logInput, $config, ["DataType","DATATYPE","datatype" ],"DataType" ,"Archive");

        if ($config["DataType"] == "Logged") $config["Aggregated"]=false;
        elseif ($config["DataType"] == "Counter") $config["Aggregated"]=false;
        else 
            {
            configfileParser($logInput, $config, ["Aggregated","AGGREGATED","aggregated" ],"Aggregated" ,false); 
            configfileParser($logInput, $config, ["AggregatedValue","AGGREGATEDVALUE","aggregatedvalue","aggregatedValue" ],"AggregatedValue" ,"Avg"); 
            }

        configFileParser($logInput, $config1, ["manAggregate","MANAGGREGATE","managgregate"],"manAggregate",false);         // false, anderer Wert default daily , array [aggregate=daily,format=>standard]
        if (is_array($config1["manAggregate"]))
            { 
            configFileParser($config1["manAggregate"], $config["manAggregate"], ["Aggregate","aggregate","AGGREGATE"],"Aggregate","daily");
            configFileParser($config1["manAggregate"], $config["manAggregate"], ["Format","format","FORMAT"],"Format","standard");
            }
        else $config["manAggregate"] = $config1["manAggregate"];

        configfileParser($logInput, $config, ["KIFeature","KIFEATURE","kifeature","KIfeature" ],"KIfeature" ,"none");
        configfileParser($logInput, $config, ["Split","SPLIT","split"],"Split" ,null);
        configfileParser($logInput, $config, ["OIdtoStore","OIDTOSTORE","oidtostore"],"OIdtoStore",null);

        configfileParser($logInput, $config, ["processondata","PROCESSONDATA","ProcessOnData","processOnData"],"processOnData",null);           // sub Config
        configfileParser($logInput, $config, ["cleanupdata","CLEANUPDATA","CleanupData","CleanUpData","cleanupData"],"cleanupData",null);                         // sub Config
        configFileParser($logInput, $config, ["meansRoll","MEANSROLL","meansroll","MeansRoll"],"meansRoll",false);                                  // sub Config

        if (isset($config["cleanupData"])) $config["cleanupData"]=$this->configCleanUpData($config["cleanupData"]);
        if (isset($config["processOnData"])) $config["processOnData"]=$this->configProcessOnData($config["processOnData"]);
        if (isset($config["meansRoll"])) $config["meansRoll"]=$this->configMeansRoll($config["meansRoll"]);

        configFileParser($logInput, $config, ["returnResult","RETURNRESULT","returnresult","ReturnResult"],"returnResult",false);   // Description

        configfileParser($logInput, $config, ["SuppressZero","SUPPRESSZERO","suppresszero"],"SuppressZero" ,true);
        configFileParser($logInput, $config, ["maxDistance","MAXDISTANCE","maxdistance"],"maxDistance",false);         // default no check for gaps any longer, just warning
        
        configfileParser($logInput, $config2, ["doCheck","DOCHECK","docheck","DoCheck"],"DoCheck" ,false);
        if (is_array($config2["DoCheck"]))
            { 
            configFileParser($config2["DoCheck"], $config["DoCheck"], ["SuppressZero","SUPPRESSZERO","suppresszero"],"SuppressZero" ,true);
            configFileParser($config2["DoCheck"], $config["DoCheck"], ["maxDistance","MAXDISTANCE","maxdistance"],"maxDistance",false);
            configFileParser($config2["DoCheck"], $config["DoCheck"], ["isNumeric","ISNUMERIC","isnumeric"],"isNumeric",true);                      //default
            }
        else 
            {
            $config["DoCheck"]["SuppressZero"] = $config["SuppressZero"];               // kopieren
            $config["DoCheck"]["maxDistance"] = $config["maxDistance"];
            $config["DoCheck"]["isNumeric"] = true;
            }

        configFileParser($logInput, $config, ["interpolate","INTERPOLATE","Interpolate"],"Interpolate",false);                                          // Interpolate false, daily, 
        configFileParser($logInput, $config, ["integrate","INTEGRATE","Integrate"],"Integrate",false);                                          // Integrate false, check and correct integrate/counter values 
        configFileParser($logInput, $config, ["deleteSourceOnError","DELETESOURCEONERROR","deletesourceonerror"],"deleteSourceOnError",true);              // true in werte unset machen wenn fehler

        configfileParser($logInput, $config, ["STARTTIME","StartTime","startTime","starttime" ],"StartTime" ,0);
        configfileParser($logInput, $config, ["ENDTIME","EndTime","endTime","endtime" ],"EndTime" ,0);
        configfileParser($logInput, $config, ["LOGCHANGE","LogChange","logChange","logchange" ],"LogChange" ,["pos"=>5,"neg"=>5]);      // in Prozent auf den Vorwert

        configFileParser($logInput, $config, ["debug","DEBUG","Debug"],"Debug",[]);   // Selective Debugging
        configFileParser($logInput, $config, ["warning","WARNING","Warning"],"Warning",true);   // echo Warning messages

        configFileParser($logInput, $config, ["showtable","SHOWTABLE","Showtable","ShowTable"],"ShowTable",null);   // Darstellungsoptionen für showValues

        if ($logs>1) 
            {
            $logs=round($logs/2)*2;              // zumindest die geforderte Anzahl an Logwerten anzeigen
            $maxLogsperInterval = $logs+$logs/2;
            if ($debug) echo "    analyseValues für $oid (".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).") aufgerufen, $maxLogsperInterval Werte werden verarbeitet\n";
            }
        else 
            {
            $logs=10;
            $maxLogsperInterval=1;                  // ein Wert reicht aus, max wäre 10
            if (($debug) && (is_array($logs) === false) ) echo "    analyseValues für $oid (".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).") aufgerufen, alle vorhandenen Werte werden verarbeitet\n";
            }

        //print_r($config);
        $config["Logs"] = $logs;
        $config["maxLogsperInterval"] = $maxLogsperInterval;
        
        $this->config = $config;
        return($config);
        }

    /* aus einem Value/Timestamp Paar den Value ausgeben
     */
    public function wert($input)
        {
        $value=false;
        if (isset($input["Avg"]))   $value = $input["Avg"];
        if (isset($input["Value"])) $value = $input["Value"]; 
        return($value);
        }

    /* aus einem Value/Timestamp Paar den Value ausgeben
     */
    public function date($input)
        {
        $value=false;
        if (isset($input["TimeStamp"]))   $value = $input["TimeStamp"];
        return(date("d.m.Y H:i:s",$value));
        }

    /* in einem Value/Timestamp Paar den Value updaten
     */
    public function updateWert(&$input,$value)
        {
        if (isset($input["Avg"]))   $input["Avg"]=$value;
        if (isset($input["Value"])) $input["Value"]=$value; 
        return($input);
        }

    /* in einem Value/Timestamp Paar den Value erhöhen
     */
    public function addWert(&$input,$value)
        {
        if ( (isset($input["Avg"]))   && (is_numeric($input["Avg"])) )   $input["Avg"]   +=$value;
        if ( (isset($input["Value"])) && (is_numeric($input["Value"])) ) $input["Value"] +=$value;             
        return($input);
        }

    }       // ende class statistics


   /* Berechnung von Mittelwerten, Summen und Max/Min, die Ausgaben und Berechnungsgrundlagen erfolgen in einen gemeinsamen Speicher
    * das Ergebnis ist ein externes array, es wird nur der Pointer übergeben
    * verarbeitet Werte mit Zeitstempel und Value, nicht aggregiert mit Avg oder Skalare ohne Zeitstempel
    *
    * beim Construct wird der Name unter dem die Berechnung gespeichert werden soll und die Anzahl der Werte die berücksichtigt werden soll gespeichert
    * wenn ich 11 Werte angebe wird auf 10 zurückgerundet
    * auch wenn in result bereits Werte gespeichert sind werden die Werte extra mit addValue übergeben
    * Ergebnis steht zB in result[full] wenn name full ist. Es können mehrere Mittelwert gleichzeitig berechnet werden
    *
    * für die Berechnung werden folgende Werte angelegt
    *      Count, Count1, Count2, CountFull
    *      Sum, Sum1, Sum2 
    * das bedeutet wir haben entweder zwei oder einen Mittelwert
    *
    * Sonderfall ist Full mit logs=false
    *
    *  __construct
    *  addValue         einen Wert übergeben
    *  checkMinMax
    *  calculate
    *  extrapolate
    *
    * extends statistic
    *       nutzt setConfiguration und addWert, updateWert, date, wert bzw. configCleanUpData, configProcessOnData
    *
    * Abgebrochen wird 
    *       wenn das übergebene array, also der Pointer, kein array ist
    *
    */

class meansCalc extends statistics
    {
    protected $extendedDebug;           // some extra output
    protected $name;
    protected $result;
    protected $sum,$sum1,$sum2,$sumTime1,$sumTime2,$count1,$count2;
    protected $configDelta;
    protected $previousValue, $previousTime;                                        // Mittelwerten aus der Differenz erstellen

    function __construct(&$result,$name="Full",$logs=false)
        {
        if (is_array($result)===false) return (false);
        $this->config=false;
        $this->configDelta = false;                       // false die Werte nehmen, true die Differenz zum Vorwert ausrechnen
        $this->extendedDebug = false;
        $this->previousValue=false; $this->previousTime=false;

        $this->sum=0; $this->sum1=0; $this->sum2=0;
        $this->sumTime1=0; $this->sumTime2=0;
        $this->count=0; $this->count1=0; $this->count2=0;
        
        $this->result=&$result;
        $this->result[$name]=array();
        $this->result[$name]["Sum"]=0;
        $this->result[$name]["Sum1"]=0;
        $this->result[$name]["Sum2"]=0;
        $this->result[$name]["Count1"]=0;
        $this->result[$name]["Count2"]=0;

        $this->result[$name]["Count"]=0;
        if ($logs) $logs=round($logs/2)*2;                             // zumindest die geforderte Anzahl an Logwerten anzeigen, Wert soll durch 2 dividierbar sein
        $this->result[$name]["CountFull"]=$logs;

        $this->result[$name]["First"]=false;
        $this->result[$name]["Last"]=false;

        $this->name=$name;
        return (true);
        }

    /* meanscalc::updateConfig nachträgliche Konfiguration
     * wenn this->config nicht mehr false ist dann etwas unternehmen
     */
    function updateConfig($configInput=false, $extraDebug=false)
        {
        if ($extraDebug) $this->extendedDebug=true;
        $this->config=$this->setConfiguration($configInput);
        if ( ($this->config["Integrate"]) )
            {
            if ($this->extendedDebug) echo "meansCalc::updateConfig Integrate\n";
            $this->configDelta=true;
            }
        //print_r($this->config);
        }

    /* Übergabe wert[value] und wert[TimeStamp] oder wert
     * alle Werte die hier übergeben werden, werden in sum addiert
     * und an checkMinMax übergeben
     *
     * sum += value; count++; 
     *
     * Spezialfunktion mit logs bzw countFull
     * zählt nur bis CountFull/2 in Sum1 dann in Sum2
     *
     * Zwei Funktionen mit Value/TimeStamp oder ohne
     *
     */
    function addValue($wertInput)
        {
        if (is_array($this->result)===false) return (false);
        if (is_numeric($wertInput["Value"])===false) return (false);                                // nicht numerische Werte lassen sich nicht addieren
        if (isset($wertInput["Value"]))         // Funktion mit Value/TimeStamp
            {
            if ($this->configDelta)             // Delta aus den Vorwerten berechnen
                {
                if ($this->previousValue===false) 
                    {
                    //$value = $wertInput["Value"];                 // Alternative
                    $value=0;
                    }
                else $value = $wertInput["Value"]-$this->previousValue;
                $this->previousValue=$wertInput["Value"]; $this->previousTime=$wertInput["TimeStamp"];
                if ($this->extendedDebug) echo date("d.m.Y H:i:s",$wertInput["TimeStamp"])." ".nf($wertInput["Value"],"mm",12).nf($value,"mm",12)."\n"; 
                }
            else $value = $wertInput["Value"];  // kein Delta berechnen
            $wertInput["Value"]=$value;

            $this->sum += $value;
            $this->count++;
            if ($this->result[$this->name]["CountFull"])            // wenn 0/false alle Werte mitnehmen, keine Teilsummen bilden
                {
                if ($this->result[$this->name]["Count"]<$this->result[$this->name]["CountFull"])
                    {
                    if ($this->result[$this->name]["Count"]<($this->result[$this->name]["CountFull"]/2))
                        {
                        $this->sum1 += $value;
                        $this->sumTime1 += $wertInput["TimeStamp"];
                        $this->count1++;
                        }    
                    else 
                        {
                        $this->sum2 += $value;
                        $this->sumTime2 += $wertInput["TimeStamp"];
                        $this->count2++;                                
                        } 
                    $this->checkMinMax($wertInput);   
                    }
                }
            else            // wenn 0/false alle Werte mitnehmen, keine Teilsummen bilden
                {   
                $this->checkMinMax($wertInput);                 // Min/Max über den ganzen Bereich, übergibt Wert und Zeitstempel
                }

            }
        else                                    // alte Art der Berechnung, ohne Timestamp
            {
            $this->result[$this->name]["Sum"] += $wertInput;
            if ($this->result[$this->name]["CountFull"])            // wenn 0/false alle Werte mitnehmen, keine Teilsummen bilden
                {
                if ($this->result[$this->name]["Count"]<$this->result[$this->name]["CountFull"])
                    {
                    if ($this->result[$this->name]["Count"]<($this->result[$this->name]["CountFull"]/2))
                        {
                        $this->result[$this->name]["Sum1"] += $wertInput;
                        $this->result[$this->name]["Count1"]++;
                        }    
                    else 
                        {
                        $this->result[$this->name]["Sum2"] += $wertInput;
                        $this->result[$this->name]["Count2"]++;
                        }    
                    $this->checkMinMax($wertInput);             // Min/Max nur über den Bereich bis CountFull (logs)

                    }
                }
            else    
                {       
                $this->checkMinMax($wertInput);    // Min/Max über den ganzen Bereich
                }
            }
        $this->result[$this->name]["Count"]++; 
        $this->checkFirstLast($wertInput);               
        return (true);
        }

    /* check Min Max 
     * kommt mehrmals in der Abfrage vor, deshalb eine private function
     */

    private function checkMinMax($wertInput)
        {
        if (isset($wertInput["Value"]))
            {
            if (isset($this->result[$this->name]['Max']))  
                {
                if ($wertInput["Value"]>$this->result[$this->name]['Max'])  
                    {
                    $this->result[$this->name]['Max'] = $wertInput["Value"];
                    $this->result[$this->name]['MaxEntry']["Value"]     = $wertInput["Value"];
                    $this->result[$this->name]['MaxEntry']["TimeStamp"] = $wertInput["TimeStamp"];
                    }
                }
            else 
                {
                $this->result[$this->name]['Max'] = $wertInput["Value"];
                $this->result[$this->name]['MaxEntry']["Value"]     = $wertInput["Value"];
                $this->result[$this->name]['MaxEntry']["TimeStamp"] = $wertInput["TimeStamp"];
                }
            if (isset($this->result[$this->name]['Min']))  
                {
                if ($wertInput["Value"]<$this->result[$this->name]['Min'])  
                    {
                    $this->result[$this->name]['Min'] = $wertInput["Value"];
                    $this->result[$this->name]['MinEntry']["Value"]     = $wertInput["Value"];
                    $this->result[$this->name]['MinEntry']["TimeStamp"] = $wertInput["TimeStamp"];                    
                    }
                }
            else 
                {
                $this->result[$this->name]['Min'] = $wertInput["Value"];
                $this->result[$this->name]['MinEntry']["Value"]     = $wertInput["Value"];
                $this->result[$this->name]['MinEntry']["TimeStamp"] = $wertInput["TimeStamp"];                 
                }
            }
        elseif (is_array($wertInput))
            {
            echo "wrong";
            }
        else
            {
            if (isset($this->result[$this->name]['Max']))  
                {
                if ($wertInput>$this->result[$this->name]['Max'])  
                    {
                    $this->result[$this->name]['Max'] = $wertInput;
                    }
                }
            else 
                {
                $this->result[$this->name]['Max'] = $wertInput;
                }
            if (isset($this->result[$this->name]['Min']))  
                {
                if ($wertInput["Value"]<$this->result[$this->name]['Min'])  
                    {
                    $this->result[$this->name]['Min'] = $wertInput;
                    }
                }
            else 
                {
                $this->result[$this->name]['Min'] = $wertInput;
                }
            }
        }

    /* check First Last 
     * kommt mehrmals in der Abfrage vor, deshalb eine private function
     */

    private function checkFirstLast($wertInput)
        {
        if (isset($wertInput["TimeStamp"]))
            {
            if ($wertInput["TimeStamp"] == 0) echo "+";
            if ( ($this->result[$this->name]['First'] === false) || ($this->result[$this->name]['First']>$wertInput["TimeStamp"]) ) $this->result[$this->name]['First'] = $wertInput["TimeStamp"];
            if ( ($this->result[$this->name]['Last'] === false)  || ($this->result[$this->name]['Last'] <$wertInput["TimeStamp"]) ) $this->result[$this->name]['Last']  = $wertInput["TimeStamp"];
            }
        else echo ".";
        }

    /* für Mittelwert gut geeignet, wenn alles summiert ist am Ende dividieren */

    function calculate()
        {
        if (is_array($this->result)===false) return (false);                
        if ($this->sum>0)
            {
            $this->result[$this->name]["Sum"]=$this->sum;                    
            //echo "Berechne Mittelwert mit TimeStamp \"".$this->name."\":\n";
            if ($this->result[$this->name]["CountFull"])
                {
                if ($this->count1==0) return (false);
                else
                    {
                    $this->result["Result"][$this->name]["MeansVar"][1]["Value"]=$this->sum1/$this->count1;
                    $this->result["Result"][$this->name]["MeansVar"][1]["TimeStamp"]=$this->sumTime1/$this->count1;
                    if ($this->count2>0)
                        {
                        $this->result["Result"][$this->name]["MeansVar"][2]["Value"]=$this->sum2/$this->count2;
                        $this->result["Result"][$this->name]["MeansVar"][2]["TimeStamp"]=$this->sumTime2/$this->count2;
                        $this->result["Result"][$this->name]["Trend"]=(($this->sum1/$this->count1)/($this->sum2/$this->count2)-1)*100;                                            
                        }
                    }
                }
            elseif ($this->result[$this->name]["Count"])  
                {
                $this->result["Result"][$this->name]["Means"]=$this->sum/$this->result[$this->name]["Count"];
                $this->result["Result"][$this->name]["Count"]=$this->result[$this->name]["Count"];                                              // die Anzahl ist nicht bekannt
                }
            }
        else                    // alte Art der Berechnung
            {
            if ($this->result[$this->name]["CountFull"])
                {
                if ($this->result[$this->name]["Count1"]==0) return (false);
                else
                    {
                    $this->result["Result"][$this->name]["Means1"]=$this->result[$this->name]["Sum1"]/$this->result[$this->name]["Count1"];
                    if ($this->result[$this->name]["Count2"]>0)
                        {
                        $this->result["Result"][$this->name]["Means2"]=$this->result[$this->name]["Sum2"]/$this->result[$this->name]["Count2"];
                        $this->result["Result"][$this->name]["Trend"]=($this->result["Result"][$this->name]["Means1"]/$this->result["Result"][$this->name]["Means2"]-1)*100;                
                        }
                    }
                }
            elseif ($this->result[$this->name]["Count"])  
                {
                $this->result["Result"][$this->name]["Means"]=$this->result[$this->name]["Sum"]/$this->result[$this->name]["Count"];
                $this->result["Result"][$this->name]["Count"]=$this->result[$this->name]["Count"];                                              // die Anzahl ist nicht bekannt
                }
            }

        if ( (isset($this->result[$this->name]['Max'])) && (isset($this->result[$this->name]['Min'])) )
            {
            $this->result["Result"][$this->name]['Max'] = $this->result[$this->name]['Max'];
            $this->result["Result"][$this->name]['Min'] = $this->result[$this->name]['Min'];
            }

        if ( (isset($this->result[$this->name]['First'])) && (isset($this->result[$this->name]['Last'])) )
            {
            $this->result["Result"][$this->name]['First'] = $this->result[$this->name]['First'];
            $this->result["Result"][$this->name]['Last']  = $this->result[$this->name]['Last'];
            if ( (isset($this->result[$this->name]["Count"])) && ($this->result[$this->name]["Count"]>0) ) $span = ($this->result["Result"][$this->name]['Last']-$this->result["Result"][$this->name]['First'])/($this->result[$this->name]["Count"]);
            //echo "caculate aufgerufen. ".date("d.m.Y H:i:s",$this->result["Result"][$this->name]['First'])." bis ".date("d.m.Y H:i:s",$this->result["Result"][$this->name]['Last'])."\n";
            }

        return (true);
        }

    /* Mittelwert hat jetzt einen Zeitbereich 
        *
        */

    function extrapolate($youngestTime)
        {
        if ( ($this->count1==0) || ($this->count2==0) ) return (false);
        $duration =  $this->sumTime1/$this->count1-$this->sumTime2/$this->count2;
        $change   =  $this->sum1/$this->count1-$this->sum2/$this->count2;
        $duration2 = $youngestTime-$this->sumTime1/$this->count1;
        $change2 = $change/$duration*$duration2+($this->sum1/$this->count1);
        //echo "Extrapolate ".nf($duration/60/60/24,"Tage")." ".nf($duration2/60/60/24,"Tage")." $change $change2\n";
        $result = $this->result["Result"][$this->name]["MeansVar"];
        unset($this->result["Result"][$this->name]["MeansVar"]);
        $result[0]["Value"]=$change2;
        $result[0]["TimeStamp"]=$youngestTime;
        ksort($result);
        $this->result["Result"][$this->name]["MeansVar"]=$result;
        foreach ($this->result["Result"][$this->name]["MeansVar"] as $index => $entry)
            {
            //echo "$index ".date("d.m.Y H:i:s",$entry["TimeStamp"])." ".$entry["Value"]."\n";
            }
        return (true);
        }

    /* Ausgeben der Berechnungen in lesbarer Form 
        */            

    function print()
        {
        echo "meansCalc::print, result for ".$this->name.":\n";
        if (isset($this->result[$this->name]['MaxEntry']["Value"]))
            {
            echo "     Max Value  ".$this->result[$this->name]['MaxEntry']["Value"]." on ".date("d.m.Y H:i:s",$this->result[$this->name]['MaxEntry']["TimeStamp"])."\n";
            echo "     Min Value  ".$this->result[$this->name]['MinEntry']["Value"]." on ".date("d.m.Y H:i:s",$this->result[$this->name]['MinEntry']["TimeStamp"])."\n";
            if ($this->sum>0)
                {
                echo "Berechne Mittelwert mit TimeStamp \"".$this->name."\":\n";            
                echo "Summe $this->sum for $this->count Werte.  Mittelwert ".($this->sum/$this->count)."\n";
                }
            if ($this->result[$this->name]["CountFull"] != 0)      // 0 oder false
                {
                echo "Daten Intervall\":\n";            
                if ($this->count1>0) echo "1: Summe $this->sum1 for $this->count1 Werte.  Mittelwert ".($this->sum1/$this->count1)."\n";
                if ($this->count2>0) echo "2: Summe $this->sum2 for $this->count2 Werte.  Mittelwert ".($this->sum2/$this->count2)."\n";
                }
            }
        }

    }


   /*  Auswertung von Archive Einträgen, Ausgaben in einen gemeinsamen Speicher
    *  das Ergebnis ist ein externes array, es wird nur der pointer übergeben
    *  es gilt besondere Ereignisse herauszufiltern.
    *  Übergabe config als Parameter:
    *      EventLog                aktiv
    *      LogChange.neg/pos       in prozent vom Wert
    *      LogChange.time          Zeit die betrachtet werden soll
    *      InputValues              das ganze Array mit den Daten, dann wird der aktuelle Index übergeben
    *
    * Ergebnis ist in result, die Inputdaten werden entweder als Einzelwert oder index übergeben
    *
    *
    *  __construct
    *  addValue
    *  addValueAsIndex
    *
    *
    *
    */

class eventLogEvaluate extends statistics
    {
    
    protected $name;
    protected $previousOne,$countPos,$countNeg,$changeDir;
    protected $previousTime;                                            // um die Richtung feststellen zu können
    protected $previousMax,$previousMaxTime,$previousMin,$previousMinTime;
    protected $confEventLog, $confLogChangeNeg, $confLogChangePos, $confLogChangeTime;
    protected $doString;                                                 // Auswertungen für Strings, statt Zahlen
    protected $inputValues;
    protected $result;
    protected $first=false,$debug;
    protected $showmax=false;                           // ein paar Zeilen zum Debug anzeigen

    /* die wichtigsten Variablen initialisieren, Ergebnis der Berechnungen wird in ein vom Auftraggeber vorbereitetes array gespeichert
     *
     * Ergebnis speichern in result[name]
     *     legt zusätzliche Untergruppen an
     *                      eventLog
     *                      countPosMax
     *                      countNegMax
     */

    function __construct(&$result,$name="All",$config=false,$debug=false)
        {
        if (is_array($result)===false) return (false);
        $this->debug = $debug;
        if ($config["DoCheck"]["isNumeric"]==false) $this->doString=true;

        $this->result=&$result;
        /* Config vorbereiten */
        if ( (isset($config["EventLog"])) && ($config["EventLog"]==true) )  $this->confEventLog=true;          
        else $this->confEventLog=false;
        if ( (isset($config["LogChange"]["neg"])) && ($config["LogChange"]["neg"]>0) ) $this->confLogChangeNeg=$config["LogChange"]["neg"];
        else $this->confLogChangeNeg=false;
        if ( (isset($config["LogChange"]["pos"])) && ($config["LogChange"]["pos"]>0) ) $this->confLogChangePos=$config["LogChange"]["pos"];
        else $this->confLogChangePos=false;            
        if ( (isset($config["LogChange"]["time"])) && ($config["LogChange"]["time"]>0) ) 
            {
            $this->confLogChangeTime=$config["LogChange"]["time"];
            //echo "Logchange Time ".nf($this->confLogChangeTime,"s")."\n";
            }
        else $this->confLogChangeTime=false;            
        if ($this->debug) 
            {
            //print_r($config);
            //echo "Config für eventLogEvaluate : ".$this->confEventLog." . ".$this->confLogChangeNeg." . ".$this->confLogChangePos." . ".$this->confLogChangeTime."\n";
            }

        if ( (isset($config["InputValues"])) && (is_array($config["InputValues"])) )
            {
            $this->inputValues = &$config["InputValues"];
            //echo "Array mit Werten übergeben, ".sizeof($this->inputValues)." Einträge, verwende addValueAsIndex:\n";
            }

        /* init der für die Analyse benötigten Variablen, es müssen nicht alle Werte die für die Berechnung benötigt werden als Ergebnis zur Verfügung stehen */
        $this->previousOne=false; $this->previousTime=false;
        $this->countPos=0;
        $this->countNeg=0;
        $this->changeDir=0;
        $this->name=$name;
        $this->showmax=10;

        $this->result[$name]=array();
        $this->result[$name]["eventLog"]=array();
        $this->result[$name]["countPosMax"]=0;
        $this->result[$name]["countNegMax"]=0;
        return (true);
        }

    /* Übergabe wert[value] oder wert[avg] und wert[TimeStamp]
     * previousOne ist rückwärts oder vorwärts möglich, TimeStamp als PreviousTime mit betrachten
     *
     */
    function addValue($wert)
        {
        if (is_array($this->result)===false) return (false);
        if (is_array($wert)===false) return (false);
        if (isset($wert["Avg"])) $messwert = $wert['Avg'];  
        else $messwert = $wert['Value']; 

        if ($this->previousOne===false) 
            {
            $this->previousOne=$messwert;
            $this->previousTime=$wert['TimeStamp'];
            $delay=0;
            }
        else $delay = $this->previousTime-$wert['TimeStamp'];
        if ($delay>0) $changeValue=($this->previousOne/$messwert-1);
        else $changeValue=($messwert/$this->previousOne-1);
        if ( ($this->confEventLog) && ($changeValue<0) )            // von nun an geht es bergab 
            {
            if ($this->debug) echo "-";
            if ( ($this->confLogChangeNeg) && ($changeValue<(-$this->confLogChangeNeg)) )
                {
                //echo "Event: Kursänderung größer -2%: \n";
                //echo "!";
                //echo nf(($wert['Value']/$previousOne-1)*100,"%")." ";
                $this->result[$this->name]["eventLog"][$wert['TimeStamp']] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangeNeg."%"; 
                }
            $this->countNeg++;
            if ($this->changeDir=="pos")                  // das war ein Richtungswechsel
                {
                if ($this->countPos>$this->result[$this->name]["countPosMax"]) $this->result[$this->name]["countPosMax"]=$this->countPos;
                $this->countPos=0;
                }
            $this->changeDir="neg";
            }

        if ( ($this->confEventLog) && ($changeValue>0) )           // von nun an gehts bergauf
            {
            if ($this->debug) echo "+";
            if ( ($this->confLogChangePos) && ($changeValue>$this->confLogChangePos) )
                {
                //echo "Event: Kursänderung größer -2%: \n";
                //echo "!";
                //echo nf(($wert['Value']/$previousOne-1)*100,"%")." ";
                //$this->result[$this->name]["eventLog"][$wert['TimeStamp']]="Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangePos."%"; 
                $this->result[$this->name]["eventLog"][$wert['TimeStamp']]="Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangePos."% Wertänderung ".$this->previousOne." am ".date("d.m.Y H:i:s",$this->previousTime)." auf ".$messwert." ";
                }                
            $this->countPos++;
            if ($this->changeDir=="neg")                  // Richtungswechsel, Analyse des bisherigen Geschehens
                {
                if ($this->countNeg>$this->result[$this->name]["countNegMax"]) $this->result[$this->name]["countNegMax"]=$this->countNeg;
                $this->countNeg=0;
                }
            $this->changeDir="pos";
            }
        /* Update Letzter Wert für Differenzbetrachtung, entweder jeder Wert oder einen Zeitabstand in Sekunden
            * muss eine for Schleife werden
            */
        if ($this->confLogChangeTime)
            {
            $timegone =abs($this->previousTime-$wert['TimeStamp']);         // wann wurde der letzte Wert betrachtet, ein fixes Raster wird darüber gelegt
            if ($timegone>$this->confLogChangeTime)
                {
                //echo "."; 
                $this->previousOne=$messwert;                      // am Ende des Rasters wird der neue Referenzwert geschrieben, bis zum nächsten beginn muss die Differenz erkannt werden
                $this->previousTime=$wert['TimeStamp'];               
                }
            }   
        else            // jeder Wert wird betrachtet
            { 
            $this->previousOne=$messwert;  
            $this->previousTime=$wert['TimeStamp'];               
            }
        return (true);
        }


    /* eventLogEvaluate::addValueAsIndex
     * 
     * wenn ich in der Berechnung auch Vorwerte berücksichtigen muss, dann immer gleich das ganze Array übergeben, vereinfacht die Berechnungen zu einem Zeitpunkt 
     * benötigt den TimeStamp kann aber sowohl Value als auch Max/Min und Avg
     * Automatische Erkennung von Aggregated Werten wenn Avg vorhanden ist
     * bei zB Temperaturwerten muss man immer mit aggregierten Werten arbeiten, da sonst die Datenmenge schnell zu gross wird und die Änderungen zwischen den Messwerten zu klein
     *
     * Es wird ein Index auf das result array übergeben um den aktuellen Wert in Bearbeitung zu identifizieren
     * Messwert wird in previousOne/previousTime für den nächsten Durchlauf gespeichert, beim ersten Mal zusätzlich am Anfang, sonst am Ende der Funktion
     * durch die Veränderung des Timestamps
     * Ergebnis in result[name][eventLog][inputValues[index][TimeStamp]]
     *
     * Konfiguration
     *      confEventLog        $config["EventLog"], true für die Auswertung von Events
     *      confLogChangeTime   $config["LogChange"]["time"], erst nach Erreichen dieser Zeit die Aufzeichnung von Events beginnen
     *      InputValues         eigener Parameter, mit Pointer auf array, daten mit index und Avg oder Value, dieses array wird anstelle des Wertes oder result übergeben
     *
     * letztes, voriges Ergebnis in
     *
     * Prüfungen:
     *  Übergabe Werte als array
     */
    function addValueAsIndex($index)
        {
        // Art des Meswertes für die weitere Bearbeitung herausfinden 
        if (is_array($this->result)===false) return (false);
        if (is_array($this->inputValues)===false) return (false);           // zusätzliche Daten übergeben mit $config["InputValues"]
        if ($this->doString) return (false);                        // STring Analysen noch nicht definiert
        if ((is_int($index)) === false) echo $index." ";
        $aggregated=false;
        if (isset($this->inputValues[$index]["Avg"]))                       // wenn Daten als Avg kommen dann aggregated setzen
            {
            $aggregated=true;
            $messwert = $this->inputValues[$index]["Avg"];          // für previousOne
            }
        elseif (isset($this->inputValues[$index]["Value"])) $messwert = $this->inputValues[$index]["Value"];   // für previousOne 
        else return (false);
        if ($this->showmax) {$this->showmax--; if ($this->debug>1) echo "addValueAsIndex($index : $messwert ".date("d.m. H:i:s",$this->inputValues[$index]['TimeStamp'])."\n"; }

        // beim ersten Mal alles vorbereiten, Ergebnis ist delay, beim ersten Mal 0 sonst die zeitliche Differenz zum vorigen Wert und damit Info ob die Auswertung zeitlich nach vorne oder nach hinten geht 
        if ($this->previousOne===false)         // Wert von der ersten, später letzten Berechnung speichern , benötigt für Einzelwerte Analyse ohne zeitlicher Komponente
            {
            $this->previousOne=$messwert;
            $this->previousTime=$this->inputValues[$index]['TimeStamp'];
            $delay=0;
            if ($aggregated)                       // es gibt aggregierte Werte
                {         
                $this->previousMax     = $this->inputValues[$index]["Max"];
                $this->previousMaxTime = $this->inputValues[$index]["MaxTime"];
                $this->previousMin     = $this->inputValues[$index]["Min"];
                $this->previousMinTime = $this->inputValues[$index]["MinTime"];                               
                }
            else
                {
                $this->previousMax     = $messwert;  
                $this->previousMaxTime = $this->previousTime;                     
                $this->previousMin     = $messwert;   
                $this->previousMinTime = $this->previousTime;
                }
            }
        else $delay = $this->previousTime-$this->inputValues[$index]['TimeStamp'];          // delay wird zur Erkennung der Richtung der zeitlichen Veränderung identifiziert

        // nach all der Vorbereitung ermitteln wir eine Veränderung zum aktuellen Wert (messwert) in changeValue, zeitlich bereinigt 
        if ($delay>0) $changeValue=($this->previousOne/$messwert-1);
        elseif ($delay==0)
            {
            // Delay ist 0, erster Wert, höchster Index
            if ($this->debug>1) echo "Delay=0, Index ist $index, Date ".date("d.m.Y H:i:s",$this->inputValues[$index]['TimeStamp'])."\n";
            $changeValue=0;
            } 
        else
            {
            //echo $delay."\n";
            if ($this->previousOne != 0) $changeValue=($messwert/$this->previousOne-1);
            else $changeValue=0;
            }

        /* zusätzlich ein EventLog erstellen, 
         * event speichern wenn markante Veränderungen erfolgen, wir haben delay und changevalue zum letzten gemessenen Wert, 
         * wenn der Abstand zwischen den Werten sehr klein ist wird die Änderung auch nicht gross ausfallen
         */
        if ($this->confEventLog)                // EventLog eruieren
            {
            if ($this->confLogChangeTime)    // Eventlog für einen Zeitraum evaluieren
                {
                $span=0;
                if ($aggregated)
                    {
                    $messwertSpan        = $this->inputValues[$index+$span]["Avg"];
                    $messwertSpanTime    = $this->inputValues[$index+$span]["TimeStamp"];
                    $messwertSpanMax     = $this->inputValues[$index+$span]["Max"];
                    $messwertSpanMaxTime = $this->inputValues[$index+$span]["MaxTime"];
                    $messwertSpanMin     = $this->inputValues[$index+$span]["Min"];
                    $messwertSpanMinTime = $this->inputValues[$index+$span]["MinTime"];
                    $messwertMax=$messwertSpanMax;$messwertMin=$messwertSpanMin;$messwertMaxTime=$messwertSpanMaxTime;$messwertMinTime=$messwertSpanMinTime;
                    }
                else 
                    {
                    $messwertSpan        = $this->inputValues[$index+$span]["Value"];
                    $messwertSpanTime    = $this->inputValues[$index+$span]["TimeStamp"];
                    $messwertMax=$messwertSpan;$messwertMin=$messwertSpan;$messwertMaxTime=$messwertSpanTime;$messwertMinTime=$messwertSpanTime;
                    }
                $changePos=0; $changeNeg=0;
                /*
                $messwertSpan        = $this->previousOne;          // letzter Wert bevor aufgerufen
                $messwertSpanMax     = $this->previousMax;
                $messwertSpanMaxTime = $this->previousMaxTime;
                $messwertSpanMin     = $this->previousMin;
                $messwertSpanMinTime = $this->previousMinTime; */
                
                do                              // do-while besser als while, weil Werte der Abfrage möglicherweise nicht vorhanden
                    {
                    if ($aggregated)
                        {
                        if ($messwertSpanMax>$messwertMax) { $messwertMax = $messwertSpanMax; $messwertMaxTime = $messwertSpanMaxTime; }
                        if ($messwertSpanMin<$messwertMin) { $messwertMin = $messwertSpanMin; $messwertMinTime = $messwertSpanMinTime; }
                        }    
                    else
                        {
                        if ($messwertSpan>$messwertMax)    { $messwertMax = $messwertSpan; $messwertMaxTime = $messwertSpanTime; }
                        if ($messwertSpan<$messwertMin)    { $messwertMin = $messwertSpan; $messwertMinTime = $messwertSpanTime; }
                        }
                    $span++;                                                            // vom Index weiterzählen
                    if (isset($this->inputValues[$index+$span]["Avg"])) 
                        {
                        $messwertSpan        = $this->inputValues[$index+$span]["Avg"];
                        $messwertSpanMax     = $this->inputValues[$index+$span]["Max"];
                        $messwertSpanMaxTime = $this->inputValues[$index+$span]["MaxTime"];
                        $messwertSpanMin     = $this->inputValues[$index+$span]["Min"];
                        $messwertSpanMinTime = $this->inputValues[$index+$span]["MinTime"];
                        }  
                    elseif (isset($this->inputValues[$index+$span]["Value"])) 
                        {
                        $messwertSpan     = $this->inputValues[$index+$span]["Value"];
                        $messwertSpanTime = $this->inputValues[$index+$span]["TimeStamp"];
                        } 
                    else break;   
                    }
                while ((abs($this->inputValues[$index]['TimeStamp']-$this->inputValues[$index+$span]['TimeStamp']))<=$this->confLogChangeTime);
                //echo "($span)";
                if ( ( ($messwertMaxTime>$messwertMinTime) && ($delay>0) ) || ( ($messwertMaxTime<$messwertMinTime) && ($delay<0) ) ) $changeValue=($messwertMax/$messwertMin-1);
                else $changeValue=($messwertMin/$messwertMax-1);
                //echo nf($changeValue,"%");
                if ($this->first===false)
                    {
                    //$this->first=true;
                    }
                }                   // Ende confLogChangeTime

            $eventEntries = &$this->result[$this->name]["eventLog"];            // hier kommen die Events hin, anderer Pointer als die Datenreihe
            $keyprevious = array_key_last($eventEntries);                           // letzter Eintrag wenn Bezug darauf genommen werden soll
            $keyactual   = $this->inputValues[$index]['TimeStamp'];
            if ($changeValue<0)             // von nun an geht es bergab , changevalue in Prozent
                {
                //echo "-";
                if ( ($this->confLogChangeNeg) && ($changeValue<(-$this->confLogChangeNeg)) )
                    {
                        
                    //echo "Event: Kursänderung größer -2%: \n";
                    //echo "!";
                    //echo nf(($wert['Value']/$previousOne-1)*100,"%")." ";
                    /*if ($this->first===false)
                        {
                        $this->first=true;
                        echo date("d.m.Y H:i:s",$this->previousTime)."       ".$this->previousOne=$messwert."\n";
                        if ($aggregated) for ($span=0;$span<10;$span++) echo date("d.m.Y H:i:s",$this->inputValues[$index+$span]['MaxTime'])."     ".$this->inputValues[$index+$span]["Max"]."     ".date("d.m.Y H:i:s",$this->inputValues[$index+$span]['MinTime'])."     ".$this->inputValues[$index+$span]["Min"]."\n";
                        else             for ($span=0;$span<10;$span++) echo date("d.m.Y H:i:s",$this->inputValues[$index+$span]['TimeStamp'])."     ".$this->inputValues[$index+$span]["Value"]."\n";
                        }  */
                    $eventEntries[$keyactual]["Change"]    = $changeValue;
                    $eventEntries[$keyactual]["TimeStamp"] = $keyactual;
                    if ($this->confLogChangeTime) 
                        {
                        $eventEntries[$keyactual]["Event"] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".nf($this->confLogChangeNeg,"%").",  Wertänderung $messwertMax ".date("d.m. H:i:s",$messwertMaxTime)." / $messwertMin ".date("d.m. H:i:s",$messwertMinTime);
                        $eventEntries[$keyactual]["Max"]     = $messwertMax;
                        $eventEntries[$keyactual]["MaxTime"] = $messwertMaxTime;
                        $eventEntries[$keyactual]["Min"]     = $messwertMin;
                        $eventEntries[$keyactual]["MinTime"] = $messwertMinTime;
                        }
                    else 
                        {
                        $eventEntries[$keyactual]["Event"] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".nf($this->confLogChangeNeg,"%").", Wertänderung ".$this->previousOne." am ".date("d.m. H:i:s",$this->previousTime)." auf ".$messwert." vom ".date("d.m. H:i:s",$keyactual); 
                        }
                    }
                $this->countNeg++;
                if ($this->changeDir=="pos")                  // das war ein Richtungswechsel
                    {
                    if ($this->countPos>$this->result[$this->name]["countPosMax"]) $this->result[$this->name]["countPosMax"]=$this->countPos;
                    $this->countPos=0;
                    }
                $this->changeDir="neg";
                }

            if ($changeValue>0)            // von nun an gehts bergauf
                {
                //echo "+";
                if ( ($this->confLogChangePos) && ($changeValue>$this->confLogChangePos) )
                    {
                    //echo "Event: Kursänderung größer -2%: \n";
                    //echo "!";
                    //echo nf(($wert['Value']/$previousOne-1)*100,"%")." ";
                    //$this->result[$this->name]["eventLog"][$wert['TimeStamp']]="Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangePos."%"; 
                    /* if ($this->first===false)
                        {
                        $this->first=true;
                        echo date("d.m.Y H:i:s",$this->previousTime)."       ".$this->previousOne=$messwert."\n";
                        if ($aggregated) for ($span=0;$span<10;$span++) echo date("d.m.Y H:i:s",$this->inputValues[$index+$span]['MaxTime'])."     ".$this->inputValues[$index+$span]["Max"]."     ".date("d.m.Y H:i:s",$this->inputValues[$index+$span]['MinTime'])."     ".$this->inputValues[$index+$span]["Min"]."\n";
                        else             for ($span=0;$span<10;$span++) echo date("d.m.Y H:i:s",$this->inputValues[$index+$span]['TimeStamp'])."     ".$this->inputValues[$index+$span]["Value"]."\n";
                        } */
                    if ( (isset($this->result[$this->name]["eventLog"][$keyprevious]["Change"])) && ($changeValue > $this->result[$this->name]["eventLog"][$keyprevious]["Change"]) ) 
                        {
                        //echo "\n";
                        //unset($this->result[$this->name]["eventLog"][$keyprevious]);
                        }
                    //if ($changeValue > $this->result[$this->name]["eventLog"][$this->previousTime]["Change"]) ) unset($this->result[$this->name]["eventLog"][$this->previousTime]);
                    $event=&$this->result[$this->name]["eventLog"][$this->inputValues[$index]['TimeStamp']];
                    $eventEntries[$keyactual]["Change"]    = $changeValue;
                    $eventEntries[$keyactual]["TimeStamp"] = $keyactual;
                    if ($this->confLogChangeTime)
                        {
                        $eventEntries[$keyactual]["Event"] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".nf($this->confLogChangePos,"%").",  Wertänderung $messwertMax ".date("d.m. H:i:s",$messwertMaxTime)." / $messwertMin ".date("d.m. H:i:s",$messwertMinTime);
                        $eventEntries[$keyactual]["Max"]     = $messwertMax;
                        $eventEntries[$keyactual]["MaxTime"] = $messwertMaxTime;
                        $eventEntries[$keyactual]["Min"]     = $messwertMin;
                        $eventEntries[$keyactual]["MinTime"] = $messwertMinTime;
                        }
                    else                // Eintrag ins EventLog von eventLogEvaluate::addValueAsIndex
                        {
                        $eventEntries[$keyactual]["Event"] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".nf($this->confLogChangePos."%").", Wertänderung ".$this->previousOne." am ".date("d.m. H:i:s",$this->previousTime)." auf ".$messwert." vom ".date("d.m. H:i:s",$keyactual);
                        }
                    }                
                $this->countPos++;
                if ($this->changeDir=="neg")                  // Richtungswechsel, Analyse des bisherigen Geschehens
                    {
                    if ($this->countNeg>$this->result[$this->name]["countNegMax"]) $this->result[$this->name]["countNegMax"]=$this->countNeg;
                    $this->countNeg=0;
                    }
                $this->changeDir="pos";
                }

            /* Nachbearbeitung der Events, neues Event vorhanden, zumindest ein altes Event bereits angelegt */
            if ( (isset($eventEntries[$keyactual])) && (isset($eventEntries[$keyprevious])) )
                {
                if (abs($keyactual-$keyprevious)<=$this->confLogChangeTime)             // Abstand zwischen den Events zu kurz
                    {
                    if ( ($eventEntries[$keyactual]["Change"]<0) && ($eventEntries[$keyprevious]["Change"]<0) )
                        {
                        //if ( ( ($delay>0) &&  ($eventEntries[$keyactual]["Change"] < $eventEntries[$keyprevious]["Change"]) ) || ( ($delay<0) &&  ($eventEntries[$keyactual]["Change"] > $eventEntries[$keyprevious]["Change"]) ) )
                        if ( ($eventEntries[$keyactual]["Change"] < $eventEntries[$keyprevious]["Change"]) )
                            {
                            //echo "Two Events ".date("d.m.Y H:i:s",$keyactual)."  ".$eventEntries[$keyactual]["Change"]." delete: ".date("d.m.Y H:i:s",$keyprevious)."  ".$eventEntries[$keyprevious]["Change"]."\n";
                            unset ($eventEntries[$keyprevious]);
                            }
                        else
                            {
                            //echo "Two Events ".date("d.m.Y H:i:s",$keyprevious)."  ".$eventEntries[$keyprevious]["Change"]." delete: ".date("d.m.Y H:i:s",$keyactual)."  ".$eventEntries[$keyactual]["Change"]."\n";
                            unset ($eventEntries[$keyactual]);
                            }
                        }
                    elseif ( ($eventEntries[$keyactual]["Change"]>0) && ($eventEntries[$keyprevious]["Change"]>0) )
                        {
                        if ( ( ($delay>0) &&  ($eventEntries[$keyactual]["Change"] > $eventEntries[$keyprevious]["Change"]) ) || ( ($delay<0) &&  ($eventEntries[$keyactual]["Change"] > $eventEntries[$keyprevious]["Change"]) ) )
                            {
                            //echo "Two Events ".date("d.m.Y H:i:s",$keyactual)."  ".$eventEntries[$keyactual]["Change"]." delete: ".date("d.m.Y H:i:s",$keyprevious)."  ".$eventEntries[$keyprevious]["Change"]."\n";
                            unset ($eventEntries[$keyprevious]);    
                            }
                        else
                            {
                            //echo "Two Events ".date("d.m.Y H:i:s",$keyprevious)."  ".$eventEntries[$keyprevious]["Change"]." delete: ".date("d.m.Y H:i:s",$keyactual)."  ".$eventEntries[$keyactual]["Change"]."\n";
                            unset ($eventEntries[$keyactual]);
                            }
                        }
                    }
                //echo "Abstand war ".nf($keyprevious-$keyactual,"s")."\n";
                }
            }               // confEventLog true konfiguriert, Events bearbeiten

        $this->previousOne=$messwert;                      // am Ende des Rasters wird der neue Referenzwert geschrieben, bis zum nächsten beginn muss die Differenz erkannt werden
        $this->previousTime=$this->inputValues[$index]['TimeStamp'];
        return (true);
        }

    }


   /*  Auswertung von Archive Einträgen, Ausgaben in einen gemeinsamen Speicher
    *  das Ergebnis ist ein externes array, es wird nur der pointer übergeben
    *  es gilt besondere Ereignisse herauszufiltern, hier nur gaps von geloggten Daten mit vielen Einträgen, die eine Vorverarbeitung mit manueller Aggregation erfordern
    *  Übergabe config als Parameter:
    *      EventLog                aktiv
    *      LogChange.neg/pos       in prozent vom Wert
    *      LogChange.time          Zeit die betrachtet werden soll
    *      InputValues              das ganze Array mit den Daten, dann wird der aktuelle Index übergeben
    *
    * Ergebnis ist in result, die Inputdaten werden entweder als Einzelwert oder index übergeben
    *
    *
    *  __construct
    *  addValue                     abgeänderte und angepasste Routine
    *  addValueAsIndex
    *
    *
    *
    */

class gapsEval extends statistics
    {
    
    protected $name;
    protected $previousOne;
    protected $previousTime;                                            // um die Richtung und den zeitlichen Abstand feststellen zu können
    protected $maxdistance;

    protected $inputValues;
    protected $result;
    protected $first=false,$debug;

    /* die wichtigsten Variablen initialisieren, Ergebnis der Berechnungen wird in ein vom Auftraggeber vorbereitetes array gespeichert
     *
     * Ergebnis speichern in result[name]
     *     legt zusätzliche Untergruppen an
     *                      eventLog
     *                      countPosMax
     *                      countNegMax
     */

    function __construct(&$result,$name="All",$config=false,$debug=false)
        {
        if (is_array($result)===false) return (false);
        $this->debug = $debug;
        $this->result=&$result;             // ist sowieso ein pointer

        /* Config vorbereiten */
        if (isset($config["maxDistance"]))   $this->maxDistance=$config["maxDistance"];          
        else $this->maxDistance=false;

        $this->name=$name;

        /* init der für die Analyse benötigten Variablen, es müssen nicht alle Werte die für die Berechnung benötigt werden als Ergebnis zur Verfügung stehen */
        $this->previousOne=false; $this->previousTime=false;
        $this->result[$name]=array();
        return (true);
        }

    /* Übergabe wert[value] oder wert[avg] und wert[TimeStamp]
     * previousOne ist rückwärts oder vorwärts möglich, TimeStamp als PreviousTime mit betrachten
     *
     */
    function addValue($wert)
        {
        if (is_array($this->result)===false) return (false);
        if (is_array($wert)===false) return (false);
        if (isset($wert["Avg"])) $messwert = $wert['Avg'];  
        else $messwert = $wert['Value']; 

        if ($this->previousOne===false)             // Init der ersten Werten
            {
            $this->previousOne=$messwert;
            $this->previousTime=$wert['TimeStamp'];
            $delay=0;
            }
        else $delay = $this->previousTime-$wert['TimeStamp'];

        if ( $this->maxDistance && ((abs($delay)/60/60)>$this->maxDistance) )            // Abstand auf Stunden umrechnen
            {
            if ($this->debug) echo "Event Abstand von ".date("d.m.Y H:i:s",$wert['TimeStamp'])." bis ".date("d.m.Y H:i:s",$this->previousTime)." ist zu groß.\n";
            $this->result[$this->name][$wert['TimeStamp']]["Event"] = "Event Abstand von ".date("d.m.Y H:i:s",$wert['TimeStamp'])." bis ".date("d.m.Y H:i:s",$this->previousTime)." ist zu groß."; 
            $this->result[$this->name][$wert['TimeStamp']]["StartTime"] = $wert['TimeStamp']; 
            $this->result[$this->name][$wert['TimeStamp']]["EndTime"]   = $this->previousTime; 
            }

        $this->previousTime=$wert['TimeStamp'];            
        return (true);
        }


    /* gapsEval::addValueAsIndex
        * 
        * wenn ich Vorwerte auch berücksichtigen muss, dann das ganze Array übergeben 
        * benötigt den TimeStamp kann aber sowohl Value als auch Max/Min und Avg
        * Automatische Erkennung von Aggregated Werten wenn Avg vorhanden ist
        *
        * Messwert wird in previousOne/previousTime für den nächsten Durchlauf gespeichert, beim ersten Mal zusätzlich am Anfang, sonst am Ende der Funktion
        * durch die Veränderung des Timestamps
        * Ergebnis in result[name][eventLog][inputValues[index][TimeStamp]]
        *
        * Konfiguration
        *      confEventLog        true für die Auswertung
        *      confLogChangeTime   
        *
        * letztes, voriges Ergebnis in
        */

    function addValueAsIndex($index)
        {
        // Art des Meswertes für die weitere Bearbeitung herausfinden 
        if (is_array($this->result)===false) return (false);
        if (is_array($this->inputValues)===false) return (false);
        if ((is_int($index)) === false) echo $index." ";
        $aggregated=false;
        if (isset($this->inputValues[$index]["Avg"])) 
            {
            $aggregated=true;
            $messwert = $this->inputValues[$index]["Avg"];          // für previousOne
            }
        elseif (isset($this->inputValues[$index]["Value"])) $messwert = $this->inputValues[$index]["Value"];   // für previousOne 
        else return (false);

        // beim ersten Mal alles vorbereiten, Ergebnis ist delay, beim ersten Mal 0 sonst die zeitliche Differenz zum vorigen Wert und damit Info ob die Auswertung zeitlich nach vorne oder nach hinten geht 
        if ($this->previousOne===false)         // Wert von der ersten, später letzten Berechnung speichern , benötigt für Einzelwerte Analyse ohne zeitlicher Komponente
            {
            $this->previousOne=$messwert;
            $this->previousTime=$this->inputValues[$index]['TimeStamp'];
            $delay=0;
            if ($aggregated)                       // es gibt aggregierte Werte
                {         
                $this->previousMax     = $this->inputValues[$index]["Max"];
                $this->previousMaxTime = $this->inputValues[$index]["MaxTime"];
                $this->previousMin     = $this->inputValues[$index]["Min"];
                $this->previousMinTime = $this->inputValues[$index]["MinTime"];                               
                }
            else
                {
                $this->previousMax     = $messwert;  
                $this->previousMaxTime = $this->previousTime;                     
                $this->previousMin     = $messwert;   
                $this->previousMinTime = $this->previousTime;
                }
            }
        else $delay = $this->previousTime-$this->inputValues[$index]['TimeStamp'];

        // nach all der Vorbereitung ermitteln wir eine Veränderung in changeValue, zeitlich bereinigt 
        if ($delay>0) $changeValue=($this->previousOne/$messwert-1)*100;
        elseif ($delay==0)
            {
            // Delay ist 0, erster Wert, höchster Index
            if ($this->debug>1) echo "Delay=0, Index ist $index, Date ".date("d.m.Y H:i:s",$this->inputValues[$index]['TimeStamp'])."\n";
            $changeValue=0;
            } 
        else
            {
            //echo $delay."\n";
            if ($this->previousOne != 0) $changeValue=($messwert/$this->previousOne-1)*100;
            else $changeValue=0;
            }

        // zusätzlich ein EventLog erstellen, event soeichern wenn markante Veränderungen erfolgen
        if ($this->confEventLog)
            {
            if ($this->confLogChangeTime)    // Eventlog für einen Zeitraum evaluieren
                {
                $span=0;
                if ($aggregated)
                    {
                    $messwertSpan        = $this->inputValues[$index+$span]["Avg"];
                    $messwertSpanTime    = $this->inputValues[$index+$span]["TimeStamp"];
                    $messwertSpanMax     = $this->inputValues[$index+$span]["Max"];
                    $messwertSpanMaxTime = $this->inputValues[$index+$span]["MaxTime"];
                    $messwertSpanMin     = $this->inputValues[$index+$span]["Min"];
                    $messwertSpanMinTime = $this->inputValues[$index+$span]["MinTime"];
                    $messwertMax=$messwertSpanMax;$messwertMin=$messwertSpanMin;$messwertMaxTime=$messwertSpanMaxTime;$messwertMinTime=$messwertSpanMinTime;
                    }
                else 
                    {
                    $messwertSpan        = $this->inputValues[$index+$span]["Value"];
                    $messwertSpanTime    = $this->inputValues[$index+$span]["TimeStamp"];
                    $messwertMax=$messwertSpan;$messwertMin=$messwertSpan;$messwertMaxTime=$messwertSpanTime;$messwertMinTime=$messwertSpanTime;
                    }
                $changePos=0; $changeNeg=0;
                /*
                $messwertSpan        = $this->previousOne;          // letzter Wert bevor aufgerufen
                $messwertSpanMax     = $this->previousMax;
                $messwertSpanMaxTime = $this->previousMaxTime;
                $messwertSpanMin     = $this->previousMin;
                $messwertSpanMinTime = $this->previousMinTime; */
                
                do                              // do-while besser als while, weil Werte der Abfrage möglicherweise nicht vorhanden
                    {
                    if ($aggregated)
                        {
                        if ($messwertSpanMax>$messwertMax) { $messwertMax = $messwertSpanMax; $messwertMaxTime = $messwertSpanMaxTime; }
                        if ($messwertSpanMin<$messwertMin) { $messwertMin = $messwertSpanMin; $messwertMinTime = $messwertSpanMinTime; }
                        }    
                    else
                        {
                        if ($messwertSpan>$messwertMax)    { $messwertMax = $messwertSpan; $messwertMaxTime = $messwertSpanTime; }
                        if ($messwertSpan<$messwertMin)    { $messwertMin = $messwertSpan; $messwertMinTime = $messwertSpanTime; }
                        }
                    $span++;
                    if (isset($this->inputValues[$index+$span]["Avg"])) 
                        {
                        $messwertSpan        = $this->inputValues[$index+$span]["Avg"];
                        $messwertSpanMax     = $this->inputValues[$index+$span]["Max"];
                        $messwertSpanMaxTime = $this->inputValues[$index+$span]["MaxTime"];
                        $messwertSpanMin     = $this->inputValues[$index+$span]["Min"];
                        $messwertSpanMinTime = $this->inputValues[$index+$span]["MinTime"];
                        }  
                    elseif (isset($this->inputValues[$index+$span]["Value"])) 
                        {
                        $messwertSpan     = $this->inputValues[$index+$span]["Value"];
                        $messwertSpanTime = $this->inputValues[$index+$span]["TimeStamp"];
                        } 
                    else break;   
                    }
                while ((abs($this->inputValues[$index]['TimeStamp']-$this->inputValues[$index+$span]['TimeStamp']))<=$this->confLogChangeTime);
                //echo "($span)";
                if ( ( ($messwertMaxTime>$messwertMinTime) && ($delay>0) ) || ( ($messwertMaxTime<$messwertMinTime) && ($delay<0) ) ) $changeValue=($messwertMax/$messwertMin-1)*100;
                else $changeValue=($messwertMin/$messwertMax-1)*100;
                //echo nf($changeValue,"%");
                if ($this->first===false)
                    {
                    //$this->first=true;
                    }
                }                   // Ende confLogChangeTime

            $eventEntries = &$this->result[$this->name]["eventLog"];
            $keyprevious = array_key_last($eventEntries);   
            $keyactual   = $this->inputValues[$index]['TimeStamp'];
            if ($changeValue<0)             // von nun an geht es bergab , changevalue in Prozent
                {
                //echo "-";
                if ( ($this->confLogChangeNeg) && ($changeValue<(-$this->confLogChangeNeg)) )
                    {
                        
                    //echo "Event: Kursänderung größer -2%: \n";
                    //echo "!";
                    //echo nf(($wert['Value']/$previousOne-1)*100,"%")." ";
                    /*if ($this->first===false)
                        {
                        $this->first=true;
                        echo date("d.m.Y H:i:s",$this->previousTime)."       ".$this->previousOne=$messwert."\n";
                        if ($aggregated) for ($span=0;$span<10;$span++) echo date("d.m.Y H:i:s",$this->inputValues[$index+$span]['MaxTime'])."     ".$this->inputValues[$index+$span]["Max"]."     ".date("d.m.Y H:i:s",$this->inputValues[$index+$span]['MinTime'])."     ".$this->inputValues[$index+$span]["Min"]."\n";
                        else             for ($span=0;$span<10;$span++) echo date("d.m.Y H:i:s",$this->inputValues[$index+$span]['TimeStamp'])."     ".$this->inputValues[$index+$span]["Value"]."\n";
                        }  */
                    $eventEntries[$keyactual]["Change"]    = $changeValue;
                    $eventEntries[$keyactual]["TimeStamp"] = $keyactual;
                    if ($this->confLogChangeTime) 
                        {
                        $eventEntries[$keyactual]["Event"] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangeNeg."%,  Wertänderung $messwertMax ".date("H:i:s",$messwertMaxTime)." / $messwertMin ".date("H:i:s",$messwertMinTime);
                        $eventEntries[$keyactual]["Max"]     = $messwertMax;
                        $eventEntries[$keyactual]["MaxTime"] = $messwertMaxTime;
                        $eventEntries[$keyactual]["Min"]     = $messwertMin;
                        $eventEntries[$keyactual]["MinTime"] = $messwertMinTime;
                        }
                    else 
                        {
                        $eventEntries[$keyactual]["Event"] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangeNeg."%, Wertänderung ".$this->previousOne." um ".date("H:i:s",$this->previousTime)." auf ".$messwert." um ".date("H:i:s",$this->previousTime); 
                        }
                    }
                $this->countNeg++;
                if ($this->changeDir=="pos")                  // das war ein Richtungswechsel
                    {
                    if ($this->countPos>$this->result[$this->name]["countPosMax"]) $this->result[$this->name]["countPosMax"]=$this->countPos;
                    $this->countPos=0;
                    }
                $this->changeDir="neg";
                }

            if ($changeValue>0)            // von nun an gehts bergauf
                {
                //echo "+";
                if ( ($this->confLogChangePos) && ($changeValue>$this->confLogChangePos) )
                    {
                    //echo "Event: Kursänderung größer -2%: \n";
                    //echo "!";
                    //echo nf(($wert['Value']/$previousOne-1)*100,"%")." ";
                    //$this->result[$this->name]["eventLog"][$wert['TimeStamp']]="Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangePos."%"; 
                    /* if ($this->first===false)
                        {
                        $this->first=true;
                        echo date("d.m.Y H:i:s",$this->previousTime)."       ".$this->previousOne=$messwert."\n";
                        if ($aggregated) for ($span=0;$span<10;$span++) echo date("d.m.Y H:i:s",$this->inputValues[$index+$span]['MaxTime'])."     ".$this->inputValues[$index+$span]["Max"]."     ".date("d.m.Y H:i:s",$this->inputValues[$index+$span]['MinTime'])."     ".$this->inputValues[$index+$span]["Min"]."\n";
                        else             for ($span=0;$span<10;$span++) echo date("d.m.Y H:i:s",$this->inputValues[$index+$span]['TimeStamp'])."     ".$this->inputValues[$index+$span]["Value"]."\n";
                        } */
                    if ( (isset($this->result[$this->name]["eventLog"][$keyprevious]["Change"])) && ($changeValue > $this->result[$this->name]["eventLog"][$keyprevious]["Change"]) ) 
                        {
                        //echo "\n";
                        //unset($this->result[$this->name]["eventLog"][$keyprevious]);
                        }
                    //if ($changeValue > $this->result[$this->name]["eventLog"][$this->previousTime]["Change"]) ) unset($this->result[$this->name]["eventLog"][$this->previousTime]);
                    $event=&$this->result[$this->name]["eventLog"][$this->inputValues[$index]['TimeStamp']];
                    $eventEntries[$keyactual]["Change"]    = $changeValue;
                    $eventEntries[$keyactual]["TimeStamp"] = $keyactual;
                    if ($this->confLogChangeTime)
                        {
                        $eventEntries[$keyactual]["Event"] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangePos."%,  Wertänderung $messwertMax ".date("H:i:s",$messwertMaxTime)." / $messwertMin ".date("H:i:s",$messwertMinTime);
                        $eventEntries[$keyactual]["Max"]     = $messwertMax;
                        $eventEntries[$keyactual]["MaxTime"] = $messwertMaxTime;
                        $eventEntries[$keyactual]["Min"]     = $messwertMin;
                        $eventEntries[$keyactual]["MinTime"] = $messwertMinTime;
                        }
                    else 
                        {
                        $eventEntries[$keyactual]["Event"] = "Event: Kursänderung ".nf($changeValue,"%").", größer als ".$this->confLogChangePos."%, Wertänderung ".$this->previousOne." um ".date("H:i:s",$this->previousTime)." auf ".$messwert." um ".date("H:i:s",$this->previousTime);
                        }
                    }                
                $this->countPos++;
                if ($this->changeDir=="neg")                  // Richtungswechsel, Analyse des bisherigen Geschehens
                    {
                    if ($this->countNeg>$this->result[$this->name]["countNegMax"]) $this->result[$this->name]["countNegMax"]=$this->countNeg;
                    $this->countNeg=0;
                    }
                $this->changeDir="pos";
                }

            /* Nachbearbeitung der Events, neues Event vorhanden, zumindest ein altes Event bereits angelegt */
            if ( (isset($eventEntries[$keyactual])) && (isset($eventEntries[$keyprevious])) )
                {
                if (abs($keyactual-$keyprevious)<=$this->confLogChangeTime)
                    {
                    if ( ($eventEntries[$keyactual]["Change"]<0) && ($eventEntries[$keyprevious]["Change"]<0) )
                        {
                        //if ( ( ($delay>0) &&  ($eventEntries[$keyactual]["Change"] < $eventEntries[$keyprevious]["Change"]) ) || ( ($delay<0) &&  ($eventEntries[$keyactual]["Change"] > $eventEntries[$keyprevious]["Change"]) ) )
                        if ( ($eventEntries[$keyactual]["Change"] < $eventEntries[$keyprevious]["Change"]) )
                            {
                            //echo "Two Events ".date("d.m.Y H:i:s",$keyactual)."  ".$eventEntries[$keyactual]["Change"]." delete: ".date("d.m.Y H:i:s",$keyprevious)."  ".$eventEntries[$keyprevious]["Change"]."\n";
                            unset ($eventEntries[$keyprevious]);
                            }
                        else
                            {
                            //echo "Two Events ".date("d.m.Y H:i:s",$keyprevious)."  ".$eventEntries[$keyprevious]["Change"]." delete: ".date("d.m.Y H:i:s",$keyactual)."  ".$eventEntries[$keyactual]["Change"]."\n";
                            unset ($eventEntries[$keyactual]);
                            }
                        }
                    elseif ( ($eventEntries[$keyactual]["Change"]>0) && ($eventEntries[$keyprevious]["Change"]>0) )
                        {
                        if ( ( ($delay>0) &&  ($eventEntries[$keyactual]["Change"] > $eventEntries[$keyprevious]["Change"]) ) || ( ($delay<0) &&  ($eventEntries[$keyactual]["Change"] > $eventEntries[$keyprevious]["Change"]) ) )
                            {
                            //echo "Two Events ".date("d.m.Y H:i:s",$keyactual)."  ".$eventEntries[$keyactual]["Change"]." delete: ".date("d.m.Y H:i:s",$keyprevious)."  ".$eventEntries[$keyprevious]["Change"]."\n";
                            unset ($eventEntries[$keyprevious]);    
                            }
                        else
                            {
                            //echo "Two Events ".date("d.m.Y H:i:s",$keyprevious)."  ".$eventEntries[$keyprevious]["Change"]." delete: ".date("d.m.Y H:i:s",$keyactual)."  ".$eventEntries[$keyactual]["Change"]."\n";
                            unset ($eventEntries[$keyactual]);
                            }
                        }
                    }
                //echo "Abstand war ".nf($keyprevious-$keyactual,"s")."\n";
                }
            }               // confEventLog true konfiguriert, Events bearbeiten

        $this->previousOne=$messwert;                      // am Ende des Rasters wird der neue Referenzwert geschrieben, bis zum nächsten beginn muss die Differenz erkannt werden
        $this->previousTime=$this->inputValues[$index]['TimeStamp'];
        return (true);
        }

    }


/*  meansRollEvaluate, Auswertung von Archive Einträgen, Input ist ein externes array, es wird nur der pointer übergeben
    *  es gibt auch eine Konfiguration:
    *          TimeStampPos    Begin, Mid, End    Default is End
    *
    * vorhandene functions:
    *  __construct
    *  meansValues
    *
    *
    */

class meansRollEvaluate extends statistics
    {
        
        protected $name;
        protected $input,$config;
        protected $showmax;
        protected $debug;

        /* in der class input werden die Werte gespeichert, ist nur der pointer auf ein externes array 
         */

        function __construct(&$input,$config=false,$debug=false)
            {
            if (is_array($input)===false) return (false);
            $this->input=&$input;

            $this-> debug = $debug;
            //$this-> debug = true;
            /* Config vorbereiten */
            if ($config === false)
                {
                $this->config["TimeStampPos"]="End";
                $this->config["CalcDirection"]="Backward";
                }
            else $this->config=$config; 
            $this->showmax=0;           
            /* init der für die Analyse benötigten Variablen, es müssen nicht alle Werte die für die Berechnung benötigt werden als Ergebnis zur Verfügung stehen */

            return (true);
            }

        /* Übergabe index auf wert[value] oder wert[avg] und wert[TimeStamp] in input und ein count als Wert oder Identifier
         * wir brauchen einen timeStamp um den Wert einem Zeitpunkt zuordnen, sonst false
         *
         * previousOne ist rückwärts oder vorwärts möglich, TimeStamp mit betrachten
         * berechnet einen rollierenden Mittelwert mit countInput Werten
         *      day, week, month
         * count ist die maximale Anzahl an Werten die berücksichtigt wird, wir beginnen bei index und zählen nach oben
         * wenn duration gesetzt ist wird nach Ablauf der Zeit bereits mit der Berechnung abgebrochen
         *
         */
        function meansValues($index, $countInput)
            {
            // preparation of work
            $debug=$this->debug;
            if (is_array($this->input)===false) return (false);                                                 //kein Daten array bedeutet Abbruch
            if (isset($this->input[$index]["TimeStamp"])) $timeStamp = $this->input[$index]["TimeStamp"];       // aktueller TimeStamp entweder false oder mit einem Wert
            else $timeStamp = false; 
            if (is_numeric($countInput)===false)                        // countInput festlegen
                {
                switch (strtoupper($countInput))    
                    {
                    case "YEAR":
                        $count=400;                          // mehr Arbeitstage, Count, es wird nach duration abgebrochen
                        //$debug=false;
                        $duration=(366*24*60*60);         // es werden Zeitstempel verglichen, Kalendertage, Schalttage mitnehmen
                        break;
                    case "MONTH":
                        $count=40;                          // mehr Arbeitstage, Count, es wird nach duration abgebrochen
                        //$debug=false;
                        $duration=(30*24*60*60);         // es werden Zeitstempel verglichen, Kalendertage
                        break;
                    case "WEEK":
                        $count=10;                           // mehr Arbeitstage, Count, es wird nach duration abgebrochen
                        //$debug=false;
                        $duration=(7*24*60*60);         // es werden Zeitstempel verglichen
                        break;
                    default:
                        break;
                    }
                }
            else                // nu die Werte zählen um den Mittelwert zu bekommen
                {
                $debug=false;
                $count=$countInput;
                $duration=false;                        // es wird nur gezählt
                }

            /* rollierender Mittelwert , diesen und die nächsten count Werte zusammenzählen 
             * Value/Avg und TimeStamp wird benötigt
             * startTime ist der erste Wert
             * wenn kein Wert da ist wird error hochgezählt
             *
             */
            $sumRol=0; $countRol=0; $sumTime=0; $aktTime=false; $value=false; $startTime = false; $endTime=false; $error=0;
            $prevTime=false; $prevValue=false;

            if ($this->config["CalcDirection"]=="Backward") 
                {
                //echo "wir zählen rückwärts.\n";
                $dir = -1;            // backward
                }
            else $dir = 1;                                                        // forward
            for ($i=0;$i<$count;$i++)           // count wird weiter oben berechnet
                {
                $nextIndex=($index+$dir*$i);  // nächster nach vor oder zurück
                if ($nextIndex<0) {  $error=1; $countRol=0; break;}                                                  
                if ($this->showmax) echo "\n ".str_pad("$index+$dir*$i=".$nextIndex,16)."  ".str_pad($countRol,5)."  ".str_pad($countInput,22)." ";

                // alten Wert kopieren, neuen Wert einlesen, falsche Werte ignorieren            
                if ($value) $prevValue=$value;
                if (isset($this->input[$nextIndex]["Value"])) $value = $this->input[$nextIndex]["Value"]; 
                elseif (isset($this->input[$nextIndex]["Avg"])) $value = $this->input[$nextIndex]["Avg"];
                else $value=false;

                // Zeitstempel ermitteln
                if (isset($this->input[$nextIndex]["TimeStamp"])) 
                    {
                    $prevTime=$aktTime;
                    $aktTime = $this->input[$nextIndex]["TimeStamp"];
                    $sumTime += $aktTime;
                    if ($this->showmax) echo date("d.m.Y H:i:s",$aktTime)."  ";
                    if  ($startTime===false)  $startTime=$aktTime;         // Startzeitpunt ermitteln
                    }
                if ($value)
                    {
                    if ($this->showmax) echo "->".$value;             // den Wert, entweder Value oder Avg
                    if (is_numeric($value)) $sumRol += $value;
                    $countRol++;
                    }
                else $error++;                  // kein Wert
                if ( ($duration) && ($aktTime) && ((abs($aktTime-$startTime))>$duration) )           // Abbruch wenn Zeit erreicht wurde
                    {
                    /* Interpolieren nur mehr optional
                    $dif=$value-$prevValue;
                    $dif=$dif-$dif/($aktTime-$prevTime)*$duration;
                    $sumRol=$sumRol-$dif;
                    if ($debug) echo "-> $duration excceed by ".($aktTime-$startTime-$duration)." seconds with Value $value. Last Value $prevValue. Last Time ".date("d.m.Y H:i:s",$prevTime)."  Total Sum Corrected by subtracting $dif.\n";
                    $aktTime=$startTime+$duration;   */
                    break;            // Dauer bereits überschritte es fehlen Werte
                    }
                }
            if ($countRol>0)
                {
                /* Mittelwertberechnung möglich */
                $ergebnis=array();
                $ergebnis["Value"] = $sumRol/$countRol;
                $midTime = $sumTime/$countRol;
                if (strtoupper($this->config["TimeStampPos"])=="MID")
                    {
                    $ergebnis["TimeStamp"]=$midTime;
                    }
                else $ergebnis["TimeStamp"]=$timeStamp;             //Wert der mit Index übergeben wurde
                $ergebnis["startTime"] = $startTime;                // startTime ist der erste Wert
                $ergebnis["endTime"]   = $aktTime;                  // aktTime nach countRol Werten oder duration , Wert für sumRol wurde eventuell interpoliert
                if ($this->showmax) echo "MIttelwert : ".$ergebnis["Value"]." um  ".date("d.m.Y H:i:s",$ergebnis["TimeStamp"])."  ".json_encode($ergebnis)."\n";             // den Wert, entweder Value oder Avg
                }
            if ($this->showmax) $this->showmax--;               // runterzählen bis null
            if ($error) $ergebnis["error"]=$error;
            return ($ergebnis);
            }

        }


/*  Auswertung von Archive Einträgen, Als speicher für das Ergebns wird ein externes array benutzt, es wird nur der pointer übergeben
    *  Immer überprüfen ob alles in Ordnung ist, damit nicht irgendwo ein Speicher überschrieben wird
    *  addValue kann alle drei Formate, nur den Wert, mit TimeStamp oder mit Max/Min Aggregated
    *  Ergebnis ist ein array mit Value/Timestamp. 
    *  Aktuell verwendet in GetValues. 
    *
    *  Berechnet folgende Werte:
    *      Max
    *      Min
    *      Means
    *
    *  Konfiguration:  aktuell keine
    *  Vorwerte:       verfügt über keien Vorwerte, wird immer mit aktuellem Wert aufgerufen
    *
    *  __construct
    *  addValue
    *  calculate
    *  youngest
    *  print
    *
    */

class maxminCalc extends statistics
    {
        
        protected $name;
        protected $result;
        protected $maxValue=false, $maxTime=false, $minValue=false, $minTime=false;                                 // Max/Min Berechnung
        protected $youngest=array();                                                                            // jüngster Wert, mit höchstem Zeitstempel
        protected $oldest=array();                                                                            // ältester Wert, mit niedrigstem Zeitstempel
        protected $sum=0, $sumTime=false, $count=0;                                                                                       // Mittelwert Berechnung
        protected $sum1=0,$sum2=0,$sumTime1=false,$sumTime2=false,$count1=0,$count2=0;                          //

        function __construct(&$result,$name="All",$config=false,$debug=false)
            {
            if (is_array($result)===false) return (false);
            $this->result=&$result;
            if (is_array($this->result)===false) return (false);
            if ($debug) echo "maxminCalc für $name aufgerufen.\n";
            $this->youngest["TimeStamp"] = 0;
            $this->youngest["Value"]     = 0;
            $this->oldest["TimeStamp"] = false;
            $this->oldest["Value"]     = 0;            
            $this->name=$name;
            return (true);
            }

        /* Übergabe wert[value] und wert[TimeStamp]
         *
         */
        function addValue($wertInput)
            {
            //echo ".";
            if (is_array($this->result)===false) return (false);
            if (is_array($wertInput)===false)                                        // Wert ist nur Wert, es gibt keinen Zeitstempel
                {
                $wert["Value"]=$wertInput;
                $wert["TimeStamp"]=false;
                }
            else $wert=$wertInput;
            if (isset($wert["Max"]))                                            // aggregated Archive Avg
                {
                if ( ($this->maxValue===false) || ($this->maxValue<$wert["Max"]) )
                    {
                    $this->maxValue = $wert["Max"]; 
                    $this->maxTime  = $wert["MaxTime"]; 
                    }
                if ( ($this->minValue===false) || ($this->minValue>$wert["Min"]) )
                    {
                    $this->minValue = $wert["Min"]; 
                    $this->minTime  = $wert["MinTime"]; 
                    }
                $this->sum     += $wert["Avg"];                                     // Mittelwert mit Einzelwerte Berechnung
                if ($this->sumTime===false) $this->sumTime = $wert["TimeStamp"];
                else $this->sumTime += $wert["TimeStamp"];

                // Alternative als Dreiecke und Flächen, Integral

                }
            else                                                                // logged Archive Value
                {
                if ( ($this->maxValue===false) || ($this->maxValue<$wert["Value"]) )
                    {
                    $this->maxValue = $wert["Value"]; 
                    $this->maxTime  = $wert["TimeStamp"]; 
                    }
                if ( ($this->minValue===false) || ($this->minValue>$wert["Value"]) )
                    {
                    $this->minValue = $wert["Value"]; 
                    $this->minTime  = $wert["TimeStamp"]; 
                    }
                if (is_numeric($wert["Value"])) $this->sum += $wert["Value"];                      
                if ($wert["TimeStamp"])
                    {
                    if ($this->sumTime===false) $this->sumTime = $wert["TimeStamp"];
                    else $this->sumTime += $wert["TimeStamp"];
                    }
                }

            if ($wert["TimeStamp"]>$this->youngest["TimeStamp"] )         // bei false nicht der Fall, für alle anderen passt es
                {
                $this->youngest["TimeStamp"] = $wert['TimeStamp'];
                if (isset($wert["Avg"]))    $this->youngest["Value"]     = $wert['Avg'];                // Aggregierte Werte auch behandeln
                else                        $this->youngest["Value"]     = $wert['Value'];   
                }

            if ( ($this->oldest["TimeStamp"]===false) || ($wert["TimeStamp"]<$this->oldest["TimeStamp"]) )         // bei false nicht der Fall, für alle anderen passt es
                {
                $this->oldest["TimeStamp"] = $wert['TimeStamp'];
                if (isset($wert["Avg"]))    $this->oldest["Value"]     = $wert['Avg'];                // Aggregierte Werte auch behandeln
                else                        $this->oldest["Value"]     = $wert['Value'];   
                }

            $this->count++;
            }

        function rating($wert)
            {
            $base  = $this->result[$this->name]["Max"]["Value"];       // steht für 0, bsp 22              Schlechteste Berwetung
            $high  = $this->result[$this->name]["Min"]["Value"];       // steht für 1, bsp 0.2             Bestwertung
            $means = $this->result[$this->name]["Means"]["Value"];        // steht für 0,5 , bsp 4
            $rateBase=$means-$base;                         // -18
            $rateHigh=$high-$means;                         // -3.8   negativ weil umgekehrt, grosse Werte nicht die Beste Bewertung haben
            $rate=$high-$base;
            if ($high>$base)                                    // gross ist eins und klein ist 0
                {
                if ($wert>=$means) $ergebnis = abs(($wert-$means)/$rateHigh/2)+0.5;
                else $ergebnis = 0.5-abs(($means-$wert)/$rateBase/2); 
                }
            else                                                // gross ist 0 und klein ist 1, Max-Min ist negativ
                {
                if ($wert<$means) $ergebnis = abs(($wert-$means)/$rateHigh/2)+0.5;                        // bsp 0.2    1..0 => 1..0.5
                else $ergebnis = 0.5-abs(($means-$wert)/$rateBase/2);             // rating ist >0.5              // bsp 22   1   4  0 
                }
            $ergebnis2 = abs(($wert-$base)/$rate);            // base ist 0 
            return($ergebnis);

            }


        /* für Mittelwert gut geeignet, wenn alles summiert ist am Ende dividieren 
         * für Max, Min nur das Array schreiben
         */

        function calculate()
            {
            $this->result[$this->name]["Max"]["Value"]     = $this->maxValue; 
            $this->result[$this->name]["Max"]["TimeStamp"] = $this->maxTime; 
            $this->result[$this->name]["Min"]["Value"]     = $this->minValue; 
            $this->result[$this->name]["Min"]["TimeStamp"] = $this->minTime;
            if ($this->sumTime)
                { 
                $this->result[$this->name]["Means"]["Value"]     = $this->sum/$this->count; 
                $this->result[$this->name]["Means"]["TimeStamp"] = $this->sumTime/$this->count;
                }
            else  $this->result[$this->name]["Means"]["Value"]     = $this->sum/$this->count;
            if ( ($this->oldest["TimeStamp"]) && (($this->count-1)>0) )
                {
                $this->result[$this->name]["Span"] = ($this->youngest["TimeStamp"]-$this->oldest["TimeStamp"])/($this->count-1);
                }
            return (true);
            }    

        function youngest()
            {
            $this->result[$this->name]["Youngest"] = $this->youngest; 
            }

        function oldest()
            {
            $this->result[$this->name]["Oldest"] = $this->oldest; 
            }

        function print()
            {
            echo "maxminCalc::print, result for ".$this->name.":\n";
            echo "     Max Value  ".$this->maxValue." on ".date("d.m.Y H:i:s",$this->maxTime)."\n";
            echo "     Min Value  ".$this->minValue." on ".date("d.m.Y H:i:s",$this->minTime)."\n";
            if ($this->sumTime) echo "     Means Value  ".$this->result[$this->name]["Means"]["Value"]." on ".date("d.m.Y H:i:s",$this->result[$this->name]["Means"]["TimeStamp"])."\n";
            else echo "     Means Value  ".$this->result[$this->name]["Means"]["Value"]."\n";
            }        

        }
        
/* Basis ist eine übernommene Funktion mit ein paar Erweiterungen
 *
 *
 * Converting XML to an array isn't easy. But if you convert it, then it's a lot easier to use.
 * As you and I both know, this isn't the best way of doing things. After years of playing around with the DOMDocument, I created this class to convert an XML string to a well formatted PHP Array.
 *
 *  analyseHtml             string analyse eine html files, nur zur Orientierung sinnvoll wenn kein Webpage Debugger F12 zur Verfügung steht
 *  XmlToArray              Convert a given XML String to Array
 *      DOMDocumentToArray
 *  walkHtml                recursiver walk durch ein DOM object, speichert ein array mit dem Resultat
 *  innerHTML               returns a string with the HTML content from a DOMDocument node element ($elm)
 *  queryFilterHtml         query with standard xpath an html with DOM and filter the result of attributes for tagnames as style 
 *  extractFilterHtml       the html result of queryFilterHtml als arry speichern 
 *
 */

class App_Convert_XmlToArray 
    {
    const NAME_ATTRIBUTES = '@attributes';
    const NAME_CONTENT = '@content';
    const NAME_ROOT = '@root';
    var $level;                                     // parsing DOM
    var $result;                                    // store ergebnis, used in recursive function walkHtml
    var $listfound=array();

    public function __construct()
        {
        $this->level=0;
        }


    /* App_Convert_XmlToArray::analyseHtml ein html analysieren, ist nur eine Orientierungsfunktion, besser DOM verwenden
     * das erste Mal aufgtaucht im SeleniumHandler, es handelt sich um einen selbstgebauten html/xml parser
     * es wird zeichenweise analysiert, einfacher Algorythmus:
     *   ident      für die aktuelle Hioerarchie, wir fangen mit 0 an, ein ident Zähler ist auch ein Space Zeichen
     *
     *
     * hier werden die bekannten Search Algorithmen unterstützt
     *
     * false DIV :
     * <div style="width: 608px">
     */

    function analyseHtml($page,$displayMode=false,$commandEntry=false)
        {
        $findID=false; 
        $mode="All";
        $display=true;
        $pageLength = strlen($page);
        echo "analyseHtml Size of Input : ".nf($pageLength,"Bytes")."\n";
        //echo $page;
        $lineShow=false;
        if ($commandEntry !== false) $commandDisplay=strtoupper($commandEntry);
        else $commandDisplay="UNKNOWN";
        if ($displayMode===false) $display = false;
        elseif (is_array($displayMode))
            {
            echo "  Config ".json_encode($displayMode)."\n";
            if (isset($displayMode["findID"])) $findID=$displayMode["findID"];
            if (isset($displayMode["ShowLine"])) $lineShow=$displayMode["ShowLine"];
            if (isset($displayMode["mode"])) $mode=$displayMode["mode"];
            if ($mode !== "All") $display=false;
            }
        elseif ($displayMode>1)
            {
            $display  = false;
            $lineShow = $displayMode;
            }
        else $display = true;
        $zeile=0; $until=0;

        /* ausgabe i erfolgt auf 0 und dann nicht mehr */
        $pos=false; $ident=0; $end=false; 
        for ($i = 0; $i < $pageLength; $i++)                //html parser
            {
            if ($i<$until) 
                {
                echo htmlentities($page[$i]);           // funktioniert gut < wird in &lt; umgewandelt
                //echo "$i(".ord($page[$i]).") ";
                //echo $i.":".$page[$i].".";
                }
            if ($page[$i]=="<")             // erstes <, damit wird pos und ident bearbeitet
                {
                $pos=$i;
                $ident++;
                }
            if (($page[$i]=="/") && ($pos !== false) ) { $ident--; $end=true; }
            if (($page[$i]=="\n") || ($page[$i]=="\r")) 
                {
                $zeile++;
                if ( ($mode=="Line") && ($zeile>$lineShow) ) $display=true;            // im Default Mode wird Display sofort eingeschaltet 
                }
            if ((($page[$i]==" ") || ($page[$i]=="\n") || ($page[$i]=="\r") || ($page[$i]==">")) && ($pos !== false) )           // ein Trennzeichen, pos=0 akzeptieren, pos=false nicht, logischerweise fangt es mit einem  < an
                {
                $epos=strpos($page,">",$pos);
                $command=strtoupper(substr($page,$pos+1,$i-$pos-1));
                //echo $zeile."  ".htmlentities(substr($page,$pos,($epos-$pos+1)))." : \"$command\"\n";
                //if ($command == "SYMBOL") $display=false;
                if ( ($mode=="findCMD") && ($command == $commandDisplay) ) $display=true;
                if ( ($command != "BR") && ($command != "IMG") && ($command != "!DOCTYPE") && ($command != "!--") && ($command != "HTML") && ($command != "META") && ($command != "LINK"))
                    {
                    //if ($display)
                        {
                        //if ($command == "BUTTON")
                            {
                            //echo strtoupper($command);                                                          // Command=Tag
                            // i steht erst bei dem ersten trennzeichen, epos beim Ende
                            //echo "check $i $epos ";
                            $attributes=""; $Wert=false;
                            $wertFound="";
                            if ( ($epos)>($i) ) 
                                {
                                $attributes=substr($page,$i,$epos-$i);
                                //echo str_pad(": $attributes   ",(100-$ident));
                                $attLen=strlen($attributes);
                                $sCmd=false; $Cmd=false;
                                for ($j=0;($j<$attLen);$j++)
                                    {
                                    if ( ($attributes[$j]==" ") || ($attributes[$j]=="=") || ($attributes[$j]=="\n") || ($attributes[$j]=="\r") )
                                        {
                                        if ($sCmd===false) $sCmd=$j;
                                        else 
                                            {
                                            if (($j-$sCmd)>1) 
                                                {
                                                $Cmd = trim(substr($attributes,$sCmd,$j-$sCmd));
                                                if ( ($findID===false) || (strtoupper($Cmd)==="ID") ) 
                                                    {
                                                    $sWert=false; $Wert=false;
                                                    for ($k=$j;($k<$attLen);$k++)
                                                        {
                                                        if ($attributes[$k]=='"')
                                                            {
                                                            if ($sWert===false) $sWert=$k;
                                                            else
                                                                {
                                                                $Wert=trim(substr($attributes,$sWert+1,($k-$sWert-1)));
                                                                }
                                                            }
                                                        }
                                                    //echo "Mode $display $mode $findID Attribute \"$Cmd\" = \"$Wert\" ";
                                                    if ( ($findID) && ($findID==$Wert) )                // && ($mode=="findID")
                                                        {
                                                        //echo "*************************";
                                                        $wertFound=$Wert;
                                                        $display=true;
                                                        }
                                                    }
                                                $sCmd=false;
                                                }
                                            }
                                        }   
                                    }
                                }
                            //else echo str_pad(" ",(100-$ident));
                            //else echo ($epos-$pos+1)."<=".($i-$pos-1);
                            //for ($p=$pos;$p<=$i;$p++) echo ord($page[$p]).".";
                            if ($display) 
                                {
                                if ( ($ident<100) && ($ident>0) )  for ($p=0;$p<$ident;$p++) echo " ";              // ident with blanks
                                echo strtoupper($command).str_pad(": $attributes   ",(100-$ident))." $wertFound  (Debug akt < len line cmd: $i $pos ".($epos-$pos)." $zeile $command)\n";
                                }
                            }
                        }
                    if ($end) {$ident--; $end=false;}
                    }
                else $ident--;
                $pos=false; 
                if ( ($mode=="findCMD") && ($command == "/".$commandDisplay) ) $display=false;
                //if ($command == "/SYMBOL") $display=true;
                }
            }
        echo "Insgesamt wurden $zeile Zeilen analysiert.\n";
        }

    /**
     * Convert a given XML String to Array
     * [body][div][a] => Array  (
            [0] => Array( [@content] => Musterdepot 1, @attributes] => Array([href] => /markets/musterdepot/308831, [class] => dropdown-item ))
            [1] => Array( [@content] => Musterdepot 2, [@attributes] => Array([href] => /markets/musterdepot/308832, [class] => dropdown-item))
            ....
            [6] => Array( [@content] => Musterdepot 3, [@attributes] => Array([href] => /markets/musterdepot/2531376, [class] => dropdown-item))
                                )
                 [@attributes] => Array( [class] => dropdown-menu wmax-400 wmax-lg-600 d-print-none, [x-placement] => bottom-start))
     *
     * uses DOMDocumentToArray
     * @param string $xmlString
     * @return array|boolean false for failure
     */
    public static function XmlToArray($xmlString,$debug=false) 
        {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);           
        //$load = $doc->loadXML($xmlString);              // oder xml
        $load = $doc->loadHTML($xmlString);              // oder html
        libxml_use_internal_errors(false);   
        if ($load == false) 
            {
            return false;
            }
        $root = $doc->documentElement;
        $output = self::DOMDocumentToArray($root,$debug);              // die eigentliche Konvertierungsfunktion
        $output[self::NAME_ROOT] = $root->tagName;
        return $output;
        }

    /**
     * Convert DOMDocument->documentElement to array
     * arbeitet rekursiv
     *
     * @param DOMElement $documentElement
     * @return array
     */
    protected static function DOMDocumentToArray($documentElement, $debug=false, $indent=0) 
        {
        //if ($debug) echo "\nDOMDocumentToArray ";           // neue Zeile bei rekursiven Aufruf
        $return = array();
        switch ($documentElement->nodeType) 
            {
            case XML_CDATA_SECTION_NODE:
                $return = trim($documentElement->textContent);
                if ($debug) echo " XML_CDATA_SECTION_NODE $return ";
                break;
            case XML_TEXT_NODE:
                $return = trim($documentElement->textContent);
                if ($debug) echo " XML_TEXT_NODE $return ";
                break;
            case XML_ELEMENT_NODE:
                $childNodeLength=$documentElement->childNodes->length;
                $tagName="";
                if (isset($child->tagName)) $tagName = $child->tagName; 
                if ($debug) echo " XML_ELEMENT_NODE [$childNodeLength] <$tagName> ";
                //echo json_encode($return)." ";
                if ($childNodeLength>0)
                    {
                    //echo " >0 ";
                    for ($count=0; $count<$childNodeLength; $count++)           //ein vorhandenes return als String darf nicht mehr überschrieben werden, umwandeln als Content, mehrere Content zusammmenzählen
                        {
                        //echo $count." ";
                        $child = $documentElement->childNodes->item($count);
                        if ($debug) 
                            {
                            if (isset($child->tagName)) echo "<".$child->tagName."> " ;
                            echo "\n   "; for ($i=0;($i<$indent);$i++) { echo "    "; }
                            }
                        $childValue = self::DOMDocumentToArray($child,$debug, $indent+1);                      // rekursiv, wir steigen ausschliesslich hier nach unten
                        if(isset($child->tagName)) 
                            {
                            $tagName = $child->tagName;
                            if ($debug) echo " </$tagName> ";            // if tagname store result as [tagname][]  
                            //echo json_encode($childValue);
                            if(!isset($return[$tagName]))               // aber es gibt noch keinen Index mit tagName
                                {
                                //echo json_encode($return)." tagName $tagName ";
                                if (is_array($return)===false)                          // aber vielleicht einen Content von Text Node
                                    {
                                    $return = array(self::NAME_CONTENT=>$return);    
                                    }
                                $return[$tagName] = array();
                                }
                            $return[$tagName][] = $childValue;          // ist das return, wieder als array oder text
                            }
                        elseif($childValue || $childValue === '0') 
                            {
                            if (is_array($return)===false)                          // aber vielleicht einen Content von Text Node
                                {
                                $return .= (string) $childValue;
                                }
                            else        // return ist bereits ein array 
                                {
                                if (isset($return[self::NAME_CONTENT])) $return[self::NAME_CONTENT].= (string) $childValue;
                                else $return[self::NAME_CONTENT]=(string) $childValue;
                                }
                            //echo $return;
                            }
                        //print_r($childValue);
                        }
                    }
                //else echo " =0 ";
                
                // Die Auswertung beginnt

                if($documentElement->attributes->length && !is_array($return))             // nur ein text node darunter, childvalue ein String :  array(@content => text)
                    {
                    $return = array(self::NAME_CONTENT=>$return);
                    }

                if(is_array($return))                           // wenn kein text und keine attributes hier nicht sonst immer vorbei
                    {
                    if($documentElement->attributes->length)                    // return um attributes erweitern array(@content => text,$attributes => attributes)
                        {
                        $attributes = array();
                        foreach($documentElement->attributes as $attrName => $attrNode)
                            {
                            $attributes[$attrName] = (string) $attrNode->value;
                            }
                        $return[self::NAME_ATTRIBUTES] = $attributes;
                        }
                    foreach ($return as $key => $value)         // kleine Korrektur wenn array(tagname)
                        {
                        if(is_array($value) && count($value)==1 && $key!=self::NAME_ATTRIBUTES)
                            {
                            $return[$key] = $value[0];
                            }
                        }
                    }
                break;          // ende Node
            }
        return $return;
        }
    
    /* rekursive Funktion, auf der Suche nach 
     * DomElement wird übergeben mit childNodes und attributes
     * return Wert ist this->result, verwendet this->level
     */
    public function walkHtml($link,$debug=false)
        {
        if ($this->level==0)            // init routine
            {
            if ($debug) echo "walkHtml ";
            $this->result=array();
            }
        $this->level++;
        if(isset($link->tagName)) if ($debug) echo $link->tagName;
        if ($link->hasAttributes())
            {
            $attributes=array();                                        // sammeln für die Debug Ausgabe
            foreach($link->attributes as $attrName => $attrNode)
                {
                $attributes[$attrName] = (string) $attrNode->value;
                $this->result[$attrName] = (string) $attrNode->value;
                } 
            if ($debug) echo json_encode($attributes); 
            }                    
        if ($link->hasChildNodes())
            {
            $return=array();
            for ($count=0, $childNodeLength=$link->childNodes->length; $count<$childNodeLength; $count++) 
                {
                $child = $link->childNodes->item($count);
                $this->walkHtml($child,$debug);
                }
            }
        if (isset($link->textContent))
            {
            $text = $link->textContent;
            $text = str_replace("\n"," ",$text);
            if (strlen($text))
                {
                if (isset($attributes["class"])) 
                    {
                    $this->result[$attributes["class"]]=$text;              // orf
                    //echo $attributes["class"];
                    }
                else $this->result["content"]=$text;
                }
            if ($debug) echo " \"$text\" \n";
            }
                    //print_r($return);   
                
        if ($this->level==1) echo "\n";                    
        return ($this->result);
        }

    /* returns a string with the HTML content from a DOMDocument node element ($elm)
     */
    function innerHTML(DOMNode $elm,$count=false) { 
        $innerHTML = ''; 
        $children  = $elm->childNodes;
        $i=0;
        foreach($children as $child) { 
            $innerHTML .= $elm->ownerDocument->saveHTML($child);
            if (($i++)===$count) { echo "break"; break; }
            }

        return $innerHTML;
        }


    /* queryFilterHtml    siehe auch SeleniumHandler
     * xpath Tutorial:  https://www.w3schools.com/xml/xpath_syntax.asp
     *      nodename    selects all nodes with name nodename
     *      /           selects from the root node, absolute path
     *      //selection selects from the current node that match the selection, relative path
     *      //*[@id]    selects all elements with an id
     *
     *      //@lang     slects all attributes that are named lang , will not work as we look only for elements
     *
     * query and filter an html with DOM
     *   $textfeld    = GetValue($easyResultID);                ( html Datei, wie sie von einer Homepage geladen wird)
     *   $xPathQuery  = '//div[@*]';       selects all div with at least one attribute of any kind
     *   $filterAttr  = 'style';
     *   $filterValue ="width: 608px";
     * resultat sind alle Ergebnisse hintereinander in einem DOM, also arbeitet wie ein Filter
     * alternativ kann das array foundlist verwendet werden
     *
     * DomDocument ist das xml/html Objekt mit den passenden Methoden dafür, dom in diesem Fall der Speicher, mit dem auch andere Operationen durchgeführt werden können
     */
    function queryFilterHtml($textfeld,$xPathQuery,$filterAttr,$filterValue,$debug=false)
        {
        $innerHTML="";
        if ($debug) echo "---- queryFilterHtml(...,$xPathQuery,$filterAttr,$filterValue)\n";
        //echo "$textfeld\n";                                                                       // lieber nicht verwenden, wird wahrscheinlich sehr unübersichtlich

        //$dom = DOMDocument::loadHTML($textfeld);          // non static forbidden
        $dom = new DOMDocument;     
        libxml_use_internal_errors(true);               // Header, Nav and Section are html5, may cause a warning, so suppress     
        $dom->loadHTML($textfeld);                  // den Teil der Homepage hineinladen
        libxml_use_internal_errors(false);
        $xpath = new DOMXpath($dom);
        //echo $dom->saveHTML();
        //echo "-------Auswertungen--------------->\n";

        /*  
        $links = $dom->getElementsByTagName('div');
        foreach ($links as $book) 
            {
            echo $book->nodeValue, PHP_EOL;
            }  */

        // Tutorial query https://www.w3schools.com/xml/xpath_syntax.asp
        //$links = $xpath->query('//div[@class="blog"]//a[@href]');
        $links = $xpath->query($xPathQuery);
        if ($debug) echo "Insgesamt ".count($links)." gefunden für \"$xPathQuery\"\n";

        $count=0; $maxcount=5;          // max 5 Einträge im debug darstellen, aber alle einsammeln
        $result=array();                // für walkHtml Auswertung

        // alle Ergebnisse in ein neues DOM speichern 
        $tmp_doc = new DOMDocument();   
        $xmlArray = new App_Convert_XmlToArray();           // class aus AllgemeineDefinitionen, convert html/xml printable string to an array
        foreach ($links as $a) 
            {
            //$attribute=$a->getAttribute('@*');  if ($attribute != "") echo $attribute."\n";
            //echo $a->textContent,PHP_EOL;

            $style = $a->getAttribute($filterAttr);
            if ( ($filterValue=="") || (strpos($filterValue,"*")!==false) || ($style == $filterValue) )
                {
                $found=true;
                if (strpos($filterValue,"*")!==false)           // * als Einzelchar oder am Ende eines Suchstrings wie ein Filter
                    {
                    $pos = strpos($filterValue,"*");
                    //if ($pos>0) echo substr($style,0,$pos)." == ".substr($filterValue,0,$pos)."\n";
                    if ( ($pos==0) || (substr($style,0,$pos) == substr($filterValue,0,$pos)) )
                        {
                        // gefunden, andernfalls wird found doch wieder false
                        }
                    else $found=false;
                    }
                if ($found)
                    {
                    if ($debug) echo "   -> found:  ".$a->nodeName." , ".$a->nodeType." $style\n";
                    $ergebnis=["name"=>$a->nodeName,"attribute"=>$style];
                    $this->listfound[]=$ergebnis;
                    $result[$count] = $xmlArray->walkHtml($a,false);                                        // Ergebnis noch etwas unklar, wir sammeln mal
                    //if (($count)<$maxcount) if ($debug) print_R($result[($count)]);
                    $count++;
                    $tmp_doc->appendChild($tmp_doc->importNode($a,true));                 
                    //echo $innerHTML;
                    }
                }
            }
        $innerHTML .= $tmp_doc->saveHTML();                      
        return ($innerHTML);
        }

    /* den Style als Array rausziehen 
     * input ist ein html/xml string aus einem DOM Format erzeugt, wahrscheinlich von queryFilterHtml erzeugt
     * aus der Sammlung von Objekten jetzt die benötigten Einträge rausziehen
     * jetzt geht es auf die Attribute, sonst wäre ja kein innerhtml notwendig und wir könnten gleich den text verwenden, text hat aber keine xml Namen mehr dabei
     *
     * Beispiel von Selenium:
            $innerHtml = $this->queryFilterHtml($page,'//div[@*]','style',"width: 608px",$this->debug);         //  suche in der aktuellen Position nach div mit beliebigen Attributen
            $ergebnis = $this->extractFilterHtml($innerHtml,'//a[@*]','title',true);             // true für Debug            
            $this->analyseHtml($page,true);                 // true alles anzeigen
     *
     */

    function extractFilterHtml($textfeld,$xPathQuery,$filterAttr,$debug=false)
        {
        $result=array();
        if ($debug) echo "---- extractFilterHtml(...,$filterAttr)\n";
        $dom = new DOMDocument; 
        $dom->loadHTML($textfeld);
        $xpath = new DOMXpath($dom);
        $links = $xpath->query($xPathQuery);
        echo "Insgesamt ".count($links)." gefunden für \"$xPathQuery\"\n";
        foreach ($links as $a) 
            {
            //$attribute=$a->getAttribute('@*');  if ($attribute != "") echo $attribute."\n";
            //echo $a->textContent,PHP_EOL;
            $result[] = $a->getAttribute($filterAttr);
            }
        return ($result);
        }

    }       // end of class

/* class for javascript snippets
 */

class jsSnippets
    {

    /* ready to go
     * in case the document is already rendered, call ready with function to ensure data is available
     * try after each other
     *      document.readyState!='loading' for modern browsers
     *      document.addEventListener on DOMContentLoaded
     *      document.attachEvent on onreadystatechange, then document.readyState==complete
     */
    public function ready($innerfunction)
        {
        $html="";
        $html .= 'function ready(callback){                                     
                if (document.readyState!=\'loading\') callback();           
                else if (document.addEventListener) document.addEventListener(\'DOMContentLoaded\', callback);
                else document.attachEvent(\'onreadystatechange\', function(){
                    if (document.readyState==\'complete\') callback();
                    });
            }'."\n";
        $html .= 'ready(function(){ '."\n";                                                          // do something        
        $html .= $innerfunction;
        $html .= '   });  
                '; 
        return ($html);
        }


    }       // end of class

/**************************************************************************************************************************
 *
 * ipsTables, Zusammenfassung von Funktionen rund um die Darstellung von Tables mit html
 * Tabellen und auch andere Elemente werden als Tabellen dargestellt. Die Darstellung der Inhalten von arrays vereinheitlichen
 *
 *      __construct
 *      checkConfiguration
 *      checkDisplayConfig
 *      setConfiguration            Sort, Html, Header
 *
 *      getColumnsName
 *      analyseConfig
 *      processData
 *      insertHeader
 *      analyseData
 *      showTable
 *      
 */

class ipsTables
    {
    
    var $config = array();          // config, auch Teil der Class
    var $data   = array();

    function __construct($cfg=false)                    // config bei create class, danach mit setConfiguration oder erst mit der Funktion übergeben,  
        {
        $this->config = $this->checkConfiguration($cfg);                  // es wird die class config geändert
        }

    /* check/set Configuration, erster Parameter, es gibt auch noch display
     * analyseConfig wird vorangestellt um beide config parameter auseinander und wieder zusammenzuführen
     *
     *      sort
     *      html            true
     *      text            true, Ausgabe als echo
     *      header          foramtieren der headerrow
     *      display
     *      insert
     *      replace
     *      filter
     *      format
     *
     */

    protected function checkConfiguration($cfg,$debug=false)
        {
        $config = array();

        if (is_array($cfg)) $cfgInput = $cfg;
        else $cfgInput=array();                             // false oder anderer scalarer Wert

        // parse configuration, logInput ist der Input und config der angepasste Output
        configfileParser($cfgInput, $config, ["SORT","Sort","sort" ],"sort" ,false);                // nicht sortieren

        configfileParser($cfgInput, $config, ["HTML","Html","html" ],"html" ,false);                // darstellung als html, wenn true
        configfileParser($cfgInput, $config, ["TEXT","Text","text" ],"text" ,false);                // Ausgabe als text während der Bearbeitung, wenn true

        configfileParser($cfgInput, $config, ["HEADER","Header","header" ],"header" ,false);                // bei html eine headerrow formatieren
        configfileParser($cfgInput, $config, ["DISPLAY","Display","display" ],"display" ,null);                // display Einstellungen als Konfigurationsparameter
        configfileParser($cfgInput, $config, ["TRANSPOSE","Transpose","transpose" ],"transpose" ,false);                // Zeilen und Spalten vertauschen
        configfileParser($cfgInput, $config, ["REVERSE","Reverse","reverse" ],"reverse" ,false);                // Zeilen in die andere Richtung sortieren

        configfileParser($cfgInput, $config, ["INSERT","Insert","insert" ],"insert" ,null);                // bei html eine headerrow einfügen mit den Namen aus display
        configfileParser($cfgInput, $config, ["REPLACE","Replace","replace" ],"replace" ,null);                // bei html die erst zeile überschreiben mit den Namen aus display

        configfileParser($cfgInput, $config, ["FILTER","Filter","filter" ],"filter" ,null);                // bei html die erst zeile überschreiben mit den Namen aus display

        configfileParser($cfgInput, $config, ["FORMAT","Format","format" ],"format" ,null);                // bei html die erst zeile überschreiben mit den Namen aus display

        return($config);
        }


    /* check/set Display Configuration, 
     *
     * wir haben nur header, format
     *
     */

    function checkDisplayConfig($cfg,$debug=false)
        {
        $config = array();
        if ($debug) echo "checkDisplayConfig : ".json_encode($cfg)."\n";
        if (is_array($cfg)) $cfgInput = $cfg;
        else $cfgInput=array();                             // false oder anderer scalarer Wert
        foreach ($cfgInput as $columnName => $cfgWert)
            {
            if (is_array($cfgWert))
                {
                configfileParser($cfgWert, $config[$columnName], ["HEADER","Header","header" ],"header" ,"unknown");
                configfileParser($cfgWert, $config[$columnName], ["FORMAT","Format","format" ],"format" ,"");
                }
            else 
                {
                $config[$columnName]["header"]=$cfgWert;
                $config[$columnName]["format"]="";
                }
            }
        return($config);
        }

    /* set Configuration
     *
     */

    public function setConfiguration($cfg,$debug=false)
        {
        $this->config = $this->checkConfiguration($cfg,$debug);
        return($config);
        }

    /* Ausgabe ein array mit der Auflistung der Indexe, also aller verfügbaren Spalten
     * zur Orientierung und wenn keine Angabe als Defaultwerte 
     */ 

    function getColumnsName($inputData,$debug=false)
        {
        if ($debug) echo "getColumnsName:  ";
        $display=array();
        $rows=false; $rowNum=0;
        foreach ($inputData as $name => $item)          // name ist die Zeilenbezeichnung
            {
            //echo "    ($name==$rowNum)   ";             //wir erwarten uns eine geordneten Index mit 0,1,2,3,4
            if ($name==$rowNum) 
                {
                $rowNum++;
                }
            else 
                {
                //echo "ungleich";
                }
            if ($rows===false) 
                {
                $rows=1;
                foreach ($item as $index => $entry) 
                    {
                    if ($debug) echo " $index ";
                    //$display[$index]="";
                    $display[$index]=$index;                // Defaultanzeige ist nicht leer
                    }
                }
            else $rows++;
            }
        if ($debug) echo "\n";
        return ($display);
        }

    /* übersichtlicher machen, display, config auflösen um es nachher wieder auseinander zu führen
     */
    protected function analyseConfig($display, $config, $debug=false)
        {
        if (is_array($config)) $config = $this->checkConfiguration($config);
        else $config = $this->config;
        if (isset( $config["display"])) 
            {
            if ($debug) echo "Einstellung für Display übernehmen, override of ".json_encode($display)." \n";
            $display = $config["display"];
            }
        unset ($config["display"]); 
        $config["display"] = $this->checkDisplayConfig($display);
        if (isset($config["format"]["class-id"])===false)   $config["format"]["class-id"]=uniqid("a");           // make it short
        if (isset($config["format"]["header-id"])===false)  $config["format"]["header-id"]="hrow";          // make it short
        return ($config);
        }

    /* data is not always structured with line and column
     * convert different formats identified by name
     * put them already under the class
     */
    function convertData($processData,$model=false)
        {
        $this->data=array();
        foreach ($processData as $dataName => $entry)               // dataname is col name
            {
            foreach ($entry as $seriesId => $subentry)
                {
                // alignment of timestamped data is difficult, use getValues   
                }
            }
        }

    /* übersichtlicher machen, die Daten vorverarbeiten, config wird erweitert
     * Header noch nicht hinzufügen, eventuell entfernen
     */
    protected function processData($processData, &$config, $debug=false)
        {
        $inputData = array();
        $doheader=false;
        $replace=false;
        $row=0;
        $expectedIndex=0;
        if ( ( (isset($config["insert"]["Header"])) && ($config["insert"]["Header"]) ) || ( (isset($config["replace"]["Header"])) && ($config["replace"]["Header"]) ) )
            {
            foreach ($config["display"] as $index => $entry)            // noch nicht einfügen, sonst wird mitsortiert
                {
                //if ((is_array($entry)) && (isset($entry["header"]))) $inputData[$row][$index]=$entry["header"];
                //else $inputData[$row][$index]=$entry;
                }
            //$row++;
            $doheader=true;
            }
        if ( (isset($config["replace"]["Header"])) && ($config["replace"]["Header"]) ) { $replace=true; $expectedIndex=1; $doheader=true; }
        foreach ($processData as $index => $entry)
            {
            if ($replace==false)            // skip first row if replace, otherwise insert
                {
                $inputData[$row]=$entry;
                if ( (isset($config["insert"]["Index"])) && ($config["insert"]["Index"]) ) 
                    {
                    $inputData[$row]["Index"]=$index; 
                    $inputData[$row]["Name"]=IPS_GetName($index);
                    }
                else 
                    {
                    //compare index with row
                    if ($index!=$expectedIndex)
                        {
                        echo "    ($index!=$expectedIndex)   wir erwarten uns einen geordneten Index mit 0,1,2,3,4 \n";
                        return (false);
                        }
                    }
                $row++; 
                $expectedIndex++;  
                }
            else $replace=false;
            }

        $config["rows"]=$row;
        $config["doheader"]=$doheader;
        return ($inputData);
        }

    /* nach dem sortieren erst den Header hinzufügen
     */
    protected function insertHeader(&$inputData, &$config, $debug=false)
        {
        //if ($debug) echo "insertHeader: ".json_encode($config)."\n";
        $doheader=false;
        $inputHeader=array();
        if ( ( (isset($config["insert"]["Header"])) && ($config["insert"]["Header"]) ) || ( (isset($config["replace"]["Header"])) && ($config["replace"]["Header"]) ) )
            {
            foreach ($config["display"] as $index => $entry)
                {
                if ((is_array($entry)) && (isset($entry["header"]))) $inputHeader[$index]=$entry["header"];
                else $inputHeader[$index]=$entry;
                }
            $doheader=true;
            }
        if ($doheader) 
            {
            if ($debug) echo "insert header now:   \n";
            array_unshift($inputData,$inputHeader);             // inputheader am Anfang des arrays einfügen
            }
        return (true);
        }


    /* analyse data including header
     */
    protected function analyseData($inputData, &$config, $debug=false)
        {
        $analyseCols=array();
        $colMax=0;
        $displayWidth=array();
        $doheader  = $config["doheader"];
        foreach ($inputData as $row => $inputLine)                              // alle zeilen durchgehen
            {
            $col=0;
            foreach ($config["display"] as $name => $itemBox)                   // alle Spalten die mit Display angezeigt werden sollen durchgehen
                {
                // evaluate display width of each column, add 2 
                if (isset($displayWidth[$col])==false) $displayWidth[$col]=2;
                if (isset($inputLine[$name])) $len = strlen($inputLine[$name])+2;
                else 
                    {
                    if ($debug>1) { echo "analyseData $name not known:"; print_r($inputLine); }
                    $len=0;
                    }
                if ($len>$displayWidth[$col]) $displayWidth[$col]=$len; 
                // analyse list of entrie
                if ((is_array($itemBox)) && (isset($itemBox["header"]))) $colName=$itemBox["header"];
                else $colName=$itemBox;
                if ( (isset($inputLine[$name])) && ( ($doheader && $row>0) || ($doheader==0) ) )
                    {
                    $entry=$inputLine[$name];
                    if (isset($analyseCols[$colName][$entry]))
                        {
                        $analyseCols[$colName][$entry]++;
                        }
                    else $analyseCols[$colName][$entry]=1;
                    }
                $col++;
                }
            if ($col>$colMax) $colMax = $col;
            }
        $config["cols"]=$colMax;
        $config["analyse"]=$analyseCols;
        return ($displayWidth);
        }

    /* ipsTables::showTable
     * inputData ist das Array, display die Darstellung und Formatierung, config Zusatzkonfigurationen, debug für zusätzliche echos
     * display und config können weg gelassen werden, dann werden defaultwerte für die einzelnen Spalten angenommen
     *
     * display Einstellungen, ein index pro Spalte, wenn false/default wird ein leeres array erstellt und dieses mit den bestehenden Spalten Keys befüllt
     *  empty
     *  header
     *  format  dieser Eintrag wird 1:1 an nf weitergeleitet : 
     *
     * config Einstellungen
     *  sort            eventuell multisort mit mehreren Spalten
     *  html
     *  insert.header   Spaltenbezeichnung einführen
     *  display
     *  text
     *
     * verwendet getColumnsName, analyseConfig, processData, insertHeader, analyseData 
     *
     * Schwierig ist die css Formatierung mit <styles>
     * css Formatierungen können global auf eine class oder individuell auf eine einzigartige element id ausgerichtet sein
     *  <style> 
     *      #id table { font-family: "Trebuchet MS" }                       individuell für ein element, wahrscheinlich ein div
     *      p {  text-align: center;   color: red; }                        global für alle p s
     *      .center {  text-align: center; }                                für alle Elemente die der class center angehören
     *
     * Wenn ein ipsTable erstellt wird, wird automatisch eine unique id erzeugt, damit kann nur ein table gezeichnet werden
     * wenn ein style für mehrere tables verwendet werden soll, dann muss die Formatierung von der eigentlichen Erstellung der Tabelle abgetrennt werden
     * ein übergeordnetes div mit einer unqeid definiert eine container struktur und wird für mehrere Tabellen verwendet
     *
     * analyseConfig kennt folgende Parameter
     *          class-id ist irreführend, soll eine uniqid darstellen, ist immer gesetzt, ausser mit false rauskonfiguriert
     *          dann wird ein style in das html eingefügt
     *          sonst wird kein style definiert, es gilt
     *          wenn reuse-styleid gesetzt ist würde auf diese verwiesen werden, das bedeutet der style mit der id ist an anderer Stelle definiert, muss aber denoch unique sein
     *          oder wenn diese auch nicht wird ein normales div eingetragen
     * auskommentiert sind noch class Definitionen möglich
     *
     * einfacher wäre es das ganze zwischen zwei html /html tags zu setzen. Ein html Block ist das Root für ein DOMs
     *
     * mehrere Tabellen als flex box
     *
		.maindiv1 { width: 100%; height: 100%;          // div darunter übereinander rechtsbündig darstellen, das sind maindiv2 und menueleiste
		  display: flex;  flex-direction: column; flex-wrap: nowrap;        
		  justify-content: space-between;
		  align-items: flex-start; align-content: flex-start;
		  box-sizing: border-box; padding: 0px 0px 0px 0px;
		}
        .maindiv1>* { position: relative;	z-index: 1;     // zusaetzliche Formatierung für die divs unter maindiv1 : 
		}
		.maindiv2 { width: 100%; height: 90%;           // div darunter für left-aligned, center-aligned und right aligned
		  display: flex; position: relative; 
		  justify-content: space-between;
		  align-items: center;
		  box-sizing: border-box;
		}
     <div id=a1234 class="maindiv1">
        <div class="maindiv2">
            <div class="left-aligned">
                <div class="left-aligned2" style="align-items: flex-start">
                <p id="debuginfo" style="color:white;">Test</p>
                </div>
                <div class="left-aligned2" style="align-items: center">
                </div>
            </div>
            <div class="center-aligned">
            
            </div>
            <div class="right-aligned">
                <div  class="right-aligned2" style="align-items: center">
                </div>
                <div  class="right-aligned2" style="align-items: flex-end">
                </div> 
            </div>
        </div>
        <div class="hidden" id="menueleiste">
            <div class="menueleistelinks">
            </div>
            <div class="menueleisterechts">
            </div>	
        </div>
    </div> 
     
     
     *
     */


    function showTable($processData,$display=false,&$config=false, $debug=false)
        {
        
        $text="";

        $ipsOps = new ipsOps();

        if ($display===false) $display=array();
        if (sizeof($display)==0) $display = $this->getColumnsName($processData,$debug);           // ein leeres display abändern dass alle Spalten angezeigt werden

        $config = $this->analyseConfig($display, $config, $debug);

        if ($debug) echo "showTable: ".json_encode($config)."\n";

        // process Data
        $inputData = $this->processData($processData, $config, $debug);             // config wird erweitert, die berabeiteten Daten retourniert
        if ($inputData===false) return (false);

        // sort Data
        //print_R($inputData);
        $dir=SORT_ASC;
        if (is_array($config["sort"]))
            {
            $sort=$config["sort"]["column"];
            $dir=$config["sort"]["direction"];    
            }
        else $sort=$config["sort"];
        $ipsOps->intelliSort($inputData, $sort,$dir,$debug);                   // $sort=SORT_ASC
        if ($config["reverse"]) krsort($inputData);

        $this->insertHeader($inputData, $config, $debug);

        // diese Konfigurationen werden übernommen, Pointer übernehmen
        $rows      = $config["rows"];
        $doheader  = $config["doheader"];
        $html      = $config["html"];
        $dotext    = $config["text"];
        $display   = $config["display"];
        $id        = $config["format"]["class-id"];
        $hid       = $config["format"]["header-id"];
        $transpose = $config["transpose"];

        $displayWidth=$this->analyseData($inputData, $config, $debug);       // Daten Anzahl Spalten und Breite bestimmen

        if (is_array($display))         // ist immer gesetzt, wenn nicht vorerst keine Funktion, zuerst filter
            {
            //print_R($config);
            $filter=false;
            if (isset($config["filter"])) 
                { 
                $filter=array();
                echo "Config Filter gesetzt :  ".json_encode($config["filter"])." jetzt anpassen für ".json_encode($display)."\n"; 
                $col=0;
                foreach ($config["display"] as $index => $entry)
                    {
                    //if ((is_array($entry)) && (isset($entry["header"]))) $inputHeader[$index]=$entry["header"];
                    //else $inputHeader[$index]=$entry;
                    if ((is_array($entry)) && (isset($entry["header"]))) $inputHeader[$col]=$entry["header"];
                    else $inputHeader[$col]=$entry;
                    $col++;
                    }
                foreach ($inputHeader as $index=>$entry)
                    {
                    if (isset($config["filter"][$entry])) $filter[$index] = $config["filter"][$entry];
                    }
                echo "Config Filter umgewandelt in :  ".json_encode($filter)."\n";
                }

            if ($debug)  echo "showTable Detail of Columns ".json_encode($display)."\n";
            if ($rows>0)          // multi array, nicht nur eine zeile, ein Array mit header darstellen
                {
                if ($debug) echo "    ".$rows." Reihen erkannt.\n"; 
                $displayOutput=array();
                $line=0;
                // macht aus inputData[row][col] displayOutput[line][col]
                foreach ($inputData as $row => $inputLine)
                    {
                    if ($debug>1) echo "Process $row ".json_encode($inputLine)."\n";
                    //print_R($inputLine);
                    if ($row==0)                                                // die erste Zeile ist der Header der Tabelle
                        {
                        if ($debug>1) echo "Header/First Row : ".($transpose?"Transpose":"")."\n";
                        $col=0;
                        foreach ($display as $name => $itemBox)
                            {
                            if ($transpose) 
                                {
                                if (is_array($itemBox)) $item=$itemBox["format"];
                                else $item=$itemBox;
                                if ((isset($inputLine["currency"])) && ($item=="<currency>")) $item=$inputLine["currency"];              // die Währung wird sich aus der gleichen tabelle aus einer anderen Spalte geholt

                                if (isset($inputLine[$name]))                                       // schauen ob es diese Zelle auch wirklich gibt, sonst leer ausgeben
                                    {
                                    if ($doheader) $input = $inputLine[$name]; 
                                    else $input = nf($inputLine[$name],$item)." ";              //nf übernimmt die Formatierung
                                    }
                                else $input = "";
                                $displayOutput[$col][$line]=$input;      // Zeilen werden Spalten
                                }
                            else 
                                {
                                if (isset($inputLine[$name])) $input=$inputLine[$name];
                                else $input="";
                                $displayOutput[$line][$col]=$input;
                                }
                            if ($dotext) echo str_pad($input,$displayWidth[$col]);
                            $col++;
                            }
                        $line++;
                        if ($debug>1) print_r($displayOutput);
                        if ($dotext) echo "\n";
                        }
                    else
                        {
                        $col=0;
                        if ($debug>1) echo "Continous Row :\n";
                        //print_r($display);
                        $displayOutputLine=array();
                        foreach ($display as $name => $itemBox)                    // die anderen Zeilen werden entsprechend display vorverarbeitet
                            {
                            if (is_array($itemBox)) $item=$itemBox["format"];
                            else $item=$itemBox;
                            if ((isset($inputLine["currency"])) && ($item=="<currency>")) $item=$inputLine["currency"];              // die Währung wird sich aus der gleichen tabelle aus einer anderen Spalte geholt

                            if (isset($inputLine[$name]))                                       // schauen ob es diese Zelle auch wirklich gibt, sonst leer ausgeben
                                {
                                $input = nf($inputLine[$name],$item)." ";              //nf übernimmt die Formatierung
                                }
                            else $input = "";
                            if ($transpose)  $displayOutput[$col][$line]=$input;
                            $displayOutputLine[$col]=$input;
                            if ($dotext) echo str_pad(nf($input,$item)." ",$displayWidth[$col]);
                            $col++;
                            }
                        if ($transpose===false)
                            {
                            if ($filter)
                                {
                                //echo "filter";
                                $found=false;
                                foreach ($filter as $column => $value)
                                    {
                                    if (isset($displayOutputLine[$column]))
                                        {
                                        $pos = strpos($displayOutputLine[$column],$value);    
                                        if ($pos) $found=true;
                                        //echo $pos;
                                        }
                                    }
                                if ($found) $displayOutput[$line]=$displayOutputLine;
                                //echo "Config Filter gesetzt";
                                }
                            else $displayOutput[$line]=$displayOutputLine;
                            }
                        $line++;
                        if (($debug>1) && ($row==1)) print_r($displayOutput);
                        if ($dotext) echo "\n";  
                        }
                    }
                //print_R($displayOutput);      // fertig mit Zeilen und Spalten, nur mehr ausgeben

                if ($html)              // Textausgabe als html Tabelle, displayOutput ist die bereits vorverarbeitete Quelle der Ausgabe
                    {
                    $size=-1;                   //responsive
                    $text=""; 
                    //if (false)          // mit einzigartiger id
                        {
                        if ($id)
                            {
                            $text .= "<style>";
                            $text.='#'.$id.' table { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; ';
                            if ($size==0) $text.='font-size: 100%; width: 100%;';
                            elseif ($size==-1) $text.='font-size:50%vw; max-width: 900px ';        // responsive font size
                            else $text.='font-size: 150%; width: 100%;';
                            $text.='color:black; border-collapse: collapse;  }';
                            //$wert .= '<font size="1" face="Courier New" >';
                            $text.='#'.$id.' td, #customers th { border: 1px solid #ddd; padding: 8px; }';
                            $text.='#'.$id.' tr:nth-child(even){background-color: #f2f2f2;color:black;}';
                            $text.='#'.$id.' tr:nth-child(odd){background-color: #e2e2e2;color:black;}';
                            $text.='#'.$id.' tr:hover {background-color: #ddd;}';
                            $text.='#'.$id.' th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; word-wrap: break-word; white-space: normal;}';
                            $text.="</style>";
                            $text .= '<div id="'.$id.'">';
                            }
                        else
                            {
                            if (isset($config["format"]["reuse-styleid"]))
                                {
                                $text .= '<div id="'.$config["format"]["reuse-styleid"].'">';
                                }
                            else $text .= '<div>';           //gar keine verwenden
                            }
                        $text .= '<table class="sortierbar">';
                        }
                    if (false)          // mit class, mehrfach verwendbar, aber nur einmal definierbar, im Skin css
                        {                        
                        $text .= '<style>';
                        $text .= 'table.quicky { border:solid 5px #006CFF; margin:0px; padding:0px; border-spacing:0px; border-collapse:collapse; line-height:22px; font-size:13px;'; 
                        $text .= ' font-family:Arial, Verdana, Tahoma, Helvetica, sans-serif; font-weight:400; text-decoration:none; color:#0018ff; white-space:pre-wrap; }';
                        $text .= 'table.quicky th { padding: 2px; background-color:#98dcff; border:solid 2px #006CFF; }';
                        $text .= 'table.quicky td { padding: 2px; border:solid 1px #006CFF; }';
                        $text .= 'table.custom_class tr { margin:0; padding:4px; }';
                        $text .= '.quicky.green {border:solid green; color:green;}';
                        $text .= '';
                        $text .= '</style>';
                        $text .= '<div class="quicky">';
                        $text .= '<table class="sortierbar">';                        
                        }
                    if (false)          // mit class aber selber formatierung
                        {
                        $text .= "<style>";
                        $text .= 'table.'.$id.' { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; ';
                        if ($size==0) $text.='font-size: 100%; width: 100%;';
                        elseif ($size==-1) $text.='font-size:50%vw; max-width: 900px ';        // responsive font size
                        else $text.='font-size: 150%; width: 100%;';
                        $text.='color:black; border-collapse: collapse;  }';
                        //$wert .= '<font size="1" face="Courier New" >';
                        $text .= ' td.'.$id.', #customers th { border: 1px solid #ddd; padding: 8px; }';
                        $text .= ' tr.'.$id.':nth-child(even){background-color: #f2f2f2;color:black;}';
                        $text .= ' tr.'.$id.':nth-child(odd){background-color: #e2e2e2;color:black;}';
                        $text .= ' tr.'.$id.':hover {background-color: #ddd;}';
                        $text .= ' th.'.$id.' { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; word-wrap: break-word; white-space: normal;}';
                        $text .= "</style>";
                        $text .= '<div class="'.$id.'">';
                        $text .= '<table class="sortierbar">';
                        }                        
                    foreach ($displayOutput as $line => $row)
                        {
                        if ($doheader) 
                            {
                            if ($line==0) $text .= '<thead><tr>';
                            if ($line==1) $text .= '<tbody><tr>';
                            }
                        else $text .= '<tr>';
                        foreach ($row as $col => $item)
                            {
                            if ( ($doheader) && ($line==0) )
                                {
                                $text .= '<th id="'.$hid.'-'.$col.'">';
                                $text .= $item;
                                $text .= '</th>'; 
                                }
                            else 
                                {
                                $text .= '<td>';
                                $text .= $item;
                                $text .= '</td>';
                                }
                            }
                        if ( ($doheader) && ($line==0) ) $text .= '</thead></tr>';
                        else $text .= '<tr>';
                        }
                    $text .= '</tbody></table>';
                    $text .= '</div>';
                    if ($debug) echo $text;
                    }
                else            // Textausgabe als echo
                    {
                    if ($debug) echo "Ausgabe als echo:\n";
                    foreach ($displayOutput as $line => $row)
                        {
                        //echo "|";
                        foreach ($row as $col => $item)
                            {
                            echo "|";
                            echo str_pad($item,$displayWidth[$col]);
                            }
                        echo "\n";
                        }
                    }
                }
            else
                {
                echo "Struktur der Daten nicht erkannt.\n";
                }  
            }
        else
            {
            echo "No display, scalar Parameter for display received\n";
            }
        return ($text);
        }

    }       // ende class ipsTable

/**************************************************************************************************************************
 *
 * ipsCharts, Zusammenfassung von Funktionen rund um die Darstellung von Charts mit Highchart
 * verwendet die class Highcharts, zusätzliche Abstrahierung, ähnlich wie die Widgets in Startpage
 * und das script IPSHighchart
 * baut schon eine nette Tabelle rund um das Chart
 *
 *      __construct
 *      createChart
 *      createChartFromArray
 *
 *
 *
 */

class ipsCharts
    {

    protected $scriptHighchartsID;                      // für Higcharts, die IPSHighcharts script ID, can be false

    public function __construct()
        {
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        $moduleManager = new IPSModuleManager('IPSHighcharts',"");
        $categoryHighchartsID = $moduleManager->GetModuleCategoryID('app');	
        $this->scriptHighchartsID = @IPS_GetScriptIDByName("IPSHighcharts", $categoryHighchartsID);
        }

    /* createChart 
     * Highcharts class übernimmt die config Daten
     * holt die Daten aus dem Archiv
     * macht lauter kleine Tabellenzellen mit den letzten Werten je OID in der Config
     * sehr praktisch für eine einfache Vorwertedarstellung zB in Station von Startpage
     *
     */

    public function createChart($chartID,$specialConf,$debug=false)
        {
            $wert = "";
            $wert .= "<table><tr>";
            if ($this->scriptHighchartsID>100)      // ohne Script gehts nicht */
                {
                foreach ($specialConf as $indexChart => $config)
                    {
                    if ($debug) echo "showSpecialRegsWidget: Highcharts Ausgabe von $indexChart (".json_encode($config).") : \n"; 

                    $endTime=time();
                    $startTime=$endTime-$config["Duration"];     /* drei Tage ist Default */
                    $chart_style=$config["Style"];            // line spline area gauge            gauge benötigt eine andere Formatierung

                    // Create Chart with Config File
                    // IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");               // ohne class, needs Charts
                    IPSUtils_Include ('Report_class.php', 					'IPSLibrary::app::modules::Report');

                    $CfgDaten=array();
                    //$CfgDaten['HighChartScriptId']= IPS_GetScriptIDByName("HC", $_IPS['SELF'])
                    //$CfgDaten["HighChartScriptId"]  = 11712;                  // ID des Highcharts Scripts
                    $CfgDaten["HighChartScriptId"]  = $this->scriptHighchartsID;                  // ID des Highcharts Scripts          *******************************

                    $CfgDaten["ArchiveHandlerId"]   = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                    $CfgDaten['ContentVarableId']   = $chartID;                                                                                   //****************************                          
                    $CfgDaten['HighChart']['Theme'] ="ips.js";   // IPS-Theme muss per Hand in in Themes kopiert werden....
                    $CfgDaten['StartTime']          = $startTime;
                    $CfgDaten['EndTime']            = $endTime;

                    $CfgDaten['Ips']['ChartType']   = 'Highcharts';           // Highcharts oder Highstock default = Highcharts
                    $CfgDaten['RunMode']            = "file";     // file nur statisch über .tmp,     script, popup  ist interaktiv und flexibler
                    $CfgDaten["File"]               = true;        // Übergabe als File oder ScriptID

                    // Abmessungen des erzeugten Charts
                    $CfgDaten['HighChart']['Width'] = 0;             // in px,  0 = 100%
                    $CfgDaten['HighChart']['Height'] = 300;         // in px, keine Angabe in Prozent möglich
                    
                    $CfgDaten['title']['text']      = "";                           // weglassen braucht zuviel Platz
                    //$CfgDaten['subtitle']['text']   = "great subtitle";         // hioer steht der Zeitraum, default als Datum zu Datum Angabe
                    $CfgDaten['subtitle']['text']   = "Zeitraum ".nf($config["Duration"],"s");         // hier steht nmormalerweise der Zeitraum, default als Datum zu Datum Angabe
                    
                    //$CfgDaten["PlotType"]= "Gauge"; 
                    $CfgDaten['plotOptions']['spline']['color']     =	 '#FF0000';
                    $CfgDaten['plotOptions']['area']['stacking']     =	 'normal';

                    if ($config["Aggregate"]) $CfgDaten['AggregatedValues']['HourValues']     = 0; 
                    //if ($config["Step"]) $CfgDaten['plotOptions'][$chart_style]['step']     =	 $config["Step"];               // false oder left , in dieser Highcharts Version noch nicht unterstützt

                    $CfgDaten['plotOptions']['series']['connectNulls'] = true;                      // normalerweise sind Nullen unterbrochene Linien, es wird nicht zwischen null und 0 unterschieden
                    $CfgDaten['plotOptions']['series']['cursor'] = "pointer";

                    /* floating legend
                    $CfgDaten['legend']['floating']      = true;                   
                    $CfgDaten['legend']['align']         = 'left';
                    $CfgDaten['legend']['verticalAlign'] = 'top';
                    $CfgDaten['legend']['x']             = 100;
                    $CfgDaten['legend']['y']             = 70;  */

                    $CfgDaten['tooltip']['enabled']             = true;
                    $CfgDaten['tooltip']['crosshairs']             = [true, true];                  // um sicherzugehen dass es ein mouseover gibt
                    //$CfgDaten['tooltip']['shared']             = true;                        // nur für Tablets, braucht update

                    $CfgDaten['chart']['type']      = $chart_style;                                     // neue Art der definition
                    $CfgDaten['chart']['backgroundColor']   = $config["backgroundColor"];                // helles Gelb ist Default

                    foreach($config["OID"] as $index => $oid)
                        {
                        $serie = array();
                        $serie['type']                  = $chart_style;                 // muss enthalten sein
                        if ($config["Step"]) $serie['step'] = $config["Step"];                // false oder left

                        /* wenn Werte für die Serie aus der geloggten Variable kommen : */
                        if (isset($config["Name"][$index])) $serie['name'] = $config["Name"][$index];
                        else $serie['name'] = $config["Name"][0];
                        //$serie['marker']['enabled'] = false;                  // keine Marker
                        $serie['Unit'] = $config["Unit"];                            // sieht man wenn man auf die Linie geht
                        $serie['Id'] = $oid;
                        //$serie['Id'] = 28664 ;
                        $CfgDaten['series'][] = $serie;
                        }
                    $highCharts = new HighCharts();
                    $CfgDaten    = $highCharts->CheckCfgDaten($CfgDaten);
                    $sConfig     = $highCharts->CreateConfigString($CfgDaten);
                    $tmpFilename = $highCharts->CreateConfigFile($sConfig, "WidgetGraph_$indexChart");
                    if ($tmpFilename != "")
                        {
                        if ($debug) echo "Ausgabe Highcharts:\n";
                        $chartType = $CfgDaten['Ips']['ChartType'];
                        $height = $CfgDaten['HighChart']['Height'] + 16;   // Prozentangaben funktionieren nicht so richtig,wird an verschiedenen Stellen verwendet, iFrame muss fast gleich gross sein
                        $callBy="CfgFile";
                        if (is_array($config["Size"]))          // Defaultwert
                            {
                            $wert .= '<td>';                                
                            $wert .= "<iframe src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy="	. $tmpFilename . "' " ."width='%' height='". $height ."' frameborder='0' scrolling='no'></iframe>";                        
                            }
                        elseif (strpos($config["Size"],"x")) 
                            {
                            $multiplier=(integer)substr($config["Size"],0,strpos($config["Size"],"x"));
                            $widthInteger=$CfgDaten['HighChart']['Height']*$multiplier;
                            // Height wird wirklich so übernommen, nur mehr 316px hoch
                            $width=$widthInteger."px";
                            //echo "Neue Width ist jetzt ".$CfgDaten['HighChart']['Height']."*$multiplier=$width.\n";
                            //$height='700px';                            
                            $wert .= '<td style="width:'.$width.'px;height:'.$height.'px;background-color:#3f1f1f">';           // width:100%;height:500px; funktioniert nicht, ist zu schmal
                            //$width="100%";
                            //$width="auto"; 
                            //$height="auto";
                            //$height="100%"; 


                            $wert .= '<iframe style="width:'.$width.';height:'.$height.'"'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."' height='".$height."' frameborder='0' scrolling='no'></iframe>";                        
                            //$wert .= '<iframe style="height:'.$height.'"'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."' frameborder='0' scrolling='no'></iframe>";                        
                            //$wert .= '<iframe'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."'></iframe>";                        
                            }
                        else 
                            {
                            //print_R($config["Size"]);
                            $wert .= '<td???>';                                
                            $wert .= "<iframe src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy="	. $tmpFilename . "' " ."width='%' height='". $height ."' frameborder='0' scrolling='no'></iframe>";                        
                            }
                        
                        //$wert .= $tmpFilename;
                        }
                    $wert .= "</td>";
                    }
                }
            $wert .= "</tr></table>";

        return ($wert);
        }


    /* createChartfrom Array 
     * Highcharts class übernimmt die config Daten
     * Darstellung in einem Table,string zur Darstellung als return, 
     * benötigt die scriptHighchartsID und einen Index unter dem der Graph als Datei abgespeichert wird
     * zeichnet mehrere Highcharts nebeinander in einer Tabelle
     *
     *  data wird aus dem array übernommen, werte alle
     *      Value
     *      TimeStamp
     *
     * specialConf
     *      indexChart                      Name des Charts 
     *          Duration
     *          Style
     *          backgroundColor
     *          step
     *          unit
     *          OID
     *              Index                   mehrere Kurven in einem Plot
     *          Name        
     *              Index                   parallel mit gleichem Index ein passender Name dazu
     *
     * gibt eine html Tabelle aus
     *
     */

    public function createChartFromArray($chartID,$specialConf,&$result,$debug=false)
        {
            $wert = "";
            $wert .= "<table><tr>";
            if ($this->scriptHighchartsID>100)      // ohne Script gehts nicht */
                {
                /* die Datenpunkte einzeln pro indexChart speichern */
                foreach ($specialConf as $indexChart => $config)
                    {
                    if ($debug) echo "showSpecialRegsWidget: Highcharts Ausgabe von $indexChart (".json_encode($config).") : \n"; 
                    if (isset($config["Index"])) $resultseries=$result[$config["Index"]];
                    else $resultseries=$result;
                    if (isset($config["StartTime"]))
                        {
                        $startTime=strtotime($config["StartTime"]);
                        $endTime=$startTime+$config["Duration"];    
                        }
                    else
                        {
                        $endTime=time();
                        $startTime=$endTime-$config["Duration"];     /* drei Tage ist Default */
                        }
                    if ($debug) echo "Starttime ist ".date("d.m.Y H:i",$startTime)." Endtime ist ".date("d.m.Y H:i",$endTime)."\n";
                    $chart_style=$config["Style"];            // line spline area gauge            gauge benötigt eine andere Formatierung

                    // Create Chart with Config File
                    // IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");               // ohne class, needs Charts
                    IPSUtils_Include ('Report_class.php', 					'IPSLibrary::app::modules::Report');

                    $CfgDaten=array();
                    //$CfgDaten['HighChartScriptId']= IPS_GetScriptIDByName("HC", $_IPS['SELF'])
                    //$CfgDaten["HighChartScriptId"]  = 11712;                  // ID des Highcharts Scripts
                    $CfgDaten["HighChartScriptId"]  = $this->scriptHighchartsID;                  // ID des Highcharts Scripts          *******************************

                    $CfgDaten["ArchiveHandlerId"]   = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                    $CfgDaten['ContentVarableId']   = $chartID;                                                                                   //****************************                          
                    $CfgDaten['HighChart']['Theme'] ="ips.js";   // IPS-Theme muss per Hand in Themes kopiert werden....
                    $CfgDaten['StartTime']          = $startTime;
                    $CfgDaten['EndTime']            = $endTime;

                    $CfgDaten['Ips']['ChartType']   = 'Highcharts';           // Highcharts oder Highstock default = Highcharts
                    $CfgDaten['RunMode']            = "file";     // file nur statisch über .tmp,     script, popup  ist interaktiv und flexibler
                    $CfgDaten["File"]               = true;        // Übergabe als File oder ScriptID

                    // Abmessungen des erzeugten Charts
                    $CfgDaten['HighChart']['Width'] = 0;             // in px,  0 = 100%
                    $CfgDaten['HighChart']['Height'] = 300;         // in px, keine Angabe in Prozent möglich
                    
                    $CfgDaten['title']['text']      = "";                           // weglassen braucht zuviel Platz
                    //$CfgDaten['subtitle']['text']   = "great subtitle";         // hier steht der Zeitraum, default als Datum zu Datum Angabe
                    $CfgDaten['subtitle']['text']   = "Zeitraum ".nf($config["Duration"],"s");         // hier steht nmormalerweise der Zeitraum, default als Datum zu Datum Angabe
                    
                    //$CfgDaten["PlotType"]= "Gauge"; 
                    $CfgDaten['plotOptions']['spline']['color']     =	 '#FF0000';
                    $CfgDaten['plotOptions']['area']['stacking']     =	 'normal';

                    if ($config["Aggregate"]) $CfgDaten['AggregatedValues']['HourValues']     = 0; 
                    //if ($config["Step"]) $CfgDaten['plotOptions'][$chart_style]['step']     =	 $config["Step"];               // false oder left , in dieser Highcharts Version noch nicht unterstützt

                    $CfgDaten['plotOptions']['series']['connectNulls'] = true;                      // normalerweise sind Nullen unterbrochene Linien, es wird nicht zwischen null und 0 unterschieden
                    $CfgDaten['plotOptions']['series']['cursor'] = "pointer";

                    /* floating legend
                    $CfgDaten['legend']['floating']      = true;                   
                    $CfgDaten['legend']['align']         = 'left';
                    $CfgDaten['legend']['verticalAlign'] = 'top';
                    $CfgDaten['legend']['x']             = 100;
                    $CfgDaten['legend']['y']             = 70;  */

                    $CfgDaten['tooltip']['enabled']             = true;
                    $CfgDaten['tooltip']['crosshairs']             = [true, true];                  // um sicherzugehen dass es ein mouseover gibt
                    //$CfgDaten['tooltip']['shared']             = true;                        // nur für Tablets, braucht update

                    $CfgDaten['chart']['type']      = $chart_style;                                     // neue Art der definition
                    $CfgDaten['chart']['backgroundColor']   = $config["backgroundColor"];                // helles Gelb ist Default
                    

                    if (isset($config["Series"]))                           // createChartFromArray
                        {
                        foreach($config["Series"] as $indexSeries => $configSerie)          // mehrere Kurven übereinander zeichnen
                            {
                            if (!( (isset($configSerie["Enable"])) && ($configSerie["Enable"]===false)  ))              // nicht aktiv disabled
                                {
                                if  (isset($configSerie["MapData"]))                // Die Daten in mehreren Serien übereinander zeichnen
                                    {
                                    if (isset($configSerie["Index"]))  
                                        {
                                        $minYear=9999;
                                        foreach ($resultseries[$configSerie["Index"]] as $entry)
                                            {
                                            $currentYear=(integer)date("Y",$entry["TimeStamp"]);   
                                            if ($currentYear<$minYear) $minYear=$currentYear;
                                            }
                                        switch ($configSerie["MapData"])
                                            {
                                            case "Year":            
                                                // Index ist 0 bis 11 für die Monate
                                                break;
                                            }
                                        $scale=1;                            
                                        for ($i=0;($i<(2025-$minYear));$i++) 
                                            {
                                            //$CfgDaten['series'][$i]['type']                  = $chart_style;                 // muss enthalten sein
                                            $CfgDaten['series'][$i]['type']                  = 'line';                 // muss enthalten sein
                                            if (isset($configSerie["Step"])) $CfgDaten['series'][$i]['step'] = $configSerie["Step"];                // false oder left
                                            if (isset($configSerie["Name"])) $CfgDaten['series'][$i]['name'] = $configSerie["Name"].($i+1);
                                            else $serie['name'] = $indexSeries.($i+1);
                                            if (isset($configSerie["Unit"])) $CfgDaten['series'][$i]['Unit'] = $configSerie["Unit"]; 
                                            $CfgDaten['series'][$i]['step']      = 'right';
                                            $CfgDaten['series'][$i]['marker']['enabled'] = false;                  // keine Marker, Punkte auf der Linie
                                            }
                                        foreach ($resultseries[$configSerie["Index"]] as $entry)                        // copy data
                                            {
                                            //if ($scale==0) $scale=100/$entry["Value"];
                                            $timestamp=$entry["TimeStamp"];
                                            $indexOfMonth=(integer)date("Y",$timestamp)-$minYear; 
                                            $timestamp = strtotime(date("d.m",$timestamp).".$minYear");
                                            if ($debug) echo "      ".str_pad($indexOfMonth,3)." ".date("d.m.Y H:i:s",$timestamp)."  ".round($entry["Value"]*$scale,1)."\n";
                                            $CfgDaten['series'][$indexOfMonth]['data'][] = ["TimeStamp" =>  $timestamp,"y" => (float)(round(($entry["Value"]*$scale),1))];
                                            }
                                        }
                                    }
                                else    
                                    {
                                    $scale=1;                            
                                    $serie = array();
                                    $serie['type']                  = $chart_style;                 // muss enthalten sein
                                    if (isset($configSerie["Step"])) $serie['step'] = $configSerie["Step"];                // false oder left
                                    if (isset($configSerie["Name"])) $serie['name'] = $configSerie["Name"];
                                    else $serie['name'] = $indexSeries;
                                    if (isset($configSerie["Unit"])) $serie['Unit'] = $configSerie["Unit"]; 
                                    //$serie['type']      = 'line';
                                    $serie['step']      = 'right';
                                    $serie['marker']['enabled'] = false;                  // keine Marker, Punkte auf der Linie
                                    if (isset($configSerie["Index"]))
                                        {
                                        foreach ($resultseries[$configSerie["Index"]] as $entry)                        // copy data
                                            {
                                            //if ($scale==0) $scale=100/$entry["Value"];
                                            $timestamp=$entry["TimeStamp"];
                                            if ($timestamp>$startTime)
                                                {
                                                if ($debug) echo "    :  ".date("d.m.Y H:i:s",$entry["TimeStamp"])."  ".round($entry["Value"]*$scale,1)."\n";
                                                $serie['data'][] = ["TimeStamp" =>  $entry["TimeStamp"],"y" => (float)(round(($entry["Value"]*$scale),1))];
                                                }
                                            }
                                        }
                                    $CfgDaten['series'][] = $serie;                                         
                                    } 
                                }
                            }                      
                        }
                    else
                        {
                        $scale=1;
                        $serie = array();                        
                        $serie['Unit'] = $config["Unit"]; 
                        $serie['type']      = 'line';
                        $serie['step']      = 'right';
                        $serie['marker']['enabled'] = false;                  // keine Marker, Punkte auf der Linie
                        if (isset($config["Name"])) $serie['name'] = $config["Name"]; else $serie['name'] = $indexChart;
                        foreach ($resultseries as $entry)
                            {
                            //if ($scale==0) $scale=100/$entry["Value"];
                            if ($debug) echo "      ".date("d.m H:i:s",$entry["TimeStamp"])."  ".round($entry["Value"]*$scale,0)."\n";
                            $serie['data'][] = ["TimeStamp" =>  $entry["TimeStamp"],"y" => (float)(round(($entry["Value"]*$scale),0))];
                            }
                        $CfgDaten['series'][] = $serie;
                        }
                    $highCharts = new HighCharts();
                    $CfgDaten    = $highCharts->CheckCfgDaten($CfgDaten);
                    $sConfig     = $highCharts->CreateConfigString($CfgDaten);
                    $tmpFilename = $highCharts->CreateConfigFile($sConfig, "WidgetGraph_$indexChart");
                    if ($tmpFilename != "")
                        {
                        if ($debug) echo "Ausgabe Highcharts in File WidgetGraph_$indexChart :\n";
                        $chartType = $CfgDaten['Ips']['ChartType'];
                        $height = $CfgDaten['HighChart']['Height'] + 16;   // Prozentangaben funktionieren nicht so richtig,wird an verschiedenen Stellen verwendet, iFrame muss fast gleich gross sein
                        $callBy="CfgFile";
                        if (is_array($config["Size"]))          // Defaultwert
                            {
                            $wert .= '<td>';                                
                            $wert .= "<iframe src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy="	. $tmpFilename . "' " ."width='%' height='". $height ."' frameborder='0' scrolling='no'></iframe>";                        
                            }
                        elseif (strpos($config["Size"],"x")) 
                            {
                            $multiplier=(integer)substr($config["Size"],0,strpos($config["Size"],"x"));
                            $widthInteger=$CfgDaten['HighChart']['Height']*$multiplier;
                            // Height wird wirklich so übernommen, nur mehr 316px hoch
                            $width=$widthInteger."px";
                            //echo "Neue Width ist jetzt ".$CfgDaten['HighChart']['Height']."*$multiplier=$width.\n";
                            //$height='700px';                            
                            $wert .= '<td style="width:'.$width.'px;height:'.$height.'px;background-color:#3f1f1f">';           // width:100%;height:500px; funktioniert nicht, ist zu schmal
                            //$width="100%";
                            //$width="auto"; 
                            //$height="auto";
                            //$height="100%"; 


                            $wert .= '<iframe style="width:'.$width.';height:'.$height.'"'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."' height='".$height."' frameborder='0' scrolling='no'></iframe>";                        
                            //$wert .= '<iframe style="height:'.$height.'"'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."' frameborder='0' scrolling='no'></iframe>";                        
                            //$wert .= '<iframe'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."'></iframe>";                        
                            }
                        else 
                            {
                            //print_R($config["Size"]);
                            $wert .= '<td???>';                                
                            $wert .= "<iframe src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy="	. $tmpFilename . "' " ."width='%' height='". $height ."' frameborder='0' scrolling='no'></iframe>";                        
                            }
                        
                        //$wert .= $tmpFilename;
                        }
                    $wert .= "</td>";
                    }
                }
            $wert .= "</tr></table>";

        return ($wert);
        }

    }

/**************************************************************************************************************************
 *
 * ipsOps, Zusammenfassung von Funktionen rund um die Erleichterung der Bedienung von IPS Symcon
 *
 * __construct              als Constructor wird entweder nichts oder der Modulname übergeben
 *
 * ipsVersion               die IPS version ausgeben, als Major, Minor Array
 * ipsVersion7check         true wenn IPS Version groesser 6
 *
 * isMemberOfCategoryName   ead path and check if Category with name is part of
 * path                     gibt den IPS Category Path als string return aus
 * getChildrenIDsOfType
 * totalChildren            die Anzahl der Children in einer hierarchischen mit Subkategorien aufgebauten Umgebung zählen
 *     countChildren           rekursive Funktion dafür.
 *
 * mb_str_pad               str pad für alle typen von chars, auch solche wie ö etc
 *
 * searchIDbyName
 * get_ScriptIDs
 * readWebfrontConfig       Aus der Default Webfront Configurator Konfiguration die Items auslesen (IPS_GetConfiguration($WFC10_ConfigId)->Items
 * getMediaListbyType
 *
 * configWebfront
 * writeConfigWebfrontAll
 * writeConfigWebfront
 *
 * intelliSort              nach einem sub index sortieren
 * serializeArrayAsPhp
 * serialize_array
 * serializeArrayAsJavascript
 *
 * emptyCategory            rekursiv
 * createVariableByName
 * updateIncludeFile
 * getVariableIDByName
 * createCategoryByName
 * getCategoryIdByName
 *
 * trimCommand
 *
 * adjustTimeFormat
 * strtotimeFormat
 *
 *
 *
 ******************************************************/

class ipsOps
    {

    var $module;
    var $includefile;

    /* einfaches construct, bereitet include file vor
     */
    function __construct($module="")
        {
        if ($module != "") $this->module = $module;
        $this->includefile="<?php";                      //  Kommentar muss sein sonst funktioniert Darstellung vom Editor nicht , verwendet von CreateVariable3
	    $this->includefile.="\n".'function ParamList() {
		        return array('."\n";
        }

    /* die IPS version ausgeben, als Major, Minor Array
     */
    public function ipsVersion()
        {
        $ipsVer = IPS_GetKernelVersion();
        $ipsVers = explode(".",$ipsVer);
        $ipsVersion["Major"]=(integer)$ipsVers[0];
        $ipsVersion["Minor"]=(integer)$ipsVers[1];
        //print_r($ipsVersion);
        return($ipsVersion);
        }

    /* check ob IPS version größer 6
     */
    public function ipsVersion7check()
        {
        $ipsVer = IPS_GetKernelVersion();
        $ipsVers = explode(".",$ipsVer);
        $ipsVersion["Major"]=(integer)$ipsVers[0];
        $ipsVersion["Minor"]=(integer)$ipsVers[1];
        //print_r($ipsVersion);
        return(($ipsVersion["Major"]>6));
        }

    /* object belongs to category
     * read path and check if Category with name is part of
     */
    public function isMemberOfCategoryName($objectR,$categoryName)
        {
        $found=false;
        while ($objectR=IPS_GetParent($objectR))
            {
            if (IPS_GetName($objectR)==$categoryName) $found=true;
            }
        return ($found);
        } 

    /* den IPS Pfad ausgeben 
     */
    public function path($objectR,$order=false)
        {
        $path=array();
        $str = IPS_GetName($objectR);
        $path[]=$objectR;
        while ($objectR=IPS_GetParent($objectR))
            {
            $path[]=$objectR;
            $str .= ".".IPS_GetName($objectR);
            }
        $str .= ".".IPS_GetName($objectR);
        if ($order)
            {
            $str=""; $first=true;
            foreach (array_reverse($path) as $oid) 
                {
                if ($first==false) $str.=".";
                else $first=false;
                $str.=IPS_GetName($oid);    
                }
            }
        return($str);
        }

    /* get Childrens from a certain Type
     */
    public function getChildrenIDsOfType($oid,$type=0)
        {
        $result = array();
        $entries=IPS_getChildrenIDs($oid);
        foreach ($entries as $entry)
            {
            $objectType=IPS_getObject($entry)["ObjectType"];
            if ($objectType==$type) $result[]=$entry;  
            }
        return ($result);
        }

    /* ipsOps, die Anzahl der Children in einer hierarchischen mit Subkategorien aufgebauten Umgebung zählen 
     */
    public function totalChildren($oid)
        {
        $count=0;
        $this->countChildren($oid,$count);
        return ($count);
        }

    /* rekursiver Aufruf für Ermittlung totalChildren   
     */
    private function countChildren($oid,&$count)
        {
        $entries=IPS_getChildrenIDs($oid);
        $countEntry=count($entries);
        if ( ($entries !== false) && ($countEntry>0) )
            {
            foreach ($entries as $entry)
                {
                $this->countChildren($entry,$count);   
                }
            }
        else $count++;
        }     

    /**
    * mb_str_pad
    *
    * @param string $input
    * @param int $pad_length
    * @param string $pad_string
    * @param int $pad_type
    * @return string
    * @author Kari "Haprog" Sderholm
    */
    function mb_str_pad( $input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT)
        {
        $diff = strlen( $input ) - mb_strlen( $input );
        return str_pad( $input, $pad_length + $diff, $pad_string, $pad_type );
        }
        
    /* ipsOps, sucht ein Children mit dem Namen der needle enthält 
     * nimmt gleich den ersten Treffer in der Reihe von Children
     *
     */
    public function searchIDbyName($needle, $oid)
        {
		$resultOID=IPS_GetObject($oid);
		//echo $oid." \"".$resultOID["ObjectName"]."\" ".$resultOID["ParentID"]."\n";
		$childrenIds=$resultOID["ChildrenIDs"];
		foreach ($childrenIds as $childrenId)
			{
			$result=IPS_GetObject($childrenId);
			$resultname=$result["ObjectName"];
			if (strpos($resultname,$needle)===false)
				{
				$nachrichtok="";
				}
			else
				{
				$nachrichtok="gefunden";
				return $childrenId;
				}
			}
        return (false);
        }

    /* ipsOps, gibt rekursiv alle scripts nach dem Namen aus 
     */
    function get_ScriptIDs(&$scriptNames, $scriptComponentsID)
        {    
        $childrens=IPS_getChildrenIDs($scriptComponentsID);
        //print_r($childrens);    
        foreach ($childrens as $children)
            {
            $objectType=IPS_getObject($children)["ObjectType"];
            if ($objectType==3) 
                {
                $Name=IPS_GetName($children);
                if (isset($scriptNames[$Name])===false) $scriptNames[$Name]=$children;
                else echo "Error, script name $Name double entry.\n";
                $path=$this->path($children,true);
                //echo "   $children   $Name   ".str_pad(IPS_GetLocation($children),45)."   $path   ".get_ObjectIDbyPath($path)."\n";
                echo "   $children   ".str_pad($Name,40)."   $path  \n";
                }  
            elseif ($objectType==0) 
                { 
                $this->get_ScriptIDs($scriptNames, $children);
                }
            else echo "Error, object not expected.\n";
            }
        }
        
    /* ipsOps::readWebfrontConfig, Aus der Default Webfront Configurator Konfiguration die Items auslesen (IPS_GetConfiguration($WFC10_ConfigId)->Items
     *
     */
    public function readWebfrontConfig($WFC10_ConfigId, $debug)
        {
        if ($debug) echo "Aus der Default Webfront Configurator Konfiguration die Items auslesen (IPS_GetConfiguration($WFC10_ConfigId)->Items:\n";
        $config = IPS_GetConfiguration($WFC10_ConfigId);
        $configStructure=json_decode($config,true); // array erstellen statt struct
        //print_r($configStructure);
        $configItems=json_decode($configStructure["Items"],true); // array erstellen statt struct
        if ($debug) print_r($configItems);

        /* flache Struktur aus dem configitems Array erstellen, Name => Parent */
        $structure=array();
        foreach ($configItems as $name => $entry)
            {
            //echo $name."   \n"; print_r($entry);
            if ($entry["ParentID"]=="") 
                {
                //echo "Root gefunden : ".$entry["ID"]." \n";
                $structure[$entry["ID"]]["ParentID"]="root";
                }
            else $structure[$entry["ID"]]["ParentID"]=$entry["ParentID"];
            $structure[$entry["ID"]]["Configuration"]=$entry["Configuration"];
            }
        //print_r($structure);


        /* Verzeichnisstruktur aus Struktur aufbauen, beginnt mit roottp */
        $directory=array();
        foreach ($structure as $index => $entry)
            {
            //echo " bearbeite $index => $entry \n";
            if ($entry["ParentID"]=="root") $directory[$index][0]=$entry["Configuration"];
            if (isset($directory[$entry["ParentID"]])) $directory[$entry["ParentID"]][$index][0]=$entry["Configuration"];
            else
                {   
                // zweite Ebene untersuchen
                $found=false;
                foreach ($directory as $ind => $needle)
                    {
                    //print_r($needle);
                    //echo "   suche in $ind \n";
                    if (isset($directory[$ind][$entry["ParentID"]])) 
                        {
                        $directory[$ind][$entry["ParentID"]][$index][0]=$entry["Configuration"];
                        $found=true;
                        }
                    }
                if ($found==false)
                    {   // wenn noch nicht gefunden dann die dritte Ebene untersuchen
                    if ($debug) echo "   Dritte Ebene untersuchen für $index / ".$entry["ParentID"]."\n";
                    foreach ($directory as $ind1 => $needle1)
                        {
                        //print_r($needle1);
                        foreach ($directory as $ind => $needle)
                            {                
                            if ($debug) echo "      suche in $ind $ind1 \n";
                            if (isset($directory[$ind][$ind1][$entry["ParentID"]])) $directory[$ind][$ind1][$entry["ParentID"]][$index][0]=$entry["Configuration"];
                            }
                        }
                    }   
                }
            }
        return ($directory);
        }           

    /* ipsOps::getMediaListbyType, Mit Medialist arbeiten. Sind alle Objekte mit Typ Media, Nutzung der zusätzlichen Features 
     */
    function getMediaListbyType($type, $debug=false)
        {
        $medias=IPS_GetMediaList();
        $mediaFound=array();    
        if ($debug) echo "Anzahl Eintraege Medialist ".count($medias)."\n";
        foreach ($medias as $media)
            {
            $mediaType=IPS_GetMedia($media)["MediaType"];
            if ($mediaType==$type) $mediaFound[]=$media;
            if ($debug) 
                {
                echo " $media ";
                switch ($mediaType)
                    {
                    case 0:
                        echo "Formular";
                        break;
                    case 1:
                        echo "Bild    ";
                        break;
                    case 2:
                        echo "Ton     ";
                        break;
                    case 3:
                        echo "Stream  ";
                        break;
                    case 4:
                        echo "Chart   ";
                        break;
                    case 5:
                        echo "Dokument";
                        break;
                    default:
                        echo "unknown ";
                        break;
                    }
                $objectR=$media;
                echo "   ".IPS_GetName($objectR);
                while ($objectR=IPS_GetParent($objectR))
                    {
                    echo ".".IPS_GetName($objectR);
                    }
                echo ".".IPS_GetName($objectR);
                echo "             ".IPS_GetMedia($media)["MediaFile"];
                echo "\n";
                }
            }
        return($mediaFound);
        }

    /***************
     *
     * ipsOps::configWebfront, das Ini File auslesen und als Array zur verfügung stellen, es wird nur der modulManager benötigt 
     * die Zuweisung ist Fix 
     *          Administrator   ->  WFC10
     *          User            ->  WFC10user
     *          Mobile          ->  Mobile
     *          Retro           ->  Retro
     * die Kachelvisualisiserung hat noch keine Ini Dateien
     *          
     ******************************/

    function configWebfront($moduleManager, $debug=false)
        {
        $result=array();
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
        $alleInstanzen = $modulhandling->getInstancesByType(6);                 // alle Visualisiserungen
        foreach ($alleInstanzen as $index => $instanz)
            {
            $instance=IPS_GetInstance($instanz["OID"]);
            $result[IPS_GetName($instanz["OID"])]["ConfigId"]=$instance["InstanceID"];
            if ($debug) echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz["OID"]),25)." ID : ".$instance["InstanceID"]."  (".$instanz["OID"].")\n";
            }
        /*echo "done.\n";
        $alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
        foreach ($alleInstanzen as $instanz)
            {
            $instance=IPS_GetInstance($instanz);
            $result[IPS_GetName($instanz)]["ConfigId"]=$instance["InstanceID"];
            if ($debug) echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$instance["InstanceID"]."  (".$instanz.")\n";
            } */
        $RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis', false);
        if ($RemoteVis_Enabled)
            {
            if ($debug) echo "RemoteVis is enabled.\n";
            $result["RemoteVis"] = true;
            }

        $WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
        if (strtoupper($WFC10_Enabled)=="FALSE") $WFC10_Enabled=false;
        $result["Administrator"]["Enabled"] = $WFC10_Enabled;
        if ($debug) echo "Wert vom Administrator Webfront $WFC10_Enabled\n";
        $TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10',false);          //TabPaneItem="WebCameraTPA", so ist es im Webfront abgespeichert
        if ($TabPaneItem !== false) $result["Administrator"]["TabPaneItem"] = $TabPaneItem;
        if ($WFC10_Enabled)
            {
            $Path        	 = $moduleManager->GetConfigValueDef('Path', 'WFC10',false);
            if ($Path) $result["Administrator"]["Path"] = $Path;
            $TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10',false);        //TabPaneParent="roottp", gleich über der Wurzel
            if ($TabPaneParent !== false) $result["Administrator"]["TabPaneParent"] = $TabPaneParent;
            $TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10',false);          // TabPaneName="" in der ersten Reihe gibt es nur Bilder/Icons keine Namen
            if ($TabPaneName !== false) $result["Administrator"]["TabPaneName"] = $TabPaneName;
            $TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10',false);          // TabPaneIcon="Camera" wichtig, das Icon zum Wiedererkennen
            if ($TabPaneIcon !== false) $result["Administrator"]["TabPaneIcon"] = $TabPaneIcon;
            $TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10',false);      // TabPaneOrder="10" wo soll das Icon in der obersten Leiste stehen  
            if ($TabPaneOrder !== false) $result["Administrator"]["TabPaneOrder"] = $TabPaneOrder;
            $TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10',false);              // TabItem="Monitor" nächste Reihe, Gliederung der Funktionen
            if ($TabItem !== false) $result["Administrator"]["TabItem"] = $TabItem;
            $TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10',false);              // TabName
            if ($TabName !== false) $result["Administrator"]["TabName"] = $TabName;
            $TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10',false);              // TabIcon="Window"
            if ($TabIcon !== false) $result["Administrator"]["TabIcon"] = $TabIcon;
            $TabOrder       = $moduleManager->GetConfigValueDef('TabOrder', 'WFC10',false);              
            if ($TabOrder !== false) $result["Administrator"]["TabOrder"] = $TabOrder;
            }

        $WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
        if (strtoupper($WFC10User_Enabled)=="FALSE") $WFC10User_Enabled=false;
        $result["User"]["Enabled"] = $WFC10User_Enabled;
        $TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10User',false);              // TabItem="Monitor" nächste Reihe, Gliederung der Funktionen
        if ($TabItem !== false) $result["User"]["TabItem"] = $TabItem;
        if ($WFC10User_Enabled)
            {        
            $Path        	 = $moduleManager->GetConfigValueDef('Path', 'WFC10User',false);
            if ($Path) $result["User"]["Path"] = $Path;
            $TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10User',false);          //TabPaneItem="WebCameraTPA", so ist es im Webfront abgespeichert
            if ($TabPaneItem !== false) $result["User"]["TabPaneItem"] = $TabPaneItem;
            $TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10User',false);        //TabPaneParent="roottp", gleich über der Wurzel
            if ($TabPaneParent !== false) $result["User"]["TabPaneParent"] = $TabPaneParent;
            $TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10User',false);          // TabPaneName="" in der ersten Reihe gibt es nur Bilder/Icons keine Namen
            if ($TabPaneName !== false) $result["User"]["TabPaneName"] = $TabPaneName;
            $TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10User',false);          // TabPaneIcon="Camera" wichtig, das Icon zum Wiedererkennen
            if ($TabPaneIcon !== false) $result["User"]["TabPaneIcon"] = $TabPaneIcon;
            $TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10User',false);      // TabPaneOrder="10" wo soll das Icon in der obersten Leiste stehen  
            if ($TabPaneOrder !== false) $result["User"]["TabPaneOrder"] = $TabPaneOrder;
            $TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10User',false);              // TabItem="Monitor" nächste Reihe, Gliederung der Funktionen
            if ($TabItem !== false) $result["User"]["TabItem"] = $TabItem;
            $TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10User',false);              // TabName
            if ($TabName !== false) $result["User"]["TabName"] = $TabItem;
            $TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10User',false);              // TabIcon="Window"
            if ($TabIcon !== false) $result["User"]["TabIcon"] = $TabIcon;
            $TabOrder       = $moduleManager->GetConfigValueDef('TabOrder', 'WFC10User',false);              
            if ($TabOrder !== false) $result["User"]["TabOrder"] = $TabOrder;            
            }

        $Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
        if (strtoupper($Mobile_Enabled)=="FALSE") $Mobile_Enabled=false;
        $result["Mobile"]["Enabled"] = $Mobile_Enabled;        
        if ($Mobile_Enabled)
            {        
            $Path        	 = $moduleManager->GetConfigValueDef('Path', 'Mobile',false);
            if ($Path) $result["Mobile"]["Path"] = $Path;
            $PathOrder     = $moduleManager->GetConfigValueDef('PathOrder', 'Mobile',false);
            if ($PathOrder !== false) $result["Mobile"]["PathOrder"] = $PathOrder;
            $PathIcon      = $moduleManager->GetConfigValueDef('PathIcon', 'Mobile',false);
            if ($PathIcon !== false) $result["Mobile"]["PathIcon"] = $PathIcon;
            $Name          = $moduleManager->GetConfigValueDef('Name', 'Mobile',false);
            if ($Name !== false) $result["Mobile"]["Name"] = $Name;
            $Order         = $moduleManager->GetConfigValueDef('Order', 'Mobile',false);
            if ($Order !== false) $result["Mobile"]["Order"] = $Order;
            $Icon          = $moduleManager->GetConfigValueDef('Icon', 'Mobile',false);            
            if ($Icon !== false) $result["Mobile"]["Icon"] = $Icon;
            }
        $Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);
        if ($Retro_Enabled)
            {        
            $Path        	 = $moduleManager->GetConfigValueDef('Path', 'Retro',false);
            if ($Path) $result["Retro"]["Path"] = $Path;
            }
        return ($result);
        }

    /*  input ist das outcome von configWebfront
    *
    *
    */
    function writeConfigWebfrontAll($config)
        {
        echo "Überblick Konfiguration Webfront:\n";
        if ( (isset($config["Administrator"]["Enabled"])) && ($config["Administrator"]["Enabled"]) )
            {
            echo "   Webfront Administrator Enabled\n";
            echo $this->writeConfigWebfront($config["Administrator"],"      ");
            }
        if ( (isset($config["User"]["Enabled"])) && ($config["User"]["Enabled"]) )
            {
            echo "   Webfront User Enabled\n";
            echo $this->writeConfigWebfront($config["User"],"      ");
            }
        if ( (isset($config["Mobile"]["Enabled"])) && ($config["Mobile"]["Enabled"]) )
            {
            echo "   Webfront Mobile Enabled\n";
            echo $this->writeConfigWebfront($config["Mobile"],"      ");
            }
        if ( (isset($config["Retro"]["Enabled"])) && ($config["Retro"]["Enabled"]) )
            {
            echo "   Webfront Retro Enabled\n";
            echo $this->writeConfigWebfront($config["Retro"],"      ");
            }
        }

    /* ein Webfront ausgeben
     * Administrator, User
     */
    function writeConfigWebfront($config,$ident="")
        {
        $result=""; 
		if (isset($config["Path"]))           $result.=$ident."Path          : ".$config["Path"]."\n";
		if (isset($config["ConfigId"]))       $result.=$ident."ConfigID      : ".$config["ConfigId"]."  (".IPS_GetName(IPS_GetParent($config["ConfigId"])).".".IPS_GetName($config["ConfigId"]).")\n";		
		if (isset($config["TabPaneItem"]))    $result.=$ident."TabPaneItem   : ".$config["TabPaneItem"]."\n";
		if (isset($config["TabPaneParent"]))  $result.=$ident."TabPaneParent : ".$config["TabPaneParent"]."\n";
		if (isset($config["TabPaneName"]))    $result.=$ident."TabPaneName   : ".$config["TabPaneName"]."\n";
		if (isset($config["TabPaneIcon"]))    $result.=$ident."TabPaneIcon   : ".$config["TabPaneIcon"]."\n";
		if (isset($config["TabPaneOrder"]))   $result.=$ident."TabPaneOrder  : ".$config["TabPaneOrder"]."\n";
		if (isset($config["TabItem"]))        $result.=$ident."TabItem       : ".$config["TabItem"]."\n";
		if (isset($config["TabName"]))        $result.=$ident."TabName       : ".$config["TabName"]."\n";
		if (isset($config["TabIcon"]))        $result.=$ident."TabIcon       : ".$config["TabIcon"]."\n";
		if (isset($config["TabOrder"]))       $result.=$ident."TabOrder      : ".$config["TabOrder"]."\n";	 
        return ($result);      
        }

    /* ipsOps, verwendet array_multisort, array wird nach dem key orderby sortiert
     * Ergebnis der Berechnungen ist sortarray aber sortiert wird inputarray, das inputarray besteht aus Zeilen und Spalten, die Spalte assoziert mit dem Key
     * zuerst alle Zeilen und Spalten durchgehen, es wird ein neues Array mit der Spalte als Key und seinem Wert angelegt, das heisst pro zeile ein Wert
     * array_multisort nimmt das sortArray mit dem orderby Key als Input für den Sortierungsalgorythmus und sortiert ensprechend das inputArray
     * als Returnwert wird üblicherweise das inputArray verwendet, return sortArray nur als Hilfestellung
     *
     * es muss ein zweidimensionales array mit Zeilen und Spalten sein
     * zuerst die Zeilen und die Spalten durchgehen und ein Array mit den sortierenden Elementen anlegen
     *
     */
    function intelliSort(&$inputArray, $orderby, $sort=SORT_ASC, $debug=false)
        {
        $first=true;
        $sortArray = array(); 
        if ($sort === false) $sort=SORT_ASC;
        if ($orderby === false) return (false);         // Spalten Key nachdem sortiert werden soll
        if (is_array($inputArray))
            {
            if ($debug) echo "intellisort: ".sizeof($inputArray)." Zeilen. Order by $orderby. Sort $sort.\n";
            foreach($inputArray as $index => $entry)              // alle Spalten zeilenweise durchgehen
                {
                if ((is_array($entry))===false) return false; 
                if ($debug && $first) { echo str_pad($index,5)."   ".sizeof($entry)." Spalten\n"; $first=false; }
                foreach($entry as $key=>$value)
                    { 
                    if (is_array($value)) { echo "intellisort, input array mehr als zweidimensional : ".json_encode($entry)."\n"; return (false); }
                    if(!isset($sortArray[$key])) $sortArray[$key] = array();  
                    $sortArray[$key][$index] = strtoupper($value);                          // Speicherung mit Index und strtoupper hinzugefügt, kann auch parametrierbar werden
                    } 
                } 
            array_multisort($sortArray[$orderby],$sort,SORT_STRING,$inputArray);        // inputArray basierend auf sortArray.orderby sortieren, wird Teil von sortArray
            return($sortArray);
            }
        else return false;
        }

    /* ipsOps, array serialize to Php 
     *
     * liest die keys der ersten Ebene und rekursiv ruft es dieselbe routine für weitere Ebenen auf
     * erstellt einen php array Definition für das echte array
     *
     * arrayInput   ist das array als array formattiert
     * text         ist der String in dem das php formatierte array hineinkopiert wird
     * depth        gibt die Anzahl der bereits erfolgten rekursiven Aufrufe wieder
     *
     */
    function serializeArrayAsPhp(&$arrayInput, &$text, $depth = 0, $configInput=0, $debug=false)
        {
        if ($debug && ($depth==0)) echo "\nserializeArrayAsPhp aufgerufen:\n";
        $arrayStart=true;
        $config=array();
        if (is_array($configInput))
            {
            if (isset($configInput["ident"])) $ident=$configInput["ident"];
            else 
                {
                $ident=$depth*10;
                $configInput["ident"]=$ident;
                }
            if (isset($configInput["start"])) $arrayStart=$configInput["start"];          // wenn root mit array(       ); einschliessen
            $config=$configInput;
            }
        else 
            {
            $ident=$configInput;        // ident als str_pad("",$ident)
            $config["ident"]=$ident;
            }
        $items = array();       // Zwischenspeicher

        if ($arrayStart)
            {
            if ($debug) echo "array("; 
            $text .= "array("; 
            }
        /* alle Eintraeg des Array durchgehen, sind immer key value Pärchen, zuerst die sub array abarbeiten und dann die einzelnen Pärchen drucken */
        foreach($arrayInput as $key => &$value)             // foreach mit referenz, der Eintrag wird verändert
            {
            if(is_array($value))
                {
                if ($debug) echo "\n".str_pad(" ",$ident)."\"$key\" => ";
                $text .= "\n".str_pad(" ",$ident)."\"$key\" => ";
                $configInput=$config;
                $configInput["ident"] += 10; $configInput["start"]=true;
                $this->serializeArrayAsPhp($value, $text, $depth+1, $configInput, $debug);

                //if ($debug) echo str_pad(" ",$ident+5)."),\n"; 
                //$text .= str_pad(" ",$ident+5)."),\n"; 
                }
            else
                {
                $items[$key] = $value;
                }
            }

        if(count($items) > 0)           // wenn es Werte gibt  'key' => 'value'
            {
            $prefix = "";
            foreach($items as $key => &$value)
                {
                if ($debug) echo "\n".str_pad(" ",$ident).$prefix . '\'' . $key . '\' => \''.$value.'\',';
                $text .= "\n".str_pad(" ",$ident).$prefix . '\'' . $key . '\' => \''.$value.'\',';
                $prefix = "";
                }
            }

        if ($depth>0) 
            {
            if ($debug) echo "\n".str_pad(" ",$ident+10).')';
            $text .= "\n".str_pad(" ",$ident+10).')';    
            if ($debug) echo ",";
            $text .= ",";
            }
        else            // root
            {
            if ($arrayStart)
                {
                if ($debug) echo "\n".str_pad(" ",$ident+10).')';
                $text .= "\n".str_pad(" ",$ident+10).')';
                if ($debug) echo ";\n";
                $text .= ";\n";
                }
            else 
                {
                if ($debug) echo "\n".str_pad(" ",$ident+10);                    
                $text .= "\n".str_pad(" ",$ident+10);
                }
            }

        return $text;
        } 

    /* ipsOps, serialize array 
     */
    function serialize_array(&$array, $root = '$root', $depth = 0)
        {
        $items = array();

        foreach($array as $key => &$value)
            {
            if(is_array($value))
                {
                serialize_array($value, $root . '[\'' . $key . '\']', $depth + 1);
                }
            else
                {
                $items[$key] = $value;
                }
            }

        if(count($items) > 0)
            {
            echo $root . ' = array(';

            $prefix = '';
            foreach($items as $key => &$value)
                {
                echo $prefix . '\'' . $key . '\' => \'' . addslashes($value) . '\'';
                $prefix = ', ';
                }
            echo ');' . "\n";
            }
        }

    /* ipsOps, array serialize to Javascript 
     *
     * liest die keys der ersten Ebene und rekursiv ruft es dieselbe routine für weitere Ebenen auf
     * erstellt eine javascript object array Definition 
     *
     * arrayInput   ist das array als array formattiert
     * text         ist der String in dem das javascript formatierte object array hineinkopiert wird
     *
     * text ist der Anfang der array definition, wie zum beispiel var segmente =
     *
     */
    function serializeArrayAsJavascript(&$arrayInput, &$text, $debug=false)
        {
        $arrayStart=false;
        $items = array();       // Zwischenspeicher

        if ($debug) echo "["; 
        $text .= "["; 

        /* alle Eintraeg des Array durchgehen, sind immer key value Pärchen, zuerst die sub array abarbeiten und dann die einzelnen Pärchen drucken */
        foreach($arrayInput as $value)             // foreach mit referenz, der Eintrag wird verändert
            {
            if(is_array($value))
                {
                if ($debug) echo "{";
                $text .= "{";
                foreach ($value as $field => $entry)
                    {
                    if ($debug) echo "$field : $entry,";
                    $text .= "$field : $entry,";
                    }
                if ($debug) echo "},\n";
                $text .= "},\n";
                }
            else
                {
                if ($debug) echo "$value,";
                $text .= "$value,";
                }
            }
        if ($debug) echo "];\n"; 
        $text .= "];\n"; 
        return $text;
        } 

	/** ipsOps::emptyCategory, Löschen des Inhalts einer Kategorie inklusve Inhalt
	 *
	 * Die Funktion löscht den gesamtem Inhalt einer Kategorie
	 *
	 * @param integer $CategoryId ID der Kategory
	 *
	 */
	function emptyCategory($CategoryId,$config=false,$debug=false) 
        {
        if ($config===false) $config=array();
        if ($debug) echo "ipsOps:emptyCategory aufgerufen mit $CategoryId (".IPS_GetName($CategoryId)."). Config ist ".json_encode($config)."\n";
		if ($CategoryId==0) 
            {
            echo "ipsOps:emptyCategory, Error, its not allowed to  delete Root Category 0 of IP Symcon Tree!!!\n";    
			Error ("Root Category CANNOT be deleted!!!");
		    }

		$ChildrenIds = IPS_GetChildrenIDs($CategoryId);
		foreach ($ChildrenIds as $ObjectId) 
            {
            $subchildren=IPS_GetChildrenIDs($ObjectId);    
            if (sizeof($subchildren)>0 )
                {
                if ($debug) echo "Subchildren found, recursively delete\n";
                $this->emptyCategory($ObjectId) ;
                }
            if (IPS_GetObject($ObjectId)["ObjectType"]==0) 
                {
                $this->emptyCategory($ObjectId) ;  
                if ( (isset($config["deleteCategories"])) && ($config["deleteCategories"]) ) DeleteObject($ObjectId);
                }
			else DeleteObject($ObjectId);
		    }
		Debug ("Empty Category ID=$CategoryId");
	    }

    /* ipsOps::createVariableByName as class function with additional features
     * Variable oder Kategorie wird nur angelegt wenn sie noch nicht vorhanden ist
     *
     */

    function createVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
        {
        //echo "Position steht auf $position.\n";
        //echo "CreateVariableByName: $id $name $type $profile $ident $position $action\n";
        /* type steht für 0 Boolean 1 Integer 2 Float 3 String */
        
        $VariableId = @IPS_GetVariableIDByName($name, $parentID);
        if ($VariableId === false)
            {
            echo "Create Variable Name $name Type $type in $parentID:\n";
            $VariableId = @IPS_CreateVariable($type);
            if ($VariableId === false ) throw new Exception("Cannot CreateVariable with Type $type");
            IPS_SetParent($VariableId, $parentID);
            IPS_SetName($VariableId, $name);
            if ( ($profile) && ($profile !== "") ) { IPS_SetVariableCustomProfile($VariableId, $profile); }
            if ( ($ident) && ($ident !=="") ) {IPS_SetIdent ($VariableId , $ident );}
            if ( $action && ($action!=0) ) { IPS_SetVariableCustomAction($VariableId,$action); }        
            if ($default !== false) SetValue($VariableId, $default);
            IPS_SetInfo($VariableId, "this variable was created by script #".$_IPS['SELF']." ");
            }
        else 
            {
            $VariableData = IPS_GetVariable($VariableId);
            $objectInfo   = IPS_GetObject($VariableId); 
            if ($VariableData['VariableType'] <> $type)
                {
                IPSLogger_Err(__file__, "CreateVariableByName, $VariableId ($name) Type ".$VariableData['VariableType']." <> \"$type\". Delete and create new.");
                IPS_DeleteVariable($VariableId); 
                $VariableId=CreateVariableByName($parentID, $name, $type, $profile, $ident, $position, $action);  
                $VariableData = IPS_GetVariable ($VariableId);            
                }
            if ($profile && ($VariableData['VariableCustomProfile'] <> $profile) )
                {
                //Debug ("Set VariableProfile='$Profile' for Variable='$name' ");
                echo "Set VariableProfile='$profile' for Variable='$name' \n";
                $result=@IPS_SetVariableCustomProfile($VariableId, $profile);
                if ($result==false) 
                    {
                    echo "CreateVariableByName, $VariableId ($name) Type ".$VariableData['VariableType']." and new Profile $profile produce error, do not match.\n";
                    IPSLogger_Err(__file__, "CreateVariableByName, $VariableId ($name) Type ".$VariableData['VariableType']." and new Profile $profile produce error, do not match.");
                    }
                }	
            if ($action && ($VariableData['VariableCustomAction'] <> $action) )
                {
                //Debug ("Set VariableCustomAction='$Action' for Variable='$Name' ");
                echo "Set VariableCustomAction='$action' for Variable='$name' \n";
                IPS_SetVariableCustomAction($VariableId, $action);
                }
            if ($ident && ($objectInfo['ObjectIdent'] <> $ident) )
                {
                //Debug ("Set VariableCustomAction='$Action' for Variable='$Name' ");
                echo "Set VariableIdent='$ident' for Variable='$name' \n";
                IPS_SetIdent($VariableId, $ident);
                }
    
            }
        IPS_SetPosition($VariableId, $position);
	    $this->includefile.='"'.$name.'" => array("OID"     => \''.$VariableId.'\','."\n".
					'                       "Name"    => \''.$name.'\','."\n".
					'                       "Type"    => \''.$type.'\','."\n".
					'                       "Profile" => \''.$profile.'\'),'."\n";        
        return $VariableId;
        }

    function updateIncludeFile($VariableId)
        {
        $name=IPS_GetName($VariableId);
        $variableType=IPS_GetVariable($VariableId);
        $type=$variableType["VariableType"];
        if (isset($variableType["VariableProfile"]))                $profile=$variableType["VariableProfile"];
        elseif (isset($variableType["VariableCustomProfile"])) $profile=$variableType["VariableCustomProfile"];
        else $profile="";

	    $this->includefile.='"'.$name.'" => array("OID"     => \''.$VariableId.'\','."\n".
					'                       "Name"    => \''.$name.'\','."\n".
					'                       "Type"    => \''.$type.'\','."\n".
					'                       "Profile" => \''.$profile.'\'),'."\n";        
        }

    function getIncludeFile()
        {
	    $includefile = $this->includefile.');'."\n";
	    $includefile.='}'."\n".'?>';
        return $includefile;
        }

    /* einfache getVariableId Function
     * vertauschte Parameter :-) und ein false ohne Fehlermeldung
     *
     */
    function getVariableIDByName($parentID, $name)
        {
        return(@IPS_GetVariableIDByName($name, $parentID));    
        }

    /* einfache CreateCategory Function
     */
    function createCategoryByName($parentID, $name, $position=0)
        {
        $vid = @IPS_GetCategoryIDByName($name, $parentID);
        if($vid === false)
            {
            $vid = IPS_CreateCategory();
            IPS_SetParent($vid, $parentID);
            IPS_SetName($vid, $name);
            IPS_SetInfo($vid, "this category was created by script #".$_IPS['SELF']." ");
            }
        IPS_SetPosition($vid, $position);
        return $vid;
        }

    /* einfache getCategoryId Function
     * vertauschte Parameter :-) und ein false ohne Fehlermeldung
     */
    function getCategoryIdByName($parentID, $name)
        {
        return(@IPS_GetCategoryIDByName($name, $parentID));
        }


	/**
	 *
	 * Command trimmen, damit es in einer Zeile ausgegeben werden kann, Befehl wird oft formatiert für bessere Lesbarkeit
	 *
	 * @return string[] Event Konfiguration
	 */
	function trimCommand($command) 
		{
	    $kommandotrim=array();
        $kommandogruppe=explode(";",$command);
        foreach ($kommandogruppe as $index => $kommando)
            {
            $befehlsgruppe=explode(",",$kommando);
            foreach ( $befehlsgruppe as $count => $befehl)
                {
                $result=trim($befehl);
                if ($result != "")
                    {
                    //echo "   ".$index." ".$count."  ".$result."\n ";
                    $kommandotrim[$index][$count]=$result;
                    }
                }
            }
        $entry3="";  $semi="";  
        //print_r($kommandotrim);
        foreach ($kommandotrim as $kommando)
            {
            //print_r($kommando);
            $comma="";
            $entry3.=$semi;
            if ($semi=="") $semi=";"; 
            foreach ($kommando as $befehl)
               {
               //echo $befehl;
               $entry3.=$comma.$befehl;
               if ($comma=="") $comma=",";
               }
           }
    	return ($entry3);
	    }


    /* ipsOps::AdjustTimeFormat , einen zeitstempel anpassen mit datetime format 
     * format   Ymd         4 Jahr 2 monat 2 Tag, nicht vorkommende Formatierungszeichen werden auf Default gesetzt
     * Beispiel Ergebnis ist der TimeStamp für
     *    1.1.Jahr 00:00 wenn als Format nur Y angegeben wird
     *    Day.Month.Year 00:00 wenn als Format Ymd angegeben wird
     *
     * aktuell nur Ymd und Y unterstützt, siehe nächste Routine
     */

    public function adjustTimeFormat($time,$format,$debug=false)
        {
        $string=date($format,$time);                                                // eine Zeitstempel formatieren, zB Ymd  20231203 für den 3.Dezember 2023
        $newTime = $this->strtotimeFormat($string,$format,$debug);                  // und dann wieder zu einem Zeitstempel machen
        return ($newTime);
        }


    /* ipsOps::strtotimeFormat aus einem String eine Uhrzeit als time machen, format bestimmt die Anordnung
     * format   Ymd         4 Jahr 2 monat 2 Tag
     */

    public function strtotimeFormat($string,$format,$debug=false)
        {
        $hour=0;
        $minute=0;
        $second=0;
        $month=1;
        $day=1;
        $year=1970;
        switch ($format)
            {
            case "YmdH":
                $year  = intval(substr($string,0,4));
                $month = intval(substr($string,4,2));
                $day   = intval(substr($string,6,2));
                $hour  = intval(substr($string,8,2));
                break;
            case "Ymd":
                $year  = intval(substr($string,0,4));
                $month = intval(substr($string,4,2));
                $day   = intval(substr($string,6,2));
                break;
            case "Ym":
                $year  = intval(substr($string,0,4));
                $month = intval(substr($string,4,2));
                break;
            case "Y":
                $year  = intval(substr($string,0,4));
                break;                
            }
        if ($debug) echo "mktime($hour,$minute,$second,$month,$day,$year)\n";
        return (mktime($hour,$minute,$second,$month,$day,$year));
        }


    }           // ende class ipsOps




/*****************************************************************
 *
 *  Funktionen rund um das Disk Operating System für Windows und soll bald auch vollständig für UNIX funktionieren
 *  Befehl ExecuteUserCommand und einige andere Befehle die potentielle Ergebnisse evaluieren müssen neu gekapselt werden
 *
 *  ExecuteUserCommand          verwendet entweder IPSEXECUTE oder IPSEXECUTEEX, abhängig wie IPS gestartet wurde, als System user oder als Administrtor
 *  getWmicsProcessList
 *  checkProcess                verwendet folgende private functions
 *      getProcessList
 *      getTaskList
 *      getJavaList
 *  getProcessListFull
 *  getSystemInfo
 *  checkProcess
 *
 *  getNiceFileSize
 *  formatSize
 *
 *  getServerMemoryUsage
 *  readHardDisk
 *
 */

class sysOps
    { 

    /* sysOps, IPS_ExecuteEX funktioniert nicht wenn der IP Symcon Dienst statt mit dem SystemUser bereits als Administrator angemeldet ist 
     * Parameter für den Aufruf
     *    command   Befehl gemeinsam mit absolutem Pfad
     *
     */
    public function ExecuteUserCommand($command,$parameter="",$show=false,$wait=false,$session=-1,$debug=false)
        {
        if (dosOps::getKernelPlattform()=="WINDOWS")            // does not use instantiated data, no construct needed, unix or windows file structure expected
            {
            if ($debug) echo "ExecuteUserCommand $command \n";
            $result=@IPS_ExecuteEx($command, $parameter, $show, $wait, $session); 
            if ($result===false) 
                {
                try
                    {
                    $result=@IPS_Execute($command, $parameter, $show, $wait);   
                    }
                catch (Exception $e) 
                    { 
                    echo "Catch Exception, Fehler bei $e.\n";
                    }
                if ($debug) echo "Ergebnis IPS_Execute nach retry $result \n";  
                }
            else if ($debug) echo "Ergebnis IPS_ExecuteEx $result \n"; 
            }
        else echo "UNIX Plattform, Windows Befehle nicht relevant.\n";
        return ($result);                                    
        }

    /*****************************************************************
     * sysOps, von checkProcess verwendet, funktioniert nur für Windows
     * wenn filename übergeben wird, dieses file öffnen, von UTF-16 auf fixed 8Bit ASCII komvertieren
     * alternativ wmic mit IPS_Execute öffnen, ??? wird aber nicht funktionieren
     * ein paar linefeeds rausnehmen und wieder als process.txt speichern
     *
     * die aktuell gestarteten Dienste werden mit wmic process list erfasst
     *
     * dann das wmic Format auflösen, mit fixed tabs zwischen den Spalten, geht mittlerweile besser
     *
     */

    private function getProcessList($filename=false,$debug=false)
        {
        //$debug=true;
        $dosOps = new dosOps();    
        $systemDir     = $dosOps->getWorkDirectory();        
        $processList=array();        
        $result="";
        if ($filename)  
            {
            if (file_exists($filename))
                {
                $handle4=fopen($filename,"r");
                $i=0;
                if ($debug) echo "getProcessList, die aktuell gestarteten Programme werden aus der Datei $filename erfasst.\n";
                while (($line=fgets($handle4)) !== false) 
                    {
                    $line=mb_convert_encoding($line,"ASCII","UTF-16");                      // UTF-8 ist ein Multibyteformat, damit funktionieren keine strpos und substr Befehle mehr
                    if ($debug) echo str_pad($i,2)." | ".strlen($line)." | $line";
                    $result .= $line;
                    if ($i++>10000) break;
                    }
                fclose($handle4);
                if ($debug)  echo "    -> $i Zeilen eingelesen.\n";
                }
            else if ($debug) echo "getProcessList, die aktuell gestarteten Programme können NICHT aus der Datei $filename erfasst werden.\n";
            }
        else    
            {
            if ($debug) echo "getProcessList, die aktuell gestarteten Dienste werden erfasst.\n";
            $result=IPS_EXECUTE("c:/windows/system32/wbem/wmic.exe","process list", true, true);
            }

        $trans = array("\x0D\x0A\x0D\x0A" => "\x0D");
        $result = strtr($result,$trans);
        $handle=fopen($systemDir."process.txt","w");
        fwrite($handle,$result);
        fclose($handle);

        $firstLine=true;
        $ergebnis=explode("\x0D",$result);
        foreach ($ergebnis as &$resultvalue)
            {
            if ($firstLine==true)
                {
                $posCommandline=strpos($resultvalue,'CommandLine');
                $posCSName=strpos($resultvalue,'CSName');
                $posDescription=strpos($resultvalue,'Description');
                $posExecutablePath=strpos($resultvalue,'ExecutablePath');
                $posExecutionState=strpos($resultvalue,'ExecutionState');
                $posHandle=strpos($resultvalue,'Handle');
                $posHandleCount=strpos($resultvalue,'HandleCount');
                $posInstallDate=strpos($resultvalue,'InstallDate');
                //echo 'CommandLine    : '.$posCommandline."\n";
                //echo 'CSName         : '.$posCSName."\n";
                //echo 'Description    : '.$posDescription."\n";
                //echo 'ExecutablePath : '.$posExecutablePath."\n";
                //echo 'ExecutionState : '.$posExecutionState."\n";
                //echo 'Handle         : '.$posHandle."\n";
                //echo 'HandleCount    : '.$posHandleCount."\n";
                //echo 'InstallDate    : '.$posInstallDate."\n";
                $firstLine=false;
                }
            $value=$resultvalue;
            //echo $value;
            $resultvalue=array();
            $resultvalue['Commandline']=trim(substr($value,$posCommandline,$posCSName));
            $resultvalue['CSName']=rtrim(substr($value,$posCSName,$posDescription-$posCSName));
            $resultvalue['Description']=rtrim(substr($value,$posDescription,$posExecutablePath-$posDescription));
            $resultvalue['ExecutablePath']=rtrim(substr($value,$posExecutablePath,$posExecutionState-$posExecutablePath));
            $resultvalue['ExecutionState']=rtrim(substr($value,$posExecutionState,$posHandle-$posExecutionState));
            $resultvalue['Handle']=rtrim(substr($value,$posHandle,$posHandleCount-$posHandle));
            $resultvalue['HandleCount']=rtrim(substr($value,$posHandleCount,$posInstallDate-$posHandleCount));
            $resultvalue['InstallDate']=rtrim(substr($value,$posInstallDate,13));
            }
        unset($resultvalue);
        //print_r($ergebnis);
        //echo "Insgesamt stehen ".sizeof($ergebnis)." Zeilen zur Bearbeitung an.\n";        
        $LineProcesses="";
        foreach ($ergebnis as $valueline)
            {
            //echo $valueline['Commandline'];
            if ((substr($valueline['Commandline'],0,3)=="C:\\") or (substr($valueline['Commandline'],0,3)=='"C:')or (substr($valueline['Commandline'],0,3)=='C:/') or (substr($valueline['Commandline'],0,3)=='C:\\')  or (substr($valueline['Commandline'],0,3)=='"C:'))
                {
                //echo "****\n";
                $process=$valueline['ExecutablePath'];
                $pos=strrpos($process,'\\');
                $process=substr($process,$pos+1,100);
                if (($process=="svchost.exe") or ($process=="lsass.exe") or ($process=="csrss.exe")or ($process=="SMSvcHost.exe")  or ($process=="WmiPrvSE.exe")  )
                    {
                    $processList[]=$process;                        
                    }
                else
                    {
                    //echo $process."  Pos : ".$pos."  \n";
                    //$processes.=$valueline['ExecutablePath']."\n";
                    $LineProcesses.=$process.",";
                    $processList[]=$process;
                    }
                }
            else 
                {
                //echo "\n";
                }
            }

        return ($processList);
        }

   /*****************************************************************
     * sysOps, von getProcessListFull verwendet, nutzt fileOps
     * die aktuell gestarteten Programme werden erfasst
     * findet auch Java Processe die unter java.exe laufen
     *
     *
     */

    private function getWmicsProcessList($filename,$debug=false)
        {
        if ($debug) echo "getWmicsProcessList mit $filename \n";
        $wmics=array();
        $fileOps = new fileOps($filename);
        $result = $fileOps->readFileFixed("UTF-16",[],1000,$debug);           // First Line selbst finden
        foreach ($result as $entry)
            {
            if (isset($entry["Caption"]))
                {
                if (strtolower($entry["Caption"])==="java.exe") 
                    {
                    //print_R($entry);
                    $commandlets = explode(" ",$entry["CommandLine"]);
                    //print_r($commandlets);
                    foreach ($commandlets as $i => $command) 
                        {
                        if ( ($i>0) && (strlen($command)>1) && (substr($command,0,1) != "-") ) $wmics[]=$command;
                        }
                    }
                else $wmics[]=$entry["Caption"];
                }
            }
        return($wmics);
        }

    /*****************************************************************
     * sysOps, von checkProcess verwendet
     * die aktuell gestarteten Programme werden erfasst
     * entweder Abfrage selbst, oder aus einem Filenamen
     */

    private function getTaskList($filename=false,$debug=false)
        {
        //$debug=true;
        $dosOps = new dosOps();    
        $systemDir     = $dosOps->getWorkDirectory();           
        $taskList=array();
        $result="";
        if ($filename)  
            {
            if (file_exists($filename))
                {
                $handle4=fopen($filename,"r");
                $i=0;
                if ($debug) echo "getTaskList, die aktuell gestarteten Programme werden aus der Datei $filename erfasst.\n";
                while (($line=fgets($handle4)) !== false) 
                    {
                    if ($debug) echo "$i | ".strlen($line)." | $line\n";
                    $result .= $line;
                    if ($i++>10000) break;
                    }
                fclose($handle4);
                }
            else if ($debug) echo "getTaskList, die aktuell gestarteten Programme können NICHT aus der Datei $filename erfasst werden.\n";
            }
        else    
            {
            if ($debug) echo "getTaskList, die aktuell gestarteten Programme werden erfasst.\n";
            else $result=IPS_EXECUTE("c:/windows/system32/tasklist.exe","", true, true);                    // vorerst nur aufrufen wenn nicht im Debug Mode
            }
        //echo $result;

        //$trans = array("\x0D\x0A" => "\x0D");
        //$result = strtr($result,$trans);
        $handle=fopen($systemDir."tasks.txt","w");
        fwrite($handle,$result);
        fclose($handle);

        $firstLine=0;
        $ergebnis=explode("\x0A",$result);
        //print_r($ergebnis);
        foreach ($ergebnis as &$resultvalue)
            {
            //echo $resultvalue;
            if ($firstLine<3)
                {
                $pos=strpos($resultvalue,'Abbildname');
                if ($pos === false)
                    {
                    }
                else
                    {
                    $posAbbild=$pos;
                    $posPID=strpos($resultvalue,'PID')-5;
                    $posSitzung=strpos($resultvalue,'Sitzungsname');
                    $posSitzNr=strpos($resultvalue,'Sitz.-Nr.')-2;
                    $posSpeicher=strpos($resultvalue,'Speichernutzung');

                    //echo 'Abbildname    : '.$posAbbild."\n";
                    //echo 'PID           : '.$posPID."\n";
                    //echo 'Sitzung       : '.$posSitzung."\n";
                    //echo 'SitzungsNr    : '.$posSitzNr."\n";
                    //echo 'Speicher      : '.$posSpeicher."\n";
                    }
                }
            else
                {
                $value=$resultvalue;
                $resultvalue=array();
                $resultvalue['Abbildname']=trim(substr($value,$posAbbild,$posPID));
                $resultvalue['PID']=rtrim(substr($value,$posPID,$posSitzung-$posPID));
                $resultvalue['Sitzung']=rtrim(substr($value,$posSitzung,$posSitzNr-$posSitzung));
                $resultvalue['ExecutablePath']=rtrim(substr($value,$posSitzNr,$posSpeicher-$posSitzNr));
                $resultvalue['ExecutionState']=rtrim(substr($value,$posSpeicher,15));
                }
            $firstLine+=1;
            }
        unset($resultvalue);
        // if ($debug) print_r($ergebnis);

        foreach ($ergebnis as $valueline)
            {
            if (isset($valueline['Abbildname'])==true)
                {
                $process=$valueline['Abbildname'];
                //echo "**** ".$process."\n";
                if (($process=="svchost.exe") or ($process=="lsass.exe") or ($process=="csrss.exe") or ($process=="SMSvcHost.exe") or ($process=="WmiPrvSE.exe")  )
                    {
                    $taskList[]=$process;                        // oder rausnehmen
                    }
                else
                    {
                    $taskList[]=$process;
                    }
                }
            }
        return ($taskList);
        }

    /*****************************************************************
     * sysOps, von checkProcess verwendet
     * die aktuell gestarteten Java Programme werden erfasst
     *
     */
    public function getJavaList($filename=false,$debug=false)
        {
        if ($debug) echo "getJavaList($filename,... aufgerufen.\n";
        $lines=0;
        $javas=array();
        if ($filename)  
            {
            if (file_exists($filename))
                {            
                $handle4=fopen($filename,"r");
                if ($debug) echo "Java Processe die aktiv sind, lese $filename : \n";
                $javas=array();
                while (($result=fgets($handle4)) !== false) 
                    {
                    $lines++;
                    $java=explode(" ",$result);
                    if ($debug)
                        {
                        if ($lines==1) echo "Textformat ".mb_detect_encoding($result)."\n";
                        if ($lines<10) echo count($java)." Spalten : $result \n";
                        }
                    $javas[$java[0]]=trim($java[1]);
                    }
                fclose($handle4);
                }
            }
        //print_R($javas);
        if ($debug) echo "In total $lines Lines had been read and ".count($javas)." processes had been found.\n";
        return ($javas);
        }

    /* get result from systeminfo aufruf
     */
    private function getSystemInfo($filename=false,$debug=false)
        {
        if ($debug) echo "getSystemInfo($filename,... aufgerufen.\n";
        $lines=0; $result="";
        if ($filename)  
            {
            if (file_exists($filename))
                {            
                $handle4=fopen($filename,"r");
                while (($iline=fgets($handle4)) !== false) 
                    {
                    $line = iconv('cp437', 'utf-8', $iline);
                    $type=mb_detect_encoding($iline,"auto",true);
                    //if ($debug) echo str_pad($i,2)." | ".strlen($line)." | $type | $line";
                    $result .= $line;
                    if ($lines++>10000) break;
                    }
                fclose($handle4);
                }
            }
        if ($debug) echo "In total $lines Lines had been read.\n";
        return ($result);
        }

    /***********************************************************************************
     *
     * sysOps::getProcessListFull
     * eine Liste der aktuell aktiven Prozesse auslesen, funktioniert nur für Windows
     * auch Java jdk berücksichtigen, wird von Watchdog Library getActiveProcesses aufgerufen
     * es wird ein array aus filenamen übergeben, alles Ergebnisse von process Abfragen
     * Verschiedene Typen:
     *      Tasklist
     *      WmicsProcesslist
     *
     * ruft folgende Routinen auf:
     *      getSystemInfo, SystemInfo
     *      getTaskList
     *      getWmicsProcessList   
     *      getProcessList
     *      getJavaList
     *
     */


    public function getProcessListFull($filename=array(),$debug=false)
        {
        if ($debug) 
            {
            echo "Aufruf getProcessListFull, die folgenden Dateien auswerten:\n";
            print_R($filename);
            }
        $tasklist=array(); $process=array(); $javas=array(); $wmics=array(); 
        if (isset($filename["SystemInfo"])) 
            {
            if ($filename["SystemInfo"] !== false) $systeminfo = $this->getSystemInfo($filename["SystemInfo"], $debug);
            $subnet="10.255.255.255";
            $OperationCenter=new OperationCenter($subnet);              // ein Router zum Datenauslesen, sonst Ausgabe "config Router empty."
            $OperationCenter->SystemInfo($systeminfo,$debug);           // verwende file Input
            }
        if (isset($filename["Tasklist"])) 
            {
            if ($filename["Tasklist"] !== false) $tasklist = $this->getTaskList($filename["Tasklist"], $debug);
            if ($debug) echo "Tasklist in ".$filename["Tasklist"]." mit ".sizeof($tasklist)." Zeilen gefunden. File Size : ".$this->getNiceFileSize(filesize($filename["Tasklist"]))." Last modified: ".date("d.m.Y H:i:s",filemtime($filename["Tasklist"]))."\n";
            }
        if (isset($filename["Processlist"]))        // wmic Format Abfrage
            {
            if ($filename["Processlist"] !== false) $process = $this->getWmicsProcessList($filename["Processlist"],$debug);            // bessere Abfrage, einheitliches Format        
            if ($debug) echo "Processlist in ".$filename["Processlist"]." mit ".sizeof($process)." Zeilen gefunden. File Size : ".$this->getNiceFileSize(filesize($filename["Processlist"]))." Last modified: ".date("d.m.Y H:i:s",filemtime($filename["Processlist"]))."\n";
            }
        /*else 
            {
            $process = $this->getProcessList();
            if ($debug) echo "Default Abfrage. Processlist ".sizeof($process)." Zeilen gefunden.\n";
            }*/
        if ( (isset($filename["Javalist"])) && ($filename["Javalist"] !== false) ) 
            {
            $javas = $this->getJavaList($filename["Javalist"],$debug);
            if ($debug) echo "Javalist in ".$filename["Javalist"]." mit ".sizeof($javas)." Zeilen gefunden. File Size : ".$this->getNiceFileSize(filesize($filename["Javalist"]))." Last modified: ".date("d.m.Y H:i:s",filemtime($filename["Javalist"]))."\n";
            }    
        if ( (isset($filename["Wmiclist"])) && ($filename["Wmiclist"] !== false) ) 
            {
            $wmics = $this->getWmicsProcessList($filename["Wmiclist"],$debug);
            if ($debug) echo "Wmiclist in ".$filename["Wmiclist"]." mit ".sizeof($wmics)." Zeilen gefunden. File Size : ".$this->getNiceFileSize(filesize($filename["Wmiclist"]))." Last modified: ".date("d.m.Y H:i:s",filemtime($filename["Wmiclist"]))."\n";
            }
        //print_r($wmics);

        $processes = array_merge($tasklist,$process,$javas,$wmics);            
        sort($processes,SORT_NATURAL | SORT_FLAG_CASE);
       
        // gleiche Prozesse aus der Tabelle nehmen
        $processesFound=array();
        $prevProcess="";
        foreach ($processes as $process)
            {
            //print_r($process);
            if ($prevProcess != $process)
                {
                $prevProcess = $process;
                $processesFound[]=$process;
                }
            }
        return($processesFound);
        }

    /***********************************************************************************
     *
     * sysOps, eine Liste der aktuell aktiven Prozesse auslesen
     * die Prgramme die in processStart übergeben wurden, überprüfen ob sie enthalten sind
     * wenn eine Prozessliste übergeben wird werden diese verwendet
     *
     */

    public function checkProcess($processStart, $processesFound=array(), $debug=false)
        {
        $init=true;
        if (sizeof($processesFound)>0)
            {
            if ($debug) echo "checkprocess für ".sizeof($processesFound)." Prozesse aufgerufen:\n";                
            foreach ($processesFound as $process)
                {
                foreach ($processStart as $key => &$start)
                    {
                    $length=strlen($key);
                    $processEach=substr($process,0,$length);
                    //if ($init) echo "$processEach versus $key\n";                        
                    if ( ($processEach==$key) || (strtoupper($processEach)==strtoupper($key)) )
                        {
                        $start="Off";
                        if ($debug) echo "   $process, start=off.\n"; 
                        }
                    }
                $init=false;                    
                unset($start);
                }
            }
        else
            {
            $processes=$this->getProcessList();
            sort($processes);
            if ($debug) print_r($processes);

            foreach ($processes as $process)
                {
                foreach ($processStart as $key => &$start)
                    {
                    if ( ($process==$key) || (strtoupper($process)==strtoupper($key)) )
                        {
                        $start="Off";
                        }
                    }
                unset($start);
                }
            //print_r($processStart);

            $processes=$this->getTaskList();
            sort($processes);
            if ($debug) print_r($processes);        
            foreach ($processes as $process)
                {
                foreach ($processStart as $key => &$start)
                    {
                if ($process==$key)
                    {
                    $start="Off";
                    }
                    }
                unset($start);
                }
            }                
        return($processStart);
        }

    /********
     *
     * sysOps, getNiceFileSize, formatSize
     *
     * zum Formattieren von Byte Angaben, beide Routinen machen das selbe auf ähnliche Weise
     * formatSize arbeitet immer mit 1024, getNicefileSize unterscheidet, da scheinbar jetzt Mega, Giga, Terra wieder auf mehrfache von 1000 gehen
     *
     *********************/

    function getNiceFileSize($bytes, $binaryPrefix=true) 
        {
        if ($binaryPrefix) 
            {
            $unit=array('B','KiB','MiB','GiB','TiB','PiB');
            if ($bytes==0) return '0 ' . $unit[0];
            return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
            } 
        else 
            {
            $unit=array('B','KB','MB','GB','TB','PB');
            if ($bytes==0) return '0 ' . $unit[0];
            return @round($bytes/pow(1000,($i=floor(log($bytes,1000)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
            }
        }

    /* sysOps, formatSize
     */
    function formatSize($value,$komma=2)
        {
        if ($value <1024) $result = number_format($value,2). " Byte";
        elseif ($value <(1024*1024)) $result = number_format(($value/1024),2)." kByte";
        elseif ($value <(1024*1024*1024)) $result = number_format(($value/1024/1024),2)." MByte";
        elseif ($value <(1024*1024*1024*1024)) $result = number_format(($value/1024/1024/1024),2)." GByte";
        else $result = number_format(($value/1024/1024/1024/1024),2)." TByte";
        
        return $result;
        }

    /********
     * sysOps
     * Returns used memory (either in percent (without percent sign) or free and overall in bytes)
     *
     *****************************/

    function getServerMemoryUsage($getPercentage=true)
        {
        $memoryTotal = null;
        $memoryFree = null;

        if (stristr(PHP_OS, "win")) 
            {
            // Get total physical memory (this is in bytes)
            $cmd = "wmic ComputerSystem get TotalPhysicalMemory";
            @exec($cmd, $outputTotalPhysicalMemory);

            // Get free physical memory (this is in kibibytes!)
            $cmd = "wmic OS get FreePhysicalMemory";
            @exec($cmd, $outputFreePhysicalMemory);

            if ($outputTotalPhysicalMemory && $outputFreePhysicalMemory) 
                {
                // Find total value
                foreach ($outputTotalPhysicalMemory as $line) 
                    {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) 
                        {
                        $memoryTotal = $line;
                        break;
                        }
                    }

                // Find free value
                foreach ($outputFreePhysicalMemory as $line) 
                    {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) 
                        {
                        $memoryFree = $line;
                        $memoryFree *= 1024;  // convert from kibibytes to bytes
                        break;
                        }   
                    }
                }   
            }
        else
            {
            if (is_readable("/proc/meminfo"))
                {
                $stats = @file_get_contents("/proc/meminfo");

                if ($stats !== false) 
                    {
                    // Separate lines
                    $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                    $stats = explode("\n", $stats);

                    // Separate values and find correct lines for total and free mem
                    foreach ($stats as $statLine) 
                        {
                        $statLineData = explode(":", trim($statLine));

                        //
                        // Extract size (TODO: It seems that (at least) the two values for total and free memory have the unit "kB" always. Is this correct?
                        //

                        // Total memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemTotal") 
                            {
                            $memoryTotal = trim($statLineData[1]);
                            $memoryTotal = explode(" ", $memoryTotal);
                            $memoryTotal = $memoryTotal[0];
                            $memoryTotal *= 1024;  // convert from kibibytes to bytes
                            }

                        // Free memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemFree") 
                            {
                            $memoryFree = trim($statLineData[1]);
                            $memoryFree = explode(" ", $memoryFree);
                            $memoryFree = $memoryFree[0];
                            $memoryFree *= 1024;  // convert from kibibytes to bytes
                            }
                        }
                    }
                }
            }

        if (is_null($memoryTotal) || is_null($memoryFree)) 
            {
            return null;
            } 
        else 
            {
            if ($getPercentage) 
                {
                return (100 - ($memoryFree * 100 / $memoryTotal));
                } 
            else 
                {
                return array( "total" => $memoryTotal, "free" => $memoryFree, );
                }
            }
        }

    /* sysOps
     *
     *
     **************/

    public function readHardDisk($debug=false)
        {
        /* 0 = Unknown
        1 = No Root Directory
        2 = Removable Disk
        3 = Local Disk
        4 = Network Drive
        5 = Compact Disc
        6 = RAM Disk

        / options of get
        ohne	nette Tabelle, lesbar für Menschen, ohne besonderes Trennzeichen , schaut aus wie bei /all
        /value  schreibt die Objekt=Wert Paare untereinander, mehrere Leerzeilen als trennung zum nächsten Objekt
        
        */

        $festplatte=array();
        $id=0;
        exec('wmic logicaldisk get deviceid, volumename, description, drivetype, freespace, size',$catch);
		$head=true;
		foreach($catch as $line)
			{
			if ($debug) echo $line."\n";
			//$result=explode(" ",$line); print_r($result);
			
			// zerlegt die Zeichenkette an Stellen mit beliebiger Anzahl von
			// Kommata oder Leerzeichen, die " ", \r, \t, \n und \f umfassen
			$schluesselwoerter = preg_split("/[\s,]+/", $line);
			//print_r($schluesselwoerter);
			if ($head) 
				{
				$index=$schluesselwoerter;
				$head=false;
				}
			else
				{
				foreach ($schluesselwoerter as $num => $schluesselwort)
					{
					if ( strpos($schluesselwort,":")==1) 
						{
						if ($debug) echo "     ->DeviceId gefunden auf $num.\n"; 
						$j=0;
						$description="";
						for ($i=0;$i<sizeof($schluesselwoerter);$i++)
							{
							if ($i < $num) $description .=$schluesselwoerter[$i];
							elseif ($i==$num)
								{
								$festplatte[$id][$index[$j++]]=$description;
								$festplatte[$id][$index[$j++]]=$schluesselwoerter[$i];
								}
							else $festplatte[$id][$index[$j++]]=$schluesselwoerter[$i];
							}
						}
					}
				$id++;
				}
			}

        if ($debug) print_r($festplatte);

        foreach ($festplatte as $entry)
            {
            if ( ($entry["DriveType"]>1) && ($entry["DriveType"]<5) )
                {
                echo "   ".$entry["DeviceID"]."  ";
                if (isset($entry["Size"]))
                    {
                    $size=$entry["Size"];
                    $free=$entry["FreeSpace"];
                    echo "  ".$this->formatSize($free,2)." from ".$this->formatSize($size,2)." free. Empty ".number_format(($free/$size)*100,2)."%\n";
                    }
                else echo "\n";
                }
            }
        return($festplatte);
        }


    } // ende class sysOps

/*****************************************************************
 *
 */

class phpOps
    {

    public $classes=array();

    public function load_classes()    // get class hierarchy
        {
        $childrens = array();
        foreach(get_declared_classes() as $class)
            {  
            $childrens[$class]["Parent"]=get_parent_class($class);
            }
        ksort($childrens);
        foreach ($childrens as $child => $data)
            {
            if ( (isset($data["Parent"])) && ($data["Parent"] != "") )
                {
                $parent = $data["Parent"];
                $childrens[$parent]["Childrens"][]=$child;    
                }   
            }
        $this->classes=$childrens;
        }    
    
    /* nicht optimal dass sich das Format ändert
     */
    public function filter_classes($filter="DetectHandler",$debug=false)
        {
        $indent=""; $output=array();               // filter geht nur auf root items
        foreach ($this->classes as $child => $data)
            {
            if ($child==$filter)
                {
                if ( (isset($data["Parent"])) && ($data["Parent"] == "") )          // es ist ein root parent, weitermachen
                    {
                    if ($debug) echo $indent.str_pad($child,55)."  \n";
                    $output["."]=$child;
                    if (isset($data["Childrens"]))                                  // der children hat
                        {
                        foreach ($data["Childrens"] as $index => $nextchild)
                            { 
                            $output[$nextchild] = $this->treeform($this->classes,$nextchild,$indent."  ");
                            }
                        }
                    }
                }
            }
        $this->classes=$output;
        }

    function treeform($childrens,$nextchild,$indent)
        {
        $output=array();
        $data=$childrens[$nextchild];
        $output["."]=$nextchild;
        //echo $indent.str_pad($nextchild,55)."  \n";
        if (isset($data["Childrens"]))                                  // der children hat
            {
            foreach ($data["Childrens"] as $index => $nextchild)
                { 
                $output[$nextchild] = $this->treeform($childrens,$nextchild,$indent."  ");
                }
            }
        return ($output);
        }

    public function get_classes()
        {
        return ($this->classes);
        }

    }


/*****************************************************************
 *
 * verschiedene Routinen im Zusammenhang mit File Operationen
 *
 *  getWorkDirectory            Windows ist es C:/scripts/
 *  replaceWorkDirectory        not implemented
 *  getUserDirectory
 *  evaluateOperatingSystem
 *  getOperatingSystem          anhand der Logging Konfiguration herausfinden welches Betriebssystem
 *
 *  setMaxScriptTime
 *  fileIntegrity               überprüfen ob das File den php Koventionen entspricht
 *
 *  findfiles                   wie fileavailable, aber hier aus einem array die Dateien herausfiltern
 *  fileAvailable               eine Datei in einem Verzeichnis suchen, auch mit Wildcards
 *  dirAvailable                ein Verzeichnis in einem oder mehreren Verzeichnissen finden
 *  mkdirtree
 *  latestChange
 *  readdirToArray              ein Verzeichnis samt Unterverzeichnisse einlesen und als Array zurückgeben
 *     dirToArray                  verwendet für das rekursive aufrufen
 *  readdirtoStat               nur statistische Informationen über das Verzeichnis zurückmelden
 *     dirToStat                   verwendet für das rekursive aufrufen
 *  writeDirStat
 *  correctDirName              einem Verzeichnisbaum ein Backslash oder Slash anhängen, sonst wäre die letzte Position eventuell auch eine Datei
 *  convertDirName              to DOS or LINUX style
 *  rrmdir                      ein Verzeichnis rekursiv loeschen
 *  readFile                    eine Datei ausgeben
 *  deleteFile
 *  moveFile
 *  copyIfNeeded
 *  readSourceDir
 *  readDirtoCheck
 */

class dosOps
    {


    /*  dosOps, C: scripts kann nicht auf allen Rechnern verwendet werden. 
     *  parametrierbar machen. Unterschiede Unix und Windows rausarbeiten
     * Auf einer Unix Maschine (Docker)
     *      Kernel Dir seit IPS 5.3. getrennt abgelegt : /var/lib/symcon/
     *      Kernel Install Dir ist auf : /usr/share/symcon/
     *      Kernel Working Directory ist auf :  /var/script/symcon/
     *
     */

    public function getWorkDirectory()
        {
        IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');	                
        $logging=new Logging();
        $config=$logging->get_IPSComponentLoggerConfig();
        //echo "GetWorkDirectory: .json_encode($config["BasicConfigs"])."\n";
        $verzeichnis=$config["BasicConfigs"]["SystemDir"];
        if ($verzeichnis != "")
            {
            $ls=$this->readdirToArray($verzeichnis);
            if ($ls===false) echo "********Fehler Verzeichnis $verzeichnis nicht vorhanden.\n";
            }
        /*  $verzeichnis="C:/scripts/";
            $ls=$this->readdirToArray($verzeichnis);
            if ($ls===false) 
                {
                echo "    UNIX System. Anderes privates Verzeichnis.\n";
                $verzeichnis="/var/script/symcon/";
                $ls=$this->readdirToArray($verzeichnis);
                if ($ls===false) echo "   Fehler, Docker Container Pfad nicht richtig konfiguriert.\n";
                }       */
        return($verzeichnis);
        }

    /* dosOps, not implemented now
     */
    public function replaceWorkDirectory()
        {

        }

    /* dosOps, noch ein typisches Verzeichnis, das des Users
     */
    public function getUserDirectory()
        {
        IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');	                
        $logging=new Logging();
        $config=$logging->get_IPSComponentLoggerConfig();
        //echo "GetWorkDirectory: .json_encode($config["BasicConfigs"])."\n";
        $verzeichnis=$config["BasicConfigs"]["UserDir"];
        if ($verzeichnis != "")
            {
            $ls=$this->readdirToArray($verzeichnis);
            if ($ls===false) echo "********Fehler Verzeichnis $verzeichnis nicht vorhanden.\n";
            }
        /*  $verzeichnis="C:/scripts/";
            $ls=$this->readdirToArray($verzeichnis);
            if ($ls===false) 
                {
                echo "    UNIX System. Anderes privates Verzeichnis.\n";
                $verzeichnis="/var/script/symcon/";
                $ls=$this->readdirToArray($verzeichnis);
                if ($ls===false) echo "   Fehler, Docker Container Pfad nicht richtig konfiguriert.\n";
                }       */
        return($verzeichnis);
        }

    /* dosOps::evaluateOperatingSystem
     *  Anhand von einer Configuration oder
     *  durch Test von C:/Scripts herausfinden ob Unix oder Windows system
     echo IPS_GetKernelDir();
        // Beispielausgabe:
        // Windows
        // ab Version 5.3
        C:\ProgramData\Symcon\
        // bis Version 5.2
        C:\Programme\IP-Symcon\

        // Linux, RaspberryPi
        /var/lib/symcon/
        
        // MacOS
        /Library/Application Support/Symcon/
     */
    public function evaluateOperatingSystem()           // eigene Routine, sonst gibt es einen Kreisläufer bei Logging
        {
        $directory=IPS_GetKernelDir();
        //echo "getOperatingSystem from this directory $directory:\n";                
        $pos1=strpos($directory,"/");
        if ($pos1===0) return("UNIX");          // nur Linux hat das / am Anfang
        else return("WINDOWS");
        }

    /* dosOps::getOperatingSystem
     * anhand der Logging Konfiguration herausfinden welches Betriebssystem
     *
     */
    public function getOperatingSystem()
        {
        IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');	                
        $logging=new Logging();
        $config=$logging->get_IPSComponentLoggerConfig();
        switch (strtoupper($config["BasicConfigs"]["OperatingSystem"]))
            {
            case "WINDOWS":
                return("WINDOWS");
                break;           
            case "UNIX":
                return("UNIX");
                break;           
            default:
                echo "dosOps::getOperatingSystem, Error, do not know ".$basicConfigs["OperatingSystem"].".\n";
                return (false);
                break;
            }
        }


    /* dosOps::getKernelPlattform 
     * anhand der Symcon Information herausfinden welches Betriebssystem
     * kapselt detailliertere Symcon Funktion
     */
    public static function getKernelPlattform()
        {
        switch (strtoupper(IPS_GetKernelPlatform()))
            {
            case "WINDOWS":
                return("WINDOWS");
                break;           
            case "UNIX":
            case "SYMBOX":
            case "UBUNTU":
            case "UBUNTU (DOCKER)":
            case "RASPERRY PI":
            case "RASPERRY PI (DOCKER)":
            case "MAC":
                return("UNIX");
                break;           
            default:
                echo "dosOps::getKernelPlattform, Error, do not know ".IPS_GetKernelPlatform().".\n";
                return (false);
                break;
            }
        }

    /* dosOps, ini_set funktioniert bei Linux Systemen scheinbar nicht mehr, daher hier zentralisiseren
     */
    public function setMaxScriptTime($time)
        {
        if ($this->evaluateOperatingSystem()=="WINDOWS")
            {
            ini_set('max_execution_time', $time);          // max. Scriptlaufzeit definieren
            }
        }

    /*
     * dosOps, überprüfen ob das File den php Koventionen entspricht
     */
    function fileIntegrity($fullDir,$fileName)
        {
        $dir = $this->readdirToArray($fullDir);
            //echo $fullDir."\n";
            //print_r($dir);
        $key = array_search ($fileName,$dir);
        //echo "Filename EvaluateHardware_Configuration.inc.php gefunden auf Pos $key \n";
        $fileNameFull = $fullDir.$fileName;
        $fileContent = file_get_contents($fileNameFull, true);
        //echo $fileContent;

        $search1='<?php';
        $search2='?>';
        $pos1 = strpos($fileContent, $search1);
        $pos2 = strpos($fileContent, $search2);

        /* echo "\n=================================\n";
        echo "Gefunden wurde $pos1 und $pos2.\n";   */

        if (($pos1 === false) || ($pos2 === false)) return (false);
        else return (true);
        }

    /* compare function from github copilot
     * the two input files need to exist
     * the compare ignore the line endings and the white spaces
     *
     */
    public function fileCompare($file1,$file2,$diffFile, $debug=false) 
        {
        if (!file_exists($file1) || !file_exists($file2)) {
            throw new Exception("One or both files do not exist.");
        }

        $content1 = file($file1);
        $content2 = file($file2);

        if ($content1 === false || $content2 === false) {
            throw new Exception("Error reading one or both files.");
        }
        
        // Normalize line endings and trim whitespace
        $content1 = array_map('trim', str_replace(array("\r\n", "\r", "\n"), "\n", $content1));
        $content2 = array_map('trim', str_replace(array("\r\n", "\r", "\n"), "\n", $content2));

        $diff = array_diff($content1, $content2);
        //echo "fileCompare $file1 ".sizeof($content1)." $file2 ".sizeof($content2)." $diffFile ".sizeof($diff)."\n";
        if ($debug) echo "fileCompare ".sizeof($content1)." --> ".sizeof($content2)." : ".sizeof($diff)."\n";

        /*
        if (empty($diff)) {
            return "Files are identical.";
        } else {
            file_put_contents($diffFile, implode("", $diff));
            return "Differences written to " . $diffFile;
            }  */
        return $diff;
        }

    /* dosOps::findfiles, wie fileavailable, aber hier aus einem array die Dateien herausfiltern
     * kann Wildcards *, *.
     * files ist ein array, index=>filename
     * Ergebnis is filestoread
     *
     */
    function findfiles($files,$filename,$debug=false)
        {
        $filesToRead=false;   
        $ignoreCase=true;         
        if (trim($filename)=="*") 
            {
            if ($debug) echo "findfiles, alle Dateien nehmen : ".json_encode($files)."\n";
            $filesToRead=$files;
            //print_R($files);                                 
            }
        else
            {
            $pos=strpos($filename,"*.");
            if ( $pos === false )
                {
                if ($debug) echo "findfiles, fileAvailable: wir suchen nach dem Filenamen \"".$filename."\"\n";
                $detName=true;
                $detExt=false;
                }
            else
                {
                $filename=substr($filename,$pos+1,20);
                if ($debug) echo "findfiles, fileAvailable: wir suchen nach der Extension \"*".$filename."\"\n";
                $detExt=true;
                }
            foreach ($files as $fileEntry)
                { 
                if (is_array($fileEntry))
                    {
                    if (isset($fileEntry["Filename"])) $file=$fileEntry["Filename"];
                    else $file="unknown";
                    }   
                else $file=$fileEntry;                        
                if ($detExt == false)
                    {
                    /* Wir suchen einen Filenamen */
                    if ($file == $filename)
                        {
                        $status=true;
                        $filesToRead[]=$file;
                        }
                    //echo $file."\n";
                    }
                else
                    {
                    /* Wir suchen eine Extension, filename ist die extension */
                    //echo $file."\n";
                    if ($ignoreCase) $pos = stripos($file,$filename);
                    else
                        {
                        $pos = strpos($file,$filename);
                        //if ($pos===false) $pos = strpos(strtoupper($file),strtoupper($filename));
                        }
                    if ( ($pos > 0 ) )
                        {
                        $len = strlen($file)-strlen($filename)-$pos;
                        //echo "Filename \"".$file."\" gefunden. Laenge Extension : ".$len." ".$pos."\n";
                        if ( $len == 0 ) 
                            {
                            $status=true; 
                            $filesToRead[]=$file;                                            
                            }
                        }
                    } 
                }    
            }                       
        return ($filesToRead);
        }


    /* dosOps::findfilesRecursive
     * wie findfiles aber rekursive Implementierung
     * für array 
     */
    function findFilesRecursive(&$result,$path,$list,$target="*",$indent="",$debug=false)
        {
        $pos=strpos($target,"*.");
        if ( $pos === false )
            {
            // echo "findItem, wir suchen nach dem Filenamen \"".$target."\"\n";
            $detExt=false;
            }
        else
            {
            $targetext=substr($target,$pos+1,20);
            // echo "findItem, wir suchen nach der Extension \"*".$target."\"\n";
            $detExt=true;
            $ignoreCase=true;
            }            
        foreach ($list as $dir => $item)
            {   
            if (is_array($item)) 
                {
                if ($debug) echo $indent."$dir \n";
                $this->findFilesRecursive($result,$path.DIRECTORY_SEPARATOR.$dir,$item,$target,$indent."  "); 
                }
            else 
                {
                $count=sizeof($result);
                if ($detExt)
                    {
                    /* Wir suchen eine Extension, filename ist die extension */
                    if ($ignoreCase) $pos = stripos($item,$targetext);
                    else
                        {
                        $pos = strpos($item,$targetext);
                        //if ($pos===false) $pos = strpos(strtoupper($file),strtoupper($filename));
                        }
                    if ( ($pos > 0 ) )
                        {
                        $len = strlen($item)-strlen($targetext)-$pos;
                        //echo "Filename \"".$file."\" gefunden. Laenge Extension : ".$len." ".$pos."\n";
                        if ( $len == 0 ) 
                            {
                            $result[$count]["Filename"]=$item;
                            $result[$count]["Path"]=$path;
                            if ($debug) echo $indent."$count $path".DIRECTORY_SEPARATOR."$item \n";                                        
                            }
                        }
                    } 
                elseif ( ($item==$target) || (trim($target)=="*") )
                    {   
                    $result[$count]["Filename"]=$item;
                    $result[$count]["Path"]=$path;
                    if ($debug) echo $indent."$count $path".DIRECTORY_SEPARATOR."$item \n";
                    }
                }
            }
        }

    /* dosOps::fileAvailable
     *
     * einen Filenamen , auch mit Wildcards, in einem Verzeichnis suchen
     * liefert status true und false zurück
     *
     */    
    function fileAvailable($filename,$verzeichnis="",$debug=false)
        {
        if ($verzeichnis=="") 
            {
            if ($debug) echo "fileAvailable $filename aufgerufen.\n";
            $posSlash=strrpos($filename,'/');
            $posBackSlash=strrpos($filename,'\\');
            if ($posSlash !== false)  
                {
                //echo $posSlash;
                $name=substr($filename,$posSlash+1);
                $dir=substr($filename,0,$posSlash+1);
                }
            elseif ($posBackSlash !== false)  
                {
                //echo $posBackSlash;
                $name=substr($filename,$posBackSlash+1);
                $dir=substr($filename,0,$posBackSlash+1);
                }
            else echo "Fehler, kein Verzeichnis angegeben, oder gefunden.\n";
            //echo "Name \"$name\" Dir \"$dir\" \n";
            $filename=$name; $verzeichnis=$dir;
            }   
        $status=false;
        /* Wildcards beim Filenamen zulassen */
        $pos=strpos($filename,"*.");
        if ( $pos === false )
            {
            if ($debug) echo "fileAvailable: wir suchen nach dem Filenamen \"".$filename."\"\n";
            $detName=true;
            $detExt=false;
            }
        else
            {
            $filename=substr($filename,$pos+1,20);
            if ($debug) echo "fileAvailable: wir suchen nach der Extension \"*".$filename."\"\n";
            $detExt=true;
            }
        if ( is_dir ( $verzeichnis ))
            {
            if ($debug) echo "   Öffnen des Verzeichnisses $verzeichnis:\n";
            if ( $handle = opendir($verzeichnis) )
                {
                while (($file = readdir($handle)) !== false)
                    {
                    $dateityp = @filetype( $verzeichnis.$file );            // seit Win11 gibt es neue Fileformate die noch nicht unterstützt werden
                    if ($dateityp == "file")
                        {
                        if ($detExt == false)
                            {
                            /* Wir suchen einen Filenamen */
                            if ($file == $filename)
                                {
                                $status=true;
                                }
                            //echo $file."\n";
                            }
                        else
                            {
                            /* Wir suchen eine Extension */
                            //echo $file."\n";
                            $pos = strpos($file,$filename);
                            if ( ($pos > 0 ) )
                                {
                                $len =strlen($file)-strlen($filename)-$pos;
                                //echo "Filename \"".$file."\" gefunden. Laenge Extension : ".$len." ".$pos."\n";
                                if ( $len == 0 ) { $status=true; }
                                }
                            }
                        }
                    elseif ($dateityp===false) echo "fileAvailable:Fehler, check \"$verzeichnis$file\".\n";
                    } /* Ende while */
                closedir($handle);
                } /* end if dir */
            }/* ende if isdir */
        return $status;
        }

    /* dosOps::dirAvailable
     * input dir Name der gesucht wird, übergeordnetes Verzeichnis als Name oder wenn mehrere als array, debug
     * ein Verzeichnis in einem Verzeichnis suchen
     * liefert status true und false zurück
     *
     */    
    function dirAvailable($filename,$verzeichnisse,$debug=false)
        {
        $status=false;
        if ((is_array($verzeichnisse))===false) $verzeichnisse[]=$verzeichnisse;
        foreach ($verzeichnisse as $verzeichnis)
            {
            $verzeichnis = $this->correctDirName($verzeichnis);
            if ($debug) echo "dirAvailable: wir suchen nach einem Verzeichnis \"".$filename."\" in $verzeichnis.\n";
            if ( is_dir ( $verzeichnis ))
                {
                if ( $handle = opendir($verzeichnis) )
                    {
                    while (($file = readdir($handle)) !== false)
                        {
                        //$stat     = stat( $verzeichnis.$file );         // alle Infos zusammen https://www.php.net/manual/de/function.stat.php
                        //if ($debug) print_R($stat);
                        $dateityp = @filetype( $verzeichnis.$file );            // seit Win11 gibt es neue Fileformate die noch nicht unterstützt werden
                        if ($dateityp == "dir")
                            {
                            if ($debug) echo str_pad($file,40);
                            if ($file == $filename)  
                                {
                                $status=$verzeichnis;
                                if ($debug) echo "found \n";
                                }
                            elseif ($debug) echo "\n";
                            }
                        elseif ( ($dateityp===false) && $debug) echo "dirAvailable:Fehler bei filetype, check \"$verzeichnis$file\".\n";
                        } /* Ende while */
                    closedir($handle);
                    } /* end if dir */
                }/* ende if isdir */
            }   // ende foreach
        return $status;
        }

    /* dosOps::mkdirtree
     * 
     * Einen Verzeichnisbaum erstellen. Routine scheitert wenn es bereits eine Datei gibt, die genauso wie das Verzeichnis heisst. Dafür einen Abbruchzähler vorsehen.
     * alle Backslash auf slash umstellen, wenn kein slash am Schluss den Teil bis zum letzten Slash ignorieren
     * zwei Schleifen inneinander
     * erste Schleife prüft solange bis Verzeichnis vorhanden oder 20 Iterationen vergangen sind
     * zweite Schleife verkürzt den Verzeichnispfad solange bis es möglich ist ein Verzeichnis zu erstellen
     *
     *
     */
    function mkdirtree($directory,$debug=false)
        {
        $directory = str_replace('\\','/',$directory);
        $directory = substr($directory,0,strrpos($directory,'/')+1);
        //$directory=substr($directory,strpos($directory,'/'));
        $i=0;
        while ((!is_dir($directory)) && ($i<20) )
            {
            $i++;
            if ($debug) echo "mkdirtree: erzeuge Verzeichnis $directory \n"; 		
            $newdir=$directory;
            while ( (!is_dir($newdir)) && ($i<20) )         // alle Verzeichnisse nach oben durchgehen bis eines bekannt ist
                {
                $i++;
                //echo "es gibt noch kein ".$newdir."\n";
                if (($pos=strrpos($newdir,"/"))==false) {$pos=strrpos($newdir,"\\");};
                if ($pos==false) break;
                $newdir=substr($newdir,0,$pos);                                             // Zielverzeichnis noch nicht vorhanden, ein verzeichnis weiter oben machen (Parent)
                if ($debug) echo "   Mach : ".$newdir.", Aufruf von mkdir($newdir)\n";
                try
                    {
                    if ($debug) 
                        {
                        @mkdir($newdir);
                        $error = error_get_last(); echo "   Mkdir hat zurück gemeldet: ".$error['message']."  \n";
                        }
                    else @mkdir($newdir);
                    }
                catch (Exception $e) 
                    { 
                    echo "."; 
                    if ($debug) echo "Catch Exception, Fehler bei mkdir($newdir).\n";
                    }
                if (is_dir($newdir)) if ($debug) echo "     Verzeichnis ".$newdir." erzeugt.\n";
                }               // ende while erzeuge Verzeichnis wenn nicht vorhanden
            if ($pos==false) break;
            }
        if ($i >= 20) echo "Fehler bei der Verzeichniserstellung.\n";	
        return(is_dir($directory));              
        }

	/* letzte Änderung in einem Verzeichnis finden 
     * dazu nocheinmal das gesamte Verzeichnis auslesen, geht aber nicht rekursiv
     */
    function latestChange($dir, $recursive=false)
        {
        //echo "LatestChange: mit $dir aufgerufen.\n";
        $latestdate=0;
        $dirs=$this->readdirToArray($dir,false);
        //print_r($dirs);
        foreach ($dirs as $filename)
            {
            $file=$dir.$filename;           // bei Rekurisv kann filename auch ein array sien, dann die selbe Routine rekursiv noch einmal aufrufen
            if (is_dir($file)) 
                {
                // echo "Fehler";
                }
            else
                {
                if ((filemtime($file))>$latestdate) $latestdate=filemtime($file);
                }
            }
        return ($latestdate);
        }

	/* gesammelte Funktionen zur Bearbeitung von Verzeichnissen 
	 * siehe auch writeDirToArray kann beides, ausgeben wenn debug eingestellt und detaillierte Ergebnisse als array zurückgeben
	 * ein Verzeichnis einlesen und als Array zurückgeben 
	 *      dir         Name des Verzeichnisses
     *      config      wenn kein array ist es 
     *          recursive   true, auch die Unterverzeichnisse einlesen 
     *                  wenn ein array gibt es
     *          recursive   default false
     *          detailed    default false, true für detaillierte Auswertung, noch nicht implementiert
     *          dironly     default false, wenn true werden keine files in die Liste übernommen
     *
     *      newest      interressante Funktion, die Dateinamen/Verzeichnisse verkehrt herum sortieren, wenn -n dann nur die ersten n übernehmen, also mit den ältesten Datum
     *                  Achtung damit wird auch die erste Verzeichnisstrukturebene umbenannt und heisst nur mehr 0...x oder 0..n
     *
     * detailed ist noch nicht implementiert
	 */
	public function readdirToArray($dir,$configInput=false,$newest=0,$debug=false)
		{
        $detailed=false;
        //if ($debug) echo "readdirToArray aufgerufen für $dir\n";
        if (is_array($configInput) === false) 
            {
            $recursiveDefault=$configInput;
            $configInput=array();
            }
        else $recursiveDefault=false;
        $config=array();
        if ($debug) echo "readdirToArray($dir,".json_encode($configInput)."\n";
        configfileParser($configInput,$config,["RECURSIVE","recursive","Recursive"],"Recursive",false);
        configfileParser($configInput,$config,["DETAILED","detailed","Detailed"],"Detailed",false);             // nur die angeführten werden übernommen
        configfileParser($configInput,$config,["DIRONLY","dironly","Dironly","DirOnly"],"Dironly",false);             // nur die angeführten werden übernommen
        configfileParser($configInput,$config,["FILTER","filter","Filter"],"Filter",null);             
        $recursive = $config["Recursive"];
        $detailed  = $config["Detailed"];    
        if ($debug) echo "Configuration ist ".json_encode($config)."\n";
	   	$result = array();
		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $dir ) )
			{		
			$cdir = scandir($dir);
			foreach ($cdir as $key => $value)
				{
				if (!in_array($value,array(".","..")))
					{
					if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
						{
						if ($recursive)
							{ 
							//echo "DirtoArray, vor Aufruf (".memory_get_usage()." Byte).\n";					
							$result[$value]=$this->dirToArray($dir . DIRECTORY_SEPARATOR . $value);
							//echo "  danach (".memory_get_usage()." Byte).  ".sizeof($result)."/".sizeof($result[$value])."\n";
							}
						else $result[] = $value;
						//$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
						}
					else
						{
                        if ($config["Dironly"]===false) $result[] = $value;
						}
					}
				} // ende foreach
			} // ende isdir
		else 
            {
            echo "ERROR, Verzeichnis $dir not available. Please create manually.\n";
            return (false);
            }
        if (isset($config["Filter"]))
            {
            if ($debug) echo "Filter aufgerufen : ".json_encode($config["Filter"])."\n";
            $result = $this->findFiles($result,$config["Filter"],$debug);                             // true Debug
            } 
		if ($newest != 0)
			{
			if ($newest<0) 
				{
				rsort($result);
				$newest=-$newest;
				}				
			foreach ($result as $index => $entry)
				{
				if ($index>$newest) unset($result[$index]);
				}
			}
		return $result;
		}		

	/* Routine fürs rekursive aufrufen in readdirtoarray */
	private function dirToArray($dir)
		{
	   	$result = array();
	
		$cdir = scandir($dir);
		foreach ($cdir as $key => $value)
			{
			if (!in_array($value,array(".","..")))
				{
				if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
	         		{
					$result[$value] = $this->dirToArray($dir . DIRECTORY_SEPARATOR . $value);
	         		}
	         	else
	         		{
	            	$result[] = $value;
	         		}
	      		}
	   		}
		return $result;
		}

	/* readdirToStat 
	 *
	 * ein Verzeichnis einlesen und die statistische Auswertzung als Array zurückgeben 
     * wenn recursive true ist werden auch alle Unterverzeichnisse analysiert
	 *
	 */
	public function readdirToStat($dir,$recursive=false)
		{
	   	$result = array();
        $stat=array();
        $stat["files"]=0; 
        $stat["dirs"]=0;
        $stat["latestdate"]=0;

		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $dir ) )
			{
            $this->dirToStat($dir,$stat,$recursive);   
            //if (false)            // Notbremse wie eine grosse Anzahl an Dateien im Verzeichnis war
                {		
                $cdir = scandir($dir);
                foreach ($cdir as $key => $value)
                    {
                    if (!in_array($value,array(".","..")))
                        {
                        if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                            {
                            if ($recursive)
                                { 
                                //echo "DirtoArray, vor Aufruf (".memory_get_usage()." Byte).\n";					
                                $this->dirToStat($dir . DIRECTORY_SEPARATOR . $value,$stat,$recursive);
                                $stat["dirs"]++;
                                //echo "  danach (".memory_get_usage()." Byte).  ".sizeof($result)."/".sizeof($result[$value])."\n";
                                }
                            else $stat["dirs"]++;
                            //$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                            }
                        else
                            {
                            $stat["files"]++;
                            $file = $dir . DIRECTORY_SEPARATOR . $value;
                            $file=str_replace('\\\\','\\',$file);                            
                            if ((filemtime($file))>$stat["latestdate"]) $stat["latestdate"]=filemtime($file);
                            }
                        }
                    } // ende foreach
                }
			} // ende isdir
		else return (false);

		return $stat;
		}		

	/* Routine fürs rekursive aufrufen in readdirtoStat */
	private function dirToStat($dir, &$stat, $recursive)
		{
	   	$result = array();
	    $dir=str_replace('\\\\','\\',$dir);     // keine doppelten Directory Seperators ...
		$cdir = scandir($dir);
		foreach ($cdir as $key => $value)
			{
			if (!in_array($value,array(".","..")))
				{
                $dirfile=str_replace('\\\\','\\',$dir . DIRECTORY_SEPARATOR . $value);     // keine doppelten Directory Seperators ...
				if (is_dir($dirfile))
	         		{
                    if ($recursive)
                        {                          
					    $this->dirToStat($dirfile,$stat, $recursive);
                        $stat["dirs"]++;
                        }
                    else $stat["dirs"]++;                        
	         		}
	         	else
	         		{
	            	$stat["files"]++;
                    $file = $dir . DIRECTORY_SEPARATOR . $value;
                    if ((filemtime($file))>$stat["latestdate"]) $stat["latestdate"]=filemtime($file);
	         		}
	      		}
	   		}
		}

    /* echo über die Struktur eines Directories ausgeben
     * verwendet readdirToArray aus dieser class
     * wenn dirstat true dann werden alle Verzeichnisse rekursiv ausgelesen, braucht sehr lange
     *
     */
    public function writeDirStat($verzeichnis, $dirstat=false)
        {
        $verzeichnis = $this->correctDirName($verzeichnis);
        echo "Verzeichnis : $verzeichnis \n";            
        $files=$this->readdirToArray($verzeichnis);
        foreach ($files as $file)
            {
            echo "  ".str_pad($file,56)." ";
            $dateityp=@filetype( $verzeichnis.$file );                                          // warnings und Fehlermeldungen unterdrücken
            if ($dateityp===false) $dateityp="Warning received";
            //if (is_dir($verzeichnis.$file)) echo "  dir";  else echo "  file";
            echo str_pad($dateityp,12);
            if ($dateityp == "dir")
                {
                if ($dirstat)
                    {
                    $dirSize=$this->readdirtoStat($verzeichnis.$file,true);       // true rekursiv
                    echo " mit insgesamt ".str_pad($dirSize["files"],10, " ", STR_PAD_LEFT)." gespeicherten Dateien.";                                    
                    }
                } 
            if ($dateityp == "file")
                {
                echo nf(filesize($verzeichnis.$file),"Byte",12);                // left padding
                echo "      ".date("d.m.Y H:i:s",filemtime($verzeichnis.$file));
                }
            echo "\n";

            }            
        }

    /* Array über die Struktur eines Directories ausgeben
     * wie readdirToArray aber mit detaillierterer Ausgabe
     * übernimmt die Dateien in files oder ruft selbst die Routine auf 
     */
    public function writeDirToArray($verzeichnis, $files=false, $debug=false)
        {
        $result=array();
        $index=0;
        $verzeichnis = $this->correctDirName($verzeichnis);
        if ($files===false) $files=$this->readdirToArray($verzeichnis);
        if ($debug) echo "writeDirToArray aufgerufen für Verzeichnis $verzeichnis und Dateien ".json_encode($files).":\n";
        foreach ($files as $file)
            {
            $dateityp=@filetype( $verzeichnis.$file );                                          // warnings und Fehlermeldungen unterdrücken
            //echo " $verzeichnis.$file : $dateityp ,";
            if ($dateityp===false) $dateityp="Warning received";
            //if (is_dir($verzeichnis.$file)) echo "  dir";  else echo "  file";
            //echo str_pad($dateityp,12);
            if ($dateityp == "dir")
                {
                $dirSize=$this->readdirtoStat($verzeichnis.$file,true);       // true rekursiv
                //echo str_pad($dateityp,12)."  ".str_pad($file,56)." mit insgesamt ".str_pad($dirSize["files"],10, " ", STR_PAD_LEFT)." gespeicherten Dateien.";                                    
                } 
            if ($dateityp == "file")
                {
                $result[$index]["Filename"]=$file;
                $result[$index]["Verzeichnis"]=$verzeichnis;
                $imageType = exif_imagetype($verzeichnis.$file);
                if ($imageType) 
                    {
                    $result[$index]["Image"]["Type"] = $imageType;
                    switch ($imageType)
                        {
                        case 1:
                            $readable="GIF";
                            break;
                        case 2:
                            $readable="JPEG";
                            break;
                        case 3:
                            $readable="PNG";
                            break;
                        case 4:
                            $readable="SWF";
                            break;
                        case 5:
                            $readable="PSD";
                            break;
                        case 6:
                            $readable="BMP";
                            break;
                        case 7:
                            $readable="TIFF_II";
                            break;
                        case 8:
                            $readable="TIFF_MM";
                            break;
                        case 9:
                            $readable="JPC";
                            break;
                        case 10:
                            $readable="JP2";
                            break;
                        case 11:
                            $readable="JPX";
                            break;
                        case 12:
                            $readable="JB2";
                            break;
                        case 13:
                            $readable="SWC";
                            break;
                        case 14:
                            $readable="IFF";
                            break;
                        case 15:
                            $readable="WBMP";
                            break;
                        case 16:
                            $readable="XBM";
                            break;
                        case 17:
                            $readable="ICO";
                            break;
                        case 18:
                            $readable="WEBP";
                            break;
                        default:                            
                            $readable="Unknown";
                            break;
                        }
                    $result[$index]["Image"]["ImageType"] = $readable; 
                    list($width, $height, $type, $attr) = getimagesize($verzeichnis.$file);
                    $result[$index]["Image"]["Width"] = $width;
                    $result[$index]["Image"]["Height"] = $height;
                    if ($height > $width) $result[$index]["Image"]["Orientation"] = "Portrait";
                    else $result[$index]["Image"]["Orientation"] = "Landscape";
                    }
                $filesize=filesize($verzeichnis.$file);
                $result[$index]["Size"]=$filesize;
                $datetime=filemtime($verzeichnis.$file);
                if ($debug) echo "  ".str_pad($file,56)." ".nf($filesize,"Byte",12)."      ".date("d.m.Y H:i:s",$datetime)."\n";
                $result[$index]["DateTime"]=$datetime;
                $index++;
                }
            }            
        return ($result);
        }


    /* einem Verzeichnisbaum ein Backslash oder Slash anhängen, sonst wäre die letzte Position eventuell auch eine Datei 
     * zusätzliche funktionen
     *     automatische Erkennung Dos oder Linux am Verzeichnisbaum
     */

	function correctDirName($verzeichnis,$debug=false)
		{
		$len=strlen($verzeichnis); 
        $pos1=strrpos($verzeichnis,"\\"); $pos2=strrpos($verzeichnis,"/");          // letzte Position bekommen
        $pos3=strpos($verzeichnis,"\\"); $pos4=strpos($verzeichnis,"/");          // erste Position bekommen
        $pos5=strpos($verzeichnis,":");

        $dos=false; $linux=false;
        if ($pos1 && $pos2)                                 // es gibt sowohl \ als auch /
            {
            if (($pos3===0) || ($pos5==1) ) $dos=true;      // backslash oder Doppelpunkt
            if ($pos4===0) $linux=true;
            }            
        if ($pos5==1) $dos=true;                        //  Doppelpunkt als zweites Zeichen, ebenfall eindeutig DOS

		if ($debug) 
            {
            echo "correctDirName Auswertungen: Len:$len pos1:$pos1 pos2:$pos2 pos3:$pos3 pos4:$pos4 pos5:$pos5\n";			// am Schluss muss ein Backslash oder Slash sein !
            if ($pos1 && $pos2) echo "   mixed usage of / und \\ , Positions of \\ $pos3 and of / $pos4.\n";
            if ($dos) echo "DOS System.\n";
            if ($linux) echo "LINUX System.\n";                
            }
		if ( ($pos1) && ($pos1<($len-1)) )   $verzeichnis .= "\\";          // Backslash kommt im String ausser auf Pos 0 vor, wenn nicht am Ende mit Backslash am Ende erweitern
		if ( ($pos2) && ($pos2<($len-1)) ) $verzeichnis .= "/";		        // Slash kommt im String ausser auf Pos 0 vor, wenn nicht am Ende mit Slash am Ende erweitern

        if ($pos3) $verzeichnis = str_replace("\\\\","\\",$verzeichnis);        // wenn ein Doppelzeichen ausser am Anfang ist dieses vereinfachen
        if ($pos4 !== false) $verzeichnis = str_replace("//","/",$verzeichnis);     // bei Unix kann es auch 0 sein, da slash an erster Position
        
        // finish to correct dir seperator according to Operating System dos/linux
        if ($dos) 
            {
            $verzeichnis = str_replace("/","\\",$verzeichnis);          // Backslash wins
            $verzeichnis = str_replace("\\\\","\\",$verzeichnis);        // aus den Kombis \/ und /\ wird ein Doppel \ dieses auch vereinfachen
            }
        if ($linux) 
            {
            $verzeichnis = str_replace("\\","/",$verzeichnis);              // Slash wins
            $verzeichnis = str_replace("//","/",$verzeichnis);
            }    
		return ($verzeichnis);
		}

    /*  
     * 
     *     automatische Erkennung Dos oder Linux am Verzeichnisbaum
     *     Vereinheitlihung der / oder \ Zeichen
     */

	function correctFileName($verzeichnis,$debug=false)
		{
		$len=strlen($verzeichnis); 
        $pos1=strrpos($verzeichnis,"\\"); $pos2=strrpos($verzeichnis,"/");          // letzte Position bekommen
        $pos3=strpos($verzeichnis,"\\"); $pos4=strpos($verzeichnis,"/");          // erste Position bekommen
        $pos5=strpos($verzeichnis,":");

        $dos=false; $linux=false;
        if ($pos1 && $pos2)                                 // es gibt sowohl \ als auch /
            {
            if (($pos3===0) || ($pos5==1) ) $dos=true;      // backslash oder Doppelpunkt
            if ($pos4===0) $linux=true;
            }            
        if ($pos5==1) $dos=true;                        //  Doppelpunkt als zweites Zeichen, ebenfall eindeutig DOS

		if ($debug) 
            {
            echo "correctFileName Auswertungen: Len:$len pos1:$pos1 pos2:$pos2 pos3:$pos3 pos4:$pos4 pos5:$pos5\n";			// am Schluss muss ein Backslash oder Slash sein !
            if ($pos1 && $pos2) echo "   mixed usage of / und \\ , Positions of \\ $pos3 and of / $pos4.\n";
            if ($dos) echo "DOS System.\n";
            if ($linux) echo "LINUX System.\n";                
            }

        if ($pos3) $verzeichnis = str_replace("\\\\","\\",$verzeichnis);        // wenn ein Doppelzeichen ausser am Anfang ist dieses vereinfachen
        if ($pos4 !== false) $verzeichnis = str_replace("//","/",$verzeichnis);     // bei Unix kann es auch 0 sein, da slash an erster Position
        
        // finish to correct dir seperator according to Operating System dos/linux
        if ($dos) 
            {
            $verzeichnis = str_replace("/","\\",$verzeichnis);          // Backslash wins
            $verzeichnis = str_replace("\\\\","\\",$verzeichnis);        // aus den Kombis \/ und /\ wird ein Doppel \ dieses auch vereinfachen
            }
        if ($linux) 
            {
            $verzeichnis = str_replace("\\","/",$verzeichnis);              // Slash wins
            $verzeichnis = str_replace("//","/",$verzeichnis);
            }    
		return ($verzeichnis);
		}


    /*  
     * 
     *     automatische Erkennung Dos oder Linux am Verzeichnisbaum
     *     Vereinheitlihung der / oder \ Zeichen
     */

	function getFileName($verzeichnis,$debug=false)
		{
		$len=strlen($verzeichnis); 
        $pos1=strrpos($verzeichnis,"\\"); $pos2=strrpos($verzeichnis,"/");          // letzte Position bekommen
        $pos3=strpos($verzeichnis,"\\"); $pos4=strpos($verzeichnis,"/");          // erste Position bekommen
        $pos5=strpos($verzeichnis,":");

        $dos=false; $linux=false;
        if ($pos1 && $pos2)                                 // es gibt sowohl \ als auch /
            {
            if (($pos3===0) || ($pos5==1) ) $dos=true;      // backslash oder Doppelpunkt
            if ($pos4===0) $linux=true;
            }            
        if ($pos5==1) $dos=true;                        //  Doppelpunkt als zweites Zeichen, ebenfalls eindeutig DOS

		if ($debug) 
            {
            echo "correctFileName Auswertungen: Len:$len pos1:$pos1 pos2:$pos2 pos3:$pos3 pos4:$pos4 pos5:$pos5\n";			// am Schluss muss ein Backslash oder Slash sein !
            if ($pos1 && $pos2) echo "   mixed usage of / und \\ , Positions of \\ $pos3 and of / $pos4.\n";
            if ($dos) echo "DOS System.\n";
            if ($linux) echo "LINUX System.\n";                
            }

        if ($pos3) $verzeichnis = str_replace("\\\\","\\",$verzeichnis);        // wenn ein Doppelzeichen ausser am Anfang ist dieses vereinfachen
        if ($pos4 !== false) $verzeichnis = str_replace("//","/",$verzeichnis);     // bei Unix kann es auch 0 sein, da slash an erster Position
        
        // finish to correct dir seperator according to Operating System dos/linux
        if ($dos) 
            {
            $verzeichnis = str_replace("/","\\",$verzeichnis);          // Backslash wins
            $verzeichnis = str_replace("\\\\","\\",$verzeichnis);        // aus den Kombis \/ und /\ wird ein Doppel \ dieses auch vereinfachen
            $pos1=strrpos($verzeichnis,"\\");
            if ($pos1) $verzeichnis=substr($verzeichnis,$pos1+1);
            }
        if ($linux) 
            {
            $verzeichnis = str_replace("\\","/",$verzeichnis);              // Slash wins
            $verzeichnis = str_replace("//","/",$verzeichnis);
            $pos1=strrpos($verzeichnis,"/");
            if ($pos1) $verzeichnis=substr($verzeichnis,$pos1+1);
            }    
		return ($verzeichnis);
		}


    /* einen Verzeichnisbaum auf Backslash oder Slash ändern */

	function convertDirName($verzeichnis,$dos=false)
		{
        if ($dos) 
            {
            $verzeichnis = str_replace("/","\\",$verzeichnis);          // Backslash wins
            $verzeichnis = str_replace("\\\\","\\",$verzeichnis);        // aus den Kombis \/ und /\ wird ein Doppel \ dieses auch vereinfachen
            }
        else 
            {
            $verzeichnis = str_replace("\\","/",$verzeichnis);              // Slash wins
            $verzeichnis = str_replace("//","/",$verzeichnis);
            }    
		return ($verzeichnis);
		}

	/* ein Verzeichnis rekursiv loeschen */

	function rrmdir($dir) 
		{
		if (is_dir($dir)) 
			{
			$objects = scandir($dir);
			foreach ($objects as $object) 
				{
				if ($object != "." && $object != "..") 
					{
					if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object);
					}
				}
			reset($objects);
			rmdir($dir);
			}
		} 

    /* eine Datei als echo ausgeben 
     * default sind alle Zeilen
     */

    function readFile($fileName, $maxLine=0)
        {
        if ( (($handle = fopen($fileName, "r")) !== false) )      // nur machen wenn filename  richtig
            {
            $row=1;
            while (($data = fgets($handle)) !== false) 
                {
                if ($row++ != $maxLine) echo $data;
                else break;
                }
            fclose($handle);
            }
        }

    /* eine Datei als String ausgeben 
     * default sind alle Zeilen
     */

    function readFileToString($fileName, $maxLine=0)
        {
        $result="";
        if ( (($handle = fopen($fileName, "r")) !== false) )      // nur machen wenn filename  richtig
            {
            $row=1;
            while (($data = fgets($handle)) !== false) 
                {
                if ($row++ != $maxLine) $result .= $data;
                else break;
                }
            fclose($handle);
            }
        return($result);
        }


   /* eine Datei löschen 
    */

    function deleteFile($fileName)
        {
        $result=false;
        if (file_exists($fileName)) $result=unlink($fileName); 
        return($result);
        }
 
   /* eine Datei kopieren 
    * fileName ist Filename samt Quellverzeichnis, targetDir ist das Zielverzeichnis ohne Filename
    */
    function moveFile($fileName,$targetDir)
        {
        $result=false;
        $dirAvailable=$this->readdirToStat($targetDir);
        if ( (file_exists($fileName)) && ($dirAvailable) ) $result=copy($fileName,$targetDir); 
        return($result);
        }

   /* eine Verzeichnis kopieren wenn notwendig 
    * 
    */
    function copyIfNeeded($bilderverzeichnis,$picturedir,$debug=false)
        {    
        $bilderverzeichnis = $this->correctDirName($bilderverzeichnis); 
        
        $bilderverzeichnis = str_replace(['\\','//','\\\\','\/'],'/',$bilderverzeichnis);
        $picturedir = str_replace(['\\','//','\\\\','\/'],'/',$picturedir);

        $file = $this->readSourceDir($bilderverzeichnis);
        echo "Insgesamt ".count($file)." Files aus dem Verzeichnis $bilderverzeichnis eingelesen, wenn notwendig nach $picturedir synchronisieren:\n";
        //print_R($file);
        $check = $this->readDirtoCheck($picturedir);

        foreach ($file as $filename)
            {
            if (isset($check[$filename]))
                {
                $check[$filename]=false;
                if ($debug) echo "Datei ".$filename." in beiden Verzeichnissen. nichts tun\n";
                }
            elseif ( is_file($bilderverzeichnis.$filename)==true )
                {	
                echo "   copy ".$bilderverzeichnis.$filename." nach ".$picturedir.$filename." \n";	
                copy($bilderverzeichnis.$filename,$picturedir.$filename);
                }
            }

        //if ($debug) echo "Verzeichnis für Anzeige auf Startpage synchronisieren:\n";	
        $i=0;
        foreach ($check as $filename => $delete)
            {
            if ($delete == true)
                {
                echo "     delete Datei ".$filename." \n";
                }
            else
                {
                //if ($debug) echo "   ".$filename."\n";
                $i++;		
                }	
            }	
        //if ($debug) echo "insgesamt ".$i." Dateien.\n";
        }


    /* Verzeichnis erstellen oder Dateien aus dem Verzeichnis einlesen 
     */
    function readSourceDir($bilderverzeichnis,$debug=false)
        {
        $file=array();
        if ( is_dir ( $bilderverzeichnis ) )
            {
            $handle=opendir ($bilderverzeichnis);
            $i=0;
            while ( false !== ($datei = readdir ($handle)) )
                {
                if ($debug)
                    {
                    if (is_dir($bilderverzeichnis.$datei)) echo "Dir  | $datei\n";
                    else echo "File | $datei\n";
                    }
                if ( ($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($bilderverzeichnis.$datei) == false) )  
                    {
                    $i++;
                    $file[$i]=$datei;
                    }
                }
            closedir($handle);
            //print_r($file);
            }/* ende if isdir */
        else
            {
            echo "Kein Verzeichnis mit dem Namen \"".$bilderverzeichnis."\" vorhanden.\n";
            $oss = IPS_GetKernelPlatform();
            $win=($oss=="windows");
            if ($win) $seperator="\\"; 
            else $seperator="/";
            $dirstruct=explode($seperator,$bilderverzeichnis);
            //print_r($dirstruct);
            $directoryPath="";
            foreach ($dirstruct as $directory)
                {
                $directoryOK=$directoryPath;
                $directoryPath.=$directory.$seperator;
                if ( is_dir ( $directoryPath ) ) {;}
                else
                    {
                    if ($directory !=="") echo "Error : ".$directory." is not in ".$directoryOK."\n";
                    }
                //echo $directoryPath."\n";
                } 
            $this->mkdirtree($bilderverzeichnis);	
            }
        return ($file);
        }

    /* read dir to check 
     */
    function readDirtoCheck($picturedir)
        {
        $check=array();
        $handle=opendir ($picturedir);
        while ( false !== ($datei = readdir ($handle)) )
            {
            if (($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($picturedir.$datei) == false)) 
                {
                $check[$datei]=true;
                }
            }
        closedir($handle);
        return ($check);
        }


    }       // ende class



/**************************************************************************************************************************
 *
 * fileOps, Zusammenfassung von Funktionen rund um das lesen und schreiben von Datenbanken im csv Format
 *
 * __construct              als Constructor wird der Filename an dem die Operationen durchgeführt werden übergeben
 *
 * fileinfo                 Auswertung status Ergebnis readFileCsv
 * 
 * readFileFixedFirstline
 * readFileFixed
 * readFileFixedLine
 *
 * readFileCsvFirstLine     liest nur die erste Zeil eines csv Files, damit bekommt man den Index
 * readFileCsv              wandelt eine csv Datei in ein Array um, erste Zeile kann als Index für die Umwandlung verwendet werden
 * readFileCsvParseConfig
 *
 * backcupFileCsv           das alte File wegspeichern als.old.csv
 *
 * writeFileCsv
 *
 *
 * Manipulationen der erhaltenen, eingelesenen Arrays
 * ---------------------------------------------------
 * findColumnsLines         Auswertung Spalten und Zeilen eines Arrays
 * writeArray
 *
 ******************************************************/

class fileOps
    {

    var $dosOps;
    var $fileName, $newFileName;
    var $config;                            //generell config to ease things
    var $analyze=array();

    function __construct($fileName=false,$config=false)
        {
        $this->dosOps = new dosOps();     // create classes used in this class
        if (is_file($fileName)) 
            {
            //echo "File Backup.csv vorhanden.\n";
            $this->fileName = $fileName;
            }
        else 
            {
            $this->fileName = false;
            $this->newFileName = $fileName;
            }
        }

    /* 
     * Auswertung von readFileCsv return status
     *   status
     *    lines
     *
     *  
     */
    function fileinfo($status,$debug=false)
        {
        $firstDate = array_key_first($status["lines"]);
        $lastDate  = array_key_last ($status["lines"]);
        $periode = $lastDate-$firstDate;
        $count=sizeof($status["lines"]);
        $interval=$periode/($count-1);
        if ($debug) echo "beinhaltet $count Werte von ".date("d.m.Y H:i",$firstDate)." bis ".date("d.m.Y H:i",$lastDate)." , Periode ".nf($periode,"s")." Intervall ".nf($interval,"s")." \n";
        $fileinfo["Count"] = $count;
        $fileinfo["FirstDate"] = $firstDate;
        $fileinfo["LastDate"]  = $lastDate;
        $fileinfo["Periode"]  = $periode;
        $fileinfo["Interval"]  = $interval;
        $fileinfo["Analyze"]  = $this->analyze;
        return ($fileinfo);
        }

    /* wenn es mehrere Spalten als Ergebnis gibt diese auch auswerten
     * die zeit fängt zum Laufen an wenn Splateneintrag ungleich "" und größer 0 ist
     */
    function analyseResult($result,$configCsv,$debug=false)
        {
        // analyse Result
        //if ($debug) { $i=0; foreach ($result as $line => $data) { echo $line; print_R($data); if ($i++>10) break; } }
        $analyze=array();
        foreach ($result as $line => $data)
            {
            foreach ($configCsv["Result"]["Column"] as $idx => $column) 
                {
                if ( (isset($data[$column])) && ($data[$column]!="") && ($data[$column]>0) )
                    {
                    $analyze[$column]["End"]=$line;
                    if (isset($analyze[$column]["Start"])===false) $analyze[$column]["Start"]=$line;
                    }
                }
            }
        if ($debug) 
            {
            // die Target Column Names als Key
            //echo "ConfigCsv ".json_encode($configCsv)."\n";
            foreach ($analyze as $idx => $coldata)
                {
                echo "     ".str_pad($idx,12).date("d.m.y H:i",$coldata["Start"])."   ".date("d.m.y H:i",$coldata["End"])."\n";
                }
            }
        return($analyze);
        }

    /* ein Fixed Delimiter File einlesen und die erste Zeile als array übergeben. 
     * Fixed Delimiter bedeuted dass die Spalten in der jeweiligen Zeile die selbe Länge haben und aus der Überschrift vorgegeben werden
     * Es ist auch eine File Format Conversion eingebaut, convert gibt das Format aus dem in ASCII konvertiert werden soll vor
     * es gibt möglicherweise Probleme mit fgets bei 16 Bit Formaten für die Char Darstellung, dann gleich die readFileFixed Funktion nehmen, die liest das ganze File ein
     *
     * ACHTUNG:  Routine ist noch nicht für mb_ funktionen vorbereitet. Kann nur ASCII 
     * bei Gelegenheit alle Filefunktionen umstellen, String Bearbeitung ist immer Multibyte
     * UTF-8 ist auch ein Multibyte Format, Standardfunktionen wie trin, substr, strpos etc. funktionieren nicht mehr
     *
     */
    function readFileFixedFirstline($convert="ASCII",$debug=false)
        {
        $delimiter=array();
        $i=0;
        if ($this->fileName !== false) 
            {
            if (($handle = fopen($this->fileName, "r")) !== false)
                {
                if ($debug) echo "readFileFixedFirstline, bearbeite Datei ".$this->fileName." mit Format $convert:\n";
                while (($line=fgets($handle)) !== false) 
                    {
                    if ($convert != "ASCII") $result=mb_convert_encoding($line,"ASCII",$convert);
                    else $result=$line;
                    if ($debug) echo "   ".strlen($line)." characters, ".strlen($result)." chars after encoding to ASCII and ".mb_strlen($line,$convert)." Multibyte Characters with format $convert found in Input.\n";
                    if ($i==0)                                                                              // nur die erste Zeile lesen
                        {
                        $delimiter=$this->readFirstLineFixed($result, "ASCII",$debug);
                        /*
                        $oldstart=false; $oldstring="";
                        $tabs=explode(" ",$result);
                        $countTabs=sizeof($tabs);               // sizeof trifft noch jede Menge Eintraeg mit einem blank                        
                        if ($countTabs>1)
                            {
                            echo "Erste Zeile :\"$result\"\n";                       
                            $delimiter=array();
                            foreach ($tabs as $index => $string)
                                {
                                if (($string == " ") || ($string == "")) 
                                    {
                                    //unset($tabs[$index]);
                                    }
                                else    
                                    {
                                    $stringTrimmed = trim($string);
                                    if ($oldstart !== false)                                        // schon etwas gefunden
                                        {
                                        $delimiter[$oldstring]["Index"]=$oldstart;
                                        $begin=$oldend;                                                 // beim ersten Mal steht das auf Pos von oldstring, im Normalfall 0
                                        if ($debug) echo "   ".str_pad($index,2)." | \"$string\" \n";
                                        if (strlen($stringTrimmed)>0) $end=strpos($result,$stringTrimmed);              // das Ende kann nur der Anfang des nächsten Eintrages sein, nicht das Ende des Wortes
                                        else 
                                            {
                                            echo "Trimmed String is empty, original string was \"$string\" \n";    
                                            $end=strlen($result);                                                    // wenn strlen 0 ist haben wir ein non doisplayable character, Tab oder end of line
                                            }
                                        if ($end<$begin) $end=strpos($result," ".$stringTrimmed)+1;            // Needle am ANfang mit einem Blank erweitern und schauen ob wir dann die richtige Position finden
                                        $delimiter[$oldstring]["Begin"]=$begin;
                                        $delimiter[$oldstring]["End"]=$end;
                                        $delimiter[$oldstring]["Length"]=$end-$begin;
                                        if ($debug) echo str_pad($index,2)." | ".str_pad("\"$oldstring\"",40)."  $begin/$end \n";
                                        $oldstart=$index;
                                        $oldstring=$stringTrimmed;
                                        $oldend=$end;
                                        if ($end == strlen($result))
                                            {
                                            echo "End of line found. Stopp analyzing.\n";
                                            break;              // stopp we are at the end, ignore trailing blanks
                                            } 
                                        }
                                    else                    // Wir suchen erstmal, blanks sind schon aussortiert, wir fangen mit einem Wort an
                                        {
                                        $oldstart=$index;
                                        $oldstring=$stringTrimmed;
                                        $oldend=strpos($result,$oldstring);
                                        //if ($debug) echo "   ".str_pad($index,2)." | \"$string\" Erster Treffer Init, Eintrag endet auf Position $oldend\n";
                                        } 
                                    }       
                                } 
                            $begin=$oldend;
                            $end=strlen($result);
                            if ($end<$begin) $end=strpos($result," ".$stringTrimmed)+1;            // mit einem Blank erweitern
                            if ($begin<$end)            // zumindet 1 Zeichen
                                {
                                $delimiter[$oldstring]["Index"]=$oldstart;
                                $delimiter[$oldstring]["Begin"]=$begin;
                                $delimiter[$oldstring]["End"]=$end;
                                $delimiter[$oldstring]["Length"]=$end-$begin;
                                }
                            if ($debug) echo str_pad($oldstart,2)." | ".str_pad("\"$oldstring\"",40)."  $begin/$end \n";                                
                            //print_r($delimiter);
                            echo "Zeile mit gefundene Spalten: ".sizeof($delimiter)."   \n";                             
                            if (sizeof($delimiter)>1) $i++;
                            } */
                        if (sizeof($delimiter)>1) $i++;
                        }
                    else break;
                    }
                fclose($handle);
                }
            }
        return($delimiter);
        }


    /* nur als firstLine aufrufen
     * entkoppelt Funktion von der Filebehandlung
     * kann mehrmals aufgerufen und verwendet werden : readFileFixed
     *
     * Umgang mit dem letzten Eintrag bis Zeilenende
     *      es wird ein Last Eintrag geschrieben, damit die Auswertungsroutine bis zum Ende liest
     *
     */
    function readFirstLineFixed($line, $convert="ASCII",$debug=false)
        {
        if ($convert != "ASCII") $result=mb_convert_encoding($line,"ASCII",$convert);
        else $result=$line;
        if ($debug) echo "   ".strlen($line)." characters, ".strlen($result)." chars after encoding to ASCII and ".mb_strlen($line,$convert)." Multibyte Characters with format $convert found in Input.\n";
        $oldstart=false; $oldstring="";
        $tabs=explode(" ",$result);             // Schnelltest
        $countTabs=sizeof($tabs);               // sizeof trifft noch jede Menge Eintraeg mit einem blank                        
        $delimiter=array();
        if ($countTabs>1)
            {
            //echo "Erste Zeile :\"$result\"\n";                       
            foreach ($tabs as $index => $string)            // alle tabs durchgehen, da gibt es auch einzelobjekte als blank
                {
                if (($string == " ") || ($string == "")) 
                    {
                    //unset($tabs[$index]);
                    }
                else    
                    {
                    $stringTrimmed = trim($string);                         // whitespace am Anfang und Ende entfernen
                    if ($oldstart !== false)                                        // schon etwas gefunden, weitermachen
                        {
                        $delimiter[$oldstring]["Index"]=$oldstart;
                        $begin=$oldend;                                                 // beim ersten Mal steht das auf Pos von oldstring, im Normalfall 0
                        //if ($debug) echo "   ".str_pad($index,2)." | \"$string\" \n";
                        if (strlen($stringTrimmed)>0) $end=strpos($result,$stringTrimmed);              // das Ende kann nur der Anfang des nächsten Eintrages sein, nicht das Ende des Wortes
                        else 
                            {
                            echo "Trimmed String is empty, original string was \"$string\" \n";    
                            $end=strlen($result);                                                    // wenn strlen 0 ist haben wir einen non-displayable character, Tab oder end of line
                            }
                        if ($end<$begin) $end=strpos($result," ".$stringTrimmed)+1;            // Needle am ANfang mit einem Blank erweitern und schauen ob wir dann die richtige Position finden
                        $delimiter[$oldstring]["Begin"]=$begin;
                        $delimiter[$oldstring]["End"]=$end;
                        $delimiter[$oldstring]["Length"]=$end-$begin;
                        if ($debug) echo str_pad($index,2)." | ".str_pad("\"$oldstring\"",40)."  $begin/$end    ($stringTrimmed)\n";
                        $oldstart=$index;
                        $oldstring=$stringTrimmed;
                        $oldend=$end;
                        if ($end == strlen($result))
                            {
                            echo "End of line found. Stopp analyzing.\n";
                            break;              // stopp we are at the end, ignore trailing blanks
                            } 
                        }
                    else                    // Wir suchen erstmal, blanks sind schon aussortiert, wir fangen mit einem Wort an
                        {
                        $oldstart=$index;
                        $oldstring=$stringTrimmed;
                        $oldend=strpos($result,$oldstring);
                        //if ($debug) echo "   ".str_pad($index,2)." | \"$string\" Erster Treffer Init, Eintrag endet auf Position $oldend\n";
                        } 
                    }       
                } 
            $begin=$oldend;
            $end=strlen($result);
            if ($end<$begin) $end=strpos($result," ".$stringTrimmed)+1;            // mit einem Blank erweitern
            if ($begin<$end)            // zumindet 1 Zeichen
                {
                $delimiter[$oldstring]["Index"]=$oldstart;
                $delimiter[$oldstring]["Begin"]=$begin;
                $delimiter[$oldstring]["End"]=$end;
                $delimiter[$oldstring]["Length"]=$end-$begin;
                $delimiter[$oldstring]["Last"]=true;                        // wenn man bis zum jeweiligen eol will
                }
            if ($debug) echo str_pad($oldstart,2)." | ".str_pad("\"$oldstring\"",40)."  $begin/$end      tatsächliches Zeilenende (".strlen($result).")\n";                                
            //print_r($delimiter);
            //echo "Zeile mit gefundene Spalten: ".sizeof($delimiter)."   \n";                             
            }
        else echo "Keine Blanks gefunden.\n";
        return ($delimiter);
        }



    /* ein file mit fixed Delimiter einlesen 
    *
    *
    */

    function readFileFixed($convert = "ASCII",$delimiter=array(),$maxline=10,$debug=false)
        {
        if ($debug) 
            {
            if (count($delimiter)>0) echo "readFileFixed, bearbeite Datei ".$this->fileName." mit Format $convert und ".count($delimiter)." Spalten.\n";
            else echo "readFileFixed, bearbeite Datei ".$this->fileName.". Erste Zeile gleich hier analysieren.\n";
            }
        $resultArray=array();
        $i=0;
        if ($this->fileName !== false) 
            {
            if ($convert != "ASCII")
                {
                //echo "read full file.\n";
                $result = @file_get_contents($this->fileName);              // dopelter Speicher
                if ($result !== false) 
                    {
                    //$encoding = mb_detect_encoding( $content, array("UTF-8","UTF-32","UTF-32BE","UTF-32LE","UTF-16","UTF-16BE","UTF-16LE"), TRUE );
                    $encoding = mb_detect_encoding( $result);
                    //$content = mb_convert_encoding($content, "UTF-8", $convert);
                    $content = mb_convert_encoding($result, "ASCII", $convert);
                    //$encoding2 = mb_detect_encoding( $content, array("UTF-8","UTF-32","UTF-32BE","UTF-32LE","UTF-16","UTF-16BE","UTF-16LE"), TRUE );
                    $encoding2 = mb_detect_encoding( $content);     // after conversion

                    if ($debug) 
                        {
                        //echo "readFileFixed, full file, bearbeite Datei ".$this->fileName." mit Format $convert:\n";
                        echo "   File gefunden, Textformat $encoding -> $encoding2\n";             // oder ".mb_detect_encoding($content)."
                        echo "   ".strlen($result)." characters, ".strlen($content)." chars after encoding to ASCII and ".mb_strlen($result,$convert)." Multibyte Characters with format $convert found in Input.\n";

                        }
                    // Separate lines
                    $content = str_replace(array("\r\n", "\n\r", "\r"), "\n", $content);
                    $content = explode("\n", $content);
                    foreach ($content as $index => $line)                            
                        {
                        if (($i==0) && ($delimiter==[]))             // einen delimiter finden
                            {
                            $delimiter=$this->readFirstLineFixed($line, "ASCII",$debug);
                            /*echo "Use first line to find delimiters.\n";
                            $oldstart=false; $oldstring="";
                            $tabs=explode(" ",$line);
                            $countTabs=sizeof($tabs);               // sizeof trifft noch jede Menge Eintraeg mit einem blank                        
                            if ($countTabs>1)
                                {
                                //echo "Erste Zeile :\"$line\"\n";                       
                                $delimiter=array();
                                foreach ($tabs as $index => $string)
                                    {
                                    if (($string == " ") || ($string == "")) 
                                        {
                                        //unset($tabs[$index]);
                                        }
                                    else    
                                        {
                                        $stringTrimmed = trim($string);
                                        if ($oldstart !== false)                                        // schon etwas gefunden
                                            {
                                            $delimiter[$oldstring]["Index"]=$oldstart;
                                            $begin=$oldend;                                                 // beim ersten Mal steht das auf Pos von oldstring, im Normalfall 0
                                            //if ($debug) echo "   ".str_pad($index,2)." | \"$string\" \n";
                                            if (strlen($stringTrimmed)>0) $end=strpos($line,$stringTrimmed);              // das Ende kann nur der Anfang des nächsten Eintrages sein, nicht das Ende des Wortes
                                            else 
                                                {
                                                echo "Trimmed String is empty, original string was \"$string\" \n";    
                                                $end=strlen($line);                                                    // wenn strlen 0 ist haben wir ein non doisplayable character, Tab oder end of line
                                                }
                                            if ($end<$begin) $end=strpos($line," ".$stringTrimmed)+1;            // Needle am ANfang mit einem Blank erweitern und schauen ob wir dann die richtige Position finden
                                            $delimiter[$oldstring]["Begin"]=$begin;
                                            $delimiter[$oldstring]["End"]=$end;
                                            $delimiter[$oldstring]["Length"]=$end-$begin;
                                            if ($debug) echo "    ".str_pad($index,2)." | ".str_pad("\"$oldstring\"",40)."  $begin/$end \n";
                                            $oldstart=$index;
                                            $oldstring=$stringTrimmed;
                                            $oldend=$end;
                                            if ($end == strlen($line))
                                                {
                                                echo "End of line found. Stopp analyzing.\n";
                                                break;              // stopp we are at the end, ignore trailing blanks
                                                } 
                                            }
                                        else                    // Wir suchen erstmal, blanks sind schon aussortiert, wir fangen mit einem Wort an
                                            {
                                            $oldstart=$index;
                                            $oldstring=$stringTrimmed;
                                            $oldend=strpos($line,$oldstring);
                                            //if ($debug) echo "   ".str_pad($index,2)." | \"$string\" Erster Treffer Init, Eintrag endet auf Position $oldend\n";
                                            } 
                                        }       
                                    } 
                                $begin=$oldend;
                                $end=strlen($line);
                                if ($end<$begin) $end=strpos($line," ".$stringTrimmed)+1;            // mit einem Blank erweitern
                                if ($begin<$end)            // zumindet 1 Zeichen
                                    {
                                    $delimiter[$oldstring]["Index"]=$oldstart;
                                    $delimiter[$oldstring]["Begin"]=$begin;
                                    $delimiter[$oldstring]["End"]=$end;
                                    $delimiter[$oldstring]["Length"]=$end-$begin;
                                    }
                                if ($debug) echo str_pad($oldstart,2)." | ".str_pad("\"$oldstring\"",40)."  $begin/$end \n";                                
                                //print_r($delimiter);
                                //echo "Zeile mit gefundene Spalten: ".sizeof($delimiter)."   \n";                             
                                if (sizeof($delimiter)>1) $i++;
                                }           // ende if count tabs
                            //$this->readFileFixedLine($line,$delimiter,true);          // Test, hier stimmt noch alles
                            */
                            if (sizeof($delimiter)>1) $i++;
                            $delimiter2=array(); $offset=0;
                            foreach ($delimiter as $index => $entry)                                    // irgend etwas läuft falsch und wir wissen nicht was 
                                {
                                $delimiter2[$index]["Begin"]=$entry["Begin"]-$offset;
                                $delimiter2[$index]["Length"]=$entry["Length"];
                                //if ($index==0) $offset=3;
                                }
                            $delimiter=$delimiter2;
                            }
                        else 
                            {
                            /* der obere Teil ist gleich wie bei FirstLine, jetzt wird aber wirklich eingelesen */
                            $resultArray[$i] = $this->readFileFixedLine($line,$delimiter,$debug);
                            if ($i++>$maxline) break;
                            }
                        }
                    }
                else echo "File is empty.\n";
                }
            elseif (($handle = fopen($this->fileName, "r")) !== false)
                {
                if ($debug) echo "readFileFixed, line per line, bearbeite Datei ".$this->fileName." mit Format $convert:\n";
                while (($result=fgets($handle)) !== false) 
                    {
                    //if ($convert != "UTF-8") $result=mb_convert_encoding($result,"UTF-8",$convert);
                    if ($debug) echo $result;
                    if ($i==0) 
                        {
                        $delimiter=$this->readFirstLineFixed($result, "ASCII",$debug);
                        /*
                        $oldstart=false; $oldstring="";
                        $tabs=explode(" ",$result);
                        $countTabs=sizeof($tabs);               // count geht nach dem Index
                        if ($countTabs>1)
                            {
                            if (sizeof($delimiter)<1)
                                {
                                //echo "Gefundene Spalten: ".$countTabs."   \n"; print_R($tabs);
                                $delimiter=array();
                                foreach ($tabs as $index => $string)
                                    {
                                    if (($string == " ") || ($string == "")) 
                                        {
                                        //unset($tabs[$index]);
                                        }
                                    else    
                                        {
                                        $string = trim($string);                                        
                                        //if ($debug) echo str_pad($index,2)." | \"$string\" \n";
                                        if ($oldstart !== false) 
                                            {
                                            $delimiter[$oldstring]["Index"]=$oldstart;
                                            $begin=$oldend;
                                            $end=strpos($result,$string);
                                            if ($end<$begin) $end=strpos($result," ".$string)+1;            // mit einem Blank erweitern
                                            $delimiter[$oldstring]["Begin"]=$begin;
                                            $delimiter[$oldstring]["End"]=$end;
                                            $delimiter[$oldstring]["Length"]=$end-$begin;
                                            if ($debug) echo str_pad($index,2)." | ".str_pad("\"$oldstring\"",40)."  $begin/$end \n";                                            
                                            $oldstart=$index;
                                            $oldstring=$string;
                                            $oldend=$end;
                                            }
                                        else 
                                            {
                                            $oldstart=$index;
                                            $oldstring=$string;
                                            $oldend=strpos($result,$oldstring);
                                            } 
                                        }       
                                    } 
                                $delimiter[$oldstring]["Index"]=$oldstart;
                                $begin=$oldend;
                                $end=strlen($result);
                                if ($end<$begin) $end=strpos($result," ".$string)+1;            // mit einem Blank erweitern
                                $delimiter[$oldstring]["Begin"]=$begin;
                                $delimiter[$oldstring]["End"]=$end;
                                $delimiter[$oldstring]["Length"]=$end-$begin;
                                if ($debug) echo str_pad($oldstart,2)." | ".str_pad("\"$oldstring\"",40)."  $begin/$end \n";                                      
                                }
                            //print_r($delimiter);
                            echo "Zeile mit insgesamt ".sizeof($delimiter)." gefundene Spalten. \n";                                                         
                            } */
                        if (sizeof($delimiter)>1) $i++;                            
                        }
                    else 
                        {
                        /* der obere Teil ist gleich wie bei FirstLine, jetzt wird aber wirklich eingelesen */
                        $ergebnis = $this->readFileFixedLine($result,$delimiter,false);            // andernfalls debug einsetzen
                        if (sizeof($ergebnis)>0) $resultArray[$i]=$ergebnis;                        // leere Elemente nicht übernehmen
                        if ($i++>$maxline) break;
                        }
                    }
                fclose($handle);
                }
            }
        else echo "Filename empty.\n";
        return($resultArray);
        }

    /* nur eine Zeile nach Anleitung delimiter zerkleinern
     * Wenn Last definiert ist bis zum Ende der Zeile lesen
     * wenn sich der Beginn bereits über die Länge der Zeile zieht abbrechen
     *
     */
    function readFileFixedLine($line,$delimiter,$debug=false)
        {
        $resultArray=array();
        foreach($delimiter as $key => $entry)
            {
            if ($entry["Begin"]>strlen($line)) break;
            //if ($debug) echo $entry["Begin"]."/".$entry["Length"]."|";
            if ( (isset($entry["Last"])) && ($entry["Last"]) ) $resultArray[$key]=trim(substr($line,$entry["Begin"]));          // bis zum Ende der Zeile lesen
            else $resultArray[$key]=trim(substr($line,$entry["Begin"],$entry["Length"]));
            }            
        if ($debug) 
            {
            //echo "\n$line\n";
            foreach ($resultArray as $item => $entry) echo $entry."|";
            echo "\n";
            }
        return($resultArray);
        }

    /* eigentlich zugeschnitten auf die Ausgaben die für Selenium processes benötigt werden
     */
    function analyseContent($content, $key, $maxempty=0, $debug=false)
        {
        $fileOps = new fileOps();
        $i=0; $result=array(); $ignore=0;  $maxCol=1;        
        foreach ($content as $index => $line)
            {
            //echo $line."\n";
            if ($i==0) 
                {
                $pos=strpos($line, $key); 
                if (($pos !==false) && ($pos < 10) )
                    {
                    if ($debug) echo ":".$line."\n";
                    $delimiter = $this->readFirstLineFixed($line,"ASCII",false);          // true für Debug
                    $maxCol=sizeof($delimiter);
                    if ($debug>1) print_r($delimiter);
                    $i++;
                    }
                }
            else
                {
                $ergebnis = $this->readFileFixedLine($line,$delimiter,false);            // andernfalls debug einsetzen
                if (sizeof($ergebnis)>=$maxCol) 
                    {
                    if ($debug) echo ":".$line."\n";
                    $result[$i]=$ergebnis;
                    $ignore=0;
                    }
                else 
                    {
                    if ($ignore>=$maxempty) break;                                  // leere zeile ist Abbruch
                    $ignore++;
                    }
                $i++;
                }
            }
        $better=array();
        foreach ($result as $entry ) $better[]=$entry;    
        return ($better);
        }


    /* ein csv File einlesen und die erste Zeile als array übergeben für die Verwendung als index. 
     * die php Funktion fgetcsv macht dabei die Arbeit
     *
     * ignore = true bedeutet das ungültige Spaltenbezeichnungen ignoriert werden und nicht auf false gesetzt werden
     */

    function readFileCsvFirstline($ignore=false,$debug=false)
        {
        $index=array();
        if ($this->fileName !== false) 
            {
            if (($handle = fopen($this->fileName, "r")) !== false)
                {
                $row=1;
                while (($data = fgetcsv($handle, 1000, ";")) !== false) 
                    {
                    $num = count($data);
                    if ($row==1) 
                        {
                        //print_r($data);
                        for ($i=0;($i<$num);$i++) 
                            {
                            $spaltenBez=trim($data[$i]);
                            if ($spaltenBez != "") $index[]=$spaltenBez;
                            elseif (!$ignore) $index[]= false;
                            }
                        }
                    else break;
                    $row++;
                    }
                fclose($handle);            
                }
            }
        //echo "Ende erreicht. Erste Zeile hat ".sizeof($index)." Spalten. Leere Spaltenbezeichner werden ignoriert.\n";
        return($index);
        }

    /* readFileCsv
     *
     * ein csv File einlesen und als array in result übergeben. Das Array ist der erste Parameter, bearbeitet als Pointer
     * die erste Zeile ist immer ein Index, entweder wird der Index übernommen oder durch einen eigenen ersetzt
     * verwendet php funktion fgetcsv mit seperator ;
     *
     * Parameter
     *      result      das Array mit Zeilen und Spalten, erster key sind die Zeilen, zweiter key sind die Spalten
     *      key         Index für die Tabelle, wenn nicht blank, wenn ein array dann ist es die Konfiguration
     *      index       ein array mit Spaltenbezeichnungen, wenn leer wird die erste Zeile verwendet, die Einträge werden getrimmt, Spalten die nicht übernommen werden sollen sind mit Indexwert false
     *      filter      welche Spalten sollen uebernommen werden
     *
     * Configuration
     *      Key     Key mit optional untergeordneten Parametern
     *                  Merge
     *                  From
     *      Result  Add oder ein array mit [Mode->Add,Columns->[Value1,Value2,Value3]]
     *      Format  umformatieren mit untergeordneten Parametern   
     *
     * für die Bearbeitung der Daten werden data und index Spalte für Spalte durchgegangen
     * und in dataentries Spalte für Spalte mit dem neuen Index gespeichert, Spalten die nicht übernommen werden sollen sind mit Indexwert false
     * es können zwei Spalten für Date und Time zu einem Unixtimestamp gemerged werden
     *
     * mehrere Debug Leveles  false/0 true/1 2 3
     *
     */

    public function readFileCsv(&$result, $key="", $index=array(), $filter=array(), $debug=false)
        {
        // parse config if provided, merge und key werden als Shortcut gesetzt            
        $merge=false;
        if (is_array($key)) 
            { 
            $config=$this->readFileCsvParseConfig($key,($debug>1));             // gibt bei Debug die Konfiguration aus
            if (is_array($config["Key"]))
                {
                if ( (isset($config["Key"]["Merge"])) && (isset($config["Key"]["From"])) && (is_array($config["Key"]["From"])) ) $merge=true;
                $key=$config["Key"]["Merge"];
                }
            else $key=$config["Key"]; 
            }
        else 
            {
            echo "Do defaults, Key is no array.\n";
            $config = $this->readFileCsvParseConfig([],$debug);               // Defaultwerte ausprobieren
            }
        if ($debug)   echo "readFileCsv::fileOps, adjusted input config is ".json_encode($config)."\n";
        if ($debug>2) echo "Memorysize : ".getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb\n";  
        // continue
        $error=0; $errorMax=20;     /* nicht mehr als 20 Fehler/Info Meldungen ausgeben */
        $error1=0; $errorMax1=50;
        $error2=0;
        $once=true;

        $rowMax=10;                 /* debug, nicht mehr als rowMax Zeilen ausgeben, sonst ist der output buffer voll */
        $ergebnis=true;             // wird false wenn Filename nicht korrekt oder result kein array
        $keyIndex=false;            // wenn kein Index mit Namen key gefunden wird
        $this->analyze=array();     // zusätzliche Auswertungen übergeben
        $analyzeIndex=0;
        $overwrite=false;           // Erkennen von einem OverLap
        $overwriteStart=false; $overwriteEnd=false;

        $oldkey1=false; $oldDiff=false; $diff=false;

        /* erste Zeile für die Bezeichnung der Spalten verwenden */
        if (count($index)==0) 
            {
            if ($debug)
                {
                echo "    No index given, use first Line for defining Columns of the Table.";
                if ($key!="") echo " Use column $key as Index for Table.";
                echo "\n";
                }
            $firstline=true;
            }
        else 
            {
            $firstline=false;
            if ($debug)
                {
                echo "Inputfile ".$this->fileName.". Index given, use this array ".json_encode($index)." for defining Index for array of result.";
                if ($key!="") echo "     Use column $key as Index for Table.";
                echo "\n";                
                //print_r($index);
                }
            }    
        if ( ($debug) && (count($result)>0) ) echo "    Input array for result has already ".count($result)." lines. Will try to merge.\n";

        if ( ($this->fileName !== false) && (is_array($result)) )           // nur machen wenn filename und übergabe Array richtig
            {
            if ( (($handle = @fopen($this->fileName, "r")) !== false) )
                {
                // csv Zeile für Zeile einlesen
                $row=1; $rowShow=1; $countIndex=false;
                while (($data = fgetcsv($handle, 0, ";")) !== false) 
                    {
                    $num = count($data);                            // wieviel Spalten werden eingelesen, num
                    if ( (($row % 100) ==0) && ($debug>2) ) echo "$row : Size Result ".count($result)." Memorysize : ".getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb\n";  // Speicherbedarf steigt mit dem Lesen der Datan
                    if ( ($firstline) && ($row==1) )                // in der ersten Reihe sind die Spaltenbezeichnungen
                        {
                        if ($debug) echo "   Erste Zeile als Index einlesen. Key löschen.\n";
                        for ($i=0;($i<$num);$i++)                   // erste Zeile Spalten einlesen
                            {
                            $spaltenBez=trim($data[$i]);
                            /* Blanks bei den Spalten entfernen, leere Spalten nicht einlesen, wenn ein key definiert ist diesen auch nicht als Spalte einlesen
                             * d.h. es bleiben alle valide Spalten die nicht key sind als Array in index über. 
                             * Um die Key Spalte richtig zuzuordnen, wird der index in $keyIndex gespeichert.
                             * data   [0=>filename  1=>0712    2=>0812    3=>""    4=>0912    5=>0612    6=>0712  
                             * key=filename, indexKey=0
                             * filter []  noch nicht implementiert, Spalten entsprechend Angabe filter auf false stellen
                             * index  
                             *
                             */
                            if ( ($spaltenBez != "") && ($spaltenBez != $key) ) $index[]=$spaltenBez;
                            elseif ($spaltenBez == $key) 
                                {
                                $keyIndex=$i;
                                $index[]=false;
                                }
                            else $index[]=false;
                            }
                        /* Filter hier bearbeiten und weitere Index Eintraeg auf false setzen */
                        if ($debug) print_r($index);            // ermittelter index ausgeben, beinhaltet false für zu überspringende Spalten
                        }
                    elseif ($row==1) 
                        {
                        $i=0;
                        foreach ($index as $key2) 
                            {
                            if ($key2 == $key) $keyIndex=$i;                  // key Index finden, mehr nicht
                            elseif ($key2 !== false) $i++;                  // Index der nicht übernommen wird auch nicht berücksichtigen    !! Merge fehlt noch hier
                            }
                        if ($debug>1) 
                            {
                            echo "   Erste Zeile gefunden, aber nicht verwenden: ";
                            for ($i=0;($i<$num);$i++)                   // erste Zeile Spalten aus data bearbeiten
                                {
                                echo trim($data[$i]).",";
                                }
                            echo "   Sondern vom Index übernehmen. Key nicht löschen. Key Index ist $keyIndex. ".json_encode($index)."\n";    // Index ist vorgegeben, Felder die übersprungen werden sollen mit false markieren                                      
                            }
                        }
                    else    /* alle anderen Zeilen hier einlesen */
                        {
                        if ($num==0) echo "Fehler, no csv Data identified in Line $row.\n";
                        if ($countIndex==false)         // alle relevanten Spalten einmal zaehlen
                            {
                            $countIndex=count($index);  // trailing Dummies aus der mindest geforderten Liste rausnehmen
                            do { $countIndex--; } while (strtoupper($index[$countIndex]) == "DUMMY");  // trailing Dummies aus der mindest geforderten Liste rausnehmen 
                            if ($debug>1) 
                                {
                                echo "From index minimum first $countIndex Columns have to be provided.\n";
                                }
                            }
                        if ($num != (count($index)) )               // num ist count($data)
                            {
                            if ( ($debug>1) && (($error1++)<$errorMax1) )
                                {
                                echo "$row Warning, not same amount of columns. $num Columns found. ".count($index)." columns expected.";
                                echo json_encode($data);
                                echo "\n";
                                //print_r($data);
                                }
                            }                            
                        if ($num >= $countIndex)     
                            { 
                            // nur Zeilen einlesen die die gewünschte Anzahl von Spalten zumindest zur Verfügung haben, zuerst alle Werte als dataEntries abspeichern 
                            $key1=$row-2;    // starts with 0, if there is no key defined
                            $i=0;
                            $dataEntries=array();       // Zeile bearbeiten und Ergebnis zwischenspeichern
                            foreach ($index as $key2)   // index durchgehen, Eintraege mit false oder DUMMY überspringen
                                {
                                //if (isset($data[$i])===false) echo "irgendwas ist falsch hier: ".json_encode($data)."\n";
                                if (($key2 !== false) && (strtoupper($key2) !== "DUMMY") && (isset($data[$i])) ) $dataEntries[$key2]=$data[$i];
                                $i++;
                                }
                            //dataEntries zusammenfassen
                            if ($merge)
                                {
                                if (isset($config["Key"]["From"])) 
                                    {
                                    foreach($config["Key"]["From"] as $function => $value) 
                                        {
                                        switch (strtoupper($function))
                                            {
                                            case "DATE":
                                                if (isset($dataEntries[$value])) 
                                                    {
                                                    $date=$dataEntries[$value];
                                                    unset($dataEntries[$value]);
                                                    }
                                                break;
                                            case "TIME":
                                                if (isset($dataEntries[$value])) 
                                                    {
                                                    $time=$dataEntries[$value];                                            
                                                    unset($dataEntries[$value]);
                                                    }
                                                break;
                                            default:
                                                break;
                                            }
                                        }
                                    $str=$date." ".$time;           // sonst wird falsches Jahr erkannt
                                    $timeStamp=strtotime($str);
                                    if ( ($debug>1) && ($error++ < $errorMax) ) echo "Zeitstempel aus $str berechnet: ".date("d.m.y H:i:s",$timeStamp)."\n";
                                    $dataEntries[$config["Key"]["Merge"]]=$timeStamp;
                                    //if ($error++ < $errorMax) print_r($dataEntries);
                                    $keyIndex=99999; // sonst greift key nicht
                                    }
                                }
                            // eventuell umformatieren, Format=[]
                            if (isset($config["Format"]))
                                {
                                if (is_array($config["Format"]))
                                    {
                                    foreach ($config["Format"] as $keyComp => $format )
                                        {
                                        if (isset($dataEntries[$keyComp])) 
                                            {
                                            if ( ($debug>1) && ($error++ < $errorMax) ) echo "Formatänderung $keyComp auf $format.\n";
                                            switch (strtoupper($format))
                                                {
                                                case "FLOAT":
                                                    if (is_string($dataEntries[$keyComp]))
                                                        {
                                                        $value = str_replace(",",".",$dataEntries[$keyComp]);           // Beistrich auf . umrechnen
                                                        $dataEntries[$keyComp]=floatval($value);         // Komma wird nicht richtig interpretiert
                                                        }
                                                    break;
                                                case "STRTOTIME":
                                                    if (is_string($dataEntries[$keyComp]))
                                                        {
                                                        if ($debug) $dataEntries[$keyComp."_orig"]=$dataEntries[$keyComp];              // für Debugzwecke auch das Originaldatum abspeichern
                                                        $dataEntries[$keyComp]=strtotime($dataEntries[$keyComp]);         // auf Unix Timestamp ändern

                                                        }
                                                    break;
                                                }
                                            }
                                        elseif ($once) { if ($debug) echo "$keyComp not in Data ".json_encode($dataEntries)."  \n"; $once=false;  }     
                                        }
                                    }
                                }
                            // dann abhängig von key speichern
                            if ( ($key=="") || ($keyIndex===false) )        // kein Key definiert oder keyindex gefunden, key1 wird aus der aktuellen Zeilenanzahl berechnet, kexindex wird nur ermittelt wenn keine neue Indexierung erfolgt
                                {
                                if ($dataEntries["Value"]!="") 
                                    {
                                    $result[$key1]=$dataEntries;
                                    if ($rowShow < $rowMax) if ($debug) echo "<p> $key1 : $num Felder in Zeile $row: ".json_encode($dataEntries)." index is $key / $keyIndex<br /></p>\n";
                                    $rowShow++;
                                    if ($oldkey1) $diff=$key1-$oldkey1; 
                                    if ( ($oldDiff) && ($diff != $oldDiff) ) echo "Other Intervall : $diff != $oldDiff\n";
                                    $oldkey1=$key1;
                                    $oldDiff=$diff;
                                    }
                                }
                            else 
                                {
                                /* wenn ein key definiert wurde, kann überprüft werden ob der Eintrag bereits vorhanden ist 
                                 * wird für in memory merge verwendet. Der Merge könnte auch nachträglich erfolgen. Benötigt aber mehr Speicher.
                                 * beides implementieren.
                                 *
                                 * wenn es einen key gibt und der im Index gefunden wurde sind wir hier
                                 */

                                if ($keyIndex<99999) 
                                    {
                                    //$key1=$data[$keyIndex];             //key1 richtig berechnen ist kein 0..n Index, keyindex wird 9999 wenn ein Merge erfolgt ist
                                    $key1=$dataEntries[$index[$keyIndex]];
                                    }
                                else $key1=$timeStamp;
                                if (isset($result[$key1])) 
                                    {
                                    if ($overwrite===false) 
                                        {
                                        $overwriteStart=$key1;
                                        $overwrite=true;
                                        }
                                    $entryExist=json_encode($result[$key1]);
                                    $entryNew=json_encode($dataEntries);
                                    if (($rowShow < $rowMax) && ($debug>1) )  { echo "dataEntries found: $entryNew \n"; $rowShow++; }
                                    if ($entryExist != $entryNew)
                                        {
                                        if ( ($debug) && ($error++ < $errorMax) )           // nicht alle Meldungen ausgeben, führen zu einem Overflow
                                            {
                                            echo "-> $key1 bereits bekannt. Eintrag $entryExist wird mit $entryNew nicht überschrieben.\n"; 
                                            }
                                        }
                                    if (strtoupper($config["Result"]["Mode"])!="ALL") unset($result[$key1]);                //ALL ist default, sonst löschen
                                    }
                                else 
                                    {
                                    if ($overwrite===true) 
                                        {
                                        $overwriteEnd=$key1;
                                        $overwrite=false;
                                        $this->analyze[$analyzeIndex]["Type"]="Lap";     // zusätzliche Auswertungen übergeben
                                        $this->analyze[$analyzeIndex]["StartDate"]=$overwriteStart;
                                        $this->analyze[$analyzeIndex]["EndDate"]=$overwriteEnd;
                                        $this->analyze[$analyzeIndex]["Diff"]=$overwriteEnd-$overwriteStart;
                                        $analyzeIndex++;
                                        }
                                    if (($rowShow < $rowMax) && ($debug>1) )  
                                        { 
                                        $entryCheck=json_encode($dataEntries);
                                        echo "      dataEntries found for Key $key1: $entryCheck \n"; $rowShow++; 
                                        }
                                    // fixe Übernahme von Werten mit Value ungleich Blank, oder einfach alle Zeilen übernehmen                                        
                                    //if ($dataEntries["Value"]!="") 
                                        {                                        
                                        if ($rowShow < $rowMax) if ($debug>1) echo "<p> $key1 : $num Felder in Zeile $row: ".json_encode($dataEntries)." index is $key <br /></p>\n";
                                        $result[$key1]=$dataEntries;   
                                        $rowShow++;
                                        if ($oldkey1)  $diff=$key1-$oldkey1; 
                                        if ( ($oldDiff) && ($diff != $oldDiff) ) 
                                            {
                                            if ($debug)
                                                {
                                                echo "<p> $oldkey1 : $num Felder in Zeile $oldRow: ".json_encode($oldDataEntries)." index is $oldKey ".date("D d.m.y H:i",$oldDataEntries[$oldKey])."<br /></p>\n";
                                                echo "<p> $key1 : $num Felder in Zeile $row: ".json_encode($dataEntries)." index is $key ".date("D d.m.y H:i",$dataEntries[$key])."<br /></p>\n";
                                                echo "Other Intervall : $diff != $oldDiff  ".nf($diff,"s")." != ".nf($oldDiff,"s")."\n";
                                                }
                                            if ($diff > $oldDiff)
                                                {
                                                $this->analyze[$analyzeIndex]["Type"]="Gap";     // zusätzliche Auswertungen übergeben
                                                $this->analyze[$analyzeIndex]["StartDate"]=$oldkey1;
                                                $this->analyze[$analyzeIndex]["EndDate"]=$key1;
                                                $this->analyze[$analyzeIndex]["Diff"]=$diff;
                                                $analyzeIndex++;
                                                }
                                            }
                                        $oldDataEntries = $dataEntries; 
                                        $oldKey=$key; $oldRow=$row; $oldkey1=$key1; $oldDiff=$diff;                                        
                                        }

                                    //if ($error2++ < $errorMax) print_r($dataEntries);
                                    }
                                }
                            //$result[$key1][$key2]=$data[$i++];                        
                            //if ($row < $rowMax) echo "<p> $key1 : $num Felder in Zeile $row: <br /></p>\n";
                            }                             
                        }
                    $row++;  
                    //if ($row>200) break;                  
                    }
                fclose($handle);
                if ($debug) echo "Input File hat $row Zeilen und $num Spalten. ".sizeof($index)." Spalten davon uebernommen.\n";
                }           // ende File korrekt geöffnet
            else $ergebnis=false;                
            }           // ende if param error check
        else 
            {
            echo "warning, input parameter wrong.\n";
            $ergebnis=false;
            }
        if ($ergebnis) return ($this->findColumnsLines($result));           // columns und lines übergeben, nicht das result, führt zu Speicherverschwendung
        else return($ergebnis);
        }  // ende function

    /* readFileCsvParseConfig
     * check und parse die Komfiguration für readFileCsv
     *      Key     Key mit optional untergeordneten Parametern
     *                  Merge
     *                  From
     *      Result
     *      Format
     */

    private function readFileCsvParseConfig($key,$debug=false)
        {
        $config=array();
        configfileParser($key,$config,["Key","key","KEY"],"Key","");
        if (is_array($config["Key"]))
            {
            configfileParser($config["Key"],$configKey,["Merge","merge","MERGE"],"Merge",Null);
            configfileParser($config["Key"],$configKey,["From","from","FROM"],"From",Null);
            $config["Key"]=$configKey;
            }
        configfileParser($key,$config,["Result","result","RESULT"],"Result",[]);            // default ist leeres Array
        if (is_array($config["Result"])===false)
            {
            $mode =$config["Result"];
            //echo "readFileCsvParseConfig Mode $mode erkannt. Result for Value not defined.";
            $config["Result"]=array();
            $config["Result"]["Mode"]=$mode;   
            }
        if (is_array($config["Result"]))        // Columns->[Value1,Value2,Value3]
            {
            configfileParser($config["Result"],$configResult,["Mode","mode","MODE"],"Mode","All");
            configfileParser($config["Result"],$configResult,["Columns","columns","COLUMNS"],"Columns",["Value"]);
            $config["Result"]=$configResult;
            }
        configfileParser($key,$config,["Format","format","FORMAT"],"Format",Null);

        if ($debug) print_r($config);
        return ($config);
        }


    /* csv file in old.csv umbenennen :
     * wenn filename noch nicht existiert (false) den newFilename nehmen
     * sonst das File auf Backup umbenennen
     */

    function backcupFileCsv()
        {
        if ($this->fileName !== false) 
            {   /* es gibt schon eine Datei, die wir aber behalten wollen, falls etwas schief geht */
            $filename=$this->fileName;
            $pathinfo=pathinfo($filename);
            print_r($pathinfo);
            $filenameOld = $this->dosOps->correctDirName($pathinfo["dirname"]).$pathinfo["filename"].".old.".$pathinfo["extension"];
            echo "Rename $filename mit neuem Dateinamen : $filenameOld \n";
            rename($filename, $filenameOld);
            }
        else $filename=$this->newFileName;
        return ($filename);
        }


    /************************
     *
     * writefilecsv schreibt ein array in die Datei $this->filename, wenn nicht vorhanden (false) dann in $this->newFileName
     *
     ***********/

    function writeFileCsv(&$result, $debug=false)
        {
        if ($this->fileName === false) $filename=$this->newFileName;
        else $filename=$this->fileName;

        if ($debug) echo "Neuer Filename $filename zum Schreiben.\n";

        $resultCandL=$this->findColumnsLines($result);
        $columns=$resultCandL["columns"];
        if ($debug) 
            {
            echo "writeFileCsv: Analyse des Input Arrays: \n";
            echo "  Insgesamt ".sizeof($resultCandL["lines"])." Zeilen und ".sizeof($resultCandL["columns"])." Spalten erkannt: \n";
            print_r($columns);
            }
        
        if (is_file($filename)) unlink($filename);      // in ein leeres File schrieben
        $handle=fopen($filename, "a");
        fputcsv($handle,$columns,";");             // den Index aus dem result File übernehmen

        foreach ($result as $file => $entry)
            {
            /* Struktur von result ist Filename = array Spalte => Datum */
            $line=array();
            foreach ($columns as $column)
                {
                if (isset($entry[$column])) $line[]=$entry[$column];
                else $line[]=false;
                }
            $entries=[$file] + $line;
            fputcsv($handle,$entries,";");
            /*
            fwrite($handle, $file.";");
            foreach ($index as $tab)
                {
                if (isset($entry[$tab])) fwrite($handle, $entry[$tab].";");   
                else fwrite($handle, ";");
                }
            fwrite($handle,"\n");
            */
            }
        fclose($handle);
        }

    /*
     * writefileJson
     * schreibt einen String in die Datei $this->filename, wenn nicht vorhanden (false) dann in $this->newFileName
     *
     ***********/

    function writeFileJson(&$result, $debug=false)
        {
        if ($this->fileName === false) $filename=$this->newFileName;
        else $filename=$this->fileName;

        if ($debug) echo "Neuer Filename $filename zum Schreiben.\n";
        if ($debug) 
            {
            echo "writeFileJson: Analyse des Input Arrays: \n";
            }
        
        if (is_file($filename)) unlink($filename);      // in ein leeres File schrieben
        $handle=fopen($filename, "a");
        fwrite($handle,$result);             // den Index aus dem result File übernehmen
        fclose($handle);
        }

    /*
     * writefilePhp wie writefileJson
     * schreibt einen String in die Datei $this->filename, wenn nicht vorhanden (false) dann in $this->newFileName
     *
     ***********/

    function writeFilePhp(&$result, $debug=false)
        {
        if ($this->fileName === false) $filename=$this->newFileName;
        else $filename=$this->fileName;

        if ($debug) echo "Neuer Filename $filename zum Schreiben.\n";
        if ($debug) 
            {
            echo "writeFilePhp: Analyse des Input Files: \n";
            }
        
        if (is_file($filename)) unlink($filename);      // in ein leeres File schrieben
        $handle=fopen($filename, "a");
        fwrite($handle,$result);             // den Index aus dem result File übernehmen
        fclose($handle);
        }

    /*************
     *
     *  find all columns and lines in an array
     *
     ***************/

    function findColumnsLines(&$resultBackupDirs, $debug=false)
        {
        $columns=array(); $lines=array();
        $row=0; $rowMax=5;
        foreach ($resultBackupDirs as $key => $line)
            {
            if ( ($row++<$rowMax) && $debug )
                {
                echo $key.":\n"; 
                //print_r($resultBackupDirs[$key]);
                }
            $lines[$key]=true;
            foreach ($line as $column => $entry)
                {
                $columns[$column]=$column;
                } 
            }
        if ($debug) echo "Insgesamt ".sizeof($lines)." Zeilen und ".sizeof($columns)." Spalten erkannt: \n";
        $columns = ["Filename" => "Filename"] + $columns;
        //print_r($columns);
        $result["columns"]=$columns;
        $result["lines"]=$lines;
        return ($result);
        }

    /* Ausgabe des Arrays mit einer max Anzahl von Zeilen machen, damit Output Puffer nicht überlastet wird */

    function writeArray(&$resultBackupDirs)
        {
        $result=$this->findColumnsLines($resultBackupDirs);
        echo "Array ist ".count($result["columns"])." Spalten und ".count($result["lines"])." Zeilen gross.\n";
        $row=0; $rowMax=50;
        foreach ($resultBackupDirs as $key => $line)
            {
            echo "Index is ".$key.":\n"; 
            print_r($line);
            if ($row++>$rowMax) break;
            }
        }

    }    // ende class




/**************************************************************************************************************************
 *
 * timerOps
 *
 * Timer Routinen von class timerHandling OperationCenter übernommen
 * ohne fixe Zuordnung der scriptIDs
 * 
 *  getAllEvents
 *  get_eventlist
 *  write_eventlist
 *  filter_eventlist
 *
 *  CreateTimerHour
 *  CreateTimerSync
 *  setTimerPerMinute
 *  getEventData             echo aktiv und zuletzt aufgerufen
 *  setDelayedEvent
 *  setEventTimer
 *  setDimTimer
 *  getEventTimerStatus
 *  getEventTimerID          eine TimerID holen oder rudimentär anlegen
 *  write
 *
 *
 *
 ******************************************************/

class timerOps
    {

    protected $eventlist=array();

    function __construct()
        {

        }
    
    public function getAllEvents($filter=false, $debug=false)
        {
        $resultEventList=array(); $result=array();
        $alleEreignisse = IPS_GetEventList();
        echo "timerOps::getAllEvents, insgesamt gibt es ".sizeof($alleEreignisse)." Events. ";
        if ($filter===false) echo "No Filter.";
        else if ($filter==0) echo "Filter Events.";
        else if ($filter==1) echo "Filter Cyclic Timers.";
        else if ($filter==2) echo "Filter Week Plans.";
        echo "\n";
        //print_R($alleEreignisse);           // das sind schon mehr als 500 Events !!!
        foreach ($alleEreignisse as $index => $ereignisId)
            {
            if ($debug) echo str_pad($index,8).str_pad($ereignisId,8);
            $result["OID"]=$ereignisId;
            $result["Name"]=IPS_GetName($ereignisId);
            $result["Pfad"]=IPS_GetName(IPS_GetParent($ereignisId))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($ereignisId)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($ereignisId))))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent($ereignisId)))));
                        
            $details=IPS_GetEvent($ereignisId);
            //print_r($details);
            switch ($details["EventType"])
                {
                case 0:
                    if ($debug)  "Auslöser   ";
                    $result["Type"]="Auslöser";
                    break;
                case 1:
                    if ($debug)  "Zyklisch   ";
                    $result["Type"]="Zyklisch";
                    break;
                case 2:
                    if ($debug)  "Wochenplan ";
                    $result["Type"]="Wochenplan";
                    break;
                default:
                    if ($debug)  $details["EventType"];
                }
            if ( ($filter && ($details["EventType"]==$filter)) || ($filter===false) ) $resultEventList[$index]=$result;
            if ($debug) echo str_pad(IPS_GetName($ereignisId),30)."\n";            
            }
        $this->eventlist=$resultEventList;
        }

    public function get_eventlist()
        {
        return ($this->eventlist);    
        }

    public function write_eventlist()
        {
        foreach ($this->eventlist as $index => $item)
            {
            echo str_pad($index,8);
            $this->getEventData($item["OID"]);
            }
        }

    public function filter_eventlist($config=array())
        {
        if (is_array($config))
            {
            configfileParser($config,$action,["Status","status","State","state"],"Status",null);
            configfileParser($config,$action,["DateType","datetype"],"DateType",null);
            foreach ($this->eventlist as $index => $item)
                {
                $EreignisInfo = @IPS_GetEvent($item["OID"]);
                if ($EreignisInfo===false)
                    {
                    unset($this->eventlist[$index]);
                    echo "delete index $index , no event.\n";
                    }
                else
                    {
                    if (isset($action["Status"]))
                        {
                        if ($EreignisInfo["EventActive"]===$action["Status"])
                            {
                            if (isset($action["DateType"]))
                                {
                                if (($action["DateType"]==2) && ( ($EreignisInfo["CyclicDateType"]===$action["DateType"]) || ($EreignisInfo["CyclicDateType"]===0) ) )
                                    {
                                    // daily
                                    }
                                elseif ($EreignisInfo["CyclicDateType"]===$action["DateType"])
                                    {

                                    }  
                                else
                                    {
                                    unset($this->eventlist[$index]);
                                    }                                    
                                }
                            else echo str_pad($item["Name"],30).json_encode($EreignisInfo)."\n";   
                            }
                        else 
                            {
                            unset($this->eventlist[$index]);
                            }
                        }    
                    else echo json_encode($EreignisInfo)."\n";
                    }
                }
            }
        }


	/* CreateTimerHour
     * automatisch Timer kreieren, damit nicht immer alle Befehle kopiert werden müssen 
     * hier die Variante mit Angabe von Stunde und Minute pro Tag
     * Timer wird automatisch gestartet
     */
	public function CreateTimerHour($name,$stunde,$minute,$scriptID,$debug=false)
		{
		/* EventHandler Config regelmaessig bearbeiten */
			
		$timID=@IPS_GetEventIDByName($name, $scriptID);
		if ($timID==false)
			{
			$timID = IPS_CreateEvent(1);
			IPS_SetParent($timID, $scriptID);
			IPS_SetName($timID, $name);
			IPS_SetEventCyclic($timID,0,0,0,0,0,0);             //  IPS_SetEventCyclic (int $EventID, int $DateType, int $DateInterval, int $DateDay, int $DateDayInterval, int $TimeType, int $TimeInterval)
			IPS_SetEventCyclicTimeFrom($timID,$stunde,$minute,0);  /* immer um ss:xx */
			IPS_SetEventActive($timID,true);
			if ($debug) echo "   Timer Event ".$name." neu angelegt. Timer um ".$stunde.":".$minute." ist aktiviert.\n";
			}
		else
			{
			if ($debug) echo "   Timer Event ".$name." bereits angelegt. Timer um ".$stunde.":".$minute." ist aktiviert.\n";
			IPS_SetEventActive($timID,true);
			}
		return($timID);
		}

    /* automatisch Timer kreieren, damit nicht immer alle Befehle kopiert werden müssen 
     * hier die Variante die alle x Sekunden aufgerufen wird
     * Timer wird nicht automatisch gestartet
     */

	public function CreateTimerSync($name,$sekunden,$scriptID,$debug=false)
		{
		$timID = @IPS_GetEventIDByName($name, $scriptID);
		if ($timID==false)
			{
			$timID = IPS_CreateEvent(1);
			IPS_SetParent($timID, $scriptID);
			IPS_SetName($timID, $name);
			IPS_SetEventCyclic($timID,0,1,0,0,1,$sekunden);      // alle x sec, kein Datumstyp-täglich, keine Auswertung, Sekunden 
			//IPS_SetEventActive($tim2ID,true);
			IPS_SetEventCyclicTimeFrom($timID,0,2,$sekunden%60);  // damit die Timer hintereinander ausgeführt werden, Sekunden modulo 60
			if ($debug) echo "   Timer Event ".$name." neu angelegt. Timer $sekunden sec ist noch nicht aktiviert.\n";
			}
		else
			{
			if ($debug) echo "   Timer Event ".$name." bereits angelegt. Timer $sekunden sec ist noch nicht aktiviert.\n";
			IPS_SetEventCyclicTimeFrom($timID,0,2,$sekunden%60);  // damit die Timer hintereinander ausgeführt werden 
			//IPS_SetEventActive($tim2ID,true);
			}
		return($timID);
		}	

    function setTimerPerMinute($name, $scriptIdActivity, $minutes,$debug=false)
        {
        $tim4ID = @IPS_GetEventIDByName($name, $scriptIdActivity);
        if ($tim4ID==false)
            {
            $tim4ID = IPS_CreateEvent(1);
            IPS_SetParent($tim4ID, $scriptIdActivity);
            IPS_SetName($tim4ID, $name);
            /* das Event wird alle 5 Minuten aufgerufen, der Standard Sysping, wenn nicht als FAST gekennzeichnet, läuft allerdings alle 60 Minuten */
            IPS_SetEventCyclic($tim4ID,0,1,0,0,2,$minutes);      /* alle 5 Minuten , Tägliche Ausführung, keine Auswertung, Datumstage, Datumstageintervall, Zeittyp-2-alle x Minute, Zeitintervall */
            IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);
            IPS_SetEventActive($tim4ID,true);
            if ($debug) echo "   Timer Event $name neu angelegt. Timer $minutes Minuten ist aktiviert.\n";
            }
        else
            {
            if ($debug) echo "   Timer Event $name bereits angelegt. Timer $minutes Minuten ist aktiviert.\n";
            IPS_SetEventActive($tim4ID,true);
            IPS_SetEventCyclic($tim4ID,0,1,0,0,2,$minutes);      /* Tägliche Ausführung, keine Auswertung, Datumstage, Datumstageintervall, Zeittyp-2-alle x Minute, Zeitintervall */
            IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);
            }
        return ($tim4ID);
        }

    /* Infos über ein Timer Event ausgeben 
     */

    public function getEventData($EreignisID,$debug=false)
        {
        $EreignisInfo = @IPS_GetEvent($EreignisID);
        if ($EreignisInfo===false) echo "Timer $EreignisID not available.\n";
        else
            {
            $eventID = $EreignisInfo["EventID"];
            $lastrun=date("d.m.Y H:i:s",$EreignisInfo["LastRun"]);
            $nextrun=date("d.m.Y H:i:s",$EreignisInfo["NextRun"]);
            echo "$eventID ";
            if ($debug) $text = "(".IPS_GetName($eventID)."/".IPS_GetName(IPS_GetParent($eventID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($eventID))).")";
            else $text = "(".IPS_GetName($eventID).")";
            echo str_pad($text,72);
            echo " Lastrun $lastrun   Nextrun $nextrun  Status ".($EreignisInfo["EventActive"]?"Ein":"Aus")."  \n";
            //if ($debug) print_r($EreignisInfo);
            }
        }

    /* verwendet in IpsComponentSensor_Motion für die Delayed Bewegungungsereignisse
     *
     */

    public function setDelayedEvent($name,$scriptId,$delay,$execScript="",$debug=false)
        {
        if ($debug) echo "setDelayedEvent($name,$scriptId,$delay,$execScript,$debug) aufgerufen.\n";
        $EreignisID = @IPS_GetEventIDByName($name, IPS_GetParent($scriptId));
        if ($EreignisID === false)
            { 
            if ($debug) echo "Event nicht gefunden > neu anlegen.\n";
            $EreignisID = IPS_CreateEvent(1);
            IPS_SetName($EreignisID,$name);
            IPS_SetParent($EreignisID, IPS_GetParent($scriptId));
            }
        IPS_SetEventCyclic($EreignisID,0,1,0,0,1,$delay);           // konfigurierbar, zB alle 30 Minuten, d.h. 30 Minuten kann man still sitzen bevor keine Bewegung mehr erkannt wird 
        $zeit=time();
        $stunde=intval(date("H",$zeit),10);             // integer dezimal enkodiern
        $minute=intval(date("i",$zeit),10);             // integer dezimal enkodiern
        //IPS_SetEventCyclicTimeBounds($EreignisID,time(),0);         // damit die Timer hintereinander ausgeführt werden 
        IPS_SetEventCyclicTimeFrom($EreignisID,$stunde,$minute,0);  // (integer $EreignisID, integer $Stunde, integer $Minute, integer $Sekunde)
        if ($execScript !="") IPS_SetEventScript($EreignisID,$execScript);
        IPS_SetEventActive($EreignisID,true);

        }


	/* kommt von class Autosteuerung 
     * einen Timer anlegen und setzen, ist für ein einmaliges Event 
     */
    
    function setEventTimer($name,$delay,$command,$categoryIdApp,$debug=false)
	    {
    	if ($debug) echo "setEventTimer: Jetzt wird der Timer gesetzt : ".$name."_EVENT mit Zeitverzoegerung von $delay Sekunden. Befehl lautet : ".str_replace("\n","",$command)."\n";
	    IPSLogger_Dbg(__file__, 'Autosteuerung, Timer setzen : '.$name.' mit Zeitverzoegerung von '.$delay.' Sekunden. Befehl lautet : '.str_replace("\n","",$command));	
    	$zeit = time()+$delay;
    	$EreignisID = $this->getEventTimerID($name."_EVENT",$categoryIdApp);
    	IPS_SetEventActive($EreignisID,true);
	    IPS_SetEventCyclic($EreignisID, 1, 0, 0, 0, 0,0);
    	/* EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
	    /* EreignisID, 1 einmalig,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
        $tag     = intval(date("d",$zeit),10);             // integer dezimal enkodiern
        $monat   = intval(date("m",$zeit),10);             // integer dezimal enkodiern
        $jahr    = intval(date("Y",$zeit),10);             // integer dezimal enkodiern
        $stunde  = intval(date("H",$zeit),10);             // integer dezimal enkodiern
        $minute  = intval(date("i",$zeit),10);             // integer dezimal enkodiern
        $sekunde = intval(date("s",$zeit),10);             // integer dezimal enkodiern
        IPS_SetEventCyclicTimeFrom($EreignisID,$stunde,$minute,$sekunde);  // (integer $EreignisID, integer $Stunde, integer $Minute, integer $Sekunde) 
        IPS_SetEventCyclicDateFrom($EreignisID,$tag,$monat,$jahr);  // (integer $EreignisID, integer $Tag, integer $Monat, integer $Jahr)       
    	IPS_SetEventScript($EreignisID,$command);
	    }

	/* class Autosteuerung einen zyklischen Timer anlegen und setzen, ist für ein einmaliges Event 
     */

	function setDimTimer($name,$delay,$command,$categoryIdApp,$debug=false)
		{
		if ($debug) echo "setDimTimer: Jetzt wird der Timer gesetzt : ".$name."_EVENT_DIM"." und 10x alle ".$delay." Sekunden aufgerufen\n";
		IPSLogger_Dbg(__file__, 'Autosteuerung, Timer setzen : '.$name.' mit Zeitverzoegerung von '.$delay.' Sekunden. Befehl lautet : '.str_replace("\n","",$command));	
		$EreignisID = $this->getEventTimerID($name."_EVENT_DIM",$categoryIdApp);
   		IPS_SetEventActive($EreignisID,true);
   		IPS_SetEventCyclic($EreignisID, 0, 0, 0, 0, 1, $delay);
		/* EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 1 Sekuendlich,  Anzahl Sekunden */
		/* EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
		/* EreignisID, 1 einmalig,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
   		IPS_SetEventScript($EreignisID,$command);
		}

	/* für einen Timer Namen den Status zurückmelden 
     * beim Status wird auch die targetTime des Timers berücksichtigt, wenn die bereits in der Vergangenheit liegt kann der Timer auch noch aktiv sein
     *
     * verwendet in class Autosteuerung
     */

    function getEventTimerStatus($name,$categoryIdApp,$debug=false)
	    {
    	$EreignisID = $this->getEventTimerID($name,$categoryIdApp);         // timerID abfragen oder wenn nicht vorhanden einen Timer ohne besondere Parametrierung anlegen
        if ($debug) echo "Timer ID : ".$EreignisID."   (".IPS_GetName($EreignisID)."/".IPS_GetName(IPS_GetParent($EreignisID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($EreignisID))).")\n";
        $status=IPS_GetEvent($EreignisID);
        //print_r($status);
        //echo $status["EventActive"]."   ".date("Y-m-d H:i:s",$targetTime)."   ".$status["CyclicDateFrom"]["Day"].".".$status["CyclicDateFrom"]["Month"].".".$status["CyclicDateFrom"]["Year"]." ".$status["CyclicTimeFrom"]["Hour"].":".$status["CyclicTimeFrom"]["Minute"].":".$status["CyclicTimeFrom"]["Second"]."\n";
        $targetTime=strtotime($status["CyclicDateFrom"]["Day"].".".$status["CyclicDateFrom"]["Month"].".".$status["CyclicDateFrom"]["Year"]." ".$status["CyclicTimeFrom"]["Hour"]
            .":".$status["CyclicTimeFrom"]["Minute"].":".$status["CyclicTimeFrom"]["Second"]);
        if ( ($status["EventActive"]==true) && (time()<=$targetTime) ) $result=true;
        else $result=false;    
        return($result);
        }

	/*   
     * mit Übergabe categoryIdApp wird die EreignisID dort anhand des Namen gesucht
     * eine TimerID abfragen oder wenn nicht vorhanden einen Timer ohne besondere Parametrierung anlegen 
     *
     * verwendet in class Autosteuerung
     */

    function getEventTimerID($name,$categoryIdApp)
	    {
	    $EreignisID = @IPS_GetEventIDByName($name,  $categoryIdApp);
    	if ($EreignisID === false)
	    	{ //Event nicht gefunden > neu anlegen
		    $EreignisID = IPS_CreateEvent(1);
    		IPS_SetName($EreignisID,$name);
	    	IPS_SetParent($EreignisID, $categoryIdApp);
		    }
		return($EreignisID);
		}


    function write($string)
        {
        echo $string;
        }

    }

/**************************************************************************************************************************
 *
 * curlOps
 *
 * curl Routinen zusammengefasst
 *  
 *  downloadFile
 *  getJsonConfig
 *
 *
 *
 *
 ******************************************************/

class curlOps
    {

    function __construct()
        {

        }

    /* eine Datei in einem Target Verzeichnis speichern
     * $url = "https://www.7-zip.org/a/7zr.exe";
     *
     */
    function downloadFile($url, $dir, $debug=false)
        {
        $ch = curl_init($url);              // Initialize the cURL session
        $file_name = basename($url);
        $save_file_loc = $dir . $file_name;             // Save file into file location
        if ($debug) echo "downloadFile, save Url with Filename $file_name to $save_file_loc.\n";
        $fp = fopen($save_file_loc, 'wb');              // Open file für Target
        
        // It set an option for a cURL transfer
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        
        curl_exec($ch);             // Perform a cURL session
        
        curl_close($ch);           // Closes a cURL session and frees all resources
    
        fclose($fp);                // Close file

        return (true);
        }

    function getJsonConfig($url)
        {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url); //set the url we want to use
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result= curl_exec ($ch); 
        $config = json_decode($result,true);            
        return ($config);
        }

    }


/**************************************************************************************************************************
 *
 * geoOps siehe Startpage_Update
 *
 * geo Routinen zusammengefasst
 *  
 * Abstandsberechnung vereinfacht nach https://www.kompf.de/gps/distcalc.html 
 *  
 *      __construct
 *      distance
 *      writePosDegrees
 *      PosDDDegMinSec
 *      DDDegMinSec
 *      
 *      roundvariantfix
 *
 ******************************************************/


class geoOps
    {

    function __construct()
        {

        }

    /* distance = sqrt(dx * dx + dy * dy)
     * mit distance: Entfernung in km 
     * dx = 71.5 * (lon1 - lon2)                    Abstand Breitenkreisen für unsere breite 49 Grad
     * dy = 111.3 * (lat1 - lat2)                   Abstand Länge für unseren bereich 9 Grad
     * lat1, lat2, lon1, lon2: Breite, Länge in Grad
     */
    public function distance($pos1,$pos2)
        {
        $dx= 71.5*($pos1["north"]-$pos2["north"]);
        $dy= 111.3*($pos1["east"]-$pos2["east"]);
        $dist=sqrt($dx*$dx+$dy*$dy);
        return $dist;
        }

    public function writePosDegrees($posd)
        {
        return( $posd["north"]["deg"]."°". $posd["north"]["min"]."'". $posd["north"]["sec"]."\"N ".$posd["east"]["deg"]."°". $posd["east"]["min"]."'". $posd["east"]["sec"]."\"E");
        }

    public function PosDDDegMinSec($pos)
        {
        $posd=array();
        if (is_array($pos))
            {
            if (isset($pos["north"])) $posd["north"] = $this->DDDegMinSec($pos["north"]);    
            if (isset($pos["east"]))  $posd["east"]  = $this->DDDegMinSec($pos["east"]);    
            if (isset($pos["name"]))  $posd["name"]  = $pos["name"];    
            return $posd;
            }   
        return false; 
        }

    /* aus einem Gradwert mit Komma ein Grad Minuten Sekunden mit Komma machen
     */
    public function DDDegMinSec(float $DD)
        {
        $A = abs($DD);
        $B = $A * 3600;                                                 // in secs
        $C = round($B - 60 * $this->roundvariantfix($B / 60), 2);       // Runden auf 100stel secs

        if ($C == 60) {
            $D = 0;
            $E = $B + 60;                   // die hundertsel Rundung holt auf die volle Minute auf
        } else {
            $D = $C;
            $E = $B;
        }

        if ($DD < 0) {
            $DDDeg = $this->roundvariantfix($E / 3600) * (-1);
        } else {
            $DDDeg = $this->roundvariantfix($E / 3600);
        }
        $DDMin = fmod(floor($E / 60), 60);
        $DDSec = $D;

        return ["deg"=>$DDDeg,"min"=>$DDMin, "sec"=>$DDSec];

        }

    /* wenn Wert größer 0 floor, wenn kleiner 0 ist ceil nehmen
     * floor macht aus 4.3 -> 4, 9.999 -> 9, aber aus -3.14 -> -4 daher bei neg. Werten ceil und -3.14 -> -3
     * das heisst wir vernichten die Kommastellen, egal ob plus oder minus
     * der Wert bleibt float, geht daher auch für sehr grosse Zahlen
     */
    protected function roundvariantfix($value)
        {
        $roundvalue = 0;
        if ($value >= 0)
            $roundvalue = floor($value);
        elseif ($value < 0)
            $roundvalue = ceil($value);
        return $roundvalue;
        }

    }


/**************************************************************************************************************************
 *
 * errorAusgabe
 *
 * nicht verwendet
 *
 * 
 *
 ******************************************************/


class errorAusgabe
    {

    function __construct()
        {

        }

    function write($string)
        {
        echo $string;
        }

    }





/***********************************************************************************
 *
 * Die Komplette Installation von Components in einer Klasse zusammenfassen
 *
 * __construct
 * getArchiveSDQL_HandlerID
 * listOfRemoteServer
 * getStructureofROID
 *
 * registerEvent
 * getComponent
 * workOnDeviceList
 * workOnHomematicList
 * addOnKeyName
 * getKeyword
 * installComponent  (DEPRICIATED)
 * installComponentFull
 *
 *
 * setLogging
 * getLoggingStatus
 *
 *
 *********************************************************************************************/

class ComponentHandling
    {

    private $archiveHandlerID; 
    private $archiveSQL_HandlerID;                              // zusätzliches Logging in MySQL  

    private $installedModules, $debug;
    private $remote, $messageHandler;
    //private $congigMessage;

    /* ComponentHandling Klasse initialisiseren 
     * kann archiveHandlerID und archiveSDQL_HandlerID
     * wenn Modul vorhanden auch RemoteAccess
     *
     */
	public function __construct($debug=false)
        {
        $this->debug=$debug;
        $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
		$this->installedModules=$moduleManager->GetInstalledModules();
		if (isset ($this->installedModules["RemoteAccess"]))
			{
			IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
			IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");			
			$this->remote=new RemoteAccess();
            }
	    $this->messageHandler = new IPSMessageHandler();
        $this->configMessage=IPSMessageHandler_GetEventConfiguration();
        
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
        if (isset($modulhandling->getInstances("Archive Control MySQL")[0])) $this->archiveSDQL_HandlerID=$modulhandling->getInstances("Archive Control MySQL")[0];
        else $this->archiveSDQL_HandlerID=false;
        }

    /* ComponentHandling::getArchiveSDQL_HandlerID
     * für den Fall dass ein entsprechendes MariaDB Modul installiert ist die passende Instanz dafür zurück geben 
     */ 
    public function getArchiveSDQL_HandlerID()
        {
        return($this->archiveSDQL_HandlerID);
        }

    /* ComponentHandling::listOfRemoteServer
     * Liste der remote Server ausgeben 
     */
    public function listOfRemoteServer($debug=false)
        {
		$remServer=array();
		if (isset ($this->installedModules["RemoteAccess"]))
			{
			$status=$this->remote->RemoteAccessServerTable();
			$text=$this->remote->writeRemoteAccessServerTable($status);
        	$remServer=$this->remote->get_listofROIDs();
	        if ( ($text!="") && $debug)
    	        {
		       	echo "Liste der Remote Logging Server (mit Status Active und für Logging freigegeben):        \n";
            	echo $text;
    			echo "Liste der ROIDs der Remote Logging Server (mit Status Active und für Logging freigegeben):   \n";
		    	echo $this->remote->write_listofROIDs();
        	    }
            }
        return($remServer);
        } 

    /* ComponentHandling::getStructureofROID
     * die Remote Struktur für das Keyword auslesen, verwendet Modul RemoteAccess und kann Debug
     * wird von installComponentFull verwendet
     */
    public function getStructureofROID($keyword,$debug=false)
        {
        $struktur=array();
		if (isset ($this->installedModules["RemoteAccess"]))
			{        
			$struktur=$this->remote->get_StructureofROID($keyword);
            if ( ((sizeof($struktur))>0) && $debug)
                {
    			echo "      Struktur Server für $keyword ausgeben:             \n";
	    		foreach ($struktur as $Name => $Eintraege)
		    		{
			    	echo "        ".$Name." für $keyword  hat ".sizeof($Eintraege)." Eintraege \n";
				    //print_r($Eintraege);
    				foreach ($Eintraege as $Eintrag) echo "           ".$Eintrag["Name"]."   ".$Eintrag["OID"]."\n";
                    }
				}           
            }
        return($struktur);
        }

    /* ComponentHandling::register Event
     */
    public function registerEvent($oid,$update,$component,$module,$commentField)
        {
        if ($this->debug) echo "ComponentHandling::registerEvent($oid,'$update','$component','$module','$commentField'); aufgerufen.\n";    
		//$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		//$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
        $change=false;
        if (isset($this->configMessage[$oid])) 
            {
            if ($this->debug) echo "  $oid -> in Config gefunden:\n";
            $compare=$this->configMessage[$oid];
            if ( ($compare[0] != $update) || ($compare[1] != $component) || ($compare[2] != $module) ) 
				{
				$change = true;
				echo "    Event $oid neu konfigurieren mit \"$update\",\"$component\",\"$module\"\n";
				}
            }
		else 
			{
			$change = true;
			echo "    Event $oid neu registrieren mit \"$update\",\"$component\",\"$module\"\n";
			}
        if ($change)
            {
		    $this->messageHandler->RegisterEvent($oid,$update,$component,$module);
		    //echo "    Event $oid registriert mit \"$update\",\"$component\",\"$module\"\n";
			$eventName = $update.'_'.$oid;
			$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
			$eventId   = @IPS_GetObjectIDByIdent($eventName, $scriptId);
			if ($eventId) 
				{
				echo "    Event $eventName mit Kommentarfeld versehen.EventId $eventId gefunden.\n";
				IPS_SetInfo($eventId,$commentField);
				}
            }
        else echo "    Event $oid ist bereits korrekt mit \"$update\",\"$component\",\"$module\" registriert.\n";    
        }

    /***********************************************************************************
    *
    * ComponentHandling::getComponent, nach Keywords aus den Geräten in einer Liste die richtigen finden und entsprechend behandeln
    * Die Liste kann entweder die HardwareListe oder die DeviceListe aus EvaluateHardware sein, wird automatisch erkannt. Zusätzlich funktioniert jetzt auch eine MySQL Anbindung
    * 
    * Es wird aufgerufen wenn Elements ein Array ist entweder
    *       workOnDeviceList
    *       workOnHomematicList
    * sonst beginnt die MySQL Abarbeitung
    *
    * Abhängig vom Ausgabeswitch write wird 
    *       Array       array component mit den einzelnen COIDs und wenn debug echo result
    *       Install     array install mit den vollständigen Einträgen
    *       false       text result
    *
    * HardwareListe:
    * $keywords kann ein Eintrag oder ein Array sein
    * Die Elements sind die Geräteliste aus EvaluateHardware_include, sortiert nach Name, im COID sind die Unterobjekte nach denen die oder das Keyword verglichen wird
    * Bei einem array ist das ausschlaggebende Keyword immer das erste, die anderen keywords sind zusätzliche Vergleichsoperatoren
    *
    * Es gibt vorgefertigte TYPE_ keywords: TYPE_ACTUATOR, TYPE_CONTACT, TYPE_THERMOSTAT
    *
    * DeviceList,MySQL
    * kann nur mehr die TYPE_ keywords und erweitert mit REGISTER
    *
    * Return ist ein Array nit den wichtigsten Informationen über jeden Component
    * Anzeige der einzelnen Components erfolgt zB aus OperationCenterLibrary $DeviceManager->writeCheckStatus($result)
    *
    ****************************************************************************************/    

	function getComponent($Elements,$keywords, $write="Array", $debug=false)
		{
        $component=array(); $install=array(); $result="";
		$totalfound=false;
		
        if ($this->debug) $debug=true;
        if ($debug)
            {
            $once=true;
            if ( is_array($keywords) )          // Keywords ist ein Array
                {
                if (is_array($Elements))                    // Elements ist ein Array
                    {
                    echo "   getComponent: Passende Geraeteregister in Elements suchen für ";
                    }
                else 
                    {
                    echo "   getComponent: Passende Geraeteregister in MySQL Database suchen für ";
                    }
                foreach ($keywords as $index => $entry) echo "\"$index => $entry\" ";
                echo ":\n";
                }
            else 
                {
                echo "   getComponent: Passende Geraeteregister in ELements suchen für \"$keywords\" :\n";
                if (is_array($Elements)===false) echo "MySQL Database oder Fehler ?\n";                    
                }
            }
        else $once=false;		

        if (is_array($Elements))
            {
            /* für alle Instanzen in der Liste machen, keyword muss vorhanden sein */
            if ($debug) echo "     Elements ist ein array mit ".sizeof($Elements)." Eintraegen. Nichts weiter tun. \n";
            foreach ($Elements as $Key)
                {
                $count=0; $countNo=0; $max=0; $maxNo=0; $found=false;

                if ( (isset($Key["Type"])) && (isset($Key["Instances"])) )
                    {
                    /******* devicelist als Formattierung */  
                    if ($debug && $once) echo "     ****** devicelist als Formattierung, workOnDeviceList aufrufen.\n";
                    $count++; 
                    $keyName=$this->workOnDeviceList($Key, $keywords,$debug);
                    //if ($debug) echo "       Aufruf workOnDeviceList(".json_encode($Key).", ".json_encode($keywords).",$debug).\n";                       
                    }               // ende deviceList durchsuchen
                else    
                    {
                    /********** hardwareList als Formattierung 
                    * Übergabe entweder mit einem Keyword oder einem array
                    * Hardwareliste ist nach COIDs organisiert
                    */                    
                    //echo " getComponent HardwareList Entry: \n"; print_r($Key); 
                    if ($debug && $once) echo "     ****** hardwarelist als Formattierung, workOnHomematicList aufrufen.\n";
                    $keyName=$this->workOnHomematicList($Key, $keywords,$debug);
                    }           // Ende Hardware Liste durchsuchen
                if (isset($keyName[0]))
                    {
                    if ($debug) echo "Mehrere Ergebnisse erkannt.\n";
                    foreach ($keyName as $index => $entry)
                        {
                        if (isset($entry["Name"]))
                            {
                            if ($debug) echo "  Gefunden ".$entry["Name"]." : ".json_encode($entry)."\n";
                            $totalfound=true;
                            $this->addOnKeyName($entry,$debug);                      // Array entry wird ausgehend von OID,COID,KEY,Name erweitert um 
                            $component[]=(integer)$entry["COID"];
                            $install[$entry["Name"]]=$entry;
                            if ($this->debug) $result .= "  ".str_pad($entry["Name"]."/".$keyword,50)." = ".GetValueIfFormatted($coid)."   (".date("d.m H:i",IPS_GetVariable($coid)["VariableChanged"]).")       \n";
                            }   // ende Found
                        }
                    }
                if (isset($keyName["Name"]))
                    {
                    if ($debug) echo "Gefunden ".$keyName["Name"]." : ".json_encode($keyName)."\n";
                    $totalfound=true;
                    $this->addOnKeyName($keyName,$debug);                      // Array keyname wird ausgehend von OID,COID,KEY,Name erweitert um 
                    $component[]=(integer)$keyName["COID"];
                    $install[$keyName["Name"]]=$keyName;
                    if ($this->debug) $result .= "  ".str_pad($keyName["Name"]."/".$keyword,50)." = ".GetValueIfFormatted($coid)."   (".date("d.m H:i",IPS_GetVariable($coid)["VariableChanged"]).")       \n";
                    }   // ende Found
                $once=false;                // nur einmal manches ausgeben    
                } /* Ende foreach elements  */
            }           // Ende is_array
        else
            {           // MySQL Datenbank, es gibt keine Elementsliste als Übergabe
            if ($debug) echo "         Aufruf der MySQL Datenbank ";
            IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
            IPSUtils_Include ("EvaluateHardware_Configuration.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            $Elements=array();          // umdefinieren, damit wenn nichtsgefunden wird die Fehlerausgabe nicht scheitert
            $typeChanKey="?"; $typeRegKey="?";             
            if ( is_array($keywords) )
                {
                foreach ($keywords as $index => $entry)
                    {
                    if ( ((strtoupper($index)) == "TYPECHAN") || ($index === 0) )  $typeChanKey=$entry; 
                    if ( ((strtoupper($index)) == "REGISTER") || ($index === 1) )  $typeRegKey=$entry; 
                    //if ($once) echo "        $index => $entry \n";
                    }
                }
            else echo "Error\n";
            if ($debug) echo " mit Channel $typeChanKey und Register $typeRegKey.\n";
            $keyName=array();
            //$oids=getfromDatabase($typeChanKey,$typeRegKey,false,$debug);           // dritter Parameter ist alternative
            $oids=getfromDatabase($typeChanKey,$typeRegKey);           // dritter Parameter ist alternative, keine Debuginfos ausgeben
            //if ($debug) print_r($oids);
            $install=array();
            if ($oids!==false)          // Fehlerausgabe wenn keine datenbank installiert ist auch hier noch einmal abfangen
                {            
                foreach ($oids as $oid)
                    {
                    $totalfound=true;
                    $keyName["Name"]=$oid["Name"];
                    $keyName["OID"]=$oid["OID"];
                    $keyName["COID"]=$oid["COID"];
                    $keyName["KEY"]=$oid["TypeRegKey"];
                    $keyName["COMPONENT"]=$oid["componentName"];
                    $keyName["MODULE"]=$oid["moduleName"];
                    $this->addOnKeyName($keyName,$debug);                          // hier alle Zusatzinformationen dazupacken
                    
                    $component[]=(integer)$keyName["COID"];
                    $install[$keyName["Name"]]=$keyName;
                    }
                }
            }               // ende MySQL Analyse

		if ( (!$totalfound) && (sizeof($Elements)>0) ) if ($debug) echo "************getComponent, Warning, keine Einträge für ".json_encode($keywords)." gefunden.\n";
        switch ($write)
            {
            case "Array":
                if ($debug) echo $result;
                return ($component);
                break;
            case "Install":         // InstallComponentFull ruft mit keyword "install" auf, zurück kommt $install[Name] => keyname
                //if ($debug) echo $result;
                return ($install);
                break;
            default:
                return ($result);
                break;
            }
        return (false);             // Ergebnis wird schon vorher zurückgemeldet, abhängig von write
		}	

    /* ComponentHandling::workOnDeviceList
     * Handle Keys and Keywords on deviceList 
     *      Key         Eintrag eines Gerätes/device
     *      keywords    etwas wie ["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "CO2"]
     *
     * return Keyname mit einem oder mehreren Einträgen
     *   beinhaltet folgende Informationen:
     *              ok ["Name"]
     *              ok ["COID"]=(integer)$coid;
     *              ok ["OID"]=$oid;
     *              ok ["KEY"]=$keyword;
     *
     *               $install[$keyName]["TYP"]=$variabletyp;
     *               $install[$keyName]["INDEX"]=$index;
     *               $install[$keyName]["PROFILE"]=$profile;					 
     *               $install[$keyName]["DETECTMOVEMENT"]=$detectmovement;
     *               $install[$keyName]["INDEXNAMEEXT"]=$indexNameExt;
     */

    function workOnDeviceList($Key, $keywords, $debug=false)
        {
        //$debug=false;     
        //if ($debug) echo "workOnDeviceList aufgerufen von getComponent mit den Parametern (".json_encode($Key).", ".json_encode($keywords).",$debug):\n";  
        $keyName=array();
        $keyNames=array();          // es kann auch mehrere Ergbenisse innerhalb eines Device geben, zuerst beim Durchgangssensor aufgetreten

        $once=false;
        $typeChanKey="?"; $typeRegKey="?"; 
        if ( is_array($keywords) )
            {
            foreach ($keywords as $index => $entry)
                {
                if ( ((strtoupper($index)) == "TYPECHAN") || ($index === 0) )  $typeChanKey=$entry; 
                if ( ((strtoupper($index)) == "REGISTER") || ($index === 1) )  $typeRegKey=$entry; 
                //if ($once) echo "        $index => $entry \n";
                }
            }
        else $typeChanKey=$keywords;
        //if ($debug) echo "   workOnDeviceList, Umsetzung der Eingabe auf $typeChanKey und $typeRegKey. Wir suchen in der Devicelist einen Channel mit einer Instanz mit Namen $typeChanKey \n";

        if (isset($Key["Channels"]))
            {
            foreach ($Key["Channels"] as $index => $instance)       // es gibt mehrere channels, alle channels durchgehen, index
                {
                //print_r($instance);
                if (isset($instance[$typeChanKey]))         /* gibt es denn eine TYPECHAN Eintrag im Array */
                    {
                    //if ($debug) echo "    workOnDeviceList, first success \"$typeChanKey\" found ".json_encode($instance[$typeChanKey]).". Check now register \"$typeRegKey\" as well.\n";         // Register may still be wrong, then return empty array 
                    $keyName["OID"] = $Key["Instances"][$index]["OID"];
                    $oid = $keyName["OID"];
                    $channelTypes   = $Key["Channels"][$index]["TYPECHAN"];
                    $types = explode(",",$channelTypes);
                    $keyName["COID"]=false;
                    if (array_search($typeChanKey,$types) !== false)            // ungleich false, da tatsächliche Position zurückgemeldet wird, also auch 0
                        {
                        $channelRegister = $Key["Channels"][$index][$typeChanKey];    
                        foreach ($channelRegister as $IDkey => $varName)
                            {
                            if ($IDkey == $typeRegKey)
                                {
                                if ($debug) echo "   $IDkey gefunden,suche $varName in $oid !\n";
                                $keyName["COID"]=@IPS_GetObjectIDByName($varName,$oid);
                                $keyName["KEY"]=$typeRegKey;
                                if ( ($debug) && false) 
                                    {
                                    if (isset($keyName["Name"])) echo "        DeviceList für TYPECHAN => $typeChanKey und REGISTER => $typeRegKey gefunden : ".$keyName["Name"]."  ".$keyName["OID"]."  $channelTypes \n";
                                    else  echo "        DeviceList für TYPECHAN => $typeChanKey und REGISTER => $typeRegKey gefunden : ".$keyName["OID"]."  $channelTypes \n";
                                    }
                                }
                            elseif ($typeRegKey=="?") 
                                {
                                $keyName["COID"]=@IPS_GetObjectIDByName($varName,$oid);
                                $keyName["KEY"]=$varName;
                                if ($debug) 
                                    {
                                    if (isset($keyName["Name"])) echo "        DeviceList für TYPECHAN => $typeChanKey gefunden : ".$keyName["Name"]."  ".$keyName["OID"]."  $channelTypes \n";                                
                                    else echo "        DeviceList für TYPECHAN => $typeChanKey gefunden : ".$keyName["OID"]."(".IPS_GetName($keyName["OID"]).")  $channelTypes \n";
                                    }
                                }
                            }
                        }
                    //echo "       TYPECHAN: Eintrag $oid gefunden. ".IPS_GetName($oid)."\n";                                            
                    //print_r($Key["Channels"][$index]);
                    //if ($keyName["COID"]==false) echo "COID in $oid (".IPS_GetName($oid).") nicht gefunden, IPS_GetObjectIDByName($varName,$oid)\n";
                    $keyName["Name"]=$instance["Name"];
                    $keyNames[]=$keyName;
                    //if ($debug) echo " getComponent: DeviceList für TYPECHAN => $typeChanKey und REGISTER => $typeRegKey gefunden : ".$keyName["Name"]."  ".$keyName["OID"]."  $channelTypes \n";
                    } 
                }                                
            }
        if (sizeof($keyNames)>1) 
            {
            if ($debug) echo "getComponent, workOnDeviceList, Info : mehrere Ergebnisse in einer Instanz gefunden: ";           // normale betriebsart, kein fehler, nur unterschiedliche Art Daten zurückzumelden
            foreach ($keyNames as $index => $keyName) 
                {
                if ($debug) echo $keyName["Name"]." ";
                if ( (isset($keyName["KEY"]) === false) || ($keyName["COID"] === false) ) $keyNames[$index]=array();              // ohne gesetztem Key oder nicht gefundenem COID auch nichts gefunden, nachtraeglich korrigieren
                }
            if ($debug) echo "\n";
            return $keyNames;       // mit einem Index zurückgeben 
            }
        if ( (isset($keyName["KEY"]) === false) || ($keyName["COID"] === false) ) $keyName=array();              // ohne gesetztem Key oder nicht gefundenem COID auch nichts gefunden, nachtraeglich korrigieren
        if ($debug)
            {
            if (isset($keyName["Name"])) 
                {
                //print_r($keyName);
                }
            else 
                {
                //echo "   workOnDeviceList, Eingabe auf $typeChanKey und $typeRegKey in ".sizeof($Key)." Channels nicht gefunden.\n";
                }
            }
        return $keyName;            // ohne Index zurückgeben
        }

    /* ComponentHandling::workOnHomematicList
     * Handle Keys and Keywords on homematicList 
     * von getComponent aufgerufen, zur Vereinfachung der Darstellung
     *
     */

    function workOnHomematicList($Key, $keywords, $debug=false)
        {
        if ($debug) echo "workOnHomematicList mit Keywords ".json_encode($keywords)." aufgerufen.\n";
        $keyName=array();       // wenn nix gefunden wurde istz das Array leer            
        $count=0; $countNo=0; $max=0; $maxNo=0; $found=false;             
        if ( is_array($keywords) == true )      // Übergabe Array mit Keywords für die Hardware Liste, kann auch NOT
            {
            foreach ($keywords as $entry)
                {
                /* solange das Keyword uebereinstimmt ist alles gut */
                if (strpos($entry,"!")===false)
                    {
                    $max++;
                    if (isset($Key["COID"][$entry])==true) $count++; 
                    //echo "    Ueberpruefe  ".$entry."    ".$count."/".$max."\n";
                    }
                elseif  (strpos($entry,"!")==0)
                    {
                    $maxNo++;
                    $entry1=substr($entry,1);
                    if (isset($Key["COID"][$entry1])==true) $countNo++;
                    //echo "    Ueberpruefe  NICHT ".$entry1."    ".$countNo."/".$maxNo." \n";
                    }
                }
            if ( ($max == $count) && ($countNo == 0) ) 
                { 
                $found=true; 
                //echo "**gefunden\n";
                }
            if (isset($keywords[0])==false) 
                {
                echo "workOnHomematicList, Error, Keywords for Key $key do not make sense to us:\n";
                print_R($keywords);
                }
            else $keyword=$keywords[0];	
            }	
        else                                    // Übergabe Keyword
            {
            if (isset($Key["COID"][$keywords])==true) 
                { 
                $found=true;  
                }
            $keyword=$keywords; 
            }	
        
        $typeKeyword=$keyword;
        if ( (isset($Key["Device"])==true) && ($found==false) )
            {
            /* Vielleicht ist ein Device Type als Keyword angegeben worden.\n" */
            if ($Key["Device"] == $typeKeyword)
                {
                //echo "      Ein Gerät mit der Device Bezeichnung $keyword gefunden.\n";
                $found=true; 
                switch ($keyword)
                    {
                    case "TYPE_ACTUATOR":
                        if (isset($Key["COID"]["LEVEL"]["OID"]) == true) $keyword="LEVEL";
                        elseif (isset($Key["COID"]["VALVE_STATE"]["OID"]) == true) $keyword="VALVE_STATE";
                        $detectmovement="HeatControl";
                        break;
                    case "TYPE_THERMOSTAT":
                        if (isset($Key["COID"]["SET_TEMPERATURE"]["OID"]) == true) $keyword="SET_TEMPERATURE";
                        if (isset($Key["COID"]["SET_POINT_TEMPERATURE"]["OID"]) == true) $keyword="SET_POINT_TEMPERATURE";
                        if (isset($Key["COID"]["TargetTempVar"]["OID"]) == true) $keyword="TargetTempVar";
                        break;
                    case "TYPE_CONTACT":
                        if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) ) { $keyword="CONTACT"; $registerName="STATE"; }        // nicht STATE verwenden, später umdrehen
                        //if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) ) $keyword="STATE";
                        if ($debug) echo "            TYPE_CONTACT gefunden. Keyword ist jetzt $keyword.\n";
                        $detectmovement="Contact";
                        break;							
                    default:	
                        echo "FEHLER: unknown keyword.\n";
                    }
                }
            }
        if ($found)
            {
            if ( ($typeKeyword!=$keyword) && ($typeKeyword=="TYPE_CONTACT") && ($keyword=="CONTACT") ) 
                {
                // Sonderbehandlung weil keyName["KEY"]=CONTACT sein muss, aber das Register anders heisst 
                $keyName["COID"]=(integer)$Key["COID"][$registerName]["OID"];
                }
            else $keyName["COID"]=(integer)$Key["COID"][$keyword]["OID"];
            
            $keyName["Name"]=$Key["Name"];
            $keyName["KEY"]=$keyword;
            $keyName["OID"]=(integer)$Key["OID"];                                                     
            }            
        return $keyName;
        }

    /* ComponentHandling, Zuweisung von Orientierungshilfen für das Anlegen der Variablen. addOnKeyName wird von folgenden Routinen aufgerufen:   getComponent
     *
     *  Index           DetectMovement
     *  HeatSet 
     *  Temperatur      Temperatur
     *  Humidity        Feuchtigkeit
     *  HeatControl     HeatControl
     *  Schalter
     *  Bewegung        Motion
     *  Helligkeit      Helligkeit
     *  Bewegung        Contact
     *  Klima
     *
     * Index wird für die Struktur der RemoteServer Speicherung verwendet.
     * DetectMovement für die lokale Speicherung
     * 
     * ["COID"]=(integer)$oid;               das Register
     * ["OID"]=(integer)$Key["OID"];         die Instanz
     * ["KEY"]=$keyword;
     * ["TYP"]=$variabletyp;
     * ["INDEX"]=$index;
     * ["PROFILE"]=$profile;					 
     * ["DETECTMOVEMENT"]=$detectmovement;
     * ["INDEXNAMEEXT"]=$indexNameExt;
     *
     *
     * RPC_CreateVariableByName($rpc, (integer)$Server["Bewegung"], $Key["Name"], 0);
     * index="Bewegung"
     *
     * Anhand keyname["KEY"] wird entschieden wie es weitergeht:
     *
     *
     */

    function addOnKeyName(&$keyName,$debug=false)
        {
        //if ($debug) echo "addOnKeyName  based on ".$keyName["KEY"]."  \n";
	    $detectmovement=false; 
        $profile=""; 
        $indexNameExt="";
        $update="OnChange";
        
        switch (strtoupper($keyName["KEY"]))
            {
            case "TARGETTEMPVAR":			/* Thermostat Temperatur Setzen */
            case "SET_POINT_TEMPERATURE":
            case "SET_TEMPERATURE":
                $variabletyp=2; 		/* Float */
                $index="HeatSet";
                $detectmovement="HeatSet";
                //$profile="TemperaturSet";		/* Umstellung auf vorgefertigte Profile, da besser in der Darstellung */
                $profile="~Temperature";
                break;	
            case "CONTROL_MODE":                // Thermostat Homematic und HomematicIP
            case "SET_POINT_MODE":
            case "TARGETMODEVAR":				// Thermostat FHT
                $variabletyp=1; 		/* Integer */
                $index="HeatSet";
                $indexNameExt="_Mode";								/* gemeinsam mit den Soll Temperaturwerten abspeichern */
                $profile="mode.HM";             // privates Profil für Formattierung RemoteAccess Variable verwenden, da nicht sichergestellt ist das das jeweilige Format der Harwdare auf der Zielmaschine installliert ist
                break;                    			
            case "TEMERATUREVAR";			/* Temperatur auslesen */
            case "TEMPERATURE":             // auch von devicelist normaler Temperatursensor
            case "ACTUAL_TEMPERATURE":
                $detectmovement="Temperatur";				
                $variabletyp=2; 		/* Float */
                $index="Temperatur";
                //$profile="Temperatur";		/* Umstellung auf vorgefertigte Profile, da besser in der Darstellung */
                $profile="~Temperature";
                break;
            case "HUMIDITY":
            case "ACTUAL_HUMIDITY":
                $detectmovement="Feuchtigkeit";
                $variabletyp=2; 		/* Float */							
                //$index="Humidity";
                $index="Feuchtigkeit";
                $indexNameExt="_Humi";								/* gemeinsam mit den CO2 Werten abspeichern */                
                $profile="Humidity";
                break;
            case "POSITIONVAR":
            case "VALVE_STATE": 
                $detectmovement="HeatControl";
                $variabletyp=2; 		/* Float */
                $index="HeatControl";
                $profile="~Valve.F";
                break;					
            case "LEVEL":
                $detectmovement="HeatControl";
                $variabletyp=1; 		/* Integer */	
                $index="HeatControl";
                $profile="~Intensity.100";
                break;
            case "STATE":
            case "STATUSVARIABLE":
                $variabletyp=0; 		/* Boolean */	
                $index="Schalter";
                $profile="Switch";
                break;
            case "TYPE_THERMOSTAT":		/* known keywords, do nothing, all has been done above */	
            case "TYPE_ACTUATOR":
                break;
            case "MOTION":
                $detectmovement="Motion";
                $variabletyp=0; 		/* Boolean */					
                $index="Bewegung";
                $profile="Motion";
                break;	
            case "DIRECTION":                   // Durchgangssensor, testweise gleich wie ein Bewegungserkenner machen
                $detectmovement="Motion";
                $variabletyp=0; 		/* Boolean */					
                $index="Bewegung";
                $profile="Motion";
                $update="OnUpdate";                
                break;	
            case "BRIGHTNESS":                              // selber Component wie Motion
                $detectmovement="Helligkeit";
                $variabletyp=1; 		/* Integer */					
                $index="Helligkeit";
                $profile="Helligkeit";                  // Variablen Profil
                break;
            case "ENERGY":                             
                $variabletyp=2; 		            // Float 
                $index="Stromverbrauch";
                $profile="~Electricity";                  // Variablen Profil
                break;
            case "POWER":                             
                //$detectmovement="Helligkeit";
                $variabletyp=2; 		            // Float 
                $index="Stromverbrauch";
                $profile="~Power";                  // Variablen Profil ist kW, irgendwo muss umgerechnet werden
                break;
            case "CONTACT":
                $detectmovement="Contact";
                $keyName["Key"]="STATE";                        // Eigenen Index Key definieren
                $variabletyp=1; 		                        // Integer, Kontakte können mehrer Zustände haben , gibt manchmal auch gekippt , war früher Boolean 					
                //$index="Bewegung";
                $index="Kontakte";                              // Kategorie in Webfront/Administrator/RemoteAccess
                $profile="Window";                             // abgeleitet von Window.HM, ist ein Integer Profil oder Boolean Contact
                break;
            case "CO2":
                $detectmovement="Climate";
                $variabletyp=1; 		/* Integer */	
                $index="Klima";
                $indexNameExt="_CO2";								/* gemeinsam mit den Baro Werten auf den remote Servern abspeichern */                
                $profile="CO2";
                break;
            case "BAROPRESSURE":
                $detectmovement="Climate";
                $variabletyp=2; 		/* Float */	
                $index="Klima";
                $indexNameExt="_Baro";								/* gemeinsam mit den CO2 Werten abspeichern */                
                $profile="Pressure";
                break;   
            case "RAIN_COUNTER":
                $detectmovement="Weather";              // vorerst gemeinsam mit Climate bearbeiten, aber eigene Erkennung
                $variabletyp=2; 		/* Float, Typ Variable am remote Server */	
                $index="Klima";         /* Struktur am Remote Server, muss schon vorher angelegt sein */
                $indexNameExt="_Rain";								/* gemeinsam mit den CO2 Werten abspeichern */                
                $profile="Rainfall";        /* profile am Remote Server, ähnlich wie für Mirror Register, umgestellt auf gemeinsames Custom Profile */
                $update="OnUpdate";
                break;           
            case "RAINING":
                $detectmovement="Weather";          // macht nur true wenn es zu regnen anfangt und false wenn es aufhört
                $variabletyp=0; 		            // Boolean, Typ Variable am remote Server 	
                $index="Klima";                     // Struktur am Remote Server, muss schon vorher angelegt sein
                $indexNameExt="_Raining";			// gemeinsam mit den CO2 Werten abspeichern                 
                $profile="Raining";                 // profile am Remote Server, ähnlich wie für Mirror Register, umgestellt auf gemeinsames Custom Profile abgeleitet von ~Raining
                $update="OnUpdate";
                break;           
            case "KEYSTATE":
                // kein detectmovement, false ist default
                $variabletyp=1; 		/* Integer */	
                $index="PowerLock";
                $profile="PowerlockBefehl";            
                break;                   
            case "LOCKSTATE":
                // kein detectmovement, false ist default
                $variabletyp=1; 		/* Integer */	
                $index="PowerLock";
                $profile="PowerlockStatus";            
                break;          
            case "DUTY_CYCLE_LEVEL":
                // kein detectmovement, false ist default
                $variabletyp=1; 		/* Integer */	
                $index="System";
                $profile="";                    // kein Profil
                break;                   
            default:	
                $variabletyp=0; 		/* Boolean */	
                echo "************AllgemeineDefinitionen::addOnKeyName, kenne ".strtoupper($keyName["KEY"])." nicht.\n";
                break;
            }

        if (isset($this->installedModules["DetectMovement"])===false) $detectmovement = false;    // wenn Modul nicht installiert auch nicht bearbeiten		
        //echo "********** ".$Key["Name"]."\n"; print_r($Key);
        //$keyName["COID"]=(integer)$coid;
        //$keyName["OID"]=$oid;
        //$keyName["KEY"]=$keyword;
        $keyName["TYP"]=$variabletyp;
        $keyName["INDEX"]=$index;
        $keyName["PROFILE"]=$profile;					 
        $keyName["DETECTMOVEMENT"]=$detectmovement;
        $keyName["INDEXNAMEEXT"]=$indexNameExt;	
        $keyName["UPDATE"]=$update; 
        if (isset($keyName["COID"])) 
            {
            if ($debug) 
                {
                echo "addonkeyname based on ".(strtoupper($keyName["KEY"])).", wichtig für ".$keyName["Name"]." ist COID: ".$keyName["COID"]." \n";
                print_R($keyName);
                }
            $variableType=IPS_GetVariable($keyName["COID"]);
            if (isset($variableType["VariableProfile"])) $keyName["VarProfile"]=$variableType["VariableProfile"];
            elseif (isset($variableType["VariableCustomProfile"])) $keyName["CustProfile"]=$variableType["VariableCustomProfile"];
            if (isset($variableType["VariableType"])) $keyName["VarType"]=$variableType["VariableType"];
            //print_r($variableType);
            //print_R($keyName);   
            }
        }

    /***********************************************************************************
    *
    * getKeyword
    *
    * aus dem Ergebnis von getComponent nur den Index herausholen, soll für alle Eintraege gleich sein 
    *
    ****************************************************************************************/    

	function getKeyword($result)
		{
		$keyword="";
		//print_r($result);	
		foreach ($result as $entry)
			{
			if ($keyword=="") $keyword = $entry["INDEX"];
			else
				{
				if ($keyword!=$entry["INDEX"]) echo "Fehler, unterschiedliche index erkannt, nicht eindeutig.\n";
				}
			}
		return ($keyword);
		}

    /***********************************************************************************
    *
    * DEPRECIATED
    *
    * verwendet von CustomComponents, RemoteAccess und EvaluateHeatControl zum schnellen Anlegen der Variablen
    * ist auch in der Remote Access Class angelegt und kann direkt aus der Klasse aufgerufen werden.
    *
    * Elements		Objekte aus EvaluateHardware, alle Homematic, alle FS20 etc.
    * keyword		Name des Children Objektes das enthalten sein muss, wenn array auch mehrer Keywords, erstes Keyword ist das indexierte
    * InitComponent	erster Parameter bei der Registrierung
    * InitModule		zweiter Parameter bei der Registrierung
    * parameter	wenn array parameter[oid] gesetzt ist, ist RemoteAccess vorhanden und aufgesetzt
    *
    * Ergebnis: ein zusaetzliches Event wurde beim Messagehandler registriert
    *
    ****************************************************************************************/
	
	function installComponent($Elements,$keywords,$InitComponent, $InitModule, $parameter=array())
		{
		//echo "InstallComponent aufgerufen.\n";
		foreach ($Elements as $Key)
			{
			//echo "  Evaluiere ".$Key["Name"]."\n";			
			/* alle Stellmotoren ausgeben */
			$count=0; $found=false;
			if ( is_array($keywords) == true )
				{
				foreach ($keywords as $entry)
					{
					/* solange das Keyword uebereinstimmt ist alles gut */
					if (isset($Key["COID"][$entry])==true) $count++; 
					//echo "    Ueberpruefe  ".$entry."    ".$count."/".sizeof($keywords)."\n";
					}
				if ( sizeof($keywords) == $count ) $found=true;
				$keyword=$keywords[0];	
				}	
			elseif (isset($Key["COID"][$keywords])==true) { $found=true; $keyword=$keywords; }	
			if ($found)
				{		
				//echo "********** ".$Key["Name"]."\n";
				//print_r($Key);
				$oid=(integer)$Key["COID"][$keyword]["OID"];
				$variabletyp=IPS_GetVariable($oid);
                if ($this->debug)
                    {
    				if ($variabletyp["VariableProfile"]!="")
	    				{
		    			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
			    		}
				    else
					    {
    					echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
	    				}
                    }    
				if ( isset ($parameter[$oid]) )
					{
					echo "  Remote Access installiert, Gruppen Variablen auch am VIS Server aufmachen.\n";
					$messageHandler = new IPSMessageHandler();
					$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",$InitComponent.','.$parameter[$oid],$InitModule);
					}
				else
					{
					/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
					echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
					$messageHandler = new IPSMessageHandler();
					$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",$InitComponent.',',$InitModule);
					}			
				}
			} /* Ende foreach */		
		}	

    /***********************************************************************************
    *
    * ComponentHandling::installComponentFull, anlegen von CustomComponents Events
    *
    * verwendet zum schnellen und einheitlichen Anlegen der Variablen und Events für CustomComponents, RemoteAccess und EvaluateHeatControl 
    * ist auch in der Remote Access Class angelegt und kann direkt aus der Klasse aufgerufen werden.
    *
    * kann insgesamt drei Eingabemöglichkeiten
    *     - Hardwareliste, Format Homematic etc.
    *     - Geräteliste, Format deviceList
    *     - Datenbank, Eingabewerte werden direkt aus der MariaDB übernommen  
    *
    * Elements		Objekte aus EvaluateHardware, alle Homematic, alle FS20 etc.
    * keyword		Name des Children Objektes das enthalten sein muss, wenn array auch mehrer Keywords, erstes Keyword ist das indexierte
    * InitComponent	erster Parameter bei der Registrierung
    * InitModule		zweiter Parameter bei der Registrierung
    * 
    * Ergebnis: ein zusaetzliches Event wurde beim Messagehandler registriert
    *
    * funktioniert für Humidity, Temperature, Heat Control Actuator und Heat Control Set
    * wenn RemoteAccess Modul installiert ist werden die Variablen auch auf den Remote Vis Servern angelegt
    * die Erkennung ob es sich um das richtige Gerät handelt erfolgt über Keywords, die auch ein Array sein können:
    * 		Die Untervariablen(Children/COID einer Instanz werden verglichen ob einer der Variablen wie das keyword heisst
    * 		bei einem Array gilt die Und Verknüpfung - also Variablen für alle Keywords muessen vorhanden sein.
    * 		das Keyword kann auch ein Device Type sein, Evaluate Hardware speichert unter Device einen Device TYP ab
    *			TYPE_BUTTON, TYPE_CONTACT, TYPE_METER_POWER, TYPE_METER_TEMPERATURE, TYPE_MOTION
    *			TYPE_SWITCH, TYPE_DIMMER, 
    *			TYPE_ACTUATOR	setzt $keyword auf VALVE_STATE 
    *			TYPE_THERMOSTAT	setzt $keyword auf SET_TEMPERATURE, SET_POINT_TEMPERATURE, TargetTempVar wenn die COID Objekte auch vorhanden sind.
    *
    * Beispielsweise Aufruf von remoteAccess::EvaluateHomematic, keywords
    *           ["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "TEMPERATURE"]
    *           ["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "HUMIDITY"]
    *           ["TYPECHAN" => "TYPE_METER_HUMIDITY","REGISTER" => "HUMIDITY"]
    * EvaluateAndere
    *           ["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "CO2"]
    * etc.
    * mit dem Filter alle passenden Elemente suchen
    *
    ****************************************************************************************/
		
	function installComponentFull($Elements,$keywords,$InitComponent="", $InitModule="", $commentField="",$debug=false)
		{
        if ($debug) echo "installComponentfull mit Keywords ".json_encode($keywords)." aufgerufen:\n";
		$donotregister=false; $i=0; $maxi=600;		// Notbremse
        $struktur=array();          // Ergbenis, behandelte Objekte

		/* einheitliche Routine verwenden, Formatierung Ergebnis für Install 
         *      [COID] => 13434
         *      [OID] => 55104
         *      [KEY] => HUMIDITY
         *      [TYP] => 1
         *      [INDEX] => Humidity
         *      [PROFILE] => Humidity
         *      [DETECTMOVEMENT] => Feuchtigkeit
         *      [INDEXNAMEEXT] => 
         */   
		//echo "Passende Geraeteregister suchen:\n"; 
		$result=$this->getComponent($Elements,$keywords,"Install",$debug);        /* passende Geräte aus Elements anhand keywords suchen*/
        $count=(sizeof($result));				
        if ($debug) echo "installComponentfull , insgesamt $count Register für die Component Installation gefunden.\n";
		if  ($count>0) 											/* gibts ueberhaupt etwas zu tun */
			{		
			$keyword=$this->getKeyword($result);            // holt sich den ersten Wert von ["Index"] und kontrolliert die anderen
			/* Erreichbarkeit Remote Server nur einmal pro Aufruf ermitteln */
			$remServer=$this->listOfRemoteServer();
            echo "getStructureofROID($keyword \n";
            $struktur=$this->getStructureofROID($keyword,$debug);
            echo "-------------\n";
            if ($debug)
                {
                echo "Keyword für Component wird aus dem Resultat ermittelt : $keyword\n";
                //print_r($result);         // Ausgabe von getComponent 
                echo "Remote Server herausfinden und Struktur auslesen:\n";
                print_r($remServer); print_r($struktur);
      			echo "installComponentFull: Resultat für gefundene Geraeteregister verarbeiten:\n";
        		$archiveID=$this->getArchiveSDQL_HandlerID();       // nicht mehr benötigt, eigene function
                if ($archiveID) echo "MySQL Archiver installed and available. Archive Variables there as well:\n";
                }
        	foreach ($result as $IndexName => $entry)       // nur die passenden Geraete durchgehen, Steuergroessen alle in getComponent
      	    	{
	            if ($debug) 
                    { 
                    echo "----> $IndexName:\n"; print_r($entry); 
                    echo "DetectMovement Type (HeatControl,Feuchtigkeit,Temperatur,Movement,Contact):".$entry["DETECTMOVEMENT"].".\n";
                    echo "Component Typ in der Konfiguration :".$entry["KEY"]."\n";
                    }
				$oid=$entry["COID"];
                if ( ($this->debug) || ($debug) ) echo "  ".str_pad($IndexName."/".$entry["KEY"],50)." = ".GetValueIfFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
                $this->setLogging($oid);            // Archivieren für diese Variable aktivieren  
				if ($donotregister==false)      /* Notbremse, oder generell deaktivierbares registrieren */
					{                    
	   		        $detectmovement=$entry["DETECTMOVEMENT"];
    				if ($detectmovement !== false)          // Nachbearbeitung für HeatControl, Feuchtigkeit, Temperatur, Motion, Contact 
	    				{
		    			IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
			    		IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                        /*  unterstützt werden aktuell DetectSensorHandler, DetectClimateHandler, DetectHumidityHandler, DetectMovementHandler
                         *                        DetectContactHandler, DetectBrightnessHandler, DetectTemperatureHandler, DetectHeatControlHandler 
                         */
				    	switch ($detectmovement)
					    	{
						    case "Sensor":      //neu
							    $DetectSensorHandler = new DetectSensorHandler();						
    							$DetectSensorHandler->RegisterEvent($oid,"Sensor",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
	    						break;
                            case "Weather":     // nicht nur für Baro und Co2, auch für RegenCounter 
						    case "Climate":      //neu
							    $DetectClimateHandler = new DetectClimateHandler();						
    							$DetectClimateHandler->RegisterEvent($oid,"Climate",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
	    						break;
						    case "Brightness":
                            case "Helligkeit":
    							$DetectBrightnessHandler = new DetectBrightnessHandler();						
	    						$DetectBrightnessHandler->RegisterEvent($oid,"Brightness",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
		    					break;															
						    case "HeatControl":					
							    $DetectHeatControlHandler = new DetectHeatControlHandler();						
    							$DetectHeatControlHandler->RegisterEvent($oid,"HeatControl",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
	    						break;
						    case "HeatSet":					
							    $DetectHeatSetHandler = new DetectHeatSetHandler();						
    							$DetectHeatSetHandler->RegisterEvent($oid,"HeatSet",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
	    						break;
		    				case "Feuchtigkeit":
			    				$DetectHumidityHandler = new DetectHumidityHandler();		
				    			$DetectHumidityHandler->RegisterEvent($oid,"Feuchtigkeit",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
					    		break;
						    case "Temperatur":
    							$DetectTemperatureHandler = new DetectTemperatureHandler();						
	    						$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
		    					break;
						    case "Motion":
    							$DetectMovementHandler = new DetectMovementHandler();						
	    						$DetectMovementHandler->RegisterEvent($oid,"Motion",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
		    					break;		
						    case "Contact":
    							$DetectContactHandler = new DetectContactHandler();						
	    						$DetectContactHandler->RegisterEvent($oid,"Contact",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */
		    					break;															
			    			default:
                                echo "Fehler, kenne detectmovement $detectmovement nicht.\n";
				    			break;
					    	}		
					    }
    				$variabletyp=$entry["TYP"];         // für RPC calls um Variable am remote Server in einer vernünftigen Struktur zu speichern
	    			$index= $entry["INDEX"];            // vorgegebene Struktur die einmal angelegt wird
		    		$profile=$entry["PROFILE"];         // Profil dafür
                    $IndexNameExt=$entry["INDEXNAMEEXT"];

                    /* beim registrieren als Event den richtigen Componen/Module Name dazugeben, MySQL kennt das */
                    if ( (isset($entry["COMPONENT"])) && ($InitComponent == "") ) $InitComponent=$entry["COMPONENT"];
                    if ( (isset($entry["MODULE"])) && ($InitModule == "") ) $InitModule=$entry["MODULE"];
                    if (isset($entry["UPDATE"])) $update=$entry["UPDATE"]; else $update="OnChange";

			    	if (isset ($this->installedModules["RemoteAccess"]))
				    	{
						$i++; if ($i>$maxi) { $donotregister=true; }	        /* Notbremse */										
						$parameter="";
						foreach ($remServer as $Name => $Server)        /* es werden nur erreichbare Server behandelt */
							{
							$rpc = new JSONRPC($Server["Adresse"]);
							/* variabletyp steht für 0 Boolean 1 Integer 2 Float 3 String */
                            if (isset($Server[$index])===false)             // Workaround for wrong naming of Kategories in Remote Server 
                                {
                                echo "      Warning, unknown $index in ".json_encode($Server)."\n"; 
                                switch ($index)
                                    {
                                    case "Schalter":
                                        $index="Switch";
                                        echo "      Renamed Index from Schalter to Switch, continue\n";
                                        break;
                                    default: 
                                        echo "      Error, no, solution found, unknown index.\n";
                                        return(false);
                                    }
                                }                            
                            if ($debug) 
                                {
                                echo "     Erzeuge Variable in ".$Server[$index]." mit $IndexName$IndexNameExt und Variabletyp $variabletyp "; 
                                echo "und ".json_encode($struktur[$Name]);
                                echo ".\n";	
                                }
							$result=$this->remote->RPC_CreateVariableByName($rpc, (integer)$Server[$index], $IndexName.$IndexNameExt, $variabletyp,$struktur[$Name]);
							if ($debug) echo "     Setze Profil für $IndexName$IndexNameExt auf ".$Server["Adresse"]." direkt noch einmal auf \"$profile\" da es hier immer Probleme gibt ...\n";							
							$rpc->IPS_SetVariableCustomProfile($result,$profile);
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
							$parameter.=$Name.":".$result.";";
							$struktur[$Name][$result]["Status"]=true;
							$struktur[$Name][$result]["Hide"]=false;
							//$struktur[$Name][$result]["newName"]=$Key["Name"];	// könnte nun der IndexName sein, wenn weiterhin benötigt						
							}	
						/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
                        if ($InitComponent=="") echo "  >>>>Error, Component Missing in RegisterEvent($oid,\"OnChange\",$InitComponent,".$entry["OID"].",$parameter,".$entry["KEY"].",$InitModule,$commentField)   -> ".json_encode($entry)."\n";
    		            $this->RegisterEvent($oid,$update,$InitComponent.','.$entry["OID"].','.$parameter.','.$entry["KEY"],$InitModule,$commentField);
						}
					else
						{
						/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
						echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
						$this->RegisterEvent($oid,$update,$InitComponent.",".$entry["OID"].",".$entry["KEY"],$InitModule,$commentField);
						}			
					}           /* ende donotregister */
				} /* Ende foreach */
			}
		else 
			{
			echo "    Für Keyword ".json_encode($keywords)."  -> keine gefunden.\n";
			}				
        return ($struktur);
		}	

    /* ComponentHandling::setLogging
     */
    function setLogging($oid, $debug=false)
        {
        $archiveID = $this->getArchiveSDQL_HandlerID();
        if ( $archiveID && (ACmySQL_GetLoggingStatus($archiveID,$oid)==false) )
            {
            ACmySQL_SetLoggingStatus($archiveID,$oid,true);
            //ACmySQL_SetAggregationType($archiveID,$oid,0);            // es gibt nur einen Aggregation Type 0
            IPS_ApplyChanges($archiveID);
            echo "       Variable ".$oid." (".IPS_GetName($oid)."), mySQL Archiv logging für dieses Geraeteregister wurde aktiviert.\n";
            }
        /* check, es sollten auch alle Quellvariablen gelogged werden */
        if (AC_GetLoggingStatus($this->archiveHandlerID,$oid)==false)
            {
            /* Wenn variable noch nicht gelogged automatisch logging einschalten */
            AC_SetLoggingStatus($this->archiveHandlerID,$oid,true);
            AC_SetAggregationType($this->archiveHandlerID,$oid,0);
            IPS_ApplyChanges($this->archiveHandlerID);
            echo "       Variable ".$oid." (".IPS_GetName($oid)."), Archiv logging für dieses Geraeteregister wurde aktiviert.\n";
            }        
        }

    /* ComponentHandling::getLoggingStatus
     */

    function getLoggingStatus($oid, $debug=false)
        {
        $logStatus=false;
        if (AC_GetLoggingStatus($this->archiveHandlerID,$oid)==true) $logStatus=true;
        return($logStatus);
        }
        
    } // endof class ComponentHandling

    /***********************************************************************************
    *
    *  quick server ping to reduce error messages in log
    *
    **************************************************************************************/

    function quickServerPing($UrlAddress)
        {    		
        $method="IPS_GetName"; $params=array();
        $rpc = new JSONRPC($UrlAddress);
        //echo "Server : ".$UrlAddress." hat Uptime: ".$rpc->IPS_GetUptime()."\n";
        $data = @parse_url($UrlAddress);
        if(($data === false) || !isset($data['scheme']) || !isset($data['host']))
            throw new Exception("Invalid URL");
        $url = $data['scheme']."://".$data['host'];
        if(isset($data['port'])) $url .= ":".$data['port'];
        if(isset($data['path'])) $url .= $data['path'];
        if(isset($data['user']))
            {
            $username = $data['user'];
            }
        else
            {
            $username = "";
            }
        if(isset($data['pass']))
            {
            $password = $data['pass'];
            }
        else
            {
            $password = "";
            }
        if (!is_scalar($method)) {
                throw new Exception('Method name has no scalar value');
            }
        if (!is_array($params)) {
                throw new Exception('Params must be given as array');
            }
        $id = round(fmod(microtime(true)*1000, 10000));
        $params = array_values($params);
        $strencode = function(&$item, $key) {
            if ( is_string($item) ) $item = utf8_encode($item);
            else if ( is_array($item) ) array_walk_recursive($item, $strencode);
            };
        array_walk_recursive($params, $strencode);
        $request = Array(
                            "jsonrpc" => "2.0",
                            "method" => $method,
                            "params" => $params,
                            "id" => $id
                        );
        $request = json_encode($request);
        $header = "Content-type: application/json"."\r\n";
        if(($username != "") || ($password != "")) {
            $header .= "Authorization: Basic ".base64_encode($username.":".$password)."\r\n";
            }
        $options = Array(
                "http" => array (
                "method"  => 'POST',
                "header"  => $header,
                "content" => $request
                                )
                    );
        $context  = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        echo $UrlAddress."   ".($response?"Ja":"Nein")."\n";
        if ($response === false) return ($response);
        else return ($rpc);
        }

    /***********************************************************************************
    *
    *  selectProtocol, selectProtocolDevice
    *
    * Beide Routinen gehen die HomematicList durch
    * typischer Befehl: foreach ( (selectProtocolDevice("","TYPE_THERMOSTAT",HomematicList())) as $entry) echo "   ".$entry["Name"]."\n";
    *
    *
    **************************************************************************************/

		function selectProtocol($protocol,$devicelist)
			{
			$result=array();
			foreach ($devicelist as $index => $device)
				{
				if ($device["Protocol"]==$protocol) $result[$index]=$device;
				}
			return ($result);
			}	
		
        function selectProtocolDevice($protocol,$type,$devicelist)
			{
			$result=array();
			foreach ($devicelist as $index => $device)
				{
                if ( isset($device["Device"]) === false ) 
                    {
                    echo "FEHLER, Array Identifier Device nicht festgelegt.\n";
                    print_r($device);
                    }
                elseif ( ($device["Protocol"]==$protocol) && ($device["Device"]==$type) ) $result[$index]=$device;
                elseif ( ($protocol=="") && ($device["Device"]==$type) ) $result[$index]=$device;
                elseif ( ($type=="")     && ($device["Protocol"]==$protocol) ) $result[$index]=$device;   
				}
			return ($result);
			}



/******************************************************************
 *
 * Vereinfachter Webfront Aufbau wenn SplitPanes verwendet werden sollen. 
 * Darstellung von Variablen nur in Kategorien kann einfacher gelöst werden. Da reicht der Link.
 *
 *  __construct                 $WebfrontConfigID anlegen mit den IDs der vorhandenen Webfronts
 *
 *  get_Webfronts               alle Visulaisierungsinstanzen auslesen
 *  get_WebfrontConfigID
 *  createLinkinWebfront       fuer Stromheizung
 *  CreateLinkWithDestination
 *
 *  get_WfcStatus               echo der installierten Webfront Koniguratoren, IDs werden in construct angelegt
 *  print_wfc                   public für Ausgabe Ergebnis read_wfc
 *  write_wfc                   rekursive private für Ausgabe Ergebnis read_wfc
 *  search_wfc
 *  read_wfc                    Webfront Konfig auslesen, die max Tiefe für die Sublevels angeben
 *  read_wfcByInstance
 *
 *  read_WebfrontConfig         IPS_GetConfiguration und update internal memory of class
 *  write_WebfrontConfig        IPS_SetConfiguration from internal memory und IPS_ApplyChanges
 *  GetItems                    ersetzt WFC_GetItems wenn configID nicht false
 *      GetItem
 *  update_itemListWebfront
 *  UpdateItems
 *      UpdateItem
 *  AddItem
 *  DeleteItem
 *
 *  ReloadAllWebFronts
 *  GetWFCIdDefault
 *  exists_WFCItem
 *  PrepareWFCItemData
 *  CreateWFCItem               ersetzt CreateWFCItem wenn configID false
 *  CreateWFCItemTabPane        ersetzt CreateWFCItemTabPane
 *  CreateWFCItemSplitPane      ersetzt CreateWFCItemSplitPane
 *  CreateWFCItemCategory       ersetzt CreateWFCItemCategory
 *  CreateWFCItemExternalPage   ersetzt CreateWFCItemExternalPage
 *  CreateWFCItemWidget         ersetzt CreateWFCItemWidget
 *  UpdateConfiguration         ersetzt WFC_UpdateConfiguration wenn configID false
 *  UpdateParentID
 *  UpdatePosition
 *  DeleteWFCItems
 *      DeleteWFCItem
 *
 *  installWebfront             die beiden Webfronts anlegen und das Standard Webfront loeschen, WebfrontConfigID als return
 *  easySetupWebfront           Aufbau des Webfronts, Standardroutine
 *  setupWebfront               Aufruf zur Erzeugung des Webfronts, im Array sind die IDs die verlinkt werden sollen bereits gespeichert
 *  setupWebfrontEntry
 *  createSplitPane
 *  createLinks
 *  deletePane
 *
 * verwendet Spezialfunktionen wie zum Beispiel CreateWFCItemTabPane aus ISPInstaller.inc.php
 * diese verwenden wiederum undokumentierte WFC_ Befehle, die abgekündigt werden könnten, zB ab IPS 6.3 mit der Integration von IPS_StudioView
 *
 *
 ******************************************************************/

class WfcHandling
	{
    
    private $WFC10_ConfigId, $WebfrontConfigID;

    private $installedModules;                                              // Modul abhängige Routinen, Bereiche
    private $categoryIdSwitches, $categoryIdGroups, $categoryIdPrograms;            // wenn Stromheizung installiert
    private $customComponentCategories;                                             // wenn CustomComponents installiert

    private $configWF;                                                      // von easySetupWebfront
    private $configID;                                                      // for Standard Commands
    private $paneConfig;                                                    // interner Parameter zum Beispiel für das Aufsetzen von SplitPanes                        

    private $configWebfront;                                                // interne Configuration eines Webfronts als Array einlesen, dann modifizieren und wieder schreiben
    private $itemListWebfront;                                              // Zuordnung index 0..x und itemID - das ist der Name

    protected $linkTable;                               // ale Links die seit der initialisiserung geschrieben wurden

    /* WfcHandling::__construct
     * legt schon eine Menge Variablen an:
     * die installierten Module
     * wenn Stromheizung oder CustomComponents werden die passenden Kategorien auch gleich angelegt
     * die ID des Default Webfront Konfigurators
     * und als Tabelle alle Webfronts mit dem Namen als ID, kann mit get_WebfrontConfigID abgefragt werden
     *
     * abhängig von configId werden die in dieser class implementierten Standard Interfaces (false) oder die eigenen Interfaces (ID) verwendet, gilt für:
     *      GetItems                return(WFC_GetItems($this->configID))
     *      CreateWFCItem           return(CreateWFCItem ($this->configID, $ItemId, $ParentId, $Position, $Title, $Icon, $ClassName, $Configuration));
     *      CreateWFCItemTabPane    return(CreateWFCItemTabPane ($this->configID, $ItemId, $ParentId, $Position, $Title, $Icon));
     *      CreateWFCItemSplitPane  return(CreateWFCItemSplitPane ($this->configID, $ItemId, $ParentId, $Position, $Title, $Icon, $Alignment, $Ratio, $RatioTarget, $RatioType, $ShowBorder));
     *
     *
     */
	public function __construct($configID=false,$debug=false)
		{
        $this->configID=$configID;                                                    //true means we do immediate change in configuration, false means nbatch modus in mirror config
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $this->installedModules = $moduleManager->GetInstalledModules();
        /*$inst_modules="\nInstallierte Module:\n";
        foreach ($installedModules as $name=>$modules) $inst_modules.="  ".str_pad($name,20)." ".$modules."\n";
        echo $inst_modules."\n";*/
    	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';

        if (isset ($this->installedModules["Stromheizung"])) 
            { 
            if ($debug) echo "Modul Stromheizung ist installiert.\n";
            IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");
            IPSUtils_Include ("IPSHeat_Constants.inc.php",      "IPSLibrary::app::modules::Stromheizung");
            IPSUtils_Include ('StromheizungLib.class.php', 'IPSLibrary::app::modules::Stromheizung');
            IPSUtils_Include ('Stromheizung_Configuration.inc.php', 'IPSLibrary::config::modules::Stromheizung');
    		$moduleManagerSH      = new IPSModuleManager('Stromheizung',$repository);
        	$CategoryIdDataSH     = $moduleManagerSH->GetModuleCategoryID('data');

	        $this->categoryIdSwitches = IPS_GetObjectIDByName('Switches', $CategoryIdDataSH);
	        $this->categoryIdGroups   = IPS_GetObjectIDByName('Groups',   $CategoryIdDataSH);
	        $this->categoryIdPrograms = IPS_GetObjectIDByName('Programs', $CategoryIdDataSH);
            } 
        else 
            { 
            echo "Achtung, class WfcHandling, Modul Stromheizung ist NICHT installiert. Routinen werden uebersprungen.\n"; 
            }
        if (isset ($this->installedModules["CustomComponent"])) 
            { 
            if ($debug) echo "Modul CustomComponent ist installiert.\n";
            $moduleManagerCC      = new IPSModuleManager('CustomComponent',$repository);
            $CategoryIdDataCC     = $moduleManagerCC->GetModuleCategoryID('data');

            $this->customComponentCategories=array();
            $Category=IPS_GetChildrenIDs($CategoryIdDataCC);
            foreach ($Category as $CategoryId)
                {
                //echo "  Category    ID : ".$CategoryId." Name : ".IPS_GetName($CategoryId)."\n";
                $Params = explode("-",IPS_GetName($CategoryId)); 
                if ( (sizeof($Params)>1) && ($Params[1]=="Auswertung") )
                    {
                    $this->customComponentCategories[$Params[0]]=$CategoryId;
                    }
                }
            //print_r($this->customComponentCategories);
            } 
        else 
            { 
            echo "Achtung, class WfcHandling, Modul CustomComponent ist NICHT installiert. Routinen werden uebersprungen.\n"; 
            }

        $this->WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	    //echo "Default WFC10_ConfigId, wenn nicht definiert : ".IPS_GetName($this->WFC10_ConfigId)."  (".$this->WFC10_ConfigId.")\n\n";
    	$this->WebfrontConfigID=array();
        $alleInstanzen = $this->get_Webfronts();                 // alle Visualisiserungen auslesen
        foreach ($alleInstanzen as $index => $instanz)
            {
            $instance=IPS_GetInstance($instanz["OID"]);
            $this->WebfrontConfigID[IPS_GetName($instanz["OID"])]=$instance["InstanceID"];
            //if ($debug) echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz["OID"]),25)." ID : ".$instance["InstanceID"]."  (".$instanz["OID"].")\n";
            }        
	    /*$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
    	foreach ($alleInstanzen as $instanz)
	    	{
		    $result=IPS_GetInstance($instanz);
    		$this->WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
	    	//echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."  (".$instanz.")\n";
    		}
	    //echo "\n"; */       
        }

    /* WfcHandling::get_Webfronts
     * alle Visualisierungen auslesen 
     */
    public function get_Webfronts()
        {
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug        
        return($modulhandling->getInstancesByType(6));                 // alle Visualisiserungen    
        }

    /* WfcHandling::get_WebfrontConfigID
     * Abfrage als Tabelle alle Webfronts mit dem Namen als ID
     * zB Array
     * (
     *  [Administrator] => 36728
     *   [User] => 41606
     * )
     */

    public function get_WebfrontConfigID()
        {
        return($this->WebfrontConfigID);
        }

    /* WfcHandling::createLinkinWebfront
     * nur den Link anlegen, nicht soviel Automatik
     * wird in Stromheizung_Installation verwendet, 
     * link kann einen :: enthalten, zusätzliche Aktion ist die Zuordnung des vorderen Teils zu einem CustomComponent
     *
     *
     */

    public function createLinkinWebfront($link,$name,$categoryId,$order)
        {
        $register=explode('::',$link);
        if ( (count($register)>1) && (isset($this->customComponentCategories[$register[0]])) )
            {
            echo "Zerlege Link wenn :: enthalten in $link.\n";
            print_r($register);
            $groupCat=$this->customComponentCategories[$register[0]];
            $variableID=@IPS_GetObjectIDByName($register[1],$groupCat);
            if ($variableID) 
                {
                echo "Link für ein Register, kein Schalter, gefunden $variableID in $groupCat ".(IPS_GetName($groupCat))."\n";
    			// definition CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="") 
                CreateLinkByDestination($name, $variableID, $categoryId, $order);
                }
            else
                {
                echo "Link zum Register nicht gefunden, noch einmal nachschauen:\n";
                $registers=IPS_GetChildrenIDs($groupCat);
                echo "looking on Group $groupCat for ".$register[1].":\n";
                foreach ($registers as $register)
                    {
                    echo "  Register ID : ".$register." Name : ".IPS_GetName($register)."\n";
                    }
                }
            }
        else
            {
            $variableID = getVariableId($link,[$this->categoryIdSwitches,$this->categoryIdGroups,$this->categoryIdPrograms]);
            if ($variableID) 
                {
                //CreateLinkByDestination($name, $variableID, $categoryId, $order);
                $this->CreateLinkWithDestination($name, $variableID, $categoryId, $order);
                }
            else echo "****Fehler, Variable $link kein Switch, Group oder Program.\n";
            }
        }

    /* WfcHandling::CreateLinkWithDestination
     * besser als CreateLinkByDestination
     * bessert Fehler selbstständig aus, kürzt Links auf Links ab
     *
     */

    public function CreateLinkWithDestination($name, $variableID, $categoryId, $order)
        {
        $createLink=true;
        $object=IPS_GetObject($variableID);
        if ($object)
            {
            $type = $object["ObjectType"];
            echo "               Link mit Namen $name aufbauen von Variable $variableID und Typ $type in $categoryId. Einordnen nach $order.\n";
            switch ($type)         // wenn keine Variable, was einfallen lassen
                {
                case 6:         //Link
                    $createLink=false;          //nicht anzeigen, die einfachste Variante
                    break;
                case 2:
                default:
                    break;
                }
            if ($createLink) CreateLinkByDestination($name, $variableID, $categoryId, $order);
            }
        }


    /* WfcHandling::initiateLinkTable
     * ein json string eines arrays mit allen Links, Namen und ID
     * parentId => TargetID
     */
    public function initiateLinkTable()
        {
        $this->linkTable=array();
        }

	/* WfcHandling::createLinkByDestination
     * Anlegen eines Links
	 *
	 * Die Funktion sucht in der spezifizierten Parent Kategorie alle vorhandenen Links und überprüft ob einer der
	 * Links bereits auf das zu verknüpfende Objekt verweist. Wenn kein Link gefunden wurde wird ein neuer angelegt,
	 * anderenfalls wird Position und Name existierenden Links auf den neuen Wert gesetzt.
	 *
	 * @param string $Name Name des Links im logischen Objektbaum
	 * @param integer $LinkChildId ID des zu verknüpfenden Objekts
	 * @param integer $ParentId ID des übergeordneten Objekts im logischen Objektbaum
	 * @param integer $Position Positionswert des Objekts
	 * @param string $ident Identifikator für das Objekt
	 * @return integer ID der Variable
	 *
	 */
	public function createLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="") 
        {
		$LinkId    = false;
		$ObjectIds = IPS_GetChildrenIDs ($ParentId);
		foreach ($ObjectIds as $ObjectId) 
            {
			$Object = IPS_GetObject ($ObjectId);
			if ($Object['ObjectType']==6 /*Link*/) 
                {
				$Link = IPS_GetLink($ObjectId);
				if ($Link['TargetID']==$LinkChildId) 
                    {
					$LinkId = $ObjectId;
					break;
				    }
			    }
		    }

		if ($LinkId === false) 
            {
			$LinkId = IPS_CreateLink();
			IPS_SetParent($LinkId, $ParentId);
			IPS_SetPosition($LinkId, $Position);
		    }
		IPS_SetLinkTargetID($LinkId, $LinkChildId);
		if ($ident<>"") { IPS_SetIdent($LinkId, $ident); }
		IPS_SetName($LinkId, $Name);
		$this->updateObjectData($LinkId, $Position);
        $this->linkTable[$ParentId][$LinkChildId]=$LinkId;
		return $LinkId;
	    }

	protected function updateObjectData($ObjectId, $Position, $Icon="") 
        {
		$ObjectData = IPS_GetObject ($ObjectId);
		$ObjectPath = IPS_GetLocation($ObjectId);
		if ($ObjectData['ObjectPosition'] <> $Position and $Position!==false) 
            {
			Debug ("Set ObjectPosition='$Position' for Object='$ObjectPath' ");
			IPS_SetPosition($ObjectId, $Position);
		    }
		if ($ObjectData['ObjectIcon'] <> $Icon and $Icon!==false) 
            {
			Debug ("Set ObjectIcon='$Icon' for Object='$ObjectPath' ");
			IPS_SetIcon($ObjectId, $Icon);
		    }
    	}

    /* WfcHandling::getLinkTable
     * deliver the content of the linkTable
     */
    public function getLinkTable()
        {
        return(json_encode($this->linkTable));
        }

    /* WfcHandling::get_WfcStatus
     * echo der installierten Webfront Konfiguratoren, IDs werden in construct angelegt 
     */
    public function get_WfcStatus()
        {
	    echo "Default WFC10_ConfigId, wenn nicht definiert : ".IPS_GetName($this->WFC10_ConfigId)."  (".$this->WFC10_ConfigId.")\n";
        // $this->WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
        foreach ($this->WebfrontConfigID as $name => $entry)    
            {
            echo "     Webfront Konfigurator Name : ".str_pad($name,20)." ID : ".$entry."\n";
            }
        echo "\n";
        }

    /************************************************************************************/

    /* WfcHandling::print_wfc
     * eine WFC Struktur mit einem ident ausgeben 
     */
    public function print_wfc($input)
        {
        $this->write_wfc($input,"",10);    
        }

    /* WfcHandling::write_wfc
     * rekursive Funktion, eine WFC Struktur mit einem ident ausgeben, von print_wfc aufgerufen 
     * durch den rekursiven Aufruf wird ident immer um drei blanks länger
     */
    private function write_wfc($input,$indent,$level)
	    {
    	if (is_array($input) && (sizeof($input) > 0) && ($level>0) )
	    	{
		    foreach ($input as $index => $entry)
			    {
    			if ( $index != "." )
	    			{
		    		echo $indent.$entry["."]."\n";
			    	$this->write_wfc($entry,$indent."   ",($level-1));
				    }
    			}
	    	}	
    	}

    /* WfcHandling::search_wfc
     * rekursive Funktion, in einer WFC Struktur einen Namen suchen 
     * die STruktur wird als array übergeben
     */
    private function search_wfc($input,$search,$tree)
	    {
    	$result="";
	    if (is_array($input) && (sizeof($input) > 0) )
		    {
    		foreach ($input as $index => $entry)
	    		{
		    	if ( $index != "." )
			    	{
				    //echo $tree.".".$index."\n";
    				if ($entry["."] == $search) 
	    				{ 
		    			//echo "search_wfc: ".$search." gefunden in Tree : ".$tree.".\n"; 
			    		return($tree.".");
				    	}
    				else 
	    				{	
		    			$result=$this->search_wfc($entry,$search,$tree.".".$index);
			    		if ( $result != "") { return($result); }
				    	}
    				}
	    		}
		    }
    	else 
	    	{
		    //echo "Search Array Size ".sizeof($input)."\n";
    		}
	    return($result);						
	    }

    /* WfcHandling::read_wfc
     * Die Konfiguration eines Webfronts auslesen, sehr hilfreich
     * Gibt ein Array $resultWebfront als return Wert zurück
     * Webfront Configurator Instanzen ermitteln
     *
     * soll auch für Kachelvisualisierung funktionieren, keine Anzeige aktuell da woanders gespeichert
     *
     *
     *
     *
     *
     */
    public function read_wfc($level=10,$debug=false)
	    {
    	if ($debug) echo "   read_wfc($level aufgerufen:\n";
        $resultWebfront=array();
	    $WebfrontConfigID=array();
        $alleInstanzen = $this->get_Webfronts();                 // alle Visualisiserungen auslesen
        foreach ($alleInstanzen as $index => $instanz)
            {
            $webfront=IPS_GetName($instanz["OID"]);   

            $instance=IPS_GetInstance($instanz["OID"]);
            $WebfrontConfigID[IPS_GetName($instanz["OID"])]=$instance["InstanceID"];
            if ($debug) echo "   Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz["OID"]),25)." ID : ".$instance["InstanceID"]."  (".$instanz["OID"].")\n";
            $resultWebfront[$webfront] = $this->read_wfcByInstance($instanz["OID"],$level,$debug);            // funktioniert noch nicht für die Kacheln
            }        
        return ($resultWebfront);    
    	}   // ende function

    /* WfcHandling::read_wfcByInstance  
     * wenn instanz false dann nimmt er den internen Speicher
     */
    public function read_wfcByInstance($instanz,$level,$debug=false)
        {
        if ($debug) echo "read_wfcByInstance($instanz ...\n";
        $ItemList = $this->GetItems($instanz);          // wenn Instanz false wird die interne ItemList genommen
        if (is_array($ItemList))
            {
                //print_r($ItemList);
		    	$wfc_tree=array(); $root="";
                for ($i=0;$i<5;$i++)        // mehrere Durchläufe
                    {
                    $count=0;
    			    foreach ($ItemList as $entry)
	    			    {
    	    			if ($entry["ParentID"] != "")
	    	    			{
                            /* Liste der Einträge ist flat es gibt immer einen entry und einen parent */
		    		    	//echo "   WFC Eintrag:    ".$entry["ParentID"]." (Parent)  ".$entry["ID"]." (Eintrag)\n";
    			    		$result = $this->search_wfc($wfc_tree,$entry["ParentID"],"");
	    			    	//echo "  search_wfc: ".$entry["ParentID"]." mit Ergebnis \"".$result."\"  ".substr($result,1,strlen($result)-2)."\n";
		    			    if ($result == "")
			    			    {
                                if ( ($root != "") && ($entry["ParentID"]==$root) ) /* parent not found, unclear if root */
                                    {
    					    	    $wfc_tree[$entry["ParentID"]][$entry["ID"]]=array();
	    					        $wfc_tree[$entry["ParentID"]]["."]=$entry["ParentID"];
		    				        $wfc_tree[$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    			    			    if ($debug>1) echo "   Root -> ".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                    $count++;
                                    }
		    		    		}
			    		    else
				    		    {
    				    		$tree=explode(".",substr($result,1,strlen($result)-2));
	    				    	if ($tree) 
		    				    	{
			    			 	    //print_r($tree); 
    				    			if ($tree[0]=="")
	    				    			{
		    				    		$wfc_tree[$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
			    				    	if ($debug>1) echo "   -> ".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";						
                                        $count++;
				    				    }
    				    			else	
	    				    			{
		    				    		//echo "Tiefe : ".sizeof($tree)." \n";
			    				    	switch (sizeof($tree))
				    				    	{
					    				    case 1:
						    				    $wfc_tree[$tree[0]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    							    			if ($debug>1) echo "   -> ".$tree[0].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                                $count++;
	    							    		break;
		    							    case 2:
			    							    $wfc_tree[$tree[0]][$tree[1]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    			    							if ($debug>1) echo "   -> ".$tree[0].".".$tree[1].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                                $count++;
	    			    						break;
		    			    				case 3:
			    			    				$wfc_tree[$tree[0]][$tree[1]][$tree[2]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
				    			    			if ($debug>1) echo "   -> ".$tree[0].".".$tree[1].".".$tree[2].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                                $count++;
					    			    		break;
						    			    case 4:
							    			    $wfc_tree[$tree[0]][$tree[1]][$tree[2]][$tree[3]][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    								    		if ($debug>1) echo "   -> ".$tree[0].".".$tree[1].".".$tree[2].".".$tree[3].".".$entry["ParentID"].".".$entry["ID"]." not found - Create.\n";
                                                $count++;
	    								    	break;
    	    								default:
	    	    								echo "Fehler, groessere Tiefe als programmiert.\n";																		
		    	    						}
			    	    				}								
				    	    		}	
					    	    }						
        					/* Routine sucht nach ParentID Eintrag, schreibt Struktur mit unter der dieser Eintrag gefunden wurde */
	        				/*$found="";
		        			foreach ($wfc_tree as $key => $wfc_entry)
			        			{
				        		$skey=$wfc_entry["."]; 
					        	echo $skey." ".sizeof($wfc_entry)." : ";
						        foreach ($wfc_entry as $index => $result)
							        {
    							    if ($result["."] == $entry["ParentID"]) 
    	    							{ 
	    	    						$found=$result["."]; 
		    	    					$fkey=$skey; 
			    	    				echo "-> ".$fkey."/".$found." found.\n";break;
				    	    			}
					    	    	}
    					    	}
    	    				if ($found != "")
	    	    				{	
		    	    			//print_r($wfc_tree);
			    	    		echo "Create : ".$fkey."/".$entry["ParentID"]."/".$entry["ID"]."\n";
				    	    	$wfc_tree[$fkey][$entry["ParentID"]][$entry["ID"]]=array();
					    	    $wfc_tree[$fkey][$entry["ParentID"]][$entry["ID"]]["."]=$entry["ID"];
    					    	}
	    				    */
    		    			}
	    		    	else        // Root Eintrag, parent ist leer
		    		    	{
                            if ($root=="") 
                                {
			    		        if ($debug) echo "WFC Root Eintrag (nicht mehr als einer pro Configurator):    ".$entry["ID"]." (Eintrag)\n";
                                $root=$entry["ID"];
                                }
                            elseif ($root != $entry["ID"]) echo "******* mehrere Root Eintraege !!\n"; 
                            else {} // alles ok   
    				    	}
	    			    }   // ende foreach, alle Konfiguratoren abgeschlossen
                    //echo "*************".$count."\n";
                    } //ende for 2x

		    	if ($debug)
                    {
                    //echo "\n================ WFC Tree ".$webfront."=====\n";	
			        //print_r($wfc_tree);
    			    $this->write_wfc($wfc_tree,"",$level);	
	    		    //echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		    	    /* alle Instanzen dargestellt */
			        //echo "**     ".IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
    			    //print_r($result);
                    }
	        return($wfc_tree);	
            }
        else return (false);
    	}

    /* WfcHandling::read_WebfrontConfig 
     * ein Abbild der Webfront Konfig schaffen, später modifizieren und dann wieder schreiben
     */
    public function read_WebfrontConfig($instanz)
        {
        $this->configWebfront = json_decode(IPS_GetConfiguration($instanz),true);
        $this->update_itemListWebfront();
        return (true);
        }

    /* WfcHandling::get_WebfrontConfig
     * Webfront Konfig in schreibbarem zustand auslesen
     */
    public function get_WebfrontConfig()
        {
        return(json_encode($this->configWebfront));
        }

    /* WfcHandling::write_WebfrontConfig
     * Webfront Konfig schreiben , mit Apply Changes aber ohne reload Webfront
     */
    public function write_WebfrontConfig($instanz)
        {
        /* double check Configuration 
         */
        IPS_SetConfiguration($instanz,json_encode($this->configWebfront));
        return(IPS_ApplyChanges($instanz)); 
        }

    /* es gibt jede Menge nicht dokumentierte WFC_ funktionen die vom IPS_Installer aber auch direkt hier aufgerufen werden
     * schrittweise alle eliminieren, ab IPS 6.3 wird von Symcon umgestellt
     */

    /* WfcHandling::GetItems
     * die Webfront Config für die Items ausgeben, legacy mode wenn configID beim construct übergeben wird
     * Parameter instanz 
     *     Wert,extern: mit IPS_GetConfiguration($instanz) 
     *     false intern: für eine bestimmte Instanz >configWebfront["Items"] die mit read_WebfrontConfig eingelesen wurde
     */
    public function GetItems($instanz=false,$debug=false)
        {
        if ($this->configID) return(WFC_GetItems($this->configID));                       // interop mode needs instance
        //$ItemList = WFC_GetItems($instanz);
        if ($instanz)        // aus der externen Quelle, direkt aus der Instanz Konfig auslesen
            {
            $config=json_decode(IPS_GetConfiguration($instanz),true);
            //print_r($config);
            if (isset($config["Items"])) $ItemList = json_decode(json_decode(IPS_GetConfiguration($instanz))->Items,true);
            else return (false);
            }
        else            // aus dem internen Abbild auslesen
            {
            if (isset($this->configWebfront["Items"])) 
                {
                $ItemList = json_decode($this->configWebfront["Items"],true);            
                //if ($debug) { echo "GetItems ".$this->configID." $instanz reads configWebfront : \n"; print_R($ItemList); }
                }
            else 
                {
                echo "Error, GetItems($instanz) without Items.\n";
                print_R($this->configWebfront);
                return (false);
                }
            }
        return ($ItemList);
        }

    /* WfcHandling::GetItemsByName
     * Items mit einem Namen oder dem Beginn des selben Namens finden, Ausgabe als array
     * benötigt read_WebfrontConfig(instanz) zum Einlesen der Konfiguration
     */
    public function GetItemsByName($name=false)
        {
        $nameID=array();   
        $result=array();
        if (isset($this->configWebfront["Items"])) 
            {
            $ItemList = json_decode($this->configWebfront["Items"],true);      // aus dem internen Abbild auslesen 
            foreach ($ItemList as $index => $configItem)
                {
                //echo "-------$index "; print_R($configItem);
                $nameID[$configItem["ID"]]=$index;
                if ($configItem["ID"]==$name) return ($configItem);
                if (strpos($configItem['ID'], $name)===0) 
                    {
                    if (isset($result[$configItem["ID"]])) echo "Warning, GetItemsByName, $name found already.\n"; 
                    $result[$configItem["ID"]]=$index; 
                    }
                }            
            if ($name==false) return ($nameID);
            elseif (sizeof($result)>0) return ($result);
            else echo "Warning, GetItemsByName, $name not found.\n";
            }
        else 
            {
            echo "Warning, GetItemsByName, no data in class, use read_WebfrontConfig(instanz).\n";
            return (false);
            }
        }

    /* WfcHandling::GetItemsByParentName
     * Items mit einem Namen oder dem Beginn des selben Namens als Parent finden, Ausgabe als array
     * benötigt read_WebfrontConfig(instanz) zum Einlesen der Konfiguration
     * bricht nicht ab wenn der Name genau gefunden wird, da mehrere Treffer sicher sind
     */
    public function GetItemsByParentName($name=false)
        {
        $nameID=array();   
        $result=array();
        if (isset($this->configWebfront["Items"])) 
            {
            $ItemList = json_decode($this->configWebfront["Items"],true);      // aus dem internen Abbild auslesen 
            foreach ($ItemList as $index => $configItem)
                {
                //echo "-------$index "; print_R($configItem);
                $nameID[$configItem["ID"]]=$index;
                //if ($configItem["ParentID"]==$name) return ($configItem);
                if (strpos($configItem['ParentID'], $name)===0) 
                    {
                    $result[$configItem["ID"]]=$index;
                    //$result[$configItem["ID"]]=$configItem["Configuration"];
                    //$result[$configItem["ID"]]=$configItem;
                    //$result[$configItem["ID"]]=$configItem["ClassName"];
                    }
                }            
            if ($name==false) return ($nameID);
            elseif (sizeof($result)>0) return ($result);
            else echo "Warning, GetItemsByParentName, $name not found.\n";
            }
        else 
            {
            echo "Warning, GetItemsByParentName, no data in class, use read_WebfrontConfig(instanz).\n";
            return (false);
            }
        }

    /* WfcHandling::GetItem
     * die Webfront Config für ein Item oder wenn false für alle Items ausgeben, gleich wie exists_WFCItem
     * die ItemId ist ein Index und kein Name, daher alle Einträge durchgehen und mit ID vergleichen
     * Item ID muss vollstaendig überein stimmen
     *
     */
    public function GetItem($ItemId=false,$instanz=false)  
        {
        $configItems=$this->GetItems($instanz);
        $nameID=array();
        if ($ItemId===false) return($configItems);
        else
            {
            foreach ($configItems as $index => $configItem)
                {
                //echo "-------$index "; print_R($configItem);
                $nameID[$configItem["ID"]]=$index;
                if ($configItem["ID"]==$ItemId) return ($configItem);
                }
            }
        return($nameID);
        } 

    /* WfcHandling::update_itemListWebfront
     * Zuordnung Index und Name in itemID herstellen und in der class speichern
     */
    private function update_itemListWebfront()
        {
        $configItems = json_decode($this->configWebfront["Items"],true); 
        foreach ($configItems as $index => $configItem)
            {
            //echo "-------$index "; print_R($configItem);
            $nameID[$configItem["ID"]]=$index;
            }           
        $this->itemListWebfront=$nameID;
        }

    /* WfcHandling::UpdateItems
     * die interne Webfront Config mit der neuen Items Config überschreiben
     */
    public function UpdateItems($configItems)
        {
        $this->configWebfront["Items"] = json_encode($configItems);
        $this->update_itemListWebfront();                                           // wenn configWebfront geändert wird auch den Bezugspunkt ändern
        return (true);
        }

    /* WfcHandling::UpdateItem
     * die interne Webfront Config mit der Config für ein Item überschreiben
     */
    public function UpdateItem($ItemId,$configItem)
        {
        if (isset($this->itemListWebfront[$ItemId])) 
            {
            $index=$this->itemListWebfront[$ItemId];
            $configItems = json_decode($this->configWebfront["Items"],true);
            echo "   UpdateItem, found $ItemId. Index : $index \n";
            print_R($configItems[$index]);
            echo "--------------------\n";
            $configItems[$index]=$configItem;       // den einen Index austauschen
            $this->UpdateItems($configItems);           // alles wieder schreiben
            return (true);
            }
        else return (false);
        }        

    /* WfcHandling::AddItem
     * benutzt UpdateItems(ConfigItems), hängt vorher die neue Konfiguration zu configWebfront["Items"]
     *
     * Array
        (
        [ParentID] => roottp
        [Visible] => 1
        [Configuration] => {"title":"","name":"AutoTPU","icon":"Car"}
        [Position] => 500
        [ID] => AutoTPU
        [ClassName] => TabPane
        )
     */
    public function AddItem($ItemId, $ClassName, $Configuration, $ParentId)
        {
        $configItem=array();
        $configItem["ParentID"]=$ParentId;
        $configItem["Visible"]=true;                            // boolean
        $configItem["Configuration"]=$Configuration;
        $configItem["Position"]=(int)0;                         // integer
        $configItem["ID"]=$ItemId;
        $configItem["ClassName"]=$ClassName;
        if (isset($this->itemListWebfront[$ItemId])) return($this->UpdateItem($ItemId,$configItem));
        else
            {
            $configItems = json_decode($this->configWebfront["Items"],true);
            $configItems[] = $configItem;
            $this->UpdateItems($configItems);           // alles wieder schreiben
            return (true);            
            }
        return (false);
        }

    /* WfcHandling::DeleteItem
     */
    public function DeleteItem($ItemId)
        {
        if (isset($this->itemListWebfront[$ItemId])) 
            {
            $configItems = json_decode($this->configWebfront["Items"],true);
            $index = $this->itemListWebfront[$ItemId];
            echo "DeleteItem $index \n";
            unset($configItems[$index]);
            $configItemsNew=array();
            foreach ($configItems as $configItem) $configItemsNew[] = $configItem;
            $this->UpdateItems($configItemsNew);           // alles wieder schreiben
            $this->update_itemListWebfront();
            return (true);            
            }
        return (false);
        }

	/* WfcHandling::ReloadAllWebFronts
	 * Lädt alle WebFronts neu
	 */
	function ReloadAllWebFronts() {
		$wfIds = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
		foreach ($wfIds as $wfId) {
		    WFC_Reload($wfId);
		}
	}

    /* WfcHandling::GetWFCIdDefault
     * Liefert die ID des ersten gefundenen WebFront Konfigurators
	 *
	 * Die Funktion gibt die ID des ersten WebFront Konfigurators zurück. Wenn keiner existiert, wird 'false' zurückgegeben.
	 *
	 */
	function GetWFCIdDefault() {
	    $wfIds = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
		foreach ($wfIds as $wfId) {
		    return $wfId;
		}
		return false;
	}

	/* WfcHandling::exists_WFCItem
     * Existenz eines WebFront Konfigurator Items überprüfen
	 *
	 * Der Befehl überprüft ob ein bestimmtes Item im WebFront Konfigurator existiert
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Element Name im Konfigurator Objekt Baum
	 * @return boolean TRUE wenn das Item existiert anderenfalls FALSE
	 *
	 */
	function exists_WFCItem($ItemId, $WFCId=false, $debug=false) 
        {
        $ItemList = $this->GetItems($WFCId,$debug);            // wenn WFCId false dann internen Speicher nehmen
        if ($ItemList !== false)
            {
            foreach ($ItemList as $Item) 
                {
                if ($Item['ID']==$ItemId) return true;
                }
            foreach ($ItemList as $Item)
                {
                if ($debug) echo $Item['ID']."\n";
                }
            }
        elseif ($debug) echo "Warning, exists_WFCItem, itemList from GetItems($WFCId) is false.\n";
	    return false;
	}    
    
    /* WfcHandling::PrepareWFCItemData
     * spezielle Formattierung berücksichtigen
     * für ItemId, ParentId, Title 
     * Blank mit underscore tauschen, nur für Item und Parent
     * bei IP Symcon Version 1 und 2 auch noch utf encoden
     */
	function PrepareWFCItemData (&$ItemId, &$ParentId, &$Title) {
		$ItemId   = str_replace(' ','_',$ItemId);
		$ParentId = str_replace(' ','_',$ParentId);
		//$ItemId   = str_replace('_','',$ItemId);
		//$ParentId = str_replace('_','',$ParentId);
		$version = IPS_GetKernelVersion();
		$versionArray = explode('.', $version);
		if ($versionArray[0] < 3) {                         // für sehr alte IP Symcon Versionen
			$Title    = utf8_encode($Title);
			$ItemId   = utf8_encode($ItemId);
			$ParentId = utf8_encode($ParentId);
		}
	}

    /* WfcHandling::CreateWFCItem
     * wichtigste Funktion, verwendet allerdings jede Menge proprietären Quatsch
     * wenn nicht vorhanden mit AddItem beginnen, dann UpdateConfiguration
     */
	function CreateWFCItem ($ItemId, $ParentId, $Position, $Title, $Icon, $ClassName, $Configuration) 
        {
        if ($this->configID) return(CreateWFCItem ($this->configID, $ItemId, $ParentId, $Position, $Title, $Icon, $ClassName, $Configuration));                       // interop mode needs instance
	    if (!$this->exists_WFCItem($ItemId)) {
		    Debug ("Add WFCItem='$ItemId', Class=$ClassName, Config=$Configuration");
		    $this->AddItem($ItemId, $ClassName, $Configuration, $ParentId);
		}
		$this->UpdateConfiguration($ItemId, $Configuration);
		$this->UpdateParentID($ItemId, $ParentId);
		$this->UpdatePosition($ItemId, $Position);
        }

	/* WfcHandling::CreateWFCItemTabPane
     * Anlegen eines TabPanes im WebFront Konfigurator
	 *
	 * Der Befehl legt im WebFront Konfigurator ein TabPane mit dem Element Namen $ItemId an
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Element Name im Konfigurator Objekt Baum
	 * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
	 * @param integer $Position Positionswert im Objekt Baum
	 * @param string $Title Title
	 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
	 *
	 */
	function CreateWFCItemTabPane ($ItemId, $ParentId, $Position, $Title, $Icon) 
        {
        if ($this->configID) return(CreateWFCItemTabPane ($this->configID, $ItemId, $ParentId, $Position, $Title, $Icon));                       // interop mode needs instance
        echo "CreateWFCItemTabPane $ItemId in $ParentId on Position $Position with Title $Title and Icon $Icon:\n";
		$this->PrepareWFCItemData ($ItemId, $ParentId, $Title);
		$Configuration = "{\"title\":\"$Title\",\"name\":\"$ItemId\",\"icon\":\"$Icon\"}";
		$this->CreateWFCItem ($ItemId, $ParentId, $Position, $Title, $Icon, 'TabPane', $Configuration);
	    }

	/* WfcHandling::CreateWFCItemSplitPane
     * Anlegen eines SplitPanes im WebFront Konfigurator
	 *
	 * Der Befehl legt im WebFront Konfigurator ein SplitPane mit dem Element Namen $ItemId an
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Element Name im Konfigurator Objekt Baum
	 * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
	 * @param integer $Position Positionswert im Objekt Baum
	 * @param string $Title Title
	 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
	 * @param integer $Alignment Aufteilung der Container (0=horizontal, 1=vertical)
	 * @param integer $Ratio Größe der Container
	 * @param integer $RatioTarget Zuordnung der Größenangabe (0=erster Container, 1=zweiter Container)
	 * @param integer $RatioType Einheit der Größenangabe (0=Percentage, 1=Pixel)
	 * @param string $ShowBorder Zeige Begrenzungs Linie
	 *
	 */
	function CreateWFCItemSplitPane ($ItemId, $ParentId, $Position, $Title, $Icon="", $Alignment=0 /*0=horizontal, 1=vertical*/, $Ratio=50, $RatioTarget=0 /*0 or 1*/, $RatioType /*0=Percentage, 1=Pixel*/, $ShowBorder='true' /*'true' or 'false'*/) 
        {
        if ($this->configID) return(CreateWFCItemSplitPane ($this->configID, $ItemId, $ParentId, $Position, $Title, $Icon, $Alignment, $Ratio, $RatioTarget, $RatioType, $ShowBorder));
        echo "CreateWFCItemSplitPane $ItemId in $ParentId:\n";
		$this->PrepareWFCItemData ($ItemId, $ParentId, $Title);
		$Configuration = "{\"title\":\"$Title\",\"name\":\"$ItemId\",\"icon\":\"$Icon\",\"alignmentType\":$Alignment,\"ratio\":$Ratio,\"ratioTarget\":$RatioTarget,\"ratioType\":$RatioType,\"showBorder\":$ShowBorder}";
		$this->CreateWFCItem ($ItemId, $ParentId, $Position, $Title, $Icon, 'SplitPane', $Configuration);
	    }

	/* WfcHandling::CreateWFCItemCategory
     * Anlegen einer Kategorie im WebFront Konfigurator
	 *
	 * Der Befehl legt im WebFront Konfigurator eine Kategorie mit dem Element Namen $ItemId an
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Element Name im Konfigurator Objekt Baum
	 * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
	 * @param integer $Position Positionswert im Objekt Baum
	 * @param string $Title Title
	 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
	 * @param integer $BaseId Kategorie ID im logischen Objektbaum
	 * @param string $BarBottomVisible Sichtbarkeit der Navigations Leiste
	 * @param integer $BarColums
	 * @param integer $BarSteps
	 * @param integer $PercentageSlider
	 *
	 */
	function CreateWFCItemCategory ($ItemId, $ParentId, $Position, $Title, $Icon="", $BaseId /*ID of Category*/, $BarBottomVisible='true' /*'true' or 'false'*/, $BarColums=9, $BarSteps=5, $PercentageSlider='true' /*'true' or 'false'*/ ) 
        {
        if ($this->configID) return(CreateWFCItemCategory ($this->configID, $ItemId, $ParentId, $Position, $Title, $Icon, $BaseId, $BarBottomVisible, $BarColums, $BarSteps, $PercentageSlider));            
        echo "CreateWFCItemCategory $ItemId in $ParentId:\n";
		$this->PrepareWFCItemData ($ItemId, $ParentId, $Title);
		$Configuration = "{\"title\":\"$Title\",\"name\":\"$ItemId\",\"icon\":\"$Icon\",\"baseID\":$BaseId,\"enumBarColumns\":$BarColums,\"selectorBarSteps\":$BarSteps,\"isBarBottomVisible\":$BarBottomVisible,\"enablePercentageSlider\":$PercentageSlider}";
		$this->CreateWFCItem ($ItemId, $ParentId, $Position, $Title, $Icon, 'Category', $Configuration);
    	}

	/* WfcHandling::CreateWFCItemExternalPage
     * Anlegen einer ExternalPage im WebFront Konfigurator
	 *
	 * Der Befehl legt im WebFront Konfigurator eine ExternalPage mit dem Element Namen $ItemId an
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Element Name im Konfigurator Objekt Baum
	 * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
	 * @param integer $Position Positionswert im Objekt Baum
	 * @param string $Title Title
	 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
	 * @param string $PageUri URL der externen Seite
	 * @param string $BarBottomVisible Sichtbarkeit der Navigations Leiste
	 *
	 */
	function CreateWFCItemExternalPage ($ItemId, $ParentId, $Position, $Title, $Icon="", $PageUri, $BarBottomVisible='true' /*'true' or 'false'*/) 
        {
        if ($this->configID) return(CreateWFCItemExternalPage ($this->configID, $ItemId, $ParentId, $Position, $Title, $Icon, $PageUri, $BarBottomVisible)); 
        echo "CreateWFCItemExternalPage $ItemId in $ParentId:\n";
		$this->PrepareWFCItemData ($ItemId, $ParentId, $Title);
		$Configuration = "{\"title\":\"$Title\",\"name\":\"$ItemId\",\"icon\":\"$Icon\",\"pageUri\":\"$PageUri\",\"isBarBottomVisible\":$BarBottomVisible}";
		$this->CreateWFCItem ($ItemId, $ParentId, $Position, $Title, $Icon, 'ExternalPage', $Configuration);
	    }


	/* WfcHandling::CreateWFCItemWidget 
     * Anlegen eines Widget im WebFront Konfigurator
	 *
	 * Der Befehl legt im WebFront Konfigurator ein Widget mit dem Element Namen $ItemId an
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Element Name im Konfigurator Objekt Baum
	 * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
	 * @param integer $Position Positionswert im Objekt Baum
	 * @param string $variableId VariableId, die zur Anzeige im Widget verwendet werden soll
	 * @param string $scriptId ScriptId, Script das ausgeführt werden soll
	 *
	 */
	function CreateWFCItemWidget ($ItemId, $ParentId, $Position, $variableId, $scriptId) {
        if ($this->configID) return(CreateWFCItemWidget($this->configID, $ItemId, $ParentId, $Position, $variableId, $scriptId));
		$this->PrepareWFCItemData ($ItemId, $ParentId, $Title);
        $Configuration = '{"variableID":'.$variableId.',"scriptID":'.$scriptId.',"name":"'.$ItemId.'"}';
		$this->CreateWFCItem ($ItemId, $ParentId, $Position, '', '', 'InfoWidget', $Configuration);
	}

    /* WfcHandling::UpdateConfiguration
     * 
     * WFC_UpdateVisibility             $configItem["Visible"]
     * WFC_UpdatePosition               $configItem["Position"]
     * WFC_UpdateParentID               $configItem["ParentID"]    
     * WFC_UpdateConfiguration          $configItem["Configuration"]
     *        
     */
    public function UpdateConfiguration($ItemId, $Configuration)
        {
        if ($this->configID) return(WFC_UpdateConfiguration($this->configID, $ItemId, $Configuration));
        if (isset($this->itemListWebfront[$ItemId])) 
            {
            $index=$this->itemListWebfront[$ItemId];
            $configItems = json_decode($this->configWebfront["Items"],true);
            echo "   UpdateConfiguration, found $ItemId. Index : $index , new Configuration $Configuration ";
            if ($configItems[$index]["Configuration"] !== $Configuration)
                {            
                print_R($configItems[$index]);
                echo "--------------------\n";
                $configItem = $configItems[$index];
                $configItem["Configuration"]=$Configuration;
                $configItems[$index]=$configItem;       // den einen Index austauschen
                $this->UpdateItems($configItems);           // alles wieder schreiben                
                }
            else echo "unchanged.\n";                  
            return (true);
            }
        else return (false);
        }

    /* WfcHandling::UpdateParentID
     * liest itemListWebfront
     */
    public function UpdateParentID($ItemId, $ParentId)
        {
        if ($this->configID) return(WFC_UpdateParentID($this->configID, $ItemId, $ParentId));
        if (isset($this->itemListWebfront[$ItemId])) 
            {
            $index=$this->itemListWebfront[$ItemId];
            $configItems = json_decode($this->configWebfront["Items"],true);
            echo "   UpdateParentID, found $ItemId. Index : $index , new ParentId $ParentId ";
            if ($configItems[$index]["ParentID"] !== $ParentId)
                {
                print_R($configItems[$index]);
                echo "--------------------\n";
                $configItem = $configItems[$index];            
                $configItem["ParentID"]=$ParentId;
                $configItems[$index]=$configItem;       // den einen Index austauschen
                $this->UpdateItems($configItems);           // alles wieder schreiben
                }
            else echo "unchanged.\n";                
            return (true);
            }
        else 
            {
            echo "Warning, UpdateParentID, $ItemId not found in Webfront Config itemListWebfront.\n";
            //print_R($this->itemListWebfront);
            return (false);
            }
        }

    /* WfcHandling::UpdatePosition
     * Update Position schwierig wenn das Element egerade erzeugt wurde, da die Item ID noch nicht in der internen Config ist 
     */
    public function UpdatePosition($ItemId, $Position)
        {
        if ($this->configID) return(WFC_UpdatePosition($this->configID, $ItemId, $Position));            
        if (isset($this->itemListWebfront[$ItemId])) 
            {
            $index=$this->itemListWebfront[$ItemId];
            $configItems = json_decode($this->configWebfront["Items"],true);
            echo "   UpdatePosition, found $ItemId. Index : $index , new Position $Position ";
            if ($configItems[$index]["Position"] !== (int)$Position)
                {
                $configItem = $configItems[$index];  
                //print_R($configItem);
                var_dump($configItem);   
                echo "--------------------\n";                       
                $configItem["Position"]=(int)$Position;
                $configItems[$index]=$configItem;       // den einen Index austauschen
                $this->UpdateItems($configItems);           // alles wieder schreiben
                }
            else echo "unchanged.\n";
            //var_dump($configItems[$index]); 
            return (true);
            }
        else 
            {
            echo "   UpdatePosition, not found $ItemId .\n";
            return (false);
            }
        }

    /* WfcHandling::UpdateVisibility
     *
     */
    public function UpdateVisibility($ItemId, $Visibility)
        {
        if ($this->configID) return(WFC_UpdateVisibility($this->configID, $ItemId, $Visibility));
        if (isset($this->itemListWebfront[$ItemId])) 
            {
            $index=$this->itemListWebfront[$ItemId];
            $configItems = json_decode($this->configWebfront["Items"],true);
            echo "   UpdateVisibility, found $ItemId. Index : $index , new Visibility $Visibility ";
            if ( (isset($configItems[$index]["Visibility"])) && ($configItems[$index]["Visibility"] !== (bool)$Visibility) )
                {
                print_R($configItems[$index]);
                echo "--------------------\n";
                $configItem = $configItems[$index];            
                $configItem["Visibility"]=(bool)$Visibility;
                $configItems[$index]=$configItem;       // den einen Index austauschen
                $this->UpdateItems($configItems);           // alles wieder schreiben
                }
            else echo "unchanged.\n";
            return (true);
            }
        else 
            {
            echo "   UpdateVisibility, not found $ItemId.\n";
            return (false);
            }
        }
        
	/* WfcHandling::DeleteWFCItems
     * Löschen eines kompletten Objektbaumes aus dem WebFront Konfigurator
	 *
	 * Der Befehl löscht im WebFront Konfigurator einen Teilbaum durch Angabe des Root Element Namens $ItemId
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Root Element Name im Konfigurator Objekt Baum
	 *
	 */
	function DeleteWFCItems($ItemId) {
		$ItemList = $this->GetItems();
		foreach ($ItemList as $Item) {
			if (strpos($Item['ID'], $ItemId)===0) {
				$this->DeleteWFCItem($Item['ID']);
			}
		}
	}

	/* WfcHandling::DeleteWFCItem
    * Löschen ein Element aus dem WebFront Konfigurator
	 *
	 * Der Befehl löscht im WebFront Konfigurator ein Element durch Angabe des Element Namens $ItemId
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Element Name im Konfigurator Objekt Baum
	 *
	 */
	function DeleteWFCItem($ItemId) {
		Debug ("Delete WFC Item='$ItemId'");
        if ($this->configID) return(DeleteWFCItem($this->configID, $ItemId));
		$this->DeleteItem($ItemId);
	}

    /* WfcHandling::installWebfront
     * wird vom install von CustomComponent, EvaluateHardware,Stromheizung und WebLinks aufgerufen 
     * EvaluateHardware erstellt auch die Kategorien Topology und World 
     *
     * die beiden Webfronts anlegen und das Standard Webfront loeschen 
     * $WebfrontConfigID als return
     *
     * Zusätzlich ein Kachelwebfront anlegen und die config auslesen
     *
     */

    public function installWebfront($debug=false)
        {

        if ($debug) echo "installWebfront, Webfront GUID herausfinden:\n";
        //$wfcTree=$this->read_wfc(10,$debug);
        //print_r($wfcTree);	
        if ($debug) echo "-----------------------------\n";
        $WebfrontConfigID=array();
        $alleInstanzen = $this->get_Webfronts();                 // alle Visualisiserungen auslesen
        foreach ($alleInstanzen as $index => $instanz)
            {
            $instance=IPS_GetInstance($instanz["OID"]);
            $WebfrontConfigID[IPS_GetName($instanz["OID"])]=$instance["InstanceID"];
            echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz["OID"]),25)." ID : ".$instance["InstanceID"]."  (".$instanz["OID"].")\n";
            $config=json_decode(IPS_GetConfiguration($instanz["OID"]));

        /* $alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
        foreach ($alleInstanzen as $instanz)
            {
            $result=IPS_GetInstance($instanz);
            $WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
            echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
            $config=json_decode(IPS_GetConfiguration($instanz)); 
            if ($debug) 
                {
                print_r($config);
                $configItems = json_decode(json_decode(IPS_GetConfiguration($instanz))->Items);
                print_r($configItems);
                }           */

            if ($debug) 
                {
                print_r($config);
                if (isset($config->Items))          // funktioniert auch für Kachelvisualisiserung
                    {
                    $configItems = json_decode(json_decode(IPS_GetConfiguration($instanz["OID"]))->Items);
                    print_r($configItems);
                    }
                }            
            echo "  Remote Access Webfront Password set : (".$config->Password.")\n";
            if (isset($config->MobileID)) echo "  Mobile Webfront aktiviert : ".$config->MobileID."\n";		
            if (isset($config->RetroID))  echo "  Retro Webfront aktiviert : ".$config->RetroID."\n";                
            }
        //print_r($WebfrontConfigID);
        
        /* webfront Configuratoren anlegen, wenn noch nicht vorhanden */
        if ( isset($WebfrontConfigID["Administrator"]) == false )
            {
            echo "\nWebfront Configurator \"Administrator\"  erstellen !\n";
            $AdministratorID = IPS_CreateInstance("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}"); // Administrator Webfront Configurator anlegen
            IPS_SetName($AdministratorID, "Administrator");
            $config = IPS_GetConfiguration($AdministratorID);
            echo " Konfig: ".$config."\n";
            IPS_SetConfiguration($AdministratorID,'{"MobileID":-1}');
            IPS_ApplyChanges($AdministratorID);	
            $WebfrontConfigID["Administrator"]=$AdministratorID;
            echo "Webfront Configurator \"Administrator\" aktiviert : ".$AdministratorID." \n";
            }
        else
            {
            $AdministratorID = $WebfrontConfigID["Administrator"];
            echo "Webfront Configurator \"Administrator\" bereits vorhanden : ".$AdministratorID." \n";
            /* kein Mobile Access für Administratoren */
            IPS_SetConfiguration($AdministratorID,'{"MobileID":-1}');
            IPS_ApplyChanges($AdministratorID);			
            }		
        if ( isset($WebfrontConfigID["User"]) == false )
            //$UserID = @IPS_GetInstanceIDByName("User", 0);
            //if(!IPS_InstanceExists($UserID))
            {
            echo "\nWebfront Configurator \"User\"  erstellen !\n";
            $UserID = IPS_CreateInstance("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}"); // Administrator Webfront Configurator anlegen
            IPS_SetName($UserID, "User");
            $config = IPS_GetConfiguration($UserID);
            echo "Konfig : ".$config."\n";
            $categoryId_Mobile         = CreateCategoryPath("Visualization.Mobile");		
            IPS_SetConfiguration($UserID,'{"MobileID":'.$categoryId_Mobile.'}');
            IPS_ApplyChanges($UserID);
            $WebfrontConfigID["User"]=$UserID;
            echo "Webfront Configurator \"User\" aktiviert : ".$UserID." \n";
            }
        else
            {
            $UserID = $WebfrontConfigID["User"];
            echo "Webfront Configurator \"User\" bereits vorhanden : ".$UserID." \n";
            $categoryId_Mobile         = CreateCategoryPath("Visualization.Mobile");		
            //$config = IPS_GetConfiguration($UserID);
            //echo "Konfig : ".$config."\n";
            IPS_SetConfiguration($UserID,'{"MobileID":'.$categoryId_Mobile.'}');
            IPS_ApplyChanges($UserID);			
            }	
		$version = IPS_GetKernelVersion();
		$versionArray = explode('.', $version);
		if ($versionArray[0] < 7) 
            {                         // für sehr alte IP Symcon Versionen
            echo "IPS Version unterstützt keine Kachel Visualisierung.\n";
		    }
        else    
            {            
            $topID=@IPS_GetCategoryIDByName("Topology", 0 );
            if ($topID === false) 	$topID = CreateCategory("Topology",0,20);       // Kategorie anlegen wenn noch nicht da
            $worldID=@IPS_GetCategoryIDByName("World", $topID );
            if ($worldID === false) 	$worldID = CreateCategory("World",$topID,20);       // Kategorie anlegen wenn noch nicht da

            if ( isset($WebfrontConfigID["Kachel Visualisierung"]) == false )
                {
                echo "\nWebfront Configurator \"Kachel Visualisierung\"  erstellen !\n";
                $KachelID = IPS_CreateInstance("{B5B875BB-9B76-45FD-4E67-2607E45B3AC4}"); // Kachel Visualisiserung Configurator anlegen
                IPS_SetName($KachelID, "Kachel Visualisierung");

                $WebfrontConfigID["Kachel Visualisierung"]=$KachelID;
                echo "Webfront Configurator \"Kachel Visualisierung\" aktiviert : ".$KachelID." \n";
                }
            else
                {
                $KachelID = $WebfrontConfigID["Kachel Visualisierung"];
                echo "Webfront Configurator \"Kachel Visualisierung\" bereits vorhanden : ".$KachelID." \n";
                }
            $config = IPS_GetConfiguration($KachelID);
            print_R($config);
            IPS_SetConfiguration($KachelID,'{"BaseID":'.$worldID.'}');
            IPS_ApplyChanges($KachelID);			
            }
        echo "\n";

        /* check nach weiteren Webfront Konfiguratoren */

        echo "Security and Configuration check.\n";
        foreach ($WebfrontConfigID as $Key=>$Item)
            {
            $config=json_decode(IPS_GetConfiguration($Item));
            switch ($Key)
                {
                case "User":	
                    if ($config->MobileID < 0) 
                        {
                        echo "  ".$Key.": Mobile Access for User not set (".$config->MobileID.").   --> setzen\n";
                        }
                case "Administrator":
                case "Kachel Visualisierung":
                    if ($config->Password == "") 
                        {
                        echo "  ".$Key.": Remote Access Webfront Password not set.   --> setzen\n";
                        }
                    else	
                        {
                        echo "  OK ".$Key.": Remote Access Webfront Password set : (".$config->Password.")\n";
                        }					
                    break;
                default:
                    echo "    $Key : Zusaetzlichen Webfront Configurator gefunden.  --> loeschen\n";
                    print_r($config);
                }
            }	

        return ($WebfrontConfigID);  
        }

    /* WfcHandling::anzahlItems
     *
     */
    private function anzahlItems($webfront_links)
        {
        $result = new stdClass();
        $result->count=0;
        $result->firstKey=false;
        $result->tabs=array();
        foreach ($webfront_links as $key => $item)
            {
            switch (strtoupper($key))
                {
                case "CONFIG":
                case "STYLE":
                case "ORDER":
                    break;
                default:
                    if ($result->firstKey) $result->firstKey=$key;
                    $result->tabs[$key]=$item;
                    $result->count++;
                    break;
                }    
            }
        return ($result);        
        }

    /* WfcHandling::easySetupWebfront
     *
     *
     * Beispiel alternative Struktur ohne Nachrichtenspeicher
     *   Konnex zu unten ist Tab AmazonEcho, SubTab Auswertung oder Nachrichten, Gruppe ? 
     *
        Tab Energiemessung
            Subtab:    Summe
                Gruppe:  Wohnung-LBG70
                    Register:  46646/Wirkenergie
                    Register:  35207/Wirkleistung
            Subtab:    Homematic
                Gruppe:  Arbeitszimmer
                    Register:  27977/Wirkenergie
                    Register:  29750/Wirkleistung
            Subtab:    Zusammenfassung
                Gruppe:  Energievorschub der letzten Tage
                    Register:  13234/Zaehlervariablen

     */

    /******
     * Verwendung in Amis, Autosteuerung, CustomComponent, Guthabensteuerung
     *
     * instanz from scope, liest und schreibt automatisch
     *
     * Aufbau einer Webfront Seite, es wird immer mitgegeben ob es sich um einen Administrator, User etc, handelt, es wird der richtigte Teil des WebfrontConfigID übergeben 
     * ruft setupWebfrontEntry mit der richtigen Webfront ConfigID und dem Namen des Webfronts (Administrator/User)
     * wird mittlerweile in Sprachsteuerung_Installation und customcomponent_installation verwendet
     *
     * Parametrierung ist in $webfront_links
     * wenn es nur einen ersten Key gibt, dann
     * Keys in Auswertung und wenn gewünscht Nachrichten strukturiert
     *
	$webfront_links=array(
		"AmazonEcho" => array(
			"Auswertung" => array(
				$ButtonID => array(
						"NAME"				=> "Test",
						"ORDER"				=> 20,
						"ADMINISTRATOR" 	=> true,
						"USER"				=> false,
						"MOBILE"			=> false,
							),	   
    					),
			"Nachrichten" => array(
				$Nachricht_inputID => array(
						"NAME"				=> "Nachrichten",
						"ORDER"				=> 10,
						"ADMINISTRATOR" 	=> true,
						"USER"				=> false,
						"MOBILE"			=> false,
							),
						),					
					),	
				);      
     *
     * Dieser Teil der function übernimmt die Fehlerabfragen und ermittelt anhand der Struktur des Arrays ob 
     * TabPaneItem oder TabPaneParent übergben wird 
     * in der obigen Konfiguration wird TabPaneParent mit AmazonEcho an setupWebfront und dann gleich an setupWebfrontEntry übergeben
     * wenn auch Nachrichten angelegt wird gibt es einen Splitscreen
     *
     * erster Parameter ist configWF, die WebfrontConfig wird auch als class config gespeichert
     *      TabPaneParent       wenn nur ein key übergeben wird erfolgt die Installation im Parent, allerdings nicht in roottp
     *      Enabled             darf nicht false sein
     *      Path                muss vorhandens ein
     */

    public function easySetupWebfront($configWF,$webfront_links, $config, $debug=false)
        {
        $active=true;           // false for debugging purposes, true to execute
        if (is_array($config))
            {
            if (isset($config["Scope"])) $scope=$config["Scope"];
            else $scope="Administrator";
            if (isset($config["EmptyCategory"])) $empty=$config["EmptyCategory"];
            else $empty=true;    
            if (isset($config["Active"])) $active=$config["Active"];
            else $active=true;    
            }
        else 
            {
            $scope=$config;
            $empty=true;
            }
        $WebfrontConfigID = $this->get_WebfrontConfigID();   
        if (isset($WebfrontConfigID[$scope])) $this->read_WebfrontConfig($WebfrontConfigID[$scope]);           // instanz from scope, liest und schreibt automatisch
        else return(false);
        $status=false;
        $ipsOps = new ipsOps();
        $this->configWF=$configWF;                                              /* mitnehmen in die anderen Routinen */
        $info   = $this->anzahlItems($webfront_links);
        if ($debug)                             // check, analyze Config
            {
            echo "easySetupWebfront für Scope \"$scope\" aufgerufen.\n";
            if ($info->count==1) echo "Installation im ".$this->configWF["TabPaneParent"].", nur ein Key ".$info->firstKey.":\n";
            else 
                {
                echo "Installation im \"".$this->configWF["TabPaneItem"]."|".$this->configWF["TabPaneParent"]."\" mit Tabs : ";
                foreach ($info->tabs as $key => $entry)  { if ( ($key !== "CONFIG") && ($key !== "@CONFIG") ) echo "$key  "; }
                echo "\n";                    
                }
            if (isset($configWF["TabPaneName"])) echo "  Tab ".$configWF["TabPaneName"]."(".$configWF["TabPaneItem"].")\n";             // nur ausgeben wenn wirklich definiert wurde
            //echo json_encode($webfront_links)."\n";
            $default=false;
            foreach ($webfront_links as $Name => $webfront_group)
                {
                switch ($Name)
                    {
                    case "ORDER":
                    case "STYLE":
                    case "@CONFIG":
                    case "CONFIG":
                        echo "      Configuration ".json_encode($webfront_group)."\n";    
                        break;
                    case "Auswertung":
                    case "Nachrichten":
                        $default=true;
                    default:
                        echo "    Subtab:    ".$Name."\n";
                        //echo json_encode($webfront_group)."\n";
                        foreach ($webfront_group as $Group => $RegisterEntries)
                            {
                            //echo "      Switch $Group \n";
                            switch ($Group)
                                {
                                case "ORDER":
                                case "STYLE":
                                case "CONFIG":
                                case "@CONFIG":
                                    echo "         Configuration ".json_encode($RegisterEntries)."\n";    
                                    break;
                                case "Auswertung":
                                case "Nachrichten": 
                                default:
                                    if ( ((isset($webfront_links["CONFIG"])) || (isset($webfront_links["@CONFIG"]))) && ($default==false))
                                        {
                                        //echo json_encode($RegisterEntries);
                                        echo "        Register:  ".$Group."/";
                                        if (isset($RegisterEntries["NAME"])) echo $RegisterEntries["NAME"];
                                        echo "\n";
                                        }
                                    elseif ( (isset($webfront_group["CONFIG"])) || (isset($webfront_links["@CONFIG"])) )
                                        {
                                        if (is_numeric($Group)) echo "        Register:  ".$Group."/".$RegisterEntries["NAME"]."\n";
                                        }
                                    elseif (is_array($RegisterEntries))
                                        {
                                        echo "      Gruppe:  ".$Group."\n";
                                        foreach ($RegisterEntries as $OID => $Entries)
                                            {
                                            if (is_numeric($OID))
                                                {
                                                echo "        Register:  ".$OID;
                                                echo "/".$Entries["NAME"]."\n";
                                                }
                                            }
                                        }
                                    break;
                                }
                            }               // foreach 
                        break;
                    }

                }
            }
        if ( !((isset($configWF["Enabled"])) && ($this->configWF["Enabled"]==false)) )   
            {
            if ( (isset($this->configWF["Path"])) )
                {
                $categoryId_WebFront         = CreateCategoryPath($this->configWF["Path"]);        
                if ($debug) 
                    {
                    echo "Webfront für ".IPS_GetName($categoryId_WebFront)." ($categoryId_WebFront) Kategorie im Pfad ".$this->configWF["Path"]." erstellen.\n";
                    echo "Kategorie $categoryId_WebFront (".$ipsOps->path($categoryId_WebFront).") Inhalt loeschen und verstecken. Es dürfen keine Unterkategorien enthalten sein, sonst nicht erfolgreich.\n";  
                    }            
                if ($active && $empty) $status=@EmptyCategory($categoryId_WebFront);
                if (($debug)  && ($status)) echo "   -> erfolgreich.\n";  
		        IPS_SetHidden($categoryId_WebFront, true); //Objekt verstecken
                if ($this->configWF["TabPaneParent"] != "roottp") 
                    {
                    if ( $this->exists_WFCItem($this->configWF["TabPaneParent"],false,false) )               // true mit Debug
                        {                     
                        //print_R($webfront_links);
                        if (sizeof($webfront_links)==1)                 // Unterscheidung ob TabPaneParent oder TabPaneItem genommen wird
                            {
                            if ($debug) echo "Installation im ".$this->configWF["TabPaneParent"].", nur ein Key ".array_key_first($webfront_links).":\n";
                            if ($active) $this->setupWebfront($webfront_links,$this->configWF["TabPaneParent"],$categoryId_WebFront, $scope, $debug);
                            $this->write_WebfrontConfig($WebfrontConfigID[$scope]);
                            echo "easySetupWebfront, write_WebfrontConfig for ".$WebfrontConfigID[$scope]." completed.\n";
                            }
                        elseif (sizeof($webfront_links)>1) 
                            {
                            if ($debug) 
                                {
                                echo "Installation im \"".$this->configWF["TabPaneItem"]."|".$this->configWF["TabPaneParent"]."\" mit Tabs : ";
                                foreach ($webfront_links as $key => $entry) { if ( ($key !== "CONFIG") && ($key !== "@CONFIG") ) echo "$key  "; }
                                echo "\n";
                                }
                            if ($active) $this->setupWebfront($webfront_links,$this->configWF["TabPaneItem"],$categoryId_WebFront, $scope, $debug);
                            $this->write_WebfrontConfig($WebfrontConfigID[$scope]);
                            echo "easySetupWebfront, write_WebfrontConfig for ".$WebfrontConfigID[$scope]." completed.\n";
                            }
                        else echo "easySetupWebfront: Fehler, Webfront Konfiguration für Darstellung der Daten leer.\n"; 
                        }
                    else 
                        {
                        echo "easySetupWebfront: Fehler, Webfront ".$this->configID." with TabPaneParent ".$this->configWF["TabPaneParent"]." nicht vorhanden.\n";  
                        $wfc=$this->read_wfc();                 // false interne Datenbank für Config nehmen, nur die Struktur auslesen, ich brauch alle Namen, den nur so funktionieren die Filter, Keys zum löschen
                        if (is_array($wfc))
                            {
                            foreach ($wfc as $index => $entry)                              // Index ist User, Administrator
                                {
                                echo "\n------$index:\n";
                                $this->print_wfc($wfc[$index]);
                                }
                            }              
                        }
                    }
                else echo "easySetupWebfront: Fehler, Webfront TabPaneParent roottp nicht erlaubt.\n";
                }
            else echo "easySetupWebfront: Fehler, kein Pfad für die Kategorie in der die Daten oder diel Links gespeichert werden angegeben.\n"; 
            }
        else 
            {
            echo "easySetupWebfront: Fehler, keine weitere Bearbeitung. Webfront $scope not enabled. Config ";
            print_r($this->configWF);
            }
        }                           // ende function

    /* WfcHandling::setupWebfront
     *
     * Verwendung intern und von sprachsteuerung Install
     * Aufbau einer Webfront Seite, Aufruf erfolgt von easysetupwebfront
     * ruft selbst setupWebfrontEntry auf, macht nur kurzen Plausi check
     *
     */
    public function setupWebfront($webfront_links,$WFC10_TabPaneItem,$categoryId_WebFront,$scope, $debug=false)
        {
        $active=true;            
		if ( isset($this->WebfrontConfigID[$scope]) )
			{
	        if ($debug) echo "setupWebfront: mit Parameter aus array in ".$WFC10_TabPaneItem." mit der Katgeorie ".$categoryId_WebFront." im Parent ".$this->configWF["TabPaneParent"]." für den Webfront Configurator ".$scope."\n";
            if ($active) $this->setupWebfrontEntry($webfront_links,$WFC10_TabPaneItem,$categoryId_WebFront, $scope, $debug);
            }
		else
			{	
			if ($debug) echo "Webfront ConfiguratorID unbekannt.\n";
			}
		}

    /* WfcHandling::setupWebfrontEntry, intern
     * anders probieren, nicht den scope übergeben, kann private auch sein, wird nur intern von setupWebfront verwendet 
     *
     * Parametrierung ist in $webfront_links
     * erwartet sich einen Index/key mit Auswertung, entweder in der ersten Ebene oder in der zweiten
     *      wenn nur ein Index wird die Kategorie angelegt und mit createLinks die Variablen Links angelegt
     *      wenn mehrere Indexe gibt, wird createSplitPane aufgerufen und dort alles erstellt
     *
     * im Detail noch einmal:
     *  wenn es nur einen ersten Key gibt der nicht Auswertung oder Nachrichten heisst, dann wird createSplitPane aufgerufen und dort alles erstellt
     *  wenn es nur einen ersten Key gibt der Auswertung heisst wird CreateCategoy aufgerufen und es werden die Links gleich hier erstellt
     *  wenn nur Nachrichten scheitert die Routine, wenn weder Auswertung noch Nachrichten dann werden SubTabs angelegt
     *  wenn es mehrere Keys gibt, wovon einer Auswertung oder Nachrichten heisst, dann wird createSplitPane aufgerufen und dort alles erstellt
     *
     * Die neue Implementierung geht dann so: es gibt keinen Key mit Auswertung oder Nachrichten, aber einen oder mehrere keys
     *  für jeden Key wird eine Kategorie erstellt, es gibt Zusatzinformationen unter dem Key, mit dem Subkey
     *      ORDER   es wird die order angepasst
     *      CONFIG  set von Konfigurationsdaten, der subkey muss nicht mehr Auswertung oder Nachrichten heissen, es werden ein oder zwei Tabs erstellt
     *      STYLE   andere Bearbeitung, es muss nur der key da sein
     *  dann ist wieder entscheidend wieviele sub keys vorhanden sind oder ob eine config vorliegt
     *
     *  $webfront_links = array (
     *               "stationKey" => array (            Netatmo
     *                          )
     *                        )
     *
     *  $webfront_links = array (
     *               "Auswertung" => array (            
     *                          )
     *               "Nachrichten" => array (            optional, da wird halt ein Splitpane erstellt, sonst nur ein Pane
     *                          )
     *                        )
     */

    public function setupWebfrontEntry($webfront_links,$WFC10_TabPaneItem,$categoryId_WebFrontAdministrator, $scope, $debug=false)
        {
        $active=true;                       // false for debugging purposes, true to execute
        $info   = $this->anzahlItems($webfront_links);
        if ($debug) echo "--------------------------\nsetupWebfrontEntry, Anzahl Gruppen gefunden : ".$info->count."\n";
    	if (isset($this->configWF["TabPaneOrder"])) $order=$this->configWF["TabPaneOrder"];
        else $order=10; 
        if ( (array_key_exists("Auswertung",$webfront_links)) || (array_key_exists("Nachrichten",$webfront_links)) )            // Index für Item oder SplitPane bereits in der ersten Ebene
            {
            //$count=getConfig;
            $tabItem="Default";                
            if ($info->count==1)      // kein SplitPane notwendig
                {
                if ($debug) echo "setupWebfrontEntry Typ 1.1, Kategorie Auswertung vorhanden. Nur ein Pane erstellen.\n";
                if ($active)
                    {
                    $this->CreateWFCItemCategory  ($tabItem, $WFC10_TabPaneItem,  $order, $tabItem, '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);   
                    $this->createLinks($webfront_group,$scope,$categoryId_WebFrontTab);     // FALSCH !!!
                    }
                }
            else
                {
        	    /* Kein Name für den Pane definiert */
	    		//echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";    
    		    if ($debug) echo "setupWebfrontEntry Typ 1.2, Kategorie Auswertung vorhanden, SplitPane erzeugt TabItem \"".$WFC10_TabPaneItem."Item\" in \"".$WFC10_TabPaneItem."\" mit Namen $tabItem.\n";
    	        if ($active) $this->createSplitPane($webfront_links,$tabItem,$WFC10_TabPaneItem."Item",$WFC10_TabPaneItem,$categoryId_WebFrontAdministrator,$scope);
        	    }
            }
        else
            {
            if ($debug) 
                {
                echo "setupWebfrontEntry Typ 2, Kategorie Auswertung oder Nachrichten nicht als Key vorhanden. Untergruppen bilden mit den folgenden Tabs : ";
                foreach ($info->tabs as $key => $entry) echo "$key  ";
                echo "\n";                
                }
            //if (sizeof($webfront_links)==1) 
            foreach ($webfront_links as $Name => $webfront_group)
                {
                $infoGroup   = $this->anzahlItems($webfront_group);                         // ist eine stdclass, count und tab
                $onePane=true;                                                              // wenn count == 1 und onePane immer noch true
                if (($Name=="CONFIG") || ($Name=="@CONFIG"))
                    {
                    echo "Config erkannt neben Tabname. ignorieren. Config muss untergeordnet sein.\n";         // hier könnte noch eine Erweiterungsmöglichkeit bestehen
                    }
                else    
                    {
                    // Konfigurationslemente aus der Webfront Gruppe rausbringen
                    if (isset($webfront_group["ORDER"])) 
                        {
                        $order = $webfront_group["ORDER"];
                        unset($webfront_group["ORDER"]);
                        }
                    else 
                        {
                        if ($order>200) $order=10;          // irgendwie zurück setzen, es gibt kein default
                        else $order += 10;   
                        }
                    if (isset($webfront_group["STYLE"])) 
                        {             
                        $style=true; 
                        unset($webfront_group["STYLE"]);      
                        }
                    else $style=false;
                    if ( (isset($webfront_group["CONFIG"])) || (isset($webfront_group["@CONFIG"])) )
                        {  
                        echo "Tab $Name Config Information Detected.\n";           
                        if (isset($webfront_group["CONFIG"])) $config = $webfront_group["CONFIG"];
                        else $config = $webfront_group["@CONFIG"];
                        if ( (is_array($config)) && (in_array("WFCSplitPanel",$config)) ) $onePane=false;    
                        //unset($webfront_group["CONFIG"]);      
                        }
                    else $config=false;

                    /* Das erste Arrayfeld bestimmt die Tabs in denen jeweils ein linkes und rechtes Feld erstellt werden: Bewegung, Feuchtigkeit etc.
                    * Der Name für die Felder wird selbst erfunden.
                    */

                    if ($debug) echo "***erstelle Kategorie \"".$Name."\" in ".$categoryId_WebFrontAdministrator." (".IPS_GetName($categoryId_WebFrontAdministrator)."/".IPS_GetName(IPS_GetParent($categoryId_WebFrontAdministrator)).").\n";
                    $categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontAdministrator, $order);
                    $status = @EmptyCategory($categoryId_WebFrontTab);   
                    if ($debug) 
                        {
                        echo "      Kategorien erstellt, Main install for ".$Name." : ".$categoryId_WebFrontTab." in ".$categoryId_WebFrontAdministrator." Kategorie Inhalt geloescht.\n";
                        if ($status===false) echo "       Info über Fehler von Function EmptyCategory: Kategorie $Name nicht vollständig gelöscht.\n";
                        }
                    $tabItem = $WFC10_TabPaneItem.$Name;				/* Netten eindeutigen Namen berechnen */
                    echo "Delete Panes starting with $tabItem.\n";
                    $this->deletePane($tabItem);              /* Spuren von vormals beseitigen */

                    $this->paneConfig=$config;
                    if ($config !==false ) echo "Configuration detected in Tab $Name: ".json_encode($config)."\n";

                    if ( ($config !==false ) || (array_key_exists("Auswertung",$webfront_group)) || (array_key_exists("Nachrichten",$webfront_group)) )            // Index für Item oder SplitPane bereits in der ersten Ebene
                        {
                        if ( (($onePane) && ($infoGroup->count==1) ) || ((isset($config["type"])) && ($config["type"]=="link") ))     // kein SplitPane notwendig
                            {
                            if ($debug) echo "setupWebfrontEntry Typ 2.1, Kategorie Auswertung in $Name vorhanden oder Config für link level. Nur ein Pane erstellen. Active $active\n";
                            if ($active)
                                {
                                echo "CreateWFCItemCategory   ($tabItem, $WFC10_TabPaneItem,  $order, $Name, ,$categoryId_WebFrontTab)\n";    
                                $this->CreateWFCItemCategory  ($tabItem, $WFC10_TabPaneItem,  $order, $Name, '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);   
                                if ($config !==false ) $this->createLinks($webfront_group,$scope,$categoryId_WebFrontTab,false,$debug);
                                else $this->createGroupLinks($webfront_group,$scope,$categoryId_WebFrontTab,false,$debug);
                                }
                            }
                        elseif ( (isset($config["type"])) && ($config["type"]=="pane") )
                            {
                            if ($debug) echo "setupWebfrontEntry Typ 3.n no Split Pane, but several panes.\n";
                            foreach ($webfront_group as $SubName => $webfront_subgroup)
                                {                    
                                /* noch eine Zwischenebene an Tabs einführen */
                                if ( ($active) && (strtoupper($SubName) !== "CONFIG") )
                                    {
                                    if (isset($webfront_subgroup["CONFIG"])) $subconfig=$webfront_subgroup["CONFIG"];
                                    else $subconfig=array();
                                    if ($debug) 
                                        {
                                        echo "\n         erstelle Sub Kategorie ".$SubName." in $categoryId_WebFrontTab. Subtab Konfiguration ".json_encode($config)." ";                                
                                        echo "Item Konfiguration ".json_encode($subconfig)."  Pane Config ".json_encode($this->paneConfig);
                                        echo "\n";
                                        }
                                    if (isset($subconfig["name"])===false) $subconfig["name"]="";
                                    if (isset($subconfig["icon"])===false) $subconfig["icon"]="";
                                    $categoryId_WebFrontSubTab         = CreateCategory($SubName,$categoryId_WebFrontTab, 10);
                                    EmptyCategory($categoryId_WebFrontSubTab);   
                                    //if ($debug) echo "Kategorien erstellt, Sub install for ".$SubName." : ".$categoryId_WebFrontSubTab." in ".$categoryId_WebFrontTab." Kategorie Inhalt geloescht.\n";

                                    $tabSubItem = $WFC10_TabPaneItem.$Name.$SubName;				/* Netten eindeutigen Namen berechnen */
                                    $this->deletePane($tabSubItem);              /* Spuren von vormals beseitigen */

                                    if ($debug) echo "   ***Tabpane ".$tabItem." erzeugen in ".$WFC10_TabPaneItem.". Der Name im Titel ist $Name.\n";
                                    //$this->CreateWFCItemTabPane($tabItem, $WFC10_TabPaneItem,  $order, $Name, "");    /* macht den Notenschlüssel in die oberste Leiste, oder in der zweiten Zeile den Subtab Namen */
                                    $this->CreateWFCItemCategory  ($tabItem.$SubName,   $WFC10_TabPaneItem,   10, $subconfig["name"], $subconfig["icon"], $categoryId_WebFrontSubTab   /*BaseId*/, 'false' /*BarBottomVisible*/);
                                    $this->createLinks($webfront_subgroup,$scope,$categoryId_WebFrontSubTab,false,$debug);
                                    //$this->createSplitPane($webfront_subgroup,$SubName,$tabSubItem,$tabItem,$categoryId_WebFrontSubTab,"Administrator",$debug);    
                                    }
                                }
                            }
                        else
                            {                        
                            if ($debug) echo "setupWebfrontEntry Typ 2.2, Kategorie Auswertung in $Name vorhanden oder Config, SplitPane erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem." mit Namen $Name\n";
                            $this->configWF["TabPaneOrder"]=$order;     // neue Anordnung des SplitPane, etwas komplizierte Parameter Übergabe
                            if ($active) $this->createSplitPane($webfront_group,$Name,$tabItem,$WFC10_TabPaneItem,$categoryId_WebFrontTab,"Administrator",$debug);
                            }
                        }
                    elseif ($style)     // einfache Darstellung von Variablen
                        {
                        if ($debug) echo "\n  **** new Style Visualization in ".$categoryId_WebFrontTab.".\n";
                        foreach ($webfront_group as $SubName => $webfront_subgroup)
                            { 
                            if ( ($active) && (strtoupper($SubName) !== "CONFIG") )
                                {
                                if ($debug) echo "\n         erstelle Sub Kategorie ".$SubName." in $categoryId_WebFrontTab.\n";
                                $categoryId_WebFrontSubTab         = CreateCategory($SubName,$categoryId_WebFrontTab, 10);
                                EmptyCategory($categoryId_WebFrontSubTab);   
                                //if ($debug) echo "Kategorien erstellt, Sub install for ".$SubName." : ".$categoryId_WebFrontSubTab." in ".$categoryId_WebFrontTab." Kategorie Inhalt geloescht.\n";

                                $tabSubItem = $WFC10_TabPaneItem.$Name.$SubName;				/* Netten eindeutigen Namen berechnen */
                                $this->deletePane($tabSubItem);              /* Spuren von vormals beseitigen */

                                if ($debug) echo "   ***Tabpane ".$tabItem." erzeugen in ".$WFC10_TabPaneItem.". Der Name im Titel ist $Name.\n";
                                $this->CreateWFCItemTabPane($tabItem, $WFC10_TabPaneItem,  $order, $Name, "");    /* macht den Notenschlüssel in die oberste Leiste, oder in der zweiten Zeile den Subtab Namen */

                                $this->createSplitPane($webfront_subgroup,$SubName,$tabSubItem,$tabItem,$categoryId_WebFrontSubTab,"Administrator",$debug);    
                                }
                            }                    
                        }            
                    else                // noch mehr Subgruppen, es gibt keine Auswertung/Nachrichten Tabs
                        {
                        if ($debug) echo "setupWebfrontEntry Typ 3, keine Kategorie Auswertung in $Name vorhanden oder Config definiert, erstelle eine Sub Kategorie.\n";
                        foreach ($webfront_group as $SubName => $webfront_subgroup)
                            {                    
                            /* noch eine Zwischenebene an Tabs einführen */
                            if ($debug) echo "******erstelle Sub Kategorie ".$SubName." in ".$categoryId_WebFrontTab.".\n";
                            if ($active)
                                {
                                $categoryId_WebFrontSubTab         = CreateCategory($SubName,$categoryId_WebFrontTab, 10);
                                EmptyCategory($categoryId_WebFrontSubTab);   
                                if ($debug) echo "Kategorien erstellt, Sub install for ".$SubName." : ".$categoryId_WebFrontSubTab." in ".$categoryId_WebFrontTab." Kategorie Inhalt geloescht.\n";

                                $tabSubItem = $WFC10_TabPaneItem.$Name.$SubName;				/* Netten eindeutigen Namen berechnen */
                                $this->deletePane($tabSubItem);              /* Spuren von vormals beseitigen */

                                if ($debug) echo "   ***Tabpane ".$tabItem." erzeugen in ".$WFC10_TabPaneItem.". Der Name im Titel ist $Name.\n";
                                $this->CreateWFCItemTabPane($tabItem, $WFC10_TabPaneItem,  $order, $Name, "");    /* macht den Notenschlüssel in die oberste Leiste, oder in der zweiten Zeile den Subtab Namen */

                                if ($debug) echo "   ***SplitPane erzeugt TabItem :".$tabSubItem." in ".$tabItem."\n"; 
                                $this->createSplitPane($webfront_subgroup,$SubName,$tabSubItem,$tabItem,$categoryId_WebFrontSubTab,"Administrator",$debug);    
                                }
                            }
                        }    
                    $order += 10;	
                    }                   // ende korrekter Name für TabPane
                }  // ende foreach
            }       
		}



    /* WfcHandling::createSplitPane
     * Erzeuge ein Splitpane mit Name und den Links die in webfront_group angelegt sind in WFC10_TabPaneItem
     * Nutzt zusätzlich einen class parameter paneConfig mit width
     */
    private function createSplitPane($webfront_group, $Name, $tabItem, $WFC10_TabPaneItem,$categoryId_WebFrontSubTab,$scope="Administrator", $debug=false)
        {
    	if (isset($this->configWF["TabPaneOrder"])) $order=$this->configWF["TabPaneOrder"];
        else $order=10; 
        if ($debug) echo "  createSplitPane mit Name ".$Name." Als Pane ".$tabItem." in ".$WFC10_TabPaneItem." Order $order im Konfigurator verwendet Kategorie ".$categoryId_WebFrontSubTab."\n";

		$categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFrontSubTab, 10);
		$categoryIdRight = CreateCategory('Right', $categoryId_WebFrontSubTab, 20);
		if ($debug) echo "  Kategorien erstellt, SubSub install for Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n"; 

        if ($debug) echo "   **** Splitpane mit Namen $WFC10_TabPaneItem erzeugen als $tabItem in Wfc:";
        /* @param integer $WFCId ID des WebFront Konfigurators
            * @param string $ItemId Element Name im Konfigurator Objekt Baum
            * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
            * @param integer $Position Positionswert im Objekt Baum
            * @param string $Title Title
            * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
            * @param integer $Alignment Aufteilung der Container (0=horizontal, 1=vertical)
            * @param integer $Ratio Größe der Container
            * @param integer $RatioTarget Zuordnung der Größenangabe (0=erster Container, 1=zweiter Container)
            * @param integer $RatioType Einheit der Größenangabe (0=Percentage, 1=Pixel)
            * @param string $ShowBorder Zeige Begrenzungs Linie
            */
        $width=40;
        if ($this->paneConfig !== false) 
            {
            if ($debug) echo json_encode($this->paneConfig);
            print_r($this->paneConfig);
            if (isset($this->paneConfig["width"])) { $width=$this->paneConfig["width"]; echo "Width is $width.\n"; }
            //if ((is_array($this->paneConfig[0])) && (isset($this->paneConfig[0][6])) ) { $width=$this->paneConfig[0][6]; echo "Width is $width , Parameter 7.\n"; }                        // liest von WFCSplitPanel das i aus
            }
        if ($debug) echo "\n";
        //CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
        $this->CreateWFCItemSplitPane ($tabItem, $WFC10_TabPaneItem,    $order,     $Name,     "", 1 /*Vertical*/, $width, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
        $this->CreateWFCItemCategory  ($tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
        $this->CreateWFCItemCategory  ($tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);            

        //print_r($webfront_group); 
        $this->createGroupLinks($webfront_group,$scope,$categoryIdLeft,$categoryIdRight,$debug);   
            
        }

    /* WfcHandling::createGroupLinks
     * createLinks, die eigentliche Routine, speichere die Links, es gibt zwei Kategorien Left und Right, 
     * Übergabestruktur ist immer noch mit group arrays mit Keys und link arrays mit OID (integer value) und entry arrays mit key OID und arrays NAME, ORDER
     * wenn Right default oder false ist wird Right mit Left beschrieben, also sind beide gleich
     *
     *     [Control] => Array  (
                [Read Meter] => Array  (
                     [39677] => Array (
                            [NAME] => ReadMeter ) 
                            [ORDER] =>                                      optional
                            [ADMINISTRATOR]  => true  )                     optional
                [CONFIG] => Array  (
                     [0]                     => WFCSplitPanel ) 
                                             => [["WFCSplitPanel","AutoTPAStromheizung","AutoTPA","Stromheizung","Radiator",1,40,0,0,"true"]]                                       
     *
     *      Control ist der SubTab, Übergabe erfolgt ab Read Meter ist die Gruppe
     *      Gruppe sollte Auswertung (rechts) oder Nachrichten (links) heissen, Die linke Gruppe muss nicht automatisch Nachrichten heissen, kann auch etwas anderes sein
     *      Link wird auf die OID mit dem Namen NAME und der Position ORDER angelegt
     * 
     */
    private function createGroupLinks($webfront_group,$scope,$categoryIdLeft,$categoryIdRight=false, $debug=false)
        {
        //$debug=true;
        $config=array();
        if ($categoryIdRight==false) $categoryIdRight=$categoryIdLeft;
        if ($debug) echo "    createGroupLinks aufgerufen. Category Left: $categoryIdLeft Right: $categoryIdRight .".json_encode($webfront_group)." \n";

        /* Konfiguration, wenn übermittelt, berücksichtigen */
        if ( (isset($webfront_group["CONFIG"])) || (isset($webfront_group["@CONFIG"])) ) 
            {
            if (isset($webfront_group["CONFIG"]))$config=$webfront_group["CONFIG"];          // ORDER, RIGHT
            else $config=$webfront_group["@CONFIG"];
            echo "          CONFIG erkannt ".json_encode($config)."\n";
            }
        if (isset($config["right"])==false) $config["right"]="AUSWERTUNG";

			foreach ($webfront_group as $Group => $webfront_link)
				{
                if ( ($Group == "CONFIG") || ($Group == "@CONFIG") || ($Group == "STYLE") || ($Group == "ORDER") )       // SplitPane Konfiguration
                    {
                    //echo "Konfiguration für createLinks erkannt: ".json_encode($webfront_link)."\n";   // wird bereits oben abgearbeitet
                    }
                else
                    {
                    if ($debug) echo "     ***** Gruppe : $Group bearbeiten. Zu erstellende Links suchen.\n";
                    foreach ($webfront_link as $OID => $link)
                        {
                        /* Hier erfolgt die Aufteilung auf linkes und rechtes Feld
                        * Auswertung kommt nach links und Nachrichten nach rechts
                        */	
                        if (isset($link["NAME"]) === false) { echo "no NAME key in array OID: $OID   > ".json_encode($link)." Overall ".json_encode($webfront_link); }
                        if (!( ($OID=="ORDER") || ($OID=="CONFIG") || ($OID=="@CONFIG")))
                            {
                            if ($debug) echo "        createLinks, bearbeite Link ".$Group.".".$link["NAME"]." mit OID : ".$OID."  (".json_encode($link).")\n";
                            // Optional auch einzelne Berechtigungen pro Objekt
                            if ( (($scope=="Administrator") && (((isset($link["ADMINISTRATOR"])) && ($scope=="Administrator") &&  $link["ADMINISTRATOR"]) || ((isset($link["ADMINISTRATOR"])===false)) )) ||
                                        (($scope=="User") && (((isset($link["USER"])) &&  $link["USER"]) || ((isset($link["USER"])===false)) )) || 
                                            (($scope=="Mobile") && (((isset($link["MOBILE"])) &&  $link["MOBILE"]) || ((isset($link["MOBILE"])===false)) ))  )
                                {  
                                if (isset($link["ORDER"])===false) $link["ORDER"]=10;
                                if (isset($link["PANE"])===false) $link["PANE"]=false;
                                else echo "         Link Pane definiert \n";
                                echo $Group."==".$config["right"]."?\n";
                                //if ( ($Group=="Auswertung") || ($Group==$config["right"]) || ( (isset($link["PANE"])) && ($link["PANE"]) ) )
                                if ( ($Group=="Auswertung") || ($Group==$config["right"])  )
                                    {
                                    //if ($link["PANE"])
                                    if ( (isset($link["PANE"])) && ($link["PANE"]) )
                                        {
                                        $categoryIdGroup  = CreateVariableByName($categoryIdLeft, $Group, 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
                                        if ($debug) echo "       erzeuge Link mit Name ".$link["NAME"]." auf $OID in der linken Category $categoryIdLeft in der Gruppe $categoryIdGroup \n";
                                        CreateLinkByDestination($link["NAME"], $OID,    $categoryIdGroup,  $link["ORDER"]);                                        
                                        }
                                    else
                                        {
                                        
                                        if ($debug) echo "       erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der linken Category ".$categoryIdLeft."\n";
                                        CreateLinkByDestination($link["NAME"], $OID,    $categoryIdLeft,  $link["ORDER"]);
                                        }
                                    }
                                else
                                    {
                                    //if ( (isset($link["PANE"])) && ($link["PANE"]==false) )
                                    if ( (isset($link["PANE"])) && ($link["PANE"]) )
                                        {
                                        $categoryIdGroup  = CreateVariableByName($categoryIdRight, $Group, 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
                                        if ($debug) echo "       erzeuge Link mit Name ".$link["NAME"]." auf $OID in der rechten Category $categoryIdLeft in der Gruppe $categoryIdGroup \n";
                                        CreateLinkByDestination($link["NAME"], $OID,    $categoryIdGroup,  $link["ORDER"]);                                        
                                        }
                                    else
                                        {
                                        if ($debug) echo "       erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der rechten Category ".$categoryIdRight."\n";
                                        CreateLinkByDestination($link["NAME"], $OID,    $categoryIdRight,  $link["ORDER"]);
                                        }
                                    }
                                }
                            }
                        } // ende foreach
                    }                               // CONFIG ausfiltern
                }  // ende foreach  

        }

    /* WfcHandling::createLinks
     *
     */
    private function createLinks($webfront_group,$scope,$categoryIdLeft,$categoryIdRight=false, $debug=false)
        {
        //$debug=true;
        $config=array();
        if ($categoryIdRight==false) $categoryIdRight=$categoryIdLeft;
        if ($debug) echo "    createLinks aufgerufen. Category Left: $categoryIdLeft Right: $categoryIdRight .".json_encode($webfront_group)." \n";

        /* Konfiguration, wenn übermittelt, berücksichtigen */
        if (isset($webfront_group["CONFIG"])) 
            {
            echo "          CONFIG erkannt ".json_encode($webfront_group["CONFIG"])."\n";
            $config=$webfront_group["CONFIG"];          // ORDER, RIGHT
            }
        if (isset($config["right"])==false) $config["right"]="AUSWERTUNG";

			foreach ($webfront_group as $OID => $link)
				{
                if ( ($OID == "CONFIG") || ($OID == "STYLE") || ($OID == "ORDER") )       // SplitPane Konfiguration
                    {
                    //echo "Konfiguration für createLinks erkannt: ".json_encode($webfront_link)."\n";   // wird bereits oben abgearbeitet
                    }
                else
                    {
                    /* Hier erfolgt die Aufteilung auf linkes und rechtes Feld
                    * Auswertung kommt nach links und Nachrichten nach rechts
                    */	
                    if (isset($link["NAME"]) === false) { echo "WebfrontGroup OID: $OID, no NAME in config of Link:   "; print_r($link); }               // fehlererkennung
                    elseif ($OID!="ORDER")
                        {
                        if ($debug) echo "        createLinks, bearbeite Link ".$link["NAME"]." mit OID : ".$OID."  (".json_encode($link).")\n";
                        // Optional auch einzelne Berechtigungen pro Objekt
                        if ( (($scope=="Administrator") && (((isset($link["ADMINISTRATOR"])) && ($scope=="Administrator") &&  $link["ADMINISTRATOR"]) || ((isset($link["ADMINISTRATOR"])===false)) )) ||
                                    (($scope=="User") && (((isset($link["USER"])) &&  $link["USER"]) || ((isset($link["USER"])===false)) )) || 
                                        (($scope=="Mobile") && (((isset($link["MOBILE"])) &&  $link["MOBILE"]) || ((isset($link["MOBILE"])===false)) ))  )
                            {  
                            if (isset($link["ORDER"])===false) $link["ORDER"]=10;
                            if (isset($link["PANE"])===false) $link["PANE"]=false;
                            else echo "         Link Pane definiert \n";
                            if ($debug) echo "       erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der linken Category ".$categoryIdLeft."\n";
                            CreateLinkByDestination($link["NAME"], $OID,    $categoryIdLeft,  $link["ORDER"]);
                            }
                        }
                    } // ende foreach
                }                               // CONFIG ausfiltern

        }



    /* WfcHandling::CreateWFCItemRootTabPane
     * Anlegen eines TabPanes im WebFront Konfigurator, Nutzung von IPSInstaller
	 *
	 * Der Befehl legt im WebFront Konfigurator ein TabPane mit dem Element Namen $ItemId an
	 *
	 * @param integer $WFCId ID des WebFront Konfigurators
	 * @param string $ItemId Element Name im Konfigurator Objekt Baum
	 * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
	 * @param integer $Position Positionswert im Objekt Baum
	 * @param string $Title Title
	 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
	 *
	 */
	public function CreateWFCItemRootTabPane ($WFCId, $ItemId, $ParentId, $Position, $Title, $Icon) 
        {
		PrepareWFCItemData ($ItemId, $ParentId, $Title);
		$Configuration = "{\"subTitle\":\"$Title\",\"name\":\"$ItemId\",\"subIcon\":\"$Icon\"}";
		CreateWFCItem ($WFCId, $ItemId, $ParentId, $Position, $Title, $Icon, 'TabPane', $Configuration);
	    }

    /* WfcHandling::deletePane
     * does not delete Panes as long there is no write command
     * works on internal config copy now
     */

    public function deletePane($tabItem)
        {
        if ( $this->exists_WFCItem($tabItem) )
            {
            echo "deletePane, Webfront Config löscht TabItem : ".$tabItem."\n";
            $this->DeleteWFCItems($tabItem);
            }
        else
            {
            echo "deletePane, Webfront Config TabItem : ".$tabItem." nicht mehr vorhanden.\n";
            }	
        }

    }   // ende class

/******************************************************************
 *
 * Module und Klassendefinitionen
 *
 * __construct	 		speichert bereits alle Libraries und Module in Klassenvariablen ab, braucht dann natürlich etwas Platz
 *
 *   printrLibraries	gibt die gespeicherte Variable für die Library aus
 *   printrModules		gibt die gespeicherte Variable für die Module aus, alle Module für alle Libraries
 *
 *   printLibraries     echo Ausgabe der verfügbaren Bibliotheken, das sind die externen Libraries wie Astronomy
 *   getLibrary         Libraries nach einer bestimmten Needle untersuchen, wenn false alle, nach einer ID nach einem Namen
 *   printModules		Alle Module die einer bestimmten Library zugeordnet sind als echo ausgeben
 *   printInstances		Alle Instanzen die einem bestimmten Modul zugeordnet sind als echo ausgeben
 *   getInstances		Alle Instanzen die einem bestimmten Modul zugeordnet sind als array ausgeben
 *   getDiscovery       Alle installierten Discovery Instanzen nach Typ 5 ausgeben
 *   getModules         Alle Module die einer bestimmten Library, Auswahl nach ID oder Name, zugeordnet sind ausgeben 
 *
 *   addDiscovery       add Discovery Instanzen zu einem array aus einem Array mit Libraries
 *   addConfigurator    add Configurator Instanzen zu einem array aus einem Array mit bestimmten Libraries
 *   addNonDiscovery    ander Konfigurator Instanzen dazugeben
 *
 *   getInstancesByType Alle installierten Instanzen mit einem bestimmten Typ als Array ausgeben
 *   getInstancesByName
 *   getFunctions
 *   getFunctionAsArray
 *   
 *   get_string_between($input,'{','}')		Unterstützungsfunktion um den json_decode zu unterstützen
 *   selectConfiguration
 *
 *   Instanzen haben configurationForm, dieses analysieren
 *
 *   lookforkeyinarray      ConfigurationForm analysieren 
 *   analyseConfigForm   
 *   analyseConfigStructure
 *   findParentsConfigForm
 *   findChildsConfigForm
 *   findAllChildsConfigForm
 *   findAllParentsConfigForm
 *
 *
 ******************************************************************/

class ModuleHandling
	{
	
	private $libraries;	// array mit Liste der Namen und GUIDs von Libraries 
	private $modules;	// array mit Liste der Namen und GUIDs von Modules 
	private $functions;
	private $debug;
    private $ips;       // Hilfestellung
	
    /* verwendet class storage for libraries and modules, mit Indizierter Suche 
     */
	public function __construct($debug=false)
		{
		$this->debug=$debug;
		if ($debug) echo "Alle verfügbaren Bibliotheken mit GUID ausgeben:\n";
		foreach(IPS_GetLibraryList() as $guid)
			{
			$module = IPS_GetLibrary($guid);
			$pair[$module['Name']] = $guid;
			}
		ksort($pair);
		foreach($pair as $key=>$guid)
			{
			$this->libraries[$key]=$guid;
			if ($debug) echo "    ".$key." = ".$guid."\n";
			}
		unset($pair);
		if ($debug) echo "Alle installierten Modulnamen mit GUID ausgeben: \n";
		foreach(IPS_GetModuleList() as $guid)
			{
			$module = IPS_GetModule($guid);
			$pair[$module['ModuleName']] = $guid;
			}
		ksort($pair);
		foreach($pair as $key=>$guid)
			{
			$this->modules[$key]=$guid;
			if ($debug) echo $key." = ".$guid."\n";
			}
        $this->ipsOps=new ipsOps();
		}

    /* abgeleitet von IPS_GetLibraryList(), in construct erstellt
     */
	public function printrLibraries()
		{
		print_r($this->libraries);
		}

    /* abgeleitet von IPS_GetModuleList(), in construct erstellt
     */
	public function printrModules()
		{
		print_r($this->modules);
		}

	/* Alle Libraries als echo ausgeben 
     */
	public function printLibraries()
		{
		echo "Alle verfügbaren Bibliotheken auflisten:\n";		
		foreach($this->libraries as $index => $library)
			{
			//print_r($module);
			echo "   ".str_pad($index,35)."    ".$library."\n";
			}
		}

	/* Libraries nach einer bestimmten Needle untersuchen, wenn
     *  false      alle Libraries als array ausgeben
     *  GUID       Name der Library ausgeben
     *  Name       Name der Library ausgeben
     */
	public function getLibrary($needleID=false, $debug=false)
		{
        if ($debug) echo "getLibrary aufgerufen:\n";
        $result=false;
        if ($needleID!==false)
            {
		    $needleID=trim($needleID);
		    $key=$this->get_string_between($needleID,'{','}');
    		if (strlen($key)==36) 
                {
                if ($debug) echo "Gültige GUID mit ".$key."\n";                    
                foreach($this->libraries as $index => $library)
                    {
                    if ($debug) echo "   ".str_pad($index,35)."    ".$library."\n";
                    if ($library == $needleID) 
                        {
                        $result=$index;
                        }
                    }
                return($result);
                }
            else   
                {
                foreach($this->libraries as $index => $library)
                    {
                    if ($debug) echo "   ".str_pad($index,35)."    ".$library."\n";
                    if ($index == $needleID) 
                        {
                        $result=$index;
                        }
                    }
                return($result);
                }
            }
        else
            {
    		foreach($this->libraries as $index => $library)
	    		{
                if ($debug) echo "   ".str_pad($index,35)."    ".$library."\n";
    			}
            return($this->libraries);
            }
		}

	/* Alle Module die einer bestimmten Library zugeordnet sind ausgeben 
     */
	public function printModules($input)
		{
        $modules = $this->getModules($input);
		/*$input=trim($input);
		$key=$this->get_string_between($input,'{','}');
		if (strlen($key)==36) 
			{
			echo "Gültige GUID mit ".$key."\n";
			$modules=IPS_GetLibraryModules($input);
			}
		else
			{
			// wahrscheinlich keine GUID sondern ein Name eingeben 
			if (isset($this->libraries[$input])==true)
				{
				echo "Library ".$input." mit GUID ".$this->libraries[$input]." hat folgende Module:\n";
				$modules=IPS_GetLibraryModules($this->libraries[$input]);
				}
			else $modules=array();	
			} */
		$pair=array();			
		foreach($modules as $guid)
			{
			$module = IPS_GetModule($guid);
			$pair[$module['ModuleName']]["GUID"] = $guid;
            switch ($module['ModuleType'])
                {
                /*
                    0	Kern Instanz
                    1	I/O Instanz
                    2	Splitter Instanz
                    3	Gerät Instanz
                    4	Konfigurator Instanz
                    5	Discovery Instanz
                    6   
                */
                case 0:
                    $pair[$module['ModuleName']]["Type"] = "Kern";
                    break;
                case 1:
                    $pair[$module['ModuleName']]["Type"] = "I/O";
                    break;
                case 2:
                    $pair[$module['ModuleName']]["Type"] = "Splitter";
                    break;
                case 3:
                    $pair[$module['ModuleName']]["Type"] = "Gerät";
                    break;
                case 4:
                    $pair[$module['ModuleName']]["Type"] = "Konfigurator";
                    break;
                case 5:
                    $pair[$module['ModuleName']]["Type"] = "Discovery";
                    break;
                case 6:
                    $pair[$module['ModuleName']]["Type"] = "Visualization";             // neu hinzugekommen, Webfront
                    break;                    
                default:
                    $pair[$module['ModuleName']]["Type"] = $module['ModuleType'];
                    break;
                }
			}
		if ( sizeof($pair) > 0 ) ksort($pair);
		foreach($pair as $modulName=>$entry)
			{
			echo "     ".str_pad($modulName,30)." = ".str_pad($entry["GUID"],40)."     ".$entry["Type"];
			//if (IPS_ModuleExists($guid)) echo "***************";
			echo "\n";
			}
        return($modules);
		}

	/* Alle Instanzen die einem bestimmten Modul zugeordnet sind als echo ausgeben 
     */
	public function printInstances($input)
		{
		$input=trim($input);
		$key=$this->get_string_between($input,'{','}');
		if (strlen($key)==36) 
			{
			echo "Gültige GUID mit ".$key."\n";
			$instances=IPS_GetInstanceListByModuleID($input);
			}
		else
			{
			/* wahrscheinlich keine GUID sondern ein Name eingeben */
			if (isset($this->modules[$input])==true)
				{
				echo "Instanz ".$input." hat GUID :".$this->modules[$input]."\n";
				$instances=IPS_GetInstanceListByModuleID($this->modules[$input]);
				}
			else $instances=array();	
			}		
		foreach ($instances as $ID => $name) echo "     ".str_pad($ID,5).str_pad($name,7).str_pad(IPS_GetName($name),30)."  ".IPS_GetName(IPS_GetParent($name))."\n";
		}

	/* ModuleHandling::getInstances
     * Alle Instanzen die einem bestimmten Modul zugeordnet sind als array ausgeben
     * der Modulname kann auf unterschiedliche Varianten übermittelt werden
     * als Modul Identifier:        {31F53ADE-EC84-55ED-901D-38C5EF0970C4}
     * als Name:
     * wenn empty oder *:           alle installierten Instanzen, egal welches Modul
     */
	public function getInstances($input, $format="OID")
		{
        if (is_array($input))
            {
            $instances=array();
            foreach ($input as $item)
                {
                $result = $this->getInstances($item);           // ruft sich selbst auf
                //print_R($result);   
                $instances = array_merge($instances, $result);
                }
            return($instances);
            }
        //echo "getInstances aufgerufen mit Parameter $input \n"; 
		$input=trim($input);
		$key=$this->get_string_between($input,'{','}');
		if (strlen($key)==36) 
			{
            /* Übergabe einer ModulID */
			if ($this->debug) echo "Anforderung mit Module GUID : ".$key."\n";
			$instances=IPS_GetInstanceListByModuleID($input);
			}
		elseif ( ($input=="") || ($input=="*") )
            {
            //echo "   get all Instances.\n";
            $instances = IPS_GetInstanceList();
            }
        else    
			{
			/* wahrscheinlich keine GUID sondern ein Modulname eingeben */
            //echo "   look for a module with this name $input \n";
			if (isset($this->modules[$input])==true)
				{
				//echo "Objekt Input ".$input." hat GUID :".$this->modules[$input]."\n";
				$instances=IPS_GetInstanceListByModuleID($this->modules[$input]);
				}
			else 
                {
                //$asterix=explode("*",$input);
                //print_r($asterix);
                //echo "Fehler getInstances: Modulname unbekannt.\n";
                $instances=array();	
                }
			}
        if ($format=="OID") return ($instances);
        else
            {
            $result=array();
            foreach ($instances as $instance)    
                {
                $result[IPS_GetName($instance)]=$instance;
                }
            return ($result);
            }
		}

    /* ModuleHandling::getDiscovery
     * Alle installierten Discovery Instanzen nach Typ 5 ausgeben
     *   Homematic
     *
     *   andere müssen manuell mit addNonDiscovery() hinzugefügt werden
     */

	public function getDiscovery($debug=false)
		{
        return ($this->getInstancesByType(5,false,$debug));
        }

    /* ModuleHandling::getModules
     * Alle Module die einer bestimmten Library, Auswahl nach ID oder Name, zugeordnet sind ausgeben 
     */
	public function getModules($input, $debug=false)
		{
		$input=trim($input);
		$key=$this->get_string_between($input,'{','}');
		if (strlen($key)==36) 
			{
			if ($debug) echo "Gültige GUID mit ".$key."\n";
			$modules=IPS_GetLibraryModules($input);
			}
		else
			{
			/* wahrscheinlich keine GUID sondern ein Name eingeben */
			if (isset($this->libraries[$input])==true)
				{
				if ($debug) echo "Library ".$input." mit GUID ".$this->libraries[$input]." hat folgende Module:\n";
				$modules=IPS_GetLibraryModules($this->libraries[$input]);
				}
			else $modules=array();	
			}
        return($modules);
        }

    /* ModuleHandling::addDiscovery
     * add Discovery Instanzen zu einem array aus einem Array mit Libraries
     */
    public function addDiscovery(&$discovery,$libraries,$debug=false)
        {
        if ($debug) echo "addDiscovery, all Libraries and Units with Discovery Modul:\n";
        foreach ($libraries as $library)
            {
            $instances=$this->getInstancesByType(5,["Library"=>$library]);          //mit Debug, Discovery aus library
            if ($debug>1) print_r($instances);
            foreach ($instances as $instance)
                {
                $modName=$instance["ModuleName"];
                $pos=strpos($modName,"Discovery");
                $unitName=trim(substr($modName,0,$pos));
                if ($debug) echo "   ".str_pad($modName,25)."   ".str_pad($library,30)."   $unitName\n";
                $input["ModuleID"]    = $instance["ModuleID"];        
                $input["ModuleName"]  = $modName; 
                $input["UnitName"]    = $unitName; 
                $input["LibraryName"] = $library; 
                $discovery[]=$input; 
                }
            }
        return ($discovery);
        }

    /* ModuleHandling::addConfigurator
     * add Configurator Instanzen zu einem array aus einem Array mit bestimmten Libraries
     * discovery ist der Ausgabewert als array, es werden discovery und Konfigurator Instanzen ermittelt
     * die Libraries für die Konfiguratoren gesucht werden sollen, werden als parameter übergeben
     * in units werden die Ergebnisse gesammelt
     *
     */
    public function addConfigurator(&$discovery,$libraries,$debug=false)
        {
        if ($debug) echo "addConfigurator \n";
        $units=array();
        //print_r($discovery);
        foreach ($discovery as $entry)
            {
            if (isset($entry["UnitName"])) $units[$entry["UnitName"]]=true;                     // wenn discovery schon definiert, keinen Konfigurator dazunehmen
            }
        foreach ($libraries as $library)
            {
            $instances=$this->getInstancesByType(4,["Library"=>$library]);          //mit Debug, Discovery (5) und Konfigurator (4) aus library ermitteln
            if ($debug>1) print_r($instances);
            $modules=array(); 
            foreach($instances as $instance)
                {
                $modules[$instance["ModuleID"]]=$instance;              // gleiche werden überschrieben    
                }
            foreach ($modules as $instance)
                {
                $modName=$instance["ModuleName"];
                $pos=strpos($modName,"Configurator");
                $unitName=trim(substr($modName,0,$pos));
                if (isset($units[$unitName])===false)
                    {                   
                    if ($debug) echo "   ".str_pad($modName,25)."   ".str_pad($library,30)."   $unitName\n"; 
                    $input["ModuleID"]   = $instance["ModuleID"];        
                    $input["ModuleName"] = $instance["ModuleName"];  
                    $input["UnitName"]   = $unitName; 
                    $units[$unitName]    = true;                        // hinzufügen, nur einmal
                    $discovery[]=$input; 
                    }
                }
            }

        return ($discovery);
        }

    /* Alle zusätzlichen nicht automatisierbaren Discovery Instanzen ausgeben, dazu discovery anreichern
     *      AmazonEchoConfigurator
     *      NetatmoWeatherConfig
     *      HomeMatic RF Interface Configurator
     *      HUE Configurator
     *      FS20EX Instanzen, FS20 Instanzen, FHT Instanzen         deprecated
     *      CAM Instanzen
     *      SwitchBot
     *      MQTT Client
     *      MQTT Server
     */
	public function addNonDiscovery(&$discovery,$debug=false)
		{
        //$discovery=array();       // brauchen wir nicht, wird gleich am lebenden Objekt umgesetzt

        /* wenn keine Discovery verfügbar, dann den Configurator als Übergangslösung verwenden 
        * {44CAAF86-E8E0-F417-825D-6BFFF044CBF5} = AmazonEchoConfigurator
        * {DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8} = NetatmoWeatherConfig
        *
        */
        if (false)
            {
            $input["ModuleID"]   = "{EE92367A-BB8B-494F-A4D2-FAD77290CCF4}";        // add HUE, nicht mehr benötigt, es gibt Philips Hue V2
            $input["ModuleName"] = "HUE Configurator";
            $discovery[]=$input;
            }
        
        // vgl addConfigurator
        // AmazonEchoConfigurator, NetatmoWeatherConfig, 
        //$guids=["{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}","{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}","{91624C6F-E67E-47DA-ADFE-9A5A1A89AAC3}",'{0D86E724-D3DB-5685-03CE-DDD6B981F39B}','{2408C0E0-E672-7FE5-414B-F78C9B9244E4}','{CC4F15B1-81C2-4F45-8D53-972F0C9C8103}'];
        $guids=[        "EchoConfigurator"                      => "{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}",
                        "NetatmoWeatherConfig"                  => "{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}",
                        "HomeMatic RF Interface Configurator"   => "{91624C6F-E67E-47DA-ADFE-9A5A1A89AAC3}",
                        "SwitchBot Konfigurator"                => '{0D86E724-D3DB-5685-03CE-DDD6B981F39B}',
                        "MQTT Client Configurator"              => '{2408C0E0-E672-7FE5-414B-F78C9B9244E4}',
                        "MQTT Server Configurator"              => '{CC4F15B1-81C2-4F45-8D53-972F0C9C8103}'
            ];        
        foreach ($guids as $name=>$guid)
            {
            $instances=$this->getInstances($guid);
            if (sizeof($instances)==0) 
                {
                $lib=@IPS_GetLibrary($guid)["Name"];
                if ($lib===false) $lib=@IPS_GetModule ($guid)["ModuleName"];
                echo "   Warning, addNonDiscovery, no Configurators available for ";
                if ($lib == false) echo "Library/Module $name ";
                else echo "Library/Module $lib ";
                echo " $guid.\n";
                }
            else
                {
                $input["ModuleID"]   = $guid;        // add Switchbot
                $instanceInfo=IPS_GetInstance($instances[0]);
                $input["ModuleName"] = $instanceInfo["ModuleInfo"]["ModuleName"];
                $discovery[]=$input;
                }
            }
        /*
        $input["ModuleID"]   = "{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}";        // add EchoControl
        $input["ModuleName"] = "AmazonEchoConfigurator";
        $discovery[]=$input;
        $input["ModuleID"]   = "{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}";        // add NetatmoWeather
        $input["ModuleName"] = "NetatmoWeatherConfig";
        $discovery[]=$input;
        $input["ModuleID"]   = "{0D86E724-D3DB-5685-03CE-DDD6B981F39B}";        // add Switchbot
        $input["ModuleName"] = "Switchbot Konfigurator";
        $discovery[]=$input;
        $input["ModuleID"]   = "{91624C6F-E67E-47DA-ADFE-9A5A1A89AAC3}";
        $input["ModuleName"] = "HomeMatic RF Interface Configurator";
        $discovery[]=$input;
        $input["ModuleID"]   = "{2408C0E0-E672-7FE5-414B-F78C9B9244E4}";
        $input["ModuleName"] = "MQTT Client Konfigurator";
        $discovery[]=$input;
        $input["ModuleID"]   = "{CC4F15B1-81C2-4F45-8D53-972F0C9C8103}";
        $input["ModuleName"] = "MQTT Server Konfigurator";
        $discovery[]=$input;*/

        /* wenn keine Konfiguratoren verfügbar dann die GUIDs der Instanzen eingeben
        *
        *
        */
        $input["ModuleID"] =   "{56800073-A809-4513-9618-1C593EE1240C}";            // FS20EX Instanzen
        $input["ModuleName"] = "FS20EX Instanzen";  
        $discovery[]=$input;
        $input["ModuleID"] =   "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}";            // FS20EX Instanzen
        $input["ModuleName"] = "FS20 Instanzen";          
        $discovery[]=$input;
        $input["ModuleID"] =    "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}";           // FHT devices Instanzen, kein Konfigurator, kein Discovery, haendische Installation
        $input["ModuleName"] = "FHT Instanzen";
        $discovery[]=$input;     
        $input["ModuleID"] =    "{D26101C0-BE49-7655-87D3-D721064D4E40}";           // OperationCenter Cam Instanzen, kein Konfigurator, kein Discovery, haendische Installation
        $input["ModuleName"] = "CAM Instanzen";
        $discovery[]=$input; 
          
        /*    IPSHeat nur indirekt einbringen, als Parameter für Actuators       
        $input["ModuleID"] =    "{81F09287-FDDF-204E-98CB-30B27D106ECE}";           // IPSHeat Instanzen, kein Konfigurator, kein Discovery, haendische Installation
        $input["ModuleName"] = "IPSHeat Instanzen";
        $discovery[]=$input;     
        */

        return ($discovery);
        }

    /* ModuleHandling::getInstancesByType
     * Alle installierten Instanzen mit einem bestimmten Typ ausgeben
     * WERT	BESCHREIBUNG
     *  0	Kern
     *  1	I/O
     *  2	Splitter
     *  3	Geräte
     *  4	Konfigurator
     *  5	Discovery
     *  6	Visualisierung
     * es werden zuerst alle Instances mit dem selben Type ermittelt. Aus den Instance Parametern wird ein Subset aus OID,Name,ModulName, ModulID, ModuleType ermittelt
     * dann wird überprüft ob das Modul mit ModulID noch vorhanden ist
     * settings übergibt ein array[Library]=libraryname, wenn die ModulLibrary ungleich ist wird in der Ausgabe gefiltert
     * das Ergebnis sind alle Instances mit einem Type aus einer Library, wenn keine Library angegeben wurde ist das Ergebnis alle instances mit einem Type
     * $instances=$modulhandling->getInstancesByType(1,["Library"=>"Built-In"]);
     */
	public function getInstancesByType($type,$settings=false,$debug=false)
		{
        if ($settings===false) $settings=array();
        $configurator=array();
        if ($debug) echo "getInstancesByType mit Type $type aufgerufen :\n"; 
        $discovery2=IPS_GetInstanceListByModuleType($type);
        $result=array();
        foreach($discovery2 as $instance)
            {
            $result[$instance]["OID"]=$instance;
            $result[$instance]["Name"]=IPS_GetName($instance);
            $moduleinfo = IPS_GetInstance($instance)["ModuleInfo"];
            //print_r($moduleinfo);
            if ($debug>1) echo "   ".$instance."   ".str_pad(IPS_GetName($instance),42)."    ".$moduleinfo["ModuleName"]."\n";
            $result[$instance]["ModuleName"] = $moduleinfo["ModuleName"];
            $result[$instance]["ModuleID"]   = $moduleinfo["ModuleID"];
            $result[$instance]["ModuleType"] = $moduleinfo["ModuleType"];
            }
        $i=0;
        foreach ($result as $entry)
            {
            $getModule=@IPS_GetModule($entry["ModuleID"]);
            if ($getModule === false) echo "FEHLER: ".$entry["ModuleName"]." eingetragen, aber nicht mehr installiert. Gehe zum Store.\n";
            else
                {
                $libraryID=$getModule["LibraryID"];
                $libraryName=$this->getLibrary($libraryID);
                $filter=true;
                if ( (isset($settings["Library"])) && ($settings["Library"]!=$libraryName) ) $filter=false;
                elseif ( (isset($settings["Module"])) && ($settings["Module"]!=$entry["ModuleName"]) ) $filter=false;                
                if ($debug) echo "   ".$entry["OID"]."   ".str_pad($entry["Name"],52)."    ".str_pad($entry["ModuleName"],32)."    ".$libraryName."\n";    
                if ($filter) 
                    {
                    $configurator[$i]=$entry;
                    $configurator[$i]["Library"]=$libraryName;
                    }
                }
            $i++;
            }
        //print_r($configurator);
        return ($configurator);
		//return ($this->getInstancesByName("Discovery"));
		}

    /* Alle installierten Instanzen, die ein Schlüsselwort enthalten ausgeben
     *
     */
	public function getInstancesByName($search)
		{
        //echo "getInstancesByName aufgerufen :\n"; 
        $instances = $this->getInstances('');          // alle installierten Instanzen, sonst ist es etwas komplizierter da die Instanzen nur über die Libraries ermittelt werden können
        $result=array(); $configurator=array();
        foreach ($instances as $instance) 
            {
            $moduleinfo = IPS_GetInstance($instance)["ModuleInfo"];
            //print_r($moduleinfo);
            //echo "   ".$instance."   ".str_pad(IPS_GetName($instance),42)."    ".$moduleinfo["ModuleName"]."\n";
            $result[$instance]["OID"]=$instance;
            $result[$instance]["Name"]=IPS_GetName($instance);
            $result[$instance]["ModuleName"]=$moduleinfo["ModuleName"];
            $result[$instance]["ModuleID"]=$moduleinfo["ModuleID"];
            $result[$instance]["ModuleType"]=$moduleinfo["ModuleType"];
            }
        $sort=$this->ipsOps->intelliSort($result, "ModuleName");
        //print_r($result);
        $i=0;
        foreach ($result as $entry)
            {
            if (strpos($entry["ModuleName"],$search) !== false) 
                {
                $configurator[$i]=$entry;
                $getModule=@IPS_GetModule($entry["ModuleID"]);
                if ($getModule === false) echo "FEHLER: ".$entry["ModuleName"]." eingetragen, aber nicht mehr installiert. Gehe zum Store.\n";
                else
                    {
                    $libraryID=$getModule["LibraryID"];
                    $libraryName=$this->getLibrary($libraryID);
                    echo "   ".$entry["OID"]."   ".str_pad($entry["Name"],32)."    ".str_pad($entry["ModuleName"],32)."    ".$libraryName."\n";    
                    $configurator[$i]["Library"]=$libraryName;
                    }
                $i++;
                }
            }
        //print_r($sort);        
		return ($configurator);
		}


	/* Alle Funktionen die einem bestimmten Modul zugeordnet sind als print/echo ausgeben 
     */
	public function getFunctions($lookup="")
		{
		if ($lookup=="") $alleFunktionen = IPS_GetFunctionList(0);
		else
			{
			$alleFunktionen = array();
			$functions = IPS_GetFunctionList(0);
			foreach ($functions as $function)
				{
				$pos=strpos($function,"_");
				if ($pos) 
					{
					$funcName=substr($function,$pos+1);
					$funcModul=substr($function,0,$pos);
					if ($funcModul==$lookup) 
						{
						echo "   ".str_pad($funcModul,10)."   ".$funcName."   \n";
						$alleFunktionen[]=$function;
						}
					}
				}	
			}
		return ($alleFunktionen);
		}

	public function getFunctionAsArray($lookup="")
        {
        if ($lookup=="")
            {
            echo "Funktionlist, alle Module:\n";
            $instanceid = 0; //0 = Alle Funktionen, sonst Filter auf InstanzID
            }
        else
            {
            echo "Funktionlist, Module $lookup :\n";
            $instanceid = $lookup;
            }
        //Exportiert alle IP-Symcon Funktionen mit einer Parameterliste
        $fs = IPS_GetFunctionList($instanceid);
        asort($fs);
        $typestr = Array("boolean", "integer", "float", "string", "variant", "array");
        foreach($fs as $f) 
            {
            $f = IPS_GetFunction($f);
            echo sprintf("[%7s]", $typestr[$f['Result']['Type_']]) . " - ".$f['FunctionName']."(";
            $a = Array();
            foreach($f['Parameters'] as $p) 
                {
                if(isset($p['Enumeration']) && sizeof($p['Enumeration']) > 0) 
                    {
                    $b=Array();
                    foreach($p['Enumeration'] as $k => $v) 
                        {
                        $b[] = $k."=".$v;
                        }
                    $type = "integer/enum[".implode(", ", $b)."]";
                    } 
                else 
                    {
                    $type = $typestr[$p['Type_']];
                    }
                $a[]=$type." $".$p['Description'];
                }
            echo implode(", ", $a).");\n";
            }        
        return ($a);
        }
        
    /* Strukturen die nicht unbedingt jeson encoded sind von ihren {} Klammern befreien
     */	
	private function get_string_between($string, $start, $end)
		{
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
		}	

    /* Config einer Instanz oder einem array aus Instanzen auslesen und bestimmte Variablen der Konfiguration als array mitgeben
     * Filterfunktion für Konfiguration
     */
	public function selectConfiguration($id,$select=false)
		{
        $result=array();
		if (is_array($id)) 
			{
			echo "Bereits ein array.\n";
			$ida=$id;
			}
		else $ida[0]=$id;
		foreach ($ida as $id1)
			{
            $config=IPS_GetConfiguration($id1);     /* alle Instanzen durchgehen */
            $configStruct=json_decode($config);
            if ( ($select===false) or !(is_array($select)) ) $result[$id1]=$configStruct;
            else    /* select ist ein Array */
                {
                foreach ($select as $entry)
                    {
                    if (isset($configStruct->$entry)) $result[$id1][$entry]=$configStruct->$entry;
                    }
			    //echo ">>>>>> ".$id1."\n";
			    //print_r($select);
                }
			}
        return ($result);    
		} /* ende function */

    /* lookforkeyinarray
     * einen besonderen key finden, liese sich auch recursive anstellen
     * für das Untersuchen von ConfigurationForms
     */
    public function lookforkeyinarray($config,$key,$debug=false)
        {
        $actions=false;
        if ($debug) echo "lookforkeyinarray, debug requested:\n";
        if (is_array($config))
            {
            foreach ($config as $type => $item)
                {
                if ($debug) 
                    {
                    echo "    ".str_pad($type,23)." | ";
                    if (is_array($item)) echo sizeof($item);
                    echo "\n";  
                    }
                if ($type==$key) $actions=$item; 
                if (is_array($item)) foreach ($item as $index => $entry) 
                    {
                    if ($debug) 
                        {                    
                        echo "        ".str_pad($index,20)." | ";
                        if (is_array($entry)) echo sizeof($entry);
                        echo "\n"; 
                        }
                    if ($index==$key) $actions=$entry; 
                    if ($index=="de") ;                             // Schönheitskorrektur für mehr Übersichtlichkeit
                    elseif (is_array($entry)) foreach ($entry as $topic => $subentry)
                        {
                        if ($debug) 
                            {                         
                            echo "           ".str_pad($topic,17)." | ";
                            if (is_array($subentry)) echo sizeof($subentry);
                            echo "\n";   
                            }
                        if ($topic==$key) $actions=$subentry;
                        }  
                    }
                }
            }
        else echo "lookforkeyinarray, warning, no array provided.\n";
        return $actions;
        }

    // look for caption in actions, elements aso that has Values
    function lookforkeytypecaptioninarray($form,$look,$debug=false)
        {
        if ( (is_array($look)) && (is_array($form)) )
            {
            if ( (isset($look["key"])) && (isset($look["type"])) && (isset($look["caption"])) )  
                {             
                foreach ($form as $key => $item)
                    {
                    if ($debug) echo "   $key \n";
                    foreach ($item as $index => $entry)
                        {
                        if (isset($entry["type"])) $type=$entry["type"];
                        else $type="";
                        if (isset($entry["caption"])) $caption=$entry["caption"];
                        else $type="";            
                        if ($debug) echo "    ".str_pad($index,3).str_pad($type,20).str_pad($caption,40)."\n";
                        // wir finden key,type,caption  return key index
                        if ( ($look["key"]==$key) && ($look["type"]==$type) && ($look["caption"]==$caption) )
                            return ["key"=>$key,"index"=>$index];
                        }
                    }
                }
            }
        return false;
        }

    /* standardisierte Ausgabe der Daten von ConfigForm
     * typical script
            $config=json_decode(IPS_GetConfigurationForm(5333l),true);
            $actions = $modulhandling->lookforkeyinarray($config,"actions");         // actions->[0]->values oder actions->values
            $values  = $modulhandling->lookforkeyinarray($actions[0],"values",$debug); 
            $resultDevices=$modulhandling->findAllParentsConfigForm($values,$debug);
            $modulhandling->analyseConfigForm($resultDevices);
     * return Value ist eine Liste sortiert nach Namen
     * übernimmt resultDevices von findAllParents, spezifische Eintrage mitnehmen
     */
    public function analyseConfigForm($resultDevices,$nonshow=false)
        {
        $result=array();
        if ($nonshow=="array") $show=false;             // keinen text ausgeben, macht die Routine danach
        else $show=true;
        foreach ($resultDevices as $idx=>$device)         // mehrere Bridges, use of id, name, child
            {
            // additional information
            if (isset($device["ModelName"])) $modelName=$device["ModelName"];
            else $modelName="unknown";
            if (isset($device["Productname"])) { $productName=$device["Productname"]; }
            else $productName="unknown";
            if ($show) echo str_pad($device["name"],40)."   $modelName  $productName\n";
            foreach ($device["child"] as $idx=>$configurator)
                {
                if (isset($configurator["instanceID"])) $instanceId=$configurator["instanceID"];
                else $instanceId="unknown";
                if (isset($configurator["create"][0]["moduleID"])) $moduleID=$configurator["create"][0]["moduleID"];
                else $moduleID="unknown";
                $name=""; $type="unknown";
                if (isset($configurator["name"])) $name=$configurator["name"];
                if (isset($configurator["Type"])) $type=$configurator["Type"];
                if ($name=="") $name=$type;
                if ($show) echo "     ".str_pad($instanceId,12)."   ".str_pad($name,50)." $moduleID  \n";
                $result[$idx]["oid"]=$instanceId;
                $result[$idx]["name"]=$name;
                $result[$idx]["groupname"]=$device["name"];
                if ($productName!="unknown") $result[$idx]["productname"]=$productName;
                }
            }
        return $result;            
        }
    /* analyseConfigStructure -- depricated, copied from DeviceManagement
     * Values in der ConfigurationForms gefunden, jetzt analysieren, die ConfigurationForms ist mit parent als hierarchische Struktur aufgebaut
     * wahrscheinlich immer ähnliche Struktur, hierarchische Struktur flat aufgebaut als gleichwertige Eintraege mit laufender id
     * wenn parent dann ein child
     *  
     *  root   $id => [ id=> , name=>  ,   Productname=> , instanceId=>,  Type=> , ]   
     *     wenn ein root gefunden ist gleich nach passenden childs durchsuchen              
     *
     * name und Productname werden nur übernommen wenn ein Eintrag vorhanden ist, also nicht blank
     * sonst name=id, type=value[Type], 
     *
     * Reihenfolge umdrehen, die Childs erfassen und den Parent anzeigen
     *
     */
    public function analyseConfigStructure($values,$debug=false)
        {
        $result=array();
        if ($debug) echo "analyseConfigStructure \n";
        foreach ($values as $id => $value)
            {
            $itemId=$value["id"];
            if (isset($value["parent"])==false)     // nur root 
                {
                if ($debug) echo "$id   ID : $itemId  ";                                        // Ausgabe zB 0  ID : 1  Stehlampe     Hue white lamp
                if ( (isset($value["name"])) && ($value["name"] != "")  ) 
                    {
                    $name=$value["name"];
                    if ($debug) echo $value["name"]."     ";
                    //print_r($value);
                    }
                else $name=$id;
                /*
                if ( (isset($value["Productname"])) && ($value["Productname"] != "")  )             // Type wird entweder Productname Hue Motion Sensor oder Type device
                    {
                    $type=$value["Productname"];
                    if ($debug) echo $type."     ";
                    //print_r($value);
                    }
                elseif (isset($value["Type"])) $type=$value["Type"];
                */
                if ( (isset($value["instanceID"])) && ((int)$value["instanceID"]>0) ) 
                    {
                    if ($debug) echo "  OID:  ".$value["instanceID"]."     ";
                    //print_r($value);
                    }
                //if (isset($value["instanceID"])) echo "  OID:  ".$value["instanceID"];
                foreach ($values as $idx => $child)          // looking for childrens
                    {
                    if ( (isset($child["parent"])) && ($child["parent"]==$itemId) ) 
                        {
                        //print_r($child);
                        //if ($debug) echo "\n    $idx  ".$child["Type"]." ";
                        if ( (isset($child["instanceID"])) && ((int)$child["instanceID"]>0) ) 
                            {
                            if ($debug) echo "  OID:  ".$child["instanceID"]."     ";
                            $deviceOID=$child["instanceID"];
                            $result[$deviceOID]["instanceID"]=$deviceOID;              // geht sonst bei merge verloren
                            $result[$deviceOID]["name"]=$name;
                            //$result[$deviceOID]["Type"]=$type;
                            //$result[$deviceOID]["TypeChild"]=$child["Type"];
                            $found=$id;
                            //print_r($value);
                            }
                        }
                    }
                if ($debug) echo "\n";
                }
            }
        return($result);
        }

    /* vorausgesetzte Struktur   id, parent, [ ]  oder ID, parent, [  ], entscheidet welches nicht empty
     */
    public function findParentsConfigForm($values,$debug=false)
        {
        $result=array();
        if ($debug) echo "analyseConfigStructure, findParentsConfigForm \n";
        foreach ($values as $key => $value)
            {
            $itemId=false;
            if ((isset($value["id"])) && ($value["id"] != "")) $itemId=$value["id"];
            if ((isset($value["ID"]))  && ($value["ID"] != "")) $itemId=$value["ID"];
            if ($itemId)
                {
                if (isset($value["parent"])==false)     // nur root 
                    {
                    $result[$itemId]=$value;
                    }
                }
            else            // weder id noch ID vorhanden
                {
                echo "findParentsConfigForm, unknown structure: "; 
                print_R($value);
                break;
                }
            }
        return($result);
        }

    /* vorausgesetzte Struktur   id, parent, [ ]
     * find childs with parent itemId, store with its 
     */
    public function findChildsConfigForm($values,$itemId,$debug=false)
        {
        $result=array();
        $parents=$this->findParentsConfigForm($values);
        if ($debug) echo "analyseConfigStructure, findChildsConfigForm for parent $itemId : \n";
        foreach ($values as $key => $value)
            {
            if ((isset($value["parent"])) && ($value["parent"]==$itemId))     // nur root und parentId = itemId
                {
                $value["parent"]=$parents[$itemId];
                $id=false;
                if (isset($value["id"])) $id=$value["id"];
                if (isset($value["ID"])) $id=$value["ID"];
                if ($id) $result[$id]=$value;
                }
            }
        return($result);
        }

    /* vorausgesetzte Struktur :  id, parent, [ ]
     * wir strukturieren nach Child Items und ordnen den Parent zu 
     */
    public function findAllChildsConfigForm($values,$debug=false)
        {
        $result=array();
        $parents=$this->findParentsConfigForm($values);
        if ($debug) echo "analyseConfigStructure, findChildsConfigForm for all parents : \n";
        foreach ($values as $key => $value)
            {
            if (isset($value["parent"]))     // nur root 
                {
                $value["parent"]=$parents[$value["parent"]];
                $itemId=$value["id"];
                $result[$itemId]=$value;
                }
            }
        return($result);
        }


    /* vorausgesetzte Struktur :  id, parent, [ ]
     * wir strukturieren nach Parent Items und ordnen die Childs zu
     */
    public function findAllParentsConfigForm($values, $debug=false)
        {
        $result=array();
        if ($debug) echo "analyseConfigStructure, findAllParentsConfigForm, findChildsConfigForm for all parents : \n";
        $parents=$this->findParentsConfigForm($values,$debug);
        foreach ($parents as $key => $value)
            {
            $value["child"]=$this->findChildsConfigForm($values,$key);
            $result[$key]=$value;
            }
        return($result);
        }

	}           // ende class ModuleHandling


/******************************************************************
 *
 * Unique ID
 *  v3
 *  v4 
 *  v5
 *
 * The following class generates VALID RFC 4211 COMPLIANT Universally Unique IDentifiers (UUID) version 3, 4 and 5.
 * Version 3 and 5 UUIDs are named based. They require a namespace (another valid UUID) and a value (the name). Given the same namespace and name, the output is always the same.
 * Version 4 UUIDs are pseudo-random.
 * UUIDs generated below validates using OSSP UUID Tool, and output for named-based UUIDs are exactly the same. This is a pure PHP implementation.
 * Ausgabe als '%08s-%04s-%04x-%04x-%12s' : zB
 *
 ******************************************************************/

class UUID 
    {
    public static function v3($namespace, $name) 
        {
        if(!self::is_valid($namespace)) return false;

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-','{','}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for($i = 0; $i < strlen($nhex); $i+=2) 
            {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
            }

        // Calculate hash value
        $hash = md5($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',

            // 32 bits for "time_low"
            substr($hash, 0, 8),

            // 16 bits for "time_mid"
            substr($hash, 8, 4),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 3
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

            // 48 bits for "node"
            substr($hash, 20, 12)
            );
        }

    public static function v4() 
        {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }

  public static function v5($namespace, $name) {
    if(!self::is_valid($namespace)) return false;

    // Get hexadecimal components of namespace
    $nhex = str_replace(array('-','{','}'), '', $namespace);

    // Binary Value
    $nstr = '';

    // Convert Namespace UUID to bits
    for($i = 0; $i < strlen($nhex); $i+=2) {
      $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
    }

    // Calculate hash value
    $hash = sha1($nstr . $name);

    return sprintf('%08s-%04s-%04x-%04x-%12s',

      // 32 bits for "time_low"
      substr($hash, 0, 8),

      // 16 bits for "time_mid"
      substr($hash, 8, 4),

      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 5
      (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

      // 48 bits for "node"
      substr($hash, 20, 12)
    );
  }

  public static function is_valid($uuid) {
    return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.
                      '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
  }
}

/******************************************************************

Alternativer Error Handler

******************************************************************/

function AD_ErrorHandler($fehlercode, $fehlertext, $fehlerdatei, $fehlerzeile)
    {
    $errorReporting=error_reporting();    
    echo "\n";
    //echo "AD_ErrorHandler($fehlercode, $fehlertext, $fehlerdatei, $fehlerzeile ... Error Reporting: ".($errorReporting & $fehlercode)." \n";
    if (!(error_reporting() & $fehlercode)) 
        {
        echo "AD_ErrorHandler, unprocessed error code : [$fehlercode] $fehlertext in $fehlerdatei:$fehlerzeile\n";
        // Dieser Fehlercode ist nicht in error_reporting enthalten
        return;
        }
    $noerror=false;
    switch ($fehlercode) 
        {
        case E_WARNING:
            echo "<b>AD_ErrorHandler, WARNING</b> [$fehlercode] $fehlertext in $fehlerdatei:$fehlerzeile<br />\n";
            if (strpos($fehlertext,"DUTY_CYCLE") !== false) $noerror=true;          // DUTY_CYCLE Fehler unterdrücken
            else
                {
                //echo "  Warning in Zeile $fehlerzeile in der Datei $fehlerdatei";
                //echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                //print_r($Vars); echo "\n";            
                }
            break;
        case E_USER_ERROR:
            echo "<b>AD_ErrorHandler, USER ERROR</b> [$fehlercode] $fehlertext in $fehlerdatei:$fehlerzeile<br />\n";
            //echo "  User Fehler in Zeile $fehlerzeile in der Datei $fehlerdatei";
            //echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
            //echo "Abbruch...<br />\n";
            //exit(1);
            break;
        case E_USER_WARNING:
            echo "<b>AD_ErrorHandler, USER WARNING</b> [$fehlercode] $fehlertext in $fehlerdatei:$fehlerzeile<br />\n";
            break;
        case E_USER_NOTICE:
            echo "<b>AD_ErrorHandler, USER NOTICE</b> [$fehlercode] $fehlertext in $fehlerdatei:$fehlerzeile<br />\n";
            break;
        default:
            echo "AD_ErrorHandler, Unbekannter Fehlertyp: [$fehlercode] $fehlertext in $fehlerdatei:$fehlerzeile<br />\n";
            break;
        }
    if ($noerror==false)
        {
    	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	    $AllgemeineDefId     = IPS_GetObjectIDByName('AllgemeineDefinitionen',$moduleManager->GetModuleCategoryID('data'));
        //echo "ScriptID ist ".$AllgemeineDefId."  ".IPS_GetName($AllgemeineDefId)."/".IPS_GetName(IPS_GetParent($AllgemeineDefId))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($AllgemeineDefId)))."\n";    
    
        if ($AllgemeineDefId===false)
            {
            restore_error_handler();
            return (true);
            //throw new ErrorException($fehlertext, 0, $fehlercode, $fehlerdatei, $fehlerzeile);
            }
        else
            {
            $ErrorHandlerAltID = CreateVariableByName($AllgemeineDefId, "ErrorHandler", 3);
            $ErrorHandler=GetValue($ErrorHandlerAltID);
            if (function_exists($ErrorHandler) == true)
                {
                echo "Nächsten Error Handler aufrufen basierend auf $ErrorHandlerAltID : $ErrorHandler  ";
                echo str_pad(sprintf('%b', $fehlercode),16,"0",STR_PAD_LEFT)." Mask $errorReporting : ".str_pad(sprintf('%b', $errorReporting),16,"0",STR_PAD_LEFT)."  ".str_pad(sprintf('%b', E_ALL),16,"0",STR_PAD_LEFT);
                echo "\n";
                /* function IPSLogger_PhpErrorHandler ($ErrType, $ErrMsg, $FileName, $LineNum, $Vars) */
                $fehler=$ErrorHandler($fehlercode, $fehlertext, $fehlerdatei, $fehlerzeile);
                }
            else return (true);
            }
        }    
    }



/******************************** DEPRCIATED **************************/


function createPortal($Path)
	{
		$categoryId_WebFront         = CreateCategoryPath($Path);
		$categoryId_WebFrontTemp     = CreateCategoryPath($Path.".Temperatur");
		$categoryId_WebFrontHumi     = CreateCategoryPath($Path.".Feuchtigkeit");
		$categoryId_WebFrontSwitch   = CreateCategoryPath($Path.".Schalter");

		IPSUtils_Include ("RemoteReadWrite_Configuration.inc.php","IPSLibrary::config::modules::RemoteReadWrite");

		//IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");

		$Homematic = HomematicList();
		$FHT = FHTList();
		$FS20= FS20List();
	
		foreach ($Homematic as $Key)
			{
			/* alle Temperaturwerte ausgeben */
			if (isset($Key["COID"]["TEMPERATURE"])==true)
	   		{
      		$oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontTemp,  10);
				//print_r($Key["COID"]["TEMPERATURE"]);
				//echo $Key["COID"]["TEMPERATURE"]["OID"]." ";
				//echo date("d.m h:i",IPS_GetVariable($oid)["VariableChanged"])." ";
				//echo $Key["Name"].".".$Key["COID"]["TEMPERATURE"]["Name"]." = ".GetValueFormatted($oid)."\n";
				}
			}

		foreach ($FHT as $Key)
			{
			/* alle Temperaturwerte ausgeben */
			if (isset($Key["COID"]["TemeratureVar"])==true)
		   	{
      		$oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontTemp,  10);
				}
			}

		foreach ($Homematic as $Key)
			{
			/* alle Feuchtigkeitswerte ausgeben */
			if (isset($Key["COID"]["HUMIDITY"])==true)
		   	{
	   	   $oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontHumi,  10);
				//$alleHumidityWerte.=str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			}

   	$categoryId_WebFrontSwitchFS20   = CreateCategoryPath($Path.".Schalter.FS20");
		foreach ($FS20 as $Key)
			{
			/* alle Statuswerte ausgeben */
			if (isset($Key["COID"]["StatusVariable"])==true)
			   {
      		$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontSwitchFS20,  10);
				}
			}
   	$categoryId_WebFrontSwitchHM   = CreateCategoryPath($Path.".Schalter.Homematic");
		foreach ($Homematic as $Key)
			{
			/* alle Temperaturwerte ausgeben */
			if (isset($Key["COID"]["STATE"])==true)
	   		{
	      	$oid=(integer)$Key["COID"]["STATE"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontSwitchHM,  10);
				}
			}
	}


    function getNiceFileSize($bytes, $binaryPrefix=true) {
        if ($binaryPrefix) {
            $unit=array('B','KiB','MiB','GiB','TiB','PiB');
            if ($bytes==0) return '0 ' . $unit[0];
            return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
        } else {
            $unit=array('B','KB','MB','GB','TB','PB');
            if ($bytes==0) return '0 ' . $unit[0];
            return @round($bytes/pow(1000,($i=floor(log($bytes,1000)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
        }
    }

    // Returns used memory (either in percent (without percent sign) or free and overall in bytes)
    
    function getServerMemoryUsage($getPercentage=true)
    {
        $memoryTotal = null;
        $memoryFree = null;

        if (stristr(PHP_OS, "win")) {
            // Get total physical memory (this is in bytes)
            $cmd = "wmic ComputerSystem get TotalPhysicalMemory";
            @exec($cmd, $outputTotalPhysicalMemory);

            // Get free physical memory (this is in kibibytes!)
            $cmd = "wmic OS get FreePhysicalMemory";
            @exec($cmd, $outputFreePhysicalMemory);

            if ($outputTotalPhysicalMemory && $outputFreePhysicalMemory) {
                // Find total value
                foreach ($outputTotalPhysicalMemory as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $memoryTotal = $line;
                        break;
                    }
                }

                // Find free value
                foreach ($outputFreePhysicalMemory as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $memoryFree = $line;
                        $memoryFree *= 1024;  // convert from kibibytes to bytes
                        break;
                    }
                }
            }
        }
        else
        {
            if (is_readable("/proc/meminfo"))
            {
                $stats = @file_get_contents("/proc/meminfo");

                if ($stats !== false) {
                    // Separate lines
                    $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                    $stats = explode("\n", $stats);

                    // Separate values and find correct lines for total and free mem
                    foreach ($stats as $statLine) {
                        $statLineData = explode(":", trim($statLine));

                        //
                        // Extract size (TODO: It seems that (at least) the two values for total and free memory have the unit "kB" always. Is this correct?
                        //

                        // Total memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemTotal") {
                            $memoryTotal = trim($statLineData[1]);
                            $memoryTotal = explode(" ", $memoryTotal);
                            $memoryTotal = $memoryTotal[0];
                            $memoryTotal *= 1024;  // convert from kibibytes to bytes
                        }

                        // Free memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemFree") {
                            $memoryFree = trim($statLineData[1]);
                            $memoryFree = explode(" ", $memoryFree);
                            $memoryFree = $memoryFree[0];
                            $memoryFree *= 1024;  // convert from kibibytes to bytes
                        }
                    }
                }
            }
        }

        if (is_null($memoryTotal) || is_null($memoryFree)) {
            return null;
        } else {
            if ($getPercentage) {
                return (100 - ($memoryFree * 100 / $memoryTotal));
            } else {
                return array(
                    "total" => $memoryTotal,
                    "free" => $memoryFree,
                );
            }
        }
    }
    




?>