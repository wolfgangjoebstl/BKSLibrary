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

/* UpdateMySQL
 *
 * synchronisiert die MariaDB Datenbank mit der Konfiguration von IPSymcon
 * verkürzt die Evaluierung indem auch redundante Konfigurationen abgelegt sind
 *
 * folgende Tabellen sind in der MariaDB angelegt:
 *      topologies
 *      deviceList
 *      instances           Index (instanceID PRIMARY,Name UNIQUE,OID UNIQUE)
 *      channels
 *      actuators
 *      registers
 *      valuesOnRegs
 *      serverGateways
 *      componentModules
 *      auditTrail
 *      eventLog
 *
 *
 * folgende Tätigkeiten werden hier der reihe nach durchgeführt
 *
 *      sync database Configuration ($sqlOperate->syncTableConfig) 
 *      sync database Values
 *
 *
 */

$startexec=microtime(true);     // Zeitmessung, um lange Routinen zu erkennen

/******************************************************
 *
 *				INIT
 *
 *************************************************************/

    //Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
    IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');    
    IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

    IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');          // deviceList
    IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
   	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");	

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
        }
    $installedModules = $moduleManager->GetInstalledModules();

    if (isset($installedModules["DetectMovement"]))
        {
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
        $DetectDeviceListHandler = new DetectDeviceListHandler();               // neuer Handler für die DeviceList, registriert die Devices in EvaluateHarwdare_Configuration
        }

    echo "\n";
    echo "Kernel Version (Revision) ist : ".IPS_GetKernelVersion()." (".IPS_GetKernelRevision().")\n";
    echo "Kernel Datum ist : ".date("D d.m.Y H:i:s",IPS_GetKernelDate())."\n";
    echo "Kernel Startzeit ist : ".date("D d.m.Y H:i:s",IPS_GetKernelStartTime())."\n";
    echo "Kernel Dir seit IPS 5.3. getrennt abgelegt : ".IPS_GetKernelDir()."\n";
    echo "Kernel Install Dir ist auf : ".IPS_GetKernelDirEx()."\n";
    echo "\n";

    /* DeviceManger muss immer installiert werden, wird in Timer als auch RunScript und Execute verwendet */

    if (isset($installedModules["OperationCenter"])) 
        {
        IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter'); 
        echo "OperationCenter ist installiert.\n";
        //$DeviceManager = new DeviceManagement();            // class aus der OperationCenter_Library
        $DeviceManager = new DeviceManagement_Homematic();            // class aus der OperationCenter_Library
        //echo "  Aktuelle Fehlermeldung der der Homematic CCUs ausgeben:\n";      
        echo $DeviceManager->HomematicFehlermeldungen()."\n";
        //echo "  Homematic Serialnummern erfassen:\n";
        $serials=$DeviceManager->addHomematicSerialList_Typ();      // kein Debug
        }

    $ipsOps = new ipsOps();
	$modulhandling = new ModuleHandling();	                	            // in AllgemeineDefinitionen, alles rund um Bibliotheken, Module und Librariestrue bedeutet mit Debug
    $topologyLibrary = new TopologyLibraryManagement();                     // in EvaluateHardware Library, neue Form des Topology Managements
    $evaluateHardware = new EvaluateHardware();

    $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php

    $oidResult = $modulhandling->getInstances('MySQL');
    if (sizeof($oidResult)>0) 
        {
        $oid=$oidResult[0];           // ersten treffer new_checkbox_tree_get_multi_selection
        echo "sqlHandle: new $oid (".IPS_GetName($oid).") for MySQL Database found.\n";
        }
    else 
        {
        echo "sqlHandle: OID einer Instance MySQL not found.\n";
        }

    $sqlHandle = new sqlHandle();           // default MySQL Instanz
    if ($sqlHandle->available !==false)
        {
        $sqlHandle->useDatabase("ipsymcon");    // USE DATABASE ipsymcon
        $tables = $sqlHandle->showTables();     // SHOW TABLES
        $config = $sqlHandle->getDatabaseConfiguration();

        /* sync database Configuration */
        echo "---------------------------------------------------------------------------------\n";

        $sqlOperate = new sqlOperate();           // default MySQL Instanz extends sqlHandle, USE DATABASE in MariaDB bereits gesetzt
        $sqlOperate->syncTableConfig(false, true);         // false for all Tables, true for debug

        //echo "---------------------------------------------------------------------------------\n";

        $sql_serverGateways = new sql_serverGateways();
        $sql_topologies = new sql_topologies();         // eigene Klasse pro Tabelle, extends sqlOperate that extends sqlHandle
        $sql_deviceList = new sql_deviceList();         // eigene Klasse pro Tabelle, extends sqlOperate that extends sqlHandle
        $sql_instances = new sql_instances();
        $sql_channels = new sql_channels();
        $sql_registers = new sql_registers();
        $sql_componentModules = new sql_componentModules();
        $sql_auditTrail = new sql_auditTrail();

        /* sync database Values */
        echo "\n";
        echo "---------------------------------------------------------------------------------\n";
        echo "Sync Values of table serverGateways with MariaDB Database:\n";           // config with tables

        $serverGateways=array();
        $serverGateways[IPS_GetName(0)]["parent"]=IPS_GetName(0);                 // Definition der Root, das ist der Node, also Docker oder Virtual Machine      
        $serverGateways[IPS_GetName(0)]["Type"]="server";                           // Root ist immer ein Server      

        /* update Values in Tables */
        $sql_serverGateways->syncTableValues($serverGateways);

        echo "\n";
        echo "Sync Values of table topologies with MariaDB Database:\n";           // config with tables
        $sql_topologies->syncTableValues(get_Topology());                                        // Topology Table, der mit den Räumen udn Gruppen

        echo "\n";
        echo "Tabelle deviceList mit touch zusammenräumen:\n";    

        $touch=$sql_deviceList->touchTableOnDevice($deviceList,true);              // true mit Debug
        echo "---------------------------------------------------------------------------------\n";
        echo "  die Werte die in devicelsit sind bekommen ein touch, Werte ohne aktuellem touch ausgeben.\n";    
        $sql = "SELECT * FROM deviceList WHERE touch != $touch;";
        $result1=$sqlHandle->query($sql);
        $tableHTML = $result1->fetchSelect("html","darkblue");
        $result1->result->close();                      // erst am Ende den vielen Speicher freigeben, sonst ist mysqli_result bereits weg !
        echo $tableHTML;

        echo "  und danach auch gleich alle diese Werte löschen.\n";    
        $sqlCommand = "DELETE FROM deviceList WHERE touch != $touch;";
        echo " >SQL Command : $sqlCommand\n";    
        $result2=$sqlHandle->command($sqlCommand);                                     

        /* die Tabelle deviceList um die ServerGatewayID erweitern */
        echo "\n";
        echo "Tabelle deviceList um die ServerGatewayID für ".IPS_GetName(0)." erweitern:\n";    
        $sql = "SELECT * FROM serverGateways WHERE Name = '".IPS_GetName(0)."';";
        $result1=$sqlHandle->query($sql);
        $serverID = $result1->fetch();
        $result1->result->close();                      // erst am Ende den vielen Speicher freigeben, sonst ist mysqli_result bereits weg !
        if (sizeof($serverID)==1) 
            {
            $serverGatewayID=$serverID[0]["serverGatewayID"];
            echo "    serverGatewayID gefunden: ".IPS_GetName(0)."=='$serverGatewayID'.\n";
            //$sql_deviceList->syncTableServerGatewaysID($serverGatewayID);                 // deviceList Table mit eigener Server ID
            foreach ($deviceList as $name => $device)
                {
                $deviceList[$name]["serverGatewayID"] = $serverGatewayID;  
                }
            }
        echo "syncTableValues für das deviceList Array mit der MariaDB Database:\n";
        $sql_deviceList->syncTableValues($deviceList,false);                                      // deviceList Table, true ist Debug

        echo "\n";
        echo "syncTableProductType Spalte ProductType erweitern\n";
        $sql_deviceList->syncTableProductType(homematicList());                             // Homematic Table

        echo "syncTablePlaceID Spalte placeID erweitern\n";
        $sql_deviceList->syncTablePlaceID(IPSDetectDeviceHandler_GetEventConfiguration());  // Event Table mit Topologie

        echo "\n";
        $componentModules=$sql_componentModules->get_componentModules(IPSDeviceHandler_GetComponentModules());
        echo "Die aktuelle Component Liste aus der Konfiguration IPSDeviceHandler_GetComponentModules in einer eigenen Tabelle speichern und indezieren.\n";
        //print_r(IPSDeviceHandler_GetComponentModules());
        //print_r($componentModules);
        $sql_componentModules->syncTableValues($componentModules,false); 

        /* den inner join mit den componentModules weglassen um die NUL für componentModuleID zu finden */
        echo "componentModuleID in registers tabelle ergänzen. Alle Registers mit korrekten Zuordnungen auslesen ohne Berücksichtigung componentID:\n";
        $sql = "SELECT registers.registerID,topologies.Name AS Ort,deviceList.Name,instances.portID,instances.OID,deviceList.Type,deviceList.SubType,instances.Name AS Portname,
                            registers.componentModuleID,registers.TYPEREG,registers.Configuration 
                    FROM (deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID)
                    INNER JOIN registers ON deviceList.deviceID=registers.deviceID AND instances.portID=registers.portID
                    INNER JOIN topologies ON deviceList.placeID=topologies.topologyID;";
        $result3=$sqlHandle->query($sql);
        $fetchRegisters = $result3->fetch();
        $result3->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        echo "\n\n";
        echo "Registerabfrage ohne Erweiterung componentModules hat ".sizeof($fetchRegisters)." Einträge/Zeilen:\n";
        $columnComponentModule=$sql_componentModules->get_ColumnComponentModule(IPSDeviceHandler_GetComponentModules(),$fetchRegisters);

        $sql_registers = new sql_registers();
        echo "Echo Values from MariaDB Database componentModules wieder auslesen wegen der Indexes:\n";    
        $sql = "SELECT * FROM componentModules";
        $result2=$sqlHandle->query($sql);
        $fetch = $result2->fetch();
        $result2->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !

        //print_r($fetch);
        $componentID=array();
        foreach ($fetch as $singleRow)
            {
            $componentID[$singleRow["componentName"]]=$singleRow["componentModuleID"];    
            }
        //print_r($componentID);
        $columnData=array();
        foreach ($columnComponentModule as $registerID => $entry)
            {
            if (isset($componentID[$entry["Component"]])) $columnData[$registerID]=$componentID[$entry["Component"]];   
            }
        echo "syncTableColumnOnRegisterID aufrufen um componentModuleID Spalte basierend auf der registerID upzudaten:\n";   
        //print_r($columnData);           // Key ist die registerID und data wird die Spalte componentModuleID
        $sql_registers->syncTableColumnOnRegisterID("componentModuleID",$columnData,false);          // true = Debug

        echo "Tabelle valuesOnRegs schreiben. Alle Registers mit korrekten Zuordnungen für diesen Server auslesen.\n";
        $myServerGatewayID=$sql_serverGateways->getWhere(IPS_GetName(0)); 
        $filter="WHERE serverGatewayID='$myServerGatewayID'";
        $sql = "SELECT registers.registerID,topologies.Name AS Ort,deviceList.Name,instances.portID,instances.OID,deviceList.Type,deviceList.SubType,instances.Name AS Portname,
                            registers.componentModuleID,registers.TYPEREG,registers.Configuration 
                    FROM (deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID)
                    INNER JOIN registers ON deviceList.deviceID=registers.deviceID AND instances.portID=registers.portID
                    INNER JOIN topologies ON deviceList.placeID=topologies.topologyID
                    $filter;";
        $result3=$sqlHandle->query($sql);
        $fetchRegisters = $result3->fetch();
        $result3->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !

        $sql_valuesOnRegs = new sql_valuesOnRegs();
        /* Zu einer Tabelle der Werte kommen, in eine Register Zeile können mehrere Werte stehen, alle auslesen */
        $singleRows=array();
        echo "\nErmittlung der Werte für Tabelle valuesOnRegs:\n";
        foreach ($fetchRegisters as $singleRow)
            {
            $result=getCOIDforRegisterID($singleRow,false,true);         // singleRow wird von der function erweitert, register=false, debug=true
            //print_r($result);
            if ($result !== false) $singleRows=$singleRows + $result;
            }
        //print_r($singleRows);
        echo "\nvaluesOnRegs::syncTableValues aufrufen.\n";
        $sql_valuesOnRegs->syncTableValues($singleRows);

        }

    echo "--------------------------> Exectime Database Handling: ".exectime($startexec)." Seconds.\n";


?>