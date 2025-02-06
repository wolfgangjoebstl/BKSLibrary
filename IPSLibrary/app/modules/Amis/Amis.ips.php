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
     * AMIS, Auslesung von Energie und Leistungsregistern, Auswertung im 15 Minutenraster
     * 
     * In Amis_Configuration eine Config erstellen : Rückgabewert von  get_MeterConfiguration
     * Berechnung der Energiewerte schwierig. DetectMovementLib zählt Gruppen zusammen. 
     * Die Config listet alle Energieregister aus denen 15 Min Werte gebildet werden sollen auf. Muss nicht automatisch alle Energieregister sein.
     * In der Config wird angeführt welche register Sub sidn, diese werden unter ihrem Parent dargestellt.
     * Damit anzeigen wenn Werte nicht zum gesamtverbrauch beitragen sondern eine zusätzliche detaillierung übernehmen.
     * Parent muss ein Messgerät sein. 
     *
     * Script zur Auslesung von Energiewerten. Diese Script übernimmt die Webfrontvariablen Bearbeitung und wird zusaetzlich zu Testzwecken weiterhin verwendet
     * Regelmaessiger Aufruf wird jetzt von MomentanwerteAbfragen uebernommen. Die Antwort eines AMIS Zähler wird automatisch von AMIS Cutter bearbeitet sobald der Wert verfügbar ist
     *
     * Diese Routine kann als Einstieg verwendet werden um einen Überblick über die Installation zu bekommen
     *
     * webfront verarbeitung allgemein und speziell für localdata->energiemessung->smart meter
     * es geht um Buttons innerhalb einer htmlBox, diese mit iFrame anlegen
     *      InterActive     <iframe .. src="../user/Guthabensteuerung/GuthabensteuerungReceiver.php
     * testweise siehe SetupWebfront
     *      InterActive     <iframe .. src="../user/Guthabensteuerung/Guthabensteuerung.php                     in Debug
     *      TestALot        <iframe .. src="../user/Guthabensteuerung/GuthabensteuerungTables.php
     *
     * iFrame gibt es zB für 
     * es gibt wie Guthabensteuerung update,calculate,sort
     *
     * @file      
     * @author        Wolfgang Joebstl
     * @version
     *  Version 4.0 13.6.2016
     */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
    IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');
    IPSUtils_Include ('Amis_Constants.inc.php', 'IPSLibrary::app::modules::Amis');


	/******************************************************
     *
	 *			INIT
     *
	*************************************************************/
	
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $installedModules = $moduleManager->GetInstalledModules();
    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

    if ($_IPS['SENDER'] == "Execute") $debug=true;
    else $debug=false;

    if (isset($installedModules["Guthabensteuerung"]))
        {
        if ($debug) echo "Guthabensteuerung ist installiert.\n";
        IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
        IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");					// Library verwendet Configuration, danach includen
        }
    elseif ($debug) echo "Guthabensteuerung ist NICHT installiert.\n";

	$amis=new Amis();           // Ausgabe SystemDir, erstellt MeterConfig

    $webOps = new webOps();

    $categoryId_SmartMeter      = IPS_GetObjectIDByName('SmartMeter', $CategoryIdData);
    //$pnames = ["Directory","Update","Calculate","Sort"];                                    // muss gleich sein
    $webOps->setSelectButtons(SMART_SELECT,$categoryId_SmartMeter);
    $buttonsId = $webOps->getSelectButtons();
    
    /*print_r($buttonsId);
    $updateApiTableID           = IPS_GetObjectIDByName("Update", $categoryId_SmartMeter);           // button profile is Integer
    $calculateApiTableID        = IPS_GetObjectIDByName("Calculate", $categoryId_SmartMeter);           // button profile is Integer
    $sortApiTableID             = IPS_GetObjectIDByName("Sort", $categoryId_SmartMeter);           // button profile is Integer    */

    //echo "new AmisSmartMeter : ";
    $amisSM = new AmisSmartMeter();             // verwendet class GuthabenHandler wenn vorhanden

    $MeterStatusConfig=$amisSM->getAmisConfig();
    $configCsv=$MeterStatusConfig["File"]["INPUTCSV"];

    $statusDirectoryID = IPS_GetObjectIDByName("DirectoryStatus",$categoryId_SmartMeter);
    $statusSmartMeterID = IPS_GetObjectIDByName("SmartMeterStatus",$categoryId_SmartMeter);
    //echo " Done\n";

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet 
     *      Update      SmartMeterStatus        $amisSM->writeSmartMeterDataToHtml()
     *      Calculate   NewLookandFeel          nur update htmlBox zum Nachladen
     *      Sort        InterActive             html verlinkt auf ein webfront script file, das wird wo anders gesetzt
     *
     * Update erzeugt neue Smart Meter Register Übersicht mit 24h Counter und Tageswerten, den 15min Werten von InputCsv 
     * und als weitere Referenz die logWien Daten die von Selenium ermittelt werden.
     */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);

    switch ($_IPS['VARIABLE'])
        {
        case $buttonsId[0]["ID"]:         // Directory
            $webOps->selectButton(0);
            $MeterStatusConfig=$amisSM->getAmisConfig();
            $config=array();
            $config["File"]=$MeterStatusConfig["File"];
            SetValue($statusDirectoryID,$amisSM->writeSmartMeterCsvInfoToHtml(false,$config,false));
            break;
        case $buttonsId[1]["ID"]:         // Update
            $webOps->selectButton(1);
            SetValue($statusSmartMeterID,$amisSM->writeSmartMeterDataToHtml());
            break;
        case $buttonsId[2]["ID"]:         // Calculate
            $webOps->selectButton(2);

            ini_set('memory_limit', '128M');                        // können grosse Dateien werden            
            $oid=$configCsv["Target"]["OID"];
            //$configCsv["Target"]["Column"]="Value1";              // manual overwrite
            $files=$amisSM->readDirectory($configCsv);
            $archiveOps = new archiveOps();                        // alle Archive einlesen, noch nicht festlegen
            foreach ($files as $file) $archiveOps->addValuesfromCsv($file,$oid,$configCsv,false);             // debug false schreibt die werte ins Archive
            SetValue($statusSmartMeterID,$amisSM->writeSmartMeterDataToHtml());
            break;
       case $buttonsId[3]["ID"]:         // Transfer
            $webOps->selectButton(3);
            $variableIdLookAndFeelHTML = IPS_GetObjectIdByName("NewLookAndFeel",$categoryId_SmartMeter);
            SetValue($variableIdLookAndFeelHTML,GetValue($variableIdLookAndFeelHTML));
            break;            
       case $buttonsId[4]["ID"]:         // LookandFeel
            $webOps->selectButton(4);
            $variableIdLookAndFeelHTML = IPS_GetObjectIdByName("NewLookAndFeel",$categoryId_SmartMeter);
            SetValue($variableIdLookAndFeelHTML,GetValue($variableIdLookAndFeelHTML));
            break;
        case $buttonsId[5]["ID"]:         // Interactive
            $webOps->selectButton(5);
            $variableIdInterActiveHTML = IPS_GetObjectIdByName("InterActive",$categoryId_SmartMeter);
            SetValue($variableIdInterActiveHTML,GetValue($variableIdInterActiveHTML));
            break;

        }

	}
