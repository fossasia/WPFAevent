<?php
/**
 * Unit tests for the Eventyay Markdown to HTML conversion.
 *
 * @package Wpfaevent
 */

/**
 * Covers Markdown conversion for rich text imported from Eventyay.
 */
class MarkdownHelperTest extends WP_UnitTestCase {
	/**
	 * A description shaped like the ones Eventyay returns.
	 *
	 * @var string
	 */
	private $document = "**Where open source meets**\n\nA one-day event.\nOrganized by FOSSASIA.\n\n#### **Important access information**\n\nSee [the docs](https://example.org/docs) for details.\n\n- Community Pass includes catering\n- Supporter Ticket includes a T-shirt";

	/**
	 * Assert that no tag in the markup can run script.
	 *
	 * @param string $html    Markup to inspect.
	 * @param string $message Failure context.
	 * @return void
	 */
	private function assert_safe_markup( $html, $message ) {
		preg_match_all( '/<[^>]*>/', $html, $tags );

		foreach ( $tags[0] as $tag ) {
			$this->assertDoesNotMatchRegularExpression( '/^<\/?(?:script|iframe|style|object|embed)\b/i', $tag, $message );
			$this->assertDoesNotMatchRegularExpression( '/\son\w+\s*=/i', (string) preg_replace( '/"[^"]*"/', '""', $tag ), $message );
			$this->assertDoesNotMatchRegularExpression( '/(?:javascript|vbscript|data)\s*:/i', $tag, $message );
		}
	}

	/**
	 * ATX headings from level one to six should become heading tags.
	 */
	public function test_converts_atx_headings() {
		for ( $level = 1; $level <= 6; $level++ ) {
			$this->assertSame(
				'<h' . $level . '>Venue</h' . $level . '>',
				Wpfaevent_Markdown_Helper::to_html( str_repeat( '#', $level ) . ' Venue' )
			);
		}

		$this->assertSame( '<h2>Venue</h2>', Wpfaevent_Markdown_Helper::to_html( '## Venue ##' ) );
		$this->assertSame( '<h4><strong>Access</strong></h4>', Wpfaevent_Markdown_Helper::to_html( '#### **Access**' ) );
	}

	/**
	 * Hashtags and runs of more than six hashes are not headings.
	 */
	public function test_leaves_hashtags_and_seven_hashes_alone() {
		$this->assertSame( '#fossasia is trending', Wpfaevent_Markdown_Helper::to_html( '#fossasia is trending' ) );
		$this->assertSame( '####### Venue', Wpfaevent_Markdown_Helper::to_html( '####### Venue' ) );
	}

	/**
	 * Blank lines should split paragraphs and single newlines should become line breaks.
	 */
	public function test_converts_paragraphs_and_line_breaks() {
		$this->assertSame(
			"<p><strong>First</strong> line<br />\nsecond line</p>\n\n<p>Second paragraph</p>",
			Wpfaevent_Markdown_Helper::to_html( "**First** line\r\nsecond line\r\n\r\n\r\nSecond paragraph" )
		);
	}

	/**
	 * Bold, italic and strikethrough markers should become inline tags.
	 */
	public function test_converts_bold_italic_and_strikethrough() {
		$this->assertSame( '<p><strong>bold</strong></p>', Wpfaevent_Markdown_Helper::to_html( '**bold**' ) );
		$this->assertSame( '<p><strong>bold</strong></p>', Wpfaevent_Markdown_Helper::to_html( '__bold__' ) );
		$this->assertSame( '<p><em>italic</em></p>', Wpfaevent_Markdown_Helper::to_html( '*italic*' ) );
		$this->assertSame( '<p><em>italic</em></p>', Wpfaevent_Markdown_Helper::to_html( '_italic_' ) );
		$this->assertSame( '<p><del>gone</del></p>', Wpfaevent_Markdown_Helper::to_html( '~~gone~~' ) );
		$this->assertSame( '<p><strong><em>both</em></strong></p>', Wpfaevent_Markdown_Helper::to_html( '***both***' ) );
		$this->assertSame(
			'<p>A <strong>bold <em>and italic</em></strong> phrase</p>',
			Wpfaevent_Markdown_Helper::to_html( 'A **bold *and italic*** phrase' )
		);
	}

	/**
	 * Backslash escapes should keep Markdown punctuation literal.
	 */
	public function test_backslash_escapes_keep_punctuation_literal() {
		$this->assertSame( '<p>*not emphasis*</p>', Wpfaevent_Markdown_Helper::to_html( '\*not emphasis\*' ) );
	}

	/**
	 * Inline code should be wrapped and its content escaped, not converted.
	 */
	public function test_converts_inline_code_and_escapes_its_content() {
		$this->assertSame(
			'<p>Use <code>&lt;p&gt; **x** a_b_c</code> here</p>',
			Wpfaevent_Markdown_Helper::to_html( 'Use `<p> **x** a_b_c` here' )
		);
	}

	/**
	 * Fenced code blocks should keep their content verbatim and escaped.
	 */
	public function test_converts_fenced_code_blocks_and_escapes_their_content() {
		$this->assertSame(
			"<p>Example:</p>\n\n<pre><code>&lt;b&gt;x&lt;/b&gt;\n\n**y** # z</code></pre>\n\n<p>Done</p>",
			Wpfaevent_Markdown_Helper::to_html( "Example:\n```php\n<b>x</b>\n\n**y** # z\n```\nDone" )
		);
		$this->assertSame(
			"<pre><code>```\ninner\n```</code></pre>",
			Wpfaevent_Markdown_Helper::to_html( "~~~\n```\ninner\n```\n~~~" )
		);
	}

	/**
	 * A fence that is never closed, or that carries prose, is ordinary text.
	 */
	public function test_leaves_unclosed_fences_alone() {
		$this->assertSame( "```\nnever closed", Wpfaevent_Markdown_Helper::to_html( "```\nnever closed" ) );
		$this->assertSame(
			"<p>```prose after the fence<br />\n<strong>kept</strong></p>\n\n<p>```</p>",
			Wpfaevent_Markdown_Helper::to_html( "```prose after the fence\n**kept**\n```" )
		);
	}

	/**
	 * Inline links should become anchors, with the title when one is given.
	 */
	public function test_converts_links_with_optional_title() {
		$this->assertSame(
			'<p>See <a href="https://example.org/docs">the docs</a> for details.</p>',
			Wpfaevent_Markdown_Helper::to_html( 'See [the docs](https://example.org/docs) for details.' )
		);
		$this->assertSame(
			'<p><a href="https://example.org/docs" title="Read &quot;the&quot; docs">the <strong>docs</strong></a></p>',
			Wpfaevent_Markdown_Helper::to_html( '[the **docs**](https://example.org/docs "Read "the" docs")' )
		);
		$this->assertSame(
			'<p><a href="https://example.org/wiki/Event_(disambiguation)">wiki</a></p>',
			Wpfaevent_Markdown_Helper::to_html( '[wiki](https://example.org/wiki/Event_(disambiguation))' )
		);
	}

	/**
	 * Image syntax should become a link because Eventyay does not render images.
	 */
	public function test_converts_image_syntax_to_a_link() {
		$html = Wpfaevent_Markdown_Helper::to_html( '![Venue map](https://example.org/map.png)' );

		$this->assertSame( '<p><a href="https://example.org/map.png">Venue map</a></p>', $html );
	}

