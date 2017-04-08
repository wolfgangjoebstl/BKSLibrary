<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur Berechnung der Energiewerte aus Homematic und AMIS Zählern
	 *
	 * wird alle 60 Sekunden aufgerufen, es gibt 15 Timeslots, Je timeslot werden alle registrierten Zählertypen je nach Anforderung bearbeitet
	 *
	 * Timelsot 1,8,11 für AMIS Zähler
	 * Timeslot 7 für Homematic
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');

/******************************************************
 *
 *			INIT
 *
 *************************************************************/

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');

IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
$MeterConfig = get_MeterConfiguration();
//print_r($MeterConfig);

/* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
$MeterReadID = CreateVariableByName($parentid, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
$TimeSlotReadID = CreateVariableByName($parentid, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */

if ($_IPS['SENDER']=="TimerEvent")
	{
	if (Getvalue($MeterReadID))
		{		
		foreach ($MeterConfig as $identifier => $meter)
			{
			//echo"-------------------------------------------------------------\n";
			//echo "Create Category/Variable for : ".$meter["NAME"]." \n";
			$ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
			if ($meter["TYPE"]=="Amis")
				{
				$amismetername=$meter["NAME"];
				//echo "  Create Variableset for AMIS :".$meter["NAME"]." \n";
				$AmisID = CreateVariableByName($ID, "AMIS", 3);
				
				$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
				$SendTimeID = CreateVariableByName($AmisID, "SendTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
				
				$AmisReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */

				// Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
				$AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);
				$AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);

				// Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
				$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
				$variableID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
			
				//Hier die COM-Port Instanz festlegen
				$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
				foreach ($serialPortID as $num => $serialPort)
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
							$configPort[$regVarID]["Name"]=$amismetername;	
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
				} /* endeif AMIS */					
			switch (Getvalue($TimeSlotReadID))
				{
				case "15":  /* Auto */
				case "14":  /* Auto */
				case "13":  /* Auto */
				case "12":  /* Auto */
					break;
				case "11":  /* Auto */
					if (isset($AmisReadMeterID)==true)
						{
						if (Getvalue($AmisReadMeterID))
							{
							Setvalue($SendTimeID,time());
							COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
							IPS_Sleep(1550);
							COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> */
							IPS_Sleep(1550);
							COMPort_SendText($com_Port ,"\x01\x52\x32\x02F010(*.7.*.*)\x03$");    /* <SOH>R2<STX>F010(*.7.*.*)<ETX> */
							$handlelog=fopen("C:\Scripts\Log_AMIS.csv","a");
							$ausgabewert=date("d.m.y H:i:s").";"."Abfrage R2-F010\n";
							fwrite($handlelog, $ausgabewert."\r\n");
							fclose($handlelog);
							}
						}
					break;
				case "10":  /* Auto */
				case "9":  /* Auto */
					break;
				case "8":  /* Auto */
					if (isset($AmisReadMeterID)==true)
						{
						if (Getvalue($AmisReadMeterID))
							{
							Setvalue($SendTimeID,time());
							COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
							IPS_Sleep(1550);
							COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> */
							IPS_Sleep(1550);
							COMPort_SendText($com_Port ,"\x01\x52\x32\x02F001()\x03\x17");    /* <SOH>R2<STX>F001()<ETX> */
							$handlelog=fopen("C:\Scripts\Log_AMIS.csv","a");
							$ausgabewert=date("d.m.y H:i:s").";"."Abfrage R2-F001\n";
							fwrite($handlelog, $ausgabewert."\r\n");
							fclose($handlelog);
							}
						}
					break;
				case "7":  /* Auto */
					writeEnergyHomematic($meter);
					break;
				case "6":  /* Auto */
				case "5":  /* Auto */
				case "4":  /* Auto */
				case "3":  /* Auto */
				case "2":  /* Auto */
					break;
				case "1":
					if (isset($AmisReadMeterID)==true)
						{
						if (Getvalue($AmisReadMeterID))
							{
							Setvalue($SendTimeID,time());
							COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
							IPS_Sleep(1550);
							COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> auf 300 baud bleiben */
							IPS_Sleep(1550);
							COMPort_SendText($com_Port ,"\x01\x52\x32\x02F009()\x03\x1F");    /* <SOH>R2<STX>F009()<ETX> checksumme*/
							$handlelog=fopen("C:\Scripts\Log_AMIS.csv","a");
							$ausgabewert=date("d.m.y H:i:s").";"."Abfrage R2-F009\n";
		 					fwrite($handlelog, $ausgabewert."\r\n");
							fclose($handlelog);
							}
						}
					break;
				default:
					Setvalue($TimeSlotReadID,1);
					break;
				}  /* ende switch */
			}  /* ende foreach, alle Zäähler durchgehen pro timeslot */
		}  /* endeif Meterread aktiviert */
	else
		{
		echo "MeterRead deaktiviert, keine Zählwerte definiert.\n";
		}
	
	if (Getvalue($TimeSlotReadID)==15)
		{
		Setvalue($TimeSlotReadID,1);
		}
	else
		{
		Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);	
		}			
	} // Ende if timer
	
