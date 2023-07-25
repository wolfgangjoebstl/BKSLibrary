<?

/*
	 * @defgroup Gartensteuerung
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 * Webfront Interface für Tastendrücke
	 *
	 * @file          Gartensteuerung.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

/************************************************************
 *
 * Webfront Aufruf
 *
 * Variablen erkennen und bearbeiten
 *
 * Besondere Funktion:
 *
 * wenn man bei EinmalEin nocheinmal die selbe Taste gedrückt wird der Giesskreis weitergeschaltet 
 *
 * Die Ansteuerung und Configuration erfolgt mit standardisierten Methoden. Es ist Homematic oder IPSHeat aktuell unterstützt.
 *
 * Zwei Betriebsarten:
 *
 * EinmalEin unterschiedlich ob Switch oder Auto Mode
 * Im SwitchMode kann der Giesskreis direkt selektiert werden
 *
 ****************************************************************/

if ($_IPS['SENDER']=="WebFront")
	{
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');
    IPSUtils_Include ('Gartensteuerung_Library.class.ips.php', 'IPSLibrary::app::modules::Gartensteuerung');

	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
		}

/******************************************************

				Variablen initialisieren
				
*************************************************************/

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');	
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
	$categoryId_Register    		= CreateCategory('Gartensteuerung-Register',   $CategoryIdData, 200);

	$GiessAnlagePrevID 	= @IPS_GetVariableIDByName("GiessAnlagePrev",$categoryId_Register);
	$GiessCountID		= @IPS_GetVariableIDByName("GiessCount",$categoryId_Register);
	$GiessCountOffsetID	= @IPS_GetVariableIDByName("GiessCountOffset",$categoryId_Register);
	$GiessAnlageID		= @IPS_GetVariableIDByName("GiessAnlage",$categoryId_Gartensteuerung);
	$GiessKreisID		= @IPS_GetVariableIDByName("GiessKreis",$categoryId_Gartensteuerung);
	$GiessKreisInfoID	= @IPS_GetVariableIDByName("GiessKreisInfo",$categoryId_Gartensteuerung);
	$GiessTimeID	    = @IPS_GetVariableIDByName("GiessTime", $categoryId_Gartensteuerung); 
	$GiessPauseID 	    = @IPS_GetVariableIDByName("GiessPause",$categoryId_Register);
	$GiessTimeRemainID	= @IPS_GetVariableIDByName("GiessTimeRemain", $categoryId_Gartensteuerung); 

	$GartensteuerungScriptID   		= IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);

    $dosOps = new dosOps();    
    $systemDir     = $dosOps->getWorkDirectory(); 

	$NachrichtenID  = IPS_GetCategoryIDByName("Nachrichtenverlauf-Gartensteuerung",$CategoryIdData);
    $NachrichtenInputID = IPS_GetVariableIDByName("Nachricht_Input",$NachrichtenID);
    $log_Giessanlage        = new Logging($systemDir."Log_Giessanlage2.csv",$NachrichtenInputID,IPS_GetName(0).";Gartensteuerung;");

	$timerDawnID = @IPS_GetEventIDByName("Timer3", $GartensteuerungScriptID);
	$UpdateTimerID = @IPS_GetEventIDByName("UpdateTimer", $GartensteuerungScriptID);

    $gartensteuerung = new Gartensteuerung();   // default, default, debug=false
    $GartensteuerungConfiguration =	$gartensteuerung->getConfig_Gartensteuerung();
    $configuration=$GartensteuerungConfiguration["Configuration"];                          // Abkürzung
    $switchMode=false; $pauseTime=1;                                    //Defaultwerte
	if (isset ($configuration["PAUSE"])) $pauseTime=$configuration["PAUSE"]; 
    if ( (isset($configuration["Mode"])) && ($configuration["Mode"]=="Switch") ) $switchMode=true;
	SetValue($GiessPauseID,$pauseTime);
	//echo "PauseTime : ".$pauseTime;
	
	/* vom Webfront aus gestartet, folgende tasten werden unterstützt
     *      GiessAnlage
     *      Giesskreis
     */
	$samebutton=false;
	$variableID=$_IPS['VARIABLE'];
	switch ($variableID)
		{
		case $GiessAnlageID: 
			//echo "Giessanlage Betriebsart Umschaltung bearbeiten."; 
			$value=$_IPS['VALUE'];
			if (GetValue($variableID)==$value)
				{ /* die selbe Taste nocheinmal gedrückt */
				$samebutton=true;
		   		}
			else
				{  /* andere Taste als vorher */
				SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
				SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
				}
			switch ($_IPS['VALUE'])
				{
				case "2":  /* Auto */
				case "-1":  /* Auto */
	    	  		IPS_SetEventActive($UpdateTimerID,false);
    	  			IPS_SetEventActive($timerDawnID,true);
					SetValue($GiessTimeRemainID ,0);				
 					$log_Giessanlage->LogMessage("Gartengiessanlage auf Auto gesetzt");
 					$log_Giessanlage->LogNachrichten("Gartengiessanlage auf Auto gesetzt");
                    $gartensteuerung->control_waterPump(false);                                     // sicherheitshalber hier immer nur ausschalten
	 				//$failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
					/* Vorgeschichte egal, nur bei einmal ein wichtig */
					SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
					break;
				case "1":  /* Einmal Ein */
					// damit auch wenn noch kein Wetter zum Giessen, gegossen werden kann, Giesszeit manuell auf 10 setzen, Giesscount=1 oder bei nocheinmal drücken Giesscount++
					if ($samebutton==true)
					   	{ // gleiche Taste heisst weiter, Giesscount++, Wasserpumpe ausschalten damit AUtúto weiterschaltet
                        SetValue($GiessTimeID,10);
                        SetValue($GiessTimeRemainID ,0);				
                        IPS_SetEventActive($UpdateTimerID,true);				
                        IPS_SetEventCyclicTimeBounds($UpdateTimerID,time(),0);  /* damit alle Timer gleichzeitig und richtig anfangen und nicht zur vollen Stunde */
      					IPS_SetEventActive($timerDawnID,false);
			      		SetValue($GiessCountID,GetValue($GiessCountID)+1);
 						$log_Giessanlage->LogMessage("Gartengiessanlage Weiter geschaltet");
 						$log_Giessanlage->LogNachrichten("Gartengiessanlage Weiter geschaltet");
                        $gartensteuerung->control_waterPump(false);
 						//$failure=set_gartenpumpe(false);
						//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); // sicherheitshalber !!! 
			   			}
					else
					   	{ // oder wenn zum ersten mal, Aufruf der Giessfunktion, mit Pause beginnen, Start 
                        SetValue($GiessTimeID,10);
                        SetValue($GiessTimeRemainID ,0);				
                        IPS_SetEventActive($UpdateTimerID,true);				
                        IPS_SetEventCyclicTimeBounds($UpdateTimerID,time(),0);  /* damit alle Timer gleichzeitig und richtig anfangen und nicht zur vollen Stunde */
	      				IPS_SetEventActive($timerDawnID,false);
			      		SetValue($GiessCountID,1);
		 				$log_Giessanlage->LogMessage("Gartengiessanlage auf EinmalEin gesetzt.");
		 				$log_Giessanlage->LogNachrichten("Gartengiessanlage auf EinmalEin gesetzt.");
                        $gartensteuerung->control_waterPump(false);
 						//$failure=set_gartenpumpe(false);
						//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
						}
					break;
				case "0":  /* Aus */
      				IPS_SetEventActive($timerDawnID,false);
    	  			IPS_SetEventActive($UpdateTimerID,false);				
		      		SetValue($GiessCountID,0);
					SetValue($GiessTimeRemainID ,0);				
 					$log_Giessanlage->LogMessage("Gartengiessanlage auf Aus gesetzt");
 					$log_Giessanlage->LogNachrichten("Gartengiessanlage auf Aus gesetzt");
                    $gartensteuerung->control_waterPump(false);
 					//$failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
					/* Vorgeschichte egal, nur bei einmal ein wichtig */
					SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
					break;
				default:
					break;	
				} /* ende switch value */
			break;
		case $GiessKreisID:                                 // durch Drücken der Giesskreis ID im Switch Mode den richtigen Giesskreis schalten, Gartenpumpe muss bereits eingeschaltet sein (EinmalEin)
            $value=$_IPS['VALUE'];
			SetValue($_IPS['VARIABLE'],$value);				
			SetValue($GiessKreisInfoID,$GartensteuerungConfiguration["Configuration"]["KREIS".(string)GetValue($GiessKreisID)]);
            if ($switchMode)
                {
                $giessCount=$value*2;
                SetValue($GiessCountID,$giessCount);
                $gartensteuerung->control_waterValves($giessCount);                      // umrechnen auf fiktiven $GiessCount    
                }
			break;
		default:
			SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);		
			break;
		}  /* ende switch variable ID */		 
	}

?>