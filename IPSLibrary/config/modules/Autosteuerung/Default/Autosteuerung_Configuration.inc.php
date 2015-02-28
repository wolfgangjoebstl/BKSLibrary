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


	/**@defgroup DetectMovement
	 * @ingroup DetectMovement
	 * @{
	 *
	 * Konfigurations File für DetectMovement
	 *
	 * @file          Gartensteuerung_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */


	/* Beispiele zum EInstellen:
			21675 => array('OnUpdate','tts_play(1,"Hello","",2);','par2',),
			21675 => array('OnUpdate','IPSLight_SetSwitchByName("ArbeitszimmerStatusLED", true);','par2',),
			37416 => array('OnUpdate','IPSLight_SetSwitchByName("ArbeitszimmerStatusLED", false);','par2',),
			21675 => array('OnUpdate','IPSLight_SetProgramNextByName("WohnzimmerProgram");','par2',),
			37416 => array('OnUpdate','IPSLight_SetGroupByName("WohnzimmerHue", false);','par2',),
			24558 => array('OnChange','Anwesenheit','par2',),        für Anwesenheitssimulation, siehe config weiter unten
	*/



	function Autosteuerung_GetEventConfiguration() {
		$eventConfiguration = array(

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


                );
		return $scenes;
	}





	 
	 

	/** @}*/
?>
