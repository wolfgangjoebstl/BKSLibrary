<?

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
	}

$installedModules   = $moduleManager->GetInstalledModules();
$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

echo "Category Data ID           : ".$CategoryIdData."\n";
echo "Category App ID            : ".$CategoryIdApp."\n";

/* Config einlesen
 *
 *********************************************************************************************/

	$GuthabenConfig = get_GuthabenConfiguration();
	$GuthabenAllgConfig = get_GuthabenAllgemeinConfig();

	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	
	echo "Verzeichnis für Macros     : ".$GuthabenAllgConfig["MacroDirectory"]."\n";
	echo "Verzeichnis für Ergebnisse : ".$GuthabenAllgConfig["DownloadDirectory"]."\n\n";
	/* "C:/Users/Wolfgang/Documents/iMacros/Downloads/ */

/* Logging aktivieren
 *
 *********************************************************************************************/

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_Guthaben.csv",$input);


/******************************************************

				RUNSCRIPT

*************************************************************/

	//print_r($GuthabenConfig);
	$ergebnis="";

	foreach ($GuthabenConfig as $TelNummer)
		{
		//print_r($TelNummer);
		if ( strtoupper($TelNummer["STATUS"]) == "ACTIVE" )
			{ 
			$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

			$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
			$ergebnis1=parsetxtfile($GuthabenAllgConfig["DownloadDirectory"],$TelNummer["NUMMER"]);
			SetValue($phone1ID,$ergebnis1);
			$ergebnis.=$ergebnis1."\n";
			}
		else
			{
			}	
		}

/******************************************************

				Execute

*************************************************************/

