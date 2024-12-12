<?php

namespace DsOpenSearchBundle\Command;

use DsOpenSearchBundle\Manager\IndexManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Contracts\Translation\TranslatorInterface;

class RebuildIndexCommand extends Command
{
    protected static $defaultName = 'dynamic-search:os:rebuild-index-mapping';
    protected static $defaultDescription = 'Rebuild Index Mapping';

    public function __construct(
        protected IndexManager $indexManager,
        protected TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('context', 'c', InputOption::VALUE_REQUIRED, 'Context name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $contextName = $input->getOption('context');

        if (empty($contextName)) {
            $output->writeln('<error>no context definition name given</error>');
            return Command::FAILURE;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $text = $this->translator->trans('ds_index_provider_opensearch.actions.index.rebuild_mapping.confirmation.message', [], 'admin');
        $commandText = sprintf(' <info>%s (y/n)</info> [<comment>%s</comment>]:', $text, 'no');
        $question = new ConfirmationQuestion($commandText, false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        try {
            $this->indexManager->rebuildIndex($contextName);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>Error rebuilding index mapping: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>%s</info>', $this->translator->trans('ds_index_provider_opensearch.actions.index.rebuild_mapping.success', [], 'admin')));

        return Command::SUCCESS;
    }
}
