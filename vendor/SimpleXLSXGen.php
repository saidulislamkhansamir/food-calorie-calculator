<?php
/**
 * SimpleXLSXGen — Write Excel 2007+ (.xlsx) files.
 *
 * Bundled single-file vendor library.
 * Original: https://github.com/shuchkin/simplexlsxgen (MIT Licence)
 * Version: 1.3.12 (trimmed for plugin use — array-to-xlsx export only)
 *
 * MIT License
 * Copyright (c) 2019-2023 Sergey Shuchkin <sergey.shuchkin@gmail.com>
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

if ( ! class_exists( 'SimpleXLSXGen' ) ) :

class SimpleXLSXGen {

	/** @var array<int, array<int, scalar>> */
	protected array $data;

	/** @var string Worksheet title */
	protected string $sheet_name;

	public function __construct( array $data = [], string $sheet_name = 'Sheet1' ) {
		$this->data       = $data;
		$this->sheet_name = $sheet_name;
	}

	// -------------------------------------------------------------------------
	// Static factory
	// -------------------------------------------------------------------------

	/**
	 * Create from a 2D array (rows × columns).
	 *
	 * @param array  $data       2D array of scalar values. NULL cells become empty.
	 * @param string $sheet_name Worksheet tab name.
	 * @return static
	 */
	public static function fromArray( array $data, string $sheet_name = 'Sheet1' ): static {
		return new static( $data, $sheet_name );
	}

	// -------------------------------------------------------------------------
	// Output
	// -------------------------------------------------------------------------

	/**
	 * Send .xlsx to the browser as a download and exit.
	 *
	 * @param string $filename Suggested download filename (e.g. "foods-export.xlsx").
	 */
	public function downloadAs( string $filename ): void {
		$content = $this->_build();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( $filename ) . '"' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'Cache-Control: max-age=0' );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Save to a file path and return success.
	 *
	 * @param string $path Absolute file path.
	 */
	public function saveAs( string $path ): bool {
		return (bool) file_put_contents( $path, $this->_build() );
	}

	// -------------------------------------------------------------------------
	// XLSX builder
	// -------------------------------------------------------------------------

	protected function _build(): string {
		$shared_strings   = [];
		$shared_index     = [];
		$sheet_xml_rows   = '';
		$row_num          = 1;

		foreach ( $this->data as $row ) {
			$cells = '';
			$col   = 0;
			foreach ( (array) $row as $val ) {
				$col_letter = $this->_col_letter( $col );
				$ref        = $col_letter . $row_num;
				$col++;

				if ( $val === null || $val === '' ) {
					$cells .= '<c r="' . $ref . '"/>';
					continue;
				}

				if ( is_numeric( $val ) ) {
					$cells .= '<c r="' . $ref . '"><v>' . $this->_xml( (string) $val ) . '</v></c>';
					continue;
				}

				// String — use shared strings.
				$str = (string) $val;
				if ( ! isset( $shared_index[ $str ] ) ) {
					$shared_index[ $str ]  = count( $shared_strings );
					$shared_strings[]      = $str;
				}
				$si = $shared_index[ $str ];
				$cells .= '<c r="' . $ref . '" t="s"><v>' . $si . '</v></c>';
			}
			$sheet_xml_rows .= '<row r="' . $row_num . '">' . $cells . '</row>';
			$row_num++;
		}

		$ss_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
			'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count( $shared_strings ) . '" uniqueCount="' . count( $shared_strings ) . '">';
		foreach ( $shared_strings as $s ) {
			$ss_xml .= '<si><t xml:space="preserve">' . $this->_xml( $s ) . '</t></si>';
		}
		$ss_xml .= '</sst>';

		$sheet_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
			'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
			'<sheetData>' . $sheet_xml_rows . '</sheetData></worksheet>';

		$workbook_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
			'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' .
			'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
			'<sheets><sheet name="' . $this->_xml( $this->sheet_name ) . '" sheetId="1" r:id="rId1"/></sheets>' .
			'</workbook>';

		$workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
			'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
			'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
			'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' .
			'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
			'</Relationships>';

		$styles_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
			'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
			'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>' .
			'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>' .
			'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' .
			'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
			'<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>' .
			'</styleSheet>';

		$content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
			'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
			'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
			'<Default Extension="xml"  ContentType="application/xml"/>' .
			'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
			'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
			'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' .
			'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
			'</Types>';

		$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
			'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
			'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
			'</Relationships>';

		// Pack into a ZIP in memory.
		$tmp = tempnam( sys_get_temp_dir(), 'xlsx' );
		$zip = new ZipArchive();
		$zip->open( $tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		$zip->addFromString( '[Content_Types].xml',           $content_types );
		$zip->addFromString( '_rels/.rels',                   $rels );
		$zip->addFromString( 'xl/workbook.xml',               $workbook_xml );
		$zip->addFromString( 'xl/_rels/workbook.xml.rels',    $workbook_rels );
		$zip->addFromString( 'xl/worksheets/sheet1.xml',      $sheet_xml );
		$zip->addFromString( 'xl/sharedStrings.xml',          $ss_xml );
		$zip->addFromString( 'xl/styles.xml',                 $styles_xml );
		$zip->close();

		$content = file_get_contents( $tmp );
		unlink( $tmp );
		return $content;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	protected function _col_letter( int $index ): string {
		$letter = '';
		$index++;
		while ( $index > 0 ) {
			$mod     = ( $index - 1 ) % 26;
			$letter  = chr( 65 + $mod ) . $letter;
			$index   = (int) ( ( $index - $mod ) / 26 );
		}
		return $letter;
	}

	protected function _xml( string $s ): string {
		return htmlspecialchars( $s, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}
}

endif; // class_exists
