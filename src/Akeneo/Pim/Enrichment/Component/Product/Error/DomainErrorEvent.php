<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Component\Product\Error;

use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;

/**
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DomainErrorEvent
{
    /** @var IdentifiableDomainErrorInterface */
    private $error;

    public function __construct(IdentifiableDomainErrorInterface $error)
    {
        $this->error = $error;
    }

    public function getError(): IdentifiableDomainErrorInterface
    {
        return $this->error;
    }
}
