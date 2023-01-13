<?php

if (!function_exists("ts_ticket_define_report_page")) {

    add_action('admin_menu', 'ts_ticket_define_report_page');

    /**
     * define report page.
     */
    function ts_ticket_define_report_page()
    {
        add_submenu_page(
            "edit.php?post_type=ts_ticket",
            "گزارش ها",
            'گزارش ها',
            // only administrator can see
            'activate_plugins',
            'reports.php',
            'ts_display_report_page'
        );
    }
}

if (!function_exists("ts_display_report_page")) {

    function ts_display_report_page()
    {
        $date = ts_get_date_from_dropdown($_GET['m'] ?? null);
        global $wpdb;
        $this_month_tickets = ts_report_tickets_last_month_per_day($date['year'], $date['month'], $date['day']);

        $tickets_by_type = ts_get_tickets_by_tax_count($date['year'], $date['month'], $date['day']);
        $tickets_by_type_per_day = ts_get_tickets_tax_count_per_day($date['year'], $date['month'], $date['day']);

        $tickets_by_type_per_day_keys = array_keys($tickets_by_type_per_day[0]);

        $tickets_by_type_per_day_days = $tickets_by_type_per_day[1];
        $ticket_terms = get_terms("ticket_type");
        require_once TS_VIEW . "reports.php";
    }
}

if (!function_exists("ts_report_scripts_enqueue")) {
    add_action("admin_enqueue_scripts", "ts_report_scripts_enqueue");

    /**
     * enqueue and add assets that need in report page
     */
    function ts_report_scripts_enqueue()
    {
        // only fire on admin screen
        if (!is_admin()) {
            return false;
        }

        // only fire on reports page
        if (isset($_GET['page']) && $_GET['page'] != "reports.php") {
            return false;
        }
        wp_enqueue_style("print", TS_CSS . "print.min.css");
        // wp_enqueue_script ( "print", TS_JS . "print.min.js" );
        wp_enqueue_style("chart", TS_CSS . "chart.min.css");
        wp_enqueue_script("chart", TS_JS . "chart.min.js");
    }
}

if (!function_exists("ts_report_tickets_last_month_per_day")) {

    /**
     * return count of ticket per day
     * @param int $year
     * @param int $month
     * @param int $day
     * @return array of date and count
     */
    function ts_report_tickets_last_month_per_day($year = 0, $month = 0, $day = 1)
    {
        global $wpdb;

        $query = 'select count(*) as "count", DATE_FORMAT(post_date, "%Y-%m-%d") as "day" from ' . $wpdb->prefix . 'posts where `post_type` = "ts_ticket" AND `post_status` = "publish" ';

        $query = ts_add_date_restrict($query, $year, $month, $day);

        // set group
        $query .= ' group by DATE_FORMAT(post_date, "%Y-%m-%d")';

        $res = $wpdb->get_results($query);

        $result = array();
        // set results to date-count couples
        foreach ($res as $r) {
            $date = ts_jdate_convert($r->day);
            $result[$date] = $r->count;
        }

        return $result;
    }
}

if (!function_exists("ts_get_tickets_by_tax_count")) {
    /**
     * return count of type(category) of tickets
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return array $result.
     */
    function ts_get_tickets_by_tax_count($year = 0, $month = 0, $day = 1)
    {
        global $wpdb;

        $query = "select count(*) as 'count', `{$wpdb->prefix}terms`.`name` from {$wpdb->prefix}term_taxonomy
                    INNER JOIN `{$wpdb->prefix}terms` on `{$wpdb->prefix}terms`.`term_id` = `{$wpdb->prefix}term_taxonomy`.`term_id`
                    INNER JOIN `{$wpdb->prefix}term_relationships` on `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` = `{$wpdb->prefix}term_taxonomy`.`term_taxonomy_id`
                    INNER JOIN `{$wpdb->prefix}posts` on `{$wpdb->prefix}posts`.`ID` = `{$wpdb->prefix}term_relationships`.`object_id`
                    where `taxonomy`= 'ticket_type' and `{$wpdb->prefix}posts`.`post_status` = 'publish'";

        $query = ts_add_date_restrict($query, $year, $month, $day);


        // set group
        $query .= "GROUP BY {$wpdb->prefix}terms.name";


        $results = $wpdb->get_results($query);
        $result = array();

        foreach ($results as $r) {
            $result[$r->name] = $r->count;
        }

        return $result;
    }
}

