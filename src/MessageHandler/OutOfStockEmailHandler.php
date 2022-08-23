<?php
namespace App\MessageHandler;

use App\Message\OutOfStockEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class OutOfStockEmailHandler
{
    public function __construct(
        public MailerInterface $mailer,
        public ContainerBagInterface $params
    ){}

    public function __invoke(OutOfStockEmail $message)
    {
        $sku = $message->sku;
        $branch = $message->branch;
        $stock = $message->stock;

        $email = (new Email())
            ->priority(Email::PRIORITY_HIGH)
            ->subject("$sku is out of stock at $branch")
            ->html("<p>$sku is out of stock at $branch. Current stock: $stock</p>");

        $this->mailer->send($email);
    }
}