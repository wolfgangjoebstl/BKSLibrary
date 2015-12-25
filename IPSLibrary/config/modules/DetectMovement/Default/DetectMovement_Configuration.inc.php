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
			54389 => array('Contact','Bewegung','',),
			22915 => array('Contact','Alarm','',),
			21581 => array('Motion','Bewegung','',),
			22695 => array('Temperatur','Temperatur_Aussen','',),   
			51998 => array('Feuchtigkeit','Feuchtigkeit_Aussen','',),
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



	 
	 

	/** @}*/
?>
