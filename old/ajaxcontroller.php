<?php
require_once 'lastfm.class.php'; 
/**
 * Manage Last.FM ajax calls.
 */

function main() {
  $result = false;
  $validActions = array (
    'tagcloud',
    'timeline'
  );
  if ( !empty($_POST['action']) && in_array($_POST['action'], $validActions, true)) {
    $result = $_POST['action']();
  }
  return $result;
}

/**** timelines ****/

function timeline() {

  // so, we start by getting the artist history.
  $validFrequencies = array('1', '2', '4');

  // BFV (the "v" stands for "validation")
  if (
    empty($_POST['frequency']) || 
    !in_array($_POST['frequency'], $validFrequencies) ||
    empty($_POST['user']) ||
    !is_string($_POST['user']) ||
    empty($_POST['subject']) ||
    !($_POST['subject'] == 'tags' || $_POST['subject'] == 'artists') ||
    empty($_POST['enddate']) ||
    !strtotime($_POST['enddate'])
  ) {
    // return 'Invalid data was submitted!';
  }

  // commit to the values
  $user = strtolower($_POST['user']); // what user?
  $subject = $_POST['subject']; // tags or artists?
  $frequency = $_POST['frequency']; // observations every X weeks
  $endDate = strtotime($_POST['enddate']); // starting X weeks ago
  $length = ($subject == 'artists') ? 12 : 8 ; // total # observations
  $artistSamples = ($subject == 'artists') ? 30 : 7; // artists per week
  $tagSamples = ($subject == 'artists') ? 0 : 20; // tags per week
  
  // if the date wasn't parsed correctly, complain
  if (!$endDate) return 'You didn\'t enter a valid end date.';
  
  // get the dates
  $rawDates = query ('user', 'getWeeklyChartList', array(
    'user' => $user
  ));
  // handle errors
  if ((string) $rawDates == 'No user with that name') {
    return (string) $rawDates;
  }
  elseif ((string)$rawDates['code'] == '8') {
    return 'Something weird happened. Your profile may not have enough data.';
  }
  elseif (is_string($rawDates) || !$rawDates) {
    return false;
  }
  // make a list of timestamps, keyed by readable start date
  $dates = array();
  $counter = 0;
  // we need to iterate the object backward so that
  //   we get the most recent items first.
  $chart = $rawDates->weeklychartlist->chart;
  for ($i = count($chart) - 1; $i >= 0; $i--)
  {
    // if we haven't reached our startweek yet,
    // then carry on until we do
    if ((int) $chart[$i]['to'] > $endDate) {
      continue;
    }
    $counter++;
    // if this isn't a multiple of our interval, continue
    if ($counter % $frequency) continue;
    // if we have enough data, quit
    if (count($dates) >= $length) break;
    // add the array value
    $value = $chart[$i];
    $dates[date('Y-m-d', (string) $value['to'])] = array (
      'to' => (string) $value['from'],
      'from' => (string) $value['from']
    ); 
  }
  if (!$dates || count($dates) < $length) {
    return 'You don\'t have enough chart data.';
  }
  // resort, oldest first
  asort($dates);
  // now, we pick up the artists.
  $artists = array();
  $weekCounts = array();
  foreach ($dates as $stringDate => $date) {
    // quit when we have enough dates
    if (count($artists) >= $length) break;
    // top artists for this week?
    $artists[$stringDate] = array();
    $rawWeek = query('user', 'getWeeklyArtistChart', array (
      'user' => $user,
      'from' => $date['from'],
      'to' => $date['to']
    ));
    // catch errors (poorly)
    if (!$rawWeek || is_string($rawWeek)) return 'Sorry, something weird happened.';
    $weekCounts[$stringDate] = 0;
    if ($rawWeek['status'] == 'ok') {
    // add artists for this week
      foreach ($rawWeek->weeklyartistchart->artist as $artist) {
        $weekCounts[$stringDate] += (int) $artist->playcount;
        // quit when we have enough artists
        if (count($artists[$stringDate]) >= $artistSamples) continue;
        $artists[$stringDate][(string)$artist->name] = (int) $artist->playcount;
      }
    }
    
  }
  
  // if we got nothing, error.
  $found = false;
  foreach ($weekCounts as $weekCount) {
    if ($weekCount) {
      $found = true;
      break;
    }
  }
  if (!$found) return 'No scrobbles were found during the specified time period.';
  
  // if we wanted artists, terminate.
  if ($subject == 'artists') return array('data'=>$artists, 'playCounts' => $weekCounts);
  
  // if we wanted tags, get 'em
  $tags = array();
  foreach ($artists as $date => $list) {
    $tags[$date] = array();
    foreach ($list as $artist => $count) {
      // only get 8 tags per week
      $rawTags = query('artist', 'getTopTags', array('artist'=>$artist));
      if (!$rawTags || is_string($rawTags)) return 'Sorry, something weird happened.';
      if ($rawTags['status'] == 'ok') {
        // we're just going to ignore hyphens
        foreach($rawTags->toptags->tag as $tag) {
          $tag = htmlentities(str_replace('-', ' ', strtolower((string)$tag->name)));
          if (isset($tags[$date][$tag])) {
            $tags[$date][$tag] += $count;
          }
          elseif (count($tags[$date]) <= $tagSamples) { 
            $tags[$date][$tag] = $count;
          }
        }
      }
    }
  }
  
  // if we got nothing, error.
  $found = false;
  foreach ($weekCounts as $weekCount) {
    if ($weekCount) {
      $found = true;
      break;
    }
  }
  if (!$found) return 'No tagged scrobbles were found during the specified time period.';
  // we got our tags. return.
  return array('data'=>$tags, 'playCounts' => $weekCounts);
  
} // end timeline

