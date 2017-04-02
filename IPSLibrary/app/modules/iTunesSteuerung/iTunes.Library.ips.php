<?

/* 
 * Library für iTunes, class Definition 
 *
 *
 **/


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
	 *Constructor
	 **/
	function iTunesControll($adress)
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