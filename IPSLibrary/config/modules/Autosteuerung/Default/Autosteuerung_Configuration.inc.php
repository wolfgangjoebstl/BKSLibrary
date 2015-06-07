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


	/**@defgroup AutoSteuerung
	 * @ingroup AutoSteuerung
	 * @{
	 *
	 * Konfigurations File für AutoSteuerung
	 *
	 * @file          AutoSteuerung_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */


	/* Beispiele zum Einstellen:
	
	[OnChange|OnUpdate] [fx call|Anwesenheit|Ventilator|Parameter|Status|StatusRGB|Custom|Switch]

	[Switch]  Name,Aus,Ein,Auo
	
			10161 => array('OnChange','par1','par2',),
			14147 => array('OnChange','par1','par2',),




	*/


	/********ACHTUNG Function wird automatisch beschrieben */
	function Autosteuerung_GetEventConfiguration() {
		$eventConfiguration = array(
			38272 => array('OnChange','par1','par2',),        /* Anwesenheitssimulation    */
			54561 => array('OnChange','par1','par2',),        /* Anwesenheitserkennung    */
			14147 => array('OnChange','par1','par2',),        /* Ventilatorsteuerung    */
			);

		return $eventConfiguration;
	}

	/* Beispiele zum Einspielen

	            'GangEG'  =>  array(
                'NAME'                      => 'GangEG',
                    'ACTIVE_FROM_TO'        => '20:40-23:00',
                    'EVENT_CHANCE'        => 10,
                    'EVENT_DURATION'      => 2,
                    'EVENT_IPSLIGHT_GRP'  => 'GangEG'
                    ),
            'Wohnzimmer' => array(
                'NAME'                      => 'Wohnzimmer',
                    'ACTIVE_FROM_TO'        => '20:40-23:00',
                    'EVENT_CHANCE'        => 20,
                    'EVENT_DURATION'      => 30,
                    'EVENT_IPSLIGHT_GRP' => 'Wohnzimmer'
                    ),
             'Fernseher' => array(
                'NAME'                      => 'Fernseher',
                    'ACTIVE_FROM_TO'        => '19:00-23:00',
                    'EVENT_CHANCE'        => 10,
                    'EVENT_DURATION'      => 45,
                    'EVENT_IPSLIGHT_GRP' => 'Fernseher'
                    )
             'AWSTischLampe'  =>  array(
                	'NAME'                      => 'AWSTischLampe',
                  'ACTIVE_FROM_TO'        => '20:40-23:00',
                  'EVENT_CHANCE'        => 20, 	//20% Eintrittswahrscheinlichkeit
                  'EVENT_DURATION'      => 1,   // Minuten
                  'EVENT_IPSLIGHT_GRP'  => 'Gaestezimmer'  //zu schaltende IPS-LIGHT Gruppe
                    ),
             'AWSWZDeko'  =>  array(
                	'NAME'                      => 'AWSWZDeko',
                  'ACTIVE_FROM_TO'        => '20:40-23:00',
                  'EVENT_CHANCE'        => 20, 	//20% Eintrittswahrscheinlichkeit
                  'EVENT_DURATION'      => 1,   // Minuten
                  'EVENT_IPSLIGHT_GRP'  => 'WohnzimmerDeko'  //zu schaltende IPS-LIGHT Gruppe
                    ),
             'AWSWZKugel'  =>  array(
                	'NAME'                      => 'AWSWZKugel',
                  'ACTIVE_FROM_TO'        => '20:40-23:00',
                  'EVENT_CHANCE'        => 20, 	//20% Eintrittswahrscheinlichkeit
                  'EVENT_DURATION'      => 1,   // Minuten
                  'EVENT_IPSLIGHT_GRP'  => 'WohnzimmerKugel'  //zu schaltende IPS-LIGHT Gruppe
                    ),

	*/


	function Autosteuerung_GetScenes() {
		 $scenes = array(
             'AWSWZDeko'  =>  array(
                	'NAME'                      => 'Dekolampe',
                  'ACTIVE_FROM_TO'        => 'sunset-23:00',
                  'EVENT_CHANCE'        => 20, 	//20% Eintrittswahrscheinlichkeit
                  'EVENT_DURATION'      => 1,   // Minuten
                  'EVENT_IPSLIGHT_GRP'  => 'DekolampeG'  //zu schaltende IPS-LIGHT Gruppe
                  //'EVENT_IPSLIGHT'  => 'Dekolampe'  //zu schaltende IPS-LIGHT Switch
                    ),

                );
		return $scenes;
	}

	function Autosteuerung_SetSwitches() {
		 $switches = array(
             'Anwesenheitssimulation'  =>  array(
                	'NAME'               => 'Anwesenheitssimulation',
                  'PROFIL'             => 'AusEinAuto',
                  'ADMINISTRATOR'      => true,
                  'USER'      			=> false,
                  'MOBILE'  				=> false,
                    ),
             'Anwesenheitserkennung'   =>  array(
                	'NAME'               => 'Anwesenheitserkennung',
                  'PROFIL'             => 'AusEinAuto',
                  'ADMINISTRATOR'      => true,
                  'USER'      			=> false,
                  'MOBILE'  				=> false,
                    ),
             'Ventilatorsteuerung'   =>  array(
                	'NAME'               => 'Ventilatorsteuerung',
                  'PROFIL'             => 'AusEinAuto',
                  'ADMINISTRATOR'      => true,
                  'USER'      			=> false,
                  'MOBILE'  				=> false,
                    ),
                );
		return $switches;
	}


	function Autosteuerung_Speak() {
		$speak = array(
			'Parameter' => array('On',' ',' ',),
	 
                );
		return $speak;
	}


	/** @}*/
?>