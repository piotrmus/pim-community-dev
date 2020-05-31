<?php

declare(strict_types=1);

namespace Akeneo\Connectivity\Connection\Infrastructure\Normalizer;

use Akeneo\Pim\Enrichment\Component\Product\Error\IdentifiableDomainErrorInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductDomainErrorNormalizer implements NormalizerInterface
{
    /**
     * @param IdentifiableDomainErrorInterface $object
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $data = [
            'domain_error_code' => $object->getDomainErrorCode(),
            'message' => $object->getMessage(),
        ];

        if (isset($context['product'])) {
            $product = $context['product'];
            if (false === $product instanceof ProductInterface) {
                throw new \LogicException(
                    sprintf('Context property "product" should be an instance of %s', ProductInterface::class)
                );
            }

            $data['product'] = [
                'id' => $product->getId()
            ];
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof IdentifiableDomainErrorInterface;
    }
}
