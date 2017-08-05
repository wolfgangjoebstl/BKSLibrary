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
	 
	/*
			dd => array('Contact','Bewegung','',),
			dd => array('Contact','Alarm','',),
			dd => array('Motion','Bewegung','',),
			dd => array('Temperatur','Temperatur_Aussen','',),   
			dd => array('Feuchtigkeit','Feuchtigkeit_Aussen','',),
			
			
	function IPSDetectMovementHandler_GetEventConfiguration() {
		$eventMoveConfiguration = array(
			aa => array('Motion','Alarm','',),   
			bb => array('Motion','Alarm','',),   
			cc => array('Motion','Bewegung','',),   
			dd => array('Motion','Bewegung','',),   
			ee => array('Contact','Alarm','',),   
			);

		return $eventMoveConfiguration;
	}

	function IPSDetectTemperatureHandler_GetEventConfiguration() {
		$eventTempConfiguration = array(
			ss => array('Temperatur','Temp_Wintergarten','par3',),   
			ss => array('Temperatur','Temp_Keller','par3',),   
			);

		return $eventTempConfiguration;
	}


	function IPSDetectHumidityHandler_GetEventConfiguration() {
		$eventHumidityConfiguration = array(
			hh => array('Feuchtigkeit','Feuchtigkeit_Wintergarten','par3',), 
			hh => array('Feuchtigkeit','Feuchtigkeit_Keller','par3',),  
			);

		return $eventHumidityConfiguration;
	}			
			
	*/
	 

	function IPSDetectMovementHandler_GetEventConfiguration() {
		$eventMoveConfiguration = array(

			);

		return $eventMoveConfiguration;
	}

	function IPSDetectTemperatureHandler_GetEventConfiguration() {
		$eventTempConfiguration = array(

			);

		return $eventTempConfiguration;
	}

	function IPSDetectHumidityHandler_GetEventConfiguration() {
		$eventHumidityConfiguration = array(

			);

		return $eventHumidityConfiguration;
	}

	 
	 

	/** @}*/
?>