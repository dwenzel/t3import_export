<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\View;

use CPSIT\ImportExportCore\Domain\Model\TransferSet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders the index templates of the backend modules
 */
class ModuleIndexViewTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function moduleDataProvider(): array
    {
        return [
            'export' => ['export', 'Export', 'Export/Index', 'exportSet'],
            'import' => ['import', 'Import', 'Import/Index', 'importSet'],
        ];
    }

    #[Test]
    #[DataProvider('moduleDataProvider')]
    public function indexShowsLabelAndDescriptionOfSets(
        string $module,
        string $controller,
        string $template,
        string $action
    ): void {
        $setWithLabel = new TransferSet();
        $setWithLabel->setIdentifier('daily');
        $setWithLabel->setLabel('Daily transfer');
        $setWithLabel->setDescription('Runs all daily tasks.');
        $setWithoutLabel = new TransferSet();
        $setWithoutLabel->setIdentifier('weekly');

        $html = $this->render($module, $controller, $template, [
            'tasks' => [],
            'sets' => ['daily' => $setWithLabel, 'weekly' => $setWithoutLabel],
        ]);

        self::assertStringContainsString('<h3>Daily transfer</h3>', $html);
        self::assertStringContainsString('<p>Runs all daily tasks.</p>', $html);
        self::assertStringContainsString('<h3>weekly</h3>', $html);
        self::assertStringNotContainsString('<select', $html);
        $path = '/typo3/module/' . $module . '/' . $controller . '/' . $action . '?token=';
        self::assertSame(2, substr_count($html, $path), 'Each set links to the ' . $action . ' action with a token');
        self::assertStringContainsString('&amp;identifier=daily"', $html);
        self::assertStringContainsString('&amp;identifier=weekly"', $html);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function render(string $module, string $controller, string $template, array $variables): string
    {
        $route = $this->get(Router::class)->getRoute($module);
        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route)
            ->withAttribute(
                'extbase',
                (new ExtbaseRequestParameters())
                    ->setControllerExtensionName('T3importExport')
                    ->setControllerName($controller)
                    ->setControllerActionName('index')
                    ->setPluginName($module)
            );
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: ['EXT:t3import_export/Resources/Private/Templates'],
            partialRootPaths: ['EXT:t3import_export/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:t3import_export/Resources/Private/Layouts'],
            request: new Request($request),
        ));
        $view->assignMultiple($variables);

        return $view->render($template);
    }
}
