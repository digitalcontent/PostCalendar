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

/**
 * PostCalendar filter
 *
 * @param array  $args   array with arguments. Used values:
 *                       'type'  comma separated list of filter types;
 *                               can be one or more of 'user', 'category', 'topic' (required)
 *                       'class' the classname(s) (optional, default no class)
 *                       'label' the label on the submit button (optional, default _PC_TPL_VIEW_SUBMIT)
 *                       'order' comma separated list of arguments to sort on (optional)
 * @param Smarty $smarty
 */
function smarty_function_pc_filter($args, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('PostCalendar');
    if (empty($args['type'])) {
        $smarty->trigger_error(__("pc_filter: missing 'type' parameter", $dom));
        return;
    }
    $class = isset($args['class']) ? ' class="'.$args['class'].'"' : '';
    $label = isset($args['label']) ? $args['label'] : __('change', $dom);
    $order = isset($args['order']) ? $args['order'] : null;

    $jumpday   = FormUtil::getPassedValue('jumpday');
    $jumpmonth = FormUtil::getPassedValue('jumpmonth');
    $jumpyear  = FormUtil::getPassedValue('jumpyear');
    $Date      = FormUtil::getPassedValue('Date');
    $Date      = pnModAPIFunc('PostCalendar','user','getDate',compact('Date','jumpday','jumpmonth','jumpyear'));    

    if (!isset($y)) $y = substr($Date, 0, 4);
    if (!isset($m)) $m = substr($Date, 4, 2);
    if (!isset($d)) $d = substr($Date, 6, 2);

    $tplview = FormUtil::getPassedValue('tplview');
    $viewtype = FormUtil::getPassedValue('viewtype', _SETTING_DEFAULT_VIEW);
    $pc_username = FormUtil::getPassedValue('pc_username', _PC_FILTER_GLOBAL);
    $types = explode(',', $args['type']);

    //================================================================
    // build the username filter pulldown
    //================================================================
    if (pnModGetVar('PostCalendar', 'pcAllowUserCalendar')) { // do not show if users not allowed personal calendar
        if (in_array('user', $types)) {
            @define('_PC_FORM_USERNAME', true);
            //define array of filter options
            $filteroptions = array(
                _PC_FILTER_GLOBAL  => __('Global Events', $dom),
                _PC_FILTER_PRIVATE => __('My Events', $dom) ." ". __('Only', $dom),
                _PC_FILTER_ALL     => __('Global Events', $dom) ." + ". __('My Events', $dom),
            );
            // if user is admin, add list of users with private events
            if (pnSecAuthAction(0, 'PostCalendar::', '::', ACCESS_ADMIN)) {
                $users = DBUtil::selectFieldArray('postcalendar_events', 'informant', null, null, true, 'aid');
                    // if informant is converted to userid, then this area should be checked.
                $filteroptions = $filteroptions + $users;
            }
            // generate html for selectbox - should move this to the template...
            $useroptions = "<select name='pc_username' $class>";
            foreach ($filteroptions as $k => $v) {
                $sel = ($pc_username == $k ? ' selected="selected"' : '');
                $useroptions .= "<option value='$k'$sel$class>$v</option>";
            }
            $useroptions .= '</select>';
        }
    } else {
        // remove user from types array to force hidden input display below
        $key = array_search('user',$types);
        unset($types[$key]);
    }
    //================================================================
    // build the category filter pulldown
    //================================================================
    if (in_array('category', $types) && _SETTING_ALLOW_CAT_FILTER) {
        @define('_PC_FORM_CATEGORY', true);
        $category = FormUtil::getPassedValue('pc_category');
        $categories = pnModAPIFunc('PostCalendar', 'user', 'getCategories');
        $catoptions = "<select name=\"pc_category\" $class>";
        $catoptions .= "<option value=\"\" $class>" . __('All Categories', $dom) . "</option>";
        foreach ($categories as $c) {
            $sel = ($category == $c['catid'] ? 'selected="selected"' : '');
            $catoptions .= "<option value=\"$c[catid]\" $sel $class>$c[catname]</option>";
        }
        $catoptions .= '</select>';
    } else {
        $catoptions = '';
        $key = array_search('category',$types);
        unset($types[$key]);
    }

    //================================================================
    // build the topic filter pulldown
    //================================================================
    if (in_array('topic', $types) && _SETTING_DISPLAY_TOPICS && _SETTING_ALLOW_CAT_FILTER) {
        @define('_PC_FORM_TOPIC', true);
        $topic = FormUtil::getPassedValue('pc_topic');
        $topics = pnModAPIFunc('PostCalendar', 'user', 'getTopics');
        $topoptions = "<select name=\"pc_topic\" $class>";
        $topoptions .= "<option value=\"\" $class>" . __('All Topics', $dom) . "</option>";
        foreach ($topics as $t) {
            $sel = ($topic == $t['topicid'] ? 'selected="selected"' : '');
            $topoptions .= "<option value=\"$t[topicid]\" $sel $class>$t[topictext]</option>";
        }
        $topoptions .= '</select>';
    } else {
        $topoptions = '';
        $key = array_search('topic',$types);
        unset($types[$key]);
    }


    if (!empty($types)) {
        //================================================================
        // build it in the correct order
        //================================================================
        $submit = "<input type=\"submit\" name=\"submit\" value=\"$label\" $class />";
        $orderArray = array('user' => $useroptions, 'category' => $catoptions, 'topic' => $topoptions, 'jump' => $submit);
    
        if (!is_null($order)) {
            $newOrder = array();
            $order = explode(',', $order);
            foreach ($order as $tmp_order) {
                array_push($newOrder, $orderArray[$tmp_order]);
            }
            foreach ($orderArray as $key => $old_order) {
                if (!in_array($old_order, $newOrder)) array_push($newOrder, $orderArray[$key]);
            }

            $order = $newOrder;
        } else {
            $order = $orderArray;
        }

        $ret_val = "";
        foreach ($order as $element) {
            $ret_val .= $element;
        }
        $ret_val .= "<br />";
    }

    if (!in_array('user', $types)) $ret_val .= "<input type='hidden' name='pc_username' value='$pc_username' />";

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
    } else {
        return $ret_val;
    }
}
