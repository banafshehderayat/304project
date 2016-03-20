
<html>
<form method="POST" action="employee.php">
	Customer Name: <input type="text" name="custName"> <br>
	Customer Address: <input type="text" name="custAddr"> <br>
	<input type="submit" value="Find Customer" name="findCust"></p>
</form>


<?php 
error_reporting(-1);
ini_set('display_errors',1);

require 'util.php';
$util = new Util;
$debug = False;

$db_conn = OCILogon("ora_b9y8", "a38319125", "ug");
if ($db_conn) {
	
	if ($debug) {
		echo "Successfully connected to Oracle. \n";
	}

	if (array_key_exists('findCust', $_POST)) {
		$tuple = array (
				":bind1" => $_POST['custName'],
				":bind2" => $_POST['custAddr']
			);
		$allTuple = array (
			$tuple
		);
		
		$customers = $util->executeBoundSQL("select * FROM customers
					WHERE CNAME = (:bind1) AND 
					ADDRESS = (:bind2)", $allTuple);
		
		OCICommit($db_conn);
		$util->printResult($customers);


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
