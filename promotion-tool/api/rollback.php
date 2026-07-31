<?php
//cao39: api rollback procedure (remove + restore from backup folder)

require_once "../lib/inventory.php";

try {

    //cao39: Grabbing user input to define what lane to roll back and which folder to restore

    $destination = trim(readline("Please enter the lane to you wish to roll back (e.g., qa or production): "));
    $backupFolder = trim(readline("Please enter the backup folder name to restore (Please use the latest backup): "));

    $inventory = loadInventory();
    $config = getRoleConfiguration($inventory, $destination, "api");


    //cao39: removing the current API files (.env is left untouched)

    echo PHP_EOL;
    echo "Removing the current API files..." . PHP_EOL;


    $removeCommand =
        "ssh " .
        $config["user"] .
        "@" .
        $config["host"] .
        " \"rm -rf " .
        $config["destination_path"] .
        "/*\"";


    exec(
        $removeCommand,
        $removeOutput,
        $removeResult
    );


    if ($removeResult !== 0) {

        throw new RuntimeException(
            "Error. Unable to remove current API files."
        );

    }


    echo "Current API files removed." . PHP_EOL;



    //Cao39: restoring the backup as requested (.env is ignored)

    echo PHP_EOL;
    echo "One moment..Restoring backup..." . PHP_EOL;


    $restoreCommand =
        "ssh " .
        $config["user"] .
        "@" .
        $config["host"] .
        " \"cp -r ~/backups/" .
        $backupFolder .
        "/* " .
        $config["destination_path"] .
        "/\"";


    exec(
        $restoreCommand,
        $restoreOutput,
        $restoreResult
    );


    if ($restoreResult !== 0) {

        throw new RuntimeException(
            "Unable to restore backup."
        );

    }


    echo "Backup restored successfully." . PHP_EOL;



    /*cao39: creating rollback log */

    writePromotionLog(

        "ROLLBACK: API restored on {$destination}. " .
        "Backup: {$backupFolder}"

    );


    echo PHP_EOL;
    echo "Rollback added to log." . PHP_EOL;



    /*
        cao39: Rollback completion
    */

    echo PHP_EOL;
    echo "Rollback has been successfully completed." . PHP_EOL;


}
catch (RuntimeException $e) {

    writePromotionLog(
        "ROLLBACK FAILED: " . $e->getMessage()
    );


    echo PHP_EOL;
    echo "Error: " .
        $e->getMessage() .
        PHP_EOL;

}

?>
