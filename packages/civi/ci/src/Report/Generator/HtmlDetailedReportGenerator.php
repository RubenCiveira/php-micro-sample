<?php

namespace Civi\Ci\Report\Generator;

use Civi\Ci\Report\ReportSummary;
use Civi\Ci\Report\Result\TestCaseResult;
use Civi\Ci\Report\Result\CoverageFileResult;
use Civi\Ci\Report\Result\StaticAnalysisIssue;
use Civi\Ci\Report\Result\LintError;
use Civi\Ci\Report\Result\SastIssue;

class HtmlDetailedReportGenerator implements ReportGeneratorInterface
{
    public function generate(ReportSummary $summary, string $outputPath): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Informe Detallado de Calidad</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: left; }
        .ok { background: #c8e6c9; }
        .warn { background: #fff9c4; }
        .fail { background: #ffcdd2; }
    </style>
</head>
<body>

    <h1>Informe Detallado de Calidad</h1>

    <h2>Resumen General</h2>
    <ul>
        <li><strong>Tests totales:</strong> {$summary->testReport->getTotalTests()}</li>
        <li><strong>Tests pasados:</strong> {$summary->testReport->getPassedTests()}</li>
        <li><strong>Porcentaje cobertura:</strong> {$summary->coverageReport->getOverallCoveragePercent()}%</li>
        <li><strong>Errores de estilo:</strong> {$summary->lintReport->getTotalErrors()}</li>
        <li><strong>Avisos de estilo:</strong> {$summary->lintReport->getTotalWarnings()}</li>
        <li><strong>Problemas de calidad:</strong> {$summary->staticAnalysisReport->getTotalIssues()}</li>
        <li><strong>Vulnerabilidades:</strong> {$summary->sastReport->getTotalIssues()}</li>
    </ul>

    <h2>Detalles de Tests</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Clase</th>
                <th>Fichero</th>
                <th>Tiempo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($summary->testReport->testCases as $test) {
            /** @var TestCaseResult $test */
            $status = $test->skipped ? 'SKIPPED' : ($test->passed ? 'PASSED' : 'FAILED');
            $statusClass = $test->passed ? 'ok' : ($test->skipped ? 'warn' : 'fail');

            $html .= <<<HTML
<tr class="{$statusClass}">
    <td>{$test->name}</td>
    <td>{$test->class}</td>
    <td>{$test->file}</td>
    <td>{$test->time}</td>
    <td>{$status}</td>
</tr>
HTML;
        }

        $html .= <<<HTML
        </tbody>
    </table>

    <h2>Detalles de Cobertura</h2>
    <table>
        <thead>
            <tr>
                <th>Fichero</th>
                <th>Porcentaje de cobertura</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($summary->coverageReport->files as $file) {
            /** @var CoverageFileResult $file */
            $html .= <<<HTML
<tr>
    <td>{$file->file}</td>
    <td>{$file->getCoveragePercent()}%</td>
</tr>
HTML;
        }

        $html .= <<<HTML
        </tbody>
    </table>

    <h2>Errores de Estilo (Lint)</h2>
    <table>
        <thead>
            <tr>
                <th>Fichero</th>
                <th>Línea</th>
                <th>Severidad</th>
                <th>Mensaje</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($summary->lintReport->errors as $error) {
            /** @var LintError $error */
            $html .= <<<HTML
<tr>
    <td>{$error->file}</td>
    <td>{$error->line}</td>
    <td>{$error->severity}</td>
    <td>{$error->message}</td>
</tr>
HTML;
        }

        $html .= <<<HTML
        </tbody>
    </table>

    <h2>Problemas de Análisis Estático</h2>
    <table>
        <thead>
            <tr>
                <th>Fichero</th>
                <th>Línea</th>
                <th>Tipo</th>
                <th>Mensaje</th>
            </tr>
        </thead>
        <tbody>
HTML;
        $groupedIssues = $summary->staticAnalysisReport->getIssuesBySeverity();
        foreach ($groupedIssues as $severity => $issues) {
            $title = ucfirst($severity);

        foreach ($summary->staticAnalysisReport->issues as $issue) {
            /** @var StaticAnalysisIssue $issue */
            $html .= <<<HTML
<tr>
    <td>{$issue->fileName}</td>
    <td>{$issue->lineFrom}</td>
    <td>{$issue->type}</td>
    <td>{$issue->message}</td>
</tr>
HTML;
        }
        }
        $html .= <<<HTML
        </tbody>
    </table>

    <h2>Vulnerabilidades (SAST)</h2>
    <table>
        <thead>
            <tr>
                <th>Fichero</th>
                <th>Línea</th>
                <th>Tipo</th>
                <th>Mensaje</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($summary->sastReport->issues as $issue) {
            /** @var SastIssue $issue */
            $html .= <<<HTML
<tr>
    <td>{$issue->file}</td>
    <td>{$issue->line}</td>
    <td>{$issue->type}</td>
    <td>{$issue->message}</td>
</tr>
HTML;
        }

        $html .= <<<HTML
        </tbody>
    </table>

</body>
</html>
HTML;

        file_put_contents($outputPath, $html);
    }
}
