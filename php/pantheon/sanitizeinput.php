<?php
/**
 * Generate escaped array from outside source.
 */

function sanitizeInput($input) {
    if (is_array($input)) {
        $result = array();

        foreach ( $input as $key => $value ) {
            $cleanKey = htmlspecialchars($key);

            if (is_array($value) || is_object($value)) {
                $result[$cleanKey] = sanitizeInput($value);
            } else {
                $result[$cleanKey] = htmlspecialchars($value);
            }
        }

        return $result;
    }
    
    elseif (is_object($input)) {
        $result = new stdClass();

        foreach ( $input as $key => $value ) {
            $cleanKey = htmlspecialchars($key);

            if (is_array($value) || is_object($value)) {
                $result->$cleanKey = sanitizeInput($value);
            } else {
                $result->$cleanKey = htmlspecialchars($value);
            }
        }

        return $result;
    }
    
    else {
        return htmlspecialchars($input);
    }
}

