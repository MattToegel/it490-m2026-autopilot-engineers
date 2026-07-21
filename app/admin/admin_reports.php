<?php
//cao39 - US-04 Admin reports
// US-04 AC2 - Admin Report Moderation
// Administrator can remove harmful or inappropriate comments/reports
// and issue warnings.
//   Loads reports for moderation
//   Sends warning/flag action to DB VM
session_start();


require_once "../auth/auth_protect.php";
require_once "admin_client.php";



// Only administrators may access

if (($_SESSION['role'] ?? '') !== 'admin')
{
    die("Access Denied");
}



$message = null;





/*
==========================================================
Load reports from DB VM

Routing:
content.adm.report

==========================================================
*/


$response = sendAdminRequest(
    "content.adm.report",
    []
);



$reports = [];



if ($response !== null &&
    ($response['status'] ?? '') === "success")
{

    $reports = $response['reports'] ?? [];

}





/*
==========================================================
Delete Report

Routing:
report.adm.delete

Existing functionality

==========================================================
*/


if ($_SERVER['REQUEST_METHOD'] === "POST"
    &&
    isset($_POST['delete_report']))
{


    $reportId = (int)$_POST['report_id'];



    $response = sendAdminRequest(
        "report.adm.delete",
        [
            "report_id" => $reportId
        ]
    );



    if (($response['status'] ?? '') === "success")
    {

        $message =
            "Report deleted successfully.";

    }

    else

    {

        $message =
            $response['message']
            ??
            "Unable to delete report.";

    }



}







/*
==========================================================


Create Admin Warning / Flag

Routing:
create.adm.notice

Purpose:
Allows administrators to flag inappropriate
community reports/comments.

==========================================================
*/


if ($_SERVER['REQUEST_METHOD'] === "POST"
    &&
    isset($_POST['flag_report']))
{


    $reportId = (int)$_POST['report_id'];

    $reason =
        trim($_POST['reason']);



    $response = sendAdminRequest(
        "create.adm.notice",
        [

            "report_id" => $reportId,

            "reason" => $reason

        ]
    );



    if (($response['status'] ?? '') === "success")
    {

        $message =
            "Warning created successfully.";

    }

    else

    {

        $message =
            $response['message']
            ??
            "Unable to create warning.";

    }


}



?>



<!DOCTYPE html>

<html lang="en">


<head>


<meta charset="UTF-8">


<meta name="viewport"
content="width=device-width, initial-scale=1.0">


<title>
Admin Reports | OnTheRadar
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
Report Moderation
</h2>





<?php if ($message): ?>


<div class="admin-success">


<?php echo htmlspecialchars($message); ?>


</div>



<?php endif; ?>







<section class="admin-card">





<table class="admin-table">


<thead>


<tr>


<th>
Report ID
</th>


<th>
User
</th>


<th>
Airport
</th>


<th>
Category
</th>


<th>
Comment
</th>


<th>
Actions
</th>


</tr>


</thead>





<tbody>





<?php if (count($reports) === 0): ?>


<tr>

<td colspan="6">

No reports found.

</td>


</tr>




<?php else: ?>





<?php foreach ($reports as $report): ?>



<tr>




<td>

<?php echo htmlspecialchars(
$report['report_id']
); ?>

</td>





<td>

<?php echo htmlspecialchars(
$report['user_id']
); ?>

</td>





<td>

<?php echo htmlspecialchars(
$report['airport_code']
); ?>

</td>





<td>

<?php echo htmlspecialchars(
$report['category']
); ?>

</td>





<td>

<?php echo htmlspecialchars(
$report['comment_text']
); ?>

</td>





<td>





<!--
==========================================================
DELETE REPORT BUTTON

Existing AC2 functionality

==========================================================
-->

<form method="POST">


<input type="hidden"
name="report_id"
value="<?php echo $report['report_id']; ?>">



<button
class="admin-button delete-button"
name="delete_report"
type="submit">

Delete

</button>



</form>







<!--
==========================================================


Flag / Warning Form

==========================================================
-->



<form method="POST"
class="flag-form">


<input type="hidden"
name="report_id"
value="<?php echo $report['report_id']; ?>">



<input type="text"
name="reason"
placeholder="Warning reason"
required>



<button
class="admin-button warning-button"
name="flag_report"
type="submit">


Flag


</button>



</form>






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
