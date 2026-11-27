<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Resource\File;
use CPSIT\T3importExport\Messaging\Message;
use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\T3importExport\LoggingTrait;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use CPSIT\T3importExport\Resource\ResourceStorageTrait;
use CPSIT\T3importExport\Resource\FileTargetGuardTrait;
use CPSIT\T3importExport\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Resource\FileSourceGuard;
use CPSIT\T3importExport\Resource\FileTargetGuard;
use CPSIT\T3importExport\Resource\UrlSourceLoader;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;

/**
 * Trait GenerateFileTrait
 */
trait GenerateFileTrait
{
    use ResourceStorageTrait, LoggingTrait, FileTargetGuardTrait;

    /**
     * Errors by id
     *
     * @var array
     */
    protected static $errors = [
        1_499_007_587 => ['Empty configuration', 'Configuration must not be empty'],
        1_497_427_302 => ['Missing storage id', 'config[\'storageId\'] must be set'],
        1_497_427_320 => ['Missing target directory ', 'config[\'targetDirectoryPath\` must be set'],
        1_497_427_335 => ['Missing field name', 'config[\'sourceField\'] must be set'],
        1_497_427_336 => ['Missing field name', 'config[\'targetField\'] must be set'],
        1_497_427_346 => ['Invalid storage', 'Could not find storage with id %s given in $config[\'storageId\']'],
        1_497_427_363 => ['Missing directory', 'Directory %s given in $config[\'basePath\'] and $config[\'targetDirectory\'] does not exist.']
    ];

    /**
     * Bytes of the content which are scanned for the root element
     */
    private const CHECKED_WINDOW = 1048576;

    /**
     * Bytes after the checked window which XMLReader reads in addition; it
     * returns a node only after some lookahead
     */
    private const READER_MARGIN = 8192;

    /**
     * Items of the prolog (processing instructions, comments, document types)
     * which are scanned for the root element
     */
    private const MAX_PROLOG_ITEMS = 1000;

    /**
     * Warning: the SVG sanitizer removed the whole content of a file
     */
    public const WARNING_SVG_EMPTY = 1_795_300_502;

    /**
     * Warning: an XML file contains XHTML or SVG elements below another root
     * element or an xml-stylesheet processing instruction
     */
    public const WARNING_ACTIVE_XML = 1_795_300_503;

    /**
     * File types of active content (in any position of the file name), which
     * are not written unless
     * `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets']`
     * is `true`
     */
    private const ACTIVE_CONTENT_PATTERN = '/\.(?:html?|xht(?:ml)?|m?js|shtml?)(?:\..*)?$/i';

    protected FilePathFactory $filePathFactory;

    protected ?FileSourceGuard $fileSourceGuard = null;

    protected ?UrlSourceLoader $urlSourceLoader = null;

    public function setFileSourceGuard(FileSourceGuard $fileSourceGuard): void
    {
        $this->fileSourceGuard = $fileSourceGuard;
    }

    protected function getFileSourceGuard(): FileSourceGuard
    {
        return $this->fileSourceGuard ??= new FileSourceGuard();
    }

    public function setUrlSourceLoader(UrlSourceLoader $urlSourceLoader): void
    {
        $this->urlSourceLoader = $urlSourceLoader;
    }

    protected function getUrlSourceLoader(): UrlSourceLoader
    {
        return $this->urlSourceLoader ??= new UrlSourceLoader(
            GeneralUtility::makeInstance(RequestFactory::class),
            $this->getFileSourceGuard()
        );
    }

    /**
     * injects the file path factory
     * @param FilePathFactory $factory
     * @deprecated
     */
    public function injectFilePathFactory(FilePathFactory $factory){
        $this->filePathFactory = $factory;
    }

    /**
     * Get File object
     * Method must fetch the file and return the correct value for the target field (either a file object or string or null).
     *
     * @param array $configuration
     * @param string $sourceFilePath
     * @return File|string|null
     */
    abstract public function getFile($configuration, $sourceFilePath);

    /**
     * Returns error codes for current component.
     * Must be an array in the form
     * [
     *  <id> => ['errorTitle', 'errorDescription']
     * ]
     * 'errorDescription' may contain placeholder (%s) for arguments.
     * @return array
     */
    public function getErrorCodes()
    {
        return static::$errors;
    }

