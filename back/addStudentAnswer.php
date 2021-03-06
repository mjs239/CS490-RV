<?php
//addStudentAnswer.php, Matthew Stepnowski
include "db.php";
$examID = $_POST['examID'];
$questionID = $_POST['questionID'];
$username = $_POST['username'];
$studentAnswer = $_POST['studentAnswer'];
$json = array();

//Recieving necessary inputs for the autograding process
$testCasesInputs = mysqli_query($connection, "SELECT testCasesInputs FROM CS490_questions WHERE questionID = '$questionID'");
$testCasesInputs = mysqli_fetch_assoc($testCasesInputs);
$testCasesInputs=$testCasesInputs["testCasesInputs"];

$testCasesOutputs = mysqli_query($connection, "SELECT testCasesOutputs FROM CS490_questions WHERE questionID = '$questionID'");
$testCasesOutputs = mysqli_fetch_assoc($testCasesOutputs);
$testCasesOutputs=$testCasesOutputs["testCasesOutputs"];

$questionDescription = mysqli_query($connection, "SELECT description FROM CS490_questions WHERE questionID = '$questionID'");
$questionDescription = mysqli_fetch_assoc($questionDescription);
$questionDescription=$questionDescription["description"];

$points = mysqli_query($connection, "SELECT points FROM CS490_exams WHERE examID='$examID' AND questionID = '$questionID'");
$points = mysqli_fetch_assoc($points);
$points=$points["points"];

$questionConstraint = mysqli_query($connection, "SELECT questionConstraint FROM CS490_questions WHERE questionID = '$questionID'");
$questionConstraint = mysqli_fetch_assoc($questionConstraint);
$questionConstraint=$questionConstraint["questionConstraint"];

//trigger the autograding
function triggerAutograde($examID, $questionID,$questionDescription, $username, $studentAnswer, $questionConstraint, $testCasesInputs, $testCasesOutputs, $points){
  $data = array('message_type' => 'auto_grade', 'examID' => $examID, 'questionID' => $questionID,'questionDescription' => $questionDescription,'username' => $username, 'studentAnswer' => $studentAnswer, 'questionConstraint' => $questionConstraint, 'testCasesInputs' => $testCasesInputs, 'testCasesOutputs' => $testCasesOutputs, 'points' => $points);
 	
  $url = "https://web.njit.edu/~mjs239/CS490/rc/newMiddle.php";
 	$curl = curl_init();
 	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
 	$res = curl_exec($curl); //Recieve the encoded JSON response from the backend
  curl_close ($curl);
  return $res;
}
$result = triggerAutograde($examID, $questionID,$questionDescription, $username, $studentAnswer, $questionConstraint, $testCasesInputs, $testCasesOutputs, $points);

//Inserting info into database
$result= json_decode($result);

$examID = $result->examID;
$questionID = $result->questionID;
$username = $result->username;
$grade = $result->grade;
$grade = json_encode($grade);
$overallGrade = $result->overallGrade;

$result1 = mysqli_query($connection, "INSERT INTO `CS490_studentGrading`(`examID`, `questionID`, `username`, `studentAnswer`, `grade`, `overallGrade`) VALUES ('$examID','$questionID','$username','$studentAnswer', '$grade', '$overallGrade')");
//result from database for adding the students grades
if ($result1) {
  $json = array("message_type" => "success");
  echo json_encode($json);
} else {
$json = "Error: " . $result1 . "<br>" . mysqli_error($connection);
}

$log = fopen("../rc/logFile.txt", "a") or die("Unable to open Log File"); //Log the result
$logTxt = "DATABASE RESULT ".$json.PHP_EOL.PHP_EOL;
fwrite($log,$logTxt);
fclose($log);


mysqli_close($conn);
?>