<?php
/**
 * AdminOrderExportController
 *
 * Handles the order-export admin page: renders the filter form and
 * streams a CSV file when the user clicks "Download CSV".
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/OrderExporter.php';

class AdminOrderExportController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->meta_title = $this->l('Export Orders');
    }

    /**
     * Main entry point – render form or export CSV.
     */
    public function initContent()
    {
        parent::initContent();

        if (Tools::isSubmit('export_csv')) {
            $this->exportCsv();
            return;
        }

        $this->renderExportForm();
    }

    /**
     * Build and assign the filter form to the Smarty template.
     */
    private function renderExportForm()
    {
        $orderStatuses = OrderState::getOrderStates($this->context->language->id);
        $products      = $this->getAllProducts();

        $this->context->smarty->assign([
            'order_statuses'  => $orderStatuses,
            'products'        => $products,
            'form_action'     => $this->context->link->getAdminLink('AdminOrderExport'),
            'selected_status' => (int) Tools::getValue('id_order_state', 0),
            'selected_product'=> (int) Tools::getValue('id_product', 0),
        ]);

        $this->setTemplate(
            _PS_MODULE_DIR_ . 'mod_export_orders/views/admin/order_export_form.tpl'
        );
    }

    /**
     * Generate and stream the CSV file.
     */
    private function exportCsv()
    {
        $idOrderState = (int) Tools::getValue('id_order_state', 0);
        $idProduct    = (int) Tools::getValue('id_product', 0);

        $exporter = new OrderExporter();
        $rows     = $exporter->getFilteredOrders($idOrderState, $idProduct);

        $filename = 'orders_export_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, [
            $this->l('Order Number'),
            $this->l('First Name'),
            $this->l('Last Name'),
            $this->l('Phone'),
            $this->l('Order Date'),
            $this->l('Total Amount'),
        ], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['reference'],
                $row['firstname'],
                $row['lastname'],
                $row['phone'],
                $row['date_add'],
                $row['total_paid'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Return a flat list of all active products (id + name).
     *
     * @return array
     */
    private function getAllProducts()
    {
        $idLang = (int) $this->context->language->id;
        $sql = '
            SELECT p.`id_product`, pl.`name`
            FROM `' . _DB_PREFIX_ . 'product` p
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                ON (p.`id_product` = pl.`id_product`
                    AND pl.`id_lang` = ' . $idLang . '
                    AND pl.`id_shop` = ' . (int) $this->context->shop->id . ')
            WHERE p.`active` = 1
            ORDER BY pl.`name` ASC
        ';

        return Db::getInstance()->executeS($sql);
    }
}
