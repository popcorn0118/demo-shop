<?php
/**
 * WAPO Block Class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ProductAddOns
 * @version 2.0.0
 */

defined( 'YITH_WAPO' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WAPO_Block' ) ) {

	/**
	 *  Block class.
	 *  The class manage all the Block behaviors.
	 */
	class YITH_WAPO_Block {

		/**
		 *  ID
		 *
		 * @var int
		 */
		public $id = 0;

		/**
		 *  Settings
		 *
		 * @var array
		 */
		public $settings = array();

		/**
		 *  User ID
		 *
		 * @var int
		 */
		public $user_id;

		/**
		 *  Vendor ID
		 *
		 * @var int
		 */
		public $vendor_id;

		/**
		 *  Visibility
		 *
		 * @var boolean
		 */
		public $visibility = 1;

		/**
		 *  Name
		 *
		 * @var string
		 */
		public $name = '';

		/**
		 *  Priority
		 *
		 * @var int
		 */
		public $priority;

		/**
		 * Rules
		 *
		 * @var array
		 */
		public $rules = array();

		/**
		 * Rules
		 *
		 * @var string
		 */
		public $product_association = '';

		/**
		 * Rules
		 *
		 * @var boolean
		 */
		public $exclude_products = 0;

		/**
		 * Rules
		 *
		 * @var string
		 */
		public $user_association = '';

		/**
		 * Rules
		 *
		 * @var boolean
		 */
		public $exclude_users = 0;

		/**
		 *  Constructor
		 *
		 * @param array $args The args to instantiate the class.
		 */
		public function __construct( $args ) {
			global $wpdb;

			/**
			 * $id -> The block id.
			 */
			extract( $args ); //phpcs:ignore

			if ( $id > 0 ) {

				$blocks_name = $wpdb->prefix . YITH_WAPO_DB()::YITH_WAPO_BLOCKS;
				$query       = "SELECT * FROM $blocks_name WHERE id='$id'";

				$row = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

				if ( isset( $row ) && $row->id === $id ) {

					$this->id                  = $row->id;
					$this->user_id             = $row->user_id;
					$this->vendor_id           = $row->vendor_id;
					$this->priority            = $row->priority;
					$this->visibility          = $row->visibility;
					$this->name                = $row->name ?? '';
					$this->settings            = @unserialize( $row->settings ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.NoSilencedErrors.Discouraged
					$this->rules               = $this->settings['rules'] ?? array();
					$this->product_association = $row->product_association ?? '';
					$this->exclude_products    = $row->exclude_products ?? '';
					$this->user_association    = $row->user_association ?? '';
					$this->exclude_users       = $row->exclude_users ?? '';

				}
			}
		}

		/**
		 * Return id of the current block.
		 *
		 * @return string
		 */
		public function get_id() {
			return $this->id ?? 0;
		}

        /**
         * Return user_id of the current block.
         *
         * @return string
         */
        public function get_user_id() {
            return $this->user_id ?? 0;
        }

        /**
         * Return vendor_id of the current block.
         *
         * @return string
         */
        public function get_vendor_id() {
            return 0;
        }

		/**
		 * Get Setting
		 *
		 * @param string $option Option.
		 * @param string $default_setting Default setting.
		 */
		public function get_setting( $option, $default_setting = '' ) {
			return $this->settings[ $option ] ?? $default_setting;
		}

		/**
		 * Get Rule
		 *
		 * @param string $name Name.
		 * @param string $default_rule Default rule.
		 */
		public function get_rule( $name, $default_rule = '' ) {
			return $this->rules[ $name ] ?? $default_rule;
		}

		/**
		 * Return name of the current block.
		 *
		 * @return string
		 */
		public function get_name() {
			return $this->name ?? '';
		}

		/**
		 * Return visibility of the current block.
		 *
		 * @return string
		 */
		public function get_visibility() {
			return $this->visibility ?? 0;
		}

		/**
		 * Return priority of the current block.
		 *
		 * @return string
		 */
		public function get_priority() {
			return $this->priority ?? 0;
		}

		/**
		 * Get all the settings.
		 *
		 * @return array|mixed
		 */
		public function get_settings() {
			return $this->settings ?? array();
		}

		/**
		 * Get all the rules.
		 *
		 * @return array|mixed
		 */
		public function get_rules() {
			return $this->rules ?? array();
		}
	}

}