    /**
     * Copies a source file or URL to a target file.
     *
     * Local files and `EXT:` paths are allowed only below the configured
     * `sourcePath` and local files only in an allowed directory, URLs must be
     * http(s) URLs of an allowed host (see `FileSourceGuard`). The content is
     * written to a
     * temporary file in the target directory, which then replaces the target
     * file. SVG files (by file extension or MIME type of the content) are
     * sanitized. Returns false if the source cannot be read or the target
     * cannot be written.
     *
     * @param string|null $sourcePath the `sourcePath` of the configuration
     * @throws InvalidArgumentException if the source is not allowed
     */
    protected function copySourceFile(string $sourceFilePath, string $absoluteTargetPath, ?string $sourcePath = null): bool
    {
        if ($absoluteTargetPath === '') {
            return false;
        }
        if (FileSourceGuard::isUrl($sourceFilePath)) {
            $this->getFileSourceGuard()->assertUrlAllowed($sourceFilePath);
            $stream = $this->getUrlSourceLoader()->open($sourceFilePath);
            if ($stream === null) {
                return false;
            }
        } else {
            $realPath = $this->getFileSourceGuard()->resolveFile($sourceFilePath, $sourcePath);
            if (!is_file($realPath)) {
                return false;
            }
            $source = @fopen($realPath, 'rb');
            if ($source === false) {
                return false;
            }
            $stream = new Stream($source);
        }

        $temporaryPath = \dirname($absoluteTargetPath) . '/.' . basename($absoluteTargetPath) . '.' . bin2hex(random_bytes(8)) . '.part';
        $handle = false;
        $created = false;
        $renamed = false;
        try {
            $handle = @fopen($temporaryPath, 'xb');
            if ($handle === false) {
                return false;
            }
            $created = true;
            $written = $this->writeStream($stream, $handle);
            $closed = fclose($handle);
            $handle = false;
            if (!$closed || !$written) {
                return false;
            }
            if ($this->isSvgFile($absoluteTargetPath, $temporaryPath)) {
                $this->sanitizeSvgFile($temporaryPath, $absoluteTargetPath);
            } else {
                $this->checkXmlContent($temporaryPath, $absoluteTargetPath);
            }
            $renamed = @rename($temporaryPath, $absoluteTargetPath);

            return $renamed;
        } finally {
            if (\is_resource($handle)) {
                fclose($handle);
            }
            if ($created && !$renamed) {
                @unlink($temporaryPath);
            }
            $stream->close();
        }
    }

    /**
     * Checks a source without reading it: a URL must be an http(s) URL of an
     * allowed host, a local file or `EXT:` path must be allowed by
     * `FileSourceGuard::resolveFile()`.
     *
     * @param string|null $sourcePath the `sourcePath` of the configuration
     * @throws InvalidArgumentException if the source is not allowed
     */
    protected function assertSourceAllowed(string $sourceFilePath, ?string $sourcePath = null): void
    {
        if (FileSourceGuard::isUrl($sourceFilePath)) {
            $this->getFileSourceGuard()->assertUrlAllowed($sourceFilePath);

            return;
        }
        $this->getFileSourceGuard()->resolveFile($sourceFilePath, $sourcePath);
    }

    /**
     * Sanitizes an SVG file with the SVG sanitizer of TYPO3; logs a warning
     * if the sanitizer leaves an empty file, e.g. for content that is no
     * valid SVG. If the sanitizer refuses the content (an exception for
     * content without svg element), the file is emptied; if it cannot be
     * emptied, the exception is thrown again.
     */
    protected function sanitizeSvgFile(string $path, string $targetPath): void
    {
        clearstatcache(true, $path);
        $sizeBefore = (int)@filesize($path);
        try {
            GeneralUtility::makeInstance(SvgSanitizer::class)->sanitizeFile($path);
        } catch (\LogicException $exception) {
            // the sanitizer refuses content without svg element: nothing of it is kept
            if (@file_put_contents($path, '') !== 0) {
                // the file is removed by the caller
                throw $exception;
            }
        }
        clearstatcache(true, $path);
        if ($sizeBefore > 0 && (int)@filesize($path) === 0) {
            $this->logMessage(
                'SVG file empty',
                \sprintf('The SVG sanitizer removed the whole content of "%s".', basename($targetPath)),
                Message::WARNING,
                self::WARNING_SVG_EMPTY
            );
        }
    }

