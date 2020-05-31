<?php

declare(strict_types=1);

namespace Akeneo\Connectivity\Connection\Infrastructure\Normalizer;

use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Tool\Component\Api\Normalizer\Exception\ViolationNormalizer;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Normalize a ViolationHttpException with all errors
 *
 * @author Pierre Jolly <pierre.jolly@akeneo.com>
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ConstraintViolationNormalizer extends ViolationNormalizer
{
    /**
     * @param ConstraintViolationInterface $object
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $existingViolation = [];
        $data = parent::normalizeViolation($object, $existingViolation);

        $data['type'] = 'violation';
        $data['raw_message'] = $object->getMessageTemplate();
        $data['parameters'] = $object->getParameters();

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
        return $data instanceof ConstraintViolationInterface;
    }
}
