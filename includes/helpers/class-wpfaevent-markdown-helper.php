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
 * The text comes from a remote API, so every step is linear in the size of the
 * input and nesting is bounded. The output is not sanitized here. Callers must
 * pass it through wp_kses_post().
 */
class Wpfaevent_Markdown_Helper {

	/**
	 * Largest input, in bytes, that is converted. Larger text is returned unchanged.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_LENGTH = 200000;

	/**
	 * Deepest nesting of blockquotes and lists. Anything deeper stays literal.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_DEPTH = 6;

	/**
	 * Widest table that is converted. Wider ones stay text.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_TABLE_COLUMNS = 20;

	/**
	 * Largest allowed growth of the output, as a multiple of the input.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_EXPANSION = 8;

	/**
	 * Most runs of emphasis markers paired in one piece of text. Beyond it they stay literal.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_MARKERS = 10000;

	/**
	 * Characters that a backslash escapes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ESCAPABLE = '\\`*_{}[]()#+-.!~|>';

	/**
	 * Characters PCRE treats as white space.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const SPACE = " \t\n\r\x0b\x0c";

	/**
	 * Block-level tags, the list wpautop() uses.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BLOCK_TAGS = 'table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary';

	/**
	 * Ordered or unordered list item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const LIST_ITEM = '/^( *)([-*+]|\d{1,9}\.) +(\S.*)$/';

	/**
	 * Blockquote line.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const QUOTE = '/^ {0,3}> ?/';

	/**
	 * Convert Markdown to HTML.
	 *
	 * Text without any Markdown, and text that is already HTML, is returned
	 * untouched so it keeps rendering the way it did before.
	 *
	 * @since 1.0.0
	 *
	 * @param string $markdown Markdown text.
	 * @return string
	 */
	public static function to_html( $markdown ) {
		$source = (string) $markdown;

		if ( strlen( $source ) > self::MAX_LENGTH ) {
			return $source;
		}

		$text = trim( str_replace( array( "\r\n", "\r", "\t", "\x02", "\x03" ), array( "\n", "\n", '    ', '', '' ), $source ) );

		if ( '' === $text ) {
			return $source;
		}

		$html = self::render_blocks( explode( "\n", $text ), 0 );

		if ( self::strip_layout( $html ) === self::strip_layout( $text ) || strlen( $html ) > self::MAX_EXPANSION * strlen( $text ) + 1024 ) {
			return $source;
		}

		return $html;
	}