    /**
     * Whether a file is sanitized as SVG: the file name has the extension
     * svg (or svgz) in any position, e.g. `image.svg.txt`, or the root element of the content is an `svg` element (also
     * with a namespace prefix): the first element after a byte order mark,
     * whitespace, the XML declaration, processing instructions, comments and
     * the document type, within the first megabyte. The root element is read
     * with XMLReader, which reads 8 KB more (it returns an element only after
     * some lookahead); for content XMLReader cannot read, a simple scan is
     * used, which treats a document type with an internal subset as unknown.
     * If the root element is unknown (e.g. an unclosed comment), the MIME
     * type image/svg+xml decides. Markup with another root element, e.g.
     * HTML or RSS with an inline `svg` element, is not sanitized. The
     * detection is a best effort.
     */
    protected function isSvgFile(string $targetPath, string $contentPath): bool
    {
        // web servers may serve a file by any of its extensions
        if (preg_match('/\.svgz?(?:\..*)?$/i', basename($targetPath)) !== 0) {
            return true;
        }
        $content = @file_get_contents($contentPath, false, null, 0, self::CHECKED_WINDOW + self::READER_MARGIN);
        if (!\is_string($content)) {
            return false;
        }
        $window = substr($content, 0, self::CHECKED_WINDOW);

        return self::readRootElementIsSvg($content)
            ?? self::hasSvgRootElement($window)
            ?? (new \finfo(FILEINFO_MIME_TYPE))->buffer($window) === 'image/svg+xml';
    }

    /**
     * Logs a warning if XML content (within the first megabyte) contains
     * elements of the XHTML or SVG namespace or an xml-stylesheet processing
     * instruction. Such content is written unchanged; browsers can run
     * scripts in it.
     */
    protected function checkXmlContent(string $contentPath, string $targetPath): void
    {
        $content = @file_get_contents($contentPath, false, null, 0, self::CHECKED_WINDOW + self::READER_MARGIN);
        if (!\is_string($content)) {
            return;
        }
        $window = substr($content, 0, self::CHECKED_WINDOW);
        if (\strlen($window) >= self::CHECKED_WINDOW && self::isRootElementBeyondWindow($window)) {
            $this->logMessage(
                'XML file not checked',
                \sprintf(
                    'The first megabyte of "%s" contains no root element (e.g. a long comment or document type); its content is not checked and not sanitized.',
                    basename($targetPath)
                ),
                Message::WARNING,
                self::WARNING_ACTIVE_XML
            );

            return;
        }
        // XMLReader reads the window and the margin
        if (!self::hasXmlActiveContent($content)) {
            return;
        }
        $this->logMessage(
            'XML file with active content',
            \sprintf(
                'The file "%s" contains XHTML or SVG elements or an xml-stylesheet processing instruction; it is not sanitized.',
                basename($targetPath)
            ),
            Message::WARNING,
            self::WARNING_ACTIVE_XML
        );
    }

    /**
     * Whether the root element of markup content does not start within the
     * checked window: the window holds only the prolog (byte order mark,
     * whitespace, XML declaration, processing instructions, comments and the
     * document type, at most `MAX_PROLOG_ITEMS` items), or it ends inside the
     * start tag of the root element, e.g. after a long comment.
     */
    private static function isRootElementBeyondWindow(string $content): bool
    {
        $start = str_starts_with($content, "\xEF\xBB\xBF") ? 3 : 0;
        $start += strspn($content, " \t\n\r\0\x0B", $start);
        if ($start >= \strlen($content)) {
            // byte order mark and whitespace fill the window
            return true;
        }
        if ($content[$start] !== '<') {
            return false;
        }
        $offset = self::skipProlog($content, true);
        if ($offset === null || $offset >= \strlen($content)) {
            return true;
        }

        // a complete start tag of the root element (attribute values may contain
        // ">"); fails safe: an error of preg_match() (false) counts as beyond
        // the window, so the warning is logged
        return preg_match(
            '/\G<[^\s>\/]++(?:\s++[^\s=>\/]++(?:\s*+=\s*+(?:"[^"]*+"|\'[^\']*+\'|[^\s"\'>]++))?)*+\s*+\/?>/',
            $content,
            $matches,
            0,
            $offset
        ) !== 1;
    }

