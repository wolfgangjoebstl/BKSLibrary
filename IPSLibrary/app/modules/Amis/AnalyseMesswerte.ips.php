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
    echo "Name                          Type      Found Registers storing Data\n";
	$MeterConfig = $amis->getMeterConfig();
    $amis->analyseMeterConfig();

	$AmisConfig = $amis->getAmisConfig();



    $categoryId_SmartMeter      = IPS_GetObjectIDByName('SmartMeter', $CategoryIdData);
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






        
	   
?>