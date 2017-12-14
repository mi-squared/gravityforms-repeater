<?php

class GF_Field_Repeater extends GF_Field {

    const ID_PREFIX = 152;

	public $type = 'repeater';
	public $element = null;
	public $expotrable = false;
	public $exportParent = null;

	public function init_ajax() {
        add_filter('gform_export_fields', array('GF_Field_Repeater', 'gform_export_repeater_fields'), 10, 4);
        add_filter('gform_entries_field_header_pre_export', array('GF_Field_Repeater', 'gform_export_field_header'), 10, 4);
    }

	public static function init_admin() {
		$admin_page = rgget('page');

		if ($admin_page == 'gf_edit_forms' && !empty($_GET['id'])) {
			add_action('gform_field_standard_settings' , array('GF_Field_Repeater', 'gform_standard_settings'), 10, 2);
			add_action('gform_field_appearance_settings' , array('GF_Field_Repeater', 'gform_appearance_settings'), 10, 2);
			add_action('gform_editor_js_set_default_values', array('GF_Field_Repeater', 'gform_set_defaults'));
			add_action('gform_editor_js', array('GF_Field_Repeater', 'gform_editor'));
			add_filter('gform_tooltips', array('GF_Field_Repeater', 'gform_tooltips'));
		}

		if ($admin_page == 'gf_entries') {
			add_filter('gform_form_post_get_meta', array('GF_Field_Repeater', 'gform_hide_children'));
		}
	}

	public static function init_frontend() {
		add_action('gform_form_args', array('GF_Field_Repeater', 'gform_disable_ajax'));
		add_action('gform_enqueue_scripts', array('GF_Field_Repeater', 'gform_enqueue_scripts'), 10, 2);
		add_filter('gform_pre_render', array('GF_Field_Repeater', 'gform_unhide_children_validation'));
		add_filter('gform_pre_validation', array('GF_Field_Repeater', 'gform_bypass_children_validation'));
		add_filter('gform_counter_script', array('GF_Field_Repeater', 'set_counter_script'), 10, 4);

	}

	public static function gform_enqueue_scripts($form, $is_ajax) {
		if (!empty($form)) {
			if (GF_Field_Repeater::get_field_index($form) !== false) {
				wp_register_script('gforms_repeater_postcapture_js', plugins_url('js/jquery.postcapture.min.js', __FILE__), array('jquery'), '0.0.1');
				wp_register_script('gforms_repeater_js', plugins_url('js/gf-repeater.js', __FILE__), array('jquery'), GF_REPEATER_VERSION);
				wp_register_style('gforms_repeater_css', plugins_url('css/gf-repeater.css', __FILE__), array(), GF_REPEATER_VERSION);

				wp_localize_script('gforms_repeater_js', 'gfRepeater_php', array('debug' => GF_REPEATER_DEBUG));

				wp_localize_script('gforms_repeater_js', 'GF_Repeater_Phrases',
					array(
						'field_required'	=> __( 'This field is required.', 'gravityformsrepeater' ),
					)
				);

				wp_enqueue_script('gforms_repeater_postcapture_js');
				wp_enqueue_script('gforms_repeater_js');
				wp_enqueue_style('gforms_repeater_css');
			}
		}
	}

	public function get_form_editor_field_title() {
		return 'Repeater';
	}

	public function get_form_editor_field_settings() {
		return apply_filters('gform_editor_repeater_field_settings',
			array(
				'admin_label_setting',
				'css_class_setting',
				'description_setting',
				'error_message_setting',
				'label_setting',
				'prepopulate_field_setting',
				'conditional_logic_field_setting'
			)
		);
	}

	public function add_button($field_groups) {
		foreach ($field_groups as &$group) {
			if ($group['name'] == 'advanced_fields') {
				$group['fields'][] = array(
					'class'     => 'button',
					'data-type' => 'repeater',
					'value'     => 'Repeater',
					'onclick'   => "StartAddField('repeater');"
				);
				break;
			}
		}

		return $field_groups;
	}

	public static function gform_set_defaults() {
		echo "
			case \"repeater\" :
				field.label = \"Repeater\";
			break;
		";
	}

