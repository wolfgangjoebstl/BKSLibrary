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

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
$MeterReadID = CreateVariableByName($parentid, "Startpagetype", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */


$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
$com_Port = $serialPortID[0];

if (Getvalue($MeterReadID))
	{
	echo "Comport aktiviert. \n";
	COMPort_SetOpen($com_Port, true); //false für aus
	IPS_ApplyChanges($com_Port);

	COMPort_SetDTR($com_Port , true); /* Wichtig sonst wird der Lesekopf nicht versorgt */

	COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */

	IPS_Sleep(1550);

	COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> auf 300 baud bleiben */

	IPS_Sleep(1550);
	COMPort_SendText($com_Port ,"\x01\x52\x32\x02F009()\x03\x1F");    /* <SOH>R2<STX>F009()<ETX> checksumme*/

	}
else
	{
	echo "Comport deaktiviert. \n";
	COMPort_SetOpen($com_Port, false); //false für aus
	IPS_ApplyChanges($com_Port);
	}


	
?>
