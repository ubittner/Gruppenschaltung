<?php

/** @noinspection PhpUnused */

/*
 * @module      Gruppenschaltung (20201120-0601)
 *
 * @prefix      GS
 *
 * @file        module.php
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

include_once __DIR__ . '/helper/autoload.php';

class Gruppenschaltung extends IPSModule
{
    //Helper
    use GS_backupRestore;
    use GS_controlGroup;

    //Constants
    private const DELAY_MILLISECONDS = 250;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterProperties();
        $this->RegisterVariables();
        $this->RegisterAttributeBoolean('DisableUpdateMode', false);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        //Never delete this line!
        parent::ApplyChanges();
        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->RegisterMessages();
        $this->WriteAttributeBoolean('DisableUpdateMode', false);
        $this->UpdateGroup();
        $this->ValidateConfiguration();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'Message from SenderID ' . $SenderID . ' with Message ' . $Message . "\r\n Data: " . print_r($Data, true), 0);
        $this->SendDebug(__FUNCTION__, 'Microtime:' . microtime(true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:
                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp value changed
                //$Data[5] = timestamp last value
                if ($this->CheckMaintenanceMode()) {
                    return;
                }
                $variables = json_decode($this->ReadPropertyString('Variables'), true);
                if (!empty($variables)) {
                    if (array_search($SenderID, array_column($variables, 'ID')) !== false) {
                        $scriptText = 'GS_UpdateGroup(' . $this->InstanceID . ');';
                        @IPS_RunScriptText($scriptText);
                    }
                }
                break;

        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $result = true;
        //Variables
        $vars = json_decode($this->ReadPropertyString('Variables'));
        if (!empty($vars)) {
            foreach ($vars as $var) {
                $rowColor = '';
                $id = $var->ID;
                if ($id == 0 || !@IPS_ObjectExists($id)) {
                    if ($var->Use) {
                        $rowColor = '#FFC0C0'; # red
                        $result = false;
                    }
                }
                $formData['elements'][1]['items'][0]['values'][] = [
                    'Use'           => $var->Use,
                    'ID'            => $id,
                    'Description'   => $var->Description,
                    'rowColor'      => $rowColor];
            }
        }
        //Registered messages
        $messages = $this->GetMessageList();
        foreach ($messages as $senderID => $messageID) {
            $senderName = 'Objekt #' . $senderID . ' existiert nicht';
            $rowColor = '#FFC0C0'; # red
            if (@IPS_ObjectExists($senderID)) {
                $senderName = IPS_GetName($senderID);
                $rowColor = '';
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $formData['actions'][1]['items'][0]['values'][] = [
                'SenderID'                                              => $senderID,
                'SenderName'                                            => $senderName,
                'MessageID'                                             => $messageID,
                'MessageDescription'                                    => $messageDescription,
                'rowColor'                                              => $rowColor];
        }
        $status = $this->GetStatus();
        if (!$result && $status == 102) {
            $status = 201;
        }
        $this->SetStatus($status);
        return json_encode($formData);
    }

    public function ReloadConfiguration()
    {
        $this->ReloadForm();
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'GroupSwitch':
                $this->ToggleGroup($Value);
                break;

        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function RegisterProperties(): void
    {
        //Maintenance
        $this->RegisterPropertyBoolean('MaintenanceMode', false);
        //Group
        $this->RegisterPropertyString('Variables', '[]');
    }

    private function RegisterVariables(): void
    {
        //Group switch
        $this->RegisterVariableBoolean('GroupSwitch', 'Gruppenschaltung', '~Switch', 10);
        $this->EnableAction('GroupSwitch');
    }

    private function RegisterMessages(): void
    {
        //Unregister
        $registeredMessages = $this->GetMessageList();
        if (!empty($registeredMessages)) {
            foreach ($registeredMessages as $id => $registeredMessage) {
                foreach ($registeredMessage as $messageType) {
                    if ($messageType == VM_UPDATE) {
                        $this->UnregisterMessage($id, VM_UPDATE);
                    }
                }
            }
        }
        //Register
        $variables = json_decode($this->ReadPropertyString('Variables'));
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                if ($variable->Use) {
                    if ($variable->ID != 0 && IPS_ObjectExists($variable->ID)) {
                        $this->RegisterMessage($variable->ID, VM_UPDATE);
                    }
                }
            }
        }
    }

    private function ValidateConfiguration(): bool
    {
        $result = true;
        $status = 102;
        //Maintenance mode
        $maintenance = $this->CheckMaintenanceMode();
        if ($maintenance) {
            $result = false;
            $status = 104;
        }
        IPS_SetDisabled($this->InstanceID, $maintenance);
        $this->SetStatus($status);
        return $result;
    }

    private function CheckMaintenanceMode(): bool
    {
        $result = $this->ReadPropertyBoolean('MaintenanceMode');
        if ($result) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wartungsmodus ist aktiv!', 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', Abbruch, der Wartungsmodus ist aktiv!', KL_WARNING);
        }
        return $result;
    }
}