<?php
include "HM_Heizung_Funktionen.ips.php";
include "hmxml.inc.php";
include "HM_Heizung_Konfig.ips.php";

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Stromheizung',$repository);
		}
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	// Add Scripts
	$scriptIdActionScript  = IPS_GetScriptIDByName('HM_Edit', $CategoryIdApp);
  	$HMXML_DataPath='Program.IPSLibrary.data.hardware.IPSHomematic.ThermostatConfig';
   	$categoryId_hmxml = CreateCategoryPath($HMXML_DataPath);
    $categoryId_control=CreateCategory("Control",$categoryId_hmxml,10);

    echo "Kategorie HMXML : $categoryId_hmxml ,   Control : $categoryId_control ,  Action Script ID : $scriptIdActionScript  \n";

  	$WFE_LinkPath='Visualization.WebFront.Administrator.OperationCenter.Thermostate';
    $categoryId_wfe = CreateCategoryPath($WFE_LinkPath);

$dayArray = array("MONDAY","TUESDAY","WEDNESDAY","THURSDAY","FRIDAY","SATURDAY","SUNDAY");



$HM_Zimmer_Auswahl_id=CreateVariableByName($categoryId_control, "HM_Edit_Zimmer_Auswahl", 1, Profil_anlegen("HM_Heizung_Auswahl"), "HMeditZimmerAuswahl", 1);
IPS_SetVariableCustomAction($HM_Zimmer_Auswahl_id, $scriptIdActionScript);

$Praesenz_Profil_Auswahl_id=CreateVariableByName($categoryId_control, "HM_Edit_Präsenz_Profil", 1, "Praesenz", "HMeditPraesenzProfil", 2);
IPS_SetVariableCustomAction($Praesenz_Profil_Auswahl_id, $scriptIdActionScript);

$Profil_Wochentage_id= CreateVariableByName($categoryId_control, "HM_Edit_Wochentag_Auswahl", 1, "HM_Heizung_Wochentag", "HMeditWochentagAuswahl", 3);
IPS_SetVariableCustomAction($Profil_Wochentage_id, $scriptIdActionScript);

$Profil_Slot_Auswahl_id= CreateVariableByName($categoryId_control, "HM_Edit_TagesProfil_Slot_Auswahl", 1, "HM_Heizung_Slot", "HMeditTagesProfilSlotAuswahl", 4);
IPS_SetVariableCustomAction($Profil_Slot_Auswahl_id, $scriptIdActionScript);

$TagesProfil_edit_Anzeige_id = CreateVariableByName($categoryId_control, "HM_Edit_Tagesprofil_Anzeige_html", 3, "~HTMLBox", "HMeditTagesProfilAnzeigehtml", 5);

$WochenProfil_edit_Anzeige_id=CreateVariableByName($categoryId_control, "HM_Edit_WochenProfil_Anzeige_html", 3, "~HTMLBox", "HMeditWochenProfilAnzeigehtml", 6);

$WochenProfil_Daten_edit_id=CreateVariableByName($categoryId_control, "HM_Edit_WochenProfil_Daten", 3, "~String", "HMeditWochenProfilDaten", 7);

$Temp_edit_id=CreateVariableByName($categoryId_control, "HM_Edit_+1°C_-1°C", 1, "HM_Heizung_Temperatur_Edit", "HMeditTemp", 8);
IPS_SetVariableCustomAction($Temp_edit_id, $scriptIdActionScript);

$Zeit_edit_id=CreateVariableByName($categoryId_control, "HM_Edit_+10min_-10min", 1, "HM_Heizung_Zeit_Edit", "HMeditZeit", 9);
IPS_SetVariableCustomAction($Zeit_edit_id, $scriptIdActionScript);

$Slot_add_del_id=CreateVariableByName($categoryId_control, "HM_Edit_Slot_add_del", 1, "HM_Heizung_Slot_add_del", "HMeditSlot", 10);
IPS_SetVariableCustomAction($Slot_add_del_id, $_IPS['SELF']);

$Wochenprofil_speichern_id=CreateVariableByName($categoryId_control, "HM_Edit_Profil_speichern", 1, "HM_Heizung_Profil_speichern", "HMeditProfilspeichern", 11);
IPS_SetVariableCustomAction($Wochenprofil_speichern_id, $scriptIdActionScript);

