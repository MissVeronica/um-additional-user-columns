<?php
/**
 * Plugin Name:     Ultimate Member - Additional User Columns
 * Description:     Extension to Ultimate Member for additional User Columns in the WP All Users page.
 * Version:         1.0.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

Class UM_Additional_User_Columns {

    public $users_columns = array();

    function __construct() {

        if ( is_admin()) {

            add_filter( 'manage_users_columns',          array( $this, 'manage_users_columns_new_columns' ));
            add_filter( 'manage_users_sortable_columns', array( $this, 'register_sortable_columns_custom' ), 10, 1 );
            add_action( 'pre_get_users',                 array( $this, 'pre_get_users_sort_columns_custom' ));
            add_filter( 'manage_users_custom_column',    array( $this, 'manage_users_custom_column_new_columns' ), 10, 3 );
            add_filter( 'um_settings_structure',         array( $this, 'um_settings_structure_user_new_columns' ), 10, 1 );

            $new_columns = UM()->options()->get( 'um_new_columns_items_list' );

            if ( ! empty( $new_columns )) {
                $terminator = strpos( $new_columns, "\n" ) ? $terminator = "\n" : $terminator = "\r";
                $new_columns = array_map( 'sanitize_text_field', array_map( 'trim', explode( $terminator, $new_columns )));

                foreach( $new_columns as $new_column ) {
                    if ( ! empty( $new_column ) && str_contains( $new_column, ':' )) {

                        $items = array_map( 'sanitize_text_field', array_map( 'trim', explode( ':', $new_column )));

                        if ( is_array( $items ) && count( $items ) == 2 && ! empty( $items[0] ) && ! empty( $items[1] )) {
                            $this->users_columns[$items[0]] = $items[1];
                        }
                    }
                }
            }
        }
    }

    public function register_sortable_columns_custom( $columns ) {

        foreach( $this->users_columns as $meta_key => $label ) {
            $columns['um_column_' . $meta_key] = 'um_column_' . $meta_key;
        }
        return $columns;
    }

    public function manage_users_columns_new_columns( $columns ) {

        foreach( $this->users_columns as $meta_key => $label ) {
            $columns['um_column_' . $meta_key] = esc_attr( $label );
        }
        return $columns;
    }

    public function manage_users_custom_column_new_columns( $value, $column_name, $user_id ) {

        foreach( $this->users_columns as $meta_key => $label ) {
            if ( $column_name == 'um_column_' . $meta_key ) {

                um_fetch_user( $user_id );
                $value = um_user( $meta_key );

                if( empty( $value )) {
                    $value = '-';
                }
                return $value;
            }
        }
        return $value;
    }

    public function pre_get_users_sort_columns_custom( $query ) {

        foreach( $this->users_columns as $meta_key => $label ) {

            if ( $query->get( 'orderby' ) == 'um_column_' . $meta_key ) {
                 $query->set( 'orderby',  'meta_value' );
                 $query->set( 'meta_key', $meta_key );
                 break;
            }
        }
    }

    public function um_settings_structure_user_new_columns( $settings_structure ) {

        $settings_structure['']['sections']['users']['fields'][] = array(
            'id'            => 'um_new_columns_items_list',
            'type'          => 'textarea',
            'size'          => 'medium',
            'label'         => __( 'Additional User Columns - meta_key:label', 'ultimate-member' ),
            'tooltip'       => __( 'Add one meta_key:label per line for display in the WP All Users page.', 'ultimate-member' ),
        );

        return $settings_structure;
    }
}

new UM_Additional_User_Columns();
