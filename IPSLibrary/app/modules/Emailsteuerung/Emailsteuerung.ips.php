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

	echo "Alle SMTP Clients:\n";
	print_r(IPS_GetInstanceListByModuleID("{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}"));
	$SendEmailID = @IPS_GetInstanceIDByName("SendEmail", $CategoryIdData);
	echo "Send Email ID: ".$SendEmailID."\n";

/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */

	$event=date("D d.m.y h:i:s")." Die Werte aus der Hausautomatisierung: \n\n".send_status(true).
		"\n\n************************************************************************************************************************\n".send_status(false);
	SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status LBG70", $event);
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{

	}


/*********************************************************************************************/


/*********************************************************************************************/




?>
