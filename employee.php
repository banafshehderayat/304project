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
  					$debug = True;
  					if ($debug) {
  					}
  		  		$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
  					if ($db_conn) {
  						$result = $util2->executePlainSQL("select * from location");
  						$util2->printResultDropdown($result, 'LOCATION_ADDRESS');
  						OCILogoff($db_conn);
  					}
				?>
			</select>
	<br>
        <input type="submit" value="View Rooms" name="viewRooms"></p>


	


</form>


<?php
error_reporting(-1);
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
                          	$util->printResultTable($stid , ["ROOM_NUMBER", "LOCATION_ADDRESS", "TYPE", "MAX_OCCUPANCY", "COST_PER_DAY"]);
                          	OCICommit($db_conn);
        } 
	echo '<a href="updateRooms.php"> Update Rooms</a>';


	if ($_SESSION['user_type'] == 'MANAGER'){
		echo '<br>';
		echo '<a href="manager.php">go back!</a>';	
	}


        OCILogoff($db_conn);


	
      } else {
        $err = OCIError();
        echo "Oracle Connect Error" . $err['message'];
}
?>
</html>
