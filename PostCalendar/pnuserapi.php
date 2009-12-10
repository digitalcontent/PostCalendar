<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

include dirname(__FILE__) . '/global.php';

/**
 * postcalendar_userapi_buildView
 *
 * Builds the calendar display
 * @param string $Date mm/dd/yyyy format (we should use timestamps)
 * @return string generated html output
 * @access public
 */
function postcalendar_userapi_buildView($args)
{
    $dom         = ZLanguage::getModuleDomain('PostCalendar');
    $Date        = $args['Date'];
    $viewtype    = $args['viewtype'];
    $pc_username = $args['pc_username'];
    $filtercats  = $args['filtercats'];
    $func        = $args['func'];

    if (strlen($Date) == 8 && is_numeric($Date)) $Date .= '000000'; // 20060101 + 000000

    // finish setting things up
    $the_year = substr($Date, 0, 4);
    $the_month = substr($Date, 4, 2);
    $the_day = substr($Date, 6, 2);
    $last_day = Date_Calc::daysInMonth($the_month, $the_year);

    // prepare Month Names, Long Day Names and Short Day Names
    // as translated in the language files for template
    // (may be adding more here soon - based on need)
    $pc_month_names = explode(" ", __('January February March April May June July August September October November December', $dom));
    $pc_short_day_names = explode (" ", __(/*!First Letter of each Day of week*/'S M T W T F S', $dom));
    $pc_long_day_names = explode (" ", __('Sunday Monday Tuesday Wednesday Thursday Friday Saturday', $dom));

    // set up some information for later variable creation.
    // This helps establish the correct date ranges for each view.
    // There may be a better way to handle all this.
    switch (_SETTING_FIRST_DAY_WEEK) {
        case _IS_MONDAY:
            $pc_array_pos = 1;
            $first_day = date('w', mktime(0, 0, 0, $the_month, 0, $the_year));
            $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day - 1, $the_year));
            $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
            if ($end_dow != 0) {
                $the_last_day = $last_day + (7 - $end_dow);
            } else {
                $the_last_day = $last_day;
            }
            break;
        case _IS_SATURDAY:
            $pc_array_pos = 6;
            $first_day = date('w', mktime(0, 0, 0, $the_month, 2, $the_year));
            $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day + 1, $the_year));
            $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
            if ($end_dow == 6) {
                $the_last_day = $last_day + 6;
            } elseif ($end_dow != 5) {
                $the_last_day = $last_day + (5 - $end_dow);
            } else {
                $the_last_day = $last_day;
            }
            break;
        case _IS_SUNDAY:
        default:
            $pc_array_pos = 0;
            $first_day = date('w', mktime(0, 0, 0, $the_month, 1, $the_year));
            $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day, $the_year));
            $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
            if ($end_dow != 6) {
                $the_last_day = $last_day + (6 - $end_dow);
            } else {
                $the_last_day = $last_day;
            }
            break;
    }

    // Week View
    // This section will
    // find the correct starting and ending dates for a given
    // seven day period, based on the day of the week the
    // calendar is setup to run under (Sunday, Saturday, Monday)
    $first_day_of_week = sprintf('%02d', $the_day - $week_day);
    $week_first_day = date('m/d/Y', mktime(0, 0, 0, $the_month, $first_day_of_week, $the_year));
    list($week_first_day_month, $week_first_day_date, $week_first_day_year) = explode('/', $week_first_day);
    $week_first_day_month_name = pnModAPIFunc('PostCalendar', 'user', 'getmonthname',
        array('Date' => mktime(0, 0, 0, $week_first_day_month, $week_first_day_date, $week_first_day_year)));
    $week_last_day = date('m/d/Y', mktime(0, 0, 0, $the_month, $first_day_of_week + 6, $the_year));
    list($week_last_day_month, $week_last_day_date, $week_last_day_year) = explode('/', $week_last_day);
    $week_last_day_month_name = pnModAPIFunc('PostCalendar', 'user', 'getmonthname',
        array('Date' => mktime(0, 0, 0, $week_last_day_month, $week_last_day_date, $week_last_day_year)));

    // Setup some information so we know the actual month's dates
    // also get today's date for later use and highlighting
    $month_view_start = date('Y-m-d', mktime(0, 0, 0, $the_month, 1, $the_year));
    $month_view_end = date('Y-m-t', mktime(0, 0, 0, $the_month, 1, $the_year));
    $today_date = DateUtil::getDatetime('', '%Y-%m-%d');

    // Setup the starting and ending date ranges for pcGetEvents()
    switch ($viewtype) {
        case 'day':
            $starting_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_day, $the_year));
            $ending_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_day, $the_year));
            break;
        case 'week':
            $starting_date = "$week_first_day_month/$week_first_day_date/$week_first_day_year";
            $ending_date = "$week_last_day_month/$week_last_day_date/$week_last_day_year";
            $calendarView = Date_Calc::getCalendarWeek($week_first_day_date, $week_first_day_month,
            $week_first_day_year, '%Y-%m-%d');
            break;
        case 'month':
            $starting_date = date('m/d/Y', mktime(0, 0, 0, $the_month, 1 - $first_day, $the_year));
            $ending_date = date('m/d/Y', mktime(0, 0, 0, $the_month, $the_last_day, $the_year));
            $calendarView = Date_Calc::getCalendarMonth($the_month, $the_year, '%Y-%m-%d');
            break;
        case 'year':
            $starting_date = date('m/d/Y', mktime(0, 0, 0, 1, 1, $the_year));
            $ending_date = date('m/d/Y', mktime(0, 0, 0, 1, 1, $the_year + 1));
            $calendarView = Date_Calc::getCalendarYear($the_year, '%Y-%m-%d');
            break;
        case 'list':
            $starting_date = "$the_month/1/$the_year";
            $ending_date = "$the_month/$last_day/$the_year";
            $calendarView = Date_Calc::getCalendarMonth($the_month, $the_year, '%Y-%m-%d');
            break;
    }

    // Load the events
    $eventsByDate = & pnModAPIFunc('PostCalendar', 'event', 'getEvents',
        array('start'=>$starting_date, 'end'=>$ending_date, 'filtercats'=>$filtercats, 'Date'=>$Date, 'pc_username'=>$pc_username));

    // Create an array with the day names in the correct order
    $daynames = array();
    $numDays = count($pc_long_day_names);
    for ($i = 0; $i < $numDays; $i++) {
        if ($pc_array_pos >= $numDays) {
            $pc_array_pos = 0;
        }
        array_push($daynames, $pc_long_day_names[$pc_array_pos]);
        $pc_array_pos++;
    }
    unset($numDays);
    $sdaynames = array();
    $numDays = count($pc_short_day_names);
    for ($i = 0; $i < $numDays; $i++) {
        if ($pc_array_pos >= $numDays) {
            $pc_array_pos = 0;
        }
        array_push($sdaynames, $pc_short_day_names[$pc_array_pos]);
        $pc_array_pos++;
    }
    unset($numDays);

    // Prepare values for the template
    $prev_month = Date_Calc::beginOfPrevMonth(1, $the_month, $the_year, '%Y%m%d');
    $next_month = Date_Calc::beginOfNextMonth(1, $the_month, $the_year, '%Y%m%d');

    // Prepare links for template
    $pc_prev = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => $viewtype, 'Date' => $prev_month, 'pc_username' => $pc_username, 'filtercats' => $filtercats));
    $pc_next = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => $viewtype, 'Date' => $next_month, 'pc_username' => $pc_username, 'filtercats' => $filtercats));
    $prev_day = Date_Calc::prevDay($the_day, $the_month, $the_year, '%Y%m%d');
    $next_day = Date_Calc::nextDay($the_day, $the_month, $the_year, '%Y%m%d');
    $pc_prev_day = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'day', 'Date' => $prev_day, 'pc_username' => $pc_username, 'filtercats' => $filtercats));
    $pc_next_day = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'day', 'Date' => $next_day, 'pc_username' => $pc_username, 'filtercats' => $filtercats));
    $prev_week = date('Ymd', mktime(0, 0, 0, $week_first_day_month, $week_first_day_date - 7, $week_first_day_year));
    $next_week = date('Ymd', mktime(0, 0, 0, $week_last_day_month, $week_last_day_date + 1, $week_last_day_year));
    $pc_prev_week = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'week', 'Date' => $prev_week, 'pc_username' => $pc_username, 'filtercats' => $filtercats));
    $pc_next_week = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'week', 'Date' => $next_week, 'pc_username' => $pc_username, 'filtercats' => $filtercats));
    $prev_year = date('Ymd', mktime(0, 0, 0, 1, 1, $the_year - 1));
    $next_year = date('Ymd', mktime(0, 0, 0, 1, 1, $the_year + 1));
    $pc_prev_year = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'year', 'Date' => $prev_year, 'pc_username' => $pc_username, 'filtercats' => $filtercats));
    $pc_next_year = pnModURL('PostCalendar', 'user', 'view',
        array('viewtype' => 'year', 'Date' => $next_year, 'pc_username' => $pc_username, 'filtercats' => $filtercats));


    if (isset($calendarView)) $function_out['CAL_FORMAT'] = $calendarView;
    // convert categories array to proper filter info
    $catsarray = $filtercats['__CATEGORIES__'];
    foreach ($catsarray as $propname => $propid) {
        if ($propid <= 0) unset($catsarray[$propname]); // removes categories set to 'all'
    }

    $function_out['FUNCTION']          = $func;
    $function_out['VIEW_TYPE']         = $viewtype;
    $function_out['A_MONTH_NAMES']     = $pc_month_names;
    $function_out['A_LONG_DAY_NAMES']  = $pc_long_day_names;
    $function_out['A_SHORT_DAY_NAMES'] = $pc_short_day_names;
    $function_out['S_LONG_DAY_NAMES']  = $daynames;
    $function_out['S_SHORT_DAY_NAMES'] = $sdaynames;
    $function_out['A_EVENTS']          = $eventsByDate;
    $function_out['selectedcategories']= $catsarray;
    $function_out['PREV_MONTH_URL']    = DataUtil::formatForDisplay($pc_prev);
    $function_out['NEXT_MONTH_URL']    = DataUtil::formatForDisplay($pc_next);
    $function_out['PREV_DAY_URL']      = DataUtil::formatForDisplay($pc_prev_day);
    $function_out['NEXT_DAY_URL']      = DataUtil::formatForDisplay($pc_next_day);
    $function_out['PREV_WEEK_URL']     = DataUtil::formatForDisplay($pc_prev_week);
    $function_out['NEXT_WEEK_URL']     = DataUtil::formatForDisplay($pc_next_week);
    $function_out['PREV_YEAR_URL']     = DataUtil::formatForDisplay($pc_prev_year);
    $function_out['NEXT_YEAR_URL']     = DataUtil::formatForDisplay($pc_next_year);
    $function_out['MONTH_START_DATE']  = $month_view_start;
    $function_out['MONTH_END_DATE']    = $month_view_end;
    $function_out['TODAY_DATE']        = $today_date;
    $function_out['DATE']              = $Date;

    return $function_out;
}

