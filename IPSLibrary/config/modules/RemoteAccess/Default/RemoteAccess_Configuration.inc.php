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


	/**@defgroup RemoetAccess_configuration RemoteAccess Konfiguration
	 * @ingroup RemoteAccess
	 * @{
	 *
	 * Konfigurations File für RemoteACcess
	 *
	 * @file          Gartensteuerung_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */


/* comment one of the following remote servers */

function RemoteAccess_GetConfiguration()
	{
		return array(
				"LBG-VIS"        		=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06##@hupo35.ddns-instar.de:86/api/',
						);
	}

function LocalAccess_GetConfiguration()
	{
		return array(
				//"LBG70"        		=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.0.20:82/api/',
						);
	}


function RemoteAccess_TypeFS20()
	{
		return array(
				/*"Zentralzimmer"      => 	array(
	                              			"OID" 	=> 50080,
	                                       "Type"   => "Motion",
																  ) */
						);
	}


	/** @}*/
?>
