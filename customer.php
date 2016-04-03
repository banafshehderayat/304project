<?php
error_reporting(-1);
ini_set('display_errors',1);

class MyAccount {
	
	/**
	 * @var type of user
	 */
	 private $user_type = "";

	 /**
	 * @var user id
	 */
	 private $id = "";

	 /**
	  * @var database connection
	  */
	 private $db_connection = null;


	 public function __construct() {
	 	$this->runApplication();
	 }

	 public function runApplication() {
	 	if ($this->initialize()) {
	 		$this->myAccount();
	 	}
	 }

	 /**
	  * Initializes user_type, id, db_connection
	  * @return true if all members are initialized correctly.
	  */
	 private function initialize() {
	 	date_default_timezone_set('America/Los_Angeles');

	 	if(session_status() == PHP_SESSION_NONE) {
            session_save_path("php_sessions");
            session_start();
        }

        // User is not logged in; redirect to login page
	 	if (empty($_SESSION['user_is_logged_in']) || !($_SESSION['user_is_logged_in'])) {
	 		$this->notLoggedIn();
	 		return false;
	 	}

	 	$this->user_type = $_SESSION['user_type'];
	 	$this->id = $_SESSION['id'];

	 	$this->createDBConnection();
	 	return !empty($this->user_type) &&
	 		   !empty($this->id) &&
	 		   !empty($this->db_connection);
	 }

	 /**
     * Establishes database connection using OCI methods.
     * @return bool Database creation success status, false by default
     */
	 private function createDBConnection() {
	 	$this->db_connection = OCILogon("ora_j7l8", "a31501125", "ug");
        if ($this->db_connection) {
            return true;
        }
        else {
            $this->feedback = OCIError();
            echo "Oracle Connect Error" . $err['message'];
            return false;
        }
	 }

