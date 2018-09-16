<?

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
$moduleManagerOC 	= new IPSModuleManager('OperationCenter',$repository);
$CategoryIdDataOC   = $moduleManagerOC->GetModuleCategoryID('data');
$categoryId_Autosteuerung 	= IPS_GetObjectIDByIdent('Autosteuerung',   $CategoryIdDataOC);
$SchalterSortAS_ID			= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_Autosteuerung);

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	switch ($_IPS['VARIABLE'])
		{
		case $SchalterSortAS_ID:
			//echo "Kategorie Autosteuerung gefunden : ".$categoryId_Autosteuerung."\n";
			//echo "Umschalter für das Tabellen sortierwen gefunden : ".$SchalterSortAS_ID."\n";
			break;
		default:
			break;				
		}

	}

?>