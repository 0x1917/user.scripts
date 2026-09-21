#!/usr/bin/php
<?PHP
require_once("/usr/local/emhttp/plugins/user.scripts/helpers.php");

$command = "";
foreach ($argv as $arg) {
  $command .= $arg." ";
}
$command = str_replace($argv[0],"",$command);
$command = trim($command);
$origCommand = $command;
$origlogFile = dirname($command)."/log.txt";
$scriptVariables = getScriptVariables($origCommand);
if ( $scriptVariables['clearLog']??false ) {
  @unlink($origlogFile);
}
file_put_contents($origlogFile,"Script Starting ".date("M d, Y  H:i.s")."\n\n",FILE_APPEND);
$command = str_replace(" ","\ ",$command);
$logFile = str_replace(" ","\ ",$origlogFile);
$scriptName = basename(dirname($origCommand));

$command = $command." ".($scriptVariables['argumentDefault'] ?? "")." >> $logFile 2>&1";
file_put_contents("/tmp/user.scripts/running/$scriptName",getmypid());
file_put_contents($origlogFile,"Full logs for this script are available at $origlogFile\n\n",FILE_APPEND);
exec($command,$execOutput,$rc);
if ( $rc == 0 ) {
  file_put_contents($origlogFile,"Script Finished ".date("M d, Y  H:i.s")."\n\n",FILE_APPEND);
  if ( $scriptVariables['notifyOnSuccess'] ?? false ) {
    $notifyCommand = "/usr/local/emhttp/webGui/scripts/notify".
      " -e ".escapeshellarg("User Scripts").
      " -i ".escapeshellarg("normal").
      " -s ".escapeshellarg("User Script '$scriptName' finished successfully").
      " -d ".escapeshellarg("The script exited with a zero exit code.  Full logs for this script are available at $origlogFile");
    exec($notifyCommand);
  }
} else {
  file_put_contents($origlogFile,"Script Failed ".date("M d, Y  H:i.s")."  (Exit Code: $rc)\n\n",FILE_APPEND);
  if ( $scriptVariables['notifyOnFailure'] ?? true ) {
    $notifyCommand = "/usr/local/emhttp/webGui/scripts/notify".
      " -e ".escapeshellarg("User Scripts").
      " -i ".escapeshellarg("alert").
      " -s ".escapeshellarg("User Script '$scriptName' failed (exit code $rc)").
      " -d ".escapeshellarg("The script exited with a non-zero exit code ($rc).  Full logs for this script are available at $origlogFile");
    exec($notifyCommand);
  }
}
unlink("/tmp/user.scripts/running/$scriptName");
file_put_contents("/tmp/user.scripts/finished/$scriptName", $rc == 0 ? "finished" : "failed:$rc");
file_put_contents($origlogFile,"Full logs for this script are available at $origlogFile\n\n",FILE_APPEND);


?>

