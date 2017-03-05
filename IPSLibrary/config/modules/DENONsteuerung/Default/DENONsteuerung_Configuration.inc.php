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

	 /* wenn neue Denon Receiver hinzukommen muss der DENONSteuerung_Install erneut ausgeführt werden */

	function Denon_Configuration() {
		$eventConfiguration = array(
             	'Wohnzimmer'  =>  array(            // gleicher Name wie in der naechsten Tabelle notwendig
                	'NAME'               => 'Wohnzimmer',
                  'IPADRESSE'          => '10.0.0.115',
                	'NAME'               => 'Denon-Wohnzimmer',
                  'INSTANZ'          	=> 'DENON1',
                  'TYPE'               => 'Denon',
                    ),
               'Arbeitszimmer'   =>  array(        // gleicher Name wie in der naechsten Tabelle notwendig
                	'NAME'               => 'Arbeitszimmer',
                  'IPADRESSE'          => '10.0.0.23',
                	'NAME'               => 'Denon-Arbeitszimmer',
                  'INSTANZ'          	=> 'DENON2',
                  'TYPE'               => 'Denon',
                    ),
       /*      'Netplayer'  =>  array(     // gleicher Name wie in der naechsten Tabelle notwendig
                	'NAME'               => 'RemoteNetPlayer',
                  'IPADRESSE'          => 'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/',
                  'INSTANZ'          	=> 'DENON1',
                  'TYPE'               => 'Netplayer',
                    ),  */

			);
		return $eventConfiguration;
	}

/* Wenn ein Webfront konfiguriert wird muss es auch im ini file freigeschaltet sein */
/*
 * Arbeitszimmer: Tuner und
 *
 *	soll eine Tabelle darstellen die die Variablen definiert die angezeigt werden. handelt es sich um ein Profil wird ein array angelegt
 *	mit der entsprechenden Zuordnung
 *
 *	derzeit Hardcoded im Sourcecode vom Variablen und Command Manager
 *
 */

	function Denon_WebfrontConfig() {
		$WebConfiguration = array(
			'Wohnzimmer'  =>  array(         // gleicher Name wie in der vorigen Tabelle notwendig
				'DATA'   => array(            /* Auswahlfunktion sollte für alle Darstellungen göeich sein, aber unterschiedlich pro Denon Receiver */
					'AuswahlFunktion'   =>  array(
							'PC'              => 'DVD',
							'TUNER'           => 'TUNER',
							'XBOX'            => 'CBL/SAT',
										),
									),
			'Visualization.WebFront.User.DENON'   =>  array(
                  'NAME'               => 'User',
               	'Power'              => 'Power',
                    ),

			'Visualization.WebFront.Administrator.Audio'   =>  array(
                  'NAME'               => 'Audio',
                	'Power'              => 'Power',
                    ),
			'Visualization.WebFront.Administrator.DENON'   =>  array(
                  'NAME'               => 'Administrator',
                	'*'              => '*',
                    ),
				),
			'Arbeitszimmer'  =>  array(         // gleicher Name wie in der vorigen Tabelle notwendig
				'DATA'   => array(            /* Auswahlfunktion sollte für alle Darstellungen göeich sein, aber unterschiedlich pro Denon Receiver */
					'AuswahlFunktion'   =>  array(
							'PC'              => 'DVD',
							'TUNER'           => 'TUNER',
							'XBOX'           => 'CBL/SAT',
      			              ),
      			         ),
             'Visualization.WebFront.User.DENON'   =>  array(
                  'NAME'               => 'User',
               	'Power'              => 'Power',
                    ),

            'Visualization.WebFront.Administrator.Audio'   =>  array(
                  'NAME'               => 'Audio',
                	'Power'              => 'Power',
                    ),
            'Visualization.WebFront.Administrator.DENON'   =>  array(
                  'NAME'               => 'Administrator',
                	'*'              => '*',
                    ),
				),
			);
		return $WebConfiguration;
	}


	 
	 

	/** @}*/
?>