	public static function gform_standard_settings($position, $form_id) {
		if ($position == 1600) {
			echo "<li class=\"repeater_settings field_setting\">
					<label for=\"field_repeater_start\">Start ";

			gform_tooltip('form_field_repeater_start');

			echo "	</label>
					<input type=\"number\" id=\"field_repeater_start\" min=\"1\" value=\"1\" onchange=\"SetFieldProperty('start', this.value);\">
				</li>";

			echo "<li class=\"repeater_settings field_setting\">
					<label for=\"field_repeater_min\">Min ";

			gform_tooltip('form_field_repeater_min');

			echo "	</label>
					<input type=\"number\" id=\"field_repeater_min\" min=\"1\" value=\"1\" onchange=\"SetFieldProperty('min', this.value);\">
				</li>";

			echo "<li class=\"repeater_settings field_setting\">
					<label for=\"field_repeater_max\">Max ";

			gform_tooltip('form_field_repeater_max');

			echo "	</label>
					<input type=\"number\" id=\"field_repeater_max\" min=\"1\" onchange=\"SetFieldProperty('max', this.value);\">
				</li>";
		}
	}

	public static function gform_appearance_settings($position, $form_id) {
		if ($position == 400) {
			echo "<li class=\"repeater_settings field_setting\">
					<label for=\"field_repeater_animations\">Animation Properties ";

			gform_tooltip('form_field_repeater_animations');

			echo "	</label>
					<input type=\"text\" id=\"field_repeater_animations\" class=\"fieldwidth-3\" onchange=\"SetFieldProperty('animations', this.value);\">
				</li>";

			echo "<li class=\"repeater_settings field_setting\">
					<input type=\"checkbox\" id=\"field_repeater_hideLabel\" onchange=\"SetFieldProperty('hideLabel', this.checked);\"> 
					<label for=\"field_repeater_hideLabel\" class=\"inline\">Hide Label & Description ";

			gform_tooltip('form_field_repeater_hideLabel');

			echo "	</label>
				</li>";
		}
	}

	public static function gform_editor() {
		echo "<script type=\"text/javascript\">
				fieldSettings['repeater'] += ', .repeater_settings';
				jQuery(document).bind('gform_load_field_settings', function(event, field, form){
					jQuery('#field_repeater_start').val(field['start']);
					jQuery('#field_repeater_min').val(field['min']);
					jQuery('#field_repeater_max').val(field['max']);
					jQuery('#field_repeater_animations').val(field['animations']);
					jQuery('#field_repeater_hideLabel').prop('checked', field['hideLabel']);
				});
			</script>";
	}

	public static function gform_tooltips($tooltips) {
		$tooltips['form_field_repeater_start'] = __( "The number of times the repeater will be repeated when the form is rendered. Leaving this field blank or setting it to a number higher than the maximum number is the same as setting it to 1.", 'gravityformsrepeater' );
		$tooltips['form_field_repeater_min'] = __( "The minimum number of times the repeater is allowed to be repeated. Leaving this field blank or setting it to a number higher than the maximum field is the same as setting it to 1.", 'gravityformsrepeater' );
		$tooltips['form_field_repeater_max'] = __( "The maximum number of times the repeater is allowed to be repeated. Leaving this field blank or setting it to a number lower than the minimum field is the same as setting it to unlimited.", 'gravityformsrepeater' );
		$tooltips['form_field_repeater_animations'] = __( "A JavaScript object to be passed for animation settings. For advanced users only. Do not include initial brackets.", 'gravityformsrepeater' );
		$tooltips['form_field_repeater_hideLabel'] = __( "If this is checked, the repeater label and description will not be shown to users on the form.", 'gravityformsrepeater' );
		return $tooltips;
	}

