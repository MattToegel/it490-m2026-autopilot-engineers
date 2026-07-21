<?php
// US-04 Admin Community Management Test File
// Tests:
//   AC1 - View all users
//   AC2 - Delete report
//   AC3 - Update user role

require_once "admin_client.php";



/* ==========================================================
 * US-04 AC1 - View All Users
 * ==========================================================
 */

echo "==============================\n";
echo "US-04 AC1 - View Users\n";
echo "==============================\n";

$response = sendAdminRequest(
    "usr.adm.list",
    [
        "admin_user_id" => 2,
    ]
);

print_r($response);

echo "\n\n";



/* ==========================================================
 * US-04 AC3 - Update User Role
 * ==========================================================
 */

echo "==============================\n";
echo "US-04 AC3 - Update User Role\n";
echo "==============================\n";

$response = sendAdminRequest(
    "role.adm.update",
    [
        "admin_user_id" => 2,
        "user_id" => 16,
        "role"    => "admin"
    ]
);

print_r($response);

echo "\n\n";


/* ==========================================================
 * US-04 AC2 - Delete Report
 * ==========================================================
 */
/*
echo "==============================\n";
echo "US-04 AC2 - Delete Report\n";
echo "==============================\n";

$response = sendAdminRequest(
    "report.adm.delete",
    [
        "admin_user_id" => 2,
        "report_id" => 4
    ]
);

print_r($response);

echo "\n";
*/
?>
