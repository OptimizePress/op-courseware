<?php

/**
 * Plugin Name: OptimizePress WP Courseware integration
 * Plugin URI:  www.optimizepress.com
 * Description: Plugin adds option to meta box for rendering courseware unit details over shortcode and fixes rendering complete box multiple time over the page
 * Version:     1.0.0
 * Author:      OptimizePress <info@optimizepress.com>
 * Author URI:  optimizepress.com
 */
class OptimizePress_WPCourseware_Integration
{
    /**
     * @var OptimizePress_WPCourseware_Integration
     */
    protected static $instance;

    /**
     * Registering actions and filters
     */
    protected function __construct()
    {
        add_action("wp_head", array($this, 'removeWPCoursewareContentFilter'));

        add_action("add_meta_boxes", array($this, 'addOPCoursewareMetaBox'));
        add_action("save_post", array($this, 'saveCustomMetaBox'), 9, 3);

        add_shortcode('op-courseware', array($this, 'renderCoursewareUnitDetails'));
    }

    /**
     * Removes WP_Courseware the_content filter that renders unit_details
     * @return void
     */
    public function removeWPCoursewareContentFilter()
    {
        global $post;
        $checkbox_value = get_post_meta($post->ID, "op-courseware-checkbox", true);
        if (intval($checkbox_value) == 1) {
            remove_filter('the_content', 'WPCW_units_processUnitContent');
        }
    }

    /**
     * Adds Meta Box with OptimizePress and WP_Courseware compatibility
     * @return void
     */
    public function addOPCoursewareMetaBox()
    {
        add_meta_box(
            "op-courseware-box",
            __("OptimizePress & Courseware", "op_courseware"),
            array($this, "customMetaBoxMarkup"),
            "course_unit",
            "side",
            "default"
        );
    }

    /**
     * Renders WP-Coursware unit detail over [op-courseware] shortcode
     * @return string
     */
    public static function renderCoursewareUnitDetails()
    {
        global $post;
        if (class_exists('WPCW_UnitFrontend')) {
            $fe = new WPCW_UnitFrontend($post);
            return $fe->render_detailsForUnit("");
        }
    }

    /**
     * Renders checkbox inside Meta box
     * @return string
     */
    public function customMetaBoxMarkup($object)
    {
        wp_nonce_field("op-courseware-box-nonce", "op-courseware-box-nonce");
        $checkbox_value = get_post_meta($object->ID, "op-courseware-checkbox", true);
        ?>
        <div>
            <input name="op-courseware-checkbox" type="checkbox" value="1" <?php checked($checkbox_value, 1); ?>>
            <label for="op-courseware-checkbox"><?php _e("Use shortcode [op-courseware] for rendering wp-courseware complete box", "op_courseware"); ?></label>
        </div>
        <?php
    }

    /**
     * Saves op-coursware meta
     * @return string
     */
    public function saveCustomMetaBox($post_id, $post, $update)
    {
        if ((!isset($_POST["op-courseware-box-nonce"]) || !wp_verify_nonce($_POST["op-courseware-box-nonce"], "op-courseware-box-nonce"))
            || (!current_user_can("edit_post", $post_id))
            || (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
            || ("course_unit" !== $post->post_type)) {
            return $post_id;
        }

        $meta_box_checkbox_value = "";

        if (isset($_POST["op-courseware-checkbox"])) {
            $meta_box_checkbox_value = intval($_POST["op-courseware-checkbox"]);
        }
        update_post_meta($post_id, "op-courseware-checkbox", $meta_box_checkbox_value);
    }

    /**
     * Singleton
     * @return OptimizePress_WPCourseware_Integration
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
}

OptimizePress_WPCourseware_Integration::getInstance();