	/**
	 * Bare and angle-bracketed URLs should become links.
	 */
	public function test_autolinks_bare_urls() {
		$this->assertSame(
			'<p>Visit <a href="https://example.org/call_for_papers">https://example.org/call_for_papers</a> today.</p>',
			Wpfaevent_Markdown_Helper::to_html( 'Visit https://example.org/call_for_papers today.' )
		);
		$this->assertSame(
			'<p>Docs (<a href="https://example.org/a?b=1&#038;c=2">https://example.org/a?b=1&amp;c=2</a>), wiki <a href="https://example.org/Event_(2026)">https://example.org/Event_(2026)</a>.</p>',
			Wpfaevent_Markdown_Helper::to_html( 'Docs (https://example.org/a?b=1&c=2), wiki https://example.org/Event_(2026).' )
		);
		$this->assertSame(
			'<p><strong>Register: <a href="https://example.org/sign_up">https://example.org/sign_up</a></strong></p>',
			Wpfaevent_Markdown_Helper::to_html( '**Register: https://example.org/sign_up**' )
		);
		$this->assertSame(
			'<p><a href="https://example.org/a_b">https://example.org/a_b</a></p>',
			Wpfaevent_Markdown_Helper::to_html( '<https://example.org/a_b>' )
		);
	}

	/**
	 * URLs that are already linked must not be linked a second time.
	 */
	public function test_does_not_autolink_inside_existing_links() {
		$this->assertSame(
			'<p><a href="https://example.org">https://example.org</a></p>',
			Wpfaevent_Markdown_Helper::to_html( '[https://example.org](https://example.org)' )
		);
		$this->assertSame(
			'<p><strong>Site</strong>: <a href="https://example.org">https://example.org</a></p>',
			Wpfaevent_Markdown_Helper::to_html( '**Site**: <a href="https://example.org">https://example.org</a>' )
		);
	}

	/**
	 * Each unordered list marker should produce a list.
	 */
	public function test_converts_unordered_lists() {
		foreach ( array( '-', '*', '+' ) as $marker ) {
			$this->assertSame(
				"<ul><li>One <strong>item</strong></li>\n<li>Two</li></ul>",
				Wpfaevent_Markdown_Helper::to_html( $marker . ' One **item**' . "\n" . $marker . ' Two' ),
				$marker
			);
		}
	}

	/**
	 * Numbered lines should produce an ordered list.
	 */
	public function test_converts_ordered_lists() {
		$this->assertSame(
			"<ol><li>One</li>\n<li>Two</li></ol>",
			Wpfaevent_Markdown_Helper::to_html( "1. One\n2. Two" )
		);
		$this->assertSame(
			"<ol start=\"3\"><li>Three</li>\n<li>Four</li></ol>",
			Wpfaevent_Markdown_Helper::to_html( "3. Three\n4. Four" )
		);
	}

	/**
	 * Indented items should nest inside the item above them.
	 */
	public function test_converts_nested_lists() {
		$this->assertSame(
			"<ul><li>Day one\n<ul><li>Keynote</li>\n<li>Workshops</li></ul></li>\n<li>Day two\n<ol><li>Talks</li></ol></li></ul>",
			Wpfaevent_Markdown_Helper::to_html( "- Day one\n  - Keynote\n  - Workshops\n- Day two\n    1. Talks" )
		);
	}

	/**
	 * A list should start right after a paragraph and end when the list type changes.
	 */
	public function test_separates_lists_from_surrounding_blocks() {
		$this->assertSame(
			"<p>Topics:</p>\n\n<ul><li>Cloud</li>\n<li>AI</li></ul>\n\n<ol><li>First</li></ol>\n\n<p>After</p>",
			Wpfaevent_Markdown_Helper::to_html( "Topics:\n\n- Cloud\n\n- AI\n1. First\n\nAfter" )
		);
	}

	/**
	 * Quoted lines should become a blockquote.
	 */
	public function test_converts_blockquotes() {
		$this->assertSame(
			"<blockquote><p>Open source is <strong>great</strong><br />\nsaid everyone</p></blockquote>",
			Wpfaevent_Markdown_Helper::to_html( "> Open source is **great**\n> said everyone" )
		);
	}

	/**
	 * Three or more rule markers on a line should become a horizontal rule.
	 */
	public function test_converts_horizontal_rules() {
		foreach ( array( '---', '***', '___', '- - -' ) as $rule ) {
			$this->assertSame(
				"<p>Above</p>\n\n<hr />\n\n<p>Below</p>",
				Wpfaevent_Markdown_Helper::to_html( "Above\n\n" . $rule . "\n\nBelow" ),
				$rule
			);
		}
	}

	/**
	 * Pipe tables should become HTML tables.
	 */
	public function test_converts_tables() {
		$this->assertSame(
			"<table><thead><tr><th>Day</th>\n<th>Topic</th></tr></thead>\n<tbody><tr><td>Mon</td>\n<td><strong>AI</strong></td></tr>\n<tr><td>Tue</td>\n<td>a | b</td></tr>\n<tr><td>Wed</td>\n<td><code>a|b</code></td></tr>\n<tr><td>Thu</td>\n<td></td></tr></tbody></table>",
			Wpfaevent_Markdown_Helper::to_html( "| Day | Topic |\n|-----|:-----:|\n| Mon | **AI** |\nTue | a \\| b\n| Wed | `a|b` |\n| Thu |" )
		);
	}

	/**
	 * A pipe in ordinary text is not a table.
	 */
	public function test_leaves_pipes_without_a_separator_row_alone() {
		$this->assertSame( "Cloud | AI\nData | Web", Wpfaevent_Markdown_Helper::to_html( "Cloud | AI\nData | Web" ) );
	}

	/**
	 * Underscores inside words and spaced asterisks are not emphasis.
	 */
	public function test_leaves_intraword_underscores_and_spaced_asterisks_alone() {
		$text = 'Set snake_case_words so that 2 * 3 * 4 stays.';

		$this->assertSame( $text, Wpfaevent_Markdown_Helper::to_html( $text ) );
		$this->assertSame(
			'<p><strong>Note</strong>: snake_case_words and 2 * 3 * 4</p>',
			Wpfaevent_Markdown_Helper::to_html( '**Note**: snake_case_words and 2 * 3 * 4' )
		);
	}

	/**
	 * URLs containing underscores or asterisks must keep them.
	 */
	public function test_keeps_urls_with_underscores_and_asterisks_intact() {
		$linked = Wpfaevent_Markdown_Helper::to_html( '[a_b](https://example.org/a_b_c/*x*/d_e) and _real_ emphasis' );
		$bare   = Wpfaevent_Markdown_Helper::to_html( 'Go to https://example.org/a_b_c/*x*/d_e and https://example.org/f_g_h now' );

		$this->assertSame( '<p><a href="https://example.org/a_b_c/*x*/d_e">a_b</a> and <em>real</em> emphasis</p>', $linked );
		$this->assertStringContainsString( '<a href="https://example.org/a_b_c/*x*/d_e">', $bare );
		$this->assertStringContainsString( '<a href="https://example.org/f_g_h">', $bare );
		$this->assertStringNotContainsString( '<em>', $bare );
	}

