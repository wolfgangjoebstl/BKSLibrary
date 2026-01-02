<?php
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

/*
Inital-Autor: philipp, Quelle: http://www.ip-symcon.de/forum/f53/denon-avr-3808-integration-7007/

Funktionen:
	*Funktionssammlung aller implementierten DENON-Status und Befehle
	*empängt die Steuerbefehle aus dem DENON.Actionscript,
		formatiert diese und sendt sie an den "DENON Client Socket"
*/


######################### Main Functions #######################################

function DENON_POWER($id, $value) // STANDBY oder ON
{
 CSCK_SendText($id, "PW".$value.chr(13));
 IPSLogger_Dbg(__file__, "Denon.Functions ".$id." PW".$value);
}

function DENON_MasterVolume($id, $value) // "UP" or "DOWN"
{
 CSCK_SendText($id, "MV".$value.chr(13));
}

function DENON_MasterVolumeFix($id, $value) // Volume direct -80(db) bis 18(db)
{
 $value= intval($value) +80;
 CSCK_SendText($id, "MV".$value.chr(13));
}

function DENON_BassLevel($id, $value)
{
	$value = (intval($value) +50);
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSBAS ".$value.chr(13));
}

function DENON_LFELevel($id, $value)
{
	$value = (intval($value) +10);
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSLFE ".$value.chr(13));
}

function DENON_TrebleLevel($id, $value)
{
	$value = (intval($value) +50);
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSTRE ".$value.chr(13));
}

function DENON_ChannelVolume($id, $value) // setzen Korrekturlevel pro LS-Kanal
{
 CSCK_SendText($id, "CV".$value.chr(13));
}

function DENON_MainMute($id, $value) // "ON" or "OFF"
{
 CSCK_SendText($id, "MU".$value.chr(13));
}

function DENON_Input($id, $value) // NET/USB; USB; NAPSTER; LASTFM; FLICKR; FAVORITES; IRADIO; SERVER; SERVER;  USB/IPOD
{
 CSCK_SendText($id, "SI".$value.chr(13));
}

function DENON_MainZonePower($id, $value) // MainZone "ON" or "OFF"
{
 CSCK_SendText($id, "ZM".$value.chr(13));
}

function DENON_RecSelect($id, $value) //
{
 CSCK_SendText($id, "SR".$value.chr(13)); // NET/USB; USB; NAPSTER; LASTFM; FLICKR; FAVORITES; IRADIO; SERVER; SERVER;  USB/IPOD
}

function DENON_SelectDecodeMode($id, $value) // AUTO; HDMI; DIGITAL; ANALOG
{
  CSCK_SendText($id, "SD".$value.chr(13));
}

function DENON_DecodeMode($id, $value) // Auto, PCM, DTS
{
 CSCK_SendText($id, "DC".$value.chr(13));
}

function DENON_VideoSelect($id, $value) // Video Select DVD/BD/TV/SAT_CBL/DVR/GAME/V.AUX/DOCK/SOURCE
{
 CSCK_SendText($id, "SV".$value.chr(13));
}

function DENON_SLEEP($id, $value) //
{
	if ($value == 0)
	{
		CSCK_SendText($id, "SLPOFF".chr(13));
	}
	ELSE
	{
	$value = str_pad($value, 3 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "SLP".$value.chr(13));
	}
}

function DENON_ModeSelect($id, $value) //
{
 CSCK_SendText($id, "MS".$value.chr(13));
}

function DENON_VideoSet($id, $value) //
{
 CSCK_SendText($id, "VS".$value.chr(13));
}

function DENON_ParaSettings($id, $value) // S
{
 CSCK_SendText($id, "PS".$value.chr(13));
}

function DENON_ParaVideo($id, $value) //
{
 CSCK_SendText($id, "PV".$value.chr(13));
}

function DENON_QuickSelect($id, $value) // 1-5
{
  CSCK_SendText($id, "MSQUICK".$value.chr(13));
}

function DENON_Preset($id, $value) //
{
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "NSB".$value.chr(13));
}

function DENON_NSE_Request($id) // fragt NSE-Werte ab
{
  CSCK_SendText($id, "NSE".chr(13));
}

function DENON_DynEQ($id, $value) // Dynamic Equilizer ON/OFF
{
  CSCK_SendText($id, "PSDYNEQ ".$value.chr(13));
}

function DENON_CinEQ($id, $value) // Cinema Equilizer ON/OFF
{
  CSCK_SendText($id, "PSCINEMA EQ.".$value.chr(13));
}
function DENON_MultiEQMode($id, $value) // MultiEquilizer AUDYSSEE/BYP.LR/FLAT/MANUELL/OFF
{
  CSCK_SendText($id, "PSMULTEQ:".$value.chr(13));
}

