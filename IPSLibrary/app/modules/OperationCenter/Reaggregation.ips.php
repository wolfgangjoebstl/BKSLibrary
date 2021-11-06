<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur Reaggregation von Archivwerten
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 4.0 13.6.2016
*/

//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

/******************************************************

				INIT

*************************************************************/

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.OperationCenter');

	
/******************************************************

				Archive Handler ueberpruefen, wurde notwendig bei Update auf IPS 4.0

*************************************************************/

echo "\n---------------------------------------------------------------------\n";
echo "Archive Handler ueberpruefen, wurde notwendig bei Update auf IPS 4.0 \n";

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
echo "Archive Handler OID : ".$archiveHandlerID." und Name : ".IPS_GetName($archiveHandlerID)."\n";


/*****
*
* Automatische Reaggregation aller geloggten Variablen
*
* Dieses Skript reaggregiert automatisch alle geloggten Variablen nacheinander
* automatisiert bei Ausführung. Nach Abschluss des Vorgangs wird der Skript-Timer
* gestoppt. Zur erneuten kompletten Reaggregation ist der Inhalt der automatisch
* unterhalb des Skripts angelegten Variable 'History' zu löschen.
*
*****/

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
$historyID = CreateVariableByName($_IPS['SELF'], "History", 3, "");

$finished = true;
$history = explode(',', GetValue($historyID));
$variableIDs = IPS_GetVariableList();

if ($_IPS['SENDER'] == "Execute")
	{
	$count=0;
	echo "\nFolgende Archiv Variablen wurden Reagreggiert :\n\n";
	foreach ($history as $item)
		{
		if ($item == "")
		   {
		   }
		else
		   {
		   echo "  ".$item."    ",IPS_GetName($item)."\n";
		   $count++;
		   }
		}
	/* welche Variablen muessen agreggiert werden */
	$total=0;
	foreach ($variableIDs as $variableID)
		{
	   $v = IPS_GetVariable($variableID);
   	if(isset($v['VariableValue']['ValueType']))
			{
      	$variableType = ($v['VariableValue']['ValueType']);
   	   }
		else
			{
   	   $variableType = ($v['VariableType']);
	    	}
   	if($variableType != 3)
			{
      	if (AC_GetLoggingStatus($archiveHandlerID, $variableID)) { $total++; }
   	 	}
		}
	echo "\nInsgesamt wurden ".$count." Archiv Variablen von ".$total." reagreggiert. Insgesamt ". sizeof($variableIDs)." Variablen !\n";
	if ( (sizeof($variableIDs)) > $total )
	   {
		IPSLogger_Dbg(__file__, "TimerEvent für Reaggregation von Variablen gestartet");
		IPS_SetScriptTimer($_IPS['SELF'], 60);
	   }
	}

if ($_IPS['SENDER'] == "TimerEvent")
	{
	IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Reaggregation von Variablen. Status ".sizeof($history)." reaggregiert");
	$variableIDs = IPS_GetVariableList();

	foreach ($variableIDs as $variableID)
		{
	   $v = IPS_GetVariable($variableID);
   	if(isset($v['VariableValue']['ValueType']))
			{
      	$variableType = ($v['VariableValue']['ValueType']);
   	   }
		else
			{
   	   $variableType = ($v['VariableType']);
	    	}

   	if($variableType != 3)
			{
      	if (AC_GetLoggingStatus($archiveHandlerID, $variableID) && !in_array($variableID,$history))
				{
	         $finished = false;
         	if (@AC_ReAggregateVariable($archiveHandlerID, $variableID))
					{
   	         $history[] = $variableID;
	            SetValue($historyID, implode(',', $history));
            	}
        		break;
      	  	}
   	 	}
		}

	if ($finished)
		{
   	IPS_LogMessage('Reaggregation', 'Reaggregation completed!');
		}

	IPS_SetScriptTimer($_IPS['SELF'], $finished ? 0 : 60);
	}

?>
