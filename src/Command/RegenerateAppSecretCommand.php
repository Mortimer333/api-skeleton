<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:regenerate:secret',
    description: 'Regenerate App Secret',
)]
class RegenerateAppSecretCommand extends BaseCommandAbstract
{
    protected function command(InputInterface $input, OutputInterface $output): void
    {
        $a = '0123456789abcdef';
        $secret = '';
        for ($i = 0; $i < 32; ++$i) {
            $secret .= $a[rand(0, 15)];
        }

        $this->log('New APP_SECRET was generated: ' . $secret);
    }
}
