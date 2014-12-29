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
	 * @file          Guthabensteuerung_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */

	function get_GuthabenAllgemeinConfig()
		{
		return array(
			//"MacroDirectory" 			=> "c:/Users/Wolfgang/Documents/iMacros/Macros/", 	/* Verzeichnis von iMacro */
			"MacroDirectory" 			=> "C:/Users/wolfg_000/Documents/iMacros/Macros/", 	/* Verzeichnis von iMacro */
			"DownloadDirectory" 			=> "C:/Users/wolfg_000/Documents/iMacros/Downloads/", 	/* Verzeichnis von iMacro */
			);
		}


	function get_GuthabenConfiguration()
		{
		return array(
			"Nummer1" => array(
				"NAME"            => 'Nummer1',
				"NUMMER"          => '06602765645',
				"PASSWORD"        => 'cloudg06',
													),
			"Nummer2" => array(
				"NAME"            => 'Nummer2',
				"NUMMER"          => '06603192670',
				"PASSWORD"        => 'Cloudg06',
													),
			"Nummer3" => array(
				"NAME"            => 'Nummer3',
				"NUMMER"          => '06603404332',
				"PASSWORD"        => 'Cloudg06',
													),
			"Nummer4" => array(
				"NAME"            => 'Nummer4',
				"NUMMER"          => '06603404350',
				"PASSWORD"        => 'Cloudg06',
													),
			"Nummer5" => array(
				"NAME"            => 'Nummer5',
				"NUMMER"          => '06605960456',
				"PASSWORD"        => 'cloudg06',
													),
			"Nummer6" => array(
				"NAME"            => 'Nummer6',
				"NUMMER"          => '06607625474',
				"PASSWORD"        => 'cloudg06',
													),

					);
		}

	


	 
	 

	/** @}*/
?>
