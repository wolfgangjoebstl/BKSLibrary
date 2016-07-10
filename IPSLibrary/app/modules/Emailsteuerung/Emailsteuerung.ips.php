<?

/***********************************************************************

Sprachsteuerung

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("Emailsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Emailsteuerung");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 500);
$startexec=microtime(true);

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

	$ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
	$ScriptExecTimeID=CreateVariableByName($CategoryIdData,"ScriptExecTime",1);
	
	$device=IPS_GetName(0);

	echo "\nAlle SMTP Clients:\n";
	$smtp_clients=IPS_GetInstanceListByModuleID("{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}");
	foreach ($smtp_clients as $smtp_client)
		{
		echo "  Smtp Client ID: ".$smtp_client."  -> ".IPS_GetName($smtp_client)."\n";
		}
	
	$SendEmailID = @IPS_GetInstanceIDByName("SendEmail", $CategoryIdData);
	echo "\nSend Email ID: ".$SendEmailID."\n";

	/******************************************************

				INIT, Timer, sollte eigentlich in der Install Routine sein

				MoveCamFiles				, alle 150 Sec
				RouterAufruftimer       , immer um 0:20

	*************************************************************/
	
	$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $scriptId);
   $tim3ID = @IPS_GetEventIDByName("EmailExectimer", $scriptId);


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

	echo "Du arbeitest auf Gerät : ".$device." und sendest zwei Statusemails.\n";
	SetValue($ScriptExecTimeID,0); /* timer ausschalten, wenn gerade laeuft */

	if (true)
	   {
		$event1=date("D d.m.y h:i:s")." Die aktuellen Werte aus der Hausautomatisierung: \n\n".send_status(true,$startexec).
			"\n\n************************************************************************************************************************\n";
		echo "******************** sendstatus true bislang genutzte Rechenzeit : ".exectime($startexec)."\n";
		//	SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$device, $event1);
		echo "******************** sendemail bislang genutzte Rechenzeit : ".exectime($startexec)."\n";
		$event2=date("D d.m.y h:i:s")." Die historischen Werte aus der Hausautomatisierung: \n\n".send_status(false,$startexec).
			"\n\n************************************************************************************************************************\n";
		echo "******************** sendstatus false bislang genutzte Rechenzeit : ".exectime($startexec)."\n";
		//SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle und historische Werte ".$device, $event2);
		echo "******************** sendemail bislang genutzte Rechenzeit : ".exectime($startexec)."\n";
	
		echo $event1.$event2;
		}
	echo "******************** sendemail bislang genutzte Rechenzeit : ".exectime($startexec)."\n";
	echo "Groesse : ".strlen($event1)."  ".strlen($event2)."\n";
	$emailStatus=SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle und historische Werte ".$device, $event1.$event1);
	if ($emailStatus==false) echo "Fehler bei der email Uebertragung.\n";
	echo "******************** sendemail bislang genutzte Rechenzeit : ".exectime($startexec)."\n";

	SetValue($ScriptExecTimeID,1); /* timer wieder einschalten, wenn gerade laeuft */
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	switch ($_IPS['EVENT'])
	   {
	   case $tim1ID:        /* einmal am Tag */
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Aufruftimer email Status Auswertung");
			/********************************************************
		   Einmal am Tag: den Staus auslesen und als zwei emails verschicken
			**********************************************************/
			SetValue($ScriptCounterID,1);
			IPS_SetEventActive($tim3ID,true);
	      break;

	   case $tim3ID:

			/******************************************************************************************
		     Email Status Auswertung, Schritt für Schritt
			*********************************************************************************************/

			$counter=GetValue($ScriptCounterID);
			switch ($counter)
			   {
				case 3:
				   /* reserviert für Nachbearbeitung */
		      	SetValue($ScriptCounterID,0);
			      IPS_SetEventActive($tim3ID,false);
		      	break;
			   case 2:
					/* Email Auswertung Teil 2*/
					if (GetValue($ScriptExecTimeID)>0)
					   {
					   SetValue($ScriptExecTimeID,0);
						$event1=date("D d.m.y h:i:s")." Die aktuellen Werte aus der Hausautomatisierung: \n\n".send_status(true).
							"\n\n************************************************************************************************************************\n";
						SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$device, $event1);
						SetValue($ScriptCounterID,$counter+1);
						}
			   	break;
			   case 1:
					/* Email Auswertung Teil 1 */
					if (GetValue($ScriptExecTimeID)>0)
					   {
					   SetValue($ScriptExecTimeID,0);
						$event2=date("D d.m.y h:i:s")." Die historischen Werte aus der Hausautomatisierung: \n\n".send_status(false).
							"\n\n************************************************************************************************************************\n";
						SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, historische Werte ".$device, $event2);
			      	SetValue($ScriptCounterID,$counter+1);
			      	SetValue($ScriptExecTimeID,(microtime(true)-$startexec));
			         }
					break;
			   case 0:
				default:
				   break;
			   }
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Email Status Auswertung. ScriptcountID neu: ".GetValue($ScriptCounterID)." Laufzeit ".(microtime(true)-$startexec)." Sek");
			break;
		default:
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." ID unbekannt.");
		   break;
		}
	}



/*********************************************************************************************/

/* reserviert für functions */



/*********************************************************************************************/




?>
