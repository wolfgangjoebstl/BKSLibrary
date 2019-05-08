<?

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
If (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
}
$installedModules = $moduleManager->GetInstalledModules();
$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$ParseGuthabenID		= IPS_GetScriptIDByName('ParseDreiGuthaben',$CategoryIdApp);
$GuthabensteuerungID	= IPS_GetScriptIDByName('Guthabensteuerung',$CategoryIdApp);

$ScriptCounterID		= IPS_GetObjectIDByName("ScriptCounter",$CategoryIdData);
$startImacroID 			= IPS_GetObjectIDByName("StartImacro", $CategoryIdData);
$statusReadID 			= IPS_GetObjectIDByName("StatusWebread", $CategoryIdData);

$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "Aufruftimer");
	IPS_SetEventCyclic($tim1ID,0,0,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
	}
IPS_SetEventActive($tim1ID,true);

$tim2ID = @IPS_GetEventIDByName("Exectimer", $_IPS['SELF']);
if ($tim2ID==false)
	{
	$tim2ID = IPS_CreateEvent(1);
	IPS_SetParent($tim2ID, $_IPS['SELF']);
	IPS_SetName($tim2ID, "Exectimer");
	IPS_SetEventCyclic($tim2ID,2,1,0,0,1,150);      /* alle 150 sec */
	//IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
	}

$GuthabenConfig = get_GuthabenConfiguration();
$GuthabenAllgConfig = get_GuthabenAllgemeinConfig();
	
$phoneID=array();
$i=0;
foreach ($GuthabenConfig as $TelNummer)
	{
	//echo "Telefonnummer ".$TelNummer["NUMMER"]."\n";
	if (isset($TelNummer["STATUS"])==true)
		{
		if ($TelNummer["STATUS"]=="Active")
			{
			$phoneID[$i]["Nummer"]=$TelNummer["NUMMER"];
			$phoneID[$i]["Password"]=$TelNummer["PASSWORD"];
			$phoneID[$i++]["Short"]=substr($TelNummer["NUMMER"],(strlen($TelNummer["NUMMER"])-3),10);
			}
		}
	else	
		{
		$phoneID[$i]["Nummer"]=$TelNummer["NUMMER"];
		$phoneID[$i]["Password"]=$TelNummer["PASSWORD"];		
		$phoneID[$i++]["Short"]=substr($TelNummer["NUMMER"],(strlen($TelNummer["NUMMER"])-3),10);
		}
	} // ende foreach
$maxcount=$i;

//print_r($GuthabenAllgConfig);	
if ( isset($GuthabenAllgConfig["FirefoxDirectory"]) == true )
	{
	$firefox=$GuthabenAllgConfig["FirefoxDirectory"]."firefox.exe";
	//echo "Firefox verzeichnis : ".$firefox."\n";				
	}
else
	{
	if ( isset($GuthabenAllgConfig["FireFoxDirectory"]) == true )
		{
		$firefox=$GuthabenAllgConfig["FireFoxDirectory"]."firefox.exe";
		//echo "Firefox verzeichnis : ".$firefox."\n";				
		}
	else
		{		
		$firefox=ADR_Programs."Mozilla Firefox/firefox.exe";
		echo "Firefox Verzeichnis (old style aus AllgemeineDefinitionen): ".$firefox."\n";
		}					
	}

	
$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

/******************************************************

				TIMER

*************************************************************/

