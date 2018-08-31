<?

/*
	 * @defgroup Gartensteuerung
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 *
	 *
	 * @file          Gartensteuerung.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/


/************************************************************
 *
 * Gartensteuerung Library
 *
 * verschiedene Funktionen um innerhalb und ausserhalb des Moduls Autosteuerung eine Giessanlage anzusteuern
 *
 * construct		mit bereits zahlreichen Ermittlungen, wie zB die regenStatistik, regenStand2h, regenStand48h
 *
 * getConfig_aussentempID()         $this->variableTempID=get_aussentempID();  oder eine OID 
 * getConfig_raincounterID()        $this->variableID=get_raincounterID();     oder eine OID oder ein Wert aus CustomComponents
 *
 * Giessdauer		Berechnung der Giessdauer anhand der Durchschnittstemperatur und Regenmenge
 *
 * listRainEvents	Zusammenfassung der vergangenen Regenschauer in lesbare Funktionen/Zeilen
 *                  es können auch mehr als 10 Tage angefordert wereden, bleiben aber nicht als Werte der Class
 * listEvents		Ausgabe der Nachrichten Events
 *
 * zusätzliche Funktionen für Regen Statistik (immer 10 Jahre Werte):
 *
 * Auswertung Regenmenge als 1/7/30/30/360/360 Statistik 
 * Auswertung Regenmenge als Aggregation pro Monat
 * Auswertung Regendauer (benötigt inkrementelle Werte aus CustomComponents)
 *
 ****************************************************************/
 
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

