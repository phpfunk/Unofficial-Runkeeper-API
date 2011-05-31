<?php
error_reporting(E_ALL);
include('class.runkeeper.php');
$rk = new Runkeeper('YOUR EMAIL', 'YOUR PASS', 'YOUR USERNAME', BOOLEAN FOR KEEPING LOGS);


//Get activity after or equal to 5/15
$rk->get('activity', array(
  'min_date' => '05/15/2011'
));

//Get fastest pace of 5K or above
$pace = $rk->get('pace', array(
  'username'  =>  'phpfunk',
  'type'      =>  'run',
  'return'    =>  'best',
  'distance'  =>  '>=3.1',
  'min_date'  => '05/01/2011',
  'max_date'  => '05/31/2011'
));

//Get street team and total running miles in May for each
$rk->get('street_team');
foreach ($rk->street_team as $username => $fqn) {
  $miles = $rk->get('miles', array(
    'username' => $username,
    'min_date' => '05/01/2011',
    'max_date' => '05/31/2011',
    'type'     => 'run'
  ));
  
  print 'RUNNER: ' . $fqn . ' (' . $username . ') ran ' . $miles . ' miles in May.<BR>';
}
?>