<html>
<form method="POST" action="employee.php">
        Customer Name: <input type="text" name="custName"> <br>
        Customer Address: <input type="text" name="custAddr"> <br>
        <input type="submit" value="Find Customer" name="findCust"></p>
        <br>
        <br>
	Location:
	<select name="loc">
  		<?php 
  			require_once 'util.php';
  			$util2 = new Util;
  			$db_conn = OCILogon("ora_b9y8", "a38319125", "ug");
			if ($db_conn) {
				$result = $util2->executePlainSQL("select * from location");
				$util2->printResultDropdown($result);
				OCILogoff($db_conn);
			}
		?>
	</select>
	<br>
        <input type="submit" value="View Rooms" name="viewRooms"></p>

        Start Date : <input type="date" name="startDate"><br>
        End Date : <input type="date" name="endDate"><br>
        <input type="submit" value="Find Available Rooms" name="findRooms"></p>
	
	<input type="submit" value="Update Rooms" name="updateRooms"></p>
	

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

        if (array_key_exists('findCust', $_POST)) {
                $statement = 'SELECT * FROM customers WHERE cname =:bind1 and address = :bind2';
                $stid = oci_parse($db_conn, $statement);
                $bind1 = $_POST['custName'];
                $bind2 = $_POST['custAddr'];
                OCIBindByName($stid, ':bind1', $bind1);
                OCIBindByName($stid, ':bind2', $bind2);
               	OCIExecute($stid);
                $util->printResultTable($stid, ["CNAME", "ADDRESS", "CID"]);
                OCICommit($db_conn);
                if ($debug) {
                        echo "Customer found\n";
                }
        } else
		if (array_key_exists('viewRooms', $_POST)) {
			
			$statement = "SELECT * FROM rooms where location_address = :bind";
                	$stid = oci_parse($db_conn, $statement);
                	$bind = $_POST['loc'];
                	OCIBindByName($stid, ':bind', $bind);
                	OCIExecute($stid);
			echo($stid);
                	$util->printResultTable($stid , ["ROOM_NUMBER", "LOCATION_ADDRESS", "TYPE", "MAX_OCCUPANCY"]);
                	OCICommit($db_conn);
                	if ($debug) {
                        	$result = $util->executePlainSQL("select * from rooms");
				$util->printResultTable($result, ["ROOM_NUMBER", "LOCATION_ADDRESS", "TYPE", "MAX_OCCUPANCY"]);
                	}
	
		} else 
			if (array_key_exists('updateRooms', $_POST)){
				header('Location: http://www.ugrad.cs.ubc.ca/~k2c9/updateRooms.php');			
			}



        OCILogoff($db_conn);
}else {
        $err = OCIError();
        echo "Oracle Connect Error" . $err['message'];
}

?>
</html>

