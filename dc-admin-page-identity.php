<?php

class DC_Admin_Page_Identity {
	const PAGE_SLUG = 'dc-identity';
	
	private $notice;
	
	private $notice_class;
	
	public function __construct() {
		add_action('admin_menu', array($this, 'admin_menu'));
	}
	
	public function admin_menu() {
		$slug = add_submenu_page(null, 'Identity Toevoegen', 'identity', 'manage_options', DC_Admin_Page_Identity::PAGE_SLUG, array($this, 'render_page'));
		
		add_action('load-' . $slug, array($this, 'page_load'));
	}
	
	public function page_load() {
		if(isset($_POST['action']) && $_POST['action'] == 'dc-submit') {
			$args = array();
			
			foreach($this->get_form_fields() as $name => $field) {
				$value = $this->get_value_for_field($name);
				
				if(!empty($value)) {
					$args[$name] = $value;
				}
			}
			
			$request = new DC_Request_Add_Identity($args);
			
			$this->notice = $request->getStatusMessage();
			$this->notice_class = $request->is_error() ? 'error' : 'updated';
			
			add_action('admin_notices', array($this, 'admin_notice'));
		}
	}
	
	public function admin_notice() {
		?>
			<div class="<?php echo $this->notice_class ?>">
				<p><?php echo $this->notice ?></p>
			</div>
		<?php
	}
	
	private function get_value_for_field($name) {
		return isset($_REQUEST[$name]) ? sanitize_text_field($_REQUEST[$name]) : '';
	}
	
	public function render_page() {
		?>
			<div class="wrap">
				<h2>Identity Toevoegen</h2>
				
				<p>Met dit formulier kunt u het aan te maken profiel overzien en wijzigen voordat u het toevoegd.</p>

				<form method="post" action="<?php menu_page_url(DC_Admin_Page_Identity::PAGE_SLUG); ?>">
					<table class="form-table">
						
						<?php foreach($this->get_form_fields() as $name => $field): ?>
							<tr>
								
								<th>
									<?php
										echo $field['label'];
										if(isset($field['required']) && $field['required']) echo '*';
									?>
									<p class="description"><?php echo $name ?></p>
								</th>
								
								<td>
									<input name="<?php echo $name ?>" type="text" value="<?php echo $this->get_value_for_field($name) ?>">
									<?php if(isset($field['description'])): ?>
										<p class="description"><?php echo $field['description'] ?></p>
									<?php endif; ?>
								</td>
								
							</tr>
						<?php endforeach; ?>
						
					</table>
					
					<?php submit_button(); ?>
					<input type="hidden" name="action" value="dc-submit" />
				</form>
			</div>
		<?php
	}
	
	private function get_form_fields() {
		return array(
			'alias' => array(
				'label' => 'Alias',
				'description' => 'Alias van de identity (default is de interne handle)',
			),
			'company' => array(
				'label' => 'Company',
				'description' => 'Is opgegeven identity een bedrijf (Y / N)',
				'required' => true,
			),
			'company_name' => array(
				'label' => 'Bedrijfsnaam',
			),
			'jobtitle' => array(
				'label' => 'Functie',
			),
			'firstname' => array(
				'label' => 'Voornaam',
				'required' => true,
			),
			'lastname' => array(
				'label' => 'Achternaam',
				'required' => true,
			),
			'street' => array(
				'label' => 'Straat',
				'required' => true,
			),
			'number' => array(
				'label' => 'Huisnummer',
				'required' => true,
			),
			'suffix' => array(
				'label' => 'Toevoeging huisnummer',
			),
			'postalcode' => array(
				'label' => 'Postcode',
				'required' => true,
			),
			'city' => array(
				'label' => 'Plaatsnaam',
				'required' => true,
			),
			'state' => array(
				'label' => 'Provincie',
				'required' => true,
			),
			'tel' => array(
				'label' => 'Telefoonnummer',
				'required' => true,
			),
			'fax' => array(
				'label' => 'Faxnummer',
			),
			'email' => array(
				'label' => 'Emailadress',
				'required' => true,
			),
			'country' => array(
				'label' => 'Landcode',
				'description' => '(bijvoorbeeld NL of BE)',
				'required' => true,
			),
			'datebirth' => array(
				'label' => 'Geboorte datum',
				'description' => 'Geboorte datum van de contactpersoon (DD-MM-YYYY)',
			),
			'placebirth' => array(
				'label' => 'Geboorteplaats',
			),
			'countrybirth' => array(
				'label' => 'Geboortelandcode',
			),
			'idnumber' => array(
				'label' => 'bsn/Sofi nummer',
				'description' => 'Alleen van toepassing op particulieren',
			),
			'regnumber' => array(
				'label' => 'kvk Nummer',
			),
			'vatnumber' => array(
				'label' => 'btw Nummer',
			),
			'tmnumber' => array(
				'label' => 'Trademark nummer',
			),
			'tmcountry' => array(
				'label' => 'Trademark land',
			),
			'idcarddate' => array(
				'label' => 'Legitimatie nummer',
			),
			'idcardissuer' => array(
				'label' => 'Legitimatie uitgever',
				'description' => 'Uitegevende instantie van het legitimatiebewijs',
			),
		);
	}
}