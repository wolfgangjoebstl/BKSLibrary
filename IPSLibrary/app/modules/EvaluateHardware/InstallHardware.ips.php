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
	 
	$installHI		= "Homematic-CCU";		/* Homematic Instanz die installiert werden soll */
	
	$ownIPaddress	= "10.0.0.124";
	$debug=false;							/* wenn true, nicht wirklich Veränderungen vornehmen */
	$analyze=false;						/* redundanten nur zur Analyse gedachten Output reduzieren */
	
	$setupHomematicSocket=false;			/* wenn true werden auch die Homematic Sockets aufgesetzt */
	$setupHomematic=true;					/* wenn true werden auch die Homematic Geraete aufgesetzt */
	$setupFHT=false;					/* wenn true werden auch die FHT Geraete aufgesetzt */
	$setupFS20=false;					/* wenn true werden auch die FHT Geraete aufgesetzt */
	
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
		echo "\n";	
		}
	if ($analyze) print_r($config);
	$HomematicInstanzen=HomematicInstanzen();
	if ( (isset($HomematicInstanzen[$installHI]) == true ) && ($setupHomematicSocket == true) )
		{
		echo "Homematic CCU instanz mit Namen \"".$installHI."\" im Konfiguratiosfile gefunden.\n";
		if ( isset($config[$installHI]) == false )
			{
			echo "!!! Homematic CCU instanz mit Namen \"".$installHI."\" neu anlegen.\n"; 
			if (!$debug)
				{		/* wenn debug true ist keine Variablen anlegen */
				$InsID = IPS_CreateInstance("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
				IPS_SetName($InsID, $installHI); // Instanz benennen
				}
			}
		else
			{
			$InsID = $config[$installHI]["OID"];
			}
		$configHI=json_decode($HomematicInstanzen[$installHI]["CONFIG"]);
		if ($configHI->IPAddress!=$ownIPaddress)
			{
			echo "!!! Vorhandene oder neue CCU von ".$configHI->IPAddress." im alten Config File auf eigene IP Adresse ".$ownIPaddress." setzen.\n";
			echo "   Check config Vorhanden: ".IPS_GetConfiguration($InsID)."\n";
			$configHI->IPAddress=$ownIPaddress;
			echo "   mit neuer Configuration: ".json_encode($configHI)."\n";
			if (!$debug)
				{		/* wenn debug true ist keine Variablen anlegen */
				IPS_SetConfiguration($InsID, json_encode($configHI));
				IPS_ApplyChanges($InsID);
				}
			}
		}

	/**********************************************************************************************
	 * evaluate Homematic Devices allready installed
	 *
	 */

	//Homematic Sender
	$guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);

	if ($analyze) echo "\nBereits installierte Homematic Instanzen : ".sizeof($alleInstanzen)."\n\n";
	$serienNummer=array();
	$SerienNummerListe=array();
	
	foreach ($alleInstanzen as $instanz)
		{
		$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
		//echo "   ".IPS_GetConfiguration($instanz)."   ".$HM_CCU_Name."\n";
		$HM_Adresse=IPS_GetProperty($instanz,'Address');
		$result=explode(":",$HM_Adresse);
		//print_r($result);
		if ($analyze) echo str_pad(IPS_GetName($instanz),40)." ".$instanz." ".$HM_Adresse." ".str_pad(IPS_GetProperty($instanz,'Protocol'),3)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name."\n";
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
	if ($analyze)
		{
		echo "\n";
		echo "Liste aller Homematic Seriennummern mit Kanalnummern, die bereits verbaut sind.\n";
		print_r($SerienNummerListe);
		}
		
	/**********************************************************************************************
	 * install new Homematic Devices
	 *
	 */

	$Homematic = HomematicList();
	//print_r($Homematic);

	$Hardware_ID    = CreateCategory('Hardware',   0, 0);
	$Homematic_ID   = CreateCategory('Homematic',  $Hardware_ID, 0);
	$HomematicIP_ID   = CreateCategory('HomematicIP',  $Hardware_ID, 0);

	if ($setupHomematic==true)
		{
		$count=0;
		foreach ($Homematic as $name => $Entry)
			{
			/* wurde die Instanz bereits gefunden ? durch die vorher generierte Liste gehen */
			$found=false;
			foreach ($SerienNummerListe as $SerienNummer)
				{
				if ( $SerienNummer == $Entry['Adresse'] )
					{
					//echo $SerienNummer." ".$Entry['Adresse']." GEFUNDEN\n";
					$found=true;
					}
				}
		
			if ($analyze) echo $name." ".$Entry['Adresse']."\n";
			$Test_ID = @IPS_GetInstanceIDByName($name, $Homematic_ID);
			if ($Test_ID == false) { $Test_ID = @IPS_GetInstanceIDByName($name, $HomematicIP_ID); }
		
			if (!$debug)
			//If ( $found == false )
				{
				if ($Test_ID == false)
					{
					/* wurde noch nicht angelegt */
					$Test_ID = IPS_CreateInstance("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
					if ($Entry["Protocol"]=="IP") 
						{
						IPS_SetParent($Test_ID, $HomematicIP_ID);
						IPS_SetProperty($Test_ID,"Protocol",2);
						echo "HomematicIP-Instanz #$Test_ID in Kategorie HomematicIP angelegt\n";
						}
					else 
						{
						IPS_SetParent($Test_ID, $Homematic_ID);
						IPS_SetProperty($Test_ID,"Protocol",0);
						echo "Homematic-Instanz #$Test_ID in Kategorie Homematic angelegt\n";
						}
					IPS_SetName($Test_ID, $name);
					IPS_SetInfo($Test_ID, "this Object was created by Script InstallHardware of EvaluateHardware");
					IPS_SetProperty($Test_ID,"Address",$Entry['Adresse']);
					ConnectInstance($Test_ID);
					IPS_ApplyChanges($Test_ID);
					}
				else
					{
					if ($analyze) echo "     ".IPS_GetConfiguration($Test_ID)."\n";
					IPS_SetPosition($Test_ID,$count);
					IPS_SetProperty($Test_ID,"Address",$Entry['Adresse']);
					//print_r($Entry);
					ConnectInstance($Test_ID);
					if (IPS_HasChanges($Test_ID))
						{
						$status=@IPS_ApplyChanges($Test_ID);
						//echo "ID : ".$Test_ID." ".$status."\n";
						}
					}
				} // Ende if found
			else
				{
				if ($Test_ID == false) echo "Homematic-Instanz Main Zone \"".$name."\" in Kategorie Homematic muss angelegt werden.\n";
				}	
			$count++;
			if ($Test_ID != 0)
				{
				if ( $found ) 
					{
					//echo "** ".str_pad($name,30)." Konfiguration : ".IPS_GetConfiguration($Test_ID)." Property : ".IPS_GetProperty($Test_ID,"Address")." ".$Test_ID."\n";
					}
				else
					{
					echo "   ".str_pad($name,30)." Konfiguration : ".IPS_GetConfiguration($Test_ID)." Property : ".IPS_GetProperty($Test_ID,"Address")." ".$Test_ID."\n";
					}
				}		
			//print_r( );
			}
		}

	/**********************************************************************************************
	 * sort Homematic Devices according to Instance Name, alphabetically
	 *
	 */
	
	$guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	echo "\nHomematic Geräte: ".sizeof($alleInstanzen)." alphabethisch sortieren, wie folgt:\n\n";
	$serienNummer=array();
	$CategoryNames=array();
	foreach ($alleInstanzen as $instanz)
		{
		$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
		$HM_Adresse=IPS_GetProperty($instanz,'Address');
		$result=explode(":",$HM_Adresse);
		//print_r($result);
		if ($analyze) echo str_pad(IPS_GetName($instanz),40)." ".$instanz." ".$HM_Adresse." ".str_pad(IPS_GetProperty($instanz,'Protocol'),3)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name."\n";
		if (isset($serienNummer[$HM_CCU_Name][$result[0]]))
			{
			$serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]+=1;
			}
		else
			{
			$serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]=1;
			$serienNummer[$HM_CCU_Name][$result[0]]["Values"]="";
			}
		$CategoryNames[IPS_GetName($instanz)]=$instanz;
		}
	//print_r($serienNummer);
	ksort($CategoryNames);
	print_r($CategoryNames);

	$Hardware_ID    = CreateCategory('Hardware',   0, 0);
	$Homematic_ID   = CreateCategory('Homematic',  $Hardware_ID, 0);

	$count=0;
	foreach ($CategoryNames as $name => $oid)
		{
		if ( (!$debug) && ($setupHomematic==true) )
			{
			IPS_SetPosition($oid,$count);
			if (IPS_HasChanges($oid))
				{
				$status=@IPS_ApplyChanges($oid);
   				//echo "ID : ".$Test_ID." ".$status."\n";
				}
			$count++;
			}
		}

	/**********************************************************************************************
	 * FS20 Variablen anlegen
	 *
	 */

	$FS20s = FS20List();
	//print_r($FS20s);
	
	$Hardware_ID    = CreateCategory('Hardware',   0, 0);
	$FS20_ID   = CreateCategory('FS20',  $Hardware_ID, 0);
	
	/*
	echo "Serieller Port:\n";
	print_r(IPS_GetInstance(40910));
	echo "Splitter:\n";	
	print_r(IPS_GetInstance(59562));
	*/
	
	$guid = "{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}";
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	echo "\nSerielle Ports für FHZ: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),45)."    ".IPS_GetConfiguration($instanz)."\n";
		}

	$guid = "{57040540-4432-4220-8D2D-4676B57E223D}";
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	echo "\nSplitter für FHZ: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),45)."    ".IPS_GetConfiguration($instanz)."\n";
		}

	$guid = "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	echo "\nFS20 Geräte: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),45)." ".$instanz." ".IPS_GetProperty($instanz,'HomeCode')." ".IPS_GetProperty($instanz,'Address').IPS_GetProperty($instanz,'SubAddress')." ".IPS_GetProperty($instanz,'EnableTimer')." ".IPS_GetProperty($instanz,'EnableReceive').IPS_GetProperty($instanz,'Mapping')."\n";
		echo "    ".IPS_GetConfiguration($instanz)."\n";
		}
		
	if ($setupFS20==true)
		{		
		foreach ($FS20s as $name => $FS20)		
			{
			$Test_ID = @IPS_GetInstanceIDByName($name, $FS20_ID);
			if (!$debug)
				{
				if ($Test_ID == false)
					{
					$Test_ID = IPS_CreateInstance("{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}");
					IPS_SetParent($Test_ID, $FS20_ID);
					IPS_SetName($Test_ID, $name);
					IPS_SetInfo($Test_ID, "this Object was created by Script InstallHardware of EvaluateHardware");
					IPS_SetProperty($Test_ID,"Address",$FS20['Adresse']);
					IPS_SetProperty($Test_ID,"SubAddress",$FS20['SubAdresse']);
					IPS_SetProperty($Test_ID,"HomeCode",$FS20['HomeCode']);

					//IPS_ConnectInstance($Test_ID,$config[$installHI]["OID"]); 					
					IPS_ApplyChanges($Test_ID);
					echo "FS20-Instanz #$Test_ID in Kategorie FS20 angelegt\n";
					}
				else
					{
					}
				}						
			echo "** ".str_pad($name,30)." Konfiguration : ".IPS_GetConfiguration($Test_ID)." Property : ".IPS_GetProperty($Test_ID,"Address")." ".$Test_ID."\n";
			}		
		}
		
	/**********************************************************************************************
	 * FHT Variablen anlegen
	 *
	 */

	$guid = "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	echo "\nFHT Geräte: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		}
	$FHTs=FHTList();
	if ($setupFHT==true)
		{	
		foreach ($FHTs as $name => $FHT)		
			{
			$Test_ID = @IPS_GetInstanceIDByName($name, $FS20_ID);
			if (!$debug)
				{
				if ($Test_ID == false)
					{
					$Test_ID = IPS_CreateInstance("{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}");
					IPS_SetParent($Test_ID, $FS20_ID);
					IPS_SetName($Test_ID, $name);
					IPS_SetInfo($Test_ID, "this Object was created by Script InstallHardware of EvaluateHardware");
					IPS_SetProperty($Test_ID,"Address",$FHT['Adresse']);

					//IPS_ConnectInstance($Test_ID,$config[$installHI]["OID"]); 					
					IPS_ApplyChanges($Test_ID);
					echo "FHT-Instanz #$Test_ID in Kategorie FS20 angelegt\n";
					}
				else
					{
					}
				}						
			echo "** ".str_pad($name,30)." Konfiguration : ".IPS_GetConfiguration($Test_ID)." Property : ".IPS_GetProperty($Test_ID,"Address")." ".$Test_ID."\n";		
			}
		}
	
	/****************************************************************************************************************************/

	function ConnectInstance($Test_ID)
		{
		global $Entry, $config;
			
		$ConnectInstance=IPS_GetInstance($Test_ID)["ConnectionID"];
					$ccuName=$Entry["CCU"]; 
					//echo "   ".$ccuName."\n";
					$CCUinstance=$config[$ccuName]["OID"];
					if ($ConnectInstance != $CCUinstance)
						{ 
						echo "  Connected Instanz: ".$ConnectInstance."   Target Instance : ".$CCUinstance."\n";
						//IPS_DisconnectInstance($Test_ID);
						//IPS_ConnectInstance($Test_ID,CCUinstance); 					
						}
		}
	
?>