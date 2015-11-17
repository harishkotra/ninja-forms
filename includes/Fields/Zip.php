<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Zip
 */
class NF_Fields_Zip extends NF_Abstracts_UserInfo
{
    protected $_name = 'zip';

    protected $_nicename = 'Zip';

    protected $_section = 'userinfo';

    protected $_templates = array( 'zip', 'textbox', 'input' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __('Zip', 'ninja-forms');
    }
}