if ($_IPS['SENDER']=="TimerEvent")
	{
	//IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']);

	switch ($_IPS['EVENT'])
		{
		case $tim1ID:
			IPS_SetEventActive($tim2ID,true);
			SetValue($statusReadID,"");			// Beim Erstaufruf Html Log loeschen.
			break;
		case $tim2ID:
			//IPSLogger_Dbg(__file__, "TimerExecEvent from :".$_IPS['EVENT']." ScriptcountID:".GetValue($ScriptCounterID)." von ".$maxcount);
			$ScriptCounter=GetValue($ScriptCounterID);
			//IPS_SetScriptTimer($_IPS['SELF'], 150);
			if ($ScriptCounter < $maxcount)
				{
				// keine Anführungszeichen verwenden
				IPS_ExecuteEX($firefox, "imacros://run/?m=dreiat_".$phoneID[$ScriptCounter]["Nummer"].".iim", false, false, -1);
				if ($ScriptCounter>0)
					{
					$filedate=date ("d.m.Y H:i:s.", filemtime($GuthabenAllgConfig["DownloadDirectory"]."report_dreiat_".$phoneID[($ScriptCounter-1)]["Nummer"].".txt") );
					$note="Letzte Abfrage war um ".date("d.m.Y H:i:s")." für dreiat_".$phoneID[($ScriptCounter)]["Nummer"].".iim. Letztes Ergebnis für ".$phoneID[($ScriptCounter-1)]["Nummer"]." mit Datum ".$filedate." ";
					}
				else 	$note="Letzte Abfrage war um ".date("d.m.Y H:i:s")." für dreiat_".$phoneID[($ScriptCounter)]["Nummer"].".iim.";
				SetValue($statusReadID,GetValue($statusReadID)."<br>".$note);	

			    SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1);                	
				}
			else
				{
				IPS_RunScript($ParseGuthabenID);            // ParseDreiGuthaben wird aufgerufen
                $fileName=$GuthabenAllgConfig["DownloadDirectory"]."report_dreiat_".$phoneID[($ScriptCounter-1)]["Nummer"].".txt";
                if (file_exists($fileName)==true)
                    {
                    $fileMTimeDatei=filemtime($fileName);   // Liefert Datum und Uhrzeit der letzten Dateiänderung
				    $filedate=date ("d.m.Y H:i:s.", $fileMTimeDatei);
				    $note="Parse Files war um ".date("d.m.Y H:i:s").". Letztes Ergebnis für ".$phoneID[($ScriptCounter-1)]["Nummer"]." mit Datum ".$filedate." ";
                    }
                else $note="File ".$fileName." does not exists.";
				SetValue($statusReadID,GetValue($statusReadID)."<br>".$note);		

				SetValue($ScriptCounterID,0);
				//IPS_SetScriptTimer($_IPS['SELF'], 0);
				IPS_SetEventActive($tim2ID,false);
				}
			break;
		default:
			break;
		}
	}

