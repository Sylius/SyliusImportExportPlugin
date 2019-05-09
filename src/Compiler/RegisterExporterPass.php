<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\DependencyInjection\Compiler;

use FriendsOfSylius\SyliusImportExportPlugin\Exporter\ExporterRegistry;
use Port\Csv\CsvWriter;
use Port\Spreadsheet\SpreadsheetWriter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterExporterPass implements CompilerPassInterface
{
    private const CLASS_CSV_WRITER = CsvWriter::class;
    private const CLASS_SPREADSHEET_WRITER = SpreadsheetWriter::class;

    /** @var array */
    private $typesAndFormats = [];


    public function process(ContainerBuilder $container)
    {
        $serviceId = 'sylius.exporters_registry';

        if ($container->has($serviceId) == false) {
            return;
        }

        $exportersRegistry = $container->findDefinition($serviceId);

        foreach ($container->findTaggedServiceIds('sylius.exporter') as $id => $attributes) {
            if (!isset($attributes[0]['type'])) {
                throw new \InvalidArgumentException('Tagged exporter ' . $id . ' needs to have a type');
            }
            if (!isset($attributes[0]['format'])) {
                throw new \InvalidArgumentException('Tagged exporter ' . $id . ' needs to have a format');
            }
            $type = $attributes[0]['type'];
            $format = $attributes[0]['format'];

            $name = ExporterRegistry::buildServiceName($type, $format);
            $exportersRegistry->addMethodCall('register', [$name, new Reference($id)]);

            if ($container->getParameter('sylius.exporter.web_ui')) {
                $this->registerTypeAndFormat($type, $format);
            }
        }
    }

    private function registerTypeAndFormat(string $type, string $format): void
    {
        if ('csv' === $format && !class_exists(self::CLASS_CSV_WRITER)) {
            return;
        }

        if ('xlsx' === $format && !class_exists(self::CLASS_SPREADSHEET_WRITER)) {
            return;
        }

        if (!isset($this->typesAndFormats[$type])) {
            $this->typesAndFormats[$type] = [];
        }

        if (!isset($this->typesAndFormats[$type][$format])) {
            $this->typesAndFormats[$type][] = $format;
        }
    }

}