if ($_IPS['SENDER']=="Execute")
	   {
		echo "Execute, Script wird ausgeführt:\n\n";
		echo $ergebnis;
	   	$ergebnis1="";
		foreach ($GuthabenConfig as $TelNummer)
			{
			$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

			$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
			$dateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Date", 3);
			$ldateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_loadDate", 3);
			$udateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_unchangedDate", 3);
			$userID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
			$ergebnis1.=$TelNummer["NUMMER"]."  ".str_pad(GetValue($userID),30)."  ".str_pad(GetValue($dateID),30)." ".str_pad(GetValue($udateID),30)." ".GetValue($ldateID)."\n";
			//echo "Telnummer ".$TelNummer["NUMMER"]." ".$udateID."\n";
			}
		echo "\nAusgabe der letzten Aenderungen der ausgelesenen Files : \n";
		echo "Nummer           Name                  letztes File von       letzte Aenderung Guthaben    letzte Aufladung\n".$ergebnis1;
		//print_r($GuthabenConfig);

		echo "\n\nHistorie der Guthaben und verbrauchten Datenvolumen.\n";
		//$variableID=get_raincounterID();
		$endtime=time();
		$starttime=$endtime-60*60*24*2;  /* die letzten zwei Tage */
		$starttime2=$endtime-60*60*24*800;  /* die letzten 100 Tage */

		foreach ($GuthabenConfig as $TelNummer)
			{
			$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
			$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
			$phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Volume", 2);
    		$phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
			$phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_VolumeCumm", 2);
			echo "\n".$TelNummer["NUMMER"]." ".GetValue($phone_User_ID)." : ".GetValue($phone_Volume_ID)."MB und kummuliert ".GetValue($phone_VolumeCumm_ID)."MB \n";
			if (AC_GetLoggingStatus($archiveHandlerID, $phone_VolumeCumm_ID)==false)
				{
			   echo "Werte wird noch nicht gelogged.\n";
			   }
			else
				{
				$werteLogVolC = AC_GetLoggedValues($archiveHandlerID, $phone_VolumeCumm_ID, $starttime2, $endtime,0);
				$werteLogVol = AC_GetLoggedValues($archiveHandlerID, $phone_Volume_ID, $starttime2, $endtime,0);
				//$werteAggVol = AC_GetAggregatedValues($archiveHandlerID, $phone_Volume_ID, 1, $starttime2, $endtime,0); /* tägliche Aggregation */
				$wertAlt=-1; $letzteZeile="";
				foreach ($werteLogVol as $wert)
					{
					if ($wertAlt!=$wert["Value"])
			      		{
						echo $letzteZeile;
						$letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
			   			//echo $letzteZeile;
			   			$wertAlt=$wert["Value"];
			   			}
					else
						{
  			   			$letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
						}
		   			//echo $letzteZeile;
			   		}
				$phone_Cost_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Cost", 2);
				$werteLogCost = AC_GetLoggedValues($archiveHandlerID, $phone_Cost_ID, $starttime2, $endtime,0);
				echo "Logged Cost Vaules:\n";
				$wertAlt=-1; $letzteZeile="";
				foreach ($werteLogCost as $wert)
					{
					if ($wertAlt!=$wert["Value"])
						{
			   			echo $letzteZeile;
			   			$letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
			   			//echo $letzteZeile;
			   			$wertAlt=$wert["Value"];
			   			}
					else
						{
  						$letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
						}
		   			}
				$phone_Load_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Load", 2);
				$werteLogLoad = AC_GetLoggedValues($archiveHandlerID, $phone_Load_ID, $starttime2, $endtime,0);
				echo "Logged Load Vaules:\n";
				$wertAlt=-1; $letzteZeile="";
				foreach ($werteLogLoad as $wert)
					{
					if ($wertAlt!=$wert["Value"])
						{
						echo $letzteZeile;
						$letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
						//echo $letzteZeile;
						$wertAlt=$wert["Value"];
						}
					else
						{
  						$letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
						}
					}
		   		/*
	   	 		$phone_Bonus_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Bonus", 2);
				$werteLogBonus = AC_GetLoggedValues($archiveHandlerID, $phone_Bonus_ID, $starttime2, $endtime,0);
			   echo "Logged Bonus Vaules:\n";
			   $wertAlt=-1; $letzteZeile="";
				foreach ($werteLogBonus as $wert)
				   {
			   	if ($wertAlt!=$wert["Value"])
			      	{
			   		//echo $letzteZeile;
			   		$letzteZeile="  Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
			   		echo $letzteZeile;
			   		$wertAlt=$wert["Value"];
			   		}
		   		}
				*/


				}
				
				
				
			/*
			foreach ($werteAggVol as $wert)
			   {
	   		echo "  Wert : ".number_format($wert["Avg"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
		   	}
			print_r($werteAggVol);
			*/
			
			//$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
			//$ergebnis1=parsetxtfile($GuthabenAllgConfig["DownloadDirectory"],$TelNummer["NUMMER"]);
			//SetValue($phone1ID,$ergebnis1);
			//$ergebnis.=$ergebnis1."\n";
			}

		}




/**************************************************************************************************/

