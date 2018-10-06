<?

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
$moduleManagerOC 	= new IPSModuleManager('OperationCenter',$repository);
$CategoryIdDataOC   = $moduleManagerOC->GetModuleCategoryID('data');
$categoryId_Autosteuerung 	= IPS_GetObjectIDByIdent('Autosteuerung',   $CategoryIdDataOC);
$TableEventsAS_ID			= IPS_GetObjectIDByIdent("TableEvents", $categoryId_Autosteuerung);
$SchalterSortAS_ID			= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_Autosteuerung);

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	switch ($_IPS['VARIABLE'])
		{
		case $SchalterSortAS_ID:
			/* Tabelle updaten wenn die Taste gedrueckt wird */
			$debug=false;
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
		default:
			break;				
		}
	}
	
if ($_IPS['SENDER']=="Execute")
	{
	echo "Testweise Execute des Scripts aufgerufen.\n";
	$debug=true;
	$detectMovement = new TestMovement($debug);
	echo "========================================\n";
	$autosteuerung_config=Autosteuerung_GetEventConfiguration();
	$eventlist=$detectMovement->getAutoEventListTable($autosteuerung_config);
	echo "Ergebnis der Analyse der Autosteuerungs Events wird in der Tabelle gespeichert.\n";
	//print_r($eventlist);
	$html=$detectMovement->writeEventlistTable($eventlist);
	SetValue($TableEventsAS_ID,$html);
	
	}								

?>