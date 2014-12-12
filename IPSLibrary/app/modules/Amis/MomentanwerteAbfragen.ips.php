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
IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
/******************************************************

				INIT
				
*************************************************************/

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
$MeterReadID = CreateVariableByName($parentid, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
$TimeSlotReadID = CreateVariableByName($parentid, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
$SendTimeID = CreateVariableByName($parentid, "SendTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
$AmisConfig = get_AmisConfiguration();
$MeterConfig = get_MeterConfiguration();

foreach ($MeterConfig as $meter)
	{
	//print_r($meter);
	$ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	}
	
$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
$com_Port = $serialPortID[0];
//print_r($serialPortID);

if (Getvalue($MeterReadID))
	{
	if ($AmisConfig["Type"] == "Bluetooth")
	   {
      echo "Comport Bluetooth aktiviert. \n";
      COMPort_SendText($com_Port ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */
		}
		
	if ($AmisConfig["Type"] == "Serial")
	   {
      echo "Comport Serial aktiviert. \n";
		COMPort_SetOpen($com_Port, true); //false für aus
		IPS_ApplyChanges($com_Port);
		COMPort_SetDTR($com_Port , true); /* Wichtig sonst wird der Lesekopf nicht versorgt */
		}

   switch (Getvalue($TimeSlotReadID))
		{
		case "15":  /* Auto */
		   Setvalue($TimeSlotReadID,1);
  			break;

		case "8":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
		   Setvalue($SendTimeID,time());
		   COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
			IPS_Sleep(1550);
			COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> */
			IPS_Sleep(1550);
			COMPort_SendText($com_Port ,"\x01\x52\x32\x02F001()\x03\x17");    /* <SOH>R2<STX>F001()<ETX> */
			break;
			
		case "14":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "13":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "12":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "11":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "10":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "9":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "7":  /* Auto */
			writeEnergyHomematic($MeterConfig);
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "6":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "5":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "4":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "3":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "2":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;

		case "1":
		   Setvalue($SendTimeID,time());
			COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
			IPS_Sleep(1550);
			COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> auf 300 baud bleiben */
			IPS_Sleep(1550);
			COMPort_SendText($com_Port ,"\x01\x52\x32\x02F009()\x03\x1F");    /* <SOH>R2<STX>F009()<ETX> checksumme*/
			Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;

		default:
		   Setvalue($TimeSlotReadID,1);
			break;

		}
		
	}
else
	{
	if ($AmisConfig["Type"] == "Serial")
	   {
		echo "Comport Serial deaktiviert. \n";
		COMPort_SetOpen($com_Port, false); //false für aus
		IPS_ApplyChanges($com_Port);
		}
		
	if ($AmisConfig["Type"] == "Bluetooth")
	   {
		echo "Comport Bluetooth deaktiviert. \n";
		}
	}

if ($_IPS['SENDER']=="Execute")
   {
	echo "\n\n************************************************************************************************************************\n";

	$SerialComPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
	//print_r($PortID);
	echo "Serial Port : ".$SerialComPortID[0]." Name : ".IPS_GetName($SerialComPortID[0])."\n";
	
	//echo "Alle I/O Instanzen\n";
	//$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur I/O Instanzen auflisten

	//echo "Alle Kern Instanzen\n";
	//$alleInstanzen = IPS_GetInstanceListByModuleType(0); // nur Kern Instanzen auflisten

	//echo "Alle Splitter Instanzen\n";
	$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur Splitter Instanzen auflisten
	//print_r($alleInstanzen);
	foreach ($alleInstanzen as $instanz)
	   {
	   $datainstanz=IPS_GetInstance($instanz);
	   echo "****".$instanz." Name : ".IPS_GetName($instanz)."\n";
	   if ($instanz==$SerialComPortID[0])
	      {
	   	echo "\nRegisterVariable ".$instanz." Name : ".IPS_GetName($instanz)."\n";
		   print_r($datainstanz);
		   }
	   //print_r($datainstanz);
		//print_r($instanz);
	   }

//  COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
//		IPS_Sleep(1550);
//		COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> */
//		IPS_Sleep(1550);
//		COMPort_SendText($com_Port ,"\x01\x52\x32\x02F001()\x03\x17");    /* <SOH>R2<STX>F001()<ETX> */

	$pname="kWh";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','kWh');
	   print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="Wh";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','Wh');
	   print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="kW";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','kW');
	   print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	foreach ($MeterConfig as $meter)
		{
		//print_r($meter);
		echo "Create Variableset for :".$meter["NAME"]." \n";
		if ($meter["TYPE"]=="Homematic")
	   	{
	      $ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	      $EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	      IPS_SetVariableCustomProfile($EnergieID,'kWh');
	      $HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */

	      $result=IPS_SetVariableCustomProfile($HM_EnergieID,"kWh");
	      //echo "Change Profile for :".$HM_EnergieID." ".$result."\n";
	      //$vprof=IPS_GetVariableProfile("Wh");
	      //print_r($vprof);
	      
	      $LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
  	      IPS_SetVariableCustomProfile($LeistungID,'kW');
  	      }
  	   }
  	   
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];
	AC_SetLoggingStatus($archiveHandlerID,$EnergieID, true);

	$jetzt=time();
	$endtime=mktime(0,0,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
	$starttime=$endtime-60*60*24*1;
	echo "Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
	echo "Variable: ".IPS_GetName($EnergieID)."\n";

	$ergebnis=summestartende($starttime, $endtime, true,false,$archiveHandlerID,$EnergieID,true);

	
	}


/*************************************************************************************************************************/

function writeEnergyHomematic($MConfig)
	{
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	foreach ($MConfig as $meter)
		{
		if ($meter["TYPE"]=="Homematic")
	   	{
	      $ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	      $EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	      IPS_SetVariableCustomProfile($EnergieID,'kWh');
	      $HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	      IPS_SetVariableCustomProfile($HM_EnergieID,'kWh');
	      $LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
  	      IPS_SetVariableCustomProfile($LeistungID,'kW');
	      $energie=GetValue($meter["WirkenergieID"]);
	      $energievorschub=$energie-GetValue($HM_EnergieID)/1000;
			SetValue($HM_EnergieID,$energie/1000);
			$energie_neu=GetValue($EnergieID)+$energievorschub;
			SetValue($EnergieID,$energie_neu);
			SetValue($LeistungID,$energievorschub/1000*4);
	      //echo "Energie Aktuell :".$energie." gespeichert auf ID:".$EnergieID."\n";
	      echo "Energiezählerstand :".$energie_neu." kWh Leistung :".GetValue($LeistungID)." Wh \n";
			//print_r($meter);
			}
		}

	}


	
?>
