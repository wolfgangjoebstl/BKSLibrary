<?

 //Fügen Sie hier Ihren Skriptquellcode ein
$startexec=microtime(true);

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

/******************************************************

				INIT

*************************************************************/

$startexec=microtime(true);

//$repository = 'https://10.0.1.6/user/repository/';
$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('DetectMovement',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();

if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; break; }
if (isset ($installedModules["EvaluateHardware"])) { echo "Modul EvaluateHardware ist installiert.\n"; } else { echo "Modul EvaluateHardware ist NICHT installiert.\n"; break;}
//if (isset ($installedModules["RemoteReadWrite"])) { echo "Modul RemoteReadWrite ist installiert.\n"; } else { echo "Modul RemoteReadWrite ist NICHT installiert.\n"; break;}
if (isset ($installedModules["RemoteAccess"])) { echo "Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; break;}

/*

jetzt wird für jeden Bewegungsmelder ein Event registriert. Das führt beim Message handler dazu das die class function handle event aufgerufen woird

Selbe Routine in RemoteAccess, allerdings wird dann auch auf einem Remote Server zusaetzlich geloggt


*/

IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");



/****************************************************************************************************************/
/*                                                                                                              */
/*                                      Install                                                                 */
/*                                                                                                              */
/****************************************************************************************************************/




/****************************************************************************************************************/
/*                                                                                                              */
/*                                      Execute                                                                 */
/*                                                                                                              */
/****************************************************************************************************************/

	if ($_IPS['SENDER']=="Execute")
		{
		$Homematic = HomematicList();
		//print_r($Homematic);
		$FS20= FS20List();
		$cuscompid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.core.IPSComponent');

		$alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
		echo "\n";
		echo "Execute von Detect Movement, zusaetzliche Auswertungen.\n\n";

		echo "===========================Alle Homematic Bewegungsmelder ausgeben.\n";
		foreach ($Homematic as $Name => $Key)
			{
			/* Alle Homematic Bewegungsmelder ausgeben */
			if ( (isset($Key["COID"]["MOTION"])==true) )
		   		{
		   		/* alle Bewegungsmelder */
				echo "*******\nBearbeite Bewegungsmelder ".$Name."\n";
			    $oid=(integer)$Key["COID"]["MOTION"]["OID"];
				$log=new Motion_Logging($oid);
				$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
	   			{
			   	/* alle Kontakte */
				echo "*******\nBearbeite Kontakt ".$Name."\n";
			    $oid=(integer)$Key["COID"]["STATE"]["OID"];
				$log=new Motion_Logging($oid);
				$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			}
		echo "===========================Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein.\n";
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$TypeFS20=RemoteAccess_TypeFS20();
		foreach ($FS20 as $Key)
			{
			/* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
			if ( (isset($Key["COID"]["MOTION"])==true) )
				{
		   		/* alle Bewegungsmelder */
				echo "*******\nBearbeite FS20 Bewegungsmelder ".$Name."\n";
			    $oid=(integer)$Key["COID"]["MOTION"]["OID"];
				$log=new Motion_Logging($oid);
				$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			/* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei verknüpfen */
			if ((isset($Key["COID"]["StatusVariable"])==true))
			   	{
		   		foreach ($TypeFS20 as $Type)
		   			{
		   		   	if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
			   	    	{
						echo "*******\nBearbeite FS20 Bewegungsmelder ".$Name."\n";						
	      				$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
			  	      	$variabletyp=IPS_GetVariable($oid);
			  	      	IPS_SetName($oid,"MOTION");
						$log=new Motion_Logging($oid);
						$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
		   		      	}
		   	   		}
				}
			}

			$alleMotionWerte.="********* Gesamtdarstellung\n".$log->writeEvents(true,true)."\n\n";
			echo "\n\n======================================================================================\n";
			echo $alleMotionWerte;
			echo "\n\n======================================================================================\n";
						
			/* Detect Movement Auswertungen analysieren */
			
			/* Routine in Log_Motion uebernehmen */
			IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
			IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
			$DetectMovementHandler = new DetectMovementHandler();
			echo "Ausgabe aller Event IDs mit zugeordneter Gruppe deren erster Parameter Motion ist:\n";
			print_r($DetectMovementHandler->ListEvents("Motion"));
			echo "Ausgabe aller Event IDs mit zugeordneter Gruppe deren erster Parameter Contact ist:\n";
			print_r($DetectMovementHandler->ListEvents("Contact"));

			echo "\n==================================================================\n";
			$groups=$DetectMovementHandler->ListGroups();
			foreach($groups as $group=>$name)
				{
				echo "*****\nDetect Movement Gruppe ".$group." behandeln.\n";
				$config=$DetectMovementHandler->ListEvents($group);
				$status=false;
				foreach ($config as $oid=>$params)
					{
					$status=$status || GetValue($oid);
					echo "   OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
					}
				echo "Gruppe ".$group." hat neuen Status : ".(integer)$status."\n";
				}

			echo "****\nDetect Movement Konfiguration durchgehen:\n";
			$config=IPSDetectMovementHandler_GetEventConfiguration();
			$gesamt=array();
			foreach ($config as $oid=>$params)
				{
				echo "  OID: ".$oid." Name: ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),50)." Type :".str_pad($params[0],15)."Status: ".(integer)GetValue($oid)." Gruppe ".$params[1]."\n";
				$gesamt["Gesamtauswertung_".$params[1]]["NAME"]="Gesamtauswertung_".$params[1];
				$gesamt["Gesamtauswertung_".$params[1]]["OID"]=@IPS_GetObjectIDByName("Gesamtauswertung_".$params[1],$DetectMovementHandler->getCustomComponentsDataGroup());
				$gesamt["Gesamtauswertung_".$params[1]]["MOID"]=@IPS_GetObjectIDByName("Gesamtauswertung_".$params[1],$DetectMovementHandler->getDetectMovementDataGroup());
				}

			$LogConfiguration=get_IPSComponentLoggerConfig();
			$delayTime=$LogConfiguration["LogConfigs"]["DelayMotion"]/60;
			echo "Delay zum Glätten sind ".($delayTime)." Minuten.\n\n";
				
			echo "****\nZusammenfassung:\n";	
			$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];			
			foreach ($gesamt as $entry)
				{
				echo "\n    ".$entry["NAME"]."   ".$entry["OID"]." (".IPS_GetName($entry["OID"])."/".IPS_GetName(IPS_GetParent($entry["OID"])).")   ".$entry["MOID"]." (".IPS_GetName($entry["MOID"])."/".IPS_GetName(IPS_GetParent($entry["MOID"])).")\n";
				$endtime=time();
				$starttime=$endtime-60*60*24*10;
				echo "       Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime)." für : ".$entry["OID"]." Aktuell : ".(GetValue($entry["OID"])?"Ein":"Aus")."\n";
				$werte = AC_GetLoggedValues($archiveHandlerID, $entry["OID"], $starttime, $endtime, 0);
				$zeile=0; $zeilemax=6;
				foreach($werte as $wert)
					{
					echo "           ".date("D d.m H:i", $wert['TimeStamp'])."   ".($wert['Value']?"Ein":"Aus")."    ".$wert['Duration']."\n";
					$zeile++;
					if ($zeile>($zeilemax*2)) break;
					}
				echo "       Zeitreihe von ".date("D d.m H:i",$starttime)." bis ".date("D d.m H:i",$endtime)." für : ".$entry["MOID"]." Aktuell : ".(GetValue($entry["OID"])?"Ein":"Aus")."   Geglättet mit ".$delayTime." Minuten.\n";
				$werte = AC_GetLoggedValues($archiveHandlerID, $entry["MOID"], $starttime, $endtime, 0);
				$zeile=0;
				foreach($werte as $wert)
					{
					echo "           ".date("D d.m H:i", $wert['TimeStamp'])."   ".($wert['Value']?"Ein":"Aus")."    ".$wert['Duration']."\n";
					$zeile++;
					if ($zeile>$zeilemax) break;
					}
					
				}	

			echo "Was ist mit den Gesamtauswertungen_ im CustomComponents verzeichnis.\n";



			echo "\n";
			echo "Execute von Detect Movement, zusaetzliche Auswertungen fuer Temperatur.\n\n";
			echo "===========================Alle Homematic Temperaturmelder ausgeben.\n";
			
			$alleTempWerte="\n\nHistorische Temperaturwerte aus den Logs der CustomComponents:\n\n";
			
			foreach ($Homematic as $Key)
				{
				/* alle Feuchtigkeits oder Temperaturwerte ausgeben */
				if (isset($Key["COID"]["TEMPERATURE"])==true)
	   			{
	 				$oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
					$log=new Temperature_Logging($oid);
					$alleTempWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
					}
				}
			$alleTempWerte.="********* Gesamtdarstellung\n".$log->writeEvents(true,true)."\n\n";
			echo $alleTempWerte;


			
			
	}  // Ende if execute

?>