<?php
/*
Plugin Name: Cooll Delete Option & Transient Manager
Description: Search and delete WordPress options or transients safely, and clean expired transients.
Version: 1.0
Author: leonidas.tsaras@gmail.com
*/

if ( ! defined('ABSPATH') ) exit;

// Add admin page
add_action('admin_menu', function() {
    add_management_page(
        'Delete Option Tool',
        'Cooll Delete Option',
        'manage_options',
        'delete-option-tool',
        'dot_render_admin_page'
    );
});

function dot_render_admin_page() {
    global $wpdb;

    if ( ! current_user_can('manage_options') ) return;

    $message = '';
    $options = [];

    // Handle deletion of selected options/transients
    if ( isset($_POST['dot_delete_selected']) && ! empty($_POST['dot_option_names']) && check_admin_referer('dot_delete_options_action', 'dot_nonce_field') ) {
        $deleted = 0;
        foreach ( $_POST['dot_option_names'] as $opt ) {
            $opt = sanitize_text_field($opt);
            if ( strpos($opt, '_transient_') === 0 ) {
                $transient_name = str_replace(['_transient_', '_transient_timeout_'], '', $opt);
                delete_transient($transient_name);
                $deleted++;
            } elseif ( delete_option($opt) ) {
                $deleted++;
            }
        }
        $message = "<div class='updated'><p>✅ Deleted {$deleted} option(s)/transient(s) successfully.</p></div>";
    }

    // Handle "delete all expired transients"
    if ( isset($_POST['dot_delete_expired_transients']) && check_admin_referer('dot_delete_expired_action', 'dot_expired_nonce') ) {
        $now = time();
        $expired = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                $now
            )
        );

        $count = 0;
        foreach ( $expired as $row ) {
            $transient_name = str_replace('_transient_timeout_', '', $row->option_name);
            delete_transient($transient_name);
            $count++;
        }
        $message = "<div class='updated'><p>🧹 Deleted {$count} expired transient(s).</p></div>";
    }

    // Handle search
    if ( isset($_POST['dot_search_keyword']) && check_admin_referer('dot_search_options_action', 'dot_search_nonce') ) {
        $keyword = sanitize_text_field( $_POST['dot_search_keyword'] );

        $like = '%' . $wpdb->esc_like( $keyword ) . '%';
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 200",
            $like
        ) );

        $options = $results;
        if ( empty($results) ) {
            $message = "<div class='notice notice-warning'><p>⚠️ No options or transients found matching '<strong>{$keyword}</strong>'.</p></div>";
        }
    }
    ?>

    <div class="wrap">
        <h1>Delete Option & Transient Manager</h1>
        <p>Use this tool to search and delete <strong>options</strong> or <strong>transients</strong> from the database.<br>
        You can also remove all <strong>expired transients</strong> in one click.</p>

        <?php echo $message; ?>

        <!-- Search Form -->
        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('dot_search_options_action', 'dot_search_nonce'); ?>
            <input type="text" name="dot_search_keyword" placeholder="Search keyword (e.g. sale, cache, transient)" value="<?php echo isset($keyword) ? esc_attr($keyword) : ''; ?>" required>
            <?php submit_button('Search', 'secondary', '', false); ?>
        </form>

        <!-- Delete Expired Transients -->
        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('dot_delete_expired_action', 'dot_expired_nonce'); ?>
            <?php submit_button('🧹 Delete All Expired Transients', 'secondary', 'dot_delete_expired_transients'); ?>
        </form>

        <?php if ( ! empty($options) ) : ?>
            <form method="post">
                <?php wp_nonce_field('dot_delete_options_action', 'dot_nonce_field'); ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="dot_check_all"></th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Value (truncated)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $options as $opt ) :
                            $is_transient = (strpos($opt->option_name, '_transient_') === 0);
                            ?>
                            <tr>
                                <td><input type="checkbox" name="dot_option_names[]" value="<?php echo esc_attr($opt->option_name); ?>"></td>
                                <td><?php echo $is_transient ? '<span style="color:#2271b1;">Transient</span>' : 'Option'; ?></td>
                                <td><code><?php echo esc_html($opt->option_name); ?></code></td>
                                <td><code><?php echo esc_html(mb_strimwidth($opt->option_value, 0, 100, '...')); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
                <?php submit_button('Delete Selected', 'delete', 'dot_delete_selected'); ?>
            </form>

            <script>
                document.getElementById('dot_check_all').addEventListener('change', function(e) {
                    document.querySelectorAll('input[name="dot_option_names[]"]').forEach(cb => cb.checked = e.target.checked);
                });
            </script>
        <?php endif; ?>
    </div>
    <?php
}
