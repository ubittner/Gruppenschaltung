<?php

/*
 * @module      Gruppenschaltung
 *
 * @prefix      GS
 *
 * @file        GS_controlGroup.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2020
 * @license    	CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @see         https://github.com/ubittner/Gruppenschaltung
 *
 */

declare(strict_types=1);

trait GS_controlGroup
{
    /**
     * Toggles the group off or on.
     *
     * @param bool $State
     * false    = off
     * true     = on
     *
     * @return bool
     * false    = an error occurred
     * true     = ok
     *
     * @throws Exception
     */
    public function ToggleGroup(bool $State): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt (' . microtime(true) . ')', 0);
        $vars = json_decode($this->ReadPropertyString('Variables'), true);
        if (empty($vars)) {
            return false;
        }
        if ($State) {
            if ($this->CheckMaintenanceMode()) {
                return false;
            }
        }
        $result = true;
        $this->SetValue('GroupSwitch', $State);
        foreach ($vars as $var) {
            if ($var['Use']) {
                $id = $var['ID'];
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $this->WriteAttributeBoolean('DisableUpdateMode', true);
                    IPS_Sleep($var['SwitchingDelay']);
                    $response = @RequestAction($id, $State);
                    if (!$response) {
                        //Retry
                        IPS_Sleep(self::DELAY_MILLISECONDS);
                        $response = @RequestAction($id, $State);
                        if (!$response) {
                            //Last retry
                            if (!$State) {
                                IPS_Sleep(self::DELAY_MILLISECONDS);
                                $response = @RequestAction($id, $State);
                            }
                        }
                    }
                    if (!$response) {
                        $result = false;
                    }
                }
            }
        }
        $this->WriteAttributeBoolean('DisableUpdateMode', false);
        $this->UpdateGroup();
        return $result;
    }

    /**
     * Updates the water sensor state.
     *
     * @return bool
     * false    = an error occurred
     * true     = ok
     *
     * @throws Exception
     */
    public function UpdateGroup(): bool
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt (' . microtime(true) . ')', 0);
        if ($this->CheckMaintenanceMode()) {
            return false;
        }
        $vars = json_decode($this->ReadPropertyString('Variables'), true);
        if (empty($vars)) {
            return false;
        }
        $result = false;
        $state = false;
        foreach ($vars as $var) {
            if ($var['Use']) {
                $id = $var['ID'];
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $result = true;
                    if (GetValueBoolean($var['ID'])) {
                        $state = true;
                    }
                }
            }
        }
        $this->SetValue('GroupSwitch', $state);
        return $result;
    }
}