if (!function_exists("ts_get_tickets_tax_count_per_day")) :

    /**
     * return count of type(category) of tickets per day
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return array.
     */
    function ts_get_tickets_tax_count_per_day($year = 0, $month = 0, $day = 1)
    {
        global $wpdb;
        $query = "select count({$wpdb->prefix}posts.ID) as 'count', `{$wpdb->prefix}terms`.`name`, `{$wpdb->prefix}terms`.`term_id`, DATE_FORMAT(post_date, '%Y-%m-%d') as 'date' from {$wpdb->prefix}term_taxonomy
                INNER JOIN `{$wpdb->prefix}terms` on `{$wpdb->prefix}terms`.`term_id` = `{$wpdb->prefix}term_taxonomy`.`term_id`
                INNER JOIN `{$wpdb->prefix}term_relationships` on `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` = `{$wpdb->prefix}term_taxonomy`.`term_taxonomy_id`
                INNER JOIN `{$wpdb->prefix}posts` on `{$wpdb->prefix}posts`.`ID` = `{$wpdb->prefix}term_relationships`.`object_id`
                where `taxonomy`= 'ticket_type' and `{$wpdb->prefix}posts`.`post_status` = 'publish'";

        $query = ts_add_date_restrict($query, $year, $month, $day);

        $query .= "group by DATE_FORMAT(post_date, '%Y-%m-%d'), {$wpdb->prefix}terms.name order by `{$wpdb->prefix}terms`.`term_id` ";


        $results = $wpdb->get_results($query);

        $result = array();
        $days = array();
        foreach ($results as $res) {
            $days[] = $res->date;
            $result[$res->term_id][$res->date] = [$res->name, $res->count];
        }

        // remove duplicated days
        $days = array_unique($days);

        // sort days
        sort($days);


        // try to set null values to 0 and convert date
        $term_ids = array_keys($result);
        $type_stat = [];
        foreach ($term_ids as $t) {
            foreach ($days as $tday) {
                if (!isset($result[$t][$tday])) {
                    $result[$t][$tday] = [get_term($t)->name, 0];
                }
                $date = ts_jdate_convert($tday);
                $type_stat[$t][$date] = $result[$t][$tday];
            }
        }

        // map the jdate
        $days = array_map("ts_jdate_convert", $days);

        return array($type_stat, $days);
    }
endif;

if (!function_exists("ts_jdate_convert")) :

    function ts_jdate_convert($date)
    {

        if (!is_plugin_active("wp-persian/wp-persian.php")) {
            return $date;
        }

        return date_i18n(get_option('date_format'), $date);
    }

endif;
if (!function_exists("ts_add_date_restrict")) :

    /**
     * add date restrict to existing query.
     *
     * @param int $year . has this year by default
     * @param int $month . has this month by default
     * @param int $day . has today by default
     *
     * @return string $query.
     */
    function ts_add_date_restrict($query, $year = 0, $month = 0, $day = 1)
    {

        if ($year == 0 && $month == 0) {
            $date_from = date('Y-m-d', strtotime("-1 month"));
        } else {
            $year = $year > 0 ? $year : date("Y");
            $lastmonth = mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"));
            $month = $month > 0 ? $month : date("m", $lastmonth);
            $date_from = $year . '-' . $month . '-' . $day;
        }
        
        if ($day <= 0) {
            $day = date("j");
        }

        $date_to = date_create($date_from . " +1 month +1 day")->format("Y-m-d");
        // set date
        $query .= ' AND `post_date` BETWEEN "' . $date_from . '" AND "' . $date_to . '" ';
        
        return $query;
    }
