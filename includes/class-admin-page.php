<?php
/**
 * AI Navigator Hub - Admin Import Page
 *
 * @package AI_Navigator_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 添加后台管理菜单
 */
function ai_navigator_add_admin_menu() {
    // 主菜单
    add_menu_page(
        __( '大海导航', 'dh-nav' ),
        __( '大海导航', 'dh-nav' ),
        'edit_posts',
        'dh-nav',
        'ai_navigator_admin_page',
        'dashicons-admin-tools',
        30
    );

    // 子菜单：导入数据
    add_submenu_page(
        'dh-nav',
        __( '导入演示数据', 'dh-nav' ),
        __( '导入数据', 'dh-nav' ),
        'edit_posts',
        'dh-nav',
        'ai_navigator_admin_page'
    );

    // 子菜单：网站列表
    add_submenu_page(
        'dh-nav',
        __( '所有网站', 'dh-nav' ),
        __( '网站列表', 'dh-nav' ),
        'edit_posts',
        'edit.php?post_type=ai_tool'
    );

    // 子菜单：添加新网站
    add_submenu_page(
        'dh-nav',
        __( '添加新网站', 'dh-nav' ),
        __( '添加新网站', 'dh-nav' ),
        'edit_posts',
        'post-new.php?post_type=ai_tool'
    );

    // 子菜单：网站分类
    add_submenu_page(
        'dh-nav',
        __( '网站分类', 'dh-nav' ),
        __( '网站分类', 'dh-nav' ),
        'edit_posts',
        'ai-navigator-categories',
        'ai_navigator_categories_page'
    );

    // 子菜单：网站标签
    add_submenu_page(
        'dh-nav',
        __( '网站标签', 'dh-nav' ),
        __( '网站标签', 'dh-nav' ),
        'edit_posts',
        'ai-navigator-tags',
        'ai_navigator_tags_page'
    );

    // 子菜单：待审核申请
    $pending_count = wp_count_posts('ai_tool')->pending ?? 0;
    $pending_label = $pending_count > 0 ? sprintf(__('待审核 (%d)', 'dh-nav'), $pending_count) : __('待审核', 'dh-nav');
    add_submenu_page(
        'dh-nav',
        __( '待审核申请', 'dh-nav' ),
        $pending_label,
        'edit_posts',
        'ai-navigator-pending',
        'ai_navigator_pending_page'
    );

    // 子菜单：网站设置
    add_submenu_page(
        'dh-nav',
        __( '网站设置', 'dh-nav' ),
        __( '网站设置', 'dh-nav' ),
        'manage_options',
        'ai-navigator-settings',
        'ai_navigator_settings_page'
    );
}
add_action( 'admin_menu', 'ai_navigator_add_admin_menu' );

/**
 * 注册设置
 */
function ai_navigator_admin_init() {
    register_setting( 'ai_navigator_settings', 'ai_navigator_installed' );
    register_setting( 'ai_navigator_settings', 'ai_navigator_click_action' );
}
add_action( 'admin_init', 'ai_navigator_admin_init' );

/**
 * 网站分类管理页面 - 跳转到原生分类管理页面
 */
function ai_navigator_categories_page() {
    echo '<script>window.location.href = "' . admin_url('edit-tags.php?taxonomy=ai_category&post_type=ai_tool') . '";</script>';
    echo '<noscript><div class="notice notice-info"><p>正在跳转到分类管理页面...</p></div></noscript>';
}

/**
 * 网站标签管理页面 - 跳转到原生标签管理页面
 */
function ai_navigator_tags_page() {
    echo '<script>window.location.href = "' . admin_url('edit-tags.php?taxonomy=ai_tag&post_type=ai_tool') . '";</script>';
    echo '<noscript><div class="notice notice-info"><p>正在跳转到标签管理页面...</p></div></noscript>';
}

/**
 * 后台管理页面
 */
