<?php

class DC_Admin_Page {
	const PAGE_SLUG = 'dc-admin';
	
	public function __construct() {
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_init', array($this, 'admin_init'));
	}
	
	public function admin_menu() {
		add_options_page('Domaincheck Settings', 'Domaincheck', 'manage_options', DC_Admin_Page::PAGE_SLUG, array($this, 'render_page'));			
	}
	
	public function admin_init() {
		register_setting(Domaincheck::SETTING_SLUG, Domaincheck::SETTING_SLUG, array($this, 'sanitize'));
		add_settings_section('default', null, null, DC_Admin_Page::PAGE_SLUG);

		foreach(Domaincheck::getInstance()->get_setting() as $setting) {
			$setting = wp_parse_args($setting, array(
				'id' => '',
				'title' => '',
				'type' => '',
			));
						
			$setting['label_for'] = $setting['id'];
			
			add_settings_field($setting['id'], $setting['title'], array($this, 'render_input'), DC_Admin_Page::PAGE_SLUG, 'default', $setting);			
		}
	}
	
	public function render_page() {
		?>
			<div class="wrap">
				<h2><?php echo esc_html(get_admin_page_title()); ?></h2>

				<form method="post" action="options.php" autocomplete="off">
				<?php
					settings_fields(Domaincheck::SETTING_SLUG);
					do_settings_sections(DC_Admin_Page::PAGE_SLUG);

					submit_button();
				?>
				</form>
			</div>
		<?php
	}
	
	public function render_input($args) {
		$args = wp_parse_args($args, array(
			'type' => '',
			'id' => '',
			'default' => '',
			'description' => '',
		));
		
		$value = Domaincheck::getInstance()->get_setting_value($args['id']);
		
		switch($args['type']) {
			case 'textarea':
				$format = '<textarea id="%1$s" class="all-options code" rows="' . (substr_count($value, "\n") + 2) . '" name="%3$s[%1$s]">%2$s</textarea>';
				break;
			default:
				$format = '<input id="%1$s" class="regular-text" type="text" name="%3$s[%1$s]" value="%2$s" />';
				break;
		}
		
		printf($format, $args['id'], $value, Domaincheck::SETTING_SLUG);
		
		if($args['description']) {
			printf('<p class="description">%s</p>', $args['description']);
		}
	}
	
	public function sanitize($input) {
		$output = array();
		
		foreach(Domaincheck::getInstance()->get_setting() as $setting) {
			$setting = wp_parse_args($setting, array(
				'id' => '',
				'type' => 'text',
			));
			
			if(!isset($input[(string) $setting['id']])) continue;
			
			$value = $input[$setting['id']];
			
			switch($setting['type']) {
				case 'textarea':
					$value = implode("\n", array_map('sanitize_text_field', explode("\n", $value)));
					break;
				default:
					$value = sanitize_text_field($value);
					break;
			}
			
			$output[$setting['id']] = $value;
		}
		
		return $output;
	}
}