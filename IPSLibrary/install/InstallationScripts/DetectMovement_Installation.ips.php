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

	/****************************************************************************************************
	 *
	 * es gibt mehrere unterschiedliche Routinen die auf eine Aenderung einer Variablen abzielen
	 * Messagehandler arbeitet CustomComponents, DetectMovement und RemoteAccess ab
	 *
	 * bei einer Aenderung wird das entsprechen programmierte Custom Event aufgerufen und dann wenn installiert auch RemoteAccess und DetectMovement abgearbeitet.
	 * Im Detail: eine Eventaenderung ruft den Messagehandler auf, der holt sich aus dem Configfile den entsprechenden Component
	 * im Component wird zuerst logvalue und dann die RemoteAccess Zugriffe abgearbeitet
	 * bei LogValue wird auch noch wenn installiert DetectMovement abgearbeitet
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

	if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; }
	if (isset ($installedModules["EvaluateHardware"])) 
        { 
        echo "Modul EvaluateHardware ist installiert.\n"; 
        IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");        // jetzt neu unter config
        } 
    else 
        { 
        echo "Modul EvaluateHardware ist NICHT installiert. Routinen werden uebersprungen.\n"; 
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
	/*                                      Install                                                                 */
	/*                                                                                                              */
	/****************************************************************************************************************/

	IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
	IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

	IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	
    $componentHandling=new ComponentHandling();
    $commentField="zuletzt Konfiguriert von DetectMovement EvaluateMotion um ".date("h:i am d.m.Y ").".";

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

	echo "***********************************************************************************************\n";
	echo "Bewegungsmelder und Contact Handler wird ausgeführt.\n";
	
	if (function_exists('HomematicList'))
		{
		echo "\n";
		echo "Homematic Bewegungsmelder werden registriert.\n";
		//$components=$componentHandling->getComponent(HomematicList(),"MOTION"); print_r($components);
		$componentHandling->installComponentFull(HomematicList(),"MOTION",'IPSComponentSensor_Motion','IPSModuleSensor_Motion',$commentField);
		//$components=$componentHandling->getComponent(HomematicList(),"TYPE_CONTACT"); print_r($components);		
		$componentHandling->installComponentFull(HomematicList(),"TYPE_CONTACT",'IPSComponentSensor_Motion','IPSModuleSensor_Motion',$commentField);
		} 
		
	echo "\n";
			
	if (function_exists('FS20List'))
		{
		echo "\n";
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
		echo "Remote Access installiert, zumindest die Gruppen Variablen für Bewegung/Motion auch auf den RemoteAccess VIS Server aufmachen.\n";
		echo "Für die Erzeugung der einzelnen Variablen am Remote Server rufen sie dazu die entsprechenden Remote Access Routinen auf (EvaluateXXXX) ! \n";
		IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$remServer=ROID_List();
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$ZusammenfassungID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Zusammenfassung");
			}
		if (isset($ZusammenfassungID)==true) print_r($ZusammenfassungID);	

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

    $componentHandling=new ComponentHandling();

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
			$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */

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
			$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */

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
		IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$remServer=ROID_List();
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$ZusammenfassungID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Zusammenfassung");
			}


		$groups=$DetectTemperatureHandler->ListGroups('Temperatur');
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
				$logTemp=new Temperature_Logging($oid);
				//print_r($logTemp);
				$class=$logTemp->GetComponent($oid);
				//echo "Letzte Variable hat OID :".$logTemp->variableLogID."\n"; /* EreignisID gibt es bei Temperatur nicht, anderen Wert holen und im selben Verzeichnis den Summenspeicher anlegen */
				$statusID=CreateVariable2("Gesamtauswertung_".$group,2,IPS_GetParent(intval($logTemp->variableLogID)),900,"~Temperature");
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
			$DetectHumidityHandler->RegisterEvent($oid,"Feuchtigkeit",'','');     /* par2, par3 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben */

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
		IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$remServer=ROID_List();
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$ZusammenfassungID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Zusammenfassung");
			}


		$groups=$DetectHumidityHandler->ListGroups('Feuchtigkeit');
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
				$logHum=new Feuchtigkeit_Logging($oid);
				//print_r($logHum);
				$class=$logHum->GetComponent($oid);
				//echo "Letzte Variable hat OID :".$logHum->variableLogID."\n"; /* EreignisID gibt es bei Temperatur nicht, anderen Wert holen und im selben Verzeichnis den Summenspeicher anlegen */
				$statusID=CreateVariable2("Gesamtauswertung_".$group,1,IPS_GetParent(intval($logHum->variableLogID)),900,"~Humidity");  /* 0 .. Boolean 1..Integer 2..Float 3..String */
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














?>