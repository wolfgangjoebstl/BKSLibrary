<?

 //Fügen Sie hier Ihren Skriptquellcode ein
$startexec=microtime(true);

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
//include(IPS_GetKernelDir()."scripts\_include\Logging.class.php");
//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

/******************************************************

				INIT

*************************************************************/

//$repository = 'https://10.0.1.6/user/repository/';
$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('DetectMovement',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; break; }
if (isset ($installedModules["EvaluateHardware"])) { echo "Modul EvaluateHardware ist installiert.\n"; } else { echo "Modul EvaluateHardware ist NICHT installiert.\n"; break;}

/*

jetzt wird für jeden Bewegungsmelder ein Event registriert. Das führt beim Message handler dazu das die class function handle event aufgerufen woird

Selbe Routine in RemoteAccess, allerdings wird dann auch auf einem Remote Server zusaetzlich geloggt


*/

IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");



/****************************************************************************************************************/
/*                                                                                                              */
/*                                      Install                                                                 */
/*                                                                                                              */
/****************************************************************************************************************/

$DetectMovementHandler = new DetectMovementHandler();

echo "Detect Movement wird ausgeführt.\n";
if (true)
	{
	/* nur die Detect Movement Funktion registrieren */
	
	/* Wenn Eintrag in Datenbank bereits besteht wird er nicht mehr geaendert */

	echo "Homematic Geräte werden registriert.\n";
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
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$DetectMovementHandler->RegisterEvent($oid,"Contact",'','par3');
			}
		}

	echo "FS20 Geräte werden registriert.\n";
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
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$DetectMovementHandler->RegisterEvent($oid,"Motion",'','par3');
			}
		}
		
		$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
		$modules=$moduleManager->GetInstalledModules();
		//print_r($result);

		if (isset ($modules["RemoteAccess"]))
  			{
			echo "Remote Access installiert, Variablen auch am VIS Server aufmachen.\n";
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
			   echo "Gruppe ".$group." behandeln.\n";
				$config=$DetectMovementHandler->ListEvents($group);
				$status=false;
				foreach ($config as $oid=>$params)
					{
					$status=$status || GetValue($oid);
					echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
					}
			   echo "Gruppe ".$group." hat neuen Status : ".(integer)$status."\n";
				$log=new Motion_Logging($oid);
				$class=$log->GetComponent($oid);
				$statusID=CreateVariable("Gesamtauswertung_".$group,1,IPS_GetParent(intval($log->EreignisID)));
				SetValue($statusID,(integer)$status);

				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, $ZusammenfassungID[$Name], "Gesamtauswertung_".$group, 0);
	   			$rpc->IPS_SetVariableCustomProfile($result,"Motion");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
			   $messageHandler = new IPSMessageHandler();
	   		$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
			   }
	  		}

$DetectTemperatureHandler = new DetectTemperatureHandler();

	echo "Temperatur wird ausgeführt.\n";
	echo "Homematic Geräte werden registriert.\n";

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
						echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
						}
					else
			   		{
						echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
						}
					$DetectTemperatureHandler->RegisterEvent($oid,"par1",'par2','par3');
					}
					
			/* remote Server aufsetzen fehlt noch ..... */
					
			}
	}




if ($_IPS['SENDER']=="Execute")
	{
			$Homematic = HomematicList();
			$FS20= FS20List();
		   $cuscompid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.core.IPSComponent');

		   $alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
			echo "===========================Alle Homematic Bewegungsmelder ausgeben.\n";
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Bewegungsmelder ausgeben */
				if ( (isset($Key["COID"]["MOTION"])==true) )
		   		{
		   		/* alle Bewegungsmelder */

			      $oid=(integer)$Key["COID"]["MOTION"]["OID"];
					$log=new Motion_Logging($oid);
					$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
					}
				if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
	   			{
			   	/* alle Kontakte */
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
			echo $alleMotionWerte;
			
			/* Detect Movement Auswertungen analysieren */
			
			/* Routine in Log_Motion uebernehmen */
			IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
			IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
		   $DetectMovementHandler = new DetectMovementHandler();
			print_r($DetectMovementHandler->ListEvents("Motion"));
			print_r($DetectMovementHandler->ListEvents("Contact"));

			$groups=$DetectMovementHandler->ListGroups();
			foreach($groups as $group=>$name)
			   {
			   echo "Gruppe ".$group." behandeln.\n";
				$config=$DetectMovementHandler->ListEvents($group);
				$status=false;
				foreach ($config as $oid=>$params)
					{
					$status=$status || GetValue($oid);
					echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
					}
			   echo "Gruppe ".$group." hat neuen Status : ".(integer)$status."\n";
				$log=new Motion_Logging($oid);
				$class=$log->GetComponent($oid);
				$statusID=CreateVariable("Gesamtauswertung_".$group,1,IPS_GetParent(intval($log->EreignisID)));
				SetValue($statusID,(integer)$status);
			   }

			
			$config=IPSDetectMovementHandler_GetEventConfiguration();

			foreach ($config as $oid=>$params)
				{
				echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)." Type :".str_pad($params[0],15)."Status: ".(integer)GetValue($oid)." Gruppe ".$params[1]."\n";
				$log=new Motion_Logging($oid);
				$class=$log->GetComponent($oid);
				//print_r($class);
				echo "ParentID:".IPS_GetParent(intval($log->EreignisID))." Name :","Gesamtauswertung_".$params[1]."\n";
				$erID=CreateVariable("Gesamtauswertung_".$params[1],1,IPS_GetParent(intval($log->EreignisID)));
				}
			
			
	}

?>
