<?php
namespace VKR\ViewMaterializerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VKR\ViewMaterializerBundle\Services\ViewMaterializer;

/**
 * @package VKR\ViewMaterializerBundle\Command
 */
class MaterializeViewsCommand extends ContainerAwareCommand
{
    /**
     * Sets command name, arguments and description
     */
    protected function configure()
    {
        $this
            ->setName('views:materialize')
            ->setDescription('Materialize views to tables')
        ;
    }

    /**
     * Transfers further execution to the viewMaterializer service
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ViewMaterializer $materializerService */
        $materializerService = $this->getContainer()->get('vkr_view_materializer.view_materializer');
        $isSuccessful = $materializerService->materializeViews();
        if ($isSuccessful) {
            $output->writeln('Views materialized successfully');
            return;
        }
        $output->writeln('There was an error while materializing views. See app/logs/view_materializer.log for details');
    }

}
