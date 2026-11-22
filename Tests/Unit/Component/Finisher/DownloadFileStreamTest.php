<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\T3importExport\Component\Finisher\DownloadFileStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DownloadFileStream::class)]
class DownloadFileStreamTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = (string)tempnam(sys_get_temp_dir(), 't3ie_download_');
        file_put_contents($this->file, 'file content');
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    /**
     * Subject which records headers and throws instead of ending the process
     */
    private function createSubject(): DownloadFileStream
    {
        return new class () extends DownloadFileStream {
            public array $headers = [];

            protected function sendHeader(string $header): void
            {
                $this->headers[] = $header;
            }

            protected function terminate(): never
            {
                throw new \RuntimeException('terminated', 1763800000);
            }
        };
    }

    public function testProcessReturnsFalseIfResultIsNoTaskResult(): void
    {
        $subject = $this->createSubject();
        $records = [];
        $result = ['foo'];

        self::assertFalse($subject->process([], $records, $result));
        self::assertSame([], $subject->headers);
    }

    public function testProcessReturnsFalseIfFileDoesNotExist(): void
    {
        $subject = $this->createSubject();
        $records = [];
        $result = new TaskResult();
        $result->setInfo($this->file . '.missing');

        self::assertFalse($subject->process([], $records, $result));
        self::assertSame([], $subject->headers);
    }

    public static function configurationDataProvider(): array
    {
        return [
            'type and file name' => [
                ['type' => 'text/csv', 'filename' => 'export', 'fileExt' => 'csv'],
                'text/csv',
                'export.csv',
            ],
            'extension with leading dot' => [
                ['filename' => 'export', 'fileExt' => '.xml'],
                'application/octet-stream',
                'export.xml',
            ],
            'default type and extension' => [
                ['filename' => 'export'],
                'application/octet-stream',
                'export.dat',
            ],
        ];
    }

    #[DataProvider('configurationDataProvider')]
    public function testProcessSendsFileWithHeaders(array $configuration, string $type, string $fileName): void
    {
        $subject = $this->createSubject();
        $records = [];
        $result = new TaskResult();
        $result->setInfo($this->file);

        $this->expectOutputString('file content');
        try {
            $subject->process($configuration, $records, $result);
            self::fail('The request was not terminated');
        } catch (\RuntimeException $exception) {
            self::assertSame(1763800000, $exception->getCode());
        }

        self::assertSame(
            [
                'Content-Description: File Transfer',
                'Content-Type: ' . $type,
                'Content-Disposition: attachment; filename="' . $fileName . '"',
                'Expires: 0',
                'Cache-Control: must-revalidate',
                'Pragma: public',
                'Content-Length: 12',
            ],
            $subject->headers
        );
    }

    public function testProcessUsesGeneratedFileNameByDefault(): void
    {
        $subject = $this->createSubject();
        $records = [];
        $result = new TaskResult();
        $result->setInfo($this->file);

        $this->expectOutputString('file content');
        try {
            $subject->process([], $records, $result);
        } catch (\RuntimeException) {
        }

        self::assertMatchesRegularExpression(
            '/^Content-Disposition: attachment; filename="file_\d+\.dat"$/',
            $subject->headers[2]
        );
    }
}