$Profil_uebertragen_show_id= CreateVariableByName($categoryId_control, "HM_Edit_Profil_übertragen", 1, "HM_heizung_Profil_uebertragen", "HMeditProfiluebertragen", 12);
IPS_SetVariableCustomAction($Profil_uebertragen_show_id, $scriptIdActionScript);

$Tagesprofil_kopieren_id= CreateVariableByName($categoryId_control, "HM_Edit_Tragesprofil_kopieren", 1, "HM_Heizung_Wochentag", "HMeditTagesProfilkopieren", 13);
IPS_SetVariableCustomAction($Tagesprofil_kopieren_id, $scriptIdActionScript);

$Wochenprofil_uebernehmen_von_id =  CreateVariableByName($categoryId_control, "HM_Edit_Wochenprofil_übernehmen_von", 1, "HM_Heizung_Auswahl", "HMeditWochenProfiluebernehmen", 14);
IPS_SetVariableCustomAction($Wochenprofil_uebernehmen_von_id, $scriptIdActionScript);

$Wochenprofil_uebernehmen_von_Praesenz_Profil_id =  CreateVariableByName($categoryId_control, "HM_Edit_Wochenprofil_übernehmen_von_Praesenz_Profil", 1, "Praesenz", "HMeditWochenProfiluebernehmenPraesenz", 15);
IPS_SetVariableCustomAction($Wochenprofil_uebernehmen_von_Praesenz_Profil_id, $scriptIdActionScript);

//***************************************************************************************************************************************
//SetLinkByName($parentID, $name, $targetID, $position)
//SetKatByName($parentID, $name, $ident, $position)

$KatID=SetKatByName($categoryId_wfe, "Zeitplan - Editieren - Anzeige links oben", "", 1);
SetLinkByName($KatID, "Zimmer Auswahl", $HM_Zimmer_Auswahl_id, 1);
SetLinkByName($KatID, "Präsenz Profil Auswahl", $Praesenz_Profil_Auswahl_id, 2);
SetLinkByName($KatID, "Wochenprofil", $WochenProfil_edit_Anzeige_id, 3);

$KatID=SetKatByName($categoryId_wfe, "Zeitplan - Editieren - Anzeige links unten", "", 2);
SetLinkByName($KatID, "Zimmer Auswahl", $TagesProfil_edit_Anzeige_id, 1);

$KatID=SetKatByName($categoryId_wfe, "Zeitplan - Editieren - Anzeige rechts", "", 3);
$DumID=SetDummyByName($KatID, "Editieren", "", 1);
SetLinkByName($DumID, "Wochentag", $Profil_Wochentage_id, 1);
SetLinkByName($DumID, "Zeit-Slot", $Profil_Slot_Auswahl_id, 2);
SetDummyByName($DumID, chr(127), "", 3);
SetLinkByName($DumID, "Zeit", $Zeit_edit_id, 4);
SetLinkByName($DumID, "Temperatur", $Temp_edit_id, 5);
SetLinkByName($DumID, "ZeitSlot", $Slot_add_del_id, 6);
SetLinkByName($KatID, "Profil speichern", $Wochenprofil_speichern_id, 2);
SetLinkByName($KatID, "Profil übertragen", $Profil_uebertragen_show_id, 3);

$Dum_uebertragenID=SetDummyByName($KatID, "Profil übertragen / Profil übernehmen", "", 4);
SetLinkByName($Dum_uebertragenID, "Tragesprofil kopieren nach", $Tagesprofil_kopieren_id, 1);
SetDummyByName($Dum_uebertragenID, chr(127), "", 2);
SetDummyByName($Dum_uebertragenID, "Wochenprofil übernehmen von", "", 3);
SetLinkByName($Dum_uebertragenID, "Präsenz", $Wochenprofil_uebernehmen_von_Praesenz_Profil_id, 4);
SetLinkByName($Dum_uebertragenID, "Wochenprofil", $Wochenprofil_uebernehmen_von_id, 5);


