<?php
error_reporting(-1);
ini_set('display_errors',1);


/**
 * Class Login
 *
 * PHP login class heavily based on:
 * @author Panique
 * @link https://github.com/panique/php-login-one-file/
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * Class has been edited to suit our needs for a hotel reservation system. 
 *
 */
class Login
{
    /**
     * @var object Database connection
     */
    private $db_connection = null;

    /**
     * @var bool Login status of user
     */
    private $user_is_logged_in = false;

    /**
     * @var string System messages, likes errors, notices, etc.
     */
    public $feedback = "";


    /**
     * Run Application as soon as it's created
     */
    public function __construct()
    {
        require_once("password_compatibility_library.php");
        $this->runApplication();
    }

    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication()
    {
        // check is user wants to see register page (etc.)
        if (isset($_GET["action"]) && $_GET["action"] == "register") {
            $this->doRegistration();
            $this->showPageRegistration();
        } else {
            // start the session, always needed!
            $this->doStartSession();
            // check for possible user interactions (login with session/post data or logout)
            $this->performUserLoginAction();
            // show "page", according to user's login status
            if ($this->getUserLoginStatus()) {
                $this->showPageLoggedIn();
            } else {
                $this->showPageLoginForm();
            }
        }
    }

    /**
     * Establishes database connection using OCI methods.
     * @return bool Database creation success status, false by default
     */
    private function createDatabaseConnection()
    {
        $this->db_connection = OCILogon("ora_b9y8", "a38319125", "ug");
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
     * Handles the flow of the login/logout process. According to the circumstances, a logout, a login with session
     * data or a login with post data will be performed
     */
    private function performUserLoginAction()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();
        } elseif (!empty($_SESSION['id']) && ($_SESSION['user_is_logged_in'])) {
            $this->doLoginWithSessionData();
        } elseif (isset($_POST["login"])) {
            $this->doLoginWithPostData();
        }
    }

    /**
     * Starts the session.
     * SETUP INSTRUCTIONS FOR JAS AND BANAFSHEH: 
     *      1. create a php_sessions directory in public_html
     *      2. chmod 755 it. 
     */
    private function doStartSession()
    {
        if(session_status() == PHP_SESSION_NONE) {
            session_save_path("php_sessions");
            session_start();
        }
    }

    /**
     * Set a marker (NOTE: is this method necessary ?)
     */
    private function doLoginWithSessionData()
    {
        $this->user_is_logged_in = true; // ?
    }

    /**
     * Process flow of login with POST data
     */
    private function doLoginWithPostData()
    {
        if ($this->checkLoginFormDataNotEmpty()) {
            if ($this->createDatabaseConnection()) {
                $this->checkPasswordCorrectnessAndLogin();
            }
        }
    }

    /**
     * Logs the user out
     */
    private function doLogout()
    {
        $_SESSION = array();
        session_destroy();
        $this->user_is_logged_in = false;
        $this->feedback = "You were just logged out.";
    }

    /**
     * The registration flow
     * @return bool
     */
    private function doRegistration()
    {
        if ($this->checkRegistrationData()) {
            if ($this->createDatabaseConnection()) {
                $this->createNewCustomer();
            }
        }
        // default return
        return false;
    }

    /**
     * Validates the login form data, checks if username and password are provided
     * @return bool Login form data check success state
     */
    private function checkLoginFormDataNotEmpty()
    {
        if (empty($_POST['name'])) {
            $this->feedback = "Name field was empty.";
            return false;
        }
        elseif (empty($_POST['address'])) {
            $this->feedback = "Address field was empty.";
            return false;
        }
        elseif (empty($_POST['password'])) {
            $this->feedback = "Password field was empty.";
            return false;
        }

        return true;
    }

    /**
     * Checks if user exits, if so: check if provided password matches the one in the database
     * @return bool User login success status
     */
    private function checkPasswordCorrectnessAndLogin()
    {
        if ($this->checkCustomerLogin()) {
            if ($this->user_is_logged_in) {
                // TODO: commented out for now, but perhaps this is an option for redirecting onto
                //       appropriate customer/employee/manager page
                // echo "<meta http-equiv=\"refresh\" content=\"0; URL='http://www.google.com'\" />";
                return true;
            }
        }
        elseif ($this->checkEmployeeLogin()) {
            if ($this->user_is_logged_in) {
                // TODO: commented out for now, but perhaps this is an option for redirecting onto
                //       appropriate customer/employee/manager page
                // echo "<meta http-equiv=\"refresh\" content=\"0; URL='http://www.google.com'\" />";
                return true;
            }
        }
        
        return false;
    }

    /**
     * Checks if user is a customer.
     * If provided name, address match an entry in the customer table,
     * @return true
     *
     * NOTE, method will return true even if the password is wrong, as the user is a customer.
     */
    private function checkCustomerLogin() {
        $sql = 'SELECT * FROM customers WHERE cname =:bind1 and address = :bind2';
        $statement = oci_parse($this->db_connection, $sql);

        ocibindbyname($statement, ':bind1', $_POST['name']);
        ocibindbyname($statement, ':bind2', $_POST['address']);

        $r = OCIExecute($statement, OCI_DEFAULT);
        if (!$r) {
            echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
            $e = OCI_Error($statement); // For OCIExecute errors pass the statementhandle
            echo htmlentities($e['message']);
            echo "<br>";
        }
        OCICommit($this->db_connection);

        $row = oci_fetch_array($statement);
        if ($row) {
            // Now, check if the password matches
            if (password_verify($_POST['password'], $row['PASSWORD'])) {
                $_SESSION['user_type'] = 'CUSTOMER';
                $_SESSION['id'] = $row['CID'];
                $_SESSION['user_is_logged_in'] = true;
                $this->user_is_logged_in = true;
            }
            else {
                $this->feedback = "Wrong password.";
            }

            // Row exists, so a customer by this name and address must exist.
            return true;
        }

        return false;
    }

    /**
     * Checks if user is an employee.
     * If provided name and location match an entry in the employee table,
     * @return true
     *
     * NOTE, method will return true even if the password is wrong, as the user is a employee.
     */
    private function checkEmployeeLogin() {
        $sql = 'SELECT * FROM employee WHERE name =:bind1 and location_address = :bind2';
        $statement = oci_parse($this->db_connection, $sql);

        ocibindbyname($statement, ':bind1', $_POST['name']);
        ocibindbyname($statement, ':bind2', $_POST['address']);

        $r = OCIExecute($statement, OCI_DEFAULT);
        if (!$r) {
            echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
            $e = OCI_Error($statement); // For OCIExecute errors pass the statementhandle
            echo htmlentities($e['message']);
            echo "<br>";
        }
        OCICommit($this->db_connection);

        $row = oci_fetch_array($statement);
        if ($row) {
            // Now, check if the password matches
            if (password_verify($_POST['password'], $row['PASSWORD'])) {
                $_SESSION['user_type'] = 'EMPLOYEE';
                $_SESSION['id'] = $row['EMPLOYEE_ID'];
                $_SESSION['user_is_logged_in'] = true;
                $this->user_is_logged_in = true;
            }
            else {
                $this->feedback = "Wrong password.";
            }

            // Row exists, so an employee by this name and location must exist.
            return true;
        }

        return false;
    }

    /**
     * Validates the user's registration input
     * @return bool Success status of user's registration data validation
     */
    private function checkRegistrationData()
    {
        // if no registration form submitted: exit the method
        if (!isset($_POST["register"])) {
            return false;
        }

        // validating the input
        if (!empty($_POST['name'])
            && !empty($_POST['address'])
            && !empty($_POST['password_new'])
            && strlen($_POST['password_new']) >= 3
            && !empty($_POST['password_repeat'])
            && ($_POST['password_new'] === $_POST['password_repeat'])
        ) {
            // only this case return true, only this case is valid
            return true;
        } elseif (empty($_POST['name'])) {
            $this->feedback = "Empty Username";
        } elseif (empty($_POST['password_new']) || empty($_POST['password_repeat'])) {
            $this->feedback = "Empty Password";
        } elseif ($_POST['password_new'] !== $_POST['password_repeat']) {
            $this->feedback = "Password and password repeat are not the same";
        } elseif (strlen($_POST['password_new']) < 3) {
            $this->feedback = "Password has a minimum length of 3 characters";
        } elseif (empty($_POST['address'])) {
            $this->feedback = "Address cannot be empty";
        } else {
            $this->feedback = "An unknown error occurred.";
        }

        // default return
        return false;
    }

    /**
     * Creates a new customer.
     * @return bool Success status of customer registration
     */
    private function createNewCustomer()
    {
        $name = htmlentities($_POST['name'], ENT_QUOTES);
        $address = htmlentities($_POST['address'], ENT_QUOTES);
        $password = $_POST['password_new'];
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // make sure no other customer already exists with cid
        do {
            $cid = rand(0, 1023);
            $sql = 'SELECT * FROM customers WHERE cid =:bind';
            $statement = oci_parse($this->db_connection, $sql);
            ocibindbyname($statement, ':bind', $cid);

            OCIExecute($statement, OCI_DEFAULT);
            OCICommit($this->db_connection);
        }
        while ($row = oci_fetch_array($statement));


        $sql = 'insert into customers values (:cname, :addr, :cid, :pw)';
        $statement = ociparse($this->db_connection, $sql);

        ocibindbyname($statement, ':cname', $name);
        ocibindbyname($statement, ':addr', $address);
        ocibindbyname($statement, ':cid', $cid);
        ocibindbyname($statement, ':pw', $password_hash);

        OCIExecute($statement, OCI_DEFAULT);
        $registration_success_state = OCICommit($this->db_connection);

        if ($registration_success_state) {
            $this->feedback = "Your account has been created successfully. You can now log in.";
        } else {
            $this->feedback = "Sorry, your registration failed. Please go back and try again.";
        }

        return $registration_success_state;
    }

    /**
     * Simply returns the current status of the user's login
     * @return bool User's login status
     */
    public function getUserLoginStatus()
    {
        return $this->user_is_logged_in;
    }

    /**
     * Simple demo-"page" that will be shown when the user is logged in.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoggedIn()
    {
        if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }

        echo 'Hello ' . $_SESSION['user_type'] . ', you are logged in.<br/><br/>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout">Log out</a>';
    }

    /**
     * Simple demo-"page" with the login form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoginForm()
    {
        if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }

        echo '<h2>Login</h2>';

        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '" name="loginform">';
        echo '<label for="login_input_username">Name</label> ';
        echo '<input id="login_input_username" type="text" name="name" required /> ';
        echo '<br>';
        echo '<label for="login_input_address">Address</label> ';
        echo '<input id="login_input_uaddress" type="text" name="address" required /> ';
        echo '<br>';
        echo '<label for="login_input_password">Password</label> ';
        echo '<input id="login_input_password" type="password" name="password" required /> ';
        echo '<br>';
        echo '<input type="submit"  name="login" value="Log in" />';
        echo '</form>';

        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=register">Register new account</a>';
    }

    /**
     * Simple demo-"page" with the registration form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageRegistration()
    {
        if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }

        echo '<h2>Registration</h2>';

        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '?action=register" name="registerform">';
        echo '<label for="login_input_username">Name</label>';
        echo '<input id="login_input_username" type="text" name="name" required />';
        echo '<br>';
        echo '<label for="login_input_email">Address</label>';
        echo '<input id="login_input_email" type="text" name="address" required />';
        echo '<br>';
        echo '<label for="login_input_password_new">Password (min. 3 characters)</label>';
        echo '<input id="login_input_password_new" class="login_input" type="password" name="password_new" pattern=".{3,}" required autocomplete="off" />';
        echo '<br>';
        echo '<label for="login_input_password_repeat">Repeat password</label>';
        echo '<input id="login_input_password_repeat" class="login_input" type="password" name="password_repeat" pattern=".{3,}" required autocomplete="off" />';
        echo '<br>';
        echo '<input type="submit" name="register" value="Register" />';
        echo '</form>';

        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '">Log In</a>';
    }
}

// Start login
$login = new Login();