if ($_IPS['SENDER']=="Execute")
	{
	echo "********************************************CONFIG**************************************************************\n\n";

	echo  "Genereller Meter Read eingeschaltet : ".GetvalueFormatted($MeterReadID)."\n";
	echo  "Aktueller Timeslot der 15x 1 Minuten Intervalle : ".GetValue($TimeSlotReadID)."\n\n"; 
		
	echo "Konfiguration für Zaehlerauslesung: \n\n";	
	foreach ($MeterConfig as $identifier => $meter)
		{
		$ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
		echo "Category/Variable for : ".str_pad($meter["NAME"],30)." ".$meter["TYPE"]."\n";
		if ($meter["TYPE"]=="Amis")
			{
			$AmisID = CreateVariableByName($ID, "AMIS", 3);			
			$AmisReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */	
			echo "  AMIS Meter Read eingeschaltet       : ".GetvalueFormatted($AmisReadMeterID)."\n";
	
			//Hier die COM-Port Instanz festlegen
			$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
			foreach ($serialPortID as $num => $serialPort)
				{
				if (IPS_GetName($serialPort) == $identifier." Serial Port")   { $com_Port = $serialPort; }
				if (IPS_GetName($serialPort) == $identifier." Bluetooth COM") { $com_Port = $serialPort; }
				}
			if (isset($com_Port) === false) { echo "  Kein AMIS Zähler Serial Port definiert\n"; break; }
			else { echo "  AMIS Zähler Port auf OID ".$com_Port." definiert.\n"; }
			}
		//print_r($meter);
		}

	$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
	echo "\nAlle Seriellen Ports auflisten:\n";
	foreach ($serialPortID as $num => $serialPort)
		{
		echo "  Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";
		}
	//echo "Alle I/O Instanzen\n";
	//$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur I/O Instanzen auflisten

	//echo "Alle Kern Instanzen\n";
	//$alleInstanzen = IPS_GetInstanceListByModuleType(0); // nur Kern Instanzen auflisten

	echo "\nAlle Splitter Instanzen auflisten:\n";
	$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur Splitter Instanzen auflisten
	//print_r($alleInstanzen);
	foreach ($alleInstanzen as $instanz)
	   {
	   $datainstanz=IPS_GetInstance($instanz);
	   echo " ".$instanz." Name : ".IPS_GetName($instanz)."\n";
	   }

	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	
	echo "\n********************************************VALUES**************************************************************\n\n";
	$homematic=writeEnergyHomematics($MeterConfig);  // alle Homematic schreiben
	}


/************************************************************************************************************************
 *
 * Alle Homematic Energiesensoren auslesen
 *
 * es wird ein String mit dem Namen als Kategorie angelegt und darunter die Variablen gespeichert
 *
 *****************************************************************************************************************************/

function writeEnergyHomematics($MConfig)
	{
	$homematic=false;
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];
	foreach ($MConfig as $meter)
		{
		if ($meter["TYPE"]=="Homematic")
	   	{
	   	$homematic=true;
	   	echo "Werte von : ".$meter["NAME"]."\n";
	   		      
	      $ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

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
		}
	return ($homematic);
	}

function writeEnergyHomematic($meter)
	{
	$homematic=false;
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];
	if ($meter["TYPE"]=="Homematic")
	   	{
	   	$homematic=true;
	   	echo "Werte von : ".$meter["NAME"]."\n";
	   		      
	    $ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */

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
	return ($homematic);
	}

	
?>