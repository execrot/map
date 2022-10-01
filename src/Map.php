<?php

declare(strict_types=1);

namespace Light\Map;

use Iterator;
use ArrayAccess; 
use Closure;

/**
 * Class Map
 * @package Light
 */
class Map
{
  /**
   * @param $data
   * @param $mapper
   * @param array|null $userData
   *
   * @return array|null
   */
  public static function execute($data, $mapper, array $userData = null): mixed
  {
    if (!$data) {
      return null;
    }

    if (is_array($mapper)) {
      return self::executeAssoc($data, $mapper, $userData);
    }

    if (is_string($mapper)) {
      return self::executeLine($data, $mapper, $userData);
    }

    if ($mapper instanceof Closure) {
      return self::executeLineClosure($data, $mapper, $userData);
    }

    return null;
  }

  /**
   * @param Countable|ArrayAccess|array $data
   * @return bool
   */
  private static function isSingle(Countable|ArrayAccess|array $data): bool
  {
    if (is_array($data)) {
      return !isset($data[0]);
    }
    return !(class_implements($data)[\Countable::class] ?? false);
  }

  /**
   * @param ArrayAccess|array $data
   * @param array $mapper
   * @param array|null $userData
   *
   * @return array|null
   */
  private static function executeAssoc(
    ArrayAccess|array $data,
    array             $mapper,
    array             $userData = null
  ): ?array
  {
    if (self::isSingle($data)) {
      return self::executeSingle($data, $mapper, $userData);
    }
    $mapped = [];
    foreach ($data as $row) {
      $mapped[] = self::executeSingle($row, $mapper, $userData);
    }
    return $mapped;
  }

  /**
   * @param $data
   * @param string $mapper
   * @param array|null $userData
   *
   * @return array|null
   */
  private static function executeLine($data, string $mapper, array $userData = null): ?array
  {
    if (self::isSingle($data)) {
      return self::executeSingle($data, [$mapper], $userData)[$mapper];
    }

    $mapped = [];
    foreach ($data as $row) {
      $mapped[] = self::executeSingle($row, [$mapper], $userData)[$mapper];
    }
    return $mapped;
  }

  /**
   * @param $data
   * @param Closure $mapper
   * @param array|null $userData
   *
   * @return array
   */
  private static function executeLineClosure($data, Closure $mapper, array $userData = null): mixed
  {
    if (self::isSingle($data)) {
      return self::transform($data, null, $mapper, $userData);
    }

    $mapped = [];
    foreach ($data as $row) {
      $mapped[] = self::transform($row, null, $mapper, $userData);
    }
    return $mapped;
  }

  /**
   * @param $data
   * @param array $mapper
   * @param array|null $userData
   *
   * @return array
   */
  private static function executeSingle($data, array $mapper, array $userData = null): array
  {
    $mapped = [];

    foreach ($mapper as $dest => $value) {
      if (is_string($dest)) {
        $mapped[$dest] = self::transform($data, $dest, $value, $userData ?? []);
      } else {
        $mapped[$value] = self::transform($data, $dest, $value, $userData ?? []);
      }
    }

    return $mapped;
  }

  /**
   * @param mixed $data
   * @param $dest
   * @param $value
   * @param array|null $userData
   *
   * @return mixed
   */
  private static function transform(mixed $data, $dest, $value, array $userData = null): mixed
  {
    if ($value instanceof Closure) {
      return $value($data, $userData ?: []);
    }

    return $data[$value]
      ?? $data[$dest] ?? null;
  }
}
