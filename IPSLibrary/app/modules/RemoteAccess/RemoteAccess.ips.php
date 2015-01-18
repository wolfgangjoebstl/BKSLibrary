<?

/*


baut die Struktur für die Schalter auf


*/

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
	$contactID=RPC_CreateCategoryByName($rpc, $raID, "Kontakte");
	$motionID=RPC_CreateCategoryByName($rpc, $raID, "Bewegungsmelder");
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


	/******************** EVALUATION *******************/

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
	}
	$gartensteuerung=false;
	$guthabensteuerung=false;
	$amis=false;

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
		   case "Amis":
		   	$amis=true;
		   	break;
		   }
		}
	echo $inst_modules."\n\n";

	if ($guthabensteuerung) {echo "Guthabensteuerung installiert und erkannt\n";}
	if ($gartensteuerung) {echo "Gartensteuerung installiert und erkannt\n";}
	if ($amis) {echo "AMIS Stromverbrauchsmessung installiert und erkannt\n";}

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

		if ($amis)
			{
			$includefile.='function AmisStromverbrauchList() { return array('."\n";
         $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
			echo "Amis Stromverbrauch Data auf :".$parentid."\n";
			$result=IPS_GetChildrenIDs($parentid);
			//print_r($result);
			/*
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
			*/
			$includefile.="\n      ".');}'."\n";
			}


		$includefile.="\n".'?>';
		$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\RemoteAccess\EvaluateVariables.inc.php';
		if (!file_put_contents($filename, $includefile))
			{
        	throw new Exception('Create File '.$filename.' failed!');
    		}

	/************* PROFILES *******************/

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

	$pname="Switch";
	if ($rpc->IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Aus","",0xffffff);
 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Ein","",0xffffff);


	   //print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="Contact";
	if ($rpc->IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Zu","",0xffffff);
 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Offen","",0xffffff);


	   //print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }


	$pname="Motion";
	if ($rpc->IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Ruhe","",0xffffff); 
 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Bewegung","",0xffffff); 


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
	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
	
	$Homematic = HomematicList();


	/******************************************** Schalter  *****************************************/

	foreach ($Homematic as $Key)
		{
		/* alle Schalterzustände ausgeben */
		if ( isset($Key["COID"]["STATE"]) and isset($Key["COID"]["INHIBIT"]) and (isset($Key["COID"]["ERROR"])==false) )
	   		{
	      	$oid=(integer)$Key["COID"]["STATE"]["OID"];
  	      	$variabletyp=IPS_GetVariable($oid);
				if ($variabletyp["VariableProfile"]!="")
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}
				else
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}
				$result=RPC_CreateVariableByName($rpc, $switchID, $Key["Name"], 0);
	   		$rpc->IPS_SetVariableCustomProfile($result,"Switch");
				$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
				$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,0);
				$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);				//print_r($result);
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSwitch_Remote,'.$result.',626','IPSModuleSwitch_IPSLight,1,2,3');
				}
		}


	/******************************************* Kontakte ***********************************************/

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
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$result=RPC_CreateVariableByName($rpc, $contactID, $Key["Name"], 0);
   		$rpc->IPS_SetVariableCustomProfile($result,"Contact");
			$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,0);
			$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
			//print_r($result);
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion,'.$result,'IPSModuleSensor_Motion');
			}
		}


	/******************************************* Bewegungsmelder ***********************************************/

   //RPC_CreateVariableField($rpc, $motionID, $Homematic, "MOTION", "Temperatur",$RPCarchiveHandlerID);  /* rpc, remote OID of category, OID Liste, OID Typ daraus, zuzuordnendes Profil, RPC ArchiveHandler */

	
	$keyword="MOTION";
	foreach ($Homematic as $Key)
		{
		if ( (isset($Key["COID"][$keyword])==true) )
	   	{
	   	/* alle Bewegungsmelder */

	      $oid=(integer)$Key["COID"][$keyword]["OID"];
      	$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$result=RPC_CreateVariableByName($rpc, $motionID, $Key["Name"], 0);
   		$rpc->IPS_SetVariableCustomProfile($result,"Motion");
			$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,0);
			$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
			
			//print_r($result);
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion'.$result,'IPSModuleSensor_Motion');
			}
		}

	
	
	
	
	
	
	
	
	


	

	

/******************************************************************/

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
