<?php

/**
 * Created by PhpStorm.
 * User: microb
 * Date: 22.11.2017
 * Time: 0:09
 */
class wmCalendar
{

    const EVENTS_SLUG = 'wmcalendar';
    const POST_TYPE = 'wmc_event';
    const SHORTCODE = 'wm_calendar';
    const dayNames = ['Пн.', 'Вт.', 'Ср.', 'Чт.', 'Пт.', 'Сб.', 'Вс.'];
    const monthNames = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];


    /**
     * Создаем свой тип записи
     */
    public static function createPostType() {
        $labels = array(
            'name'              => 'События',
            'singular_name'     => 'Собырие',
            'add_new'           => 'Добавить новое',
            'add_new_item'      => 'Добавить новое событие',
            'edit_item'         => 'Редактировать событие',
            'new_item'          => 'Новое событие',
            'all_items'         => 'Все события',
            'view_item'         => 'Просмотр события',
            'search_items'      => 'Искать события',
            'not_found'         => 'События не найдены',
            'parent_item_colon' => '',
            'menu_name'         => 'События'
        );

        $event_args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => self::EVENTS_SLUG, 'with_front' => true),
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_icon'          => 'dashicons-calendar',
            'supports'           => array('title', 'editor', 'thumbnail'),
        );
        register_post_type(self::POST_TYPE, $event_args);

        flush_rewrite_rules(false);
    }

    /**
     * @return string
     */
    public static function replaceShortcode() {

        $events = self::getEvents();

        $result = '<div class="wmc-inline-content">';
        $result .= self::renderCalendar($events);
        $result .= '</div>';

        return $result;
    }

    /**
     * Генерит HTML календаря
     *
     * @param array $events
     * @return string
     */
    public function renderCalendar(array $events) {
        $result = '';

        $caption = self::monthNames[date('m') - 1] . ' ' . date('Y');

        $result .= '<table cellpadding="0" cellspacing="0" class="wmc-calendar">';
        $result .= '<caption>' . $caption . '</caption>';
        $result .= '<thead><tr class="wmc-calendar-row">';
        foreach (self::dayNames as $d) {
            $result .= '<th class="wmc-calendar-day-head" scope="col">' . $d . '</th>';
        }
        $result .= '</tr></thead>';
        $result .= '<tbody>';

        // начальный день (изначально первый день месяца)
        $startDate = new DateTime(date('Y-m') . '-01');
        // если начальный день - не понедельник
        if ($startDate->format('N') > 1) {
            $startDate->modify("-{$startDate->format('N')} day");
        }
        // последний день в календаре
        $endDate = new DateTime(date('Y-m-t'));


        while ($startDate <= $endDate) {
            if ($startDate->format('N') == 1) {
                $result .= '<tr class="wmc-calendar-row">';
            }

            if ($startDate->format('m') == date('m')) {
                $day  = $startDate->format('d');
                $text = (isset($events[$day]) && !empty($events[$day])) ? sprintf('<a href="#" class="ajaxCbox" data-date="%s">%d</a>', $startDate->format('Y-m-d'), $day) : $day;
                $result .= '<td class="wmc-calendar-day"><div class="wmc-day-number">' . $text . '</div></td>';
            } else {
                $result .= '<td class="wmc-calendar-day-np"> </td>';
            }


            if ($startDate->format('N') == 7) {
                $result .= '</tr>';
            }
            $startDate->modify("+1 day");
        }

        $result .= '</tbody>';
        $result .= '</table>';

        return $result;
    }

    /**
     * Возвращает массив мероприятий на указаный день или текущий месяц
     *
     * @return array
     */
    public function getEvents($date = NULL) {
        // события по дням месяца
        $events = [];
        if (isset($date) && strtotime($date)) {
            $dateQuery = [
                'key'   => 'eventDate',
                'value' => date('Y-m-d', strtotime($date)),
            ];
        } else {
            $dateQuery = [
                'key'     => 'eventDate',
                'value'   => [date('Y-m') . '-01', date('Y-m-t')],
                'compare' => 'BETWEEN'
            ];
        }

        // Получаем события за этот месяц
        $args = array(
            'post_type'      => wmCalendar::POST_TYPE,
            'meta_query'     => array($dateQuery),
            'orderby'        => 'eventDate',
            'order'          => 'ASC',
            'posts_per_page' => 100
        );

        $query = new WP_Query($args);
        // The Loop
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $event = new stdClass();

                $event->title   = get_the_title();
                $event->date    = get_post_meta($query->post->ID, 'eventDate', true);
                $event->time    = get_post_meta($query->post->ID, 'eventTime', true);
                $event->address = get_post_meta($query->post->ID, 'eventAddress', true);
                $event->day     = DateTime::createFromFormat('Y-m-d', $event->date)->format('d');
                //$event->url     = get_permalink();

                if (!isset($events[$event->day])) {
                    $events[$event->day] = array();
                }
                array_push($events[$event->day], $event);
            }
        }
        wp_reset_postdata();

        return $events;
    }


    /**
     * Нужно ли подгружать стили
     *
     * @return bool
     */
    function isCalendarPage() {
        global $post;

        return (is_object($post) && strpos($post->post_content, '[' . self::SHORTCODE . ']') !== false) ? true : false;
    }

    /**
     *
     */
    function loadFrontendScripts() {

        if (self::isCalendarPage() || is_singular(self::POST_TYPE)) {
            wp_enqueue_style('wmc-style', plugins_url('assets/css/wm-calendar.css', __FILE__));

            wp_enqueue_script('wmc-colorbox', plugins_url('assets/js/jquery.colorbox-min.js', __FILE__), array('jquery'), '1.0', false);
            wp_enqueue_script('wmc-scripts', plugins_url('assets/js/wm-calendar.js', __FILE__), array('jquery','wmc-colorbox'), '1.0', false);
            wp_localize_script('wmc-scripts', 'wmc_vars', ['ajaxurl' => admin_url('admin-ajax.php')]);
        }
    }

    /**
     *
     */
    public static function ajaxLoadEvents() {
        if (isset( $_POST['wmc_date'] )) {
            $events = self::getEvents(sanitize_text_field($_POST['wmc_date']));
            $json = json_encode(array_shift($events), JSON_UNESCAPED_UNICODE);
            die($json);
        }
    }

}