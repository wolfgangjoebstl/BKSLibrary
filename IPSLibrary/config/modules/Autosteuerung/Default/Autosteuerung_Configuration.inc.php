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

	function Autosteuerung_SetSwitches() {
		 $switches = array(
			'Anwesenheitssimulation' 	=>  array(
            	'NAME'               	=> 'Anwesenheitssimulation',
                'PROFIL'             	=> 'AusEinAuto',
                'ADMINISTRATOR'      	=> true,
                'USER'      			=> false,
                'MOBILE'  				=> false,
				'OWNTAB'				=> 'AnwesenheitSimulation',				  
                    ),
             'Anwesenheitserkennung'   =>  array(
             	'NAME'               => 'Anwesenheitserkennung',
                'PROFIL'             => 'AusEinAuto',
                'ADMINISTRATOR'      => true,
                'USER'      			=> false,
                'MOBILE'  				=> false,
                'OWNTAB'				=> 'Anwesenheitserkennung',                
                    ),
			'Alarmanlage'   				=>  array(					// diesen Namen nicht verändern
				'NAME'               => 'Alarmanlage',
				'PROFIL'             => 'AusEinAuto',
				'ADMINISTRATOR'      => true,
				'USER'      			=> true,
				'MOBILE'  				=> true,
                                                                    // zumindest eine ohne OWNTAB sonst wird AUtosteuerung nicht angelegt
					),                      
                );
		return $switches;
		}


/* für die Anwesenheitserkennung werden die angeführten Statusvariablen UND und ODER verknüpft */

	function Autosteuerung_Anwesend() {
		$logic = array(
			'OR' => array(
					//45364,
						),
			'AND' => array(
							
							),
                );
		return $logic;
	}
	
	

/**************************************************
 *
 * Alexa Konfiguration, Wenn Sprache erkannt werden, sowie bei Autosteuerung, Befehle abarbeiten
 * Unterschied ist das Übergabeformat von Alexa.
 *	TurnOnRequest, TurnOffRequest
 *
 ***********************************************************************/

	function Alexa_GetEventConfiguration() {
		$alexaConfiguration = array(
			//"44404b3f-5f92-40ba-a7d5-63e8a83987a4" => array('OnUpdate','Status','name:ArbeitszimmerHintergrund',),        
			);

		return $alexaConfiguration;
	}

