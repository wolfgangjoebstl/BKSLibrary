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


	/**@defgroup DetectMovement
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script um Ereignisse zusammenzufassen, ursprünglich für die Bewegungserfassung gecshrieben
	 *
	 * funktioniert nun auch für Bewegung, Temperatur und feuchtigkeit
	 *
	 *
	 * @file          DetectMovement_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

	$startexec=microtime(true);

	/****************************************************************************************************************/
	/*                                                                                                              */
	/*                                      Init                                                                    */
	/*                                                                                                              */
	/****************************************************************************************************************/


	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('DetectMovement',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('DetectMovement');
	echo "\nDetectMovement Version : ".$ergebnis."\n";

 	$installedModules = $moduleManager->GetInstalledModules();
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; break; }
	if (isset ($installedModules["EvaluateHardware"])) { echo "Modul EvaluateHardware ist installiert.\n"; } else { echo "Modul EvaluateHardware ist NICHT installiert.\n"; break;}
	if (isset ($installedModules["RemoteReadWrite"])) { echo "Modul RemoteReadWrite ist installiert.\n"; } else { echo "Modul RemoteReadWrite ist NICHT installiert.\n"; break;}
	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "Modul RemoteAccess ist installiert.\n";
		IPSUtils_Include ('RemoteAccess_Configuration.inc.php', 'IPSLibrary::config::modules::RemoteAccess');
		}
	else
		{
		echo "Modul RemoteAccess ist NICHT installiert.\n";
		break;
		}
	if (isset ($installedModules["IPSCam"])) { 				echo "Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
	if (isset ($installedModules["OperationCenter"])) { 	echo "Modul OperationCenter ist installiert.\n"; } else { echo "Modul OperationCenter ist NICHT installiert.\n"; }

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
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");

	/****************************************************************************************************************
	 *                                                                                                    
	 *                                      Movement
	 *
	 ****************************************************************************************************************/


	$DetectMovementHandler = new DetectMovementHandler();
	
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Detect Movement Handler wird ausgeführt.\n";

	/* nur die Detect Movement Funktion registrieren */
	/* Wenn Eintrag in Datenbank bereits besteht wird er nicht mehr geaendert */

	echo "\n";
	if (function_exists('HomematicList'))
	   {
		echo "Homematic Bewegungsmelder und Kontakte werden registriert.\n";
		$Homematic = HomematicList();
		$keyword="MOTION";
		foreach ($Homematic as $Key)
			{
			$found=false;
			if ( (isset($Key["COID"][$keyword])==true) )
		   	{
	   		/* alle Bewegungsmelder */

		      $oid=(integer)$Key["COID"][$keyword]["OID"];
		      $found=true;
				}

			if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
		   	{
	   		/* alle Kontakte */

		      $oid=(integer)$Key["COID"]["STATE"]["OID"];
		      $found=true;
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
					echo "Remote Access nicht installiert, Variable ".IPS_GetName($oid)." selbst registrieren.\n";
			   	$messageHandler = new IPSMessageHandler();
				   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

				   /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
				   }
				}
			}
		}

	if (function_exists('FS20List'))
	   {
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
				   $messageHandler = new IPSMessageHandler();
				   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

				   /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
				   }
				}
			}
		}
		
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
				echo "Im OperationCenterConfig sind auch die CAM Variablen angelegt.\n";
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
					$DetectMovementHandler->RegisterEvent($oid,"Motion",'','');
			
					if (isset ($installedModules["RemoteAccess"]))
						{
						//echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
						}
					else
					   {
					   /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
						echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
					   $messageHandler = new IPSMessageHandler();
					   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					   /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
						$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
					   }
					}

				}  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
			}     /* isset OperationCenter */
		}     /* isset IPSCam */


	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "\n";
		echo "Remote Access installiert, Gruppen Variablen für Bewegung/Motion auch auf den RemoteAccess VIS Server aufmachen.\n";
		echo "Für die Erzeugung der einzelnen Variablen am Remote Server rufen sie dazu die entsprechende Remote Access Routine auf ! \n";
		IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$remServer=ROID_List();
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$ZusammenfassungID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Zusammenfassung");
			}


		$groups=$DetectMovementHandler->ListGroups();
		foreach($groups as $group=>$name)
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
			$log=new Motion_Logging($oid);
			//print_r($log);
			$class=$log->GetComponent($oid);
			$statusID=CreateVariable("Gesamtauswertung_".$group,0,IPS_GetParent(intval($log->GetEreignisID() )));
  	   	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
			IPS_ApplyChanges($archiveHandlerID);
			SetValue($statusID,(integer)$status);

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
		   $messageHandler = new IPSMessageHandler();
   		$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($statusID,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($statusID,"OnChange",'IPSComponentSensor_Motion,'.$parameter,'IPSModuleSensor_Motion');
			/* die alte IPSComponentSensor_Remote Variante wird eigentlich nicht mehr verwendet */
			echo "Event ".$statusID." mit Parameter ".$parameter." wurde als Gesamtauswertung_".$group." registriert.\n";

		   }
		}

	/****************************************************************************************************************
	 *
	 *                                      Temperature
	 *
	 ****************************************************************************************************************/

	$DetectTemperatureHandler = new DetectTemperatureHandler();
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Temperatur Handler wird ausgeführt.\n";
	echo "\n";
	echo "Homematic Temperatur Sensoren werden registriert.\n";

	$Homematic = HomematicList();
	$keyword="TEMPERATURE";
	foreach ($Homematic as $Key)
		{
		/* alle Temperaturwerte ausgeben */
		if (isset($Key["COID"][$keyword])==true)
			{
	      $oid=(integer)$Key["COID"][$keyword]["OID"];
	     	$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			else
	   		{
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','par3');     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */

			if (isset ($installedModules["RemoteAccess"]))
				{
				//echo "Remote Access installiert, Gruppen Variablen auch am VIS Server aufmachen.\n";
				}
			else
			   {
			   /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
				echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

			   /* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,1,2,3');
			   }

			}  /* Ende isset Homatic Temperatur */
		} /* Ende foreach */


	echo "FHT Heizungssteuerung Geräte werden registriert.\n";

	$FHT = FHTList();
	$keyword="TemeratureVar";

	foreach ($FHT as $Key)
		{
		/* alle Temperaturwerte der Heizungssteuerungen ausgeben */
		if (isset($Key["COID"][$keyword])==true)
  			{
	      $oid=(integer)$Key["COID"][$keyword]["OID"];
      	$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			else
	   		{
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','par3');     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */

			if (isset ($installedModules["RemoteAccess"]))
				{
				//echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
				}
			else
			   {
			   /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
				echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

			   /* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,1,2,3');
			   }

			}  /* Ende isset Heizungssteuerung */
		} /* Ende foreach */

	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "\n";
		echo "Remote Access installiert, Gruppen Variable für Temperatur auch am VIS Server aufmachen.\n";
		echo "Für die Erzeugung der einzelnen Variablen am Remote Server rufen sie dazu die entsprechende Remote Access Routine auf ! \n";
		IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$remServer=ROID_List();
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$ZusammenfassungID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Zusammenfassung");
			}


		$groups=$DetectTemperatureHandler->ListGroups();
		foreach($groups as $group=>$name)
		   {
		   if (($group != "") | ($group != " "))
		      {
		      echo "\n";
			   echo "Gruppe ".$group." behandeln.\n";
				$config=$DetectTemperatureHandler->ListEvents($group);
				$status=(float)0;
				$count=0;
				foreach ($config as $oid=>$params)
					{
					$status+=GetValue($oid);
					$count++;
					echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".GetValue($oid)." ".$status."\n";
					}
				if ($count>0) { $status=$status/$count; }
			   echo "Gruppe ".$group." hat neuen Temperaturmittelwert : ".$status."\n";
  		   	/* letzte Variable noch einmal aktivieren damit der Speicherort gefunden werden kann */
				$log=new Temperature_Logging($oid);
				//print_r($log);
				$class=$log->GetComponent($oid);
				//echo "Letzte Variable hat OID :".$log->variableLogID."\n"; /* EreignisID gibt es bei Temperatur nicht, anderen Wert holen und im selben Verzeichnis den Summenspeicher anlegen */
				$statusID=CreateVariable2("Gesamtauswertung_".$group,2,IPS_GetParent(intval($log->variableLogID)),900,"~Temperature");
				/* auch die Archivierung einsetzen */
   	   	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
      		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
				AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				SetValue($statusID,$status);

				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, $ZusammenfassungID[$Name], "Gesamtauswertung_".$group, 2);
   				$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
				echo "Summenvariable Gesamtauswertung_".$group." auf den folgenden Remoteservern angelegt Name:OID ".$parameter."\n";
			   $messageHandler = new IPSMessageHandler();
   			$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   $messageHandler->CreateEvent($statusID,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($statusID,"OnChange",'IPSComponentSensor_Temperatur,'.$parameter,'IPSModuleSensor_Temperatur,1,2,3');
				echo "Event ".$statusID." mit Parameter ".$parameter." wurde als Gesamtauswertung_".$group." registriert.\n";
				}
		   }
		}

	/****************************************************************************************************************
	 *
	 *                                      Humidity
	 *
	 ****************************************************************************************************************/

	$DetectHumidityHandler = new DetectHumidityHandler();
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Humidity Handler wird ausgeführt.\n";
	echo "\n";
	echo "Homematic Humidity Sensoren werden registriert.\n";

	$Homematic = HomematicList();
	$keyword="HUMIDITY";
	foreach ($Homematic as $Key)
		{
		/* alle Feuchtigkeitswerte ausgeben */
		if (isset($Key["COID"][$keyword])==true)
			{
	      $oid=(integer)$Key["COID"][$keyword]["OID"];
	     	$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			else
	   		{
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			$DetectHumidityHandler->RegisterEvent($oid,"Feuchtigkeit",'','par3');     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */

			if (isset ($installedModules["RemoteAccess"]))
				{
				//echo "Remote Access installiert, Gruppen Variablen auch am VIS Server aufmachen.\n";
				//echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
				}
			else
			   {
		   	/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
				echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   	$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

			   /* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Feuchtigkeit','IPSModuleSensor_Feuchtigkeit,1,2,3');
			   }

			}  /* Ende isset Feuchtigkeitswert */
		} /* Ende foreach */

	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "\n";
		echo "Remote Access installiert, Gruppen Variable für Feuchtighkeit auch am VIS Server aufmachen.\n";
		echo "Für die Erzeugung der einzelnen Variablen am Remote Server rufen sie dazu die entsprechende Remote Access Routine auf ! \n";
		IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$remServer=ROID_List();
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$ZusammenfassungID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Zusammenfassung");
			}


		$groups=$DetectHumidityHandler->ListGroups();
		foreach($groups as $group=>$name)
		   {
		   if (($group != "") | ($group != " "))
		      {
		      echo "\n";
			   echo "Gruppe ".$group." behandeln.\n";
				$config=$DetectHumidityHandler->ListEvents($group);
				$status=(integer)0;
				$count=0;
				foreach ($config as $oid=>$params)
					{
					$status+=GetValue($oid);
					$count++;
					echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".GetValue($oid)." ".$status."\n";
					}
				if ($count>0) { $status=$status/$count; }
				$statusint=(integer)$status;
			   echo "Gruppe ".$group." hat neuen Feuchtigkeitsmittelwert : ".$statusint."\n";
  		   	/* letzte Variable noch einmal aktivieren damit der Speicherort gefunden werden kann */
				$log=new Feuchtigkeit_Logging($oid);
				//print_r($log);
				$class=$log->GetComponent($oid);
				//echo "Letzte Variable hat OID :".$log->variableLogID."\n"; /* EreignisID gibt es bei Temperatur nicht, anderen Wert holen und im selben Verzeichnis den Summenspeicher anlegen */
				$statusID=CreateVariable2("Gesamtauswertung_".$group,1,IPS_GetParent(intval($log->variableLogID)),900,"~Humidity");  /* 0 .. Boolean 1..Integer 2..Float 3..String */
				/* auch die Archivierung einsetzen */
   	   	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
      		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
				AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				SetValue($statusID,$statusint);

				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, $ZusammenfassungID[$Name], "Gesamtauswertung_".$group, 1);
   				$rpc->IPS_SetVariableCustomProfile($result,"~Humidity");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
				echo "Summenvariable Gesamtauswertung_".$group." auf den folgenden Remoteservern angelegt Name:OID ".$parameter."\n";
			   $messageHandler = new IPSMessageHandler();
   			$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   $messageHandler->CreateEvent($statusID,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($statusID,"OnChange",'IPSComponentSensor_Feuchtigkeit,'.$parameter,'IPSModuleSensor_Feuchtigkeit,1,2,3');
				echo "Event ".$statusID." mit Parameter ".$parameter." wurde als Gesamtauswertung_".$group." registriert.\n";
				}
		   }
		}
























?>