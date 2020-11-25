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
	 
/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für Homematic und FS20 Bewegungsmelder, und die Bewegungsmelder der Cams
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');		/* für Definiotion RemoteAccess_TypeFS20 wenn benötigt */

 /******************************************************
  *
  *  			INIT
  *
  *************************************************************/

// max. Scriptlaufzeit definieren
set_time_limit(120);
$startexec=microtime(true);

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

echo "RA Category Data ID   : ".$CategoryIdData."\n";
echo "RA Category App ID    : ".$CategoryIdApp."\n";

    echo "Folgende Module werden von RemoteAccess bearbeitet:\n";
    if (isset ($installedModules["IPSLight"])) { 			echo "  Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; }
    if (isset ($installedModules["IPSPowerControl"])) { 	echo "  Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n";}
    if (isset ($installedModules["IPSCam"])) { 				echo "  Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
    if (isset ($installedModules["OperationCenter"])) { 	echo "  Modul OperationCenter ist installiert.\n"; } else { echo "Modul OperationCenter ist NICHT installiert.\n"; }
    if (isset ($installedModules["RemoteAccess"])) { 		echo "  Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; }
    if (isset ($installedModules["LedAnsteuerung"])) { 	echo "  Modul LedAnsteuerung ist installiert.\n"; } else { echo "Modul LedAnsteuerung ist NICHT installiert.\n";}
    if (isset ($installedModules["DENONsteuerung"])) { 	echo "  Modul DENONsteuerung ist installiert.\n"; } else { echo "Modul DENONsteuerung ist NICHT installiert.\n";}
    if (isset ($installedModules["DetectMovement"])) { 	echo "  Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n";}
	if (isset ($installedModules["EvaluateHardware"])) 
        { 
        echo "Modul EvaluateHardware ist installiert.\n"; 
        IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');      
        IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");                  // jetzt neu unter config
        IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt

        echo "========================================================================\n";    
        echo "Statistik der Register nach Typen:\n";
        $hardwareTypeDetect = new Hardware();
        $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php
        $statistic = $hardwareTypeDetect->getRegisterStatistics($deviceList,false);                // false keine Warnings ausgeben
        print_r($statistic);        
        } 
    else 
        { 
        echo "Modul EvaluateHardware ist NICHT installiert. Routinen werden uebersprungen.\n"; 
        }
echo "\n";

 /******************************************************
  *
  *  			INSTALLATION
  *
  *************************************************************/

	if (isset ($installedModules["DetectMovement"]))
		{
		IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
		IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
		}

	echo "Update Konfiguration und register Events\n";

	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

	/*
	 *
	 ******************************************* Bewegungsmelder **********************************************
	 *
	 */

    $componentHandling=new ComponentHandling();
    $commentField="zuletzt Konfiguriert von RemoteAccess EvaluateMotion um ".date("h:i am d.m.Y ").".";

	echo "\n";
	echo "***********************************************************************************************\n";
	echo "EvaluateMotion, Bewegungsmelder, Helligkeitssesor und Contact Handler wird ausgeführt:\n";
    echo "--------------------------------------------------------------------------------------\n";
    if ( (function_exists('deviceList')) )
        {
        echo "Bewegungsmelder von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "MOTION"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungsensoren */
        //print_r($result);
        echo "---------------------------------------------------------------\n";
        echo "Helligkeitssensoren von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "BRIGHTNESS"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField,true);               // true mit Debug
        echo "---------------------------------------------------------------\n";
        echo "Kontakte von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_CONTACT","REGISTER" => "CONTACT"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungsensoren */
        //print_r($result);
        }
    elseif (function_exists('HomematicList'))
		{
		echo "\n";
		echo "    Homematic Bewegungsmelder werden registriert.\n";

	
        /*
        *  Homematic
        *
        */

        $Homematic = HomematicList();
        $FHT = FHTList();
        $FS20= FS20List();

        $remServer=ROID_List();
        $status=RemoteAccessServerTable();	
        //print_r($remServer);

		//$components=$componentHandling->getComponent(HomematicList(),"MOTION"); print_r($components);
		$componentHandling->installComponentFull(HomematicList(),"MOTION",'IPSComponentSensor_Motion','IPSModuleSensor_Motion',$commentField);

        /*
        *  FS20
        *
        */

        set_time_limit(120);
        $TypeFS20=RemoteAccess_TypeFS20();

        foreach ($FS20 as $Key)
            {
            echo "\n";
            echo "       FS20 Bewegungsmelder ausgeben:\n";
            if ((isset($Key["COID"]["StatusVariable"])==true))
                {
                foreach ($TypeFS20 as $Type)
                {
                if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
                        {
                        echo "   Bewegungsmelder : ".$Key["Name"]." OID ".$Key["OID"]."\n";

                        $oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
                            }
                        else
                            {
                            echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
                            }
                        $parameter="";
                        foreach ($remServer as $Name => $Server)
                            {
                            echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
                            if ( $status[$Name]["Status"] == true )
                                {							
                                $rpc = new JSONRPC($Server["Adresse"]);
                                $result=RPC_CreateVariableByName($rpc, (integer)$Server["Bewegung"], $Key["Name"], 0);
                                $rpc->IPS_SetVariableCustomProfile($result,"Motion");
                                $rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
                                $rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
                                $rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
                                $parameter.=$Name.":".$result.";";
                                }
                            }
                        $messageHandler = new IPSMessageHandler();
                        $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
                        echo "Message Handler hat FS20 Event mit ".$oid." und ROIDs mit ".$parameter." angelegt.\n";
                        $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
                        $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion,'.$parameter,'IPSModuleSensor_Motion');
                        
                        if (isset ($installedModules["DetectMovement"]))
                            {
                            //echo "Detect Movement anlegen.\n";
                            $DetectMovementHandler = new DetectMovementHandler();
                            $DetectMovementHandler->RegisterEvent($oid,"Motion",'','');
                            }
                        }
                    }

                }
            }

        }

    /*
    *  IPCams
    *
    */
	set_time_limit(120);

    echo "\n";
    echo "---------------------------------------------------------------\n";
    echo "       IPSCAM Bewegungsmelder ausgeben:\n";

	if (isset ($installedModules["IPSCam"]))
		{
		IPSUtils_Include ("IPSCam.inc.php",     "IPSLibrary::app::modules::IPSCam");
        $remServer=ROID_List();
        $status=RemoteAccessServerTable();

		$camManager = new IPSCam_Manager();
		$config     = IPSCam_GetConfiguration();
	    echo "Folgende Kameras sind im Modul IPSCam vorhanden:\n";
		foreach ($config as $cam)
	   	    {
		    echo "   Kamera : ".$cam["Name"]." vom Typ ".$cam["Type"]."\n";
		    }
	    echo "Bearbeite lokale Kameras, die im Modul OperationCenter definiert sind:\n";
		if (isset ($installedModules["OperationCenter"]))
			{
			IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
			$OperationCenterConfig = OperationCenter_Configuration();
			if (isset ($OperationCenterConfig['CAM']))
				{
    			echo "   IPSCam und OperationCenter Modul installiert. \n";
				echo "   Im OperationCenterConfig sind auch die CAM Variablen angelegt:\n";
                $OperationCenterScriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
                $OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
                    if ( (isset($cam_config["FTP"])) && (strtoupper($cam_config["FTP"])=="ENABLED") ) 
                        {
                        $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$OperationCenterDataId);

                        $WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
                        echo "       Bearbeite Kamera : ".$cam_name." Cam Category ID : ".$cam_categoryId."  Motion ID : ".$WebCam_MotionID."\n";;
                        print_R($cam_config);

                        $oid=$WebCam_MotionID;
                        $cam_name="IPCam_".$cam_name;
                        //echo "          ".str_pad($cam_name,30)." = ".str_pad(GetValueIfFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";						
                        $componentHandling = new ComponentHandling();           //true mit Debug
                        $InitComponent = 'IPSComponentSensor_Motion';
                        $parameter="";
                        if (isset ($installedModules["RemoteAccess"]))
                            {
                            foreach ($remServer as $Name => $Server)
                                {
                                echo "        Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
                                if ( $status[$Name]["Status"] == true )
                                    {						
                                    $rpc = new JSONRPC($Server["Adresse"]);
                                    $result=RPC_CreateVariableByName($rpc, (integer)$Server["Bewegung"], $cam_name, 0);
                                    $rpc->IPS_SetVariableCustomProfile($result,"Motion");
                                    $rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
                                    $rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
                                    $rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
                                    $parameter.=$Name.":".$result.";";
                                    }
                                }
                            $componentHandling->RegisterEvent($oid,"OnChange",$InitComponent.','.$parameter,'IPSModuleSensor_Motion',"generated by EvaluateMotion");        //entry OID und Key fehlt und Kommentar 
                            //$this->RegisterEvent($oid,"OnChange",$InitComponent.','.$entry["OID"].','.$parameter.','.$entry["KEY"],$InitModule,$commentField);
                            }
                        else
                            {
                            /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
                            echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
                            $componentHandling->RegisterEvent($oid,"OnChange",$InitComponent.','.$parameter,'IPSModuleSensor_Motion',"generated by EvaluateMotion");        //entry OID und Key fehlt und Kommentar 
                            //$this->RegisterEvent($oid,"OnChange",$InitComponent.",".$entry["OID"].",".$entry["KEY"],$InitModule,$commentField);
                            }			
                        
                        if (isset ($installedModules["DetectMovement"]))
                            {
                            echo "           Detect Movement anlegen, richtiges Verzeichnis erwischen.\n";
                            $DetectMovementHandler = new DetectMovementHandler();
                            $DetectMovementHandler->RegisterEvent($oid,"Motion",'','');
                            }
                        }
					}           // ende ifset FTP=ENABLED

				}  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
			}     /* isset OperationCenter */
		}     /* isset IPSCam */



	
	
?>