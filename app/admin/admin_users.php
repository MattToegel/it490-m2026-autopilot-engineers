<?php
//cao39 - admin users
// admin_users.php
// US-04 AC1 - View Users
// cao39 - Admins can also search for users via:
// - username
// - email
// - user ID


session_start();


require_once "../auth/auth_protect.php";
require_once "admin_client.php";



// cao39 -  Only administrators may access

if (($_SESSION['role'] ?? '') !== 'admin')
{
    die("Access Denied");
}



$users = [];

$error = null;



// cao39 - Stores search text from admin input

$searchTerm = trim($_GET['search'] ?? '');





/*
==========================================================
cao39 - US-04 AC1 - Shows all users

MQ Queue Routing:
usr.adm.list
==========================================================
*/


if ($searchTerm === '')
{

    $response = sendAdminRequest(
        "usr.adm.list",
        []
    );

}

else

{

/*
==========================================================

US-04 User Search

Routing:
usr.adm.search

Allows admin to search:
- username
- email
- user ID

==========================================================
*/


    $response = sendAdminRequest(
        "usr.adm.search",
        [
            "search" => $searchTerm
        ]
    );

}





/*
==========================================================
Process DB VM response
==========================================================
*/


if ($response === null)
{

    $error = "Admin service unavailable.";

}

elseif (($response['status'] ?? '') === "success")
{

    $users = $response['users'] ?? [];

}

else
{

    $error =
        $response['message']
        ??
        "Unable to load users.";

}



?>



<!DOCTYPE html>

<html lang="en">


<head>


<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">


<title>
Admin Users | OnTheRadar
</title>


<link rel="stylesheet"
      href="admin.css">


</head>




<body>



<div class="admin-page">



<header class="admin-header">


<h1>
OnTheRadar Admin Panel
</h1>



<nav>


<a href="admin_dashboard.php">
Dashboard
</a>


<a href="admin_users.php">
Users
</a>


<a href="admin_reports.php">
Reports
</a>


<a href="admin_roles.php">
Roles
</a>


<a href="../dashboard.php">
User Dashboard
</a>


</nav>



</header>





<main class="admin-content">



<h2>
User Management
</h2>




<!--
==========================================================

Admin search form

Allows:
username
email
user id

==========================================================
-->


<section class="admin-card">


<form method="GET"
      class="admin-search-form">


<label for="search">

Search Users

</label>



<input
    type="text"
    id="search"
    name="search"
    placeholder="Username, email, or ID"
    value="<?php echo htmlspecialchars($searchTerm); ?>">



<button
    class="admin-button"
    type="submit">

Search

</button>



<a href="admin_users.php"
   class="admin-cancel-button">

Clear

</a>



</form>



</section>





<?php if ($error): ?>


<div class="admin-error">


<?php echo htmlspecialchars($error); ?>


</div>


<?php endif; ?>






<section class="admin-card">



<table class="admin-table">


<thead>


<tr>

<th>
User ID
</th>


<th>
Username
</th>


<th>
Email
</th>


<th>
Role
</th>

<th>
Reports
</th>

<th>
Violations
</th>

</tr>


</thead>





<tbody>




<?php if (count($users) === 0): ?>


<tr>

<td colspan="4">

No users found.

</td>

</tr>




<?php else: ?>




<?php foreach ($users as $user): ?>



<tr>


<td>

<?php echo htmlspecialchars($user['user_id']); ?>

</td>



<td>

<?php echo htmlspecialchars($user['username']); ?>

</td>




<td>

<?php echo htmlspecialchars($user['email']); ?>

</td>




<td>


<span class="role-badge">


<?php echo htmlspecialchars($user['role']); ?>


</span>


</td>


<td>


<a href="admin_user_reports.php?user_id=<?php echo (int)$user['user_id']; ?>">


View Reports


</a>


</td>

<td>

<a href="admin_user_violations.php?user_id=<?php echo (int)$user['user_id']; ?>">

View Violations

</a>

</td>

</tr>



<?php endforeach; ?>




<?php endif; ?>



</tbody>



</table>



</section>



</main>





<footer class="admin-footer">


OnTheRadar Admin


</footer>




</div>



</body>


</html>
