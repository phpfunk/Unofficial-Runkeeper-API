### Description
The unofficial Runkeeper API - Pretty much hacked, but better than nothing

### Requirements
PHP 5+, json_decode(), cURL, Runkeeper Account

### Details
This API is a hack that will allow you to log into your Runkeeper.com account and get your miles, calories, pacing, speed (mph), your street team members, their stats, keeps logs, local cache, etc.

To use the API you must meet the requirements above.

### Create your object

    /**
    Arguments
    --------------
    Email     (string)  = Your email address for RK
    Password  (string)  = Your password
    Username  (string)  = Your username used on the site
    Keep Logs (boolean) = Set to true to keep logs, false to not (defaults to true)
    Use Cache (boolean) = Set to true to use local cache, false to not (default to true)
    
    Return
    --------------
    N/A
    
    Cached
    --------------
    N/A
    
    Notes
    --------------
    If not storing the stats in a local DB, use cache. It cuts back call time from about
    50 seconds to 0.03.
    **/
    
    $rk = new Runkeeper('EMAIL', 'PASSWORD', 'USERNAME', KEEP_LOGS, USE_CACHE);
    
### Get your street team members (never cached)

    /**
    Arguments
    --------------
    Action (string)  = street_team
    
    Return
    --------------
    Associative array with [$username] => $real_name
    Array
    (
      [jason] => Jason Jacobs
      [phpfunk] => Jeff Johns
      [asdiet] => Annie
      ...
    )
    
    Cached
    --------------
    No, never
    
    Notes
    --------------
    This includes your username to make it easy to loop through stats if so desired.
    **/
    
    $street_team = $rk->get('street_team');
    
    
### Extracting Calories, Pace, Miles, Speed and Stats

For each of the above they are all called the same way. You use the get() method and submit the first argument as the action and an array of data for the second. That array of data can be used to narrow down the data returned.

    Example:
    $calories = $rk->get('calories', array(
      'username'  =>  'phpfunk',
      'type'      =>  'run',
      'distance'  =>  '>=3.1',
      'variance'  =>  .25,
      'min_date'  => '05/01/2011',
      'max_date'  => '05/31/2011'
    ));
    
Those are the keys you can submit for each method in the heading above. None of which are required. They can all be used to narrow down the data to be returned.

    Explanation
    --------------
    username    =>  The username to get the data for
      - Defaults to your username
      
    type        =>  The type of activity to extract (walk, run, etc)
      - Defaults to ALL
      
    distance    =>  The distance to use
      - You can use the =, >, <, >= or <= symbols for distance
      - Defaults to ALL
      
    variance    =>  If you want to extract all 5Ks but want to allow for overage, set the overage here
      - Defaults to 0
      
    min_date    =>  The minimum date to extract data from
      - Defaults to the 1st of the current month
      
    max_date    =>  The maximum date to extract data from
      - Defaults to the current data
    

### Extract your calories

    /**
    Arguments
    --------------
    Action  (string) =  calories
    Options (array)  =  see explanation above
    
    
    Return
    --------------
    Array
    (
      [activity] => Array
          (
              [1307176800] => Array
                  (
                      [date] => 06/04/2011
                      [calories] => 931
                  )
              ...
          )
    
      [total] => 1573
      [most] => 931
      [least] => 642
      [average] => 786.50
    )
    
    Cached
    --------------
    Yes
    
    Notes
    --------------
    Will also return average, total, most and least calories earned.
    **/
    
    $calories = $rk->get('calories', array(
      'type'      =>  'run',
      'distance'  =>  '>=3.1',
      'min_date'  => '06/01/2011'
    ));
    
### Extract your pacing

    /**
    Arguments
    --------------
    Action  (string) =  pace
    Options (array)  =  see explanation above
    
    
    Return
    --------------
    Array
    (
      [activity] => Array
          (
              [1307176800] => Array
                  (
                      [date] => 06/04/2011
                      [pace] => 8:33
                  )
              ...
          )
  
      [fastest] => 7:52
      [slowest] => 8:33
      [average] => 8:13
    )
    
    Cached
    --------------
    Yes
    
    Notes
    --------------
    Will also return average, fastest and slowest pace.
    **/
    
    $pace = $rk->get('pace', array(
      'type'      =>  'run',
      'distance'  =>  '>=3.1',
      'min_date'  => '06/01/2011'
    ));
    
### Extract your miles

    /**
    Arguments
    --------------
    Action  (string) =  miles
    Options (array)  =  see explanation above
    
    
    Return
    --------------
    Array
    (
      [activity] => Array
          (
              [1307176800] => Array
                  (
                      [date] => 06/04/2011
                      [distance] => 8.19
                  )
              ...
          )
  
      [total] => 13.26
      [longest] => 8.19
      [shortest] => 5.07
      [average] => 6.63
    )
    
    Cached
    --------------
    Yes
    
    Notes
    --------------
    Will also return average, total, longest and shortest miles.
    **/
    
    $miles = $rk->get('miles', array(
      'type'      =>  'run',
      'distance'  =>  '>=3.1',
      'min_date'  => '06/01/2011'
    ));
    
### Extract your speed

    /**
    Arguments
    --------------
    Action  (string) =  speed
    Options (array)  =  see explanation above
    
    
    Return
    --------------
    Array
    (
      [activity] => Array
          (
              [1307176800] => Array
                  (
                      [date] => 06/04/2011
                      [speed] => 7.02
                  )
              ...
      [fastest] => 7.63
      [slowest] => 7.02
      [average] => 7.33
    )
    
    Cached
    --------------
    Yes
    
    Notes
    --------------
    Will also return average, fastest and slowest speed.
    **/
    
    $speed = $rk->get('speed', array(
      'type'      =>  'run',
      'distance'  =>  '>=3.1',
      'min_date'  => '06/01/2011'
    ));
    
### Extract all stats

    /**
    Arguments
    --------------
    Action  (string) =  stats
    Options (array)  =  see explanation above
    
    
    Return
    --------------
    Array
    (
      [1307176800] => Array
          (
              [distance] => 8.19
              [pace] => 8:33
              [calories] => 931
              [duration] => 1:10:00
              [speed] => 7.02
              [elevation] => 786
              [type] => RUN
          )
      ...
    )
    
    Cached
    --------------
    Yes
    
    Notes
    --------------
    Have fun.
    **/
    
    $stats = $rk->get('stats', array(
      'type'      =>  'run',
      'distance'  =>  '>=3.1',
      'min_date'  => '06/01/2011'
    ));
    
### Get total miles in June for your entire street team

    $street_team = $rk->get('street_team');
    foreach ($street_team as $username => $fqn) {
      $miles = $rk->get('miles', array(
        'username' => $username,
        'type'     => 'run',
        'min_date' => '06/01/2011'
      ));
  
      print 'RUNNER: ' . $fqn . ' (' . $username . ') ran ' . $miles['total'] . ' miles in June.<BR>';
    }

    /**
    Example :: 
    RUNNER: Jason Jacobs (jason) ran 18.98 miles in June.
    RUNNER: Jeff Johns (phpfunk) ran 13.26 miles in June.
    RUNNER: Annie (asdiet) ran 3.1 miles in June.
    **/
    
### Get the raw data

For every call the application goes out and grabs all the activity json URLs and extracts the data. If you want to use the raw data feeds, you can simply call the $rk->json variable and see all the data.

    $stats = $rk->get('stats', array(
      'type'      =>  'run',
      'distance'  =>  '>=3.1',
      'min_date'  => '06/01/2011'
    ));

    print '<pre>';
    print_r($rk->json);
    print '</pre>';