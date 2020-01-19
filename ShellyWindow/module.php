<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ShellyHelper.php';

class ShellyWindow extends IPSModule
{
    use Shelly;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        $this->RegisterPropertyString('MQTTTopic', '');
        $this->RegisterPropertyString('Device', '');
        $this->RegisterVariableBoolean('Shelly_Window', $this->Translate('State'), '~Window');
        $this->RegisterVariableInteger('Shelly_Lux', $this->Translate('Lux'), '~Illumination');
        $this->RegisterVariableInteger('Shelly_Battery', $this->Translate('Battery'), '');
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
                if (fnmatch('*/state', $Buffer->Topic)) {
                    $this->SendDebug('State Topic', $Buffer->Topic, 0);
                    $this->SendDebug('State Payload', $Buffer->Payload, 0);
                    switch ($Buffer->Payload) {
                        case 'close':
                            SetValue($this->GetIDForIdent('Shelly_State'), true);
                            break;
                        case 'open':
                            SetValue($this->GetIDForIdent('Shelly_State'), false);
                            break;
                        default:
                            $this->SendDebug('Invalid Payload for State', $Buffer->Payload, 0);
                            break;
                        }
                }
                if (fnmatch('*/lux', $Buffer->Topic)) {
                    $this->SendDebug('Lux Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Lux Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Lux'), boolval($Buffer->Payload));
                }
                if (fnmatch('*/battery', $Buffer->Topic)) {
                    $this->SendDebug('Battery Topic', $Buffer->Topic, 0);
                    $this->SendDebug('Battery Payload', $Buffer->Payload, 0);
                    SetValue($this->GetIDForIdent('Shelly_Battery'), $Buffer->Payload);
                }
            }
        }
    }
}