//***************************************************************************************************************************************
Foreach ($Zimmer as $key=> $Raum)
   {
   $id = @IPS_GetCategoryIDByName ($Raum, IPS_GetParent ($parentID));
   if($id != false)
		{
		$IPS_HM_Wochenprofil[$key]=@IPS_GetObjectIDByIdent ("HMWochenprofilDaten", $id);  // id der Variable für die gespeicherten Wochenprofile mit Präsenz
		$IPS_HM_aktives_PraesenzProfil_Heizung[$key]=@IPS_GetObjectIDByIdent ("HMPraesenzProfilAuswahl", $id);
		$HM_Heizung_WochenProfil_Anzeige_html_ID[$key]=@IPS_GetObjectIDByIdent ("HMWochenprofilhtml", $id);;
		 }
	}
//***************************************************************************************************************************************

if ($_IPS['SENDER']=='WebFront')
	{

	If ($_IPS['VARIABLE']==$Wochenprofil_speichern_id)
		{
		If (GetValue($Wochenprofil_speichern_id)==1) // Es liegen Änderungen zum speichern vor
		   {
		   // Teste Integrität der Tagesprofile
		   If (HM_WochenTempProfil_prüfen($WochenProfil_Daten_edit_id) == "OK")
		      {
		      // echo "Alles OK !!";
		      // nur wenn editiertes Wochenprofil zur aktuellen (Heizungs)Präsenz passt, dann übertrage das Profil auch an das Thermostat
			   If (GetValue($IPS_HM_aktives_PraesenzProfil_Heizung[GetValue($HM_Zimmer_Auswahl_id)])==GetValue($Praesenz_Profil_Auswahl_id))  
					{
					If ($HM_Typ[GetValue($HM_Zimmer_Auswahl_id)]=="HM-TC-IT-WM-W-EU")
					   {
                  $tmp=HMXML_setTempProfile($IPS_HM_DeviceID[GetValue($HM_Zimmer_Auswahl_id)], unserialize(GetValue($WochenProfil_Daten_edit_id)),GetValue($Praesenz_Profil_Auswahl_id)+1);
					   }
					   else
					   {
					 	$tmp=HMXML_setTempProfile($IPS_HM_DeviceID[GetValue($HM_Zimmer_Auswahl_id)], unserialize(GetValue($WochenProfil_Daten_edit_id)));
						}
						
					if ($tmp==true)
					   {
					   echo "Speichern Erfolgreich";

						$aktProfiltmp = HMXML_getTempProfile($IPS_HM_DeviceID[GetValue($HM_Zimmer_Auswahl_id)],false, false,GetValue($Praesenz_Profil_Auswahl_id)+1);

						$Profiltemp=unserialize(GetValue($IPS_HM_Wochenprofil[GetValue($HM_Zimmer_Auswahl_id)]));

						$Profiltemp[GetValueFormatted($Praesenz_Profil_Auswahl_id)]=$aktProfiltmp; //z.B. $Profiltemp['Normal']=$aktProfiltmp
        				Setvalue($IPS_HM_Wochenprofil[GetValue($HM_Zimmer_Auswahl_id)], serialize($Profiltemp));

						$ProfilDaten= unserialize(GetValue($IPS_HM_Wochenprofil[GetValue($HM_Zimmer_Auswahl_id)]));
						SetValue($WochenProfil_Daten_edit_id, serialize(@$ProfilDaten[GetValueFormatted($Praesenz_Profil_Auswahl_id)]));

				        SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));


						$tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
						$day=GetValue($Profil_Wochentage_id);
						SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
						SetValue($Wochenprofil_speichern_id,0);  // Speicher Button zurücksetzen
						
						SetValue($HM_Heizung_WochenProfil_Anzeige_html_ID[GetValue($HM_Zimmer_Auswahl_id)], HM_WochenTempProfil_html($WochenProfil_Daten_edit_id));
						
					   }
					 }
					 else // Wenn aktives PräsenzProfil nicht dem editierten Profil entspricht, dann wird nicht zm Thermostat übertragen sondern nur abgespeichert
					 {
					 If ($HM_Typ[GetValue($HM_Zimmer_Auswahl_id)]=="HM-TC-IT-WM-W-EU")
					   {
                  $tmp=HMXML_setTempProfile($IPS_HM_DeviceID[GetValue($HM_Zimmer_Auswahl_id)], unserialize(GetValue($WochenProfil_Daten_edit_id)),GetValue($Praesenz_Profil_Auswahl_id)+1);
					   }

					 $tmp=unserialize(GetValue($WochenProfil_Daten_edit_id));
					 $Profiltemp=unserialize(GetValue($IPS_HM_Wochenprofil[GetValue($HM_Zimmer_Auswahl_id)]));

					 $Profiltemp[GetValueFormatted($Praesenz_Profil_Auswahl_id)]=$tmp; //z.B. $Profiltemp['Normal']=$tmp
        			 Setvalue($IPS_HM_Wochenprofil[GetValue($HM_Zimmer_Auswahl_id)], serialize($Profiltemp));

					 $ProfilDaten= unserialize(GetValue($IPS_HM_Wochenprofil[GetValue($HM_Zimmer_Auswahl_id)]));
					 SetValue($WochenProfil_Daten_edit_id, serialize(@$ProfilDaten[GetValueFormatted($Praesenz_Profil_Auswahl_id)]));

				    SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
					 $tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
					 $day=GetValue($Profil_Wochentage_id);
					 SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
					 SetValue($Wochenprofil_speichern_id,0);  // Speicher Button zurücksetzen
    				 }
    		    }
		     else
				{
				echo HM_WochenTempProfil_prüfen($WochenProfil_Daten_edit_id);
				}
          }
  		 }
    	else
		 {
		 SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
		 }


	Switch ($_IPS['VARIABLE'])
	   {
		Case $HM_Zimmer_Auswahl_id:
		Case $Praesenz_Profil_Auswahl_id:
			  //33994*[Gewerke\Heizung\Zeitplan - Editieren\HM_Edit_Zimmer_Auswahl]*/

			If (GetValue($HM_Zimmer_Auswahl_id)== 0) // Zurücksetzen
			   {
			   SetValue($Profil_Slot_Auswahl_id,1);
			   SetValue($Profil_Wochentage_id,0);
			   SetValue($WochenProfil_Daten_edit_id, "");
			   $str = 	$str = "<table align='center' border=0 cellpadding=3 cellspacing=3 width=90%>
								<tr height=30 ><th> Slot </th><th> Start </th><th> Ende </th><th> SollWert</th></tr>";
            $str .= "<tr  align='center' height=25> <td width=25%>  </td><td width=25%> </td><td width=25%> </td><td width=25%> </td></tr></table>";
            SetValue($TagesProfil_edit_Anzeige_id, $str);
				SetValue($WochenProfil_edit_Anzeige_id,"");
				SetValue($Wochenprofil_speichern_id,0);  // Speicher Button zurücksetzen
				}

			If (GetValue($HM_Zimmer_Auswahl_id)> 0)
				{
            $ProfilDaten= unserialize(GetValue($IPS_HM_Wochenprofil[GetValue($HM_Zimmer_Auswahl_id)]));
				SetValue($WochenProfil_Daten_edit_id, serialize(@$ProfilDaten[GetValueFormatted($Praesenz_Profil_Auswahl_id)]));

				If (GetValue($WochenProfil_Daten_edit_id)=="N;")  //Profil ist leer
				   {
				   $str = $str = "<table align='center' border=0 cellpadding=3 cellspacing=3 width=90%>
								<tr height=30 ><th> Slot </th><th> Start </th><th> Ende </th><th> SollWert</th></tr>";
		         $str .= "<tr  align='center' height=25> <td width=25%>  </td><td width=25%> </td><td width=25%> </td><td width=25%> </td></tr></table>";
	            SetValue($TagesProfil_edit_Anzeige_id, $str);
					SetValue($WochenProfil_edit_Anzeige_id,"");
					SetValue($Wochenprofil_speichern_id,0);  // Speicher Button zurücksetzen
   			   }
				   else
					{
    				SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
					$tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
					$day=GetValue($Profil_Wochentage_id);
					SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
					SetValue($Wochenprofil_speichern_id,0);  // Speicher Button zurücksetzen
					}
				}

		 break;

		 Case $Profil_Slot_Auswahl_id:
         If (GetValue($HM_Zimmer_Auswahl_id)!=0)
				{
            $tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
				$day=GetValue($Profil_Wochentage_id);
		      SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
		      }
		 break;


	 	 Case $Profil_Wochentage_id: //34509

			If (GetValue($HM_Zimmer_Auswahl_id)!=0)
			   {
			   $tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
				$day=GetValue($Profil_Wochentage_id);
  				SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
            SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));


  				If (IPS_GetVariableProfile("HM_Heizung_Slot")['MaxValue']<GetValue($Profil_Slot_Auswahl_id))
  				   {
					SetValue($Profil_Slot_Auswahl_id,IPS_GetVariableProfile("HM_Heizung_Slot")['MaxValue']);
  				   }
      	   }
  	    break;


		 Case $Zeit_edit_id: //17552 *[Gewerke\Heizung\Zeitplan - Editieren\HM_Edit_+10min_-10min]*/

			If (GetValue($HM_Zimmer_Auswahl_id)!=0)
			   {
	         $tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
				$day=GetValue($Profil_Wochentage_id);
				$Slot=GetValue($Profil_Slot_Auswahl_id);

            SetValue($Wochenprofil_speichern_id,1);  // Speicher Button freigeben

				If ($Slot< count($tmp[$dayArray[$day]]['EndTimes'])) // Verhindert dass die Zeit des letzten Slots geändert wird (ist immer 24:00)
					{
					If ($Slot==1)
					   {
						$Start = "00:00";
						}
					  else
						{
						$Start = $tmp[$dayArray[$day]]['EndTimes'][$Slot-2];
						}

					$Ende = $tmp[$dayArray[$day]]['EndTimes'][$Slot-1];
					$Ende_next = $tmp[$dayArray[$day]]['EndTimes'][$Slot];

					Switch (GetValue($Zeit_edit_id))
					   {
						Case 1: // -1h
							If ((strtotime($Ende)-(60*60))> strtotime($Start))
							   {
							   $Ende = date("H:i", (strtotime($Ende)-(60*60)));
					   		}
	                  	$tmp[$dayArray[$day]]['EndTimes'][$Slot-1]=$Ende;
   		               SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
	      	            SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
	      	            SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id,GetValue($Profil_Wochentage_id)));
					   break;




						Case 2: // -10 min
							If ((strtotime($Ende)-(10*60))> strtotime($Start))
							   {
							   $Ende = date("H:i", (strtotime($Ende)-(10*60)));
					   		}
	                  	$tmp[$dayArray[$day]]['EndTimes'][$Slot-1]=$Ende;
   		               SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
	      	            SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
	      	            SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
					   break;

						Case 3: // -5 min nur beim HM-TC-IT-WM-W-EU
						   If ($HM_Typ[GetValue($HM_Zimmer_Auswahl_id)]=="HM-TC-IT-WM-W-EU")
						      {
						      If ((strtotime($Ende)-(5*60))> strtotime($Start))
							   	{
								   $Ende = date("H:i", (strtotime($Ende)-(5*60)));
						   		}
	   	               	$tmp[$dayArray[$day]]['EndTimes'][$Slot-1]=$Ende;
  				               SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
      			            SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
      		   	         SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
						      }
					   break;


						Case 4: // +5 min nur beim HM-TC-IT-WM-W-EU
						   If ($HM_Typ[GetValue($HM_Zimmer_Auswahl_id)]=="HM-TC-IT-WM-W-EU")
						      {
								If ((strtotime($Ende)+(5*60))< strtotime($Ende_next))
								   {
								   $Ende = date("H:i", (strtotime($Ende)+(5*60)));
								   }
            	   		   $tmp[$dayArray[$day]]['EndTimes'][$Slot-1]=$Ende;
	            		      SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
	   	            	   SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
	   	               	SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
						      }
					   break;


						Case 5: // +10min
							If ((strtotime($Ende)+(10*60))< strtotime($Ende_next))
							   {
							   $Ende = date("H:i", (strtotime($Ende)+(10*60)));
							   }
               		   $tmp[$dayArray[$day]]['EndTimes'][$Slot-1]=$Ende;
	                  	SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
	   	               SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
	   	               SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
 						break;

						Case 6: // +1h
							If ((strtotime($Ende)+(60*60))< strtotime($Ende_next))
							   {
							   $Ende = date("H:i", (strtotime($Ende)+(60*60)));
							   }
               		   $tmp[$dayArray[$day]]['EndTimes'][$Slot-1]=$Ende;
	                  	SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
	   	               SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
	   	               SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
 						break;

   				   }
   				}
				 SetValue($Zeit_edit_id,0);
				 }
		 break;

		 Case $Temp_edit_id:	//36633*[Gewerke\Heizung\Zeitplan - Editieren\HM_Edit_+1°C_-1°C]*/

			If (GetValue($HM_Zimmer_Auswahl_id)!=0)
			   {
				$tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
				$day=GetValue($Profil_Wochentage_id);
				$Slot=GetValue($Profil_Slot_Auswahl_id);
         	$Temperatur = $tmp[$dayArray[$day]]['Values'][$Slot-1];

				SetValue($Wochenprofil_speichern_id,1);  // Speicher Button freigeben

	         Switch (GetValue($Temp_edit_id))
				   {
				   Case 1: // -1°C min

				      If (($Temperatur-1)>=4.99)
							{
							$Temperatur=$Temperatur-1;
							$tmp[$dayArray[$day]]['Values'][$Slot-1]=$Temperatur;
	            	   SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
   	            	SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
   	            	SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
							}
		   	   break;

			   	Case 2: // -0.1°C min
						If (($Temperatur-0.1)>4.9)
							{
							$Temperatur=$Temperatur-0.1;
							$tmp[$dayArray[$day]]['Values'][$Slot-1]=$Temperatur;
      	   	      SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
         	   	   SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
         	   	   SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
							}
			      break;

				   Case 3: // +0.1°C min
				      If (($Temperatur+0.1)<30.1)
							{
							$Temperatur=$Temperatur+0.1;
							$tmp[$dayArray[$day]]['Values'][$Slot-1]=$Temperatur;
            		   SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
               		SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
               		SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
							}
			      break;

				   Case 4: // +1°C min
				      If (($Temperatur+1)<=30.01)
							{
							$Temperatur=$Temperatur+1;
							$tmp[$dayArray[$day]]['Values'][$Slot-1]=$Temperatur;
            		   SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
               		SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
               		SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
							}
			      break;
					}
					SetValue($Temp_edit_id,0);
			   }
		   break;

			Case $Slot_add_del_id://*[Gewerke\Heizung\Zeitplan - Editieren\HM_Edit_Slot_add_del]*/
			   If (GetValue($HM_Zimmer_Auswahl_id)!=0)
			   	{
	             $tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
					 $day=GetValue($Profil_Wochentage_id);
					 $Slot=GetValue($Profil_Slot_Auswahl_id);
					 $Slots = count($tmp[$dayArray[$day]]['EndTimes']);

					 SetValue($Wochenprofil_speichern_id,1);  // Speicher Button freigeben

				  	 Switch (GetValue($Slot_add_del_id))
				      {

						Case 1:   // ZeitSlot löschen

							If ($Slots >1)  // letzter TimeSlot darf nicht gelöscht werden
							   {
								array_splice($tmp[$dayArray[$day]]['EndTimes'], $Slot-1, 1);
         	         	array_splice($tmp[$dayArray[$day]]['Values'], $Slot-1, 1);
                        $tmp[$dayArray[$day]]['EndTimes'][$Slots-2]="24:00"; // letzten TimeSlot Ende immer 24:00



            	         SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
		   	   	      SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
		   	      	   SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));

								If (IPS_GetVariableProfile("HM_Heizung_Slot")['MaxValue']<GetValue($Profil_Slot_Auswahl_id))
  						   		{
									SetValue($Profil_Slot_Auswahl_id,IPS_GetVariableProfile("HM_Heizung_Slot")['MaxValue']);
  						   		}
								}
				   	 break;


					    Case 2:   // ZeitSlot anfügen

							If ($Slots <24)  // max 24 TimeSlots zulässig
							   {
								array_splice($tmp[$dayArray[$day]]['EndTimes'], $Slots, 0, "24:00");
         	         	array_splice($tmp[$dayArray[$day]]['Values'], $Slots, 0, "17");

            	         SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
		   	   	      SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
		   	      	   SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));

								If (IPS_GetVariableProfile("HM_Heizung_Slot")['MaxValue']<GetValue($Profil_Slot_Auswahl_id))
  						   		{
									SetValue($Profil_Slot_Auswahl_id,IPS_GetVariableProfile("HM_Heizung_Slot")['MaxValue']);
						   		}
								}
						  break;

				      }
					SetValue($Slot_add_del_id,0);
					}


			break;

			Case $Profil_uebertragen_show_id:

   			//Ein- und Ausschalten der erweiterten Anzeige
           IPS_SetHidden($Dum_uebertragenID, !IPS_GetObject ($Dum_uebertragenID)['ObjectIsHidden']);

			break;


			Case $Tagesprofil_kopieren_id:
              If (GetValue($HM_Zimmer_Auswahl_id)!=0)
			   	{
					$tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
					$day_ziel=$dayArray[GetValue($Tagesprofil_kopieren_id)];
					$day_quelle=$dayArray[GetValue($Profil_Wochentage_id)];
					array_splice($tmp[$day_ziel]['EndTimes'], 0, count($tmp[$day_ziel]['EndTimes']), $tmp[$day_quelle]['EndTimes']);
					array_splice($tmp[$day_ziel]['Values'], 0, count($tmp[$day_ziel]['Values']), $tmp[$day_quelle]['Values']);

					SetValue($WochenProfil_Daten_edit_id,serialize($tmp));
            	SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));

				   SetValue($Wochenprofil_speichern_id,1);  // Speicher Button freigeben
					}


			break;

			case $Wochenprofil_uebernehmen_von_id:
              If (GetValue($HM_Zimmer_Auswahl_id)!=0)
			   	{
	            If (GetValue($Wochenprofil_uebernehmen_von_id)!=0)
			   		{
                  $ProfilDaten= unserialize(GetValue($IPS_HM_Wochenprofil[GetValue($Wochenprofil_uebernehmen_von_id)])); // Wochenprofil holen
				      $tmp=@$ProfilDaten[GetValueFormatted($Wochenprofil_uebernehmen_von_Praesenz_Profil_id)]; // Wochenprofil passend zur gewünschten Praesenz
         			SetValue($WochenProfil_Daten_edit_id,serialize($tmp));


						If (GetValue($WochenProfil_Daten_edit_id)=="N;")  //Profil ist leer
				   		{
						   $str = $str = "<table align='center' border=0 cellpadding=3 cellspacing=3 width=90%>
									<tr height=30 ><th> Slot </th><th> Start </th><th> Ende </th><th> SollWert</th></tr>";
		   		      $str .= "<tr  align='center' height=25> <td width=25%>  </td><td width=25%> </td><td width=25%> </td><td width=25%> </td></tr></table>";
			            SetValue($TagesProfil_edit_Anzeige_id, $str);
							SetValue($WochenProfil_edit_Anzeige_id,"");
							SetValue($Wochenprofil_speichern_id,1);  // Speicher Button freigeben
   			   		}
						  else
							{
		    				SetValue($WochenProfil_edit_Anzeige_id, HM_WochenTempProfil_html($WochenProfil_Daten_edit_id, GetValue($Profil_Wochentage_id)));
							$tmp=(unserialize(Getvalue($WochenProfil_Daten_edit_id)));
							$day=GetValue($Profil_Wochentage_id);
							SetValue($TagesProfil_edit_Anzeige_id, HM_TagesTempProfil_html($tmp[$dayArray[$day]], GetValue($Profil_Slot_Auswahl_id)));
							SetValue($Wochenprofil_speichern_id,1);  // Speicher Button freigeben							}
							}
						}
					}
			break;

			Case $Praesenz_Profil_Auswahl_id:
				SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
			break;



		break;
		}
	}

if ($_IPS['SENDER']=='Variable')
	{

	}

//****************************************************************************************************

//echo $_IPS['SENDER'];
//echo $_IPS['VARIABLE'];

//print_r($Tages_Profil);




?>