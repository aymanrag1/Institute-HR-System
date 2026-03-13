<?php
/**
 * Translation Updater + MO Compiler
 *
 * يجمع كل الـ strings من ملفات PHP ويحدّث ملفات .po ويعيد بناء .mo
 * تشغيل: php languages/update-translations.php
 *
 * @package RSYI_HR
 */

define( 'PLUGIN_ROOT',  dirname( __DIR__ ) );
define( 'TEXT_DOMAIN',  'rsyi-hr' );
define( 'LANG_DIR',     __DIR__ );

/* ── 1. جمع الـ strings من الكود ──────────────────────────────────────── */

function scan_strings(): array {
    $functions = [ '__', '_e', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e', 'esc_js' ];
    $pattern   = '/(?:' . implode( '|', array_map( 'preg_quote', $functions ) ) . ')\s*\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'[\s,]+\'' . preg_quote( TEXT_DOMAIN ) . '\'/u';

    $strings = [];
    $files   = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( PLUGIN_ROOT, RecursiveDirectoryIterator::SKIP_DOTS )
    );

    foreach ( $files as $file ) {
        if ( $file->getExtension() !== 'php' ) { continue; }
        // تجنب الـ languages directory نفسه
        if ( strpos( $file->getPathname(), LANG_DIR ) === 0 ) { continue; }

        $content = file_get_contents( $file->getPathname() );
        if ( preg_match_all( $pattern, $content, $m ) ) {
            foreach ( $m[1] as $s ) {
                $s = stripcslashes( $s );
                if ( $s !== '' ) {
                    $strings[ $s ] = $s;
                }
            }
        }
    }

    ksort( $strings );
    return $strings;
}

/* ── 2. قراءة .po ──────────────────────────────────────────────────────── */

function parse_po( string $file ): array {
    $translations = [];
    if ( ! file_exists( $file ) ) { return []; }

    $msgid = $msgstr = '';
    $in_id = $in_str = false;

    foreach ( file( $file ) as $line ) {
        $line = rtrim( $line );
        if ( strncmp( $line, 'msgid ', 6 ) === 0 ) {
            if ( $msgid !== '' && $msgid !== '""' ) {
                $translations[ stripcslashes( trim( $msgid, '"' ) ) ] = stripcslashes( trim( $msgstr, '"' ) );
            }
            $msgid = substr( $line, 6 ); $msgstr = '';
            $in_id = true;  $in_str = false;
        } elseif ( strncmp( $line, 'msgstr ', 7 ) === 0 ) {
            $msgstr = substr( $line, 7 );
            $in_id  = false; $in_str = true;
        } elseif ( $line !== '' && $line[0] === '"' ) {
            if ( $in_id  ) { $msgid  .= "\n" . $line; }
            if ( $in_str ) { $msgstr .= "\n" . $line; }
        } else {
            $in_id = $in_str = false;
        }
    }
    if ( $msgid !== '' && $msgid !== '""' ) {
        $translations[ stripcslashes( trim( $msgid, '"' ) ) ] = stripcslashes( trim( $msgstr, '"' ) );
    }

    return $translations;
}

/* ── 3. كتابة .po محدَّث ──────────────────────────────────────────────── */

function write_po( string $file, string $header, array $strings, array $existing ): array {
    $added   = [];
    $removed = [];
    $lines   = $header . "\n";

    foreach ( $strings as $msgid ) {
        $trans = $existing[ $msgid ] ?? '';
        if ( ! isset( $existing[ $msgid ] ) ) {
            $added[] = $msgid;
        }
        $lines .= 'msgid "'  . addcslashes( $msgid, '"\\' ) . '"' . "\n";
        $lines .= 'msgstr "' . addcslashes( $trans,  '"\\' ) . '"' . "\n\n";
    }

    // رصد المحذوفة (كانت موجودة وبقت obsolete)
    foreach ( array_keys( $existing ) as $old ) {
        if ( ! isset( $strings[ $old ] ) ) {
            $removed[] = $old;
        }
    }

    file_put_contents( $file, $lines );
    return [ 'added' => $added, 'removed' => $removed ];
}

/* ── 4. بناء .mo ───────────────────────────────────────────────────────── */

