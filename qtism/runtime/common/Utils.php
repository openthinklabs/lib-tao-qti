<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2013-2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 * @license GPLv2
 */

namespace qtism\runtime\common;

use InvalidArgumentException;
use qtism\common\datatypes\QtiBoolean;
use qtism\common\datatypes\QtiDatatype;
use qtism\common\datatypes\QtiFloat;
use qtism\common\datatypes\QtiIdentifier;
use qtism\common\datatypes\QtiInteger;
use qtism\common\datatypes\QtiIntOrIdentifier;
use qtism\common\datatypes\QtiString;
use qtism\common\datatypes\QtiUri;
use qtism\common\enums\BaseType;
use qtism\runtime\common\Utils as RuntimeUtils;

/**
 * Utility class gathering utility methods for the \qtism\runtime\common namespace.
 */
class Utils
{
    /**
     * Whether a given $value is compliant with the QTI runtime model. In other words,
     * if it is null or a QTI Scalar Datatype.
     *
     * @param mixed $value A value you want to check the compatibility with the QTI runtime model.
     * @return bool
     */
    public static function isRuntimeCompliant($value)
    {
        if ($value === null) {
            return true;
        } elseif ($value instanceof QtiDatatype) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Whether a given $value is compliant with a given $baseType.
     *
     * @param int $baseType A value from the BaseType enumeration.
     * @param mixed $value A value.
     * @return bool
     */
    public static function isBaseTypeCompliant($baseType, $value)
    {
        if ($value === null) {
            return true; // A value can always be null.
        } elseif ($value instanceof QtiDatatype && $baseType === $value->getBaseType()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Whether a given $cardinality is compliant with a given $value.
     *
     * @param int $cardinality
     * @param mixed $value
     * @return bool
     */
    public static function isCardinalityCompliant($cardinality, $value)
    {
        if ($value === null) {
            return true;
        } elseif ($value instanceof QtiDatatype && $cardinality === $value->getCardinality()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Throw an InvalidArgumentException depending on a PHP in-memory value.
     *
     * @param mixed $value A given PHP primitive value.
     * @throws InvalidArgumentException In any case.
     */
    public static function throwTypingError($value)
    {
        $givenValue = (gettype($value) == 'object') ? get_class($value) : gettype($value);
        $acceptedTypes = ['boolean', 'integer', 'float', 'double', 'string', 'Duration', 'Pair', 'DirectedPair', 'Point'];
        $acceptedTypes = implode(', ', $acceptedTypes);
        $msg = "A value is not compliant with the QTI runtime model datatypes: ${acceptedTypes} . '${givenValue}' given.";
        throw new InvalidArgumentException($msg);
    }

    /**
     * Throw an InvalidArgumentException depending on a given qti:baseType
     * and an in-memory PHP value.
     *
     * @param int $baseType A value from the BaseType enumeration.
     * @param mixed $value A given PHP primitive value.
     * @throws InvalidArgumentException In any case.
     */
    public static function throwBaseTypeTypingError($baseType, $value)
    {
        $givenValue = (gettype($value) == 'object') ? get_class($value) : gettype($value) . ':' . $value;
        $acceptedTypes = BaseType::getNameByConstant($baseType);
        $msg = "The value '${givenValue}' is not compliant with the '${acceptedTypes}' baseType.";
        throw new InvalidArgumentException($msg);
    }

    /**
     * Infer the QTI baseType of a given $value.
     *
     * @param mixed $value A value you want to know the QTI baseType.
     * @return int|false A value from the BaseType enumeration or false if the baseType could not be infered.
     */
    public static function inferBaseType($value)
    {
        if ($value === null) {
            return false;
        } elseif ($value instanceof RecordContainer) {
            return false;
        } elseif ($value instanceof QtiDatatype) {
            return $value->getBaseType();
        } else {
            return false;
        }
    }

    /**
     * Infer the cardinality of a given $value.
     *
     * Please note that:
     *
     * * A RecordContainer has no cardinality, thus it always returns false for such a container.
     * * The null value has no cardinality, this it always returns false for such a value.
     *
     * @param mixed $value A value you want to infer the cardinality.
     * @return int|bool A value from the Cardinality enumeration or false if it could not be infered.
     */
    public static function inferCardinality($value)
    {
        if ($value === null) {
            return false;
        } elseif ($value instanceof QtiDatatype) {
            return $value->getCardinality();
        } else {
            return false;
        }
    }

    /**
     * Whether a given $string is a valid variable identifier.
     *
     * Q01            -> Valid
     * Q_01            -> Valid
     * 1_Q01        -> Invalid
     * Q01.SCORE    -> Valid
     * Q-01.1.Score    -> Valid
     * Q*01.2.Score    -> Invalid
     *
     * @param string $string A string value.
     * @return bool Whether the given $string is a valid variable identifier.
     */
    public static function isValidVariableIdentifier($string)
    {
        if (gettype($string) !== 'string' || empty($string)) {
            return false;
        }

        $pattern = '/^[a-z][a-z0-9_\-]*(?:(?:\.[1-9][0-9]*){0,1}(?:\.[a-z][a-z0-9_\-]*){0,1}){0,1}$/iu';

        return preg_match($pattern, $string) === 1;
    }

    /**
     * Makes $value compliant with baseType $targetBaseType, if $value is compliant. Otherwise,
     * the original $value is returned.
     *
     * @param mixed $value A QTI Runtime compliant value.
     * @param int $targetBaseType The target baseType.
     * @return mixed The juggled value if needed, otherwise the original value of $value.
     */
    public static function juggle($value, $targetBaseType)
    {
        // A lot of people designing QTI items want to put float values
        // in integer baseType'd variables... So let's go for type juggling!

        $valueBaseType = RuntimeUtils::inferBaseType($value);

        if ($valueBaseType !== $targetBaseType && ($value instanceof MultipleContainer || $value instanceof OrderedContainer)) {
            $class = get_class($value);

            if ($valueBaseType === BaseType::FLOAT && $targetBaseType === BaseType::INTEGER) {
                $value = new $class($targetBaseType, self::floatArrayToInteger($value->getArrayCopy()));
            } elseif ($valueBaseType === BaseType::INTEGER && $targetBaseType === BaseType::FLOAT) {
                $value = new $class($targetBaseType, self::integerArrayToFloat($value->getArrayCopy()));
            } elseif ($valueBaseType === BaseType::IDENTIFIER && $targetBaseType === BaseType::STRING) {
                $value = new $class($targetBaseType, $value->getArrayCopy());
            } elseif ($valueBaseType === BaseType::STRING && $targetBaseType === BaseType::IDENTIFIER) {
                $value = new $class($targetBaseType, $value->getArrayCopy());
            } elseif ($valueBaseType === BaseType::URI && $targetBaseType === BaseType::STRING) {
                $value = new $class($targetBaseType, $value->getArrayCopy());
            } elseif ($valueBaseType === BaseType::STRING && $targetBaseType === BaseType::URI) {
                $value = new $class($targetBaseType, $value->getArrayCopy());
            } elseif ($valueBaseType === BaseType::URI && $targetBaseType === BaseType::IDENTIFIER) {
                $value = new $class($targetBaseType, $value->getArrayCopy());
            } elseif ($valueBaseType === BaseType::IDENTIFIER && $targetBaseType === BaseType::URI) {
                $value = new $class($targetBaseType, $value->getArrayCopy());
            }
        } elseif ($valueBaseType !== $targetBaseType) {
            // Scalar value.
            if ($valueBaseType === BaseType::FLOAT && $targetBaseType === BaseType::INTEGER) {
                $value = (int)$value;
            } elseif ($valueBaseType === BaseType::INTEGER && $targetBaseType === BaseType::FLOAT) {
                $value = (float)$value;
            }
        }

        return $value;
    }

    /**
     * Check whether or not $firstBaseType is compliant with $secondBaseType.
     *
     * The following associations of baseTypes are considered to be compliant:
     *
     * * identifier - string
     * * string - identifier
     * * uri - string
     * * string - uri
     * * uri - identifier
     * * identifier - uri
     * * string - intOrIdentifier
     * * integer - intOrIdentifier
     * * identifier - intOrIdentifier
     *
     * @param int $firstBaseType A value from the baseType enumeration.
     * @param int $secondBaseType A value from the baseType enumeration.
     * @return bool
     */
    public static function areBaseTypesCompliant($firstBaseType, $secondBaseType)
    {
        if ($firstBaseType === $secondBaseType) {
            return true;
        } elseif ($firstBaseType === BaseType::IDENTIFIER && $secondBaseType === BaseType::STRING) {
            return true;
        } elseif ($firstBaseType === BaseType::STRING && $secondBaseType === BaseType::IDENTIFIER) {
            return true;
        } elseif ($firstBaseType === BaseType::URI && $secondBaseType === BaseType::STRING) {
            return true;
        } elseif ($firstBaseType === BaseType::STRING && $secondBaseType === BaseType::URI) {
            return true;
        } elseif ($firstBaseType === BaseType::URI && $secondBaseType === BaseType::IDENTIFIER) {
            return true;
        } elseif ($firstBaseType === BaseType::IDENTIFIER && $secondBaseType === BaseType::URI) {
            return true;
        } elseif ($firstBaseType === BaseType::STRING && $secondBaseType === BaseType::INT_OR_IDENTIFIER) {
            return true;
        } elseif ($firstBaseType === BaseType::INTEGER && $secondBaseType === BaseType::INT_OR_IDENTIFIER) {
            return true;
        } elseif ($firstBaseType === BaseType::IDENTIFIER && $secondBaseType === BaseType::INT_OR_IDENTIFIER) {
            return true;
        }

        return false;
    }

    /**
     * Transforms the content of float array to an integer array.
     *
     * @param array $floatArray An array containing float values.
     * @return array An array containing integer values.
     */
    public static function floatArrayToInteger($floatArray)
    {
        $integerArray = [];
        foreach ($floatArray as $f) {
            $integerArray[] = (is_null($f) === false) ? (int)$f : null;
        }

        return $integerArray;
    }

    /**
     * Transforms the content of an integer array to a float array.
     *
     * @param array $integerArray An array containing integer values.
     * @return array An array containing float values.
     */
    public static function integerArrayToFloat($integerArray)
    {
        $floatArray = [];
        foreach ($integerArray as $i) {
            $floatArray[] = (is_null($i) === false) ? (float)$i : null;
        }

        return $floatArray;
    }

    /**
     * Transform a given PHP scalar value to a QtiScalar equivalent object.
     *
     * @param mixed|null $v
     * @param int $baseType A value from the BaseType enumeration.
     * @return QtiScalar
     */
    public static function valueToRuntime($v, $baseType)
    {
        if ($v !== null) {
            if (is_int($v) === true) {
                if ($baseType === -1 || $baseType === BaseType::INTEGER) {
                    return new QtiInteger($v);
                } elseif ($baseType === BaseType::INT_OR_IDENTIFIER) {
                    return new QtiIntOrIdentifier($v);
                }
            } elseif (is_string($v) === true) {
                if ($baseType === BaseType::IDENTIFIER) {
                    return new QtiIdentifier($v);
                }
                if ($baseType === -1 || $baseType === BaseType::STRING) {
                    return new QtiString($v);
                } elseif ($baseType === BaseType::URI) {
                    return new QtiUri($v);
                } elseif ($baseType === BaseType::INT_OR_IDENTIFIER) {
                    return new QtiIntOrIdentifier($v);
                }
            } elseif (is_float($v) === true) {
                return new QtiFloat($v);
            } elseif (is_bool($v) === true) {
                return new QtiBoolean($v);
            }
        }

        return $v;
    }

    /**
     * Whether or not a QtiDatatype is considered to be null.
     *
     * As per the QTI specification, the NULL value, empty strings and empty containers
     * are always treated as NULL values.
     *
     * @param QtiDatatype $value
     * @return bool
     */
    public static function isNull(QtiDatatype $value = null)
    {
        return is_null($value) === true || ($value instanceof QtiString && $value->getValue() === '') || ($value instanceof Container && count($value) === 0);
    }
}
