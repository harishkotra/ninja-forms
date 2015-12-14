<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_AJAX_Controllers_Submission extends NF_Abstracts_Controller
{
    protected $_form_data = array();

    protected $_form_id = '';

    public function __construct()
    {
        if( isset( $_POST['formData'] ) ) $this->_form_data = json_decode( stripslashes( $_POST['formData'] ), TRUE  );
        add_action( 'wp_ajax_nf_ajax_submit',   array( $this, 'process' )  );
        add_action( 'wp_ajax_nopriv_nf_ajax_submit',   array( $this, 'process' )  );
    }

    public function process()
    {
        check_ajax_referer( 'ninja_forms_ajax_nonce', 'security' );

        if( ! $this->_form_data ) {

            $this->_errors[] = 'Form Data not found.';
            $this->_respond();
        }

        $this->_form_id = $this->_form_data['id'];

        $this->_data['settings'] = $this->_form_data['settings'];

        $this->_data['fields'] = $this->_form_data['fields'];

        $field_merge_tags = Ninja_Forms()->merge_tags[ 'fields' ];

        $this->populate_field_merge_tags( $this->_data['fields'], $field_merge_tags );

        $this->validate_fields();

        $this->process_fields();

        if( isset( $this->_form_data[ 'settings' ][ 'is_preview' ] ) && $this->_form_data[ 'settings' ][ 'is_preview' ] ) {
            $this->run_actions_preview();
        } else {
            $this->run_actions();
        }

        $this->_respond();
    }

    protected function populate_field_merge_tags( $fields, $field_merge_tags )
    {
        foreach( $fields as $field ){

            $field[ 'key' ] = $field[ 'id' ];

            if( ! isset( $field[ 'key' ] ) ) continue;
            $field_merge_tags->set_merge_tags( $field[ 'key' ], $field[ 'value' ] );
        }
    }

    protected function validate_fields()
    {
        foreach( $this->_data['fields'] as $field ){

            $errors = $this->validate_field( $field, $this->_data );

            if( ! empty( $errors ) ){
                $this->_errors[ $field['id'] ] = $errors;
            }
        }
    }

    protected function validate_field( $field, $data )
    {
        $field_model = Ninja_Forms()->form()->field( $field['id'] )->get();

        $field = array_merge( $field, $field_model->get_settings() );

        $field_class = Ninja_Forms()->fields[ $field['type'] ];

        return $errors = $field_class->validate( $field, $data );
    }

    protected function process_fields()
    {
        foreach( $this->_data['fields'] as $field ){

            $data = $this->process_field( $field, $this->_data );

            if( ! empty( $data ) ){
                $this->_data = $data;
            }
        }
    }

    protected function process_field( $field, $data )
    {
        $field_model = Ninja_Forms()->form()->field( $field['id'] )->get();

        $field = array_merge( $field, $field_model->get_settings() );

        $field_class = Ninja_Forms()->fields[ $field['type'] ];

        return $field_class->process( $field, $data );
    }

    protected function run_actions()
    {
        $actions = Ninja_Forms()->form( $this->_form_id )->get_actions();

        foreach( $actions as $action ){

            $action_settings = apply_filters( 'ninja_forms_run_action_settings', $action->get_settings(), $this->_form_id, $action->get_id(), $this->_data['settings'] );

            $this->_data[ 'run_action' ][ $action_settings['type'] ][ 'active' ] = $action_settings['active'];

            if( ! $action_settings['active'] ) continue;

            $type = $action_settings['type'];

            $data = Ninja_Forms()->actions[ $type ]->process( $action_settings, $this->_form_id, $this->_data );

            $this->_data = ( $data ) ? $data : $this->_data;
        }
    }

    protected function run_actions_preview()
    {
        $form = get_user_option( 'nf_form_preview_' . $this->_form_id );

        if( ! isset( $form[ 'actions' ] ) || empty( $form[ 'actions' ] ) ) return;

        foreach( $form[ 'actions' ] as $action ){

            $action_settings = apply_filters( 'ninja_forms_run_action_settings_preview', $action[ 'settings' ], $this->_form_id, '', $this->_data['settings'] );

            if( ! $action_settings['active'] ) continue;

            $type = $action_settings['type'];

            $data = Ninja_Forms()->actions[ $type ]->process( $action_settings, $this->_form_id, $this->_data );

            $this->_data = ( $data ) ? $data : $this->_data;
        }
    }
}