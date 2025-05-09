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


	/**@defgroup OperationCenter
	 * @ingroup OperationCenter
	 * @{
	 *
	 * Konfigurations File für OperationCenter
	 *
	 * @file          OperationCenter_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */

	/*******************************************************
	 *  Beispiele zum Einstellen:
	 *

	function OperationCenter_Configuration() {
		$eventConfiguration = array(
			"INTERNET" 	=> array(
				"Google"  =>  array (
					"NAME"         		=> 	'Google',    Dont use Blanks or any othe character not suitable for filenames
					"IPADRESSE"       	=> 	'8.8.8.8',
					//"REBOOTSWITCH"    	=> 12345,
					"NOK_HOURS"       	=>	5,
						),
				              ),				"ROUTER" => array(
				"UMTS Router"  =>  array (
					"NAME"         	=> 'UMTS_Router',    Dont use Blanks or any othe character not suitable for filenames
					"TYP"          	=> 'MBRN3000',
					"MANUFACTURER"    => 'Netgear',
					"IPADRESSE"       => '10.0.0.1',
					"USER"            => 'admin',
					"PASSWORD"        => 'cloudg06',
					                           ),
				"WIFI Router"  =>  array (
					"NAME"         	=> 'WIFI_Router',    Dont use Blanks or any othe character not suitable for filenames
					"TYP"          	=> 'MR3420',
					"MANUFACTURER"    => 'Tplink',
					"IPADRESSE"       => '10.0.1.201',
					"USER"            => 'admin',
					"PASSWORD"        => 'cloudg06',
					"MacroDirectory" 				=> "C:/Users/wolfg_000/Documents/iMacros/Macros/", 	Verzeichnis von iMacro
					"DownloadDirectory" 			=> "C:/Users/wolfg_000/Documents/iMacros/Downloads/", 	Verzeichnis von iMacro
					                           ),
				"Airplay Router"  =>  array (
					"NAME"         	=>'Nummer1',      Dont use Blanks or any othe character not suitable for filenames
					"TYP"          	=> 'DIR655',
					"MANUFACTURER"    => 'Netgear',
					"IPADRESSE"       => '127.0.0.1',
					"PASSWORD"        => 'cloudg06',
					                           ),
				"LED1 Router"  =>  array (
					"NAME"         	=>'Nummer1',
					"TYP"          	=> 'DIR655',
					"MANUFACTURER"    => 'Netgear',
					"IPADRESSE"       => '127.0.0.1',
					"PASSWORD"        => 'cloudg06',
					                           ),
				"LED2 Router"  =>  array (
					"NAME"         	=>'Nummer1',
					"TYP"          	=> 'DIR655',
					"MANUFACTURER"    => 'Netgear',
					"IPADRESSE"       => '127.0.0.1',
					"PASSWORD"        => 'cloudg06',
					                           ),
									),
			"CAM" => array(                                          use it, cam configurations will be read from IPSCam module
				"FTPFOLDER"       => 'I:\\ftp_folder\\',              outbrreak bakslashes
				"NUMMER"          => '06603192670',
				"PASSWORD"        => 'Cloudg06',
													),
			"Nummer2" => array(
				"NAME"            => 'Nummer2',
				"NUMMER"          => '06603192670',
				"PASSWORD"        => 'Cloudg06',
													),
			);

		return $eventConfiguration;
	}

function LogAlles_Hostnames() {
		return array(

// Router

			"UPC"      => array(	"IP_Adresse"         => "10.0.2.1",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "80-c6-ab-73-fe-1c",            	// MAC Adresse muss vergeben werden
									"Hostname"           => "UPC-Gateway",		 					// Hostname ist auch zu vergeben
			              ),
			"SYNRT"      => array(	"IP_Adresse"         => "10.0.0.1",           		// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-11-32-4d-5a-76",            	// MAC Adresse muss vergeben werden, damit Wiedererkennung funktioniert
									"Hostname"           => "SynologyRouter",		 				// Hostname ist auch zu vergeben
			              ),

// Computer

			"GLA"      => array(	"IP_Adresse"         => "10.0.0.26",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "90-e6-ba-19-43-26",            // MAC Adresse muss vergeben werden
									"Hostname"           => "GanzLinks",					// Hostname ist auch zu vergeben
			              ),
			"LBG70"      => array(	"IP_Adresse"         => "10.0.0.20",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "80-ee-73-32-89-9f",            // MAC Adresse muss vergeben werden
									"Hostname"           => "LBG70Server",					// Hostname ist auch zu vergeben
			              ),

// Network Access Service

			"SYNST"      => array(	"IP_Adresse"         => "10.0.0.35",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-11-32-4b-d0-86",            // MAC Adresse muss vergeben werden, damit Wiedererkennung funktioniert
									"Hostname"           => "SynologyStation",		 			// Hostname ist auch zu vergeben
			              ),



// Receiver

			"AVR17"    => array(	"IP_Adresse"         => "10.0.0.23",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-05-cd-2d-c8-0a",            // MAC Adresse muss vergeben werden
									"Hostname"           => "DENON AVR-1713",		 		// Hostname ist auch zu vergeben
			              ),
			"AVR33"    => array(	"IP_Adresse"         => "10.0.0.115",           		// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-05-cd-25-91-76",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Denon AVR-3312",		 		// Hostname ist auch zu vergeben
			              ),

// Ipads, Ipods, MobilePhones

			"IPAD1"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "74-81-14-5d-be-07",            // MAC Adresse muss vergeben werden
									"Hostname"           => "IPad Wolfgang",		 				// Hostname ist auch zu vergeben
			              ),
			"IPAD2"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "c8-bc-c8-54-79-56",            // MAC Adresse muss vergeben werden
									"Hostname"           => "IPad LBG70",		 				// Hostname ist auch zu vergeben
			              ),
			"IPAD4"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "f0-d1-a9-39-27-24",            // MAC Adresse muss vergeben werden
									"Hostname"           => "IPad4 LBG70",		 				// Hostname ist auch zu vergeben
			              ),
			"APPTV"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "70-56-81-e1-7b-66",            // MAC Adresse muss vergeben werden
									"Hostname"           => "AppleTV",		 				// Hostname ist auch zu vergeben
			              ),
			"AMOB1"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "50-f0-d3-2a-ed-38",            // MAC Adresse muss vergeben werden
									"Hostname"           => "MobTel1",		 				// Hostname ist auch zu vergeben
			              ),
			"AMOB2"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "a0-0b-ba-d9-2f-aa",            // MAC Adresse muss vergeben werden
									"Hostname"           => "MobTel1",		 				// Hostname ist auch zu vergeben
			              ),
			"IPOD"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "90-84-0d-cf-c8-89",            // MAC Adresse muss vergeben werden
									"Hostname"           => "IPod_Schlafzimmer",		 				// Hostname ist auch zu vergeben
			              ),

// LED, HUE

			"HUE1"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-17-88-19-9e-dc",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Philips Hue",		 				// Hostname ist auch zu vergeben
			              ),
			"LED-AZ"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "ac-cf-23-43-0b-fd",            // MAC Adresse muss vergeben werden
									"Hostname"           => "LED-Arbeitszimmer",		 				// Hostname ist auch zu vergeben
			              ),

//

			"IP009"    => array(	"IP_Adresse"         => "10.0.0.9",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "d8-30-62-32-0b-93",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),
			"IP024"    => array(	"IP_Adresse"         => "10.0.0.24",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-1c-c0-02-2f-05",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),
			"IP027"    => array(	"IP_Adresse"         => "10.0.0.27",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "78-ca-39-42-87-c3",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),

// WebCams

			"CAMWZ"    => array(	"IP_Adresse"         => "10.0.0.85",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "e8-ab-fa-58-69-4e",            // MAC Adresse muss vergeben werden
									"Hostname"           => "WZ-IPCam",		 				// Hostname ist auch zu vergeben
			              ),
			"CAMVZ"    => array(	"IP_Adresse"         => "10.0.0.28",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-e0-4c-bc-89-bd",            // MAC Adresse muss vergeben werden
									"Hostname"           => "VZ-IPCam",		 				// Hostname ist auch zu vergeben
			              ),

//

			"IP030"    => array(	"IP_Adresse"         => "10.0.0.30",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-08-c9-01-65-63",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),
			"IP032"    => array(	"IP_Adresse"         => "10.0.0.32",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "90-84-0d-cf-c8-89",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),
			"IP034"    => array(	"IP_Adresse"         => "10.0.0.34",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "4c-ed-de-a2-d9-42",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),
			"IP038"    => array(	"IP_Adresse"         => "10.0.0.34",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-1d-ba-8f-11-37",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),
			"IP082"    => array(	"IP_Adresse"         => "10.0.0.82",           			// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "00-1a-22-00-3a-b1",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),
			"IP112"    => array(	"IP_Adresse"         => "10.0.0.112",           		// IP Adresse, kann auch sp?ter vergeben werden
									"Mac_Adresse"    	 => "e4-e0-c5-25-66-27",            // MAC Adresse muss vergeben werden
									"Hostname"           => "Unknown",		 				// Hostname ist auch zu vergeben
			              ),
					);
	}


	*******************************************************************************************************************************************/



	function OperationCenter_Configuration() {
		$eventConfiguration = array(
			"INTERNET" 	=> array(
				              ),			
			"ROUTER" 	=> array(
				              ),
			"CAM" 		=> array(                                          
								),
			"LED" 		=> array(                                          
								),
			"DENON" 	=> array(                                          
								),
		);
		return $eventConfiguration;
	}

