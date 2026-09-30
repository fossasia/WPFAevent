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
			Wpfaevent_Markdown_Helper::to_html( "Topics:\n- Cloud\n\n- AI\n1. First\n\nAfter" )
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
			"<H2>Title</H2>\n\n- not a list",
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
}
