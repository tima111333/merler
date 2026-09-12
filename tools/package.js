/**
 * Этап 9. Сборка комплекта на сдачу: два zip и папка dist.
 * Запуск: node tools/package.js
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const ROOT = path.join( __dirname, '..' );
const DIST = path.join( ROOT, 'dist' );

fs.rmSync( DIST, { recursive: true, force: true } );
fs.mkdirSync( DIST, { recursive: true } );

/**
 * Упаковать папку в zip средствами PowerShell (без внешних зависимостей).
 *
 * @param {string} source Папка-источник.
 * @param {string} target Итоговый архив.
 */
function zip( source, target ) {
	// PowerShell 5.1 пишет в архив пути с обратными слэшами — такой zip ломается
	// при установке на Linux-хостинге, поэтому собираем через PHP ZipArchive.
	const php = process.env.MERLER_PHP || 'C:/Users/Tima/AppData/Local/Temp/php82/php.exe';
	execFileSync( php, [ path.join( __dirname, 'zip.php' ), source, target ], { stdio: 'inherit' } );
}

zip( path.join( ROOT, 'plugin', 'merler-menu' ), path.join( DIST, 'merler-menu.zip' ) );
zip( path.join( ROOT, 'theme', 'merler-theme' ), path.join( DIST, 'merler-theme.zip' ) );

// Сопутствующие файлы.
fs.copyFileSync( path.join( ROOT, 'data', 'menu-data.json' ), path.join( DIST, 'menu-data.json' ) );
fs.copyFileSync( path.join( ROOT, 'README.md' ), path.join( DIST, 'README.md' ) );

fs.mkdirSync( path.join( DIST, 'docs' ), { recursive: true } );
[ '00-analiz.md', '02-arhitektura.md', '09-sdacha.md', 'instrukciya-dlya-klienta.md', 'test-log.txt', 'test-log-2.txt' ].forEach( ( file ) => {
	const from = path.join( ROOT, 'docs', file );
	if ( fs.existsSync( from ) ) {
		fs.copyFileSync( from, path.join( DIST, 'docs', file ) );
	}
} );

[ 'test-report.json', 'test-report-2.json' ].forEach( ( file ) => {
	const from = path.join( ROOT, 'docs', file );
	if ( fs.existsSync( from ) ) {
		fs.copyFileSync( from, path.join( DIST, 'docs', file ) );
	}
} );

// Прототип — отдельной папкой, чтобы открывался двойным щелчком.
const protoDir = path.join( DIST, 'prototype' );
fs.mkdirSync( path.join( protoDir, 'assets' ), { recursive: true } );
fs.cpSync( path.join( ROOT, 'prototype' ), protoDir, { recursive: true } );
fs.cpSync( path.join( ROOT, 'assets' ), path.join( protoDir, 'assets' ), { recursive: true } );

console.log( 'Собрано в dist:' );
fs.readdirSync( DIST ).forEach( ( f ) => {
	const stat = fs.statSync( path.join( DIST, f ) );
	console.log( '  ' + f + ( stat.isFile() ? '  ' + Math.round( stat.size / 1024 ) + ' КБ' : '  (папка)' ) );
} );
