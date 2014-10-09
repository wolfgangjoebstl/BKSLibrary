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
$AmisConfig = get_AmisConfiguration();

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


	
?>
