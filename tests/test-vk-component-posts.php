<?php
/**
 * Class VK_Component_Posts_Test
 *
 * @package VK_Components
 */

use VektorInc\VK_Component\VK_Component_Posts;
new VK_Component_Posts();

/**
 * Tests for VK_Component_Posts helpers.
 */
class VkComponentPostsTest extends WP_UnitTestCase {

	/**
	 * @see VK_Component_Posts::get_col_size_classes()
	 */
	public function test_get_col_size_classes() {
		$test_cases = array(
			array(
				'test_condition_name' => '全てのカラム指定を変換して結合する',
				'conditions'          => array(
					'attributes' => array(
						'col_xs'  => 1,
						'col_sm'  => 2,
						'col_md'  => 3,
						'col_lg'  => 4,
						'col_xl'  => 6,
						'col_xxl' => 4,
					),
				),
				'expected'            => 'vk_post-col-xs-12 vk_post-col-sm-6 vk_post-col-md-4 vk_post-col-lg-3 vk_post-col-xl-2 vk_post-col-xxl-3',
			),
			array(
				'test_condition_name' => '未知の値はデフォルト変換で col-4 として扱う',
				'conditions'          => array(
					'attributes' => array(
						'col_md' => 5,
						'col_xl' => 'not-numeric',
					),
				),
				'expected'            => 'vk_post-col-md-4 vk_post-col-xl-4',
			),
			array(
				'test_condition_name' => '指定が無ければ空文字を返す',
				'conditions'          => array(
					'attributes' => array(),
				),
				'expected'            => '',
			),
		);

		foreach ( $test_cases as $case ) {
			$attributes = $case['conditions']['attributes'];
			$return     = VK_Component_Posts::get_col_size_classes( $attributes );
			$this->assertSame( $case['expected'], $return, $case['test_condition_name'] );
		}
	}
}
