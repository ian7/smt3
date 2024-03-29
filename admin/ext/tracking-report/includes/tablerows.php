<?php
// needed for async calls to this file
if (!session_id()) session_start();
/* Defining a relative path to smt2 root in this script is a bit tricky,
 * because this file can be called both from Ajax and regular HTML requests. 
 */
$base = realpath(dirname(__FILE__).'/../../../../');
require_once $base.'/config.php';
// use ajax settings
require_once dirname(__FILE__).'/settings.php';

// get ajax data
if (!empty($_GET['page'])) { $page = $_GET['page']; }

// $show is set on index.php
if (!isset($show)) {
  // check defaults from DB or current sesion
  $show = (isset($_SESSION['limit'])) ? $_SESSION['limit'] : db_option(TBL_PREFIX.TBL_CMS, "recordsPerTable");
  // sanitize (retrieve default value from settings.php)
  if (!$show) { $show = $defaultNumRecords; }
}

// set query limits
$start = $page * $show - $show;
$limit = "$start,$show";
// is JavaScript enabled?
if (isset($_GET[$resetFlag])) { $limit = $page*$show; }

// query priority: filtered or default
$where = (!empty($_SESSION['filterquery'])) ? $_SESSION['filterquery'] : "1"; // will group by log id

$records = db_select_all(
                          TBL_PREFIX.TBL_RECORDS,
                          //"id,client_id,cache_id,os_id,browser_id,ftu,ip,sess_date,sess_time,fps,coords_x,coords_y,clicks",
                          "*",
                          $where." ORDER BY sess_date DESC, client_id LIMIT $limit"
                        );
// if there are no more records, display message
if ($records) 
{ 
  $GROUPED = '<abbr title="Data are grouped">&mdash;</abbr>';
  // show pretty dates over timestamps if PHP >= 5.2.0
  if (check_systemversion("php", "5.2.0")) {
    $usePrettyDate = true;
    require_once SYS_DIR.'class.prettydate.php';
  }
  // call this function once, using session data for Ajax request
  $ROOT = is_root();
  // dump (smt) records  
  $tablerow = "";
  foreach ($records as $i => $r) 
  {
    // wait for very recent visits
    $timeDiff = time() - strtotime($r['sess_date']);
    
    $receivingData = ($timeDiff > 0 && $timeDiff < 30);
    $safeToDelete = ($timeDiff > 3600);
    // delete logs with no mouse data
    if ( $safeToDelete && !count(array_sanitize(explode(",", $r['coords_x']))) ) {
      db_delete(TBL_PREFIX.TBL_RECORDS, "id='".$r['id']."' LIMIT 1");
      continue;
    }
    
    $cssClass = ($i%2 == 0) ? "odd" : "even";
    
    if (!empty($_SESSION['groupby'])) 
    {
      $ftu = null;
      switch ($_SESSION['groupby'])
      {
        case 'cache_id':
        
          $IP = $GROUPED;
          $displayId = 'pid='.$r['cache_id'];
          $pageId = $r['cache_id'];
          $clientId = $GROUPED;
          // check if cached page exists
          $cache = db_select(TBL_PREFIX.TBL_CACHE, "file", "id='".$pageId."'");
          if (!is_file(CACHE_DIR.$cache['file'])) { continue; }
          
          break;
          
        case 'client_id':
        
          $IP = $GROUPED;
          $displayId = 'cid='.$r['client_id'];
          $pageId = $GROUPED;
          $clientId = mask_client($r['client_id']);
          
          break;
          
        case 'ip':
        
          $IP = mask_client(md5($r['ip']));
          $displayId = 'ip='.$r['ip'];
          $pageId = $GROUPED;
          $clientId = $GROUPED;
          // check if IP exists
          if (empty($r['ip'])) { continue; }
          
          break;
          
        default:
          break;
      }
      
      $displayDate     = $GROUPED;
      $browsingTime    = $GROUPED;
      $interactionTime = $GROUPED;
      $numClicks       = $GROUPED;
      
    } else {
      // display a start on first time visitors
      $ftu = ($r['ftu']) ? ' class="ftu"' : null;
      // use pretty date?
      $displayDate = ($usePrettyDate) ? 
        '<abbr title="'.prettyDate::getStringResolved($r['sess_date']).'">'.$r['sess_date'].'</abbr>' : $r['sess_date'];
      
      $browsingTime = $r['sess_time'];
      $interactionTime = round(count(explode(",", $r['coords_x']))/$r['fps'], 2);
      $IP = mask_client(md5($r['ip']));
      $displayId = 'id='.$r['id'];
      $pageId = $r['cache_id'];
      $clientId = mask_client($r['client_id']);

      // display number of mouse clicks
      $numClicks = count_clicks($r['clicks']);
    }
    
    // create list item
    $tablerow .= '<tr class="'.$cssClass.'">'.PHP_EOL;
    $tablerow .= ' <td'.$ftu.'>'.$clientId.'</td>'.PHP_EOL;
    $tablerow .= ' <td>'.$IP.'</td>'.PHP_EOL;
    $tablerow .= ' <td>'.$pageId.'</td>'.PHP_EOL;
    $tablerow .= ' <td>'.$displayDate.'</td>'.PHP_EOL;
    $tablerow .= ' <td>'.$browsingTime.'</td>'.PHP_EOL;
    $tablerow .= ' <td>'.$interactionTime.'</td>'.PHP_EOL;    
    $tablerow .= ' <td>'.$numClicks.'</td>'.PHP_EOL;
    $tablerow .= ' <td>'.PHP_EOL;
    
    if (!$receivingData)
    {
      if (isset($_SESSION['groupby']) && ($_SESSION['groupby'] == "client_id" || $_SESSION['groupby'] == "ip")) {
        $tablerow .= $GROUPED;
      } else {
        // apend dynamically the API to the query string, based on browser capabilities
        $tablerow .= '<a href="track.php?'.$displayId.'" class="view" title="View log"><img src="styles/track-view.png" alt="view"/></a>'.PHP_EOL;
      }
      
      $tablerow .= ' <a href="analyze.php?'.$displayId.'" title="Analyze log"><img src="styles/track-analyze.png" alt="analyze"/></a>'.PHP_EOL;
      $tablerow .= ' <a href="download.php?'.$displayId.'" title="Download log"><img src="styles/track-download.png" alt="download"/></a>'.PHP_EOL;
      if ($ROOT) {
        $tablerow .= ' <a href="delete.php?'.$displayId.'" class="del" title="Delete log"><img src="styles/track-remove.png" alt="delete"/></a>'.PHP_EOL;
      }
    }
    else
    {
      //$tablerow .= '<em>please wait...</em>';
      $tablerow .= '<em>receiving data...</em>';
    }

    $tablerow .= ' </td>'.PHP_EOL;
    $tablerow .= '</tr>'.PHP_EOL;
  }
    
  echo $tablerow;
  // check both normal and async (ajax) requests
  if ($start + $show < db_records()) {
    $displayMoreButton = true;
  } else {
    echo '<!--'.$noMoreText.'-->'.PHP_EOL;
  }

} else { echo '<!--'.$noMoreText.'-->'; }
?>
