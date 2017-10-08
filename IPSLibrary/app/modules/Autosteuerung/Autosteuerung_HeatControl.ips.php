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
	Case "Timer":
	default:
		$kalender=new AutosteuerungStromheizung();	
	}																																																																

/*********************************************************************************************/


?>