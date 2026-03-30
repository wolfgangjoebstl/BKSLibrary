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
     * Messwerte auslesen, analysieren udn bewerten, eventuell bereinigen
	 * Stadardfunktionen
     *
     * @file      
     * @author        Wolfgang Joebstl
     *
     */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
    IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');
    IPSUtils_Include ('Amis_Constants.inc.php', 'IPSLibrary::app::modules::Amis');

    IPSUtils_Include ('DeviceManagement_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');
    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
    IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');          // unbedingt erforderlich

	/******************************************************
     *
	 *			INIT
     *
	*************************************************************/
	
    ini_set('memory_limit', '128M');       //usually it is 32/16/8/4MB 
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $installedModules = $moduleManager->GetInstalledModules();
    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

    if ($_IPS['SENDER'] == "Execute") 
        {
        echo "Debug aktiviert, calling from Script. Debug Levels are 0,1 or 2\n";
        $debug=true;
        }
    else $debug=false;

	$amis=new Amis();           // Ausgabe SystemDir, erstellt MeterConfig

    $debug1=false;
    echo "Script called for Analysis of Config and Data.\n";
    echo "Name                          Type           Found Registers storing Data\n";
	$MeterConfig = $amis->getMeterConfig();
    //print_R($MeterConfig);
    $amis->analyseMeterConfig();

	$AmisConfig = $amis->getAmisConfig();
    echo "\n";      
    echo "New Meter Topology:\n";
    $amisTopo = new AmisTopology();
    $meterTopology=array(); 
    if ($amisTopo->createMeterTopology($meterTopology,$MeterConfig,false)===false) 
        {
        echo "call once more without Debug\n";
        $amisTopo->createMeterTopology($meterTopology,$MeterConfig);     //zweimal aufrufen       
        }
    //print_R($meterTopology);
    echo "\n";
        
    $meterValues=array();         // werden weiter oben erzeugt und zusätzlich ausgegeben
    //echo "printMeterTopology:\n";
    //echo "amis->writeEnergyRegistertoArray(MeterConfig aufgerufen. Debug ".($debug?"ja":"nein")."\n";           // beim ? ist zuerst true und dann false
    $meterValues=$amis->writeEnergyRegistertoArray($MeterConfig, ($debug>1));             // ($debug>1)
    //print_R($meterValues);
    /*   Gaestezimmer         data 55215        , device 24074
        *   WohnzimmerSideboard  data 21249 , device 15876
        */
    echo "Topology, Leistungswerte gerechnet und gemessen:\n"; 
    echo "---------------------------------------------------------------------------------------------------------------\n";
    echo "Name                                              Type         Power/data         Power/Device\n";
    echo $amisTopo->printMeterTopology($meterTopology,$meterValues,"",false);         // true für debug , uses depricated function , no debug, otherwise doubled echo !
    echo "\n"; 


    /* Anzeige der berechneten Werte basierend auf dem Vorschub der Geräteregister
     * man kann Tageswerte und Monatswerte berechnen, die werden immer um 00:00 für den aktuellen Tag ausgegeben. als 27.03.2026 00:00:00 ist die Summe aller Werte von diesem Tag
     * WirkenergieId ist ein Counter, der Wert wird immer größer basierend auf den Vorschüben der einzelnen Register
     * Sobald aggregiert wird haben wir den Vorschub für eine Zeitspanne
     */
    echo "\n";
    echo "=====================================================\n";
    echo "Archivierte Werte bearbeiten:\n";
    echo "=====================================================\n";
    echo "\n";

    $archiveOps = new archiveOps(); 
    $archiveID = $archiveOps->getArchiveID();

    $config=array();
    $config["StartTime"]=strtotime("-10days");