	function validate($value, $form) {
		$repeater_required = $this->repeaterRequiredChildren;

		if (!empty($repeater_required)) {
			$dataArray = json_decode($value, true);

			foreach ($form['fields'] as $key=>$value) {
				$fieldKeys[$value['id']] = $key;

				if (is_array($value['inputs'])) {
					foreach ($value['inputs'] as $inputKey=>$inputValue) {
						$inputKeys[$value['id']][$inputValue['id']] = $inputKey;
					}
				}
			}

			if ($dataArray['repeatCount'] < $this->min) {
				$this->failed_validation  = true;
				$this->validation_message = sprintf( __( "A minimum number of %s is required.", 'gravityformsrepeater' ), $this->min );
				return;
			}

			if ($this->max && $dataArray['repeatCount'] > $this->max) {
				$this->failed_validation  = true;
				$this->validation_message = sprintf( __( "A maximum number of %s is allowed.", 'gravityformsrepeater' ), $this->max );
				return;
			}

			for ($i = 1; $i < $dataArray['repeatCount'] + 1; $i++) {
				foreach ($dataArray['children'] as $field_id=>$field) {
					$repeatSkips = array();

					if (array_key_exists('conditionalLogic', $field)) {
						if (is_array($field['conditionalLogic'])) {
							if (array_key_exists('skip', $field['conditionalLogic'])) {
								$repeatSkips = $field['conditionalLogic']['skip'];
							}
						}
					}

					if (array_key_exists('inputs', $field)) {
						$inputNames = $field['inputs'];
					} else { continue; }

					if (is_array($repeatSkips)) {
						if (in_array($i, $repeatSkips) || in_array('all', $repeatSkips)) { continue; }
					}

					foreach ($inputNames as $inputName) {
						if (is_array($inputName)) { $inputName = reset($inputName); }

						if (substr($inputName, -2) == '[]') {
							$getInputName = substr($inputName, 0, strlen($inputName) - 2).'-'.$dataArray['repeaterId'].'-'.$i;
						} else {
							$getInputName = $inputName.'-'.$dataArray['repeaterId'].'-'.$i;
						}

						$getInputName = str_replace('.', '_', strval($getInputName));
						$getInputData = rgpost($getInputName);
						$getInputIdNum = preg_split("/(_|-)/", $getInputName);

						if (in_array($getInputIdNum[1], $repeater_required)) {
							$fieldKey = $fieldKeys[$getInputIdNum[1]];
							$fieldType = $form['fields'][$fieldKey]['type'];
							$failedValidation = false;

							switch($fieldType) {
								case 'name':
									$requiredIDs = array(3, 6);
									if (in_array($getInputIdNum[2], $requiredIDs) && empty($getInputData)) { $failedValidation = true; }
									break;
								case 'address':
									$skipIDs = array(2);
									if (!in_array($getInputIdNum[2], $skipIDs) && empty($getInputData)) { $failedValidation = true; }
									break;
								default:
									if (empty($getInputData)) { $failedValidation = true; }
							}

							if ($failedValidation) {
								$this->failed_validation  = true;
								if ($this->errorMessage) { $this->validation_message = $this->errorMessage; } else { $this->validation_message = __( "A required field was left blank.", 'gravityformsrepeater' ); }
								return;
							}
						}
					}
				}
			}
		}
	}

	public function get_field_content($value, $force_frontend_label, $form) {
		if (is_admin()) {
			$admin_buttons = $this->get_admin_buttons();
			$field_content = "{$admin_buttons}
							<div class=\"gf-pagebreak-first gf-pagebreak-container gf-repeater gf-repeater-start\">
								<div class=\"gf-pagebreak-text-before\">begin repeater</div>
								<div class=\"gf-pagebreak-text-main\"><span>REPEATER</span></div>
								<div class=\"gf-pagebreak-text-after\">top of repeater</div>
							</div>";
		} else {
			$field_label		= $this->get_field_label($force_frontend_label, $value);
			$description		= $this->get_description($this->description, 'gsection_description gf_repeater_description');
			$hide_label			= $this->hideLabel;
			$validation_message = ( $this->failed_validation && ! empty( $this->validation_message ) ) ? sprintf( "<div class='gfield_description validation_message'>%s</div>", $this->validation_message ) : '';
			if (!empty($field_label)) { $field_label = "<h2 class='gf_repeater_title'>{$field_label}</h2>"; } else { $field_label = ''; }
			if ($hide_label) { $field_label = ''; $description = ''; }
			$field_content = "<div class=\"ginput_container ginput_container_repeater\">{$field_label}{FIELD}</div>{$description}{$validation_message}";
		}
		return $field_content;
	}

