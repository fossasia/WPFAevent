<?php
/**
 * Markdown conversion helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert the Markdown that Eventyay returns for rich text into HTML.
 *
 * The output is not sanitized here. Callers must pass it through wp_kses_post().
 */
class Wpfaevent_Markdown_Helper {

	/**
	 * Fenced code block delimiter.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const FENCE = '/^ {0,3}(`{3,}|~{3,}) *[\w#.+-]* *$/';

	/**
	 * ATX heading.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const HEADING = '/^ {0,3}(#{1,6}) +(.+?)(?: +#+)? *$/';

	/**
	 * Horizontal rule.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const RULE = '/^ {0,3}([-*_])(?: *\1){2,} *$/';

	/**
	 * Blockquote line.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const QUOTE = '/^ {0,3}> ?/';

	/**
	 * Ordered or unordered list item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const LIST_ITEM = '/^( *)([-*+]|\d{1,9}\.) +(\S.*)$/';

	/**
	 * Table row that separates the header from the body.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TABLE_RULE = '/^ {0,3}\|? *:?-+:? *(?:\| *:?-+:? *)*\|? *$/';

	/**
	 * Line that ends the paragraph above it without a blank line in between.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PARAGRAPH_END = '/^ {0,3}(?:#{1,6} |>|`{3}|~{3}|[-*+] +\S|1\. +\S|([-*_])(?: *\1){2,} *$)/';

	/**
	 * Convert Markdown to HTML.
	 *
	 * Text that already carries block-level HTML, and text without any Markdown,
	 * is returned untouched so it keeps rendering the way it did before.
	 *
	 * @since 1.0.0
	 *
	 * @param string $markdown Markdown text.
	 * @return string
	 */
	public static function to_html( $markdown ) {
		$text = trim( str_replace( array( "\r\n", "\r", "\t", "\x02", "\x03" ), array( "\n", "\n", '    ', '', '' ), (string) $markdown ) );

		if ( '' === $text || self::has_block_html( $text ) ) {
			return $markdown;
		}

		$html = self::render_blocks( explode( "\n", $text ) );

		if ( self::collapse_whitespace( str_replace( array( '<p>', '</p>', '<br />' ), '', $html ) ) === self::collapse_whitespace( $text ) ) {
			return $markdown;
		}

		return $html;
	}

	/**
	 * Determine whether text carries block-level HTML outside of code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text to inspect.
	 * @return bool
	 */
	private static function has_block_html( $text ) {
		$text = preg_replace( '/(`+|~{3,}).+?\1/s', '', $text );

		return 1 === preg_match( '#</?(?:p|div|h[1-6]|ul|ol|li|blockquote|pre|table|hr)\b#i', (string) $text );
	}

