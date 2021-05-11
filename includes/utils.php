<?php

/**
 * Var dump variable into a string
 */
if( !function_exists( 'var_dump_to_string' ) ) {
	function var_dump_to_string($var) {
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}
}

function b4rcp_get_api_key() {
	return B4RCP_Settings::get_instance()->get_options()['api_key'];
}

function b4rcp_get_block_id() {
	return B4RCP_Settings::get_instance()->get_options()['block_id'];
}

function b4rcp_is_connected() {
	return B4RCP_Settings::get_instance()->get_options()['is_connected'];
}

function b4rcp_invalidate_connection() {
	B4RCP_Settings::get_instance()->invalidate_connection();
	return;
}