	public function get_field_input($form, $value = '', $entry = null) {
		if (is_admin()) {
			return '';
		} else {
			$form_id				= $form['id'];
			$is_entry_detail		= $this->is_entry_detail();
			$is_form_editor			= $this->is_form_editor();
			$id						= (int) $this->id;
			$field_id				= $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
			$tabindex  				= $this->get_tabindex();
			$repeater_id			= $this->repeaterId;
			$repeater_parem			= $this->inputName;
			$repeater_required		= $this->repeaterRequiredChildren;
			$repeater_children		= $this->repeaterChildren;
			$repeater_start			= apply_filters('gf_repeater_start', $this->start, $form, $this);
			$repeater_min			= apply_filters('gf_repeater_min', $this->min, $form, $this);
			$repeater_max			= apply_filters('gf_repeater_max', $this->max, $form, $this);
			$repeater_animations	= apply_filters('gf_repeater_animations', $this->animations, $form, $this);

			if (!empty($repeater_parem)) {
				$repeater_parem_value = GFFormsModel::get_parameter_value($repeater_parem, $value, $this);
				if (!empty($repeater_parem_value)) { $repeater_start = $repeater_parem_value; }
			}

			if (!empty($repeater_children)) {
				$repeater_children_info = array();
				$repeater_parems = GF_Field_Repeater::get_children_parem_values($form, $repeater_children);

				foreach($repeater_children as $repeater_child) {
					$repeater_children_info[$repeater_child] = array();
					$repeater_child_field_index = GF_Field_Repeater::get_field_index($form, 'id', $repeater_child);

					if (!empty($repeater_required)) {
						if (in_array($repeater_child, $repeater_required)) {
							$repeater_children_info[$repeater_child]['required'] = true;
						}
					}

					if (!empty($repeater_parems)) {
						if (array_key_exists($repeater_child, $repeater_parems)) {
							$repeater_children_info[$repeater_child]['prePopulate'] = $repeater_parems[$repeater_child];
						}
					}

					if ($repeater_child_field_index !== false) {
						$repeater_child_field = $form['fields'][$repeater_child_field_index];

						if ($repeater_child_field['inputMask']) {
							$repeater_children_info[$repeater_child]['inputMask'] = $repeater_child_field['inputMaskValue'];
						} elseif ($repeater_child_field['type'] == 'phone' && $repeater_child_field['phoneFormat'] = 'standard') {
							$repeater_children_info[$repeater_child]['inputMask'] = "(999) 999-9999";
						}

						if ($repeater_child_field['conditionalLogic']) {
							$repeater_children_info[$repeater_child]['conditionalLogic'] = $repeater_child_field['conditionalLogic'];
						}

						if ($repeater_child_field['maxLength']) {
							$repeater_children_info[$repeater_child]['maxLength'] = $repeater_child_field['maxLength'];
						}

						if ($repeater_child_field['enableEnhancedUI']) {
							$repeater_children_info[$repeater_child]['enableEnhancedUI'] = $repeater_child_field['enableEnhancedUI'];
						}
					}
				}

				$repeater_children = $repeater_children_info;
			}

			$value = array();
			$value['formId'] = $form_id;
			if (!empty($repeater_start)) { $value['start'] = $repeater_start; }
			if (!empty($repeater_min)) { $value['min'] = $repeater_min; }
			if (!empty($repeater_max)) { $value['max'] = $repeater_max; }
			if (!empty($repeater_children)) { $value['children'] = $repeater_children; }
			$value = json_encode($value);

			if (!empty($repeater_animations)) {
				$animation_script = "var animations={".$repeater_animations."};jQuery.extend(true,gfRepeater_repeaters[".$form_id."][".$repeater_id."].settings.animations,animations);";
				GFFormDisplay::add_init_script($form_id, 'repeater_animations', GFFormDisplay::ON_PAGE_RENDER, $animation_script);
			}

			return sprintf("<input name='input_%d' id='%s' type='hidden' class='gform_repeater' value='%s' %s />", $id, $field_id, $value, $tabindex);
		}
	}

