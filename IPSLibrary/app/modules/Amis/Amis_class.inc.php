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


    /************************************
     *
     * Klassen zur Energieberechnung
     *  Amis    
     *  AmisSmartmeter extends Amis
     *  AmisTopology extends Amis
     *
     */

    /*************************************
     * class Amis
     *
     * Zusammenfassung der mit Energieberechnung verwandten Funktionen
     * behandelt Homematic Energiemessgeräte und den AMIS Zähler
     *
     *  __construct                 speichert strukturierte MeterConfig
     *  setMeterConfig              mit dieser Routine wird MeterConfig erstellt
     *  getMeterConfig              die bereinigte Konfiguration auslesen, die disabled registers sind weg
     *
     *  getWirkenergieID            Wirkenergie Spiegelregister mit dem Namen Wirkenergie in der Kategorie des jeweiligen Objektes
     *  getZaehlervariablenID       beliebiges Register aus den Zaehlervariablen der jeweiligen Kategorie heraussuchen, nur für AMIS aktuell
     *  getRegisterIDbyConfig
     *  getRegisterID               aus der MeterConfig oder mit einem Namen die ID herausfinden
     *  getWirkleistungID           speziell für Wirkleistung, siehe auch getWirkenergieID
     *  getHomematicRegistersfromOID   Homematic Instance OID übergeben, schauen ob childrens enthalten sind und die richtigen register POWER, ENERGY_COUNTER herausholen
     *  getRegistersfromOID         allgemeine Funktion das Energieregister herauszufinden. Unabhängig von einer Hardwaretype
     *
     *  getPortConfiguration
     *  getSystemDir                Das Verzeichnis am Server finden
     *  getAmisAvailable
     *  configurePort
     *
     *  sendReadCommandAmis         Lese Befehle an den AMIS Zähler schicken, regelmaessig in der Statemachine aufgerufen (11,8,6,1)
     *                              Antwort kommt über Serial und Cutter Module wieder rein
     *
     *  writeEnergyHomematics       aus einer MeterConfig als Parameter alle Homematic Register ausgeben, verwendet writeEnergyHomematic
     *  writeEnergyHomematic        abhängig vom Typ schreiben,regelmaessig in der Statemachine aufgerufen
     *  writeEnergyRegister         regelmaessig in der Statemachine aufgerufen
     *  writeEnergySumme            regelmaessig in der Statemachine aufgerufen (15)
     *  writeEnergyAmis
     *
     *  writeEnergyRegistertoString         die Tabelle "Stromzählerstand aktuell Energiewert in kWh:" als html schreiben,
     *                                      dazu alle Homematic Energiesensoren der letzten Woche als Wert auslesen, ignoriert andere Typen, html formatierung default, nur hinter execute verwendet
     *  writeEnergyRegisterValuestoString   die Tabelle "Stromzählerstand aktuell Energiewert in kWh" mit Werten aus writeEnergyRegistertoArray schreiben, Inputparameter sind die Werte, öfter verwendet, auch für send_status
     *  writeEnergyRegisterTabletoString    die html Tabelle Stromverbrauch der letzten Tage als Änderung der Energiewerte pro Tag mit Werten aus writeEnergyRegistertoArray, wird öfter verwendet
     *  writeEnergyPeriodesTabletoString    die html Tabelle Stromverbrauch als Periodenwerte aggregiert in kWh, auch für send_status
     *
     *  getEnergyRegister                   für Debug, visualisisert die Werte aus writeEnergyRegistertoArray
     *  getAMISDataOids
     *
     *  writeEnergyRegistertoArray          in BerechnePeriodenwerte
     *  getArchiveData
     *  getArchiveDataMax
     *  aggregate15minPower2Energy          aus Lastprofilen Tageswerte machen
     *  do_register
     *  do_calculate
     *  summestartende                      1/7/360 Summen erstellen
     *
     *
     ********************************************************************/

	class Amis {

        public $CategoryIdData, $CategoryIdApp;
		private $archiveHandlerID=0;
        protected $installedModules;
		
		private $MeterConfig,$AmisConfig;           // die bereinigre Meter Config und neu die Amis Config
        private $systemDir;              // das SystemDir, gemeinsam für Zugriff zentral gespeichert

        public $result;                 // Array mit aktuellen zusätzlichen Ergebnissen

        protected $ipsOps,$dosOps;
        protected $debug;                 // zusaetzliche hilfreiche Debugs
		
		/**
		 * @public
		 *
		 * Initialisierung der AMIS class
		 *
		 */
		public function __construct($debug=false) 
			{
            $this->debug=$debug;
            $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	        $moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
	        $this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	        $this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
            $this->installedModules   = $moduleManager->GetInstalledModules();

			$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

            $this->ipsOps = new ipsOps();
            $this->dosOps = new dosOps();
            $this->systemDir     = $this->dosOps->getWorkDirectory();

			$this->MeterConfig = $this->setMeterConfig();
			$this->AmisConfig  = $this->setAmisConfig();
            $this->result=array();                                        // init Ergebnis Variable result
			}

        /* aus der offiziellen Config die deaktivierten Zähler herausfiltern, kommen gar nicht soweit
         * Umstellung auf set/get Meter Configuration
         */
        public function setMeterConfig()
            {
            if (function_exists("get_Cost")) $cost=get_Cost();
            else $cost=0.40;
            if ($this->debug) 
                {
                echo "setMeterConfig aufgerufen. SystemDir ist ".$this->getSystemDir().".\n";
                echo "Energy Cost is ".($cost*100)." €cent.\n";
                }
            $result=array();
            if ((function_exists("get_MeterConfiguration"))===false) IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');            
            // die Disabled Meters rausnehmen
            foreach (get_MeterConfiguration() as $index => $config)
                {
                //echo "Bearbeite Zähler $index.\n"; print_r($config);
                if ( (isset($config["Status"])) && ( (strtoupper($config["Status"])=="DISABLED") || (strtoupper($config["Status"])=="DEACTIVATED") ) )
                    {
                    /* Deaktivierte Energiezähler aus der Konfig nehmen */
                    }
                else
                    {                    
                    //$result[$index]=$config;
                    // configfileParser(&$inputArray, &$outputArray, $synonymArray,$tag,$defaultValue,$debug=false)
                    configfileParser($config,$result[$index],["Name","NAME","name"],"NAME",$index);
                    configfileParser($config,$result[$index],["Type","TYPE","type"],"TYPE","Register");
                    configfileParser($config,$result[$index],["Order","ORDER","order"],"ORDER","Main");
                    configfileParser($config,$result[$index],["costkWh","COSTKWH","costkwh","Costkwh","CostKwh"],"costkWh",$cost);
                    configfileParser($config,$result[$index],["Source","SOURCE","source"],"Source","default");
                    configfileParser($config,$result[$index],["VariableName","VARIABLENAME","nariablename","Variablename"],"VariableName",null);
                    configfileParser($config,$result[$index],["OIDTYPE","oidtype","OidType","oidType","OIDType"],"OIDType","kWh");                    
                    if (strtoupper($result[$index]["TYPE"])=="AMIS") 
                        {
                        configfileParser($config,$result[$index],["Port","PORT","port"],"PORT",null);                                   // default null, produziert einen Fehler wenn nicht vorhanden
                        configfileParser($config,$result[$index],["ComPort","Comport","COMPORT","comport"],"COMPORT",null);           // produziert einen Fehler wenn nicht vorhanden
                        configfileParser($config,$result[$index],["Calculate","CALCULATE","calculate"],"CALCULATE",null);           // produziert einen Fehler wenn nicht vorhanden
                        configfileParser($config,$result[$index],["Register","REGISTER","register"],"REGISTER",null);           // produziert einen Fehler wenn nicht vorhanden
                        }
                    if (strtoupper($result[$index]["TYPE"])=="HOMEMATIC") 
                        {
                        configfileParser($config,$result[$index],["Oid","OID","oid"],"OID",null);
                        if (isset($result[$index]["OID"])===false) echo "Warning, OID must be provided for TYPE Homematic.\n";
                        }
                    if (strtoupper($result[$index]["TYPE"])=="DAILYREAD") 
                        {
                        if (isset($result[$index]["WirkenergieID"])===false) 
                            {
                            $oid=$this->getWirkEnergieID($config,$this->debug);
                            //echo "Warning, setMeterConfig, OID Identifier must be provided for TYPE DAILYREAD of ".$result[$index]["NAME"].". Found one by searching: $oid\n";
                            }                            
                        configfileParser($config,$result[$index],["WirkenergieID","WIRKENERGIEID","WirkenergieId","Oid","OID","oid"],"WirkenergieID",$oid);
                        if ( (isset($result[$index]["WirkenergieID"])===false) || ($result[$index]["OIDType"] !== "kWh") ) 
                            {
                            echo "Warning, OID of OIDType kWh must be provided for TYPE DAILYREAD.\n";
                            print_R($result[$index]);
                            }
                        }
                    if (strtoupper($result[$index]["TYPE"])=="DAILYLPREAD") 
                        {
                        $oid=null;
                        if (isset($result[$index]["LeistungID"])===false) 
                            {
                            $oid=$this->getWirkleistungID($config,$this->debug);
                            //echo "Warning, setMeterConfig, OID Identifier must be provided for TYPE DAILYLPREAD of ".$result[$index]["NAME"].". Found one by searching: $oid\n";
                            }
                        if ($result[$index]["OIDType"] !== "kWh") configfileParser($config,$result[$index],["LeistungID","LEISTUNGID","LeistungId","Oid","OID","oid"],"LeistungID",$oid);
                        else                                      configfileParser($config,$result[$index],["WirkenergieID","WIRKENERGIEID","WirkenergieId","Oid","OID","oid"],"WirkenergieID",null); 
                        // both Types are possible
                        }
                    if (strtoupper($result[$index]["TYPE"])=="SUMME") 
                        {
                        configfileParser($config,$result[$index],["Calculate","CALCULATE","calculate"],"Calculate",null);
                        if (isset($result[$index]["Calculate"])===false) echo "Warning, Calculate must be provided for TYPE SUMME.\n";
                        }  
                    if (strtoupper($result[$index]["ORDER"])=="SUB")  configfileParser($config,$result[$index],["Parent","PARENT","parent"],"PARENT","Main");
                    }
                }
            return ($result);
            }

        /* und die Meter Configuration ausgeben 
         */
        public function getMeterConfig()
            {
            return ($this->MeterConfig);
            }

        /* aus der allgemeinen Amis Config grundsaetzliche Einstellungen über die Funktionsweise übernehmen
         * verwendet set/get AmisConfiguration
         */
        public function setAmisConfig()
            {
            if (function_exists("get_Cost")) $cost=get_Cost();
            else $cost=0.40;
            if ($this->debug) 
                {
                echo "setAmisConfig aufgerufen. SystemDir ist ".$this->getSystemDir().".\n";
                echo "Energy Cost is ".($cost*100)." €cent.\n";
                }
            $result=array();
            if ((function_exists("get_AmisConfiguration"))===false) IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis'); 
            if (function_exists("get_AmisConfiguration")) 
                {
                $amisConfiguration = get_AmisConfiguration();
                if (( (is_array($amisConfiguration)) && (sizeof($amisConfiguration)) ) === false) $amisConfiguration=array();
                }
            else $amisConfiguration=array();

            configfileParser($amisConfiguration,$result,["Status","STATUS","status"],"Status","active");
            configfileParser($amisConfiguration,$result,["WebPage","WEBPAGE","webpage"],"WebPage",array());
            configfileParser($amisConfiguration,$result,["FILE","File","file"],"File",null);

            return ($result);
            }

        /* und die allgemeine Amis Configuration ausgeben 
         */
        public function getAmisConfig()
            {
            return ($this->AmisConfig);
            }


        /* getWirkenergieID aus der Config entnehmen
         *
         * in der AMIS Data Kategorie gibt es pro meter["Name"] eine Variable, die Variable wird vorausgesetzt, false wennnicht
         * unter dieser Variable gibt es dann auch die Zusammenfassung Periodenwerte
         * die VariableID, also die Datenquelle wird durch die Configuration "WirkenergieID" festgelegt
         * wenn diese nicht vorhanden ist kann abhängig vom Meter "Type"  auch woanders gesucht werden.
         *
         * erweitert um Abfrage ohne meter=array Configuration
         * es wird nur nach dem NAME gesucht, verwende neue getRegisterID function
         *
         */
        public function getWirkenergieID($meter, $debug=false)
            {
            if (is_array($meter))
                {
                $ID = IPS_GetObjectIDByName($meter["NAME"], $this->CategoryIdData);  
                if ($debug) echo "getWirkenergieID suche nach ".$meter["NAME"]." in ".$this->CategoryIdData."  found as category $ID \n";               
                $variableID=false;
                if ($ID)
                    {
                    if (isset($meter["WirkenergieID"]) == true )	 
                        { 
                        $variableID = $meter["WirkenergieID"]; 
                        }
                    else
                        {
                        /* Variable ID selbst festlegen */
                        //echo "     Variable Wirkenergie selber anlegen. nicht in Konfiguration vorgesehen:\n";
                        switch (strtoupper($meter["TYPE"]))
                            {
                            case "AMIS":
                                $AmisID = IPS_GetObjectIDByName( "AMIS", $ID);
                                $variableID = IPS_GetObjectIDByName ( 'Wirkenergie' , $AmisID );
                                //$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
                                //$variableID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
                                break;
                            case "HOMEMATIC":
                            case "REGISTER":
                            case "DAILYREAD":
                            case "DAILYLPREAD":
                            case "SUMME": 
                                $variableID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
                                break;
                            default:
                                //echo "Fehler, Type noch nicht bekannt.\n";
                                break;	
                            }        
                        //print_r($meter);
                        }
                    }
                else echo "   --> Fehler, Variable ".$meter["NAME"]." in ".$this->CategoryIdData." nicht vorhanden.\n";
                return ($variableID);
                }
            else return ($this->getRegisterID($meter,'Wirkenergie',$debug));
            }

        /* beliebiges Register aus den Zaehlervariablen heraussuchen 
         */
        public function getZaehlervariablenID($meter, $identifier,$debug=false)
            {
            //echo "getZaehlervariablenID(".json_encode($meter).", $identifier\n";                
			return ($this->getRegisterIDbyConfig($meter,$identifier,$debug));
            }

        /* selbe function wie oben nur anderer Name 
         */
        public function getRegisterIDbyConfig($meter,$identifier,$debug=false)
            {
            if ((is_array($meter))===false)           // Name und nicht aus der MeterConfig
                {
                $config=$this->getMeterConfig();
                foreach ($config as $index => $meterEntry)
                    {
                    if ($debug) echo "   checking \"".$meterEntry["NAME"]."\" mit Type ".$meterEntry["TYPE"]."\n";
                    if ($meterEntry["NAME"]==$meter)
                        {
                        $meter = $meterEntry; 
                        break;
                        }
                    }
                }
            if ($debug) echo "getRegisterIDbyConfig(".json_encode($meter).", $identifier\n";                
            $LeistungID=false;                
            $ID = @IPS_GetObjectIdByName($meter["NAME"], $this->CategoryIdData);   // ID der Kategorie  
            switch (strtoupper($meter["TYPE"]))
                {
                case "DAILYLPREAD":
                case "HOMEMATIC":
                    if ($ID===false)  { echo "Warnung, Kategorie noch nicht installiert.\n"; return (false); }                      
                    $LeistungID = @IPS_GetObjectIdByName($identifier,$ID);   // nur eine Wirkleistung gespeichert
                    if ($LeistungID && $debug) echo "   --> Ergebnis $LeistungID in $ID\n";                           
                    break;
                case "DAILYREAD":
                    if ($identifier=="Wirkenergie") $LeistungID=$meter["WirkenergieID"];
                    else
                        {
                        if ($ID===false)  { echo "Warnung, Kategorie noch nicht installiert.\n"; return (false); }                      
                        $LeistungID = @IPS_GetObjectIdByName($identifier,$ID);   // nur eine Wirkleistung gespeichert
                        }
                    //print_R($meter);  
                    break;
                case "AMIS":
                    $AmisID = IPS_GetObjectIDByName( "AMIS", $ID);
                    //echo "AmisID $AmisID (".$this->ipsOps->path($AmisID).")\n"; 
                    $zaehlervarID = IPS_GetObjectIDByName ( 'Zaehlervariablen' , $AmisID );
                    //echo "ZaehlervariablenID $zaehlervarID (".$this->ipsOps->path($zaehlervarID).")\n"; 
                    $LeistungID = IPS_GetObjectIDByName ( $identifier , $zaehlervarID );
                    //echo "variableID $variableID (".$this->ipsOps->path($variableID).")\n"; 
                    break;
                default:
                    echo "Warnung, Match aber kein Eintrag für den Typ ".$meter["TYPE"]."\n";  
                    //print_R($meter);  
                    break;
                }
            return ($LeistungID);
            }

        /* Homematic IDs ausgeben, etwas besser abstrahieren
         * die gespeicherte und berechnet Wirkleistung als ID
         * übergabe ein Eintrag aus der $amis->getMeterConfig() oder den Namen "NAME"
         */

        function getRegisterID($meter,$identifier,$debug=false)
            {
            $LeistungID=false;
            if (is_array($meter))           // aus der MeterConfig
                {
                if ($debug) 
                    {
                    echo "getRegisterID mit Parameter Array eines Zählers aufgerufen, look for ";
                    if (isset($meter["Name"])) echo "\"".$meter["Name"]."\" with ";
                    echo "Identifier $identifier\n";                     
                    }
                $LeistungID=$this->getRegisterIDbyConfig($meter,$identifier,$debug);  
                }
            else
                {
                if ($debug) echo "getRegisterID aufgerufen, look for \"$meter\" with Identifier $identifier.\n";
                $config=$this->getMeterConfig();
                foreach ($config as $index => $meterEntry)
                    {
                    if ($debug) echo "   checking \"".$meterEntry["NAME"]."\" mit Type ".$meterEntry["TYPE"]."\n";
                    if ($meterEntry["NAME"]==$meter)
                        {
                        $LeistungID=$this->getRegisterIDbyConfig($meter,$identifier,$debug); 
                        }
                    }
                }
            return($LeistungID);
            }

        /* RegisterID speziell für Wirkleistung
         */

        function getWirkleistungID($meter,$debug=false)
            {
            return ($this->getRegisterID($meter,'Wirkleistung',$debug));
            }

		/* OID übergeben, schauen ob childrens enthalten sind und die richtigen register rausholen, wenn nicht eine Ebene höher gehen
         * wenn die OID nicht vorhanden ist als Ergebnis false zurückgeben
		 */
				
		function getHomematicRegistersfromOID($oid)
			{
			$result=false;
			$cids = @IPS_GetChildrenIDs($oid);
            if ($cids === false) return(false);
            else
                {
                if (sizeof($cids) == 0)		/* vielleicht schon das Energy Register angegeben, mal eine Eben höher schauen */ 
                    {
                    $oid = IPS_GetParent($oid);
                    $cids = IPS_GetChildrenIDs($oid);
                    }					
                foreach($cids as $cid)
                    {
                    $o = IPS_GetObject($cid);
                    if($o['ObjectIdent'] != "")
                        {
                        if ( $o['ObjectName'] == "POWER" ) { $result["HM_LeistungID"]=$o['ObjectID']; }
                        if ( $o['ObjectName'] == "ENERGY_COUNTER" ) { $result["HM_EnergieID"]=$o['ObjectID']; }
                        }
                    }
                return ($result);
                }
			}	

        /* allgemeine Funktion das Energieregister herauszufinden. Unabhängig von einer Hardwaretype
         * die MeterConfig oder eben nur Teile daraus kann auch als Parameter übergeben werden.
         *
         */            
			
		function getRegistersfromOID($oid,$MConfig=array(),$debug=false)
			{
			$result=false;
			if (sizeof($MConfig)==0) 
				{
				//echo "Array ist leer. Default Config nehmen.\n";
				$MConfig=$this->MeterConfig;
				}
            if ($debug) echo "getRegistersfromOID($oid,[".json_encode($MConfig) ."]) aufgerufen.\n";              
            if (is_string($oid))
                {
                //echo "String angegeben.\n"; 
                //$MConfig=$this->MeterConfig;
                //print_r($MConfig);
                foreach ($MConfig as $entry)
                    {
                    if ($debug) echo "           ".$entry["NAME"].",";                        
                    if ( ($entry["NAME"]==$oid) && (isset($entry["OID"])) )
                        {
                        $realOID=$entry["OID"];
                        if ($debug) echo "   ".$entry["NAME"]." gefunden. OID aus Config übernehmen $realOID ".IPS_GetName($realOID)."\n";
                        $result=$this->getRegistersfromOID($realOID,$MConfig,$debug);
                        }
                    }
                if ($debug) echo "\n";                        
                }
            else
                {
                foreach ($MConfig as $identifier => $meter)
                    {
                    if ( isset($meter["OID"]) == true )
                        {
                        if ($meter["OID"]==$oid) 
                            {
                            if ( (strtoupper($meter["TYPE"]))=="HOMEMATIC") 
                                {
                                //echo "Homematic Gerät abgefragt !\n";
                                $cids = @IPS_GetChildrenIDs($oid);
                                if ($cids === false) return(false);             // OID gibt es nicht, darum false 
                                else
                                    {
                                    if (sizeof($cids) == 0)		/* vielleicht schon das Energy Register angegeben, mal eine Eben höher schauen */ 
                                        {
                                        $oid = IPS_GetParent($oid);
                                        $cids = IPS_GetChildrenIDs($oid);
                                        }                            
                                    foreach($cids as $cid)
                                        {
                                        $o = IPS_GetObject($cid);
                                        if($o['ObjectIdent'] != "")
                                            {
                                            if ( $o['ObjectName'] == "POWER" ) { $result["LeistungID"]=$o['ObjectID']; }
                                            if ( $o['ObjectName'] == "ENERGY_COUNTER" ) { $result["EnergieID"]=$o['ObjectID']; }
                                            }
                                        }
                                    if ( (isset($result["LeistungID"])) && ($debug) ) echo "    gefunden : ".$oid." in ".$identifier."  \n";
                                    }    
                                }
                            else            // alle anderen Typen, hier sind die Register im Data/module/Amis/meterName und heissen Wirkenergie und Wirkleistung  
                                {
                                //echo "Irgendein Gerät abgefragt.\n";
                                //$catID=IPS_GetCategoryIDByName($meter["NAME"], $this->CategoryIdData);
                                $catID=IPS_GetVariableIDByName($meter["NAME"], $this->CategoryIdData);
                                $result["EnergieID"]=IPS_GetVariableIDByName("Wirkenergie", $catID);
                                $result["LeistungID"]=IPS_GetVariableIDByName("Wirkleistung", $catID);
                                //echo "    gefunden : ".$oid." in ".$identifier." und Kategorie ".$catID." (".IPS_GetName($catID).") \n";
                                }
                            $result["Identifier"]=$identifier;
                            $result["Name"]=$meter["NAME"];
                            }				
                        }
                    }
                }
			return ($result);				
			}																																																																																																																																																																																																																																																				


        /* configurePort, die AMIS Konfiguration anders anordnen und gleich die Variablen anlegen 
        * Parameter sind die Konfiguration und der Parameter ob die IPS Cutter Funktion verwendet wird (true) oder nicht
        *
        * es wird eine String Variable mit dem NAME aus der Konfig im Modul Data angelegt. Dient als Über-Kategorie.
        */

        public function getPortConfiguration($MeterConfig, $cutter, $debug=false)
            {
            $configPort=array();
            foreach ($MeterConfig as $identifier => $meter)
                {
                $identifierTrim=trim($identifier);
                if ($debug) echo "getPortConfiguration: \"$identifierTrim\"\n";
                $ID = CreateVariableByName($this->CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
                if ($meter["TYPE"]=="Amis")
                    {
                    if ($debug)
                        {
                        echo"-------------------------------------------------------------\n";
                        echo "Create AMIS Variableset for :".$meter["NAME"]." (".$identifierTrim.") \n";
                        }
                    $amismetername=$meter["NAME"];
                    $amisAvailable=true;
                    //echo "Amis Zähler, verfügbare Ports:\n";			
                    
                    $AmisID = CreateVariableByName($ID, "AMIS", 3);
                    $ReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
                    $ReceiveTimeID = CreateVariableByName($AmisID, "ReceiveTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
                    $AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
                        
                    // Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
                    $AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);
                    $AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);

                    // Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
                    $zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
                    $variableID = CreateVariableByName($zaehlerid,'Wirkenergie', 2);
                        
                    //Hier die COM-Port Instanz festlegen
                    $serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
                    foreach ($serialPortID as $num => $serialPort)
                        {
                        //echo "      Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";
                        if (IPS_GetName($serialPort) == $identifierTrim." Serial Port") 
                            { 
                            $com_Port = $serialPort;
                            $regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
                            if (IPS_InstanceExists($regVarID) && ($cutter==false) )
                                {
                                //echo "        Registervariable wenn Cutter nicht aktiv : ".$regVarID."\n";
                                $configPort[$regVarID]["Name"]=$amismetername;	
                                $configPort[$regVarID]["ID"]=$identifierTrim;	
                                $configPort[$regVarID]["Port"]=$serialPort;																				 
                                }
                            }	
                        if (IPS_GetName($serialPort) == $identifierTrim." Bluetooth COM") 
                            { 
                            $com_Port = $serialPort; 
                            $regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
                            if (IPS_InstanceExists($regVarID) && ($cutter==false) )
                                {
                                echo "        Registervariable wenn Cutter nicht aktiv : ".$regVarID."\n";
                                $configPort[$regVarID]["Name"]=$amismetername;	
                                $configPort[$regVarID]["ID"]=$identifierTrim;
                                $configPort[$regVarID]["Port"]=$serialPort;							
                                }					
                            }				
                        }
                    $listCutter=IPS_GetInstanceListByModuleID('{AC6C6E74-C797-40B3-BA82-F135D941D1A2}');
                    foreach ($listCutter as $num => $CutterID)
                        {
                        if (IPS_GetName($CutterID) == $identifierTrim." Cutter")
                            { 
                            //echo "      Cutter ".$num." mit OID ".$CutterID." und Bezeichnung ".IPS_GetName($CutterID)."\n";
                            $result=IPS_getConfiguration($CutterID);
                            //echo "        ".$result."\n";
                            $childrenIDs=IPS_GetInstanceChildrenIDs($CutterID);
                            //print_r($childrenIDs);
                            $parentID=IPS_GetInstanceParentID($CutterID);
                            //echo "         ParentID mit OID ".$parentID." und Bezeichnung ".IPS_GetName($parentID)."\n";
                            $regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$CutterID);
                            if (IPS_InstanceExists($regVarID) && ($cutter==true))
                                {
                                //echo "        Registervariable : ".$regVarID."\n";
                                $configPort[$regVarID]["Name"]=$amismetername;	
                                $configPort[$regVarID]["ID"]=$identifierTrim;
                                $configPort[$regVarID]["Port"]=$CutterID;							
                                }					
                            }  // Registervariable mit Cutter gefunden
                        }	// alle Cutter durchgehen

                            
                    if (isset($com_Port) === true) 
                        { 
                        //echo "\nAMIS Zähler Serial Port auf OID ".$com_Port." definiert.\n"; 
                        }
                    if ( (isset($configPort[$regVarID])) && (isset($meter["REGISTER"])) )
                        {
                        $configPort[$regVarID]["Register"]=$meter["REGISTER"];							
                        if (isset($meter["CALCULATION"]))
                            {
                            $configPort[$regVarID]["Calculate"]=$meter["CALCULATION"];   
                            } 
                        if (isset($meter["CALCULATE"]))
                            {
                            $configPort[$regVarID]["Calculate"]=$meter["CALCULATE"];   
                            }                                                    							
                        }
                    }  // if AMIS Zähler
                if (!file_exists($this->getSystemDir()."\Log_Cutter_".$identifierTrim.".csv"))
                    {
                    $handle=fopen( $this->systemDir."Log_Cutter_".$identifierTrim.".csv", "a");
                    fwrite($handle, date("d.m.y H:i:s").";Quelle;Laenge;Zählerdatensatz\r\n");
                    fclose($handle);
                    }				
                //print_r($meter);
                }
            //echo "Ermittelte Registervariablen als mögliche Quelle für empfangene Daten.\n";	
            return ($configPort);		
            }

        /* systemDir ist private */

        public function getSystemDir()
            {
            return ($this->systemDir);
            }


        /* aus der AMIS Konfiguration rausfinden ob ein AMIS Zähler enthalten ist
        *
        *
        */

        public function getAmisAvailable($MeterConfig)
            {
            $amisAvailable=false;                
            foreach ($MeterConfig as $identifier => $meter)
                {
                if ($meter["TYPE"]=="Amis")
                    {
                    $amisAvailable=true;
                    }  // if AMIS Zähler
                }
            return ($amisAvailable);		
            }


		/*  
		 * Seriellen port programmieren, mit kleinen Problemen bei open port und apply changes 
		 *
		 * identifier  Name des seriellen Ports, zum selber suchen
		 *
		 * 
		 */
						
		function configurePort($identifier,$config)
			{
			//Print_r($config);
			$SerialComPortID = @IPS_GetInstanceIDByName($identifier, 0);
			if(!IPS_InstanceExists($SerialComPortID))
				{
				echo "\nAMIS Serial Port mit Namen \"".$identifier."\" erstellen !";
				$SerialComPortID = IPS_CreateInstance("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // Comport anlegen
				IPS_SetName($SerialComPortID, $identifier);

				IPS_SetProperty($SerialComPortID, "Port", $config[0]); // ComNummer welche dem PC-Interface zugewiesen ist!
				IPS_SetProperty($SerialComPortID, "BaudRate", $config[1]);
				IPS_SetProperty($SerialComPortID, "DataBits", $config[2]);
				IPS_SetProperty($SerialComPortID, "StopBits", $config[3]);
				IPS_SetProperty($SerialComPortID, "Parity", $config[4]);
				sleep(2);
				IPS_SetProperty($SerialComPortID, "Open", true);			  /* macht Fehlermeldung, wenn Port nicht offen */
		 		$result=@IPS_ApplyChanges($SerialComPortID);
				echo "    Comport ist nun aktiviert. \n";
				$SerialComPortID = @IPS_GetInstanceIDByName($identifier." Bluetooth COM", 0);
				}
			else
				{
				$port=strtoupper($config[0]);
				if (IPS_GetProperty($SerialComPortID, 'Port') == $port)
					{
					echo "Com Port vorhanden und richtig programmiert .\n";
					}
				else	
					{
					echo "Com Port falsch programmiert. Ist : ".IPS_GetProperty($SerialComPortID, 'Port')." sollte sein : ".$port."\n";
					IPS_SetProperty($SerialComPortID, 'Port', $port);   //false für aus
					}
				$status=IPS_GetProperty($SerialComPortID, 'Open');
				if ($status==false)  /* nur wenn Port geschlossen ist aufmachen, sollte ein paar sinnlose Fehlermeldungen eliminieren */
					{
					echo "Comport is closed. Open now.\n";
					IPS_SetProperty($SerialComPortID, 'Open', true);   //false für aus
					}					
				$result=@IPS_ApplyChanges($SerialComPortID);
				if ($result==false)
					{
					IPS_DeleteInstance($SerialComPortID);
					echo "Fehler, Instanz geloescht, Installation noch einmal aufrufen. \n";
					}	
				else
					{
					$result=IPS_GetConfiguration($SerialComPortID);
					echo $result;
					}
				}
			return ($result);			
			}


		/************************************************************************************************************************
 		 *
		 * Minuetlich von Momentanwerte abfragen Scipt aufgerufen.				 
 		 * Anforderung Lesebefehl an alle AMIS Zähler schicken
 		 *
 		 * es wird ein String an das entsprechende Com Port geschickt.
		 *
		 *****************************************************************************************************************************/

		function sendReadCommandAmis($meter,$identifier,$command)			/* Lesebefehl an den entsprechenden AMIS Zähler schicken */
			{

			$amisAvailable=false;

			if (strtoupper($meter["TYPE"])=="AMIS")
				{
				$amisAvailable=true;
	 			echo "Werte von : ".$meter["NAME"]."\n";
				$ID = CreateVariableByName($this->CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */				
				$AmisID = CreateVariableByName($ID, "AMIS", 3);
				$SendTimeID = CreateVariableByName($AmisID, "SendTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */				
				$AmisReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
				if (Getvalue($AmisReadMeterID))
					{				
					//Hier die COM-Port Instanz festlegen
					$serialPortIDs = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
					foreach ($serialPortIDs as $num => $serialPort)
						{
						//echo "      Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";
						if (IPS_GetName($serialPort) == $identifier." Serial Port") 
							{ 
							echo "  Comport Serial aktiviert. \n";
							$com_Port = $serialPort;
							$regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
							if (IPS_InstanceExists($regVarID) )
								{
								//echo "        Registervariable : ".$regVarID."\n";
								$configPort[$regVarID]["Name"]=$meter["NAME"];	
								$configPort[$regVarID]["ID"]=$identifier;	
								$configPort[$regVarID]["Port"]=$serialPort;																				 
								}
							//IPSLogger_Dbg(__file__, "Modul AMIS Momemntanwerte abfragen. Comport ".$com_Port." Serial aktiviert.");
							$config = IPS_GetConfiguration($com_Port);
							$remove = array("{", "}", '"');
							$config = str_replace($remove, "", $config);
							$Config = explode (',',$config);
							$AllConfig=array();
							foreach ($Config as $configItem)
								{
								$items=explode (':',$configItem);
								$Allconfig[$items[0]]=$items[1];
								}
							//print_r($Allconfig);
							if ($Allconfig["Open"]==false)
								{
								COMPort_SetOpen($com_Port, true); //false für aus
								//IPS_ApplyChanges($com_Port);
								if (!@IPS_ApplyChanges($com_Port))
									{
									IPSLogger_Dbg(__file__, "Modul AMIS Momentanwerte abfragen. Comport ".$com_Port." Serial Fehler bei Apply Changes: ".$config);
									}
								}
							else
								{
								echo "    Port ist bereits offen.\n";
								}
							COMPort_SetDTR($com_Port , true); /* Wichtig sonst wird der Lesekopf nicht versorgt */
							}	
						if (IPS_GetName($serialPort) == $identifier." Bluetooth COM") 
							{ 
							echo "  Comport Bluetooth aktiviert. \n";
							$com_Port = $serialPort; 
							$regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
							if (IPS_InstanceExists($regVarID) )
								{
								//echo "        Registervariable : ".$regVarID."\n";
								$configPort[$regVarID]["Name"]=$amismetername;	
								$configPort[$regVarID]["ID"]=$identifier;
								$configPort[$regVarID]["Port"]=$serialPort;							
								}
							//IPSLogger_Dbg(__file__, "Modul AMIS Momentanwerte abfragen. Bluetooth Comport Serial aktiviert.");
							COMPort_SendText($com_Port ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */																	
							}
						}  /* ende foreach */				

					$handlelog=fopen( $this->systemDir."Log_Cutter_AMIS.csv","a");
					Setvalue($SendTimeID,time());
					COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
					IPS_Sleep(1550);
					COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> */
					IPS_Sleep(1550);
					if ($command=="F010")
						{
						COMPort_SendText($com_Port ,"\x01\x52\x32\x02F010(*.7.*.*)\x03$");    /* <SOH>R2<STX>F010(*.7.*.*)<ETX> */
						$ausgabewert=date("d.m.y H:i:s").";"."Abfrage R2-F010\n";
						}	
					if ($command=="F001")
						{
						COMPort_SendText($com_Port ,"\x01\x52\x32\x02F001()\x03\x17");    /* <SOH>R2<STX>F001()<ETX> */
						$ausgabewert=date("d.m.y H:i:s").";"."Abfrage R2-F001\n";
						}	
					if ($command=="F009")
						{
						COMPort_SendText($com_Port ,"\x01\x52\x32\x02F009()\x03\x1F");    /* <SOH>R2<STX>F009()<ETX> checksumme*/
						$ausgabewert=date("d.m.y H:i:s").";"."Abfrage R2-F009\n";
						}														
					fwrite($handlelog, $ausgabewert."\r\n");
					fclose($handlelog);
					}
				}
			}

		/************************************************************************************************************************
 		 *
 		 * Alle Homematic Energiesensoren auslesen, ignoriert andere Typen
		 * gibt es gleich darunter als Einzelbefehl
         *
         * keine Tätigkeit wenn Meter TYPE nicht HOMEMATIC ist.
 		 *
 		 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert:
         *   Wirkenergie, Wirkleistung, Offset_Wirkenergie, Homemeatic_Wirkenergie -> alle Float
         *
         * Das Homemeatic_Wirkenergie Register wird mit dem neuen gemessenen Wert verglichen und auf Plausi überprüft
         *   Subtraktion neu-alt ist der Vorschub. Wenn zu gross dann ignorieren, wenn negativ dann Offset anrechnen
		 *
		 *****************************************************************************************************************************/

		function writeEnergyHomematics($MConfig)			/* alle Werte aus der Config ausgeben */
			{
			$homematicAvailable=true;

			foreach ($MConfig as $identifier => $meter)
				{
                echo "   Aufruf von $identifier.\n";
                $ergebnis = $this->writeEnergyHomematic($meter);
                $homematicAvailable = $homematicAvailable && $ergebnis;
				}       // ende foreach
			return ($homematicAvailable);
			}

        /* Abgleich Energieregister mit Devicelist von EvaluateHarwdare
         * devicelist aus EvaluateHarwdare auslesen und mit meterConfig abgleichen:
                IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
                IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
                $hardwareTypeDetect = new Hardware();
                $deviceList = deviceList();
                $deviceListFiltered = $hardwareTypeDetect->getDeviceListFiltered(deviceList(),["TYPECHAN" => "TYPE_METER_POWER"],"Install");    true with Debug, Install hat keinen Einfluss mehr, gibt nur mehr das
         *
         *
         */
		function doublecheckEnergyRegisters($deviceListFiltered,$debug=false)			/* alle Werte aus der Config ausgeben */
			{
            if ($debug>1)
                {
                echo "doublecheckEnergyRegisters mit deviceList aufgerufen.\n";    
                print_r($deviceListFiltered);
                }
            $MeterConfig=$this->MeterConfig;    
            $powerMeter=array();
            $energyMeter=array();
            foreach ($deviceListFiltered as $name => $entry)
                {
                foreach ($entry["Instances"] as $index => $instance)
                    {
                    if ($instance["TYPEDEV"]=="TYPE_METER_POWER") 
                        {
                        $powerMeter[$instance["OID"]]["NAME"]=$instance["NAME"];
                        $powerMeter[$instance["OID"]]["REGISTER_NAME"]=$entry["Channels"][$index]["TYPE_METER_POWER"]["ENERGY"];
                        $childrens=IPS_GetChildrenIDs($instance["OID"]);
                        foreach ($childrens as $children)
                            {
                            if (IPS_GetName($children)==$powerMeter[$instance["OID"]]["REGISTER_NAME"]) 
                                {
                                $powerMeter[$instance["OID"]]["REGISTER_OID"] = $children;
                                $energyMeter[$children]=$instance["NAME"];
                                }
                            }
                        //print_R($childrens); foreach ($childrens as $children) echo IPS_getName($children)."  "; echo "\n";
                        }
                    }
                }
            //print_r($energyMeter);
            $energyMeterAll=$energyMeter;
            $powerMeterAll=$powerMeter;
            $energyMeterName=array();
            if ($debug>1)
                {
                echo "-------------------------------------------------------------\n";
                echo "Analysing the AMIS Meter Configuration:\n";                                   // Die Konfiguration durchgehen, bekannte Register löschen und schauen was am Ende noch da ist
                }
            foreach ($MeterConfig as $identifier => $meter)
                {
                if (strtoupper($meter["TYPE"])=="HOMEMATIC")
                    {
                    $variableID = $this->getWirkenergieID($meter);      // kurze Ausgabe suche nach found as
                    if ($debug>1) echo " ".str_pad($meter["NAME"],35).IPS_GetName($meter["OID"])." Konfig : ".json_encode($meter)."     $variableID ".IPS_GetName($variableID)."\n";
                    $oid=$meter["OID"];
                    if (isset($powerMeter[$meter["OID"]])) 
                        {
                        //print_r($meter);
                        $oid=$powerMeter[$meter["OID"]]["REGISTER_OID"];
                        unset($powerMeter[$meter["OID"]]);
                        }
                    if (isset($energyMeter[$oid])) 
                        {
                        $energyMeterName[$oid]=$meter["NAME"];
                        unset($energyMeter[$oid]);
                        }
                    elseif ($debug>1) echo "   --> unknown ".$meter["OID"]." ".IPS_GetName($meter["OID"])."\n"; 

                    }
                }
            //echo"-------------------------------------------------------------\n";
            //print_r($energyMeter);
            //echo"-------------------------------------------------------------\n";
            echo "doublecheckEnergyRegisters, all Register marked with *** are not in the AMIS configuration.\n";
            foreach ($energyMeterAll as $oid => $register)
                {
                $props=IPS_GetVariable($oid);
                if (isset($energyMeter[$oid])) echo " *** $oid : ";
                else                           echo "     $oid : ";
                echo str_pad(IPS_GetName(IPS_GetParent($oid)),50).str_pad(GetValueIfFormatted($oid),20," ",STR_PAD_LEFT)."   ".date("d.m.Y H:i:s",$props["VariableChanged"])."      ";
                if (isset($energyMeterName[$oid])) echo $energyMeterName[$oid]."\n";
                else echo "\n";            
                }
            //echo"-------------------------------------------------------------\n";
            } 


		/************************************************************************************************************************
 		 *
		 * Minuetlich wird ein Scipt aufgerufen um die Momentanwerte abfragen. Dieses Script kommt alle 15 Minuten dran.
         * es wird der Einzeleintrag der Konfiguration übergeben, also ohne Identifier. Die Einträge in der Konfiguration durchgehen und auf Brauchbarkeit untersuchen.
 		 * Homematic Energiesensoren auslesen, ignoriert andere Typen als Einzelbefehl.
         * Übergeben wird entweder die Homematic Instanz OID oder die Einzelwerte HM_EnergieID und HM_LeistungID
         *  wenn die OID übergeben wird, werden die HM Register mit getHomematicRegistersfromOID erzeugt
         * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
         *      Wirkenergie
         *      Wirkleistung
         *      Offset_Wirkenergie
         *      Homematic_Wirkenergie
 		 *      ConfigReading               erfasst eine Änderung der OID != $HMenergieID
         * die Speicherung erfolgt nur wenn debug false ist !
 		 * 
         * AC_GetLoggedValues mit 15 Min Werte für Homematic nur für Debugzwecke
		 *
		 *****************************************************************************************************************************/

		function writeEnergyHomematic($meter, $debug=false)		/* nur einen Wert aus der Config ausgeben */
			{
			$homematicAvailable=false;
			if (strtoupper($meter["TYPE"])=="HOMEMATIC")
				{
                if ($debug) echo "   writeEnergyHomematic: aufgerufen mit ".json_encode($meter)." mit Werten von : ".$meter["NAME"]."\n";
				$homematicAvailable=true;
				$ID = CreateVariableByName($this->CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

				$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String , prüft ob die Variable schon vorhanden ist */
				$LeistungID = IPS_GetObjectIDByName('Wirkleistung', $ID);   /* 0 Boolean 1 Integer 2 Float 3 String , prüft ob die Variable schon vorhanden ist */
				$LeistungTagID = IPS_GetObjectIDByName('Wirkleistung (Tag)', $ID);   /* 0 Boolean 1 Integer 2 Float 3 String , prüft ob die Variable schon vorhanden ist */
				$OffsetID = CreateVariableByName($ID, 'Offset_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String , prüft ob die Variable schon vorhanden ist */
				$Homematic_WirkenergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String , prüft ob die Variable schon vorhanden ist */
            
                /* Config und History Logging vorbereiten */
                $ConfigID = CreateVariableByName($ID, 'ConfigReading', 3);
                $configuration = json_decode(GetValue($ConfigID),true);
                if ( ($configuration===NULL) || ($configuration==0) ) { $configuration = array(); echo "Configuration neu angelegt.\n"; }

                /* Meter Variablen bestimmen */
                $error=false;
                if ( isset($meter["OID"]) == true )
                    {
                    $result  = $this->getHomematicRegistersfromOID($meter["OID"]);
                    if ($result===false) $error=true;
                    else
                        {
                        $HMenergieID  = $result["HM_EnergieID"];
                        $HMleistungID = $result["HM_LeistungID"];							
                        echo "     OID der Homematic Register selbst bestimmt : Energie : ".$HMenergieID." Leistung : ".$HMleistungID."\n";
                        }
                    }
                else
                    {
                    $HMenergieID  = $meter["HM_EnergieID"];         /* es gibt die Möglichkeit statt der OID, das wäre entweder das Energy Register oder die Instanz auch die entsprechenden Homematic Register direkt anzugeben */
                    $HMleistungID = $meter["HM_LeistungID"];
                    }                

                /* Energievorschub bestimmen, plausibilisieren und schreiben */
                if ( ($HMenergieID != 0) && ($HMleistungID != 0) && !$error)
                    {

                    /* Config und History Logging machen */
                    $changeDone=false;
                    $configValue=json_encode($configuration);                    
                    if (isset($configuration["OID"])===false) 
                        {
                        $configuration["OID"] = $HMenergieID;           // new, erster Wert für Energieregister
                        echo "NewDone: ".$configuration["OID"]." = $HMenergieID. \n";
                        $configValue=json_encode($configuration);
                        SetValue($ConfigID, $configValue);                             
                        }
                    else 
                        {
                        if ($configuration["OID"] != $HMenergieID)
                            {
                            echo "ChangeDone: ".$configuration["OID"]." != $HMenergieID. Old value fetched from ".GetValue($ConfigID)."\n";
                            print_r($configuration);
                            $changeDone=true;
                            $change = date("d.m.y H:i:s")." change OID from ".$configuration["OID"]." to $HMenergieID;";
                            if (isset($configuration["HISTORY"]) === false) $configuration["HISTORY"]=$change;
                            else $configuration["HISTORY"].=$change;

                            $configuration["OID"]=$HMenergieID;
                            //print_r($configuration);
                            $configValue=json_encode($configuration);
                            SetValue($ConfigID, $configValue);                             
                            }
                        }

                    /* Werte schreiben */
                    $logAvailable=AC_GetLoggingStatus($this->archiveHandlerID, $EnergieID);
                    if ($logAvailable) 
                        {
                        $powerDay=$this->getArchivePower($EnergieID,$debug);
                        SetValue($LeistungTagID,$powerDay);
                            /*
                            $jetzt=time();
                            $endtime=$jetzt; $starttime=$endtime-60*60*24-7*60;
                            if ($debug) echo "Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime).":\n";

                            $werte = AC_GetLoggedValues($this->archiveHandlerID, $EnergieID, $starttime, $endtime, 0);
                            $first = $werte[array_key_first($werte)];    
                            $last = $werte[array_key_last($werte)];
                            $wert=$first["Value"]-$last["Value"];
                            $zeit=$first["TimeStamp"]-$last["TimeStamp"];          
                            $leist=$wert/$zeit*60*60;
                            echo "   $wert / $zeit = $leist W,   First   ".$first["Value"]."  ".date("D d.m H:i",$first["TimeStamp"])."   Last   ".$last["Value"]."  ".date("D d.m H:i",$last["TimeStamp"])."  \n";
                            //print_R($werte);
                            $vorigertag=date("d.m.Y",$jetzt);	// einen Tag ausblenden 
                            $laufend=1; $alterWert=0; $alteZeit=$jetzt;
                            foreach($werte as $wert)
                                {
                                $zeit=$wert['TimeStamp']-60;
                                $leistung=($alterWert-$wert['Value'])/($alteZeit-$wert['TimeStamp'])*60*60*1000;            // Umrechnung auf Stunde und W
                                if ($alterWert) echo "    ".date("D d.m H:i", $alteZeit)."   ".nf($alterWert,"kWh")."    ".str_pad(($alteZeit-$wert['TimeStamp']),8)."   ".nf($alterWert-$wert['Value'],"kWh")."  ".nf($leistung,"W")."\n";
                                //echo "    ".date("D d.m H:i", $wert['TimeStamp'])."   ".$wert['Value']."    ".$wert['Duration']."\n";
                                if (date("d.m.Y", $zeit)!=$vorigertag)
                                    {
                                    //$zeile[$metercount]["Datum"][$laufend] = date("d.m", $zeit);
                                    //$zeile[$metercount]["Wochentag"][$laufend] = date("D  ", $zeit);
                                    echo "  Werte : ".date("D d.m H:i", $zeit)." ".number_format($wert['Value'], 2, ",", "" ) ." kWh\n";
                                    //$zeile[$metercount]["Energie"][$laufend] = number_format($wert['Value'], 3, ",", "" );
                                    if ($laufend>1) 
                                        {
                                        //$zeile[$metercount]["EnergieVS"][$altesDatum] = number_format(($alterWert-$wert['Value']), 2, ",", "" );
                                        }
                                    
                                    $laufend+=1;
                                    $altesDatum=date("d.m", $zeit);
                                    //echo "Voriger Tag :".date("d.m.Y",$zeit)."\n";
                                    }
                                $alterWert=$wert['Value']; $alteZeit=$wert['TimeStamp'];
                                $vorigertag=date("d.m.Y",$zeit);
                                }
                            } */
                        } 
                    $energie=GetValue($HMenergieID)/1000; // Homematic Wert ist in Wh, in kWh umrechnen 
                    $leistung=GetValue($HMleistungID);
                    $energievorschub=$energie-GetValue($Homematic_WirkenergieID);
                    if ($changeDone)                // das Homemeatic Register hat sich geändert. Erster Wert ist Offset. Nächsten Wert erst für den Vorschub verwenden 
                        {
                        echo "   >>Info, das beobachtete Energieregister hat sich geändert. Ersten Wert als Basis nehmen. Vorschub bleibt 0.\n";
                        $energievorschub=0;   
                        }                      
                    elseif ($energievorschub<0)       // Energieregister in der Homematic Komponente durch Stromausfall zurückgesetzt 
                        {
                        echo "   >>Fehler, Energievorschub kleiner 0, ist $energievorschub. Den aktuellen Wert des Spiegelregisters in Data ($Homematic_WirkergieID) auf den Offset aufaddieren : ".GetValue($Homematic_WirkergieID)."kWh.\n";
                        $offset = GetValue($OffsetID);                        
                        $offset+=GetValue($Homematic_WirkenergieID); // als Offset alten bekannten Wert dazu addieren 
                        $energievorschub=$energie;
                        SetValue($OffsetID,$offset);
                        }
                    elseif ($energievorschub>10)       // verbrauchte Energie in einem 15 Minutenintervall ist realistisch maximal 2 kWh, 10kWh abfragen   
                        {   // Unplausibilitaet ebenfalls behandeln 
                        echo "   >>Fehler, Energievorschub groesser 10, ist $energievorschub. Diese Änderung ignorieren.\n";
                        $energievorschub=0;
                        }		

                    $energie_neu=GetValue($EnergieID)+$energievorschub;
                    if ($debug==false)
                        {
                        SetValue($Homematic_WirkenergieID,$energie);          // Spiegelregister für Homematic Energie Register 
                        SetValue($EnergieID,$energie_neu);
                        SetValue($LeistungID,$energievorschub*4);
                        }
                    else
                        {
                        $power=GetValue($HMleistungID);    
                        $cost=$power*24/1000*365*0.4;
                        echo "     Werte aus der Homematic : ".nf($energie,"kWh")." ".nf($power,"W")."    ~".nf($cost,"€")."/year if continous\n";
                        echo "     Energievorschub aktuell : ".nf($energievorschub,"kWh")."\n";
                        echo "     Energiezählerstand      : ".nf($energie_neu,"kWh")." Leistung : ".nf(GetValue($LeistungID),"kW")." \n";
                        echo "     Offset Energie          : ".nf(GetValue($OffsetID),"kWh")." \n";
                        echo "     Configuration           : $configValue \n\n";
                        }
                    }
                else echo "    Fehler, IDs der Energieregister konnte nicht bestimmt werden.\n";                    
                }   
			return ($homematicAvailable);
			}
			

		/************************************************************************************************************************
 		 *
		 * Minuetlich von Momentanwerte abfragen Scipt aufgerufen.
 		 * Alle Energiewerte die als Register definiert sind auslesen, ignoriert andere Typen
 		 *
 		 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
		 *
		 *****************************************************************************************************************************/
		 
		function writeEnergyRegister($meter, $debug=false)		/* nur einen Wert aus der Config ausgeben */
			{
			$registerAvailable=false;

			if (strtoupper($meter["TYPE"])=="REGISTER")
				{
				$registerAvailable=true;
				if ($debug) echo "writeEnergyRegister, Werte von : ".$meter["NAME"]."\n";
			      
				$ID = CreateVariableByName($this->CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

				$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

				if ( isset($meter["OID"]) == true )
					{
                    if ( isset($meter["OIDTYPE"]) == true ) $regType=strtoupper($meter["OIDTYPE"]);
                    elseif ( isset($meter["OIDType"]) == true ) $regType=strtoupper($meter["OIDType"]);             // alternative Schreibweise
                    else $regType="kWh"; 
					$HMenergieID = $meter["OID"];
					echo "  OID des Register für die Messung aus der Konfiguration, Type ist $regType";
                    echo " Energie OID: ".$HMenergieID." (".IPS_GetName($HMenergieID).") Leistung OID: nicht bekannt\n";
 
                    $leistungStore=false;
                    switch (strtoupper($regType))
                        {
                        case "KWH":
        	    			$energie=GetValue($HMenergieID);            /* Register Wert ist in kWh, nicht umrechnen */
                            break;
                        case "WH":
    				        $energie=GetValue($HMenergieID)/1000;       /* Register Wert ist in Wh, in kWh umrechnen */
                            break;
                        case "KW":
    				        $leistung=GetValue($HMenergieID);           /* Register Wert ist in kW  */
                            $lastAChanged=IPS_GetVariable($HMenergieID)["VariableUpdated"];
                            $timeAChanged=time()-$lastAChanged;
                            echo "   Wert in A, letzte Änderung des Wertes war ".date("d.m.Y H:i:s",$lastAChanged)."  vor $timeAChanged Sekunden\n";
                            $energie=$leistung*$timeAChanged/3600;
                            echo "   Energie  $energie kWh  Leistung $leistung kW \n";
                            $leistungStore=true;
                            break;
                        case "A":
    				        $leistung=GetValue($HMenergieID)*230/1000; /* Homematic Wert ist in A in W umrechnen, in kW umrechnen */
                            $lastAChanged=IPS_GetVariable($HMenergieID)["VariableUpdated"];
                            $timeAChanged=time()-$lastAChanged;
                            echo "   Wert in A, letzte Änderung des Wertes war ".date("d.m.Y H:i:s",$lastAChanged)."  vor $timeAChanged Sekunden\n";
                            $energie=$leistung*$timeAChanged/3600;
                            echo "   Energie  $energie kWh  Leistung $leistung kW \n";
                            $leistungStore=true;
                            break;
                        default:
        	    			$energie=GetValue($HMenergieID); /* Homematic Wert ist in kWh, nicht umrechnen */
                            break;
                        }
                    if ($leistungStore)
                        {
                        SetValue($EnergieID,GetValue($EnergieID)+$energie);
                        SetValue($LeistungID,$leistung);
                        }
                    else
                        {
                        //print_r(IPS_GetObject($EnergieID));
                        //print_r(IPS_GetVariable($EnergieID));
                        $lastChanged=IPS_GetVariable($EnergieID)["VariableUpdated"];
                        $timeChanged=time()-$lastChanged;    // in Sekunden
                        //echo "    Last changed ".date("d.m.Y H:i:s",IPS_GetVariable($EnergieID)["VariableChanged"])."   Wert  :  ".GetValue($EnergieID)." \n";
                        echo "  Last updated ".date("d.m.Y H:i:s",$lastChanged)." seit $timeChanged Sekunden,  Wert  :  ".GetValue($EnergieID)." \n";
                        $leistung = (($energie-GetValue($EnergieID))/$timeChanged*3600);
                        echo "  Umgerechnete Werte aus dem Register : ".$energie." kWh abgeleitet von ".GetValue($HMenergieID)." $regType, vorher war ".GetValue($EnergieID)." $regType. Unterschied ".(($energie-GetValue($EnergieID))*1000)." Wh ,  Leistung : ".($leistung*1000)." W\n";
                        if ($timeChanged>880)   // nur alle 15 Minuten schreiben
                            {
                            $leistung=($energie-GetValue($EnergieID))*4;
                            SetValue($EnergieID,$energie);
                            SetValue($LeistungID,$leistung);
                            }
                        }
                    }
				}
			return ($registerAvailable);
			}

		/************************************************************************************************************************
 		 *
		 * Script MomentanwerteAbfragen wird minuetlich aufgerufen. Diese Routine kommt alle 15 Minuten dran.
 		 * Nur die SUMMEN Energiewerte (meter["TYPE"]=="SUMME") bearbeiten, ignoriert andere Meter Typen
 		 *
 		 * es wird ein String mit dem Namen $meter["NAME"] als Kategorie angelegt und darunter die Variablen Wirkenergie und Wirkleistung als Float gespeichert
         * es wird nur berechnet wenn $meter["Calculate"] die Werte die für die Berechnung als Namen mit Komma getrennt angelegt sind
         *
		 *
		 *****************************************************************************************************************************/
		 
		function writeEnergySumme($meter,$debug=false)		/* nur einen Wert aus der Config ausgeben */
			{
			$registerAvailable=false;
			$energie=0;
			$leistung=0;

			if (strtoupper($meter["TYPE"])=="SUMME")
				{
				$registerAvailable=true;
				if ($debug) echo "   writeEnergySumme, Werte von : ".$meter["NAME"]."\n";
			      
				$ID = CreateVariableByName($this->CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

				$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

				if ( isset($meter["Calculate"]) == true )
					{
					$calculate = explode(",",$meter["Calculate"]);                  
					if ($debug) echo "     dazu werden die folgenden Register zusammengezählt: ".$meter["Calculate"]."\n";
					//print_r($calculate);
                    $e=0;
					foreach ($calculate as $oid)
						{
                        $e++;       // Zeilenindex
                        if ($debug) echo "        $e bearbeite $oid:\n";                          
						$result=$this->getRegistersfromOID($oid,[],$debug);           // das sind die Quellregister (von Homematic, Register, AMSI Zähler etc.), vor der Verarbeitung

                        //print_r($result);
                        /* nicht die Source sondern die Target Register zusammenzählen 
						$energie+=GetValue($result["EnergieID"]);
						$leistung+=GetValue($result["LeistungID"]);
                        */
                        if ($result !== false)
                            {
                            $category=IPS_GetObjectIdByName($result["Name"],$this->CategoryIdData);
                            if ($category !== false)
                                {
                                $wirkEnergieId=IPS_GetObjectIdByName("Wirkenergie",$category);
                                $wirkLeistungId=IPS_GetObjectIdByName("Wirkleistung",$category);
                                if ( ($wirkEnergieId !== false) && ($wirkLeistungId !== false) )
                                    {
                                    echo "   $e $wirkEnergieId : ".nf(GetValue($wirkEnergieId),"kWh")." $wirkLeistungId : ".nf(GetValue($wirkLeistungId),"kW");
                                    $energie+=GetValue($wirkEnergieId);
                                    $leistung+=GetValue($wirkLeistungId);
                                    echo " ergibt Summe Energie ($EnergieID): ".nf($energie,"kWh")." Summe Leistung ($LeistungID): ".nf($leistung,"kW")."\n"; 
                                    }
                                }
                            }
                        else echo "Warning, Variable $oid to Calculate Sum not found. Looked for [Name] not Key.\n";
						}
						
					}
                $lastChanged=IPS_GetVariable($EnergieID)["VariableUpdated"];
                $timeChanged=time()-$lastChanged;    // in Sekunden
                echo "  Last updated ".date("d.m.Y H:i:s",$lastChanged)." seit $timeChanged Sekunden,  Wert  :  ".nf(GetValue($EnergieID),"kWh")." \n";


				$leistungVergleich=($energie-GetValue($EnergieID))/$timeChanged*15*60*4;
                if ($timeChanged>880 )      // 15 Minuten sind 900 Sekunden
                    {
				    SetValue($EnergieID,$energie);
				    SetValue($LeistungID,$leistung);
				    echo "  Neue Werte : ".nf($energie,"kWh")."  ".nf($leistung,"kW")."    Zum Vergleich : ".nf($leistungVergleich,"kW")."\n"; 
                    }
                else echo "  Keine Update, Zeitspanne zu kurz. Neue Werte : ".nf($energie,"kWh")."  ".nf($leistung,"kW")."    Zum Vergleich : ".nf($leistungVergleich,"kW")."\n";
				}
			return ($registerAvailable);
			}

		/************************************************************************************************************************
 		 *
 		 * Alle AMIS Energiesensoren auslesen, ignoriert andere Typen
 		 *
 		 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
		 *
		 *****************************************************************************************************************************/		

		function writeEnergyAmis($meter)
			{
			$amisAvailable=false;

			if (strtoupper($meter["TYPE"])=="AMIS")
				{
				$amisAvailable=true;
	 			echo "Werte von : ".$meter["NAME"]."\n";
	
				$ID = CreateVariableByName($this->CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

	    		$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	    		$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	    		$OffsetID = CreateVariableByName($ID, 'Offset_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	    		$Homematic_WirkergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

	    		if ( isset($meter["OID"]) == true )
					{
					$OID  = $meter["OID"];
					$cids = IPS_GetChildrenIDs($OID);
					foreach($cids as $cid)
			   			{
			   	 		$o = IPS_GetObject($cid);
			    		if($o['ObjectIdent'] != "")
			        		{
			       		 	if ( $o['ObjectName'] == "POWER" ) { $HMleistungID=$o['ObjectID']; }
			        		if ( $o['ObjectName'] == "ENERGY_COUNTER" ) { $HMenergieID=$o['ObjectID']; }
			       			}
			   			}
		    		echo "  OID der Homematic Register selbst bestimmt : Energie : ".$HMenergieID." Leistung : ".$HMleistungID."\n";
					}
				else
					{
					$HMenergieID  = $meter["HM_EnergieID"];
					$HMleistungID = $meter["HM_LeistungID"];
					}
	    		$energie=GetValue($HMenergieID)/1000; /* Homematic Wert ist in Wh, in kWh umrechnen */
	    		$leistung=GetValue($HMleistungID);
	    		$energievorschub=$energie-GetValue($Homematic_WirkergieID);
	    		if ($energievorschub<0)       /* Energieregister in der Homematic Komponente durch Stromausfall zurückgesetzt */
	        		{
	        		$offset+=GetValue($Homematic_WirkergieID); /* als Offset alten bekannten Wert dazu addieren */
					$energievorschub=$energie;
	        		SetValue($OffsetID,$offset);
	        		}
				SetValue($Homematic_WirkergieID,$energie);
				$energie_neu=GetValue($EnergieID)+$energievorschub;
				SetValue($EnergieID,$energie_neu);
				SetValue($LeistungID,$energievorschub*4);
	    		echo "  Werte aus der Homematic : ".$energie." kWh  ".GetValue($HMleistungID)." W\n";
	    		echo "  Energievorschub aktuell : ".$energievorschub." kWh\n";
	    		echo "  Energiezählerstand      : ".$energie_neu." kWh Leistung : ".GetValue($LeistungID)." kW \n\n";
				}
			return ($amisAvailable);
			}
			
		/************************************************************************************************************************
 		 *
         * die Tabelle "Stromzählerstand aktuell Energiewert in kWh:" als html schreiben
 		 * Alle Homematic Energiesensoren der letzten Woche als Wert auslesen, ignoriert andere Typen
		 * gibt die Werte wenn nicht anders gewünscht mit einer html Formatierung aus
		 *
		 * die html Formatierung wird als <style> mit Klassen und mehreren <div> tags aufgebaut. 
		 * <html> und <body> tags werden nicht erstellt
         * Formatierung so gewählt das beide Tabellen txt und html gemeinsam erstellt werden
         *
         * nur verwendet für die Ausgeben die hinter Execute stehen
		 * verwendet AC_GetLoggedValues
		 *****************************************************************************************************************************/

		function writeEnergyRegistertoString($MConfig,$html=true,$debug=false)			/* alle Werte aus der Config ausgeben */
			{
            if ($debug) echo "writeEnergyRegistertoString aufgerufen mit Konfig ".json_encode($MConfig)."\n";
			if ($html==true) 
				{
                if ($debug) echo "   Ausgabe des Strings als html.\n";
				$style="<style> .zeile { font-family:Arial,'Courier New'; font-size: 0.8 em; white-space:pre-wrap;   }
				                .rotetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid red;  }
								.blauetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid blue; }  
								.gruenetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid green;     }   </style> \n\n";
				$starttable="<table style=\"border-collapse=collapse;\">"; $endtable="</table>";
				$startcell="<td style=\"border:1px solid black\">"; $endcell="</td>";
				$startcellheader="<th colspan=\"11\" style=\"border:1px solid black;\" >";$endcellheader="</th>";
				$startparagraph="<tr>"; $endparagraph="</tr>";  /* Paragraph oder Table Line */
				$output="<div class=\"rotetabelle\"> \n";   /* Umschalten auf Courier Font */
				$outputEnergiewerte="<div class=\"gruenetabelle\"> \n";   /* Umschalten auf Courier Font */
				$outputTabelle="<div class=\"blauetabelle\"> \n";   /* Umschalten auf Courier Font */
				$newline="<BR>\n";
				if ($debug) echo "<p class=\"zeile\"> \n";   /* Umschalten auf Arial Font */				
				}
			else
				{
				$style="";				
				$starttable=""; $endtable="";
				$startcell=""; $endcell="";					/* ein Tabelleneintrag */
				$startcellheader=""; $endcellheader="";
				$startparagraph=""; $endparagraph="\n";		/* eine Tabellenzeile */				
				$output="";
				$outputEnergiewerte="";
				$outputTabelle="";				
				$newline="\n";
				}
					
			$outputEnergiewerte.=$starttable.$startparagraph.$startcellheader."Stromzählerstand aktuell Energiewert in kWh:".$endcellheader.$endparagraph;

			/* Umbauen auf zuerst einlesen der Zählerwerte und danach generieren der entsprechenden Tabellen !*/
			//$zeile=writeEnergyRegistertoArray($MConfig);
			//for ($metercount=0;$metercount<size() ...

			$metercount=0;
			$tabwidth0=24;

            /* Energiewerte der letzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr */
            $jetzt=time();
            $endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
            $starttime=$endtime-60*60*24*10;

			foreach ($MConfig as $meter)
				{
                /* es werden nur die Homematic Zähler ausgelesen */
				if (strtoupper($meter["TYPE"])=="HOMEMATIC")
					{
                    if ($debug)
                        {
					    echo "-----------------------------".$newline;
					    echo "Werte von : ".$meter["NAME"].$newline;
                        }
					$ID = CreateVariableByName($this->CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

					$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
					$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
					$OffsetID = CreateVariableByName($ID, 'Offset_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
					$Homematic_WirkergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
					if ( isset($meter["OID"]) == true )
						{
						$OID  = $meter["OID"];
						$cids = IPS_GetChildrenIDs($OID);
						if (sizeof($cids) == 0) 
							{
							$OID = IPS_GetParent($OID);
							$cids = IPS_GetChildrenIDs($OID);
							}
						//echo "OID der passenden Homematic Register selbst bestimmen. Wir sind auf ".$OID." (".IPS_GetName($OID).")\n";
						//print_r($cids);
						foreach($cids as $cid)
							{
			      			$o = IPS_GetObject($cid);
			      			if($o['ObjectIdent'] != "")
			         			{
			         			if ( $o['ObjectName'] == "POWER" ) { $HMleistungID=$o['ObjectID']; }
			         			if ( $o['ObjectName'] == "ENERGY_COUNTER" ) { $HMenergieID=$o['ObjectID']; }
			        			}
			    			}
		      			//echo "  OID der Homematic Register selbst bestimmt : Energie : ".$HMenergieID." Leistung : ".$HMleistungID."\n";
						}
					else
						{
						$HMenergieID  = $meter["HM_EnergieID"];
						$HMleistungID = $meter["HM_LeistungID"];
						}
						
					$energievorschub=GetValue($LeistungID);
					$energie=GetValue($Homematic_WirkergieID);
                    if ($debug)
                        {
                        echo "  Werte aus der Homematic : aktuelle Energie : ".number_format($energie, 2, ",", "" )." kWh  aktuelle Leistung : ".number_format(GetValue($HMleistungID), 2, ",", "" )." W".$newline;
                        echo "  Energievorschub aktuell : ".number_format($energievorschub, 2, ",", "" )." kWh".$newline;
                        echo "  Energiezählerstand      : Energie ".number_format(GetValue($EnergieID), 2, ",", "" )." kWh Leistung : ".number_format(GetValue($LeistungID), 2, ",", "" )." kW".$newline;
                        }
					$vorigertag=date("d.m.Y",$jetzt);	/* einen Tag ausblenden */
                    $logAvailable=AC_GetLoggingStatus($this->archiveHandlerID, $EnergieID);
                    if ($logAvailable)
                        {
                        if ($debug) echo "Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime).":".$newline;

                        $werte = AC_GetLoggedValues($this->archiveHandlerID, $EnergieID, $starttime, $endtime, 0);
                        $zeile[$metercount] = array("Wochentag" => array("Wochentag",0,1,2), "Datum" => array("Datum",0,1,2), "Energie" => array("Energie",0,1,2) );
                        $laufend=1; $alterWert=0; 
                        foreach($werte as $wert)
                            {
                            $zeit=$wert['TimeStamp']-60;
                            //echo "    ".date("D d.m H:i", $wert['TimeStamp'])."   ".$wert['Value']."    ".$wert['Duration']."\n";
                            if (date("d.m.Y", $zeit)!=$vorigertag)
                                {
                                $zeile[$metercount]["Datum"][$laufend] = date("d.m", $zeit);
                                $zeile[$metercount]["Wochentag"][$laufend] = date("D  ", $zeit);
                                if ($debug) echo "  Werte : ".date("D d.m H:i", $zeit)." ".number_format($wert['Value'], 2, ",", "" ) ." kWh".$newline;
                                $zeile[$metercount]["Energie"][$laufend] = number_format($wert['Value'], 3, ",", "" );
                                if ($laufend>1) 
                                    {
                                    $zeile[$metercount]["EnergieVS"][$altesDatum] = number_format(($alterWert-$wert['Value']), 2, ",", "" );
                                    }
                                
                                $laufend+=1;
                                $alterWert=$wert['Value']; $altesDatum=date("d.m", $zeit);
                                //echo "Voriger Tag :".date("d.m.Y",$zeit)."\n";
                                }
                            $vorigertag=date("d.m.Y",$zeit);
                            }
                        $anzahl2=$laufend-1;
                        $ergebnis_datum=""; $ergebnis_wochentag=""; $ergebnis_tabelle="";
                        $zeile[$metercount]["Wochentag"][0]=$meter["NAME"];
                        $laufend=0;
                        while ($laufend<=$anzahl2)
                            {
                            if ($laufend==0) 
                                {
                                $tabwidth=strlen($zeile[$metercount]["Wochentag"][0])+8;
                                if ($debug) echo "Es sind ".($anzahl2+1)." Eintraege vorhanden. Breite erster Spalte ist : ".$tabwidth.$newline;
                                $ergebnis_wochentag.=$startcell.substr(("Energie in kWh                            "),0,$tabwidth).$endcell;
                                $ergebnis_datum.=$startcell.substr(($zeile[$metercount]["Datum"][$laufend]."                             "),0,$tabwidth).$endcell;
                                $ergebnis_tabelle.=$startcell.substr(($zeile[$metercount]["Wochentag"][$laufend]."                          "),0,$tabwidth).$endcell;
                                }
                            else
                                {
                                $tabwidth=12;
                                $ergebnis_wochentag.=$startcell.substr(($zeile[$metercount]["Wochentag"][$laufend]."                            "),0,$tabwidth).$endcell;
                                $ergebnis_datum.=$startcell.substr(($zeile[$metercount]["Datum"][$laufend]."                             "),0,$tabwidth).$endcell;
                                $ergebnis_tabelle.=$startcell.substr(($zeile[$metercount]["Energie"][$laufend]."                          "),0,$tabwidth).$endcell;
                                }	
                            $laufend+=1;
                            //echo $ergebnis_tabelle."\n";
                            }
                        $output.=$starttable.$startparagraph.$startcell."Stromverbrauch der letzten Tage von ".$meter["NAME"]." :".$newline.$newline;
                        $output.="Energiewert aktuell ".$zeile[$metercount]["Energie"][1].$newline.$newline.$endcell.$endparagraph.$endtable;
                        $output.=$starttable.$startparagraph.$ergebnis_wochentag.$newline.$endparagraph.$startparagraph.$ergebnis_datum.$newline.$endparagraph.$startparagraph.$ergebnis_tabelle.$newline.$newline.$endparagraph.$endtable;						

                        $outputEnergiewerte.=$startparagraph.$startcell.substr($meter["NAME"]."                           ",0,$tabwidth0).$endcell.$startcell.$zeile[$metercount]["Energie"][1].$endcell.$endparagraph;
                        $metercount+=1;
                        }
                    elseif ($debug)  echo "****Fehler, Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime)." nicht verfügbar\n";
					}
				} /* ende foreach Meter Entry */

			/* Ausgabe aller Enrgievorschuebe in einer gemeinsamen Tabelle 
			 * zuerst Überschrift, Einleitung der Tabelle machen, Endergebnis in outputTabelle zusammenstellen
			 * Tabellenspalten für Ausgabe als plaintext in Courier auf gleiche Länge ablengen/schneiden
			 * keine automatische Erfassung des laengsten Eintrages 24,8,8,8,8,8
			 */
			$tabwidth=6; 			
			$zeile0=$startcell."Energievorschub in kWh  ".$endcell;
			$zeile1=$startcell."Datum                   ".$endcell;
			$zeit=$endtime-24*60*60;
			for ($i=1;$i<10;$i++)
				{
				$zeile0.=$startcell." ".substr((date("D", $zeit)."                            "),0,$tabwidth-1).$endcell;
				$zeile1.=$startcell." ".substr((date("d.m", $zeit)."                            "),0,$tabwidth-1).$endcell;
				$zeit-=24*60*60;
				}

			/* ganze Tabelle zusammenbauen, Zähler fürZähler, Zeile 0 und 1 übernehmen */

			$outputTabelle.=$starttable.$startparagraph.$startcellheader."Stromverbrauch der letzten Tage als Änderung der Energiewerte pro Tag:".$endcellheader.$endparagraph;
			$outputTabelle.=$startparagraph.$zeile0.$endparagraph.$startparagraph.$zeile1.$endparagraph;
			if ($debug) echo "Gesamt Tabelle aufbauen. Anzahl Zähler ist $metercount. \n";
			for ($line=0;$line<($metercount);$line++)
				{
				$outputTabelle.=$startparagraph.$startcell.substr($zeile[$line]["Wochentag"][0]."                               ",0,$tabwidth0).$endcell;	/* neue Zeile pro Zähler */ 
				$zeit=$endtime-24*60*60;
				for ($i=1;$i<10;$i++)
					{				
					if ( isset($zeile[$line]["EnergieVS"][date("d.m", $zeit)])==true )
						{
						$outputTabelle.=$startcell." ".substr($zeile[$line]["EnergieVS"][date("d.m", $zeit)]."        ",0,$tabwidth-1).$endcell;
						}
					else	/* wenn es keinen Wert gibt leere Zelle drucken*/
						{
						if ($debug) echo "   Zählerwert für ".$zeile[$line]["Wochentag"][0]." vom Datum ".date("d.m", $zeit)." fehlt.\n  ";
						$outputTabelle.=$startcell.substr("              ",0,$tabwidth).$endcell;
						}
					$zeit-=24*60*60;	/* naechster Tag */			
					}
				$outputTabelle.=$endparagraph; 					
				}	

			//if ($metercount) print_r($zeile);	        // es gibt auch den Fall dass keine Homematic Messgeräte angeschlossen sind
			if ($html==true) 
				{
				if ($debug) echo "</p>";
				$output.="</div> \n";   /* Umschalten auf Courier Font */
				$outputEnergiewerte.=$endtable."</div> \n";
				$outputTabelle.=$endtable."</div> \n";				
				}
			return ($style.$output."\n\n".$outputEnergiewerte."\n\n".$outputTabelle);
			}

        /* die Tabelle "Stromzählerstand aktuell Energiewert in kWh" schreiben, Inputparameter sind die Werte
         * aus der Funktion writeEnergyRegistertoArray, Formatierung ist weird, aber es steht alles drin was man braucht
         */

		function writeEnergyRegisterValuestoString($Werte,$html=true)			/* alle Werte als String ausgeben, Input ist das Array der Werte */
			{
			if ($html==true) 
				{
				$style="<style> .zeile { font-family:Arial,'Courier New'; font-size: 0.8 em; white-space:pre-wrap;   }
				                .rotetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid red;  }
								.blauetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid blue; }  
								.gruenetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid green;     }   </style> \n\n";
				$starttable="<table style=\"border-collapse=collapse;\">"; $endtable="</table>";
				$startcell="<td style=\"border:1px solid black\">"; $endcell="</td>";
				$startcellheader="<th colspan=\"11\" style=\"border:1px solid black;\" >";$endcellheader="</th>";
				$startparagraph="<tr>"; $endparagraph="</tr>";  /* Paragraph oder Table Line */
				$outputEnergiewerte="<div> \n";   /* Umschalten auf Courier Font */
				$newline="<BR>\n";
				echo "<p class=\"zeile\"> \n";   /* Umschalten auf Arial Font */				
				}
			else
				{
				$style="";				
				$starttable=""; $endtable="";
				$startcell=""; $endcell="";					/* ein Tabelleneintrag */
				$startcellheader=""; $endcellheader="";
				$startparagraph=""; $endparagraph="\n";		/* eine Tabellenzeile */				
				$outputEnergiewerte="";
				$newline="\n";
				}
					
			$outputEnergiewerte.=$starttable.$startparagraph.$startcellheader."Stromzählerstand aktuell Energiewert in kWh:".$endcellheader.$endparagraph;

			$jetzt=time();
			$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
			$metercount=sizeof($Werte);
			$tabwidth0=24;
			echo "Gesamte aktuelle Registerwerte Tabelle aufbauen. Groesse ist ".$metercount." Eintraege.\n";
			for ($line=0;$line<($metercount);$line++)			
				{
				if (isset($Werte[$line]["Energie"][1])==true)
					{
					$outputEnergiewerte.=$startparagraph.$startcell.substr($Werte[$line]["Wochentag"][0]."                           ",0,$tabwidth0).$endcell.$startcell.str_pad($Werte[$line]["Energie"][1]." kWh",14).$endcell;
					}
				else
					{
					$outputEnergiewerte.=$startparagraph.$startcell.substr($Werte[$line]["Wochentag"][0]."                           ",0,$tabwidth0).$endcell.$startcell.str_pad(" ",10).$endcell;
					}
				if (isset($Werte[$line]["Power"])==true)
					{
					$outputEnergiewerte.=$startcell.(round(($Werte[$line]["Power"]*1000),1))." W".$endcell;
					}
				$outputEnergiewerte.=$startcell.str_pad($Werte[$line]["Information"]["Type"],11).$endcell;
				if ( isset($Werte[$line]["Information"]["Parentname"]) == true )
					{
					$outputEnergiewerte.=$startcell.str_pad($Werte[$line]["Information"]["Parentname"],28).$endcell;
					}
				else	
					{
					$outputEnergiewerte.=$startcell.str_pad(" ",28).$endcell;
					}
				$outputEnergiewerte.=$endparagraph;
				//echo "    ".substr($Werte[$line]["Wochentag"][0]."                           ",0,$tabwidth0)."   ".str_pad($Werte[$line]["Energie"][1],10)."\n";		
				} /* ende foreach Meter Entry */

			if ($html==true) 
				{
				echo "</p>";
                $outputEnergiewerte.='<tr><td align="right" colspan="5"><font size="-1">last update on '.date("d.m.Y H:i:s").'</font></td></tr>'; 
				$outputEnergiewerte.=$endtable."</div> \n";
				}
			return ($style.$outputEnergiewerte);
			}

		/* writeEnergyRegisterTabletoString
         *
         * fasst alle Energieregister als Vorschubwerte der letzten 9 Tage in einer uebersichtlichen Tabelle zusammen, 
		 * Tabelle kann sowohl als html als auch als Text ausgegeben werden
		 *
		 *	Stromverbrauch der letzten Tage als Änderung der Energiewerte pro Tag:
		 *	Energievorschub in kWh   Tue   Mon   Sun   Sat   Fri   Thu   Wed   Tue   Mon  
		 *	Datum                    06.02 05.02 04.02 03.02 02.02 01.02 31.01 30.01 29.01
		 *	Arbeitszimmer-AMIS       6,14  7,07  7,25  4,85  7,85  4,49  5,98  5,16  6,73 
		 *	Wohnzimmer               1,02  1,04  1,01  1,01  1,30  1,03  1,41  1,03  1,10 
		 *	Wohnzimmer-Effektlicht                                                        
		 *	Arbeitszimmer-Netzwerk   1,08  1,13  1,84  1,50  1,85  1,86  1,85  1,21  1,79 
		 *	Esstisch-Effektlicht     0,01  0,01  0,01  0,01  0,01  0,01  0,01  0,01  0,01 
		 *	Statusanzeige            0,01  0,02  0,02  0,00  0,04        0,02  0,01  0,01
		 * 
         * Die Werte für die Tabelle werden als Array übergeben: $Werte[$line]["EnergieVS"]
         * Die Daten kommen am Besten von writeEnergyRegistertoArray
         *
         * Routine aufgerufen von:
         *      AllgemeindeDefinitionen     send_status email Ausgabe der Daten
         *      AMIS Installation           Ausgabe in Tab.Register Zusammenfassung.Historie-Energie
         *      OperationCenter             einmal am Tag um 3:50 Update der register Historie-Energie
         *      AMIS                        bei Execute Aufruf Ausgabe als Html Tabelle
         *      BerechnePeriodenwerte       bei Execute Aufruf Ausgabe als Html Tabelle
         *
         *
		 */
		function writeEnergyRegisterTabletoString($Werte,$html=true,$debug=false)			/* alle Werte als String ausgeben */
			{
			if ($html==true) 
				{
				$style="<style> .zeile { font-family:Arial,'Courier New'; font-size: 0.8 em; white-space:pre-wrap;   }
				                .rotetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid red;  }
								.blauetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid blue; }  
								.gruenetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid green;     }   </style> \n\n";
				$starttable="<table style=\"border-collapse=collapse;\">"; $endtable="</table>";
				$startcell="<td style=\"border:1px solid black\">"; $endcell="</td>";
				$startcellheader="<th colspan=\"11\" style=\"border:1px solid black;\" >";$endcellheader="</th>";
				$startparagraph="<tr>"; $endparagraph="</tr>";  /* Paragraph oder Table Line */
				$outputTabelle="<div> \n";
				$newline="<BR>\n";
				if ($debug) echo "<p class=\"zeile\"> \n";                        // Umschalten auf Arial Font, Debug Ausgabe als html ?  
				}
			else
				{
				$style="";				
				$starttable=""; $endtable="";
				$startcell=""; $endcell="";					/* ein Tabelleneintrag */
				$startcellheader=""; $endcellheader="";
				$startparagraph=""; $endparagraph="\n";		/* eine Tabellenzeile */				
				$outputTabelle="";				
				$newline="\n";
				}
					
			$jetzt=time();
			$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
			$metercount=sizeof($Werte);
			$tabwidth0=24;

			/* Ausgabe aller Enrgievorschuebe in einer gemeinsamen Tabelle 
			 * zuerst Überschrift, Einleitung der Tabelle machen, Endergebnis in outputTabelle zusammenstellen
			 * Tabellenspalten für Ausgabe als plaintext in Courier auf gleiche Länge ablengen/schneiden
			 * keine automatische Erfassung des laengsten Eintrages 24,8,8,8,8,8
			 */
			$tabwidth=6; 			
			$zeile0=$startcell."Energievorschub in kWh  ".$endcell;
			$zeile1=$startcell."Datum                   ".$endcell;
			$zeit=$endtime-24*60*60;
			for ($i=1;$i<10;$i++)
				{
				$zeile0.=$startcell." ".substr((date("D", $zeit)."                            "),0,$tabwidth-1).$endcell;
				$zeile1.=$startcell." ".substr((date("d.m", $zeit)."                            "),0,$tabwidth-1).$endcell;
				$zeit-=24*60*60;
				}

			/* ganze Tabelle zusammenbauen, Zähler fürZähler, Zeile 0 und 1 übernehmen */

			$outputTabelle.=$starttable.$startparagraph.$startcellheader."Stromverbrauch der letzten Tage als Änderung der Energiewerte pro Tag:".$endcellheader.$endparagraph;
			$outputTabelle.=$startparagraph.$zeile0.$endparagraph.$startparagraph.$zeile1.$endparagraph;
			if ($debug) echo "writeEnergyRegisterTabletoString, gesamte Tabelle im Format ".($html?"Html":"Text")." für ".count($Werte)." Zeilen aufbauen.\n";
			for ($line=0;$line<($metercount);$line++)
				{
                if ($debug) echo "   Zeile $line für Zähler : ".$Werte[$line]["Wochentag"][0]."\n";
				$outputTabelle.=$startparagraph.$startcell.substr($Werte[$line]["Wochentag"][0]."                               ",0,$tabwidth0).$endcell;	/* neue Zeile pro Zähler */ 
				$zeit=$endtime-24*60*60;
				for ($i=1;$i<10;$i++)
					{				
					if ( isset($Werte[$line]["EnergieVS"][date("d.m", $zeit)])==true )
						{
						$outputTabelle.=$startcell." ".substr($Werte[$line]["EnergieVS"][date("d.m", $zeit)]."        ",0,$tabwidth-1).$endcell;
						}
					else	/* wenn es keinen Wert gibt leere Zelle drucken*/
						{
						if ($debug) echo "       Zählerwert für ".$Werte[$line]["Wochentag"][0]." mit Index".date("d.m", $zeit)." fehlt. Kein Zeitstempel für ".date("d.m", $zeit)." vorhanden.\n  ";
                        //foreach ($Werte[$line]["EnergieVS"] as $index => $value) echo "Index $index Wert $value, Found : ".(isset($Werte[$line]["EnergieVS"][date("d.m", $zeit)]))."\n";
						$outputTabelle.=$startcell.substr("              ",0,$tabwidth).$endcell;
						}
					$zeit-=24*60*60;	/* naechster Tag */			
					}
				$outputTabelle.=$endparagraph; 					
				}	

			//print_r($Werte);	
			if ($html==true) 
				{
				if ($debug) echo "</p>";                  // auch Ausgabe als html
				$outputTabelle.=$endtable."</div> \n";				
				}
			return ($style.$outputTabelle);
			}

        /* writeEnergyPeriodesTabletoString
         * 
         * die einzige Funktion zum Darstellen der 1/7/30/360 Werte
         * noch um brauchbare Funktionen erweitern, wie sortieren und zusätzliche Spalten, array als Config Item
         * mögliche config Parameter:
         *      Format
         *      Unit
         *      Sort
         *      Extend
         *
         * Erster Parameter Werte bestimmt den Inhalt, Wird mit writeEnergyRegistertoArray erzeugt:
			$amis=new Amis();
			$MeterConfig = get_MeterConfiguration();
			$Meter=$amis->writeEnergyRegistertoArray($MeterConfig,false);         
         *
         * Aus Werte wird aktuell nur Name und periodenwerte verwendet
         *
         */

		function writeEnergyPeriodesTabletoString($Werte,$config=true,$kwh=true)			/* alle Werte als String ausgeben */
			{
			/* Werte zwar uebernehmen, aber für Periodenwerte nicht wirklich notwendig */ 

            $sort="lastChanged";
            $extend=false;			
            if (is_array($config))            //Zusatzparameter übergeben
                {
                if ( (isset($config["Format"])) && ($config["Format"]=="html") ) $html=true;
                else $html=false;
                if ( (isset($config["Unit"])) && ($config["Unit"]=="kwh") ) $kwh=true;
                else $kwh=false;
                if (isset($config["Sort"]))  $sort=$config["Sort"];
                if (isset($config["Extend"])) $extend=true;
                echo "writeEnergyPeriodesTabletoString mit Config ".json_encode($config)." aufgerufen.\n";
                }
			else 
                {
                $html=$config;
                $sort      = "lastWeek";            // es gibt keinen Defaultparameter, für intellisort der Tabellendaten benötigt

                echo "writeEnergyPeriodesTabletoString mit Config \"".($config?"html":"line")."\" aufgerufen.\n";
                }

			if ($html==true) 
				{
				$style="<style> .zeile { font-family:Arial,'Courier New'; font-size: 0.8 em; white-space:pre-wrap;   }
				                .rotetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid red;  }
								.blauetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid blue; }  
								.gruenetabelle 	{ 	font-family:'Courier New', Arial; font-size: 0.8 em; 
													white-space: pre; border:1px solid green;     }   </style> \n\n";
				$starttable="<table style=\"border-collapse=collapse;\">"; $endtable="</table>";
				$startcell="<td style=\"border:1px solid black\">"; $endcell="</td>";
				$startcellheader="<th colspan=\"11\" style=\"border:1px solid black;\" >";$endcellheader="</th>";
				$startparagraph="<tr>"; $endparagraph="</tr>";  /* Paragraph oder Table Line */
				$outputTabelle="<div> \n";
				$newline="<BR>\n";
				echo "<p class=\"zeile\"> \n";   /* Umschalten auf Arial Font */				
				}
			else
				{
				$style="";				
				$starttable=""; $endtable="";
				$startcell=""; $endcell="";					/* ein Tabelleneintrag */
				$startcellheader=""; $endcellheader="";
				$startparagraph=""; $endparagraph="\n";		/* eine Tabellenzeile */				
				$outputTabelle="";				
				$newline="\n";
				}
					
			$metercount=sizeof($Werte);
			$tabwidth0=24;

			/* Ausgabe aller Periodenwerte in einer gemeinsamen Tabelle 
			 * zuerst Überschrift, Einleitung der Tabelle machen, Endergebnis in outputTabelle zusammenstellen
			 * Tabellenspalten für Ausgabe als plaintext in Courier auf gleiche Länge ablengen/schneiden
			 * keine automatische Erfassung des laengsten Eintrages 24,8,8,8,8,8
			 */
			$tabwidth=10; 			
			$zeile0 =$startcell."Periodenwerte           ".$endcell;
            if ($extend) $zeile0 .= $startcell." Type     ".$endcell;
            $zeile0.=$startcell."   1      ".$endcell;
			$zeile0.=$startcell."   7      ".$endcell;
			$zeile0.=$startcell."  30      ".$endcell;
			$zeile0.=$startcell." 360      ".$endcell;
            if ($extend) $zeile0 .= $startcell." lastChanged    ".$endcell;                                                                     

			/* ganze Tabelle zusammenbauen, Zähler fürZähler, Zeile 0 übernehmen */
			
			if ($kwh==true)
				{
				$outputTabelle.=$starttable.$startparagraph.$startcellheader."Stromverbrauch als Periodenwerte aggregiert in kWh:".$endcellheader.$endparagraph;
				}
			else
				{
				$outputTabelle.=$starttable.$startparagraph.$startcellheader."Stromverbrauch als Periodenwerte aggregiert in EUR:".$endcellheader.$endparagraph;
				}	
			$outputTabelle.=$startparagraph.$zeile0.$endparagraph;
			echo "  Gesamte Tabelle aufbauen, eine Zeile pro Zähler\n";

            $display=array();
			for ($line=0;$line<($metercount);$line++)
				{
				$PeriodenwerteID = $Werte[$line]["Information"]["Periodenwerte"];
                $props = IPS_GetVariable($Werte[$line]["Information"]["Register-OID"]);
                $display[$line]["lastChanged"]      = $props["VariableUpdated"];         // VariableUpdated
                $display[$line]["lastDay"]          = GetValue(IPS_GetVariableIDByName('Wirkenergie_letzterTag',$PeriodenwerteID));
				$display[$line]["lastWeek"]         = GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte7Tage',$PeriodenwerteID));
				$display[$line]["lastMonth"]        = GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte30Tage',$PeriodenwerteID));
				$display[$line]["lastYear"]         = GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte360Tage',$PeriodenwerteID));
				$display[$line]["lastDayEuro"]      = GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzterTag',$PeriodenwerteID));
				$display[$line]["lastWeekEuro"]     = GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte7Tage',$PeriodenwerteID));
				$display[$line]["lastMonthEuro"]    = GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte30Tage',$PeriodenwerteID));
				$display[$line]["lastYearEuro"]     = GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte360Tage',$PeriodenwerteID));
                $display[$line]["Information"]      = json_encode($Werte[$line]["Information"]);
                //$display[$line]["Information"]["Name"] = $Werte[$line]["Information"]["NAME"];            // intelliSort kann nur zweidimensional arrays
                //$display[$line]["Information"]["Type"] = $Werte[$line]["Information"]["Type"];
				}	
            $this->ipsOps->intelliSort($display,$sort,SORT_DESC);

			for ($line=0;$line<($metercount);$line++)
				{
                $display[$line]["Information"]      = json_decode($display[$line]["Information"],true);
				$outputTabelle.=$startparagraph.$startcell.substr($display[$line]["Information"]["NAME"]."                               ",0,$tabwidth0).$endcell;	/* neue Zeile pro Zähler */ 
				if ($kwh==true)
					{
                    if ($extend) $outputTabelle.=$startcell.str_pad($display[$line]["Information"]["Type"],$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format($display[$line]["lastDay"], 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format($display[$line]["lastWeek"], 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format($display[$line]["lastMonth"], 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format($display[$line]["lastYear"], 2, ",", "" ),$tabwidth).$endcell;
                    if ($extend) $outputTabelle.=$startcell.str_pad(date("d.m.Y H:i:s",$display[$line]["lastChanged"]),$tabwidth).$endcell;
					}
				else
					{	
					$outputTabelle.=$startcell.str_pad(number_format($display[$line]["lastDayEuro"], 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format($display[$line]["lastWeekEuro"], 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format($display[$line]["lastMonthEuro"], 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format($display[$line]["lastYearEuro"], 2, ",", "" ),$tabwidth).$endcell;
					}
					
				$outputTabelle.=$endparagraph; 					
				}	

			//print_r($Werte);	
			if ($html==true) 
				{
				echo "</p>";
				$outputTabelle.=$endtable."</div> \n";				
				}
			return ($style.$outputTabelle);
			}


		
		/* Analysefunktionen
         * Parameter meter kommt von $Meter=$amis->writeEnergyRegistertoArray($MeterConfig, true);
		 * Vergleichsfunktion, welche Hardware ist installiert 
		 * und welche ist als Energieregister mit Archiv und Anzeige konfiguriert
		 * Ausgabe als Text-String für AmisStromverbrauchList in EvaluateVariables_ROID
		 */
		 
		function getEnergyRegister($Meter=array(),$debug=false)
			{
			$size=sizeof($Meter);
			$oids=array();
			if ($debug) echo "getEnergyRegister, Anzahl Zähler-Eintraege von writeEnergyRegistertoArray: ".$size."\n";
			for ($i=0;$i<$size;$i++)                    //Liste der OIDs erstellen
				{
				$oids[$Meter[$i]["Information"]["Register-OID"]]=$Meter[$i]["Information"]["NAME"];
				if ($debug) echo "  ".str_pad($Meter[$i]["Information"]["NAME"],28)."  ".$Meter[$i]["Information"]["OID"]."   ".$Meter[$i]["Information"]["Parentname"]."/".$Meter[$i]["Information"]["Register-OID"]."   \n";
				//print_r($Meter[$i]);
				}
			//print_r($oids);	
			//echo "\n\n";
			
		  	/* EvaluateHardware_include.inc wird automatisch nach Aufruf von EvaluateHardware erstellt */			
			IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
			$Homematic = HomematicList();
            $powerValues=array();
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Energiesensoren ausgeben */
				if ( (isset($Key["COID"]["VOLTAGE"])==true) )
					{
					/* alle Leistungs Sensoren */
                    $powerValues[$Key["Name"]]=array();
                    $powerValues[$Key["Name"]]["OID"]=$Key["COID"]["POWER"]["OID"];
                    $powerValues[$Key["Name"]]["Power"]=GetValue($Key["COID"]["POWER"]["OID"]);
                    //print_r($Key);
                    }
                }
            $ipsOps = new ipsOps();
            $ipsOps->intelliSort($powerValues,"Power");
            //print_R($powerValues);
            if ($debug) echo "\nLeistungsregister direkt aus den Homematic Instanzen:\n";
            foreach ($powerValues as $name => $entry) 
                {
                $oid=$entry["OID"];
                if ($debug) echo str_pad($name,45)." (".$oid.") = ".str_pad(GetValueIfFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")   \n";
                }

			$alleStromWerte="";
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Energiesensoren ausgeben */
				if ( (isset($Key["COID"]["VOLTAGE"])==true) )
					{
					/* alle Energiesensoren */

					$oid=(integer)$Key["COID"]["ENERGY_COUNTER"]["OID"];
					$variabletyp=IPS_GetVariable($oid);
    				$alleStromWerte.=str_pad($Key["Name"],30)." (".$oid.") = ".str_pad(GetValueIfFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")";
					if ( (isset($oids[$oid]) == true) ) 
						{
						$alleStromWerte.="  Configured as ".$oids[$oid]."\n";
						}
					else
						{
						$alleStromWerte.="  Not Configured\n";
						}
					}
				}
			
			$alleStromWerte.="\n\nAlle Stromwerte aus RemoteAccess EvaluateVariables_ROID.inc.php function AMISStromverbrauchList():\n";
										  	
		  	/* EvaluateVariables_ROID.inc wird automatisch nach Aufruf von RemoteAccess erstellt , enthält Routine AmisStromverbrauchlist */
			IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");	
            if (function_exists("AmisStromverbrauchList"))	
                {	
                $stromverbrauch=AmisStromverbrauchList();
                //print_r($stromverbrauch);             // das sind die von AMIS bereits evaluierten Register, Update alle 15 Minuten
                foreach ($stromverbrauch as $Key)
                    {
                    $oid=(integer)$Key["OID"];
                    $variabletyp=IPS_GetVariable($oid);
                    //print_r($variabletyp);
                    //echo $ipsOps->path($oid)."\n";
                    if ($Key["Profile"]=="~Power") $alleStromWerte.= str_pad($Key["Name"],30)." = ".GetValueIfFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).") \n";
                    //else echo $Key["Profile"]."..";
                    }
                }
			return($alleStromWerte);	
			}
		
		
		function getAMISDataOids()
			{
			return(CreateVariableByName($this->CategoryIdData, "Zusammenfassung", 3)); 
			}
			
		/* writeEnergyRegistertoArray
         *
		 * zweiteilige Funktionalitaet, erst die Energieregister samt Einzelwerten der letzten Tage einsammeln und
		 * dann in einer zweiten Funktion die Ausgabe als html oder text machen.
		 * Übergabe	der Energiewerte der letzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr erfolgt als Array.
         * Das Array wird aus der MeterConfig erzeugt:  $MeterConfig = get_MeterConfiguration();
         * die Parameter daraus werden genutzt: TYPE, OID,          
         *
         * die Ausgabe wird Zeilenweise mit index metercount geschrieben
         *
         * return Wert Wochentag, Datum, Energiewert, Information { Name, 
         *    
         * Aufruf erfolgt in MomentanwerteAbfragen, AMIS_Installation, OperationCenter, Berechneperiodenwerte etc.
         * verwendet archiveOps:getValues    
         * schreibt keine Register mit SetValue
         *
		 */
		
		function writeEnergyRegistertoArray($MConfig,$debug=false)
			{
            if ($debug) echo "writeEnergyRegistertoArray wurde aufgerufen. MeterConfig einzeln durchgehen und Messwerte für Tabelle ermitteln.\n";
            $archiveOps = new archiveOps(); 
			$zeile=array();
			$metercount=0;
			foreach ($MConfig as $meter)            // alle Zähler entsprechend Config duchgehen
				{
				if ($debug)
					{
					echo "   -----------------------------\n";
					echo "   writeEnergyRegistertoArray, Werte von : ".$meter["NAME"]." für Typ ".$meter["TYPE"]."\n";
					}
				$meterdataID = IPS_GetObjectIdByName($meter["NAME"],$this->CategoryIdData);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$EnergieID = $this->getWirkenergieID($meter);    // ID von Wirkenergie bestimmen 
                if ($EnergieID === false) echo "Error, did not find WirkenergieID of ".json_encode($meter)." Add Type in getWirkenergieID. Ignore this Entry\n";
                else
                    {
                    /* Energiewerte der letzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr */
                    $jetzt=time();                        
                    switch ( strtoupper($meter["TYPE"]) )                   
                        {	
                        case "HOMEMATIC":                                       // Spezialbehandlung für Homematic register, RegID bestimmen
                            if ( isset($meter["OID"]) == true )
                                {
                                $OID  = $meter["OID"];
                                $cids = IPS_GetChildrenIDs($OID);
                                if (sizeof($cids) == 0) 
                                    {
                                    $OID = IPS_GetParent($OID);
                                    $cids = IPS_GetChildrenIDs($OID);
                                    }
                                //echo "OID der passenden Homematic Register selbst bestimmen. Wir sind auf ".$OID." (".IPS_GetName($OID).")\n";
                                //print_r($cids);
                                foreach($cids as $cid)
                                    {
                                    $o = IPS_GetObject($cid);
                                    if($o['ObjectIdent'] != "")
                                        {
                                        if ( $o['ObjectName'] == "ENERGY_COUNTER" ) { $RegID=$o['ObjectID']; }
                                        }
                                    }
                                //echo "  OID der Homematic Register selbst bestimmt : Energie : ".$HMenergieID." Leistung : ".$HMleistungID."\n";
                                }
                            else
                                {
                                $RegID  = $meter["HM_EnergieID"];
                                }
                            //$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
                            $endtime=$jetzt;                            // die aktuelle Uhrzeit sollte ausreichen
                            $vorigertag=date("d.m.Y",$jetzt);	/* einen Tag ausblenden */
                            $vorschub=false;                                   
                            break;
                        case "DAILYLPREAD":	
                            $RegID=$EnergieID;	
                            $endtime=$jetzt;                // das ist nur ein Wert pro Tag, die Werte von heute wurden eh noch nicht erfasst
                            $vorigertag=0;
                            $vorschub=2;                                        
                            break;
                        case "DAILYREAD":	
                            $RegID=$EnergieID;	
                            $endtime=$jetzt;                // das ist nur ein Wert pro Tag, die Werte von heute wurden eh noch nicht erfasst
                            $vorigertag=0;
                            $vorschub=true;                                        
                            break;
                        default:
                            $RegID=$EnergieID;
                            //$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
                            $endtime=$jetzt;                            // die aktuelle Uhrzeit sollte ausreichen
                            $vorigertag=date("d.m.Y",$jetzt);	/* einen Tag ausblenden */
                            $vorschub=false;                            
                            break;
                        }					
                    $starttime=$endtime-60*60*24*10;
                    if ($debug)
                        {
                        echo "      Type ".$meter["TYPE"]." : Energy register with Energychange is here $RegID , there is no counter.\n";
                        echo "      Starttime für Auswertung ist ".date("d.m.Y H:i:s",$starttime)." Endtime ist ".date("d.m.Y H:i:s",$endtime)."\n";
                        }
                    //$werte = AC_GetLoggedValues($this->archiveHandlerID, $EnergieID, $starttime, $endtime, 0);

                    /* Alternative Auswertung mit Vorschüben als Ergebnis
                     * entweder Counter für Auswertung 15min Vorschuebe oder manAggregate
                     */
                    $config=array();
                    $config["StartTime"] = $starttime;   // 0 endtime ist now
                    $config["EndTime"]   = $endtime;
                    $config["DataType"]  = "Counter";           // eine Auswertung von Zählerständen ist sinnlos, vorher umrechnen
                    //$config["manAggregate"]  = "daily";
                    $valuesInterval = $archiveOps->getValues($EnergieID,$config,$debug);     // Analyse der Archivdaten, debug von default

                    // Alternative Auswertung
                    $config=array();
                    $config["StartTime"] = $starttime;   // 0 endtime ist now
                    $config["EndTime"]   = $endtime;
                    $valuesAnalysed = $archiveOps->getValues($EnergieID,$config,$debug);     // Analyse der Archivdaten, debug von default
                    //if ($metercount==0) print_R($valuesAnalysed["Description"]["MaxMin"]);
                    $debug1=false;
                    if (isset($valuesAnalysed["Values"]))
                        {
                        if (isset($valuesAnalysed["Description"]["MaxMin"]["Span"])) { if ($debug1) echo "Span ".nf($valuesAnalysed["Description"]["MaxMin"]["Span"],"s")."\n"; }
                        $werte=$valuesAnalysed["Values"];
                        $result=$archiveOps->countperIntervalValues($werte,$debug1);                // zusätzliche Debugmöglichkeit
                        //print_R($result);
                        //print_r($valuesAnalysed["Description"]);
                        }
                    else $werte=false;
                    
                    if ($debug) 
                        {
                        echo "~~~~~~~~~~~~~~~~\n";
                        if ($werte===false) echo "Warnung, keine Zeitreihe möglich.\n";
                        elseif (is_array($werte)) 
                            {
                            echo "writeEnergyRegistertoArray, Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime)." mit ".count($werte)." Eintraegen ermittelt, jetzt bearbeiten und speichern :\n";
                            $first = $werte[array_key_first($werte)];    
                            $last = $werte[array_key_last($werte)];
                            $wert=-($first["Value"]-$last["Value"]);
                            $zeit=-($first["TimeStamp"]-$last["TimeStamp"]);          
                            $leist=$wert/$zeit*60*60;
                            echo "   $wert / $zeit = $leist W,   First   ".$first["Value"]."  ".date("D d.m H:i",$first["TimeStamp"])."   Last   ".$last["Value"]."  ".date("D d.m H:i",$last["TimeStamp"])."  \n";
                            //print_R($werte);                            
                            }
                        }
                    if ( ($werte===false) || (is_array($werte)===false) ) $werte=array();

                    $zeile[$metercount] = array(
                        "Wochentag" 	=> array($meter["NAME"]), 
                        "Datum" 		=> array("Datum"), 
                        "Energie" 		=> array("Energie"),
                        "Information" 	=> array(
                            "NAME" 			=> $meter["NAME"],
                            "OID"			=> $EnergieID,
                            "Register-OID"	=> $RegID,
                            "Parentname"	=> (IPS_GetName(IPS_GetParent($RegID))),
                            "Unit"			=> "kWh",
                            "Type"			=> strtoupper($meter["TYPE"]),
                            "Periodenwerte" => CreateVariableByName($meterdataID, "Periodenwerte", 3),
                                                ) 
                                            );
                    $laufend=1; $alterWert=0; $initial=true; $count=0;
                    // hier die Tagesaggregation machen, es gibt Tageswerte mit 00:00 als Summe für diesen Tag und 15min Werte mit dem letzten Eintrag des Vortages
                    // das heisst wenn es nur Tageswerte sind, die um 00:00 eingetragen sind, ist die gebildete Summe einen Tag zu spät
                    // wenn die Werte einmal 00:00 und einmal xx Uhr sind fehlen Werte, da sie überschrieben werden
                    foreach ($werte as $wert)            
                        {
                        if ($vorschub<2) $zeit=$wert['TimeStamp']-60;                // eine Minute in die Vergangenheit 00:00 ist 23:59, nur wenn 15min Werte oder egal
                        else $zeit=$wert['TimeStamp'];
                        //echo "    ".date("D d.m H:i", $wert['TimeStamp'])."   ".$wert['Value']."    ".$wert['Duration']."\n";
                        if (date("d.m.Y", $zeit)!=$vorigertag)          // aktueller Wert hat ein anderes Datum als der vorige Wert
                            {
                            $zeile[$metercount]["Datum"][$laufend] = date("d.m", $zeit);
                            $zeile[$metercount]["Wochentag"][$laufend] = date("D  ", $zeit);
                            if ($initial) { $alterWert=$wert['Value']; $initial=false; }
                            if ($vorschub)        // Tageswechsel für Vorschubvariabel, DAILYLPREAD
                                {
                                $datumOfValues=date("d.m",strtoTime("-0 days", strtotime(date("d.m.Y 00:01",$zeit))));      // immer um 1:00 ist Target Time
                                if ($debug) echo "  DailyRead Werte : ".$datumOfValues." ".nf($wert['Value'],"kWh")."      (".strtoupper($meter["TYPE"]).")\n";
                                if (isset($zeile[$metercount]["EnergieVS"][$datumOfValues])===false) $zeile[$metercount]["EnergieVS"][$datumOfValues] = number_format($wert['Value'], 3, ",", "" );
                                else echo "etwas tun.\n";
                                }
                            else
                                {       // Tageswechsel für Zählervariabel
                                $diff=abs($alterWert-$wert['Value']);
                                if ($debug) echo "  Werte : ".date("D d.m H:i", $zeit)." ".nf($wert['Value'],"kWh")."   ".nf($diff,"kWh")."         (".strtoupper($meter["TYPE"]).")    Wert bearbeitet $count\n";
                                $zeile[$metercount]["Energie"][$laufend] = number_format($wert['Value'], 3, ",", "" );
                                if ($laufend>1) 
                                    {
                                    $zeile[$metercount]["EnergieVS"][$altesDatum] = number_format($diff, 2, ",", "" );
                                    }
                                }
                            $alterWert=$wert['Value'];                            
                            $altesDatum=date("d.m", $zeit);
                            $laufend+=1;
                            $count=0;
                            //echo "Voriger Tag :".date("d.m.Y",$zeit)."\n";
                            }
                        $vorigertag=date("d.m.Y",$zeit);
                        $count++;
                        }
                    // Space for additional Evaluations that result into zeile[metercount]
                    $zeile[$metercount]["Power"] = $this->getArchivePower($EnergieID,($debug>1));
                    }
				$metercount+=1;                                     //  nächste Zeile
				} /* ende foreach Meter Entry */
			
			return($zeile);
			}

        /* getArchiveData
         *
         * Messwerte aus dem Archive auslesen und gleichzeitig eine Plausicheck machen
         * weitestgehend als generische Routine geschrieben
         *
         * starttime ist eine Zeit vor x Tagen und 
         * endtime ist normalerweise heute
         * type unterscheidet "" für Energie das sind Zählwerte, "A" zB für Messwerte
         * display aktiviert zusätzliche Anzeigen und 
         * deleteCheck macht eine Bereinigung von falschen Werten
         *
         * verwendet AC_GetLoggedValues
         */

        function getArchiveData($variableID, $starttime, $endtime, $type="",$display=false,$deleteCheck="",$debug=false)
            {
            $object = @IPS_GetObject($variableID);
            if ($object === false) 
                {
                echo "FEHLER,getArchiveData Variable mit ID  $variableID  nicht vorhanden.\n";
                return(false);                
                }

            if ($deleteCheck=="Delete") $delete=true;          // damit werden geloggte Werte die als nicht plausibel gekennzeichnet sind gelöscht
            else $delete=false;
            
            if ($deleteCheck=="Aggregate") $reaggregate=true;           // damit kann die Reaggregation auch bewusst aktiviert werden.
            else $reaggregate=false;
            
            $initial=true;              /* Tätigkeiten nur beim allerersten Mal */
            $ergebnis=0;
            $vorigertag="";
            $disp_vorigertag="";
            $neuwert=0;

            $vorwert=0;
            $zaehler=0;
            //$variableID=44113;               
            //echo "ArchiveHandler: ".$this->archiveHandlerID." Variable: $variableID (".$this->ipsOps->path($variableID).")\n";
            if ($debug)
                {
                if ($type == "") echo "getArchiveData: Werte Variable: ".IPS_GetName($variableID)."/".IPS_GetName(IPS_GetParent($variableID))." von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
                else echo "getArchiveData: Werte Variable: ".IPS_GetName($variableID)."/".IPS_GetName(IPS_GetParent($variableID))." mit Typ $type von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
                }            
            else $display=false;            // kein debug, kein display

            $increment=1;
            //echo "Increment :".$increment."\n";
            $gepldauer=($endtime-$starttime)/24/60/60;          // Zeit in Tagen

            $energyCalc=0;              // Wert in kWh

            $time24h=$endtime-(24*60*60);                       // Zeitstempel vor 24 Stunden, solange Zeit größer Differenz schreiben
            $value24h=false;

            do {
                /* es könnten mehr als 10.000 Werte sein, Abfrage generisch lassen     
                 * Dieser Teil erstellt eine Ausgabe im Skriptfenster mit den abgefragten Werten, nicht mer als 10.000 Werte ...
                 */
                $werte = AC_GetLoggedValues($this->archiveHandlerID, $variableID, $starttime, $endtime, 0);
                //print_r($werte);
                $anzahl=count($werte);

                if (($anzahl == 0) & ($zaehler == 0))       // aktuelle Anzahl Einträge im Archiv und Anzahl der vorigen 10.000er Ergebnisse
                    {
                    echo "getArchiveData: Fehler, Variable: ".IPS_GetName($variableID)." hat keine Werte zwischen den geforderten Zeiten ".date("d.m.Y H:i:s",$endtime)." und ".date("d.m.Y H:i:s",$starttime)." archiviert. \n";
                    break;
                    }   // hartes Ende der Schleife wenn keine Werte vorhanden

                if ($initial)
                    {
                    /* allererster Durchlauf */
                    if ($debug) echo "   Variable: ".IPS_GetName($variableID)." mit ".$anzahl." Werte. \n";
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

                //$initial=true;

                /* zeit, aktwert mit aktuellem Wert beschreiben, aktuellen tag rausfiltern
                 * wenn Energiewert, unplausible Werte wie 0, kleiner 0, großer Wert früher als kleiner Wert wenn delete gesetzt ist auch löschen
                 * Tageswechsel wird erkannt, es wird der Stringwert für das Datum verglichen
                 * abhängig von increment gibt es verschiedene Betriebsarten
                 *
                 * Auswertungen machen
                 *
                 * 24 Stundenwert
                 *
                 */

                foreach($werte as $wert)
                    {
                    $zeit=$wert['TimeStamp'];
                    $tag=date("d.m.Y", $zeit);
                    $aktwert=(float)$wert['Value'];

                    if ($initial)
                        {
                        //print_r($wert);
                        $initial=false;
                        $vorwert=$aktwert;  $vorzeit=$zeit;         // es gibt noch keine Vorwerte
                        //echo "   Endzeitpunkt, letzter Wert in Zeitreihe:".date("d.m.Y H:i:s", $wert['TimeStamp'])." -> ".nf($aktwert,3)."\n";
                        }
                    if ($type=="")      /* Energie(zähl)werte, das heisst sie steigen kontinuiertlich */
                        {                        
                        $vorwertCalc=$vorwert;    $vorzeitCalc=$vorzeit;                      // Vorwert sichwern, wird gleich überschrieben
                        if (($aktwert>$vorwert) or ($aktwert==0) or ($aktwert<0))       // unplausible Werte bei Energiemessung rausfiltern */
                            {
                            if ($delete==true)
                                {
                                AC_DeleteVariableData($this->archiveHandlerID, $variableID, $zeit, $zeit);
                                $reaggregate=true;
                                }
                            if ($debug) echo "****".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." ergibt in Summe         : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
                            }
                        else
                            {
                            $vorwert=$aktwert;
                            $vorzeit=$zeit;
                            }
                        if ( ($time24h>$zeit) && ($value24h==false) )           // wenn das erste Mal der 24 Stunden Zeitstempel unterschritten wird
                            {
                            $value24h=$ersterwert-$aktwert;
                            }
                        else $energyCalc = $ersterwert-$aktwert;            // Total Energie
                        if ($tag!=$vorigertag)          // beim ersten Mal schlägt der Check gleich an, da vorigertag "" ist
                            { /* neuer Tag */
                            $altwert=$neuwert;
                            $neuwert=$aktwert;
                            switch ($increment)
                                {
                                case 1:                                     // unterschiedliche Betriebsarten, normaler Zähler für Energie
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
                        }


                    if ($type=="")          // Energie(zaehl)werte
                        {
                        /* jeden Eintrag ausgeben, aktwert und zeit sind die Werte aus dem Archiv */
                        //print_r($wert);
                        if ($vorwertCalc != $aktwert)
                            {
                            $intervall= ($vorzeitCalc-$zeit);
                            if ($intervall != 0)
                                {
                                $multiplikator = (60*60)/$intervall;
                                $leistung = ($vorwertCalc-$aktwert)*$multiplikator;
                                if ( ($display==true) || ($multiplikator < (3.9)) || ($multiplikator > (4.1)) ) 
                                    {
                                    if ($debug)
                                        {
                                        echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . nf($aktwert,"kWh")."   ".nf($leistung, "kW")." ergibt in Summe (Tageswert) : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
                                        if ( ($multiplikator < (1)) || ($multiplikator > (6)) ) echo "        ==>   Leistungsberechnung, Zeitdauer ".nf($intervall,"s")." ".nf($multiplikator,1)." \n";
                                        elseif ( ($multiplikator < (3.9)) || ($multiplikator > (4.1)) ) echo "              Leistungsberechnung, Zeitdauer ".nf($intervall,"s")." ".nf($multiplikator,1)." \n";
                                        }
                                    }
                                }
                            }
                        else 
                            {       /*  erster Wert */
                            if  ($display==true) echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . nf($aktwert, "kWh")."         ergibt in Summe (Tageswert) : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
                            }
                        }
                    else                // Leistungswerte
                        {
                        /* jeden Eintrag ausgeben */
                        $invalid = false;
                        switch (strtoupper($type))
                            {
                            case "W":               // Plausiprüfung für Leistung, gröer 10kW ist unwahrscheinlich 
                                if (($aktwert > (10000)) or ($aktwert==0) or ($aktwert<0)) $invalid = true;      // unplausible Werte bei Leistungsmessung rausfiltern
                                $powerCalc=$aktwert/1000;
                                break;
                            case "KW":               // Plausiprüfung für Leistung, gröer 10kW ist unwahrscheinlich 
                                if (($aktwert > (10)) or ($aktwert==0) or ($aktwert<0)) $invalid = true;      // unplausible Werte bei Leistungsmessung rausfiltern
                                $powerCalc=$aktwert;
                                break;
                            default:
                                $powerCalc=$aktwert;                            
                                break;
                            }
                        if ($invalid)
                            {
                            if ($delete==true)
                                {
                                AC_DeleteVariableData($this->archiveHandlerID, $variableID, $zeit, $zeit);
                                $reaggregate=true;
                                }
                            if ($debug) echo "****".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." nicht plausibel ". PHP_EOL;
                            }
                        else                // gültiger leistungswert
                            {
                            $energyCalc += $powerCalc*($vorzeit-$zeit)/60/60;           //15min Werte sind nur ein viertel der Energie
                            if ( ($time24h>$zeit) && ($value24h==false) )           // wenn das erste Mal der 24 Stunden Zeitstempel unterschritten wird
                                {
                                $value24h=$energyCalc;
                                }
                            if ($display==true) echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "")." $type ".number_format($energyCalc, 3, ".", "")."\n";
                            $vorwert=$aktwert;
                            $vorzeit=$zeit;
                            }
                        }

                    $zaehler+=1;
                    }
                        //$endtime=$zeit;
                } while (count($werte)==10000);
                
            if ($zaehler==0) return (false);            //keine Werte vorhanden
            
            if ($delete && $reaggregate) 
                {
                echo "Delete of one value means re aggregate the archive.\n";
                $result = @AC_ReAggregateVariable($this->archiveHandlerID, $variableID);
                if ($result===false) echo " Error, take pace, re aggragation takes allready place.\n";
                }
            $result=array();
            if ($value24h)
                {
                if ($debug) echo "24h Wert geschrieben, Wert erfasst am/um ".date("d.m.Y H:i:s",$time24h)."\n";
                $result["24h"]["Value"]=$value24h;
                $result["24h"]["TimeStamp"]=$time24h;
                }
            else
                {
                if ($debug) echo "letzter Wert geschrieben, Wert erfasst am/um ".date("d.m.Y H:i:s",$zeit)."\n";
                $result["24h"]["Value"]=$energyCalc;
                $result["24h"]["TimeStamp"]=$zeit;
                }

            return ($result);
            }

		
        /* getArchiveDataMax
         * aus den Archivedaten den grössten Wert finden 
         * Wert zurückmelden
         *
         * verwendet AC_GetLoggedValues
         */

        function getArchiveDataMax($variableID, $starttime, $endtime, $display=false)
            {
            $object = @IPS_GetObject($variableID);
            if ($object === false) 
                {
                echo "FEHLER,getArchiveDataMax Variable mit ID  $variableID  nicht vorhanden.\n";
                return(false);                
                }

            $maxwert=0;
            $zaehler=0;
            //$variableID=44113;               
            //echo "ArchiveHandler: ".$this->archiveHandlerID." Variable: $variableID (".$this->ipsOps->path($variableID).")\n";
            echo "getArchiveDataMax: Werte Variable: ".IPS_GetName($variableID)."/".IPS_GetName(IPS_GetParent($variableID))." Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
            
            $gepldauer=($endtime-$starttime)/24/60/60;

            $initial=true;                  // ersten Wert anders behandeln
            do {
                /* es könnten mehr als 10.000 Werte sein, Abfrage generisch lassen, wird mehrmals durchlaufen */

                $werte = AC_GetLoggedValues($this->archiveHandlerID, $variableID, $starttime, $endtime, 0);
                $anzahl=count($werte);
                echo "   Variable: ".IPS_GetName($variableID)." mit ".$anzahl." Werte. \n";

                if (($anzahl == 0) & ($zaehler == 0)) 
                    {
                    echo " Keine Werte archiviert. \n";
                    return (false);
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
                        $maxwert=$aktwert;
                        echo "   Initial Startzeitpunkt:".date("d.m.Y H:i:s", $wert['TimeStamp'])."\n";
                        }
                    if ($aktwert > $maxwert) $maxwert=$aktwert;
                    if ($display==true) 
                        {
                        /* jeden Eintrag ausgeben */
                        echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "")."    aktuelles Max ist $maxwert. \n";
                        }
                    $zaehler+=1;
                    }
                        //$endtime=$zeit;
                } while (count($werte)==10000);
            return ($maxwert);
            }

        /* getArchivePower
         *
         */
        private function getArchivePower($EnergieID,$debug=false)
            {
            $jetzt=time();
            $endtime=$jetzt; $starttime=$endtime-60*60*24-7*60;
            if ($debug) echo "getArchivePower, Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime).":\n";

            $werte = AC_GetLoggedValues($this->archiveHandlerID, $EnergieID, $starttime, $endtime, 0);
            if (count($werte)<10) return (0);               // wenn nichkeine Werte gespeichert sind 0 als Leistung zurückgeben
            $first = $werte[array_key_first($werte)];    
            $last = $werte[array_key_last($werte)];
            $wert=$first["Value"]-$last["Value"];
            $zeit=$first["TimeStamp"]-$last["TimeStamp"];          
            $leist=$wert/$zeit*60*60;
            if ($debug) echo "   $wert / $zeit = $leist W,   First   ".$first["Value"]."  ".date("D d.m H:i",$first["TimeStamp"])."   Last   ".$last["Value"]."  ".date("D d.m H:i",$last["TimeStamp"])."  \n";
            //print_R($werte);
            $vorigertag=date("d.m.Y",$jetzt);	/* einen Tag ausblenden */
            $laufend=1; $alterWert=0; $alteZeit=$jetzt;
            foreach($werte as $wert)
                {
                $zeit=$wert['TimeStamp']-60;
                $leistung=($alterWert-$wert['Value'])/($alteZeit-$wert['TimeStamp'])*60*60*1000;            // Umrechnung auf Stunde und W
                if ( ($alterWert) && ($debug) )echo "    ".date("D d.m H:i", $alteZeit)."   ".nf($alterWert,"kWh")."    ".str_pad(($alteZeit-$wert['TimeStamp']),8)."   ".nf($alterWert-$wert['Value'],"kWh")."  ".nf($leistung,"W")."\n";
                //echo "    ".date("D d.m H:i", $wert['TimeStamp'])."   ".$wert['Value']."    ".$wert['Duration']."\n";
                if (date("d.m.Y", $zeit)!=$vorigertag)
                    {
                    //$zeile[$metercount]["Datum"][$laufend] = date("d.m", $zeit);
                    //$zeile[$metercount]["Wochentag"][$laufend] = date("D  ", $zeit);
                    if ($debug) echo "  Werte : ".date("D d.m H:i", $zeit)." ".number_format($wert['Value'], 2, ",", "" ) ." kWh\n";
                    //$zeile[$metercount]["Energie"][$laufend] = number_format($wert['Value'], 3, ",", "" );
                    if ($laufend>1) 
                        {
                        //$zeile[$metercount]["EnergieVS"][$altesDatum] = number_format(($alterWert-$wert['Value']), 2, ",", "" );
                        }
                    
                    $laufend+=1;
                    $altesDatum=date("d.m", $zeit);
                    //echo "Voriger Tag :".date("d.m.Y",$zeit)."\n";
                    }
                $alterWert=$wert['Value']; $alteZeit=$wert['TimeStamp'];
                $vorigertag=date("d.m.Y",$zeit);
                }
            return ($leist);
            }

        /* check Config für aggregate15minPower2Energy
         *
         */
        function setConfigAggregate15min($configInput,$meter)
            {
            $config=array();
            configfileParser($configInput,$config,["Update","UPDATE","update"],"Update",false);          //Update Function
            configfileParser($configInput,$config,["StartTime","Starttime","STARTTIME","starttime"],"StartTime",strtotime("-60days"));  
            configfileParser($configInput,$config,["Aggregated","AGGREGATED","aggregated"],"Aggregated",false); 
            configfileParser($configInput,$config,["manAggregate","MANAGGREGATE","managgregate","ManAggregate"],"manAggregate","daily"); 
            $inputId=false;
            if ( (strtoupper($meter["TYPE"])=="DAILYLPREAD") && (isset($meter["LeistungID"])) )
                {
                $inputId = $meter["LeistungID"];
                }
            configfileParser($configInput,$config,["InputID","INPUTID","inputid","InputId"],"InputID",$inputId); 
            $outputId=false;
            if ( (strtoupper($meter["TYPE"])=="DAILYLPREAD") && (isset($meter["WirkenergieID"])) )
                {
                $outputId = $meter["WirkenergieID"];
                }
            configfileParser($configInput,$config,["OutputID","OUTPUTID","outputid","OutputId"],"OutputID",$outputId);             
            configfileParser($configInput,$config,["OutputCounterID","OUTPUTCOUNTERID","outputcounterid","OutputcounterId"],"OutputCounterID",false);
            configfileParser($configInput,$config,["InputValue","INPUTVALUE","inputvalue","inputValue"],"InputValue",0);
            return ($config);              
            }

        /* aggregate15minPower2Energy
         * 
         * aggregate 15min Power Werte to EnergyDaily Wert
         * funktioniert nur wenn meter.TYPE auf DAILYLPREAD steht
         * read $meter["LeistungID"] ist der Input, wird manuell auf Tageswerte umgerechnet
         * und mit getWirkenergieID($meter) verglichen
         *
         * config Parameter:
         *      Update
         *      StartTime
         *      Aggregated
         *      manAggregate
         *      InputID                 Eingabe OID, wenn nicht angegeben, dann aus der Config nehmen
         *      OutputID                Ausgabe OID, wenn nicht angegeben, dann aus der Config nehmen
         *      OutputCounterID         Ausgabe OID für einen Zählwert
         *
         * verwendet getValues
         * aufgerufen von BerechnePeriodenwerte wenn meter[TYPE] DAILYLPREAD oder DAILYREAD ist
         *
         */
        function aggregate15minPower2Energy($meter,$update=false,$debug=false)
            {
            $debug1=$debug;
            if (is_array($update))
                {
                $config=$this->setConfigAggregate15min($update,$meter);
                $update=$config["Update"];  
                }
            else            // keine Config mitgegeben
                {
                $configInput =["Update" => $update];                    
                $config=$this->setConfigAggregate15min($configInput,$meter);
                }

            if ($debug) echo "aggregate15minPower2Energy, mit Config ".json_encode($config)." aufgerufen.\n";
            //$update=false;      //Testbetrieb überschreiben    

            if (strtoupper($meter["TYPE"])=="DAILYLPREAD")
                {
                if ($debug) echo "   Werte von DailyLPRead ".$meter["NAME"]." 15 Min Werte in Tageswerte umrechnen. Es gibt eine Aggregate Funktion.\n";
                $archiveOps = new archiveOps(); 
                $archiveID = $archiveOps->getArchiveID(); 
                $ipsOps = new ipsOps();
                // Inputwerte einsammeln und manuell aggregierte Tageswerte ermitteln und in Variable ergebnis speichern
                if ($config["InputID"]) 
                    {
                    $oid = $config["InputID"];
                    if ($debug) echo "   Leistungs Register $oid ".$ipsOps->path($oid)." mit Wert ".nf(GetValue($oid),"W")." ist bekannt.\n";
                    $ergebnis = $archiveOps->getValues($oid,$config,$debug1);          // Debug Level true,1,2,3 
                    if ($ergebnis===false) $ergebnis=["Values" => [],];                 // wenn empty, dann eine Annahme mit einem leeren Array machen
                    if ($debug) 
                        {
                        echo "   Archivierte Werte bearbeiten, show ".count($ergebnis["Values"]).":\n";                    
                        //$archiveOps->showValues($ergebnis["Values"],[],$debug); 
                        }
                    //print_R($ergebnis["Values"]);             // array mit TimeStamp und Value
                    $timestamp = strtotime("-1days");           // genau einen Tag zurück
                    $timestamp = $ipsOps->adjustTimeFormat($timestamp,"Ymd");
                    //print_R($archiveOps->lookforValues($ergebnis["Values"],$timestamp));
                    $filtered = $archiveOps->lookforValues($ergebnis["Values"],$timestamp);
                    if ($debug)
                        {
                        if ($filtered) echo "   Gestern : ".date("d.m.Y H:i:s",$timestamp)." war der aggregierte Tageswert : ".$filtered["Value"]." kWh\n";
                        else echo "   Gestern : ".date("d.m.Y H:i:s",$timestamp)." wurde kein aggregierter Tageswert ermittelt.\n";
                        }
                    }
                else echo "Warning, meter LeistungID not known, no calculations executed.\n";
                if ($config["OutputID"]) 
                    {
                    // Leistung fertig, was ist mit der Wirkenergie
                    unset($config["manAggregate"]);
                    //$variableID = $this->getWirkenergieID($meter); 
                    $variableID = $config["OutputID"];                          // Ein Energie Register mit tageswerten, kein Counter
                    if ($debug)
                        {
                        echo "   --------------------------------\n";
                        echo "   Zielregister, Tageswerte Wirkenergie $variableID (".$ipsOps->path($variableID).") auch noch anschauen:\n";          // wie bei parsedreiguthaben EVN getKnownData
                        }
                    $ergebnisEnergie = $archiveOps->getValues($variableID,$config,$debug);      // warum erst manual Aggregate, false debug Level
                    if ($ergebnisEnergie===false) $ergebnisEnergie=["Values" => [],];
                    //$ergebnisEnergie = $archiveOps->getValues($oid,$config,2);      // warum erst manual Aggregate
                    //print_r($ergebnisEnergie["Values"]);
                    if ($debug)
                        {
                        echo "--------------------------------\n";                
                        $archiveOps->showValues($ergebnisEnergie["Values"],[],$debug);                //true für Debug
                        }
                    $timeStampknown=array();
                    $deleteIndex = $this->cleanupValuesOnEnergyDaily($timeStampknown, $ergebnisEnergie["Values"]);           // berechnete Tagesenergiewerte nach Zeitstempel indexieren, doppelte Werte zum löschen markieren    
                    /* $deleteIndex=array(); $d=0;
                    if ((isset($ergebnisEnergie["Values"])) && (count($ergebnisEnergie["Values"])>0)) 
                        {
                        foreach ($ergebnisEnergie["Values"] as $wert) 
                            {  
                            $timeStamp=strtotime(date("d.m.Y",$wert["TimeStamp"]));
                            if ($timeStamp != $wert["TimeStamp"])                           // im Archive müssen alle Werte auf 00:00 stehen
                                {
                                echo "falscher Timestamp ".date("d.m.Y H:i:s",$wert["TimeStamp"])." im Archive. Wird gelöscht muss ".date("d.m.Y H:i:s",$timeStamp)."\n";
                                $deleteIndex[$d]["StartTime"]=$wert["TimeStamp"];
                                $deleteIndex[$d]["EndTime"]  =$wert["TimeStamp"];
                                $d++;
                                }                        
                            if (isset($timeStampknown[$timeStamp]))  // es können mehrere Werte an einem tag sein, doppelte Werte rausfinden, Timestamp auf 00:00 des Tages stellen
                                {
                                echo "Zeitstempel ".date("d.m.Y H:i:s",$timeStamp)." mit Wert ".$timeStampknown[$timeStamp]." soll mit Zeitstempel ".date("d.m.Y H:i:s",$wert["TimeStamp"])." mit Wert ".$wert["Value"]." überschrieben werden. Hier löschen.\n";   
                                $deleteIndex[$d]["StartTime"]=$wert["TimeStamp"];
                                $deleteIndex[$d]["EndTime"]  =$wert["TimeStamp"];
                                $d++;
                                }  
                            else $timeStampknown[$timeStamp]=$wert["Value"];
                            }
                        } */
                    // die Ergebniswerte für die Energie nach timestamps indexieren und mit Ergebnis (also den aggregierten tageswerten) vergleichen
                    $d=count($deleteIndex);
                    $input=array();
                    $count=0;
                    if ((isset($ergebnis["Values"])) && (count($ergebnis["Values"])>0)) 
                        {  
                        foreach ($ergebnis["Values"] as $wert) 
                            {
                            if (isset($wert["TimeStamp"]))          // da kommt noch mehr
                                {
                                if (isset($timeStampknown[$wert["TimeStamp"]])) 
                                    {
                                    if (round($timeStampknown[$wert["TimeStamp"]],3) != round($wert["Value"],3)) 
                                        {
                                        echo "Werte bei timestamp ".date("d.m.Y H:i:s",$wert["TimeStamp"])." ungleich:  \"".$timeStampknown[$wert["TimeStamp"]]."\" != \"".$wert["Value"]."\" \n";
                                        //var_dump($timeStampknown[$wert["TimeStamp"]]); var_dump($wert["Value"]);
                                        $deleteIndex[$d]["StartTime"]=$wert["TimeStamp"];
                                        $deleteIndex[$d]["EndTime"]  =$wert["TimeStamp"];
                                        $d++;                                
                                        }
                                    //if ($debug) echo "Wert mit Timestamp ".$wert["TimeStamp"]." hat bereits einen Eintrag ".$wert["Value"]." , überspringen.\n";
                                    }
                                else
                                    {
                                    if ($debug) echo "Wert mit Timestamp ".$wert["TimeStamp"]." hat noch keinen Eintrag ".$wert["Value"]." einfügen.\n";
                                    $input[$count]["TimeStamp"] = $wert["TimeStamp"];
                                    $input[$count]["Value"] = $wert["Value"];
                                    $count++;
                                    }
                                }
                            }
                        }
                    //print_r($input);
                    $delete=count($deleteIndex);
                    $i=0; $start=false; $displayMax=20;

                    echo "Delete Logged Values: $delete from archived Energy Daily Values in $variableID. No Counter.\n";
                    if ($config["Update"] && $delete) 
                        {    
                        foreach ($deleteIndex as $indexDel => $entry)
                            {
                            /*    
                            if ($i++<$displayMax) echo "$indexDel Delete Archive entries ".date("d.m.Y H:i:s",$entry["Index"]).", which is between ".($entry["Index"]-1)." und ".($entry["Index"]+1)."\n";
                            If ($start===false) $start=$entry["Index"];
                            $end=$entry["Index"];
                            // AC_DeleteVariableData (integer $InstanzID, integer $VariablenID, integer $Startzeit, integer $Endzeit)
                            if ($i>2000) break;  */
                            AC_DeleteVariableData ($archiveID, $variableID,$entry["EndTime"],$entry["StartTime"]);          // $start>$end
                            }
                        }
                    else
                        {
                        echo "   No double entries.\n";
                        }
                    $add=count($input);
                    echo "Add Logged Values: $add to archived Energy Daily Values in $variableID. No Counter.\n";
                    if ($config["Update"] && $add)
                        {
                        $archiveID = $archiveOps->getArchiveID();
                        $status=AC_AddLoggedValues($archiveID,$variableID,$input);
                        //echo "Erfolgreich : $status \n";
                        }
                    if ($delete || $add) AC_ReAggregateVariable($archiveID,$variableID);    
                    }
                else echo "no OutputID defined in config. Set Parameter OutputID accordingly.\n";
                if ($config["OutputCounterID"])             // Tagesenergiezähler
                    {
                    $variableID=$config["OutputCounterID"];
                    echo "OutputCounterID found, $variableID ".$ipsOps->path($variableID)." mit Wert ".nf(GetValue($variableID),"kWh")." ist bekannt.\n";
                    if (AC_GetAggregationType($archiveID,$variableID) == 1)
                        {
                        // schauen was es so gibt
                        $timeStampknown=array();
                        $ergebnisEnergieZaehler = $archiveOps->getValues($variableID,$config,$debug);      // warum erst manual Aggregate, false debug Level
                        if ($ergebnisEnergieZaehler===false)            // keine Energiewerte vorhanden
                            {
                            $ergebnisEnergieZaehler=["Values" => [],];
                            $startNewValue=$config["InputValue"];                           // parametrierbar, wenn leeres Array
                            echo "No data found, start with $startNewValue kWh.\n";
                            $first=0; $last=0;
                            }
                        else
                            {
                            if ($debug)
                                {
                                echo "--------------------------------\n";                
                                $archiveOps->showValues($ergebnisEnergieZaehler["Values"],[],$debug);                //true für Debug
                                }
                            $deleteIndex = $this->cleanupValuesOnEnergyDaily($timeStampknown, $ergebnisEnergieZaehler["Values"]);           // berechnete Tagesenergiewerte nach Zeitstempel indexieren, doppelte Werte zum löschen markieren    
                            ksort($timeStampknown);
                            //print_R($timeStampknown); 
                            $first=array_key_first($timeStampknown);
                            $last=array_key_last($timeStampknown);
                            $startNewValue=$timeStampknown[$last];
                            echo "Daten sortiert nach timestamp von $first to $last, start with $startNewValue kWh.\n";
                            }
                        if ((isset($ergebnis["Values"])) && (count($ergebnis["Values"])>0)) 
                            {  
                            echo "Zaehlregister mit Tageswerten erzeugen.\n";
                            $deleteIndex=array();
                            $input=array();
                            $add=0; $delete=count($deleteIndex);
                            $countValue=0;$startValue=0;
                            foreach ($ergebnis["Values"] as $index=>$wert) 
                                {
                                if (isset($wert["TimeStamp"]))         
                                    {
                                    $countValue+=$wert["Value"];
                                    $ergebnis["Values"][$index]["Register"]=$countValue;
                                    echo "Input Daily Power ".date("d.m.Y H:i:s",$wert["TimeStamp"])."   ".str_pad($wert["Value"],12)."    ".str_pad($countValue+$startValue,15)."   ";
                                    if (isset($timeStampknown[$wert["TimeStamp"]])) 
                                        {
                                        //echo "found";
                                        if ( (round($timeStampknown[$wert["TimeStamp"]],3) != round($countValue+$startValue,3)) && ($startValue==0) ) // adjust
                                            {
                                            $startValue=$timeStampknown[$wert["TimeStamp"]]-$countValue;
                                            $startTimeStamp=$wert["TimeStamp"];
                                            echo "adjusted ";
                                            }
                                        if  (round($timeStampknown[$wert["TimeStamp"]],3) != round($countValue+$startValue,3))  // check after adjust
                                            {
                                            echo "Werte bei timestamp ".date("d.m.Y H:i:s",$wert["TimeStamp"])." ungleich:  \"".$timeStampknown[$wert["TimeStamp"]]."\" != \"".$countValue."\" ";
                                            //var_dump($timeStampknown[$wert["TimeStamp"]]); var_dump($wert["Value"]);
                                            $deleteIndex[$delete]["StartTime"]=$wert["TimeStamp"];
                                            $deleteIndex[$delete]["EndTime"]  =$wert["TimeStamp"];
                                            $delete++;                                
                                            }
                                        //if ($debug) echo "Wert mit Timestamp ".$wert["TimeStamp"]." hat bereits einen Eintrag ".$wert["Value"]." , überspringen.\n";
                                        }
                                    else
                                        {
                                        if ($startValue==0)
                                            {
                                            if ($wert["TimeStamp"]>$last) $startValue=$startNewValue;           // startnewvalue ist jetzt der letzte Wert im target register counter
                                            else 
                                                {
                                                echo "Warning, did not consider that , break\n";
                                                echo $wert["TimeStamp"]." <= ".date("d.m.Y H:i:s",$last)." \n";
                                                                                                
                                                //return (false);
                                                }
                                            }
                                        echo "Wert mit Timestamp ".$wert["TimeStamp"]." hat noch keinen Eintrag $countValue einfügen.";
                                        $input[$add]["TimeStamp"] = $wert["TimeStamp"];
                                        $input[$add]["Value"] = $countValue;                            // relativ, finally it is plus startValue
                                        $add++;
                                        }
                                    echo "\n";
                                    }

                                }
                            if ($config["Update"] && $delete) 
                                {    
                                echo "Delete Values Item by item :";
                                foreach ($deleteIndex as $indexDel => $entry)
                                    {
                                    echo ".";
                                    AC_DeleteVariableData ($archiveID, $variableID,$entry["EndTime"],$entry["StartTime"]);          // $start>$end
                                    }
                                echo "\n";                                    
                                }
                            elseif ($delete)            // just talk, dont do
                                {
                                echo "Delete $delete Values planned, but not executed.\n";
                                }   
                            else                                
                                {
                                echo "   No double entries.\n";
                                }
                            if ($config["Update"] && $add)
                                {
                                foreach ($input as $index => $entry)
                                    {
                                    if ( ($first===false) || ($first>$input[$index]["TimeStamp"]) ) $first=$input[$index]["TimeStamp"];
                                    if ( ($last===false) || ($last<$input[$index]["TimeStamp"]) ) $last=$input[$index]["TimeStamp"];
                                    $input[$index]["Value"]+=$startValue;
                                    echo "add ".date("d.m.Y H:i",$input[$index]["TimeStamp"])." ".$input[$index]["Value"]."\n";
                                    }
                                echo "Add Logged Values: $add to archived Energy Daily Values in $variableID.\n";
                                $archiveID = $archiveOps->getArchiveID();
                                $status=AC_AddLoggedValues($archiveID,$variableID,$input);
                                echo "Erfolgreich : $status \n";
                                }
                            elseif ($add)            // just talk, dont do
                                {
                                echo "Add $add Values planned, but not executed.\n";
                                }                                   
                            if ($delete || $add) AC_ReAggregateVariable($archiveID,$variableID);                                    
                            }
                        }
                    else "Aggregation Type ist kein Zähler.\n";
                    }
                }
            else echo "aggregate15minPower2Energy: wrong Type ".strtoupper($meter["TYPE"])." shall be DAILYLPREAD.\n";
            } 

        /* cleanupValuesOnEnergyDaily
         * berechnete Tagesenergiewerte nach Zeitstempel indexieren, doppelte Werte zum löschen markieren
         * liefert eine Tabelle TimeStampKnown mit Index Zeitstempel und alle Werte die gelöscht werden sollen als return
         * Zusatzparameter increment, Werte sollen von einem Zähler kommen, das heisst sie steigen an 
         */
        private function cleanupValuesOnEnergyDaily(&$timeStampknown, $ergebnisEnergie,$increment=0, $debug=false)
            { 
            if ($debug) echo "cleanupValuesOnEnergyDaily  ".count($ergebnisEnergie)." Werte bearbeiten. Typ ist ".($increment?"Zähler":"Messwerte")."\n";
                    $deleteIndex=array(); $d=0;
                    $lastTimeStamp=false;               // Vergleichswerte aufbauen
                    $lastValue=0;  
                    if ((isset($ergebnisEnergie)) && (count($ergebnisEnergie)>0)) 
                        {
                        foreach ($ergebnisEnergie as $wert) 
                            {  
                            $timeStamp=strtotime(date("d.m.Y",$wert["TimeStamp"]));         // nur ein Wert pro Tag 
                            if ($timeStamp != $wert["TimeStamp"])                           // im Archive müssen alle Werte auf 00:00 stehen
                                {
                                echo "falscher Timestamp ".date("d.m.Y H:i:s",$wert["TimeStamp"])." mit Wert ".$wert["Value"]." im Archive. Wird gelöscht muss ".date("d.m.Y H:i:s",$timeStamp)." sein.\n";
                                $deleteIndex[$d]["StartTime"]=$wert["TimeStamp"];
                                $deleteIndex[$d]["EndTime"]  =$wert["TimeStamp"];
                                $d++;
                                }                        
                            if (isset($timeStampknown[$timeStamp]))  // es können mehrere Werte an einem tag sein, doppelte Werte rausfinden, Timestamp auf 00:00 des Tages stellen
                                {
                                echo "Zeitstempel ".date("d.m.Y H:i:s",$timeStamp)." mit Wert ".$timeStampknown[$timeStamp]." soll mit Zeitstempel ".date("d.m.Y H:i:s",$wert["TimeStamp"])." mit Wert ".$wert["Value"]." überschrieben werden. Hier löschen.\n";   
                                $deleteIndex[$d]["StartTime"]=$wert["TimeStamp"];
                                $deleteIndex[$d]["EndTime"]  =$wert["TimeStamp"];
                                $d++;
                                }  
                            else                // noch kein Eintrag bei den Targetwerten im Archiv
                                {
                                if ($lastTimeStamp===false) 
                                    {
                                    $lastTimeStamp=$timeStamp; 
                                    $lastValue=$wert["Value"]; 
                                    $timeStampknown[$timeStamp]=$wert["Value"];
                                    }
                                else
                                    {
                                    if ($lastTimeStamp>$timeStamp) echo "Timestamps do not increment\n";
                                    elseif ( ($increment) && ($lastValue > $wert["Value"]) )
                                        {
                                        echo "Zeitstempel ".date("d.m.Y H:i:s",$timeStamp)." Werte do not increase, no counter.($lastValue > ".$wert["Value"].") \n";
                                        $deleteIndex[$d]["StartTime"]=$wert["TimeStamp"];
                                        $deleteIndex[$d]["EndTime"]  =$wert["TimeStamp"];
                                        $d++;
                                        }
                                    else $timeStampknown[$timeStamp]=$wert["Value"];
                                    }
                                }
                            }
                        }
            return ($deleteIndex);
            }

        /******************************************************************************************************************/

        /*
         * Routinen werden für die Auswertung der Datenströme vom Zähler und zusätzlichen Berechnungen verwendet
         */

        function do_register($config,$content,$zaehlerid)
            {
            echo "Alle konfigurierten Register auslesen:\n";
            //print_r($config["Register"]);
            foreach ($config as $name => $filterConf)
                {
                echo " *   $name Filter : ".$filterConf[0]."%".$filterConf[1]."  Variablenprofil : ".$filterConf[2]."\n"; 
                if ((count($filterConf))==3)
                    {   
                    //print_r($filterConf);
                    anfrage($name, $filterConf[0],$filterConf[1],$content,2,$filterConf[2],$this->archiveHandlerID,$zaehlerid,true);
                    }
                }   
            }

        function do_calculate($config,$content,$zaehlerid)
            {
            echo "Weitere Register kalkulieren:\n";
            foreach ($config as $name => $filterConf)
                {
                if ((count($filterConf))==4)
                    {
                    echo " *   $name  : ".$filterConf[0]." mit Variable 1 : ".$filterConf[1]."  Variable 2 : ".$filterConf[2]."  Variablenprofil : ".$filterConf[3]."\n"; 
                    switch (strtoupper($filterConf[0]))
                        {
                        case "MULTIPLY":     
                            $var1ID=IPS_GetObjectIDByName($filterConf[1],$zaehlerid);
                            $var2ID=IPS_GetObjectIDByName($filterConf[2],$zaehlerid);                         
                            if ( ($var1ID !== false) && ($var2ID !== false) )
                                {                                    
                                $wert=(GetValue($var1ID)*GetValue($var2ID))/1000;
                                echo "    Wert berechnet : $wert kW\n"; 
                                vars($this->archiveHandlerID, $zaehlerid, $name, $wert, 2, $filterConf[3]);                                    
                                //anfrage($name, $filterConf[0],$filterConf[1],$content,2,$filterConf[2],$arhid,$zaehlerid,true);
                                }
                            break;                                
                        case "MAX":     
                            $var1ID=IPS_GetObjectIDByName($filterConf[1],$zaehlerid);
                            $timeBack=strtotime("-".$filterConf[2]);
                            echo "     Datum ab : ".date("D d.m.Y H:i:s",$timeBack)."\n";
                            if ($var1ID !== false)
                                {
                                $maxWert = $this->getArchiveDataMax($var1ID, $timeBack, time());  
                                echo "    Wert berechnet ".$filterConf[1]." $var1ID (".$this->ipsOps->path($var1ID).")   MaxWert : $maxWert \n";
                                vars($this->archiveHandlerID, $zaehlerid, $name, $maxWert, 2, $filterConf[3]);   	
                                }
                            
                            break;
                        default:
                            print_r($filterConf);
                            break;                        
                        }
                    }
                }       // ende foreach
            }										


        /******************************************************
        *
        * Summestartende,
        *
        * Gemeinschaftsfunktion, fuer die manuelle Aggregation von historisierten Daten, vergleiche Versionen in Allgemeinedefinitionen und archive class
        *
        * Eingabe Beginnzeit Format time(), Endzeit Format time(), 0 Statuswert 1 Inkrementwert 2 test, Anzahl der Werte, Variable OID, Debug
        * estimate   false ohne Hochrechnung, abgelöst durch count
        * count      false, oder anzahl der maximal berücksichtigten Werte für die Summe
        * Parameter  increment_var muss immer 1 sein.
        *
        * Verwendet aggregated Archive data. Abhängig vom Archiveparameter Counter oder Register werden die Summen berechnet. Ergebnis sind aber immer Einzelwerte
        * Es werden bereits täglich aggregierte Werte aus dem Archive ausgelesen, es sollte Standard oder Zähler beim Archiv richtig gesetzt sein
        *
        * Routine fehlerhaft bei Ende Sommerzeit, hier wird als Startzeit -30 Tage eine Stunde zu wenig berechnet 
        *
        ******************************************************************************************/

        function summestartende($starttime, $endtime, $increment_var, $count, $variableID, $debug=false )
            {
            if ($debug)
                {
                echo "Aufruf summestartende: Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
                echo "ArchiveHandler: ".$this->archiveHandlerID." Variable: $variableID (".$this->ipsOps->path($variableID).")\n";                   
                }
            $zaehler=0;
            $ergebnis=0;
            $increment=(integer)$increment_var;         // Art der Berechnung 0,1,2
            $countAct=0;
                
            do {
                /* es könnten mehr als 10.000 Werte sein
                    Abfrage generisch lassen
                */
                
                // Eintraege für GetAggregated integer $InstanzID, integer $VariablenID, integer $Aggregationsstufe, integer $Startzeit, integer $Endzeit, integer $Limit
                $aggWerte = @AC_GetAggregatedValues ( $this->archiveHandlerID, $variableID, 1, $starttime, $endtime, 0 );
                if ($aggWerte === false) 
                    {
                    echo "  Fehler, Variable $variableID ".IPS_GetName($variableID)." neu aggregieren. Dauert etwas.\n";
                    AC_ReAggregateVariable ($this->archiveHandlerID, $variableID);              // Reperaturversuch                 
                    $aggWerte = AC_GetAggregatedValues ( $this->archiveHandlerID, $variableID, 1, $starttime, $endtime, 0 );
                    throw new Exception("AC_GetAggregatedValues, Fehler beim Aggregieren - wird automatisch repariert.");
                    }
                $aggAnzahl=count($aggWerte);
                //print_r($aggWerte);
                foreach ($aggWerte as $entry)
                    {
                    if (((time()-$entry["MinTime"])/60/60/24)>1)            // keine halben Tage ausgeben, aktuelle Zeit minus mehr als 24 Stunden zum Start, vom 18.1. 11:00 auf den 17.1. 00:00
                        {
                        $aktwert=(float)$entry["Avg"];
                        if ($debug>1) echo "     ".date("D d.m.Y H:i:s",$entry["TimeStamp"])."      ".$aktwert."\n";
                        switch ($increment)
                            {
                            case 0:
                            case 2:
                                echo "*************Fehler.\n";
                                break;
                            case 1:        // Statuswert, daher kompletten Bereich zusammenzählen
                                $countAct++;                // auf 1 nach dem ersten Wert
                                if ($count)                 // wenn Count ungleich 0 wird verglichen, sonst unbeschränkt addiert
                                    {
                                    if ($countAct<=$count) $ergebnis+=$aktwert;
                                    }
                                else $ergebnis+=$aktwert;
                                break;
                            default:
                            }
                        }
                    else
                        {
                        $aggAnzahl--;
                        }	
                    }
                if (($aggAnzahl == 0) & ($zaehler == 0)) {return 0;}   // hartes Ende wenn keine Werte vorhanden
                
                $zaehler+=1;
                    
                } while (count($aggWerte)==10000);		
            if ($debug) echo "   Variable: ".IPS_GetName($variableID)." mit ".$aggAnzahl." Tageswerten, davon berücksichtigt $count und ".$ergebnis." als aggregiertes Ergebnis.\n";
            $this->result["Function"]="summestartende";
            $this->result["Count"]=$count;
            $this->result["DailyValues"]=$aggAnzahl;
            $this->result["VariableID"]=$variableID;
            return $ergebnis;
            }

        /* zusätzliche Ergebnisse der berechnungen übermitteln, implementiert für
         *      summestartende
         *
         *
         */
        public function getFunctionResult()
            {
            return ($this->result);
            }
			
		}  // ende class

    /*************************************************************************************************
     * 
     * Erweiterung für Smart Meter Funktionen
     *
     * verwendet getMeterConfig um alle AMIS Register herauszufinden und abzuarbeiten
     *
     *      __construct
     *      writeSmartMeterDataToHtml
     *      writeSmartMeterCsvInfoToHtml
     *
     */
	class AmisSmartMeter extends Amis
        {

        protected $guthabenHandler;

        function __construct($debug=false)
            {
            parent::__construct($debug);
            if ( (isset($this->installedModules["Guthabensteuerung"])) && (class_exists("GuthabenHandler")) )
                {
                $this->guthabenHandler = new GuthabenHandler();                  //default keine Ausgabe, speichern
                }
            else 
                {
                if ($debug) echo "Warning: AmisSmartMeter::new, class GuthabenHandler not available. Use Modul and include Library to get full functionality.\n";
                $this->guthabenHandler = false;
                }
            }

        function readDirectory($config,$debug=false)
            {
            $dosOps = new dosOps();
                if (isset($config["InputDir"]))  $dir=$config["InputDir"];                      //Input Verzeichnis suchen 
                else $dir="Energy";
                $verzeichnis=$dosOps->getWorkDirectory();
                $inputDir = $dosOps->correctDirName($verzeichnis.$dir);  
                $files=$dosOps->readdirToArray($inputDir);
                if (isset($config["InputFile"])) $filename=$config["InputFile"];
                else $filename="*.csv";
                if ($debug) echo "   Input Filename is \"$filename\" in $inputDir.\n";
                $filesFound = $dosOps->findfiles($files,$filename,false);           // true für Debug
                foreach ($filesFound as $key=>$data) $filesFound[$key] = $inputDir.$data;
            return ($filesFound);
            }


        /* AmisSmartMeter::writeSmartMeterDataToHtml
         * die Register mit Werten von Smart metern gemeinsam als html Tabelle darstellen
         * für eine ajax Abfrage die Tabelle anders darstellen
         *
         * nur die Types DAILYREAD,DAILYLPREAD verarbeiten
         */
        function writeSmartMeterDataToHtml($html=true, $debug=false)
            {
            //echo "Button 0";                              // das sind die drei Button links
            $webOps = new webOps();
            $archiveOps = new archiveOps();                // allgemeines Init

            $MeterConfig = $this->getMeterConfig();

            $endtime=time();
            $starttime=$endtime-60*60*24*60;            // letzte 60 Tage

            $html="";
            $result=array();        $index=1;
            /*      Info Source
             *
             */
            
            /*
            $html .= '<style>';
            $html .= '.cuwContainer { width: auto; height: auto; max-height:95%; max-width: 100%; background-color: #1f1f1f; }';
            $html .= '</style>';
            */
            if ($debug) echo "Show Smart Meter Types from Configuration :\n";
            $html .= '<div class="cuw-quick">';
            $html .= '<table>';

            foreach ($MeterConfig as $identifier => $meter)
                {
                $html .= "<tr>";
                $ID = IPS_GetObjectIdByName($meter["NAME"], $this->CategoryIdData);   
                switch (strtoupper($meter["TYPE"]))
                    {
                    case "DAILYREAD":
                    case "DAILYLPREAD":
                        $result["Info"]=$meter["TYPE"]." ".$meter["NAME"]." mit ID : ".$identifier;
                        $html .= "<td>".$result["Info"]."</td>";
                        if (isset($meter["Source"])) $result["Source"]='Quelle der Daten kommt von '.$meter["Source"];
                        else $result["Source"]="";
                        if ($debug) echo $meter["TYPE"]." ".$meter["NAME"]." mit ID : ".$identifier.",  ".$result["Source"]."\n";
                        $html .= '<td colspan="4">'.$result["Source"]."</td>";
                        $html .= "</tr>";
                        $html .= "<tr>";
                        $html .= "<th>Bezeichnung</th><th>OID</th><th>Anzahl</th><th>Periode</th><th>Intervall</th><th>Erster Eintrag</th><th>Letzter Eintrag</th><th>Pfad</th>";
                        $html .= "</tr>";
                        $variableID = IPS_GetObjectIdByName('Wirkenergie', $ID);
                        $smEnergyCounterArchiveOps = new archiveOps($variableID);
                        $smEnergyCounterConfig = $smEnergyCounterArchiveOps->getConfig();
                        //print_r($smEnergyCounterConfig);    
                        $count = $smEnergyCounterConfig["RecordCount"];
                        $periode = $smEnergyCounterConfig["LastTime"] - $smEnergyCounterConfig["FirstTime"];
                        if ($count>1) $interval = $periode/($count-1);            // Abstände nicht Anzahl
                        else $interval = false;
                        if ($debug) 
                            {
                            echo "   ".$meter["TYPE"]." ".$meter["NAME"]." mit ID : ".$identifier." \n";
                            echo "    ".str_pad("Smart Meter Wirkenergie ".($smEnergyCounterConfig["AggregationType"]?"Zaehler":"Werte")." :",55)." $count Einträge, Periode ".nf($periode,"s")." Intervall ".nf($interval,"s")." letzter Wert vom ".date("d.m.Y H:i",$smEnergyCounterConfig["LastTime"])." ID : $variableID . Wert wurde berechnet.";
                            echo " Pfad: ".$this->ipsOps->path($variableID)."  \n";
                            }
                        
                        $result["Table"][$index]["Bezeichnung"]="Smart Meter Wirkenergie".($smEnergyCounterConfig["AggregationType"]?"Zaehler":"Werte");
                        $result["Table"][$index]["OID"]=$variableID;
                        $result["Table"][$index]["Anzahl"]=$count;
                        $result["Table"][$index]["Periode"]="";
                        $result["Table"][$index]["Intervall"]=nf($interval,"s");
                        $result["Table"][$index]["ErsterEintrag"]=date("d.m.Y H:i",$smEnergyCounterConfig["FirstTime"]);
                        $result["Table"][$index]["LetzterEintrag"]=date("d.m.Y H:i",$smEnergyCounterConfig["LastTime"]);
                        $result["Table"][$index]["Pfad"]=$this->ipsOps->path($variableID);

                        $html .= "<tr>";
                        $html .= "<td>".$result["Table"][$index]["Bezeichnung"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["OID"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Anzahl"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Periode"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Intervall"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["ErsterEintrag"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["LetzterEintrag"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Pfad"]."</td>";
                        $html .= "</tr>";
                        $index++;

                        /*$archiveID = $smEnergyCounterArchiveOps->getArchiveID();
                        AC_DeleteVariableData ($archiveID, $variableID,strtotime("24.08.2021 00:00"),strtotime("24.10.2021 00:00"));          // $start>$end    
                        AC_ReaggregateVariable ($archiveID, $variableID);
                        */ 
                        $variableLogWienID = $this->getWirkenergieID($meter); 
                        $smEnergyWebArchiveOps = new archiveOps($variableLogWienID);
                        $smEnergyWebConfig = $smEnergyWebArchiveOps->getConfig();
                        //print_r($smEnergyWebConfig);    
                        $count = $smEnergyWebConfig["RecordCount"];
                        $periode = $smEnergyWebConfig["LastTime"] - $smEnergyWebConfig["FirstTime"];
                        if ($count>1) $interval = $periode/($count-1);            // Abstände nicht Anzahl
                        else $interval = false;
                        if ($debug) 
                            {
                            echo "    ".str_pad("Smart Meter Wirkenergie ".($smEnergyWebConfig["AggregationType"]?"Zaehler":"Werte")." von ".$meter["Source"].":",55)." $count Einträge, Periode ".nf($periode,"s")." Intervall ".nf($interval,"s")." letzter Wert vom ".date("d.m.Y H:i",$smEnergyWebConfig["LastTime"])." ID : $variableLogWienID . Wert wurde berechnet.";
                            echo " Pfad: ".$this->ipsOps->path($variableLogWienID)."  \n";
                            }

                        $result["Table"][$index]["Bezeichnung"]="Smart Meter Wirkenergie".($smEnergyWebConfig["AggregationType"]?"Zaehler":"Werte")." von ".$meter["Source"];
                        $result["Table"][$index]["OID"]=$variableLogWienID;
                        $result["Table"][$index]["Anzahl"]=$count;
                        $result["Table"][$index]["Periode"]=nf($periode,"s");
                        $result["Table"][$index]["Intervall"]=nf($interval,"s");
                        $result["Table"][$index]["ErsterEintrag"]=date("d.m.Y H:i",$smEnergyWebConfig["FirstTime"]);
                        $result["Table"][$index]["LetzterEintrag"]=date("d.m.Y H:i",$smEnergyWebConfig["LastTime"]);
                        $result["Table"][$index]["Pfad"]=$this->ipsOps->path($variableLogWienID);

                        $html .= "<tr>";
                        $html .= "<td>".$result["Table"][$index]["Bezeichnung"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["OID"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Anzahl"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Periode"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Intervall"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["ErsterEintrag"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["LetzterEintrag"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Pfad"]."</td>";
                        $html .= "</tr>";
                        $index++;

                        if (isset($meter["LeistungID"])===false) 
                            {
                            $inputID=$this->getWirkleistungID($meter,false);                //true for Debug
                            //echo "Warning, setMeterConfig, OID Identifier must be provided for TYPE DAILYREAD of ".$meter["NAME"].". Found one by searching: $inputID\n";
                            } 
                        else $inputID=$meter["LeistungID"];
                        $smPowerInputArchiveOps = new archiveOps($inputID);
                        $smPowerInputConfig = $smPowerInputArchiveOps->getConfig();
                        //print_r($smEnergyWebConfig);    
                        $count = $smPowerInputConfig["RecordCount"];
                        $periode = $smPowerInputConfig["LastTime"] - $smPowerInputConfig["FirstTime"];
                        if ($count>1) $interval = $periode/($count-1);            // Abstände nicht Anzahl
                        else $interval = false;
                        if ($debug) 
                            {
                            echo "    ".str_pad("Smart Meter Wirkleistung ".($smPowerInputConfig["AggregationType"]?"Zaehler":"Werte")." von InputCsv:",55)." $count Einträge, Periode ".nf($periode,"s")." Intervall ".nf($interval,"s")." letzter Wert vom ".date("d.m.Y H:i",$smPowerInputConfig["LastTime"])." ID : $inputID . Wert wurde von File eingelesen.";
                            echo " Pfad: ".$this->ipsOps->path($inputID)."  \n";
                            }

                        $result["Table"][$index]["Bezeichnung"]="Smart Meter Wirkleistung ".($smPowerInputConfig["AggregationType"]?"Zaehler":"Werte")." von InputCsv";
                        $result["Table"][$index]["OID"]=$inputID;
                        $result["Table"][$index]["Anzahl"]=$count;
                        $result["Table"][$index]["Periode"]=nf($periode,"s");
                        $result["Table"][$index]["Intervall"]=nf($interval,"s");
                        $result["Table"][$index]["ErsterEintrag"]=date("d.m.Y H:i",$smPowerInputConfig["FirstTime"]);
                        $result["Table"][$index]["LetzterEintrag"]=date("d.m.Y H:i",$smPowerInputConfig["LastTime"]);
                        $result["Table"][$index]["Pfad"]=$this->ipsOps->path($inputID);

                        $html .= "<tr>";
                        $html .= "<td>".$result["Table"][$index]["Bezeichnung"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["OID"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Anzahl"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Periode"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Intervall"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["ErsterEintrag"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["LetzterEintrag"]."</td>";
                        $html .= "<td>".$result["Table"][$index]["Pfad"]."</td>";
                        $html .= "</tr>";
                        $index++;

                        //echo "\n";
                        if (isset($meter["Source"]))
                            {
                            if ($this->debug) echo "Quelle der Daten kommt von ".$meter["Source"]."   \n";
                            switch ($meter["Source"])
                                {
                                case "LOGWIEN":
                                case "logwien":
                                case "LogWien":
                                case "log.wien":
                                    /* brauch ich diesen Teil wirklich ?
                                    IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
                                    $configLogWien = $this->guthabenHandler->getSeleniumHostsConfig()["Hosts"]["LogWien"]["CONFIG"];
                                    //print_R($configLogWien);
                                    $seleniumLogWien = new SeleniumLogWien();
                                    $seleniumOperations = new SeleniumOperations();
                                    $variableLogWienID = $seleniumOperations->defineTargetID("LogWien",$configLogWien); 
                                    */       
                                    if ($this->debug) echo "    ".$meter["Source"]." Werte werden hier gespeichert: $variableLogWienID \n";
                                    //$variableLogWienID=$seleniumOperations->getResultID("LogWien","Result",true);                  // true Debug
                                    //print_R($result);
                                    //echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";
                                    //$variableLogWienID = $seleniumLogWien->getEnergyValueId("EnergyCounter");
                                    $smEnergyLogWienArchiveOps = new archiveOps($variableLogWienID);
                                    $smEnergyLogWienConfig = $smEnergyLogWienArchiveOps->getConfig();
                                    //print_r($smEnergyLogWienConfig);    
                                    $count = $smEnergyLogWienConfig["RecordCount"];
                                    $periode = $smEnergyLogWienConfig["LastTime"] - $smEnergyLogWienConfig["FirstTime"];
                                    if ($count>1) $interval = $periode/($count-1);            // Abstände nicht Anzahl
                                    else $interval = false;
                                    if ($this->debug) 
                                        {
                                        echo "    ".str_pad("Smart Meter Wirkenergie ".($smEnergyLogWienConfig["AggregationType"]?"Zaehler":"Werte")." von Selenium ".$meter["Source"].":",55)." $count Einträge, Periode ".nf($periode,"s")." Intervall ".nf($interval,"s")." letzter Wert vom ".date("d.m.Y H:i",$smEnergyLogWienConfig["LastTime"])." ID : $variableLogWienID . Wert wurde mit Selenium geladen.";
                                        echo " Pfad: ".$this->ipsOps->path($variableLogWienID)."  \n";
                                        }

                                    $result["Table"][$index]["Bezeichnung"]="Smart Meter Wirkenergie ".($smEnergyLogWienConfig["AggregationType"]?"Zaehler":"Werte")." von Selenium ".$meter["Source"];
                                    $result["Table"][$index]["OID"]=$variableLogWienID;
                                    $result["Table"][$index]["Anzahl"]=$count;
                                    $result["Table"][$index]["Periode"]=nf($periode,"s");
                                    $result["Table"][$index]["Intervall"]=nf($interval,"s");
                                    $result["Table"][$index]["ErsterEintrag"]=date("d.m.Y H:i",$smEnergyLogWienConfig["FirstTime"]);
                                    $result["Table"][$index]["LetzterEintrag"]=date("d.m.Y H:i",$smEnergyLogWienConfig["LastTime"]);
                                    $result["Table"][$index]["Pfad"]=$this->ipsOps->path($variableLogWienID);

                                    $html .= "<tr>";
                                    $html .= "<td>".$result["Table"][$index]["Bezeichnung"]."</td>";
                                    $html .= "<td>".$result["Table"][$index]["OID"]."</td>";
                                    $html .= "<td>".$result["Table"][$index]["Anzahl"]."</td>";
                                    $html .= "<td>".$result["Table"][$index]["Periode"]."</td>";
                                    $html .= "<td>".$result["Table"][$index]["Intervall"]."</td>";
                                    $html .= "<td>".$result["Table"][$index]["ErsterEintrag"]."</td>";
                                    $html .= "<td>".$result["Table"][$index]["LetzterEintrag"]."</td>";
                                    $html .= "<td>".$result["Table"][$index]["Pfad"]."</td>";
                                    $html .= "</tr>";
                                    $index++;
                                    break;
                                case "default":
                                default:
                                    break;
                                }
                            }
                        else print_R($meter);

                        break;
                    }                   // ende switch
                //$html .= "</tr>";            
                }                       // ende foreach
            $html .= "</table>";
            $html .= "</div>";
            //echo "\n";
            if ($html) return ($html);
            else return (json_encode($result));
            }

        /* writeSmartMeterCsvInfoToHtml
         * ähnliche Funktion auch in archiveOps addValuesfromCsv
         *
         * es gibt ein csv Verzeichnis, die Dateien darin anzeigen mit den relevanten verfügbaren Informationen
         * es gibt eine Steuerung mit cmd als array oder string, könnte man auch als class variable machen
         * folgende Commands werden unterstützt:
         * Format cmd oder "cmd"=>cmd, "Sort"=sort
         *      cmd default ist false, sort ist false, html Ausgabe ist true
         *      wenn cmd ein arra ist gilt
         *          Sort
         *          Html
         *          Cmd
         *
         * Cmd kann die folgenden werte annehmen
         *      header          header ist fix und ist die erste Zeile ohne ZwischenBlanks
         *      config          die INPUTCSV Config
         *      files           die files als array
         *      inputDir
         *      TargetID
         *
         * die Ausgabe als html
         *      div class="cuw-quick"       siehe css Datei in webfront/skins/SkinDark/webfront.css
         *
         * die Ausgabe als json
         *      header =>
         *      table  =>
         *
         */
        function writeSmartMeterCsvInfoToHtml($cmd=false,$config=false,$debug=false)
            {
            //if ($debug===false) $debug=$this->debug;
            $sort=false; $modeHtml=true; $headerIndex=array(); $headerSort=array();
            echo "writeSmartMeterCsvInfoToHtml Command $cmd\n";
            if ($config===false) 
                {
                if ($this->guthabenHandler===false)
                    {
                    echo "   writeSmartMeterCsvInfoToHtml, warning config Array as Parameter 2 needed.\n";
                    return(false);    
                    }
                $config = $this->guthabenHandler->getSeleniumHostsConfig()["Hosts"];
                }
            if (is_array($cmd))
                {
                if ($debug) echo "   Aufruf mit Parameter ".json_encode($cmd)."\n"; 
                if (isset($cmd["Sort"])) $sort = $cmd["Sort"];
                if (isset($cmd["Html"]))  $modeHtml = $cmd["Html"];            // wenn false Ausgabe als json
                //print_R($cmd);
                if (isset($cmd["Cmd"]))  $cmd  = $cmd["Cmd"];
                else $cmd=false;
                }
            if ($debug && $modeHtml) echo "   Ausgabe als html formatierten String.\n"; 
            $html = ""; $json=array(); $sub=1;
            //$header = array("Verzeichnis","Filename","Size","FileDate","Erster Eintrag","Letzter Eintrag","Anzahl","Interval","Periode","Analyze");
            //Verzeichnis - Filename - Size - DateTime - FirstDate - LastDate - Count - Intervall - Periode - Analyze
            $headerAssign = array("Verzeichnis"=>"Verzeichnis","Filename"=>"Filename","Size"=>"Size","DateTime"=>"FileDate","FirstDate"=>"Erster Eintrag","LastDate"=>"Letzter Eintrag","Count"=>"Anzahl","Interval"=>"Intervall","Periode"=>"Periode","Analyze"=>"Analyze");
            $headerShow = array("Filename","Size","DateTime","FirstDate","LastDate","Count","Interval","Periode","Analyze");            // keys für Auswahl der Spalten verwenden

            foreach ($headerShow as $index => $entry) 
                {
                $name = $headerAssign[$entry];
                $json["Header"][$index]["Column"]=$name;                         // index erhöht sich automatisch, explizite Speicherung damit json_encode kompatible zu javascript
                $json["Header"][$index]["Key"]=$entry;
                $headerIndex[$index]=str_replace(" ", "",$name);
                $headerSort[$headerIndex[$index]] = $name;
                $header[$index]=$name;                                           // simple list of Column Names in right order
                }
            //echo json_encode($headerSort);
            ini_set('memory_limit', '128M');                        // können grosse Dateien werden            
            if (strtoupper($cmd)=="HEADER") return ($headerIndex);            // Header ausgeben, damit Befehle erkannt werden können
            $dosOps = new dosOps();
            $archiveOps = new archiveOps();            
            foreach ($config as $host => $entry)
                {
                switch (strtoupper($host))
                    {
                    case "FILE":
                        if ($debug) echo "   Bearbeite FILE Input, Command: \"$cmd\" InputCsv : ".json_encode($entry["INPUTCSV"])."\n";

                        break;
                    case "EVN":
                    case "LOGWIEN":   
                        if ($debug) echo "   Bearbeite EVN und log.Wien, Command: \"$cmd\" InputCsv : ".json_encode($entry["INPUTCSV"])."\n";   
                     /* if (strtoupper($cmd)=="CONFIG") return($entry["INPUTCSV"]);
                        $selenium = new SeleniumHandler();                       // gemeinsam für log.Wien und EVN, übergeordnet
                        $filesToRead = $selenium->getInputCsvFiles($entry["INPUTCSV"],$this->debug);              // true Debug
                        if (strtoupper($cmd)=="FILES") 
                            {
                            $files = array();
                            foreach ($filesToRead as $entry) $files[]=str_replace(" ", "",$entry["Filename"]);
                            return ($files);            // Header ausgeben, damit Befehle erkannt werden können
                            }
                        if (strtoupper($cmd)=="INPUTDIR") return ($selenium->getDirInputCsv($entry["INPUTCSV"]));            // Header ausgeben, damit Befehle erkannt werden können
                        $targetId=$selenium->getTargetIdInputCsv($entry["INPUTCSV"];
                        if (strtoupper($cmd)=="TARGETID") return ($targetId);
                          */
                        break;
                    default:
                        break;
                    }
                $config = $entry["INPUTCSV"];
                if (strtoupper($cmd)=="CONFIG") return(json_encode($config));
                if (isset($config["InputDir"]))  $dir=$config["InputDir"];                      //Input Verzeichnis suchen 
                else $dir="Energy";
                $verzeichnis=$dosOps->getWorkDirectory();
                $inputDir = $dosOps->correctDirName($verzeichnis.$dir);  
                if (strtoupper($cmd)=="INPUTDIR") return ($inputDir);                               
                $files=$dosOps->readdirToArray($inputDir);
                $filename=$config["InputFile"];
                if (isset($config["InputFile"])) $filename=$config["InputFile"];
                else $filename="*.csv";
                if ($debug) echo "   Input Filename is \"$filename\" in $inputDir.\n";
                $filesFound = $dosOps->findfiles($files,$filename,false);           // true für Debug
                //look for "Index","Key","Format","Result" in config
                $configCsv=$archiveOps->setConfigForAddValues($config);           //parse config file, done twice, also in readFileCsv
                $indexCols=$configCsv["Index"];
                $debug1=$debug;
                //$debug1=false;
                $fileinfo = $dosOps->writeDirToArray($inputDir, $filesFound); 
                //print_R($fileinfo);   
                //print_r($filesFound);                                                  // das sind nur die Filenamen und Directories
                //$files = $fileinfo;                     // files wird von readFileCsv ergänzt um Einzelwerte
                foreach ($filesFound as $file)
                    {
                    $result=array();                                // keine Summe aus allen Dateien machen, sondern Datei für Datei auswerten, daher vorher immer init
                    $dateityp=@filetype( $inputDir.$file );     
                    if ($debug) echo "      Check $dateityp $inputDir$file mit Dateityp ".json_encode($dateityp)."\n";
                    if ($dateityp == "file")
                        {
                        $index=false;
                        foreach ($fileinfo as $index => $entry) 
                            {
                            if (isset($entry["Filename"]))
                                {
                                if ($entry["Filename"] == $file) break;     
                                }
                            else { echo "     Warning, no Filename as index where we can add information : ".json_encode($entry)."\n"; return (false); }
                            }
                        if ($debug) echo "      Filename $file als $index gefunden.\n";               
                        $fileOps = new fileOps($inputDir.$file);             // Filenamen gleich mit übergeben, Datei bleibt in der Instanz hinterlegt
                        //$index=[];                            // erste Zeile als Index übernehmen
                        //$index=["Date","Time","Value"];         // Date und Time werden gemerged
                        //if (isset($config$index=["DateTime","Value","Estimate","Dummy"];                                             // Spalten die nicht übernommen werden sollen sind mit Indexwert false
                        //$index=["DateTime","Value","Estimate","Dummy"];
                                                    // Ergebnis
                        if (strtoupper($cmd)=="FIRSTLINE") return($fileOps->readFileCsvFirstline(false,false));
                        if ($debug1) echo "readFileCsv mit Config ".json_encode($configCsv)." und Index ".json_encode($indexCols)." aufgerufen.\n";       // readFileCsv(&$result, $key="", $index=array(), $filter=array(), $debug=false)
                        $status = $fileOps->readFileCsv($result,$configCsv,$indexCols,[],$debug1);                  // Archive als Input, status liefert zu wenig Informationen
                        //print_r($fileOps->analyze);
                        //print_R($status);
                        // analyse Status
                        if ($status===false) return(false);
                        //echo "-----------\n";  print_r($status["columns"]);
                        $firstDate = array_key_first($status["lines"]);
                        $lastDate  = array_key_last ($status["lines"]);
                        $periode = $lastDate-$firstDate;
                        $count=sizeof($status["lines"]);
                        $interval=$periode/($count-1);
                        if ($debug) echo "beinhaltet $count Werte von ".date("d.m.Y H:i",$firstDate)." bis ".date("d.m.Y H:i",$lastDate)." , Periode ".nf($periode,"s")." Intervall ".nf($interval,"s")." \n";
                        $fileinfo[$index]["Count"] = $count;
                        $fileinfo[$index]["FirstDate"] = $firstDate;
                        $fileinfo[$index]["LastDate"]  = $lastDate;
                        $fileinfo[$index]["Periode"]  = $periode;
                        $fileinfo[$index]["Interval"]  = $interval;
                        $fileinfo[$index]["Analyze"]  = $fileOps->analyze;
                        // analyse Result
                        //if ($debug) { $i=0; foreach ($result as $line => $data) { echo $line; print_R($data); if ($i++>10) break; } }
                        echo "ConfigCsv ".json_encode($configCsv)."\n";
                        $analyze=array();
                        foreach ($result as $line => $data)
                            {
                            foreach ($configCsv["Result"]["Column"] as $idx => $column) 
                                {
                                if ( (isset($data[$column])) && ($data[$column]!="") )
                                    {
                                    $analyze[$column]["End"]=$line;
                                    if (isset($analyze[$column]["Start"])===false) $analyze[$column]["Start"]=$line;
                                    }
                                }
                            }
                        //print_R($analyze);
                        foreach ($analyze as $idx => $coldata)
                            {
                            echo "     ".str_pad($idx,12).date("d.m.y H:i",$coldata["Start"])."   ".date("d.m.y H:i",$coldata["End"])."\n";
                            }
                        if (isset($config["Target"])) 
                            {
                            echo "Target defined, store data accordingly : ".json_encode($config["Target"])."\n";
                            if (isset($config["Target"]["OID"])) $oid=$config["Target"]["OID"];
                            if (isset($config["Target"]["Column"])) $source=$config["Target"]["Column"];
                            $j=0;
                            foreach ($result as $line => $data)
                                {
                                if ($line >= $analyze[$source]["Start"])
                                    {
                                    echo date("d.m.y H:i",$line)."    ".$data[$source]."\n";
                                    if ($j++>10) break;
                                    }
                                }
                            }
                        }
                    }
                $filesToRead=$fileinfo;
                if ($debug) print_r($filesToRead);
                if (strtoupper($cmd)=="FILES") 
                    {
                    $files = array();
                    foreach ($filesToRead as $entry) $files[]=str_replace(" ", "",$entry["Filename"]);
                    return ($files);            // Header ausgeben, damit Befehle erkannt werden können
                    }
                $oid=false;
                if (isset($config["Target"]["Name"]))
                    {
                    $targetName = $config["Target"]["Name"];
                    if ($debug) echo "   Get the new register, Modul $modul mit Category $categoryIdResult und Register Name $targetName.\n";
                    $oid = IPS_GetObjectIDByName($targetName,$targetCategory);         // kein Identifier, darf in einer Ebene nicht gleich sein   -- Fehler
                    if ($debug) echo "   Use the Register mit OID $oid und Name ".IPS_GetName($oid).".\n";
                    }
                elseif (isset($config["Target"]["OID"]))
                    {
                    $oid=$config["Target"]["OID"];
                    if ($debug>1) echo "   Get and use the Register mit OID $oid und Name ".IPS_GetName($oid).".\n";
                    } 
                $targetId=$oid;
                if (strtoupper($cmd)=="TARGETID") return ($targetId);
                $html .= '<div class="cuw-quick">';
                $html .= '<table>';
                if (in_array("Verzeichnis",$header)===false) foreach ($filesToRead as $entryHeader) if (isset($entryHeader["Verzeichnis"])) 
                    { 
                    $html .= "<tr>";
                    $html .= "<th>".$entryHeader["Verzeichnis"]."</th>";
                    $html .= "<th>".$targetId."</th>";
                    if (isset($headerSort[$sort])) $html .= "<th>Sort:".$headerSort[$sort]."</th>";
                    $html .= "</tr>"; 
                    break; 
                    }
                $html .= '</table><br>';
                $html .= '<table class="sortierbar">';                            
                $html .= "<thead><tr>";
                //$html .= "<th>Verzeichnis</th><th>Filename</th><th>Size</th><th>FileDate</th><th>Erster Eintrag</th><th>Letzter Eintrag</th><th>Anzahl</th><th>Interval</th><th>Periode</th><th>Analyze</th>";
                //foreach ($header as $value) $html .= "<th>".$value."</th>";
                foreach ($header as $value) 
                    {
                    $value1 = str_replace(" ", "",$value);
                    //$html .= '<th><a href="#" onClick=trigger_button(\''.$value1.'\',\'Guthabensteuerung\',\'\')>'.$value."</a></th>"; 
                    $html .= '<th>'.$value."</th>"; 
                    }
                $html .= "</tr></thead><tbody>";
                // Ausgabe in der Reihenfolge Verzeichnis - Filename - Size - DateTime - FirstDate - LastDate - Count - Intervall - Periode - Analyze
                foreach ($filesToRead as $entry) 
                    {
                    $json["Table"][$sub]=$entry;
                    $sub++;
                    $html .= "<tr>";
                    if (in_array("Verzeichnis",$header)) $html .= "<td>".$entry["Verzeichnis"]."</td>";
                    //$html .= "<td>".$entry["Filename"]."</td>";
                    //$html .= '<td><a href="#" onClick=trigger_button(\''.$entry["Filename"].'\',\'Guthabensteuerung\',\'\')>'.$entry["Filename"]."</a></td>";
                    $html .= '<td>'.$entry["Filename"]."</td>";
                    $html .= "<td>".$entry["Size"]."</td>";
                    $html .= "<td>".date("d.m.Y H:i",$entry["DateTime"])."</td>";
                    $html .= "<td>".date("d.m.Y H:i",$entry["FirstDate"])."</td>";
                    $html .= "<td>".date("d.m.Y H:i",$entry["LastDate"])."</td>";
                    $html .= "<td>".$entry["Count"]."</td>";
                    $html .= "<td>".nf($entry["Interval"],"s")."</td>";
                    $html .= "<td>".nf($entry["Periode"],"s")."</td>";
                    if (sizeof($entry["Analyze"])>0) 
                        {
                        $html .= "<td><table>";
                        foreach ($entry["Analyze"] as $index => $subentry) 
                            {
                            $html .= "<tr>";
                            $html .= "<td>".$subentry["Type"]."</td>";    
                            $html .= "<td>".date("d.m.Y H:i",$subentry["StartDate"])."</td>";
                            $html .= "<td>".date("d.m.Y H:i",$subentry["EndDate"])."</td>";
                            $html .= "<td>".nf($subentry["Diff"],"s")."</td>";
                            $html .= "</tr>";                            
                            }
                        $html .= "</table></td>";
                        }
                    else 
                    $html .= "</tr>";
                    }
                $html .= "</tbody></table>";
                $html .= "</div>";

                }
            if ($modeHtml===true) return ($html);
            elseif (strtoupper($modeHtml)=="ARRAY")  return ($json);   // ist aber das array
            else return (json_encode($json));
            }   // ende function

		}  // ende class


    /*************************************************************************************************
     * 
     * Erweiterung für Topology based Funktionen
     *
     * verwendet getMeterConfig um alle AMIS Register herauszufinden und abzuarbeiten
     * typischer Programmaufbau:
     *     $meterTopology=array(); 
            if (createMeterTopology($meterTopology,$MeterConfig,true)===false) createMeterTopology($meterTopology,$MeterConfig);     //zweimal aufrufen       
            printMeterTopology($meterTopology,$meterValues);
     * eine flach strukturierte Tabelle mit Parent wie bei topology wird in eine hierarchische Tabelle unter Verwendung des Eintrages Children umgestellt
     *
     * verwendete functions:
     *      __construct
     *      
     *
     */
	class AmisTopology extends Amis
        {

        /* AmisTopology::processMeterTopology
         * recursive function, die eine meter topology aufbaut
         * wird von createMeterTopology aufgerufen, die die Topologie initialisisert
         */
        function processMeterTopology(&$meterTopology,&$meter,$ident="")
            {
            $result=false;
            foreach ($meterTopology as $name => $entry)
                {
                if ($name===$meter["PARENT"]) 
                    {
                    $result=true;
                    if (isset($meterTopology[$name]["CHILDREN"][$meter["NAME"]])===false)  $meterTopology[$name]["CHILDREN"][$meter["NAME"]]["CONFIG"]=$meter;
                    }
                else
                    {
                    if (isset($meterTopology[$name]["CHILDREN"]))
                        {
                        $result=$this->processMeterTopology($meterTopology[$name]["CHILDREN"],$meter,$ident."   ");    
                        /*foreach ($meterTopology[$name]["CHILDREN"] as $nameSub => $entrySub)
                            {
                            if ( ($nameSub===$meter["PARENT"]) && (isset($meterTopology[$name]["CHILDREN"][$nameSub]["CHILDREN"][$meter["NAME"]])===false) ) $meterTopology[$name]["CHILDREN"][$nameSub]["CHILDREN"][$meter["NAME"]]["CONFIG"]=$meter;
                            }*/
                        }
                    }
                }
            return($result);
            }

        /* AmisTopology::createMeterTopology
         * return wert ist $meterTopology
         */
        function createMeterTopology(&$meterTopology,$MeterConfig,$debug=false)
            {
            $meterTopology=array();         // Topologie ergründen  Name => [ Children, Config ] , Children [ Name => [ Children, Config ]]
            $resultOverall=true;
            foreach ($MeterConfig as $identifier => $meter)
                {
                if ($debug) echo str_pad($meter["NAME"],30);
                if (strtoupper($meter["ORDER"])=="MAIN") 
                    {
                    if (isset($meterTopology[$meter["NAME"]])===false) $meterTopology[$meter["NAME"]]["CONFIG"]=$meter;   
                    if ($debug) echo "Main    ".json_encode($meter);
                    }
                else 
                    {
                    $result = $this->processMeterTopology($meterTopology,$meter,"");
                    if ($debug) echo $meter["ORDER"]."   ".$meter["PARENT"] ;
                    if ($result == false) 
                        {
                        echo "Parent \"".$meter["PARENT"]." \" nicht gefunden, noch einmal probieren.\n";
                        $resultOverall=false;
                        }           
                    }
                if ($debug) echo "\n";
                }
            return ($resultOverall);
            }

        /* AmisTopology::printMeterTopology
         * rekursive Darstellung, immer wenn Children erneuter Aufruf mit der function
         *
         */
        function printMeterTopology($meterTopology, $meterValues, $ident="",$debug=false)
            {
            $ipsOps=new ipsOps();
            foreach ($meterTopology as $name => $meter)
                {
                if ($debug) echo str_pad($ident.$name,50);
                foreach ($meterValues as $line => $entry)
                    {
                    if ( (isset($entry["Information"]["NAME"])) && ($entry["Information"]["NAME"]==$name) ) 
                        {
                        if ($debug>1) echo "  found";
                        $oid=$this->getWirkleistungID($name,false);              //true für Debug
                        //print_R($regs);
                        if ($oid !== false) 
                            {
                            if ($debug) echo "    ($oid) ".nf(getValue($oid),"kW");
                            $regs=$this->getRegistersfromOID($name);     // geht auch mit Name
                            if (isset($regs["LeistungID"])) echo "  Homematic (".$regs["LeistungID"]."): ".GetValueIfFormatted($regs["LeistungID"]);
                            //echo " (".$ipsOps->path($oid).") ";
                            }
                        }
                    }
                /*
                if (isset($meter["CHILDREN"])) echo "children ";
                else echo "         ";
                if (isset($meter["CONFIG"])) echo json_encode($meter["CONFIG"]);  
                */
                echo "\n"; 
                if (isset($meter["CHILDREN"]))
                    {
                    $this->printMeterTopology($meter["CHILDREN"],$meterValues,$ident."   ",$debug);
                    }
                }

            }





        }





	/** @}*/
?>