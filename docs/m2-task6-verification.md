\# Milestone 2 Task 6 Verification Notes

Owner: Rosmy Antony    
GitHub: rma1214    
UCID: rma9  

\#\# Purpose

This document records the commands, test values, and evidence used for Task 6: End-to-End Demo and MQ-Only Boundary Verification.

The required authentication path is:

App VM \-\> RabbitMQ/MQ VM \-\> DB VM \-\> RabbitMQ/MQ VM \-\> App VM

The App VM does not connect directly to the DB VM.

\#\# Test Values

Username: task6rma9    
Email: task6rma9@test.com    
Password: Used only during runtime testing and not committed or logged.

Unknown email test value: task7rma9@test.com    
Wrong password test value: WrongPassword123

\#\# App VM Runtime URLs

The App VM auth pages were served with the PHP development server during testing.

\`\`\`bash  
cd \~/it490-m2026-autopilot-engineers/app  
php \-S 0.0.0.0:8092

Register page:

http://100.125.189.17:8092/auth/register.php

Login page:

http://100.125.189.17:8092/auth/login.php

Dashboard page:

http://100.125.189.17:8092/auth/dashboard.php

Logout page:

http://100.125.189.17:8092/auth/logout.php

## **Evidence Collected**

* Runtime evidence of one user registering.  
* Database evidence that the registered user's password is stored as a hash.  
* Runtime evidence that an unknown email is rejected.  
* Runtime evidence that an incorrect password is rejected.  
* Runtime evidence of that user logging in.  
* Runtime evidence that the session persists during page navigation.  
* Runtime evidence of successful protected access while logged in.  
* Runtime evidence of logout.  
* Runtime evidence of failed protected access after logout.  
* Timestamps and matching test values connecting the steps.  
* Focused MQ evidence for the workflow.  
* Focused evidence proving the App VM does not connect directly to the DB VM.

## **MQ-Only App-to-DB Boundary Check**

Commands run from the App VM in the current repo:

cd \~/it490-m2026-autopilot-engineers

echo "Role: App Server | Owner: Rosmy Antony (rma9)"  
echo "Testing RabbitMQ access:"  
nc \-vz 100.123.225.26 5672

echo "Checking for direct database access:"  
grep \-RniE "mysqli|PDO|mysql|DB\_HOST|DB\_PASSWORD|3306" app \--exclude-dir=vendor \--exclude-dir=.git \--exclude=".env" \\  
|| echo "No direct database code or credentials found"

dpkg \-l | grep \-E "mysql-server|mariadb-server" \\  
|| echo "No database server installed on App VM"

Observed result:

* RabbitMQ connection on port 5672 succeeded.  
* No direct database code or credentials were found in the App VM files.  
* No MySQL/MariaDB database server was installed on the App VM.

## **MQ Workflow Evidence**

MQ evidence showed the `db.auth` queue with an active consumer. This confirms that the DB auth consumer was connected to RabbitMQ and ready to receive authentication messages.

DB auth consumer evidence showed auth workflow messages such as:

* `user.register`  
* `user.login`  
* invalid credentials for unknown email  
* invalid credentials for incorrect password

## **DB Hash Evidence**

The DB users table showed the registered user with a `password_hash` value beginning with a bcrypt-style hash prefix such as `$2y$12$`.

This proves the password was stored as a hash and not plaintext.

## **Final Result Summary**

Passed:

* App VM registration page loaded.  
* User registration completed and redirected to login.  
* Registered user was stored in the DB with a hashed password.  
* Unknown email login was rejected.  
* Incorrect password login was rejected.  
* Valid login succeeded.  
* Session persisted during dashboard/page navigation.  
* Protected dashboard access worked while logged in.  
* Logout worked.  
* Protected dashboard access failed after logout.  
* MQ workflow evidence was collected.  
* App VM no-direct-DB evidence was collected.

Failed:

* No final demo items failed.

Incomplete:

* Nothing remains incomplete once screenshots, issue link, PR link, and this verification document are linked in the final submission.