/**
 * postcalendar_userapi_getDate
 *
 * get the correct day, format it and return
 * @param string format
 * @param string Date
 * @param string jumpday
 * @param string jumpmonth
 * @param string jumpyear
 * @return string formatted date string
 * @access public
 */
function postcalendar_userapi_getDate($args)
{
    if (!is_array($args)) {
        $format = $args; //backwards compatibility
    } else {
        $format    = (!empty($args['format'])) ? $args['format'] : '%Y%m%d%H%M%S';
        $Date      = $args['Date'];
        $jumpday   = $args['jumpday'];
        $jumpmonth = $args['jumpmonth'];
        $jumpyear  = $args['jumpyear'];
    }

    if (empty($Date)) {
        // if we still don't have a date then calculate it
        $time = time();
        if (pnUserLoggedIn()) $time += (pnUserGetVar('timezone_offset') - pnConfigGetVar('timezone_offset')) * 3600;
        // check the jump menu
        if (!isset($jumpday))   $jumpday = strftime('%d', $time);
        if (!isset($jumpmonth)) $jumpmonth = strftime('%m', $time);
        if (!isset($jumpyear))  $jumpyear = strftime('%Y', $time);
        $Date = (int) "$jumpyear$jumpmonth$jumpday";
    }

    $y = substr($Date, 0, 4);
    $m = substr($Date, 4, 2);
    $d = substr($Date, 6, 2);
    return strftime($format, mktime(0, 0, 0, $m, $d, $y));
}

/**
 * postcalendar_userapi_getmonthname()
 *
 * Returns the month name translated for the user's current language
 *
 * @param array $args['Date'] date to return month name of
 * @return string month name in user's language
 */
function postcalendar_userapi_getmonthname($args)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (!isset($args['Date'])) return LogUtil::registerError(__('Error! Required arguments not present.', $dom));
    $month_name = array('01' => __('January', $dom), '02' => __('February', $dom), '03' => __('March', $dom),
                    '04' => __('April', $dom), '05' => __('May', $dom), '06' => __('June', $dom),
                    '07' => __('July', $dom), '08' => __('August', $dom), '09' => __('September', $dom),
                    '10' => __('October', $dom), '11' => __('November', $dom), '12' => __('December', $dom));
    return $month_name[date('m', $args['Date'])];
}
