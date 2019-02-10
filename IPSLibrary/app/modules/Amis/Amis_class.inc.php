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
     * configurePort
     * sendReadCommandAmis
     * writeEnergyHomematics
     * writeEnergyHomematic
     *
     *
     *
     ********************************************************************/

	class Amis {


		var $parentid=0;
		var $archiveHandlerID=0;
		
		var $MeterConfig;
		
		/**
		 * @public
		 *
		 * Initialisierung der AMIS class
		 *
		 */
		public function __construct() 
			{
			$this->parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
			$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$this->MeterConfig = $this->getMeterConfig();
			}

        /* aus der offiziellen Config die deaktivierten Zähler herausfiltern, kommen gar nicht soweit
         *
         */

        public function getMeterConfig()
            {
            $result=array();
            foreach (get_MeterConfiguration() as $index => $config)
                {
                //echo "Bearbeite Zähler $index.\n"; print_r($config);
                if ( (isset($config["Status"])) && ( (strtoupper($config["Status"])=="DISABLED") || (strtoupper($config["Status"])=="DEACTIVATED") ) )
                    {
                    /* Deaktivierte Energiezähler aus der Konfig nehmen */
                    }
                else
                    {                    
                    $result[$index]=$config;
                    }
                }

            return ($result);
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
				$ID = CreateVariableByName($this->parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */				
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

					$handlelog=fopen("C:\Scripts\Log_Cutter_AMIS.csv","a");
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
 		 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
		 *
		 *****************************************************************************************************************************/

		function writeEnergyHomematics($MConfig)			/* alle Werte aus der Config ausgeben */
			{
			$homematicAvailable=false;

			foreach ($MConfig as $meter)
				{
				if (strtoupper($meter["TYPE"])=="HOMEMATIC")
					{
					$homematicAvailable=true;
					echo "Werte von : ".$meter["NAME"]."\n";
	   		      
					$ID = CreateVariableByName($this->parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

					$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
					$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
					$OffsetID = CreateVariableByName($ID, 'Offset_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
					$Homematic_WirkergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

					if ( isset($meter["OID"]) == true )
						{
						$result  = $this->getHomematicRegistersfromOID($meter["OID"]);
						$HMenergieID  = $result["HM_EnergieID"];
						$HMleistungID = $result["HM_LeistungID"];							
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
				}
			return ($homematicAvailable);
			}

		/************************************************************************************************************************
 		 *
		 * Minuetlich von Momentanwerte abfragen Scipt aufgerufen.		 
 		 * Homematic Energiesensoren auslesen, ignoriert andere Typen als Einzelbefehl
 		 *
 		 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
		 *
		 *****************************************************************************************************************************/

		function writeEnergyHomematic($meter)		/* nur einen Wert aus der Config ausgeben */
			{
			$homematicAvailable=false;

			if (strtoupper($meter["TYPE"])=="HOMEMATIC")
				{
				$homematicAvailable=true;
				echo "Werte von : ".$meter["NAME"]."\n";
			      
				$ID = CreateVariableByName($this->parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

				$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$OffsetID = CreateVariableByName($ID, 'Offset_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$Homematic_WirkergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

				if ( isset($meter["OID"]) == true )
					{
					$result  = $this->getHomematicRegistersfromOID($meter["OID"]);
					$HMenergieID  = $result["HM_EnergieID"];
					$HMleistungID = $result["HM_LeistungID"];	
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
				if ($energievorschub>10)       /* verbrauchte Energie in einem 15 Minutenintervall ist realsitisch maximal 2 kWh, 10kWh abfragen   */
					{   /* Unplausibilitaet ebenfalls behandeln */
					$energievorschub=0;
					}		
				SetValue($Homematic_WirkergieID,$energie);
				$energie_neu=GetValue($EnergieID)+$energievorschub;
				SetValue($EnergieID,$energie_neu);
				SetValue($LeistungID,$energievorschub*4);
				echo "  Werte aus der Homematic : ".$energie." kWh  ".GetValue($HMleistungID)." W\n";
				echo "  Energievorschub aktuell : ".$energievorschub." kWh\n";
				echo "  Energiezählerstand      : ".$energie_neu." kWh Leistung : ".GetValue($LeistungID)." kW \n\n";
				}
			return ($homematicAvailable);
			}
			
		/* OID übergeben, schauen ob childrens enthalten sind und die richtigen register rausholen, wenn nicht eine Ebene höher gehen
		 */
				
		function getHomematicRegistersfromOID($oid)
			{
			$result=false;
			$cids = IPS_GetChildrenIDs($oid);
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
			
			
		function getRegistersfromOID($oid,$MConfig=array())
			{
			$result=false;
			if (sizeof($MConfig)==0) 
				{
				//echo "Array ist leer. Default Config nehmen.\n";
				$MConfig=$this->MeterConfig;
				}
			foreach ($MConfig as $identifier => $meter)
				{
				if ( isset($meter["OID"]) == true )
					{
					if ($meter["OID"]==$oid) 
						{
						//$catID=IPS_GetCategoryIDByName($meter["NAME"], $this->parentid);
						$catID=IPS_GetVariableIDByName($meter["NAME"], $this->parentid);
						$result["EnergieID"]=IPS_GetVariableIDByName("Wirkenergie", $catID);
						$result["LeistungID"]=IPS_GetVariableIDByName("Wirkleistung", $catID);
						echo "    gefunden : ".$oid." in ".$identifier." und Kategorie ".$catID." (".IPS_GetName($catID).") \n";
						}				
					}
				}
			return ($result);				
			}																																																																																																																																																																																																																																																				

		/************************************************************************************************************************
 		 *
		 * Minuetlich von Momentanwerte abfragen Scipt aufgerufen.
 		 * Alle Energiewerte die als Register definiert sind auslesen, ignoriert andere Typen
 		 *
 		 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
		 *
		 *****************************************************************************************************************************/
		 
		function writeEnergyRegister($meter)		/* nur einen Wert aus der Config ausgeben */
			{
			$registerAvailable=false;

			if (strtoupper($meter["TYPE"])=="REGISTER")
				{
				$registerAvailable=true;
				echo "Werte von : ".$meter["NAME"]."\n";
			      
				$ID = CreateVariableByName($this->parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

				$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

				if ( isset($meter["OID"]) == true )
					{
					$HMenergieID = $meter["OID"];
					echo "  OID der Homematic Register selbst bestimmt : Energie : ".$HMenergieID." Leistung : nicht bekannt\n";
					}
				else
					{
					//$HMenergieID  = $meter["HM_EnergieID"];
					//$HMleistungID = $meter["HM_LeistungID"];
					}
				//$energie=GetValue($HMenergieID)/1000; /* Homematic Wert ist in Wh, in kWh umrechnen */
				$energie=GetValue($HMenergieID); /* Homematic Wert ist in kWh, nicht umrechnen */
				$leistung=($energie-GetValue($EnergieID))*4;

				SetValue($EnergieID,$energie);
				SetValue($LeistungID,$leistung);
				echo "  Werte aus dem Register : ".$energie." kWh  ".GetValue($HMenergieID)." W\n";
				}
			return ($registerAvailable);
			}

		/************************************************************************************************************************
 		 *
		 * Script MomentanwerteAbfragen wird minuetlich aufgerufen. Diese Routine kommt alle 15 Minuten dran.
 		 * Nur die SUMMEN Energiewerte bearbeiten, ignoriert andere Typen
 		 *
 		 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
		 *
		 *****************************************************************************************************************************/
		 
		function writeEnergySumme($meter)		/* nur einen Wert aus der Config ausgeben */
			{
			$registerAvailable=false;
			$energie=0;
			$leistung=0;

			if (strtoupper($meter["TYPE"])=="SUMME")
				{
				$registerAvailable=true;
				echo "Werte von : ".$meter["NAME"]."\n";
			      
				$ID = CreateVariableByName($this->parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

				$EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
				$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

				if ( isset($meter["Calculate"]) == true )
					{
					$calculate = explode(",",$meter["Calculate"]);
					echo "  die folgenden Register werden zusammengezählt:\n";
					print_r($calculate);
					foreach ($calculate as $oid)
						{
						$result=$this->getRegistersfromOID($oid);
						$energie+=GetValue($result["EnergieID"]);
						$leistung+=GetValue($result["LeistungID"]);
						echo "Energie : ".$energie." Leistung : ".$leistung."\n"; 
						}
						
					}
				$leistungVergleich=($energie-GetValue($EnergieID))*4;

				SetValue($EnergieID,$energie);
				SetValue($LeistungID,$leistung);
				echo "  Neue Werte : ".$energie." kWh  ".$leistung." kW    Zum Vergleich : ".$leistungVergleich." kW\n"; 
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
	
				$ID = CreateVariableByName($this->parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

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
 		 * Alle Homematic Energiesensoren der letzten Woche als Wert auslesen, ignoriert andere Typen
		 * gibt die Werte wenn nicht anders gewünscht mit einer html Formatierung aus
		 *
		 * die html Formatierung wird als <style> mit Klassen und mehreren <div> tags aufgebaut. 
		 * <html> und <body> tags werden nicht erstellt
		 *
		 *****************************************************************************************************************************/

		function writeEnergyRegistertoString($MConfig,$html=true)			/* alle Werte aus der Config ausgeben */
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
			foreach ($MConfig as $meter)
				{
				if (strtoupper($meter["TYPE"])=="HOMEMATIC")
					{
					echo "-----------------------------".$newline;
					echo "Werte von : ".$meter["NAME"].$newline;
					$ID = CreateVariableByName($this->parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

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
	      			echo "  Werte aus der Homematic : aktuelle Energie : ".number_format($energie, 2, ",", "" )." kWh  aktuelle Leistung : ".number_format(GetValue($HMleistungID), 2, ",", "" )." W".$newline;
	      			echo "  Energievorschub aktuell : ".number_format($energievorschub, 2, ",", "" )." kWh".$newline;
	      			echo "  Energiezählerstand      : Energie ".number_format(GetValue($EnergieID), 2, ",", "" )." kWh Leistung : ".number_format(GetValue($LeistungID), 2, ",", "" )." kW".$newline;
						
					/* Energiewerte der letzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr */
					$jetzt=time();
					$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
					$starttime=$endtime-60*60*24*10;
					$vorigertag=date("d.m.Y",$jetzt);	/* einen Tag ausblenden */
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
			echo "Gesamt Tabelle aufbauen\n";
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

			print_r($zeile);	
			if ($html==true) 
				{
				echo "</p>";
				$output.="</div> \n";   /* Umschalten auf Courier Font */
				$outputEnergiewerte.=$endtable."</div> \n";
				$outputTabelle.=$endtable."</div> \n";				
				}
			return ($style.$output."\n\n".$outputEnergiewerte."\n\n".$outputTabelle);
			}

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
			echo "Gesamt Tabelle aufbauen\n";
			for ($line=0;$line<($metercount);$line++)
				{
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
						echo "   Zählerwert für ".$Werte[$line]["Wochentag"][0]." vom Datum ".date("d.m", $zeit)." fehlt.\n  ";
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


		
		/*
		 * Vergleichsfunktion, welche Hardware ist installiert 
		 * und welche ist als Enrgieregister mit Archiv und Anzeige konfiguriert
		 * Ausgabe als Text-String
		 */
		 
		function getEnergyRegister($Meter=array())
			{
			$size=sizeof($Meter);
			$oids=array();
			//echo "Zähler-Eintraege im EnergyRegisterArray : ".$size."\n";
			for ($i=0;$i<$size;$i++)
				{
				$oids[$Meter[$i]["Information"]["Register-OID"]]=$Meter[$i]["Information"]["NAME"];
				//echo "  ".str_pad($Meter[$i]["Information"]["NAME"],28)."  ".$Meter[$i]["Information"]["OID"]."   ".$Meter[$i]["Information"]["Parentname"]."/".$Meter[$i]["Information"]["Register-OID"]."   \n";
				//print_r($Meter[$i]);
				}
			//print_r($oids);	
			//echo "\n\n";
			
			$alleStromWerte="";
		  	/* EvaluateHardware_include.inc wird automatisch nach Aufruf von EvaluateHardware erstellt */			
			IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
			$Homematic = HomematicList();
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
						$alleStromWerte.=str_pad($Key["Name"],30)." (".$oid.") = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")";
						}
					else
						{
						$alleStromWerte.=str_pad($Key["Name"],30)." (".$oid.")  = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")";
						}
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
			
			$alleStromWerte.="\n\n";
										  	
		  	/* EvaluateVariables_ROID.inc wird automatisch nach Aufruf von RemoteAccess erstellt , enthält Routine AmisStromverbrauchlist */
			IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");			
			$stromverbrauch=AmisStromverbrauchList();

			foreach ($stromverbrauch as $Key)
				{
      			$oid=(integer)$Key["OID"];
     			$variabletyp=IPS_GetVariable($oid);
				//print_r($variabletyp);
				if ($variabletyp["VariableProfile"]!="")
		   			{
					$alleStromWerte.= str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).") \n";
					}
				else
		   			{
					$alleStromWerte.= str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  \n";
					}
				}
			return($alleStromWerte);	
			}
		
		
		function getAMISDataOids()
			{
			return(CreateVariableByName($this->parentid, "Zusammenfassung", 3)); 
			}
			
		/* 
		 * zweiteilige Funktionalitaet, erst die Energieregister samt Einzelwerten der letzten Tage einsammeln und
		 * dann in einer zweiten Funktion die Ausgabe machen.
		 * Übergabe erfolgt als Array.
		 */
		
		function writeEnergyRegistertoArray($MConfig,$debug=false)
			{
			$zeile=array();
			$metercount=0;
			foreach ($MConfig as $meter)
				{
				if ($debug)
					{
					echo "-----------------------------\n";
					echo "Werte von : ".$meter["NAME"]."\n";
					}
				$meterdataID = CreateVariableByName($this->parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
				/* ID von Wirkenergie bestimmen */
				switch ( strtoupper($meter["TYPE"]) )
					{	
					case "AMIS":
						$AmisID = CreateVariableByName($meterdataID, "AMIS", 3);
						//$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
						//$variableID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
						$EnergieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $AmisID );
						$RegID=$EnergieID;
						break;
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
						$EnergieID = CreateVariableByName($meterdataID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
						break;		
					case "REGISTER":	
					default:
						$EnergieID = CreateVariableByName($meterdataID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
						$RegID=$EnergieID;
						break;
					}					


					
				/* Energiewerte der letzten 10 Tage als Zeitreihe beginnend um 1:00 Uhr */
				$jetzt=time();
				$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
				$starttime=$endtime-60*60*24*10;
				$vorigertag=date("d.m.Y",$jetzt);	/* einen Tag ausblenden */
				if ($debug) echo "Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime).":\n";

				$werte = AC_GetLoggedValues($this->archiveHandlerID, $EnergieID, $starttime, $endtime, 0);
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
				$laufend=1; $alterWert=0; 
				foreach($werte as $wert)
					{
					$zeit=$wert['TimeStamp']-60;
					//echo "    ".date("D d.m H:i", $wert['TimeStamp'])."   ".$wert['Value']."    ".$wert['Duration']."\n";
					if (date("d.m.Y", $zeit)!=$vorigertag)
						{
						$zeile[$metercount]["Datum"][$laufend] = date("d.m", $zeit);
						$zeile[$metercount]["Wochentag"][$laufend] = date("D  ", $zeit);
						if ($debug) echo "  Werte : ".date("D d.m H:i", $zeit)." ".number_format($wert['Value'], 2, ",", "" ) ." kWh\n";
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
				$metercount+=1;
				} /* ende foreach Meter Entry */
			
			return($zeile);
			}
		
										
			
		}  // ende class

	/** @}*/
?>