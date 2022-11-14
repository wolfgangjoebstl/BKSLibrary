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


    /************************************
     *
     * Zusammenfassung der mit Energieberechnung verwandten Funktionen
     * behandelt Homematic Energiemessgeräte und den AMIS Zähler
     *
     *  __construct                 speichert strukturierte MeterConfig
     *  setMeterConfig              mit dieser Routine wird MeterConfig erstellt
     *  getMeterConfig              die bereinihgte Konfiguration auslesen, die disabled registers sind weg
     *
     *  getWirkenergieID            Wirkenergie Spiegelregister mit dem Namen Wirkenergie in der Kategorie des jeweiligen Objektes
     *  getZaehlervariablenID       beliebiges Register aus den Zaehlervariablen der jeweiligen Kategorie heraussuchen, nur für AMIS aktuell
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
     *  do_register
     *  do_calculate
     *  summestartende
     *
     *
     ********************************************************************/

	class Amis {

        public $CategoryIdData, $CategoryIdApp;
		private $archiveHandlerID=0;
		
		private $MeterConfig;           // die bereinigtre AMIS Meter Config
        private $systemDir;              // das SystemDir, gemeinsam für Zugriff zentral gespeichert
        private $debug;                 // zusaetzliche hilfreiche Debugs

        private $ipsOps,$dosOps;
		
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

			$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

            $this->ipsOps = new ipsOps();
            $this->dosOps = new dosOps();
            $this->systemDir     = $this->dosOps->getWorkDirectory();

			$this->MeterConfig = $this->setMeterConfig();
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
                    configfileParser($config,$result[$index],["VariableName","VARIABLENAME","nariablename","Variablename"],"VariableName",null);
                    if (strtoupper($result[$index]["TYPE"])=="HOMEMATIC") 
                        {
                        configfileParser($config,$result[$index],["Oid","OID","oid"],"OID",null);
                        if (isset($result[$index]["OID"])===false) echo "Warning, OID must be provided for TYPE Homematic.\n";
                        }
                    if (strtoupper($result[$index]["TYPE"])=="DAILYREAD") 
                        {
                        configfileParser($config,$result[$index],["WirkenergieID","WIRKENERGIEID","WirkenergieId","Oid","OID","oid"],"WirkenergieID",null);
                        if (isset($result[$index]["WirkenergieID"])===false) echo "Warning, OID must be provided for TYPE DAILYREAD.\n";
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

        /* und die Meter Configuration ausgeben */

        public function getMeterConfig()
            {
            return ($this->MeterConfig);
            }

        /* getWirkenergieID aus der Config
         * in der AMIS data Kategorie gibt es pro meter["Name"] eine Variable, die Variable wird vorausgesetzt, false wennnicht
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
                echo "getWirkenergieID suche nach ".$meter["NAME"]." in ".$this->CategoryIdData."  found as $ID \n";               
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

        /* beliebiges Register aus den Zaehlervariablen heraussuchen */

        public function getZaehlervariablenID($meter, $name)
            {
            $ID = IPS_GetObjectIDByName($meter["NAME"], $this->CategoryIdData,);                  
            $variableID=false;
            switch (strtoupper($meter["TYPE"]))
                {
                case "AMIS":
                    $AmisID = IPS_GetObjectIDByName( "AMIS", $ID);
                    //echo "AmisID $AmisID (".$this->ipsOps->path($AmisID).")\n"; 
                    $zaehlervarID = IPS_GetObjectIDByName ( 'Zaehlervariablen' , $AmisID );
                    //echo "ZaehlervariablenID $zaehlervarID (".$this->ipsOps->path($zaehlervarID).")\n"; 
                    $variableID = IPS_GetObjectIDByName ( $name , $zaehlervarID );
                    //echo "variableID $variableID (".$this->ipsOps->path($variableID).")\n"; 
                default:
                    break;	
                }        
			return ($variableID);
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
                if ($debug) echo "getRegisterID mit Parameter Array eines Zählers aufgerufen.\n";                    
			    if (strtoupper($meter["TYPE"])=="HOMEMATIC")
                    {
                    $ID = IPS_GetObjectIdByName($meter["NAME"], $this->CategoryIdData);   // ID der Kategorie                        
           		    $LeistungID = @IPS_GetObjectIdByName($identifier,$ID);   // nur eine Wirkleistung gespeichert
                    }
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
                        if (strtoupper($meterEntry["TYPE"])=="HOMEMATIC") 
                            {
                            $ID = IPS_GetObjectIdByName($meterEntry["NAME"], $this->CategoryIdData);   // ID der Kategorie                        
                            $LeistungID = @IPS_GetObjectIdByName($identifier,$ID);   // nur eine Wirkleistung gespeichert 
                            if ($LeistungID && $debug) echo "   --> Ergebnis $LeistungID in $ID\n";                           
                            }
                        elseif (strtoupper($meterEntry["TYPE"])=="DAILYREAD")
                            {
                            if ($identifier=="Wirkenergie") $LeistungID=$meterEntry["WirkenergieID"];
                            print_R($meterEntry);  
                            
                            } 
                        else
                            {
                            echo "Warnung, Match aber kein Eintrag für den Typ ".$meterEntry["TYPE"]."\n";  
                            print_R($meterEntry);  
                            }
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
                        //echo "   ".$entry["NAME"]." gefunden. OID aus Config übernehmen $realOID ".IPS_GetName($realOID)."\n";
                        $result=$this->getRegistersfromOID($realOID);
                        }
                    if ($debug) echo "\n";                        
                    }
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
        * Parameter sind die Konfiguration und der Parameter ob die IPS Cutter Funktion verwendet weird (true) oder nicht
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

		/************************************************************************************************************************
 		 *
		 * Minuetlich wird ein Scipt aufgerufen um die Momentanwerte abfragen. Dieses Script kommt alle 15 Minuten dran.
         * es wird der Einzeleintrag der Konfiguration übergeben, also ohne Identifier. Die Einträge in der Konfiguration durchgehen und auf Brauchbarkeit untersuchen.
 		 * Homematic Energiesensoren auslesen, ignoriert andere Typen als Einzelbefehl.
        * Übergeben wird entweder die Homematic Instanz OID oder die Einzelwerte HM_EnergieID und HM_LeistungID
 		 *
 		 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
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
				$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String , prüft ob die Variable schon vorhanden ist */
				$OffsetID = CreateVariableByName($ID, 'Offset_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String , prüft ob die Variable schon vorhanden ist */
				$Homematic_WirkergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String , prüft ob die Variable schon vorhanden ist */
            
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
                    if (isset($configuration["OID"])===false) $configuration["OID"] = $HMenergieID;
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
                    $energie=GetValue($HMenergieID)/1000; /* Homematic Wert ist in Wh, in kWh umrechnen */
                    $leistung=GetValue($HMleistungID);
                    $energievorschub=$energie-GetValue($Homematic_WirkergieID);
                    if ($changeDone)                /* das Homemeatic Register hat sich geändert. Erster Wert ist Offset. Nächsten Wert erst für den Vorschub verwenden */ 
                        {
                        echo "   >>Info, das beobachtete Energieregister hat sich geändert. Ersten Wert als Basis nehmen. Vorschub bleibt 0.\n";
                        $energievorschub=0;   
                        }                      
                    elseif ($energievorschub<0)       /* Energieregister in der Homematic Komponente durch Stromausfall zurückgesetzt */
                        {
                        echo "   >>Fehler, Energievorschub kleiner 0, ist $energievorschub. Den aktuellen Wert des Spiegelregisters in Data ($Homematic_WirkergieID) auf den Offset aufaddieren : ".GetValue($Homematic_WirkergieID)."kWh.\n";
                        $offset = GetValue($OffsetID);                        
                        $offset+=GetValue($Homematic_WirkergieID); /* als Offset alten bekannten Wert dazu addieren */
                        $energievorschub=$energie;
                        SetValue($OffsetID,$offset);
                        }
                    elseif ($energievorschub>10)       /* verbrauchte Energie in einem 15 Minutenintervall ist realistisch maximal 2 kWh, 10kWh abfragen   */
                        {   /* Unplausibilitaet ebenfalls behandeln */
                        echo "   >>Fehler, Energievorschub groesser 10, ist $energievorschub. Diese Änderung ignorieren.\n";
                        $energievorschub=0;
                        }		

                    $energie_neu=GetValue($EnergieID)+$energievorschub;
                    if ($debug==false)
                        {
                        SetValue($Homematic_WirkergieID,$energie);          /* Spiegelregister für Homematic Energie Register */
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
				if ($debug) echo "writeEnergySumme, Werte von : ".$meter["NAME"]."\n";
			      
				$ID = CreateVariableByName($this->CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

				$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

				if ( isset($meter["Calculate"]) == true )
					{
					$calculate = explode(",",$meter["Calculate"]);                  
					if ($debug) echo "  die folgenden Register werden zusammengezählt: ".$meter["Calculate"]."\n";
					//print_r($calculate);
                    $e=0;
					foreach ($calculate as $oid)
						{
                        $e++;       // Zeilenindex
                        if ($debug) echo "      $e bearbeite $oid:\n";                          
						$result=$this->getRegistersfromOID($oid);           // das sind die Quellregister (von Homematic, Register, AMSI Zähler etc.), vor der Verarbeitung
                        /* nicht die Source sondern die Target Register zusammenzählen 
                        print_r($result);
						$energie+=GetValue($result["EnergieID"]);
						$leistung+=GetValue($result["LeistungID"]);
                        */
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
		 *
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
				echo "<p class=\"zeile\"> \n";   /* Umschalten auf Arial Font */				
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
                        echo "Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime).":".$newline;

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
                                echo "  Werte : ".date("D d.m H:i", $zeit)." ".number_format($wert['Value'], 2, ",", "" ) ." kWh".$newline;
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
                                echo "Es sind ".($anzahl2+1)." Eintraege vorhanden. Breite erster Spalte ist : ".$tabwidth.$newline;
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
                    else echo "****Fehler, Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime)." nicht verfügbar\n";
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
			echo "Gesamt Tabelle aufbauen. Anzahl Zähler ist $metercount. \n";
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
						echo "   Zählerwert für ".$zeile[$line]["Wochentag"][0]." vom Datum ".date("d.m", $zeit)." fehlt.\n  ";
						$outputTabelle.=$startcell.substr("              ",0,$tabwidth).$endcell;
						}
					$zeit-=24*60*60;	/* naechster Tag */			
					}
				$outputTabelle.=$endparagraph; 					
				}	

			//if ($metercount) print_r($zeile);	        // es gibt auch den Fall dass keine Homematic Messgeräte angeschlossen sind
			if ($html==true) 
				{
				echo "</p>";
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
					$outputEnergiewerte.=$startparagraph.$startcell.substr($Werte[$line]["Wochentag"][0]."                           ",0,$tabwidth0).$endcell.$startcell.str_pad($Werte[$line]["Energie"][1],10).$endcell;
					}
				else
					{
					$outputEnergiewerte.=$startparagraph.$startcell.substr($Werte[$line]["Wochentag"][0]."                           ",0,$tabwidth0).$endcell.$startcell.str_pad(" ",10).$endcell;
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
				$outputEnergiewerte.=$endtable."</div> \n";
				}
			return ($style.$outputEnergiewerte);
			}

		/* fasst alle Energieregister als Vorschubwerte der letzten 9 Tage in einer uebersichtlichen Tabelle zusammen, 
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
         *
		 */

		function writeEnergyRegisterTabletoString($Werte,$html=true)			/* alle Werte als String ausgeben */
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
			echo "writeEnergyRegisterTabletoString, gesamte Tabelle im Format ".($html?"Html":"Text")." für ".count($Werte)." Zeilen aufbauen.\n";
			for ($line=0;$line<($metercount);$line++)
				{
                echo "Zeile $line für Zähler : ".$Werte[$line]["Wochentag"][0]."\n";
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
						echo "   Zählerwert für ".$Werte[$line]["Wochentag"][0]." mit Index".date("d.m", $zeit)." fehlt. Kein Zeitstempel für $zeit vorhanden.\n  ";
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
				echo "</p>";
				$outputTabelle.=$endtable."</div> \n";				
				}
			return ($style.$outputTabelle);
			}

		function writeEnergyPeriodesTabletoString($Werte,$html=true,$kwh=true)			/* alle Werte als String ausgeben */
			{
			/* Werte zwar uebernhemen, aber für Periodenwerte nicht wirklich notwendig */ 
			
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
			$zeile0=$startcell."Periodenwerte           ".$endcell.$startcell."   1      ".$endcell
														 .$endcell.$startcell."   7      ".$endcell
														 .$endcell.$startcell."  30      ".$endcell
														 .$endcell.$startcell." 360      ".$endcell;

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
			echo "Gesamt Tabelle aufbauen, eine Zeile pro Zähler\n";
			for ($line=0;$line<($metercount);$line++)
				{
				$outputTabelle.=$startparagraph.$startcell.substr($Werte[$line]["Information"]["NAME"]."                               ",0,$tabwidth0).$endcell;	/* neue Zeile pro Zähler */ 
					
				$PeriodenwerteID = $Werte[$line]["Information"]["Periodenwerte"];
				
				if ($kwh==true)
					{
					$outputTabelle.=$startcell.str_pad(number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzterTag',$PeriodenwerteID)), 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte7Tage',$PeriodenwerteID)), 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte30Tage',$PeriodenwerteID)), 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_letzte360Tage',$PeriodenwerteID)), 2, ",", "" ),$tabwidth).$endcell;
					}
				else
					{	
					$outputTabelle.=$startcell.str_pad(number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzterTag',$PeriodenwerteID)), 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte7Tage',$PeriodenwerteID)), 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte30Tage',$PeriodenwerteID)), 2, ",", "" ),$tabwidth).$endcell;
					$outputTabelle.=$startcell.str_pad(number_format(GetValue(IPS_GetVariableIDByName('Wirkenergie_Euro_letzte360Tage',$PeriodenwerteID)), 2, ",", "" ),$tabwidth).$endcell;
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
            echo "\nLeistungsregister direkt aus den Homematic Instanzen:\n";
            foreach ($powerValues as $name => $entry) 
                {
                $oid=$entry["OID"];
                echo str_pad($name,45)." (".$oid.") = ".str_pad(GetValueIfFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")   \n";
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
			return($alleStromWerte);	
			}
		
		
		function getAMISDataOids()
			{
			return(CreateVariableByName($this->CategoryIdData, "Zusammenfassung", 3)); 
			}
			
		/* 
		 * zweiteilige Funktionalitaet, erst die Energieregister samt Einzelwerten der letzten Tage einsammeln und
		 * dann in einer zweiten Funktion die Ausgabe als html oder text machen.
		 * Übergabe	der Energiewerte der letzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr erfolgt als Array.
         *
         * die Ausgabe wird Zeilenweise mit index metercount geschrieben
         *
		 */
		
		function writeEnergyRegistertoArray($MConfig,$debug=false)
			{
            // if ($debug) echo "writeEnergyRegistertoArray wurde aufgerufen. MeterConfig einzeln durchgehen und Messwerte für Tabelle ermitteln.\n";
            $archiveOps = new archiveOps(); 
			$zeile=array();
			$metercount=0;
			foreach ($MConfig as $meter)
				{
				if ($debug)
					{
					echo "-----------------------------\n";
					echo "writeEnergyRegistertoArray, Werte von : ".$meter["NAME"]." für Typ ".$meter["TYPE"]."\n";
					}
				$meterdataID = IPS_GetObjectIdByName($meter["NAME"],$this->CategoryIdData);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$EnergieID = $this->getWirkenergieID($meter);    // ID von Wirkenergie bestimmen 
				switch ( strtoupper($meter["TYPE"]) )                   // Spezialbehandlung für Hoimematic register, RegID bestimmen
					{	
					case "HOMEMATIC":
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
						break;		
					default:
						$RegID=$EnergieID;
						break;
					}					
					
				/* Energiewerte der letzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr */
				$jetzt=time();
                if (strtoupper($meter["TYPE"])=="DAILYREAD")        // der Energievorschub wird gespeichert, keine Zählregister, ausserdem 2 tage vom Datum hinten nach
                    {
                    $endtime=$jetzt;                // das ist nur ein Wert pro Tag, die Werte von heute wurden eh noch nicht erfasst
                    $vorigertag=0;
                    $vorschub=true;
                    }
				else 
                    {
                    $endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
    				$vorigertag=date("d.m.Y",$jetzt);	/* einen Tag ausblenden */
                    $vorschub=false;
                    }
				$starttime=$endtime-60*60*24*10;
				//$werte = AC_GetLoggedValues($this->archiveHandlerID, $EnergieID, $starttime, $endtime, 0);

                // Alternative Auswertung
                $config=array();
                $config["StartTime"] = $starttime;   // 0 endtime ist now
                $config["EndTime"]   = $endtime;
                $valuesAnalysed = $archiveOps->getValues($EnergieID,$config,1);     // Analyse der Archivdaten
                if (isset($valuesAnalysed["Values"]))
                    {
                    $werte=$valuesAnalysed["Values"];
                    $result=$archiveOps->countperIntervalValues($werte,2);
                    //print_R($result);
                    //print_r($valuesAnalysed["Description"]);
                    }
                else $werte=false;
                
				if ($debug) 
                    {
                    echo "~~~~~~~~~~~~~~~~\n";
                    if ($werte===false) echo "Warnung, keine Zeitreihe möglich.\n";
                    elseif (is_array($werte)) echo "writeEnergyRegistertoArray, Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime)." mit ".count($werte)." Eintraegen ermittelt, jetzt bearbeiten und speichern :\n";
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
				foreach($werte as $wert)            // hier die Tagesaggregation machen
					{
					$zeit=$wert['TimeStamp']-60;                // eine Minute in die Vergangenheit 00:00 ist 23:59
					//echo "    ".date("D d.m H:i", $wert['TimeStamp'])."   ".$wert['Value']."    ".$wert['Duration']."\n";
					if (date("d.m.Y", $zeit)!=$vorigertag)          // aktueller Wert hat ein anderes Datum als der vorige Wert
						{
						$zeile[$metercount]["Datum"][$laufend] = date("d.m", $zeit);
						$zeile[$metercount]["Wochentag"][$laufend] = date("D  ", $zeit);
                        if ($initial) { $alterWert=$wert['Value']; $initial=false; }
                        if (strtoupper($meter["TYPE"])=="DAILYREAD")        // Tageswechsel für Vorschubvariabel
                            {
                            $datumOfValues=date("d.m",strtoTime("-2 days", strtotime(date("d.m.Y 00:01",$zeit))));      // immer um 1:00 ist Target Time
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
				$metercount+=1;                                     //  nächste Zeile
				} /* ende foreach Meter Entry */
			
			return($zeile);
			}

        /* Messwerte aus dem Archive auslesen und gleichzeitig eine Plausicheck machen
         * weitestgehend als generische Routine geschrieben
         *
         * type unterscheidet "" für Energie das sind Zählwerte, "A" zB für Messwerte
         * display aktovoert zusätzliche Anzeigen und 
         * delete macht eine Bereinigung von falschen Werten
         */

        function getArchiveData($variableID, $starttime, $endtime, $type="",$display=false,$deleteCheck="")
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
            if ($type == "") echo "getArchiveData: Werte Variable: ".IPS_GetName($variableID)."/".IPS_GetName(IPS_GetParent($variableID))." von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
            else echo "getArchiveData: Werte Variable: ".IPS_GetName($variableID)."/".IPS_GetName(IPS_GetParent($variableID))." mit Typ $type von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
            
            $increment=1;
            //echo "Increment :".$increment."\n";
            $gepldauer=($endtime-$starttime)/24/60/60;
            do {
                /* es könnten mehr als 10.000 Werte sein, Abfrage generisch lassen     
                 * Dieser Teil erstellt eine Ausgabe im Skriptfenster mit den abgefragten Werten, nicht mer als 10.000 Werte ...
                 */
                $werte = AC_GetLoggedValues($this->archiveHandlerID, $variableID, $starttime, $endtime, 0);
                //print_r($werte);
                $anzahl=count($werte);

                if (($anzahl == 0) & ($zaehler == 0)) 
                    {
                    echo " Fehler, Variable: ".IPS_GetName($variableID)." hat keine Werte archiviert. \n";
                    break;
                    }   // hartes Ende der Schleife wenn keine Werte vorhanden

                if ($initial)
                    {
                    /* allererster Durchlauf */
                    echo "   Variable: ".IPS_GetName($variableID)." mit ".$anzahl." Werte. \n";
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
                            echo "****".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." ergibt in Summe         : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
                            }
                        else
                            {
                            $vorwert=$aktwert;
                            $vorzeit=$zeit;
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
                        }
                    if ($type=="")
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
                                    echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . nf($aktwert,"kWh")."   ".nf($leistung, "kW")." ergibt in Summe (Tageswert) : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
                                    if ( ($multiplikator < (1)) || ($multiplikator > (6)) ) echo "        ==>   Leistungsberechnung, Zeitdauer ".nf($intervall,"s")." ".nf($multiplikator,1)." \n";
                                    elseif ( ($multiplikator < (3.9)) || ($multiplikator > (4.1)) ) echo "              Leistungsberechnung, Zeitdauer ".nf($intervall,"s")." ".nf($multiplikator,1)." \n";
                                    }
                                }
                            }
                        else 
                            {       /*  erster Wert */
                            if  ($display==true) echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . nf($aktwert, "kWh")."         ergibt in Summe (Tageswert) : " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
                            }
                        }
                    else
                        {
                        /* jeden Eintrag ausgeben */
                        $invalid = false;
                        switch (strtoupper($type))
                            {
                            case "W":               // Plausiprüfung für Leistung, gröer 10kW ist unwahrscheinlich 
                                if (($aktwert > (10000)) or ($aktwert==0) or ($aktwert<0)) $invalid = true;      // unplausible Werte bei Leistungsmessung rausfiltern
                                break;
                            case "KW":               // Plausiprüfung für Leistung, gröer 10kW ist unwahrscheinlich 
                                if (($aktwert > (10)) or ($aktwert==0) or ($aktwert<0)) $invalid = true;      // unplausible Werte bei Leistungsmessung rausfiltern
                                break;
                            default:
                                break;
                            }
                        if ($invalid)
                            {
                            if ($delete==true)
                                {
                                AC_DeleteVariableData($this->archiveHandlerID, $variableID, $zeit, $zeit);
                                $reaggregate=true;
                                }
                            echo "****".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." nicht plausibel ". PHP_EOL;
                            }
                        elseif ($display==true) echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "")." $type\n";
                        }

                    $zaehler+=1;
                    }
                        //$endtime=$zeit;
                } while (count($werte)==10000);
            if ($delete && $reaggregate) 
                {
                echo "Delete of one value means re aggregate the archive.\n";
                $result = @AC_ReAggregateVariable($this->archiveHandlerID, $variableID);
                if ($result===false) echo " Error, take pace, re aggragation takes allready place.\n";
                }
            }

		
        /* aus den Archivedaten den grössten Wert finden 
         * Wert zurückmelden
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
        * Eingabe Beginnzeit Format time(), Endzeit Format time(), 0 Statuswert 1 Inkrementwert 2 test, false ohne Hochrechnung
        * Parameter increment_var muss immer 1 sein.
        *
        *
        * Es werden bereits täglich aggregierte Werte aus dem Archive ausgelesen, es sollte Standard oder Zähler beim Archiv richtig gesetzt sein
        *
        * Routine fehlerhaft bei Ende Sommerzeit, hier wird als Startzeit -30 Tage eine Stunde zu wenig berechnet 
        *
        ******************************************************************************************/

        function summestartende($starttime, $endtime, $increment_var, $estimate, $variableID, $display=false )
            {
            if ($display)
                {
                echo "ArchiveHandler: ".$this->archiveHandlerID." Variable: $variableID (".$this->ipsOps->path($variableID).")\n";
                echo "Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";                    
                }
            $zaehler=0;
            $ergebnis=0;
            $increment=(integer)$increment_var;
                
            do {
                /* es könnten mehr als 10.000 Werte sein
                    Abfrage generisch lassen
                */
                
                // Eintraege für GetAggregated integer $InstanzID, integer $VariablenID, integer $Aggregationsstufe, integer $Startzeit, integer $Endzeit, integer $Limit
                $aggWerte = @AC_GetAggregatedValues ( $this->archiveHandlerID, $variableID, 1, $starttime, $endtime, 0 );
                if ($aggWerte === false) 
                    {
                    echo "Variable $variableID ".IPS_GetName($variableID)." neu aggregieren. Dauert etwas.\n";
                    AC_ReAggregateVariable ($this->archiveHandlerID, $variableID);              // Reperaturversuch                 
                    $aggWerte = AC_GetAggregatedValues ( $this->archiveHandlerID, $variableID, 1, $starttime, $endtime, 0 );
                    throw new Exception("AC_GetAggregatedValues, Fehler beim Aggregieren - wird automatisch repariert.");
                    }
                $aggAnzahl=count($aggWerte);
                //print_r($aggWerte);
                foreach ($aggWerte as $entry)
                    {
                    if (((time()-$entry["MinTime"])/60/60/24)>1) 
                        {
                        /* keine halben Tage ausgeben */
                        $aktwert=(float)$entry["Avg"];
                        if ($display) echo "     ".date("D d.m.Y H:i:s",$entry["TimeStamp"])."      ".$aktwert."\n";
                        switch ($increment)
                            {
                            case 0:
                            case 2:
                                echo "*************Fehler.\n";
                                break;
                            case 1:        /* Statuswert, daher kompletten Bereich zusammenzählen */
                                $ergebnis+=$aktwert;
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
            if ($display) echo "   Variable: ".IPS_GetName($variableID)." mit ".$aggAnzahl." Tageswerten und ".$ergebnis." als Ergebnis.\n";
            return $ergebnis;
            }

			
		}  // ende class

	/** @}*/
?>