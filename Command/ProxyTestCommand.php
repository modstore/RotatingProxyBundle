<?php

namespace Modstore\RotatingProxyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProxyTestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('modstore_rotating_proxy:test')
            ->setDescription('Test a proxy request.')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'The url for the request.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');

        $url = $input->getOption('url') ? $input->getOption('url') : 'http://whatismyip.org';

        $proxyManager = $this->getContainer()->get('modstore_rotating_proxy.manager');
        $proxyManager->setAttempts(1);

        $crawler = $proxyManager->crawlPage($url, 'test', ['https://www.google.com.au/search?q=test&oq=test&sourceid=chrome&ie=UTF-8']);

        $output->writeln(print_r($crawler, true));
    }
}