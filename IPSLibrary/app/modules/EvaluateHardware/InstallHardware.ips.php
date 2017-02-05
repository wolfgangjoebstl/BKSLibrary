<?

/* installiert die Hardware auf einem neuen System basierend auf einem includefile dass auf einem anderen PC erstellt wurde
 *
 */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	include_once 'c:\\scripts\\EvaluateHardware_Include.inc.php';

	/**********************************************************************************************
	 * set up configuration for installation
	 *
	 */
	 
	$installHI		= "Homematic-CCU";	/* Homematic Instanz die installiert werden soll */
	$ownIPaddress	= "10.0.0.124";

	/**********************************************************************************************
	 * set up Homematic Socket
	 *
	 */

	$ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
	$HomInstanz=sizeof($ids);
	$config=array();
	if($HomInstanz == 0)
	   {
   	echo "ERROR: Keine HomeMatic Socket Instanz gefunden!\n";
	   }
	else
		{	
		for ($i=0;$i < $HomInstanz; $i++)
			{
			$ccu_name=IPS_GetName($ids[$i]);
			echo "\nHomatic Socket ID ".$ids[$i]." / ".$ccu_name."   \n";
			$config[$ccu_name]["Name"]=$ccu_name;
			$config[$ccu_name]["OID"]=$ids[$i];
			$config[$ccu_name]["Config"]=json_decode(IPS_GetConfiguration($ids[$i]));
			}
		}
	print_r($config);
	$HomematicInstanzen=HomematicInstanzen();
	if ( isset($HomematicInstanzen[$installHI]) == true )
		{
		if ( isset($config[$installHI]) == false )
			{
			$InsID = IPS_CreateInstance("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
			IPS_SetName($InsID, $installHI); // Instanz benennen
			}
		else
			{
			$InsID = $config[$installHI]["OID"];
			}
		$configHI=json_decode($HomematicInstanzen[$installHI]["CONFIG"]);
		$configHI->IPAddress=$ownIPaddress;
		IPS_SetConfiguration($InsID, json_encode($configHI));
		IPS_ApplyChanges($InsID);
		}

	/**********************************************************************************************
	 * evaluate Homematic Devices allready installed
	 *
	 */

	//Homematic Sender
	$guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);

	echo "\nBereits installierte Homematic Geräte: ".sizeof($alleInstanzen)."\n\n";
	$serienNummer=array();
	$SerienNummerListe=array();
	
	foreach ($alleInstanzen as $instanz)
		{
		$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
		echo "   ".IPS_GetConfiguration($instanz)."   ".$HM_CCU_Name."\n";
		$HM_Adresse=IPS_GetProperty($instanz,'Address');
		$result=explode(":",$HM_Adresse);
		//print_r($result);
		echo str_pad(IPS_GetName($instanz),40)." ".$instanz." ".$HM_Adresse." ".str_pad(IPS_GetProperty($instanz,'Protocol'),3)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name."\n";
		$SerienNummerListe[]=$HM_Adresse;
		if (isset($serienNummer[$HM_CCU_Name][$result[0]]))
	   	{
		   $serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]+=1;
		   }
		else
			{
		   $serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]=1;
		   $serienNummer[$HM_CCU_Name][$result[0]]["Values"]="";
	   	}
		$cids = IPS_GetChildrenIDs($instanz);
		//print_r($cids);
   	foreach($cids as $cid)
    		{
	      $o = IPS_GetObject($cid);
   	   //echo "\nCID :".$cid;
      	//print_r($o);
	      if ( $o['ObjectIdent'] != "" )
				{
	         $serienNummer[$HM_CCU_Name][$result[0]]["Values"].=$o['ObjectIdent']." ";
   	     	}
    		}
		}

	//print_r($SerienNummerListe);

	/**********************************************************************************************
	 * install new Homematic Devices
	 *
	 */

	$Homematic = HomematicList();
	//print_r($Homematic);

	$Hardware_ID    = CreateCategory('Hardware',   0, 0);
	$Homematic_ID   = CreateCategory('Homematic',  $Hardware_ID, 0);

	$count=0;
	foreach ($Homematic as $name => $Key)
		{
		/* wurde die Instanz bereits gefunden ? durch die vorher generierte Liste gehen */
		$found=false;
		foreach ($SerienNummerListe as $SerienNummer)
		   {
		   if ( $SerienNummer == $Key['Adresse'] )
		      {
				//echo $SerienNummer." ".$Key['Adresse']." GEFUNDEN\n";
		      $found=true;
				}
			}
		
		//echo $name." ".$Key['Adresse']."\n";
		$Test_ID = @IPS_GetInstanceIDByName($name, $Homematic_ID);
		//If ( $found == false )
		   {
			if ($Test_ID == false)
				{
				$Test_ID = IPS_CreateInstance("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
				IPS_SetParent($Test_ID, $Homematic_ID);
				IPS_SetName($Test_ID, $name);
				IPS_SetInfo($Test_ID, "this Object was created by Script InstallHomematic");
		   	IPS_SetProperty($Test_ID,"Address",$Key['Adresse']);
				IPS_ConnectInstance($Test_ID,$config[$installHI]["OID"]); 					
				IPS_ApplyChanges($Test_ID);
				echo "Homematic-Instanz Main Zone #$Test_ID in Kategorie Homematic angelegt\n";
				}
			else
			   {
			   IPS_SetPosition($Test_ID,$count);
		   	IPS_SetProperty($Test_ID,"Address",$Key['Adresse']);
				IPS_ConnectInstance($Test_ID,$config[$installHI]["OID"]); 					
			   if (IPS_HasChanges($Test_ID))
					{
					$status=@IPS_ApplyChanges($Test_ID);
		   		//echo "ID : ".$Test_ID." ".$status."\n";
					}
			   }
			} // Ende if found
		$count++;
		if ( $found )
		   {
			echo "** ".str_pad($name,30)." Konfiguration : ".IPS_GetConfiguration($Test_ID)." Property : ".IPS_GetProperty($Test_ID,"Address")." ".$Test_ID."\n";
			}
		else
		   {
			echo "   ".str_pad($name,30)." Konfiguration : ".IPS_GetConfiguration($Test_ID)." Property : ".IPS_GetProperty($Test_ID,"Address")." ".$Test_ID."\n";
		   }
		//print_r( );
		}


?>