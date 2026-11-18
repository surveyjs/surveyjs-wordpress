<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Gravity_Forms_Importer extends SurveyJS_Importer {

    const NAME_PARTS = array( '2' => 'prefix', '3' => 'first', '4' => 'middle', '6' => 'last', '8' => 'suffix' );

    const ADDRESS_PARTS = array( '1' => 'street', '2' => 'line2', '3' => 'city', '4' => 'state', '5' => 'zip', '6' => 'country' );

    const PRICING_TYPES = array( 'product', 'option', 'quantity', 'shipping', 'total', 'donation', 'creditcard', 'calculation', 'singleproduct', 'singleshipping', 'hiddenproduct', 'price' );

    const POST_TYPES = array( 'post_title' => 'text', 'post_content' => 'textarea', 'post_excerpt' => 'textarea', 'post_tags' => 'text', 'post_custom_field' => 'text' );

    const REF_DEFAULTS = array( 'separator' => '', 'email' => false, 'multiple' => false, 'decimal_comma' => false, 'columns' => array() );

    const CAPTCHAS = array( 'recaptcha' => 'Google reCAPTCHA', 'turnstile' => 'Cloudflare Turnstile', 'hcaptcha' => 'hCaptcha' );

    const REQUIRED_PARTS = array( 'name' => array( '3', '6' ), 'address' => array( '1', '3', '4', '5', '6' ) );

    const USER_MERGE_TAGS = array(
        'ID'           => 'id',
        'user_login'   => 'login',
        'user_email'   => 'email',
        'user_url'     => 'url',
        'display_name' => 'display_name',
        'first_name'   => 'first_name',
        'last_name'    => 'last_name',
        'nickname'     => 'nickname',
    );

    const COUNTRIES = array(
        'AF' => 'Afghanistan', 'AX' => 'Åland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola',
        'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua and Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia',
        'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus',
        'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia',
        'BQ' => 'Bonaire, Sint Eustatius and Saba', 'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi',
        'CV' => 'Cabo Verde', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic',
        'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos Islands', 'CO' => 'Colombia', 'KM' => 'Comoros',
        'CG' => 'Congo', 'CD' => 'Congo, Democratic Republic of the', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Côte d\'Ivoire',
        'HR' => 'Croatia', 'CU' => 'Cuba', 'CW' => 'Curaçao', 'CY' => 'Cyprus', 'CZ' => 'Czechia', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica',
        'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea',
        'EE' => 'Estonia', 'SZ' => 'Eswatini', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland',
        'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia',
        'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada',
        'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana',
        'HT' => 'Haiti', 'HM' => 'Heard Island and McDonald Islands', 'VA' => 'Holy See', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary',
        'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle of Man', 'IL' => 'Israel',
        'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati',
        'KP' => 'Korea, Democratic People\'s Republic of', 'KR' => 'Korea, Republic of', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan',
        'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libya',
        'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia',
        'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius',
        'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro',
        'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal',
        'NL' => 'Netherlands', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue',
        'NF' => 'Norfolk Island', 'MK' => 'North Macedonia', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan',
        'PW' => 'Palau', 'PS' => 'Palestine, State of', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru',
        'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Réunion',
        'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'BL' => 'Saint Barthélemy', 'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
        'KN' => 'Saint Kitts and Nevis', 'LC' => 'Saint Lucia', 'MF' => 'Saint Martin', 'PM' => 'Saint Pierre and Miquelon',
        'VC' => 'Saint Vincent and the Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome and Principe', 'SA' => 'Saudi Arabia',
        'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SX' => 'Sint Maarten', 'SK' => 'Slovakia',
        'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia and the South Sandwich Islands',
        'SS' => 'South Sudan', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard and Jan Mayen', 'SE' => 'Sweden',
        'CH' => 'Switzerland', 'SY' => 'Syria Arab Republic', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania, the United Republic of',
        'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia',
        'TR' => 'Türkiye', 'TM' => 'Turkmenistan', 'TC' => 'Turks and Caicos Islands', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States', 'UY' => 'Uruguay', 'UM' => 'US Minor Outlying Islands',
        'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela', 'VN' => 'Viet Nam', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.',
        'WF' => 'Wallis and Futuna', 'EH' => 'Western Sahara', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe',
    );

    const US_STATES = array(
        'Alabama', 'Alaska', 'American Samoa', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'District of Columbia', 'Florida',
        'Georgia', 'Guam', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts',
        'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York',
        'North Carolina', 'North Dakota', 'Northern Mariana Islands', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Puerto Rico', 'Rhode Island',
        'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'U.S. Virgin Islands', 'Vermont', 'Virginia', 'Washington', 'West Virginia',
        'Wisconsin', 'Wyoming', 'Armed Forces Americas', 'Armed Forces Europe', 'Armed Forces Pacific',
    );

    const CANADIAN_PROVINCES = array(
        'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador', 'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario',
        'Prince Edward Island', 'Quebec', 'Saskatchewan', 'Yukon',
    );

    private $title = '';
    private $foreign = false;
    private $taken = array();
    private $refs = array();
    private $conditions = array();
    private $skipped = array();
    private $notes = array();
    private $json = array();
    private $captcha = '';
    private $form = array();
    private $css_classes = array();

    public static function source() {
        return 'gravityforms';
    }

    public static function is_available() {
        return self::table_exists( 'gf_form' ) && self::table_exists( 'gf_form_meta' );
    }

    public static function forms() {
        global $wpdb;
        $forms = array();
        if ( ! self::is_available() ) {
            return $forms;
        }
        foreach ( $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}gf_form WHERE is_trash = 0 ORDER BY id" ) as $row ) {
            $forms[ (int) $row->id ] = $row->title;
        }
        return $forms;
    }

    protected static function convert_form( $form_id ) {
        global $wpdb;
        $row = self::is_available() ? $wpdb->get_row( $wpdb->prepare( "SELECT f.title, m.display_meta, m.confirmations, m.notifications FROM {$wpdb->prefix}gf_form f INNER JOIN {$wpdb->prefix}gf_form_meta m ON m.form_id = f.id WHERE f.id = %d AND f.is_trash = 0", $form_id ) ) : null;
        $form = $row ? json_decode( (string) $row->display_meta, true ) : null;
        if ( ! is_array( $form ) ) {
            return new WP_Error( 'not_found', __( 'The Gravity Forms form was not found.', 'surveyjs' ) );
        }
        foreach ( array( 'confirmations', 'notifications' ) as $column ) {
            $value = json_decode( (string) $row->$column, true );
            if ( is_array( $value ) ) {
                $form[ $column ] = $value;
            }
        }
        $form['title'] = $row->title;
        return self::convert_meta( $form, false );
    }

    protected static function convert_meta( $form, $foreign ) {
        $title = self::form_name( $form );
        return array( 'name' => $title ) + ( new self() )->convert( $form, $title, $foreign );
    }

    protected static function form_name( $form ) {
        $title = self::text( $form['title'] ?? '' );
        /* translators: %d: Gravity Forms form ID */
        return '' !== $title ? $title : sprintf( __( 'Gravity Forms form %d', 'surveyjs' ), (int) ( $form['id'] ?? 0 ) );
    }

    public static function entries_available() {
        return self::is_available() && self::table_exists( 'gf_entry' ) && self::table_exists( 'gf_entry_meta' );
    }

    protected static function copies_entry_files() {
        return true;
    }

    protected static function entry_dates( $form_id, $entry_ids ) {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, date_created FROM {$wpdb->prefix}gf_entry WHERE id IN ( " . implode( ', ', array_fill( 0, count( $entry_ids ), '%d' ) ) . ' )', array_map( 'intval', $entry_ids ) ) );
        $dates = array();
        foreach ( $rows as $row ) {
            $dates[ (int) $row->id ] = self::gmt_date( $row->date_created );
        }
        return array_filter( $dates );
    }

    protected static function entries_page( $form_id, $form, $offset, $limit ) {
        global $wpdb;
        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}gf_entry WHERE form_id = %d AND status = 'active'", $form_id ) );
        $ids = $limit
            ? $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}gf_entry WHERE form_id = %d AND status = 'active' ORDER BY id LIMIT %d OFFSET %d", $form_id, $limit, $offset ) )
            : $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}gf_entry WHERE form_id = %d AND status = 'active' ORDER BY id", $form_id ) );
        $ids = array_map( 'intval', $ids );
        $metas = array_fill_keys( $ids, array() );
        if ( $ids ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT entry_id, meta_key, meta_value FROM {$wpdb->prefix}gf_entry_meta WHERE entry_id IN ( " . implode( ', ', array_fill( 0, count( $ids ), '%d' ) ) . " ) AND ( item_index IS NULL OR item_index = '' ) ORDER BY id",
                $ids
            ) );
            foreach ( $rows as $row ) {
                $metas[ (int) $row->entry_id ][ (string) $row->meta_key ] = (string) $row->meta_value;
            }
        }
        $questions = array_column( SurveyJS_Survey_Values::survey_questions( $form['json'] ), null, 'name' );
        $entries = array();
        foreach ( $metas as $entry_id => $meta ) {
            $data = self::entry_data( $form['refs'], $questions, $meta );
            if ( $data ) {
                $entries[ $entry_id ] = $data;
            }
        }
        return array( 'entries' => $entries, 'total' => $total );
    }

    private static function entry_data( $refs, $questions, $meta ) {
        $data = array();
        foreach ( $refs as $field_id => $ref ) {
            if ( 'parts' === $ref['kind'] ) {
                foreach ( $ref['parts'] as $suffix => $name ) {
                    if ( isset( $questions[ $name ], $meta[ $field_id . '.' . $suffix ] ) ) {
                        $data += self::entry_value( $questions[ $name ], $meta[ $field_id . '.' . $suffix ] );
                    }
                }
                continue;
            }
            $name = $ref['parts']['value'];
            if ( ! isset( $questions[ $name ] ) ) {
                continue;
            }
            if ( in_array( $ref['kind'], array( 'checkbox', 'consent', 'likert_rows' ), true ) ) {
                $value = array();
                foreach ( $meta as $key => $item ) {
                    if ( 0 === strpos( (string) $key, $field_id . '.' ) && '' !== $item ) {
                        $value[ substr( (string) $key, strlen( $field_id ) + 1 ) ] = $item;
                    }
                }
                ksort( $value, SORT_NATURAL );
            } elseif ( isset( $meta[ $field_id ] ) ) {
                $value = $meta[ $field_id ];
            } else {
                continue;
            }
            $data += self::entry_field_value( $questions[ $name ], $ref, $value );
        }
        return $data;
    }

    private static function entry_field_value( $question, $ref, $value ) {
        $name = $question['name'];
        switch ( $ref['kind'] ) {
            case 'checkbox':
                return self::entry_value( $question, array_values( $value ) );

            case 'multiselect':
                $items = '[' === substr( $value, 0, 1 ) ? json_decode( $value, true ) : explode( ',', $value );
                return self::entry_value( $question, is_array( $items ) ? $items : array() );

            case 'consent':
                return '1' === trim( (string) ( $value['1'] ?? '' ) ) ? array( $name => true ) : array();

            case 'date':
                return preg_match( '/^\d{4}-\d{2}-\d{2}$/', trim( $value ) ) ? array( $name => trim( $value ) ) : self::entry_value( $question, $value );

            case 'time':
                if ( ! preg_match( '/^(\d{1,2}):(\d{2})\s*(am|pm)?$/i', trim( $value ), $time ) ) {
                    return self::entry_value( $question, $value );
                }
                $hour = (int) $time[1] % ( empty( $time[3] ) ? 24 : 12 ) + ( 'pm' === strtolower( $time[3] ?? '' ) ? 12 : 0 );
                return array( $name => sprintf( '%02d:%02d', $hour, (int) $time[2] ) );

            case 'number':
                $number = trim( $value );
                if ( ! is_numeric( $number ) && $ref['decimal_comma'] ) {
                    $number = str_replace( array( '.', ',' ), array( '', '.' ), $number );
                }
                return is_numeric( $number ) ? array( $name => 0 + $number ) : array();

            case 'file':
                $urls = '[' === substr( trim( $value ), 0, 1 ) ? json_decode( $value, true ) : array( $value );
                $files = array();
                foreach ( array_filter( (array) $urls, 'is_string' ) as $url ) {
                    $url = esc_url_raw( trim( $url ) );
                    if ( '' !== $url ) {
                        $files[] = array(
                            'name'    => rawurldecode( wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ),
                            'type'    => (string) ( wp_check_filetype( $url )['type'] ?? '' ),
                            'content' => $url,
                        );
                    }
                }
                return $files ? array( $name => $files ) : array();

            case 'list':
                $rows = array();
                foreach ( self::list_rows( $value ) as $row ) {
                    $item = array();
                    foreach ( (array) $row as $column => $cell ) {
                        $column_name = $ref['columns'][ is_array( $row ) ? (string) $column : '' ] ?? null;
                        if ( null !== $column_name && is_scalar( $cell ) && '' !== trim( (string) $cell ) ) {
                            $item[ $column_name ] = (string) $cell;
                        }
                    }
                    if ( $item ) {
                        $rows[] = $item;
                    }
                }
                return $rows ? array( $name => $rows ) : array();

            case 'likert_rows':
                $rows = array_column( $question['rows'] ?? array(), 'value' );
                $columns = array_column( $question['columns'] ?? array(), 'value' );
                $matrix = array();
                foreach ( $value as $item ) {
                    $pair = explode( ':', (string) $item, 2 );
                    if ( 2 === count( $pair ) && in_array( $pair[0], $rows, true ) && in_array( $pair[1], $columns, true ) ) {
                        $matrix[ $pair[0] ] = $pair[1];
                    }
                }
                return $matrix ? array( $name => $matrix ) : array();

            case 'rank':
                $items = array_values( array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) );
                return $items ? array( $name => $items ) : array();

            case 'rating':
                $values = array_map( 'strval', array_column( $question['rateValues'] ?? array(), 'value' ) );
                return in_array( trim( $value ), $values, true ) ? array( $name => trim( $value ) ) : array();

            case 'signature':
                $image = self::signature_image( $value );
                return '' !== $image ? array( $name => $image ) : array();
        }
        return self::entry_value( $question, $value );
    }

    private static function list_rows( $value ) {
        if ( ! is_serialized( $value ) ) {
            return array();
        }
        $rows = unserialize( trim( $value ), array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Gravity Forms stores List values serialized; allowed_classes => false rules out object injection.
        return is_array( $rows ) ? $rows : array();
    }

    private static function signature_image( $file_name ) {
        $file_name = trim( (string) $file_name );
        if ( ! preg_match( '/^[A-Za-z0-9_.-]+\.png$/', $file_name ) ) {
            return '';
        }
        $upload_dir = wp_upload_dir( null, false );
        $path = $upload_dir['basedir'] . '/gravity_forms/signatures/' . $file_name;
        if ( ! empty( $upload_dir['error'] ) || ( ! wp_is_stream( $path ) && is_link( $path ) ) || ! is_file( $path ) || ! is_readable( $path ) ) {
            return '';
        }
        $contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- a local file in the Gravity Forms signatures folder, never a URL.
        return false === $contents || '' === $contents ? '' : 'data:image/png;base64,' . base64_encode( $contents ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- a signature pad value is a data URL.
    }

    public function convert( $form, $title, $foreign ) {
        $this->title = $title;
        $this->foreign = $foreign;
        $this->form = $form;
        $this->css_class( $form['cssClass'] ?? '' );
        $fields = array_values( array_filter( (array) ( $form['fields'] ?? array() ), 'is_array' ) );
        $group_sizes = array_count_values( array_filter( array_map( function ( $field ) {
            return is_scalar( $field['layoutGroupId'] ?? null ) ? (string) $field['layoutGroupId'] : '';
        }, $fields ), 'strlen' ) );

        $pages = array( array( 'name' => 'page1', 'elements' => array() ) );
        $panel = null;
        $previous_group = '';
        foreach ( $fields as $field ) {
            $type = (string) ( $field['type'] ?? '' );
            $current = count( $pages ) - 1;
            if ( 'page' === $type ) {
                $pages[] = array( 'name' => 'page' . ( count( $pages ) + 1 ), 'elements' => array() );
                $this->condition( 'page' . count( $pages ), $field, __( 'Page', 'surveyjs' ) . ' ' . count( $pages ) );
                $this->button_text( 'pageNextText', $field['nextButton'] ?? null );
                $this->button_text( 'pagePrevText', $field['previousButton'] ?? null );
                if ( self::condition_active( $field['nextButton']['conditionalLogic'] ?? null ) ) {
                    $this->notes[] = __( 'The conditional logic of the Next button was not imported.', 'surveyjs' );
                }
                $panel = null;
                $previous_group = '';
                continue;
            }
            if ( 'section' === $type ) {
                $label = self::text( $field['label'] ?? '' );
                $section = array( 'type' => 'panel', 'name' => $this->unique_name( '' !== $label ? $label : 'section' ), 'elements' => array() );
                if ( '' !== $label ) {
                    $section['title'] = $label;
                }
                if ( '' !== self::text( $field['description'] ?? '' ) ) {
                    $section['description'] = self::text( $field['description'] );
                }
                if ( in_array( (string) ( $field['visibility'] ?? '' ), array( 'hidden', 'administrative' ), true ) ) {
                    $section['visible'] = false;
                }
                $this->condition( $section['name'], $field, $label );
                $pages[ $current ]['elements'][] = $section;
                $panel = count( $pages[ $current ]['elements'] ) - 1;
                $previous_group = '';
                continue;
            }
            $elements = $this->elements( $field );
            if ( ! $elements ) {
                continue;
            }
            $group = is_scalar( $field['layoutGroupId'] ?? null ) ? (string) $field['layoutGroupId'] : '';
            if ( '' !== $group && $group === $previous_group ) {
                $elements[0]['startWithNewLine'] = false;
            }
            $span = (int) ( $field['layoutGridColumnSpan'] ?? 0 );
            if ( '' !== $group && ( $group_sizes[ $group ] ?? 0 ) > 1 && 1 === count( $elements ) && $span > 0 && $span < 12 ) {
                $elements[0]['width'] = round( $span / 12 * 100, 2 ) . '%';
                // Without it SurveyJS keeps a 300px minimum and wraps three or more columns that fit in Gravity Forms.
                $elements[0]['minWidth'] = 'auto';
            }
            $previous_group = $group;
            if ( null === $panel ) {
                array_push( $pages[ $current ]['elements'], ...$elements );
            } else {
                array_push( $pages[ $current ]['elements'][ $panel ]['elements'], ...$elements );
            }
        }

        $button = (array) ( $form['button'] ?? array() );
        $this->button_text( 'completeText', $button );
        if ( self::condition_active( $button['conditionalLogic'] ?? null ) ) {
            $this->notes[] = __( 'The conditional logic of the submit button was not imported.', 'surveyjs' );
        }
        $this->button_text( 'pagePrevText', $form['lastPageButton'] ?? null );

        $pagination = (array) ( $form['pagination'] ?? array() );
        foreach ( array_values( (array) ( $pagination['pages'] ?? array() ) ) as $index => $page_title ) {
            if ( isset( $pages[ $index ] ) && '' !== self::text( $page_title ) ) {
                $pages[ $index ]['navigationTitle'] = self::text( $page_title );
            }
        }
        $indicator = (string) ( $pagination['type'] ?? '' );
        if ( in_array( $indicator, array( 'percentage', 'steps' ), true ) ) {
            $this->json['showProgressBar'] = true;
            if ( 'steps' === $indicator ) {
                $this->json['progressBarType'] = 'buttons';
            }
        }

        foreach ( $pages as &$page ) {
            $page['elements'] = $this->empty_sections( $page['elements'] );
        }
        unset( $page );
        $pages = array_values( array_filter( $pages, function ( $page ) {
            return ! empty( $page['elements'] );
        } ) );
        $pages = $this->apply_conditions( $pages );
        $json = array();
        if ( count( $pages ) > 1 ) {
            $json['pages'] = $pages;
        } else {
            $json['elements'] = $pages ? $pages[0]['elements'] : array();
            if ( isset( $pages[0]['visibleIf'] ) ) {
                $this->notes[] = __( 'The conditional logic of the only page was not imported.', 'surveyjs' );
            }
            unset( $this->json['showProgressBar'], $this->json['progressBarType'], $this->json['pageNextText'], $this->json['pagePrevText'] );
        }
        $json += $this->json;

        if ( '' !== self::text( $form['description'] ?? '' ) ) {
            $json['description'] = self::text( $form['description'] );
        }
        if ( in_array( (string) ( $form['labelPlacement'] ?? '' ), array( 'left_label', 'right_label' ), true ) ) {
            $json['questionTitleLocation'] = 'left';
        }
        if ( ! $this->description_above( array() ) ) {
            $json['questionDescriptionLocation'] = 'underInput';
        }
        $this->confirmation( array_filter( (array) ( $form['confirmations'] ?? array() ), 'is_array' ), $json );
        $restrictions = array(
            'requireLogin' => __( 'Require user to be logged in', 'surveyjs' ),
            'limitEntries' => __( 'Limit number of entries', 'surveyjs' ),
            'scheduleForm' => __( 'Schedule form', 'surveyjs' ),
        );
        foreach ( $restrictions as $key => $label ) {
            if ( ! empty( $form[ $key ] ) ) {
                /* translators: %s: Gravity Forms restriction name */
                $this->notes[] = sprintf( __( 'The form restriction "%s" was not imported.', 'surveyjs' ), $label );
            }
        }
        if ( ! empty( $form['save']['enabled'] ) ) {
            $this->notes[] = __( 'Save and Continue was not imported.', 'surveyjs' );
        }
        if ( ! empty( $form['enableHoneypot'] ) ) {
            $this->notes[] = __( 'The anti-spam honeypot was not imported.', 'surveyjs' );
        }
        if ( in_array( (string) ( $form['personalData']['retention']['policy'] ?? '' ), array( 'trash', 'delete' ), true ) ) {
            $this->notes[] = __( 'The entry retention policy (trash or delete entries after a number of days) was not imported.', 'surveyjs' );
        }
        if ( $this->css_classes ) {
            /* translators: %s: list of CSS class names */
            $this->notes[] = sprintf( __( 'Custom CSS classes were not imported: %s.', 'surveyjs' ), implode( ', ', array_unique( $this->css_classes ) ) );
        }

        $settings = $this->notification_settings( array_filter( (array) ( $form['notifications'] ?? array() ), 'is_array' ) );
        if ( '' !== $this->captcha ) {
            SurveyJS_Captcha::import( $settings, $this->notes, $this->captcha );
        }

        return array(
            'json'     => $json,
            'settings' => $settings,
            'skipped'  => $this->skipped,
            'notes'    => $this->notes,
            'refs'     => $this->refs,
        );
    }

    private function button_text( $property, $button ) {
        if ( ! isset( $this->json[ $property ] ) && is_array( $button ) && 'image' !== ( $button['type'] ?? 'text' ) && '' !== self::text( $button['text'] ?? '' ) ) {
            $this->json[ $property ] = self::text( $button['text'] );
        }
    }

    private function empty_sections( $elements ) {
        foreach ( $elements as &$element ) {
            if ( 'panel' === $element['type'] && empty( $element['elements'] ) ) {
                $markup = isset( $element['title'] ) ? '<h3>' . esc_html( $element['title'] ) . '</h3>' : '';
                $markup .= isset( $element['description'] ) ? wpautop( esc_html( $element['description'] ) ) : '';
                $element = array_intersect_key( $element, array_flip( array( 'name', 'visible' ) ) ) + array( 'type' => 'html', 'html' => $markup );
            }
        }
        return array_values( array_filter( $elements, function ( $element ) {
            return 'html' !== $element['type'] || '' !== $element['html'];
        } ) );
    }

    private function elements( $field ) {
        $type = (string) ( $field['type'] ?? '' );
        $input_type = (string) ( $field['inputType'] ?? '' );
        $input_type = '' !== $input_type ? $input_type : $type;
        $id = (string) ( $field['id'] ?? '' );
        $label = self::text( $field['label'] ?? '' );
        $title = '' !== $label ? $label : self::text( $field['adminLabel'] ?? '' );
        $title = '' !== $title ? $title : $type . ' ' . $id;

        if ( in_array( $type, self::PRICING_TYPES, true ) || in_array( $type, array( 'post_image', 'post_category', 'repeater', 'username' ), true ) ) {
            $this->skipped[] = sprintf( '%s (%s)', $title, $type );
            return array();
        }
        if ( 'submit' === $type ) {
            return array();
        }
        if ( 'captcha' === $type ) {
            $this->captcha = self::CAPTCHAS[ (string) ( $field['captchaType'] ?? 'recaptcha' ) ] ?? __( 'a CAPTCHA', 'surveyjs' );
            return array();
        }
        if ( isset( self::POST_TYPES[ $type ] ) ) {
            $input_type = $input_type === $type ? self::POST_TYPES[ $type ] : $input_type;
            /* translators: %s: field label */
            $this->notes[] = sprintf( __( '"%s" was imported as a regular question: the form no longer creates a post.', 'surveyjs' ), $title );
        }

        $common = array( 'title' => $title );
        $placement = (string) ( $field['labelPlacement'] ?? '' );
        if ( '' === $label || 'hidden_label' === $placement ) {
            $common['titleLocation'] = 'hidden';
        } elseif ( in_array( $placement, array( 'left_label', 'right_label' ), true ) ) {
            $common['titleLocation'] = 'left';
        }
        if ( '' !== self::text( $field['description'] ?? '' ) && 'consent' !== $input_type ) {
            $common['description'] = self::text( $field['description'] );
            if ( $this->description_above( $field ) !== $this->description_above( array() ) ) {
                $common['descriptionLocation'] = $this->description_above( $field ) ? 'underTitle' : 'underInput';
            }
        }
        $this->css_class( $field['cssClass'] ?? '' );
        $this->prefill_notes( $field, $title );
        if ( ! empty( $field['noDuplicates'] ) ) {
            /* translators: %s: field label */
            $this->notes[] = sprintf( __( 'The unique value check of "%s" was not imported.', 'surveyjs' ), $title );
        }
        if ( ! empty( $field['isRequired'] ) ) {
            $common['isRequired'] = true;
            if ( '' !== self::text( $field['errorMessage'] ?? '' ) ) {
                $common['requiredErrorText'] = self::text( $field['errorMessage'] );
            }
        }
        $hidden = in_array( (string) ( $field['visibility'] ?? '' ), array( 'hidden', 'administrative' ), true );
        $question = function ( $survey_type, $props = array() ) use ( $title, $common, $field, $hidden ) {
            $element = array_merge( array( 'type' => $survey_type, 'name' => $this->unique_name( $title ) ), $common, $props );
            if ( $hidden ) {
                $element['visible'] = false;
            }
            $this->condition( $element['name'], $field, $title );
            return $element;
        };

        switch ( $input_type ) {
            case 'text':
            case 'email':
            case 'phone':
            case 'website':
            case 'password':
            case 'hidden':
                $input_types = array( 'email' => 'email', 'phone' => 'tel', 'website' => 'url', 'password' => 'password' );
                $props = isset( $input_types[ $input_type ] ) ? array( 'inputType' => $input_types[ $input_type ] ) : array();
                if ( ! empty( $field['enablePasswordInput'] ) ) {
                    $props['inputType'] = 'password';
                }
                $props += $this->placeholder_default( $field, $title );
                if ( (int) ( $field['maxLength'] ?? 0 ) > 0 ) {
                    $props['maxLength'] = (int) $field['maxLength'];
                }
                if ( 'phone' === $input_type && 'standard' === ( $field['phoneFormat'] ?? '' ) ) {
                    $props += array( 'maskType' => 'pattern', 'maskSettings' => array( 'pattern' => '(999) 999-9999', 'saveMaskedValue' => true ) );
                } elseif ( ! empty( $field['inputMask'] ) ) {
                    $props += $this->mask( (string) ( $field['inputMaskValue'] ?? '' ), $title );
                }
                if ( 'email' === $input_type && ! empty( $field['emailConfirmEnabled'] ) ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The confirmation input of "%s" was not imported.', 'surveyjs' ), $title );
                }
                if ( 'hidden' === $input_type ) {
                    $props += array( 'visible' => false, 'clearIfInvisible' => 'none' );
                }
                $element = $question( 'text', $props );
                if ( 'hidden' === $input_type ) {
                    unset( $element['titleLocation'] );
                }
                $this->ref( $id, $element['name'], 'text', array( 'email' => 'email' === $input_type ) );
                return array( $element );

            case 'textarea':
                $props = $this->placeholder_default( $field, $title );
                if ( (int) ( $field['maxLength'] ?? 0 ) > 0 ) {
                    $props['maxLength'] = (int) $field['maxLength'];
                }
                if ( ! empty( $field['useRichTextEditor'] ) ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The rich text editor of "%s" was not imported: it is a plain text box.', 'surveyjs' ), $title );
                }
                $element = $question( 'comment', $props );
                $this->ref( $id, $element['name'], 'text' );
                return array( $element );

            case 'number':
                $props = array( 'inputType' => 'number' ) + $this->placeholder_default( $field, $title );
                foreach ( array( 'rangeMin' => 'min', 'rangeMax' => 'max' ) as $source => $target ) {
                    if ( is_numeric( $field[ $source ] ?? '' ) ) {
                        $props[ $target ] = 0 + $field[ $source ];
                    }
                }
                if ( is_numeric( $props['defaultValue'] ?? '' ) ) {
                    $props['defaultValue'] = 0 + $props['defaultValue'];
                }
                if ( ! empty( $field['enableCalculation'] ) ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The calculation of "%s" was not imported.', 'surveyjs' ), $title );
                }
                $element = $question( 'text', $props );
                $this->ref( $id, $element['name'], 'number', array( 'decimal_comma' => 'decimal_comma' === ( $field['numberFormat'] ?? '' ) ) );
                return array( $element );

            case 'date':
                $element = $question( 'text', array( 'inputType' => 'date' ) + $this->placeholder_default( $field, $title ) );
                $this->ref( $id, $element['name'], 'date' );
                return array( $element );

            case 'time':
                $element = $question( 'text', array( 'inputType' => 'time' ) );
                $this->ref( $id, $element['name'], 'time' );
                return array( $element );

            case 'select':
            case 'multiselect':
            case 'radio':
            case 'checkbox':
                $props = $this->choices( $field, $input_type, $title );
                $element = $question( $props['type'], $props );
                $multiple = in_array( $element['type'], array( 'checkbox', 'tagbox' ), true ) || ! empty( $element['multiSelect'] );
                $kind = 'checkbox' === $input_type ? 'checkbox' : ( 'multiselect' === $input_type ? 'multiselect' : 'choice' );
                $this->ref( $id, $element['name'], $kind, array( 'multiple' => $multiple ) );
                if ( in_array( $type, array( 'quiz', 'poll' ), true ) ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The quiz or poll settings of "%s" (correct answers, scores, results) were not imported.', 'surveyjs' ), $title );
                }
                return array( $element );

            case 'name':
            case 'address':
                return $this->parts( $field, $input_type, $title, $common, $hidden );

            case 'fileupload':
                $element = $question( 'file', $this->file_props( $field ) );
                $this->ref( $id, $element['name'], 'file' );
                return array( $element );

            case 'list':
                return array( $this->list_question( $field, $question, $id, $title ) );

            case 'consent':
                $checkbox = self::text( $field['checkboxLabel'] ?? '' );
                $props = array( 'title' => '' !== $checkbox ? $checkbox : $title );
                if ( '' !== self::text( $field['description'] ?? '' ) ) {
                    $props['description'] = self::text( $field['description'] );
                }
                $element = self::agreement( $question( 'boolean', $props ), $common['requiredErrorText'] ?? '' );
                $this->ref( $id, $element['name'], 'consent' );
                return array( $element );

            case 'html':
                $element = array( 'type' => 'html', 'name' => $this->unique_name( '' !== $label ? $label : 'html' ), 'html' => wp_kses_post( (string) ( $field['content'] ?? '' ) ) );
                if ( $hidden ) {
                    $element['visible'] = false;
                }
                $this->condition( $element['name'], $field, $title );
                return '' !== trim( $element['html'] ) ? array( $element ) : array();

            case 'likert':
                $columns = self::choice_items( $field['choices'] ?? array() );
                if ( ! empty( $field['gsurveyLikertEnableMultipleRows'] ) ) {
                    $element = $question( 'matrix', array( 'columns' => $columns, 'rows' => self::choice_items( $field['gsurveyLikertRows'] ?? array() ) ) );
                    $this->ref( $id, $element['name'], 'likert_rows' );
                } else {
                    $element = $question( 'radiogroup', array( 'choices' => $columns, 'colCount' => 0 ) );
                    $this->ref( $id, $element['name'], 'choice' );
                }
                return array( $element );

            case 'rank':
                $element = $question( 'ranking', array( 'choices' => self::choice_items( $field['choices'] ?? array() ) ) );
                $this->ref( $id, $element['name'], 'rank' );
                return array( $element );

            case 'rating':
                $values = self::choice_items( $field['choices'] ?? array() );
                $element = $question( 'rating', array( 'rateValues' => ! empty( $field['reversed'] ) ? array_reverse( $values ) : $values ) );
                $this->ref( $id, $element['name'], 'rating' );
                return array( $element );

            case 'signature':
                $element = $question( 'signaturepad' );
                $this->ref( $id, $element['name'], 'signature' );
                return array( $element );
        }
        $this->skipped[] = sprintf( '%s (%s)', $title, $input_type );
        return array();
    }

    private function parts( $field, $type, $title, $common, $hidden ) {
        $id = (string) ( $field['id'] ?? '' );
        $inputs = array_values( array_filter( (array) ( $field['inputs'] ?? array() ), 'is_array' ) );
        if ( ! $inputs ) {
            $element = array_merge( array( 'type' => 'text', 'name' => $this->unique_name( $title ) ), $common, $this->placeholder_default( $field, $title ) );
            if ( $hidden ) {
                $element['visible'] = false;
            }
            $this->condition( $element['name'], $field, $title );
            $this->ref( $id, $element['name'], 'text' );
            return array( $element );
        }
        $names = 'address' === $type ? self::ADDRESS_PARTS : self::NAME_PARTS;
        $address = 'address' === $type ? $this->address_type( $field ) : array( 'hidden' => array() );
        $panel = array( 'type' => 'panel', 'name' => $this->unique_name( $title ) );
        if ( 'hidden' !== ( $common['titleLocation'] ?? '' ) ) {
            $panel['title'] = $title;
        }
        if ( isset( $common['description'] ) ) {
            $panel['description'] = $common['description'];
        }
        $panel += $this->sub_label_location( $field );
        if ( 'address' === $type && ! empty( $field['enableCopyValuesOption'] ) ) {
            /* translators: %s: field label */
            $this->notes[] = sprintf( __( 'The option to copy the values of another field into "%s" was not imported.', 'surveyjs' ), $title );
        }
        $questions = array();
        $parts = array();
        $column = 0;
        foreach ( $inputs as $input ) {
            $suffix = substr( strrchr( '.' . (string) ( $input['id'] ?? '' ), '.' ), 1 );
            $part = $names[ $suffix ] ?? '';
            if ( '' === $part || ! empty( $input['isHidden'] ) || in_array( $part, $address['hidden'], true ) ) {
                continue;
            }
            $sub_label = self::text( $input['customLabel'] ?? '' );
            $sub_label = '' !== $sub_label ? $sub_label : self::text( $input['label'] ?? '' );
            $question = array( 'type' => 'text', 'name' => $this->unique_name( $panel['name'] . '_' . $part ), 'title' => '' !== $sub_label ? $sub_label : ucfirst( $part ) );
            if ( ! empty( $field['isRequired'] ) && in_array( $suffix, self::REQUIRED_PARTS[ $type ], true ) ) {
                $question['isRequired'] = true;
                $panel['isRequired'] = true;
                if ( isset( $common['requiredErrorText'] ) ) {
                    $question['requiredErrorText'] = $common['requiredErrorText'];
                    $panel['requiredErrorText'] = $common['requiredErrorText'];
                }
            }
            if ( 'name' === $type ) {
                $new_line = ! $questions;
            } elseif ( in_array( $part, array( 'street', 'line2' ), true ) ) {
                $new_line = true;
                $column = 0;
            } else {
                $new_line = 0 === $column % 2;
                $column++;
            }
            if ( ! $new_line ) {
                $question['startWithNewLine'] = false;
            }
            $choices = self::choice_items( $input['choices'] ?? array() );
            $default = '';
            if ( 'country' === $part ) {
                $choices = self::countries();
                $default = self::country_value( self::text( $field['defaultCountry'] ?? '' ), $choices );
            } elseif ( 'state' === $part && $address['states'] ) {
                $choices = $address['states'];
                $default = in_array( $address['default_state'], $choices, true ) ? $address['default_state'] : '';
            }
            if ( $choices ) {
                $question['type'] = 'dropdown';
                $question['choices'] = $choices;
            }
            if ( '' !== $default ) {
                $question['defaultValue'] = $default;
            }
            $question += $this->placeholder_default( $input, $question['title'] );
            $questions[] = $question;
            $parts[ $suffix ] = $question['name'];
        }
        if ( ! $parts ) {
            return array();
        }
        $this->refs[ $id ] = array( 'parts' => $parts, 'kind' => 'parts', 'separator' => 'address' === $type ? ', ' : ' ' ) + self::REF_DEFAULTS;
        if ( $hidden ) {
            $panel['visible'] = false;
        }
        $this->condition( $panel['name'], $field, $title );
        return array( $panel + array( 'elements' => $questions ) );
    }

    private function sub_label_location( $field ) {
        $placement = (string) ( $field['subLabelPlacement'] ?? '' );
        $placement = '' !== $placement ? $placement : (string) ( $this->form['subLabelPlacement'] ?? '' );
        if ( 'hidden_label' === $placement ) {
            return array( 'questionTitleLocation' => 'hidden' );
        }
        if ( 'above' !== $placement ) {
            return array( 'questionTitleLocation' => 'bottom' );
        }
        return in_array( $this->label_placement( $field ), array( 'left_label', 'right_label' ), true ) ? array( 'questionTitleLocation' => 'top' ) : array();
    }

    private function label_placement( $field ) {
        $placement = (string) ( $field['labelPlacement'] ?? '' );
        return '' !== $placement ? $placement : (string) ( $this->form['labelPlacement'] ?? 'top_label' );
    }

    private function description_above( $field ) {
        $placement = (string) ( $field['descriptionPlacement'] ?? '' );
        $placement = '' !== $placement ? $placement : (string) ( $this->form['descriptionPlacement'] ?? '' );
        return 'above' === $placement && in_array( $this->label_placement( $field ), array( 'top_label', 'hidden_label' ), true );
    }

    private function address_type( $field ) {
        $type = (string) ( $field['addressType'] ?? '' );
        $hidden = array();
        foreach ( array( 'hideAddress2' => 'line2', 'hideState' => 'state', 'hideCountry' => 'country' ) as $legacy => $part ) {
            if ( ! empty( $field[ $legacy ] ) ) {
                $hidden[] = $part;
            }
        }
        $states = array();
        if ( in_array( $type, array( 'us', 'canadian' ), true ) ) {
            $hidden[] = 'country';
            $states = 'us' === $type ? self::states( 'get_us_states', self::US_STATES ) : self::states( 'get_canadian_provinces', self::CANADIAN_PROVINCES );
        }
        $default_state = self::text( $field['defaultState'] ?? '' );
        if ( '' === $default_state && 'canadian' === $type ) {
            $default_state = self::text( $field['defaultProvince'] ?? '' );
        }
        return array( 'hidden' => $hidden, 'states' => $states, 'default_state' => $default_state );
    }

    private static function gf_list( $method ) {
        if ( ! class_exists( 'GF_Field_Address' ) || ! method_exists( 'GF_Field_Address', $method ) ) {
            return array();
        }
        $list = ( new GF_Field_Address() )->$method();
        return is_array( $list ) ? $list : array();
    }

    private static function countries() {
        $countries = self::gf_list( 'get_countries' );
        $choices = array();
        foreach ( $countries ? $countries : self::COUNTRIES as $code => $name ) {
            $name = self::text( $name );
            if ( '' !== $name ) {
                $choices[] = is_string( $code ) ? array( 'value' => $code, 'text' => $name ) : $name;
            }
        }
        return $choices;
    }

    // Gravity Forms 3.0.3+ stores a country code; older forms store the country name, in English or translated.
    private static function country_value( $country, $choices ) {
        $code = array_search( $country, self::COUNTRIES, true );
        foreach ( '' !== $country ? $choices : array() as $choice ) {
            $value = is_array( $choice ) ? $choice['value'] : $choice;
            if ( 0 === strcasecmp( $value, $country ) || ( is_array( $choice ) && 0 === strcasecmp( $choice['text'], $country ) ) || ( false !== $code && $value === $code ) ) {
                return $value;
            }
        }
        return '';
    }

    private static function states( $method, $fallback ) {
        $states = array_values( array_filter( array_map( array( __CLASS__, 'text' ), self::gf_list( $method ) ), 'strlen' ) );
        return $states ? $states : $fallback;
    }

    private function list_question( $field, $question, $id, $title ) {
        $columns = array();
        $map = array();
        $names = array();
        if ( ! empty( $field['enableColumns'] ) ) {
            foreach ( array_filter( (array) ( $field['choices'] ?? array() ), 'is_array' ) as $index => $choice ) {
                $text = self::text( $choice['text'] ?? '' );
                $text = '' !== $text ? $text : 'column' . ( $index + 1 );
                $column = array( 'name' => self::column_name( $text, $names ), 'title' => $text, 'cellType' => 'text' );
                $columns[] = $column;
                $map[ (string) ( $choice['text'] ?? '' ) ] = $column['name'];
            }
        }
        if ( ! $columns ) {
            $columns[] = array( 'name' => 'value', 'title' => $title, 'cellType' => 'text' );
            $map[''] = 'value';
        }
        $props = array( 'columns' => $columns, 'rowCount' => 1, 'minRowCount' => 1 );
        if ( (int) ( $field['maxRows'] ?? 0 ) > 0 ) {
            $props['maxRowCount'] = (int) $field['maxRows'];
        }
        $element = $question( 'matrixdynamic', $props );
        $this->ref( $id, $element['name'], 'list', array( 'columns' => $map ) );
        return $element;
    }

    private static function column_name( $title, &$names ) {
        $base = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( remove_accents( $title ) ) ), '_' );
        $base = '' !== $base ? substr( $base, 0, 40 ) : 'column';
        $name = $base;
        for ( $i = 2; in_array( $name, $names, true ); $i++ ) {
            $name = $base . '_' . $i;
        }
        $names[] = $name;
        return $name;
    }

    private function choices( $field, $input_type, $label ) {
        $images = 'image_choice' === ( $field['type'] ?? '' );
        $multiple = in_array( $input_type, array( 'checkbox', 'multiselect' ), true );
        $choices = array();
        $defaults = array();
        $blank = null;
        foreach ( array_filter( (array) ( $field['choices'] ?? array() ), 'is_array' ) as $choice ) {
            $text = self::text( $choice['text'] ?? '' );
            if ( 'select' === $input_type && ! $choices && null === $blank && ! empty( $field['enableChoiceValue'] ) && '' === (string) ( $choice['value'] ?? '' ) ) {
                $blank = $text;
                continue;
            }
            $value = isset( $choice['value'] ) && '' !== (string) $choice['value'] ? (string) $choice['value'] : $text;
            $item = $value === $text ? $text : array( 'value' => $value, 'text' => $text );
            if ( $images ) {
                $item = array( 'value' => $value, 'text' => $text, 'imageLink' => esc_url_raw( (string) ( $choice['file_url'] ?? '' ) ) );
            }
            $choices[] = $item;
            if ( ! empty( $choice['isSelected'] ) ) {
                $defaults[] = $value;
            }
        }
        if ( $images ) {
            $props = array( 'type' => 'imagepicker', 'showLabel' => true ) + ( $multiple ? array( 'multiSelect' => true ) : array() );
        } elseif ( 'select' === $input_type || 'multiselect' === $input_type ) {
            $props = array( 'type' => $multiple ? 'tagbox' : 'dropdown' );
            if ( '' !== self::text( $field['placeholder'] ?? '' ) ) {
                $props['placeholder'] = self::text( $field['placeholder'] );
            } elseif ( null !== $blank && '' !== $blank ) {
                $props['placeholder'] = $blank;
            }
        } else {
            $props = array( 'type' => $multiple ? 'checkbox' : 'radiogroup' );
        }
        if ( 'radio' === $input_type && ! empty( $field['enableOtherChoice'] ) ) {
            if ( $images ) {
                /* translators: %s: field label */
                $this->notes[] = sprintf( __( 'The "Other" choice of "%s" was not imported: image choices do not support it.', 'surveyjs' ), $label );
            } else {
                $props['showOtherItem'] = true;
                $props['otherText'] = __( 'Other', 'surveyjs' );
            }
        }
        if ( 'checkbox' === $input_type && ! empty( $field['enableSelectAll'] ) && ! $images ) {
            $props['showSelectAllItem'] = true;
            if ( '' !== self::text( $field['selectAllText'] ?? '' ) ) {
                $props['selectAllText'] = self::text( $field['selectAllText'] );
            }
        }
        if ( $multiple ) {
            $limit = (string) ( $field['choiceLimit'] ?? '' );
            if ( 'exactly' === $limit && (int) ( $field['choiceLimitNumber'] ?? 0 ) > 0 ) {
                $props['minSelectedChoices'] = (int) $field['choiceLimitNumber'];
                $props['maxSelectedChoices'] = (int) $field['choiceLimitNumber'];
            } elseif ( 'range' === $limit ) {
                foreach ( array( 'choiceLimitMin' => 'minSelectedChoices', 'choiceLimitMax' => 'maxSelectedChoices' ) as $source => $target ) {
                    if ( (int) ( $field[ $source ] ?? 0 ) > 0 ) {
                        $props[ $target ] = (int) $field[ $source ];
                    }
                }
            }
        }
        $props['choices'] = $choices;
        // A Gravity Forms drop down without a placeholder is a plain <select>, so its first choice is selected and submitted.
        if ( ! $defaults && 'dropdown' === $props['type'] && ! isset( $props['placeholder'] ) && null === $blank && $choices ) {
            $defaults[] = is_array( $choices[0] ) ? $choices[0]['value'] : $choices[0];
        }
        if ( $defaults ) {
            $props['defaultValue'] = $multiple ? $defaults : $defaults[0];
        }
        return $props;
    }

    private static function choice_items( $choices ) {
        $items = array();
        foreach ( array_filter( (array) $choices, 'is_array' ) as $choice ) {
            $text = self::text( $choice['text'] ?? '' );
            $value = isset( $choice['value'] ) && '' !== (string) $choice['value'] ? (string) $choice['value'] : $text;
            if ( '' !== $value ) {
                $items[] = array( 'value' => $value, 'text' => '' !== $text ? $text : $value );
            }
        }
        return $items;
    }

    private function file_props( $field ) {
        $props = array( 'storeDataAsText' => false );
        if ( ! empty( $field['multipleFiles'] ) ) {
            $props['allowMultiple'] = true;
            if ( (int) ( $field['maxFiles'] ?? 0 ) > 0 ) {
                $props['maxFiles'] = (int) $field['maxFiles'];
            }
        }
        if ( is_numeric( $field['maxFileSize'] ?? '' ) && $field['maxFileSize'] > 0 ) {
            $props['maxSize'] = (int) round( $field['maxFileSize'] * MB_IN_BYTES );
        }
        $extensions = array();
        foreach ( explode( ',', strtolower( (string) ( $field['allowedExtensions'] ?? '' ) ) ) as $extension ) {
            $extension = trim( $extension, " \t\n\r\0\x0B." );
            if ( '' !== $extension ) {
                $extensions[] = '.' . $extension;
            }
        }
        if ( $extensions ) {
            $props['acceptedTypes'] = implode( ',', array_unique( $extensions ) );
        }
        return $props;
    }

    private function mask( $mask, $label ) {
        if ( '' === $mask ) {
            return array();
        }
        if ( false !== strpos( $mask, '?' ) ) {
            /* translators: 1: field label, 2: input mask */
            $this->notes[] = sprintf( __( 'The input mask of "%1$s" (%2$s) was not imported: it has optional parts.', 'surveyjs' ), $label, $mask );
            return array();
        }
        return array( 'maskType' => 'pattern', 'maskSettings' => array( 'pattern' => strtr( $mask, array( '*' => '#' ) ), 'saveMaskedValue' => true ) );
    }

    private function prefill_notes( $field, $label ) {
        if ( empty( $field['allowsPrepopulate'] ) ) {
            return;
        }
        $params = array( $field['inputName'] ?? '' );
        foreach ( array_filter( (array) ( $field['inputs'] ?? array() ), 'is_array' ) as $input ) {
            if ( empty( $input['isHidden'] ) ) {
                $params[] = $input['name'] ?? '';
            }
        }
        foreach ( array_unique( array_filter( array_map( array( __CLASS__, 'text' ), $params ), 'strlen' ) ) as $param ) {
            /* translators: 1: URL parameter name, 2: field label */
            $this->notes[] = sprintf( __( 'The URL parameter "%1$s" of "%2$s" was not imported.', 'surveyjs' ), $param, $label );
        }
    }

    private function css_class( $classes ) {
        foreach ( preg_split( '/\s+/', self::text( $classes ), -1, PREG_SPLIT_NO_EMPTY ) as $class ) {
            $this->css_classes[] = $class;
        }
    }

    private function placeholder_default( $field, $label ) {
        $props = array();
        if ( '' !== self::text( $field['placeholder'] ?? '' ) ) {
            $props['placeholder'] = self::text( $field['placeholder'] );
        }
        $default = is_scalar( $field['defaultValue'] ?? '' ) ? trim( (string) ( $field['defaultValue'] ?? '' ) ) : '';
        $variable = preg_match( '/^\{user:(\w+)\}$/', $default, $match ) ? self::user_variable( self::USER_MERGE_TAGS[ $match[1] ] ?? '' ) : '';
        if ( '' !== $variable ) {
            $props['defaultValueExpression'] = self::variables_expression( array( $variable ) );
        } elseif ( preg_match( '/\{[^{}]+\}/', $default ) ) {
            /* translators: 1: field label, 2: default value */
            $this->notes[] = sprintf( __( 'The default value of "%1$s" (%2$s) uses a merge tag and was not imported.', 'surveyjs' ), $label, $default );
        } elseif ( '' !== $default ) {
            $props['defaultValue'] = $default;
        }
        return $props;
    }

    private function ref( $field_id, $name, $kind, $extra = array() ) {
        if ( '' === $field_id ) {
            return;
        }
        $this->refs[ $field_id ] = $extra + array( 'parts' => array( 'value' => $name ), 'kind' => $kind ) + self::REF_DEFAULTS;
    }

    private function condition( $name, $field, $label ) {
        $logic = $field['conditionalLogic'] ?? null;
        if ( self::condition_active( $logic ) ) {
            $this->conditions[ $name ][] = array( $logic, $label );
        }
    }

    private static function condition_active( $logic ) {
        return is_array( $logic ) && ! empty( $logic['rules'] ) && is_array( $logic['rules'] );
    }

    private function apply_conditions( $elements ) {
        foreach ( $elements as &$element ) {
            if ( ! empty( $element['elements'] ) ) {
                $element['elements'] = $this->apply_conditions( $element['elements'] );
            }
            $expressions = array();
            foreach ( $this->conditions[ $element['name'] ] ?? array() as list( $logic, $label ) ) {
                $expression = $this->expression( $logic );
                if ( '' === $expression ) {
                    /* translators: %s: field label */
                    $this->notes[] = sprintf( __( 'The conditional logic of "%s" was not imported.', 'surveyjs' ), '' !== $label ? $label : $element['name'] );
                    continue;
                }
                $expressions[] = $expression;
            }
            $expressions = array_unique( $expressions );
            if ( $expressions ) {
                $element['visibleIf'] = count( $expressions ) > 1 ? '(' . implode( ') and (', $expressions ) . ')' : $expressions[0];
            }
        }
        return $elements;
    }

    private function expression( $logic ) {
        if ( ! self::condition_active( $logic ) ) {
            return '';
        }
        $expressions = array();
        foreach ( array_filter( $logic['rules'], 'is_array' ) as $rule ) {
            $expression = $this->rule( $rule );
            if ( '' === $expression ) {
                return '';
            }
            $expressions[] = $expression;
        }
        if ( ! $expressions ) {
            return '';
        }
        $joined = implode( 'any' === ( $logic['logicType'] ?? 'all' ) ? ' or ' : ' and ', $expressions );
        return 'hide' === ( $logic['actionType'] ?? 'show' ) ? '!(' . $joined . ')' : $joined;
    }

    private function rule( $rule ) {
        $field_id = (string) ( $rule['fieldId'] ?? '' );
        $names = $this->field_names( $field_id );
        if ( 1 !== count( $names ) ) {
            return '';
        }
        $ref = $this->refs[ strtok( $field_id, '.' ) ];
        $name = '{' . $names[0] . '}';
        $operator = (string) ( $rule['operator'] ?? 'is' );
        $value = is_scalar( $rule['value'] ?? '' ) ? trim( (string) ( $rule['value'] ?? '' ) ) : '';
        if ( 'consent' === $ref['kind'] && in_array( $operator, array( 'is', 'isnot' ), true ) ) {
            return $name . ( ( 'is' === $operator ) === ( '' !== $value && '0' !== $value ) ? ' = true' : ' <> true' );
        }
        if ( '' === $value && in_array( $operator, array( 'is', 'isnot' ), true ) ) {
            return $name . ( 'is' === $operator ? ' empty' : ' notempty' );
        }
        if ( in_array( $operator, array( '>', '<', 'greater_than', 'less_than' ), true ) ) {
            return is_numeric( $value ) ? $name . ' ' . ( in_array( $operator, array( '>', 'greater_than' ), true ) ? '>' : '<' ) . ' ' . ( 0 + $value ) : '';
        }
        if ( false !== strpos( $value, '{' ) ) {
            return '';
        }
        $multiple = in_array( $ref['kind'], array( 'checkbox', 'multiselect' ), true ) || $ref['multiple'];
        if ( in_array( $operator, array( 'starts_with', 'ends_with' ), true ) ) {
            return $multiple ? '' : self::affix_rule( $names[0], 'starts_with' === $operator, $value );
        }
        $quoted = 'number' === $ref['kind'] && is_numeric( $value ) ? (string) ( 0 + $value ) : self::quoted( $value );
        if ( '' === $quoted ) {
            return '';
        }
        $operators = array(
            'is'       => $multiple ? 'contains' : '=',
            'isnot'    => $multiple ? 'notcontains' : '<>',
            'contains' => 'contains',
        );
        return isset( $operators[ $operator ] ) ? $name . ' ' . $operators[ $operator ] . ' ' . $quoted : '';
    }

    private function field_names( $field_id ) {
        if ( ! preg_match( '/^(\d+)(?:\.(\d+))?$/', $field_id, $match ) || ! isset( $this->refs[ $match[1] ] ) ) {
            return array();
        }
        $ref = $this->refs[ $match[1] ];
        if ( 'parts' !== $ref['kind'] ) {
            return array( $ref['parts']['value'] );
        }
        if ( isset( $match[2] ) ) {
            return isset( $ref['parts'][ $match[2] ] ) ? array( $ref['parts'][ $match[2] ] ) : array();
        }
        return array_values( $ref['parts'] );
    }

    private function confirmation( $confirmations, &$json ) {
        $default = null;
        $conditional = array();
        foreach ( $confirmations as $confirmation ) {
            if ( isset( $confirmation['isActive'] ) && ! $confirmation['isActive'] ) {
                continue;
            }
            if ( ! empty( $confirmation['isDefault'] ) ) {
                $default = $default ? $default : $confirmation;
            } elseif ( self::condition_active( $confirmation['conditionalLogic'] ?? null ) ) {
                $conditional[] = $confirmation;
            }
        }
        $messages = array();
        foreach ( $conditional as $confirmation ) {
            $expression = $this->expression( $confirmation['conditionalLogic'] );
            if ( '' === $expression ) {
                /* translators: %s: confirmation name */
                $this->notes[] = sprintf( __( 'The confirmation "%s" was not imported: its conditional logic is not supported.', 'surveyjs' ), $confirmation['name'] ?? '' );
                continue;
            }
            $this->add_confirmation( $confirmation, $expression, $messages, $json );
        }
        if ( $default ) {
            $this->add_confirmation( $default, '', $messages, $json );
        }
    }

    private function add_confirmation( $confirmation, $expression, &$messages, &$json ) {
        $type = (string) ( $confirmation['type'] ?? 'message' );
        $name = (string) ( $confirmation['name'] ?? __( 'Default Confirmation', 'surveyjs' ) );
        if ( 'message' === $type ) {
            $message = $this->tags( (string) ( $confirmation['message'] ?? '' ), true );
            if ( '' === trim( $message ) ) {
                return;
            }
            $html = trim( wp_kses_post( empty( $confirmation['disableAutoformat'] ) ? wpautop( $message ) : $message ) );
            if ( '' === $expression ) {
                $json['completedHtml'] = $html;
            } else {
                $json['completedHtmlOnCondition'][] = array( 'expression' => $expression, 'html' => $html );
                $messages[] = $expression;
            }
            return;
        }
        if ( 'page' === $type && $this->foreign ) {
            /* translators: %s: confirmation name */
            $this->notes[] = sprintf( __( 'The confirmation "%s" was not imported: it redirects to a page of the site the form was exported from.', 'surveyjs' ), $name );
            return;
        }
        $url = 'page' === $type ? (string) get_permalink( (int) ( $confirmation['pageId'] ?? 0 ) ) : trim( (string) ( $confirmation['url'] ?? '' ) );
        $query = trim( (string) ( $confirmation['queryString'] ?? '' ) );
        if ( '' !== $url && '' !== $query ) {
            $url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . ltrim( $query, '?&' );
        }
        $url = $this->tags( $url, true );
        if ( ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) {
            /* translators: %s: confirmation name */
            $this->notes[] = sprintf( __( 'The redirect of the confirmation "%s" was not imported: it has no valid URL.', 'surveyjs' ), $name );
            return;
        }
        // SurveyJS redirects before it shows any completed page, so a redirect must not match when an earlier conditional message does.
        $conditions = '' !== $expression ? array( '(' . $expression . ')' ) : array();
        if ( $messages ) {
            $conditions[] = '!((' . implode( ') or (', $messages ) . '))';
        }
        if ( ! $conditions ) {
            $json['navigateToUrl'] = $url;
        } else {
            $json['navigateToUrlOnCondition'][] = array( 'expression' => implode( ' and ', $conditions ), 'url' => $url );
        }
    }

    private function notification_settings( $notifications ) {
        $admin = null;
        $auto_reply = null;
        $auto_reply_field = '';
        $others = array();
        foreach ( $notifications as $notification ) {
            $event = (string) ( $notification['event'] ?? 'form_submission' );
            if ( '' !== $event && 'form_submission' !== $event ) {
                /* translators: 1: notification name, 2: Gravity Forms event key */
                $this->notes[] = sprintf( __( 'The notification "%1$s" was not imported: it is sent on the event "%2$s", not on form submission.', 'surveyjs' ), $notification['name'] ?? '', $event );
                continue;
            }
            $field = $this->recipient_field( $notification );
            if ( '' !== $field && ! $auto_reply ) {
                $auto_reply = $notification;
                $auto_reply_field = $field;
            } elseif ( '' === $field && ( ! $admin || ( ! self::active( $admin ) && self::active( $notification ) ) ) ) {
                if ( $admin ) {
                    $others[] = $admin;
                }
                $admin = $notification;
            } else {
                $others[] = $notification;
            }
        }

        $settings = array( 'notificationEnabled' => false );
        if ( $admin ) {
            if ( 'routing' === ( $admin['toType'] ?? '' ) ) {
                $to = '';
                /* translators: %s: notification name */
                $this->notes[] = sprintf( __( 'The conditional routing of the notification "%s" was not imported: it is sent to the site admin.', 'surveyjs' ), $admin['name'] ?? '' );
            } elseif ( 'field' === ( $admin['toType'] ?? '' ) ) {
                $to = '';
                /* translators: %s: notification name */
                $this->notes[] = sprintf( __( 'The recipient of the notification "%s" is not an Email field, so it was not imported: it is sent to the site admin.', 'surveyjs' ), $admin['name'] ?? '' );
            } else {
                $to = $this->tags( $admin['to'] ?? '', false );
            }
            $settings = array(
                'notificationEnabled'   => self::active( $admin ),
                'notificationTo'        => $to,
                'notificationSubject'   => $this->tags( $admin['subject'] ?? '', false ),
                'notificationFromName'  => $this->tags( $admin['fromName'] ?? '', false ),
                'notificationFromEmail' => $this->tags( $admin['from'] ?? '', false ),
            ) + $this->message_settings( $admin, 'notification' );
            foreach ( array( 'cc' => 'CC', 'bcc' => 'BCC' ) as $key => $header ) {
                if ( '' !== trim( (string) ( $admin[ $key ] ?? '' ) ) ) {
                    /* translators: 1: CC or BCC, 2: email addresses */
                    $this->notes[] = sprintf( __( '%1$s "%2$s" was not imported: add these addresses to Send To if needed.', 'surveyjs' ), $header, $admin[ $key ] );
                }
            }
            $reply_to = trim( (string) ( $admin['replyTo'] ?? '' ) );
            if ( '' !== $reply_to ) {
                $settings['notificationReplyTo'] = $this->email_field( $reply_to );
                if ( '' === $settings['notificationReplyTo'] ) {
                    /* translators: %s: Reply-To value */
                    $this->notes[] = sprintf( __( 'Reply-To "%s" was not imported: Reply-To can only be an Email field.', 'surveyjs' ), $reply_to );
                }
            }
            $this->notification_notes( $admin );
        }

        if ( $auto_reply ) {
            if ( in_array( $settings['notificationReplyTo'] ?? '', array( '', $auto_reply_field ), true ) ) {
                $settings['notificationReplyTo'] = $auto_reply_field;
                $settings['autoReplyEnabled'] = self::active( $auto_reply );
                $settings['autoReplySubject'] = $this->tags( $auto_reply['subject'] ?? '', false );
                $settings += $this->message_settings( $auto_reply, 'autoReply' );
                /* translators: %s: notification name */
                $this->notes[] = sprintf( __( 'The notification "%s" was imported as the auto-reply.', 'surveyjs' ), $auto_reply['name'] ?? '' );
                $this->notification_notes( $auto_reply );
            } else {
                $others[] = $auto_reply;
            }
        }
        foreach ( $others as $other ) {
            /* translators: %s: notification name */
            $this->notes[] = sprintf( __( 'The notification "%s" was not imported: a form has one email notification and one auto-reply.', 'surveyjs' ), $other['name'] ?? '' );
        }
        return $settings;
    }

    private static function active( $item ) {
        return ! isset( $item['isActive'] ) || ! empty( $item['isActive'] );
    }

    private function message_settings( $notification, $prefix ) {
        $message = $this->tags( $notification['message'] ?? '', false );
        $html = (bool) preg_match( '/<[a-z][^>]*>/i', $message );
        $settings = array(
            $prefix . 'Message' => $html && empty( $notification['disableAutoformat'] ) ? wpautop( $message ) : $message,
            $prefix . 'Html'    => $html,
        );
        if ( ! empty( $notification['enableAttachments'] ) ) {
            $files = array();
            foreach ( $this->refs as $ref ) {
                if ( 'file' === $ref['kind'] ) {
                    $files[] = $ref['parts']['value'];
                }
            }
            if ( $files ) {
                $settings[ $prefix . 'Attachments' ] = $files[0];
            }
            if ( count( $files ) > 1 ) {
                /* translators: %s: notification name */
                $this->notes[] = sprintf( __( 'Only one file field of the notification "%s" is attached; other attachments were not imported.', 'surveyjs' ), $notification['name'] ?? '' );
            }
        }
        return $settings;
    }

    private function notification_notes( $notification ) {
        if ( self::condition_active( $notification['conditionalLogic'] ?? null ) ) {
            /* translators: %s: notification name */
            $this->notes[] = sprintf( __( 'The conditional logic of the notification "%s" was not imported.', 'surveyjs' ), $notification['name'] ?? '' );
        }
    }

    private function recipient_field( $notification ) {
        if ( 'field' === ( $notification['toType'] ?? '' ) ) {
            $field_id = (string) ( $notification['to'] ?? $notification['toField'] ?? '' );
            return ! empty( $this->refs[ $field_id ]['email'] ) ? $this->refs[ $field_id ]['parts']['value'] : '';
        }
        return 'email' === ( $notification['toType'] ?? 'email' ) ? $this->email_field( (string) ( $notification['to'] ?? '' ) ) : '';
    }

    private function email_field( $text ) {
        if ( ! preg_match( '/^\s*\{[^{}]*:(\d+)(?::[^{}]*)?\}\s*$/', $text, $match ) ) {
            return '';
        }
        return ! empty( $this->refs[ $match[1] ]['email'] ) ? $this->refs[ $match[1] ]['parts']['value'] : '';
    }

    private function tags( $text, $html ) {
        return preg_replace_callback( '/\{([^{}]+)\}/', function ( $match ) use ( $html ) {
            if ( preg_match( '/^[^{}]*:(\d+(?:\.\d+)?)(?::[^{}]*)?$/', $match[1], $field ) ) {
                $names = $this->field_names( $field[1] );
                if ( $names ) {
                    $ref = $this->refs[ strtok( $field[1], '.' ) ];
                    return implode( $ref['separator'], array_map( function ( $name ) use ( $html ) {
                        return $html ? '{' . $name . '}' : '{field:' . $name . '}';
                    }, $names ) );
                }
            } elseif ( preg_match( '/^([a-z_]+)(?::[^{}]*)?$/', $match[1], $code ) ) {
                $values = array(
                    'admin_email' => array( '{admin_email}', get_option( 'admin_email' ) ),
                    'form_title'  => array( '{form_name}', $this->title ),
                    'all_fields'  => array( '{all_fields}', null ),
                );
                if ( isset( $values[ $code[1] ] ) && ( ! $html || null !== $values[ $code[1] ][1] ) ) {
                    return $html ? esc_html( $values[ $code[1] ][1] ) : $values[ $code[1] ][0];
                }
            } else {
                return $match[0];
            }
            $this->notes[] = $html
                /* translators: %s: Gravity Forms merge tag */
                ? sprintf( __( 'Merge tag %s in the confirmation has no equivalent and was removed.', 'surveyjs' ), $match[0] )
                /* translators: %s: Gravity Forms merge tag */
                : sprintf( __( 'Merge tag %s has no equivalent and was kept as text.', 'surveyjs' ), $match[0] );
            return $html ? '' : $match[0];
        }, (string) $text );
    }

    private function unique_name( $text ) {
        $base = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( remove_accents( $text ) ) ), '_' );
        $base = '' !== $base ? substr( $base, 0, 40 ) : 'field';
        $name = $base;
        for ( $i = 2; in_array( $name, $this->taken, true ); $i++ ) {
            $name = $base . '_' . $i;
        }
        $this->taken[] = $name;
        return $name;
    }

    private static function table_exists( $table ) {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . $table ) ) );
    }

    private static function text( $value ) {
        return is_scalar( $value ) ? trim( wp_strip_all_tags( html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) ) ) : '';
    }
}