/**** tag clouds ****/

function tagcloud() {

  // so, we start by getting the artist history.
  $validPeriods = array('overall', '3month', '6month', '12month');
  // BFV (the "v" stands for "validation")
  if (
    empty($_POST['period']) || 
    !in_array($_POST['period'], $validPeriods) ||
    empty($_POST['user']) ||
    !is_string($_POST['user']) ||
    !is_string($_POST['excludes'])
  ) {
    return 'Invalid data was submitted!';
  }
  // commit to the values
  $period = $_POST['period'];
  $user = strtolower($_POST['user']);
  $excludes = (empty($_POST['excludes']) || !is_string($_POST['excludes'])) ? array() : explode(',', strtolower($_POST['excludes']));
  $excludes = array_map(function($value) {
    return trim($value);
  }, $excludes);
  // use the data to pick up the user's artist history
  $rawArtists = query('user', 'getTopArtists', array (
    'user' => urlencode($user),
    'period' => urlencode($period),
    'limit' => 20
  ));
  // if the user doesn't exist, die.
  if ((string) $rawArtists == 'No user with that name') {
    return (string) $rawArtists;
  }
  // now we tally the artists
  $artists = array();
  foreach($rawArtists->topartists->artist as $artist) {
    $artists[] = (string)$artist->name;
  }
  // now we pick up the tags
  $tags = array();
  foreach ($artists as $artist) {
    $rawTags = query('artist', 'getTopTags', array('artist'=>$artist));
    if ($rawTags && !is_string($rawTags)) {
      foreach($rawTags->toptags->tag as $tag) {
        $tag = htmlentities(strtolower((string)$tag->name));
        // if "hip hop" is already here, then put "hip-hop" in with it.
        // then, do the opposite. we're just gonna use the first one we see.
        if (isset($tags[str_replace(' ', '-', $tag)])) $tag = str_replace(' ', '-', $tag);
        elseif (isset($tags[str_replace('-', ' ', $tag)])) $tag = str_replace('-', ' ', $tag);
        $tags[$tag] = isset($tags[$tag]) ? ($tags[$tag] + 1) : 1;
      }
    }
  }
  // take the top 20 non-excluded tags
  arsort($tags);
  $result = array();
  foreach ($tags as $tag=>$count) {
    if (count($result) > 20) break;
    if (!in_array($tag, $excludes)) $result[$tag] = $count;
  }
  // sort the tags alphabetically
  ksort ($result);
  // finished!
  return $result;
}

/**
 * Create a color gradient.
 * @param string $in A well formed hex color string.
 * @param string $out A well formed hex color string.
 * @param int $steps The number of steps in the gradient.
 * @return array A list of hex codes.
 */
function gradient($in, $out, $steps) {
  // validate types, then make sure we have valid hex colors
  $pattern = '/^#?[a-fA-F0-9]{6}$/';
  if (!is_string($in) || !is_string($out) || !preg_match($pattern, $in) || !preg_match($pattern, $out) || !is_int($steps)) return false;
  // get rid of # prefix, if there is one
  $in = str_replace('#', '', $in);
  $out = str_replace('#', '', $out);
  // get the values
  $startRed = hexdec(substr($in, 0, 2));
  $startGreen = hexdec(substr($in, 2, 2));
  $startBlue = hexdec(substr($in, 4, 2));
  $endRed = hexdec(substr($in, 0, 2));
  $endGreen = hexdec(substr($in, 2, 2));
  $endBlue = hexdec(substr($in, 4, 2));
  // get the distance we're traveling
  $dRed = $endRed - $startRed;
  $dGreen = $endGreen - $startGreen;
  $dBlue = $endBlue - $startBlue;
  // get the distance per jump
  $stepRed = floor($dRed/$steps); 
  $stepGreen = floor($dGreen/$steps);
  $stepBlue = floor($dBlue/$steps);
  // begin the calculation
  $result = array();
  $cRed = $startRed;
  $cGreen = $startGreen;
  $cBlue = $startBlue;
  for ($i = 0; $i < $step; $i++) {
    $result[] = '#'.dechex($cRed).dechex($cGreen).dechex($cBlue);
    $cRed += $stepRed;
    $cGreen += $stepGreen;
    $cBlue += $stepBlue;
  }
  return $result;
}

/**
 * Perform an API query.
 * @param string $object The object to request from the API.
 * @param string $method The method to request from the API.
 * @param array $args Arguments to send with the query.
 * @return mixed False for fatal error.
 *               String for caught exception.
 *               SimpleXMLElement for success.
 */
function query($object, $method, $args = array()) {
  $lastFm = new Lastfm();
  $result = $lastFm->query($object, $method, $args);
  // return string error
  if ($result instanceof SimpleXMLElement && $result['status'] != 'ok') {
    return $result->error;
  }
  // return F or data
  return $result;
}

echo json_encode(main());

?>