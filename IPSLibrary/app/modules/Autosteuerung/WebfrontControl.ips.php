<?

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_AlexaClass.inc.php");

IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
$moduleManagerOC 	= new IPSModuleManager('OperationCenter',$repository);
$CategoryIdDataOC   = $moduleManagerOC->GetModuleCategoryID('data');
$installedModules 	= $moduleManagerOC->GetInstalledModules();

$categoryId_Autosteuerung 		= IPS_GetObjectIDByIdent('Autosteuerung',   $CategoryIdDataOC);
$TableEventsAS_ID				= IPS_GetObjectIDByIdent("TableEvents", $categoryId_Autosteuerung);
$SchalterSortAS_ID				= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_Autosteuerung);

$categoryId_AutosteuerungAlexa 	= IPS_GetObjectIDByIdent('Alexa',   $CategoryIdDataOC);
$TableEventsAlexa_ID			= IPS_GetObjectIDByName("TableEvents",$categoryId_AutosteuerungAlexa);
$SchalterSortAlexa_ID			= IPS_GetObjectIDByName("Tabelle sortieren",$categoryId_AutosteuerungAlexa);	

$categoryId_DeviceManagement    = IPS_GetObjectIDByIdent('DeviceManagement',   $CategoryIdDataOC);
$TableEventsDevMan_ID			= IPS_GetObjectIDByName("TableEvents", $categoryId_DeviceManagement);
$SchalterSortDevMan_ID			= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_DeviceManagement);

if (isset ($installedModules["DetectMovement"]))
	{
	$moduleManagerDM = new IPSModuleManager('DetectMovement',$repository);
	$CategoryIdDataDM     = $moduleManagerDM->GetModuleCategoryID('data');
	$CategoryIdAppDM      = $moduleManagerDM->GetModuleCategoryID('app');
	$scriptId  = IPS_GetObjectIDByIdent('TestMovement', $CategoryIdAppDM);	

	$categoryId_DetectMovement    	= IPS_GetObjectIDByIdent('DetectMovement',   $CategoryIdDataOC);
	$TableEventsDM_ID				= IPS_GetObjectIDByName("TableEvents", $categoryId_DetectMovement);
	$SchalterSortDM_ID				= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_DetectMovement);
	}

/************************************************************************************
 *
 * Webfront Routinen, abhängig vom Sortierbefehl
 *
 ***************************************************************************************/

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
	$debug=false;	// keine Echo Ausgaben
	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	switch ($_IPS['VARIABLE'])
		{
		case $SchalterSortAS_ID:
			/* Tabelle updaten wenn die Taste gedrueckt wird */
			$detectMovement = new TestMovement($debug);
			$autosteuerung_config=Autosteuerung_GetEventConfiguration();
			$eventlist=$detectMovement->getAutoEventListTable($autosteuerung_config,$debug);		// no Debug
			switch ($_IPS['VALUE'])
				{
				case 0:
					$html=$detectMovement->writeEventlistTable($eventlist);				
					break;
				case 1:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("OID",$eventlist));
					break;
				case 2:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Name",$eventlist));
					break;
				case 3:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Pfad",$eventlist));
					break;
				case 4:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("NameEvent",$eventlist));
					break;
				case 5:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Instanz",$eventlist));
					break;
				case 6:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Typ",$eventlist));
					break;
				case 7:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Config",$eventlist));
					break;
				case 8:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Homematic",$eventlist));
					break;
				case 9:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("DetectMovement",$eventlist));
					break;
				case 10:
					$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Autosteuerung",$eventlist));
					break;
				default;
					break;	
				}
			SetValue($TableEventsAS_ID,$html);				
			break;
		case $SchalterSortAlexa_ID:
			$Alexa = new AutosteuerungAlexaHandler();
			$alexaConfiguration=$Alexa->getAlexaConfig();
			$table = $Alexa->writeAlexaConfig($alexaConfiguration,"",true);	// html Ausgabe
			SetValue($TableEventsAlexa_ID,$table);			
			break;
		case $SchalterSortDevMan_ID:
		case $SchalterSortDM_ID:
		default:
			break;				
		}
	}

/************************************************************************************
 *
 * Execute, Testroutinen
 *
 ***************************************************************************************/
	
if ($_IPS['SENDER']=="Execute")
	{
	echo "Testweise Execute des Scripts aufgerufen.\n";
	echo "  Schalter für Sortierung der Autosteuerungs Events : $SchalterSortAS_ID.\n";
	echo "  Schalter für Sortierung der Alexa Befehle         : $SchalterSortAlexa_ID.\n";
	echo "\n";
	$debug=true;
	
	echo "========================================Alexa\n";
    $Alexa = new AutosteuerungAlexaHandler();
    echo "Alexa Instanzen, StatusCount = ".$Alexa->getCountInstances()." : ";
    foreach ($Alexa->getInstances() as $oid) echo $oid."   ";
	echo "\n";
    echo "Alexa Configuration:\n";
    $alexaConfiguration=$Alexa->getAlexaConfig();
    //print_r($alexaConfiguration);
    $filter="DeviceTemperatureSensor";
    $Alexa->writeAlexaConfig($alexaConfiguration,$filter);
    $filter="DeviceThermostat";
    $Alexa->writeAlexaConfig($alexaConfiguration,$filter,true);	
	
	$table = $Alexa->writeAlexaConfig($alexaConfiguration,"",true);	// html Ausgabe
	echo $table;

	echo "\n";
	echo "========================================DetectMovement\n";
	$detectMovement = new TestMovement($debug);
	$autosteuerung_config=Autosteuerung_GetEventConfiguration();
	$eventlist=$detectMovement->getAutoEventListTable($autosteuerung_config);
	echo "Ergebnis der Analyse der Autosteuerungs Events wird in der Tabelle gespeichert.\n";
	//print_r($eventlist);
	$html=$detectMovement->writeEventlistTable($eventlist);
	SetValue($TableEventsAS_ID,$html);



	
	}								

?>