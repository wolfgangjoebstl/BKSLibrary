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



?>