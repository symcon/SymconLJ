<?php

declare(strict_types=1);

class LJQuick extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterTimer('DateTimeTimer', 10 * 60 * 1000, 'LJ_SendDateTime($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Set receive filter to something that will never match
        $this->SetReceiveDataFilter('(?!)');

        //Connect to available splitter or create a new one
        $this->ConnectParent('{1C902193-B044-43B8-9433-419F09C641B8}');

        //We need our own profiles
        if (!IPS_VariableProfileExists('Electricity.Wh')) {
            IPS_CreateVariableProfile('Electricity.Wh', 1);
            IPS_SetVariableProfileValues('Electricity.Wh', 0, 0, 0);
            IPS_SetVariableProfileText('Electricity.Wh', '', ' Wh');
        }

        if (!IPS_VariableProfileExists('Electricity.kWh')) {
            IPS_CreateVariableProfile('Electricity.kWh', 1);
            IPS_SetVariableProfileValues('Electricity.kWh', 0, 0, 0);
            IPS_SetVariableProfileText('Electricity.kWh', '', ' kWh');
        }

        if (!IPS_VariableProfileExists('Electricity.MWh')) {
            IPS_CreateVariableProfile('Electricity.MWh', 1);
            IPS_SetVariableProfileValues('Electricity.MWh', 0, 0, 0);
            IPS_SetVariableProfileText('Electricity.MWh', '', ' MWh');
        }

        if (!IPS_VariableProfileExists('Volume.CubicMeter')) {
            IPS_CreateVariableProfile('Volume.CubicMeter', 1);
            IPS_SetVariableProfileValues('Volume.CubicMeter', 0, 0, 0);
            IPS_SetVariableProfileText('Volume.CubicMeter', '', ' m³');
        }

        if (!IPS_VariableProfileExists('Volume.Liter')) {
            IPS_CreateVariableProfile('Volume.Liter', 1);
            IPS_SetVariableProfileValues('Volume.Liter', 0, 0, 0);
            IPS_SetVariableProfileText('Volume.Liter', '', ' l');
        }

        if (!IPS_VariableProfileExists('Power.W')) {
            IPS_CreateVariableProfile('Power.W', 2);
            IPS_SetVariableProfileValues('Power.W', 0, 0, 0);
            IPS_SetVariableProfileDigits('Power.W', 0);
            IPS_SetVariableProfileText('Power.W', '', ' W');
        }

        if (!IPS_VariableProfileExists('Flow.CubicMeterPerHour')) {
            IPS_CreateVariableProfile('Flow.CubicMeterPerHour', 2);
            IPS_SetVariableProfileValues('Flow.CubicMeterPerHour', 0, 0, 0);
            IPS_SetVariableProfileDigits('Flow.CubicMeterPerHour', 3);
            IPS_SetVariableProfileText('Flow.CubicMeterPerHour', '', ' m³/h');
        }
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     *
     * LJ_GenerateSwitch($id, $Group);
     *
     */
    public function GenerateSwitch(int $Group)
    {
        $qid = @IPS_GetObjectIDByIdent('KNXQuick', 0);
        if ($qid === false) {
            $qid = IPS_CreateCategory();
            IPS_SetName($qid, 'KNX quick');
            IPS_SetIdent($qid, 'KNXQuick');
        }

        $sid = @IPS_GetObjectIDByIdent('Switch', $qid);
        if ($sid === false) {
            $sid = IPS_CreateCategory();
            IPS_SetName($sid, 'Switch');
            IPS_SetIdent($sid, 'Switch');
            IPS_SetParent($sid, $qid);
            IPS_SetPosition($sid, 1);
        }

        for ($Channel = 0; $Channel <= 9; $Channel++) {
            $iid = @IPS_GetObjectIDByIdent('Switch' . strtoupper(dechex($Group) . $Channel), $sid);
            if ($iid === false) {
                $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                IPS_SetName($iid, 'Switch (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                IPS_SetIdent($iid, 'Switch' . strtoupper(dechex($Group)) . $Channel);
                IPS_SetParent($iid, $sid);
                IPS_SetPosition($iid, $Channel * 10);
                IPS_SetProperty($iid, 'GroupFunction', 'Switch');
                IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                IPS_SetProperty($iid, 'GroupAddress1', 15);
                IPS_SetProperty($iid, 'GroupAddress2', 0);
                IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                if ($Channel > 0) {
                    $mapping = [];
                    $mapping[] = [
                        'GroupAddress1' => 15,
                        'GroupAddress2' => 0,
                        'GroupAddress3' => $Group * 16
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 15,
                        'GroupAddress2' => 1,
                        'GroupAddress3' => ($Group * 16) + $Channel
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 15,
                        'GroupAddress2' => 0,
                        'GroupAddress3' => 240
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 15,
                        'GroupAddress2' => 0,
                        'GroupAddress3' => 240 + $Channel
                    ];
                    IPS_SetProperty($iid, 'GroupMapping', json_encode($mapping));
                }
                IPS_ApplyChanges($iid);

                //Rename Status Variable
                $vid = IPS_GetObjectIDByIdent('Value', $iid);
                IPS_SetName($vid, 'Switch (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
            }
        }

        echo 'Done.';
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     *
     * LJ_GenerateDim($id, $Group);
     *
     */
    public function GenerateDim(int $Group)
    {
        $qid = @IPS_GetObjectIDByIdent('KNXQuick', 0);
        if ($qid === false) {
            $qid = IPS_CreateCategory();
            IPS_SetName($qid, 'KNX quick');
            IPS_SetIdent($qid, 'KNXQuick');
        }

        $sid = @IPS_GetObjectIDByIdent('Dim', $qid);
        if ($sid === false) {
            $sid = IPS_CreateCategory();
            IPS_SetName($sid, 'Dim');
            IPS_SetIdent($sid, 'Dim');
            IPS_SetParent($sid, $qid);
            IPS_SetPosition($sid, 2);
        }

        for ($Channel = 0; $Channel <= 9; $Channel++) {
            $iid = @IPS_GetObjectIDByIdent('Dim' . strtoupper(dechex($Group) . $Channel), $sid);
            if ($iid === false) {
                $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                IPS_SetName($iid, 'Dim (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                IPS_SetIdent($iid, 'Dim' . strtoupper(dechex($Group)) . $Channel);
                IPS_SetParent($iid, $sid);
                IPS_SetPosition($iid, $Channel * 10);
                IPS_SetProperty($iid, 'GroupFunction', 'DimValue');
                IPS_SetProperty($iid, 'GroupInterpretation', 'Percent');
                IPS_SetProperty($iid, 'GroupAddress1', 15);
                IPS_SetProperty($iid, 'GroupAddress2', 4);
                IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                if ($Channel > 0) {
                    $mapping = [];
                    $mapping[] = [
                        'GroupAddress1' => 15,
                        'GroupAddress2' => 4,
                        'GroupAddress3' => $Group * 16
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 15,
                        'GroupAddress2' => 6,
                        'GroupAddress3' => ($Group * 16) + $Channel
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 15,
                        'GroupAddress2' => 4,
                        'GroupAddress3' => 240
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 15,
                        'GroupAddress2' => 4,
                        'GroupAddress3' => 240 + $Channel
                    ];
                    IPS_SetProperty($iid, 'GroupMapping', json_encode($mapping));
                }
                IPS_ApplyChanges($iid);

                //Rename Status Variable
                $vid = IPS_GetObjectIDByIdent('Value', $iid);
                IPS_SetName($vid, 'Dim (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
            }
        }

        echo 'Done.';
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     *
     * LJ_GenerateShutter($id, $Group);
     *
     */
    public function GenerateShutter(int $Group)
    {
        $qid = @IPS_GetObjectIDByIdent('KNXQuick', 0);
        if ($qid === false) {
            $qid = IPS_CreateCategory();
            IPS_SetName($qid, 'KNX quick');
            IPS_SetIdent($qid, 'KNXQuick');
        }

        $sid = @IPS_GetObjectIDByIdent('Shutter', $qid);
        if ($sid === false) {
            $sid = IPS_CreateCategory();
            IPS_SetName($sid, 'Shutter');
            IPS_SetIdent($sid, 'Shutter');
            IPS_SetParent($sid, $qid);
            IPS_SetPosition($sid, 2);
        }

        for ($Channel = 0; $Channel <= 9; $Channel++) {
            $iid = @IPS_GetObjectIDByIdent('Shutter' . strtoupper(dechex($Group) . $Channel), $sid);
            if ($iid === false) {
                $iid = IPS_CreateInstance('{24A9D68D-7B98-4D74-9BAE-3645D435A9EF}');
                IPS_SetName($iid, 'Shutter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                IPS_SetIdent($iid, 'Shutter' . strtoupper(dechex($Group)) . $Channel);
                IPS_SetParent($iid, $sid);
                IPS_SetPosition($iid, $Channel * 10);
                IPS_SetProperty($iid, 'GroupMoveAddress1', 14);
                IPS_SetProperty($iid, 'GroupMoveAddress2', 0);
                IPS_SetProperty($iid, 'GroupMoveAddress3', ($Group * 16) + $Channel);
                IPS_SetProperty($iid, 'GroupStopAddress1', 14);
                IPS_SetProperty($iid, 'GroupStopAddress2', 1);
                IPS_SetProperty($iid, 'GroupStopAddress3', ($Group * 16) + $Channel);
                if ($Channel > 0) {
                    $mapping = [];
                    $mapping[] = [
                        'GroupAddress1' => 14,
                        'GroupAddress2' => 0,
                        'GroupAddress3' => $Group * 16
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 14,
                        'GroupAddress2' => 0,
                        'GroupAddress3' => 240
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 14,
                        'GroupAddress2' => 0,
                        'GroupAddress3' => 240 + $Channel
                    ];
                    IPS_SetProperty($iid, 'GroupMoveMapping', json_encode($mapping));
                    $mapping = [];
                    $mapping[] = [
                        'GroupAddress1' => 14,
                        'GroupAddress2' => 1,
                        'GroupAddress3' => $Group * 16
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 14,
                        'GroupAddress2' => 1,
                        'GroupAddress3' => 240
                    ];
                    $mapping[] = [
                        'GroupAddress1' => 14,
                        'GroupAddress2' => 1,
                        'GroupAddress3' => 240 + $Channel
                    ];
                    IPS_SetProperty($iid, 'GroupStopMapping', json_encode($mapping));
                }
                IPS_ApplyChanges($iid);

                //Rename Status Variable
                $vid = IPS_GetObjectIDByIdent('Status', $iid);
                IPS_SetName($vid, 'Status (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');

                //Rename Action Variable
                $vid = IPS_GetObjectIDByIdent('Action', $iid);
                IPS_SetName($vid, 'Action (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
            }
        }

        echo 'Done.';
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     *
     * Type:
     *
     * 	0 = Energy
     * 	1 = Water, Gas, Oil
     *  2 = Heat Quantity
     *
     * LJ_GenerateCounter($id, $Group, $Type);
     *
     */
    public function GenerateCounter(int $Group, int $Type)
    {
        switch ($Type) {
            case 0:
                $TypeName = 'Energy';
                break;
            case 1:
                $TypeName = 'Water, Gas, Oil';
                break;
            case 2:
                $TypeName = 'Heat Quantity';
                break;
            default:
                die('Invalid type!');
        }

        $qid = @IPS_GetObjectIDByIdent('KNXQuick', 0);
        if ($qid === false) {
            $qid = IPS_CreateCategory();
            IPS_SetName($qid, 'KNX quick');
            IPS_SetIdent($qid, 'KNXQuick');
        }

        $sid = @IPS_GetObjectIDByIdent('Counter_' . $Type, $qid);
        if ($sid === false) {
            $sid = IPS_CreateCategory();
            IPS_SetName($sid, 'Counter (' . $TypeName . ')');
            IPS_SetIdent($sid, 'Counter_' . $Type);
            IPS_SetParent($sid, $qid);
            IPS_SetPosition($sid, 1);
        }

        for ($Channel = 1; $Channel <= 9; $Channel++) {

            //S/N
            $iid = @IPS_GetObjectIDByIdent('Counter_SerialNumber_' . strtoupper(dechex($Group) . $Channel), $sid);
            if ($iid === false) {
                $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                IPS_SetIdent($iid, 'Counter_SerialNumber_' . strtoupper(dechex($Group)) . $Channel);
                IPS_SetParent($iid, $sid);
                IPS_SetPosition($iid, $Channel * 20 + 0);
                IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                IPS_SetProperty($iid, 'GroupAddress1', 11);
                IPS_SetProperty($iid, 'GroupAddress2', 6);
                IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                IPS_ApplyChanges($iid);

                $vid = IPS_GetObjectIDByIdent('Value', $iid);
                IPS_SetName($vid, 'Serial Number (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
            }

            //Status
            $iid = @IPS_GetObjectIDByIdent('Counter_Status_' . strtoupper(dechex($Group) . $Channel), $sid);
            if ($iid === false) {
                $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                IPS_SetIdent($iid, 'Counter_Status_' . strtoupper(dechex($Group)) . $Channel);
                IPS_SetParent($iid, $sid);
                IPS_SetPosition($iid, $Channel * 20 + 1);
                IPS_SetProperty($iid, 'GroupFunction', 'Switch');
                IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                IPS_SetProperty($iid, 'GroupAddress1', 11);
                IPS_SetProperty($iid, 'GroupAddress2', 7);
                IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                IPS_ApplyChanges($iid);

                //Set as read-only
                $vid = IPS_GetObjectIDByIdent('Value', $iid);
                IPS_SetName($vid, 'Status (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                IPS_SetVariableCustomAction($vid, 1);
            }

            //Read Meter
            $iid = @IPS_GetObjectIDByIdent('Counter_ReadMeter_' . strtoupper(dechex($Group) . $Channel), $sid);
            if ($iid === false) {
                $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                IPS_SetIdent($iid, 'Counter_ReadMeter_' . strtoupper(dechex($Group)) . $Channel);
                IPS_SetParent($iid, $sid);
                IPS_SetPosition($iid, $Channel * 20 + 2);
                IPS_SetProperty($iid, 'GroupFunction', 'Switch');
                IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                IPS_SetProperty($iid, 'GroupAddress1', 14);
                IPS_SetProperty($iid, 'GroupAddress2', 7);
                IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                IPS_ApplyChanges($iid);

                $vid = IPS_GetObjectIDByIdent('Value', $iid);
                IPS_SetName($vid, 'Read Meter (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
            }

            if ($Type == 0 /* Energy */) {

                //Power Forward (W)
                $iid = @IPS_GetObjectIDByIdent('Counter_PowerForward_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_PowerForward_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 3);
                    IPS_SetProperty($iid, 'GroupFunction', 'FloatValue');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 11);
                    IPS_SetProperty($iid, 'GroupAddress2', 0);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Power.W');
                    IPS_SetName($vid, 'Power Forward (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Power Reverse (W)
                $iid = @IPS_GetObjectIDByIdent('Counter_PowerReverse_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_PowerReverse_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 4);
                    IPS_SetProperty($iid, 'GroupFunction', 'FloatValue');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 11);
                    IPS_SetProperty($iid, 'GroupAddress2', 1);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Power.W');
                    IPS_SetName($vid, 'Power Reverse (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Energy Forward (Wh)
                $iid = @IPS_GetObjectIDByIdent('Counter_EnergyForwardWh_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_EnergyForwardWh_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 5);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 0);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Electricity.Wh');
                    IPS_SetName($vid, 'Energy Forward (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Energy Forward (kWh)
                $iid = @IPS_GetObjectIDByIdent('Counter_EnergyForwardkWh_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_EnergyForwardkWh_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 6);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 1);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Electricity.kWh');
                    IPS_SetName($vid, 'Energy Forward (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Energy Reverse (Wh)
                $iid = @IPS_GetObjectIDByIdent('Counter_EnergyReverseWh_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_EnergyReverseWh_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 7);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 3);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Electricity.Wh');
                    IPS_SetName($vid, 'Energy Reverse (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Energy Reverse (kWh)
                $iid = @IPS_GetObjectIDByIdent('Counter_EnergyReversekWh_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_EnergyReversekWh_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 8);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 4);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Electricity.kWh');
                    IPS_SetName($vid, 'Energy Reverse (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }
            }

            if ($Type == 1 /* Water, Gas, Oil */ || $Type == 2 /* Heat Quantity */) {

                //Volume (l)
                $iid = @IPS_GetObjectIDByIdent('Counter_VolumeLiter_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_VolumeLiter_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 9);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 6);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Volume.Liter');
                    IPS_SetName($vid, 'Volume (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Volume (m^3)
                $iid = @IPS_GetObjectIDByIdent('Counter_VolumeM3_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_VolumeM3_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 10);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 7);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Volume.CubicMeter');
                    IPS_SetName($vid, 'Volume (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }
            }

            if ($Type == 2 /* Heat Quantity */) {

                //Power (W)
                $iid = @IPS_GetObjectIDByIdent('Counter_Power_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_Power_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 11);
                    IPS_SetProperty($iid, 'GroupFunction', 'FloatValue');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 11);
                    IPS_SetProperty($iid, 'GroupAddress2', 0);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Power.W');
                    IPS_SetName($vid, 'Power (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Flow (m^3/h)
                $iid = @IPS_GetObjectIDByIdent('Counter_Flow_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_Flow_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 12);
                    IPS_SetProperty($iid, 'GroupFunction', 'FloatValue');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 11);
                    IPS_SetProperty($iid, 'GroupAddress2', 2);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Flow.CubicMeterPerHour');
                    IPS_SetName($vid, 'Flow (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Temperature Forward (°C)
                $iid = @IPS_GetObjectIDByIdent('Counter_TemperatureForward_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_TemperatureForward_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 13);
                    IPS_SetProperty($iid, 'GroupFunction', 'Value');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 11);
                    IPS_SetProperty($iid, 'GroupAddress2', 4);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, '~Temperature');
                    IPS_SetName($vid, 'Temperature Forward (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Temperature Reverse (°C)
                $iid = @IPS_GetObjectIDByIdent('Counter_TemperatureReverse_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_TemperatureReverse_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 14);
                    IPS_SetProperty($iid, 'GroupFunction', 'Value');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 11);
                    IPS_SetProperty($iid, 'GroupAddress2', 5);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, '~Temperature');
                    IPS_SetName($vid, 'Temperature Reverse (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Energy Heat (kWh)
                $iid = @IPS_GetObjectIDByIdent('Counter_EnergyHeatkWh_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_EnergyHeatkWh_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 15);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 1);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Electricity.kWh');
                    IPS_SetName($vid, 'Energy Heat (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Energy Heat (MWh)
                $iid = @IPS_GetObjectIDByIdent('Counter_EnergyHeatMWh_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_EnergyHeatMWh_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 16);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 2);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Electricity.MWh');
                    IPS_SetName($vid, 'Energy Heat (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Energy Cool (kWh)
                $iid = @IPS_GetObjectIDByIdent('Counter_EnergyCoolkWh_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_EnergyCoolkWh_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 17);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 4);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Electricity.kWh');
                    IPS_SetName($vid, 'Energy Cool (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }

                //Energy Cool (MWh)
                $iid = @IPS_GetObjectIDByIdent('Counter_EnergyCoolMWh_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Counter (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Counter_EnergyCoolMWh_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 20 + 18);
                    IPS_SetProperty($iid, 'GroupFunction', '32bitCounter');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 12);
                    IPS_SetProperty($iid, 'GroupAddress2', 5);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, 'Electricity.MWh');
                    IPS_SetName($vid, 'Energy Cool (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }
            }
        }

        echo 'Done.';
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     *
     * Type:
     *
     * 	0 = Temperature
     * 	1 = Temperature/Humidity
     *
     * LJ_GenerateHeating($id, $Group, $Type);
     *
     */
    public function GenerateHeating(int $Group, int $Type)
    {
        switch ($Type) {
            case 0:
                $TypeName = 'Temperature';
                break;
            case 1:
                $TypeName = 'Temperature/Humidity';
                break;
            default:
                die('Invalid type!');
        }

        $qid = @IPS_GetObjectIDByIdent('KNXQuick', 0);
        if ($qid === false) {
            $qid = IPS_CreateCategory();
            IPS_SetName($qid, 'KNX quick');
            IPS_SetIdent($qid, 'KNXQuick');
        }

        $sid = @IPS_GetObjectIDByIdent('Heating_' . $Type, $qid);
        if ($sid === false) {
            $sid = IPS_CreateCategory();
            IPS_SetName($sid, 'Heating (' . $TypeName . ')');
            IPS_SetIdent($sid, 'Heating_' . $Type);
            IPS_SetParent($sid, $qid);
            IPS_SetPosition($sid, 1);
        }

        for ($Channel = 0; $Channel <= 9; $Channel++) {

            //Status
            $iid = @IPS_GetObjectIDByIdent('Heating_Status_' . strtoupper(dechex($Group) . $Channel), $sid);
            if ($iid === false) {
                $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                IPS_SetName($iid, 'Heating (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                IPS_SetIdent($iid, 'Heating_Status_' . strtoupper(dechex($Group)) . $Channel);
                IPS_SetParent($iid, $sid);
                IPS_SetPosition($iid, $Channel * 10 + 0);
                IPS_SetProperty($iid, 'GroupFunction', 'Switch');
                IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                IPS_SetProperty($iid, 'GroupAddress1', 13);
                IPS_SetProperty($iid, 'GroupAddress2', 7);
                IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                IPS_ApplyChanges($iid);

                //Set as read-only
                $vid = IPS_GetObjectIDByIdent('Value', $iid);
                IPS_SetName($vid, 'Status (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                IPS_SetVariableCustomAction($vid, 1);
            }

            //Temperature
            $iid = @IPS_GetObjectIDByIdent('Heating_Temperature_' . strtoupper(dechex($Group) . $Channel), $sid);
            if ($iid === false) {
                $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                IPS_SetName($iid, 'Heating (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                IPS_SetIdent($iid, 'Heating_Temperature_' . strtoupper(dechex($Group)) . $Channel);
                IPS_SetParent($iid, $sid);
                IPS_SetPosition($iid, $Channel * 10 + 1);
                IPS_SetProperty($iid, 'GroupFunction', 'Value');
                IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                IPS_SetProperty($iid, 'GroupAddress1', 13);
                IPS_SetProperty($iid, 'GroupAddress2', 5);
                IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                IPS_ApplyChanges($iid);

                $vid = IPS_GetObjectIDByIdent('Value', $iid);
                IPS_SetVariableCustomProfile($vid, '~Temperature');
                IPS_SetName($vid, 'Temperature (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
            }

            if ($Type == 1 /* Temperature/Humidity */) {

                //Humidity
                $iid = @IPS_GetObjectIDByIdent('Heating_Humidity_' . strtoupper(dechex($Group) . $Channel), $sid);
                if ($iid === false) {
                    $iid = IPS_CreateInstance('{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}');
                    IPS_SetName($iid, 'Heating (Group ' . strtoupper(dechex($Group)) . ', Channel ' . $Channel . ')');
                    IPS_SetIdent($iid, 'Heating_Humidity_' . strtoupper(dechex($Group)) . $Channel);
                    IPS_SetParent($iid, $sid);
                    IPS_SetPosition($iid, $Channel * 10 + 2);
                    IPS_SetProperty($iid, 'GroupFunction', 'Value');
                    IPS_SetProperty($iid, 'GroupInterpretation', 'Standard');
                    IPS_SetProperty($iid, 'GroupAddress1', 13);
                    IPS_SetProperty($iid, 'GroupAddress2', 6);
                    IPS_SetProperty($iid, 'GroupAddress3', ($Group * 16) + $Channel);
                    IPS_ApplyChanges($iid);

                    $vid = IPS_GetObjectIDByIdent('Value', $iid);
                    IPS_SetVariableCustomProfile($vid, '~Humidity.F');
                    IPS_SetName($vid, 'Humidity (GR ' . strtoupper(dechex($Group)) . ', CH ' . $Channel . ')');
                }
            }
        }

        echo 'Done.';
    }

    public function SendDateTime()
    {

        //Require at least Version 5.1 from 01.05.2019
        if (IPS_GetKernelDate() <= 1556734554) {
            return;
        }

        $data = "\x80" .
            chr(100 + intval(date('y'))) .
            chr(intval(date('m'))) .
            chr(intval(date('d'))) .
            chr((intval(date('N')) << 5) + intval(date('H'))) .
            chr(intval(date('i'))) .
            chr(intval(date('s'))) .
            chr(intval(date('I')) ? 1 : 0) .
            chr(0);

        if (floatval(IPS_GetKernelVersion()) >= 5.4) {
            $json = [
                'DataID'        => '{42DFD4E4-5831-4A27-91B9-6FF1B2960260}',
                'GroupAddress1' => 30,
                'GroupAddress2' => 3,
                'GroupAddress3' => 254,
                'Data'          => utf8_encode($data)
            ];
        } else {
            $header = "\x00\x00\xF3\xFE"; //30/3/254
            $json = [
                'DataID' => '{42DFD4E4-5831-4A27-91B9-6FF1B2960260}',
                'Header' => utf8_encode($header),
                'Data'   => utf8_encode($data)
            ];
        }

        if ($this->HasActiveParent()) {
            $this->SendDataToParent(json_encode($json));
        }
    }
}
