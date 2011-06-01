<?php
include('class.http.php');
class Runkeeper extends HTTP {

  public $email        = NULL;
  public $keep_log     = TRUE;
  public $log_path     = 'logs/';
  public $password     = NULL;
  public $street_team  = array();
  public $username     = NULL;
  
  protected $args      = array();
  protected $feeds     = array();

  public function __construct($email, $password, $username, $keep_log=TRUE)
  {
    parent::__construct();
    $this->email = $email;
    $this->password = $password;
    $this->username = $username;
    $this->webbot = 'Unofficial Runkeeper API - https://github.com/phpfunk/Unofficial-Runkeeper-API';
    $this->cookie_file = 'cookies/runkeeper-api.txt';
    $this->keep_log = $keep_log;
    $this->reset_feeds();
    $this->login();
  }
  
  protected function _print_r($arr)
  {
    print '<pre>';
    print_r($arr);
    print '</pre>';
  }
  
  protected function activity()
  {

    $this->log_start();
    $this->log_write('Getting activities...');
    $username = (isset($this->args['username'])) ? $this->args['username'] : $this->username;
    $max_date = (isset($this->args['max_date'])) ? strtotime($this->args['max_date']) : strtotime(date('m/d/Y'));
    $min_date = (isset($this->args['min_date'])) ? strtotime($this->args['min_date']) : mktime(0,0,0, date('m'), 1, date('Y'));
    
    $this->log_write('Connecting to: http://runkeeper.com/user/' . $username . '/activity/');
    $html = $this->connect('http://runkeeper.com/user/' . $username . '/activity/');
    $this->check_errors();
    
    $this->reset_feeds();
    
    preg_match_all('~.*?link="(/user/' . $username . '/activity/(\d+))".*?~', $html, $m);
    foreach ($m[2] as $activity_id) {
      $activity_html = $this->connect('http://runkeeper.com/user/' . $username . '/activity/' . $activity_id);
      preg_match_all('~<span class="secondary">(.*?)</span>~is', $activity_html, $am);
      foreach ($am[1] as $date) {
        $date = explode('-', $date);
        $date = strtotime(trim(strip_tags($date[1])));
        if (date('Y', $date) >= 2005) {
          break;
        }
      }
        
      if ($date < $min_date) {
        break;
      }

      if ($date <= $max_date && $date >= $min_date) {
        $this->log_write('Finding activity for ' . $username . '...');
        $this->log_write('Activity ID: ' . $activity_id);
        $this->log_write('Activity Date: ' . date('m/d/Y', $date));
        $this->log_write('Max Date: ' . date('m/d/Y', $max_date));
        $this->log_write('Min Date: ' . date('m/d/Y', $min_date));
        $this->log_write('Max Range: ' . $date . ' <= ' . $max_date);
        $this->log_write('Min Range: ' . $date . ' >= ' . $min_date);
        $this->log_write('JSON URL: http://runkeeper.com//ajax/pointData?activityId=' . $activity_id);
        $this->log_write('GPX URL: http://runkeeper.com/download/activity?activityId=' . $activity_id . '&downloadType=gpx');
        $this->log_end();
        $this->feeds['json'][$date] = 'http://runkeeper.com//ajax/pointData?activityId=' . $activity_id;
        $this->feeds['gpx'][$date] = 'http://runkeeper.com/download/activity?activityId=' . $activity_id . '&downloadType=gpx';
      }
    }
    $this->log_end();
  }
  
  protected function calories()
  {
    $stats = $this->get('stats');
    
    $this->log_start();
    $this->log_write('Getting calories you skinny mofo...');
    $total = 0;
    $calories = 0;
    $average = (isset($this->args['return']) && $this->args['return'] == 'average');
    foreach ($stats as $date => $arr) {
      $use = (isset($this->args['distance'])) ? $this->use_distance($this->args['distance'], $arr['distance']) : TRUE;
      if ($use === TRUE) {
        $total += 1;
        $this->log_write('Use stat: Yes');
        $this->log_write('Finding total calories...');
        $calories += $arr['calories'];
        $this->log_write('Current Calories: ' . $arr['calories']);
        $this->log_write('Total Calories: ' . $calories);
        $this->log_write('------------------------------');
      }
    }
    
    if ($average === TRUE) {
      $calories = number_format($calories / $total, 2, '.', '');
      $this->log_write('Average Calories Burned: ' . $calories . ' out of ' . $total . ' activities.');
    }
    else {
      $this->log_write('Total Calories Burned: ' . $calories . ' out of ' . $total . ' activities.');
    }
    
    $this->log_end();
    return $calories;
  }
  
