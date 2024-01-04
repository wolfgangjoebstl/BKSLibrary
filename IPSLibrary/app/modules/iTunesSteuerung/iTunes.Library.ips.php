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

    /*
	 *
	 * Sammlung class Routines rund um Media Funktionen von IP Symcon und der IPS Library
	 *
	 *
	 * @file          iTunes.Library.class.php
	 * @author        Wolfgang Joebstl
     */

    /**************
     *
     * die folgenden class Definitionen sind hier zu erwarten
     *
     * iTunes
     *
     * MSSoapClient
     *
     * iTunesControll
     *
     *
     *
     *
     *******************/


/* 
 * Library für iTunes, class Definition 
 *
 *
 **/

    class iTunes
        {

        public $CategoryIdData, $CategoryIdApp, $installedModules;
		private $archiveHandlerID=0;
		
		private $iTunesConfig;           // die bereinigtre AMIS Meter Config
        private $systemDir;              // das SystemDir, gemeinsam für Zugriff zentral gespeichert
        private $debug;                 // zusaetzliche hilfreiche Debugs

        private $ipsOps,$dosOps;

		/**
		 * @public
		 *
		 * Initialisierung der AMIS class
		 *
		 */
		public function __construct($debug=false) 
			{
            $this->debug=$debug;
            $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	        $moduleManager = new IPSModuleManager('iTunesSteuerung',$repository);     /*   <--- change here */
	        $this->installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

	        $this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	        $this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

			$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

            $this->ipsOps = new ipsOps();
            $this->dosOps = new dosOps();
            $this->systemDir     = $this->dosOps->getWorkDirectory();

			$this->iTunesConfig = $this->setiTunesConfig();
			}

        /* Konfiguration analysieren und vereinheitlichen
         * Umstellung auf set/get Meter Configuration
         */

        public function setiTunesConfig($debug=false)
            {
            $config=array(); $configInput=array();

            if ((function_exists("iTunes_Configuration"))===false) IPSUtils_Include ("iTunes.Configuration.inc.php","IPSLibrary::config::modules::iTunesSteuerung");				
            if (function_exists("iTunes_Configuration"))  $configInput = iTunes_Configuration();
            else echo "*************Fehler, iTunesSteuerung Konfig File nicht included oder Funktion iTunes_Configuration() nicht vorhanden. Es wird mit Defaultwerten gearbeitet.\n";

            if ($debug) 
                {
                echo "setiTunesConfig aufgerufen. SystemDir ist ".$this->getSystemDir().".\n";
                print_R($configInput);
                }

            /* Root der Konfig durchgehen, es wird das ganze Unterverzeichnis übernommen */
            configfileParser($configInput, $configMedia,    ["Media","media","MEDIA","iTunes","itunes","ITUNES"],"Media","[]");                // null es wird als Default zumindest ein Indexknoten angelegt
            configfileParser($configInput, $configOe3,      ["Oe3Player","oe3player","OE3PLAYER"],"Oe3Player","[]");

            configfileParser($configInput, $config,         ["Denon","denon","DENON"],"Denon","[]");
            configfileParser($configInput, $config,         ["Alexa","alexa","ALEXA"],"Alexa","[]");
            configfileParser($configInput, $config,         ["Netplayer","netplayer","NETPLAYER"],"Netplayer","[]");
            configfileParser($configInput, $config,         ["ttsPlayer","ttsplayer","TTSPLAYER"],"ttsPlayer","[]");
            configfileParser($configInput, $config,         ["iTunesSteuerung","itunessteuerung","ITUNESSTEUERUNG"],"iTunesSteuerung","[]");                // null es wird als Default zumindest ein Indexknoten angelegt

            configfileParser($configInput, $config, ["Configuration","configuration","CONFIGURATION"],"Configuration","[]");

            /* Sub Tabs */
            $config["Media"] = $this->checkConfigTabs($configMedia["Media"], $debug);       // mit oder ohne Debug, return output array, input array, widget config
            $config["Oe3Player"] = $this->checkConfigTabs($configOe3["Oe3Player"], $debug);       // mit oder ohne Debug, return output array, input array, widget config


         /*   $result=array();
            foreach (iTunes_Configuration() as $index => $configuration)
                {
                $result[$index]=$configuration;
                }
            return ($result);   */

            return ($config);
            }

        /* check Configuration for Media and Oe3Player
         * we expect Name, Profile, Side, Type, Link 
         */
        private function checkConfigTabs($configInput,$debug=false)
            {
            $config = array();
            foreach ($configInput as $name => $entry)
                {
                configfileParser($entry, $config[$name], ["Name","name","NAME"],"NAME",$name);
                configfileParser($entry, $config[$name], ["Profile","profile","PROFILE"],"PROFILE","");
                configfileParser($entry, $config[$name], ["Side","side","SIDE"],"SIDE","LEFT");
                $config[$name]["SIDE"]=strtoupper($config[$name]["SIDE"]);
                configfileParser($entry, $config[$name], ["Type","type","TYPE"],"TYPE","SWITCH");
                $config[$name]["TYPE"]=strtoupper($config[$name]["TYPE"]);
                configfileParser($entry, $config[$name], ["Link","link","LINK"],"LINK",null);                
                }

            return ($config);
            }

        public function getiTunesConfig()
            {
            return ($this->iTunesConfig);
            }

        /* systemDir ist private */

        public function getSystemDir()
            {
            return ($this->systemDir);
            }

        public function update_Page($categoryId_Oe3Player, $debug=false)
            {
            $html="";
            if (isset($this->installedModules["Startpage"]))  
                {
                IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
                IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
                IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

                $startpage = new StartpageHandler();
                //$configurationSP = $startpage->getStartpageConfiguration(); print_r($configurationSP["SpecialRegs"]);
                $html .= $startpage->writeStartpageStyle();
                $html .= "<div><table>";        
                //$html .= $startpage->StartPageWrite(1,true);
                //$html .= $startpage->showTempGroupWidget(true);       // show Station widget temp group
                //$html .= $startpage->showSpecialRegsWidget(false,true);       // show Station widget temp group, false take internal config, true Debug
                //$html .= $startpage->showTemperatureTable("",false,true);         // erster Parameter ist colspan als config für table
                $html .= $startpage->showWeatherTemperatureWidget(true);
                $html .= '</table></div>';                
                if ($debug) echo "iTunes Oe3 Page update";
                //echo $html;
                }

            $widgetID              = IPS_GetObjectIDByName("Widget",$categoryId_Oe3Player);
            SetValue($widgetID,$html);
            }        

        }


