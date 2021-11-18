<?php

namespace DpdConnect\classes\Connect;


use PrestaShopLogger;

class Product extends Connection
{
    public function getList(bool $filterAdditionalService = true)
    {
        try {
            // This could throw an error when no DPD credentials are set
            $dpdProducts = $this->client->product->getList();
        } catch (\Exception $exception) {
            PrestaShopLogger::addLog('Could not get products: ' . $exception->getMessage(), 3);
            return [];
        }

        return array_filter($dpdProducts, function ($product) use ($filterAdditionalService) {
            // Filter list where 'additionalService' is false
            if ($filterAdditionalService) {
                return $product['additionalService'] === false;
            }

            return true;
        });
    }

    public function getProductsByType(string $type)
    {
        return array_filter($this->getList(), function($product) use ($type) {
            return $product['type'] === $type;
        });
    }
}
