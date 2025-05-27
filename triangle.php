<?php

// Read & write operation
function read_strings_from_file(string $file_path): ?array
{
    if (!file_exists($file_path)) {
        echo "Error: Input file '{$file_path}' does not exist.\n";
        return null;
    }
    try {
        $lines_raw = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines_raw === false) {
            throw new Exception("Failed to read file.");
        }
        $strings = array_map('trim', $lines_raw);
        return array_filter($strings, function ($s) {
            return $s !== '';
        });
    } catch (Exception $e) {
        echo "Error: Could not read input file '{$file_path}'. " . $e->getMessage() . "\n";
        return null;
    }
}

function write_lines_to_file(string $file_path, array $lines): bool
{
    try {
        $file_handle = fopen($file_path, 'w');
        if ($file_handle === false) {
            throw new Exception("Failed to open file for writing.");
        }
        foreach ($lines as $line) {
            fwrite($file_handle, $line . "\n");
        }
        fclose($file_handle);
        return true;
    } catch (Exception $e) {
        echo "Error: Could not write to output file '{$file_path}'. " . $e->getMessage() . "\n";
        return false;
    }
}

// Empty triangle
function calculate_dynamic_top_triangle_lines(array $strings_list): int
{
    $start = 2;
    while (true) {
        $flag = true;
        for ($i = 0; $i < count($strings_list); $i++) {
            if (strlen($strings_list[$i]) >= $start + $i) {
                $start++;
                $flag = false;
                break;
            }
        }
        if ($flag) {
            break;
        }
    }
    return $start;
}

// Frame dimensions
function calculate_frame_dimensions(
    array $strings,
    int $num_strings,
    int $first_text_line_side_frame_width
): array {
    $max_string_length = 0;
    if ($num_strings > 0) {
        foreach ($strings as $s) {
            $max_string_length = max($max_string_length, strlen($s));
        }
    }

    $last_line_side_frame_width = $first_text_line_side_frame_width + ($num_strings - 1);
    $overall_max_width = (2 * $last_line_side_frame_width) + $max_string_length + 2;
    return [
        'max_string_length' => $max_string_length,
        'overall_max_width' => $overall_max_width,
    ];
}

// Frame generator
function generate_framed_lines(
    array $strings,
    int $num_strings,
    int $max_string_length,
    int $overall_max_width,
    string $frame_char,
    int $N_empty_lines,
): array {
    $output_lines = [];

    $x = 0;
    for ($k = 1; $k <= $N_empty_lines; $k++) {
        $num_fchars_on_line = (2 * $k - 1);
        $current_line_shape = str_repeat($frame_char, $num_fchars_on_line);

        $padding_total = $overall_max_width - strlen($current_line_shape);
        $padding_left = floor($padding_total / 2);
        $x = strlen($current_line_shape);
        $output_lines[] = str_repeat(' ', $padding_left) . $current_line_shape;
    }

    $y = 0;
    $total = 0;
    for ($i = 0; $i < $num_strings; $i++) {
        $current_string_content = $strings[$i];
        $num_side_fchars = floor(($x - $max_string_length) / 2) + $i;
        echo $num_side_fchars . "\n";
        echo ($x - $max_string_length) . " test\n";
        $left_fchars_block = str_repeat($frame_char, (($x - $max_string_length) % 2 == 0) ? $num_side_fchars : $num_side_fchars + 1);
        $right_fchars_block = str_repeat($frame_char, $num_side_fchars);

        $string_padding_needed = $max_string_length - strlen($current_string_content);
        $padded_text = $current_string_content . str_repeat(' ', max(0, $string_padding_needed));

        $line_content_block = "{$left_fchars_block} {$padded_text} {$right_fchars_block}";

        $block_actual_width = strlen($line_content_block);
        $padding_total = $overall_max_width - $block_actual_width;
        $padding_left = floor($padding_total / 2);
        $y = $padding_left;
        $total = strlen($line_content_block);
        $output_lines[] = str_repeat(' ', $padding_left) . $line_content_block;
    }


    if ($overall_max_width > 0) {
        $output_lines[] = str_repeat(' ', max(0, $y - 1)) . str_repeat($frame_char, $total + 2);
    } elseif ($num_strings > 0 || $N_empty_lines > 0) {
        $output_lines[] = $frame_char;
    }

    return $output_lines;
}

function process_text_file_to_triangular_frame(
    string $input_file,
    string $output_file_path_for_return,
    string $frame_char = '*'
): ?array {
    if (strlen($frame_char) != 1) {
        echo "Error: Frame character '{$frame_char}' must be a single character.\n";
        return null;
    }

    $strings = read_strings_from_file($input_file);
    if ($strings === null) {
        return null;
    }

    $num_strings = count($strings);
    if ($num_strings == 0) {
        echo "No processable strings found in '{$input_file}'. Frame generation aborted.\n";
        return [$output_file_path_for_return, ["Warning: No strings to frame, so no output generated."]];
    }

    $N_empty_lines = calculate_dynamic_top_triangle_lines($strings);


    $first_text_line_side_frame_width = $N_empty_lines + 1;

    $dimensions = calculate_frame_dimensions($strings, $num_strings, $first_text_line_side_frame_width);
    $max_string_length = $dimensions['max_string_length'];
    $overall_max_width = $dimensions['overall_max_width'];

    if ($overall_max_width <= 0) {
        echo "Warning: Calculated overall_max_width ({$overall_max_width}) is not positive. Output may be incorrect.\n";
        $overall_max_width = max(1, $overall_max_width);
    }

    $framed_lines = generate_framed_lines(
        $strings,
        $num_strings,
        $max_string_length,
        $overall_max_width,
        $frame_char,
        $N_empty_lines,
    );

    return [$output_file_path_for_return, $framed_lines];
}

$input_filename = 'triangle_input.txt';
$output_filename = 'triangle_output.txt';
$frame_char_to_use = '*';

if (!file_exists($input_filename)) {
    file_put_contents($input_filename, "Hello\nWorld\nPHP");
    if (!file_exists($input_filename)) {
        echo "Error: Could not create dummy input file '{$input_filename}'. Exiting.\n";
        exit(1);
    }
}

$processing_result = process_text_file_to_triangular_frame(
    $input_filename,
    $output_filename,
    $frame_char_to_use
);

if ($processing_result !== null) {
    list($output_file_path_from_func, $framed_lines_from_func) = $processing_result;
    write_lines_to_file($output_file_path_from_func, $framed_lines_from_func);
}
