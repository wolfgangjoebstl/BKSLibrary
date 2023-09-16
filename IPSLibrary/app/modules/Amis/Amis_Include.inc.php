<?

/*
	 * @defgroup Amis Include
	 *
	 * Include Script zur Ansteuerung Amis
	 *
	 *
	 * @file          Amis_include.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

/* 
 *
 *
 *
 *
 */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');	
    IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');

	// neded, called from Amis
    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");					// Library verwendet Configuration, danach includen
    IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");


	IPSUtils_Include ("IPSLogger.inc.php",                      "IPSLibrary::app::core::IPSLogger");

	// hier eventuell von CreateVariable3 definierte Informationen Amis.inc.php

	/**********************************************************************************************
	 * 
	 *auswahl nach Identifier, wenn kein automatischer Include besteht
	 *
	 ***/

	define ('GUTHABEN_VAR_ACTION',				'Action');
	define ('GUTHABEN_VAR_MODULE',				'Module');
	define ('GUTHABEN_VAR_INFO',				'Info');
	define ('GUTHABEN_VAR_HTML',				'InterActive');
	define ('AMIS_VAR_HTML',				    'NewLookAndFeel');
	
		
?>