function parsetxtfile($verzeichnis, $nummer)
	{

	//$startdatenguthaben=7;
	$startdatenguthaben=0;
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

	$handle = @fopen($verzeichnis."/report_dreiat_".$nummer.".txt", "r");
	$result1="";$result2="";$result3="";$result4="";$result5="";$result6="";
	$result4g="";$result4v="";$result4f="";  $result7=""; $result8="";
	$entgelte=false;
	unset($tarif);
	$postpaid=false;
	if ($handle)
		{
		while (($buffer = fgets($handle, 4096)) !== false) /* liest bis zum Zeilenende */
			{
			/* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */

			/********** zuerst den User ermitteln, steht hinter Willkommen 
			 *
			 */
			//echo $buffer;
			if(preg_match('/Willkommen/i',$buffer))
	   			{
				$pos=strpos($buffer,"kommen");
				if ($pos!=false)
					{
					$result1=trim(substr($buffer,$pos+7,200));
					}
				//echo "*********Ausgabe User : ".$result1."\n<br>";
				}
			/********** dann die rufnummer, am einfachsten zu finden mit der 0660er oder 0676er Kennung 
			 *
			 */
			if(preg_match('/0660/i',$buffer))
				{
				$result2=trim($buffer);
				//echo "*********Ausgabe Nummer : ".$result2."\n<br>";
				}
			if(preg_match('/0676/i',$buffer))      /* manchmal haben wir die Rufnummer mitgenommen */
				{
				$result2=trim($buffer);
				//echo "*********Ausgabe Nummer : ".$result2."\n<br>";
				}

			/********* dann das Datum der letzten Aktualisierung, zu finden nach Aktualisierung
			 *         bei postpaid kommt noch der Begriff Abrechnung als Endemarkierung hinzu
			 *
			 *         in den nächsten drei Zeilen wäre beim ersten mal der Tarif beschrieben, Zeilenvorschub auch rausnehmen
			 *         beim zweiten mal wäre es das Guthaben
			 *
			 *********************/
	      	if(preg_match('/Aktualisierung/i',$buffer))
		   		{
	   			$pos=strpos($buffer,"Aktualisierung");
	   			$Ende=strpos($buffer,"\n");
				if (strpos($buffer,"Abrechnung")!==false)
					{
					$Ende=strpos($buffer,"Abrechnung");
					$postpaid=true;
					}
				if ($pos!=false)
					{
					$result3=trim(substr($buffer,$pos+16,$Ende-$pos-16));
					}
				if (isset($tarif)==false)
					{
				   	/* nur beim ersten mal machen */
					$tarif=json_encode(fgets($handle, 4096).fgets($handle, 4096).fgets($handle, 4096));
					$order   = array('\r\n', '\n', '\r');
					$replace = '';
					$tarif1 = json_decode(str_replace($order, $replace, $tarif));
					//echo "******".$result2." ".$tarif." \n";
					//echo "******".$result2." ".$tarif1." \n";
					}
				//echo "*********Wert von : ".$result3." ".($pos+16). " ".($Ende-$pos-16)." \n<br>";
				}
			//echo "-----------------------------------------\n";
			//echo $buffer;

			/********* dann das Datum der letzten Aufladung
			 *
			 *********************/
	      	if(preg_match('/Aufladung:/i',$buffer))
		   		{
	   			$pos=strpos($buffer,"Aufladung:");
	   			$Ende=strpos($buffer,"\n");
				if ($pos!=false)
					{
					$result8=trim(substr($buffer,$pos+11,$Ende-$pos-11));
					}
				//echo "********* ".$result2." ".$result1." letzte Aufladung am : ".$result8." ".($pos+16). " ".($Ende-$pos-16)." \n<br>";
				//echo $buffer;
				}

			/************ Ermittlung verfügbares Datenguthaben
			 *            Suchen nach erstem Auftreten von MB, 
			 *            die MBit und
			 *            den Roaming Disclaimer und 
			 *            die Tarifinfo ausnehmen
			 *****************/
			if(preg_match('/MB/i',$buffer) and ($result4g=="") and !preg_match('/MBit/i',$buffer) and !preg_match('/MB,/i',$buffer) 
					and !preg_match('/MMS/i',$buffer) and !preg_match('/Taktung/i',$buffer) )         /* verfügbares Datenvolumen, was gibt es grundsaetzlich, erstes MB, aber nicht MBit */
			//if (preg_match('/MB/i',$buffer))
				{
				$result4g=trim(substr($buffer,$startdatenguthaben,200));
				if (preg_match('/Datenmenge/i',$result4g))
					{
					$result4g=substr($result4g,10,40);
					}
	   		//echo "*********Datenmenge : ".$result4g."\n<br>";
				}

			if (preg_match('/MB verbr/i',$buffer))
	   		{
				$result4v=trim(substr($buffer,$startdatenguthaben,200));
	   		//echo "*********verbraucht : ".$result4v."\n<br>";
				}

			/************************** Ermittlung verbrauchtes Datenguthaben */
			if (preg_match('/MB frei/i',$buffer))                       /* verbrauchtes Datenvolumen, das heisst was habe ich noch */
	   		{
				$result4f=trim(substr($buffer,$startdatenguthaben,200));
	   		//echo "*********frei : ".$result4f."\n<br>";
				}
				
			if (preg_match('/unlimitiert/i',$buffer))
	   		{
	   		$result4g="99999 MB";
				$result4f="99999 MB frei";
				$result4v=" 0 MB verbraucht";
	   		//echo "*********frei : ".$result4f."\n<br>";
				}

			/************************ Gültigkeit des Guthabens */
			if (preg_match('/bis:/i',$buffer))
	   		{
				$result7=trim(substr($buffer,12,200));
	   		//echo "*********Gültig bis : ".$result7."\n<br>";
				}
			if (preg_match('/Abrechnungszeitraum:/i',$buffer))
	   		{
				$result7=trim(substr($buffer,strpos($buffer,"-")+1,200));
	   		//echo "*********Gültig bis : ".$result7."\n<br>";
				}

			/************************** Ermittlung des Guthabens, oder zusätzlicher Verbindungsentgelte */
      	if (preg_match('/haben:/i',$buffer))
	   		{
	   		$pos=strpos($buffer,"haben:");
		  		$Ende=strpos($buffer,",");       /* Eurozeichen laesst sich nicht finden */
				if ($pos!=false)
					{
					$pos=$pos+6;
					$result5=trim(substr($buffer,$pos,$Ende-$pos+3));
					}
				if ($Ende === false)   // manchmal steht das Guthaben auch in der nächsten Zeile
					{
					$buffer = fgets($handle, 4096);
					$Ende=strpos($buffer,",");       /* Eurozeichen laesst sich nicht finden */
					$result5=trim(substr($buffer,$Ende-3,6));
					//echo "*********Geldguthaben : ".$result5." \n<br>";
					}
    			}
      	if ($entgelte==true)
	   		{
	   		$entgelte=false;
		  		$Ende=strpos($buffer,",");
				$result5=trim(substr($buffer,0,$Ende+3));
				//echo "*********Geldguthaben : ".$result5." \n<br>";
    			}
      	if (preg_match('/Verbindungsentgelte:/i',$buffer))
	   		{
	   		$entgelte=true;
    			}
	    	}
    	//$ergebnis="User:".$result1." Nummer:".$result2." Status:".$result4." Wert vom:".$result3." Guthaben:".$result5."\n";
 		$phone1ID = CreateVariableByName($parentid, "Phone_".$nummer, 3);
  		$phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Summary", 3);
    	$phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_User", 3);
     	//$phone_Status_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Status", 3);
     	$phone_Date_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Date", 3);
     	$phone_loadDate_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_loadDate", 3);
     	$phone_unchangedDate_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_unchangedDate", 3);
     	$phone_Bonus_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Bonus", 3);
     	$phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Volume", 2);
     	$phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_VolumeCumm", 2);
     	$phone_nCost_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Cost", 2);
     	$phone_nLoad_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Load", 2);
    	$phone_Cost_ID = CreateVariableByName($parentid, "Phone_Cost", 2);
     	$phone_Load_ID = CreateVariableByName($parentid, "Phone_Load", 2);
     	$phone_CL_Change_ID = CreateVariableByName($parentid, "Phone_CL_Change", 2);
		//$ergebnis="User:".$result1." Status:".$result4." Guthaben:".$result5." Euro\n";
		SetValue($phone_User_ID,$result1);
		//SetValue($phone_Status_ID,$result4);   /* die eigentlich interessante Information */
		//echo ":::::".$result4."::::::\n";
 		SetValue($phone_Date_ID,$result3);
 		$old_cost=(float)GetValue($phone_Bonus_ID);
 		$new_cost=(float)$result5;
		SetValue($phone_CL_Change_ID,$new_cost-$old_cost);
 		if ($new_cost < $old_cost)
 		   {
 		   SetValue($phone_Cost_ID, GetValue($phone_Cost_ID)+$old_cost-$new_cost);
 		   SetValue($phone_nCost_ID, GetValue($phone_nCost_ID)+$old_cost-$new_cost);
 		   SetValue($phone_unchangedDate_ID,date("d.m.Y"));
 		   }
 		if ($new_cost > $old_cost)
 		   {
 		   SetValue($phone_Load_ID, GetValue($phone_Cost_ID)-$old_cost+$new_cost);
 		   SetValue($phone_nLoad_ID, GetValue($phone_nLoad_ID)-$old_cost+$new_cost);
 		   SetValue($phone_unchangedDate_ID,date("d.m.Y"));
 		   }
  		SetValue($phone_Bonus_ID,$result5);

		if ($result8!="")
  			{
			SetValue($phone_loadDate_ID,$result8);
			}
			
		if ($result4!="")
  			{
	  		$Anfang=strpos($result4,"verbraucht")+10;
  			$Ende=strpos($result4,"frei");
  			$result6=trim(substr($result4,($Anfang),($Ende-$Anfang)));

	  		$Anfang=strpos($result4,"bis:")+5;
  			$result7=trim(substr($result4,($Anfang),20));
			}

  		 if ($result4g!="")
			{
			/*`hier wird das aktuelle Datenvolumen geschrieben */
			//$result6=" von ".$result4g." wurden ".$result4v." und daher sind  ".$result4f.".";
			$result6=" von ".$result4g." sind ".$result4f;
			$Ende=strpos($result4,"MB");
			$restvolumen=(float)trim(substr($result4f,0,($Ende-1)));
			//echo "Restvolumen ist : ".$restvolumen." MB \n";
			$bisherVolumen=GetValue($phone_Volume_ID);
			SetValue($phone_Volume_ID,$restvolumen);
			if (($bisherVolumen-$restvolumen)>0)
				{
				SetValue($phone_VolumeCumm_ID,$bisherVolumen-$restvolumen);
				}
			else
				{
				/* guthaben wurde aufgeladen */
				SetValue($phone_VolumeCumm_ID,$bisherVolumen);
		 		}				
			}


  		//echo $result1.":".$result6."bis:".$result7.".\n";
  		if ($postpaid==true)
  			{
  		 	if ($result6=="")
				{
		   		$ergebnis=$nummer." ".str_pad("(".$result1.")",30)."  Rechnung:".$result5." Euro";
				}
		 	else
		   		{
		   		$ergebnis=$nummer." ".str_pad("(".$result1.")",30)." ".$result6." bis ".$result7." Rechnung:".$result5." Euro";
				}
  		   	}
  		 else   /* prepaid tarif */
  		   	{
			//echo "Prepaid : ".$nummer."  ".$result7."\n";
  		 	if ($result6=="")
				{
		   		$ergebnis=$nummer." ".str_pad("(".$result1.")",30)."  Guthaben:".$result5." Euro";
				}
		 	else
		   		{
		   		$ergebnis=$nummer." ".str_pad("(".$result1.")",30)." ".$result6." bis ".$result7." Guthaben:".$result5." Euro";
				}
			if ($result7=="")  // Nutzungszeit abgelaufen
				{
				if ($result4g=="")
					{
					$ergebnis=$nummer." ".str_pad("(".$result1.")",30)."  Guthaben:".$result5." Euro";
					}
				else
					{	
		   			$ergebnis=$nummer." ".str_pad("(".$result1.")",30)."  Datenmenge : ".$result4g." Guthaben:".$result5." Euro";
					}
				}			
			}
		if (!feof($handle))
		 	{
      		$ergebnis="Fehler: unerwarteter fgets() Fehlschlag\n";
	    	}	
		fclose($handle);
		}
	else
		{
		$ergebnis="Handle nicht definiert\n";
 		$phone1ID = CreateVariableByName($parentid, "Phone_".$nummer, 3);
  		$phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Summary", 3);
    	$phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_User", 3);
     	$phone_Date_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Date", 3);
     	$phone_unchangedDate_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_unchangedDate", 3);
     	$phone_Bonus_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Bonus", 3);
     	$phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Volume", 2);
     	$phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_VolumeCumm", 2);
     	$phone_nCost_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Cost", 2);
     	$phone_nLoad_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Load", 2);
    	$phone_Cost_ID = CreateVariableByName($parentid, "Phone_Cost", 2);
     	$phone_Load_ID = CreateVariableByName($parentid, "Phone_Load", 2);
     	$phone_CL_Change_ID = CreateVariableByName($parentid, "Phone_CL_Change", 2);
		}
	//$ergebnis.=$result4g." ".$result4v." ".$result4f;
	SetValue($phone_Summ_ID,$ergebnis);
	return $ergebnis;
	}



?>