<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Command;

use Enqueue\Redis\RedisConnectionFactory;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\ExporterRegistry;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\MqItemWriter;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\ResourceExporterInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class ExportDataCommand extends Command
{
    use ContainerAwareTrait;

    /**
     * @var ExporterRegistry
     */
    private $exporterRegistry;

    /**
     * @param ExporterRegistry $exporterRegistry
     */
    public function __construct(ExporterRegistry $exporterRegistry)
    {
        $this->exporterRegistry = $exporterRegistry;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('sylius:export')
            ->setDescription('Export data to a file.')
            ->setDefinition([
                new InputArgument('exporter', InputArgument::OPTIONAL, 'The exporter to use.'),
                new InputArgument('file', InputArgument::OPTIONAL, 'The target file to export to.'),
                new InputOption('format', null, InputOption::VALUE_OPTIONAL, 'The format of the file to export to'),
                /** @todo Extracting details to show with this option. At the moment it will have no effect */
                new InputOption('details', null, InputOption::VALUE_NONE,
                    'If to return details about skipped/failed rows'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exporter = $input->getArgument('exporter');
        $format = $input->getOption('format');

        if (empty($exporter) || (empty($format))) {
            $message = 'choose an exporter and format';
            $this->listExporters($input, $output, $message);
        }

        /** @var RepositoryInterface $repository */
        $repository = $this->container->get('sylius.repository.' . $exporter);
        $allItems = $repository->findAll();

        /** @var array $idsToExport */
        $idsToExport = $this->prepareExport($allItems);

        $name = ExporterRegistry::buildServiceName('sylius.' . $exporter, $format);

        if (!$this->exporterRegistry->has($name)) {
            $message = sprintf(
                "<error>There is no '%s' exporter.</error>",
                $name
            );

            $this->listExporters($input, $output, $message);
        }

        $file = $input->getArgument('file');

        if (empty($file)) {
            $output->writeln('<info>Please provide a filename</info>');
            exit(0);
        }

        $this->exportToFile($name, $file, $idsToExport);

        $this->finishExport($allItems, $file, $name, $output);
    }

    /**
     * @param array $allItems
     *
     * @return array
     */
    private function prepareExport(array $allItems): array
    {
        $idsToExport = [];
        foreach ($allItems as $item) {
            /** @var ResourceInterface $item */
            $idsToExport[] = $item->getId();
        }

        return $idsToExport;
    }

    /**
     * @param string $name
     * @param string $file
     * @param array $idsToExport
     */
    private function exportToFile(string $name, string $file, array $idsToExport): void
    {
        /** @var ResourceExporterInterface $service */
        $service = $this->exporterRegistry->get($name);
        $service->setExportFile($file);

        $service->export($idsToExport);
    }

    /**
     * @param string $name
     * @param array $idsToExport
     * @param string $exporter
     */
    private function exportToMessageQueue(string $name, array $idsToExport, string $exporter): void
    {
        /** @var ResourceExporterInterface $service */
        $service = $this->exporterRegistry->get($name);
        $service->export($idsToExport);
        $itemsToExport = $service->getExportedData();

        $mqItemWriter = new MqItemWriter(new RedisConnectionFactory());
        $mqItemWriter->initQueue('sylius.export.queue.' . $exporter);
        $mqItemWriter->write(json_decode($itemsToExport));
    }

    /**
     * @param array $allItems
     * @param string $file
     * @param string $name
     * @param OutputInterface $output
     */
    private function finishExport(array $allItems, string $file, string $name, OutputInterface $output): void
    {
        $message = sprintf(
            "<info>Exported %d item(s) to '%s' via the %s exporter</info>",
            count($allItems),
            $file,
            $name
        );
        $output->writeln($message);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $message
     */
    private function listExporters(
        InputInterface $input,
        OutputInterface $output,
        string $message
    ): void {
        $output->writeln($message);
        $output->writeln('<info>Available exporters and formats:</info>');
        $all = array_keys($this->exporterRegistry->all());
        $exporters = [];
        // "sylius.country.csv" is an example of an exporter
        foreach ($all as $exporter) {
            $exporter = explode('.', $exporter);
            // saves the exporter in the exporters array, sets the exporterentity as the first key of the 2d array and the exportertypes each in the second array
            $exporters[$exporter[1]][] = $exporter[2];
        }

        $list = [];

        foreach ($exporters as $exporter => $formats) {
            $list[] = sprintf(
                '%s (formats: %s)',
                $exporter,
                implode(', ', $formats)
            );
        }

        $io = new SymfonyStyle($input, $output);
        $io->listing($list);
        exit(0);
    }
}
