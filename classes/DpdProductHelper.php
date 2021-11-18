<?php

namespace DpdConnect\classes;

use Configuration;
use DpdConnect\classes\Connect\Product;
use DbQuery;
use Db;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManager;

class DpdProductHelper
{
    // Map a DPD Product to a specific carrier
    public function mapProductToCarrier(array $dpdProduct, string $carrierId)
    {
        if ($carrierId === false) {
            return;
        }

        $result = Db::getInstance()->execute('
            INSERT INTO `' . _DB_PREFIX_ . 'carrier_dpd_product` (`carrier_id`, `dpd_product_code`) 
            VALUES(' . (int)($carrierId) . ',"' . ($dpdProduct['code']) . '")
		');

        return empty($result) ? false : $result;
    }

    // Retrieve a carrier which uses supplied DPD Product
    public function getCarrierByProduct(array $dpdProduct)
    {
        if (empty($dpdProduct)) {
            return false;
        }

        $sql = new DbQuery();
        $sql->from('carrier_dpd_product');
        $sql->select('*');
        $sql->where('dpd_product_code = "' . $dpdProduct['code'] . '"');
        $sql->limit(1);

        $result = current(Db::getInstance()->ExecuteS($sql));

        return $result ?? false;
    }

    public function getProductByCarrier(string $carrierId)
    {
        if (!$carrierId) {
            return false;
        }
        $sql = new DbQuery();
        $sql->from('carrier_dpd_product');
        $sql->select('*');
        $sql->where('carrier_id = "' . $carrierId . '"');
        $sql->limit(1);

        $result = current(Db::getInstance()->ExecuteS($sql));
        if (!$result) {
            return false;
        }

        $product = $this->getProductByProductCode($result['dpd_product_code']);
        if (!$product) {
            return false;
        }

        return $product;
    }

    // Get all DPD Product codes from the DPD Carriers
    public function getCarrierProductCodes()
    {
        $query = new DbQuery();
        $query->select('dpd_product_code');
        $query->from('carrier_dpd_product');

        $result = Db::getInstance()->ExecuteS($query);

        if ($result) {
            return array_column($result, 'dpd_product_code');
        }

        return false;
    }

    // Get all DPD Carriers
    public function getDpdCarriers()
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('carrier_dpd_product');

        $result = Db::getInstance()->ExecuteS($query);

        return $result ?? false;
    }

    // Return first DPD Product that matches supplied Product code
    public function getProductByProductCode(string $productCode)
    {
        $connectProduct = new Product();

        foreach ($connectProduct->getList() as $product) {
            if ($product['code'] === $productCode) {
                return $product;
            }
        }

        return false;
    }

    // Return first DPD Product that matches supplied Product type
    public function getProductByProductType(string $productType)
    {
        $connectProduct = new Product();

        foreach ($connectProduct->getList() as $product) {
            if ($product['type'] === $productType) {
                return $product;
            }
        }

        return false;
    }

    public function isDpdCarrier(string $carrierId)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('carrier_dpd_product');
        $query->where('carrier_id = "' . $carrierId . '"');
        $query->limit(1);

        $result = current(Db::getInstance()->ExecuteS($query));

        return !empty($result);
    }

    public function getDpdParcelshopCarrierId()
    {
        $dpdParcelshopProduct = $this->getProductByProductType('parcelshop');
        if (!$dpdParcelshopProduct) {
            return false;
        }

        $dpdParcelshopCarrier = $this->getCarrierByProduct($dpdParcelshopProduct);
        if (!$dpdParcelshopCarrier) {
            return false;
        }

        return $dpdParcelshopCarrier['carrier_id'];
    }

    // Update DPD Carriers by checking for added/removed DPD Products and adding/deleting carriers for those products
    public function updateDPDCarriers()
    {
        // Check if module has been installed. If not installed, skip update
        if (Configuration::get('dpd') !== 'dpdconnect') {
            return;
        }

        // Check if the carriers have been updated in the past 60 minutes. If so, skip update
        $carriersLastUpdatedAt = Configuration::get('dpd_carriers_updated_at');
        if ($carriersLastUpdatedAt && ((time() - $carriersLastUpdatedAt) / 60) <= 60) {
            return;
        }


        $dpdCarrier = new DpdCarrier();
        try {
            $connectProduct = new Product();
        } catch (\Exception $exception) {
            return;
        }

        $activeDpdProducts = $connectProduct->getList();

        if (!empty($activeDpdProducts)) {
            Configuration::updateValue('dpd_carriers_updated_at', time());
        }

        $activeDpdProductCodes = array_column($activeDpdProducts, 'code');
        $existingDpdCarriers = $this->getDpdCarriers();

        // Check for disabled or enabled products from DPD's side. If found, create/disable carriers for those product(s)
        $addedDpdProductCodes = array_diff($activeDpdProductCodes, array_column($existingDpdCarriers, 'dpd_product_code'));
        $removedDpdProductCodes = array_diff(array_column($existingDpdCarriers, 'dpd_product_code'), $activeDpdProductCodes);

        // Create carriers for dpd products which didn't exist yet
        foreach ($addedDpdProductCodes as $addedDpdProductCode) {
            $dpdProduct = $this->getProductByProductCode($addedDpdProductCode);

            // DPD Fresh/Freeze products shouldn't be carriers
            if (strtolower($dpdProduct['type']) === 'fresh') {
                continue;
            }

            $carrier = $this->getCarrierByProduct($dpdProduct);

            // Carrier exists, but is soft-deleted. Undo soft delete and continue to next iteration
            if ($carrier) {
                $dpdCarrier->unDeleteCarrier($dpdCarrier->getLatestCarrierByReferenceId($carrier['carrier_id']));
                continue;
            }

            // Carrier does not exist. Create new carrier
            $dpdCarrier->createCarrier($dpdProduct);
        }

        foreach ($existingDpdCarriers as $existingDpdCarrier) {
            // Undo soft-deleted carriers with active DPD Product, because all carriers with an active DPD Product should be displayed in the back-end carrier list
            if (in_array($existingDpdCarrier['dpd_product_code'], $activeDpdProductCodes)) {
                // Undo soft-delete for this carrier
                $dpdCarrier->unDeleteCarrier($dpdCarrier->getLatestCarrierByReferenceId($existingDpdCarrier['carrier_id']));
            }

            // Check if carrier should be removed
            if (in_array($existingDpdCarrier['dpd_product_code'], $removedDpdProductCodes)) {
                // Remove (soft-delete) this carrier
                $dpdCarrier->softDeleteCarriers($dpdCarrier->getLatestCarrierByReferenceId($existingDpdCarrier['carrier_id']));
            }
        }
    }
}