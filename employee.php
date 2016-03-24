<html>
<form method="POST" action="employee.php">
        Customer Name: <input type="text" name="custName"> <br>
        Customer Address: <input type="text" name="custAddr"> <br>
        <input type="submit" value="Find Customer" name="findCust"></p>
        <br>
        <br>

        Room Location: <input type="text" name="roomLoc"> <br>
        <input type="submit" value="View Rooms" name="viewRooms"></p>

        Start Date : <input type="date" name="startDate"><br>
        End Date : <input type="date" name="endDate"><br>
        <input type="submit" value="Find Available Rooms"
name="findRooms"></p>

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

        if (array_key_exists('findCust', $_POST)) {
                $statement = 'SELECT * FROM customers WHERE cname =
:bind1 and address = :bind2';
                $stid = oci_parse($db_conn, $statement);
                $bind1 = $_POST['custName'];
                $bind2 = $_POST['custAddr'];
                OCIBindByName($stid, ':bind1', $bind1);
                OCIBindByName($stid, ':bind2', $bind2);
                $customer = OCIExecute($stid);
                echo('this is customer ' + $customer);
                $util->printResult($stid);
                OCICommit($db_conn);
                if ($debug) {
                        echo "Customer found\n";
                }
        }


        if ($debug) {
                echo "good\n";
        }

        OCILogoff($db_conn);
}
else {
        $err = OCIError();
        echo "Oracle Connect Error" . $err['message'];
}

?>
</html>