function ai_navigator_admin_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    $message = '';
    $message_type = '';

    // 处理导入请求
    if ( isset( $_POST['ai_navigator_import_nonce'] ) && wp_verify_nonce( $_POST['ai_navigator_import_nonce'], 'ai_navigator_import' ) ) {
        require_once get_template_directory() . '/includes/class-ai-tool-post-type.php';
        ai_navigator_create_sample_data();
        delete_option('dh_nav_data_cleared');
        $message = '演示数据导入成功！';
        $message_type = 'success';
    }

    // 处理清空请求
    if ( isset( $_POST['ai_navigator_clear_nonce'] ) && wp_verify_nonce( $_POST['ai_navigator_clear_nonce'], 'ai_navigator_clear' ) ) {
        ai_navigator_delete_all_data();
        $message = '所有数据已清空。';
        $message_type = 'success';
    }

    // 处理点击行为设置
    if ( isset( $_POST['ai_navigator_click_action_nonce'] ) && wp_verify_nonce( $_POST['ai_navigator_click_action_nonce'], 'ai_navigator_click_action' ) ) {
        $click_action = isset( $_POST['click_action'] ) ? sanitize_text_field( $_POST['click_action'] ) : 'modal';
        update_option( 'ai_navigator_click_action', $click_action );
        $message = '点击行为设置已保存！';
        $message_type = 'success';
    }

    $stats = ai_navigator_get_stats();
    ?>
    <div class="wrap ai-navigator-admin">
        <h1>大海导航管理</h1>
        
        <?php if ( $message ) : ?>
            <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        <?php endif; ?>

        <div class="ai-navigator-stats">
            <div class="stat-box"><span class="stat-number"><?php echo $stats['tools']; ?></span><span class="stat-label">网站</span></div>
            <div class="stat-box"><span class="stat-number"><?php echo $stats['categories']; ?></span><span class="stat-label">分类</span></div>
            <div class="stat-box"><span class="stat-number"><?php echo $stats['tags']; ?></span><span class="stat-label">标签</span></div>
        </div>

        <div class="ai-navigator-actions">
            <div class="action-card">
                <h2>⚙️ 点击行为设置</h2>
                <p>设置用户点击网站卡片时的行为</p>
                <form method="post">
                    <?php wp_nonce_field( 'ai_navigator_click_action', 'ai_navigator_click_action_nonce' ); ?>
                    <p>
                        <label style="display:flex;align-items:center;gap:8px;margin:8px 0;cursor:pointer;">
                            <input type="radio" name="click_action" value="modal" <?php checked( get_option( 'ai_navigator_click_action', 'modal' ), 'modal' ); ?>>
                            <span>弹窗模式</span>
                            <span style="color:#646970;font-size:13px;">— 点击卡片弹出详情弹窗</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;margin:8px 0;cursor:pointer;">
                            <input type="radio" name="click_action" value="detail" <?php checked( get_option( 'ai_navigator_click_action', 'modal' ), 'detail' ); ?>>
                            <span>详情页模式</span>
                            <span style="color:#646970;font-size:13px;">— 点击卡片跳转到网站详情页</span>
                        </label>
                    </p>
                    <button type="submit" class="button button-primary">保存设置</button>
                </form>
            </div>

            <div class="action-card">
                <h2>📥 导入演示数据</h2>
                <p>一键导入示例网站数据</p>
                <form method="post">
                    <?php wp_nonce_field( 'ai_navigator_import', 'ai_navigator_import_nonce' ); ?>
                    <button type="submit" class="button button-primary">立即导入</button>
                </form>
            </div>

            <div class="action-card">
                <h2>🗑️ 清空所有数据</h2>
                <p>删除所有网站、分类和标签</p>
                <form method="post" onsubmit="return confirm('确定要清空所有数据吗？');">
                    <?php wp_nonce_field( 'ai_navigator_clear', 'ai_navigator_clear_nonce' ); ?>
                    <button type="submit" class="button button-secondary">清空数据</button>
                </form>
            </div>

            <div class="action-card">
                <h2>🔗 API 测试</h2>
                <p>检查 REST API 是否正常</p>
                <button type="button" class="button" onclick="aiNavigatorTestAPI()">测试 API</button>
                <div id="api-test-result" style="margin-top: 10px;"></div>
            </div>
        </div>

        <style>
        .ai-navigator-admin h1 { margin-bottom: 20px; }
        .ai-navigator-stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { background: #fff; border: 1px solid #ccd0d4; padding: 20px 40px; text-align: center; border-radius: 4px; }
        .stat-number { display: block; font-size: 32px; font-weight: bold; color: #2271b1; }
        .stat-label { display: block; color: #646970; margin-top: 5px; }
        .ai-navigator-actions { display: flex; gap: 20px; flex-wrap: wrap; }
        .action-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; flex: 1; min-width: 250px; }
        .action-card h2 { margin-top: 0; font-size: 16px; }
        .action-card p { color: #646970; }
        </style>

        <script>
        function aiNavigatorTestAPI() {
            var result = document.getElementById('api-test-result');
            result.innerHTML = '<span style="color: #666;">测试中...</span>';
            Promise.all([
                fetch('/wp-json/wp/v2/ai_tool?per_page=3').then(r => r.json()),
                fetch('/wp-json/wp/v2/ai_category?per_page=5').then(r => r.json())
            ]).then(function(data) {
                var tools = Array.isArray(data[0]) ? data[0].length : 0;
                var cats = Array.isArray(data[1]) ? data[1].length : 0;
                result.innerHTML = '<span style="color: green;">✓ API 正常：' + tools + ' 个网站，' + cats + ' 个分类</span>';
            }).catch(function(err) {
                result.innerHTML = '<span style="color: red;">✗ API 失败: ' + err.message + '</span>';
            });
        }
        </script>
    </div>
    <?php
}

function ai_navigator_get_stats() {
    return array(
        'tools'       => wp_count_posts( 'ai_tool' )->publish ?? 0,
        'categories'  => wp_count_terms( 'ai_category' ) ?? 0,
        'tags'        => wp_count_terms( 'ai_tag' ) ?? 0,
    );
}

function ai_navigator_delete_all_data() {
    if ( ! current_user_can( 'edit_posts' ) ) return;

    $posts = get_posts( array('post_type' => 'ai_tool', 'posts_per_page' => -1, 'post_status' => 'any') );
    foreach ( $posts as $post ) { wp_delete_post( $post->ID, true ); }

    foreach ( get_terms( array('taxonomy' => 'ai_category', 'hide_empty' => false) ) as $cat ) { wp_delete_term( $cat->term_id, 'ai_category' ); }
    foreach ( get_terms( array('taxonomy' => 'ai_tag', 'hide_empty' => false) ) as $tag ) { wp_delete_term( $tag->term_id, 'ai_tag' ); }

    // 标记用户主动清空，防止 admin_init fallback 自动重新导入
    update_option('dh_nav_data_cleared', true);
}

/**
 * 确保重写规则已刷新
 */
function ai_navigator_ensure_rewrite_rules() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}
add_action( 'admin_init', 'ai_navigator_ensure_rewrite_rules', 1 );

/**
 * 在网站列表页面添加分类筛选器
 */
function ai_navigator_add_category_filter($post_type) {
    if ($post_type !== 'ai_tool') return;
    
    $selected = isset($_GET['ai_category_filter']) ? sanitize_text_field($_GET['ai_category_filter']) : '';
    $categories = get_terms(array('taxonomy' => 'ai_category', 'hide_empty' => false));
    
    echo '<select name="ai_category_filter" id="ai-category-filter" style="margin-right: 8px;">';
    echo '<option value="">所有分类</option>';
    foreach ($categories as $cat) {
        $selected_attr = ($selected === $cat->slug) ? ' selected="selected"' : '';
        echo '<option value="' . esc_attr($cat->slug) . '"' . $selected_attr . '>' . esc_html($cat->name) . '</option>';
    }
    echo '</select>';
    
    // 添加标签筛选器
    $selected_tag = isset($_GET['ai_tag_filter']) ? sanitize_text_field($_GET['ai_tag_filter']) : '';
    $tags = get_terms(array('taxonomy' => 'ai_tag', 'hide_empty' => false, 'number' => 20));
    
    echo '<select name="ai_tag_filter" id="ai-tag-filter">';
    echo '<option value="">所有标签</option>';
    foreach ($tags as $tag) {
        $selected_attr = ($selected_tag === $tag->slug) ? ' selected="selected"' : '';
        echo '<option value="' . esc_attr($tag->slug) . '"' . $selected_attr . '>' . esc_html($tag->name) . '</option>';
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'ai_navigator_add_category_filter');

/**
 * 处理分类筛选查询
 */
function ai_navigator_parse_category_filter($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    
    if ($query->get('post_type') !== 'ai_tool') return;
    
    // 处理分类筛选
    if (isset($_GET['ai_category_filter']) && !empty($_GET['ai_category_filter'])) {
        $category_slug = sanitize_text_field($_GET['ai_category_filter']);
        $tax_query = array(
            array(
                'taxonomy' => 'ai_category',
                'field' => 'slug',
                'terms' => $category_slug,
            ),
        );
        $query->set('tax_query', $tax_query);
    }
    
    // 处理标签筛选
    if (isset($_GET['ai_tag_filter']) && !empty($_GET['ai_tag_filter'])) {
        $tag_slug = sanitize_text_field($_GET['ai_tag_filter']);
        $tax_query = $query->get('tax_query') ?: array();
        $tax_query[] = array(
            'taxonomy' => 'ai_tag',
            'field' => 'slug',
            'terms' => $tag_slug,
        );
        $query->set('tax_query', $tax_query);
    }
}
add_action('parse_query', 'ai_navigator_parse_category_filter');

/**
 * 自定义网站列表列
 */
function ai_navigator_custom_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = '网站名称';
    $new_columns['tool_icon'] = '图标';
    $new_columns['tool_url'] = '网址';
    $new_columns['categories'] = '分类';
    $new_columns['tags'] = '标签';
    $new_columns['tool_hot'] = '热门';
    $new_columns['date'] = '日期';
    return $new_columns;
}
add_filter('manage_ai_tool_posts_columns', 'ai_navigator_custom_columns');

/**
 * 自定义列表列内容
 */
function ai_navigator_custom_columns_content($column, $post_id) {
    switch ($column) {
        case 'tool_icon':
            $icon = get_post_meta($post_id, 'tool_icon', true);
            echo '<span style="font-size: 24px;">' . ($icon ? esc_html($icon) : '🔧') . '</span>';
            break;
        case 'tool_url':
            $url = get_post_meta($post_id, 'tool_url', true);
            if ($url) {
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_url($url) . '</a>';
            } else {
                echo '<span style="color: #999;">未设置</span>';
            }
            break;
        case 'categories':
            $terms = get_the_terms($post_id, 'ai_category');
            if ($terms && !is_wp_error($terms)) {
                $links = array();
                foreach ($terms as $term) {
                    $link = get_term_link($term, 'ai_category');
                    if (!is_wp_error($link)) {
                        $links[] = '<a href="' . esc_url($link) . '">' . esc_html($term->name) . '</a>';
                    }
                }
                echo implode(', ', $links);
            } else {
                echo '<span style="color: #999;">未分类</span>';
            }
            break;
        case 'tags':
            $terms = get_the_terms($post_id, 'ai_tag');
            if ($terms && !is_wp_error($terms)) {
                $links = array();
                foreach ($terms as $term) {
                    $link = get_term_link($term, 'ai_tag');
                    if (!is_wp_error($link)) {
                        $links[] = '<a href="' . esc_url($link) . '">' . esc_html($term->name) . '</a>';
                    }
                }
                echo implode(', ', $links);
            } else {
                echo '<span style="color: #999;">无标签</span>';
            }
            break;
        case 'tool_hot':
            $hot = get_post_meta($post_id, 'tool_hot', true);
            if ($hot) {
                echo '<span style="color: #d63638;">🔥 热门</span>';
            }
            break;
    }
}
add_action('manage_ai_tool_posts_custom_column', 'ai_navigator_custom_columns_content', 10, 2);

/**
 * 使列表列可排序
 */
function ai_navigator_sortable_columns($columns) {
    $columns['tool_hot'] = 'tool_hot';
    return $columns;
}
add_filter('manage_edit-ai_tool_sortable_columns', 'ai_navigator_sortable_columns');

/**
 * 为分类/标签编辑页面添加大海导航样式和面包屑
 */
function ai_navigator_taxonomy_admin_styles() {
    $screen = get_current_screen();
    if (!$screen) return;
    
    // 只在分类法编辑页面处理
    if ($screen->base === 'term' && in_array($screen->taxonomy, array('ai_category', 'ai_tag'))) {
        ?>
        <style>
        #wpbody-content { position: relative; }
        .ai-nav-breadcrumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 12px 20px;
            margin: -20px -20px 20px -20px;
            border-radius: 4px 4px 0 0;
            font-size: 14px;
        }
        .ai-nav-breadcrumb a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        .ai-nav-breadcrumb a:hover {
            color: #fff;
            text-decoration: underline;
        }
        .ai-nav-breadcrumb .current {
            color: #fff;
            font-weight: 500;
        }
        #tag-name, #slug, #description {
            width: 100%;
            max-width: 400px;
        }
        .term-parent-wrap {
            max-width: 400px;
        }
        </style>
        <script>
        jQuery(document).ready(function($) {
            var taxonomy = '<?php echo esc_js($screen->taxonomy); ?>';
            var baseUrl = '<?php echo esc_url(admin_url("admin.php")); ?>';
            var subPage = taxonomy === 'ai_category' ? 'categories' : 'tags';
            var breadcrumb = '<div class="ai-nav-breadcrumb">' +
                '<a href="<?php echo esc_url(admin_url('admin.php?page=dh-nav')); ?>">大海导航</a> » ' +
                '<a href="' + baseUrl + '?page=ai-navigator-' + subPage + '">' +
                (taxonomy === 'ai_category' ? '网站分类' : '网站标签') + '</a> » ' +
                '<span class="current">编辑</span>' +
                '</div>';
            $('#wpbody-content').prepend(breadcrumb);
            
            // 展开大海导航父级菜单
            $('#adminmenu .wp-has-submenu').each(function() {
                if ($(this).find('.wp-submenu li a[href*="page=ai-navigator"]').length > 0) {
                    $(this).addClass('wp-menu-open').find('.wp-menu-toggle').attr('aria-expanded', 'true');
                    $(this).find('.wp-submenu').css('display', 'block');
                }
            });
        });
        </script>
        <?php
    }
}
add_action('admin_head', 'ai_navigator_taxonomy_admin_styles');

