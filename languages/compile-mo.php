<?php
/**
 * PO to MO Compiler
 * Run: php compile-mo.php
 */

function compile_po_to_mo( string $po_file, string $mo_file ): void {
    if ( ! file_exists( $po_file ) ) {
        echo "File not found: {$po_file}\n";
        return;
    }

    $strings = [];
    $msgid   = '';
    $msgstr  = '';
    $in_msgid  = false;
    $in_msgstr = false;

    foreach ( file( $po_file ) as $line ) {
        $line = rtrim( $line );

        if ( str_starts_with( $line, 'msgid ' ) ) {
            if ( $msgid !== '' && $msgstr !== '' && $msgid !== '""' ) {
                $strings[ stripcslashes( trim( $msgid, '"' ) ) ] = stripcslashes( trim( $msgstr, '"' ) );
            }
            $msgid     = substr( $line, 6 );
            $msgstr    = '';
            $in_msgid  = true;
            $in_msgstr = false;
        } elseif ( str_starts_with( $line, 'msgstr ' ) ) {
            $msgstr    = substr( $line, 7 );
            $in_msgid  = false;
            $in_msgstr = true;
        } elseif ( str_starts_with( $line, '"' ) ) {
            if ( $in_msgid )  { $msgid  .= "\n" . $line; }
            if ( $in_msgstr ) { $msgstr .= "\n" . $line; }
        } else {
            $in_msgid = $in_msgstr = false;
        }
    }

    // Last entry
    if ( $msgid !== '' && $msgstr !== '' && $msgid !== '""' ) {
        $strings[ stripcslashes( trim( $msgid, '"' ) ) ] = stripcslashes( trim( $msgstr, '"' ) );
    }

    // Remove empty translations
    $strings = array_filter( $strings, fn( $v ) => $v !== '' );

    // Build MO binary
    $num     = count( $strings );
    $offsets = [];

    // Sorted by msgid (required by spec)
    ksort( $strings );

    $originals    = '';
    $translations = '';

    foreach ( $strings as $orig => $trans ) {
        $offsets[] = [ strlen( $orig ), strlen( $originals ) ];
        $originals .= $orig . "\x00";

        $offsets_t[] = [ strlen( $trans ), strlen( $translations ) ];
        $translations .= $trans . "\x00";
    }

    // Header offsets
    $orig_table_offset  = 28;                                     // after header (7 * 4 bytes)
    $trans_table_offset = $orig_table_offset + $num * 8;          // after orig table
    $orig_strings_start = $trans_table_offset + $num * 8;         // after trans table
    $trans_strings_start = $orig_strings_start + strlen( $originals );

    $mo  = pack( 'V', 0x950412de );  // magic
    $mo .= pack( 'V', 0 );           // revision
    $mo .= pack( 'V', $num );        // num strings
    $mo .= pack( 'V', $orig_table_offset );
    $mo .= pack( 'V', $trans_table_offset );
    $mo .= pack( 'V', 0 );           // hash table size
    $mo .= pack( 'V', 28 + $num * 16 ); // hash table offset

    foreach ( $offsets as $o ) {
        $mo .= pack( 'V', $o[0] );
        $mo .= pack( 'V', $orig_strings_start + $o[1] );
    }

    foreach ( $offsets_t as $o ) {
        $mo .= pack( 'V', $o[0] );
        $mo .= pack( 'V', $trans_strings_start + $o[1] );
    }

    $mo .= $originals . $translations;

    file_put_contents( $mo_file, $mo );
    echo "Compiled: {$po_file} → {$mo_file} ({$num} strings)\n";
}

$dir = __DIR__;
compile_po_to_mo( "{$dir}/rsyi-hr-en_US.po", "{$dir}/rsyi-hr-en_US.mo" );
compile_po_to_mo( "{$dir}/rsyi-hr-ar.po",    "{$dir}/rsyi-hr-ar.mo" );
echo "Done.\n";
