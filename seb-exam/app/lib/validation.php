<?php



function validate_string(mixed $value, int $minLen = 1, int $maxLen = 255): string|false {
    if (!is_string($value)) return false;
    $value = trim($value);
    $len = mb_strlen($value, 'UTF-8');
    if ($len < $minLen || $len > $maxLen) return false;
    return $value;
}


function validate_int(mixed $value, int $min = 0, int $max = PHP_INT_MAX): int|false {
    $value = filter_var($value, FILTER_VALIDATE_INT);
    if ($value === false) return false;
    if ($value < $min || $value > $max) return false;
    return $value;
}


function validate_float(mixed $value, float $min = 0.0, float $max = PHP_FLOAT_MAX): float|false {
    $value = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($value === false) return false;
    if ($value < $min || $value > $max) return false;
    return $value;
}


function validate_email(mixed $value): string|false {
    if (!is_string($value)) return false;
    $value = trim($value);
    return filter_var($value, FILTER_VALIDATE_EMAIL) ?: false;
}


function validate_json(string $json): array|false {
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    return $data;
}


function validate_game_json(array $data): array {
    $errors = [];
    if (empty($data['title']) || !is_string($data['title'])) {
        $errors[] = 'Missing or invalid "title" field.';
    }
    if (empty($data['nodes']) || !is_array($data['nodes'])) {
        $errors[] = 'Missing or invalid "nodes" array.';
    } else {
        foreach ($data['nodes'] as $i => $node) {
            if (empty($node['node_key'])) {
                $errors[] = "Node #$i: missing \"node_key\".";
            }
            if (empty($node['title'])) {
                $errors[] = "Node #$i: missing \"title\".";
            }
            if (isset($node['choices']) && !is_array($node['choices'])) {
                $errors[] = "Node #$i: \"choices\" must be an array.";
            }
        }
    }
    return $errors;
}


function validate_enum(mixed $value, array $allowed): mixed {
    return in_array($value, $allowed, true) ? $value : false;
}