// 在 edit-tags.php 页面展开大海导航菜单
function ai_navigator_expand_menu_on_tags_page() {
    $screen = get_current_screen();
    if (!$screen) return;
    
    if ($screen->base === 'edit-tags' && in_array($screen->taxonomy, array('ai_category', 'ai_tag'))) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // 展开大海导航父级菜单
            $('#adminmenu .wp-has-submenu').each(function() {
                var $parent = $(this);
                if ($parent.find('.wp-submenu li a[href*="page=ai-navigator"]').length > 0) {
                    $parent.addClass('wp-menu-open wp-has-current-submenu').attr('aria-expanded', 'true');
                    $parent.find('.wp-submenu').css('display', 'block');
                }
            });
            // 高亮当前菜单项
            var page = '<?php echo $screen->taxonomy === "ai_category" ? "ai-navigator-categories" : "ai-navigator-tags"; ?>';
            $('#adminmenu .wp-submenu li a[href*="page=' + page + '"]').parent().addClass('current');
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'ai_navigator_expand_menu_on_tags_page');

/**
 * 待审核申请管理页面
 */
function ai_navigator_pending_page() {
    if (!current_user_can('edit_posts')) return;

    $message = '';
    $message_type = '';

    // 处理审核操作
    if (isset($_POST['ai_navigator_review_nonce']) && wp_verify_nonce($_POST['ai_navigator_review_nonce'], 'ai_navigator_review')) {
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');
        $post_id = absint($_POST['post_id'] ?? 0);

        if ($post_id && $action_type) {
            $post = get_post($post_id);
            if ($post && $post->post_type === 'ai_tool' && $post->post_status === 'pending') {
                if ($action_type === 'approve') {
                    wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
                    $message = '已审核通过并发布：' . $post->post_title;
                    $message_type = 'success';
                } elseif ($action_type === 'reject') {
                    wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
                    $message = '已拒绝：' . $post->post_title;
                    $message_type = 'info';
                } elseif ($action_type === 'trash') {
                    wp_trash_post($post_id);
                    $message = '已移至回收站：' . $post->post_title;
                    $message_type = 'info';
                }
            }
        }
    }

    // 批量操作
    if (isset($_POST['ai_navigator_bulk_nonce']) && wp_verify_nonce($_POST['ai_navigator_bulk_nonce'], 'ai_navigator_bulk')) {
        $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $post_ids = array_map('absint', $_POST['post_ids'] ?? array());

        if (!empty($post_ids) && $bulk_action) {
            $count = 0;
            foreach ($post_ids as $pid) {
                $post = get_post($pid);
                if ($post && $post->post_type === 'ai_tool' && $post->post_status === 'pending') {
                    if ($bulk_action === 'approve') {
                        wp_update_post(array('ID' => $pid, 'post_status' => 'publish'));
                        $count++;
                    } elseif ($bulk_action === 'reject') {
                        wp_update_post(array('ID' => $pid, 'post_status' => 'draft'));
                        $count++;
                    } elseif ($bulk_action === 'trash') {
                        wp_trash_post($pid);
                        $count++;
                    }
                }
            }
            $action_labels = array('approve' => '通过', 'reject' => '拒绝', 'trash' => '删除');
            $message = sprintf('已批量%s %d 条记录', $action_labels[$bulk_action] ?? $bulk_action, $count);
            $message_type = 'success';
        }
    }

    // 获取待审核列表
    $pending_posts = get_posts(array(
        'post_type' => 'ai_tool',
        'post_status' => 'pending',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    $pending_count = count($pending_posts);
    ?>
    <div class="wrap ai-navigator-pending">
        <h1>📋 待审核申请 <?php if ($pending_count > 0) : ?><span class="pending-badge"><?php echo $pending_count; ?></span><?php endif; ?></h1>

        <?php if ($message) : ?>
        <div class="notice notice-<?php echo $message_type === 'success' ? 'success' : 'info'; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php endif; ?>

        <?php if (empty($pending_posts)) : ?>
        <div class="empty-pending">
            <div class="empty-icon">✅</div>
            <h2>暂无待审核申请</h2>
            <p>所有申请已处理完毕，用户通过<a href="<?php echo home_url('/submit'); ?>" target="_blank">申请收录页面</a>提交的网站将在此显示。</p>
        </div>
        <?php else : ?>

        <!-- 批量操作 -->
        <div class="bulk-actions">
            <form method="post">
                <?php wp_nonce_field('ai_navigator_bulk', 'ai_navigator_bulk_nonce'); ?>
                <select name="bulk_action">
                    <option value="">批量操作</option>
                    <option value="approve">批量通过</option>
                    <option value="reject">批量拒绝</option>
                    <option value="trash">批量删除</option>
                </select>
                <button type="submit" class="button button-primary" onclick="return confirm('确认执行批量操作？')">应用</button>

                <table class="wp-list-table widefat fixed striped pending-table">
                    <thead>
                        <tr>
                            <th class="check-column"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th>网站名称</th>
                            <th>网址</th>
                            <th>图标</th>
                            <th>分类</th>
                            <th>简介</th>
                            <th>联系方式</th>
                            <th>提交时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pending_posts as $post) :
                        $tool_url = get_post_meta($post->ID, 'tool_url', true);
                        $tool_icon = get_post_meta($post->ID, 'tool_icon', true);
                        $submit_contact = get_post_meta($post->ID, 'submit_contact', true);
                        $submit_time = get_post_meta($post->ID, 'submit_time', true);
                        $categories = get_the_terms($post->ID, 'ai_category');
                        $cat_names = $categories && !is_wp_error($categories) ? implode(', ', wp_list_pluck($categories, 'name')) : '—';
                    ?>
                        <tr>
                            <td class="check-column"><input type="checkbox" name="post_ids[]" value="<?php echo $post->ID; ?>" class="post-checkbox"></td>
                            <td><strong><?php echo esc_html($post->post_title); ?></strong></td>
                            <td>
                                <?php if ($tool_url) : ?>
                                    <a href="<?php echo esc_url($tool_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($tool_url); ?></a>
                                <?php else : ?>—<?php endif; ?>
                            </td>
                            <td style="font-size:24px;text-align:center;"><?php echo esc_html($tool_icon ?: '🔧'); ?></td>
                            <td><?php echo esc_html($cat_names); ?></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html(wp_trim_words($post->post_excerpt ?: $post->post_content, 20)); ?></td>
                            <td><?php echo esc_html($submit_contact ?: '—'); ?></td>
                            <td style="white-space:nowrap;"><?php echo $submit_time ? esc_html($submit_time) : esc_html(get_the_date('', $post)); ?></td>
                            <td style="white-space:nowrap;">
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('ai_navigator_review', 'ai_navigator_review_nonce'); ?>
                                    <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
                                    <input type="hidden" name="action_type" value="approve">
                                    <button type="submit" class="button button-small button-primary" title="通过并发布">✓ 通过</button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('ai_navigator_review', 'ai_navigator_review_nonce'); ?>
                                    <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
                                    <input type="hidden" name="action_type" value="reject">
                                    <button type="submit" class="button button-small" title="拒绝，移至草稿">✗ 拒绝</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('确认删除？');">
                                    <?php wp_nonce_field('ai_navigator_review', 'ai_navigator_review_nonce'); ?>
                                    <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
                                    <input type="hidden" name="action_type" value="trash">
                                    <button type="submit" class="button button-small" title="移至回收站" style="color:#b32d2e;">🗑</button>
                                </form>
                                <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small" title="编辑">编辑</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php endif; ?>

        <style>
        .ai-navigator-pending h1 { margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .pending-badge { background: #d63638; color: #fff; font-size: 13px; padding: 2px 10px; border-radius: 12px; font-weight: 500; }
        .empty-pending { text-align: center; padding: 60px 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin-top: 20px; }
        .empty-icon { font-size: 48px; margin-bottom: 16px; }
        .empty-pending h2 { font-size: 18px; margin-bottom: 8px; }
        .empty-pending p { color: #646970; }
        .empty-pending a { color: #2271b1; }
        .bulk-actions { margin-top: 20px; }
        .bulk-actions > form > select,
        .bulk-actions > form > .button { vertical-align: middle; margin-right: 8px; }
        .pending-table { margin-top: 12px; }
        .pending-table td, .pending-table th { padding: 10px 8px; vertical-align: middle; }
        .check-column { width: 30px; text-align: center; }
        </style>

        <script>
        function toggleAll(el) {
            document.querySelectorAll('.post-checkbox').forEach(cb => cb.checked = el.checked);
        }
        </script>
    </div>
    <?php
}

/**
 * 在后台顶部显示待审核数量提示
 */
function ai_navigator_pending_admin_notice() {
    $pending_count = wp_count_posts('ai_tool')->pending ?? 0;
    if ($pending_count > 0 && !isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] !== 'ai-navigator-pending')) {
        $screen = get_current_screen();
        // 仅在大海导航相关页面显示
        if ($screen && (strpos($screen->id, 'ai-navigator') !== false || $screen->post_type === 'ai_tool')) {
            echo '<div class="notice notice-info"><p>📋 您有 <strong>' . $pending_count . '</strong> 条待审核的网站申请，<a href="' . admin_url('admin.php?page=ai-navigator-pending') . '">点击查看</a></p></div>';
        }
    }
}
add_action('admin_notices', 'ai_navigator_pending_admin_notice');

/**
 * 网站设置页面
 */
function ai_navigator_settings_page() {
    if (!current_user_can('manage_options')) return;

    // 保存设置
    if (isset($_POST['ai_navigator_settings_nonce']) && wp_verify_nonce($_POST['ai_navigator_settings_nonce'], 'ai_navigator_settings_save')) {
        update_option('ai_navigator_site_name', sanitize_text_field($_POST['site_name'] ?? ''));
        update_option('ai_navigator_icp_number', sanitize_text_field($_POST['icp_number'] ?? ''));
        update_option('ai_navigator_icp_url', esc_url_raw($_POST['icp_url'] ?? ''));
        update_option('ai_navigator_copyright', sanitize_text_field($_POST['copyright'] ?? ''));
        update_option('ai_navigator_contact_info', wp_kses_post($_POST['contact_info'] ?? ''));
        update_option('ai_navigator_friend_links', wp_kses_post($_POST['friend_links'] ?? ''));
        update_option('ai_navigator_head_js', wp_unslash($_POST['head_js'] ?? ''));
        update_option('ai_navigator_footer_js', wp_unslash($_POST['footer_js'] ?? ''));

        echo '<div class="notice notice-success is-dismissible"><p>设置已保存！</p></div>';
    }

    // 读取当前值
    $site_name = get_option('ai_navigator_site_name', '大海导航');
    $icp_number = get_option('ai_navigator_icp_number', '');
    $icp_url = get_option('ai_navigator_icp_url', 'https://beian.miit.gov.cn/');
    $copyright = get_option('ai_navigator_copyright', '');
    $contact_info = get_option('ai_navigator_contact_info', '');
    $friend_links = get_option('ai_navigator_friend_links', '');
    $head_js = get_option('ai_navigator_head_js', '');
    $footer_js = get_option('ai_navigator_footer_js', '');
    ?>
    <div class="wrap ai-navigator-settings">
        <h1>⚙️ 网站设置</h1>

        <form method="post">
            <?php wp_nonce_field('ai_navigator_settings_save', 'ai_navigator_settings_nonce'); ?>

            <!-- 基本信息 -->
            <div class="settings-section">
                <h2>📋 基本信息</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="site_name">网站名称</label></th>
                        <td>
                            <input type="text" id="site_name" name="site_name" value="<?php echo esc_attr($site_name); ?>" class="regular-text">
                            <p class="description">显示在页脚和标题中的网站名称</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="copyright">版权信息</label></th>
                        <td>
                            <input type="text" id="copyright" name="copyright" value="<?php echo esc_attr($copyright); ?>" class="regular-text" placeholder="© <?php echo date('Y'); ?> 大海导航 All Rights Reserved.">
                            <p class="description">留空则默认显示 "© 年份 网站名称 All Rights Reserved."</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 备案信息 -->
            <div class="settings-section">
                <h2>📝 备案信息</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="icp_number">ICP 备案号</label></th>
                        <td>
                            <input type="text" id="icp_number" name="icp_number" value="<?php echo esc_attr($icp_number); ?>" class="regular-text" placeholder="京ICP备XXXXXXXX号">
                            <p class="description">留空则页脚不显示备案号</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="icp_url">备案号链接</label></th>
                        <td>
                            <input type="url" id="icp_url" name="icp_url" value="<?php echo esc_attr($icp_url); ?>" class="regular-text">
                            <p class="description">点击备案号跳转的链接，默认为工信部网站</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 联系方式 -->
            <div class="settings-section">
                <h2>📞 联系方式</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="contact_info">联系方式</label></th>
                        <td>
                            <textarea id="contact_info" name="contact_info" rows="4" class="large-text code" placeholder="邮箱：admin@example.com&#10;微信：xxxxx&#10;QQ群：123456789&#10;GitHub：https://github.com/xxx"><?php echo esc_textarea($contact_info); ?></textarea>
                            <p class="description">每行一条联系方式，支持 HTML 标签（如链接 &lt;a&gt;）</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 友情链接 -->
            <div class="settings-section">
                <h2>🔗 友情链接</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="friend_links">友情链接</label></th>
                        <td>
                            <textarea id="friend_links" name="friend_links" rows="6" class="large-text code" placeholder="百度|https://www.baidu.com&#10;谷歌|https://www.google.com&#10;或使用HTML: &lt;a href=&quot;URL&quot;&gt;名称&lt;/a&gt;"><?php echo esc_textarea($friend_links); ?></textarea>
                            <p class="description">每行一条，格式：<code>名称|URL</code>，或直接使用 HTML 代码</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 自定义代码 -->
            <div class="settings-section">
                <h2>💻 自定义代码</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="head_js">Head 代码</label></th>
                        <td>
                            <textarea id="head_js" name="head_js" rows="6" class="large-text code" placeholder="放在 &lt;head&gt; 中的代码，如统计代码、Meta标签等"><?php echo esc_textarea($head_js); ?></textarea>
                            <p class="description">将输出在页面的 &lt;head&gt; 中，适合放统计代码、Meta 标签等</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="footer_js">Footer 代码</label></th>
                        <td>
                            <textarea id="footer_js" name="footer_js" rows="6" class="large-text code" placeholder="放在 &lt;/body&gt; 前的代码，如统计代码、在线客服等"><?php echo esc_textarea($footer_js); ?></textarea>
                            <p class="description">将输出在页面 &lt;/body&gt; 前，适合放统计代码、在线客服等</p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button('保存设置'); ?>
        </form>

        <style>
        .ai-navigator-settings h1 { margin-bottom: 20px; }
        .settings-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .settings-section h2 {
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
            font-size: 16px;
        }
        .settings-section .form-table { margin-top: 10px; }
        .settings-section .large-text { width: 100%; max-width: 700px; }
        .settings-section .code { font-family: Consolas, Monaco, monospace; font-size: 13px; }
        .settings-section .description code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        </style>
    </div>
    <?php
}
