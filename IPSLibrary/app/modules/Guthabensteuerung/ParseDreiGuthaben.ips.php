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

/***************************************************************************** 
 *
 * Config einlesen
 *
 *********************************************************************************************/

	$GuthabenConfig = get_GuthabenConfiguration();
	$GuthabenAllgConfig = get_GuthabenAllgemeinConfig();

	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	
	echo "Verzeichnis für Macros     : ".$GuthabenAllgConfig["MacroDirectory"]."\n";
	echo "Verzeichnis für Ergebnisse : ".$GuthabenAllgConfig["DownloadDirectory"]."\n\n";
	/* "C:/Users/Wolfgang/Documents/iMacros/Downloads/ */

/*********************************************************************************************
 * 
 * Logging aktivieren
 *
 *********************************************************************************************/

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_Guthaben.csv",$input);

	if ($_IPS['SENDER']=="Execute")
		{
		/* Logging Einstellungen zum Debuggen */
		
		//$ausgeben=true; $ergebnisse=true; $speichern=true;				// Debug
		//$ausgeben=false; $ergebnisse=false; $speichern=false;				// Operation
		$ausgeben=true; $ergebnisse=true; $speichern=false;
		}
	else
		{	
		$ausgeben=false; $ergebnisse=false; $speichern=false;
		}

/******************************************************
 *
 *                        RUN
 *
 * Parse textfiles, die von iMacro generiert wurden
 *				
 *
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
			$ergebnis1=parsetxtfile($GuthabenAllgConfig,$TelNummer);
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
		echo "========================================================\n";
		echo "Execute, Script ParseDreiGuthaben wird ausgeführt:\n\n";
		echo "  Ausgabe Ergebnis parsetxtfile :\n";
		echo "  -------------------------------\n";
		echo $ergebnis;
		echo "  Ausgabe Status der aktiven SIM Karten :\n";
		echo "  ---------------------------------------\n";
		$ergebnis1="";
		foreach ($GuthabenConfig as $TelNummer)
			{
			//print_r($TelNummer);
			$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

			$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
			$dateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Date", 3);
			$ldateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_loadDate", 3);
			$udateID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_unchangedDate", 3);
			$userID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
			if (strtoupper($TelNummer["STATUS"])=="ACTIVE") 
				{
				$ergebnis1.="    ".$TelNummer["NUMMER"]."  ".str_pad(GetValue($userID),30)."  ".str_pad(GetValue($dateID),30)." ".str_pad(GetValue($udateID),30)." ".GetValue($ldateID)."\n";
				}
			//echo "Telnummer ".$TelNummer["NUMMER"]." ".$udateID."\n";
			}
		echo "  Nummer                Name                                letztes File von       letzte Aenderung Guthaben    letzte Aufladung\n";
		echo $ergebnis1;
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




/*************************************************************************************************
 *
 * Function Parse textfile
 *
 * bestimmte Textfelder/Marker finden und denn Wert dahinter auslesen und einer Variablen zuordnen
 *
 * $result1 	Username
 * $result2 	Telefonnummer
 * $result3 	Datum der letzten Aktualisierung (wenn vorhanden)
 * $result4v	MB verbraucht
 * $tarif1  	Name des Tarifs
 * $lastbill 	letzte Rechnungsperiode
 * $result5 	Guthaben oder Bertrag aktuelle Rechnung
 * $result7 	Gültigkeit des aktuellen Guthabens
 *
 ************************************************************************************************************/

