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
        $selectedProduct = (int) Tools::getValue('id_product', 0);
        $sizes = $this->getProductSizes($selectedProduct);
        $selectedSize = (int) Tools::getValue('id_attribute', 0);

        if (!$this->isAttributeInList($sizes, $selectedSize)) {
            $selectedSize = 0;
        }

        $this->context->smarty->assign([
            'order_statuses'  => $orderStatuses,
            'products'        => $products,
            'form_action'     => $this->context->link->getAdminLink('AdminOrderExport'),
            'selected_status' => (int) Tools::getValue('id_order_state', 0),
            'selected_product'=> $selectedProduct,
            'sizes'           => $sizes,
            'selected_size'   => $selectedSize,
        ]);

        $this->setTemplate('order_export_form.tpl');
    }

    /**
     * Generate and stream the CSV file.
     */
    private function exportCsv()
    {
        $idOrderState = (int) Tools::getValue('id_order_state', 0);
        $idProduct    = (int) Tools::getValue('id_product', 0);
        $idAttribute  = (int) Tools::getValue('id_attribute', 0);

        if ($idProduct <= 0) {
            $idAttribute = 0;
        }

        $exporter = new OrderExporter();
        $rows     = $exporter->getFilteredOrders($idOrderState, $idProduct, $idAttribute);

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

    /**
     * Return all non-colour variant attributes for the selected product.
     *
     * @param int $idProduct
     *
     * @return array
     */
    private function getProductSizes($idProduct)
    {
        $idProduct = (int) $idProduct;
        if ($idProduct <= 0) {
            return [];
        }

        $idLang = (int) $this->context->language->id;
        $sql = '
            SELECT DISTINCT a.`id_attribute`, al.`name`
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                ON pa.`id_product_attribute` = pac.`id_product_attribute`
            INNER JOIN `' . _DB_PREFIX_ . 'attribute` a
                ON pac.`id_attribute` = a.`id_attribute`
            INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . $idLang . ')
            INNER JOIN `' . _DB_PREFIX_ . 'attribute_group` ag
                ON a.`id_attribute_group` = ag.`id_attribute_group`
            WHERE pa.`id_product` = ' . $idProduct . '
                AND ag.`is_color_group` = 0
            ORDER BY al.`name` ASC
        ';

        $results = Db::getInstance()->executeS($sql);

        return is_array($results) ? $results : [];
    }

    /**
     * Validate whether attribute exists in available sizes list.
     *
     * @param array $sizes
     * @param int $idAttribute
     *
     * @return bool
     */
    private function isAttributeInList(array $sizes, $idAttribute)
    {
        $idAttribute = (int) $idAttribute;
        if ($idAttribute <= 0) {
            return false;
        }

        foreach ($sizes as $size) {
            if ((int) $size['id_attribute'] === $idAttribute) {
                return true;
            }
        }

        return false;
    }
}
