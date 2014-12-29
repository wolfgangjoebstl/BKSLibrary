<?

 //Fügen Sie hier Ihren Skriptquellcode ein

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

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
		   case "Gartensteuerung":
		   	$gartensteuerung=true;
		   	break;
		   }
		}
	echo $inst_modules."\n\n";

	if ($guthabensteuerung) {echo "Guthabensteuerung installiert und erkannt\n";}
	if ($gartensteuerung) {echo "Gartensteuerung installiert und erkannt\n";}

	if ($_IPS['SENDER']=="Execute")
		{
		
		$includefile='<?'."\n";

		if ($guthabensteuerung)
			{
			$includefile.='function GuthabensteuerungList() { return array('."\n";
         $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
			echo "Guthabensteuerung Data auf :".$parentid."\n";
			$result=IPS_GetChildrenIDs($parentid);
			//print_r($result);
			$count_phone=100;
			$count_var=500;
			foreach ($result as $variableID)
			   {
		   	//$includefile.='     "'.str_pad(IPS_GetName($variableID),30).'" => '.$variableID.', '."\n";

			   $children=IPS_HasChildren($variableID);
			   echo "Variable ".IPS_GetName($variableID)."  ".$children."\n";
				if ($children)
				   {
				   add_variable($variableID,$includefile,$count_phone);
				   $volumeID=IPS_GetVariableIDByName(IPS_GetName($variableID)."_Volume",$variableID);
				   add_variable($volumeID,$includefile,$count_phone);
				   echo"  VolumeID :".$volumeID."\n";
			      }
			   else
			      {
				   add_variable($variableID,$includefile,$count_var);
					}
			   }
			//$includefile.="\n      ".'	),'."\n";
			$includefile.="\n      ".');}'."\n";
			}

		$includefile.="\n".'?>';
		$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\RemoteAccess\EvaluateVariables.inc.php';
		if (!file_put_contents($filename, $includefile)) {
        	throw new Exception('Create File '.$filename.' failed!');
    		}
	
		}
	

	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");

	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
	$remServer=RemoteAccess_GetConfiguration();

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
	$guthID=RPC_CreateCategoryByName($rpc, $raID, "Guthaben");

	/* RPC braucht elendslang in der Verarbeitung, bis hierher 10 Sekunden !!!! */

	//IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

	$Guthabensteuerung=GuthabensteuerungList();
	
	foreach ($Guthabensteuerung as $Key)
		{
	      $oid=(integer)$Key["OID"];
      	$variabletyp=IPS_GetVariable($oid);
			//print_r($variabletyp);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$result=RPC_CreateVariableByName($rpc, $guthID, $Key["Name"], $Key["Typ"]);
			//$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
			//$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			//$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
			//$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$result,'IPSModuleSensor_Remote');
	
		}


/******************************************************************/

function add_variable($variableID,&$includefile,&$count)
	{
	$includefile.='"'.IPS_GetName($variableID).'" => array('."\n         ".'"OID" => '.$variableID.', ';
	$includefile.="\n         ".'"Name" => "'.IPS_GetName($variableID).'", ';
	$variabletyp=IPS_GetVariable($variableID);
	//print_r($variabletyp);
	//echo "Typ:".$variabletyp["VariableValue"]["ValueType"]."\n";
	$includefile.="\n         ".'"Typ" => '.$variabletyp["VariableValue"]["ValueType"].', ';
	$includefile.="\n         ".'"Order" => "'.$count++.'", ';
	$includefile.="\n             ".'	),'."\n";
	}

?>
