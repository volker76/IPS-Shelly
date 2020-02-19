<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ShellyHelper.php';

class Shelly3EM extends IPSModule
{
    use Shelly;
    use
        ShellyRelayAction;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        $this->RegisterPropertyString('MQTTTopic', '');

        $this->RegisterVariableFloat('Shelly_Power0', $this->Translate('Power') . ' A', '~Watt.3680');
        $this->RegisterVariableFloat('Shelly_PowerFactor0', $this->Translate('Power Factor') . ' A', '~Watt.3680');
        $this->RegisterVariableFloat('Shelly_Current0', $this->Translate('Current') . ' A', '~Ampere');
        $this->RegisterVariableFloat('Shelly_Voltage0', $this->Translate('Voltage') . ' A', '~Volt');

        $this->RegisterVariableFloat('Shelly_Power0', $this->Translate('Power') . ' B', '~Watt.3680');
        $this->RegisterVariableFloat('Shelly_PowerFactor0', $this->Translate('Power Factor') . ' B', '~Watt.3680');
        $this->RegisterVariableFloat('Shelly_Current0', $this->Translate('Current') . ' B', '~Ampere');
        $this->RegisterVariableFloat('Shelly_Voltage0', $this->Translate('Voltage') . ' B', '~Volt');

        $this->RegisterVariableFloat('Shelly_Power2', $this->Translate('Power') . ' C', '~Watt.3680');
        $this->RegisterVariableFloat('Shelly_PowerFactor2', $this->Translate('Power Factor') . ' C', '~Watt.3680');
        $this->RegisterVariableFloat('Shelly_Current2', $this->Translate('Current') . ' C', '~Ampere');
        $this->RegisterVariableFloat('Shelly_Voltage2', $this->Translate('Voltage') . ' C', '~Volt');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        //Setze Filter für ReceiveData
        $MQTTTopic = $this->ReadPropertyString('MQTTTopic');
        $this->SetReceiveDataFilter('.*' . $MQTTTopic . '.*');
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        if (!empty($this->ReadPropertyString('MQTTTopic'))) {
            $data = json_decode($JSONString);
            // Buffer decodieren und in eine Variable schreiben
            $Buffer = $data;
            $this->SendDebug('MQTT Topic', $Buffer->Topic, 0);

            if (property_exists($Buffer, 'Topic')) {
                //Phase A
                if (fnmatch('*emeter/0/power', $Buffer->Topic)) {
                    $this->SendDebug('Power Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Power Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Power0'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/0/pf', $Buffer->Topic)) {
                    $this->SendDebug('Power Factor Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Power Factor Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_PowerFactor0'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/0/current', $Buffer->Topic)) {
                    $this->SendDebug('Current Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Current Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Current0'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/0/voltage', $Buffer->Topic)) {
                    $this->SendDebug('Voltage Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Voltage Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Voltage0'), floatval($Buffer->Payload));
                }

                //Phase B
                if (fnmatch('*emeter/1/power', $Buffer->Topic)) {
                    $this->SendDebug('Power Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Power Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Power1'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/1/pf', $Buffer->Topic)) {
                    $this->SendDebug('Power Factor Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Power Factor Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_PowerFactor1'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/1/current', $Buffer->Topic)) {
                    $this->SendDebug('Current Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Current Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Current1'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/1/voltage', $Buffer->Topic)) {
                    $this->SendDebug('Voltage Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Voltage Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Voltage1'), floatval($Buffer->Payload));
                }

                //Phase C
                if (fnmatch('*emeter/2/power', $Buffer->Topic)) {
                    $this->SendDebug('Power Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Power Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Power2'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/2/pf', $Buffer->Topic)) {
                    $this->SendDebug('Power Factor Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Power Factor Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_PowerFactor2'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/2/current', $Buffer->Topic)) {
                    $this->SendDebug('Current Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Current Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Current2'), floatval($Buffer->Payload));
                }
                if (fnmatch('*emeter/2/voltage', $Buffer->Topic)) {
                    $this->SendDebug('Voltage Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Voltage Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Voltage2'), floatval($Buffer->Payload));
                }
            }
        }
    }
}
