<?php

namespace Civi\Ci\Report\Generator;

use Civi\Ci\Report\ReportSummary;

class HtmlSummaryReportGenerator implements ReportGeneratorInterface
{
    public function generate(ReportSummary $summary, string $outputPath): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Calidad</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: center; }
        .ok { background: #c8e6c9; }
        .warn { background: #fff9c4; }
        .fail { background: #ffcdd2; }
    </style>
</head>
<body>
    <h1>Resumen de Calidad del Proyecto</h1>

    <table>
        <thead>
            <tr>
                <th>Indicador</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total tests</td>
                <td>{$summary->testReport->getTotalTests()}</td>
            </tr>
            <tr>
                <td>Tests pasados</td>
                <td>{$summary->testReport->getPassedTests()}</td>
            </tr>
            <tr>
                <td>Porcentaje cobertura</td>
                <td>{$summary->coverageReport->getOverallCoveragePercent()}%</td>
            </tr>
            <tr>
                <td>Errores de estilo</td>
                <td>{$summary->lintReport->getTotalErrors()}</td>
            </tr>
            <tr>
                <td>Problemas de calidad</td>
                <td>{$summary->staticAnalysisReport->getTotalIssues()}</td>
            </tr>
            <tr>
                <td>Vulnerabilidades</td>
                <td>{$summary->sastReport->getTotalIssues()}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
HTML;

        file_put_contents($outputPath, $html);
    }
}
