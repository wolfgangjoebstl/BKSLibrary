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


	/**@defgroup IPSComponentLogger
	 * @ingroup IPSComponentLogger
	 * @{
	 *
	 * Konfigurations File für IPSComponentLogger
	 *
	 * @file          IPSComponentLogger_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
     *
     * Windows C:/Scripts/Logging/Temperature/
     * Linux   /var/log/symcon/Logging/Temperature     - Log Files (Logfiles...)
     *         /var/opt/symcon/Logging/
     *
	 *
	 */

	function get_IPSComponentLoggerConfig()
		{
		return array(
		   "LogDirectories"    => array (
			"TemperatureLog"		=> "C:/Scripts/Logging/Temperature/",
			"HumidityLog" 			=> "C:/Scripts/Logging/Humidity/",
			"MotionLog" 			=> "C:/Scripts/Logging/Motion/",
			"CounterLog" 			=> "C:/Scripts/Logging/Counter/",
			"HeatControlLog" 		=> "C:/Scripts/Logging/HeatControl/",			
										),
			"LogConfigs"         => array (
			   "DelayMotion"        => 1800,                            /* verzoegerte Bewegungswerte um Hüllkurve zu erzeugen und kurze Unterbrechungen auszufiltern */
			                     ),
            "BasicConfigs"      => array (
                "LogStyle"          => "html",
            /*    "SystemDir"         => "C:/Scripts/",
                "OperatingSystem"   => "Windows",   */
                                ),                                  
			);
		}

	function get_IPSComponentHeatConfig()
		{
		return array(
		   "HeatingPower"    => array (

										),
			);
		}

		
		/* wenn FS20 Bewegungsmelder zugeordnet werden muessen, ist an den Datenfeldern oft nicht eindeutig zu erkennen um welches Gerät es sich handelt - hier Zusatzinformatioen zur Verfügung stellen   */

	function RemoteAccess_TypeFS20()
		{
		return array(
		/*		"Zentralzimmer"      => 	array(
	                              			"OID" 	=> 50080,
	                                       "Type"   => "Motion",
																  )            */
						);
		}

	/** @}*/
?>