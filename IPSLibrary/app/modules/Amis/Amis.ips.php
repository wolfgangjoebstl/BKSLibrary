<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur 
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

/******************************************************

				INIT
				
*************************************************************/



//Hier die COM-Port Instanz
$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
$com_Port = $serialPortID[0];

if (!file_exists("C:\Scripts\Log_AMIS.csv"))
		{
      $handle=fopen("C:\Scripts\Log_AMIS.csv", "a");
	   fwrite($handle, date("d.m.y H:i:s").";Zählerdatensatz\r\n");
      fclose($handle);
	   }

$parentid1  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	   
// Archiv Handler damit das Logging eingeschaltet werden kann.
$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
$arhid = $archiveHandlerID[0];
//echo $arhid."\n";

// Wert in der die aktuell gerade empfangenen Telegramme hineingeschrieben werden
$AMISReceiveID = CreateVariableByName($parentid1, "AMIS Receive", 3);

// Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden 
$parentid = CreateVariableByName($parentid1, "Zaehlervariablen", 3);

if ($_IPS['SENDER'] == "RegisterVariable")
	 {
    $content = $_IPS['VALUE'];

  	 $handle=fopen("C:\Scripts\Log_AMIS.csv","a");
	 $ausgabewert=date("d.m.y H:i:s").";".$content;
	 fwrite($handle, $ausgabewert."\r\n");
	 fclose($handle);

    SetValue($AMISReceiveID,date("Y-m-d H:i:s",time()).":".$content);
    if (strlen($content)>20)
      {
	 	/* Routine funktioniert nur wenn der ganze Verrechnungsdatensatz ausgelesen wird
		 	Dauer ca. 60 Sekunden
	 	*/

		anfrage('Fehlerregister','F.F(',')',$content,3,'',$arhid,$parentid);
    	anfrage('Wirkleistung','1.7.0(','*kW)',$content,2,'~Power',$arhid,$parentid);
    	anfrage('Strom L1','31.7(','*A)',$content,2,'~Ampere',$arhid,$parentid);
    	anfrage('Strom L2','51.7(','*A)',$content,2,'~Ampere',$arhid,$parentid);
    	anfrage('Strom L3','71.7(','*A)',$content,2,'~Ampere',$arhid,$parentid);
    	anfrage('Frequenz','14.7(','*Hz)',$content,2,'~Hertz',$arhid,$parentid);

    	if (anfrage('Default-Wirkenergie','1.8.0(','*kWh)',$content,2,'~Electricity',$arhid,$parentid))
			{
			$wirkenergie_vwID = CreateVariableByName($parentid1, "Letzter Wert Wirkenergie", 2);
			$letzterWertID = CreateVariableByName($parentid1, "Letzter Wert", 1);
			$aktuelleLeistungID = CreateVariableByName($parentid1, "Aktuelle Leistung", 2);
			$wirkenergie_ID = IPS_GetObjectIDByName ( 'Default-Wirkenergie' , $parentid );
			echo "ID:".$wirkenergie_ID."\n";

	 		$wirkenergie=GetValue($wirkenergie_ID);
		 	$wirkenergie_vw=GetValue($wirkenergie_vwID);
		 	SetValue($wirkenergie_vwID,$wirkenergie);
	 		$time_now=time();
		 	$time=$time_now-GetValue($letzterWertID);
		 	SetValue($letzterWertID,$time_now);
	 		$power=($wirkenergie-$wirkenergie_vw)/$time*60*60;
		 	echo "Zeit in Sek : ".$time. " Leistung aktuell: ".$power."\n";
		 	SetValue($aktuelleLeistungID,$power);

		 	//echo "Ergebnis:".$content."\n";
			}
		}


/*
	 $zaehler_nr_ist = anfragezahlernr('Zählernummer','0.0(',')',$content);
	 // Hier sind die werte die abgefragt werden sollen.
    if ($Zaehler_nr == $zaehler_nr_ist)
	 	{
       anfrage('Zählerstand EnbW Einkauf','1.8.0(','*kWh)',$content,3,'',$arhid,$ParentID);
       anfrage('Zählerstand PV Verkauf','2.8.0(','*kWh)',$content,3,'',$arhid,$ParentID);
       anfrage('Spannung L1','32.7(','*V)',$content,2,'~Volt.230',$arhid,$ParentID);
       anfrage('Spannung L2','52.7(','*V)',$content,2,'~Volt.230',$arhid,$ParentID);
       anfrage('Spannung L3','72.7(','*V)',$content,2,'~Volt.230',$arhid,$ParentID);
       anfrage('Strom L1','31.7(','*A)',$content,2,'~Ampere',$arhid,$ParentID);
        anfrage('Strom L2','51.7(','*A)',$content,2,'~Ampere',$arhid,$ParentID);
        anfrage('Strom L3','71.7(','*A)',$content,2,'~Ampere',$arhid,$ParentID);
        //anfrage('Verbrauch aktuell L1','1.6.1(','*kW)',$content,2,'~Power',$arhid,$ParentID);
        //anfrage('Verbrauch aktuell L2','1.6.2(','*kW)',$content,2,'~Power',$arhid,$ParentID);
        //anfrage('Verbrauch aktuell L3','1.6.3(','*kW)',$content,2,'~Power',$arhid,$ParentID);
        anfrage('Verbrauch aktuell','16.7(','*kW)',$content,2,'~Power',$arhid,$ParentID);
        //anfrage('cos phi L1','33.7(','*cos)',$content,3,'',$arhid,$ParentID);
        //anfrage('cos phi L2','53.7(','*cos)',$content,3,'',$arhid,$ParentID);
        //anfrage('cos phi L3','73.7(','*cos)',$content,3,'',$arhid,$ParentID);
    	};
*/
	 }

