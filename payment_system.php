
<html>
	<head>
		<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.12.0.min.js"></script>
		<p>Rent! Rent Now! A room with us gets a room for you, because you exchanged money via our payment system for the service we provide, which, you guessed it, is a room.</p>
	</head>
	<body>
		<form method="POST" action="payment_system.php">
			Location & Room:
			<!-- <select required name="locroom"> -->
				<?php
		  			require_once 'util.php';
		  			$util2 = new Util;
					$debug = True;
					if ($debug) {
					}
		  			$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
					if ($db_conn) {
						$result = $util2->executePlainSQL("SELECT 'Room #: '||ROOM_NUMBER||' at location: '||LOCATION_ADDRESS CONCATENATION FROM rooms order by location_address, room_number");
						echo "<select name=\"loc_room\" required>";
						$util2->printResultDropdown($result, 'CONCATENATION');
						OCILogoff($db_conn);
						echo "</select><br>";
					}
				?>
			</select>
			Start Date : <input type="date" name="startDate" required><br>
      End Date : <input type="date" name="endDate" required><br>
			Customer Name: <input type="text" name="custName" required> <br>
			Customer Address: <input type="text" name="custAddr" required> <br>
      <input type="submit" value="Check Price" name="checkCost"></br>

			Payment type: 	<select id="paymentType" name="paymentType" required>
						<option value="Cash">Cash</option>
						<option value="Mastercard">Mastercard</option>
						<option value="Visa">Visa</option>
					</select> <br>
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
				echo "Successfully connected to Oracle. <br>";
			}

			if (array_key_exists('saveRes', $_POST)) {

				$_POST['room'] = trim(substr($_POST['loc_room'], 7, 3));
				$_POST['loc'] = trim(substr($_POST['loc_room'], 23));

				$amount = calculatePayment($_POST, $db_conn);

				// verify no overlapping reservations
				$statement = 'SELECT * FROM reserves WHERE (location_address = :bind1 and room_number = :bind2) and ((start_date between :bind3 and :bind4) or (end_date between :bind3 and :bind4) or (:bind3 between start_date and end_date) or (:bind4 between start_date and end_date))';
        $stid = oci_parse($db_conn, $statement);
        $bind1 = $_POST['loc'];
        $bind2 = $_POST['room'];
				$bind3 = $_POST['startDate'];
        $bind4 = $_POST['endDate'];
        OCIBindByName($stid, ':bind1', $bind1);
        OCIBindByName($stid, ':bind2', $bind2);
				OCIBindByName($stid, ':bind3', $bind3);
        OCIBindByName($stid, ':bind4', $bind4);
        OCIExecute($stid);

				// check if room booked
				if (oci_fetch_array($stid, OCI_BOTH) != false) {
					echo '<h2>Room already booked! Try another date.</h2>';
					echo '<br>';
				}
				else
				{ // otherwise proceed
						//cash payment
						if ($_POST['paymentType'] == "Cash") {
							addCashPayment($amount, $util, $db_conn, $debug);
						}
						else //card payment
						{
							addCardPayment($amount, $_POST['cardNo'], $util, $db_conn, $debug);
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
							insertIntoReserves($_POST, $util, $db_conn);
						}
						else // otherwise, add customer to customer db first, then insert all values into reserves
						{
							insertIntoCustomers($_POST, $util, $db_conn);
							if ($debug) { echo "Customer added<br>"; }
							insertIntoReserves($_POST, $util, $db_conn);
						}

						echo "<h2>Reservation added!</h2>";
				}
			} else if (array_key_exists('checkCost', $_POST)) {
				calculatePayment($_POST, $db_conn);
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
		else
		{
			$err = OCIError();
			echo "Oracle Connect Error" . $err['message'];
		}

		function addCashPayment($amount, $util, $db_conn, $debug) {
			if ($debug) {
				echo "Cash payment selected <br>";
			}

			addPayment($amount, $util, $db_conn, $debug);

			$tuple = array (
				":bind1" => null
			);
			$allTuple = array (
				$tuple
			);

			$util->executeBoundSQL("insert into cash_payment values (:bind1)", $allTuple);
			OCICommit($db_conn);
		}

		function addCardPayment($amount, $card, $util, $db_conn, $debug) {
			if ($debug) {
				echo "Card payment selected <br>";
			}

			addPayment($amount, $util, $db_conn, $debug);

			$tuple = array (
				":bind1" => null,
				":bind2" => $card
			);
			$allTuple = array (
				$tuple
			);

			$util->executeBoundSQL("insert into card_payment values (:bind1, :bind2)", $allTuple);
			OCICommit($db_conn);
		}

		function addPayment($amount, $util, $db_conn, $debug) {
			$tuple = array (
				":bind1" => null,
				":bind2" => $amount
			);
			$allTuple = array (
				$tuple
			);

			$util->executeBoundSQL("insert into payment values (:bind1, :bind2)", $allTuple);
			OCICommit($db_conn);

			if ($debug) {
				echo "Payment added <br>";
			}
		}

		function insertIntoReserves($array, $util, $db_conn) {
			$tuple = array (
					":bind1" => $array['custName'],
					":bind2" => $array['custAddr'],
					":bind3" => $array['loc'],
					":bind4" => $array['room'],
					":bind5" => null,
					":bind6" => $array['startDate'],
					":bind7" => $array['endDate']
				);
			$allTuple = array (
				$tuple
			);

			$util->executeBoundSQL("insert into reserves values (:bind1, :bind2, :bind3, :bind4, :bind5, :bind6, :bind7)", $allTuple);
			OCICommit($db_conn);
		}

		function insertIntoCustomers($array, $util, $db_conn) {
			$tuple = array (
				":bind1" => $array['custName'],
				":bind2" => $array['custAddr'],
				":bind3" => null,
				":bind4" => "JOIefE"
			);
			$allTuple = array (
				$tuple
			);

			$util->executeBoundSQL("insert into customers values (:bind1, :bind2, :bind3, :bind4)", $allTuple);
			OCICommit($db_conn);
		}

		function calculatePayment($array, $db_conn) {
			date_default_timezone_set('UTC');
			$datediff = strtotime($array['endDate']) - strtotime($array['startDate']);
			$datediff = floor($datediff/(60*60*24));
			echo '<h3> Reservation for ';
			echo $datediff;
			echo ' days</h3>';

			$statement = 'SELECT cost_per_day FROM rooms WHERE location_address = :bind1 and room_number = :bind2';
			$stid = oci_parse($db_conn, $statement);
			$bind1 = trim(substr($array['loc_room'], 23));
			$bind2 = trim(substr($array['loc_room'], 7, 3));
			OCIBindByName($stid, ':bind1', $bind1);
			OCIBindByName($stid, ':bind2', $bind2);
			OCIExecute($stid);

			$row = OCI_Fetch_Array($stid, OCI_BOTH);

			echo '<h2> Booking Total: ';
			$amount = $row['COST_PER_DAY'] * $datediff;
			echo $amount;
			echo '</h2>';
			return $amount;
		}
		?>
	</body>
</html>