class SurveyJS_Gravity_Forms_File_Importer extends SurveyJS_Gravity_Forms_Importer {

    private static $uploaded = array();

    public static function source() {
        return 'gravityforms-file';
    }

    public static function is_available() {
        return true;
    }

    public static function forms() {
        return array_map( function ( $form ) {
            return $form['name'];
        }, self::$uploaded );
    }

    public static function entries_available() {
        return false;
    }

    protected static function convert_form( $form_id ) {
        if ( ! isset( self::$uploaded[ $form_id ] ) ) {
            return new WP_Error( 'not_found', __( 'The Gravity Forms form was not found.', 'surveyjs' ) );
        }
        return self::convert_meta( self::$uploaded[ $form_id ]['form'], true );
    }

    // A Gravity Forms export is {"0": form, "1": form, …, "version": "x.y"}; a list of forms or a single form object is accepted too.
    public static function load( $contents ) {
        $data = json_decode( (string) $contents, true );
        if ( is_array( $data ) && isset( $data['fields'] ) ) {
            $data = array( $data );
        }
        self::$uploaded = array();
        foreach ( is_array( $data ) ? $data : array() as $key => $form ) {
            if ( 'version' === $key || ! is_array( $form ) || ! isset( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
                continue;
            }
            $id = absint( $form['id'] ?? 0 );
            if ( ! $id || isset( self::$uploaded[ $id ] ) ) {
                $id = max( array_merge( array( 0 ), array_keys( self::$uploaded ) ) ) + 1;
            }
            self::$uploaded[ $id ] = array( 'form' => $form, 'name' => self::form_name( $form ) );
        }
        if ( ! self::$uploaded ) {
            return new WP_Error( 'invalid_file', __( 'This file is not a Gravity Forms export. In Gravity Forms, go to Forms → Import/Export → Export Forms and download the .json file.', 'surveyjs' ) );
        }
        return array_keys( self::$uploaded );
    }
}