if ($_IPS['SENDER'] == "Execute")
	{
	echo "Testweise letztes Ergebnis auswerten.\n";
   $content=GetValue($AMISReceiveID);
   //echo $content;
	echo "Fehlerregister : ".Auswerten($content,'F.F(',')')."\n";
	echo "Default Wirkenergie : ".Auswerten($content,'1.8.0(','*kWh)')."kWh \n";
	
	$oid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis.Zaehlervariablen');
	$wirkenergie_ID = IPS_GetObjectIDByName ( 'Default-Wirkenergie' , $oid );
	echo "Default Wirkenergie : ".$wirkenergie_ID."\n";
	}



/******************************************************************************************************************/

function anfragezahlernr($varname,$anfang,$ende,$content){
    $zaehler_nr_ist = Auswerten($content,$anfang,$ende);
    return $zaehler_nr_ist;
};

function anfrage($varname, $anfang, $ende, $content, $vartyp, $VariProfile, $arhid, $ParentID){
    $wert = Auswerten($content, $anfang, $ende);
    if ($wert) {vars($arhid, $ParentID, $varname, $wert, $vartyp, $VariProfile); return (true); }
    else { return (false); }
};

function Auswerten($content,$anfang,$ende){
 	$result_1 = explode($anfang,$content);
 	if (sizeof($result_1)>1)
   	{
		$result_2 = explode($ende,$result_1[1]);
 		$wert = str_replace(".", ",", $result_2[0]);
	 	/* echo "gefunden:".sizeof($result_1)." ".sizeof($result_2)." \n";
 		print_r($result_1);
	 	print_r($result_2);   */
 		return $wert;
 		}
 	else
 	   {
 	   return (false);
 	   }
};


function vars($arhid,$ParentID, $varname, $wert, $vartyp, $VariProfile)
  {
$VariID = IPS_GetVariableIDByName($varname, $ParentID);
    if ($VariID == false)
    {
        $VariID = IPS_CreateVariable ($vartyp);
        IPS_SetVariableCustomProfile($VariID, $VariProfile);
        IPS_SetName($VariID,$varname);
          AC_SetLoggingStatus($arhid, $VariID, true);
        IPS_SetParent($VariID,$ParentID);
    }
    SetValue($VariID, $wert);
  };


	   
?>
