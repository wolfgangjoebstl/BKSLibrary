<?php

/*
	 * @defgroup Startpage Include
	 *
	 * Include Script zur Ansteuerung der Startpage
	 *
	 *
	 * @file          Startpage.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

/* alter Inhalt von startpage.inc.php ist jetzt im startpage_copyfiles.ips.php file enthalten
 *
 *
 *
 *
 *
 */

	// Configuration Property Definition

	/* das sind die Variablen die in der data.Startpage angelegt werden soll */

	define ('STARTPAGE_VAR_ACTION',				'Action');
	define ('STARTPAGE_VAR_MODULE',				'Module');
	define ('STARTPAGE_VAR_INFO',				'Info');
	define ('STARTPAGE_VAR_HTML',				'HTML');

	define ('STARTPAGE_ACTION_OVERVIEW',			'Overview');
	define ('STARTPAGE_ACTION_UPDATES',			'Updates');
	define ('STARTPAGE_ACTION_LOGS',				'Logs');
	define ('STARTPAGE_ACTION_LOGFILE',			'LogFile');
	define ('STARTPAGE_ACTION_MODULE',				'Module');
	define ('STARTPAGE_ACTION_WIZARD',				'Wizard');
	define ('STARTPAGE_ACTION_NEWMODULE',			'NewModule');
	define ('STARTPAGE_ACTION_STORE',				'Store');
	define ('STARTPAGE_ACTION_STOREANDINSTALL',	'StoreAndInstall');


	IPSUtils_Include ("IPSLogger.inc.php",                      "IPSLibrary::app::core::IPSLogger");
	IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

	/*************************************************************************************************/

	/**
	 * Setzt eine bestimmte Seite in der Startpage
	 *
	 * @param string $action Action String  
	 * @param string $module optionaler Module String
	 * @param string $info optionaler Info String
	 */
	function Startpage_SetPage($action, $module='', $info='', $id='', $configuration='') {
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_ACTION, $baseId), $action);               // Verschiedene Variablen in data :  Action
		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_MODULE, $baseId), $module);
		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_INFO, $baseId), $info);
		$typeId = IPS_GetObjectIDByName("Startpagetype", $baseId);

        $sqlHandle = new sqlHandle(false);           // false, default MySQL Instanz, true debug
        if ($sqlHandle->available !==false)
            {
            $sqlHandle->useDatabase("ipsymcon");    // USE DATABASE ipsymcon
            $targetTable = "webfrontAccess";        // Zugriff mit Webfront Share Cookies
            $targetTableClass = "sql_".$targetTable;
            $sql = new $targetTableClass();
            $values = array(
                // accessID is automatic Key
                "nameOfID"          => $action,                 // Cookie
                "eventDescription"  => $info,                   // startofscript, button-eins etc
                "eventName"         => $id,                     // TopologyReceiver
                "configuration"     => $configuration,
            );
            $result=$sql->updateEntriesValues($values);
            IPSLogger_Inf(__file__, 'Startpage_SetPage aufgerufen, Update Table '.$targetTable.":".json_encode($result));	
            }
		return ($typeId);		
		}

    function Startpage_GetData($action,$full=false)
        {
        $tableArray = false;
        $sqlHandle = new sqlHandle(false);           // false, default MySQL Instanz, true debug
        if ($sqlHandle->available !==false)
            {
            $sqlHandle->useDatabase("ipsymcon");    // USE DATABASE ipsymcon
            $targetTable = "webfrontAccess";        // Zugriff mit Webfront Share Cookies

            $tables=array("webfrontAccess"=>"eventName");
            //echo "Show from all of these tables ".json_encode($tables)." the content:\n";
            foreach ($tables as $table => $active)
                {
                if ($active !== false)
                    {
                    //echo "<br>\n---------------------------------------------------------------------------------<br>\n";
                    //echo "Echo Values from MariaDB Database $table:<br>\n"; 
                    if ($active !=1) $sql = "SELECT * FROM $table WHERE nameOfID='".$action."' AND eventName='TopologyReceiver' ORDER BY $active;";
                    else $sql = "SELECT * FROM $table;";
                    //echo "$sql<br>\n";
                    $result1=$sqlHandle->query($sql);
                    $tableArray = $result1->fetchSelect();
                    $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
                    }
                }

            }
        //return $tableArray;
        if ($full=="configuration")
            {
            $config=array(); $id=0;
            foreach ($tableArray as $idx => $entry)
                {
                $index = $entry["eventDescription"];
                $index = str_replace("-","",$index);
                $config[$index]=$entry["configuration"];
                }
            return json_encode($config);
            }
        elseif ($full) return $tableArray;
        else return $tableArray[0]["configuration"];
        }


	/**
	 * Refresh der Startpage
	 *
	 */
	function Startpage_Refresh() 
		{
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
		$variableIdHTML = IPS_GetObjectIDByIdent(STARTPAGE_VAR_HTML, $baseId);
		SetValue($variableIdHTML, GetValue($variableIdHTML));
		}

    function StartpageOverview_Refresh()
        {
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.WebLinks');
		$variableIdHTML = IPS_GetObjectIDByIdent("htmlFrameTable", $baseId);
		SetValue($variableIdHTML, GetValue($variableIdHTML));
        }        

    function StartpageTopology_Refresh()
        {
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.WebLinks');
		$variableIdHTML = IPS_GetObjectIDByIdent("htmlCanvasTable", $baseId);
		SetValue($variableIdHTML, GetValue($variableIdHTML));
        }        

		
	/*************************************************************************************************/
		
	function controlMonitor($status,$configuration)
		{
		/* aus Konfiguration lernen ob Remote oder lokal zu schalten ist */
		$lokal=true;
		if (isset($configuration["Monitor"]["Remote"]) == true )
			{
			if ( ( strtoupper($configuration["Monitor"]["Remote"])=="ACTIVE" ) && ( isset ($configuration["Monitor"]["Address"]) ) ) $lokal=false; 
		    $url=$configuration["Monitor"]["Address"];
			$oid=$configuration["Monitor"]["ScriptID"];
			}
		if ($lokal)
			{	/* Remote Config nicht ausreichen, lokal probieren */ 
			switch ($status)
				{
		    	case "on":
			    	IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, 1);
				    break;
				case "off":
				    IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "monitor off", false, false, 1);
		    		break;
			    case "FullScren":
				default:
					IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, 1);
				    break;
		    	}	
			}
		else
		 	{	/* remote ansteuern */
			$rpc = new JSONRPC($url);
		   	switch ($status)
				{
			 	case "on":
			    	$monitor=array("Monitor" => "on");
				    $rpc->IPS_RunScriptEx($oid,$monitor);
		    		break;
			   	case "off":
			   		$monitor=array("Monitor" => "off");
			    	$rpc->IPS_RunScriptEx($oid,$monitor);
				    break;
		    	case "FullScren":
				default:
			   		$monitor=array("Monitor" => "FullScreen");
				    $rpc->IPS_RunScriptEx($oid,$monitor);
		    		break;
				}			
			}																
		}

		
?>