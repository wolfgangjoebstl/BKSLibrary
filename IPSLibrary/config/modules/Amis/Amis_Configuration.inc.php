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


	/**@defgroup ipstwilight_configuration IPSTwilight Konfiguration
	 * @ingroup ipstwilight
	 * @{
	 *
	 * Konfigurations File für IPSTwilight
	 *
	 * @file          Gartensteuerung_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */


	function get_AmisConfiguration()
		{ 
		return array(
			"Type" => "Bluetooth", /* Bluetooth oder Serial auswählen */
         "Adresse" => "34",
					); 
		}
					
	function get_MeterConfiguration()
		{
		return array(
			"AMIS" => array(
				"NAME"            => 'BKS01',                   /* eintragen, das ist die Kategorie unter der die Variablen gespeichert sind, nachher nicht mehr aendern */
				"TYPE"            => 'Amis',
				"ORDER"           => 'Main',
				"VariableName"    => 'Default-Wirkenergie',     /* wenn es bereits eine Variablennamen gibt, hier eintragen */
				"WirkenergieID"   => 52333,                     /* bei AMIS meist in einem anderen Verzeichnis, haendisch eintragen */
				"WirkleistungID"  => 11777,
				"Periodenwerte"   => 34315
						),
			"HM-Keller" => array(
				"NAME"            => 'Keller',                  /* eintragen, das ist die Kategorie unter der die Variablen gespeichert sind, nachher nicht mehr aendern */
				"TYPE"            => 'Homematic',               /* wird abgefragt, damit man weis wie zu behandeln ist */
				"ORDER"           => 'Sub',
				//"VariableName"    => 'Wirkenergie',             /* herausnehmen, braucht glaub ich keiner ... */
				//"WirkenergieID"   => 46557,							/* spaeter eintragen, sobald installiert wurden, muss geloggt werden */
				"WirkleistungID"  => 53240,                     /* spaeter eintragen, sobald installiert wurden */
				"HM_EnergieID"    => 57928,                     /* eintragen, von hier kommen die Werte fuer die Berechnung */
				"HM_LeistungID"   => 33654,							/* eintragen, von hier kommen die Werte fuer die Berechnung */
				"Periodenwerte"   => 50795
													),				
					);					
		}



	 

	/** @}*/
?>