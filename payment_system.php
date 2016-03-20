
<html>

<p>Rent! Rent Now! A room with us gets a room for you, because you exchanged money via our payment system for the service we provide, which, you guessed it, is a room.</p>

<form method="POST" action="payment_system.php">
	Customer Name: <input type="text" name="custName"> <br>
	Customer Address: <input type="text" name="custAddr"> <br>
	Location:
	<select name="loc">
  		<?php 
  			require_once 'util.php';
  			$util2 = new Util;
			$debug = True;
			if ($debug) {
				echo "Successfully connected to Oracle. for select \n";
			}

  			$db_conn = OCILogon("ora_b9y8", "a38319125", "ug");
			if ($db_conn) {

				$result = $util2->executePlainSQL("select * from location");
				$util2->printResultDropdown($result);
				OCILogoff($db_conn);
			}
		?>
	</select>

	<br>
	Room: <input type="text" name="custName"> <br>
	<input type="submit" value="Add Customer" name="addCust"></p>
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

	if (array_key_exists('addCust', $_POST)) {
		$tuple = array (
				":bind1" => $_POST['custName'],
				":bind2" => $_POST['custAddr']
			);
		$allTuple = array (
			$tuple
		);

		$util->executeBoundSQL("insert into customers values (:bind1, :bind2)", $allTuple);
		OCICommit($db_conn);

		if ($debug) { 
			echo "Customer added\n";
		}
	}

	if ($debug) {
		$result = $util->executePlainSQL("select * from customers");
		$util->printResultTable($result);
	}

	OCILogoff($db_conn);
}
else {
	$err = OCIError();
	echo "Oracle Connect Error" . $err['message'];
}

?>
</html>