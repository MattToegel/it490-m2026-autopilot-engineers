<?php
// register.php
// tad46: Registration form and handler for the App VM
// rma9: Added Task 3 App-side invalid-input handling before MQ publish

require_once __DIR__ . '/auth_client.php';  // added by tad46: for auth requests
require_once __DIR__ . '/../logging/app_log.php';   // added by tad46: for logging events
//grab external php file and grab its php code to be used for register and to send messages to rabbitmq
$error = null; //added by tad46;
//no error at first in cases if something goes wrong $error will hold the error message

//rma9: run this if the user submits by clicking register if not the code shouldnt run
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // rma9 Task 3: safely collect and trim form input before using it
    $username = trim($_POST['username'] ?? ''); //rma9: will grab the username from the form and trim will remove any extra spaces if username is missing code will use empty text
    $email = trim($_POST['email'] ?? ''); //rma9: grabs the email from the form also removes extra spaces and if email is missing it uses empty text
    $password = $_POST['password'] ?? ''; //rma9: gets the password from the form. If password is missing, it uses empty text. do not use trim password because spaces might be part of the password.

    // rma9 Task 3: log email only; never log plaintext password
    publishAppLog('info', "Registration form submitted for {$email}"); //rma9: writes a log stating registration form is submitted and will save the email in the log but no password

    // rma9 Task 3: invalid input checks happen before sending to RabbitMQ
    if ($username === '') { //rma9: will check if the username is empty
        $error = 'Username is required.'; //if empty put error message into the error variable from above
    } elseif ($email === '') {  //if username is good check email 
        $error = 'Email is required.'; //rma9: if email is empty show error
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //rma9: check if the email format is bad, ! means it is not valid
        $error = 'Please enter a valid email address.'; //rma9: if email format is bad show this error
    } elseif ($password === '') { //rma9: will check if password is empty
        $error = 'Password is required.'; //rma9: if password is empty show this error
    } elseif (strlen($password) < 8) { //rma9: check if the password is less than 8 characters
        $error = 'Password must be at least 8 characters long.'; //rma9: show error if so
    } else { //all cases pass and the input is valid and will be sent to MQ
        // rma9 Task 3: only valid input reaches RabbitMQ
        $response = sendAuthRequest('user.register',  //rma9: good registration infor will be sent to rabbitmq, user.register means the message is to create a new user
        [ //rma9 : start of data that is going to be sent
            'username' => $username, //rma9:will send the username
            'email'    => $email, //rma9:will send the email
            'password' => $password, //rma9: will send the password thru MQ to DB
        ]); //rma9 : end of data package
        
        // tad46: handle successful registration response
        if ($response && $response['status'] === 'success') 
        {
            publishAppLog('info', "User registered: {$email}");
            header('Location: login.php?registered=1');
            exit;
        } 
        else 
        {
            // tad46: handle failed registration response
            publishAppLog('warning', "Registration Failed: " . ($response['message'] ?? 'no response'));
            $error = $response['message'] ?? 'service unavailable';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title> <!-- tad46: register page title -->
    <link rel="stylesheet" href="auth_style.css">
</head>
<body>
    <h1>Register</h1> <!-- tad46: register page heading -->

    <?php if ($error): ?> <!-- tad46: show an error message if registration fails -->
        <p style="color: red;">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?> <!-- rma9: safely display validation/service errors -->
        </p>
    <?php endif; ?>

    <form method="post"> <!-- tad46: registration form sends data using POST -->
        <label>Username: <!-- tad46: username field -->
            <input 
                name="username" 
                value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                required
            > <!-- rma9: keep username after error, but escape it safely -->
        </label><br>

        <label>Email: <!-- tad46: email field -->
            <input 
                name="email" 
                type="email" 
                value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                required
            > <!-- rma9: keep email after error, but escape it safely -->
        </label><br>

        <label>Password: <!-- tad46: password field -->
            <input name="password" type="password" required> <!-- tad46: password is hidden and not preserved after errors -->
        </label><br>

        <button>Register</button> <!-- tad46: submit registration form -->
    </form>

    <p>Already have an account? <a href="login.php">Log in</a></p> <!-- tad46: link to login page -->
</body>
</html>
