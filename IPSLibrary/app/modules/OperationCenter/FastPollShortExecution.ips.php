<?

/***********************************************************************
 *
 * OperationCenter, FastPollShortExecution
 *
 * Nur für kurze Interactionen für die timer die alle 10 Sekunden kommen können
 * Durchlaufzeiten achten
 *
 * 
 *
 ***********************************************************/

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
    IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

    $debug=false;	                    // keine lokalen Echo Ausgaben

/******************************************************

				INIT

*************************************************************/

    $dosOps = new dosOps();
    $startexec=microtime(true);

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('OperationCenter',$repository);
        }

    $installedModules = $moduleManager->GetInstalledModules();

    $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
    $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);
    $scriptIdFastPollShort     = IPS_GetScriptIDByName('FastPollShortExecution', $CategoryIdApp);

    if ($debug)
        {
        echo "Script ID Operation Center        : $scriptIdOperationCenter \n";
        echo "Script ID FastPoll ShortExecution : $scriptIdFastPollShort \n";
        }

    $timerOps = new timerOps();
    $tim12ID = @IPS_GetEventIDByName("HighSpeedUpdate",$scriptIdFastPollShort);					/* alle 10 Sekunden Werte updaten, zB die Werte einer SNMP Auslesung über IPS SNMP */

/*********************************************************************************************/

    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

    $ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);

	$pname="MByte";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'',' MByte');
	   print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   //echo "Profile \"MByte\" vorhanden.\n";
	   }


/* Logging aktivieren
 *
 *********************************************************************************************/

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

/*********************************************************************************************/

	$subnet="10.255.255.255";
	$OperationCenter=new OperationCenter($subnet);
	
	$OperationCenterConfig = $OperationCenter->getConfiguration();
	$OperationCenterSetup = $OperationCenter->getSetup();
    
	$DeviceManager = new DeviceManagement();                            // stürzt aktuell mit HMI_CreateReport ab


/********************************************************************************************
 *
 * Timer Aufrufe nur Fast Execute
 *
 **********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	switch ($_IPS['EVENT'])
		{
		case $tim12ID:			// High Speed Polling, alle 10 Sekunden
            /* benötigt ein fehlendes STATUS oder ein STATUS mit ACTIVE und ein READMODE mit SNMP
             * im data gibt es ein Kategorie mit Router_RouterName, und eine Variable SnmpFastPoll
             * wenn nicht ebenfalls Bearbeitung abbrechen, unter der Variable SnmpFastPoll gibt es zwei Variablen ifTable, SNMP Fast Poll
             * wenn die Variablen nicht vorhanden sind oder SNMP Fast Poll nicht auf true steht Abbruch
             */
			foreach ($OperationCenterConfig['ROUTER'] as $router)
				{
                if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
                    {

                    }
                else
                    {
					if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") ) 
						{					                    
					    echo "Timer: Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
	        			$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
						$fastPollId=@IPS_GetObjectIDByName("SnmpFastPoll",$router_categoryId);
						if ($fastPollId!== false)
							{
							$ifTable_ID=@IPS_GetObjectIDByName("ifTable", $fastPollId);
							$SchalterFastPoll_ID=@IPS_GetObjectIDByName("SNMP Fast Poll", $fastPollId);
							if ( ($SchalterFastPoll_ID !== false) && ($ifTable_ID !== false) && (GetValue($SchalterFastPoll_ID)==true) )
								{
								$host          = $router["IPADRESSE"];
								if (isset($router["COMMUNITY"])) $community     = $router["COMMUNITY"]; 
								else $community     = "public";				    //print_r($router);
			                    $binary        = "C:\Scripts\ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
													
							    //print_r($router);
			                    switch (strtoupper($router["TYP"]))
			                        {                    
			                        case 'B2368':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_B2368($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT1900AC':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT1900AC($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT2600AC':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT2600AC($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT6600AX':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT6600AX($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
									default:
										echo "   Kein Eintrag für \"".$router['NAME']."\" gefunden. Typ \"".strtoupper($router["TYP"])."\" nicht erkannt.\n";
										break;						
			                        }       // router case
								}			// ende if SchalterFastPoll
							}				// ende if fastPoll Category
						}   	// if snmp fast poll active
                    }       // if active
                }   // foreach
			break;
		default:                // unbekannt
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." ID unbekannt.");
		   break;
		}
	}

if ($_IPS['SENDER']=="Execute")            // selbe Routine wie timer oben
    {
    echo "Status of Timer for fastPoll :\n";
    $timerOps->getEventData($tim12ID);        
            /* benötigt ein fehlendes STATUS oder ein STATUS mit ACTIVE und ein READMODE mit SNMP
             * im data gibt es ein Kategorie mit Router_RouterName, und eine Variable SnmpFastPoll
             * wenn nicht ebenfalls Bearbeitung abbrechen, unter der Variable SnmpFastPoll gibt es zwei Variablen ifTable, SNMP Fast Poll
             * wenn die Variablen nicht vorhanden sind oder SNMP Fast Poll nicht auf true steht Abbruch
             */
			foreach ($OperationCenterConfig['ROUTER'] as $router)
				{
                if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
                    {

                    }
                else
                    {
					if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") ) 
						{					                    
					    echo "Timer: Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
	        			$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
						$fastPollId=@IPS_GetObjectIDByName("SnmpFastPoll",$router_categoryId);
						if ($fastPollId!== false)
							{
							$ifTable_ID=@IPS_GetObjectIDByName("ifTable", $fastPollId);
							$SchalterFastPoll_ID=@IPS_GetObjectIDByName("SNMP Fast Poll", $fastPollId);
							if ( ($SchalterFastPoll_ID !== false) && ($ifTable_ID !== false) && (GetValue($SchalterFastPoll_ID)==true) )
								{
								$host          = $router["IPADRESSE"];
								if (isset($router["COMMUNITY"])) $community     = $router["COMMUNITY"]; 
								else $community     = "public";				    //print_r($router);
			                    $binary        = "C:\Scripts\ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
													
							    //print_r($router);
			                    switch (strtoupper($router["TYP"]))
			                        {                    
			                        case 'B2368':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_B2368($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT1900AC':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT1900AC($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT2600AC':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT2600AC($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT6600AX':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT6600AX($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
									default:
										echo "   Kein Eintrag für \"".$router['NAME']."\" gefunden. Typ \"".strtoupper($router["TYP"])."\" nicht erkannt.\n";
										break;						
			                        }       // router case
								}			// ende if SchalterFastPoll
							}				// ende if fastPoll Category
						}   	// if snmp fast poll active
                    }       // if active
                }   // foreach
    }

    echo "\nDurchlaufzeit : ".(microtime(true)-$startexec)." Sekunden\n";


	
?>