	 /**
	  * Handles the actions of the myAccount page
	  */
	 private function myAccount() {
	 	if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();
        }
        elseif (isset($_POST["edit"]) && $_POST["edit"] == "Edit") {
        	$this->doEditReservation();
        }
        elseif (isset($_POST["edit"]) && $_POST["edit"] == "Update") { 
        	$this->doUpdateReservation();
        	$this->doEditReservation();
        }
        elseif (isset($_POST["delete"]) && $_POST["delete"] == "Delete") { 
        	$this->doDeleteReservation();
        	$this->showAccountSummary();
        }
        else {
        	$this->showAccountSummary();
        }
	 }

	 /**
      * Logs the user out. Destroys session.
      */
     private function doLogout() {
        $_SESSION = array();
        session_destroy();
     }

     /**
      * Edit reservation
      */
     private function doEditReservation() {
     	if (!isset($_POST['edit'])) {
     		echo "There's nothing here! <br>";
     		echo '<br><br><a href="' . $_SERVER['SCRIPT_NAME'] . '">Back to Account Summary</a>';
     		return;
     	}

     	$start_date = htmlentities($_POST['start'], ENT_QUOTES);
     	$end_date = htmlentities($_POST['end'], ENT_QUOTES);
     	$location = htmlentities($_POST['location'], ENT_QUOTES);
     	$room_number = htmlentities($_POST['room'], ENT_QUOTES);
        $reservation = htmlentities($_POST['reservation_id'], ENT_QUOTES);

     	$sql = 'SELECT type, max_occupancy
     			FROM rooms
     			WHERE room_number = :rn AND location_address = :loc';

     	$statement = oci_parse($this->db_connection, $sql);
	 	ocibindbyname($statement, ':rn', $room_number);
	 	ocibindbyname($statement, ':loc', $location);

	 	OCIExecute($statement, OCI_DEFAULT);
        OCICommit($this->db_connection);

        $row = OCI_Fetch_Array($statement);

     	echo '<form method="POST" action="' . $_SERVER['SCRIPT_NAME'] . '?action=edit">';
     	echo "<table><tr>
     		  <th>Location Address</th>
     		  <th>Room Number</th>
     		  <th>Room Type</th>
     		  <th>Max Occupancy</th>
     		  <th>Reservation Start Date<br>(DD-MM-YY)</th>
     		  <th>Reservation End Date<br>(DD-MM-YY)</th></tr>";

     	echo "<tr>
     		  <td><input type='text' name='location' value='" . $location . "' readonly></td>
     		  <td><input type='text' name='room' value='" . $room_number . "' readonly></td>
     		  <td><input type='text' name='room_type' value='" . $row['TYPE'] . "' readonly></td>
     		  <td><input type='text' name='max_occupancy' value='" . $row['MAX_OCCUPANCY'] . "' readonly></td>
     		  <td><input type='text' name='start' value='" . $start_date ."' pattern='[0-9]{2}\-[0-9]{2}\-[0-9]{2}' required></td>
     		  <td><input type='text' name='end' value='" . $end_date ."' pattern='[0-9]{2}\-[0-9]{2}\-[0-9]{2}' required></td>
     		  <input type='hidden' name='start_old' value='" . $start_date . "'/>
     		  <input type='hidden' name='end_old' value='" . $end_date ."'/>
     		  <input type='hidden' name='transaction' value='" . $_POST['transaction'] ."'/>
              <input type='hidden' value='" . $reservation . "' name='reservation_id' />
     		  </tr></table>"; 

     	echo "<input type='submit' value='Update' name='edit'>";
        echo "<input type='submit' value='Delete' name='delete'></form>";
        


     	echo '<br><br><a href="' . $_SERVER['SCRIPT_NAME'] . '">Back to Account Summary</a>';
     }

     /**
	  * Use connection with database to update values of db.
	  */
     private function doUpdateReservation() {
     	$location = htmlentities($_POST['location'], ENT_QUOTES);
     	$room_number = htmlentities($_POST['room'], ENT_QUOTES);

     	$start_date = htmlentities($_POST['start'], ENT_QUOTES);
     	$end_date = htmlentities($_POST['end'], ENT_QUOTES);

     	if ($this->checkNewReserationDates($location, $room_number, $start_date, $end_date)) {
     		$start_old = htmlentities($_POST['start_old'], ENT_QUOTES);
     		$end_old = htmlentities($_POST['end_old'], ENT_QUOTES);

     		$sql = 'SELECT cname, address
     		FROM customers
     		where cid = :id';

     		$statement = oci_parse($this->db_connection, $sql);
     		ocibindbyname($statement, ':id', $this->id);

     		OCIExecute($statement, OCI_DEFAULT);
     		OCICommit($this->db_connection);

     		$row = OCI_Fetch_Array($statement);
     		$cname = $row['CNAME'];
     		$caddr = $row['ADDRESS'];

     		$update = "UPDATE reserves 
     		SET START_DATE = :startd , END_DATE = :finish
     		WHERE location_address = :loc AND 
     		room_number = :rn AND
     		name = :name AND
     		address = :addr AND
     		start_date = :ostart AND
     		end_date = :oend";

     		$upStmt = oci_parse($this->db_connection, $update);
     		ocibindbyname($upStmt, ':startd', $start_date);
     		ocibindbyname($upStmt, ':finish', $end_date);
     		ocibindbyname($upStmt, ':loc', $location);
     		ocibindbyname($upStmt, ':rn', $room_number);
     		ocibindbyname($upStmt, ':name', $cname);
     		ocibindbyname($upStmt, ':addr', $caddr);
     		ocibindbyname($upStmt, ':ostart', $start_old);
     		ocibindbyname($upStmt, ':oend', $end_old);

     		OCIExecute($upStmt, OCI_DEFAULT);
     		OCICommit($this->db_connection);
     	}
     }

     private function doDeleteReservation() {
       $transaction_id = htmlentities($_POST['transaction'], ENT_QUOTES);
       // Cascade delete reservation associated with transaction_id
       $delete = "DELETE FROM PAYMENT 
       WHERE transaction_id = :transaction_id";

       $upStmt = oci_parse($this->db_connection, $delete);
       ocibindbyname($upStmt, ':transaction_id', $transaction_id);

       OCIExecute($upStmt, OCI_DEFAULT);
       OCICommit($this->db_connection);
     }

     /**
      * @return true if there are no overlapping reservations for the
      * 			 given room and dates
      */
     private function checkNewReserationDates($loc, $room, $start, $end) {
     	$sql = 'SELECT * 
     			FROM reserves 
     			WHERE transaction_id <> :bind0 and
     				  (location_address = :bind1 and room_number = :bind2) and 
     				  ((start_date between :bind3 and :bind4) or 
     				  (end_date between :bind3 and :bind4) or 
     				  (:bind3 between start_date and end_date) or 
     				  (:bind4 between start_date and end_date))';
		
		$statement = oci_parse($this->db_connection, $sql);
		OCIBindByName($statement, ':bind0', $_POST['transaction']);
		OCIBindByName($statement, ':bind1', $loc);
		OCIBindByName($statement, ':bind2', $room);
		OCIBindByName($statement, ':bind3', $start);
		OCIBindByName($statement, ':bind4', $end);

		OCIExecute($statement, OCI_DEFAULT);
		OCICommit($this->db_connection);

		$row = OCI_Fetch_Array($statement, OCI_BOTH);
		if ($row != false) {
			$message = "Room is already booked from " . $row['START_DATE'] . " to " . $row['END_DATE'] .
					   ". Please select another date.";
			echo "<script type='text/javascript'>alert('$message');</script>";
			return false;
		}

		return true;
     }

	 /**
	  * TODO: split into "upcoming" and reservation history
	  */
	 private function showAccountSummary() {
	 	// Get customer's reservations
	 	$reservations = $this->getReservationDetails();

	 	// Display resrevations
	 	$this->printReservations($reservations);

	 	echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout">Log out</a>';
	 }


	 /**
	  * Prints reservation table with an extra 'edit' column at the end.
	  */
	 function printReservations($result) {
	 	$columns = ['Room Number', 'Location', 'Start Date<br> DD-MM-YY', 'End Date<br>DD-MM-YY', 'Amount Paid', 'TID'];

	 	echo '<table><tr>';

	 	foreach ($columns as &$col) {
	 		echo '<th>' . $col . '</th>';
	 	}
	 	echo '</tr>';


	 	while ($row = OCI_Fetch_Array($result)) {
	 		echo "<tr>";
	 		echo '<td>' . $row['ROOM_NUMBER'] . '</td>';
	 		echo '<td>' . $row['LOCATION'] . '</td>';
	 		echo '<td>' . $row['START_DATE'] . '</td>';
	 		echo '<td>' . $row['END_DATE'] . '</td>';
	 		echo '<td>' . $row['AMOUNT'] . '</td>';
            echo '<td>' . $row['TRANSACTION_ID'] . '</td>';

	 		echo '<td><form method="POST" action="' . $_SERVER['SCRIPT_NAME'] . '?action=edit">
	 		<input type="hidden" name="location" value="' . $row['LOCATION']. '"/>
            <input type="hidden" name="reservation_id" value="' . $row['RESERVATION_ID'] .'"/>
	 		<input type="hidden" name="room" value="' . $row['ROOM_NUMBER'] .'"/>
	 		<input type="hidden" name="start" value="' . $row['START_DATE'] .'"/>
	 		<input type="hidden" name="end" value="' . $row['END_DATE'] .'"/>
	 		<input type="hidden" name="transaction" value="' . $row['TRANSACTION_ID'] .'"/>
	 		<input type="submit" name="edit" value="Edit" />
	 		</form></td>';
	 		echo "</tr>";
	 	}

	 	echo "</table>";
	 }

	 /**
	  * @return result $statement
	  */
	 private function getReservationDetails() {
	 	$sql = 'SELECT reserves.room_number, reserves.location_address AS LOCATION, 
	 				   reserves.start_date, reserves.end_date, payment.amount, customers.cid,
	 				   reserves.transaction_id,
                       reserves.reservation_id
	 			FROM customers, reserves, payment
	 			WHERE customers.cname = reserves.name AND 
	 				  customers.address = reserves.address AND
	 				  reserves.transaction_id = payment.transaction_id AND
	 				  customers.cid = :bind';


	 	$statement = oci_parse($this->db_connection, $sql);
	 	$bind = $this->id;
	 	OCIBindByName($statement, ':bind', $bind);

	 	OCIExecute($statement);
        OCICommit($this->db_connection);

        return $statement;
	 }


	 private function notLoggedIn() {
	 	echo "<meta http-equiv=\"refresh\" content=\"0; URL='login.php'\" />";
	 }

}

$page = new MyAccount();
?>