class Gartensteuerung
	{
	
	private 	$archiveHandlerID;
	private		$debug;
	private		$tempwerte, $tempwerteLog, $werteLog, $werte;
	private		$variableTempID, $variableID;
	private 	$GartensteuerungConfiguration;
	
	public 		$regenStatistik;
	public 		$letzterRegen, $regenStand2h, $regenStand48h;
	
	public      $categoryId_Auswertung, $categoryId_Register,$categoryId_Statistiken;
    public 		$GiessTimeID,$GiessDauerInfoID;
    public      $StatistikBox1ID, $StatistikBox2ID, $StatistikBox3ID;                   // html boxen für statistische Auswertungen
	
	public 		$log_Giessanlage;									// logging Library class

    private     $RainRegisterIncrementID;                           // in CustomComponent gibt es einen Incremental Counter
    public      $DauerKalendermonate, $RegenKalendermonate;         // Ausertung Regendauer (wenn inkrementell da) und Regenmenge der letzten 10 Jahre


	/******************
	 *
	 * Vorbereitung und Ermittlung der Basisinformationen wie 
	 *
	 * Übergabe der Konfiguration, als Funktion, hat den Vorteil und funktioniert auch mit RemoteAccess
	 *   $this->variableTempID=get_aussentempID();  oder eine OID 
	 *   $this->variableID=get_raincounterID();     oder eine OID oder ein Wert aus CustomComponents
	 *
	 * $this->regenStatistik	wird zweimal berechnet, in construct und in listRainEvents
     * hier berechnen um für die Giessdauerberechung bereits den Wert für letzten Regen die letzten 2 und 48 Stunden zu haben
     *
     * aus dem Archive werden sich nur die letzten 2 Tage für Temperatur und die letzten 10 Tage für Regen genommen
	 *
     * es werden in Program.IPSLibrary.data.modules.Gartensteuerung Kategorien angelegt
     *          Gartensteuerung-Auswertung
     *          Statistiken
     *
	 ************************************************/
		
	public function __construct($starttime=0,$starttime2=0,$debug=false)
		{
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager)) 
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
			}
		$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
		$this->categoryId_Auswertung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
		$this->categoryId_Register  	= CreateCategory('Gartensteuerung-Register', $CategoryIdData, 200);
        $this->categoryId_Statistiken	= CreateCategory('Statistiken',   $CategoryIdData, 200);
        echo "Gartensteuerung Kategorie Data ist : ".$CategoryIdData."   (".IPS_GetName($CategoryIdData).")\n";
        echo "Gartensteuerung Kategorie Data.Gartensteuerung-Register ist : ".$this->categoryId_Register."   (".IPS_GetName($this->categoryId_Register).")\n";
        
		$this->GiessTimeID	            = @IPS_GetVariableIDByName("GiessTime", $this->categoryId_Auswertung); 
		$this->GiessDauerInfoID	        = @IPS_GetVariableIDByName("GiessDauerInfo",$this->categoryId_Auswertung);
		//echo "GiesstimeID ist ".$this->GiessTimeID."\n";

	    $this->StatistikBox1ID			= @IPS_GetVariableIDByName("Regenmengenkalender"   , $this->categoryId_Statistiken); 
	    $this->StatistikBox2ID			= @IPS_GetVariableIDByName("Regendauerkalender"   , $this->categoryId_Statistiken); 
	    $this->StatistikBox3ID			= @IPS_GetVariableIDByName("Regenereignisse" , $this->categoryId_Statistiken);   
		$this->GartensteuerungConfiguration=getGartensteuerungConfiguration();

		$object2= new ipsobject($CategoryIdData);
		$object3= new ipsobject($object2->osearch("Nachricht"));
		$NachrichtenInputID=$object3->osearch("Input");
		$this->log_Giessanlage=new Logging("C:\Scripts\Log_Giessanlage2.csv",$NachrichtenInputID,IPS_GetName(0).";Gartensteuerung;");

		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$this->debug=$debug;
		$endtime=time();
		if ($starttime==0)  { $starttime=$endtime-60*60*24*2; }  /* die letzten zwei Tage Temperatur*/
		if ($starttime2==0) { $starttime2=$endtime-60*60*24*10; }  /* die letzten 10 Tage Niederschlag*/

		/* getConfiguration */
		$this->getConfig_aussentempID();
		$this->getConfig_raincounterID();
		$Server=$this->getConfig_RemoteAccess_Address();

		if ($this->debug)
			{
			echo"--------Class Construct Giessdauerberechnung:\n";
			}
		If ($Server=="")
			{
  			$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$this->tempwerteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->variableTempID, $starttime, $endtime,0);		
	   		$this->tempwerte = AC_GetAggregatedValues($this->archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);	/* Tageswerte agreggiert */
			$this->werteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
		   	$this->werte = AC_GetAggregatedValues($this->archiveHandlerID, $this->variableID, 1, $starttime2, $endtime,0);	/* Tageswerte agreggiert */
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$this->archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$this->tempwerteLog = $rpc->AC_GetLoggedValues($this->archiveHandlerID, $this->variableTempID, $starttime, $endtime,0);		
   			$this->tempwerte = $rpc->AC_GetAggregatedValues($this->archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);
			$this->werteLog = $rpc->AC_GetLoggedValues($this->archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
			$this->werte = $rpc->AC_GetAggregatedValues($this->archiveHandlerID, $this->variableID, 1, $starttime2, $endtime,0);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}

		/* Letzen Regen ermitteln, alle Einträge der letzten 10 Tage durchgehen */
		$this->letzterRegen=0;
		$this->regenStand2h=0;
		$this->regenStand48h=0;
		$regenStand=0;			/* der erste Regenwerte, also aktueller Stand */
		$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogget wurden */
		$regenAnfangZeit=0;
		$regenStandEnde=0;
		$regenEndeZeit=0;
		$regenMenge=0; $regenMengeAcc=0;
		$regenDauer=0; $regenDauerAcc=0;
		$vorwert=0; $vorzeit=0;
		$this->regenStatistik=array();
		$regenMaxStd=0;
		foreach ($this->werteLog as $wert)
			{
			if ($vorwert==0) 
		   		{ 
				if ($this->debug) {	echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				$regenStand=$wert["Value"];
				}
			else 
				{
				/* die erste Zeile erst mit dem zweiten Eintrag auffuellen ... */
				$regenMenge=round(($vorwert-$wert["Value"]),1);
				$regenDauer=round(($vorzeit-$wert["TimeStamp"])/60,0);
				if ($this->debug) { echo " ".$regenMenge."mm/".$regenDauer."min  "; }
				if (($regenMenge/$regenDauer*60)>$regenMaxStd) {$regenMaxStd=$regenMenge/$regenDauer*60;}
				if ( ($regenMenge<0.4) and ($regenDauer>60) ) 
					{
					/* gilt nicht als Regen, ist uns zu wenig, mehr ein nieseln */
					if ($regenEndeZeit != 0)
						{ 
						/* gilt auch als Regenanfang wenn ein Regenende erkannt wurde*/
						$regenAnfangZeit=$vorzeit;
						if ($this->debug) 
							{ 
							echo $regenMengeAcc."mm ".$regenDauerAcc."min ";						
							echo "  Regenanfang : ".date("d.m H:i",$regenAnfangZeit)."   ".round($vorwert,1)."  ".round($regenMaxStd,1)."mm/Std ";	
							}
						$this->regenStatistik[$regenAnfangZeit]["Beginn"]=$regenAnfangZeit;
						$this->regenStatistik[$regenAnfangZeit]["Ende"]  =$regenEndeZeit;
						$this->regenStatistik[$regenAnfangZeit]["Regen"] =$regenMengeAcc;
						$this->regenStatistik[$regenAnfangZeit]["Max"]   =$regenMaxStd;					
						$regenEndeZeit=0; $regenStandEnde=0; $regenMaxStd=0;
						}
					else
						{
						if ($this->debug) { echo "* "; }
						}	
					$regenMenge=0; $regenDauerAcc=0; $regenMengeAcc=0;
					} 
				else
					{
					if ($regenDauer<60)
						{  
						/* es regnet */
						$regenMengeAcc+=$regenMenge;
						$regenDauerAcc+=$regenDauer;				
						if ($this->debug) { echo $regenMengeAcc."mm ".$regenDauerAcc."min "; }
						if ($regenEndeZeit==0)
							{
							$regenStandEnde=$vorwert;
							$regenEndeZeit=$vorzeit;
							if ($this->debug) { echo "  Regenende : ".date("d.m H:i",$regenEndeZeit)."   ".round($regenStandEnde,1)."  ";	}
							}
						}
					else
						{
						/* es hat gerade begonnen zu regnen, gleich richtig mit Wert > 0.3mm */
						if ($regenEndeZeit != 0)
							{ 
							/* gilt auch als Regenanfang wenn ein Regenende erkannt wurde*/
							$regenMengeAcc+=$regenMenge;							
							$regenAnfangZeit=$vorzeit;
							if ($this->debug) 
								{ 
								echo $regenMengeAcc."mm ".$regenDauerAcc."min ";						
								echo "  Regenanfang : ".date("d.m H:i",$regenAnfangZeit)."   ".round($vorwert,1)."  ".round($regenMaxStd,1)."mm/Std ";	
								}
							$regenStatistik[$regenAnfangZeit]["Beginn"]=$regenAnfangZeit;
							$regenStatistik[$regenAnfangZeit]["Ende"]  =$regenEndeZeit;
							$regenStatistik[$regenAnfangZeit]["Regen"] =$regenMengeAcc;
							$regenStatistik[$regenAnfangZeit]["Max"]   =$regenMaxStd;					
							$regenEndeZeit=0; $regenStandEnde=0; $regenMaxStd=0;
							}
						else
							{
							if ($this->debug) { echo "* "; }
							}	
						} 								
					If ( ($this->letzterRegen==0) && (round($wert["Value"]) > 0) )
						{
						$this->letzterRegen=$wert["TimeStamp"];
						$regenStandEnde=$wert["Value"];
						if ($this->debug) { echo "Letzter Regen !"; }
			  			}				
					}	
				if ($this->debug) { echo "\n   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				}
			/* Regenstand innerhalb der letzten 2 Stunden ermitteln */
			if (((time()-$wert["TimeStamp"])/60/60)<2)
				{
				$this->regenStand2h=$regenStand-$wert["Value"];
				}
			/* Regenstand innerhalb der letzten 48 Stunden ermitteln */
			if (((time()-$wert["TimeStamp"])/60/60)<48)
				{
				$this->regenStand48h=$regenStand-$wert["Value"];
				}
			$vorwert=$wert["Value"];	
			$vorzeit=$wert["TimeStamp"];
			}  // Regenwerte der Reihe nach durchgehen
			
		if ($this->debug) { echo "\n\n"; }
		}
	
	
	/******************************************************************
	 *
	 * Einlesen der Konfiguration, manche Parameter können auch komplizierter als Funktion (customizing) 
	 * berechnet oder eingelesen werden. Wenn kein Eintrag in der Konfiguration dann Funktion aufrufen
	 *
	 ***************************************************************************/

	private function getConfig_aussentempID()
		{
		if ( isset($GartensteuerungConfiguration["AUSSENTEMP"])==true)
			{
			$this->variableTempID=$GartensteuerungConfiguration["AUSSENTEMP"];
			}
		else 
			{
			$this->variableTempID=get_aussentempID();
			}
		return($this->variableTempID); 		
		}

	private function getConfig_raincounterID()
		{
        $this->RainRegisterIncrementID=0;
		if ( isset($GartensteuerungConfiguration["RAINCOUNTER"])==true)
			{
            /* wenn die Variable in der Config angegeben ist diese nehmen, sonst die eigene Funktion aufrufen */
    		if ((integer)$GartensteuerungConfiguration["RAINCOUNTER"]==0) 
	    		{
                /* wenn sich der String als integer Zahl auflösen lässt, auch diese Zahl nehmen, Achtung bei Zahlen im String !!! */
    			echo "Alternative Erkennung des Regensensors, String als OID Wert für den RAINCOUNTER angegeben: \"".$GartensteuerungConfiguration["RAINCOUNTER"]."\". Jetzt in CustomComponents schauen ob vorhanden.\n";
	    		$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
		    	$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			    $CounterAuswertungID=@IPS_GetObjectIDByName("Counter-Auswertung",$CategoryIdData);
    			$this->RainRegisterIncrementID=@IPS_GetObjectIDByName($GartensteuerungConfiguration["RAINCOUNTER"],$CounterAuswertungID);
	    		echo "Check : Kategorie Data ".$CategoryIdData."  Counter Kategorie   ".$CounterAuswertungID."  Incremental Counter Register  ".$RegisterIncrementID."\n";
		    	if ( ($CounterAuswertungID==false) || ($RegisterIncrementID==false) ) $fatalerror=true;
           		$RegisterCounterID=@IPS_GetObjectIDByName($GartensteuerungConfiguration["RAINCOUNTER"]."_Counter",$CounterAuswertungID);
	        	echo "Check : Kategorie Data ".$CategoryIdData."  Counter Kategorie   ".$CounterAuswertungID."  Counter Register  ".$RegisterCounterID."\n";
    			if ( ($CounterAuswertungID==false) || ($RegisterCounterID==false) ) $fatalerror=true;
                $this->variableID=$RegisterCounterID;
                }    
			else $this->variableID=(integer)$GartensteuerungConfiguration["RAINCOUNTER"];
			}
		else 
			{		
			$this->variableID=get_raincounterID();
			}
		return($this->variableID); 		
		}

	private function getConfig_RemoteAccess_Address()
		{
		if ( isset($GartensteuerungConfiguration["REMOTEACCESSADR"])==true)
			{
			$Server=$GartensteuerungConfiguration["REMOTEACCESSADR"];
			}
		else 
			{			
			$Server=RemoteAccess_Address();
			}
		return ($Server);
		}

	/******************************************************************
	 *
	 * Berechnung Giessbeginn morgens und abends
	 *
	 ***************************************************************************/

    public function fromdusktilldawn($duskordawn=true)      // default ist Abends
        {
        $fatalerror=false;
		/* Beginnzeit Timer für morgen ausrechnen */
		$riseID = @IPS_GetObjectIDByName("Program",0);
		$riseID = @IPS_GetObjectIDByName("IPSLibrary",$riseID);
		$riseID = @IPS_GetObjectIDByName("data",$riseID);
		$riseID = @IPS_GetObjectIDByName("modules",$riseID);
		$riseID = @IPS_GetObjectIDByName("Weather",$riseID);
		$riseID = @IPS_GetObjectIDByName("IPSTwilight",$riseID);
		$riseID = @IPS_GetObjectIDByName("Values",$riseID);
		//$dawnID = @IPS_GetObjectIDByName("SunriseEndLimited",$dawnID);
        if ($duskordawn==true) $dawnID = @IPS_GetObjectIDByName("SunriseEnd",$riseID);
        else $dawnID = @IPS_GetObjectIDByName("SunriseBegin",$riseID);

		if ($dawnID > 0)
			{
			$dawn=GetValue($dawnID);
			$pos=strrpos($dawn,":");
			if ($pos==false) 
                {
                $fatalerror=true;
                }
            else 
                {
				$hour=(integer)substr($dawn,0,$pos);
				$minute=(integer)substr($dawn,$pos+1,10);
				if ($duskordawn==true) $startminuten=$hour*60+$minute-90;
                else $startminuten=$hour*60+$minute;
				$calcminuten=$startminuten-5;
				}
            }    
		if ( ($dawnID === false) || ($fatalerror) )    /* keine Dämmerungszeit verfügbar, default 16:00 */
			{
			$startminuten=16*60;
			$calcminuten=$startminuten-5;
			}
        return($startminuten);
        }	
		
	/******************************************************************
	 *
	 * Berechnung der Giessdauer 0, 10 oder 20 Minuten
	 *
	 ***************************************************************************/

	public function Giessdauer($GartensteuerungConfiguration)
		{

		//global $log_Giessanlage;

		$giessdauerVar=0;
	
		$Server=RemoteAccess_Address();
		if ($this->debug)
			{
			echo"--------Giessdauerberechnung:\n";
			}
		If ($Server=="")
			{
			$variableTempName = IPS_GetName($this->variableTempID);
			$variableName = IPS_GetName($this->variableID);
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$variableTempName = $rpc->IPS_GetName($this->variableTempID);
			$variableName = $rpc->IPS_GetName($this->variableID);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}

		//$AussenTemperaturGesternMax=get_AussenTemperaturGesternMax();
		$AussenTemperaturGesternMax=$this->tempwerte[1]["Max"];
		//$AussenTemperaturGestern=AussenTemperaturGestern();
		$AussenTemperaturGestern=$this->tempwerte[1]["Avg"];
	
		$letzterRegen=0;
		$regenStand=0;
		$regenStand2h=0;
		$regenStand48h=0;
		$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogged wurden */
		$regenStandEnde=0;
		$RegenGestern=0;
		/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */

		foreach ($this->werteLog as $wert)
			{
    		//echo "Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
			$regenStandAnfang=$wert["Value"];
			If (($letzterRegen==0) && ($wert["Value"]>0))
				{
				$letzterRegen=$wert["TimeStamp"];
				$regenStandEnde=$wert["Value"];
				}
			if (((time()-$wert["TimeStamp"])/60/60)<2)
	   			{
				$regenStand2h=$regenStandEnde-$regenStandAnfang;
				}
			if (((time()-$wert["TimeStamp"])/60/60)<48)
				{
				$regenStand48h=$regenStandEnde-$regenStandAnfang;
				}
			$regenStand=$regenStandEnde-$regenStandAnfang;
			}
		if ($this->debug)
			{
			echo "Regenstand 2h : ".$regenStand2h." 48h : ".$regenStand48h." 10 Tage : ".$regenStand." mm.\n";
			}
		$letzterRegen=0;
		$RefWert=0;
		foreach ($this->werte as $wert)
			{
			if ($RefWert == 0) { $RefWert=round($wert["Avg"]); }
			if ( ($letzterRegen==0) && (($RefWert)-round($wert["Avg"])>0) )
				{
		   		$letzterRegen=$wert["MaxTime"]; 		/* MaxTime ist der Wert mit dem groessten Niederschlagswert, also am Ende des Regens, und MinTime daher immer am Anfang des Tages */
				}
			}
		if ( isset($werte[1]["Avg"]) == true ) {	$RegenGestern=$werte[1]["Avg"]; }
		$letzterRegenStd=(time()-$letzterRegen)/60/60;

		if ($this->debug)
			{
			echo "Letzter erfasster Regenwert : ".date("d.m H:i",$letzterRegen)." also vor ".$letzterRegenStd." Stunden.\n";
	 		echo "Aussentemperatur Gestern : ".number_format($AussenTemperaturGestern, 1, ",", "")." Grad (muss > ".$GartensteuerungConfiguration["TEMPERATUR-MITTEL"]."° sein).\n";
 			if ($AussenTemperaturGesternMax>($GartensteuerungConfiguration["TEMPERATUR-MAX"]))
 				{
 				echo "Doppelte Giesszeit da Maximumtemperatur  : ".number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad groesser als ".$GartensteuerungConfiguration["TEMPERATUR-MAX"]." Grad ist.\n";
				}
			if (($letzterRegenStd/60/60)<50)
				{
				echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
					" mm und letzter Regen war aktuell vor ".number_format(($letzterRegenStd), 1, ",", "")." Stunden.\n";
				}
			else
				{
				echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
					" mm und letzter Regen war aktuell vor länger als 48 Stunden.\n";
				}
			echo "Regen letzte 2/48 Stunden : ".$regenStand2h." mm / ".$regenStand48h." mm \n\n";
			if ($regenStand48h<($GartensteuerungConfiguration["REGEN48H"]))
				{
				echo "Regen in den letzten 48 Stunden weniger als ".$GartensteuerungConfiguration["REGEN48H"]."mm.\n";
				}
			if ($regenStand<($GartensteuerungConfiguration["REGEN10T"]))
				{
				echo "Regen in den letzten 10 Tagen weniger als ".$GartensteuerungConfiguration["REGEN10T"]."mm.\n";
				}
			}

		if (($regenStand48h<($GartensteuerungConfiguration["REGEN48H"])) && ($AussenTemperaturGestern>($GartensteuerungConfiguration["TEMPERATUR-MITTEL"])))
			{ /* es hat in den letzten 48h weniger als xx mm geregnet und die mittlere Aussentemperatur war groesser xx Grad*/
			if (($regenStand2h)==0)
				{ /* und es regnet aktuell nicht */
				if ( ($AussenTemperaturGesternMax>($GartensteuerungConfiguration["TEMPERATUR-MAX"])) || ($regenStand<($GartensteuerungConfiguration["REGEN10T"])) )
					{ /* es war richtig warm */
					$giessdauerVar=20;
					}
				else
					{ /* oder nur gleichmässig warm */
					$giessdauerVar=10;
					}
				}
			}
		$textausgabe="Giessdauer:".GetValue($this->GiessTimeID)
			." Min. Regen 2/48/max Std:".number_format($regenStand2h, 1, ",", "")."mm/".number_format($regenStand48h, 1, ",", "")."mm/".number_format($regenStand, 1, ",", "")."mm. Temp mit/max: "
			.number_format($AussenTemperaturGestern, 1, ",", "")."/"
			.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
		$textausgabe2="Giessdauer:".GetValue($this->GiessTimeID)
			." Min. <br>Regen 2/48/max Std:".number_format($regenStand2h, 1, ",", "")."mm/".number_format($regenStand48h, 1, ",", "")."mm/".number_format($regenStand, 1, ",", "")."mm. <br>Temp mit/max: "
			.number_format($AussenTemperaturGestern, 1, ",", "")."/"
			.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
		SetValue($this->GiessDauerInfoID,$textausgabe2);
		if ($this->debug==false)
			{
			//$log_Giessanlage->message($textausgabe);
			}
		else
			{
			echo $textausgabe;
			}
		return $giessdauerVar;
		}

	/******************************************************************
	 *
	 * Ermittlung und Ausgabe der Regenschauer in den letzten Tagen
	 *
	 * soll auch in construct verwendet werden
	 * beschreibt und überschreibt $this->regenStatistik	
	 *
	 * Übergabeparameter, Anzahl der Kalendertage die evaluiert werden sollen
	 *
	 * es wird der Registerstand des Regensensors des aktuellen Eintrages mit dem Vorwert hinsichtlich Menge und Zeitabstand verglichen
	 * damit wird Regendauer und Regenmenge ermittelt
	 * Regenmenge und Regendauer bestimmt den maximalen Niederschlag pro Minute, hochgerechnet auf 1 Stunde
	 * eine Regenmenge <0.4 und Regendauer >60 Minuten wird ignoriert - Messfehler oder Nieseln
	 * beim Ersten erkannten Regen (wir bearbeiten zurück in die Verangenheit) wird das Regenende festgelegt
	 *
	 */

	public function listRainEvents($days=10)
		{
		$endtime=time();
		$starttime2=$endtime-60*60*24*$days;   /* die letzten x (default 10) Tage Niederschlag*/

		$Server=$this->getConfig_RemoteAccess_Address();
		if ($this->debug)
			{
			echo "\n";
			echo"--------Function List RainEvents:\n";
			}
		If ($Server=="")
			{
			$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = AC_GetLoggedValues($archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$this->archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}
		/* Letzen Regen ermitteln, alle Einträge der letzten x (default 10) Tage Niederschlag durchgehen */
		$regenStand=0;			/* der erste Regenwerte, also aktueller Stand */
		$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogget wurden */
		$regenAnfangZeit=0;
		$regenStandEnde=0;
		$regenEndeZeit=0;
		$regenMenge=0; $regenMengeAcc=0;
		$regenDauer=0; $regenDauerAcc=0;
		$vorwert=0; $vorzeit=0;
		$regenStatistik=array();
		$regenMaxStd=0;
		foreach ($werteLog as $wert)	/* Regenwerte Eintrag für Eintrag durchgehen */
			{
			if ($vorwert==0) 
				{ 
				if ($this->debug) {	echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				$regenStand=$wert["Value"];
				}
			else 
				{
				/* die erste Zeile erst mit dem zweiten Eintrag auffuellen ... */
				$regenMenge=round(($vorwert-$wert["Value"]),1);
				$regenDauer=round(($vorzeit-$wert["TimeStamp"])/60,0);
				if ($this->debug) 
					{
					if ( $regenDauer>(60*24*2) ) echo " ".$regenMenge."mm/>2Tage  "; 
					else echo " ".$regenMenge."mm/".$regenDauer."min  "; 
					}
				if (($regenMenge/$regenDauer*60)>$regenMaxStd) {$regenMaxStd=$regenMenge/$regenDauer*60;} // maximalen Niederschlag pro Stunde ermitteln
				if ( ($regenMenge<0.4) and ($regenDauer>60) ) 
					{
					/* gilt nicht als Regen, ist uns zu wenig, mehr ein nieseln */
					if ($this->debug) echo "  kurzes Nieseln ";
					if ($regenEndeZeit != 0)
						{ 
						/* gilt auch als Regenanfang wenn ein Regenende erkannt wurde*/
						$regenAnfangZeit=$vorzeit;
						if ($this->debug) 
							{ 
							echo $regenMengeAcc."mm ".$regenDauerAcc."min ";						
							echo "  Regenanfang : ".date("d.m H:i",$regenAnfangZeit)."   ".round($vorwert,1)."  ".round($regenMaxStd,1)."mm/Std ";	
							}
						$regenStatistik[$regenAnfangZeit]["Beginn"]=$regenAnfangZeit;
						$regenStatistik[$regenAnfangZeit]["Ende"]  =$regenEndeZeit;
						$regenStatistik[$regenAnfangZeit]["Regen"] =$regenMengeAcc;
						$regenStatistik[$regenAnfangZeit]["Max"]   =$regenMaxStd;					
						$regenEndeZeit=0; $regenStandEnde=0; $regenMaxStd=0;
						}
					else
						{
						if ($this->debug) { echo "* "; }
						}	
					$regenMenge=0; $regenDauerAcc=0; $regenMengeAcc=0;
					} 
				else
					{
					if ($regenDauer<60)
						{  
						/* es regnet */
						$regenMengeAcc+=$regenMenge;
						$regenDauerAcc+=$regenDauer;				
						if ($this->debug) { echo $regenMengeAcc."mm ".$regenDauerAcc."min "; }
						if ($regenEndeZeit==0)
							{
							$regenStandEnde=$vorwert;
							$regenEndeZeit=$vorzeit;
							if ($this->debug) { echo "  Regenende : ".date("d.m H:i",$regenEndeZeit)."   ".round($regenStandEnde,1)."  ";	}
							}
						}
					else
						{
						/* es hat gerade begonnen zu regnen, gleich richtig mit Wert > 0.3mm */
						if ($regenEndeZeit != 0)
							{ 
							/* gilt auch als Regenanfang wenn ein Regenende erkannt wurde*/
							$regenMengeAcc+=$regenMenge;							
							$regenAnfangZeit=$vorzeit;
							if ($this->debug) 
								{ 
								echo $regenMengeAcc."mm ".$regenDauerAcc."min ";						
								echo "  Regenanfang : ".date("d.m H:i",$regenAnfangZeit)."   ".round($vorwert,1)."  ".round($regenMaxStd,1)."mm/Std ";	
								}
							$regenStatistik[$regenAnfangZeit]["Beginn"]=$regenAnfangZeit;
							$regenStatistik[$regenAnfangZeit]["Ende"]  =$regenEndeZeit;
							$regenStatistik[$regenAnfangZeit]["Regen"] =$regenMengeAcc;
							$regenStatistik[$regenAnfangZeit]["Max"]   =$regenMaxStd;					
							$regenEndeZeit=0; $regenStandEnde=0; $regenMaxStd=0;
							}
						else
							{
							if ($this->debug) { echo "* "; }
							}	
						} 								
					}	
				if ($this->debug) { echo "\n   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				}
			$vorwert=$wert["Value"];	
			$vorzeit=$wert["TimeStamp"];
			}
		return ($regenStatistik);
		}

	/*******************
     *
     * Ausgabe der Regenereignisse (ermittelt mit listrainevents) mit Beginn, Ende, Dauer etc als html Tabelle zum speichern in einer hml Box
	 */

	public function writeRainEventsHtml($rainevents)
		{
        $html="";
    	$html.='<table frameborder="1" width="100%">';
	    $html.="<th> <tr> <td> Regenbeginn </td> <td> Regenende </td> <td> Dauer Min</td> <td> Menge mm </td> <td> max mm/Stunde </td> </tr> </th>";
    	//echo "\nRegenstatistik, letzte 100 Eintraege :\n";
	    foreach ($rainevents as $regeneintrag)
		    {
    		//echo "  Regenbeginn ".date("d.m H:i",$regeneintrag["Beginn"])."  Regenende ".date("d.m H:i",$regeneintrag["Ende"])." mit insgesamt ".number_format($regeneintrag["Regen"], 1, ",", "")." mm Regen. Max pro Stunde ca. ".number_format($regeneintrag["Max"], 1, ",", "")."mm/Std.\n";
		    $html.="<tr> <td>".date("D d.m H:i",$regeneintrag["Beginn"])."</td> <td>".date("d.m H:i",$regeneintrag["Ende"])."</td> <td>"
		        .number_format(($regeneintrag["Ende"]-$regeneintrag["Beginn"])/60, 0, ",", "")."</td> <td>".number_format($regeneintrag["Regen"], 1, ",", "")
    			."</td> <td>".number_format($regeneintrag["Max"], 1, ",", "")."</td> </tr>";
	    	}
    	$html.="</table>";
	    //echo "Und als html Tabelle :\n"; echo $html;	
        return($html);
        }

	/*
	 *   Ausgabe der Events/Nachrichten über die Giessanlage, wann wie lange eingeschaltet
	 *
	 */

	public function listEvents()
		{
		return ($this->log_Giessanlage->PrintNachrichten());
		}

	/*
	 *  Allerlei Auswertungen mit der Regenmenge. 
	 *  Ergebnisse der statistischen Auswertungen werden in der Klasse gespeichert und sind dann auch verfügbar
	 */

	public function getRainStatistics($debug=false)
		{
        $regenereignis=array();
        if ($this->RainRegisterIncrementID>0)
            {
            /* Es bibt ein inkrementales regenregister in der CustomComponents Auswertung. Da kann ich dann schön die Regendauer pro Monat auswerten
             * es werden auch die Regenereignisse gefunden.
             */
    		$endtime=time();
	    	$starttime2=$endtime-60*60*24*3650;   /* die letzten 10 Jahre Niederschlag*/
  		    //$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
    		$werteLog = AC_GetLoggedValues($this->archiveHandlerID, $RegisterIncrementID, $starttime2, $endtime,0);
            /* Auswertung von Agreggierten Werten funktioniert leider bei inkrementellen Countern nicht */

            $lasttime=time(); $lastwert=0; $init=true; $regen=0; $zeit=0;
            $i=0; $ende=true; $maxmenge=0;
			foreach ($werteLog as $wert)
				{
                if ($wert["Value"]!=0)
                   {
                   $regen+=$wert["Value"];
                   if ($init) 
                        {
                        //echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m H:i",$wert["TimeStamp"])."    ";
                        $regenereignis[$i]["Ende"]=$wert["TimeStamp"];
                        $init=false;    
                        }
                   else 
                        {
                        $zeit= ($lasttime-$wert["TimeStamp"])/60;                         
                        $menge=60/$zeit*$wert["Value"];
                        if ($menge>$maxmenge) $maxmenge=$menge;
    			        //echo number_format($zeit,1,",","")."Min ".number_format($menge,1,",","")."l/Std   ".$regen."\n   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m H:i",$wert["TimeStamp"])."    "; 
                        }       
                    if ($zeit>60) 
                        {
                        $regenereignis[$i]["Regen"]=$regen;
                        $regenereignis[$i]["Beginn"]=$lasttime;
                        $regenereignis[$i]["Max"]=$maxmenge;
                        $regen=0; $i++;
                        $regenereignis[$i]["Ende"]=$wert["TimeStamp"]; $maxmenge=0; 
                        }
                    $lastwert=$wert["Value"]; $lasttime=$wert["TimeStamp"]; 
                    }
				}
            $regenereignis[$i]["Regen"]=$regen;
            $regenereignis[$i]["Beginn"]=$lasttime;   
            $regenereignis[$i]["Max"]=$maxmenge;
            echo "\n";
    		}
            
        $kalendermonate=array();
        if (sizeof($regenereignis)==0) $regenereignis=$this->listRainEvents(1000);
        foreach ($regenereignis as $eintrag) 
            {
            //print_r($eintrag);
            $dauer = ($eintrag["Ende"]-$eintrag["Beginn"])/60/60;
            $monat=date("m.y",$eintrag["Ende"]);
            if (isset($kalendermonate[$monat])) $kalendermonate[$monat]+= $dauer;
            else $kalendermonate[$monat] = $dauer;
                
            if ($dauer>0)
                {
                /* einmalige Ereignise, 03 mm in 2 Stunden ingnorieren */
                if ($debug) echo "Anfang ".date("D d.m.y H:i",$eintrag["Beginn"])." bis Ende ".date("D d.m H:i",$eintrag["Ende"])." mit insgesamt "
                        .number_format($eintrag["Regen"], 1, ",", "")." mm Dauer ".number_format($dauer, 1, ",", "")." Stunden  Max :".number_format($eintrag["Max"], 1, ",", "").
                        " l/Std\n";
                }
            }  
        $this->DauerKalendermonate=$kalendermonate;

    	$RegisterCounterID=$this->variableID;
    	$endtime=time();
		$starttime2=$endtime-60*60*24*3650;   /* die letzten 3650 Tage Niederschlag*/
  		$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        unset($werteLog);
		$werteLog = AC_GetLoggedValues($archiveHandlerID, $RegisterCounterID, $starttime2, $endtime,0);
        //echo "Die insgesamt ".sizeof($werteLog)." Counter Werte anschauen und auswerten nach Kalendermonaten (12 Monate) und eine 1/7/30/30/360/360 Auswertung:\n";
        /* Regenwert pro Tag ermitteln. Gleiche Werte am selben Tag igorienen, spätester Wert pro Tag bleibt. */ 
        $zeit=date("d.m.y",time()); $init=true;
        $regenWerte=array();
		foreach ($werteLog as $wert)
			{
            if ($init) {$init=false; $lastwert=$wert["Value"];}
            $neuezeit=date("d.m.y",$wert["TimeStamp"]);
            if ($zeit != $neuezeit)
                 {
                 if ($wert["Value"]>$lastwert) 
                     {
                     //echo "*********".$wert["Value"]."*********";
                     foreach ($regenWerte as $timestamp => $entry) 
                          {
                          $regenWerte[$timestamp]+=$wert["Value"];
                          //echo $date."   ".$regenWerte[$date]."\n";
                          } 
                      }
                 //echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
                 $zeit=$neuezeit; $lastwert=$wert["Value"];
                 $regenWerte[$wert["TimeStamp"]]=$wert["Value"];
                 }
            }
        $init=true; $tageswert=false; $wochenwert=false; $monatswert=false; $monat2wert=false; $jahreswert=false; $jahr2wert=false;
        $tag=0; $woche=0; $monat=0; $monat2=0; $jahr=0; $jahr2=0;
        $kalendermonate=array();
        foreach ($regenWerte as $zeit => $regen) 
             {
             if ($init)
                  {
                  $lasttime=$zeit; $lastwert=$regen; $init=false; 
                  $inittime=time(); $initwert=$regen;
                  $startmonat=$regen; $aktmonat=date("m.y",$zeit);
                  }
             else
                  {        
                  //echo "  ".date("D d.m.y H:i",$lasttime)."   ".number_format(($lastwert-$regen), 1, ",", "");
                  $dauer=(($inittime-$zeit)/60/60/24);
                  //echo "    Wert für ".number_format((($inittime-$zeit)/60/60/24), 1, ",", "")." Tage ".($initwert-$lastwert);
                  //echo "\n";
                  if ( ($dauer>1) && !($tageswert) ) {$tageswert=true; $tag=$initwert-$lastwert; }
                  if ( ($dauer>7) && !($wochenwert) ) {$wochenwert=true; $woche=$initwert-$lastwert; }
                  if ( ($dauer>30) && !($monatswert) ) {$monatswert=true; $monat=$initwert-$lastwert; $init2wert=$lastwert; }
                  if ( ($dauer>60) && !($monat2wert) ) {$monat2wert=true; $monat2=$init2wert-$lastwert;  }
                  if ( ($dauer>360) && !($jahreswert) ) {$jahreswert=true; $jahr=$initwert-$lastwert; $init2wert=$lastwert; }
                  if ( ($dauer>720) && !($jahr2wert) ) {$jahr2wert=true; $jahr2=$init2wert-$lastwert;  }
                  if ($aktmonat != (date("m.y",$zeit))) {$kalendermonate[$aktmonat]=$startmonat-$regen; $startmonat=$regen; $aktmonat=date("m.y",$zeit);    }
                  $lasttime=$zeit; $lastwert=$regen;
                  }
            } 
        if ($jahr==0) { $jahr=$initwert-$lastwert; $init2wert=$lastwert;}   
        if ($jahr2==0) { $jahr2=$init2wert-$lastwert; }    
        $ergebnis="Auswertung 1: ".number_format($tag, 1, ",", "")."mm  7: ".number_format($woche, 1, ",", "")."mm 30: ".number_format($monat, 1, ",", "")."mm 30: "
                        .number_format($monat2, 1, ",", "")."mm 360: ".number_format($jahr, 1, ",", "")."mm 360: ".number_format($jahr2, 1, ",", "")."mm\n";      
        $this->RegenKalendermonate=$kalendermonate;
	    return ($ergebnis);
        }

	/*******************
     *
     * Ausgabe der Regenereignisse (ermittelt mit listrainevents) mit Beginn, Ende, Dauer etc als html Tabelle zum speichern in einer hml Box
	 */

	public function writeKalendermonateHtml()
		{
        $html="";
    	$html.='<table frameborder="1" width="100%">';
	    $html.="<th> <tr> <td> Regenbeginn </td> <td> Regenende </td> <td> Dauer Min</td> <td> Menge mm </td> <td> max mm/Stunde </td> </tr> </th>";
	    foreach ($kalendermonate as $regeneintrag)
		    {
    		//echo "  Regenbeginn ".date("d.m H:i",$regeneintrag["Beginn"])."  Regenende ".date("d.m H:i",$regeneintrag["Ende"])." mit insgesamt ".number_format($regeneintrag["Regen"], 1, ",", "")." mm Regen. Max pro Stunde ca. ".number_format($regeneintrag["Max"], 1, ",", "")."mm/Std.\n";
		    $html.="<tr> <td>".date("D d.m H:i",$regeneintrag["Beginn"])."</td> <td>".date("d.m H:i",$regeneintrag["Ende"])."</td> <td>"
		        .number_format(($regeneintrag["Ende"]-$regeneintrag["Beginn"])/60, 0, ",", "")."</td> <td>".number_format($regeneintrag["Regen"], 1, ",", "")
    			."</td> <td>".number_format($regeneintrag["Max"], 1, ",", "")."</td> </tr>";
	    	}
    	$html.="</table>";
	    //echo "Und als html Tabelle :\n"; echo $html;	
        return($html);
        }

	/*******************
     *
     * Ausgabe der Regenmenge/dauer (ermittelt mit listrainevents) als html Tabelle zum speichern in einer hml Box
	 */

	public function writeOverviewMonthsHtml($monthlyValues)
		{
        $startjahr=(integer)date("y",time());
        $minjahr=$startjahr;
        //print_r($monthlyValues);
        foreach ($monthlyValues as $date=>$regenmenge)
            {
            $MonatJahr=explode(".",$date);
            if ((integer)$MonatJahr[1]<$minjahr) $minjahr=(integer)$MonatJahr[1];
            $tabelle[(integer)$MonatJahr[0]][(integer)$MonatJahr[1]]=$regenmenge;
            }
        $html="";
        $html.='<table frameborder="1" width="100%">';
    	$html.="<th> <tr> <td> Monat </td> ";
        for ($i=$startjahr;$i>=$minjahr;$i--) {$html.="<td> ".$i." </td>";}
        $html.= " </tr> </th>";
	    for ($j=1;$j<=12;$j++)
            {
            $html.="<tr> <td>".$j."</td>";
            for ($i=$startjahr;$i>=$minjahr;$i--) 
                {
                if (isset($tabelle[$j][$i])==true) $html.="<td> ".number_format($tabelle[$j][$i],1,",","")." </td>";
                else $html.="<td> </td>";
                }
            $html.= " </tr> ";
    	    }
   	    $html.="</table>";
        return($html);
        }   

	}  /* Ende class Gartensteuerung */



	
?>