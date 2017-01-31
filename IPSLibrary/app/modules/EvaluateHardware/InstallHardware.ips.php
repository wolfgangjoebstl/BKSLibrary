<?

/* installiert die Hardware auf einem neuen System basierend auf einem includefile dass auf einem anderen PC erstellt wurde
 *
 */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	include_once IPS_GetKernelDir().'scripts\\EvaluateHardware_Include.inc.php';

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

	$Homematic = HomematicList();
	//print_r($Homematic);

	$Hardware_ID    = CreateCategory('Hardware',   0, 0);
	$Homematic_ID   = CreateCategory('Homematic',  $Hardware_ID, 0);

	$count=0;
	foreach ($Homematic as $name => $Key)
		{
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
		If ( $found == false )
		   {
			if ($Test_ID == false)
				{
				$Test_ID = IPS_CreateInstance("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
				IPS_SetParent($Test_ID, $Homematic_ID);
				IPS_SetName($Test_ID, $name);
				IPS_SetInfo($Test_ID, "this Object was created by Script CopyHomematic");
		   	IPS_SetProperty($Test_ID,"Address",$Key['Adresse']);
				IPS_ApplyChanges($Test_ID);
				echo "Homematic-Instanz Main Zone #$Test_ID in Kategorie Homematic angelegt\n";
				}
			else
			   {
			   IPS_SetPosition($Test_ID,$count);
		   	IPS_SetProperty($Test_ID,"Address",$Key['Adresse']);
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