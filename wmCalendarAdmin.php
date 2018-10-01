<?php

/**
 * Created by PhpStorm.
 * User: microb
 * Date: 22.11.2017
 * Time: 1:15
 */
class wmCalendarAdmin
{

    /**
     * Добавляет метабокс с информацие о событии
     *
     * @param $post_type
     */
    public static function addMetabox($post_type) {

        add_meta_box(
            'wmc_metabox'
            , 'Информация о событии'
            , array(__CLASS__, 'renderMetaboxContent')
            , wmCalendar::POST_TYPE
            , 'normal'
            , 'high'
        );
    }

    /**
     * Генерит HTML для метабокса
     *
     * @param $post
     */
    public static function renderMetaboxContent($post) {
        // Добавляем nonce
        wp_nonce_field(wmCalendar::POST_TYPE . '_edit', wmCalendar::POST_TYPE . '_nonce');

        // Подключаем datepicker
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);

        ?>
        <table>
            <tr>
                <td>Дата мероприятия:</td>
                <td><input type="text" name="EventDate" id="EventDate" value="<?php echo get_post_meta($post->ID, 'eventDate', true); ?>" size="10" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder="yyyy-mm-dd" required/></td>
            </tr>
            <tr>
                <td>Время:</td>
                <td><input type="time" name="EventTime" id="EventTime" value="<?php echo get_post_meta($post->ID, 'eventTime', true); ?>" size="5" pattern="[0-9]{2}:[0-9]{2}" placeholder="hh:mm"/></td>
            </tr>
            <tr>
                <td>Адрес:</td>
                <td><input type="text" name="EventAddress" id="EventAddress" value="<?php echo get_post_meta($post->ID, 'eventAddress', true); ?>" size="50"/></td>
            </tr>
            <tr>
                <td colspan="2"><?php
                    if (!empty($_SESSION['wmc_metabox_errors'])) {
                        echo '<div style="color: red;">' . $_SESSION['wmc_metabox_errors'] . '</div>';
                        $_SESSION['wmc_metabox_errors'] = '';
                    } else if (!empty($_SESSION['wmc_metabox_success'])) {
                        echo '<div style="color: green;">' . $_SESSION['wmc_metabox_success'] . '</div>';
                        $_SESSION['wmc_metabox_success'] = '';
                    }
                    ?></td>
            </tr>
        </table>

        <script>
            jQuery(document).ready(function () {
                if (jQuery.fn.datepicker) {
                    jQuery('#EventDate').datepicker({
                        dateFormat: 'yy-mm-dd',
                    });
                }
            });
        </script>
        <?php
    }

    /**
     * Сохраняет данные о событии при сохранении поста
     *
     * @param $post_id
     * @return mixed
     */
    public static function saveMetaboxContent($post_id) {
        global $post;

        $_SESSION['wmc_metabox_errors']  = '';
        $_SESSION['wmc_metabox_success'] = '';

        // проверяем nonce
        if ( empty($_POST) || !wp_verify_nonce($_POST[wmCalendar::POST_TYPE . '_nonce'], wmCalendar::POST_TYPE . '_edit') ) return $post_id;

        // игнорми автосейв
        if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) return $post_id;

        // проверяем доступ
        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        // Чистим user input
        $eventDate    = sanitize_text_field($_POST['EventDate']);
        $eventTime    = sanitize_text_field($_POST['EventTime']);
        $eventTime    = empty($eventTime) ? '00:00' : $eventTime;
        $eventAddress = sanitize_text_field($_POST['EventAddress']);

        if (empty($eventDate)) {
            $_SESSION['wmc_metabox_errors'] = '<p>Ошибка! Поле "Дата" обязательно для заполнения.</p>';
        }

        if (!empty($_SESSION['wmc_metabox_errors'])) {
            return $post_id;
        }

        // сохраняем данные
        update_post_meta($post_id, 'eventDate', $eventDate);
        update_post_meta($post_id, 'eventTime', $eventTime);
        update_post_meta($post_id, 'eventAddress', $eventAddress);

        $_SESSION['wmc_metabox_success'] = '<p>Событие успешно сохранено!</p>';
        $_SESSION['wmc_metabox_errors']  = '';
    }

    /**
     * Добавляем колонки "дата мероприятия" и "время"
     *
     * @param $columns
     * @return array
     */
    public static function addDateColumn($columns) {
        $new_columns = array(
            wmCalendar::POST_TYPE . '_date' => 'Дата мероприятия',
            wmCalendar::POST_TYPE . '_time' => 'Время',
            'date'                          => $columns['date'],
        );
        unset($columns['date']);

        return array_merge($columns, $new_columns);
    }

    /**
     * Выводим значения для колонок "дата мероприятия" и "время"
     *
     * @param $column
     * @param $post_id
     */
    public static function fillDateColumn($column, $post_id) {

        switch ($column) {
            case wmCalendar::POST_TYPE . '_date' :
                echo get_post_meta($post_id, 'eventDate', true);
                break;

            case wmCalendar::POST_TYPE . '_time' :
                echo get_post_meta($post_id, 'eventTime', true);
                break;
        }

    }

    /**
     * Добавляем сортировку по дате
     *
     * @param $columns
     * @return mixed
     */
    public static function sortableDateColumn($columns) {
        $columns[wmCalendar::POST_TYPE . '_date'] = wmCalendar::POST_TYPE . '_date';

        return $columns;
    }




}