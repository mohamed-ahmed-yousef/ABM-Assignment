<?php
function readFileLines(string $filePath): ?array {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        echo "Error: File not found at '{$filePath}'.\n";
        return null;
    }
    try {
     
        $raw_lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($raw_lines === false) {
            echo "Error reading file '{$filePath}'.\n";
            return null;
        }
        $lines = [];
        foreach ($raw_lines as $line) {
            $trimmed_line = trim($line);
            if (!empty($trimmed_line)) { 
                $lines[] = $trimmed_line;
            }
        }
        return $lines;
    } catch (Exception $e) {
        echo "Error reading file '{$filePath}': " . $e->getMessage() . "\n";
        return null;
    }
}

function writeLinesToFile(string $filePath, array $dataLines): bool {
    try {
        $content = "";
        foreach ($dataLines as $line) {
            $content .= $line . "\n";
        }
        if (file_put_contents($filePath, $content) === false) {
            echo "Error: Could not write to file '{$filePath}'.\n";
            return false;
        }
        return true;
    } catch (Exception $e) {
        echo "An unexpected error occurred while writing to '{$filePath}': " . $e->getMessage() . "\n";
        return false;
    }
}

function processAttendanceLogsFromData(?array $logDataLines): array {
    $processedLogs = [];
    if (empty($logDataLines)) {
        return $processedLogs;
    }

    foreach ($logDataLines as $line) {
        $parts = explode(' ', $line);
        if (count($parts) !== 2) {
            echo "Skipping log line: {$line}\n"; 
            continue;
        }

        $dateStr = $parts[0];
        $timeStr = $parts[1];

        $parts_repr = '[' . implode(', ', array_map(function($p) { return is_string($p) ? "'{$p}'" : $p; }, $parts)) . ']';
        echo $parts_repr . " " . $dateStr . " " . $timeStr . " " . gettype($dateStr) . " " . gettype($timeStr) . "\n";

        if (!(strlen($timeStr) === 5 && $timeStr[2] === ':' &&
              ctype_digit(substr($timeStr, 0, 2)) && ctype_digit(substr($timeStr, 3, 2)))) {
            echo "Skipping log line with invalid time format: {$line}\n"; 
            continue;
        }

        if (!isset($processedLogs[$dateStr])) {
            $processedLogs[$dateStr] = ['arrival' => $timeStr, 'departure' => $timeStr];
        } else {
            if ($timeStr < $processedLogs[$dateStr]['arrival']) {
                $processedLogs[$dateStr]['arrival'] = $timeStr;
            }
            if ($timeStr > $processedLogs[$dateStr]['departure']) {
                $processedLogs[$dateStr]['departure'] = $timeStr;
            }
        }
    }
    return $processedLogs;
}

function generateAttendanceReportFromData(?array $scheduleDataLines, ?array $processedLogs): array {
    $outputLines = [];
    if (empty($scheduleDataLines)) {
        return $outputLines;
    }

    if ($processedLogs === null) { 
        $processedLogs = [];
    }

    foreach ($scheduleDataLines as $line) {
        $parts = explode(',', $line);
        $scheduleDate = trim($parts[0]);

        if (count($parts) === 1) { 
            $outputLines[] = "{$scheduleDate}, weekend"; 
        } elseif (count($parts) === 3) { 
            if (isset($processedLogs[$scheduleDate])) {
                $logEntry = $processedLogs[$scheduleDate];
                $actualArrival = $logEntry['arrival'];
                $actualDeparture = $logEntry['departure'];

                if ($actualArrival === $actualDeparture) { 
                    $outputLines[] = "{$scheduleDate}, {$actualArrival}, n/a"; 
                } else {
                    $outputLines[] = "{$scheduleDate}, {$actualArrival}, {$actualDeparture}";
                }
            } else { 
                $outputLines[] = "{$scheduleDate}, absence"; 
            }
        } else {
            echo "Skipping schedule line: {$line}\n"; 
        }
    }
    return $outputLines;
}

function runAttendanceProcessing(array $scheduleLinesData, array $logLinesData, string $outputFilePathName): ?array {
    $dailyLogs = processAttendanceLogsFromData($logLinesData);
    $reportLines = generateAttendanceReportFromData($scheduleLinesData, $dailyLogs);

    if (!empty($reportLines)) {
        return ['filePath' => $outputFilePathName, 'reportLines' => $reportLines];
    } else {
        echo "No report lines generated. Output file will not be created or will be empty.\n";
        return null;
    }
}

$scheduleFile = "attendance_schedule.txt";
$logFile = "attendance_log.txt";
$outputFile = "attendance_output.txt";


$scheduleLines = readFileLines($scheduleFile);
$logLines = readFileLines($logFile); 

if ($scheduleLines === null) {
    echo "Failed to read schedule file. Aborting.\n";
    exit(1);
}

$logLines = readFileLines($logFile);
if ($logLines === null) {
    echo "Failed to read log file. Aborting.\n";
    exit(1);
}

$result = runAttendanceProcessing($scheduleLines, $logLines, $outputFile);

if ($result !== null) {
    $outputFilePathFromFunc = $result['filePath'];
    $reportLinesFromFunc = $result['reportLines'];

    $success = writeLinesToFile($outputFilePathFromFunc, $reportLinesFromFunc);
    if ($success) {
        echo "Attendance report generated successfully: '{$outputFilePathFromFunc}'\n";
    } else {
        echo "Failed to write the attendance report.\n";
    }
}
