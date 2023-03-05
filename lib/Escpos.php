<?php

namespace Twf\Pps;

require_once(dirname(__FILE__) . "/../I18N/Arabic.php");

use I18N_Arabic;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintBuffers\ImagePrintBuffer;
use Mike42\Escpos\CapabilityProfiles\EposTepCapabilityProfile;

/*
 * Drop Ar-php into the folder listed below:
 */

class Escpos
{

	public $printer;
	public $char_per_line = 42;

	public function load($printer)
	{

		if ($printer->connection_type == 'network') {
			set_time_limit(30);
			$connector = new NetworkPrintConnector($printer->ip_address, $printer->port);
		} elseif ($printer->connection_type == 'linux') {
			$connector = new FilePrintConnector($printer->path);
		} else {
			$connector = new WindowsPrintConnector($printer->path);
		}

		$this->char_per_line = $printer->char_per_line;

		// $profile = CapabilityProfile::load($printer->capability_profile);
		$profile = CapabilityProfile::load("default");
		$this->printer = new Printer($connector, $profile);
	}

	public function print_invoice($data)
	{

		$fontPath = dirname(__FILE__) . "/../I18N/Arabic/Examples/GD/stv-bold.ttf";

		mb_internal_encoding("UTF-8");

		/*
 		* Set up and use an image print buffer with a suitable font
 		*/
		$buffer = new ImagePrintBuffer();
		$buffer->setFont($fontPath);
		$buffer->setFontSize(28);

		$this->printer->setPrintBuffer($buffer);

		//Print logo
		if (isset($data->logo) && !empty($data->logo)) {
			$logo = $this->download_image($data->logo);

			$this->printer->setJustification(Printer::JUSTIFY_CENTER);

			$logo = EscposImage::load($logo, false);
			//$this->printer->graphics($logo);
			$this->printer->bitImage($logo);
		}

		/* Header */
		$this->printer->setJustification(Printer::JUSTIFY_CENTER);
		$buffer->setFontSize(50);
		$this->printer->setPrintBuffer($buffer);
		if (isset($data->header_text) && !empty($data->header_text)) {
			$this->printer->text(mb_convert_encoding(strip_tags($data->header_text), 'UTF-8'));
			// $this->printer->feed();
		}

		$buffer->setFontSize(30);
		$this->printer->setPrintBuffer($buffer);

		/* Shop & Location Name */
		if (isset($data->display_name) && !empty($data->display_name)) {
			$this->printer->text(mb_convert_encoding($data->display_name, 'UTF-8'));
			// $this->printer->feed();
		}

		$buffer->setFontSize(20);
		$this->printer->setPrintBuffer($buffer);

		/* Shop Address */
		if (isset($data->address) && !empty($data->address)) {
			$this->printer->text($data->address);
			// $this->printer->feed(2);
		}

		/* Custom 5 lines */
		if (!empty($data->sub_heading_line1)) {
			$this->printer->text($data->sub_heading_line1);
			// $this->printer->feed(1);
		}
		if (!empty($data->sub_heading_line2)) {
			$this->printer->text($data->sub_heading_line2);
			// $this->printer->feed(1);
		}
		if (!empty($data->sub_heading_line3)) {
			$this->printer->text($data->sub_heading_line3);
			// $this->printer->feed(1);
		}
		if (!empty($data->sub_heading_line4)) {
			$this->printer->text($data->sub_heading_line4);
			// $this->printer->feed(1);
		}
		if (!empty($data->sub_heading_line5)) {
			$this->printer->text($data->sub_heading_line5);
			// $this->printer->feed(1);
		}

		/* Tax 1 & tax 2 info */
		if (!empty($data->tax_info1) || !empty($data->tax_info2)) {

			if (!empty($data->tax_info1)) {
				$this->printer->setEmphasis(true);
				$this->printer->text($data->tax_label1);
				$this->printer->setEmphasis(false);

				$this->printer->text($data->tax_info1);
				$this->printer->feed();
			}

			if (!empty($data->tax_info2)) {
				$this->printer->setEmphasis(true);
				$this->printer->text($data->tax_label2);
				$this->printer->setEmphasis(false);

				$this->printer->text($data->tax_info2);
				$this->printer->feed();
			}
		}

		$buffer->setFontSize(30);
		$this->printer->setPrintBuffer($buffer);

		/* Title of receipt */
		if (isset($data->invoice_heading) && !empty($data->invoice_heading)) {
			$this->printer->setEmphasis(true);
			$this->printer->text(mb_convert_encoding($data->invoice_heading, 'UTF-8'));
			$this->printer->setEmphasis(false);
			// $this->printer->feed(1);
		}
		
		/* invoice order num */
		if (isset($data->invoice_order_num) && !empty($data->invoice_order_num)) {
			$buffer->setFontSize(50);
			$this->printer->setPrintBuffer($buffer);

			$this->printer->setJustification(Printer::JUSTIFY_CENTER);

			$this->printer->text($this->drawLine());

			$this->printer->text(mb_convert_encoding($data->invoice_order_num_label . ' ' . $data->invoice_order_num, 'UTF-8'));
			
			$this->printer->text($this->drawLine());

		}

		/* Title of kitchen_name */
		if (isset($data->kitchen_name) && !empty($data->kitchen_name) && isset($data->table) && !empty($data->table)) {

			$buffer->setFontSize(25);
			$this->printer->setPrintBuffer($buffer);

			$this->printer->text(rtrim($this->columnify(mb_convert_encoding($data->kitchen_name, 'UTF-8'), $this->columnify(mb_convert_encoding($data->table_label, 'UTF-8'), mb_convert_encoding($data->table, 'UTF-8'), 25, 25, 0, 0), 50, 50, 0, 0)));

		} else if (isset($data->kitchen_name) && !empty($data->kitchen_name)) {
			
		$buffer->setFontSize(35);
		$this->printer->setPrintBuffer($buffer);

			$this->printer->setEmphasis(true);
			$this->printer->setTextSize(2, 2);
			$this->printer->text(mb_convert_encoding($data->kitchen_name, 'UTF-8'));
			$this->printer->setEmphasis(false);
			// $this->printer->feed(1);
			$this->printer->setTextSize(1, 1);

			$buffer->setFontSize(28);
			$this->printer->setPrintBuffer($buffer);
	
		}

		$this->printer->setJustification(Printer::JUSTIFY_CENTER);

		$invoice_no = $data->invoice_no_prefix;
		$invoice_no .= ' ' . $data->invoice_no;

		// & Date
		$date = $data->date_label;
		$date .= ' ' . $data->invoice_date;

		
		$buffer->setFontSize(23);
		$this->printer->setPrintBuffer($buffer);

		$this->printer->text(rtrim($this->columnify($invoice_no, $date, 50, 50, 0, 0)));

		//Customer info with tabled style
		if (!empty($data->customer_info) || !empty($data->client_id)) {

			$customer_info = '';
			if (!empty($data->customer_info)) {
				$customer_info = $data->customer_label;
				$customer_info .= ' ' . $data->customer_info;
			}

			$client_id = '';
			if (!empty($data->client_id)) {
				$client_id = $data->client_id_label;
				$client_id .= ' ' . $data->client_id;
			}

			$this->printer->text(rtrim($this->columnify($customer_info, $client_id, 50, 50, 0, 0)));
			// $this->printer->feed();
		}

		//Show products list
		if (isset($data->lines) && !empty($data->lines)) {

			//Print heading
			//QTY, ITEM, PRICE, TOTAL
			//10,		40, 25,25
			$buffer->setFontSize(35);
			$this->printer->setPrintBuffer($buffer);

			$this->printer->text($this->drawLine());
			
			$this->printer->setJustification(Printer::JUSTIFY_RIGHT);
			
			if (isset($data->kitchen_name)) {
				$string = $this->columnify(mb_convert_encoding($data->table_qty_label, 'UTF-8'),  ' ' . mb_convert_encoding($data->table_product_label, 'UTF-8'), 30, 70, 0, 0);
			} else {
				$string = $this->columnify($this->columnify($this->columnify($data->table_qty_label, ' ' . $data->table_product_label, 15, 80, 0, 0), $data->table_unit_price_label, 50, 25, 0, 0), ' ' . $data->table_subtotal_label, 75, 25, 0, 0);
			}
			
			$buffer->setFontSize(32);
			$this->printer->setPrintBuffer($buffer);

			$this->printer->text(trim($string));

			$this->printer->setJustification(Printer::JUSTIFY_CENTER);
			
			$buffer->setFontSize(28);
			$this->printer->setPrintBuffer($buffer);
			
			$this->printer->text($this->drawLine());
			
			$buffer->setFontSize(25);
			$this->printer->setPrintBuffer($buffer);
			
			$this->printer->setJustification(Printer::JUSTIFY_RIGHT);
			foreach ($data->lines as $key => $line) {
				$line = (array)$line;

				//Generate product name
				$product = $line['name'] . ' ' . $line['variation'];
				//sell_line_note
				if (!empty($line['sell_line_note'])) {
					$product = $product . '(' . $line['sell_line_note'] . ')';
				}
				//Sku
				if (!empty($line['sub_sku'])) {
					$product = $product . ', ' . $line['sub_sku'];
				}
				//brand
				if (!empty($line['brand'])) {
					$product = $product . ', ' . $line['brand'];
				}
				//cat_code
				if (!empty($line['cat_code'])) {
					$product = $product . ', ' . $line['cat_code'];
				}

				$quantity = $line['quantity'];

				//$unit_price = $line['unit_price_inc_tax'];
				$unit_price = $line['unit_price_exc_tax'];

				$line_total = $line['line_total'];

				if (isset($data->kitchen_name)) {
					$string = trim($this->columnify($quantity, mb_convert_encoding($product, 'UTF-8'), 30, 70, 0, 0));
				} else {
					$string = trim(
						$this->columnify(
							$this->columnify(
								$this->columnify($quantity, $product, 10, 30, 0, 0),
								$unit_price,
								50,
								25,
								0,
								0
							),

							$line_total,
							75,
							25,
							0,
							0
						),
					);
				}

				$this->printer->text($string);
				$this->printer->feed(1);
			}
			$this->printer->setJustification(Printer::JUSTIFY_CENTER);
			$this->printer->feed();
			$this->printer->text($this->drawLine());
		}

		//SubTotal, Discount, Tax, Total
		if (isset($data->subtotal) && !empty($data->subtotal)) {
			$subtotal = $this->columnify($data->subtotal_label, $data->subtotal, 50, 50, 0, 0);
			$this->printer->text(rtrim($subtotal));
			$this->printer->feed();
		}
		if (isset($data->discount) && !empty($data->discount) && $data->discount != 0) {
			$discount = $this->columnify($data->discount_label, $data->discount, 50, 50, 0, 0);
			$this->printer->text(rtrim($discount));
			$this->printer->feed();
		}
		if (isset($data->tax) && !empty($data->tax) && $data->tax != 0) {
			$tax = $this->columnify($data->tax_label, $data->tax, 50, 50, 0, 0);
			// $tax = $this->columnify('', $data->tax_label . ' ' . $data->tax, 40, 60, 0,0);
			$this->printer->text(rtrim($tax));
			$this->printer->feed();
		}
		if (isset($data->total) && !empty($data->total)) {
			$this->printer->setEmphasis(true);
			$total = $this->columnify($data->total_label, $data->total, 50, 50, 0, 0);
			$this->printer->text(rtrim($total));
			$this->printer->feed();
			$this->printer->setEmphasis(false);
		}

		//if payment is set then display payment details else only display the amount.
		if (!empty($data->payments)) {
			$this->printer->setEmphasis(true);
			$this->printer->text(rtrim($data->total_paid_label));
			$this->printer->feed();
			$this->printer->setEmphasis(false);

			foreach ($data->payments as $payment) {
				$total_paid = $this->columnify($payment->method, $payment->amount, 50, 50, 0, 0);
				$this->printer->text(rtrim($total_paid));
				$this->printer->feed();
			}
		} else {
			if (isset($data->total_paid) && !empty($data->total_paid)) {
				$total_paid = $this->columnify($data->total_paid_label, $data->total_paid, 50, 50, 0, 0);
				$this->printer->text(rtrim($total_paid));
				$this->printer->feed();
			}
		}

		if (isset($data->total_due) && !empty($data->total_due) && $data->total_due != 0) {
			$total_due = $this->columnify($data->total_due_label, $data->total_due, 50, 50, 0, 0);
			$this->printer->text(rtrim($total_due));
			$this->printer->feed();
			$this->printer->text($this->drawLine());
		}



		if (!empty($data->taxes)) {
			$this->printer->setEmphasis(true);
			$this->printer->setJustification(Printer::JUSTIFY_CENTER);
			$this->printer->text($data->tax_label . "\n");
			$this->printer->setJustification(Printer::JUSTIFY_LEFT);
			$this->printer->setEmphasis(false);
			$this->printer->text($this->drawLine());

			foreach ($data->taxes as $key => $value) {
				// columnify($leftCol, $rightCol, $leftWidthPercent, $rightWidthPercent, $space = 2, $remove_for_space = 0)
				$string = rtrim($this->columnify($key, $value, 50, 45, 0, 0));
				$this->printer->text($string);
				$this->printer->feed(1);
			}

			$this->printer->text($this->drawLine());
		}



		if (isset($data->footer_text) && !empty($data->footer_text)) {
			$this->printer->setJustification(Printer::JUSTIFY_CENTER);
			$this->printer->feed(1);
			$this->printer->text(strip_tags($data->footer_text) . "\n");
			$this->printer->feed();
		}

		if (isset($data->additional_notes) && !empty($data->additional_notes)) {
			$this->printer->setJustification(Printer::JUSTIFY_CENTER);
			$this->printer->feed(1);
			$this->printer->text(strip_tags($data->additional_notes) . "\n");
			$this->printer->feed();
		}

		//Barcode
		// if (isset($data->barcode) && !empty($data->barcode)) {
		// 	$this->printer->setBarcodeHeight(40);
		// 	$this->printer->setBarcodeWidth(2);
		// 	$this->printer->selectPrintMode();
		// 	$this->printer->setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW);
		// 	$this->printer->barcode($data->barcode, Printer::BARCODE_CODE39);
		// 	$this->printer->feed();
		// }


		$this->printer->feed();
		$this->printer->cut();

		if (isset($data->cash_drawer) && !empty($data->cash_drawer)) {
			$this->printer->pulse();
		}

		$this->printer->close();
	}

