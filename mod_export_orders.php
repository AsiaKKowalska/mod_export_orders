<?php
/**
 * mod_export_orders - PrestaShop 1.7.8.10 module for exporting orders to CSV
 *
 * @author    Asia Kowalska
 * @copyright 2024 Asia Kowalska
 * @license   MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mod_Export_Orders extends Module
{
    public function __construct()
    {
        $this->name          = 'mod_export_orders';
        $this->tab           = 'administration';
        $this->version       = '1.0.0';
        $this->author        = 'Asia Kowalska';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '1.7.9.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Export Orders');
        $this->description = $this->l('Export orders to CSV with filtering by status and product.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    /**
     * Install the module: register admin tab.
     */
    public function install()
    {
        return parent::install() && $this->installTab();
    }

    /**
     * Uninstall the module: remove admin tab.
     */
    public function uninstall()
    {
        return $this->uninstallTab() && parent::uninstall();
    }

    /**
     * Register the admin menu entry under "Orders".
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active       = 1;
        $tab->class_name   = 'AdminOrderExport';
        $tab->module       = $this->name;
        $tab->id_parent    = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->icon         = 'icon-download';

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Export Orders');
        }

        return $tab->add();
    }

    /**
     * Remove the admin menu entry.
     */
    private function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminOrderExport');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * Module configuration page (redirects to the export controller).
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminOrderExport')
        );
    }
}
