<?php

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
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(120); 
	$startexec=microtime(true);

	/***************** INSTALLATION **************/

	echo "Update Switch configuration and register Events\n";

	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

    $componentHandling=new ComponentHandling();
	$commentField="zuletzt Konfiguriert von EvaluateHomematic um ".date("h:i am d.m.Y ").".";

    $debug=false;

	/******************************************** Schalter  *****************************************/

	echo "***********************************************************************************************\n";
	echo "Switch Handler wird ausgeführt. Macht bereits install CustomCompnents, DetectMovement und RemoteAccess mit !\n";
	echo "\n";
	if (function_exists('HomematicList'))
		{
        /* die Homematic Switche werden installiert, Routine übernimmt install CustomComponents, DetectMovement und RemoteAccess */
		echo "Homematic Switche werden registriert.\n";
		$struktur1=$componentHandling->installComponentFull(HomematicList(),["STATE","INHIBIT","!ERROR"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,',$commentField, $debug);				/* Homematic Switche */
	    echo "***********************************************************************************************\n";
		echo "HomematicIP Switche werden registriert.\n";
		$struktur2=$componentHandling->installComponentFull(HomematicList(),["STATE","SECTION","PROCESS"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,',$commentField, $debug);			    /* HomemeaticIP Switche */
        //print_r($struktur1);
        //print_r($struktur2);
        }
	if (function_exists('FS20List'))
		{
	    echo "***********************************************************************************************\n";
		echo "FS20 Switche werden registriert.\n";
        $struktur3=$componentHandling->installComponentFull(FS20List(),"StatusVariable",'IPSComponentSwitch_RFS20','IPSModuleSwitch_IPSHeat,',$commentField, $debug);			// Variable heisst Status, es wird übergeordnet nach StatusVariable gesucht
        //print_r($struktur3);
		}
	echo "***********************************************************************************************\n";

    
	
?>