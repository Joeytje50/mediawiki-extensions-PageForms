<?php
/**
 * File holding the PFComboBoxInput class
 *
 * @file
 * @ingroup PF
 */

/**
 * The PFComboBoxInput class.
 *
 * @ingroup PFFormInput
 */
class PFComboBoxInput extends PFFormInput {
	public static function getName() {
		return 'combobox';
	}

	public static function getOtherPropTypesHandled() {
		return array( '_wpg', '_str' );
	}

	public static function getDefaultCargoTypes() {
		return array( 'Page' => array() );
	}

	public static function getOtherCargoTypesHandled() {
		return array( 'String' );
	}

	public static function getHTML( $cur_value, $input_name, $is_mandatory, $is_disabled, $other_args ) {
		global $wgPageFormsTabIndex, $wgPageFormsFieldNum, $wgPageFormsEDSettings;

		$className = 'pfComboBox';
		if ( $is_mandatory ) {
			$className .= ' mandatoryField';
		}
		if ( array_key_exists( 'class', $other_args ) ) {
			$className .= ' ' . $other_args['class'];
		}

		if ( array_key_exists( 'size', $other_args ) ) {
			$size = $other_args['size'];
		} else {
			$size = '35';
		}
		if ( array_key_exists( 'values from external data', $other_args ) ) {
			$autocompleteSettings = 'external data';
			$remoteDataType = null;
			if ( array_key_exists( 'origName', $other_args ) ) {
				$name = $other_args['origName'];
			} else {
				$name = $input_name;
			}
			$wgPageFormsEDSettings[$name] = array();
			if ( $other_args['values from external data'] != null ) {
				$wgPageFormsEDSettings[$name]['title'] = $other_args['values from external data'];
			}
			if ( array_key_exists( 'image', $other_args ) ) {
				$image_param = $other_args['image'];
				$wgPageFormsEDSettings[$name]['image'] = $image_param;
				global $edgValues;
				for ($i = 0; $i < count($edgValues[$image_param]); $i++) {
					$image = $edgValues[$image_param][$i];
					if ( strpos( $image, "http" ) !== 0 ) {
						$file = wfFindFile( $image );
						if ( $file ) {
							$url = $file->getFullUrl();
							$edgValues[$image_param][$i] = $url;
						} else {
							$edgValues[$image_param][$i] = "";
						}
					}
				}
			}
			if ( array_key_exists( 'description', $other_args ) ) {
				$wgPageFormsEDSettings[$name]['description'] = $other_args['description'];
				if ( !array_key_exists( 'size', $other_args ) ) {
					$size = '80';//Set larger default size if description is also there
				}
			}
		} else {
			list( $autocompleteSettings, $remoteDataType ) = self::setAutocompleteValues( $other_args );
		}

		$inputAttrs = array(
			'type' => 'text',
			'id' => "input_$wgPageFormsFieldNum",
			'name' => $input_name,
			'class' => $className,
			'tabindex' => $wgPageFormsTabIndex,
			'autocompletesettings' => $autocompleteSettings,
			'value' => $cur_value,
			'size' => $size,
			'disabled' => $is_disabled,
		);
		if ( array_key_exists( 'origName', $other_args ) ) {
			$inputAttrs['origname'] = $other_args['origName'];
		}
		if ( array_key_exists( 'existing values only', $other_args ) ) {
			$inputAttrs['existingvaluesonly'] = 'true';
		}
		if ( array_key_exists( 'placeholder', $other_args ) ) {
			$inputAttrs['placeholder'] = $other_args['placeholder'];
		}
		if ( !is_null( $remoteDataType ) ) {
			$inputAttrs['autocompletedatatype'] = $remoteDataType;
		}
 		if ( array_key_exists( 'namespace', $other_args ) ) {
 			$inputAttrs['data-namespace'] = $other_args['namespace'];
 		}

		$inputText = Html::rawElement( 'input', $inputAttrs);

		$divClass = 'ui-widget';
		if ( $is_mandatory ) {
			$divClass .= ' mandatory';
		}

		$text = Html::rawElement( 'div', array( 'class' => $divClass ), $inputText );
		return $text;
	}

	public static function setAutocompleteValues( $field_args ) {
		global $wgPageFormsAutocompleteValues, $wgPageFormsMaxLocalAutocompleteValues;

		list( $autocompleteFieldType, $autocompletionSource ) =
			PFTextWithAutocompleteInput::getAutocompletionTypeAndSource( $field_args );

		$remoteDataType = null;
		if ( $autocompleteFieldType == 'external_url' ) {
			// Autocompletion from URL is always done remotely.
			$remoteDataType = $autocompleteFieldType;
		} elseif ( $autocompletionSource !== '' ) {
			// @TODO - that count() check shouldn't be necessary
			if ( array_key_exists( 'possible_values', $field_args ) &&
			count( $field_args['possible_values'] ) > 0 ) {
				$autocompleteValues = $field_args['possible_values'];
			} elseif ( $autocompleteFieldType == 'values' ) {
				$autocompleteValues = explode( ',', $field_args['values'] );
			} else {
				$autocompleteValues = PFValuesUtils::getAutocompleteValues( $autocompletionSource, $autocompleteFieldType );
			}
			if ( count( $autocompleteValues ) > $wgPageFormsMaxLocalAutocompleteValues &&
			$autocompleteFieldType != 'values' && !array_key_exists( 'values dependent on', $field_args ) && !array_key_exists( 'mapping template', $field_args ) ) {
				$remoteDataType = $autocompleteFieldType;
			} else {
				$wgPageFormsAutocompleteValues[$autocompletionSource] = $autocompleteValues;
			}
		}
		$autocompletionSource = str_replace( "'", "\'", $autocompletionSource );
		return array( $autocompletionSource, $remoteDataType );
	}

	public static function getParameters() {
		$params = parent::getParameters();
		$params[] = array(
			'name' => 'size',
			'type' => 'int',
			'description' => wfMessage( 'pf_forminputs_size' )->text()
		);
		$params = array_merge( $params, PFEnumInput::getValuesParameters() );
		$params[] = array(
			'name' => 'existing values only',
			'type' => 'boolean',
			'description' => wfMessage( 'pf_forminputs_existingvaluesonly' )->text()
		);
		return $params;
	}

	/**
	 * Returns the HTML code to be included in the output page for this input.
	 */
	public function getHtmlText() {
		return self::getHTML(
			$this->mCurrentValue,
			$this->mInputName,
			$this->mIsMandatory,
			$this->mIsDisabled,
			$this->mOtherArgs
		);
	}
}
