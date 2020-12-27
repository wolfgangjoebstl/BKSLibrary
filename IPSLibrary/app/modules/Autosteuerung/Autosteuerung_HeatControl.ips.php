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

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Autosteuerung\Autosteuerung_Configuration.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");
	
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
        $auto = new Autosteuerung();
        $oid=$kalender->getAutoFillID();
        if ($oid)
            {
            $configuration = $kalender->get_Configuration();

            /*  $kalender->InitLogMessage(true);            // noch einmal Interesse halber anschauen
            $kalender->getStatus();
            print_r($configuration);  
            $zeile1=$kalender->getZeile1ID();		// OID von Zeile1, aktueller Status
            echo "Execute vom script aufgerufen (AutoFill:$oid  Zeile1:$zeile1):\n";   */

            $value=$kalender->getStatusfromProfile(GetValue($oid));                 // value kann auch false sein
            if ($value)
                {
                $kalender->ShiftforNextDay($value);                                     /* die Werte im Wochenplan durchschieben, neuer Wert ist der Parameter, die Links heissen aber immer noch gleich */
                $kalender->UpdateLinks($kalender->getWochenplanID());                   /* Update Links für Administrator Webfront */
                $kalender->UpdateLinks($kalender->getCategoryIdTab());		                            /* Upodate Links for Mobility Webfront */

                if ($configuration["HeatControl"]["SwitchName"] != Null)
                    {
                    $result = $auto->isitheatday(true);             // true für Debug
                    $conf=array();
                    $conf["TYP"]=$configuration["HeatControl"]["Type"];
                    $conf["MODULE"]=$configuration["HeatControl"]["Module"];
                    $conf["NAME"]=$configuration["HeatControl"]["SwitchName"];
                    $auto->switchByTypeModule($conf,$result,true);              // true für Debug
                    //print_r($auto->getFunctions());
                    }
                }
            }
		break;	
	default:
        break;
	}																																																																

/*********************************************************************************************/


?>