<?php
/**
 * SimpleXLSX — Read Excel 2007+ (.xlsx) files.
 *
 * Bundled single-file vendor library.
 * Original: https://github.com/shuchkin/simplexlsx (MIT Licence)
 * Version: 1.1.13 (trimmed for plugin use — core parse + rows only)
 *
 * MIT License
 * Copyright (c) 2012-2023 Sergey Shuchkin <sergey.shuchkin@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

if ( ! class_exists( 'SimpleXLSX' ) ) :

class SimpleXLSX {

	/** @var string|null Last error message */
	public static $error = null;

	/** @var array Parsed worksheets [ 0 => [ [row], [row], … ], … ] */
	protected $sheets = [];

	/** @var array Shared strings table */
	protected $shared_strings = [];

	/** @var array Number formats */
	protected $num_formats = [];

	/** @var array Cell XFs (style index → numFmt id) */
	protected $xfs = [];

	// -------------------------------------------------------------------------
	// Public static factory
	// -------------------------------------------------------------------------

	/**
	 * Parse an .xlsx file and return a SimpleXLSX object, or false on failure.
	 *
	 * @param string $filename Absolute path to .xlsx file.
	 * @return static|false
	 */
	public static function parse( string $filename ) {
		self::$error = null;
		$obj = new static();
		if ( $obj->_parse( $filename ) ) {
			return $obj;
		}
		return false;
	}

	// -------------------------------------------------------------------------
	// Public instance methods
	// -------------------------------------------------------------------------

	/**
	 * Returns all rows from a worksheet (0-indexed).
	 *
	 * @param int $worksheet_index 0-based.
	 * @return array<int, array<int, string>>
	 */
	public function rows( int $worksheet_index = 0 ): array {
		return $this->sheets[ $worksheet_index ] ?? [];
	}

	/**
	 * Number of sheets.
	 */
	public function sheetsCount(): int {
		return count( $this->sheets );
	}

	// -------------------------------------------------------------------------
	// Internal parse
	// -------------------------------------------------------------------------

	protected function _parse( string $filename ): bool {
		if ( ! file_exists( $filename ) ) {
			self::$error = 'File not found: ' . $filename;
			return false;
		}

		// Open zip.
		$zip = new ZipArchive();
		if ( $zip->open( $filename ) !== true ) {
			self::$error = 'Cannot open zip archive.';
			return false;
		}

		// Shared strings.
		$ss = $zip->getFromName( 'xl/sharedStrings.xml' );
		if ( $ss !== false ) {
			$this->_parse_shared_strings( $ss );
		}

		// Styles (number formats).
		$styles = $zip->getFromName( 'xl/styles.xml' );
		if ( $styles !== false ) {
			$this->_parse_styles( $styles );
		}

		// Workbook — find sheet relationships.
		$wb_rels = $zip->getFromName( 'xl/_rels/workbook.xml.rels' );
		$wb_xml  = $zip->getFromName( 'xl/workbook.xml' );
		$sheet_paths = $this->_find_sheet_paths( $wb_xml, $wb_rels );

		foreach ( $sheet_paths as $path ) {
			$xml = $zip->getFromName( 'xl/' . ltrim( $path, '/' ) );
			if ( $xml !== false ) {
				$this->sheets[] = $this->_parse_sheet( $xml );
			}
		}

		$zip->close();
		return true;
	}

	protected function _parse_shared_strings( string $xml ): void {
		$dom = $this->_load_xml( $xml );
		if ( ! $dom ) return;

		foreach ( $dom->getElementsByTagName( 'si' ) as $si ) {
			$t_nodes = $si->getElementsByTagName( 't' );
			$str     = '';
			foreach ( $t_nodes as $t ) {
				$str .= $t->nodeValue;
			}
			$this->shared_strings[] = $str;
		}
	}

	protected function _parse_styles( string $xml ): void {
		$dom = $this->_load_xml( $xml );
		if ( ! $dom ) return;

		// Built-in number formats (date/time IDs 14-22, 45-47).
		$this->num_formats = [];
		$nf_els = $dom->getElementsByTagName( 'numFmt' );
		foreach ( $nf_els as $nf ) {
			$id  = (int) $nf->getAttribute( 'numFmtId' );
			$fmt = $nf->getAttribute( 'formatCode' );
			$this->num_formats[ $id ] = $fmt;
		}

		$xf_list = $dom->getElementsByTagName( 'cellXfs' );
		if ( $xf_list->length ) {
			$xfs = $xf_list->item(0)->getElementsByTagName( 'xf' );
			foreach ( $xfs as $xf ) {
				$this->xfs[] = (int) $xf->getAttribute( 'numFmtId' );
			}
		}
	}

	protected function _find_sheet_paths( string $wb_xml, string $wb_rels ): array {
		$paths = [];

		if ( ! $wb_rels ) return [ 'worksheets/sheet1.xml' ];

		$dom = $this->_load_xml( $wb_rels );
		if ( ! $dom ) return [ 'worksheets/sheet1.xml' ];

		// Build id→target map.
		$rel_map = [];
		foreach ( $dom->getElementsByTagName( 'Relationship' ) as $rel ) {
			$rel_map[ $rel->getAttribute( 'Id' ) ] = $rel->getAttribute( 'Target' );
		}

		// Order sheets from workbook.xml.
		$dom2 = $this->_load_xml( $wb_xml );
		if ( $dom2 ) {
			foreach ( $dom2->getElementsByTagName( 'sheet' ) as $sheet ) {
				$rid = $sheet->getAttribute( 'r:id' );
				if ( isset( $rel_map[ $rid ] ) ) {
					$paths[] = $rel_map[ $rid ];
				}
			}
		}

		return $paths ?: [ 'worksheets/sheet1.xml' ];
	}

	/**
	 * Parse a single worksheet XML string into a 2D array of cell values.
	 *
	 * @return array<int, array<int, string>>
	 */
	protected function _parse_sheet( string $xml ): array {
		$dom = $this->_load_xml( $xml );
		if ( ! $dom ) return [];

		$rows = [];
		foreach ( $dom->getElementsByTagName( 'row' ) as $row_el ) {
			$row_idx = (int) $row_el->getAttribute( 'r' ) - 1;
			$row = [];

			foreach ( $row_el->getElementsByTagName( 'c' ) as $c ) {
				$ref    = $c->getAttribute( 'r' ); // e.g. A1, B2
				$col    = $this->_col_to_index( preg_replace( '/\d/', '', $ref ) );
				$type   = $c->getAttribute( 't' );
				$style  = (int) $c->getAttribute( 's' );
				$v_el   = $c->getElementsByTagName( 'v' )->item(0);
				$val    = $v_el ? $v_el->nodeValue : '';

				// Resolve value by type.
				if ( $type === 's' ) {
					// Shared string.
					$val = $this->shared_strings[ (int) $val ] ?? '';
				} elseif ( $type === 'inlineStr' ) {
					$t  = $c->getElementsByTagName( 't' )->item(0);
					$val = $t ? $t->nodeValue : '';
				} elseif ( $type === 'b' ) {
					$val = $val ? 'TRUE' : 'FALSE';
				} elseif ( $val !== '' ) {
					// Numeric — check if it's a date style.
					$num_fmt_id = $this->xfs[ $style ] ?? 0;
					if ( $this->_is_date_format( $num_fmt_id ) ) {
						$val = $this->_excel_date( (float) $val );
					}
					// Leave other numbers as-is (string representation is fine for import).
				}

				$row[ $col ] = (string) $val;
			}

			// Fill sparse gaps with empty strings so row is contiguous.
			if ( $row ) {
				$max_col = max( array_keys( $row ) );
				for ( $i = 0; $i <= $max_col; $i++ ) {
					$row[ $i ] = $row[ $i ] ?? '';
				}
				ksort( $row );
				$rows[ $row_idx ] = array_values( $row );
			}
		}

		ksort( $rows );
		return array_values( $rows );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	protected function _load_xml( string $xml ): ?\DOMDocument {
		if ( trim( $xml ) === '' ) return null;
		$dom  = new \DOMDocument();
		$prev = libxml_use_internal_errors( true );
		// Belt-and-suspenders XXE prevention. PHP 8.0+ disables entity loading by
		// default, but we also set substituteEntities = false and call the deprecated
		// libxml_disable_entity_loader() on older PHP for defence in depth.
		$dom->substituteEntities = false;
		if ( function_exists( 'libxml_disable_entity_loader' ) ) {
			$prev_entity = libxml_disable_entity_loader( true );
		}
		$ok = $dom->loadXML( $xml, LIBXML_NONET );
		if ( function_exists( 'libxml_disable_entity_loader' ) && isset( $prev_entity ) ) {
			libxml_disable_entity_loader( $prev_entity );
		}
		libxml_use_internal_errors( $prev );
		return $ok ? $dom : null;
	}

	protected function _col_to_index( string $col ): int {
		$col = strtoupper( $col );
		$n   = 0;
		$len = strlen( $col );
		for ( $i = 0; $i < $len; $i++ ) {
			$n = $n * 26 + ( ord( $col[ $i ] ) - 64 );
		}
		return $n - 1;
	}

	protected function _is_date_format( int $id ): bool {
		// Built-in date/time numFmt IDs per OOXML spec.
		$date_ids = [ 14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47 ];
		if ( in_array( $id, $date_ids, true ) ) return true;
		// Custom format: check for d/m/y/h characters.
		$fmt = $this->num_formats[ $id ] ?? '';
		return (bool) preg_match( '/[dDmMyYhH]/', str_replace( [ '"', "'" ], '', $fmt ) );
	}

	protected function _excel_date( float $serial ): string {
		// Excel epoch: 1900-01-01 (with 1900 leap-year bug offset).
		$ts = (int) ( ( $serial - 25569 ) * 86400 );
		return gmdate( 'Y-m-d', $ts );
	}
}

endif; // class_exists