	/**
	 * Values that already carry block-level HTML must come back untouched.
	 */
	public function test_returns_block_level_html_unchanged() {
		$values = array(
			'<p>Hello <strong>world</strong></p>',
			"Intro\n<ul>\n<li>**kept** as is</li>\n</ul>",
			'<div class="about">1. text</div>',
		);

		foreach ( $values as $value ) {
			$this->assertSame( $value, Wpfaevent_Markdown_Helper::to_html( $value ) );
		}
	}

	/**
	 * Block-level tags quoted as code do not make a value HTML.
	 */
	public function test_converts_markdown_that_mentions_block_tags_in_code() {
		$this->assertSame(
			'<p>Why <code>&lt;div&gt;</code> soup <strong>hurts</strong></p>',
			Wpfaevent_Markdown_Helper::to_html( 'Why `<div>` soup **hurts**' )
		);
	}

	/**
	 * Text without Markdown must come back byte for byte.
	 */
	public function test_returns_plain_text_unchanged() {
		$values = array(
			'',
			'A single sentence.',
			"  First line.\r\nSecond line.\n\nSecond paragraph with R&D, 5 < 6 and a_b.  ",
			'Tom &amp; Jerry <b>inline</b> tags',
		);

		foreach ( $values as $value ) {
			$this->assertSame( $value, Wpfaevent_Markdown_Helper::to_html( $value ) );
		}
	}

	/**
	 * Converting the converter's own output again must change nothing.
	 */
	public function test_conversion_is_idempotent() {
		$parser = new Wpfaevent_JSONAPI_Parser();
		$values = array(
			$this->document,
			"# Title\n\n> quote with `code`\n\n1. one\n   - nested\n2. two\n\n---\n\n| A | B |\n|---|---|\n| 1 | 2 |",
			"```\n<p>**x**</p>\n`tick`\n```",
			'Stray ` tick and **bold**' . "\n\n" . 'another ` tick',
			'\*escaped\* and [a & b](https://example.org/?a=1&b=2 "T & t")',
			'Visit https://example.org/a_b today',
			'Plain text only, R&D and 5 < 6.',
		);

		foreach ( $values as $value ) {
			$once = Wpfaevent_Markdown_Helper::to_html( $value );
			$this->assertSame( $once, Wpfaevent_Markdown_Helper::to_html( $once ), $value );

			$stored = $parser->eventyay_rich_text_value( $value );
			$this->assertSame( $stored, $parser->eventyay_rich_text_value( $stored ), $value );
		}
	}

	/**
	 * The templates run wpautop() over stored values, which must not reshape converted markup.
	 */
	public function test_converted_markup_survives_wpautop() {
		$markdown = $this->document . "\n\n- Day one\n  - Keynote\n- Day two\n\n> quoted\n\n```\ncode\n\nmore\n```\n\n---\n\n| A | B |\n|---|---|\n| 1 | 2 |";
		$html     = Wpfaevent_Markdown_Helper::to_html( $markdown );
		$rendered = wpautop( $html );

		foreach ( array( '<p>', '</p>', '<br' ) as $tag ) {
			$this->assertSame( substr_count( $html, $tag ), substr_count( $rendered, $tag ), $tag );
		}
	}

	/**
	 * Plain text must render exactly as it did before the conversion existed.
	 */
	public function test_plain_text_renders_as_before() {
		$parser = new Wpfaevent_JSONAPI_Parser();
		$text   = "First line.\nSecond line.\n\nSecond paragraph with R&D.";

		$this->assertSame( wpautop( wp_kses_post( $text ) ), wpautop( $parser->eventyay_rich_text_value( $text ) ) );
	}

	/**
	 * Links with script-capable protocols must lose the link and keep the text.
	 */
	public function test_drops_links_with_unsafe_protocols() {
		$this->assertSame( '<p>x</p>', Wpfaevent_Markdown_Helper::to_html( '[x](javascript:alert(1))' ) );
		$this->assertSame( '<p>x</p>', Wpfaevent_Markdown_Helper::to_html( '[x](JaVaScRiPt:alert(1))' ) );
		$this->assertSame( '<p>x</p>', Wpfaevent_Markdown_Helper::to_html( '[x](data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)' ) );
		$this->assertSame( '<p>x</p>', Wpfaevent_Markdown_Helper::to_html( '![x](vbscript:msgbox(1))' ) );
	}

	/**
	 * Nothing hostile in the remote text may survive the import funnel.
	 */
	public function test_hostile_input_is_neutralised_by_the_import_funnel() {
		$parser   = new Wpfaevent_JSONAPI_Parser();
		$payloads = array(
			'[x](javascript:alert(1))',
			'[x](data:text/html,<script>alert(1)</script>)',
			'[x](https://example.org "t" onmouseover="alert(1)")',
			'[x](https://example.org/"onmouseover="alert(1))',
			'**bold** <script>alert(1)</script>',
			'**bold** <img src=x onerror=alert(1)>',
			'[<img src=x onerror=alert(1)>](https://example.org)',
			'<javascript:alert(1)> **bold**',
			'<https://example.org/"onmouseover="alert(1)>',
			'`</code><script>alert(1)</script>`',
			"```\n</code></pre><script>alert(1)</script>\n```",
			"| a |\n|---|\n| <iframe src=javascript:alert(1)> |",
			"**bold** \x020\x03 <a href=\"javascript:alert(1)\">x</a>",
			'# <a href="data:text/html,x" onclick="alert(1)">x</a>',
		);

		foreach ( $payloads as $payload ) {
			$html = $parser->eventyay_rich_text_value( $payload );

			$this->assert_safe_markup( $html, $payload );
			$this->assertSame( wp_kses_post( $html ), $html, $payload );
			$this->assertStringNotContainsString( "\x02", $html, $payload );
		}
	}

	/**
	 * Script typed as code should be shown as text.
	 */
	public function test_code_content_is_escaped() {
		$this->assertSame(
			'<p><strong>Run</strong> <code>&lt;script&gt;alert(1)&lt;/script&gt;</code></p>',
			Wpfaevent_Markdown_Helper::to_html( '**Run** `<script>alert(1)</script>`' )
		);
	}

	/**
	 * A whole description should convert block by block.
	 */
	public function test_converts_a_whole_description() {
		$this->assertSame(
			"<p><strong>Where open source meets</strong></p>\n\n<p>A one-day event.<br />\nOrganized by FOSSASIA.</p>\n\n<h4><strong>Important access information</strong></h4>\n\n<p>See <a href=\"https://example.org/docs\">the docs</a> for details.</p>\n\n<ul><li>Community Pass includes catering</li>\n<li>Supporter Ticket includes a T-shirt</li></ul>",
			Wpfaevent_Markdown_Helper::to_html( $this->document )
		);
	}

	/**
	 * Stripping the tags, as the hero lead text does, must keep words apart.
	 */
	public function test_stripped_markup_keeps_blocks_apart() {
		$this->assertSame(
			'Where open source meets A one-day event. Organized by FOSSASIA. Important access information See the docs for details. Community Pass includes catering Supporter Ticket includes a T-shirt',
			preg_replace( '/\s+/', ' ', wp_strip_all_tags( Wpfaevent_Markdown_Helper::to_html( $this->document ) ) )
		);
	}