function parsetxtfile($fileconfig, $config)
	{
	global	$ausgeben,$ergebnisse,$speichern;

    //echo "Parse Textfile :\n";
    //print_r($config);
	$verzeichnis=$fileconfig["DownloadDirectory"];
	$nummer=$config["NUMMER"];
	if (isset($config["TYP"])) $typ=strtoupper($config["TYP"]);
    else $typ="Drei";
	//$startdatenguthaben=7;
	$startdatenguthaben=0;
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

	$handle = @fopen($verzeichnis."/report_dreiat_".$nummer.".txt", "r");
	$result1="";$result2="";$result3="";$result4="";$result5="";$result6="";
	$result4g="";$result4v="";$result4f="";  $result7=""; $result8="";
	$entgelte=false;
	unset($tarif); $tarif1="";
	$postpaid=false;

	if ($handle)
		{
		if ($ausgeben) 
			{
			//echo "Rückmeldung fopen : "; print_r($handle); echo "\n";
			echo "Aufruf von parsetxtfile mit folgender Config: ".$config["NAME"]." / ".$config["NUMMER"]." (".$config["PASSWORD"].")  -> ".$config["TARIF"]."   ";
			if ($typ=="DREI") echo "Drei prepaid oder postpaid Karte.\n";
			else echo "Alternative erkannt, UPC.\n";
            if ($ergebnisse) echo "==========================================================================================================================\n";
			//print_r($config);
			}
		
		while (($buffer = fgets($handle, 4096)) !== false) /* liest bis zum Zeilenende */
			{
			/* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */
	
			/********** zuerst den User ermitteln, steht hinter Willkommen 
			 *
			 */
			if ($ausgeben) echo $buffer;			// zeilenweise ausgeben
			if(preg_match('/Willkommen/i',$buffer))
				{
				$pos=strpos($buffer,"kommen");
				if (($pos!=false) && !(preg_match('/Troy/i',$buffer)))
					{
					$posEnde=strpos($buffer,"Abmelden");
					if ($posEnde !== false) $result1=trim(substr($buffer,$pos+7,($posEnde-7-$pos)));		/* UPC klebt am Ende des Usenamens ein Abmelden dran */
					else $result1=trim(substr($buffer,$pos+7,200));
					if ($ergebnisse) echo "*********Ausgabe User : ".$result1."\n<br>";
					}
				}

			/********** dann die rufnummer, am einfachsten zu finden mit der 0660er (Drei) oder 0676er (TMobile) oder 0678 (UPC) Kennung 
			 *
			 */
			if ( (preg_match('/0660/i',$buffer)) or (preg_match('/0676/i',$buffer)) or (preg_match('/0678/i',$buffer)) )
				/* manchmal haben wir die Rufnummer mitgenommen, geht auch jetzt für UPC */
				{
				$result2=trim($buffer);
				$fnd1=strpos($result2,"0");
				if ($fnd1>0) $result2=substr($result2,$fnd1);
				if ($ergebnisse) echo "*********Ausgabe Nummer : ".$result2."\n<br>";
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
				//echo "***Aktualisierung gefunden : ".$pos."  ".$Ende."\n";
				if (strpos($buffer,"Abrechnung")!==false)
					{
					$Ende=strpos($buffer,"Abrechnung");
					$postpaid=true;
					}
				if ($pos!=false)
					{
					$result3=trim(substr($buffer,$pos+16,$Ende-$pos-16));
					if ($ergebnisse) echo "*********Letzte Aktualisierung : ".$result3."\n";
					}
				if (isset($tarif)==false)
					{
					/* nur beim ersten mal machen */
					$buffer=fgets($handle, 4096);	// hier koennte auch das Datum der letzten Aufladung stehen, danach gleich bearbeiten
					if ($ausgeben) echo $buffer;
					if ( !(preg_match('/Aufladung/i',$buffer)) )
						{
						$buffer2=fgets($handle, 4096);
						if ($ausgeben) echo $buffer2;
						$buffer3=fgets($handle, 4096);
						if ($ausgeben) echo $buffer3;
						$tarif=json_encode($buffer.$buffer2.$buffer3);
						//echo "****Tarif :".$buffer.$buffer2.$buffer3;
						$order   = array('\r\n', '\n', '\r');
						$replace = '';
						$tarif1 = json_decode(str_replace($order, $replace, $tarif));
						if ($ergebnisse) echo "********* Tarif : ".$tarif1."\n";
						}
					}
				}
			/********* dann der Name des Tarifs
			 *
			 *********************/				
			//echo "-----------------------------------------\n";
			//echo $buffer;
			if ( (preg_match('/Wertkarte/i',$buffer)) && !(preg_match('/Wertkarte im/i',$buffer)) )
				{
				if (strpos($buffer,"Wertkarte")==0)
					{
					$buffer = fgets($handle, 4096); if ($ausgeben) echo $buffer;
					$tarif1=trim($buffer);
					if ($ergebnisse) echo "********* Tarif : ".$tarif1."\n";
					}
				}
			$posTarif=strpos($buffer,"Tarif:");
			if ($posTarif !== false)
				{  // anscheind etwas gefunden, Tarif: wird bei UPC verwendet */	
				$tarif1=trim(substr($buffer,6,100));
				if ($ergebnisse) echo "********* Tarif : ".$tarif1."\n";				
				}
				
			/********* dann das Datum der letzten Aufladung
			 *
			 *********************/
			if(preg_match('/Aufladung/i',$buffer))
		   		{
	   			$pos=strpos($buffer,"Aufladung:");
	   			$Ende=strpos($buffer,"\n");
				//echo "***Aufladung gefunden : ".$pos."  ".$Ende."\n";
				if ($pos!=false)
					{
					$result8=trim(substr($buffer,$pos+11,$Ende-$pos-11));
					}
				if ($ergebnisse) echo "********* letzte Aufladung am : ".$result8." \n";
				}

			/************ Ermittlung verfügbares Datenguthaben
			 *            Suchen nach erstem Auftreten von MB, 
			 *            die MBit und den Roaming Disclaimer und die Tarifinfo ausnehmen
			 *
			 * result4g gekaufte Datenmenge, Paket
			 * result4v verbrauchte Datenmenge
			 * result4f noch zur Verfügung stehendes Datenvolumen
			 *
			 *****************/
			//if (preg_match('/MB/i',$buffer))
			if ( (preg_match('/MB/i',$buffer)) and ($result4g=="") and !preg_match('/MBit/i',$buffer) and !preg_match('/MB,/i',$buffer) 
					and !preg_match('/MMS/i',$buffer) and !preg_match('/Taktung/i',$buffer) )         /* verfügbares Datenvolumen, was gibt es grundsaetzlich, erstes MB, aber nicht MBit */
				{
				$pos=strpos($buffer,"MB");
				if ($pos)
					{
					$i=$pos-2;
					while ( (is_numeric($buffer[$i]) or ($buffer[$i]==".")) && ($i != 0)) $i--;
					//echo "***".$i."  ".$pos."\n";
					$result4g=trim(substr($buffer,$i,$pos-$i+2));
					if (preg_match('/Datenmenge/i',$result4g))
						{
						$result4g=substr($result4g,10,40);
						}
					if ($ergebnisse) echo "*********Datenmenge Ticket: ".$result4g."\n<br>";
					}
				}

			if ( ( (preg_match('/MB verbr/i',$buffer)) or (preg_match('/MB gesamt verbr/i',$buffer)) or (preg_match('/MB verbraucht Inland/i',$buffer)) ) && 
					!( (preg_match('/MB verbraucht EU/i',$buffer)) ) )
				{
				$pos=strpos($buffer,"MB");
				if ($pos)
					{
					$i=$pos-2;
					while ( ( (is_numeric($buffer[$i])) or ($buffer[$i]==".") ) && ($i != 0)) $i--;
					//echo "***".$i."  ".$pos."\n";
					$result4v=trim(substr($buffer,$i,$pos-$i+3));
					if ($ergebnisse) echo "*********verbraucht : ".$result4v."\n<br>";
					}
				}

			/************************** Ermittlung verfügbares Datenguthaben */
			if (preg_match('/MB frei/i',$buffer))                       /* verbrauchtes Datenvolumen, das heisst was habe ich noch */
				{
				$result4f=trim(substr($buffer,$startdatenguthaben,200));
				if ($ergebnisse) echo "*********frei : ".$result4f."\n<br>";
				}
				
			if (preg_match('/unlimitiert/i',$buffer))
				{
				$result4g="99999 MB";
				$result4f="99999 MB frei";
				$result4v=" 0 MB verbraucht";
				if ($ergebnisse) echo "*********frei : ".$result4f."\n<br>";
				}

			/************************ Gültigkeit des Guthabens */
			if ( (preg_match('/bis:/i',$buffer)) && ($result7=="") )  // nur das erste Mal 
				{
				$pos=strpos($buffer,"bis:");
				$result7=trim(substr($buffer,$pos+4,200));
				$pos1=strpos($result7,".")+1;
				$pos2=strpos(substr($result7,$pos1),".")+1;
				if ($ergebnisse) echo "*** erstes bis : ".$result7."   ".$pos."  ".$pos1."  ".$pos2."\n";
				if ( ($pos1) && ($pos2) )
					{
					$result7=substr($result7,0,$pos1+$pos2+4);
					if ($ergebnisse) echo "*********Gültig bis : ".$result7."\n<br>";
					}
				}
				
			/************************ Erkennung Postpaidvertrag */	
			if (preg_match('/Abrechnungszeitraum:/i',$buffer))
				{
				$pos=strpos($buffer,"-");
				if ($pos)
					{ // wenn ein bis Zeichen ist das ein hinweis auf postpaid System
					$result7=trim(substr($buffer,$pos+1,200));
					$postpaid=true;
					if ($ergebnisse) 
						{
						echo "*********Gültig bis : ".$result7."\n<br>";
						echo "*********Postpaidvertrag (1).\n";
						}
					}
				}
			$posPostpaid=strpos($buffer,"Verbleibende Tage:");
			if ($posPostpaid !== false)
				{  // bei UPC gibt es kein Ende der Abrechnungsperiode, aber verbleibende Tage, auch gut 
				$pos=strpos($buffer,":");
				if ($pos)
					{ // kein hinweis auf postpaid System, aber Abrechnungsperiode gültig bis
					$tage=(integer)trim(substr($buffer,$pos+1,4));
					$result7=date("d.m.Y",(time()+(60*60*24*$tage)) );
					if ($ergebnisse) 
						{
						echo "*********Gültig bis : ".$result7."\n<br>";
						echo "*********Postpaidvertrag (3).\n";
						}
					}
				}
			$posPostpaid=strpos($buffer,"Rechnung");
			if ($posPostpaid !== false)
				{  // anscheind etwas gefunden, Rechnung wird bei UPC verwendet */	
				$posDatum=strpos($buffer,"Datum zeit");
				if ($posDatum !== false)				
					{ /* interessant, uebernaechste Zeile holen */
					$buffer = fgets($handle, 4096); if ($ausgeben) echo $buffer;
					$buffer = fgets($handle, 4096); if ($ausgeben) echo $buffer;
					$pos=strpos($buffer,"-");
					if ($pos)
						{ // wenn ein bis Zeichen ist das ein hinweis auf postpaid System
						$lastbill=trim(substr($buffer,$pos+1,200));
						$postpaid=true;
						if ($ergebnisse) 
							{
							echo "*********Gültig bis : ".$lastbill."\n<br>";
							echo "*********Postpaidvertrag (2).\n";
							}
						}
					}
				}
				

			/************************** 
			 * Ermittlung des Guthabens, oder zusätzlicher Verbindungsentgelte 
			 *
			 * entweder wird haben: oder Guthaben gefunden, Bearbeitung eigentlich ähnlich
			 *
			 *******************/
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
					if ($ausgeben) echo $buffer;
					$Ende=strpos($buffer,",")-3;       /* Eurozeichen laesst sich nicht finden */
					if ($Ende<0) $Ende=0;
					$result5=trim(substr($buffer,$Ende,6));
					if ($ergebnisse) echo "*********Geldguthaben : ".$result5." \n<br>";
					}
				}
			if ( (preg_match('/Guthaben/i',$buffer)) && !(preg_match('/Guthaben laden/i',$buffer)) )
				{
				//echo $buffer;
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
					if ($ausgeben) echo $buffer;
					$pos=strpos($buffer,",");       /* Eurozeichen laesst sich nicht finden */
                    $pos1=strpos($buffer,"€");
                    $i=0;
                    if ($pos1==false)           // kein Eurozeichen
                        {
                        if ($pos!=false)        // wenn Komma aber kein Eurozeichen
                            {
                            $i=$pos;
                            $len=3;     // Komma plus zwei Kommastellen
                            }
                        }
                    else                        // Eurozeichen gefunden 
                        {
                        if ($pos==false)        // wenn kein Komma aber Eurozeichen
                            {
                            $len=0;         
                            if ($pos1<3) 
                                { 
                                $result5="0"; 
                                $i=0; 
                                if ($ergebnisse) echo "*********Guthaben : ".$result5." \n<br>";
                                }
                            else $i=--$pos1;
                            $pos=$pos1;
                            }
                        else                    // Eurozeichen und Komma gefunden
                            {
                            //if ($ergebnisse) echo "*** Eurozeichen und Komma gefunden.\n";
                            $len=3;
                            $i=$pos;
                            }
                        }
                    if ($i>0)
                        {
	    				while ( ( (is_numeric($buffer[$i]))  or ($buffer[$i]==",") ) && ($i != 0)) 
                            {
                            $i--;
				    	    //echo "***".$i."  ".$pos."\n";
                            }
                        //if ($ergebnisse) echo "***".$i."  ".$pos."   ".$len."\n";
					    $result5=trim(substr($buffer,$i,$pos-$i+$len));
					    if ($ergebnisse) echo "*********Guthaben : ".$result5." \n<br>";
                        }
					}
				}
			if ($entgelte==true)
				{
				$entgelte=false;
				$Ende=strpos($buffer,",");
				$result5=trim(substr($buffer,0,$Ende+3));
				if ($ergebnisse) echo "*********Geldguthaben : ".$result5." \n<br>";
				}
			if (preg_match('/Verbindungsentgelte:/i',$buffer))
				{
				$entgelte=true;
				}
			if (preg_match('/Aktuelle Kosten:/i',$buffer))
				{
				$pos=strpos($buffer,"Kosten:");
				$Ende=strpos($buffer,",");
				if ( ($pos>0) && ($Ende >0) )
					{
					$result5=trim(substr($buffer,$pos+7,$Ende-$pos+3+7));
					if ($ergebnisse) echo "*********Rechnung : ".$result5." \n<br>";
					}
				}				
				
			}  /* ende while buffer schleife */
			

		if ($result1=="") $result1=$config["TARIF"];	// wenn der Username nicht gefunden wurde einen Ersatzwert nehmen
		else $result1.=$tarif1;							// wenn Username gefunden wurde gleich auch mit dem ermittelten Tarif zusammanhaengen
		
		//$ergebnis="User:".$result1." Nummer:".$result2." Status:".$result4." Wert vom:".$result3." Guthaben:".$result5."\n";
		if ($ausgeben) echo "\n-----------------------------\n";
		if ($ergebnisse) 
			{
			echo "User:".$result1." Nummer:".$result2." Wert vom:".$result3." Letzte Aufladung ".$result8." Guthaben:".$result5." Tarif: ".$tarif1."\n";
			echo "Status:".$result4."   ".$result6." Gesamt Ticket : ".$result4g." Frei : ".$result4f." Verbraucht : ".$result4v." Gültig bis : ".$result7."\n";
			echo "\n-----------------------------\n";
			}
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
		if ($speichern) echo "--> ".IPS_GetName($phone_User_ID)." : ".$result1."\n";
		//SetValue($phone_Status_ID,$result4);   /* die eigentlich interessante Information */
		//echo ":::::".$result4."::::::\n";
 		SetValue($phone_Date_ID,$result3);
		if ($speichern) echo "--> ".IPS_GetName($phone_Date_ID)." : ".$result3."\n";
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
		if ($speichern) 
			{
			echo "--> ".IPS_GetName($phone_CL_Change_ID)." : ".GetValue($phone_CL_Change_ID)."\n";
			echo "--> ".IPS_GetName($phone_Cost_ID)." : ".GetValue($phone_Cost_ID)."\n";
			echo "--> ".IPS_GetName($phone_nCost_ID)." : ".GetValue($phone_nCost_ID)."\n";
			echo "--> ".IPS_GetName($phone_unchangedDate_ID)." : ".GetValue($phone_unchangedDate_ID)."\n";
			echo "--> ".IPS_GetName($phone_Bonus_ID)." : ".$result5."\n";
			}						
						
		if ($result8!="")
  			{
			SetValue($phone_loadDate_ID,$result8);
			if ($speichern) echo "--> ".IPS_GetName($phone_loadDate_ID)." : ".$result8."\n";
			}

		/* Datenvolumen Auswertung, result4 ist geschrieben wenn in einer Zeile gespeichert und die Auswertung am Ende hier gemacht werden kann
		 *
		 */
		
		if ($result4!="")
			{
			$Anfang=strpos($result4,"verbraucht")+10;
			$Ende=strpos($result4,"frei");
			$result6=trim(substr($result4,($Anfang),($Ende-$Anfang)));

			$Anfang=strpos($result4,"bis:")+5;
			$result7=trim(substr($result4,($Anfang),20));
			}

		/* Datenvolumen des Tickets wurde ermittelt, in result6 eine Zusammenfassung erstellen. 
		 *	 
		 *	$result4g="99999 MB";
		 *	$result4f="99999 MB frei";
		 *	$result4v=" 0 MB verbraucht";
		 *
		 */

		 if ($result4g!="")
			{
			if ($result4f!="")		
				{    // noch freies Datenvolumen (Restvolumen) wurde angegeben
				/*`hier wird das aktuelle Datenvolumen geschrieben */
				//$result6=" von ".$result4g." wurden ".$result4v." verbraucht und daher sind  ".$result4f.".noch frei.";
				$result6=" von ".$result4g." sind ".$result4f;
				$Ende=strpos($result4f,"MB");
				$restvolumen=(float)trim(substr($result4f,0,($Ende-1)));
				}
			else
				{
				$Ende=strpos($result4g,"MB");
				$ticketvolumen=(float)trim(substr($result4g,0,($Ende-1)));
				$Ende=strpos($result4v,"MB");
				$verbrauchtesvolumen=(float)trim(substr($result4v,0,($Ende-1)));
				$restvolumen=$ticketvolumen-$verbrauchtesvolumen;
				$result6=" von ".$result4g." sind ".$restvolumen." MB frei";
				}
			if ($ergebnisse) echo "Restvolumen ist : ".$restvolumen." MB \n";
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
		else
			{
			$result6=" verbraucht sind ".$result4f;
			}	
		if ($speichern) 
			{
			echo "--> ".IPS_GetName($phone_Volume_ID)." : ".GetValue($phone_Volume_ID)."\n";
			echo "--> ".IPS_GetName($phone_VolumeCumm_ID)." : ".GetValue($phone_VolumeCumm_ID)."\n";
			}

		/********
		 * textuelles Ergebnis zusammenfassen beginnen
		 *
		 * unterscheiden zwischen postpaid und prepaid: postpaid hat fixe Abrechnungsperiode, prepaid wenn aktiv eine Gültigkeitsdauer bis
		 *
		 *************************************/
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
		$ergebnis="Handle nicht definiert. Kein Ergebnis des Macroscripts erhalten.\n";
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
	if ($speichern) echo "--> ".IPS_GetName($phone_Summ_ID)." : ".$ergebnis."\n";
	SetValue($phone_Summ_ID,$ergebnis);
	return $ergebnis;
	}



?>