<?php

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für alle Homematic Kontakte
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(120); 
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

	echo "Update Konfiguration und register Events:\n\n";

    IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
    IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
    IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt

	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

    $componentHandling=new ComponentHandling();
    $commentField="zuletzt Konfiguriert von RemoteAccess EvaluateMotion um ".date("h:i am d.m.Y ").".";

	echo "\n";
	echo "***********************************************************************************************\n";
	echo "EvaluateMotion, Bewegungsmelder, Helligkeitssesor und Contact Handler wird ausgeführt:\n";
    echo "--------------------------------------------------------------------------------------\n";
    if ( (function_exists('deviceList')) )
        {
        echo "Kontakte von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_CONTACT","REGISTER" => "CONTACT"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungsensoren */
        //print_r($result);
        }
    elseif (function_exists('HomematicList'))
		{
        $Homematic = HomematicList();
        $FHT = FHTList();
        $FS20= FS20List();
        $FS20EX= FS20EXList();

        /******************************************* Kontakte ***********************************************/

        $remServer=ROID_List();
        $status=RemoteAccessServerTable();

        echo "******* Alle Homematic Kontakte ausgeben.\n";
        $keyword="MOTION";
        foreach ($Homematic as $Key)
            {
            if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
                {
                /* alle Kontakte */

                $oid=(integer)$Key["COID"]["STATE"]["OID"];
                $variabletyp=IPS_GetVariable($oid);
                if ($variabletyp["VariableProfile"]!="")
                {
                    echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                    }
                else
                {
                    echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                    }
                $parameter="";
                foreach ($remServer as $Name => $Server)
                    {
                    echo "      Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
                    if ( $status[$Name]["Status"] == true )
                        {
                        $rpc = new JSONRPC($Server["Adresse"]);
                        $result=RPC_CreateVariableByName($rpc, (integer)$Server["Kontakte"], $Key["Name"], 0);
                        $rpc->IPS_SetVariableCustomProfile($result,"Contact");
                        $rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
                        $rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
                        $rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
                        $parameter.=$Name.":".$result.";";
                        }
                    }	
                $messageHandler = new IPSMessageHandler();
                $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
                //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
                $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
                $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion,'.$parameter,'IPSModuleSensor_Motion');
                if (isset ($installedModules["DetectMovement"]))
                    {
                    //echo "Detect Movement anlegen.\n";			
                    $DetectMovementHandler = new DetectMovementHandler();
                    $DetectMovementHandler->RegisterEvent($oid,"Contact",'','');
                    }
                }
            }
        }

if (false)
	{	
	echo "******* Alle FS20EX Kontakte ausgeben.\n";
	foreach ($FS20EX as $Key)
		{
		if ( (isset($Key["COID"])==true) and (isset($Key["DeviceList"])==true) )
			{
			/* alle Kontakte */
			echo str_pad($Key["Name"],30)."\n";
			foreach ($Key["COID"] as $Entry)
				{
				//print_r($Entry);
				$oid=(integer)$Entry["OID"];
				$variabletyp=IPS_GetVariable($oid);
				$SubName=$Entry["Name"];
				if ( strpos($SubName,"rät ") != false) { $SubName = "Status"; }
				if ( strpos($SubName,"räte") != false) { $SubName = "Wert"; }
				$VarName=$Key["Name"]."-".$SubName;
				if ($variabletyp["VariableProfile"]!="")
					{
					echo "    ".str_pad($VarName,30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",$variabletyp["VariableChanged"]).")  ".$variabletyp["VariableType"]."\n";
					}
				else
					{
					echo "    ".str_pad($VarName,30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",$variabletyp["VariableChanged"]).")  ".$variabletyp["VariableType"]."\n";
					}
				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
					if ( $status[$Name]["Status"] == true )
						{
						$rpc = new JSONRPC($Server["Adresse"]);
						$result=RPC_CreateVariableByName($rpc, (integer)$Server["Kontakte"], $VarName, $variabletyp["VariableType"]);
						if ( $variabletyp["VariableType"] == 0) /* Boolean, loggen, Rest zur Vollstaendigkeit */
							{
							$rpc->IPS_SetVariableCustomProfile($result,"Contact");
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							}
						$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
						$parameter.=$Name.":".$result.";";
						}
					}	
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				//echo "Message Handler hat Event mit ".$oid." angelegt.\n";
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion,'.$parameter,'IPSModuleSensor_Motion');

				if (isset ($installedModules["DetectMovement"]))
					{
					//echo "Detect Movement anlegen.\n";
					$DetectMovementHandler = new DetectMovementHandler();
					$DetectMovementHandler->RegisterEvent($oid,"Contact",'','');
					}
				}	
			}
		}           // ende foreach
	}               // ende if false
	
?>