	public function open_drawer()
	{

		$this->printer->pulse();
		$this->printer->close();
	}

	function drawLine()
	{

		$new = '';
		for ($i = 1; $i < $this->char_per_line; $i++) {
			$new .= '-';
		}
		return $new . "\n";
	}

	function printLine($str, $size = NULL, $sep = ":", $space = NULL)
	{
		if (!$size) {
			$size = $this->char_per_line;
		}
		$size = $space ? $space : $size;
		$length = strlen($str);
		list($first, $second) = explode(":", $str, 2);
		$line = $first . ($sep == ":" ? $sep : '');
		for ($i = 1; $i < ($size - $length); $i++) {
			$line .= ' ';
		}
		$line .= ($sep != ":" ? $sep : '') . $second;
		return $line;
	}

	/**
	 * Arrange ASCII text into columns
	 * 
	 * @param string $leftCol
	 *            Text in left column
	 * @param string $rightCol
	 *            Text in right column
	 * @param number $leftWidthPercent
	 *            Width of left column
	 * @param number $rightWidthPercent
	 *            Width of right column
	 * @param number $space
	 *            Gap between columns
	 * @param number $remove_for_space
	 *            Remove the number of characters for spaces
	 * @return string Text in columns
	 */
	function columnify($leftCol, $rightCol, $leftWidthPercent, $rightWidthPercent, $space = 2, $remove_for_space = 0)
	{
		$char_per_line = $this->char_per_line - $remove_for_space;

		$leftWidth = $char_per_line * $leftWidthPercent / 100;
		$rightWidth = $char_per_line * $rightWidthPercent / 100;

		$leftWrapped = wordwrap($leftCol, $leftWidth, "\n", true);
		$rightWrapped = wordwrap($rightCol, $rightWidth, "\n", true);

		$leftLines = explode("\n", $leftWrapped);
		$rightLines = explode("\n", $rightWrapped);
		$allLines = array();
		for ($i = 0; $i < max(count($leftLines), count($rightLines)); $i++) {
			$leftPart = str_pad(isset($leftLines[$i]) ? $leftLines[$i] : "", $leftWidth, " ");
			$rightPart = str_pad(isset($rightLines[$i]) ? $rightLines[$i] : "", $rightWidth, " ");
			$allLines[] = $leftPart . str_repeat(" ", $space) . $rightPart;
		}
		return implode("\n", $allLines) . "\n";
	}

	/**
	 * Check if image is not present than check download and save it.
	 * 
	 * @param string $url
	 */
	function download_image($url)
	{

		$file = basename($url);

		$logo_directory = realpath(dirname(__FILE__) . '/../logos/');
		$logo_image = $logo_directory . '/' . $file;

		//Check if the file exists
		//If not, download and store it.
		//Reurn the file path
		$success = true;
		if (!file_exists($logo_image)) {
			$image_content = file_get_contents($url);
			$success = file_put_contents($logo_image, $image_content);
		}

		if ($success) {
			return $logo_image;
		} else {
			return false;
		}
	}
}