	public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead) {
		$dataArray = json_decode($value, true);
		$value = Array();

		for ($i = 1; $i < $dataArray['repeatCount'] + 1; $i++) {
			$childValue = Array();
			if( !array_key_exists('children', $dataArray) && !isset($dataArray['children']) ){
				continue;
			}
			foreach ($dataArray['children'] as $field_id=>$field) {
				$inputData = Array();

				if (array_key_exists('inputs', $field)) {
					$inputNames = $field['inputs'];
					$repeatSkips = array();

					if (array_key_exists('conditionalLogic', $field)) {
						if (is_array($field['conditionalLogic'])) {
							if (array_key_exists('skip', $field['conditionalLogic'])) {
								$repeatSkips = $field['conditionalLogic']['skip'];
							}
						}
					}

					if (is_array($repeatSkips)) {
						if (in_array($i, $repeatSkips) || in_array('all', $repeatSkips)) { continue; }
					}
					
					if (is_array($inputNames)) {
						foreach ($inputNames as $inputName) {
							if (substr($inputName, -2) == '[]') {
								$getInputName = substr($inputName, 0, strlen($inputName) - 2).'-'.$dataArray['repeaterId'].'-'.$i;
							} else {
								$getInputName = $inputName.'-'.$dataArray['repeaterId'].'-'.$i;
							}

							$input_field_id_num = explode('.', $inputName);
							if (count($input_field_id_num) == 2) { $input_field_id_num = $input_field_id_num[1]; } else { $input_field_id_num = null; }

							$getInputData = rgpost(str_replace('.', '_', strval($getInputName)));

							if (!empty($getInputData)) {
								if (is_array($getInputData)) {
									$inputCount = 0;
									foreach ($getInputData as $theInputData) {
										if (!$input_field_id_num) {
											$inputCount++;
											$inputData[$inputCount] = $theInputData;
										} else {
											$inputData[$input_field_id_num] = $theInputData;
										}
									}
								} else {
									$inputData[1] = $getInputData;
								}
							}
						}
					}
				} else {
					if (GF_Field_Repeater::get_field_type($form, $field_id) == 'section') { $inputData = '[gfRepeater-section]'; }
				}

				$childValue[$field_id] = $inputData;
			}
			$value[$i] = $childValue;
		}

		return maybe_serialize($value);
	}

	public function get_value_entry_list($value, $entry, $field_id, $columns, $form) {
		if (empty($value)) {
			return '';
		} else {
			$dataArray = GFFormsModel::unserialize($value);
			$arrayCount = count($dataArray);
			if ($arrayCount > 1) { $returnText = $arrayCount.' entries'; } else { $returnText = $arrayCount.' entry'; }
			return $returnText;
		}
	}

	public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {
		if (empty($value)) {
			return '';
		} else {
			$dataArray = GFFormsModel::unserialize($value);
			$arrayCount = count($dataArray);
			$output = "\n";
			$count = 0;
			$repeatCount = 0;
			$display_empty_fields = rgget('gf_display_empty_fields', $_COOKIE);
			$form_id = $this->formId;
			$get_form = GFFormsModel::get_form_meta_by_id($form_id);
			$form = $get_form[0];

			foreach ($dataArray as $key=>$value) {
				$repeatCount++;
				$tableContents = '';

				if (empty($value)) { continue; }
				
				if (!empty($value) && !is_array($value)) {
					$save_value = $value;
					unset($value);
					$value[0] = $save_value;
				} elseif (version_compare(phpversion(), '5.3') !== -1) {
					uksort($value, function($a, $b) use($form){
						$a_index = GF_Field_Repeater::get_field_index($form, 'id', $a);
						$b_index = GF_Field_Repeater::get_field_index($form, 'id', $b);
						if ($a_index > $b_index) { return 1; }
						return 0;
					});
				}

				foreach ($value as $childKey=>$childValue) {
					$count++;
					$childValueOutput = '';
					
					if (empty($display_empty_fields) && count($childValue) == 0) { continue; }

					if (is_numeric($childKey)) {
						$field_index = GF_Field_Repeater::get_field_index($form, 'id', $childKey);
						if ($field_index === false) { continue; }
						$entry_title = $form['fields'][$field_index]['label'];
					} else {
						$entry_title = $childKey;
					}

					$entry_title = str_replace('[gfRepeater-count]', $repeatCount, $entry_title);

					if ($format == 'html') {
						if ($childValue == '[gfRepeater-section]') {
							if ($media == 'email') {
								$tableStyling = ' style="font-size:14px;font-weight:bold;background-color:#eee;border-bottom:1px solid #dfdfdf;padding:7px 7px"';
							} else {
								$tableStyling = ' class="entry-view-section-break"';
							}
						} else {
							if ($media == 'email') {
								$tableStyling = ' style="background-color:#EAF2FA;font-family:sans-serif;font-size:12px;font-weight:bold"';
							} else {
								$tableStyling = ' class="entry-view-field-name"';
							}
						}

						$tableContents .= "<tr>\n<td colspan=\"2\"".$tableStyling.">".$entry_title."</td>\n</tr>\n";
					} else {
						$tableContents .= $entry_title.": ";
					}

					if (is_array($childValue)) {
						if (count($childValue) == 1) {
							$childValueOutput = apply_filters('gform_entry_field_value', reset($childValue), $form['fields'][$field_index], array(), $form);
						} elseif (count($childValue) > 1) {
                            if ($form['fields'][$field_index]['type'] == 'date') {
                                $childValueOutput = implode('/', $childValue);                                
                            } else {
                                if ($format == 'html') {
                                    if ($media == 'email') {
                                        $childValueOutput = "<ul style=\"list-style:none;margin:0;padding:0;\">\n";
                                    } else {
                                        $childValueOutput = "<ul>\n";
                                    }
                                }
    
                                foreach ($childValue as $childValueData) {
                                    $childValueData = apply_filters('gform_entry_field_value', $childValueData, $form['fields'][$field_index], array(), $form);
                                    if ($format == 'html') {
                                        $childValueOutput .= "<li>".$childValueData."</li>";
                                    } else {
                                        $childValueOutput .= $childValueData."\n";
                                    }
                                }
                                
                                if ($format == 'html') { $childValueOutput .= "</ul>\n"; }
                            }
						}

						if ($media == 'email') { $tableStyling = ''; } else { $tableStyling = ' class="entry-view-field-value"'; }

						if ($format == 'html') {
							$tableContents .= "<tr>\n<td colspan=\"2\"".$tableStyling.">".$childValueOutput."</td>\n</tr>\n";
						} else {
							$tableContents .= $childValueOutput."\n";
						}
					}
				}

				if (!empty($tableContents)) {
					if ($format == 'html') {
						if ($media == 'email') { $tableStyling = ' width="100%" border="0" cellpadding="5" bgcolor="#FFFFFF"'; } else { $tableStyling = ' class="widefat fixed entry-detail-view"'; }
						$output .= "<table cellspacing=\"0\"".$tableStyling.">\n";
						$output .= $tableContents;
						$output .= "</table>\n";
					} else {
						$output .= $tableContents."\n";
					}
				}
			}
		}

		if ($count !== 0) {
			if ($format == 'text') { $output = rtrim($output); }
			if (GF_REPEATER_DEBUG && $format == 'html') { $output = '<pre style="height:100px;overflow:auto;resize:vertical;">'.print_r($dataArray, true).'</pre>'.$output; }
			return $output;
		} else { return ''; }
	}

	public function get_value_merge_tag($value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br) {
		$output = GF_Field_Repeater::get_value_entry_detail($raw_value, '', false, $format, 'email');
		$output = preg_replace("/[\r\n]+/", "\n", $output);
		return trim($output);
	}

	public function get_value_export($entry, $input_id = '', $use_text = false, $is_csv = false) {
		$realElement = $this->element;
		$output = '';
		if ( $realElement instanceof GF_Field ) {
            $input_id = $realElement->id;
            $parentID = $this->exportParent;
            $repeaterEntry = $entry[$parentID];
            $parentElement = GFFormsModel::unserialize( $repeaterEntry );
            $output = $parentElement[$this->index][$input_id];
        }
		return $output;
	}

    public static function gform_export_field_header( $header, $form, $field )
    {
        return $field->label;
    }

	// Define the columns for export
    public static function gform_export_repeater_fields( $form )
    {
        // Get the Repeater field
        $repeaterIDs = self::get_repeater_ids( $form );
        $repeaterChildrenIDs = [];
        $repeaterMaxCounts = [];
        $formId = (int) $form['id'];
	    $entries = GFAPI::get_entries( $formId );
	    foreach ( $entries as $entry ) {
	        foreach ( $repeaterIDs as $repeaterID ) {
	            $repeaterEntry = $entry[$repeaterID];
	            $unserializedEntry = GFFormsModel::unserialize( $repeaterEntry );
	            // Now count how many repeater entries we have for this from the json
                $repeaterCount = count( $unserializedEntry );
                // Save the max
                if ( isset( $repeaterMaxCounts[$repeaterID]) ) {
                    $repeaterMaxCounts[$repeaterID] = max( $repeaterMaxCounts[$repeaterID], $repeaterCount );
                } else {
                    $repeaterMaxCounts[$repeaterID] = $repeaterCount;
                }
            }
        }

        $repeaters = [];
        foreach ( $repeaterIDs as $repeaterID ) {

            $position = 0;
            foreach ( $form['fields'] as $field ) {

                if ( $field instanceof GF_Field_Repeater &&
                    $repeaterID == $field->id ) {

                    // Found the repeater. Find the children
                    $repeater = new stdClass();
                    $repeater->id = $repeaterID;
                    $repeater->children = $field->repeaterChildren;
                    $repeaterChildrenIDs = array_merge( $repeaterChildrenIDs, $repeater->children );
                    $repeater->position = $position;
                    $repeater->maxCount = $repeaterMaxCounts[$repeaterID];
                    $repeater->elements = []; // Array of child repeaters
                    $repeaters []= $repeater;
                }

                ++$position;
            }
        }

        // repeaterChildren contains an array of arrays with all the repeater children IDs
        // Now we go through and create the labels
        foreach ( $repeaters as $repeater ) {

            // Make a label for each, up to maxcount
            for ( $i = 1; $i <= $repeater->maxCount; ++$i ) {
                foreach ( $repeater->children as $repeaterChild ) {
                    $childField = self::get_field_by_id( $form[ 'fields' ], $repeaterChild );
                    $childField = clone $childField;
                    $gf_rep = new GF_Field_Repeater();
                    $childField->label = $gf_rep->label = __( $childField->label . ' ' . $i );
                    $id = $childField->id.(self::ID_PREFIX + $i);
                    $gf_rep->id = $id;
                    if ( is_array( $childField->inputs ) ) {
                        $inputsClone = $childField->inputs;
                        $ii = 0;
                        foreach ( $childField->inputs as $input ) {
                            $inputsClone[ $ii ][ 'label' ] = $input[ 'label' ];
                            $id = $gf_rep->id.'.'.$ii;
                            $inputsClone[ $ii ][ 'id' ] = $id; // KCC here?
                            $ii++;
                        }
                        $childField->inputs = $inputsClone;

                    }

                    $gf_rep->index = $i;
                    $gf_rep->inputs = $childField->inputs;
                    $gf_rep->element = $childField;
                    $gf_rep->expotrable =  true;
                    $gf_rep->exportParent = $repeater->id;
                    $repeater->elements []= $gf_rep;
                }
            }
        }
        // Go through field labels and splice them in at their position

        foreach ( $repeaters as $repeater ) {
            array_splice( $form['fields'], $repeater->position, 0, $repeater->elements );
        }

        // remove the repeater fields by unsetting their element in "fields" array
        $fieldsCopy = $form['fields'];
        $index = 0;
        while ( $index < count( $form['fields'] ) )  {
            $field = $form['fields'][$index];
            if ( $field instanceof  GF_Field_Repeater &&
                $field->expotrable === false ) {

                while ( !($field instanceof GF_Field_Repeater_End) ) {

                    $field = $form['fields'][$index];
                    unset( $fieldsCopy[$index] );
                    $index++;
                }
            }

            $index++;
        }

        $form['fields'] = $fieldsCopy;

        return $form;
    }

    public static function get_repeater_ids( $form )
    {
        $repeaterIDs = array();
        foreach ( $form[ 'fields' ] as $key => $field ) {
            if ( $field->type == 'repeater' ) {
                $repeaterIDs []= $field->id;

            }
        }

        return $repeaterIDs;
    }

    public static function get_field_by_id( $fields, $field_id )
    {
        $found = null;
        foreach ( $fields as $key => $field ) {
            if ( $field->id == $field_id ) {
                $found = $field;
                break;
            }
        }

        return $found;
    }

	public static function gform_hide_children($form) {
		$form_id = $form['id'];
		$repeaterChildren = Array();
		$grid_modified = false;
		$grid_meta = GFFormsModel::get_grid_column_meta($form_id);

		foreach($form['fields'] as $key=>$field) {
			if ($field->type == 'repeater') {
				if (is_array($field->repeaterChildren)) { $repeaterChildren = array_merge($repeaterChildren, $field->repeaterChildren); }
			} elseif ($field->type == 'repeater-end') { array_push($repeaterChildren, $field->id); }

			if (!empty($repeaterChildren)) {
				if (in_array($field->id, $repeaterChildren)) {
					unset($form['fields'][$key]);

					if (is_array($grid_meta)) {
						$grid_pos = array_search($field->id, $grid_meta);
						if ($grid_pos) {
							$grid_modified = true;
							unset($grid_meta[$grid_pos]);
						}
					}
				}
			}
		}

		if ($grid_modified) { GFFormsModel::update_grid_column_meta($form_id, $grid_meta); }

		$form['fields'] = array_values($form['fields']);

		return $form;
	}

	public static function gform_disable_ajax($args) {
		$get_form = GFFormsModel::get_form_meta_by_id($args['form_id']);
		$form = reset($get_form);

		if (GF_Field_Repeater::get_field_index($form) !== false) {
			$args['ajax'] = false;
		}

		return $args;
	}

	public static function gform_bypass_children_validation($form) {
		if (GF_Field_Repeater::get_field_index($form) === false) { return $form; }

		$repeaterChildren = Array();

		foreach($form['fields'] as $key=>$field) {
			if ($field->type == 'repeater') {
				if (is_array($field->repeaterChildren)) { $repeaterChildren = array_merge($repeaterChildren, $field->repeaterChildren); }
			}

			if (!empty($repeaterChildren)) {
				if (in_array($field->id, $repeaterChildren) && !$field->adminOnly) {
					$form['fields'][$key]['adminOnly'] = true;
					$form['fields'][$key]['repeaterChildValidationHidden'] = true;
				}
			}
		}

		return $form;
	}

	public static function gform_unhide_children_validation($form) {
		if (GF_Field_Repeater::get_field_index($form) === false) { return $form; }
		
		foreach($form['fields'] as $key=>$field) {
			if ($field->repeaterChildValidationHidden) {
				$form['fields'][$key]['adminOnly'] = false;
				$form['fields'][$key]['repeaterChildValidationHidden'] = false;
			}
		}

		return $form;
	}

	public static function get_field_index($form, $key = 'type', $value = 'repeater') {
		if (is_array($form)) {
			if (!array_key_exists('fields', $form)) { return false; }
		} else { return false; }

		foreach ($form['fields'] as $field_key=>$field_value) {
			if (is_object($field_value)) {
				if (property_exists($field_value, $key)) {
					if ($field_value[$key] == $value) { return $field_key; }
				}
			}
		}

		return false;
	}

	public static function get_field_type($form, $id) {
		$field_index = GF_Field_Repeater::get_field_index($form, 'id', $id);
		if ($field_index !== false) { return $form['fields'][$field_index]['type']; }
		return false;
	}

	public static function get_field_id_number($field_id) {
		$field_id = explode('_', $field_id);
		return $field_id[2];
	}

	public static function get_children_parems($form, $children_ids) {
		foreach($form['fields'] as $key=>$value) {
			if (in_array($value['id'], $children_ids)) {
				if ($value['inputName']) {
					$parems[$value['id']] = $value['inputName'];
				} elseif ($value['inputs']) {
					foreach($value['inputs'] as $key=>$value) {
						if ($value['name']) { $parems[$value['id']] = $value['name']; }
					}
				}
			}
		}
		if (!empty($parems)) { return $parems; } else { return false; }
	}

	public static function get_children_parem_values($form, $children_ids) {
		global $wp_filter;
		$children_parems = GF_Field_Repeater::get_children_parems($form, $children_ids);

		if (empty($children_parems)) { return false; }

		// Check the URL first
		foreach($_GET as $url_key=>$url_value) {
			$key = array_search($url_key, $children_parems);
			if ($key !== false) {
				$parems[$key][0] = $url_value;
			} else {
				$split_key = preg_split('/\D+\K/', $url_key);
				$key = array_search($split_key[0], $children_parems);
				if ($key !== false) { $parems[$key][$split_key[1]] = $url_value; }
			}
		}

		// Then check the filters
		foreach($wp_filter as $key=>$value) {
			$split_key = preg_split('/^gform_field_value_+\K/', $key);
			if (!empty($split_key[1])) {
				$key1 = array_search($split_key[1], $children_parems);
				if ($key1 !== false) {
					$parems[$key1][0] = apply_filters($key, '');
				} else {
					$split_key2 = preg_split('/\D+\K/', $split_key[1]);
					$key2 = array_search($split_key2[0], $children_parems);
					if ($key2 !== false) { $parems[$key2][$split_key2[1]] = apply_filters($key, ''); }
				}
			}
		}
		if (!empty($parems)) { return $parems; } else { return false; }
	}

	public static function field_is_repeater_child($form, $field_id) {
		if (GF_Field_Repeater::get_field_index($form) === false) { return false; }

		$fieldIsRepeaterChild = false;
		$repeaterChildren = Array();

		foreach($form['fields'] as $key=>$field) {
			if ($field->type == 'repeater') {
				if (is_array($field->repeaterChildren)) { $repeaterChildren = array_merge($repeaterChildren, $field->repeaterChildren); }
			}

			if (!empty($repeaterChildren)) {
				if ($field->id == $field_id && in_array($field->id, $repeaterChildren)) {
					$fieldIsRepeaterChild = true;
					break;
				}
			}
		}

		return $fieldIsRepeaterChild;
	}

	public static function set_counter_script($script, $form_id, $field_id, $max_length) {
		$get_form = GFFormsModel::get_form_meta_by_id($form_id);
		$form = reset($get_form);
		$field_id_num = GF_Field_Repeater::get_field_id_number($field_id);

		if (GF_Field_Repeater::field_is_repeater_child($form, $field_id_num)) {
			$script = '';
		}

		return $script;
	}


}
GF_Fields::register(new GF_Field_Repeater());
