<?php
include('class.http.php');
class Runkeeper extends HTTP {

  public $cache_path   = 'cache/';
  public $email        = NULL;
  public $feeds        = array();
  public $keep_log     = TRUE;
  public $log_path     = 'logs/';
  public $password     = NULL;
  public $street_team  = array();
  public $total_time   = 0;
  public $use_cache    = TRUE;
  public $username     = NULL;
  
  protected $args      = array();
  protected $hash      = NULL;
  protected $no_hash   = array();
  protected $start     = 0;

  public function __construct($email, $password, $username, $keep_log=TRUE, $use_cache=TRUE)
  {
    parent::__construct();
    
    $this->email        = $email;
    $this->password     = $password;
    $this->username     = $username;
    $this->webbot       = 'Unofficial Runkeeper API - https://github.com/phpfunk/Unofficial-Runkeeper-API';
    $this->cookie_file  = 'cookies/runkeeper-api.txt';
    $this->keep_log     = $keep_log;
    $this->use_cache    = $use_cache;
    $this->no_hash      = array('cache','stats','activity');
    
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
  
  protected function base_stats($key)
  {
    $stats = $this->get('stats');
    $return = array();
    $result['activity'] = array();
    $result['total'] = 0;
    foreach ($stats as $date => $arr) {
      $use = (isset($this->args['distance'])) ? $this->use_distance($this->args['distance'], $arr['distance']) : TRUE;
      if ($use === TRUE) {
        $result['activity'][$date] = array();
        $result['activity'][$date]['date'] = date('m/d/Y', $date);
        $result['activity'][$date][$key] = $arr[$key];
        
        if ($key == 'pace') {
          $tmp = explode(':', $arr[$key]);
          $result['total'] += $tmp[0] + ($tmp[1] / 60);
        }
        else {
          $result['total'] += $arr[$key];
        }

        $this->log_write($key . ': ' . $arr[$key] . ' on ' . date('m/d/Y', $date));
        $this->log_write('------------------------------');
      }
    }
    return $result;
  }
  
  protected function cache()
  {
    $this->log_start();
    $this->log_write('Finding cache file...');
    $file = $this->cache_path . $this->hash . '.cache';
    $this->log_end();
    return (file_exists($file) && $this->use_cache === TRUE) ? unserialize(file_get_contents($file)) : FALSE;
  }
  
  protected function calories()
  { 
    $this->log_start();
    $this->log_write('Getting calories you skinny mofo...');
    $calories = $this->base_stats('calories');
    $calories['most'] = $this->single_stat($calories, 'calories', 'most');
    $calories['least'] = $this->single_stat($calories, 'calories', 'least');
    $calories['average'] = (count($calories['activity']) > 0) ? number_format($calories['total'] / count($calories['activity']), 2, '.', '') : 0;
    $this->log_write('Average Calories: ' . $calories['average'] . ' out of ' . count($calories['activity']) . ' activities.');   
    $this->log_end();
    $this->total_time();
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
  
  protected function create_hash($action)
  {
    $this->log_start();
    $this->log_write('Creating hash key...');
    $str = $action;
    if (! is_array($this->args)) {
      $str .= $this->args;
    }
    else {
      foreach ($this->args as $k => $v) {
        $str .= $k . $v;
      }
    }
    $this->hash = md5($str);
    $this->log_write('Hash String: ' . $str);
    $this->log_write('Hash Key: ' . $this->hash);
    $this->log_write('Action: ' . $action);
    $this->log_end();
  }
  
  public function get($action, $args=array())
  {
    $create_hash = ($action == 'stats');
    
    if ($create_hash === TRUE) {
      $this->start = $this->timer();
    }
    
    if (method_exists($this, $action)) {
      if (! is_array($args) || @count($args) > 0) {
        $this->args = $args;
      }
      
      if ($create_hash === TRUE) {
        $this->create_hash($action);
      }
      else {
        $this->log_write('Hash key skipped, action sent: ' . $action);
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
    $this->log_start();
    $this->log_write('Counting the miles...');
    $miles = $this->base_stats('distance');
    $miles['longest'] = $this->single_stat($miles, 'distance', 'longest');
    $miles['shortest'] = $this->single_stat($miles, 'distance', 'shortest');
    $miles['average'] = (count($miles['activity']) > 0) ? number_format($miles['total'] / count($miles['activity']), 2, '.', ',') : 0;
    $this->log_end();
    $this->total_time();
    return $miles;
  }
  
  protected function pace()
  {
    $this->log_start();
    $this->log_write('Start pacing yourself...');
    $pace = $this->base_stats('pace');
    $pace['fastest'] = str_replace('.', ':', $this->single_stat($pace, 'pace', 'fastest'));
    $pace['slowest'] = str_replace('.', ':', $this->single_stat($pace, 'pace', 'slowest'));
    $average = (count($pace['activity']) > 0) ? $pace['total'] / count($pace['activity']) : 0;
    unset($pace['total']);
    $tmp = explode('.', $average);
    $pace['average'] = $tmp[0] . ':' . number_format(($average - $tmp[0]) * 60, 0, '', '');
    $this->log_write('Average Pace: ' . $pace['average']);
    $this->log_end();
    $this->total_time();
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
  
  protected function single_stat($activities, $key, $type)
  {
    $this->log_write('Finding ' . $type . ' ' . $key . '...');
    $result = NULL;

    $o_type = $type;
    if ($key == 'pace' && $type == 'slowest') {
      $type = 'fastest';
    }
    elseif ($key == 'pace' && $type == 'fastest') {
      $type = 'slowest';
    }

    foreach ($activities['activity'] as $date => $arr) {
      if (is_array($arr)) {
        $val = $arr[$key];
        $val = str_replace(':', '.', $val);
        $result = (empty($result)) ? $val : $result;
      
        if ($type == 'slowest' || $type == 'shortest' || $type == 'least') {
          $result = ($val < str_replace(':', '.', $result)) ? $val : $result;
        }
        else {
          $result = ($val > str_replace(':', '.', $result)) ? $val : $result;
        }
      }
    }
    
    $this->log_write($o_type . ' ' . $key . ' = ' . $result);
    return $result;
  }
  
  protected function speed()
  {
    $this->log_start();
    $this->log_write('You are lightning fast...');
    $speed = $this->base_stats('speed');
    $speed['fastest'] = $this->single_stat($speed, 'speed', 'fastest');
    $speed['slowest'] = $this->single_stat($speed, 'speed', 'slowest');
    $speed['average'] = (count($speed['activity']) > 0) ? number_format($speed['total'] / count($speed['activity']), 2, '.', ',') : 0;
    unset($speed['total']);
    $this->log_write('Average Speed: ' . $speed['average']);
    $this->log_end();
    $this->total_time();
    return $speed;
  }
  
  protected function stats()
  {
    $stats = $this->get('cache');
    if ($stats === FALSE) {
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
  		$this->write_cache($stats);
    }
    else {
      $this->log_start();
  		$this->log_write('Getting stats from cache...');
  		$this->log_write('File: ' . $this->cache_path . $this->hash . '.cache');
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
    
    $username = (isset($this->args['username'])) ? $this->args['username'] : $this->username;
    $this->log_start();
    $this->log_write('Street team extraction starting...');
    $this->log_write('Connecting to: http://runkeeper.com/user/' . $username . '/streetTeam');

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
    return $this->street_team;
  }
  
  protected function timer()
	{
    $this->log_start();
    $this->log_write('Starting timer...');
		list($msec, $sec) = explode(' ', microtime());
		$this->log_end();
		return ((float)$msec + (float)$sec);
	}
	
	protected function total_time()
	{
    $this->log_start();
		$timer = $this->start;
		$this->total_time = number_format($this->timer() - $timer, 5, '.', '');
		$this->log_write('Total execution time: ' . $this->total_time . ' seconds');
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

  protected function write_cache($stats)
  {
    $this->log_start();
    $this->log_write('Writing cache file...');
    $file = $this->cache_path . $this->hash . '.cache';
    $fp = @fopen($file, 'w');
    $return = @fwrite($fp, serialize($stats));
    @fclose($fp);
    
    if ($return !== FALSE) {
      $this->log_write('Stats successfully written to cache: ' . $this->cache_path . $this->hash . '.cache');
    }
    else {
      $this->log_write('Could not write cache file, please check permission: ' . $this->cache_path);
    }
    
    $this->log_end();
  }

}
?>