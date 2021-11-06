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

	/**@ingroup IPSHeat
	 * @{
	 *
	 * @file          IPSHeat_ActionScript.inc.php
	 * @author        Andreas Brauneis
	 * @version
	 *  Version 2.50.1, 26.07.2012<br/>
	 *
	 * IPSHeat ActionScript 
	 *
	 */

	//include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
	IPSUtils_Include ('IPSHeat.inc.php', 'IPSLibrary::app::modules::Stromheizung');

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Autosteuerung\Autosteuerung_Configuration.inc.php");
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_AlexaClass.inc.php");

	IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('Autosteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Autosteuerung');
    IPSUtils_Include ('Autosteuerung_Class.inc.php', 'IPSLibrary::app::modules::Autosteuerung');
    IPSUtils_Include ('Autosteuerung_AlexaClass.inc.php', 'IPSLibrary::app::modules::Autosteuerung');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) 
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$configurationAutosteuerung = Autosteuerung_Setup();

$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);

$nachrichten=new AutosteuerungAlexa();
$Alexa = new AutosteuerungAlexaHandler();
$register=new AutosteuerungConfigurationAlexa($scriptIdAutosteuerung);

	/*********************************************************************************************/
	
	if ($_IPS['SENDER']!='Execute') 
		{
		$variableId   = $_IPS['VARIABLE'];
		$value        = $_IPS['VALUE'];
		$categoryName = IPS_GetName(IPS_GetParent($_IPS['VARIABLE']));
		}
		
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($_IPS['SENDER']=='WebFront') 
		{
		switch ($categoryName) 
			{
			case 'Switches':
				IPSHeat_SetValue($variableId, $value);
				break;
			case 'Groups':
				IPSHeat_SetGroup($variableId, $value);
				break;
			case 'Programs':
				IPSHeat_SetProgram($variableId, $value);
				break;
			default:
				trigger_error('Unknown Category '.$categoryName);
			}
		}
	// ----------------------------------------------------------------------------------------------------------------------------
	elseif ($_IPS['SENDER']=='VoiceControl')
		{
 		IPSLogger_Inf(__file__,"Heat_ActionScript empfaengt von Alexa für $categoryName: ".IPS_GetName($variableId)." (".$variableId.")  den Wert $value.");

		switch ($categoryName) 
			{
			case 'Switches':
				$nachrichten->LogNachrichten("Alexa/VoiceControl : Switch ".IPS_GetName($variableId)." (".$variableId.")   ".($value?"Ein":"Aus")."   ".$_IPS['VALUE']." .");
				IPSHeat_SetValue($variableId, $value);
				break;
			case 'Groups':
				$nachrichten->LogNachrichten("Alexa/VoiceControl : Group ".IPS_GetName($variableId)." (".$variableId.")   ".$_IPS['VALUE']." .");
				IPSHeat_SetGroup($variableId, $value);
				break;
			case 'Programs':
				$nachrichten->LogNachrichten("Alexa/VoiceControl : Programs ".IPS_GetName($variableId)." (".$variableId.")   ".$_IPS['VALUE']." .");
				IPSHeat_SetProgram($variableId, $value);
				break;
			default:
				$nachrichten->LogNachrichten("Alexa/VoiceControl : Unknown Category ".IPS_GetName($variableId)." (".$variableId.")   ".$_IPS['VALUE']." .");
				trigger_error('Unknown Category '.$categoryName);
			}
		}
	else
		{
		}
		
		
    /** @}*/
?>