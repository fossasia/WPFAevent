<?php
// phpcs:ignoreFile -- Standalone CLI test that shells out to PHP_CodeSniffer.
/**
 * Guards the custom inline-HTML whitespace sniff (Squiz's own sniff can't see T_INLINE_HTML).
 *
 * Run with: php tests/phpcs-whitespace-test.php
 *
 * @package Wpfaevent
 */

$wpfaevent_root   = dirname( __DIR__ );
$wpfaevent_sniff  = 'WPFAEvent.WhiteSpace.InlineHtmlTrailingWhitespace';
$wpfaevent_phpcs  = $wpfaevent_root . '/vendor/bin/phpcs';
$wpfaevent_phpcbf = $wpfaevent_root . '/vendor/bin/phpcbf';

function wpfaevent_phpcs_test_fail( $message ) {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 1 );
}

function wpfaevent_phpcs_test_assert_same( $expected, $actual, $message ) {
	if ( $expected === $actual ) {
		return;
	}

	wpfaevent_phpcs_test_fail(
		$message . PHP_EOL
		. 'Expected: ' . var_export( $expected, true ) . PHP_EOL
		. 'Actual: ' . var_export( $actual, true )
	);
}

if ( ! file_exists( $wpfaevent_phpcs ) || ! file_exists( $wpfaevent_phpcbf ) ) {
	wpfaevent_phpcs_test_fail( 'PHP_CodeSniffer is not installed. Run "composer install" first.' );
}

/*
 * The fixture lives inside the repository because the ruleset skips system temp
 * directories, and it is generated at run time because a committed file with
 * intentional trailing whitespace would fail the whitespace-check workflow.
 */
$wpfaevent_fixture_dir = $wpfaevent_root . '/tests/.phpcs-whitespace-fixture-' . getmypid();

if ( ! is_dir( $wpfaevent_fixture_dir ) && ! mkdir( $wpfaevent_fixture_dir ) ) {
	wpfaevent_phpcs_test_fail( 'Unable to create the fixture directory: ' . $wpfaevent_fixture_dir );
}

register_shutdown_function(
	function () use ( $wpfaevent_fixture_dir ) {
		foreach ( glob( $wpfaevent_fixture_dir . '/*' ) as $wpfaevent_leftover ) {
			unlink( $wpfaevent_leftover );
		}

		rmdir( $wpfaevent_fixture_dir );
	}
);

$wpfaevent_fixture_file = $wpfaevent_fixture_dir . '/fixture.php';

// Concatenated/escaped so no editor or commit hook silently strips the whitespace under test.
$wpfaevent_fixture_lines = array(
	'<?php',
	'$heading = "Hi";',
	'?>',
	'<div class="a">' . '  ',   // line 4: trailing spaces after markup.
	"\t\t",                     // line 5: whitespace-only line inside markup.
	'</div>',
	'<?php',
	'echo $heading;',
);

file_put_contents( $wpfaevent_fixture_file, implode( "\n", $wpfaevent_fixture_lines ) . "\n" );

/**
 * Runs the sniff alone against a fixture and returns the lines it flagged.
 *
 * Restricting the run with --sniffs also proves the sniff is registered in
 * phpcs.xml: PHP_CodeSniffer aborts when asked for a code it cannot resolve.
 */
function wpfaevent_phpcs_flagged_lines( $phpcs, $standard, $sniff, $file ) {
	$report = array();
	$status = 0;
	exec(
		escapeshellarg( $phpcs )
			. ' --standard=' . escapeshellarg( $standard )
			. ' --sniffs=' . escapeshellarg( $sniff )
			. ' --report=csv --no-colors '
			. escapeshellarg( $file ) . ' 2>&1',
		$report,
		$status
	);

	if ( 0 === $status ) {
		wpfaevent_phpcs_test_fail(
			'PHPCS reported no violations.' . PHP_EOL . implode( PHP_EOL, $report )
		);
	}

	$lines = array();

	foreach ( $report as $row ) {
		$columns = str_getcsv( $row );

		if ( isset( $columns[1], $columns[5] ) && is_numeric( $columns[1] ) && $sniff . '.Found' === $columns[5] ) {
			$lines[] = (int) $columns[1];
		}
	}

	sort( $lines );

	return $lines;
}

$wpfaevent_standard = $wpfaevent_root . '/phpcs.xml';

wpfaevent_phpcs_test_assert_same(
	array( 4, 5 ),
	wpfaevent_phpcs_flagged_lines( $wpfaevent_phpcs, $wpfaevent_standard, $wpfaevent_sniff, $wpfaevent_fixture_file ),
	'PHPCS should flag the trailing spaces after markup and the whitespace-only line, and nothing else.'
);

// The violation must stay auto-fixable so that "composer phpcbf" can clean it up.
exec(
	escapeshellarg( $wpfaevent_phpcbf )
		. ' --standard=' . escapeshellarg( $wpfaevent_standard )
		. ' --sniffs=' . escapeshellarg( $wpfaevent_sniff )
		. ' --no-colors '
		. escapeshellarg( $wpfaevent_fixture_file ) . ' 2>&1'
);

$wpfaevent_fixed = file_get_contents( $wpfaevent_fixture_file );

wpfaevent_phpcs_test_assert_same(
	implode(
		"\n",
		array( '<?php', '$heading = "Hi";', '?>', '<div class="a">', '', '</div>', '<?php', 'echo $heading;' )
	) . "\n",
	$wpfaevent_fixed,
	'PHPCBF should strip the trailing whitespace and leave the rest of the file untouched.'
);

/*
 * A file with no trailing newline at all has no "next line" for the last inline-HTML
 * token to end at, which is a separate code path from every line above.
 */
$wpfaevent_eof_fixture_file = $wpfaevent_fixture_dir . '/fixture-no-eol.php';
file_put_contents( $wpfaevent_eof_fixture_file, "<?php\necho 1;\n?>\nBye   " );

wpfaevent_phpcs_test_assert_same(
	array( 4 ),
	wpfaevent_phpcs_flagged_lines( $wpfaevent_phpcs, $wpfaevent_standard, $wpfaevent_sniff, $wpfaevent_eof_fixture_file ),
	'PHPCS should flag trailing whitespace on the last line even without a final newline.'
);

exec(
	escapeshellarg( $wpfaevent_phpcbf )
		. ' --standard=' . escapeshellarg( $wpfaevent_standard )
		. ' --sniffs=' . escapeshellarg( $wpfaevent_sniff )
		. ' --no-colors '
		. escapeshellarg( $wpfaevent_eof_fixture_file ) . ' 2>&1'
);

wpfaevent_phpcs_test_assert_same(
	"<?php\necho 1;\n?>\nBye",
	file_get_contents( $wpfaevent_eof_fixture_file ),
	'PHPCBF should strip the trailing whitespace without adding a final newline that was never there.'
);

fwrite( STDOUT, 'PHPCS whitespace tests passed.' . PHP_EOL );
