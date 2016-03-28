<html>
	<form method="POST" action="updateRooms.php">
		<input type="submit" value="back" name="back">
        <input type="submit" value="View Rooms" name="viewRooms"></p>
	</form>

	<?php
		error_reporting(-1);
		ini_set('display_errors',1);

		require_once 'util.php';
		$util = new Util;
		$debug = True;

		$db_conn = OCILogon("ora_b9y8", "a38319125", "ug");
		if ($db_conn) {
        		if ($debug) {
               		echo "Successfully connected to Oracle. \n";
        		}
        		
        		if (array_key_exists('back', $_POST)){
				header('Location: http://www.ugrad.cs.ubc.ca/~k2c9/employee.php');			
			} else 
				if (array_key_exists('viewRooms', $_POST)){
					$statement = "SELECT * FROM rooms";
                			$stid = oci_parse($db_conn, $statement);
                			OCIExecute($stid);
					echo '<form name="form1" method="post" action="">';
					echo "<table>";
        				echo "<tr><th>room_number</th><th>location_address</th><th>type</th><th>max_occupancy</th></tr>";
					$i = 0;
        				while ($row = OCI_Fetch_Array($stid)) {
            				echo "<tr><td><input type='text' name='num[]' value='" . $row["ROOM_NUMBER"] . "' readonly></td><td><input type='text' name='loc[]' value='" . $row["LOCATION_ADDRESS"] . "' readonly></td><td><input type='text'name='type[]' value='" .$row["TYPE"]."'></td><td><input type='text' name='max[]' value='" . $row["MAX_OCCUPANCY"]."'></td></tr>"; 
					$i = $i + 1;
        				}
        				echo "</table>";
					echo "<br>";
					echo "<input type='submit' value='update' name='update'>";
					echo "</form>";
                			OCICommit($db_conn);
				} else  
					if (array_key_exists('update', $_POST)){
						foreach($_POST['num'] as $value){
							$index = $value - 1;
							$statement = "UPDATE rooms SET TYPE = :bind1 , MAX_OCCUPANCY = :bind2 
							WHERE ROOM_NUMBER = :bind3 and LOCATION_ADDRESS = :bind4";
							$stid = oci_parse($db_conn, $statement);
							$bind1 = $_POST['type'][$index];
                					$bind2 = $_POST['max'][$index];
							$bind3 = $_POST['num'][$index];
							$bind4 = $_POST['loc'][$index];
                					OCIBindByName($stid, ':bind1', $bind1);
                					OCIBindByName($stid, ':bind2', $bind2);
							OCIBindByName($stid, ':bind3', $bind3);
							OCIBindByName($stid, ':bind4', $bind4);
               						OCIExecute($stid);
							//$util->printResultRooms($stid);
                					//OCICommit($db_conn);
						}				
					}


        		OCILogoff($db_conn);
		}else {
        		$err = OCIError();
        		echo "Oracle Connect Error" . $err['message'];
		}		

	?>
</html>
