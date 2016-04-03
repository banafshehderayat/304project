<html>
	<form method="POST" action="updateRooms.php">

        <input type="submit" value="View Rooms" name="viewRooms"></p>
	</form>

	<?php
		error_reporting(E_ERROR);
		ini_set('display_errors',1);

		require_once 'util.php';
		$util = new Util;
		$debug = False;
		// User is not logged in; redirect to login page
		session_save_path("php_sessions");
        	session_start();
	 	if (empty($_SESSION['user_is_logged_in']) || !($_SESSION['user_is_logged_in']) || ($_SESSION['user_type'] == 'CUSTOMER')) {
	 		echo "<meta http-equiv=\"refresh\" content=\"0; URL='login.php?action=logout'\" />";
	 		return;
	 	}
		$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
		if ($db_conn) {
        		if ($debug) {
               		echo "Successfully connected to Oracle. \n";
        		}
			

						if (array_key_exists('viewRooms', $_POST)){
							$statement = "SELECT * FROM rooms";
		          $stid = oci_parse($db_conn, $statement);
		          OCIExecute($stid);
							echo '<form name="form1" method="post" action="">';
							echo "<table>";
		        	echo "<tr><th>room_number</th><th>location_address</th><th>type</th><th>max_occupancy</th><th>cost_per_day</th></tr>";
							$i = 0;
		        	while ($row = OCI_Fetch_Array($stid)) {
		            	echo "<tr><td><input type='text' name='num[]' value='" . $row["ROOM_NUMBER"] . "' readonly></td><td><input type='text' name='loc[]' value='" . $row["LOCATION_ADDRESS"] . "' readonly></td><td><input type='text'name='type[]' value='" .$row["TYPE"]."'></td><td><input type='text' name='max[]' value='" . $row["MAX_OCCUPANCY"]."'></td><td><input type='text' name='cost[]' value='" . $row["COST_PER_DAY"]."'></td></tr>";
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
									$statement = "UPDATE rooms SET TYPE = :bind1 , MAX_OCCUPANCY = :bind2 , COST_PER_DAY = :bind3 WHERE ROOM_NUMBER = :bind4 and LOCATION_ADDRESS = :bind5";
									$stid = oci_parse($db_conn, $statement);
									$bind1 = $_POST['type'][$index];
		              						$bind2 = $_POST['max'][$index];
									$bind3 = $_POST['cost'][$index];
									$bind4 = $_POST['num'][$index];
									$bind5 = $_POST['loc'][$index];
		              						OCIBindByName($stid, ':bind1', $bind1);
		              						OCIBindByName($stid, ':bind2', $bind2);
									OCIBindByName($stid, ':bind3', $bind3);
									OCIBindByName($stid, ':bind4', $bind4);
									OCIBindByName($stid, ':bind5', $bind5);
		              						$r = OCIExecute($stid);
									if ($r == FALSE){
									echo '<script type="text/javascript">'. 'alert("The room information you have entered is invalid.")</script>';
									} else{ OCICommit($db_conn);
										}
								}
							}

        		
echo '<a href="employee.php"> go back </a>';
OCILogoff($db_conn);
		} else {
        		$err = OCIError();
        		echo "Oracle Connect Error" . $err['message'];
		}

	?>
</html>
