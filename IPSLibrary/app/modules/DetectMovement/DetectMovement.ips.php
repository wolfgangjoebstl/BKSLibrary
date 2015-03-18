<?

 //Fügen Sie hier Ihren Skriptquellcode ein

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
//include(IPS_GetKernelDir()."scripts\_include\Logging.class.php");
//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
IPSUtils_Include ("DetectMovement_Configuration.inc.php","IPSLibrary::config::modules::DetectMovement");

/******************************************************

				INIT

*************************************************************/

//$repository = 'https://10.0.1.6/user/repository/';
$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('DetectMovement',$repository);
}
$gartensteuerung=false;
$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,20)." ".$modules."\n";
	}
echo $inst_modules."\n\n";


/*

jetzt wird für jeden Bewegungsmelder ein Event registriert. Das führt beim Message handler dazu das die class function handle event aufgerufen woird

Selbe Routine in RemoteAccess, allerdings wird dann auch auf einem Remote Server zusaetzlich geloggt


*/

	IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
   
	IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
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
			//$result=RPC_CreateVariableByName($rpc, $switchID, $Key["Name"], 0);
			//print_r($result);
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion');
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
	}

?>
