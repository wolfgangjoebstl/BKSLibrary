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
 * Webfront Aufruf
 *
 * Variablen erkennen und bearbeiten
 *
 * Besondere Funktion:
 *
 * wenn man bei EinmalEin nocheinmal die selbe taste gedrückt wird der Giesskreis weitergeschaltet 
 *
 *
 ****************************************************************/

if ($_IPS['SENDER']=="WebFront")
	{
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');

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
	$GiessTimeID	= @IPS_GetVariableIDByName("GiessTime", $categoryId_Gartensteuerung); 
	$GiessPauseID 	= @IPS_GetVariableIDByName("GiessPause",$categoryId_Register);
	$GiessTimeRemainID	= @IPS_GetVariableIDByName("GiessTimeRemain", $categoryId_Gartensteuerung); 

	$GartensteuerungScriptID   		= IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);

    $dosOps = new dosOps();    
    $systemDir     = $dosOps->getWorkDirectory(); 

	$object2= new ipsobject($CategoryIdData);
	$object3= new ipsobject($object2->osearch("Nachricht"));
	$NachrichtenInputID=$object3->osearch("Input");
	$log_Giessanlage=new Logging($systemDir."Log_Giessanlage2.csv",$NachrichtenInputID,IPS_GetName(0).";Gartensteuerung;");

	$timerDawnID = @IPS_GetEventIDByName("Timer3", $GartensteuerungScriptID);
	$UpdateTimerID = @IPS_GetEventIDByName("UpdateTimer", $GartensteuerungScriptID);

	$GartensteuerungConfiguration=getGartensteuerungConfiguration();
	if (isset ($GartensteuerungConfiguration["PAUSE"])) { $pauseTime=$GartensteuerungConfiguration["PAUSE"]; } else { $pauseTime=1; }
	SetValue($GiessPauseID,$pauseTime);
	//echo "PauseTime : ".$pauseTime;
	
	/* vom Webfront aus gestartet */
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
	 				$failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
					/* Vorgeschichte egal, nur bei einmal ein wichtig */
					SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
					break;
				case "1":  /* Einmal Ein */
					/* damit auch wenn noch kein Wetter zum Giessen, gegossen werden kann, Giesszeit manuell setzen */
					SetValue($GiessTimeID,10);
					SetValue($GiessTimeRemainID ,0);				
    	  			IPS_SetEventActive($UpdateTimerID,true);				
					IPS_SetEventCyclicTimeBounds($UpdateTimerID,time(),0);  /* damit alle Timer gleichzeitig und richtig anfangen und nicht zur vollen Stunde */
					if ($samebutton==true)
					   	{ /* gleiche Taste heisst weiter */
      					IPS_SetEventActive($timerDawnID,false);
			      		SetValue($GiessCountID,GetValue($GiessCountID)+1);
 						$log_Giessanlage->LogMessage("Gartengiessanlage Weiter geschaltet");
 						$log_Giessanlage->LogNachrichten("Gartengiessanlage Weiter geschaltet");
 						$failure=set_gartenpumpe(false);
						//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
			   			}
					else
					   	{ /* oder wenn zum ersten mal, Aufruf der Giessfunktion, mit Pause beginnen */
	      				IPS_SetEventActive($timerDawnID,false);
			      		SetValue($GiessCountID,1);
		 				$log_Giessanlage->LogMessage("Gartengiessanlage auf EinmalEin gesetzt.");
		 				$log_Giessanlage->LogNachrichten("Gartengiessanlage auf EinmalEin gesetzt.");
 						$failure=set_gartenpumpe(false);
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
 					$failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
					/* Vorgeschichte egal, nur bei einmal ein wichtig */
					SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
					break;
				default:
					break;	
				} /* ende switch value */
			break;
		case $GiessKreisID:
			SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);				
			SetValue($GiessKreisInfoID,$GartensteuerungConfiguration["KREIS".(string)GetValue($GiessKreisID)]);
			break;
		default:
			SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);		
			break;
		}  /* ende switch variable ID */		 
	}

?>