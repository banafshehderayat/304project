
<html>
<form method="POST" action="main.php">
	Customer Name: <input type="text" name="custName"> <br>
	Customer Address: <input type="text" name="custAddr"> <br>
	<input type="submit" value="Add Customer" name="addCust"></p>
</form>


<?php 
error_reporting(-1);
ini_set('display_errors',1);

require 'util.php';
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
		$util->printResult($result);
	}

	OCILogoff($db_conn);
}
else {
	$err = OCIError();
	echo "Oracle Connect Error" . $err['message'];
}

?>
</html>