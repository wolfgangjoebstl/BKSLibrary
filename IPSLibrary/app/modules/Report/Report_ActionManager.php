<?
	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */    

	/**@ingroup 	report action manager
	 * @{
	 *
     * steht im Autoexecute Script für jede der Webfront Variablen, wird bei Abänderung automatisch aufgerufen
     * ruft mit der variableID in der class report manager die function changesettings auf
     *
     *
	 * @file          Report_ActionManager.php
	 * @author        Wolfgang Jöbstl
	 * @version
	 *  Version 2.50.1, 2.02.2016<br/>
	 *
	 * Aufruf bei Variablenaenderung
	 *
	 */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ("Report_Constants.inc.php",     	"IPSLibrary::app::modules::Report");
	IPSUtils_Include ('Report_Configuration.inc.php', 	'IPSLibrary::config::modules::Report');
	IPSUtils_Include ('Report_class.php', 					'IPSLibrary::app::modules::Report');

    ini_set('memory_limit', '128M');       //usually it is 32/16/8/4MB 

	if ($_IPS['SENDER']=='WebFront')
		{
		$variableId   = $_IPS['VARIABLE'];
		$value        = $_IPS['VALUE'];

		$pcManager = new ReportControl_Manager();
		$pcManager->ChangeSetting($variableId, $value);

		}


	/** @}*/
?>
