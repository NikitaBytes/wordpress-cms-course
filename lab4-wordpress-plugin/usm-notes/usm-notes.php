<?php
/*
Plugin Name: USM Notes
Plugin URI: http://localhost:8080
Description: Учебный плагин для WordPress, добавляющий заметки, приоритеты и дату напоминания.
Version: 1.0
Author: Savca Nichita
Author URI: http://localhost:8080
*/

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация Custom Post Type: usm_note
 */
function usm_notes_register_cpt() {
    $labels = array(
        'name'               => 'Заметки',
        'singular_name'      => 'Заметка',
        'add_new'            => 'Добавить заметку',
        'add_new_item'       => 'Добавить новую заметку',
        'edit_item'          => 'Редактировать заметку',
        'new_item'           => 'Новая заметка',
        'view_item'          => 'Просмотреть заметку',
        'search_items'       => 'Искать заметки',
        'not_found'          => 'Заметки не найдены',
        'not_found_in_trash' => 'В корзине заметок нет',
        'menu_name'          => 'Заметки',
    );

    $args = array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-welcome-write-blog',
        'supports'      => array('title', 'editor', 'author', 'thumbnail'),
        'show_in_rest'  => true,
    );

    register_post_type('usm_note', $args);
}
add_action('init', 'usm_notes_register_cpt');

/**
 * Регистрация таксономии: usm_priority
 */
function usm_notes_register_taxonomy() {
    $labels = array(
        'name'              => 'Приоритеты',
        'singular_name'     => 'Приоритет',
        'search_items'      => 'Искать приоритеты',
        'all_items'         => 'Все приоритеты',
        'edit_item'         => 'Редактировать приоритет',
        'update_item'       => 'Обновить приоритет',
        'add_new_item'      => 'Добавить новый приоритет',
        'new_item_name'     => 'Название нового приоритета',
        'menu_name'         => 'Приоритет',
    );

    $args = array(
        'labels'        => $labels,
        'hierarchical'  => true,
        'public'        => true,
        'show_in_rest'  => true,
    );

    register_taxonomy('usm_priority', array('usm_note'), $args);
}
add_action('init', 'usm_notes_register_taxonomy');

/**
 * Добавление метабокса Due Date
 */
function usm_notes_add_meta_box() {
    add_meta_box(
        'usm_notes_due_date',
        'Дата напоминания',
        'usm_notes_due_date_callback',
        'usm_note',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'usm_notes_add_meta_box');

/**
 * Отображение поля даты
 */
function usm_notes_due_date_callback($post) {
    wp_nonce_field('usm_notes_save_due_date', 'usm_notes_due_date_nonce');
    $value = get_post_meta($post->ID, '_usm_due_date', true);

    echo '<label for="usm_due_date">Выберите дату:</label>';
    echo '<input type="date" id="usm_due_date" name="usm_due_date" value="' . esc_attr($value) . '" required style="width:100%;" />';
}

/**
 * Валидация и сохранение даты
 */
function usm_notes_save_due_date($post_id) {
    if (!isset($_POST['usm_notes_due_date_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['usm_notes_due_date_nonce'], 'usm_notes_save_due_date')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && $_POST['post_type'] !== 'usm_note') {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!isset($_POST['usm_due_date']) || empty($_POST['usm_due_date'])) {
        set_transient('usm_notes_error_' . $post_id, 'Дата напоминания обязательна для заполнения.', 30);
        return;
    }

    $due_date = sanitize_text_field($_POST['usm_due_date']);
    $today = date('Y-m-d');

    if ($due_date < $today) {
        set_transient('usm_notes_error_' . $post_id, 'Дата напоминания не может быть в прошлом.', 30);
        return;
    }

    update_post_meta($post_id, '_usm_due_date', $due_date);
}
add_action('save_post', 'usm_notes_save_due_date');

/**
 * Показ ошибки в админке
 */
function usm_notes_admin_error_notice() {
    global $post;

    if (!$post) {
        return;
    }

    $error = get_transient('usm_notes_error_' . $post->ID);

    if ($error) {
        echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        delete_transient('usm_notes_error_' . $post->ID);
    }
}
add_action('admin_notices', 'usm_notes_admin_error_notice');

/**
 * Добавление колонки Due Date в список заметок
 */
function usm_notes_add_due_date_column($columns) {
    $columns['usm_due_date'] = 'Дата напоминания';
    return $columns;
}
add_filter('manage_usm_note_posts_columns', 'usm_notes_add_due_date_column');

function usm_notes_render_due_date_column($column, $post_id) {
    if ($column === 'usm_due_date') {
        $value = get_post_meta($post_id, '_usm_due_date', true);
        echo $value ? esc_html($value) : '—';
    }
}
add_action('manage_usm_note_posts_custom_column', 'usm_notes_render_due_date_column', 10, 2);

/**
 * Шорткод [usm_notes priority="high" before_date="2026-04-30"]
 */
function usm_notes_shortcode($atts) {
    $atts = shortcode_atts(array(
        'priority'    => '',
        'before_date' => '',
    ), $atts, 'usm_notes');

    $meta_query = array();
    $tax_query = array();

    if (!empty($atts['before_date'])) {
        $meta_query[] = array(
            'key'     => '_usm_due_date',
            'value'   => sanitize_text_field($atts['before_date']),
            'compare' => '<=',
            'type'    => 'DATE',
        );
    }

    if (!empty($atts['priority'])) {
        $tax_query[] = array(
            'taxonomy' => 'usm_priority',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($atts['priority']),
        );
    }

    $args = array(
        'post_type'      => 'usm_note',
        'posts_per_page' => -1,
        'meta_query'     => $meta_query,
        'tax_query'      => $tax_query,
    );

    $query = new WP_Query($args);

    ob_start();

    echo '<div class="usm-notes-list">';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $due_date = get_post_meta(get_the_ID(), '_usm_due_date', true);
            $terms = get_the_terms(get_the_ID(), 'usm_priority');
            $priority = $terms && !is_wp_error($terms) ? $terms[0]->name : 'Без приоритета';

            echo '<div class="usm-note-item">';
            echo '<h3>' . esc_html(get_the_title()) . '</h3>';
            echo '<p>' . esc_html(get_the_excerpt()) . '</p>';
            echo '<p><strong>Приоритет:</strong> ' . esc_html($priority) . '</p>';
            echo '<p><strong>Дата напоминания:</strong> ' . esc_html($due_date) . '</p>';
            echo '</div>';
        }
    } else {
        echo '<p>Нет заметок с заданными параметрами.</p>';
    }

    echo '</div>';

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('usm_notes', 'usm_notes_shortcode');

/**
 * Стили для шорткода
 */
function usm_notes_inline_styles() {
    echo '<style>
        .usm-notes-list {
            display: grid;
            gap: 16px;
            margin: 24px 0;
        }
        .usm-note-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .usm-note-item h3 {
            margin-top: 0;
            margin-bottom: 8px;
        }
        .usm-note-item p {
            margin: 6px 0;
        }
    </style>';
}
add_action('wp_head', 'usm_notes_inline_styles');

/**
 * Flush rewrite rules при активации/деактивации
 */
function usm_notes_activate_plugin() {
    usm_notes_register_cpt();
    usm_notes_register_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'usm_notes_activate_plugin');

function usm_notes_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'usm_notes_deactivate_plugin');