function LogAlles_Hostnames() {
		return array(
					);
	}


function LogAlles_Manufacturers() {
		return array(
			"00-04-20"			=> "Slim Devices, Inc",			
			"00-05-cd"			=> "Denon Ltd",
			"00-08-c9"			=> "TechniSat Digital GmbH Daun",			
			"00-0c-29"			=> "VMware Inc",
			"00-11-32"			=> "Synology Incorporated",
			"00-1a-22"			=> "eQ3 Entwicklung GmbH",
			"00-17-88"			=> "Philips Lighting BV",
			"00-22-fb"			=> "Intel Corporate",
			"00-e0-4c"			=> "Realtek Semiconductor Corp",
			"10-02-b5"			=> "Intel Corporate",
			"10-fe-ed"			=> "TP-LINK TECHNOLOGIES CO LTD",
			"1c-b7-2c"			=> "ASUSTek Computer Inc.",
			"34-36-3b"			=> "Apple Inc",
			"38-2c-4a"			=> "ASUSTek Computer Inc.",
			"40-b0-34"			=> "Hewlett Packard",
			"48-4B-AA"			=> "Apple Inc",			
			"5c-51-4f"			=> "Intel Corporate",
			"78-ca-39"			=> "Apple Inc",
			"80-ee-73"			=> "Shuttle Inc.",
			"90-b1-c1"			=> "Dell Inc",
			"90-84-0d"			=> "Apple Inc",
			"90-e6-ba"			=> "AsusTek Computer Inc",
			"ac-cf-23"			=> "Hi-flying electronics technology Co ltd",
			"c8-bc-c8"			=> "Apple Inc",
			"d8-30-62"			=> "Apple Inc",
			"dc-85-de"			=> "Azurewave Technologies., inc.",
			"e4-e0-c5"			=> "Samsung Electronics Co., LTD",
			"e4-b3-18"			=> "Intel Corporate",
			"e8-ab-fa"			=> "Shenzen Reecam Tech Ltd",
			"ec-9b-f3"			=> "SAMSUNG ELECTRO-MECHANICS(THAILAND)",
			"fc-a1-83"			=> "Amazon Technologies Inc.",
			"fc-f1-36"			=> "Samsung Electronics Co.,Ltd",
			         );
			     }	
				  
				  
/* Wo ist die Dropbox für den Austausch der Scriptfiles udn Statusmeldungen */

	function OperationCenter_SetUp()
		{
		$oc_setup = array(

					);
		return $oc_setup;
		}
					  

	/** @}*/
?>