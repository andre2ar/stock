<?php
namespace App\Tests\Command;

use App\Entity\Stock;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ImportProductsCommandTest extends KernelTestCase
{
    private Application $application;
    private $entityManager;
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->application = new Application($kernel);
    }
    public function testExecute()
    {
        $rightPath = './tests/DataFixture/test-stock-file.csv';
        $command = $this->application->find('app:import-products');

        // Success
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'path' => $rightPath
        ]);

        $commandTester->assertCommandIsSuccessful();

        $productStock = $this->entityManager
            ->getRepository(Stock::class)
            ->findOneBy(['sku' => '000003029003', 'branch' => 'LIM']);

        $this->assertSame(2, $productStock->getStock());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Products from ./tests/DataFixture/test-stock-file.csv were persisted', $output);

        // Fail
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'path' => 'test-stock-file-wrong.csv'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('File not found at: test-stock-file-wrong.csv', $output);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}