<?


/* Herausfinden welche Hardware verbaut ist und in IPSComponent und IPSHOmematic bekannt machen
	Define Files und Array function notwendig
	
*/

/******************************************************

				INIT

*************************************************************/

$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "Aufruftimer");
	IPS_SetEventCyclic($tim1ID,2,1,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,1,10,0);  /* immer um 01:10 */
	}
IPS_SetEventActive($tim1ID,true);

//$includefile='<?'."\n".'$fileList = array('."\n";
$includefile='<?'."\n"; 
$alleInstanzen = IPS_GetInstanceListByModuleType(3); // nur Geräte Instanzen auflisten
foreach ($alleInstanzen as $instanz)
	{
	$result=IPS_GetInstance($instanz);
	//echo IPS_GetName($instanz)." ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
	/* alle Instanzen dargestellt */
	//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
	//print_r(IPS_GetInstance($instanz));

	}

//FHT Sender
$guid = "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}";
//Auflisten
$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
$includefile.='function FHTList() { return array('."\n";

echo "\nFHT Geräte: ".sizeof($alleInstanzen)."\n\n";
foreach ($alleInstanzen as $instanz)
	{
	echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
	//echo IPS_GetName($instanz)." ".$instanz." \n";
	$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
	$includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
	$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
	$includefile.="\n         ".'"COID" => array(';

	$cids = IPS_GetChildrenIDs($instanz);
	//print_r($cids);
   foreach($cids as $cid)
    	{
      $o = IPS_GetObject($cid);
      //echo "\nCID :".$cid;
      //print_r($o);
      if($o['ObjectIdent'] != "")
		{
			$includefile.="\n                ".'"'.$o['ObjectIdent'].'" => array(';
			$includefile.="\n                              ".'"OID" => "'.$o['ObjectID'].'", ';
			$includefile.="\n                              ".'"Name" => "'.$o['ObjectName'].'", ';
			$includefile.="\n                              ".'"Typ" => "'.$o['ObjectType'].'",), ';
      	if(@HM_RequestStatus($id, $o['ObjectIdent']) === false)
				{
            echo "Fehler: ".IPS_GetLocation($id)."\n";
            break;
            }
        }
    }


	$includefile.="\n             ".'	),'."\n";
	$includefile.="\n      ".'	),'."\n";	//print_r(IPS_GetInstance($instanz));
	}
$includefile.=');}'."\n";

//FS20EX Sender
$guid = "{56800073-A809-4513-9618-1C593EE1240C}";
//Auflisten
$alleInstanzen = IPS_GetInstanceListByModuleID($guid);

echo "\nFS20EX Geräte: ".sizeof($alleInstanzen)."\n\n";
foreach ($alleInstanzen as $instanz)
	{
	echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'HomeCode')." ".IPS_GetProperty($instanz,'DeviceList')."\n";
	//echo IPS_GetName($instanz)." ".$instanz." \n";
	}

//FS20 Sender
$guid = "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}";
//Auflisten
$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
$includefile.='function FS20List() { return array('."\n";

echo "\nFS20 Geräte: ".sizeof($alleInstanzen)."\n\n";
foreach ($alleInstanzen as $instanz)
	{
	echo str_pad(IPS_GetName($instanz),45)." ".$instanz." ".IPS_GetProperty($instanz,'HomeCode')." ".IPS_GetProperty($instanz,'Address').IPS_GetProperty($instanz,'SubAddress')." ".IPS_GetProperty($instanz,'EnableTimer')." ".IPS_GetProperty($instanz,'EnableReceive').IPS_GetProperty($instanz,'Mapping')."\n";
	//echo IPS_GetName($instanz)." ".$instanz." \n";
	$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
	$includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
	$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
	$includefile.="\n         ".'"COID" => array(';

	$cids = IPS_GetChildrenIDs($instanz);
	//print_r($cids);
   foreach($cids as $cid)
    	{
      $o = IPS_GetObject($cid);
      //echo "\nCID :".$cid;
      //print_r($o);
      if($o['ObjectIdent'] != "")
				{
				$includefile.="\n                ".'"'.$o['ObjectIdent'].'" => array(';
				$includefile.="\n                              ".'"OID" => "'.$o['ObjectID'].'", ';
				$includefile.="\n                              ".'"Name" => "'.$o['ObjectName'].'", ';
				$includefile.="\n                              ".'"Typ" => "'.$o['ObjectType'].'",), ';
   	   	if(@HM_RequestStatus($id, $o['ObjectIdent']) === false)
					{
         	   echo "Fehler: ".IPS_GetLocation($id)."\n";
         	   break;
            	}
	        }
   	 }
	$includefile.="\n             ".'	),'."\n";
	$includefile.="\n      ".'	),'."\n";	//print_r(IPS_GetInstance($instanz));
	}
$includefile.=');}'."\n";


