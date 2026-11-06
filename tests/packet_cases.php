<?php
/**
 * Deterministic packet cases built from the packet class declarations.
 *
 * The goal is not to produce semantically meaningful packets; it is to drive
 * every declared PACK field with a stable, non-null value so two PHP runtimes
 * can be compared byte for byte.
 *
 * Keep this file PHP 5.6/8.x compatible.
 */

function test_packet_skip_classes()
{
    return array(
        // Header-only PACK definitions or intentionally variable packets with
        // no pack() implementation are covered by separate cases later.
        'IS_HCP'    => true,
        'IS_MCI'    => true,
        'IS_NLP'    => true,
        'IS_CON'    => true, // receive-only: pack() only emits the header; unpack() builds A/B
        'IS_CSC'    => true, // receive-only: PACK leaves the CarContOBJ tail out
        'IS_UCO'    => true, // receive-only: PACK leaves the CarContOBJ tail out

        'OutSimPack'=> true,
        'IR_HOS'    => true,
    );
}

function test_packet_size_skip_classes()
{
    return array(
        // Size is supplied by LFS on receive and is uninitialized for send.
        'IS_MSO' => true,
        'IS_III' => true,
        'IS_ACR' => true,
    );
}

function test_packet_classes()
{
    $skip = test_packet_skip_classes();
    $classes = array();

    foreach (get_declared_classes() as $class) {
        if ($class === 'Struct' || !is_subclass_of($class, 'Struct')) {
            continue;
        }
        $reflection = new ReflectionClass($class);
        if ($reflection->isAbstract() || !$reflection->hasConstant('PACK')) {
            continue;
        }
        if (isset($skip[$class])) {
            continue;
        }
        $classes[] = $class;
    }

    sort($classes);
    return $classes;
}

function test_packet_format_value($format, $index)
{
    if ($format === 'x') {
        return null;
    }

    if (is_array($format)) {
        list($type, $elements) = $format;
        $values = array();
        for ($i = 0; $i < (int) $elements; ++$i) {
            if ($type === 'f') {
                $values[$i] = 1.5 + $i;
            } else {
                $values[$i] = 1 + $i;
            }
        }
        return $values;
    }

    $type = substr($format, 0, 1);
    if ($type === 'a' || $type === 'A') {
        return 'test';
    }
    if ($type === 'f') {
        return 1.5 + $index;
    }
    return 1 + $index;
}

/**
 * Build one deterministic packet object. Only public properties are assigned;
 * protected fields (Size, Type, spare bytes, etc.) keep their declared defaults.
 */
function test_packet_make($class)
{
    $packet = new $class();
    $formats = $packet->parsePackFormat();
    $reflection = new ReflectionClass($class);
    $properties = $reflection->getProperties();
    $formatIndex = 0;

    foreach ($properties as $property) {
        if ($property->isStatic()) {
            continue;
        }

        $name = $property->getName();
        if (!isset($formats[$formatIndex])) {
            break;
        }
        $format = $formats[$formatIndex];
        ++$formatIndex;

        if ($format === 'x' || $name === 'Size' || $name === 'Type') {
            continue;
        }
        if (!$property->isPublic()) {
            continue;
        }

        $value = test_packet_format_value($format, $formatIndex);
        if ($value !== null) {
            $packet->$name = $value;
        }
    }

    return $packet;
}

function test_packet_case_name($class)
{
    return 'packet/' . $class . '/default';
}
