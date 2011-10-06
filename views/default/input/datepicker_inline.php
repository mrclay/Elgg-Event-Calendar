<?php

/**
 * JQuery data picker(inline version)
 * 
 * @package event_calendar
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Kevin Jardine <kevin@radagast.biz>
 * @copyright Radagast Solutions 2008 -2011
 * @link http://radagast.biz/
 * 
 */

// make sure days have leading zero
$vars['start_date'] = preg_replace('/(\\d{4}\\-\\d{2}\\-)(\\d)$/', '${1}0$2', $vars['start_date']);
$vars['end_date']   = preg_replace('/(\\d{4}\\-\\d{2}\\-)(\\d)$/', '${1}0$2', $vars['end_date']);

if ($vars['group_guid']) {
	$link_bit = $vars['url']."event_calendar/group/{$vars['group_guid']}/%s/{$vars['mode']}";
} else {
	$link_bit = $vars['url']."event_calendar/list/%s/{$vars['mode']}/{$vars['filter']}";
}

if ($vars['mode'] == 'week') {
	$selected_week = date('W', strtotime($vars['start_date'].' 13:00 UTC'))+1;
} else {
	$selected_week = '';
}

if ($vars['mode']) {
	$wrapper_class = "event-calendar-filter-period-".$vars['mode'];
} else {
	$wrapper_class = "event-calendar-filter-period-month";
}
// TODO - figure out how to move this JavaScript
?>

<script type="text/javascript">
$(function(){
    var selected_week = "<?php echo $selected_week; ?>",
        mode = "<?php echo $vars['mode'] ?>",
        // all Dates here are local time.
        start_date = $.datepicker.parseDate('yy-mm-dd', '<?php echo $vars['start_date']; ?>'),
        end_date = $.datepicker.parseDate('yy-mm-dd', '<?php echo $vars['end_date']; ?>'),
        url_date = start_date,
        url_date_pattern = /\/(\d{4})\-(\d\d?)\-(\d\d?)\//,
        done_loading = false,
        // cached to speed up getRenderingSpec
        start_year = start_date.getYear(),
        start_month = start_date.getMonth(),
        start_day_of_month = start_date.getDate(),
        m;

    if (m = location.href.match(url_date_pattern)) {
        url_date = new Date(m[1], m[2].replace(/^0/, '') - 1, m[3].replace(/^0/, ''));
    }

    /**
     * Start loading new page and show ajax loader
     * @param String href
     */
    function go(href) {
        location.href = href;
        // the table is recreated just after this function ends, so we need to
        // start a little later
        setTimeout(function () {
            $('#my_datepicker table').fadeTo(0, .2).parent().append('<div class="event-calendar-loading" />');
        }, 50);
    }

    /**
     * Get the spec for rendering a date on the calendar
     * @param Date date midnight local time (generated by widget)
     * @return Array [(bool) isSelectable, (string) cssClass]
     */
    function getRenderingSpec(date) {
        var highlighted = [true, 'day-highlight'];
        if (mode === 'month') {
            if (date.getYear() === start_year && date.getMonth() === start_month) {
                return highlighted;
            }
        } else if (mode === 'week') {
            var week_number = $.datepicker.iso8601Week(date);
            // Move Sundays into the next week. Note: must use getDay() because date is in local time
            if (date.getDay() == 0) {
                week_number += 1;
            }
            if (selected_week == week_number) {
                return highlighted;
            }
        } else {
            // day
            if (date.getYear() === start_year && date.getMonth() === start_month && date.getDate() === start_day_of_month) {
                return highlighted;
            }
        }
        return [true, ''];
    }

    /**
     * Called when user clicks 'Prev' or 'Next'
     * @param year 4 digit year
     * @param month (Jan = 1)
     * @param inst datepicker instance
     */
    function handlePrevNext(year, month, inst) {
        // The datepicker calls this while it loads, we only worry about after it loads
        if (done_loading) {
            var firstOfMonth = (year+'-'+month+'-01').replace(/\-(\d)\-/, '-0$1-'); // Y-m-d
            if (mode === 'month') {
                if (url_date_pattern.test(location.href)) {
                    go(location.href.replace(url_date_pattern, '/'+firstOfMonth+'/'));
                } else {
                    var noDatePattern = /(event_calendar\/(group\/\d+|list)\b).*/;
                    if (noDatePattern.test(location.href)) {
                        go(location.href.replace(noDatePattern, '$1/'+firstOfMonth+'/month'));
                    } else {
                        // Fallback replacement. This one loses filters so we try to avoid
                        go("<?php echo $link_bit; ?>".replace('%s', firstOfMonth));
                    }
                }
            } else {
                // if mode is changed to month, we want to select the month displayed
                $('#calendarmenu .sys_calmenu_last. a').each(function () {
                    this.href = this.href.replace(url_date_pattern, '/'+firstOfMonth+'/');
                });
            }
        }
    }

    $("#<?php echo $vars['name']; ?>").datepicker({
        onChangeMonthYear: handlePrevNext,
        onSelect: function(date) {
            // jump to the new page
            var href = "<?php echo $link_bit; ?>".replace('%s', date.substring(0,10));
            if (mode === 'month') {
                // switch to day view
                href = href.replace(/\/month\//, '/day/');
            }
            location.href = href;
        },
        dateFormat: "yy-mm-dd",
        <?php echo $vars['range_bit']; ?>

        // even if displaying a week that started last month,
        // make the date the user actually clicked visible
        defaultDate: url_date,
        beforeShowDay: getRenderingSpec
    });

    //$("#<?php echo $vars['name']; ?>").datepicker("setDate", start_date, end_date);
    done_loading = true;
});

</script>
<div style="position:relative;" id="<?php echo $vars['name']; ?>" class="<?php echo $wrapper_class; ?>" ></div>
<p style="clear: both;"><!-- See day-by-day example for highlighting days code --></p>