//Homematic Sender
$guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
//Auflisten
$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
$includefile.='function HomematicList() { return array('."\n";

echo "\nHomematic Geräte: ".sizeof($alleInstanzen)."\n\n";
$sizeHM=array();
$serienNummer=array();
foreach ($alleInstanzen as $instanz)
	{
	$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
	$HM_Adresse=IPS_GetProperty($instanz,'Address');
	$result=explode(":",$HM_Adresse);
	//print_r($result);
	echo str_pad(IPS_GetName($instanz),40)." ".$instanz." ".$HM_Adresse." ".str_pad(IPS_GetProperty($instanz,'Protocol'),3)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name."\n";
	if (isset($sizeHM[$HM_CCU_Name]))
	   {
	   $sizeHM[$HM_CCU_Name]+=1;
	   }
	else
		{
	   $sizeHM[$HM_CCU_Name]=0;
	   }
	if (isset($serienNummer[$HM_CCU_Name][$result[0]]))
	   {
	   $serienNummer[$HM_CCU_Name][$result[0]]+=1;
	   }
	else
		{
	   $serienNummer[$HM_CCU_Name][$result[0]]=1;
	   }
	$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
	$includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
	$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
	$includefile.="\n         ".'"CCU" => "'.$HM_CCU_Name.'", ';
	$includefile.="\n         ".'"COID" => array(';
	
	$cids = IPS_GetChildrenIDs($instanz);
	//print_r($cids);
   foreach($cids as $cid)
    	{
      $o = IPS_GetObject($cid);
      //echo "\nCID :".$cid;
      //print_r($o);
      if($o['ObjectIdent'] != "")
			{
			$includefile.="\n                ".'"'.$o['ObjectIdent'].'" => array(';
			$includefile.="\n                              ".'"OID" => "'.$o['ObjectID'].'", ';
			$includefile.="\n                              ".'"Name" => "'.$o['ObjectName'].'", ';
			$includefile.="\n                              ".'"Typ" => "'.$o['ObjectType'].'",), ';
      	if(@HM_RequestStatus($id, $o['ObjectIdent']) === false)
				{
            echo "Fehler: ".IPS_GetLocation($id)."\n";
            break;
            }
        	}
    	}
	$includefile.="\n             ".'	),'."\n";
	$includefile.="\n      ".'	),'."\n";	//print_r(IPS_GetInstance($instanz));
	}

/*$includefile.=');'."\n".'?>';*/
$includefile.=');}'."\n";
$includefile.="\n".'?>';
$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\EvaluateHardware\EvaluateHardware_Include.inc.php';
if (!file_put_contents($filename, $includefile)) {
        throw new Exception('Create File '.$filename.' failed!');
    		}
//include $filename;
//print_r($fileList);


$texte = Array(
    "CONFIG_PENDING" => "Konfigurationsdaten stehen zur Übertragung an",
    "LOWBAT" => "Batterieladezustand gering",
    "STICKY_UNREACH" => "Gerätekommunikation war gestört",
    "UNREACH" => "Gerätekommunikation aktuell gestört"
);

$ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
$HomInstanz=sizeof($ids);
if($HomInstanz == 0)
   {
   echo "ERROR: Keine HomeMatic Socket Instanz gefunden!\n";
   }

for ($i=0;$i < $HomInstanz; $i++)
   {
   $ccu_name=IPS_GetName($ids[$i]);
	echo "\nHomatic Socket ID ".$ids[$i]." / ".$ccu_name."   ".$sizeHM[$ccu_name]."\n";
	$msgs = HM_ReadServiceMessages($ids[$i]);
	if($msgs === false)
	   {
	   echo "  ERROR: Verbindung zur CCU fehlgeschlagen!\n";
	   }
	if(sizeof($msgs) == 0)
	   {
   	echo "  OK, keine Servicemeldungen!\n";
		}
	foreach($msgs as $msg)
		{
	   if(array_key_exists($msg['Message'], $texte))
			{
  		  	$text = $texte[$msg['Message']];
   		}
		else
			{
  	  		$text = $msg['Message'];
  			}
	   $id = GetInstanceIDFromHMID($msg['Address']);
	  	if(IPS_InstanceExists($id))
		 	{
   		$name = IPS_GetLocation($id);
	   	}
		else
			{
      	$name = "Gerät nicht in IP-Symcon eingerichtet";
    		}
	  	echo "  NACHRICHT : ".$name."  ".$msg['Address']."   ".$text." \n";
		}
	}
echo "insgesamt sind ".sizeof($serienNummer)." Geräte angeschlossen.\n";
print_r($serienNummer);

/********************************************************************************************************************/

function GetInstanceIDFromHMID($sid)
{
    $ids = IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
    foreach($ids as $id)
    {
        $a = explode(":", HM_GetAddress($id));
        $b = explode(":", $sid);
        if($a[0] == $b[0])
        {
            return $id;
        }
    }
    return 0;
}


?>
