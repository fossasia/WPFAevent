<?php
/**
 * Flags trailing whitespace inside inline HTML - the one place
 * Squiz.WhiteSpace.SuperfluousWhitespace can't reach, since it never registers T_INLINE_HTML.
 *
 * @package Wpfaevent
 */

namespace WPFAEvent\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class InlineHtmlTrailingWhitespaceSniff implements Sniff {

	public function register() {
		return array( T_INLINE_HTML );
	}

	// PHPCS splits multi-line markup into one T_INLINE_HTML token per line, so $content
	// below is never more than one line.
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens        = $phpcsFile->getTokens();
		$content       = $tokens[ $stackPtr ]['content'];
		$eol           = $phpcsFile->eolChar;
		$ends_with_eol = substr( $content, - strlen( $eol ) ) === $eol;

		// Skip: continues into a PHP tag (meaningful whitespace) - unless this is genuinely EOF.
		if ( ! $ends_with_eol && isset( $tokens[ $stackPtr + 1 ] ) ) {
			return;
		}

		$line    = $ends_with_eol ? substr( $content, 0, - strlen( $eol ) ) : $content;
		$trimmed = rtrim( $line, " \t" );

		if ( $trimmed === $line ) {
			return;
		}

		$fix = $phpcsFile->addFixableError( 'Whitespace found at end of line', $stackPtr, 'Found' );

		if ( true === $fix ) {
			$phpcsFile->fixer->replaceToken( $stackPtr, $trimmed . ( $ends_with_eol ? $eol : '' ) );
		}
	}
}
