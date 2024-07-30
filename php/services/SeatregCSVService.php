<?php

class ValidationResult {
    public $isValid;
    public $message;

    public function __construct($isValid, $message) {
        $this->isValid = $isValid;
        $this->message = $message;
    }
}

class SeatregCSVService {
    public static function validateCSV($file) {
        $file_extension = self::getExtension($file);
        if ($file_extension != 'csv') {
            return new ValidationResult(false, 'Invalid file extension. Please upload a CSV file.');
        }

        $mime_type = self::getMimeType($file);
        if ($mime_type !== 'text/csv') {
            return new ValidationResult(false, 'Invalid file type. Please upload a CSV file..');
        }

        $file_content = self::getContent($file);
        if (strpos($file_content, ',') === false) {
            return new ValidationResult(false, 'Invalid file type. Please upload a CSV file..');
        }

        // Validate that each row has the correct number of columns
        $file_handle = fopen($file['tmp_name'], 'r');
        $expectedColumnCount = 14;

        while (($row = fgetcsv($file_handle)) !== false) {
            $rowCount = count($row);
            if ($rowCount  != $expectedColumnCount) {
                fclose($file_handle);
                return new ValidationResult(false, 'Each row must contain exactly ' . $expectedColumnCount . ' columns. But got ' . $rowCount . ' columns.');
            }
        }

        fclose($file_handle);

        return new ValidationResult(true, 'ok');
    }
    
    public static function getExtension($file) {
        return pathinfo($file['name'], PATHINFO_EXTENSION);
    }

    public static function getMimeType($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        return $mime_type;
    }

    public static function getContent($file) {
        return file_get_contents($file['tmp_name']);

  

    } 
}