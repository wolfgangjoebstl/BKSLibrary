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

	/**@defgroup ipslight IPSLight
	 * @ingroup modules
	 * @{
	 *
	 * @file          IPSLight.inc.php
	 * @author        Andreas Brauneis
	 * @version
	 *  Version 2.50.1, 26.07.2012<br/>
	 *
	 * IPSLight API
     *
     * Vom Webfront aufgerufen
     *      IPSHeat_SetValue
     *      IPSHeat_SetGroup
     *      IPSHeat_SetProgram
     *
     * Liste aller Funktionen
     *      IPSHeat_SetValue
     *      IPSHeat_SetSwitch
	 *      IPSHeat_ToggleSwitch
     *      IPSHeat_SetDimmerAbs
     *      IPSHeat_SetDimmerRel
     *      IPSHeat_SetGroup
     *      IPSHeat_ToggleGroup
     *      IPSHeat_SetProgram
     *      IPSHeat_SetProgramNext
     *      IPSHeat_SetSwitchByName
     *      IPSHeat_ToggleSwitchByName
     *
     *
     *
	 */

	IPSUtils_Include ("IPSLogger.inc.php",                  "IPSLibrary::app::core::IPSLogger");
	IPSUtils_Include ("IPSComponent.class.php",             "IPSLibrary::app::core::IPSComponent");
	IPSUtils_Include ("IPSHeat_Constants.inc.php",         "IPSLibrary::app::modules::Stromheizung");
	IPSUtils_Include ("Stromheizung_Configuration.inc.php",     "IPSLibrary::config::modules::Stromheizung");
	//IPSUtils_Include ("IPSHeat_Custom.inc.php",            "IPSLibrary::config::modules::Stromheizung");
	IPSUtils_Include ("IPSHeat_Manager.class.php",         "IPSLibrary::app::modules::Stromheizung");

	/**
	 * Setzt den Wert einer Variable (Schalter, Dimmer, Gruppe, ...) anhand der zugehörigen ID
	 *
	 * @param int $variableId ID der Variable
	 * @param variant $value Neuer Wert der Variable
	 */
	function IPSHeat_SetValue($variableId, $value, $debug=false) 
        {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetValue($variableId, $value, $debug);
	    }

	/**
	 * Setzt den Wert eines Schalters anhand der zugehörigen ID
	 *
	 * @param int $switchId ID der Variable
	 * @param bool $value Neuer Wert der Variable
	 */
	function IPSHeat_SetSwitch($switchId, $value) {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetValue($switchId, $value);
	}

	/**
	 * "Toggle" eines Schalters anhand der zugehörigen ID
	 *
	 * @param int $switchId ID der Variable
	 */
	function IPSHeat_ToggleSwitch($switchId) {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetValue($switchId, !$lightManager->GetValue($switchId));
	}

	/**
	 * Setzt den Wert eines Dimmers anhand der zugehörigen Level ID
	 *
	 * @param int $levelId ID der Variable
	 * @param int $value Neuer Wert der Variable
	 */
	function IPSHeat_SetDimmerAbs($levelId, $value) {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetDimmer($levelId, $value);
	}

	/**
	 * Verändert den Wert eines Dimmers anhand der zugehörigen Level ID um einen bestimmten Delta Wert
	 *
	 * @param int $levelId ID der Variable
	 * @param int $value Delta Wert um den der Dimmer Wert erhöht bzw. erniedrigt werden soll
	 */
	function IPSHeat_SetDimmerRel($levelId, $value) {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetDimmer($levelId, $lightManager->GetValue($levelId) + $value);
	}

	/**
	 * Setzt den Wert eines Gruppen Schalters anhand der zugehörigen ID
	 *
	 * @param int $groupId ID des Gruppen Schalters
	 * @param bool $value Neuer Wert der Gruppe
	 */
	function IPSHeat_SetGroup($groupId, $value, $debug=false, $exectime=false) {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetGroup($groupId, $value, true, true, $debug,$exectime);
	}

	/**
	 * "Toogle" Gruppen Schalter anhand der zugehörigen ID
	 *
	 * @param int $groupId ID des Gruppen Schalters
	 */
	function IPSHeat_ToggleGroup($groupId) {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetGroup($groupId, !$lightManager->GetValue(groupId));
	}

	/**
	 * Setzt den Wert eines Programm Schalters anhand der zugehörigen ID
	 *
	 * @param int $programId ID des Programm Schalters
	 * @param bool $value Neuer Wert des Programm Schalters
	 */
	function IPSHeat_SetProgram($programId, $value) {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetProgram($programId, $value);
	}

	/**
	 * Setzt des nächtsten Programms anhand der zugehörigen ID
	 *
	 * @param int $programId ID des Programm Schalters
	 */
	function IPSHeat_SetProgramNext($programId) {
		$lightManager = new IPSHeat_Manager();
		$lightManager->SetProgram($programId, $lightManager->GetValue($programId) + 1);
	}


	/**
	 * Setzt den Wert eines Schalters anhand des zugehörigen Namens
	 *
	 * @param string $lightName Name des Schalters
	 * @param bool $value Neuer Wert des Schalters
	 */
	function IPSHeat_SetSwitchByName($lightName, $value) {
		$lightManager = new IPSHeat_Manager();
		$switchId = $lightManager->GetSwitchIdByName($lightName);
		$lightManager->SetValue($switchId, $value);
	}

	/**
	 * "Toogle" Schalter anhand des zugehörigen Namens
	 *
	 * @param string $lightName Name des Schalters
	 */
	function IPSHeat_ToggleSwitchByName($lightName) {
		$lightManager = new IPSHeat_Manager();
		$switchId = $lightManager->GetSwitchIdByName($lightName);
		$lightManager->SetValue($switchId, !$lightManager->GetValue($switchId));
	}

	/**
	 * Setzt den Wert eines Dimmers anhand des zugehörigen Namens
	 *
	 * @param string $lightName Name des Dimmers
	 * @param int $value Neuer Wert des Dimmers
	 */
	function IPSHeat_DimAbsoluteByName($lightName, $value) {
		$lightManager = new IPSHeat_Manager();
		$levelId = $lightManager->GetLevelIdByName($lightName);
		$lightManager->SetDimmer($levelId, $value);
	}

	/**
	 * Verändert den Wert eines Dimmers anhand des zugehörigen Namens um einen übergebenen Delta Wert
	 *
	 * @param string $lightName Name des Dimmers
	 * @param int $value Delta Wert (positiv oder negativ)
	 */
	function IPSHeat_DimRelativByName($lightName, $value) {
		$lightManager = new IPSHeat_Manager();
		$levelId = $lightManager->GetLevelIdByName($lightName);
		$lightManager->SetDimmer($levelId, $lightManager->GetValue($levelId) + $value);
	}

	/**
	 * Setzt den Wert einer Gruppe anhand des zugehörigen Namens
	 *
	 * @param string $groupName Name der Gruppe
	 * @param bool $value Neuer Wert der Gruppe
	 */
	function IPSHeat_SetGroupByName($groupName, $value, $debug=false) {
		$lightManager = new IPSHeat_Manager();
        $lightManager->setConfiguration(true);                      // ["donotupdateSwitch"]==false schaltet bei level Änderung auch gleich ein/aus
		$groupId = $lightManager->GetGroupIdByName($groupName);
        if ($debug) echo "IPSHeat_SetGroupByName, Aufruf von SetGroup mit $groupId und $value.\n";
        $syncGroups=true; $syncPrograms=true;
		$lightManager->SetGroup($groupId, $value, $syncGroups, $syncPrograms, $debug);
	}

	/**
	 * "Toogle" Wert einer Gruppe anhand des zugehörigen Namens
	 *
	 * @param string $groupName Name der Gruppe
	 */
	function IPSHeat_ToggleGroupByName($groupName) {
		$lightManager = new IPSHeat_Manager();
		$groupId = $lightManager->GetGroupIdByName($groupName);
		$lightManager->SetGroup($groupId, !$lightManager->GetValue($groupId));
	}

	/**
	 * Setzt den Wert eines Programms anhand des zugehörigen Namens
	 *
	 * @param string $programName Name des Programms
	 * @param bool $value Neuer Wert des Programms
	 */
	function IPSHeat_SetProgramName($programName, $value) {
		$lightManager = new IPSHeat_Manager();
		$programId = $lightManager->GetProgramIdByName($programName);
		$lightManager->SetProgram($programId, $value);
	}

	/**
	 * Setzt das nächste Programm eines Programwahlschalters anhand des zugehörigen Namens
	 *
	 * @param string $programName Name des Programms
	 */
	function IPSHeat_SetProgramNextByName($programName) {
		$lightManager = new IPSHeat_Manager();
		$programId = $lightManager->GetProgramIdByName($programName);
		$lightManager->SetProgram($programId, $lightManager->GetValue($programId) + 1);
	}

    /** @}*/
?>