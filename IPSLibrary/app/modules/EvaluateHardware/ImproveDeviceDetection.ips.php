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

    /* Teil des Evaluate Hardware Moduls
     *
     * Webfront: Bearbeitung der Buttons 
     *              für das Webfront Werte in LocalData
     *              für das Webfront Werkzeugschlüssel
     *
     * Execute:  Analyse der Konfigurationsdateien und Abgleich mit der gespeicherten Parametrierung
     *
     *
     *
     *
     * verbessert und überprüft die aktuelle Systemkonfiguration
     * mit dem Durchlauf des Scripts sollten Anomalien in der Parametrierung des Systems sichtbar gemacht werden.
     * in der DetectDeviceHandler Configuration werden zusätzliche neue Register angelegt.
     *
     */

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");    

    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
    IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

    IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
    IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');           // sonst werden die Event Listen überschrieben
    IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

    $startexec=microtime(true);
    $executeExecute=true;                  // false den Execute Teil nicht ausführen, wie wenn Webfront aufgerufen wird

    /***************************************************************************
     * 
     * INIT
     *
     * Zwei Webfront Tabellen. Eine in SystemTP, die andere in LocalData
     *
     * EvaluateHardware
     *      DetectDevice
     *          SortTableBy
     *          MessageTable
     * CustomComponent
     *      HardwareStatus
     *          ShowTablesBy
     *          ValuesTable
     *
     ****************************/

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    $moduleManager    = new ModuleManagerIPS7('EvaluateHardware',$repository);  
    $installedModules = $moduleManager->GetInstalledModules();

	$CategoryIdData                 = $moduleManager->GetModuleCategoryID('data');

    $statusDeviceID                 = getVariableIDByName($CategoryIdData,"StatusDevice");
    $statusEvaluateHardwareID       = getVariableIDByName($CategoryIdData,"StatusEvaluateHardware");
    $logEvaluateHardwareID          = getVariableIDByName($CategoryIdData,"LogEvaluateHardware");
    $actionUpdateID                 = getVariableIDByName($CategoryIdData,"Update");

    $categoryId_DetectDevice        = getCategoryIdByName($CategoryIdData,'DetectDevice');
    $actionSortMessageTableID       = getVariableIDByName($categoryId_DetectDevice,"SortTableBy");
    $messageTableID                 = getVariableIDByName($categoryId_DetectDevice,"MessageTable");

    // Cookies tabelle in Tab BrowserCookies
    $categoryId_BrowserCookies      = getCategoryIdByName($CategoryIdData, "WebfrontCookies");                  // Achtung false ist wie 0
    $actionViewWebbrowserTableID    = getVariableIDByName($categoryId_BrowserCookies,"ViewBrowserOn");
    $webbrowserCookiesTableID       = getVariableIDByName($categoryId_BrowserCookies, "BrowserCookieTable");

    $categoryId_DetectTopologies    = getCategoryIdByName($CategoryIdData, "DetectTopologies");                  // Achtung false ist wie 0
    $actionViewMessageTableID       = getVariableIDByName($categoryId_DetectTopologies,"ViewTableOn");

    $moduleManagerCC = new ModuleManagerIPS7('CustomComponent',$repository);
	$CategoryIdDataCC     = $moduleManagerCC->GetModuleCategoryID('data');
    $hardwareStatusCat      = IPS_GetObjectIDByName("HardwareStatus",$CategoryIdDataCC);
    $valuesDeviceTableID    = IPS_GetObjectIDByName("ValuesTable", $hardwareStatusCat);	
    $actionDeviceTableID    = IPS_GetObjectIDByName("ShowTablesBy", $hardwareStatusCat);
    
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdImproveDeviceDetection   = IPS_GetScriptIDByName('ImproveDeviceDetection', $CategoryIdApp);

    $ipsOps = new ipsOps();
    $ipsTables = new ipsTables();               // fertige Routinen für eine Tabelle in der HMLBox verwenden    

    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];


    /***************************************************************************
     * 
     * alle Events aus dem IPSMessageHandler auslesen und danach etwas besser strukturieren und abgleichen mit
     *  CustomComponent     IPSMessageHandler
     *  DetectMovement      TestMovement
     *  OperationCenter     DeviceManagement_Homematic
     *
     *
     ****************************/

	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');	
	
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");

    $dosOps = new dosOps();


    /* gleiche Funktion wie Evaluate_Overview */

	$messageHandler = new IPSMessageHandlerExtended();      /* auch delete von Events moeglich */

  	$eventConf = IPSMessageHandler_GetEventConfiguration();
 	$eventCust = IPSMessageHandler_GetEventConfigurationCust();
	$eventlist = $eventConf + $eventCust;

    if (isset($installedModules["DetectMovement"])) 
        {
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        
        IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
        IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

        IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
        IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
        IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");

    	$debug=false;
		$testMovement = new TestMovement($debug);
        }

    if (isset($installedModules["OperationCenter"])) 
        {    
        //echo "OperationCenter ist installiert.\n";
        IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
        IPSUtils_Include ("DeviceManagement_Library.class.php","IPSLibrary::app::modules::OperationCenter");
        $DeviceManager = new DeviceManagement_Homematic();            // class aus der OperationCenter_Library
        }

    if (isset($installedModules["Startpage"])) 
        {
        IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');
        IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
        }

    /* Sortierfunktionen für zwei tabellen
     *
     * getComponentEventListTable
     * showHardwareStatus
     */

    if ($_IPS['SENDER']=="WebFront")
	    {
        switch ($_IPS['VARIABLE'])
            {
            case $actionSortMessageTableID:
                if ($_IPS['VALUE'] == GetValue($_IPS['VARIABLE'])) 
                    {
                    $sort=SORT_DESC;
                    echo "same";
                    }
                else $sort=SORT_ASC;
            	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
                if ($messageTableID)
                    {
                    $filter="IPSMessageHandler_Event";
                    $resultEventList = $testMovement->getEventListfromIPS($filter,false);                           // false no Debug
                    foreach ($resultEventList as $index => $entry)
                        {
                        $trigger = $entry["TriggerVariableID"];
                        if (isset($eventlist[$trigger])) 
                            {
                            $resultEventList[$index]["Component"]=$eventlist[$trigger][1];
                            $resultEventList[$index]["Module"]=$eventlist[$trigger][2];
                            }
                        }
                    switch ($_IPS['VALUE'])
                        {
                        case 0:
                            $ipsOps->intelliSort($resultEventList,"Module",$sort);
                            break;
                        case 1:
                            $ipsOps->intelliSort($resultEventList,"LastRun",$sort);
                            break;
                        default:
                            break;
                        }
                    $html=$testMovement->getComponentEventListTable($resultEventList,$filter,true,false);             // false no Debug, wenn file IPSMessage_Handler ist gibt es detailliertere Informationen
                    setValue($messageTableID,$html);
                    }
                else echo "Table not found.\n";
                break;
            case $actionDeviceTableID:
            	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
                if (isset($DeviceManager))
                    {
                    $hwStatus = $DeviceManager->HardwareStatus("array");           // Ausgabe als Array
                    //print_r($hwStatus);
                    $output="";
                    $output.='<style>';
                    $output.=' .first1  { margin: 30px; float:left; background-color: green; display: flex; flex-wrap: wrap;}';
                    $output.=' .second2 { margin: 30px;  float:left; background-color: blue; display: flex; flex-wrap: wrap;}';
                    $output.=' .third3  { margin: 30px;  float:left; background-color: orange; display: flex; flex-wrap: wrap;}';
                    $output.='</style>';
                    $output.='<div style="overflow:auto">';
                    $output.='  <div class="first1">';
                    $output .= $DeviceManager->showHardwareStatus($hwStatus,["Type"=>"Temperature"],["Header"=>"Temperatur"]);           // Ausgabe als html
                    $output.="  </div>";
                    $output.='  <div class="second2">';
                    $output .= $DeviceManager->showHardwareStatus($hwStatus,["Type"=>"Humidity"],["Header"=>"Feuchtigkeit"]);           // Ausgabe als html
                    $output.="  </div>";
                    $output.='  <div class="third3">';
                    $output .= $DeviceManager->showHardwareStatus($hwStatus,["Type"=>"Motion"],["Header"=>"Bewegung"]);           // Ausgabe als html
                    $output.="  </div>";
                    $output.='  <div class="first1">';
                    $output .= $DeviceManager->showHardwareStatus($hwStatus,["Type"=>"Contact"],["Header"=>"Kontakte"]);           // Ausgabe als html
                    $output.="  </div>";
                    $output.='  <div class="second2">';
                    $output .= $DeviceManager->showHardwareStatus($hwStatus,["Type"=>"Brightness"],["Header"=>"Helligkeit"]);           // Ausgabe als html
                    $output.="  </div>";
                    $output.='  <div class="third3">';
                    $output .= $DeviceManager->showHardwareStatus($hwStatus,["Type"=>"Energy"],["Header"=>"Energie"]);           // Ausgabe als html
                    $output.="  </div>";
                    $output.="  </div>";
                    $output.='  <div class="first1">';
                    $output .= $DeviceManager->showHardwareStatus($hwStatus,["Type"=>"Solltemperatur"],["Header"=>"Solltemperatur"]);           // Ausgabe als html
                    $output.="  </div>";
                    $output.='  <div class="second2">';
                    $output .= $DeviceManager->showHardwareStatus($hwStatus,["Type"=>"Ventilwert"],["Header"=>"Ventilwert"]);           // Ausgabe als html
                    $output.="  </div>";
                    $output.="</div>";
                    SetValue($valuesDeviceTableID,$output);
                    }

                break;
            case $actionViewWebbrowserTableID:                      // Cookies Table
                SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
                $webBrowserLibrary = new webBrowserLibrary();               // Teil der Startpage_Library
                $tableAccess = $webBrowserLibrary->getTable_webfrontAccess();
                $config=array();
                $config["html"]    = true;
                $config["insert"]["Header"]    = true;
                switch ($_IPS['VALUE'])
                    {
                    case 0:
                        $config["sort"] = "nameOfID";
                        break;
                    case 1:
                        $config["sort"] = "Updated";
                        break;
                    default:
                        break;
                    }
                echo "View ".$_IPS['VALUE']." ".count($tableAccess)." ".$config["sort"];
                $html = $ipsTables->showTable($tableAccess, false ,$config,false);                // true Debug
                SetValue($webbrowserCookiesTableID,$html);                        
                break;
            case $actionViewMessageTableID:
                SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
                switch ($_IPS['VALUE'])
                    {
                    case 0:
                        break;

                    default:
                        break;
                    }                    
                break;
            case $actionUpdateID:
                echo "Update Tables";
                $debug2=false;
                // $logEvaluateHardwareID, $statusDeviceID, $statusEvaluateHardwareID
                $verzeichnis=IPS_GetKernelDir()."scripts\\IPSLibrary\\config\\modules\\EvaluateHardware\\";
                $verzeichnis = $dosOps->correctDirName($verzeichnis,false);          //true für Debug
                $filename=$verzeichnis.'EvaluateHardware_DeviceErrorLog.inc.php';  
                $arrHM_Errors = $DeviceManager->HomematicFehlermeldungen(true); 
                $storedError_Log=$DeviceManager->updateHomematicErrorLog($filename,$arrHM_Errors,$debug2);        //true für Debug
                //print_R($storedError_Log);
                krsort($storedError_Log);
                $shortError_Log = array_slice($storedError_Log, 0, 40, true);

                $html = $DeviceManager->showHomematicFehlermeldungenLog($shortError_Log,$debug2);             //true für Debug
                SetValue($logEvaluateHardwareID,$html);

                $arrHM_ErrorsDetailed = $DeviceManager->HomematicFehlermeldungen("Array"); 
                $html=$DeviceManager->showHomematicFehlermeldungen($arrHM_ErrorsDetailed);
                SetValue($statusEvaluateHardwareID,$html);

                $hwStatus = $DeviceManager->HardwareStatus("array",$debug2);           // Ausgabe als Array, true für Debug
                $output = $DeviceManager->showHardwareStatus($hwStatus,["Reach"=>false,]);           // Ausgabe als html
                SetValue($statusDeviceID,$output);                
                break;
            default:
                echo "Do not know ".$_IPS['VARIABLE']."\n";
                break;
            }
        }

    /* nur bei manuellem Aufruf des Scriptes ausführen
     * verwendet testMovement
     *
     */
    if ( ($_IPS['SENDER']=="Execute") && $executeExecute)
        {

        if (isset($installedModules["OperationCenter"])) 
            {
            IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter'); 
            echo "OperationCenter ist installiert.\n"; 
            //echo "HMI_CreateReport updaten, wenn in den letzten 24h nicht erfolgt.\n";
            $DeviceManager = new DeviceManagement();            // class aus der OperationCenter_Library, getHomematicAddressList wird auch im construct aufgerufen
            //$HomematicAddressesList = $DeviceManager->getHomematicAddressList(true);        // noch einmal aufrufen mit Debug
            //print_r($HomematicAddressesList);
            //echo "    --done---\n";
            }  

        if (isset($testMovement)) 
            {
            echo "DetectMovement Module installiert. Class TestMovement für Auswertungen verwenden:\n";
            //echo "--------\n";
            $eventListforDeletion = $testMovement->getEventListforDeletion();
            if (count($eventListforDeletion)>0) 
                {
                echo "Ergebnis TestMovement construct: Es müssen ".count($eventListforDeletion)." Events in der Config Datei \"IPSMessageHandler_GetEventConfiguration\" gelöscht werden, da keine Konfiguration mehr dazu angelegt ist.\n";
                echo "                                 und es müssen auch diese Events hinterlegt beim IPSMessageHandler_Event geloescht werden \"Bei Änderung Event Ungültig\".\n";
                print_R($eventListforDeletion);
                }
            else 
                {
                echo "Events von IPS_MessageHandler mit Konfiguration abgeglichen. TestMovement sagt alles ist in Ordnung.\n";
                echo "\n";
                }
            $filter="IPSMessageHandler_Event";
            $resultEventList = $testMovement->getEventListfromIPS($filter,true);
            //$ipsOps->intelliSort($resultEventList,"OID");                           // Event ID
            $ipsOps->intelliSort($resultEventList,"Name");                           // Device Event ID
            $html=$testMovement->getComponentEventListTable($resultEventList,$filter,true,true);
            echo $html;

            }

        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug

        $TopologyLibrary=$modulhandling->printModules('TopologyMappingLibrary');
        if (empty($TopologyLibrary)) echo "TopologyMappingLibrary noch nicht installiert.  \n";
        echo "\n";   


        /**********************************************************************************
        *
        * Topology Mapping, check Libraries
        *
        *
        **********************************/

        /*
        $modulhandling->printLibraries();
        echo "\n";
        $modulhandling->printInstances('TopologyDevice');
        $deviceInstances = $modulhandling->getInstances('TopologyDevice');
        $modulhandling->printInstances('TopologyRoom');        
        $roomInstances = $modulhandling->getInstances('TopologyRoom');
        */

        echo "Overview of registered Events ".sizeof($eventConf)." + ".sizeof($eventCust)." = ".sizeof($eventlist)." Eintraege : \n";
        //print_R($eventlist);

        if ( (isset($installedModules["DetectMovement"])) && (isset($installedModules["EvaluateHardware"])) && !(empty($TopologyLibrary)) )
            {
            IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");          // neues EvaluateHardware Include File
            IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
            IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');

            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

            echo "\n\n";
            echo "Module DetectMovement und EvaluateHardware sind installiert. TopologyMappingLibrary Instanzen sind vorhanden.\n";

            $hardwareTypeDetect = new Hardware();
            $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php

            $DetectDeviceHandler = new DetectDeviceHandler();
            $DetectDeviceListHandler = new DetectDeviceListHandler();               // neuer Handler für die DeviceList, registriert die Devices in EvaluateHarwdare_Configuration

            echo "   Die devicelist von EvaluateHardware_DeviceList.inc.php einlesen.\n";

            /* alle Instanzen aus der Topolgie auslesen */
            $deviceInstances = $modulhandling->getInstances('TopologyDevice',"NAME");
            $roomInstances = $modulhandling->getInstances('TopologyRoom',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            $placeInstances = $modulhandling->getInstances('TopologyPlace',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            $devicegroupInstances = $modulhandling->getInstances('TopologyDeviceGroup',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key

            echo "   Die Topologie Instanzen aus dem IP Symcon Inventory einlesen.\n";
            /* die Konfiguration herauslesen und eventuell hinsichtlich Topologie ergänzen */
            $topology       = $DetectDeviceHandler->Get_Topology();
            echo "   Die Channel EventList aus dem DetectDeviceHandler einlesen.\n";
            $channelEventList    = $DetectDeviceHandler->Get_EventConfigurationAuto();        
            echo "   Die Device EventList aus dem DetectDeviceListHandler für die Geräte einlesen.\n";
            $deviceEventList     = $DetectDeviceListHandler->Get_EventConfigurationAuto(); 

            /* aus der devicelist von Configuration::EvaluateHardware_Include die Devices registrieren, es fehlt die device OID aus der Topologie
            * Aktuell sind zwei Durchläufe erforderlich, einmal Instanz anlegen und das zweite Mal Event registrieren 
            * Es wird dann auch die Topologie erstellt, momentat noch in CheckTopology
            * Anordnung von devicelist nach Namen ohne Subname nach dem :
            *
            * die devicelist aus EvaluateHardware_Devicelist Eintrag für Eintrag durchgehen
            * es muss Instances angelegt sein, es kann eine oder mehrere in einem Gerät sein (Taster, Schalter, Energiemessung)
            * mit der aktuellen Topologie abgleichen:         
            *      $deviceInstances = $modulhandling->getInstances('TopologyDevice',"NAME");
            *      $roomInstances = $modulhandling->getInstances('TopologyRoom',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            *      $placeInstances = $modulhandling->getInstances('TopologyPlace',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            *      $devicegroupInstances = $modulhandling->getInstances('TopologyDeviceGroup',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            *
            *
            */

            $topID=@IPS_GetObjectIDByName("Topology", 0 );
            if ($topID === false) 	$topID = CreateCategory("Topology",0,20);       // Kategorie anlegen wenn noch nicht da

            //if (false)              /* für das Anlegen der TopologyDevices */
                {
                $i=0;
                $onlyOne=true;      // schön vorsichtig, nur eine Instanz nach der anderen anschauen
                $parent=$topID;

                echo "\nDie EvaluateHardware_Devicelist devicelist() jetzt Gerät für Gerät durchgehen und wenn noch nicht vorhanden ein Topology Device anlegen:\n\n";
                foreach ($deviceList as $name => $entry)
                    {
                    $instances=$entry["Instances"];
                    if ($onlyOne)
                        {
                        if ( (isset($deviceInstances[$name])) === false )
                            {
                            echo str_pad($i,4)."Eine Topology Device Instanz in der Kategorie Topology mit dem Namen $name unter ".IPS_GetName($parent)." ($parent) erstellen:\n";
                            /* $InsID = IPS_CreateInstance("{5F6703F2-C638-B4FA-8986-C664F7F6319D}");          //Topology Device Instanz erstellen 
                            if ($InsID !== false)
                                {
                                IPS_SetName($InsID, $name); // Instanz benennen
                                IPS_SetParent($InsID, $parent); // Instanz einsortieren unter dem angeführten Objekt 
                                
                                //Konfiguration
                                //IPS_SetProperty($InsID, "HomeCode", "12345678"); // Ändere Eigenschaft "HomeCode"
                                IPS_ApplyChanges($InsID);           // Übernehme Änderungen -> Die Instanz benutzt den geänderten HomeCode
                                }
                            else echo "Fehler beim Instanz erstellen. Wahrscheinlich ein echo Befehl im Modul versteckt. \n"; */
                            }
                        else
                            {
                            /* wenn schon angelegt die DeviceList() auf die richtige Topology setzen */
                            $InstanzID = $deviceInstances[$name];    
                            if ($debug) echo str_pad($i,4)."Eine Topology Device Instanz mit dem Namen $name unter ".IPS_GetName(IPS_GetParent($InstanzID))." (".IPS_GetParent($InstanzID).") gibt es bereits und lautet: ". $InstanzID."   \n";
                            $room="";
                            $writeChannels=false;
                            $writeDevices=false;
                            /* die Instanzen aus der devicelist() durchgehen und schauen ob die Channels in der DetectDeviceHandler class angelegt sind und sich im selben Raum befinden */
                            foreach ($instances as $instance)
                                {
                                $config="";
                                //print_r($channelEventList[$instance["OID"]]);
                                if (isset($channelEventList[$instance["OID"]])) 
                                    {
                                    $config=json_encode($channelEventList[$instance["OID"]]);
                                    if ($room == "") $room=$channelEventList[$instance["OID"]][1];  // Parameter 1 ist der Raum
                                    elseif ($room != $channelEventList[$instance["OID"]][1]) 
                                        {
                                        echo "        !!!Fehler, die Channels sind in der DetectDeviceHandler->Get_EventConfigurationAuto() in unterschiedlichen Räumen.\n";
                                        $writeChannels=true;
                                        }
                                    }
                                if ($debug) echo "     Channel ".$instance["OID"]." (".IPS_GetName($instance["OID"]).") mit Configuration :    $config  \n";
                                }
                            if ($debug) echo "     Raum \"$room\" in der Config der Channel Instances gefunden.\n";
                            if (isset($deviceEventList[$InstanzID]))
                                {
                                //print_r($deviceEventList[$InstanzID]);
                                if ($room != $deviceEventList[$InstanzID][1]) 
                                    {
                                    if (($deviceEventList[$InstanzID][1])!="")
                                        {
                                        echo "      !!!Fehler, die Channels und das Gerät $InstanzID (".IPS_GetName($InstanzID).") sind in unterschiedlichen Räumen: \"$room\" \"".$deviceEventList[$InstanzID][1]."\" Zweiten Begriff übernehmen.\n";
                                        $room = $deviceEventList[$InstanzID][1];
                                        }
                                    else echo "     !!!Fehler, Definition Raum für das Gerät $InstanzID (".IPS_GetName($InstanzID).") ist nicht vollstaendig.\n";
                                    $writeChannels=true;
                                    }
                                }
                            if (isset($roomInstances[$room]))
                                {
                                //echo "Vergleiche ".IPS_GetParent($InstanzID)." mit ".$roomInstances[$room]."\n";
                                if ( IPS_GetParent($InstanzID) != $roomInstances[$room])
                                    {
                                    if ($debug) echo "    -> Instanz Room vorhanden. Parent auf $room setzen.\n";
                                    //IPS_SetParent($InstanzID,$roomInstances[$room]);
                                    }
                                }
                            elseif (isset($devicegroupInstances[$room]))    
                                {
                                if ( IPS_GetParent($InstanzID) != $devicegroupInstances[$room])
                                    {
                                    if ($debug) echo "    -> Instanz DeviceGroup vorhanden. Parent $room setzen.\n";
                                    //IPS_SetParent($InstanzID,$devicegroupInstances[$room]);
                                    }
                                }

                            $configTopologyDevice=IPS_GetConfiguration($InstanzID);
                            //echo "  Hier ist die abgespeicherte Konfiguration:    $configTopologyDevice \n";
                            /*
                            $oldconfig=json_decode($configTopologyDevice,true);
                            print_r($oldconfig);
                            $oldconfig["UpdateInterval"]=10;
                            $newconfig=json_encode($oldconfig);
                            echo "Neue geplante Konfiguration wäre : $newconfig \n";
                            IPS_SetConfiguration($InstanzID,$newconfig);
                            */
                            if ($debug) echo "   TOPD_SetDeviceList($InstanzID,".json_encode($instances).")\n";
                            if (isset($installedModules["DetectMovement"]))  
                                {
                                //echo "     RegisterEvent DetectDeviceListHandler $InstanzID,'Topology',$room,''\n";
                                //$DetectDeviceListHandler->RegisterEvent($InstanzID,'Topology',$room,'');	                    /* für Topology registrieren, ich brauch eine OID damit die Liste erzeugt werden kann */
                                foreach ($instances as $instance)
                                    {
                                    if ($writeChannels)
                                        {
                                        if (isset($channelEventList[$instance["OID"]])) echo "  Channel ".$instance["OID"]." update Eventliste DetectDeviceHandler->RegisterEvent(".$instance["OID"].",'Topology',$room,'')\n";
                                        else echo "   Channel ".$instance["OID"]." nicht Bestandteil der Eventliste. DetectDeviceHandler->RegisterEvent(".$instance["OID"].",'Topology',$room,'')\n";
                                        $DetectDeviceHandler->RegisterEvent($instance["OID"],'Topology',$room,'');	                    /* für Topology registrieren, ich brauch eine OID damit die Liste erzeugt werden kann */
                                        }
                                    }       // ende foreach
                                }
                            }
                        //$onlyOne=false;
                        $i++;    
                        }
                    }      // end foreach
                }

            /*****************************************************************
            *
            * statistische Auswertungen
            *
            *  
            **********************************************************/

            echo "========================================================================\n";    
            echo "Statistik der Devicelist nach Typen, Aufruf der getDeviceStatistics in HardwareLibrary:\n";
            $statistic = $hardwareTypeDetect->getDeviceStatistics($deviceList,false);                // false keine Warnings ausgeben
            print_r($statistic);


            echo "========================================================================\n";    
            echo "Auflisten aller Devices mit dem Device Typ:\n";
            $i=0;
            foreach ($deviceList as $name => $entry)
                {
                if (isset($entry ["Information"])) echo str_pad($i,3).str_pad($name,40).str_pad($entry ["Type"],20).str_pad($entry ["Information"],25)."\n";
                else echo str_pad($i,3).str_pad($name,40).str_pad($entry ["Type"],20)."unknown -------------------\n";
                $i++;
                }

            echo "--------------------\n";   

            }
    echo "\n";
            

    /*
    $name="Arbeitslicht3"; $parent=17297;
    echo "Eine Device Instanz mit dem Namen $name unter $parent erstellen, wenn sie nicht bereits erstellt wurde:\n";
    $InstanzID = @IPS_GetInstanceIDByName($name, $parent);

    if ($InstanzID === false)
        {
        echo "Instanz nicht gefunden, neu anlegen.";
        $InsID = IPS_CreateInstance("{5F6703F2-C638-B4FA-8986-C664F7F6319D}");          //Topology Device Instanz erstellen mit dem Namen "Stehlampe"
        if ($InsID !== false)
            {
            IPS_SetName($InsID, $name); // Instanz benennen
            IPS_SetParent($InsID, $parent); // Instanz einsortieren unter dem angeführten Objekt 
            
            //Konfiguration
            //IPS_SetProperty($InsID, "HomeCode", "12345678"); // Ändere Eigenschaft "HomeCode"
            IPS_ApplyChanges($InsID);           // Übernehme Änderungen -> Die Instanz benutzt den geänderten HomeCode
            }
        else echo "Fehler beim Instanz erstellen. Wahrscheinlich ein echo Befehl im Modul versteckt. \n";
        }
    else
        {
        echo "Die Instanz-ID gibt es bereits und lautet: ". $InstanzID."   \n";
		$configTopologyDevice=IPS_GetConfiguration($InstanzID);
        echo "  Hier ist die abgespeicherte Konfiguration:\n";
        echo "  $configTopologyDevice  \n";
        }

    $config=array();
    if ($InstanzID !==false) TOPD_CreateReport($InstanzID,$config);

    */




    /* komplette Datei aus dem HM Inventory auslesen 
    * 
    *
    */

    $HMIs=$modulhandling->getInstances('HM Inventory Report Creator');		
    $countHMI = sizeof($HMIs);
    echo "Es gibt insgesamt ".$countHMI." SymCon Homematic Inventory Instanzen. Entspricht üblicherweise der Anzahl der CCUs.\n";
    if ($countHMI>0)
        {		
        foreach ($HMIs as $HMI)
            {
            echo "   HMI Inventory, Update Report: ".$HMI."\n";
            //HMI_CreateReport($HMI);
            $config = IPS_GetConfiguration($HMI);
            echo $config;
            $childrens=IPS_GetChildrenIDs($HMI);
            if (isset($childrens[0]))
                {
                $objects = IPS_GetVariable($childrens[0]);
                echo "Report last Update: ".date("d.m.y H:i:s",$objects["VariableUpdated"])."\n";
                //print_r($objects);
                $HomeMaticEntries=json_decode(GetValue($childrens[0]),true);  
                echo "\nTesteintrag #0 aus dem HomeMaticInventory:\n";          
                print_R($HomeMaticEntries[0]);         // IPS_name ist die Referenz auf den Instanznamen von IPS
                /* DetectDevicec wertet diese Information auch aus DeviceManagement->($this->HomematicAddressesList=$this->getHomematicAddressList();)*/
                }
            }
        }



	$modulhandling = new ModuleHandling();	

    echo "\nAlle installierten Discovery Instances mit zugehörigem Modul und Library:\n";
    $discovery = $modulhandling->getDiscovery();
    echo "\n";

    if ( (isset($installedModules["DetectMovement"]) )             )
        {    
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

        echo "Auflistung aller DetectDeviceHandler Instanzen:\n";
        $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
        $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();        
        $DetectDeviceHandler->Print_EventConfigurationAuto(false);
        echo "\n";

        echo "Bewegungsregister Auflistung der Events:\n";
        $DetectMovementHandler = new DetectMovementHandler();
        $DetectMovementHandler->Print_EventConfigurationAuto(true);         // true extended display
        echo "\n";

        $eventListonOID=array();
        //print_r($resultEventList);
        foreach ($resultEventList as $index => $entry ) 
            {
            $eventListonOID[$entry["TriggerVariableID"]]=$entry;
            }
        //print_r($eventListonOID); 

    	$events=$DetectMovementHandler->ListEvents();               /* Alle Events für DetectMovement */
        $eventMoveConfig=$DetectMovementHandler->Get_EventConfigurationAuto();    
        
        /*  foreach ($events as $oid => $typ)
            {
            if (isset($eventListonOID[$oid])) print_r($eventListonOID[$oid]);   
            }  */ 

        //$detectMoveConfig=$DetectMovementHandler->ListConfigurations();  print_r($detectMoveConfig);
        
        echo "Array mit Spiegelregistern anlegen.\n";
        $mirrorsMoveFound=array();
        foreach ($events as $oid => $typ)
            {
            $moid=$DetectMovementHandler->getMirrorRegister($oid);
            if (IPS_GetObject($oid) === false) 
                {
                echo "   --> Fehler, Register $oid nicht bekannt.\n";
                }
            else
                {
                if ($moid === false) echo "  --> Fehler, Spiegelregister nicht bekannt.\n";
                else
                    {
                    if (isset($eventListonOID[$oid])) 
                        {
                        $eventListonOID[$oid]["MirrorRegisterID"]=$moid;
                        $eventListonOID[$oid]["MirrorRegister"]=IPS_GetName($moid);
                        }    
                    $mirrorsMoveFound[$moid] = IPS_GetName($moid);                
                    echo "     ".IPS_GetName($moid);
                    }
                echo "\n";
                }
            }
        print_R($mirrorsMoveFound);

        echo "Temperaturregister Auflistung der Events:\n";
        $DetectTemperatureHandler = new DetectTemperatureHandler();
        $DetectTemperatureHandler->Print_EventConfigurationAuto(true);         // true extended display

    	$events=$DetectTemperatureHandler->ListEvents();
        $eventTempConfig=$DetectTemperatureHandler->Get_EventConfigurationAuto();    

        echo "Array mit Spiegelregistern anlegen.\n";
        $mirrorsTempFound=array();
        echo "      OID    Pfad                                                                              Config aus EvaluateHardware                                             TemperatureConfig aus DetectMovement            \n";
        foreach ($events as $oid => $typ)
            {
            $moid=$DetectTemperatureHandler->getMirrorRegister($oid);
            if (IPS_GetObject($oid) === false) echo "     Fehler, Register nicht bekannt.\n";
            else
                {
                if ($moid === false) echo "  --> Fehler, Spiegelregister nicht bekannt.\n";
                else
                    {
                    if (isset($eventListonOID[$oid])) 
                        {
                        $eventListonOID[$oid]["MirrorRegisterID"]=$moid;
                        $eventListonOID[$oid]["MirrorRegister"]=IPS_GetName($moid);
                        }    
                    $mirrorsTempFound[$moid] = IPS_GetName($moid);                
                    echo "     ".IPS_GetName($oid)."\n";
                    }
                }
            }

        echo "\n";
        }
    else 
        {
        $mirrorsMoveFound=array();             
        $mirrorsTempFound=array();   
        }

    if ( (isset($installedModules["CustomComponent"]) )                   ) 
        {    

        $moduleManagerCC = new IPSModuleManager('CustomComponent',$repository);

        IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
        IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
        IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

        $CategoryIdDataCC     = $moduleManagerCC->GetModuleCategoryID('data');
        $CategoryIdAppCC      = $moduleManagerCC->GetModuleCategoryID('app');

        echo "\n";
        echo "Modul CustomComponents Category OIDs for data : ".$CategoryIdDataCC."  (".IPS_GetName($CategoryIdDataCC).") for App : ".$CategoryIdAppCC."\n";
        echo "Ausgabe aller Bewegung Spiegelregister:\n";
        $name="Bewegung-Auswertung";
        $MoveAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdDataCC);
        checkMirrorRegisters($MoveAuswertungID,$mirrorsMoveFound);

        echo "---------\n";
        echo "Ausgabe aller Temperatur Spiegelregister:\n";
    	$events=$DetectTemperatureHandler->ListEvents();
        $mirrorsFound = $DetectTemperatureHandler->getMirrorRegisters($events);
        /* Get Category to store the Temperature-Spiegelregister */	
        $name="Temperatur-Auswertung";
        $TempAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdDataCC);
        $DetectTemperatureHandler->checkMirrorRegisters($TempAuswertungID,$mirrorsFound);
        }

    if ( (isset($installedModules["DetectMovement"]) )             )
        {    

        echo "\n";
        echo "=======================================================================\n";
        echo "Detect Movement Modul Summenregister suchen und evaluieren :\n";
        echo "---------\n";
        echo "Die Configurationen der Bewegungsregisterregister auf Konsistenz prüfen:\n";
        $events=$DetectMovementHandler->ListEvents();
        foreach ($events as $oid => $typ)
            {
            $moid=$DetectMovementHandler->getMirrorRegister($oid);
            if ( (isset($detectDeviceConfig[$oid]["Config"]["Mirror"])) && ($detectDeviceConfig[$oid]["Config"]["Mirror"] != "") ) 
                {
                if ($detectDeviceConfig[$oid]["Config"]["Mirror"] != IPS_GetName($moid)) 
                    {
                    $mirror1=$detectDeviceConfig[$oid]["Config"]["Mirror"];
                    echo "     ---> Mirror register in detectDeviceConfig cannot be overwritten. Clear manually to $mirror1!\n";
                    //print_r($detectDeviceConfig[$oid]);
                    }
                }
            //echo "\ndetectTemperatureConfig:\n"; print_r($detectTemperatureConfig[$oid]);
            if ( (isset($detectTemperatureConfig[$oid]["Config"]["Mirror"])) && ($detectTemperatureConfig[$oid]["Config"]["Mirror"] != "") ) 
                {
                if ($detectTemperatureConfig[$oid]["Config"]["Mirror"] != IPS_GetName($moid)) 
                    {
                    $mirror2=$detectTemperatureConfig[$oid]["Config"]["Mirror"];
                    echo "     ---> Mirror register in detectMovementConfig cannot be overwritten. Clear manually to $mirror2 !\n";
                    //print_R($detectTemperatureConfig[$oid]);
                    }
                }            
            }        
        //print_r($events);
        /*
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$DetectMovementHandler->getMirrorRegister($oid);
            $DetectDeviceHandler->RegisterEvent($moid,'Topology','','Movement');		
            }
        */
        $groups=$DetectMovementHandler->ListGroups("Motion");       // Type angeben damit mehrere Gruppen aufgelöst werden können
        //print_r($groups)
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectMovementHandler->InitGroup($group);
            echo "     ".$soid."  ".str_pad(IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid))),70)."  ".(integer)GetValue($soid)."\n";
            //$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Movement');		
            }	

        echo "---------\n";
        echo "Die Configurationen der Temperaturregister auf Konsistenz prüfen:\n";
        $events=$DetectTemperatureHandler->ListEvents();
        foreach ($events as $oid => $typ)
            {
            $moid=$DetectTemperatureHandler->getMirrorRegister($oid);
            if ( (isset($detectDeviceConfig[$oid]["Config"]["Mirror"])) && ($detectDeviceConfig[$oid]["Config"]["Mirror"] != "") ) 
                {
                if ($detectDeviceConfig[$oid]["Config"]["Mirror"] != IPS_GetName($moid)) 
                    {
                    $mirror1=$detectDeviceConfig[$oid]["Config"]["Mirror"];
                    echo "     ---> Mirror register in detectDeviceConfig cannot be overwritten. Clear manually to $mirror1!\n";
                    //print_r($detectDeviceConfig[$oid]);
                    }
                }
            //echo "\ndetectTemperatureConfig:\n"; print_r($detectTemperatureConfig[$oid]);
            if ( (isset($detectTemperatureConfig[$oid]["Config"]["Mirror"])) && ($detectTemperatureConfig[$oid]["Config"]["Mirror"] != "") ) 
                {
                if ($detectTemperatureConfig[$oid]["Config"]["Mirror"] != IPS_GetName($moid)) 
                    {
                    $mirror2=$detectTemperatureConfig[$oid]["Config"]["Mirror"];
                    echo "     ---> Mirror register in detectTemperatureConfig cannot be overwritten. Clear manually to $mirror2 !\n";
                    //print_R($detectTemperatureConfig[$oid]);
                    }
                }            
            }        
        echo "Alle Temperatur Gruppen durchgehen und wenn erforderlich neu registrieren :\n";
        $groups=$DetectTemperatureHandler->ListGroups("Temperatur");        /* Type angeben damit mehrere Gruppen aufgelöst werden können */
        //print_r($groups);
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectTemperatureHandler->InitGroup($group);
            echo "     ".$soid."  ".str_pad(IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid))),70)."  ".GetValue($soid)." °C\n";
            //$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Temperature');		
            }
        }	
        echo "Aktuelle Laufzeit ".(time()-$startexec)." Sekunden.\n";

    }       // nur wenn Script Execute
