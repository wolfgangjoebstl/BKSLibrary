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


/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur Berechnung der Energiewerte aus Homematic und AMIS Zählern. Hat auchen einen Execute Teil zur Unterstützung des Debuggings.
     * Mehr übergeordnetes Debugging ist in AMIS.
	 *
	 * wird alle 60 Sekunden aufgerufen, es gibt 15 Timeslots, Je timeslot werden alle registrierten Zählertypen je nach Anforderung bearbeitet
     * kommt vom AMIS Zähler der etwas langsam bei der Augabe der Daten ist.
	 *
	 * Timelslot 
     *     1        sendReadCommandAmis "F009"
     *     2
     *     3
     *     4        writeEnergyRegister
     *     5
     *     6        sendReadCommandAmis "F009"
     *     7        writeEnergyHomematic
     *     8        sendReadCommandAmis "F001"
     *     9
     *    10
     *    11        sendReadCommandAmis "F010"
     *    12
     *    13
	 *    14
     *    15        writeEnergySumme
	 *
     * Wichtig ist das klar ist das alle Ansaetze der Berechnung eine sinnvolle Berechtigung haben
     * Behandlung als Event : sekundengenaue Aktualisiserung und Berechnung von Summen
     * Behandlung als Patch : 15 Minutenweise Berechnung hier 
     *
     * Master für die komplette Steuerung der Funktionsweise ist das Configfile. Nicht alle Register die
     * eine Energiemessung unterstützen sollen übernommen werden. Der Wert kann zu klein oder Teil einer übergeordneten Messung sein.
     *          $amis=new Amis(); $MeterConfig = $amis->getMeterConfig();
     *
     * writeEnergyHomematic ist Teil der AMIS class
     *   Aufruf mit dem Einzeleintrag der Konfiguration ohne identifier
     *
     *
     *
     * writeEnergySumme
     * jeweils für jeden Zähler einmal alle 15 Minuten aufgerufen. meter[Type] muss Summe sein.
     * im Parameter meter[Calcualte]stehen die Register die zusammengezählt werden sollen drinnen.
     *
     *
     *
     *
     *
     *
     *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');


/******************************************************
 *
 *			INIT
 *
 *************************************************************/
	
    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $amis=new Amis();
    $MeterConfig = $amis->getMeterConfig();

    /* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
    $MeterReadID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
    $TimeSlotReadID = CreateVariableByName($CategoryIdData, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */



/******************************************************
 *
 *			TIMER
 *
 *************************************************************/

if ($_IPS['SENDER']=="TimerEvent")          // alle 60 Sekunden
	{
	if (Getvalue($MeterReadID))
		{		
		foreach ($MeterConfig as $identifier => $meter)
			{
			
			/**********
			 *
			 * für jeden Timeslot alle Zähler durchgehen, also ganze Meter Config abarbeiten, 
             * bitte beachten:
             * das serielle AMIS Interface mit 300 baud ist sehr langsam, damit können nicht zuviele register zur selben Zeit abgefragt werden
			 *
			 ****************************/
			 
			switch (Getvalue($TimeSlotReadID))
				{
				case "15":  /* Auto */
					$amis->writeEnergySumme($meter);            // meter["TYPE"]=="SUMME"  einen Summenwert berechnen
					break;
				case "14":  /* Auto */
    				$dataOID=$amis->getAMISDataOids();
                    $regID = CreateVariableByName($dataOID, "Aktuelle-Energie", 3);
                    $Meter=$amis->writeEnergyRegistertoArray($MeterConfig);
                    SetValue($regID,$amis->writeEnergyRegisterValuestoString($Meter));
                    //SetValue($tableID,$amis->writeEnergyRegisterTabletoString($Meter));
                    //$tableID = CreateVariableByName($dataOID, "Historie-Energie", 3);
                    break;
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
                    $amis->sendReadCommandAmis($meter,$identifier,"F009");                
                    break;                
				case "5":  /* Auto */
				case "4":  /* Auto */
					$amis->writeEnergyRegister($meter);     // $meter["TYPE"])=="REGISTER"
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
    $debug=false;
    echo "===========================================================\n";
    echo "Execute aufgerufen:\n";        
    echo "Amis::MomentwerteAbfragen Ausgabe der Konfiguration:\n";    
    print_r($MeterConfig);                                                      // meter config von AMIS class ausgelesen

	echo "********************************************CONFIG**************************************************************\n\n";

	echo  "Genereller Meter Read eingeschaltet : ".GetvalueIfFormatted($MeterReadID)."\n";
	echo  "Aktueller Timeslot der 15x 1 Minuten Intervalle : ".GetValue($TimeSlotReadID)."\n\n"; 
		
	echo "Konfiguration für Zaehlerauslesung: \n\n";	
	foreach ($MeterConfig as $identifier => $meter)
		{
		$ID = CreateVariableByName($CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
		echo "Category/Variable for : ".str_pad($meter["NAME"],30)." ".$meter["TYPE"]."\n";
		if (strtoupper($meter["TYPE"])=="AMIS")
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
        elseif (strtoupper($meter["TYPE"])=="SUMME")
			{
            $amis->writeEnergySumme($meter,$debug);               // true für Debug
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

	echo "\n********************************************VALUES**************************************************************\n\n";
	//$homematic=$amis->writeEnergyHomematics($MeterConfig);  // alle Homematic Register schreiben, verwirrt die 15 minütige Erfassung, daher nicht mehr verwendet
    echo $amis->writeEnergyRegistertoString($MeterConfig,true,true);            // output asl html (true) und mit debug (true) aber ebenfalls nur für die Homematic Register
	}


	
?>