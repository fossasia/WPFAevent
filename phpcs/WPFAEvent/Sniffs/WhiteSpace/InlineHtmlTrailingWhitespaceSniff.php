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

/**
 * Reports whitespace at the end of an inline HTML line.
 */
class InlineHtmlTrailingWhitespaceSniff implements Sniff {

	/**
	 * Returns the token types this sniff listens for.
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array( T_INLINE_HTML );
	}

	/**
	 * Processes an inline HTML token.
	 *
	 * PHP_CodeSniffer emits one inline HTML token per line, and splits a line
	 * into several tokens when PHP is embedded mid-line. Only the token that
	 * carries the newline actually ends the line.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  Position of the current token in the token stack.
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens  = $phpcsFile->getTokens();
		$content = $tokens[ $stackPtr ]['content'];
		$eol     = $phpcsFile->eolChar;

		// Without a newline the line continues into a PHP tag, where the
		// whitespace before the tag is meaningful.
		if ( substr( $content, - strlen( $eol ) ) !== $eol ) {
			return;
		}

		$line    = substr( $content, 0, - strlen( $eol ) );
		$trimmed = rtrim( $line, " \t" );

		if ( $trimmed === $line ) {
			return;
		}

		$fix = $phpcsFile->addFixableError( 'Whitespace found at end of line', $stackPtr, 'Found' );

		if ( true === $fix ) {
			$phpcsFile->fixer->replaceToken( $stackPtr, $trimmed . $eol );
		}
	}
}
