<?php
/**
 * Code Separator Block Registration
 *
 * @package Eighty Four EM
 */

namespace EightyFourEM\CodeSeparator;

defined( 'ABSPATH' ) || exit;

/**
 * Register the Code Separator block
 */
\add_action(
	hook_name: 'init',
	callback: function (): void {
		\register_block_type(
			\get_template_directory() . '/blocks/code-separator'
		);
	}
);
