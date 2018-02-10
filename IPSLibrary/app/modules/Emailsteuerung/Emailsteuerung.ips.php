<?

/***********************************************************************

Emailsteuerung

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("Emailsteuerung_Library.class.php","IPSLibrary::app::modules::Emailsteuerung");
IPSUtils_Include ("Emailsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Emailsteuerung");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');


/******************************************************

				INIT

*************************************************************/

	// max. Scriptlaufzeit definieren
	ini_set('max_execution_time', 500);
	$startexec=microtime(true);

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Emailsteuerung',$repository);
		}

	$installedModules = $moduleManager->GetInstalledModules();

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$scriptId  = IPS_GetObjectIDByIdent('Emailsteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Emailsteuerung'));

	$ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
	$ScriptExecTimeID=CreateVariableByName($CategoryIdData,"ScriptExecTime",1);
	
	$device=IPS_GetName(0);

	$SendEmailID = @IPS_GetInstanceIDByName("SendEmail", $CategoryIdData);


/******************************************************

	INIT, Timer 

			EmailExecTimer				, 
			Aufruftimer       			, 

*************************************************************/
	
	$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $scriptId);
	$tim3ID = @IPS_GetEventIDByName("EmailExectimer", $scriptId);

/*************************************************************/

	$EmailControl = new EmailControlCenter();

/********************************************************************************************

	WEBFRONT

***********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/********************************************************************************************

	EXECUTE

***********************************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */
	//$archive_handler=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	//$jetzt=time();
	//$endtime=mktime(0,1,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
	//$starttime=$endtime-60*60*24*9;
	//$starttime=$endtime-60*2;
	//$werte = AC_GetLoggedValues($archive_handler, 24129, $starttime, $endtime, 0);
	
	echo "Category App ID:".$CategoryIdApp."\n";
	echo "Category Script ID:".$scriptId."\n";

	echo "\nEmail Konfiguration (ID: ".$SendEmailID."):\n";
	$result=IPS_GetConfiguration($SendEmailID);
	echo $result."\n";

	echo "\nAlle SMTP Clients:\n";
	$smtp_clients=IPS_GetInstanceListByModuleID("{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}");
	foreach ($smtp_clients as $smtp_client)
		{
		echo "  Smtp Client ID: ".$smtp_client."  -> ".IPS_GetName($smtp_client)."\n";
		}
	echo "\n";
	
	SetValue($ScriptExecTimeID,0); /* timer ausschalten, wenn gerade laeuft */

	$status=$EmailControl->SendMailStatusActualasAttachment();
	if ($status==false) $status=$EmailControl->SendMailStatusActualasAttachment(60*60*24);

	$status=$EmailControl->SendMailStatusHistoryasAttachment();
	if ($status==false) $status=$EmailControl->SendMailStatusHistoryasAttachment(60*60*24);

	$EmailControl->GetDirStatusActual();

	if (false)
	   {
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
		}
	SetValue($ScriptExecTimeID,1); /* timer wieder einschalten, wenn gerade laeuft */
	}

/********************************************************************************************

	VARIABLE

*************************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/********************************************************************************************

	TIMER

*************************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	switch ($_IPS['EVENT'])
	   {
	   case $tim1ID:        /* einmal am Tag */
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Aufruftimer email Status Auswertung verschicken");
			/********************************************************
			Einmal am Tag: den Staus auslesen und als zwei emails verschicken
			**********************************************************/
			if (isset($installedModules['OperationCenter']))
				{
				echo "OperationCenter installiert, auf Dropbox Verzeichnis gibt es eine Status Datei.\n ";
				if (false)
					{
					/* verwendet jetzt die Library */
					IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
					IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
	
					$moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
					$CategoryIdData  = $moduleManagerOC->GetModuleCategoryID('data');

					$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
					$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
					$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

					$emailText="\nLogspeicher ausgedruckt:\n".$log_OperationCenter->PrintNachrichten();
				
					$OperationCenter=new OperationCenter();
					$DIR_copystatusdropbox = $OperationCenter->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';
					echo "Status Dateien findet man auf ".$DIR_copystatusdropbox.".\n";
					$filename=$DIR_copystatusdropbox.date("Ymd").'StatusAktuell.txt';
					if ( ($status=@file_get_contents($filename)) === false)
						{
						echo "Filename ".$filename." wurde noch nicht erzeugt.\n";
						$emailStatus=SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$device, "File wurde noch nicht erzeugt !");
						}
					else
					   {
						$emailStatus=SMTP_SendMailAttachment($SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$device, "Daten und Auswertungen siehe Anhang\n".$emailText,$filename);
						if ($emailStatus==false) echo "Fehler bei der email Uebertragung.\n";
						}
					$filename=$DIR_copystatusdropbox.date("Ymd").'StatusHistorie.txt';
					if ( ($status=@file_get_contents($filename)) === false)
						{
						echo "Filename ".$filename." wurde noch nicht erzeugt.\n";
						$emailStatus=SMTP_SendMail($SendEmailID,date("Y.m.d D")." Nachgefragter Status, historische Werte ".$device, "File wurde noch nicht erzeugt !");
						}
					else
					   {
						$emailStatus=SMTP_SendMailAttachment($SendEmailID,date("Y.m.d D")." Nachgefragter Status, historische Werte ".$device, "Daten und Auswertungen siehe Anhang\n".$emailText,$filename);
						if ($emailStatus==false) echo "Fehler bei der email Uebertragung.\n";
						}
					}
					
				$status=$EmailControl->SendMailStatusActualasAttachment();
				if ($status==false) 
					{
					echo "Fehler beim email senden der aktuellen Werte.\n";
					IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Fehler: email Status Auswertung aktuelle Daten verschicken gescheitert");
					}
				$status=$EmailControl->SendMailStatusHistoryasAttachment();
				if ($status==false) 
					{
					echo "Fehler beim email senden der historischen Werte.\n";
					IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Fehler: email Status Auswertung historische Daten verschicken gescheitert");
					}										
				}	
			else
			   {
			   /* wenn nicht OperationCenter geladen, selbst erstellen und verschicken probieren */
				SetValue($ScriptCounterID,1);
				IPS_SetEventActive($tim3ID,true);
				}
		   break;

	   case $tim3ID:

			/******************************************************************************************
			 *
			 * Email Status Auswertung, Schritt für Schritt, 
			 * OperationCenter ist nicht installiert, alles selber machen
			 *
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
			   		else
			   			{
			   			IPS_SetEventActive($tim3ID,false);
			   			}
			   		break;	
				case 0:
  			   		IPS_SetEventActive($tim3ID,false);
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