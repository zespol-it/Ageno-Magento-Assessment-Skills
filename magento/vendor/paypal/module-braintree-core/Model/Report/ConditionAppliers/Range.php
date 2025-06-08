<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);
namespace PayPal\Braintree\Model\Report\ConditionAppliers;

use Braintree\RangeNode;

/**
 * Range applier
 */
class Range implements ApplierInterface
{
    /**
     * @inheritDoc
     */
    public function apply($field, $condition, $value): bool
    {
        $result = false;

        switch ($condition) {
            case ApplierInterface::QTEQ:
                $field->greaterThanOrEqualTo($value);
                $result = true;
                break;
            case ApplierInterface::LTEQ:
                $field->lessThanOrEqualTo($value);
                $result = true;
                break;
        }

        return $result;
    }
}
