<?php
/*xml: This file copies files into the release folder
. This file traverses throguh the manifest of a specific
release, looks through our main progect code and copies those
files into the release folder*/
require_once "../lib/inventory.php";


$inventory = loadInventory();


$releaseNum = readline("Please input the release ID (ex. release-001 etc): ");

/*xml: This finds the manifest file of the users inputted release folder*/
$findManifest =__DIR__ ."/releases/" .$releaseNum . "/manifest.json";

/*xml: This stops the execution of the code if the relase number folder the user
inputted does not have a manifest file*/
if (!file_exists($findManifest)) {

	die("The manifest file is missing\n");

}

/*xml: This reads the contents of the manifest file*/
$info =json_decode(file_get_contents($findManifest),true);


/*xml: This portion of cod stops the execution of the file if the
manifest file is no considered valid*/
if (!is_array($info)) {

    die("This is an invalid manifest\n");

}


$source =getRoleConfiguration($inventory,"development","api");


echo "\nCreating release: {$releaseNum}\n\n";


/*xml: This travereses through every file in the manifest's target list*/
foreach ($info["targets"] as $file) {


    /*xml: For every file in that target list, find where it lives in the main project folder*/
    $from = $source["source_path"] ."/" .$file;

    /*xml: this is the command that explains where the file should be 
copied to based on the inputted release number folder */
    $to =__DIR__ . "/releases/" . $releaseNum ."/" .$file;

    echo "Checking: $from\n";

	/*xml: conditional makes sure that the file exist in the 
	main project folder, if not the execution of this file is
	killed*/

    if (!file_exists($from)) {

        die("Missing source file: $file\n");

    }

    /*xml: This portion of code is in charge of making any folders
    as needed. If a folder (where the code is orginated from in the
    main folder) is not in the relese folder, this will make sure
    it is created*/
    $directory =
        dirname($to);


    if (!is_dir($directory)) {
	mkdir($directory,0775,true);
    }

    /*xml: Will copy the files from the main folder into the release
    one*/
    if (!copy($from, $to)) {

        die("The following file(s) failed faile to copy: $file\n");

    }


    echo "File(s) opied: $file\n\n";

}

//Xml: this will print if here were no issues and all files were sucessfully copied over
echo "All needed files have been moved over\n";

?>
