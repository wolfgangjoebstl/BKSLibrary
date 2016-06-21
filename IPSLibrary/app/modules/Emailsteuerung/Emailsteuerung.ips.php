<?

/***********************************************************************

Sprachsteuerung

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("Emailsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Emailsteuerung");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('Emailsteuerung',$repository);
}

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('Emailsteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Emailsteuerung'));
echo "Category App ID:".$CategoryIdApp."\n";
echo "Category Script ID:".$scriptId."\n";

echo "\nAlle SMTP Clients:\n";
$smtp_clients=IPS_GetInstanceListByModuleID("{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}");
foreach ($smtp_clients as $smtp_client)
	{
	echo "  Smtp Client ID: ".$smtp_client."  -> ".IPS_GetName($smtp_client)."\n";
	}
	
$SendEmailID = @IPS_GetInstanceIDByName("SendEmail", $CategoryIdData);
echo "\nSend Email ID: ".$SendEmailID."\n";
	
$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "Aufruftimer");
	IPS_SetEventCyclic($tim1ID,0,0,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,4,10,0);  /* immer um 04:10 */
	}
IPS_SetEventActive($tim1ID,true);

/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */
	//$archive_handler=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	//$jetzt=time();
	//$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
	//$starttime=$endtime-60*60*24*9;
	//$starttime=$endtime-60*2;
	//$werte = AC_GetLoggedValues($archive_handler, 24129, $starttime, $endtime, 0);

	$device=IPS_GetName(0);
	echo "Du arbeitest auf Gerät : ".$device." und sendest zwei Statusemails.\n";

	$event1=date("D d.m.y h:i:s")." Die aktuellen Werte aus der Hausautomatisierung: \n\n".send_status(true).
		"\n\n************************************************************************************************************************\n";
	SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$device, $event1);

	$event2=date("D d.m.y h:i:s")." Die historischen Werte aus der Hausautomatisierung: \n\n".send_status(false).
		"\n\n************************************************************************************************************************\n";
	SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, historische Werte ".$device, $event2);

	echo $event1.$event2;
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	$device=IPS_GetName(0);
	$event=date("D d.m.y h:i:s")." Die Werte aus der Hausautomatisierung: \n\n".send_status(true).
		"\n\n************************************************************************************************************************\n".send_status(false);
	SMTP_SendMail($SendEmailID,date("Y.m.d D")." Regelmaesig nachgefragter Status ".$device, $event);
	}


/*********************************************************************************************/


/*********************************************************************************************/




?>
