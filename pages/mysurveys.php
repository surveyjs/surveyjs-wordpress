<?php
function generateMySurveysPage () {
    ?>
    <h1>Hello SurveyJS!</h1>
    <?php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $accessKey = "dc736a6f384d48f9b71a1dd94d9d5c24";
    curl_setopt($ch, CURLOPT_URL, "https://surveyjs.io/api/MySurveys/getActive?ownerId=null&accessKey=".$accessKey);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $obj = json_decode($result);

    foreach ($obj as $surveyDefinition) {
        ?>
        <h1><?php echo $surveyDefinition->Name ?></h1>
        <?php
        }
}
?>
