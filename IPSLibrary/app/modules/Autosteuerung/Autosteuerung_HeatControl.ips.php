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
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
		}
	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
	if ($Mobile_Enabled==true)
		{	
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		}
	$categoryIdTab         = CreateCategoryPath($Mobile_Path.".Stromheizung");

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
		$kalender->UpdateLinks($kalender->getWochenplanID());
		$kalender->UpdateLinks($categoryIdTab);		
		break;	
	default:
		$kalender=new AutosteuerungStromheizung();	
	}																																																																

/*********************************************************************************************/


?>