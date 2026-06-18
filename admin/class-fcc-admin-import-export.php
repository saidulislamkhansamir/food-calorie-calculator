<?php
/**
 * Admin: Import/Export page handler.
 *
 * Handles file upload (CSV or XLSX import) and export download triggers.
 * All file handling uses WP's native upload machinery.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Import_Export {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'admin_post_fcc_import',            $this, 'handle_import' );
		$loader->add_action( 'admin_post_fcc_export_csv',        $this, 'handle_export_csv' );
		$loader->add_action( 'admin_post_fcc_export_xlsx',       $this, 'handle_export_xlsx' );
		$loader->add_action( 'admin_post_fcc_download_template', $this, 'handle_download_template' );
		$loader->add_action( 'wp_ajax_fcc_ajax_import',          $this, 'ajax_import' );
	}

	// -------------------------------------------------------------------------
	// Import.
	// -------------------------------------------------------------------------

	public function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}

		check_admin_referer( 'fcc_import' );

		if ( empty( $_FILES['fcc_import_file']['tmp_name'] ) ) {
			$this->redirect_import( 'error', __( 'No file uploaded.', 'food-calorie-calculator' ) );
			return;
		}

		$file     = $_FILES['fcc_import_file'];
		$tmp_path = $file['tmp_name'];
		$name     = sanitize_file_name( $file['name'] );

		if ( ! is_uploaded_file( $tmp_path ) ) {
			$this->redirect_import( 'error', __( 'Invalid file upload.', 'food-calorie-calculator' ) );
			return;
		}

		// Server-side file size limit (10 MB — the HTML attribute is easily bypassed via cURL).
		if ( $file['size'] > 10 * 1024 * 1024 ) {
			$this->redirect_import( 'error', __( 'File exceeds the 10 MB limit.', 'food-calorie-calculator' ) );
			return;
		}

		// Extension allowlist.
		$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, [ 'csv', 'xlsx' ], true ) ) {
			$this->redirect_import( 'error', __( 'Only CSV and XLSX files are supported.', 'food-calorie-calculator' ) );
			return;
		}

		// XLSX must be a ZIP archive — verify magic bytes to reject renamed files.
		if ( 'xlsx' === $ext ) {
			$fh    = fopen( $tmp_path, 'rb' );
			$magic = $fh ? fread( $fh, 4 ) : '';
			if ( $fh ) {
				fclose( $fh );
			}
			if ( "\x50\x4b\x03\x04" !== $magic ) {
				$this->redirect_import( 'error', __( 'The uploaded file is not a valid XLSX file.', 'food-calorie-calculator' ) );
				return;
			}
		}

		if ( 'csv' === $ext ) {
			$result = \FCC\Import_Export::import_csv( $tmp_path );
		} elseif ( 'xlsx' === $ext ) {
			$result = \FCC\Import_Export::import_xlsx( $tmp_path );
		} else {
			$this->redirect_import( 'error', __( 'Only CSV and XLSX files are supported.', 'food-calorie-calculator' ) );
			return;
		}

		$msg = sprintf(
			// translators: 1: imported count, 2: skipped count.
			__( 'Import complete: %1$d food(s) imported, %2$d skipped.', 'food-calorie-calculator' ),
			$result['imported'],
			$result['skipped']
		);

		self::log_import( $name, $result['imported'], $result['skipped'] );

		if ( $result['errors'] ) {
			set_transient( 'fcc_import_errors', $result['errors'], 60 );
		}

		$this->redirect_import( $result['errors'] ? 'warning' : 'success', $msg );
	}

	// -------------------------------------------------------------------------
	// AJAX import.
	// -------------------------------------------------------------------------

	public function ajax_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'food-calorie-calculator' ), 403 );
		}

		check_ajax_referer( 'fcc_import' );

		if ( empty( $_FILES['fcc_import_file']['tmp_name'] ) ) {
			wp_send_json_error( __( 'No file uploaded.', 'food-calorie-calculator' ) );
		}

		$file     = $_FILES['fcc_import_file'];
		$tmp_path = $file['tmp_name'];
		$name     = sanitize_file_name( $file['name'] );

		if ( ! is_uploaded_file( $tmp_path ) ) {
			wp_send_json_error( __( 'Invalid file upload.', 'food-calorie-calculator' ) );
		}

		if ( $file['size'] > 10 * 1024 * 1024 ) {
			wp_send_json_error( __( 'File exceeds the 10 MB limit.', 'food-calorie-calculator' ) );
		}

		$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, [ 'csv', 'xlsx' ], true ) ) {
			wp_send_json_error( __( 'Only CSV and XLSX files are supported.', 'food-calorie-calculator' ) );
		}

		if ( 'xlsx' === $ext ) {
			$fh    = fopen( $tmp_path, 'rb' );
			$magic = $fh ? fread( $fh, 4 ) : '';
			if ( $fh ) {
				fclose( $fh );
			}
			if ( "\x50\x4b\x03\x04" !== $magic ) {
				wp_send_json_error( __( 'The uploaded file is not a valid XLSX file.', 'food-calorie-calculator' ) );
			}
		}

		if ( 'csv' === $ext ) {
			$result = \FCC\Import_Export::import_csv( $tmp_path );
		} else {
			$result = \FCC\Import_Export::import_xlsx( $tmp_path );
		}

		$msg = sprintf(
			// translators: 1: imported count, 2: skipped count.
			__( 'Import complete: %1$d food(s) imported, %2$d skipped.', 'food-calorie-calculator' ),
			$result['imported'],
			$result['skipped']
		);

		self::log_import( $name, $result['imported'], $result['skipped'] );

		wp_send_json_success( [
			'message' => $msg,
			'errors'  => $result['errors'] ?? [],
		] );
	}

	// -------------------------------------------------------------------------
	// Export.
	// -------------------------------------------------------------------------

	public function handle_export_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_export_csv' );
		\FCC\Import_Export::export_csv();
	}

	public function handle_export_xlsx(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_export_xlsx' );
		\FCC\Import_Export::export_xlsx();
	}

	/** Download a sample CSV template with headers + 1 example row. */
	public function handle_download_template(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Permission denied.' ); }
		check_admin_referer( 'fcc_download_template' );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="fcc-import-template.csv"' );

		$out = fopen( 'php://output', 'w' );
		fprintf( $out, "\xEF\xBB\xBF" );

		$cols = \FCC\Import_Export::columns();
		fputcsv( $out, array_keys( $cols ) );
		fputcsv( $out, [
			'Chicken Breast (raw)', 'Meat & Poultry', '165', '690', '31.0', '0.0',
			'0.0', '3.6', '1.0', '0', '1.0', '', '', '', '', '',
			'[{"label":"1 breast","grams":174}]', 'USDA FoodData Central',
		] );
		fclose( $out );
		exit;
	}

	/**
	 * Log an import event to the history option.
	 *
	 * @param string $filename  Original file name.
	 * @param int    $imported  Count of foods imported.
	 * @param int    $skipped   Count of rows skipped.
	 */
	public static function log_import( string $filename, int $imported, int $skipped ): void {
		$history = get_option( 'fcc_import_history', [] );
		if ( ! is_array( $history ) ) { $history = []; }
		array_unshift( $history, [
			'file'     => $filename,
			'imported' => $imported,
			'skipped'  => $skipped,
			'date'     => current_time( 'mysql' ),
			'user'     => wp_get_current_user()->display_name,
		] );
		$history = array_slice( $history, 0, 10 );
		update_option( 'fcc_import_history', $history );
	}

	// -------------------------------------------------------------------------
	// Helper.
	// -------------------------------------------------------------------------

	private function redirect_import( string $type, string $msg ): void {
		wp_safe_redirect( add_query_arg(
			[
				'page'       => 'fcc-import-export',
				'fcc_notice' => rawurlencode( $msg ),
				'fcc_ntype'  => $type,
			],
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
