<?php
/**
 * Сборка zip с правильными разделителями путей (ZIP-спецификация требует «/»).
 * Запуск: php tools/zip.php <папка-источник> <итоговый-архив>
 */
if ( $argc < 3 ) {
	fwrite( STDERR, "Использование: php zip.php <папка> <архив.zip>\n" );
	exit( 1 );
}

$source = realpath( $argv[1] );
$target = $argv[2];

if ( ! $source || ! is_dir( $source ) ) {
	fwrite( STDERR, "Папка не найдена: {$argv[1]}\n" );
	exit( 1 );
}

if ( file_exists( $target ) ) {
	unlink( $target );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $target, ZipArchive::CREATE ) ) {
	fwrite( STDERR, "Не удалось создать архив: $target\n" );
	exit( 1 );
}

$root  = basename( $source );
$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;

foreach ( $files as $file ) {
	$path  = $file->getRealPath();
	$local = $root . '/' . str_replace( '\\', '/', substr( $path, strlen( $source ) + 1 ) );

	if ( $file->isDir() ) {
		$zip->addEmptyDir( $local );
	} else {
		$zip->addFile( $path, $local );
		++$count;
	}
}

$zip->close();

printf( "%s — %d файлов, %d КБ\n", basename( $target ), $count, round( filesize( $target ) / 1024 ) );