    /**
     * Skips the prolog of markup content: byte order mark, whitespace, XML
     * declaration, processing instructions, comments and the document type.
     * Scans with an offset, without copies of the content.
     *
     * @param bool $allowInternalSubset whether a document type may have an internal subset
     * @return int|null offset of the first character after the prolog; null
     *                  if an item does not end within the content, a document
     *                  type is not allowed or there are more than
     *                  `MAX_PROLOG_ITEMS` items
     */
    private static function skipProlog(string $content, bool $allowInternalSubset): ?int
    {
        $whitespace = " \t\n\r\0\x0B";
        $length = \strlen($content);
        $offset = str_starts_with($content, "\xEF\xBB\xBF") ? 3 : 0;
        $offset += strspn($content, $whitespace, $offset);
        $items = 0;
        while ($offset < $length) {
            if (substr_compare($content, '<?', $offset, 2) === 0) {
                $end = strpos($content, '?>', $offset + 2);
                $end = $end === false ? false : $end + 2;
            } elseif (substr_compare($content, '<!--', $offset, 4) === 0) {
                $end = strpos($content, '-->', $offset + 4);
                $end = $end === false ? false : $end + 3;
            } elseif (substr_compare($content, '<!DOCTYPE', $offset, 9, true) === 0) {
                $pattern = $allowInternalSubset
                    ? '/\G<!DOCTYPE[^\[>]*+(?:\[[^\]]*+\]\s*+)?>/i'
                    : '/\G<!DOCTYPE[^\[>]*+>/i';
                $end = preg_match($pattern, $content, $matches, 0, $offset) === 1 ? $offset + \strlen($matches[0]) : false;
            } else {
                return $offset;
            }
            if ($end === false || ++$items > self::MAX_PROLOG_ITEMS) {
                return null;
            }
            $offset = $end + strspn($content, $whitespace, $end);
        }

        return $offset;
    }

