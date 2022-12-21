<?php

/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * User: fanyk
 * Date: 2017/10/28
 * Time: 14:58.
 */
class UploadedFile extends SplFileInfo {
    /**
     * @var int[]
     */
    private static $errors = array(
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    );

    /**
     * 上传文件名.
     *
     * @var string
     */
    private $clientFilename;

    /**
     * //上传的mimeType.
     *
     * @var string
     */
    private $clientMediaType;

    /**
     * @var int
     */
    private $error;

    /**
     * @var null|string
     */
    private $file;

    /**
     * @var bool
     */
    private $moved = false;

    /**
     * @var int
     */
    private $size;

    public function __construct(
        $streamOrFile,
        $size,
        $errorStatus,
        $clientFilename = null,
        $clientMediaType = null
    ) {
        $this->setError($errorStatus);
        $this->setSize($size);
        $this->setClientFilename($clientFilename);
        $this->setClientMediaType($clientMediaType);
        parent::__construct($streamOrFile);
        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    /**
     * Depending on the value set file or stream variable.
     *
     * @param mixed $streamOrFile
     *
     * @throws InvalidArgumentException
     */
    private function setStreamOrFile($streamOrFile) {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } else {
            throw new InvalidArgumentException(
                'Invalid stream or file provided for UploadedFile'
            );
        }
    }

    /**
     * @param int $error
     *
     * @throws InvalidArgumentException
     */
    private function setError($error) {
        if (false === is_int($error)) {
            throw new InvalidArgumentException(
                'Upload file error status must be an integer'
            );
        }

        if (false === in_array($error, self::$errors)) {
            throw new InvalidArgumentException(
                'Invalid error status for UploadedFile'
            );
        }

        $this->error = $error;
    }

    /**
     * @param int $size
     *
     * @throws InvalidArgumentException
     */
    private function setSize($size) {
        if (false === is_int($size)) {
            throw new InvalidArgumentException(
                'Upload file size must be an integer'
            );
        }

        $this->size = $size;
    }

    /**
     * @param mixed $param
     *
     * @return boolean
     */
    private function isStringOrNull($param) {
        return in_array(gettype($param), array('string', 'NULL'));
    }

    /**
     * @param mixed $param
     *
     * @return boolean
     */
    private function isStringNotEmpty($param) {
        return is_string($param) && false === empty($param);
    }

    /**
     * @param string|null $clientFilename
     *
     * @throws InvalidArgumentException
     */
    private function setClientFilename($clientFilename) {
        if (false === $this->isStringOrNull($clientFilename)) {
            throw new InvalidArgumentException(
                'Upload file client filename must be a string or null'
            );
        }

        $this->clientFilename = $clientFilename;
    }

    /**
     * @param string|null $clientMediaType
     *
     * @throws InvalidArgumentException
     */
    private function setClientMediaType($clientMediaType) {
        if (false === $this->isStringOrNull($clientMediaType)) {
            throw new InvalidArgumentException(
                'Upload file client media type must be a string or null'
            );
        }

        $this->clientMediaType = $clientMediaType;
    }

    /**
     * Return true if there is no upload error.
     *
     * @return boolean
     */
    public function isOk() {
        return UPLOAD_ERR_OK === $this->error;
    }

    /**
     * @return boolean
     */
    public function isMoved() {
        return $this->moved;
    }

    /**
     * @throws RuntimeException if is moved or not ok
     */
    private function validateActive() {
        if (false === $this->isOk()) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->isMoved()) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    public function moveTo($targetPath) {
        $this->validateActive();
        if (false === $this->isStringNotEmpty($targetPath)) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        if ($this->file) {
            $this->moved = 'cli' == php_sapi_name()
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);
        }

        if (false === $this->moved) {
            throw new RuntimeException(
                sprintf('Uploaded file could not be moved to %s', $targetPath)
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *  上传错误码
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     *
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError() {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *                     was provided.
     */
    public function getClientFilename() {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType() {
        return $this->clientMediaType;
    }

    /**
     * 是否是图片.
     *
     * @return bool
     *
     * @since version
     */
    public function isImage() {
        return $this->isOk() && in_array($this->clientMediaType, array());
    }

    /**
     * @since version
     */
    public function clientExtension() {
        return pathinfo($this->getClientFilename(), PATHINFO_EXTENSION);
    }

    /**
     *  是否允许指定的后缀
     *
     * @param $ext
     *
     * @return bool
     *
     * @since version
     */
    public function allowExt($ext) {
        return $this->clientExtension() === $ext;
    }

    /**
     * 获取内容.
     *
     * @return bool|string
     *
     * @since version
     */
    public function getContent() {
        return file_get_contents($this->file);
    }

    public static function createFromGlobal() {
        $files = array();
        foreach ($_FILES as $key => $file) {
            $createFiles = static::create($file);
            $files[$key] = $createFiles;
        }

        return $files;
    }

    /**
     *  从数组中创建文件.
     *
     * @param $file
     *
     * @return array|UploadedFile
     */
    private static function create($file) {
        if (is_array($file['tmp_name'])) {
            return static::createArrayFile($file);
        }

        return static::createUploadedFile($file);
    }

    /**
     *  如果传的是多个文件.
     *
     * @param $files
     *
     * @return array
     */
    public static function createArrayFile($files) {
        $data = array();
        foreach (array_keys($files['tmp_name']) as $key) {
            $file = array(
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            );
            $data[$key] = self::createUploadedFile($file);
        }

        return $data;
    }

    private static function createUploadedFile($value) {
        $upfile = new static(
            $value['tmp_name'],
            $value['size'],
            $value['error'],
            $value['name'],
            $value['type']
        );

        return $upfile;
    }
}