	/**
	 * The parser funnel should turn Markdown into HTML.
	 */
	public function test_parser_rich_text_value_converts_markdown() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame( '<p><strong>Asha Rao</strong> is a contributor.</p>', $parser->eventyay_rich_text_value( '**Asha Rao** is a contributor.' ) );
		$this->assertSame(
			'<p><strong>Asha Rao</strong> is a contributor.</p>',
			$parser->eventyay_first_present_rich_text( array( 'biography' => '**Asha Rao** is a contributor.' ), array( 'biography' ) )
		);
	}

	/**
	 * The parser funnel should convert multi-language values.
	 */
	public function test_parser_rich_text_value_converts_multi_language_values() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame( '<h4>Access</h4>', $parser->eventyay_rich_text_value( array( 'en' => '#### Access' ) ) );
		$this->assertSame( '<h4>Zugang</h4>', $parser->eventyay_rich_text_value( array( 'de' => '#### Zugang' ) ) );
	}

	/**
	 * The event description should be converted from the Eventyay settings shape.
	 */
	public function test_parser_event_description_converts_frontpage_text() {
		$parser = new Wpfaevent_JSONAPI_Parser();
		$event  = array(
			'name'     => array( 'en' => 'Demo' ),
			'settings' => array(
				'frontpage_text' => array( 'en' => $this->document ),
			),
		);

		$description = $parser->eventyay_event_description( $event );

		$this->assertStringContainsString( '<p><strong>Where open source meets</strong></p>', $description );
		$this->assertStringContainsString( '<h4><strong>Important access information</strong></h4>', $description );
		$this->assertStringContainsString( '<a href="https://example.org/docs">the docs</a>', $description );
		$this->assertStringContainsString( '<ul><li>Community Pass includes catering</li>', $description );
	}

	/**
	 * The resource utils funnel should turn Markdown into HTML.
	 */
	public function test_resource_utils_rich_text_value_converts_markdown() {
		$utils = new Wpfaevent_JSONAPI_Resource_Utils();

		$this->assertSame( '<p><strong>Asha Rao</strong> is a contributor.</p>', $utils->eventyay_rich_text_value( ' **Asha Rao** is a contributor. ' ) );
		$this->assertSame( '<h4>Access</h4>', $utils->eventyay_rich_text_value( array( 'en' => '#### Access' ) ) );
		$this->assertSame( '<h4>Zugang</h4>', $utils->eventyay_rich_text_value( array( 'de' => '#### Zugang' ) ) );
		$this->assertSame(
			"<ul><li>One</li>\n<li>Two</li></ul>",
			$utils->eventyay_first_present_rich_text( array( 'abstract' => "- One\n- Two" ), array( 'abstract' ) )
		);
	}

	/**
	 * The admin importer funnel should turn Markdown into HTML.
	 */
	public function test_importer_rich_text_value_converts_markdown() {
		$importer = new Wpfaevent_Eventyay_Importer();
		$method   = new ReflectionMethod( $importer, 'eventyay_rich_text_value' );

		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$this->assertSame( '<p><strong>Asha Rao</strong> is a contributor.</p>', $method->invoke( $importer, ' **Asha Rao** is a contributor. ' ) );
		$this->assertSame( '<h4>Access</h4>', $method->invoke( $importer, array( 'en' => '#### Access' ) ) );
		$this->assertSame( '<h4>Zugang</h4>', $method->invoke( $importer, array( 'de' => '#### Zugang' ) ) );
	}

	/**
	 * Run the converter and return the time it took.
	 *
	 * @param string $input Markdown.
	 * @return array{string, float}
	 */
	private function timed( $input ) {
		$start  = microtime( true );
		$output = Wpfaevent_Markdown_Helper::to_html( $input );

		return array( $output, microtime( true ) - $start );
	}

	/**
	 * Inputs built to make a converter do quadratic work or recurse without end.
	 *
	 * @return array<string, array{string}>
	 */
	public function hostile_provider() {
		$rep  = static function ( $unit, $bytes ) {
			return str_repeat( $unit, (int) max( 1, floor( $bytes / strlen( $unit ) ) ) );
		};
		$n    = 100000;
		$nest = '';

		for ( $i = 0, $size = 0; $size < $n; ++$i ) {
			$line  = str_repeat( ' ', 2 * $i ) . "- a\n";
			$nest .= $line;
			$size += strlen( $line );
		}

		return array(
			'stars'         => array( 'a ' . $rep( '*', $n ) ),
			'star_open'     => array( $rep( '*a ', $n ) ),
			'star2_open'    => array( $rep( '**a ', $n ) ),
			'star3_open'    => array( $rep( '***a ', $n ) ),
			'under_open'    => array( $rep( '_a ', $n ) ),
			'under2_open'   => array( $rep( '__a ', $n ) ),
			'tilde_open'    => array( 'x ' . $rep( '~~a ', $n ) ),
			'backticks'     => array( 'a ' . $rep( '`', $n ) ),
			'brackets'      => array( 'a ' . $rep( '[', $n ) ),
			'bracket_pairs' => array( $rep( '[a](', $n ) ),
			'link_title'    => array( $rep( '[a](b "', $n ) ),
			'link_parens'   => array( '[a](' . $rep( '(a)', $n ) ),
			'pipes'         => array( 'a ' . $rep( '|', $n ) ),
			'table_wide'    => array( $rep( 'a|', $n / 2 ) . "a\n" . $rep( '-|', $n / 2 ) . '-' ),
			'table_amp'     => array( $rep( 'a|', 280 ) . "a\n" . $rep( '-|', 280 ) . "-\n" . $rep( "|\n", $n - 1200 ) ),
			'table_rule_sp' => array( "a|b\n-:" . $rep( ' ', $n ) . 'x' ),
			'heading_sp'    => array( '# a' . $rep( ' ', $n ) . 'x' ),
			'heading_hash'  => array( '# a' . $rep( ' #', $n ) . 'x' ),
			'quotes'        => array( $rep( '>', $n ) . 'a' ),
			'quotes_sp'     => array( $rep( '> ', $n ) . 'a' ),
			'fence_open'    => array( $rep( "```a\n", $n ) . 'x' ),
			'list_nest'     => array( $nest ),
			'list_flat'     => array( $rep( "- a\n", $n ) ),
			'list_blank'    => array( "- a\n" . $rep( "\n", $n ) . '- b' ),
			'rule_sp'       => array( "a\n\n-" . $rep( ' ', $n ) . '- x' ),
			'lt_open'       => array( 'a ' . $rep( '<a ', $n ) ),
			'a_open'        => array( '*a* ' . $rep( '<a x>', $n ) ),
			'angle_url'     => array( '*a* ' . $rep( '<http://a', $n ) ),
			'bare_url'      => array( '*a* http://a/' . $rep( '(', $n ) ),
			'bare_urls'     => array( $rep( 'http://a.b/c ', $n ) ),
			'escapes'       => array( $rep( '\\*', $n ) ),
			'mixed_marks'   => array( $rep( '_*', $n ) ),
			'html_open'     => array( $rep( "<div>\n", $n ) ),
			'pre_open'      => array( $rep( "<pre>\n\n", $n ) ),
			'mixed_words'   => array( $rep( "**bold** and *em* with `code` and [l](http://x.y)\n", $n ) ),
		);
	}

	/**
	 * Hostile input must finish quickly, stay bounded and leave no placeholder behind.
	 *
	 * The time limit is skipped under a coverage tool, which slows every run down.
	 *
	 * @dataProvider hostile_provider
	 *
	 * @param string $input Hostile Markdown.
	 */
	public function test_hostile_input_is_linear_and_bounded( $input ) {
		list( $output, $seconds ) = $this->timed( $input );

		if ( ! extension_loaded( 'xdebug' ) && ! extension_loaded( 'pcov' ) ) {
			$this->assertLessThan( 1.5, $seconds );
		}

		$this->assertLessThanOrEqual( 4 * strlen( $input ), strlen( $output ) );
		$this->assertStringNotContainsString( "\x02", $output );
		$this->assertStringNotContainsString( "\x03", $output );
	}

	/**
	 * A line of nothing but quote markers must not recurse once per marker.
	 */
	public function test_deeply_nested_blockquotes_use_bounded_memory() {
		$before = memory_get_peak_usage();
		$output = Wpfaevent_Markdown_Helper::to_html( str_repeat( '>', 9000 ) . 'a' );

		$this->assertLessThan( 16 * 1024 * 1024, memory_get_peak_usage() - $before );
		$this->assertSame( 1, substr_count( $output, '<blockquote>' ) > 0 ? 1 : 0 );
		$this->assertLessThanOrEqual( Wpfaevent_Markdown_Helper::MAX_DEPTH, substr_count( $output, '<blockquote>' ) );
		$this->assertStringContainsString( '>a', $output );
	}

	/**
	 * Nesting beyond the bound stays literal text.
	 */
	public function test_nesting_beyond_the_bound_stays_literal() {
		$quote = Wpfaevent_Markdown_Helper::to_html( str_repeat( '> ', 10 ) . 'deep **text**' );
		$list  = Wpfaevent_Markdown_Helper::to_html( "- a\n  - b\n    - c\n      - d\n        - e\n          - f\n            - g\n              - h" );

		$this->assertSame( Wpfaevent_Markdown_Helper::MAX_DEPTH, substr_count( $quote, '<blockquote>' ) );
		$this->assertStringContainsString( '<strong>text</strong>', $quote );
		$this->assertLessThanOrEqual( Wpfaevent_Markdown_Helper::MAX_DEPTH + 1, substr_count( $list, '<ul>' ) );
		$this->assertStringContainsString( '- h', $list );
	}

	/**
	 * Text crowded with emphasis markers keeps them literal, so pairing them cannot exhaust memory.
	 */
	public function test_emphasis_markers_beyond_the_bound_stay_literal() {
		$pair = '*a* ';
		$many = str_repeat( $pair, Wpfaevent_Markdown_Helper::MAX_MARKERS / 2 + 1 );
		$few  = str_repeat( $pair, Wpfaevent_Markdown_Helper::MAX_MARKERS / 2 );

		$this->assertSame( $many, Wpfaevent_Markdown_Helper::to_html( $many ) );
		$this->assertSame( Wpfaevent_Markdown_Helper::MAX_MARKERS / 2, substr_count( Wpfaevent_Markdown_Helper::to_html( $few ), '<em>a</em>' ) );

		if ( function_exists( 'memory_reset_peak_usage' ) ) {
			memory_reset_peak_usage();
			$before = memory_get_usage();
			Wpfaevent_Markdown_Helper::to_html( str_repeat( '_*', Wpfaevent_Markdown_Helper::MAX_LENGTH / 2 ) );

			$this->assertLessThan( 32 * 1024 * 1024, memory_get_peak_usage() - $before );
		}
	}

	/**
	 * Input above the size cap comes back exactly as given.
	 */
	public function test_input_above_the_size_cap_is_returned_unchanged() {
		$input = "**bold**\n\n" . str_repeat( 'x', Wpfaevent_Markdown_Helper::MAX_LENGTH );

		$this->assertSame( $input, Wpfaevent_Markdown_Helper::to_html( $input ) );
		$this->assertStringContainsString( '<strong>', Wpfaevent_Markdown_Helper::to_html( "**bold**\n\n" . str_repeat( 'x', 1000 ) ) );
	}

	/**
	 * Tables wider than the bound are text, so a padded table cannot multiply the output.
	 */
	public function test_tables_beyond_the_column_bound_stay_text() {
		$cells = Wpfaevent_Markdown_Helper::MAX_TABLE_COLUMNS + 1;
		$head  = implode( '|', array_fill( 0, $cells, 'a' ) );
		$rule  = implode( '|', array_fill( 0, $cells, '-' ) );

		$this->assertStringNotContainsString( '<table>', Wpfaevent_Markdown_Helper::to_html( "**x**\n\n$head\n$rule\n$head" ) );
		$this->assertStringContainsString( '<table>', Wpfaevent_Markdown_Helper::to_html( "**x**\n\na|b\n-|-\n1|2" ) );
	}

	/**
	 * Escaped characters inside a link target are part of the URL.
	 */
	public function test_backslash_escapes_inside_link_targets_are_resolved() {
		$this->assertSame(
			'<p><a href="https://example.org/my_talk_slides.pdf">Slides</a></p>',
			Wpfaevent_Markdown_Helper::to_html( '[Slides](https://example.org/my\_talk\_slides.pdf)' )
		);
		$this->assertSame(
			'<p><a href="https://en.wikipedia.org/wiki/Foo_(bar)">Wiki</a></p>',
			Wpfaevent_Markdown_Helper::to_html( '[Wiki](https://en.wikipedia.org/wiki/Foo\_\(bar\))' )
		);
		$this->assertSame(
			'<p>See <a href="https://example.org/faq#tickets">the FAQ</a> and <code>code</code> first</p>',
			Wpfaevent_Markdown_Helper::to_html( 'See [the FAQ](https://example.org/faq\#tickets) and `code` first' )
		);
		$this->assertSame(
			'<p><a href="https://example.org/a_b" title="x*y">t</a></p>',
			Wpfaevent_Markdown_Helper::to_html( '[t](https://example.org/a\_b "x*y")' )
		);
	}

	/**
	 * Escaped characters inside a bare URL are part of the URL.
	 */
	public function test_backslash_escapes_inside_bare_urls_are_resolved() {
		$this->assertSame(
			'<p>Slides: <a href="https://example.org/my_talk_slides.pdf">https://example.org/my_talk_slides.pdf</a></p>',
			Wpfaevent_Markdown_Helper::to_html( 'Slides: https://example.org/my\_talk\_slides.pdf' )
		);
		$this->assertSame(
			'<p><a href="https://example.org/a_">https://example.org/a_</a></p>',
			Wpfaevent_Markdown_Helper::to_html( 'https://example.org/a\_' )
		);
	}

	/**
	 * A placeholder must never end up in a URL or a title.
	 */
	public function test_no_placeholder_reaches_a_url_or_title() {
		$inputs = array(
			'[a](https://example.org/`x`/\_y "t `c` \*")',
			'[a](https://example.org/\*\*\*) **b** `c`',
			'https://example.org/\(a\)\*\_ **b**',
			'<https://example.org/\_> `c`',
		);

		foreach ( $inputs as $input ) {
			$html = Wpfaevent_Markdown_Helper::to_html( $input );

			$this->assertDoesNotMatchRegularExpression( '/\x02|\x03|href="[^"]*\d\d*"/', $html, $input );
			$this->assertStringNotContainsString( "\x02", $html, $input );
		}
	}

	/**
	 * A bare URL ends where CJK, Thai or full-width text begins.
	 */
	public function test_bare_urls_stop_at_cjk_and_full_width_characters() {
		$this->assertSame(
			'<p>请访问<a href="https://fossasia.org">https://fossasia.org</a>，<strong>免费</strong>入场</p>',
			Wpfaevent_Markdown_Helper::to_html( '请访问https://fossasia.org，**免费**入场' )
		);
		$this->assertSame(
			'<p>公式サイトは<a href="https://fossasia.org">https://fossasia.org</a>です。</p>',
			Wpfaevent_Markdown_Helper::to_html( '公式サイトはhttps://fossasia.orgです。' )
		);
		$this->assertSame(
			'<p>เว็บไซต์<a href="https://fossasia.org">https://fossasia.org</a>ครับ</p>',
			Wpfaevent_Markdown_Helper::to_html( 'เว็บไซต์https://fossasia.orgครับ' )
		);
		$this->assertSame(
			'<p>官网：<a href="https://fossasia.org">https://fossasia.org</a>。欢迎<strong>参加</strong></p>',
			Wpfaevent_Markdown_Helper::to_html( '官网：https://fossasia.org。欢迎**参加**' )
		);
	}

	/**
	 * Hosts and paths in Latin scripts keep their accented characters.
	 */
	public function test_bare_urls_keep_latin_script_unicode() {
		$this->assertSame(
			'<p><a href="https://münchen.de/straße">https://münchen.de/straße</a></p>',
			Wpfaevent_Markdown_Helper::to_html( 'https://münchen.de/straße' )
		);
	}

	/**
	 * Emphasis markers next to non-Latin letters are part of the word.
	 */
	public function test_underscores_next_to_non_latin_letters_are_not_emphasis() {
		$values = array(
			'日本語_語_日本語',
			'日本語__語__日本語',
			'привет_мир_привет',
			'ไทย_ไทย_ไทย',
		);

		foreach ( $values as $value ) {
			$this->assertSame( $value, Wpfaevent_Markdown_Helper::to_html( $value ) );
		}

		$this->assertSame( '<p>日本語 <em>語</em> 日本語</p>', Wpfaevent_Markdown_Helper::to_html( '日本語 _語_ 日本語' ) );
		$this->assertSame( '<p>日本語 <strong>語</strong> 日本語</p>', Wpfaevent_Markdown_Helper::to_html( '日本語 __語__ 日本語' ) );
	}

	/**
	 * Block-level HTML passes through and the Markdown around it is converted.
	 */
	public function test_converts_markdown_around_block_html() {
		$this->assertSame(
			"<div class=\"text-center\">Welcome!</div>\n\n<h2>Tickets</h2>\n\n<ul><li><strong>Standard</strong></li>\n<li><a href=\"https://example.org/vip\">VIP</a></li></ul>",
			Wpfaevent_Markdown_Helper::to_html( "<div class=\"text-center\">Welcome!</div>\n\n## Tickets\n\n- **Standard**\n- [VIP](https://example.org/vip)" )
		);
		$this->assertSame(
			"<p>Part 1</p>\n\n<hr>\n\n<p><strong>Part 2</strong></p>\n\n<ul><li>item</li></ul>",
			Wpfaevent_Markdown_Helper::to_html( "Part 1\n<hr>\n**Part 2**\n\n- item" )
		);
		$this->assertSame(
			"<h1>Title</h1>\n\n<table><tr><td>**kept**</td></tr></table>\n\n<p><em>after</em></p>",
			Wpfaevent_Markdown_Helper::to_html( "# Title\n\n<table><tr><td>**kept**</td></tr></table>\n*after*" )
		);
		$this->assertSame(
			"<H2>Title</H2>\n\n<ul><li>a list</li></ul>",
			Wpfaevent_Markdown_Helper::to_html( "<H2>Title</H2>\n\n- a list" )
		);
	}

	/**
	 * Every block tag that wpautop() knows is passed through, not wrapped in a paragraph.
	 */
	public function test_block_tags_known_to_wpautop_are_never_wrapped_in_paragraphs() {
		$tags = array( 'div', 'section', 'article', 'aside', 'header', 'footer', 'nav', 'figure', 'figcaption', 'details', 'summary', 'address', 'dl', 'fieldset', 'form', 'table', 'ul', 'ol', 'blockquote', 'pre', 'p', 'h3', 'menu' );

		foreach ( $tags as $tag ) {
			$html = Wpfaevent_Markdown_Helper::to_html( "<$tag>inner</$tag>\n\n**after**" );

			$this->assertSame( "<$tag>inner</$tag>\n\n<p><strong>after</strong></p>", $html, $tag );
		}

		$html = Wpfaevent_Markdown_Helper::to_html( "<details><summary>More</summary>\n\n**Hidden** text\n\n</details>\n\n**after**" );

		$this->assertDoesNotMatchRegularExpression( '#<p>\s*</?(?:details|summary)|</?(?:details|summary)[^>]*>\s*</p>#', $html );
		$this->assertStringContainsString( '<strong>after</strong>', $html );
	}

	/**
	 * Text that only looks like a tag is not block HTML.
	 */
	public function test_text_that_resembles_a_tag_is_not_html() {
		$this->assertSame(
			"<p>We show that for n&lt;p the <strong>algorithm</strong> converges.</p>\n\n<ul><li>fast</li>\n<li>simple</li></ul>",
			str_replace( 'n<p the', 'n&lt;p the', Wpfaevent_Markdown_Helper::to_html( "We show that for n<p the **algorithm** converges.\n\n- fast\n- simple" ) )
		);
		$this->assertStringContainsString( '<strong>Paul</strong>', Wpfaevent_Markdown_Helper::to_html( 'Contact **Paul** <p.smith@example.org> today' ) );
		$this->assertStringStartsWith( '<p>', Wpfaevent_Markdown_Helper::to_html( '<p.smith@example.org> wrote **this**' ) );
		$this->assertStringStartsWith( '<p>', Wpfaevent_Markdown_Helper::to_html( "<p the **algorithm**\n\n- a" ) );
	}

	/**
	 * Text that is entirely HTML comes back byte for byte.
	 */
	public function test_entirely_html_text_is_unchanged() {
		$values = array(
			"<div class=\"a\">\n<p>One</p>\n\n<p>Two **x**</p>\n</div>",
			"<ul>\n<li>one</li>\n<li>two</li>\n</ul>\n\n<p>Tail</p>",
			"<table>\n<tr><td>1. a</td></tr>\n</table>",
			"<h2>Title</h2>\n<p>Body</p>",
		);

		foreach ( $values as $value ) {
			$this->assertSame( $value, Wpfaevent_Markdown_Helper::to_html( $value ) );
		}
	}

	/**
	 * A pre block keeps its blank lines and its content.
	 */
	public function test_pre_blocks_survive_blank_lines() {
		$pre = "<pre>line one\n\n  **line** three\n\n- four</pre>";

		$this->assertSame( $pre, Wpfaevent_Markdown_Helper::to_html( $pre ) );
		$this->assertSame( "<h2>Title</h2>\n\n$pre\n\n<p><em>after</em></p>", Wpfaevent_Markdown_Helper::to_html( "## Title\n\n$pre\n\n*after*" ) );
	}

	/**
	 * The converter's own output must survive a second pass, block HTML included.
	 */
	public function test_output_with_block_html_is_stable() {
		$values = array(
			"<div class=\"text-center\">Welcome!</div>\n\n## Tickets\n\n- **Standard**\n- [VIP](https://example.org/vip)",
			"Part 1\n<hr>\n**Part 2**\n\n- item",
			"## Title\n\n<pre>a\n\nb</pre>\n\n> q1\n>\n> q2\n\n- a\n\n  para\n\n- b",
			"<details><summary>More</summary>\n\n**Hidden**\n\n</details>",
			"Line one<br>Line two\n\n1. a\n\n   > quote\n2. b",
		);

		foreach ( $values as $value ) {
			$once = Wpfaevent_Markdown_Helper::to_html( $value );

			$this->assertSame( $once, Wpfaevent_Markdown_Helper::to_html( $once ), $value );
		}
	}

	/**
	 * A list line under a paragraph is part of the paragraph.
	 */
	public function test_a_list_cannot_interrupt_a_paragraph() {
		$values = array(
			"Price: 50 EUR*\n* excludes VAT",
			"Call us:\n+ 49 30 123456",
			"\"Talk is cheap. Show me the code.\"\n- Linus Torvalds",
			"Version\n1. 5 is out",
			"Topics:\n- Cloud\n- AI",
		);

		foreach ( $values as $value ) {
			$this->assertSame( $value, Wpfaevent_Markdown_Helper::to_html( $value ) );
		}

		$this->assertSame(
			"<p><strong>Call us</strong>:<br />\n+ 49 30 123456</p>",
			Wpfaevent_Markdown_Helper::to_html( "**Call us**:\n+ 49 30 123456" )
		);
	}

	/**
	 * A list that opens the text or follows a blank line is a list.
	 */
	public function test_a_list_after_a_blank_line_or_at_the_start_is_a_list() {
		$this->assertSame( "<ul><li>One</li>\n<li>Two</li></ul>", Wpfaevent_Markdown_Helper::to_html( "- One\n- Two" ) );
		$this->assertSame( "<p>Topics:</p>\n\n<ul><li>Cloud</li></ul>", Wpfaevent_Markdown_Helper::to_html( "Topics:\n\n- Cloud" ) );
		$this->assertSame( "<h2>Topics</h2>\n\n<ul><li>Cloud</li></ul>", Wpfaevent_Markdown_Helper::to_html( "## Topics\n- Cloud" ) );
	}

	/**
	 * Headings, quotes, rules and fences still end a paragraph.
	 */
	public function test_headings_quotes_rules_and_fences_end_a_paragraph() {
		$this->assertSame( "<p>a</p>\n\n<h2>Head</h2>\n\n<p>b</p>", Wpfaevent_Markdown_Helper::to_html( "a\n## Head\nb" ) );
		$this->assertSame( "<p>a</p>\n\n<h2>Head</h2>\n\n<p>b</p>", Wpfaevent_Markdown_Helper::to_html( "a\n##Head\nb" ) );
		$this->assertSame( "<p>a</p>\n\n<blockquote><p>b</p></blockquote>", Wpfaevent_Markdown_Helper::to_html( "a\n> b" ) );
		$this->assertSame( "<p>a</p>\n\n<hr />\n\n<p>b</p>", Wpfaevent_Markdown_Helper::to_html( "a\n___\nb" ) );
		$this->assertSame( "<p>a</p>\n\n<pre><code>c</code></pre>\n\n<p>b</p>", Wpfaevent_Markdown_Helper::to_html( "a\n```\nc\n```\nb" ) );
	}

	/**
	 * Ordered lists show the numbers that were typed.
	 */
	public function test_ordered_lists_keep_the_typed_numbers() {
		$this->assertSame(
			"<ol start=\"2015\"><li>Graduated.</li>\n<li value=\"2018\">Joined.</li>\n<li value=\"2021\">Founded.</li></ol>",
			Wpfaevent_Markdown_Helper::to_html( "2015. Graduated.\n2018. Joined.\n2021. Founded." )
		);
		$this->assertSame(
			"<ol start=\"3\"><li>Three</li>\n<li>Four</li>\n<li value=\"9\">Nine</li>\n<li>Ten</li></ol>",
			Wpfaevent_Markdown_Helper::to_html( "3. Three\n4. Four\n9. Nine\n10. Ten" )
		);
		$this->assertSame( "<ol><li>One</li>\n<li>Two</li></ol>", Wpfaevent_Markdown_Helper::to_html( "1. One\n2. Two" ) );
	}

	/**
	 * Two or more hashes without a space are a heading, a single hash is not.
	 */
	public function test_hashes_without_a_space_are_a_heading_from_two_hashes() {
		$this->assertSame( '<h2>Agenda</h2>', Wpfaevent_Markdown_Helper::to_html( '##Agenda' ) );
		$this->assertSame( '<h4><strong>Agenda</strong></h4>', Wpfaevent_Markdown_Helper::to_html( '####**Agenda**' ) );
		$this->assertSame( '#Agenda', Wpfaevent_Markdown_Helper::to_html( '#Agenda' ) );
		$this->assertSame( '##', Wpfaevent_Markdown_Helper::to_html( '##' ) );
		$this->assertSame( '####### Agenda', Wpfaevent_Markdown_Helper::to_html( '####### Agenda' ) );
	}

	/**
	 * A line over a row of dashes or equals signs is a heading.
	 */
	public function test_setext_headings() {
		$this->assertSame( '<h2>Agenda</h2>', Wpfaevent_Markdown_Helper::to_html( "Agenda\n------" ) );
		$this->assertSame( '<h1>About the <strong>event</strong></h1>', Wpfaevent_Markdown_Helper::to_html( "About the **event**\n===============" ) );
		$this->assertSame( "<h2>Agenda</h2>\n\n<p>Text</p>", Wpfaevent_Markdown_Helper::to_html( "Agenda\n---\nText" ) );
		$this->assertSame( "<p>Text</p>\n\n<hr />\n\n<p>More</p>", Wpfaevent_Markdown_Helper::to_html( "Text\n\n---\n\nMore" ) );
		$this->assertSame( "a\n=== b", Wpfaevent_Markdown_Helper::to_html( "a\n=== b" ) );
	}

	/**
	 * Text whose only markup is a line break tag comes back byte for byte.
	 */
	public function test_line_break_tags_alone_do_not_count_as_markdown() {
		$values = array(
			'Line one<br />Line two',
			'Line one<br>Line two<br/>Line three',
			"Line one<br>\nLine two",
			"First<br />\n\nSecond<BR>part",
		);

		foreach ( $values as $value ) {
			$this->assertSame( $value, Wpfaevent_Markdown_Helper::to_html( $value ) );
			$this->assertSame( wp_kses_post( $value ), ( new Wpfaevent_JSONAPI_Parser() )->eventyay_rich_text_value( $value ) );
		}
	}

	/**
	 * Windows line endings convert like Unix ones for every block type.
	 */
	public function test_crlf_converts_like_lf_for_every_block_type() {
		$input = "## Tickets\n\n- Standard\n- VIP\n\n---\n\n| A | B |\n|---|---|\n| 1 | 2 |\n\n```\ncode\n```\n\n> quote\n\n1. one\n2. two\n\nText\nline two\n";

		$this->assertSame( Wpfaevent_Markdown_Helper::to_html( $input ), Wpfaevent_Markdown_Helper::to_html( str_replace( "\n", "\r\n", $input ) ) );
		$this->assertSame( Wpfaevent_Markdown_Helper::to_html( $input ), Wpfaevent_Markdown_Helper::to_html( str_replace( "\n", "\r", $input ) ) );
		$this->assertStringNotContainsString( "\r", Wpfaevent_Markdown_Helper::to_html( str_replace( "\n", "\r\n", $input ) ) );
	}

	/**
	 * Tabs act as indentation.
	 */
	public function test_tabs_are_expanded() {
		$this->assertSame( "<ul><li>One</li>\n<li>Two</li></ul>", Wpfaevent_Markdown_Helper::to_html( "-\tOne\n-\tTwo" ) );
		$this->assertSame(
			"<ul><li>Day\n<ul><li>Keynote</li></ul></li></ul>",
			Wpfaevent_Markdown_Helper::to_html( "- Day\n\t- Keynote" )
		);
	}

	/**
	 * Tags in the text are kept away from the URL and emphasis rules.
	 */
	public function test_inline_html_is_protected() {
		$this->assertSame(
			'<p><img src="https://example.org/a_b_c.png" alt="x_y_z"> and <strong>bold</strong></p>',
			Wpfaevent_Markdown_Helper::to_html( '<img src="https://example.org/a_b_c.png" alt="x_y_z"> and **bold**' )
		);
		$this->assertSame(
			'<p><span title="*x* http://a.b/c_d_e">one</span> <em>two</em></p>',
			Wpfaevent_Markdown_Helper::to_html( '<span title="*x* http://a.b/c_d_e">one</span> *two*' )
		);
	}

	/**
	 * Indented lines continue the item above them.
	 */
	public function test_list_items_keep_their_continuation_lines() {
		$this->assertSame(
			"<ul><li>First line<br />\nsecond line<br />\nthird <strong>line</strong></li>\n<li>Next</li></ul>",
			Wpfaevent_Markdown_Helper::to_html( "- First line\n  second line\n  third **line**\n- Next" )
		);
		$this->assertSame(
			"<ul><li>One\n<p>para in item</p></li>\n<li>Two</li></ul>",
			Wpfaevent_Markdown_Helper::to_html( "- One\n\n  para in item\n\n- Two" )
		);
	}

	/**
	 * A header row and a separator row with different cell counts are not a table.
	 */
	public function test_table_header_and_separator_must_have_the_same_cell_count() {
		$this->assertStringNotContainsString( '<table>', Wpfaevent_Markdown_Helper::to_html( "Speaker | Engineer\n---" ) );
		$this->assertStringNotContainsString( '<table>', Wpfaevent_Markdown_Helper::to_html( "a | b | c\n--|--" ) );
		$this->assertStringContainsString( '<table>', Wpfaevent_Markdown_Helper::to_html( "a | b\n--|--" ) );
		$this->assertSame( '<h2>Speaker | Engineer</h2>', Wpfaevent_Markdown_Helper::to_html( "Speaker | Engineer\n---" ) );
	}

	/**
	 * The admin importer resolves array values in its own key order.
	 */
	public function test_importer_funnel_resolves_values_in_its_own_key_order() {
		$importer = new Wpfaevent_Eventyay_Importer();
		$method   = new ReflectionMethod( $importer, 'eventyay_rich_text_value' );

		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$value = array(
			'en'       => '**en**',
			'default'  => '**default**',
			'value'    => '**value**',
			'raw'      => '**raw**',
			'rendered' => '**rendered**',
			'content'  => '**content**',
			'body'     => '**body**',
			'html'     => '**html**',
		);

		foreach ( array( 'html', 'body', 'content', 'rendered', 'raw', 'value', 'default', 'en' ) as $key ) {
			$this->assertSame( "<p><strong>$key</strong></p>", $method->invoke( $importer, $value ), $key );
			unset( $value[ $key ] );
		}

		$this->assertSame( '', $method->invoke( $importer, $value ) );
		$this->assertSame(
			'<p><em>x</em></p>',
			$method->invoke(
				$importer,
				array(
					'html' => '',
					'body' => array( 'en' => '*x*' ),
				)
			)
		);
		$this->assertSame( '<p><em>y</em></p>', $method->invoke( $importer, array( 'other' => '*y*' ) ) );
	}

	/**
	 * Markdown inside an anchor is converted, but its text is not linked again.
	 */
	public function test_markdown_inside_anchors_is_converted_without_relinking() {
		$this->assertSame(
			'<p>Visit <a href="https://example.org">my <em>site</em></a> for <strong>more</strong>.</p>',
			Wpfaevent_Markdown_Helper::to_html( 'Visit <a href="https://example.org">my *site*</a> for **more**.' )
		);
		$this->assertStringContainsString(
			'<a href="https://example.org"><strong>bold link</strong> https://example.org/a_b</a> <a href="https://x.org">https://x.org</a>',
			Wpfaevent_Markdown_Helper::to_html( '<a href="https://example.org">**bold link** https://example.org/a_b</a> https://x.org' )
		);
		$this->assertSame(
			'<p><a href="https://a.org">[x](https://b.org) <em>em</em></a></p>',
			Wpfaevent_Markdown_Helper::to_html( '<a href="https://a.org">[x](https://b.org) *em*</a>' )
		);
	}

	/**
	 * A line that follows a list item without a blank line belongs to that item.
	 */
	public function test_list_items_accept_lazy_continuation_lines() {
		$this->assertSame(
			"<ul><li>one<br />\ncontinued lazily</li>\n<li>two</li></ul>",
			Wpfaevent_Markdown_Helper::to_html( "- one\ncontinued lazily\n- two" )
		);
		$this->assertSame(
			"<ul><li>one</li></ul>\n\n<p>after a blank line</p>",
			Wpfaevent_Markdown_Helper::to_html( "- one\n\nafter a blank line" )
		);
		$this->assertSame(
			"<ul><li>one</li></ul>\n\n<h2>Heading</h2>",
			Wpfaevent_Markdown_Helper::to_html( "- one\n## Heading" )
		);
	}

	/**
	 * A line that follows a quote without a blank line belongs to that quote.
	 */
	public function test_blockquotes_accept_lazy_continuation_lines() {
		$this->assertSame(
			"<blockquote><p>Quote line one<br />\ncontinued lazily</p></blockquote>",
			Wpfaevent_Markdown_Helper::to_html( "> Quote line one\ncontinued lazily" )
		);
		$this->assertSame(
			"<blockquote><p>\"Talk is cheap.\"<br />\n- Linus Torvalds</p></blockquote>",
			Wpfaevent_Markdown_Helper::to_html( "> \"Talk is cheap.\"\n- Linus Torvalds" )
		);
		$this->assertSame(
			"<blockquote><p>quoted</p></blockquote>\n\n<p>after a blank line</p>",
			Wpfaevent_Markdown_Helper::to_html( "> quoted\n\nafter a blank line" )
		);
	}

	/**
	 * Output that would grow far beyond its input is dropped in favour of the original text.
	 */
	public function test_output_that_would_explode_is_returned_unchanged() {
		$cells = Wpfaevent_Markdown_Helper::MAX_TABLE_COLUMNS;
		$input = implode( '|', array_fill( 0, $cells, 'a' ) ) . "\n" . implode( '|', array_fill( 0, $cells, '-' ) ) . "\n" . str_repeat( "|\n", 3000 );

		$this->assertSame( $input, Wpfaevent_Markdown_Helper::to_html( $input ) );
		$this->assertStringContainsString( '<table>', Wpfaevent_Markdown_Helper::to_html( implode( '|', array_fill( 0, $cells, 'a' ) ) . "\n" . implode( '|', array_fill( 0, $cells, '-' ) ) . "\n|\n|\n" ) );
	}
}
