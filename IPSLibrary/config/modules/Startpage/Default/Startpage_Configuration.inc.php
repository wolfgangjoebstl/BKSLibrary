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

    /* Struktur Startpage Configuration
     *      Directories     wichtige Verzeichnisse, erforderlich für die Funktion
     *      Display         Darstellung Startpage, Info über Widgetstyle ist aber hier
     *      Widgets         Eigene Infoscreen Darstellung mit Kacheln in mehreren Ebenen
     *      Monitor
     *      SpecialRegs
     *      Temperature
     *
     */
     
	function startpage_configuration()
		{
		return array(
			"Directories"    => array (
				//"Pictures"		=> 'C:/Users/Wolfgang/Dropbox/Privat/IP-Symcon/pictures/',    /* Dont use Blanks or any other character not suitable for filenames */

                // if SynologyDrive is Used, configure fixed synchronisation, with on demand the download is not automatically requested
				"Pictures"		=> 'C:/users/wolfg/SynologyDrive/Drive/IP-Symcon/pictures/',                /* Dont use Blanks or any other character not suitable for filenames */                
				"Images"		=> 'C:/users/wolfg/SynologyDrive/Drive/IP-Symcon/images/',                  /* Dont use Blanks or any other character not suitable for filenames */
				"Icons"		    => 'C:/users/wolfg/SynologyDrive/Drive/IP-Symcon/icons/',                   /* Dont use Blanks or any other character not suitable for filenames */                 
				"Scripts"		=> 'C:/Scripts/process/',
									),
			"Display"    => array (
				"Weathertable"	=> 'Active', 
									),	
			"Widgets"    => array (                                         // das Station Display ist Widget orientiert
									),	

						);
		}			

	function temperatur()
		{ 
		//$rpc = new JSONRPC("http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/");
		//return $rpc->GetValue(21416);
		return GetValue(21416); 
		}

	function innentemperatur()
		{ 
		//$rpc = new JSONRPC("http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/");
		//return $rpc->GetValue(56688);
		return GetValue(56688); 
		}



	 
	 

	/** @}*/
?>