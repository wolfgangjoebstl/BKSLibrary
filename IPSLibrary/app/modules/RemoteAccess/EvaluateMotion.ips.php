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

   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

	$Homematic = HomematicList();
	$FHT = FHTList();
	$FS20= FS20List();

	/*
	 *
	 ******************************************* Bewegungsmelder **********************************************
	 *
	 */

	$remServer=ROID_List();
	$status=RemoteAccessServerTable();	
	//print_r($remServer);
	
	/*
	 *  Homematic
	 *
	 */
	
	echo "******* Alle Homematic Bewegungsmelder ausgeben.\n";
	$keyword="MOTION";
	foreach ($Homematic as $Key)
		{
		if ( (isset($Key["COID"][$keyword])==true) )
	   	{
	   	/* alle Bewegungsmelder */

	      $oid=(integer)$Key["COID"][$keyword]["OID"];
      	$variabletyp=IPS_GetVariable($oid);
      	
  	   	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
      	if (AC_GetLoggingStatus($archiveHandlerID,$oid)==false)
				{
				/* Wenn variable noch nicht gelogged automatisch logging einschalten */
				AC_SetLoggingStatus($archiveHandlerID,$oid,true);
				AC_SetAggregationType($archiveHandlerID,$oid,0);
				IPS_ApplyChanges($archiveHandlerID);
				echo "Variable ".$oid." Archiv logging aktiviert.\n";
				}
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
		   echo "Message Handler hat Homematic Bewegungsmelder Event mit ".$oid." und ROIDs mit ".$parameter." angelegt.\n";
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

	/*
	 *  FS20
	 *
	 */

	set_time_limit(120);
	$TypeFS20=RemoteAccess_TypeFS20();

	foreach ($FS20 as $Key)
		{
		/* FS20 alle Bewegungsmelder ausgeben */
		if ((isset($Key["COID"]["StatusVariable"])==true))
		   	{
		   	foreach ($TypeFS20 as $Type)
		   	   {
		   	   if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
		   	      {
				   	echo "Bewegungsmelder : ".$Key["Name"]." OID ".$Key["OID"]."\n";

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

	/*
	 *  IPCams
	 *
	 */

	set_time_limit(120);

	if (isset ($installedModules["IPSCam"]))
		{
		IPSUtils_Include ("IPSCam.inc.php",     "IPSLibrary::app::modules::IPSCam");

		$camManager = new IPSCam_Manager();
		$config     = IPSCam_GetConfiguration();
	   echo "Folgende Kameras sind im Modul IPSCam vorhanden:\n";
		foreach ($config as $cam)
	   	{
		   echo "   Kamera : ".$cam["Name"]." vom Typ ".$cam["Type"]."\n";
		   }
	   echo "Bearbeite lokale Kameras im Modul OperationCenter definiert:\n";
		if (isset ($installedModules["OperationCenter"]))
			{
			IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
			$OperationCenterConfig = OperationCenter_Configuration();
			echo "IPSCam und OperationCenter Modul installiert. \n";
			if (isset ($OperationCenterConfig['CAM']))
				{
				echo "Im OperationCenterConfig auch die CAm Variablen angelegt.\n";
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
					$OperationCenterScriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
					$OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
					$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$OperationCenterDataId);

					$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
					echo "   Bearbeite Kamera : ".$cam_name." Cam Category ID : ".$cam_categoryId."  Motion ID : ".$WebCam_MotionID."\n";;

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

					$parameter="";
					foreach ($remServer as $Name => $Server)
						{
						echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
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
					$messageHandler = new IPSMessageHandler();
				   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   		echo "Message Handler hat IPCAM Event mit ".$oid." und ROIDs mit ".$parameter." angelegt.\n";
				   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
					$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion,'.$parameter,'IPSModuleSensor_Motion');
					
					if (isset ($installedModules["DetectMovement"]))
						{
						//echo "Detect Movement anlegen.\n";
					   $DetectMovementHandler = new DetectMovementHandler();
						$DetectMovementHandler->RegisterEvent($oid,"Motion",'','');
						}
					}

				}  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
			}     /* isset OperationCenter */
		}     /* isset IPSCam */



	
	
?>