/* die folgende Funktion schaltet den Monitor ein und aus wenn die Maschine virtualisiert auf einem Server laeuft */

	function monitorOnOff($status)
		{
		$remServer=array(
//			"BKS-Server"           	=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/',
					);
		foreach ($remServer as $Server)
			{
			$rpc = new JSONRPC($Server);
			}
//		$monitor=array("Monitor" => "on");
//		$monitor["Monitor"]=$status;
//		$rpc->IPS_RunScriptEx(20996,$monitor);
		}

	function Autosteuerung_SetUp()
		{
		$as_setup = array(
			"LogDirectory"     		=> 'C:/Scripts/Autosteuerung/',    /* Dont use Blanks or any other character not suitable for filenames */
					);
		return $as_setup;
		}
		
	/***********************************************************
	 *
	 * wie funktioniert die Konfiguration zur Erstellung einer komplexen Webfront Darstellung.
	 *
	 *  'Anwesenheit'  => array      das ist der Name der Kategorie die in Visualization.Administrator angelegt wird
	 *    WFCSPLITPANEL,$WFCId, $ItemId, $ParentId, $Position, $Title, $Icon=““, $Alignment=  0=horizontal, 1=vertical, $Ratio=50, $RatioTarget=  0 or 1, $RatioType 0=Percentage, 1=Pixel, $ShowBorder=’true‘ or ‚false’)
	 *            $WFCId   Webfront Konfigurator ID (Administrator oder User)
	 *            $ItemId  der neu Name im Webfront Konfigurator
	 *            $ParentId  der bestehende Namen auf dem ItemID aufsetzt
	 *            $Title
	 *            $Icon
	 *
	 ************************************************************************************/

	function Autosteuerung_GetWebFrontConfiguration() {
		return array(
			'Administrator' => array(
				'AnwesenheitSimulation' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPAAnwesenheitSimulation',       'AutoTPA',        'AnwesenheitSimulation','Bed',1,40,0,0,'true'),    /*  vertical=1, Ratio=33,RatioTarget=0,Percentage,ShowBorder */

					),
				'AnwesenheitErkennung' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPADetails1',       'AutoTPA',        'AnwesenheitErkennung','Bed',1,40,0,0,'true'),    /*  vertical=1, Ratio=33,RatioTarget=0,Percentage,ShowBorder */
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails1_Left',  'AutoTPADetails1', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails1_Right',  'AutoTPADetails1', null,null),
					),
				'Autosteuerung' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPADetails2',        'AutoTPA',        'Autosteuerung',null,1,40,0,0,'true'),  /*  vertical=1,   Ratio=65, RatioTarget=0,Percentage, ShowBorder */
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails2_Left',  'AutoTPADetails2', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails2_Right',  'AutoTPADetails2', null,null),
					),
				'Stromheizung' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPADetails3',        'AutoTPA',        'Stromheizung','Radiator',1,40,0,0,'true'),
					array(IPSHEAT_WFCSPLITPANEL,   'AutoTPADetails3_Links',   'AutoTPADetails3',   null,null,0,270,0,1,'true'),				
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails3_Left',  'AutoTPADetails3_Links', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails3_LeftDown',  'AutoTPADetails3_Links', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails3_Right',  'AutoTPADetails3', null,null),
					),
				'Alexa' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPADetails4',        'AutoTPA',        'Alexa','Eyes',1,40,0,0,'true'),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails4_Left',  'AutoTPADetails4', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails4_Right',  'AutoTPADetails4', null,null),
					),
				'Control' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPADetails5',        'AutoTPA',        'Control','Robot',1,40,0,0,'true'),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails5_Left',  'AutoTPADetails5', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails5_Right', 'AutoTPADetails5', null,null),
					),						
				),
			'User'		=> array(
				'AnwesenheitErkennung' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPUDetails0',       'AutoTPU',        'Anwesenheit','Bed',1,40,0,0,'true'),    /*  vertical=1, Ratio=33,RatioTarget=0,Percentage,ShowBorder */
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails0_Left',  'AutoTPUDetails0', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails0_Right',  'AutoTPUDetails0', null,null),
					),
				'Autosteuerung' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPUDetails1',        'AutoTPU',        'Autosteuerung',null,1,40,0,0,'true'),  /*  vertical=1,   Ratio=65, RatioTarget=0,Percentage, ShowBorder */
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails1_Left',  'AutoTPUDetails1', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails1_Right',  'AutoTPUDetails1', null,null),
					),
				'Stromheizung' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPUDetails2',        'AutoTPU',        'Stromheizung','Radiator',1,40,0,0,'true'),
					array(IPSHEAT_WFCSPLITPANEL,   'AutoTPUDetails2_Links',   'AutoTPUDetails2',   null,null,0,270,0,1,'true'),				
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails2_Left',  'AutoTPUDetails2_Links', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails2_LeftDown',  'AutoTPUDetails2_Links', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails2_Right',  'AutoTPUDetails2', null,null),
					),
				'Alexa' => array(
					array(IPSHEAT_WFCSPLITPANEL, 'AutoTPUDetails3',        'AutoTPU',        'Alexa','Eyes',1,40,0,0,'true'),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails3_Left',  'AutoTPUDetails3', null,null),
					array(IPSHEAT_WFCCATEGORY,       'AutoTPUDetails3_Right',  'AutoTPUDetails3', null,null),
					),
				),
			'Mobile'		=> array(
				'Stromheizung' => array(
					array(IPSHEAT_WFCLINKS,       'Auto0',  'Schaltbefehle', null,null),
					),					),
								
		);

	}


	function Autosteuerung_Speak() {
		$speak = array(
			'Parameter' => array('On',' ',' ',),
	 
                );
		return $speak;
	}


	/** @}*/
?>