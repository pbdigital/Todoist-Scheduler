<?php
 // error_reporting(E_ALL);
 // ini_set('display_errors', 'On');

// Allow any domain to make requests to this server
header("Access-Control-Allow-Origin: *");

// Allow the following HTTP methods: POST, GET, PUT, DELETE, and OPTIONS
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");

// Allow the following HTTP headers: Content-Type
header("Access-Control-Allow-Headers: Content-Type");

// Set the default timezone to Brisbane, Australia
date_default_timezone_set('Australia/Brisbane');

// Read the request body as a JSON string
$json = file_get_contents('php://input');

// Convert the JSON string to a PHP array
$tasks = json_decode($json, true);


// Initialize the duration variable to 0
$duration = 0;


require_once '../libs/google-api/vendor/autoload.php';


$client = new Google\Client();
$client->setAuthConfig('../libs/google-api/client_secret.json');
$client->addScope(Google\Service\Calendar::CALENDAR);
$redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
$client->setRedirectUri($redirect_uri);


$token = json_decode(file_get_contents('token.txt'), true);
if (!empty($token)) {
	$client->setAccessToken($token);
	if ($client->isAccessTokenExpired()) {
		$refreshToken = $client->getRefreshToken();
		$client->fetchAccessTokenWithRefreshToken($refreshToken);
		$token = $client->getAccessToken();
		file_put_contents('token.txt', json_encode($token));
	}
} else {
	$authUrl = $client->createAuthUrl();
	if (!empty($authUrl)) {
		header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
	}
}

$calendarService = new Google_Service_Calendar($client);

$calendarIds = ['CAL_ID','CAL_ID2']; // Replace with the calendar ID you want to retrieve events for

$timeMin = (new DateTime('today', new DateTimeZone('Australia/Brisbane')))->format(DateTime::ATOM);
$timeMax = (new DateTime('tomorrow', new DateTimeZone('Australia/Brisbane')))->format(DateTime::ATOM);
$google_events = [];
$events = [];
foreach ($calendarIds as $calendarId) {

	$events = $calendarService->events->listEvents($calendarId, [
		'timeMin' => $timeMin,
		'timeMax' => $timeMax,
		'timeZone' => 'Australia/Brisbane',
	]);


	foreach ($events as $event) {
		$startTime = new DateTime($event->start->dateTime);
		$endTime = new DateTime($event->end->dateTime);
		$duration = $endTime->diff($startTime);

		$startTime->setTimezone(new DateTimeZone('Australia/Brisbane'));
		$endTime->setTimezone(new DateTimeZone('Australia/Brisbane'));
		$start = strtotime($startTime->format('H:i:s'));
		$end = strtotime($endTime->format('H:i:s'));

		if ($end - $start >= (5 * 60)) {
			$google_events[] = [
				"name" => $event->summary,
				"start" => $startTime->format('H:i:s'),
				"end" => $endTime->format('H:i:s'),
			];
		}
	}
}



// Initialize an array to store events
$events = $google_events;


$startOfDay = "06:00:00";
$taskBuffer = 0; //15 minute buffer
$scheduledTasks = array(); // array to store the scheduled tasks

$hour = date('H');
$minute = date('i');
if ($minute < 15) {
	$minutes = 15 - $minute;
} elseif ($minute < 30) {
	$minutes = 30 - $minute;
} elseif ($minute < 45) {
	$minutes = 45 - $minute;
} else {
	$minutes = 60 - $minute;
}
$proposedStartTime = date('H:i:00', strtotime("+$minutes minutes"));
$currentTime =  date('H:i:00', strtotime("+$minutes minutes"));
//$proposedStartTime = date('16:15:00', strtotime("+$minutes minutes"));
// Convert the timestamp to a date and time in UTC

function sortByStart($a, $b)
{
	return strtotime($a['start']) - strtotime($b['start']);
}

usort($events, 'sortByStart');




foreach ($tasks as $task) {
	// get the duration of the task in minutes
	$duration = $task['duration'];
	$proposedStartTime = date('H:i:00', strtotime("+$minutes minutes"));
	
	//$proposedStartTime = date('17:15:00', strtotime("+$minutes minutes"));
	// set a flag to indicate whether the task has been scheduled or not
	$scheduled = false;


	echo 'Attempting to Schedule: ' . $task['name'];
	echo '<br>';
	// iterate over the events
	foreach ($events as $event) {
		$canSchedule = true;
		echo 'Checking ' . $event['name'] . ' ct ' . strtotime($proposedStartTime) . ' st' . strtotime($event['start']) . ' et' . strtotime($event['end']).'<br>';
		
		if (strtotime($currentTime) > strtotime($event['end'])) {
			$canSchedule = false;
			echo " - Proposed Start Is In Past<br>";
			
		}
		

		if (strtotime($proposedStartTime) >= strtotime($event["start"]) && strtotime($proposedStartTime) <= strtotime($event["end"])) {
			$canSchedule = false;
			echo " - Proposed Start Clashes<br>";
		}

		if (strtotime($proposedStartTime) + ($duration * 60) - 1 >= strtotime($event["start"]) && strtotime($proposedStartTime) + ($duration * 60) <= strtotime($event["end"])) {
			$canSchedule = false;
			echo " - Proposed End Clashes<br>";
		}



		$end = date("H:i:s", strtotime($proposedStartTime) + ($duration * 60));
		if (!$canSchedule) {
			//Update Proposed Start time to end of current event
			if (strtotime($event["end"]) > strtotime($currentTime)) {
				echo 'Updating Proposed Time <br>';
				$proposedStartTime = $event["end"];
				
			}
		} else {
			echo "scheduling task<br>";
			//$proposedStartTime = $event["end"];
			$end = date("H:i:s", strtotime($proposedStartTime) + ($duration * 60));
			$scheduledTasks[] = $events[] = array(

				"id" => $task['id'],
				"name" => $task['name'],
				"start" => $proposedStartTime,
				"end" => $end
			);

			usort($events, 'sortByStart');
			$scheduled = true;
			break;
		}
	}
	if (!$scheduled) {
		echo "<br>**Not Scheduled**<br>";
		echo strtotime($event["end"]).' - '.strtotime($currentTime);
		if (strtotime($event["end"]) > strtotime($currentTime)) {
			$start = $event["end"];
			$end = date("H:i:s", strtotime($start) + ($duration * 60));
		} else {
			$start = $currentTime;
			$end = date("H:i:s", strtotime($currentTime) + ($duration * 60));
		}
		$scheduledTasks[] = array(
			"id" => $task['id'],
			"name" => $task['name'],
			"start" => $start,
			"end" => $end
		);
	}

	echo '<br><br>';
}

foreach ($scheduledTasks as $task) {

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://api.todoist.com/rest/v2/tasks/' . $task['id'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => '{
		"due_datetime" : "' . gmdate(DATE_RFC3339, strtotime($task['start'])) . '"
	
	}',
		CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer TOKEN_GOES_HERE',
			'Content-Type: application/json',
		),
	));

	$response = curl_exec($curl);
	echo $response;
	curl_close($curl);
}





// print the scheduled tasks
echo '<pre>';
echo 'Events:<br>';
print_r($events);
echo 'Tasks:<br>';
print_r($tasks);
echo 'Scheduled Tasks:<br>';
print_r($scheduledTasks);