function DENON_DynVol($id, $value) // Dynamic Volume NGT(EVE/DAY
{
  CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
}

function DENON_AudioDelay($id, $value) // Audio Delay 0-200 ms
{
	$value = str_pad($value, 3 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSDELAY ".$value.chr(13));
}

function DENON_Dimension($id, $value) // Audio Delay 0-200 ms
{
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSDIM ".$value.chr(13));
}

function DENON_InputSource($id, $value) // Input Source
{
	//echo "Sende SourceInput ".$value."\n";
   CSCK_SendText($id, "SI".$value.chr(13));
}

function DENON_DynamicCompressor($id, $value) // Dynamic Compressor OFF/LOW/MID/HIGH
{
  CSCK_SendText($id, "PSDCO ".$value.chr(13));
}

function DENON_ToneDefeat($id, $value) // Tone Defeat (AVR3809) ON/OFF
{
  CSCK_SendText($id, "PSTONE DEFEAT ".$value.chr(13));
}

function DENON_ToneCTRL($id, $value) // Tone Control (AVR 3311) ON/OFF
{
  CSCK_SendText($id, "PSTONE CTRL ".$value.chr(13));
}

function DENON_AudioRestorer($id, $value) // Audio Restorer OFF/MODE1/MODE2/MODE3
{
	switch ($value)
	{
	   case 0:
	      $value = "OFF";
	      CSCK_SendText($id, "PSRSTR ".$value.chr(13));
		break;

		case 1:
	      $value = "MODE1";
	      CSCK_SendText($id, "PSRSTR ".$value.chr(13));
		break;

		case 2:
	      $value = "MODE2";
	      CSCK_SendText($id, "PSRSTR ".$value.chr(13));
		break;

		case 3:
	      $value = "MODE2";
	      CSCK_SendText($id, "PSRSTR ".$value.chr(13));
		break;

	}
}

function DENON_DigitalInputMode($id, $value) // Digital Input Mode AUTO/PCM/DTS
{
  CSCK_SendText($id, "DC".$value.chr(13));
}

function DENON_InputMode($id, $value) // Input Mode AUTO/HDMI/DIGITALANALOG/ARC/NO
{
  CSCK_SendText($id, "SD".$value.chr(13));
}

function DENON_DynamicRange($id, $value) // DynamicRange
{
  CSCK_SendText($id, "PSDRC ".$value.chr(13));
}

function DENON_DynamicEQ($id, $value)
{
  CSCK_SendText($id, "PSDYNEQ ".$value.chr(13));
}

function DENON_DynamicVolume($id, $value)
{
	switch ($value)
		{
		   case 0:
		      $value = "OFF";
		      CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
			break;

			case 1:
		      $value = "NGT";
		      CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
			break;

			case 2:
		      $value = "EVE";
		      CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
			break;

			case 3:
		      $value = "DAY";
		      CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
			break;

		}
}

function DENON_RoomSize($id, $value)
{
	switch ($value)
		{
		   case 0:
		      $value = "N";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 1:
		      $value = "S";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 2:
		      $value = "MS";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 3:
		      $value = "M";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 4:
		      $value = "MS";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 5:
		      $value = "L";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

		}
}

function DENON_SurroundBackMode($id, $value)
{
  CSCK_SendText($id, "PSSB:".$value.chr(13));
}

function DENON_CWidth($id, $value)
{
  CSCK_SendText($id, "PSCEN ".$value.chr(13));
}

function DENON_SurroundMode($id, $value)
{
  CSCK_SendText($id, "MS".$value.chr(13));
}

function DENON_SurroundPlayMode($id, $value)
{
  CSCK_SendText($id, "PSMODE:".$value.chr(13));
}

function DENON_CinemaEQ($id, $value)
{
  CSCK_SendText($id, "PSCINEMA EQ.".$value.chr(13));
}

function DENON_Panorama($id, $value)
{
  CSCK_SendText($id, "PSPAN ".$value.chr(13));
}

function DENON_FrontHeight($id, $value)
{
  CSCK_SendText($id, "PSFH:".$value.chr(13));
}

function DENON_NSE_DisplayRequest($id)
{
  CSCK_SendText($id, "NSE".chr(13));
}

function DENON_NSA_DisplayRequest($id)
{
  CSCK_SendText($id, "NSA".chr(13));
}

function DENON_PresetRequest($id)
{
  CSCK_SendText($id, "NSH".chr(13));
}

function DENON_ChannelVolumeFL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFL ".$value.chr(13));
}

function DENON_ChannelVolumeFR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFR ".$value.chr(13));
}

function DENON_ChannelVolumeC($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVC ".$value.chr(13));
}

function DENON_ChannelVolumeSW($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSW ".$value.chr(13));
}