class MSSoapClient extends SoapClient {

    function __doRequest($request,$location,$action,$version,$oneway =NULL){


	$headers = array(
		'Method: POST',
		'Connection: close',
		'User-Agent: YOUR-USER-AGENT',
		'Content-Type: text/xml',
		'SOAPAction: "'.$action.'"',
	);

        $ch = curl_init($location);
	curl_setopt_array($ch,array(
		CURLOPT_VERBOSE=>false,
		CURLOPT_RETURNTRANSFER=>true,
		CURLOPT_POST=>true,
		CURLOPT_POSTFIELDS=>$request,
		CURLOPT_HEADER=>false,
		CURLOPT_HTTPHEADER=>$headers
	));

        $response = curl_exec($ch);

        return $response;

    }


}




class iTunesControll
    {
    private $maxRetry=5;

	/***
	 *COM Object for iTunes
	 */
	private $iTunesSOAP;

	/**
	 *Constructor, selber Name nicht mehr erlaubt
	 **/
	//function iTunesControll($adress)
    function __construct($adress)
	    {
                $success=false;
                $count=0;
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $this->iTunesSOAP= @new MSSoapClient("http://".$adress."/services?wsdl",array('connection_timeout' => 5));
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }
				if ($count==$this->maxRetry) { WriteLogEvent("SOAP Problem, 4 mal Curl-Fehler: "); throw new Exception('Soap client url nicht erreicht.'); }
                }
	    }

    function getPlayStatus()
        {

                $success=false;
                $count=0;
                $retVal=0;
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $retVal=$this->iTunesSOAP->getPlayStatus();
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

            return $retVal;
        }

    function playStream($streamName)
        {
            // return $this->iTunesCOM->OpenURL($streamName);
        }

    function getChangeGuid()
        {
            $success=false;
                $count=0;
                $retVal="";
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $retVal=$this->iTunesSOAP->getChangeGuid();
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }
				if ($count==$this->maxRetry) { WriteLogEvent("SOAP Problem, 4 mal getChangeGuid-Fehler: "); throw new Exception('Soap client url nicht erreicht.'); }
                }

            return $retVal;
        }

    function updateSpeaker($name,$vol,$act)
        {
                $success=false;
                $count=0;
                $retVal="";
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $this->iTunesSOAP->updateSpeaker($name,$vol,$act);
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

        }



    function getSpeakers()
        {
            $success=false;
                $count=0;
                $retVal=0;
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $retVal=$this->iTunesSOAP->getSpeakers();

                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

            return $retVal;
        }

    function getSpeakerCount()
        {
                $success=false;
                $count=0;
                $retVal="";
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $retVal=$this->iTunesSOAP->getNumberOfSpeakers();

                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

            return $retVal;
        }
    /**
    * returns the current track name
    *
    */
	function getTrackName()
	    {
                $success=false;
                $count=0;
                $retVal="";
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $retVal=$this->iTunesSOAP->getTrackName();;
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

            return $retVal;

	    }
    /*
    * Start playing the actual track
    */
	function play()
	    {

             $success=false;
             $count=0;

                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $this->iTunesSOAP->play();
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }




	    }

    /*
    * Sop playing the actual track
    */
	function stop()
    	{
            $success=false;
             $count=0;

                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $this->iTunesSOAP->pause();
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

	    }
    /*
    * Pause PLaying the actual track
    */
	function pause()
    	{
                $success=false;
                $count=0;

                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $this->iTunesSOAP->pause();
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }
	    }
    /*
    * Skip forward
    */
	function forward()
    	{
                $success=false;
                $count=0;

                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $this->iTunesSOAP->NextTrack();
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

	    }
    /*
    * Skip backward
    */
	function back()
    	{
                 $success=false;
                $count=0;

                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $this->iTunesSOAP->PreviousTrack();
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

	    }
    /*
    * Returns an assoative array of play lists
    */
	function &getPlayLists()
    	{

                $success=false;
                $plst=0;
                $count=0;
                $retVal="";
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $plst= $this->iTunesSOAP->getPlaylists();
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }




		return $plst;
	    }

	function getPlayList($index)
	    {

	    }

	function getTracksFromPlayList($index)
	    {

	    }

	function playTrack($pl,$track)
	    {
        }

	function getTracksFromCurrentPlayList()
	    {

	    }

	function getCurrenPlayListID($playListName)
	    {

	    }

	function getActualTrackTime()
	    {

             $success=false;
                $count=0;
                $retVal="";
                while(!$success &&($count<$this->maxRetry))
                {
                    try
                    {
                        $pos=$this->iTunesSOAP->PlayerPosition();
                        $min=intval($pos/60);
                        $sec=$pos%60;
                        $retVal= sprintf("%02d:%02d",$min,$sec);
                        $success=true;
                    }
                    catch (Exception $e)
                    {
                        $count++;
                    }

                }

            return $retVal;

	    }

	function storeCoverArt($path)
	    {

	    }

	function hasCoverArt()
	    {
	    }

	function getVolume()
	    {
		return $this->iTunesSOAP->getVolume();
	    }

	function setVolume($val)
	    {
		$this->iTunesSOAP->SetVolume($val);
	    }

    function playPlayList($id)
        {
            $this->iTunesSOAP->playPlayList($id);
        }

    function getTracks($start,$count,$id)
        {
           // IPS_LogMessage("Externe Exec", "Track gen");
            $tracks=$this->iTunesSOAP->getTracks($start,$count,$id);
           //IPS_LogMessage("Externe Exec", "Track gen finished");
            return $tracks;
        }

    function playPlaylistItem($persId,$containerId)
        {
            $this->iTunesSOAP->playPlaylistItem($persId,$containerId);
        }

    }


?>