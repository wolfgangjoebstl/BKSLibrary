<?

 //Fügen Sie hier Ihren Skriptquellcode ein

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

$remServer=RemoteAccess_GetConfiguration();
//print_r($configuration);

foreach ($remServer as $Server)
	{
	$rpc = new JSONRPC($Server);
	}

	/* nimmt vorerst immer die zweite Adresse */

	$result=RPC_CreateCategoryByName($rpc, 0,"Visualization");
	echo "OID = ".$result." \n";

	$visID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
	$wfID=RPC_CreateCategoryByName($rpc, $visID, "WebFront");
	$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
	$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
	$tempID=RPC_CreateCategoryByName($rpc, $raID, "Temperatur");
	$switchID=RPC_CreateCategoryByName($rpc, $raID, "Schalter");
	$humiID=RPC_CreateCategoryByName($rpc, $raID, "Feuchtigkeit");
	echo "Remote VIS-ID                    ".$visID,"\n";
	echo "Remote WebFront-ID               ".$wfID,"\n";
	echo "Remote Administrator-ID          ".$webID,"\n";
	echo "RemoteAccess-ID                  ".$raID,"\n";
	echo "Remote Temperatur Cat-ID         ".$tempID,"\n";
	echo "Remote Switch Cat-ID             ".$switchID,"\n";
	echo "Remote Feuchtigkeit Cat-ID       ".$humiID,"\n";

	$RPCarchiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$RPCarchiveHandlerID = $RPCarchiveHandlerID[0];

	
	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
	}
	$gartensteuerung=false;
	$guthabensteuerung=false;
	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		switch ($name)
		   {
		   case "Guthabensteuerung":
		   	$guthabensteuerung=true;
		   	break;
		   }
		}
	echo $inst_modules."\n\n";
	if ($guthabensteuerung) {echo "Guthabensteuerung installiert und erkannt\n";}

	if ($_IPS['SENDER']=="Execute")
		{
	
		/* macht einmal die Installation, später rueberkopieren, Routine dann eigentlich unnötig */
	
	$pname="Temperatur";
	if ($rpc->IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		$rpc->IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		$rpc->IPS_SetVariableProfileText($pname,'',' °C');
	   //print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="Humidity";
	if ($rpc->IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		$rpc->IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
  		$rpc->IPS_SetVariableProfileText($pname,'',' %');
	   //print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }
	   
	/***************** INSTALLATION **************/

	echo "Update Konfiguration und register Events\n";
	
	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

	$Homematic = HomematicList();

	foreach ($Homematic as $Key)
		{
		/* alle Temperaturwerte ausgeben */
		if (isset($Key["COID"]["TEMPERATURE"])==true)
	   	{
	      $oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			$result=RPC_CreateVariableByName($rpc, $tempID, $Key["Name"], 2);
			$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
			$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
			$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Temperatur,'.$result.',626','IPSModuleSensor_Temperatur,1,2,3');
			}
		}

   RPC_CreateVariableField($rpc, $humiID, $Homematic, "HUMIDITY", "Humidity",$RPCarchiveHandlerID);  /* rpc, remote OID of category, OID Liste, OID Typ daraus, zuzuordnendes Profil, RPC ArchiveHandler */

	foreach ($Homematic as $Key)
		{
		/* alle Schalterzustände ausgeben */
		if (isset($Key["COID"]["STATE"])==true)
	   		{
	      	$oid=(integer)$Key["COID"]["STATE"]["OID"];
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				$result=RPC_CreateVariableByName($rpc, $switchID, $Key["Name"], 0);
			   $messageHandler = new IPSMessageHandler();
		   	$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSwitch_RHomematic,'.$result.',626','IPSModuleSwitch_IPSLight,1,2,3');
				}
		}
	}
	

/******************************************************************/

function RPC_CreateVariableByName($rpc, $id, $name, $type)
	{

	/* type steht für 0 Boolean 1 Integer 2 Float 3 String */

	$result="";
	$struktur=$rpc->IPS_GetChildrenIDs($id);
	foreach ($struktur as $category)
	   {
	   $oname=$rpc->IPS_GetName($category);
	   //echo str_pad($oname,20)." ".$category."\n";
	   if ($name==$oname) {$result=$name;$vid=$category;}
	   }
	if ($result=="")
	   {
      $vid = $rpc->IPS_CreateVariable($type);
      $rpc->IPS_SetParent($vid, $id);
      $rpc->IPS_SetName($vid, $name);
      $rpc->IPS_SetInfo($vid, "this variable was created by script. ");
      }
    return $vid;
	}


function RPC_CreateCategoryByName($rpc, $id, $name)
	{

	/* erzeugt eine Category am Remote Server */

	$result="";
	$struktur=$rpc->IPS_GetChildrenIDs($id);
	foreach ($struktur as $category)
	   {
	   $oname=$rpc->IPS_GetName($category);
	   //echo str_pad($oname,20)." ".$category."\n";
	   if ($name==$oname) {$result=$name;$vid=$category;}
	   }
	if ($result=="")
	   {
      $vid = $rpc->IPS_CreateCategory();
      $rpc->IPS_SetParent($vid, $id);
      $rpc->IPS_SetName($vid, $name);
      $rpc->IPS_SetInfo($vid, "this category was created by script. ");
      }
    return $vid;
	}


function RPC_CreateVariableField($rpc, $roid, $Homematic, $keyword, $profile, $RPCarchiveHandlerID)
	{
	
	foreach ($Homematic as $Key)
		{
		/* alle Feuchtigkeitswerte ausgeben */
		if (isset($Key["COID"][$keyword])==true)
	   	{
	      $oid=(integer)$Key["COID"][$keyword]["OID"];
			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			$result=RPC_CreateVariableByName($rpc, $roid, $Key["Name"], 2);

			$rpc->IPS_SetVariableCustomProfile($result,$profile);
			$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
			$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);

		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Temperatur,'.$result.',626','IPSModuleSensor_Temperatur,1,2,3');
			}
		}
	
	}



?>
