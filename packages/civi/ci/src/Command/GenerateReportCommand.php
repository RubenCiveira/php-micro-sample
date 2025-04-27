<?php

namespace Civi\Ci\Command;

use Civi\Ci\Report\Generator\AdocDetailedReportGenerator;
use Civi\Ci\Report\Generator\AdocSummaryReportGenerator;
use Civi\Ci\Report\Generator\HtmlDetailedReportGenerator;
use Civi\Ci\Report\Generator\HtmlSummaryReportGenerator;
use Civi\Ci\Report\Generator\ReportGeneratorInterface;
use Civi\Ci\Report\Parser\CoverageReportParser;
use Civi\Ci\Report\Parser\LintReportParser;
use Civi\Ci\Report\Parser\SastReportParser;
use Civi\Ci\Report\Parser\StaticAnalysisReportParser;
use Civi\Ci\Report\Parser\TestReportParser;
use Civi\Ci\Report\ReportAnalyzer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateReportCommand extends Command
{
    protected static $defaultName = 'report:generate';

    private ReportAnalyzer $reportAnalyzer;
    private string $reportDir;

    public function __construct()
    {
        parent::__construct();
        // ReportAnalyzer $reportAnalyzer, string $reportDir
        $this->reportAnalyzer = new ReportAnalyzer(
            new TestReportParser(),
            new CoverageReportParser(),
            new StaticAnalysisReportParser(),
            new LintReportParser(),
            new SastReportParser()
        );
        $this->reportDir = './build/reports/';
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Genera un reporte de calidad del proyecto.')
            ->addArgument('output-filename', InputArgument::REQUIRED, 'Ruta del fichero de salida')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Tipo de reporte (summary, detailed)', 'summary');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputFilename = $input->getArgument('output-filename');
        // Normalizamos la extensión a minúsculas por si acaso
        $extension = strtolower( pathinfo($outputFilename, PATHINFO_EXTENSION) );

        $type = $input->getOption('type');

        $summary = $this->reportAnalyzer->analyze($this->reportDir);

        $generator = $this->resolveGenerator("{$extension}-{$type}");

        if (!$generator) {
            $output->writeln('<error>Tipo de reporte no válido.</error>');
            return Command::FAILURE;
        }

        $generator->generate($summary, $outputFilename);

        $output->writeln("<info>Reporte generado exitosamente en {$outputFilename}.</info>");

        return Command::SUCCESS;
    }

    private function resolveGenerator(string $type): ?ReportGeneratorInterface
    {
        return match ($type) {
            'html-summary' => new HtmlSummaryReportGenerator(),
            'html-detailed' => new HtmlDetailedReportGenerator(),
            'adoc-summary' => new AdocSummaryReportGenerator(),
            'adoc-detailed' => new AdocDetailedReportGenerator(),
            default => null,
        };
    }
}