if (false)
    {
    // Vergleich mehrere Tageswerte
    // Tageswerte mehrere Werte
    $names=["KBG47"=>"KBG47","KellerHeizraumSumme"=>"Summe","KellerHeizraum"=>"All","KellerHeizraum_L1"=>"L1","KellerHeizraum_L2"=>"L2","KellerHeizraum_L3"=>"L3"];
    $config["Aggregated"]="daily";            // daily tägliche Werte, false alle geloggten Werte auslesen
    foreach ($names as $name=>$short)
        {
        $wirkenergieId=$amis->getWirkenergieID($name);
        $wirkleistungId=$amis->getWirkleistungID($name);
        if ($wirkenergieId !== false)
            {
            echo "Detaillierte Werte für : $wirkenergieId (".IPS_GetName($wirkenergieId).".".IPS_GetName(IPS_GetParent($wirkenergieId)).") \n";
            $config["OIdtoStore"]=$short;
            $ergebnis = $archiveOps->getValues($wirkenergieId,$config,false);                //1,2 für Debug, 2 mit Werte Ausgabe
            }
        }
    echo "\n";
    $archiveOps->showValues(false,[],false);        
    }

if (false)
    {
    // detaillierte Darstellung eines Wertes
    $name="Wohnung-LBG70";
    $wirkenergieId=$amis->getWirkenergieID($name);
    $wirkleistungId=$amis->getWirkleistungID($name);
    echo "Name $name EnergieID $wirkenergieId LeistungID  $wirkleistungId Register Gerät : \n";

    if ($wirkenergieId !== false)
        {
        echo "Detaillierte Werte für : $wirkenergieId (".IPS_GetName($wirkenergieId).".".IPS_GetName(IPS_GetParent($wirkenergieId)).") \n";
        $config["Aggregated"]=false;            // tägliche Werte, false geloggte Werte auslesen
        $ergebnis = $archiveOps->getValues($wirkenergieId,$config,false);                //1,2 für Debug, 2 mit Werte Ausgabe
        //foreach ($ergebnis as $index => $entries) echo "$index \n";                 // index : Values, MeansRoll, Description 
        //$archiveOps->showValues($ergebnis["Values"],[],false);                  // false no debug


        $config["manAggregate"]=false;            // tägliche Werte, false geloggte Werte auslesen
        $config["Aggregated"]="daily";            // monatliche Werte, false geloggte Werte auslesen
        $config["OIdtoStore"]="Daily";
        $ergebnis = $archiveOps->getValues($wirkenergieId,$config,false);                //1,2 für Debug, 2 mit Werte Ausgabe

        $config["Aggregated"]="monthly";            // tägliche Werte, false geloggte Werte auslesen
        $config["OIdtoStore"]="Monthly";
        $ergebnis = $archiveOps->getValues($wirkenergieId,$config,false);                //1,2 für Debug, 2 mit Werte Ausgabe

        $config["Aggregated"]=false;            // tägliche Werte, false geloggte Werte auslesen
        $config["OIdtoStore"]="Power";
        $ergebnis = $archiveOps->getValues($wirkleistungId,$config,false);                //1,2 für Debug, 2 mit Werte Ausgabe

        //$archiveOps->showValues($ergebnis["Values"],[],false);                  // false no debug
        $archiveOps->showValues(false,[],false);
        }
    }

if (false)
    {
    $categoryId_SmartMeter      = IPS_GetCategoryIDByName('SmartMeter', $CategoryIdData);
    echo "Category SmartMeter : $categoryId_SmartMeter \n";    
    $statusSmartMeterID = IPS_GetObjectIDByName("SmartMeterStatus",$categoryId_SmartMeter);
    echo "SmartMeterStatus     $statusSmartMeterID   Status ";
    echo (GetValue($statusSmartMeterID)?"on":"off")."\n";

	/* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
	$MeterReadID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$configPort=array();

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
    echo $amis->writeEnergyRegistertoString($MeterConfig,true,$debug);            // output as html (true) and with debug (true), sehr lange Ausgabe
	echo "\n----------------------------------------------------\n";
    echo "\nUebersicht Homematic Registers:\n";
	foreach ($MeterConfig as $identifier => $meter)
		{	
        $amis->writeEnergyHomematic($meter,$debug);           // true für Debug, macht nette Ausgabe
        }
    echo "===============================================================================\n";        
    echo "\nUebersicht Shelly Registers:\n";
	foreach ($MeterConfig as $identifier => $meter)
		{	
        $amis->writeEnergyDevice($meter,"SHELLY",$debug);           // true für Debug, macht nette Ausgabe
        }
    }





        
	   
?>