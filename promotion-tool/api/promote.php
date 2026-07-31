<?php
/*xml: API Promotion Tool*/

require_once "../lib/inventory.php";

try {
    /*xml: This function is derived from Rosmys shared code. This function basically loads
    in information from the shared inventory.json file. */
    $inventory = loadInventory();

    /*xml: simply asks users to input and reads their reply on the source lane, detination lane, and
    and release number*/
    $source = readline("Type in the source lane (development or qa): ");
    $destination = readline("Type in the destination lane (qa or production): ");
    $releaseNum = readline("Type in the Release ID (ex. release-001): ");

    /*xml: this code is derved from Rosmys code, this function allows for checking if the
    requested promotion, given to us by the user, is valid and possible. This, for example,
    restricts promotions like development --> prod*/
    validatePromotion($inventory,$source,$destination);

    //xml: This is dervied from Rosmy's this function is in charge of returning the configuration lanes
    //requested by the user
    $sourceConfig = getRoleConfiguration($inventory,$source,"api");
    $destinationConfig = getRoleConfiguration($inventory,$destination,"api");

    /*xml: This portion of code takes the inputted release number and goes to
    to verify that one there is a manifest file and two that its runable or currupted
    error messages are as follows*/
    $findManifest=__DIR__ ."/releases/" .$releaseNum ."/manifest.json";

    if (!file_exists($findManifest)) {
        throw new RuntimeException(
            "There is no mainfest found."
        );
    }

    $manifest =json_decode(file_get_contents($findManifest),true);

    if (!is_array($manifest)) {
        throw new RuntimeException("Invalid manifest.");
    }

    //xml: prints the information our in ther terminal for confirmation
    echo "Release : {$manifest['release_id']}\n";
    echo "Role    : {$manifest['role']}\n";
    echo "From    : {$source}\n";
    echo "To      : {$destination}\n";


    /*xml: This portion of code checks to make sure that the files in the manifest file actually exist
    on the source computer itself. If the file does not exist, then the loop is terminated and error message
    is sent out. Otherwise, a sucessful message is proccessed*/
    foreach ($manifest["targets"] as $targets) {

    $sourceFile =
        __DIR__ .
        "/releases/" .
        $releaseNum .
        "/" .
        $targets;


    echo "Checking: $sourceFile\n";


    if (!file_exists($sourceFile)) {

        throw new RuntimeException(
            "Missing release file: $targets"
        );

    }


    echo "Verified: $targets\n";

}

    echo "\nCreating backup...\n";


    /*xml: this initiates the start of the backup files. A unique name for the backup folder is made by
    using the releasenumber and the date an time. */
    $backupName =$releaseNum ."_" .date("Ymd_His");

    $backupFolder ="/home/xml/backups/" .$backupName;

    /*xml: This command ssh into the destinaion vm, then a backup folder is made if truly needed
    if not then the command will proceed to copy the code into the backup folder. If there is any issue
    it will be promptd to the terminal using the following message*/
    $runBackup =
        "ssh " . $destinationConfig["user"] ."@" .
        $destinationConfig["host"] . " \"" .
        "mkdir -p " . $backupFolder .
        " && cp -r " . $destinationConfig["destination_path"] .
        "/. " .$backupFolder . "/\"";

    exec($runBackup,$out,$result);

    if ($result !== 0) {
        throw new RuntimeException("Backup failed.");
    }

    echo "Backup complete.\n";

    /*xml: This will create the file were SFTP commands will be in.
    The file is opened so that it can be written in. Then we traverse through all of the targets in the
    mainfest file. With every iteration, the command is created for each target including its source and
    destination. The the file is then closed*/
    $batch =__DIR__ ."/commands.txt";

    $fp =fopen($batch,"w");

    foreach ($manifest["targets"] as $targets) {

     $sourceFile =
        __DIR__ .
        "/releases/" .
        $releaseNum .
        "/" .
        $targets;


    $destinationFile =
        $destinationConfig["destination_path"] .
        "/" .
        $targets;

        fwrite(
            $fp,
            "put " .
            $sourceFile .
            " " .
            $destinationFile .
            "\n"
	);

    }

    fclose($fp);

    echo "\nStarting SFTP...\n";


    /*xml: This is building the sftp command. Then this command is executed. if the transfer is sucessful
     then theere is a sucess message sent to the terminal, if it is not, then the promotopn is stopped and the
     error is sent off to the terminal to noify user
    */
    $sftp =
        "sftp -b " .
        $batch .
        " " .
        $destinationConfig["user"] .
        "@" .
        $destinationConfig["host"];

    exec($sftp,$output,$transfer);

    if ($transfer !== 0) {

        throw new RuntimeException("SFTP transfer failed.");

    }

    /*Derived from Rosmy's code, this is a logging message that sends a sucesslog off */
    writePromotionLog("SUCCESS | " . "Release={$releaseNum} | " ."Role=API | " ."From={$source} | " ."To={$destination} | " ."Backup={$backupFolder}"

    );

    echo "\nPromotion completed\n";

}

/*xml: This catches any error within the try, if there is the this block will execute a failed log and will
notify the user*/
catch(RuntimeException $e){

    writePromotionLog("FAILED | " . $e->getMessage());

    echo "\nERROR: " . $e->getMessage() . "\n";

}

?>