else
	{	
    ini_set('memory_limit', '128M');       //usually it is 32/16/8/4MB 
    $debug=false;
    echo "Script called due to an other event than a Webfront Interaction.\n";
	$MeterConfig = $amis->getMeterConfig();
	$AmisConfig = $amis->getAmisConfig();

    $statusSmartMeterID = IPS_GetObjectIDByName("SmartMeterStatus",$categoryId_SmartMeter);
    echo "SmartMeterStatus     $statusSmartMeterID \n";
    echo GetValue($statusSmartMeterID);
    if (isset($installedModules["RemoteAccess"]))
        {
        echo "RemoteAccess ist installiert.\n";
        IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
        IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
	    $remote=new RemoteAccess();
        $remote->add_Amis();
        echo "\n".($remote->show_includeFile())."\n";
        }

    if ($debug>1) 
        {
        echo "AMIS Configuration:\n";
	    print_r($AmisConfig);
        echo "Meter Configuration:\n";
	    print_r($MeterConfig);
        }

    if (isset($installedModules["OperationCenter"]))
        {
        IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');            
        $DeviceManager = new DeviceManagement();
        echo "--------------------------------\n";
        $result=$DeviceManager->updateHomematicAddressList();
        if ($debug) 
            {
            echo "Modul OperationCenter installiert. Qualität des Inventory evaluieren.\n";
            if ($result) echo "    --> Alles in Ordnung, HMI_CreateReport wird regelmaessig aufgerufen.\n";
            else "Fehler HMI_CreateReport muss schon wieder aufgerufen werden.\n";
            echo "\n";
            }
        }

    if (isset($installedModules["EvaluateHardware"]))
        {
        if ($debug) 
            {
            echo "Modul EvaluateHardware installiert. Ausführliches Inventory vorhanden.\n";
            echo "========================================================================\n"; 
            }   
        IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
        IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

        IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
        IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');           // sonst werden die Event Listen überschrieben
        IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');

        $hardwareTypeDetect = new Hardware();
        $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php
        if ($debug>1)
            {
            echo "Statistik der Devicelist nach Typen, Aufruf der getDeviceStatistics in HardwareLibrary:\n";
            $statistic = $hardwareTypeDetect->getDeviceStatistics($deviceList,false);                // false keine Warnings ausgeben
            print_r($statistic);
            echo "========================================================================\n";    
            echo "Statistik der Register nach Typen:\n";
            $statistic = $hardwareTypeDetect->getRegisterStatistics($deviceList,false);                // false keine Warnings ausgeben
            $hardwareTypeDetect->writeRegisterStatistics($statistic);        
            }
        $deviceListFiltered = $hardwareTypeDetect->getDeviceListFiltered(deviceList(),["TYPECHAN" => "TYPE_METER_POWER"],"Install");     // true with Debug, Install hat keinen Einfluss mehr, gibt nur mehr das
        $amis->doublecheckEnergyRegisters($deviceListFiltered,$debug);
        /*print_r($deviceListFiltered);
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
        if ($debug)
            {
            echo "-------------------------------------------------------------\n";
            echo "Analysing the AMIS Meter Configuration:\n";                                   // Die Konfiguration durchgehen, bekannte Register löschen und schauen was am Ende noch da ist
            }
        foreach ($MeterConfig as $identifier => $meter)
            {
            if (strtoupper($meter["TYPE"])=="HOMEMATIC")
                {
                $variableID = $amis->getWirkenergieID($meter);      // kurze Ausgabe suche nach found as
                if ($debug) echo " ".str_pad($meter["NAME"],35).IPS_GetName($meter["OID"])." Konfig : ".json_encode($meter)."     $variableID ".IPS_GetName($variableID)."\n";
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
                else echo "   --> unknown ".$meter["OID"]." ".IPS_GetName($meter["OID"])."\n"; 

                }
            }
        //echo"-------------------------------------------------------------\n";
        //print_r($energyMeter);
        echo"-------------------------------------------------------------\n";
        echo "Register marked with *** is not in the AMIS configuration.\n";
        foreach ($energyMeterAll as $oid => $register)
            {
            $props=IPS_GetVariable($oid);
            if (isset($energyMeter[$oid])) echo " *** $oid : ";
            else                           echo "     $oid : ";
            echo str_pad(IPS_GetName(IPS_GetParent($oid)),50).str_pad(GetValueIfFormatted($oid),20," ",STR_PAD_LEFT)."   ".date("d.m.Y H:i:s",$props["VariableChanged"])."      ";
            if (isset($energyMeterName[$oid])) echo $energyMeterName[$oid]."\n";
            else echo "\n";            
            }
        echo"-------------------------------------------------------------\n";   */

        }       // ende EvaluateHardware


	/* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
	$MeterReadID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$configPort=array();

    echo"-------------------------------\n";
    $amisFound=false;
	foreach ($MeterConfig as $identifier => $meter)
		{
		if ($debug) echo "Create Variableset for : ".str_pad($meter["NAME"],35)." Konfig : ".json_encode($meter)."\n";
		$ID = CreateVariableByName($CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
		if ($meter["TYPE"]=="Amis")
		    {
            $amisFound=true;
		    $amismetername=$meter["NAME"];			
            echo "AMIS Zähler Configuration überprüfen, vergleiche mit class getPortConfiguration:\n";
			echo "Amis Zähler, verfügbare Ports:\n";			
		
			$AmisID = CreateVariableByName($ID, "AMIS", 3);
			$AmisReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$TimeSlotReadID = CreateVariableByName($AmisID, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
			$SendTimeID = CreateVariableByName($AmisID, "SendTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */

			// Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
			$AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);
			$AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);

			// Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
			$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
			$variableID = CreateVariableByName($zaehlerid,'Wirkenergie', 2);
			
			//Hier die COM-Port Instanz
			$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
			foreach ($serialPortID as $num => $serialPort)
			   {
			   if ($debug) echo "Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";

				/********* COM Port ******************/				
			   if (IPS_GetName($serialPort) == $identifier." Serial Port") 
					{ 
					$com_Port = $serialPort;
					$regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
					if (IPS_InstanceExists($regVarID) )
	   				{
						echo "     Registervariable : ".$regVarID."\n";
						$configPort[$regVarID]=$amismetername;							 
						}
					$config = IPS_GetConfiguration($com_Port);
					echo "Comport Serial aktiviert. Konfiguration: ".$config." \n";
					$stdobj = json_decode($config);
					$ergebnis=json_encode($stdobj);
					echo "      ede/encode zum Vergleich ".$ergebnis."\n";
					print_r($stdobj);	
					echo "Comport Status : ".$stdobj->Open."\n";
					$remove = array("{", "}", '"');
					$config = str_replace($remove, "", $config);
					$Config = explode (',',$config);
					$AllConfig=array();
					foreach ($Config as $configItem)
						{
						$items=explode (':',$configItem);
						$Allconfig[$items[0]]=$items[1];
						}
					print_r($Allconfig);
					if ($Allconfig["Open"]==false) 
						{
						COMPort_SetOpen($com_Port, true); //false für aus
						IPS_ApplyChanges($com_Port);
						}
					else
						{
						echo "Port ist offen.\n";
						}
					COMPort_SetDTR($com_Port , true); /* Wichtig sonst wird der Lesekopf nicht versorgt */
					}
					
				/********* Bluetooth ******************/			
			   if (IPS_GetName($serialPort) == $identifier." Bluetooth COM") 
					{ 
					$com_Port = $serialPort; 
					$regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
					if (IPS_InstanceExists($regVarID) )
	   					{
						echo "     Registervariable : ".$regVarID."\n";
						$configPort[$regVarID]=$amismetername;	 
						}
					$status=IPS_GetProperty($com_Port,"Open");
					if ($status==true)
						{
						$status=@COMPort_SendText($com_Port ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */
						if ($status==true)
							{
							echo "Comport Bluetooth aktiviert. \n";
							}
						else	
							{
							echo "Comport Bluetooth aktiv. Fehler beim Senden von Text. \n";
							}
						}
					else
						{
						echo "Comport Bluetooth nicht aktiviert. \n";						
						}	
					}
				}
			if (isset($com_Port) === false) { echo "Kein AMIS Zähler Serial Port definiert\n"; break; }
			else { echo "\nAMIS Zähler Serial Port auf OID ".$com_Port." definiert.\n"; }
			}
        //else echo "No configuration for AMIS Meter found.\n";
		//echo "\nZählerkonfiguration: \n";
		//print_r($meter);
		}

	echo "\nGenereller Meter Read eingeschaltet:".GetvalueFormatted($MeterReadID)."\n";
	if (isset($AmisReadMeterID)==true)
		{
		echo "AMIS Meter Read eingeschaltet:".GetvalueFormatted($AmisReadMeterID)." auf Com-Port : ".$com_Port."\n";
		}
	else
		{	
		echo "AMIS Meter Read ausgeschaltet.\n";
		}
    if ($amisFound===false) echo "Info, kein AMIS Zähler konfiguriert.\n";

	} // ende else Webfront Aufruf
	

