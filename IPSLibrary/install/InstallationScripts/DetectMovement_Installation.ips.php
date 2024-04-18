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

	/****************************************************************************************************
	 *
	 * es gibt mehrere unterschiedliche Routinen die auf eine Aenderung einer Variablen abzielen
	 * Messagehandler arbeitet Input von CustomComponents, DetectMovement und RemoteAccess ab
	 *
	 * bei einer Aenderung wird das entsprechen programmierte Custom Event aufgerufen und dann wenn installiert auch RemoteAccess und DetectMovement abgearbeitet.
	 * Im Detail: 
     *      eine Eventaenderung ruft den Messagehandler auf, 
     *      der holt sich aus dem Configfile den entsprechenden Component
	 *      im Component wird zuerst logvalue und dann die RemoteAccess Zugriffe abgearbeitet
	 *      bei LogValue wird auch noch wenn installiert DetectMovement abgearbeitet
	 *
	 * Autosteuerung hat ihre eigenenen Messagehandler installiert und haengt nicht von den CustomComponents ab.
	 * Heizung ist an die CustomComponents angelehnt
	 *
	 *
	 * Bei der Installation sollten alle drei Module unabhängig davon ob die anderen Module installiert sind die selbe Funktionalitaet haben
	 *
	 *
	 *
	 *
	 *
	 *
	 * in IPSMessageHandler_Configuration enthaelt die OID die entweder als ONCHANGE oder ONUPDATE beobachtet wird und den CustomCompenet der aufgerufen wird
	 * nach dem CustomComponet stehen die RemotAccess Paare ServerName:RemoteOID;usw. 
	 *
	 * RemoteAccess Config definieren die Server und die Funktion des Servers
	 * DetectMovement Config definiert die Gruppierung und die zugeordneten Raeume oder Funktionen (Heizungsregelung)
	 *
	 **********************/

	/**@defgroup DetectMovement
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script um Ereignisse zusammenzufassen, ursprünglich für die Bewegungserfassung geschrieben
	 *
	 * funktioniert nun auch für Bewegung, Temperatur und feuchtigkeit
	 *
	 *
	 * @file          DetectMovement_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

    IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
    IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    // max. Scriptlaufzeit definierensonst stoppt vorher wegen langsamer Kamerainstallation
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(400); 
	$startexec=microtime(true);
    $installModules=false;
    $debug = true;    

	/****************************************************************************************************************/
	/*                                                                                                              */
	/*                                      Init                                                                    */
	/*                                                                                                              */
	/****************************************************************************************************************/


    // ab IPS7 gibt es das webfront Verzeichnis nicht mehr, wurde verschoben in das User Verzeichnis    
    IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new ModuleManagerIPS7('DetectMovement',$repository);

	echo "\nKernelversion          : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version            : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('DetectMovement');
	echo "\nDetectMovement Version : ".$ergebnis."\n";

 	$installedModules = $moduleManager->GetInstalledModules();
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");

	if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; }
	if (isset ($installedModules["EvaluateHardware"])) 
        { 
        echo "Modul EvaluateHardware ist installiert.\n"; 
        IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');      
        IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");                  // jetzt neu unter config
        IPSUtils_Include ("EvaluateHardware_Devicelist.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt
        IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');

        echo "========================================================================\n";    
        echo "EvaluateHardware: Statistik der Register nach Typen aus der devicelist erheben:\n";
        $hardwareTypeDetect = new Hardware();
        $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php
        $statistic = $hardwareTypeDetect->getRegisterStatistics($deviceList,false);                // false keine Warnings ausgeben
        $hardwareTypeDetect->writeRegisterStatistics($statistic);   

        /* für die Anzeige im Webfront wird EvaluateHardware verwendet */  
        $moduleManagerEH = new IPSModuleManager('EvaluateHardware',$repository);   
        if ($WFC10_Enabled = $moduleManagerEH->GetConfigValueDef('Enabled', 'WFC10',false))
            {
            $WFC10_Path           = $moduleManagerEH->GetConfigValue('Path', 'WFC10');
            echo "========================================================================\n";                
            echo "Webfront von EvaluateHardware in $WFC10_Path verwenden.\n";
            $categoryId_AdminWebFront=CreateCategoryPath($WFC10_Path);

            } 
        } 
    else 
        { 
        echo "Modul EvaluateHardware ist NICHT installiert. Routinen werden uebersprungen.\n"; 

        /* eine eigene Anzeige im Webfront machen */
        if ($WFC10_Enabled = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false))
            {
            $WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');        
            $categoryId_AdminWebFront=CreateCategoryPath($WFC10_Path);
            }
        }
	if (isset ($installedModules["RemoteReadWrite"])) { echo "Modul RemoteReadWrite ist installiert.\n"; } else { echo "Modul RemoteReadWrite ist NICHT installiert.\n"; }
	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "Modul RemoteAccess ist installiert.\n";
		IPSUtils_Include ('RemoteAccess_Configuration.inc.php', 'IPSLibrary::config::modules::RemoteAccess');
		}
	else
		{
		echo "Modul RemoteAccess ist NICHT installiert.\n";
		}
	if (isset ($installedModules["IPSCam"])) { 				echo "Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
	if (isset ($installedModules["OperationCenter"])) { 	echo "Modul OperationCenter ist installiert.\n"; } else { echo "Modul OperationCenter ist NICHT installiert.\n"; }


	/****************************************************************************************************************/
	/*                                                                                                              */
	/*                                      Draw Webfront Topologie                                                                 */
	/*                                                                                                              */
	/****************************************************************************************************************/

    /* nutzt die Library aus DetectMovement */

    /* ein Raum hat zumindest folgende Eigenschaften:
    *
    * Temperature			aktuelle Temperatur
    * Humidity				aktuelle Luftfeuchtigkeit
    * Movement				Bewegung erkannt oder nicht
    * HeatControl			Sollwert der Heizung/Kühlung
    *
    * Contacts				Summe der Kontakte in diesem Raum (Fenster, Tueren ...)
    *
    * Liste der Automatisierungsgeräte in diesem Raum
    *
    * eine Wohnung hat dieselben Objekte wie ein Raum, aber mehr Summenobjekte, zusaetzlich
    *
    *	Namen der Personen die sich aktuell in der Wohnung aufhalten
    *
    * ein Ort hat 
    *
    *	Temperature
    *	Humidity
    *
    * ein Grundstück ist wie ein Ort und hat zusaetzlich
    *	Movement			wenn Aussensesoren angebracht sind (zB im Garten)
    *
    *
    * zu Temperatur: es werden die STATUS Register der Automatisiserungsgeräte verwendet und zusaetzliche Summenregister evaluiert
    *
    * 
    * Device Handler ist in DetectMovementLib angelegt
    *
    * mit _construct wird bereits die Topologie im Webfront angelegt.
    *
    *
    * EvaluateHardware bearbeitet die Kategorie Topologie in der Root. Hier werden Topology Geräte einsortiert
    *
    *
    * hier zusammenfassen und als includefile wieder ausgeben
    *
    */

	echo "Topology ausgeben:\n";
	$DetectDeviceHandler = new DetectDeviceHandler();
	$DetectDeviceListHandler = new DetectDeviceListHandler();
    $ipsOps=new ipsOps();

	if ($debug) 
        {
        /* ein paar extra Auswertungen */
        $DetectDeviceHandler->create_TopologyConfigurationFile(true);
        $result=$DetectDeviceHandler->ListGroups("Topology");
        print_r($result);
    	$DetectDeviceHandler->evalTopology("World");
        echo "Definiere und erzeuge Kategorien für die Topologie:\n";
        }

	if (isset ($installedModules["Startpage"])) 
        { 				
        echo "Modul Startpage ist installiert.\n"; 
        IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
        IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
        IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');        
        $startpage = new StartpageHandler();                        // true with debug
        }
    else 
        { 
        echo "Modul Startpage ist NICHT installiert.\n"; 
        }

	/*************************************************************************************/
    if (false)   // nur in EvaluateHardware_Installation machen
        {
        $DetectDeviceHandler->create_Topology(true, true);            // true für init, true für Debug
        $topology=$DetectDeviceHandler->Get_Topology();
        $configurationDevice = $DetectDeviceHandler->Get_EventConfigurationAuto();        // IPSDetectDeviceHandler_GetEventConfiguration()
        $configurationEvent = $DetectDeviceListHandler->Get_EventConfigurationAuto();        // IPSDetectDeviceHandler_GetEventConfiguration()

        print_r($topology);

        /* die Topologie mit den Geräten anreichen:
            *    wir starten mit Name, Parent, Type, OID, Children  
            * Es gibt Links zu Chíldren, INSTANCE und OBJECT 
            *    Children, listet die untergeordneten Eintraege
            *    OBJECT sind dann wenn das Gewerk in der Eventliste angegeben wurde, wie zB Temperature, Humidity aso
            *    INSTANCE ist der vollständigkeit halber für die Geräte
            *
            * Damit diese Tabelle funktioniert muss der DetDeviceHandler fleissig register definieren
            */
        
        echo "=====================================================================================\n";
        echo "mergeTopologyObjects aufgerufen:\n";
        $topologyPlusLinks=$DetectDeviceHandler->mergeTopologyObjects($topology,$configurationDevice,false);        // true for Debug
        //echo "=====================================================================================\n";
        //$topologyPlusLinks=$DetectDeviceListHandler->mergeTopologyObjects($topologyPlusLinks,$configurationEvent,$debug);

        if ($debug) 
            {
            echo "=====================================================================================\n";
            echo "looking at Webfront Kategory $categoryId_AdminWebFront ".$ipsOps->path($categoryId_AdminWebFront)."\n";
            $worldID=IPS_GetObjectIDByName("World",$categoryId_AdminWebFront);
            if ($worldID===false) echo "Failure, Dont know why but we miss category World in $categoryId_AdminWebFront.\n";
            //print_r($topologyPlusLinks);

            /* testweise hier die Auswertung machen 
            $html = $startpage->showTopology();
            echo $html;     */

            print_R($topologyPlusLinks);
            }

        $DetectDeviceHandler->updateLinks($topologyPlusLinks);
        }
  
	/****************************************************************************************************************/
	/*                                                                                                              */
	/*                                      Install                                                                 */
	/*                                                                                                              */
	/****************************************************************************************************************/

	IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
	IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

	IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	
    echo "=====================================================================================\n";
    echo "Install\n";    
    $componentHandling=new ComponentHandling();
    $commentField="zuletzt Konfiguriert von DetectMovement EvaluateMotion um ".date("h:i am d.m.Y ").".";
    $DetectMovementHandler = new DetectMovementHandler();
    echo "List Groups Motion:\n";
    $groups=$DetectMovementHandler->ListGroups('Motion');
    print_r($groups);
    echo "List Groups Sensor:\n";
    $DetectSensorHandler   = new DetectSensorHandler();
    $groups=$DetectSensorHandler->ListGroups('');
    print_r($groups);
    echo "List Groups Climate:\n";
    $DetectClimateHandler  = new DetectClimateHandler();
    $groups=$DetectClimateHandler->ListGroups('');
    print_r($groups);
    echo "List Groups Humidity:\n";
    $DetectHumidityHandler = new DetectHumidityHandler();
    $groups=$DetectHumidityHandler->ListGroups('Feuchtigkeit');
    print_r($groups);
    echo "List Groups Contacts:\n";
    $DetectContactHandler  = new DetectContactHandler();
    $groups=$DetectContactHandler->ListGroups('Contact');
    print_r($groups);
    echo "List Groups Brightness:\n";
    $DetectBrightnessHandler = new DetectBrightnessHandler();
    $groups=$DetectBrightnessHandler->ListGroups('');
    print_r($groups);
    echo "List Groups Temperature:\n";
    $DetectTemperatureHandler = new DetectTemperatureHandler(); 
    $groups=$DetectTemperatureHandler->ListGroups('Temperatur');
    print_r($groups);
    echo "List Groups Heatcontrol:\n";
    $DetectHeatControlHandler = new DetectHeatControlHandler();
    $groups=$DetectHeatControlHandler->ListGroups('HeatControl');
    print_r($groups);

	$messageHandler = new IPSMessageHandlerExtended();

    if ($installModules)
        {

        /****************************************************************************************************************
        *                                                                                                    
        *                                      Movement
        *
        ****************************************************************************************************************/
        
        echo "\n";
        echo "***********************************************************************************************\n";
        echo "Detect Movement Handler wird ausgeführt.\n";

        /* nur die Detect Movement Funktion registrieren */
        /* Wenn Eintrag in Datenbank bereits besteht wird er nicht mehr geaendert */

        echo "***********************************************************************************************\n";
        echo "Bewegungsmelder und Contact Handler wird ausgeführt.\n";
        if (function_exists('deviceList'))
            {
            echo "Bewegungsmelder von verschiedenen Geräten werden registriert.\n";
            $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "MOTION"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungsensoren */
            //print_r($result);
            echo "Kontakte von verschiedenen Geräten werden registriert.\n";
            $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_CONTACT","REGISTER" => "CONTACT"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungsensoren */
            //print_r($result);
            }
        elseif (function_exists('HomematicList'))
            {
            echo "\n-----------------------------------------------------\n";
            echo "Homematic Bewegungsmelder werden registriert.\n";
            // $components=$componentHandling->getComponent(HomematicList(),"MOTION"); print_r($components);
            // installComponentFull($Elements,$keywords,$InitComponent="", $InitModule="", $commentField="",$debug=false)
            $componentHandling->installComponentFull(HomematicList(),"MOTION",'IPSComponentSensor_Motion','IPSModuleSensor_Motion',$commentField);
            echo "\n-----------------------------------------------------\n";
            echo "Homematic Kontaktgeber werden registriert.\n";        
            //$components=$componentHandling->getComponent(HomematicList(),"TYPE_CONTACT"); print_r($components);		
            $componentHandling->installComponentFull(HomematicList(),"TYPE_CONTACT",'IPSComponentSensor_Motion','IPSModuleSensor_Motion',$commentField,false);              // true für Debug

            echo "\n";
                    
            if (function_exists('FS20List'))
                {
                echo "\n-----------------------------------------------------\n";
                echo "FS20 Bewegungsmelder und Kontakte werden registriert.\n";
                $TypeFS20=RemoteAccess_TypeFS20();
                $FS20= FS20List();
                foreach ($FS20 as $Key)
                    {
                    /* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
                    $found=false;
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder */
                        $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                        $found=true;
                        }
                    /* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei verknüpfen */
                    if ((isset($Key["COID"]["StatusVariable"])==true))
                        {
                        foreach ($TypeFS20 as $Type)
                            {
                            if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
                                {
                                $oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
                                $found=true;
                                }
                            }
                        }

                    if ($found)
                        {
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        else
                            {
                            echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                            }
                        $DetectMovementHandler->RegisterEvent($oid,"Motion",'','');

                        if (isset ($installedModules["RemoteAccess"]))
                            {
                            //echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
                            }
                        else
                            {
                            /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
                            echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
                            $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
                            $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

                            /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
                            $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3',$debug);
                            }
                        }
                    }
                }
            }
            
        if (isset ($installedModules["IPSCam"]))
            {
            IPSUtils_Include ("IPSCam.inc.php",     "IPSLibrary::app::modules::IPSCam");

            $camManager = new IPSCam_Manager();
            $config     = IPSCam_GetConfiguration();
            echo "\n";
            echo "Folgende Kameras sind im Modul IPSCam vorhanden:\n";
            foreach ($config as $cam)
                {
                echo "   Kamera : ".$cam["Name"]." vom Typ ".$cam["Type"]."\n";
                }
            echo "\n";
            echo "Bearbeite lokale Kameras wie im Modul OperationCenter definiert:\n";
            if (isset ($installedModules["OperationCenter"]))
                {
                IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
                $OperationCenterConfig = OperationCenter_Configuration();
                echo "    IPSCam und OperationCenter Modul installiert. \n";
                if (isset ($OperationCenterConfig['CAM']))
                    {
                    echo "  Im OperationCenterConfig sind auch die CAM Variablen angelegt.\n";
                    foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
                        {
                        $OperationCenterScriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
                        $OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
                        $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$OperationCenterDataId);

                        $WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
                        echo "    Bearbeite Kamera : ".$cam_name." Cam Category ID : ".$cam_categoryId."  Motion ID : ".$WebCam_MotionID."\n";;

                        $oid=$WebCam_MotionID;
                        $cam_name="IPCam_".$cam_name;
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            echo "      ".str_pad($cam_name,30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
                            }
                        else
                            {
                            echo "      ".str_pad($cam_name,30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
                            }
                        $DetectMovementHandler->RegisterEvent($oid,"Motion",'','');
                
                        if (isset ($installedModules["RemoteAccess"]))
                            {
                            //echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
                            }
                        else
                            {
                            /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
                            echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
                            $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
                            $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

                            /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
                            $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3',$debug);
                            }
                        }

                    }  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
                }     /* isset OperationCenter */
            }     /* isset IPSCam */
        }

	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "\n================================================================================================================================\n";
		echo "Remote Access installiert, zumindest die Gruppen Variablen für Bewegung/Motion auch auf den RemoteAccess VIS Server aufmachen.\n";
		echo "Für die Erzeugung der einzelnen Variablen am Remote Server rufen sie dazu die entsprechenden Remote Access Routinen auf (EvaluateXXXX) ! \n";
		IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$remServer=ROID_List();
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$ZusammenfassungID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Zusammenfassung");
			}
		if (isset($ZusammenfassungID)==true) 
            {
            echo "Ort der Kategorie Zusammenfassung auf den Remote Servern:\n";
            print_r($ZusammenfassungID);	
            }

		echo "\n jetzt die einzelnen Zusammenfassungsvariablen für die Gruppen anlegen.\n";
		$groups=$DetectMovementHandler->ListGroups('Motion');
		foreach($groups as $group=>$name)
			{
			$statusID=$DetectMovementHandler->InitGroup($group);
			/* nur die Gesamtauswertungen ohne Delay auf den remoteAccess Servern anlegen */		
			if (false)
				{
				echo "\n";
				echo "Gruppe ".$group." behandeln.\n";
				$config=$DetectMovementHandler->ListEvents($group);
				$status=false;
				foreach ($config as $oid=>$params)
					{
					$status=$status || GetValue($oid);
					echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
					}
				echo "Gruppe ".$group." hat neuen Status : ".(integer)$status."\n";
				/* letzte Variable noch einmal aktivieren damit der Speicherort gefunden werden kann */
				$logMot=new Motion_Logging($oid);
				//print_r($logMot);
				$class=$logMot->GetComponent($oid);
				$statusID=CreateVariable("Gesamtauswertung_".$group,0,IPS_GetParent(intval($logMot->GetEreignisID() )));
  				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     			AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
				AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				SetValue($statusID,(integer)$status);
				}

			$parameter="";
			foreach ($remServer as $Name => $Server)
				{
				$rpc = new JSONRPC($Server["Adresse"]);
				$result=RPC_CreateVariableByName($rpc, $ZusammenfassungID[$Name], "Gesamtauswertung_".$group, 0);
   				$rpc->IPS_SetVariableCustomProfile($result,"Motion");
				$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
				$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0); 	/* 0 Standard 1 ist Zähler */
				$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
				$parameter.=$Name.":".$result.";";
				}
			echo "Summenvariable Gesamtauswertung_".$group." mit ".$statusID." auf den folgenden Remoteservern angelegt [Name:OID] : ".$parameter."\n";
   			//$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			$messageHandler->CreateEvent($statusID,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($statusID,"OnChange",'IPSComponentSensor_Motion,'.$parameter,'IPSModuleSensor_Motion',$debug);
			/* die alte IPSComponentSensor_Remote Variante wird eigentlich nicht mehr verwendet */
			echo "Event ".$statusID." mit Parameter ".$parameter." wurde als Gesamtauswertung_".$group." registriert.\n";
			}

		echo "\n jetzt die einzelnen Zusammenfassungsvariablen für die Gruppen anlegen.\n";
		$groups=$DetectTemperatureHandler->ListGroups('Temperatur');
		foreach($groups as $group=>$name)
			{
			$statusID=$DetectTemperatureHandler->InitGroup($group);
			$parameter="";
			foreach ($remServer as $Name => $Server)
				{
				$rpc = new JSONRPC($Server["Adresse"]);
				$result=RPC_CreateVariableByName($rpc, $ZusammenfassungID[$Name], "Gesamtauswertung_".$group, 2);
   				$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
				$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
				$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0); 	/* 0 Standard 1 ist Zähler */
				$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
				$parameter.=$Name.":".$result.";";
				}
			echo "Summenvariable Gesamtauswertung_".$group." mit ".$statusID." auf den folgenden Remoteservern angelegt [Name:OID] : ".$parameter."\n";
   			//$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			$messageHandler->CreateEvent($statusID,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($statusID,"OnChange",'IPSComponentSensor_Temperatur,,'.$parameter.',TEMPERATUR','IPSModuleSensor_Temperatur',$debug);           //ROID Angaben immer bei par2, par1 ist für die Instanz reserviert - ausser Aktoren
			/* die alte IPSComponentSensor_Remote Variante wird eigentlich nicht mehr verwendet */
			echo "Event ".$statusID." mit Parameter ".$parameter." wurde als Gesamtauswertung_".$group." registriert.\n";
			}

		}

    if ($installModules)
        {
        /****************************************************************************************************************
        *
        *                                      Temperature
        *
        ****************************************************************************************************************/

        $componentHandling=new ComponentHandling();
        $commentField="zuletzt Konfiguriert von EvaluateHomematic um ".date("h:i am d.m.Y ").".";

        /****************************************************************************************************************
        *
        *                                      Temperature
        *
        ****************************************************************************************************************/
        echo "\n";
        echo "***********************************************************************************************\n";
        echo "Temperatur Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
        echo "\n";
        echo "Homematic Temperatur Sensoren werden registriert.\n";
        if (function_exists('HomematicList'))
            {
            $componentHandling->installComponentFull(HomematicList(),"TEMPERATURE",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);				/* Temperatursensoren und Homematic Thermostat */
            $componentHandling->installComponentFull(HomematicList(),"ACTUAL_TEMPERATURE",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);		/* HomematicIP Thermostat */
            } 
        echo "FHT Heizungssteuerung Geräte werden registriert.\n";
        if (function_exists('FHTList'))
            {
            $componentHandling->installComponentFull(FHTList(),"TemeratureVar",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);
            }


        /****************************************************************************************************************
        *
        *                                      Humidity
        *
        ****************************************************************************************************************/
        echo "\n";
        echo "***********************************************************************************************\n";
        echo "Humidity Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
        echo "\n";
        echo "Homematic Humidity Sensoren werden registriert.\n";
        if (function_exists('HomematicList'))
            {
            $componentHandling->installComponentFull(HomematicList(),"HUMIDITY",'IPSComponentSensor_Feuchtigkeit','IPSModuleSensor_Feuchtigkeit,',$commentField,true);      // true Debug
            } 


        /****************************************************************************************************************
        *
        *                                      HeatControl
        *
        ****************************************************************************************************************/

        $DetectHeatControlHandler = new DetectHeatControlHandler();
        
        echo "\n";
        echo "***********************************************************************************************\n";
        echo "HeatControl Handler wird ausgeführt.\n";
        echo "\n";
        echo "Homematic Heat Actuators werden registriert.\n";

        if (function_exists('HomematicList'))
            {
            $componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),"TYPE_ACTUATOR",'IPSComponentHeatControl_Homematic','IPSModuleHeatControl_All');
            $componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),"TYPE_ACTUATOR",'IPSComponentHeatControl_HomematicIP','IPSModuleHeatControl_All');
            }
            
        echo "\n";
        echo "FHT80b Heat Control Actuator werden registriert.\n";
        if (function_exists('FHTList'))
            {
            //installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All');
            $componentHandling->installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All');
            }

        }












?>