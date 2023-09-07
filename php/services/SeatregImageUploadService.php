<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregImageUploadService {
    protected $_uploadedFile;
    protected $_uploadsDir;
    protected $_baseName;
    protected $_targetFile;
    protected $_imageDimentsions;
    protected $_imageMaxSize;

    public function __construct($imageDestinationFolder, $imageMaxSize = SEATREG_BACKGROUND_IMAGE_MAX_SIZE) {
        $this->_uploadsDir = SEATREG_TEMP_FOLDER_DIR . $imageDestinationFolder;
        $this->_imageMaxSize = $imageMaxSize;
    }

    protected function imageCheck() {
        $check = getimagesize( $this->_uploadedFile["tmp_name"] );

        if( $check == false ) {
            throw new Exception('File is not an image');
        }
        $this->_imageDimentsions = $check[0] . ',' . $check[1];
    }
    
    protected function fileExistsCheck() {
        if ( file_exists($this->_targetFile) ) {
            throw new Exception('Picture already exists. Please rename image');
        }
    }

    protected function fileSizeCheck() {
        if ( $this->_uploadedFile["size"] > $this->_imageMaxSize ) {
            throw new Exception('Picture is too large');	
        }
    }

    protected function fileFormatCheck() {
        $allowedFileTypes = array('jpg', 'png', 'jpeg', 'gif');
        $imageFileType = pathinfo( $this->_targetFile, PATHINFO_EXTENSION);

        if( !in_array($imageFileType, $allowedFileTypes)  ) {
            throw new Exception('Sorry, only JPG, JPEG, PNG & GIF files are allowed');	
        }
    }

    protected function createFolder() {
        if (!file_exists($this->_uploadsDir)) {
            mkdir($this->_uploadsDir, 0755, true);
        }
    }

    protected function moveFile() {
        move_uploaded_file( $this->_uploadedFile["tmp_name"], $this->_targetFile );
    }

    public function uploadImage($image) {
        $this->_uploadedFile = $image;
        $this->_baseName = basename(sanitize_file_name( $this->_uploadedFile["name"] ));
        $this->_targetFile = $this->_uploadsDir . $this->_baseName;

        $this->imageCheck();
        $this->fileFormatCheck();
        $this->fileExistsCheck();
        $this->fileSizeCheck();
        $this->createFolder();
        $this->moveFile();

        return (object)[
            'text' => "The picture ". $this->_baseName. " has been uploaded.",
            'basename' => $this->_baseName,
            'imageDimentsions' => $this->_imageDimentsions
        ];
    }
}