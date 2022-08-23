<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Message\OutOfStockEmail;
use App\Repository\StockRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/stock')]
class StockController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ){}

    #[Route('/', methods: ['GET'], name: 'stock_index')]
    public function index(
        Request $request,
        StockRepository $stockRepository
    ): Response
    {
        $page = $request->query->get('page', 1);
        $productsStock = $stockRepository->paginate($page);

        return $this->render('stock/index.html.twig', [
            'page' => $page,
            'lastPage' => $productsStock->getLastPage(),
            'paginator' => $productsStock
        ]);
    }

    #[Route('/new', methods: ['GET'], name: 'stock_new_form')]
    public function storeForm(Request $request): Response
    {
        return $this->render('stock/new.html.twig');
    }

    #[Route('/', methods: ['POST'], name: 'stock_store')]
    public function store(
        Request $request,
        ManagerRegistry $doctrine,
        MessageBusInterface $bus
    ): Response
    {
        $sku = (string) $request->get('sku');
        $branch = (string) $request->get('branch');
        $stock = $request->get('stock');

        $errors = $this->storeValidation([
            'sku' => $sku,
            'branch' => $branch,
            'stock' => $stock
        ]);

        if($errors->count() || !is_numeric($stock)) {
            return $this->redirectToRoute('stock_new_form');
        }

        $entityManager = $doctrine->getManager();

        $productStock = $entityManager->getRepository(Stock::class)->findOneBy([
            'sku' => $sku,
            'branch' => $branch
        ]);

        if(!$productStock) {
            $productStock = new Stock();

            $productStock->setSku($sku);
            $productStock->setBranch($branch);
        } else if($productStock->getStock() > 0 && $stock <= 0) {
            $bus->dispatch(new OutOfStockEmail(
                $productStock->getSku(),
                $productStock->getBranch(),
                $productStock->getStock()
            ));
        }

        $productStock->setStock($stock);

        $entityManager->persist($productStock);
        $entityManager->flush();
        $entityManager->clear();

        return $this->redirectToRoute('stock_index');
    }

    private function storeValidation(array $postData): ConstraintViolationListInterface
    {
        $constraints = new Collection([
            'sku' => [
                new NotBlank(),
                new Length(min: 1, max: 255)
            ],
            'branch' => [
                new NotBlank(),
                new Length(3)
            ],
            'stock' => [
                new NotBlank(),
            ],
        ]);

        return $this->validator->validate($postData, $constraints);
    }
}