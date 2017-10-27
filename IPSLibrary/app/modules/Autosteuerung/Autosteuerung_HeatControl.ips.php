<?

/****************************************************************************************
*
* Autosteuerung, Spezialroutinen für Stromheizung/Heatcontrol
*
*
*
*
*******************************************************************************************/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Autosteuerung\Autosteuerung_Configuration.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");
	


/*********************************************************************************************/

Switch ($_IPS['SENDER'])
    {
	Case "WebFront":
		/* vom Webfront aus gestartet */
		SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
		break;
	Case "TimerEvent":
	Case "Execute":
		$kalender=new AutosteuerungStromheizung();
		$kalender->ShiftforNextDay();
		break;	
	default:
		$kalender=new AutosteuerungStromheizung();	
	}																																																																

/*********************************************************************************************/


?>