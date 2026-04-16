<?php

namespace App\Services\Gateway;

use App\Contracts\Gateway\PaymentGatewayInterface;
use App\Exceptions\InvalidPaymentConfigException;
use App\Exceptions\PaymentProviderNotFoundException;
use App\Models\Provider;
use App\Models\ProviderPaymentType;

class PaymentGatewayFactory
{
    /**
     * Create gateway instance by provider_payment_type_id.
     */
    public function createByProviderPaymentTypeId(int $pptId): PaymentGatewayInterface
    {
        $ppt = ProviderPaymentType::with('provider')->findOrFail($pptId);

        return $this->createFromProvider($ppt->provider);
    }

    /**
     * Create gateway instance by vendor_id string.
     */
    public function createByVendorId(string $vendorId): PaymentGatewayInterface
    {
        $provider = Provider::where('vendor_id', $vendorId)->firstOrFail();

        return $this->createFromProvider($provider);
    }

    /**
     * Create gateway instance from a Provider model.
     */
    public function createFromProvider(Provider $provider): PaymentGatewayInterface
    {
        $vendorId = $provider->vendor_id;

        if (! $vendorId) {
            throw new InvalidPaymentConfigException("Provider #{$provider->id} has no vendor_id");
        }

        // 3-layer config merge: config/gateways.php < provider.vendor_meta < system fields
        $defaultConfig = config("gateways.{$vendorId}", []);
        $dbConfig = $provider->vendor_meta ?? [];
        $systemFields = [
            'provider_id' => $provider->id,
            'provider_no' => $provider->provider_no,
            'vendor_id' => $vendorId,
        ];

        $mergedConfig = array_merge($defaultConfig, $dbConfig, $systemFields);

        $className = $mergedConfig['classname'] ?? null;

        if (! $className || ! class_exists($className)) {
            throw new PaymentProviderNotFoundException($vendorId);
        }

        /** @var AbstractPaymentGateway $gateway */
        $gateway = app()->make($className);
        $gateway->setConfig($mergedConfig);
        $gateway->setVendorId($vendorId);

        return $gateway;
    }
}