  protected function check_errors()
  {
    if ($this->errors()) {
      $this->log_start();
      $this->log_write('Connection errors found');
      foreach ($this->errors as $error) {
        $this->log_write($error);
      }
      $this->log_end();
      exit;
    }
  }
  
  public function get($action, $args=array())
  {
    if (method_exists($this, $action)) {
      if (! is_array($args) || @count($args) > 0) {
        $this->args = $args;
      }
      return $this->$action();
    }
    else {
      $this->log_start();
      $this->log_write('Method does not exist: ' . $action);
      if (@count($args) > 0 || ! is_array($args)) {
        $this->log_write('Arguments Submitted -> ');
        if (! is_array($args)) {
          $this->log_write('NULL => ' . $args);
        }
        else {
          foreach ($args as $k => $v) {
            $this->log_write($k . ' => ' . $v);
          }
        }
      }
      $this->log_end();
    }
  }
  
  protected function log_end()
  {
    $this->log_write('--------------------------------------------' . "\n");
  }
  
  protected function log_start()
  {
    $this->log_write(date('m/d/Y H:i:s A'));
    $this->log_write('--------------------------------------------');
  }
  
  protected function log_write($msg)
  {
    if ($this->keep_log === TRUE) {
      $file = date('Ymd') . '.log';
      $fp = fopen($this->log_path . $file, 'a');
      fwrite($fp, $msg . "\n");
      fclose($fp);
    }
  }
  
  public function login($email=NULL, $password=NULL)
  {
    $this->log_start();
    $this->log_write('Logging in...');
    $this->email    = (! empty($email)) ? $email : $this->email;
    $this->password = (! empty($password)) ? $password : $this->password;
    $this->query = array(
      'email'       =>  $this->email,
      'password'    =>  $this->password,
      '_eventName'  =>  'login'
    );
    
    $this->log_write('Connecting to: http://runkeeper.com/login (POST)');
    $html = $this->connect('http://runkeeper.com/login', 'POST');
    $this->check_errors();
    
    if (stristr($html, 'following errors:')) {
      $this->log_write('There were errors with the provided credentials. Please try again.');
      $this->log_end();
      print 'There were errors with the provided credentials. Please try again.';
      exit;
    }
    
    $this->log_write('Login was successful.');
    $this->log_end();
  }
  
  protected function miles()
  {
    $stats = $this->get('stats');
    $miles = 0;
    foreach ($stats as $date => $arr) {
      $miles += $arr['distance'];
    }
    return $miles;
  }
  
  protected function pace()
  {
    $stats = $this->get('stats');
    $this->log_start();
    $this->log_write('Start pacing yourself...');
    $pace = NULL;
    $total = 0;
    $average = (isset($this->args['return']) && $this->args['return'] == 'average');
    foreach ($stats as $date => $arr) {
      $use = (isset($this->args['distance'])) ? $this->use_distance($this->args['distance'], $arr['distance']) : TRUE;
      if ($use === TRUE) {
        $total += 1;
        $this->log_write('Use stat: Yes');
        if ($average === TRUE) {
          $this->log_write('Calculating average pace...');
          $tmp = explode(':', $arr['pace']);
          $pace += $tmp[0] + ($tmp[1] / 60);
          $this->log_write('This Time: ' . $arr['pace']);
          $this->log_write('Total Time: ' . $pace);
        }
        else {
          //Best Pace
          $this->log_write('Evaluating best pace...');
          $this->log_write('Pace: ' . $pace);
          $pace = (empty($pace)) ? $arr['pace'] : $pace;
          $pace = (str_replace(':', '.', $arr['pace']) < str_replace(':', '.', $pace)) ? $arr['pace'] : $pace;
          $this->log_write('Current Pace: ' . $arr['pace']);
          $this->log_write('Best Pace: ' . $pace);
        }
      }
    }
    
    if ($average === TRUE) {
      $pace = $pace / $total;
      $tmp = explode('.', $pace);
      $pace = $tmp[0] . ':' . number_format(($pace - $tmp[0]) * 60, 0, '', '');
      $this->log_write('Average Pace: ' . $pace . ' out of ' . $total . ' activities.');
    }
    else {
      $this->log_write('Best Pace: ' . $pace . ' out of ' . $total . ' activities.');
    }
    
    $this->log_end();
    return $pace;
    
  }
  
