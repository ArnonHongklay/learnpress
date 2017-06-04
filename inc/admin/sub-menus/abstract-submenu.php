<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Abstract_Submenu
 *
 * @since 3.0.0
 */
abstract class LP_Abstract_Submenu {
	/**
	 * Menu slug
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Menu title
	 *
	 * @var string
	 */
	protected $menu_title = '';

	/**
	 * Page title
	 *
	 * @var string
	 */
	protected $page_title = '';

	/**
	 * Priority
	 *
	 * @var int
	 */
	protected $priority = 10;

	/**
	 * Capability
	 *
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * Menu icon
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Callback (may not be used)
	 *
	 * @var bool
	 */
	protected $callback = false;

	/**
	 * Heading tabs
	 *
	 * @var array
	 */
	protected $tabs = array();

	/**
	 * Tab's sections
	 *
	 * @var array
	 */
	protected $sections = array();

	/**
	 * Current page
	 *
	 * @var bool
	 */
	protected $page = false;

	/**
	 * LP_Abstract_Submenu constructor.
	 */
	public function __construct() {
		add_action( 'learn-press/admin/page-content-sections', array( $this, 'output_section_nav' ) );
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function get_menu_title() {
		return $this->menu_title;
	}

	/**
	 * @param string $menu_title
	 */
	public function set_menu_title( $menu_title ) {
		$this->menu_title = $menu_title;
	}

	/**
	 * @return string
	 */
	public function get_page_title() {
		return $this->page_title;
	}

	/**
	 * @param string $page_title
	 */
	public function set_page_title( $page_title ) {
		$this->page_title = $page_title;
	}

	/**
	 * @return int|string
	 */
	public function get_priority() {
		return $this->priority;
	}

	/**
	 * @param string|int $priority
	 */
	public function set_priority( $priority ) {
		$this->priority = $priority;
	}

	/**
	 * @return string
	 */
	public function get_capability() {
		return $this->capability;
	}

	/**
	 * @param string $capability
	 */
	public function set_capability( $capability ) {
		$this->capability = $capability;
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return $this->icon;
	}

	/**
	 * @param string $icon
	 */
	public function set_icon( $icon ) {
		$this->icon = $icon;
	}

	/**
	 * Get heading tabs.
	 *
	 * @return mixed|array
	 */
	public function get_tabs() {
		return apply_filters( 'learn-press/submenu-' . $this->get_id() . '-heading-tabs', $this->sanitize_tabs( $this->tabs ) );
	}

	public function sanitize_tabs( $tabs ) {
		$sanitized_tabs = array();
		if ( $tabs ) {
			foreach ( $tabs as $tab => $name ) {
				if ( is_string( $name ) && class_exists( $name ) ) {
					$objSettings                        = new $name();
					$sanitized_tabs[ $objSettings->id ] = $objSettings;
				} else {
					$sanitized_tabs[ $tab ] = $name;
				}
			}
		}

		return $sanitized_tabs;
	}

	/**
	 * Get active tab by checking ?tab=tab-name
	 *
	 * @return bool|mixed
	 */
	public function get_active_tab() {
		$tabs = $this->get_tabs();
		if ( ! $tabs ) {
			return false;
		}
		$tab = ! empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : false;
		if ( ! $tab || empty( $tabs[ $tab ] ) ) {
			$tab_keys = array_keys( $tabs );
			$tab      = reset( $tab_keys );
		}

		return $tab;
	}

	/**
	 * @return array|mixed
	 */
	public function has_tabs() {
		return $this->get_tabs();
	}

	/**
	 * @return mixed
	 */
	public function get_sections() {
		$active_tab = $this->get_active_tab();
		if ( ! empty( $this->tabs[ $active_tab ] ) ) {
			if ( is_callable( array( $this->tabs[ $active_tab ], 'get_sections' ) ) ) {
				$this->sections = call_user_func( array( $this->tabs[ $active_tab ], 'get_sections' ) );
			}
		}

		return apply_filters( 'learn-press/submenu-sections', $this->sections );
	}

	public function get_active_page() {
		if ( false === $this->page ) {
			$this->page = ! empty ( $_REQUEST['page'] ) ? $_REQUEST['page'] : null;
		}

		return $this->page;
	}

	/**
	 * Get active section by checking ?section=tab-name
	 *
	 * @return bool|mixed
	 */
	public function get_active_section() {
		$sections = $this->get_sections();
		if ( ! $sections ) {
			return false;
		}
		$section = ! empty( $_REQUEST['section'] ) ? $_REQUEST['section'] : false;
		if ( ! $section || empty( $sections[ $section ] ) ) {
			$section_keys = array_keys( $sections );
			$section      = reset( $section_keys );
		}

		return $section;
	}

	/**
	 * Display menu content
	 */
	public function display() {
		$tabs       = $this->get_tabs();
		$active_tab = $this->get_active_tab();
		$classes    = array( 'wrap', 'lp-submenu-page', $this->get_id() );

		?>
        <div class="<?php echo join( ' ', $classes ); ?>">
            <div id="icon-themes" class="icon32"><br></div>
			<?php if ( $tabs ) { ?>
                <h2 class="nav-tab-wrapper">
					<?php foreach ( $tabs as $tab => $name ) { ?>
						<?php
						$objTab = false;
						if ( is_object( $name ) ) {
							$objTab = $name;
							$name   = $objTab->text;
							$tab    = $objTab->id;
						}
						$active_class = ( $tab == $active_tab ) ? ' nav-tab-active' : '';
						$tab_title    = apply_filters( 'learn-press/admin/submenu-heading-tab-title', $name, $tab );
						?>
						<?php if ( $active_class ) { ?>
                            <span class="nav-tab<?php echo esc_attr( $active_class ); ?>"><?php echo $tab_title; ?></span>
						<?php } else { ?>
                            <a class="nav-tab"
                               href="?page=learn-press-settings&tab=<?php echo $tab; ?>"><?php echo $tab_title; ?></a>
						<?php } ?>
					<?php } ?>
                </h2>
			<?php } else { ?>
                <h1 class="wp-heading-inline"><?php echo $this->get_menu_title(); ?></h1>
			<?php } ?>
			<?php
			$classes = array( 'lp-admin-tabs' );
			if ( ( $sections = $this->get_sections() ) && sizeof( $sections ) > 1 ) {
				$classes[] = 'has-sections';
			}
			?>
            <form class="<?php echo join( ' ', $classes ); ?>" method="post" enctype="multipart/form-data">
				<?php $this->page_content(); ?>
            </form>
        </div>
		<?php
	}

	/**
	 * This function for displaying content of active tab only.
	 * For displaying content of main page without tab,
	 * overwrite this function in sub class.
	 */
	public function page_content() {
		do_action( 'learn-press/admin/page-content-sections', $this );

		echo '<div class="lp-admin-tab-content">';
		if ( $this->has_tabs() ) {
			$page = $this->_get_page();
			$tab  = $this->get_active_tab();
			// If I have a function named 'page_content_TAB_SLUG' then call it.
			$callback = array( $this, sprintf( 'page_content_%s', $tab ) );
			if ( is_callable( $callback ) ) {
				call_user_func_array( $callback, array() );
			} else {
				// Otherwise, do a actions.
				do_action( 'learn-press/admin/page-content-' . $page, $tab );
				do_action( 'learn-press/admin/page-content-' . $page . '/' . $tab );
			}
		}
		echo '</div>';
	}

	/**
	 * Output section navigation.
	 */
	public function output_section_nav() {
		if ( $this->id !== $this->get_active_page() ) {
			return;
		}

		$active_section = $this->get_active_section();
		if ( ! $sections = $this->get_sections() ) {
			return;
		}
		?>
        <ul class="lp-admin-tab-navs">
			<?php foreach ( $sections as $slug => $section ) { ?>
				<?php
				$active_class  = ( $slug == $active_section ) ? ' nav-section-active' : '';
				$section_title = apply_filters( 'learn-press/admin/submenu-section-title', $section, $slug );
				?>
                <li class="nav-section<?php echo $active_class; ?>">
					<?php if ( $active_class ) { ?>
                        <span><?php echo $section_title; ?></span>
					<?php } else { ?>
                        <a href="<?php echo esc_url( add_query_arg( 'section', $slug ) ); ?>"><?php echo $section_title; ?></a>
					<?php } ?>
                </li>
			<?php } ?>
        </ul>
		<?php
	}

	/**
	 * Display section content
	 */
	public function display_section() {
		if ( ! $section = $this->get_active_section() ) {
			return false;
		}

		if ( ! $sectionClass = $this->sections[ $section ] ) {
			return false;
		}

		do_action( 'learn-press/admin/page-' . $this->_get_page() . '/section-content', $section );

		if ( is_callable( array( $this, 'section_content_' . $section ) ) ) {
			call_user_func( array( $this, 'section_content_' . $section ) );
		} else {
			do_action( 'learn-press/admin/page-' . $this->_get_page() . '/section-content-' . $section );
		}

		return true;
	}

	protected function _get_page() {
		return str_replace( 'learn-press-', '', $this->get_id() );
	}
}