if ($_IPS['SENDER'] == "Execute")
	{
	
	/******************************************************

				STATUS

	*************************************************************/

	//Hier die COM-Port Instanz
    echo "\n";
    echo "----------------------------------------------------\n";
	echo "--------Execute aufgerufen -------------------------\n";
    echo "----------------------------------------------------\n"; 
    echo "\n";   
    //echo "Data OID der AMIS Zusammenfassung : ".$amis->getAMISDataOids()."\n\n";
    //$debug=2;
    echo "amis->writeEnergyRegistertoArray(MeterConfig aufgerufen. Debug ".($debug?"ja":"nein")."\n";           // beim ? ist zuerst true und dann false
    $meterValues=$amis->writeEnergyRegistertoArray($MeterConfig, ($debug>1));
    //print_R($meterValues);
    echo "amis->writeEnergyRegisterTabletoString(meterValues aufgerufen.\n";
    echo $amis->writeEnergyRegisterTabletoString($meterValues);
    echo "\n----------------------------------------------------\n";
    echo $amis->getEnergyRegister($meterValues,$debug);
    echo "\n----------------------------------------------------\n";
    echo $amis->writeEnergyRegistertoString($MeterConfig,true,$debug);            // output asl html (true) und mit debug (true), sehr lange Ausgabe
	echo "\n----------------------------------------------------\n";
    echo "\nUebersicht Homematic Registers:\n";
	foreach ($MeterConfig as $identifier => $meter)
		{	
        $amis->writeEnergyHomematic($meter,$debug);           // true für Debug, macht nette Ausgabe
        }

	echo "\nUebersicht serielle Ports:\n";
	foreach ($MeterConfig as $identifier => $meter)
		{	
		$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
		foreach ($serialPortID as $num => $serialPort)
			{
			if (IPS_GetName($serialPort) == $identifier." Serial Port") 
				{ 			
				echo "  Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";
				$config = IPS_GetConfiguration($serialPort);
				echo "    ".$config."\n";
				
				/* Standard Verrechnungsdatensatz kurz Abfrage, da kommen einige Daten zurück  */
				$amis->sendReadCommandAmis($meter,$identifier,"F001");
				}
			}
		}	
	
	echo "\n";
	print_r($configPort);
	if (isset($AmisReadMeterID)==true)
		{	
		echo "----------------------\n";
		//SetValue($AMISReceiveCharID,"");
		echo GetValue($AMISReceiveCharID);
		echo "----------------------\n";	
		echo GetValue($AMISReceiveChar1ID);
		}
    
    echo "======================aboutMeterTopology============\n";

    $amisTopo = new AmisTopology();

    $meterTopology=array(); 
    if ($amisTopo->createMeterTopology($meterTopology,$MeterConfig,true)===false) $amisTopo->createMeterTopology($meterTopology,$MeterConfig);     //zweimal aufrufen       
    print_R($meterTopology);
    //print_R($meterValues);
    //$meterValues=array();         // werden weiter oben erzeugt und zusätzlich ausgegeben
    echo "printMeterTopology:\n";
    $amisTopo->printMeterTopology($meterTopology,$meterValues,"",true);         // true für debug

    echo "\n";
    echo "=====================================================\n";
    echo "Archivierte Werte bearbeiten:\n";
    echo "=====================================================\n";
    echo "\n";

    $archiveOps = new archiveOps(); 
    $archiveID = $archiveOps->getArchiveID();

    $config=array();
    $config["StartTime"]=strtotime("-10days");
    $config["manAggregate"]="daily";
    
    //$config["DataType"]="Array";
    //$config["StartTime"]=strtotime("1.1.2021");
    //$config["manAggregate"]="monthly";            // tägliche Werte, false geloggte Werte auslesen

    /*$config["Aggregated"]=false;            // tägliche Werte, false geloggte Werte auslesen
    $config["manAggregate"]="daily";            // tägliche Werte, false geloggte Werte auslesen
    $oid=$amis->getWirkleistungID("Kueche");                // Arbeitszimmer  */

    $config["Aggregated"]=false;            // tägliche Werte, false geloggte Werte auslesen
    $oid=$amis->getWirkenergieID("Weinkuehler");                // Arbeitszimmer Kueche
    if ($oid !== false)
        {
        echo "Ergebnis ist $oid (".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).") \n";
        $ergebnis = $archiveOps->getValues($oid,$config,true);                //1,2 für Debug, 2 mit Werte Ausgabe
        foreach ($ergebnis as $index => $entries) echo "$index \n";                 // index : Values, MeansRoll, Description 
        //$archiveOps->showValues($ergebnis["Values"],[],false);                  // false no debug
        
        $config["Aggregated"]=3;            // monatliche Werte, false geloggte Werte auslesen
        $config["manAggregate"]=false;            // tägliche Werte, false geloggte Werte auslesen
        $config["OIdtoStore"]=$oid+1;
        $ergebnis = $archiveOps->getValues($oid,$config,true);                //1,2 für Debug, 2 mit Werte Ausgabe
        //$archiveOps->showValues($ergebnis["Values"],[],false);                  // false no debug
        $archiveOps->showValues(false,[],false);
        }

	}
	
