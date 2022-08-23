<?php
namespace App\Tests\Controller;

use App\Entity\Stock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StockControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private $entityManager;
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testIndex()
    {
        $this->client->request('GET', '/stock/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Stock');
    }

    public function testStoreForm()
    {
        $this->client->request('GET', '/stock/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Add product to stock');
    }

    public function testStoreCreateNew()
    {
        // Success
        $this->client->request('POST', '/stock/', [
            'sku' => '000003029003',
            'branch' => 'GL1',
            'stock' => 1
        ]);

        $productStock = $this->entityManager
            ->getRepository(Stock::class)
            ->findOneBy(['sku' => '000003029003', 'branch' => 'GL1']);

        $this->assertSame(1, $productStock->getStock());

        $this->assertResponseRedirects('/stock/');

        // Fail
        $this->client->request('POST', '/stock/', [
            'sku' => '000003029004',
            'branch' => 'GL5',
            'stock' => 'ss'
        ]);

        $this->assertResponseRedirects('/stock/new');

        // Fail
        $this->client->request('POST', '/stock/', [
            'sku' => '',
            'branch' => '',
            'stock' => ''
        ]);

        $this->assertResponseRedirects('/stock/new');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}