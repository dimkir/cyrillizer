<?php

//Úτƒ-8 encoded 2

include '../include.inc.php';
include_once 'utilsx.inc.php';
include 'loggingwizard.class.php';

$es = getParamOrDefault("es", ":empty:");
$ru = getParamOrDefault("ru", ":empty:");

$log = new LoggingWizard("d:/tmp/logroot/", "REST_SAVE");
$log->put("saving es [$es], ru [$ru]" );

echo $ru;

?>