//else echo "kein Execute";             // Test ob das das Ende von Execute ist

/**********************************************************************************************/


        function checkMirrorRegisters($TempAuswertungID,$mirrorsFound)
            {
            $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $i=0;
            $childrens=IPS_getChildrenIDs($TempAuswertungID);
            $mirrors = array();
            foreach ($childrens as $oid)
                {    
                $mirrors[IPS_GetName($oid)]=$oid;
                }
            ksort($mirrors);
            //print_r($mirrors);
            foreach ($mirrors as $oid)
                {
                $werte = @AC_GetLoggedValues($archiveHandlerID,$oid, time()-60*24*60*60, time(),1000);
                if ($werte === false) echo "   ".str_pad($i,4).str_pad($oid,6).str_pad("(".IPS_GetName($oid).")",35)."  : no archive\n";
                else 
                    {
                    echo "   ".str_pad($i,4).str_pad($oid,6).str_pad("(".IPS_GetName($oid).")",35)."  : ".str_pad(count($werte),4)."  ";
                    if (count($werte)>0) 
                        {
                        //print_r($werte[0]);
                        echo " last change ".date("d.m.Y H:i:s",$werte[0]["TimeStamp"]);
                        }
                    else echo "                                ";
                    if (isset($mirrorsFound[$oid])) echo "   -> Mirror in Config";
                    echo "\n";
                    }
                $i++;
                }
            }











	
?>