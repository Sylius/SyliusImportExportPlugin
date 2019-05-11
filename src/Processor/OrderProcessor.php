<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Processor;

use Doctrine\Common\Persistence\ObjectManager;
use FriendsOfSylius\SyliusImportExportPlugin\Formatter\DateTimeFormatterInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\OrderItemUnitInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Factory\OrderItemUnitFactoryInterface;
use Sylius\Component\Order\Model\AdjustableInterface;
use Sylius\Component\Order\Model\AdjustmentInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class OrderProcessor implements ResourceProcessorInterface
{
    /** @var FactoryInterface */
    private $orderFactory;

    /** @var FactoryInterface */
    private $customerFactory;

    /** @var FactoryInterface */
    private $addressFactory;

    /** @var FactoryInterface */
    private $orderItemFactory;

    /** @var OrderItemUnitFactoryInterface */
    private $orderItemUnitFactory;

    /** @var FactoryInterface */
    private $adjustmentFactory;

    /** @var FactoryInterface */
    private $shipmentFactory;

    /** @var FactoryInterface */
    private $paymentFactory;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var RepositoryInterface */
    private $paymentMethodRepository;

    /** @var RepositoryInterface */
    private $shippingMethodRepository;

    /** @var RepositoryInterface */
    private $channelRepository;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var RepositoryInterface */
    private $productVariantRepository;

    /** @var ObjectManager */
    private $manager;

    /** @var DateTimeFormatterInterface */
    private $dateTimeFormatter;

    /** @var MetadataValidatorInterface */
    private $metadataValidator;

    /** @var array */
    private $orderStatesApplicableToImport;

    /** @var bool */
    private $alwaysCreateNew;

    /** @var string[] */
    private $headerKeys;

    public function __construct(
        FactoryInterface $orderFactory,
        FactoryInterface $customerFactory,
        FactoryInterface $addressFactory,
        FactoryInterface $orderItemFactory,
        OrderItemUnitFactoryInterface $orderItemUnitFactory,
        FactoryInterface $adjustmentFactory,
        FactoryInterface $shipmentFactory,
        FactoryInterface $paymentFactory,
        OrderRepositoryInterface $orderRepository,
        RepositoryInterface $paymentMethodRepository,
        RepositoryInterface $shippingMethodRepository,
        RepositoryInterface $channelRepository,
        CustomerRepositoryInterface $customerRepository,
        RepositoryInterface $productVariantRepository,
        ObjectManager $manager,
        DateTimeFormatterInterface $dateTimeFormatter,
        MetadataValidatorInterface $metadataValidator,
        array $orderStatesApplicableToImport,
        bool $alwaysCreateNew,
        array $headerKeys
    ) {
        $this->orderFactory = $orderFactory;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderItemUnitFactory = $orderItemUnitFactory;
        $this->adjustmentFactory = $adjustmentFactory;
        $this->shipmentFactory = $shipmentFactory;
        $this->paymentFactory = $paymentFactory;
        $this->orderRepository = $orderRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->channelRepository = $channelRepository;
        $this->customerRepository = $customerRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->manager = $manager;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->metadataValidator = $metadataValidator;
        $this->orderStatesApplicableToImport = $orderStatesApplicableToImport;
        $this->alwaysCreateNew = $alwaysCreateNew;
        $this->headerKeys = $headerKeys;
    }

    public function process(array $data): void
    {
        if (!in_array($data['State'], $this->orderStatesApplicableToImport)) {
            return;
        }

        $this->metadataValidator->validateHeaders($this->headerKeys, $data);

        /** @var OrderInterface $order */
        $order = $this->getOrder($data['Number']);

        /** @var CustomerInterface $customer */
        $customer = $this->getCustomer($data['Customer']);

        $order->setCreatedAt($this->getDateTimeFromString($data['CreatedAt']));
        $order->setUpdatedAt($this->getDateTimeFromString($data['UpdatedAt']));
        $order->setCheckoutCompletedAt($this->getDateTimeFromString($data['CheckoutCompletedAt']));

        $order->setCurrencyCode($data['CurrencyCode']);
        $order->setLocaleCode($data['LocaleCode']);
        $order->setNotes($data['Notes']);
        $order->setState($data['State']);
        $order->setCustomerIp($data['CustomerIp']);
        $order->setTokenValue($data['TokenValue']);
        $order->setCustomer($customer);
        $order->setPaymentState($data['PaymentState']);
        $order->setShippingState($data['ShippingState']);

        $order->setChannel($this->getChannel($data['Channel']));
        $order->setShippingAddress($this->getAddress($data['ShippingAddress'], $customer));
        $order->setBillingAddress($this->getAddress($data['BillingAddress'], $customer));
        $order->setCheckoutState($data['CheckoutState']);

        $this->addItemsToOrder($order, $data['Items']);

        /** @var array $adjustment */
        foreach ($data['Adjustments'] as $adjustment) {
            $this->getAdjustment($adjustment, $order);
        }

        /** @var array $shipment */
        foreach ($data['Shipments'] as $shipment) {
            $order->addShipment($this->getShipment($shipment, $order));
        }

        /** @var array $payment */
        foreach ($data['Payments'] as $payment) {
            $order->addPayment($this->getPayment($payment, $order));
        }
    }

    private function getOrder(string $number): OrderInterface
    {
        if ($this->alwaysCreateNew) {
            return $this->createOrder($number);
        }

        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->findOneByNumber($number);

        if (null === $order) {
            return $this->createOrder($number);
        }

        return $order;
    }

    private function createOrder(string $number): OrderInterface
    {
        /** @var OrderInterface $order */
        $order = $this->orderFactory->createNew();
        $order->setNumber($number);
        $this->manager->persist($order);

        return $order;
    }

    private function getCustomer(array $parameters): CustomerInterface
    {
        /** @var CustomerInterface|null $customer */
        $customer = $this->customerRepository->findOneBy(['emailCanonical' => $parameters['EmailCanonical']]);

        if ($customer === null) {
            /** @var CustomerInterface $customer */
            $customer = $this->customerFactory->createNew();
            $customer->setEmailCanonical($parameters['EmailCanonical']);
        }

        $customer->setCreatedAt($this->getDateTimeFromString($parameters['CreatedAt']));
        $customer->setUpdatedAt($this->getDateTimeFromString($parameters['UpdatedAt']));
        $customer->setEmail($parameters['Email']);
        $customer->setBirthday($this->getDateTimeFromString($parameters['Birthday']));
        $customer->setFirstName($parameters['FirstName']);
        $customer->setLastName($parameters['LastName']);
        $customer->setGender($parameters['Gender']);
        $customer->setPhoneNumber($parameters['PhoneNumber']);
        $customer->setSubscribedToNewsletter($parameters['SubscribedToNewsletter']);

        $this->manager->persist($customer);

        if ($parameters['DefaultAddress'] !== []) {
            $customer->setDefaultAddress($this->getAddress($parameters['DefaultAddress'], $customer));
        }

        return $customer;
    }

    private function getAddress(array $parameters, CustomerInterface $customer): AddressInterface
    {
        /** @var AddressInterface $address */
        $address = $this->addressFactory->createNew();

        $address->setPhoneNumber($parameters['PhoneNumber']);
        $address->setFirstName($parameters['FirstName']);
        $address->setLastName($parameters['LastName']);
        $address->setCity($parameters['City']);
        $address->setStreet($parameters['Street']);
        $address->setPostcode($parameters['Postcode']);
        $address->setCountryCode($parameters['CountryCode']);
        $address->setCompany($parameters['Company']);
        $address->setProvinceCode($parameters['ProvinceCode']);
        $address->setProvinceName($parameters['ProvinceName']);
        $address->setCustomer($customer);

        $this->manager->persist($address);

        return $address;
    }

    private function getChannel(?string $code): ?ChannelInterface
    {
        /** @var ChannelInterface|null $channel */
        $channel = $this->channelRepository->findOneBy(['code' => $code]);

        return $channel;
    }

    private function addItemsToOrder(OrderInterface $order, array $orderItemsParameters): void
    {
        /** @var array $orderItemParameters */
        foreach ($orderItemsParameters as $orderItemParameters) {
            /** @var OrderItemInterface $orderItem */
            $orderItem = $this->orderItemFactory->createNew();
            $orderItem->setOrder($order);

            $orderItem->setImmutable($orderItemParameters['Immutable']);
            $orderItem->setUnitPrice($orderItemParameters['UnitPrice']);

            foreach ($orderItemParameters['Units'] as $orderItemUnitParameters) {
                /** @var OrderItemUnitInterface $orderItemUnit */
                $orderItemUnit = $this->orderItemUnitFactory->createForItem($orderItem);
                $orderItemUnit->setCreatedAt($this->getDateTimeFromString($orderItemUnitParameters['CreatedAt']));
                $orderItemUnit->setUpdatedAt($this->getDateTimeFromString($orderItemUnitParameters['UpdatedAt']));
                $orderItemUnit->setShipment($this->getShipment($orderItemUnitParameters['Shipment'], $order));

                foreach ($orderItemUnitParameters['Adjustments'] as $adjustment) {
                    $orderItemUnit->addAdjustment($this->getAdjustment($adjustment, $orderItemUnit));
                }
            }

            foreach ($orderItemParameters['Adjustments'] as $adjustment) {
                $orderItem->addAdjustment($this->getAdjustment($adjustment, $orderItem));
            }

            $orderItem->setVariant($this->getProductVariant($orderItemParameters['Variant']));
        }
    }

    private function getAdjustment(array $parameters, AdjustableInterface $adjustable): AdjustmentInterface
    {
        /** @var AdjustmentInterface $adjustment */
        $adjustment = $this->adjustmentFactory->createNew();

        $adjustment->setCreatedAt($this->getDateTimeFromString($parameters['CreatedAt']));
        $adjustment->setUpdatedAt($this->getDateTimeFromString($parameters['UpdatedAt']));
        $adjustment->setAmount($parameters['Amount']);
        $adjustment->setLabel($parameters['Label']);
        $adjustment->setType($parameters['Type']);
        $adjustment->setAdjustable($adjustable);
        $adjustment->setNeutral($parameters['Neutral']);
        $adjustment->setOriginCode($parameters['OriginCode']);

        $this->manager->persist($adjustment);

        return $adjustment;
    }

    private function getShipment(array $parameters, OrderInterface $order): ShipmentInterface
    {
        /** @var ShipmentInterface $shipment */
        $shipment = $this->shipmentFactory->createNew();

        $shipment->setCreatedAt($this->getDateTimeFromString($parameters['CreatedAt']));
        $shipment->setUpdatedAt($this->getDateTimeFromString($parameters['UpdatedAt']));
        $shipment->setState($parameters['State']);
        $shipment->setTracking($parameters['Tracking']);
        $shipment->setMethod($this->getShippingMethod($parameters['Method']));
        $shipment->setOrder($order);

        $this->manager->persist($shipment);

        return $shipment;
    }

    private function getPayment(array $parameters, OrderInterface $order): PaymentInterface
    {
        /** @var PaymentInterface $payment */
        $payment = $this->paymentFactory->createNew();
        $payment->setOrder($order);

        $payment->setCreatedAt($this->getDateTimeFromString($parameters['CreatedAt']));
        $payment->setUpdatedAt($this->getDateTimeFromString($parameters['UpdatedAt']));
        $payment->setMethod($this->getPaymentMethod($parameters['PaymentMethod']));
        $payment->setState($parameters['State']);
        $payment->setAmount($parameters['Amount']);
        $payment->setCurrencyCode($parameters['CurrencyCode']);
        $payment->setDetails($parameters['Details']);

        $this->manager->persist($payment);

        return $payment;
    }

    private function getProductVariant(?string $code): ?ProductVariantInterface
    {
        /** @var ProductVariantInterface|null $productVariant */
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $code]);

        return $productVariant;
    }

    private function getShippingMethod(?string $code): ?ShippingMethodInterface
    {
        /** @var ShippingMethodInterface|null $shippingMethod */
        $shippingMethod = $this->shippingMethodRepository->findOneBy(['code' => $code]);

        return $shippingMethod;
    }

    private function getPaymentMethod(?string $code): ?PaymentMethodInterface
    {
        /** @var PaymentMethodInterface|null $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository->findOneBy(['code' => $code]);

        return $paymentMethod;
    }

    private function getDateTimeFromString(?string $dateTimeAsString): ?\DateTimeInterface
    {
        return null !== $dateTimeAsString ? $this->dateTimeFormatter->toDateTime($dateTimeAsString) : null;
    }
}