function compile_mo( string $po_file, string $mo_file ): int {
    $translations = parse_po( $po_file );
    $translations = array_filter( $translations, fn( $v ) => $v !== '' );
    ksort( $translations );

    $num         = count( $translations );
    $originals   = '';
    $trans_str   = '';
    $offsets     = [];
    $offsets_t   = [];

    foreach ( $translations as $orig => $trans ) {
        $offsets[]   = [ strlen( $orig ),  strlen( $originals ) ];
        $originals  .= $orig  . "\x00";
        $offsets_t[] = [ strlen( $trans ), strlen( $trans_str ) ];
        $trans_str  .= $trans . "\x00";
    }

    $orig_table   = 28;
    $trans_table  = $orig_table  + $num * 8;
    $orig_start   = $trans_table + $num * 8;
    $trans_start  = $orig_start  + strlen( $originals );

    $mo  = pack( 'V', 0x950412de );
    $mo .= pack( 'V', 0 );
    $mo .= pack( 'V', $num );
    $mo .= pack( 'V', $orig_table );
    $mo .= pack( 'V', $trans_table );
    $mo .= pack( 'V', 0 );
    $mo .= pack( 'V', 28 + $num * 16 );

    foreach ( $offsets   as $o ) { $mo .= pack( 'VV', $o[0], $orig_start  + $o[1] ); }
    foreach ( $offsets_t as $o ) { $mo .= pack( 'VV', $o[0], $trans_start + $o[1] ); }

    $mo .= $originals . $trans_str;
    file_put_contents( $mo_file, $mo );
    return $num;
}

/* ── 5. POT headers ────────────────────────────────────────────────────── */

function po_header( string $lang, string $lang_label ): string {
    $now = date( 'Y-m-d\TH:i:sP' );
    return <<<HDR
# {$lang_label} translation for RSYI HR System
# Developer: AYMAN RAGAB | Mobile: +201159230034
msgid ""
msgstr ""
"Project-Id-Version: RSYI HR System 2.1.0\n"
"POT-Creation-Date: {$now}\n"
"PO-Revision-Date: {$now}\n"
"Last-Translator: AYMAN RAGAB\n"
"Language-Team: {$lang_label}\n"
"Language: {$lang}\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Domain: rsyi-hr\n"
HDR;
}

/* ── الـ Main ───────────────────────────────────────────────────────────── */

echo "\n=== RSYI HR — Translation Updater ===\n\n";

// جمع الـ strings
echo "جارٍ فحص الكود...\n";
$strings = scan_strings();
echo "✓ تم العثور على " . count( $strings ) . " string قابل للترجمة\n\n";

// ملفات اللغة
$files = [
    'ar'    => [ 'label' => 'Arabic',       'po' => LANG_DIR . '/rsyi-hr-ar.po',    'mo' => LANG_DIR . '/rsyi-hr-ar.mo' ],
    'en_US' => [ 'label' => 'English (US)', 'po' => LANG_DIR . '/rsyi-hr-en_US.po', 'mo' => LANG_DIR . '/rsyi-hr-en_US.mo' ],
];

foreach ( $files as $lang => $cfg ) {
    echo "── {$cfg['label']} ({$lang}) ──────────────────────\n";

    $existing = parse_po( $cfg['po'] );
    $result   = write_po( $cfg['po'], po_header( $lang, $cfg['label'] ), $strings, $existing );

    if ( $result['added'] ) {
        echo "  + مضاف  : " . count( $result['added'] ) . " string جديد\n";
        foreach ( $result['added'] as $s ) {
            echo "      · " . mb_substr( $s, 0, 60 ) . "\n";
        }
    } else {
        echo "  ✓ لا توجد strings جديدة\n";
    }

    if ( $result['removed'] ) {
        echo "  - محذوف : " . count( $result['removed'] ) . " string قديم\n";
    }

    $count = compile_mo( $cfg['po'], $cfg['mo'] );
    echo "  ✓ تم بناء .mo  ({$count} ترجمة فعّالة)\n\n";
}

// تحديث .pot أيضاً
$pot  = LANG_DIR . '/rsyi-hr.pot';
$body = '';
foreach ( array_keys( $strings ) as $s ) {
    $body .= 'msgid "'  . addcslashes( $s, '"\\' ) . '"' . "\n";
    $body .= 'msgstr ""' . "\n\n";
}
file_put_contents( $pot, po_header( '', 'Template' ) . "\n\n" . $body );
echo "✓ تم تحديث rsyi-hr.pot\n";
echo "\n=== اكتمل بنجاح ===\n\n";
