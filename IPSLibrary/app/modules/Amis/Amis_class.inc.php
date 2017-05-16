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


	class Amis {


		var $parentid=0;
		var $archiveHandlerID=0;
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



			}

		/************************************************************************************************************************
 		 *
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
				
				
				if (Getvalue($AmisReadMeterID))
					{
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
						$OID  = $meter["OID"];
						$cids = IPS_GetChildrenIDs($OID);
						if (sizeof($cids) == 0) 
							{
							$OID = IPS_GetParent($OID);
							$cids = IPS_GetChildrenIDs($OID);
							}
						echo "OID der passenden Homematic Register selbst bestimmen. Wir sind auf ".$OID." (".IPS_GetName($OID).")\n";
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
					$OID  = $meter["OID"];
					$cids = IPS_GetChildrenIDs($OID);
					if (sizeof($cids) == 0)		/* vielleicht schon das Energy Register angegeben, mal eine Eben höher schauen */ 
						{
						$OID = IPS_GetParent($OID);
						$cids = IPS_GetChildrenIDs($OID);
						}					
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
			return ($homematicAvailable);
			}

		/************************************************************************************************************************
 		 *
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
				$energie=GetValue($HMenergieID)/1000; /* Homematic Wert ist in Wh, in kWh umrechnen */
				$leistung=($energie-GetValue($EnergieID))*4;

				SetValue($EnergieID,$energie);
				SetValue($LeistungID,$leistung);
				echo "  Werte aus dem Register : ".$energie." kWh  ".GetValue($HMenergieID)." W\n";
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
			
		}  // ende class

	/** @}*/
?>