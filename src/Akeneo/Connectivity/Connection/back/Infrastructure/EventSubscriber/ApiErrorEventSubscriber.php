<?php

declare(strict_types=1);

namespace Akeneo\Connectivity\Connection\Infrastructure\EventSubscriber;

use Akeneo\Connectivity\Connection\Infrastructure\ErrorManagement\CollectApiError;
use Akeneo\Connectivity\Connection\Infrastructure\Event\ProductDomainErrorEvent;
use Akeneo\Connectivity\Connection\Infrastructure\Event\ProductValidationErrorEvent;
use Akeneo\Connectivity\Connection\Infrastructure\Event\TechnicalErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class ApiErrorEventSubscriber implements EventSubscriberInterface
{
    /** @var CollectApiError */
    private $collectApiError;

    public function __construct(CollectApiError $collectApiError)
    {
        $this->collectApiError = $collectApiError;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductDomainErrorEvent::class => 'collectProductDomainError',
            ProductValidationErrorEvent::class => 'collectProductValidationError',
            TechnicalErrorEvent::class => 'collectTechnicalError',
            KernelEvents::TERMINATE => 'flushApiErrors',
        ];
    }

    public function collectProductDomainError(ProductDomainErrorEvent $event): void
    {
        $this->collectApiError->collectFromProductDomainError($event->getProduct(), $event->getError());
    }

    public function collectProductValidationError(ProductValidationErrorEvent $event): void
    {
        $this->collectApiError->collectFromProductValidationError(
            $event->getProduct(),
            $event->getConstraintViolationList()
        );
    }

    public function collectTechnicalError(TechnicalErrorEvent $event): void
    {
        $this->collectApiError->collectFromTechnicalError($event->getError());
    }

    public function flushApiErrors(): void
    {
        $this->collectApiError->flush();
    }
}
