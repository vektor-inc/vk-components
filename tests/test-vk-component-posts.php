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
				'test_condition_name' => 'XXLのサイズが未指定の場合はXXLのクラスを返さない',
				'conditions'          => array(
					'attributes' => array(
						'col_xs' => 1,
						'col_sm' => 2,
						'col_md' => 3,
						'col_lg' => 4,
						'col_xl' => 6,
					),
				),
				'expected'            => 'vk_post-col-xs-12 vk_post-col-sm-6 vk_post-col-md-4 vk_post-col-lg-3 vk_post-col-xl-2',
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

	/**
	 * 投稿本文の属性・文字列・HTML 断片の扱いを確認する。
	 *
	 * @return void
	 */
	public function test_get_view_body() {
		$post = self::factory()->post->create_and_get();
		// 正規レイアウトでは既存の HTML 全体が変わらないことを確認する。
		foreach ( array( 'card', 'card-horizontal', 'card-noborder', 'card-intext', 'media' ) as $layout ) {
			$body_class = 'media' === $layout ? 'media' : 'card';
			$options = VK_Component_Posts::get_loop_post_view_options(
				array( 'layout' => $layout, 'textlink' => false, 'display_new' => false, 'display_date' => false ),
				$post
			);
			$expected = '<div class="vk_post_body ' . $body_class . '-body"><h5 class="vk_post_title ' . $body_class . '-title">' . get_the_title( $post->ID ) . '</h5></div><!-- [ /.' . $body_class . '-body ] -->';
			$this->assertSame( $expected, VK_Component_Posts::get_view_body( $post, $options ) );
		}
		// 正規値、入力済みの実体参照、タグを含む値を比較する。
		$cases = array(
			array( 'card', 'New!!', 'text-right', 'card', 'New!!', 'text-right' ),
			array( 'media', 'New &amp; Next', 'text-center', 'media', 'New &amp; Next', 'text-center' ),
			array( 'x" onclick="bad<>&', '<b>Next</b>', 'x" onclick="bad<>&', 'x&quot; onclick=&quot;bad&lt;&gt;&amp;', '&lt;b&gt;Next&lt;/b&gt;', 'x&quot; onclick=&quot;bad&lt;&gt;&amp;' ),
			array( 'x--><script>bad</script>', 'New!!', 'text-left', 'x--&gt;&lt;script&gt;bad&lt;/script&gt;', 'New!!', 'text-left' ),
		);
		foreach ( $cases as $case ) {
			$options = VK_Component_Posts::get_loop_post_view_options( array(
				'layout' => $case[0], 'new_text' => $case[1], 'btn_align' => $case[2],
				'display_btn' => true, 'display_modified' => true, 'display_excerpt' => true,
				'body_prepend' => '<div class="before">Before</div>',
				'body_append' => '<span class="after">After</span>',
			), $post );
			$html = VK_Component_Posts::get_view_body( $post, $options );
			foreach ( array( '-body', '-title', '-date published', '-date modified', '-text' ) as $suffix ) {
				$this->assertStringContainsString( $case[3] . $suffix . '"', $html );
			}
			$this->assertStringContainsString( '<span class="vk_post_title_new">' . $case[4] . '</span>', $html );
			$this->assertStringContainsString( 'class="vk_post_btnOuter ' . $case[5] . '"', $html );
			$this->assertStringContainsString( $options['body_prepend'], $html );
			$this->assertStringContainsString( $options['body_append'], $html );
			$this->assertStringNotContainsString( '<script>', $html );
		}
		// 許可タグの大文字入力と許可外タグのフォールバックを確認する。
		foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span', 'H2', 'script', 'h2 onclick="bad"', 'h2><img src=x>', '' ) as $tag ) {
			$expected = in_array( $tag, array( 'script', 'h2 onclick="bad"', 'h2><img src=x>', '' ), true ) ? 'h5' : strtolower( $tag );
			$options['title_tag'] = $tag;
			$html = VK_Component_Posts::get_view_body( $post, $options );
			$this->assertStringContainsString( '<' . $expected . ' class="vk_post_title ', $html );
			$this->assertStringContainsString( '</' . $expected . '>', $html );
		}
	}

	/**
	 * テキスト一覧でも新着文字をテキストとして出力する。
	 *
	 * @return void
	 */
	public function test_get_view_type_text() {
		$post = self::factory()->post->create_and_get();
		$GLOBALS['post'] = $post;
		foreach ( array( 'New!!' => 'New!!', '<b>New</b>' => '&lt;b&gt;New&lt;/b&gt;', 'New &amp; Next' => 'New &amp; Next' ) as $input => $expected ) {
			$options = VK_Component_Posts::get_loop_post_view_options( array( 'layout' => 'postListText', 'new_text' => $input ), $post );
			$html = VK_Component_Posts::get_view_type_text( $post, $options );
			$this->assertStringContainsString( '<span class="vk_post_title_new">' . $expected . '</span>', $html );
		}
	}

	/**
	 * 一覧のクラス属性から入力値が抜け出さないことを確認する。
	 *
	 * @return void
	 */
	public function test_get_loop() {
		$id = self::factory()->post->create();
		foreach ( array( 'custom spacing', 'x" data-injected="yes<>&', 'x&amp;y' ) as $value ) {
			$query = new WP_Query( array( 'p' => $id ) );
			$html = VK_Component_Posts::get_loop( $query, array(
				'layout' => $value, 'class_outer' => $value, 'marginTop' => $value, 'marginBottom' => $value,
			), array( 'class_loop_outer' => $value ) );
			// DOM 上に新しい属性・要素が作られず、入力値がクラス内に残る。
			$document = new DOMDocument();
			@$document->loadHTML( $html );
			$xpath = new DOMXPath( $document );
			$this->assertSame( 0, $xpath->query( '//*[@data-injected]' )->length );
			$outer = $document->getElementsByTagName( 'div' )->item( 0 );
			$this->assertStringContainsString( 'vk_posts-layout-' . html_entity_decode( $value, ENT_QUOTES ), $outer->getAttribute( 'class' ) );
			$this->assertSame( 4, substr_count( $outer->getAttribute( 'class' ), html_entity_decode( $value, ENT_QUOTES ) ) );
			wp_reset_postdata();
		}
	}

	/**
	 * 画像の追加クラスを属性内に閉じ込め、HTML の重ね合わせを保つ。
	 *
	 * @return void
	 */
	public function test_get_thumbnail_image() {
		$post = self::factory()->post->create_and_get();
		$options = VK_Component_Posts::get_loop_post_view_options( array(
			'image_default_url' => 'https://example.org/image.jpg',
			'overlay' => '<strong>Overlay</strong>',
		), $post );
		foreach ( array( 'custom', 'x" data-injected="yes<>&' ) as $value ) {
			$html = VK_Component_Posts::get_thumbnail_image( $post, $options, array( 'class_outer' => $value, 'class_image' => $value ) );
			$this->assertStringContainsString( '<strong>Overlay</strong>', $html );
			$this->assertStringNotContainsString( ' data-injected="', $html );
			$this->assertStringContainsString( 'class="vk_post_imgOuter ' . esc_attr( $value ) . '"', $html );
			$this->assertStringContainsString( 'class="vk_post_imgOuter_img ' . esc_attr( $value ) . '"', $html );
		}
	}

	/**
	 * ページ送りの属性・見出しの出力を確認する。
	 *
	 * @return void
	 */
	public function test_get_pagenation() {
		$query = new WP_Query();
		$query->max_num_pages = 2;
		foreach ( array( 'pagination', 'x" data-injected="yes<>' ) as $value ) {
			$html = VK_Component_Posts::get_pagenation( $query, array( 'class' => $value, 'aria_label' => $value, 'screen_reader_text' => $value ) );
			$this->assertStringContainsString( 'class="navigation ' . esc_attr( $value ) . '"', $html );
			$this->assertStringContainsString( 'aria-label="' . esc_attr( $value ) . '"', $html );
			$this->assertStringContainsString( '<h4 class="screen-reader-text">' . esc_html( $value ) . '</h4>', $html );
		}
	}

	/**
	 * アーカイブボタンの位置属性と意図した装飾 HTML を確認する。
	 *
	 * @return void
	 */
	public function test_get_archive_link_btn() {
		foreach ( array( 'right', 'x" data-injected="yes<>' ) as $value ) {
			$html = VK_Component_Posts::get_archive_link_btn( new WP_Query(), array( 'btn_position' => $value, 'btn_text' => '<strong>More</strong>' ) );
			$this->assertStringContainsString( '<div class="text-' . esc_attr( $value ) . '">', $html );
			$this->assertStringContainsString( '<strong>More</strong>', $html );
			$this->assertStringContainsString( '<i class="far fa-arrow-alt-circle-right"></i>', $html );
		}
	}
}