	/**
	 * Reduce text to its words, dropping the paragraph and line break tags that conversion adds.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text or HTML.
	 * @return string
	 */
	private static function strip_layout( $text ) {
		$text = (string) preg_replace( '#</?p>|<br\s*/?>#i', '', $text );

		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * Render lines of Markdown as block-level HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lines Markdown lines.
	 * @param int   $depth Nesting depth of the lines.
	 * @return string
	 * @phpstan-param array<int, string> $lines
	 */
	private static function render_blocks( $lines, $depth ) {
		$blocks = array();
		$count  = count( $lines );
		$index  = 0;
		$cache  = array(
			'fence' => array(),
			'pre'   => null,
		);
		$nest   = $depth < self::MAX_DEPTH;

		while ( $index < $count ) {
			$line = $lines[ $index ];

			if ( '' === trim( $line ) ) {
				++$index;
				continue;
			}

			$fence_end = self::find_fence_end( $lines, $index, $cache );
			$html_end  = $fence_end ? -1 : self::find_html_end( $lines, $index, $cache );
			$heading   = self::match_heading( $line );

			if ( $fence_end ) {
				$blocks[] = '<pre><code>' . esc_html( implode( "\n", array_slice( $lines, $index + 1, $fence_end - $index - 1 ) ) ) . '</code></pre>';
				$index    = $fence_end + 1;
			} elseif ( $html_end >= 0 ) {
				$blocks[] = implode( "\n", array_slice( $lines, $index, $html_end - $index + 1 ) );
				$index    = $html_end + 1;
			} elseif ( $heading ) {
				++$index;
				$blocks[] = '<h' . $heading[0] . '>' . self::render_inline( $heading[1] ) . '</h' . $heading[0] . '>';
			} elseif ( self::is_rule( $line ) ) {
				++$index;
				$blocks[] = '<hr />';
			} elseif ( $nest && preg_match( self::QUOTE, $line ) ) {
				$quote = array();

				while ( $index < $count ) {
					$candidate = $lines[ $index ];

					if ( preg_match( self::QUOTE, $candidate ) ) {
						$quote[] = (string) preg_replace( self::QUOTE, '', $candidate );
					} elseif ( '' !== trim( $quote[ count( $quote ) - 1 ] ) && '' !== trim( $candidate ) && ! self::ends_paragraph( $candidate, true ) ) {
						$quote[] = trim( $candidate );
					} else {
						break;
					}

					++$index;
				}

				$blocks[] = '<blockquote>' . self::render_blocks( $quote, $depth + 1 ) . '</blockquote>';
			} elseif ( $nest && preg_match( self::LIST_ITEM, $line, $item ) ) {
				$list = array();

				while ( $index < $count ) {
					$next = $index;

					while ( $next < $count && '' === trim( $lines[ $next ] ) ) {
						++$next;
					}

					if ( $next === $count || ! self::continues_list( $lines[ $next ], $item, $next === $index ) ) {
						break;
					}

					if ( $next > $index ) {
						$list[] = '';
					}

					$list[] = $lines[ $next ];
					$index  = $next + 1;
				}

				$blocks[] = self::render_list( $list, $depth );
			} elseif ( $index + 1 < $count && self::is_table_header( $line, $lines[ $index + 1 ] ) ) {
				$head   = self::split_table_row( $line );
				$rows   = array();
				$index += 2;

				while ( $index < $count && false !== strpos( $lines[ $index ], '|' ) ) {
					$rows[] = self::render_table_row( array_slice( array_pad( self::split_table_row( $lines[ $index ] ), count( $head ), '' ), 0, count( $head ) ), 'td' );
					++$index;
				}

				$blocks[] = '<table><thead>' . self::render_table_row( $head, 'th' ) . '</thead>' . ( $rows ? "\n<tbody>" . implode( "\n", $rows ) . '</tbody>' : '' ) . '</table>';
			} elseif ( $index + 1 < $count && preg_match( '/^ {0,3}(=+|-+) *$/', $lines[ $index + 1 ], $underline ) ) {
				$index   += 2;
				$level    = '=' === $underline[1][0] ? 1 : 2;
				$blocks[] = '<h' . $level . '>' . self::render_inline( trim( $line ) ) . '</h' . $level . '>';
			} else {
				$paragraph = array( trim( $line ) );
				++$index;

				while ( $index < $count && '' !== trim( $lines[ $index ] ) && ! self::ends_paragraph( $lines[ $index ], $nest ) ) {
					$paragraph[] = trim( $lines[ $index ] );
					++$index;
				}

				$blocks[] = '<p>' . self::render_inline( implode( "\n", $paragraph ) ) . '</p>';
			}
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Determine whether a line ends the paragraph above it.
	 *
	 * Lists cannot interrupt a paragraph, everything else that opens a block can.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Line to inspect.
	 * @param bool   $nest Whether blockquotes are allowed at this depth.
	 * @return bool
	 */
	private static function ends_paragraph( $line, $nest ) {
		return ( $nest && preg_match( self::QUOTE, $line ) )
			|| preg_match( '/^ {0,3}(?:`{3}|~{3})/', $line )
			|| self::match_heading( $line )
			|| self::is_rule( $line )
			|| self::is_html_start( $line );
	}

	/**
	 * Match an ATX heading.
	 *
	 * A single hash needs a space after it, two or more do not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Line to inspect.
	 * @return array|null Level and text, or null when the line is not a heading.
	 * @phpstan-return array{int, string}|null
	 */
	private static function match_heading( $line ) {
		if ( ! preg_match( '/^ {0,3}(#{1,6})(?!#)/', $line, $hashes ) ) {
			return null;
		}

		$level = strlen( $hashes[1] );
		$rest  = (string) substr( $line, strlen( $hashes[0] ) );

		if ( 1 === $level && ( '' === $rest || ' ' !== $rest[0] ) ) {
			return null;
		}

		$text   = trim( $rest );
		$closed = rtrim( $text, '#' );

		if ( '' !== $closed && $closed !== $text && ' ' === substr( $closed, -1 ) ) {
			$text = rtrim( $closed );
		}

		return '' === $text ? null : array( $level, $text );
	}

	/**
	 * Determine whether a line is a horizontal rule.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Line to inspect.
	 * @return bool
	 */
	private static function is_rule( $line ) {
		if ( strspn( $line, ' ' ) > 3 ) {
			return false;
		}

		$marks = str_replace( ' ', '', $line );

		return strlen( $marks ) >= 3 && strspn( $marks, $marks[0] ) === strlen( $marks ) && false !== strpos( '-*_', $marks[0] );
	}

	/**
	 * Match the opening line of a code fence.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Line to inspect.
	 * @return array|null Fence character and length, or null.
	 * @phpstan-return array{string, int}|null
	 */
	private static function match_fence( $line ) {
		if ( ! preg_match( '/^ {0,3}(`{3,}|~{3,})/', $line, $fence ) ) {
			return null;
		}

		$info = trim( (string) substr( $line, strlen( $fence[0] ) ) );

		if ( '' !== $info && ! preg_match( '/^[\w#.+-]*$/', $info ) ) {
			return null;
		}

		return array( $fence[1][0], strlen( $fence[1] ) );
	}

	/**
	 * Find the line that closes the code fence opened at the given line.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lines Markdown lines.
	 * @param int   $index Index of the candidate opening fence.
	 * @param array $cache Per-call cache of fences that are known to stay open.
	 * @return int Index of the closing fence, or 0 when the line opens no closed fence.
	 * @phpstan-param array<int, string> $lines
	 * @phpstan-param array<string, mixed> $cache
	 */
	private static function find_fence_end( $lines, $index, &$cache ) {
		$fence = self::match_fence( $lines[ $index ] );

		if ( ! $fence || ( isset( $cache['fence'][ $fence[0] ] ) && $fence[1] >= $cache['fence'][ $fence[0] ] ) ) {
			return 0;
		}

		$count = count( $lines );

		for ( $end = $index + 1; $end < $count; ++$end ) {
			$closing = rtrim( (string) substr( $lines[ $end ], min( 3, strspn( $lines[ $end ], ' ' ) ) ) );

			if ( strlen( $closing ) >= $fence[1] && strspn( $closing, $fence[0] ) === strlen( $closing ) && strspn( $lines[ $end ], ' ' ) <= 3 ) {
				return $end;
			}
		}

		$cache['fence'][ $fence[0] ] = isset( $cache['fence'][ $fence[0] ] ) ? min( $cache['fence'][ $fence[0] ], $fence[1] ) : $fence[1];

		return 0;
	}

	/**
	 * Determine whether a line opens a block-level HTML tag.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Line to inspect.
	 * @return bool
	 */
	private static function is_html_start( $line ) {
		return 1 === preg_match( '#^ {0,3}<(/?)(' . self::BLOCK_TAGS . ')(?=[\s/>])[^<>]*>#i', $line );
	}

	/**
	 * Find the last line of the block-level HTML that starts at the given line.
	 *
	 * The block ends where its first tag is closed, at the first blank line, or,
	 * for a pre element, at the line that closes it.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lines Markdown lines.
	 * @param int   $index Index of the candidate first line.
	 * @param array $cache Per-call cache of the lines that close a pre element.
	 * @return int Index of the last line, or -1 when the line starts no HTML block.
	 * @phpstan-param array<int, string> $lines
	 * @phpstan-param array<string, mixed> $cache
	 */
	private static function find_html_end( $lines, $index, &$cache ) {
		if ( ! preg_match( '#^ {0,3}<(/?)(' . self::BLOCK_TAGS . ')(?=[\s/>])[^<>]*>#i', $lines[ $index ], $tag ) ) {
			return -1;
		}

		$name = strtolower( $tag[2] );

		if ( in_array( $name, array( 'hr', 'col', 'area' ), true ) ) {
			return $index;
		}

		if ( 'pre' === $name && '' === $tag[1] ) {
			if ( null === $cache['pre'] ) {
				$cache['pre'] = array();

				foreach ( $lines as $key => $candidate ) {
					if ( false !== stripos( $candidate, '</pre' ) ) {
						$cache['pre'][] = $key;
					}
				}
			}

			foreach ( $cache['pre'] as $key ) {
				if ( $key >= $index ) {
					return $key;
				}
			}
		}

		$count = count( $lines );
		$depth = 0;

		for ( $end = $index; $end < $count; ++$end ) {
			if ( $end > $index && '' === trim( $lines[ $end ] ) ) {
				return $end - 1;
			}

			$depth += self::tag_balance( $lines[ $end ], $name );

			if ( $depth <= 0 ) {
				return $end;
			}
		}

		return $count - 1;
	}

	/**
	 * Count the tags of one element that a line opens, minus the ones it closes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Line to inspect.
	 * @param string $name Lowercase tag name.
	 * @return int
	 */
	private static function tag_balance( $line, $name ) {
		if ( ! preg_match_all( '#<(/?)' . $name . '(?=[\s/>])[^<>]*>#i', $line, $tags, PREG_SET_ORDER ) ) {
			return 0;
		}

		$balance = 0;

		foreach ( $tags as $tag ) {
			if ( '/>' === substr( $tag[0], -2 ) ) {
				continue;
			}

			$balance += '' === $tag[1] ? 1 : -1;
		}

		return $balance;
	}

	/**
	 * Determine whether a line belongs to the list opened by the given item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line     Line to inspect.
	 * @param array  $first    Matches of the item that opened the list.
	 * @param bool   $adjacent Whether no blank line lies between this line and the previous one.
	 * @return bool
	 * @phpstan-param array<int, string> $first
	 */
	private static function continues_list( $line, $first, $adjacent ) {
		if ( strspn( $line, ' ' ) >= strlen( $first[1] ) + 2 ) {
			return true;
		}

		if ( preg_match( self::LIST_ITEM, $line, $item ) ) {
			return ! self::is_rule( $line ) && ctype_digit( $item[2][0] ) === ctype_digit( $first[2][0] );
		}

		return $adjacent && ! self::ends_paragraph( $line, true );
	}

	/**
	 * Render the lines of one list, nesting the items that are indented.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lines List lines, starting with the first item.
	 * @param int   $depth Nesting depth of the list.
	 * @return string
	 * @phpstan-param array<int, string> $lines
	 */
	private static function render_list( $lines, $depth ) {
		preg_match( self::LIST_ITEM, $lines[0], $first );

		$ordered = ctype_digit( $first[2][0] );
		$tag     = $ordered ? 'ol' : 'ul';
		$start   = $ordered && 1 !== (int) $first[2] ? ' start="' . (int) $first[2] . '"' : '';
		$limit   = strlen( $first[1] ) + 2;
		$texts   = array();
		$numbers = array();
		$rests   = array();

		foreach ( $lines as $line ) {
			if ( preg_match( self::LIST_ITEM, $line, $item ) && strlen( $item[1] ) < $limit ) {
				$texts[]   = $item[3];
				$numbers[] = (int) $item[2];
				$rests[]   = array();
			} else {
				$rests[ count( $rests ) - 1 ][] = $line;
			}
		}

		$html = array();

		foreach ( $texts as $key => $text ) {
			$rest  = $rests[ $key ];
			$skip  = 0;
			$total = count( $rest );

			while ( $skip < $total && '' !== trim( $rest[ $skip ] ) && ! preg_match( self::LIST_ITEM, $rest[ $skip ] ) ) {
				$text .= "\n" . trim( $rest[ $skip ] );
				++$skip;
			}

			$nested = '';

			if ( $skip < $total ) {
				$inner  = array_slice( $rest, $skip );
				$indent = PHP_INT_MAX;

				foreach ( $inner as $line ) {
					if ( '' !== trim( $line ) ) {
						$indent = min( $indent, strspn( $line, ' ' ) );
					}
				}

				$indent = PHP_INT_MAX === $indent ? 0 : $indent;
				$nested = self::render_blocks(
					array_map(
						static function ( $line ) use ( $indent ) {
							return (string) substr( $line, $indent );
						},
						$inner
					),
					$depth + 1
				);
			}

			$value  = $ordered && $key > 0 && $numbers[ $key ] !== $numbers[ $key - 1 ] + 1 ? ' value="' . $numbers[ $key ] . '"' : '';
			$html[] = '<li' . $value . '>' . self::render_inline( $text ) . ( '' !== $nested ? "\n" . $nested : '' ) . '</li>';
		}

		return '<' . $tag . $start . '>' . implode( "\n", $html ) . '</' . $tag . '>';
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
		if ( false === strpos( $line, '|' ) || strspn( $next, '-: |' ) !== strlen( $next ) || false === strpos( $next, '-' ) ) {
			return false;
		}

		$head  = self::split_table_row( $line );
		$rules = self::split_table_row( $next );

		if ( count( $head ) !== count( $rules ) || count( $head ) > self::MAX_TABLE_COLUMNS ) {
			return false;
		}

		foreach ( $rules as $rule ) {
			if ( ! preg_match( '/^:?-+:?$/', $rule ) ) {
				return false;
			}
		}

		return true;
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
		$row = trim( $row );

		if ( '' !== $row && '|' === $row[0] ) {
			$row = (string) substr( $row, 1 );
		}

		if ( '|' === substr( $row, -1 ) && '\\' !== substr( $row, -2, 1 ) ) {
			$row = (string) substr( $row, 0, -1 );
		}

		$cells   = array();
		$cell    = '';
		$in_code = false;
		$length  = strlen( $row );

		for ( $i = 0; $i < $length; ++$i ) {
			$char = $row[ $i ];

			if ( '\\' === $char && $i + 1 < $length && '|' === $row[ $i + 1 ] ) {
				$cell .= '\\|';
				++$i;
			} elseif ( '|' === $char && ! $in_code ) {
				$cells[] = trim( $cell );
				$cell    = '';
			} else {
				$in_code = '`' === $char ? ! $in_code : $in_code;
				$cell   .= $char;
			}
		}

		$cells[] = trim( $cell );

		return $cells;
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
	 * @param string $text  Inline Markdown.
	 * @param bool   $links Whether links are converted. Link text never contains links.
	 * @return string
	 */
	private static function render_inline( $text, $links = true ) {
		$text = str_replace( "\x03", '', str_replace( "\x02", '', $text ) );

		if ( false === strpbrk( $text, "\\`<[!*_~\n" ) && false === stripos( $text, 'http' ) ) {
			return $text;
		}

		$stash = array();
		$text  = self::protect( $text, $links, $stash );
		$text  = str_replace( "\n", "<br />\n", self::render_emphasis( $text ) );

		return self::restore( $text, $stash );
	}

	/**
	 * Replace code, links, tags and escapes with placeholders in a single pass.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text  Inline Markdown.
	 * @param bool   $links Whether links are converted.
	 * @param array  $stash Finished markup, filled in by this method.
	 * @return string
	 * @phpstan-param array<int, string> $stash
	 */
	private static function protect( $text, $links, &$stash ) {
		$length = strlen( $text );
		$out    = '';
		$i      = 0;
		$cache  = array();
		$ticks  = null;
		$anchor = false;
		$hold   = static function ( $html ) use ( &$stash ) {
			$stash[] = $html;

			return "\x02" . ( count( $stash ) - 1 ) . "\x03";
		};

		while ( $i < $length ) {
			$jump = $i + strcspn( $text, '\\`<[!hH', $i );

			if ( $jump > $i ) {
				$out .= substr( $text, $i, $jump - $i );
				$i    = $jump;
			}

			if ( $i >= $length ) {
				break;
			}

			$char = $text[ $i ];
			$next = $i + 1 < $length ? $text[ $i + 1 ] : '';

			if ( '\\' === $char && '' !== $next && false !== strpos( self::ESCAPABLE, $next ) ) {
				$out .= $hold( $next );
				$i   += 2;
			} elseif ( '`' === $char ) {
				$run = strspn( $text, '`', $i );

				if ( null === $ticks ) {
					$ticks = self::index_backtick_runs( $text );
				}

				$close = self::next_backtick_run( $ticks, $run, $i );

				if ( false === $close ) {
					$out .= substr( $text, $i, $run );
					$i   += $run;
				} else {
					$out .= $hold( '<code>' . esc_html( trim( substr( $text, $i + $run, $close - $i - $run ) ) ) . '</code>' );
					$i    = $close + $run;
				}
			} elseif ( '<' === $char ) {
				$i = self::protect_tag( $text, $i, $out, $hold, $anchor );
			} elseif ( '[' === $char || ( '!' === $char && '[' === $next ) ) {
				$link = $links && ! $anchor ? self::match_link( $text, $i, $cache ) : null;

				if ( $link ) {
					$out .= self::build_link( $link[1], self::render_inline( $link[2], false ), $link[3], $hold );
					$i    = $link[0];
				} else {
					$out .= $char;
					++$i;
				}
			} elseif ( ( 'h' === $char || 'H' === $char ) && $links && ! $anchor ) {
				$url = self::match_bare_url( $text, $i );

				if ( $url ) {
					$out .= self::build_link( $url[1], esc_html( $url[1] ), '', $hold );
					$i    = $url[0];
				} else {
					$out .= $char;
					++$i;
				}
			} else {
				$out .= $char;
				++$i;
			}
		}

		return $out;
	}

	/**
	 * Protect an angle-bracket URL or a tag that starts at the given position.
	 *
	 * Text inside an anchor is not linked again, but its Markdown is still converted.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $text   Inline Markdown.
	 * @param int      $i      Position of the opening bracket.
	 * @param string   $out    Output so far, extended by this method.
	 * @param callable $hold   Stores finished markup and returns its placeholder.
	 * @param bool     $anchor Whether the position is inside an anchor, updated by this method.
	 * @return int Position to continue from.
	 */
	private static function protect_tag( $text, $i, &$out, $hold, &$anchor ) {
		if ( preg_match( '#\G<(https?://[^\s<>]+)>#i', $text, $url, 0, $i ) ) {
			$out .= self::build_link( $url[1], esc_html( $url[1] ), '', $hold );

			return $i + strlen( $url[0] );
		}

		if ( preg_match( '#\G</?[a-z][a-z0-9-]*(?:\s[^<>]*)?/?>#i', $text, $tag, 0, $i ) ) {
			$out .= $hold( $tag[0] );

			if ( preg_match( '#^<(/?)a[\s>/]#i', $tag[0], $name ) ) {
				$anchor = '' === $name[1];
			}

			return $i + strlen( $tag[0] );
		}

		$out .= '<';

		return $i + 1;
	}

	/**
	 * Index the runs of backticks in a text by their length.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Inline Markdown.
	 * @return array
	 * @phpstan-return array<int, array{int[], int}>
	 */
	private static function index_backtick_runs( $text ) {
		$index = array();

		if ( preg_match_all( '/`+/', $text, $runs, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $runs[0] as $run ) {
				$size = strlen( $run[0] );

				if ( ! isset( $index[ $size ] ) ) {
					$index[ $size ] = array( array(), 0 );
				}

				$index[ $size ][0][] = $run[1];
			}
		}

		return $index;
	}

	/**
	 * Find the next run of backticks of the given length after a position.
	 *
	 * @since 1.0.0
	 *
	 * @param array $ticks Index built by index_backtick_runs(), advanced by this method.
	 * @param int   $size  Run length.
	 * @param int   $after Position the run must follow.
	 * @return int|false
	 * @phpstan-param array<int, array{int[], int}> $ticks
	 */
	private static function next_backtick_run( &$ticks, $size, $after ) {
		if ( ! isset( $ticks[ $size ] ) ) {
			return false;
		}

		$total = count( $ticks[ $size ][0] );

		while ( $ticks[ $size ][1] < $total && $ticks[ $size ][0][ $ticks[ $size ][1] ] <= $after ) {
			++$ticks[ $size ][1];
		}

		return $ticks[ $size ][1] < $total ? $ticks[ $size ][0][ $ticks[ $size ][1] ] : false;
	}

	/**
	 * Match an inline link or image that starts at the given position.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text  Inline Markdown.
	 * @param int    $i     Position of the opening bracket or exclamation mark.
	 * @param array  $cache Per-call cache of search results.
	 * @return array|null Position after the link, URL, label and title, or null.
	 * @phpstan-param array<string, mixed> $cache
	 * @phpstan-return array{int, string, string, string}|null
	 */
	private static function match_link( $text, $i, &$cache ) {
		$length = strlen( $text );
		$open   = '!' === $text[ $i ] ? $i + 1 : $i;
		$pos    = $open + 1;
		$nested = false;

		while ( true ) {
			$pos += strcspn( $text, '[]', $pos );

			if ( $pos >= $length ) {
				return null;
			}

			if ( '[' === $text[ $pos ] ) {
				if ( $nested ) {
					return null;
				}

				$nested = true;
			} elseif ( $nested ) {
				$nested = false;
			} else {
				break;
			}

			++$pos;
		}

		$label = substr( $text, $open + 1, $pos - $open - 1 );

		if ( '' === $label || $pos + 1 >= $length || '(' !== $text[ $pos + 1 ] ) {
			return null;
		}

		$pos += 2;
		$pos += strspn( $text, self::SPACE, $pos );
		$url  = '';

		while ( $pos < $length ) {
			$chunk = strcspn( $text, "\\()<> \t\n\r\x0b\x0c", $pos );
			$url  .= substr( $text, $pos, $chunk );
			$pos  += $chunk;

			if ( $pos >= $length ) {
				break;
			}

			if ( '\\' === $text[ $pos ] ) {
				$escaped = $pos + 1 < $length && false !== strpos( self::ESCAPABLE, $text[ $pos + 1 ] );
				$url    .= $escaped ? $text[ $pos + 1 ] : '\\';
				$pos    += $escaped ? 2 : 1;
			} elseif ( '(' === $text[ $pos ] ) {
				$end = $pos + 1 + strcspn( $text, "()<> \t\n\r\x0b\x0c", $pos + 1 );

				if ( $end >= $length || ')' !== $text[ $end ] ) {
					break;
				}

				$url .= substr( $text, $pos, $end - $pos + 1 );
				$pos  = $end + 1;
			} else {
				break;
			}
		}

		$gap   = strspn( $text, self::SPACE, $pos );
		$title = '';

		if ( $gap > 0 && $pos + $gap < $length && '"' === $text[ $pos + $gap ] ) {
			$from = $pos + $gap + 1;

			if ( isset( $cache['title'] ) && ( false === $cache['title'] || $cache['title'] >= $from ) ) {
				$close = $cache['title'];
			} elseif ( preg_match( '/"\s*\)/', $text, $found, PREG_OFFSET_CAPTURE, $from ) ) {
				$close = $found[0][1];
			} else {
				$close = false;
			}

			$cache['title'] = $close;

			if ( false === $close ) {
				return null;
			}

			$title = substr( $text, $from, $close - $from );
			$pos   = $close + 1;
			$gap   = strspn( $text, self::SPACE, $pos );
		}

		$pos += $gap;

		return $pos < $length && ')' === $text[ $pos ] ? array( $pos + 1, $url, $label, $title ) : null;
	}

	/**
	 * Match a bare URL that starts at the given position.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Inline Markdown.
	 * @param int    $i    Position of the candidate first letter.
	 * @return array|null Position after the URL and the URL, or null.
	 * @phpstan-return array{int, string}|null
	 */
	private static function match_bare_url( $text, $i ) {
		if ( ! preg_match( '#\Ghttps?://#i', $text, $scheme, 0, $i ) || ( $i > 0 && preg_match( '#[\w/]#', $text[ $i - 1 ] ) ) ) {
			return null;
		}

		$length  = strlen( $text );
		$pos     = $i + strlen( $scheme[0] );
		$url     = $scheme[0];
		$escaped = false;

		while ( $pos < $length ) {
			$char = $text[ $pos ];

			if ( '\\' === $char && $pos + 1 < $length && false !== strpos( self::ESCAPABLE, $text[ $pos + 1 ] ) ) {
				$url    .= $text[ $pos + 1 ];
				$pos    += 2;
				$escaped = true;
				continue;
			}

			if ( false !== strpos( self::SPACE . '<>")', $char ) ) {
				break;
			}

			if ( '(' === $char ) {
				$end = $pos + 1 + strcspn( $text, self::SPACE . '<>"()', $pos + 1 );

				if ( $end >= $length || ')' !== $text[ $end ] ) {
					break;
				}

				$url    .= substr( $text, $pos, $end - $pos + 1 );
				$pos     = $end + 1;
				$escaped = false;
				continue;
			}

			if ( ord( $char ) >= 0xE0 && self::is_wide_character( substr( $text, $pos, 3 ) ) ) {
				break;
			}

			$url    .= $char;
			$escaped = false;
			++$pos;
		}

		$floor = strlen( $scheme[0] );
		$size  = strlen( $url );

		while ( ! $escaped && $size > $floor && false !== strpos( ".,;:!?'*_~", $url[ $size - 1 ] ) ) {
			--$size;
			--$pos;
		}

		return $size > $floor ? array( $pos, substr( $url, 0, $size ) ) : null;
	}

	/**
	 * Determine whether a character is written without spaces around it, like CJK and Thai.
	 *
	 * @since 1.0.0
	 *
	 * @param string $character Three bytes that start the character.
	 * @return bool
	 */
	private static function is_wide_character( $character ) {
		return 1 === preg_match( '/^[\x{0E00}-\x{0E7F}\x{2E80}-\x{9FFF}\x{AC00}-\x{D7AF}\x{F900}-\x{FAFF}\x{FE30}-\x{FE4F}\x{FF00}-\x{FFEF}]/u', $character );
	}

	/**
	 * Build a link, or return the label alone when the URL is not allowed.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $url   Link target.
	 * @param string   $label Finished HTML of the link text.
	 * @param string   $title Link title.
	 * @param callable $hold  Stores finished markup and returns its placeholder.
	 * @return string
	 */
	private static function build_link( $url, $label, $title, $hold ) {
		$url = esc_url( $url );

		if ( '' === $url ) {
			return $hold( $label );
		}

		return $hold( '<a href="' . $url . '"' . ( '' !== $title ? ' title="' . esc_attr( $title ) . '"' : '' ) . '>' . $label . '</a>' );
	}

	/**
	 * Put the finished markup back in place of its placeholders.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text  Text with placeholders.
	 * @param array  $stash Finished markup.
	 * @return string
	 * @phpstan-param array<int, string> $stash
	 */
	private static function restore( $text, $stash ) {
		$parts = explode( "\x02", $text );
		$out   = (string) array_shift( $parts );

		foreach ( $parts as $part ) {
			$end = strpos( $part, "\x03" );

			if ( false === $end ) {
				$out .= $part;
				continue;
			}

			$key  = (int) substr( $part, 0, $end );
			$out .= ( isset( $stash[ $key ] ) ? $stash[ $key ] : '' ) . substr( $part, $end + 1 );
		}

		return $out;
	}

	/**
	 * Render bold, italic and strikethrough markers as HTML.
	 *
	 * Marker runs are paired with a stack, so the cost stays linear, and their
	 * number is bounded, because each run is tracked in memory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Inline text with placeholders.
	 * @return string
	 */
	private static function render_emphasis( $text ) {
		if ( preg_match_all( '/\*+|_+|~{2,}/', $text ) > self::MAX_MARKERS || ! preg_match_all( '/\*+|_+|~{2,}/', $text, $runs, PREG_OFFSET_CAPTURE ) ) {
			return $text;
		}

		$length = strlen( $text );
		$parts  = array();
		$delims = array();
		$last   = 0;

		foreach ( $runs[0] as $run ) {
			$size    = strlen( $run[0] );
			$prev    = $run[1] > 0 ? $text[ $run[1] - 1 ] : '';
			$next    = $run[1] + $size < $length ? $text[ $run[1] + $size ] : '';
			$open    = '' !== $next && ! ctype_space( $next );
			$close   = '' !== $prev && ! ctype_space( $prev );
			$parts[] = (string) substr( $text, $last, $run[1] - $last );

			if ( '_' === $run[0][0] ) {
				$open  = $open && ( '' === $prev || ! self::is_word_byte( $prev ) );
				$close = $close && ( '' === $next || ! self::is_word_byte( $next ) );
			}

			$parts[]  = count( $delims );
			$delims[] = array(
				'char'  => $run[0][0],
				'left'  => $size,
				'open'  => $open,
				'close' => $close,
				'pre'   => '',
				'post'  => '',
			);
			$last     = $run[1] + $size;
		}

		$parts[] = (string) substr( $text, $last );
		$stacks  = array(
			'*' => array(),
			'_' => array(),
			'~' => array(),
		);

		foreach ( array_keys( $delims ) as $key ) {
			$char = $delims[ $key ]['char'];

			while ( $delims[ $key ]['close'] && $delims[ $key ]['left'] > 0 && $stacks[ $char ] ) {
				$top = $stacks[ $char ][ count( $stacks[ $char ] ) - 1 ];
				$use = self::emphasis_width( $char, $delims[ $key ]['left'], $delims[ $top ]['left'] );

				if ( 0 === $use ) {
					break;
				}

				foreach ( array_keys( $stacks ) as $other ) {
					$size = count( $stacks[ $other ] );

					while ( $size > 0 && $stacks[ $other ][ $size - 1 ] > $top ) {
						array_pop( $stacks[ $other ] );
						--$size;
					}
				}

				$tag = '~' === $char ? 'del' : ( 2 === $use ? 'strong' : 'em' );

				$delims[ $top ]['post']  = '<' . $tag . '>' . $delims[ $top ]['post'];
				$delims[ $key ]['pre']  .= '</' . $tag . '>';
				$delims[ $top ]['left'] -= $use;
				$delims[ $key ]['left'] -= $use;

				if ( $delims[ $top ]['left'] < ( '~' === $char ? 2 : 1 ) ) {
					array_pop( $stacks[ $char ] );
				}
			}

			if ( $delims[ $key ]['open'] && $delims[ $key ]['left'] >= ( '~' === $char ? 2 : 1 ) ) {
				$stacks[ $char ][] = $key;
			}
		}

		$out = '';

		foreach ( $parts as $part ) {
			if ( is_int( $part ) ) {
				$out .= $delims[ $part ]['pre'] . str_repeat( $delims[ $part ]['char'], $delims[ $part ]['left'] ) . $delims[ $part ]['post'];
			} else {
				$out .= $part;
			}
		}

		return $out;
	}

	/**
	 * Decide how many marker characters a match uses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $char   Marker character.
	 * @param int    $closer Characters left in the closing run.
	 * @param int    $opener Characters left in the opening run.
	 * @return int
	 */
	private static function emphasis_width( $char, $closer, $opener ) {
		if ( '~' === $char ) {
			return $closer >= 2 && $opener >= 2 ? 2 : 0;
		}

		if ( $closer >= 3 && $opener >= 3 ) {
			return 1;
		}

		return $closer >= 2 && $opener >= 2 ? 2 : 1;
	}

	/**
	 * Determine whether a byte belongs to a word, counting every non-ASCII byte as a letter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $byte Single byte.
	 * @return bool
	 */
	private static function is_word_byte( $byte ) {
		return ctype_alnum( $byte ) || '_' === $byte || ord( $byte ) >= 0x80;
	}
}
