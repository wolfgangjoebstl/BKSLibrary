<?

/*
	 * @defgroup Startpage Update
	 *
	 * Script zur Bearbeitung des tatsers
	 *
	 *
	 * @file          Startpage.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');

	IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
	IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');



?>