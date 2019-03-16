<?

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

	// Confguration Property Definition

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

	/**
	 * Setz eine bestimmte Seite in der Startpage
	 *
	 * @param string $action Action String
	 * @param string $module optionaler Module String
	 * @param string $info optionaler Info String
	 */
	function Startpage_SetPage($action, $module='', $info='') {
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');

		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_ACTION, $baseId), $action);
		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_MODULE, $baseId), $module);
		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_INFO, $baseId), $info);
		$typeId = IPS_GetObjectIDByName("Startpagetype", $baseId);
		return ($typeId);		
	}

	/**
	 * Refresh der Startpage
	 *
	 */
	function Startpage_Refresh() {
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
		$variableIdHTML = IPS_GetObjectIDByIdent(STARTPAGE_VAR_HTML, $baseId);
		SetValue($variableIdHTML, GetValue($variableIdHTML));
	}

    function writeStartpageStyle()
        {
    	$wert='<style>';
        $wert.='kopf { background-color: red; height:120px;  }';        // define element selectors
        $wert.='strg { height:280px; color:black; background-color: #c1c1c1; font-size: 12em; }';
        $wert.='innen { color:black; background-color: #ffffff; height:100px; font-size: 80px; }';
        $wert.='aussen { color:black; background-color: #c1c1c1; height:100px; font-size: 80px; }';
        $wert.='addText { color:black; background-color: #c1c1c1; height:100px; font-size: 24px; align:center; }';
        $wert.='temperatur { color:black; height:100px; font-size: 28px; align:center; }';
        $wert.='infotext { color:white; height:100px; font-size: 12px; }';
	    $wert.='#nested { border-collapse: collapse; border: 2px solid white; background-color: #f1f1f1; width: auto;  }';
        $wert.='#nested td { border: 1px solid white; }';		  
        $wert.='#temp td { background-color:#ffefef; }';                // define ID Selectors
        $wert.='#imgdisp { border-radius: 8px;  max-width: 100%; height: auto;  }';
        $wert.='#startpage { border-collapse: collapse; border: 2px dotted white; width: 100%; }';
        $wert.='#startpage td { border: 1px dotted DarkSlateGrey; }';	 
        $wert.='.container { width: auto; height: auto; max-height:95%; max-width: 100% }';
        $wert.='.image { opacity: 1; display: block; width: auto; height: auto; max-height: 90%; max-width: 80%; object-fit: contain; transition: .5s ease; backface-visibility: hidden; padding: 5px }';
        $wert.='.middle { transition: .5s ease; opacity: 0; position: absolute; top: 90%; left: 30%; transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%) }';
        $wert.='.container:hover .image { opacity: 0.8; }';             // define classes
        $wert.='.container:hover .middle { opacity: 1; }';
        $wert.='.text { background-color: #4CAF50; color: white; font-size: 16px; padding: 16px 32px; }';
        $wert.='</style>';
        return($wert);
        }

    function getStartpageConfiguration()
        {
        $configuration=startpage_configuration();
        if (!isset($configuration["Display"])) $configuration["Display"]["Weathertable"]="Inactive";
        return ($configuration);
        }

    function additionalTableLines($configuration)
        {
        $wert="";
        if ( (isset($configuration["AddLine"])) && (sizeof($configuration["AddLine"])>0) )
            {
            foreach($configuration["AddLine"] as $tablerow)
                {
                //echo "   Eintrag : ".$tablerow["Name"]."  ".$tablerow["OID"]."  ".$tablerow["Icon"]."\n";
    			$wert.='<tr><td><addText>'.$tablerow["Name"].'</addText></td><td><addText>'.number_format(GetValue($tablerow["OID"]), 1, ",", "" ).'°C</addtext></td></tr>';
                }
            //print_r($configuration["AddLine"]);
			//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
            //echo $wert;
            }
        return ($wert);
        }

    function bottomTableLines($configuration)
        {
        $wert="";
        if ( (isset($configuration["BottomLine"])) && (sizeof($configuration["BottomLine"])>0) )
            {
            $wert.='<tr>';
            foreach($configuration["BottomLine"] as $tableEntry)
                {
                //echo "   Eintrag : ".$tablerow["Name"]."  ".$tablerow["OID"]."  ".$tablerow["Icon"]."\n";
    			$wert.='<td><addText>'.$tableEntry["Name"].'</addText></td><td><addText>'.number_format(GetValue($tableEntry["OID"]), 1, ",", "" ).'°C</addtext></td>';
                }
            $wert.='</tr>';
            //print_r($configuration["AddLine"]);
			//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
            //echo $wert;
            }
        return ($wert);            
        }

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