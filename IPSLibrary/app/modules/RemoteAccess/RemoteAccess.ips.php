<?

/*


baut die Struktur für die Schalter auf


*/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 500);
$startexec=microtime(true);

	/******************** EVALUATION *******************/

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
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
			IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
			$MeterConfig = get_MeterConfiguration();

			$includefile.="\n".'function AmisStromverbrauchList() { return array('."\n";
         $amisdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
			echo "Amis Stromverbrauch Data auf :".$amisdataID."\n";

			$count_phone=100;
			$count_var=500;
			foreach ($MeterConfig as $meter)
				{
				echo "Meter :".$meter["NAME"]."\n";
				//print_r($meter);

	      	$meterdataID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
   	      /* ID von Wirkenergie bestimmen */
				if ($meter["TYPE"]=="Amis")
				   {
					$AmisID = CreateVariableByName($meterdataID, "AMIS", 3);
					$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
					$energieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
					$leistungID = IPS_GetObjectIDByName ( 'Wirkleistung' , $zaehlerid );
			   	}
				if ($meter["TYPE"]=="Homematic")
			   	{
					$energieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $meterdataID);
					$leistungID = IPS_GetObjectIDByName ( 'Wirkleistung' , $meterdataID);
			   	}
				add_variablewithname($energieID,$meter["NAME"]."_Wirkenergie",$includefile,$count_phone);
				add_variablewithname($leistungID,$meter["NAME"]."_Wirkleistung",$includefile,$count_phone);
			   }
			$includefile.="\n      ".');}'."\n";
			}


echo "Ende Evaluierung : ".(microtime(true)-$startexec)." Sekunden \n";

   
/******************************************************

				INIT

*************************************************************/

$includefile.="\n".'function ROID_List() { return array('."\n";
$remServer=RemoteAccess_GetConfiguration();
foreach ($remServer as $Name => $Server)
	{
	$rpc = new JSONRPC($Server);
	$includefile.='"'.$Name.'" => array('."\n         ".'"Adresse" => "'.$Server.'", ';
	
	$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
	$visname=IPS_GetName(0);
	echo "Server : ".$Name."  ".$Server." OID = ".$visrootID." fuer Server ".$visname." \n";
	$includefile.="\n         ".'"VisRootID" => "'.$visrootID.'", ';
	
	$wfID=RPC_CreateCategoryByName($rpc, $visrootID, "WebFront");
	$includefile.="\n         ".'"WebFront" => "'.$wfID.'", ';

	$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
	$includefile.="\n         ".'"Administrator" => "'.$webID.'", ';
	
	$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
	$includefile.="\n         ".'"RemoteAccess" => "'.$raID.'", ';
		
	$servID=RPC_CreateCategoryByName($rpc, $raID,$visname);
	$includefile.="\n         ".'"ServerName" => "'.$servID.'", ';
	
	$tempID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Temperatur");
	$includefile.="\n         ".'"Temperatur" => "'.$tempID[$Name].'", ';
	
	$switchID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Schalter");
	$includefile.="\n         ".'"Schalter" => "'.$switchID[$Name].'", ';
	
	$contactID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Kontakte");
	$includefile.="\n         ".'"Kontakte" => "'.$contactID[$Name].'", ';
	
	$motionID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Bewegungsmelder");
	$includefile.="\n         ".'"Bewegung" => "'.$motionID[$Name].'", ';
	
	$humiID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Feuchtigkeit");
	$includefile.="\n         ".'"Humidity" => "'.$humiID[$Name].'", ';

	$andereID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Andere");
	$includefile.="\n         ".'"Andere" => "'.$andereID[$Name].'", ';
	
	echo "Remote VIS-ID                    ".$visrootID,"\n";
	echo "Remote WebFront-ID               ".$wfID,"\n";
	echo "Remote Administrator-ID          ".$webID,"\n";
	echo "RemoteAccess-ID                  ".$raID,"\n";
	echo "RemoteServer-ID                  ".$servID,"\n";
	echo "Ende Server : ".(microtime(true)-$startexec)." Sekunden \n";

	$RPCHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$RPCarchiveHandlerID[$Name] = $RPCHandlerID[0];
	$includefile.="\n         ".'"ArchiveHandler" => "'.$RPCarchiveHandlerID[$Name].'", ';
	$includefile.="\n             ".'	),'."\n";
	
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
	}

echo "\nOID          ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($Name,10);
	}
echo "\nTemperature  ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($tempID[$Name],10);
	}
echo "\nSwitch       ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($switchID[$Name],10);
	}
echo "\nKontakt      ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($contactID[$Name],10);
	}
echo "\nBewegung     ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($motionID[$Name],10);
	}
echo "\nFeuchtigkeit ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($humiID[$Name],10);
	}
echo "\n\n";

		$includefile.="      ".');}'."\n";
		$includefile.="\n".'?>';
		$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\RemoteAccess\EvaluateVariables.inc.php';
		if (!file_put_contents($filename, $includefile))
			{
        	throw new Exception('Create File '.$filename.' failed!');
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

/******************************************************************/

function add_variablewithname($variableID,$name,&$includefile,&$count)
	{
	$includefile.='"'.$name.'" => array('."\n         ".'"OID" => '.$variableID.', ';
	$includefile.="\n         ".'"Name" => "'.$name.'", ';
	$variabletyp=IPS_GetVariable($variableID);
	print_r($variabletyp);
	//echo "Typ:".$variabletyp["VariableValue"]["ValueType"]."\n";
	$includefile.="\n         ".'"Typ"      => '.$variabletyp["VariableValue"]["ValueType"].', ';
	$includefile.="\n         ".'"Profile"  => "'.$variabletyp["VariableCustomProfile"].'", ';
	$includefile.="\n         ".'"Order"    => "'.$count++.'", ';
	$includefile.="\n             ".'	),'."\n";
	}




?>