/******************************************************

				Webfront

*************************************************************/

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	$value=$_IPS['VALUE']; $variable=$_IPS['VARIABLE'];
	switch ($variable)
		{
		case ($startImacroID):
			switch ($value)
				{
				case $maxcount:			// Alle
					IPS_SetEventActive($tim2ID,true);
					SetValue($ScriptCounterID,0);
					SetValue($statusReadID,"Abfrage für Alle um ".date("d.m.Y H:i:s")." gestartet.");			// Beim Erstaufruf Html Log loeschen.					
					//echo "Ja Alle";
					break;
				case ($maxcount+1):		// Test
					$handle2=fopen($GuthabenAllgConfig["MacroDirectory"]."dreiat_test.iim","w");
					fwrite($handle2,'VERSION BUILD=8970419 RECORDER=FX'."\n");
					fwrite($handle2,'TAB T=1'."\n");
					fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
					fwrite($handle2,'SET !EXTRACT NULL'."\n");
					fwrite($handle2,'SET !VAR0 '.$phoneID[6]["Nummer"]."\n");
					fwrite($handle2,'ADD !EXTRACT {{!VAR0}}'."\n");
					if (false)
						{
					//fwrite($handle2,'URL GOTO=http://www.drei.at/'."\n");
					fwrite($handle2,'URL GOTO=https://www.drei.at/selfcare/restricted/prepareMyProfile.do'."\n");
					fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:Kundenzone'."\n");
					fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=ID:loginForm ATTR=ID:userName CONTENT='.$phoneID[0]["Nummer"]."\n");
					fwrite($handle2,'SET !ENCRYPTION NO'."\n");
					fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=ID:loginForm ATTR=ID:password CONTENT='.$phoneID[0]["Password"]."\n");
					fwrite($handle2,'TAG POS=1 TYPE=BUTTON FORM=ID:loginForm ATTR=TXT:Login'."\n");
					fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_dreiat_{{!VAR0}}'."\n");
					fwrite($handle2,'\'Ausloggen'."\n");
					fwrite($handle2,'URL GOTO=https://www.drei.at/selfcare/restricted/prepareMainPage.do'."\n");
					fwrite($handle2,'TAG POS=2 TYPE=A ATTR=TXT:Kundenzone'."\n");
					fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:logout'."\n");
					fwrite($handle2,'TAB CLOSE'."\n");					
						}
					else
						{
						fwrite($handle2,'URL GOTO=https://service.upc.at/myupc/portal/mobile'."\n");
						//fwrite($handle2,'URL GOTO=https://service.upc.at/login/?TAM_OP=login&USERNAME=unauthenticated&ERROR_CODE=0x00000000&URL=%2Fmyupc%2Fportal%2Fmobile&REFERER=&OLDSESSION='."\n");
						fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=ACTION:/pkmslogin.form ATTR=ID:username CONTENT=wolfgangjoebstl@yahoo.com'."\n");
						fwrite($handle2,'SET !ENCRYPTION NO'."\n");
						fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=ACTION:/pkmslogin.form ATTR=ID:password CONTENT=##cloudG06##'."\n");
						fwrite($handle2,'TAG POS=1 TYPE=SPAN ATTR=ID:lbl_login_signin'."\n");
						fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_dreiat_{{!VAR0}}'."\n");
						fwrite($handle2,'TAG POS=1 TYPE=SPAN ATTR=ID:MYUPC_child.logout_dsLoggedInAs'."\n");
						fwrite($handle2,'TAG POS=1 TYPE=STRONG ATTR=TXT:Abmelden'."\n");						
						fwrite($handle2,'TAB CLOSE'."\n");							
						}	
					
					fclose($handle2);
					IPS_ExecuteEX($firefox, "imacros://run/?m=dreiat_test.iim", false, false, -1);
					break;
				default:
					//echo "ImacroAufruf von ".$phoneID[$value]["Nummer"]." mit Index ".$value.".\n";
					IPS_ExecuteEX($firefox, "imacros://run/?m=dreiat_".$phoneID[$value]["Nummer"].".iim", false, false, -1);
					break;
				}			
			break;
		default:
			break;	
		}
	
	}

/******************************************************

				Execute

*************************************************************/

