<?php
namespace App\Message;

class OutOfStockEmail
{
    public function __construct(
        public string $sku,
        public string $branch,
        public int $stock
    ){}
}