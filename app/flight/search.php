<?php

session_start();

require_once __DIR__ . "/flight/flight_client.php";


$result = null;
$error = null;
$flights = [];



if ($_SERVER["REQUEST_METHOD"] === "POST")
{

    try
    {

        $type = $_POST["search_type"] ?? "";


        switch ($type)
        {


            case "flight":

                $result = sendFlightRequest(
                    "search.flight",
                    [
                        "routing_key" => "search.flight",

                        "payload" => [
                            "flight_number" =>
                                strtoupper(trim($_POST["flight_number"]))
                        ]
                    ]
                );

            break;



            case "airport":

                $result = sendFlightRequest(
                    "search.airport",
                    [
                        "routing_key" => "search.airport",

                        "payload" => [
                            "airport" =>
                                strtoupper(trim($_POST["airport"]))
                        ]
                    ]
                );

            break;



            case "route":

                $result = sendFlightRequest(
                    "search.route",
                    [
                        "routing_key" => "search.route",

                        "payload" => [

                            "origin" =>
                                strtoupper(trim($_POST["origin"])),

                            "destination" =>
                                strtoupper(trim($_POST["destination"]))

                        ]
                    ]
                );

            break;



            default:

                throw new Exception(
                    "Invalid search type"
                );
        }



        /*
        |--------------------------------------------------------------------------
        | Normalize RabbitMQ Response
        |--------------------------------------------------------------------------
        */


        $possible = [

            $result["payload"]["flights"] ?? null,

            $result["payload"]["results"] ?? null,

            $result["payload"]["data"] ?? null,

            $result["payload"] ?? null,

            $result["flights"] ?? null

        ];



        foreach($possible as $item)
        {

            if(empty($flights) && is_array($item))
            {

                /*
                Check if this is a list
                */

                if(isset($item[0]) && is_array($item[0]))
                {
                    $flights = $item;
                    break;
                }


                /*
                Check if this is a single flight
                */

                if(
                    isset($item["flight_number"])
                    ||
                    isset($item["number"])
                )
                {
                    $flights[] = $item;
                    break;
                }

            }

        }



    }

    catch(Exception $e)
    {

        $error = $e->getMessage();

    }

}


?>



<!DOCTYPE html>
<html>


<head>


<title>
XAIDYN Flight Search
</title>



<style>


*
{
    margin:0;
    padding:0;
    box-sizing:border-box;
}


body
{
    font-family:Arial, Helvetica, sans-serif;

    background:#f4f5f7;

}



.container
{
    width:90%;

    max-width:1200px;

    margin:50px auto;

}



.card
{
    background:white;

    padding:30px;

    border-radius:12px;

    box-shadow:0 5px 20px rgba(0,0,0,.08);

    margin-bottom:30px;

}



h1,h2
{
    margin-bottom:20px;
}



label
{
    display:block;

    font-weight:bold;

    margin-top:15px;

}



input,
select
{
    width:100%;

    padding:12px;

    margin-top:8px;

    border:1px solid #ccc;

    border-radius:6px;

}



button
{
    margin-top:25px;

    width:100%;

    padding:13px;

    border:none;

    border-radius:6px;

    background:#111;

    color:white;

    cursor:pointer;

}



button:hover
{
    background:#333;
}



.hidden
{
    display:none;
}



table
{
    width:100%;

    border-collapse:collapse;

}



thead
{
    background:#111;

    color:white;

}



th,
td
{
    padding:12px;

    text-align:left;

}



td
{
    border-bottom:1px solid #ddd;

}



tr:hover
{
    background:#f5f5f5;

}



.error
{
    background:#ffe5e5;

    color:#b30000;

    padding:15px;

    border-radius:8px;

}


pre
{
    background:#222;

    color:#00ff90;

    padding:20px;

    overflow:auto;

}


</style>


</head>



<body>



<div class="container">



<div class="card">


<h1>
Flight Search
</h1>




<form method="POST">



<label>
Search Type
</label>


<select 
name="search_type"
id="search_type"
onchange="changeSearch()">



<option value="flight">
Flight Number
</option>



<option value="airport">
Airport
</option>



<option value="route">
Route
</option>



</select>




<div id="flight">


<label>
Flight Number
</label>


<input
name="flight_number"
placeholder="UA1343">


</div>





<div id="airport" class="hidden">


<label>
Airport Code
</label>


<input
name="airport"
placeholder="EWR">


</div>





<div id="route" class="hidden">


<label>
Origin
</label>


<input
name="origin"
placeholder="EWR">


<label>
Destination
</label>


<input
name="destination"
placeholder="SFO">


</div>



<button>
Search
</button>



</form>



</div>






<?php if($error): ?>


<div class="card error">

<?=htmlspecialchars($error)?>

</div>


<?php endif; ?>







<?php if(!empty($flights)): ?>


<div class="card">


<h2>
Results
</h2>



<table>


<thead>

<tr>

<th>Flight</th>
<th>Airline</th>
<th>Status</th>
<th>From</th>
<th>To</th>
<th>Terminal</th>
<th>Time</th>
<th>Aircraft</th>

</tr>

</thead>



<tbody>


<?php foreach($flights as $flight): ?>


<tr>


<td>

<?=htmlspecialchars(
$flight["flight_number"]
??
$flight["number"]
??
"N/A"
)?>

</td>



<td>

<?=htmlspecialchars(
$flight["airline"]
??
$flight["airline"]["name"]
??
"N/A"
)?>

</td>



<td>

<?=htmlspecialchars(
$flight["status"]
??
"N/A"
)?>

</td>



<td>

<?=htmlspecialchars(
$flight["departure_airport"]
??
$flight["departure"]["airport"]["name"]
??
"N/A"
)?>

</td>



<td>

<?=htmlspecialchars(
$flight["arrival_airport"]
??
$flight["movement"]["airport"]["name"]
??
"N/A"
)?>

</td>



<td>

<?=htmlspecialchars(
$flight["terminal"]
??
$flight["movement"]["terminal"]
??
"N/A"
)?>

</td>



<td>

<?=htmlspecialchars(
$flight["scheduled_departure"]
??
$flight["movement"]["scheduledTime"]["utc"]
??
"N/A"
)?>

</td>



<td>

<?=htmlspecialchars(
$flight["aircraft_model"]
??
$flight["aircraft"]["model"]
??
"N/A"
)?>

</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php elseif($result): ?>


<div class="card">


<h2>
Debug Response
</h2>


<pre>

<?=htmlspecialchars(
print_r($result,true)
)?>

</pre>


</div>


<?php endif; ?>



</div>





<script>


function changeSearch()
{

let type =
document.getElementById("search_type").value;



document.getElementById("flight").classList.add("hidden");

document.getElementById("airport").classList.add("hidden");

document.getElementById("route").classList.add("hidden");



document.getElementById(type)
.classList.remove("hidden");


}


</script>



</body>

</html>