endif;


add_action("ts_restrict_reports", "ts_display_months_dropdown");
/**
 * display months dropdown
 */
function ts_display_months_dropdown()
{
    global $post_type;
    $post_type = "ts_ticket";
    echo "فیلتر کردن بر اساس تاریخ:";

    // check if wp-persian exist use persian date
    if (class_exists("WPP_Hooks")) {
        WPP_Hooks::wpp_restrict_manage_posts();
    } else {
        ts_display_wp_defalt_months_dropdown($post_type);
    }

    submit_button(__('Filter'), '', 'filter_action', false, array('id' => 'report-query-submit'));
}

/**
 * create wp drop down selector for months
 * @param $post_type
 * @return string. html select element
 */
function ts_display_wp_defalt_months_dropdown($post_type)
{
    global $wpdb, $wp_locale;

    $extra_checks = "AND post_status != 'auto-draft'";

    $months = $wpdb->get_results(
        $wpdb->prepare(
            "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			$extra_checks
			ORDER BY post_date DESC
		",
            $post_type
        )
    );

    $month_count = count($months);

    if (!$month_count || (1 == $month_count && 0 == $months[0]->month)) {
        return;
    }

    $m = isset($_GET['m']) ? (int)$_GET['m'] : 0;
    $data =
        "<label for=\"filter-by-date\" class=\"screen-reader-text\"><?php _e( 'Filter by date' ); ?></label>
    <select name=\"m\" id=\"filter-by-date\">" .
        "<option " . selected($m, 0, false) . " value=\"0\">" . __('All dates') . "</option>";
    foreach ($months as $arc_row) {
        if (0 == $arc_row->year) {
            continue;
        }
        $month = zeroise($arc_row->month, 2);
        $year = $arc_row->year;
        $data .= sprintf(
            "<option %s value='%s'>%s</option>\n",
            selected($m, $year . $month, false),
            esc_attr($arc_row->year . $month),
            /* translators: 1: month name, 2: 4-digit year */
            sprintf(__('%1$s %2$d'), $wp_locale->get_month($month), $year)
        );
    }
    $data .= "</select>";
    echo $data;
}

function ts_get_date_from_dropdown($dropdown_val)
{
    $date['year'] = $date['month'] = $date['day'] = 0;
    if (!isset($dropdown_val) || $dropdown_val < 1000) {
        return $date;
    }

    $m = $dropdown_val;
    $date = ts_parse_date_from_m_($m);

    if ($date['year'] < 1800 && function_exists("wpp_jalali_to_gregorian")) {
        // jalali date
        $jdate = wpp_jalali_to_gregorian($date['year'], $date['month'], $date['day'], '-');
        $date['year'] = $jdate[0];
        $date['month'] = $jdate[1];
        $date['day'] = $jdate[2];
    }

    return $date;
}

/**
 * parse m that was get from drop down menu
 * @param $m
 * @return array that have year, month and day
 */
function ts_parse_date_from_m_($m)
{
    $date = ["year" => 0, "month" => 0, "day" => 1];
    // The "m" parameter is meant for months but accepts date times of varying specificity
    if ($m) {
        $date['year'] = substr($m, 0, 4);
        if (strlen($m) > 5) {
            $date['month'] = substr($m, 4, 2);
        }
        if (strlen($m) > 7) {
            $date['day'] = substr($m, 6, 2);
        }
        if (strlen($m) > 9) {
            $date['hour'] = substr($m, 8, 2);
        }
        if (strlen($m) > 11) {
            $date['minute'] = substr($m, 10, 2);
        }
        if (strlen($m) > 13) {
            $date['second'] = substr($m, 12, 2);
        }
    }

    return $date;
}
