<?

	class LJQuick extends IPSModule
	{
		
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			//Connect to available splitter or create a new one
			$this->ConnectParent("{1C902193-B044-43B8-9433-419F09C641B8}");
			
		}
		
		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* LJ_GenerateSwitch($id, $Start, $End);
		*
		*/
		public function GenerateSwitch($Start, $End)
		{
			
			$qid = @IPS_GetObjectIDByIdent("KNXQuick", 0);
			if($qid === false) {
				$qid = IPS_CreateCategory();
				IPS_SetName($qid, "KNX quick");
				IPS_SetIdent($qid, "KNXQuick");
			}
			
			$sid = @IPS_GetObjectIDByIdent("Switch", $qid);
			if($sid === false) {
				$sid = IPS_CreateCategory();
				IPS_SetName($sid, "Switch");
				IPS_SetIdent($sid, "Switch");
				IPS_SetParent($sid, $qid);
				IPS_SetPosition($sid, 1);
			}
			

			for($i=$Start; $i<=$End; $i++) {
				for($j=0; $j<=9; $j++) {
					$iid = @IPS_GetObjectIDByIdent("Switch".strtoupper(dechex($i).$j), $sid);
					if($iid === false) {
						$iid = IPS_CreateInstance("{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}");
						IPS_SetName($iid, "Switch (Group ".strtoupper(dechex($i)).", Channel ".$j.")");
						IPS_SetIdent($iid, "Switch".strtoupper(dechex($i)).$j);
						IPS_SetParent($iid, $sid);
						IPS_SetProperty($iid, "GroupFunction", "Switch");
						IPS_SetProperty($iid, "GroupInterpretation", "Standard");
						IPS_SetProperty($iid, "GroupAddress1", 15);
						IPS_SetProperty($iid, "GroupAddress2", 0);
						IPS_SetProperty($iid, "GroupAddress3", ($i*16)+$j);
						if($j > 0) {
							$mapping = Array();
							$mapping[] = Array(
								"GroupAddress1" => 15,
								"GroupAddress2" => 0,
								"GroupAddress3" => $i*16
							);
							$mapping[] = Array(
								"GroupAddress1" => 15,
								"GroupAddress2" => 1,
								"GroupAddress3" => ($i*16)+$j
							);
							$mapping[] = Array(
								"GroupAddress1" => 15,
								"GroupAddress2" => 0,
								"GroupAddress3" => 240
							);
							$mapping[] = Array(
								"GroupAddress1" => 15,
								"GroupAddress2" => 0,
								"GroupAddress3" => 240+$j
							);
							IPS_SetProperty($iid, "GroupMapping", json_encode($mapping));
						}
						IPS_ApplyChanges($iid);
					}
					//No subchannels for broadcast
					if($i == 0)
						break;
				}
			}
			
			echo "Done.";
			
		}
		
		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* LJ_GenerateDim($id, $Start, $End);
		*
		*/
		public function GenerateDim($Start, $End)
		{
			
			$qid = @IPS_GetObjectIDByIdent("KNXQuick", 0);
			if($qid === false) {
				$qid = IPS_CreateCategory();
				IPS_SetName($qid, "KNX quick");
				IPS_SetIdent($qid, "KNXQuick");
			}
			
			$sid = @IPS_GetObjectIDByIdent("Dim", $qid);
			if($sid === false) {
				$sid = IPS_CreateCategory();
				IPS_SetName($sid, "Dim");
				IPS_SetIdent($sid, "Dim");
				IPS_SetParent($sid, $qid);
				IPS_SetPosition($sid, 2);
			}
			
			for($i=$Start; $i<=$End; $i++) {
				for($j=0; $j<=9; $j++) {
					$iid = @IPS_GetObjectIDByIdent("Dim".strtoupper(dechex($i).$j), $sid);
					if($iid === false) {
						$iid = IPS_CreateInstance("{D62B95D3-0C5E-406E-B1D9-8D102E50F64B}");
						IPS_SetName($iid, "Dim (Group ".strtoupper(dechex($i)).", Channel ".$j.")");
						IPS_SetIdent($iid, "Dim".strtoupper(dechex($i)).$j);
						IPS_SetParent($iid, $sid);
						IPS_SetProperty($iid, "GroupFunction", "DimValue");
						IPS_SetProperty($iid, "GroupInterpretation", "Percent");
						IPS_SetProperty($iid, "GroupAddress1", 15);
						IPS_SetProperty($iid, "GroupAddress2", 4);
						IPS_SetProperty($iid, "GroupAddress3", ($i*16)+$j);
						if($j > 0) {
							$mapping = Array();
							$mapping[] = Array(
								"GroupAddress1" => 15,
								"GroupAddress2" => 4,
								"GroupAddress3" => $i*16
							);
							$mapping[] = Array(
								"GroupAddress1" => 15,
								"GroupAddress2" => 6,
								"GroupAddress3" => ($i*16)+$j
							);
							$mapping[] = Array(
								"GroupAddress1" => 15,
								"GroupAddress2" => 4,
								"GroupAddress3" => 240
							);
							$mapping[] = Array(
								"GroupAddress1" => 15,
								"GroupAddress2" => 4,
								"GroupAddress3" => 240+$j
							);
							IPS_SetProperty($iid, "GroupMapping", json_encode($mapping));
						}
						IPS_ApplyChanges($iid);
					}
					//No subchannels for broadcast
					if($i == 0)
						break;
				}
			}
			
			echo "Done.";
			
		}

		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* LJ_GenerateShutter($id, $Start, $End);
		*
		*/
		public function GenerateShutter($Start, $End)
		{
			
			echo "Not available yet.";
			
		}		

	}

?>