    /**
     * Read with XMLReader: no network access, no DTD loading, no entity
     * substitution. Content which is no XML has no XML active content.
     */
    private static function hasXmlActiveContent(string $content): bool
    {
        $previous = libxml_use_internal_errors(true);
        $reader = null;
        try {
            $reader = \XMLReader::XML($content, null, LIBXML_NONET);
            if (!$reader instanceof \XMLReader) {
                return false;
            }
            while ($reader->read()) {
                if (
                    $reader->nodeType === \XMLReader::PI
                    && strtolower($reader->name) === 'xml-stylesheet'
                ) {
                    return true;
                }
                if (
                    $reader->nodeType === \XMLReader::ELEMENT
                    && \in_array($reader->namespaceURI, ['http://www.w3.org/1999/xhtml', 'http://www.w3.org/2000/svg'], true)
                ) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable) {
            return false;
        } finally {
            $reader?->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Whether the root element is `svg`, read with XMLReader: no network
     * access, no DTD loading, no entity substitution.
     *
     * @return bool|null null if XMLReader finds no root element, e.g. the
     *                   content is no XML
     */
    private static function readRootElementIsSvg(string $content): ?bool
    {
        $previous = libxml_use_internal_errors(true);
        $reader = null;
        try {
            $reader = \XMLReader::XML($content, null, LIBXML_NONET);
            if (!$reader instanceof \XMLReader) {
                return null;
            }
            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT) {
                    return strtolower($reader->localName) === 'svg';
                }
            }

            return null;
        } catch (\Throwable) {
            return null;
        } finally {
            $reader?->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * The root element without an XML parser, for content XMLReader cannot
     * read. A document type with an internal subset makes the root element
     * unknown.
     *
     * @return bool|null null if the root element is unknown
     */
    private static function hasSvgRootElement(string $content): ?bool
    {
        $offset = self::skipProlog($content, false);
        if ($offset === null || preg_match('/\G<[a-z_]/i', $content, $matches, 0, $offset) !== 1) {
            return null;
        }

        // the start tag may end with the checked window
        return preg_match('/\G<(?:[a-z0-9_.-]+:)?svg(?:[\s>\/]|$)/i', $content, $matches, 0, $offset) === 1;
    }

    /**
     * File name of a source file; for http(s) URLs the last segment of the
     * path, without query and fragment
     */
    protected function getSourceFileName(string $sourceFilePath): string
    {
        if (preg_match('#^https?://#i', $sourceFilePath) === 1) {
            $sourceFilePath = (string)parse_url($sourceFilePath, PHP_URL_PATH);
        }

        return PathUtility::basename($sourceFilePath);
    }

    /**
     * @param resource $handle
     * @return bool false if the stream cannot be read or the file cannot be written
     */
    private function writeStream(StreamInterface $stream, $handle): bool
    {
        try {
            while (!$stream->eof()) {
                $chunk = $stream->read(8192);
                if (fwrite($handle, $chunk) !== \strlen($chunk)) {
                    return false;
                }
            }
        } catch (\RuntimeException) {
            return false;
        }

        return true;
    }

    /**
     * Process record
     * Generates one or multiple file objects, adds them to the repository and the record field
     *
     * @param array $configuration
     * @param array $record
     * @return bool
     */
    public function process($configuration, &$record)
    {
        $separator = ',';
        if (isset($configuration['separator'])) {
            $separator = $configuration['separator'];
        }
        $sourceField = $configuration['sourceField'];
        $targetField = $configuration['targetField'];

        $filePaths = GeneralUtility::trimExplode($separator, $record[$sourceField], true);

        // Prefix all files with the source path
        if (isset($configuration['sourcePath'])) {
            $sourcePath = (string)$configuration['sourcePath'];
            $filePaths = array_map(
                fn(string $filePath): string => $this->getFileSourceGuard()->buildLocation($sourcePath, $filePath),
                $filePaths
            );
        }

        if ($configuration['multipleRows']) {
            $fieldValue = [];

            foreach ($filePaths as $filePath) {
                $singleValue = $this->getFile($configuration, $filePath);
                $fieldValue[] = $singleValue;
            }
        } else {
            $fieldValue = $this->getFile($configuration, $filePaths[0]);
        }

        $record[$targetField] = $fieldValue;

        return true;
    }

    /**
     * Check configuration
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (empty($configuration)) {
            $this->logError(1_499_007_587);
            return false;
        }
        if (!isset($configuration['targetDirectoryPath'])) {
            $this->logError(1_497_427_320);
            return false;
        }

        if (!isset($configuration['sourceField'])) {
            $this->logError(1_497_427_335);
            return false;
        }
        if (!isset($configuration['targetField'])) {
            $this->logError(1_497_427_336);
            return false;
        }

        if (!isset($configuration['storageId'])) {
            $this->logError(1_497_427_302);
            return false;
        }

        $this->initializeStorage($configuration);

        if (!$this->resourceStorage instanceof ResourceStorage) {
            $this->logError(1_497_427_346, [$configuration['storageId']]);
            return false;
        }

        if (!$this->resourceStorage->hasFolder($configuration['targetDirectoryPath'])) {
            $storageConfiguration = $this->resourceStorage->getConfiguration();
            $this->logError(1_497_427_363, [$storageConfiguration['basePath'] . ltrim((string) $configuration['targetDirectoryPath'], '/\\')]);

            return false;
        }

        return true;
    }

    /**
     * The file name of the target is the one of the source. It must not
     * match the fileDenyPattern of TYPO3 (the file is copied without FAL).
     * The last file extension must consist of letters and digits; compressed
     * SVG files (svgz, in any position of the name) are refused, and active content (`html`, `htm`,
     * `xhtml`, `xht`, `shtml`, `shtm`, `js`, `mjs` in any position of the
     * name, see `ACTIVE_CONTENT_PATTERN`) unless `allowActiveContentTargets`
     * is `true`.
     *
     * @param array $configuration
     * @param string $sourcePath
     * @return string
     * @throws InvalidArgumentException if the file name is not allowed
     */
    protected function getTargetPath($configuration, $sourcePath)
    {
        $fileName = $this->getSourceFileName((string)$sourcePath);
        $this->getFileTargetGuard()->assertFileName($fileName);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (preg_match('/^[A-Za-z0-9]+$/', $extension) !== 1) {
            throw new InvalidArgumentException(
                \sprintf('The file "%s" cannot be written: the last file extension must consist of letters and digits.', $fileName),
                FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED
            );
        }
        if (preg_match('/\.svgz(?:\..*)?$/i', $fileName) !== 0) {
            throw new InvalidArgumentException(
                \sprintf('The file "%s" cannot be written: compressed SVG files (svgz) are not allowed.', $fileName),
                FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED
            );
        }
        if (
            preg_match(self::ACTIVE_CONTENT_PATTERN, $fileName) !== 0
            && ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets'] ?? false) !== true
        ) {
            throw new InvalidArgumentException(
                \sprintf(
                    'The file "%s" cannot be written: files of the types html, htm, xhtml, xht, shtml, shtm, js and mjs (in any position of the name) are only written if $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'t3import_export\'][\'allowActiveContentTargets\'] is true.',
                    $fileName
                ),
                FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED
            );
        }
        $storageConfiguration = $this->resourceStorage->getConfiguration();

        $targetDirectoryPath = $this->filePathFactory->createFromParts(
            [
                $storageConfiguration['basePath'],
                $configuration['targetDirectoryPath'],
            ]
        );

        return $targetDirectoryPath . $fileName;
    }
}