//if (($_IPS['SENDER']=="Execute") or ($_IPS['SENDER']=="WebFront"))
if ($_IPS['SENDER']=="Execute")
	{
	echo "Category Data ID            : ".$CategoryIdData."\n";
	echo "Category App ID             : ".$CategoryIdApp."\n";

	echo "Verzeichnis für Macros :     ".$GuthabenAllgConfig["MacroDirectory"]."\n";
	echo "Verzeichnis für Ergebnisse : ".$GuthabenAllgConfig["DownloadDirectory"]."\n";
	echo "Verzeichnis für Mozilla exe: ".$firefox."\n\n";
	
	//print_r($phone);
	
	$dir=array(); $count=0; $verzeichnis=$GuthabenAllgConfig["DownloadDirectory"];
		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $verzeichnis ) )
			{
			// öffnen des Verzeichnisses
			if ( $handle = opendir($verzeichnis) )
				{
				/* einlesen der Verzeichnisses	*/
				while ((($file = readdir($handle)) !== false) )
					{
					if ($file!="." and $file != "..")
						{	/* kein Directoryverweis (. oder ..), würde zu einer Fehlermeldung bei filetype führen */
						//echo "Bearbeite ".$verzeichnis.$file."\n";
						$dateityp=filetype( $verzeichnis.$file );
						if ($dateityp == "file")			// alternativ dir für Verzeichnis
							{
							//echo "   Erfasse Verzeichnis ".$verzeichnis.$file."\n";
							$dir[$count]["Name"]=$verzeichnis.$file;
							$dir[$count++]["Date"]=date ("d.m.Y H:i:s.", filemtime($verzeichnis.$file));
							}
						}	
					//echo "    ".$file."    ".$dateityp."\n";
					} /* Ende while */
				//echo "   Insgesamt wurden ".$count." Verzeichnisse entdeckt.\n";	
				closedir($handle);
				} /* end if dir */
			}/* ende if isdir */
		else
			{
			echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
			}	
	//print_r($dir);
	echo "Dateien im Download Verzeichnis.\n";
	foreach ($dir as $entry) echo "   ".$entry["Name"]."  zuletzt geändert am ".$entry["Date"]."\n";

	echo "Stand ScriptCounter :".GetValue($ScriptCounterID)." von max ".$maxcount."\n";
	
	if (false)		// Jetzt das automatisches Auslesen über Webfront steuern
		{
		SetValue($ScriptCounterID,0);
		//IPS_SetScriptTimer($_IPS['SELF'], 1);
		IPS_SetEventActive($tim2ID,true);
		echo "Exectimer gestartet, Auslesung beginnt ....\n";
		echo "Timer täglich ID:".$tim1ID." und alle 150 Sekunden ID: ".$tim2ID."\n";
		//echo ADR_Programs."Mozilla Firefox/firefox.exe";
   
		echo "\n\nGuthabensteuerung laeuft nun, da sie haendisch mit Aufruf dieses Scripts ausgelöst wurde.\n";
   		//IPS_SetEventActive($tim2ID,true); /* siehe weiter oben ...*/
		}

	echo "\n-----------------------------------------------------\n";
	echo "Historie der Guthaben und verbrauchten Datenvolumen.\n";
	//$variableID=get_raincounterID();
	$endtime=time();
	$starttime=$endtime-60*60*24*2;  /* die letzten zwei Tage */
	$starttime2=$endtime-60*60*24*100;  /* die letzten 100 Tage */

	foreach ($GuthabenConfig as $TelNummer)
		{
		$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
		$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
		$phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Volume", 2);
    	$phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
		$phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_VolumeCumm", 2);
		echo "\n".$TelNummer["NUMMER"]." ".str_pad($TelNummer["STATUS"],10)."  ".str_pad(GetValue($phone_User_ID),40)." : ".GetValue($phone_Volume_ID)."MB und kummuliert ".GetValue($phone_VolumeCumm_ID)."MB \n";
		if (AC_GetLoggingStatus($archiveHandlerID, $phone_VolumeCumm_ID)==false)
		   {
		   echo "Werte wird noch nicht gelogged.\n";
		   }
		else
			{
			$werteLog = AC_GetLoggedValues($archiveHandlerID, $phone_VolumeCumm_ID, $starttime2, $endtime,0);
			$werteLog = AC_GetLoggedValues($archiveHandlerID, $phone_Volume_ID, $starttime2, $endtime,0);
	  		$werte = AC_GetAggregatedValues($archiveHandlerID, $phone_Volume_ID, 1, $starttime2, $endtime,0);
			foreach ($werteLog as $wert)
				{
				echo "Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
				}
			}
		//$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
		//$ergebnis1=parsetxtfile($GuthabenAllgConfig["DownloadDirectory"],$TelNummer["NUMMER"]);
		//SetValue($phone1ID,$ergebnis1);
		//$ergebnis.=$ergebnis1."\n";
		}

	if (false)
		{
		echo "\n-----------------------------------------------------\n";
		echo "Aufruf von iMacro oder Parseguthaben wenn Abfragen bereits abgeschlossen.\n";
		SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1);
		//IPS_SetScriptTimer($_IPS['SELF'], 150);
		if (GetValue($ScriptCounterID) < $maxcount)
			{
			// keine Anführungszeichen verwenden
			echo "Aufruf von :".$firefox." imacros://run/?m=dreiat_".$phoneID[GetValue($ScriptCounterID)]["Nummer"].".iim"."\n";
			IPS_ExecuteEX($firefox, "imacros://run/?m=dreiat_".$phoneID[GetValue($ScriptCounterID)]["Nummer"].".iim", false, false, -1);		
			}
		else
			{
			IPS_RunScript($ParseGuthabenID);
			SetValue($ScriptCounterID,0);
			//IPS_SetScriptTimer($_IPS['SELF'], 0);
			IPS_SetEventActive($tim2ID,false);
			}
		}
	}


?>