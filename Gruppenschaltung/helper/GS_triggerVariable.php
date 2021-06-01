<?php

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2020, 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Fernbedienung/tree/main/Fernbedienung
 */

/** @noinspection PhpUnused */

declare(strict_types=1);

trait GS_triggerVariable
{
    public function CheckTriggerVariable(int $VariableID, bool $ValueChanged): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        $triggerVariables = json_decode($this->ReadPropertyString('TriggerVariables'), true);
        if (!empty($triggerVariables)) {
            // Check if variable is listed
            $keys = array_keys(array_column($triggerVariables, 'ID'), $VariableID);
            foreach ($keys as $key) {
                if (!$triggerVariables[$key]['Use']) {
                    continue;
                }
                $triggered = false;
                $type = IPS_GetVariable($VariableID)['VariableType'];
                $triggerValue = $triggerVariables[$key]['TriggerValue'];
                switch ($triggerVariables[$key]['TriggerType']) {
                    case 0: # on change (bool, integer, float, string)
                        if ($ValueChanged) {
                            $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Änderung (bool, integer, float, string)', 0);
                            $triggered = true;
                        }
                        break;

                    case 1: # on update (bool, integer, float, string)
                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Aktualisierung (bool, integer, float, string)', 0);
                        $triggered = true;
                        break;

                    case 2: # on limit drop, once (integer, float)
                        switch ($type) {
                            case 1: # integer
                                if ($ValueChanged) {
                                    if ($triggerValue == 'false') {
                                        $triggerValue = '0';
                                    }
                                    if ($triggerValue == 'true') {
                                        $triggerValue = '1';
                                    }
                                    if (GetValueInteger($VariableID) < intval($triggerValue)) {
                                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Grenzunterschreitung, einmalig (integer)', 0);
                                        $triggered = true;
                                    }
                                }
                                break;

                            case 2: # float
                                if ($ValueChanged) {
                                    if ($triggerValue == 'false') {
                                        $triggerValue = '0';
                                    }
                                    if ($triggerValue == 'true') {
                                        $triggerValue = '1';
                                    }
                                    if (GetValueFloat($VariableID) < floatval(str_replace(',', '.', $triggerValue))) {
                                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Grenzunterschreitung, einmalig (float)', 0);
                                        $triggered = true;
                                    }
                                }
                                break;

                        }
                        break;

                    case 3: # on limit drop, every time (integer, float)
                        switch ($type) {
                            case 1: # integer
                                if ($triggerValue == 'false') {
                                    $triggerValue = '0';
                                }
                                if ($triggerValue == 'true') {
                                    $triggerValue = '1';
                                }
                                if (GetValueInteger($VariableID) < intval($triggerValue)) {
                                    $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Grenzunterschreitung, mehrmalig (integer)', 0);
                                    $triggered = true;
                                }
                                break;

                            case 2: # float
                                if ($triggerValue == 'false') {
                                    $triggerValue = '0';
                                }
                                if ($triggerValue == 'true') {
                                    $triggerValue = '1';
                                }
                                if (GetValueFloat($VariableID) < floatval(str_replace(',', '.', $triggerValue))) {
                                    $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Grenzunterschreitung, mehrmalig (float)', 0);
                                    $triggered = true;
                                }
                                break;

                        }
                        break;

                    case 4: # on limit exceed, once (integer, float)
                        switch ($type) {
                            case 1: # integer
                                if ($ValueChanged) {
                                    if ($triggerValue == 'false') {
                                        $triggerValue = '0';
                                    }
                                    if ($triggerValue == 'true') {
                                        $triggerValue = '1';
                                    }
                                    if (GetValueInteger($VariableID) > intval($triggerValue)) {
                                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Grenzunterschreitung, einmalig (integer)', 0);
                                        $triggered = true;
                                    }
                                }
                                break;

                            case 2: # float
                                if ($ValueChanged) {
                                    if ($triggerValue == 'false') {
                                        $triggerValue = '0';
                                    }
                                    if ($triggerValue == 'true') {
                                        $triggerValue = '1';
                                    }
                                    if (GetValueFloat($VariableID) > floatval(str_replace(',', '.', $triggerValue))) {
                                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Grenzunterschreitung, einmalig (float)', 0);
                                        $triggered = true;
                                    }
                                }
                                break;

                        }
                        break;

                    case 5: # on limit exceed, every time (integer, float)
                        switch ($type) {
                            case 1: # integer
                                if ($triggerValue == 'false') {
                                    $triggerValue = '0';
                                }
                                if ($triggerValue == 'true') {
                                    $triggerValue = '1';
                                }
                                if (GetValueInteger($VariableID) > intval($triggerValue)) {
                                    $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Grenzunterschreitung, mehrmalig (integer)', 0);
                                    $triggered = true;
                                }
                                break;

                            case 2: # float
                                if ($triggerValue == 'false') {
                                    $triggerValue = '0';
                                }
                                if ($triggerValue == 'true') {
                                    $triggerValue = '1';
                                }
                                if (GetValueFloat($VariableID) > floatval(str_replace(',', '.', $triggerValue))) {
                                    $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei Grenzunterschreitung, mehrmalig (float)', 0);
                                    $triggered = true;
                                }
                                break;

                        }
                        break;

                    case 6: # on specific value, once (bool, integer, float, string)
                        switch ($type) {
                            case 0: #bool
                                if ($ValueChanged) {
                                    if ($triggerValue == 'false') {
                                        $triggerValue = '0';
                                    }
                                    if (GetValueBoolean($VariableID) == boolval($triggerValue)) {
                                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei bestimmten Wert, einmalig (bool)', 0);
                                        $triggered = true;
                                    }
                                }
                                break;

                            case 1: # integer
                                if ($ValueChanged) {
                                    if ($triggerValue == 'false') {
                                        $triggerValue = '0';
                                    }
                                    if ($triggerValue == 'true') {
                                        $triggerValue = '1';
                                    }
                                    if (GetValueInteger($VariableID) == intval($triggerValue)) {
                                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei bestimmten Wert, einmalig (integer)', 0);
                                        $triggered = true;
                                    }
                                }
                                break;

                            case 2: # float
                                if ($ValueChanged) {
                                    if ($triggerValue == 'false') {
                                        $triggerValue = '0';
                                    }
                                    if ($triggerValue == 'true') {
                                        $triggerValue = '1';
                                    }
                                    if (GetValueFloat($VariableID) == floatval(str_replace(',', '.', $triggerValue))) {
                                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei bestimmten Wert, einmalig (float)', 0);
                                        $triggered = true;
                                    }
                                }
                                break;

                            case 3: # string
                                if ($ValueChanged) {
                                    if (GetValueString($VariableID) == (string) $triggerValue) {
                                        $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei bestimmten Wert, einmalig (string)', 0);
                                        $triggered = true;
                                    }
                                }
                                break;

                        }
                        break;

                    case 7: # on specific value, every time (bool, integer, float, string)
                        switch ($type) {
                            case 0: # bool
                                if ($triggerValue == 'false') {
                                    $triggerValue = '0';
                                }
                                if (GetValueBoolean($VariableID) == boolval($triggerValue)) {
                                    $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei bestimmten Wert, mehrmalig (bool)', 0);
                                    $triggered = true;
                                }
                                break;

                            case 1: # integer
                                if ($triggerValue == 'false') {
                                    $triggerValue = '0';
                                }
                                if ($triggerValue == 'true') {
                                    $triggerValue = '1';
                                }
                                if (GetValueInteger($VariableID) == intval($triggerValue)) {
                                    $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei bestimmten Wert, mehrmalig (integer)', 0);
                                    $triggered = true;
                                }
                                break;

                            case 2: # float
                                if ($triggerValue == 'false') {
                                    $triggerValue = '0';
                                }
                                if ($triggerValue == 'true') {
                                    $triggerValue = '1';
                                }
                                if (GetValueFloat($VariableID) == floatval(str_replace(',', '.', $triggerValue))) {
                                    $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei bestimmten Wert, mehrmalig (float)', 0);
                                    $triggered = true;
                                }
                                break;

                            case 3: # string
                                if (GetValueString($VariableID) == (string) $triggerValue) {
                                    $this->SendDebug(__FUNCTION__, 'Auslösebedingung: Bei bestimmten Wert, mehrmalig (string)', 0);
                                    $triggered = true;
                                }
                                break;

                        }
                        break;
                }
                if ($triggered) {
                    switch ($triggerVariables[$key]['TriggerAction']) {
                        case 0: # toggle group off
                            $this->SendDebug(__FUNCTION__, 'Sender ID ' . $triggerVariables[$key]['ID'] . ', Aktion: Gruppe ausschalten wird ausgeführt.', 0);
                            $this->ToggleGroup(false);
                            break;

                        case 1: # toggle group on
                            $this->SendDebug(__FUNCTION__, 'Sender ID ' . $triggerVariables[$key]['ID'] . ', Aktion: Gruppe einschalten wird ausgeführt.', 0);
                            $this->ToggleGroup(true);
                            break;

                        case 2: # toggle group
                            $this->SendDebug(__FUNCTION__, 'Sender ID ' . $triggerVariables[$key]['ID'] . ', Aktion: Gruppe umschalten wird ausgeführt.', 0);
                            $this->ToggleGroup(!$this->GetValue('GroupSwitch'));
                            break;

                    }
                }
            }
        }
    }
}