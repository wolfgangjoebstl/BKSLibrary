<?php
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


	/**@defgroup IPSWatchdog
	 * @ingroup IPSWatchdog
	 * @{
	 *
	 * Konfigurations File für IPSWatchdog
	 *
	 * @file          IPSWatchdog_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */


/* Beispiele zum EInstellen:

	function Watchdog_Configuration() {
		return array(
		   "Software" => array(
		   	"Watchdog"  =>  array (
					"Directory"        		=> 'C:/IP-Symcon/',
					"Autostart"             => 'Yes',
					                  ),
		   	"VMware"  =>  array (
					"Directory"        		=> 'C:/Program Files (x86)/VMware/VMware Player/',
					"DirFiles"        		=> 'c:/Scripts/VirtualMachines/Windows 7 IPS/',
					"FileName"              => 'Windows 7 IPS.vmx',
					"Autostart"             => 'Yes',
					                  ),
				"iTunes"  =>  array (
					"Directory"        		=> 'C:/Program Files/iTunes/',
					"Autostart"             => 'Yes',
					"SoapIP"                => '10.0.0.20',
					                  ),
				"Firefox"  =>  array (
					"Directory"        		=> 'C:/Program Files (x86)/Mozilla Firefox/',
					"Url"                   => 'http://10.0.0.20:82/#37538 ',
					"Autostart"             => 'Yes',
					                  ),
					              ),
			"RemoteShutDown"     => array(
				"Server"  =>	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.0.48:86/api/',
				                  ),
							);
	}

	function Sprachsteuerung_Configuration() {
		$eventConfiguration = array(

			);

		return $eventConfiguration;
	}
	
	*/

	function Watchdog_Configuration() {
		return array(
		   "Software" => array(
		   	"Watchdog"  =>  array (
					"Directory"        		=> 'C:/IP-Symcon/',
					"Autostart"             => 'Yes',
					                  ),
				"Firefox"  =>  array (
					"Directory"        		=> 'C:/Program Files (x86)/Mozilla Firefox/',
					"Url"                   => 'http://10.0.0.20:82/#37538',
					"Autostart"             => 'Yes',
					                  ),
				       ),
							);
	}




	 

	/** @}*/
?>