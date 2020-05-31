<?php

declare(strict_types=1);

namespace Akeneo\Connectivity\Connection\Infrastructure\ErrorManagement;

use Akeneo\Connectivity\Connection\Application\ConnectionContextInterface;
use Akeneo\Connectivity\Connection\Application\ErrorManagement\Command\UpdateConnectionErrorCountCommand;
use Akeneo\Connectivity\Connection\Application\ErrorManagement\Command\UpdateConnectionErrorCountHandler;
use Akeneo\Connectivity\Connection\Domain\ErrorManagement\ErrorTypes;
use Akeneo\Connectivity\Connection\Domain\ErrorManagement\Model\Write\ApiErrorCollection;
use Akeneo\Connectivity\Connection\Domain\ErrorManagement\Model\Write\BusinessError;
use Akeneo\Connectivity\Connection\Domain\ErrorManagement\Model\Write\HourlyErrorCount;
use Akeneo\Connectivity\Connection\Domain\ErrorManagement\Model\Write\TechnicalError;
use Akeneo\Connectivity\Connection\Domain\ErrorManagement\Persistence\Repository\BusinessErrorRepository;
use Akeneo\Connectivity\Connection\Domain\Settings\Model\ValueObject\FlowType;
use Akeneo\Connectivity\Connection\Domain\ValueObject\HourlyInterval;
use Akeneo\Pim\Enrichment\Component\Product\Error\IdentifiableDomainErrorInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Tool\Component\Api\Exception\ViolationHttpException;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class CollectApiError
{
    /** @var BusinessErrorRepository */
    private $repository;

    /** @var ConnectionContextInterface */
    private $connectionContext;

    /** @var UpdateConnectionErrorCountHandler */
    private $updateErrorCountHandler;

    /** @var Serializer */
    private $serializer;

    /** @var ApiErrorCollection */
    private $errors;

    public function __construct(
        ConnectionContextInterface $connectionContext,
        BusinessErrorRepository $repository,
        UpdateConnectionErrorCountHandler $updateErrorCountHandler,
        Serializer $serializer
    ) {
        $this->connectionContext = $connectionContext;
        $this->repository = $repository;
        $this->updateErrorCountHandler = $updateErrorCountHandler;
        $this->serializer = $serializer;
        $this->errors = new ApiErrorCollection();
    }

    public function collectFromProductDomainError(
        ProductInterface $product,
        IdentifiableDomainErrorInterface $error
    ): void {
        if (false === $this->isConnectionCollectable()) {
            return;
        }

        $context = (new Context())->setAttribute('product', $product);
        $json = $this->serializer->serialize($error, 'json', $context);
        $this->errors->add(new BusinessError($json));
    }

    public function collectFromProductValidationError(
        ProductInterface $product,
        ConstraintViolationListInterface $constraintViolationList
    ): void {
        if (false === $this->isConnectionCollectable()) {
            return;
        }

        $context = (new Context())->setAttribute('product', $product);
        foreach ($constraintViolationList as $constraintViolation) {
            $json = $this->serializer->serialize($constraintViolation, 'json', $context);
            $this->errors->add(new BusinessError($json));
        }
    }

    public function collectFromTechnicalError(\Throwable $error): void
    {
        if (false === $this->isConnectionCollectable()) {
            return;
        }

        $this->errors->add(new TechnicalError('{"message":""}')); // TODO We don't save technical error anymore
    }

    public function flush(): void
    {
        if (0 === $this->errors->count()) {
            return;
        }

        $connection = $this->connectionContext->getConnection();
        if (null === $connection) {
            return;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $errorCounts = [];
        foreach ($this->errors->getSorted() as $errorType => $errors) {
            $errorCounts[] = new HourlyErrorCount(
                (string) $connection->code(),
                HourlyInterval::createFromDateTime($now),
                count($errors),
                $errorType
            );
        }

        $command = new UpdateConnectionErrorCountCommand($errorCounts);
        $this->updateErrorCountHandler->handle($command);

        /** @var BusinessError[] */
        $businessErrors = $this->errors->getByType(ErrorTypes::BUSINESS);
        $this->repository->bulkInsert($connection->code(), $businessErrors);
    }

    private function isConnectionCollectable(): bool
    {
        $connection = $this->connectionContext->getConnection();
        if (null === $connection) {
            return false;
        }

        if (
            false === $this->connectionContext->isCollectable() ||
            FlowType::DATA_SOURCE !== (string) $connection->flowType()
        ) {
            return false;
        }

        return true;
    }
}
