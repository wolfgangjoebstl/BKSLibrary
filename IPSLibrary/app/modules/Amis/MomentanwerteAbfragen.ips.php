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
IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');


/******************************************************
 *
 *			INIT
 *
 *************************************************************/

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');

$amis=new Amis();
$MeterConfig = $amis->getMeterConfig();
//print_r($MeterConfig);

/* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
$MeterReadID = CreateVariableByName($parentid, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
$TimeSlotReadID = CreateVariableByName($parentid, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */



/******************************************************
 *
 *			TIMER
 *
 *************************************************************/

if ($_IPS['SENDER']=="TimerEvent")
	{
	if (Getvalue($MeterReadID))
		{		
		foreach ($MeterConfig as $identifier => $meter)
			{
			
			/**********
			 *
			 * für jeden Timeslot alle Zähler durchgehen, also ganze Meter Config abarbeiten
			 *
			 ****************************/
			 
			switch (Getvalue($TimeSlotReadID))
				{
				case "15":  /* Auto */
					$amis->writeEnergySumme($meter);
					break;
				case "14":  /* Auto */
				case "13":  /* Auto */
				case "12":  /* Auto */
					break;
				case "11":  /* AMIS Zählerabfrage pro Com Port */
					$amis->sendReadCommandAmis($meter,$identifier,"F010");
					break;
				case "10":  /* Auto */
				case "9":  /* Auto */
					break;
				case "8":  /* AMIS Zählerabfrage pro Com Port */
					$amis->sendReadCommandAmis($meter,$identifier,"F001");
					break;
				case "7":  /* Auto */
					$amis->writeEnergyHomematic($meter);
					break;
				case "6":  /* Auto */
				case "5":  /* Auto */
				case "4":  /* Auto */
					$amis->writeEnergyRegister($meter);
					break;					
				case "3":  /* Auto */
				case "2":  /* Auto */
					break;
				case "1":
					$amis->sendReadCommandAmis($meter,$identifier,"F009");
					break;
				default:
					Setvalue($TimeSlotReadID,1);
					break;
				}  /* ende switch */
			}  /* ende foreach, alle Zähler durchgehen pro timeslot */
		}  /* endeif Meterread aktiviert */
	else
		{
		echo "MeterRead deaktiviert, keine Zählwerte definiert.\n";
		}  /* endeif Meterread deaktiviert */
	
	/* einen Timeslot weiterzählen */
	
	if (Getvalue($TimeSlotReadID)==15)
		{
		Setvalue($TimeSlotReadID,1);
		}
	else
		{
		Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);	
		}			
	} // Ende if timer
	
/******************************************************
 *
 *			EXECUTE
 *
 *************************************************************/	
	
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

	echo "\nAlle Cutter Instanzen auflisten:\n";		
	$cutterIDs = IPS_GetInstanceListByModuleID("{AC6C6E74-C797-40B3-BA82-F135D941D1A2}");
	foreach ($cutterIDs as $num => $cutter)
		{
		echo "  Cutter ".$num." mit OID ".$cutter." und Bezeichnung ".IPS_GetName($cutter)."\n";
		$result=IPS_getConfiguration($cutter);
		echo "        ".$result."\n";		
		}

	echo "\nAlle Socket Instanzen auflisten:\n";
	$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur Splitter Instanzen auflisten
	//print_r($alleInstanzen);
	foreach ($alleInstanzen as $instanz)
	   {
	   $datainstanz=IPS_GetInstance($instanz);
	   echo " ".$instanz." Name : ".IPS_GetName($instanz)."\n";
	   }

	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	
	echo "\n********************************************VALUES**************************************************************\n\n";
	$homematic=$amis->writeEnergyHomematics($MeterConfig);  // alle Homematic schreiben
	}


	
?>