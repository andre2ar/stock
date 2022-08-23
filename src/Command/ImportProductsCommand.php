<?php
namespace App\Command;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from CSV file'
)]
class ImportProductsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StockRepository        $stockRepository
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'File path')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');

        $filesystem = new Filesystem();
        $fileExists = $filesystem->exists($path);

        if(!$fileExists) {
            $output->writeln("File not found at: $path");

            return Command::INVALID;
        }

        $file = fopen($path, "r");
        if(!$file) {
            $output->writeln("File could not be opened: $path");

            return Command::FAILURE;
        }

        fgets($file); //Discard header
        while (($line = fgets($file)) !== false) {
            [$sku, $branch, $stock] = explode(',', $line);

            $productStock = $this->stockRepository->findOneBy([
                'sku' => $sku,
                'branch' => $branch
            ]);

            if(!$productStock) {
                $productStock = new Stock();

                $productStock->setSku($sku);
                $productStock->setBranch($branch);
            }

            $productStock->setStock($stock);

            $this->entityManager->persist($productStock);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        fclose($file);

        $output->writeln("Products from $path were persisted");

        return Command::SUCCESS;
    }
}
