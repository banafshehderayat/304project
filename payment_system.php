
<html>
	<head>
		<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.12.0.min.js"></script>
		<p>Rent! Rent Now! A room with us gets a room for you, because you exchanged money via our payment system for the service we provide, which, you guessed it, is a room.</p>
	</head>
	<body>
		<form method="POST" action="payment_system.php">
			Location & Room:
				<?php
		  			require_once 'util.php';
		  			$util2 = new Util;
					$debug = False;
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
			Start Date : <input type="date" name="startDate" required><br>
      		End Date : <input type="date" name="endDate" required><br>
			Customer Name: <input type="text" name="custName" required> <br>
			Customer Address: <input type="text" name="custAddr" required> <br>
      		
      		<input type="submit" value="Check Price" name="checkCost">
      		<input type="submit" value="Show cheapest room!" name="lowco"></p>

			Payment type: 	
				<select id="paymentType" name="paymentType" required>
					<option value="Cash">Cash</option>
					<option value="Mastercard">Mastercard</option>
					<option value="Visa">Visa</option>
				</select> <br>
			Card Number: <input type="text" id="cardNo" name="cardNo" required> <br>
			<input type="submit" value="Save Reservation" name="saveRes">
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

        $db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
		require_once 'util.php';
		$util = new Util;
		$debug = False;

		if ($db_conn) {

			if ($debug) {
				echo "Successfully connected to Oracle. <br>";
			}

			if (array_key_exists('saveRes', $_POST)) {

				$_POST['room'] = trim(substr($_POST['loc_room'], 7, 3));
				$_POST['loc'] = trim(substr($_POST['loc_room'], 23));

				// verify no overlapping reservations
                $stid = findExistingReservation($_POST, $db_conn);

				// check if room booked
				if (oci_fetch_array($stid, OCI_BOTH) != false) {
					echo '<script type="text/javascript" >alert("This room is already booked! Try another date."); </script>';
				}
				else // otherwise proceed
				{ 
					// check if customer exists
					$statement = 'SELECT * FROM customers WHERE cname = :bind1 and address = :bind2';
			        $stid = oci_parse($db_conn, $statement);
			        $bind1 = $_POST['custName'];
			        $bind2 = $_POST['custAddr'];
			        OCIBindByName($stid, ':bind1', $bind1);
			        OCIBindByName($stid, ':bind2', $bind2);
			        OCIExecute($stid, OCI_DEFAULT);

			        // if so, insert all values into reserves.
			        $amount = calculatePayment($_POST, $db_conn);
			        if (oci_fetch_array($stid, OCI_BOTH) != false) {
						//cash payment
						if ($_POST['paymentType'] == "Cash") {
							addCashPayment($amount, $util, $db_conn, $debug);
						}
						else //card payment
						{
							addCardPayment($amount, $_POST['cardNo'], $util, $db_conn, $debug);
						}
						
						insertIntoReserves($_POST, $util, $db_conn);
						echo '<script type="text/javascript" >alert("Reservation added!"); </script>';
                        
                        //Print Reservation Id
                        $stid = findExistingReservation($_POST, $db_conn);
                        $row = oci_fetch_array($stid, OCI_BOTH);
                         
                        if ($row != False) {
    						echo "<h2>Reservation added! Your Reservation ID is: ";
                            echo $row['RESERVATION_ID'];
                            echo ". Make sure you write it down!</h2>";
                        }
					}
					else // otherwise, advise user to register
					{
						echo '<h2 style="color:#ff0000">Either something is wrong with the customer info you entered, or you have not registered!</h2><h2>Please click the login link in the nav to register if so.</h2>';
					}
				}
			} else if (array_key_exists('checkCost', $_POST)) {
				calculatePayment($_POST, $db_conn);
			} else if (array_key_exists('lowco', $_POST)) {
				getTheCheapSeats($db_conn);		
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
                    ":bind1" => null,
					":bind2" => $array['custName'],
					":bind3" => $array['custAddr'],
					":bind4" => $array['loc'],
					":bind5" => $array['room'],
					":bind6" => null,
					":bind7" => $array['startDate'],
					":bind8" => $array['endDate']
				);
			$allTuple = array (
				$tuple
			);

			$util->executeBoundSQL("insert into reserves values (:bind1, :bind2, :bind3, :bind4, :bind5, :bind6, :bind7, :bind8)", $allTuple);
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

		function getTheCheapSeats($db_conn) {
			$statement = 'SELECT * FROM rooms r where r.cost_per_day in (SELECT MIN(cost_per_day) FROM rooms) and ROWNUM=1';
			$stid = oci_parse($db_conn, $statement);
			OCIExecute($stid);

			$row = OCI_Fetch_Array($stid, OCI_BOTH);

			echo '<script type="text/javascript" >alert(
				"The cheapest available room is room number ' . 
				$row['ROOM_NUMBER'] . ' at location ' . 
				$row['LOCATION_ADDRESS'] . ' for $' . 
				$row['COST_PER_DAY'] . ' per day"); </script>';
		}
        
        function findExistingReservation($array, $db_conn) {
            $statement = 'SELECT * FROM reserves WHERE (location_address = :bind1 and room_number = :bind2) and ((start_date between :bind3 and :bind4) or (end_date between :bind3 and :bind4) or (:bind3 between start_date and end_date) or (:bind4 between start_date and end_date))';
            $stid = oci_parse($db_conn, $statement);
            $bind1 = $array['loc'];
            $bind2 = $array['room'];
            $bind3 = $array['startDate'];
            $bind4 = $array['endDate'];
            OCIBindByName($stid, ':bind1', $bind1);
            OCIBindByName($stid, ':bind2', $bind2);
            OCIBindByName($stid, ':bind3', $bind3);
            OCIBindByName($stid, ':bind4', $bind4);
            OCIExecute($stid);
            
            return $stid;
		}
		?>
	</body>
</html>
