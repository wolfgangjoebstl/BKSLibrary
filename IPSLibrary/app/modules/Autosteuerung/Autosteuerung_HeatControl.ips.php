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

	$kalender=new AutosteuerungStromheizung();

/********************************************************************************************
 *
 * Activity Script für die HeatControl
 *
 *  übernimmt das Setzen von Variablen
 *  verschiebt einmal am Tag den Wochenplan für den Einsatz der Heizung
 *
 **********************************************/

Switch ($_IPS['SENDER'])
    {
	Case "WebFront":
        //echo "Select";
		/* vom Webfront aus gestartet */
        $variableID=$_IPS['VARIABLE'];
        $oid=$kalender->getAutoFillID();
        switch ($variableID)
            {
            case $oid:
				//echo "SetAutoFill $oid ".$_IPS['VALUE']."  \n";
                $kalender->setAutoFill($_IPS['VALUE']);
                break;
            default:
        		SetValue($variableID,$_IPS['VALUE']);
                break;
            }    
		break;
	Case "TimerEvent":
	Case "Execute":
		$oid=IPS_GetVariableIDByName("AutoFill",$kalender->getWochenplanID());		// OID von Profilvariable für Autofill
		$value=$kalender->getStatusfromProfile(GetValue($oid));
		$kalender->ShiftforNextDay($value);                                     /* die Werte im Wochenplan durchschieben, neuer Wert ist der Parameter, die Links heissen aber immer noch gleich */
		$kalender->UpdateLinks($kalender->getWochenplanID());                   /* Update Links für Administrator Webfront */
		$kalender->UpdateLinks($categoryIdTab);		                            /* Upodate Links for Mobility Webfront */
		break;	
	default:
		$kalender=new AutosteuerungStromheizung();	
	}																																																																

/*********************************************************************************************/


?>