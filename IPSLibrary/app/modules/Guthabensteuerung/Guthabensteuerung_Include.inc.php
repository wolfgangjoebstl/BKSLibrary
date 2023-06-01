<?

/*
	 * @defgroup Guthabensteuerung Include
	 *
	 * Include Script zur Ansteuerung der Guthabensteuerung
	 *
	 *
	 * @file          Guthabensteuerung.inc.php
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

    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");					// Library verwendet Configuration, danach includen
    IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");

	// Configuration Property Definition

	/* das sind die Variablen die in der data.Startpage angelegt werden soll */

	define ('GUTHABEN_VAR_ACTION',				'Action');
	define ('GUTHABEN_VAR_MODULE',				'Module');
	define ('GUTHABEN_VAR_INFO',				'Info');
	define ('GUTHABEN_VAR_HTML',				'InterActive');

	IPSUtils_Include ("IPSLogger.inc.php",                      "IPSLibrary::app::core::IPSLogger");


	/*************************************************************************************************/


		
?>