/******************************************************************************************************************/

function anfragezahlernr($varname,$anfang,$ende,$content){
    $zaehler_nr_ist = Auswerten($content,$anfang,$ende);
    return $zaehler_nr_ist;
};

function anfrage($varname, $anfang, $ende, $content, $vartyp, $VariProfile, $arhid, $ParentID){
    $wert = Auswerten($content, $anfang, $ende);
    if ($wert) {vars($arhid, $ParentID, $varname, $wert, $vartyp, $VariProfile); return (true); }
    else { return (false); }
};

function Auswerten($content,$anfang,$ende){
 	$result_1 = explode($anfang,$content);
 	if (sizeof($result_1)>1)
   	{
		$result_2 = explode($ende,$result_1[1]);
 		$wert = str_replace(".", ",", $result_2[0]);
	 	/* echo "gefunden:".sizeof($result_1)." ".sizeof($result_2)." \n";
 		print_r($result_1);
	 	print_r($result_2);   */
 		return $wert;
 		}
 	else
 	   {
 	   return (false);
 	   }
};


function vars($arhid,$ParentID, $varname, $wert, $vartyp, $VariProfile)
  {
$VariID = IPS_GetVariableIDByName($varname, $ParentID);
    if ($VariID == false)
    {
        $VariID = IPS_CreateVariable ($vartyp);
        IPS_SetVariableCustomProfile($VariID, $VariProfile);
        IPS_SetName($VariID,$varname);
          AC_SetLoggingStatus($arhid, $VariID, true);
        IPS_SetParent($VariID,$ParentID);
    }
    SetValue($VariID, $wert);
  };


	   
?>