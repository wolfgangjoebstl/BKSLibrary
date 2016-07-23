<?

/*
	 * @defgroup Startpage
	 *
	 * Script zur Ansteuerung der Startpage
	 *
	 *
	 * @file          Startpage.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/


include_once IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\NetPlayer\NetPlayer.inc.php";

if (false)
	{
	NetPlayer_Power(true);
	$value=0;   /* MP3 Player */
	$value=1;   /* Radio */
	NetPlayer_SetSource($value);
	
	/* einmal aufrufen, damit Radiosenderliste übernommen wird */
	NetPlayer_NavigateRadioForward(NP_COUNT_RADIOVARIABLE);
	
	$radioName = array();
	$profileData   = IPS_GetVariableProfile('NetPlayer_RadioList');
	$associations  = $profileData['Associations'];
	foreach ($associations as $idx=>$association)
		{
		echo "Radiosender : ".$idx." ".$association['Name']."\n";
		//print_r($association);
	   if ($association['Value']==$value)
			{
	      $radioName = $association['Name'];
	   	}
	   }
	$radioList = NetPlayer_GetRadioList();
	$radioUrl  = $radioList[$radioName];
	NetPlayer_PlayRadio($radioUrl, $radioName);
	echo "Es spielt jetzt ".$radioName." von ".$radioUrl."\n";
	}
//NetPlayer_Power(false);

?>