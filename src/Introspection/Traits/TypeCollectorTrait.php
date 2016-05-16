<?php
/**
 * Date: 04.12.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\GraphQL\Introspection\Traits;

use Youshido\GraphQL\Type\AbstractType;
use Youshido\GraphQL\Type\TypeMap;

trait TypeCollectorTrait
{

    protected $types = [];

    protected function collectTypes(AbstractType $type)
    {
        if (!$type) {
            return;
        }
        if (is_object($type) && array_key_exists($type->getName(), $this->types)) return;

        switch ($type->getKind()) {
            case TypeMap::KIND_INTERFACE:
            case TypeMap::KIND_UNION:
            case TypeMap::KIND_ENUM:
            case TypeMap::KIND_SCALAR:
                $this->insertType($type->getName(), $type);

                if ($type->getKind() == TypeMap::KIND_UNION) {
                    foreach ($type->getTypes() as $subType) {
                        $this->collectTypes($subType);
                    }
                }

                break;

            case TypeMap::KIND_INPUT_OBJECT:
            case TypeMap::KIND_OBJECT:
                $namedType = $type->getNamedType();

                if ($namedType->getKind() == TypeMap::KIND_LIST) {
                    $namedType = $namedType->getNamedType();
                }

                $this->checkAndInsertInterfaces($namedType);

                if ($this->insertType($namedType->getName(), $namedType)) {
                    $this->collectFieldsArgsTypes($namedType);
                }

                break;

            case TypeMap::KIND_LIST:
                $this->collectTypes($type->getNamedType());
                break;

            case TypeMap::KIND_NON_NULL:
                $this->collectTypes($type->getNamedType());

                break;
        }
    }

    private function checkAndInsertInterfaces(AbstractType $type)
    {
        $interfaces = $type->getConfig()->getInterfaces();

        if (is_array($interfaces) && $interfaces) {
            foreach ($interfaces as $interface) {
                $this->insertType($interface->getName(), $interface);
            }
        }
    }

    /**
     * @param $type AbstractType
     */
    private function collectFieldsArgsTypes($type)
    {
        if (!$type->getConfig()) {
            return;
        }

        foreach ($type->getConfig()->getFields() as $field) {
            $arguments = $field->getConfig()->getArguments();

            if (is_array($arguments)) {
                foreach ($arguments as $argument) {
                    $this->collectTypes($argument->getType());
                }
            }

            $this->collectTypes($field->getType());
        }
    }

    private function insertType($name, $type)
    {
        if (!array_key_exists($name, $this->types)) {
            $this->types[$name] = $type;

            return true;
        }

        return false;
    }

}
