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

	function Smtp_Configuration() {
		$eventConfiguration = array(
				"Password" 				=> "cloudg06",
				"Recipient" 			=> "wolfgangjoebstl@gmail.com",
				"SenderAddress"		=> "claudiaundwolfganggemeinsam@gmail.com",
				"Username"				=> "claudiaundwolfganggemeinsam@gmail.com",
				"SenderName"			=> "LBG02",
				"UseAuthentication"	=> true,
				"Port"   				=> "465",
				"Host"   				=> "smtp.gmail.com",
				"UseSSL" 				=> true,
			);
		return $eventConfiguration;
	}

	function Imap_Configuration() {
		$eventConfiguration = array(
				"CacheInterval"      => "300",
				"Password"	      	=> "cloudg06",
				"CacheSize"      		=> "10",
				"Username"      		=> "claudiaundwolfganggemeinsam@gmail.com",
				"UseAuthentication"  => true,
				"Port"      			=> "993",
				"Host"      			=> "imap.googlemail.com",
				"UseSSL"      			=> true,
			);
		return $eventConfiguration;
	}

	*/



	function Smtp_Configuration() {
		$eventConfiguration = array(
			);
		return $eventConfiguration;
	}

	function Imap_Configuration() {
		$eventConfiguration = array(
			);
		return $eventConfiguration;
	}


	 

	/** @}*/
?>