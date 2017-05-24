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


	/**@defgroup startpage_configuration
	 * @ingroup startpage
	 * @{
	 *
	 *
	 * @file          Startpage_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */

	function startpage_configuration()
		{
		return array(
			"Directories"    => array (
				"Pictures"		=> 'C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/pictures/',    /* Dont use Blanks or any other character not suitable for filenames */
									),
		
						);
		}		

	function temperatur()
		{ return GetValue(21416); }

	function innentemperatur()
		{ return GetValue(56688); }



	 
	 

	/** @}*/
?>