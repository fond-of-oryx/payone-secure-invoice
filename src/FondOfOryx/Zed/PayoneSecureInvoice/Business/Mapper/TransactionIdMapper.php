<?php

namespace FondOfOryx\Zed\PayoneSecureInvoice\Business\Mapper;

use FondOfOryx\Shared\PayoneSecureInvoice\PayoneSecureInvoiceConstants;
use FondOfOryx\Zed\PayoneSecureInvoice\Persistence\PayoneSecureInvoiceRepositoryInterface;
use SprykerEco\Shared\Payone\PayoneApiConstants;
use SprykerEco\Zed\Payone\Business\Api\Request\Container\ContainerInterface;
use SprykerEco\Zed\Payone\Business\Key\HashGeneratorInterface;

class TransactionIdMapper implements TransactionIdMapperInterface
{
    /**
     * @var array<string>
     */
    protected const VALID_PAYMENT_METHODS = [
        PayoneApiConstants::PAYMENT_METHOD_INVOICE,
        PayoneApiConstants::PAYMENT_METHOD_SECURITY_INVOICE,
    ];

    /**
     * @var \FondOfOryx\Zed\PayoneSecureInvoice\Persistence\PayoneSecureInvoiceRepositoryInterface
     */
    protected $repository;

    /**
     * @var \SprykerEco\Zed\Payone\Business\Key\HashGeneratorInterface
     */
    protected $hashGenerator;

    /**
     * @param \FondOfOryx\Zed\PayoneSecureInvoice\Persistence\PayoneSecureInvoiceRepositoryInterface $repository
     * @param \SprykerEco\Zed\Payone\Business\Key\HashGeneratorInterface $hashGenerator
     */
    public function __construct(PayoneSecureInvoiceRepositoryInterface $repository, HashGeneratorInterface $hashGenerator)
    {
        $this->repository = $repository;
        $this->hashGenerator = $hashGenerator;
    }

    /**
     * @param \SprykerEco\Zed\Payone\Business\Api\Request\Container\ContainerInterface $requestContainer
     * @param array<string, string> $credentials
     *
     * @return \SprykerEco\Zed\Payone\Business\Api\Request\Container\ContainerInterface
     */
    public function map(ContainerInterface $requestContainer, array $credentials): ContainerInterface
    {
        if (
            !method_exists($requestContainer, 'getTxid') ||
            !method_exists($requestContainer, 'setAid') ||
            !method_exists($requestContainer, 'setPortalid') ||
            !method_exists($requestContainer, 'setKey')
        ) {
            return $requestContainer;
        }

        $paymentMethod = $this->repository->getPaymentMethodByTxId($requestContainer->getTxid());

        if ($paymentMethod === null || !in_array($paymentMethod, static::VALID_PAYMENT_METHODS)) {
            return $requestContainer;
        }

        $requestContainer->setAid($credentials[PayoneSecureInvoiceConstants::PAYONE_CREDENTIALS_AID]);
        $requestContainer->setPortalid($credentials[PayoneSecureInvoiceConstants::PAYONE_CREDENTIALS_PORTAL_ID]);
        $requestContainer->setKey($this->hashGenerator->hash($credentials[PayoneSecureInvoiceConstants::PAYONE_CREDENTIALS_KEY]));

        return $requestContainer;
    }
}
