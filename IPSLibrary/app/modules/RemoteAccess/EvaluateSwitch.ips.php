<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für HomematicIP, Homematic und FS20 Schalten
 *
 */

	Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

    /******************************************************

				INIT

    *************************************************************/

	// max. Scriptlaufzeit definieren
	ini_set('max_execution_time', 2000);    /* sollte man am Ende wieder zurückstellen, gilt global */
	set_time_limit(120);
	$startexec=microtime(true);

	/***************** INSTALLATION **************/

	echo "Update Switch configuration and register Events\n";

	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

	/******************************************** Schalter  *****************************************/

	echo "***********************************************************************************************\n";
	echo "Switch Handler wird ausgeführt. Macht bereits install CustomCompnents, DetectMovement und RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic, HomematicIP und FS20 Switche werden registriert.\n";
    echo "\n";
	if (function_exists('HomematicList'))
		{
        /* die Homematic Switche werden installiert, Routine übernimmt install CustomComponents, DetectMovement und RemoteAccess */
		$struktur1=installComponentFull(HomematicList(),["STATE","INHIBIT","!ERROR"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,');				/* Homematic Switche */
	    echo "***********************************************************************************************\n";
		$struktur2=installComponentFull(HomematicList(),["STATE","SECTION","PROCESS"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,');			    /* HomemeaticIP Switche */
        print_r($struktur1);
        print_r($struktur2);
        }
	if (function_exists('FS20List'))
		{
	    echo "***********************************************************************************************\n";        
        $struktur3=installComponentFull(FS20List(),"StatusVariable",'IPSComponentSwitch_RFS20','IPSModuleSwitch_IPSHeat,');
        print_r($struktur3);
		}
	echo "***********************************************************************************************\n";

    
	
?>