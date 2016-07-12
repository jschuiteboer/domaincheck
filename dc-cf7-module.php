<?php
	/**
	 * Contact Form 7 Module
	 * Adds [domaincheck] and [domaincheck*] shortcodes and all functionality
	 * surrounding it.
	 */
	class DC_WPCF7_Module {
		private static $SHORTCODES = array('domaincheck', 'domaincheck*');
		
		public function __construct() {
			wpcf7_add_shortcode(self::$SHORTCODES, array($this, 'do_domaincheck_shortcode'), true);
			
			add_action('admin_init', array($this, 'admin_init_action'), 60);
			
			add_filter('wpcf7_ajax_json_echo', array($this, 'wpcf7_ajax_json_echo_filter'), 10, 2);
			
			add_filter('wpcf7_validate_' . 'domaincheck*', array($this, 'wpcf7_validate_domaincheck_filter'), 10, 2 );
			
			add_action('wpcf7_before_send_mail', array($this, 'wpcf7_before_send_mail_action'));
			
			add_filter('wpcf7_special_mail_tags', array($this, 'wpcf7_special_mail_tags'), 10, 3);
		}
		
		/**
		 * Adds a tag generator for the contact form 7 backend.
		 * Callback for the admin_init hook.
		 */
		public function admin_init_action() {
			wpcf7_add_tag_generator('domaincheck', 'Domaincheck', 'dc-tg-pane-domaincheck', array($this, 'do_tag_generator_domaincheck'));
		}
		
		/**
		 * Adds functionality for the [domaincheck_identity_link] mail tag; a
		 * tag that outputs a link to the identity admin page.
		 * 
		 * Callback for the wpcf7_special_mail_tags hook.
		 */
		public function wpcf7_special_mail_tags($output, $name, $html) {
			if($name == 'domaincheck_identity_link') {
				$output = $this->get_identity_form_link(wpcf7_get_current_contact_form());
			}
			
			return $output;
		}
		
		private function get_identity_form_link($form) {
			$args = array();
			$tags = $form->form_scan_shortcode();
			
			foreach($tags as $tag) {
				$value = urlencode($form->replace_mail_tags('[' . $tag['name'] . ']'));
				
				$args[$tag['name']] = $value;
			}
						
			$args['page'] = 'dc-identity';
			
			return add_query_arg($args, 'http://acservices.nl/wp-admin/admin.php');
		}

		/**
		 * Because we use some javascript trickery to redirect to a seccond form.
		 * We need to skip the email message when the form is submitted.
		 * 
		 * Callback for the wpcf7_before_send_mail hook.
		 */
		public function wpcf7_before_send_mail_action($form) {
			$tag = $form->form_scan_shortcode(array('type' => self::$SHORTCODES));
			
			// no nothing if the tag was not found.
			if(empty($tag)) return;
			
			// only look at the first tag.
			$tag = new WPCF7_Shortcode($tag[0]);
			
			// no nothing if the form is a domaincheck landing page.
			if($tag->has_option('landingpage')) return;
			
			$form->skip_mail = true;
		}
		
		/**
		 * Add some extra output to the contact form 7 ajax response.
		 * Specifically to 'onSentOk' hook which will be excecuted by the
		 * browser if the form has been filled in succesfully.
		 * 
		 * Callback for the wpcf7_ajax_json_echo hook.
		 */
		public function wpcf7_ajax_json_echo_filter($items, $result) {
			if(!is_array($items)) return $items;
			
			if($items['mailSent'] == false) return $items;
			
			// check if the form has a domaincheck tag
			$tag = wpcf7_scan_shortcode(array('type' => self::$SHORTCODES));
			if(empty($tag)) return $items;
			
			// only use the first shortcode
			$tag = new WPCF7_Shortcode($tag[0]);
			
			// do nothing if the page is a domaincheck landing page
			if($tag->has_option('landingpage')) return $items;
			
			$domainParts = $this->get_domain_parts($_POST[$tag->name]);
			
			$script = sprintf("initDCForm(\$form, %s, '%s');", json_encode($this->get_extensions($domainParts['tld'])), $domainParts['sld']);

			$items['onSentOk'][] = wpcf7_strip_quote($script);

			return $items;
		}
		
		/**
		 * Will return an array of extensions.
		 * @param string $tld - will move or add the specified tld to the start
		 *                      of the array.
		 * @return array of strings
		 */
		private function get_extensions($tld = null) {
			$extensions = explode("\n", Domaincheck::getInstance()->get_setting_value('dc_tld_list'));

			if($tld != null) {
				// search for the tld, if found remove it
				$pos = array_search($tld, $extensions);				
				if($pos !== false) unset($extensions[$pos]);
				
				// prepend to the array
				array_unshift($extensions, $tld);
			}
			
			return $extensions;
		}
		
		/**
		 * Will split the specified url into parts containing the top level
		 * domain (tld) and the second level domain (sld).
		 * 
		 * www.example.com = array('tld' => 'com',     'sld' => 'example')
		 *     example.com = array('tld' => 'com',     'sld' => 'example')
		 *     example     = array('tld' => '',        'sld' => 'example')
		 * www.example     = array('tld' => 'example', 'sld' => 'www') // malformed url
		 * 
		 * @param string $url
		 * @return array
		 */
		private function get_domain_parts($url) {
			$out = array('tld' => '', 'sld' => '');

			if(stristr($url, '.')) {
				$parts = explode('.', $url);

				$out['tld'] = array_pop($parts);
				$out['sld'] = array_pop($parts);			
			} else {
				$out['sld'] = $url;
			}

			return $out;
		}
		
		/**
		 * Validation function for the domaincheck shortcode, which basically
		 * behaves like a text shortcode.
		 * 
		 * Callback for the wpcf7_validate_domaincheck* hook.
		 */
		public function wpcf7_validate_domaincheck_filter($result, $tag) {
			$tag = new WPCF7_Shortcode($tag);
			
			// do nothing if the page is a landing page
			if($tag->has_option('landingpage')) return $result;
			
			$value = '';
			
			if(isset($_POST[$tag->name])) {
				$value = trim(wp_unslash(strtr((string) $_POST[$tag->name], "\n", " ")));
			}

			if($tag->type == 'domaincheck*' && $value == '') {
				$result['valid'] = false;
				$result['reason'][$tag->name] = wpcf7_get_message('invalid_required');
			}
			
			if(isset($result['reason'][$tag->name]) && $id = $tag->get_id_option()) {
				$result['idref'][$tag->name] = $id;
			}
			
			return $result;
		}
		
		/**
		 * Generates the html for the domaincheck shortcode.
		 * Callback for the wpcf7_add_shortcode function.
		 */
		public function do_domaincheck_shortcode($tag) {
			$tag = new WPCF7_Shortcode($tag);

			wp_enqueue_style('domaincheck', plugins_url('includes/dc-style.css', __FILE__));
			
			if($tag->has_option('landingpage')) {
				$tlds = isset($_POST['tld']) ? $_POST['tld'] : false;
				$sld = isset($_POST['sld']) ? $_POST['sld'] : false;
				
				if(!is_array($tlds) || !$sld) return '';
				
				$html  = '<ul>';
				foreach($tlds as $tld => $v) {
					$html .= '<li>' . $sld . '.' . $tld . '</li>';
					$html .= sprintf('<input type="hidden" name="%1$s[%3$s]" value="%2$s.%3$s" />', $tag->name, $sld, $tld);
				}
				$html .= '</ul>';
				
				return $html;
			} else {
				$redirect = $tag->get_option('redirect', '', true);
				if($redirect === false) $redirect = '';
				
				$js_objects = array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'redirectTo' => $redirect,
				);

				wp_enqueue_script('domaincheck', plugins_url('includes/dc-script.js', __FILE__), array('jquery', 'jquery-form'));
				wp_localize_script('domaincheck', '_dc', $js_objects);

				// pretend we're a text shortcode.
				$tag->basetype = 'text';
				$html = wpcf7_text_shortcode_handler($tag);

				return $html;
			}
		}
		
		/**
		 * Ouputs a tag generator for the contact form 7 backend.
		 * Callback for the wpcf7_add_tag_generator function.
		 */
		public function do_tag_generator_domaincheck() {
			?>
			<div id="dc-tg-pane-domaincheck" class="hidden">
				<form action="">					
					<table>
						<tr>
							<td>
								<input type="checkbox" name="required" checked/>&nbsp;<?php echo esc_html(__('Required field?', 'contact-form-7')); ?>
							</td>
						</tr>
						<tr>
							<td>
								<?php echo esc_html(__('Name', 'contact-form-7')); ?><br />
								<input type="text" name="name" class="tg-name oneline" />
							</td>
							<td></td>
						</tr>
					</table>

					<table>
						<tr>
							<td>
								<code>id</code> (<?php echo esc_html(__('optional', 'contact-form-7')); ?>)<br />
								<input type="text" name="id" class="idvalue oneline option" />
							</td>
							<td>
								<code>class</code> (<?php echo esc_html(__( 'optional', 'contact-form-7')); ?>)<br />
								<input type="text" name="class" class="classvalue oneline option" />
							</td>
						</tr>
						<tr>
							<td>
								<code>size</code> (<?php echo esc_html(__('optional', 'contact-form-7')); ?>)<br />
								<input type="number" name="size" class="numeric oneline option" min="1" />
							</td>
							<td>
								<code>maxlength</code> (<?php echo esc_html(__('optional', 'contact-form-7')); ?>)<br />
								<input type="number" name="maxlength" class="numeric oneline option" min="1" />
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<?php echo esc_html(__('Akismet', 'contact-form-7')); ?> (<?php echo esc_html(__('optional', 'contact-form-7')); ?>)<br />
								<input type="checkbox" name="akismet:author_url" class="option" />&nbsp;<?php echo esc_html(__("This field requires author's URL", 'contact-form-7')); ?>
							</td>
						</tr>
						<tr>
							<td>
								<?php echo esc_html(__('Default value', 'contact-form-7')); ?> (<?php echo esc_html(__('optional', 'contact-form-7')); ?>)<br />
								<input type="text" name="values" class="oneline" />
							</td>
							<td>
								<br />
								<input type="checkbox" name="placeholder" class="option" />&nbsp;<?php echo esc_html(__('Use this text as placeholder?', 'contact-form-7')); ?>
							</td>
						</tr>
						<tr>
							<td>
								<code>redirect</code> (<?php echo esc_html(__('optional', 'contact-form-7')); ?>)<br />
								<input type="text" name="redirect" class="oneline option" />
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" name="landingpage" class="option" />&nbsp;Dit formulier word gebruikt als domaincheck landings pagina.
							</td>
						</tr>
					</table>

					<div class="tg-tag">
						<?php echo esc_html(__('Copy this code and paste it into the form left.', 'contact-form-7')); ?><br />
						<input type="text" name="domaincheck" class="tag wp-ui-text-highlight code" readonly="readonly" onfocus="this.select()" />
					</div>

					<div class="tg-mail-tag">
						<?php echo esc_html(__('And, put this code into the Mail fields below.', 'contact-form-7')); ?><br />
						<input type="text" class="mail-tag wp-ui-text-highlight code" readonly="readonly" onfocus="this.select()" />
					</div>
				</form>
			</div>
			<?php
		}
	}