function DENON_ChannelVolumeSL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSL ".$value.chr(13));
}

function DENON_ChannelVolumeSR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSR ".$value.chr(13));
}

function DENON_ChannelVolumeSBL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSBL ".$value.chr(13));
}

function DENON_ChannelVolumeSBR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSBR ".$value.chr(13));
}

function DENON_ChannelVolumeSB($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSB ".$value.chr(13));
}

function DENON_ChannelVolumeFHL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFHL ".$value.chr(13));
}

function DENON_ChannelVolumeFHR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFHR ".$value.chr(13));
}

function DENON_ChannelVolumeFWL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFWL ".$value.chr(13));
}

function DENON_ChannelVolumeFWR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFWR ".$value.chr(13));
}

######################## Cursor Steuerung ######################################

function DENON_CursorUp($id)
{
  CSCK_SendText($id, "MNCUP".chr(13));
}

function DENON_CursorDown($id)
{
  CSCK_SendText($id, "MNCDN".chr(13));
}

function DENON_CursorLeft($id)
{
  CSCK_SendText($id, "MNCLT".chr(13));
}

function DENON_CursorRight($id)
{
  CSCK_SendText($id, "MNCRT".chr(13));
}

function DENON_Enter($id)
{
  CSCK_SendText($id, "MNENT".chr(13));
}

function DENON_Return($id)
{
  CSCK_SendText($id, "MNRTN".chr(13));
}


######################## Zone 2 functions ######################################

function DENON_Z2_Volume($id, $value) // "UP" or "DOWN"
{
	CSCK_SendText($id, "Z2".$value.chr(13));
}

function DENON_Zone2VolumeFix($id, $value) // 18(db) bis -80(db)
{
	$value= intval($value) +80;
	CSCK_SendText($id, "Z2".$value.chr(13));
}

function DENON_Zone2Power($id, $value) // "ON" or "OFF"
{
	CSCK_SendText($id, "Z2".$value.chr(13));
}

function DENON_Zone2Mute($id, $value) // "ON" or "OFF"
{
	CSCK_SendText($id, "Z2MU".$value.chr(13));
}

function DENON_Zone2InputSource($id, $value) // PHONO ; DVD ; HDP ; "TV/CBL" ; SAT ; "NET/USB" ; DVR ; TUNER
{
	CSCK_SendText($id, "Z2".$value.chr(13));
}

function DENON_Zone2ChannelSetting($id, $value) // Zone 2 Channel Setting: STEREO/MONO
{
	CSCK_SendText($id, "Z2CS".$value.chr(13));
}

function DENON_Zone2QuickSelect($id, $value) // Zone 2 Quickselect 1-5
{
	$value = $value +1;
	CSCK_SendText($id, "Z2QUICK".$value.chr(13));
}

function DENON_Zone2ChannelVolumeFL($id)
{
   $value = $value + 50;
	CSCK_SendText($id, "Z2CVFL ".$value.chr(13));
}

function DENON_Zone2ChannelVolumeFR($id)
{
   $value = $value + 50;
	CSCK_SendText($id, "Z2CVFR ".$value.chr(13));
}

########################## Zone 3 Functions ####################################

function DENON_Zone3Volume($id, $value) // "UP" or "DOWN"
{
	CSCK_SendText($id, "Z3".$value.chr(13));
}

function DENON_Zone3VolumeFix($id, $value) // 18(db) bis -80(db)
{
	$value= intval($value) +80;
	CSCK_SendText($id, "Z3".$value.chr(13));
}

function DENON_Zone3Power($id, $value) // "ON" or "OFF"
{
	CSCK_SendText($id, "Z3".$value.chr(13));
}

function DENON_Zone3Mute($id, $value) // "ON" or "OFF"
{
	CSCK_SendText($id, "Z3MU".$value.chr(13));
}

function DENON_Zone3InputSource($id, $value) // PHONO ; DVD ; HDP ; "TV/CBL" ; SAT ; "NET/USB" ; DVR
{
	CSCK_SendText($id, "Z3".$value.chr(13));
}

function DENON_Zone3ChannelSetting($id, $value) // Zone 3 Channel Setting: STEREO/MONO
{
	CSCK_SendText($id, "Z3CS".$value.chr(13));
}

function DENON_Zone3QuickSelect($id, $value) // Zone 3 Quickselect 1-5
{
   $value = $value +1;
	CSCK_SendText($id, "Z3QUICK".$value.chr(13));
}

function DENON_Zone3ChannelVolumeFL($id)
{
   $value = $value + 50;
	CSCK_SendText($id, "Z3CVFL ".$value.chr(13));
}

function DENON_Zone3ChannelVolumeFR($id)
{
   $value = $value + 50;
	CSCK_SendText($id, "Z3CVFR ".$value.chr(13));
}

?>