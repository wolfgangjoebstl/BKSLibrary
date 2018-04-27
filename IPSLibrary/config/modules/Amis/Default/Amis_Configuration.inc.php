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


	/**@defgroup ipstwilight_configuration Amis Konfiguration
	 * @ingroup ipstwilight
	 * @{
	 *
	 * Konfigurations File für Amis
	 *
	 * @file          Amis_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */

	/*  Beispiel für Konfiguration:
	*
	
	function get_MeterConfiguration()
		{
		return array(
			"AMIS" => array(
				"NAME"            => 'Arbeitszimmer',
				"TYPE"            => 'Amis',
				"ORDER"           => 'Sub',
				"PORT" 				=> "Serial", 	// Bluetooth oder Serial auswählen
				"COMPORT"			=>	"COM3",		// COM Port definieren, ist nicht immer Com Port 3	
				"VariableName"    => 'Wirkenergie',     			ist eigentlich immer Wirkenergie
													),
			"HM-Wohnzimmer" => array(
				"NAME"            => 'Wohnzimmer',
				"TYPE"            => 'Homematic',
				"ORDER"           => 'Sub',
				"VariableName"    => 'Wirkenergie',
				"OID"             => 31985,
												),
			"HM-Arbeitszimmer-Netzwerk" => array(
				"NAME"            => 'Arbeitszimmer-Netzwerk',
				"TYPE"            => 'Homematic',
				"ORDER"           => 'Sub',
				"VariableName"    => 'Wirkenergie',
				"OID"             => 30642,
											),
					);
		}
	 *
	 */
	function get_Cost()
		{
		$cost=0.2;
		return $cost;
		}
					
	function get_MeterConfiguration()
		{
		return array(
					);					
		}



	 

	/** @}*/
?>