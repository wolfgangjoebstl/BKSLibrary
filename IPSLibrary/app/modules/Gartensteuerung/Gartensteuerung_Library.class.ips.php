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
 *
 *
 ****************************************************************/
 
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');

class Gartensteuerung
	{
	
	private 	$archiveHandlerID;
	private		$debug;
	private		$tempwerte, $tempwerteLog, $werteLog, $werte;
	private		$variableTempID, $variableID;
	
	public 		$regenStatistik;
	public 		$letzterRegen, $regenStand2h, $regenStand48h;
	
	public 		$GiessTimeID,$GiessDauerInfoID;
		
	public function __construct($starttime=0,$starttime2=0,$debug=false)
		{
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager)) 
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
			}
		$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
		$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
		$this->GiessTimeID	= @IPS_GetVariableIDByName("GiessTime", $categoryId_Gartensteuerung); 
		$this->GiessDauerInfoID	= @IPS_GetVariableIDByName("GiessDauerInfo",$categoryId_Gartensteuerung);
		//echo "GiesstimeID ist ".$this->GiessTimeID."\n";

		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$this->debug=$debug;
		$endtime=time();
		if ($starttime==0)  { $starttime=$endtime-60*60*24*2; }  /* die letzten zwei Tage Temperatur*/
		if ($starttime2==0) { $starttime2=$endtime-60*60*24*10; }  /* die letzten 10 Tage Niederschlag*/

		$this->variableTempID=get_aussentempID();
		$this->variableID=get_raincounterID();

		$Server=RemoteAccess_Address();
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

		/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */
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
					/* es regnet */
					$regenMengeAcc+=$regenMenge;
					$regenDauerAcc+=$regenDauer;				
					if ($this->debug) { echo $regenMengeAcc."mm ".$regenDauerAcc."min "; }
					if ($regenEndeZeit==0)
						{
						$regenStandEnde=$vorwert;
						$regenEndeZeit=$vorzeit;
						}						
					if ($this->debug) { echo "  Regenende : ".date("d.m H:i",$regenEndeZeit)."   ".round($regenStandEnde,1)."  ";	}
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
			}
		if ($this->debug) { echo "\n\n"; }
		}
	

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
		
	}  /* Ende class Gartensteuerung */



	
?>