	/**
	 * Collapse every run of whitespace into a single space.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text to collapse.
	 * @return string
	 */
	private static function collapse_whitespace( $text ) {
		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * Render lines of Markdown as block-level HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lines Markdown lines.
	 * @return string
	 * @phpstan-param array<int, string> $lines
	 */
	private static function render_blocks( $lines ) {
		$blocks = array();
		$count  = count( $lines );
		$index  = 0;

		while ( $index < $count ) {
			$line      = $lines[ $index ];
			$fence_end = self::find_fence_end( $lines, $index );

			if ( '' === trim( $line ) ) {
				++$index;
			} elseif ( $fence_end ) {
				$blocks[] = '<pre><code>' . esc_html( implode( "\n", array_slice( $lines, $index + 1, $fence_end - $index - 1 ) ) ) . '</code></pre>';
				$index    = $fence_end + 1;
			} elseif ( preg_match( self::HEADING, $line, $heading ) ) {
				++$index;
				$level    = strlen( $heading[1] );
				$blocks[] = '<h' . $level . '>' . self::render_inline( $heading[2] ) . '</h' . $level . '>';
			} elseif ( preg_match( self::RULE, $line ) ) {
				++$index;
				$blocks[] = '<hr />';
			} elseif ( preg_match( self::QUOTE, $line ) ) {
				$quote = array();

				while ( $index < $count && preg_match( self::QUOTE, $lines[ $index ] ) ) {
					$quote[] = (string) preg_replace( self::QUOTE, '', $lines[ $index ] );
					++$index;
				}

				$blocks[] = '<blockquote>' . self::render_blocks( $quote ) . '</blockquote>';
			} elseif ( preg_match( self::LIST_ITEM, $line, $item ) ) {
				$list = array();

				while ( $index < $count ) {
					$next = $index;

					while ( $next < $count && '' === trim( $lines[ $next ] ) ) {
						++$next;
					}

					if ( $next === $count || ! self::continues_list( $lines[ $next ], $item ) ) {
						break;
					}

					if ( $next > $index ) {
						$list[] = '';
					}

					$list[] = $lines[ $next ];
					$index  = $next + 1;
				}

				$blocks[] = self::render_list( $list );
			} elseif ( $index + 1 < $count && self::is_table_header( $line, $lines[ $index + 1 ] ) ) {
				$head   = self::split_table_row( $line );
				$rows   = array();
				$index += 2;

				while ( $index < $count && false !== strpos( $lines[ $index ], '|' ) ) {
					$rows[] = self::render_table_row( array_slice( array_pad( self::split_table_row( $lines[ $index ] ), count( $head ), '' ), 0, count( $head ) ), 'td' );
					++$index;
				}

				$blocks[] = '<table><thead>' . self::render_table_row( $head, 'th' ) . '</thead>' . ( $rows ? "\n<tbody>" . implode( "\n", $rows ) . '</tbody>' : '' ) . '</table>';
			} else {
				$paragraph = array( trim( $line ) );
				++$index;

				while ( $index < $count && '' !== trim( $lines[ $index ] ) && ! preg_match( self::PARAGRAPH_END, $lines[ $index ] ) ) {
					$paragraph[] = trim( $lines[ $index ] );
					++$index;
				}

				$blocks[] = '<p>' . self::render_inline( implode( "\n", $paragraph ) ) . '</p>';
			}
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Find the line that closes the code fence opened at the given line.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lines Markdown lines.
	 * @param int   $index Index of the candidate opening fence.
	 * @return int Index of the closing fence, or 0 when the line opens no closed fence.
	 * @phpstan-param array<int, string> $lines
	 */
	private static function find_fence_end( $lines, $index ) {
		if ( ! preg_match( self::FENCE, $lines[ $index ], $fence ) ) {
			return 0;
		}

		$count = count( $lines );

		for ( $end = $index + 1; $end < $count; ++$end ) {
			if ( preg_match( '/^ {0,3}' . $fence[1] . '+ *$/', $lines[ $end ] ) ) {
				return $end;
			}
		}

		return 0;
	}

	/**
	 * Determine whether a line belongs to the list opened by the given item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line  Line to inspect.
	 * @param array  $first Matches of the item that opened the list.
	 * @return bool
	 * @phpstan-param array<int, string> $first
	 */
	private static function continues_list( $line, $first ) {
		if ( strspn( $line, ' ' ) >= strlen( $first[1] ) + 2 ) {
			return true;
		}

		return ! preg_match( self::RULE, $line )
			&& preg_match( self::LIST_ITEM, $line, $item )
			&& ctype_digit( $item[2][0] ) === ctype_digit( $first[2][0] );
	}

	/**
	 * Render the lines of one list, nesting the items that are indented.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lines List lines, starting with the first item.
	 * @return string
	 * @phpstan-param array<int, string> $lines
	 */
	private static function render_list( $lines ) {
		preg_match( self::LIST_ITEM, $lines[0], $first );

		$tag   = ctype_digit( $first[2][0] ) ? 'ol' : 'ul';
		$start = 'ol' === $tag && 1 !== (int) $first[2] ? ' start="' . (int) $first[2] . '"' : '';
		$items = array();

		foreach ( $lines as $line ) {
			if ( preg_match( self::LIST_ITEM, $line, $item ) && strlen( $item[1] ) < strlen( $first[1] ) + 2 ) {
				$items[] = array( $item[3] );
			} else {
				$items[ count( $items ) - 1 ][] = $line;
			}
		}

		foreach ( $items as $key => $item ) {
			$text = (string) array_shift( $item );

			while ( $item && '' !== trim( $item[0] ) && ! preg_match( self::LIST_ITEM, $item[0] ) ) {
				$text .= "\n" . trim( (string) array_shift( $item ) );
			}

			$nested = '';

			if ( $item ) {
				$indent = min(
					array_map(
						static function ( $line ) {
							return '' === trim( $line ) ? PHP_INT_MAX : strspn( $line, ' ' );
						},
						$item
					)
				);
				$nested = self::render_blocks(
					array_map(
						static function ( $line ) use ( $indent ) {
							return (string) substr( $line, $indent );
						},
						$item
					)
				);
			}

			$items[ $key ] = '<li>' . self::render_inline( $text ) . ( '' !== $nested ? "\n" . $nested : '' ) . '</li>';
		}

		return '<' . $tag . $start . '>' . implode( "\n", $items ) . '</' . $tag . '>';
	}

	/**
	 * Determine whether two lines form a table header and its separator row.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Candidate header row.
	 * @param string $next Candidate separator row.
	 * @return bool
	 */
	private static function is_table_header( $line, $next ) {
		return false !== strpos( $line, '|' )
			&& preg_match( self::TABLE_RULE, $next )
			&& count( self::split_table_row( $line ) ) === count( self::split_table_row( $next ) );
	}

	/**
	 * Split a table row into its cells.
	 *
	 * @since 1.0.0
	 *
	 * @param string $row Table row.
	 * @return array
	 * @phpstan-return array<int, string>
	 */
	private static function split_table_row( $row ) {
		$row   = (string) preg_replace( '/^\||(?<!\\\\)\|$/', '', trim( $row ) );
		$cells = preg_split( '/(?<!\\\\)\|(?=(?:[^`]*`[^`]*`)*[^`]*$)/', $row );

		return array_map( 'trim', $cells ? $cells : array( $row ) );
	}

	/**
	 * Render the cells of one table row.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $cells Table cells.
	 * @param string $tag   Cell tag, th or td.
	 * @return string
	 * @phpstan-param array<int, string> $cells
	 */
	private static function render_table_row( $cells, $tag ) {
		$cells = array_map( array( __CLASS__, 'render_inline' ), $cells );

		return '<tr><' . $tag . '>' . implode( '</' . $tag . ">\n<" . $tag . '>', $cells ) . '</' . $tag . '></tr>';
	}

	/**
	 * Render inline Markdown as HTML.
	 *
	 * Finished markup is swapped for placeholders so later steps cannot alter it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Inline Markdown.
	 * @return string
	 */
	private static function render_inline( $text ) {
		$stash = array();
		$hold  = static function ( $html ) use ( &$stash ) {
			$stash[] = $html;

			return "\x02" . ( count( $stash ) - 1 ) . "\x03";
		};
		$link  = static function ( $url, $label, $title = '' ) use ( $hold ) {
			$url = esc_url( $url );

			if ( '' === $url ) {
				return $label;
			}

			return $hold( '<a href="' . $url . '"' . ( '' !== $title ? ' title="' . esc_attr( $title ) . '"' : '' ) . '>' . $label . '</a>' );
		};
		$steps = array(
			// Code spans.
			'/(?<!`)(`+)(?!`)(.+?)(?<!`)\1(?!`)/s'    => static function ( $matches ) use ( $hold ) {
				return $hold( '<code>' . esc_html( trim( $matches[2] ) ) . '</code>' );
			},
			// URLs in angle brackets.
			'/<(https?:\/\/[^\s<>]+)>/i'              => static function ( $matches ) use ( $link ) {
				return $link( $matches[1], esc_html( $matches[1] ) );
			},
			// Inline HTML.
			'/<a\s[^>]*>.*?<\/a>|<\/?[a-z][^<>]*>/is' => static function ( $matches ) use ( $hold ) {
				return $hold( $matches[0] );
			},
			// Backslash escapes.
			'/\\\\([\\\\`*_{}\[\]()#+\-.!~|>])/'      => static function ( $matches ) use ( $hold ) {
				return $hold( $matches[1] );
			},
			// Links and images.
			'/!?\[((?:[^\[\]]|\[[^\[\]]*\])+)\]\(\s*([^\s()<>]*(?:\([^\s()<>]*\)[^\s()<>]*)*)(?:\s+"(.*?)")?\s*\)/s' => static function ( $matches ) use ( $link ) {
				return $link( $matches[2], self::render_emphasis( $matches[1] ), isset( $matches[3] ) ? $matches[3] : '' );
			},
			// Bare URLs, without the punctuation or emphasis markers that trail them.
			'/(?<![\w\/])https?:\/\/(?:[^\s<>"()\x02]|\([^\s<>"()\x02]*\))*(?:[^\s<>"()\x02.,;:!?\'*_~]|\([^\s<>"()\x02]*\))/i' => static function ( $matches ) use ( $link ) {
				return $link( $matches[0], esc_html( $matches[0] ) );
			},
		);

		foreach ( $steps as $pattern => $callback ) {
			$text = self::replace( $pattern, $callback, $text );
		}

		$text = str_replace( "\n", "<br />\n", self::render_emphasis( $text ) );

		for ( $key = count( $stash ) - 1; $key >= 0; --$key ) {
			$text = str_replace( "\x02" . $key . "\x03", $stash[ $key ], $text );
		}

		return $text;
	}

	/**
	 * Render bold, italic and strikethrough markers as HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Inline Markdown.
	 * @return string
	 */
	private static function render_emphasis( $text ) {
		$rules = array(
			'/\*\*\*(?=[^\s*])(.+?)(?<=[^\s*])\*\*\*/s' => '<strong><em>$1</em></strong>',
			'/\*\*(?=[^\s*])(.+?)(?<=\S)\*\*(?!\*)/s'   => '<strong>$1</strong>',
			'/(?<![\w\x80-\xff])__(?=[^\s_])(.+?)(?<=\S)__(?![\w\x80-\xff])/s' => '<strong>$1</strong>',
			'/\*(?=[^\s*])(.+?)(?<=[^\s*])\*/s'         => '<em>$1</em>',
			'/(?<![\w\x80-\xff])_(?=[^\s_])(.+?)(?<=[^\s_])_(?![\w\x80-\xff])/s' => '<em>$1</em>',
			'/~~(?=\S)(.+?)(?<=\S)~~/s'                 => '<del>$1</del>',
		);

		foreach ( $rules as $pattern => $replacement ) {
			$text = self::replace( $pattern, $replacement, $text );
		}

		return $text;
	}

	/**
	 * Run a regular expression replacement, keeping the text when the engine fails.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $pattern     Regular expression.
	 * @param string|callable $replacement Replacement string or callback.
	 * @param string          $text        Text to search.
	 * @return string
	 */
	private static function replace( $pattern, $replacement, $text ) {
		$result = is_string( $replacement )
			? preg_replace( $pattern, $replacement, $text )
			: preg_replace_callback( $pattern, $replacement, $text );

		return null === $result ? $text : $result;
	}
}
