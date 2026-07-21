<?php
/**
 * OrderExporter
 *
 * Retrieves orders from the PrestaShop database, optionally filtered by
 * order-state and/or a specific product contained in the order.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderExporter
{
    /**
     * Return orders matching the given filters.
     *
     * @param int $idOrderState  0 = all statuses
     * @param int $idProduct     0 = all products
     * @param int $idAttribute   0 = all sizes
     *
     * @return array  Each element contains: reference, firstname, lastname,
     *                phone, date_add, total_paid
     */
    public function getFilteredOrders($idOrderState = 0, $idProduct = 0, $idAttribute = 0)
    {
        $idOrderState = (int) $idOrderState;
        $idProduct    = (int) $idProduct;
        $idAttribute  = (int) $idAttribute;

        $sql = '
            SELECT DISTINCT
                o.`reference`,
                a.`firstname`,
                a.`lastname`,
                a.`phone`,
                o.`date_add`,
                o.`total_paid`
            FROM `' . _DB_PREFIX_ . 'orders` o
            LEFT JOIN `' . _DB_PREFIX_ . 'address` a
                ON o.`id_address_delivery` = a.`id_address`
        ';

        // Join order_detail only when filtering by product
        if ($idProduct > 0) {
            $sql .= '
            INNER JOIN `' . _DB_PREFIX_ . 'order_detail` od
                ON o.`id_order` = od.`id_order`
                AND od.`product_id` = ' . $idProduct . '
            ';

            if ($idAttribute > 0) {
                $sql .= '
                INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                    ON od.`product_attribute_id` = pac.`id_product_attribute`
                    AND pac.`id_attribute` = ' . $idAttribute . '
                ';
            }
        }

        $conditions = [];

        if ($idOrderState > 0) {
            $conditions[] = 'o.`current_state` = ' . $idOrderState;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY o.`date_add` DESC';

        $results = Db::getInstance()->executeS($sql);

        if (!is_array($results)) {
            return [];
        }

        // Format values for CSV output
        foreach ($results as &$row) {
            $row['total_paid'] = number_format((float) $row['total_paid'], 2, ',', ' ');
            $row['date_add']   = date('d.m.Y H:i', strtotime($row['date_add']));
            $row['phone']      = isset($row['phone']) ? $row['phone'] : '';
        }

        return $results;
    }
}
