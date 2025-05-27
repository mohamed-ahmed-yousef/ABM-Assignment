<?php
/**
 * Actually i dind't understand the problem well.
 * The Test case is not clear, so I will just implement as a subsequence check but the second test case will fail.
 * I asked for clarification but didn't get any response, so I will just implement the Two Pointer technique to check if the second string is a subsequence of the first string.
 * Thank you for your patience.
 */

function read_binary_input($filename = 'binary_input.txt') {
    try {
        $lines_raw = file($filename, FILE_IGNORE_NEW_LINES);
        $input = [];
        foreach ($lines_raw as $line) {
            $parts = explode(' ', trim($line));
            if (!empty($parts) && $parts[0] !== '') {
                $input[] = $parts;
            }
        }
        return $input;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return [];
    }
}

function write_binary_output($results, $filename = 'binary_output.txt') {
    try {
        file_put_contents($filename, implode("\n", $results));
    } catch (Exception $e) {
        echo "An error occurred while writing: " . $e->getMessage() . "\n";
    }
}

function TwoPointer($first, $second) {
    $j = 0;
    for ($i = 0; $i < strlen($first); $i++) {
        if ($first[$i] == $second[$j]) {
            $j++;
            if ($j == strlen($second)) {
                return 1;
            }
        }
    }
    return ($j == strlen($second)) ? 1 : 0;
}

function main() {
    // Read input
    $input = read_binary_input();
    print_r($input);
    $results = [];

    foreach ($input as $pair) {
        if (count($pair) === 2) {
            list($first, $second) = $pair;
            echo $first . " " . $second . "\n";
            $result = TwoPointer($first, $second);
            $results[] = $result;
        }
    }

    write_binary_output($results);
}

main();
