
<html>
	<head>

		<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.12.0.min.js"></script>
		<p>Rent! Rent Now! A room with us gets a room for you, because you exchanged money via our payment system for the service we provide, which, you guessed it, is a room.</p>
	</head>
	<body>
		<form method="POST" action="payment_system.php">
			Location:
			<select required name="loc">
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
			<input type="submit" value="View Rooms" name="viewRooms"><br>
		  		<?php 
		  			require_once 'util.php';
		  			$util2 = new Util;
					$debug = True;
					if (!isset($_POST['loc'])) {
						$_POST['loc'] = "111 UBC";
					}

		  			$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
					if ($db_conn) {
						$statement = "SELECT * FROM rooms where location_address = :bind";
	                	$stid = oci_parse($db_conn, $statement);
	                	$bind = $_POST['loc'];
	                	OCIBindByName($stid, ':bind', $bind);
	                	OCIExecute($stid);
						echo "Rooms at location * $bind * : <select required name=\"room\">";
						$util2->printResultDropdown($stid, 'ROOM_NUMBER');
						OCILogoff($db_conn);
						echo "</select><br>";
					}
				?>
			Start Date : <input type="date" name="startDate" required><br>
        	End Date : <input type="date" name="endDate" required><br>
        	<!-- <input type="submit" value="Find Available Rooms" name="findRooms"></p> -->

			Customer Name: <input type="text" name="custName" required> <br>
			Customer Address: <input type="text" name="custAddr" required> <br>
			Payment type: 	<select id="paymentType" name="paymentType" required>
						<option value="Cash">Cash</option>
						<option value="Mastercard">Mastercard</option>
						<option value="Visa">Visa</option>
					</select> <br>
			Amount: <input type="text" name="amount" required> <br>
			Card Number: <input type="text" id="cardNo" name="cardNo" required> <br>
			<input type="submit" value="Save Reservation" name="saveRes"></p>
		</form>

		<script type="text/javascript">
		// Disable card number input if customer selects cash payment
			jQuery(document).ready(function ($) {
		    	$('#cardNo').attr('disabled', 'disabled');
		    	$('select[name="paymentType"]').on('change', function () {
		        	var option = $(this).val();
		        	if (option == "Cash") {
		        		$('#cardNo').attr('disabled', 'disabled');
		        	} else {
		            	$('#cardNo').removeAttr('disabled');
		        	}
		    	})
		    });
		</script>

		<?php 
		error_reporting(-1);
		ini_set('display_errors',1);

		require_once 'util.php';
		$util = new Util;
		$debug = True;

		$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
		if ($db_conn) {
			
			if ($debug) {
				// echo "Successfully connected to Oracle. \n";
			}

			if (array_key_exists('saveRes', $_POST)) {

				//cash payment
				if ($_POST['paymentType'] == "Cash") {
					if ($debug) {
						echo "Cash payment selected <br>";
					}

					$tuple = array (
						":bind1" => null,
						":bind2" => $_POST['amount']
					);
					$allTuple = array (
						$tuple
					);

					$util->executeBoundSQL("insert into payment values (:bind1, :bind2)", $allTuple);
					OCICommit($db_conn);

					$tuple = array (
						":bind1" => null
					);
					$allTuple = array (
						$tuple
					);

					$util->executeBoundSQL("insert into cash_payment values (:bind1)", $allTuple);
					OCICommit($db_conn);
				} 
				else //card payment
				{
					if ($debug) {
						echo "Card payment selected <br>";
					}

					$tuple = array (
						":bind1" => null,
						":bind2" => $_POST['amount']
					);
					$allTuple = array (
						$tuple
					);

					$util->executeBoundSQL("insert into payment values (:bind1, :bind2)", $allTuple);
					OCICommit($db_conn);

					$tuple = array (
						":bind1" => null,
						":bind2" => $_POST['cardNo']
					);
					$allTuple = array (
						$tuple
					);

					$util->executeBoundSQL("insert into card_payment values (:bind1, :bind2)", $allTuple);
					OCICommit($db_conn);
				}

				if ($debug) { 
					echo "Payment added<br>";
				}

				// check if customer exists
				$statement = 'SELECT * FROM customers WHERE cname = :bind1 and address = :bind2';
            	$stid = oci_parse($db_conn, $statement);
            	$bind1 = $_POST['custName'];
                $bind2 = $_POST['custAddr'];
            	OCIBindByName($stid, ':bind1', $bind1);
                OCIBindByName($stid, ':bind2', $bind2);
            	OCIExecute($stid);

            	// if so, insert all values into reserves.
            	if (oci_fetch_array($stid, OCI_BOTH) != false) {
					$tuple = array (
							":bind1" => $_POST['custName'],
							":bind2" => $_POST['custAddr'],
							":bind3" => $_POST['loc'],
							":bind4" => $_POST['room'],
							":bind5" => null,
							":bind6" => $_POST['startDate'],
							":bind7" => $_POST['endDate']
						);
					$allTuple = array (
						$tuple
					);

					$util->executeBoundSQL("insert into reserves values (:bind1, :bind2, :bind3, :bind4, :bind5, :bind6, :bind7)", $allTuple);
					OCICommit($db_conn);
				} 
				else // otherwise, add customer to customer db first, then insert all values into reserves
				{
					$tuple = array (
						":bind1" => $_POST['custName'],
						":bind2" => $_POST['custAddr'],
						":bind3" => null
					);
					$allTuple = array (
						$tuple
					);

					$util->executeBoundSQL("insert into customers values (:bind1, :bind2, :bind3)", $allTuple);
					OCICommit($db_conn);

					if ($debug) { 
						echo "Customer added<br>";
					}

					$tuple = array (
						":bind1" => $_POST['custName'],
						":bind2" => $_POST['custAddr'],
						":bind3" => $_POST['loc'],
						":bind4" => $_POST['room'],
						":bind5" => null,
						":bind6" => $_POST['startDate'],
						":bind7" => $_POST['endDate']	
					);
					$allTuple = array (
						$tuple
					);

					$util->executeBoundSQL("insert into reserves values (:bind1, :bind2, :bind3, :bind4, :bind5, :bind6, :bind7)", $allTuple);
					OCICommit($db_conn);
				}

				if ($debug) { 
					echo "Reservation added\n";
				} 
			// view rooms for the selected location
			} else if (array_key_exists('viewRooms', $_POST)) {
			
					$statement = "SELECT * FROM rooms where location_address = :bind";
                	$stid = oci_parse($db_conn, $statement);
                	$bind = $_POST['loc'];
                	OCIBindByName($stid, ':bind', $bind);
                	OCIExecute($stid);
                	$util->printResultTable($stid , ["ROOM_NUMBER", "LOCATION_ADDRESS", "TYPE", "MAX_OCCUPANCY"]);
                	OCICommit($db_conn);
                }

			if ($debug) {
				$result = $util->executePlainSQL("select * from customers");
				$util->printResultTable($result, ["CNAME", "ADDRESS", "CID"]);

				$result = $util->executePlainSQL("select * from payment");
				$util->printResultTable($result, ["TRANSACTION_ID", "AMOUNT"]);

				$result = $util->executePlainSQL("select * from cash_payment");
				$util->printResultTable($result, ["TRANSACTION_ID"]);

				$result = $util->executePlainSQL("select * from card_payment");
				$util->printResultTable($result, ["TRANSACTION_ID", "CARD_NUMBER"]);

				$result = $util->executePlainSQL("select * from reserves");
				$util->printResultTable($result, ["NAME", "ADDRESS", "LOCATION_ADDRESS", "ROOM_NUMBER", "TRANSACTION_ID", "START_DATE", "END_DATE"]);
			}

			OCILogoff($db_conn);
		}
		else {
			$err = OCIError();
			echo "Oracle Connect Error" . $err['message'];
		}

		?>
	</body>
</html>