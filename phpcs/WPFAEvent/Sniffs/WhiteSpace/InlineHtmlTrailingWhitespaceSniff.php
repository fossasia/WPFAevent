<?php
/**
 * Flags trailing whitespace inside inline HTML.
 *
 * Squiz.WhiteSpace.SuperfluousWhitespace only registers PHP tokens, so its
 * end-of-line check cannot reach the markup regions of template files. This
 * sniff covers that gap and is deliberately limited to T_INLINE_HTML so that
 * whitespace in PHP code is still reported by the Squiz sniff alone.
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

	/**
	 * PHP_CodeSniffer emits one inline HTML token per line, splitting a line into
	 * several tokens when PHP is embedded mid-line; only the token carrying the
	 * newline actually ends the line.
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens        = $phpcsFile->getTokens();
		$content       = $tokens[ $stackPtr ]['content'];
		$eol           = $phpcsFile->eolChar;
		$ends_with_eol = substr( $content, - strlen( $eol ) ) === $eol;

		// Without a newline the line usually continues into a PHP tag, where the
		// whitespace before the tag is meaningful - unless this token is the last
		// in the file, i.e. the file has no trailing newline at all.
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
