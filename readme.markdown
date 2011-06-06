### Description
The unofficial Runkeeper API - Pretty much hacked, but better than nothing

### Requirements
PHP 5+, json_decode(), cURL, Runkeeper Account

### Details
This API is a hack that will allow you to log into your Runkeeper.com account and get your miles, calories, pacing, speed (mph), your street team members, their stats, keeps logs, local cache, etc.

To use the API you must meet the requirements above.

### Create your object

    /**
    Email     (string)  = Your email address for RK
    Password  (string)  = Your password
    Username  (string)  = Your username used on the site
    Keep Logs (boolean) = Set to true to keep logs, false to not (defaults to true)
    Use Cache (boolean) = Set to true to use local cache, false to not (default to true)
    
    If not storing the stats in a local DB, use cache. It cuts back call time from about
    50 seconds to 0.03.
    **/
    $rk = new Runkeeper('EMAIL', 'PASSWORD', 'USERNAME', KEEP_LOGS, USE_CACHE);
    
### Get your street team members (never cached)

    $street_team = $rk->get('street_team');
    
    /**
    Returns associative array with [$username] => $real_name
    Example Return:
    
    Array
    (
      [jason] => Jason Jacobs
      [phpfunk] => Jeff Johns
      [rboulette] => Russell E. Boulette Jr.
      [asdiet] => Annie
      ...
    )
    **/