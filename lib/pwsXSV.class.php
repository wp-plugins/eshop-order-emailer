<?php

if ( ! class_exists( 'pwsXSV' ) ) {

class pwsXSV {

	private $delimiter = ',';
	private $enclosure = '"';

	public function load( $path ) {
		$rows = array( );
		ini_set('auto_detect_line_endings', true);
		if ( ( $handle = fopen( $path, 'r' ) ) !== FALSE ) {
			while ( ( $row = fgetcsv( $handle, 100000, $this->delimiter, $this->enclosure ) ) !== FALSE ) {
				if ( !empty( $rows ) ) {
					if ( count( $row ) == count( $rows[0] ) ) {
						$rows[] = $row;
					}
				} else {
					$rows[] = $row;
				}
			}
			fclose( $handle );
		}
		return $rows;
	}

	public function save( Array $file, $path, $overwrite = FALSE, $headings = FALSE ) {
		if ( is_array( $file ) && !empty( $file ) ) {
			if ( !file_exists( $path ) || $overwrite ) {
				if ( !file_exists( dirname( $path ) ) ) mkdir( dirname( $path ), 0755, TRUE );
				$fp = fopen( $path, 'w+' );
				
				if ( $headings ) fputcsv( $fp, array_keys( $file[0] ), $this->delimiter, $this->enclosure );
				foreach ( $file as $line ) {
					fputcsv( $fp, $line, $this->delimiter, $this->enclosure );
				}
			
				fclose( $fp );
				return file_exists( $path );
			}
		}
	}

}

}

?>