  protected function parse_json($url, $date=NULL)
  {
    $json = json_decode($this->connect($url, 'POST'));
    $this->check_errors();
    if (! empty($date)) {
      $json->date = date('m/d/Y', $date);
    }
    return $json;
  }
  
  protected function reset_feeds()
  {
    $this->feeds['json'] = array();
    $this->feeds['gpx']  = array();
  }
  
  protected function stats()
  {
    $this->get('activity');
    $stats = array();
    $this->log_start();
    $this->log_write('Getting stats...');
    foreach ($this->feeds['json'] as $date => $url) {
      $this->log_write('Connecting to: ' . $url);
      $json = $this->parse_json($url, $date);
      if ((isset($this->args['type']) && strtolower($this->args['type']) == strtolower($json->activityType)) || ! isset($this->args['type'])) {
        $stats[$date] = array();
        $stats[$date]['distance'] = $json->statsDistance;
        $stats[$date]['pace'] = $json->statsPace;
        $stats[$date]['calories'] = $json->statsCalories;
        $stats[$date]['duration'] = $json->statsDuration;
        $stats[$date]['speed'] = $json->statsSpeed;
        $stats[$date]['elevation'] = $json->statsElevation;
        $stats[$date]['type'] = $json->activityType;
        $this->log_write('Activity Type: ' . $json->activityType . ' - Stats used.');
      }
      else {
        $this->log_write('Activity Type: ' . $json->activityType . ' - Stats not used.');
      }
    }
    $this->log_end();
    return $stats;
  }
  
  protected function street_team()
  {
    if (! is_array($this->args)) {
      $str = $this->args;
      $this->args = array();
      $this->args['username'] = $str;
      unset($str);
    }
    
    $this->log_start();
    $this->log_write('Street team extraction starting...');
    $this->log_write('Connecting to: http://runkeeper.com/user/' . $username . '/streetTeam');

    $username = (isset($this->args['username'])) ? $this->args['username'] : $this->username;
    $html = $this->connect('http://runkeeper.com/user/' . $username . '/streetTeam');
    $this->check_errors();
    
    preg_match_all('~<div class="userInfoBox">.*?<a class="usernameLink" href="/user/(.*?)/profile">(.*?)</a>~is', $html, $m);
    
    if (count($m) > 0) {
      foreach ($m[1] as $key => $value) {
        $this->street_team[$value] = $m[2][$key];
        $this->log_write('Street team member found: ' . $value . ' => ' . $m[2][$key]);
      }
    }
    else {
      $this->log_write('No street team found.');
    }
    $this->log_end();
  }
  
  protected function use_distance($eq, $distance)
  {
    $variance = (isset($this->args['variance'])) ? $this->args['variance'] : 0;
    $distance = round($distance, 2);
    $this->log_write('Checking distance...');
    $this->log_write('EQ: ' . $eq);
    $this->log_write('Distance: ' . $distance);
    if (stristr($eq, '<=')) {
      $eq = trim(str_replace('<=', '', $eq));
      return $distance <= round(($eq + $variance), 2);
    }
    elseif (stristr($eq, '>=')) {
      $eq = trim(str_replace('>=', '', $eq));
      return $distance >= round(($eq + $variance), 2);
    }
    elseif (stristr($eq, '<')) {
      $eq = trim(str_replace('<', '', $eq));
      return $distance < round(($eq + $variance), 2);
    }
    elseif (stristr($eq, '>')) {
      $eq = trim(str_replace('>', '', $eq));
      return $distance > round(($eq + $variance), 2);
    }
    else {
      return round(($eq + $variance), 2) == $distance;
    }
  }

}
?>